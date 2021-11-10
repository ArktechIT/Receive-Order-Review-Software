<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('../54 Automated Material Computation Software/ace_materialTemporaryBooking.php');
	include('../54 Automated Material Computation Software/ace_accessoryTemporaryBooking.php');
	include('../54 Automated Material Computation Software/ace_finishedGoodTemporaryBooking.php');
	ini_set("display_errors","on");
	
	//~ if($_SESSION['idNumber']!='0346')
	//~ {
		//~ echo "Sorry we're under maintenance!";
		//~ exit(0);
	//~ }

	$customerFilter = isset($_POST['customerFilter']) ? $_POST['customerFilter'] : "";
	
	if(isset($_POST['type']) AND $_POST['type']=='modalBox')
	{
		$partId = $_POST['partId'];
		$patternId = $_POST['patternId'];
		
		echo "
			<table border='1'>
				<tr>
					<th>".displayText('L58')."</th>
					<th>".displayText('L59')."</th>
					<th>".displayText('L1121')."</th>
				</tr>
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
			
			echo "Inventory Id : ".$inventoryId;
			
			$sql = "SELECT processCode, processSection FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processOrder >= ".$firstProcessOrder." ORDER BY processOrder";
			$processOrder = 1;
			
			if($_GET['country']==2)
			{
				echo "
					<tr>
						<td>".$processOrder."</td>
						<td>出庫 Goods Issue</td>
						<td>入庫　Warehouse (Customer Delivery)</td>
					</tr>
				";
			}
			else
			{
				echo "
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
				
				if($_GET['country']=='2' AND $patternId=='2' AND $processCode==343)
				{
					finishedGoodTemporaryBooking($_POST['poId']);
					
					/*
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
						$queryInsert = $db->query($sql);
					}*/
				}
				
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
				
				echo "
					<tr>
						<td>".$processOrder."</td>
						<td>".$processName."</td>
						<td>".$sectionName."</td>
					</tr>
				";
			}
		}
		
		echo "</table>";
		exit(0);
	}
	
	// ------------------------------------------- Error Mats -------------------------------
	$MatError = (isset($_GET['MatError'])) ? $_GET['MatError'] : 0;
	if($MatError==1)
	{
		echo "<script>alert('Error Material Check!!!');</script>";
	}
	// ------------------------------------------- Generate Printout -------------------------------
	$reviewId = (isset($_GET['reviewId'])) ? $_GET['reviewId'] : "";
	if($reviewId!="")
	{
		echo "<script>window.open('rose_print.php?reviewId=".$reviewId."', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=500, left=500, width=400, height=400')</script>";
	}
	// ------------------------------------------- Generate Printout -------------------------------
	
	// ------------------------------------------- Insert Review Data Into Temporary Table -------------------------------
	if(isset($_POST['ajax']))
	{	
		$date = "";
		$error=0;
		$needToupdate = "";
		$needToupdate2 = "";
		$date = (isset($_POST['date'])) ? $_POST['date'] : "";
		$remarks = (isset($_POST['remarks'])) ? $_POST['remarks'] : "";
	
		if($_POST['ajax']=='updatedesignReviewTF'){ $needToupdate="designReviewTF"; }
		if($_POST['ajax']=='updateproductionSchedulingTF'){ $needToupdate="productionSchedulingTF"; }
		if($_POST['ajax']=='updatematerialBookingTF'){ $needToupdate="materialBookingTF"; }
		if($_POST['ajax']=='updateproductionTF'){ $needToupdate="productionTF"; }
		if($_POST['ajax']=='updatesubconDeliveryTF'){ $needToupdate="subconDeliveryTF"; }
		if($_POST['ajax']=='updatereceivingSubconTF'){ $needToupdate="receivingSubconTF"; }
		if($_POST['ajax']=='updatedeliveryTF'){ $needToupdate="delivery"; }
		if($_POST['ajax']=='updatevenue'){ $needToupdate="venue"; }
		if($_POST['ajax']=='updatetitle'){ $needToupdate="title"; }
		if($_POST['ajax']=='updateparticipants'){ $needToupdate="participants"; }
		if($_POST['ajax']=='updateremarks'){ $needToupdate="remarks"; }
		if($_POST['ajax']=='updatematcheck'){ $needToupdate2="matcheck"; }
		
		if($needToupdate!="")
		{
			$sql = "SELECT * FROM ppic_roreviewdetailstemp";
			$queryDates = $db->query($sql);
			if($queryDates->num_rows > 0)
			{						
				$sqlUpdate = "UPDATE ppic_roreviewdetailstemp SET ".$needToupdate." = '".trim($date)."' LIMIT 1";
				$queryUpdate = $db->query($sqlUpdate);					
			}
			else
			{
				$sqlInsert = "INSERT INTO ppic_roreviewdetailstemp(".$needToupdate.") VALUES ('".trim($date)."')";		
				$queryUpdate = $db->query($sqlInsert);					
			}
		}
		if($needToupdate2!="")
		{
			$roseValueMat=explode("_",$date);
			$sql = "SELECT * FROM ppic_roreviewdatatemp where poId=".$roseValueMat[0];
			$queryDatas = $db->query($sql);
			if($queryDatas->num_rows > 0)
			{						
				
				$sqlUpdate = "UPDATE ppic_roreviewdatatemp SET matCheck = ".trim($roseValueMat[1]).", remarks = '".$remarks."' where poId=".$roseValueMat[0]." LIMIT 1";
				$queryUpdate = $db->query($sqlUpdate);					
			}
		}
		exit(0);
	}
	
	// ------------------------------------------- End Of Insert Review Data Into Temporary Table ------------------------
	
	function createFilterInput($sqlFilter,$column,$value)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$return = "<option value=''>".displayText('L490')." </option>";
		
		$sql = "SELECT DISTINCT ".$column." FROM cadcam_materialspecs ".$sqlFilter." ORDER BY ".$column."";
		if($column=='materialType' OR $column=='baseWeight' OR $column=='coatingWeight')
		{
			$materialTypeIdArray = array();
			$sql = "SELECT DISTINCT materialTypeId FROM cadcam_materialspecs ".$sqlFilter."";
			$query = $db->query($sql);
			if($query->num_rows > 0)
			{
				while($result = $query->fetch_array())
				{
					$materialTypeIdArray[] = $result['materialTypeId'];
				}
			}
			
			if(count($materialTypeIdArray) > 0)
			{
				$sql = "SELECT DISTINCT ".$column." FROM engineering_materialtype WHERE materialTypeId IN(".implode(",",$materialTypeIdArray).") ORDER BY ".$column."";
			}
		}
		
		$query = $db->query($sql);
		if($query->num_rows > 0)
		{
			while($result = $query->fetch_array())
			{
				$valueCaption = $result[$column];
				
				$selected = ($value==$result[$column]) ? 'selected' : '';
				
				$return .= "<option value='".$result[$column]."' ".$selected.">".$valueCaption."</option>";
			}
		}		
		return $return;
	}
	
	$materialSpecId = (isset($_POST['materialSpecId'])) ? $_POST['materialSpecId'] : '';
	$materialType = (isset($_POST['materialType'])) ? $_POST['materialType'] : '';
	$metalThickness = (isset($_POST['metalThickness'])) ? $_POST['metalThickness'] : '';
	$baseWeight = (isset($_POST['baseWeight'])) ? $_POST['baseWeight'] : '';
	$coatingWeight = (isset($_POST['coatingWeight'])) ? $_POST['coatingWeight'] : '';
	
	$sqlFilter = "";
	$sqlFilterArray = array();
	
	if($materialSpecId!='')	$sqlFilterArray[] = "materialSpecId = ".$materialSpecId."";
	if($materialType!='')	$sqlFilterArray[] = "materialTypeId IN(SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '".$materialType."')";
	if($metalThickness!='')	$sqlFilterArray[] = "metalThickness = ".$metalThickness."";
	if($baseWeight!='')		$sqlFilterArray[] = "materialTypeId IN(SELECT materialTypeId FROM engineering_materialtype WHERE baseWeight = ".$baseWeight."')";
	if($coatingWeight!='')	$sqlFilterArray[] = "materialTypeId IN(SELECT materialTypeId FROM engineering_materialtype WHERE coatingWeight = ".$coatingWeight."')";
	
	
	$sqlFilter = "";
	if(count($sqlFilterArray) > 0)
	{
		$sqlFilter = "WHERE ".implode(" AND ",$sqlFilterArray)." ";
	}

	# DP
	$sqlFilterData = "";
	// if($customerFilter != '') $sqlFilterData .= " AND id IN (".$customerFilter.")";
	# DP END
	
	$sql = "SELECT materialSpecId FROM cadcam_materialspecs ".$sqlFilter;
	$queryMaterialSpecs = $db->query($sql);
	$totalRecords = ($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0) ? $queryMaterialSpecs->num_rows : 0;

