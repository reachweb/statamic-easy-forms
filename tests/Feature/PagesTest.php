<?php

use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Reach\StatamicEasyForms\Listeners\ValidateRecaptcha;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Form;

/*
|--------------------------------------------------------------------------
| Statamic Forms Pro pages
|--------------------------------------------------------------------------
|
| With Forms Pro installed a form can have several pages. Statamic processes
| one page per request (keyed by `_page`), saves a partial submission and
| answers with the `next_page`, which page logic may pick by skipping pages.
| The last page finalizes the submission. Easy Forms renders these forms in
| page mode: one step per page, each posted on its own by pageHandler.js.
|
*/

beforeEach(function () {
    // Pretend Forms Pro is installed, like Statamic's own tests do
    Composer::shouldReceive('isInstalled')->andReturnFalse()->byDefault();
    Composer::shouldReceive('isInstalled')->with('statamic/forms-pro')->andReturnTrue()->byDefault();

    Form::all()->each(function ($form) {
        $form->submissions()->each->delete();
        $form->delete();
    });
});

afterEach(function () {
    Form::all()->each(function ($form) {
        $form->submissions()->each->delete();
        $form->delete();
    });
});

/**
 * Create a form with pages, the way the Forms Pro builder stores them.
 */
function createMultiPageForm(string $handle, array $pages): Statamic\Forms\Form
{
    $form = Form::make($handle)->title('Survey')->honeypot('honeypot');

    $form->formFields(['pages' => $pages])->save();

    return $form;
}

/**
 * A survey with three pages. Choosing "sales" on the first page skips the second.
 */
function createSurveyForm(string $handle = 'survey', array $extraContactFields = []): Statamic\Forms\Form
{
    return createMultiPageForm($handle, [
        [
            'id' => 'about',
            'display' => 'About you',
            'instructions' => 'Tell us who you are.',
            'button_label' => 'Continue',
            'rules' => [
                [
                    'conditions' => [['field' => 'topic', 'operator' => 'equals', 'value' => 'sales']],
                    'destination' => 'details',
                ],
            ],
            'sections' => [
                ['display' => 'Your name', 'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name', 'validate' => ['required']]],
                ]],
                ['display' => 'Your enquiry', 'fields' => [
                    ['handle' => 'topic', 'field' => ['type' => 'multi_choice', 'display' => 'Topic', 'options' => [
                        ['key' => 'sales', 'value' => 'Sales'],
                        ['key' => 'support', 'value' => 'Support'],
                    ]]],
                ]],
            ],
        ],
        [
            'id' => 'contact',
            'show_previous_button' => true,
            'sections' => [
                ['fields' => [
                    ['handle' => 'email', 'field' => ['type' => 'email', 'display' => 'Email', 'validate' => ['required']]],
                    ...$extraContactFields,
                ]],
            ],
        ],
        [
            'id' => 'details',
            'display' => 'Details',
            'previous_page_label' => 'Back',
            'sections' => [
                ['fields' => [
                    ['handle' => 'message', 'field' => ['type' => 'long_answer', 'display' => 'Message']],
                ]],
            ],
        ],
    ]);
}

/**
 * Post a page the way pageHandler.js does: FormData with the page's fields and `_page`.
 */
function postPage(string $handle, string $page, array $fields, array $extra = [])
{
    return test()->post("/!/forms/{$handle}", array_merge($fields, $extra, [
        '_token' => csrf_token(),
        '_page' => $page,
    ]), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'Content-Type' => 'multipart/form-data',
        'Referer' => "http://localhost/{$handle}",
    ]);
}

/*
|--------------------------------------------------------------------------
| Rendering
|--------------------------------------------------------------------------
*/

test('multi page forms render one step per page with its sections as fieldsets', function () {
    createSurveyForm();

    $output = renderEasyFormTag('survey');

    expect($output)
        ->toContain('...pageHandler(')
        ->not->toContain('wizardHandler')
        ->toContain('data-page="about"')
        ->toContain('data-page="contact"')
        ->toContain('data-page="details"')
        ->toContain("x-show=\"currentPage === 'about'\"")
        // Page headings and instructions
        ->toContain('id="survey_page_1_heading"')
        ->toContain('data-page-heading>About you</h2>')
        ->toContain('Tell us who you are.')
        ->toContain('data-page-heading>Details</h2>')
        // A page without a display is labelled by its step and still receives focus
        ->toContain('aria-label="Step 2 of 3"')
        // Sections render as fieldsets inside the page
        ->toContain('<legend class="text-ef-xl font-bold text-ef-label mb-6">Your name</legend>')
        ->toContain('Your enquiry</legend>')
        ->toContain('name="name"')
        ->toContain('name="email"')
        ->toContain('name="message"')
        // Progress across all pages
        ->toContain('x-ref="progress"')
        ->toContain('Step <span x-text="currentStep">1</span> of 3');

    // The page is posted by JavaScript, so there is no hidden _page input
    expect($output)->not->toContain('name="_page"');
});

