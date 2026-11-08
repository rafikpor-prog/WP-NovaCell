# NOVACELL 0.7.0 — edycja z frontendu, okruszki, katalogi i EN

Wydanie: 8 listopada 2026. Instalatory: `NOVACELL-Manager-0.7.0.zip` oraz `NOVACELL-Theme-0.6.0.zip`. Visual Editor pozostaje w wersji 0.2.0 i nie wymaga reinstalacji.

## Kolejność instalacji

1. Zrób kopię bazy i plików witryny. Zacznij od stagingu.
2. Wtyczki → Dodaj nową → Wyślij wtyczkę → NOVACELL-Manager-0.7.0.zip → Zastąp obecną wersję.
3. Wygląd → Motywy → Dodaj motyw → NOVACELL-Theme-0.6.0.zip → Zastąp obecną wersję i aktywuj.
4. Otwórz panel administracyjny jako administrator, aby uruchomić wersjonowaną migrację English Editorial i naprawę rewrite WordPress.
5. NOVACELL Manager → Naprawa linków 404 → Zapisz ponownie reguły WordPress. Następnie Ustawienia → Bezpośrednie odnośniki → wybierz „Nazwa wpisu” i zapisz.
6. NOVACELL Manager → Treści angielskie → sprawdź raport importu. Zakładki PL / EN edytujesz w tych samych rekordach.

## Gdzie znajduje się tekst?

- Frontend, zalogowany administrator: pasek WordPress → **Edytuj tę stronę**. Prowadzi do rekordu, kreatora strony głównej, podstron PL/EN lub katalogów zależnie od źródła.
- NOVACELL Manager → **Znajdź treść**: wyszukiwarka adresów, rekordów, usług, pakietów i FAQ oraz przyciski edycji.
- NOVACELL Manager → **Strona główna i sekcje**: edycja treści sekcji, kolejności i widoczności, w dwóch językach. Z menu frontendu można przejść bezpośrednio do konkretnej sekcji.
- NOVACELL Manager → **Strony katalogów**: `/z-czym-pomagamy/`, `/chce-zadbac-o-zdrowie/`, `/wyglad-i-estetyka/` i `/metody/`; edytowalne tytuły, leady i opisy PL/EN, karty pod nimi są automatyczne.
- NOVACELL Manager → **Podstrony PL/EN**: strony stałe zarządzane przez Site Builder.
- NOVACELL Manager → **Treści angielskie**: raport wersji 43 EN + zachowany istniejący tekst dla pacjentów międzynarodowych (łącznie 44 źródłowe rekordy).
- Edycja lekarza/metody/ścieżki: aktywne okruszki „NOVACELL Manager → Znajdź treść → Rejestr → Aktualny rekord”, przyciski „Zobacz PL/EN”.

## Błąd Apache / 404

Aplikacja odpowiada za wirtualne trasy, np. `/chce-zadbac-o-zdrowie/`, i zapisuje odpowiednie reguły WordPress. **Pierwsza aktualizacja po instalacji próbuje twardo odświeżyć rewrite rules WordPress, w tym znaczniki `.htaccess` jeżeli hosting na to pozwala.** Nie zastępuje całego pliku `.htaccess` ani nie zmienia konfiguracji Nginx.

Po naprawie wykonywany jest test HTTP strony katalogu. Jeżeli realny kod 200 i właściwa treść są potwierdzone, Manager przełącza menu na czytelne URL. W innym przypadku pozostają bezpieczne adresy `?nc_view=...`.

Przykłady do sprawdzenia:
- `/chce-zadbac-o-zdrowie/`
- `/z-czym-pomagamy/`
- `/metody/`
- `/en/preventive-health/`
- Ratunkowo: `/?nc_view=prevention-index&nc_lang=pl`

**Jeżeli Apache zwraca „Not Found” także po naprawie, a `?nc_view=...` działa, trzeba zweryfikować DocumentRoot oraz przepisywanie URL w konfiguracji hostingu.** Żaden plugin PHP nie wykona przekierowania przed wejściem żądania do WordPress.

## Jak traktować teksty EN

Plik z tabelą podaje **55 powiązań historycznych URL**, a nie kompletny zasób tłumaczeń. Na starych stronach znalazły się twierdzenia o skuteczności procedur, które nie powinny być bezrefleksyjnie przenoszone. Wersja 0.7.0 dostarcza **43 nowe, ustrukturyzowane opisy EN w statusie szkiców redakcyjnych medycznych**, z wypełnieniem dostępnych pól, plus istniejący oryginalny opis EN pacjentów zagranicznych. To pełne pola do edycji, ale **nie jest to zatwierdzony, dosłowny przekład całego 135-stronicowego dokumentu**.

Treści Word i materiały źródłowe znajdą się wyłącznie w prywatnym ZIP właściciela, nie w publicznym repo GitHub. Część historycznych artykułów jest przestarzała, a wymagające oceny specjalisty twierdzenia o MSC, egzosomach, DFPP, NAD+ i laserach nie są przedstawiane jako udowodniona skuteczność.

Wcześniejsze ręczne zmiany EN są chronione; nowy importer podmienia wyłącznie puste, własne wcześniej wygenerowane lub odpowiadające historycznemu materiałowi seed pola. W panelu jest osobny przełącznik świadomego nadpisania ręcznych zmian, a wartości sprzed aktualizacji są zapisane w metadanych `_nc_en_backup_20261108`.

## Dostępność i wydajność

- Semantyczne `h1` do tytułu strony i `h2` do rozdziałów, listy ul/ol, label i prawidłowe taby WYSIWYG PL/EN.
- FAQ: natywny `details/summary`, obsługa klawiatury i focus oraz wersje językowe.
- Wysokokontrastowe granatowe treści na jasnym tle; Inter dla tekstu, Manrope dla nagłówków.
- Lazy loading dla zdjęć wtórnych, odroczony JS; brak migania i automatycznych karuzel; redukcja animacji zgodnie z `prefers-reduced-motion`.
- Okruszki oraz linki admin nie są elementami medycznymi i pojawiają się wyłącznie uprawnionym użytkownikom.

**Nie przeprowadzono automatycznego audytu WCAG 2.2 AA ani pomiarów Core Web Vitals/PageSpeed Insights na rzeczywistym hostingu.** Zapewnione implementacje wymagają jeszcze testów przeglądarek, urządzeń i technologii wspomagających.

## Zasady bezpieczeństwa

Uprawnienia administratora pozostają ograniczone `manage_options` i `edit_post`. Publiczne wpisy są `publish`, ale recenzja medyczna/EN pozostaje niezależnym statusem; nie nadajemy fałszywych podpisów i zatwierdzeń. Nie zmieniamy cennika, relacji ani godzin przyjęć bez źródłowego potwierdzenia.
