<?php

if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}
include_once __DIR__ . "/config.php";

use \modules\articles\articles;
use \modules\statistics\statistics;

articles::init();

$routes = [
    "links" => function (array $params) {
        return facade::file(config::template_dir . "links.template.php");
    }
];

// register routes
\mc\router::init($routes);

$fill = [
    "login-form" => \mc\user::login_form(),
    "user-menu" => \mc\user::user_menu(),
    "content" => \mc\router::run(),
    "statistics" => statistics::block(),
    "www" => \config::www,
];

echo facade::template("index.template.php")->fill($fill)->value();