test('page instructions render on a page without a display', function () {
    createMultiPageForm('untitled_pages', [
        ['id' => 'first', 'display' => 'First', 'sections' => [['fields' => [
            ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ]]]],
        ['id' => 'second', 'instructions' => 'Describe the problem so we can help.', 'sections' => [['fields' => [
            ['handle' => 'problem', 'field' => ['type' => 'long_answer', 'display' => 'Problem']],
        ]]]],
    ]);

    expect(renderEasyFormTag('untitled_pages'))
        ->toContain('Describe the problem so we can help.')
        ->not->toContain('id="untitled_pages_page_2_heading"');
});

test('page mode passes each page id and field handles to the page handler', function () {
    createSurveyForm();

    $output = renderEasyFormTag('survey');

    preg_match('/\.\.\.pageHandler\((.+?)\)\s*}"/s', $output, $matches);
    $config = json_decode(html_entity_decode($matches[1]), true);

    expect($config)->toBe([
        ['id' => 'about', 'fields' => ['name', 'topic'], 'show_previous' => false, 'is_last' => false],
        ['id' => 'contact', 'fields' => ['email'], 'show_previous' => true, 'is_last' => false],
        ['id' => 'details', 'fields' => ['message'], 'show_previous' => true, 'is_last' => true],
    ]);

    // Every field is still handed to formFields(), so conditions can refer to fields on other pages
    expect($output)
        ->toContain('&quot;handle&quot;:&quot;name&quot;')
        ->toContain('&quot;handle&quot;:&quot;message&quot;');
});

test('page buttons use the labels from the form builder', function () {
    createSurveyForm();

    $output = renderEasyFormTag('survey');

    expect($output)
        // Custom next label on the first page, default labels after it
        ->toContain("<span x-show=\"currentPage === 'about'\">Continue</span>")
        ->toContain("<span x-show=\"currentPage === 'contact'\">Next</span>")
        ->toContain("<span x-show=\"currentPage === 'details'\">Submit</span>")
        // Previous buttons: the default label when enabled without one, then the custom label
        ->toContain("<span x-show=\"currentPage === 'contact'\">Previous</span>")
        ->toContain("<span x-show=\"currentPage === 'details'\">Back</span>")
        ->not->toContain("<span x-show=\"currentPage === 'about'\">Previous</span>")
        ->toContain('x-show="canGoBack()"')
        ->toContain('type="submit"');
});

test('the submit_text parameter labels the last page unless it has its own label', function () {
    createSurveyForm();

    expect(renderEasyFormTag('survey', ['submit_text' => 'Send survey']))
        ->toContain("<span x-show=\"currentPage === 'details'\">Send survey</span>");

    createMultiPageForm('labelled', [
        ['id' => 'one', 'sections' => [['fields' => [['handle' => 'name', 'field' => ['type' => 'short_answer']]]]]],
        ['id' => 'two', 'button_label' => 'Finish', 'sections' => [['fields' => [['handle' => 'email', 'field' => ['type' => 'email']]]]]],
    ]);

    expect(renderEasyFormTag('labelled', ['submit_text' => 'Send survey']))
        ->toContain("<span x-show=\"currentPage === 'two'\">Finish</span>")
        ->not->toContain('Send survey');
});

test('the wizard parameter does not change page mode', function () {
    createSurveyForm();

    $output = renderEasyFormTag('survey', ['wizard' => true]);

    expect($output)
        ->toContain('...pageHandler(')
        ->not->toContain('wizardHandler')
        ->not->toContain('stepFields')
        ->not->toContain('x-ref="step-1"')
        ->toContain('data-page="about"');
});

