<?php

class ChessPosition {

    var $fen;
    var $board;
    var $fonsize;
    var $style;
    var $author;
    var $stipulation;
    var $solution;
    var $source;
    var $date;
    var $award;

    public function decodeJSON(string $str) {
        $col = ["a" => 1, "b" => 2, "c" => 3, "d" => 4, "e" => 5, "f" => 6, "g" => 7, "h" => 8];
        $obj = json_decode($str);
        $this->author = isset($obj->authors) ? implode("; ", $obj->authors) : "?";
        $this->stipulation = $obj->stipulation;
        $this->solution = isset($obj->solution) ? $obj->solution : "?";
        $this->source = isset($obj->source) ? $obj->source : "?";
        $this->date = isset($obj->date) ? $obj->date : "????.??.??";
        $this->award = isset($obj->distinction) ? $obj->distinction : "?";
        $board = ["1222222223",
            "4*+*+*+*+5",
            "4+*+*+*+*5",
            "4*+*+*+*+5",
            "4+*+*+*+*5",
            "4*+*+*+*+5",
            "4+*+*+*+*5",
            "4*+*+*+*+5",
            "4+*+*+*+*5",
        ];
        $board[] = ($this->style == "alpha") ? "6777777778" : "7888888889";
        foreach ($obj->algebraic->white as $wpiece) {
            $r = (int) $col[$wpiece[1]];
            $c = 9 - (int) $wpiece[2];
            switch ($wpiece[0]) {
                case "K": $piece = ($c % 2 == $r % 2) ? "k" : "K";
                    break;
                case "Q": $piece = ($c % 2 == $r % 2) ? "q" : "Q";
                    break;
                case "R": $piece = ($c % 2 == $r % 2) ? "r" : "R";
                    break;
                case "B": $piece = ($c % 2 == $r % 2) ? "b" : "B";
                    break;
                case "S":
                case "N":
                    if ($this->style == "alpha")
                        $piece = ($c % 2 == $r % 2) ? "h" : "H";
                    else
                        $piece = ($c % 2 == $r % 2) ? "n" : "N";
                    break;
                case "P": $piece = ($c % 2 == $r % 2) ? "p" : "P";
                    break;
            }
            $board[$c][$r] = $piece;
        }
        foreach ($obj->algebraic->black as $bpiece) {
            $r = (int) $col[$bpiece[1]];
            $c = 9 - (int) $bpiece[2];
            switch ($bpiece[0]) {
                case "K": $piece = ($c % 2 == $r % 2) ? "l" : "L";
                    break;
                case "Q": $piece = ($c % 2 == $r % 2) ? "w" : "W";
                    break;
                case "R": $piece = ($c % 2 == $r % 2) ? "t" : "T";
                    break;
                case "B":
                    if ($this->style == "alpha")
                        $piece = ($c % 2 == $r % 2) ? "n" : "N";
                    else
                        $piece = ($c % 2 == $r % 2) ? "v" : "V";
                    break;
                case "S":
                case "N": $piece = ($c % 2 == $r % 2) ? "m" : "M";
                    break;
                case "P": $piece = ($c % 2 == $r % 2) ? "o" : "O";
                    break;
            }
            $board[$c][$r] = $piece;
        }

        $this->board = $board;
    }

    public function makeBoard() {
        // cut the tail if exist
        $parts = explode(" ", $this->fen);
        $fen = $parts[0];
        $len = strlen($fen);
        // fen string verifying
        $fields = 0;
        for ($i = 0; $i < $len; $i++) {
            if (strpos("12345678", $fen[$i]) !== false) {
                $fields += $fen[$i];
            } elseif (strpos("kqrbnpKQRBNP", $fen[$i]) !== false) {
                $fields++;
            } elseif ($fen[$i] === '/') {
                
            } else {
                $fields += 100;
            }
        }
        if ($fields > 64) {
            $fen = "8/8/8/8/8/8/8/8";
            $len = strlen($fen);
        }

        // building diagram
        $board = array("1222222223");
        $boardline = "4";
        $fields = 0;
        for ($i = 0; $i < $len; $i++) {
            if (strpos("kqrbnpKQRBNP", $fen[$i]) !== false) {
                $posy = $fields >> 3;
                $posx = $fields % 8;
                switch ($fen[$i]) {
                    case "k": $piece = ($posy % 2 == $posx % 2) ? "l" : "L";
                        break;
                    case "q": $piece = ($posy % 2 == $posx % 2) ? "w" : "W";
                        break;
                    case "r": $piece = ($posy % 2 == $posx % 2) ? "t" : "T";
                        break;
                    case "b":
                        if ($this->style == "alpha")
                            $piece = ($posy % 2 == $posx % 2) ? "n" : "N";
                        else
                            $piece = ($posy % 2 == $posx % 2) ? "v" : "V";
                        break;
                    case "n": $piece = ($posy % 2 == $posx % 2) ? "m" : "M";
                        break;
                    case "p": $piece = ($posy % 2 == $posx % 2) ? "o" : "O";
                        break;
                    case "K": $piece = ($posy % 2 == $posx % 2) ? "k" : "K";
                        break;
                    case "Q": $piece = ($posy % 2 == $posx % 2) ? "q" : "Q";
                        break;
                    case "R": $piece = ($posy % 2 == $posx % 2) ? "r" : "R";
                        break;
                    case "B": $piece = ($posy % 2 == $posx % 2) ? "b" : "B";
                        break;
                    case "N":
                        if ($this->style == "alpha")
                            $piece = ($posy % 2 == $posx % 2) ? "h" : "H";
                        else
                            $piece = ($posy % 2 == $posx % 2) ? "n" : "N";
                        break;
                    case "P": $piece = ($posy % 2 == $posx % 2) ? "p" : "P";
                        break;
                }
                $boardline .= $piece;
                if (($fields + 1) % 8 == 0) {
                    $board[] = $boardline . "5";
                    $boardline = "4";
                }
                $fields++;
            } elseif ($fen[$i] > "0" and $fen[$i] < "9") {
                for ($j = 0; $j < $fen[$i]; $j++) {
                    $posy = $fields >> 3;
                    $posx = $fields % 8;
                    $boardline .= ($posy % 2 == $posx % 2) ? "*" : "+";
                    if (($fields + 1) % 8 == 0) {

                        $board[] = $boardline . "5";
                        $boardline = "4";
                    }
                    $fields++;
                }
            }
        }

        $board[] = ($this->style == "alpha") ? "6777777778" : "7888888889";
        return $board;
    }

    public function __construct($_fen = "8/8/8/8/8/8/8/8", $_size = 16, $_style = "marrfont") {
        $this->fen = $_fen;
        $this->fontsize = $_size;
        $this->style = $_style;
        $this->board = $this->makeBoard();
        $this->author = 'unknown';
        $this->stipulation = '';
        $this->source = '';
        $this->solution = '';
    }

}
