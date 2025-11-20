<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('integer field renders correctly', function () {
    createTestForm('integer_test', [
        [
            'handle' => 'quantity',
            'field' => [
                'type' => 'integer',
                'display' => 'Quantity',
            ],
        ],
    ]);

    $output = renderEasyFormTag('integer_test');

    expect($output)
        ->toContain('name="quantity"')
        ->toContain('type="number"');
});

test('integer field with min/max renders correctly', function () {
    createTestForm('integer_minmax', [
        [
            'handle' => 'age',
            'field' => [
                'type' => 'integer',
                'display' => 'Age',
                'min' => 18,
                'max' => 100,
            ],
        ],
    ]);

    $output = renderEasyFormTag('integer_minmax');

    expect($output)->toContain('name="age"');
});

test('integer field has Alpine model binding', function () {
    createTestForm('alpine_integer', [
        [
            'handle' => 'count',
            'field' => [
                'type' => 'integer',
                'display' => 'Count',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_integer');

    expect($output)->toContain('x-model');
});
