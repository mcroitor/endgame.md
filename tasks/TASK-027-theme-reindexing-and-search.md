# TASK-027: Theme Re-Indexing, Manual/Automatic Tagging, and Theme Search

**Priority:** Medium  
**Reference:** `site/modules/analytics/analytics.php`, `site/api/index.php`, `site/theme/templates/searchform.template.php`

## Scope
Introduce a structured theme taxonomy (`themes` and `endgame_themes` tables or normalized `theme` mapping), support assigning one or more themes to each study (manually in study edit/create forms and automatically via a rule-based/PGN-commentary/tactical indexer), and restore theme filtering in the search form and API.

## Affected Files
- `site/update/sql/structure/common.sql`
- `site/meta/theme.php` (new, if using ORM/meta)
- `site/modules/endgame/endgame.php`
- `site/modules/analytics/analytics.php`
- `site/api/index.php`
- `site/theme/templates/searchform.template.php`
- `site/scripts/studydb.js`
- `site/theme/css/main.css`

## Implementation Checklist
1. **Database Schema (`common.sql` + migration)**
   - Create a `themes` table (`id`, `slug`, `name`, `description`) with a canonical list of endgame study themes (e.g., `Stalemate`, `Underpromotion`, `Domination`, `Zugzwang`, `Perpetual Check`, `Fortress`, `Pin`, `Skewer`, `Fork`, `Promotion`, `Opposition`, `Passed Pawn`, `Mate`, `Study-like`, etc.).
   - Create a many-to-many join table `endgame_themes (pid INTEGER, theme_id INTEGER, source TEXT DEFAULT 'manual', PRIMARY KEY (pid, theme_id))` (or keep `endgame.theme` synchronized as a comma-separated cache for backwards compatibility).
2. **Automatic Theme Indexer (`modules/analytics/analytics.php` / CLI)**
   - Implement an automatic indexer that scans PGN moves, PGN comments (`{...}`), `endgame.commentary`, and `stipulation`:
     - Keyword matching in commentary/PGN comments (e.g., `stalemate` / `пат`, `zugzwang`, `domination`, `fortress`, `perpetual`, `opposition`).
     - PGN move-pattern detection (e.g., `=[RNB]` for underpromotion, `=Q` for promotion, `#` for mate, `1/2-1/2` draw motifs).
   - Provide a dry-run preview and CSRF-protected execution UI under `/?q=analytics/themes` (similar to `analytics/fix`).
3. **Manual Theme Assignment (`modules/endgame/endgame.php`, `analytics`)**
   - Add a multi-select / checkbox list of canonical themes in `endgame/create` and `endgame/edit` forms.
   - Allow editors/admins to quickly tag/re-tag studies from `analytics/themes` (e.g., filtering untagged / `"unknown"` studies).
4. **Theme Search & Display (`searchform.template.php`, `api/index.php`, `studydb.js`)**
   - Restore the `Theme` selector (single or multi-theme) in the Advanced Search panel (`searchform.template.php`), populated dynamically from the `themes` table.
   - Update `api/index.php` (`get_data`) to filter by `endgame_themes` (with prepared statements) and include assigned themes in study card/modal badges.

## Acceptance Criteria
- [ ] Canonical `themes` table and `endgame_themes` relation exist and are populated without breaking existing studies.
- [ ] Admins can run an automatic theme re-indexing pass (with dry-run preview) and manually edit themes per study.
- [ ] Visitors can filter studies by one or more themes in the search form and see assigned theme badges on study cards / modal.
