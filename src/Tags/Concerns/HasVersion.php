<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Composer\InstalledVersions;

trait HasVersion
{
    /**
     * Get the installed version of the addon.
     *
     * Returns the version string from Composer, or 'dev' if:
     * - The package is not installed via Composer
     * - Running in local development
     * - Composer\InstalledVersions is not available
     *
     * Usage: {{ easyform:version }}
     *
     * @return string The version string (e.g., "1.0.0") or "dev"
     */
    public function version(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        try {
            return InstalledVersions::getVersion('reachweb/statamic-easy-forms') ?? 'dev';
        } catch (\Exception $e) {
            return 'dev';
        }
    }
}
