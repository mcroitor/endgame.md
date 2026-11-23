# TASK-029: Complete `management/roles` and `management/capabilities` Admin Views

**Priority:** High  
**Reference:** Broken Management Links (`site/theme/templates/management.template.php`, `site/core/management.php`)

## Scope
Implement the missing `management/roles` and `management/capabilities` routes linked from the `/?q=management` dashboard (`management.template.php`) so administrators can create/rename/delete roles, create/delete capabilities, and assign capability permissions (`role_capabilities`) through the UI. Also clean up minor URL/redirect inconsistencies in `core/management.php` and `manageoption.template.php`.

## Affected Files
- `site/core/management.php`
- `site/theme/templates/manageoption.template.php`
- `site/theme/templates/management.template.php`
- `site/meta/role.php`
- `site/meta/capabilities.php`
- `site/meta/role_capabilities.php`

## Implementation Checklist
1. **Roles Management (`/?q=management/roles`, `site/core/management.php`)**
   - Add `#[\mc\route("management/roles")]` (`manageRoles()`) restricted to authenticated `admin` users.
   - Render a table of existing roles from `role` with assigned user counts and capability counts, plus a form to create a new role (`POST` with CSRF protection) and edit/delete actions (preventing deletion of built-in `admin` / `guest` roles or roles still referenced by `user`).
   - Add `#[\mc\route("management/role")]` (or `role/edit/{id}`, `role/update/{id}`, `role/create`, `role/delete/{id}`) to edit a role's name and toggle its permissions across all rows in `capabilities` via `role_capabilities` (`permission = 1` / `0`) using checkboxes and CSRF validation (`user::require_csrf()`).
2. **Capabilities Management (`/?q=management/capabilities`, `site/core/management.php`)**
   - Add `#[\mc\route("management/capabilities")]` (`manageCapabilities()`) restricted to authenticated `admin` users.
   - List all capabilities from `capabilities` grouped by prefix (e.g., `endgame::*`, `article::*`, `analytics::*`, `user::*`), showing which roles currently have each capability enabled.
   - Add a CSRF-protected `POST` form to register a new capability (`name` validation) and a safe `POST` delete action (removing corresponding rows in `role_capabilities` and `user_menu` or blocking deletion if referenced).
3. **Management Routing & Template Fixes (`site/core/management.php`, `site/theme/templates/manageoption.template.php`)**
   - Fix redirect URLs in `management::installModule()`, `install_module()`, and `uninstall_module()` from `config::www . "?q=management/modules"` to `config::www . "/?q=management/modules"`.
   - Update the `Back` button in `site/theme/templates/manageoption.template.php` to `<!-- www -->/?q=management` so subdirectory deployments resolve properly.
   - Escape all output in `manageUsers()`, `manageModules()`, `manageRoles()`, and `manageCapabilities()` with `htmlspecialchars(..., ENT_QUOTES, "UTF-8")`.

## Acceptance Criteria
- [x] Clicking `Manage Roles` on `/?q=management` opens a working `/?q=management/roles` page where admins can create/rename roles and toggle capability checkboxes stored in `role_capabilities`.
- [x] Clicking `Manage Capabilities` on `/?q=management` opens a working `/?q=management/capabilities` page where admins can view, add, and remove capabilities.
- [x] All state-changing actions in Management require `POST`, `admin` role authorization, and a valid CSRF token.
- [x] Non-admin and unauthenticated users are redirected away from `management/roles` and `management/capabilities`.
