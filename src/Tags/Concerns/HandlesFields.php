<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Statamic\Fields\Field;

trait HandlesFields
{
    /**
     * Process a field to extract needed properties and add optional flag.
     *
     * @param  \Statamic\Fields\Field  $field
     * @param  string|null  $parentHandle  Parent handle for nested fields (e.g., group fields)
     */
    protected function processField($field, ?string $parentHandle = null): array
    {
        // Get config defaults from the fieldtype (includes prepend, append, etc.)
        $configDefaults = Field::commonFieldOptions()->all()
            ->merge($field->fieldtype()->configFields()->all())
            ->map->get('default')
            ->filter()
            ->all();

        // Inject phone codes
        if ($field->get('input_type') === 'tel' && $field->get('improved_field') === true) {
            $configDefaults['options'] = $this->getDictionaryOptions('country_phone_codes');
        }

        // Merge config defaults with field data, then add custom data
        $fieldData = array_merge(
            $configDefaults,
            $field->toArray(),
            [
                'optional' => $this->isFieldOptional($field),
                'value' => $field->value() ?? $field->defaultValue(),
                'has_own_instructions' => ! empty($field->get('instructions')),
            ],
            $field->fieldtype()->extraRenderableFieldData()
        );

        // Process group fields - extract nested fields
        if ($field->type() === 'group' && ! empty($fieldData['fields'])) {
            $fieldData['group_fields'] = $this->processGroupFields(
                $fieldData['fields'],
                $field->handle()
            );
        }

        // Process grid fields - extract nested fields with __INDEX__ placeholders
        if ($field->type() === 'grid' && ! empty($fieldData['fields'])) {
            $fieldData['grid_fields'] = $this->processGridFields(
                $fieldData['fields'],
                $field->handle()
            );
            $fieldData['min_rows'] = $fieldData['min_rows'] ?? 1;
            $fieldData['max_rows'] = $fieldData['max_rows'] ?? null;
            $fieldData['fixed_rows'] = $fieldData['fixed_rows'] ?? null;
            $fieldData['is_fixed'] = ! empty($fieldData['fixed_rows']);
            $fieldData['add_row_text'] = $fieldData['add_row'] ?? __('Add Row');
        }

        // Add parent handle for nested fields (used in templates for name prefixing)
        if ($parentHandle) {
            $fieldData['parent_handle'] = $parentHandle;
        }

        return $fieldData;
    }

    /**
     * Process nested fields within a group field.
     */
    protected function processGroupFields(array $fields, string $parentHandle): array
    {
        return collect($fields)->map(function (array $fieldConfig) use ($parentHandle) {
            $handle = $fieldConfig['handle'];
            $nestedField = new Field($handle, $fieldConfig['field']);
            $processed = $this->processField($nestedField);

            return array_merge($processed, [
                'parent_handle' => $parentHandle,
                'field_name' => "{$parentHandle}[{$handle}]",
                'field_key' => "{$parentHandle}.{$handle}",
            ]);
        })->all();
    }

    /**
     * Process nested fields within a grid field.
     * Uses __INDEX__ placeholders that JavaScript will replace with actual row indices.
     */
    protected function processGridFields(array $fields, string $parentHandle): array
    {
        return collect($fields)->map(function (array $fieldConfig) use ($parentHandle) {
            $handle = $fieldConfig['handle'];
            $nestedField = new Field($handle, $fieldConfig['field']);
            $processed = $this->processField($nestedField);

            return array_merge($processed, [
                'parent_handle' => $parentHandle,
                'field_name' => "{$parentHandle}[__INDEX__][{$handle}]",
                'field_key' => "{$parentHandle}.__INDEX__.{$handle}",
            ]);
        })->all();
    }

    /**
     * Check if a field is optional (not required).
     *
     * @param  \Statamic\Fields\Field  $field
     */
    protected function isFieldOptional($field): bool
    {
        $validate = $field->get('validate');

        if (! $validate) {
            return true;
        }

        // Convert to array if string
        if (is_string($validate)) {
            $validate = explode('|', $validate);
        }

        $requiredKeys = [
            'required',
            'accepted',
            'required_if',
            'required_unless',
            'required_with',
            'required_with_all',
            'required_without',
            'required_without_all',
            'required_array_keys',
            'filled',
            'present',
        ];

        // Check if any validation rule starts with a required keyword
        foreach ((array) $validate as $rule) {
            // Handle array format ['required' => true] or string format 'required'
            $ruleName = is_array($rule) ? key($rule) : $rule;

            // Extract rule name if it has parameters (e.g., "required_if:other,value")
            if (is_string($ruleName) && str_contains($ruleName, ':')) {
                $ruleName = explode(':', $ruleName)[0];
            }

            if (in_array($ruleName, $requiredKeys)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process all fields from the blueprint.
     *
     * @param  \Statamic\Fields\Blueprint  $blueprint
     */
    protected function processAllFields($blueprint): array
    {
        return collect($blueprint->fields()->all())
            ->map(fn ($field) => $this->processField($field))
            ->values()
            ->all();
    }
}
