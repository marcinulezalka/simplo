<?php

namespace Simplysmart\Simplo\App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

/**
 * Class SimploProvider
 *
 * Provider odpowiedzialny za rejestrację usług i bootstrapping modułu Simplo.
 * Może być używany do rejestrowania komend, helperów, aliasów, eventów, itp.
 *
 * @package Simplysmart\Simplo\App\Providers
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class SimploProvider extends ServiceProvider
{
    /**
     * Rejestruje bindingi w kontenerze aplikacji.
     *
     * @return void
     */
    public function register(): void
    {
        // Możesz tutaj rejestrować singletony, helpery, aliasy, itp.
        // Przykład:
        // $this->app->singleton(SimploService::class, function () {
        //     return new SimploService();
        // });
    }

    /**
     * Bootstrapping usług po załadowaniu wszystkich providerów.
     *
     * @param Router $router Router Laravelowy – dostępny do rejestracji tras.
     * @return void
     */
    public function boot(Router $router): void
    {
        // Możesz tutaj rejestrować trasy, eventy, publikacje plików, itp.
        // Przykład:
        // $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
