# TASK-001: Fix Authentication, Authorization, and Role Schema

**Priority:** Critical  
**Related Bugs:** BUG 001, BUG 002, BUG 003, BUG 018

## Scope
Fix authentication state checks, capability permission enforcement, login input handling, and the `role_capabilities` foreign key definition.

## Affected Files
- `core/user.php`
- `update/sql/structure/common.sql`

## Implementation Checklist
1. **Fix `user::is_authenticated()` (`core/user.php`, lines 99–102 — BUG 001)**
   - Change `return user::name() === self::GUEST_NAME;` to `return user::name() !== self::GUEST_NAME;`.
2. **Enforce `permission = 1` in `user::load_capabilities()` (`core/user.php`, lines 162–174 — BUG 002)**
   - Filter `role_capabilities` by `["role_id" => $roleId, "permission" => 1]`.
   - Remove the unused `$db = new \mc\sql\database(\config::dsn);` instance.
   - Guard against missing capability rows before accessing index `[0]`.
3. **Validate login inputs and terminate on failure (`core/user.php`, lines 136–145 — BUG 003)**
   - Ensure `$login` and `$password` default to `""` (or reject `null`/empty values) before calling `user::crypt()`.
   - Replace `return;` with `exit();` after `header("location:" . config::www);` when `count($user) !== 1`.
4. **Fix foreign key reference in schema (`update/sql/structure/common.sql`, line 30 — BUG 018)**
   - Change `FOREIGN KEY (capability_id) REFERENCES capability(id)` to `FOREIGN KEY (capability_id) REFERENCES capabilities(id)`.

## Acceptance Criteria
- [x] `user::is_authenticated()` returns `false` for guests and `true` for logged-in users.
- [x] Users with `role_id = 2` do not receive capabilities marked `permission = 0` in `role_capabilities`.
- [x] Submitting missing or invalid credentials to `user/login` redirects cleanly without a `TypeError` or trailing output.
- [x] Fresh database initialization from `update/sql/structure/common.sql` succeeds with foreign key checks enabled.
