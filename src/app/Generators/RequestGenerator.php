<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
// use Illuminate\Validation\Rule;

/**
 * Class RequestGenerator
 *
 * Generator klas FormRequest na podstawie schematu bazy danych.
 * Obsługuje:
 * - generowanie Store/Update requestów,
 * - mapowanie typów SQL na reguły walidacji,
 * - automatyczne komunikaty walidacyjne,
 * - wykrywanie pól boolean (is_*) oraz json_*.
 *
 * @package Simplysmart\Simplo\App\Generators
 * @author
 * @version 1.0.0
 */
class RequestGenerator
{
    /**
     * Nazwa połączenia bazodanowego.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Ścieżka docelowa dla wygenerowanych plików.
     *
     * @var string
     */
    protected string $outputPath;

    /**
     * Lista pól wykluczonych z walidacji.
     *
     * @var array
     */
    protected array $excluded;

    /**
     * Konstruktor generatora.
     *
     * @param string $connection Nazwa połączenia bazodanowego (domyślnie mysql).
     * @param string $outputPath Ścieżka docelowa (domyślnie app/Http/Requests).
     */
    public function __construct(string $connection = 'mysql', string $outputPath = '')
    {
        $this->connection = $connection;
        $this->outputPath = $outputPath ?: app_path('Http/Requests');
        $this->excluded = config('simplo.exclude.validation', []);
    }

