<?php
if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}

include "config.php";

$db = new \core\database(config::dsn);

$html = [];

function translitIt($str) {
    $tr = array(
        "А" => "A", "Б" => "B", "В" => "V", "Г" => "G",
        "Д" => "D", "Е" => "E", "Ж" => "J", "З" => "Z", "И" => "I",
        "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N",
        "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T",
        "У" => "U", "Ф" => "F", "Х" => "H", "Ц" => "TS", "Ч" => "CH",
        "Ш" => "SH", "Щ" => "SCH", "Ъ" => "", "Ы" => "Y", "Ь" => "",
        "Э" => "E", "Ю" => "YU", "Я" => "YA", "а" => "a", "б" => "b",
        "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j",
        "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
        "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
        "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
        "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
        "ы" => "y", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya"
    );
    return strtr($str, $tr);
}

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
//$rule = [
//    "{" => "<div class='commentary'>{",
//    "}" => "}</div>",
//    ")" => ")</div>",
//    "(" => "<div class='variant'>(",
//    " $1 " => "! ",
//    " $2 " => "? ",
//    " $3 " => "!! ",
//    " $4 " => "?? ",
//    " $5 " => "!? ",
//    " $6 " => "?! ",
//    " $11" => "=",
//    " $19" => "-+",
//    " $18" => "+-"
//];

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
