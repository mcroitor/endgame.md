# TASK-025: Enable Admin Access and Enforce Permissions for `analytics` Module

**Priority:** High  
**Reference:** Module Access & Authorization (`modules/analytics/analytics.php`, `update/sql/data/common.sql`)

## Scope
Make the `analytics` module accessible to administrators from the UI (user menu / management panel), register its module/capability entries in database seed/migration scripts, and enforce capability checks on all `analytics/*` routes.

## Affected Files
- `modules/analytics/analytics.php`
- `modules/analytics/capabilities/analytics_capabilities.php`
- `theme/templates/management.template.php`
- `update/sql/data/common.sql`

## Implementation Checklist
1. **Module & Capability Registration (`update/sql/data/common.sql`, `modules/analytics/install/`)**
   - Register `analytics` in the `modules` table (or provide an `install/install.php` script for `management::installModule("analytics")`).
   - Add `analytics::create`, `analytics::read`, `analytics::update`, and `analytics::delete` to `capabilities` and grant them (`permission = 1`) to the `admin` role in `role_capabilities`.
2. **Admin Navigation Entry (`update/sql/data/common.sql`, `theme/templates/management.template.php`)**
   - Add an `Analytics` entry pointing to `/?q=analytics/links` in `user_menu` (gated by `endgame::update` or `analytics::read`).
   - Add an `Analytics` section/link on the `/?q=management` dashboard (`management.template.php`).
3. **Route Authorization & Markup Fixes (`modules/analytics/analytics.php`)**
   - Enforce capability checks (`ANALYTICS_CAPABILITY::READ` / `ANALYTICS_CAPABILITY::UPDATE` or `ENDGAME_CAPABILITIY::UPDATE`) in `analytics::fix()` (`/?q=analytics/fix`) and `analytics::links()` (`/?q=analytics/links`), matching `authors()` and `author()`.
   - Escape output in `editAuthor()` (`FEN`, `AUTHOR`) and fix the mismatched closing tags (`</div></div></ul>` → `</ul></div></div>`) in `analytics::links()`.

## Acceptance Criteria
- [x] Administrators see a direct `Analytics` link in the user menu and/or management dashboard (`/?q=management`).
- [x] `/?q=analytics/links`, `/?q=analytics/fix`, `/?q=analytics/authors`, and `/?q=analytics/author/*` are all protected from unauthenticated or unauthorized access.
- [x] All `analytics` capabilities are seeded in `capabilities` and `role_capabilities` for the `admin` role.
