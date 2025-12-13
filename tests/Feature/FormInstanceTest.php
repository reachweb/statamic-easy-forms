<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('form without instance parameter uses handle as form_id', function () {
    createTestForm('contact', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact');

    expect($output)
        ->toContain('id="contact_email"')
        ->toContain('for="contact_email"')
        ->toContain("formHandler('contact', 'contact',");
});

test('form with instance parameter appends instance to form_id', function () {
    createTestForm('contact', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact', ['instance' => 'header']);

    expect($output)
        ->toContain('id="contact_header_email"')
        ->toContain('for="contact_header_email"')
        ->toContain("formHandler('contact', 'contact_header',");
});

test('two instances of same form have unique IDs', function () {
    createTestForm('newsletter', [
        [
            'handle' => 'subscriber_email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
    ]);

    $headerOutput = renderEasyFormTag('newsletter', ['instance' => 'header']);
    $footerOutput = renderEasyFormTag('newsletter', ['instance' => 'footer']);

    // Header instance should have header-prefixed IDs
    expect($headerOutput)
        ->toContain('id="newsletter_header_subscriber_email"')
        ->toContain('for="newsletter_header_subscriber_email"')
        ->toContain("formHandler('newsletter', 'newsletter_header',");

    // Footer instance should have footer-prefixed IDs
    expect($footerOutput)
        ->toContain('id="newsletter_footer_subscriber_email"')
        ->toContain('for="newsletter_footer_subscriber_email"')
        ->toContain("formHandler('newsletter', 'newsletter_footer',");

    // They should be different
    expect($headerOutput)->not->toContain('id="newsletter_footer_subscriber_email"');
    expect($footerOutput)->not->toContain('id="newsletter_header_subscriber_email"');
});

test('instance parameter scopes honeypot field IDs', function () {
    createTestForm('contact', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact', ['instance' => 'sidebar']);

    // Honeypot should also be scoped with form_id
    expect($output)
        ->toContain('id="contact_sidebar_honeypot"')
        ->toContain('for="contact_sidebar_honeypot"');
});

test('instance parameter scopes aria-describedby attributes', function () {
    createTestForm('feedback', [
        [
            'handle' => 'message',
            'field' => [
                'type' => 'textarea',
                'display' => 'Message',
                'instructions' => 'Please provide your feedback',
            ],
        ],
    ]);

    $output = renderEasyFormTag('feedback', ['instance' => 'modal']);

    expect($output)
        ->toContain('aria-describedby="feedback_modal_message-description"')
        ->toContain('id="feedback_modal_message-description"');
});

test('instance parameter works with toggle fields', function () {
    createTestForm('preferences', [
        [
            'handle' => 'notifications',
            'field' => [
                'type' => 'toggle',
                'display' => 'Enable notifications',
                'inline_label' => 'Enable notifications',
            ],
        ],
    ]);

    $output = renderEasyFormTag('preferences', ['instance' => 'settings']);

    expect($output)
        ->toContain('id="preferences_settings_notifications"')
        ->toContain('for="preferences_settings_notifications"')
        ->toContain('id="preferences_settings_notifications-label"');
});

test('instance parameter works with radio fields', function () {
    createTestForm('survey', [
        [
            'handle' => 'rating',
            'field' => [
                'type' => 'radio',
                'display' => 'Rating',
                'options' => [
                    'good' => 'Good',
                    'bad' => 'Bad',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('survey', ['instance' => 'popup']);

    expect($output)
        ->toContain('id="survey_popup_rating_good"')
        ->toContain('for="survey_popup_rating_good"')
        ->toContain('id="survey_popup_rating_bad"')
        ->toContain('for="survey_popup_rating_bad"');
});

test('instance parameter works with checkboxes fields', function () {
    createTestForm('interests', [
        [
            'handle' => 'topics',
            'field' => [
                'type' => 'checkboxes',
                'display' => 'Topics',
                'options' => [
                    'tech' => 'Technology',
                    'sports' => 'Sports',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('interests', ['instance' => 'widget']);

    expect($output)
        ->toContain('id="interests_widget_topics_tech"')
        ->toContain('for="interests_widget_topics_tech"')
        ->toContain('id="interests_widget_topics_sports"')
        ->toContain('for="interests_widget_topics_sports"');
});

test('instance parameter works with select fields', function () {
    createTestForm('location', [
        [
            'handle' => 'country',
            'field' => [
                'type' => 'select',
                'display' => 'Country',
                'options' => [
                    'us' => 'United States',
                    'uk' => 'United Kingdom',
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('location', ['instance' => 'checkout']);

    expect($output)
        ->toContain('id="location_checkout_country"')
        ->toContain('for="location_checkout_country"');
});

test('instance parameter works with integer counter fields', function () {
    createTestForm('order', [
        [
            'handle' => 'quantity',
            'field' => [
                'type' => 'integer',
                'display' => 'Quantity',
                'integer_template' => 'counter',
                'min_value' => 1,
                'max_value' => 10,
            ],
        ],
    ]);

    $output = renderEasyFormTag('order', ['instance' => 'cart']);

    expect($output)
        ->toContain('id="order_cart_quantity"')
        ->toContain('aria-labelledby="order_cart_quantity-label"');
});

test('instance parameter works with integer rating fields', function () {
    createTestForm('review', [
        [
            'handle' => 'stars',
            'field' => [
                'type' => 'integer',
                'display' => 'Stars',
                'integer_template' => 'rating',
                'min_value' => 1,
                'max_value' => 5,
            ],
        ],
    ]);

    $output = renderEasyFormTag('review', ['instance' => 'product']);

    // Rating uses dynamic Alpine.js :id binding with form_id prefix
    expect($output)
        ->toContain(":id=\"'review_product_stars-star-' + star\"")
        ->toContain(":for=\"'review_product_stars-star-' + star\"");
});

test('instance parameter works with date fields', function () {
    createTestForm('booking', [
        [
            'handle' => 'check_in',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Check-in Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('booking', ['instance' => 'reservation']);

    // Date field has id on the input element
    expect($output)
        ->toContain('id="booking_reservation_check_in"')
        ->toContain('for="booking_reservation_check_in"');
});

test('instance parameter works with time fields', function () {
    createTestForm('appointment', [
        [
            'handle' => 'preferred_time',
            'field' => [
                'type' => 'text',
                'input_type' => 'time',
                'improved_field' => true,
                'display' => 'Preferred Time',
            ],
        ],
    ]);

    $output = renderEasyFormTag('appointment', ['instance' => 'scheduler']);

    expect($output)
        ->toContain('id="appointment_scheduler_preferred_time"')
        ->toContain('for="appointment_scheduler_preferred_time"');
});

test('instance parameter works with files fields', function () {
    createTestForm('upload', [
        [
            'handle' => 'document',
            'field' => [
                'type' => 'files',
                'display' => 'Document',
            ],
        ],
    ]);

    $output = renderEasyFormTag('upload', ['instance' => 'attachment']);

    expect($output)
        ->toContain('id="upload_attachment_document"')
        ->toContain('for="upload_attachment_document"');
});

test('instance parameter works with assets fields', function () {
    createTestForm('gallery', [
        [
            'handle' => 'images',
            'field' => [
                'type' => 'assets',
                'display' => 'Images',
            ],
        ],
    ]);

    $output = renderEasyFormTag('gallery', ['instance' => 'uploader']);

    expect($output)
        ->toContain('id="gallery_uploader_images"');
});

test('name attributes remain unchanged with instance parameter', function () {
    createTestForm('contact', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
        [
            'handle' => 'message',
            'field' => [
                'type' => 'textarea',
                'display' => 'Message',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact', ['instance' => 'footer']);

    // name attributes should NOT include the instance - they're used for form submission
    expect($output)
        ->toContain('name="email"')
        ->toContain('name="message"')
        ->not->toContain('name="contact_footer_email"')
        ->not->toContain('name="footer_email"');
});

test('x-model bindings remain unchanged with instance parameter', function () {
    createTestForm('contact', [
        [
            'handle' => 'email',
            'field' => [
                'type' => 'text',
                'display' => 'Email',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact', ['instance' => 'sidebar']);

    // x-model should use the field handle, not the instance-prefixed ID
    expect($output)
        ->toContain("x-model=\"submitFields['email']\"")
        ->not->toContain("x-model=\"submitFields['contact_sidebar_email']\"");
});

test('form action URL remains unchanged with instance parameter', function () {
    createTestForm('contact', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
            ],
        ],
    ]);

    $output = renderEasyFormTag('contact', ['instance' => 'popup']);

    // Action URL should use the original form handle
    expect($output)->toContain('action="http://localhost/!/forms/contact"');
});
