<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services;

use Illuminate\Filesystem\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

/**
 * Class DirectoryCopyWithProgressService
 *
 * Kopiuje katalog źródłowy do katalogu docelowego z paskiem postępu.
 * Obsługuje wykluczanie katalogów tymczasowych i tworzenie struktury docelowej.
 *
 * @package Simplysmart\Simplo\App\Services
 */
class DirectoryCopyWithProgressService
{
    protected Filesystem $fs;

    /**
     * Inicjalizuje usługę kopiowania katalogów.
     *
     * @param Filesystem|null $fs Instancja Laravelowego Filesystem (opcjonalna)
     */
    public function __construct(?Filesystem $fs = null)
    {
        $this->fs = $fs ?? new Filesystem();
    }

    /**
     * Kopiuje katalog źródłowy do katalogu docelowego z paskiem postępu.
     *
     * @param string $src Katalog źródłowy
     * @param string $dst Katalog docelowy
     * @param string $label Etykieta operacji (np. 'smartpanel:tabler')
     * @param array $excludeDirs Lista katalogów do pominięcia (np. ['views_temp'])
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

        $files = [];
        foreach ($rii as $item) {
            foreach ($excludeDirs as $exclude) {
                if (str_contains($item->getPathname(), DIRECTORY_SEPARATOR . $exclude . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            if ($item->isFile()) {
                $files[] = $item;
            }
        }

        $total = count($files);
        $done = 0;

        foreach ($files as $item) {
            $targetPath = $dst . DIRECTORY_SEPARATOR . ltrim(substr($item->getPathname(), strlen($src)), DIRECTORY_SEPARATOR);
            $this->fs->ensureDirectoryExists(dirname($targetPath));
            $this->fs->copy($item->getPathname(), $targetPath);
            $done++;
            ProgressService::show($done, $total, $label);
        }

        return $total;
    }
}
