# TASK-005: Fix Attic, PGN Parser, Endgame Upload, and Analytics

**Priority:** Medium  
**Related Bugs:** BUG 014, BUG 015, BUG 016, BUG 017, BUG 019

## Scope
Fix data source, regex parsing, upload safety/ID consistency, and analytics edge cases across modules.

## Affected Files
- `modules/attic/attic.php`
- `modules/pgn.php`
- `modules/endgame/endgame.php`
- `modules/analytics/analytics.php`

## Implementation Checklist
1. **Fix `attic::getHtml()` data source (`modules/attic/attic.php`, line 33 — BUG 014)**
   - Change `articles::get($offset, $limit)` to `attic::get($offset, $limit)`.
2. **Fix `Pgn::parse()` (`modules/pgn.php`, lines 23, 51–57 — BUG 015)**
   - Check the return value of `preg_match($pattern, $game, $matches)` before reading `$matches[1]`.
   - Fix `self::MOVES` regex so it extracts the move text rather than header brackets `[...]`.
3. **Fix `endgame::upload()` (`modules/endgame/endgame.php`, lines 88–92, 113–114 — BUG 017)**
   - Escape or parameterize `$headers[\ChessGame::FEN]` in the duplicate-check query.
   - Capture the inserted `pid` from `$db->insert("endgame", $data)` and insert into `raw` with `["id" => $pid, "data" => $game->getRaw()]`.
4. **Fix `analytics::fixPiecePatterns()` (`modules/analytics/analytics.php`, lines 111–128 — BUG 016)**
   - Add `continue;` after deleting the endgame when `$pattern === ""`.
5. **Fix `analytics::author()` parameter handling (`modules/analytics/analytics.php`, lines 210–214 — BUG 019)**
   - Guard `$action = $params[0] ?? "";` and cast/validate `$pid = isset($params[1]) ? (int)$params[1] : 0;` before checking `if ($pid <= 0)`.

## Acceptance Criteria
- [x] `attic::getHtml()` renders rows from the `attic` table.
- [x] `Pgn::parse()` handles PGNs with missing optional tags without errors and extracts moves properly.
- [x] `endgame::upload()` safely handles FEN strings and keeps `raw.id` synchronized with `endgame.pid`.
- [x] `analytics::fixPiecePatterns()` does not attempt to update deleted rows.
- [x] `analytics::author()` handles missing or `"0"` parameters gracefully.
