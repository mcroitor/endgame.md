<?php

namespace modules\statistics;

use mc\template;
use mc\sql\database;

class statistics {

    public static function endgames() {
        return \config::$db->select("endgame", ["count(pid) as nr"])[0]["nr"];
    }

    public static function authors() {
        return \config::$db->select("endgame", ["count(distinct author) as nr"])[0]["nr"];
    }

    public static function queries() {
        return \config::$db->select("statistic", ["count(id) as nr"])[0]["nr"];
    }

    public static function users() {
        return \config::$db->select("statistic", ["count(distinct ip) as nr"])[0]["nr"];
    }

    public static function last_changes() {
        // last changes
        $last_changes = "";
        $changes_table = \meta\changes::__name__;
        $select_changes = \config::$db->query_sql(
            "SELECT * FROM {$changes_table} ORDER BY date DESC LIMIT 5");
        foreach ($select_changes as $fetch) {
            $changes = new \meta\changes($fetch);
            $last_changes .= "<li>{$changes->date}: {$changes->nr_games} "
                . "endgames from {$changes->filename}</li>";
        }
        return $last_changes;
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
