<?php

namespace modules\analytics;

include_once __DIR__ . "/capabilities/analytics_capabilities.php";

use mc\user;
use modules\analytics\capabilities\ANALYTICS_CAPABILITY;
use modules\endgame\capabilities\ENDGAME_CAPABILITIY;

/**
 * Here we implement the logic for endgame analytics and statistics.
 * It will do:
 * 
 *  - detect authors and collect their statistics
 *  - detect endgame piece patterns
 *  - detect data mistakes and fix them
 */
class analytics
{
    /**
     * Check and fix the results of the endgame.
     */
    public static function fixResults()
    {
        $okResults = [
            "White wins",
            "Draw",
            "Black wins",
        ];
        $association = [
            "1-0" => "White wins",
            "1/2-1/2" => "Draw",
            "Win" => "White wins",
        ];
        $stat = [
            "fixed" => 0,
            "invalid" => 0,
        ];

        $db = \config::$db;
        $endgames = $db->select(\meta\endgame::__name__, ["*"], [
            \meta\endgame::STIPULATION . 
            " NOT IN ('{$okResults[0]}', '{$okResults[1]}', '{$okResults[2]}')"
        ]);
        foreach ($endgames as $endgame) {
            $result = $endgame[\meta\endgame::STIPULATION];
            if (isset($association[$result])) {
                $stat["fixed"]++;
                $endgame[\meta\endgame::STIPULATION] = $association[$result];
                $db->update(
                    \meta\endgame::__name__,
                    $endgame,
                    [\meta\endgame::PID => $endgame[\meta\endgame::PID]]
                );
            } else {
                $stat["invalid"]++;
            }
        }
        return $stat;
    }

    /**
     * Detect the authors of the endgames and update the database.
     */
    public static function detectBrokenAuthors()
    {
        // invalid author starts with non-alphabetic symbol.
        // select 20 endgames max.
        $db = \config::$db;
        $endgames = $db->select(
            \meta\endgame::__name__,
            ["*"],
            [
                \meta\endgame::AUTHOR . " GLOB '[^a-zA-Z]*'"
            ],
            [
                "offset" => 0,
                "limit" => 20
            ]
        );
        return $endgames;
    }

    /**
     * Fix the endgame piece patterns.
     */
    public static function fixPiecePatterns()
    {
        $db = \config::$db;
        $endgames = $db->select(
            \meta\endgame::__name__, 
            ["*"],
            [
                \meta\endgame::PIECE_PATTERN . 
                " IS NULL OR " . 
                \meta\endgame::PIECE_PATTERN . " like ''" .
                " OR " . \meta\endgame::PIECE_PATTERN . " = 'unknown'"
            ]
        );
        \mc\logger::stderr()->info("Endgames to fix: " . count($endgames));
        $stat = [
            "fixed" => 0,
            "removed" => 0,
        ];
        foreach ($endgames as $endgame) {
            $pattern = self::getPiecePattern($endgame);
            \mc\logger::stderr()->info("PID: " . $endgame[\meta\endgame::PID]);
            \mc\logger::stderr()->info("FEN: " . $endgame[\meta\endgame::FEN]);
            \mc\logger::stderr()->info("Pattern: " . $pattern);
            if($pattern === "") {
                $db->delete(
                    \meta\endgame::__name__,
                    [\meta\endgame::PID => $endgame[\meta\endgame::PID]]
                );
                $stat["removed"]++;
                continue;
            }
            if ($pattern === $endgame[\meta\endgame::PIECE_PATTERN]) {
                continue;
            }
            $endgame[\meta\endgame::PIECE_PATTERN] = $pattern;
            $db->update(
                \meta\endgame::__name__,
                $endgame,
                [\meta\endgame::PID => $endgame[\meta\endgame::PID]]
            );
            $stat["fixed"]++;
        }
        return $stat;
    }

    private static function getPiecePattern($endgame)
    {
        $pieceStat = [
            "Q" => 0,
            "R" => 0,
            "B" => 0,
            "N" => 0,
            "P" => 0,
            "q" => 0,
            "r" => 0,
            "b" => 0,
            "n" => 0,
            "p" => 0,
        ];
        $fen = $endgame[\meta\endgame::FEN];
        $epd = explode(" ", $fen)[0];
        for ($i = 0; $i < strlen($epd); $i++) {
            if (str_contains("QRBNPqrnbp", $epd[$i])) {
                $pieceStat[$epd[$i]]++;
            }
        }
        $pattern = "";
        foreach ($pieceStat as $piece => $count) {
            for ($i = 0; $i < $count; $i++) {
                $pattern .= $piece;
            }
        }
        return $pattern;
    }

