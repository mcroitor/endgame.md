<?php

// $db->query_sql('SET NAMES utf8');
date_default_timezone_set("Europe/Chisinau");

class config {

    public const root_dir = __DIR__ . DIRECTORY_SEPARATOR;
    public const core_dir = self::root_dir . "core" . DIRECTORY_SEPARATOR;
    public const dbname = "endgame.20210106.db";
    public const dsn = "sqlite:" . self::root_dir . "database/" . self::dbname;
    private const core = [
        "clitext",
        "database",
        "logger",
        "template",
    ];
    private const core_html = [
    ];

    public static function include_core() {
        foreach (self::core as $class_name) {
            include_once self::core_dir . "{$class_name}.php";
        }
    }

    public static function include_core_html() {
        foreach (self::core_html as $class_name) {
            include_once self::core_dir . "html" . DIRECTORY_SEPARATOR . "{$class_name}.php";
        }
    }

}

config::include_core();
include './meta/_include_meta.php';
