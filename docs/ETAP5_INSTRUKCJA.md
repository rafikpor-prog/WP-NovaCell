# NOVACELL — Etap 5 (7.11.2026)

## Instalatory
- `NOVACELL-Manager-0.5.0.zip`: WordPress → Wtyczki → Dodaj nową → Wyślij → zastąp obecną wtyczkę i aktywuj.
- `NOVACELL-Theme-0.4.0.zip`: Wygląd → Motywy → Dodaj → Wyślij motyw → zastąp poprzednią wersję i aktywuj.
- `NOVACELL-Visual-Editor-0.1.0.zip`: Wtyczki → Dodaj nową → Wyślij → aktywuj.

**Uwaga:** przed aktualizacją wykonaj pełną kopię zapasową plików i MySQL. W pierwszej kolejności użyj staging. Wszystkie trzy pakiety mają stałe slugi i standardowe instalatory WordPress, bez ręcznego FTP ani osobnego instalatora PHP.

## Widoczność publiczna i problem 404
1. Zaloguj się do WordPress jako administrator, przejdź do **NOVACELL Manager → Widoczność testowa**. Instalator jednorazowo włącza publiczny odczyt opisów, ale nie zmienia znacznika akceptacji klinicznej.
2. Przejdź do **NOVACELL Manager → Trasy i podgląd**. Zapisz **Tryb bezpieczny (bez 404, parametry ?nc_view)**. To tryb domyślny, działa bez Apache mod_rewrite / konfiguracji nginx.
3. Kliknij **Napraw adresy i sprawdź menu** oraz **NOVACELL Manager → Pełne treści → Uzupełnij brakujące treści** (jeśli jeszcze ich nie ma). Import jest idempotentny i zachowuje ręczne edycje.
4. Przetestuj stronę `/` oraz `/?nc_view=home&nc_lang=en`, opis menopauzy `/?nc_view=ncfull-topic-1&nc_lang=pl`, i `/en/` tylko po włączeniu trybu przyjaznych odnośników.
5. Jeśli bezpieczne adresy działają, a `/z-czym-pomagamy/menopauza/` nadal zwraca 404, sprawdź WordPress **Ustawienia → Bezpośrednie odnośniki → Zapisz** i konfigurację `.htaccess`/serwera. Wtyczka nie może naprawić serwera, który nie kieruje żądań do WordPress.
6. **Wyłącznie po prawidłowym teście serwera** można w diagnostyce przełączyć się na przyjazne URL. W razie błędu wróć do trybu bezpiecznego.

## Redakcja treści
- **NOVACELL Manager → Strona główna i sekcje**: edycja 9 sekcji w PL/EN, zmiana kolejności przyciskami i myszą, widoczność oraz podgląd przez iframe.
- **NOVACELL Manager → Podstrony PL / EN**: strony stałe, pola WYSIWYG.
- **NOVACELL Manager → Ścieżki opieki / Lekarze / Metody / Podstrony i opisy**: każdy element ma obok własne zakładki PL i EN. Brak pełnego tłumaczenia nie jest udawany automatycznie.
- **NOVACELL Manager → Menu główne PL i EN**: utworzenie menu i edycja w **Wygląd → Menu**.
- **Strony → Dodaj nową (Gutenberg)**: po otwarciu edytora wybierz panel **NOVACELL Visual Editor**. Można wstawiać kafelki, przeciągać, edytować style i wstawiać dynamiczny blok `Sekcja NOVACELL`.

## Ważne rozróżnienie
Wpisy są opublikowane technicznie zgodnie z poleceniem, a publiczna strona jest widoczna bez logowania. Nie oznacza to akceptacji klinicznej. Dokument Word zawiera nieuzupełnione dane i ceny robocze. Frontend wyświetla komunikat o weryfikacji medycznej i przekazuje `noindex, nofollow` do czasu faktycznego zatwierdzenia.

## Wydajność, SEO, dostępność
- SSR HTML i proste `details/summary` dla FAQ bez JavaScript.
- Lazy-load zdjęć w kartach; podgląd nie wymaga animacji; grafiki SVG i screenshot 1200 × 900.
- WCAG 2.2 AA jako cel: wysoki kontrast, skip-link, focus-visible, semantyczny HTML, obsługa klawiatury, drag-and-drop z alternatywą kliknięcia, preferencje ograniczania ruchu, ostrzeżenia o niskim kontraście w edytorze.
- Rzeczywiste pomiary PageSpeed Insights, Lighthouse, VoiceOver/NVDA i WCAG 2.2 AA wymagają testów na hostingu, których nie udajemy w raporcie.
- SEO meta description, canonical i hreflang dla rzeczywistych tłumaczeń. Niektóre opisy EN nadal oczekują tłumaczenia, mimo istniejących zakładek.

## Etap 6
Testy w prawdziwym WordPress, pomiar Core Web Vitals, pełny audyt WCAG, weryfikacja źródłowego cennika 2027, mapa 301, zatwierdzenia medyczne/prawne, przyjazne adresy i kontrolowany rollout z rollbackiem.
