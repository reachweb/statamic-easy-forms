<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('fieldtypes without a template render a labelled text input', function () {
    createTestForm('fallback_test', [
        ['handle' => 'shade', 'field' => ['type' => 'color', 'display' => 'Shade', 'instructions' => 'Any colour']],
    ]);

    $output = renderEasyFormTag('fallback_test');

    expect($output)
        ->toContain('<!-- Easy Forms has no template for the "color" fieldtype, so it renders a text input -->')
        ->toContain('for="fallback_test_shade"')
        ->toContain('id="fallback_test_shade"')
        ->toContain('name="shade"')
        ->toContain('type="text"')
        ->toContain('aria-describedby="fallback_test_shade-description"')
        ->toContain("\$dispatch('validate-field', 'shade')");
});

test('fieldtypes without a template inside a group use the nested name', function () {
    createTestForm('fallback_group', [
        [
            'handle' => 'brand',
            'field' => [
                'type' => 'group',
                'display' => 'Brand',
                'fields' => [
                    ['handle' => 'slug', 'field' => ['type' => 'slug', 'display' => 'Slug']],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('fallback_group');

    expect($output)
        ->toContain('<!-- Easy Forms has no template for the "slug" fieldtype, so it renders a text input -->')
        ->toContain('name="brand[slug]"')
        ->toContain("submitFields['brand.slug']");
});

test('fieldtypes with a template never get the fallback input', function () {
    createTestForm('fallback_known', [
        ['handle' => 'name', 'field' => ['type' => 'short_answer', 'display' => 'Name']],
        ['handle' => 'about', 'field' => ['type' => 'long_answer', 'display' => 'About']],
        ['handle' => 'guests', 'field' => ['type' => 'number', 'display' => 'Guests']],
        ['handle' => 'budget', 'field' => ['type' => 'currency', 'display' => 'Budget', 'currency' => 'EUR']],
        ['handle' => 'arrival', 'field' => ['type' => 'date_picker', 'display' => 'Arrival']],
        ['handle' => 'arrival_time', 'field' => ['type' => 'time_picker', 'display' => 'Arrival time']],
        ['handle' => 'cms', 'field' => ['type' => 'dropdown', 'display' => 'CMS', 'options' => [['key' => 'a', 'value' => 'A']]]],
        ['handle' => 'level', 'field' => ['type' => 'multi_choice', 'display' => 'Level', 'options' => [['key' => 'a', 'value' => 'A']]]],
        ['handle' => 'subscribe', 'field' => ['type' => 'yes_no', 'display' => 'Subscribe']],
        ['handle' => 'interests', 'field' => ['type' => 'checkboxes', 'display' => 'Interests', 'options' => [['key' => 'a', 'value' => 'A']]]],
        ['handle' => 'terms', 'field' => ['type' => 'toggle', 'display' => 'Terms']],
        ['handle' => 'country', 'field' => ['type' => 'dictionary', 'display' => 'Country', 'dictionary' => ['type' => 'countries'], 'max_items' => 1]],
        ['handle' => 'photo', 'field' => ['type' => 'upload', 'display' => 'Photo', 'store' => false]],
        ['handle' => 'stars', 'field' => ['type' => 'star_rating', 'display' => 'Stars']],
        ['handle' => 'score', 'field' => ['type' => 'opinion_scale', 'display' => 'Score', 'min' => 1, 'max' => 5]],
        ['handle' => 'order', 'field' => ['type' => 'ranking', 'display' => 'Order', 'options' => [['key' => 'a', 'value' => 'A']]]],
        ['handle' => 'pick', 'field' => ['type' => 'image_choice', 'display' => 'Pick', 'options' => [['key' => 'a', 'label' => 'A', 'image' => '/a.jpg']]]],
        ['handle' => 'title', 'field' => ['type' => 'heading', 'display' => 'Title']],
        ['handle' => 'intro', 'field' => ['type' => 'paragraph', 'display' => 'Intro', 'text' => 'Hello']],
        ['handle' => 'note', 'field' => ['type' => 'banner', 'display' => 'Note', 'content' => 'Hi']],
        ['handle' => 'gap', 'field' => ['type' => 'spacer', 'display' => 'Gap']],
        ['handle' => 'secret', 'field' => ['type' => 'hidden', 'display' => 'Secret']],
        ['handle' => 'people', 'field' => ['type' => 'grid', 'display' => 'People', 'fields' => [['handle' => 'name', 'field' => ['type' => 'text', 'display' => 'Name']]]]],
        ['handle' => 'address', 'field' => ['type' => 'group', 'display' => 'Address', 'fields' => [['handle' => 'city', 'field' => ['type' => 'text', 'display' => 'City']]]]],
    ]);

    $output = renderEasyFormTag('fallback_known');

    expect($output)->not->toContain('Easy Forms has no template');
});
