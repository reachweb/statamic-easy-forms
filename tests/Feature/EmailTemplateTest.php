<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Reach\StatamicEasyForms\EmailTemplate;
use Reach\StatamicEasyForms\ServiceProvider;
use Statamic\Contracts\Forms\Submission;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Form;
use Statamic\Facades\FormConnector;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\User;
use Statamic\Forms\Email;
use Statamic\Forms\SubmissionMentions;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
    AssetContainer::all()->each->delete();
    File::deleteDirectory(resource_path('views/vendor/statamic-easy-forms'));
});

/**
 * Render the email template with hand-built view data. Statamic always passes
 * the fields grouped in sections too, so add a section without a heading.
 */
function renderEmailTemplate(array $data): string
{
    $data['sections'] ??= [['display' => null, 'instructions' => null, 'fields' => $data['fields'] ?? []]];

    return View::make('statamic-easy-forms::emails.form-submission', $data)->render();
}

/**
 * Render the email template through Statamic's mailable, as an email connection
 * would send it. Whitespace is collapsed to keep the assertions readable.
 */
function renderSubmissionEmail(Submission $submission, array $config = []): string
{
    $email = new Email($submission, array_merge([
        'to' => 'team@example.com',
        'html' => 'statamic-easy-forms::emails.form-submission',
    ], $config), Site::default());

    return preg_replace('/\s+/', ' ', $email->render());
}

/**
 * Create an asset container for stored uploads, keeping its YAML out of the addon.
 */
function createUploadsContainer(): void
{
    Storage::fake('uploads');
    Stache::store('asset-containers')->directory(storage_path('framework/testing/asset-containers'));
    AssetContainer::make('uploads')->disk('uploads')->save();
}

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

    expect($rendered)
        ->toContain('Message')
        ->toContain('Line 1');
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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

    // True values are shown as "Yes", false values are not shown since they're falsy
    expect($rendered)
        ->toContain('Agree to Terms')
        ->toContain('Notifications')
        ->toContain('Yes')
        ->not->toContain('Newsletter');
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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

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

    $rendered = renderEmailTemplate($data);

    expect($rendered)
        ->toContain('Field With Value')
        ->toContain('I have a value')
        ->not->toContain('Empty Field')
        ->not->toContain('Null Field');
});

/*
|--------------------------------------------------------------------------
| Forms 2 email connections
|--------------------------------------------------------------------------
|
| These tests send the template through Statamic's mailable, so the view gets
| the same data as an email connection: augmented values, sections and the
| connection's config as email_config.
|
*/

test('email template shows the connection message above the answers', function () {
    $form = createTestForm('message_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
    ]);
    $submission = $form->makeSubmission()->data(['name' => 'Jane Doe']);

    $html = renderSubmissionEmail($submission, [
        'subject' => 'New enquiry from [[ name ]]',
        'body' => 'Please reply within one business day.',
    ]);

    expect($html)
        ->toContain('Please reply within one business day.')
        ->toContain('Test Form')
        ->not->toContain('New Submission')
        ->not->toContain('[[')
        ->and(strpos($html, 'Please reply'))->toBeLessThan(strpos($html, 'Jane Doe'));
});

test('email template keeps the default heading without a connection message', function () {
    $form = createTestForm('no_message_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
    ]);
    $submission = $form->makeSubmission()->data(['name' => 'Jane Doe']);

    expect(renderSubmissionEmail($submission))
        ->toContain('New Submission')
        ->toContain('Test Form')
        ->toContain('Jane Doe')
        ->not->toContain('class="ef-message"');
});

test('email template renders the connection message as markdown with mentions', function () {
    $form = createTestForm('mention_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
    ]);
    $submission = $form->makeSubmission()->data(['name' => '<b>Jane</b>']);

    expect(renderSubmissionEmail($submission, ['body' => 'Thanks **[[ name ]]**']))
        ->toContain('<p>Thanks <strong>&lt;b&gt;Jane&lt;/b&gt;</strong></p>');
})->skip(! class_exists(SubmissionMentions::class), 'Needs the Message mentions from statamic/cms#15647');

