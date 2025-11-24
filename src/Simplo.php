<?php

namespace Simplysmart\Simplo;

use Simplysmart\Simplo\App\Console\Kernel;

/**
 * Class Simplo
 *
 * Główna klasa uruchamiająca CLI dla modułu Simplo.
 * Odpowiada za bootstrapowanie aplikacji Laravel oraz delegowanie komend do Kernel.
 *
 * @package Simplysmart\Simplo
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class Simplo
{
    /**
     * Uruchamia CLI na podstawie argumentów z linii poleceń.
     *
     * @param array $argv Argumenty wejściowe z CLI (np. ['simplo', 'make:models', '--all']).
     * @return void
     */
    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        if (in_array($command, ['help', '--help', '-h'])) {
            $this->showHelp();
            return;
        }

        try {
            (new Kernel())->dispatch($command, $args);
        } catch (\Throwable $e) {
            echo "❌ Błąd: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Wyświetla pomoc CLI z listą dostępnych komend.
     *
     * @return void
     */
    private function showHelp(): void
    {
        echo "\n📘 SIMPLO - dostępne komendy:\n\n";
        echo "  php simplo make:models [table] [--module=module1]   - generuje modele na podstawie bazy danych\n";
        echo "  php simplo make:request [table] [--module=module1]  - generuje FormRequesty na podstawie bazy danych\n";
        echo "  php simplo make:lang [table] [--module=module1]     - generuje pliki lang na podstawie schematu bazy danych\n";
        echo "      --all                                           - generuje pliki lang dla wszystkich tabel\n";
        echo "      --connection=mysql                              - (opcjonalnie) wskazuje połączenie bazodanowe\n";
        echo "  php simplo update [package] [type]                  - aktualizuje wersję i publikuje zasoby\n";
        echo "  php simplo clear:env                                - czyści cache/config/route/view\n";
        echo "  php simplo publish:launcher [--force]               - publikuje plik uruchamiający do skeletona\n";
        echo "  php simplo theme:publish                            - publikuje zasoby motywów do katalogu public\n";
        echo "      --system=web                                    - (opcjonalnie) system, np. web, smartpanel\n";
        echo "      --theme=tabler                                  - (opcjonalnie) motyw do publikacji\n";
        echo "      --no-header                                     - (opcjonalnie) pomija nagłówki logów\n";
        echo "  php simplo help                                     - pokazuje tę pomoc\n\n";
    }

}
