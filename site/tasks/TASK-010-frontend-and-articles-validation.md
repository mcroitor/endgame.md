# TASK-010: Fix Frontend Form Reset, PGN Viewer Guard, and Article Input Validation

**Priority:** Medium  
**Related Bugs:** BUG 029, BUG 030

## Scope
Fix `clearAll()` and missing PGN handling in `scripts/studydb.js`, and validate `POST` inputs in `articles::create()`.

## Affected Files
- `scripts/studydb.js`
- `modules/articles/articles.php`

## Implementation Checklist
1. **Fix `clearAll()` and `pgnLive()` (`scripts/studydb.js`, lines 107–124 — BUG 029)**
   - Reset `wmin`/`bmin` to `'2'`, `wmax`/`bmax` to `'7'`, `stipulation` and `theme` to `'-'`, `cook.checked` to `false`, and `from_date`/`to_date` to their initial values (`'1900'` and current year).
   - Guard `pgnLive(pid)` against missing or empty `pgn.data` before initializing `LichessPgnViewer`.
2. **Validate `POST` inputs in `articles::create()` (`modules/articles/articles.php`, lines 61–69 — BUG 030)**
   - Default `article-title` and `article-body` to `""` (or reject empty submissions and redirect) instead of passing `null` into `NOT NULL` columns.
   - Add `exit();` after `header("location:" . \config::www . "/?q=about");`.

## Acceptance Criteria
- [x] Clicking "Clear Form" restores all search inputs (piece counts, stipulation, theme, dates, cook checkbox) to their default state.
- [x] Opening `pgnLive()` for a missing `raw` ID does not pass `undefined` to `LichessPgnViewer`.
- [x] Submitting `article/create` with missing `POST` fields does not trigger a SQLite `NOT NULL` constraint error.
