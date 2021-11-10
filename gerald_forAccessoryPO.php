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
		$accessoryComputationId = $dataPart[0];
		$supplyId = $dataPart[1];
		
		$sql = "SELECT `finalQuantity` FROM ppic_accessorycomputation WHERE accessoryComputationId = ".$accessoryComputationId." LIMIT 1";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			$resultMaterialComputation = $queryMaterialComputation->fetch_assoc();
			$finalQuantity = $resultMaterialComputation['finalQuantity'];
			
			$lotNumber = createPurchasingLotNumber($supplyId,4,$finalQuantity);
			
			if(strstr($lotNumber,'error')===FALSE)
			{
				//~ $sql = "UPDATE ppic_lotlist SET workingQuantity = ".$finalQuantity." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				//~ $queryUpdate = $db->query($sql);
				
				$sql = "UPDATE ppic_accessorycomputation SET lotNumber = '".$lotNumber."' WHERE accessoryComputationId = ".$accessoryComputationId." LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
		}
		
		header('location:'.$_SERVER['PHP_SELF']);
		exit(0);
	}
	
	echo "
		<form action='' method='post' id='formId'></form>
		<table border='1'>
			<tr>
				<th></th>
				<th>".displayText('L96')."</th>
				<th>".displayText('L97')."</th>
				<th>".displayText('L506')."</th>
				<th>".displayText('L31')."</th>
				<th></th>
			</tr>
	";
	
	$sql = "SELECT `accessoryComputationId`, `customerAlias`, `accessoryNumber`, `accessoryName`, `accessoryDescription`, `quantity`, `finalQuantity`, `lotNumber`, `alternateAccessory` FROM ppic_accessorycomputation WHERE lotNumber ='' AND status = 1";
	$queryAccessoryComputation = $db->query($sql);
	if($queryAccessoryComputation AND $queryAccessoryComputation->num_rows > 0)
	{
		while($resultAccessoryComputation = $queryAccessoryComputation->fetch_assoc())
		{
			$accessoryComputationId = $resultAccessoryComputation['accessoryComputationId'];
			$customerAlias = $resultAccessoryComputation['customerAlias'];
			$accessoryNumber = $resultAccessoryComputation['accessoryNumber'];
			$accessoryName = $resultAccessoryComputation['accessoryName'];
			$accessoryDescription = $resultAccessoryComputation['accessoryDescription'];
			$quantity = $resultAccessoryComputation['quantity'];			
			$finalQuantity = $resultAccessoryComputation['finalQuantity'];			
			$status = $resultAccessoryComputation['status'];
			$alternateAccessory = $resultAccessoryComputation['alternateAccessory'];
			
			$accessoryId = '';
			$sql = "SELECT accessoryId FROM cadcam_accessories WHERE accessoryNumber LIKE '".$accessoryNumber."' AND accessoryName LIKE '".$accessoryName."' AND accessoryDescription LIKE '".$accessoryDescription."' LIMIT 1";
			$queryAccessory = $db->query($sql);
			if($queryAccessory AND $queryAccessory->num_rows > 0)
			{
				$resultAccessory = $queryAccessory->fetch_assoc();
				$accessoryId = $resultAccessory['accessoryId'];
			}
			
			$variable = "";
			if($accessoryId!='')
			{
				$productIdArray = array();
				$sql = "SELECT productId FROM purchasing_supplierproductlinking WHERE supplyId = ".$accessoryId." AND supplyType = 4";
				$querySupplierProductLinking = $db->query($sql);
				if($querySupplierProductLinking AND $querySupplierProductLinking->num_rows > 0)
				{
					while($resultSupplierProductLinking = $querySupplierProductLinking->fetch_assoc())
					{
						$productIdArray[] = $resultSupplierProductLinking['productId'];
					}
				}
				
				$sql = "SELECT productMOQ FROM purchasing_supplierproducts WHERE productId IN(".implode(",",$productIdArray).") AND productMOQ > 0 AND supplyType = 4 LIMIT 1";
				$querySupplies = $db->query($sql);
				if($querySupplies AND $querySupplies->num_rows > 0)
				{
					$variable = "<button name='data' value='".$accessoryComputationId."|".$accessoryId."' form='formId'>".displayText('L491')."</button>";
				}
				else
				{
					$variable = "<span style='color:blue;text-decoration:underline;cursor:pointer;'>NO DATA</span>";
				}
			}
			
			echo  "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$accessoryNumber."</td>
					<td>".$accessoryName."</td>
					<td>".$accessoryDescription."</td>
					<td>".$finalQuantity."</td>
					<td>".$variable."</td>
				</tr>
			";
		}
	}
	
	echo "</table>";
?>