test('pages whose fields are all hidden still render as a step', function () {
    createMultiPageForm('hidden_page', [
        ['id' => 'one', 'sections' => [['fields' => [['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']]]]]],
        ['id' => 'two', 'display' => 'Internal', 'sections' => [['fields' => [
            ['handle' => 'internal', 'field' => ['type' => 'short_answer', 'display' => 'Internal', 'hidden' => true]],
        ]]]],
        ['id' => 'three', 'sections' => [['fields' => [['handle' => 'email', 'field' => ['type' => 'email', 'display' => 'Email']]]]]],
    ]);

    $output = renderEasyFormTag('hidden_page');

    // The server can still send the visitor to the page, so it needs a step
    expect($output)
        ->toContain('data-page="two"')
        ->toContain('&quot;id&quot;:&quot;two&quot;,&quot;fields&quot;:[]')
        ->toContain('of 3')
        ->not->toContain('name="internal"');
});

test('a form with a single page renders as a regular form', function () {
    createMultiPageForm('single_page', [
        ['id' => 'only', 'sections' => [
            ['display' => 'One', 'fields' => [['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']]]],
            ['display' => 'Two', 'fields' => [['handle' => 'email', 'field' => ['type' => 'email', 'display' => 'Email']]]],
        ]],
    ]);

    expect(renderEasyFormTag('single_page'))
        ->toContain('x-data="formHandler(')
        ->not->toContain('pageHandler')
        ->toContain('<fieldset');

    expect(renderEasyFormTag('single_page', ['wizard' => true]))
        ->toContain('...wizardHandler(2)')
        ->not->toContain('pageHandler');
});

test('without forms pro the pages collapse into sections and render as before', function () {
    Composer::shouldReceive('isInstalled')->with('statamic/forms-pro')->andReturnFalse();

    createSurveyForm();

    expect(renderEasyFormTag('survey'))
        ->toContain('x-data="formHandler(')
        ->not->toContain('pageHandler')
        ->not->toContain('data-page=')
        ->toContain('Your name</legend>')
        ->toContain('name="message"');

    // Wizard mode keeps one step per section
    expect(renderEasyFormTag('survey', ['wizard' => true]))
        ->toContain('...wizardHandler(4)')
        ->toContain('x-ref="step-4"')
        ->not->toContain('pageHandler');
});

/*
|--------------------------------------------------------------------------
| Submitting
|--------------------------------------------------------------------------
*/

test('each page is saved as a partial submission and the last page finalizes it', function () {
    $form = createSurveyForm();

    $dispatched = 0;
    Event::listen(FormSubmitted::class, function () use (&$dispatched) {
        $dispatched++;
    });

    postPage('survey', 'about', ['name' => 'Olaf', 'topic' => 'support'])
        ->assertOk()
        ->assertJson(['success' => true, 'next_page' => 'contact']);

    $submission = $form->submissions()->first();
    expect($submission->isPartial())->toBeTrue()
        ->and($submission->get('name'))->toBe('Olaf')
        ->and($dispatched)->toBe(0);

    postPage('survey', 'contact', ['email' => 'olaf@example.com'])
        ->assertOk()
        ->assertJson(['success' => true, 'next_page' => 'details']);

    postPage('survey', 'details', ['message' => 'Hello'])
        ->assertOk()
        ->assertJson(['success' => true, 'submission_created' => true, 'next_page' => null]);

    // The pages were merged into the one submission, which is now complete
    $submissions = $form->submissions();
    expect($submissions)->toHaveCount(1);

    $submission = $submissions->first();
    expect($submission->isPartial())->toBeFalse()
        ->and($submission->get('name'))->toBe('Olaf')
        ->and($submission->get('topic'))->toBe('support')
        ->and($submission->get('email'))->toBe('olaf@example.com')
        ->and($submission->get('message'))->toBe('Hello')
        ->and($dispatched)->toBe(1);
});

test('only the posted page is validated', function () {
    createSurveyForm();

    postPage('survey', 'about', ['name' => 'Olaf', 'topic' => 'support'])->assertOk();

    // The email field on this page is required. The name field from the first page is not posted again.
    $response = postPage('survey', 'contact', [])
        ->assertStatus(400);

    expect($response->json('error'))->toHaveKey('email')
        ->and($response->json('error'))->toHaveCount(1);
});

