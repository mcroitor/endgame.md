<?php

use mc\user;

include_once __DIR__ . "/capabilities/analytics_capabilities.php";

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

        $db = new \mc\sql\database(config::dsn);
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
        $db = new \mc\sql\database(config::dsn);
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
        $db = new \mc\sql\database(config::dsn);
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
        if(!user::has_capability(ENDGAME_CAPABILITIY::UPDATE)) {
            header("location:" . config::www);
            exit();
        }
        $brokenAuthors = self::detectBrokenAuthors();
        
        $html = "<h3>Broken authors</h3>";
        $html .= "<table class='u-full-width'>";
        $html .= "<tr><th>PID</th><th>Author</th><th>Edit</th><th>Remove</th></tr>";
        foreach ($brokenAuthors as $endgame) {
            $html .= "<tr>";
            $html .= "<td>" . $endgame[\meta\endgame::PID] . "</td>";
            $html .= "<td>" . $endgame[\meta\endgame::AUTHOR] . "</td>";
            $html .= "<td><a href='<!-- www -->/?q=analytics/author/edit/" . $endgame[\meta\endgame::PID] . "'>Edit</a></td>";
            $html .= "<td><a href='<!-- www -->/?q=analytics/author/remove/" . $endgame[\meta\endgame::PID] . "'>Remove</a></td>";
            $html .= "</tr>";
        }
        $html .= "</table>";
        return $html;
    }

    #[\mc\route("analytics/author")]
    public static function author(array $params)
    {
        if(!user::has_capability(ENDGAME_CAPABILITIY::UPDATE)) {
            header("location:" . config::www);
            exit();
        }
        $action = $params[0];
        $pid = $params[1] ?? 0;
        if($pid === 0) {
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
        $db = new \mc\sql\database(config::dsn);
        $endgame = $db->select(
            \meta\endgame::__name__,
            ["*"],
            [\meta\endgame::PID => $pid]
        );
        if(count($endgame) === 0) {
            return "Endgame not found";
        }
        $endgame = $endgame[0];
        $html = "<h3>Edit author</h3>";
        $html .= "<img src='<!-- www -->/modules/diagram/?fen={$endgame[\meta\endgame::FEN]}&size=32'>";
        $html .= "<form method='post' action='<!-- www -->/?q=analytics/author/update/{$pid}'>";
        $html .= "<label for='author'>Author</label>";
        $html .= "<input type='text' name='author' value='" . $endgame[\meta\endgame::AUTHOR] . "'>";
        $html .= "<input type='submit' value='Save'>";
        $html .= "</form>";
        return $html;
    }

    private static function removeAuthor($pid)
    {
        $db = new \mc\sql\database(config::dsn);
        $db->delete(
            \meta\endgame::__name__,
            [\meta\endgame::PID => $pid]);
        header("location:" . config::www . "/?q=analytics/authors");
        exit();
    }

    private static function updateAuthor($pid)
    {
        $author = filter_input(
            INPUT_POST, 
            "author", 
            FILTER_DEFAULT,
            ["options" => ["default" => ""]]);
        $db = new \mc\sql\database(config::dsn);
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
        header("location:" . config::www . "/?q=analytics/authors");
        exit();
    }

    #[\mc\route("analytics/links")]
    public static function links(array $params)
    {
        $html = "<h3>Analytics links</h3>";
        $links = \mc\router::getRoutes();
        $links = array_filter($links, function($link) {
            return str_contains($link, "analytics");
        });
        $html .= "<div class='row'><div class='twelve columns'><ul>";
        foreach ($links as $link) {
            $html .= "<li><a href='<!-- www -->/?q={$link}'>{$link}</a></li>";
        }
        $html .= "</div></div></ul>";
        return $html;
    }
}
