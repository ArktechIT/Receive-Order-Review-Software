<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);    
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	
	$hideNoSchedFlag = (isset($_GET['hideNoSchedFlag'])) ? $_GET['hideNoSchedFlag'] : 0;
	$poId = (isset($_GET['poId'])) ? $_GET['poId'] : 0;
	$partialBatchIdGet = (isset($_GET['partialBatchId'])) ? $_GET['partialBatchId'] : '';
	
	$tableContent = "";
	
	$poQuantity = $partId = $receiveDate = $deliveryDate = "";
	$sql = "SELECT poQuantity, partId, receiveDate, deliveryDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
	$queryPoList = $db->query($sql);
	if($queryPoList AND $queryPoList->num_rows > 0)
	{
		$resultPoList = $queryPoList->fetch_assoc();
		$poQuantity = $resultPoList['poQuantity'];
		$partId = $resultPoList['partId'];
		$receiveDate = $resultPoList['receiveDate'];
		$deliveryDate = $resultPoList['deliveryDate'];
		
		$lotNumberArray = array();
		
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND partLevel = 1 ORDER BY partLevel";
		if($partialBatchIdGet!='')	$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND partialBatchId = ".$partialBatchIdGet." ORDER BY partLevel";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumberArray[] = $resultLotList['lotNumber'];
			}
		}
		
		$scheduleDataArray = $standardTimeDataArray = array();
		
		$sql = "SELECT lotNumber, processCode, targetFinish, standardTime FROM `system_temporaryworkschedule` WHERE `idNumber` LIKE '".$_SESSION['idNumber']."' AND poId = ".$poId." ORDER BY lotNumber, processOrder";
		$queryTemporaryWorkschedule = $db->query($sql);
		if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
		{
			while($resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc())
			{
				$lotNumber = $resultTemporaryWorkschedule['lotNumber'];
				$processCode = $resultTemporaryWorkschedule['processCode'];
				$targetFinish = $resultTemporaryWorkschedule['targetFinish'];
				$standardTime = $resultTemporaryWorkschedule['standardTime'];
				
				if(!isset($scheduleDataArray[$targetFinish])) $scheduleDataArray[$targetFinish] = array();
				
				if(!isset($scheduleDataArray[$targetFinish][$lotNumber])) $scheduleDataArray[$targetFinish][$lotNumber] = array();
				
				$processName = '';
				$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$processCode." LIMIT 1";
				$queryProcess = $db->query($sql);
				if($queryProcess AND $queryProcess->num_rows > 0)
				{
					$resultProcess = $queryProcess->fetch_assoc();
					$processName = $resultProcess['processName'];
				}
				
				$scheduleDataArray[$targetFinish][$lotNumber][] = array(
					'processName' => $processName,
					'standardTime' => $standardTime
				);
				
				if(!isset($standardTimeDataArray[$lotNumber])) $standardTimeDataArray[$lotNumber] = 0;
				
				$standardTimeDataArray[$lotNumber] += $standardTime;
				
			}
		}	
		
		$startDate = $endDate = '0000-00-00';
		$sql = "SELECT MIN(targetFinish) as startDate, MAX(targetFinish) as endDate FROM `system_temporaryworkschedule` WHERE `idNumber` LIKE '".$_SESSION['idNumber']."' AND poId = ".$poId." AND targetFinish != '0000-00-00'";
		$queryTemporaryWorkschedule = $db->query($sql);
		if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
		{
			$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
			$startDate = $resultTemporaryWorkschedule['startDate'];
			$endDate = $resultTemporaryWorkschedule['endDate'];
		}
		
		$tableContent = "";
		
		$daysCount = $holidayAndSundayCount = $leadTimeCount = 0;
		$tempDate = $receiveDate;
		while(strtotime($tempDate) <= strtotime($deliveryDate))
		{
			$tdArray = array();
			foreach($lotNumberArray as $lot)
			{
				//~ $processArray = $scheduleDataArray[$tempDate][$lot];
				$schedDataArray = $scheduleDataArray[$tempDate][$lot];
				
				$processes = "";
				if(count($schedDataArray) > 0)
				{
					foreach($schedDataArray as $valueArray)
					{
						$processName = $valueArray['processName'];
						$standardTime = $valueArray['standardTime'];
						
						$processes .= "
							<div class='row'>
								<div class='col-md-6'>".$processName."</div>
								<div class='col-md-2'>".convertSeconds($standardTime)."</div>
							</div>
						";
					}
				}
				
				//~ $processes = (count($processArray) > 0) ? implode("<br>",$processArray) : "";
				
				$tdArray[] = $processes;
			}
			
			$checkNoSched = trim(implode("",$tdArray));
			
			$remarks = '';
			$sql = "SELECT holidayName FROM hr_holiday WHERE holidayDate = '".$tempDate."' AND holidayType < 6 LIMIT 1";
			$queryHoliday = $db->query($sql);
			if($queryHoliday AND $queryHoliday->num_rows > 0)
			{
				$remarks = '(Holiday)';
				$holidayAndSundayCount++;
			}
			else
			{
				if(date('w',strtotime($tempDate))==0)
				{
					$remarks = '(Sunday)';
					$holidayAndSundayCount++;
				}
			}
			
			$bgColor = ($remarks!='') ? 'w3-pale-red' : '';
			
			$displayFlag = 1;
			
			$noSchedClass = '';
			if($checkNoSched=='')
			{
				$noSchedClass = 'noSchedClass';
				
				if($hideNoSchedFlag==1)
				{
					$displayFlag = 0;
				}
			}
			
			if($displayFlag==1)
			{
				$tableContent .= "
					<tr class='".$bgColor." ".$noSchedClass."'>
						<td>".$tempDate." ".$remarks."</td>
						<td>".implode("</td><td>",$tdArray)."</td>
					</tr>
				";
			}
			
			if(strtotime($tempDate) >= strtotime($startDate) AND strtotime($tempDate) <= strtotime($endDate))
			{
				$leadTimeCount++;
			}
			
			$tempDate = date('Y-m-d',strtotime($tempDate.'+1 days'));
			
			$daysCount++;
		}
	}
	
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php //echo displayText('10-2','utf8',0,1,1);?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetAlert2/dist/sweetalert2.css">
    <script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>    
	<style type="text/css">
		body
		{
			font-size: 11px;
			font-family: Roboto;
			background-color: whitesmoke;
		}

         #fixTop {
            position:fixed;
            width:100%;
            z-index: 1000;
		}
		
		.dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content, .dropdown-content-filter {
            display: none;
            position: absolute;
            background-color:whitesmoke;
            z-index: 9999999;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }
	</style>
