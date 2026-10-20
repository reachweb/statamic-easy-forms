<?php

namespace Reach\StatamicEasyForms;

class EmailTemplate
{
    /**
     * The view to pick as the "HTML view" of a form's email connection.
     *
     * The control panel only accepts views from resources/views (and from the
     * statamic.forms.email_view_folder folder when that is set), so the template
     * is published there instead of being used through the addon's namespace.
     */
    public static function view(): string
    {
        if ($folder = trim((string) config('statamic.forms.email_view_folder'), '/')) {
            return $folder.'/easy-forms-submission';
        }

        return 'vendor/statamic-easy-forms/emails/form-submission';
    }

    /**
     * The file the email template is published to.
     */
    public static function path(): string
    {
        return resource_path('views/'.static::view().'.antlers.html');
    }
}
