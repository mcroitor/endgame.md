# TASK-002: Fix Core CRUD and Module Management

**Priority:** High  
**Related Bugs:** BUG 004, BUG 005

## Scope
Fix control flow in `crud::insert_or_update()` and primary key handling when uninstalling modules.

## Affected Files
- `core/database/mc/crud.php`
- `core/management.php`

## Implementation Checklist
1. **Add missing `return` statements in `crud::insert_or_update()` (`core/database/mc/crud.php`, lines 86–103 — BUG 004)**
   - Return immediately after `$this->insert($data);` when `$data[$this->_key]` is empty (returning the inserted ID).
   - Return immediately after `$this->insert($data);` when `$this->select($key)` is empty.
   - Remove the stray `echo "[debug] key found ..."` output if not intended for production.
2. **Delete module by `name` in `management::uninstallModule()` (`core/management.php`, lines 84–91 — BUG 005)**
   - Instantiate `crud` with `"name"` as the key (`new \mc\sql\crud(\config::$db, \meta\modules::__name__, \meta\modules::NAME)`), or delete directly with `[\meta\modules::NAME => $module]`.

## Acceptance Criteria
- [x] Calling `crud::insert_or_update()` with a missing key inserts once and does not trigger an undefined array key or call `update()`.
- [x] Calling `crud::insert_or_update()` with a non-existent key inserts once and does not call `update()`.
- [x] Uninstalling a module via `management/modules` removes its row from the `modules` table.
