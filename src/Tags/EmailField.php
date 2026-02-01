<?php

namespace Reach\StatamicEasyForms\Tags;

use Reach\StatamicEasyForms\Tags\Concerns\FormatsFieldValues;
use Statamic\Tags\Tags;

class EmailField extends Tags
{
    use FormatsFieldValues;

    protected static $handle = 'email_field';

    /**
     * The {{ email_field }} tag.
     *
     * Renders a field for the email template with proper formatting.
     * When called within a {{ fields }} loop, it reads field data from context.
     *
     * Usage in email template:
     * {{ fields }}
     *     {{ unless fieldtype == 'section' }}
     *         {{ email_field }}
     *     {{ /unless }}
     * {{ /fields }}
     *
     * Alternatively, can be called with explicit field parameter:
     * {{ email_field :field="field_data" }}
     *
     * Handles:
     * - Standard fields (text, textarea, select, etc.)
     * - Array values (checkboxes, multi-select)
     * - Group fields with nested values
     * - Assets fields with CP link
     *
     * @return string Rendered HTML table rows
     */
    public function index(): string
    {
        // Get field data from params (when called directly) or context (when called within loop)
        $field = $this->params->get('field');

        if (! $field || ! is_array($field)) {
            // When called as {{ email_field }} within a loop, the field data is in context
            $field = $this->getFieldFromContext();
        }

        if (! $field || ! is_array($field)) {
            return '';
        }

        $fieldtype = $field['fieldtype'] ?? 'text';
        $value = $field['value'] ?? null;

        // Skip empty values
        if ($this->isEmpty($value)) {
            return '';
        }

        return match ($fieldtype) {
            'group' => $this->renderGroupField($field),
            'assets' => $this->renderAssetsField($field),
            default => $this->renderStandardField($field),
        };
    }

    /**
     * Extract field data from the current context.
     * Used when the tag is called within a {{ fields }} loop.
     */
    protected function getFieldFromContext(): ?array
    {
        $display = $this->context->get('display');
        $value = $this->context->get('value');
        $fieldtype = $this->context->get('fieldtype');

        // If we have the basic field properties in context, build the field array
        if ($display !== null || $fieldtype !== null) {
            return [
                'display' => $display ?? '',
                'value' => $value,
                'fieldtype' => $fieldtype ?? 'text',
                'config' => $this->context->get('config'),
            ];
        }

        return null;
    }

    /**
     * Check if a value is considered empty for email display purposes.
     */
    protected function isEmpty(mixed $value): bool
    {
        return $value === null
            || $value === ''
            || $value === false
            || (is_array($value) && empty($value));
    }

    /**
     * Render a group field with its nested fields.
     */
    protected function renderGroupField(array $field): string
    {
        $display = $field['display'] ?? '';
        $value = $field['value'] ?? null;
        $config = $field['config'] ?? [];
        $configFields = $config['fields'] ?? [];

        if (empty($configFields) || empty($value)) {
            return '';
        }

        $nestedFields = $this->getNestedFieldsWithValues($configFields, $value);

        if (empty($nestedFields)) {
            return '';
        }

        $html = $this->renderGroupHeader($display);

        foreach ($nestedFields as $nestedField) {
            $html .= $this->renderNestedFieldRow(
                $nestedField['display'],
                $nestedField['formatted_value']
            );
        }

        return $html;
    }

    /**
     * Render the group field header row.
     */
    protected function renderGroupHeader(string $display): string
    {
        return <<<HTML
<tr>
    <td colspan="2" style="padding: 16px 0 8px 0;">
        <p style="margin: 0; font-size: 13px; font-weight: bold; color: #2d7a7d; letter-spacing: 0.5px; text-transform: uppercase;">
            {$display}
        </p>
    </td>
</tr>
HTML;
    }

    /**
     * Render a nested field row within a group.
     */
    protected function renderNestedFieldRow(string $display, string $value): string
    {
        return <<<HTML
<tr>
    <td width="35%" valign="middle" style="padding: 12px 10px 12px 15px; border-bottom: 1px solid #eeeeee;">
        <p style="margin: 0; font-size: 12px; font-weight: bold; color: #888888; letter-spacing: 0.5px; line-height: 1.4;">
            {$display}
        </p>
    </td>
    <td width="65%" valign="middle" style="padding: 12px 0 12px 0; border-bottom: 1px solid #eeeeee;">
        <div style="margin: 0; font-size: 14px; color: #222222; line-height: 1.5; word-break: break-word;">
            {$value}
        </div>
    </td>
</tr>
HTML;
    }

    /**
     * Render an assets field with a link to the control panel.
     */
    protected function renderAssetsField(array $field): string
    {
        $display = $field['display'] ?? '';
        $formHandle = $this->getContextValue('form:handle', '');
        $submissionId = $this->getContextValue('id', '');
        $appUrl = config('app.url', '');

        // Build the CP link
        $cpLink = "{$appUrl}/cp/forms/{$formHandle}/submissions/{$submissionId}";

        $linkHtml = <<<HTML
<a href="{$cpLink}" style="color: #2d7a7d; text-decoration: none; font-weight: 500;">
    Login to view file(s) &rarr;
</a>
HTML;

        return $this->renderStandardFieldRow($display, $linkHtml);
    }

    /**
     * Get a value from context, handling colon-separated keys like 'form:handle'.
     */
    protected function getContextValue(string $key, mixed $default = null): mixed
    {
        // First try direct access (works for flat arrays in tests)
        $value = $this->context->get($key);
        if ($value !== null) {
            return $value;
        }

        // Handle colon-separated keys like 'form:handle' by accessing nested objects
        if (str_contains($key, ':')) {
            [$parentKey, $childKey] = explode(':', $key, 2);
            $parent = $this->context->get($parentKey);

            if ($parent === null) {
                return $default;
            }

            if (is_object($parent) && method_exists($parent, $childKey)) {
                return $parent->$childKey();
            }

            if (is_object($parent) && isset($parent->$childKey)) {
                return $parent->$childKey;
            }

            if (is_array($parent) && isset($parent[$childKey])) {
                return $parent[$childKey];
            }
        }

        return $default;
    }

    /**
     * Render a standard field.
     */
    protected function renderStandardField(array $field): string
    {
        $display = $field['display'] ?? '';
        $value = $field['value'] ?? null;
        $fieldtype = $field['fieldtype'] ?? 'text';

        $formattedValue = $this->formatValue($value, $fieldtype);

        if ($formattedValue === '') {
            return '';
        }

        return $this->renderStandardFieldRow($display, $formattedValue);
    }

    /**
     * Render a standard field row.
     */
    protected function renderStandardFieldRow(string $display, string $value): string
    {
        return <<<HTML
<tr>
    <td width="35%" valign="middle" style="padding: 12px 10px 12px 0; border-bottom: 1px solid #eeeeee;">
        <p style="margin: 0; font-size: 12px; font-weight: bold; color: #888888; letter-spacing: 0.5px; line-height: 1.4;">
            {$display}
        </p>
    </td>
    <td width="65%" valign="middle" style="padding: 12px 0 12px 0; border-bottom: 1px solid #eeeeee;">
        <div style="margin: 0; font-size: 14px; color: #222222; line-height: 1.5; word-break: break-word;">
            {$value}
        </div>
    </td>
</tr>
HTML;
    }
}
