<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service SchemaInspector
 *
 * Odpowiada za inspekcję schematu bazy danych i przygotowanie metadanych
 * dla generatorów (Model, Lang, Request).
 *
 * @package Simplysmart\Simplo\App\Services
 */
class SchemaInspector
{
    /**
     * Nazwa połączenia bazodanowego używanego do inspekcji schematu.
     *
     * Określa, z którego connection (np. `mysql`, `pgsql`) korzysta serwis
     * przy wykonywaniu zapytań typu `SHOW FULL COLUMNS` czy `SHOW KEYS`.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Konfiguracja wykluczeń pól dla poszczególnych sekcji.
     *
     * Tablica pobierana z `config('simplo.exclude', [])`, zawiera listy pól,
     * które mają być pominięte przy generowaniu:
     * - `fillable`   → pola pomijane w mass assignment,
     * - `attributes` → pola pomijane w domyślnych atrybutach,
     * - `hidden`     → pola pomijane w tablicy `$hidden`,
     * - `casts`      → pola pomijane w tablicy `$casts`.
     *
     * @var array
     */
    protected array $excluded;

    /**
     * Konstruktor serwisu SchemaInspector.
     *
     * @param string $connection Nazwa połączenia bazodanowego (domyślnie: mysql).
     */
    public function __construct(string $connection = 'mysql')
    {
        $this->connection = $connection;
        $this->excluded   = config('simplo.exclude', []);
    }

    /**
     * Pobiera wszystkie kolumny dla wskazanej tabeli.
     *
     * @param string $table Nazwa tabeli.
     * @return array Lista obiektów kolumn (SHOW FULL COLUMNS).
     */
    public function getColumns(string $table): array
    {
        return DB::connection($this->connection)
            ->select("SHOW FULL COLUMNS FROM `$table`");
    }

