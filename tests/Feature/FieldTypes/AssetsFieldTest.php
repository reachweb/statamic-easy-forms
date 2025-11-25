<?php

use Statamic\Facades\Form;

beforeEach(function () {
    Form::all()->each->delete();
});

afterEach(function () {
    Form::all()->each->delete();
});

test('assets field renders correctly', function () {
    createTestForm('assets_test', [
        [
            'handle' => 'attachments',
            'field' => [
                'type' => 'assets',
                'display' => 'Attachments',
                'max_files' => 3,
            ],
        ],
    ]);

    $output = renderEasyFormTag('assets_test');

    expect($output)
        ->toContain('name="attachments"')
        ->toContain('type="file"')
        ->toContain('id="attachments"');
});

test('assets field with multiple files enabled', function () {
    createTestForm('multi_assets', [
        [
            'handle' => 'documents',
            'field' => [
                'type' => 'assets',
                'display' => 'Documents',
                'max_files' => 5,
            ],
        ],
    ]);

    $output = renderEasyFormTag('multi_assets');

    expect($output)
        ->toContain('name="documents"')
        ->toContain('multiple')
        ->toContain('maxFiles: 5');
});

test('assets field with single file (default)', function () {
    createTestForm('single_asset', [
        [
            'handle' => 'photo',
            'field' => [
                'type' => 'assets',
                'display' => 'Photo',
            ],
        ],
    ]);

    $output = renderEasyFormTag('single_asset');

    expect($output)
        ->toContain('name="photo"')
        ->toContain('maxFiles: 1');

    // Verify the multiple HTML attribute is not present on the input
    expect($output)->not->toMatch('/<input[^>]*\smultiple[^>]*>/');
});

test('assets field includes drag and drop zone', function () {
    createTestForm('dragdrop_assets', [
        [
            'handle' => 'files',
            'field' => [
                'type' => 'assets',
                'display' => 'Files',
                'max_files' => 10,
            ],
        ],
    ]);

    $output = renderEasyFormTag('dragdrop_assets');

    expect($output)
        ->toContain('isDragging')
        ->toContain('dragover')
        ->toContain('drop')
        ->toContain('dragleave');
});

test('assets field includes file size formatting', function () {
    createTestForm('filesize_assets', [
        [
            'handle' => 'uploads',
            'field' => [
                'type' => 'assets',
                'display' => 'Uploads',
            ],
        ],
    ]);

    $output = renderEasyFormTag('filesize_assets');

    expect($output)
        ->toContain('formatFileSize');
});

test('assets field includes remove file functionality', function () {
    createTestForm('removable_assets', [
        [
            'handle' => 'images',
            'field' => [
                'type' => 'assets',
                'display' => 'Images',
                'max_files' => 3,
            ],
        ],
    ]);

    $output = renderEasyFormTag('removable_assets');

    expect($output)
        ->toContain('removeFile')
        ->toContain('Remove');
});

test('assets field includes instructions', function () {
    createTestForm('instructed_assets', [
        [
            'handle' => 'docs',
            'field' => [
                'type' => 'assets',
                'display' => 'Documents',
                'instructions' => 'PDF, DOC, or DOCX only (max 5MB)',
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructed_assets');

    expect($output)
        ->toContain('PDF, DOC, or DOCX only (max 5MB)')
        ->toContain('aria-describedby="docs-description"');
});

test('assets field shows file counter for multiple files', function () {
    createTestForm('counter_assets', [
        [
            'handle' => 'gallery',
            'field' => [
                'type' => 'assets',
                'display' => 'Gallery',
                'max_files' => 20,
            ],
        ],
    ]);

    $output = renderEasyFormTag('counter_assets');

    expect($output)
        ->toContain('max files');
});

test('assets field includes upload icon', function () {
    createTestForm('icon_assets', [
        [
            'handle' => 'file',
            'field' => [
                'type' => 'assets',
                'display' => 'File Upload',
            ],
        ],
    ]);

    $output = renderEasyFormTag('icon_assets');

    expect($output)
        ->toContain('<svg')
        ->toContain('viewBox');
});

test('assets field handles file validation', function () {
    createTestForm('validated_assets', [
        [
            'handle' => 'validated_files',
            'field' => [
                'type' => 'assets',
                'display' => 'Validated Files',
                'max_files' => 3,
            ],
        ],
    ]);

    $output = renderEasyFormTag('validated_assets');

    expect($output)
        ->toContain('handleFiles')
        ->toContain('maxFiles');
});

test('assets field includes Alpine.js data binding', function () {
    createTestForm('alpine_assets', [
        [
            'handle' => 'attachments',
            'field' => [
                'type' => 'assets',
                'display' => 'Attachments',
            ],
        ],
    ]);

    $output = renderEasyFormTag('alpine_assets');

    expect($output)
        ->toContain('x-data')
        ->toContain('files: []')
        ->toContain('submitFields');
});

test('assets field shows selected files list', function () {
    createTestForm('list_assets', [
        [
            'handle' => 'documents',
            'field' => [
                'type' => 'assets',
                'display' => 'Documents',
                'max_files' => 5,
            ],
        ],
    ]);

    $output = renderEasyFormTag('list_assets');

    expect($output)
        ->toContain('files.length > 0')
        ->toContain('x-for="(file, index) in files"')
        ->toContain('file.name');
});

test('assets field has accessible labels', function () {
    createTestForm('accessible_assets', [
        [
            'handle' => 'uploads',
            'field' => [
                'type' => 'assets',
                'display' => 'Uploads',
                'instructions' => 'Upload your files here',
            ],
        ],
    ]);

    $output = renderEasyFormTag('accessible_assets');

    expect($output)
        ->toContain('aria-describedby')
        ->toContain('aria-label');
});

test('assets field uses theme colors', function () {
    createTestForm('themed_assets', [
        [
            'handle' => 'files',
            'field' => [
                'type' => 'assets',
                'display' => 'Files',
            ],
        ],
    ]);

    $output = renderEasyFormTag('themed_assets');

    expect($output)
        ->toContain('text-ef-')
        ->toContain('bg-ef-')
        ->toContain('border-ef-');
});
