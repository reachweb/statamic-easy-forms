<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Statamic\Forms\Form;

trait HandlesPages
{
    /**
     * Add the view data for a Statamic Forms Pro multi-page form ("page mode").
     *
     * Statamic processes one page per request, keyed by `_page`. It saves a partial
     * submission and answers with the `next_page` to show, which page logic can pick
     * by skipping pages. Only the last page finalizes the submission. Each page is
     * therefore rendered as a step that is submitted on its own, whatever the `wizard`
     * parameter is set to, and the page's sections render as fieldsets inside it.
     *
     * @param  Form  $form
     * @param  array  $sections  The processed sections, each with the id of its page
     */
    protected function addPageData(array $data, $form, array $sections): array
    {
        $formPages = $form->formFields()->pages()->values();
        $lastIndex = $formPages->count() - 1;

        $pages = $formPages->map(function (array $page, int $index) use ($sections, $lastIndex) {
            // Every page gets a step, even one without fields, because the server may send the visitor to it
            $pageSections = array_values(array_filter($sections, fn (array $section) => $section['page_id'] === $page['id']));

            return [
                'page_id' => $page['id'],
                'page_step' => $index + 1,
                'page_display' => $page['display'] ?? null,
                'page_instructions' => $page['instructions'] ?? null,
                'page_sections' => $pageSections,
                'page_field_handles' => array_merge([], ...array_column($pageSections, 'field_handles')),
                'button_label' => $page['button_label'] ?? null,
                // Like {{ form:create }}, the previous button is opt-in per page in the form builder
                'show_previous_button' => $index > 0 && ($page['show_previous_button'] ?? filled($page['previous_page_label'] ?? null)),
                'previous_page_label' => $page['previous_page_label'] ?? null,
                'is_first_page' => $index === 0,
                'is_last_page' => $index === $lastIndex,
            ];
        })->all();

        return array_merge($data, [
            'page_mode' => true,
            // Pages replace the section steps of wizard mode
            'wizard' => false,
            'pages' => $pages,
            'total_pages' => count($pages),
            // What pageHandler() needs to post each page and follow the server to the next one
            'pages_config' => array_map(fn (array $page) => [
                'id' => $page['page_id'],
                'fields' => $page['page_field_handles'],
                'show_previous' => $page['show_previous_button'],
                'is_last' => $page['is_last_page'],
            ], $pages),
        ]);
    }
}
