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
        'make:request' => \Simplysmart\Simplo\App\Console\Commands\MakeRequestCommand::class,
        'update' => \Simplysmart\Simplo\App\Console\Commands\UpdateCommand::class,
        'clear:env' => \Simplysmart\Simplo\App\Console\Commands\ClearEnvCommand::class,
        'publish:launcher' => \Simplysmart\Simplo\App\Console\Commands\PublishLauncherCommand::class,
        'theme:publish' => \Simplysmart\Simplo\App\Console\Commands\ThemePublishCommand::class,
    ];

    /**
     * Rejestruje nową komendę w dispatcherze.
     *
     * @param string $name
     * @param class-string<CommandInterface> $handler
     * @return void
     */
    public function register(string $name, string $handler): void
    {
        $this->commands[$name] = $handler;
    }

    /**
     * Sprawdza, czy dana komenda jest zarejestrowana.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Zwraca listę dostępnych komend.
     *
     * @return array<string>
     */
    public function list(): array
    {
        return array_keys($this->commands);
    }

    /**
     * Zwraca instancję klasy obsługującej daną komendę.
     *
     * @param string $name
     * @return CommandInterface|null
     */
    public function resolve(string $name): ?CommandInterface
    {
        if (!$this->has($name)) {
            return null;
        }

        $handler = app($this->commands[$name]);

        return $handler instanceof CommandInterface ? $handler : null;
    }

    /**
     * Uruchamia odpowiednią komendę na podstawie wejścia CLI.
     *
     * @param string $command
     * @param array $args
     * @return void
     */
    public function dispatch(string $command, array $args = []): void
    {
        $handler = $this->resolve($command);

        if (!$handler) {
            echo "❌ Nieznana komenda: $command\n";
            echo "ℹ️ Użyj 'php simplo help' aby zobaczyć dostępne komendy.\n";
            return;
        }

        $handler->handle($args);
    }
}
