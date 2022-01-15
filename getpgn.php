<?php

if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}
include("config.php");

$pid = filter_input(INPUT_GET, "pid");
$db = new \core\database(config::dsn);
$game = $db->select("raw", ["*"], ["id" => $pid])[0]["data"];

header('Content-type: text/plain');
header("Content-Disposition: attachment; filename=endgame_{$pid}.pgn");
$game = str_replace(["rn", "\\r\\n"], " ", $game);
    
//$source = stripslashes($fetch["source"]);
//$author = stripslashes($fetch["author"]);
//$solution = stripslashes($fetch["solution"]);
//$game = "[Event '{$source}']
//[Site '?']
//[Date '{$fetch["date"]}']
//[Round '?']
//[White '{$author}']
//[Black '{$fetch["stipulation"]}']
//[Result '*']
//[FEN '{$fetch["fen"]}']
//
//{$solution}\r\n";

echo $game;
