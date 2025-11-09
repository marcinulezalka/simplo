<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Console;

use Simplysmart\Simplo\App\Contracts\CommandInterface;

/**
 * Class Kernel
 *
 * Dispatcher CLI dla komend Simplo. Odpowiada za mapowanie komend tekstowych
 * na konkretne klasy wykonawcze oraz ich uruchamianie.
 *
 * @package Simplysmart\Simplo\App\Console
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class Kernel
{
    /**
     * Mapa dostępnych komend CLI i ich klas obsługujących.
     *
     * @var array<string, class-string<CommandInterface>>
     */
    protected array $commands = [
        'make:models' => \Simplysmart\Simplo\App\Console\Commands\MakeModelsCommand::class,
        'update' => \Simplysmart\Simplo\App\Console\Commands\UpdateCommand::class,
        'clear:env' => \Simplysmart\Simplo\App\Console\Commands\ClearEnvCommand::class,
        'publish:launcher' => \Simplysmart\Simplo\App\Console\Commands\PublishLauncherCommand::class,
    ];

    /**
     * Uruchamia odpowiednią komendę na podstawie wejścia CLI.
     *
     * @param string $command Nazwa komendy (np. 'make:models').
     * @param array $args Argumenty przekazane z CLI (np. ['--all', '--module=module1']).
     * @return void
     */
    public function dispatch(string $command, array $args = []): void
    {
        if (!isset($this->commands[$command])) {
            echo "❌ Nieznana komenda: $command\n";
            echo "ℹ️ Użyj 'php simplo help' aby zobaczyć dostępne komendy.\n";
            return;
        }

        $handler = app($this->commands[$command]);

        if (!$handler instanceof CommandInterface) {
            echo "❌ Komenda '$command' nie implementuje CommandInterface.\n";
            return;
        }

        $handler->handle($args);
    }
}
