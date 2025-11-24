<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Simplysmart\Simplo\App\Services\SchemaInspector;
use Illuminate\Support\Str;

/**
 * Class LangGenerator
 *
 * Generator plików tłumaczeń lang na podstawie schematu bazy danych.
 * Obsługuje:
 * - generowanie pliku lang dla pojedynczej tabeli,
 * - generowanie wpisów w sekcji 'text' na podstawie pól fillable,
 * - dodawanie PHPDoc z komentarzy kolumn.
 *
 * @package Simplysmart\Simplo\App\Generators
 */
class LangGenerator
{
    /**
     * Nazwa połączenia bazodanowego używanego do inspekcji schematu.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Ścieżka docelowa do zapisu wygenerowanych plików lang.
     *
     * @var string
     */
    protected string $outputPath;

    /**
     * Serwis SchemaInspector odpowiedzialny za analizę schematu bazy danych.
     *
     * @var SchemaInspector
     */
    protected SchemaInspector $inspector;

    /**
     * Konstruktor generatora plików lang.
     *
     * @param string $connection Nazwa połączenia bazodanowego (domyślnie: mysql).
     * @param string $outputPath Ścieżka docelowa do katalogu lang (domyślnie: lang/pl/simplo/).
     */
    public function __construct(string $connection = 'mysql', string $outputPath = '')
    {
        $this->connection = $connection;
        $this->outputPath = $outputPath ?: base_path('lang/pl/simplo/');
        $this->inspector  = new SchemaInspector($connection);
    }

    /**
     * Generuje kod pliku lang dla wskazanej tabeli.
     *
     * @param string $table Nazwa tabeli
     * @return string Kod PHP pliku lang
     */
    public function generateLang(string $table): string
    {
        $fillable = $this->inspector->getFillable($table);
        $phpdoc   = $this->inspector->getPhpDoc($table);

        // budujemy sekcję 'text'
        $fields = implode("\n", array_map(fn($f) => "        '$f' => '$f',", $fillable));

        $templatePath = config('simplo.lang_template_path');
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Brakuje szablonu: $templatePath");
        }

        $template = file_get_contents($templatePath);
        $template = str_replace('{{ table }}', $table, $template);
        $template = str_replace('{{ fields }}', $fields, $template);
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $template = str_replace('{{ phpdoc }}', $phpdoc, $template);

        return $template;
    }

    /**
     * Zapisuje plik lang do katalogu.
     *
     * @param string $table Nazwa tabeli
     * @return void
     */
    public function saveLang(string $table): void
    {
        $fileName = Str::snake($table) . '.php';
        $path = rtrim($this->outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($path)) {
            echo "ℹ️ Plik lang $fileName już istnieje – pomijam.\n";
            return;
        }

        file_put_contents($path, $this->generateLang($table));
        echo "✅ Wygenerowano plik lang: $fileName\n";
    }
}
