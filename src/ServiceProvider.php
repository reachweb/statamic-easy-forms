<?php

namespace Reach\StatamicEasyForms;

use Reach\StatamicEasyForms\Modifiers\NeededProperties;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $modifiers = [
        NeededProperties::class,
    ];

    public function bootAddon()
    {
        //
    }
}
