<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

trait FormatsFieldValues
{
    /**
     * Format a field value for display in emails.
     *
     * @param  mixed  $value  The raw field value
     * @param  string  $fieldtype  The field type (text, checkboxes, select, etc.)
     * @return string The formatted value for display
     */
    protected function formatValue(mixed $value, string $fieldtype = 'text'): string
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return '';
        }

        // Handle boolean values
        if (is_bool($value)) {
            return $value ? 'Yes' : '';
        }

        // Handle arrays (checkboxes, multi-select, etc.)
        if (is_array($value)) {
            return $this->formatArrayValue($value);
        }

        // Handle objects with label/value properties
        if (is_object($value)) {
            return $this->extractLabelFromObject($value);
        }

        return e((string) $value);
    }

    /**
     * Format an array value as comma-separated string.
     * Handles arrays of strings, label/value pairs, and nested objects.
     *
     * @param  array  $array  The array to format
     * @return string Comma-separated values
     */
    protected function formatArrayValue(array $array): string
    {
        $values = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                $label = $item['label'] ?? $item['value'] ?? $item['key'] ?? null;
                if ($label !== null) {
                    $values[] = e((string) $label);
                }
            } elseif (is_object($item)) {
                $values[] = $this->extractLabelFromObject($item);
            } else {
                $values[] = e((string) $item);
            }
        }

        return implode(', ', $values);
    }

    /**
     * Extract label from an object value.
     *
     * @param  object  $object  The object to extract label from
     * @return string The extracted label
     */
    protected function extractLabelFromObject(object $object): string
    {
        // Try common label properties
        foreach (['label', 'value', 'key'] as $prop) {
            if (isset($object->$prop)) {
                return e((string) $object->$prop);
            }
        }

        // Fallback to string cast
        return e((string) $object);
    }

    /**
     * Extract nested fields with their values from a group field.
     *
     * @param  array  $configFields  The config:fields array containing field definitions
     * @param  mixed  $groupValue  The group field's value (associative array or Values object)
     * @return array Array of [display, value, formatted_value] for each nested field with a value
     */
    protected function getNestedFieldsWithValues(array $configFields, mixed $groupValue): array
    {
        $result = [];

        if (empty($groupValue)) {
            return $result;
        }

        foreach ($configFields as $fieldConfig) {
            $handle = $fieldConfig['handle'] ?? null;
            $fieldSettings = $fieldConfig['field'] ?? [];
            $display = $fieldSettings['display'] ?? $handle ?? '';
            $fieldtype = $fieldSettings['type'] ?? 'text';

            if (! $handle) {
                continue;
            }

            // Get the value for this nested field
            $nestedValue = $this->extractNestedValue($groupValue, $handle);

            // Skip empty values
            if ($nestedValue === null || $nestedValue === '' || (is_array($nestedValue) && empty($nestedValue))) {
                continue;
            }

            $result[] = [
                'handle' => $handle,
                'display' => $display,
                'value' => $nestedValue,
                'fieldtype' => $fieldtype,
                'formatted_value' => $this->formatValue($nestedValue, $fieldtype),
            ];
        }

        return $result;
    }

    /**
     * Extract a nested value from a group field's value.
     *
     * @param  mixed  $groupValue  The group value (array, object, or Values)
     * @param  string  $handle  The field handle to extract
     * @return mixed The extracted value
     */
    protected function extractNestedValue(mixed $groupValue, string $handle): mixed
    {
        $value = null;

        if (is_array($groupValue) && isset($groupValue[$handle])) {
            $value = $groupValue[$handle];
        } elseif ($groupValue instanceof \ArrayAccess && isset($groupValue[$handle])) {
            $value = $groupValue[$handle];
        } elseif (is_object($groupValue) && isset($groupValue->$handle)) {
            $value = $groupValue->$handle;
        }

        return $this->unwrapValue($value);
    }

    /**
     * Unwrap a Statamic Value object to get its raw value.
     */
    protected function unwrapValue(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, 'value')) {
            return $value->value();
        }

        return $value;
    }
}
