<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);
include('PHP Modules/mysqliConnection.php');
include('classes/fpdf.php');
include('fpdi/fpdi.php');
ini_set("display_errors", "on");



class PDF extends FPDI
{
	function Footer()
	{  		
		$this->SetY(-15);   
		$this->SetFont('Arial','I',8); 
		$this->Cell(50,5,'Page '.$this->PageNo().'/{nb}',0,0,'L');		
		
		if($_GET['country']=="1")
		{
			$this->Cell(112.5,5,' ***This is a system generated document*** -(Printed:'.date('Y-M-d').')  - By:'.$_SESSION['userID'],0,0,'C');
		}
		else
		{			
			$this->SetFont('Arial','I',8); 
			$this->Cell(91,5,' ***This is a system generated document*** -(Printed:'.date('Y-M-d').')  - By:',0,0,'C');
			$this->SetFont('SJIS','',8);
			$this->Cell(11,5,$_SESSION['userID'],0,0,'L');
		}
	}

///---JAPANESE
function AddCIDFont($family, $style, $name, $cw, $CMap, $registry)
{
	$fontkey=strtolower($family).strtoupper($style);
	if(isset($this->fonts[$fontkey]))
		$this->Error("CID font already added: $family $style");
	$i=count($this->fonts)+1;
	$this->fonts[$fontkey]=array('i'=>$i,'type'=>'Type0','name'=>$name,'up'=>-120,'ut'=>40,'cw'=>$cw,'CMap'=>$CMap,'registry'=>$registry);
}

function AddCIDFonts($family, $name, $cw, $CMap, $registry)
{
	$this->AddCIDFont($family,'',$name,$cw,$CMap,$registry);
	$this->AddCIDFont($family,'B',$name.',Bold',$cw,$CMap,$registry);
	$this->AddCIDFont($family,'I',$name.',Italic',$cw,$CMap,$registry);
	$this->AddCIDFont($family,'BI',$name.',BoldItalic',$cw,$CMap,$registry);
}

function AddSJISFont($family='SJIS')
{
	// Add SJIS font with proportional Latin
	$name='KozMinPro-Regular-Acro';
	$cw=$GLOBALS['SJIS_widths'];
	$CMap='90msp-RKSJ-H';
	$registry=array('ordering'=>'Japan1','supplement'=>2);
	$this->AddCIDFonts($family,$name,$cw,$CMap,$registry);
}

function AddSJIShwFont($family='SJIS-hw')
{
	// Add SJIS font with half-width Latin
	$name='KozMinPro-Regular-Acro';
	for($i=32;$i<=126;$i++)
		$cw[chr($i)]=500;
	$CMap='90ms-RKSJ-H';
	$registry=array('ordering'=>'Japan1','supplement'=>2);
	$this->AddCIDFonts($family,$name,$cw,$CMap,$registry);
}

function GetStringWidth($s)
{
	if($this->CurrentFont['type']=='Type0')
		return $this->GetSJISStringWidth($s);
	else
		return parent::GetStringWidth($s);
}

function GetSJISStringWidth($s)
{
	// SJIS version of GetStringWidth()
	$l=0;
	$cw=&$this->CurrentFont['cw'];
	$nb=strlen($s);
	$i=0;
	while($i<$nb)
	{
		$o=ord($s[$i]);
		if($o<128)
		{
			// ASCII
			$l+=$cw[$s[$i]];
			$i++;
		}
		elseif($o>=161 && $o<=223)
		{
			// Half-width katakana
			$l+=500;
			$i++;
		}
		else
		{
			// Full-width character
			$l+=1000;
			$i+=2;
		}
	}
	return $l*$this->FontSize/1000;
}

function MultiCell($w, $h, $txt, $border=0, $align='L', $fill=false)
{
	if($this->CurrentFont['type']=='Type0')
		$this->SJISMultiCell($w,$h,$txt,$border,$align,$fill);
	else
		parent::MultiCell($w,$h,$txt,$border,$align,$fill);
}

function SJISMultiCell($w, $h, $txt, $border=0, $align='L', $fill=false)
{
	// Output text with automatic or explicit line breaks
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 && $s[$nb-1]=="\n")
		$nb--;
	$b=0;
	if($border)
	{
		if($border==1)
		{
			$border='LTRB';
			$b='LRT';
			$b2='LR';
		}
		else
		{
			$b2='';
			if(is_int(strpos($border,'L')))
				$b2.='L';
			if(is_int(strpos($border,'R')))
				$b2.='R';
			$b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
		}
	}
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		// Get next character
		$c=$s[$i];
		$o=ord($c);
		if($o==10)
		{
			// Explicit line break
			$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
			if($border && $nl==2)
				$b=$b2;
			continue;
		}
		if($o<128)
		{
			// ASCII
			$l+=$cw[$c];
			$n=1;
			if($o==32)
				$sep=$i;
		}
		elseif($o>=161 && $o<=223)
		{
			// Half-width katakana
			$l+=500;
			$n=1;
			$sep=$i;
		}
		else
		{
			// Full-width character
			$l+=1000;
			$n=2;
			$sep=$i;
		}
		if($l>$wmax)
		{
			// Automatic line break
			if($sep==-1 || $i==$j)
			{
				if($i==$j)
					$i+=$n;
				$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
			}
			else
			{
				$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
				$i=($s[$sep]==' ') ? $sep+1 : $sep;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
			if($border && $nl==2)
				$b=$b2;
		}
		else
		{
			$i+=$n;
			if($o>=128)
				$sep=$i;
		}
	}
	// Last chunk
	if($border && is_int(strpos($border,'B')))
		$b.='B';
	$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
	$this->x=$this->lMargin;
}

