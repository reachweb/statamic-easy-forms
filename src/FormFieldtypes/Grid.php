<?php

namespace Reach\StatamicEasyForms\FormFieldtypes;

use Statamic\Forms\Fields\FormFieldtype;
use Statamic\Support\Arr;

use function Statamic\trans as __;

class Grid extends FormFieldtype
{
    protected static $fieldtype = 'grid';

    protected $description = 'Repeatable rows of fields, e.g. one row per attendee.';

    protected $icon = 'fieldtype-grid';

    // Not "structure": fields in that category don't collect a value, so their rows would be left out of emails
    protected $categories = ['other'];

    protected $keywords = ['repeater', 'rows', 'table'];

    protected function configFieldItems(): array
    {
        return [
            'fields' => [
                'display' => __('Fields'),
                'instructions' => __('statamic::fieldtypes.grid.config.fields'),
                'type' => 'fields',
                'full_width_setting' => true,
            ],
            'min_rows' => [
                'display' => __('Minimum Rows'),
                'instructions' => __('statamic::fieldtypes.grid.config.min_rows'),
                'type' => 'integer',
                'width' => 50,
            ],
            'max_rows' => [
                'display' => __('Maximum Rows'),
                'instructions' => __('statamic::fieldtypes.grid.config.max_rows'),
                'type' => 'integer',
                'width' => 50,
            ],
            'add_row' => [
                'display' => __('Add Row Label'),
                'instructions' => __('statamic::fieldtypes.grid.config.add_row'),
                'type' => 'text',
                'placeholder' => __('Add Row'),
            ],
            'fixed_rows' => [
                'type' => 'integer',
                'display' => 'Fixed rows',
                'instructions' => 'Render exactly this many rows with no add/remove buttons. Overrides min/max.',
            ],
            'dynamic_rows_field' => [
                'type' => 'text',
                'display' => 'Dynamic rows field',
                'instructions' => 'Handle of a field whose value controls the number of grid rows. The grid will behave as fixed (no add/remove buttons).',
                'unless' => [
                    'fixed_rows' => 'not null',
                ],
            ],
        ];
    }

    public function toFieldArray(): array
    {
        return [
            'type' => 'grid',
            'fields' => $this->config('fields', []),
            ...Arr::except($this->config(), ['type', 'fields']),
        ];
    }
}
