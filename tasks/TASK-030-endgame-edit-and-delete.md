# TASK-030: Full Endgame Study Editing and Deletion (`endgame/edit`, `endgame/update`, `endgame/delete`)

**Priority:** High  
**Reference:** Missing Study Edit Workflow (`site/modules/endgame/endgame.php`, `site/modules/endgame/capabilities/endgame_capabilities.php`, `site/scripts/studydb.js`)

## Scope
Allow authorized editors and administrators (users with `endgame::update` / `endgame::delete` capabilities) to edit any existing endgame study when errors are found—including its FEN position, PGN solution/raw record, author, date, source, award, stipulation, theme, commentary, and `cook` status—as well as delete erroneous studies safely.

## Affected Files
- `site/modules/endgame/endgame.php`
- `site/modules/endgame/templates/endgame-edit-form.template.php` (or shared `endgame-form.template.php`)
- `site/scripts/studydb.js`
- `site/api/index.php`
- `site/theme/templates/index.template.php`
- `site/theme/css/main.css`

## Implementation Checklist
1. **Backend Edit & Update Routes (`site/modules/endgame/endgame.php`)**
   - Add `#[\mc\route("endgame/edit")]` (`editForm(array $params)`):
     - Enforce `user::has_capability(ENDGAME_CAPABILITIY::UPDATE)` (or `admin` role).
     - Validate `$pid = (int) ($params[0] ?? 0)` and load the study from `endgame` plus its PGN text from `raw`. Return a clear error message if not found.
     - Render a pre-filled form (`POST` to `<!-- www -->/?q=endgame/update/{pid}` with `<!-- csrf-input -->`) covering:
       - `fen` (with live diagram preview),
       - `author`, `date`, `source`, `award`,
       - `stipulation` (`White wins`, `Draw`, `Black wins`, `*`),
       - `theme`, `cook` checkbox (`0`/`1`), `commentary`,
       - full `raw` PGN text (or solution moves) synced with `raw.data`.
   - Add `#[\mc\route("endgame/update")]` (`update(array $params)`):
     - Require `POST` and `user::require_csrf()`, plus `ENDGAME_CAPABILITIY::UPDATE`.
     - Validate non-empty `fen` and `author`, recompute `whitep`, `blackp`, and `piece_pattern` from the updated FEN via `analyzeFen()`, and update `endgame` using prepared statements / `exec_prepared`.
     - Update (or insert if missing) the matching `raw` row (`id = $pid`) with the edited PGN text (updating `[FEN "..."]`, `[White "..."]`, `[Event "..."]`, `[Date "..."]`, `[Result "..."]` headers if user edits metadata-only, or saving edited raw PGN directly).
     - Redirect to `/?pid={$pid}` after saving.
2. **Backend Safe Deletion (`site/modules/endgame/endgame.php`)**
   - Add `#[\mc\route("endgame/delete")]` (`delete(array $params)`):
     - Require `POST`, `user::require_csrf()`, and `user::has_capability(ENDGAME_CAPABILITIY::DELETE)` (or `admin` role).
     - Delete matching rows from `endgame`, `raw`, and `endgame_ratings` (if present) by `pid`, then redirect to `config::www`.
3. **Frontend Edit Entry Points (`site/api/index.php`, `site/scripts/studydb.js`, `site/theme/templates/index.template.php`)**
   - Expose lightweight permission flags for the current session (e.g. `can_edit`, `can_delete` in `api/?q=data` or in a `data-*` / JS config attribute on `index.template.php`).
   - Show an `Edit study` link/button (`/?q=endgame/edit/{pid}`) inside the `Solution` modal (and optionally on study cards or `analytics` duplicate/integrity tables) when `can_edit` is true.
   - Add a `Delete` button with confirmation prompt inside `endgame/edit/{pid}` when `can_delete` is true.

## Acceptance Criteria
- [x] Authorized users (`endgame::update`) see an `Edit` button in the `Solution` modal and can open `/?q=endgame/edit/{pid}` for any study.
- [x] Saving edits on `/?q=endgame/edit/{pid}` updates both `endgame` (including recalculated `whitep`, `blackp`, `piece_pattern` when FEN changes) and `raw`, and redirects to the updated study.
- [x] Authorized users (`endgame::delete`) can delete a study via a CSRF-protected `POST` action with confirmation.
- [x] Unauthorized users cannot access `endgame/edit`, `endgame/update`, or `endgame/delete` and do not see edit/delete controls in the UI.
