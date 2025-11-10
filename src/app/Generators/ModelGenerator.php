<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
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
    protected AbstractSchemaManager $schema;

    public function __construct(string $connection = 'mysql', string $outputPath = '')
    {
        $this->connection = $connection;
        $this->outputPath = $outputPath;

        /** @var AbstractSchemaManager $schema */
        $schema = DB::connection($this->connection)->getDoctrineSchemaManager();
        $this->schema = $schema;
    }

    /**
     * Generuje model na podstawie jednej tabeli.
     *
     * @param string $table
     * @return string
     * @throws Exception
     */
    public function generateModel(string $table): string
    {
        $tableDetails = $this->schema->introspectTable($table);
        $columns = $tableDetails->getColumns();
        $primaryKey = $tableDetails->getPrimaryKey()?->getColumns()[0] ?? 'id';

        $fillable = [];
        $casts = [];
        $attributes = [];

        foreach ($columns as $column) {
            $name = $column->getName();

            if ($name === $primaryKey) {
                continue;
            }

            $fillable[] = $name;

            $type = $column->getType()->getName();
            $default = $column->getDefault();

            $casts[$name] = match (true) {
                in_array($type, ['boolean', 'tinyint']), Str::startsWith($name, 'is_') => 'boolean',
                in_array($type, ['json', 'text']) && Str::contains($name, 'json') => 'array',
                in_array($type, ['integer', 'bigint', 'smallint']) => 'int',
                in_array($type, ['decimal', 'float', 'double']) => 'float',
                in_array($type, ['datetime', 'timestamp']) => 'datetime',
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
     * @throws Exception
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
