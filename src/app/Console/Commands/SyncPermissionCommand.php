<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Console\Commands;

use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Services\RolePermissionManager;
use App\Services\PackageDataProvider;
use RuntimeException;

/**
 * Class SyncPermissionCommand
 *
 * Komenda CLI synchronizująca wszystkie trasy aplikacji z tabelą permissions
 * oraz przypisująca je do ról zgodnie z konfiguracją w pliku roles.json.
 *
 * Obsługuje opcjonalny parametr --guards=web,elmts,dbm do wskazania guardów.
 *
 * Przykłady użycia:
 * php simplo sync:permission
 * php simplo sync:permission --guards=web,smartpanel
 *
 * @package Simplysmart\Simplo\App\Console\Commands
 */
class SyncPermissionCommand implements CommandInterface
{
    /**
     * Obsługuje wykonanie komendy CLI.
     *
     * @param array $args Argumenty przekazane z CLI (np. ['--guards=web,elmts'])
     * @return void
     * @throws \Throwable
     */
    public function handle(array $args = []): void
    {
        // Ustal base path skeletona (Laravel)
        $basePath = function_exists('base_path') ? base_path() : dirname(__DIR__, 4);

        // Bootstrap aplikacji Laravel
        $app = require $basePath . '/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        /** @var RolePermissionManager $manager */
        $manager = $app->make(RolePermissionManager::class);

        // Pobierz konfigurację ról z pliku roles.json
        try {
            $rolesConfig = $app->make(PackageDataProvider::class)->get('smartdb-seeders::vendor/roles.json');
        } catch (RuntimeException $e) {
            echo "❌ Błąd ładowania pliku ról: " . $e->getMessage() . "\n";
            return;
        }

        // Guards z configu lub z parametru --guards
        $guards = config('simplo.seeder_guards', ['web']);
        $guardsOption = $this->extractOption($args, '--guards');
        if ($guardsOption) {
            $guards = array_map('trim', explode(',', $guardsOption));
        }

        // Pełna synchronizacja
        $manager->syncAllPermissionsAndRoles($rolesConfig, $guards);

        echo "✅ Synchronizacja permissions i ról zakończona dla guardów: " . implode(',', $guards) . "\n";
    }

    /**
     * Pobiera wartość opcji CLI w formacie --klucz=wartość.
     *
     * @param array $args Lista argumentów CLI
     * @param string $key Nazwa opcji (np. '--guards')
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
}
