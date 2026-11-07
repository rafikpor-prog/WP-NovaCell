# NovaCell Manager 0.1.0 (fundament)

**Status:** pierwsza wersja deweloperska. Nie uruchamiać na stronie produkcyjnej bez testów.

## Instalacja testowa

1. Pobierz katalog `plugins/novacell-manager` z gałęzi `feature/manager-0.1.0`.
2. Umieść go w `wp-content/plugins/novacell-manager/` na środowisku **staging** (WordPress >= 6.5, PHP >= 8.0).
3. Aktywuj w menu **Wtyczki**.
4. W lewym menu wybierz **NOVACELL Manager**.

Nie instaluj całego repozytorium jako jednej wtyczki.

## Co można przetestować

- Panel NOVACELL Manager z kartami 7 rejestrów.
- **Problemy pacjentów**, **Ścieżki opieki**, **Metody i terapie**, **Lekarze i zespół**, **Pozycje cennika**, **Pakiety**, **FAQ**.
- W ścieżce opieki edycja 8 sekcji za pomocą natywnych edytorów WYSIWYG.
- W ścieżce opieki przypisywanie lekarzy, metod i usług; rekordy należy utworzyć przed powiązaniem.
- Metoda: status proceduralny, wstępna kategoria dowodów, przeciwwskazania i źródła.
- Pozycja cennika: kwota PLN, kwota EUR i opcja „od”. Brak automatycznych przeliczeń walut.
- Wszystkie typy są na razie **wewnętrzne**. Nie generują stron publicznych i nie wystawiają REST API.

## Kroki weryfikacji

1. Dodaj lekarza, np. fikcyjny testowy profil „Lekarz testowy”.
2. Dodaj usługę „Konsultacja testowa”, ustaw cenę 950,00 PLN; zapisz i ponownie otwórz.
3. Dodaj metodę „Metoda testowa” ze statusem standardowym i wypełnij opis źródeł.
4. Dodaj ścieżkę „Ścieżka testowa”, opisz wprowadzenie w WYSIWYG, przypisz lekarza, metodę i usługę.
5. Zapisz, odśwież formularz i sprawdź zachowanie treści, relacji i cen.
6. Potwierdź, że publiczny adres ścieżki nie powstał automatycznie, a istniejąca strona klienta działa bez zmian.

## Ograniczenia tego wydania

- Brak importu cennika, walidacji na poziomie formularza, wersjonowania cen i weryfikacji danych publikacyjnych.
- Brak workflow zatwierdzania medycznego, audytu zmian i ról zespołu. Z tego powodu typy nie są jeszcze publiczne.
- W edytorze relacji tymczasowo maksymalnie 200 rekordów na pole; docelowo wyszukiwanie AJAX.
- Brak frontendowych szablonów, SEO, języków, nakładki Gutenberg i automatycznych aktualizacji.
- Edytor WYSIWYG w meta-boxach wymaga przetestowania z rzeczywistą konfiguracją WordPress i wtyczkami.
- Brak wbudowanych danych pacjentów, nie wprowadzaj danych osobowych ani wrażliwej dokumentacji do tych pól.

## Architektura

Główny plik: `novacell-manager.php`; klasy wewnętrzne w `includes/`.

Gdy ten szkielet przejdzie testy, rozbudowujemy system o workflow zatwierdzeń, dynamiczne karty i konfigurator sekcji, a następnie niezależny frontend.
