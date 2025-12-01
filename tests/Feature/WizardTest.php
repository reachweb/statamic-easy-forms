<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('form renders normally without wizard parameter', function () {
    createFormWithSections('normal_form', [
        [
            'display' => 'Section One',
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
        [
            'display' => 'Section Two',
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

    $output = renderEasyFormTag('normal_form');

    expect($output)
        ->toContain('<fieldset')
        ->toContain('<legend')
        ->toContain('Section One')
        ->toContain('Section Two')
        ->not->toContain('wizardHandler')
        ->not->toContain('currentStep');
});

test('form renders wizard mode when wizard parameter is true', function () {
    createFormWithSections('wizard_form', [
        [
            'display' => 'Step One',
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
        [
            'display' => 'Step Two',
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

    $output = renderEasyFormTag('wizard_form', ['wizard' => true]);

    expect($output)
        ->toContain('wizardHandler(2')
        ->toContain('currentStep')
        ->toContain('x-ref="step-1"')
        ->toContain('x-ref="step-2"')
        ->not->toContain('<fieldset')
        ->not->toContain('<legend');
});

test('wizard shows progress indicator', function () {
    createFormWithSections('wizard_progress', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_progress', ['wizard' => true]);

    expect($output)
        ->toContain('x-ref="wizard"')
        ->toContain('getProgressPercent()')
        ->toContain('of 2'); // "Step X of 2"
});

test('wizard shows navigation buttons', function () {
    createFormWithSections('wizard_nav', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_nav', ['wizard' => true]);

    expect($output)
        ->toContain('goPrev()')
        ->toContain('goNext()')
        ->toContain('Previous')
        ->toContain('Next');
});

test('wizard submit button only shows on last step', function () {
    createFormWithSections('wizard_submit', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_submit', ['wizard' => true]);

    // Submit button should be visible only when currentStep === 2 (last step)
    expect($output)
        ->toContain('x-show="currentStep === 2"')
        ->toContain('type="submit"');
});

test('wizard previous button hidden on first step', function () {
    createFormWithSections('wizard_prev', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_prev', ['wizard' => true]);

    // Previous button should only show when currentStep > 1
    expect($output)
        ->toContain('x-show="currentStep > 1"')
        ->toContain('goPrev()');
});

test('wizard sections include step metadata', function () {
    createFormWithSections('wizard_metadata', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
        [
            'display' => 'Step Three',
            'fields' => [
                [
                    'handle' => 'message',
                    'field' => ['type' => 'textarea', 'display' => 'Message'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_metadata', ['wizard' => true]);

    expect($output)
        ->toContain('wizardHandler(3')
        ->toContain('x-ref="step-1"')
        ->toContain('x-ref="step-2"')
        ->toContain('x-ref="step-3"')
        ->toContain('currentStep === 1')
        ->toContain('currentStep === 2')
        ->toContain('currentStep === 3');
});

test('wizard includes step field handles for validation', function () {
    createFormWithSections('wizard_fields', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'first_name',
                    'field' => ['type' => 'text', 'display' => 'First Name'],
                ],
                [
                    'handle' => 'last_name',
                    'field' => ['type' => 'text', 'display' => 'Last Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_fields', ['wizard' => true]);

    // Check stepFields initialization contains field handles
    expect($output)
        ->toContain('stepFields')
        ->toContain('first_name')
        ->toContain('last_name')
        ->toContain('email');
});

test('wizard works with precognition enabled', function () {
    createFormWithSections('wizard_precog', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_precog', ['wizard' => true, 'precognition' => true]);

    expect($output)
        ->toContain('wizardHandler(2)')
        ->toContain('formHandler(')
        ->toContain(', true)'); // precognition enabled in formHandler
});

test('wizard custom submit text works', function () {
    createFormWithSections('wizard_custom_text', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_custom_text', [
        'wizard' => true,
        'submit_text' => 'Send Application',
    ]);

    expect($output)
        ->toContain('Previous')
        ->toContain('Next')
        ->toContain('Send Application');
});

test('form without sections renders normally even with wizard enabled', function () {
    createTestForm('no_sections_wizard', [
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

    $output = renderEasyFormTag('no_sections_wizard', ['wizard' => true]);

    // Without sections, wizard mode still initializes but with 0 steps
    // Should render fields normally in a grid
    expect($output)
        ->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('grid grid-cols-12');
});

test('wizard shows step validating state', function () {
    createFormWithSections('wizard_validating', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_validating', ['wizard' => true]);

    expect($output)
        ->toContain('stepValidating')
        ->toContain('Validating...');
});

test('wizard step headers render correctly', function () {
    createFormWithSections('wizard_headers', [
        [
            'display' => 'Personal Details',
            'instructions' => 'Enter your personal information',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Contact Information',
            'instructions' => 'How can we reach you?',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_headers', ['wizard' => true]);

    expect($output)
        ->toContain('Personal Details')
        ->toContain('Enter your personal information')
        ->toContain('Contact Information')
        ->toContain('How can we reach you?')
        // In wizard mode, headers use h2 instead of legend
        ->toContain('<h2');
});

test('wizard does not show dividers between steps', function () {
    createFormWithSections('wizard_no_dividers', [
        [
            'display' => 'Step One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Step Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('wizard_no_dividers', ['wizard' => true]);

    // Wizard mode should not have the divide-y class
    expect($output)
        ->not->toContain('divide-y divide-ef-border');
});

test('regular form with sections still shows dividers', function () {
    createFormWithSections('regular_dividers', [
        [
            'display' => 'Section One',
            'fields' => [
                [
                    'handle' => 'name',
                    'field' => ['type' => 'text', 'display' => 'Name'],
                ],
            ],
        ],
        [
            'display' => 'Section Two',
            'fields' => [
                [
                    'handle' => 'email',
                    'field' => ['type' => 'text', 'display' => 'Email'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('regular_dividers');

    expect($output)
        ->toContain('divide-y divide-ef-border');
});
