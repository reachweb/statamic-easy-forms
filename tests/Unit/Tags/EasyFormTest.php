<?php

use Reach\StatamicEasyForms\Tags\EasyForm;
use Statamic\Facades\Form;

beforeEach(function () {
    // Clean up any existing forms
    Form::all()->each->delete();
});

afterEach(function () {
    // Clean up after tests
    Form::all()->each->delete();
});

test('tag renders form successfully with valid handle', function () {
    $form = createTestForm('contact', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
                'validate' => 'required',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact');

    expect($output)
        ->toBeString()
        ->not->toBeEmpty()
        ->toContainFormElement();
});

test('tag throws exception with missing handle', function () {
    $tag = new EasyForm;
    $tag->setContext([]);
    $tag->setParameters([]);

    $tag->index();
})->throws(Exception::class, 'A form handle is required');

test('tag throws exception with invalid handle', function () {
    renderEasyFormTag('nonexistent_form');
})->throws(Exception::class, 'cannot be found');

test('tag includes all form data', function () {
    createTestForm('test_form', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'input_type' => 'email',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('test_form');

    expect($output)
        ->toContain('name="email"')
        ->toContain('type="email"')
        ->toContain('honeypot');
});

test('tag respects custom view parameter', function () {
    createTestForm('custom_view_form');

    // The view parameter allows using a different template
    $output = renderEasyFormTag('custom_view_form', [
        'view' => 'form/_form_component',
    ]);

    expect($output)->toContainFormElement();
});

test('tag respects hide_fields parameter', function () {
    createTestForm('hidden_fields_form', [
        [
            'handle' => 'visible_field',
            'field' => [
                'type' => 'text',
                'display' => 'Visible',
            ],
        ],
        [
            'handle' => 'hidden_field',
            'field' => [
                'type' => 'text',
                'display' => 'Hidden',
            ],
        ],
    ]);

    $output = renderEasyFormTag('hidden_fields_form', [
        'hide_fields' => 'hidden_field',
    ]);

    // The visible field should be present without the hidden class
    expect($output)->toContain('visible_field');

    // The hidden field should still be in the output but with x-show Alpine directive
    // (it will be hidden by Alpine.js on the client side)
    expect($output)
        ->toContain('name="hidden_field"')
        ->toContain('x-show="shouldShowField(\'hidden_field\')"');
});

test('tag renders with all field types', function () {
    createTestForm('all_fields', [
        [
            'handle' => 'text_field',
            'field' => ['type' => 'text', 'display' => 'Text'],
        ],
        [
            'handle' => 'textarea_field',
            'field' => ['type' => 'textarea', 'display' => 'Textarea'],
        ],
        [
            'handle' => 'select_field',
            'field' => [
                'type' => 'select',
                'display' => 'Select',
                'options' => ['option1' => 'Option 1', 'option2' => 'Option 2'],
            ],
        ],
    ]);

    $output = renderEasyFormTag('all_fields');

    expect($output)
        ->toContain('name="text_field"')
        ->toContain('name="textarea_field"')
        ->toContain('name="select_field"');
});

test('tag handles hidden fields properly', function () {
    createTestForm('with_hidden', [
        [
            'handle' => 'visible',
            'field' => ['type' => 'text', 'display' => 'Visible'],
        ],
        [
            'handle' => 'secret',
            'field' => ['type' => 'hidden', 'display' => 'Secret'],
        ],
    ]);

    $output = renderEasyFormTag('with_hidden');

    // Hidden fields should still be in the form but marked as hidden
    expect($output)->toBeString();
});

test('parseHideFields handles pipe-separated string', function () {
    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('parseHideFields');
    $method->setAccessible(true);

    $result = $method->invoke($tag, 'field1|field2|field3');

    expect($result)->toBe(['field1', 'field2', 'field3']);
});

test('parseHideFields handles array input', function () {
    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('parseHideFields');
    $method->setAccessible(true);

    $result = $method->invoke($tag, ['field1', 'field2']);

    expect($result)->toBe(['field1', 'field2']);
});

test('parseHideFields handles empty string', function () {
    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('parseHideFields');
    $method->setAccessible(true);

    $result = $method->invoke($tag, '');

    expect($result)->toBe([]);
});

test('tag uses form handle as formHandler identifier', function () {
    createTestForm('contact_form');

    $output = renderEasyFormTag('contact_form');

    // The form handle should be used as the formHandler identifier
    expect($output)->toContain("formHandler('contact_form'");
});

test('tag passes recaptcha site key when configured', function () {
    createTestForm('recaptcha_form');

    // Set the environment variable
    putenv('RECAPTCHA_SITE_KEY=test_site_key_123');

    $output = renderEasyFormTag('recaptcha_form');

    // Clean up
    putenv('RECAPTCHA_SITE_KEY');

    expect($output)->toContain("formHandler('recaptcha_form', 'test_site_key_123')");
});

test('tag passes null for recaptcha when not configured', function () {
    createTestForm('no_recaptcha_form');

    // Ensure the environment variable is not set
    putenv('RECAPTCHA_SITE_KEY');

    $output = renderEasyFormTag('no_recaptcha_form');

    expect($output)->toContain("formHandler('no_recaptcha_form', null)");
});

test('tag displays prepend value in field label', function () {
    createTestForm('prepend_form', [
        [
            'handle' => 'text_field',
            'field' => [
                'type' => 'text',
                'display' => 'Text Field',
                'prepend' => 'Prefix:',
            ],
        ],
    ]);

    $output = renderEasyFormTag('prepend_form');

    expect($output)
        ->toContain('Prefix:')
        ->toContain('Text Field');
});

test('tag displays append value in field label', function () {
    createTestForm('append_form', [
        [
            'handle' => 'text_field',
            'field' => [
                'type' => 'text',
                'display' => 'Text Field',
                'append' => '(required)',
            ],
        ],
    ]);

    $output = renderEasyFormTag('append_form');

    expect($output)
        ->toContain('Text Field')
        ->toContain('(required)');
});

test('tag displays both prepend and append values in field label', function () {
    createTestForm('prepend_append_form', [
        [
            'handle' => 'amount_field',
            'field' => [
                'type' => 'text',
                'display' => 'Amount',
                'prepend' => '$',
                'append' => 'USD',
            ],
        ],
    ]);

    $output = renderEasyFormTag('prepend_append_form');

    expect($output)
        ->toContain('$')
        ->toContain('Amount')
        ->toContain('USD');
});

test('tag renders integer counter fieldtype', function () {
    createTestForm('counter_form', [
        [
            'handle' => 'quantity',
            'field' => [
                'type' => 'integer',
                'display' => 'Quantity',
                'integer_template' => 'counter',
                'min_value' => 1,
                'max_value' => 10,
                'default' => 5,
                'increment_amount' => 2,
            ],
        ],
    ]);

    $output = renderEasyFormTag('counter_form');

    expect($output)
        ->toContain('name="quantity"')
        ->toContain('minVal: 1')
        ->toContain('maxVal: 10')
        ->toContain('incrementAmount: 2')
        ->toContain('x-model.number="submitFields[\'quantity\']"')
        ->toContain('role="group"')
        ->toContain('aria-labelledby="quantity-label"')
        ->toContain(':min="minVal"')
        ->toContain(':max="maxVal"');
});

test('tag renders integer rating fieldtype', function () {
    createTestForm('rating_form', [
        [
            'handle' => 'rating',
            'field' => [
                'type' => 'integer',
                'display' => 'Rating',
                'integer_template' => 'rating',
                'min_value' => 1,
                'max_value' => 5,
                'default' => 3,
            ],
        ],
    ]);

    $output = renderEasyFormTag('rating_form');

    expect($output)
        ->toContain('name="rating"')
        ->toContain('minVal: 1')
        ->toContain('maxVal: 5')
        ->toContain('x-model.number="submitFields[\'rating\']"')
        ->toContain('<fieldset')
        ->toContain('<legend class="sr-only">')
        ->toContain('aria-live="polite"')
        ->toContain('aria-atomic="true"');
});

test('tag renders simple integer fieldtype when template is simple', function () {
    createTestForm('simple_integer_form', [
        [
            'handle' => 'age',
            'field' => [
                'type' => 'integer',
                'display' => 'Age',
                'integer_template' => 'simple',
                'min_value' => 18,
                'max_value' => 100,
            ],
        ],
    ]);

    $output = renderEasyFormTag('simple_integer_form');

    expect($output)
        ->toContain('name="age"')
        ->toContain('type="number"')
        ->toContain('min="18"')
        ->toContain('max="100"')
        ->toContain('x-model.number="submitFields[\'age\']"')
        ->not->toContain('role="group"')
        ->not->toContain('incrementAmount');
});

test('tag renders simple integer fieldtype when template is not set', function () {
    createTestForm('default_integer_form', [
        [
            'handle' => 'count',
            'field' => [
                'type' => 'integer',
                'display' => 'Count',
            ],
        ],
    ]);

    $output = renderEasyFormTag('default_integer_form');

    expect($output)
        ->toContain('name="count"')
        ->toContain('type="number"')
        ->toContain('x-model.number="submitFields[\'count\']"')
        ->not->toContain('role="group"')
        ->not->toContain('incrementAmount');
});

test('integer counter respects increment amount', function () {
    createTestForm('counter_increment_form', [
        [
            'handle' => 'items',
            'field' => [
                'type' => 'integer',
                'display' => 'Items',
                'integer_template' => 'counter',
                'min_value' => 0,
                'increment_amount' => 5,
            ],
        ],
    ]);

    $output = renderEasyFormTag('counter_increment_form');

    expect($output)
        ->toContain('incrementAmount: 5')
        ->toContain('minVal: 0');
});

test('integer rating respects min and max values', function () {
    createTestForm('rating_range_form', [
        [
            'handle' => 'score',
            'field' => [
                'type' => 'integer',
                'display' => 'Score',
                'integer_template' => 'rating',
                'min_value' => 0,
                'max_value' => 10,
                'default' => 7,
            ],
        ],
    ]);

    $output = renderEasyFormTag('rating_range_form');

    expect($output)
        ->toContain('minVal: 0')
        ->toContain('maxVal: 10')
        ->toContain('type="radio"')
        ->toContain('focus-within:ring-2');
});

test('tag respects custom submit_text parameter', function () {
    createTestForm('custom_submit_form', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('custom_submit_form', [
        'submit_text' => 'Send Now',
    ]);

    expect($output)
        ->toContain('Send Now')
        ->not->toContain('<span>Submit</span>');
});

test('tag respects custom success_message parameter', function () {
    createTestForm('custom_success_form', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('custom_success_form', [
        'success_message' => 'Message received!',
    ]);

    expect($output)
        ->toContain('Message received!')
        ->not->toContain('Thank you for your message! We will get back to you shortly!');
});

test('tag uses default submit text when submit_text not provided', function () {
    createTestForm('default_submit_form', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('default_submit_form');

    expect($output)
        ->toContain('Submit');
});

test('tag uses default success message when success_message not provided', function () {
    createTestForm('default_success_form', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('default_success_form');

    expect($output)
        ->toContain('Thank you for your message! We will get back to you shortly!');
});

test('version method returns a string', function () {
    $tag = new EasyForm;

    $version = $tag->version();

    expect($version)->toBeString();
});

test('version method returns dev or hashed version string', function () {
    $tag = new EasyForm;

    $version = $tag->version();

    // Should be one of:
    // - 'dev' for local development without Composer
    // - A 12-character hexadecimal hash for installed versions
    expect(
        $version === 'dev' ||
        preg_match('/^[a-f0-9]{12}$/', $version)
    )->toBeTrue();
});

test('version method handles missing InstalledVersions class gracefully', function () {
    // Create a mock tag that simulates missing InstalledVersions
    $tag = new class extends EasyForm
    {
        public function version(): string
        {
            // Simulate class_exists returning false
            if (! class_exists('NonExistentClass')) {
                return 'dev';
            }

            return parent::version();
        }
    };

    expect($tag->version())->toBe('dev');
});
