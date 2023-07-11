<?php

include_once __DIR__ . "/../config.php";

#[route("pgn")]
function get_pgn(array $params) {
    $pgnId = empty($params) ? 1 : (int)$params[0];
    $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "raw");
    return $crud->select($pgnId);
}

// register routes
\mc\router::init();

// process route
echo json_encode(\mc\router::run());
