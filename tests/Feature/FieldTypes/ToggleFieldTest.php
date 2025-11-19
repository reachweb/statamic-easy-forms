<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('toggle field renders correctly', function () {
    createTestForm('toggle_test', [
        [
            'handle' => 'newsletter',
            'field' => [
                'type' => 'toggle',
                'display' => 'Subscribe to Newsletter',
            ],
        ],
    ]);

    $output = renderEasyFormTag('toggle_test');

    expect($output)
        ->toContain('name="newsletter"')
        ->toContain('type="checkbox"');
});

test('toggle field with inline label renders correctly', function () {
    createTestForm('toggle_inline', [
        [
            'handle' => 'agree',
            'field' => [
                'type' => 'toggle',
                'display' => 'Agree to Terms',
                'inline_label' => 'I agree to the terms and conditions',
            ],
        ],
    ]);

    $output = renderEasyFormTag('toggle_inline');

    expect($output)->toContain('I agree to the terms and conditions');
});

test('toggle field has Alpine model binding', function () {
    createTestForm('alpine_toggle', [
        [
            'handle' => 'enabled',
            'field' => [
                'type' => 'toggle',
                'display' => 'Enabled',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_toggle');

    expect($output)->toContain('x-model');
});
