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
];
```

# 📁 Struktura

```
project-root/
├── simplo                  ← launcher CLI
├── artisan
├── composer.json
├── vendor/
│   └── simplysmart/simplo/
│       ├── simplo          ← źródłowy launcher
│       └── src/
│           └── Simplo.php  ← dispatcher CLI

```

# ⚙️ Automatyczna publikacja launchera
W projekcie Laravel (np. skeletonie) dodaj do composer.json:

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


## update
Uruchamia composer update i podbija wersję pakietu zgodnie z typem aktualizacji.
Składnia:
php simplo update [type] [package]


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

# 📜 Licencja

Simplo jest udostępniany na licencji MIT.

Możesz swobodnie używać, modyfikować i rozpowszechniać ten pakiet, zarówno komercyjnie jak i prywatnie, pod warunkiem:

- zachowania informacji o autorze w credits (np. _"Powered by Simplo"_)
- akceptacji braku odpowiedzialności autora za błędy lub skutki użycia

Pełna treść licencji znajduje się w pliku `LICENSE`.

🧑‍💻 Autor
Marcin Ulezalka
SimplySMART, 2025
