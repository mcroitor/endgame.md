<?php

date_default_timezone_set("Europe/Chisinau");

class config {

    public const root_dir = __DIR__ . DIRECTORY_SEPARATOR;
    public const www = "http://localhost:8000";
    public const core_dir = self::root_dir . "core" . DIRECTORY_SEPARATOR;
    public const modules_dir = self::root_dir . "modules" . DIRECTORY_SEPARATOR;
    public const theme_dir = self::root_dir . "theme" . DIRECTORY_SEPARATOR;
    public const template_dir = self::theme_dir . "templates" . DIRECTORY_SEPARATOR;
    public const dbname = "endgame.20220115.sqlite";
    public const dsn = "sqlite:" . self::root_dir . "database/" . self::dbname;
    private const core = [
        "clitext",
        "database/mc/database",
        "database/mc/crud",
        "dblogger",
        "lib",
        "logger/mc/logger",
        "router/mc/router",
        "template",
        "user",
    ];

    public static function include_core() {
        foreach (self::core as $class_name) {
            include_once self::core_dir . "{$class_name}.php";
        }
    }

    public static function load_modules() {
        $db = new \mc\sql\database(config::dsn);
        $modules = $db->select("modules");
        foreach($modules as $module){
            include_once self::modules_dir . "{$module['name']}/{$module['entry_point']}.php";
        }

    }

}

config::include_core();
\mc\user::init();
config::load_modules();

include_once config::root_dir . '/meta/_include_meta.php';
