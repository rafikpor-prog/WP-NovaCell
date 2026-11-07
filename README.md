# WP-NovaCell

System WordPress dla nowej witryny NOVACELL, oparty na trzech odrębnych komponentach:

- `plugins/novacell-manager/` — **NovaCell Manager 0.5.0**: rejestry danych, edycja w zakładkach PL/EN, strona główna, import, 44 trasy i zarządzanie publicznym podglądem.
- `themes/novacell/` — **NOVACELL Theme 0.4.0**: responsywny frontend, dostępne FAQ, polskie i angielskie interfejsy, SEO, SVG, screenshot i wariant adresów niewymagający reguł rewrite.
- `plugins/novacell-visual-editor/` — **NOVACELL Visual Editor 0.1.0**: nakładka na Gutenberg z kafelkami, drag-and-drop, edycją treści i inspektorem stylów.

## Instalacja i aktualizacja

Paczki WordPress są dostarczane **oddzielnie**:
`NOVACELL-Manager-0.5.0.zip`, `NOVACELL-Theme-0.4.0.zip`, `NOVACELL-Visual-Editor-0.1.0.zip`.

W każdym ZIP jest jeden katalog główny o stałym slug, który można zainstalować lub zastąpić w natywnym instalatorze WordPress. Kolejność: Manager → Theme → Visual Editor.

Zobacz: [Instrukcja etapu 5](docs/ETAP5_INSTRUKCJA.md) i [opis wydania](releases/NOVACELL_Etap5_2026-11-07.md).

## Ważna zasada poufności

**Publiczny GitHub zawiera kod, ale nie prywatne materiały kliniki.** Pliki `full-content.private.json` i `starter-content.private.json` są celowo wyłączone z repozytorium. Instalacyjny ZIP przekazywany właścicielowi zawiera osobny zestaw roboczych materiałów w CMS. Nie umieszczaj w Git danych pacjentów, haseł, kluczy ani niezatwierdzonych materiałów wewnętrznych.

## Status publikacji

Techniczne `publish` nie jest równoznaczne z zatwierdzeniem medycznym. Wersja 0.5.0 umożliwia publiczne oglądanie treści, ale domyślnie wyświetla zastrzeżenie redakcyjne oraz nagłówek `noindex`. Formalna akceptacja tekstów i oferty jest oddzielnym działaniem.

## Jakość

Weryfikacja lokalna: składnia 28 plików PHP, składnia JS oraz izolowany test 181 wpisów, 44 tras, FAQ i idempotentnego importu. Te testy **nie zastępują** testów integracyjnych prawdziwego WordPress, audytu WCAG 2.2 AA lub pomiarów PageSpeed Insights na serwerze.

## Kolejny etap

Etap 6: integracja z rzeczywistym środowiskiem, testy dostępności i Core Web Vitals, weryfikacja cennika źródłowego, mapa przekierowań 301, SEO i kontrolowane przełączenie produkcyjne.
