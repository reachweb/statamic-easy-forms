<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('text field renders correctly', function () {
    createTestForm('text_test', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('text_test');

    expect($output)
        ->toContain('name="name"')
        ->toContain('type="text"');
});

test('email input type renders correctly', function () {
    createTestForm('email_test', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'input_type' => 'email',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('email_test');

    expect($output)
        ->toContain('name="email"')
        ->toContain('type="email"');
});

test('text field with placeholder renders correctly', function () {
    createTestForm('placeholder_test', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
                'placeholder' => 'Enter your name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('placeholder_test');

    expect($output)->toContain('Enter your name');
});

test('text field with default value renders correctly', function () {
    createTestForm('default_test', [
        [
            'handle' => 'country',
            'field' => [
                'type' => 'text',
                'display' => 'Country',
                'default' => 'USA',
            ],
        ],
    ]);

    $output = renderEasyFormTag('default_test');

    expect($output)->toContain('name="country"');
});

test('text field has Alpine model binding', function () {
    createTestForm('alpine_binding', [
        [
            'handle' => 'field',
            'field' => [
                'type' => 'text',
                'display' => 'Field',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_binding');

    expect($output)->toContain('x-model');
});
