<?php

date_default_timezone_set("Europe/Chisinau");

/**
 * common configuration class defines site structure and loads dependencies.
 */
class config {

    public const root_dir = __DIR__ . DIRECTORY_SEPARATOR;

/**
 * This constant is used for correct redirections. If you want to place
 * database in the Internet, please edit value of this constant.
 */
    public const www = "http://localhost:8000";

/**
 * defines path to the core.
 */
    public const core_dir = self::root_dir . "core" . DIRECTORY_SEPARATOR;

/**
 * defines path to the modules.
 */
    public const modules_dir = self::root_dir . "modules" . DIRECTORY_SEPARATOR;
    public const theme_dir = self::root_dir . "theme" . DIRECTORY_SEPARATOR;
    public const template_dir = self::theme_dir . "templates" . DIRECTORY_SEPARATOR;
    public const dbname = "endgame.20220115.sqlite";

/**
 * dsn for database connection. At the moment sqlite database is used, but
 * MySQL is possible.
 */
    public const dsn = "sqlite:" . self::root_dir . "database/" . self::dbname;

/**
 * core files enumeration.
 */
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

/**
 * method for core loading (it is simulation of autoload, by the way).
 */
    public static function include_core() {
        foreach (self::core as $class_name) {
            include_once self::core_dir . "{$class_name}.php";
        }
    }

    public static function load_modules() {
        $db = new \mc\sql\database(config::dsn);
        $modules = $db->select("modules");
        foreach ($modules as $module){
            include_once self::modules_dir . "{$module['name']}/{$module['entry_point']}.php";
        }

    }

}

config::include_core();
\mc\user::init();
config::load_modules();

/**
 * meta files describes structure of tables. Used for autocompleting.
 * not is necessary, by the way.
 */
include_once config::root_dir . '/meta/_include_meta.php';
