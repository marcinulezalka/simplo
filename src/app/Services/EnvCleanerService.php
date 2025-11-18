<?php

namespace Simplysmart\Simplo\App\Services;

/**
 * Class EnvCleanerService
 *
 * Czyści środowisko Laravel: cache, config, route, view oraz dump-autoload.
 *
 * @package Simplysmart\Simplo\App\Services
 */
class EnvCleanerService
{
    /**
     * Wykonuje czyszczenie środowiska Laravel z wizualnym postępem.
     *
     * @return void
     */
    public static function clearAll(): void
    {
        $steps = [
            ['cmd' => 'php artisan cache:clear',  'desc' => '🧹 Clearing application cache'],
            ['cmd' => 'php artisan config:clear', 'desc' => '⚙️ Clearing config cache'],
            ['cmd' => 'php artisan route:clear',  'desc' => '🛣️ Clearing route cache'],
            ['cmd' => 'php artisan view:clear',   'desc' => '🖼️ Clearing view cache'],
        ];

        echo "🧼 Laravel environment cleanup in progress...\n";

        foreach ($steps as $step) {
            echo $step['desc'] . "\n";
            for ($i = 50; $i <= 100; $i++) {
                usleep(1000);
                ProgressService::show($i, 100, $step['desc']);
            }
            passthru($step['cmd']);
        }

        echo "✅ All Laravel caches have been cleared!\n\n";

//        echo "📦 Regenerating Composer autoload files...\n";
//        for ($i = 0; $i <= 100; $i += 10) {
//            usleep(15000);
//            ProgressService::show($i, 100, "composer dump-autoload");
//        }
//        passthru('composer dump-autoload');

//        echo "✅ All Laravel caches have been cleared and autoload regenerated!\n\n";
    }
}
