<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	$submitType = (isset($_POST['submitType'])) ? $_POST['submitType'] : '';
	$groupNo = $_POST['groupNo'];
	$sqlFilter = $_POST['sqlFilter'];
	$queryLimit = 50;
	$queryPosition = ($groupNo * $queryLimit);
	
	$endQuery = "LIMIT ".$queryPosition.", ".$queryLimit;
	if($submitType!='')	$endQuery = "";
	
	$requirementArray = $materialSpecIdArray = array();
	$count = $queryPosition;
	$sql = "SELECT poId, partId, SUM(workingQuantity) as workingQuantity, partLevel FROM ppic_lotlist ".$sqlFilter." ".$endQuery;
	$sqlMain = $sql;
	$query = $db->query($sql);
	if($query->num_rows > 0)
	{
		//~ $tableContent = "<tr><td colspan='14'>".$sqlMain."</td></tr>";
		while($result = $query->fetch_array())
		{
			$lotNumber = $result['lotNumber'];
			$poId = $result['poId'];
			$partId = $result['partId'];
			$workingQuantity = $result['workingQuantity'];
			$partLevel = $result['partLevel'];			
			
			$poNumber = '';
			$sql = "SELECT poNumber FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				$resultPoList = $queryPoList->fetch_assoc();
				$poNumber = $resultPoList['poNumber'];
			}
			
			$partNumber = $revisionId = $customerId = $materialSpecId = $PVC = $x = $y = $treatmentId = '';
			$requiredLength = $requiredWidth = 0;
			$sql = "SELECT partNumber, revisionId, customerId, materialSpecId, PVC, x, y, treatmentId, requiredLength, requiredWidth FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_assoc();
				$partNumber = $resultParts['partNumber'];
				$revisionId = $resultParts['revisionId'];
				$customerId = $resultParts['customerId'];
				$materialSpecId = $resultParts['materialSpecId'];
				$PVC = $resultParts['PVC'];
				$x = $resultParts['x'];
				$y = $resultParts['y'];
				$treatmentId = $resultParts['treatmentId'];
				$requiredLength = $resultParts['requiredLength'];
				$requiredWidth = $resultParts['requiredWidth'];
			}
			
			$customerAlias = '';
			$sql = "SELECT customerAlias FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
			$queryCustomer = $db->query($sql);
			if($queryCustomer AND $queryCustomer->num_rows > 0)
			{
				$resultCustomer = $queryCustomer->fetch_assoc();
				$customerAlias = $resultCustomer['customerAlias'];
			}
			
			$materialTypeId = $metalThickness = '';
			$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
			$queryMaterialSpecs = $db->query($sql);
			if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
			{
				$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
				$materialTypeId = $resultMaterialSpecs['materialTypeId'];
				$metalThickness = $resultMaterialSpecs['metalThickness'];
			}
			
			$materialType = '';
			$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
			$queryMaterialType = $db->query($sql);
			if($queryMaterialType AND $queryMaterialType->num_rows > 0)
			{
				$resultMaterialType = $queryMaterialType->fetch_assoc();
				$materialType = $resultMaterialType['materialType'];
			}
			
			$treatmentName = '';
			$sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId = ".$treatmentId." LIMIT 1";
			$queryTreatmentProcess = $db->query($sql);
			if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
			{
				$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
				$treatmentName = $resultTreatmentProcess['treatmentName'];
			}
			
			$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode = 1 LIMIT 1";
			$queryCheckBend = $db->query($sql);
			$bendStatus = ($queryCheckBend->num_rows > 0) ? 'Yes' : 'No';
			
			$pvcStatus = ($PVC=='1') ? 'Yes' : 'No';
			
			if($partLevel > 1)	$poNumber = '';
			
			$processCode = '';
			$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode IN(86,52,381,98,392) LIMIT 1";
			$queryPartProcess = $db->query($sql);
			if($queryPartProcess->num_rows > 0)
			{
				$resultPartProcess = $queryPartProcess->fetch_array();
				$processCode = $resultPartProcess['processCode'];
			}
			
			$blankingProcess = '';
			if($processCode==86)
			{
				$blankingProcess = 'TPP';
			}
			else if($processCode==381)
			{
				$blankingProcess = 'Laser';
			}
			else if($processCode==52)
			{
				$blankingProcess = 'Press';
			}
			else if(in_array($processCode,array(328,98,392)))
			{
				$blankingProcess = 'Cutting';
			}
			
			$matLength = ($requiredLength > 0) ? $requiredLength : '';
			$matWidth = ($requiredWidth > 0) ? $requiredWidth : '';
			
			if($matLength=='' AND $matWidth=='' AND $blankingProcess!='')
			{
				//material sizes
				if($customerId==45)
				{
					if($materialType=='2024T3P')
					{
						$matLength = 2438;
						$matWidth = 1219;
					}
					else
					{
						$matLength = 2000;
						$matWidth = 1000;
					}
				}
				else
				{
					if(in_array($metalThickness, array(1.5,0.8)) AND $materialType == '1050A H14')
					{
						$matLength = 2500;
						$matWidth = 1250;
					}
					else if(in_array($metalThickness, array(0.7,0.5,0.71,0.8,2.5,1.2)) AND $materialType == '6082T6')
					{
						$matLength = 2000;
						$matWidth = 1000;
					}
					else if(in_array($metalThickness, array(1.5,3.0,1.0,2.0)) AND $materialType == '6082T6')
					{
						// if($blanking == 'Laser')
						// {
							$matLength = 2500;
							$matWidth = 1250;
						// }
						// else if($blanking == 'TPP')
						// {
						// 	$length = 1250;
						// 	$width = 625;
						// }
						
					}
					else if($metalThickness == 0.9 AND $materialType == '6082T6')
					{
						$matLength = 1524;
						$matWidth = 609;
					}
					else if(in_array($metalThickness, array(1.0,0.7)) AND in_array($materialType, array('MS2007','MS2009')))
					{
						$matLength = 1220;
						$matWidth = 609;
					}
					else if(in_array($metalThickness, array(0.8,1.0,0.7,1.6,0.9,1.2,1.5,0.5)) AND in_array($materialType, array('SUS 304','SUS 304L','SUS 304 2B','SUS304 SGB3')))
					{
						$matLength = 2500;
						$matWidth = 1250;
					}
					else if($materialType == '2024-T3')
					{
						$matLength = 3657;
						$matWidth = 1219;
					}
					else if($materialType == 'SPHC')
					{
						$matLength = 1254;
						$matWidth = 1219;
					}
					else if(in_array($materialType, array('SPCC-SD','SPCC')))
					{
						$matLength = 1219;
						$matWidth = 1000;
					}
					else if(in_array($metalThickness, array(1.2, 1.0)) AND in_array($materialType, array('SECC1', 'SECC')))
					{
						$matLength = 1219;
						$matWidth = 1000;
					}

					if(trim($matLength)=="" or trim($matWidth)=="" or $materialType=="")
					{
						$materialTypeId = '';
						$sql = "SELECT suppliermaterialID FROM purchasing_materialtype WHERE materialType LIKE '".$materialType."' LIMIT 1";
						$queryMaterialType = $db->query($sql);
						if($queryMaterialType AND $queryMaterialType->num_rows > 0)
						{
							$resultMaterialType = $queryMaterialType->fetch_assoc();
							$materialTypeId = $resultMaterialType['suppliermaterialID'];
						}
						
						$sql = "SELECT length, width FROM purchasing_material WHERE materialTypeId = ".$materialTypeId." AND thickness = ".$metalThickness." AND length >= ".$x." AND width >= ".$y." LIMIT 1";
						$queryMaterial = $db->query($sql);
						if($queryMaterial AND $queryMaterial->num_rows > 0)
						{
							$resultMaterial = $queryMaterial->fetch_assoc();
							$matLength = $resultMaterial['length'];
							$matWidth = $resultMaterial['width'];
						}
					}
				}
			}									
			
			$qtyPerSheet = $requirement = 0;
			if($matLength > 0 AND $matWidth > 0 AND $blankingProcess!='')
			{
				$qtyPerSheet = computeQtyPerSheet($x,$y,$matLength,$matWidth,$blankingProcess,$customerId);
				$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0;
			}
			
			if($requirement > 0)
			{
				$arrayKey = $materialType."`".$metalThickness."`".$matLength."`".$matWidth."`".$treatmentName."`".$pvcStatus;
				
				if(!isset($requirementArray[$arrayKey])) $requirementArray[$arrayKey] = 0;
				$requirementArray[$arrayKey] += $requirement;
			}
			
			if(!in_array($materialSpecId,$materialSpecIdArray) AND $materialSpecId != 1)
			{
				$materialSpecIdArray[] = $materialSpecId;
			}
			
			$tableContent .= "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$customerAlias."</td>
					<td>".$poNumber."</td>
					<td>".$partNumber." ".$blankingProcess."</td>
					<td>".$revisionId."</td>
					<td align='right'>".$workingQuantity."</td>
					<td>".$materialType."</td>
					<td>".$metalThickness."</td>
					<td>".$treatmentName."</td>
					<td align='right'>".$x."</td>
					<td align='right'>".$y."</td>
					<td>".$bendStatus."</td>
					<td>".$pvcStatus."</td>
					<td><input type='number' data-part-id='".$partId."' class='length api-form' name='matLength[]' value='".$matLength."' style='width:100px;' step='any' form='computeFormId'></td>
					<td><input type='number' data-part-id='".$partId."' class='width api-form' name='matWidth[]' value='".$matWidth."' style='width:100px;' step='any' form='computeFormId'></td>
					<td class='qtyPerSheet'>".$qtyPerSheet."</td>
					<td class='requirement'>".$requirement."</td>
				</tr>
			";
		}
		if($submitType=='calculate')
		{
			//~ echo "<table border='1'>".$tableContent."</table>";
			
			if(count($requirementArray) > 0)
			{
				//~ echo "<table border='1' cellpadding='0' cellspacing='0' style='float:left;width:25%;'>";	
					//~ echo "
					//~ <thead>
						//~ <tr>
							//~ <th colspan = 2>".displayText('L309')."</th>
							//~ <td></td>
						//~ </tr>
					//~ </thead>";
				$compressedInput = '';	
				foreach($requirementArray as $key=>$val)
				{
					$keyArray = explode("`",$key);
					//~ echo "
						//~ <tr>
							//~ <td>".$keyArray[4]."</td>
							//~ <td>".$keyArray[0]." ".$keyArray[1]." ".$keyArray[2]." ".$keyArray[3]." ".$keyArray[5]."</td>
							//~ <td>".ceil($val)."</td>
						//~ </tr>
					//~ ";
					
					if($compressedInput == "")
					{
						$compressedInput = $keyArray[0]."`".$keyArray[1]."`".$keyArray[2]."`".$keyArray[3]."`".ceil($val)."`".$keyArray[4]."`".$keyArray[5];
					}
					else
					{
						$compressedInput = $compressedInput."`".$keyArray[0]."`".$keyArray[1]."`".$keyArray[2]."`".$keyArray[3]."`".ceil($val)."`".$keyArray[4]."`".$keyArray[5];
					}			
				}
				//~ echo "</table>";
				//~ echo "<form action = '/V3/3-4 Material Requirements Computation Software/anthony_computationSummaryConverter.php' method = 'POST' id = 'print' ></form>";
				echo "<form action = '/".v."/54 Automated Material Computation Software/anthony_computationSummaryConverter.php' method = 'POST' id = 'print' ></form>";
				echo "<input type = 'hidden' name = 'specId' value = '".$compressedInput."' form='print'>";
				echo "<input type = 'hidden' id='printId' src = '/".v."/Common Data/Templates/buttons/printIcon.png' height = '35' width = '50' form='print'>";
				?><script>document.getElementById('print').submit();</script><?php
			}
		}
		else
		{		
			echo $tableContent;
		}
	}
					
?>
