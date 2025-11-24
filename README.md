# 🛠️ Simplo

Simplo to helper dla Laravel, który pozwala generować modele, zarządzać wersjami pakietów, czyścić środowisko i publikować plik uruchamiający. Nie wymaga Artisana — działa przez własny dispatcher.

---

## 🚀 Instalacja

Simplo instaluje się jak każdy pakiet Composer, ale automatycznie publikuje plik uruchamiający i nadaje mu uprawnienia:

```bash
composer require simplysmart/simplo --dev
```

Po instalacji:
- plik `simplo` pojawi się w katalogu głównym projektu
- otrzyma uprawnienia do uruchamiania (`chmod 755`)
- będzie gotowy do uruchamiania jako CLI

Upewnij się, że provider `SimploProvider` jest zarejestrowany w `config/app.php` lub automatycznie ładowany przez Composer.

🔧 Konfiguracja (config/simplo.php)

Opublikuj konfigurację

```bash
php artisan vendor:publish --tag=simplo-config
```

Plik konfiguracyjny:

```php
return [
'model_template_path' => base_path('vendor/simplysmart/simplo/src/resources/templates/model.php.stub'),

    'exclude' => [
        'fillable'   => ['created_at', 'updated_at', 'deleted_at'],
        'casts'      => ['created_at', 'updated_at', 'deleted_at'],
        'attributes' => ['created_at', 'updated_at', 'deleted_at'],
        'hidden'     => ['remember_token'],
    ],

    'model_path' => app_path('Models'),
    'model_template_path'   => base_path('stubs/model.stub'),
       'request_template_path' => base_path('stubs/request.stub'),
       'lang_template_path'    => base_path('stubs/lang.stub'),
       'lang_namespace'        => 'simplo',

];
```

# 📁 Struktura

```bash
project-root/
├── src
│   ├── app
│   │   ├── Console
│   │   │   ├──Commands
│   │   │   │   ├── ClearEnvCommand.php
│   │   │   │   ├── MakeLangCommand.php
│   │   │   │   ├── MakeModelsCommand.php
│   │   │   │   ├── PublishLauncherCommand.php
│   │   │   │   ├── ThemePublishCommand.php
│   │   │   │   └── UpdateCommand.php
│   │   │   └── Kernel.php
│   │   ├── Services
│   │   │   ├──Theme
│   │   │   │   ├── ThemePublisherService.php
│   │   │   │   └── ThemeService.php
│   │   │   ├── ComposerUpdateService.php
│   │   │   ├── EnvCleanerService.php 
│   │   │   ├── LauncherPublisher.php
│   │   │   ├── ProgressService.php
│   │   │   ├── SchemaInspector.php
│   │   │   └── SimploService.php
│   └── Simplo.php                          ← dispatcher CLI
├── simplo                                  ← launcher CLI
├── artisan
├── composer.json
├── vendor/
│   └── simplysmart/simplo/
│       ├── simplo                          ← źródłowy launcher
│       └── src/
│           └── Simplo.php                  ← dispatcher CLI
```

# ⚙️ Automatyczna publikacja launchera
W projekcie Laravel (np. skeletonie) dodaj do `composer.json`:

```json
{
    "scripts": {
        "post-install-cmd": [
            "@php vendor/simplysmart/simplo/simplo publish:launcher",
            "@chmod 755 simplo"
        ],
        "post-update-cmd": [
            "@php vendor/simplysmart/simplo/simplo publish:launcher",
            "@chmod 755 simplo"
        ]
    }
}
```

Dzięki temu plik simplo będzie publikowany automatycznie po każdej instalacji lub aktualizacji.


## ▶️ Pierwsze uruchomienie
```bash
php simplo help
```

Zobaczysz listę dostępnych komend.


## 📦 Dostępne komendy

---

## make:models
Generator modeli Eloquent na podstawie schematu bazy danych. Tworzy kompletne klasy PHP z uwzględnieniem pól `fillable`, `casts`, `attributes`, `hidden`, `primaryKey`, `connection`, a także komentarzy ze schematu jako adnotacji `PHPDoc`.

Przykłady:

```bash
php simplo make:models --all

php simplo make:models --table=users
```

### ⚙️ Opcje
| Opcja                | Opis                                                           |
|----------------------|----------------------------------------------------------------|
| `--table=users`      | Generuje model tylko dla wskazanej tabeli.                     |
| `--path=app/Domain`  | Nadpisuje domyślną ścieżkę zapisu modeli.                      |
| `--stub=custom.stub` | Używa alternatywnego szablonu modelu.                          |
| `--no-casts`         | Pomija generowanie tablicy `$casts`                            |
| `--no-attributes`    | Pomija generowanie tablicy `$attributes`                       |
| `--no-docblock`      | Pomija generowanie komentarza PHPDoc z adnotacjami `@property` |
| `--force`            | Nadpisuje istniejące pliki modeli.                             |

## make:request

