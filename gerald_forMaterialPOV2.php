<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	function insertForPurchase($lotNumber,$itemQuantity,$productId)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$workSchedId = '';
		$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 597 AND status = 0 LIMIT 1";
		$queryWorkSched = $db->query($sql);
		if($queryWorkSched AND $queryWorkSched->num_rows > 0)
		{
			$resultWorkSched = $queryWorkSched->fetch_assoc();
			$workSchedId = $resultWorkSched['id'];
		}		
		
		$supplierId = $supplierType = $itemName = $itemDescription = $itemUnit = $itemContentQuantity = $itemContentUnit = '';
		$sql = "SELECT supplierId, supplierType, productName, productDescription, productUnit, productContentQuantity, productContentUnit FROM purchasing_supplierproducts WHERE productId = ".$productId." LIMIT 1";
		$querySupplierProducts = $db->query($sql);
		if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
		{
			$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
			$supplierId = $resultSupplierProducts['supplierId'];
			$supplierType = $resultSupplierProducts['supplierType'];
			$itemName = $resultSupplierProducts['productName'];
			$itemDescription = $resultSupplierProducts['productDescription'];
			$itemUnit = $resultSupplierProducts['productUnit'];
			$itemContentQuantity = $resultSupplierProducts['productContentQuantity'];
			$itemContentUnit = $resultSupplierProducts['productContentUnit'];
		}
		
		$poCurrency = '';
		$itemPrice = 0;
		$sql = "SELECT priceLowerRange, priceUpperRange, currency, price FROM purchasing_price WHERE productId = ".$productId."";
		$queryPrice = $db->query($sql);
		if($queryPrice AND $queryPrice->num_rows > 0)
		{
			while($resultPrice = $queryPrice->fetch_assoc())
			{
				$priceLowerRange = $resultPrice['priceLowerRange'];
				$priceUpperRange = $resultPrice['priceUpperRange'];
				$poCurrency = $resultPrice['currency'];
				$price = $resultPrice['price'];
				
				if($priceLowerRange != 0 AND $priceUpperRange != 0)
				{
					if($itemQuantity >= $priceLowerRange AND $itemQuantity <= $priceUpperRange)	$breakFlag = 1;
				}
				else
				{
					$breakFlag = 1;
				}
				
				if($breakFlag==1)
				{
					$itemPrice = $price;
					break;
				}
			}
		}
		
		//~ $dateNeeded = '';
		//~ $sql = "SELECT dateNeeded FROM purchasing_prcontent WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
		//~ $queryPrContent2 = $db->query($sql);
		//~ if($queryPrContent2 AND $queryPrContent2->num_rows > 0)
		//~ {
			//~ $resultPrContent2 = $queryPrContent2->fetch_assoc();
			//~ $dateNeeded = $resultPrContent2['dateNeeded'];
		//~ }			
		
		$itemFlag = '';
		
		$sql = "SELECT identifier, status FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			$resultLotList = $queryLotList->fetch_assoc();
			$identifier = $resultLotList['identifier'];
			$status = $resultLotList['status'];
			
			if($identifier==4)
			{
				if($status==1)
				{
					$sql = "SELECT pvc FROM system_confirmedmaterialpo WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryConfirmedMaterialPo = $db->query($sql);
					if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
					{
						$resultConfirmedMaterialPo = $queryConfirmedMaterialPo->fetch_assoc();
						$itemFlag = $resultConfirmedMaterialPo['pvc'];
					}
					
					$sql = "SELECT dateNeeded FROM ppic_materialcomputation WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryMaterialComputation = $db->query($sql);
					if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
					{
						$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
						$dateNeeded = $resultMaterialComputation['dateNeeded'];
					}
				}
			}
		}
		
		$sql = "
			SELECT a.id FROM ppic_workschedule as a
			INNER JOIN purchasing_forpurchaseorder as b ON b.lotNumber = a.lotNumber AND b.processRemarks = a.processRemarks
			WHERE id = ".$workSchedId." LIMIT 1
		";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
		{
			$sql = "
				INSERT INTO purchasing_forpurchaseorder
					(	`lotNumber`, `processRemarks`, `productId`,		`supplierId`,		`supplierType`,		`poCurrency`,		`dateNeeded`,		`itemName`, 		`itemDescription`, 		`itemQuantity`, 	`itemUnit`,		`itemContentQuantity`, 		`itemContentUnit`,		`itemPrice`,		`itemFlag`)
				SELECT	`lotNumber`, `processRemarks`, '".$productId."','".$supplierId."',	'".$supplierType."','".$poCurrency."',	'".$dateNeeded."',	'".$itemName."',	'".$itemDescription."',	'".$itemQuantity."','".$itemUnit."','".$itemContentQuantity."',	'".$itemContentUnit."',	'".$itemPrice."',	'".$itemFlag."'
				FROM ppic_workschedule WHERE id = ".$workSchedId." LIMIT 1
			";
			$queryInsert = $db->query($sql);
		}		
	}
	
	function generateMaterialPOLot($supplyId,$dateNeeded,$forPo)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$processCode = '461';//Purchase Order Making
		
		$poQuantity = $forPo;
		
		$targetFinish = $dateNeeded;
		$lot = getLotNumber();
		$repeatFlag = 1;
		while($repeatFlag == 1)
		{
			$repeatFlag = 0;				
			$sql = "INSERT INTO	ppic_lotlist
							(	lotNumber,	poId,	partId, 			parentLot,	partLevel,	workingQuantity,	identifier, dateGenerated,	status,		bookingStatus)
					VALUES	(	'".$lot."',	0,		'".$supplyId."',	'',			0,			'".$poQuantity."',	4,			now(),			1,			0)";
			//~ echo $sql."<br>";
			if($displayFlag == 0)
			{
				$queryInsert = $db->query($sql);
				if(!$queryInsert)
				{
					$mysqliError = $db->error;
					if(strstr($mysqliError,'Duplicate entry'))
					{
						$lot = getLotNumber(); // ------- Ace : Can Be Deleted If $lot = getLotNumber() Will Be Moved To The First Line Of The While Clause
						$repeatFlag = 1;
					}
				}
			}
		}
		
		$targetFinish = addDays(-7,$dateNeeded);
		
		if(strtotime($targetFinish)<strtotime(date('Y-m-d')))
		{
			$targetFinish = date('Y-m-d');
		}		

		$sql = "INSERT INTO `ppic_workschedule`
						(	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,			`actualFinish`,	`status`,	`employeeId`,	`processSection`,	`availability`)
				VALUES	(	'".$lot."',		597,			1,				'".$targetFinish."',	'',				0,			'',				5,					1),
						(	'".$lot."',		598,			1,				'".$targetFinish."',	'',				0,			'',				5,					0)
			";
		//~ echo $sql."<br>";
		if($displayFlag == 0)
		{
			$queryInsert = $db->query($sql);
			if($queryInsert)
			{
				$sql = "
					INSERT INTO view_workschedule
							(	`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`)
					SELECT 		`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`
					FROM	ppic_workschedule
					WHERE 	lotNumber LIKE '".$lot."' ORDER BY processOrder
					";
				$queryInsert = $db->query($sql);
			}
		}
		
		return $lot;		
	}
	
	function splitQty($materialComputationId,$finalQuantity,$productMOQ,$splitQtyArrival='')
	{
		include('PHP Modules/mysqliConnection.php');
		
		$finalQuantityTemp = $finalQuantity;
		
		$return = '';		
		
		if($splitQtyArrival=='')
		{
			$splitQtyArrivalData = '';
			$sql = "SELECT `splitQtyArrival` FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryMaterialComputation = $db->query($sql);
			if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
			{
				$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
				$splitQtyArrivalData = $resultMaterialComputation['splitQtyArrival'];
			}
			
			$dateArray = $finalReqDataArray = $finalLotDataArray = array();
			
			if($splitQtyArrivalData=='')
			{
				$dateNeededArray = $lotDataArray = $reqDataArray = array();
				$sql = "SELECT lotNumber, requirement, nestingDate FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId." ORDER BY nestingDate";
				$queryMaterialComputationDetails = $db->query($sql);
				if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
				{
					while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
					{
						$lotNumber = $resultMaterialComputationDetails['lotNumber'];
						$nestingDate = $resultMaterialComputationDetails['nestingDate'];
						$requirement = $resultMaterialComputationDetails['requirement'];
						$dateNeeded = addDays(-1,$nestingDate);
						
						if($lotNumber=='20-07-1840')		$req = 5;
						else if($lotNumber=='20-07-1839')	$req = 25;				
						
						if(strtotime($dateNeeded)<strtotime(date('Y-m-d')))
						{
							$dateNeeded = date('Y-m-d');
						}
						
						$dateNeededArray[] = $dateNeeded;
						
						if(!isset($lotDataArray[$dateNeeded])) $lotDataArray[$dateNeeded] = array();
						$lotDataArray[$dateNeeded][] = $lotNumber;
						
						if(!isset($reqDataArray[$dateNeeded])) $reqDataArray[$dateNeeded] = 0;
						$reqDataArray[$dateNeeded] += $requirement;
					}
				}
				
				if(count($dateNeededArray) > 0)
				{
					$dateNeededArray = array_values(array_filter(array_unique($dateNeededArray)));
					
					$lastDateTemp = '';
					
					sort($dateNeededArray);
					
					foreach($dateNeededArray as $dataDate)
					{
						$req = $reqDataArray[$dataDate];
						$lot = $lotDataArray[$dataDate];
						
						if($lastDateTemp=='')
						{
							$dateArray[] = $dataDate;
							$lastDateTemp = addDays(7,$dataDate);
							$finalDate = $dataDate;
						}
						else
						{
							if(strtotime($dataDate) > strtotime($lastDateTemp))
							{
								$dateArray[] = $dataDate;
								$lastDateTemp = addDays(7,$dataDate);
								$finalDate = $dataDate;
							}
						}
						
						if(!isset($finalLotDataArray[$finalDate])) $finalLotDataArray[$finalDate] = array();
						$finalLotDataArray[$finalDate][] = "'".implode("','",$lot)."'";
						
						if(!isset($finalReqDataArray[$finalDate])) $finalReqDataArray[$finalDate] = 0;
						$finalReqDataArray[$finalDate] += $req;
					}
					
					foreach($finalLotDataArray as $finalDate => $lotArray)
					{
						$sql = "UPDATE ppic_materialcomputationdetails SET arrivalDate = '".$finalDate."' WHERE materialComputationId = ".$materialComputationId." AND lotNumber IN(".implode(",",$lotArray).")";
						$queryUpdate = $db->query($sql);
					}
				}
			}
			else
			{
				$splitQtyArrivalDataArray = explode("\n",$splitQtyArrivalData);
				
				$forMaterialPOArray = array();
				if(count($splitQtyArrivalDataArray) > 0)
				{
					foreach($splitQtyArrivalDataArray as $splitQtyArrivalData)
					{
						$splitQtyArrivalPart = explode("|",$splitQtyArrivalData);
						
						$dateArray[] = $splitQtyArrivalPart[0];
						$finalReqDataArray[$splitQtyArrivalPart[0]] = $splitQtyArrivalPart[2];
						
						
					}
				}
			}
					
			$finalDateQtyArray = array();
			
			//~ echo "<br>".$finalQuantity;
			
			if(count($dateArray) > 0)
			{
				while($finalQuantity > 0)
				{
					foreach($dateArray as $date)
					{
						$qty = ceil($finalReqDataArray[$date]);
						if($productMOQ > $qty)
						{
							$qty = $productMOQ;
						}
						
						if($qty > $finalQuantity)
						{
							$qty = $finalQuantity;
						}
						
						if($finalQuantity<=0)
						{
							$qty = 0;
						}
						
						//~ echo "<br>".$date." gerald ".$finalQuantity." aguila ".$qty;
						
						$finalQuantity -= $qty;
						
						if(!isset($finalDateQtyArray[$date])) $finalDateQtyArray[$date] = 0;
						$finalDateQtyArray[$date] += $qty;
					}
				}
			
				$splitQtyArrivalArray = array();
				
				$lineNo = 0;
				$span = '';
				foreach($finalDateQtyArray as $date => $qty)
				{
					$lineNo++;
					
					$req = $finalReqDataArray[$date];
					$splitQtyArrivalArray[] = $date."|".$qty."|".$req;
					
					$thisLine = $date."|".$qty."|".$req;
					$qtyReq = "|".$qty."|".$req;
					
					$span .= "<span><input type='date' data-qty-req='".$qtyReq."' data-product-M-O-Q='".$productMOQ."' data-req='".$req."' data-final-quantity='".$finalQuantityTemp."' data-this-line='".$thisLine."' data-line-no='".$lineNo."' data-old-date='".$date."' data-material-computation-id='".$materialComputationId."' value='".$date."' onchange=\" changeDate(this); \" class='updateDateClass'> : <input type='number' data-product-M-O-Q='".$productMOQ."' data-req='".$req."' data-date='".$date."' data-final-quantity='".$finalQuantityTemp."' data-line-no='".$lineNo."' data-old-quantity='".$qty."' data-material-computation-id='".$materialComputationId."' class='finalQuantityClass api-form' value='".$qty."' min='".$minLimit."' onchange=\" changeQuantity(this); \" style='width:50px;' step='any'></span><br>";
				}
				
				$splitQtyArrival = implode("\n",$splitQtyArrivalArray);
				
				$sql = "UPDATE ppic_materialcomputation SET `splitQtyArrival` = '".$splitQtyArrival."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);			
				
				$return = $span;
			}
		}
		else
		{
			$splitQtyArrivalArray = explode("\n",$splitQtyArrival);
			
			$lineNo = 0;
			$span = '';
			if(count($splitQtyArrivalArray) > 0)
			{
				foreach($splitQtyArrivalArray as $splitQtyArrival)
				{
					$lineNo++;
					
					$splitQtyArrivalPart = explode("|",$splitQtyArrival);
					$date = $splitQtyArrivalPart[0];
					$qty = $splitQtyArrivalPart[1];
					$req = $splitQtyArrivalPart[2];
					
					$thisLine = $date."|".$qty."|".$req;
					$qtyReq = "|".$qty."|".$req;
					
					$span .= "<span><input type='date' data-qty-req='".$qtyReq."' data-product-M-O-Q='".$productMOQ."' data-req='".$req."' data-final-quantity='".$finalQuantityTemp."' data-this-line='".$thisLine."' data-line-no='".$lineNo."' data-old-date='".$date."' data-material-computation-id='".$materialComputationId."' value='".$date."' onchange=\" changeDate(this); \" class='updateDateClass'> : <input type='number' data-product-M-O-Q='".$productMOQ."' data-req='".$req."' data-date='".$date."' data-final-quantity='".$finalQuantityTemp."' data-line-no='".$lineNo."' data-old-quantity='".$qty."' data-material-computation-id='".$materialComputationId."' class='finalQuantityClass api-form' value='".$qty."' min='".$minLimit."' onchange=\" changeQuantity(this); \" style='width:50px;' step='any'></span><br>";
				}
				$splitQtyArrival = implode("\n",$splitQtyArrivalArray);
				
				$return = $span;
			}
		}
		
		return $return;
	}
	
	if(isset($_POST['submit']))
	{
		$batchId = date('YmdHis');
		
		$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial`, `productId`, `supplyId`, `splitQtyArrival` FROM ppic_materialcomputation WHERE lotNumber ='' AND status = 1 AND productId > 0";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
			{
				$materialComputationId = $resultMaterialComputation['materialComputationId'];
				$pvc = $resultMaterialComputation['pvc'];
				$productId = $resultMaterialComputation['productId'];
				$supplyId = $resultMaterialComputation['supplyId'];
				$splitQtyArrival = $resultMaterialComputation['splitQtyArrival'];
				
				//~ $supplyId = 0;
				//~ $sql = "SELECT supplyId FROM purchasing_supplierproductlinking WHERE productId = ".$productId." AND supplyType = 1 ORDER BY productLinkId DESC LIMIT 1";
				//~ $querySupplies = $db->query($sql);
				//~ if($querySupplies AND $querySupplies->num_rows > 0)
				//~ {
					//~ $resultSupplies = $querySupplies->fetch_assoc();
					//~ $supplyId = $resultSupplies['supplyId'];
				//~ }
				
				$splitQtyArrivalArray = explode("\n",$splitQtyArrival);
				
				$forMaterialPOArray = array();
				if(count($splitQtyArrivalArray) > 0)
				{
					foreach($splitQtyArrivalArray as $splitQtyArrival)
					{
						$splitQtyArrivalPart = explode("|",$splitQtyArrival);
						if($splitQtyArrivalPart[1] > 0)
						{
							$forMaterialPOArray[$splitQtyArrivalPart[0]] = $splitQtyArrivalPart[1];
						}
					}
				}
				
				if(count($forMaterialPOArray) > 0)
				{
					$arrayCount = count($forMaterialPOArray);
					
					foreach($forMaterialPOArray as $dateNeeded=>$forPo)
					{
						if($arrayCount==1)
						{
							$lotNumber = generateMaterialPOLot($supplyId,$dateNeeded,$forPo);
							
							$sql = "INSERT INTO system_confirmedmaterialpo (materialId, sheetQuantity, pvc,lotNumber) VALUES (".$supplyId.", ".$forPo.", ".$pvc.",'".$lotNumber."')";
							$queryInsert = $db->query($sql);
							if($queryInsert)
							{
								$sql = "UPDATE ppic_materialcomputation SET lotNumber = '".$lotNumber."', dateNeeded = '".$dateNeeded."', batchId = '".$batchId."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
								$queryUpdate = $db->query($sql);
							}
							
							insertForPurchase($lotNumber,$forPo,$productId);
						}
						else if($arrayCount > 1)
						{
							$lotNumber = generateMaterialPOLot($supplyId,$dateNeeded,$forPo);
							
							$sql = "INSERT INTO system_confirmedmaterialpo (materialId, sheetQuantity, pvc,lotNumber) VALUES (".$supplyId.", ".$forPo.", ".$pvc.",'".$lotNumber."')";
							$queryInsert = $db->query($sql);
							if($queryInsert)
							{
								$sql = "
									INSERT INTO `ppic_materialcomputation`
										(	`customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, 	`finalQuantity`,`status`, `idnumber`, `inputDateTime`,	`lotNumber`,		`dateNeeded`,		`blankingProcess`, `alternateMaterial`, `productId`, `supplyId`, `materialComputationIdSource`, `batchId`)
									SELECT	`customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, '".$forPo."',	'".$forPo."', 	`status`, `idnumber`, NOW(), 			'".$lotNumber."',	'".$dateNeeded."',	`blankingProcess`, `alternateMaterial`, `productId`, `supplyId`, '".$materialComputationId."',	'".$batchId."'
									FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1
								";
								$queryInsert = $db->query($sql);
							}	
							
							insertForPurchase($lotNumber,$forPo,$productId);						
						}
					}
					
					if($arrayCount > 1)
					{
						$sql = "UPDATE ppic_materialcomputation SET lotNumber = 'SPLITLOT' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
						$queryUpdate = $db->query($sql);
					}
				}
			}
		}
		
		$materialComputationIdArray = array();
		$sql = "SELECT materialComputationId, materialComputationIdSource FROM ppic_materialcomputation WHERE batchId = ".$batchId."";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
			{
				$materialComputationId = $resultMaterialComputation['materialComputationId'];
				$materialComputationIdSource = $resultMaterialComputation['materialComputationIdSource'];
				
				$materialComputationIdArray[] = ($materialComputationIdSource > 0) ? $materialComputationIdSource : $materialComputationId; 
			}
		}
		
		$arrivalDateArray = array();
		$sql = "SELECT lotNumber, nestingDate, arrivalDate FROM ppic_materialcomputationdetails WHERE materialComputationId IN(".implode(",",$materialComputationIdArray).")";
		$queryMaterialComputationDetails = $db->query($sql);
		if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
		{
			while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
			{
				$lotNumber = $resultMaterialComputationDetails['lotNumber'];
				$nestingDate = $resultMaterialComputationDetails['nestingDate'];
				$arrivalDate = $resultMaterialComputationDetails['arrivalDate'];
				
				if($arrivalDate > addDays(-1,$nestingDate))
				{
					$sql = "SELECT poId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$poId = $resultLotList['poId'];
						
						if(!isset($arrivalDateArray[$poId])) $arrivalDateArray[$poId] = $arrivalDate;
						if(strtotime($arrivalDate) < strtotime($arrivalDateArray[$poId]))
						{
							$arrivalDateArray[$poId] = $arrivalDate;
						}
					}
				}
			}
		}
		
		if(count($arrivalDateArray) > 0)
		{
			foreach($arrivalDateArray as $poId => $arrivalDate)
			{
				$startDate = addDays(1,$arrivalDate);
				
				$sql = "SELECT lotNumber, recoveryDate FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					$resultLotList = $queryLotList->fetch_assoc();
					$lotNumber = $resultLotList['lotNumber'];
					$dueDate = $resultLotList['recoveryDate'];
					
					generateScheduleItems($poId,array('start'=>$startDate,'dueDate'=>$dueDate),1,0);
				}
			}			
		}
		
		header('location:gerald_forMaterialPOPdf.php?batchId='.$batchId);
		exit(0);
	}
	
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='updateData')
		{
			$materialComputationId = $_POST['materialComputationId'];
			$fieldName = $_POST['fieldName'];
			$value = $_POST['value'];
			$productMOQ = $_POST['productMOQ'];
			$finalQuantity = $_POST['finalQuantity'];
			$newDate = $_POST['newDate'];
			$oldDate = $_POST['oldDate'];
			
			if($fieldName=='productId')
			{
				$valuePart = explode("-",$value);
				$productId = $valuePart[0];
				$supplyId = $valuePart[1];
				
				$sql = "UPDATE ppic_materialcomputation SET `productId` = '".$productId."', `supplyId` = '".$supplyId."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
			else
			{
				$sql = "UPDATE ppic_materialcomputation SET `".$fieldName."` = '".$value."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
			
			if($fieldName=='finalQuantity')
			{
				echo splitQty($materialComputationId,$value,$productMOQ);
			}
			else if($fieldName=='splitQtyArrival')
			{
				echo splitQty($materialComputationId,$finalQuantity,$productMOQ);
				
				$sql = "UPDATE ppic_materialcomputationdetails SET arrivalDate = '".$newDate."' WHERE materialComputationId = ".$materialComputationId." AND arrivalDate LIKE '".$oldDate."'";
				$queryUpdate = $db->query($sql);
			}
		}
		else if($_POST['ajaxType']=='updateSplitQtyArrival')
		{
			$materialComputationId = $_POST['materialComputationId'];
			$splitQtyArrival = $_POST['splitQtyArrival'];
			
			$sql = "UPDATE ppic_materialcomputation SET splitQtyArrival = '".$splitQtyArrival."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			echo splitQty($materialComputationId,$finalQuantity,$productMOQ,$splitQtyArrival);
		}
		exit(0);
	}
	
	echo "
		<form action='".$_SERVER['PHP_SELF']."' method='post' id='formId'></form>
		<table border='1'>
			<tr>
				<th></th>
				<th>".displayText('L111')."</th>
				<th>".displayText('L184')."</th>
				<th>".displayText('L74')."</th>
				<th>".displayText('L75')."</th>
				<th>".displayText('L67')."</th>
				<th>".displayText('L306')."</th>
				<th>".displayText('L31')."</th>
				<th>Purchasing Data</th>
				<th>MOQ</th>
				<th>For PO</th>
				<th>Arrival Date</th>
			</tr>
	";
	
	$sql = "SELECT `materialComputationId`, `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `blankingProcess`, `alternateMaterial`, `productId`, `supplyId`, `splitQtyArrival` FROM ppic_materialcomputation WHERE lotNumber ='' AND status = 1";
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
			$materialTypeInitial = $resultMaterialComputation['materialType'];
			$thickness = $resultMaterialComputation['thickness'];
			$length = $resultMaterialComputation['length'];
			$width = $resultMaterialComputation['width'];			
			$treatment = $resultMaterialComputation['treatment'];			
			$pvc = $resultMaterialComputation['pvc'];
			$quantity = $resultMaterialComputation['quantity'];			
			$finalQuantity = $resultMaterialComputation['finalQuantity'];			
			$status = $resultMaterialComputation['status'];
			$alternateMaterial = $resultMaterialComputation['alternateMaterial'];
			$productId = $resultMaterialComputation['productId'];
			$supplyId = $resultMaterialComputation['supplyId'];
			$splitQtyArrival = $resultMaterialComputation['splitQtyArrival'];
			
			//~ $materialType = $alternateMaterial;
			
			$materialTypeArray = array();
			$materialTypeArray[] = $materialTypeInitial;
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
					$materialTypeArray[] = $resultAlternateMaterial['materialType'];
				}
			}
			
			
			$materialTypeIdArray = array();
			$sql = "SELECT materialTypeId FROM engineering_materialtype WHERE materialType IN('".implode("','",$materialTypeArray)."')";
			$queryMaterialType = $db->query($sql);
			if($queryMaterialType AND $queryMaterialType->num_rows > 0)
			{
				while($resultMaterialType = $queryMaterialType->fetch_assoc())
				{
					$materialTypeIdArray[] = $resultMaterialType['materialTypeId'];
				}
			}
			
			$materialSpecIdArray = array();
			$sql = "SELECT materialSpecId FROM cadcam_materialspecs WHERE materialTypeId IN(".implode(",",$materialTypeIdArray).") AND metalThickness = ".$thickness."";
			$queryMaterialSpecs = $db->query($sql);
			if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
			{
				while($resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc())
				{
					$materialSpecIdArray[] = $resultMaterialSpecs['materialSpecId'];
				}
			}
			
			$cadcamTreatmentId = '';
			$sql = "SELECT treatmentId FROM cadcam_treatmentprocess WHERE treatmentName LIKE '".$treatment."' LIMIT 1";
			$queryTreatmentProcess = $db->query($sql);
			if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
			{
				$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
				$cadcamTreatmentId = $resultTreatmentProcess['treatmentId'];
			}
			
			$materialIdArray = array();
			$sql = "SELECT materialId FROM purchasing_material WHERE materialSpecId IN(".implode(",",$materialSpecIdArray).") AND thickness = ".$thickness." AND length = ".$length." AND width = ".$width."";
			$queryMaterial = $db->query($sql);
			if($queryMaterial AND $queryMaterial->num_rows > 0)
			{
				while($resultMaterial = $queryMaterial->fetch_assoc())
				{
					$materialIdArray[] = $resultMaterial['materialId'];
				}
			}
			
			//~ $materialTreatmentIdArray = array();
			//~ $sql = "SELECT materialTreatmentId FROM purchasing_materialtreatment WHERE materialId IN(".implode(",",$materialIdArray).") AND treatmentId = ".$cadcamTreatmentId."";
			//~ $queryMaterialTreatment = $db->query($sql);
			//~ if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
			//~ {
				//~ while($resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc())
				//~ {
					//~ $materialTreatmentIdArray[] = $resultMaterialTreatment['materialTreatmentId'];
				//~ }
			//~ }			
			
			$noData = 0;
			$arrayOuput = $listIdArray = $materialTreatmentIdArray = array();
			$sql = "SELECT materialTreatmentId, materialId, treatmentId, pvc FROM purchasing_materialtreatment WHERE materialId IN(".implode(",",$materialIdArray).") AND treatmentId = ".$cadcamTreatmentId." AND pvc = ".$pvc."";
			$queryMaterialTreatment = $db->query($sql);
			if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
			{
				while($resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc())
				{
					$materialTreatmentId = $resultMaterialTreatment['materialTreatmentId'];
					$materialId = $resultMaterialTreatment['materialId'];
					$treatmentId = $resultMaterialTreatment['treatmentId'];
					$pibisi = ($resultMaterialTreatment['pvc']==1) ? ' w/PVC' : '';
					
					$sql = "SELECT `materialId`, `materialSpecId`, `thickness`, `length`, `width`, `spare` FROM `purchasing_material` WHERE materialId = ".$materialId." LIMIT 1";
					$queryMaterial = $db->query($sql);
					if($queryMaterial->num_rows > 0)
					{
						$resultMaterial = $queryMaterial->fetch_array();
						
						$materialTypeId = '';
						$sql = "SELECT materialTypeId FROM cadcam_materialspecs WHERE materialSpecId = ".$resultMaterial['materialSpecId']." LIMIT 1";
						$queryMaterialSpecs = $db->query($sql);
						if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
						{
							$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
							$materialTypeId = $resultMaterialSpecs['materialTypeId'];
						}
						
						$materialType = '';
						//~ $sql = "SELECT materialType FROM purchasing_materialtype WHERE suppliermaterialID = ".$materialTypeId." LIMIT 1";
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
						$queryMaterialType = $db->query($sql);
						if($queryMaterialType->num_rows > 0)
						{
							$resultMaterialType = $queryMaterialType->fetch_array();
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
						
						//~ $selected = ($supplyId == $materialTreatmentId) ? 'selected' : '';
						
						$productIdArray = array();
						$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$materialTreatmentId." AND supplyType = 1";
						$querySupplierProductLinking = $db->query($sql);
						if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
						{
							while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
							{
								$productIdArray[] = $resultSupplierProductLinking['productId'];
							}
						}
						
						$sql = "SELECT productId, supplierId FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND supplyType = 1";
						$querySupplies = $db->query($sql);
						if($querySupplies AND $querySupplies->num_rows > 0)
						{
							while($resultSupplies = $querySupplies->fetch_assoc())
							{
								$listIdArray[] = $listId = $resultSupplies['productId'];
								$supplierId = $resultSupplies['supplierId'];
								
								$materialTreatmentIdArray[] = $materialTreatmentId;
								
								$supplierAlias = '';
								$sql = "SELECT supplierAlias FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
								$querySupplier = $db->query($sql);
								if($querySupplier AND $querySupplier->num_rows > 0)
								{
									$resultSupplier = $querySupplier->fetch_assoc();
									$supplierAlias = $resultSupplier['supplierAlias'];
								}

								$arrayOuput[$listId."-".$materialTreatmentId] = $materialType." t".$resultMaterial['thickness']." ".$resultMaterial['length']." x ".$resultMaterial['width']." ".$treatmentName.$pibisi." (".$supplierAlias.")";
							}
						}
						else
						{
							$arrayOuput['noData'.(++$noData)] = $materialType." t".$resultMaterial['thickness']." ".$resultMaterial['length']." x ".$resultMaterial['width']." ".$treatmentName.$pibisi." (NO PRICE DATA)";
						}
					}
				}
			}
			
			if($productId==0 AND $supplyId==0 AND count($listIdArray) == 1)
			{
				$productId = $listIdArray[0];
				$supplyId = $materialTreatmentIdArray[0];
				$sql = "UPDATE ppic_materialcomputation SET productId = ".$productId.", supplyId = ".$supplyId." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}

			if(count($materialTreatmentIdArray) > 1)
			{
				$sql = "SELECT poId, partId FROM ppic_lotlist WHERE partId IN(".implode(",",$materialTreatmentIdArray).") AND identifier = 4 AND status = 1 ORDER BY dateGenerated DESC";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$poId = $resultLotList['poId'];
						$sql = "SELECT productId FROM purchasing_pocontents WHERE poContentId = ".$poId." AND productId IN(".implode(",",$listIdArray).") AND itemStatus != 2 LIMIT 1";
						$queryPoContents = $db->query($sql);
						if($queryPoContents AND $queryPoContents->num_rows > 0)
						{
							$resultPoContents = $queryPoContents->fetch_assoc();

							$productId = $resultPoContents['productId'];
							$supplyId = $resultLotList['partId'];
							$sql = "UPDATE ppic_materialcomputation SET productId = ".$productId.", supplyId = ".$supplyId." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
							$queryUpdate = $db->query($sql);							
							break;
						}
					}
				}
			}
			
			asort($arrayOuput);
			
			$supplyChoices = "<select class='updateDataClass' data-material-computation-id='".$materialComputationId."' data-field-name='productId'>";
			$supplyChoices .= "<option value='0'></option>";
			foreach ($arrayOuput as $key => $item) {
				
				$selected = ($key == $productId."-".$supplyId) ? 'selected' : '';
				
				$supplyChoices .= "<option value='".$key."' ".$selected.">".$item."</option>";
			}			
			$supplyChoices .= "</select>";
			
			$productMOQ = 0;
			$sql = "SELECT productMOQ FROM purchasing_supplierproducts WHERE productId = ".$productId." LIMIT 1";
			$querySupplierProducts = $db->query($sql);
			if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
			{
				$resultSupplierProducts = $querySupplierProducts->fetch_assoc();
				$productMOQ = $resultSupplierProducts['productMOQ'];
			}
			//~ $productMOQ = 10;
			
			$pvcStatus = ($pvc==1) ? 'Yes' : 'No';
			
			$minLimit = ($productMOQ > $quantity) ? $productMOQ : $quantity;
			
			if($minLimit > $finalQuantity)
			{
				$finalQuantity = $minLimit;
				$sql = "UPDATE ppic_materialcomputation SET finalQuantity = ".$minLimit." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);				
			}

			$quantityInput = "<input type='number' data-material-computation-id='".$materialComputationId."' data-product-M-O-Q='".$productMOQ."' data-field-name='finalQuantity' class='updateDataClass api-form' value='".$finalQuantity."' min='".$minLimit."' style='width:50px;' step='any'>";
			
			$span = splitQty($materialComputationId,$finalQuantity,$productMOQ,$splitQtyArrival);
			
			echo  "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$materialTypeInitial."</td>
					<td>".$thickness."</td>
					<td>".$length."</td>
					<td>".$width."</td>
					<td>".$treatment."</td>
					<td>".$pvcStatus."</td>
					<td>".$quantity."</td>
					<td>".$supplyChoices."</td>
					<td>".$productMOQ."</td>
					<td>".$quantityInput."</td>
					<td id='splitQty".$materialComputationId."' >".$span."</td>
				</tr>
			";
		}
	}
	
	echo "
			<tr>
				<th colspan='12'><input type='submit' name='submit' value='Submit' form='formId'></th>
			</tr>
		</table>
	";
