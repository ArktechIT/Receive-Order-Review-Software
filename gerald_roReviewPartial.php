<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('Templates/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors", "on");
	
	function partialBooking($lot,$quantity,$employeeId,$partialBatchId=0)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$dashPosition = strrpos($lot, "-");	
		if($dashPosition>=9)
		{
			$originalLotNumber=substr($lot,0,$dashPosition);
		}
		else
		{
			$originalLotNumber = $lot;			
		}
		// -------------------------------------------- Detect Latest Lot Number and Create New Lot Number -----------------------------------------------------------
		$sql="SELECT MAX( CAST(SUBSTRING(lotNumber,LOCATE('-',lotNumber,10)+1) AS SIGNED) ) as max	FROM ppic_lotlist where lotNumber like '".$originalLotNumber."-%'";	
		$lotQuery=$db->query($sql);
		if($lotQuery->num_rows>0)
		{
			$lotQueryResult = $lotQuery->fetch_assoc();
			$newLotNumber = $originalLotNumber."-".($lotQueryResult['max']+1);
		}
		else
		{
			$newLotNumber = $originalLotNumber."-1";
		}
		// ----------------------------------------------------------------------------------------------------------------------------------------------------------
		
		// ----------------------------------------- Insert Lot Data Into ppic_lotlist ----------------------------------------------------
		$sql="SELECT poId, partId, parentLot, partLevel, workingQuantity, identifier, status, poContentId FROM ppic_lotlist where lotNumber like '".$lot."' AND workingQuantity > ".$quantity." LIMIT 1";	
		$lotQuery=$db->query($sql);
		if($lotQuery->num_rows > 0)
		{
			$lotQueryResult = $lotQuery->fetch_assoc();
			
			$newQuantity = $lotQueryResult['workingQuantity']-$quantity;
			$sql = "insert into ppic_lotlist (lotNumber, poId , partId, parentLot, partLevel, workingQuantity, identifier, dateGenerated, status, bookingStatus, poContentId, partialBatchId) values ('".$newLotNumber."', ".$lotQueryResult['poId'].", ".$lotQueryResult['partId'].", '".$lotQueryResult['parentLot']."', ".$lotQueryResult['partLevel'].", ".$quantity.", ".$lotQueryResult['identifier'].", now(), ".$lotQueryResult['status'].", 1, '".$lotQueryResult['poContentId']."','".$partialBatchId."')";
			
			$query = $db->query($sql);
			// ---------------------------------------------------------------------------------------------------------------------------------
			
			// --------------------------------------- Update Working Quantity of Source Lot ---------------------------------------------------
			$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$newQuantity." WHERE lotNumber like '".$lot."'";	
			
			$query = $db->query($sql);
			// ------------------------------------------------------------------------------------------------------ --------------------------

			// -------------------------------------------- Retrieve Work Schedule Data --------------------------------------------------------
			if($_GET['country']==1)
			{
				$excemptedProcess = "141,174";
			}
			else
			{
				$excemptedProcess   = "141";
			}
			$sql = "select poId, customerId, poNumber, partNumber, revisionId, receiveDate, deliveryDate, recoveryDate, urgentFlag, subconFlag, partLevelFlag from ppic_workschedule where lotNumber like '".$lot."' and processCode NOT IN (".$excemptedProcess.") LIMIT 1";
			$workScheduleDetailQuery=$db->query($sql);
			$workScheduleDetailQueryResult = $workScheduleDetailQuery->fetch_assoc();	
			// -------------------------------------------- End of Retrieve Work Schedule Data --------------------------------------------------------
			
			//~ $sql = "
				//~ INSERT INTO `ppic_workschedule`
						//~ (	`poId`, `customerId`, `poNumber`, `lotNumber`,			`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
				//~ SELECT		`poId`, `customerId`, `poNumber`, '".$newLotNumber."',	`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
				//~ FROM `ppic_workschedule` WHERE `lotNumber` LIKE '".$lot."' AND `processCode` IN (459,324) ORDER BY FIELD(processCode,459,324)
			//~ ";
			//~ $queryInsert = $db->query($sql);
			$sql = "
				INSERT INTO `ppic_workschedule`
						(	`poId`, `customerId`, `poNumber`, `lotNumber`,			`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
				SELECT		`poId`, `customerId`, `poNumber`, '".$newLotNumber."',	`partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
				FROM `ppic_workschedule` WHERE `lotNumber` LIKE '".$lot."' AND `processOrder` > 0 ORDER BY processOrder
			";
			$queryInsert = $db->query($sql);
			
			$sql = "INSERT INTO `system_lotOnHold`(`lotNumber`, `date`, `remarks`, `employeeId`) VALUES ('".$newLotNumber."',NOW(),'Automated Hold (Unhold automatically after RO Review process of the main lot has been finished)','".$_SESSION['idNumber']."')";
			$queryInsert = $db->query($sql);
			
			// ------------------------------------------------ Insert Into PRS Log --------------------------------------------------------------------
			$sql="INSERT INTO ppic_prslog (lotNumber,employeeId,date,remarks,type,sourceLotNumber,partialQuantity) values ('".$newLotNumber."', '".$employeeId."', now(), 'Automated Partial', 3,'".$lot."', '".$quantity."')";	
			$query = $db->query($sql);
			// -----------------------------------------------------------------------------------------------------------------------------------------
			
			$sql = "SELECT id, processSection FROM `ppic_workschedule` WHERE lotNumber LIKE '".$newLotNumber."' AND processCode IN(312,430,431,432) ORDER BY processOrder LIMIT 1";
			$query = $db->query($sql);
			if($query AND $query->num_rows > 0)
			{
				$result = $query->fetch_assoc();
				$id = $result['id'];
				$processSection = $result['processSection'];
				
				$insert = "INSERT INTO `system_machineWorkschedule`(`workScheduleId`, `machineId`, `idNumber`, `sectionId`, `inputDate`, `inputTime`, `status`, `printStatus`) VALUES (".$id.",0,'".$employeeId."',".$processSection.",NOW(),NOW(),0,0)";
				$insertQuery = $db->query($insert);
			}
			
			$materialRequirement = $newMaterialRequirement = 0;
			$sql = "SELECT listId, materialRequirement FROM engineering_bookingdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryBookingDetails = $db->query($sql);
			if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
			{
				$resultBookingDetails = $queryBookingDetails->fetch_assoc();
				$listId = $resultBookingDetails['listId'];
				$materialRequirement = $resultBookingDetails['materialRequirement'];
				
				$newMaterialRequirement = ($newQuantity * $materialRequirement) / ($newQuantity + $quantity);
				
				$sql = "UPDATE engineering_bookingdetails SET materialRequirement = '".$newMaterialRequirement."' WHERE listId = ".$listId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
			
			$sql = "
				INSERT INTO `engineering_bookingdetails`
					(	`bookingId`, `lotNumber`, 			`quantity`,		`status`, `materialRequirement`)
				SELECT	`bookingId`, '".$newLotNumber."',	'".$quantity."',`status`, '".($materialRequirement-$newMaterialRequirement)."'
				FROM engineering_bookingdetails WHERE lotNumber LIKE '".$lot."' LIMIT 1
			";
			$queryInsert = $db->query($sql);
		}
		
		return $newLotNumber;
	}
	
	function cancelPartial($poId,$insertFlag=0)
	{
		include('PHP Modules/mysqliConnection.php');
		
		//~ $sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND lotNumber LIKE '%-%-%-%' LIMIT 1";
		//~ $queryLotList = $db->query($sql);
		//~ if($queryLotList AND $queryLotList->num_rows > 0)
		//~ {
			//~ echo "Cannot Partial";
			//~ exit(0);
		//~ }
		
		$lotNumberArray = array();
		$sql = "SELECT SUBSTRING_INDEX(lotNumber,'-',3) as mainLot, GROUP_CONCAT(lotNumber) as lots, SUM(workingQuantity) as totalWQty FROM `ppic_lotlist` WHERE `poId` = ".$poId." GROUP BY mainLot";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$mainLot = $resultLotList['mainLot'];
				$totalWQty = $resultLotList['totalWQty'];
				$lots = $resultLotList['lots'];
				
				$lotNumberArray[] = $mainLot;
				
				echo "<hr>".$mainLot." (".$lots.")";
				
				echo "<br>".$sql = "DELETE FROM ppic_workschedule WHERE lotNumber IN('".str_replace(",","','",$lots)."') AND lotNumber != '".$mainLot."'";
				if($insertFlag==1) $queryDelete = $db->query($sql);
				
				echo "<br>".$sql = "UPDATE ppic_prslog SET type = 7 WHERE lotNumber IN('".str_replace(",","','",$lots)."') AND lotNumber != '".$mainLot."'";
				if($insertFlag==1) $queryUpdate = $db->query($sql);
				
				echo "<br>".$sql = "DELETE FROM system_lotOnHold WHERE lotNumber IN('".str_replace(",","','",$lots)."') AND lotNumber != '".$mainLot."' AND remarks LIKE 'Automated Hold (Unhold automatically after RO Review process of the main lot has been finished)'";
				if($insertFlag==1) $queryDelete = $db->query($sql);
				
				echo "<br>".$sql = "DELETE FROM ppic_lotlist WHERE lotNumber IN('".str_replace(",","','",$lots)."') AND lotNumber != '".$mainLot."'";
				if($insertFlag==1) $queryDelete = $db->query($sql);
				
				echo "<br>".$sql = "UPDATE ppic_lotlist SET workingQuantity = '".$totalWQty."' WHERE lotNumber LIKE '".$mainLot."' LIMIT 1";
				if($insertFlag==1) $queryUpdate = $db->query($sql);
			}
			
			$sql = "UPDATE ppic_workschedule SET status = 0 WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode NOT IN(324)";
			$queryUpdate = $db->query($sql);			
		}
	}	
	
	//~ $poId = $_GET['poId'];//1481196, 1481176
	//~ $lotCount = (isset($_GET['lotCount'])) ? $_GET['lotCount'] : 2;//1481196, 1481176
	
	//~ $lotNumber = '21-08-6409';//100
	//~ $lotNumber = '21-08-6333';//1000
	
	//~ $lotCount = 7;
	
	if(isset($_POST['submit']))
	{
		$poId = $_POST['poId'];//1481196, 1481176
		$lotCount = $_POST['lotCount'];
		
		//~ if($_SESSION['idNumber']!='0346')
		//~ {
			//~ echo "test";
			//~ exit(0);
		//~ }
		
		$customerDeliveryDate = $customerId = $receiveDate = '';
		$poQuantity = 0;
		$sql = "SELECT poQuantity, customerId, receiveDate, customerDeliveryDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			$resultPoList = $queryPoList->fetch_assoc();
			$poQuantity = $resultPoList['poQuantity'];
			$customerId = $resultPoList['customerId'];
			$receiveDate = $resultPoList['receiveDate'];
			$customerDeliveryDate = $resultPoList['customerDeliveryDate'];
		}
		
		$dueDate = $recoveryDate = '';
		$sql = "SELECT answerDate, recoveryDate FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			$resultLotList = $queryLotList->fetch_assoc();
			$customerDeliveryDate = ($resultLotList['answerDate']!='0000-00-00') ? $resultLotList['answerDate'] : $customerDeliveryDate;
			$recoveryDate = $resultLotList['recoveryDate'];
		}
		
		
		$sql = "SELECT lotNumber, poId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			$resultLotList = $queryLotList->fetch_assoc();
			$loteLote = $resultLotList['lotNumber'];
				
			$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$loteLote."' AND processCode = 324 AND status = 0 LIMIT 1";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
	
				$sql = "SELECT dueDate FROM ppic_roreviewdatatemp where poId=".$poId." AND dueDate != '0000-00-00' LIMIT 1";
				$queryRoReviewDataTemp = $db->query($sql);
				if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
				{
					$resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
					$dueDate = $resultRoReviewDataTemp['dueDate'];
				}
				else
				{
					$sql = "SELECT dueDate FROM ppic_roreviewdata where poId=".$poId." AND dueDate != '0000-00-00' LIMIT 1";
					$queryRoReviewDataTemp = $db->query($sql);
					if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
					{
						$resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
						$dueDate = $resultRoReviewDataTemp['dueDate'];
					}
				}
			}
			
			if($dueDate=='')
			{
				$deliveryType = '';
				$sql = "SELECT deliveryType FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
				$queryCustomer = $db->query($sql);
				if($queryCustomer AND $queryCustomer->num_rows > 0)
				{
					$resultCustomer = $queryCustomer->fetch_assoc();
					$deliveryType = $resultCustomer['deliveryType'];
				}
				
				$deliveryInterval = 1;
				if($deliveryType==1)
				{
					$deliveryInterval = 5;//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
				}
				else if($deliveryType==2)
				{
					$deliveryInterval = 7;
				}
				else if($deliveryType==3)
				{
					$deliveryInterval = 30;
				}
				
				$dueDate = date("Y-m-d",strtotime($customerDeliveryDate."-".$deliveryInterval." Days"));
				
				$day =  date('l', strtotime($dueDate));
				
				// -------------------------- Check If Incremented / Decremented Date Is Holiday Or Sunday ----------------------
				if($_GET['country']=='1')//Philippines
				{
					$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType < 6 LIMIT 1";
				}
				else if($_GET['country']=='2')//Japan
				{
					$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType >= 6 LIMIT 1";
				}
				$dc = $db->query($sql);
				$dcnum = $dc->num_rows;
				// -------------------------- Increment / Decrement Date If Holiday Or Sunday ----------------------
				if($day=='Sunday' OR $dcnum > 0)
				{
					$dueDate = addDays(-1,$dueDate);
				}
			}			
		}		
		//~ echo $dueDate;
		//~ exit(0);
		if($poQuantity<=0)
		{
			echo "PO Quantity error";
			exit(0);
		}
		
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND lotNumber LIKE '%-%-%-%' LIMIT 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			echo "Cannot Partial";
			exit(0);
		}
		
		$lotDataArray = array();
		
		$lotNumber = '';
		$workingQuantity = 0;
		$sql = "SELECT lotNumber, workingQuantity, identifier, partLevel, parentLot FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) ORDER BY partLevel";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumber = $resultLotList['lotNumber'];
				$workingQuantity = $resultLotList['workingQuantity'];
				$identifier = $resultLotList['identifier'];
				$partLevel = $resultLotList['partLevel'];
				$parentLot = $resultLotList['parentLot'];
				
				$multiplier = 1;
				
				if($partLevel==1 AND $identifier==1)
				{
					if($poQuantity!=$workingQuantity)
					{
						echo "Cannot Partial";
						break;
					}
				}
				
				if($partLevel > 1)
				{
					$parentWorkingQuantity = 0;
					$sql = "SELECT workingQuantity FROM ppic_lotlist WHERE lotNumber LIKE '".$parentLot."' LIMIT 1";
					$queryLotParentQuantity = $db->query($sql);
					if($queryLotParentQuantity AND $queryLotParentQuantity->num_rows > 0)
					{
						$resultLotParentQuantity = $queryLotParentQuantity->fetch_assoc();
						$parentWorkingQuantity = $resultLotParentQuantity['workingQuantity'];
					}
					
					$multiplier = $workingQuantity / $parentWorkingQuantity;
					
					$tempQuantity = $parentWorkingQuantity;
				}
				else
				{
					$tempQuantity = $workingQuantity;
				}
				
				$quotient = $tempQuantity/$lotCount;
				
				$quotientFloor = floor($quotient);
				$remainingQuantity = ($tempQuantity - ($quotientFloor * $lotCount));
				
				$lotQtyArray = array();
				
				$tempRemainingQuantity = $remainingQuantity;
				
				$i = 0;
				while($i < $lotCount)
				{
					$plusQty = 0;
					$i++;
					if($tempRemainingQuantity > 0)
					{
						$plusQty++;
						$tempRemainingQuantity--;
					}
						
					$quantity = $quotientFloor + $plusQty;
					
					$lotQtyArray[] = $quantity * $multiplier;
				}
				
				$lotDataArray[] = array($lotNumber,$lotQtyArray);
			}
		}
		
		$employeeId = $_SESSION['idNumber'];
		
		if(count($lotDataArray) > 0)
		{
			foreach($lotDataArray as $valueArray)
			{
				$lotNumber = $valueArray[0];
				$lotQtyArray = $valueArray[1];
				
				if(count($lotQtyArray) > 0)
				{
					$i = 0;
					foreach($lotQtyArray as $key=>$quantity)
					{
						if($key > 0)
						{
							$partialBatchId = ++$i;
							partialBooking($lotNumber,$quantity,$employeeId,$partialBatchId);
						}
					}
					//~ echo "<br>Lot : ".implode("<br>Lot : ",$lotQtyArray);
				}
			}
			
			/*
			$lotNumberArray = array();
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) ORDER BY partLevel";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumberArray[] = $resultLotList['lotNumber'];
				}
				
				$sql = "UPDATE ppic_workschedule SET status = 1 WHERE lotNumber IN('".implode("','",$lotNumberArray)."')";
				$queryUpdate = $db->query($sql);
			}*/
			
			$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
			$queryDelete = $db->query($sql);			
			
			//~ echo "asd".$dueDate;
			
			$startDate = date('Y-m-d');
			if(strtotime($dueDate) < strtotime($startDate))
			{
				$startDate = $receiveDate;
			}
			
			generateScheduleItems($poId,array('start'=>$startDate,'dueDate'=>$dueDate,'remarksLog'=>'Auto Partial from RO Review'));
			//~ generateScheduleItems($poId,array('start'=>'2020-07-27','dueDate'=>'2020-08-20','remarksLog'=>'test'));
			//~ header("location:gerald_lotScheduleViewer.php?poId=".$poId);			
			
			?>
			<script>
				location.href='gerald_lotScheduleViewer.php?poId=<?php echo $poId;?>';
			</script>
			<?php
			exit(0);
		}
	}
	else
	{
		$poId = $_GET['poId'];
		
		/*
		$sql = "
			SELECT	a.listId, a.processCode, a.processOrder, 
					a.lotNumber, a.targetStart, a.targetFinish,
					b.lotNumber, b.targetStart, b.targetFinish,
					c.lotNumber, c.targetStart, c.targetFinish,
					d.lotNumber, d.targetStart, d.targetFinish FROM system_temporaryworkschedule as a
			LEFT JOIN system_temporaryworkschedule as b ON b.lotNumber = CONCAT(a.lotNumber,'-1') AND b.processCode = a.processCode AND b.processOrder = a.processOrder
			LEFT JOIN system_temporaryworkschedule as c ON c.lotNumber = CONCAT(a.lotNumber,'-2') AND c.processCode = a.processCode AND c.processOrder = a.processOrder
			LEFT JOIN system_temporaryworkschedule as d ON d.lotNumber = CONCAT(a.lotNumber,'-3') AND d.processCode = a.processCode AND d.processOrder = a.processOrder
			WHERE a.idNumber LIKE '0346' AND a.lotNumber = '20-07-3095'			
		";
		*/
		
		$poNumber = $customerId = '';
		$poQuantity = $partId = 0;
		$sql = "SELECT poQuantity, partId, poNumber, customerId FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			$resultPoList = $queryPoList->fetch_assoc();
			$poQuantity = $resultPoList['poQuantity'];
			$partId = $resultPoList['partId'];
			$poNumber = $resultPoList['poNumber'];
			$customerId = $resultPoList['customerId'];
		}
		
		if($poQuantity<=0)
		{
			echo "PO Quantity error";
			exit(0);
		}
		
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND lotNumber LIKE '%-%-%-%' LIMIT 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			// cancelPartial($poId,1);
			echo "Cannot Partial";
			exit(0);
		}
		
		$customerAlias = $partNumber = $revisionId = '';
		$sql = "SELECT customerAlias, partNumber, revisionId FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			$resultLotList = $queryLotList->fetch_assoc();
			$customerAlias = $resultLotList['customerAlias'];
			$partNumber = $resultLotList['partNumber'];
			$revisionId = $resultLotList['revisionId'];
		}
		else
		{
			$sql = "SELECT partNumber, revisionId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_assoc();
				$partNumber = $resultParts['partNumber'];
				$revisionId = $resultParts['revisionId'];
			}
			
			$sql = "SELECT customerAlias FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
			$queryCustomer = $db->query($sql);
			if($queryCustomer AND $queryCustomer->num_rows > 0)
			{
				$resultCustomer = $queryCustomer->fetch_assoc();
				$customerAlias = $resultCustomer['customerAlias'];
			}
		}
		
		?>
		<form action='' method='post' id='formId'></form>
		<input type='hidden' name='poId' value='<?php echo $poId;?>' form='formId'>
		<table border='1'>
			<tr>
				<td>Customer</td>
				<td><?php echo $customerAlias;?></td>
			</tr>
			<tr>
				<td>PO Number</td>
				<td><?php echo $poNumber;?></td>
			</tr>
			<tr>
				<td>Part Number</td>
				<td><?php echo $partNumber;?></td>
			</tr>
			<tr>
				<td>Revision</td>
				<td><?php echo $revisionId;?></td>
			</tr>
			<tr>
				<td>PO Quantity</td>
				<td><?php echo $poQuantity;?></td>
			</tr>
			<tr>
				<td>Lot Count</td>
				<td><input type='number' name='lotCount' step='any' autofocus form='formId'></td>
			</tr>
			<tr>
				<th colspan='2'><input type='submit' name='submit' value='SUBMIT' form='formId'></th>
			</tr>
		</table>
		<?php
	}
?>
