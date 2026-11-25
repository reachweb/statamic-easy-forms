<?php

use Statamic\Facades\Form;
use Statamic\Fields\Fields;
use Statamic\Fields\Fieldtype;
use Statamic\Fields\Values;
use Statamic\Forms\Fields\FormFieldtype;

/**
 * Stands in for the Forms Pro address fieldtype, which isn't a dependency.
 */
class FakeAddressFieldtype extends Fieldtype
{
    protected static $handle = 'address';

    protected $selectable = false;

    public function fields(): Fields
    {
        $rules = in_array('required', (array) $this->field()->get('validate')) ? ['required'] : [];

        return new Fields(collect([
            'line_1' => ['display' => 'Address Line 1', 'validate' => $rules],
            'line_2' => ['display' => 'Address Line 2'],
            'city' => ['display' => 'City', 'validate' => $rules],
            'region' => ['display' => 'Region'],
            'postcode' => ['display' => 'Postcode'],
            'country' => ['display' => 'Country', 'validate' => $rules, 'default' => $this->config('default_country')],
        ])->map(fn (array $field, string $handle) => ['handle' => $handle, 'field' => ['type' => 'text', ...$field]])->values()->all(), $this->field()->parent(), $this->field());
    }

    public function configFieldItems(): array
    {
        return ['default_country' => ['type' => 'text']];
    }

    public function rules(): array
    {
        return ['array'];
    }

    public function augment($value)
    {
        return $value ? new Values([...$value, 'formatted' => implode(', ', array_filter($value))]) : null;
    }

    public function extraRenderableFieldData(): array
    {
        return ['countries' => [['code' => 'GR', 'name' => 'Greece'], ['code' => 'GB', 'name' => 'United Kingdom']]];
    }
}

class FakeAddressFormFieldtype extends FormFieldtype
{
    protected static $handle = 'address';

    protected static $fieldtype = 'address';

    public function toFieldArray(): array
    {
        return [...$this->config(), 'type' => 'address'];
    }
}

beforeEach(function () {
    Form::all()->each->delete();
    FakeAddressFieldtype::register();
    FakeAddressFormFieldtype::register();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('address renders its fields as a group', function () {
    createTestForm('address_test', [
        ['handle' => 'home', 'field' => ['type' => 'address', 'display' => 'Home address']],
    ]);

    $output = renderEasyFormTag('address_test');

    expect($output)->not->toContain('Easy Forms has no template');

    foreach (['line_1', 'line_2', 'city', 'region', 'postcode', 'country'] as $handle) {
        expect($output)
            ->toContain("name=\"home[{$handle}]\"")
            ->toContain("id=\"address_test_home_{$handle}\"")
            ->toContain("submitFields['home.{$handle}']");
    }
});

test('address fields have autocomplete tokens and a country dropdown', function () {
    createTestForm('address_tokens', [
        ['handle' => 'home', 'field' => ['type' => 'address', 'display' => 'Home address', 'default_country' => 'GR']],
    ]);

    expect(renderEasyFormTag('address_tokens'))
        ->toContain('autocomplete="address-line1"')
        ->toContain('autocomplete="postal-code"')
        ->toMatch('/<select[^>]*id="address_tokens_home_country"[^>]*autocomplete="country"/')
        ->toMatch('/<option value="GR" selected>\s*Greece/')
        ->toContain('United Kingdom');
});

test('only the required address fields are marked as required', function () {
    createTestForm('address_required', [
        ['handle' => 'home', 'field' => ['type' => 'address', 'display' => 'Home address', 'validate' => ['required']]],
    ]);

    // line_2, region and postcode stay optional
    expect(substr_count(renderEasyFormTag('address_required'), '(Optional)'))->toBe(3);
});
