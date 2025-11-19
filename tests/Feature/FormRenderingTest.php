<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('complete form renders with all components', function () {
    createTestForm('complete', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
                'validate' => 'required',
            ],
        ],
        [
            'handle' => 'message',
            'field' => [
                'type' => 'textarea',
                'display' => 'Message',
            ],
        ],
    ]);

    $output = renderEasyFormTag('complete');

    expect($output)
        ->toBeValidHtml()
        ->toContainFormElement()
        ->toContain('name="name"')
        ->toContain('name="message"')
        ->toContain('type="submit"')
        ->toContain('x-data');
});

test('form includes CSRF token', function () {
    createTestForm('csrf_test');

    $output = renderEasyFormTag('csrf_test');

    expect($output)
        ->toContain('csrf')
        ->toContain('data-csrf-token');
});

test('form includes honeypot field', function () {
    $form = createTestForm('honeypot_test');

    $output = renderEasyFormTag('honeypot_test');

    expect($output)->toContain('honeypot');
});

test('form includes correct action URL', function () {
    createTestForm('action_test');

    $output = renderEasyFormTag('action_test');

    expect($output)->toContain('action=');
});

test('required labels display correctly', function () {
    createTestForm('required_labels', [
        [
            'handle' => 'required_field',
            'field' => [
                'type' => 'text',
                'display' => 'Required Field',
                'validate' => 'required',
            ],
        ],
    ]);

    $output = renderEasyFormTag('required_labels');

    expect($output)
        ->toContain('Required Field')
        ->not->toContain('Optional');
});

test('optional labels display correctly', function () {
    createTestForm('optional_labels', [
        [
            'handle' => 'optional_field',
            'field' => [
                'type' => 'text',
                'display' => 'Optional Field',
            ],
        ],
    ]);

    $output = renderEasyFormTag('optional_labels');

    expect($output)
        ->toContain('Optional Field')
        ->toContain('Optional');
});

test('field instructions render above by default', function () {
    createTestForm('instructions_above', [
        [
            'handle' => 'field_with_instructions',
            'field' => [
                'type' => 'text',
                'display' => 'Field',
                'instructions' => 'These are instructions',
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructions_above');

    expect($output)->toContain('These are instructions');
});

test('field instructions render below when specified', function () {
    createTestForm('instructions_below', [
        [
            'handle' => 'field',
            'field' => [
                'type' => 'text',
                'display' => 'Field',
                'instructions' => 'Below instructions',
                'instructions_position' => 'below',
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructions_below');

    expect($output)->toContain('Below instructions');
});

test('field width classes apply correctly for 50%', function () {
    createTestForm('width_50', [
        [
            'handle' => 'half_width',
            'field' => [
                'type' => 'text',
                'display' => 'Half Width',
                'width' => 50,
            ],
        ],
    ]);

    $output = renderEasyFormTag('width_50');

    expect($output)
        ->toContain('col-span-12')
        ->toContain('md:col-span-6');
});

test('field width classes apply correctly for 33%', function () {
    createTestForm('width_33', [
        [
            'handle' => 'third_width',
            'field' => [
                'type' => 'text',
                'display' => 'Third Width',
                'width' => 33,
            ],
        ],
    ]);

    $output = renderEasyFormTag('width_33');

    expect($output)
        ->toContain('col-span-12')
        ->toContain('md:col-span-4');
});

test('field width classes apply correctly for 25%', function () {
    createTestForm('width_25', [
        [
            'handle' => 'quarter_width',
            'field' => [
                'type' => 'text',
                'display' => 'Quarter Width',
                'width' => 25,
            ],
        ],
    ]);

    $output = renderEasyFormTag('width_25');

    expect($output)
        ->toContain('col-span-12')
        ->toContain('md:col-span-3');
});

test('alpine js attributes are present', function () {
    createTestForm('alpine_test', [
        [
            'handle' => 'field',
            'field' => [
                'type' => 'text',
                'display' => 'Field',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_test');

    expect($output)
        ->toContain('x-data')
        ->toContain('x-ref')
        ->toContain('x-on:submit.prevent')
        ->toContain('x-show');
});

test('success message is included', function () {
    createTestForm('success_message');

    $output = renderEasyFormTag('success_message');

    expect($output)
        ->toContain('successMessage')
        ->toContain('Thank you');
});

test('error message container is included', function () {
    createTestForm('error_container');

    $output = renderEasyFormTag('error_container');

    expect($output)
        ->toContain('errors')
        ->toContain('fatalError');
});

test('submit button has correct attributes', function () {
    createTestForm('submit_button');

    $output = renderEasyFormTag('submit_button');

    expect($output)
        ->toContain('type="submit"')
        ->toContain('x-bind:disabled')
        ->toContain('Submit');
});

test('form renders with multiple fields in grid layout', function () {
    createTestForm('grid_layout', [
        [
            'handle' => 'first_name',
            'field' => [
                'type' => 'text',
                'display' => 'First Name',
                'width' => 50,
            ],
        ],
        [
            'handle' => 'last_name',
            'field' => [
                'type' => 'text',
                'display' => 'Last Name',
                'width' => 50,
            ],
        ],
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'input_type' => 'email',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_layout');

    expect($output)
        ->toContain('grid')
        ->toContain('grid-cols-12')
        ->toContain('name="first_name"')
        ->toContain('name="last_name"')
        ->toContain('name="email"');
});

test('conditional field visibility attributes are present', function () {
    createTestForm('conditional', [
        [
            'handle' => 'show_advanced',
            'field' => [
                'type' => 'toggle',
                'display' => 'Show Advanced',
            ],
        ],
        [
            'handle' => 'advanced_field',
            'field' => [
                'type' => 'text',
                'display' => 'Advanced',
                'if' => 'show_advanced === true',
            ],
        ],
    ]);

    $output = renderEasyFormTag('conditional');

    expect($output)->toContain('shouldShowField');
});
