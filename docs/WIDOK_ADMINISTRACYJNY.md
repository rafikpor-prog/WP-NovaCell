# Projekt UI / UX panelu zarządzania i nakładki Gutenberg

## Zasada projektowa

Użytkownik nie musi wiedzieć, czym jest CPT, post meta, template part lub block variation. Interfejs używa nazw zrozumiałych dla zespołu kliniki.

### Nawigacja „NOVACELL Manager”

- **Pulpit** — status publikacji, zadania, informacje wymagające aktualizacji.
- **Strony i sekcje** — strona główna, strony stałe, kolejność i ukrywanie sekcji.
- **Z czym pomagamy** — problemy pacjentów i ścieżki opieki.
- **Metody i terapie** — opisy, status, dowody, ryzyka.
- **Lekarze i zespół** — profile, specjalizacje, godziny, rejestracja.
- **Usługi i cennik** — usługi, pakiety, import, wersje i waluty.
- **Wiedza i pytania** — artykuły, FAQ, źródła.
- **Materiały do zatwierdzenia** — workflow, wersje i audyt.
- **Kontakt i ustawienia** — telefon, WhatsApp, godziny, social, SEO, integracje.
- **Przekierowania i języki** — trasy 301, tłumaczenia, hreflang.

Każdy ekran ma wyszukiwanie, filtry i statusy. Szerokie tabele przekształcają się na mobilnych ekranach w przejrzyste karty. Zmiany z potencjalnym wpływem na publikację otrzymują podgląd miejsc, w których zostaną użyte.

### Panel edytora wizualnego

**Lewa kolumna**: „Dodaj element” z kafelkami i ikonami, a po wybraniu elementu zakładki:

1. Treść — WYSIWYG, obrazy, linki i wybór źródła danych.
2. Wygląd — typografia, barwy i wariant komponentu.
3. Rozmiary — marginesy, odstępy, szerokości, obramowania.
4. Zaawansowane — atrybuty, klasy CSS, kotwice, ustawienia dostępności.

**Środek**: rzeczywisty podgląd strony w kontekście stylów motywu. Szybkie przełączanie Desktop/Tablet/Telefon, brak niezależnej kopii dokumentu.

**Prawy panel**: domyślnie zamknięty; „Ustawienia wpisu” otwierane na żądanie. Górny pasek: Cofnij/Ponów, Podgląd, Zapisz, Opublikuj, Powrót do panelu.

### Kafelki (nazwy redakcyjne)

Tekst; Nagłówek; Zdjęcie; Galeria; Film; Przycisk; Dwie kolumny; Sekcja z tłem; Lista; Pytania i odpowiedzi; Zakładki; Kroki opieki; Karta lekarza; Karty problemów; Karta metody; Cennik; Kontakt i formularz; Wyróżniona informacja; Artykuły; Mapa dojazdu.

Ikony w repozytorium SVG o spójnym stylu, wspólna biblioteka; SVG oczyszczane, odpowiednie alternatywy tekstowe, cel dotykowy i fokus klawiatury.

### Granice integracji

- Standardowe bloki WP: obsługiwane przez natywne API i mechanizmy block supports.
- Bloki własne: pełna obsługa stylów i treści w specjalnym panelu.
- Bloki stron trzecich: adaptory tylko tam, gdzie można bezpiecznie odczytać i zapisać atrybuty; w przeciwnym razie otworzyć ich natywny inspektor. **Nie obiecujemy automatycznego przeniesienia 100% niepublicznych opcji cudzych bloków**.
- Przestawianie elementów korzysta z mechanizmów Gutenberga i obsługuje klawiaturę.
- W administracyjnych formularzach opisów działa `wp_editor`; wewnątrz edytora blokowego używamy natywnego RichText/BlockEdit, ponieważ instancji TinyMCE nie wolno dowolnie przenosić po DOM.

### Definicja ukończenia

Po wyłączeniu nakładki treść pozostaje dostępna w zwykłym Gutenberg, na frontendzie nie rozjeżdża się układ, a projekt spełnia WCAG 2.2 AA również dla obsługi edytora.
