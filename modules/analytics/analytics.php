<?php

include_once __DIR__ . "/capabilities/analytics_capabilities.php";

/**
 * Here we implement the logic for endgame analytics and statistics.
 * It will do:
 * 
 *  - detect authors and collect their statistics
 *  - detect endgame piece patterns
 *  - detect data mistakes and fix them
 */
class analytics
{
    /**
     * Check the results of the game and update to the correct values.
     */
    public static function checkResults()
    {
        $okResults = [
            "White wins",
            "Draw",
            "Black wins",
        ];
        $association = [
            "1-0" => "White wins",
            "1/2-1/2" => "Draw",
            "Win" => "White wins",
        ];
        $stat = [
            "valid" => 0,
            "fixed" => 0,
            "invalid" => 0,
        ];

        $crud = new \mc\sql\crud(
            new \mc\sql\database(config::dsn),
            \meta\endgame::__name__
        );

        $count = $crud->count();
        $endgames = $crud->all(0, $count);
        foreach ($endgames as $endgame) {
            $result = $endgame["result"];
            if (isset($association[$result])) {
                $stat["fixed"]++;
                $endgame["result"] = $association[$result];
                $crud->update($endgame);
            }
            elseif (in_array($result, $okResults)) {
                $stat["valid"]++;
            }
            else {
                $stat["invalid"]++;
            }
        }
        return $stat;
    }
}
