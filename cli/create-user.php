<?php

use core\clitext;

include_once __DIR__ . "/../config.php";

function usage()
{
    $filename = str_replace(__DIR__, "", __FILE__);
    $text = "USAGE:\n" +
        "\tphp " . $filename . "-l <login> -p <password> [-n <name>]\n";
    echo new clitext($text);
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

$text = new clitext("registered new user with id = " . $user_id);

echo $text;
