<?php

use Statamic\Facades\Form;

/*
|--------------------------------------------------------------------------
| Date Picker
|--------------------------------------------------------------------------
|
| The Forms 2 date picker wraps the classic date fieldtype. Its range mode is
| that fieldtype's, so ranges are posted, validated and stored as
| {start, end} by core.
|
*/

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

function createDatePickerForm(string $handle, array $config = [], array $validate = []): void
{
    createTestForm($handle, [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'stay', 'field' => array_filter([
            'type' => 'date_picker',
            'display' => 'Stay',
            'validate' => $validate,
            ...$config,
        ])],
    ]);
}

function submitDatePickerForm(string $handle, mixed $stay)
{
    return test()->post("/!/forms/{$handle}", ['name' => 'Jane', 'stay' => $stay], [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);
}

test('date pickers render the single date picker by default', function () {
    createDatePickerForm('single_picker');

    expect(renderEasyFormTag('single_picker'))
        ->toContain("mode: 'single'")
        ->toContain('rangeAsObject: true')
        ->toContain('x-modelable="value"');
});

test('date pickers render the easy forms date options', function () {
    createDatePickerForm('range_picker', [
        'mode' => 'range',
        'max_range' => 7,
        'min_date_today' => 1,
        'max_date' => '2030-12-31',
        'dont_close_after_selection' => true,
    ]);

    expect(renderEasyFormTag('range_picker'))
        ->toContain("mode: 'range'")
        ->toContain('rangeAsObject: true')
        ->toContain('maxRange: 7')
        ->toContain('configMinDateToday: 1')
        ->toContain("configMaxDateStr: '2030-12-31'")
        ->toContain('dontCloseAfterSelection: true');
});

test('text date ranges are still posted as a single string', function () {
    createTestForm('text_range', [
        ['handle' => 'dates', 'field' => ['type' => 'text', 'input_type' => 'date', 'improved_field' => true, 'date_range' => true, 'display' => 'Dates']],
    ]);

    expect(renderEasyFormTag('text_range'))
        ->toContain("mode: 'range'")
        ->toContain('rangeAsObject: false');
});

test('a single date is accepted and stored', function () {
    createDatePickerForm('single_submit');

    submitDatePickerForm('single_submit', '2026-03-01')->assertOk();

    expect(Form::find('single_submit')->submissions()->first()->get('stay'))->toBe('2026-03-01');
});

test('an invalid single date is rejected', function (mixed $stay) {
    createDatePickerForm('single_invalid');

    submitDatePickerForm('single_invalid', $stay)
        ->assertStatus(400)
        ->assertJsonStructure(['error' => ['stay']]);

    expect(Form::find('single_invalid')->submissions())->toHaveCount(0);
})->with([
    'wrong format' => '01/03/2026',
    'impossible date' => '2026-02-30',
    'range string' => '2026-03-01 - 2026-03-05',
]);

test('a date range is accepted and stored as start and end', function () {
    createDatePickerForm('range_submit', ['mode' => 'range']);

    submitDatePickerForm('range_submit', ['start' => '2026-03-01', 'end' => '2026-03-05'])->assertOk();

    expect(Form::find('range_submit')->submissions()->first()->get('stay'))
        ->toBe(['start' => '2026-03-01', 'end' => '2026-03-05']);
});

test('an invalid date range is rejected', function (mixed $stay) {
    createDatePickerForm('range_invalid', ['mode' => 'range']);

    submitDatePickerForm('range_invalid', $stay)
        ->assertStatus(400)
        ->assertJsonStructure(['error' => ['stay']]);

    expect(Form::find('range_invalid')->submissions())->toHaveCount(0);
})->with([
    'missing end' => [['start' => '2026-03-01']],
    'empty end' => [['start' => '2026-03-01', 'end' => '']],
    'invalid start' => [['start' => '2026-13-01', 'end' => '2026-03-05']],
]);

test('an optional date range can be left empty', function () {
    createDatePickerForm('range_optional', ['mode' => 'range']);

    submitDatePickerForm('range_optional', '')->assertOk();

    expect(Form::find('range_optional')->submissions()->first()->get('stay'))->toBeNull();
});

test('a required date range must be filled in', function () {
    createDatePickerForm('range_required', ['mode' => 'range'], ['required']);

    submitDatePickerForm('range_required', '')
        ->assertStatus(400)
        ->assertJsonStructure(['error' => ['stay']]);
});
