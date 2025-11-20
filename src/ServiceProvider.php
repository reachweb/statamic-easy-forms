<?php

namespace Reach\StatamicEasyForms;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Fieldtypes\Text;

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
        $this->addConfigOptions();
        
        // Load views from the resources/views directory
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'statamic-easy-forms');

        // Load translation files
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'statamic-easy-forms');

        // Publish views for customization
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/statamic-easy-forms'),
        ], 'easy-forms-views');

        // Publish assets for production use
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/easy-forms'),
        ], 'easy-forms-assets');
    }

    public function addConfigOptions()
    {
        Text::appendConfigFields([
            'improved_fieldtypes' => [
                'type' => 'toggle',
                'display' => 'Enable Improved Fieldtypes',
                'description' => 'Enable dictionary for supported input types (eg. date, tel)'
            ],
        ]);
    }
}
