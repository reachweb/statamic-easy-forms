<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('textarea field renders correctly', function () {
    createTestForm('textarea_test', [
        [
            'handle' => 'message',
            'field' => [
                'type' => 'textarea',
                'display' => 'Message',
            ],
        ],
    ]);

    $output = renderEasyFormTag('textarea_test');

    expect($output)
        ->toContain('name="message"')
        ->toContain('<textarea');
});

test('textarea with placeholder renders correctly', function () {
    createTestForm('textarea_placeholder', [
        [
            'handle' => 'comment',
            'field' => [
                'type' => 'textarea',
                'display' => 'Comment',
                'placeholder' => 'Enter your comment here',
            ],
        ],
    ]);

    $output = renderEasyFormTag('textarea_placeholder');

    expect($output)->toContain('Enter your comment here');
});

test('textarea has Alpine model binding', function () {
    createTestForm('alpine_textarea', [
        [
            'handle' => 'notes',
            'field' => [
                'type' => 'textarea',
                'display' => 'Notes',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_textarea');

    expect($output)->toContain('x-model');
});
