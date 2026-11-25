<?php

namespace Reach\StatamicEasyForms\Modifiers;

use Statamic\Facades\Form;
use Statamic\Modifiers\Modifier;

/**
 * Whether the form with the given handle stores its submissions, which the email template needs
 * to link to a submission: a form's augmented data doesn't include its "store" setting.
 *
 * {{ form:handle | easy_forms_stores_submissions }}
 */
class StoresSubmissions extends Modifier
{
    protected static $handle = 'easy_forms_stores_submissions';

    public function index($value)
    {
        return (bool) Form::find((string) $value)?->store();
    }
}