function Write($h, $txt, $link='')
{
	if($this->CurrentFont['type']=='Type0')
		$this->SJISWrite($h,$txt,$link);
	else
		parent::Write($h,$txt,$link);
}

function SJISWrite($h, $txt, $link)
{
	// SJIS version of Write()
	$cw=&$this->CurrentFont['cw'];
	$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		// Get next character
		$c=$s[$i];
		$o=ord($c);
		if($o==10)
		{
			// Explicit line break
			$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			if($nl==1)
			{
				// Go to left margin
				$this->x=$this->lMargin;
				$w=$this->w-$this->rMargin-$this->x;
				$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			}
			$nl++;
			continue;
		}
		if($o<128)
		{
			// ASCII
			$l+=$cw[$c];
			$n=1;
			if($o==32)
				$sep=$i;
		}
		elseif($o>=161 && $o<=223)
		{
			// Half-width katakana
			$l+=500;
			$n=1;
			$sep=$i;
		}
		else
		{
			// Full-width character
			$l+=1000;
			$n=2;
			$sep=$i;
		}
		if($l>$wmax)
		{
			// Automatic line break
			if($sep==-1 || $i==$j)
			{
				if($this->x>$this->lMargin)
				{
					// Move to next line
					$this->x=$this->lMargin;
					$this->y+=$h;
					$w=$this->w-$this->rMargin-$this->x;
					$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
					$i+=$n;
					$nl++;
					continue;
				}
				if($i==$j)
					$i+=$n;
				$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
			}
			else
			{
				$this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',0,$link);
				$i=($s[$sep]==' ') ? $sep+1 : $sep;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			if($nl==1)
			{
				$this->x=$this->lMargin;
				$w=$this->w-$this->rMargin-$this->x;
				$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			}
			$nl++;
		}
		else
		{
			$i+=$n;
			if($o>=128)
				$sep=$i;
		}
	}
	// Last chunk
	if($i!=$j)
		$this->Cell($l/1000*$this->FontSize,$h,substr($s,$j,$i-$j),0,0,'',0,$link);
}

