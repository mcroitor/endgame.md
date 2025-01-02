<?php

include_once __DIR__ . "/../config.php";

$logger = \mc\logger::stderr();

// #[\mc\route("pgn")]
function get_pgn(array $params)
{
    $pgnId = empty($params) ? 1 : (int) $params[0];
    $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "raw");
    return $crud->select($pgnId);
}

#[\mc\route("data")]
function get_data(array $params)
{
    $db = new \mc\sql\database(config::dsn);

    $html = [];

    $data = file_get_contents("php://input");

    $request = json_decode($data);
    $request->author = translitIt($request->author);

    $request->fromDate = empty($request->fromDate) ? "0000" : $request->fromDate;
    $request->toDate = empty($request->toDate) ? "2050" : $request->toDate;

    $page = 12 * $request->page;
    if ($page < 0) {
        $page = 0;
    }

    $query_config = [
        \mc\sql\query::TYPE => \mc\sql\query::SELECT,
        \mc\sql\query::TABLE => "endgame",
        \mc\sql\query::FIELDS => ["*"],
        \mc\sql\query::WHERE => [
            "author LIKE '%{$request->author}%'",
            "whitep BETWEEN {$request->wmin} and {$request->wmax}",
            "blackp BETWEEN {$request->bmin} and {$request->bmax}",
            "date BETWEEN {$request->fromDate} and {$request->toDate}",
        ],
    ];

    if ($request->stipulation !== "-") {
        $query_config[\mc\sql\query::WHERE][] = "stipulation LIKE '{$request->stipulation}'";
    }
    if ($request->theme !== "-") {
        $query_config[\mc\sql\query::WHERE][] = "theme LIKE '%{$request->theme}%'";
    }
    if ($request->piece_pattern != "") {
        $query_config[\mc\sql\query::WHERE][] = "piece_pattern='{$request->piece_pattern}'";
    }
    if (!empty($request->cook)) {
        $query_config[\mc\sql\query::WHERE][] = "cook=1";
    }

    $query = new \mc\sql\query($query_config);

    $queryResult = $db->exec($query);
    $stat = count($queryResult);

    $query = $query->limit(12, $page);

    // logging
    $dblogger = new \core\dblogger($db, \meta\statistic::__name__);
    $logData = [
        \meta\statistic::QUERY => $query->build(),
        \meta\statistic::TIME => time(),
        \meta\statistic::IP => $_SERVER['REMOTE_ADDR']
    ];
    $dblogger->write($logData);
    //end logging

    $result = $db->exec($query);

    foreach ($result as $fetch) {
        $html[] = [
            "#author#" => stripslashes($fetch[\meta\endgame::AUTHOR]),
            "#fen#" => $fetch[\meta\endgame::FEN],
            "#stip#" => $fetch[\meta\endgame::STIPULATION],
            "#source#" => stripslashes($fetch[\meta\endgame::SOURCE]),
            "#pieces#" => $fetch[\meta\endgame::WHITEP] . " + " . $fetch[\meta\endgame::BLACKP],
            "#pid#" => $fetch[\meta\endgame::PID],
            "#date#" => $fetch[\meta\endgame::DATE],
        ];
    }

    return ["html" => $html, "stat" => $stat];
}

// register routes
\mc\router::init();

// process route
$logger->debug("routes: " . json_encode(\mc\router::getRoutes()), config::debug);
echo json_encode(\mc\router::run());
$logger->debug("route: " . \mc\router::getSelectedRoute(), config::debug);
