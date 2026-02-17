<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('form without sections renders fields in simple grid', function () {
    createTestForm('no_sections', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'input_type' => 'email',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('no_sections');

    expect($output)
        ->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('grid grid-cols-12')
        ->not->toContain('<legend');
});

test('form with sections renders fieldsets', function () {
    $form = createFormWithSections('sectioned_form', [
        [
            'display' => 'Personal Information',
            'instructions' => 'Please provide your personal details',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Name',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('sectioned_form');

    expect($output)
        ->toContain('<fieldset')
        ->toContain('<legend')
        ->toContain('Personal Information')
        ->toContain('Please provide your personal details');
});

test('form with multiple sections renders all sections', function () {
    createFormWithSections('multi_sections', [
        [
            'display' => 'Section One',
            'instructions' => 'First section instructions',
            'fields' => [
                [
                    'handle' => 'field1',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 1',
                    ],
                ],
            ],
        ],
        [
            'display' => 'Section Two',
            'instructions' => 'Second section instructions',
            'fields' => [
                [
                    'handle' => 'field2',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 2',
                    ],
                ],
            ],
        ],
        [
            'display' => 'Section Three',
            'fields' => [
                [
                    'handle' => 'field3',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 3',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('multi_sections');

    expect($output)
        ->toContain('Section One')
        ->toContain('First section instructions')
        ->toContain('Section Two')
        ->toContain('Second section instructions')
        ->toContain('Section Three')
        ->toContain('name="field1"')
        ->toContain('name="field2"')
        ->toContain('name="field3"');
});

test('section without display or instructions still renders fields', function () {
    createFormWithSections('no_section_meta', [
        [
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Name',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('no_section_meta');

    expect($output)
        ->toContain('name="name"')
        ->toContain('<fieldset');
});

test('section with only display renders without instructions', function () {
    createFormWithSections('display_only', [
        [
            'display' => 'Contact Details',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => [
                        'type' => 'text',
                        'input_type' => 'email',
                        'display' => 'Email',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('display_only');

    expect($output)
        ->toContain('<legend')
        ->toContain('Contact Details')
        ->toContain('name="email"');
});

test('section with only instructions renders without legend', function () {
    createFormWithSections('instructions_only', [
        [
            'instructions' => 'Fill out the form below',
            'fields' => [
                [
                    'handle' => 'message',
                    'field' => [
                        'type' => 'textarea',
                        'display' => 'Message',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructions_only');

    expect($output)
        ->not->toContain('<legend')
        ->toContain('Fill out the form below')
        ->toContain('name="message"');
});

test('section fields use grid layout', function () {
    createFormWithSections('grid_layout', [
        [
            'display' => 'Test Section',
            'fields' => [
                [
                    'handle' => 'field1',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 1',
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'field2',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 2',
                        'width' => 50,
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('grid_layout');

    expect($output)
        ->toContain('grid grid-cols-12')
        ->toContain('col-span-12 md:col-span-6');
});

test('sections have proper spacing classes', function () {
    createFormWithSections('spacing_test', [
        [
            'display' => 'Section One',
            'fields' => [
                [
                    'handle' => 'field1',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 1',
                    ],
                ],
            ],
        ],
        [
            'display' => 'Section Two',
            'fields' => [
                [
                    'handle' => 'field2',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field 2',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('spacing_test');

    expect($output)
        ->toContain('space-y-6')
        ->toContain('divide-y')
        ->toContain('divide-ef-border');
});

test('section legend has correct styling', function () {
    createFormWithSections('legend_style', [
        [
            'display' => 'My Section',
            'fields' => [
                [
                    'handle' => 'field',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('legend_style');

    expect($output)
        ->toContain('text-ef-xl')
        ->toContain('font-bold')
        ->toContain('text-ef-label')
        ->toContain('mb-6');
});

test('section instructions have correct styling', function () {
    createFormWithSections('instructions_style', [
        [
            'display' => 'Section',
            'instructions' => 'These are instructions',
            'fields' => [
                [
                    'handle' => 'field',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Field',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructions_style');

    expect($output)
        ->toContain('text-ef-sm')
        ->toContain('text-ef-text-muted')
        ->toContain('mb-6');
});

test('section can contain multiple fields', function () {
    createFormWithSections('multiple_fields', [
        [
            'display' => 'Contact Information',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Name',
                    ],
                ],
                [
                    'handle' => 'email',
                    'field' => [
                        'type' => 'text',
                        'input_type' => 'email',
                        'display' => 'Email',
                    ],
                ],
                [
                    'handle' => 'phone',
                    'field' => [
                        'type' => 'text',
                        'input_type' => 'tel',
                        'display' => 'Phone',
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('multiple_fields');

    expect($output)
        ->toContain('Contact Information')
        ->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('name="phone"');
});

test('section fields render with correct field types', function () {
    createFormWithSections('field_types', [
        [
            'display' => 'Mixed Fields',
            'fields' => [
                [
                    'handle' => 'text_field',
                    'field' => [
                        'type' => 'text',
                        'display' => 'Text',
                    ],
                ],
                [
                    'handle' => 'textarea_field',
                    'field' => [
                        'type' => 'textarea',
                        'display' => 'Textarea',
                    ],
                ],
                [
                    'handle' => 'select_field',
                    'field' => [
                        'type' => 'select',
                        'display' => 'Select',
                        'options' => ['a' => 'Option A', 'b' => 'Option B'],
                    ],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('field_types');

    expect($output)
        ->toContain('name="text_field"')
        ->toContain('name="textarea_field"')
        ->toContain('name="select_field"');
});