function _putType0($font)
{
	// Type0
	$this->_newobj();
	$this->_out('<</Type /Font');
	$this->_out('/Subtype /Type0');
	$this->_out('/BaseFont /'.$font['name'].'-'.$font['CMap']);
	$this->_out('/Encoding /'.$font['CMap']);
	$this->_out('/DescendantFonts ['.($this->n+1).' 0 R]');
	$this->_out('>>');
	$this->_out('endobj');
	// CIDFont
	$this->_newobj();
	$this->_out('<</Type /Font');
	$this->_out('/Subtype /CIDFontType0');
	$this->_out('/BaseFont /'.$font['name']);
	$this->_out('/CIDSystemInfo <</Registry (Adobe) /Ordering ('.$font['registry']['ordering'].') /Supplement '.$font['registry']['supplement'].'>>');
	$this->_out('/FontDescriptor '.($this->n+1).' 0 R');
	$W='/W [1 [';
	//foreach($font['cw'] as $w)
	//	$W.=$w.' ';
	$this->_out($W.'] 231 325 500 631 [500] 326 389 500]');
	$this->_out('>>');
	$this->_out('endobj');
	// Font descriptor
	$this->_newobj();
	$this->_out('<</Type /FontDescriptor');
	$this->_out('/FontName /'.$font['name']);
	$this->_out('/Flags 6');
	$this->_out('/FontBBox [0 -200 1000 900]');
	$this->_out('/ItalicAngle 0');
	$this->_out('/Ascent 800');
	$this->_out('/Descent -200');
	$this->_out('/CapHeight 800');
	$this->_out('/StemV 60');
	$this->_out('>>');
	$this->_out('endobj');
}
	
	
}
$reviewId = (isset($_GET['reviewId'])) ? $_GET['reviewId'] : "";
$pdf=new PDF('P','mm','A4');
$pdf->AddSJISFont();
$pdf->AddPage();
$pdf->AliasNbPages();
$pdf->SetFont('Arial','B',10);
$db->set_charset("sjis");

$pdf->Cell(55,5,'Date: '.date('Y-M-d'),0,0);
//$pdf->Cell(125,5,'',0,0);
$pdf->Cell(25,5,'',0,0);
$pdf->Cell(80,5,'RO Review#: ',0,0,'R');
$pdf->Cell(20,5,$reviewId,1,0,'L');
$pdf->Ln();
//$pdf->Cell(275,5,'RO Review PrintOut',0,0,'C');
$pdf->Cell(175,5,'RO Review PrintOut',0,0,'C');
$pdf->Ln();

$pdf->SetFont('Arial','',11);
//$pdf->Line(10,20,285,20); $pdf->Ln();
$pdf->Line(10,20,185,20); $pdf->Ln();


$pdf->SetFont('Arial','',8);
$pdf->Cell(10,5,"#",'TLR',0,'C');
$pdf->Cell(20,5,"Customer",'TLR',0,'C');
$pdf->Cell(20,5,"PO",'TLR',0,'C');
$pdf->Cell(20,5,"Lot",'TLR',0,'C');
$pdf->Cell(35,5,"Part Number",'TLR',0,'C');
$pdf->Cell(15,5,"Qty",'TLR',0,'C');
$pdf->Cell(20,5,"Customer",'TLR',0,'C');
$pdf->Cell(40,5,"Remarks",'TLR',0,'C');
/*
$pdf->Cell(20,5,"Design",'TLR',0,'C');
$pdf->Cell(20,5,"Production",'TLR',0,'C');
$pdf->Cell(20,5,"Material",'TLR',0,'C');
$pdf->Cell(20,5,"ProductionTF",'TLR',0,'C');
$pdf->Cell(20,5,"Subcon",'TLR',0,'C');
$pdf->Cell(20,5,"Receiving",'TLR',0,'C');
$pdf->Cell(20,5,"Internal",'TLR',0,'C');
*/
$pdf->Ln();
$pdf->Cell(10,5,"",'BLR',0,'C');
$pdf->Cell(20,5,"",'BLR',0,'C');
$pdf->Cell(20,5,"Number",'BLR',0,'C');
$pdf->Cell(20,5,"Number",'BLR',0,'C');
$pdf->Cell(35,5,"",'BLR',0,'C');
$pdf->Cell(15,5,"",'BLR',0,'C');
$pdf->Cell(20,5,"Delivery",'BLR',0,'C');
$pdf->Cell(40,5,"",'BLR',0,'C');
/*
$pdf->Cell(20,5,"ReviewTF",'BLR',0,'C');
$pdf->Cell(20,5,"SchedTF",'BLR',0,'C');
$pdf->Cell(20,5,"BookingTF",'BLR',0,'C');
$pdf->Cell(20,5,"",'BLR',0,'C');
$pdf->Cell(20,5,"DeliveryTF",'BLR',0,'C');
$pdf->Cell(20,5,"SubconTF",'BLR',0,'C');
$pdf->Cell(20,5,"Delivery",'BLR',0,'C');
*/
$pdf->Ln();