    /**
     * Zapisuje pliki requestów (Store/Update) dla wskazanej tabeli.
     *
     * @param string $table Nazwa tabeli.
     * @return void
     */
    public function saveRequest(string $table): void
    {
        foreach (['store', 'update'] as $type) {
            $className = $this->getRequestClassName($table, $type);
            $path = rtrim($this->outputPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "$className.php";

            if (file_exists($path)) {
                echo "ℹ️ Request $className już istnieje – pomijam.\n";
                continue;
            }

            $code = $this->generateRequest($table, $type);
            file_put_contents($path, $code);
            echo "✅ Wygenerowano request: $className\n";
        }
    }

    /**
     * Generuje kod klasy FormRequest dla wskazanej tabeli.
     *
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu (store|update).
     * @return string Kod PHP klasy.
     */
    public function generateRequest(string $table, string $type): string
    {
        $columns = $this->getTableColumns($table);
        $className = $this->getRequestClassName($table, $type);
        $namespace = $this->getNamespace($table);
        $rules = $this->generateRules($columns, $table, $type);
        $comments = $this->generateCommentedFields($columns);
        $messages = $this->generateMessages($columns, $table);
        $booleanFields = $this->generateBooleanFields($columns);

        return $this->renderTemplate([
            'namespace' => $namespace,
            'class' => $className,
            'rules' => $rules,
            'messages' => $messages,
            'comments' => $comments,
            'booleanFields' => $booleanFields,
        ]);
    }

    /**
     * Pobiera definicję kolumn dla tabeli.
     *
     * @param string $table Nazwa tabeli.
     * @return array Lista kolumn.
     */
    protected function getTableColumns(string $table): array
    {
        return DB::connection($this->connection)->select("SHOW FULL COLUMNS FROM `$table`");
    }

    /**
     * Buduje nazwę klasy requestu.
     *
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu (store|update).
     * @return string Nazwa klasy.
     */
    protected function getRequestClassName(string $table, string $type): string
    {
        return ucfirst($type) . Str::studly(Str::singular($table)) . 'Request';
    }

    /**
     * Buduje namespace dla klasy requestu.
     *
     * @param string $table Nazwa tabeli.
     * @return string Namespace.
     */
    protected function getNamespace(string $table): string
    {
        $segments = explode('_', $table);
        $domain = Str::studly($segments[0] ?? 'App');
        $resource = Str::studly(Str::plural(end($segments)));

        return "App\\Http\\Requests\\$domain\\$resource";
    }

    /**
     * Generuje komunikaty walidacyjne dla pól tabeli.
     *
     * @param array $columns Lista kolumn.
     * @param string $table Nazwa tabeli.
     * @return string Kod komunikatów.
     */
    protected function generateMessages(array $columns, string $table): string
    {
        $namespace = config('simplo.lang_namespace', 'simplo');
        $messages = [];

        foreach ($columns as $column) {
            $field = $column->Field;

            if ($field === 'id') {
                continue;
            }

            $rules = [];
            $rules[] = $column->Null === 'YES' ? 'nullable' : 'required';
            $rules[] = $this->mapToValidationType($column->Type, $field);

            if ($max = $this->extractMaxLength($column->Type)) {
                $rules[] = "max:$max";
            }
            if (Str::endsWith($field, '_id')) {
                $rules[] = 'exists';
            }
            if ($this->isEnum($column->Type)) {
                $rules[] = 'in';
            }
            if ($column->Key === 'UNI') {
                $rules[] = 'unique';
            }

            foreach ($rules as $rule) {
                $ruleName = explode(':', $rule)[0];
                $messages[] = "            '$field.$ruleName' => text('$namespace::$table.validation.$field.$ruleName'),";
            }
        }

        return implode("\n", $messages);
    }

    /**
     * Generuje listę pól boolean (is_*).
     *
     * @param array $columns Lista kolumn.
     * @return string Kod pól boolean.
     */
    protected function generateBooleanFields(array $columns): string
    {
        $flags = collect($columns)
            ->filter(fn($col) => Str::startsWith($col->Field, 'is_'))
            ->map(fn($col) => "            '{$col->Field}' => true,")
            ->implode("\n");

        return $flags ?: "            // brak pól typu boolean";
    }

    /**
     * Generuje reguły walidacji dla tabeli.
     *
     * @param array $columns Lista kolumn.
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu.
     * @return string Kod reguł.
     */
    protected function generateRules(array $columns, string $table, string $type): string
    {
        return collect($columns)
            ->filter(fn($col) => $col->Field !== 'id' && !in_array($col->Field, $this->excluded))
            ->map(fn($col) => $this->formatRule($col, $table, $type))
            ->implode("\n");
    }

    /**
     * Formatuje pojedynczą regułę walidacji.
     *
     * @param object $column Definicja kolumny.
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu.
     * @return string Zakomentowana linia reguły.
     */
    protected function formatRule(object $column, string $table, string $type): string
    {
        $name = $column->Field;
        $nullable = $column->Null === 'YES';
        $rules = $nullable ? ['nullable'] : ['required'];

        $rules[] = $this->mapToValidationType($column->Type, $name);

        if ($max = $this->extractMaxLength($column->Type)) {
            $rules[] = "max:$max";
        }

        if (Str::endsWith($name, '_id')) {
            $rules[] = "exists:$table,$name";
        }

        if ($this->isEnum($column->Type)) {
            $rules[] = $this->generateEnumRule($column->Type);
        }

        if ($column->Key === 'UNI') {
            $unique = "Rule::unique('$table', '$name')";
            if ($type === 'update') {
                $unique .= "->ignore(\$this->route('id'))";
            }
            $rules[] = $unique;
        }

        // 🔑 dodajemy apostrofy dla zwykłych reguł
        $quoted = array_map(function ($rule) {
            return str_starts_with($rule, 'Rule::')
                ? $rule
                : "'$rule'";
        }, $rules);

        // cała linia zakomentowana
        return "            // '$name' => [" . implode(', ', $quoted) . "],";
    }

    /**
     * Mapuje typ SQL oraz nazwę kolumny na regułę walidacji.
     *
     * Obsługuje:
     * - pola json_* jako 'array' nawet jeśli typ to TEXT,
     * - pola enum(...) jako 'string',
     * - pola int jako 'integer' lub 'boolean' jeśli nazwa zaczyna się od is_,
     * - pola bool jako 'boolean',
     * - pola float/decimal jako 'numeric',
     * - pola date/timestamp jako 'date',
     * - pola json jako 'array',
     * - inne jako 'string'.
     *
     * @param string $sqlType Typ SQL kolumny.
     * @param string $name    Nazwa kolumny.
     * @return string Reguła walidacji.
     */
    protected function mapToValidationType(string $sqlType, string $name): string
    {
        return match (true) {
            Str::startsWith($name, 'json_') => 'array',
            $this->isEnum($sqlType) => 'enum', // 👈 zmiana
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

    protected function extractMaxLength(string $sqlType): ?int
    {
        return preg_match('/varchar\((\d+)\)/', $sqlType, $m) ? (int)$m[1] : null;
    }

    /**
     * Sprawdza czy typ SQL jest typu enum.
     *
     * @param string $sqlType Typ SQL kolumny.
     * @return bool True jeśli typ to enum, false w przeciwnym razie.
     */
    protected function isEnum(string $sqlType): bool
    {
        return str_starts_with($sqlType, 'enum(');
    }

    /**
     * Generuje regułę walidacji Rule::in([...]) dla pola enum.
     *
     * @param string $sqlType Typ SQL kolumny (enum).
     * @return string Reguła walidacji Rule::in([...]).
     */
    protected function generateEnumRule(string $sqlType): string
    {
        preg_match_all("/'([^']+)'/", $sqlType, $matches);
        $values = $matches[1] ?? [];
        return 'Rule::in([' . implode(', ', array_map(fn($v) => "'$v'", $values)) . '])';
    }

    /**
     * Generuje zakomentowaną listę pól z typem i komentarzem.
     *
     * @param array $columns Lista kolumn tabeli.
     * @return string Zakomentowane pola z typem i opisem.
     */
    protected function generateCommentedFields(array $columns): string
    {
        return collect($columns)->map(function ($col) {
            $type = $this->mapToValidationType($col->Type, $col->Field);
            $comment = trim($col->Comment ?? '');
            $enum = $this->isEnum($col->Type)
                ? ' // enum: ' . implode(', ', $this->extractEnumValues($col->Type))
                : '';

            return "// $type \${$col->Field}" . ($comment ? " // $comment" : '') . $enum;
        })->implode("\n");
    }

    /**
     * Wyciąga wartości enum z definicji SQL.
     *
     * @param string $sqlType Typ SQL kolumny (enum).
     * @return array Lista wartości enum.
     */
    protected function extractEnumValues(string $sqlType): array
    {
        preg_match_all("/'([^']+)'/", $sqlType, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Renderuje szablon requestu na podstawie danych.
     *
     * @param array $data Dane do podstawienia w szablonie.
     * @return string Kod PHP wygenerowanej klasy.
     *
     * @throws \RuntimeException Jeśli brak pliku szablonu.
     */
    protected function renderTemplate(array $data): string
    {
        $templatePath = config('simplo.request_template_path');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Brakuje szablonu: $templatePath");
        }

        $template = file_get_contents($templatePath);

        foreach ($data as $key => $value) {
            $template = str_replace('{{ ' . $key . ' }}', $value, $template);
        }

        return $template;
    }
}
