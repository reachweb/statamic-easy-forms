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

test('select field with multiple attribute renders correctly', function () {
    createTestForm('select_multiple', [
        [
            'handle' => 'colors',
            'field' => [
                'type' => 'select',
                'display' => 'Colors',
                'options' => [
                    'red' => 'Red',
                    'blue' => 'Blue',
                    'green' => 'Green',
                ],
                'multiple' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_multiple');

    expect($output)
        ->toContain('name="colors[]"')
        ->toContain('multiple')
        ->not->toContain('form-select')
        ->not->toContain('Please select');
});

test('select field without multiple has form-select class and placeholder', function () {
    createTestForm('select_single', [
        [
            'handle' => 'color',
            'field' => [
                'type' => 'select',
                'display' => 'Color',
                'options' => [
                    'red' => 'Red',
                    'blue' => 'Blue',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_single');

    expect($output)
        ->toContain('name="color"')
        ->toContain('form-select')
        ->toContain('Please select')
        ->not->toContain('name="color[]"');
});

// Tests for improved select field
test('select improved field renders with single selection (no checkboxes)', function () {
    createTestForm('select_improved_single', [
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
                'improved_field' => true,
                'multiple' => false,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_single');

    expect($output)
        ->toContain('selectOption')
        ->toContain('role="combobox"')
        ->not->toContain('type="checkbox"');
});

test('select improved field renders with multiple selection (with checkboxes)', function () {
    createTestForm('select_improved_multiple', [
        [
            'handle' => 'countries',
            'field' => [
                'type' => 'select',
                'display' => 'Countries',
                'options' => [
                    'us' => 'United States',
                    'ca' => 'Canada',
                    'uk' => 'United Kingdom',
                ],
                'improved_field' => true,
                'multiple' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_multiple');

    expect($output)
        ->toContain('handleOptionToggle')
        ->toContain('type="checkbox"')
        ->toContain('form-checkbox');
});

test('select improved field includes search when searchable is true', function () {
    createTestForm('select_improved_searchable', [
        [
            'handle' => 'city',
            'field' => [
                'type' => 'select',
                'display' => 'City',
                'options' => [
                    'ny' => 'New York',
                    'la' => 'Los Angeles',
                    'sf' => 'San Francisco',
                ],
                'improved_field' => true,
                'searchable' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_searchable');

    expect($output)
        ->toContain('searchField')
        ->toContain('getFilteredOptions')
        ->toContain('placeholder="Search"');
});

test('select improved field includes search by default', function () {
    createTestForm('select_improved_default_search', [
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
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_default_search');

    expect($output)
        ->toContain('searchField')
        ->toContain('placeholder="Search"');
});

test('select improved field includes clear button when clearable is true', function () {
    createTestForm('select_improved_clearable', [
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
                'improved_field' => true,
                'clearable' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_clearable');

    expect($output)
        ->toContain('selectedOptions = []')
        ->toContain('resetSearch');
});

test('select improved field includes all options in dropdown', function () {
    createTestForm('select_improved_options', [
        [
            'handle' => 'fruit',
            'field' => [
                'type' => 'select',
                'display' => 'Fruit',
                'options' => [
                    'apple' => 'Apple',
                    'banana' => 'Banana',
                    'orange' => 'Orange',
                ],
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_options');

    expect($output)
        ->toContain('Apple')
        ->toContain('Banana')
        ->toContain('Orange');
});

test('select improved field has proper Alpine.js data structure', function () {
    createTestForm('select_improved_alpine', [
        [
            'handle' => 'status',
            'field' => [
                'type' => 'select',
                'display' => 'Status',
                'options' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_alpine');

    expect($output)
        ->toContain('x-data')
        ->toContain('allOptions')
        ->toContain('selectedOptions')
        ->toContain('isOpen')
        ->toContain('updateSelectedLabels')
        ->toContain('submitFields');
});

test('select improved field closes dropdown on single selection', function () {
    createTestForm('select_improved_close', [
        [
            'handle' => 'option',
            'field' => [
                'type' => 'select',
                'display' => 'Option',
                'options' => [
                    'a' => 'Option A',
                    'b' => 'Option B',
                ],
                'improved_field' => true,
                'multiple' => false,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_close');

    expect($output)
        ->toContain('this.isOpen = false');
});

test('select improved field respects max_items for multiple selection', function () {
    createTestForm('select_improved_max', [
        [
            'handle' => 'tags',
            'field' => [
                'type' => 'select',
                'display' => 'Tags',
                'options' => [
                    'tag1' => 'Tag 1',
                    'tag2' => 'Tag 2',
                    'tag3' => 'Tag 3',
                ],
                'improved_field' => true,
                'multiple' => true,
                'max_items' => 2,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_max');

    expect($output)
        ->toContain('max_items')
        ->toContain('selectedOptions.length >= 2');
});

test('select improved field has proper focus states', function () {
    createTestForm('select_improved_focus', [
        [
            'handle' => 'field',
            'field' => [
                'type' => 'select',
                'display' => 'Field',
                'options' => ['a' => 'A', 'b' => 'B'],
                'improved_field' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('select_improved_focus');

    expect($output)
        ->toContain('focus:ring-2')
        ->toContain('focus:ring-ef-focus')
        ->toContain('border-ef-focus');
});
