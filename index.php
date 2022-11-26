<?php

if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}
include_once __DIR__ . "/config.php";

// total endgames
$db = new \mc\sql\database(config::dsn);
$total_endgames = $db->select(\meta\endgame::__name__, ["COUNT(*) AS total"])[0]['total'];

// last changes
$last_changes = "";
$changes_table = \meta\changes::__name__;
$select_changes = $db->query_sql("SELECT * FROM {$changes_table} ORDER BY date DESC LIMIT 5");
foreach ($select_changes as $fetch) {
    $changes = new \meta\changes($fetch);
    $last_changes .= "<li>{$changes->date}: {$changes->nr_games} "
    . "endgames from {$changes->filename}</li>";
}


$template = file_get_contents(__DIR__ . "/theme/templates/index.template.php");

articles::init();

\mc\logger::stdout()->info(json_encode(articles::getHtml(0, 5)));

$fill = [
    "<!-- total_endgames -->" => $total_endgames,
    "<!-- last_changes -->" => $last_changes,
    "<!-- login-form -->" => \mc\user::login_form(),
    "<!-- articles -->" => articles::getHtml(0, 5)
];

echo (new \core\template($template))->fill($fill)->value();