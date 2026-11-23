# TASK-012: Search Form UX, Explicit Filter Toggle, Enter Submit, and URL State Sync

**Priority:** High  
**Reference:** `tasks/ui/recommendations.md` (Section 2), `tasks/ui/ui_ex.png`

## Scope
Make search filters discoverable and persistent, support `Enter` key submission, sync search state with URL query parameters, and fix missing `cook` parameter in PDF export.

## Affected Files
- `modules/endgame/templates/endgame-search-form.template.php`
- `scripts/studydb.js`
- `theme/css/main.css`

## Implementation Checklist
1. **Form Submission & Button Hierarchy (`endgame-search-form.template.php`, `theme/css/main.css`)**
   - Wrap search inputs in `<form id="search-form" onsubmit="event.preventDefault(); getPosition(0);">` so pressing `Enter` in any field triggers search.
   - Make `Search` (`Make Query`) the primary filled button (`.button-primary`) and `Clear` (`Clear Form`) a secondary outlined/text button with proper spacing.
   - Align labels cleanly with inputs and ensure minimum `44px` touch target height on mobile.
2. **Explicit Advanced Filters Toggle (`endgame-search-form.template.php`, `scripts/studydb.js`)**
   - Remove `onfocus="showAdvanced()"` from `#author` and remove automatic hiding of `#advanced` inside `getPosition()`.
   - Add an explicit toggle button: `Advanced search ▾ (filters: N)` with `aria-expanded` and `white-space: nowrap` (so the counter never wraps onto a second line).
   - Dynamically compute and display the count of non-default active filters.
3. **URL Query Synchronization (`scripts/studydb.js`)**
   - On search (`getPosition(page)`), update the browser URL using `history.pushState` / `URLSearchParams` (e.g. `?author=Kubbel&page=0`).
   - On page load (`DOMContentLoaded`), read URL parameters, populate the form fields, and automatically run `getPosition(page)` if search parameters are present.
4. **PDF Export Sync (`scripts/studydb.js`)**
   - Pass the `cook` filter parameter in `getPdf()` so exported PDFs match the on-screen query.

## Acceptance Criteria
- [x] Pressing `Enter` in any search input submits the query.
- [x] Advanced filters toggle via an explicit button with an active-filter counter and stay open after searching.
- [x] Reloading or sharing a URL with query parameters restores the filters and displays the matching results.
- [x] `getPdf()` includes the `cook` filter state.
