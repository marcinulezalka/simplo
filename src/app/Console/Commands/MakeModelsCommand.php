<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */


namespace Simplysmart\Simplo\App\Console\Commands;

use Doctrine\DBAL\Exception;
use Simplysmart\Simplo\App\Contracts\CommandInterface;
use Simplysmart\Simplo\App\Generators\ModelGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Class MakeModelsCommand
 *
 * Generuje modele Eloquent na podstawie schematu bazy danych.
 *
 * @package Simplysmart\Simplo\App\Console\Commands
 * @author Marcin Ulezalka
 * @version 1.0.0
 */
class MakeModelsCommand implements CommandInterface
{
    /**
     * @throws Exception
     */
    public function handle(array $args = []): void
    {
        $options = $this->parseArgs($args);

        $connection = $options['--connection'] ?? 'mysql';
        $table = $options['table'] ?? null;
        $module = $options['--module'] ?? null;
        $all = in_array('--all', $args);

        $outputPath = $module
            ? base_path("Modules/$module/Models")
            : app_path('Models');

        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $generator = new ModelGenerator($connection, $outputPath);

        if ($table) {
            echo "🔧 Generuję model dla tabeli: $table\n";
            $generator->saveModel($table);
            return;
        }

        if ($all) {
            echo "🔄 Generuję modele dla wszystkich tabel w połączeniu: $connection\n";
            $schema = DB::connection($connection)->getDoctrineSchemaManager();
            $tables = $schema->listTableNames();

            foreach ($tables as $tableName) {
                $generator->saveModel($tableName);
            }

            return;
        }

        echo "⚠️ Musisz podać nazwę tabeli lub użyć --all\n";
    }

    private function parseArgs(array $args): array
    {
        $parsed = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                [$key, $value] = array_pad(explode('=', $arg, 2), 2, true);
                $parsed[$key] = $value;
            } else {
                $parsed['table'] = $arg;
            }
        }

        return $parsed;
    }
}
