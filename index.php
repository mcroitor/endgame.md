<?php

if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}
include_once __DIR__ . "/config.php";

$db = new \mc\sql\database(config::dsn);

$template = file_get_contents(__DIR__ . "/theme/templates/index.template.php");

articles::init();

$routes = [
    "/" => function (array $params) {
        return file_get_contents(config::template_dir . "searchform.template.php");
    },
    "links" => function (array $params) {
        return file_get_contents(config::template_dir . "links.template.php");
    }
];

// register routes
\mc\router::init($routes);

$fill = [
//    "<!-- last_changes -->" => statistics::last_changes(),
    "<!-- login-form -->" => \mc\user::login_form(),
    "<!-- user-menu -->" => \mc\user::user_menu(),
    "<!-- content -->" => \mc\router::run(),
    "<!-- statistics -->" => statistics::block(),
    "<!-- www -->" => config::www
];

echo (new \mc\template($template))->fill($fill)->value();
