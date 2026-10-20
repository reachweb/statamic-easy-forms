<?php

use Illuminate\Validation\ValidationException;
use Reach\StatamicEasyForms\Listeners\ValidateRecaptcha;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Form;

/*
|--------------------------------------------------------------------------
| Statamic Forms 2
|--------------------------------------------------------------------------
|
| Forms 2 stores fields on the form itself using form fieldtypes (short_answer,
| upload, date_picker, ...). The form blueprint is generated from them, so the
| tag sees the classic fieldtype each one wraps (form_upload, date, yes_no, ...).
|
*/

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

/**
 * Create a form that stores its fields in the Forms 2 format.
 */
function createForms2Form(string $handle, array $sections, array $config = []): Statamic\Forms\Form
{
    $form = Form::make($handle)->title('Forms 2 Form')->honeypot('honeypot');

    foreach ($config as $key => $value) {
        $form->set($key, $value);
    }

    $form->formFields(['sections' => $sections])->save();

    return $form;
}

test('stored upload fields render the drag and drop upload', function () {
    createForms2Form('upload_form', [
        ['fields' => [
            ['handle' => 'photos', 'field' => ['type' => 'upload', 'display' => 'Photos', 'store' => true, 'max_files' => 3]],
        ]],
    ]);

    $output = renderEasyFormTag('upload_form');

    expect($output)
        ->toContain('id="upload_form_photos"')
        ->toContain('type="file"')
        ->toContain('or drag and drop here')
        ->toContain('multiple');
});

test('upload fields that are not stored render the simple file input', function () {
    createForms2Form('attachment_form', [
        ['fields' => [
            ['handle' => 'attachment', 'field' => ['type' => 'upload', 'display' => 'Attachment', 'store' => false]],
        ]],
    ]);

    $output = renderEasyFormTag('attachment_form');

    expect($output)
        ->toContain('id="attachment_form_attachment"')
        ->toContain('type="file"')
        ->toContain('handleFileChange')
        ->not->toContain('or drag and drop here');
});

test('date picker fields render the easy forms datepicker', function () {
    createForms2Form('date_form', [
        ['fields' => [
            ['handle' => 'arrival', 'field' => ['type' => 'date_picker', 'display' => 'Arrival']],
        ]],
    ]);

    $output = renderEasyFormTag('date_form');

    expect($output)
        ->toContain('id="date_form_arrival"')
        ->toContain('aria-label="Choose date"');
});

test('time picker fields render the masked time input', function () {
    createForms2Form('time_form', [
        ['fields' => [
            ['handle' => 'arrival_time', 'field' => ['type' => 'time_picker', 'display' => 'Arrival time']],
        ]],
    ]);

    $output = renderEasyFormTag('time_form');

    expect($output)
        ->toContain('id="time_form_arrival_time"')
        ->toContain('x-mask="99:99"');
});

test('yes no fields render as a radio group with yes and no options', function () {
    createForms2Form('yes_no_form', [
        ['fields' => [
            ['handle' => 'subscribe', 'field' => ['type' => 'yes_no', 'display' => 'Subscribe?']],
        ]],
    ]);

    $output = renderEasyFormTag('yes_no_form');

    expect($output)
        ->toContain('type="radio"')
        ->toContain('value="yes"')
        ->toContain('value="no"')
        ->toContain('<legend class="sr-only">Subscribe?</legend>')
        // Radio groups are labelled by their legend, not a label pointing at a single input
        ->not->toContain('for="yes_no_form_subscribe"');
});

test('display only fields render their content without a label', function () {
    createForms2Form('display_form', [
        ['fields' => [
            ['handle' => 'intro_heading', 'field' => ['type' => 'heading', 'display' => 'About you', 'subheading' => 'Tell us a little about yourself']],
            ['handle' => 'intro_paragraph', 'field' => ['type' => 'paragraph', 'display' => 'Internal paragraph label', 'text' => 'All fields are confidential.']],
            ['handle' => 'notice', 'field' => ['type' => 'banner', 'display' => 'Internal banner label', 'content' => 'Read the **terms** first.', 'state' => 'warning']],
        ]],
    ]);

    $output = renderEasyFormTag('display_form');

    expect($output)
        ->toContain('About you</h3>')
        ->toContain('Tell us a little about yourself')
        ->toContain('All fields are confidential.')
        ->toContain('<strong>terms</strong>')
        ->toContain('data-state="warning"')
        // The internal labels are only part of the field config handed to Alpine
        ->not->toMatch('/>\s*Internal (paragraph|banner) label\s*</')
        ->not->toContain('(Optional)');
});

