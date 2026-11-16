# TASK-024: Favorites, Viewing History, Dark Mode, and Moderator Cook Toggle

**Priority:** Low  
**Reference:** Product & UX Enhancements (`tasks/ui/recommendations.md` §6, `tasks/ui/ui_ideas.md`)

## Scope
Add high-value quality-of-life features for regular visitors and moderators: client-side favorites and recent history, a dark mode toggle, and a quick `Cooked / Sound` status toggle for authorized editors.

## Affected Files
- `theme/templates/index.template.php`
- `theme/css/main.css`
- `scripts/studydb.js`
- `api/index.php`
- `modules/endgame/endgame.php`

## Implementation Checklist
1. **Favorites & Recent History (`scripts/studydb.js`, `theme/css/main.css`)**
   - Allow toggling a `Favorite` star button on study cards and in the `Solution` modal, persisted in `localStorage`.
   - Track recently opened studies in `localStorage` and provide a quick filter/drawer to revisit them.
2. **Dark Mode (`theme/css/main.css`, `theme/templates/index.template.php`, `scripts/studydb.js`)**
   - Refactor colors into CSS custom properties (`--bg`, `--surface`, `--text`, `--border`, `--accent`).
   - Add a theme toggle in the navbar respecting `prefers-color-scheme` and persisting user preference in `localStorage`.
3. **Moderator Cook Toggle (`api/index.php` or `modules/endgame/endgame.php`, `scripts/studydb.js`)**
   - Expose a capability-guarded endpoint (`endgame::UPDATE`) to toggle `cook` (`0` / `1`) on a study directly from the `Solution` modal.

## Acceptance Criteria
- [ ] Users can bookmark studies and view their favorites/recent history across page reloads without logging in.
- [ ] Dark mode switches cleanly across cards, forms, and the `Solution` modal with readable contrast.
- [ ] Authorized editors/admins can toggle a study's `cook` status from the modal and see the updated state in search results.
