<?php
/*
 * Copyright (c) 2014-2025.
 */

/**
 * ============================================================================
 * Konfiguracja Modułu: Simplo
 * ============================================================================
 */

return [
    /**
     * Ścieżki szablonów dla generatorów kodu Simplo.
     */
    'model_template_path' => base_path('vendor/simplysmart/simplo/src/resources/templates/model.php.stub'),
    'request_template_path'=> base_path('vendor/simplysmart/simplo/src/resources/templates/request.php.stub'),
    'lang_template_path'=> base_path('vendor/simplysmart/simplo/src/resources/templates/lang.php.stub'),

    /**
     * Lista guardów wykorzystywana w systemie
     */
    'seeder_guards'=>['web'],

    /**
     * Pełna nazwa pakietu (np. vendor/nazwa).
     * Używana do:
     * - Identyfikacji zasobów (Assets, Seederów)
     * - Ścieżek do storage i publikowanych zasobów
     */
    'package_name' => 'marcinulezalka/simplo',

    /**
     * Pełna nazwa aliasu do katalogu lang wykorzystywana podczas tłumaczenia (np. elmts-lang::).
     * Używana do:
     * - Identyfikacji zasobów (lang)
     * - Ścieżek do lang
     */
    'lang_namespace' => 'simplo-lang',

    /**
     * Nazwa modułu (biznesowa).
     * Może być używana w interfejsie, brandingach, nagłówkach.
     */
    'module_name' => 'Simplo',

    /**
     * Adres repozytorium modułu (opcjonalnie).
     */
    'module_repository_url' => '#',

    /**
     * Wersja modułu.
     */
    'module_version' => '1.0',

    /**
     * Lista pól do wykluczenia z generowanych tablic fillable, hidden, casts, attributes.
     */
    'exclude' => [
        'fillable'   => ['created_at', 'updated_at', 'deleted_at'],
        'casts'      => ['created_at', 'updated_at', 'deleted_at'],
        'attributes' => ['created_at', 'updated_at', 'deleted_at'],
        'hidden'     => [],
    ],


];
