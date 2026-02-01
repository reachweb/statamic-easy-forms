<?php

use Reach\StatamicEasyForms\Tags\Concerns\FormatsFieldValues;

// Create a test class that uses the trait
beforeEach(function () {
    $this->formatter = new class
    {
        use FormatsFieldValues;

        // Expose protected methods for testing
        public function testFormatValue(mixed $value, string $fieldtype = 'text'): string
        {
            return $this->formatValue($value, $fieldtype);
        }

        public function testFormatArrayValue(array $array): string
        {
            return $this->formatArrayValue($array);
        }

        public function testGetNestedFieldsWithValues(array $configFields, mixed $groupValue): array
        {
            return $this->getNestedFieldsWithValues($configFields, $groupValue);
        }

        public function testExtractNestedValue(mixed $groupValue, string $handle): mixed
        {
            return $this->extractNestedValue($groupValue, $handle);
        }
    };
});

describe('formatValue', function () {
    test('returns empty string for null value', function () {
        expect($this->formatter->testFormatValue(null))->toBe('');
    });

    test('returns empty string for empty string', function () {
        expect($this->formatter->testFormatValue(''))->toBe('');
    });

    test('returns empty string for empty array', function () {
        expect($this->formatter->testFormatValue([]))->toBe('');
    });

    test('formats simple string value', function () {
        expect($this->formatter->testFormatValue('Hello World'))->toBe('Hello World');
    });

    test('escapes HTML entities in string', function () {
        expect($this->formatter->testFormatValue('<script>alert("xss")</script>'))
            ->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
    });

    test('escapes ampersands', function () {
        expect($this->formatter->testFormatValue('Smith & Sons'))->toBe('Smith &amp; Sons');
    });

    test('returns Yes for boolean true', function () {
        expect($this->formatter->testFormatValue(true))->toBe('Yes');
    });

    test('returns empty string for boolean false', function () {
        expect($this->formatter->testFormatValue(false))->toBe('');
    });

    test('formats integer as string', function () {
        expect($this->formatter->testFormatValue(42))->toBe('42');
    });

    test('formats float as string', function () {
        expect($this->formatter->testFormatValue(3.14))->toBe('3.14');
    });

    test('formats array value using formatArrayValue', function () {
        expect($this->formatter->testFormatValue(['a', 'b', 'c']))->toBe('a, b, c');
    });
});

describe('formatArrayValue', function () {
    test('joins simple strings with comma separator', function () {
        expect($this->formatter->testFormatArrayValue(['Red', 'Green', 'Blue']))
            ->toBe('Red, Green, Blue');
    });

    test('handles single item array', function () {
        expect($this->formatter->testFormatArrayValue(['Only One']))->toBe('Only One');
    });

    test('extracts label from label/value pairs', function () {
        $array = [
            ['label' => 'United States', 'value' => 'us'],
            ['label' => 'Canada', 'value' => 'ca'],
        ];
        expect($this->formatter->testFormatArrayValue($array))->toBe('United States, Canada');
    });

    test('falls back to value when label missing', function () {
        $array = [
            ['value' => 'option_a'],
            ['value' => 'option_b'],
        ];
        expect($this->formatter->testFormatArrayValue($array))->toBe('option_a, option_b');
    });

    test('falls back to key when label and value missing', function () {
        $array = [
            ['key' => 'key_1'],
            ['key' => 'key_2'],
        ];
        expect($this->formatter->testFormatArrayValue($array))->toBe('key_1, key_2');
    });

    test('skips items with no label, value, or key', function () {
        $array = [
            ['label' => 'Valid'],
            ['other' => 'ignored'],
        ];
        // The second item will not produce a value
        expect($this->formatter->testFormatArrayValue($array))->toBe('Valid');
    });

    test('escapes HTML in array items', function () {
        $array = ['<script>', '&test'];
        expect($this->formatter->testFormatArrayValue($array))
            ->toBe('&lt;script&gt;, &amp;test');
    });

    test('handles mixed array types', function () {
        $array = [
            'Simple String',
            ['label' => 'From Object'],
            42,
        ];
        expect($this->formatter->testFormatArrayValue($array))
            ->toBe('Simple String, From Object, 42');
    });
});

