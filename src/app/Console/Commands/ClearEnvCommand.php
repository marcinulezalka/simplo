<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Console\Commands;

use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Services\EnvCleanerService;

/**
 * Class ClearEnvCommand
 *
 * Komenda CLI czyszcząca cache/config/route/view oraz dump-autoload.
 *
 * @package Simplysmart\Simplo\App\Console\Commands
 */
class ClearEnvCommand implements CommandInterface
{
    public function handle(array $args = []): void
    {
        EnvCleanerService::clearAll();
    }
}