Generator klas FormRequest (StoreRequest i UpdateRequest) na podstawie schematu bazy danych.
Tworzy reguły walidacji, komunikaty błędów i komentarze na podstawie typów SQL i komentarzy w schemacie.

Przykłady:

```bash
php simplo make:request --table=orders

php simplo make:request --all
```

### ⚙️ Opcje
| Opcja                    | Opis                                               |
|--------------------------|----------------------------------------------------|
| --table=orders           | Generuje requesty tylko dla wskazanej tabeli.      |
| --path=app/Http/Requests | Nadpisuje domyślną ścieżkę zapisu requestów.       |
| --stub=custom.stub       | Używa alternatywnego szablonu requestu.            |
| --no-messages            | Pomija generowanie komunikatów walidacyjnych.      |
| --no-comments            | Pomija zakomentowaną listę pól z typami i opisami. |
| --force                  | Nadpisuje istniejące pliki requestów.              |


## make:langs - Generowanie plików lang

Generator plików tłumaczeń lang na podstawie schematu bazy danych.
Tworzy pliki w katalogu lang/pl lub w module, z sekcją text zawierającą wszystkie pola fillable.
Dodaje komentarze PHPDoc z opisami kolumn.

Przykłady:

```bash
php simplo make:lang --table=users

php simplo make:lang --all --connection=mysql

```

### ⚙️ Opcje
| Opcja                        | Opis                                                         |
|------------------------------|--------------------------------------------------------------|
| --table=users                | Generuje plik lang tylko dla wskazanej tabeli.               |
| --path=Modules/Elmts/lang/pl | Nadpisuje domyślną ścieżkę zapisu plików lang.               |
| --stub=custom.stub           | Używa alternatywnego szablonu pliku lang.                    |
| --connection=mysql           | Wskazuje połączenie bazodanowe (domyślnie mysql).            |
| --all                        | Generuje pliki lang dla wszystkich tabel w danym połączeniu. |
| --force                      | Nadpisuje istniejące pliki lang.                             |


## update
Uruchamia composer update i podbija wersję pakietu zgodnie z typem aktualizacji.
Składnia:
php simplo update type package


Typy:
- fix – domyślny, bez zmiany wersji
- build – bump PATCH
- release – bump MINOR, reset PATCH
  Przykłady:
- php simplo update simplo
- php simplo update build simplo
- php simplo update release simplo

## clear:env
Czyści środowisko Laravel (cache, config, route, view) i wykonuje composer dump-autoload.
Składnia:
php simplo clear:env


## publish:launcher
Publikuje plik simplo do katalogu głównego aplikacji Laravel.
Składnia:
php simplo publish:launcher


🧪 Testowanie komend
Możesz przetestować każdą komendę bez Artisana:
php simplo make:models users
php simplo update release simplo
php simplo clear:env
php simplo publish:launcher


---

# 🎨 Publikacja motywów do katalogu

Simplo umożliwia automatyczną publikację zasobów motywów zdefiniowanych w `config/themes.php` do katalogu `public/`. Operacja ta kopiuje pliki z `source_path` do `public_path` dla każdego motywu w systemie.

#### 🔧 Konfiguracja

Plik `config/themes.php` powinien zawierać strukturę:

```php
return [
'web' => [
    'default' => 'tabler',
    'themes' => [
        'tabler' => [
            'source_path' => 'vendor/simplysmart/smartpanel/src/resources/themes/web/tabler',
            'public_path' => 'simplysmart/web/themes/tabler/',
            'assets_file' => 'theme_assets.json',
            'default_mode' => 'light',
        ],
    ],
],
// kolejne systemy...
];
```
### 🚀 Uruchomienie publikacji

Publikacja wszystkich motywów

```bash
php simplo theme:publish-assets
```
Publikuje wszystkie motywy dla wszystkich systemów. Po zakończeniu następuje automatyczne czyszczenie cache Laravel (cache, config, route, view) oraz composer dump-autoload.

Publikacja konkretnego motywu

```bash
php simplo theme:publish-assets --system=web --theme=tabler
```

Publikuje tylko wskazany motyw dla danego systemu.

### ⚙️ Opcje dodatkowe
- --no-header – pomija nagłówki logów w konsoli


### 📦 Efekt działania

Zasoby motywu zostają skopiowane do public/{public_path}. Istniejące katalogi są nadpisywane. Operacja ignoruje katalogi tymczasowe (views_temp) i wyświetla postęp kopiowania.

---

# 📜 Licencja

Simplo jest udostępniany na licencji MIT.

Możesz swobodnie używać, modyfikować i rozpowszechniać ten pakiet, zarówno komercyjnie jak i prywatnie, pod warunkiem:

- zachowania informacji o autorze w credits (np. _"Powered by Simplo"_)
- akceptacji braku odpowiedzialności autora za błędy lub skutki użycia

Pełna treść licencji znajduje się w pliku `LICENSE`.

🧑‍💻 Autor
Marcin Ulezalka
SimplySMART, 2025
