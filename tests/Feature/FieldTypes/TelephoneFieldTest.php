<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('telephone field with improved_fieldtypes enabled renders telephone partial', function () {
    createTestForm('tel_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_test');

    expect($output)
        ->toContain('type="tel"')
        ->toContain('id="phoneNumber"')
        ->toContain('x-ref="phoneNumber"')
        ->toContain('id="phonesList"');
});

test('telephone field without improved_fieldtypes renders simple tel input', function () {
    createTestForm('tel_simple_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => false,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_simple_test');

    expect($output)
        ->toContain('type="tel"')
        ->toContain('name="phone"')
        ->not->toContain('id="phonesList"');
});

test('telephone field includes country phone codes options', function () {
    createTestForm('tel_options_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_options_test');

    // Check for Alpine.js data structure with options
    expect($output)
        ->toContain('options:')
        ->toContain('comboboxData:');
});

test('telephone field has dropdown button for country code selection', function () {
    createTestForm('tel_dropdown_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_dropdown_test');

    expect($output)
        ->toContain('role="combobox"')
        ->toContain('x-ref="dropdownButton"')
        ->toContain('aria-haspopup="listbox"');
});

test('telephone field includes search functionality', function () {
    createTestForm('tel_search_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_search_test');

    expect($output)
        ->toContain('x-ref="searchField"')
        ->toContain('getFilteredOptions');
});

test('telephone field has readonly attribute binding', function () {
    createTestForm('tel_readonly_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_readonly_test');

    expect($output)
        ->toContain('x-bind:readonly="!selectedOption"');
});

test('telephone field includes init method for pre-filled values', function () {
    createTestForm('tel_init_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_init_test');

    expect($output)
        ->toContain('init() {');
});

test('telephone field includes handleDeletion method', function () {
    createTestForm('tel_deletion_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_deletion_test');

    expect($output)
        ->toContain('handleDeletion(event)')
        ->toContain('x-on:keydown="handleDeletion($event)"');
});

test('telephone field includes handleInput method', function () {
    createTestForm('tel_input_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_input_test');

    expect($output)
        ->toContain('handleInput(event)')
        ->toContain('x-on:input="handleInput($event)"');
});

test('telephone field has Alpine model binding', function () {
    createTestForm('tel_alpine_binding', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_alpine_binding');

    expect($output)->toContain('x-model');
});

test('telephone field with placeholder renders correctly', function () {
    createTestForm('tel_placeholder_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_placeholder_test');

    expect($output)->toContain('Select country code first');
});

test('telephone field includes checkPosition method for dropdown', function () {
    createTestForm('tel_position_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_position_test');

    expect($output)
        ->toContain('checkPosition()')
        ->toContain('openUpwards');
});

test('telephone field includes setSelectedOption method', function () {
    createTestForm('tel_set_option_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_set_option_test');

    expect($output)
        ->toContain('setSelectedOption(option)')
        ->toContain('x-on:click="setSelectedOption(item)"');
});

test('telephone field includes keyboard navigation', function () {
    createTestForm('tel_keyboard_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_keyboard_test');

    expect($output)
        ->toContain('x-on:keydown.down.prevent')
        ->toContain('x-on:keydown.enter.prevent')
        ->toContain('x-on:keydown.esc.window');
});

test('telephone field includes focus trap', function () {
    createTestForm('tel_trap_test', [
        [
            'handle' => 'phone',
            'field' => [
                'type' => 'text',
                'input_type' => 'tel',
                'display' => 'Phone Number',
                'improved_fieldtypes' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('tel_trap_test');

    expect($output)->toContain('x-trap.inert');
});
