<?php

require("chessposition.class.php");

if (!defined("FPDF_PATH")) {
    define("FPDF_PATH", "./");
}
if (!defined("FPDF_FONTPATH")) {
    define("FPDF_FONTPATH", FPDF_PATH . "font/");
}
require(FPDF_PATH . "extfpdf.php");

class MakeBook extends ExtFPDF {

    public static $HeaderFont = "Courier";
    public static $HeaderFontSize = 20;
    public static $CommonFont = "ArialPSMT";
    public static $CommonFontSize = 10;
    public static $FooterFontSize = 8;
    var $headerData;
    var $isHeaderEnabled;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        parent::__construct($orientation, $unit, $format);
        $this->isHeaderEnabled = false;
        $this->headerData = 'test';
    }

    public function EnableHeader() {
        $this->isHeaderEnabled = true;
    }

    public function DisableHeader() {
        $this->isHeaderEnabled = false;
    }

    public function Header() {
        if ($this->isHeaderEnabled) {
            $this->SetXY(20, 10);
            $this->WriteHtml($this->headerData);
            $this->Ln();
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont(self::$CommonFont, 'I', self::$FooterFontSize);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    public function DrawDiagram($position, $x = -1, $y = -1) {
        $tmpfont = $this->FontFamily;
        $tmpsize = $this->FontSizePt;
        $this->AddFont($position->style, '', $position->style . ".php");
        $this->SetFont($position->style);
        $this->SetFontSize($position->fontsize);
        $size = px2mm($position->fontsize);
        if ($y > 0) {
            $this->SetY($y + $i * $size);
        }
        if ($this->y + 11 * $size > $this->PageBreakTrigger) {
            if ($this->col < $this->colNr - 1) {
                $this->SetCol($this->col + 1);
                $this->SetY($this->y0);
            } else {
                $this->SetCol(0);
                $this->AddPage();
            }
        }

        foreach ($position->board as $i => $line) {
            if ($x > 0)
                $this->SetX($x);
            $this->Ln();
            $this->Write($size, $line);
        }
        $this->SetFont($tmpfont, '', $tmpsize);
//		$this->SetXY($x+$size,$y+9*$size);
        $this->Ln(0);
        $this->SetX($this->x + $size);
        $this->Write($size, "{$position->author}, {$position->date}, {$position->stipulation}");
        $this->Ln();
    }

    public function DrawDiagramPage($positions, $cols, $rows) {
        $sizex = (int) (($this->w - $this->lMargin - $this->rMargin - 20) / $cols);
        $sizey = (int) (($this->h - $this->tMargin - 25) / $rows);
        $dgSize = ($sizex > $sizey) ? $sizey : $sizex;

        foreach ($positions as $key => $position) {
            $i = ((int) ($key / $cols)) % $rows;
            $j = $key % $cols;
            if ($i == 0 && $j == 0) {
                $this->AddPage();
            }
            $position->fontsize = mm2px($dgSize / 8 - 2);
            $this->DrawDiagram($position, 20 + $j * $sizex, 10 + $i * $sizey);
            $this->SetXY(20 + $j * $sizex, 20 + $i * $sizey);
            $this->Write(5, $key + 1);
        }
    }

    public function CoverPage(){
        $h = 20;
        $w = $this->GetPageWidth() / 2 - 20;
        $this->SetXY($h, $w);
        $this->SetFont(self::$HeaderFont, "B", self::$HeaderFontSize + 4);
        $this->Write($h, $this->metadata["Title"]);
        $this->SetXY($h + 50, $w + 30);
        $this->SetFont(self::$HeaderFont, "", self::$HeaderFontSize);
        $this->Write($h, $this->metadata["Author"]);
        $this->SetFont(self::$CommonFont, "", self::$CommonFontSize);
    }
}