test('email template escapes submitted values', function () {
    $form = createTestForm('escape_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'message', 'field' => ['type' => 'long_answer', 'display' => 'Message']],
    ]);
    $submission = $form->makeSubmission()->data([
        'name' => '<script>alert(1)</script>',
        'message' => "First line\nSecond line",
    ]);

    expect(renderSubmissionEmail($submission))
        ->not->toContain('<script>alert(1)</script>')
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->toContain('First line<br /> Second line');
});

test('email template escapes rating and ranking answers, which Statamic stores as posted', function () {
    $form = createTestForm('escape_scales_form', [
        ['handle' => 'stars', 'field' => ['type' => 'star_rating', 'display' => 'Rating']],
        ['handle' => 'nps', 'field' => ['type' => 'opinion_scale', 'display' => 'How likely', 'min' => 0, 'max' => 10]],
        ['handle' => 'seasons', 'field' => ['type' => 'ranking', 'display' => 'Rank the seasons', 'options' => [
            ['key' => 'winter', 'value' => 'Winter'],
            ['key' => 'spring', 'value' => 'Spring'],
        ]]],
    ]);
    $submission = $form->makeSubmission()->data([
        'stars' => '<a href="https://evil.example/login">Verify</a>',
        'nps' => '<img src="https://evil.example/pixel.gif">',
        // The keys of a posted array are kept too
        'seasons' => ['<a href="https://evil.example/claim">Claim</a>' => 'winter', 'x' => 'spring'],
    ]);

    expect(renderSubmissionEmail($submission))
        ->not->toContain('<a href="https://evil.example')
        ->not->toContain('<img src="https://evil.example')
        ->not->toContain('Claim')
        ->toContain('&lt;a href=&quot;https://evil.example/login&quot;&gt;Verify&lt;/a&gt; / 5')
        ->toContain('&lt;img src=&quot;https://evil.example/pixel.gif&quot;&gt; / 10')
        ->toContain('1. Winter<br /> 2. Spring');
});

test('email template lists the answers when a field is named sections', function () {
    $form = createTestForm('sections_handle_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'sections', 'field' => ['type' => 'short_answer', 'display' => 'Sections']],
    ]);
    $submission = $form->makeSubmission()->data(['name' => 'Jane Doe', 'sections' => 'Kitchen']);

    expect(renderSubmissionEmail($submission))
        ->toContain('Jane Doe')
        ->toContain('Sections </p>')
        ->toContain('Kitchen');
});

test('email template shows human friendly values for choice fields', function () {
    $form = createTestForm('choices_form', [
        ['handle' => 'recommend', 'field' => ['type' => 'yes_no', 'display' => 'Would you recommend us?']],
        ['handle' => 'seasons', 'field' => ['type' => 'ranking', 'display' => 'Rank the seasons', 'options' => [
            ['key' => 'spring', 'value' => 'Spring'],
            ['key' => 'summer', 'value' => 'Summer'],
            ['key' => 'winter', 'value' => 'Winter'],
        ]]],
        ['handle' => 'pet', 'field' => ['type' => 'image_choice', 'display' => 'Favourite pet', 'options' => [
            ['key' => 'cat', 'label' => 'Cat'],
            ['key' => 'dog', 'label' => 'Dog'],
        ]]],
        ['handle' => 'pets', 'field' => ['type' => 'image_choice', 'display' => 'All pets', 'multiple' => true, 'options' => [
            ['key' => 'cat', 'label' => 'Cat'],
            ['key' => 'dog', 'label' => 'Dog'],
            ['key' => 'fish'],
        ]]],
        ['handle' => 'interests', 'field' => ['type' => 'checkboxes', 'display' => 'Interests', 'options' => [
            ['key' => 'music', 'value' => 'Music & Arts'],
            ['key' => 'sports', 'value' => 'Sports'],
        ]]],
        ['handle' => 'contact', 'field' => ['type' => 'multi_choice', 'display' => 'Contact method', 'options' => [
            ['key' => 'phone', 'value' => 'By phone'],
        ]]],
        ['handle' => 'subscribe', 'field' => ['type' => 'toggle', 'display' => 'Subscribe']],
    ]);
    $submission = $form->makeSubmission()->data([
        'recommend' => 'no',
        'seasons' => ['winter', 'spring', 'summer'],
        'pet' => 'dog',
        'pets' => ['cat', 'fish'],
        'interests' => ['music', 'sports'],
        'contact' => 'phone',
        'subscribe' => true,
    ]);

    expect(renderSubmissionEmail($submission))
        ->toContain('Would you recommend us? </p>')
        ->toContain('> No </div>')
        ->toContain('1. Winter<br /> 2. Spring<br /> 3. Summer')
        ->toContain('> Dog </div>')
        ->toContain('Cat, fish')
        ->toContain('Music &amp; Arts, Sports')
        ->toContain('By phone')
        ->toContain('> Yes </div>')
        ->not->toContain('winter')
        ->not->toContain('music');
});

