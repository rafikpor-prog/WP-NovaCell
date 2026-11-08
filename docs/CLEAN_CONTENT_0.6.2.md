# NOVACELL: oczyszczenie treści dla pacjentów (0.6.2)

Data: 8 listopada 2026. Podstawa: `Nowa_strona_Novacell_komplet-2(1).docx` (wersja 3, 5 listopada 2026, SHA-256 `26831ad56befd88d79b676f807ee0596d5054e2ba82842b95b1e26fb5f4a14ca`).

## Zakres

- 41 opisów stron, ścieżek i metod oraz 3 profile lekarzy.
- 9 sekcji strony głównej, 3 strony PL/EN i 10 wcześniejszych rekordów startowych.
- Katalog 120 usług oraz 14 pakietów zachowany bez zmiany kwot, statusów medycznych i relacji.
- łącznie opisy źródłowe: 264 201 znaków przed redakcją → 203 838 znaków po, redukcja ok. 22,8%. Zmniejszenie długości dotyczy głównie wytycznych technicznych i roboczych notatek, ale kilka stron opracowano w krótszej formie skierowanej do pacjenta.

## Usunięto z treści publicznych

Instrukcje CMS, SEO i programowania; etapy projektu; techniczne identyfikatory tematów i wzory layoutów; nieuzupełnione pola w nawiasach kwadratowych; odniesienia do załączników; opis przekierowań 301 z działu Wiedza; sugestie publikowania stron i ukrywania kafelków; puste rubryki przypadków z praktyki.

Zachowano kliniczne opisy objawów, diagnostyki, opcji leczenia, ograniczeń, ryzyk, pytania i odpowiedzi oraz tabele merytoryczne, gdy te dane znajdowały się w źródle. Strony związane z polityką prywatności, regulaminem, kontaktem i listą lekarzy otrzymały krótszą treść, która nie udaje kompletnego dokumentu prawnego ani potwierdzonej oferty.

## Automatyczna aktualizacja

Po zainstalowaniu `NOVACELL-Manager-0.6.2.zip`, administrator otwiera `wp-admin`: skrypt `Full_Import::maybe_upgrade` aktualizuje rekordy źródłowe i zapisuje wersję importu 5. Następnie `Cleanup_Update::maybe_upgrade` nadpisuje znane sekcje strony głównej i podstrony PL/EN, pozostawiając konfigurację kolejności, widoczność, adresy CTA i własne sekcje.

- Wcześniejsze `post_content`, tytuły, excerpt i język EN są zapisywane w `_nc_cleanup_backup_20261108` per rekord.
- Opcje strony głównej i stron PL/EN: `nc_home_sections_backup_20261108` i `nc_route_pages_backup_20261108`.
- Ponowne wykonanie nie powoduje duplikowania rekordów.
- Nie zmienia statusu `post_status=publish`; prawdziwe zatwierdzenie medyczne/prawne pozostaje odrębnym procesem.

## Ścieżka testów

`NOVACELL Manager → Czyste treści` oraz `NOVACELL Manager → Podstrony PL/EN`. Porównaj publiczną treść `/wiedza/`, `/lekarze/`, `/z-czym-pomagamy/menopauza/` i stronę główną. Żaden z tych opisów nie powinien zawierać słów `CMS`, `mapa przekierowań`, `[do uzupełnienia]`, `link do profilu` lub instrukcji projektowych.

## Ograniczenia

Nie przeprowadzono recenzji klinicznej twierdzeń medycznych ani ostatecznej oceny prawnej polityk i regulaminu. Nie publikowano pełnych źródeł kliniki w publicznym repozytorium. Testy lokalne są izolowane i nie dowodzą poprawnego działania na hostingu klienta.
