<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('files field renders correctly', function () {
    createTestForm('files_test', [
        [
            'handle' => 'document',
            'field' => [
                'type' => 'files',
                'display' => 'Document',
            ],
        ],
    ]);

    $output = renderEasyFormTag('files_test');

    expect($output)
        ->toContain('name="document"')
        ->toContain('type="file"')
        ->toContain('id="document"');
});

test('files field with single file (default)', function () {
    createTestForm('single_file', [
        [
            'handle' => 'resume',
            'field' => [
                'type' => 'files',
                'display' => 'Resume',
            ],
        ],
    ]);

    $output = renderEasyFormTag('single_file');

    expect($output)
        ->toContain('name="resume"')
        ->toContain('type="file"')
        ->not->toContain('multiple');
});

test('files field with multiple files enabled', function () {
    createTestForm('multi_files', [
        [
            'handle' => 'attachments',
            'field' => [
                'type' => 'files',
                'display' => 'Attachments',
                'max_files' => 5,
            ],
        ],
    ]);

    $output = renderEasyFormTag('multi_files');

    expect($output)
        ->toContain('name="attachments"')
        ->toContain('multiple');
});

test('files field uses theme styling', function () {
    createTestForm('styled_files', [
        [
            'handle' => 'upload',
            'field' => [
                'type' => 'files',
                'display' => 'Upload',
            ],
        ],
    ]);

    $output = renderEasyFormTag('styled_files');

    expect($output)
        ->toContain('rounded-ef')
        ->toContain('border-ef-border')
        ->toContain('bg-ef-input-bg')
        ->toContain('text-ef-input-text')
        ->toContain('focus:border-ef-focus')
        ->toContain('focus:ring-ef-focus');
});

test('files field has file button styling', function () {
    createTestForm('button_files', [
        [
            'handle' => 'docs',
            'field' => [
                'type' => 'files',
                'display' => 'Documents',
            ],
        ],
    ]);

    $output = renderEasyFormTag('button_files');

    expect($output)
        ->toContain('file:mr-4')
        ->toContain('file:border-none')
        ->toContain('file:bg-ef-subtle')
        ->toContain('file:px-4')
        ->toContain('file:py-2')
        ->toContain('file:font-medium')
        ->toContain('file:text-ef-text');
});

test('files field includes instructions with aria', function () {
    createTestForm('instructed_files', [
        [
            'handle' => 'certificate',
            'field' => [
                'type' => 'files',
                'display' => 'Certificate',
                'instructions' => 'Upload PDF only',
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructed_files');

    expect($output)
        ->toContain('aria-describedby="certificate-description"');
});

test('files field has Alpine.js data binding', function () {
    createTestForm('alpine_files', [
        [
            'handle' => 'file_upload',
            'field' => [
                'type' => 'files',
                'display' => 'File Upload',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_files');

    expect($output)
        ->toContain('x-ref="fileInput"')
        ->toContain('@change="handleFileChange($event)"')
        ->toContain('submitFields');
});

test('files field handles file change events', function () {
    createTestForm('change_files', [
        [
            'handle' => 'upload',
            'field' => [
                'type' => 'files',
                'display' => 'Upload',
            ],
        ],
    ]);

    $output = renderEasyFormTag('change_files');

    expect($output)
        ->toContain('handleFileChange')
        ->toContain('fileField_upload');
});

test('files field respects max_files configuration', function () {
    createTestForm('max_files', [
        [
            'handle' => 'images',
            'field' => [
                'type' => 'files',
                'display' => 'Images',
                'max_files' => 10,
            ],
        ],
    ]);

    $output = renderEasyFormTag('max_files');

    expect($output)
        ->toContain('const maxFiles = 10')
        ->toContain('maxFiles === 1');
});

test('files field is accessible', function () {
    createTestForm('accessible_files', [
        [
            'handle' => 'accessible_upload',
            'field' => [
                'type' => 'files',
                'display' => 'Upload File',
                'instructions' => 'Select a file to upload',
            ],
        ],
    ]);

    $output = renderEasyFormTag('accessible_files');

    expect($output)
        ->toContain('id="accessible_upload"')
        ->toContain('aria-describedby');
});

test('files field has proper input type', function () {
    createTestForm('type_files', [
        [
            'handle' => 'file',
            'field' => [
                'type' => 'files',
                'display' => 'File',
            ],
        ],
    ]);

    $output = renderEasyFormTag('type_files');

    expect($output)
        ->toContain('type="file"');
});

test('files field includes Alpine script initialization', function () {
    createTestForm('script_files', [
        [
            'handle' => 'upload',
            'field' => [
                'type' => 'files',
                'display' => 'Upload',
            ],
        ],
    ]);

    $output = renderEasyFormTag('script_files');

    expect($output)
        ->toContain('alpine:init')
        ->toContain('Alpine.data');
});

test('files field updates submit fields correctly for single file', function () {
    createTestForm('single_submit', [
        [
            'handle' => 'single',
            'field' => [
                'type' => 'files',
                'display' => 'Single File',
                'max_files' => 1,
            ],
        ],
    ]);

    $output = renderEasyFormTag('single_submit');

    expect($output)
        ->toContain('submitFields[\'single\'] = files[0] || null');
});

test('files field updates submit fields correctly for multiple files', function () {
    createTestForm('multi_submit', [
        [
            'handle' => 'multiple',
            'field' => [
                'type' => 'files',
                'display' => 'Multiple Files',
                'max_files' => 5,
            ],
        ],
    ]);

    $output = renderEasyFormTag('multi_submit');

    expect($output)
        ->toContain('submitFields[\'multiple\'] = files.length > 0 ? files : null');
});

test('files field has focus states', function () {
    createTestForm('focus_files', [
        [
            'handle' => 'focused',
            'field' => [
                'type' => 'files',
                'display' => 'Focused Field',
            ],
        ],
    ]);

    $output = renderEasyFormTag('focus_files');

    expect($output)
        ->toContain('focus:border-ef-focus')
        ->toContain('focus:ring-2')
        ->toContain('focus:ring-ef-focus')
        ->toContain('focus:outline-none');
});