test('email template shows human friendly values for number fields', function () {
    $form = createTestForm('numbers_form', [
        ['handle' => 'stars', 'field' => ['type' => 'star_rating', 'display' => 'Rating']],
        ['handle' => 'nps', 'field' => ['type' => 'opinion_scale', 'display' => 'How likely', 'min' => 0, 'max' => 10]],
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => 'EUR']],
        ['handle' => 'guests', 'field' => ['type' => 'number', 'display' => 'Guests']],
    ]);
    $submission = $form->makeSubmission()->data([
        'stars' => 4,
        'nps' => 0,
        'budget' => 1500,
        'guests' => 3,
    ]);

    expect(renderSubmissionEmail($submission))
        ->toContain('4 / 5')
        ->toContain('How likely')
        ->toContain('0 / 10')
        ->toContain('€1,500')
        ->toContain('> 3 </div>');
});

test('email template shows human friendly values for date, time and dictionary fields', function () {
    $form = createTestForm('dates_form', [
        ['handle' => 'arrival', 'field' => ['type' => 'date_picker', 'display' => 'Arrival']],
        ['handle' => 'lunch', 'field' => ['type' => 'time_picker', 'display' => 'Lunch']],
        ['handle' => 'stay', 'field' => ['type' => 'date', 'display' => 'Stay', 'mode' => 'range']],
        ['handle' => 'country', 'field' => ['type' => 'dictionary', 'display' => 'Country', 'dictionary' => 'countries', 'max_items' => 1]],
        ['handle' => 'visited', 'field' => ['type' => 'dictionary', 'display' => 'Visited', 'dictionary' => 'countries']],
        ['handle' => 'skipped', 'field' => ['type' => 'dictionary', 'display' => 'Skipped country', 'dictionary' => 'countries', 'max_items' => 1]],
    ]);
    $submission = $form->makeSubmission()->data([
        'arrival' => '2026-03-14',
        'lunch' => '12:30',
        'stay' => ['start' => '2026-03-14', 'end' => '2026-03-18'],
        'country' => 'GRC',
        'visited' => ['CYP', 'ITA'],
    ]);

    expect(renderSubmissionEmail($submission))
        ->toContain('> March 14, 2026 </div>')
        ->toContain('12:30')
        ->toContain('March 14, 2026 – March 18, 2026')
        ->toContain('Greece')
        ->toContain('Cyprus')
        ->toContain('Italy')
        ->not->toContain('GRC')
        ->not->toContain('Skipped country');
});

test('email template renders grid rows', function () {
    $form = createTestForm('grid_form', [
        ['handle' => 'guests', 'field' => ['type' => 'grid', 'display' => 'Guests', 'fields' => [
            ['handle' => 'guest_name', 'field' => ['type' => 'text', 'display' => 'Guest name']],
        ]]],
    ]);
    $submission = $form->makeSubmission()->data([
        'guests' => [['guest_name' => 'Ann'], ['guest_name' => 'Bob']],
    ]);

    expect(renderSubmissionEmail($submission))
        ->toContain('Guests')
        ->toContain('Guest name:</span> Ann')
        ->toContain('Guest name:</span> Bob');
});