    #[\mc\route("analytics/fix")]
    public static function fix(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::UPDATE)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }
        $fixResult = self::fixResults();
        // $authors = self::detectAuthors();
        $fixPatterns = self::fixPiecePatterns();
        $result = "<h3>Database fixes</h3>";
        $result .= "<ul>";
        $result .= "<li>Results fixed: " . $fixResult["fixed"] . "</li>";
        $result .= "<li>Results invalid: " . $fixResult["invalid"] . "</li>";
        // $result .= "<li>Authors detected: " . $authors . "</li>";
        $result .= "<li>Patterns fixed: " . $fixPatterns["fixed"] . "</li>";
        $result .= "<li>Endgames removed: " . $fixPatterns["removed"] . "</li>";
        $result .= "</ul>";
        return $result;
    }

    #[\mc\route("analytics/authors")]
    public static function authors(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::UPDATE)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }
        $brokenAuthors = self::detectBrokenAuthors();

        $html = "<h3>Broken authors</h3>";
        $html .= "<table class='u-full-width'>";
        $html .= "<tr><th>PID</th><th>Author</th><th>Source</th><th>Date</th><th>Actions</th></tr>";
        foreach ($brokenAuthors as $endgame) {
            $pid = (int) $endgame[\meta\endgame::PID];
            $authorEsc = htmlspecialchars((string) $endgame[\meta\endgame::AUTHOR], ENT_QUOTES, "UTF-8");
            $sourceEsc = htmlspecialchars((string) ($endgame[\meta\endgame::SOURCE] ?? ""), ENT_QUOTES, "UTF-8");
            $dateEsc = htmlspecialchars((string) ($endgame[\meta\endgame::DATE] ?? ""), ENT_QUOTES, "UTF-8");
            $html .= "<tr>";
            $html .= "<td><a href='<!-- www -->/?pid={$pid}'>{$pid}</a></td>";
            $html .= "<td>{$authorEsc}</td>";
            $html .= "<td>{$sourceEsc}</td>";
            $html .= "<td>{$dateEsc}</td>";
            $html .= "<td>";
            $html .= "<a href='<!-- www -->/?q=analytics/author/edit/{$pid}' class='button'>Edit</a> ";
            $html .= "<form method='post' action='<!-- www -->/?q=analytics/author/remove/{$pid}' style='display:inline;' onsubmit=\"return confirm('Delete study PID {$pid}?');\">";
            $html .= "<!-- csrf-input -->";
            $html .= "<input type='submit' value='Remove' class='button' />";
            $html .= "</form>";
            $html .= "</td>";
            $html .= "</tr>";
        }
        $html .= "</table>";
        return $html;
    }

    #[\mc\route("analytics/author")]
    public static function author(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::UPDATE)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }
        $action = $params[0] ?? "";
        $pid = isset($params[1]) ? (int)$params[1] : 0;
        if($pid <= 0) {
            return "Invalid PID";
        }
        switch($action) {
            case "edit":
                return self::editAuthor($pid);
            case "update":
                return self::updateAuthor($pid);
            case "remove":
                return self::removeAuthor($pid);
            default:
                return "Invalid action";
        }
    }

    private static function editAuthor($pid)
    {
        $db = \config::$db;
        $endgame = $db->select(
            \meta\endgame::__name__,
            ["*"],
            [\meta\endgame::PID => $pid]
        );
        if(count($endgame) === 0) {
            return "Endgame not found";
        }
        $endgame = $endgame[0];
        $fenEncoded = urlencode((string) $endgame[\meta\endgame::FEN]);
        $authorEscaped = htmlspecialchars((string) $endgame[\meta\endgame::AUTHOR], ENT_QUOTES, "UTF-8");
        $sourceEscaped = htmlspecialchars((string) ($endgame[\meta\endgame::SOURCE] ?? ""), ENT_QUOTES, "UTF-8");
        $dateEscaped = htmlspecialchars((string) ($endgame[\meta\endgame::DATE] ?? ""), ENT_QUOTES, "UTF-8");
        $stipEscaped = htmlspecialchars((string) ($endgame[\meta\endgame::STIPULATION] ?? ""), ENT_QUOTES, "UTF-8");

        $html = "<h3>Edit author (PID {$pid})</h3>";
        $html .= "<p><strong>Source:</strong> {$sourceEscaped} | <strong>Date:</strong> {$dateEscaped} | <strong>Stipulation:</strong> {$stipEscaped} | <a href='<!-- www -->/?pid={$pid}'>Open study</a></p>";
        $html .= "<img src='<!-- www -->/modules/diagram/?fen={$fenEncoded}&size=32' alt='Endgame diagram'>";
        $html .= "<form method='post' action='<!-- www -->/?q=analytics/author/update/{$pid}'>";
        $html .= "<!-- csrf-input -->";
        $html .= "<label for='author'>Author</label>";
        $html .= "<input type='text' id='author' name='author' value='{$authorEscaped}'>";
        $html .= "<input type='submit' value='Save' class='button-primary'>";
        $html .= "</form>";
        return $html;
    }

    private static function removeAuthor($pid)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed: POST required";
        }
        user::require_csrf();
        $db = \config::$db;
        $db->delete(\meta\endgame::__name__, [\meta\endgame::PID => $pid]);
        $db->delete("raw", ["id" => $pid]);
        $redirect = filter_input(INPUT_POST, "redirect") ?? "";
        $target = $redirect === "duplicates" ? "/?q=analytics/duplicates" : "/?q=analytics/authors";
        header("location:" . \config::www . $target);
        exit();
    }

    private static function updateAuthor($pid)
    {
        user::require_csrf();
        $author = filter_input(
            INPUT_POST, 
            "author", 
            FILTER_DEFAULT,
            ["options" => ["default" => ""]]);
        $db = \config::$db;
        $endgame = $db->select(
            \meta\endgame::__name__,
            ["*"],
            [\meta\endgame::PID => $pid]
        );
        if(count($endgame) === 0) {
            return "Endgame not found";
        }
        $endgame = $endgame[0];
        $endgame[\meta\endgame::AUTHOR] = $author;
        $db->update(
            \meta\endgame::__name__,
            $endgame,
            [\meta\endgame::PID => $pid]
        );
        header("location:" . \config::www . "/?q=analytics/authors");
        exit();
    }

    #[\mc\route("analytics/duplicates")]
    public static function duplicates(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::READ)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }

        $db = \config::$db;
        $rows = $db->query_sql("SELECT pid, author, source, date, fen FROM endgame ORDER BY pid ASC");
        $groups = [];
        foreach ($rows as $row) {
            $parts = explode(" ", trim((string) $row[\meta\endgame::FEN]));
            $key = implode(" ", array_slice($parts, 0, 4));
            if ($key === "") continue;
            $groups[$key][] = $row;
        }

        $dups = array_filter($groups, static fn($g) => count($g) > 1);
        $totalGroups = count($dups);
        $dups = array_slice($dups, 0, 30, true);

        $html = "<h3>Duplicate Positions</h3>";
        $html .= "<p>Found <strong>{$totalGroups}</strong> duplicate position groups (showing up to 30).</p>";
        if ($totalGroups === 0) {
            return $html . "<p>No duplicate positions found.</p>";
        }

        foreach ($dups as $fenKey => $items) {
            $fenUrl = urlencode((string) $items[0][\meta\endgame::FEN]);
            $html .= "<div class='analytics-dup-group' style='border:1px solid #e2e8f0; border-radius:6px; padding:1.2rem; margin-bottom:1.4rem;'>";
            $html .= "<div class='row'>";
            $html .= "<div class='three columns'><img src='<!-- www -->/modules/diagram/?fen={$fenUrl}&size=24' alt='Duplicate position' /></div>";
            $html .= "<div class='nine columns'><table class='u-full-width'><tr><th>PID</th><th>Author</th><th>Source</th><th>Date</th><th>Action</th></tr>";
            foreach ($items as $item) {
                $pid = (int) $item[\meta\endgame::PID];
                $authorEsc = htmlspecialchars((string) $item[\meta\endgame::AUTHOR], ENT_QUOTES, "UTF-8");
                $sourceEsc = htmlspecialchars((string) ($item[\meta\endgame::SOURCE] ?? ""), ENT_QUOTES, "UTF-8");
                $dateEsc = htmlspecialchars((string) ($item[\meta\endgame::DATE] ?? ""), ENT_QUOTES, "UTF-8");
                $html .= "<tr><td><a href='<!-- www -->/?pid={$pid}'>{$pid}</a></td><td>{$authorEsc}</td><td>{$sourceEsc}</td><td>{$dateEsc}</td>";
                $html .= "<td><form method='post' action='<!-- www -->/?q=analytics/author/remove/{$pid}' style='display:inline;' onsubmit=\"return confirm('Delete duplicate PID {$pid}?');\">";
                $html .= "<!-- csrf-input --><input type='hidden' name='redirect' value='duplicates' />";
                $html .= "<input type='submit' value='Delete' class='button' /></form></td></tr>";
            }
            $html .= "</table></div></div></div>";
        }
        return $html;
    }

    #[\mc\route("analytics/integrity")]
    public static function integrity(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::UPDATE)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }

        $db = \config::$db;
        $recounted = 0;
        if (($params[0] ?? "") === "recount" && ($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
            user::require_csrf();
            $all = $db->query_sql("SELECT pid, fen, whitep, blackp FROM endgame");
            foreach ($all as $row) {
                $epd = explode(" ", trim((string) $row[\meta\endgame::FEN]))[0] ?? "";
                $w = 0;
                $b = 0;
                for ($i = 0; $i < strlen($epd); $i++) {
                    if (str_contains("KQRBNP", $epd[$i])) $w++;
                    elseif (str_contains("kqrbnp", $epd[$i])) $b++;
                }
                if ((int) $row[\meta\endgame::WHITEP] !== $w || (int) $row[\meta\endgame::BLACKP] !== $b) {
                    $db->exec_prepared("UPDATE endgame SET whitep = ?, blackp = ? WHERE pid = ?", [$w, $b, (int) $row[\meta\endgame::PID]], false);
                    $recounted++;
                }
            }
        }

        $missingRaw = $db->query_sql("SELECT e.pid, e.author, e.date FROM endgame e LEFT JOIN raw r ON r.id = e.pid WHERE r.id IS NULL OR trim(r.data) = '' LIMIT 30");
        $orphanRaw = $db->query_sql("SELECT r.id FROM raw r LEFT JOIN endgame e ON e.pid = r.id WHERE e.pid IS NULL LIMIT 30");
        $pieceMismatches = $db->query_sql("SELECT pid, fen, whitep, blackp FROM endgame LIMIT 5000");
        $mismatchRows = [];
        foreach ($pieceMismatches as $row) {
            $epd = explode(" ", trim((string) $row[\meta\endgame::FEN]))[0] ?? "";
            $w = 0;
            $b = 0;
            for ($i = 0; $i < strlen($epd); $i++) {
                if (str_contains("KQRBNP", $epd[$i])) $w++;
                elseif (str_contains("kqrbnp", $epd[$i])) $b++;
            }
            if ((int) $row[\meta\endgame::WHITEP] !== $w || (int) $row[\meta\endgame::BLACKP] !== $b) {
                $mismatchRows[] = ["pid" => (int) $row[\meta\endgame::PID], "db" => "{$row[\meta\endgame::WHITEP]}+{$row[\meta\endgame::BLACKP]}", "fen" => "{$w}+{$b}"];
                if (count($mismatchRows) >= 30) break;
            }
        }

        $html = "<h3>Database Integrity Check</h3>";
        if ($recounted > 0) {
            $html .= "<p><strong>Recounted piece counts for {$recounted} studies.</strong></p>";
        }
        $html .= "<h4>Studies missing PGN in <code>raw</code> (" . count($missingRaw) . ")</h4>";
        if (!empty($missingRaw)) {
            $html .= "<ul>";
            foreach ($missingRaw as $r) {
                $pid = (int) $r[\meta\endgame::PID];
                $a = htmlspecialchars((string) $r[\meta\endgame::AUTHOR], ENT_QUOTES, "UTF-8");
                $html .= "<li><a href='<!-- www -->/?pid={$pid}'>PID {$pid}</a> &mdash; {$a}</li>";
            }
            $html .= "</ul>";
        } else {
            $html .= "<p>None.</p>";
        }

        $html .= "<h4>Orphaned rows in <code>raw</code> (" . count($orphanRaw) . ")</h4>";
        $html .= !empty($orphanRaw)
            ? "<p>IDs: " . implode(", ", array_map(static fn($r) => (int) $r["id"], $orphanRaw)) . "</p>"
            : "<p>None.</p>";

        $html .= "<h4>Piece count mismatches (" . count($mismatchRows) . " sampled)</h4>";
        if (!empty($mismatchRows)) {
            $html .= "<form method='post' action='<!-- www -->/?q=analytics/integrity/recount'><!-- csrf-input --><input type='submit' value='Recount all piece counts' class='button-primary' /></form>";
            $html .= "<ul>";
            foreach ($mismatchRows as $m) {
                $html .= "<li>PID {$m['pid']}: DB {$m['db']} vs FEN {$m['fen']}</li>";
            }
            $html .= "</ul>";
        } else {
            $html .= "<p>All sampled piece counts match FEN.</p>";
        }
        return $html;
    }

    #[\mc\route("analytics/merge-authors")]
    public static function mergeAuthors(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::UPDATE)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }

        $db = \config::$db;
        $message = "";
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
            user::require_csrf();
            $from = trim((string) (filter_input(INPUT_POST, "from_author") ?? ""));
            $to = trim((string) (filter_input(INPUT_POST, "to_author") ?? ""));
            if ($from !== "" && $to !== "" && $from !== $to) {
                $countRows = $db->exec_prepared("SELECT COUNT(*) AS cnt FROM endgame WHERE author = ?", [$from]);
                $affected = (int) ($countRows[0]["cnt"] ?? 0);
                $db->exec_prepared("UPDATE endgame SET author = ? WHERE author = ?", [$to, $from], false);
                $message = "<p><strong>Merged {$affected} studies</strong> from <code>" . htmlspecialchars($from, ENT_QUOTES, "UTF-8") . "</code> to <code>" . htmlspecialchars($to, ENT_QUOTES, "UTF-8") . "</code>.</p>";
            }
        }

        $topAuthors = $db->query_sql("SELECT author, COUNT(*) AS cnt FROM endgame GROUP BY author ORDER BY cnt DESC LIMIT 25");
        $html = "<h3>Merge Authors</h3>{$message}";
        $html .= "<form method='post' action='<!-- www -->/?q=analytics/merge-authors'><!-- csrf-input -->";
        $html .= "<div class='row'><div class='five columns'><label for='from_author'>From author (exact)</label><input type='text' id='from_author' name='from_author' class='u-full-width' required /></div>";
        $html .= "<div class='five columns'><label for='to_author'>To canonical author</label><input type='text' id='to_author' name='to_author' class='u-full-width' required /></div>";
        $html .= "<div class='two columns'><label>&nbsp;</label><input type='submit' value='Merge' class='button-primary u-full-width' /></div></div></form>";

        $html .= "<h4>Top 25 authors by study count</h4><table class='u-full-width'><tr><th>Author</th><th>Studies</th></tr>";
        foreach ($topAuthors as $row) {
            $a = htmlspecialchars((string) $row["author"], ENT_QUOTES, "UTF-8");
            $cnt = (int) $row["cnt"];
            $html .= "<tr><td>{$a}</td><td>{$cnt}</td></tr>";
        }
        $html .= "</table>";
        return $html;
    }

    #[\mc\route("analytics/insights")]
    public static function insights(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::READ)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }

        $db = \config::$db;
        $totalStudies = (int) ($db->query_sql("SELECT COUNT(*) AS cnt FROM endgame")[0]["cnt"] ?? 0);
        $cookedCount = (int) ($db->query_sql("SELECT COUNT(*) AS cnt FROM endgame WHERE cook = 1")[0]["cnt"] ?? 0);
        $stipRows = $db->query_sql("SELECT stipulation, COUNT(*) AS cnt FROM endgame GROUP BY stipulation ORDER BY cnt DESC LIMIT 8");
        $decadeRows = $db->query_sql("SELECT substr(date, 1, 3) || '0s' AS decade, COUNT(*) AS cnt FROM endgame WHERE date GLOB '[0-9][0-9][0-9][0-9]*' GROUP BY decade ORDER BY decade ASC");

        $totalQueries = (int) ($db->query_sql("SELECT COUNT(*) AS cnt FROM statistic")[0]["cnt"] ?? 0);
        $uniqueIps = (int) ($db->query_sql("SELECT COUNT(DISTINCT ip) AS cnt FROM statistic")[0]["cnt"] ?? 0);
        $recentCutoff = time() - 7 * 86400;
        $weeklyQueries = (int) ($db->exec_prepared("SELECT COUNT(*) AS cnt FROM statistic WHERE time >= ?", [$recentCutoff])[0]["cnt"] ?? 0);

        $rawQueries = $db->query_sql("SELECT query FROM statistic ORDER BY id DESC LIMIT 1000");
        $authorSearchCounts = [];
        foreach ($rawQueries as $rq) {
            if (preg_match("/author LIKE '%([^%']+)%'/i", (string) $rq["query"], $m)) {
                $term = trim($m[1]);
                if ($term !== "") {
                    $authorSearchCounts[$term] = ($authorSearchCounts[$term] ?? 0) + 1;
                }
            }
        }
        arsort($authorSearchCounts);
        $topSearchTerms = array_slice($authorSearchCounts, 0, 10, true);

        $cookedPct = $totalStudies > 0 ? round(($cookedCount / $totalStudies) * 100, 1) : 0;
        $html = "<h3>Database &amp; Search Insights</h3>";
        $html .= "<div class='row'><div class='six columns'><h4>Database Summary</h4><ul>";
        $html .= "<li>Total studies: <strong>" . number_format($totalStudies, 0, ".", " ") . "</strong></li>";
        $html .= "<li>Cooked studies: <strong>" . number_format($cookedCount, 0, ".", " ") . "</strong> ({$cookedPct}%)</li>";
        $html .= "</ul><h5>By Stipulation</h5><ul>";
        foreach ($stipRows as $s) {
            $st = htmlspecialchars((string) $s["stipulation"], ENT_QUOTES, "UTF-8");
            $html .= "<li>{$st}: " . (int) $s["cnt"] . "</li>";
        }
        $html .= "</ul></div>";

        $html .= "<div class='six columns'><h4>Search Activity</h4><ul>";
        $html .= "<li>Total queries logged: <strong>" . number_format($totalQueries, 0, ".", " ") . "</strong></li>";
        $html .= "<li>Queries (last 7 days): <strong>" . number_format($weeklyQueries, 0, ".", " ") . "</strong></li>";
        $html .= "<li>Unique visitor IPs: <strong>" . number_format($uniqueIps, 0, ".", " ") . "</strong></li>";
        $html .= "</ul><h5>Top Searched Authors (recent 1,000)</h5><ul>";
        foreach ($topSearchTerms as $term => $cnt) {
            $tEsc = htmlspecialchars($term, ENT_QUOTES, "UTF-8");
            $html .= "<li>{$tEsc}: {$cnt}</li>";
        }
        $html .= "</ul></div></div>";

        $html .= "<h4>Studies by Decade</h4><table class='u-full-width'><tr><th>Decade</th><th>Studies</th></tr>";
        foreach ($decadeRows as $d) {
            $dec = htmlspecialchars((string) $d["decade"], ENT_QUOTES, "UTF-8");
            $html .= "<tr><td>{$dec}</td><td>" . (int) $d["cnt"] . "</td></tr>";
        }
        $html .= "</table>";
        return $html;
    }

    #[\mc\route("analytics/links")]
    public static function links(array $params)
    {
        if (
            !user::has_capability(ANALYTICS_CAPABILITY::READ)
            && !user::has_capability(ENDGAME_CAPABILITIY::UPDATE)
            && !user::has_role("admin")
        ) {
            header("location:" . \config::www);
            exit();
        }
        $html = "<h3>Analytics links</h3>";
        $links = \mc\router::getRoutes();
        $links = array_filter($links, function($link) {
            return str_contains($link, "analytics");
        });
        $html .= "<div class='row'><div class='twelve columns'><ul>";
        foreach ($links as $link) {
            $html .= "<li><a href='<!-- www -->/?q={$link}'>{$link}</a></li>";
        }
        $html .= "</ul></div></div>";
        return $html;
    }
}
