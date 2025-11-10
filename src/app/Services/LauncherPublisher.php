<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services;

/**
 * Class LauncherPublisher
 *
 * Odpowiada za publikację pliku `simplo` do katalogu głównego aplikacji.
 *
 * @package Simplysmart\Simplo\App\Services
 */
class LauncherPublisher
{
    /**
     * Publikuje plik uruchamiający `simplo` do katalogu głównego aplikacji Laravel.
     *
     * Kopiuje plik z katalogu vendora do katalogu głównego skeletona,
     * nadaje uprawnienia do uruchamiania i informuje o statusie.
     *
     * @return void
     */
    public static function publishLauncher(): void
    {
        $source = base_path('vendor/simplysmart/simplo/simplo');
        $target = base_path('simplo');

        if (!file_exists($source)) {
            echo "❌ Nie znaleziono pliku źródłowego: $source\n";
            return;
        }

        if (!file_exists($target)) {
            copy($source, $target);
            chmod($target, 0755);
            echo "✅ Plik 'simplo' został opublikowany w katalogu głównym aplikacji.\n";
        } else {
            echo "ℹ️ Plik 'simplo' już istnieje – pomijam kopiowanie.\n";
        }
    }
}
