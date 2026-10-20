<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('ranking renders a sortable list that posts the order as an array', function () {
    createTestForm('ranking_test', [
        [
            'handle' => 'priorities',
            'field' => [
                'type' => 'ranking',
                'display' => 'Priorities',
                'options' => [
                    ['key' => 'price', 'value' => 'Price'],
                    ['key' => 'speed', 'value' => 'Speed'],
                    ['key' => 'quality', 'value' => 'Quality'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('ranking_test');

    expect($output)
        ->toContain('<ol class="space-y-2" role="list" x-ref="list">')
        ->toContain('x-for="(key, index) in order"')
        ->toContain('draggable="true"')
        ->toContain('name="priorities[]"')
        ->toContain("submitFields['priorities']");
});

test('ranking keeps the options order, including numeric keys', function () {
    createTestForm('ranking_order', [
        [
            'handle' => 'priorities',
            'field' => [
                'type' => 'ranking',
                'display' => 'Priorities',
                'options' => [
                    ['key' => '3', 'value' => 'Three'],
                    ['key' => '1', 'value' => 'One'],
                    ['key' => 'speed', 'value' => null],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('ranking_order');

    // Options are rendered in PHP order (a JSON object would sort numeric keys), and fall back to the key as label
    expect($output)->toContain(
        'options: [{ key: String(3), label: &quot;Three&quot; }, { key: String(1), label: &quot;One&quot; }, { key: String(&quot;speed&quot;), label: &quot;speed&quot; }, ]'
    );
});

test('ranking has keyboard accessible move buttons and announces moves', function () {
    createTestForm('ranking_a11y', [
        [
            'handle' => 'priorities',
            'field' => [
                'type' => 'ranking',
                'display' => 'Rank these',
                'instructions' => 'Most important first',
                'options' => [
                    ['key' => 'price', 'value' => 'Price'],
                    ['key' => 'speed', 'value' => 'Speed'],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('ranking_a11y');

    expect($output)
        ->toContain('id="ranking_a11y_priorities"')
        ->toContain('<legend class="sr-only">Rank these</legend>')
        ->toContain('aria-describedby="ranking_a11y_priorities-description"')
        ->toContain('data-move="up"')
        ->toContain('data-move="down"')
        ->toContain('x-bind:aria-label="message(\'up\', key, index)"')
        ->toContain('x-bind:aria-disabled="index === 0"')
        ->toContain('x-bind:aria-disabled="index === order.length - 1"')
        ->toContain('up: &quot;Move :item up&quot;')
        ->toContain('down: &quot;Move :item down&quot;')
        ->toContain('moved: &quot;:item moved to position :position of :total&quot;')
        ->toContain('<div class="sr-only" aria-live="polite" aria-atomic="true" x-text="announcement"></div>')
        ->toContain("this.\$dispatch('validate-field', 'priorities')")
        ->not->toContain('for="ranking_a11y_priorities"');

    expect(substr_count($output, 'type="button"'))->toBe(2);
});

test('ranking inside a group uses the nested name and key', function () {
    createTestForm('ranking_in_group', [
        [
            'handle' => 'preferences',
            'field' => [
                'type' => 'group',
                'display' => 'Preferences',
                'fields' => [
                    ['handle' => 'order', 'field' => ['type' => 'ranking', 'display' => 'Order', 'options' => ['a' => 'A', 'b' => 'B']]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('ranking_in_group', ['instance' => 'modal']);

    expect($output)
        ->toContain('id="ranking_in_group_modal_preferences_order"')
        ->toContain('name="preferences[order][]"')
        ->toContain("submitFields['preferences.order']");
});

test('ranking inside a grid uses index placeholders', function () {
    createTestForm('ranking_in_grid', [
        [
            'handle' => 'rounds',
            'field' => [
                'type' => 'grid',
                'display' => 'Rounds',
                'fields' => [
                    ['handle' => 'order', 'field' => ['type' => 'ranking', 'display' => 'Order', 'options' => ['a' => 'A', 'b' => 'B']]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('ranking_in_grid');

    expect($output)
        ->toContain('name="rounds[__INDEX__][order][]"')
        ->toContain("submitFields['rounds.__INDEX__.order']");
});

test('ranking in a group or grid uses the key and value rows that forms 2 saves', function () {
    $rows = [['key' => 'a', 'value' => 'Alpha'], ['key' => 'b', 'value' => 'Beta'], ['key' => 'c', 'value' => 'Gamma', 'hidden' => true]];

    createTestForm('nested_ranking_rows', [
        ['handle' => 'feedback', 'field' => ['type' => 'group', 'display' => 'Feedback', 'fields' => [
            ['handle' => 'order', 'field' => ['type' => 'ranking', 'display' => 'Order', 'options' => $rows]],
        ]]],
        ['handle' => 'rows', 'field' => ['type' => 'grid', 'display' => 'Rows', 'fields' => [
            ['handle' => 'order', 'field' => ['type' => 'ranking', 'display' => 'Order', 'options' => $rows]],
        ]]],
    ]);

    expect(renderEasyFormTag('nested_ranking_rows'))
        ->toContain('{ key: String(&quot;a&quot;), label: &quot;Alpha&quot; }')
        ->toContain('{ key: String(&quot;b&quot;), label: &quot;Beta&quot; }')
        // Hidden options are left out, and row indexes are not used as keys
        ->not->toContain('label: &quot;Gamma&quot;')
        ->not->toContain('key: String(0)');
});
