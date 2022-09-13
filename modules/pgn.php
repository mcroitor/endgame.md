<?php

class Pgn {
    protected const EVENT = "event";
    protected const SITE = "site";
    protected const DATE = "date";
    protected const ROUND = "round";
    protected const WHITE = "white";
    protected const BLACK = "black";
    protected const RESULT = "result";
    protected const FEN = "fen";
    protected const MOVES = "moves";

    protected const PATTERNS = [
        self::EVENT => "/\[Event \"(.*?)\"\]/",
        self::SITE => "/\[Site \"(.*?)\"\]/",
        self::DATE => "/\[Date \"(.*?)\"\]/",
        self::ROUND => "/\[Round \"(.*?)\"\]/",
        self::WHITE => "/\[White \"(.*?)\"\]/",
        self::BLACK => "/\[Black \"(.*?)\"\]/",
        self::RESULT => "/\[Result \"(.*?)\"\]/",
        self::FEN => "/\[FEN \"(.*?)\"\]/",
        self::MOVES => "/\[(.*?)\]/",
    ];


    protected $event;
    protected $site;
    protected $date;
    protected $round;
    protected $white;
    protected $black;
    protected $result;
    protected $fen;
    protected $moves;

    public function __construct(string $game) {
        $this->event = "?";
        $this->site = "?";
        $this->date = "?";
        $this->round = "?";
        $this->white = "?";
        $this->black = "?";
        $this->result = "?";
        $this->fen = "8/8/8/8/8/8/8/8 w - - 0 1";
        $this->moves = "";

        $this->parse($game);
    }

    public function parse(string $game) {
        $matches = [];
        foreach (self::PATTERNS as $key => $pattern) {
            preg_match($pattern, $game, $matches);
            $this->$key = $matches[1];
        }
    }

    public function getEvent() {
        return $this->event;
    }

    public function getSite() {
        return $this->site;
    }

    public function getDate() {
        return $this->date;
    }

    public function getRound() {
        return $this->round;
    }

    public function getWhite() {
        return $this->white;
    }

    public function getBlack() {
        return $this->black;
    }

    public function getResult() {
        return $this->result;
    }

    public function getMoves() {
        return $this->moves;
    }

    public function getFen() {
        return $this->fen;
    }

    public function getPgn() {
        $pgn = "";
        $pgn .= "[Event \"" . $this->event . "\"]" . PHP_EOL;
        $pgn .= "[Site \"" . $this->site . "\"]" . PHP_EOL;
        $pgn .= "[Date \"" . $this->date . "\"]" . PHP_EOL;
        $pgn .= "[Round \"" . $this->round . "\"]" . PHP_EOL;
        $pgn .= "[White \"" . $this->white . "\"]" . PHP_EOL;
        $pgn .= "[Black \"" . $this->black . "\"]" . PHP_EOL;
        $pgn .= "[Result \"" . $this->result . "\"]" . PHP_EOL;
        $pgn .= "[WhiteElo \"" . $this->whiteElo . "\"]" . PHP_EOL;
        $pgn .= "[BlackElo \"" . $this->blackElo . "\"]" . PHP_EOL;
        $pgn .= "[FEN \"" . $this->fen . "\"]" . PHP_EOL;
        $pgn .= PHP_EOL . $this->moves . PHP_EOL;

        return $pgn;
    }   
}
