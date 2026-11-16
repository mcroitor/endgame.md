# TASK-020: Prepared Statements, Password Hashing, and CSRF Protection

**Priority:** Critical  
**Reference:** Security Audit & Architecture Hardening

## Scope
Replace string-escaped SQL interpolation in search and PDF endpoints with PDO prepared statements, upgrade legacy `crypt()` password hashing to `password_hash()` / `password_verify()`, and add CSRF token protection to state-changing POST forms.

## Affected Files
- `core/database/mc/database.php`
- `core/database/mc/query.php`
- `api/index.php`
- `modules/pdf/pdf.php`
- `core/user.php`
- `core/management.php`
- `theme/templates/loginform.template.php`
- `modules/articles/templates/article-form.template.php`
- `modules/endgame/templates/endgame-form.template.php`
- `modules/endgame/templates/endgame-import-form.template.php`

## Implementation Checklist
1. **PDO Prepared Statements (`core/database/mc/database.php`, `api/index.php`, `modules/pdf/pdf.php`)**
   - Add parameterized query execution support (`execPrepared(string $sql, array $params)`) in `\mc\sql\database`.
   - Refactor `get_data()` in `api/index.php` and `pdf::get()` in `modules/pdf/pdf.php` to bind all user-supplied filters (`author`, `wmin`, `wmax`, `bmin`, `bmax`, `from_date`, `to_date`, `stipulation`, `theme`, `piece_pattern`) as bound parameters instead of `str_replace("'", "''", ...)`.
2. **Modern Password Hashing (`core/user.php`, `cli/create-user.php`, `cli/reset-password.php`)**
   - Replace `crypt($password . $login, $login)` with `password_hash($password, PASSWORD_DEFAULT)`.
   - Support seamless verification in `user::login()`: check `password_verify()`, and if a legacy `crypt()` hash matches, transparently rehash and update the database record.
3. **CSRF Protection (`core/user.php`, `core/lib.php`, templates)**
   - Generate and store a per-session CSRF token (`$_SESSION['csrf_token']`) and provide a helper to render a hidden input and validate submitted tokens.
   - Enforce CSRF validation on login, article creation/editing, endgame creation/import, and admin module/user management actions.

## Acceptance Criteria
- [x] `api/?q=data` and `/?q=pdf/get` execute all filter queries via PDO prepared statements with zero raw string interpolation of user input.
- [x] New and reset passwords are stored using `password_hash()`, while existing users can still log in and are migrated on login.
- [x] All state-changing POST requests reject missing or invalid CSRF tokens with HTTP `403`.
