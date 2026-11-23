<?php

use mc\user;

/**
 * site management class
 * Access to the settings and configurations of the site, install / uninstall modules, etc.
 */
class management
{
    private const BUILTIN_ROLES = ["admin", "guest"];

    private static function isAdmin(): bool
    {
        return user::is_authenticated() && user::role() === "admin";
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }

    private static function baseUrl(): string
    {
        return rtrim((string) \config::www, "/");
    }

    private static function renderOptionPage(string $title, string $description, string $html): string
    {
        $template = new \mc\template(
            file_get_contents(\config::template_dir . "manageoption.template.php"),
            [
                "prefix" => "<!-- ",
                "suffix" => " -->"
            ]
        );
        return $template->fill([
            "title" => $title,
            "description" => $description,
            "html" => $html,
        ])->value();
    }

    private static function statusBanner(array $messages): string
    {
        $msg = (string) ($_GET["msg"] ?? "");
        if ($msg !== "" && isset($messages["msg"][$msg])) {
            return '<div class="status-message" style="margin-bottom:1.5rem;border-color:#86efac;background:#f0fdf4;color:#166534;">'
                . self::esc($messages["msg"][$msg]) . '</div>';
        }
        $err = (string) ($_GET["error"] ?? "");
        if ($err !== "" && isset($messages["error"][$err])) {
            return '<div class="status-message status-error" style="margin-bottom:1.5rem;">'
                . self::esc($messages["error"][$err]) . '</div>';
        }
        return "";
    }

    private static function installedModules()
    {
        $crud = new \mc\sql\crud(\config::$db, \meta\modules::__name__);
        $installed = $crud->all(0, $crud->count());
        \config::$logger->debug("Installed modules: " . json_encode($installed), \config::debug);
        return $installed;
    }

    private static function availableModules()
    {
        $dirs = scandir(\config::modules_dir);
        $modules = [];
        foreach ($dirs as $dir) {
            if ($dir === "." || $dir === "..") {
                continue;
            }
            if (
                is_dir(\config::modules_dir . $dir) &&
                (
                    file_exists(\config::modules_dir . "{$dir}/{$dir}.php") ||
                    file_exists(\config::modules_dir . "{$dir}/install")
                )
            ) {
                $modules[] = $dir;
            }
        }
        \config::$logger->debug("Available modules: " . json_encode($modules), \config::debug);
        return $modules;
    }

    private static function brokenModules()
    {
        $installed = self::installedModules();
        $available = self::availableModules();
        $broken = [];
        foreach ($installed as $module) {
            if (!in_array($module[\meta\modules::NAME], $available)) {
                $broken[] = $module;
            }
        }
        return $broken;
    }

    private static function newModules()
    {
        $installed = array_column(self::installedModules(), \meta\modules::NAME);
        $available = self::availableModules();
        $new = [];
        foreach ($available as $module) {
            if (!in_array($module, $installed)) {
                $new[] = $module;
            }
        }
        \config::$logger->debug("New modules: " . json_encode($new), \config::debug);
        return $new;
    }

    public static function installModule($module)
    {
        $crud = new \mc\sql\crud(\config::$db, \meta\modules::__name__);
        \config::$logger->debug("Installing module: " . $module, \config::debug);
        if (file_exists(\config::modules_dir . "{$module}/{$module}.php")) {
            $crud->insert([
                \meta\modules::NAME => $module,
                \meta\modules::ENTRY_POINT => $module
            ]);
        } elseif (file_exists(\config::modules_dir . "{$module}/install")) {
            include \config::modules_dir . "{$module}/install/install.php";
        }
        header("location:" . self::baseUrl() . "/?q=management/modules");
    }

    public static function uninstallModule($module)
    {
        $crud = new \mc\sql\crud(\config::$db, \meta\modules::__name__, \meta\modules::NAME);
        $crud->delete($module);
        if (file_exists(\config::modules_dir . "{$module}/install")) {
            include \config::modules_dir . "{$module}/install/uninstall.php";
        }
    }

