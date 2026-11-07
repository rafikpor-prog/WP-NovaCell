# NOVACELL Manager 0.1.1

Data: 7.11.2026. Status: **wydanie deweloperskie do testów staging**.

## Zawartość
- Instalacja z ZIP przez natywny instalator wtyczek WordPress.
- Automatyczna inicjalizacja: kontrola minimalnego PHP i WP, rejestracja typów treści, uprawnienia administratora i wersja schematu.
- 7 rejestrów wewnętrznych, niezależne sekcje WYSIWYG, powiązania, pole cen PLN/EUR i „od”.
- Kategorie statusów metod i uproszczone oznaczenia siły dowodów do późniejszej recenzji.
- Wyłączenie wtyczki **nie usuwa wprowadzonych danych**.
- Rozdzielenie dokumentacji technicznej od paczki instalacyjnej.

## Jak zainstalować
1. Utwórz backup. Użyj staging (WordPress >= 6.5, PHP >= 8.0).
2. W panelu WP wybierz Wtyczki → Dodaj nową → Wyślij wtyczkę.
3. Wskaż `NOVACELL-Manager-0.1.1.zip`, zainstaluj i aktywuj.
4. W menu głównym pojawi się NOVACELL Manager.

## Testy
Utwórz lekarza, metodę, usługę za 950,00 PLN i ścieżkę powiązaną z tymi rekordami. Zapisz, odśwież, sprawdź dane. Zweryfikuj brak automatycznie publikowanych publicznych podstron. Dezaktywuj i aktywuj: wpisane rekordy powinny zostać zachowane.

## Ograniczenia
Nie jest to wersja produkcyjna. Brak jeszcze zatwierdzania medycznego, pełnych walidatorów, cennika źródłowego, importera, motywu i nakładki edytora. Nie podawaj do bazy danych zdrowotnych pacjentów, nie publikuj ofert w trybie niezatwierdzonym.

## Porządek
ZIP obejmuje jedynie katalog `novacell-manager/` z kodem PHP. Opisy wydań: `releases/`, dokumentacja: `docs/`. Nie umieszczamy tych plików w katalogu instalacyjnym.
