<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\Simplysmart;

use Simplysmart\Simplo\App\Utils\VersionManager;
use Simplysmart\Simplo\App\Services\SimploService;

/**
 * 🚀 Klasa Simplo – prosty przykładowy CLI
 *
 * @author  Marcin Ulezalka
 * @version 1.0.13
 */
class Simplo extends SimploService
{
    /**
     * Nazwa vendora (np. simplysmart, itp.)
     * @var string
     */
    protected string $vendorName = 'simplysmart';

    /**
     * ▶️ Główna metoda uruchamiająca CLI.
     *
     * @param array $argv Argumenty wejściowe z linii poleceń.
     * @return void
     */
    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';

        switch ($command) {
            case 'update':
                // php simplo update [bumpType] [package]
                // bumpType: 'release' lub 'build' lub 'fix' (domyślnie 'fix')
                $bumpType = ($argv[2] ?? '');
                if (!in_array($bumpType, ['release', 'build', 'fix'])) {
                    $bumpType = 'fix';
                }
                $package = (in_array(($argv[2] ?? ''), ['release', 'build', 'fix']))
                    ? ($argv[3] ?? 'simplo')
                    : ($argv[2] ?? 'simplo');
                $this->composerUpdate($package, $bumpType);
                break;

            case 'clear:env':
                $this->clearEnvAll();
                break;

            // Dodaj kolejne komendy poniżej:
            // case 'deploy':
            //     $this->deploySomething();
            //     break;

            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }

    /**
     * 📦 Uruchamia composer update z przykładowym progress barem.
     *
     * @param string $package
     * @param string $bumpType 'release' lub 'build'
     * @return void
     */
    protected function composerUpdate(string $package, string $bumpType): void
    {
        echo "Preparing to run composer update for package: $package ...\n";
        $total = 100;
        for ($i = 0; $i <= 60; $i += 5) {
            SimploService::progressBar($i, $total, "composer update");
            usleep(40000);
        }
        echo "\nStart composer update...\n";
        passthru('composer update');
        SimploService::progressBar(100, $total, "composer update");

        echo "\nComposer update finished.\n";

        $flagFile = base_path("vendor/$this->vendorName/$package") . '/.published';
        VersionManager::handlePublishAndVersionBump(
            $package,
            $flagFile,
            $bumpType, // przekazujemy string 'build' lub 'release'
            true
        );
    }

    /**
     * ℹ️ Wyświetla dostępną pomoc i listę komend.
     *
     * @return void
     */
    private function showHelp(): void
    {
        echo "\n";
        echo "SIMPLO - $this->vendorName - dostępne komendy:\n\n";
        echo "  php simplo update [package]                - composer update + build (bump PATCH)\n";
        echo "  php simplo update fix [package]            - composer update + build\n";
        echo "  php simplo update build [package]          - composer update + build (bump PATCH)\n";
        echo "  php simplo update release [package]        - composer update + release (bump MINOR, reset PATCH)\n";
        echo "  php simplo clear:env                       - czyści cache/config/route/view w Laravelu\n";
        echo "  php simplo help                            - pokazuje tę pomoc\n";
        echo "\nPrzykłady:\n";
        echo "  php simplo update simplo\n";
        echo "  php simplo update fix simplo\n";
        echo "  php simplo update build simplo\n";
        echo "  php simplo update release simplo\n";
        echo "\n";
    }

    /**
     * 🧹 Clears all Laravel caches using Artisan commands with a visual progress bar.
     *
     * Ta funkcja uruchamia kolejno polecenia Artisan:
     *   🧹 php artisan cache:clear   (czyści application cache)
     *   ⚙️ php artisan config:clear  (czyści configuration cache)
     *   🛣️ php artisan route:clear   (czyści route cache)
     *   🖼️ php artisan view:clear    (czyści view cache)
     *   📦 composer dump-autoload    (regeneruje autoloader)
     *
     * Pokazuje progress bar dla każdego kroku przez progressBar().
     *
     * Przykład użycia:
     *   ./simplo clear:env -all
     *
     * @return void
     */
    private function clearEnvAll(): void
    {
        $steps = [
            ['cmd' => 'php artisan cache:clear',  'desc' => '🧹 Clearing application cache'],
            ['cmd' => 'php artisan config:clear', 'desc' => '⚙️ Clearing config cache'],
            ['cmd' => 'php artisan route:clear',  'desc' => '🛣️ Clearing route cache'],
            ['cmd' => 'php artisan view:clear',   'desc' => '🖼️ Clearing view cache'],
        ];

        echo "Laravel cache cleanup in progress...\n";

        foreach ($steps as $step) {
            echo $step['desc'] . "\n";
            for ($i = 50; $i <= 100; $i++) {
                usleep(1);
                SimploService::progressBar($i, 100, $step['desc']);
            }
            passthru($step['cmd']);
        }

        echo "📦 Regenerating Composer autoload files...\n";
        for ($i = 0; $i <= 100; $i += 10) {
            usleep(15000);
            SimploService::progressBar($i, 100, "composer dump-autoload");
        }
        passthru('composer dump-autoload');

        echo "✅ All Laravel caches have been cleared and autoload regenerated!\n\n";
    }
}
