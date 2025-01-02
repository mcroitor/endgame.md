<?php

// loading config data
if (!file_exists(__DIR__ . "/config.php")) {
    exit("<h2>Site is not installed or damaged</h2>");
}

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

const FPDF_PATH = __DIR__ . "/modules/fpdf/";
const FPDF_FONTPATH = FPDF_PATH . 'font/';

include_once __DIR__ . "/config.php";
require_once __DIR__ . '/modules/makebook.php';

$db = new \mc\sql\database(config::dsn);

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

$author = filter_input(
    INPUT_GET, 
    "author", 
    FILTER_DEFAULT,
    ["options" => ["default" => ""]]);
$white_min = filter_input(
    INPUT_GET, 
    "wmin", 
    FILTER_SANITIZE_NUMBER_INT,
    ["options" => ["default" => 0]]);
$white_max = filter_input(
    INPUT_GET,
    "wmax",
    FILTER_SANITIZE_NUMBER_INT,
    ["options" => ["default" => 0]]);
$black_min = filter_input(
    INPUT_GET, 
    "bmin", 
    FILTER_SANITIZE_NUMBER_INT,
    ["options" => ["default" => 0]]);
$black_max = filter_input(
    INPUT_GET,
    "bmax",
    FILTER_SANITIZE_NUMBER_INT,
    ["options" => ["default" => 0]]);
$piece_pattern = filter_input(
    INPUT_GET, 
    "piece_pattern", 
    FILTER_DEFAULT,
    ["options" => ["default" => ""]]);
$stipulation = filter_input(
    INPUT_GET, 
    "stipulation", 
    FILTER_DEFAULT,
    ["options" => ["default" => "-"]]);
$theme = filter_input(
    INPUT_GET, 
    "theme", 
    FILTER_DEFAULT,
    ["options" => ["default" => "-"]]);
$from_date = filter_input(
    INPUT_GET, 
    "from_date", 
    FILTER_DEFAULT,
    ["options" => ["default" => "0000"]]);
$to_date = filter_input(
    INPUT_GET, 
    "to_date", 
    FILTER_DEFAULT,
    ["options" => ["default" => "2050"]]);

$query = "SELECT * FROM endgame WHERE author LIKE '%{$author}%' ";
$query .= "AND whitep >= {$white_min} AND whitep <= {$white_max} ";
$query .= "AND blackp >= {$black_min} AND blackp <= {$black_max}";
if ($stipulation !== "-") {
    $query .= "AND stipulation LIKE '$stipulation' ";
}
if ($theme !== "-") {
    $query .= "AND theme LIKE '%$theme%' ";
}
if ($piece_pattern != "") {
    $query .= "AND piece_pattern='$piece_pattern' ";
}

$query .= "AND date >= {$from_date} AND date <= {$to_date} ORDER BY date ASC LIMIT 1000";

$result = $db->query_sql($query);

//create a FPDF object
$pdf = new MB();
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
    $position = new ChessPosition($value[\meta\endgame::FEN], 20, "leipzig");
    $position->author = $value[\meta\endgame::AUTHOR];
    $position->stipulation = $value[\meta\endgame::STIPULATION];
    $position->source = $value[\meta\endgame::SOURCE];
    $position->date = explode(".", $value[\meta\endgame::DATE])[0];
    $pgn = $db->select("raw", ["*"], ["id" => $value[\meta\endgame::PID]])[0]["data"];
    $solution = preg_replace('/\[\w+ ".+"\]\s/', "", $pgn);

    $position->solution = (new \mc\template($solution))->fill($rule)->value();
    $pdf->DrawDiagram($position);
    $pdf->WriteHTML($position->solution);
}


$pdf->SetLeftMargin(10);
$pdf->Close();
//Output the document
$pdf->Output('example1.pdf', 'I');
