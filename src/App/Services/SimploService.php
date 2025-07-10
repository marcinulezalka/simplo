<?php

namespace Simplysmart\Simplo\App\Services;

class SimploService
{
    /**
     * Pokazuje postęp w formie progress bara.
     *
     * @param int $done   Liczba już skopiowanych plików/procent
     * @param int $total  Całkowita liczba plików/procent (np. 100)
     * @param string $pkg Nazwa paczki (do wyświetlenia)
     * @return void
     */
    public static function progressBar(int $done, int $total, string $pkg = ''): void
    {
        $width = 30;
        $perc = ($total > 0) ? round(($done / $total) * 100) : 100;
        $bars = ($total > 0) ? floor(($done / $total) * $width) : $width;
        $line = str_repeat('=', $bars);
        $space = str_repeat(' ', $width - $bars);
        $percentStr = str_pad($perc, 3, ' ', STR_PAD_LEFT);
        printf("\r[%s%s] %s%% %s", $line, $space, $percentStr, $pkg);
        if ($done === $total) {
            echo " ✅";
        }
    }
}
