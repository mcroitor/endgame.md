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

    $author = translitIt((string) ($request->author ?? ""));
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

    $where = [
        "author LIKE ?",
        "whitep BETWEEN ? AND ?",
        "blackp BETWEEN ? AND ?",
        "date BETWEEN ? AND ?",
    ];
    $params = [
        "%{$author}%",
        $wmin,
        $wmax,
        $bmin,
        $bmax,
        $fromDate,
        $toDate,
    ];

    $stipulation = (string) ($request->stipulation ?? "-");
    if ($stipulation !== "-") {
        $where[] = "stipulation LIKE ?";
        $params[] = $stipulation;
    }
    $theme = (string) ($request->theme ?? "-");
    if ($theme !== "-") {
        $where[] = "theme LIKE ?";
        $params[] = "%{$theme}%";
    }
    $piecePatternRaw = (string) ($request->piece_pattern ?? "");
    $piecePattern = preg_replace('/[^QRBNPqrbnp]/', '', $piecePatternRaw);
    if ($piecePattern !== "") {
        $order = "QRBNPqrbnp";
        $counts = [];
        for ($i = 0; $i < strlen($order); $i++) {
            $ch = $order[$i];
            $cnt = substr_count($piecePattern, $ch);
            if ($cnt > 0) {
                $counts[$ch] = $cnt;
            }
        }
        $canonical = "";
        $glob = "*";
        foreach ($counts as $ch => $cnt) {
            $rep = str_repeat($ch, $cnt);
            $canonical .= $rep;
            $glob .= $rep . "*";
        }
        $pieceMode = (string) ($request->piece_mode ?? "exact");
        if ($pieceMode === "contains") {
            $where[] = "piece_pattern GLOB ?";
            $params[] = $glob;
        } else {
            $where[] = "piece_pattern = ?";
            $params[] = $canonical;
        }
    }
    $cook = $request->cook ?? "all";
    if ($cook === true || $cook === "1" || $cook === 1) {
        $where[] = "cook = 1";
    } elseif ($cook === "0" || $cook === 0) {
        $where[] = "cook = 0";
    }

    $sortMap = [
        "pid_asc" => "pid ASC",
        "date_asc" => "date ASC, pid ASC",
        "date_desc" => "date DESC, pid ASC",
        "author_asc" => "author ASC, pid ASC",
        "pieces_asc" => "(whitep + blackp) ASC, pid ASC",
    ];
    $sortKey = (string) ($request->sort ?? "pid_asc");
    $orderBy = $sortMap[$sortKey] ?? $sortMap["pid_asc"];

    $whereSql = implode(" AND ", $where);
    $countSql = "SELECT COUNT(*) AS cnt FROM endgame WHERE {$whereSql}";
    $countRows = $db->exec_prepared($countSql, $params);
    $stat = (int) ($countRows[0]["cnt"] ?? 0);

    $sql = "SELECT * FROM endgame WHERE {$whereSql} ORDER BY {$orderBy} LIMIT 12 OFFSET {$page}";

    // logging
    $dblogger = new \core\dblogger($db, \meta\statistic::__name__);
    $logData = [
        \meta\statistic::QUERY => $sql,
        \meta\statistic::TIME => time(),
        \meta\statistic::IP => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    $dblogger->write($logData);
    //end logging

    $result = $db->exec_prepared($sql, $params);

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
