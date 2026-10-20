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
                'type' => 'upload',
                'store' => true,
                'display' => 'Attachments',
                'max_files' => 3,
            ],
        ],
    ]);

    $output = renderEasyFormTag('assets_test');

    expect($output)
        ->toContain('name="attachments"')
        ->toContain('type="file"')
        ->toContain('id="assets_test_attachments"');
});

test('assets field with multiple files enabled', function () {
    createTestForm('multi_assets', [
        [
            'handle' => 'documents',
            'field' => [
                'type' => 'upload',
                'store' => true,
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

test('assets field with max_files 1 takes a single file', function () {
    createTestForm('single_asset', [
        [
            'handle' => 'photo',
            'field' => [
                'type' => 'upload',
                'store' => true,
                'display' => 'Photo',
                'max_files' => 1,
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

test('assets field without max_files takes any number of files', function () {
    createTestForm('unlimited_assets', [
        [
            'handle' => 'photos',
            'field' => [
                'type' => 'upload',
                'store' => true,
                'display' => 'Photos',
            ],
        ],
    ]);

    $output = renderEasyFormTag('unlimited_assets');

    expect($output)
        ->toContain('maxFiles: null')
        ->toContain('this.maxFiles && totalFiles > this.maxFiles')
        ->toMatch('/<input[^>]*\smultiple[^>]*>/');
});

test('assets field shows the minimum number of files', function () {
    createTestForm('min_assets', [
        [
            'handle' => 'photos',
            'field' => [
                'type' => 'upload',
                'store' => true,
                'display' => 'Photos',
                'min_files' => 2,
                'max_files' => 5,
                'instructions' => 'Upload a few photos',
            ],
        ],
    ]);

    $output = renderEasyFormTag('min_assets');

    expect($output)
        ->toContain('minFiles: 2')
        ->toContain('id="min_assets_photos-min-files"')
        ->toContain('Select at least 2 files')
        ->toContain('aria-describedby="min_assets_photos-description min_assets_photos-min-files"')
        ->toContain('files.length < minFiles');
});

test('assets field without min_files shows no minimum', function () {
    createTestForm('no_min_assets', [
        [
            'handle' => 'photos',
            'field' => [
                'type' => 'upload',
                'store' => true,
                'display' => 'Photos',
            ],
        ],
    ]);

    expect(renderEasyFormTag('no_min_assets'))
        ->toContain('minFiles: null')
        ->not->toContain('-min-files')
        ->not->toContain('aria-describedby');
});

test('assets field includes drag and drop zone', function () {
    createTestForm('dragdrop_assets', [
        [
            'handle' => 'files',
            'field' => [
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
                'display' => 'Documents',
                'instructions' => 'PDF, DOC, or DOCX only (max 5MB)',
            ],
        ],
    ]);

    $output = renderEasyFormTag('instructed_assets');

    expect($output)
        ->toContain('PDF, DOC, or DOCX only (max 5MB)')
        ->toContain('aria-describedby="instructed_assets_docs-description"');
});

test('assets field shows file counter for multiple files', function () {
    createTestForm('counter_assets', [
        [
            'handle' => 'gallery',
            'field' => [
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
                'type' => 'upload',
                'store' => true,
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
