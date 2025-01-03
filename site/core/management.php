<?php

use mc\user;

/**
 * site management class
 * Access to the settings and configurations of the site, install / uninstall modules, etc.
 */
class management
{

    private static function installedModules()
    {
        $crud = new \mc\sql\crud(\config::$db, \meta\modules::__name__);
        $installed = $crud->all(0, $crud->count());
        \config::$logger->debug("Installed modules: " . json_encode($installed), \config::debug);
        return $installed;
    }

    private static function availableModules()
    {
        $dirs = scandir(config::modules_dir);
        $modules = [];
        foreach ($dirs as $dir) {
            if ($dir === "." || $dir === "..") {
                continue;
            }
            if (
                is_dir(config::modules_dir . $dir) &&
                (
                    file_exists(config::modules_dir . "{$dir}/{$dir}.php") ||
                    file_exists(config::modules_dir . "{$dir}/install")
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
        if (file_exists(config::modules_dir . "{$module}/{$module}.php")) {
            $crud->insert([
                \meta\modules::NAME => $module,
                \meta\modules::ENTRY_POINT => $module
            ]);
        } elseif (file_exists(config::modules_dir . "{$module}/install")) {
            include config::modules_dir . "{$module}/install/install.php";
        }
        header("location:" . config::www . "?q=management/modules");
    }

    public static function uninstallModule($module)
    {
        $crud = new \mc\sql\crud(\config::$db, \meta\modules::__name__);
        $crud->delete($module);
        if (file_exists(config::modules_dir . "{$module}/install")) {
            include config::modules_dir . "{$module}/install/uninstall.php";
        }
    }

    #[\mc\route("management")]
    public static function managementView()
    {
        if (user::has_capability("user::authenticate") || user::role() !== "admin") {
            header("location:" . config::www);
            return "";
        }
        return file_get_contents(config::template_dir . "management.template.php");
    }

    #[\mc\route("management/users")]
    public static function manageUsers()
    {
        if (user::has_capability("user::authenticate") || user::role() !== "admin") {
            header("location:" . config::www);
            return "";
        }
        $template = new \mc\template(
            file_get_contents(config::template_dir . "manageoption.template.php")
        );
        $html = "<table class='u-full-width'>";
        $html .= "<tr><th>Username</th><th>Role</th><th>Action</th></tr>";

        $users = \config::$db->select(\meta\user::__name__, ["*"]);
        foreach ($users as $user) {
            $role = \config::$db->select(
                \meta\role::__name__,
                [\meta\role::NAME],
                ["id" => $user[\meta\user::ROLE_ID]]
            )[0][\meta\role::NAME];
            $html .= "<tr><td>{$user[\meta\user::LOGIN]}</td>" .
                "<td>{$role}</td>" .
                "<td><a href='/?q=user/edit/{$user[\meta\user::ID]}'>Edit</a></td></tr>";
        }
        $html .= "</table>";
        $data = [
            "title" => "Manage Users",
            "description" => "Here you can manage your users.",
            "html" => $html,
        ];
        return $template->fill($data)->value();
    }

    #[\mc\route("management/modules")]
    public static function manageModules()
    {
        if (user::has_capability("user::authenticate") || user::role() !== "admin") {
            header("location:" . config::www);
            return "";
        }
        $template = new \mc\template(
            file_get_contents(config::template_dir . "manageoption.template.php"),
            [
                "prefix" => "<!-- ",
                "suffix" => " -->"
            ]
        );
        $new = self::newModules();
        $installed = self::installedModules();
        $html = "";
        if (!empty($new)) {
            $html .= "<h2>New Modules</h2>";
            $html .= "<table class='u-full-width'>";
            $html .= "<tr><th>Module name</th><th>Action</th></tr>";
            foreach ($new as $module) {
                $html .= "<tr><td>{$module}</td><td><a href='<!-- www -->/?q=module/install/{$module}'>Install</a></td></tr>";
            }
            $html .= "</table>";
        }
        $html .= "<h2>Installed Modules</h2>";
        $html .= "<table class='u-full-width'>";
        $html .= "<tr><th>Module name</th><th>Action</th></tr>";
        foreach ($installed as $module) {
            $html .= "<tr><td>{$module[\meta\modules::NAME]}</td><td><a href='<!-- www -->/?q=module/uninstall/{$module[\meta\modules::NAME]}'>Uninstall</a></td></tr>";
        }
        $html .= "</table>";
        $data = [
            "title" => "Manage Modules",
            "description" => "Here you can manage your modules.",
            "html" => $html,
        ];
        return $template->fill($data)->value();
    }

    #[\mc\route("module/install")]
    public static function install_module(array $params)
    {
        if (user::has_capability("user::authenticate") || user::role() !== "admin") {
            header("location:" . config::www);
            return "";
        }
        if (empty($params)) {
            header("location:" . config::www . "?q=management/modules");
            return "";
        }
        // check if the module exists
        if (in_array($params[0], self::newModules())) {
            self::installModule($params[0]);
        }
        header("location:" . config::www . "?q=management/modules");
        return "";
    }

    #[\mc\route("module/uninstall")]
    public static function uninstall_module(array $params)
    {
        if (user::has_capability("user::authenticate") || user::role() !== "admin") {
            header("location:" . config::www);
            return "";
        }
        if (empty($params)) {
            header("location:" . config::www . "?q=management/modules");
            return "";
        }
        // check if the module exists
        if (in_array($params[0], array_column(self::installedModules(), \meta\modules::NAME))) {
            self::uninstallModule($params[0]);
        }
        header("location:" . config::www . "?q=management/modules");
        return "";
    }
}
