# Harmonogram wdrożenia z wczesnym podglądem NOVACELL

**Data decyzji:** 7 listopada 2026. **Status:** plan realizacji, nie wdrożenie. **Punkt wyjścia:** NovaCell Manager 0.1.1 w wersji testowej.

Ten dokument opisuje **6 etapów technicznych** budowy witryny, odmiennych od **3 etapów uruchamiania oferty medycznej (Start / Programy / Rozszerzenia)** z pliku źródłowego `Nowa strona NOVACELL` v3, 5.11.2026.

## Zasada wiążąca dla wszystkich etapów

Nie tworzymy pustych modułów z tekstami `Lorem ipsum`. Każda dostarczana wersja ma pokazywać przykłady z dostarczonej dokumentacji jako **robocze treści przeznaczone do akceptacji kliniki**. Treści oznaczone `[do uzupełnienia]` pozostają widoczne jako zadania, a nie zamieniane na fikcyjne numery, kwalifikacje, godziny lub ceny.

Przygotowujemy importer danych roboczych z identyfikatorami źródłowymi, tak aby:
1. Dwukrotne uruchomienie nie tworzyło duplikatów.
2. Import nie nadpisywał ręcznie poprawionych rekordów bez potwierdzenia.
3. Ustawiał status szkicu / do recenzji, **nigdy** automatycznie `publish`.
4. Pokazywał przypisania i zależności pomiędzy ścieżkami, lekarzami, metodami, usługami, artykułami i FAQ.
5. Dawał możliwość usunięcia wyłącznie danych demonstracyjnych i zachowania własnych treści.
6. W demonstracyjnym środowisku WordPress wyświetlał niesprawdzone treści tylko zalogowanym użytkownikom z odpowiednim uprawnieniem. Nie indeksować staging.

## Etap 1 — fundament i pierwszy import z dokumentu (Manager 0.2.0)

- Rozbudowa istniejącego `novacell-manager` o edytor profili lekarzy, model relacji, podgląd grafu powiązań i szkicowe rekordy.
- Pierwsze importowane materiały: strona główna (C1, temat 23), `Jak pracujemy` (C1, temat 24), trzy profile wymienione w C1, ścieżka menopauzy (C2, temat 1), diagnostyka i plan (C3, temat 8) oraz przykładowa metoda DFPP/TPE (C5).
- Import wpisów jako szkice. Wprowadzić wyłącznie dane istniejące w dokumencie; ceny i statusy wymagające zatwierdzenia prezentować jako robocze.
- W panelu administratora: `NOVACELL Manager → Import i podgląd → Załaduj treści robocze` (do zaimplementowania); `Powiązania` i `Sprawy do uzupełnienia`.

**Odbiór:** można wejść w rekord menopauzy, zobaczyć przypisanych lekarzy i metody; można poprawić zapisany tekst WYSIWYG. Udokumentowany test importu powtórnego bez duplikatów.

## Etap 2 — pierwszy Theme i rzeczywisty frontend (Theme 0.1.0)

Rozpoczynamy motyw już teraz, nie dopiero na końcu rozwoju CMS.
- Motyw blokowy (WordPress block theme) + `theme.json`, design tokens, podstawowe menu, header, footer, komponenty SSR.
- Home (C1, temat 23): hero „Z czym możemy Ci pomóc?”, kafelki problemów, „Jak pracujemy” (6 kroków), lista lekarzy, „Dlaczego NOVACELL”, Wiedza, pacjenci zagraniczni, kontakt.
- Pierwsze szablony: `/jak-pracujemy`, `/lekarze`, `/lekarze/[profil]`, `/z-czym-pomagamy/menopauza`, `/chce-zadbac-o-zdrowie/diagnostyka-i-plan`.
- Łączenie danych z CMS — karta lekarza / metody nie jest kopią danych tekstowych.
- Responsywność, brak auto-przewijanych karuzel, podstawowa dostępność od pierwszej wersji.
- Strony robocze w zamkniętym staging; publikacja publiczna wyłącznie po zatwierdzeniu.

**Odbiór:** pierwsza pełna nawigowalna makieta frontendowa z rzeczywistymi tekstami i funkcjonalnymi dynamicznymi relacjami.

