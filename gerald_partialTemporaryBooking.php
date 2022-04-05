<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	//include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");

//include('ace_materialTemporaryBookingForUnbookedItem.php');
//------------------------------------------------------------------ Partial Booking Function ---------------------------------------------------------------------//
	function partialBooking($lot,$quantity,$employeeId)
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
		$sql="SELECT poId, partId, parentLot, partLevel, workingQuantity, identifier, status, poContentId, patternId FROM ppic_lotlist where lotNumber like '".$lot."' AND workingQuantity > ".$quantity." LIMIT 1";	
		$lotQuery=$db->query($sql);
		if($lotQuery->num_rows > 0)
		{
			$lotQueryResult = $lotQuery->fetch_assoc();
			
			$newQuantity = $lotQueryResult['workingQuantity']-$quantity;
			$sql = "insert into ppic_lotlist (lotNumber, poId , partId, parentLot, partLevel, workingQuantity, identifier, dateGenerated, status, bookingStatus, poContentId, patternId) values ('".$newLotNumber."', ".$lotQueryResult['poId'].", ".$lotQueryResult['partId'].", '".$lotQueryResult['parentLot']."', ".$lotQueryResult['partLevel'].", ".$newQuantity.", ".$lotQueryResult['identifier'].", now(), ".$lotQueryResult['status'].", 1, '".$lotQueryResult['poContentId']."', '".$lotQueryResult['patternId']."')";
			
			$query = $db->query($sql);
			// ---------------------------------------------------------------------------------------------------------------------------------
			
			// --------------------------------------- Update Working Quantity of Source Lot ---------------------------------------------------
			$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$quantity." WHERE lotNumber like '".$lot."'";	
			
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
			$sql="INSERT INTO ppic_prslog (lotNumber,employeeId,date,remarks,type,sourceLotNumber,partialQuantity) values ('".$newLotNumber."', '".$employeeId."', now(), 'Automated Partial', 3,'".$lot."', '".$newQuantity."')";	
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
				// $insertQuery = $db->query($insert);
			}
		}
		
		return $newLotNumber;
	}
//--------------------------------------------------------- END Partial Booking Function ---------------------------------------------------------//	

