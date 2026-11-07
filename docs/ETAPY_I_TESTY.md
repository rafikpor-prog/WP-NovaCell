# Plan realizacji i akceptacji

Wersja robocza. Kolejność chroni dane i ogranicza konieczność przerabiania motywu.

## Etap 0 — audyt i przygotowanie
- Inwentaryzacja treści, mediów, obecnych adresów, integracji, motywu i aktywnych wtyczek na środowisku testowym.
- Zamrożenie macierzy typów treści, pól i relacji oraz listy obowiązkowych zatwierdzeń.
- Uzyskanie wersji źródłowej cennika do importu; nie traktować tekstu z projektu strony jako jedynego cennika.
- Określenie terminów, odpowiedzialności, pól nierozstrzygniętych.
**Akceptacja:** zatwierdzony schemat danych + zestaw tras URL + lista ryzyk.

## Etap 1 — fundament NovaCell Manager
- Struktura pluginu, instalator i migracje wersji, role, capabilities.
- CPT, taksonomie, relacje, formularze oraz rejestrowane REST meta.
- Admin Dashboard + ustawienia globalne; system lokalizacji i linków zewnętrznych.
- Bezpieczna aktualizacja schematu bez usuwania danych.
**Akceptacja:** każdy typ rekordu można utworzyć, edytować, wycofać i powiązać z innym; zmiana jest w jednym miejscu.

## Etap 2 — redakcja treści / sekcje
- Admin dla ścieżek opieki z rozdziałami i wariantami objawy/profilaktyka.
- Formularze WYSIWYG dla opisów stałych, FAQ, pól lekarza i metod.
- Kreator sekcji strony głównej i stron statycznych: kolejność, widoczność, wybór źródła danych.
- Panel zatwierdzeń z audytem i walidacją medyczną / prawną.
**Akceptacja:** bez kodowania da się skonfigurować stronę główną, szablon ścieżki i profil lekarza.

## Etap 3 — cennik i integracje
- Import CSV/XLSX do usług i pakietów po kontroli sum i duplikatów.
- Jedno źródło cen; cena PLN/EUR i daty obowiązywania; podgląd zmian.
- ZnanyLekarz jako zewnętrzna rejestracja, telefon, WhatsApp, bezpieczny formularz.
- Bramka ukrywania niedostępnych usług, lekarzy i metod.
**Akceptacja:** aktualizacja usługi zmienia wszystkie publiczne wystąpienia i nie wpływa na wyłączone terapie.

## Etap 4 — NovaCell Theme
- Responsywne szablony, `theme.json`, biblioteka sekcji, ikony SVG.
- Karta ścieżki, metody, lekarza, artykułu, cennika; dostępne menu i filtrowanie.
- SEO, SSR, zgodność adresów, wydajność, wersje językowe.
**Akceptacja:** odwzorowanie informacji bez kopiowania danych; test z JS wyłączonym, Lighthouse i klawiaturą.

## Etap 5 — NovaCell Visual Editor
- Osobna wtyczka wyłącznie dla ekranu blokowego, z panelem bocznym i płótnem.
- Kafelki nazwane językiem redakcji, własne ikony, drag-and-drop i alternatywa klawiaturowa.
- Inspektor Treść / Wygląd / Rozmiary / Zaawansowane; reużycie block supports.
- WYSIWYG i live preview, undo/redo, autosave, zgodność z WordPress i Gutenberg.
**Akceptacja:** zapis pozostaje poprawnymi blokami WordPress; edytor standardowy może nadal otwierać i modyfikować tę samą stronę.

## Etap 6 — migracja i publikacja
- Import zatwierdzonych treści, meta SEO, artykułów, lekarzy, pozycji cennika.
- Pełna mapa 301 na bazie eksportu obecnego CMS i weryfikacja każdej trasy.
- Przegląd językowy, medyczny, prawny i WCAG; testy wydajności i bezpieczeństwa.
- Kopia zapasowa, staging, próba wdrożenia i rollback.
**Akceptacja:** brak niezamierzonych 404, duplikatów cen, niezatwierdzonych treści i niedziałających CTA.

## Praca w GitHub
- `main`: stan zatwierdzony; funkcje w gałęziach `feature/...`.
- PR z opisem zmian i instrukcją testów. Bez bezpośrednich aktualizacji produkcji z gałęzi rozwojowej.
- Testy CI: PHP lint, PHPCS/WordPress Coding Standards, testy funkcjonalne PHP, JS lint/build i testy e2e edytora.
- Wydania z tagiem wersji, changelog i paczką ZIP dla każdej wtyczki i motywu.
- Sekrety tylko w środowisku wdrożeniowym, nie w repozytorium.

## Lista do weryfikacji przed importem
- Oryginalny arkusz cennika oraz jego data obowiązywania.
- Liczba pakietów w pliku źródłowym vs zestawienie dokumentacyjne.
- Profil i uprawnienia do publikacji każdego lekarza; dane kontaktowe i godziny.
- Statusy prawne i źródła dla metod o nieustalonym lub eksperymentalnym charakterze.
- Zakres materiałów do publikacji w języku angielskim.
- Dokumenty prawne i zgody na publikację przypadków.
- Spis istniejących URL, zasobów i map przekierowań.
