<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('currency renders a whole number input with the currency symbol beside it', function () {
    createTestForm('currency_test', [
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => 'EUR']],
    ]);

    $output = renderEasyFormTag('currency_test');

    expect($output)
        ->toMatch('/<span\s+id="currency_test_budget-currency"[^>]*aria-hidden="true"\s*>€<\/span>/')
        ->toContain('id="currency_test_budget"')
        ->toContain('name="budget"')
        ->toContain('type="number"')
        ->toContain('inputmode="numeric"')
        ->toContain('step="1"')
        ->toContain('aria-describedby="currency_test_budget-currency"')
        ->toContain("x-model.number=\"submitFields['budget']\"")
        ->toContain("\$dispatch('validate-field', 'budget')");
});

test('currency symbol is not printed in the label', function () {
    createTestForm('currency_label', [
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => 'USD']],
    ]);

    $output = renderEasyFormTag('currency_label');

    expect($output)->toMatch('/<label class="[^"]*" for="currency_label_budget">\s*Budget\s*<\/label>/');
});

test('currency adds its symbol to the instructions description', function () {
    createTestForm('currency_instructions', [
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => 'GBP', 'instructions' => 'Whole pounds only']],
    ]);

    $output = renderEasyFormTag('currency_instructions');

    expect($output)
        ->toContain('aria-describedby="currency_instructions_budget-currency currency_instructions_budget-description"')
        ->toContain('id="currency_instructions_budget-description"');
});

test('currency ignores the integer counter and rating templates', function (string $template) {
    createTestForm('currency_template', [
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => 'EUR', 'integer_template' => $template]],
    ]);

    $output = renderEasyFormTag('currency_template');

    expect($output)
        ->toContain('id="currency_template_budget-currency"')
        ->toContain('for="currency_template_budget"')
        ->not->toContain('incrementAmount')
        ->not->toContain('-star-');
})->with(['counter', 'rating']);

test('number fields still use the integer template', function () {
    createTestForm('number_test', [
        ['handle' => 'guests', 'field' => ['type' => 'number', 'display' => 'Guests', 'min' => 1, 'max' => 10]],
    ]);

    $output = renderEasyFormTag('number_test');

    expect($output)
        ->toContain('id="number_test_guests"')
        ->toContain('type="number"')
        ->not->toContain('-currency"');
});

test('prepend and append still show in the label for other fields', function () {
    createTestForm('prepend_test', [
        ['handle' => 'price', 'field' => ['type' => 'integer', 'display' => 'Price', 'prepend' => 'USD', 'append' => 'per night']],
    ]);

    $output = renderEasyFormTag('prepend_test');

    expect($output)
        ->toContain('<span class="text-ef-text-muted">USD</span>')
        ->toContain('<span class="text-ef-text-muted">per night</span>');
});

test('currency inside a group uses the nested name and key', function () {
    createTestForm('currency_in_group', [
        [
            'handle' => 'quote',
            'field' => [
                'type' => 'group',
                'display' => 'Quote',
                'fields' => [
                    ['handle' => 'amount', 'field' => ['type' => 'integer', 'display' => 'Amount', 'currency_symbol' => '$', 'prepend' => '$']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('currency_in_group', ['instance' => 'inline']);

    expect($output)
        ->toContain('id="currency_in_group_inline_quote_amount"')
        ->toContain('id="currency_in_group_inline_quote_amount-currency"')
        ->toContain('name="quote[amount]"')
        ->toContain("submitFields['quote.amount']");
});