test('email template renders grid choices, toggles and zero answers readably', function () {
    $form = createTestForm('grid_values_form', [
        ['handle' => 'attendees', 'field' => ['type' => 'grid', 'display' => 'Attendees', 'fields' => [
            ['handle' => 'diet', 'field' => ['type' => 'select', 'display' => 'Diet', 'options' => ['vegan' => 'Vegan diet', 'none' => 'No preference']]],
            ['handle' => 'parking', 'field' => ['type' => 'toggle', 'display' => 'Needs parking']],
            ['handle' => 'children', 'field' => ['type' => 'integer', 'display' => 'Children']],
        ]]],
    ]);
    $submission = $form->makeSubmission()->data([
        'attendees' => [['diet' => 'vegan', 'parking' => true, 'children' => 0]],
    ]);

    expect(renderSubmissionEmail($submission))
        ->toContain('Diet:</span> Vegan diet </p>')
        ->not->toContain('vegan, Vegan diet')
        ->toContain('Needs parking:</span> Yes')
        ->toContain('Children:</span> 0');
});

test('email template renders section headings for sections with answers', function () {
    $form = createFormWithSections('sections_form', [
        ['display' => 'About you', 'fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]],
        ['display' => 'Optional extras', 'fields' => [
            ['handle' => 'notes', 'field' => ['type' => 'long_answer', 'display' => 'Notes']],
        ]],
        ['fields' => [
            ['handle' => 'company', 'field' => ['type' => 'short_answer', 'display' => 'Company']],
        ]],
    ]);
    $submission = $form->makeSubmission()->data(['name' => 'Jane Doe', 'company' => 'Acme']);

    $html = renderSubmissionEmail($submission);

    expect($html)
        ->toContain('About you')
        ->not->toContain('Optional extras')
        ->toContain('Acme')
        ->and(strpos($html, 'About you'))->toBeLessThan(strpos($html, 'Jane Doe'));
});

test('email template lists stored uploads with a link to the submission in the control panel', function () {
    createUploadsContainer();
    Storage::disk('uploads')->put('submissions/photo.jpg', 'photo');
    Storage::disk('uploads')->put('submissions/cv.pdf', 'cv');

    $form = createTestForm('stored_upload_form', [
        ['handle' => 'photos', 'field' => ['type' => 'upload', 'display' => 'Photos', 'store' => true, 'container' => 'uploads']],
        ['handle' => 'cv', 'field' => ['type' => 'upload', 'display' => 'CV', 'store' => true, 'container' => 'uploads', 'max_files' => 1]],
        ['handle' => 'portfolio', 'field' => ['type' => 'upload', 'display' => 'Portfolio', 'store' => true, 'container' => 'uploads']],
    ]);
    $submission = $form->makeSubmission()->data([
        'photos' => ['submissions/photo.jpg', 'submissions/cv.pdf'],
        'cv' => 'submissions/cv.pdf',
    ]);

    $url = cp_route('forms.submissions.show', [$form->handle(), $submission->id()]);

    expect(renderSubmissionEmail($submission))
        ->toContain('photo.jpg, cv.pdf')
        ->toContain('> cv.pdf <br />')
        ->toContain('href="'.$url.'"')
        ->toContain('Login to view file(s)')
        ->not->toContain('Portfolio')
        ->and($url)->toEndWith('/cp/forms/stored_upload_form/submissions/'.$submission->id());

    // With a Message, the email may be a confirmation to the visitor
    expect(renderSubmissionEmail($submission, ['body' => 'Thanks for your photos.']))
        ->toContain('photo.jpg, cv.pdf')
        ->not->toContain('Login to view file(s)')
        ->not->toContain($url);
});

test('email template lists uploads that are not stored by file name only', function () {
    $form = createTestForm('file_upload_form', [
        ['handle' => 'resume', 'field' => ['type' => 'upload', 'display' => 'Resume', 'store' => false]],
        ['handle' => 'cover', 'field' => ['type' => 'upload', 'display' => 'Cover letter', 'store' => false, 'max_files' => 1]],
    ]);
    $submission = $form->makeSubmission()->data([
        'resume' => ['1700000000.1234/resume.pdf', '1700000000.1234/references.pdf'],
        'cover' => '1700000000.1234/cover.pdf',
    ]);

    expect(renderSubmissionEmail($submission))
        ->toContain('resume.pdf, references.pdf')
        ->toContain('cover.pdf')
        ->not->toContain('1700000000.1234/')
        ->not->toContain('Login to view file(s)')
        ->not->toContain('Attached to this email');
});

test('email template lists attached uploads when attachments are enabled', function () {
    createUploadsContainer();
    Storage::disk('uploads')->put('submissions/photo.jpg', 'photo');

    $form = createTestForm('attachment_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'photo', 'field' => ['type' => 'upload', 'display' => 'Photo', 'store' => true, 'container' => 'uploads', 'max_files' => 1]],
        ['handle' => 'resume', 'field' => ['type' => 'upload', 'display' => 'Resume', 'store' => false]],
        ['handle' => 'portfolio', 'field' => ['type' => 'upload', 'display' => 'Portfolio', 'store' => false]],
    ]);
    $submission = $form->makeSubmission()->data([
        'name' => 'Jane Doe',
        'photo' => 'submissions/photo.jpg',
        'resume' => ['1700000000.1234/resume.pdf'],
    ]);

    $html = renderSubmissionEmail($submission, ['attachments' => true]);

    expect($html)
        ->toContain('Photo')
        ->toContain('photo.jpg')
        ->toContain('Resume')
        ->toContain('resume.pdf')
        ->not->toContain('Portfolio')
        ->not->toContain('Login to view file(s)')
        ->and(substr_count($html, 'Attached to this email'))->toBe(2);
});