?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Received Order Review V2.0</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/V3/Common Data/Templates/api.css">
	<script src="/V3/Common Data/Templates/api.js"></script>
    <link rel="stylesheet" href="../Common Data/Templates/Bootstrap/w3css/w3.css">
	<link rel="stylesheet" href="../Common Data/Templates/Bootstrap/Bootstrap 3.3.7/css/bootstrap.css">
	<link rel="stylesheet" href="../Common Data/Templates/Bootstrap/Font Awesome/css/font-awesome.css">
	<link rel="stylesheet" href="../Common Data/Templates/Bootstrap/Bootstrap 3.3.7/Roboto Font/roboto.css">	
	<link rel="stylesheet" type="text/css" href="../Common Data/Libraries/Javascript/Super Quick Table/datatables.min.css">
	<link rel="stylesheet" href="../Common Data/Templates/api.css">



</head>
<body class='api-loading'>
	<div class="api-row">
		<!-- --------------------------------- Left Buttons ---------------------------------------- -->
		<div class="api-top api-col api-left-buttons" style='width:30%'>
			<button class='api-btn api-btn-home' onclick="location.href='/V3/dashboard.php';" data-api-title='HOME'></button>
		</div>
		<!-- ---------------------------- End Of Left Buttons -------------------------------------- -->
		
		<!-- ----------------------------- Title --------------------------------------------------- -->
		<div class="api-top api-col api-title" style='width:40%;'>
			<h2><?php echo displayText('L1025')?>V2.0</h2>
		</div>
		<!-- ----------------------------- End Of Title --------------------------------------------------- -->
		
		<!-- ----------------------------- Right Buttons -------------------------------------------------- -->
		<div class="api-top api-col api-right-buttons" style='width:30%'>
			<div class="dropdown-filter">
				<button class="api-btn" data-api-title='FILTERS' <?php //echo toolTip('L435');?>></button>
				<input id="dropdown-filter" type="hidden" value="0">
				<div class="dropdown-content-filter">
				
				</div>
			</div>
			<button class='api-btn api-btn-add' id='qwe' onclick="location.href='rose_roreviewList.php';" style='width:33%' data-api-title='HISTORY'></button>
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='REFRESH'></button>
		</div>
		<!-- ----------------------------- End Of Right Buttons -------------------------------------------------- -->
		
		<!-- ---------------------------- Contents --------------------------------------------------------------- -->
		<div class="api-col" style='width:100%;height:88vh;'>
						
			<!-- ----------------- Retrieve Data ----------------->			
						
			<!-- ----------------- End Of Retrieve Data ---------->
						
			<!-------------------- Main Table -------------------->
			<div style='height: 50%; width:70%; float:left;'><!-- Adjust height if browser had a vertical scroll -->
				<form id='formFilter' action='rose_roreviewSoftwareTest.php' method='POST'></form>
				<form id='formId' action='rose_reviewTinyBox.php' method='POST'></form>
				<table id="mainTableId" style="font-size:12px;" class="table table-bordered table-striped table-condensed" cellpadding="0" cellspacing="0">
					<thead class="w3-indigo" style="white-space: nowrap;">
						<tr>
							<th></th>
							<th><?php echo displayText('L24')?></th>
							<th><?php echo displayText('L224')?></th>
							<th><?php echo displayText('L45')?></th>
							<th><?php echo displayText('L28')?></th>
							<th><?php echo displayText('L30');?></th>
							<th><?php echo displayText('L174')?></th>
							
							<th><?php echo displayText('L31')?></th>
							<th><?php echo displayText('L172')?></th>
							<th><?php echo displayText('L62')?></th>
							<th><?php echo displayText('L711')?></th>
							<th><?php echo displayText('L91')?></th>
							<th><?php echo displayText('L1026')?></th>
							<th><?php echo displayText('L1120');?></th> 
						</tr>
					</thead>
					<tbody>
						
					</tbody>
					<tfoot class='w3-indigo'>
						<tr>								
							<th><input type='checkbox' id='chkAll' name='checkall' align="left" ></th>
							<th><input type='image' id='submitId' src="/V3/Common Data/Templates/buttons/addIcon.png" width='15' height='15' form=''/></th>
							<th></th>
							<th></th>
							<th></th>	
							<th></th>
							<th></th>
							<th></th>
							<th></th>		
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>	
				</table>
			</div>
			<!------------------ End Of Main Table ------------------>	
			
			<!-- transfer the code Below Print button ROSEMIE -->
			
			<!-------------------- Selection Table -------------------->
			<div style='height: 50%; width:70%; float:left;'><!-- Adjust height if browser had a vertical scroll -->
				<form id='formId' name='inputForm2' action='rose_reviewTinyBox.php' method='POST'>
				<table id="mainTableId2" style="font-size:12px;" class="table table-bordered table-striped table-condensed" cellpadding="0" cellspacing="0">
					<thead class="w3-indigo" style="white-space: nowrap;">
						<tr>
							<th></th>
							<th><?php echo displayText('L24')?></th>
							<th><?php echo displayText('L224')?></th>
							<th><?php echo displayText('L45')?></th>
							<th><?php echo displayText('L28')?></th>
							<th><?php echo displayText('L174')?></th>
							<th><?php echo displayText('L31')?></th>
							<th><?php echo displayText('L172')?></th>
							<th><?php echo displayText('L711')?></th>
							<th><?php echo 'FG' ?></th>
							<th><?php echo displayText('L174')?></th>
							<th><?php echo displayText('L471')?></th>
							<th><?php echo displayText('L91')?></th>
							<th><?php echo displayText('L1026')?></th>
							<th><?php echo displayText('L1027')?></th>
							<th><?php echo displayText('3-6','utf8',0,1,1)?></th>
							<th><?php echo displayText('L763')?></th>
						</tr>
					</thead>
					<tbody>
					<?php
						$checkingOfReqFlag=0;
						
						$checkingOfReqFlag2=0;
						$noDeliveryProcessFlag=0;
						
						$matCheckYesArray = $matCheckNoArray = array();
						
						for($i=0;$i<count($poIdArray);$i++)
						{
						$checkingOfReqFlagArry[$i]=0;
							$sql = "SELECT poId, matCheck, materialComputationId FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i];
							$roReviewQuery = $db->query($sql);
							if($roReviewQuery->num_rows > 0)
							{
								$matCheckQueryResult = $roReviewQuery->fetch_assoc();
								$matCheck = $matCheckQueryResult['matCheck'];
								$materialComputationId = $matCheckQueryResult['materialComputationId'];
								
								$materialComputationStatus = ($matCheck==1) ? 'Not Finish' : 'N/A';
								if($materialComputationId > 0)
								{
									$materialComputationStatus = 'Finish';
									$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE materialComputationId = ".$materialComputationId." AND status = 0 LIMIT 1";
									$queryMaterialComputation = $db->query($sql);
									if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
									{
										$materialComputationStatus = 'Not Finish';
									}
								}
								
								if($_GET['country']==2 AND $customerAliasArray[$i]=='GIGA-OYAMA' AND strstr($poNumberArray[$i],'IPO')!==FALSE)
								{
									$finishedGoodStockColor = "LightPink";
									$finishedGoodStockFlag = 'X';
								}
								else
								{
									$finishedGoodStockColor = "LightGreen";
									$finishedGoodStockFlag = finishedGoodTemporaryBooking($poIdArray[$i]);
									if($finishedGoodStockFlag=='X')
									{
										$finishedGoodStockColor = "LightPink";
										//$checkingOfReqFlag=1;
										//$checkingOfReqFlagArry[$i]=1;
									}
								}
								
								$materialStockColor = "LightGreen";
								$accessoryStockColor = "LightGreen";
								// -------------------------- With Finished Good Stocks -------------------------
								if($finishedGoodStockFlag=="O")
								{
									$materialStockFlag = "N/A";
									$accessoryStockFlag = "N/A";
								}
								// -------------------------- With Finished Good Stocks -------------------------
								// -------------------------- No Finished Good Stocks -------------------------
								else
								{
									$materialStockFlag = materialTemporaryBooking($poIdArray[$i]);
									//$materialStockFlag = 'X';
									
									if($materialStockFlag=='X')
									{
										$materialStockColor = "LightPink";
										$checkingOfReqFlag=1;
										$checkingOfReqFlagArry[$i]=1;
									}
									else if($materialStockFlag=='!')
									{
										$materialStockColor = "Orange";
										$checkingOfReqFlag=1;
										$checkingOfReqFlagArry[$i]=1;
									}
									
									$accessoryStockFlag = accessoryTemporaryBooking($poIdArray[$i]);
									//$accessoryStockFlag = 'X';
									if($accessoryStockFlag=='X')
									{
										$accessoryStockColor = "LightPink";
										$checkingOfReqFlag=1; // rose 2018-05-24 binalik
										//~ $checkingOfReqFlagArry[$i]=1;
									}
								}
								// -------------------------- No Finished Good Stocks -------------------------								
								
								$patternRadio = "N/A";
								$partId = $patternId = 0;
								$sql = "SELECT partId, patternId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumberArray[$i]."' AND identifier = 1 LIMIT 1";
								$queryLotList = $db->query($sql);
								if($queryLotList AND $queryLotList->num_rows > 0)
								{
									$resultLotList = $queryLotList->fetch_assoc();
									$partId = $resultLotList['partId'];
									$patternId = $resultLotList['patternId'];
									
									$deliveryProcessCount = 0;
									$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND (processCode = 144 OR 94)";
									$queryCheckDeliveryProcess = $db->query($sql);
									if($queryCheckDeliveryProcess AND $queryCheckDeliveryProcess->num_rows > 0)
									{
										$deliveryProcessCount = $queryCheckDeliveryProcess->num_rows;
										
										$checkedFlag = 0;
										$patternRadio = "";
										$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." ORDER BY patternId";
										$queryPartProcess = $db->query($sql);
										if($queryPartProcess AND $queryPartProcess->num_rows > 0)
										{
											while($resultPartProcess = $queryPartProcess->fetch_assoc())
											{
												$checked = ($patternId==$resultPartProcess['patternId']) ? 'checked' : '';
												if($queryPartProcess->num_rows > 1)
												{
													$checked = '';
													
													if($deliveryProcessCount == 1)
													{
														$sql = "SELECT partId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$resultPartProcess['patternId']." AND processCode = 144 LIMIT 1";
														$queryCheckDeliveryProcess = $db->query($sql);
														if($queryCheckDeliveryProcess->num_rows > 0 AND $checkedFlag==0)
														{
															$checked = 'checked';
															$checkedFlag = 1;
															$patternId = $resultPartProcess['patternId'];
															$sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumberArray[$i]."' LIMIT 1";
															$queryUpdate = $db->query($sql);
														}
													}
												}
												$patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&poId=".$poIdArray[$i]."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId2'>Pattern(".$resultPartProcess['patternId'].")</label>";
												//~ $patternRadio .= "<label style='cursor:pointer;' onclick=\" openTinyBox('','','".$_SERVER['PHP_SELF']."','','type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&'); \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId'>Pattern(".$resultPartProcess['patternId'].")</label>";
											}
										}
									}
									else
									{
										$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode = 353";
										$queryCheckDeliveryProcess = $db->query($sql);
										if($queryCheckDeliveryProcess AND $queryCheckDeliveryProcess->num_rows == 0)
										{
											$patternRadio = "No Delivery Process";
											$noDeliveryProcessFlag = 1;
										}
									}
								}
															
								echo "<tr class='rowCount'>";								
									echo "<td align='left'><input type='checkbox' class='chkBox2' name='bookingId2[]' value='".$poIdArray[$i]."' /></td>";
									echo "<td class=''>".$customerAliasArray[$i]."</td>";
									echo "<td class='text-center'>".$poNumberArray[$i]."</td>";
									echo "<td class='text-center'>".$lotNumberArray[$i]."</td>";
									echo "<td class='text-center'>".$partNumberArray[$i]."</td>";
									echo "<td class='text-center'>".$materialSpecificationArray[$i]."</td>";
									echo "<td class='text-center'>".$workingQuantityArray[$i]."</td>";
									echo "<td class='text-center'>".$repeatStatusArray[$i]."</td>";									
									echo "<td class='text-center'>".date('m-d',strtotime($deliveryDateArray[$i]))."</td>";
									echo "<td class='text-center' bgcolor='".$finishedGoodStockColor."'><center><b><a href='/V3/54 Automated Material Computation Software/ace_viewFinishedGoodComputation.php?inputData=".$poIdArray[$i]."'>".$finishedGoodStockFlag."</a></b></center></td>";
									echo "<td class='text-center' bgcolor='".$materialStockColor."'><center><b><a href='/V3/54 Automated Material Computation Software/ace_viewMaterialComputation.php?inputData=".$poIdArray[$i]."'>".$materialStockFlag."</a></b></center></td>";
									echo "<td class='text-center' bgcolor='".$accessoryStockColor."'><center><b><a href='/V3/54 Automated Material Computation Software/ace_viewAccessoryComputation.php?inputData=".$poIdArray[$i]."'>".$accessoryStockFlag."</a></b></center></td>";
									echo "<td class='text-center'>".$subconFlagArray[$i]."</td>";
									echo "<td class='text-center'>".$bendFlagArray[$i]."</td>";
									echo "<td width='auto' align = 'center' class='matCheckClass' data-po-id='".$poIdArray[$i]."' title='Dobule Click to edit'>";
										///* 
										//if($checkingOfReqFlagArry[$i]==1 and $_SESSION['idNumber']=='0276')
										if($checkingOfReqFlagArry[$i]==1)
										{
											//~ echo "<select name='matCheck' onchange='showmatcheck(this)'>";
												//~ $sql = "select matCheck from ppic_roreviewdatatemp where poId=".$poIdArray[$i];
												//~ $matCheckQuery=$db->query($sql);
												//~ $matCheckQueryResult = $matCheckQuery->fetch_assoc();											
												//~ echo "<option value = ".$poIdArray[$i]."_0"; if($matCheckQueryResult['matCheck']==0){ echo " selected"; } echo ">No</option>";
												//~ echo "<option value = ".$poIdArray[$i]."_1"; if($matCheckQueryResult['matCheck']==1){ echo " selected"; } echo ">".displayText('L1210')."</option>";											
											//~ echo "</select>";
										
											//~ $sql = "select matCheck from ppic_roreviewdatatemp where poId=".$poIdArray[$i];
											//~ $matCheckQuery=$db->query($sql);
											//~ if($matCheckQuery AND $matCheckQuery->num_rows > 0)
											//~ {
												//~ $matCheckQueryResult = $matCheckQuery->fetch_assoc();
												//~ if($matCheckQueryResult['matCheck']==0)	echo "No";
												//~ else if($matCheckQueryResult['matCheck']==1)	echo "displayText('L1210')";
											
												//~ if($matCheckQueryResult['matCheck']==0){ $checkingOfReqFlag2=1; }
											//~ }
											
											if($matCheck==0)	echo "No";
											else if($matCheck==1)	echo "displayText('L1210')";
										
											if($matCheck==0){ $checkingOfReqFlag2=1; }
											
											if($matCheck==0)	$matCheckYesArray[] = $poIdArray[$i];
										}
										// */
										else
										{
											echo "<input type = 'hidden' name = 'matCheck' value = '".$poIdArray[$i]."_1'>";
											
											if($matCheck==1)	$matCheckNoArray[] = $poIdArray[$i];
										}
									echo "</td>";
									echo "<td class='text-center'>".$materialComputationStatus."</td>";
									echo "<td class='text-center'>".$patternRadio."</td>";
								echo "</tr>";
							}
						//if($checkingOfReqFlag2==0){ $checkingOfReqFlag=0; } // rose 2018-05-24 commented
						}
					?>
					</tbody>
					<tfoot class="w3-indigo">
						<tr>
							<th><input type='checkbox' id='chkAll2' name='checkall2' align="left" ></th>								
							<th><input type='image' id='deleteButton' src="/V3/Common Data/Templates/buttons/deleteIcon.png" width='15' height='15' form='formId'/></th>
							<th><input type='button' onclick="location.href='gerald_roReviewNoMaterial.php'" value="Mat'l Computation"></th>
							<th></th>	
							<th></th>	
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>		
							<th></th>		
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th><input type='button' onclick="location.href='/V3/54 Automated Material Computation Software/ace_viewSummarizedComputation.php'" value="Requirement List"></th>
						</tr>
					</tfoot>	
				</table>
				</form>
			</div>
			<!------------------ End Of Selection Table ------------------>
			
			<!--transfer code here Print button ROSEMIE -->
			<!-- --------------- Input Form ------------------------->
			
			<?php
			$sqlCheck = "SELECT poId FROM ppic_roreviewdatatemp";
			$queryCheck = $db->query($sqlCheck);	
			if($queryCheck->num_rows==0){ $disableFields="disabled"; }
			else{  $disableFields=""; }
			
			$designReviewTF="";
			$productionSchedulingTF="";
			$materialBookingTF="";
			$productionTF="";
			$subconDeliveryTF="";
			$receivingSubconTF="";
			$deliveryTF="";
			$venue="";
			$title="";
			$remarks="";
			$participants="";
				$sql = "SELECT * FROM ppic_roreviewdetailstemp";
				$queryDates = $db->query($sql);
				if($queryDates->num_rows > 0)
				{
					$resultDates = $queryDates->fetch_assoc();
					if($resultDates['designReviewTF']!="0000-00-00" or $resultDates['designReviewTF']!=""){ $designReviewTF = $resultDates['designReviewTF']; }
					if($resultDates['productionSchedulingTF']!="0000-00-00" or $resultDates['productionSchedulingTF']!=""){ $productionSchedulingTF = $resultDates['productionSchedulingTF']; }
					if($resultDates['materialBookingTF']!="0000-00-00" or $resultDates['materialBookingTF']!=""){ $materialBookingTF = $resultDates['materialBookingTF']; }
					if($resultDates['productionTF']!="0000-00-00" or $resultDates['productionTF']!=""){ $productionTF = $resultDates['productionTF']; }
					if($resultDates['subconDeliveryTF']!="0000-00-00" or $resultDates['subconDeliveryTF']!=""){ $subconDeliveryTF = $resultDates['subconDeliveryTF']; }
					if($resultDates['receivingSubconTF']!="0000-00-00" or $resultDates['receivingSubconTF']!=""){ $receivingSubconTF = $resultDates['receivingSubconTF']; }
					if($resultDates['delivery']!="0000-00-00" or $resultDates['delivery']!=""){ $deliveryTF = $resultDates['delivery']; }
					if($resultDates['venue']!=""){ $venue = $resultDates['venue']; }
					if($resultDates['title']!=""){ $title = $resultDates['title']; }
					if($resultDates['remarks']!=""){ $remarks = $resultDates['remarks']; }
					if($resultDates['participants']!=""){ $participants = $resultDates['participants']; }				
				}
				
			// Gerald Code 2017-10-02
			$sql = "UPDATE ppic_roreviewdatatemp SET matCheck = 1 WHERE poId IN(".implode(",",$matCheckYesArray).")";
			$queryUpdate = $db->query($sql);
			
			$sql = "UPDATE ppic_roreviewdatatemp SET matCheck = 0 WHERE poId IN(".implode(",",$matCheckNoArray).")";
			$queryUpdate = $db->query($sql);
			// Gerald Code 2017-10-02
			
			
			// Gerald Code 2017-09-19	
			$materialComputationFlag = 0;
			$sql = "SELECT materialComputationId FROM ppic_roreviewdatatemp WHERE matCheck = 1 AND materialComputationId = 0 LIMIT 1";
			$queryRoReviewDataTemp = $db->query($sql);
			if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
			{
				$materialComputationFlag = 1;
			}
			else
			{
				$materialComputationIdArray = array();
				$sql = "SELECT DISTINCT materialComputationId FROM ppic_roreviewdatatemp WHERE matCheck = 1";
				$queryRoReviewDataTemp = $db->query($sql);
				if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
				{
					if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
					{
						while($resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc())
						{
							$materialComputationIdArray[] = $resultRoReviewDataTemp['materialComputationId'];
						}
					}
				}
				
				$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE materialComputationId IN(".implode(",",$materialComputationIdArray).") AND status = 0 LIMIT 1";
				$queryMaterialComputation = $db->query($sql);
				if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
				{
					$materialComputationFlag = 1;
				}
			}
			// Gerald Code 2017-09-19	
				
			?>
			
			<div style='height: 55%; width: 25%; float:right; border:2px solid; border-radius:25px; background-color: white; '>
				<form id='formId2' name='inputForm' action='rose_finalizeReview.php' method='POST'>
				<table border = 0>	
				</br>
					<!-- comment 2017-09-25 sabi ni ace by rose-->
					<!--
					<tr><td><?php echo displayText('L1028')?></td><td><input type='date' value='<?php echo $designReviewTF; ?>' required <?php echo $disableFields; ?>  onchange="showdesignReviewTF(this)"></td></tr>
					<tr><td><?php echo displayText('L1029')?></td><td><input type='date' name='productionSchedulingTF' value='<?php echo $productionSchedulingTF; ?>' required <?php echo $disableFields; ?>  onchange="showproductionSchedulingTF(this)"></td></tr>
					<tr><td><?php echo displayText('L1030')?></td><td><input type='date' name='materialBookingTF' value='<?php echo $materialBookingTF; ?>' required <?php echo $disableFields; ?>  onchange="showmaterialBookingTF(this)"></td></tr>
					<tr><td><?php echo displayText('L1031')?></td><td><input type='date' name='productionTF' value='<?php echo $productionTF; ?>' required <?php echo $disableFields; ?>  onchange="showproductionTF(this)"></td></tr>
					<tr><td><?php echo displayText('L1032')?></td><td><input type='date' name='subconDeliveryTF' value='<?php echo $subconDeliveryTF; ?>' <?php echo $disableFields; ?>  onchange="showsubconDeliveryTF(this)"></td></tr>
					<tr><td><?php echo displayText('L1033')?></td><td><input type='date' name='receivingSubconTF' value='<?php echo $receivingSubconTF; ?>' <?php echo $disableFields; ?>  onchange="showreceivingSubconTF(this)"></td></tr>
					<tr><td><?php echo displayText('L1034')?></td><td><input type='date' name='deliveryTF' value='<?php echo $deliveryTF; ?>' required <?php echo $disableFields; ?>  onchange="showdeliveryTF(this)"></td></tr>
					-->
					<!--
					<tr><td colspan=2 align=center><input type='checkbox' name='PrevDataOn' <?php echo $disableFields; ?>/> Use Previous Data</td></tr>
					-->
					<tr><td><?php echo displayText('L1035')?></td><td><input type='text' name='venue' value='<?php echo $venue; ?>' required <?php echo $disableFields; ?>  onchange="showvenue(this)"></td></tr>
					
					<tr><td><?php echo displayText('L1036')?></td><td><textarea name="participants" rows="4" cols="20" <?php echo $disableFields; ?> onchange="showparticipants(this)" required><?php echo $participants; ?></textarea></td></tr>				
					<?php
					if($errorALARM!="")
					{
						echo "<tr><td colspan=2><font color=red size=5>Employee Id Mismatch</font></td></tr>";
						echo "<tr><td colspan=2><font color=red size=5>Please check and try again.</font></td></tr>";
					}
					?>
					<!--
					<tr><td>Notes/Remarks</td><td><textarea name="remarks" rows="2" cols="20" <?php echo $disableFields; ?> onchange="showremarks(this)"><?php echo $remarks; ?></textarea></td></tr>
					-->
					<?php
					$materialComputationFlag = 0;//Disabled 2017-11-17 gerald
					$checkingOfReqFlag = 0;//Disabled 2017-12-07 gerald
					if($checkingOfReqFlag==1)
					{
					?>
					<tr><td colspan='2' align=center><font color=red>Cannot submit, Error Material or Accessory Requirements!!!</font></td></tr>	
					<?php
					}
					else if($materialComputationFlag==1)
					{
					?>
					<tr><td colspan='2' align=center><font color=red>Cannot submit, Please complete Material Computation!!!</font></td></tr>	
					<?php
					}
					else if($noDeliveryProcessFlag==1)
					{
					?>
					<tr><td colspan='2' align=center><font color=red>Cannot submit, Some Item has no Delivery Process!!!</font></td></tr>
					<?php
					}
					else
					{
					?>
					<tr><td colspan='2' align=center><input type='submit' name='save' value='Print RO Review'></td></tr>				
					<?php
					}
					?>
				</table>
				</form>
			</div>
			<!-- --------------- End Of Input Form ------------------>
			
		</div>
		<!-- ---------------------------- Contents --------------------------------------------------------------- -->
	</div>
