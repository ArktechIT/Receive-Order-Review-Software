<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('Templates/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('../54 Automated Material Computation Software/ace_finishedGoodTemporaryBooking.php');
	ini_set("display_errors", "on");
	
	if(isset($_POST['type']))
	{
		if($_POST['type']=='updatePattern')
		{
			$lot = $_POST['lot'];
			$patternId = $_POST['patternId'];

			echo $sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lot."' LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		exit(0);
	}

	// $poId = $_GET['poId'];//1481196, 1481176
	// $lotCount = 1;//1481196, 1481176
	
	$sql = "SELECT poId FROM ppic_roreviewdatatemp";
	$queryRoReviewDataTemp = $db->query($sql);
	if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
	{
		while($resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc())
		{
			$poId = $resultRoReviewDataTemp['poId'];

			$custonerAlias = $poNumber = '';
			$sql = "SELECT custonerAlias, poNumber FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				$resultPoList = $queryPoList->fetch_assoc();
				$custonerAlias = $resultPoList['custonerAlias'];
				$poNumber = $resultPoList['poNumber'];
			}

			if($_GET['country']==2 AND $custonerAlias=='GIGA-OYAMA' AND strstr($poNumber,'IPO')!==FALSE)
			{
				$finishedGoodStockFlag = 'X';
			}
			else
			{
				$finishedGoodStockFlag = finishedGoodTemporaryBooking($poId);
			}

			$lotNumberArray = array();
			$mainLotNumber = '';
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND parentLot = '' AND partLevel = 1 AND identifier = 1 LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$mainLotNumber = $resultLotList['lotNumber'];

				// $sql = "SELECT FROM ppic_workschedule WHERE lotNumber LIKE '".$mainLotNumber."' AND processCode = 459 AND status = 0 AND processRemarks ='Reviewed' LIMIT 1";
				// $queryWorkSchedule = $db->query($sql);
				// if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				// {
				// 	continue;
				// }
				
				$lotNumberArray[] = $mainLotNumber;
				$lotNoArray[] = $mainLotNumber;
				
				buildLotNumberTree($lotNumberArray,$mainLotNumber);
			}

			$lotDataArray = array();
			
			echo "
				<table border='1'>
					<tr>
						<th></th>
						<th>Part Number</th>
						<th>Part Name</th>
						<th>Lot Number</th>
						<th>Pattern</th>
					</tr>";
		
			$lotNumber = '';
			$workingQuantity = 0;
			$sql = "SELECT lotNumber, partId, identifier, partLevel, parentLot, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND lotNumber IN('".implode("','",$lotNumberArray)."') AND identifier IN(1,2) ORDER BY FIELD(lotNumber,'".implode("','",$lotNumberArray)."')";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumber = $resultLotList['lotNumber'];
					$partId = $resultLotList['partId'];
					$identifier = $resultLotList['identifier'];
					$partLevel = $resultLotList['partLevel'];
					$parentLot = $resultLotList['parentLot'];
					$patternIdLot = $resultLotList['patternId'];
					
					$partNumber = $partName = '';
					if($identifier == 1)
					{
						$sql = "SELECT partNumber, partName FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
						$queryParts = $db->query($sql);
						if($queryParts AND $queryParts->num_rows > 0)
						{
							$resultParts = $queryParts->fetch_assoc();
							$partNumber = $resultParts['partNumber'];
							$partName = $resultParts['partName'];
						}

						$patternIdArray = array();
						$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." ORDER BY patternId";
						$queryPartProcess = $db->query($sql);
						if($queryPartProcess AND $queryPartProcess->num_rows > 0)
						{
							while($resultPartProcess = $queryPartProcess->fetch_assoc())
							{
								$patternIdArray[] = $resultPartProcess['patternId'];
								
							}
						}						
					}
					else
					{
						$sql = "SELECT accessoryNumber FROM cadcam_accessories WHERE accessoryId = ".$partId." LIMIT 1";
						$queryAccessories = $db->query($sql);
						if($queryAccessories AND $queryAccessories->num_rows > 0)
						{
							$resultAccessories = $queryAccessories->fetch_assoc();
							$partNumber = $resultAccessories['accessoryNumber'];
						}

						$patternIdArray = array();
					}
					

					
					if($finishedGoodStockFlag=="O")
					{	
						$patternIdArray[] = '-1';
					}
					
					$open = (count($patternIdArray) > 1) ? 'open' : '';

					$processTable = "
						<table border='1' >
							<tr>
					";
					
					foreach($patternIdArray as $patternId)
					{
						$checked = ($patternId==$patternIdLot) ? 'checked' : '';

						$processTable .= "<td valign='top'>";
						
						$processTable .= "
						<details ".$open.">
							<summary><label title='".$process."' style='cursor:pointer;'><input class='patternClass' onclick=\" changePattern(this) \" data-lot='".$lotNumber."' type='radio' name='patternId".$lotNumber."' value='".$patternId."' ".$checked." required form='formId2'>Pattern(".$patternId.")</label></summary>
							<table border='1' class='table table-bordered table-condensed'>
								<thead class='w3-indigo thead'>
									<tr>
										<th>".displayText('L58')."</th>
										<th>".displayText('L59')."</th>
										<th>".displayText('L1121')."</th>
									</tr>
								</thead>
								<tbody>
						";
						
						
						if($patternId=='-1')
						{
							$inventoryId = $_POST['inventoryId'];
							$lotNumber = $_POST['lotNumber'];
							
							$sql = "SELECT listId FROM system_finishedgoodbooking WHERE inventoryId LIKE '".$inventoryId."' AND lotNumber LIKE '".$lotNumber."' LIMIT 1";
							$queryFinishGoodBooking = $db->query($sql);
							if($queryFinishGoodBooking AND $queryFinishGoodBooking->num_rows == 0)
							{
								$workingQuantity = 0;
								$sql = "SELECT workingQuantity FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
								$queryLotList = $db->query($sql);
								if($queryLotList AND $queryLotList->num_rows > 0)
								{
									$resultLotList = $queryLotList->fetch_assoc();
									$workingQuantity = $resultLotList['workingQuantity'];
								}
								
								$sql = "INSERT INTO `system_finishedgoodbooking`
												(	`inventoryId`,		`lotNumber`,		`bookQuantity`)
										VALUES	(	'".$inventoryId."',	'".$lotNumber."',	'".$workingQuantity."')";
								//~ $queryInsert = $db->query($sql);
							}
							
							$patId = '';
							$sql = "SELECT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode = 144 LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess->num_rows > 0)
							{
								$resultPartProcess = $queryPartProcess->fetch_array();
								$patId = $resultPartProcess['patternId'];
							}
							
							$qcProcess = ($_GET['country']==2) ? "91,163,167,256,140,137,93,92,455,344,352,364,368,413,508,510" : "91,92,93,168,197,205,220,230,238,241,242,342,343,346,163,424,173";
							
							$firstProcessOrder = '';
							$sql = "SELECT processOrder FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processCode IN(".$qcProcess.") ORDER BY processOrder DESC LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess->num_rows > 0)
							{
								$resultPartProcess = $queryPartProcess->fetch_array();
								$firstProcessOrder = $resultPartProcess['processOrder'];
							}
							
							// $processTable .= "Inventory Id : ".$inventoryId;
							
							$sql = "SELECT processCode, processSection FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processOrder >= ".$firstProcessOrder." ORDER BY processOrder";
							$processOrder = 1;
							
							if($_GET['country']==2)
							{
								$processTable .= "
									<tr>
										<td>".$processOrder."</td>
										<td>出庫 Goods Issue</td>
										<td>入庫　Warehouse (Customer Delivery)</td>
									</tr>
								";
							}
							else
							{
								$processTable .= "
									<tr>
										<td>".$processOrder."</td>
										<td>".displayText('L1773')."</td>
										<td>".displayText('7','utf8',0,1,1)."</td>
									</tr>
								";
							}
						}
						else
						{
							$sql = "SELECT processOrder, processCode, processSection FROM cadcam_partprocess WHERE partId = ".$partId." ANd patternId = ".$patternId." ORDER BY processOrder";
						}
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
							{
								$processCode = $resultWorkSchedule['processCode'];
								$processSection = $resultWorkSchedule['processSection'];
								
								if($patternId=='-1')
								{
									$processOrder++;
									if(in_array($processCode,array(163,424)))
									{
										if($_GET['country']=='1')
										{
											$processCode = 91;
											$processSection = 4;
										}
									}
								}
								else
								{
									$processOrder = $resultWorkSchedule['processOrder'];
								}
								
								// if($_GET['country']=='2' AND $patternId=='2' AND $processCode==343)
								// {
								// 	finishedGoodTemporaryBooking($_POST['poId']);
								// }
								
								$processName = '';
								$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$processCode." LIMIT 1";
								$queryProcess = $db->query($sql);
								if($queryProcess AND $queryProcess->num_rows > 0)
								{
									$resultProcess = $queryProcess->fetch_assoc();
									$processName = $resultProcess['processName'];
								}
								
								$sectionName = '';
								$sql = "SELECT sectionName FROM ppic_section WHERE sectionId = ".$processSection." LIMIT 1";
								$querySection = $db->query($sql);
								if($querySection AND $querySection->num_rows > 0)
								{
									$resultSection = $querySection->fetch_assoc();
									$sectionName = $resultSection['sectionName'];
								}
								
								$processTable .= "
									<tr>
										<td>".$processOrder."</td>
										<td>".$processName."</td>
										<td>".$sectionName."</td>
									</tr>
								";
							}
						}
						
						$processTable .= "</tbody></table></details>";
						
						$processTable .= "</td>";
					}
					
					$processTable .= "</tr></table>";		
					
					$indent = "";
					$i=0;
					while($i<$partLevel)
					{
						$i++;
						$downRightArrow = ($i > 1 AND $i==$partLevel) ? '&#8627;' : '&nbsp;';
						$indent .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$downRightArrow."&nbsp;&nbsp;";
					}
					
					$partNo = $indent."<span style='text-decoration:underline;'>".$partNumber."</span>";
		
					echo "
						<tr>
							<td>".++$count."</td>
							<td>{$partNo}</td>
							<td>{$partName}</td>
							<td>{$lotNumber}</td>
							<td>{$processTable}</td>
						</tr>
					";
				}
			}
			echo "</table>";
		}
	}
	echo "<button onclick=\" location.href='rose_roreviewSoftware.php'; \">OK</button>";
?>
<script>
	const changePattern = async (obj) => {
		const patternId = obj.value;
		const lot = obj.dataset.lot;

		let formData = new FormData();
		formData.append('type', 'updatePattern');
		formData.append('patternId', patternId);
		formData.append('lot', lot);

		const response = await fetch('<?php echo $_SERVER['PHP_SELF']?>', {
			method: 'POST',
			body: formData
		});	

		const data = await response.text();

		// console.log(data);	
	}
</script>
