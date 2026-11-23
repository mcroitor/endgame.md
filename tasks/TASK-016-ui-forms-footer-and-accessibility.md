# TASK-016: Endgame Form Preview, Footer Statistics Polish, Typos, and Accessibility

**Priority:** Low  
**Reference:** `tasks/ui/recommendations.md` (Section 5)

## Scope
Polish secondary templates, add FEN diagram preview and stipulation dropdown to the endgame creation form, clean up footer statistics styling, and fix text typos and accessibility attributes.

## Affected Files
- `modules/endgame/templates/endgame-form.template.php`
- `modules/statistics/statistics.template.php`
- `modules/articles/templates/article-form.template.php`
- `theme/templates/links.template.php`
- `theme/css/main.css`

## Implementation Checklist
1. **Endgame Creation Form (`modules/endgame/templates/endgame-form.template.php`)**
   - Replace `<input type="text" name="stipulation">` with a `<select>` matching the search form options (`White wins`, `Draw`, `*`).
   - Add a live board preview image (`/modules/diagram/?fen=...`) that updates when the `#fen` input changes.
2. **Footer Statistics (`modules/statistics/statistics.template.php`, `theme/css/main.css`)**
   - Remove list bullet markers (`list-style: none; padding: 0;`) on `.footer ul`.
   - Format numbers with thousands separators (e.g., `29 014`) and clarify labels (`from composers`, `from users`).
3. **Typos & Accessibility (`article-form.template.php`, `links.template.php`, `theme/css/main.css`)**
   - Fix `"submit"` → `"Submit"` in `article-form.template.php`.
   - Fix `"Dmidivi Turevski"` and `"Endgamestudies"` in `links.template.php`.
   - Ensure all `<label for="...">` attributes match input IDs, all icons have `alt` / `aria-label`, and interactive elements have visible `:focus-visible` outlines.

## Acceptance Criteria
- [x] Typing a valid FEN in `endgame-form.template.php` previews the chessboard diagram.
- [x] Footer statistics render without bullet points and with readable number formatting.
- [x] Keyboard navigation (`Tab`) shows clear focus rings across all forms, links, and buttons.