    #[\mc\route("management")]
    public static function managementView()
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }
        return file_get_contents(\config::template_dir . "management.template.php");
    }

    #[\mc\route("management/users")]
    public static function manageUsers()
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }
        $html = "<table class='u-full-width'>";
        $html .= "<tr><th>Username</th><th>Role</th><th>Action</th></tr>";

        $users = \config::$db->select(\meta\user::__name__, ["*"]);
        foreach ($users as $user) {
            $roles = \config::$db->select(
                \meta\role::__name__,
                [\meta\role::NAME],
                ["id" => $user[\meta\user::ROLE_ID]]
            );
            $role = self::esc((string) ($roles[0][\meta\role::NAME] ?? "unknown"));
            $loginEsc = self::esc((string) ($user[\meta\user::LOGIN] ?? ""));
            $uid = (int) $user[\meta\user::ID];
            $html .= "<tr><td>{$loginEsc}</td>" .
                "<td>{$role}</td>" .
                "<td><a href='<!-- www -->/?q=user/edit/{$uid}'>Edit</a></td></tr>";
        }
        $html .= "</table>";
        return self::renderOptionPage("Manage Users", "Here you can manage your users.", $html);
    }

    #[\mc\route("management/modules")]
    public static function manageModules()
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }
        $new = self::newModules();
        $installed = self::installedModules();
        $html = "";
        if (!empty($new)) {
            $html .= "<h2>New Modules</h2>";
            $html .= "<table class='u-full-width'>";
            $html .= "<tr><th>Module name</th><th>Action</th></tr>";
            foreach ($new as $module) {
                $modEsc = self::esc((string) $module);
                $modUrl = urlencode((string) $module);
                $html .= "<tr><td>{$modEsc}</td><td><a href='<!-- www -->/?q=module/install/{$modUrl}'>Install</a></td></tr>";
            }
            $html .= "</table>";
        }
        $html .= "<h2>Installed Modules</h2>";
        $html .= "<table class='u-full-width'>";
        $html .= "<tr><th>Module name</th><th>Action</th></tr>";
        foreach ($installed as $module) {
            $modName = (string) ($module[\meta\modules::NAME] ?? "");
            $modEsc = self::esc($modName);
            $modUrl = urlencode($modName);
            $html .= "<tr><td>{$modEsc}</td><td><a href='<!-- www -->/?q=module/uninstall/{$modUrl}'>Uninstall</a></td></tr>";
        }
        $html .= "</table>";
        return self::renderOptionPage("Manage Modules", "Here you can manage your modules.", $html);
    }

    #[\mc\route("management/roles")]
    public static function manageRoles()
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }

        $db = \config::$db;
        $roles = $db->exec_prepared(
            "SELECT r.id, r.name,
                    (SELECT COUNT(*) FROM user u WHERE u.role_id = r.id) AS user_count,
                    (SELECT COUNT(*) FROM role_capabilities rc WHERE rc.role_id = r.id AND rc.permission = 1) AS cap_count
             FROM role r
             ORDER BY r.id ASC"
        );

        $html = self::statusBanner([
            "msg" => [
                "role_created" => "Role created.",
                "role_updated" => "Role and permissions updated.",
                "role_deleted" => "Role deleted.",
            ],
            "error" => [
                "invalid_name" => "Role name must be 2–50 characters (letters, numbers, _ or -).",
                "role_exists" => "A role with that name already exists.",
                "cannot_delete_builtin" => "Built-in roles (admin, guest) cannot be deleted.",
                "role_in_use" => "Cannot delete a role that is still assigned to users.",
                "not_found" => "Role not found.",
            ],
        ]);

        $html .= "<table class='u-full-width'>";
        $html .= "<tr><th>ID</th><th>Role</th><th>Users</th><th>Enabled Capabilities</th><th>Actions</th></tr>";
        foreach ($roles as $role) {
            $roleId = (int) $role["id"];
            $roleName = (string) $role["name"];
            $roleEsc = self::esc($roleName);
            $userCount = (int) $role["user_count"];
            $capCount = (int) $role["cap_count"];
            $isBuiltin = in_array($roleName, self::BUILTIN_ROLES, true);

            $actions = "<a href='<!-- www -->/?q=role/edit/{$roleId}' class='button'>Edit permissions</a>";
            if (!$isBuiltin && $userCount === 0) {
                $actions .= " <form method='post' action='<!-- www -->/?q=role/delete/{$roleId}' style='display:inline;' onsubmit=\"return confirm('Delete role {$roleEsc}?');\">"
                    . "<!-- csrf-input -->"
                    . "<button type='submit' class='button'>Delete</button>"
                    . "</form>";
            }

            $html .= "<tr>"
                . "<td>{$roleId}</td>"
                . "<td><strong>{$roleEsc}</strong></td>"
                . "<td>{$userCount}</td>"
                . "<td>{$capCount}</td>"
                . "<td>{$actions}</td>"
                . "</tr>";
        }
        $html .= "</table>";

        $html .= "<h4>Create New Role</h4>";
        $html .= "<form method='post' action='<!-- www -->/?q=role/create'>"
            . "<!-- csrf-input -->"
            . "<div class='row'>"
            . "<div class='six columns'>"
            . "<label for='new-role-name'>Role name</label>"
            . "<input type='text' id='new-role-name' name='name' class='u-full-width' placeholder='e.g. editor' required />"
            . "</div>"
            . "<div class='six columns' style='padding-top:2.4rem;'>"
            . "<input type='submit' value='Create Role' class='button-primary' />"
            . "</div>"
            . "</div>"
            . "</form>";

        return self::renderOptionPage(
            "Manage Roles",
            "Create roles and configure capability permissions for each role.",
            $html
        );
    }

    #[\mc\route("role/create")]
    public static function createRole()
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            exit();
        }

        $name = trim((string) ($_POST["name"] ?? ""));
        $baseUrl = self::baseUrl();
        if (!preg_match('/^[a-zA-Z0-9_-]{2,50}$/', $name)) {
            header("location:" . $baseUrl . "/?q=management/roles&error=invalid_name");
            exit();
        }

        $db = \config::$db;
        $existing = $db->exec_prepared("SELECT id FROM role WHERE name = ?", [$name]);
        if (!empty($existing)) {
            header("location:" . $baseUrl . "/?q=management/roles&error=role_exists");
            exit();
        }

        $db->exec_prepared("INSERT INTO role (name) VALUES (?)", [$name], false);
        $created = $db->exec_prepared("SELECT id FROM role WHERE name = ?", [$name]);
        $newId = (int) ($created[0]["id"] ?? 0);
        if ($newId > 0) {
            header("location:" . $baseUrl . "/?q=role/edit/{$newId}&msg=role_created");
        } else {
            header("location:" . $baseUrl . "/?q=management/roles&msg=role_created");
        }
        exit();
    }

    #[\mc\route("role/edit")]
    public static function editRole(array $params)
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }

        $roleId = isset($params[0]) ? (int) $params[0] : 0;
        if ($roleId <= 0) {
            return self::renderOptionPage("Edit Role", "", "<div class='status-message status-error'>Invalid role ID.</div><p><a href='<!-- www -->/?q=management/roles' class='button'>Back to Roles</a></p>");
        }

        $db = \config::$db;
        $roles = $db->exec_prepared("SELECT id, name FROM role WHERE id = ?", [$roleId]);
        if (empty($roles)) {
            return self::renderOptionPage("Edit Role", "", "<div class='status-message status-error'>Role not found.</div><p><a href='<!-- www -->/?q=management/roles' class='button'>Back to Roles</a></p>");
        }
        $role = $roles[0];
        $roleName = (string) $role["name"];
        $roleEsc = self::esc($roleName);
        $isBuiltin = in_array($roleName, self::BUILTIN_ROLES, true);

        $capabilities = $db->exec_prepared("SELECT id, name FROM capabilities ORDER BY name ASC");
        $assignedRows = $db->exec_prepared(
            "SELECT capability_id, permission FROM role_capabilities WHERE role_id = ?",
            [$roleId]
        );
        $assignedMap = [];
        foreach ($assignedRows as $row) {
            $assignedMap[(int) $row["capability_id"]] = (int) $row["permission"];
        }

        $grouped = [];
        foreach ($capabilities as $cap) {
            $capName = (string) $cap["name"];
            $parts = explode("::", $capName, 2);
            $group = count($parts) === 2 ? $parts[0] : "general";
            $grouped[$group][] = $cap;
        }

        $html = self::statusBanner([
            "msg" => [
                "role_created" => "Role created. Select capabilities below.",
            ],
            "error" => [
                "invalid_name" => "Role name must be 2–50 characters (letters, numbers, _ or -).",
                "role_exists" => "Another role with that name already exists.",
            ],
        ]);

        $html .= "<form method='post' action='<!-- www -->/?q=role/update/{$roleId}'>";
        $html .= "<!-- csrf-input -->";
        $html .= "<div class='row'><div class='six columns'>";
        $html .= "<label for='edit-role-name'>Role Name</label>";
        if ($isBuiltin) {
            $html .= "<input type='text' id='edit-role-name' name='name' value='{$roleEsc}' class='u-full-width' readonly />";
        } else {
            $html .= "<input type='text' id='edit-role-name' name='name' value='{$roleEsc}' class='u-full-width' required />";
        }
        $html .= "</div></div>";

        $html .= "<h4>Capabilities</h4>";
        if (empty($grouped)) {
            $html .= "<p>No capabilities registered yet.</p>";
        } else {
            foreach ($grouped as $group => $caps) {
                $groupEsc = self::esc($group);
                $html .= "<fieldset style='border:1px solid #e5e7eb;padding:1rem 1.4rem;margin-bottom:1.2rem;border-radius:4px;'>";
                $html .= "<legend style='font-weight:600;padding:0 0.5rem;'>{$groupEsc}</legend>";
                foreach ($caps as $cap) {
                    $capId = (int) $cap["id"];
                    $capEsc = self::esc((string) $cap["name"]);
                    $checked = ($assignedMap[$capId] ?? 0) === 1 ? " checked" : "";
                    $html .= "<label style='display:inline-flex;align-items:center;gap:0.5rem;margin-right:1.8rem;font-weight:normal;'>"
                        . "<input type='checkbox' name='caps[]' value='{$capId}'{$checked} />"
                        . "<span>{$capEsc}</span>"
                        . "</label>";
                }
                $html .= "</fieldset>";
            }
        }

        $html .= "<div style='display:flex;gap:1rem;margin-top:1.2rem;'>"
            . "<input type='submit' value='Save Role' class='button-primary' />"
            . "<a href='<!-- www -->/?q=management/roles' class='button'>Cancel</a>"
            . "</div>";
        $html .= "</form>";

        return self::renderOptionPage(
            "Edit Role: {$roleEsc}",
            "Update role name and toggle capability permissions.",
            $html
        );
    }

    #[\mc\route("role/update")]
    public static function updateRole(array $params)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            exit();
        }

        $baseUrl = self::baseUrl();
        $roleId = isset($params[0]) ? (int) $params[0] : 0;
        if ($roleId <= 0) {
            header("location:" . $baseUrl . "/?q=management/roles&error=not_found");
            exit();
        }

        $db = \config::$db;
        $roles = $db->exec_prepared("SELECT id, name FROM role WHERE id = ?", [$roleId]);
        if (empty($roles)) {
            header("location:" . $baseUrl . "/?q=management/roles&error=not_found");
            exit();
        }
        $oldName = (string) $roles[0]["name"];
        $isBuiltin = in_array($oldName, self::BUILTIN_ROLES, true);

        if (!$isBuiltin) {
            $newName = trim((string) ($_POST["name"] ?? ""));
            if (!preg_match('/^[a-zA-Z0-9_-]{2,50}$/', $newName)) {
                header("location:" . $baseUrl . "/?q=role/edit/{$roleId}&error=invalid_name");
                exit();
            }
            $conflict = $db->exec_prepared("SELECT id FROM role WHERE name = ? AND id != ?", [$newName, $roleId]);
            if (!empty($conflict)) {
                header("location:" . $baseUrl . "/?q=role/edit/{$roleId}&error=role_exists");
                exit();
            }
            $db->exec_prepared("UPDATE role SET name = ? WHERE id = ?", [$newName, $roleId], false);
        }

        $submittedCaps = $_POST["caps"] ?? [];
        if (!is_array($submittedCaps)) {
            $submittedCaps = [];
        }
        $enabledCapIds = [];
        foreach ($submittedCaps as $cid) {
            $idInt = (int) $cid;
            if ($idInt > 0) {
                $enabledCapIds[$idInt] = true;
            }
        }

        $allCaps = $db->exec_prepared("SELECT id FROM capabilities");
        $db->begin_transaction();
        try {
            $db->exec_prepared("DELETE FROM role_capabilities WHERE role_id = ?", [$roleId], false);
            foreach ($allCaps as $cap) {
                $capId = (int) $cap["id"];
                $perm = isset($enabledCapIds[$capId]) ? 1 : 0;
                $db->exec_prepared(
                    "INSERT INTO role_capabilities (role_id, capability_id, permission) VALUES (?, ?, ?)",
                    [$roleId, $capId, $perm],
                    false
                );
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->roll_back();
            throw $e;
        }

        header("location:" . $baseUrl . "/?q=management/roles&msg=role_updated");
        exit();
    }

    #[\mc\route("role/delete")]
    public static function deleteRole(array $params)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            exit();
        }

        $baseUrl = self::baseUrl();
        $roleId = isset($params[0]) ? (int) $params[0] : 0;
        if ($roleId <= 0) {
            header("location:" . $baseUrl . "/?q=management/roles&error=not_found");
            exit();
        }

        $db = \config::$db;
        $roles = $db->exec_prepared("SELECT id, name FROM role WHERE id = ?", [$roleId]);
        if (empty($roles)) {
            header("location:" . $baseUrl . "/?q=management/roles&error=not_found");
            exit();
        }
        if (in_array((string) $roles[0]["name"], self::BUILTIN_ROLES, true)) {
            header("location:" . $baseUrl . "/?q=management/roles&error=cannot_delete_builtin");
            exit();
        }

        $usersWithRole = $db->exec_prepared("SELECT COUNT(*) AS cnt FROM user WHERE role_id = ?", [$roleId]);
        if ((int) ($usersWithRole[0]["cnt"] ?? 0) > 0) {
            header("location:" . $baseUrl . "/?q=management/roles&error=role_in_use");
            exit();
        }

        $db->exec_prepared("DELETE FROM role_capabilities WHERE role_id = ?", [$roleId], false);
        $db->exec_prepared("DELETE FROM role WHERE id = ?", [$roleId], false);

        header("location:" . $baseUrl . "/?q=management/roles&msg=role_deleted");
        exit();
    }

    #[\mc\route("management/capabilities")]
    public static function manageCapabilities()
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }

        $db = \config::$db;
        $capabilities = $db->exec_prepared("SELECT id, name FROM capabilities ORDER BY name ASC");
        $roleCapRows = $db->exec_prepared(
            "SELECT rc.capability_id, r.name AS role_name
             FROM role_capabilities rc
             JOIN role r ON r.id = rc.role_id
             WHERE rc.permission = 1
             ORDER BY r.id ASC"
        );
        $rolesByCap = [];
        foreach ($roleCapRows as $row) {
            $rolesByCap[(int) $row["capability_id"]][] = (string) $row["role_name"];
        }

        $grouped = [];
        foreach ($capabilities as $cap) {
            $capName = (string) $cap["name"];
            $parts = explode("::", $capName, 2);
            $group = count($parts) === 2 ? $parts[0] : "general";
            $grouped[$group][] = $cap;
        }

        $html = self::statusBanner([
            "msg" => [
                "cap_created" => "Capability created.",
                "cap_deleted" => "Capability deleted.",
            ],
            "error" => [
                "invalid_name" => "Capability name must match format 'group::action' or alphanumeric/underscore identifier.",
                "cap_exists" => "That capability already exists.",
                "not_found" => "Capability not found.",
            ],
        ]);

        foreach ($grouped as $group => $caps) {
            $groupEsc = self::esc($group);
            $html .= "<h4>{$groupEsc}</h4>";
            $html .= "<table class='u-full-width'>";
            $html .= "<tr><th>ID</th><th>Capability</th><th>Enabled for Roles</th><th>Action</th></tr>";
            foreach ($caps as $cap) {
                $capId = (int) $cap["id"];
                $capNameEsc = self::esc((string) $cap["name"]);
                $roleList = $rolesByCap[$capId] ?? [];
                $rolesText = !empty($roleList) ? self::esc(implode(", ", $roleList)) : "<em>none</em>";
                $html .= "<tr>"
                    . "<td>{$capId}</td>"
                    . "<td><code>{$capNameEsc}</code></td>"
                    . "<td>{$rolesText}</td>"
                    . "<td>"
                    . "<form method='post' action='<!-- www -->/?q=capability/delete/{$capId}' style='display:inline;' onsubmit=\"return confirm('Delete capability {$capNameEsc}?');\">"
                    . "<!-- csrf-input -->"
                    . "<button type='submit' class='button'>Delete</button>"
                    . "</form>"
                    . "</td>"
                    . "</tr>";
            }
            $html .= "</table>";
        }

        $html .= "<h4>Add Capability</h4>";
        $html .= "<form method='post' action='<!-- www -->/?q=capability/create'>"
            . "<!-- csrf-input -->"
            . "<div class='row'>"
            . "<div class='six columns'>"
            . "<label for='new-cap-name'>Capability name</label>"
            . "<input type='text' id='new-cap-name' name='name' class='u-full-width' placeholder='e.g. endgame::export' required />"
            . "</div>"
            . "<div class='six columns' style='padding-top:2.4rem;'>"
            . "<input type='submit' value='Add Capability' class='button-primary' />"
            . "</div>"
            . "</div>"
            . "</form>";

        return self::renderOptionPage(
            "Manage Capabilities",
            "View registered capabilities, see which roles use them, and add or remove capabilities.",
            $html
        );
    }

    #[\mc\route("capability/create")]
    public static function createCapability()
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            exit();
        }

        $baseUrl = self::baseUrl();
        $name = trim((string) ($_POST["name"] ?? ""));
        if (!preg_match('/^[a-zA-Z0-9_]+(::[a-zA-Z0-9_]+)?$/', $name)) {
            header("location:" . $baseUrl . "/?q=management/capabilities&error=invalid_name");
            exit();
        }

        $db = \config::$db;
        $existing = $db->exec_prepared("SELECT id FROM capabilities WHERE name = ?", [$name]);
        if (!empty($existing)) {
            header("location:" . $baseUrl . "/?q=management/capabilities&error=cap_exists");
            exit();
        }

        $db->exec_prepared("INSERT INTO capabilities (name) VALUES (?)", [$name], false);
        header("location:" . $baseUrl . "/?q=management/capabilities&msg=cap_created");
        exit();
    }

    #[\mc\route("capability/delete")]
    public static function deleteCapability(array $params)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            exit();
        }

        $baseUrl = self::baseUrl();
        $capId = isset($params[0]) ? (int) $params[0] : 0;
        if ($capId <= 0) {
            header("location:" . $baseUrl . "/?q=management/capabilities&error=not_found");
            exit();
        }

        $db = \config::$db;
        $existing = $db->exec_prepared("SELECT id FROM capabilities WHERE id = ?", [$capId]);
        if (empty($existing)) {
            header("location:" . $baseUrl . "/?q=management/capabilities&error=not_found");
            exit();
        }

        $db->exec_prepared("DELETE FROM role_capabilities WHERE capability_id = ?", [$capId], false);
        $db->exec_prepared("DELETE FROM user_menu WHERE capability_id = ?", [$capId], false);
        $db->exec_prepared("DELETE FROM capabilities WHERE id = ?", [$capId], false);

        header("location:" . $baseUrl . "/?q=management/capabilities&msg=cap_deleted");
        exit();
    }

    #[\mc\route("module/install")]
    public static function install_module(array $params)
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }
        if (empty($params)) {
            header("location:" . self::baseUrl() . "/?q=management/modules");
            return "";
        }
        // check if the module exists
        if (in_array($params[0], self::newModules())) {
            self::installModule($params[0]);
        }
        header("location:" . self::baseUrl() . "/?q=management/modules");
        return "";
    }

    #[\mc\route("module/uninstall")]
    public static function uninstall_module(array $params)
    {
        if (!self::isAdmin()) {
            header("location:" . \config::www);
            return "";
        }
        if (empty($params)) {
            header("location:" . self::baseUrl() . "/?q=management/modules");
            return "";
        }
        // check if the module exists
        if (in_array($params[0], array_column(self::installedModules(), \meta\modules::NAME))) {
            self::uninstallModule($params[0]);
        }
        header("location:" . self::baseUrl() . "/?q=management/modules");
        return "";
    }
}
