# TASK-014: PGNLive Modal Backdrop, Keyboard Close, Loading & Error States

**Priority:** Medium  
**Reference:** `tasks/ui/recommendations.md` (Sections 3 & 4)

## Scope
Improve the `PGNLive!` modal UX (backdrop, `Escape` / outside-click close, larger close button) and add proper `try/catch` error, loading, and empty states to async API calls in `scripts/studydb.js`.

## Affected Files
- `theme/templates/index.template.php`
- `scripts/studydb.js`
- `theme/css/main.css`

## Implementation Checklist
1. **Modal Backdrop & Controls (`index.template.php`, `theme/css/main.css`, `scripts/studydb.js`)**
   - Wrap `#pgnlive-wrapper` in a fixed full-screen backdrop (`#pgnlive-backdrop`, `background: rgba(0, 0, 0, 0.5)`).
   - Close the modal on backdrop click, `Escape` key press, and close button click (replace `href="javascript:close()"` with a proper event listener).
   - Increase close button hit area to at least `32×32px` with `aria-label="Close PGN viewer"`.
2. **PGNLive Loading & Error Handling (`scripts/studydb.js`)**
   - Wrap `pgnLive(pid)` fetch in `try/catch/finally`.
   - Show a loading indicator inside `#pgnlive` while fetching `api/?q=pgn/<pid>`, and display a clear message if PGN data is missing or the request fails.
3. **Search Loading & Empty States (`scripts/studydb.js`)**
   - Wrap `getPosition(page)` fetch in `try/catch`.
   - On network/server error, replace `"Loading... Please, wait!"` with an actionable error message and retry hint.
   - When `result.stat === 0`, display `"No positions found. Try adjusting or clearing your filters."`.

## Acceptance Criteria
- [x] Opening `PGNLive!` dims the background and can be dismissed via `Escape`, clicking the backdrop, or clicking the enlarged close button.
- [x] Failed API requests in search or `PGNLive!` show a clear error message instead of leaving a stuck loading state.
- [x] Empty search results show a helpful empty-state message.
