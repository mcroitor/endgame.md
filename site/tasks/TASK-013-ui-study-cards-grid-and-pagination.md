# TASK-013: CSS Grid Study Cards, Card Hierarchy, Copy FEN, Direct PGN, and Full Pagination

**Priority:** High  
**Reference:** `tasks/ui/recommendations.md` (Section 3), `tasks/ui/ui_ex.png`

## Scope
Replace the `float: left` fixed-height card layout with a responsive CSS Grid, redesign card information hierarchy per `ui_ex.png`, improve FEN/PGN actions, and implement full top-and-bottom pagination.

## Affected Files
- `scripts/studydb.js`
- `theme/css/main.css`
- `modules/endgame/templates/endgame-search-form.template.php`

## Implementation Checklist
1. **Responsive Grid Layout (`theme/css/main.css`)**
   - Style `#diag` with `display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;`.
   - Remove `float: left`, `width: 300px`, and fixed `height: 500px` from `.dd` (`.study-card`); use `display: flex; flex-direction: column;` with card border/shadow and padding.
2. **Card Hierarchy & Alignment (`scripts/studydb.js`, `theme/css/main.css`)**
   - Remove `<center>` and editable `<input style='width:300px;'>` from `DIAGRAM_TPL`.
   - Order card sections top-to-bottom:
     1. Header: Author (bold) + Source and Year below, clamped to 2 lines (`min-height` + `-webkit-line-clamp: 2`) so boards align across rows.
     2. Chessboard image (`./modules/diagram/?fen=#fen#&size=32`) with descriptive `alt` text (`FEN: #fen#`).
     3. Badges row (stipulation / side-to-move).
     4. Footer actions: `PGNLive` button, direct `<a href="?q=pgn/#pid#" class="button" download>PGN</a>` link, `Copy FEN` button (`navigator.clipboard.writeText`), and muted `PID #pid#` on the right.
3. **DOM Performance & Results Header (`scripts/studydb.js`)**
   - Build card HTML in a string buffer and assign `get("diag").innerHTML = html` once per page instead of `+=` inside the loop.
   - Fix typo `"no more then 1000 diagrams per PDFfile"` → `"no more than 1000 diagrams per PDF file"` and style `Download PDF` as a single-line button (`white-space: nowrap`).
4. **Full Pagination (`scripts/studydb.js`, `endgame-search-form.template.php`)**
   - Render numbered pagination (`Prev`, `1`, `2`, `3`, `...`, `Last`, `Next`) instead of `left.gif` / `right.gif`.
   - Render pagination both above and below `#diag` (or below `#diag` with total count in `#stat`).

## Acceptance Criteria
- [ ] Cards display in a responsive 1/2/3-column grid with aligned chessboards even when source titles wrap to 2 lines.
- [ ] Clicking `Copy FEN` copies the FEN string to clipboard with visual feedback; clicking `PGN` downloads the file without opening a blank tab.
- [ ] Pagination shows current page / total pages and is accessible at the bottom of the results list.
