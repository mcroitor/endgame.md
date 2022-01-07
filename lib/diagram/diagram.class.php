<?php

if (defined("LIBRARY_PATH") === false) {
    define("LIBRARY_PATH", __DIR__ . "/");
}

$colors = [
    "black" => [0, 0, 0],
    "red" => [255, 0, 0],
    "green" => [0, 255, 0],
    "blue" => [0, 0, 255],
    "gray" => [127, 127, 127],
    "darkred" => [127, 0, 0],
    "darkgreen" => [0, 127, 0],
    "darkblue" => [0, 0, 127],
    "white" => [255, 255, 255],
    "blackshift" => [159, 159, 159],
    "redshift" => [255, 159, 159],
    "greenshift" => [159, 255, 159],
    "blueshift" => [159, 159, 255],
    "grayshift" => [196, 196, 196],
    "darkredshift" => [196, 159, 159],
    "darkgreenshift" => [159, 196, 159],
    "darkblueshift" => [159, 159, 196],
    "olive" => [127, 127, 0],
    "oliveshift" => [196, 196, 159],
    "armygreen" => [75, 83, 32],
    "armygreenshift" => [75 + 127, 83 + 127, 32 + 127],
    "indigo" => [75, 0, 130],
    "indigoshift" => [75 + 127, 0 + 127, 255]
];

//conversion pixel -> millimeter at 72 dpi
function px2mm($px) {
    return $px * 25.4 / 72;
}

//conversion millimeter at 72 dpi -> pixel
function mm2px($mm) {
    return $mm * 72 / 25.4;
}

//conversion pixel -> point
function px2pt($px) {
    return $px * 3 / 4;
}

//conversion point -> pixel
function pt2px($pt) {
    return $pt * 4 / 3;
}

function crop($img){
	//find the size of the borders
	$b_top = 0;
	$b_btm = 0;
	$b_lft = 0;
	$b_rt = 0;
	
	//top
	for(; $b_top < imagesy($img); ++$b_top) {
	  for($x = 0; $x < imagesx($img); ++$x) {
	    if(imagecolorat($img, $x, $b_top) != 0xFFFFFF) {
	       break 2; //out of the 'top' loop
	    }
	  }
	}
	
	//bottom
	for(; $b_btm < imagesy($img); ++$b_btm) {
	  for($x = 0; $x < imagesx($img); ++$x) {
	    if(imagecolorat($img, $x, imagesy($img) - $b_btm-1) != 0xFFFFFF) {
	       break 2; //out of the 'bottom' loop
	    }
	  }
	}
	
	//left
	for(; $b_lft < imagesx($img); ++$b_lft) {
	  for($y = 0; $y < imagesy($img); ++$y) {
	    if(imagecolorat($img, $b_lft, $y) != 0xFFFFFF) {
	       break 2; //out of the 'left' loop
	    }
	  }
	}
	
	//right
	for(; $b_rt < imagesx($img); ++$b_rt) {
	  for($y = 0; $y < imagesy($img); ++$y) {
	    if(imagecolorat($img, imagesx($img) - $b_rt-1, $y) != 0xFFFFFF) {
	       break 2; //out of the 'right' loop
	    }
	  }
	}
	
	//copy the contents, excluding the border
	$newimg = imagecreatetruecolor(
	    imagesx($img)-($b_lft+$b_rt), imagesy($img)-($b_top+$b_btm));
	
	imagecopy($newimg, $img, 0, 0, $b_lft, $b_top, imagesx($newimg), imagesy($newimg));
	return $newimg;
}

/**
 * this class represents a chess board and realize a set of useful methods
 */
class Board {

    /**
     *
     * @var string[][]
     */
    var $board;

    function __construct($fen) {
        $this->board = $this->fen2board($fen);
    }

    /**
     * Converts fen to matrix (board)
     * @param string $fen
     * @return string[][]
     */
    function fen2board($fen) {
        $parts = explode(" ", $fen);
        $epd = $parts[0];

        // fen string verifying
        if ($this->is_correct_epd($epd) === true) {
            $epd = "8/8/8/8/8/8/8/8";
        }
        $brd = [];

        $lines = explode("/", $epd);
        for ($i = 0; $i !== 8; ++$i) {
            $brd[$i] = $this->__brd_line($lines[$i]);
        }
        return $brd;
    }

    /**
     * parse one token from fen
     * @param string $line
     * @return string[]
     */
    private function __brd_line($line) {
        $brd_line = [];
        $count = 0;
        for ($i = 0; $i < strlen($line); ++$i) {
            if (strpos("12345678", $line[$i]) !== false) {
                $aux = (int) $line[$i];
                while ($aux > 0) {
                    $brd_line[$count] = " ";
                    ++$count;
                    --$aux;
                }
            } else {
                $brd_line[$count] = $line[$i];
                ++$count;
            }
        }
        return $brd_line;
    }

    /**
     * fen / epd correctness validation
     * @param string $epd
     * @return bool
     */
    private function is_correct_epd($epd) {
        $len = strlen($epd);
        $fields = 0;
        for ($i = 0; $i < $len; $i++) {
            if (strpos("12345678", $epd[$i]) === true) {
                $fields += $epd[$i];
            } elseif (strpos("kqrbnpKQRBNPX", $epd[$i]) === true) {
                $fields++;
            } elseif ($epd[$i] === '/') {
                
            } else {
                $fields += 100;
            }
        }
        return $fields === 64;
    }

