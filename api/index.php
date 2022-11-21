<?php

include_once __DIR__ . "/../config.php";

$routes = [
    "pgn" => function (array $params) {
        $pgnId = empty($params) ? 1 : (int)$params[0];
        $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "raw");
        return $crud->select($pgnId);
    },
    "user/login" => function (array $params) {
        \mc\user::login();
    },
    "user/logout" => function (array $params) {
        \mc\user::logout();
    }
];

// register routes
\mc\router::init($routes);

// process route
echo json_encode(\mc\router::run());
