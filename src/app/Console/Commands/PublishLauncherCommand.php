<?php

namespace Simplysmart\Simplo\App\Console\Commands;

use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Services\SimploService;

/**
 * Class PublishLauncherCommand
 *
 * Komenda CLI publikująca plik uruchamiający `simplo` do katalogu głównego skeletona.
 * Umożliwia łatwe wywoływanie poleceń Simplo z poziomu projektu Laravel.
 *
 * @package Simplysmart\Simplo\App\Console\Commands
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class PublishLauncherCommand implements CommandInterface
{
    /**
     * Wykonuje publikację pliku `simplo` do katalogu głównego aplikacji.
     *
     * @param array $args Argumenty z CLI (nie są wymagane dla tej komendy).
     * @return void
     */
    public function handle(array $args = []): void
    {
        SimploService::publishLauncher();
    }
}
