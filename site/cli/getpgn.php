<?php

if (!file_exists(__DIR__ . "/../config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}

include_once __DIR__ . "/../config.php";

$pid = filter_input(INPUT_GET, "pid");
$game = \config::$db->select("raw", ["*"], ["id" => $pid])[0]["data"];

header('Content-type: text/plain');
header("Content-Disposition: attachment; filename=endgame_{$pid}.pgn");
$game = str_replace(["rn", "\\r\\n"], " ", $game);
    
echo $game;
