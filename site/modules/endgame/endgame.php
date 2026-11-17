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
