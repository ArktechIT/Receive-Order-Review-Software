<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='updatePattern')
		{
			$patternId = $_POST['patternId'];
			$lotNoArray = $_POST['lotNoArray'];
			
			if(count($lotNoArray))
			{
				if($patternId!=-1)
				{
					if($_GET['country']=='2' AND $patternId=='2')
					{
						$sql = "SELECT lotNumber, partId ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNoArray)."') AND identifier = 1";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							while($resultLotList = $queryLotList->fetch_assoc())
							{
								$lotNumber = $resultLotList['lotNumber'];
								$partId = $resultLotList['partId'];
								
								$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode = 343 AND patternId = 2 LIMIT 1";
								$queryPartProcess = $db->query($sql);
								if($queryPartProcess AND $queryPartProcess->num_rows > 0)
								{
									$sql = "DELETE FROM system_finishedgoodbooking WHERE lotNumber LIKE '".$lotNumber."'";
									$queryUpdate = $db->query($sql);
								}
							}
						}
					}
					else
					{
						$sql = "DELETE FROM system_finishedgoodbooking WHERE lotNumber IN('".implode("','",$lotNoArray)."')";
						$queryUpdate = $db->query($sql);
					}
				}
				
				$sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber IN('".implode("','",$lotNoArray)."')";
				$queryUpdate = $db->query($sql);
				
			}
		}
		else if($_POST['ajaxType']=='updateDate1')
		{
			$listIdArray = $_POST['listIdArray'];
			$newDate = $_POST['newDate'];
			$oldDate = $_POST['oldDate'];
			
			$errorFlag = 0;
			if($oldDate!=$newDate)
			{
				$operatorSign = (strtotime($newDate) > strtotime($oldDate)) ? ">" : "<";
				$orderByType = (strtotime($newDate) > strtotime($oldDate)) ? "ASC" : "DESC";
				
				$lotNumber = $processOrder = '';
				$sql = "SELECT lotNumber, processOrder, targetFinish FROM system_temporaryworkschedule WHERE listId IN(".implode(",",$listIdArray).") ORDER BY processOrder ".$orderByType." LIMIT 1";
				$queryTemporaryWorkschedule = $db->query($sql);
				if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
				{
					$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
					$lotNumber = $resultTemporaryWorkschedule['lotNumber'];
					$processOrder = $resultTemporaryWorkschedule['processOrder'];
				}
				
				$targetFinish = $newDate;
				$sql = "SELECT processOrder, targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND listId NOT IN(".implode(",",$listIdArray).") AND lotNumber LIKE '".$lotNumber."' AND processOrder ".$operatorSign." ".$processOrder." ORDER BY processOrder ".$orderByType." LIMIT 1";
				$queryTemporaryWorkschedule = $db->query($sql);
				if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
				{
					$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
					$processOrder = $resultTemporaryWorkschedule['processOrder'];
					$targetFinish = $resultTemporaryWorkschedule['targetFinish'];
				}
				
				
				if($operatorSign == ">")
				{
					if(strtotime($newDate) > strtotime($targetFinish))
					{
						$errorFlag = 1;
					}
				}
				else if($operatorSign == "<")
				{
					if(strtotime($newDate) < strtotime($targetFinish))
					{
						$errorFlag = 1;
					}
				}
			}
			
			$day =  date('l', strtotime($newDate));
			
			if($_GET['country']=='1')//Philippines
			{
				$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$newDate."' AND holidayType < 6 LIMIT 1";
			}
			else if($_GET['country']=='2')//Japan
			{
				$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$newDate."' AND holidayType >= 6 LIMIT 1";
			}
			$dc = $db->query($sql);
			$dcnum = $dc->num_rows;
			if($day=='Sunday' OR $dcnum > 0)
			{
				$errorFlag = 1;
			}			
			
			if($errorFlag==0)
			{
				$sql = "UPDATE system_temporaryworkschedule SET targetFinish = '".$newDate."' WHERE listId IN(".implode(",",$listIdArray).")";
				$queryUpdate = $db->query($sql);
				echo $operatorSign;
			}
			else
			{
				echo "error";
			}
		}
		else if($_POST['ajaxType']=='checkDates')
		{
			$listIdArray = $_POST['listIdArray'];
			$lotNumber = $_POST['lotNumber'];
			$processDate = $_POST['processDate'];
			
			$minProcessOrder = '';
			$sql = "SELECT processOrder FROM system_temporaryworkschedule WHERE listId IN(".implode(",",$listIdArray).") ORDER BY processOrder ASC LIMIT 1";
			$queryTemporaryWorkschedule = $db->query($sql);
			if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
			{
				$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
				$minProcessOrder = $resultTemporaryWorkschedule['processOrder'];
			}
			
			$maxProcessOrder = '';
			$sql = "SELECT processOrder FROM system_temporaryworkschedule WHERE listId IN(".implode(",",$listIdArray).") ORDER BY processOrder DESC LIMIT 1";
			$queryTemporaryWorkschedule = $db->query($sql);
			if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
			{
				$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
				$maxProcessOrder = $resultTemporaryWorkschedule['processOrder'];
			}
			
			$minTargetFinish = date('Y-m-d');
			$sql = "SELECT targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber LIKE '".$lotNumber."' AND processOrder = ".($minProcessOrder-1)." LIMIT 1";
			$queryTemporaryWorkschedule = $db->query($sql);
			if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
			{
				$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
				$minTargetFinish = $resultTemporaryWorkschedule['targetFinish'];
			}
			
			$maxTargetFinish = date('Y-m-d');
			$sql = "SELECT targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber LIKE '".$lotNumber."' AND processOrder = ".($maxProcessOrder+1)." LIMIT 1";
			$queryTemporaryWorkschedule = $db->query($sql);
			if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
			{
				$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
				$maxTargetFinish = $resultTemporaryWorkschedule['targetFinish'];
			}
			else
			{
				$sql = "SELECT deliveryDate FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber LIKE '".$lotNumber."' ORDER BY processOrder DESC LIMIT 1";
				$queryTemporaryWorkschedule = $db->query($sql);
				if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
				{
					$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
					$maxTargetFinish = $resultTemporaryWorkschedule['deliveryDate'];
				}
			}
			
			$datesArray = array();
			$targetFinish = $minTargetFinish;
			while(strtotime($targetFinish) <= strtotime($maxTargetFinish))
			{
				if($processDate!=$targetFinish)
				{
					//~ $datesArray[] = "'".$targetFinish."':{'name':'".$targetFinish."'}";
					$datesArray[$targetFinish] = array('name'=>$targetFinish);
				}
				$targetFinish = addDays(1,$targetFinish);
			}
			
			echo json_encode($datesArray);
		}
		exit(0);
	}
	else if(isset($_GET['reviewId']))
	{
		/*	Insert Process and Schedule to Workschedule (Transfer data from system_temporaryworkschedule to ppic_workschedule)
		 * 	- INSERT INTO `ppic_workschedule`
		 * 	- DELETE FROM system_temporaryworkschedule				
		 *	Finished the PO Scheduling process
		 * 	- UPDATE ppic_workschedule SET status = 1
		 * 	- UPDATE system_machineWorkschedule SET status = 1
		 *	Change Recovery Date based on the target finish of Due Date(PH) or Delivery(JP) Process
		 * 	- UPDATE system_lotlist SET recoveryDate
		 * 	Insert DMS Making Process in Engineering Lot (for New Items only)
		 *	- INSERT INTO `ppic_workschedule`
		 * 		DMS Making
		 * 		DMS Checking
		 * 		Inprocess Data Input
		 * 	Delete RO Review Data
		 * 	- DELETE FROM ppic_roreviewdatatemp
		 * 	- DELETE FROM ppic_roreviewdetailstemp
		 */
		
		$reviewId = $_GET['reviewId'];
		$reviewId1 = (isset($_GET['reviewId1'])) ? $_GET['reviewId1'] : '';
		$manual = $_GET['manual'];
		
		if($reviewId1!='')
		{
			$poIdArray = array();
			$sql = "SELECT poId FROM ppic_roreviewdata WHERE roReviewId = ".$reviewId1."";
			$queryPoId= $db->query($sql);
			if($queryPoId->num_rows > 0)
			{
				while($resultPoId = $queryPoId->fetch_assoc())
				{
					$poIdArray[] = $resultPoId['poId'];
				}
			}
			$reviewId = $reviewId1;
		}
		else
		{
			$poIdArray = array();
			$sql = "SELECT poId FROM ppic_roreviewdatatemp";
			$queryPoId= $db->query($sql);
			if($queryPoId->num_rows > 0)
			{
				while($resultPoId = $queryPoId->fetch_assoc())
				{
					$poIdArray[] = $resultPoId['poId'];
				}
			}
			else
			{
				$sql = "SELECT poId FROM ppic_roreviewdata WHERE roReviewId = ".$reviewId."";
				$queryPoId= $db->query($sql);
				if($queryPoId->num_rows > 0)
				{
					while($resultPoId = $queryPoId->fetch_assoc())
					{
						$poIdArray[] = $resultPoId['poId'];
					}
				}	
			}
		}
		
		//~ if($_SESSION['idNumber']!='0346') $sqlFilter = "AND ROUND((LENGTH(lotNumber)-LENGTH(REPLACE(lotNumber,'-','')))/LENGTH('-')) = 2";
		
		$poIdTempArray = $poIdArray;
		$poIdArray = array();
		$sql = "SELECT lotNumber, poId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdTempArray).") AND identifier = 1 AND partLevel = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumber = $resultLotList['lotNumber'];
				$poId = $resultLotList['poId'];
				
				$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 324 AND status = 0 LIMIT 1";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$poIdArray[] = $poId;					
				}
			}
		}
		
		if(count($poIdArray) > 0)
		{
			$lotNumberArray = array();
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0 ".$sqlFilter."";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumberArray[] = "'".$resultLotList['lotNumber']."'";
				}
			}
			
			// ----- Start ----- Insert Process and Schedule to Workschedule (Transfer data from system_temporaryworkschedule to ppic_workschedule)
			$sql = "
						INSERT INTO `ppic_workschedule`
								(	`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
						SELECT		`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
						FROM	`system_temporaryworkschedule`
						WHERE	idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber IN(".implode(",",$lotNumberArray).") AND destination = 0 ORDER BY lotNumber, processOrder
			";
			$queryInsert = $db->query($sql);
			
			if($queryInsert)
			{
				$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber IN(".implode(",",$lotNumberArray).")";
				$queryDelete = $db->query($sql);
			}
			// ----- End ----- Insert Process and Schedule to Workschedule (Transfer data from system_temporaryworkschedule to ppic_workschedule)
			
			$lotNoProcessArray = $workscheduleIdArray = array();
			$sql = "SELECT id, lotNumber FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND processCode = 324";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$workscheduleIdArray[] = $resultWorkSchedule['id'];
					$lote = $resultWorkSchedule['lotNumber'];
					
					$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lote."' AND processCode NOT IN(299,298,493,460,530,459,324) LIMIT 1";
					$queryCheckProcess = $db->query($sql);
					if($queryCheckProcess AND $queryCheckProcess->num_rows > 0)
					{
						finishProcess("",$resultWorkSchedule['id'], 0, $_SESSION['idNumber'],'');
					}
					else
					{
						$lotNoProcessArray[] = $lote;
					}
				}
			}
			
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0 ".$sqlFilter."";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lote = $resultLotList['lotNumber'];
					
					$lotePartsArray = explode("-",$lote);
					if(count($lotePartsArray)==4)
					{
						$sql = "UPDATE system_lotOnHold SET status = 1, remarksUnhold='Automatic Unhold', dateUnhold=NOW() WHERE lotNumber LIKE '".$lote."' AND status = 0 LIMIT 1";
						$queryUpdate = $db->query($sql);

						updateAvailability($lote);
						
						$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lote."' AND processOrder > 0 AND status = 0 LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
						{
							$mainLot = $lotePartsArray[0]."-".$lotePartsArray[1]."-".$lotePartsArray[2];
							$sql = "
								INSERT INTO `ppic_workschedule`
										(	`poId`, `customerId`, `poNumber`, `lotNumber`,	`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
								SELECT		`poId`, `customerId`, `poNumber`, '".$lote."',	`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
								FROM `ppic_workschedule` WHERE `lotNumber` LIKE '".$mainLot."' AND `processOrder` > 0 ORDER BY processOrder
							";
							$queryInsert = $db->query($sql);
						}
					}
				}
			}
			
			//Activated 2018-09-12
			$sqlFilter = "AND ROUND((LENGTH(lotNumber)-LENGTH(REPLACE(lotNumber,'-','')))/LENGTH('-')) = 2";
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0 AND identifier = 1 ".$sqlFilter."";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					createTemporaryMaterial($resultLotList['lotNumber']);
				}
			}
			
			//~ $sql = "UPDATE system_lotlist SET recoveryDate = '".$resultSchedule['delivery']."' WHERE lotNumber IN(".implode(",",$lotNumberArray).")";
			//~ $queryUpdate = $db->query($sql);//2017-06-02
			
			//~ $sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).")";
			
			
			// ----- Start ----- Change Recovery Date based on the target finish of Due Date(PH) or Delivery(JP) Process
			if($_GET['country']==2)
			{
				$internalDeliveryDateArray = array();
				$sql = "SELECT lotNumber, targetFinish FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND processCode = 144";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$targetFinish = $resultWorkSchedule['targetFinish'];
						
						if(!isset($internalDeliveryDateArray[$targetFinish]))	$internalDeliveryDateArray[$targetFinish] = array();
						
						$internalDeliveryDateArray[$targetFinish][] = $lotNumber;
					}
				}
				
				if(count($internalDeliveryDateArray) > 0)
				{
					foreach($internalDeliveryDateArray as $internalDel => $lotNoArray)
					{
						$sql = "UPDATE system_lotlist SET recoveryDate = '".$internalDel."' WHERE lotNumber IN('".implode("','",$lotNoArray)."')";
						$queryUpdate = $db->query($sql);
					}
				}
			}
			else
			{
				$internalDeliveryDateArray = array();
				$sql = "SELECT lotNumber, targetFinish FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND processCode = 518";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$targetFinish = $resultWorkSchedule['targetFinish'];
						
						if(!isset($internalDeliveryDateArray[$targetFinish]))	$internalDeliveryDateArray[$targetFinish] = array();
						
						$internalDeliveryDateArray[$targetFinish][] = $lotNumber;
					}
				}
				
				if(count($internalDeliveryDateArray) > 0)
				{
					foreach($internalDeliveryDateArray as $internalDel => $lotNoArray)
					{
						$sql = "UPDATE system_lotlist SET recoveryDate = '".$internalDel."' WHERE lotNumber IN('".implode("','",$lotNoArray)."')";
						$queryUpdate = $db->query($sql);
					}
				}
			}
			// ----- End ----- Change Recovery Date based on the target finish of Due Date(PH) or Delivery(JP) Process
			
			
			// ******************** Insert DMS Making Per Process for new items 2018-05-12 ******************** //
			$arrayNoDMSMakingProcess = '324,141,174,313,459,403,95,366,367,432,431,430,312,136,227,171,96,94,358,317,228,172,145,144,137,138,229,448,426,424,364,352,346,343,342,242,241,238,230,220,205,197,167,163,92,91,179,162,254,184,496,518,461,299,530,460,533,437,493,539,540,298,597,598,599,600,601,602,603';
			
			$newItemPoIdArray = array();
			$sql = "SELECT DISTINCT poId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0 AND identifier = 5";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$newItemPoIdArray[] = $resultLotList['poId'];
				}
				
				$sql = "SELECT lotNumber, poId, partId FROM ppic_lotlist WHERE poId IN(".implode(",",$newItemPoIdArray).") AND partLevel > 0 AND identifier = 1 ".$sqlFilter."";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumber = $resultLotList['lotNumber'];
						$poId = $resultLotList['poId'];
						$partId = $resultLotList['partId'];
						
						$lot = '';
						$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND partId = ".$partId." AND identifier = 5 LIMIT 1";
						$queryLot = $db->query($sql);
						if($queryLot AND $queryLot->num_rows > 0)
						{
							$resultLot = $queryLot->fetch_assoc();
							$lot = $resultLot['lotNumber'];
							
							$processOrder = '';
							$sql = "SELECT processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' ORDER BY processOrder DESC LIMIT 1";
							$query = $db->query($sql);
							if($query AND $query->num_rows > 0)
							{
								$result = $query->fetch_assoc();
								$processOrder = $result['processOrder'];
							}
							
							$sql = "SET @newProcessOrder = ".$processOrder.";";
							$query = $db->query($sql);
							
							$sql = "SET @newProcessOrder1 = ".($processOrder+1).";";
							$query = $db->query($sql);
							
							$sql = "SET @newProcessOrder2 = ".($processOrder+2).";";
							$query = $db->query($sql);
							
							if($_GET['country']==2)
							{
								$dmsMaking = 564;
								$dmsChecking = 570;
								$inprocessData = 577;
							}//462,519,523
							else
							{
								$dmsMaking = 462;
								$dmsChecking = 519;
								$inprocessData = 523;
							}
							
							$sql = "SELECT id, processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' AND processCode IN(".$dmsMaking.",".$dmsChecking.",".$inprocessData.") LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
							{
								//Insert DMS Making
								$sql = "
									INSERT INTO `ppic_workschedule`
											(	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 							`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '".$dmsMaking."', 			@newProcessOrder := ( @newProcessOrder+3 ), '40', 				`processCode`, 		`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									FROM	ppic_workschedule
									WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder
								";
								$queryInsert = $db->query($sql);
								
								//Insert DMS Checking
								$sql = "
									INSERT INTO `ppic_workschedule`
											(	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 							`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '".$dmsChecking."', 			@newProcessOrder1 := ( @newProcessOrder1+3 ), '40', 			`processCode`, 		`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									FROM	ppic_workschedule
									WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder
								";
								$queryInsert = $db->query($sql);
								
								//Insert Inprocess Data Input
								$sql = "
									INSERT INTO `ppic_workschedule`
											(	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 							`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '".$inprocessData."', 			@newProcessOrder2 := ( @newProcessOrder2+3 ), '34', 			`processCode`, 		`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									FROM	ppic_workschedule
									WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder
								";
								$queryInsert = $db->query($sql);
								
								$processOrder = '';
								$sql = "SELECT processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' ORDER BY processOrder DESC LIMIT 1";
								$query = $db->query($sql);
								if($query AND $query->num_rows > 0)
								{
									$result = $query->fetch_assoc();
									$processOrder = $result['processOrder'];
								}
								
								//Insert Inprocess Data Input
								//~ $sql = "
									//~ INSERT INTO `ppic_workschedule`
											//~ (	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 	`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									//~ SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '523', 			'".$processOrder."', '34', 				`processCode`, 		`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									//~ FROM	ppic_workschedule
									//~ WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder LIMIT 1
								//~ ";
								//~ $queryInsert = $db->query($sql);
								
								$sql = "SELECT id, processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' AND processCode IN(".$dmsMaking.",".$dmsChecking.",".$inprocessData.")";
								$queryWorkSchedule = $db->query($sql);
								if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
								{
									while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
									{
										$id = $resultWorkSchedule['id'];
										$processRemarks = $resultWorkSchedule['processRemarks'];
										
										
										
										$processName = '';
										$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$processRemarks." LIMIT 1";
										$queryProcess = $db->query($sql);
										if($queryProcess AND $queryProcess->num_rows > 0)
										{
											$resultProcess = $queryProcess->fetch_assoc();
											$processName = $resultProcess['processName'];
										}
										
										$sql = "UPDATE ppic_workschedule SET processRemarks = '".$processName."' WHERE id = ".$id." LIMIT 1";
										$queryUpdate = $db->query($sql);
									}
								}
							}
						}
					}
				}
			}
			// ******************** Insert DMS Making Per Process for new items 2018-05-12 ******************** //	
			
			/* 2020-07-11 gerald
			if($_GET['country']==2)
			{
				foreach($lotNumberArray as $lote)
				{
					$sql = "DELETE FROM ppic_workschedule WHERE lotNumber LIKE ".$lote." AND processCode = 496";
					$queryDelete = $db->query($sql);
					
					$sql = "SET @newProcessOrder = 0";
					$query = $db->query($sql);
					
					$sql = "UPDATE `ppic_workschedule` SET processOrder = @newProcessOrder := ( @newProcessOrder +1 ) WHERE lotNumber LIKE ".$lote." AND processOrder > 0 ORDER BY processOrder";
					$queryUpdate = $db->query($sql);
				}
			}*/
			
			//******************************* Update sales notes (2020-02-28) ******************************* //
			$sql = "SELECT poId, note FROM system_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND note != '' GROUP BY poId";
			$queryNotes = $db->query($sql);
			if($queryNotes AND $queryNotes->num_rows > 0)
			{
				while ($resultNotes = $queryNotes->fetch_assoc()) 
				{
					$poId = $resultNotes['poId'];
					$note = $resultNotes['note'];

					$lotNumberArray = Array ();
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE identifier IN (1,2) AND poId = ".$poId;
					$queryLotlist = $db->query($sql);
					if($queryLotlist AND $queryLotlist->num_rows > 0)
					{
						while($resultLotlist = $queryLotlist->fetch_assoc())
						{
							$lotNumberArray[] = $resultLotlist['lotNumber'];
						}
					}

					$sql = "UPDATE view_workschedule SET priorityRemarks = '".$db->real_escape_string($note)."' WHERE lotNumber IN ('".implode("', '",$lotNumberArray)."')";
					$queryUpdate = $db->query($sql);
				}
			}		
			//******************************* Update sales notes (2020-02-28) ******************************* //
		}
		
		if(count($poIdTempArray) > 0)
		{
			//~ if($_SESSION['idNumber']=='0346')//Activated 2020-07-22
			//~ {
				//~ $sql = "SELECT lotNumber, poId, partLevel FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdTempArray).") AND identifier = 1";
				$sql = "SELECT lotNumber, poId, identifier, partLevel FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdTempArray).")";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumber = $resultLotList['lotNumber'];
						$poId = $resultLotList['poId'];
						$identifier = $resultLotList['identifier'];
						$partLevel = $resultLotList['partLevel'];
						
						if($identifier==1)
						{
							$lotePartsArray = explode("-",$lotNumber);
							if(count($lotePartsArray)==3)
							{
								if($partLevel==1)
								{
									$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 459 AND status = 0 LIMIT 1";
									$queryWorkSchedule = $db->query($sql);
									if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
									{
										$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
										finishProcess("",$resultWorkSchedule['id'], 0, $_SESSION['idNumber'],'');
										
										$sql = "SELECT lotNumber, recoveryDate FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
										$queryLotList = $db->query($sql);
										if($queryLotList AND $queryLotList->num_rows > 0)
										{
											$resultLotList = $queryLotList->fetch_assoc();
											$lotNumber = $resultLotList['lotNumber'];
											$dueDate = $resultLotList['recoveryDate'];
											
											generateScheduleItems($poId,array('start'=>'','dueDate'=>$dueDate,'remarksLog'=>'from RO Review (Finish)'),1,0);//Activated 2020-08-11 1:43 PM
										}
									}
								}
							}
							else
							{
								$sql = "UPDATE system_lotOnHold SET status = 1, remarksUnhold='Automatic Unhold', dateUnhold=NOW() WHERE lotNumber LIKE '".$lotNumber."' AND status = 0 LIMIT 1";
								$queryUpdate = $db->query($sql);
								
								$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder > 0 AND status = 0 LIMIT 1";
								$queryWorkSchedule = $db->query($sql);
								if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
								{
									$mainLot = $lotePartsArray[0]."-".$lotePartsArray[1]."-".$lotePartsArray[2];
									$sql = "
										INSERT INTO `ppic_workschedule`
												(	`poId`, `customerId`, `poNumber`, `lotNumber`,			`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
										SELECT		`poId`, `customerId`, `poNumber`, '".$lotNumber."',	`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
										FROM `ppic_workschedule` WHERE `lotNumber` LIKE '".$mainLot."' AND `processOrder` > 0 ORDER BY processOrder
									";
									$queryInsert = $db->query($sql);
								}
							}
						}
						
						updateAvailability($lotNumber);
					}
				}			
			//~ }
		}
	}
	
	// ----- Start ----- Delete RO Review Data
	$sqlDelete = "DELETE FROM ppic_roreviewdatatemp";	$queryDelete = $db->query($sqlDelete);
	$sqlDelete = "DELETE FROM ppic_roreviewdetailstemp";	$queryDelete = $db->query($sqlDelete);	
	// ----- End ----- Delete RO Review Data
	
	if(count($lotNoProcessArray) > 0)
	{
		$sqlMain = "INSERT INTO `system_noprocess`(`roReviewId`,`lotNumber`) VALUES ";
		$counter = 0;
		$sqlValueArray = array();
		foreach($lotNoProcessArray as $lote)
		{
			$sqlValueArray[] = "('".$reviewId."','".$lote."')";
			
			$counter++;
			
			if($counter==50)
			{
				$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
				$queryUpdate = $db->query($sqlInsert);
				$counter = 0;
				$sqlValueArray = array();
			}
		}
		if(count($sqlValueArray) > 0)
		{
			$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
			$queryUpdate = $db->query($sqlInsert);
		}
		
		header("location:rose_roreviewSoftware.php?reviewId=".$reviewId."&errorFlag=1");
	}
	else
	{
		header("location:rose_roreviewSoftware.php?reviewId=".$reviewId."");
	}
	exit(0);
?>
