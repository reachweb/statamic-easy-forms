<?php

use Reach\StatamicEasyForms\Tags\EmailField;

beforeEach(function () {
    $this->tag = new EmailField;
    $this->tag->setContext([
        'form:handle' => 'test_form',
        'id' => 'submission-123',
    ]);
});

test('returns empty string when field parameter is missing', function () {
    $this->tag->setParameters([]);

    expect($this->tag->index())->toBe('');
});

test('returns empty string when field is not an array', function () {
    $this->tag->setParameters(['field' => 'not-an-array']);

    expect($this->tag->index())->toBe('');
});

test('returns empty string when value is null', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Name',
            'value' => null,
            'fieldtype' => 'text',
        ],
    ]);

    expect($this->tag->index())->toBe('');
});

test('returns empty string when value is empty string', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Name',
            'value' => '',
            'fieldtype' => 'text',
        ],
    ]);

    expect($this->tag->index())->toBe('');
});

test('returns empty string when value is empty array', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Options',
            'value' => [],
            'fieldtype' => 'checkboxes',
        ],
    ]);

    expect($this->tag->index())->toBe('');
});

test('renders standard text field correctly', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Name',
            'value' => 'John Doe',
            'fieldtype' => 'text',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Name')
        ->toContain('John Doe')
        ->toContain('<tr>')
        ->toContain('</tr>');
});

test('escapes HTML entities in text values', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Company',
            'value' => 'Smith & Sons <Ltd>',
            'fieldtype' => 'text',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Smith &amp; Sons &lt;Ltd&gt;')
        ->not->toContain('<Ltd>');
});

test('renders array values as comma-separated list', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Interests',
            'value' => ['Sports', 'Music', 'Technology'],
            'fieldtype' => 'checkboxes',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Interests')
        ->toContain('Sports, Music, Technology');
});

test('renders array of label/value pairs using labels', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Countries',
            'value' => [
                ['label' => 'United States', 'value' => 'us'],
                ['label' => 'Canada', 'value' => 'ca'],
            ],
            'fieldtype' => 'select',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Countries')
        ->toContain('United States, Canada');
});

test('falls back to value when label not present in array items', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Options',
            'value' => [
                ['value' => 'option_a'],
                ['value' => 'option_b'],
            ],
            'fieldtype' => 'checkboxes',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)->toContain('option_a, option_b');
});

test('falls back to key when label and value not present', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Items',
            'value' => [
                ['key' => 'key_1'],
                ['key' => 'key_2'],
            ],
            'fieldtype' => 'checkboxes',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)->toContain('key_1, key_2');
});

test('renders boolean true as Yes', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Subscribe',
            'value' => true,
            'fieldtype' => 'toggle',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Subscribe')
        ->toContain('Yes');
});

test('renders assets field with CP link', function () {
    config(['app.url' => 'https://example.com']);

    // Create a mock form object with handle method
    $form = new class
    {
        public function handle()
        {
            return 'test_form';
        }
    };

    $this->tag->setContext([
        'form' => $form,
        'id' => 'submission-123',
    ]);

    $this->tag->setParameters([
        'field' => [
            'display' => 'Attachment',
            'value' => ['file.pdf'],
            'fieldtype' => 'assets',
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Attachment')
        ->toContain('Login to view file(s)')
        ->toContain('/cp/forms/test_form/submissions/submission-123');
});

test('renders group field with header and nested fields', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Previous Job',
            'value' => [
                'start_date' => '2023-01-01',
                'end_date' => '2024-01-01',
            ],
            'fieldtype' => 'group',
            'config' => [
                'fields' => [
                    [
                        'handle' => 'start_date',
                        'field' => ['display' => 'Start Date', 'type' => 'text'],
                    ],
                    [
                        'handle' => 'end_date',
                        'field' => ['display' => 'End Date', 'type' => 'text'],
                    ],
                ],
            ],
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Previous Job')
        ->toContain('Start Date')
        ->toContain('2023-01-01')
        ->toContain('End Date')
        ->toContain('2024-01-01');
});

test('group field skips nested fields with empty values', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Address',
            'value' => [
                'street' => '123 Main St',
                'apt' => '',
                'city' => 'New York',
            ],
            'fieldtype' => 'group',
            'config' => [
                'fields' => [
                    [
                        'handle' => 'street',
                        'field' => ['display' => 'Street', 'type' => 'text'],
                    ],
                    [
                        'handle' => 'apt',
                        'field' => ['display' => 'Apt #', 'type' => 'text'],
                    ],
                    [
                        'handle' => 'city',
                        'field' => ['display' => 'City', 'type' => 'text'],
                    ],
                ],
            ],
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Street')
        ->toContain('123 Main St')
        ->not->toContain('Apt #')
        ->toContain('City')
        ->toContain('New York');
});

test('group field returns empty when all nested values are empty', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Empty Group',
            'value' => [
                'field1' => '',
                'field2' => null,
            ],
            'fieldtype' => 'group',
            'config' => [
                'fields' => [
                    [
                        'handle' => 'field1',
                        'field' => ['display' => 'Field 1', 'type' => 'text'],
                    ],
                    [
                        'handle' => 'field2',
                        'field' => ['display' => 'Field 2', 'type' => 'text'],
                    ],
                ],
            ],
        ],
    ]);

    expect($this->tag->index())->toBe('');
});

test('group field handles nested array values', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Preferences',
            'value' => [
                'colors' => ['Red', 'Blue'],
            ],
            'fieldtype' => 'group',
            'config' => [
                'fields' => [
                    [
                        'handle' => 'colors',
                        'field' => ['display' => 'Favorite Colors', 'type' => 'checkboxes'],
                    ],
                ],
            ],
        ],
    ]);

    $output = $this->tag->index();

    expect($output)
        ->toContain('Preferences')
        ->toContain('Favorite Colors')
        ->toContain('Red, Blue');
});

test('applies correct styling for group header', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Section Header',
            'value' => ['field' => 'value'],
            'fieldtype' => 'group',
            'config' => [
                'fields' => [
                    [
                        'handle' => 'field',
                        'field' => ['display' => 'Field', 'type' => 'text'],
                    ],
                ],
            ],
        ],
    ]);

    $output = $this->tag->index();

    // Group header should have uppercase transform
    expect($output)->toContain('text-transform: uppercase');
});

test('applies correct styling for nested fields', function () {
    $this->tag->setParameters([
        'field' => [
            'display' => 'Group',
            'value' => ['field' => 'value'],
            'fieldtype' => 'group',
            'config' => [
                'fields' => [
                    [
                        'handle' => 'field',
                        'field' => ['display' => 'Field', 'type' => 'text'],
                    ],
                ],
            ],
        ],
    ]);

    $output = $this->tag->index();

    // Nested fields should have left padding
    expect($output)->toContain('padding: 12px 10px 12px 15px');
});
