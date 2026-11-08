# NOVACELL — Etap 6: instalacja, routing i Visual Editor

Data wydania: 7 listopada 2026. Pakiety: **NOVACELL Manager 0.6.0**, **NOVACELL Theme 0.5.0**, **NOVACELL Visual Editor 0.2.0**.

## Cel Etapu 6

1. Jednoznaczny punkt wejścia do Visual Editor: nowe menu główne **NOVACELL Visual Editor**, ekran startowy i przycisk **Otwórz NOVACELL Visual Editor**; przy aktywacji automatycznie tworzy się **robocza, niepubliczna strona Gutenberg**, którą edytor otwiera wprost.
2. Pulpit **NOVACELL Manager** zawiera poprawne odnośniki do wszystkich ekranów administracyjnych.
3. Testy HTTP na rzeczywistym serwerze z użyciem wyłącznie własnego adresu WordPress, bez fałszywej deklaracji naprawy konfiguracji Apache lub Nginx.
4. Spójność tras i szablonów frontendowych, test tłumaczeń, biblioteka ikon SVG, miniatura `screenshot.png` i zgodność z istniejącymi zapisanymi danymi.
5. Niepowielanie rekordów przy ponownym imporcie, niewymuszanie statusów medycznych po identycznym imporcie oraz zachowanie treści po aktualizacji wtyczek.

## Kolejność instalacji ZIP

1. Utwórz kopię zapasową **pliku i bazy danych**. Najpierw pracuj na staging.
2. `Wtyczki → Dodaj nową → Wyślij wtyczkę → NOVACELL-Manager-0.6.0.zip → Zainstaluj → Zastąp obecną wersję → Aktywuj`.
3. `Wygląd → Motywy → Dodaj → Wyślij motyw → NOVACELL-Theme-0.5.0.zip → Zastąp obecną wersję → Aktywuj`.
4. `Wtyczki → Dodaj nową → NOVACELL-Visual-Editor-0.2.0.zip → Zastąp obecną wersję → Aktywuj`.
5. `Ustawienia → Bezpośrednie odnośniki → Zapisz zmiany` — uruchamia aktualizację WordPress rewrite rules.
6. `NOVACELL Manager → Trasy i podgląd → Napraw adresy i sprawdź menu`.
7. `NOVACELL Manager → Testy uruchomieniowe → Sprawdź 4 rzeczywiste adresy HTTP`.

## Gdzie wejść — dokładne adresy

Jeśli WordPress jest zainstalowany pod `https://example.com`, użyj poniższych wzorów (gdy instalacja jest w podfolderze, zachowaj podfolder przed `/wp-admin`):

| Ekran | Adres administracyjny |
|---|---|
| Pulpit Managera | `https://example.com/wp-admin/admin.php?page=novacell-manager` |
| Podstrony PL/EN | `https://example.com/wp-admin/admin.php?page=novacell-pages` |
| Strona główna i sekcje | `https://example.com/wp-admin/admin.php?page=novacell-home-builder` |
| Materiały startowe | `https://example.com/wp-admin/admin.php?page=novacell-starter` |
| Widoczność | `https://example.com/wp-admin/admin.php?page=novacell-preview-settings` |
| Diagnostyka 404 | `https://example.com/wp-admin/admin.php?page=novacell-diagnostics` |
| Testy HTTP | `https://example.com/wp-admin/admin.php?page=novacell-readiness` |
| **Visual Editor** | **`https://example.com/wp-admin/admin.php?page=novacell-visual-editor`** |

**Nie wpisuj `/novacell-pages`, `/novacell-home-builder`, `/novacell-starter` itd. jako zwykłych adresów strony.** To slugi *wp-admin*, a nie adresy publikowanych stron.

### Visual Editor

1. Wejdź w lewym menu WordPress do **NOVACELL Visual Editor**.
2. Kliknij **Otwórz NOVACELL Visual Editor**. WordPress otworzy specjalnie utworzony **szkic roboczy strony** w Gutenberg (adres `wp-admin/post.php?post=ID&action=edit`).
3. W górnym menu edytora znajdź ikonę panelu **NOVACELL Visual Editor** albo wybierz **Więcej → Wtyczki → Otwórz NOVACELL Visual Editor**.
4. W panelu dodawaj bloki, przenoś je myszą lub za pomocą standardowych komend WordPress; edytuj ich treści i właściwości.
5. **Uwaga:** edytor blokowy służy do natywnych stron i wpisów WordPress. Dynamiczny landing page główny i zawartość medycznych rekordów są nadal kontrolowane przez **NOVACELL Manager → Strona główna i sekcje** / **Podstrony PL/EN**. Nie są jeszcze w pełni wizualnie edytowane w tym samym interfejsie. To świadome rozdzielenie modułów, a nie drugi, niezależny format treści.
6. Jeśli panel Gutenberg nie jest dostępny, sprawdź, czy nie wyłączają go ustawienia Classic Editor lub inna wtyczka.

