# TASK-008: Fix PDF Export Defaults, Missing PGN Guard, and Diagram Validation

**Priority:** High  
**Related Bugs:** BUG 022, BUG 023, BUG 024

## Scope
Fix default piece-count filters and missing `raw` record handling in PDF export, and harden FEN/EPD rank validation and color lookup in the diagram renderer.

## Affected Files
- `modules/pdf/pdf.php`
- `modules/diagram/diagram.class.php`

## Implementation Checklist
1. **Fix `wmax` and `bmax` defaults (`modules/pdf/pdf.php`, lines 50–58, 68–76 — BUG 022)**
   - Change `["options" => ["default" => 0]]` to `["options" => ["default" => 32]]` in both `getWhiteMax()` and `getBlackMax()`.
2. **Guard `raw` lookup in `pdf::get()` (`modules/pdf/pdf.php`, lines 179–180 — BUG 023)**
   - Check that `$db->select("raw", ["*"], ["id" => $value[\meta\endgame::PID]])` returns a non-empty array before reading `[0]["data"]`, defaulting `$pgn` to `""` when missing.
3. **Validate rank count in `Board::is_correct_epd()` (`modules/diagram/diagram.class.php`, lines 170–185 — BUG 024)**
   - Count `/` separators (or validate `count(explode("/", $epd)) === 8` and each rank sums to 8 squares) so malformed EPDs without 8 ranks are rejected and reset to `"8/8/8/8/8/8/8/8"`.
4. **Validate color key in `Diagram::toImage()` (`modules/diagram/diagram.class.php`, lines 324–327 — BUG 024)**
   - Check `isset($colors[$this->color]) && isset($colors[$this->color . "shift"])` (or fall back to `"black"` in `__construct()`) before allocating colors.

## Acceptance Criteria
- [x] Calling `/?q=pdf/get` without `wmax`/`bmax` parameters exports matching studies instead of returning an empty PDF.
- [x] PDF generation does not crash when an `endgame` row has no matching `raw` entry.
- [x] Passing an EPD with fewer or more than 8 ranks (e.g. `"88888888"`) renders an empty board instead of crashing.
- [x] Passing an unknown `color` parameter to `modules/diagram/` falls back to `"black"` cleanly.
