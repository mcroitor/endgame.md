<?php

/**
 * site management class
 * Access to the settings and configurations of the site, install / uninstall modules, etc.
 */
class management
{
    private static function installedModules()
    {
        $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), \meta\modules::__name__);
        $installed = $crud->all(0, $crud->count());
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
        return $new;
    }

    public static function installModule($module)
    {
        $db = new \mc\sql\database(config::dsn);
        $crud = new \mc\sql\crud($db, \meta\modules::__name__);
        if(file_exists(config::modules_dir . "{$module}/{$module}.php")){
            $crud->insert([
                \meta\modules::NAME => $module,
                \meta\modules::ENTRY_POINT => $module
            ]);
        }
        elseif(file_exists(config::modules_dir . "{$module}/install")){
            include config::modules_dir . "{$module}/install";
        }
    }
}
