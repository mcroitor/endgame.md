# TASK-023: Complete `endgame/create`, `piece_pattern` UI, and `user/edit` Route

**Priority:** Medium  
**Reference:** Remaining Functional Stubs (`modules/endgame/endgame.php`, `core/management.php`, `scripts/studydb.js`)

## Scope
Finish the remaining unimplemented handlers and UI controls that currently exist as stubs or broken links in the codebase.

## Affected Files
- `modules/endgame/endgame.php`
- `modules/endgame/templates/endgame-search-form.template.php`
- `scripts/studydb.js`
- `core/management.php`
- `core/user.php`
- `theme/templates/index.template.php`

## Implementation Checklist
1. **Endgame Creation Handler (`modules/endgame/endgame.php`)**
   - Implement `endgame::create()` to validate POST inputs (`fen`, `author`, `date`, `source`, `stipulation`, optional PGN moves).
   - Compute `whitep`, `blackp`, and `piece_pattern` from the FEN, insert into `endgame` and `raw` with matching IDs, and redirect or show validation feedback.
2. **Piece Pattern Filter UI (`endgame-search-form.template.php`, `scripts/studydb.js`)**
   - Replace the stub `patternMake()` returning `""` with a real UI control (e.g., compact piece-balance input or preset chips like `K+R vs K`, `K+P vs K`, `Q vs R`) synced with URL parameters.
3. **Admin User Edit Route (`core/management.php`, `core/user.php`)**
   - Implement the `user/edit/{id}` route (role assignment and user details update for admins) or replace the broken link in `management::manageUsers()`.

## Acceptance Criteria
- [x] Authorized users can submit `/?q=endgame/new` and persist a valid study into both `endgame` and `raw` tables.
- [x] Selecting a piece pattern in advanced search filters results via `piece_pattern` and persists in the URL.
- [x] Clicking `Edit` in `/?q=management/users` opens a working user-management view without 404/broken routing.
