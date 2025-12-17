<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 * Simplo 12
 */

namespace Simplysmart\Simplo\App\Contracts;

/**
 * Interface ProgressReporterInterface
 * Umożliwia raportowanie postępu w różnych implementacjach (CLI, logi, UI).
 */
interface ProgressReporterInterface
{
    public function report(int $done, int $total, string $label = ''): void;
}
