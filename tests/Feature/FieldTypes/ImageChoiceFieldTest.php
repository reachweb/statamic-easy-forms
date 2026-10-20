<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

function imageChoiceOptions(): array
{
    return [
        ['key' => 'sea', 'label' => 'Sea', 'image' => 'https://example.com/sea.jpg'],
        ['key' => 'mountain', 'label' => 'Mountain', 'image' => '/images/mountain.jpg'],
        ['key' => 'city', 'image' => 'https://example.com/city.jpg'],
    ];
}

test('image choice renders a radio card per option', function () {
    createTestForm('image_test', [
        ['handle' => 'destination', 'field' => ['type' => 'image_choice', 'display' => 'Destination', 'options' => imageChoiceOptions()]],
    ]);

    $output = renderEasyFormTag('image_test');

    expect($output)
        ->toContain('id="image_test_destination_0"')
        ->toContain('for="image_test_destination_0"')
        ->toContain('type="radio"')
        ->toContain('name="destination"')
        ->toContain('value="sea"')
        ->toContain('value="mountain"')
        ->toContain('<img src="https://example.com/sea.jpg" alt="" loading="lazy" class="size-full object-cover">')
        ->toContain('<img src="/images/mountain.jpg" alt="" loading="lazy" class="size-full object-cover">')
        ->toContain('<span>Sea</span>')
        // Options without a label use their key
        ->toContain('<span>city</span>')
        ->not->toContain('type="checkbox"')
        ->not->toMatch('/<input[^>]*\schecked[\s>=]/');

    expect(substr_count($output, 'type="radio"'))->toBe(3);
});

test('image choice with multiple renders checkboxes without required', function () {
    createTestForm('image_multiple', [
        ['handle' => 'destination', 'field' => ['type' => 'image_choice', 'display' => 'Destination', 'multiple' => true, 'options' => imageChoiceOptions(), 'validate' => ['required']]],
    ]);

    $output = renderEasyFormTag('image_multiple');

    expect($output)
        ->toContain('type="checkbox"')
        ->toContain('name="destination[]"')
        ->toContain('selected.map(String).filter(key => this.keys.includes(key))')
        ->not->toContain('type="radio"')
        ->not->toMatch('/<input[^>]*\srequired[\s>=]/');

    expect(substr_count($output, 'type="checkbox"'))->toBe(3);
});

test('image choice is a group labelled by its legend', function () {
    createTestForm('image_group', [
        ['handle' => 'destination', 'field' => ['type' => 'image_choice', 'display' => 'Pick a destination', 'instructions' => 'Choose one', 'options' => imageChoiceOptions()]],
    ]);

    $output = renderEasyFormTag('image_group');

    expect($output)
        ->toContain('id="image_group_destination"')
        ->toContain('<legend class="sr-only">Pick a destination</legend>')
        ->toContain('aria-describedby="image_group_destination-description"')
        ->toContain("\$dispatch('validate-field', 'destination')")
        ->toContain('keys: [String(&quot;sea&quot;), String(&quot;mountain&quot;), String(&quot;city&quot;), ]')
        ->not->toContain('for="image_group_destination"');
});

test('image choice uses three columns and a 16:9 ratio by default', function () {
    createTestForm('image_defaults', [
        ['handle' => 'destination', 'field' => ['type' => 'image_choice', 'display' => 'Destination', 'options' => imageChoiceOptions()]],
    ]);

    $output = renderEasyFormTag('image_defaults');

    expect($output)
        ->toContain('<div class="grid grid-cols-1 @xs:grid-cols-2 @xl:grid-cols-3 gap-3">')
        ->toContain('aspect-video');
});

test('image choice lays out by columns and aspect ratio', function (int $columns, string $ratio, string $gridClasses, string $aspectClass) {
    createTestForm('image_layout', [
        ['handle' => 'destination', 'field' => ['type' => 'image_choice', 'display' => 'Destination', 'columns' => $columns, 'aspect_ratio' => $ratio, 'options' => imageChoiceOptions()]],
    ]);

    $output = renderEasyFormTag('image_layout');

    expect($output)
        ->toContain("<div class=\"grid {$gridClasses} gap-3\">")
        ->toContain("bg-ef-subtle {$aspectClass}\"");
})->with([
    'one column, square' => [1, '1/1', 'grid-cols-1', 'aspect-square'],
    'two columns, 4:3' => [2, '4/3', 'grid-cols-1 @xs:grid-cols-2', 'aspect-4/3'],
    'four columns, 3:2' => [4, '3/2', 'grid-cols-1 @xs:grid-cols-2 @2xl:grid-cols-4', 'aspect-3/2'],
    'portrait 3:4' => [3, '3/4', 'grid-cols-1 @xs:grid-cols-2 @xl:grid-cols-3', 'aspect-3/4'],
    'portrait 2:3' => [3, '2/3', 'grid-cols-1 @xs:grid-cols-2 @xl:grid-cols-3', 'aspect-2/3'],
]);

test('image choice inside a group uses the nested name and key', function () {
    createTestForm('image_in_group', [
        [
            'handle' => 'trip',
            'field' => [
                'type' => 'group',
                'display' => 'Trip',
                'fields' => [
                    ['handle' => 'destination', 'field' => ['type' => 'image_choice', 'display' => 'Destination', 'multiple' => true, 'options' => imageChoiceOptions()]],
                ],
            ],
        ],
    ]);

    $output = renderEasyFormTag('image_in_group', ['instance' => 'hero']);

    expect($output)
        ->toContain('id="image_in_group_hero_trip_destination_0"')
        ->toContain('name="trip[destination][]"')
        ->toContain("submitFields['trip.destination']");
});
