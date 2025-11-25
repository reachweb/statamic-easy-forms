<?php

namespace Reach\StatamicEasyForms\Tags\Concerns;

use Composer\InstalledVersions;

trait HasVersion
{
    /**
     * Get a hashed version identifier of the addon.
     *
     * Returns a SHA-256 hash of the version string to prevent exposing
     * the actual package version in the HTML source code. The hash is
     * truncated to 12 characters for display purposes.
     *
     * Returns 'dev' (unhashed) if:
     * - The package is not installed via Composer
     * - Running in local development
     * - Composer\InstalledVersions is not available
     *
     * Usage: {{ easyform:version }}
     *
     * @return string A 12-character hash of the version string or "dev"
     */
    public function version(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        try {
            $version = InstalledVersions::getVersion('reachweb/statamic-easy-forms') ?? 'dev';

            // Don't hash 'dev' - return it as-is for development environments
            if ($version === 'dev') {
                return 'dev';
            }

            // Create a SHA-256 hash and truncate to 12 characters
            return substr(hash('sha256', $version), 0, 12);
        } catch (\Exception $e) {
            return 'dev';
        }
    }
}
