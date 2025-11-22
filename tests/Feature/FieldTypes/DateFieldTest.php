<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('date field renders correctly', function () {
    createTestForm('date_test', [
        [
            'handle' => 'event_date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Event Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_test');

    expect($output)
        ->toContain('type="text"')
        ->toContain('x-data=')
        ->toContain('showDatepicker')
        ->toContain('selectedDate');
});

test('date field has Alpine model binding', function () {
    createTestForm('date_alpine_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_alpine_test');

    expect($output)->toContain('x-model');
});

test('date field defaults to single mode', function () {
    createTestForm('date_single_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_single_test');

    expect($output)->toContain("mode: 'single'");
});

test('date field with date_range enabled uses range mode', function () {
    createTestForm('date_range_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date Range',
                'date_range' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_range_test');

    expect($output)->toContain("mode: 'range'");
});

test('date field with min_date_today set to 0 renders correctly', function () {
    createTestForm('date_min_today_zero_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'min_date_today' => 0,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_min_today_zero_test');

    expect($output)
        ->toContain('configMinDateToday: 0')
        ->not->toContain('configMinDateToday: null');
});

test('date field with min_date_today set to positive integer renders correctly', function () {
    createTestForm('date_min_today_positive_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'min_date_today' => 7,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_min_today_positive_test');

    expect($output)->toContain('configMinDateToday: 7');
});

test('date field with min_date_today set to negative integer renders correctly', function () {
    createTestForm('date_min_today_negative_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'min_date_today' => -3,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_min_today_negative_test');

    expect($output)->toContain('configMinDateToday: -3');
});

test('date field without min_date_today renders null', function () {
    createTestForm('date_no_min_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_no_min_test');

    expect($output)->toContain('configMinDateToday: null');
});

test('date field with max_date_today set to 0 renders correctly', function () {
    createTestForm('date_max_today_zero_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'max_date_today' => 0,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_max_today_zero_test');

    expect($output)
        ->toContain('configMaxDateToday: 0')
        ->not->toContain('configMaxDateToday: null');
});

test('date field with max_date_today set to positive integer renders correctly', function () {
    createTestForm('date_max_today_positive_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'max_date_today' => 30,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_max_today_positive_test');

    expect($output)->toContain('configMaxDateToday: 30');
});

test('date field without max_date_today renders null', function () {
    createTestForm('date_no_max_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_no_max_test');

    expect($output)->toContain('configMaxDateToday: null');
});

test('date field with static min_date renders correctly', function () {
    createTestForm('date_static_min_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'min_date' => '2025-01-01',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_static_min_test');

    expect($output)->toContain("configMinDateStr: '2025-01-01'");
});

test('date field with static max_date renders correctly', function () {
    createTestForm('date_static_max_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'max_date' => '2025-12-31',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_static_max_test');

    expect($output)->toContain("configMaxDateStr: '2025-12-31'");
});

test('date field with max_range set renders correctly', function () {
    createTestForm('date_max_range_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date Range',
                'date_range' => true,
                'max_range' => 7,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_max_range_test');

    expect($output)
        ->toContain('maxRange: 7')
        ->toContain("mode: 'range'");
});

test('date field without max_range renders null', function () {
    createTestForm('date_no_max_range_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'date_range' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_no_max_range_test');

    expect($output)->toContain('maxRange: null');
});

test('date field includes calendar interface elements', function () {
    createTestForm('date_calendar_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_calendar_test');

    expect($output)
        ->toContain('role="dialog"')
        ->toContain('aria-label="Calendar"')
        ->toContain('selectDate(date)')
        ->toContain('changeMonth');
});

test('date field includes year picker', function () {
    createTestForm('date_year_picker_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_year_picker_test');

    expect($output)
        ->toContain('showYearPicker')
        ->toContain('selectYear');
});

test('date field includes reset functionality', function () {
    createTestForm('date_reset_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_reset_test');

    expect($output)
        ->toContain('resetDate()')
        ->toContain('Clear date');
});

test('date field with placeholder renders correctly', function () {
    createTestForm('date_placeholder_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'placeholder' => 'Choose a date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_placeholder_test');

    expect($output)->toContain('Choose a date');
});

test('date field is readonly to prevent manual input', function () {
    createTestForm('date_readonly_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_readonly_test');

    expect($output)->toContain('readonly');
});

test('date field includes date validation logic', function () {
    createTestForm('date_validation_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'min_date_today' => 0,
                'max_date_today' => 30,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_validation_test');

    expect($output)->toContain('isAllowed(date)');
});

test('date field in range mode includes range selection logic', function () {
    createTestForm('date_range_logic_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date Range',
                'date_range' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_range_logic_test');

    expect($output)
        ->toContain('dateFrom')
        ->toContain('dateTo')
        ->toContain('isInRange(date)');
});

test('date field with hover functionality in range mode', function () {
    createTestForm('date_hover_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date Range',
                'date_range' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_hover_test');

    expect($output)
        ->toContain('hoverDate')
        ->toContain('setHover(date)')
        ->toContain('x-on:mouseenter="setHover(date)"');
});

test('date field includes ARIA attributes for accessibility', function () {
    createTestForm('date_aria_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_aria_test');

    expect($output)
        ->toContain('aria-label')
        ->toContain('aria-haspopup="dialog"')
        ->toContain('aria-modal="true"');
});

test('date field with combined relative and static dates', function () {
    createTestForm('date_combined_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'min_date_today' => 0,
                'max_date' => '2025-12-31',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_combined_test');

    expect($output)
        ->toContain('configMinDateToday: 0')
        ->toContain("configMaxDateStr: '2025-12-31'");
});

test('date field includes keyboard navigation', function () {
    createTestForm('date_keyboard_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_keyboard_test');

    expect($output)
        ->toContain('x-on:keydown.escape')
        ->toContain('x-on:keydown.space');
});

test('date field includes click outside to close', function () {
    createTestForm('date_click_outside_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_click_outside_test');

    expect($output)->toContain('x-on:click.outside');
});

test('date field with dont_close_after_selection enabled renders correctly', function () {
    createTestForm('date_dont_close_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
                'dont_close_after_selection' => true,
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_dont_close_test');

    expect($output)->toContain('dontCloseAfterSelection: true');
});

test('date field without dont_close_after_selection defaults to false', function () {
    createTestForm('date_default_close_test', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_default_close_test');

    expect($output)->toContain('dontCloseAfterSelection: false');
});

test('date field includes enhanced keyboard navigation methods', function () {
    createTestForm('date_keyboard_enhanced', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_keyboard_enhanced');

    expect($output)
        ->toContain('handleKeyboardNavigation')
        ->toContain('focusDateButton')
        ->toContain('initFocusOnOpen')
        ->toContain('focusedDate');
});

test('date field buttons have keyboard event handlers', function () {
    createTestForm('date_button_keyboard', [
        [
            'handle' => 'event_date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Event Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_button_keyboard');

    expect($output)
        ->toContain('x-on:keydown="if (focusedDate) handleKeyboardNavigation($event, focusedDate)"')
        ->toContain('x-on:focus="focusedDate = date"')
        ->toContain('x-bind:data-date="date"')
        ->toContain('x-bind:tabindex="focusedDate === date ? 0 : -1"');
});

test('date field input initializes focus on open', function () {
    createTestForm('date_init_focus', [
        [
            'handle' => 'date',
            'field' => [
                'type' => 'text',
                'input_type' => 'date',
                'improved_field' => true,
                'display' => 'Date',
            ],
        ],
    ]);

    $output = renderEasyFormTag('date_init_focus');

    expect($output)
        ->toContain('if(showDatepicker) initFocusOnOpen()');
});
