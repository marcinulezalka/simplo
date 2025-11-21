<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Generator klas modeli Eloquent na podstawie schematu bazy danych.
 * Obsługuje generowanie: table, primaryKey, fillable, casts, attributes, hidden, SoftDeletes.
 *
 * @package Simplysmart\Simplo\App\Generators
 */
class ModelGenerator
{
    /**
     * Nazwa połączenia bazodanowego.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Ścieżka docelowa do zapisu wygenerowanych modeli.
     *
     * @var string
     */
    protected string $outputPath;

    /**
     * Konfiguracja wykluczeń pól dla poszczególnych sekcji modelu.
     *
     * @var array
     */
    protected array $excluded;

    /**
     * Konstruktor generatora modeli.
     *
     * @param string $connection Nazwa połączenia (domyślnie: mysql)
     * @param string $outputPath Ścieżka do katalogu docelowego
     */
    public function __construct(string $connection = 'mysql', string $outputPath = '')
    {
        $this->connection = $connection;
        $this->outputPath = $outputPath;
        $this->excluded = config('simplo.exclude', []);
    }

    /**
     * Generuje kod PHP modelu Eloquent na podstawie struktury tabeli.
     *
     * @param string $table Nazwa tabeli
     * @return string Kod źródłowy modelu
     * @noinspection PhpExpressionWithSameOperandsInspection
     * @noinspection PhpRedundantOptionalArgumentInspection
     */
    public function generateModel(string $table): string
    {
        $columns = DB::connection($this->connection)->select("SHOW FULL COLUMNS FROM `$table`");
        $primaryKey = $this->getPrimaryKey($table);

        $fillable = [];
        $attributes = [];
        $hidden = [];

        $excludeFillable   = $this->excluded['fillable'] ?? [];
        $excludeAttributes = $this->excluded['attributes'] ?? [];
        $excludeHidden     = $this->excluded['hidden'] ?? [];

        foreach ($columns as $column) {
            $name = $column->Field;
            $default = $column->Default;

            if ($name === $primaryKey) {
                continue;
            }

            if (!in_array($name, $excludeFillable)) {
                $fillable[] = $name;
            }

            if (!is_null($default) && !in_array($name, $excludeAttributes)) {
                $attributes[$name] = is_numeric($default) ? $default : $default;
            }

            if (in_array($name, ['password', 'remember_token']) && !in_array($name, $excludeHidden)) {
                $hidden[] = $name;
            }
        }

        $casts = $this->generateCasts($columns);

        $className = Str::studly(Str::singular($table));
        $namespace = 'App\\Models';

        return $this->renderTemplate([
            'namespace'   => $namespace,
            'phpdoc'      => $this->generatePhpDoc($columns),
            'class'       => $className,
            'connection'  => $this->connection,
            'table'       => $table,
            'primaryKey'  => $primaryKey,
            'fillable'    => $this->formatArray($fillable),
            'hidden'      => $this->formatArray($hidden),
            'attributes'  => $this->formatAttributesArray($attributes),
            'casts'       => $this->formatAssocArray($casts, true),
        ]);
    }

    /**
     * Pobiera nazwę kolumny będącej kluczem głównym.
     *
     * @param string $table Nazwa tabeli
     * @return string Nazwa kolumny klucza głównego
     */
    protected function getPrimaryKey(string $table): string
    {
        $keys = DB::connection($this->connection)->select("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        return $keys[0]->Column_name ?? 'id';
    }

    /**
     * Generuje tablicę rzutowań typów pól dla modelu.
     *
     * @param array $columns Lista kolumn z SHOW COLUMNS
     * @return array Tablica rzutowań ['nazwa_pola' => 'typ']
     */
    protected function generateCasts(array $columns): array
    {
        $casts = [];
        $excludeCasts = $this->excluded['casts'] ?? [];

        foreach ($columns as $column) {
            $name = $column->Field;
            $type = $column->Type;

            if (in_array($name, $excludeCasts)) {
                continue;
            }

            $casts[$name] = match (true) {
                str_starts_with($type, 'enum(') => 'string',
                str_contains($type, 'tinyint') && Str::startsWith($name, 'is_') => 'boolean',
                str_contains($type, 'bool') => 'boolean',
                str_contains($type, 'int') => 'integer',
                str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal') => 'float',
                str_contains($type, 'json') || (str_contains($type, 'text') && Str::contains($name, 'json')) => 'array',
                str_contains($type, 'datetime') || str_contains($type, 'timestamp') => 'datetime',
                default => 'string',
            };
        }

        return $casts;
    }

    /**
     * Renderuje szablon modelu z podstawionymi danymi.
     *
     * @param array $data Dane do podstawienia w szablonie
     * @return string Wygenerowany kod PHP
     */
    protected function renderTemplate(array $data): string
    {
        $templatePath = config('simplo.model_template_path');

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
     * Zapisuje wygenerowany model do pliku.
     *
     * @param string $table Nazwa tabeli
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

    /**
     * Formatuje zwykłą tablicę jako string PHP array.
     *
     * @param array $items Lista wartości
     * @return string Tablica w formacie PHP
     */
    protected function formatArray(array $items): string
    {
        return '[' . implode(', ', array_map(fn($item) => "'$item'", $items)) . ']';
    }

    /**
     * Formatuje tablicę asocjacyjną jako string PHP array.
     *
     * @param array $assoc Tablica asocjacyjna
     * @param bool $quoteValues Czy otaczać wartości apostrofami
     * @return string Tablica w formacie PHP
     */
    protected function formatAssocArray(array $assoc, bool $quoteValues = true): string
    {
        $pairs = [];

        foreach ($assoc as $key => $value) {
            $val = $quoteValues ? "'$value'" : $value;
            $pairs[] = "'$key' => $val";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Formatuje tablicę domyślnych atrybutów jako PHP array string.
     * Stringi będą w apostrofach, liczby bez.
     *
     * @param array $assoc
     * @return string
     */
    protected function formatAttributesArray(array $assoc): string
    {
        $pairs = [];

        foreach ($assoc as $key => $value) {
            $val = is_numeric($value) ? $value : "'$value'";
            $pairs[] = "'$key' => $val";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Generuje PHPDoc z adnotacjami property na podstawie kolumn tabeli.
     *
     * @param array $columns
     * @return string
     */
    protected function generatePhpDoc(array $columns): string
    {
        $lines = [];

        foreach ($columns as $column) {
            $name = $column->Field;
            $type = $this->mapToPhpType($column->Type, $name);
            $comment = trim($column->Comment ?? '');

            $line = " * @property $type \$$name";
            if ($comment !== '') {
                $line .= " $comment";
            }

            $lines[] = $line;
        }

        return "*\n" . implode("\n", $lines) . "\n *";
    }

    /**
     * Mapuje typ SQL na typ PHP dla adnotacji.
     *
     * @param string $sqlType
     * @param string $name
     * @return string
     */
    protected function mapToPhpType(string $sqlType, string $name): string
    {
        return match (true) {
            str_contains($sqlType, 'int') => Str::startsWith($name, 'is_') ? 'boolean' : 'integer',
            str_contains($sqlType, 'bool') => 'boolean',
            str_contains($sqlType, 'float'), str_contains($sqlType, 'double'), str_contains($sqlType, 'decimal') => 'float',
            str_contains($sqlType, 'datetime'), str_contains($sqlType, 'timestamp') => '\Carbon\Carbon|null',
            str_contains($sqlType, 'date') => '\Carbon\Carbon|null',
            str_contains($sqlType, 'json') => 'array',
            default => 'string',
        };
    }

}
