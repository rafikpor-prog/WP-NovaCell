# WP-NovaCell

Repozytorium prac nad nowym serwisem WordPress NOVACELL.

## Założenia techniczne

Projekt składa się z trzech niezależnie wersjonowanych elementów:

- **NovaCell Manager** — wtyczka CMS do zarządzania treściami strukturalnymi, relacjami, sekcjami stron, cenami, procesem akceptacji i API.
- **NovaCell Visual Editor** — osobna nakładka edycyjna na natywny edytor blokowy WordPress (Gutenberg). Nie używamy Elementora.
- **NovaCell Theme** — lekki motyw blokowy, odpowiedzialny za frontend, dostępność, wydajność i style.

## Zasady

1. Jedno źródło prawdy: ceny, lekarze, opisy metod i dane kontaktowe mają własne rekordy, powiązane ze stronami.
2. Brak danych pacjentów, dokumentacji medycznej, danych dostępowych i sekretów w Git.
3. Zmiany medyczne wymagają weryfikacji merytorycznej przed publikacją.
4. Treść publiczna renderowana po stronie serwera; edytor ma działać na blokach WordPress.
5. Rozwój przez osobne gałęzie, przegląd zmian i testy przed wdrożeniem.

Szczegółowa architektura i plan wdrożenia będą przechowywane w katalogu `docs/`.

> Repozytorium jest publiczne. Nie zamieszczamy w nim bez dodatkowej decyzji pełnych roboczych materiałów kliniki ani danych poufnych.
