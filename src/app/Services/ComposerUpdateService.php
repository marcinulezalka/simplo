<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services;

use Simplysmart\Simplo\App\Utils\VersionManager;

/**
 * Class ComposerUpdateService
 *
 * Odpowiada za wykonanie polecenia `composer update` oraz automatyczne
 * podbicie wersji pakietu i publikację plików.
 *
 * @package Simplysmart\Simplo\App\Services
 */
class ComposerUpdateService
{
    /**
     * Wykonuje `composer update` dla danego pakietu oraz obsługuje wersjonowanie.
     *
     * @param string $package Nazwa pakietu (np. 'simplo').
     * @param string $bumpType Typ podbicia wersji: 'fix', 'build', 'release'.
     * @param string $vendor Nazwa vendora (domyślnie 'simplysmart').
     * @return void
     */
    public static function update(string $package, string $bumpType, string $vendor = 'simplysmart'): void
    {
        echo "📦 Przygotowanie do composer update...\n";
        for ($i = 0; $i <= 60; $i += 5) {
            ProgressService::show($i, 100, "composer update");
            usleep(40000);
        }

        echo "\n▶️ Start composer update...\n";
        passthru('composer update');
        ProgressService::show(100, 100, "composer update");

        echo "\n✅ Composer update zakończony.\n";

        $flagFile = base_path("vendor/{$vendor}/{$package}/.published");

        VersionManager::handlePublishAndVersionBump(
            $package,
            $flagFile,
            $bumpType,
            true
        );
    }
}
