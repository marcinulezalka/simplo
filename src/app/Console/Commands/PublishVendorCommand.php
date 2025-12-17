<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 * Simplo 12
 */

namespace Simplysmart\Simplo\App\Console\Commands;

use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Services\VendorPublisherService;

/**
 * Class PublishVendorCommand
 *
 * Komenda CLI odpowiedzialna za publikację bibliotek vendor
 * do katalogu public/vendor.
 */
class PublishVendorCommand implements CommandInterface
{
    protected VendorPublisherService $publisher;

    public function __construct(?VendorPublisherService $publisher = null)
    {
        $this->publisher = $publisher ?? new VendorPublisherService();
    }

    /**
     * Uruchamia komendę publish:vendor.
     *
     * @param array $args Argumenty przekazane z CLI (opcjonalne).
     * @return void
     */
    public function handle(array $args = []): void
    {
        echo "📦 Publikacja bibliotek vendor...\n";
        $this->publisher->publish();
        echo "🏁 Operacja publish:vendor zakończona.\n";
    }
}