    /**
     * Pobiera nazwę kolumny będącej kluczem głównym.
     *
     * @param string $table Nazwa tabeli.
     * @return string Nazwa kolumny klucza głównego (domyślnie 'id').
     */
    public function getPrimaryKey(string $table): string
    {
        $keys = DB::connection($this->connection)
            ->select("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        return $keys[0]->Column_name ?? 'id';
    }

    /**
     * Generuje listę pól fillable dla modelu.
     *
     * @param string $table Nazwa tabeli.
     * @return array Tablica nazw pól, które mogą być masowo przypisywane.
     */
    public function getFillable(string $table): array
    {
        $columns    = $this->getColumns($table);
        $primaryKey = $this->getPrimaryKey($table);
        $exclude    = $this->excluded['fillable'] ?? [];

        return array_values(array_filter(
            array_map(fn($col) => $col->Field, $columns),
            fn($name) => $name !== $primaryKey && !in_array($name, $exclude)
        ));
    }

    /**
     * Generuje tablicę domyślnych atrybutów na podstawie wartości DEFAULT w schemacie.
     *
     * @param string $table Nazwa tabeli.
     * @return array Tablica ['nazwa_pola' => domyślna_wartość].
     */
    public function getAttributes(string $table): array
    {
        $columns = $this->getColumns($table);
        $exclude = $this->excluded['attributes'] ?? [];
        $attributes = [];

        foreach ($columns as $column) {
            $name    = $column->Field;
            $default = $column->Default;

            if (!is_null($default) && !in_array($name, $exclude)) {
                /** @noinspection PhpExpressionWithSameOperandsInspection */
                $attributes[$name] = is_numeric($default) ? $default : $default;
            }
        }

        return $attributes;
    }

    /**
     * Generuje listę pól ukrytych (hidden).
     *
     * @param string $table Nazwa tabeli.
     * @return array Tablica nazw pól ukrytych (np. password, remember_token).
     */
    public function getHidden(string $table): array
    {
        $exclude = $this->excluded['hidden'] ?? [];
        $hidden  = [];

        foreach ($this->getColumns($table) as $column) {
            $name = $column->Field;
            if (in_array($name, ['password', 'remember_token']) && !in_array($name, $exclude)) {
                $hidden[] = $name;
            }
        }

        return $hidden;
    }

    /**
     * Generuje tablicę rzutowań typów pól dla modelu.
     *
     * @param string $table Nazwa tabeli.
     * @return array Tablica ['nazwa_pola' => 'typ'] dla casts.
     */
    public function getCasts(string $table): array
    {
        $columns = $this->getColumns($table);
        $exclude = $this->excluded['casts'] ?? [];
        $casts   = [];

        foreach ($columns as $column) {
            $name = $column->Field;
            $type = $column->Type;

            if (in_array($name, $exclude)) {
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
     * Generuje komentarz PHPDoc z adnotacjami @property na podstawie kolumn tabeli.
     *
     * @param string $table Nazwa tabeli.
     * @return string Tekst PHPDoc z listą właściwości.
     */
    public function getPhpDoc(string $table): string
    {
        $columns = $this->getColumns($table);
        $lines   = [];

        foreach ($columns as $column) {
            $name    = $column->Field;
            $type    = $this->mapToPhpType($column->Type, $name);
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
     * Mapuje typ SQL na typ PHP dla adnotacji w PHPDoc.
     *
     * @param string $sqlType Typ SQL kolumny.
     * @param string $name Nazwa kolumny.
     * @return string Typ PHP dla adnotacji.
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

    /**
     * Mapuje typ SQL oraz nazwę kolumny na regułę walidacji.
     *
     * @param string $sqlType Typ SQL kolumny.
     * @param string $name Nazwa kolumny.
     * @return string Reguła walidacji (np. string, integer, boolean, array, date, enum).
     */
    public function mapToValidationType(string $sqlType, string $name): string
    {
        return match (true) {
            Str::startsWith($name, 'json_') => 'array',
            $this->isEnum($sqlType) => 'enum',
            str_contains($sqlType, 'int') => Str::startsWith($name, 'is_') ? 'boolean' : 'integer',
            str_contains($sqlType, 'bool') => 'boolean',
            str_contains($sqlType, 'float'), str_contains($sqlType, 'decimal') => 'numeric',
            str_contains($sqlType, 'date'), str_contains($sqlType, 'timestamp') => 'date',
            str_contains($sqlType, 'json') => 'array',
            default => 'string',
        };
    }

    /**
     * Wyciąga maksymalną długość dla pól typu varchar.
     *
     * @param string $sqlType Typ SQL kolumny.
     * @return int|null Maksymalna długość lub null jeśli brak.
     */
    public function extractMaxLength(string $sqlType): ?int
    {
        return preg_match('/varchar\((\d+)\)/', $sqlType, $m) ? (int)$m[1] : null;
    }


    /**
     * Sprawdza czy typ SQL jest typu enum.
     *
     * @param string $sqlType Typ SQL kolumny.
     * @return bool True jeśli typ to enum, false w przeciwnym razie.
     */
    public function isEnum(string $sqlType): bool
    {
        return str_starts_with($sqlType, 'enum(');
    }

    /**
     * Wyciąga wartości enum z definicji SQL.
     *
     * @param string $sqlType Typ SQL kolumny (enum).
     * @return array Lista wartości enum.
     */
    public function extractEnumValues(string $sqlType): array
    {
        preg_match_all("/'([^']+)'/", $sqlType, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Generuje regułę walidacji Rule::in([...]) dla pola enum.
     *
     * @param string $sqlType Typ SQL kolumny (enum).
     * @return string Reguła walidacji Rule::in([...]).
     */
    public function generateEnumRule(string $sqlType): string
    {
        $values = $this->extractEnumValues($sqlType);
        return 'Rule::in([' . implode(', ', array_map(fn($v) => "'$v'", $values)) . '])';
    }
}
