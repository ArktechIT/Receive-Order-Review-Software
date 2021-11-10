<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	if(isset($_POST['data']))
	{
		$data = $_POST['data'];
		$dataPart = explode("|",$data);
		$materialComputationId = $dataPart[0];
		$supplyId = $dataPart[1];
		
		//~ if($_SESSION['idNumber']=='0346')
		//~ {
			$lotNumberArray = array();
			$sql = "SELECT lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
			$queryMaterialComputationDetails = $db->query($sql);
			if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
			{
				while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
				{
					$lotNumberArray[] = $resultMaterialComputationDetails['lotNumber'];
				}
				
				$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
				$queryDelete = $db->query($sql);
				
				$sql = "SELECT DISTINCT poId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND identifier = 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						generateScheduleItems($resultLotList['poId'],'',0,0);
					}
				}
				
				$targetFinish = '0000-00-00';
				$sql = "SELECT targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode IN(312,430,431,432) AND status = 0 ORDER BY processOrder LIMIT 1";
				$queryTemporaryWorkschedule = $db->query($sql);
				if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
				{
					$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
					$targetFinish = $resultTemporaryWorkschedule['targetFinish'];
					
					$targetFinish = addDays(-5,$targetFinish);
				}
				
				$sql = "UPDATE ppic_materialcomputation SET dateNeeded = '".$targetFinish."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
		//~ }
		
		$sql = "SELECT `pvc`, `finalQuantity` FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
			$pvc = $resultMaterialComputation['pvc'];
			$sheetQuantity = $resultMaterialComputation['finalQuantity'];
			
			$sql = "SELECT lotNumber FROM system_confirmedmaterialpo WHERE materialId = ".$supplyId." AND pvc = ".$pvc." AND poNumber = ''";
			
			$queryConfirmedMaterialPo = $db->query($sql);
			if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
			{
				$resultConfirmedMaterialPo = $queryConfirmedMaterialPo->fetch_assoc();
				$lotNumber = $resultConfirmedMaterialPo['lotNumber'];
				
				$sql = "UPDATE system_confirmedmaterialpo SET sheetQuantity = ".$sheetQuantity." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryUpdate = $db->query($sql);
				
				$sql = "UPDATE ppic_lotlist SET workingQuantity = (workingQuantity+".$sheetQuantity.") WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryUpdate = $db->query($sql);
				
				$sql = "UPDATE ppic_materialcomputation SET lotNumber = '".$lotNumber."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
				
				$sql = "SELECT id, status FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 461";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 1)
				{
					$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
					$id = $resultWorkSchedule['id'];
					$status = $resultWorkSchedule['status'];
					
					if($status==1)
					{
						$sql = "UPDATE ppic_workschedule SET status = 0 WHERE id = ".$Id." LIMIT 1";
						$queryUpdate = $db->query($sql);
					}
				}
				
				$queryUpdate = $db->query($sql);
				
				echo "<script>
						alert('Success');
						location.href = '".$_SERVER['PHP_SELF']."';
					</script>";
			}
			else
			{
				$lotNumber = createPurchasingLotNumber($supplyId,1);
				
				//~ if($lotNumber!='error')
				if(strstr($lotNumber,'error')===FALSE)
				{
					$sql = "INSERT INTO system_confirmedmaterialpo (materialId, sheetQuantity, pvc,lotNumber) VALUES (".$supplyId.", ".$sheetQuantity.", ".$pvc.",'".$lotNumber."')";
					$queryInsert = $db->query($sql);
					if($queryInsert)
					{
						$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$sheetQuantity." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$sql = "UPDATE ppic_materialcomputation SET lotNumber = '".$lotNumber."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						echo "<script>
								alert('Success');
								location.href = '".$_SERVER['PHP_SELF']."';
							</script>";
					}
					else
					{
						echo "<script>
							alert('Error 2!');
							location.href = '".$_SERVER['PHP_SELF']."';
						</script>";
					}
				}
				else
				{
					echo "<script>
						alert('Error!');
						location.href = '".$_SERVER['PHP_SELF']."';
					</script>";
				}
			}
		}
		//header('location:'.$_SERVER['PHP_SELF']);
		exit(0);
	}
	else if(isset($_POST['materialComputationId']))
	{
		$materialComputationId = $_POST['materialComputationId'];
		$alternateMaterial = $_POST['alternateMaterial'];
		
		if(isset($_POST['alternateMaterialSpecId']))
		{
			$alternateMaterialSpecId = $_POST['alternateMaterialSpecId'];
			
			$sql = "
				INSERT INTO `ppic_materialcomputation`
						(	`customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `dateNeeded`, `blankingProcess`, `alternateMaterial`)
				SELECT 		`customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `dateNeeded`, `blankingProcess`, `alternateMaterial`
				FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1
			";
			$queryInsert = $db->query($sql);
			if($queryInsert)
			{
				$newMaterialComputationId = $db->insert_id;
				
				$newTotalRequirement = $oldTotalRequirement = 0;
				$sql = "SELECT listId, lotNumber, requirement FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
				$queryMaterialComputationDetails = $db->query($sql);
				if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
				{
					while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
					{
						$listId = $resultMaterialComputationDetails['listId'];
						$lotNumber = $resultMaterialComputationDetails['lotNumber'];
						$requirement = $resultMaterialComputationDetails['requirement'];
						
						$partId = '';
						$sql = "SELECT poId, partId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							$resultLotList = $queryLotList->fetch_assoc();
							$partId = $resultLotList['partId'];
						}
						
						if($alternateMaterialSpecId!='')
						{
							$sql = "SELECT listId FROM engineering_alternatematerial WHERE partId = ".$partId." AND materialSpecId = ".$alternateMaterialSpecId." LIMIT 1";
							$queryAlternateMaterial = $db->query($sql);
							if($queryAlternateMaterial AND $queryAlternateMaterial->num_rows == 0)
							{
								$newTotalRequirement += $requirement;
							}
							else
							{
								$sql = "UPDATE ppic_materialcomputationdetails SET materialComputationId = ".$newMaterialComputationId." WHERE listId = ".$listId." LIMIT 1";
								$queryUpdate = $db->query($sql);								
								$oldTotalRequirement += $requirement;
							}
						}
					}
				}
				
				$sql = "UPDATE ppic_materialcomputation SET finalQuantity = ".ceil($oldTotalRequirement)." WHERE materialComputationId = ".$newMaterialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
			
			$sql = "UPDATE ppic_materialcomputation SET alternateMaterial = '', finalQuantity = ".ceil($newTotalRequirement)." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		else
		{
			//~ $sql = "SELECT ";
			
			$sql = "UPDATE ppic_materialcomputation SET alternateMaterial = '".$alternateMaterial."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		
		header('location:'.$_SERVER['PHP_SELF']);
		exit(0);
	}
	
	$customerDeliveryDateArray = array();
	//~ if($_SESSION['idNumber']=='0346')
	//~ {
		$materialComputationIdArray = array();
		$sql = "SELECT `materialComputationId` FROM ppic_materialcomputation WHERE lotNumber ='' AND status = 1";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
			{
				$materialComputationIdArray[] = $resultMaterialComputation['materialComputationId'];
			}
		}
		
		$lotNumberArray = array();
		$sql = "SELECT lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId IN(".implode(",",$materialComputationIdArray).")";
		$queryMaterialComputationDetails = $db->query($sql);
		if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
		{
			while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
			{
				$lotNumberArray[] = $resultMaterialComputationDetails['lotNumber'];
			}
		}
		
		$poIdArray = array();
		$sql = "SELECT DISTINCT poId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."')";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$poIdArray[] = $resultLotList['poId'];
			}
		}
		
		$customerDeliveryDateArray = array();
		$sql = "SELECT DISTINCT customerDeliveryDate FROM sales_polist WHERE poId IN(".implode(",",$poIdArray).") ORDER BY customerDeliveryDate";
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			while($resultPoList = $queryPoList->fetch_assoc())
			{
				$customerDeliveryDateArray[] = $resultPoList['customerDeliveryDate'];
			}
		}
	//~ }
	
	echo "
		<form action='' method='post' id='formId'></form>
		<table border='1'>
			<tr>
				<th></th>
				<th>".displayText('L111')."</th>
				<th>".displayText('L1289')."</th>
				<th>".displayText('L184')."</th>
				<th>".displayText('L74')."</th>
				<th>".displayText('L75')."</th>
				<th>".displayText('L67')."</th>
				<th>".displayText('L306')."</th>
				<th>".displayText('L31')."</th>
				<th></th>
				<th>".implode("</th><th>",$customerDeliveryDateArray)."</th>
			</tr>
	";
	
	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial` FROM ppic_materialcomputation WHERE lotNumber ='' AND status = 1";
	//~ if($_SESSION['idNumber']=='0346')	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial` FROM ppic_materialcomputation WHERE materialComputationId = 2648";
	//~ if($_SESSION['idNumber']=='0346')	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial` FROM ppic_materialcomputation WHERE inputDateTime = '2018-10-11 16:54:33'";
	//~ if($_SESSION['idNumber']=='0346')	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial` FROM ppic_materialcomputation WHERE materialComputationId = 2675";
	//~ if($_SESSION['idNumber']=='0346')	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial` FROM ppic_materialcomputation WHERE materialComputationId = 3129";
	$queryMaterialComputation = $db->query($sql);
	if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
	{
		while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
		{
			$materialComputationId = $resultMaterialComputation['materialComputationId'];
			$customerAlias = $resultMaterialComputation['customerAlias'];
			$materialType = $resultMaterialComputation['materialType'];
			$thickness = $resultMaterialComputation['thickness'];
			$length = $resultMaterialComputation['length'];
			$width = $resultMaterialComputation['width'];			
			$treatment = $resultMaterialComputation['treatment'];			
			$pvc = $resultMaterialComputation['pvc'];
			$quantity = $resultMaterialComputation['quantity'];			
			$finalQuantity = $resultMaterialComputation['finalQuantity'];			
			$status = $resultMaterialComputation['status'];
			$alternateMaterial = $resultMaterialComputation['alternateMaterial'];
			
			//~ $materialType = $alternateMaterial;
			
			$alternateMaterialSelect = "<form action='' method='post' id='".$materialComputationId."'></form>
				<input type='hidden' name='materialComputationId' value='".$materialComputationId."' form='".$materialComputationId."'>
				<select name='alternateMaterial' onchange=\" this.form.submit() \" form='".$materialComputationId."'>
				<option value=''></option>
			";
			$sql = "
				SELECT DISTINCT f.materialType FROM ppic_materialcomputationdetails as a
				INNER JOIN ppic_lotlist as b ON b.lotNumber = a.lotNumber
				INNER JOIN cadcam_parts as c ON c.partId = b.partId
				INNER JOIN engineering_alternatematerial as d ON d.partId = c.partId
				INNER JOIN cadcam_materialspecs as e ON e.materialSpecId = d.materialSpecId
				INNER JOIN engineering_materialtype as f ON f.materialTypeId = e.materialTypeId
				WHERE a.materialComputationId = ".$materialComputationId." ORDER BY f.materialType
			";
			$queryAlternateMaterial = $db->query($sql);
			if($queryAlternateMaterial AND $queryAlternateMaterial->num_rows > 0)
			{
				while($resultAlternateMaterial = $queryAlternateMaterial->fetch_assoc())
				{
					$selected = ($alternateMaterial==$resultAlternateMaterial['materialType']) ? 'selected' : '';
					$alternateMaterialSelect .= "<option value='".$resultAlternateMaterial['materialType']."' ".$selected.">".$resultAlternateMaterial['materialType']."</option>";
				}
			}
			$alternateMaterialSelect .= "</select>";
			
			$materialTypeId = '';
			$sql = "SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '".$materialType."' LIMIT 1";
			if($alternateMaterial!='')	$sql = "SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '".$alternateMaterial."' LIMIT 1";
			$queryMaterialType = $db->query($sql);
			if($queryMaterialType AND $queryMaterialType->num_rows > 0)
			{
				$resultMaterialType = $queryMaterialType->fetch_assoc();
				$materialTypeId = $resultMaterialType['materialTypeId'];
			}
			
			$materialSpecId = '';
			$sql = "SELECT materialSpecId FROM cadcam_materialspecs WHERE materialTypeId = ".$materialTypeId." AND metalThickness = ".$thickness." LIMIT 1";
			$queryMaterialSpecs = $db->query($sql);
			if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
			{
				$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
				$materialSpecId = $resultMaterialSpecs['materialSpecId'];
			}
			
			$suppliermaterialID = '';
			$sql = "SELECT suppliermaterialID FROM purchasing_materialtype WHERE materialType LIKE '".$materialType."' LIMIT 1";
			if($alternateMaterial!='')	$sql = "SELECT suppliermaterialID FROM purchasing_materialtype WHERE materialType LIKE '".$alternateMaterial."' LIMIT 1";
			$queryMaterialType = $db->query($sql);
			if($queryMaterialType AND $queryMaterialType->num_rows > 0)
			{
				$resultMaterialType = $queryMaterialType->fetch_assoc();
				$suppliermaterialID = $resultMaterialType['suppliermaterialID'];
			}
			
			$cadcamTreatmentId = '';
			$sql = "SELECT treatmentId FROM cadcam_treatmentprocess WHERE treatmentName LIKE '".$treatment."' LIMIT 1";
			$queryTreatmentProcess = $db->query($sql);
			if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
			{
				$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
				$cadcamTreatmentId = $resultTreatmentProcess['treatmentId'];
			}
			
			$materialTreatmentId = '';
			$materialId = '';
			$sql = "SELECT materialId FROM purchasing_material WHERE (materialSpecId = ".$materialSpecId." OR (materialTypeId = ".$suppliermaterialID." AND thickness = ".$thickness.")) AND length = ".$length." AND width = ".$width." LIMIT 1";
			$queryMaterial = $db->query($sql);
			if($queryMaterial AND $queryMaterial->num_rows > 0)
			{
				$resultMaterial = $queryMaterial->fetch_assoc();
				$materialId = $resultMaterial['materialId'];
				
				$sql = "SELECT materialTreatmentId FROM purchasing_materialtreatment WHERE materialId = ".$materialId." AND treatmentId = ".$cadcamTreatmentId." LIMIT 1";
				$queryMaterialTreatment = $db->query($sql);
				if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
				{
					$resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc();
					$materialTreatmentId = $resultMaterialTreatment['materialTreatmentId'];
				}
			}
			
			$pvcStatus = ($pvc==1) ? 'Yes' : 'No';
			
			$variable = "<a href='/".v."/4-E Material List V2/gerald_materialList.php'>NO DATA</a>";
			if($materialTreatmentId!='')
			{
				$variable = "<button name='data' value='".$materialComputationId."|".$materialTreatmentId."' form='formId'>".displayText('L491')."</button>";
			}
			
			$alternateMaterialSpecId = '';
			if($alternateMaterial!='')
			{
				$sql = "
					SELECT b.materialSpecId FROM engineering_materialtype as a
					INNER JOIN cadcam_materialspecs as b ON b.materialTypeId = a.materialTypeId
					WHERE a.materialType LIKE '".$alternateMaterial."' AND b.metalThickness = ".$thickness."
				";
				$queryMaterialType = $db->query($sql);
				if($queryMaterialType AND $queryMaterialType->num_rows > 0)
				{
					$resultMaterialType = $queryMaterialType->fetch_assoc();
					$alternateMaterialSpecId = $resultMaterialType['materialSpecId'];
				}
			}
			
			$requirementArray = $noAlternateLotArray = array();
			//~ if($_SESSION['idNumber']=='0346')
			//~ {
				$requirementDataArray = array();
				$sql = "SELECT lotNumber, requirement FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
				$queryMaterialComputationDetails = $db->query($sql);
				if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
				{
					while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
					{
						$lotNumber = $resultMaterialComputationDetails['lotNumber'];
						$requirement = $resultMaterialComputationDetails['requirement'];
						
						$poId = $partId = '';
						$sql = "SELECT poId, partId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							$resultLotList = $queryLotList->fetch_assoc();
							$poId = $resultLotList['poId'];
							$partId = $resultLotList['partId'];
						}
						
						$customerDeliveryDate = '';
						$sql = "SELECT customerDeliveryDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
						$queryPoList = $db->query($sql);
						if($queryPoList AND $queryPoList->num_rows > 0)
						{
							$resultPoList = $queryPoList->fetch_assoc();
							$customerDeliveryDate = $resultPoList['customerDeliveryDate'];
						}
						
						if(!isset($requirementDataArray[$customerDeliveryDate])) $requirementDataArray[$customerDeliveryDate] = 0;
						$requirementDataArray[$customerDeliveryDate] += $requirement;
						
						if($alternateMaterialSpecId!='')
						{
							$sql = "SELECT listId FROM engineering_alternatematerial WHERE partId = ".$partId." AND materialSpecId = ".$alternateMaterialSpecId." LIMIT 1";
							$queryAlternateMaterial = $db->query($sql);
							if($queryAlternateMaterial AND $queryAlternateMaterial->num_rows == 0)
							{
								$noAlternateLotArray[] = $lotNumber;
							}
						}
					}
				}
				
				$noAlternateLot = "";
				if(count($noAlternateLotArray) > 0)
				{
					$noAlternateLot = "<details><summary class='summary'>No Alternate</summary>".implode("<br>",$noAlternateLotArray)."</details>";
					//~ $noAlternateLot .= "<input type='submit' name='submitName' value='Separate' form='".$materialComputationId."'>";
					if($_SESSION['idNumber']=='0346') $noAlternateLot .= "<button type='submit' name='alternateMaterialSpecId' value='".$alternateMaterialSpecId."' form='".$materialComputationId."'>Separate</button>";
				}
				
				$requirementArray = array();
				foreach($customerDeliveryDateArray as $cdd)
				{
					$requirementArray[] = $requirementDataArray[$cdd];
				}
			//~ }
			
			echo  "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$materialType."</td>
					<td>".$alternateMaterialSelect.$noAlternateLot."</td>
					<td>".$thickness."</td>
					<td>".$length."</td>
					<td>".$width."</td>
					<td>".$treatment."</td>
					<td>".$pvcStatus."</td>
					<td>".$finalQuantity."</td>
					<td>".$variable."</td>
					<td>".implode("</td><td>",$requirementArray)."</td>
				</tr>
			";
		}
	}
	
	echo "</table>";
?>
