# TASK-007: Fix Core Database Null Handling and User Management UI

**Priority:** High  
**Related Bugs:** BUG 021, BUG 031

## Scope
Fix template placeholder rendering, role lookup, and edit link in `management::manageUsers()`, and handle `null` values and `UPDATE` value formatting in the core database layer.

## Affected Files
- `core/management.php`
- `core/database/mc/database.php`
- `core/database/mc/query.php`

## Implementation Checklist
1. **Fix `management::manageUsers()` (`core/management.php`, lines 110–126 — BUG 021)**
   - Pass `["prefix" => "<!-- ", "suffix" => " -->"]` when instantiating `\mc\template` for `manageoption.template.php`.
   - Guard against empty role query results before indexing `[0][\meta\role::NAME]` (fallback to `"unknown"`).
   - Remove or implement the broken `/?q=user/edit/{id}` link (or prefix with `<!-- www -->` once the route exists).
2. **Handle `null` in `database::update()` (`core/database/mc/database.php`, lines 190–199 — BUG 031)**
   - Check `is_null($value)` before calling `$this->pdo->quote($value)`, emitting `{$key}=null` when `$value === null` (matching `database::insert()`).
3. **Fix `query::build_values()` (`core/database/mc/query.php`, lines 218–224 — BUG 031)**
   - Quote string values (and emit `null` for `null` values) for `INSERT` queries, and format `key = value` pairs when `$this->type === self::UPDATE`.

## Acceptance Criteria
- [x] `management/users` renders the page title, description, and user table without raw `<!-- ... -->` placeholders.
- [x] `management/users` does not throw an error if a user references a missing role ID.
- [x] `database::update()` updates columns to SQL `NULL` without PHP `TypeError` or deprecation warnings.
- [x] `query::build()` produces valid quoted SQL for `INSERT` and `UPDATE` queries.
