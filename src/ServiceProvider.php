<?php

namespace Reach\StatamicEasyForms;

use Reach\StatamicEasyForms\Listeners\ValidateRecaptcha;
use Statamic\Events\FormSubmitted;
use Statamic\Fieldtypes\Integer;
use Statamic\Fieldtypes\Radio;
use Statamic\Fieldtypes\Select;
use Statamic\Fieldtypes\Text;
use Statamic\Fieldtypes\Toggle;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $publishables = [
        __DIR__.'/../dist' => '',
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
            __DIR__.'/../resources/views/form' => resource_path('views/vendor/statamic-easy-forms/form'),
        ], 'easy-forms-views');

        // Publish email template
        $this->publishes([
            __DIR__.'/../resources/views/emails' => resource_path('views/vendor/statamic-easy-forms/emails'),
        ], 'easy-forms-emails');

        // Publish theme file
        $this->publishes([
            __DIR__.'/../resources/css/theme.css' => resource_path('css/easy-forms-theme.css'),
        ], 'easy-forms-theme');
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

        Integer::appendConfigFields([
            'easy_forms' => [
                'type' => 'section',
                'display' => 'Easy Forms',
            ],
            'integer_template' => [
                'type' => 'button_group',
                'display' => 'Field template',
                'instructions' => 'Select the type of template you want to use',
                'options' => [
                    'simple' => 'Simple',
                    'counter' => 'Counter',
                    'rating' => 'Rating',
                ],
                'default' => 'simple',
            ],
            'min_value' => [
                'type' => 'integer',
                'display' => 'Minimum value',
                'instructions' => 'Select the minimum value allowed',
                'unless' => [
                    'integer_template' => 'is simple',
                ],
            ],
            'max_value' => [
                'type' => 'integer',
                'display' => 'Maximum value',
                'instructions' => 'Select the maximum value allowed',
                'unless' => [
                    'integer_template' => 'is simple',
                ],
            ],
            'increment_amount' => [
                'type' => 'integer',
                'display' => 'Increment amount',
                'instructions' => 'How much the value should change when user clicks + or -',
                'if' => [
                    'integer_template' => 'is counter',
                ],
            ],
        ]);
    }
}
