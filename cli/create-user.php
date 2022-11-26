<?php

if (php_sapi_name() !== "cli") {
    exit("not CLI");
}

use core\clitext;

include_once __DIR__ . "/../config.php";

function usage()
{
    $filename = str_replace(__DIR__, "", __FILE__);
    $text = "USAGE:\n" .
        "\tphp " . $filename . " -l <login> -p <password> [-n <name>]\n";
    echo new clitext($text, clitext::TXT_BLUE, clitext::BG_BRIGHT_WHITE);
}

$short_opts = "l:p:n::";

$opts = getopt($short_opts);

if (count($opts) < 2) {
    usage();
    exit();
}

$data = [
    "login" => $opts["l"],
    "password" => $opts["p"],
    "name" => $opts["n"] ?? "NewUser",
    "role_id" => 2
];

$user_id = \mc\user::register($data);

$text = new clitext("registered new user with id = " . $user_id, clitext::TXT_BLUE, clitext::BG_BRIGHT_WHITE);

echo $text;
