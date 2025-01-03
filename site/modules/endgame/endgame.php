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
        header('Content-Type: text/txt');
        header("Content-Disposition: attachment; filename=endgame_{$pgnId}.pgn");
        echo json_encode($pgn);
        exit();
    }

    #[\mc\route('endgame/new')]
    public static function newForm()
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }
        \config::$logger->warn("not implemented yet");
        $template = \facade::template("endgame-form.template.php", self::TEMPLATES_DIR);
        return $template->fill(["path" => \config::www . "/modules/articles"])->value();
    }

    #[\mc\route('endgame/create')]
    public static function create(array $params)
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . \config::www);
            exit();
        }
        \config::$logger->warn("not implemented yet");
        return null;
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
            $test_fen = $db->select(
                "endgame",
                ["pid, fen"],
                ["fen LIKE '%{$headers[\ChessGame::FEN]}%'"]
            );
            if (!empty($test_fen)) {
                $endgame_exists[] = $test_fen[0];
                continue;
            }

            $data = [
                \meta\endgame::SOURCE => $headers[\ChessGame::EVENT],
                \meta\endgame::AUTHOR => $headers[\ChessGame::WHITE],
                \meta\endgame::DATE => $headers[\ChessGame::DATE],
                \meta\endgame::STIPULATION => $headers[\ChessGame::RESULT],
                \meta\endgame::FEN => $headers[\ChessGame::FEN],
                \meta\endgame::AWARD => "",
                \meta\endgame::WHITEP => "",
                \meta\endgame::BLACKP => "",
                \meta\endgame::COMMENTARY => "",
                \meta\endgame::COOK => "",
                \meta\endgame::PIECE_PATTERN => "",
                \meta\endgame::THEME => "unknown",
            ];

            $db->insert("endgame", $data);
            $db->insert("raw", ["data" => $game->getRaw()]);
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
        return $template->fill(["last-date" => date("Y")])->value();
    }
}
