<?php
/*
 * Copyright (c) 2014-2025. simplySMART
 */

namespace Simplysmart\Simplo\App\Generators;

use Illuminate\Support\Str;
use Simplysmart\Simplo\App\Services\SchemaInspector;

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
     * Nazwa połączenia bazodanowego używanego do inspekcji schematu.
     *
     * Określa, z którego connection (np. `mysql`, `pgsql`) korzysta serwis
     * przy pobieraniu definicji kolumn tabeli.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Ścieżka docelowa do zapisu wygenerowanych plików FormRequest.
     *
     * Domyślnie: `app/Http/Requests`.
     *
     * @var string
     */
    protected string $outputPath;

    /**
     * Lista pól wykluczonych z walidacji.
     *
     * Pobierana z konfiguracji `config('simplo.exclude.validation', [])`.
     * Może zawierać np. pola systemowe, których nie chcemy walidować.
     *
     * @var array
     */
    protected array $excluded;

    /**
     * Serwis SchemaInspector odpowiedzialny za analizę schematu bazy danych.
     *
     * Wykorzystywany do pobierania kolumn, typów i komentarzy,
     * które następnie są mapowane na reguły walidacji.
     *
     * @var SchemaInspector
     */
    protected SchemaInspector $inspector;

    /**
     * Konstruktor generatora FormRequest.
     *
     * @param string $connection Nazwa połączenia bazodanowego (domyślnie: mysql).
     * @param string $outputPath Ścieżka docelowa do katalogu requestów (domyślnie: app/Http/Requests).
     */
    public function __construct(string $connection = 'mysql', string $outputPath = '')
    {
        $this->connection = $connection;
        $this->outputPath = $outputPath ?: app_path('Http/Requests');
        $this->excluded   = config('simplo.exclude.validation', []);
        $this->inspector  = new SchemaInspector($connection);
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
     */
    public function generateRequest(string $table, string $type): string
    {
        $columns       = $this->inspector->getColumns($table);
        $className     = $this->getRequestClassName($table, $type);
        $namespace     = $this->getNamespace($table);
        $rules         = $this->generateRules($columns, $table, $type);
        $comments      = $this->generateCommentedFields($columns);
        $messages      = $this->generateMessages($columns, $table);
        $booleanFields = $this->generateBooleanFields($columns);

        return $this->renderTemplate([
            'namespace'     => $namespace,
            'class'         => $className,
            'rules'         => $rules,
            'messages'      => $messages,
            'comments'      => $comments,
            'booleanFields' => $booleanFields,
        ]);
    }

    /**
     * Buduje nazwę klasy requestu na podstawie tabeli i typu.
     *
     * Przykład: dla tabeli `users` i typu `store` wygeneruje `StoreUserRequest`.
     *
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu (`store` lub `update`).
     * @return string Nazwa klasy FormRequest.
     */
    protected function getRequestClassName(string $table, string $type): string
    {
        return ucfirst($type) . Str::studly(Str::singular($table)) . 'Request';
    }

    /**
     * Buduje namespace dla klasy requestu na podstawie nazwy tabeli.
     *
     * Przykład: dla tabeli `elmts_users` wygeneruje `App\Http\Requests\Elmts\Users`.
     *
     * @param string $table Nazwa tabeli.
     * @return string Namespace klasy FormRequest.
     */
    protected function getNamespace(string $table): string
    {
        $segments = explode('_', $table);
        $domain   = Str::studly($segments[0] ?? 'App');
        $resource = Str::studly(Str::plural(end($segments)));

        return "App\\Http\\Requests\\$domain\\$resource";
    }

    /**
     * Generuje komunikaty walidacyjne dla pól tabeli.
     *
     * Tworzy wpisy w metodzie `messages()` w formacie:
     * `'field.rule' => text('namespace::table.validation.field.rule')`.
     *
     * @param array $columns Lista kolumn tabeli (SHOW FULL COLUMNS).
     * @param string $table Nazwa tabeli.
     * @return string Kod komunikatów walidacyjnych.
     */
    protected function generateMessages(array $columns, string $table): string
    {
        $namespace = config('simplo.lang_namespace', 'simplo');
        $messages  = [];

        foreach ($columns as $column) {
            $field = $column->Field;
            if ($field === 'id') {
                continue;
            }

            $rules = [];
            $rules[] = $column->Null === 'YES' ? 'nullable' : 'required';
            $rules[] = $this->inspector->mapToValidationType($column->Type, $field);

            if ($max = $this->inspector->extractMaxLength($column->Type)) {
                $rules[] = "max:$max";
            }
            if (Str::endsWith($field, '_id')) {
                $rules[] = 'exists';
            }
            if ($this->inspector->isEnum($column->Type)) {
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
     * Tworzy tablicę pól typu boolean w metodzie `prepareForValidation()`.
     *
     * @param array $columns Lista kolumn tabeli.
     * @return string Kod pól boolean lub komentarz jeśli brak.
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
     * Generuje reguły walidacji dla wskazanej tabeli.
     *
     * Tworzy zakomentowane linie w metodzie `rules()`, które można odkomentować
     * i dostosować ręcznie.
     *
     * @param array $columns Lista kolumn tabeli.
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu (`store` lub `update`).
     * @return string Kod reguł walidacyjnych.
     */
    protected function generateRules(array $columns, string $table, string $type): string
    {
        return collect($columns)
            ->filter(fn($col) => $col->Field !== 'id' && !in_array($col->Field, $this->excluded))
            ->map(fn($col) => $this->formatRule($col, $table, $type))
            ->implode("\n");
    }

    /**
     * Formatuje pojedynczą regułę walidacji dla kolumny.
     *
     * Uwzględnia:
     * - `nullable` / `required`,
     * - typ walidacji (string, integer, boolean, array, date, enum),
     * - maksymalną długość (`max`),
     * - relacje (`exists`),
     * - unikalność (`Rule::unique`).
     *
     * @param object $column Definicja kolumny (SHOW FULL COLUMNS).
     * @param string $table Nazwa tabeli.
     * @param string $type Typ requestu (`store` lub `update`).
     * @return string Zakomentowana linia reguły walidacyjnej.
     */
    protected function formatRule(object $column, string $table, string $type): string
    {
        $name     = $column->Field;
        $nullable = $column->Null === 'YES';
        $rules    = $nullable ? ['nullable'] : ['required'];

        $rules[] = $this->inspector->mapToValidationType($column->Type, $name);

        if ($max = $this->inspector->extractMaxLength($column->Type)) {
            $rules[] = "max:$max";
        }
        if (Str::endsWith($name, '_id')) {
            $rules[] = "exists:$table,$name";
        }
        if ($this->inspector->isEnum($column->Type)) {
            $rules[] = $this->inspector->generateEnumRule($column->Type);
        }
        if ($column->Key === 'UNI') {
            $unique = "Rule::unique('$table', '$name')";
            if ($type === 'update') {
                $unique .= "->ignore(\$this->route('id'))";
            }
            $rules[] = $unique;
        }

        $quoted = array_map(fn($rule) => str_starts_with($rule, 'Rule::') ? $rule : "'$rule'", $rules);

        return "            // '$name' => [" . implode(', ', $quoted) . "],";
    }

    /**
     * Generuje zakomentowaną listę pól z typem i komentarzem.
     *
     * Tworzy dokumentację pomocniczą w klasie requestu, np.:
     * `// string $name // nazwa użytkownika`
     *
     * @param array $columns Lista kolumn tabeli.
     * @return string Zakomentowane pola z typem i opisem.
     */
    protected function generateCommentedFields(array $columns): string
    {
        return collect($columns)->map(function ($col) {
            $type    = $this->inspector->mapToValidationType($col->Type, $col->Field);
            $comment = trim($col->Comment ?? '');
            $enum    = $this->inspector->isEnum($col->Type)
                ? ' // enum: ' . implode(', ', $this->inspector->extractEnumValues($col->Type))
                : '';

            return "// $type \${$col->Field}" . ($comment ? " // $comment" : '') . $enum;
        })->implode("\n");
    }

    /**
     * Renderuje szablon requestu na podstawie danych.
     *
     * Podstawia wartości do pliku szablonu (`request.stub`) w miejscach
     * oznaczonych jako `{{ key }}`.
     *
     * @param array $data Dane do podstawienia w szablonie (namespace, class, rules, messages, comments, booleanFields).
     * @return string Kod PHP wygenerowanej klasy FormRequest.
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
