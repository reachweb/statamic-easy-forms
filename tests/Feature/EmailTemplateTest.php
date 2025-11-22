<?php

use Illuminate\Support\Facades\View;
use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('email template renders with basic text fields', function () {
    $form = createTestForm('contact_form');

    $data = [
        'fields' => [
            [
                'display' => 'Name',
                'value' => 'John Doe',
                'fieldtype' => 'text',
            ],
            [
                'display' => 'Email',
                'value' => 'john@example.com',
                'fieldtype' => 'text',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Name')
        ->toContain('John Doe')
        ->toContain('Email')
        ->toContain('john@example.com')
        ->toContain('New Submission');
});

test('email template renders with array values using comma separation', function () {
    $form = createTestForm('preferences_form');

    $data = [
        'fields' => [
            [
                'display' => 'Interests',
                'value' => ['Sports', 'Music', 'Technology'],
                'fieldtype' => 'checkboxes',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Interests')
        ->toContain('Sports')
        ->toContain('Music')
        ->toContain('Technology');
});

test('email template renders with array values containing objects', function () {
    $form = createTestForm('options_form');

    // Simulate array with label/value pairs (common in select/radio fields)
    $data = [
        'fields' => [
            [
                'display' => 'Preferred Contact',
                'value' => [
                    ['label' => 'Email', 'value' => 'email'],
                    ['label' => 'Phone', 'value' => 'phone'],
                ],
                'fieldtype' => 'checkboxes',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Preferred Contact')
        ->toContain('Email')
        ->toContain('Phone');
});

test('email template does not render null or empty values', function () {
    $form = createTestForm('partial_form');

    $data = [
        'fields' => [
            [
                'display' => 'Optional Field',
                'value' => null,
                'fieldtype' => 'text',
            ],
            [
                'display' => 'Another Field',
                'value' => 'Has Value',
                'fieldtype' => 'text',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->not->toContain('Optional Field')
        ->toContain('Another Field')
        ->toContain('Has Value')
        ->not->toContain('null');
});

test('email template excludes section fields', function () {
    $form = createTestForm('sectioned_form');

    $data = [
        'fields' => [
            [
                'display' => 'Personal Information',
                'fieldtype' => 'section',
            ],
            [
                'display' => 'Name',
                'value' => 'Jane Smith',
                'fieldtype' => 'text',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->not->toContain('Personal Information')
        ->toContain('Name')
        ->toContain('Jane Smith');
});

test('email template renders form title when available', function () {
    $form = createTestForm('titled_form');

    $data = [
        'fields' => [],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Test Form');
});

test('email template renders date and time', function () {
    $form = createTestForm('date_test_form');

    $testDate = now()->setDate(2024, 1, 15)->setTime(14, 30);

    $data = [
        'fields' => [],
        'date' => $testDate,
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('January 15, 2024')
        ->toContain('2:30 PM');
});

test('email template renders with textarea containing line breaks', function () {
    $form = createTestForm('message_form');

    $data = [
        'fields' => [
            [
                'display' => 'Message',
                'value' => "Line 1\nLine 2\nLine 3",
                'fieldtype' => 'textarea',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Message')
        ->toContain('Line 1');
});

test('email template renders with special characters in values', function () {
    $form = createTestForm('special_chars_form');

    $data = [
        'fields' => [
            [
                'display' => 'Company',
                'value' => 'Smith & Sons <Ltd>',
                'fieldtype' => 'text',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Company')
        ->toContain('Smith & Sons'); // Antlers auto-escapes by default
});

test('email template renders assets field with control panel link', function () {
    $form = createTestForm('upload_form');

    $data = [
        'fields' => [
            [
                'display' => 'Attachment',
                'value' => ['file1.pdf', 'file2.jpg'],
                'fieldtype' => 'assets',
            ],
        ],
        'date' => now(),
        'form' => $form,
        'id' => 'test-submission-123',
        'cp_url' => 'http://example.com',
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Attachment')
        ->toContain('Login to view file(s)')
        ->toContain('/cp/forms/');
});

test('email template renders with multiple field types', function () {
    $form = createTestForm('mixed_form');

    $data = [
        'fields' => [
            [
                'display' => 'Name',
                'value' => 'Alice Johnson',
                'fieldtype' => 'text',
            ],
            [
                'display' => 'Subscribe',
                'value' => true,
                'fieldtype' => 'toggle',
            ],
            [
                'display' => 'Interests',
                'value' => ['Reading', 'Writing'],
                'fieldtype' => 'checkboxes',
            ],
            [
                'display' => 'Comments',
                'value' => 'Looking forward to hearing from you.',
                'fieldtype' => 'textarea',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Name')
        ->toContain('Alice Johnson')
        ->toContain('Subscribe')
        ->toContain('Interests')
        ->toContain('Reading')
        ->toContain('Writing')
        ->toContain('Comments')
        ->toContain('Looking forward to hearing from you.');
});

test('email template handles empty array values', function () {
    $form = createTestForm('empty_array_form');

    $data = [
        'fields' => [
            [
                'display' => 'Options',
                'value' => [],
                'fieldtype' => 'checkboxes',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    // Empty arrays should not cause errors and field should not be shown
    expect($rendered)->not->toContain('Options');
});

test('email template renders with nested array values', function () {
    $form = createTestForm('nested_form');

    $data = [
        'fields' => [
            [
                'display' => 'Countries',
                'value' => [
                    ['label' => 'United States', 'value' => 'us'],
                    ['label' => 'Canada', 'value' => 'ca'],
                    ['label' => 'United Kingdom', 'value' => 'uk'],
                ],
                'fieldtype' => 'select',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Countries')
        ->toContain('United States')
        ->toContain('Canada')
        ->toContain('United Kingdom');
});

test('email template renders true boolean values', function () {
    $form = createTestForm('boolean_form');

    $data = [
        'fields' => [
            [
                'display' => 'Agree to Terms',
                'value' => true,
                'fieldtype' => 'toggle',
            ],
            [
                'display' => 'Newsletter',
                'value' => false,
                'fieldtype' => 'toggle',
            ],
            [
                'display' => 'Notifications',
                'value' => 'yes',
                'fieldtype' => 'toggle',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    // True values should be shown, false values are typically not shown since they're falsy
    expect($rendered)
        ->toContain('Agree to Terms')
        ->toContain('Notifications')
        ->toContain('yes');
});

test('email template is valid HTML', function () {
    $form = createTestForm('html_validation_form');

    $data = [
        'fields' => [
            [
                'display' => 'Test Field',
                'value' => 'Test Value',
                'fieldtype' => 'text',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('<!DOCTYPE')
        ->toContain('<html')
        ->toContain('</html>')
        ->toContain('<head>')
        ->toContain('</head>')
        ->toContain('<body')
        ->toContain('</body>');
});

test('email template uses table-based layout for email compatibility', function () {
    $form = createTestForm('layout_form');

    $data = [
        'fields' => [],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('<table')
        ->toContain('cellpadding')
        ->toContain('cellspacing');
});

test('email template renders values with key and label fallbacks', function () {
    $form = createTestForm('fallback_form');

    $data = [
        'fields' => [
            [
                'display' => 'Selection',
                'value' => [
                    ['label' => 'Option A', 'key' => 'opt_a'],
                    ['value' => 'opt_b', 'key' => 'opt_b'],
                    ['key' => 'opt_c'],
                ],
                'fieldtype' => 'checkboxes',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Selection')
        ->toContain('Option A');
});

test('email template handles long text values without breaking layout', function () {
    $form = createTestForm('long_text_form');

    $longText = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 50);

    $data = [
        'fields' => [
            [
                'display' => 'Description',
                'value' => $longText,
                'fieldtype' => 'textarea',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Description')
        ->toContain('word-break: break-word');
});

test('email template only shows fields with values', function () {
    $form = createTestForm('conditional_display_form');

    $data = [
        'fields' => [
            [
                'display' => 'Field With Value',
                'value' => 'I have a value',
                'fieldtype' => 'text',
            ],
            [
                'display' => 'Empty Field',
                'value' => '',
                'fieldtype' => 'text',
            ],
            [
                'display' => 'Null Field',
                'value' => null,
                'fieldtype' => 'text',
            ],
        ],
        'date' => now(),
        'form' => $form,
    ];

    $rendered = View::make('statamic-easy-forms::emails.form-submission', $data)->render();

    expect($rendered)
        ->toContain('Field With Value')
        ->toContain('I have a value')
        ->not->toContain('Empty Field')
        ->not->toContain('Null Field');
});
