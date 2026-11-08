# Changelog 0.7.0 — 2026-11-08

## NovaCell Manager 0.7.0
- Pasek administracyjny na froncie: **Edytuj tę stronę**, link bezpośrednio do właściwego źródła, podmenu 9 sekcji home.
- Breadcrumbs w ekranach administracyjnych i formularzach rekordów.
- Wyszukiwarka treści, usług, FAQ, pakietów i stron wg tytułu/URL, z linkami do edycji.
- Edytor czterech stron katalogowych PL/EN (lead, tytuł, WYSIWYG).
- Ulepszony instalator, migracja schematu 8 i jednorazowy hard flush reguł rewrite WordPress, test HTTP i wybór trybu URL.
- 43 źródłowo powiązane, nowe redakcyjne zestawy EN wypełniające stosowne pola, z raportem i zabezpieczeniem wcześniejszych zmian; źródłowa EN strona pacjentów międzynarodowych pozostaje bez zmian.
- Bez zmian: ceny, profil uprawnień, procedura zatwierdzeń medycznych.

## NOVACELL Theme 0.6.0
- Odblokowany podgląd katalogów i ich edycja poprzez Manager.
- Naprawione lokalizowanie slugów działów (przy poprawnym przekazywaniu żądań do WP).
- Kontekstowe okruszki na froncie, zgodność z opublikowanymi rekordami.
- Nowe tłumaczenie FAQ w accordionie, regulacja kontrastu/spacingu/czytelności, bez migania.
- Logo, screenshot, kolory i fonty bez zmian.

## Visual Editor 0.2.0
- Bez zmian w kodzie; pozostaje kompatybilny i aktywny.

## Kontrola jakości
- PHP lint i JavaScript syntax.
- Test Stage5/Stage6/content-cleanup, test Stage7 (PL/EN, importer, slug-index, własne edycje, archiwa).
- Test integralności ZIP i kompletności polskiej/angielskiej paczki.
- Bez testów bezpośrednich na serwerze NOVACELL, Lighthouse i screen readerach.
