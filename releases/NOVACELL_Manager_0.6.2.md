# NOVACELL Manager 0.6.2: Patient-facing Content Cleanup

Data: 8 listopada 2026. Poprawka do instalacji ponad wersją 0.6.1. Nie wymaga ponownej instalacji Theme 0.5.0 ani Visual Editor 0.2.0.

## Poprawki

1. Zamiana notatek roboczych z dokumentu DOCX na opisy skierowane do pacjenta, bez instrukcji technicznych, projektowych i tabeli historycznych przekierowań 301.
2. Jednorazowe, automatyczne nadpisanie wcześniej importowanych opisów, również gdy były edytowane ręcznie. Wcześniejsze wartości pozostają w kopiach zapasowych w bazie WordPress.
3. Korekta widocznych tytułów, leadów, opisów PL, istniejących danych EN, 9 sekcji strony głównej i 3 stron konfiguracyjnych.
4. Zachowanie wszystkich tras, relacji, cen, układu i widoczności sekcji, własnych elementów i aktualnych statusów publikacji.
5. Nowe menu: `NOVACELL Manager → Czyste treści`, z możliwością ponownego wgrania oczyszczonych opisów.
6. Wersja schematu `7` i importu `5` (wymagana do migracji przy aktualizacji przez ZIP).

## Instalacja

1. Backup bazy i plików, najpierw staging.
2. `Wtyczki → Dodaj nową → Wyślij wtyczkę → NOVACELL-Manager-0.6.2.zip` i `Zastąp obecną wersję`.
3. Otwórz jako administrator `NOVACELL Manager → Czyste treści` i sprawdź raport migracji. W razie potrzeby uruchom ponowny import przyciskiem.
4. Sprawdź stronę główną, `Wiedza`, listę lekarzy, profile, opis metody DFPP i ścieżkę menopauzy.

Paczka zawiera wewnętrzne pliki `data/*.php` z oczyszczonym materiałem kliniki, chronione przed bezpośrednim dostępem HTTP. Publiczne GitHub zawiera tylko kod wykonywalny, testy i dokumentację.

**Uwaga:** usunięcie instrukcji technicznych nie oznacza zatwierdzenia medycznego ani prawnego. Opisy podlegają przeglądowi odpowiedzialnej osoby kliniki.
