<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('checkboxes field renders correctly', function () {
    createTestForm('checkboxes_test', [
        [
            'handle' => 'interests',
            'field' => [
                'type' => 'checkboxes',
                'display' => 'Interests',
                'options' => [
                    'sports' => 'Sports',
                    'music' => 'Music',
                    'reading' => 'Reading',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('checkboxes_test');

    expect($output)
        ->toContain('name="interests[]"')
        ->toContain('type="checkbox"');
});

test('checkboxes include all options', function () {
    createTestForm('checkbox_options', [
        [
            'handle' => 'hobbies',
            'field' => [
                'type' => 'checkboxes',
                'display' => 'Hobbies',
                'options' => [
                    'gaming' => 'Gaming',
                    'cooking' => 'Cooking',
                    'travel' => 'Travel',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('checkbox_options');

    expect($output)
        ->toContain('Gaming')
        ->toContain('Cooking')
        ->toContain('Travel');
});

test('checkboxes have Alpine model binding', function () {
    createTestForm('alpine_checkboxes', [
        [
            'handle' => 'options',
            'field' => [
                'type' => 'checkboxes',
                'display' => 'Options',
                'options' => ['a' => 'A', 'b' => 'B'],
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_checkboxes');

    expect($output)->toContain('x-model');
});

test('checkboxes render in grid layout', function () {
    createTestForm('checkbox_grid', [
        [
            'handle' => 'preferences',
            'field' => [
                'type' => 'checkboxes',
                'display' => 'Preferences',
                'options' => [
                    'opt1' => 'Option 1',
                    'opt2' => 'Option 2',
                    'opt3' => 'Option 3',
                    'opt4' => 'Option 4',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('checkbox_grid');

    expect($output)->toContain('grid');
});
