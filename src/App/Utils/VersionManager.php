<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Utils;

use Illuminate\Support\Facades\Artisan;

/**
 * Class VersionManager
 *
 * Manages semantic versioning for packages by reading, updating, and writing
 * version information to a JSON file. Also supports automatic asset publishing
 * and version bumping based on flags and conditions.
 *
 * @package App\Utils
 */
class VersionManager
{
    /**
     * Full path to the JSON version file.
     *
     * @var string
     */
    protected string $versionFile;

    /**
     * VersionManager constructor.
     *
     * @param string $versionFile Absolute path to the JSON file storing version information.
     */
    public function __construct(string $versionFile)
    {
        $this->versionFile = $versionFile;
    }

    /**
     * Increments the version number based on the specified type.
     *
     * Supported types:
     * - 'release': bumps the minor version (X.Y.0)
     * - 'build': bumps the patch version (X.Y.Z+1)
     *
     * If the version file does not exist, it initializes with '0.0.0'.
     *
     * @param string $type Type of version bump ('release' or 'build'). Default is 'build'.
     * @return string The new version string after bumping.
     */
    public function bumpVersion(string $type = 'build'): string
    {
        $dir = dirname($this->versionFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->versionFile)) {
            $versionData = ['version' => '0.0.0'];
        } else {
            $json = file_get_contents($this->versionFile);
            $versionData = json_decode($json, true) ?: ['version' => '0.0.0'];
        }

        $currentVersion = $versionData['version'] ?? '0.0.0';
        $parts = explode('.', $currentVersion);
        $parts = array_map('intval', $parts + [0, 0, 0]); // ensure 3 elements

        if ($type === 'release') {
            $parts[1]++;  // bump minor
            $parts[2] = 0; // reset patch
        } else {
            $parts[2]++; // bump patch
        }

        $newVersion = implode('.', $parts);
        $versionData['version'] = $newVersion;

        file_put_contents(
            $this->versionFile,
            json_encode($versionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $newVersion;
    }

    /**
     * Publishes package assets and bumps the version if needed.
     *
     * If the flag file does not exist, it triggers asset publishing and version bumping.
     * For 'release' and 'build' types, the flag file is deleted to force publishing.
     *
     * @param string $package Name of the package (e.g., 'my-package').
     * @param string $flagFile Path to the flag file used to control publishing.
     * @param string $bumpType Type of version bump: 'fix', 'build', or 'release'. Default is 'fix'.
     * @param bool $force Whether to force asset publishing.
     * @return void
     */
    public static function handlePublishAndVersionBump(
        string $package,
        string $flagFile,
        string $bumpType = 'fix',
        bool $force = false
    ): void {
        $versionFile = base_path("vendor/webemo/$package/config/$package.json");
        $manager = new self($versionFile);

        $currentVersion = '0.0.0';
        if (file_exists($versionFile)) {
            $json = file_get_contents($versionFile);
            $data = json_decode($json, true);

            if (!empty($data['version'])) {
                $currentVersion = $data['version'];
            }
        }
        echo "\nℹ️ Current version: $currentVersion\n";

        if (($bumpType === 'release' || $bumpType === 'build') && file_exists($flagFile)) {
            unlink($flagFile);
        }

        Artisan::call('vendor:publish', [
            '--tag' => "$package-assets",
            '--force' => $force,
        ]);

        Artisan::call('vendor:publish', [
            '--tag' => "$package-theme-assets",
            '--force' => $force,
        ]);

        if (!file_exists($flagFile)) {
            if (!is_dir(dirname($flagFile))) {
                mkdir(dirname($flagFile), 0755, true);
            }
            file_put_contents($flagFile, now()->toDateTimeString());

            $newVersion = $manager->bumpVersion($bumpType);
            echo "\n✅ Version bumped to: $newVersion \n";
        }
    }
}
