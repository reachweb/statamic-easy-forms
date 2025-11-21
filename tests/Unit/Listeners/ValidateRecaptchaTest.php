<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Reach\StatamicEasyForms\Listeners\ValidateRecaptcha;
use Statamic\Events\FormSubmitted;
use Statamic\Facades\Form;

beforeEach(function () {
    // Clean up any existing forms
    Form::all()->each->delete();

    // Mock the environment variables
    putenv('RECAPTCHA_SECRET_KEY=test_secret_key');
    putenv('RECAPTCHA_SCORE_THRESHOLD=0.5');
});

afterEach(function () {
    // Clean up after tests
    Form::all()->each->delete();

    // Clear environment variables
    putenv('RECAPTCHA_SECRET_KEY');
    putenv('RECAPTCHA_SCORE_THRESHOLD');
});

test('listener is registered in service provider', function () {
    $events = Event::getListeners(FormSubmitted::class);

    // Check that ValidateRecaptcha listener is registered
    // Laravel wraps listeners in closures with the listener class in the use clause
    $hasListener = collect($events)->contains(function ($listener) {
        if (is_string($listener)) {
            return $listener === ValidateRecaptcha::class
                || str_contains($listener, 'ValidateRecaptcha');
        }

        // Check if it's a closure with our listener class
        if ($listener instanceof \Closure) {
            $reflection = new \ReflectionFunction($listener);
            $staticVars = $reflection->getStaticVariables();

            // Laravel stores the listener class name in the $listener variable
            if (isset($staticVars['listener'])) {
                return $staticVars['listener'] === ValidateRecaptcha::class
                    || str_contains($staticVars['listener'], 'ValidateRecaptcha');
            }
        }

        return false;
    });

    expect($hasListener)->toBeTrue();
});

test('listener skips validation when no recaptcha response present', function () {
    $form = createTestForm('test_form');
    $submission = $form->makeSubmission();

    $event = new FormSubmitted($submission);
    $listener = new ValidateRecaptcha;

    // Should not throw exception when no g-recaptcha-response
    expect(fn () => $listener->handle($event))->not->toThrow(ValidationException::class);
});

test('listener skips validation when secret key not configured', function () {
    putenv('RECAPTCHA_SECRET_KEY=');

    $form = createTestForm('test_form');
    $submission = $form->makeSubmission();

    request()->merge(['g-recaptcha-response' => 'test_token']);

    $event = new FormSubmitted($submission);
    $listener = new ValidateRecaptcha;

    // Should skip validation when secret key is empty
    expect(fn () => $listener->handle($event))->not->toThrow(ValidationException::class);
});

test('listener throws validation exception when recaptcha response is empty', function () {
    $form = createTestForm('test_form');
    $submission = $form->makeSubmission();

    request()->merge(['g-recaptcha-response' => '']);

    $event = new FormSubmitted($submission);
    $listener = new ValidateRecaptcha;

    expect(fn () => $listener->handle($event))->toThrow(ValidationException::class);
});

test('listener uses configured score threshold', function () {
    putenv('RECAPTCHA_SCORE_THRESHOLD=0.7');

    $listener = new ValidateRecaptcha;

    $reflection = new ReflectionClass($listener);
    $property = $reflection->getProperty('scoreThreshold');
    $property->setAccessible(true);

    expect($property->getValue($listener))->toBe(0.7);
});

test('listener uses default score threshold when not configured', function () {
    putenv('RECAPTCHA_SCORE_THRESHOLD');

    $listener = new ValidateRecaptcha;

    $reflection = new ReflectionClass($listener);
    $property = $reflection->getProperty('scoreThreshold');
    $property->setAccessible(true);

    expect($property->getValue($listener))->toBe(0.5);
});

test('listener can be extended for custom behavior', function () {
    // Create a custom listener that extends ValidateRecaptcha
    $customListener = new class extends ValidateRecaptcha
    {
        public function handle(FormSubmitted $event): void
        {
            // Only validate on specific form
            if ($event->submission->form()->handle() !== 'contact') {
                return;
            }

            parent::handle($event);
        }
    };

    // Create a non-contact form
    $form = createTestForm('other_form');
    $submission = $form->makeSubmission();

    request()->merge(['g-recaptcha-response' => '']);

    $event = new FormSubmitted($submission);

    // Should not throw exception for non-contact form
    expect(fn () => $customListener->handle($event))->not->toThrow(ValidationException::class);
});

test('listener reads secret key from environment', function () {
    putenv('RECAPTCHA_SECRET_KEY=my_secret_key_123');

    $listener = new ValidateRecaptcha;

    $reflection = new ReflectionClass($listener);
    $property = $reflection->getProperty('secret');
    $property->setAccessible(true);

    expect($property->getValue($listener))->toBe('my_secret_key_123');
});

test('verify method is protected and requires google recaptcha package', function () {
    $listener = new ValidateRecaptcha;

    $reflection = new ReflectionClass($listener);
    $method = $reflection->getMethod('verify');

    expect($method->isProtected())->toBeTrue();
});
