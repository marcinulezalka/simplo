<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services\Theme;

use Illuminate\Filesystem\Filesystem;
use Simplysmart\Simplo\App\Services\DirectoryCopyWithProgressService;
use Simplysmart\Simplo\App\Services\EnvCleanerService;

/**
 * Class ThemePublisherService
 *
 * Publikuje zasoby motywów zdefiniowanych w config/themes.php do katalogu public/.
 * Obsługuje kopiowanie pojedynczych motywów lub wszystkich motywów w systemie.
 *
 * @package Simplysmart\Simplo\App\Services\Theme
 */
class ThemePublisherService
{
    protected string $basePath;
    protected Filesystem $fs;

    /**
     * Inicjalizuje usługę publikacji motywów.
     *
     * @param string $basePath Ścieżka bazowa aplikacji (np. base_path())
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->fs = new Filesystem();
    }

    /**
     * Publikuje wszystkie motywy dla wszystkich systemów z config/themes.php.
     *
     * @return void
     */
    public function publishAll(): void
    {
        $config = $this->loadConfig();

        foreach ($config as $system => $data) {
            foreach ($data['themes'] ?? [] as $theme => $themeConf) {
                $this->publishSingle($theme, $system);
            }
        }

        EnvCleanerService::clearAll();
    }

    /**
     * Publikuje wskazany motyw dla danego systemu.
     *
     * @param string|null $theme Nazwa motywu (np. 'tabler')
     * @param string|null $system Nazwa systemu (np. 'smartpanel')
     * @param bool $logHeader Czy wyświetlać nagłówek logów (domyślnie true)
     * @return void
     */
    public function publishSingle(?string $theme, ?string $system, bool $logHeader = true): void
    {
        $config = $this->loadConfig();

        if (!$system || !isset($config[$system])) {
            echo "❌ Nieznany system: {$system}\n";
            return;
        }

        $themes = $config[$system]['themes'] ?? [];

        if ($theme) {
            if (!isset($themes[$theme])) {
                echo "❌ Nieznany motyw: {$theme} dla systemu {$system}\n";
                return;
            }
            $this->publishTheme($system, $theme, $themes[$theme], $logHeader);
        } else {
            foreach ($themes as $themeName => $themeConf) {
                $this->publishTheme($system, $themeName, $themeConf, $logHeader);
            }
        }

        EnvCleanerService::clearAll();
    }

    /**
     * Publikuje zasoby pojedynczego motywu.
     *
     * @param string $system Nazwa systemu
     * @param string $theme Nazwa motywu
     * @param array $themeConf Konfiguracja motywu z config/themes.php
     * @param bool $logHeader Czy wyświetlać nagłówek logów
     * @return void
     */
    protected function publishTheme(string $system, string $theme, array $themeConf, bool $logHeader = true): void
    {
        $source = $this->basePath . '/' . ltrim($themeConf['source_path'] ?? '', '/\\');
        $target = $this->basePath . '/public/' . ltrim($themeConf['public_path'] ?? '', '/\\');

        if ($logHeader) {
            echo "\n📦 Publikacja motywu: {$theme} [{$system}]\n";
            echo "Źródło: {$source}\n";
            echo "Docelowo: {$target}\n";
        }

        if (!$this->fs->isDirectory($source)) {
            echo "❌ Katalog źródłowy nie istnieje: {$source}\n";
            return;
        }

        $copier = new DirectoryCopyWithProgressService($this->fs);
        $copier->copy($source, $target, "{$system}:{$theme}", ['views_temp']);
    }

    /**
     * Ładuje konfigurację motywów z config/themes.php.
     *
     * @return array
     */
    protected function loadConfig(): array
    {
        $configPath = $this->basePath . '/config/themes.php';
        return $this->fs->exists($configPath) ? require $configPath : [];
    }
}
