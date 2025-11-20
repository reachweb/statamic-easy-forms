<?php

use Reach\StatamicEasyForms\Tags\EasyForm;
use Statamic\Facades\Blueprint;

test('processField extracts correct field data', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'name',
                        'field' => [
                            'type' => 'text',
                            'display' => 'Name',
                            'validate' => 'required',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['name'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('processField');
    $method->setAccessible(true);

    $result = $method->invoke($tag, $field);

    expect($result)
        ->toBeArray()
        ->toHaveKey('handle', 'name')
        ->toHaveKey('type', 'text')
        ->toHaveKey('display', 'Name')
        ->toHaveKey('validate')
        ->toHaveKey('optional');
});

test('isFieldOptional correctly detects required fields', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'required_field',
                        'field' => [
                            'type' => 'text',
                            'validate' => 'required',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['required_field'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('isFieldOptional');
    $method->setAccessible(true);

    $result = $method->invoke($tag, $field);

    expect($result)->toBeFalse();
});

test('isFieldOptional handles required validation rule', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'test',
                        'field' => [
                            'type' => 'text',
                            'validate' => ['required'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['test'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('isFieldOptional');
    $method->setAccessible(true);

    expect($method->invoke($tag, $field))->toBeFalse();
});

test('isFieldOptional handles accepted validation rule', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'terms',
                        'field' => [
                            'type' => 'toggle',
                            'validate' => ['accepted'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['terms'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('isFieldOptional');
    $method->setAccessible(true);

    expect($method->invoke($tag, $field))->toBeFalse();
});

test('isFieldOptional handles required_if validation rule', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'conditional',
                        'field' => [
                            'type' => 'text',
                            'validate' => 'required_if:other,value',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['conditional'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('isFieldOptional');
    $method->setAccessible(true);

    expect($method->invoke($tag, $field))->toBeFalse();
});

test('field with no validation is marked as optional', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'optional',
                        'field' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['optional'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('isFieldOptional');
    $method->setAccessible(true);

    expect($method->invoke($tag, $field))->toBeTrue();
});

test('field with non-required validation is optional', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'email',
                        'field' => [
                            'type' => 'text',
                            'validate' => ['email', 'max:255'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['email'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('isFieldOptional');
    $method->setAccessible(true);

    expect($method->invoke($tag, $field))->toBeTrue();
});

test('field default values are applied', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'country',
                        'field' => [
                            'type' => 'text',
                            'default' => 'USA',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['country'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('processField');
    $method->setAccessible(true);

    $result = $method->invoke($tag, $field);

    expect($result)
        ->toHaveKey('value', 'USA');
});

test('processField handles string validation rules', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'test',
                        'field' => [
                            'type' => 'text',
                            'validate' => 'required|email|max:100',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['test'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('processField');
    $method->setAccessible(true);

    $result = $method->invoke($tag, $field);

    expect($result)
        ->toHaveKey('validate')
        ->toHaveKey('optional', false);
});

test('processField handles array validation rules', function () {
    $blueprint = Blueprint::make()->setContents([
        'sections' => [
            'main' => [
                'fields' => [
                    [
                        'handle' => 'test',
                        'field' => [
                            'type' => 'text',
                            'validate' => ['required', 'email'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $field = $blueprint->fields()->all()['test'];

    $tag = new EasyForm;
    $reflection = new ReflectionClass($tag);
    $method = $reflection->getMethod('processField');
    $method->setAccessible(true);

    $result = $method->invoke($tag, $field);

    expect($result)->toHaveKey('optional', false);
});
