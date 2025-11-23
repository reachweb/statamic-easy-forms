<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Statamic\Fields\Field;

trait HandlesFields
{
    /**
     * Process a field to extract needed properties and add optional flag.
     *
     * @param  \Statamic\Fields\Field  $field
     */
    protected function processField($field): array
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
                'has_own_instructions' => !empty($field->get('instructions')),
            ],
            $field->fieldtype()->extraRenderableFieldData()
        );

        return $fieldData;
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
