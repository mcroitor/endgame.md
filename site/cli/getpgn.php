<?php

if (!file_exists(__DIR__ . "/../config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}

include_once __DIR__ . "/../config.php";

$pid = filter_input(INPUT_GET, "pid", FILTER_VALIDATE_INT);
if (!$pid) {
    http_response_code(400);
    exit("Invalid PID");
}
$rows = \config::$db->select("raw", ["*"], ["id" => $pid]);
if (empty($rows)) {
    http_response_code(404);
    exit("PGN not found");
}
$game = $rows[0]["data"];

header('Content-type: text/plain');
header("Content-Disposition: attachment; filename=endgame_{$pid}.pgn");
$game = str_replace(["\r\n", "\\r\\n"], " ", $game);
    
echo $game;
