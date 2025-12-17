<?php
namespace Simplysmart\Simplo\App\Services;

use Simplysmart\Simplo\App\Services\DirectoryCopyWithProgressService;
use Illuminate\Filesystem\Filesystem;

class VendorPublisherService
{
    protected DirectoryCopyWithProgressService $copier;

    public function __construct(?DirectoryCopyWithProgressService $copier = null)
    {
        $this->copier = $copier ?? new DirectoryCopyWithProgressService(new Filesystem());
    }

    public function publish(): void
    {
        // skeleton paths
        $src = base_path('resources/vendor');
        $dst = public_path('vendor');

        if (!is_dir($src)) {
            echo "❌ Katalog źródłowy $src nie istnieje.\n";
            return;
        }

        echo "🚀 Publishing vendor libraries...\n";
        $total = $this->copier->copy($src, $dst, 'publish:vendor');

        echo "✅ $total libraries published to public/vendor!\n";

        echo "♻️ Clearing Laravel caches...\n";
        \Simplysmart\Simplo\App\Services\EnvCleanerService::clearAll();
        echo "✨ Laravel caches cleared. Environment is refreshed.\n";
    }
}
