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
	$sql = "SELECT `accessoryComputationId`, `customerAlias`, `accessoryNumber`, `accessoryName`, `accessoryDescription`, `quantity`, `finalQuantity`, `status` FROM `ppic_accessorycomputation` ".$sqlFilter." ".$endQuery;
	$sqlMain = $sql;
	$query = $db->query($sql);
	if($query->num_rows > 0)
	{
		//~ $tableContent = "<tr><td colspan='14'>".$sqlMain."</td></tr>";
		while($result = $query->fetch_array())
		{
			$accessoryComputationId = $result['accessoryComputationId'];
			$customerAlias = $result['customerAlias'];
			$accessoryNumber = $result['accessoryNumber'];
			$accessoryName = $result['accessoryName'];
			$accessoryDescription = $result['accessoryDescription'];
			$quantity = $result['quantity'];
			$finalQuantity = $result['finalQuantity'];
			$status = $result['status'];
			
			$pvcStatus = ($pvc==1) ? 'Yes' : 'No';
			
			$quantityInput = "<input type='number' data-accessory-computation-id='".$accessoryComputationId."' class='finalQuantityClass api-form' name='qtyPerSheet[]' value='".$finalQuantity."' style='width:50px;' step='any' form='computeFormId'>";
			
			if($lotNumber=='')
			{
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
				
				$yonko = "<select class='statusClass api-form' data-accessory-computation-id='".$accessoryComputationId."'>".implode("",$statusArray)."</select>";
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
			
			$tableContent .= "
				<tr class='internalTrClass' data-index='".$count."'>
					<td><input type='checkbox'><br>".++$count."</td>
					<td>".$customerAlias."</td>
					<td>".$accessoryNumber."</td>
					<td>".$accessoryName."</td>
					<td>".$accessoryDescription."</td>
					<td>".$quantity."</td>
					<td>".$quantityInput."</td>
					<td>".$yonko."</td>
					".$additionalTD."
				</tr>
			";
		}
		if($submitType!='')
		{
			/*
			//~ echo "<form action = '/V3/3-4 Material Requirements Computation Software/anthony_computationSummaryConverter.php?pdf=1' method = 'POST' id = 'print' ></form>";
			//~ echo "<form action = '/V3/3-4 Material Requirements Computation Software/anthony_computationSummaryConverter.php?excel=1' method = 'POST' id = 'print' ></form>";
			echo "<form action = '/V3/54 Automated Material Computation Software/anthony_computationSummaryConverter.php?excel=1' method = 'POST' id = 'print' ></form>";
			echo "<input type = 'hidden' name = 'specId' value = '".$compressedInput."' form='print'>";
			echo "<input type = 'image' id='printId' src = '/V3/Common Data/Templates/buttons/printIcon.png' height = '35' width = '50' form='print'>";
			?><script>document.getElementById('print').submit();</script><?php
			*/
		}
		else
		{
			echo $tableContent;
		}
	}
					
?>
