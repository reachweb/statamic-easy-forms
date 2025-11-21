<?php

namespace Reach\StatamicEasyForms;

use Reach\StatamicEasyForms\Listeners\ValidateRecaptcha;
use Statamic\Events\FormSubmitted;
use Statamic\Fieldtypes\Radio;
use Statamic\Fieldtypes\Select;
use Statamic\Fieldtypes\Text;
use Statamic\Fieldtypes\Toggle;
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
        // Only register reCAPTCHA validation if secret key is configured
        if (! empty(env('RECAPTCHA_SECRET_KEY'))) {
            $this->listen[FormSubmitted::class] = [
                ValidateRecaptcha::class,
            ];
        }

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
            'easy_forms' => [
                'type' => 'section',
                'display' => 'Easy Forms',
            ],
            'improved_field' => [
                'type' => 'toggle',
                'display' => 'Enable Improved Fields',
                'instructions' => 'Enable improved Alpine fields for supported input types (eg. date, tel)',
            ],
            'datepicker_config' => [
                'type' => 'section',
                'display' => 'Datepicker config',
                'if' => [
                    'input_type' => 'is date',
                ],
            ],
            'date_range' => [
                'type' => 'toggle',
                'display' => 'Date range',
                'instructions' => 'The datepicker will select a date range instead of a single day',
                'if' => [
                    'input_type' => 'is date',
                ],
            ],
            'max_range' => [
                'type' => 'integer',
                'display' => 'Max range',
                'instructions' => 'The maximum amount of days allowed when range mode is enabled',
                'if' => [
                    'input_type' => 'is date',
                    'date_range' => 'is true',
                ],
            ],
            'min_date_today' => [
                'type' => 'integer',
                'display' => 'Minimum date relative to today',
                'instructions' => 'Set to 0 for using today as minimum date or an integer to add days',
                'if' => [
                    'input_type' => 'is date',
                ],
            ],
            'max_date_today' => [
                'type' => 'integer',
                'display' => 'Maximum date relative to today',
                'instructions' => 'Set to 0 for using today as maximum date or an integer to add days',
                'if' => [
                    'input_type' => 'is date',
                ],
            ],
            'min_date' => [
                'type' => 'date',
                'display' => 'Minimum date',
                'instructions' => 'The minimum date allowed in the datepicker',
                'if' => [
                    'input_type' => 'is date',
                ],
                'unless' => [
                    'min_date_today' => 'not null',
                ],
            ],
            'max_date' => [
                'type' => 'date',
                'display' => 'Maximum date',
                'instructions' => 'The maximum date allowed in the datepicker',
                'if' => [
                    'input_type' => 'is date',
                ],
                'unless' => [
                    'max_date_today' => 'not null',
                ],
            ],
            'dont_close_after_selection' => [
                'type' => 'toggle',
                'display' => 'Don\'t close after selection',
                'instructions' => 'Do not close the datepicker after the user selects a date',
                'if' => [
                    'input_type' => 'is date',
                ],
            ],
        ]);

        Radio::appendConfigFields([
            'easy_forms' => [
                'type' => 'section',
                'display' => 'Easy Forms',
            ],
            'improved_field' => [
                'type' => 'toggle',
                'display' => 'Enable Improved Fields',
                'instructions' => 'Enable improved Alpine fields for supported input types (eg. date, tel)',
            ],
        ]);

        Select::appendConfigFields([
            'easy_forms' => [
                'type' => 'section',
                'display' => 'Easy Forms',
            ],
            'improved_field' => [
                'type' => 'toggle',
                'display' => 'Enable Improved Fields',
                'instructions' => 'Enable improved Alpine fields for supported input types (eg. date, tel)',
            ],
        ]);

        Toggle::appendConfigFields([
            'easy_forms' => [
                'type' => 'section',
                'display' => 'Easy Forms',
            ],
            'label_override' => [
                'type' => 'markdown',
                'display' => 'Label override',
                'instructions' => 'Override the inline label here to use Markdown.',
            ],
        ]);
    }
}
