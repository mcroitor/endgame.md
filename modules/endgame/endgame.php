<?php

include_once __DIR__ . '/capabilities/endgame_capabilities.php';

use mc\sql\database;
use mc\user;

class endgame
{
    public const MODULE_DIR = __DIR__;
    public const TEMPLATES_DIR = self::MODULE_DIR . "/templates/";

    protected const event_regex = "\\[Event \"[\\w\\s]*\"\\]";
    protected const white_regex = "\\[White \"[\\w\\s]*\"\\]";
    protected const black_regex = "\\[Black \"[\\w\\s]*\"\\]";
    protected const result_regex = "\\[Result \"[\\w\\s]*\"\\]";
    protected const fen_regex = "\\[FEN \"[\\w/\\s\\-]+\"\\]";
    protected const unknown_regex = "\\[[\\w\"/\\s\\-.]*\"\\]";

    #[\mc\route("pgn")]
    public static function get(array $params)
    {
        $pgnId = empty($params) ? 1 : (int)$params[0];
        $crud = new \mc\sql\crud(new \mc\sql\database(config::dsn), "raw");
        $pgn = $crud->select($pgnId);
        if(empty($pgn)) {
            header("location:" . config::www);
            exit();
        }
        header('Content-Type: text/txt');
        header("Content-Disposition: attachment; filename=endgame_{$pgnId}.pgn");
        echo json_encode($pgn);
        exit();
    }

    #[\mc\route('endgame/new')]
    public static function newForm() {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . config::www);
            exit();
        }
        $template = file_get_contents(self::TEMPLATES_DIR . "/endgame-form.template.php");
        $template = new \mc\template($template, ["prefix" => "<!-- ", "suffix" => " -->"]);
        return $template->fill(["path" => config::www . "/modules/articles"])->value();

    }

    #[\mc\route('endgame/create')]
    public static function create(array $params)
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . config::www);
            exit();
        }
        return null;
    }

    #[\mc\route('endgame/import')]
    public static function importForm(array $params) {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . config::www);
            exit();
        }
        $template = file_get_contents(self::TEMPLATES_DIR . "/endgame-import-form.template.php");
        $template = new \mc\template($template, ["prefix" => "<!-- ", "suffix" => " -->"]);
        return $template->value();

    }

    #[\mc\route("pgn/upload")]
    public static function upload(array $params)
    {
        if (!user::has_capability(ENDGAME_CAPABILITIY::CREATE)) {
            header("location:" . config::www);
            exit();
        }
        if (empty($_FILES["userfile"])) {
            header("location:" . config::www . "/?q=endgame/import");
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

    #[\mc\route("/")]
    public static function search_form() {
        $template = file_get_contents(self::TEMPLATES_DIR . "/endgame-search-form.template.php");
        $template = new \mc\template($template, ["prefix" => "<!-- ", "suffix" => " -->"]);
        return $template->value();
    }
}
