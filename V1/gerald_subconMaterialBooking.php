<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	if(isset($_POST['ajaxType']) AND $_POST['ajaxType']=='updateMaterial')
	{
		$materialComputationId = $_POST['materialComputationId'];
		$inventoryId = $_POST['inventoryId'];
		
		$sql = "SELECT `length`, `width`, `finalQuantity` FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			$resultMaterialComputation = $queryMaterialComputation->fetch_array();
			$length = $resultMaterialComputation['length'];
			$width = $resultMaterialComputation['width'];
			$finalQuantity = $resultMaterialComputation['finalQuantity'];
		}
		
		$dataThree = $dataFour = '';
		$sql = "SELECT dataThree, dataFour FROM warehouse_inventory WHERE inventoryId LIKE '".$inventoryId."' LIMIT 1";
		$queryInventory = $db->query($sql);
		if($queryInventory AND $queryInventory->num_rows > 0)
		{
			$resultInventory = $queryInventory->fetch_assoc();
			$dataThree = $resultInventory['dataThree'];
			$dataFour = $resultInventory['dataFour'];
		}
		
		$programP = ($dataThree / $length);
		$programK = ($dataFour / $width);
		
		$quantityPerSheet = floor($programP) * floor($programK);
		
		$workingQuantity = $sheetCount = 0;
		while($finalQuantity > $workingQuantity)
		{
			$workingQuantity += $quantityPerSheet;
			$sheetCount++;
		}
		
		$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$materialComputationId."' LIMIT 1";
		$queryBookingDetails = $db->query($sql);
		if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
		{
			$resultBookingDetails = $queryBookingDetails->fetch_assoc();
			$bookingId = $resultBookingDetails['bookingId'];
			
			$sql = "UPDATE engineering_booking SET inventoryId = '".$inventoryId."', bookingQuantity = ".$sheetCount." WHERE bookingId = ".$bookingId." LIMIT 1";
			$queryUpdate = $db->query($sql);
			if($queryUpdate)
			{
				$inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" apiOpenModalBox({url:'gerald_subconMaterialBooking.php',post:'materialComputationId=".$materialComputationId."&inventoryId=".$inventoryId."',customFunction:function(){jsFunctions();}}); \">".$inventoryId."</span>";
				
				$data = array(
					'dataOne' 		=>	"<td class='".$materialComputationId."' data-one='one'>".$inventoryIdSpan."</td>",
					'dataTwo' 		=>	"<td class='".$materialComputationId."' data-two='two'>".$dataThree."</td>",
					'dataThree' 		=>	"<td class='".$materialComputationId."' data-three='three'>".$dataFour."</td>",
					'dataFour' 		=>	"<td class='".$materialComputationId."' data-four='four'>".$sheetCount."</td>",
					);
				echo json_encode($data);
			}
		}
		else
		{
			$sql = "INSERT INTO engineering_booking
							(	inventoryId,		bookingQuantity,	bookingDate,	bookingTime,	bookingStatus,	nestingType)
					VALUES	(	'".$inventoryId."',	".$sheetCount.",	now(),			now(),			2,				2)";
			$insertQuery = $db->query($sql);
			if($insertQuery)
			{
				$bookingId = $db->insert_id;
				
				$sql = "INSERT INTO	engineering_bookingdetails
								(	bookingId,		lotNumber,						quantity,				status)
						VALUES	(	".$bookingId.", '".$materialComputationId."',	".$workingQuantity.", 	0)";
				$insertQuery = $db->query($sql);
				
				$inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" apiOpenModalBox({url:'gerald_subconMaterialBooking.php',post:'materialComputationId=".$materialComputationId."&inventoryId=".$inventoryId."',customFunction:function(){jsFunctions();}}); \">".$inventoryId."</span>";
				
				$data = array(
					'dataOne' 		=>	"<td class='".$materialComputationId."' data-one='one'>".$inventoryIdSpan."</td>",
					'dataTwo' 		=>	"<td class='".$materialComputationId."' data-two='two'>".$dataThree."</td>",
					'dataThree' 		=>	"<td class='".$materialComputationId."' data-three='three'>".$dataFour."</td>",
					'dataFour' 		=>	"<td class='".$materialComputationId."' data-four='four'>".$sheetCount."</td>"
					);
				echo json_encode($data);
			}
		}
		
		exit(0);
	}
	
	$materialComputationId = $_POST['materialComputationId'];
	$inventoryId = $_POST['inventoryId'];
	
	$sql = "SELECT `customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber` FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
	$queryMaterialComputation = $db->query($sql);
	if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
	{
		$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
		$customerAlias = $resultMaterialComputation['customerAlias'];
		$materialType = $resultMaterialComputation['materialType'];
		$thickness = $resultMaterialComputation['thickness'];
		$length = $resultMaterialComputation['length'];
		$width = $resultMaterialComputation['width'];
		$finalQuantity = $resultMaterialComputation['finalQuantity'];
		
		$count = 0;
		
		echo "
			<input type='hidden' name='materialComputationId' value='".$materialComputationId."''>
			<table class='api-table-fixedheader' style='height:50vh;'>
				<thead>
					<tr>
						<th></th>
						<th>Inventory Id</th>
						<th>".displayText('L111')."</th>
						<th>".displayText('L184')."</th>
						<th>".displayText('L74')."</th>
						<th>".displayText('L75')."</th>
						<th>".displayText('L2093')."</th>
					</tr>
				</thead>
				<tbody>
		";
		
		$requiredArea = ($length * $width) * $finalQuantity;
		
		if(stristr($customerAlias,'jamco')!==FALSE)//Jamco
		{
			$filterSupplier = "AND supplierAlias IN('Jamco','KAPCO','Kapco Manufacturing Inc.')";
		}
		else if(stristr($customerAlias,'B/E')!==FALSE)//B/E
		{
			$filterSupplier = "AND supplierAlias IN('Metalweb Ltd.','KAPCO','Kapco Manufacturing Inc.','Shs Perforated Materials Inc.','B/e Aerospace','Garmco','MD AEROSPACE')";
		}
		
		$sql = "SELECT inventoryId, supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive, inventoryQuantity FROM warehouse_inventory WHERE type = 1 AND dataOne LIKE '".$materialType."' AND dataTwo = ".$thickness." AND dataThree >= ".$length." AND dataFour >= ".$width." AND dataFive LIKE 'Raw' AND inventoryId != '".$inventoryId."' ".$filterSupplier." ORDER BY stockDate ASC";
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
				
				$materialArea = ($dataThree * $dataFour) * $stock;
				
				if($materialArea >= $requiredArea)
				{
					
					$inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' class='inventoryIdClass' data-inventory-id='".$inventoryId."'>".$inventoryId."</span>";
					
					echo "
						<tr>
							<td>".++$count."</td>
							<td>".$inventoryIdSpan."</td>
							<td>".$dataOne."</td>
							<td>".$dataTwo."</td>
							<td>".$dataThree."</td>
							<td>".$dataFour."</td>
							<td>".$stock."</td>
						</tr>
					";
				}
			}
		}
		echo "
			</tbody>
			
			</table>
		";
	}
?>