test('the next page follows page logic and can skip pages', function () {
    $form = createSurveyForm();

    // Choosing sales skips the contact page and its required email
    postPage('survey', 'about', ['name' => 'Olaf', 'topic' => 'sales'])
        ->assertOk()
        ->assertJson(['next_page' => 'details']);

    postPage('survey', 'details', ['message' => 'Hello'])
        ->assertOk()
        ->assertJson(['next_page' => null]);

    expect($form->submissions()->first()->isPartial())->toBeFalse();
});

test('going back and changing an answer takes a different path', function () {
    $form = createSurveyForm();

    postPage('survey', 'about', ['name' => 'Olaf', 'topic' => 'sales'])
        ->assertJson(['next_page' => 'details']);

    // Back to the first page (no request), which is posted again with another answer
    postPage('survey', 'about', ['name' => 'Olaf', 'topic' => 'support'])
        ->assertJson(['next_page' => 'contact']);

    postPage('survey', 'contact', ['email' => 'olaf@example.com'])
        ->assertJson(['next_page' => 'details']);

    postPage('survey', 'details', ['message' => 'Hello'])
        ->assertJson(['next_page' => null]);

    expect($form->submissions())->toHaveCount(1)
        ->and($form->submissions()->first()->get('topic'))->toBe('support');
});

test('files are uploaded with the request of their page', function () {
    Storage::fake('local');
    Storage::fake('uploads');
    AssetContainer::make('uploads')->disk('uploads')->save();

    $form = createSurveyForm('upload_survey', [
        ['handle' => 'cv', 'field' => ['type' => 'upload', 'display' => 'CV', 'store' => true, 'container' => 'uploads', 'max_files' => 1]],
    ]);

    postPage('upload_survey', 'about', ['name' => 'Olaf', 'topic' => 'support'])->assertOk();

    postPage('upload_survey', 'contact', [
        'email' => 'olaf@example.com',
        'cv' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
    ])->assertOk()->assertJson(['next_page' => 'details']);

    // The partial holds the temporary upload until the submission is finalized
    expect($form->submissions()->first()->get('cv'))->toEndWith('cv/cv.pdf');

    postPage('upload_survey', 'details', ['message' => 'Hello'])
        ->assertOk()
        ->assertJson(['next_page' => null]);

    $submission = $form->submissions()->first();
    expect($submission->isPartial())->toBeFalse()
        ->and($submission->get('cv'))->toBe('cv.pdf');

    Storage::disk('uploads')->assertExists('cv.pdf');

    AssetContainer::find('uploads')->delete();
});

test('recaptcha is only verified on the request that finalizes the submission', function () {
    config(['easy-forms.recaptcha.secret_key' => 'test_secret_key']);

    // Pretend google/recaptcha is installed and accepts the token
    app()->bind(ValidateRecaptcha::class, fn () => new class extends ValidateRecaptcha
    {
        protected function isRecaptchaLibraryInstalled(): bool
        {
            return true;
        }

        protected function verify(): bool
        {
            return true;
        }
    });

    $form = createSurveyForm();

    // pageHandler.js only fetches a token for the last page
    postPage('survey', 'about', ['name' => 'Olaf', 'topic' => 'support'])->assertOk();
    postPage('survey', 'contact', ['email' => 'olaf@example.com'])->assertOk();

    $response = postPage('survey', 'details', ['message' => 'Hello'])->assertStatus(400);
    expect($response->json('error'))->toHaveKey('recaptcha')
        ->and($form->submissions()->first()->isPartial())->toBeTrue();

    postPage('survey', 'details', ['message' => 'Hello'], ['g-recaptcha-response' => 'token'])
        ->assertOk()
        ->assertJson(['next_page' => null]);

    expect($form->submissions()->first()->isPartial())->toBeFalse();

    config(['easy-forms.recaptcha.secret_key' => null]);
});

test('a multi page form posted in a single request only saves its first page', function () {
    $form = createSurveyForm();

    // What the single request mode posts. formHandler.js treats the next_page as an error.
    test()->post('/!/forms/survey', [
        '_token' => csrf_token(),
        'name' => 'Olaf',
        'topic' => 'support',
        'email' => 'olaf@example.com',
        'message' => 'Hello',
    ], [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'Content-Type' => 'multipart/form-data',
    ])->assertOk()->assertJson(['success' => true, 'next_page' => 'contact']);

    $submission = $form->submissions()->first();
    expect($submission->isPartial())->toBeTrue()
        ->and($submission->get('email'))->toBeNull()
        ->and($submission->get('message'))->toBeNull();
});
