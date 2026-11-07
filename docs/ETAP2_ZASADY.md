# NOVACELL — Etap 2 (7.11.2026)

Wydania: **Manager 0.2.0** + **Theme 0.1.0**. Instalacja przez dwie niezależne paczki ZIP w WordPress, w kolejności wtyczka, potem motyw.

Zakres: 9 edytowalnych sekcji strony głównej (przeciąganie i przyciski kolejności, włącz/wyłącz), zakładki PL/EN, WYSIWYG, przyciski i odnośniki, 3 profile lekarzy, 3 ścieżki opieki, 3 problemy i 1 metoda DFPP/TPE. Na froncie trasy polskie i angielskie, przełącznik PL/EN, Manrope+Inter, kolory: #9ED2DF, #55A9CE, #183B50, #F5FAFC oraz logo NOVACELL odtworzone jako SVG. Biblioteka własnych ikon SVG bez emoji.

**Poufność:** Dokument Word jest roboczy, medycznie niezatwierdzony. Plik `plugins/novacell-manager/data/starter-content.private.json` dystrybuowany jest **wyłącznie w instalacyjnym ZIP w rozmowie**, nie w publicznym GitHub. Kod importerów w GitHub powinien działać bez pliku źródłowego (z komunikatem o brakujących danych). Własne modyfikacje CMS nie są nadpisywane przy ponownym imporcie.

## Instalacja
1. Staging WordPress 6.5+, PHP 8.0+, backup.
2. Wtyczki → Dodaj nową → Wyślij → NovaCell Manager 0.2.0 → Włącz.
3. Wygląd → Motywy → Dodaj → Wyślij → NovaCell Theme 0.1.0 → Włącz.
4. Ustawienia → Bezpośrednie odnośniki → Zapisz (gdy wymagane).
5. Administrator sprawdza `/` i `/en/` w środowisku testowym.

## Gdzie edytować
- NOVACELL Manager → Strona główna i sekcje, Podstrony PL / EN, Materiały startowe.
- NOVACELL Manager → Lekarze, Problemy, Ścieżki i Metody. Każdy rekord ma zakładkę PL/EN.
- Wygląd → NOVACELL: strona główna (skrót do edytora sekcji).
- Wygląd → Menu (oddzielne menu PL i EN).

## Bezpieczeństwo i dostępność
- Brak automatycznej publikacji niezatwierdzonego materiału. Podgląd szkiców tylko administrator.
- `noindex, nofollow` i nagłówek X-Robots-Tag dla niezarejestrowanego serwisu.
- Semantyczny HTML, skip link, nagłówki, responsywność, fokus, obsługa klawiatury, sortowanie bez drag-and-drop, prefers-reduced-motion. **Nie zastępuje pełnego audytu WCAG 2.2 AA w rzeczywistym środowisku.**
- Manrope i Inter są pobierane z zewnętrznego Google Fonts — przed produkcją zweryfikować zgodność z polityką prywatności.

## Następny etap
Manager 0.3.x: cennik źródłowy, relacje, FAQ, workflow zatwierdzania, dodatkowe treści C1–C6. Motyw 0.2.x: dodatkowe szablony i doskonalenie dostępności.
