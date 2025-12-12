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

test('toggle field can display instructions above', function () {
    createTestForm('toggle_instructions_above', [
        [
            'handle' => 'marketing',
            'field' => [
                'type' => 'toggle',
                'display' => 'Marketing Emails',
                'instructions' => 'Receive marketing and promotional emails',
                'instructions_position' => 'above',
            ],
        ],
    ]);

    $output = renderEasyFormTag('toggle_instructions_above');

    expect($output)
        ->toContain('Receive marketing and promotional emails')
        ->toContain('id="toggle_instructions_above_marketing-description"');
});

test('toggle field can display instructions below', function () {
    createTestForm('toggle_instructions_below', [
        [
            'handle' => 'notifications',
            'field' => [
                'type' => 'toggle',
                'display' => 'Notifications',
                'instructions' => 'Get notified about important updates',
                'instructions_position' => 'below',
            ],
        ],
    ]);

    $output = renderEasyFormTag('toggle_instructions_below');

    expect($output)
        ->toContain('Get notified about important updates')
        ->toContain('id="toggle_instructions_below_notifications-description"');
});

test('toggle field displays instructions by default above when position not specified', function () {
    createTestForm('toggle_instructions_default', [
        [
            'handle' => 'terms',
            'field' => [
                'type' => 'toggle',
                'display' => 'Accept Terms',
                'instructions' => 'By enabling this, you accept our terms of service',
            ],
        ],
    ]);

    $output = renderEasyFormTag('toggle_instructions_default');

    expect($output)
        ->toContain('By enabling this, you accept our terms of service')
        ->toContain('id="toggle_instructions_default_terms-description"');
});
