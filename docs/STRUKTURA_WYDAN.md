# Standard repozytorium i paczek instalacyjnych

```
WP-NovaCell/
  plugins/
    novacell-manager/      # Pliki wykonywalne wtyczki
      novacell-manager.php
      includes/
  themes/                  # Motyw (następny etap)
  docs/                    # Dokumentacja techniczna, testy, projekt panelu
  releases/                # Historia i opisy wersji
  dist/                    # Artefakty ZIP (budowane poza katalogiem wtyczki)
```

## Zasady
- Archiwum instalacyjne ma jeden folder główny `novacell-manager`; nie pakować całego repo.
- Nie zamieszczać w instalacyjnym ZIP plików DOCX, PDF, dzienników, zrzutów, dokumentacji wersji i archiwów.
- W każdej wersji synchronizować nagłówek WordPress `Version`, stałą `NC_MANAGER_VERSION`, nazwę ZIP i plik `releases/NOVACELL_Manager_<wersja>.md`.
- Instalacja WordPress: Wtyczki → Dodaj nową → Wyślij → Zainstaluj → Włącz. Hook aktywacyjny uruchamia inicjalizację bez osobnego skryptu setup.
- CI: składnia PHP i później PHP/WordPress Coding Standards, testy integracyjne i testy UI.
- Paczki wydawać po przeglądzie gałęzi i testach na staging, nie rozwijać bezpośrednio na produkcji.
- Dokumentacja i zawartość robocza kliniki wymagające poufności **nie mogą** trafiać do publicznego repo.
