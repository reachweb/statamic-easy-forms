<?php

use Reach\StatamicEasyForms\Modifiers\NeededProperties;

test('modifier filters field properties correctly', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'email',
        'type' => 'text',
        'display' => 'Email',
        'validate' => 'required|email',
        'optional' => false,

        // Should be filtered out
        'config' => ['internal' => 'config'],
        'conditions' => ['some' => 'condition'],
        'component' => 'text',
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toBeArray()
        ->toHaveKey('handle', 'email')
        ->toHaveKey('type', 'text')
        ->toHaveKey('display', 'Email')
        ->toHaveKey('validate')
        ->toHaveKey('optional', false)
        ->not->toHaveKey('config')
        ->not->toHaveKey('conditions')
        ->not->toHaveKey('component');
});

test('modifier preserves required properties', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'name',
        'type' => 'text',
        'display' => 'Name',
        'instructions' => 'Enter your name',
        'placeholder' => 'John Doe',
        'validate' => 'required',
        'width' => 50,
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('handle')
        ->toHaveKey('type')
        ->toHaveKey('display')
        ->toHaveKey('instructions')
        ->toHaveKey('placeholder')
        ->toHaveKey('validate')
        ->toHaveKey('width');
});

test('modifier removes null values', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'test',
        'type' => 'text',
        'display' => 'Test',
        'placeholder' => null,
        'instructions' => null,
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('handle')
        ->toHaveKey('type')
        ->toHaveKey('display')
        ->not->toHaveKey('placeholder')
        ->not->toHaveKey('instructions');
});

test('modifier removes empty strings', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'test',
        'type' => 'text',
        'display' => 'Test',
        'placeholder' => '',
        'instructions' => '',
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->not->toHaveKey('placeholder')
        ->not->toHaveKey('instructions');
});

test('modifier keeps false values', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'test',
        'type' => 'toggle',
        'display' => 'Test',
        'optional' => false,
        'default' => false,
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('optional', false)
        ->toHaveKey('default', false);
});

test('modifier keeps zero values', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'quantity',
        'type' => 'integer',
        'display' => 'Quantity',
        'min' => 0,
        'default' => 0,
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('min', 0)
        ->toHaveKey('default', 0);
});

test('modifier handles array of fields', function () {
    $modifier = new NeededProperties();

    $fields = [
        [
            'handle' => 'field1',
            'type' => 'text',
            'display' => 'Field 1',
            'component' => 'text',
        ],
        [
            'handle' => 'field2',
            'type' => 'textarea',
            'display' => 'Field 2',
            'component' => 'textarea',
        ],
    ];

    $result = $modifier->index($fields, [], []);

    expect($result)
        ->toBeArray()
        ->toHaveCount(2);

    expect($result[0])
        ->toHaveKey('handle', 'field1')
        ->not->toHaveKey('component');

    expect($result[1])
        ->toHaveKey('handle', 'field2')
        ->not->toHaveKey('component');
});

test('modifier preserves select field options', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'country',
        'type' => 'select',
        'display' => 'Country',
        'options' => [
            'us' => 'United States',
            'ca' => 'Canada',
            'uk' => 'United Kingdom',
        ],
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('options')
        ->and($result['options'])
        ->toBeArray()
        ->toHaveKey('us');
});

test('modifier preserves file upload configuration', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'attachment',
        'type' => 'assets',
        'display' => 'Attachment',
        'container' => 'assets',
        'max_files' => 5,
        'max_file_size' => 10,
        'allowed_extensions' => ['pdf', 'doc', 'docx'],
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('container')
        ->toHaveKey('max_files')
        ->toHaveKey('max_file_size')
        ->toHaveKey('allowed_extensions');
});

test('modifier preserves conditional logic fields', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'conditional_field',
        'type' => 'text',
        'display' => 'Conditional',
        'if' => 'other_field == "yes"',
        'show_when' => ['other_field' => 'yes'],
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toHaveKey('if')
        ->toHaveKey('show_when');
});

test('modifier handles single field without wrapping in array', function () {
    $modifier = new NeededProperties();

    $fieldData = [
        'handle' => 'single',
        'type' => 'text',
        'display' => 'Single Field',
        'component' => 'text',
    ];

    $result = $modifier->index($fieldData, [], []);

    expect($result)
        ->toBeArray()
        ->toHaveKey('handle', 'single')
        ->not->toHaveKey('component');
});

test('modifier returns empty array for invalid input', function () {
    $modifier = new NeededProperties();

    $result = $modifier->index('invalid', [], []);

    expect($result)->toBe([]);
});
