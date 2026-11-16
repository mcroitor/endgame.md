<?php

include_once __DIR__ . "/../config.php";

#[\mc\route("pgn")]
function get_pgn(array $params)
{
    $pgnId = empty($params) ? 1 : (int) $params[0];
    $crud = new \mc\sql\crud(\config::$db, "raw");
    return $crud->select($pgnId);
}

#[\mc\route("data")]
function get_data(array $params)
{
    $db = \config::$db;

    $html = [];

    $data = file_get_contents("php://input");

    $request = json_decode($data);
    if (!$request) {
        return ["html" => [], "stat" => 0];
    }

    $escape = static fn($val): string => str_replace("'", "''", (string) $val);

    $author = $escape(translitIt((string) ($request->author ?? "")));
    $wmin = (int) ($request->wmin ?? 0);
    $wmax = (int) ($request->wmax ?? 32);
    $bmin = (int) ($request->bmin ?? 0);
    $bmax = (int) ($request->bmax ?? 32);

    $fromDate = (int) (!empty($request->from_date) ? $request->from_date : (!empty($request->fromDate) ? $request->fromDate : 0));
    $toDate = (int) (!empty($request->to_date) ? $request->to_date : (!empty($request->toDate) ? $request->toDate : 2050));

    $page = 12 * (int) ($request->page ?? 0);
    if ($page < 0) {
        $page = 0;
    }

    $query_config = [
        \mc\sql\query::TYPE => \mc\sql\query::SELECT,
        \mc\sql\query::TABLE => "endgame",
        \mc\sql\query::FIELDS => ["*"],
        \mc\sql\query::WHERE => [
            "author LIKE '%{$author}%'",
            "whitep BETWEEN {$wmin} and {$wmax}",
            "blackp BETWEEN {$bmin} and {$bmax}",
            "date BETWEEN {$fromDate} and {$toDate}",
        ],
    ];

    $stipulation = (string) ($request->stipulation ?? "-");
    if ($stipulation !== "-") {
        $query_config[\mc\sql\query::WHERE][] = "stipulation LIKE '" . $escape($stipulation) . "'";
    }
    $theme = (string) ($request->theme ?? "-");
    if ($theme !== "-") {
        $query_config[\mc\sql\query::WHERE][] = "theme LIKE '%" . $escape($theme) . "%'";
    }
    $piecePattern = (string) ($request->piece_pattern ?? "");
    if ($piecePattern !== "") {
        $query_config[\mc\sql\query::WHERE][] = "piece_pattern='" . $escape($piecePattern) . "'";
    }
    $cook = $request->cook ?? "all";
    if ($cook === true || $cook === "1" || $cook === 1) {
        $query_config[\mc\sql\query::WHERE][] = "cook=1";
    } elseif ($cook === "0" || $cook === 0) {
        $query_config[\mc\sql\query::WHERE][] = "cook=0";
    }

    $sortMap = [
        "pid_asc" => ["pid" => "ASC"],
        "date_asc" => ["date" => "ASC", "pid" => "ASC"],
        "date_desc" => ["date" => "DESC", "pid" => "ASC"],
        "author_asc" => ["author" => "ASC", "pid" => "ASC"],
        "pieces_asc" => ["(whitep + blackp)" => "ASC", "pid" => "ASC"],
    ];
    $sortKey = (string) ($request->sort ?? "pid_asc");
    $query_config[\mc\sql\query::ORDER] = $sortMap[$sortKey] ?? $sortMap["pid_asc"];

    $query = new \mc\sql\query($query_config);

    $queryResult = $db->exec($query);
    $stat = count($queryResult);

    $query = $query->limit(12, $page);

    // logging
    $dblogger = new \core\dblogger($db, \meta\statistic::__name__);
    $logData = [
        \meta\statistic::QUERY => $query->build(),
        \meta\statistic::TIME => time(),
        \meta\statistic::IP => $_SERVER['REMOTE_ADDR'] ?? ''
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
\config::$logger->debug("routes: " . json_encode(\mc\router::getRoutes()), config::debug);
echo json_encode(\mc\router::run());
\config::$logger->debug("route: " . \mc\router::getSelectedRoute(), config::debug);
