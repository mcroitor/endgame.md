<?php

// loading config data
if (!file_exists("config.php")) {
    exit("<h2>Site is not installed or damaged</h2>");
}
include_once("config.php");

$rule = [
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

$db = new \core\database(config::dsn);

define("FPDF_PATH", "modules/fpdf/");
define('FPDF_FONTPATH', FPDF_PATH . 'font/');

require('modules/makebook.php');

class MB extends MakeBook {

    function Header() {
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(52);
        $this->Cell(83, 4, 'Chess Endgame Study Database Selection', 1, 0, 'C');
        $this->Ln();
        $this->Ln();
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell($this->w, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

}

$author = $_REQUEST["author"] ?? "";
$wpiece = $_REQUEST["wpiece"] ?? 0;
$bpiece = $_REQUEST["bpiece"] ?? 0;
$wsign = $_REQUEST["wsign"] ?? ">";
$bsign = $_REQUEST["bsign"] ?? ">";
$piece_pattern = $_REQUEST["piece_pattern"] ?? "";
$stipulation = $_REQUEST["stipulation"] ?? "-";
$theme = $_REQUEST["theme"] ?? "-";
$fromdate = !empty($_REQUEST["fromDate"]) ? $_REQUEST["fromDate"] . ".00.00" : "0000.00.00";
$todate = !empty($_REQUEST["toDate"]) ? $_REQUEST["toDate"] . ".??.??" : "2050.00.00";

$query = "SELECT * FROM endgame WHERE author LIKE '%$author%' ";
if ((int) $wpiece > 0) {
    $query .= "AND whitep$wsign$wpiece ";
}
if ((int) $bpiece > 0) {
    $query .= "AND blackp$bsign$bpiece ";
}
if ($stipulation !== "-") {
    $query .= "AND stipulation LIKE '$stipulation' ";
}
if ($theme !== "-") {
    $query .= "AND theme LIKE '%$theme%' ";
}
if ($piece_pattern != "") {
    $query .= "AND piece_pattern='$piece_pattern' ";
}

$query .= "AND date>='$fromdate' AND date<='$todate' ORDER BY date ASC LIMIT 1000";

$result = $db->query_sql($query);

//create a FPDF object
$pdf = new MB();
//set document properties
$pdf->SetTopMargin(5);
// $pdf->Open();
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
    $position = new ChessPosition($value[\meta\endgame::FEN], 20, "leipzig");
    $position->author = $value[\meta\endgame::AUTHOR];
    $position->stipulation = $value[\meta\endgame::STIPULATION];
    $position->source = $value[\meta\endgame::SOURCE];
    $position->date = explode(".", $value[\meta\endgame::DATE])[0];
    $pgn = $db->select("raw", ["*"], ["id" => $value[\meta\endgame::PID]])[0]["data"];
    $solution = preg_replace('/\[\w+ ".+"\]\s/', "", $pgn);

    $position->solution = (new \core\template($solution))->fill($rule)->value();
    $pdf->DrawDiagram($position);
    $pdf->WriteHTML($position->solution);
}


$pdf->SetLeftMargin(10);
$pdf->Close();
//Output the document
$pdf->Output('example1.pdf', 'I');
//	header("Location:example1.pdf");

