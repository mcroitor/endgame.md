<?php

use core\template;
use mc\sql\database;

class statistics {

    public static function endgames() {
        return (new database(config::dsn))->select("endgame", ["count(pid) as nr"])[0]["nr"];
    }

    public static function authors() {
        return (new database(config::dsn))->select("endgame", ["count(distinct author) as nr"])[0]["nr"];
    }

    public static function queries() {
        return (new database(config::dsn))->select("statistic", ["count(id) as nr"])[0]["nr"];
    }

    public static function users() {
        return (new database(config::dsn))->select("statistic", ["count(distinct ip) as nr"])[0]["nr"];
    }

    public static function block() {
        $template = new template(file_get_contents(__DIR__ . "/statistics.template.php"));
        $data = [
            "<!-- endgames -->" => statistics::endgames(),
            "<!-- authors -->" => statistics::authors(),
            "<!-- queries -->" => statistics::queries(),
            "<!-- users -->" => statistics::users(),
        ];

        return $template->fill($data)->value();
    }
}