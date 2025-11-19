<?php

namespace Reach\StatamicEasyForms;

use Statamic\Facades\Dictionary;
use Statamic\Facades\Form;
use Statamic\Fields\Field;
use Statamic\Tags\Tags;

class EasyForm extends Tags
{
    protected static $handle = 'easyform';

    /**
     * The {{ easyform }} tag.
     *
     * Renders a complete form with all fields automatically.
     *
     * Usage: {{ easyform handle="contact" }}
     *
     * Available parameters:
     * - handle (required): The form handle
     * - class: Custom CSS classes for the form wrapper
     * - button_class: Custom CSS classes for the submit button
     * - hide_fields: Array of field handles to hide (e.g., hide_fields="field1|field2")
     * - prepopulated_data: Array of field values to prepopulate
     * - text_under_form: Text or HTML to display below the form
     * - event_name: Custom analytics event name (default: "formSubmitted")
     *
     * @return string Rendered HTML
     */
    public function index(): string
    {
        $handle = $this->params->get('handle');

        if (!$handle) {
            throw new \Exception('A form handle is required for the easyform tag.');
        }

        /** @var \Statamic\Forms\Form $form */
        $form = Form::find($handle);

        if (!$form) {
            throw new \Exception("Form with handle [$handle] cannot be found.");
        }

        // Get and process all fields from the blueprint
        $processedFields = collect($form->blueprint()->fields()->all())
            ->map(fn ($field) => $this->processField($field))
            ->values()
            ->all();

        // Prepare data for the view
        $data = [
            'handle' => $form->handle(),
            'title' => $form->title(),
            'fields' => $processedFields,
            'honeypot' => $form->honeypot(),
            'action' => $form->actionUrl(),
            'method' => 'POST',

            // Tag parameters
            'form_class' => $this->params->get('class', ''),
            'button_class' => $this->params->get('button_class', ''),
            'hide_fields' => $this->parseHideFields($this->params->get('hide_fields', '')),
            'prepopulated_data' => $this->params->get('prepopulated_data', []),
            'text_under_form' => $this->params->get('text_under_form', ''),
            'event_name' => $this->params->get('event_name', 'formSubmitted'),
        ];

        return view('statamic-easy-forms::form/_form_component', $data)->render();
    }

    /**
     * Parse hide_fields parameter into an array.
     *
     * @param string|array $hideFields
     * @return array
     */
    protected function parseHideFields($hideFields): array
    {
        if (is_array($hideFields)) {
            return $hideFields;
        }

        if (empty($hideFields)) {
            return [];
        }

        // Support pipe-separated list: "field1|field2|field3"
        return array_filter(explode('|', $hideFields));
    }

    /**
     * Process a field to extract needed properties and add optional flag.
     *
     * @param \Statamic\Fields\Field $field
     * @return array
     */
    protected function processField($field)
    {
        // Get config defaults from the fieldtype (includes input_type, prepend, append, etc.)
        $configDefaults = Field::commonFieldOptions()->all()
            ->merge($field->fieldtype()->configFields()->all())
            ->map->get('default')
            ->filter()
            ->all();

        // Merge config defaults with field data, then add custom data
        $fieldData = array_merge(
            $configDefaults,
            $field->toArray(),
            [
                'optional' => $this->isFieldOptional($field),
                'value' => $field->value() ?? $field->defaultValue(),
            ],
            $field->fieldtype()->extraRenderableFieldData()
        );

        // For dictionary fields, get the dictionary items with full data (value, label, code, etc.)
        // if ($field->type() === 'dictionary' && isset($fieldData['dictionary'])) {
        //     $fieldData['options'] = $this->getDictionaryOptions($fieldData['dictionary']);
        // }

        return $fieldData;
    }

    /**
     * Check if a field is optional (not required).
     *
     * @param \Statamic\Fields\Field $field
     * @return bool
     */
    protected function isFieldOptional($field)
    {
        $validate = $field->get('validate');

        if (!$validate) {
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
     * Get dictionary options.
     *
     * @param string|array $dictionary
     * @return array
     */
    protected function getDictionaryOptions($dictionary)
    {
        $dictionaryHandle = is_array($dictionary) ? $dictionary['type'] : $dictionary;
        $dictionaryInstance = Dictionary::find($dictionaryHandle);

        if (! $dictionaryInstance) {
            return [];
        }

        return collect($dictionaryInstance->optionItems())
            ->map(fn ($item) => $item->toArray())
            ->values()
            ->all();
    }
}
