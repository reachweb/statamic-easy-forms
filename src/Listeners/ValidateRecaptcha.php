<?php

namespace Reach\StatamicEasyForms\Listeners;

use Illuminate\Validation\ValidationException;
use ReCaptcha\ReCaptcha;
use Statamic\Events\FormSubmitted;

/**
 * Validates Google reCAPTCHA v3 on form submission.
 *
 * This listener automatically validates reCAPTCHA tokens when present in form submissions.
 * It uses Google's reCAPTCHA v3 to verify the token and enforce a score threshold.
 *
 * @see https://developers.google.com/recaptcha/docs/v3
 */
class ValidateRecaptcha
{
    /**
     * The reCAPTCHA secret key.
     * This should be set in your .env file as RECAPTCHA_SECRET_KEY
     */
    protected string $secret;

    /**
     * The minimum score threshold for reCAPTCHA v3 (0.0 to 1.0).
     * Default is 0.5. Lower scores indicate more bot-like behavior.
     */
    protected float $scoreThreshold;

    /**
     * Create a new listener instance.
     */
    public function __construct()
    {
        $this->secret = env('RECAPTCHA_SECRET_KEY', '');
        $this->scoreThreshold = (float) env('RECAPTCHA_SCORE_THRESHOLD', 0.5);
    }

    /**
     * Handle the FormSubmitted event.
     *
     *
     * @throws ValidationException
     */
    public function handle(FormSubmitted $event): void
    {
        if (empty($this->secret)) {
            return;
        }

        if (! request()->has('g-recaptcha-response')) {
            throw ValidationException::withMessages([
                'recaptcha' => __('ReCAPTCHA verification is required.'),
            ]);
        }

        $this->verify();
    }

    /**
     * Verify the reCAPTCHA token.
     *
     *
     * @throws ValidationException
     */
    protected function verify(): bool
    {
        $recaptchaResponse = request()->get('g-recaptcha-response');

        if (empty($recaptchaResponse)) {
            throw ValidationException::withMessages([
                'recaptcha' => __('ReCAPTCHA verification is required.'),
            ]);
        }

        $recaptcha = new ReCaptcha($this->secret);
        $response = $recaptcha
            ->setScoreThreshold($this->scoreThreshold)
            ->verify($recaptchaResponse, request()->ip());

        if ($response->isSuccess()) {
            // Verify the action name matches what we expect (Google best practice)
            if ($response->getAction() !== 'submit') {
                throw ValidationException::withMessages([
                    'recaptcha' => __('Invalid CAPTCHA action.'),
                ]);
            }

            return true;
        }

        // Get error codes for debugging
        $errorCodes = $response->getErrorCodes();

        throw ValidationException::withMessages([
            'recaptcha' => __('Failed CAPTCHA verification. Please try again.'),
        ]);
    }
}
