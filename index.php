<?php

if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}
include_once __DIR__ . "/config.php";

$db = new \mc\sql\database(config::dsn);

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

$routes = [
    "/" => function (array $params) {
        return file_get_contents(config::template_dir . "searchform.template.php");
    },
//    "about" => "articles::getHtml",
//    "article/new" => "articles::createHtml",
    "links" => function (array $params) {
        return file_get_contents(config::template_dir . "links.template.php");
    }
];

// register routes
\mc\router::init($routes);

$fill = [
    "<!-- last_changes -->" => $last_changes,
    "<!-- login-form -->" => \mc\user::login_form(),
    "<!-- content -->" => \mc\router::run(),
    "<!-- statistics -->" => statistics::block(),
];

echo (new \core\template($template))->fill($fill)->value();
