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
	
	$requirementArray = $poIdArray = $lotNumberArray = $requirementDataArray = array();
	$count = $queryPosition;
	$sql = "SELECT lotNumber, poId, partId, SUM(workingQuantity) as workingQuantity FROM ppic_lotlist ".$sqlFilter." ".$endQuery;
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
			
			$poNumber = $customerId = '';
			$sql = "SELECT poNumber, customerId FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				$resultPoList = $queryPoList->fetch_assoc();
				$poNumber = $resultPoList['poNumber'];
				$customerId = $resultPoList['customerId'];
			}
			
			$accessoryNumber = $accessoryName = $accessoryDescription = '';
			$sql = "SELECT accessoryNumber, accessoryName, accessoryDescription FROM cadcam_accessories WHERE accessoryId = ".$partId." LIMIT 1";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_assoc();
				$accessoryNumber = $resultParts['accessoryNumber'];
				$accessoryName = $resultParts['accessoryName'];
				$accessoryDescription = $resultParts['accessoryDescription'];
			}
			
			$customerAlias = '';
			$sql = "SELECT customerAlias FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
			$queryCustomer = $db->query($sql);
			if($queryCustomer AND $queryCustomer->num_rows > 0)
			{
				$resultCustomer = $queryCustomer->fetch_assoc();
				$customerAlias = $resultCustomer['customerAlias'];
			}
			
			$arrayKey = $accessoryNumber."`".$accessoryName."`".$accessoryDescription."`".$customerAlias;
			
			if(!isset($requirementArray[$arrayKey])) $requirementArray[$arrayKey] = 0;
			$requirementArray[$arrayKey] += $workingQuantity;
			
			$tableContent .= "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$customerAlias."</td>
					<td>".$poNumber."</td>
					<td>".$lotNumber."</td>
					<td>".$accessoryNumber."</td>
					<td>".$accessoryName."</td>
					<td>".$accessoryDescription."</td>
					<td align='right' class='workingQuantity'>".$workingQuantity."</td>
				</tr>
			";
		}
		if($submitType=='calculate')
		{
			if(count($requirementArray) > 0)
			{
				$sql = "DELETE FROM ppic_accessorycomputation WHERE lotNumber LIKE ''";
				$queryDelete = $db->query($sql);
				
				$sqlMain = "INSERT INTO `ppic_accessorycomputation`(`customerAlias`, `accessoryNumber`, `accessoryName`, `accessoryDescription`, `quantity`, `finalQuantity`, `status`, `idNumber`, `inputDateTime`, `lotNumber`) VALUES ";
				$sqlValuesArray = array();
				$counter = 0;
				$compressedInput = "";
				foreach($requirementArray as $key=>$val)
				{
					$keyArray = explode("`",$key);
					
					$sqlValues = "('".$keyArray[3]."','".$keyArray[0]."','".$keyArray[1]."','".$keyArray[2]."','".ceil($val)."','".ceil($val)."','0','".$_SESSION['idNumber']."',NOW(),'')";
					
					$sqlValuesArray[] = $sqlValues;
					$counter++;
					if($counter==50)
					{
						$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
						$queryUpdate = $db->query($sqlInsert);
						$sqlValuesArray = array();
						$counter = 0;
					}
					
					if($compressedInput == "")
					{
						$compressedInput = $keyArray[0]."`".$keyArray[1]."`".$keyArray[2]."`".$keyArray[3]."`".ceil($val)."`".ceil($val);
					}
					else
					{
						$compressedInput = $compressedInput."`".$keyArray[0]."`".$keyArray[1]."`".$keyArray[2]."`".$keyArray[3]."`".ceil($val)."`".ceil($val);
					}
				}
				if($counter > 0)
				{
					$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
					$queryUpdate = $db->query($sqlInsert);
				}
				
				header('location:gerald_roReviewAccessoryComputation.php');
				exit(0);
			}
		}
		else
		{		
			echo $tableContent;
		}
	}
					
?>
