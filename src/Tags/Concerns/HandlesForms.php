<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Statamic\Facades\Form;
use Statamic\Fields\Blueprint;

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
     * Extract sections from the blueprint's tabs.
     *
     * Iterates the resolved tab/section/field objects rather than the raw contents
     * so that fieldset imports (offered by the Forms 2 builder) are expanded.
     *
     * @param  Blueprint  $blueprint
     * @return array ['has_sections' => bool, 'sections' => array]
     */
    protected function extractSections($blueprint): array
    {
        $hasSections = false;
        $sections = [];

        foreach ($blueprint->tabs() as $tab) {
            foreach ($tab->sections() as $section) {
                // Skip sections without fields, e.g. when every field in them is
                // set to "Always hide" in the Forms 2 logic and removed from the blueprint.
                if (empty($section->contents()['fields'] ?? null)) {
                    continue;
                }

                $hasSections = true;
                $processedSectionFields = [];

                foreach ($section->fields()->all() as $handle => $sectionField) {
                    $field = $blueprint->field($handle) ?? $sectionField;
                    $processedSectionFields[] = $this->processField($field);
                }

                $sections[] = [
                    'display' => $section->display(),
                    'instructions' => $section->instructions(),
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
     * @param  Blueprint  $blueprint
     */
    protected function processSections($blueprint): array
    {
        return $this->extractSections($blueprint);
    }

    /**
     * Get the form's access restriction (closed, submission limit reached, login required).
     *
     * Restrictions were introduced with Forms 2, so older Statamic versions never restrict.
     *
     * @param  \Statamic\Forms\Form  $form
     * @return array|null ['message' => string, 'status' => string]
     */
    protected function getRestriction($form): ?array
    {
        if (! method_exists($form, 'restricted') || ! $form->restricted()) {
            return null;
        }

        return [
            'message' => $form->restrictionMessage(),
            'status' => $form->status(),
        ];
    }

    /**
     * Render the restriction message in place of the form.
     *
     * @param  \Statamic\Forms\Form  $form
     */
    protected function renderRestricted($form, array $restriction): string
    {
        return view('statamic-easy-forms::form/_restricted', [
            'handle' => $form->handle(),
            'form_id' => $this->buildFormId($form->handle()),
            'title' => $form->title(),
            'restriction_message' => $restriction['message'],
            'status' => $restriction['status'],
        ])->withoutExtractions()->render();
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
