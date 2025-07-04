# SIMPLO CLI

**SIMPLO** to narzędzie CLI stworzone przez **simplySMART**, ułatwiające pracę z projektami opartymi na Laravelu oraz strukturze z modułami developerskimi i szablonami.  
**Autor:** Marcin Ulezalka

---

## 📦 Wymagania

- PHP (Laravel framework)
- Node.js & NPM
- Composer

---

## 🚀 Instalacja

Dodaj SIMPLO do swojego projektu jako zależność developerską lub zainstaluj jako globalne narzędzie CLI (jeśli jest dostępne w ten sposób).

---

## 🛠️ Dostępne komendy

| Komenda                             | Opis                                                                 |
|-------------------------------------|----------------------------------------------------------------------|
| `clear:env`                         | Czyści cache Laravel: config, route, view, itp.                      |
| `copy:vendor`                       | Kopiuje całą paczkę do `resources/vendor/{package_name}`            |
| `example`                           | Pokazuje przykład sekcji `simploManualLibs` w pliku `package.json`  |
| `help`                              | Wyświetla pomoc i dostępne komendy                                   |
| `install [module]`                  | Instalacja `dev_module/{module}` z użyciem `.templates` i Instalera |
| `make:theme-config`                 | Tworzy plik `config/themes.php` z przykładową konfiguracją          |
| `publish`                           | Publikuje vendory i wykonuje `composer update`                       |
| `publish:vendor`                    | Publikuje pliki z `resources/vendor` do `public/vendor`             |
| `theme:publish -all`                | Publikuje wszystkie motywy z `config/themes.php` do katalogu `public/` |
| `theme:publish [theme] [system]`    | Publikuje wybrany motyw, np. `theme:publish bubble smartpanel`     |
| `update`                            | Wykonuje `npm update` i kopiuje wszystkie biblioteki do `resources/vendor/{package_name}` |

---

## 📂 Przykład konfiguracji

Aby poprawnie korzystać z niektórych funkcji SIMPLO, musisz zdefiniować sekcję `simploManualLibs` w pliku `package.json`.

Użyj komendy:

```bash
php simplo example
```

Aby zobaczyć przykładową konfigurację.

---

## 🧪 Tworzenie pliku z motywami

Użyj poniższej komendy, aby wygenerować przykładowy plik `themes.php` w katalogu `config`:

```bash
php simplo make:theme-config
```

---

## 📢 Autor

**Marcin Ulezalka**  
Projekt: **SIMPLO by simplySMART**

