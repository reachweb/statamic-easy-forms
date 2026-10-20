<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('opinion scale renders a radio for every scale value', function () {
    createTestForm('opinion_test', [
        ['handle' => 'recommend', 'field' => ['type' => 'opinion_scale', 'display' => 'Recommend', 'min' => 0, 'max' => 10]],
    ]);

    $output = renderEasyFormTag('opinion_test');

    expect($output)
        ->toContain('name="recommend"')
        ->toContain('id="opinion_test_recommend_0"')
        ->toContain('id="opinion_test_recommend_10"')
        ->toContain('value="0"')
        ->toContain('value="10"')
        ->toContain('repeat(11, minmax(0, 1fr))')
        ->not->toMatch('/<input[^>]*\schecked[\s>=]/');

    expect(substr_count($output, 'type="radio"'))->toBe(11);
});

test('opinion scale caps the scale at ten like Statamic does', function () {
    createTestForm('opinion_capped', [
        ['handle' => 'score', 'field' => ['type' => 'opinion_scale', 'display' => 'Score', 'min' => 1, 'max' => 11]],
    ]);

    $output = renderEasyFormTag('opinion_capped');

    expect($output)
        ->toContain('id="opinion_capped_score_1"')
        ->toContain('id="opinion_capped_score_10"')
        ->not->toContain('id="opinion_capped_score_0"')
        ->not->toContain('id="opinion_capped_score_11"');

    expect(substr_count($output, 'type="radio"'))->toBe(10);
});

test('opinion scale describes the first, middle and last values with their labels', function () {
    createTestForm('opinion_labels', [
        [
            'handle' => 'recommend',
            'field' => [
                'type' => 'opinion_scale',
                'display' => 'Recommend',
                'min' => 0,
                'max' => 10,
                'low_label' => 'Not likely',
                'middle_label' => 'Neutral',
                'high_label' => 'Very likely',
            ],
        ],
    ]);

    $output = renderEasyFormTag('opinion_labels');

    expect($output)
        ->toContain('<span id="opinion_labels_recommend-low" class="flex-1 text-start">Not likely</span>')
        ->toContain('<span id="opinion_labels_recommend-middle" class="flex-1 text-center">Neutral</span>')
        ->toContain('<span id="opinion_labels_recommend-high" class="flex-1 text-end">Very likely</span>')
        // Low on the first value, middle on index floor((11 - 1) / 2) = 5, high on the last value
        ->toMatch('/value="0"\s+x-model="[^"]+"\s+aria-describedby="opinion_labels_recommend-low"/')
        ->toMatch('/value="5"\s+x-model="[^"]+"\s+aria-describedby="opinion_labels_recommend-middle"/')
        ->toMatch('/value="10"\s+x-model="[^"]+"\s+aria-describedby="opinion_labels_recommend-high"/');

    expect(substr_count($output, 'aria-describedby="opinion_labels_recommend-'))->toBe(3);
});

test('opinion scale places the middle label below the lower middle value of an even scale', function () {
    createTestForm('opinion_even', [
        ['handle' => 'score', 'field' => ['type' => 'opinion_scale', 'display' => 'Score', 'min' => 1, 'max' => 10, 'middle_label' => 'Okay']],
    ]);

    $output = renderEasyFormTag('opinion_even');

    // Ten values: the middle index is floor((10 - 1) / 2) = 4, which is the value 5
    expect($output)
        ->toMatch('/value="5"\s+x-model="[^"]+"\s+aria-describedby="opinion_even_score-middle"/')
        ->not->toContain('aria-describedby="opinion_even_score-low"')
        ->not->toContain('aria-describedby="opinion_even_score-high"');
});

test('opinion scale without labels renders no label row', function () {
    createTestForm('opinion_no_labels', [
        ['handle' => 'score', 'field' => ['type' => 'opinion_scale', 'display' => 'Score', 'min' => 1, 'max' => 5]],
    ]);

    $output = renderEasyFormTag('opinion_no_labels');

    expect($output)
        ->not->toContain('opinion_no_labels_score-low')
        ->not->toContain('opinion_no_labels_score-high');
});

test('opinion scale is a radio group labelled by its legend', function () {
    createTestForm('opinion_group', [
        ['handle' => 'score', 'field' => ['type' => 'opinion_scale', 'display' => 'Your score', 'instructions' => 'Pick one', 'min' => 1, 'max' => 5]],
    ]);

    $output = renderEasyFormTag('opinion_group');

    expect($output)
        ->toContain('id="opinion_group_score"')
        ->toContain('<legend class="sr-only">Your score</legend>')
        ->toContain('aria-describedby="opinion_group_score-description"')
        ->toContain("\$dispatch('validate-field', 'score')")
        ->not->toContain('for="opinion_group_score"');
});

test('opinion scale inside a group uses the nested name and key', function () {
    createTestForm('opinion_in_group', [
        [
            'handle' => 'survey',
            'field' => [
                'type' => 'group',
                'display' => 'Survey',
                'fields' => [
                    ['handle' => 'score', 'field' => ['type' => 'opinion_scale', 'display' => 'Score', 'min' => 1, 'max' => 5]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('opinion_in_group', ['instance' => 'sidebar']);

    expect($output)
        ->toContain('name="survey[score]"')
        ->toContain("submitFields['survey.score']")
        ->toContain('id="opinion_in_group_sidebar_survey_score_1"');
});

test('opinion scale keeps the selected value and focus perceivable in forced colors', function () {
    createTestForm('scale_forced_colors', [
        ['handle' => 'recommend', 'field' => ['type' => 'opinion_scale', 'display' => 'Recommend', 'min' => 0, 'max' => 3]],
    ]);

    expect(renderEasyFormTag('scale_forced_colors'))
        ->toContain('forced-colors:has-checked:outline-3')
        ->toContain('has-focus-visible:outline-transparent');
});