function materialTemporaryBooking($inputData,$lotNumber='')
{
	// --------------- Need To Include PVC ---------------
	// --------------- Initialization --------------------
	include('PHP Modules/mysqliConnection.php');
	include_once('PHP Modules/gerald_functions.php');
	include_once('PHP Modules/anthony_retrieveText.php');

	// -------------- Parameter -------------------
	$poId = $inputData;
	$xCounter = 0;
	
	$deliveryDate = '0000-00-00';
	if($poId!='')
	{
		// ------------------------------------- Retrieve Delivery Date ---------------------------------------------------
		$sql = "SELECT deliveryDate FROM system_lotlist WHERE poId = ".$poId;
		$deliveryDateQuery = $db->query($sql);
		$deliveryDateQueryResult = $deliveryDateQuery->fetch_assoc();
		$deliveryDate = $deliveryDateQueryResult['deliveryDate'];
	}
	
	// ------------------------------------- Retrieve Metal Sheet Parts ------------------------------------------------
	$sql = "SELECT lotNumber, poId, partId, workingQuantity, partLevel, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND workingQuantity > 0 AND lotNumber NOT IN (SELECT lotNumber FROM engineering_bookingdetails)";
	
	// ----------------------- Execute If Input Parameter Is Lot Number -----------------------------------
	if($lotNumber!='')
	{
		$sql = "SELECT lotNumber, poId, partId, workingQuantity, partLevel, patternId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' AND identifier = 1 AND workingQuantity > 0 AND lotNumber NOT IN (SELECT lotNumber FROM engineering_bookingdetails)";
		if(is_array($lotNumber) AND count($lotNumber) > 0)
		{
			$sql = "SELECT lotNumber, poId, partId, workingQuantity, partLevel, patternId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumber)."') AND identifier = 1 AND workingQuantity > 0 AND lotNumber NOT IN (SELECT lotNumber FROM engineering_bookingdetails)";
		}
	}
	$formIdCount = 0;
	//return $sql;
	$lotListQuery = $db->query($sql);
	if($lotListQuery->num_rows > 0)
	{
		echo "
			<table border='1'>
				<tr>
					<th>".displayText('L45')."</th>
					<th>".displayText('L1219')."</th>
					<th>Inventory Id</th>
					<th>".displayText('L2093')."</th>
					<th>".displayText('L2007')."</th>
					<th>Sufficient Qty</th>
					<th></th>
				</tr>
		";
		while($lotListQueryResult = $lotListQuery->fetch_assoc())
		{
			$lotNumber = $lotListQueryResult['lotNumber'];			
			$poId = $lotListQueryResult['poId'];
			$partId = $lotListQueryResult['partId'];
			$workingQuantity = $lotListQueryResult['workingQuantity'];
			$partLevel = $lotListQueryResult['partLevel'];
			$patternId = $lotListQueryResult['patternId'];
			
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
			
			// ---------------------------------- Check If Parts Has Cutting, Blanking Process ------------------------------------
			if($processCode!='')
			{
				// ----------------------- Set Material Computation Parameters ---------------------------------------
				$itemGap = 0;
				
				if($_GET['country']=="1")
				{
					if($processCode==381)
					{
						$blankingProcess = 'Laser';
						$itemGap = 5;
						$nestingType = 2;
					}
					else if($processCode==86)
					{
						$blankingProcess = 'TPP';
						$itemGap = 17;
						$nestingType = 1;
					}
					else if($processCode==52)
					{
						$blankingProcess = 'Press';
						$nestingType = 3;
					}
					else if(in_array($processCode,array(328,98,392)))
					{
						$blankingProcess = 'Cutting';
						$nestingType = 4;
					}
					else
					{
						$nestingType = 1;
					}
					
					if(in_array($partId,array(15061,15162)))
					{
						$itemGap = 0;
					}
				}
				else
				{				
					if($processCode==314)
					{
						$blankingProcess = 'Laser';
						$itemGap = 5;
						$nestingType = 2;
					}
					else if(in_array($processCode,array(381,382,401,403,527)))
					{
						$blankingProcess = 'TPP';
						$itemGap = 17;
						$nestingType = 1;
					}
					else if($processCode==52) // -------- Still Philippine Process --------------
					{
						$blankingProcess = 'Press';
						$nestingType = 3;
					}
					else if(in_array($processCode,array(372))) 
					{
						$blankingProcess = 'Cutting';
						$nestingType = 4;
					}
					else
					{
						$nestingType = 1;
					}
				}
				
				// ---------------------- End Of Set Material Computation Parameters --------------------------------------------

				// ------------------------------ Retrieve Parts Information And Material Specification --------------------------------------
				$sql = "SELECT customerId, materialSpecId, x, y, treatmentId, PVC FROM cadcam_parts WHERE partId = ".$partId;
				$partsQuery = $db->query($sql);
				if($partsQuery->num_rows > 0)
				{
					$partsQueryResult = $partsQuery->fetch_assoc();
					$customerId = $partsQueryResult['customerId'];
					$materialSpecId = $partsQueryResult['materialSpecId'];
					$x = $partsQueryResult['x'];
					$y = $partsQueryResult['y'];
					$treatmentId = $partsQueryResult['treatmentId'];
					$pvcFlag = $partsQueryResult['PVC'];
					
					// ---------------------- Break Loop If Item X or Item Y Is 0 --------------------------------
					if($x == 0 OR $y== 0)
					{
						if($partLevel==1)
						{
							$sql = "SELECT partLevel FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) ORDER BY partLevel DESC LIMIT 1";
							$queryPartLevel = $db->query($sql);
							if($queryPartLevel AND $queryPartLevel->num_rows > 0)
							{
								$resultPartLevel = $queryPartLevel->fetch_assoc();
								if($resultPartLevel['partLevel'] > 1) continue;
							}
						}
						else
						{
							$xCounter++;
							break;
						}
					}					
				}

				$materialTypeArray = array();
				$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId;
				$materialSpecificationQuery = $db->query($sql);
				if($materialSpecificationQuery->num_rows > 0)
				{
					$materialSpecificationQueryResult = $materialSpecificationQuery->fetch_assoc();
					$materialTypeId = $materialSpecificationQueryResult['materialTypeId'];
					$materialThickness = $materialSpecificationQueryResult['metalThickness'];
					
					$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId;
					$materialTypeQuery = $db->query($sql);
					if($materialTypeQuery->num_rows > 0)
					{
						$materialTypeQueryResult = $materialTypeQuery->fetch_assoc();
						$materialTypeArray[] = "'".$materialTypeQueryResult['materialType']."'";
					}
				}
				
				// ------------------------------ Retrieve Alternate Material ----------------------------------------------------
				$sql = "SELECT materialSpecId FROM engineering_alternatematerial WHERE partId = ".$partId;
				$alternateMaterialQuery = $db->query($sql);
				if($alternateMaterialQuery->num_rows > 0)
				{
					while($alternateMaterialQueryResult = $alternateMaterialQuery->fetch_assoc())
					{
						//$materialTypeId = $alternateMaterialQueryResult['materialSpecId'];
						$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$alternateMaterialQueryResult['materialSpecId'];		
						$materialSpecificationQuery = $db->query($sql);
						if($materialSpecificationQuery->num_rows > 0)
						{
							$materialSpecificationQueryResult = $materialSpecificationQuery->fetch_assoc();
							$materialTypeId = $materialSpecificationQueryResult['materialTypeId'];
						}
						
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId;
						$materialTypeQuery = $db->query($sql);
						if($materialTypeQuery->num_rows > 0)
						{
							$materialTypeQueryResult = $materialTypeQuery->fetch_assoc();
							$materialTypeArray[] = "'".$materialTypeQueryResult['materialType']."'";
						}
					}
				}
				// ------------------------------ End Of Retrieve Alternate Material ----------------------------------------------------
				
				// ------------------------------ End Of Retrieve Parts Information And Material Specification --------------------------------------
				
				// ------------------------------ Set The Rectangular Outline -----------------------------------------------------------
				$itemX = $x+$itemGap;
				$itemY = $y+$itemGap;
				// ------------------------------ End Of Set The Rectangular Outline ----------------------------------------------------
				
				// ------------------------------ Retrieve Material Treatment Data --------------------------------------------
				$treatmentName = 'Raw';
				$sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId = ".$treatmentId." LIMIT 1";
				$treatmentQuery = $db->query($sql);
				if($treatmentQuery->num_rows > 0)
				{
					$treatmentQueryResult = $treatmentQuery->fetch_assoc();
					$treatmentName = $treatmentQueryResult['treatmentName'];
				}
				// ------------------------------ End Of Retrieve Material Treatment Data -------------------------------------
				
				// ------------------------------ Prepare Filter Statements ---------------------------------------------------
					// ------------------------------- Supplier Filter --------------------------------------------------------
					if($customerId == '45')//Jamco
					{
						$supplierFilter = "AND supplierAlias IN('Jamco','KAPCO','Kapco Manufacturing Inc.')";
					}
					else if($customerId == '28' OR $customerId == '37')//BE
					{
						$supplierFilter = "AND supplierAlias IN('Metalweb Ltd.','KAPCO','Kapco Manufacturing Inc.','Shs Perforated Materials Inc.','B/e Aerospace','Garmco','MD AEROSPACE','WUXI HENG TAI AEROSPACE SCIENCE AND TECHNOLOGY CO.','B/E Phils.')";
					}
					else if($customerId == '16' AND $metalThickness >= 9)//SHIMS
					{
						$supplierFilter = "AND supplierAlias IN('Arktech Japan','Mm Steel')";
					}
					else
					{
						$supplierFilter = "AND supplierAlias != 'Jamco'";
					}
					// ---------------------------- End Of Supplier Filter -------------------------------------------------------
					
					// ---------------------------- PVC Flag Filter -------------------------------------------
					if($customerId==28 OR $customerId==37 OR $customerId==45)
					{
						$pvcFilter = "";
					}
					else
					{
						$pvcFilter = "AND pvcStatus = ".$pvcFlag;
					}
					// --------------------------- End Of PVC Flag Filter -------------------------------------
					
					// --------------------------- Material Type Filter ----------------------------------------------------------
					$materialTypeFilter = "dataOne IN(".implode(",",$materialTypeArray).")";
					// --------------------------- End Of Material Type Filter ---------------------------------------------------
					
				// ------------------------------ End Of Prepare Filter Statements ---------------------------------------------
							
				// -------------------------------------------- Retrieve All Material That Matches The Material Type, Thickness And Can Fit The Item -----------------------------
				$stockAlarmFlag = 1;
				$sql = "SELECT inventoryId, supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive, inventoryQuantity FROM warehouse_inventory WHERE type = 1 AND dataSix!=2 AND holdFlag != 1 AND ".$materialTypeFilter." ".$pvcFilter." AND dataTwo = ".$materialThickness." AND dataThree >= ".$itemX." AND dataFour >= ".$itemY." AND dataFive LIKE '".$treatmentName."' ".$supplierFilter." ORDER BY (dataThree * dataFour) ASC, dataSix ASC, stockDate ASC";
				//echo $sql."<br>";
				$inventoryQuery = $db->query($sql);
				if($inventoryQuery->num_rows > 0)
				{
					$mainRow1 = $subRows = '';
					$rowSpanCount = 0;
					//~ $inventoryTable = "<table border='1'>";
					while($inventoryQueryResult = $inventoryQuery->fetch_assoc())
					{
						$inventoryId = $inventoryQueryResult['inventoryId'];						
						$supplierAlias = $inventoryQueryResult['supplierAlias'];
						$dataOne = $inventoryQueryResult['dataOne'];
						$dataTwo = $inventoryQueryResult['dataTwo'];
						$dataThree = $inventoryQueryResult['dataThree'];
						$dataFour = $inventoryQueryResult['dataFour'];
						$dataFive = $inventoryQueryResult['dataFive'];
						$inventoryQuantity = $inventoryQueryResult['inventoryQuantity'];
												
						// ------------------------- Compute Material Requirements --------------------------------------
						$qtyPerSheet = computeQtyPerSheet($x,$y,$dataThree,$dataFour,$blankingProcess,$customerId,$partId);
						$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0;
						// ------------------------- End Of Compute Material Requirements --------------------------------------
												
						//CHICHA
						if($requirement > 0)
						{
							// -------------------------- Count Booked Materials ----------------------------------
							$totalBookingQty = 0;
							$sql = "SELECT IFNULL(SUM(bookingQuantity),0) as totalBookingQty FROM engineering_booking WHERE inventoryId LIKE '".$inventoryId."' AND bookingStatus IN (0,2)";
							//echo $sql;
							$queryBooking = $db->query($sql);
							if($queryBooking->num_rows > 0)
							{
								$resultBooking = $queryBooking->fetch_assoc();
								$totalBookingQty = $resultBooking['totalBookingQty'];
								if($totalBookingQty==NULL) $totalBookingQty = 0;
							}
							// ------------------------- End Of Count Booked Materials -------------------------------
							
							// ------------------------- Count Withdrawn Materials -----------------------------------
							$totalWithdrawalQty = 0;
							$sql = "SELECT IFNULL(SUM(withdrawMaterialQuantity),0) as totalWithdrawalQty FROM warehouse_materialwithdrawal WHERE withdrawMaterialId LIKE '".$inventoryId."'";
							$queryMaterialWithdrawal = $db->query($sql);
							if($queryMaterialWithdrawal->num_rows > 0)
							{
								$resultMaterialWithdrawal = $queryMaterialWithdrawal->fetch_assoc();
								$totalWithdrawalQty = $resultMaterialWithdrawal['totalWithdrawalQty'];
								if($totalWithdrawalQty==NULL) $totalWithdrawalQty = 0;
							}
							// ------------------------- End Of Count Withdrawn Materials -----------------------------------
							
							// ------------------------ Compute Useable Stocks -----------------------------------------------
							$stock = $inventoryQuantity - ($totalBookingQty + $totalWithdrawalQty);
							// ----------------------- End Of Compute Useable Stocks ----------------------------------------
							
							//~ $bookingTable = "<table border='1'>";
							
							// ------------------------- Check Temporary Booking ------------------------------------------------------
							$loopStop = 0;
							$totalBookingQty = 0;
							$sql = "SELECT bookingId, bookingQuantity FROM engineering_booking WHERE inventoryId LIKE '".$inventoryId."' AND bookingStatus = 2 AND bookingIncharge = 0 AND nestingType = ".$nestingType;
							// ---------------------------------------- Commented Due To Issue In Available Stocks -----------------------------------------------------
							//$sql = "SELECT bookingId, bookingQuantity FROM engineering_booking WHERE inventoryId LIKE '".$inventoryId."' AND bookingStatus = 2 AND bookingIncharge = 0 AND nestingType = ".$nestingType." AND cuttingDate = '".$deliveryDate."'";
						
							$temporaryBookingQuery = $db->query($sql);
							if($temporaryBookingQuery->num_rows > 0)
							{
								while($temporaryBookingQueryResult = $temporaryBookingQuery->fetch_assoc())
								{
									$temporaryBookingId = $temporaryBookingQueryResult['bookingId'];
									$bookingQuantity = $temporaryBookingQueryResult['bookingQuantity'];
									
									// --------------------------------- Check TPP Machine (Japan) ------------------------------------------
									if($_GET['country']=="2")
									{
										$sql = "SELECT lotNumber FROM engineering_bookingdetails WHERE bookingId = ".$temporaryBookingId." LIMI 1";
										$temporaryBookingLotNumberQuery = $db->query($sql);
										if($temporaryBookingLotNumberQuery->num_rows > 0)
										{
											$temporaryBookingLotNumberQueryResult = $temporaryBookingLotNumberQuery->fetch_assoc();
											$temporaryBookingLotNumber = $temporaryBookingLotNumberQueryResult['lotNumber'];
											// ----------------------------- Retrieve Part Id -----------------------------
											$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber LIKE '".$temporaryBookingLotNumber."'";
											$temporaryBookingPartIdQuery = $db->query($sql);
											if($temporaryBookingPartIdQuery->num_rows > 0)
											{
												$temporaryBookingPartIdQueryResult = $temporaryBookingPartIdQuery->fetch_assoc();
												$temporaryBookingPartId = $temporaryBookingPartIdQueryResult['partId'];
											
												// ---------------------------------------------------- Retrieve Blanking Process -----------------------------------------------
												$sql = "SELECT processCode FROM cadcam_partprocess WHERE processCode IN (527, 403, 382, 381, 380, 378, 314, 401) AND partId = ".$temporaryBookingPartId;
												$temporaryBookingPartProcessQuery = $db->query($sql);
												if($temporaryBookingPartProcessQuery->num_rows > 0)
												{
													$temporaryBookingPartProcessQueryResult = $temporaryBookingPartProcessQuery->fetch_assoc();
													$temporaryBookingProcessCode = $temporaryBookingPartProcessQueryResult['processCode'];
													// --------------------------- Execute When Blanking Process Is EMZ But Temporary Booking Is KF -------------------------- 
													if(in_array($processCode,array(382,401,403)) AND in_array($temporaryBookingProcessCode,array(381,527)))
													{
														continue;
													}
													// --------------------------- Execute When Blanking Process Is KF But Temporary Booking Is EMZ ---------------------------
													if(in_array($processCode,array(381,527)) AND in_array($temporaryBookingProcessCode,array(382,401,403)))
													{
														continue;
													}
												}
											}
										}
									}
									// ----------------------------- Check TPP Machine (Japan) ------------------------------------------
									
									// ----------------------------- Combine With Existing Booking ------------------------
									$sql = "SELECT SUM(materialRequirement) as temporaryBookingRequirement FROM engineering_bookingdetails WHERE bookingId = ".$temporaryBookingId;
									$temporaryBookingDetailsQuery = $db->query($sql);
									if($temporaryBookingDetailsQuery->num_rows > 0)
									{
										$temporaryBookingDetailsQueryResult = $temporaryBookingDetailsQuery->fetch_assoc();
										$temporaryBookingRequirement = $temporaryBookingDetailsQueryResult['temporaryBookingRequirement'];
										if($temporaryBookingRequirement > 0)
										{
											// ----------------------------- Combine With Existing Booking (No Additional Sheet) ------------------------
											$availableMaterial = ($bookingQuantity-$temporaryBookingRequirement);
											if($availableMaterial >= $requirement AND $requirement > 0)
											{
												$stockAlarmFlag = 0;
												$loopStop = 1;
												
												$sql = "INSERT INTO engineering_bookingdetails (bookingId, lotNumber, quantity, status, `materialRequirement`) VALUES (".$temporaryBookingId.", '".$lotNumber."', ".$workingQuantity.", 0, ".$requirement.")";
												//echo $sql."<br>";
												$insertQuery = $db->query($sql);
												
												break 2;
											}
											// ----------------------------- End Of Combine With Existing Booking (No Additional Sheet) ------------------------
											// ----------------------------- Combine With Existing Booking (Additional Sheet) ----------------------------------
											else
											{
												$balanceRequirement = ($requirement - $availableMaterial);
												
												// ------------------------ Execute When Requirement Will Fit On Remaining Stock -------------------------										
												if(($stock >= $balanceRequirement) AND $balanceRequirement > 0)
												{
													$stockAlarmFlag = 0;
													$loopStop = 1;
													
													$newBookingQuantity = $bookingQuantity + $balanceRequirement;
													
													$sql = "INSERT INTO engineering_bookingdetails (bookingId, lotNumber, quantity, status, `materialRequirement`) VALUES (".$temporaryBookingId.", '".$lotNumber."', ".$workingQuantity.", 0, ".$requirement.")";
													//echo $sql."<br>";
													$insertQuery = $db->query($sql);
													
													$sql = "UPDATE engineering_booking SET bookingQuantity = ".ceil($newBookingQuantity)." WHERE bookingId = ".$temporaryBookingId;
													//echo $sql."<br>";
													$updateQuery = $db->query($sql);
													
													break 2;
												}
												// ------------------------- End Of Execute When Requirement Will Fit On Remaining Stock -------------------------
											}
											// ----------------------------- End Of Combine With Existing Booking (Additional Sheet) ----------------------------------
										}
									}
									// ----------------------------- End Of Combine With Existing Booking ------------------------
								}
							}
							// ------------------------- End Of Check Temporary Booking -----------------------------------------------
							
							//~ $bookingTable .= "</table>";
							
							if($loopStop == 0)
							{
							// ------------------------- Check Unused Materials ---------------------------------------------------------
								// --------------------- Execute When Material Has Stock ---------------------------
								if(($stock >= $requirement) AND $requirement > 0)
								{									
									$sql = "INSERT INTO engineering_booking (inventoryId, bookingQuantity, bookingDate,	bookingTime, bookingStatus, nestingType, temporaryBookingFlag, cuttingDate) VALUES ('".$inventoryId."', '".ceil($requirement)."',	now(), now(), 2, '".$nestingType."', 1, '".$deliveryDate."')";
									//echo $sql."<br>";
									$insertQuery = $db->query($sql);
									
									$bookingId = $db->insert_id;
									
									$sql = "INSERT INTO engineering_bookingdetails (bookingId, lotNumber, quantity, status, `materialRequirement`) VALUES (".$bookingId.", '".$lotNumber."', ".$workingQuantity.", 0, ".number_format($requirement,2).")";
									//echo $sql."<br>";
									$insertQuery = $db->query($sql);
									
									$stockAlarmFlag = 0;
									break;
								}
								// ------------------- End Of Execute When Material Has Stock -----------------------
								else
								{
									
								}
							// ------------------------- End Of Check Unused Materials ---------------------------------------------------------
							}
							
							if($stock > 0)
							{
								$stockAlarmFlag = 2;//With Stock but insufficient
							}
						}
						
						if($stockAlarmFlag = 2)
						{
							$newWorkingQuantity = $workingQuantity;
							$requirementTemp = $requirement;
							while($stock < $requirementTemp)
							{
								$newWorkingQuantity--;
								$requirementTemp = ($qtyPerSheet > 0) ? $newWorkingQuantity / $qtyPerSheet : 0;
							}
							
							$formIdCount++;
							//~ $inventoryTable .= "
								//~ <form action='' method='post' id='formId".$formIdCount."'></form>
								//~ <input type='hidden' name='lotNumber' value='".$lotNumber."' form='formId".$formIdCount."'>
								//~ <input type='hidden' name='inventoryId' value='".$inventoryId."' form='formId".$formIdCount."'>
								//~ <input type='hidden' name='newWorkingQuantity' value='".$newWorkingQuantity."' form='formId".$formIdCount."'>
								//~ <tr>
									//~ <td>".$inventoryId."</td>
									//~ <td>".$stock."</td>
									//~ <td>".$requirement."</td>
									//~ <td>".$newWorkingQuantity."</td>
									//~ <td><input type='submit' name='submitPartial' value='Partial' form='formId".$formIdCount."'></td>
								//~ </tr>
							//~ ";
							
							$partialButton = ($newWorkingQuantity > 0) ? "<input type='submit' name='submitPartial' value='Partial' form='formId".$formIdCount."'>" : "";
							
							$dataCell = "
								<form action='' method='post' id='formId".$formIdCount."'></form>
								<input type='hidden' name='lotNumber' value='".$lotNumber."' form='formId".$formIdCount."'>
								<input type='hidden' name='inventoryId' value='".$inventoryId."' form='formId".$formIdCount."'>
								<input type='hidden' name='newWorkingQuantity' value='".$newWorkingQuantity."' form='formId".$formIdCount."'>
								<td>".$inventoryId."</td>
								<td>".$stock."</td>
								<td>".$requirement."</td>
								<td>".$newWorkingQuantity."</td>
								<td>".$partialButton."</td>
							";
							
							if($rowSpanCount==0)
							{
								$mainRow1 .= $dataCell;
								//~ $subRows .= $dataCell;
							}
							else
							{
								$subRows .= "<tr>".$dataCell."</tr>";
							}
							
							$rowSpanCount++;
						}
					}
					
					//~ echo $subRows;
					
					//~ $inventoryTable .= "</table>";
					if($stockAlarmFlag == 1 OR $stockAlarmFlag == 2)
					{
						if($stockAlarmFlag = 2)
						{
							//~ echo "
								//~ <tr>
									//~ <td>".$lotNumber."</td>
									//~ <td>".$workingQuantity."</td>
									//~ <td>".$inventoryTable."</td>
								//~ </tr>
							//~ ";
							
							$rowSpan = "rowspan='".$rowSpanCount."'";
							if($mainRow1=='')
							{
								$mainRow1 = "
									<td ".$rowSpan.">".$lotNumber."</td>
									<td ".$rowSpan.">".$workingQuantity."</td>
								";
							}
							
							$mainRow = "
								<tr>
									<td ".$rowSpan.">".$lotNumber."</td>
									<td ".$rowSpan.">".$workingQuantity."</td>
									".$mainRow1."
								</tr>
							";
							
							echo $mainRow.$subRows;
						}
						
						$xCounter++;
					}
				}
				// -------------------------------------------- End Of Retrieve All Material That Matches The Material Type, Thickness And Can Fit The Item -----------------------------
				else
				{
					$xCounter++;
				}				
			}
			// ---------------------------------- End Of Check If Parts Has Cutting, Blanking Process ------------------------------------
		}
		
		echo "</table>";
	}
	// ----------------------- End Of Retrieve Metal Sheet Parts ---------------------------------------
	
	// ----------------------- Return Value ------------------------------------------------------------
	if($xCounter > 0)
	{
		if($stockAlarmFlag==2)
		{
			$returnValue = "!";
		}
		else
		{
			$returnValue = "X";
		}
	}
	else
	{
		$returnValue = "O";
	}	
	
	return $returnValue;
}

if(isset($_POST['submitPartial']))
{
	//~ if($_SESSION['idNumber']=='0346') partialBooking($_POST['lotNumber'],$_POST['newWorkingQuantity'],$_SESSION['idNumber']);
	partialBooking($_POST['lotNumber'],$_POST['newWorkingQuantity'],$_SESSION['idNumber']);
	header('location:'.$_SERVER['PHP_SELF']);
	exit(0);
}

$poId = $_GET['poId'];;
materialTemporaryBooking($poId);
?>
