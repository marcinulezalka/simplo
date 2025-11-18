<?php

/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Console\Commands;

use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Services\Theme\ThemePublisherService;

/**
 * Class ThemePublishCommand
 *
 * Komenda CLI publikująca zasoby motywów do katalogu public.
 * Obsługuje parametry --system, --theme oraz --no-header.
 * Wykorzystuje ThemePublisherService do kopiowania zasobów oraz czyszczenia środowiska.
 *
 * Przykłady użycia:
 * php simplo theme:publish
 * php simplo theme:publish --system=smartpanel --theme=tabler
 * php simplo theme:publish --no-header
 *
 * @package Simplysmart\Simplo\App\Console\Commands
 */
class ThemePublishCommand implements CommandInterface
{
    /**
     * Obsługuje wykonanie komendy CLI.
     *
     * @param array $args Argumenty przekazane z CLI (np. ['--system=web', '--theme=tabler', '--no-header'])
     * @return void
     */
    public function handle(array $args = []): void
    {
        // Laravelowe base_path() działa, jeśli Simplo uruchamiany jest w kontekście aplikacji
        $basePath = function_exists('base_path') ? base_path() : dirname(__DIR__, 4);

        $system = $this->extractOption($args, '--system');
        $theme = $this->extractOption($args, '--theme');
        $logHeader = !$this->hasFlag($args, '--no-header');

        $publisher = new ThemePublisherService($basePath);

        if ($system || $theme) {
            $publisher->publishSingle($theme, $system, $logHeader);
        } else {
            $publisher->publishAll();
        }
    }

    /**
     * Pobiera wartość opcji CLI w formacie --klucz=wartość.
     *
     * @param array $args Lista argumentów CLI
     * @param string $key Nazwa opcji (np. '--system')
     * @return string|null Wartość opcji lub null, jeśli nie znaleziono
     */
    private function extractOption(array $args, string $key): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, $key . '=')) {
                return substr($arg, strlen($key) + 1);
            }
        }
        return null;
    }

    /**
     * Sprawdza, czy flaga CLI została podana (np. --no-header).
     *
     * @param array $args Lista argumentów CLI
     * @param string $flag Nazwa flagi (np. '--no-header')
     * @return bool True, jeśli flaga została podana; false w przeciwnym razie
     * @noinspection PhpSameParameterValueInspection
     */
    private function hasFlag(array $args, string $flag): bool
    {
        return in_array($flag, $args, true);
    }
}
