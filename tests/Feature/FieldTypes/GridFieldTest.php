<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('grid field renders correctly', function () {
    createTestForm('grid_test', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_test');

    expect($output)
        ->toContain('data-grid-template="grid_test_passengers"')
        ->toContain('data-grid-rows="grid_test_passengers"')
        ->toContain('ef-grid');
});

test('grid field renders with multiple sub-fields', function () {
    createTestForm('grid_multi', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                    ['handle' => 'category', 'field' => [
                        'type' => 'select',
                        'display' => 'Category',
                        'options' => ['a' => 'Option A', 'b' => 'Option B'],
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_multi');

    expect($output)
        ->toContain('<input')
        ->toContain('<select');
});

test('grid field uses __INDEX__ placeholders in template', function () {
    createTestForm('grid_index', [
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'fields' => [
                    ['handle' => 'title', 'field' => ['type' => 'text', 'display' => 'Title']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_index');

    expect($output)
        ->toContain('name="rows[__INDEX__][title]"')
        ->toContain("submitFields['rows.__INDEX__.title']");
});

test('grid field uses field_id for unique IDs', function () {
    createTestForm('grid_ids', [
        [
            'handle' => 'entries',
            'field' => [
                'type' => 'grid',
                'display' => 'Entries',
                'fields' => [
                    ['handle' => 'value', 'field' => ['type' => 'text', 'display' => 'Value']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_ids');

    // field_key is "entries.__INDEX__.value", field_id replaces dots with underscores
    expect($output)
        ->toContain('id="grid_ids_entries___INDEX___value"');
});

test('grid field sub-fields have correct name format', function () {
    createTestForm('grid_names', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'fields' => [
                    ['handle' => 'first_name', 'field' => ['type' => 'text', 'display' => 'First Name']],
                    ['handle' => 'last_name', 'field' => ['type' => 'text', 'display' => 'Last Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_names');

    expect($output)
        ->toContain('name="passengers[__INDEX__][first_name]"')
        ->toContain('name="passengers[__INDEX__][last_name]"');
});

test('grid field sub-fields have correct field_key', function () {
    createTestForm('grid_keys', [
        [
            'handle' => 'people',
            'field' => [
                'type' => 'grid',
                'display' => 'People',
                'fields' => [
                    ['handle' => 'email', 'field' => ['type' => 'text', 'input_type' => 'email', 'display' => 'Email']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_keys');

    expect($output)
        ->toContain("x-model=\"submitFields['people.__INDEX__.email']\"");
});

test('grid field includes Alpine grid methods', function () {
    createTestForm('grid_alpine', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_alpine');

    expect($output)
        ->toContain("addGridRow('items')")
        ->toContain("removeGridRow('items'")
        ->toContain("cloneGridRow('items'");
});

test('grid field respects min_rows config', function () {
    createTestForm('grid_min', [
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'min_rows' => 3,
                'fields' => [
                    ['handle' => 'text', 'field' => ['type' => 'text', 'display' => 'Text']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_min');

    // x-init initializes with min_rows count
    expect($output)
        ->toContain('|| 3');
});

test('grid field respects fixed_rows config', function () {
    createTestForm('grid_fixed', [
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'fixed_rows' => 2,
                'fields' => [
                    ['handle' => 'text', 'field' => ['type' => 'text', 'display' => 'Text']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_fixed');

    // fixed_rows value used in x-init, no add/remove buttons
    expect($output)
        ->toContain('|| 2')
        ->not->toContain("addGridRow('rows')")
        ->not->toContain("removeGridRow('rows'");
});

test('grid field includes remove button', function () {
    createTestForm('grid_remove', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_remove');

    expect($output)
        ->toContain('Remove')
        ->toContain("removeGridRow('items'")
        ->toContain("canRemoveGridRow('items')");
});

test('grid field hides add and remove buttons when fixed', function () {
    createTestForm('grid_fixed_buttons', [
        [
            'handle' => 'entries',
            'field' => [
                'type' => 'grid',
                'display' => 'Entries',
                'fixed_rows' => 3,
                'fields' => [
                    ['handle' => 'val', 'field' => ['type' => 'text', 'display' => 'Val']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_fixed_buttons');

    expect($output)
        ->not->toContain("addGridRow('entries')")
        ->not->toContain("removeGridRow('entries'")
        ->not->toContain("canAddGridRow('entries')")
        ->not->toContain("canRemoveGridRow('entries')");
});

test('grid field renders add row button with custom text', function () {
    createTestForm('grid_add_text', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'add_row' => 'Add Passenger',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_add_text');

    expect($output)->toContain('Add Passenger');
});

test('grid field includes row number display', function () {
    createTestForm('grid_row_num', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_row_num');

    expect($output)->toContain('row-number');
});

test('grid field has data attributes for JS', function () {
    createTestForm('grid_data', [
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'fields' => [
                    ['handle' => 'col', 'field' => ['type' => 'text', 'display' => 'Col']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_data');

    expect($output)
        ->toContain('data-grid-template="grid_data_rows"')
        ->toContain('data-grid-rows="grid_data_rows"')
        ->toContain('data-grid-row="__INDEX__"');
});

test('grid field sub-fields have aria-describedby when instructions exist', function () {
    createTestForm('grid_aria', [
        [
            'handle' => 'contacts',
            'field' => [
                'type' => 'grid',
                'display' => 'Contacts',
                'fields' => [
                    ['handle' => 'phone', 'field' => [
                        'type' => 'text',
                        'display' => 'Phone',
                        'instructions' => 'Enter phone number with country code',
                        'instructions_position' => 'above',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_aria');

    expect($output)
        ->toContain('Enter phone number with country code')
        ->toContain('aria-describedby="grid_aria_contacts___INDEX___phone-description"');
});

test('grid field works with toggle sub-fields', function () {
    createTestForm('grid_toggle', [
        [
            'handle' => 'settings',
            'field' => [
                'type' => 'grid',
                'display' => 'Settings',
                'fields' => [
                    ['handle' => 'enabled', 'field' => [
                        'type' => 'toggle',
                        'display' => 'Enabled',
                        'inline_label' => 'Enable this setting',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_toggle');

    expect($output)
        ->toContain('type="checkbox"')
        ->toContain('role="switch"')
        ->toContain('name="settings[__INDEX__][enabled]"');
});

test('grid field works with select sub-fields', function () {
    createTestForm('grid_select', [
        [
            'handle' => 'products',
            'field' => [
                'type' => 'grid',
                'display' => 'Products',
                'fields' => [
                    ['handle' => 'size', 'field' => [
                        'type' => 'select',
                        'display' => 'Size',
                        'options' => ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'],
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_select');

    expect($output)
        ->toContain('<select')
        ->toContain('name="products[__INDEX__][size]"')
        ->toContain('Small')
        ->toContain('Medium')
        ->toContain('Large');
});

test('grid field with instance parameter scopes IDs', function () {
    createTestForm('grid_instance', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_instance', ['instance' => 'sidebar']);

    // form_id becomes "grid_instance_sidebar", so IDs use that prefix
    expect($output)
        ->toContain('id="grid_instance_sidebar_items___INDEX___name"');
});

test('grid field required sub-fields are not marked optional', function () {
    createTestForm('grid_required', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'validate' => 'required',
                'fields' => [
                    ['handle' => 'name', 'field' => [
                        'type' => 'text',
                        'display' => 'Name',
                        'validate' => 'required',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_required');

    // Neither the grid field nor the sub-field should show "(Optional)"
    expect($output)
        ->not->toContain('Optional');
});

test('grid field optional sub-fields are marked optional', function () {
    createTestForm('grid_optional', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'fields' => [
                    ['handle' => 'nickname', 'field' => [
                        'type' => 'text',
                        'display' => 'Nickname',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_optional');

    expect($output)->toContain('Optional');
});

test('grid field sub-fields display error messages using dot notation', function () {
    createTestForm('grid_errors', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                    ['handle' => 'email', 'field' => ['type' => 'text', 'input_type' => 'email', 'display' => 'Email']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_errors');

    // Error display should use __INDEX__ dot notation keys
    expect($output)
        ->toContain("errors['passengers.__INDEX__.name']")
        ->toContain("errors['passengers.__INDEX__.email']");
});

test('grid field sub-fields have labels linked to inputs', function () {
    createTestForm('grid_labels', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'fields' => [
                    ['handle' => 'title', 'field' => ['type' => 'text', 'display' => 'Title']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_labels');

    // Labels and inputs should use matching IDs with __INDEX__ placeholder
    expect($output)
        ->toContain('for="grid_labels_items___INDEX___title"')
        ->toContain('id="grid_labels_items___INDEX___title"');
});

test('grid field has accessible container with legend', function () {
    createTestForm('grid_a11y', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_a11y');

    // The grid container should be a fieldset with an aria-label and sr-only legend
    expect($output)
        ->toContain('<fieldset class="ef-grid')
        ->toContain('aria-label="Passengers"')
        ->toContain('<legend class="sr-only">Passengers</legend>');
});

test('grid field dispatches grid-row-removed event on row removal', function () {
    createTestForm('grid_dispatch', [
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'fields' => [
                    ['handle' => 'value', 'field' => ['type' => 'text', 'display' => 'Value']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_dispatch');

    // The form component should listen for the grid-row-removed event
    expect($output)
        ->toContain('x-on:grid-row-removed="handleGridRowRemoved($event.detail)"');
});

test('dynamic_rows_field hides add and remove buttons', function () {
    createTestForm('grid_dynamic', [
        [
            'handle' => 'num_passengers',
            'field' => [
                'type' => 'integer',
                'display' => 'Number of Passengers',
            ],
        ],
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'dynamic_rows_field' => 'num_passengers',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_dynamic');

    // is_fixed should be true, so no add/remove buttons
    expect($output)
        ->not->toContain("addGridRow('passengers')")
        ->not->toContain("removeGridRow('passengers'")
        ->not->toContain("canAddGridRow('passengers')")
        ->not->toContain("canRemoveGridRow('passengers')");
});

test('dynamic_rows_field passes initDynamicGridRows to template', function () {
    createTestForm('grid_dynamic_init', [
        [
            'handle' => 'count',
            'field' => [
                'type' => 'integer',
                'display' => 'Count',
            ],
        ],
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'dynamic_rows_field' => 'count',
                'fields' => [
                    ['handle' => 'value', 'field' => ['type' => 'text', 'display' => 'Value']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_dynamic_init');

    expect($output)
        ->toContain("initDynamicGridRows('rows', 'count')");
});

test('dynamic_rows_field uses controlling field value in x-init', function () {
    createTestForm('grid_dynamic_xinit', [
        [
            'handle' => 'num_items',
            'field' => [
                'type' => 'integer',
                'display' => 'Number of Items',
            ],
        ],
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'dynamic_rows_field' => 'num_items',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_dynamic_xinit');

    expect($output)
        ->toContain("parseInt(submitFields['num_items'])");
});

test('fixed_rows takes precedence over dynamic_rows_field', function () {
    createTestForm('grid_fixed_wins', [
        [
            'handle' => 'count',
            'field' => [
                'type' => 'integer',
                'display' => 'Count',
            ],
        ],
        [
            'handle' => 'rows',
            'field' => [
                'type' => 'grid',
                'display' => 'Rows',
                'fixed_rows' => 3,
                'dynamic_rows_field' => 'count',
                'fields' => [
                    ['handle' => 'value', 'field' => ['type' => 'text', 'display' => 'Value']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_fixed_wins');

    // fixed_rows is set, so dynamic behavior should not appear
    expect($output)
        ->toContain('|| 3')
        ->not->toContain('initDynamicGridRows');
});

test('dynamic_rows_field works when controlling field is defined after grid', function () {
    createTestForm('grid_field_order', [
        [
            'handle' => 'passengers',
            'field' => [
                'type' => 'grid',
                'display' => 'Passengers',
                'dynamic_rows_field' => 'num_passengers',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
        [
            'handle' => 'num_passengers',
            'field' => [
                'type' => 'integer',
                'display' => 'Number of Passengers',
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_field_order');

    // Should still render dynamic grid behavior regardless of field order
    expect($output)
        ->toContain("initDynamicGridRows('passengers', 'num_passengers')")
        ->toContain("parseInt(submitFields['num_passengers'])")
        ->not->toContain("addGridRow('passengers')");
});

test('dynamic_rows_field works with prepopulated_data', function () {
    createTestForm('grid_dynamic_prepop', [
        [
            'handle' => 'count',
            'field' => [
                'type' => 'integer',
                'display' => 'Count',
            ],
        ],
        [
            'handle' => 'items',
            'field' => [
                'type' => 'grid',
                'display' => 'Items',
                'dynamic_rows_field' => 'count',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_dynamic_prepop', [
        'prepopulated_data' => ['count' => 3, 'items.0.name' => 'Alice'],
    ]);

    // Should include prepopulated data and dynamic grid setup
    expect($output)
        ->toContain('Alice')
        ->toContain("initDynamicGridRows('items', 'count')");
});
