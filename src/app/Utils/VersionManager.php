<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Utils;

use Illuminate\Support\Facades\Artisan;

/**
 * Class VersionManager
 *
 * Zarządza wersjonowaniem semantycznym pakietów poprzez odczyt, aktualizację i zapis
 * informacji o wersji w pliku JSON. Obsługuje również publikację zasobów pakietu
 * oraz automatyczne podbijanie wersji na podstawie typu aktualizacji.
 *
 * @package Simplysmart\Simplo\App\Utils
 */
class VersionManager
{
    /**
     * Pełna ścieżka do pliku JSON z informacją o wersji.
     *
     * @var string
     */
    protected string $versionFile;

    /**
     * Konstruktor klasy VersionManager.
     *
     * @param string $versionFile Absolutna ścieżka do pliku JSON przechowującego wersję.
     */
    public function __construct(string $versionFile)
    {
        $this->versionFile = $versionFile;
    }

    /**
     * Podbija wersję pakietu zgodnie z typem aktualizacji.
     *
     * Obsługiwane typy:
     * - 'release': zwiększa wersję minor (X.Y.0)
     * - 'build': zwiększa wersję patch (X.Y.Z+1)
     *
     * Jeśli plik wersji nie istnieje, inicjalizuje go jako '0.0.0'.
     *
     * @param string $type Typ podbicia wersji ('release' lub 'build'). Domyślnie 'build'.
     * @return string Nowa wersja po aktualizacji.
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
     * Publikuje zasoby pakietu i podbija wersję, jeśli wymagane.
     *
     * Działa w oparciu o plik flagowy:
     * - Jeśli plik nie istnieje → publikuje zasoby i podbija wersję.
     * - Jeśli typ to 'release' lub 'build' → usuwa plik flagowy, by wymusić publikację.
     *
     * Publikowane tagi:
     * - {package}-assets
     * - {package}-theme-assets
     *
     * @param string $package Nazwa pakietu (np. 'simplo').
     * @param string $flagFile Ścieżka do pliku flagowego kontrolującego publikację.
     * @param string $bumpType Typ podbicia wersji: 'fix', 'build', 'release'. Domyślnie 'fix'.
     * @param bool $force Czy wymusić publikację zasobów (przekazywane do Artisan).
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
