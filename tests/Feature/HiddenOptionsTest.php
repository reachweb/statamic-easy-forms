<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

$options = [
    ['key' => 'a', 'value' => 'Alpha'],
    ['key' => 'b', 'value' => 'Bravo', 'hidden' => true],
];

$fieldtypes = [
    'multi choice' => ['multi_choice', []],
    'improved multi choice' => ['multi_choice', ['improved_field' => true]],
    'checkboxes' => ['checkboxes', []],
    'dropdown' => ['dropdown', []],
    'improved dropdown' => ['dropdown', ['improved_field' => true]],
    'ranking' => ['ranking', []],
];

test('options hidden in the form builder are left out', function (string $type, array $config) use ($options) {
    createTestForm('hidden_options', [
        ['handle' => 'choice', 'field' => ['type' => $type, 'display' => 'Choice', 'options' => $options, ...$config]],
    ]);

    expect(renderEasyFormTag('hidden_options'))
        ->toContain('Alpha')
        ->not->toContain('Bravo');
})->with($fieldtypes);

test('options hidden in the form builder are left out inside a group', function (string $type, array $config) use ($options) {
    createTestForm('hidden_group_options', [
        ['handle' => 'details', 'field' => ['type' => 'group', 'display' => 'Details', 'fields' => [
            ['handle' => 'choice', 'field' => ['type' => $type, 'display' => 'Choice', 'options' => $options, ...$config]],
        ]]],
    ]);

    expect(renderEasyFormTag('hidden_group_options'))
        ->toContain('Alpha')
        ->not->toContain('Bravo');
})->with($fieldtypes);

test('options without a hidden flag keep their keys and labels', function () {
    createTestForm('plain_options', [
        ['handle' => 'choice', 'field' => ['type' => 'dropdown', 'display' => 'Choice', 'options' => ['a' => 'Alpha', 'b' => 'Bravo']]],
    ]);

    expect(renderEasyFormTag('plain_options'))
        ->toContain('value="a"')
        ->toContain('value="b"')
        ->toContain('Bravo');
});
