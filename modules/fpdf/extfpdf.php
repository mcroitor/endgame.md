<?php
if(!defined("FPDF_PATH"))
	define("FPDF_PATH", "./");
if(!defined("FPDF_FONTPATH"))
	define("FPDF_FONTPATH", FPDF_PATH."font/");

require(FPDF_PATH."fpdf.php");

//function hex2dec
//returns an associative array (keys: R,G,B) from
//a hex html code (e.g. #3FE5AA)
function hex2dec($couleur = "#000000"){
    $R = substr($couleur, 1, 2);
    $rouge = hexdec($R);
    $V = substr($couleur, 3, 2);
    $vert = hexdec($V);
    $B = substr($couleur, 5, 2);
    $bleu = hexdec($B);
    $tbl_couleur = array();
    $tbl_couleur['R']=$rouge;
    $tbl_couleur['V']=$vert;
    $tbl_couleur['B']=$bleu;
    return $tbl_couleur;
}

//conversion pixel -> millimeter at 72 dpi
function px2mm($px){
    return $px*25.4/72;
}
//conversion millimeter at 72 dpi -> pixel
function mm2px($mm){
    return $mm*72/25.4;
}

function txtentities($html){
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}

class ExtFPDF extends FPDF
{
// begin bookmark code
	var $outlines=array();
	var $OutlineRoot;

	function Bookmark($txt, $level=0, $y=0)
	{
		if($y==-1)
			$y=$this->GetY();
		$this->outlines[]=array('t'=>$txt, 'l'=>$level, 'y'=>($this->h-$y)*$this->k, 'p'=>$this->PageNo());
	}

	function BookmarkUTF8($txt, $level=0, $y=0)
	{
		$this->Bookmark($this->_UTF8toUTF16($txt),$level,$y);
	}

	function _putbookmarks()
	{
		$nb=count($this->outlines);
		if($nb==0)
			return;
		$lru=array();
		$level=0;
		foreach($this->outlines as $i=>$o)
		{
			if($o['l']>0)
			{
				$parent=$lru[$o['l']-1];
				//Set parent and last pointers
				$this->outlines[$i]['parent']=$parent;
				$this->outlines[$parent]['last']=$i;
				if($o['l']>$level)
				{
					//Level increasing: set first pointer
					$this->outlines[$parent]['first']=$i;
				}
			}
			else
				$this->outlines[$i]['parent']=$nb;
			if($o['l']<=$level and $i>0)
			{
				//Set prev and next pointers
				$prev=$lru[$o['l']];
				$this->outlines[$prev]['next']=$i;
				$this->outlines[$i]['prev']=$prev;
			}
			$lru[$o['l']]=$i;
			$level=$o['l'];
		}
		//Outline items
		$n=$this->n+1;
		foreach($this->outlines as $i=>$o)
		{
			$this->_newobj();
			$this->_out('<</Title '.$this->_textstring($o['t']));
			$this->_out('/Parent '.($n+$o['parent']).' 0 R');
			if(isset($o['prev']))
				$this->_out('/Prev '.($n+$o['prev']).' 0 R');
			if(isset($o['next']))
				$this->_out('/Next '.($n+$o['next']).' 0 R');
			if(isset($o['first']))
				$this->_out('/First '.($n+$o['first']).' 0 R');
			if(isset($o['last']))
				$this->_out('/Last '.($n+$o['last']).' 0 R');
			$this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]',1+2*$o['p'],$o['y']));
			$this->_out('/Count 0>>');
			$this->_out('endobj');
		}
		//Outline root
		$this->_newobj();
		$this->OutlineRoot=$this->n;
		$this->_out('<</Type /Outlines /First '.$n.' 0 R');
		$this->_out('/Last '.($n+$lru[0]).' 0 R>>');
		$this->_out('endobj');
	}

	function _putresources()
	{
		parent::_putresources();
		$this->_putbookmarks();
	}

	function _putcatalog()
	{
		parent::_putcatalog();
		if(count($this->outlines)>0)
		{
			$this->_out('/Outlines '.$this->OutlineRoot.' 0 R');
			$this->_out('/PageMode /UseOutlines');
		}
	}
// end bookmark code

