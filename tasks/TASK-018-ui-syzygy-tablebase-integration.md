# TASK-018: Syzygy Endgame Tablebase Lookup for <= 7 Pieces

**Priority:** Medium  
**Reference:** User feedback on Solution viewer

## Scope
Integrate Lichess Syzygy tablebase lookup (`https://tablebase.lichess.ovh/standard?fen=...`) inside the `Solution` modal for positions with 7 or fewer total pieces.

## Affected Files
- `theme/templates/index.template.php`
- `scripts/studydb.js`
- `theme/css/main.css`

## Implementation Checklist
1. **Piece Count Guard (`scripts/studydb.js`)**
   - Compute total piece count from the study's FEN (or `#pieces#` field).
   - Only show the `Tablebase (Syzygy)` panel/button when total pieces `<= 7`.
2. **Tablebase API Fetch (`scripts/studydb.js`)**
   - Fetch `https://tablebase.lichess.ovh/standard?fen=${encodeURIComponent(fen)}` on demand (when clicking `Check Syzygy` or opening the tab).
   - Handle network errors, rate limits (`429`), and unavailable positions gracefully.
3. **Result Display (`scripts/studydb.js`, `theme/css/main.css`)**
   - Display exact tablebase verdict (`Win`, `Draw`, `Loss`), DTZ/DTM if available, and top tablebase moves with their outcomes.

## Acceptance Criteria
- [x] Studies with `<= 7` pieces display a `Check Syzygy` action in the `Solution` modal.
- [x] Clicking it shows the theoretical evaluation and best moves from the Syzygy tablebase.
- [x] Studies with `> 7` pieces do not trigger unnecessary tablebase requests.
