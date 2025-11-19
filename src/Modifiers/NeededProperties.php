<?php

namespace Reach\StatamicEasyForms\Modifiers;

use Statamic\Modifiers\Modifier;

class NeededProperties extends Modifier
{
    /**
     * Filter field data to only include properties needed for frontend rendering.
     *
     * This removes unnecessary backend metadata to reduce payload size and
     * prevent exposing internal configuration.
     *
     * @param  mixed  $value  The field array to filter
     * @param  array  $params  Additional parameters (not used)
     * @param  array  $context  The context array
     * @return array The filtered field array
     */
    public function index($value, $params, $context): array
    {
        if (!is_array($value)) {
            return [];
        }

        // If it's a single field, wrap it in an array
        $isSingleField = !isset($value[0]);
        $fields = $isSingleField ? [$value] : $value;

        $filtered = collect($fields)->map(function ($field) {
            return $this->filterField($field);
        })->all();

        return $isSingleField ? $filtered[0] : $filtered;
    }

    /**
     * Filter a single field to only include needed properties.
     *
     * @param  array  $field  The field data
     * @return array The filtered field data
     */
    protected function filterField(array $field): array
    {
        // Properties needed for frontend rendering
        $neededProperties = [
            // Basic field properties
            'handle',
            'display',
            'type',
            'input_type',
            'instructions',
            'instructions_position',
            'validate',
            'optional',
            'visibility',
            'width',
            'value',
            'default',

            // Field-specific configuration
            'placeholder',
            'prepend',
            'append',
            'character_limit',
            'antlers',

            // Select/Radio/Checkboxes/Dictionary options
            'options',
            'default_option',
            'cast_booleans',
            'inline',
            'dictionary',
            'searchable',

            // Text/Textarea specific
            'input_type',
            'autocomplete',

            // Integer/Number specific
            'min',
            'max',
            'step',

            // Date/Time specific
            'mode',
            'time_enabled',
            'time_required',
            'earliest_date',
            'latest_date',
            'format',
            'full_width',
            'inline',
            'columns',
            'rows',

            // File/Assets specific
            'container',
            'folder',
            'max_files',
            'min_files',
            'max_file_size',
            'allowed_extensions',
            'restrict',

            // Toggle specific
            'inline_label',
            'inline_label_when_true',

            // Conditional logic
            'if',
            'unless',
            'if_any',
            'show_when',
            'hide_when',
        ];

        return collect($field)
            ->only($neededProperties)
            ->filter(function ($value) {
                // Remove null values and empty strings, but keep false and 0
                return $value !== null && $value !== '';
            })
            ->all();
    }
}