## Jeśli frontend nadal zwraca „Not Found”

Zacznij od publicznej strony głównej: `https://example.com/` oraz bezpiecznego linku bez reguł przepisywania, np. `https://example.com/?nc_view=ncfull-topic-1&nc_lang=pl`.

- **Strona główna i link `?nc_view=...` działają, a `/z-czym-pomagamy/menopauza/` zwraca 404**: sprawdź ustawienia bezpośrednich odnośników i reguły Apache/Nginx. W panelu *Trasy i podgląd* można wybrać tryb adresów bezpiecznych (parametrowych).
- **`/novacell-pages` i inne slugi menedżera zwracają „Not Found”**: to *nie są* URL-e frontendu. Użyj dokładnych linków `wp-admin/admin.php?page=...` z tabeli powyżej.
- **Nawet `/?nc_view=...` nie działa**: sprawdź aktywny Theme, aktywny Manager, indeks tras i wynik testu HTTP. Sprawdź, czy serwer rzeczywiście uruchamia plik `index.php` w katalogu instalacji.
- **Komunikat serwera: „Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument”**: żądanie nie trafia do aplikacji WordPress. PHP wtyczki nie może tego naprawić. Administrator serwera musi zweryfikować DocumentRoot i reguły serwera.

### Przykładowa standardowa konfiguracja Apache (do weryfikacji przez administratora hostingu)

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
```

Powyższy fragment dotyczy **WordPress w katalogu głównym** i musi być dostosowany do instalacji w podkatalogu, istniejącego `.htaccess`, konfiguracji home.pl i innych aplikacji. Nie nadpisuj istniejącego `.htaccess` bez kopii.

### Przykładowy Nginx (weryfikuje administrator serwera)

`try_files $uri $uri/ /index.php?$args;`

Zmian w konfiguracji serwera nie wprowadzamy z wtyczki.

## Widoczność i zatwierdzenia

Zgodnie z życzeniem właściciela, Etap 6 włącza **publiczny podgląd** po aktualizacji, o ile nie został wcześniej zainicjalizowany w tej wersji. Rekordy z dokumentu pozostają technicznie opublikowane (`publish`), ale otrzymują osobny status **wymaga recenzji**; nie zostają fikcyjnie podpisane przez lekarza. Takie strony mają `noindex` do czasu rzeczywistej weryfikacji. Publiczna widoczność nie jest równoznaczna z merytorycznym dopuszczeniem treści medycznych i nie zwalnia z oceny prawnej oraz klinicznej.

## Testy i ograniczenia

- Sprawdzenie składni PHP i JS, struktury ZIP oraz powtarzalności importu zostało wykonane lokalnie.
- Nie wykonano testu na docelowym hostingu, całego audytu WCAG 2.2 AA ani testów Core Web Vitals/PageSpeed Insights. Wymagają one uruchomionego WordPress oraz pomiarów w przeglądarce i Lighthouse.
- Nie dodano nieudokumentowanych tłumaczeń medycznych EN; istniejące tłumaczenia są zachowane, pozostałe wymagają redakcji i przeglądu przez specjalistę.
- Dane z załączonego Worda i cennik roboczy są dołączone tylko do **paczki instalacyjnej przeznaczonej dla właściciela**. Nie są publikowane w publicznym GitHub.

## Uzupełnienie 8 listopada 2026 — widoczność panelu w Gutenbergu

- W menu NOVACELL Manager dostępne jest też podmenu **Visual Editor**.
- Przycisk **Otwórz NOVACELL Visual Editor** kieruje do szkicu w Gutenbergu z `novacell_visual=1`; w zgodnym edytorze panel boczny otwiera się automatycznie.
- Na liście **Strony** i **Wpisy** dostępny jest link **Włącz Visual Editor** przy uprawnionych dokumentach.
- Jeśli inny plugin wyłączył edytor blokowy albo konkretna wersja nie udostępnia API panelu, użyj ręcznie menu paneli Gutenberg.
- Aktualizacja zachowuje treści i nie nadpisuje istniejącej strony głównej. Nazwy paneli nadal pozostają slugami `wp-admin/admin.php?page=...`, nie publicznymi adresami.
