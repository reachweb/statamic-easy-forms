<?php

use Facades\Statamic\Forms\Fields\FormFieldtypeRepository;
use Reach\StatamicEasyForms\FormFieldtypes\Hidden;
use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('hidden is a form fieldtype offered in the form builder', function () {
    $fieldtype = FormFieldtypeRepository::find('hidden');

    expect($fieldtype)->toBeInstanceOf(Hidden::class)
        ->and($fieldtype->isSelectable())->toBeTrue()
        ->and($fieldtype->categories())->toBe(['text'])
        ->and($fieldtype->collectsValue())->toBeTrue()
        ->and($fieldtype->configFields()->all()->keys()->all())->toBe(['default']);
});

test('hidden fields are classic hidden fields, so the control panel shows their value', function () {
    $form = createTestForm('hidden_blueprint', [
        ['handle' => 'campaign', 'field' => ['type' => 'hidden', 'display' => 'Campaign', 'default' => 'spring-sale']],
    ]);

    expect($form->blueprint()->field('campaign')->config())
        ->toMatchArray(['type' => 'hidden', 'input_type' => 'hidden', 'default' => 'spring-sale']);
});

test('hidden fields render a hidden input', function () {
    createTestForm('hidden_form', [
        ['handle' => 'campaign', 'field' => ['type' => 'hidden', 'display' => 'Campaign', 'default' => 'spring-sale']],
    ]);

    $output = renderEasyFormTag('hidden_form');

    expect($output)
        ->toMatch('/<input[^>]*name="campaign"[^>]*type="hidden"/s')
        ->toMatch('/<div\s+class="hidden\s/')
        // The default value seeds the field in Alpine
        ->toContain('&quot;handle&quot;:&quot;campaign&quot;')
        ->toContain('&quot;default&quot;:&quot;spring-sale&quot;')
        ->toContain('&quot;input_type&quot;:&quot;hidden&quot;');
});

test('a hidden tracking_id field is handed to the tracking id auto-fill', function () {
    createTestForm('tracking_form', [
        ['handle' => 'tracking_id', 'field' => ['type' => 'hidden', 'display' => 'Tracking ID']],
    ]);

    $output = renderEasyFormTag('tracking_form');

    // formFields.js fills hidden fields with the tracking_id handle from the URL or a cookie
    expect($output)
        ->toContain('&quot;handle&quot;:&quot;tracking_id&quot;')
        ->toContain('&quot;input_type&quot;:&quot;hidden&quot;')
        ->toMatch('/<input[^>]*name="tracking_id"[^>]*type="hidden"/s');
});

test('hidden field values are submitted and stored', function () {
    createTestForm('hidden_submit', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'tracking_id', 'field' => ['type' => 'hidden', 'display' => 'Tracking ID']],
    ]);

    $this->postJson('/!/forms/hidden_submit', ['name' => 'Jane', 'tracking_id' => 'gclid=abc123'])
        ->assertOk();

    expect(Form::find('hidden_submit')->submissions()->first()->get('tracking_id'))->toBe('gclid=abc123');
});