$e=0;
	$sql = "SELECT * FROM ppic_roreviewdata where roReviewId=".$reviewId;
	$reviewdataQuery = $db->query($sql);
	while($reviewdataResult = $reviewdataQuery->fetch_assoc())
	{
	$poId=0;
	$customerId=0;
	$partId=0;
	$poQuantity=0;
	$poNumber="";
	$customerName="";
	$partNumber="";
	$designReviewTF="";
	$productionSchedulingTF="";
	$materialBookingTF="";
	$productionTF="";
	$subconDeliveryTF="";
	$receivingSubconTF="";
	$delivery="";
	$deliveryDate="";
	$lotNumberPO="";
	
		$poId=$reviewdataResult['poId'];
		$designReviewTF=$reviewdataResult['designReviewTF'];
		$productionSchedulingTF=$reviewdataResult['productionSchedulingTF'];
		$materialBookingTF=$reviewdataResult['materialBookingTF'];
		$productionTF=$reviewdataResult['productionTF'];
		$subconDeliveryTF=$reviewdataResult['subconDeliveryTF'];
		$receivingSubconTF=$reviewdataResult['receivingSubconTF'];
		$delivery=$reviewdataResult['delivery'];
		
		$sql = "SELECT customerId, partId, poNumber, deliveryDate, poQuantity from sales_polist where poId=".$poId;
		$customerIdQuery = $db->query($sql);
		$customerIdQueryResult = $customerIdQuery->fetch_assoc();
		$customerId=$customerIdQueryResult['customerId'];
		$partId=$customerIdQueryResult['partId'];
		$poNumber=$customerIdQueryResult['poNumber'];
		$deliveryDate=$customerIdQueryResult['deliveryDate'];
		$poQuantity=$customerIdQueryResult['poQuantity'];
		
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." and partId = ".$partId."";
		$queryPoIdLot = $db->query($sql);
		if($queryPoIdLot AND $queryPoIdLot->num_rows > 0)
		{
			$resultPoIdLot= $queryPoIdLot->fetch_assoc();
			$lotNumberPO = $resultPoIdLot['lotNumber'];	
		}
		
		$sql = "SELECT customerAlias from sales_customer where customerId=".$customerId;
		$customerAliasQuery = $db->query($sql);
		$customerAliasQueryResult = $customerAliasQuery->fetch_assoc();
		$customerName=$customerAliasQueryResult['customerAlias'];
			
		
		$sql = "SELECT partNumber, partName, revisionId FROM cadcam_parts where partId=".$partId;
		$partListQuery = $db->query($sql);
		if($partListQuery->num_rows > 0)
		{
			$partListQueryResult = $partListQuery->fetch_assoc();
			$partNumber= $partListQueryResult['partNumber'];
		}
		
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(10,5,($e+1),1,0,'C');
		$pdf->Cell(20,5,$customerName,1,0,'L');
		$pdf->Cell(20,5,$poNumber,1,0,'L');
		$pdf->Cell(20,5,$lotNumberPO,1,0,'L');
		$pdf->Cell(35,5,$partNumber,1,0,'L');
		
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(15,5,$poQuantity,1,0,'L');
		$pdf->Cell(20,5,$deliveryDate,1,0,'C');
		$pdf->Cell(40,5,"",1,0,'C');
		/*
		$pdf->Cell(20,5,$designReviewTF,1,0,'C');
		$pdf->Cell(20,5,$productionSchedulingTF,1,0,'C');
		$pdf->Cell(20,5,$materialBookingTF,1,0,'C');
		$pdf->Cell(20,5,$productionTF,1,0,'C');
		$pdf->Cell(20,5,$subconDeliveryTF,1,0,'C');
		$pdf->Cell(20,5,$receivingSubconTF,1,0,'C');
		$pdf->Cell(20,5,$delivery,1,0,'C');
		*/
		$pdf->SetFont('Arial','',8);
		$pdf->Ln();
		$e++;
	}

	$sql = "SELECT * FROM ppic_roreviewdetails WHERE roReviewId=".$reviewId;
	$queryDetails = $db->query($sql);
	if($queryDetails AND $queryDetails->num_rows > 0)
	{
		$resultDetails = $queryDetails->fetch_assoc();
		$roReviewDate = $resultDetails['roReviewDate'];
	}

	
	$pdf->Ln();
	$pdf->Cell(20,5,"Prepared By: PC Staff","TLB",0,'L');
	$pdf->SetFont('Arial','UB',10);
	$pdf->Cell(40,5,"","TRB",0,'C');
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(60,5,"Approved By: Sales Supervisor",1,0,'L');	
	$pdf->Ln();
	
	
	$pdf->Ln();
	$pdf->Cell(50,5,"Review/Attended by:",1,0,'C');
	$pdf->Cell(50,5,"Signature",1,0,'C');
	$pdf->Cell(50,5,"",0,0,'C');
	//$pdf->Cell(20,5,"Prepared By:","TLB",0,'L');
	$pdf->SetFont('Arial','UB',10);
	//$pdf->Cell(40,5,"Isabel Gutierrez","TRB",0,'C');
	
	if($_GET['country']=="1")
	{
		$pdf->SetFont('Arial','',8);
	}
	else
	{
		$pdf->SetFont('SJIS','',8);
	}	
	//$pdf->Cell(60,5,"Approved By:",1,0,'L');
	$pdf->Ln();

	// if($roReviewDate >= "2018-01-01" AND $roReviewDate <= '2018-03-17')
	// {
		// $idNumberArray = Array("Roldan Macalindro", "Mario Alvarez", "Mercedita Vivero", "Jonnabel Tinamisan", "Susan Ebreo", "Leslie Mae Pamplona", "Eunice Ayroso", "Maria Joy Samantha Tabi");
		
		// for ($i=0; $i <count($idNumberArray) ; $i++)
		// { 
			// $pdf->Cell(50,5,$idNumberArray[$i],1,0,'L');	
			// $pdf->Cell(50,5,"",1,0,'C');	
			// $pdf->Ln();
		// }
	// }
	// else
	// {
		
		$sql = "SELECT employeeId FROM ppic_roreviewparticipants where roReviewId=".$reviewId;
		$participantQuery = $db->query($sql);
		while($participantaResult = $participantQuery->fetch_assoc())
		{	
			$participantNames="";
			
			if($roReviewDate >= '2018-03-18')
			{
				if($participantaResult['employeeId'] == "0458")
				{
					//$participantaResult['employeeId'] = "0352";
				}
			}

			$sql = "SELECT firstName, surName FROM hr_employee where idNumber like '".$participantaResult['employeeId']."'";
			$participantNameQuery = $db->query($sql);
			if($participantNameQuery->num_rows > 0)
			{
				$participantNameResult = $participantNameQuery->fetch_assoc();
				$participantNames= " - ".$participantNameResult['firstName']." ".$participantNameResult['surName'];
			}
			
			$pdf->Cell(50,5,$participantaResult['employeeId'].$participantNames."",1,0,'L');	
			$pdf->Cell(50,5,"",1,0,'C');	
			$pdf->Ln();		
		}
	// }
		

$pdf->Output();

?>
