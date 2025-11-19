<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('select field renders correctly', function () {
    createTestForm('select_test', [
        [
            'handle' => 'country',
            'field' => [
                'type' => 'select',
                'display' => 'Country',
                'options' => [
                    'us' => 'United States',
                    'ca' => 'Canada',
                    'uk' => 'United Kingdom',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_test');

    expect($output)
        ->toContain('name="country"')
        ->toContain('<select');
});

test('select field includes all options', function () {
    createTestForm('options_test', [
        [
            'handle' => 'color',
            'field' => [
                'type' => 'select',
                'display' => 'Color',
                'options' => [
                    'red' => 'Red',
                    'blue' => 'Blue',
                    'green' => 'Green',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('options_test');

    expect($output)
        ->toContain('Red')
        ->toContain('Blue')
        ->toContain('Green');
});

test('select field with default option renders correctly', function () {
    createTestForm('default_option', [
        [
            'handle' => 'size',
            'field' => [
                'type' => 'select',
                'display' => 'Size',
                'options' => [
                    's' => 'Small',
                    'm' => 'Medium',
                    'l' => 'Large',
                ],
                'default' => 'm',
            ],
        ],
    ]);

    $output = renderEasyFormTag('default_option');

    expect($output)->toContain('name="size"');
});

test('select field has Alpine model binding', function () {
    createTestForm('alpine_select', [
        [
            'handle' => 'field',
            'field' => [
                'type' => 'select',
                'display' => 'Field',
                'options' => ['a' => 'A', 'b' => 'B'],
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_select');

    expect($output)->toContain('x-model');
});
