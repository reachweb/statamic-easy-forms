<?php

use Reach\StatamicEasyForms\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Unit', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeValidHtml', function () {
    $value = $this->value;

    // Check that it's a non-empty string
    expect($value)->toBeString()->not->toBeEmpty();

    // Check for basic HTML structure markers
    expect($value)->toContain('<');

    return $this;
});

expect()->extend('toContainFormElement', function () {
    $value = $this->value;

    expect($value)->toBeString()
        ->toContain('<form')
        ->toContain('</form>');

    return $this;
});

expect()->extend('toContainField', function (string $fieldHandle) {
    $value = $this->value;

    expect($value)->toBeString()
        ->toContain("name=\"{$fieldHandle}\"");

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a test form with the given handle and fields.
 *
 * @param  string  $handle  The form handle
 * @param  array  $fields  Array of field definitions
 */
function createTestForm(string $handle = 'test_form', array $fields = []): \Statamic\Forms\Form
{
    $formConfig = [
        'title' => 'Test Form',
        'honeypot' => 'honeypot',
    ];

    $form = \Statamic\Facades\Form::make($handle)
        ->title($formConfig['title'])
        ->honeypot($formConfig['honeypot']);

    $form->save();

    if (! empty($fields)) {
        $contents = [
            'fields' => $fields,
        ];

        $blueprint = \Statamic\Facades\Blueprint::make($handle)->setContents($contents);
        $blueprint->setNamespace('forms');
        $blueprint->save();
    }

    return $form;
}

/**
 * Create a field definition for testing.
 *
 * @param  string  $handle  Field handle
 * @param  string  $type  Field type
 * @param  array  $config  Additional field configuration
 */
function createFieldDefinition(string $handle, string $type = 'text', array $config = []): array
{
    return array_merge([
        'handle' => $handle,
        'field' => array_merge([
            'type' => $type,
            'display' => ucfirst($handle),
        ], $config),
    ], $config);
}

/**
 * Create a test form with sections.
 *
 * @param  string  $handle  The form handle
 * @param  array  $sections  Array of section definitions
 */
function createFormWithSections(string $handle = 'test_form', array $sections = []): \Statamic\Forms\Form
{
    $formConfig = [
        'title' => 'Test Form',
        'honeypot' => 'honeypot',
    ];

    $form = \Statamic\Facades\Form::make($handle)
        ->title($formConfig['title'])
        ->honeypot($formConfig['honeypot']);

    $form->save();

    if (! empty($sections)) {
        $blueprintSections = [];

        foreach ($sections as $section) {
            $blueprintSections[] = [
                'display' => $section['display'] ?? null,
                'instructions' => $section['instructions'] ?? null,
                'fields' => $section['fields'] ?? [],
            ];
        }

        $contents = [
            'tabs' => [
                'main' => [
                    'display' => 'Main',
                    'sections' => $blueprintSections,
                ],
            ],
        ];

        $blueprint = \Statamic\Facades\Blueprint::make($handle)->setContents($contents);
        $blueprint->setNamespace('forms');
        $blueprint->save();
    }

    return $form;
}

/**
 * Get the rendered output from the easyform tag.
 *
 * @param  string  $handle  Form handle
 * @param  array  $params  Additional tag parameters
 */
function renderEasyFormTag(string $handle, array $params = []): string
{
    $tag = new \Reach\StatamicEasyForms\Tags\EasyForm;
    $tag->setContext([]);
    $tag->setParameters(array_merge(['handle' => $handle], $params));

    return $tag->index();
}

/**
 * Create email field data for testing.
 *
 * @param  string  $display  The field display name
 * @param  mixed  $value  The field value
 * @param  string  $fieldtype  The field type (default: text)
 * @param  array  $config  Additional field configuration
 */
function createEmailFieldData(string $display, mixed $value, string $fieldtype = 'text', array $config = []): array
{
    return array_filter([
        'display' => $display,
        'value' => $value,
        'fieldtype' => $fieldtype,
        'config' => ! empty($config) ? $config : null,
    ], fn ($v) => $v !== null);
}

/**
 * Create group field data for email testing.
 *
 * @param  string  $display  The group field display name
 * @param  array  $nestedFields  Array of nested field definitions [['handle' => 'x', 'display' => 'X', 'type' => 'text'], ...]
 * @param  array  $values  Associative array of field handle => value pairs
 */
function createGroupFieldData(string $display, array $nestedFields, array $values): array
{
    $configFields = [];
    foreach ($nestedFields as $field) {
        $configFields[] = [
            'handle' => $field['handle'],
            'field' => [
                'display' => $field['display'] ?? ucfirst($field['handle']),
                'type' => $field['type'] ?? 'text',
            ],
        ];
    }

    return [
        'display' => $display,
        'value' => $values,
        'fieldtype' => 'group',
        'config' => [
            'fields' => $configFields,
        ],
    ];
}
