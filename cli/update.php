<?php

if (php_sapi_name() !== "cli") {
    exit("not CLI");
}

include_once __DIR__ . "/../config.php";

$db = new \mc\sql\database(config::dsn);
$logger = \mc\logger::stdout();

$logger->info("start fix");
$endgames = $db->select(\meta\raw::__name__);

foreach ($endgames as $endgame) {
    $db->update(
        \meta\raw::__name__,
        [\meta\raw::DATA => str_replace('[Round "?\\"]', '[Round "?"]', $endgame[\meta\raw::DATA])],
        [\meta\raw::ID => $endgame[\meta\raw::ID]]
    );
}

$logger->info("end fix");