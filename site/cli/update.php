<?php

if (php_sapi_name() !== "cli") {
    exit("not CLI");
}

include_once __DIR__ . "/../config.php";

\config::$logger->info("start fix");
$endgames = \config::$db->select(\meta\raw::__name__);

foreach ($endgames as $endgame) {
    \config::$db->update(
        \meta\raw::__name__,
        [\meta\raw::DATA => str_replace('[Round "?\\"]', '[Round "?"]', $endgame[\meta\raw::DATA])],
        [\meta\raw::ID => $endgame[\meta\raw::ID]]
    );
}

\config::$logger->info("end fix");