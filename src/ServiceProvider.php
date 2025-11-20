<?php

namespace Reach\StatamicEasyForms;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $scripts = [
        __DIR__.'/../dist/js/easy-forms.js',
    ];

    protected $stylesheets = [
        __DIR__.'/../dist/css/easy-forms.css',
    ];

    public function bootAddon()
    {
        // Load views from the resources/views directory
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'statamic-easy-forms');

        // Publish views for customization
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/statamic-easy-forms'),
        ], 'easy-forms-views');

        // Publish assets for production use
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/easy-forms'),
        ], 'easy-forms-assets');
    }
}
