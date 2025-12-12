<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('radio field renders correctly', function () {
    createTestForm('radio_test', [
        [
            'handle' => 'gender',
            'field' => [
                'type' => 'radio',
                'display' => 'Gender',
                'options' => [
                    'male' => 'Male',
                    'female' => 'Female',
                    'other' => 'Other',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('radio_test');

    expect($output)
        ->toContain('name="gender"')
        ->toContain('type="radio"');
});

test('radio buttons include all options', function () {
    createTestForm('radio_options', [
        [
            'handle' => 'size',
            'field' => [
                'type' => 'radio',
                'display' => 'Size',
                'options' => [
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('radio_options');

    expect($output)
        ->toContain('Small')
        ->toContain('Medium')
        ->toContain('Large');
});

test('radio field has Alpine model binding', function () {
    createTestForm('alpine_radio', [
        [
            'handle' => 'choice',
            'field' => [
                'type' => 'radio',
                'display' => 'Choice',
                'options' => ['yes' => 'Yes', 'no' => 'No'],
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_radio');

    expect($output)->toContain('x-model');
});

test('radio field with default value renders correctly', function () {
    createTestForm('radio_default', [
        [
            'handle' => 'contact_method',
            'field' => [
                'type' => 'radio',
                'display' => 'Contact Method',
                'options' => [
                    'email' => 'Email',
                    'phone' => 'Phone',
                ],
                'default' => 'email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('radio_default');

    expect($output)->toContain('name="contact_method"');
});

test('standard radio field renders with fieldset and visible inputs', function () {
    createTestForm('standard_radio', [
        [
            'handle' => 'preference',
            'field' => [
                'type' => 'radio',
                'display' => 'Preference',
                'options' => [
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                    'option3' => 'Option 3',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('standard_radio');

    expect($output)
        ->toContain('<fieldset')
        ->toContain('type="radio"')
        ->toContain('form-radio')
        ->toContain('Option 1')
        ->toContain('Option 2')
        ->toContain('Option 3');
});

test('standard radio field uses grid layout', function () {
    createTestForm('grid_radio', [
        [
            'handle' => 'layout_test',
            'field' => [
                'type' => 'radio',
                'display' => 'Layout Test',
                'options' => ['a' => 'A', 'b' => 'B'],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_radio');

    expect($output)
        ->toContain('grid')
        ->toContain('lg:grid-cols-2');
});

test('improved radio field renders with button-style labels when improved_field is true', function () {
    createTestForm('improved_radio', [
        [
            'handle' => 'styled_choice',
            'field' => [
                'type' => 'radio',
                'display' => 'Styled Choice',
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No',
                ],
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('improved_radio');

    expect($output)
        ->toContain('sr-only')
        ->toContain('has-checked')
        ->toContain('<fieldset')
        ->toContain('<legend class="sr-only">')
        ->toContain('flex-col')
        ->toContain('md:flex-row');
});

test('improved radio field has enhanced Alpine.js data binding', function () {
    createTestForm('improved_alpine', [
        [
            'handle' => 'enhanced',
            'field' => [
                'type' => 'radio',
                'display' => 'Enhanced',
                'options' => ['a' => 'A', 'b' => 'B'],
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('improved_alpine');

    expect($output)
        ->toContain('x-data')
        ->toContain('x-model')
        ->toContain('selected');
});

test('improved radio field initializes with default value', function () {
    createTestForm('improved_default', [
        [
            'handle' => 'with_default',
            'field' => [
                'type' => 'radio',
                'display' => 'With Default',
                'options' => [
                    'red' => 'Red',
                    'green' => 'Green',
                    'blue' => 'Blue',
                ],
                'default' => 'green',
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('improved_default');

    expect($output)
        ->toContain('selected:')
        ->toContain("'green'")
        ->toContain('checked');
});

test('improved radio field has accessibility attributes', function () {
    createTestForm('improved_a11y', [
        [
            'handle' => 'accessible',
            'field' => [
                'type' => 'radio',
                'display' => 'Accessible',
                'options' => ['x' => 'X', 'y' => 'Y'],
                'improved_field' => true,
                'instructions' => 'Select an option',
            ],
        ],
    ]);

    $output = renderEasyFormTag('improved_a11y');

    expect($output)
        ->toContain('<fieldset')
        ->toContain('<legend class="sr-only">')
        ->toContain('aria-describedby="improved_a11y_accessible-description"');
});

test('improved radio field has responsive flex layout', function () {
    createTestForm('improved_layout', [
        [
            'handle' => 'responsive',
            'field' => [
                'type' => 'radio',
                'display' => 'Responsive',
                'options' => ['one' => 'One', 'two' => 'Two'],
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('improved_layout');

    expect($output)
        ->toContain('flex-col')
        ->toContain('md:flex-row')
        ->toContain('md:flex-1');
});
