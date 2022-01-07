<?php
namespace core;
/**
 * Description of log
 *
 * @author Croitor Mihail <mcroitor@gmail.com>
 */
class logger {

    public const INFO = 1;  // standard color
    public const PASS = 2;  // green color
    public const WARN = 4;  // yellow color
    public const ERROR = 8; // red color
    public const FAIL = 16; // red color
    
    private const LOG_TYPE = [
        self::INFO => "INFO",
        self::PASS => "PASS",
        self::WARN => "WARN",
        self::ERROR => "ERROR",
        self::FAIL => "FAIL"
    ];
    
    private const COLOR_CODE = [
        self::INFO => clitext::TXT_WHITE,
        self::PASS => clitext::TXT_GREEN,
        self::WARN => clitext::TXT_YELLOW,
        self::ERROR => clitext::TXT_RED,
        self::FAIL => clitext::TXT_RED
    ];

    var $logfile;

    public function __construct($logfile = "php://stdout") {
        $this->logfile = $logfile;
    }

    private function write($data, $log_type) {
        if (isset($_SESSION["timezone"])) {
            date_default_timezone_set($_SESSION["timezone"]);
        }
        $type = self::LOG_TYPE[$log_type];
        $text = new clitext(date("Y-m-d H:i:s") . "\t{$type}: {$data}\n", self::COLOR_CODE[$log_type]);
        file_put_contents($this->logfile, $text->to_string(), FILE_APPEND);
    }

    public function info($data) {
        $this->write($data, self::INFO);
    }

    public function warn($data) {
        $this->write($data, self::WARN);
    }

    public function pass($data) {
        $this->write($data, self::PASS);
    }

    public function error($data) {
        $this->write($data, self::ERROR);
    }

    public function fail($data) {
        $this->write($data, self::FAIL);
    }

    public static function stdout(){
        return new logger();
    }
}
