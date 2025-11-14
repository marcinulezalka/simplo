<?php
/*
 * Copyright (c) 2014–2025. simplySMART
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
     * @param bool $force Czy nadpisać istniejący plik.
     * @return void
     */
    public static function publishLauncher(bool $force = false): void
    {
        $source = getcwd() . '/vendor/simplysmart/simplo/simplo';
        $target = getcwd() . '/simplo';

        if (!file_exists($source)) {
            echo "❌ Nie znaleziono pliku źródłowego: $source\n";
            return;
        }

        if (file_exists($target) && !$force) {
            echo "ℹ️ Plik 'simplo' już istnieje – pomijam kopiowanie. Użyj --force, aby nadpisać.\n";
            return;
        }

        if (!@copy($source, $target)) {
            echo "❌ Błąd podczas kopiowania pliku do: $target\n";
            return;
        }

        chmod($target, 0755);
        echo "✅ Plik 'simplo' został opublikowany do katalogu głównego aplikacji.\n";
    }
}