// begin index code
	function CreateIndex(){
		//Index title
		$this->SetFontSize(20);
		$this->Cell(0,5,'Index',0,1,'C');
		$this->SetFontSize(15);
		$this->Ln(10);

		$size=sizeof($this->outlines);
		$PageCellSize=$this->GetStringWidth('p. '.$this->outlines[$size-1]['p'])+2;
		for ($i=0;$i<$size;$i++){
			//Offset
			$level=$this->outlines[$i]['l'];
			if($level>0)
				$this->Cell($level*8);

			//Caption
			$str=$this->outlines[$i]['t'];
			$strsize=$this->GetStringWidth($str);
			$avail_size=$this->w-$this->lMargin-$this->rMargin-$PageCellSize-($level*8)-4;
			while ($strsize>=$avail_size){
				$str=substr($str,0,-1);
				$strsize=$this->GetStringWidth($str);
			}
			$this->Cell($strsize+2,$this->FontSize+2,$str);

			//Filling dots
			$w=$this->w-$this->lMargin-$this->rMargin-$PageCellSize-($level*8)-($strsize+2);
			$nb=$w/$this->GetStringWidth('.');
			$dots=str_repeat('.',$nb);
			$this->Cell($w,$this->FontSize+2,$dots,0,0,'R');

			//Page number
			$this->Cell($PageCellSize,$this->FontSize+2,'p. '.$this->outlines[$i]['p'],0,1,'R');
		}
	}
// end index code

