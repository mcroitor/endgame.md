<?php

namespace modules\pdf;

require_once __DIR__ . '/lib/makebook.php';

class pdf
{

    private const QUERY = <<<SQL
        SELECT * FROM endgame WHERE author LIKE '%{author}%'
        AND whitep >= {white_min} AND whitep <= {white_max} 
        AND blackp >= {black_min} AND blackp <= {black_max}
    SQL;

    private const RULE = [
        "{" => "<div class='commentary'>{",
        "}" => "}</div>",
        ")" => ")</div>",
        "(" => "<div class='variant'>(",
        " $1 " => "! ",
        " $2 " => "? ",
        " $3 " => "!! ",
        " $4 " => "?? ",
        " $5 " => "!? ",
        " $6 " => "?! ",
        " $11" => "=",
        " $19" => "-+",
        " $18" => "+-"
    ];

    private static function getAuthor()
    {
        return filter_input(
            INPUT_GET,
            "author",
            FILTER_DEFAULT,
            ["options" => ["default" => ""]]
        );
    }
    private static function getWhiteMin()
    {
        return filter_input(
            INPUT_GET,
            "wmin",
            FILTER_SANITIZE_NUMBER_INT,
            ["options" => ["default" => 0]]
        );
    }
    private static function getWhiteMax()
    {
        return filter_input(
            INPUT_GET,
            "wmax",
            FILTER_SANITIZE_NUMBER_INT,
            ["options" => ["default" => 32]]
        );
    }
    private static function getBlackMin()
    {
        return filter_input(
            INPUT_GET,
            "bmin",
            FILTER_SANITIZE_NUMBER_INT,
            ["options" => ["default" => 0]]
        );
    }
    private static function getBlackMax()
    {
        return filter_input(
            INPUT_GET,
            "bmax",
            FILTER_SANITIZE_NUMBER_INT,
            ["options" => ["default" => 32]]
        );
    }
    private static function getPiecesPattern()
    {
        return filter_input(
            INPUT_GET,
            "piece_pattern",
            FILTER_DEFAULT,
            ["options" => ["default" => ""]]
        );
    }
    private static function getStipulation()
    {
        return filter_input(
            INPUT_GET,
            "stipulation",
            FILTER_DEFAULT,
            ["options" => ["default" => "-"]]
        );
    }
    private static function getTheme()
    {
        return filter_input(
            INPUT_GET,
            "theme",
            FILTER_DEFAULT,
            ["options" => ["default" => "-"]]
        );
    }
    private static function getFromDate()
    {
        return filter_input(
            INPUT_GET,
            "from_date",
            FILTER_DEFAULT,
            ["options" => ["default" => "0000"]]
        );
    }
    private static function getToDate()
    {
        return filter_input(
            INPUT_GET,
            "to_date",
            FILTER_DEFAULT,
            ["options" => ["default" => "2050"]]
        );
    }
    private static function getCook()
    {
        return filter_input(
            INPUT_GET,
            "cook",
            FILTER_DEFAULT,
            ["options" => ["default" => "all"]]
        );
    }

    #[\mc\route("pdf/get")]
    public static function get(array $params)
    {
        $db = \config::$db;
        $escape = static fn($val): string => str_replace("'", "''", (string) $val);
        $author = $escape(self::getAuthor() ?? "");
        $white_min = (int) (self::getWhiteMin() ?? 0);
        $white_max = (int) (self::getWhiteMax() ?? 32);
        $black_min = (int) (self::getBlackMin() ?? 0);
        $black_max = (int) (self::getBlackMax() ?? 32);
        $piece_pattern = (string) (self::getPiecesPattern() ?? "");
        $stipulation = (string) (self::getStipulation() ?? "-");
        $theme = (string) (self::getTheme() ?? "-");
        $from_date = (int) (self::getFromDate() ?? 0);
        $to_date = (int) (self::getToDate() ?? 2050);

        $query = str_replace(
            ["{author}", "{white_min}", "{white_max}", "{black_min}", "{black_max}"],
            [$author, $white_min, $white_max, $black_min, $black_max],
            self::QUERY
        );
        if ($stipulation !== "-") {
            $query .= " AND stipulation LIKE '" . $escape($stipulation) . "' ";
        }
        if ($theme !== "-") {
            $query .= " AND theme LIKE '%" . $escape($theme) . "%' ";
        }
        if ($piece_pattern !== "") {
            $query .= " AND piece_pattern='" . $escape($piece_pattern) . "' ";
        }
        $cook = (string) (self::getCook() ?? "all");
        if ($cook === "1" || $cook === "true") {
            $query .= " AND cook=1 ";
        } elseif ($cook === "0") {
            $query .= " AND cook=0 ";
        }

        $query .= " AND date >= {$from_date} AND date <= {$to_date} ORDER BY date ASC LIMIT 1000";
        $result = $db->query_sql($query);

        //create a FPDF object
        $pdf = new \MakeBook();
        //set document properties
        $pdf->SetTopMargin(5);

        $pdf->SetAuthor('Mihail Croitor');
        $pdf->AddFont("ArialPSMT", "", "arialcyr.php");
        $pdf->AddFont("ArialPSMT", "B", "arialcyrbd.php");
        $pdf->AddFont("ArialPSMT", "I", "arialcyri.php");
        $pdf->SetFont("ArialPSMT");
        $pdf->SetFontSize(10);
        $pdf->SetTitle('Chess Endgame Study Database Selection');
        $pdf->AddPage('P');
        $pdf->SetDisplayMode('real', 'default');
        $pdf->SetColNr(2);

        foreach ($result as $key => $value) {
            $position = new \ChessPosition($value[\meta\endgame::FEN], 20, "leipzig");
            $position->author = $value[\meta\endgame::AUTHOR];
            $position->stipulation = $value[\meta\endgame::STIPULATION];
            $position->source = $value[\meta\endgame::SOURCE];
            $position->date = explode(".", $value[\meta\endgame::DATE])[0];
            $rawRows = $db->select("raw", ["*"], ["id" => $value[\meta\endgame::PID]]);
            $pgn = $rawRows[0]["data"] ?? "";
            $solution = preg_replace('/\[\w+ ".+"\]\s/', "", $pgn);

            $position->solution = (new \mc\template($solution))
                ->fill(self::RULE)
                ->value();
            $pdf->DrawDiagram($position);
            $pdf->WriteHTML($position->solution);
        }

        $pdf->SetLeftMargin(10);
        $pdf->Close();
        //Output the document
        $pdf->Output('example1.pdf', 'I');
        return "";
    }
}
