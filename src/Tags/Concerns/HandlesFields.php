<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;
use Statamic\Fields\Field;
use Statamic\Forms\Fields\FormField;
use Statamic\Forms\Fields\FormFields;

trait HandlesFields
{
    protected static ?Collection $commonFieldOptionsCache = null;

    private const ADDRESS_FIELDS = [
        'line_1' => ['autocomplete' => 'address-line1'],
        'line_2' => ['autocomplete' => 'address-line2'],
        'city' => ['autocomplete' => 'address-level2', 'width' => 50],
        'region' => ['autocomplete' => 'address-level1', 'width' => 50],
        'postcode' => ['autocomplete' => 'postal-code', 'width' => 50],
        'country' => ['autocomplete' => 'country', 'width' => 50],
    ];

    private const REQUIRED_VALIDATION_KEYS = [
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

    /**
     * Get cached common field options.
     */
    protected function getCachedCommonFieldOptions(): Collection
    {
        if (self::$commonFieldOptionsCache === null) {
            self::$commonFieldOptionsCache = Field::commonFieldOptions()->all();
        }

        return self::$commonFieldOptionsCache;
    }

    /**
     * Process a field to extract needed properties and add optional flag.
     *
     * @param  Field  $field
     * @param  string|null  $parentHandle  Parent handle for nested fields (e.g., group fields)
     * @param  FormField|null  $formField  The form field the blueprint field was made from
     */
    protected function processField($field, ?string $parentHandle = null, ?FormField $formField = null): array
    {
        // Get config defaults from the fieldtype (includes prepend, append, etc.)
        $configDefaults = $this->getCachedCommonFieldOptions()
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

        // Let the form fieldtype adjust the data like {{ form:create }} does, e.g. to leave out hidden options
        if ($formField) {
            $fieldData = $formField->fieldtype()->prepareFrontEndRenderable($fieldData);
        }

        // The native min/max are validated server-side, so they win over the older min_value/max_value
        if ($field->type() === 'integer') {
            $fieldData['min_value'] = $fieldData['min'] ?? $fieldData['min_value'] ?? null;
            $fieldData['max_value'] = $fieldData['max'] ?? $fieldData['max_value'] ?? null;
        }

        // Forms 2 saves ranking options as key/value rows, which core only turns into a map for top-level fields
        if ($field->type() === 'ranking' && collect($fieldData['options'] ?? [])->every(fn ($option) => is_array($option) && isset($option['key']))) {
            $fieldData['options'] = collect($fieldData['options'])
                ->reject(fn (array $option) => $option['hidden'] ?? false)
                ->mapWithKeys(fn (array $option) => [$option['key'] => $option['value'] ?? $option['key']])
                ->all();
        }

        // Process group fields - extract nested fields
        if ($field->type() === 'group' && ! empty($fieldData['fields'])) {
            $fieldData['group_fields'] = $this->processGroupFields($field, $formField);

            // The raw config would end up in the page, including options that are hidden from visitors
            unset($fieldData['fields']);
        }

        // A Forms Pro address is a fixed set of fields posted as an array, so it renders like a group
        if ($field->type() === 'address' && method_exists($field->fieldtype(), 'fields')) {
            $fieldData['group_fields'] = $this->processAddressFields($field, $fieldData['countries'] ?? []);
            $fieldData['type'] = 'group';
        }

        // Process grid fields - extract nested fields with __INDEX__ placeholders
        if ($field->type() === 'grid' && ! empty($fieldData['fields'])) {
            $fieldData['grid_fields'] = $this->processGridFields($field);
            $fieldData['min_rows'] = $fieldData['min_rows'] ?? 1;
            $fieldData['max_rows'] = $fieldData['max_rows'] ?? null;
            $fieldData['fixed_rows'] = $fieldData['fixed_rows'] ?? null;
            $fieldData['dynamic_rows_field'] = $fieldData['dynamic_rows_field'] ?? null;

            // fixed_rows takes precedence over dynamic_rows_field
            if (! empty($fieldData['fixed_rows'])) {
                $fieldData['dynamic_rows_field'] = null;
            }

            $fieldData['is_fixed'] = ! empty($fieldData['fixed_rows']) || ! empty($fieldData['dynamic_rows_field']);
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
    protected function processGroupFields(Field $group, ?FormField $formField = null): array
    {
        // The form field holds the children as form fields, before core turns them into blueprint fields
        $formFields = new FormFields([
            'sections' => [['fields' => $formField?->config()['fields'] ?? []]],
        ]);

        return $group->fieldtype()->fields()->all()
            ->map(fn (Field $field, string $handle) => [
                ...$this->processField($field, formField: $formFields->field($handle)),
                ...$this->nestedGroupKeys($group->handle(), $handle),
            ])
            ->values()
            ->all();
    }

    /**
     * Process the fixed set of fields within a Forms Pro address field.
     *
     * @param  array<int, array{code: string, name: string}>  $countries
     */
    protected function processAddressFields(Field $address, array $countries): array
    {
        return $address->fieldtype()->fields()->all()
            ->map(fn (Field $field, string $handle) => [
                ...$this->processField($this->prepareAddressField($field, $handle, $countries)),
                ...$this->nestedGroupKeys($address->handle(), $handle),
            ])
            ->values()
            ->all();
    }

    /**
     * Add the width, autocomplete token and country options to an address field.
     */
    private function prepareAddressField(Field $field, string $handle, array $countries): Field
    {
        return new Field($handle, [
            ...$field->config(),
            ...self::ADDRESS_FIELDS[$handle] ?? [],
            ...$handle === 'country' && $countries ? [
                'type' => 'select',
                'options' => array_column($countries, 'name', 'code'),
            ] : [],
        ]);
    }

    /**
     * The keys that nest a field's name, id and value within its group.
     *
     * @return array{parent_handle: string, field_name: string, field_key: string}
     */
    private function nestedGroupKeys(string $parentHandle, string $handle): array
    {
        return [
            'parent_handle' => $parentHandle,
            'field_name' => "{$parentHandle}[{$handle}]",
            'field_key' => "{$parentHandle}.{$handle}",
        ];
    }

    /**
     * Process nested fields within a grid field.
     * Uses __INDEX__ placeholders that JavaScript will replace with actual row indices.
     */
    protected function processGridFields(Field $grid): array
    {
        return $grid->fieldtype()->fields()->all()
            ->map(fn (Field $field, string $handle) => [
                ...$this->processField($field),
                'parent_handle' => $grid->handle(),
                'field_name' => "{$grid->handle()}[__INDEX__][{$handle}]",
                'field_key' => "{$grid->handle()}.__INDEX__.{$handle}",
            ])
            ->values()
            ->all();
    }

    /**
     * Check if a field is optional (not required).
     *
     * @param  Field  $field
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

        // Check if any validation rule starts with a required keyword
        foreach ((array) $validate as $rule) {
            // Handle array format ['required' => true] or string format 'required'
            $ruleName = is_array($rule) ? key($rule) : $rule;

            // Extract rule name if it has parameters (e.g., "required_if:other,value")
            if (is_string($ruleName) && str_contains($ruleName, ':')) {
                $ruleName = explode(':', $ruleName)[0];
            }

            if (in_array($ruleName, self::REQUIRED_VALIDATION_KEYS)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process all fields from the blueprint.
     *
     * @param  Blueprint  $blueprint
     * @param  Collection<string, FormField>|null  $formFields
     */
    protected function processAllFields($blueprint, ?Collection $formFields = null): array
    {
        return collect($blueprint->fields()->all())
            ->map(fn ($field) => $this->processField($field, formField: $formFields?->get($field->handle())))
            ->values()
            ->all();
    }
}