describe('getNestedFieldsWithValues', function () {
    test('returns empty array when groupValue is empty', function () {
        $configFields = [
            ['handle' => 'field1', 'field' => ['display' => 'Field 1', 'type' => 'text']],
        ];
        expect($this->formatter->testGetNestedFieldsWithValues($configFields, null))->toBe([]);
        expect($this->formatter->testGetNestedFieldsWithValues($configFields, []))->toBe([]);
    });

    test('extracts nested field with display and formatted value', function () {
        $configFields = [
            ['handle' => 'name', 'field' => ['display' => 'Full Name', 'type' => 'text']],
        ];
        $groupValue = ['name' => 'John Doe'];

        $result = $this->formatter->testGetNestedFieldsWithValues($configFields, $groupValue);

        expect($result)->toHaveCount(1);
        expect($result[0]['handle'])->toBe('name');
        expect($result[0]['display'])->toBe('Full Name');
        expect($result[0]['value'])->toBe('John Doe');
        expect($result[0]['formatted_value'])->toBe('John Doe');
        expect($result[0]['fieldtype'])->toBe('text');
    });

    test('skips fields with empty values', function () {
        $configFields = [
            ['handle' => 'filled', 'field' => ['display' => 'Filled', 'type' => 'text']],
            ['handle' => 'empty', 'field' => ['display' => 'Empty', 'type' => 'text']],
            ['handle' => 'null_field', 'field' => ['display' => 'Null', 'type' => 'text']],
        ];
        $groupValue = [
            'filled' => 'Has Value',
            'empty' => '',
            'null_field' => null,
        ];

        $result = $this->formatter->testGetNestedFieldsWithValues($configFields, $groupValue);

        expect($result)->toHaveCount(1);
        expect($result[0]['handle'])->toBe('filled');
    });

    test('handles array values in nested fields', function () {
        $configFields = [
            ['handle' => 'colors', 'field' => ['display' => 'Colors', 'type' => 'checkboxes']],
        ];
        $groupValue = ['colors' => ['Red', 'Blue']];

        $result = $this->formatter->testGetNestedFieldsWithValues($configFields, $groupValue);

        expect($result[0]['formatted_value'])->toBe('Red, Blue');
    });

    test('uses handle as fallback for display', function () {
        $configFields = [
            ['handle' => 'my_field', 'field' => ['type' => 'text']],
        ];
        $groupValue = ['my_field' => 'Value'];

        $result = $this->formatter->testGetNestedFieldsWithValues($configFields, $groupValue);

        expect($result[0]['display'])->toBe('my_field');
    });

    test('skips config fields without handle', function () {
        $configFields = [
            ['field' => ['display' => 'No Handle', 'type' => 'text']],
            ['handle' => 'valid', 'field' => ['display' => 'Valid', 'type' => 'text']],
        ];
        $groupValue = ['valid' => 'Value'];

        $result = $this->formatter->testGetNestedFieldsWithValues($configFields, $groupValue);

        expect($result)->toHaveCount(1);
        expect($result[0]['handle'])->toBe('valid');
    });

    test('preserves original value alongside formatted value', function () {
        $configFields = [
            ['handle' => 'items', 'field' => ['display' => 'Items', 'type' => 'checkboxes']],
        ];
        $originalArray = ['a', 'b'];
        $groupValue = ['items' => $originalArray];

        $result = $this->formatter->testGetNestedFieldsWithValues($configFields, $groupValue);

        expect($result[0]['value'])->toBe($originalArray);
        expect($result[0]['formatted_value'])->toBe('a, b');
    });
});

describe('extractNestedValue', function () {
    test('extracts value from associative array', function () {
        $groupValue = ['name' => 'John', 'email' => 'john@example.com'];

        expect($this->formatter->testExtractNestedValue($groupValue, 'name'))->toBe('John');
        expect($this->formatter->testExtractNestedValue($groupValue, 'email'))->toBe('john@example.com');
    });

    test('returns null for missing key in array', function () {
        $groupValue = ['name' => 'John'];

        expect($this->formatter->testExtractNestedValue($groupValue, 'missing'))->toBeNull();
    });

    test('extracts value from ArrayAccess object', function () {
        $groupValue = new ArrayObject(['field' => 'value']);

        expect($this->formatter->testExtractNestedValue($groupValue, 'field'))->toBe('value');
    });

    test('returns null for missing key in ArrayAccess', function () {
        $groupValue = new ArrayObject(['field' => 'value']);

        expect($this->formatter->testExtractNestedValue($groupValue, 'missing'))->toBeNull();
    });

    test('handles nested empty string correctly', function () {
        $groupValue = ['empty' => ''];

        expect($this->formatter->testExtractNestedValue($groupValue, 'empty'))->toBe('');
    });

    test('handles nested null value correctly', function () {
        $groupValue = ['null_field' => null];

        expect($this->formatter->testExtractNestedValue($groupValue, 'null_field'))->toBeNull();
    });
});
