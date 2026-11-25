<?php

use Reach\StatamicEasyForms\FormFieldtypes\Grid;
use Reach\StatamicEasyForms\FormFieldtypes\Hidden;
use Reach\StatamicEasyForms\Tags\EasyForm;
use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\Fieldtypes as ClassicFieldtypes;
use Statamic\Forms\Fields\FormField;
use Statamic\Forms\Fields\FormFieldtype;
use Statamic\Forms\Fieldtypes;

/*
|--------------------------------------------------------------------------
| Config options
|--------------------------------------------------------------------------
|
| Easy Forms' options are appended to the form fieldtypes they apply to. The
| ones appended to classic fieldtypes (for group and grid children) are also
| copied onto form fieldtypes by core, so they're gated off where they don't
| apply.
|
*/

const EASY_FORMS_OPTIONS = [
    'easy_forms', 'improved_field', 'datepicker_config', 'date_range', 'mode', 'max_range',
    'min_date_today', 'max_date_today', 'min_date', 'max_date', 'dont_close_after_selection',
    'label_override', 'integer_template', 'increment_amount', 'min_value', 'max_value',
    'fixed_rows', 'dynamic_rows_field',
];

/**
 * The Easy Forms options a form fieldtype shows in the form builder.
 */
function visibleEasyFormsOptions(FormFieldtype $fieldtype): array
{
    $fields = $fieldtype->configBlueprint()->fields()->all();

    return $fields
        ->filter(fn ($field, $handle) => in_array($handle, EASY_FORMS_OPTIONS))
        // Classic options gated on settings that form fieldtypes don't have
        ->reject(fn ($field) => isset($field->config()['if']['input_type']) && ! $fields->has('input_type'))
        ->reject(fn ($field) => ($field->config()['unless']['type'] ?? null) === 'is '.$fieldtype->handle())
        ->keys()
        ->all();
}

function extraConfigFieldItems(Fieldtype $fieldtype): array
{
    return (new ReflectionMethod($fieldtype, 'extraConfigFieldItems'))->invoke($fieldtype);
}

function processFieldWithEasyForm(Field $field): array
{
    $method = (new ReflectionClass(EasyForm::class))->getMethod('processField');

    return $method->invoke(new EasyForm, $field);
}

test('form fieldtypes show the easy forms options that apply to them', function (string $fieldtype, array $options) {
    expect(visibleEasyFormsOptions(new $fieldtype))->toBe($options);
})->with([
    'phone' => [Fieldtypes\Phone::class, ['easy_forms', 'improved_field']],
    'date picker' => [Fieldtypes\DatePicker::class, ['easy_forms', 'mode', 'max_range', 'min_date_today', 'max_date_today', 'min_date', 'max_date', 'dont_close_after_selection']],
    'multi choice' => [Fieldtypes\MultiChoice::class, ['easy_forms', 'improved_field']],
    'dropdown' => [Fieldtypes\Dropdown::class, ['easy_forms', 'improved_field']],
    'toggle' => [Fieldtypes\Toggle::class, ['easy_forms', 'label_override']],
    'number' => [Fieldtypes\Number::class, ['easy_forms', 'integer_template', 'increment_amount']],
    'grid' => [Grid::class, ['fixed_rows', 'dynamic_rows_field']],
    'short answer' => [Fieldtypes\ShortAnswer::class, []],
    'name' => [Fieldtypes\Name::class, []],
    'email' => [Fieldtypes\Email::class, []],
    'website' => [Fieldtypes\Website::class, []],
    'long answer' => [Fieldtypes\LongAnswer::class, []],
    'yes no' => [Fieldtypes\YesNo::class, []],
    'currency' => [Fieldtypes\Currency::class, []],
    'time picker' => [Fieldtypes\TimePicker::class, []],
    'checkboxes' => [Fieldtypes\Checkboxes::class, []],
    'upload' => [Fieldtypes\Upload::class, []],
    'group' => [Fieldtypes\Group::class, []],
    'hidden' => [Hidden::class, []],
]);

test('options copied from classic fieldtypes are replaced rather than duplicated', function () {
    $handles = collect((new Fieldtypes\Phone)->configBlueprint()->contents()['tabs']['main']['sections'][0]['fields'])
        ->pluck('handle');

    expect($handles->duplicates())->toBeEmpty()
        ->and((new Fieldtypes\Phone)->configFields()->get('improved_field')->config())->not->toHaveKey('if');
});

test('classic fieldtypes keep their options for group and grid children', function () {
    $options = fn (string $fieldtype) => array_keys(extraConfigFieldItems(new $fieldtype));

    expect($options(ClassicFieldtypes\Text::class))->toBe([
        'easy_forms', 'improved_field', 'datepicker_config', 'date_range', 'max_range',
        'min_date_today', 'max_date_today', 'min_date', 'max_date', 'dont_close_after_selection',
    ])
        ->and($options(ClassicFieldtypes\Radio::class))->toBe(['easy_forms', 'improved_field'])
        ->and($options(ClassicFieldtypes\Select::class))->toBe(['easy_forms', 'improved_field'])
        ->and($options(ClassicFieldtypes\Toggle::class))->toBe(['easy_forms', 'label_override'])
        ->and($options(ClassicFieldtypes\Integer::class))->toBe(['easy_forms', 'integer_template', 'increment_amount'])
        // The grid options belong to the Easy Forms grid form fieldtype
        ->and($options(ClassicFieldtypes\Grid::class))->toBe([]);
});

test('classic text options only show for the input types they apply to', function () {
    $items = extraConfigFieldItems(new ClassicFieldtypes\Text);

    expect($items['improved_field']['if'])->toBe(['input_type' => 'contains_any tel, date, time'])
        ->and($items['max_range']['if'])->toBe(['input_type' => 'is date', 'date_range' => 'is true'])
        ->and($items['min_date']['if'])->toBe(['input_type' => 'is date'])
        ->and($items['min_date']['unless'])->toBe(['min_date_today' => 'not null']);
});

test('the date picker passes the range mode to the classic date field', function () {
    $fieldtype = (new Fieldtypes\DatePicker)->setField(new FormField('stay', [
        'type' => 'date_picker',
        'mode' => 'range',
        'max_range' => 7,
    ]));

    expect($fieldtype->toFieldArray())
        ->toMatchArray(['type' => 'date', 'format' => 'Y-m-d', 'mode' => 'range', 'max_range' => 7]);
});

test('date pickers default to single mode', function () {
    $fieldtype = (new Fieldtypes\DatePicker)->setField(new FormField('arrival', ['type' => 'date_picker']));

    expect($fieldtype->toFieldArray())->toMatchArray(['type' => 'date', 'mode' => 'single']);
});

test('native min and max take precedence over min_value and max_value', function () {
    $native = processFieldWithEasyForm(new Field('guests', [
        'type' => 'integer',
        'min' => 2,
        'max' => 8,
        'min_value' => 1,
        'max_value' => 10,
        'integer_template' => 'counter',
    ]));

    $legacy = processFieldWithEasyForm(new Field('rating', [
        'type' => 'integer',
        'min_value' => 1,
        'max_value' => 10,
        'integer_template' => 'rating',
    ]));

    expect($native)->toMatchArray(['min_value' => 2, 'max_value' => 8])
        ->and($legacy)->toMatchArray(['min_value' => 1, 'max_value' => 10]);
});