?>
<script src="/<?php echo v; ?>/Common Data/Templates/jquery.js"></script>
<script>
	function changeQuantity(obj)
	{
		var value = $(obj).val();
		var materialComputationId = $(obj).data('materialComputationId');
		var req = $(obj).data('req');			
		var lineNo = $(obj).data('lineNo');
		var finalQuantity = $(obj).data('finalQuantity');
		var oldQuantity = $(obj).data('oldQuantity');
		var productMOQ = $(obj).data('productMOQ');
		
		if(parseFloat(value) > parseFloat(finalQuantity))
		{
			alert('Must be equal or greater than '+finalQuantity);
			location.reload();
			return false;
		}
		
		var newSplitQtyArrivalArray = [];
		var currentLine = 0;		
		var plusValue = 0;	
		var minusValue = 0;	
		$(".finalQuantityClass[data-material-computation-id="+materialComputationId+"]").each(function(i){
			var thisReq = $(this).data('req');
			var thisQty = $(this).data('oldQuantity');
			var thisDate = $(this).data('date');
			//~ var thisLine = $(this).data('thisLine');
			currentLine = i + 1;
			//~ console.log(currentLine);
			//~ console.log(thisLine);
			if(currentLine==lineNo)
			{
				console.log("Value : "+value);
				console.log("This Qty : "+thisQty);
				console.log("Value : "+parseFloat(value));
				console.log("This Qty : "+parseFloat(thisQty));
				if(parseFloat(value) > parseFloat(thisQty))
				{
					minusValue = parseFloat(value) - parseFloat(thisQty);
				}
				else if(parseFloat(value) < parseFloat(thisQty))
				{
					plusValue = parseFloat(thisQty) - parseFloat(value);
				}
				
				console.log("Minus : "+minusValue);
				console.log("Plus : "+plusValue);
				newSplitQtyArrivalArray[i] = thisDate+"|"+value+"|"+thisReq;
			}
			else
			{
				if(minusValue > 0)
				{
					if(parseFloat(thisQty) > parseFloat(minusValue))
					{
						thisQty = parseFloat(thisQty) - parseFloat(minusValue);
					}
					else
					{
						minusValue = parseFloat(minusValue) - parseFloat(thisQty);
						thisQty = 0;
					}
				}
				else if(plusValue > 0)
				{
					thisQty = parseFloat(thisQty) + parseFloat(plusValue);
				}
				
				//~ if(parseFloat(productMOQ) > parseFloat(thisQty))
				//~ {
					//~ alert('Must be equal or greater than '+productMOQ);
					//~ location.reload();
					//~ return false;
				//~ }
				
				console.log(thisQty);
				newSplitQtyArrivalArray[i] = thisDate+"|"+thisQty+"|"+thisReq;
			}
		});
		
		var newSplitQtyArrival = newSplitQtyArrivalArray.join("\n");
		console.log(newSplitQtyArrival);
		$.ajax({
			url:"<?php echo $_SERVER['PHP_SELF'];?>",
			type:"post",
			data:{
				ajaxType:'updateSplitQtyArrival',
				materialComputationId:materialComputationId,
				splitQtyArrival:newSplitQtyArrival,
				productMOQ:productMOQ,
				finalQuantity:finalQuantity					
			},
			success:function(data){
				location.reload();
				//~ $("#splitQty"+materialComputationId).html(data);
				//~ alert(data);
				//~ console.log(data);
			}
		});		
	}
	
	function changeDate(obj)
	{
		var value = $(obj).val();
		var materialComputationId = $(obj).data('materialComputationId');			
		var qtyReq = $(obj).data('qtyReq');			
		var lineNo = $(obj).data('lineNo');
		var productMOQ = $(obj).data('productMOQ');			
		var finalQuantity = $(obj).data('finalQuantity');			
		var oldDate = $(obj).data('oldDate');			
		
		var newSplitQtyArrivalArray = [];
		var currentLine = 0;			
		$(".updateDateClass[data-material-computation-id="+materialComputationId+"]").each(function(i){
			var thisLine = $(this).data('thisLine');
			currentLine = i + 1;
			//~ console.log(currentLine);
			//~ console.log(thisLine);
			if(currentLine==lineNo)
			{
				//~ console.log(newLineCode);
				newSplitQtyArrivalArray[i] = value+qtyReq;
			}
			else
			{
				//~ console.log('asd');
				newSplitQtyArrivalArray[i] = thisLine;
			}
		});
		
		var newSplitQtyArrival = newSplitQtyArrivalArray.join("\n");
		console.log(newSplitQtyArrival);
		$.ajax({
			url:"<?php echo $_SERVER['PHP_SELF'];?>",
			type:"post",
			data:{
				ajaxType:'updateData',
				materialComputationId:materialComputationId,
				fieldName:'splitQtyArrival',
				value:newSplitQtyArrival,
				productMOQ:productMOQ,
				finalQuantity:finalQuantity,
				newDate:value,
				oldDate:oldDate
			},
			success:function(data){
				$("#splitQty"+materialComputationId).html(data);
				location.reload();
			}
		});			
	}
	
	$(function(){
		/*
		$(".finalQuantityClass").change(function(){
			var value = $(this).val();
			var materialComputationId = $(this).data('materialComputationId');
			var req = $(this).data('req');			
			var lineNo = $(this).data('lineNo');
			var finalQuantity = $(this).data('finalQuantity');
			var oldQuantity = $(this).data('oldQuantity');
			var productMOQ = $(this).data('productMOQ');
			
			if(parseFloat(value) > parseFloat(finalQuantity))
			{
				alert('Must be equal or greater than '+finalQuantity);
				return false;
			}
			
			var newSplitQtyArrivalArray = [];
			var currentLine = 0;		
			var plusValue = 0;	
			var minusValue = 0;	
			$(".finalQuantityClass[data-material-computation-id="+materialComputationId+"]").each(function(i){
				var thisReq = $(this).data('req');
				var thisQty = $(this).data('oldQuantity');
				var thisDate = $(this).data('date');
				//~ var thisLine = $(this).data('thisLine');
				currentLine = i + 1;
				//~ console.log(currentLine);
				//~ console.log(thisLine);
				if(currentLine==lineNo)
				{
					console.log("Value : "+value);
					console.log("This Qty : "+thisQty);
					console.log("Value : "+parseFloat(value));
					console.log("This Qty : "+parseFloat(thisQty));
					if(parseFloat(value) > parseFloat(thisQty))
					{
						minusValue = parseFloat(value) - parseFloat(thisQty);
					}
					else if(parseFloat(value) < parseFloat(thisQty))
					{
						plusValue = parseFloat(thisQty) - parseFloat(value);
					}
					
					console.log("Minus : "+minusValue);
					console.log("Plus : "+plusValue);
					newSplitQtyArrivalArray[i] = thisDate+"|"+value+"|"+thisReq;
				}
				else
				{
					if(minusValue > 0)
					{
						if(parseFloat(thisQty) > parseFloat(minusValue))
						{
							thisQty = parseFloat(thisQty) - parseFloat(minusValue);
						}
						else
						{
							minusValue = parseFloat(minusValue) - parseFloat(thisQty);
							thisQty = 0;
						}
					}
					else if(plusValue > 0)
					{
						thisQty = parseFloat(thisQty) + parseFloat(plusValue);
					}
					
					console.log(thisQty);
					newSplitQtyArrivalArray[i] = thisDate+"|"+thisQty+"|"+thisReq;
				}
			});
			
			var newSplitQtyArrival = newSplitQtyArrivalArray.join("\n");
			console.log(newSplitQtyArrival);
			$.ajax({
				url:"<?php echo $_SERVER['PHP_SELF'];?>",
				type:"post",
				data:{
					ajaxType:'updateSplitQtyArrival',
					materialComputationId:materialComputationId,
					splitQtyArrival:newSplitQtyArrival,
					productMOQ:productMOQ,
					finalQuantity:finalQuantity					
				},
				success:function(data){
					$("#splitQty"+materialComputationId).html(data);
					//~ alert(data);
					//~ console.log(data);
				}
			});
		});*/
		
		$(".updateDataClass").change(function(){
			var value = $(this).val();
			var materialComputationId = $(this).data('materialComputationId');
			var fieldName = $(this).data('fieldName');
			var productMOQ = '0';
			if(fieldName=='finalQuantity')
			{
				productMOQ = $(this).data('productMOQ');
				var minValue = $(this).prop('min');
				if(parseFloat(value) < parseFloat(minValue))
				{
					alert('Must be equal or greater than '+minValue);
					value = minValue;
					$(this).val(minValue);
					
				}
			}
			//~ alert(productMOQ);
			$.ajax({
				url:"<?php echo $_SERVER['PHP_SELF'];?>",
				type:"post",
				data:{
					ajaxType:'updateData',
					materialComputationId:materialComputationId,
					fieldName:fieldName,
					value:value,
					productMOQ:productMOQ
				},
				success:function(data){
					if(fieldName=='finalQuantity')
					{
						$("#splitQty"+materialComputationId).html(data);
					}
					else
					{
						location.reload();
					}
				}
			});
		});
		/*
		$(".updateDateClass").change(function(){
			var value = $(this).val();
			var materialComputationId = $(this).data('materialComputationId');			
			var qtyReq = $(this).data('qtyReq');			
			var lineNo = $(this).data('lineNo');
			var productMOQ = $(this).data('productMOQ');			
			var finalQuantity = $(this).data('finalQuantity');			
			var oldDate = $(this).data('oldDate');			
			
			var newSplitQtyArrivalArray = [];
			var currentLine = 0;			
			$(".updateDateClass[data-material-computation-id="+materialComputationId+"]").each(function(i){
				var thisLine = $(this).data('thisLine');
				currentLine = i + 1;
				//~ console.log(currentLine);
				//~ console.log(thisLine);
				if(currentLine==lineNo)
				{
					//~ console.log(newLineCode);
					newSplitQtyArrivalArray[i] = value+qtyReq;
				}
				else
				{
					//~ console.log('asd');
					newSplitQtyArrivalArray[i] = thisLine;
				}
			});
			
			var newSplitQtyArrival = newSplitQtyArrivalArray.join("\n");
			console.log(newSplitQtyArrival);
			$.ajax({
				url:"<?php echo $_SERVER['PHP_SELF'];?>",
				type:"post",
				data:{
					ajaxType:'updateData',
					materialComputationId:materialComputationId,
					fieldName:'splitQtyArrival',
					value:newSplitQtyArrival,
					productMOQ:productMOQ,
					finalQuantity:finalQuantity,
					newDate:value,
					oldDate:oldDate
				},
				success:function(data){
					$("#splitQty"+materialComputationId).html(data);
					location.reload();
				}
			});			
		});*/
	});
</script>
