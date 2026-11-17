# TASK-026: Extend `analytics` Module with Data Integrity, Duplicate Detection, Author Merging, and Search Insights

**Priority:** Medium  
**Reference:** `modules/analytics/analytics.php`, `modules/statistics/statistics.php`

## Scope
Expand the `analytics` module beyond basic stipulation/pattern/author fixes into a full data-quality and search-insights toolkit for administrators, while hardening destructive actions.

## Affected Files
- `site/modules/analytics/analytics.php`
- `site/theme/css/main.css`

## Implementation Checklist
1. **Duplicate Position & Integrity Checks (`analytics/duplicates`, `analytics/integrity`)**
   - Detect duplicate positions sharing the same piece placement (first 4 FEN fields) and render them side-by-side with diagrams and a safe delete action.
   - Detect `endgame` rows missing a matching `raw` record, orphaned `raw` rows without an `endgame` row, invalid FENs, and `whitep`/`blackp` mismatches against the FEN (with one-click recalculation).
2. **Author Normalization & Context (`analytics/authors`, `analytics/merge-authors`)**
   - Show `source`, `date`, and a link to PGN/diagram in `analytics/authors` and `editAuthor()` so editors have context before editing or removing a study.
   - Add a bulk `Merge Authors` tool to consolidate spelling variants (e.g., `Kubbel, L.` -> `Kubbel, Leonid`).
3. **Search & Database Insights (`analytics/insights`)**
   - Summarize `statistic` logs: daily/weekly query volume, unique IPs, top searched authors/themes, and frequent queries returning zero results.
   - Display database composition breakdowns by decade, stipulation, and `cooked` ratio.
4. **Safe Deletion (`analytics/author/remove`)**
   - Replace the `GET` delete link in `analytics/authors` with a `POST` form protected by a confirmation prompt and CSRF token (`user::require_csrf()`).

## Acceptance Criteria
- [ ] Admins can view duplicate positions and `endgame`/`raw` integrity mismatches from `/?q=analytics/links`.
- [ ] Admins can merge duplicate author names in bulk and see study context when editing broken authors.
- [ ] `/?q=analytics/insights` displays query trends and database breakdowns from `statistic` and `endgame`.
- [ ] Deleting a study via `analytics` requires a `POST` request with a valid CSRF token.
