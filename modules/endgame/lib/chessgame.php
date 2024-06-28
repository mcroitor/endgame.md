<?php

class ChessGame {
    public const EVENT = "event";
    public const SITE = "site";
    public const DATE = "date";
    public const WHITE = "white";
    public const BLACK = "black";
    public const RESULT = "result";
    public const FEN = "fen";

    private const REGEX = [
        self::EVENT => "/\[Event \"(.*)\"\]/",
        self::SITE => "/\[Site \"(.*)\"\]/",
        self::DATE => "/\[Date \"(.*)\"\]/",
        self::WHITE => "/\[White \"(.*)\"\]/",
        self::BLACK => "/\[Black \"(.*)\"\]/",
        self::RESULT => "/\[Result \"(.*)\"\]/",
        self::FEN => "/\[FEN \"(.*)\"\]/",
    ];

    private $info = [
        self::EVENT => "",
        self::SITE => "",
        self::DATE => "",
        self::WHITE => "",
        self::BLACK => "",
        self::RESULT => "",
        self::FEN => ""
    ];

    private $moves = "";
    private $raw = "";

    /**
     * Parse PGN data and extract game information
     * 
     * @param string $gameData PGN data for a single game
     */
    public function __construct($gameData) {
        $this->raw = $gameData;
        foreach (self::REGEX as $tag => $regex) {
            $matches = [];
            if(preg_match($regex, $gameData, $matches)) {
                $this->info[$tag] = $matches[1] ?? "?";
                $this->info[$tag] = trim($this->info[$tag]);
            }
            else {
                \mc\logger::stderr()->error("Failed to match tag: $tag");
                \mc\logger::stderr()->error("Regex: $regex");
            }
        }
    }

    public function getInfo() {
        return $this->info;
    }

    public function getMoves() {
        return $this->moves;
    }

    public function getRaw() {
        return $this->raw;
    }
}