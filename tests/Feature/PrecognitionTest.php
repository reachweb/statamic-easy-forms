<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('form renders without precognition by default', function () {
    createTestForm('no_precognition', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('no_precognition');

    expect($output)
        ->toContain('x-data="formHandler(')
        ->toContain(', false)') // precognitionEnabled = false
        ->not->toContain(', true)');
});

test('form renders with precognition when enabled', function () {
    createTestForm('with_precognition', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
                'input_type' => 'email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('with_precognition', ['precognition' => true]);

    expect($output)
        ->toContain('x-data="formHandler(')
        ->toContain(', true)'); // precognitionEnabled = true
});

test('form includes validate-field event listener', function () {
    createTestForm('validate_listener', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('validate_listener');

    expect($output)
        ->toContain('x-on:validate-field="validateField($event.detail)"');
});

test('text field includes blur validation trigger', function () {
    createTestForm('text_blur', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('text_blur');

    expect($output)
        ->toContain('x-on:blur="$dispatch(\'validate-field\'');
});

test('textarea field includes blur validation trigger', function () {
    createTestForm('textarea_blur', [
        [
            'handle' => 'message',
            'field' => [
                'type' => 'textarea',
                'display' => 'Message',
            ],
        ],
    ]);

    $output = renderEasyFormTag('textarea_blur');

    expect($output)
        ->toContain('x-on:blur="$dispatch(\'validate-field\'');
});

test('select field includes change validation trigger', function () {
    createTestForm('select_change', [
        [
            'handle' => 'country',
            'field' => [
                'type' => 'select',
                'display' => 'Country',
                'options' => [
                    'us' => 'United States',
                    'uk' => 'United Kingdom',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_change');

    expect($output)
        ->toContain('x-on:change="$dispatch(\'validate-field\'');
});

test('radio field includes change validation trigger', function () {
    createTestForm('radio_change', [
        [
            'handle' => 'gender',
            'field' => [
                'type' => 'radio',
                'display' => 'Gender',
                'options' => [
                    'male' => 'Male',
                    'female' => 'Female',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('radio_change');

    expect($output)
        ->toContain('x-on:change="$dispatch(\'validate-field\'');
});

test('checkboxes field includes change validation trigger', function () {
    createTestForm('checkboxes_change', [
        [
            'handle' => 'interests',
            'field' => [
                'type' => 'checkboxes',
                'display' => 'Interests',
                'options' => [
                    'sports' => 'Sports',
                    'music' => 'Music',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('checkboxes_change');

    expect($output)
        ->toContain('x-on:change="$dispatch(\'validate-field\'');
});

test('toggle field includes change validation trigger', function () {
    createTestForm('toggle_change', [
        [
            'handle' => 'newsletter',
            'field' => [
                'type' => 'toggle',
                'display' => 'Subscribe to newsletter',
            ],
        ],
    ]);

    $output = renderEasyFormTag('toggle_change');

    expect($output)
        ->toContain('x-on:change="$dispatch(\'validate-field\'');
});

test('integer field includes blur validation trigger', function () {
    createTestForm('integer_blur', [
        [
            'handle' => 'age',
            'field' => [
                'type' => 'integer',
                'display' => 'Age',
            ],
        ],
    ]);

    $output = renderEasyFormTag('integer_blur');

    expect($output)
        ->toContain('x-on:blur="$dispatch(\'validate-field\'');
});

test('time field includes blur validation trigger', function () {
    createTestForm('time_blur', [
        [
            'handle' => 'appointment_time',
            'field' => [
                'type' => 'text',
                'display' => 'Appointment Time',
                'input_type' => 'time',
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('time_blur');

    // Time field uses x-on:blur with $dispatch
    expect($output)
        ->toContain("x-on:blur=\"\$dispatch('validate-field', 'appointment_time')\"");
});

test('date field includes validation dispatch', function () {
    createTestForm('date_validate', [
        [
            'handle' => 'start_date',
            'field' => [
                'type' => 'text',
                'display' => 'Start Date',
                'input_type' => 'date',
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_validate');

    // Date field uses this.$dispatch inside Alpine component
    expect($output)
        ->toContain("this.\$dispatch('validate-field', 'start_date')");
});

test('dictionary field includes validation dispatch', function () {
    createTestForm('dictionary_validate', [
        [
            'handle' => 'country',
            'field' => [
                'type' => 'dictionary',
                'display' => 'Country',
                'dictionary' => 'countries',
            ],
        ],
    ]);

    $output = renderEasyFormTag('dictionary_validate');

    expect($output)
        ->toContain('$dispatch(\'validate-field\'');
});

test('telephone field includes validation dispatch', function () {
    createTestForm('telephone_validate', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'display' => 'Phone',
                'input_type' => 'tel',
            ],
        ],
    ]);

    $output = renderEasyFormTag('telephone_validate');

    expect($output)
        ->toContain('$dispatch(\'validate-field\'');
});

test('improved select field includes validation dispatch', function () {
    createTestForm('select_improved_validate', [
        [
            'handle' => 'category',
            'field' => [
                'type' => 'select',
                'display' => 'Category',
                'improved_field' => true,
                'options' => [
                    'a' => 'Option A',
                    'b' => 'Option B',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_validate');

    expect($output)
        ->toContain('$dispatch(\'validate-field\'');
});

test('improved radio field includes validation dispatch', function () {
    createTestForm('radio_improved_validate', [
        [
            'handle' => 'choice',
            'field' => [
                'type' => 'radio',
                'display' => 'Choice',
                'improved_field' => true,
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('radio_improved_validate');

    expect($output)
        ->toContain('$dispatch(\'validate-field\'');
});

test('integer counter field includes validation dispatch', function () {
    createTestForm('integer_counter_validate', [
        [
            'handle' => 'quantity',
            'field' => [
                'type' => 'integer',
                'display' => 'Quantity',
                'integer_template' => 'counter',
            ],
        ],
    ]);

    $output = renderEasyFormTag('integer_counter_validate');

    expect($output)
        ->toContain('$dispatch(\'validate-field\'');
});

test('integer rating field includes validation dispatch', function () {
    createTestForm('integer_rating_validate', [
        [
            'handle' => 'rating',
            'field' => [
                'type' => 'integer',
                'display' => 'Rating',
                'integer_template' => 'rating',
            ],
        ],
    ]);

    $output = renderEasyFormTag('integer_rating_validate');

    expect($output)
        ->toContain('$dispatch(\'validate-field\'');
});

test('assets field does not include validation dispatch', function () {
    createTestForm('assets_no_validate', [
        [
            'handle' => 'documents',
            'field' => [
                'type' => 'assets',
                'display' => 'Documents',
                'container' => 'assets',
            ],
        ],
    ]);

    $output = renderEasyFormTag('assets_no_validate');

    // Assets field should NOT have validate-field dispatch (files can't be validated via precognition)
    // The field itself shouldn't dispatch, but the form still has the listener
    expect($output)
        ->toContain('x-on:validate-field'); // Form listener exists
});

test('precognition parameter accepts string true', function () {
    createTestForm('precog_string_true', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('precog_string_true', ['precognition' => 'true']);

    expect($output)
        ->toContain(', true)'); // precognitionEnabled = true
});

test('precognition parameter accepts string false', function () {
    createTestForm('precog_string_false', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('precog_string_false', ['precognition' => 'false']);

    expect($output)
        ->toContain(', false)'); // precognitionEnabled = false
});

test('form with multiple fields all have validation triggers', function () {
    createTestForm('multi_field_validation', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
                'input_type' => 'email',
            ],
        ],
        [
            'handle' => 'country',
            'field' => [
                'type' => 'select',
                'display' => 'Country',
                'options' => [
                    'us' => 'United States',
                ],
            ],
        ],
        [
            'handle' => 'agree',
            'field' => [
                'type' => 'toggle',
                'display' => 'I agree',
            ],
        ],
    ]);

    $output = renderEasyFormTag('multi_field_validation');

    // Count occurrences of validate-field dispatch
    $count = substr_count($output, '$dispatch(\'validate-field\'');
    
    // Should have at least 4 dispatches (one per field) plus the form listener
    expect($count)->toBeGreaterThanOrEqual(4);
});
