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
        "\tphp " . $filename . " -l <login> -p <password>\n";
    echo new clitext($text, clitext::TXT_BLUE, clitext::BG_BRIGHT_WHITE);
}

$short_opts = "l:p:";

$opts = getopt($short_opts);

if (count($opts) < 2) {
    usage();
    exit();
}

$login = $opts["l"];
$password = $opts["p"];

$users = \config::$db->select(\meta\user::__name__, ["*"], ["login" => $login]);

if(empty($users)) {
    echo new clitext("User not found!", clitext::TXT_RED, clitext::BG_BRIGHT_WHITE);
    exit();
}

$user = $users[0];

$user[\meta\user::PASSWORD] = \mc\user::crypt($login, $password);

$db->update(\meta\user::__name__, $user, ["id" => $user[\meta\user::ID]]);

echo new clitext("Password reset successfully!", clitext::TXT_GREEN, clitext::BG_BRIGHT_WHITE);