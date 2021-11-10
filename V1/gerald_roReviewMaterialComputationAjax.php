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
	$compressedInput = "";
	
	$requirementArray = $materialSpecIdArray = array();
	$count = $queryPosition;
	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess` FROM ppic_materialcomputation ".$sqlFilter." ".$endQuery;
	$sqlMain = $sql;
	$query = $db->query($sql);
	if($query->num_rows > 0)
	{
		//~ $tableContent = "<tr><td colspan='14'>".$sqlMain."</td></tr>";
		while($result = $query->fetch_array())
		{
			$materialComputationId = $result['materialComputationId'];
			$customerAlias = $result['customerAlias'];
			$materialType = $result['materialType'];
			$thickness = $result['thickness'];
			$length = $result['length'];
			$width = $result['width'];			
			$treatment = $result['treatment'];			
			$pvc = $result['pvc'];			
			$quantity = $result['quantity'];			
			$finalQuantity = $result['finalQuantity'];			
			$status = $result['status'];			
			$idnumber = $result['idnumber'];			
			$inputDateTime = $result['inputDateTime'];			
			$lotNumber = $result['lotNumber'];			
			$blankingProcess = $result['blankingProcess'];			
			
			$pvcStatus = ($pvc==1) ? 'Yes' : 'No';
			
			$quantityInput = "<input type='number' data-material-computation-id='".$materialComputationId."' class='finalQuantityClass api-form' name='qtyPerSheet[]' value='".$finalQuantity."' style='width:50px;' step='any' form='computeFormId'>";
			
			if($lotNumber=='')
			{
				/*
				$selected0 = ($status==0) ? 'selected' : '';
				$selected1 = ($status==1) ? 'selected' : '';
				$selected2 = ($status==2) ? 'selected' : '';
				$selected3 = ($status==3) ? 'selected' : '';
				$selected4 = ($status==4) ? 'selected' : '';
				$selected5 = ($status==5) ? 'selected' : '';
				
				$statusArray = array();
				$statusArray[] = "<option value='0' ".$selected0.">Not Set</option>";
				$statusArray[] = "<option value='1' ".$selected1.">".displayText('L1345')."</option>";
				if($treatment != 'Raw')
				{
					$statusArray[] = "<option value='2' ".$selected2.">For Subcon</option>";
					
					$primeFlag = (stristr($treatment,'prime')!==FALSE) ? 1 : 0;
					
					if($primeFlag==1 AND stristr($customerAlias,'jamco')!==FALSE)
					{
						$statusArray[] = "<option value='3' ".$selected3.">For Internal Prime</option>";
					}
				}
				$statusArray[] = "<option value='4' ".$selected4.">For Customer Request</option>";
				$statusArray[] = "<option value='5' ".$selected5.">Open PO</option>";
				
				$yonko = "<select class='statusClass api-form' data-material-computation-id='".$materialComputationId."'>".implode("",$statusArray)."</select>";
				*/
				
				$selected0 = ($status==0) ? 'selected' : '';
				$selected1 = ($status==1) ? 'selected' : '';
				$selected4 = ($status==4) ? 'selected' : '';
				
				$statusArray = array();
				$statusArray[] = "<option value='0' ".$selected0."></option>";
				$statusArray[] = "<option value='1' ".$selected1.">".displayText('L1345')."</option>";
				$statusArray[] = "<option value='4' ".$selected4.">For Customer Request</option>";
				
				$yonko = "<select class='statusClass api-form' data-material-computation-id='".$materialComputationId."'>".implode("",$statusArray)."</select>";
			}
			else
			{
				if($status==0)		$yonko = 'Not Set';
				else if($status==1)	$yonko = 'For Purchase';
				else if($status==2)	$yonko = 'For Subcon';
				else if($status==3)	$yonko = 'For Internal Prime';
				else if($status==4)	$yonko = 'For Customer Request';
				else if($status==5)	$yonko = 'Open PO';
			}
			
			if($compressedInput == "")
			{
				$compressedInput = $materialType."`".$thickness."`".$length."`".$width."`".$quantity."`".$treatment."`".$pvcStatus;
			}
			else
			{
				$compressedInput = $compressedInput."`".$materialType."`".$thickness."`".$length."`".$width."`".$quantity."`".$treatment."`".$pvcStatus;
			}			
			
			$additionalTD = "";
			//~ if(strstr($sqlFilter,'status = 2')!==FALSE OR strstr($sqlFilter,'status = 3')!==FALSE)
			if(strstr($sqlFilter,'status = 2')!==FALSE OR strstr($sqlFilter,'status = 3')!==FALSE OR (strstr($sqlFilter,'status = 4')!==FALSE AND $_SESSION['idNumber']=='0346') AND $_SESSION['idNumber']=='0346')
			{
				$fixedFlag = 0;
				$materialLength = $length;
				$materialWidth = $width;
				
				//~ $finalQuantity = 11;
				
				if($status==2)
				{
					if($length==1250 AND $width==625)
					{
						//Produced 4 qty per sheet
						$materialLength = 2500;
						$materialWidth = 1250;
						$fixedFlag = 1;
					}
					else if($length==1000 AND $width==500)
					{
						//Produced 1 qty per sheet
						$materialLength = 1000;
						$materialWidth = 500;
						$fixedFlag = 1;
					}
					else if($length==1000 AND $width==1000)
					{
						//Produced 2 qty per sheet
						$materialLength = 2000;
						$materialWidth = 1000;
						$fixedFlag = 1;
					}
					else if($length==1219 AND $width==609)
					{
						//Produced 6 qty per sheet
						$materialLength = 3657;
						$materialWidth = 1219;
						$fixedFlag = 1;
					}
				}
				//~ else if($status==3)
				else if($status==3 OR ($status==4 AND $_SESSION['idNumber']=='0346'))
				{
					if($length==1219 AND $width==609)
					{
						//Produced 4 qty per sheet
						$materialLength = 2438;
						$materialWidth = 1219;
						$fixedFlag = 1;
					}
					else if($length==1000 AND $width==1000)
					{
						//Produced 2 qty per sheet
						$materialLength = 2000;
						$materialWidth = 1000;
						$fixedFlag = 1;
					}
				}
				
				$inventoryIdArray = array();
				
				$quantityPerSheet = 0;
				$bookingId = '';
				$bookingIdArray = array();
				$sql = "SELECT DISTINCT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$materialComputationId."|%'";
				$queryBookingDetails = $db->query($sql);
				if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
				{
					while($resultBookingDetails = $queryBookingDetails->fetch_assoc())
					{
						$bookingIdArray[] = $resultBookingDetails['bookingId'];
					}
					
					//~ $sql = "SELECT inventoryId, bookingQuantity FROM engineering_booking WHERE bookingId = ".$bookingId." LIMIT 1";
					$sql = "SELECT inventoryId, bookingQuantity FROM engineering_booking WHERE bookingId IN(".implode(",",$bookingIdArray).")";
					$queryBooking = $db->query($sql);
					if($queryBooking AND $queryBooking->num_rows > 0)
					{
						while($resultBooking = $queryBooking->fetch_assoc())
						{
							$inventoryIdArray[] = $resultBooking['inventoryId'];
						}
					}
				}
				else
				{
					$inventoryId = 'No Material';
					$workingQuantity = $sheetCount = 0;
					if($fixedFlag==1)
					{
						$programP = ($materialLength / $length);
						$programK = ($materialWidth / $width);
						
						$quantityPerSheet = floor($programP) * floor($programK);
						
						while($finalQuantity > $workingQuantity)
						{
							$workingQuantity += $quantityPerSheet;
							$sheetCount++;
						}
						
						$initialWorkingQuantity = $finalQuantity;
						//~ $initialWorkingQuantity = $workingQuantity;
						
						if(stristr($customerAlias,'jamco')!==FALSE)//Jamco
						{
							$filterSupplier = "AND supplierAlias IN('Jamco','KAPCO','Kapco Manufacturing Inc.')";
						}
						else if(stristr($customerAlias,'B/E')!==FALSE)//B/E
						{
							$filterSupplier = "AND supplierAlias IN('Metalweb Ltd.','KAPCO','Kapco Manufacturing Inc.','Shs Perforated Materials Inc.','B/e Aerospace','Garmco','MD AEROSPACE')";
						}
						
						$repeatFlag = 1;
						while($repeatFlag==1)
						{
							$repeatFlag = 0;
							$dataThree = $dataFour = '';
							$sql = "SELECT inventoryId, supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive, inventoryQuantity FROM warehouse_inventory WHERE type = 1 AND dataSix = 0 AND dataOne LIKE '".$materialType."' AND dataTwo = ".$thickness." AND dataThree = ".$materialLength." AND dataFour = ".$materialWidth." AND dataFive LIKE 'Raw' ".$filterSupplier." ORDER BY stockDate ASC";
							$queryInventory = $db->query($sql);
							if($queryInventory AND $queryInventory->num_rows > 0)
							{
								while($resultInventory = $queryInventory->fetch_assoc())
								{
									$inventoryId = $resultInventory['inventoryId'];
									$supplierAlias = $resultInventory['supplierAlias'];
									$dataOne = $resultInventory['dataOne'];
									$dataTwo = $resultInventory['dataTwo'];
									$dataThree = $resultInventory['dataThree'];
									$dataFour = $resultInventory['dataFour'];
									$dataFive = $resultInventory['dataFive'];
									$inventoryQuantity = $resultInventory['inventoryQuantity'];
									
									/*
									$sql = "SELECT bookingId, bookingQuantity FROM engineering_booking WHERE inventoryId LIKE '".$inventoryId."' AND bookingIncharge = 0 AND bookingStatus = 2 ";
									$queryTemporaryBooking = $db->query($sql);
									if($queryTemporaryBooking AND $queryTemporaryBooking->num_rows > 0)
									{
										while($resultTemporaryBooking = $queryTemporaryBooking->fetch_assoc())
										{
											$temporaryBookingId = $resultTemporaryBooking['bookingId'];
											$bookingQuantity = $resultTemporaryBooking['bookingQuantity'];
											
											$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE bookingId = ".$temporaryBookingId." AND lotNumber LIKE '%|%' LIMIT 1";
											$queryTemporaryBookingDetails = $db->query($sql);
											if($queryTemporaryBookingDetails AND $queryTemporaryBookingDetails->num_rows == 0)
											{
												$lotTempQuantity = 0;
												$materialComputationIdArray = array();
												$sql = "SELECT lotNumber, quantity FROM engineering_bookingdetails WHERE bookingId = ".$temporaryBookingId."";
												$queryTemporaryBookingDetails = $db->query($sql);
												if($queryTemporaryBookingDetails AND $queryTemporaryBookingDetails->num_rows == 0)
												{
													while($resultTemporaryBookingDetails = $queryTemporaryBookingDetails->fetch_assoc())
													{
														$materialComputationIdArray[] = $resultTemporaryBookingDetails['lotNumber'];
														$lotTempQuantity += $resultTemporaryBookingDetails['quantity'];
													}
												}
												
												$totalFinalQuantity = $finalQuantity;
												$sql = "SELECT SUM(finalQuantity) as totalFinalQuantity WHERE materialComputationId IN(".implode(",",$materialComputationIdArray).")";
												$queryTotalFinalQuantity = $db->query($sql);
												if($queryTotalFinalQuantity AND $queryTotalFinalQuantity->num_rows > 0)
												{
													$resultTotalFinalQuantity = $queryTotalFinalQuantity->fetch_assoc();
													$totalFinalQuantity += $resultTotalFinalQuantity['totalFinalQuantity'];
												}
												
												$tempQuantity = 0;
												while($totalFinalQuantity > $tempQuantity)
												{
													$tempQuantity += $quantityPerSheet;
													$sheetCount++;
												}
												
												$requirement = $bookingQuantity;
												$sql = "INSERT INTO	engineering_bookingdetails
																(	bookingId,					lotNumber,						quantity,				status,	materialRequirement)
														VALUES	(	".$temporaryBookingId.",	'".$materialComputationId."',	".$lotTempQuantity.", 	0,		".$requirement.")";
												//~ $insertQuery = $db->query($sql);
												break;
											}
										}
									}*/
									
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
									
									if($stock > 0 AND $stock >= $sheetCount)
									{
										$number = 1;
										$sql = "SELECT number FROM ppic_materialcomputationtemporarylot WHERE materialComputationId = ".$materialComputationId." ORDER BY number DESC LIMIT 1";
										$queryMaterialComputationTemporaryLot = $db->query($sql);
										if($queryMaterialComputationTemporaryLot AND $queryMaterialComputationTemporaryLot->num_rows > 0)
										{
											$resultMaterialComputationTemporaryLot = $queryMaterialComputationTemporaryLot->fetch_assoc();
											$number = ($resultMaterialComputationTemporaryLot['number']+1);
										}
										
										$materialComputationTemporaryLot = $materialComputationId."|".$number;
										
										$remainingQuantityQuantity = $initialWorkingQuantity;
										$workingQuantity = 0;
										$shitCount = $sheetCount;
										while($shitCount > 0)
										{
											$workingQty = ($remainingQuantityQuantity >= $quantityPerSheet) ? $quantityPerSheet : $remainingQuantityQuantity;
											$remainingQuantityQuantity -= $workingQty;
											$workingQuantity += $workingQty;
											
											$shitCount--;
										}
										
										$sql = "INSERT INTO	`ppic_materialcomputationtemporarylot`
														(	`materialComputationId`,		`number`,		`quantity`)
												VALUES	(	'".$materialComputationId."',	'".$number."',	'".$workingQuantity."')";
										//~ $queryInsert = $db->query($sql);
										
										$sql = "INSERT INTO engineering_booking
														(	inventoryId,		bookingQuantity,	bookingDate,	bookingTime,	bookingStatus,	nestingType)
												VALUES	(	'".$inventoryId."',	".$sheetCount.",	now(),			now(),			2,				2)";
										//~ $insertQuery = $db->query($sql);
										if($insertQuery)
										{
											$bookingId = $db->insert_id;
											
											$sql = "INSERT INTO	engineering_bookingdetails
															(	bookingId,		lotNumber,								quantity,				status,	materialRequirement)
													VALUES	(	".$bookingId.", '".$materialComputationTemporaryLot."',	".$workingQuantity.", 	0,		".$sheetCount.")";
											//~ $insertQuery = $db->query($sql);
										}
										
										$inventoryIdArray[] = $inventoryId;
										
										$initialWorkingQuantity -= $workingQuantity;
										
										if($initialWorkingQuantity > 0)
										{
											$repeatFlag = 1;
										}
										
										break;
									}
									else
									{
										$inventoryId = 'No Material';
									}
								}
								
								if($inventoryId=='No Material')
								{
									if($sheetCount > 0)
									{
										$sheetCount--;
										$workingQuantity -= $quantityPerSheet;
										$repeatFlag = 1;
									}
									else
									{
										$sheetCount = '';
										$dataThree = '';
										$dataFour = '';
										$sheetCount = '';
										$quantityPerSheet = 0;
										$bookingId = '';
									}
								}
							}
							else
							{
								$quantityPerSheet = 0;
							}
						}
					}
				}
				
				//~ $inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" openTinyBox('','','gerald_subconMaterialBooking.php','materialComputationId=".$materialComputationId."'); \">".$inventoryId."</span>";
				
				$removeBookingButton = ($bookingId!='') ? "<img style='cursor:pointer;' data-booking-id='".$bookingId."' data-material-computation-id='".$materialComputationId."' class='unBookClass' src='/".v."/Common Data/Templates/images/close1.png' height='10' title='Unbook'>" : "";
				
				$inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" apiOpenModalBox({url:'gerald_subconMaterialBooking.php',post:'materialComputationId=".$materialComputationId."&inventoryId=".$inventoryId."',customFunction:function(){jsFunctions();}}); \">".$inventoryId."</span>".$removeBookingButton;
				
				$laserSelected = ($blankingProcess==381) ? 'selected' : '';
				$tppSelected = ($blankingProcess==86) ? 'selected' : '';
				
				//~ $blankingProcessSelect = "N/A";
				//~ if($quantityPerSheet > 1)
				//~ {
					$blankingProcessSelect = "
						<select data-material-computation-id='".$materialComputationId."' class='blankingProcessClass api-form' name='blankingProcess[]'>
							<option value='381' ".$laserSelected.">Laser</option>
							<option value='86' ".$tppSelected.">TPP</option>
						</select>
					";
				//~ }
				//~ else
				//~ {
					//~ $blankingProcessSelect = "
						//~ <select data-material-computation-id='".$materialComputationId."' class='blankingProcessClass api-form' name='blankingProcess[]' disabled>
							//~ <option value=''>.displayText('L161').</option>
						//~ </select>
					//~ ";					
				//~ }
				
				$inventoryIdSpan = implode("<br>",$inventoryIdArray);
				
				//~ $additionalTD = "
					//~ <td class='".$materialComputationId."' data-one='one'>".$inventoryIdSpan."</td>
					//~ <td class='".$materialComputationId."' data-two='two'>".$dataThree."</td>
					//~ <td class='".$materialComputationId."' data-three='three'>".$dataFour."</td>
					//~ <td class='".$materialComputationId."' data-four='four'>".$sheetCount." </td>
					//~ <td>".$blankingProcessSelect."</td>
				//~ ";
				$additionalTD = "
					<td class='".$materialComputationId."' data-one='one'>".$inventoryIdSpan."</td>
					<td>".$blankingProcessSelect."</td>
				";
			}
			
			$tableContent .= "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$customerAlias."</td>
					<td>".$materialType."</td>
					<td>".$thickness."</td>
					<td class='length'>".$length."</td>
					<td class='width'>".$width."</td>
					<td>".$treatment."</td>
					<td>".$pvcStatus."</td>
					<td>".$quantity."</td>
					<td>".$quantityInput."</td>
					<td>".$yonko."</td>
					".$additionalTD."
				</tr>
			";
		}
		if($submitType!='')
		{
			//~ echo "<form action = '/".v."/3-4 Material Requirements Computation Software/anthony_computationSummaryConverter.php?pdf=1' method = 'POST' id = 'print' ></form>";
			//~ echo "<form action = '/".v."/54 Automated Material Computation Software/anthony_computationSummaryConverter.php?excel=1' method = 'POST' id = 'print' ></form>";
			echo "<form action = '/".v."/54 Automated Material Computation Software/anthony_computationSummaryConverter.php?pdf=2' method = 'POST' id = 'print' ></form>";
			echo "<input type = 'hidden' name = 'specId' value = '".$compressedInput."' form='print'>";
			echo "<input type = 'image' id='printId' src = '/".v."/Common Data/Templates/buttons/printIcon.png' height = '35' width = '50' form='print'>";
			?><script>document.getElementById('print').submit();</script><?php
		}
		else
		{
			echo $tableContent;
		}
	}
					
?>
