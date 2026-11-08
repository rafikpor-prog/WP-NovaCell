# Dlaczego pięć ekranów NOVACELL wyświetlało „Brak uprawnień”?

## Powód w kodzie 0.6.0

W `Plugin::boot()` funkcje `Readiness::boot()`, `Site_Builder::boot()`, `Preview_Settings::boot()` i `Diagnostics::boot()` dołączały callbacki `admin_menu` wcześniej niż `Admin::register_menu()`. Wszystkie działały z priorytetem 10. Kolejność wykonania powodowała, że `add_submenu_page('novacell-manager', ...)` odbywało się zanim `add_menu_page('novacell-manager', ...)` zarejestrowało rodzica.

WordPress wylicza `get_plugin_page_hookname()` na podstawie istniejącego menu nadrzędnego. Bez rodzica zarejestrowany hook może mieć postać `admin_page_novacell-pages` zamiast `novacell-manager_page_novacell-pages`. Późniejszy dostęp do `admin.php?page=novacell-pages` wtedy się nie zgadza i WordPress zgłasza błąd uprawnień.

## Naprawa

Zmieniono kolejność: `add_action('admin_menu', [Admin::class, 'register_menu'], 1)`. Pozostałe callbacki domyślnego priorytetu 10 działają po rodzicu. Do pulpitu dodano diagnostykę hooków, a do `tests/` test regresji.

## Sprawdzenie bezpieczne

Otwórz pulpit NOVACELL na koncie administratora, zobacz status `8/8` i użyj widocznych przycisków. Użytkownik bez `manage_options` nadal nie ma dostępu. Jeśli komunikat 403 dotyczy całego wp-admin, nie jest to problem tych hooków — sprawdź sesję i rolę użytkownika oraz aktywację wtyczki.

## Źródło

WordPress developer reference: `add_submenu_page()` oraz `get_plugin_page_hookname()`; identyfikator hooka zależy od rodzica.