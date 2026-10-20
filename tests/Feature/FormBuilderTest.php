<?php

use Statamic\Facades\Form;
use Statamic\Facades\User;

/*
|--------------------------------------------------------------------------
| Form builder
|--------------------------------------------------------------------------
|
| Easy Forms' form fieldtypes and options, as the Forms 2 builder sees them.
|
*/

beforeEach(function () {
    Form::all()->each->delete();

    $this->user = tap(User::make()->email('builder@example.com')->makeSuper())->save();
});

afterEach(function () {
    Form::all()->each->delete();

    $this->user->delete();
});

function builderField(string $handle, string $fieldtype, array $config): array
{
    return [
        '_id' => $handle,
        'handle' => $handle,
        'type' => 'inline',
        'fieldtype' => $fieldtype,
        'config' => ['type' => $fieldtype, 'display' => ucfirst($handle), ...$config],
    ];
}

test('the builder offers the hidden and grid form fieldtypes', function () {
    createTestForm('builder_form');

    $fieldtypes = collect(
        $this->actingAs($this->user)
            ->get(cp_route('forms.builder.edit', 'builder_form'))
            ->assertSuccessful()
            ->viewData('page')['props']['fieldtypes']
    )->keyBy('handle');

    expect($fieldtypes->get('hidden'))->toMatchArray(['title' => 'Hidden', 'categories' => ['text']])
        ->and($fieldtypes->get('grid'))->toMatchArray(['title' => 'Grid', 'categories' => ['other']])
        ->and(collect($fieldtypes->get('grid')['config'])->pluck('handle')->all())
        ->toBe(['fields', 'min_rows', 'max_rows', 'add_row', 'fixed_rows', 'dynamic_rows_field']);
});

test('saving the builder keeps the easy forms options', function () {
    createTestForm('builder_save');

    $this->actingAs($this->user)
        ->patch(cp_route('forms.builder.update', 'builder_save'), [
            'pages' => [[
                '_id' => 'page',
                'sections' => [[
                    '_id' => 'section',
                    'fields' => [
                        builderField('stay', 'date_picker', ['mode' => 'range', 'max_range' => 7, 'min_date_today' => 0]),
                        builderField('mobile', 'phone', ['improved_field' => true]),
                        builderField('guests', 'number', ['min' => 1, 'max' => 9, 'integer_template' => 'counter', 'increment_amount' => 2]),
                        builderField('tracking_id', 'hidden', ['default' => 'organic']),
                        builderField('rooms', 'grid', [
                            'fixed_rows' => 2,
                            'fields' => [['handle' => 'guest', 'field' => ['type' => 'text', 'display' => 'Guest']]],
                        ]),
                    ],
                ]],
            ]],
        ])
        ->assertSuccessful();

    $fields = Form::find('builder_save')->blueprint()->fields();

    expect($fields->get('stay')->config())->toMatchArray(['type' => 'date', 'mode' => 'range', 'max_range' => 7, 'min_date_today' => 0])
        ->and($fields->get('mobile')->config())->toMatchArray(['type' => 'text', 'input_type' => 'tel', 'improved_field' => true])
        ->and($fields->get('guests')->config())->toMatchArray(['type' => 'integer', 'min' => 1, 'max' => 9, 'integer_template' => 'counter', 'increment_amount' => 2])
        ->and($fields->get('tracking_id')->config())->toMatchArray(['type' => 'hidden', 'input_type' => 'hidden', 'default' => 'organic'])
        ->and($fields->get('rooms')->config())->toMatchArray(['type' => 'grid', 'fixed_rows' => 2]);
});
