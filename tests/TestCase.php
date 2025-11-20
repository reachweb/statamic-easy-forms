<?php

namespace Reach\StatamicEasyForms\Tests;

use Reach\StatamicEasyForms\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
