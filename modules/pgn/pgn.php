<?php

use mc\sql\database;
use mc\user;

class pgn
{
    protected const PGN_CREATE = "pgn::create";
    protected const PGN_UPLOAD = "pgn::upload";
    protected const PGN_UPDATE = "pgn::update";
    protected const PGN_DELETE = "pgn::delete";


    protected const event_regex = "\\[Event \"[\\w\\s]*\"\\]";
    protected const white_regex = "\\[White \"[\\w\\s]*\"\\]";
    protected const black_regex = "\\[Black \"[\\w\\s]*\"\\]";
    protected const result_regex = "\\[Result \"[\\w\\s]*\"\\]";
    protected const fen_regex = "\\[FEN \"[\\w/\\s\\-]+\"\\]";
    protected const unknown_regex = "\\[[\\w\"/\\s\\-.]*\"\\]";

    public static function get(array $params)
    {
        $pgnId = empty($params) ? 1 : (int)$params[0];
        $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "raw");
        return $crud->select($pgnId);
    }

    public static function create(array $params)
    {
        if (!user::has_capability(self::PGN_CREATE)) {
            header("location:" . config::www);
            exit();
        }
        return null;
    }

    public static function upload(array $params)
    {
        if (!user::has_capability(self::PGN_UPLOAD)) {
            header("location:" . config::www);
            exit();
        }
        if (empty($_FILES["userfile"])) {
            // rewrite, return to the page for file uploading
            header("location:" . config::www);
            exit();
        }
        $filename = $_FILES['userfile']['name'];
        $pgn = file_get_contents($_FILES['userfile']['tmp_name']) or die("file opening error");
        $games = self::explode_games($pgn);
        $db = new database(config::dsn);

        foreach ($games as $game) {
            $headers = self::get_game_header($game);
            $data = [
                \meta\endgame::SOURCE => $headers["event"],
                \meta\endgame::AUTHOR => $headers["white"],
                \meta\endgame::DATE => $headers["date"],
                \meta\endgame::STIPULATION => $headers["result"],
                \meta\endgame::FEN => $headers["fen"],
                \meta\endgame::AWARD => "",
                \meta\endgame::WHITEP => "",
                \meta\endgame::BLACKP => "",
                \meta\endgame::COMMENTARY => "",
                \meta\endgame::COOK => "",
                \meta\endgame::PIECE_PATTERN => "",
                \meta\endgame::THEME => "unknown",
            ];
            $db->insert("endgame", $data);
            $db->insert("raw", ["data" => $game]);
        }
        $db->insert("changes", [
            "nr_games" => count($games),
            "filename" => $filename,
            "date" => date("Y-M-d - h:i:s A")
        ]);
        return null;
    }

    public static function list(array $params)
    {
        $offset = isset($params[0]) ? (int)$params[0] : 0;
        $limit = isset($params[1]) ? (int)$params[1] : 20;
        $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), \meta\endgame::__name__);
        return $crud->all($offset, $limit);
    }

    private static function explode_games(string $pgn): array
    {
        $games = explode(PHP_EOL . "[Event ", $pgn);
        $games = array_map(function ($game) {
            return "[Event " . $game;
        }, $games);
        return $games;
    }

    private static function get_game_header(string $game): array
    {
        $matches = [];

        $header = [];

        preg_match(self::event_regex, $game, $matches);
        $header["event"] = addslashes(trim($matches[0], "\"'"));
        preg_match(self::white_regex, $game, $matches);
        $header["white"] = addslashes(trim($matches[0], "\"'"));
        preg_match(self::black_regex, $game, $matches);
        $header["black"] = $matches[0];
        preg_match(self::result_regex, $game, $matches);
        $header["result"] = $matches[0];
        preg_match(self::fen_regex, $game, $matches);
        $header["fen"] = $matches[0];
        return $header;
    }
}
