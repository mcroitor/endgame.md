<?php

include_once __DIR__ . "/chessgame.php";

class PGNParser
{

    protected $pgnData;
    protected $games = [];

    public function __construct($pgnSource)
    {
        $this->loadPGN($pgnSource);
        $this->parsePGN();
    }

    protected function loadPGN($pgnSource)
    {
        if (file_exists($pgnSource)) {
            $this->pgnData = file_get_contents($pgnSource);
        } else {
            $this->pgnData = $pgnSource;
        }
        // normalize line endings
        $this->pgnData = str_replace("\r\n", "\n", $this->pgnData);
    }

    protected function parsePGN()
    {
        $gamesData = explode("[Event ", $this->pgnData);
        \mc\logger::stderr()->info("Parse file: " . count($gamesData) . " games found in file");
        foreach ($gamesData as $gameData) {
            if (empty($gameData)) {
                continue;
            }
            $this->games[] = new ChessGame("[Event " . $gameData);
        }
    }

    public function getGames()
    {
        return $this->games;
    }
}