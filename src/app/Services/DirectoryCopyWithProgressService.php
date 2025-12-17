<?php

namespace Simplysmart\Simplo\App\Services;

use Illuminate\Filesystem\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Simplysmart\Simplo\App\Contracts\ProgressReporterInterface;

/**
 * Class DirectoryCopyWithProgressService
 *
 * Kopiuje katalog źródłowy do katalogu docelowego z paskiem postępu.
 * Obsługuje wykluczanie katalogów tymczasowych i tworzenie struktury docelowej.
 */
class DirectoryCopyWithProgressService
{
    protected Filesystem $fs;
    protected ?ProgressReporterInterface $progressReporter;

    public function __construct(?Filesystem $fs = null, ?ProgressReporterInterface $progressReporter = null)
    {
        $this->fs = $fs ?? new Filesystem();
        $this->progressReporter = $progressReporter;
    }

    /**
     * Kopiuje katalog źródłowy do katalogu docelowego.
     *
     * @param string $src Katalog źródłowy
     * @param string $dst Katalog docelowy
     * @param string $label Etykieta operacji
     * @param array $excludeDirs Lista katalogów do pominięcia
     * @return int Liczba skopiowanych plików
     */
    public function copy(string $src, string $dst, string $label = '', array $excludeDirs = []): int
    {
        $src = rtrim($src, DIRECTORY_SEPARATOR);
        $dst = rtrim($dst, DIRECTORY_SEPARATOR);

        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $files = array_filter(iterator_to_array($rii), function ($item) use ($excludeDirs) {
            return $item->isFile() && !$this->shouldExclude($item->getPathname(), $excludeDirs);
        });

        $total = count($files);
        $done = 0;

        foreach ($files as $item) {
            $targetPath = $dst . DIRECTORY_SEPARATOR . ltrim(substr($item->getPathname(), strlen($src)), DIRECTORY_SEPARATOR);
            $this->fs->ensureDirectoryExists(dirname($targetPath));
            $this->fs->copy($item->getPathname(), $targetPath);
            $done++;

            $this->progressReporter?->report($done, $total, $label);
        }

        return $total;
    }

    private function shouldExclude(string $path, array $excludeDirs): bool
    {
        foreach ($excludeDirs as $exclude) {
            if (str_contains($path, DIRECTORY_SEPARATOR . $exclude . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }
        return false;
    }
}
