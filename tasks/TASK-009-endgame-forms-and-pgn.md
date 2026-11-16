# TASK-009: Fix Endgame Forms, Upload Metadata, Themes, and PGN Download

**Priority:** Medium  
**Related Bugs:** BUG 025, BUG 026, BUG 027, BUG 028

## Scope
Resolve missing endgame creation template, populate integer piece-count metadata during PGN import, render theme options in the search form, and output raw PGN text on PGN download.

## Affected Files
- `modules/endgame/endgame.php`
- `modules/endgame/templates/endgame-form.template.php`
- `modules/endgame/templates/endgame-search-form.template.php`
- `scripts/studydb.js`

## Implementation Checklist
1. **Provide `endgame-form.template.php` (`modules/endgame/endgame.php`, lines 32–42 — BUG 025)**
   - Create `modules/endgame/templates/endgame-form.template.php` (or update `endgame::newForm()` to return a clear placeholder/implemented form) so `/?q=endgame/new` does not trigger a `TypeError`.
2. **Compute `whitep`, `blackp`, `piece_pattern`, and `cook` in `endgame::upload()` (`modules/endgame/endgame.php`, lines 105–111 — BUG 026)**
   - Calculate integer counts for `whitep` and `blackp` (and `piece_pattern`) from `$headers[\ChessGame::FEN]`, and set `cook` to integer `0` instead of `""`.
3. **Populate `<!-- themes -->` in `endgame::search_form()` (`modules/endgame/endgame.php`, lines 141–146 & `endgame-search-form.template.php`, lines 41–44 — BUG 027)**
   - Include `<option value='-'>Any theme</option>` and distinct theme `<option>` values from the database when filling `endgame-search-form.template.php`.
4. **Return PGN text from PGN download endpoints (`modules/endgame/endgame.php`, lines 16–30 & `scripts/studydb.js`, lines 138–141 — BUG 028)**
   - Echo `$pgn["data"]` (with `Content-Type: text/plain`) instead of `json_encode($pgn)` in `endgame::get()`.
   - Point `getPgn(pid)` in `scripts/studydb.js` to `/?q=pgn/` + `pid` so clicking "Get PGN" downloads a `.pgn` file rather than raw JSON.

## Acceptance Criteria
- [x] `/?q=endgame/new` renders without a `file_get_contents()` / `TypeError` failure.
- [x] Newly uploaded PGN studies have integer `whitep`, `blackp`, and `cook` values and appear in piece-count searches.
- [x] The search form Theme `<select>` defaults to `-` ("Any theme") and lists available themes.
- [x] Clicking "Get PGN" downloads valid `.pgn` text instead of JSON.
