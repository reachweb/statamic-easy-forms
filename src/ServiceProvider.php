<?php

namespace Reach\StatamicEasyForms;

use Reach\StatamicEasyForms\Http\Middleware\LocalizeFormSubmission;
use Statamic\Fieldtypes\Integer;
use Statamic\Fieldtypes\Radio;
use Statamic\Fieldtypes\Select;
use Statamic\Fieldtypes\Text;
use Statamic\Fieldtypes\Toggle;
use Statamic\Forms\Fieldtypes\DatePicker;
use Statamic\Forms\Fieldtypes\Dropdown;
use Statamic\Forms\Fieldtypes\MultiChoice;
use Statamic\Forms\Fieldtypes\Number;
use Statamic\Forms\Fieldtypes\Phone;
use Statamic\Forms\Fieldtypes\Toggle as ToggleFormFieldtype;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $publishables = [
        __DIR__.'/../dist' => '',
    ];

    protected $middlewareGroups = [
        'web' => [
            LocalizeFormSubmission::class,
        ],
    ];

    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/easy-forms.php', 'easy-forms');

        $this->publishes([
            __DIR__.'/../config/easy-forms.php' => config_path('easy-forms.php'),
        ], 'easy-forms-config');

        $this->addConfigOptions();

        // Load views from the resources/views directory
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'statamic-easy-forms');

        // Load translation files
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'statamic-easy-forms');

        // Publish views for customization
        $this->publishes([
            __DIR__.'/../resources/views/form' => resource_path('views/vendor/statamic-easy-forms/form'),
        ], 'easy-forms-views');

        // Publish email template where the email connection's "HTML view" picker can find it
        $this->publishes([
            __DIR__.'/../resources/views/emails/form-submission.antlers.html' => EmailTemplate::path(),
        ], 'easy-forms-emails');

        // Publish theme file
        $this->publishes([
            __DIR__.'/../resources/css/theme.css' => resource_path('css/easy-forms-theme.css'),
        ], 'easy-forms-theme');
    }

    public function addConfigOptions()
    {
        $section = [
            'easy_forms' => [
                'type' => 'section',
                'display' => 'Easy Forms',
            ],
        ];

        $improvedField = [
            'type' => 'toggle',
            'display' => 'Enable Improved Fields',
        ];

        $improvedChoices = [
            ...$section,
            'improved_field' => [
                ...$improvedField,
                'instructions' => 'Show the options as buttons',
            ],
        ];

        $improvedSelect = [
            ...$section,
            'improved_field' => [
                ...$improvedField,
                'instructions' => 'Use a searchable dropdown',
            ],
        ];

        $labelOverride = [
            ...$section,
            'label_override' => [
                'type' => 'markdown',
                'display' => 'Label override',
                'instructions' => 'Override the inline label here to use Markdown.',
            ],
        ];

        // The native Min and Max settings set the limits (and are validated server-side)
        $integerTemplate = [
            ...$section,
            'integer_template' => [
                'type' => 'button_group',
                'display' => 'Field template',
                'instructions' => 'Select the type of template you want to use. Counter and Rating use the Min and Max values.',
                'options' => [
                    'simple' => 'Simple',
                    'counter' => 'Counter',
                    'rating' => 'Rating',
                ],
                'default' => 'simple',
            ],
            'increment_amount' => [
                'type' => 'integer',
                'display' => 'Increment amount',
                'instructions' => 'How much the value should change when user clicks + or -',
                'if' => [
                    'integer_template' => 'is counter',
                ],
            ],
        ];

        $dateLimits = [
            'min_date_today' => [
                'type' => 'integer',
                'display' => 'Minimum date relative to today',
                'instructions' => 'Set to 0 for using today as minimum date or an integer to add days',
            ],
            'max_date_today' => [
                'type' => 'integer',
                'display' => 'Maximum date relative to today',
                'instructions' => 'Set to 0 for using today as maximum date or an integer to add days',
            ],
            'min_date' => [
                'type' => 'date',
                'display' => 'Minimum date',
                'instructions' => 'The minimum date allowed in the datepicker',
                'unless' => [
                    'min_date_today' => 'not null',
                ],
            ],
            'max_date' => [
                'type' => 'date',
                'display' => 'Maximum date',
                'instructions' => 'The maximum date allowed in the datepicker',
                'unless' => [
                    'max_date_today' => 'not null',
                ],
            ],
            'dont_close_after_selection' => [
                'type' => 'toggle',
                'display' => 'Don\'t close after selection',
                'instructions' => 'Do not close the datepicker after the user selects a date',
            ],
        ];

        // Form fieldtypes, offered by the form builder
        Phone::appendConfigFields([
            ...$section,
            'improved_field' => [
                ...$improvedField,
                'instructions' => 'Add a country code picker',
            ],
        ]);

        // A range uses the classic date fieldtype's range mode, so it's validated and stored as {start, end}
        DatePicker::appendConfigFields([
            ...$section,
            'mode' => [
                'type' => 'button_group',
                'display' => 'Mode',
                'instructions' => 'Select a single date or a date range',
                'options' => [
                    'single' => 'Single',
                    'range' => 'Range',
                ],
                'default' => 'single',
            ],
            'max_range' => [
                'type' => 'integer',
                'display' => 'Max range',
                'instructions' => 'The maximum amount of days allowed in a range',
                'if' => [
                    'mode' => 'is range',
                ],
            ],
            ...$dateLimits,
        ]);

        MultiChoice::appendConfigFields($improvedChoices);
        Dropdown::appendConfigFields($improvedSelect);
        ToggleFormFieldtype::appendConfigFields($labelOverride);
        Number::appendConfigFields($integerTemplate);

        // Classic fieldtypes, still used by group and grid children and fieldset fields. Until v7, core
        // also copies these onto every form fieldtype wrapping them, so they reuse the handles above
        // and are hidden on the ones they don't apply to (short answer, email, yes/no, currency...)
        Text::appendConfigFields([
            ...$this->withCondition('if', ['input_type' => 'contains_any tel, date, time'], [
                ...$section,
                'improved_field' => [
                    ...$improvedField,
                    'instructions' => 'Enable improved Alpine fields for supported input types (eg. date, tel)',
                ],
            ]),
            ...$this->withCondition('if', ['input_type' => 'is date'], [
                'datepicker_config' => [
                    'type' => 'section',
                    'display' => 'Datepicker config',
                ],
                'date_range' => [
                    'type' => 'toggle',
                    'display' => 'Date range',
                    'instructions' => 'The datepicker will select a date range instead of a single day',
                ],
                'max_range' => [
                    'type' => 'integer',
                    'display' => 'Max range',
                    'instructions' => 'The maximum amount of days allowed when range mode is enabled',
                    'if' => [
                        'date_range' => 'is true',
                    ],
                ],
                ...$dateLimits,
            ]),
        ]);

        Radio::appendConfigFields($this->withCondition('unless', ['type' => 'is yes_no'], $improvedChoices));
        Select::appendConfigFields($improvedSelect);
        Toggle::appendConfigFields($labelOverride);
        Integer::appendConfigFields($this->withCondition('unless', ['type' => 'is currency'], $integerTemplate));
    }

    /**
     * Add a condition to each of the given config fields.
     */
    private function withCondition(string $key, array $condition, array $fields): array
    {
        return collect($fields)
            ->map(fn (array $field) => [...$field, $key => [...$condition, ...($field[$key] ?? [])]])
            ->all();
    }
}
