<?php

namespace core;

/**
 * Description of clitext
 *
 * @author mcroitor
 */
class clitext {

    // text color constants
    public const TXT_BLACK = "\e[30m";
    public const TXT_RED = "\e[31m";
    public const TXT_GREEN = "\e[32m";
    public const TXT_YELLOW = "\e[33m";
    public const TXT_BLUE = "\e[34m";
    public const TXT_MAGENTA = "\e[35m";
    public const TXT_CYAN = "\e[36m";
    public const TXT_WHITE = "\e[37m";
    public const TXT_BRIGHT_BLACK = "\e[90m";
    public const TXT_BRIGHT_RED = "\e[91m";
    public const TXT_BRIGHT_GREEN = "\e[92m";
    public const TXT_BRIGHT_YELLOW = "\e[93m";
    public const TXT_BRIGHT_BLUE = "\e[94m";
    public const TXT_BRIGHT_MAGENTA = "\e[95m";
    public const TXT_BRIGHT_CYAN = "\e[96m";
    public const TXT_BRIGHT_WHITE = "\e[97m";
    public const TXT_COLOR = [
        "BLACK" => self::TXT_BLACK,
        "RED" => self::TXT_RED,
        "GREEN" => self::TXT_GREEN,
        "YELLOW" => self::TXT_YELLOW,
        "BLUE" => self::TXT_BLUE,
        "MAGENTA" => self::TXT_MAGENTA,
        "CYAN" => self::TXT_CYAN,
        "WHITE" => self::TXT_WHITE,
        "BRIGHT_BLACK" => self::TXT_BRIGHT_BLACK,
        "BRIGHT_RED" => self::TXT_BRIGHT_RED,
        "BRIGHT_GREEN" => self::TXT_BRIGHT_GREEN,
        "BRIGHT_YELLOW" => self::TXT_BRIGHT_YELLOW,
        "BRIGHT_BLUE" => self::TXT_BRIGHT_BLUE,
        "BRIGHT_MAGENTA" => self::TXT_BRIGHT_MAGENTA,
        "BRIGHT_CYAN" => self::TXT_BRIGHT_CYAN,
        "BRIGHT_WHITE" => self::TXT_BRIGHT_WHITE,
        "0" => self::TXT_BLACK,
        "1" => self::TXT_RED,
        "2" => self::TXT_GREEN,
        "3" => self::TXT_YELLOW,
        "4" => self::TXT_BLUE,
        "5" => self::TXT_MAGENTA,
        "6" => self::TXT_CYAN,
        "7" => self::TXT_WHITE,
        "8" => self::TXT_BRIGHT_BLACK,
        "9" => self::TXT_BRIGHT_RED,
        "10" => self::TXT_BRIGHT_GREEN,
        "11" => self::TXT_BRIGHT_YELLOW,
        "12" => self::TXT_BRIGHT_BLUE,
        "13" => self::TXT_BRIGHT_MAGENTA,
        "14" => self::TXT_BRIGHT_CYAN,
        "15" => self::TXT_BRIGHT_WHITE,
    ];
    // background color constants
    public const BG_BLACK = "\e[40m";
    public const BG_RED = "\e[41m";
    public const BG_GREEN = "\e[42m";
    public const BG_YELLOW = "\e[43m";
    public const BG_BLUE = "\e[44m";
    public const BG_MAGENTA = "\e[45m";
    public const BG_CYAN = "\e[46m";
    public const BG_WHITE = "\e[47m";
    public const BG_BRIGHT_BLACK = "\e[100m";
    public const BG_BRIGHT_RED = "\e[101m";
    public const BG_BRIGHT_GREEN = "\e[102m";
    public const BG_BRIGHT_YELLOW = "\e[103m";
    public const BG_BRIGHT_BLUE = "\e[104m";
    public const BG_BRIGHT_MAGENTA = "\e[105m";
    public const BG_BRIGHT_CYAN = "\e[106m";
    public const BG_BRIGHT_WHITE = "\e[107m";
    public const BG_COLOR = [
        "BLACK" => self::BG_BLACK,
        "RED" => self::BG_RED,
        "GREEN" => self::BG_GREEN,
        "YELLOW" => self::BG_YELLOW,
        "BLUE" => self::BG_BLUE,
        "MAGENTA" => self::BG_MAGENTA,
        "CYAN" => self::BG_CYAN,
        "WHITE" => self::BG_WHITE,
        "BRIGHT_BLACK" => self::BG_BRIGHT_BLACK,
        "BRIGHT_RED" => self::BG_BRIGHT_RED,
        "BRIGHT_GREEN" => self::BG_BRIGHT_GREEN,
        "BRIGHT_YELLOW" => self::BG_BRIGHT_YELLOW,
        "BRIGHT_BLUE" => self::BG_BRIGHT_BLUE,
        "BRIGHT_MAGENTA" => self::BG_BRIGHT_MAGENTA,
        "BRIGHT_CYAN" => self::BG_BRIGHT_CYAN,
        "BRIGHT_WHITE" => self::BG_BRIGHT_WHITE,
        "0" => self::BG_BLACK,
        "1" => self::BG_RED,
        "2" => self::BG_GREEN,
        "3" => self::BG_YELLOW,
        "4" => self::BG_BLUE,
        "5" => self::BG_MAGENTA,
        "6" => self::BG_CYAN,
        "7" => self::BG_WHITE,
        "8" => self::BG_BRIGHT_BLACK,
        "9" => self::BG_BRIGHT_RED,
        "10" => self::BG_BRIGHT_GREEN,
        "11" => self::BG_BRIGHT_YELLOW,
        "12" => self::BG_BRIGHT_BLUE,
        "13" => self::BG_BRIGHT_MAGENTA,
        "14" => self::BG_BRIGHT_CYAN,
        "15" => self::BG_BRIGHT_WHITE,
    ];

    /**
     * 
     * @var string
     */
    private $text;

    /**
     * 
     * @var string
     */
    private $color;

    /**
     * 
     * @var string
     */
    private $bgcolor;

    /**
     * 
     * @param string $text
     * @param string $color
     * @param string $bgcolor
     */
    public function __construct(
            string $text,
            string $color = clitext::TXT_WHITE,
            string $bgcolor = clitext::BG_BLACK
    ) {
        $this->text = $text;
        $this->color = $color;
        $this->bgcolor = $bgcolor;
    }

    /**
     * 
     * @param string $color
     * @return clitext
     * @throws \Exception
     */
    public function color(string $color): clitext {
        if (isset(self::TXT_COLOR[$color])) {
            $color = self::TXT_COLOR[$color];
        } elseif (constant($color)) {
            $color = $color;
        } else {
            throw new \Exception("undefined text color");
        }
        return new clitext($this->text, $color);
    }

    /**
     * 
     * @param string $bgcolor
     * @return clitext
     * @throws Exception
     */
    public function background(string $bgcolor): clitext {
        if (isset(self::BG_COLOR[$bgcolor])) {
            $bgcolor = self::BG_COLOR[$bgcolor];
        } elseif (constant($bgcolor)) {
            $bgcolor = $bgcolor;
        } else {
            throw new \Exception("undefined background color");
        }
        return new clitext($this->text, $this->color, $bgcolor);
    }

    /**
     * 
     * @return string
     */
    public function __toString(): string {
        return $this->color . $this->bgcolor .
                $this->text .
                self::BG_BLACK . self::TXT_WHITE;
    }

}
