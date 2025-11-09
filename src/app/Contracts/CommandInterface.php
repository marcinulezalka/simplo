<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */


namespace Simplysmart\Simplo\App\Contracts;

/**
 * Interface CommandInterface
 *
 * Kontrakt dla każdej komendy CLI w module Simplo.
 * Każda komenda musi implementować metodę handle(), która przyjmuje argumenty z CLI.
 *
 * @package Simplysmart\Simplo\App\Contracts
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
interface CommandInterface
{
    /**
     * Obsługuje wykonanie komendy CLI.
     *
     * @param array $args Argumenty przekazane z linii poleceń (np. ['--all', '--module=module1']).
     * @return void
     */
    public function handle(array $args = []): void;
}
