# TASK-015: Backend Sorting, Side-to-Move & Stipulation Badges, and 3-State Cooked Filter

**Priority:** Medium  
**Reference:** `tasks/ui/recommendations.md` (Sections 2 & 3), `tasks/ui/ui_ideas.md`, `tasks/ui/ui_ex.png`

## Scope
Add result sorting to the search API and UI, replace the single `cook` checkbox with a 3-state selector, and enrich study cards with side-to-move and human-readable stipulation badges.

## Affected Files
- `api/index.php`
- `modules/endgame/templates/endgame-search-form.template.php`
- `scripts/studydb.js`
- `theme/css/main.css`

## Implementation Checklist
1. **Sorting (`api/index.php`, `endgame-search-form.template.php`, `scripts/studydb.js`)**
   - Support `sort` parameter in `api/?q=data` with an allowlist of `ORDER BY` options (e.g., `date_asc`, `date_desc`, `author_asc`, `pieces_asc`, `pid_asc`).
   - Add a `Sort by` `<select>` in the results header sized wide enough so option text is never truncated.
2. **3-State Cooked Filter (`api/index.php`, `endgame-search-form.template.php`, `scripts/studydb.js`)**
   - Replace the `cook` checkbox with a 3-state segmented control / radio group: `All` / `Sound only (uncooked)` / `Cooked only`.
   - Update `api/index.php` to filter `cook = 0`, `cook = 1`, or no filter accordingly.
3. **Card Badges (`scripts/studydb.js`, `theme/css/main.css`)**
   - Parse side-to-move (`w` / `b`) from `#fen#` and render a `White to move` / `Black to move` pill badge.
   - Parse/format stipulation codes (e.g., `tt22#016 {m}` → `Mate in 16` with raw code in `title` attribute) alongside `White wins` / `Draw`.

## Acceptance Criteria
- [ ] Users can sort search results by year, author, or piece count without SQL errors.
- [ ] The 3-state `cooked` selector clearly highlights the active choice and filters results accurately.
- [ ] Study cards display side-to-move and readable stipulation badges.
