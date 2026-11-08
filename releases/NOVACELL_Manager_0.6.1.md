# NovaCell Manager 0.6.1 — poprawka ekranów administracyjnych

Data: 8 listopada 2026.

## Poprawiono

1. Kolejność `admin_menu`: menu nadrzędne `novacell-manager` jest rejestrowane z priorytetem 1, **przed** podmenu zarejestrowanymi z domyślnym priorytetem 10. Wcześniej WordPress mógł przypisać ekranom błędny identyfikator hooka `admin_page_*`, powodując „Brak uprawnień dostępu do wybranej strony”.
2. Pulpit Managera pokazuje stan rejestracji wszystkich ośmiu ekranów administracyjnych (`8/8`) lub listę problemów.
3. Nie przyznajemy automatycznie `manage_options` innym rolom ani nie obniżamy wymaganych uprawnień. Dostęp pozostaje wyłącznie dla kont, które faktycznie mają `manage_options`.
4. Nie zmieniono statusów publikacji, danych importu, sekcji PL/EN, tras frontendowych ani ustawień widoczności.

## Instalacja

Na staging i po backupie: WordPress → Wtyczki → Dodaj nową → Wyślij wtyczkę → `NOVACELL-Manager-0.6.1.zip` → Zainstaluj → Zastąp wersję 0.6.0 → Włącz (jeśli konieczne).

**Nie instaluj ponownie Theme 0.5.0 ani Visual Editor 0.2.0, jeżeli są już aktywne.**

## Sprawdzenie

1. `/wp-admin/admin.php?page=novacell-manager`: komunikat `Menu NOVACELL: 8/8 ekranów zarejestrowanych poprawnie`.
2. `/wp-admin/admin.php?page=novacell-pages`: Podstrony PL/EN.
3. `/wp-admin/admin.php?page=novacell-home-builder`: Strona główna i sekcje.
4. `/wp-admin/admin.php?page=novacell-starter`: Materiały startowe.
5. `/wp-admin/admin.php?page=novacell-preview-settings`: Widoczność.
6. `/wp-admin/admin.php?page=novacell-diagnostics`: Trasy i podgląd.

Slugi `/novacell-pages`, `/novacell-starter` itd. nie są publicznymi URL strony: wchodzimy przez `wp-admin/admin.php?page=...`.

## Zakres testów

- Lokalne PHP lint oraz izolowany test kolejności rejestracji menu i poprawnych identyfikatorów ośmiu ekranów.
- Testy regresji importu / tras z Etapu 5 i 6 przechodzą.
- Nie wykonano testów na serwerze klienta, bo instalacja NOVACELL nie jest dostępna przez połączoną wtyczkę administracyjną.
- Jeśli po aktualizacji dalej pojawia się 403 i konto nie ma `manage_options`, należy sprawdzić rzeczywistą rolę i aktywność wtyczki; nie zmieniać uprawnień na ślepo.

## Wydanie

ZIP zawiera wyłącznie katalog `novacell-manager` i pliki wtyczki. Dokumentację i testy przechowujemy w `docs/`, `releases/`, `tests/` poza instalatorem.