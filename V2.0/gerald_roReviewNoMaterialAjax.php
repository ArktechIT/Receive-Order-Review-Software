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
	//echo "<tr><td>".$sqlFilter."</td></tr>";
	$queryLimit = 50;
	$queryPosition = ($groupNo * $queryLimit);
	
	$endQuery = "LIMIT ".$queryPosition.", ".$queryLimit;
	if($submitType!='')	$endQuery = "";
	
	$requirementArray = $poIdArray = $lotNumberArray = $requirementDataArray = $dataDateNeededArray = array();
	$count = $queryPosition;
	$sql = "SELECT lotNumber, poId, partId, SUM(workingQuantity) as workingQuantity, partLevel, patternId FROM ppic_lotlist ".$sqlFilter." ".$endQuery;
	$sqlMain = $sql;
	//echo "<tr><td>".$sqlMain."</td></tr>";
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
			$patternId = $result['patternId'];
			
			$poNumber = $customerId = '';
			$sql = "SELECT poNumber, customerId FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				$resultPoList = $queryPoList->fetch_assoc();
				$poNumber = $resultPoList['poNumber'];
				$customerId = $resultPoList['customerId'];
			}
			
			$partNumber = $revisionId = $partNote = $materialSpecId = $PVC = $x = $y = $treatmentId = '';
			$requiredLength = $requiredWidth = 0;
			$sql = "SELECT partNumber, revisionId, partNote, customerId, materialSpecId, PVC, x, y, treatmentId, requiredLength, requiredWidth FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_assoc();
				$partNumber = $resultParts['partNumber'];
				$revisionId = $resultParts['revisionId'];
				$partNote = $resultParts['partNote'];
				//$customerId = $resultParts['customerId'];
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
			
			//~ if($partLevel > 1)	$poNumber = '';
			//~ if($partLevel > 1)	$poNumber = $lotNumber;
			
			$dateNeededTemp = '0000-00-00';
			$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(312,430,431,432) AND status = 0";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
				$dateNeededTemp = $resultWorkSchedule['targetFinish'];
				//~ $dateNeededTemp = addDays(-1,$dateNeededTemp);
			}
			
			//~ if($_SESSION['idNumber']=='0346')
			//~ {
				$blankingProcessCodeArray = ($_GET['country']==2) ? array(314,372,378,381,382,401,403,499) : array(86,52,381,98,392);
				
				$processCode = $firstProcessCode = '';
				$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode NOT IN(312,430,431,432,136) AND patternId = ".$patternId." ORDER BY processOrder LIMIT 1";
				$queryPartProcess = $db->query($sql);
				if($queryPartProcess->num_rows > 0)
				{
					$resultPartProcess = $queryPartProcess->fetch_array();
					$firstProcessCode = $resultPartProcess['processCode'];
				}
				
				if(in_array($firstProcessCode,$blankingProcessCodeArray))
				{
					$processCode = $firstProcessCode;
				}
			//~ }
			//~ else
			//~ {
				//~ $blankingProcessCode = ($_GET['country']==2) ? "314,372,378,381,382,401,403,499" : "86,52,381,98,392";
				
				//~ $processCode = '';
				//~ $sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode IN(".$blankingProcessCode.") LIMIT 1";
				//~ $queryPartProcess = $db->query($sql);
				//~ if($queryPartProcess->num_rows > 0)
				//~ {
					//~ $resultPartProcess = $queryPartProcess->fetch_array();
					//~ $processCode = $resultPartProcess['processCode'];
				//~ }
			//~ }
			
			if($_GET['country']==1)
			{
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
			}
			else if($_GET['country']==2)
			{
				$blankingProcess = '';
				if(in_array($processCode,array(372,381,382,401,403,499)))
				{
					$blankingProcess = 'TPP';
				}
				else if(in_array($processCode,array(314,378)))
				{
					$blankingProcess = 'Laser';
				}
			}
			
			$matLength = ($requiredLength > 0) ? $requiredLength : '';
			$matWidth = ($requiredWidth > 0) ? $requiredWidth : '';
			
			if($customerId==45 AND $treatmentId > 0)
			{
				if($materialType=='2024T3P')
				{
					if($matLength==2438 AND $matWidth==1219)
					{
						$matLength = $matWidth = '';
					}
				}
				else
				{
					if($matLength==2000 AND $matWidth==1000)
					{
						$matLength = $matWidth = '';
					}
				}
			}
			
			$inputFlag = 0;
			if(($matLength=='' OR $matWidth=='') AND $blankingProcess!='')
			{
				$inputFlag = 1;
				
				if($_GET['country']==1)
				{
					//material sizes
					if($customerId==45)
					{
						if($materialType=='2024T3P')
						{
							if($treatmentId > 0)
							{
								$matLength = 1219;
								$matWidth = 609;
							}
							else
							{
								$matLength = 2438;
								$matWidth = 1219;
							}
						}
						else
						{
							if($treatmentId > 0)
							{
								$matLength = 1000;
								$matWidth = 1000;
							}
							else
							{
								$matLength = 2000;
								$matWidth = 1000;
							}
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
							//~ $matLength = 2000;
							//~ $matWidth = 1000;
							//With treated 1000 x 1000
							
							if($treatmentId > 0)
							{
								$matLength = 1000;
								$matWidth = 1000;
							}
							else
							{							
								$matLength = 2000;
								$matWidth = 1000;
							}
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
							
							// with treated 1250 x 609
							
							if($treatmentId > 0)
							{
								$matLength = 1250;
								$matWidth = 609;
							}
							else
							{							
								$matLength = 2500;
								$matWidth = 1250;
							}
							
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
				
				$sql = "UPDATE cadcam_parts SET requiredLength = ".$matLength.", requiredWidth = ".$matWidth." WHERE partId = ".$partId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
			
			$excemptFlag = 0;
			$materialLengthInput = $materialWidthInput = "";
			$qtyPerSheetInput = "";
			$qtyPerSheet = $requirement = 0;
			if((($matLength > 0 AND $matWidth > 0) OR $inputFlag==1) AND $blankingProcess!='' AND !in_array($materialSpecId,array(1390,1639,1640,1514,1515,1696)))
			{
				$qtyPerSheetInput = $qtyPerSheet = computeQtyPerSheet($x,$y,$matLength,$matWidth,$blankingProcess,$customerId,$partId);
				
				$disableInput = '';
				if($requiredLength!='' AND $requiredWidth!='' AND $blankingProcess!='')
				{
					$sql = "SELECT length, width, quantityPerSheet FROM engineering_quantitypersheet WHERE partId = ".$partId." AND blankingProcess = ".$processCode." AND length = ".$requiredLength." AND width = ".$requiredWidth." LIMIT 1";
					//~ $sql = "SELECT length, width, quantityPerSheet FROM engineering_quantitypersheet WHERE partId = ".$partId." AND blankingProcess = ".$processCode." LIMIT 1";
					$queryQuantityPerSheet = $db->query($sql);
					if($queryQuantityPerSheet AND $queryQuantityPerSheet->num_rows > 0)
					{
						$resultQuantityPerSheet = $queryQuantityPerSheet->fetch_assoc();
						$matLength = $resultQuantityPerSheet['length'];
						$matWidth = $resultQuantityPerSheet['width'];
						$qtyPerSheet = $resultQuantityPerSheet['quantityPerSheet'];
						//~ $disableInput = 'disabled';
						
						$qtyPerSheetInput = "<span style='color:blue;' title='Fixed'>".$qtyPerSheet."</span>";
						
						$excemptFlag = 1;
					}
					
					//~ $qtyPerSheetInput = "<input type='number' data-part-id='".$partId."' class='width api-form' name='qtyPerSheet[]' value='".$qtyPerSheet."' style='width:50px;' step='any' form='computeFormId'>";
				}
				
				$materialLengthInput = "<input type='number' data-part-id='".$partId."' class='length api-form' name='matLength[]' value='".$matLength."' style='width:100px;' step='any' ".$disableInput." form='computeFormId'>";
				$materialWidthInput = "<input type='number' data-part-id='".$partId."' class='width api-form' name='matWidth[]' value='".$matWidth."' style='width:100px;' step='any' ".$disableInput." form='computeFormId'>";				
				
				$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0;
				
				if($submitType!='')
				{
					if($requirement <= 0 AND $excemptFlag==0)
					{
						echo "<form action='gerald_roReviewNoMaterial.php' method='POST' id='formForm'></form>";
						echo "<input type='hidden' name='partNumber' value='".$partNumber."' form='formForm'>";
						?>
						<script>
							alert("Zero requirement detected for Part Number <?php echo $partNumber;?>");
							document.getElementById('formForm').submit();
						</script>
						<?php
						exit(0);
					}
				}
			}
			
			//~ if(strtotime($dateNeededTemp)<strtotime(date('Y-m-d')))
			//~ {
				//~ $dateNeededTemp = date('Y-m-d');
			//~ }
			
			$dateNeeded = $dateNeededTemp;
			
			if($requirement > 0)
			{
				//~ $arrayKey = $materialType."`".$metalThickness."`".$matLength."`".$matWidth."`".$treatmentName."`".$pvcStatus;
				$arrayKey = $materialType."`".$metalThickness."`".$matLength."`".$matWidth."`".$treatmentName."`".$pvcStatus."`".$customerAlias;
				
				if(!isset($dataDateNeededArray[$arrayKey])) $dataDateNeededArray[$arrayKey] = array();
				
				$dataDateNeededArray[$arrayKey][] = $dateNeeded;
				
				if(!isset($requirementArray[$arrayKey])) $requirementArray[$arrayKey] = 0;
				$requirementArray[$arrayKey] += $requirement;
				
				if(!isset($poIdArray[$arrayKey])) $poIdArray[$arrayKey] = array();
				$poIdArray[$arrayKey][] = $poId;
				
				if(!isset($lotNumberArray[$arrayKey])) $lotNumberArray[$arrayKey] = array();
				$lotNumberArray[$arrayKey][] = $lotNumber;
				
				$requirementDataArray[$lotNumber] = $requirement;
			}
			
			$tableContent .= "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$customerAlias."</td>
					<td>".$poNumber."</td>
					<td>".$lotNumber."</td>
					<td>".$partNumber."</td>
					<td>".$revisionId."</td>
					<td>".$partNote."</td>
					<td align='right' class='workingQuantity'>".$workingQuantity."</td>
					<td>".$materialType."</td>
					<td>".$metalThickness."</td>
					<td>".$treatmentName."</td>
					<td align='right'>".$x."</td>
					<td align='right'>".$y."</td>
					<td>".$bendStatus."</td>
					<td>".$pvcStatus."</td>
					<td>".$blankingProcess."</td>
					<td>".$materialLengthInput."</td>
					<td>".$materialWidthInput."</td>
					<td class='qtyPerSheet'>".$qtyPerSheetInput." ".$sqlSql."</td>
					<td class='requirement'>".$requirement."</td>
				</tr>
			";
		}
		if($submitType=='calculate')
		{
			//~ echo "<table border='1'>".$tableContent."</table>";
			
			if(count($requirementArray) > 0)
			{
				$materialComputationIdArray = array();
				$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE lotNumber LIKE '' AND status = 2";
				$queryMaterialComputation = $db->query($sql);
				if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
				{
					while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
					{
						$materialComputationIdArray[] = $resultMaterialComputation['materialComputationId'];
					}
					
					$bookingIdArray = array();
					$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber IN('".implode("','",$materialComputationIdArray)."')";
					$queryBookingDetails = $db->query($sql);
					if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
					{
						while($resultBookingDetails = $queryBookingDetails->fetch_assoc())
						{
							$bookingIdArray[] = $resultBookingDetails['bookingId'];
						}
					}
					
					$sql = "DELETE FROM engineering_bookingdetails WHERE bookingId IN(".implode(",",$bookingIdArray).")";
					//~ $queryBooking = $db->query($sql);
					
					$sql = "DELETE FROM engineering_booking WHERE bookingId IN(".implode(",",$bookingIdArray).") AND bookingStatus = 2";
					//~ $queryBooking = $db->query($sql);
				}
				
				$materialComputationIdDeleteArray = array();
				$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE lotNumber LIKE ''";
				$queryMaterialComputationDetails = $db->query($sql);
				if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
				{
					while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
					{
						$materialComputationIdDeleteArray[] = $resultMaterialComputationDetails['materialComputationId'];
					}
					
					$sql = "DELETE FROM ppic_materialcomputationdetails WHERE materialComputationId IN(".implode(",",$materialComputationIdDeleteArray).")";
					$queryDelete = $db->query($sql);
				}
				
				$sql = "DELETE FROM ppic_materialcomputation WHERE lotNumber LIKE ''";
				$queryDelete = $db->query($sql);
				
				//~ echo "<table border='1' cellpadding='0' cellspacing='0' style='float:left;width:100%;'>";	
					//~ echo "
					//~ <thead>
						//~ <tr>
							//~ <th colspan = '9'>".displayText('L309')."</th>
						//~ </tr>
						//~ <tr>
							//~ <th>".displayText('L24')."</th>
							//~ <th>".displayText('L566')."</th>
							//~ <th>".displayText('L184')."</th>
							//~ <th>".displayText('L74')."</th>
							//~ <th>".displayText('L75')."</th>
							//~ <th>".displayText('L67')."</th>
							//~ <th>".displayText('L306')."</th>
							//~ <th></th>
							//~ <th></th>
						//~ </tr>
					//~ </thead>";
				
				$sqlMain = "INSERT INTO `ppic_materialcomputation`(`customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `dateNeeded`) VALUES ";
				$sqlValuesArray = array();
				$counter = 0;
				$compressedInput = '';	
				foreach($requirementArray as $key=>$val)
				{
					$keyArray = explode("`",$key);
					
					$statusArray = array();
					$statusArray[] = "<option value='0'>Not Set</option>";
					$statusArray[] = "<option value='1'>".displayText('L1345')."</option>";
					if($keyArray[5] != 'Raw')
					{
						$statusArray[] = "<option value='2'>For Subcon</option>";
						
						$primeFlag = (strstr($keyArray[5],'prime')!==FALSE) ? 1 : 0;
						
						if($primeFlag==1 AND strstr($keyArray[6],'jamco')!==FALSE)
						{
							$statusArray[] = "<option value='3'>For Internal Prime</option>";
						}
					}
					$statusArray[] = "<option value='4'>For Customer Request</option>";
					$statusArray[] = "<option value='5'>Open PO</option>";
					
					$pvcStatus = ($keyArray[5]=='Yes') ? 1 : 0;
					
					$status = 0;
					
					$materialParameter = 0;
					$sql = "SELECT materialParameter FROM sales_customer WHERE customerAlias = '".$keyArray[6]."' LIMIT 1";
					$queryCustomer = $db->query($sql);
					if($queryCustomer AND $queryCustomer->num_rows > 0)
					{
						$resultCustomer = $queryCustomer->fetch_assoc();
						$materialParameter = $resultCustomer['materialParameter'];
					}
					
					if($materialParameter==0)	$status = 1;
					else if($materialParameter==1)	$status = 4;
					
					/*
					if($_SESSION['idNumber']=='0346')
					{
						$dateNeededArray = $dataDateNeededArray[$key];
						
						$dateNeededArray = array_values(array_filter(array_unique($dateNeededArray)));
						
						$lastDateTemp = '';
						
						sort($dateNeededArray);
						
						$dateArray = array();
						
						foreach($dateNeededArray as $dataDate)
						{
							if($lastDateTemp=='')
							{
								$dateArray[] = $dataDate;
								$lastDateTemp = addDays(7,$dataDate);
							}
							else
							{
								if(strtotime($dataDate) > strtotime($lastDateTemp))
								{
									$dateArray[] = $dataDate;
									$lastDateTemp = addDays(7,$dataDate);
								}
							}
						}
						
						if(count($dateArray) > 0)
						{
							foreach($dateArray as $dateNeeded)
							{
								$sqlValues = "('".$keyArray[6]."','".$keyArray[0]."','".$keyArray[1]."','".$keyArray[2]."','".$keyArray[3]."','".$keyArray[4]."','".$pvcStatus."','".ceil($val)."','".ceil($val)."','0','".$_SESSION['idNumber']."',NOW(),'','".$dateNeeded."')";
								
								$sqlValuesArray[] = $sqlValues;
								$counter++;
								if($counter==50)
								{
									$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
									$queryUpdate = $db->query($sqlInsert);
									$sqlValuesArray = array();
									$counter = 0;
								}
							}
						}
					}
					else
					{*/
						$sqlValues = "('".$keyArray[6]."','".$keyArray[0]."','".$keyArray[1]."','".$keyArray[2]."','".$keyArray[3]."','".$keyArray[4]."','".$pvcStatus."','".ceil($val)."','".ceil($val)."','".$status."','".$_SESSION['idNumber']."',NOW(),'','".$dateNeeded."')";
						
						$sqlValuesArray[] = $sqlValues;
						$counter++;
						if($counter==50)
						{
							$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
							$queryUpdate = $db->query($sqlInsert);
							$sqlValuesArray = array();
							$counter = 0;
						}						
					//~ }
					
					$yonko = "<select class='statusClass api-form'>".implode("",$statusArray)."</select>";
					
					//~ echo "
						//~ <tr>
							//~ <td>".$keyArray[6]."</td>
							//~ <td>".$keyArray[0]."</td>
							//~ <td>".$keyArray[1]."</td>
							//~ <td>".$keyArray[2]."</td>
							//~ <td>".$keyArray[3]."</td>
							//~ <td>".$keyArray[4]."</td>
							//~ <td>".$keyArray[5]."</td>
							//~ <td>".ceil($val)."</td>
							//~ <td>".$yonko."</td>
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
				if($counter > 0)
				{
					$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
					$queryUpdate = $db->query($sqlInsert);
				}
				
				$arrayKey = $materialType."`".$metalThickness."`".$matLength."`".$matWidth."`".$treatmentName."`".$pvcStatus."`".$customerAlias;
				$sql = "SELECT materialComputationId, CONCAT(materialType,'`',thickness,'`',length,'`',width,'`',treatment,'`',IF(pvc=1,'Yes','No'),'`',customerAlias) as uniqueKey FROM ppic_materialcomputation WHERE lotNumber = ''";
				$queryMaterialComputation = $db->query($sql);
				if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
				{
					while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
					{
						$materialComputationId = $resultMaterialComputation['materialComputationId'];
						$uniqueKey = $resultMaterialComputation['uniqueKey'];
						
						$sql = "UPDATE ppic_roreviewdatatemp SET materialComputationId = '".$materialComputationId."' WHERE poId IN(".implode(",",$poIdArray[$uniqueKey]).")";
						$queryUpdate = $db->query($sql);
						
						$sql = "INSERT INTO ppic_materialcomputationdetails
										(	`materialComputationId`,		`lotNumber`,	`workingQuantity`)
								SELECT 		'".$materialComputationId."',	`lotNumber`,	`workingQuantity`
								FROM		ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray[$uniqueKey])."')";
						$queryInsert = $db->query($sql);
						
						$sql = "SELECT listId, lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
						$queryMaterialComputationDetails = $db->query($sql);
						if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
						{
							while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
							{
								$listId = $resultMaterialComputationDetails['listId'];
								$lotNumber = $resultMaterialComputationDetails['lotNumber'];
								
								$requirement = $requirementDataArray[$lotNumber];
								
								$nestingDate = '0000-00-00';
								$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(312,430,431,432) AND status = 0 LIMIT 1";
								$queryWorkSchedule = $db->query($sql);
								if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
								{
									$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
									$nestingDate = $resultWorkSchedule['targetFinish'];
								}
								
								$sql = "UPDATE ppic_materialcomputationdetails SET requirement = ".$requirement.", nestingDate = '".$nestingDate."' WHERE listId = ".$listId." LIMIT 1";
								$queryUpdate = $db->query($sql);
							}
						}
						
						//~ $sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
						//~ $queryDelete = $db->query($sql);
						
						//~ $sql = "SELECT DISTINCT poId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray[$uniqueKey])."') AND identifier = 1";
						//~ $queryLotList = $db->query($sql);
						//~ if($queryLotList AND $queryLotList->num_rows > 0)
						//~ {
							//~ while($resultLotList = $queryLotList->fetch_assoc())
							//~ {
								//~ generateScheduleItems($resultLotList['poId'],'',0,0);
							//~ }
						//~ }
						
						//~ $targetFinish = '0000-00-00';
						//~ $sql = "SELECT targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber IN('".implode("','",$lotNumberArray[$uniqueKey])."') AND processCode IN(312,430) AND status = 0 ORDER BY processOrder LIMIT 1";
						//~ $queryTemporaryWorkschedule = $db->query($sql);
						//~ if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
						//~ {
							//~ $resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
							//~ $targetFinish = $resultTemporaryWorkschedule['targetFinish'];
						//~ }
						
						//~ $sql = "UPDATE ppic_materialcomputation SET dateNeeded = '".$targetFinish."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
						//~ $queryUpdate = $db->query($sql);
					}
				}
				
				header('location:gerald_roReviewMaterialComputation.php');
				exit(0);
				
				echo "</table>";
				//~ echo "<form action = '/V3/3-4 Material Requirements Computation Software/anthony_computationSummaryConverter.php' method = 'POST' id = 'print' ></form>";
				echo "<form action = '/".v."/54 Automated Material Computation Software/anthony_computationSummaryConverter.php' method = 'POST' id = 'print' ></form>";
				echo "<input type = 'hidden' name = 'specId' value = '".$compressedInput."' form='print'>";
				echo "<input type = 'image' id='printId' src = '/".v."/Common Data/Templates/buttons/printIcon.png' height = '35' width = '50' form='print'>";
				?><script>//document.getElementById('print').submit();</script><?php
			}
		}
		else
		{		
			echo $tableContent;
		}
	}
					
?>