test('email connections accept the email view that the installer recommends', function () {
    $form = createTestForm('connection_form');
    $rules = FormConnector::find('email')->setForm($form)->rules();
    $validates = fn (string $view) => Validator::make([['to' => ['team@example.com'], 'html' => $view]], $rules)->passes();

    expect(EmailTemplate::view())->toBe('vendor/statamic-easy-forms/emails/form-submission')
        ->and(EmailTemplate::path())->toBe(resource_path('views/vendor/statamic-easy-forms/emails/form-submission.antlers.html'))
        ->and($validates(EmailTemplate::view()))->toBeTrue()
        ->and($validates('statamic-easy-forms::emails.form-submission'))->toBeFalse()
        ->and(ServiceProvider::pathsToPublish(ServiceProvider::class, 'easy-forms-emails'))->toContain(EmailTemplate::path());
});

test('email connections accept the recommended email view inside the email view folder', function () {
    config(['statamic.forms.email_view_folder' => 'emails']);

    $form = createTestForm('folder_connection_form');
    $rules = FormConnector::find('email')->setForm($form)->rules();
    $validates = fn (string $view) => Validator::make([['to' => ['team@example.com'], 'html' => $view]], $rules)->passes();

    expect(EmailTemplate::view())->toBe('emails/easy-forms-submission')
        ->and(EmailTemplate::path())->toBe(resource_path('views/emails/easy-forms-submission.antlers.html'))
        ->and($validates(EmailTemplate::view()))->toBeTrue()
        ->and($validates('vendor/statamic-easy-forms/emails/form-submission'))->toBeFalse();
});

test('control panel previews email connections that use the published template', function () {
    $this->artisan('vendor:publish', ['--tag' => 'easy-forms-emails']);

    Stache::store('users')->directory(storage_path('framework/testing/users'));
    $user = tap(User::make()->email('admin@example.com')->makeSuper())->save();

    $form = createTestForm('preview_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'recommend', 'field' => ['type' => 'yes_no', 'display' => 'Would you recommend us?']],
    ]);
    $preview = fn (string $view) => $this->actingAs($user)->postJson(cp_route('forms.connect.email.preview', $form->handle()), [
        'id' => 'preview',
        'to' => ['team@example.com'],
        'html' => $view,
        'body' => 'A new enquiry came in.',
    ]);

    $preview(EmailTemplate::view())
        ->assertOk()
        ->assertJsonPath('format', 'html')
        ->assertSee('A new enquiry came in.')
        ->assertSee('Would you recommend us?');

    $preview('statamic-easy-forms::emails.form-submission')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['0.html']);

    $user->delete();
});