    /**
     * 
     * @return string
     */
    function toString() {
        $str = "";
        for ($i = 0; $i != 8; ++$i) {
            $str .= implode($this->board[$i]) . "\n";
        }
        return $str;
    }

    /**
     * lines of board
     * @return string[]
     */
    function lines() {
        $l = [];
        for ($i = 0; $i != 8; ++$i) {
            $l[$i] = implode($this->board[$i]);
        }
        return $l;
    }

    /**
     * ASCII board
     * @param string[][] $simbols
     * @return string
     */
    function ascii($simbols) {
        $result = "";
        for ($i = 0; $i !== 8; ++$i) {
            $board_line = [];
            for ($j = 0; $j !== 8; ++$j) {
                if ($this->board[$i][$j] === " ") {
                    $index = ($i % 2 === $j % 2) ? "+" : "-";
                } else {
                    $index = ($i % 2 === $j % 2) ? $this->board[$i][$j] . "0" : $this->board[$i][$j] . "1";
                }

                $board_line[$j] = $simbols[$index];
            }
            $result .= implode($board_line) . "\n";
        }
        return $result;
    }

}

/**
 * The chess diagram generator from fen / epd string
 */
class Diagram {

    /**
     * Board definition
     * @var Board 
     */
    var $board;

    /**
     * Board style, is equivalent with chess font name
     * @var string 
     */
    var $style;

    /**
     * solid or hatched board
     * @var bool 
     */
    var $solid;

    /**
     * single or double margin of board
     * @var type bool
     */
    var $dbl_margin;

    /**
     * color of diagram
     * @var string
     */
    var $color;

    /**
     * field dimension
     * @var int 
     */
    var $size;

    /**
     * chess piece representation, defined by <i>style</i>
     * @var string[][]
     */
    var $simbols;
    var $m, $b, $s;

    function __construct($fen, $options = []) {
        $this->board = new Board($fen);
        $this->style = empty($options["style"]) ? "alpha" : $options["style"];
        $this->size = empty($options["size"]) ? 30 : $options["size"];
        $this->color = empty($options["color"]) ? "black" : $options["color"];
        $this->solid = empty($options["solid"]) ? false : $options["solid"];
        $this->dbl_margin = empty($options["dbl_margin"]) ? false : $options["dbl_margin"];

        $this->s = 8 * $this->size;
        $this->b = (int) ($this->size / 40) + 1;
        $this->m = $this->b + 1;
        $this->m *= ($this->dbl_margin === true) ? 3 : 1;
        $this->s += 2 * $this->m;


        global $simbols;
        if (!file_exists("./font-desc/{$this->style}.php")) {
            $this->style = "alpha";
        }
        include_once("./font-desc/{$this->style}.php");
        $this->simbols = $simbols[$this->style];
    }

    /**
     * ASCII board representation
     * @return string
     */
    function ascii() {
        return $this->board->ascii($this->simbols);
    }

    /**
     * returns true color image
     * @global array $colors
     * @return resource
     */
    function toImage() {
        global $colors;

        $d = imagecreatetruecolor($this->s + 1, $this->s + 1); // +1 fix
        $dark_color = imagecolorallocate(
                $d, $colors[$this->color][0], $colors[$this->color][1], $colors[$this->color][2]);
        $light_color = imagecolorallocate(
                $d, $colors[$this->color . "shift"][0], $colors[$this->color . "shift"][1], $colors[$this->color . "shift"][2]);
        $white_color = imagecolorallocate(
                $d, $colors["white"][0], $colors["white"][1], $colors["white"][2]);

        imagefill($d, 0, 0, $dark_color);
        imagefilledrectangle(
                $d, $this->b, $this->b, $this->s - $this->b, $this->s - $this->b, $white_color);

        if ($this->dbl_margin === true) {
            imagefilledrectangle(
                    $d, 2 * $this->b, 2 * $this->b, $this->s - 2 * $this->b, $this->s - 2 * $this->b, $dark_color);
            imagefilledrectangle(
                    $d, 3 * $this->b, 3 * $this->b, $this->s - 3 * $this->b, $this->s - 3 * $this->b, $white_color);
        }

        if ($this->solid == true) {
            for ($i = 0; $i != 8; ++$i) {
                for ($j = 0; $j !== 8; ++$j) {
                    if ($i % 2 !== $j % 2) {
                        imagefilledrectangle(
                                $d, $i * $this->size + $this->m, $j * $this->size + $this->m, ($i + 1) * $this->size + $this->m, ($j + 1) * $this->size + $this->m, $light_color);
                    } else {
                        imagefilledrectangle(
                                $d, $i * $this->size + $this->m, $j * $this->size + $this->m, ($i + 1) * $this->size + $this->m, ($j + 1) * $this->size + $this->m, $white_color);
                    }
                }
            }
        }

        $fix = px2pt($this->simbols["delta"] * $this->size);
        for ($i = 0; $i != 8; ++$i) {
            for ($j = 0; $j != 8; ++$j) {
                $index = ($i % 2 === $j % 2 || $this->solid == true) ? "0" : "1";
                $char = $this->board->board[$i][$j];
                if ($char === " ") {
                    $char = ($index === "0") ? $this->simbols["-"] : $this->simbols["+"];
                } else {
                    $char = $this->simbols[$char . $index];
                }
                imagefttext($d
                        , px2pt($this->size)
                        , 0
                        , $j * $this->size + $this->m
                        , ($i + 1) * $this->size + $this->m + $fix
                        , $dark_color
                        , LIBRARY_PATH . "fonts/{$this->style}.ttf"
                        , $char);
            }
        }
        return crop($d);
    }

}