</body>

<!-- -------------------------------------------TABLE FILTER JQUERY----------------------------------------------------------><!-- -------------------------------------------TABLE FILTER JQUERY---------------------------------------------------------->
<?php
	$tWidth = 'auto';
?>

<script src="../Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js"></script>
<script src="../Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui.js"></script>
<script src="../Common Data/Libraries/Javascript/jQuery 3.1.1/bootstrap.min.js"></script>
<script src="../Common Data/Libraries/Javascript/Super Quick Table/datatables.min.js"></script>
<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/Tiny Box/stylebox.css"/>
<!--
<script src="/Common Data/Templates/jquery.js"></script>
-->
<script src="/V3/Common Data/Templates/api.jquery.js"></script>
<script>
		$(function(){
			$('#chkAll').change(function(e) {
				$('input.chkBox').attr('checked', false);
				$('input.chkBox').parent().parent().css('background', '');
				$('input.chkBox:visible:not(:disabled)').attr('checked', this.checked);
				if($('input.chkBox:visible:not(:disabled)').is(':checked'))
				{
					$('input.chkBox:visible:not(:disabled)').parent().parent().css('background', 'linear-gradient(#DAE2EB, #C1CFDD)');
				}
				else
				{
					$('input.chkBox:visible:not(:disabled)').parent().parent().css('background', '');
				}
			});
			
			$('#chkAll2').change(function(e) {
				$('input.chkBox2').attr('checked', false);
				$('input.chkBox2').parent().parent().css('background', '');
				$('input.chkBox2:visible:not(:disabled)').attr('checked', this.checked);
				if($('input.chkBox2:visible:not(:disabled)').is(':checked'))
				{
					$('input.chkBox2:visible:not(:disabled)').parent().parent().css('background', 'linear-gradient(#DAE2EB, #C1CFDD)');
				}
				else
				{
					$('input.chkBox2:visible:not(:disabled)').parent().parent().css('background', '');
				}
			});		
			
			$("#submitId").click(function(e){
				if ($('input.chkBox').is(":checked") )
				{
					// var chk = 'input.chkBox:visible[checked="checked"]:not(:disabled)';
					// var chkSize = $(chk).size();
					// var chkVal = $(chk).map(function(){
					// 	return $(this).val();
					// }).get();

					var chkVal = [];
					$.each($("input[id='chk[]']:checked"), function(){            
						chkVal.push($(this).val());
					});

					// alert("ri"+chkVal);
					TINY.box.show({url:'rose_reviewTinyBox.php',post:'workScheduleId='+chkVal+'&action=Add',width:'150px',height:'70px',opacity:10,topsplit:6,animate:false,close:true})
				}
				// return false;
			});
				
			$("#deleteButton").click(function(){
				if ($('input.chkBox2').is(":checked") )
				{
					// var chk = 'input.chkBox2:visible[checked="checked"]:not(:disabled)';
					// var chkSize = $(chk).size();
					// var chkVal = $(chk).map(function(){
					// 	return $(this).val();
					// }).get();
					//alert("ri"+chkVal);

					var chkVal = [];
					$.each($("input[id='chk2[]']:checked"), function(){            
						chkVal.push($(this).val());
					});
					TINY.box.show({url:'rose_reviewTinyBox.php',post:'workScheduleId='+chkVal+'&action=Delete',width:'150px',height:'70px',opacity:10,topsplit:6,animate:false,close:true})
				}
				return false;
			});
			
			$("td.matCheckClass").dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				if(thisVal.trim()!='')
				{
					var poId = $(this).data('poId');
					var oldVal = $(this).text();
					
					var selectedYes, selectedNo;
					if(oldVal=='Yes') 		selectedYes = 'selected';
					else if(oldVal=='No')	selectedNo = 'selected';
					thisObj.text('');
					var select = $("<select id='selectId' name='matCheck' onchange='showmatcheck(this)'><option value='"+poId+"_0' "+selectedNo+">No</option><option value='"+poId+"_1' "+selectedYes+">Yes</option></select>");
					select.appendTo(thisObj);
					$("#selectId").focus();
					$("#selectId").blur(function(){
						var newVal = $(this).val();
						var newValue = (newVal==poId+"_0") ? 'No' : 'Yes';
						thisObj.text(newValue);	
					});
				}
			});
			
			$("input.patternClass").change(function(){
				var lot = $(this).attr('data-lot');
				var thisVal = $(this).val();
				//~ var checkBoxLot = $("input.toggleChild[data-lot="+lot+"]");
				
				if(thisVal!=-1)
				{
					var patSelector = "input.patternClass[data-parentlot="+lot+"]:visible";
					$(patSelector+"[value='"+thisVal+"']").prop('checked',true);
					var lotNoArray = $(patSelector).map(function(){
						return $(this).attr('data-lot');
					}).get();
					lotNoArray.push(lot);
				}
				else
				{
					var lotNoArray = [lot];
				}
				
				$.ajax({
					url:'gerald_scheduleSql.php',
					type:'post',
					data:{
						ajaxType:'updatePattern',
						patternId:thisVal,
						lotNoArray:lotNoArray
					},
					success:function(data){
						if(data.trim()!='')
						{
							alert(data);
						}
					}
				});
			});			
			
			$('body').removeClass('api-loading');
			$(window).bind('beforeunload',function(){
				$('body').addClass('api-loading');
			});			
		});
		
			
function showdesignReviewTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatedesignReviewTF',date:val}, success : function(data){}}); }
function showproductionSchedulingTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updateproductionSchedulingTF',date:val}, success : function(data){}}); }
function showmaterialBookingTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatematerialBookingTF',date:val}, success : function(data){}}); }
function showproductionTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updateproductionTF',date:val}, success : function(data){}}); }
function showsubconDeliveryTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatesubconDeliveryTF',date:val}, success : function(data){}}); }
function showreceivingSubconTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatereceivingSubconTF',date:val}, success : function(data){}}); }
function showdeliveryTF(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatedeliveryTF',date:val}, success : function(data){}}); }
function showvenue(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatevenue',date:val}, success : function(data){}}); }
function showtitle(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updatetitle',date:val}, success : function(data){}}); }
function showparticipants(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updateparticipants',date:val}, success : function(data){}}); }
function showremarks(str){ var val = str.value; $.ajax({ url:"<?php echo $_SERVER['PHP_SELF'];?>", type:"POST", data:{ ajax:'updateremarks',date:val}, success : function(data){}}); }

function showmatcheck(str)	{
	var val = str.value; 
	//~ alert("test"+val);
	
	var promptValue = '';
	/*
  	if(val.indexOf("_1") != -1)
	{
		promptValue = prompt("Please input remarks");
		//~ alert(promptValue);
	}
	*/
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"POST",
		data:{
			ajax:'updatematcheck',
			date:val,
			remarks:promptValue
		},
		success : function(data){
			}
	});
}
</script>

<!-- -----------------------------------START SMALL BOX--------------------------------------------------------------->

<style>
	#box{display:inline-block;padding: 20px}
</style>
<script type="text/javascript">

$(function(){
	var sqlFilter = "<?php echo $sqlFilter; ?>";
	var dataTable2 = $('#mainTableId2').DataTable( {
		"searching"    : false,
		"processing"    : true,
		"ordering"      : false,
		"serverSide"    : true,
		"bInfo" : false,
		"ajax":{
			url     :"rose_roreviewSoftwareAJAX2.php", // json datasource
			type    : "post",  // method  , by default get
			data    : {
						"sqlFilter"     : sqlFilter
					  },
			error: function(){  // error handling
				$(".mainTableId2-error").html("");
				$("#mainTableId2").append('<tbody class="mainTableId2-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
				$("#mainTableId2_processing").css("display","none");
				
			}
		},
		"columnDefs": [
					{ 
						"width"     : "2%",
						"targets"   : 0
					},
					{ 
						"width"     : "10%",
						"targets"   : 13
					}
		],
		fixedColumns: true,
		deferRender: true,
		scrollX     : true,
		scrollY     : 240,
		scroller    : {
			loadingIndicator    : true
		},
		stateSave   : false
	});
	console.log(dataTable2);


	//~ var sqlFilter = "<?php echo str_replace("\n"," ",$sqlFilter); ?>";
	var sqlFilter = "<?php echo $sqlFilter; ?>";
	var sqlFilterData = "<?php echo $sqlFilterData; ?>";
	// alert(sqlFilterData);
	var dataTable = $('#mainTableId').DataTable( {
		"searching"    : false,
		"processing"    : true,
		"ordering"      : false,
		"serverSide"    : true,
		"bInfo" : false,
		"ajax":{
			url     :"rose_roreviewSoftwareAJAX.php", // json datasource
			type    : "post",  // method  , by default get
			data    : {
						"sqlFilterData"     : sqlFilterData,
						"sqlFilter"     	: sqlFilter
						},
			error: function(){  // error handling
				$(".mainTableId-error").html("");
				$("#mainTableId").append('<tbody class="mainTableId-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
				$("#mainTableId_processing").css("display","none");
				
			}
		},
		"columnDefs": [
					{ 
						"width"     : "2%",
						"targets"   : 0
					},
					{ 
						"width"     : "10%",
						"targets"   : 13
					}
		],
		fixedColumns: true,
		deferRender: true,
		scrollX     : true,
		scrollY     : 210,
		scroller    : {
			loadingIndicator    : true
		},
		stateSave   : false
	});
	
	$(".dropdown-filter").on('click', function (event) {
		localStorage.clear();
		var filterDataPost = "<?php echo str_replace('"',"'",json_encode($_POST));?>";
		var filterDataGet = "<?php echo str_replace('"',"'",json_encode($_GET));?>";
		
		TINY.box.show({
						url:'princess_filterDatav2.php',
						post:'filterDataPost='+filterDataPost+'&filterDataGet='+filterDataGet+'&sqlFilter='+sqlFilter,
						width:'1200',
						height:'',
						boxid:'frameless',
						maskid:'bluemask',
						opacity:10,
						topsplit:10,
						fixed:true,
						animate:false,
						close:false,
						openjs:function(){openJSCustom()}
					});	
	});        

	$("#employeeAdd").on('click', function (event) {
		window.location.href = "paul_employeeInformationAdd.php"
	}); 

	$("#exportAll").click(function(){
		window.location.href = 'princess_importAllActive.php';
	}); 

	$("#printId").click(function(){
		if ($('input.cheboxArray').is(":checked") )
		{
			var chkVal = [];
			$.each($("input[name='cheboxArray[]']:checked"), function(){            
				chkVal.push($(this).val());
			});

			//TINY.box.show({url:'rose_idprint.php',post:'employeeId='+chkVal,width:350,height:90,opacity:10,topsplit:6,animate:false,close:true,closejs:function(){closeJS()}})
			window.location.href = "rose_idprint.php?employeeId="+chkVal;
		}
		
		if ($('#checkAll').is(":checked"))
		{
			TINY.box.show({url:'rose_idprint.php?print=active',width:350,height:90,opacity:10,topsplit:6,animate:false,close:true,closejs:function(){closeJS()}})
		}
		
		if ($('#checkAll').is(":checked") && $('#inactive').is(":checked"))
		{
			TINY.box.show({url:'rose_idprint.php?print=includeInactive',width:350,height:90,opacity:10,topsplit:6,animate:false,close:true,closejs:function(){closeJS()}})
		}
		return false;			
	});  

	$("#checkAll").change(function(){
		if($(this).is(":checked"))
		{
			$(".cheboxArray").prop('checked', true);
		}
		else
		{
			$(".cheboxArray").prop('checked', false);
		}
	}); 
});



function openTinyBox(w,h,url,html,post,iframe,left,top)
{
	TINY.box.show({
		url:url,width:w,height:h,post:post,opacity:50,topsplit:6,animate:false,close:true,iframe:iframe,left:left,top:top,html:html,
		boxid:'box',
		openjs:function(){
			<?php unset($_GET['date']);?>
		}
	});
}	
</script>
<!-- -----------------------------------END SMALL BOX----------------------------------------------------------------> 

</html>