</head>
<body>
<?php
	createHeader('','');
?>
<div class="container-fluid">
	<div class="row w3-padding-top" style='<?php if($partialBatchIdGet!='') echo 'display:none;';?>'>
		<div class='col-md-12'>
			<div class='row'>
<!--
				<div class='col-md-2'>
					<label>PO Quantity : </label>
					<input class='w3-input' type='number' name='poQuantity' value='<?php echo $poQuantity;?>' required form='formId'>
				</div>
				<div class='col-md-2'>
					<label>Receive Date : </label>
					<input class='w3-input' type='date' name='receiveDate' value='<?php echo $receiveDate;?>' required form='formId'>
				</div>
				<div class='col-md-2'>
					<label>Delivery Date : </label>
					<input class='w3-input' type='date' name='deliveryDate' value='<?php echo $deliveryDate;?>' required form='formId'>
				</div>
-->
				<div class='col-md-4'>
					<label class='w3-medium'>Days Count : <?php echo $daysCount;?></label><br>
					<label class='w3-medium'>Holiday/Sunday Count : <?php echo $holidayAndSundayCount;?></label><br>
					<label class='w3-medium'>Working Days : <?php echo $workingDays = $daysCount - $holidayAndSundayCount;//echo $leadTimeCount;?></label>
				</div>
			</div>
		</div>
	</div>
    <div class="row w3-padding-top">
		<div class='col-md-12'>
			<table class='table table-bordered table-condensed table-striped' id="mainTableId" style='width:100%;'>
				<thead class='w3-indigo thead'>
					<tr>
						<th>Date</th>
<!--
						<th><?php echo implode("</th><th>",$lotNumberArray);?></th>
