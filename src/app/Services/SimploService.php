<?php

namespace Simplysmart\Simplo\App\Services;

/**
 * Class SimploService
 *
 * Serwis pomocniczy dla CLI Simplo. Zawiera metody wspólne dla komend,
 * takie jak progress bar czy publikacja plików.
 *
 * @package Simplysmart\Simplo\App\Services
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class SimploService
{
    /**
     * Wyświetla postęp w formie prostego progress bara w konsoli.
     *
     * @param int $done Liczba wykonanych jednostek (np. procent, plików).
     * @param int $total Całkowita liczba jednostek (np. 100).
     * @param string $label Opis aktualnej operacji (np. 'Kopiowanie plików').
     * @return void
     */
    public static function progressBar(int $done, int $total, string $label = ''): void
    {
        $width = 30;
        $perc = ($total > 0) ? round(($done / $total) * 100) : 100;
        $bars = ($total > 0) ? floor(($done / $total) * $width) : $width;
        $line = str_repeat('=', $bars);
        $space = str_repeat(' ', $width - $bars);
        $percentStr = str_pad($perc, 3, ' ', STR_PAD_LEFT);
        printf("\r[%s%s] %s%% %s", $line, $space, $percentStr, $label);
        if ($done === $total) {
            echo " ✅\n";
        }
    }

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