test('sections that import a fieldset render the imported fields', function () {
    Fieldset::make('contact_details')->setContents([
        'fields' => [
            ['handle' => 'email', 'field' => ['type' => 'text', 'input_type' => 'email', 'display' => 'Email']],
            ['handle' => 'phone', 'field' => ['type' => 'text', 'input_type' => 'tel', 'display' => 'Phone']],
        ],
    ])->save();

    createForms2Form('fieldset_form', [
        ['display' => 'You', 'fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
        ['display' => 'Contact', 'fields' => [
            ['import' => 'contact_details'],
        ]],
    ]);

    $output = renderEasyFormTag('fieldset_form', ['wizard' => true]);

    expect($output)
        ->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('name="phone"')
        ->toContain('&quot;email&quot;,&quot;phone&quot;');

    Fieldset::find('contact_details')->delete();
});

test('sections whose fields are all hidden by logic are not rendered as wizard steps', function () {
    createForms2Form('hidden_section_form', [
        ['display' => 'Visible step', 'fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
        ['display' => 'Hidden step', 'fields' => [
            ['handle' => 'internal', 'field' => ['type' => 'short_answer', 'display' => 'Internal', 'hidden' => true]],
        ]],
        ['display' => 'Last step', 'fields' => [
            ['handle' => 'email', 'field' => ['type' => 'email', 'display' => 'Email']],
        ]],
    ]);

    $output = renderEasyFormTag('hidden_section_form', ['wizard' => true]);

    expect($output)
        ->toContain('Visible step')
        ->toContain('Last step')
        ->not->toContain('Hidden step')
        ->not->toContain('name="internal"')
        ->toContain('...wizardHandler(2)');
});

test('closed forms render the restriction message instead of the form', function () {
    createForms2Form('closed_form', [
        ['fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
    ], ['close_date' => now()->subDay()->toDateString(), 'closed_message' => 'Applications are closed.']);

    $output = renderEasyFormTag('closed_form');

    expect($output)
        ->toContain('Applications are closed.')
        ->toContain('data-form-status="closed"')
        ->not->toContain('<form');
});

test('forms requiring login render the restriction message for guests', function () {
    createForms2Form('members_form', [
        ['fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
    ], ['require_login' => true]);

    $output = renderEasyFormTag('members_form');

    expect($output)
        ->toContain('id="members_form_restricted"')
        ->not->toContain('<form');
});

test('open forms render normally', function () {
    createForms2Form('open_form', [
        ['fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
    ], ['close_date' => now()->addWeek()->toDateString()]);

    expect(renderEasyFormTag('open_form'))
        ->toContainFormElement()
        ->not->toContain('_restricted"');
});

test('recaptcha listener skips fake submissions generated in the control panel', function () {
    config(['easy-forms.recaptcha.secret_key' => 'test_secret_key']);

    $form = createForms2Form('fake_form', [
        ['fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
    ]);

    $listener = new class extends ValidateRecaptcha
    {
        protected function isRecaptchaLibraryInstalled(): bool
        {
            return true;
        }
    };

    $fake = $form->makeSubmission()->data(['name' => 'Fake', '_fake' => true]);
    $real = $form->makeSubmission()->data(['name' => 'Real']);

    expect(fn () => $listener->handle(new FormSubmitted($fake)))->not->toThrow(ValidationException::class)
        ->and(fn () => $listener->handle(new FormSubmitted($real)))->toThrow(ValidationException::class);

    config(['easy-forms.recaptcha.secret_key' => null]);
});