-->
						<?php
							$highestSt = 0;
							foreach($lotNumberArray as $lotNumber)
							{
								$partialBatchId = 0;
								$partId = $identifier = '';
								$sql = "SELECT partId, identifier, partialBatchId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
								$queryLotList = $db->query($sql);
								if($queryLotList AND $queryLotList->num_rows > 0)
								{
									$resultLotList = $queryLotList->fetch_assoc();
									$partId = $resultLotList['partId'];
									$identifier = $resultLotList['identifier'];
									$partialBatchId = $resultLotList['partialBatchId'];
								}
								
								$sql = "SELECT SUM(standardTime) FROM ";
								
								if($identifier==1)
								{
									$partNumber = $revisionId = '';
									$sql = "SELECT partNumber, revisionId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
									$queryParts = $db->query($sql);
									if($queryParts AND $queryParts->num_rows > 0)
									{
										$resultParts = $queryParts->fetch_assoc();
										$partNumber = $resultParts['partNumber'];
										$revisionId = $resultParts['revisionId'];
									}
								}
								else if($identifier==2)
								{
									$partNumber = $revisionId = '';
									$sql = "SELECT accessoryNumber, accessoryName FROM cadcam_accessories WHERE accessoryId = ".$partId." LIMIT 1";
									$queryParts = $db->query($sql);
									if($queryParts AND $queryParts->num_rows > 0)
									{
										$resultParts = $queryParts->fetch_assoc();
										$partNumber = $resultParts['accessoryNumber'];
										$revisionId = $resultParts['accessoryName'];
									}
								}
								
								if($highestSt < $standardTimeDataArray[$lotNumber])
								{
									$highestSt = $standardTimeDataArray[$lotNumber];
								}
								
								$spanLot = ($partialBatchIdGet=='') ? "<span style='cursor:pointer;text-decoration:underline;background-color:whitesmoke;color:blue;' title='View Whole Assy' onclick=\" window.open('".$_SERVER['PHP_SELF']."?poId=".$poId."&partialBatchId=".$partialBatchId."', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=100, left=100, width=1200, height=600') \">".$lotNumber."</span>" : $lotNumber;
								
								echo "
									<th>
										Lot : ".$spanLot."<br>
										Part : ".$partNumber." ".$revisionId."<br>
										Total ST : ".convertSeconds($standardTimeDataArray[$lotNumber])."
									</th>
								";
							}
						?>
					</tr>
				</thead>
				<tbody class='tbody'>
					<?php echo $tableContent;?>
				</tbody>
				<tfoot class='w3-indigo tfoot'>
					<tr>
						<th></th>
						<?php
							$i = 0;
							$limit = count($lotNumberArray);
							while($i < $limit)
							{
								echo "<th></th>";
								$i++;
							}
						?>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
<?php
	//~ if($_SESSION['idNumber']=='0346')
	//~ {
		//~ echo $sql = "SELECT lotNumber, COUNT(listId) as idCount, COUNT(DISTINCT targetFinish) as targetFinishCount FROM `system_temporaryworkschedule` WHERE `idNumber` LIKE '".$_SESSION['idNumber']."' AND poId = ".$poId." AND processCode IN(496,136,312,430,431) GROUP BY lotNumber";
		//~ $queryTemporaryWorkschedule = $db->query($sql);
		//~ if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
		//~ {
			//~ while($resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc())
			//~ {
				//~ $lotNumber = $resultTemporaryWorkschedule['lotNumber'];
				//~ $idCount = $resultTemporaryWorkschedule['idCount'];
				//~ $targetFinishCount = $resultTemporaryWorkschedule['targetFinishCount'];
				
				//~ echo "<hr>".$lotNumber;
				//~ echo "<br>".$idCount." <=> ".$targetFinishCount;
			//~ }
		//~ }
	//~ }
?>			
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/stylebox.css"/>
<script>
	$(document).ready(function() {
		var dataTable = $('#mainTableId').DataTable( {
			"processing"    : false,
			"ordering"      : false,
			"searching"     : false,
			"bInfo" 		: false,
			fixedColumns:   {
					leftColumns: 1
			},
			scrollY     	: 540,
			scrollX     	: true,
			//~ scrollCollapse	: false,
			scroller    	: {
				loadingIndicator    : true
			},
			stateSave   	: false
		});		
		
		<?php 
			if($highestSt > ($workingDays * 30600))
			{
				?>
				swal({
					title: "Not enough lead time",
					allowOutsideClick: false
				}).then(function () {
					
				});
				<?php
			}
		?>		
	});	
</script>
