<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('star rating renders one radio per star', function () {
    createTestForm('star_test', [
        ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating', 'max_stars' => 5]],
    ]);

    $output = renderEasyFormTag('star_test');

    expect($output)
        ->toContain('name="rating"')
        ->toContain('id="star_test_rating-star-1"')
        ->toContain('value="1"')
        ->toContain('value="5"')
        ->toContain('<span class="sr-only">1 star</span>')
        ->toContain('<span class="sr-only">5 stars</span>')
        ->not->toContain('id="star_test_rating-star-6"')
        ->not->toContain('value="0.5"');

    expect(substr_count($output, 'type="radio"'))->toBe(5);
});

test('star rating with half stars renders a radio for every half step', function () {
    createTestForm('half_star_test', [
        ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating', 'max_stars' => 3, 'allow_half_stars' => true]],
    ]);

    $output = renderEasyFormTag('half_star_test');

    expect($output)
        ->toContain('id="half_star_test_rating-star-1-half"')
        ->toContain('value="0.5"')
        ->toContain('value="1.5"')
        ->toContain('value="2.5"')
        ->toContain('value="3"')
        ->toContain('<span class="sr-only">0.5 stars</span>')
        ->toContain('<span class="sr-only">2.5 stars</span>')
        ->toContain("allowed: ['0.5', '1', '1.5', '2', '2.5', '3', ]");

    expect(substr_count($output, 'type="radio"'))->toBe(6);
});

test('star rating is a radio group labelled by its legend', function () {
    createTestForm('star_group', [
        ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'How would you rate us?', 'instructions' => 'Pick a star']],
    ]);

    $output = renderEasyFormTag('star_group');

    expect($output)
        ->toContain('<fieldset')
        ->toContain('id="star_group_rating"')
        ->toContain('<legend class="sr-only">How would you rate us?</legend>')
        ->toContain('aria-describedby="star_group_rating-description"')
        // The wrapper label is a <span>, as the radios are labelled by the legend
        ->not->toContain('for="star_group_rating"');
});

test('star rating defaults to five stars and nothing is preselected', function () {
    createTestForm('star_defaults', [
        ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating']],
    ]);

    $output = renderEasyFormTag('star_defaults');

    expect($output)
        ->toContain('id="star_defaults_rating-star-5"')
        ->toContain('x-model="submitFields[\'rating\']"')
        ->not->toMatch('/<input[^>]*\schecked[\s>=]/');

    expect(substr_count($output, 'type="radio"'))->toBe(5);
});

test('star rating validates on change', function () {
    createTestForm('star_validate', [
        ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating']],
    ]);

    $output = renderEasyFormTag('star_validate');

    expect($output)->toContain("\$dispatch('validate-field', 'rating')");
});

test('star rating inside a group uses the nested name and key', function () {
    createTestForm('star_in_group', [
        [
            'handle' => 'feedback',
            'field' => [
                'type' => 'group',
                'display' => 'Feedback',
                'fields' => [
                    ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating', 'max_stars' => 3]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('star_in_group');

    expect($output)
        ->toContain('name="feedback[rating]"')
        ->toContain("submitFields['feedback.rating']")
        ->toContain('id="star_in_group_feedback_rating-star-1"');
});

test('star rating inside a grid uses index placeholders', function () {
    createTestForm('star_in_grid', [
        [
            'handle' => 'reviews',
            'field' => [
                'type' => 'grid',
                'display' => 'Reviews',
                'fields' => [
                    ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('star_in_grid');

    expect($output)
        ->toContain('name="reviews[__INDEX__][rating]"')
        ->toContain("submitFields['reviews.__INDEX__.rating']")
        ->toContain('id="star_in_grid_reviews___INDEX___rating-star-1"');
});

test('star rating ids are scoped to the form instance', function () {
    createTestForm('star_instance', [
        ['handle' => 'rating', 'field' => ['type' => 'star_rating', 'display' => 'Rating']],
    ]);

    $output = renderEasyFormTag('star_instance', ['instance' => 'footer']);

    expect($output)
        ->toContain('id="star_instance_footer_rating"')
        ->toContain('for="star_instance_footer_rating-star-1"');
});

test('star rating falls back to the default rating color and keeps focus visible in forced colors', function () {
    createTestForm('star_colors', [
        ['handle' => 'stars', 'field' => ['type' => 'star_rating', 'display' => 'Stars', 'max_stars' => 3]],
    ]);

    expect(renderEasyFormTag('star_colors'))
        // Sites whose published theme predates --color-ef-rating still get yellow stars
        ->toContain('text-[color:var(--color-ef-rating,var(--color-yellow-500))]')
        ->toContain('has-focus-visible:outline-transparent');
});
