# TASK-003: Fix API Routing, Date Filtering, and SQL Injection

**Priority:** Critical  
**Related Bugs:** BUG 006, BUG 007, BUG 008

## Scope
Restore the `api/?q=pgn` endpoint, align date filter keys between frontend and backend, and eliminate SQL injection in `api/index.php`.

## Affected Files
- `api/index.php`
- `scripts/studydb.js`

## Implementation Checklist
1. **Enable the `pgn` route (`api/index.php`, line 5 — BUG 006)**
   - Uncomment `#[\mc\route("pgn")]` on `get_pgn(array $params)` and verify `pgnLive()` / `getPgn()` in `scripts/studydb.js` receive valid PGN data.
2. **Align date field names (`api/index.php`, lines 25–26 & `scripts/studydb.js`, lines 79–80 — BUG 007)**
   - Update `api/index.php` to read `$request->from_date` and `$request->to_date` (matching `scripts/studydb.js`), or align both sides consistently.
3. **Fix SQL injection in `get_data()` (`api/index.php`, lines 37–53 — BUG 008)**
   - Use prepared statements / PDO parameter binding (or `PDO::quote()` + integer casting for `wmin`, `wmax`, `bmin`, `bmax`, `from_date`, `to_date`) for all user-supplied search filters.

## Acceptance Criteria
- [x] Clicking `PGNLive!` or `Get PGN` in search results loads the PGN from `api/?q=pgn/<pid>`.
- [x] Searching with a date range filters results according to the selected `from_date` and `to_date`.
- [x] Quotes and SQL metacharacters in `author`, `stipulation`, `theme`, `piece_pattern`, or numeric fields cannot alter the SQL query structure.