test('installer publishes the email template and explains how to use it', function () {
    $this->artisan('easy-forms:install')
        ->expectsConfirmation('Do you want to publish the form views? (recommended)', 'no')
        ->expectsConfirmation('Do you want to publish the default theme? (recommended)', 'no')
        ->expectsConfirmation('Do you want to publish the email template? (recommended)', 'yes')
        ->expectsOutputToContain('HTML view: vendor/statamic-easy-forms/emails/form-submission')
        ->assertSuccessful();

    expect(File::exists(EmailTemplate::path()))->toBeTrue();

    $form = createTestForm('installed_form', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
    ]);
    $submission = $form->makeSubmission()->data(['name' => 'Jane Doe']);

    expect(renderSubmissionEmail($submission, ['html' => EmailTemplate::view()]))
        ->toContain('New Submission')
        ->toContain('Jane Doe');
});

test('installer keeps a published email template unless told to overwrite it', function () {
    File::ensureDirectoryExists(dirname(EmailTemplate::path()));
    File::put(EmailTemplate::path(), 'My customized template');

    $this->artisan('easy-forms:install')
        ->expectsConfirmation('Do you want to publish the form views? (recommended)', 'no')
        ->expectsConfirmation('Do you want to publish the default theme? (recommended)', 'no')
        ->expectsConfirmation('Do you want to publish the email template? (recommended)', 'yes')
        ->expectsConfirmation('The email template is already published. Do you want to overwrite it?', 'no')
        ->expectsOutputToContain('Kept the existing email template')
        ->assertSuccessful();

    expect(File::get(EmailTemplate::path()))->toBe('My customized template');
});

test('email template only shows the decimals a currency amount needs', function (string $currency, float $amount, string $expected) {
    $form = createTestForm('amounts_form', [
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => $currency]],
        ['handle' => 'quote', 'field' => ['type' => 'group', 'display' => 'Quote', 'fields' => [
            ['handle' => 'deposit', 'field' => ['type' => 'currency', 'display' => 'Deposit', 'currency' => $currency]],
        ]]],
    ]);

    $email = renderSubmissionEmail($form->makeSubmission()->data(['budget' => $amount, 'quote' => ['deposit' => $amount]]));

    expect(substr_count($email, "> {$expected} </div>"))->toBe(2);
})->with([
    'whole' => ['EUR', 1500, '€1,500'],
    'decimals' => ['EUR', 1234.5, '€1,234.50'],
    'three decimals' => ['KWD', 1.125, "د.ك.\u{200F}1.125"],
]);

test('email template lists group and grid answers of fields imported from a fieldset', function () {
    Fieldset::make('location')->setContents(['fields' => [
        ['handle' => 'home_city', 'field' => ['type' => 'text', 'display' => 'Home city']],
    ]])->save();

    $form = createTestForm('imported_form', [
        ['handle' => 'place', 'field' => ['type' => 'group', 'display' => 'Place', 'fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Place name']],
            ['import' => 'location'],
        ]]],
        ['handle' => 'stops', 'field' => ['type' => 'grid', 'display' => 'Stops', 'fields' => [['import' => 'location']]]],
    ]);

    try {
        $email = renderSubmissionEmail($form->makeSubmission()->data([
            'place' => ['name' => 'Base', 'home_city' => 'Athens & Piraeus'],
            'stops' => [['home_city' => 'Volos']],
        ]));
    } finally {
        Fieldset::find('location')->delete();
    }

    expect($email)
        ->toContain('Place name </p>')
        ->toContain('Home city </p>')
        ->toContain('Athens &amp; Piraeus')
        ->toContain('Home city:</span> Volos');

    expect(substr_count($email, 'Base'))->toBe(1);
});
