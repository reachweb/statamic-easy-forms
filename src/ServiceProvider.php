<?php

namespace Reach\StatamicEasyForms;

use Reach\StatamicEasyForms\Modifiers\NeededProperties;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $modifiers = [
        NeededProperties::class,
    ];

    protected $scripts = [
        __DIR__.'/../dist/js/easy-forms.js',
    ];

    protected $stylesheets = [
        __DIR__.'/../dist/css/easy-forms.css',
    ];

    public function bootAddon()
    {
        // Publish assets for production use
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/easy-forms'),
        ], 'easy-forms-assets');
    }
}
