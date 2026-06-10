<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\Form;
use Statamic\Facades\Site;

beforeEach(function () {
    Form::all()->each->delete();

    config(['statamic.editions.pro' => true]);

    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en_US', 'lang' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de/', 'locale' => 'de_DE', 'lang' => 'de'],
    ]);

    app('translator')->addLines(['validation.required' => 'The :attribute field is required.'], 'en');
    app('translator')->addLines(['validation.required' => 'Das Feld :attribute ist erforderlich.'], 'de');
    app('translator')->addLines(['test.listener_message' => 'English listener message'], 'en');
    app('translator')->addLines(['test.listener_message' => 'German listener message'], 'de');

    createTestForm('localized_form', [
        [
            'handle' => 'name',
            'field' => [
                'type' => 'text',
                'display' => 'Name',
                'validate' => ['required'],
            ],
        ],
    ]);
});

afterEach(function () {
    Form::all()->each->delete();

    Site::setSites([
        'default' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en_US', 'lang' => 'en'],
    ]);
});

/**
 * Statamic core localizes blueprint validation messages itself, so the
 * middleware's contribution is everything thrown after validation — messages
 * from FormSubmitted listeners, like the addon's reCAPTCHA errors.
 */
function registerListenerThrowingTranslatedMessage(): void
{
    Event::listen(FormSubmitted::class, function () {
        throw ValidationException::withMessages(['custom' => __('test.listener_message')]);
    });
}

test('validation errors are localized to the site of the referring page', function () {
    $response = $this->postJson('/!/forms/localized_form', [], [
        'referer' => 'http://localhost/de/kontakt',
    ]);

    $response->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('ist erforderlich');
});

test('validation errors use the default locale when the referring page is on the default site', function () {
    $response = $this->postJson('/!/forms/localized_form', [], [
        'referer' => 'http://localhost/kontakt',
    ]);

    $response->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('is required');
});

test('validation errors use the default locale when there is no referer', function () {
    $response = $this->postJson('/!/forms/localized_form');

    $response->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('is required');
});

test('listener messages are localized to the site of the referring page', function () {
    registerListenerThrowingTranslatedMessage();

    $response = $this->postJson('/!/forms/localized_form', ['name' => 'Hans'], [
        'referer' => 'http://localhost/de/kontakt',
    ]);

    $response->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('German listener message');
});

test('listener messages use the default locale when localization is disabled via config', function () {
    config(['easy-forms.localize_submissions' => false]);

    registerListenerThrowingTranslatedMessage();

    $response = $this->postJson('/!/forms/localized_form', ['name' => 'Hans'], [
        'referer' => 'http://localhost/de/kontakt',
    ]);

    $response->assertStatus(422);

    expect(json_encode($response->json('errors')))->toContain('English listener message');
});

test('app locale is restored after a localized submission', function () {
    $this->postJson('/!/forms/localized_form', [], [
        'referer' => 'http://localhost/de/kontakt',
    ]);

    expect(app()->getLocale())->toBe('en');
});
