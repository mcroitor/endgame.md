# TASK-017: Interactive "Solve Yourself" Mode in Solution Modal

**Priority:** High  
**Reference:** User feedback on Solution viewer (`tasks/ui/recommendations.md` §6)

## Scope
Add an interactive "Solve Yourself" mode inside the `Solution` modal where moves are hidden until solved or revealed, allowing users to play candidate moves on the board and get immediate feedback against the PGN solution tree.

## Affected Files
- `theme/templates/index.template.php`
- `scripts/studydb.js`
- `theme/css/main.css`
- `scripts/lichess/` (or lightweight chess move validator / board wrapper)

## Implementation Checklist
1. **Mode Toggle (`index.template.php`, `scripts/studydb.js`)**
   - Add a mode switch in the `Solution` modal header: `View solution` | `Solve yourself`.
   - In `Solve yourself` mode, hide the move list and variations until the user finishes the main line or clicks `Show solution`.
2. **Interactive Move Validation (`scripts/studydb.js`)**
   - Parse the PGN main line (and key variations) into a step-by-step expected move sequence.
   - Allow making moves on the board; compare user move (SAN/UCI) against the expected solution move.
   - Provide feedback: green highlight + advance to opponent's reply on correct move; gentle shake/message (`Incorrect, try again`) on wrong move.
3. **Hint & Reveal Controls (`scripts/studydb.js`, `theme/css/main.css`)**
   - Add a `Hint` button (highlights the piece to move or shows the first move).
   - Add a `Show solution` button to reveal the full PGN move tree at any time.

## Acceptance Criteria
- [x] Switching to `Solve yourself` hides the solution moves and lets the user play moves on the board.
- [x] Correct moves automatically play the opponent's response until the study is solved.
- [x] `Hint` and `Show solution` buttons work reliably and restore the full viewer.
