<?php

if (!file_exists("config.php")) {
    echo "<h2>site is not installed!</h2>";
    exit();
}
include "config.php";

// total endgames
$db = new \core\database(config::dsn);
$total_endgames = $db->select("endgame", ["COUNT(*) AS total"])[0]['total'];

//select themes
//$themes = "<option value='-'>Any theme</option>\n<option value='unknown'>unknown</option>\n";
//$select_themes = $db->select("themes");
//foreach ($select_themes as $theme) {
//    $themes .= "<option value='{$theme['theme_name']}'>{$theme['theme_id']}</option>\n";
//}

// last changes
$last_changes = "";
$select_changes = $db->query_sql("SELECT * FROM changes ORDER BY date DESC LIMIT 5");
foreach ($select_changes as $fetch) {
    $last_changes .= "<li>{$fetch['date']}: {$fetch['nr_games']} endgames from {$fetch['filename']}</li>";
}


$template = file_get_contents(__DIR__ . "/theme/templates/index.template.php");

$fill = [
    "<!-- total_endgames -->" => $total_endgames,
    "<!-- last_changes -->" => $last_changes
];

\core\logger::stdout()->info(json_encode($fill));

echo (new \core\template($template))->fill($fill)->value();