// begin writeHtml code
	//variables of html parser
	var $B;
	var $I;
	var $U;
	var $HREF;
	var $fontList;
	var $issetfont;
	var $issetcolor;
	var $H1, $H2, $H3, $H4;
	var $tmpfontsize, $tmpfontstyle;

	function ExtFPDF($orientation='P', $unit='mm', $format='A4')
	{
		//Call parent constructor
		$this->FPDF($orientation,$unit,$format);
		//Initialization
		$this->B=0;
		$this->I=0;
		$this->U=0;
		$this->HREF='';
		$this->fontlist=array('arial', 'times', 'courier', 'helvetica', 'symbol');
		$this->issetfont=false;
		$this->issetcolor=false;
		$this->H1 = 0;
		$this->H2 = 0;
		$this->H3 = 0;
		$this->H4 = 0;
		$this->tmpfontsize = 0;
		$this->tmpfontstyle = '';
		$this->SetColNr(1);
	}

	function WriteHTML($html, $x = -1, $y = -1, $w = -1, $h = -1)
	{
		if ($x > 0)
		{
			$lm = $this->lMargin;
			$this->lMargin = $x;
		}
		if ($y > 0)
		{
			$tm = $this->tMargin;
			$this->tMargin = $y;
		}
		if($w > 0)
		{
			$rm = $this->rMargin;
			$this->rMargin = $this->w - $this->lMargin - $w;
		}
		if($h > 0)
		{
			$bm = $this->bMargin;
			$this->bMargin = $this->h-$this->tMargin-$h;
		}
		//HTML parser
		$html=strip_tags($html,"<b><u><i><a><img><p><br><strong><em><font><tr><blockquote><h1><h2><h3><h4>"); //supprime tous les tags sauf ceux reconnus
		$html=str_replace("\n",' ',$html); //remplace retour a la ligne par un espace
		$a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE); //eclate la chaine avec les balises
		foreach($a as $i=>$e)
		{
			if($i%2==0)
			{
				//Text
				if($this->HREF)
					$this->PutLink($this->HREF,$e);
				else
					$this->Write(5,stripslashes(txtentities($e)));
			}
			else
			{
				//Tag
				if($e[0]=='/')
					$this->CloseTag(strtoupper(substr($e,1)));
				else
				{
					//Extract attributes
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v)
					{
						if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
							$attr[strtoupper($a3[1])]=$a3[2];
					}
					$this->OpenTag($tag,$attr);
				}
			}
		}
		if ($x > 0)
			$this->lMargin = $lm;
		if ($y > 0)
			$this->tMargin = $tm;
		if ($w > 0)
			$this->rMargin = $rm;
		if ($h > 0)
			$this->bMargin = $bm;
	}

	function OpenTag($tag, $attr)
	{
		//Opening tag
		switch($tag){
			case 'STRONG':
				$this->SetStyle('B',true);
				break;
			case 'EM':
				$this->SetStyle('I',true);
				break;
			case 'B':
			case 'I':
			case 'U':
				$this->SetStyle($tag,true);
				break;
			case 'A':
				$this->HREF=$attr['HREF'] ?? "#";
				break;
			case 'IMG':
				if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
					if(!isset($attr['WIDTH']))
						$attr['WIDTH'] = 0;
					if(!isset($attr['HEIGHT']))
						$attr['HEIGHT'] = 0;
					$this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
				}
				break;
			case 'TR':
			case 'BLOCKQUOTE':
			case 'BR':
				$this->Ln(5);
				break;
			case 'P':
				$this->Ln(5);
				break;
			case 'FONT':
				if (isset($attr['COLOR']) && $attr['COLOR']!='') {
					$coul=hex2dec($attr['COLOR']);
					$this->SetTextColor($coul['R'],$coul['V'],$coul['B']);
					$this->issetcolor=true;
				}
				if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
					$this->SetFont(strtolower($attr['FACE']));
					$this->issetfont=true;
				}
				break;
			case 'H1':
				$this->tmpfontsize = $this->FontSizePt;
				$this->tmpfontstyle = $this->FontStyle;
				$this->Ln($this->FontSizePt);
				$this->SetFontSize(2*$this->FontSizePt);
				$this->SetStyle("B", true);
				break;
			case 'H2':
				$this->tmpfontsize = $this->FontSizePt;
				$this->tmpfontstyle = $this->FontStyle;
				$this->Ln($this->FontSizePt);
				$this->SetFontSize(1.8*$this->FontSizePt);
				$this->SetStyle("B", true);
				break;
			case 'H3':
				$this->tmpfontsize = $this->FontSizePt;
				$this->tmpfontstyle = $this->FontStyle;
				$this->Ln($this->FontSizePt);
				$this->SetFontSize(1.6*$this->FontSizePt);
				$this->SetStyle("B", true);
				break;
			case 'H4':
				$this->tmpfontsize = $this->FontSizePt;
				$this->tmpfontstyle = $this->FontStyle;
				$this->Ln($this->FontSizePt);
				$this->SetFontSize(1.4*$this->FontSizePt);
				$this->SetStyle("B", true);
				break;
		}
	}

	function CloseTag($tag)
	{
		//Closing tag
		if($tag=='STRONG')
			$tag='B';
		if($tag=='EM')
			$tag='I';
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF='';
		if($tag=='FONT'){
			if ($this->issetcolor==true) {
				$this->SetTextColor(0);
			}
			if ($this->issetfont) {
				$this->SetFont('arial');
				$this->issetfont=false;
			}
		}
		if($tag=='P')
			$this->Ln(5);
		if($tag=='H1' or $tag=='H2' or $tag=='H3' or $tag=='H4')
		{
			$this->SetFontSize($this->tmpfontsize);
			$this->SetStyle("B", false);
			$this->Ln(5);
		}
	}

	function SetStyle($tag, $enable)
	{
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(array('B','I','U') as $s)
		{
			if($this->$s>0)
				$style.=$s;
		}
		$this->SetFont('',$style);
	}

	function PutLink($URL, $txt)
	{
		//Put a hyperlink
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write(5,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}
// end writeHtml code

// begin multicolumn code
	//Current column
	var $col=0;
	//Ordinate of column start
	var $y0;
	var $colNr, $colWidth;

	function SetColNr($nr)
	{
		$this->colNr = $nr;
		$this->colWidth = ($this->w-$this->lMargin-$this->rMargin)/$nr - 4;
		$this->y0=$this->GetY();
		$this->SetCol(0);
		$this->_lm = $this->lMargin;
	}

	function SetCol($col)
	{
	    //Set position at a given column
	    $this->col=$col;
	    $x=10+$col*($this->colWidth+5);
	    $r = $this->w - $x - $this->colWidth;
	    $this->SetLeftMargin($x);
	    $this->SetRightMargin($r);
	    $this->SetX($x);
	}

	function AcceptPageBreak()
	{
	    //Method accepting or not automatic page break
	    if($this->col<$this->colNr-1)
	    {
	        //Go to next column
	        $this->SetCol($this->col+1);
	        //Set ordinate to top
	        $this->SetY($this->y0);
	        //Keep on page
	        return false;
	    }
	    else
	    {
	        //Go back to first column
	        $this->SetCol(0);
	        //Page break
	        return true;
	    }
	}
// end multicolumn code
};