## Etap 3 — pełne relacje, ceny i workflow (Manager 0.3.x)

- Import zatwierdzonego źródła cennika, wariantów PLN/EUR, pakietów, powiązań z metodami i ścieżkami; brak ręcznych kopii cen.
- Lekarze, FAQ, artykuły, miejsca wykonania badań, warianty profilaktyczne i objawowe.
- Workflow: szkic → recenzja medyczna → weryfikacja prawna tam, gdzie potrzebna → zatwierdzenie → publikacja. Każda istotna edycja unieważnia wcześniejszą akceptację.
- Uzupełnienie większości materiałów etap 1 z załączników C1–C6: 42 strony według dokumentu, po weryfikacji mapowania „temat” vs „podstrona”.
- Moduł `Sprawy do uzupełnienia` i walidacja przed publikacją.

**Odbiór:** jedna zmiana zatwierdzonej ceny lub profilu lekarza aktualizuje wszystkie miejsca; niezweryfikowana metoda nie zostaje opublikowana.

## Etap 4 — docelowy Theme (Theme 0.2.x–1.0)

- Wszystkie szablony dla ścieżek, metod, lekarzy, wiedzy, FAQ, cennika, kontaktu i dokumentów prawnych.
- Mobilny układ, dostępna wyszukiwarka, filtracja problemów, poprawne adresy kanoniczne, SSR, schematy danych.
- PL i EN w zakresie istniejących zaakceptowanych tłumaczeń; DE w kolejnych rozszerzeniach.
- Spójne warianty sekcji, zdjęcia rzeczywiste od kliniki, przyjazny kontrast i typografia, WCAG 2.2 AA.

**Odbiór:** spójny frontend z działającymi kartami, nawigacją i zawartością pochodzącą z CMS.

## Etap 5 — NovaCell Visual Editor

- Samodzielna wtyczka `novacell-visual-editor` współpracująca z natywnym Gutenberg.
- Kafelki z prostymi nazwami, własne ikony SVG, drag-and-drop + alternatywa klawiaturowa.
- Inspektor Treść / Wygląd / Rozmiary / Zaawansowane; dynamiczne źródła danych.
- Podgląd live w kontekście Theme, edycja RichText/bloków bez utrzymywania kopii HTML w alternatywnym formacie.

**Odbiór:** układ strony można poprawiać z panelu graficznego; zwykły Gutenberg nadal otwiera poprawnie zapisane bloki.

## Etap 6 — pełna migracja i publikacja

- Weryfikacja i import pozostałych treści, adresów i mediów.
- Przekierowania 301 (w dokumencie C6 mapa 55 starych podstron), testy linków, SEO i danych strukturalnych.
- Przegląd medyczny i prawny, integralność cennika, prywatność i bezpieczeństwo formularzy.
- Testy dostępności, wydajności, aktualizacji ZIP, rollback, staging → produkcja.
- Publikacja tylko zatwierdzonych treści i dostępnych rzeczywistych świadczeń.

**Odbiór:** strona uruchomiona bez awarii obecnego serwisu i z potwierdzoną możliwością rollbacku.

## Stały format dostarczania wydań

- `NOVACELL-Manager-X.Y.Z.zip` — tylko pliki wykonywalne wtyczki.
- `NOVACELL-Theme-X.Y.Z.zip` — tylko pliki motywu.
- `NOVACELL-Visual-Editor-X.Y.Z.zip` — tylko pliki nakładki.
- `releases/` — historia zmian, testy i instrukcje.
- `docs/` — architektura, mapa importu, spis pól i relacji.

Każda dostawa: ZIP + zmiany + instrukcja weryfikacji w panelu + co już wypełniono + co nadal wymaga akceptacji.

## Źródło merytoryczne

`Nowa strona NOVACELL` v3, 5 listopada 2026, 135 stron: C1 (strona główna i stałe), C2 (kobiety), C3 (ogólne), C4 (estetyka), C5 (metody), C6 (EN, cennik, wiedza i przekierowania). Oryginalnego pliku zawierającego niezatwierdzony materiał kliniki nie dodajemy do publicznego repo.
