<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Statamic\Facades\Form;

trait HandlesForms
{
    /**
     * Get the form instance from the handle parameter.
     *
     * @return \Statamic\Forms\Form
     *
     * @throws \Exception
     */
    protected function getForm()
    {
        $handle = $this->params->get('handle');

        if (! $handle) {
            throw new \Exception('A form handle is required for the easyform tag.');
        }

        $form = Form::find($handle);

        if (! $form) {
            throw new \Exception("Form with handle [$handle] cannot be found.");
        }

        return $form;
    }

    /**
     * Extract sections from blueprint contents.
     *
     * @param  \Statamic\Fields\Blueprint  $blueprint
     * @return array ['has_sections' => bool, 'sections' => array]
     */
    protected function extractSections(array $contents, $blueprint): array
    {
        $hasSections = false;
        $sections = [];

        if (! isset($contents['tabs'])) {
            return compact('hasSections', 'sections');
        }

        foreach ($contents['tabs'] as $tab) {
            if (! isset($tab['sections']) || ! is_array($tab['sections'])) {
                continue;
            }

            foreach ($tab['sections'] as $section) {
                if (! isset($section['fields']) || ! is_array($section['fields'])) {
                    continue;
                }

                $hasSections = true;
                $processedSectionFields = [];

                foreach ($section['fields'] as $fieldConfig) {
                    $field = $blueprint->field($fieldConfig['handle']);
                    if ($field) {
                        $processedSectionFields[] = $this->processField($field);
                    }
                }

                $sections[] = [
                    'display' => $section['display'] ?? null,
                    'instructions' => $section['instructions'] ?? null,
                    'fields' => $processedSectionFields,
                    'field_handles' => array_column($processedSectionFields, 'handle'),
                ];
            }
        }

        // Add step metadata for wizard mode
        $totalSteps = count($sections);
        foreach ($sections as $index => &$section) {
            $section['step'] = $index + 1;
            $section['total_steps'] = $totalSteps;
            $section['is_first'] = $index === 0;
            $section['is_last'] = $index === $totalSteps - 1;
        }
        unset($section);

        return compact('hasSections', 'sections');
    }

    /**
     * Prepare view data for rendering.
     *
     * @param  \Statamic\Forms\Form  $form
     */
    protected function prepareViewData($form, array $processedFields, array $sections, bool $hasSections): array
    {
        return [
            'handle' => $form->handle(),
            'form_id' => $this->buildFormId($form->handle()),
            'title' => $form->title(),
            'fields' => $processedFields,
            'sections' => $sections,
            'has_sections' => $hasSections,
            'honeypot' => $form->honeypot(),
            'action' => $form->actionUrl(),
            'method' => 'POST',
            'recaptcha_site_key' => config('easy-forms.recaptcha.site_key'),

            // Tag parameters
            'hide_fields' => $this->parseHideFields($this->params->get('hide_fields', '')),
            'prepopulated_data' => $this->params->get('prepopulated_data', []),
            'submit_text' => $this->params->get('submit_text'),
            'success_message' => $this->params->get('success_message'),
            'precognition' => $this->params->bool('precognition', false),
            'wizard' => $this->params->bool('wizard', false),
        ];
    }

    /**
     * Build the form ID used for HTML element IDs.
     */
    protected function buildFormId(string $handle): string
    {
        $instance = $this->params->get('instance');

        return $instance ? "{$handle}_{$instance}" : $handle;
    }

    /**
     * Parse hide_fields parameter into an array.
     *
     * @param  string|array  $hideFields
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
     * Process all sections from the blueprint.
     *
     * @param  \Statamic\Fields\Blueprint  $blueprint
     */
    protected function processSections($blueprint): array
    {
        $contents = $blueprint->contents();

        return $this->extractSections($contents, $blueprint);
    }

    /**
     * Render the view with the given data.
     */
    protected function renderView(array $data): string
    {
        $view = $this->params->get('view', 'form/_form_component');

        // Prepend form/ if not there
        if (! str_starts_with($view, 'form/')) {
            $view = 'form/'.$view;
        }

        // Add underscore prefix to filename if not already prefixed
        $parts = explode('/', $view);
        $filename = array_pop($parts);
        if (! str_starts_with($filename, '_')) {
            $filename = '_'.$filename;
        }
        $parts[] = $filename;
        $view = implode('/', $parts);

        return view('statamic-easy-forms::'.$view, $data)
            ->withoutExtractions()
            ->render();
    }
}
