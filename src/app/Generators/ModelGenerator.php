<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Illuminate\Support\Str;
use Simplysmart\Simplo\App\Services\SchemaInspector;

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
        $this->excluded   = config('simplo.exclude', []);
    }

    /**
     * Generuje kod PHP modelu Eloquent na podstawie struktury tabeli.
     *
     * @param string $table Nazwa tabeli
     * @return string Kod źródłowy modelu
     * @noinspection PhpRedundantOptionalArgumentInspection
     */
    public function generateModel(string $table): string
    {
        $inspector = new SchemaInspector($this->connection);

        $primaryKey = $inspector->getPrimaryKey($table);
        $fillable   = $inspector->getFillable($table);
        $attributes = $inspector->getAttributes($table);
        $hidden     = $inspector->getHidden($table);
        $casts      = $inspector->getCasts($table);
        $phpdoc     = $inspector->getPhpDoc($table);

        $className = Str::studly(Str::singular($table));
        $namespace = 'App\\Models';

        return $this->renderTemplate([
            'namespace'   => $namespace,
            'phpdoc'      => $phpdoc,
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
}
