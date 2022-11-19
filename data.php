<?php
if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}

include_once (__DIR__ . "/config.php");

$db = new \mc\sql\database(config::dsn);

$html = [];

$data = file_get_contents("php://input");

$request = json_decode($data);
//\core\logger::stdout()->error(print_r($request, true));
//if (empty($request->author)) {
//    echo "{'error': 'no author'}";
//    exit();
//}
$request->author = translitIt($request->author);

$request->fromDate = !empty($request->fromDate) ? $request->fromDate . ".00.00" : "0000.00.00";
$request->toDate = !empty($request->toDate) ? $request->toDate . ".??.??" : "2050.00.00";

$row = 0;
$page = 12 * $request->page;
if ($page < 0) {
    $page = 0;
}

$query = "SELECT * FROM endgame WHERE author LIKE '%{$request->author}%' ";
if ((int) $request->wpiece > 0) {
    $query .= "AND whitep {$request->wsign} {$request->wpiece} ";
}
if ((int) $request->bpiece > 0) {
    $query .= "AND blackp {$request->bsign}{$request->bpiece} ";
}
if ($request->stipulation !== "-") {
    $query .= "AND stipulation LIKE '{$request->stipulation}' ";
}
if ($request->theme !== "-") {
    $query .= "AND theme LIKE '%{$request->theme}%' ";
}
if ($request->piece_pattern != "") {
    $query .= "AND piece_pattern='{$request->piece_pattern}' ";
}
$query .= "AND date>='{$request->fromDate}' AND date<='{$request->toDate}'";
if (!empty($request->cook)) {
    $query .= " AND cook=1";
}

$__result = $db->query_sql($query);
$stat = count($__result);

$query .= " LIMIT $page, 12";

// logging
$dblogger = new \core\dblogger($db, \meta\statistic::__name__);
$log_data = [
    \meta\statistic::QUERY => $query,
    \meta\statistic::TIME => time(),
    \meta\statistic::IP => $_SERVER['REMOTE_ADDR']
];
$dblogger->write($log_data);
//end logging

$result = $db->query_sql($query);

foreach ($result as $fetch) {
    $html[] = array("#author#" => stripslashes($fetch[\meta\endgame::AUTHOR]),
        "#fen#" => $fetch[\meta\endgame::FEN],
        "#stip#" => $fetch[\meta\endgame::STIPULATION],
        "#source#" => stripslashes($fetch[\meta\endgame::SOURCE]),
        "#pieces#" => $fetch[\meta\endgame::WHITEP] . " + " . $fetch[\meta\endgame::BLACKP],
        "#pid#" => $fetch[\meta\endgame::PID],
        "#date#" => $fetch[\meta\endgame::DATE],
    );
}

echo json_encode(["html" => $html, "stat" => $stat]);
