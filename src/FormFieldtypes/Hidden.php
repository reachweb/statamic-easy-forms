<?php

namespace Reach\StatamicEasyForms\FormFieldtypes;

use Statamic\Forms\Fields\FormFieldtype;
use Statamic\Support\Arr;

class Hidden extends FormFieldtype
{
    protected static $fieldtype = 'hidden';

    protected $description = 'A value that is submitted with the form without being shown, e.g. a campaign or tracking ID.';

    protected $icon = 'fieldtype-hidden';

    protected $categories = ['text'];

    protected $keywords = ['tracking', 'utm', 'gclid'];

    protected function configFieldItems(): array
    {
        return [
            'default' => [
                'display' => 'Default value',
                'instructions' => 'The value submitted with the form. With the handle `tracking_id`, the field is filled with the ad click IDs (gclid, gbraid, wbraid) from the URL or a cookie, and this value is the fallback.',
                'type' => 'text',
            ],
        ];
    }

    public function toFieldArray(): array
    {
        // The input_type makes Easy Forms treat it like its other hidden inputs (e.g. the tracking_id auto-fill)
        return [
            'type' => 'hidden',
            'input_type' => 'hidden',
            ...Arr::except($this->config(), ['type', 'input_type']),
        ];
    }
}
