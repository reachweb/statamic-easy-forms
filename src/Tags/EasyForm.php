<?php

namespace Reach\StatamicEasyForms\Tags;

use Reach\StatamicEasyForms\Tags\Concerns\HandlesDictionaries;
use Reach\StatamicEasyForms\Tags\Concerns\HandlesFields;
use Reach\StatamicEasyForms\Tags\Concerns\HandlesForms;
use Reach\StatamicEasyForms\Tags\Concerns\HasVersion;
use Statamic\Tags\Tags;

class EasyForm extends Tags
{
    use HandlesDictionaries;
    use HandlesFields;
    use HandlesForms;
    use HasVersion;

    protected static $handle = 'easyform';

    /**
     * The {{ easyform }} tag.
     *
     * Renders a complete form with all fields automatically.
     *
     * Usage: {{ easyform handle="contact" }}
     *
     * Available parameters:
     * - handle (required): The form handle (also used as the formHandler identifier for events)
     * - view: Custom view template to use (default: "form/_form_component")
     * - hide_fields: Array of field handles to hide (e.g., hide_fields="field1|field2")
     * - prepopulated_data: Array of field values to prepopulate
     * - submit_text: Custom text for the submit button
     * - success_message: Custom text for the success message after submission
     * - precognition: Enable Laravel Precognition for real-time validation (default: false)
     *
     * Example with custom view:
     * {{ easyform handle="contact" view="forms/custom-contact" }}
     *
     * Example with custom text:
     * {{ easyform handle="contact" submit_text="Send Message" success_message="Thanks! We'll be in touch soon." }}
     *
     * Example with precognition:
     * {{ easyform handle="contact" precognition="true" }}
     *
     * @return string Rendered HTML
     */
    public function index(): string
    {
        $form = $this->getForm();
        $blueprint = $form->blueprint();

        $sectionsData = $this->processSections($blueprint);
        $fields = $this->processAllFields($blueprint);

        $data = $this->prepareViewData(
            $form,
            $fields,
            $sectionsData['sections'],
            $sectionsData['hasSections']
        );

        return $this->renderView($data);
    }
}
