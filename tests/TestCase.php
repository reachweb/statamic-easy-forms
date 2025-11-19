<?php

namespace Reachweb\StatamicEasyForms\Tests;

use Reachweb\StatamicEasyForms\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
