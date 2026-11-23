<?php

namespace modules\endgame;

include_once __DIR__ . '/capabilities/endgame_capabilities.php';
include_once __DIR__ . '/lib/pgnparser.php';

use modules\endgame\capabilities\ENDGAME_CAPABILITIY;
use mc\user;

class endgame
{
    public const MODULE_DIR = __DIR__;
    public const TEMPLATES_DIR = self::MODULE_DIR . "/templates/";

    #[\mc\route("pgn")]
    public static function get(array $params)
    {
        $pgnId = empty($params) ? 1 : (int)$params[0];
        $crud = new \mc\sql\crud(\config::$db, "raw");
        $pgn = $crud->select($pgnId);
        if (empty($pgn)) {
            header("location:" . \config::www);
            exit();
        }
        header('Content-Type: text/plain');
        header("Content-Disposition: attachment; filename=endgame_{$pgnId}.pgn");
        echo $pgn["data"] ?? "";
        exit();
    }

    #[\mc\route('endgame/new')]
    public static function newForm()
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }
        $template = \facade::template("endgame-form.template.php", self::TEMPLATES_DIR);
        return $template->fill(["path" => \config::www . "/modules/articles"])->value();
    }

    private static function analyzeFen(string $fen): array
    {
        $epd = explode(" ", trim($fen))[0] ?? "";
        $whitePieces = 0;
        $blackPieces = 0;
        $pieceStat = [
            "Q" => 0, "R" => 0, "B" => 0, "N" => 0, "P" => 0,
            "q" => 0, "r" => 0, "b" => 0, "n" => 0, "p" => 0,
        ];
        for ($i = 0; $i < strlen($epd); $i++) {
            $ch = $epd[$i];
            if (str_contains("KQRBNP", $ch)) {
                $whitePieces++;
            } elseif (str_contains("kqrbnp", $ch)) {
                $blackPieces++;
            }
            if (isset($pieceStat[$ch])) {
                $pieceStat[$ch]++;
            }
        }
        $piecePattern = "";
        foreach ($pieceStat as $piece => $count) {
            $piecePattern .= str_repeat($piece, $count);
        }
        return [
            "whitep" => $whitePieces,
            "blackp" => $blackPieces,
            "piece_pattern" => $piecePattern,
        ];
    }

    private static function canEdit(): bool
    {
        return user::has_capability(ENDGAME_CAPABILITIY::UPDATE) || user::has_role("admin");
    }

    private static function canDelete(): bool
    {
        return user::has_capability(ENDGAME_CAPABILITIY::DELETE) || user::has_role("admin");
    }

    private static function buildRawPgn(
        string $source,
        string $date,
        string $author,
        string $stipulation,
        string $fen,
        string $moves = ""
    ): string {
        $resultTag = $stipulation === "White wins"
            ? "1-0"
            : ($stipulation === "Black wins" ? "0-1" : ($stipulation === "Draw" ? "1/2-1/2" : "*"));
        return sprintf(
            "[Event \"%s\"]\n[Site \"?\"]\n[Date \"%s\"]\n[Round \"-\"]\n[White \"%s\"]\n[Black \"?\"]\n[Result \"%s\"]\n[FEN \"%s\"]\n\n%s",
            str_replace('"', "'", $source !== "" ? $source : "endgame.md"),
            str_replace('"', "'", $date !== "" ? $date : date("Y")),
            str_replace('"', "'", $author),
            $resultTag,
            str_replace('"', "'", $fen),
            $moves !== "" ? $moves : $resultTag
        );
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }

    #[\mc\route('endgame/edit')]
    public static function editForm(array $params)
    {
        if (!self::canEdit()) {
            header("location:" . \config::www);
            exit();
        }
        $pid = isset($params[0]) ? (int) $params[0] : 0;
        if ($pid <= 0) {
            return '<div class="status-message status-error">Invalid study PID.</div>';
        }

        $db = \config::$db;
        $rows = $db->exec_prepared("SELECT * FROM endgame WHERE pid = ?", [$pid]);
        if (empty($rows)) {
            return '<div class="status-message status-error">Study not found.</div>';
        }
        $study = $rows[0];

        $rawRows = $db->exec_prepared("SELECT data FROM raw WHERE id = ?", [$pid]);
        $rawPgn = (string) ($rawRows[0]["data"] ?? "");
        if ($rawPgn === "") {
            $rawPgn = self::buildRawPgn(
                (string) ($study[\meta\endgame::SOURCE] ?? ""),
                (string) ($study[\meta\endgame::DATE] ?? ""),
                (string) ($study[\meta\endgame::AUTHOR] ?? ""),
                (string) ($study[\meta\endgame::STIPULATION] ?? "*"),
                (string) ($study[\meta\endgame::FEN] ?? "")
            );
        }

        $currentStip = (string) ($study[\meta\endgame::STIPULATION] ?? "*");
        $stipOptionsMap = [
            "White wins" => "White wins",
            "Draw" => "Draw",
            "Black wins" => "Black wins",
            "*" => "Not specified",
        ];
        if (!isset($stipOptionsMap[$currentStip]) && $currentStip !== "") {
            $stipOptionsMap[$currentStip] = $currentStip;
        }
        $stipOptionsHtml = "";
        foreach ($stipOptionsMap as $val => $label) {
            $selected = $val === $currentStip ? " selected" : "";
            $stipOptionsHtml .= '<option value="' . self::esc($val) . '"' . $selected . '>' . self::esc($label) . '</option>';
        }

        $errorCode = (string) ($_GET["error"] ?? "");
        $errorBanner = "";
        if ($errorCode === "missing_required") {
            $errorBanner = '<div class="status-message status-error">FEN and Author are required fields.</div>';
        }

        $deleteSection = "";
        if (self::canDelete()) {
            $deleteSection = '<hr />'
                . '<form method="post" action="<!-- www -->/?q=endgame/delete/' . $pid . '" '
                . 'onsubmit="return confirm(\'Delete study PID ' . $pid . '? This action cannot be undone.\');">'
                . '<!-- csrf-input -->'
                . '<button type="submit" class="button" style="color:#991b1b;border-color:#fca5a5;">Delete study</button>'
                . '</form>';
        }

        $template = \facade::template("endgame-edit-form.template.php", self::TEMPLATES_DIR);
        return $template->fill([
            "pid" => (string) $pid,
            "error-banner" => $errorBanner,
            "fen" => self::esc((string) ($study[\meta\endgame::FEN] ?? "")),
            "fen-encoded" => urlencode((string) ($study[\meta\endgame::FEN] ?? "")),
            "author" => self::esc((string) ($study[\meta\endgame::AUTHOR] ?? "")),
            "date" => self::esc((string) ($study[\meta\endgame::DATE] ?? "")),
            "source" => self::esc((string) ($study[\meta\endgame::SOURCE] ?? "")),
            "award" => self::esc((string) ($study[\meta\endgame::AWARD] ?? "")),
            "stipulation-options" => $stipOptionsHtml,
            "theme" => self::esc((string) ($study[\meta\endgame::THEME] ?? "")),
            "cook-checked" => ((int) ($study[\meta\endgame::COOK] ?? 0)) === 1 ? "checked" : "",
            "commentary" => self::esc((string) ($study[\meta\endgame::COMMENTARY] ?? "")),
            "raw-pgn" => self::esc($rawPgn),
            "delete-section" => $deleteSection,
        ])->value();
    }

    #[\mc\route('endgame/update')]
    public static function update(array $params)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::canEdit()) {
            header("location:" . \config::www);
            exit();
        }

        $pid = isset($params[0]) ? (int) $params[0] : 0;
        if ($pid <= 0) {
            header("location:" . \config::www);
            exit();
        }

        $db = \config::$db;
        $existing = $db->exec_prepared("SELECT * FROM endgame WHERE pid = ?", [$pid]);
        if (empty($existing)) {
            header("location:" . \config::www);
            exit();
        }
        $oldStudy = $existing[0];

        $fen = trim((string) ($_POST["fen"] ?? ""));
        $author = trim((string) ($_POST["author"] ?? ""));
        $date = trim((string) ($_POST["date"] ?? ""));
        $source = trim((string) ($_POST["source"] ?? ""));
        $award = trim((string) ($_POST["award"] ?? ""));
        $stipulation = trim((string) ($_POST["stipulation"] ?? "*"));
        $theme = trim((string) ($_POST["theme"] ?? ""));
        $commentary = trim((string) ($_POST["commentary"] ?? ""));
        $cook = (isset($_POST["cook"]) && (string) $_POST["cook"] === "1") ? 1 : 0;
        $rawPgn = trim((string) ($_POST["raw_pgn"] ?? ""));

        $baseUrl = rtrim(\config::www, "/");
        if ($fen === "" || $author === "") {
            header("location:" . $baseUrl . "/?q=endgame/edit/" . $pid . "&error=missing_required");
            exit();
        }

        $fenMeta = self::analyzeFen($fen);
        $finalDate = $date !== "" ? $date : (string) ($oldStudy[\meta\endgame::DATE] ?? date("Y"));
        $finalStip = $stipulation !== "" ? $stipulation : "*";
        $finalTheme = $theme !== "" ? $theme : "unknown";

        $db->exec_prepared(
            "UPDATE endgame SET fen = ?, author = ?, date = ?, source = ?, award = ?, stipulation = ?, theme = ?, commentary = ?, cook = ?, whitep = ?, blackp = ?, piece_pattern = ? WHERE pid = ?",
            [
                $fen,
                $author,
                $finalDate,
                $source,
                $award,
                $finalStip,
                $finalTheme,
                $commentary,
                $cook,
                $fenMeta["whitep"],
                $fenMeta["blackp"],
                $fenMeta["piece_pattern"],
                $pid,
            ],
            false
        );

        if ($rawPgn === "") {
            $rawPgn = self::buildRawPgn($source, $finalDate, $author, $finalStip, $fen);
        } else {
            $oldFen = (string) ($oldStudy[\meta\endgame::FEN] ?? "");
            if ($oldFen !== "" && $fen !== $oldFen) {
                $escapedNewFen = str_replace('"', "'", $fen);
                $rawPgn = preg_replace_callback(
                    '/\[FEN\s+"([^"]*)"\]/i',
                    static fn(array $m) => $m[1] === $oldFen ? '[FEN "' . $escapedNewFen . '"]' : $m[0],
                    $rawPgn
                );
            }
        }

        $rawExists = $db->exec_prepared("SELECT id FROM raw WHERE id = ?", [$pid]);
        if (empty($rawExists)) {
            $db->exec_prepared("INSERT INTO raw (id, data) VALUES (?, ?)", [$pid, $rawPgn], false);
        } else {
            $db->exec_prepared("UPDATE raw SET data = ? WHERE id = ?", [$rawPgn, $pid], false);
        }

        header("location:" . $baseUrl . "/?pid=" . urlencode((string) $pid));
        exit();
    }

    #[\mc\route('endgame/delete')]
    public static function delete(array $params)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            return "Method Not Allowed";
        }
        user::require_csrf();
        if (!self::canDelete()) {
            header("location:" . \config::www);
            exit();
        }

        $pid = isset($params[0]) ? (int) $params[0] : 0;
        if ($pid > 0) {
            $db = \config::$db;
            $db->exec_prepared("DELETE FROM endgame WHERE pid = ?", [$pid], false);
            $db->exec_prepared("DELETE FROM raw WHERE id = ?", [$pid], false);
            $hasRatings = $db->exec_prepared(
                "SELECT name FROM sqlite_master WHERE type='table' AND name='endgame_ratings'"
            );
            if (!empty($hasRatings)) {
                $db->exec_prepared("DELETE FROM endgame_ratings WHERE pid = ?", [$pid], false);
            }
        }

        header("location:" . \config::www);
        exit();
    }

    #[\mc\route('endgame/create')]
    public static function create(array $params)
    {
        user::require_csrf();
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }

        $fen = trim((string) (filter_input(INPUT_POST, "fen") ?? ""));
        $author = trim((string) (filter_input(INPUT_POST, "author") ?? ""));
        $date = trim((string) (filter_input(INPUT_POST, "date") ?? ""));
        $source = trim((string) (filter_input(INPUT_POST, "source") ?? ""));
        $stipulation = trim((string) (filter_input(INPUT_POST, "stipulation") ?? "*"));
        $theme = trim((string) (filter_input(INPUT_POST, "theme") ?? ""));
        $moves = trim((string) (filter_input(INPUT_POST, "moves") ?? ""));

        if ($fen === "" || $author === "") {
            header("location:" . \config::www . "/?q=endgame/new");
            exit();
        }

        $fenMeta = self::analyzeFen($fen);
        $data = [
            \meta\endgame::SOURCE => $source,
            \meta\endgame::AUTHOR => $author,
            \meta\endgame::DATE => $date !== "" ? $date : date("Y"),
            \meta\endgame::STIPULATION => $stipulation !== "" ? $stipulation : "*",
            \meta\endgame::FEN => $fen,
            \meta\endgame::AWARD => "",
            \meta\endgame::WHITEP => $fenMeta["whitep"],
            \meta\endgame::BLACKP => $fenMeta["blackp"],
            \meta\endgame::COMMENTARY => "",
            \meta\endgame::COOK => 0,
            \meta\endgame::PIECE_PATTERN => $fenMeta["piece_pattern"],
            \meta\endgame::THEME => $theme !== "" ? $theme : "unknown",
        ];

        $db = \config::$db;
        $pid = $db->insert("endgame", $data);

        $resultTag = $stipulation === "White wins" ? "1-0" : ($stipulation === "Draw" ? "1/2-1/2" : "*");
        $rawPgn = sprintf(
            "[Event \"%s\"]\n[Site \"?\"]\n[Date \"%s\"]\n[Round \"-\"]\n[White \"%s\"]\n[Black \"?\"]\n[Result \"%s\"]\n[FEN \"%s\"]\n\n%s",
            str_replace('"', "'", $source !== "" ? $source : "endgame.md"),
            str_replace('"', "'", $data[\meta\endgame::DATE]),
            str_replace('"', "'", $author),
            $resultTag,
            str_replace('"', "'", $fen),
            $moves !== "" ? $moves : $resultTag
        );
        $db->insert("raw", ["id" => $pid, "data" => $rawPgn]);

        header("location:" . \config::www . "/?pid=" . urlencode((string) $pid));
        exit();
    }

    #[\mc\route('endgame/import')]
    public static function importForm(array $params)
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }
        $template = \facade::template("endgame-import-form.template.php", self::TEMPLATES_DIR);
        return $template->value();
    }

    #[\mc\route("pgn/upload")]
    public static function upload(array $params)
    {
        user::require_csrf();
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }
        if (empty($_FILES["userfile"])) {
            header("location:" . \config::www . "/?q=endgame/import");
            exit();
        }
        $filename = filter_var($_FILES['userfile']['name']);
        $parser = new \PGNParser(filter_var($_FILES['userfile']['tmp_name']));
        $db = \config::$db;
        $endgame_exists = [];

        $games = $parser->getGames();

        \mc\logger::stderr()->info("Endgame import: " . count($games) . " games found in file: " . $filename);

        foreach ($games as $game) {
            $headers = $game->getInfo();
            $fenEscaped = str_replace("'", "''", $headers[\ChessGame::FEN]);
            $test_fen = $db->select(
                "endgame",
                ["pid, fen"],
                ["fen LIKE '%{$fenEscaped}%'"]
            );
            if (!empty($test_fen)) {
                $endgame_exists[] = $test_fen[0];
                continue;
            }

            $epd = explode(" ", $headers[\ChessGame::FEN])[0] ?? "";
            $whitePieces = 0;
            $blackPieces = 0;
            $pieceStat = [
                "Q" => 0, "R" => 0, "B" => 0, "N" => 0, "P" => 0,
                "q" => 0, "r" => 0, "b" => 0, "n" => 0, "p" => 0,
            ];
            for ($i = 0; $i < strlen($epd); $i++) {
                $ch = $epd[$i];
                if (str_contains("KQRBNP", $ch)) {
                    $whitePieces++;
                } elseif (str_contains("kqrbnp", $ch)) {
                    $blackPieces++;
                }
                if (isset($pieceStat[$ch])) {
                    $pieceStat[$ch]++;
                }
            }
            $piecePattern = "";
            foreach ($pieceStat as $piece => $count) {
                $piecePattern .= str_repeat($piece, $count);
            }

            $data = [
                \meta\endgame::SOURCE => $headers[\ChessGame::EVENT],
                \meta\endgame::AUTHOR => $headers[\ChessGame::WHITE],
                \meta\endgame::DATE => $headers[\ChessGame::DATE],
                \meta\endgame::STIPULATION => $headers[\ChessGame::RESULT],
                \meta\endgame::FEN => $headers[\ChessGame::FEN],
                \meta\endgame::AWARD => "",
                \meta\endgame::WHITEP => $whitePieces,
                \meta\endgame::BLACKP => $blackPieces,
                \meta\endgame::COMMENTARY => "",
                \meta\endgame::COOK => 0,
                \meta\endgame::PIECE_PATTERN => $piecePattern,
                \meta\endgame::THEME => "unknown",
            ];

            $pid = $db->insert("endgame", $data);
            $db->insert("raw", ["id" => $pid, "data" => $game->getRaw()]);
        }
        $db->insert("changes", [
            "nr_games" => count($parser->getGames()) - count($endgame_exists),
            "filename" => $filename,
            "date" => date("Y-M-d - h:i:s A")
        ]);

        \mc\logger::stderr()->info("Endgame import: " . count($endgame_exists) . " games already exists in the database");
        $info = "<h3>Endgame import results</h3>";
        $info .= "<ul>";
        $info .= "<li>" . count($games) . " games found in file: " . $filename . "</li>";
        $info .= "<li>" . count($endgame_exists) . " games already exists in the database</li>";
        $info .= "<li>" . (count($games) - count($endgame_exists)) . " games imported</li>";
        $info .= "</ul>";
        return $info;
    }

    public static function list(array $params)
    {
        $offset = isset($params[0]) ? (int)$params[0] : 0;
        $limit = isset($params[1]) ? (int)$params[1] : 20;
        $crud = new \mc\sql\crud(\config::$db, \meta\endgame::__name__);
        return $crud->all($offset, $limit);
    }

    #[\mc\route("/")]
    public static function search_form()
    {
        $template = \facade::template("endgame-search-form.template.php", self::TEMPLATES_DIR);
        return $template->fill([
            "last-date" => date("Y"),
        ])->value();
    }
}
