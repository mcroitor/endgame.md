# TASK-019: Advanced Solution Player Controls, Collapsible Variations, and Mobile Gestures

**Priority:** Medium  
**Reference:** User feedback on PGN Viewer / Solution modal

## Scope
Enhance the `Solution` modal player with full navigation controls (first/last move, autoplay, flip board), active-move highlighting, collapsible variations, and touch swipe support on mobile.

## Affected Files
- `theme/css/main.css`
- `scripts/studydb.js`
- `theme/templates/index.template.php`

## Implementation Checklist
1. **Player Controls (`scripts/studydb.js`, `theme/css/main.css`)**
   - Expose or add clear toolbar buttons: `First (|<)`, `Prev (<)`, `Play / Pause`, `Next (>)`, `Last (>|)`, and `Flip board`.
   - Support keyboard shortcuts `Home` (first move), `End` (last move), `Space` (play/pause), and `f` (flip board) when the modal is open.
2. **Move List & Variations (`theme/css/main.css`, `scripts/studydb.js`)**
   - Highlight the currently active move clearly in the compact ~320px move list and ensure clicking any move jumps to that position.
   - Indent variations visually and make long variations collapsible (`Show/hide variation`).
3. **Mobile Swipe Support (`scripts/studydb.js`)**
   - Detect horizontal touch swipes (`touchstart` / `touchend`) on the board/viewer area to step backward (`swipe right`) or forward (`swipe left`).

## Acceptance Criteria
- [x] Users can jump to start/end, toggle autoplay, and flip the board via buttons or keyboard.
- [x] Variations are indented and collapsible instead of breaking the main-line reading flow.
- [x] Swiping left/right on mobile steps through the solution moves.
