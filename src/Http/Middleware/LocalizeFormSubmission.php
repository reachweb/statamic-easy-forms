<?php

namespace Reach\StatamicEasyForms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\Response;

/**
 * Localizes form submission requests on multisite installations.
 *
 * Statamic's form action route (/!/forms/{form}) only runs the plain `web`
 * middleware group, not `statamic.web`, so Statamic's Localize middleware never
 * sets the app locale for submissions. Core localizes blueprint validation
 * messages itself (FrontendFormRequest wraps validation in withLocale), but the
 * rest of the request — including messages thrown by FormSubmitted listeners
 * such as the addon's reCAPTCHA validation — still runs in the default locale.
 * Like Statamic's own FormController does for emails, the site is resolved from
 * the referring page and the app locale is set accordingly.
 *
 * Can be disabled via the easy-forms.localize_submissions config option.
 */
class LocalizeFormSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLocalize($request)) {
            return $next($request);
        }

        if (! $site = Site::findByUrl(URL::previous())) {
            return $next($request);
        }

        $originalLocale = app()->getLocale();
        app()->setLocale($site->lang());

        $response = $next($request);

        app()->setLocale($originalLocale);

        return $response;
    }

    /**
     * Localize only form action requests on multisites, unless disabled.
     */
    protected function shouldLocalize(Request $request): bool
    {
        return config('easy-forms.localize_submissions', true)
            && Site::hasMultiple()
            && $request->is(trim(config('statamic.routes.action'), '/').'/forms/*');
    }
}
