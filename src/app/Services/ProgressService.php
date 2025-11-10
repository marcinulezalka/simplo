<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services;

/**
 * Class ProgressService
 *
 * Wyświetla prosty pasek postępu w konsoli CLI.
 *
 * @package Simplysmart\Simplo\App\Services
 */
class ProgressService
{
    /**
     * Wyświetla pasek postępu w konsoli.
     *
     * @param int $done Liczba wykonanych jednostek (np. procent).
     * @param int $total Całkowita liczba jednostek (np. 100).
     * @param string $label Opis aktualnej operacji (np. 'Kopiowanie plików').
     * @return void
     */
    public static function show(int $done, int $total, string $label = ''): void
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
}
