# TASK-004: Fix PDF Export SQL Injection, Diagram Validation, and Rendering

**Priority:** High  
**Related Bugs:** BUG 009, BUG 011, BUG 012, BUG 013

## Scope
Secure `modules/pdf/pdf.php` against SQL injection, fix PDF diagram font sizing, and fix `modules/diagram` input validation, headers, and EPD parsing.

## Affected Files
- `modules/pdf/pdf.php`
- `modules/pdf/lib/makebook.php`
- `modules/diagram/index.php`
- `modules/diagram/diagram.class.php`

## Implementation Checklist
1. **Fix SQL injection in `pdf::get()` (`modules/pdf/pdf.php`, lines 138–154 — BUG 009)**
   - Sanitize/quote all string parameters (`author`, `stipulation`, `theme`, `piece_pattern`) and cast numeric parameters (`wmin`, `wmax`, `bmin`, `bmax`, `from_date`, `to_date`) or use prepared statements.
2. **Fix `fontSize` property typo in `MakeBook::DrawDiagramPage()` (`modules/pdf/lib/makebook.php`, line 102 — BUG 013)**
   - Change `$position->fontsize` to `$position->fontSize`.
3. **Fix `fen` validation and PNG header in `modules/diagram/index.php` (lines 3–6, 21–22 — BUG 011)**
   - Check `if (empty($fen))` (or `if ($fen === null || $fen === false)`) instead of `if ($fen === false)`.
   - Uncomment `header('Content-type: image/png');`.
4. **Fix `Board::fen2board()` and `Board::is_correct_epd()` (`modules/diagram/diagram.class.php`, lines 129–131, 170–185 — BUG 012)**
   - Replace `strpos(...) === true` with `strpos(...) !== false`.
   - Change `if ($this->is_correct_epd($epd) === true)` to `if ($this->is_correct_epd($epd) === false)` so invalid EPDs fall back to `"8/8/8/8/8/8/8/8"`.

## Acceptance Criteria
- [x] `pdf/get` safely handles quotes and SQL metacharacters in query parameters.
- [x] `MakeBook::DrawDiagramPage()` applies the calculated `fontSize` to `ChessPosition`.
- [x] Requesting `modules/diagram/` without `fen` returns the usage message instead of crashing; requesting with a valid `fen` returns a `image/png` response.
- [x] Valid FENs render accurately and invalid FENs safely fall back to an empty board without PHP errors.
