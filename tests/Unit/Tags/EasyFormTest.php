<?php

use Reach\StatamicEasyForms\Tags\EasyForm;
use Statamic\Facades\Form;

beforeEach(function () {
    // Clean up any existing forms
    Form::all()->each->delete();
});

afterEach(function () {
    // Clean up after tests
    Form::all()->each->delete();
});

test('tag renders form successfully with valid handle', function () {
    $form = createTestForm('contact', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
                'validate' => 'required',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact');

    expect($output)
        ->toBeString()
        ->not->toBeEmpty()
        ->toContainFormElement();
});

test('tag throws exception with missing handle', function () {
    $tag = new EasyForm;
    $tag->setContext([]);
    $tag->setParameters([]);

    $tag->index();
})->throws(Exception::class, 'A form handle is required');

test('tag throws exception with invalid handle', function () {
    renderEasyFormTag('nonexistent_form');
})->throws(Exception::class, 'cannot be found');

test('tag includes all form data', function () {
    createTestForm('test_form', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'input_type' => 'email',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('test_form');

    expect($output)
        ->toContain('name="email"')
        ->toContain('type="email"')
        ->toContain('honeypot');
});

test('tag respects custom view parameter', function () {
    createTestForm('custom_view_form');

    // The view parameter allows using a different template
    $output = renderEasyFormTag('custom_view_form', [
        'view' => 'form/_form_component',
    ]);

    expect($output)->toContainFormElement();
});

test('tag respects hide_fields parameter', function () {
    createTestForm('hidden_fields_form', [
        [
            'handle' => 'visible_field',
            'field' => [
                'type' => 'text',
                'display' => 'Visible',
            ],
        ],
        [
            'handle' => 'hidden_field',
            'field' => [
                'type' => 'text',
                'display' => 'Hidden',
            ],
        ],
    ]);

    $output = renderEasyFormTag('hidden_fields_form', [
        'hide_fields' => 'hidden_field',
    ]);

    // The visible field should be present without the hidden class
    expect($output)->toContain('visible_field');

    // The hidden field should still be in the output but with x-show Alpine directive
    // (it will be hidden by Alpine.js on the client side)
    expect($output)
        ->toContain('name="hidden_field"')
        ->toContain('x-show="shouldShowField(\'hidden_field\')"');
});

test('tag renders with all field types', function () {
    createTestForm('all_fields', [
        [
            'handle' => 'text_field',
            'field' => ['type' => 'text', 'display' => 'Text'],
        ],
        [
            'handle' => 'textarea_field',
            'field' => ['type' => 'textarea', 'display' => 'Textarea'],
        ],
        [
            'handle' => 'select_field',
            'field' => [
                'type' => 'select',
                'display' => 'Select',
                'options' => ['option1' => 'Option 1', 'option2' => 'Option 2'],
            ],
        ],
    ]);

    $output = renderEasyFormTag('all_fields');

    expect($output)
        ->toContain('name="text_field"')
        ->toContain('name="textarea_field"')
        ->toContain('name="select_field"');
});

test('tag handles hidden fields properly', function () {
    createTestForm('with_hidden', [
        [
            'handle' => 'visible',
            'field' => ['type' => 'text', 'display' => 'Visible'],
        ],
        [
            'handle' => 'secret',
            'field' => ['type' => 'hidden', 'display' => 'Secret'],
        ],
    ]);

    $output = renderEasyFormTag('with_hidden');

    // Hidden fields should still be in the form but marked as hidden
    expect($output)->toBeString();
});

test('parseHideFields handles pipe-separated string', function () {
    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('parseHideFields');
    $method->setAccessible(true);

    $result = $method->invoke($tag, 'field1|field2|field3');

    expect($result)->toBe(['field1', 'field2', 'field3']);
});

test('parseHideFields handles array input', function () {
    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('parseHideFields');
    $method->setAccessible(true);

    $result = $method->invoke($tag, ['field1', 'field2']);

    expect($result)->toBe(['field1', 'field2']);
});

test('parseHideFields handles empty string', function () {
    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('parseHideFields');
    $method->setAccessible(true);

    $result = $method->invoke($tag, '');

    expect($result)->toBe([]);
});

test('tag uses form handle as formHandler identifier', function () {
    createTestForm('contact_form');

    $output = renderEasyFormTag('contact_form');

    // The form handle should be used as the formHandler identifier
    expect($output)->toContain("formHandler('contact_form')");
});

test('tag displays prepend value in field label', function () {
    createTestForm('prepend_form', [
        [
            'handle' => 'text_field',
            'field' => [
                'type' => 'text',
                'display' => 'Text Field',
                'prepend' => 'Prefix:',
            ],
        ],
    ]);

    $output = renderEasyFormTag('prepend_form');

    expect($output)
        ->toContain('Prefix:')
        ->toContain('Text Field');
});

test('tag displays append value in field label', function () {
    createTestForm('append_form', [
        [
            'handle' => 'text_field',
            'field' => [
                'type' => 'text',
                'display' => 'Text Field',
                'append' => '(required)',
            ],
        ],
    ]);

    $output = renderEasyFormTag('append_form');

    expect($output)
        ->toContain('Text Field')
        ->toContain('(required)');
});

test('tag displays both prepend and append values in field label', function () {
    createTestForm('prepend_append_form', [
        [
            'handle' => 'amount_field',
            'field' => [
                'type' => 'text',
                'display' => 'Amount',
                'prepend' => '$',
                'append' => 'USD',
            ],
        ],
    ]);

    $output = renderEasyFormTag('prepend_append_form');

    expect($output)
        ->toContain('$')
        ->toContain('Amount')
        ->toContain('USD');
});
