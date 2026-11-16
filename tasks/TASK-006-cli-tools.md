# TASK-006: Fix CLI Password Reset and PGN Export

**Priority:** Medium  
**Related Bugs:** BUG 010, BUG 020

## Scope
Fix fatal undefined variable error in `cli/reset-password.php` and input/formatting bugs in `cli/getpgn.php`.

## Affected Files
- `cli/reset-password.php`
- `cli/getpgn.php`

## Implementation Checklist
1. **Use `\config::$db` in `cli/reset-password.php` (line 42 — BUG 010)**
   - Replace `$db->update(...)` with `\config::$db->update(...)`.
2. **Fix `pid` validation and newline cleanup in `cli/getpgn.php` (lines 10–17 — BUG 020)**
   - Validate/cast `$pid = (int)filter_input(INPUT_GET, "pid", FILTER_VALIDATE_INT);` and return an error/404 if the row does not exist.
   - Replace `str_replace(["rn", "\\r\\n"], " ", $game)` with `str_replace(["\r\n", "\\r\\n"], " ", $game)` (or `\n`) so literal `"rn"` substrings in words are not corrupted.

## Acceptance Criteria
- [x] Running `php cli/reset-password.php -l <login> -p <password>` updates the user's password without errors.
- [x] Requesting `cli/getpgn.php` with a missing or unknown `pid` fails gracefully without undefined index errors.
- [x] Exported PGN text preserves words containing `"rn"` (e.g., `"Tournament"`, `"Bernstein"`).
