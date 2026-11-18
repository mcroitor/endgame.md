# TASK-028: Study Star Rating System (1–5 Stars)

**Priority:** Medium  
**Reference:** `site/api/index.php`, `site/scripts/studydb.js`, `site/theme/css/main.css`

## Scope
Allow website visitors to rate endgame studies from 1 to 5 stars and display the average rating (and vote count) on study cards, in the `Solution` modal, and optionally as a sort option.

## Affected Files
- `site/update/sql/structure/common.sql`
- `site/api/index.php`
- `site/scripts/studydb.js`
- `site/theme/templates/searchform.template.php`
- `site/theme/css/main.css`

## Implementation Checklist
1. **Database Schema (`common.sql` + migration)**
   - Create `endgame_ratings` table:
     - `id INTEGER PRIMARY KEY AUTOINCREMENT`
     - `pid INTEGER NOT NULL`
     - `voter_hash TEXT NOT NULL` (anonymized hash of visitor IP / session or `user_id` for logged-in users, to prevent duplicate spam votes while allowing rating updates)
     - `rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5)`
     - `created_at TEXT NOT NULL`
     - `updated_at TEXT NOT NULL`
     - `UNIQUE (pid, voter_hash)`
   - Optionally add denormalized `rating_avg REAL DEFAULT 0` and `rating_count INTEGER DEFAULT 0` columns on `endgame` (or aggregate via `LEFT JOIN` / subquery in `api/index.php`) for fast sorting and display.
2. **Backend API (`api/index.php`)**
   - Update `get_data` (and single-study response) to return `rating_avg` (rounded to 1–2 decimals), `rating_count`, and the current visitor's `user_rating` (if already voted).
   - Add `POST /api/?q=rate` endpoint accepting `{ pid, rating }` (`1..5`):
     - Validate `pid` exists in `endgame` and `rating` is an integer `1..5`.
     - Upsert into `endgame_ratings` using `voter_hash` (from `user::id()` if authenticated, or salted hash of client IP + browser cookie token for guests).
     - Return updated `{ pid, rating_avg, rating_count, user_rating }`.
   - Support sorting by `rating_desc` ("Top rated") in `api/index.php` and `searchform.template.php`.
3. **Frontend UI (`scripts/studydb.js`, `theme/css/main.css`)**
   - Render a compact average rating badge (`★ 4.6 (12)`) on study cards in the search results grid.
   - Render an interactive 1–5 star rating widget in the `Solution` modal (hover preview, click to submit/update rating, immediate UI update of average and count, accessible keyboard/ARIA labels).
   - Persist/sync a lightweight visitor token cookie or `localStorage` fallback if needed alongside server-side `voter_hash`.

## Acceptance Criteria
- [x] Visitors can rate any study from 1 to 5 stars in the `Solution` modal without requiring login, and can update their rating.
- [x] Duplicate votes from the same visitor overwrite their previous rating rather than inflating the vote count.
- [x] Study cards and the `Solution` modal display the current average score and total number of votes.
- [x] Studies can be sorted by highest average rating.
