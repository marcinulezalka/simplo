<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Throwable;


class RolePermissionManager
{
    /**
     * Zapewnia istnienie jawnych (explicit) uprawnień dla wskazanego guarda.
     *
     * Pomija wzorce typu '*' oraz 'prefix.*'.
     *
     * @param array  $permissionNames Lista nazw uprawnień do utworzenia
     * @param string $guardName       Nazwa guarda (np. 'web', 'smartpanel')
     *
     * @return Collection<Permission> Kolekcja utworzonych lub istniejących modeli Permission
     */
    public function ensureExplicitPermissions(array $permissionNames, string $guardName): Collection
    {
        $created = collect();

        foreach ($permissionNames as $name) {
            // skip wildcards here
            if ($name === '*' || Str::endsWith($name, '.*')) {
                continue;
            }

            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]);

            $created->push($permission);
        }

        return $created;
    }

    /**
     * Rozwiązuje mieszane wzorce uprawnień (jawne + wildcardy) do modeli Permission dla danego guarda.
     *
     * Obsługiwane wzorce:
     * - '*'            → wszystkie istniejące Permission dla guarda
     * - 'prefix.*'     → wszystkie Permission odpowiadające trasom zaczynającym się od podanego prefiksu
     * - jawna nazwa    → pojedyncze Permission, jawnie podane
     *
     * Dodatkowo:
     * - Jawne nazwy są tworzone przez ensureExplicitPermissions() jeśli nie istnieją.
     * - Wildcardy 'prefix.*' są rozwijane na podstawie nazw tras zarejestrowanych w Route::getRoutes().
     * - Brakujące wpisy Permission są tworzone defensywnie przez firstOrCreate().
     *
     * @param array  $permissionPatterns Lista wzorców uprawnień (np. ['smartpanel.settings.*', 'smartdb.dashboard.index'])
     * @param string $guardName          Nazwa guarda (np. 'web', 'smartpanel', 'smartdb')
     *
     * @return Collection<Permission> Kolekcja dopasowanych modeli Permission
     *
     * @throws Throwable W przypadku problemów z dostępem do tras lub bazy danych
     */
    public function resolvePermissions(array $permissionPatterns, string $guardName): Collection
    {
        $resolved = collect();

        // 1. Upewnij się, że jawne nazwy istnieją
        $this->ensureExplicitPermissions($permissionPatterns, $guardName);

        foreach ($permissionPatterns as $pattern) {
            if ($pattern === '*') {
                // wszystkie permissions dla guard
                $all = Permission::where('guard_name', $guardName)->get();
                $resolved = $resolved->merge($all);
                continue;
            }

            if (Str::endsWith($pattern, '.*')) {
                $prefix = rtrim($pattern, '.*');

                // znajdź wszystkie trasy zaczynające się od prefix
                $routeNames = collect(Route::getRoutes())
                    ->map(fn($route) => $route->getName())
                    ->filter(fn($name) => $name && Str::startsWith($name, $prefix . '.'));

                if ($routeNames->isEmpty()) {
                    Log::warning("RolePermissionManager: wildcard pattern '{$pattern}' nie dopasował żadnych tras dla guard '{$guardName}'");
                }

                $matches = $routeNames->map(function ($name) use ($guardName) {
                    return Permission::firstOrCreate([
                        'name' => $name,
                        'guard_name' => $guardName,
                    ]);
                });

                $resolved = $resolved->merge($matches);
                continue;
            }

            // jawne permission (powinno istnieć po ensureExplicitPermissions)
            $perm = Permission::where('guard_name', $guardName)
                ->where('name', $pattern)
                ->first();

            if($perm) {
                $resolved->push($perm);
            } else {
                // defensywnie utwórz brakujące
                $perm = Permission::firstOrCreate([
                    'name' => $pattern,
                    'guard_name' => $guardName,
                ]);
                $resolved->push($perm);
            }
        }

        return $resolved->unique('id')->values();
    }

    /**
     * Zapewnia istnienie roli dla wskazanego guarda i przypisuje jej podane uprawnienia.
     *
     * Uprawnienia mogą być przekazane jako:
     * - kolekcja modeli Permission
     * - tablica nazw uprawnień
     *
     * @param string              $roleName    Nazwa roli
     * @param string              $guardName   Nazwa guarda
     * @param array|Collection    $permissions Kolekcja modeli Permission lub tablica nazw
     *
     * @return Role Utworzony lub istniejący model Role z przypisanymi uprawnieniami
     */
    public function ensureRoleWithPermissions(string $roleName, string $guardName, array|Collection $permissions): Role
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => $guardName,
        ]);

        // normalize permissions to Permission models
        $perms = $permissions instanceof Collection ? $permissions : collect($permissions);

        // if permissions are names, resolve to models
        if ($perms->isNotEmpty() && is_string($perms->first())) {
            $perms = collect($perms->map(function ($name) use ($guardName) {
                return Permission::firstOrCreate(['name' => $name, 'guard_name' => $guardName]);
            }));
        }

        foreach ($perms as $permission) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                Log::info("RolePermissionManager: assigned permission '{$permission->name}' to role '{$roleName}' [guard: {$guardName}]");
            }
        }

        return $role;
    }

    /**
     * Ułatwienie: tworzy rolę na podstawie wzorców uprawnień dla wielu guardów.
     *
     * @param string $roleName           Nazwa roli
     * @param array  $guards             Lista guardów
     * @param array  $permissionPatterns Lista wzorców uprawnień
     *
     * @return void
     *
     * @throws Throwable W przypadku problemów z dostępem do tras lub bazy danych
     */
    public function createRoleFromPatterns(string $roleName, array $guards, array $permissionPatterns): void
    {
        foreach ($guards as $guard) {
            $resolved = $this->resolvePermissions($permissionPatterns, $guard);
            $this->ensureRoleWithPermissions($roleName, $guard, $resolved);
        }
    }

    /**
     * Czyści cache uprawnień Spatie (operacja defensywna).
     *
     * @return void
     */
    public function clearPermissionCache(): void
    {
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (Throwable $e) {
            Log::warning('RolePermissionManager: failed to clear permission cache: ' . $e->getMessage());
        }
    }

    /**
     * Pełna synchronizacja: rejestruje wszystkie trasy jako Permission,
     * następnie przypisuje je do ról zgodnie z konfiguracją.
     *
     * Kroki:
     * 1. Utworzenie wpisów Permission dla wszystkich tras i guardów.
     * 2. Utworzenie ról i przypisanie im uprawnień na podstawie pliku konfiguracyjnego.
     * 3. Wyczyścienie cache Spatie.
     *
     * @param array $rolesConfig Konfiguracja ról (np. z pliku roles.json)
     * @param array $guards      Lista guardów do obsługi (np. ['web','smartpanel','smartdb'])
     *
     * @return void
     *
     * @throws Throwable W przypadku problemów z dostępem do tras lub bazy danych
     */
    public function syncAllPermissionsAndRoles(array $rolesConfig, array $guards = ['web']): void
    {
        // 1. Zarejestruj wszystkie trasy jako Permission dla każdego guard
        foreach ($guards as $guard) {
            foreach (Route::getRoutes() as $route) {
                $name = $route->getName();
                if ($name) {
                    Permission::firstOrCreate([
                        'name' => $name,
                        'guard_name' => $guard,
                    ]);
                }
            }
        }

        // 2. Utwórz role i przypisz permissions zgodnie z configiem
        foreach ($rolesConfig['roles'] ?? [] as $roleName => $roleData) {
            $targetGuards = $roleData['guards'] ?? $guards;
            $permissions  = $roleData['permissions'] ?? [];

            $this->createRoleFromPatterns($roleName, $targetGuards, $permissions);
            Log::info("RolePermissionManager: synced role '{$roleName}' for guards: " . implode(',', $targetGuards));
        }

        // 3. Wyczyść cache Spatie
        $this->clearPermissionCache();
    }
}
