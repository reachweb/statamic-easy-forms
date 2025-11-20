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
