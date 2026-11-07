# NOVACELL Etap 5

- Manager 0.5.0: publiczny podgląd opublikowanych rekordów (bez przypisywania im akceptacji medycznej), tryb bezpiecznych URL `?nc_view=...`, diagnostyka i bezpośrednie odnośniki dla administratora, PL/EN WYSIWYG, relacje, import z dokumentu C1–C6 (roboczy).
- Theme 0.4.0: publiczne renderowanie SSR bez 404 w trybie parametrowym, mapa linków menu/CTA, dostępne FAQ Accordion, screenshot.png 1200×900, kolorystyka i logo NOVACELL, przepisany baner informacyjny, brak automatycznej indeksacji niezatwierdzonych treści.
- Visual Editor 0.1.0: osobna nakładka na Gutenberg, 12 kafelków SVG, przeciąganie oraz przyciski dodawania, inspektor Treść/Wygląd/Rozmiary/Zaawansowane, obsługa klawiatury w zakładkach, ostrzeżenia o kontraście i blok dynamiczny.

**Bezpieczeństwo źródeł importu:** dane dokumentu znajdują się w plikach PHP zabezpieczonych przez kontrolę `ABSPATH`; stare pliki JSON są usuwane podczas migracji (o ile uprawnienia plików pozwalają).

**Testy lokalne:** PHP syntax, JS syntax, izolowany test importu/tras/FAQ/trybu publicznego. Brak wdrożenia na zewnętrzny serwer WordPress (nie został połączony), brak zweryfikowanego audytu WCAG/PSI na hostingu. Opublikowano technicznie 181 rekordów w mock teście z zestawu źródłowego; w rzeczywistym WordPress liczby potwierdzić w panelu diagnostycznym.
