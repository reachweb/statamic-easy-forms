<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('group field renders correctly', function () {
    createTestForm('group_test', [
        [
            'handle' => 'address',
            'field' => [
                'type' => 'group',
                'display' => 'Address',
                'fields' => [
                    ['handle' => 'street', 'field' => ['type' => 'text', 'display' => 'Street']],
                    ['handle' => 'city', 'field' => ['type' => 'text', 'display' => 'City']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_test');

    expect($output)
        ->toContain('<fieldset')
        ->toContain('Address')
        ->toContain('name="address[street]"')
        ->toContain('name="address[city]"');
});

test('group field sub-fields use dot notation for field_key', function () {
    createTestForm('group_keys', [
        [
            'handle' => 'contact',
            'field' => [
                'type' => 'group',
                'display' => 'Contact',
                'fields' => [
                    ['handle' => 'email', 'field' => ['type' => 'text', 'input_type' => 'email', 'display' => 'Email']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_keys');

    expect($output)
        ->toContain("x-model=\"submitFields['contact.email']\"");
});

test('group field sub-fields have correct IDs', function () {
    createTestForm('group_ids', [
        [
            'handle' => 'person',
            'field' => [
                'type' => 'group',
                'display' => 'Person',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_ids');

    // field_key is "person.name", field_id replaces dots with underscores
    expect($output)
        ->toContain('id="group_ids_person_name"');
});

test('group field renders legend when display is set', function () {
    createTestForm('group_legend', [
        [
            'handle' => 'details',
            'field' => [
                'type' => 'group',
                'display' => 'Personal Details',
                'fields' => [
                    ['handle' => 'age', 'field' => ['type' => 'text', 'display' => 'Age']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_legend');

    expect($output)
        ->toContain('<legend')
        ->toContain('Personal Details');
});

test('group field with multiple sub-field types', function () {
    createTestForm('group_multi', [
        [
            'handle' => 'preferences',
            'field' => [
                'type' => 'group',
                'display' => 'Preferences',
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']],
                    ['handle' => 'color', 'field' => [
                        'type' => 'select',
                        'display' => 'Favorite Color',
                        'options' => ['red' => 'Red', 'blue' => 'Blue'],
                    ]],
                    ['handle' => 'newsletter', 'field' => [
                        'type' => 'toggle',
                        'display' => 'Newsletter',
                        'inline_label' => 'Subscribe to newsletter',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_multi');

    expect($output)
        ->toContain('name="preferences[name]"')
        ->toContain('name="preferences[color]"')
        ->toContain('name="preferences[newsletter]"')
        ->toContain('<select')
        ->toContain('type="checkbox"');
});

test('group field with instance parameter scopes IDs', function () {
    createTestForm('group_instance', [
        [
            'handle' => 'info',
            'field' => [
                'type' => 'group',
                'display' => 'Info',
                'fields' => [
                    ['handle' => 'note', 'field' => ['type' => 'text', 'display' => 'Note']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_instance', ['instance' => 'sidebar']);

    // form_id becomes "group_instance_sidebar", so IDs use that prefix
    expect($output)
        ->toContain('id="group_instance_sidebar_info_note"');
});

test('group field required sub-fields are not marked optional', function () {
    createTestForm('group_required', [
        [
            'handle' => 'contact',
            'field' => [
                'type' => 'group',
                'display' => 'Contact',
                'fields' => [
                    ['handle' => 'email', 'field' => [
                        'type' => 'text',
                        'input_type' => 'email',
                        'display' => 'Email',
                        'validate' => 'required|email',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_required');

    // Required field should NOT have the "(Optional)" label
    expect($output)
        ->not->toContain('Optional');
});

test('group field optional sub-fields are marked optional', function () {
    createTestForm('group_optional', [
        [
            'handle' => 'extras',
            'field' => [
                'type' => 'group',
                'display' => 'Extras',
                'fields' => [
                    ['handle' => 'notes', 'field' => [
                        'type' => 'text',
                        'display' => 'Notes',
                    ]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_optional');

    expect($output)->toContain('Optional');
});

test('group field sub-fields display error messages using dot notation', function () {
    createTestForm('group_errors', [
        [
            'handle' => 'address',
            'field' => [
                'type' => 'group',
                'display' => 'Address',
                'fields' => [
                    ['handle' => 'street', 'field' => ['type' => 'text', 'display' => 'Street']],
                    ['handle' => 'city', 'field' => ['type' => 'text', 'display' => 'City']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_errors');

    // Error display should use dot notation keys to match Laravel validation errors
    expect($output)
        ->toContain("errors['address.street']")
        ->toContain("errors['address.city']");
});

test('group field sub-fields have labels linked to inputs', function () {
    createTestForm('group_labels', [
        [
            'handle' => 'person',
            'field' => [
                'type' => 'group',
                'display' => 'Person',
                'fields' => [
                    ['handle' => 'first_name', 'field' => ['type' => 'text', 'display' => 'First Name']],
                    ['handle' => 'last_name', 'field' => ['type' => 'text', 'display' => 'Last Name']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('group_labels');

    // Labels should use for="form_id_field_id" and inputs should use matching id
    expect($output)
        ->toContain('for="group_labels_person_first_name"')
        ->toContain('id="group_labels_person_first_name"')
        ->toContain('for="group_labels_person_last_name"')
        ->toContain('id="group_labels_person_last_name"');
});
