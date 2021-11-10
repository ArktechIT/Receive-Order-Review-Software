<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	require('Libraries/PHP/FPDF/fpdf.php');
	ini_set('display_errors', 'on');

	$batchId = $_GET['batchId'];
	//~ $batchId = 20200717093711;

	$pdf = new FPDF('L','mm','A4');
	//~ $pdf->SetLeftMargin(3);
	//~ $pdf->SetTopMargin(5);
	//~ $pdf->SetAutoPageBreak('off');
	$pdf->AddPage();
	
	$pdf->SetFont('Arial', 'B',9);
	$pdf->Cell(20,5,'Date : ',0,0,'R');
	$pdf->SetFont('Arial', '',9);
	$pdf->Cell(0,5,date('Y-m-d'),0,0,'L');
	$pdf->Ln();
	
	$pdf->SetFont('Arial', 'B',9);
	$pdf->Cell(20,5,'Customer : ',0,0,'R');
	$pdf->SetFont('Arial', '',9);
	$customerX = $pdf->GetX();
	$customerY = $pdf->GetY();
	$pdf->Ln();
	
	$pdf->SetFont('Arial', 'B',9);
	$pdf->Cell(20,5,'PO Number : ',0,0,'R');
	$pdf->SetFont('Arial', '',9);
	$poNumberX = $pdf->GetX();
	$poNumberY = $pdf->GetY();
	
	$pdf->Ln(10);
	
	$pdf->SetFont('Arial', 'B',9);
	$pdf->Cell(10,5,'#',1,0,'C');
	$pdf->Cell(35.75,5,'Type',1,0,'C');
	$pdf->Cell(30.75,5,'Thickness',1,0,'C');
	$pdf->Cell(29.75,5,'Length',1,0,'C');
	$pdf->Cell(29.75,5,'Width',1,0,'C');
	$pdf->Cell(43,5,'Treatment',1,0,'C');
	$pdf->Cell(10,5,'PVC',1,0,'C');
	$pdf->Cell(38,5,'Requirement',1,0,'C');
	//~ $pdf->Cell(15,5,'Sheet',1,0,'C');
	$pdf->Cell(15,5,'For PO',1,0,'C');
	$pdf->Cell(20,5,'ARRIVAL',1,0,'C');
	$pdf->Ln();
	
	$pdf->SetFont('Arial', '',9);
	
	$materialComputationIdArray = $customerAliasArray = array();
	
	$count = 0;
	$sql = "SELECT GROUP_CONCAT(materialComputationId) as materialComputationIds FROM ppic_materialcomputation WHERE batchId = ".$batchId." GROUP BY `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`";
	$queryMaterialComputationGroup = $db->query($sql);
	if($queryMaterialComputationGroup AND $queryMaterialComputationGroup->num_rows > 0)
	{
		while($resultMaterialComputationGroup = $queryMaterialComputationGroup->fetch_assoc())
		{
			$materialComputationIds = $resultMaterialComputationGroup['materialComputationIds'];
			
			$index = 0;
			
			$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `dateNeeded`, `productId`, `materialComputationIdSource` FROM ppic_materialcomputation WHERE materialComputationId IN(".$materialComputationIds.")";
			$queryMaterialComputation = $db->query($sql);
			if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
			{
				$lastIndex = $queryMaterialComputation->num_rows;
				
				$height = $lastIndex * 5;
				while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
				{
					$index++;
					
					$materialComputationId = $resultMaterialComputation['materialComputationId'];
					$customerAliasArray[] = $resultMaterialComputation['customerAlias'];
					$materialType = $resultMaterialComputation['materialType'];
					$thickness = $resultMaterialComputation['thickness'];
					$length = $resultMaterialComputation['length'];
					$width = $resultMaterialComputation['width'];
					$quantity = $resultMaterialComputation['quantity'];
					$finalQuantity = $resultMaterialComputation['finalQuantity'];
					$treatment = $resultMaterialComputation['treatment'];
					$pvc = $resultMaterialComputation['pvc'];
					$arrival = $resultMaterialComputation['dateNeeded'];
					$productId = $resultMaterialComputation['productId'];
					$materialComputationIdSource = $resultMaterialComputation['materialComputationIdSource'];
					
					$materialComputationId = ($materialComputationIdSource > 0) ? $materialComputationIdSource : $materialComputationId; 
					
					$materialComputationIdArray[] = $materialComputationId;
					
					$pvcValue = ($pvc=='0') ? 'No' : 'Yes';
					
					$requirement = 0;
					$sql = "SELECT SUM(requirement) as totalRequirement FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
					$queryMaterialComputationDetails = $db->query($sql);
					if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
					{
						$resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc();
						$requirement = $resultMaterialComputationDetails['totalRequirement'];
					}
					
					$border = 1;
					if($lastIndex > 1)
					{
						$border = 0;
						if($index==1)	$border = 'RTL';
						else if($index==$lastIndex)	$border = 'RBL';
					}
					
					$materialTreatmentId = '';
					$sql = "SELECT supplyId FROM purchasing_supplierproductlinking WHERE productId = ".$productId." AND supplyType = 1 LIMIT 1";
					$querySupplies = $db->query($sql);
					if($querySupplies AND $querySupplies->num_rows > 0)
					{
						$resultSupplies = $querySupplies->fetch_assoc();
						$materialTreatmentId = $resultSupplies['supplyId'];
					}
					
					$sql = "
						SELECT a.materialType FROM engineering_materialtype as a
						INNER JOIN cadcam_materialspecs as b ON b.materialTypeId = a.materialTypeId
						INNER JOIN purchasing_material as c ON c.materialSpecId = b.materialSpecId
						INNER JOIN purchasing_materialtreatment as d ON d.materialId = c.materialId
						WHERE d.materialTreatmentId = ".$materialTreatmentId." LIMIT 1
					";
					$queryMaterial = $db->query($sql);
					if($queryMaterial AND $queryMaterial->num_rows > 0)
					{
						$resultMaterial = $queryMaterial->fetch_assoc();
						$materialType = $resultMaterial['materialType'];
					}
					
					
					if($index==1)
					{
						$border = 1;
						
						$pdf->Cell(10,$height,++$count,$border,0,'C');
						$pdf->Cell(35.75,$height,$materialType,$border,0,'C');
						$pdf->Cell(30.75,$height,$thickness,$border,0,'C');
						$pdf->Cell(29.75,$height,$length,$border,0,'C');
						$pdf->Cell(29.75,$height,$width,$border,0,'C');
						$pdf->Cell(43,$height,$treatment,$border,0,'L');
						$pdf->Cell(10,$height,$pvcValue,$border,0,'L');
						$pdf->Cell(38,$height,$requirement,$border,0,'C');
						//~ $pdf->Cell(15,5,$quantity,$border,0,'C');
						$pdf->Cell(15,5,$finalQuantity,1,0,'C');
						$pdf->Cell(20,5,$arrival,1,0,'C');
					}
					else
					{
						$border = 0;
						
						$pdf->Cell(10,5,'',$border,0,'C');
						$pdf->Cell(35.75,5,'',$border,0,'C');
						$pdf->Cell(30.75,5,'',$border,0,'C');
						$pdf->Cell(29.75,5,'',$border,0,'C');
						$pdf->Cell(29.75,5,'',$border,0,'C');
						$pdf->Cell(43,5,'',$border,0,'L');
						$pdf->Cell(10,5,'',$border,0,'L');
						$pdf->Cell(38,5,'',$border,0,'C');
						//~ $pdf->Cell(15,5,'',$border,0,'C');
						$pdf->Cell(15,5,$finalQuantity,1,0,'C');
						$pdf->Cell(20,5,$arrival,1,0,'C');
					}
					
					$pdf->Ln(5);
				}
			}
		}
	}
	
	$pdf->Ln(10);
	
	//~ $pdf->SetFont('Arial', 'B',9);
	//~ $pdf->Cell(10,5,'#',1,0,'C');
	//~ $pdf->Cell(35.75,5,'Lot Number',1,0,'C');
	//~ $pdf->Cell(35.75,5,'Part Number',1,0,'C');
	//~ $pdf->Cell(29.75,5,'Quantity',1,0,'C');
	//~ $pdf->Cell(29.75,5,'Material Type',1,0,'C');
	//~ $pdf->Cell(43,5,'Requirement',1,0,'C');
	//~ $pdf->Ln();
	
	$pdf->SetFont('Arial', '',9);
	
	$lotNumberArray = array();
	$count = 0;
	$sql = "
		SELECT a.lotNumber, a.workingQuantity, a.requirement, b.materialType, d.partNumber FROM ppic_materialcomputationdetails as a
		INNER JOIN ppic_materialcomputation as b ON b.materialComputationId = a.materialComputationId
		INNER JOIN ppic_lotlist as c ON c.lotNumber = a.lotNumber AND c.identifier = 1
		INNER JOIN cadcam_parts as d ON d.partId = c.partId
		WHERE a.materialComputationId IN(".implode(",",$materialComputationIdArray).")
	";
	$queryMaterialComputationDetails = $db->query($sql);
	if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
	{
		while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
		{
			$lotNumber = $resultMaterialComputationDetails['lotNumber'];
			$workingQuantity = $resultMaterialComputationDetails['workingQuantity'];
			$requirement = $resultMaterialComputationDetails['requirement'];
			$materialType = $resultMaterialComputationDetails['materialType'];
			$partNumber = $resultMaterialComputationDetails['partNumber'];
			
			$lotNumberArray[] = $lotNumber;
			
			//~ $pdf->Cell(10,5,++$count,1,0,'C');
			//~ $pdf->Cell(35.75,5,$lotNumber,1,0,'C');
			//~ $pdf->Cell(35.75,5,$partNumber,1,0,'C');
			//~ $pdf->Cell(29.75,5,$workingQuantity,1,0,'C');
			//~ $pdf->Cell(29.75,5,$materialType,1,0,'C');
			//~ $pdf->Cell(43,5,$requirement,1,0,'L');
			
			//~ $pdf->Ln();
		}
	}
	
	$pdf->Ln(10);
	
	$pdf->SetFont('Arial', 'B',9);
	$pdf->Cell(50,5,'Prepared by :',0,0,'L');
	$pdf->Cell(75,5,'',0,0,'C');
	$pdf->Cell(50,5,'Confirmed by : (Purchasing Personnel)',0,0,'L');
	$pdf->Ln();
	$pdf->Cell(50,5,'','B',0,'L');
	$pdf->Cell(75,5,'',0,0,'C');
	$pdf->Cell(50,5,'','B',0,'L');
	
	$poNumberArray = array();
	$sql = "
		SELECT b.poNumber FROM ppic_lotlist as a
		INNER JOIN sales_polist as b ON b.poId = a.poId
		WHERE a.lotNumber IN('".implode("','",$lotNumberArray)."')
		GROUP BY b.poNumber
	";
	$queryPoNumber = $db->query($sql);
	if($queryPoNumber AND $queryPoNumber->num_rows > 0)
	{
		while($resultPoNumber = $queryPoNumber->fetch_assoc())
		{
			$poNumberArray[] = $resultPoNumber['poNumber'];
		}
	}
	
	$customerAliasArray = array_unique($customerAliasArray);
	
	$customers = implode(", ",$customerAliasArray);
	$pdf->SetXY($customerX,$customerY);
	$pdf->Cell(0,5,$customers,0,0,'L');
	
	$poNumbers = implode(", ",$poNumberArray);
	$pdf->SetXY($poNumberX,$poNumberY);
	$pdf->Cell(0,5,$poNumbers,0,0,'L');
	
	$pdf->Output();
?>
