<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class ModelGenerator
 *
 * Generator klas modeli Eloquent na podstawie schematu bazy danych.
 * Obsługuje generowanie: table, primaryKey, fillable, casts, attributes, hidden, SoftDeletes.
 *
 * @package Simplysmart\Simplo\App\Generators
 */
class ModelGenerator
{
    protected string $connection;
    protected string $outputPath;

    public function __construct(string $connection = 'mysql', string $outputPath = '')
    {
        $this->connection = $connection;
        $this->outputPath = $outputPath;
    }

    /**
     * Generuje model na podstawie jednej tabeli.
     *
     * @param string $table
     * @return string
     */
    public function generateModel(string $table): string
    {
        $columns = DB::connection($this->connection)->select("SHOW COLUMNS FROM `$table`");
        $primaryKey = $this->getPrimaryKey($table);

        $fillable = [];
        $casts = [];
        $attributes = [];

        foreach ($columns as $column) {
            $name = $column->Field;
            $type = $column->Type;
            $default = $column->Default;

            if ($name === $primaryKey) {
                continue;
            }

            $fillable[] = $name;

            $casts[$name] = match (true) {
                str_contains($type, 'int') => 'int',
                str_contains($type, 'bool') || Str::startsWith($name, 'is_') => 'boolean',
                str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal') => 'float',
                str_contains($type, 'json') || (str_contains($type, 'text') && Str::contains($name, 'json')) => 'array',
                str_contains($type, 'datetime') || str_contains($type, 'timestamp') => 'datetime',
                default => 'string',
            };

            if (!is_null($default)) {
                $attributes[$name] = is_numeric($default) ? $default : "'$default'";
            }
        }

        $className = Str::studly(Str::singular($table));
        $namespace = 'App\\Models';

        return $this->renderTemplate([
            'namespace'   => $namespace,
            'class'       => $className,
            'connection'  => $this->connection,
            'table'       => $table,
            'primaryKey'  => $primaryKey,
            'fillable'    => var_export($fillable, true),
            'attributes'  => var_export($attributes, true),
            'casts'       => var_export($casts, true),
        ]);
    }

    /**
     * Pobiera nazwę klucza głównego z tabeli.
     *
     * @param string $table
     * @return string
     */
    protected function getPrimaryKey(string $table): string
    {
        $keys = DB::connection($this->connection)->select("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        return $keys[0]->Column_name ?? 'id';
    }

    /**
     * Renderuje szablon modelu z podstawionymi danymi.
     *
     * @param array $data
     * @return string
     */
    protected function renderTemplate(array $data): string
    {
        $templatePath = base_path('resources/templates/model.php.stub');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Brakuje szablonu: $templatePath");
        }

        $template = file_get_contents($templatePath);

        foreach ($data as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', $value, $template);
        }

        return $template;
    }

    /**
     * Zapisuje model do pliku.
     *
     * @param string $table
     * @return void
     */
    public function saveModel(string $table): void
    {
        $className = Str::studly(Str::singular($table));
        $path = rtrim($this->outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "$className.php";

        if (file_exists($path)) {
            echo "ℹ️ Model $className już istnieje – pomijam.\n";
            return;
        }

        file_put_contents($path, $this->generateModel($table));
        echo "✅ Wygenerowano model: $className\n";
    }
}
