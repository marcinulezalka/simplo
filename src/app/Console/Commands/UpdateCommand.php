<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */


namespace Simplysmart\Simplo\App\Console\Commands;

use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Services\ComposerUpdateService;

/**
 * Class UpdateCommand
 *
 * Komenda CLI uruchamiająca composer update i bump wersji pakietu.
 *
 * @package Simplysmart\Simplo\App\Console\Commands
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class UpdateCommand implements CommandInterface
{
    public function handle(array $args = []): void
    {
        [$bumpType, $package] = $this->parseArgs($args);

        echo "🔧 Composer update dla pakietu: $package (bump: $bumpType)\n";

        ComposerUpdateService::update($package, $bumpType);
    }

    protected function parseArgs(array $args): array
    {
        $validTypes = ['release', 'build', 'fix'];
        $first = $args[0] ?? '';
        $second = $args[1] ?? '';

        $bumpType = in_array($first, $validTypes) ? $first : 'fix';
        $package = in_array($first, $validTypes) ? ($second ?: 'simplo') : ($first ?: 'simplo');

        return [$bumpType, $package];
    }
}
