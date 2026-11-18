<?php

include_once __DIR__ . "/../config.php";

function ensure_ratings_table(\mc\sql\database $db): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $db->exec_prepared(
        "CREATE TABLE IF NOT EXISTS endgame_ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            pid INTEGER NOT NULL,
            voter_hash TEXT NOT NULL,
            rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL,
            UNIQUE (pid, voter_hash)
        )",
        [],
        false
    );
    $db->exec_prepared(
        "CREATE INDEX IF NOT EXISTS idx_endgame_ratings_pid ON endgame_ratings(pid)",
        [],
        false
    );
    $ensured = true;
}

function get_voter_hash(bool $createCookie = false): string
{
    if (class_exists('\core\user') && \core\user::is_authenticated()) {
        return 'u:' . \core\user::id();
    }
    $cookie = $_COOKIE['endgame_voter'] ?? '';
    if (is_string($cookie) && preg_match('/^[a-f0-9]{32}$/', $cookie)) {
        return 'c:' . $cookie;
    }
    if ($createCookie) {
        $token = bin2hex(random_bytes(16));
        setcookie('endgame_voter', $token, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['endgame_voter'] = $token;
        return 'c:' . $token;
    }
    return '';
}

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
        "pid_asc" => "endgame.pid ASC",
        "date_asc" => "endgame.date ASC, endgame.pid ASC",
        "date_desc" => "endgame.date DESC, endgame.pid ASC",
        "author_asc" => "endgame.author ASC, endgame.pid ASC",
        "pieces_asc" => "(endgame.whitep + endgame.blackp) ASC, endgame.pid ASC",
        "rating_desc" => "rating_avg DESC, rating_count DESC, endgame.pid ASC",
        "rating_asc" => "(rating_count = 0) ASC, rating_avg ASC, rating_count DESC, endgame.pid ASC",
    ];
    $sortKey = (string) ($request->sort ?? "pid_asc");
    $orderBy = $sortMap[$sortKey] ?? $sortMap["pid_asc"];

    ensure_ratings_table($db);

    $whereSql = implode(" AND ", $where);
    $countSql = "SELECT COUNT(*) AS cnt FROM endgame WHERE {$whereSql}";
    $countRows = $db->exec_prepared($countSql, $params);
    $stat = (int) ($countRows[0]["cnt"] ?? 0);

    $sql = "SELECT endgame.*, COALESCE(r.rating_avg, 0) AS rating_avg, COALESCE(r.rating_count, 0) AS rating_count
            FROM endgame
            LEFT JOIN (
                SELECT pid AS r_pid, ROUND(AVG(rating), 2) AS rating_avg, COUNT(*) AS rating_count
                FROM endgame_ratings
                GROUP BY pid
            ) r ON r.r_pid = endgame.pid
            WHERE {$whereSql}
            ORDER BY {$orderBy}
            LIMIT 12 OFFSET {$page}";

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

    $userRatings = [];
    $voterHash = get_voter_hash(false);
    if ($voterHash !== '' && !empty($result)) {
        $pids = array_map(fn($row) => (int) $row[\meta\endgame::PID], $result);
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $urRows = $db->exec_prepared(
            "SELECT pid, rating FROM endgame_ratings WHERE voter_hash = ? AND pid IN ({$placeholders})",
            array_merge([$voterHash], $pids)
        );
        foreach ($urRows as $ur) {
            $userRatings[(int) $ur['pid']] = (int) $ur['rating'];
        }
    }

    foreach ($result as $fetch) {
        $pidInt = (int) $fetch[\meta\endgame::PID];
        $html[] = [
            "#author#" => stripslashes($fetch[\meta\endgame::AUTHOR]),
            "#fen#" => $fetch[\meta\endgame::FEN],
            "#stip#" => $fetch[\meta\endgame::STIPULATION],
            "#source#" => stripslashes($fetch[\meta\endgame::SOURCE]),
            "#pieces#" => $fetch[\meta\endgame::WHITEP] . " + " . $fetch[\meta\endgame::BLACKP],
            "#pid#" => $fetch[\meta\endgame::PID],
            "#date#" => $fetch[\meta\endgame::DATE],
            "#rating_avg#" => round((float) ($fetch["rating_avg"] ?? 0), 1),
            "#rating_count#" => (int) ($fetch["rating_count"] ?? 0),
            "#user_rating#" => $userRatings[$pidInt] ?? 0,
        ];
    }

    return ["html" => $html, "stat" => $stat];
}

#[\mc\route("rate")]
function rate_study(array $params)
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        return ["error" => "Method not allowed"];
    }

    $db = \config::$db;
    ensure_ratings_table($db);

    $raw = file_get_contents("php://input");
    $request = json_decode($raw);
    $pid = (int) ($request->pid ?? 0);
    $rating = (int) ($request->rating ?? 0);

    if ($pid <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        return ["error" => "Invalid pid or rating (expected 1..5)"];
    }

    $exists = $db->exec_prepared("SELECT pid FROM endgame WHERE pid = ?", [$pid]);
    if (empty($exists)) {
        http_response_code(404);
        return ["error" => "Study not found"];
    }

    $voterHash = get_voter_hash(true);
    $now = time();

    $db->exec_prepared(
        "INSERT INTO endgame_ratings (pid, voter_hash, rating, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?)
         ON CONFLICT(pid, voter_hash) DO UPDATE SET rating = excluded.rating, updated_at = excluded.updated_at",
        [$pid, $voterHash, $rating, $now, $now],
        false
    );

    $agg = $db->exec_prepared(
        "SELECT COALESCE(ROUND(AVG(rating), 2), 0) AS rating_avg, COUNT(*) AS rating_count
         FROM endgame_ratings WHERE pid = ?",
        [$pid]
    );

    return [
        "pid" => $pid,
        "rating_avg" => round((float) ($agg[0]["rating_avg"] ?? 0), 1),
        "rating_count" => (int) ($agg[0]["rating_count"] ?? 0),
        "user_rating" => $rating,
    ];
}

// register routes
\mc\router::init();

// process route
\config::$logger->debug("routes: " . json_encode(\mc\router::getRoutes()), config::debug);
echo json_encode(\mc\router::run());
\config::$logger->debug("route: " . \mc\router::getSelectedRoute(), config::debug);
