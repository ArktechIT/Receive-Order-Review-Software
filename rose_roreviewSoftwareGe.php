<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/rhay_function.php');
	include('PHP Modules/rose_prodfunctions.php');
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
	//update duedate
	if(isset($_POST['submitDueDate']))
	{	
		$newDueDate =  $_POST['dueDate'];

		$sql = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$newDueDate."', changeDueDateFlag = 1";
		$process = $db->query($sql);
		
		//~ if($_SESSION['idNumber']=='0346')
		//~ {
			$sql = "SELECT poId FROM ppic_roreviewdatatemp";
			$queryRoReviewDataTemp = $db->query($sql);
			if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
			{
				while($resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc())
				{
					$poId = $resultRoReviewDataTemp['poId'];
				
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1 LIMIT 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$lotNumber = $resultLotList['lotNumber'];
						
						$sql = "SELECT actualFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 324 AND status = 1 LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
							$startDate = $resultWorkSchedule['actualFinish'];
							
							$startDate = date('Y-m-d');
							
							generateScheduleItems($poId,array('start'=>$startDate,'dueDate'=>$newDueDate,'remarksLog'=>'from RO Review'),1,0);
							
							$sql = "UPDATE system_lotlist SET recoveryDate = '".$newDueDate."' WHERE lotNumber = '".$lotNumber."'";
							$queryUpdate = $db->query($sql);
							
							$sql = "UPDATE ppic_workschedule SET recoveryDate = '".$newDueDate."' WHERE lotNumber = '".$lotNumber."'";
							$queryUpdate = $db->query($sql);							
						}
					}
				}
			}
		//~ }		
		
	}
	
	if(isset($_POST['type']) AND $_POST['type']=='modalBox2')
	{
		$partId = $_POST['partId'];
		$patternIdSelected = $_POST['patternId'];
		$finishedGoodStockFlag = $_POST['finishedGoodStockFlag'];
		
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
		
		if($finishedGoodStockFlag=="O")
		{	
			$patternIdArray[] = '-1';
		}
		
		echo "
			<table border='1' >
				<tr>
		";
		
		foreach($patternIdArray as $patternId)
		{
			echo "<td valign='top'>";
			
			echo "
				<label>Pattern(".$patternId.")</label>
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
			
			echo "</tbody></table>";
			
			echo "</td>";
		}
		
		echo "</tr></table>";
		exit(0);
	}
	else if(isset($_POST['type']) AND $_POST['type']=='modalBox')
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
	
	$errorFlag = (isset($_GET['errorFlag'])) ? $_GET['errorFlag'] : "";
	
	// ------------------------------------------- Error Mats -------------------------------
	$MatError = (isset($_GET['MatError'])) ? $_GET['MatError'] : 0;
	if($MatError==1)
	{
		echo "<script>alert('Error Material Check!!!');</script>";
	}
	// ------------------------------------------- Generate Printout -------------------------------
	$reviewId = (isset($_GET['reviewId'])) ? $_GET['reviewId'] : "";
	if($reviewId!="" AND $errorFlag=="")
	{
		echo "<script>window.open('rose_print.php?reviewId=".$reviewId."', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=500, left=500, width=400, height=400')</script>";
	}
	// ------------------------------------------- Generate Printout -------------------------------
	
	if($errorFlag=="")
	{
		$sql = "SELECT roReviewId FROM system_noprocess WHERE status = 0 LIMIT 1";
		$queryNoProcess = $db->query($sql);
		if($queryNoProcess AND $queryNoProcess->num_rows > 0)
		{
			$resultNoProcess = $queryNoProcess->fetch_assoc();
			$reviewId = $resultNoProcess['roReviewId'];
			$errorFlag = 1;
		}
	}
	
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
		if($_POST['ajax']=='updateDueDate'){ $needToupdate3="dueDate"; }
		if($_POST['ajax']=='updateNegoDate'){ $needToupdate4="negoDate"; }
		
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
		if($needToupdate3!="")
		{
			if(isset($_POST['deliveryType']))
			{
				$deliveryType = $_POST['deliveryType'];
				
				//~ $sql = "SELECT customerDeliveryDate FROM sales_polist WHERE poId = ".$_POST['poId']." LIMIT 1";
				$sql = "SELECT answerDate FROM system_lotlist WHERE poId = ".$_POST['poId']." LIMIT 1";
				$queryPoList = $db->query($sql);
				if($queryPoList AND $queryPoList->num_rows > 0)
				{
					$resultPoList = $queryPoList->fetch_assoc();
					//~ $customerDeliveryDate = $resultPoList['customerDeliveryDate'];
					$answerDate = $resultPoList['answerDate'];
					
					if($deliveryType==1)
					{
						$interval = 5;//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
					}
					else if($deliveryType==2)
					{
						$interval = 7;
					}
					else if($deliveryType==3)
					{
						$interval = 30;
					}
					
					//~ $dueDate = date("Y-m-d",strtotime($customerDeliveryDate."-".$interval." Days"));
					$dueDate = date("Y-m-d",strtotime($answerDate."-".$interval." Days"));
					
					$day =  date('l', strtotime($dueDate));

					// -------------------------- Check If Incremented / Decremented Date Is Holiday Or Sunday ----------------------
					if($_GET['country']=='1')//Philippines
					{
						$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType < 6 LIMIT 1";
					}
					else if($_GET['country']=='2')//Japan
					{
						$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType >= 6 LIMIT 1";
					}
					$dc = $db->query($sql);
					$dcnum = $dc->num_rows;
					// -------------------------- Increment / Decrement Date If Holiday Or Sunday ----------------------
					if($day=='Sunday' OR $dcnum > 0)
					{
						$dueDate = addDays(-1,$dueDate);
					}
					
					echo $dueDate;
					
					$sql = "SELECT * FROM ppic_roreviewdatatemp where poId=".$_POST['poId'];
					$queryDatas = $db->query($sql);
					if($queryDatas->num_rows > 0)
					{
						
						//~ $sqlUpdate = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$dueDate."', deldate = '".$customerDeliveryDate."', deliveryType = '".$deliveryType."' where poId=".$_POST['poId']." LIMIT 1";
						$sqlUpdate = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$dueDate."', deldate = '".$answerDate."', deliveryType = '".$deliveryType."' where poId=".$_POST['poId']." LIMIT 1";
						$queryUpdate = $db->query($sqlUpdate);					
					}
					
					//~ if($_SESSION['idNumber']=='0346')//Activated 2020-07-22
					//~ {
						$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$_POST['poId']." AND identifier = 1 AND partLevel = 1 LIMIT 1";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							$resultLotList = $queryLotList->fetch_assoc();
							$lotNumber = $resultLotList['lotNumber'];
							
							$sql = "SELECT actualFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 324 AND status = 1 LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
								$startDate = $resultWorkSchedule['actualFinish'];
								
								$startDate = date('Y-m-d');
								
								generateScheduleItems($_POST['poId'],array('start'=>$startDate,'dueDate'=>$dueDate,'remarksLog'=>'from RO Review'),1,0);
								
								$sql = "UPDATE system_lotlist SET recoveryDate = '".$dueDate."' WHERE lotNumber = '".$lotNumber."'";
								$queryUpdate = $db->query($sql);
								
								$sql = "UPDATE ppic_workschedule SET recoveryDate = '".$dueDate."' WHERE lotNumber = '".$lotNumber."'";
								$queryUpdate = $db->query($sql);
							}
						}
					//~ }
				}
			}
			else
			{
				$dueDate = $_POST['date'];
				
				$sql = "SELECT * FROM ppic_roreviewdatatemp where poId=".$_POST['poId'];
				$queryDatas = $db->query($sql);
				if($queryDatas->num_rows > 0)
				{						
					
					$sqlUpdate = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$dueDate."', changeDueDateFlag = 1 where poId=".$_POST['poId']." LIMIT 1";
					$queryUpdate = $db->query($sqlUpdate);					
				}
				
				//~ if($_SESSION['idNumber']=='0346')//Activated 2020-07-22
				//~ {
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$_POST['poId']." AND identifier = 1 AND partLevel = 1 LIMIT 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$lotNumber = $resultLotList['lotNumber'];
						
						$sql = "SELECT actualFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 324 AND status = 1 LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
							$startDate = $resultWorkSchedule['actualFinish'];
							
							$startDate = date('Y-m-d');
							
							generateScheduleItems($_POST['poId'],array('start'=>$startDate,'dueDate'=>$dueDate,'remarksLog'=>'from RO Review'),1,0);
							
							$sql = "UPDATE system_lotlist SET recoveryDate = '".$dueDate."' WHERE lotNumber = '".$lotNumber."'";
							$queryUpdate = $db->query($sql);
							
							$sql = "UPDATE ppic_workschedule SET recoveryDate = '".$dueDate."' WHERE lotNumber = '".$lotNumber."'";
							$queryUpdate = $db->query($sql);
						}
					}
				//~ }
			}
		}
		if($needToupdate4!='')
		{
			$negoDate = $_POST['date'];
			$poId = $_POST['poId'];
			
			if($negoDate!='')
			{
				$sql = "UPDATE system_lotlist SET negoDate = '".$negoDate."', bspFlag = 2 WHERE poId = ".$poId."";
				$queryUpdate = $db->query($sql);
			}
			else
			{
				$sql = "UPDATE system_lotlist SET negoDate = '".$negoDate."', bspFlag = 1 WHERE poId = ".$poId."";
				$queryUpdate = $db->query($sql);
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
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/api.css">
	<link rel="stylesheet" href="/V4/Others/Sam/css/bootstrap.css">
    <link rel="stylesheet" href="/V4/Others/Sam/aos.css">
    <link rel="stylesheet" href="/V4/Others/Sam/animate.min.css">
	<script src="/<?php echo v; ?>/Common Data/Templates/api.js"></script>
	<style>
	.animate__animated.animate__rotateOutDownLeft {
    --animate-duration: 5s;
    }
    @media only screen and (orientation:portrait) {
		#sam{
			display: none !important;
		}
        #divFull{
            display: block !important;
            position: fixed;
            top: 0px;
            left: 0px;
            width: 100%;
            height: 100%;
            z-index: 10000;
        }
    }
    @media only screen and (max-device-width: 568px) {
        #divFull{
            display: none !important;
        }
        #divFullm {
            display: block !important;
            position: fixed;
            top: 0px;
            left: 0px;
            width: 100%;
            height: 100%;
            z-index: 10000;
           
        }
    }
	@media only screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape) {
		#sam{
			display: block !important;
		}
        th, td{
            font-size: 8px !important;
        }
		#table10 tbody{			
			z-index: -999 !important;
		}
		#table11{
			width: 60vw !important;
		}
		#table11 tbody{
			height: 12vh !important;
			z-index: 1 !important;
		}
		#printz{
			height: 34vh !important;
		}
    }
	</style>
</head>
<body class='api-loading'>
<?php 
$displayId = "3-8"; # RO LIST
$version = "";
$previousLink = "/".v."/1-14%20Received%20Order%20Processing/raymond_receivedOrderProcessing.php";
createHeader($displayId, $version, $previousLink);
?>
<div id='divFullm' class="divFull text-center" style="background-color: #96D6F7; display: none">
    <img class="mx-auto animate__animated animate__backInDown" style="height: 300px; margin-top: 15%;" src="/V4/Others/Sam/img/samsam.png" alt="">
    <div class="w-100 text-center">
        <h5 class="text-muted fw-bolder">Sorry... This page is not available for mobile view.</h5>
    </div>
    <div class="w-100 text-center mt-5">
        <h5 class="text-muted fw-bolder">Redirecting to main menu...</h5>
        <h3 class="text-muted fw-bolder animate__animated animate__fadeInDown animate__infinite" id='bilang'></h3>
    </div>
</div>
<div id='divFull' class="divFull text-center" style="background-color: #96D6F7; display: none;">
    <img class="mx-auto animate__animated animate__rotateOutDownLeft animate__infinite" style="height: 300px; margin-top: 5%; overflow: auto;" src="/V4/Others/Sam/img/rotate2.png" alt="">
    <img class="mx-auto" style="height: 300px; margin-top: 32%; margin-left: -20% !important;" src="/V4/Others/Sam/img/rotate1.png" alt="">
    <div class="w-100 text-center">
        <h3 class="text-muted fw-bolder">Rotate the device for best view.</h3>
    </div>
</div>
<div id="sam">
	<div class="api-row">
		<!-- --------------------------------- Left Buttons ---------------------------------------- -->
		<div class="api-top api-col api-left-buttons" style='width:30%'>
			<!-- <button class='api-btn api-btn-home' onclick="location.href='/V3/dashboard.php';" data-api-title='<?php echo displayText('L434'); ?>'></button> HOME -->
		</div>
		<!-- ---------------------------- End Of Left Buttons -------------------------------------- -->
		
		<!-- ----------------------------- Title --------------------------------------------------- -->
		<div class="api-top api-col api-title" style='width:40%;'>
			<!-- <h2><?php echo displayText('L1025')?>V2.0</h2> -->
		</div>
		<!-- ----------------------------- End Of Title --------------------------------------------------- -->
		
		<!-- ----------------------------- Right Buttons -------------------------------------------------- -->
		<div class="api-top api-col api-right-buttons" style='width:30%'>
			<button class='w3-btn w3-round w3-blue' onclick="window.open('/<?php echo v; ?>/Section Work Schedule Graph/carlo_sectionScheduleGraph.php','BBC','left=50,screenX=20,screenY=60,resizable,scrollbars,status,width=1500,height=700'); return false;" id='schedGraph'><i class='fa fa-bar-chart'></i> <b><?php echo displayText('L3487','utf8',0,0,1); // SCHEDULE GRAPH?></b></button>&nbsp;
			<button class='api-btn api-btn-add' id='qwe' onclick="location.href='rose_roreviewList.php';" style='width:33%' data-api-title='<?php echo displayText('L2033','utf8',0,0,1); ?>'></button>	<!-- HISTORY -->
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436'); ?>'></button> <!-- REFRESH -->
		</div>
		<!-- ----------------------------- End Of Right Buttons -------------------------------------------------- -->
		
		<!-- ---------------------------- Contents --------------------------------------------------------------- -->
		<div class="api-col" style='width:100%;height:88vh;'>
						
			<!-- ----------------- Retrieve Data ----------------->			
			<?php			
			$poIdArray = array();
			$customerAliasArray = array();
			$poNumberArray = array();
			$lotNumberArray = array();
			$partNumberArray = array();
			$partNameArray = array();
			$materialSpecificationArray = array();
			$workingQuantityArray = array();
			$repeatStatusArray = array();
			$targetFinishArray = array();
			$deliveryDateArray = array();
			$subconFlagArray = array();
			$bendFlagArray = array();		
			$answerDateArray = array();		
			
			//$sql = "SELECT lotNumber, targetFinish FROM view_workschedule WHERE processCode IN(459) AND status = 0"; // edited by rose 2019-08-24
			$processsCodes = ($_GET['country']==1) ? '459,460,463' : '459,565,563';
			$sql = "SELECT lotNumber, targetFinish, processCode FROM view_workschedule WHERE processCode IN(".$processsCodes.") and lotNumber!='19-08-2408'";
			$viewWorkSechedule = $db->query($sql);
			while ($viewWorkSecheduleResult = $viewWorkSechedule->fetch_assoc())
			{
				$targetFinish = $viewWorkSecheduleResult['targetFinish'];
				$lotNumber = $viewWorkSecheduleResult['lotNumber'];
				$processCodeFilter = $viewWorkSecheduleResult['processCode'];
			
				$workingQuantity=0;
				$poId=0;
				$partId=0;				
				$sql = "SELECT poId, partId, workingQuantity, identifier FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."'";
				$lotListQuery = $db->query($sql);
				if($lotListQuery AND $lotListQuery->num_rows > 0)
				{
					$lotListQueryResult = $lotListQuery->fetch_assoc();
					$poId = $lotListQueryResult['poId'];
					$partId = $lotListQueryResult['partId'];					
					$workingQuantity = $lotListQueryResult['workingQuantity'];					 		
					$identifierData = $lotListQueryResult['identifier'];					 		
				}			 
				
				$remarks = 'N/A';				 
				$sql = "SELECT listId FROM system_hotlist WHERE poId=".$poId;
				$hotListQuery = $db->query($sql);
				if($hotListQuery->num_rows > 0)
				{
					$remarks = 'URGENT';					
				}
					
				$customerAlias = '';
				$poNumber = '';
				$deliveryDate = '';
				
				$sql = "SELECT customerId, poNumber, deliveryDate, customerDeliveryDate FROM sales_polist WHERE poId IN(".$poId.")";
				$poListQuery = $db->query($sql);
				if($poListQuery->num_rows > 0)
				{
					$poListQueryResult = $poListQuery->fetch_assoc();
					$customerId = $poListQueryResult['customerId'];		
					$poNumber = $poListQueryResult['poNumber'];		
					//~ $deliveryDate = $poListQueryResult['deliveryDate'];		
					$deliveryDate = $poListQueryResult['customerDeliveryDate'];		
					
					$sql = "SELECT customerAlias FROM sales_customer WHERE customerId IN(".$customerId.")";
					$customerAliasQuery = $db->query($sql);
					if($customerAliasQuery->num_rows > 0)
					{
						$customerAliasQueryResult = $customerAliasQuery->fetch_assoc();
						$customerAlias = $customerAliasQueryResult['customerAlias'];									
					}
				}
				
				$answerDate = '';
				$sql = "SELECT answerDate FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					$resultLotList = $queryLotList->fetch_assoc();
					$answerDate = $resultLotList['answerDate'];
				}
				
				$materialSpecification = '';
				$partNumber = '';
				$metalType = '';
				$metalThickness = '';
				$metalMaterial = '';
				$sql = "SELECT partNumber,materialSpecId, partId, partName FROM cadcam_parts WHERE partId IN(".$partId.")";
				$partsListQuery = $db->query($sql);
				if($partsListQuery->num_rows > 0)
				{
					$partsListQueryResult = $partsListQuery->fetch_assoc();
					$partNumber = $partsListQueryResult['partNumber'];
					$materialSpecId = $partsListQueryResult['materialSpecId'];
					$partIdList = $partsListQueryResult['partId'];
					$partNameList = $partsListQueryResult['partName'];
					
					//~ if(!in_array($materialSpecId,array(760,1028))) continue;
					
					$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId IN (".$materialSpecId.")";
					$materialSpecificationQuery = $db->query($sql);
					if($materialSpecificationQuery->num_rows > 0)
					{
						$materialSpecificationQueryResult = $materialSpecificationQuery->fetch_assoc();
						$materialTypeId = $materialSpecificationQueryResult['materialTypeId'];
						$metalThickness = $materialSpecificationQueryResult['metalThickness'];
						
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
						$materialTypeQuery = $db->query($sql);
						if($materialTypeQuery AND $materialTypeQuery->num_rows > 0)
						{
							$materialTypeQueryResult = $materialTypeQuery->fetch_assoc();
							$metalType = $materialTypeQueryResult['materialType'];
							$materialSpecification = trim($metalType)." ".trim($metalThickness);
						}
					}
				}
				
				//~ if($customerId!=45) continue;
				
				$repeatStatus="Repeat";
				$subconFlag="newItem";
				$bendFlag="newItem";
				$sql = "SELECT poId FROM sales_polist WHERE partId = ".$partId." and newItemFlag=1";
				$getPoIdPoList = $db->query($sql);
				if($getPoIdPoList AND $getPoIdPoList->num_rows > 0)
				{
					$repeatStatus="New";
				}
				else
				{
					$subconFlag="NO";
					$sql = "SELECT a FROM cadcam_subconlist WHERE partId IN(".$partId.")";
					$querySubcon = $db->query($sql);
					if($querySubcon->num_rows > 0)
					{
						$subconFlag="YES";									
					}
					
					$partIdArray = array();
					$sql = "SELECT DISTINCT partId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1";
					$queryLotlist = $db->query($sql);
					if($queryLotlist AND $queryLotlist->num_rows > 0)
					{
						while($resultLotlist = $queryLotlist->fetch_assoc())
						{
							$partIdArray[] = $resultLotlist['partId'];
						}
						$sql = "SELECT partId FROM cadcam_partprocess WHERE partId IN(".implode(",",$partIdArray).") AND processCode IN(145,148) LIMIT 1";
						$queryCheckSubcon = $db->query($sql);
						$subconFlag = ($queryCheckSubcon->num_rows > 0) ? 'Yes': 'No';
                    }

                    $childIdArray = Array();
                    $sql = "SELECT childId FROM cadcam_subparts WHERE parentId = ".$partId." AND identifier = 1";
                    $querySubparts = $db->query($sql);
                    if($querySubparts AND $querySubparts->num_rows > 0)
                    {
                        while($resultSubparts = $querySubparts->fetch_assoc())
                        {
                            $childIdArray[] = $resultSubparts['childId'];
                        }
                    }

                    if($childIdArray != NULL)
                    {
                        $sql = "SELECT a FROM cadcam_subconlist WHERE partId IN(".implode(", ",$childIdArray).")";
                        $querySubconCheck = $db->query($sql);
                        if($querySubconCheck AND $querySubconCheck->num_rows > 0)
                        {
                            $subconFlag="YES";							
                        }
                    }
					
					$bendFlag="NO";
					$sql = "SELECT partId FROM cadcam_partprocess WHERE partId IN(".$partId.") and  processCode>0 and processCode<25";
					$queryBend = $db->query($sql);
					if($queryBend->num_rows > 0)
					{
						$bendFlag="YES";
                    }
				}
				
				
				$docStatusNow = inspectionCall($poId,$lotNumber,1);// seen at audit of BE 2021-10-29
				if(stristr($docStatusNow, "FAI")){$repeatStatus = "New";} else{$repeatStatus = "Repeat";}
				
				$poIdArray[] = $poId;
				$customerAliasArray[] = $customerAlias;
				$poNumberArray[] = $poNumber;
				$lotNumberArray[] = $lotNumber;
				$processCodeFilterArray[] = $processCodeFilter;
				$partNumberArray[] = $partNumber;
				$materialSpecificationArray[] = $materialSpecification;
				$workingQuantityArray[] = $workingQuantity;
				$repeatStatusArray[] = $repeatStatus;
				$targetFinishArray[] = $targetFinish;
				$deliveryDateArray[] = $deliveryDate;
				$subconFlagArray[] = $subconFlag;
				$bendFlagArray[] = $bendFlag;

				$partIdListArray[] = $partIdList;	
				$partNameArray[] = $partNameList;	
				$answerDateArray[] = $answerDate;	
			}
				
				
			?>			
			<!-- ----------------- End Of Retrieve Data ---------->
						
			<!-------------------- Main Table -------------------->
			<div id="tablex" style='height: 50%; width:70%; float:left;'><!-- Adjust height if browser had a vertical scroll -->
				<form id='formId' action='rose_reviewTinyBox.php' method='POST'>
				<table id="table10" class="mytable" cellpadding="0" cellspacing="0">
					<thead>
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
							<th><?php echo displayText('L4117')?></th>
							<th><?php echo displayText('L91')?></th>
							<th><?php echo displayText('L1026')?></th>
							<th><?php echo displayText('L1120');?></th> 
							<th></th> 
							<th></th> 
							<th><?php echo displayText('L763')?></th> 
						</tr>
					</thead>
					<tbody>
						<?php
						for($i=0;$i<count($poIdArray);$i++)
						{
							$HoldRemarks="";
							$HoldRemarksColor="";
							$HoldRemarks=checkHold($lotNumberArray[$i]);
							if($HoldRemarks!="")
							{
								$HoldRemarksColor=" bgcolor=red";
							}
							$sql = "SELECT poId FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i];
							$selectedItemQuery = $db->query($sql);
							if(!$selectedItemQuery->num_rows > 0)
							{
								//ROSEMIE 2020-feb-20 START
								$roseNote="";		
								$sqlRose = "SELECT note FROM system_lotlist where lotNumber = '".$lotNumberArray[$i]."'";			
								$getSysLotlist = $db->query($sqlRose);
								if($getSysLotlist->num_rows > 0)
								{
									while($getSysLotlistResult = $getSysLotlist->fetch_assoc())
									{						
										$roseNote = $getSysLotlistResult['note']."";
									}
								}

								$withMatPo = 'No';
								$loteArray = [];
								$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poIdArray[$i]." AND identifier = 1";
								$queryLotList = $db->query($sql);
								if($queryLotList AND $queryLotList->num_rows > 0)
								{
									while($resultLotList = $queryLotList->fetch_assoc())
									{
										$loteArray[] = $resultLotList['lotNumber'];
									}
									$materialComputationIdArray = [];
									$sql = "SELECT materialComputationId FROM ppic_materialcomputationdetails WHERE lotNumber IN('".implode("','",$loteArray)."')";
									$queryMaterialComputationDetails = $db->query($sql);
									if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
									{
										while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
										{
											$materialComputationIdArray[] = $resultMaterialComputationDetails['materialComputationId'];
										}

										$purchasingLotArray = [];
										$sql = "SELECT lotNumber FROM ppic_materialcomputation WHERE materialComputationId IN(".implode(",",$materialComputationIdArray).") AND lotNumber LIKE '%-%-%' LIMIT 1";
										$queryMaterialComputation = $db->query($sql);
										if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
										{
											while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
											{
												$purchasingLotArray[] = $resultMaterialComputation['lotNumber'];
											}
										}

										$sql = "SELECT poContentId FROM purchasing_pocontents WHERE lotNumber IN('".implode("','",$purchasingLotArray)."') AND itemStatus !=2 LIMIT 1";
										$queryPoContents = $db->query($sql);
										if($queryPoContents AND $queryPoContents->num_rows > 0)
										{
											$withMatPo = 'Yes';
										}
									}
								}

								//will add pattern here
								$noDeliveryExemption = 0;
								if($_GET['country']==2 AND $customerAliasArray[$i]=='MRT')
								{
									$noDeliveryExemption = 1;
								}
								
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
									//~ $sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND (processCode = 144 OR 94)";
									$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND (processCode = 144 OR processCode = 94)";
									$queryCheckDeliveryProcess = $db->query($sql);
									if(($queryCheckDeliveryProcess AND $queryCheckDeliveryProcess->num_rows > 0) OR $noDeliveryExemption==1)
									{
										$deliveryProcessCount = $queryCheckDeliveryProcess->num_rows;
										
										$patternCount = $checkedFlag = 0;
										$patternRadio = "";
										$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." ORDER BY patternId";
										$queryPartProcess = $db->query($sql);
										if($queryPartProcess AND $queryPartProcess->num_rows > 0)
										{
											$patternCount = $queryPartProcess->num_rows;

											while($resultPartProcess = $queryPartProcess->fetch_assoc())
											{
												$checked = ($patternId==$resultPartProcess['patternId']) ? 'checked' : '';
												if($queryPartProcess->num_rows > 1)
												{
													$checked = '';
													
													//~ if($patternId == 0)	$checked = '';
													
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
												//~ if($_SESSION['idNumber']=='0346')
												if($_SESSION['idNumber']==true)
												{
													$patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox2&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&poId=".$poIdArray[$i]."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-po-id='".$poIdArray[$i]."' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId2'>Pattern(".$resultPartProcess['patternId'].")</label>";
												}
												else
												{
													$patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&poId=".$poIdArray[$i]."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-po-id='".$poIdArray[$i]."' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId2'>Pattern(".$resultPartProcess['patternId'].")</label>";
													//~ $patternRadio .= "<label style='cursor:pointer;' onclick=\" openTinyBox('','','".$_SERVER['PHP_SELF']."','','type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&'); \" title='Click to view process'><input class='patternClass' data-po-id='".$poIdArray[$i]."' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId'>Pattern(".$resultPartProcess['patternId'].")</label>";
												}												
											}
										}

										if($finishedGoodStockFlag=="O")
										{
											//~ $patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox&partId=".$partId."&patternId=-1&inventoryId=".$inventoryId."&lotNumber=".$lotNumber."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='-1' ".$checked." required form='formId'>FG</label>";
											//~ $fgCaption = "<span style='color:green;' >(FG)</span>";
											
											$patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox&partId=".$partId."&patternId=-1&poId=".$poIdArray[$i]."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='-1' ".$checked." required form='formId2'>FG</label>";
											$sql = "UPDATE ppic_lotlist SET patternId = -1 WHERE lotNumber LIKE '".$lotNumberArray[$i]."' LIMIT 1";
											$queryUpdate = $db->query($sql);
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
											if($poNumberArray[$i]=='IPO 1058')	$noDeliveryProcessFlag = 0;//by leslie 2019-08-06
											if($poNumberArray[$i]=='IPO 1070')	$noDeliveryProcessFlag = 0;//by leslie 2019-09-06
											if($poNumberArray[$i]=='IPO 1089')	$noDeliveryProcessFlag = 0;//by leslie 2019-10-17
											if($poNumberArray[$i]=='IPO 1166')	$noDeliveryProcessFlag = 0;//by leslie 2020-05-23
											if($poNumberArray[$i]=='IPO 1174')	$noDeliveryProcessFlag = 0;//by leslie 2020-06-19
											if($poNumberArray[$i]=='IPO 1180')	$noDeliveryProcessFlag = 0;//by leslie 2020-07-19
											if($poNumberArray[$i]=='IPO 1190')	$noDeliveryProcessFlag = 0;//by leslie 2020-09-18
											if($poNumberArray[$i]=='IPO 1203')	$noDeliveryProcessFlag = 0;//by leslie 2020-09-18
											if($poNumberArray[$i]=='IPO 1204')	$noDeliveryProcessFlag = 0;//by leslie 2020-12-01
											if($poNumberArray[$i]=='PHI21618')	$noDeliveryProcessFlag = 0;//by ikang 2021-06-11
											if($poNumberArray[$i]=='IPO 1267')	$noDeliveryProcessFlag = 0;//by leslie 2021-06-11
											if($poNumberArray[$i]=='IPO 1308')	$noDeliveryProcessFlag = 0;//by jane 2021-09-23
										}
									}
								}								
								

								//ROSEMIE 2020-feb-20 END
								echo "<tr class='rowCount'>";	
									if(in_array($processCodeFilterArray[$i],array(460,463)) and $_GET['country']==1)//added by rose 2019-08-24
									{
										echo "<td align='left'".$HoldRemarksColor.">".($i+1)."</td>";
									}
									else if(in_array($processCodeFilterArray[$i],array(563,565)) and $_GET['country']==2)//added by rose 2020-07-28//563,565
									{
										echo "<td align='left'".$HoldRemarksColor.">".($i+1)."</td>";
									}
									else
									{
										echo "<td align='left'".$HoldRemarksColor."><input type='checkbox' class='chkBox' name='bookingId[]' value='".$poIdArray[$i]."' />".($i+1).".</td>";
									}
									//echo "<td align='left'><input type='checkbox' class='chkBox' name='bookingId[]' value='".$poIdArray[$i]."' />".($i+1)."</td>";
									echo "<td class=''>".$customerAliasArray[$i]."</td>";
									echo "<td class='text-center'>".$poNumberArray[$i]."</td>";
									// echo "<td class='text-center'><span style='color:blue;cursor:pointer;text-decoration:underline;' onclick=\"window.open('/".v."/16 Lot Details Management Software/ace_lotDetails.php?inputLot=".$lotNumberArray[$i]."', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=100, left=100, width=1200, height=600')\" >".$lotNumberArray[$i]."</span></td>";
									echo "<td class='text-center'><span style='color:blue;cursor:pointer;text-decoration:underline;' onclick=\"window.open('/".v."/16 Lot Details Management Software V4/ace_lotDetails.php?barcode2=".$lotNumberArray[$i]."&formDoor[]=1&formDoor[]=2&formDoor[]=3', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=100, left=100, width=1200, height=600')\" >".$lotNumberArray[$i]."</span></td>";
									echo "<td class='text-center'>".$partNumberArray[$i]."</td>";
									echo "<td>".$partNameArray[$i]."</td>";
									echo "<td class='text-center'>".$materialSpecificationArray[$i]."</td>";
									echo "<td class='text-center'>".$workingQuantityArray[$i]."</td>";
									echo "<td class='text-center'>".$repeatStatusArray[$i]."</td>";
									echo "<td class='text-center'>".date('m-d',strtotime($targetFinishArray[$i]))."</td>";
									echo "<td class='text-center'>".date('m-d',strtotime($deliveryDateArray[$i]))."</td>";
									echo "<td class='text-center'>".date('m-d',strtotime($answerDateArray[$i]))."</td>";
									echo "<td class='text-center'>".$subconFlagArray[$i]."</td>";
									echo "<td class='text-center'>".$bendFlagArray[$i]."</td>";

								if(file_exists("../../Document Management System/Arktech Folder/ARK_".$partIdListArray[$i].".pdf")>0)
								{
									$pdfDrawing = "<span style='color:blue;cursor:pointer;text-decoration:underline;' onclick=\" window.open('/".v."/20 Document Management System/gerald_viewPdf.php?file=Arktech Folder/ARK_".$partIdListArray[$i].".pdf','myWindow2','left=50,screenX=20,screenY=60,resizable,scrollbars,status,width=700,height=500'); return false;\"><img src='/".v."/Common Data/Templates/images/view1.png' height='15'); \"></span>";
								}
								else
								{ 
									$pdfDrawing = ""; 
								}
								
									echo "<td>".$pdfDrawing."</td>"; 
									echo "<td>".$roseNote."</td>"; 
									echo "<td>".$withMatPo."</td>"; 
									echo "<td>".$patternRadio."</td>"; 
									
				
								echo "</tr>";
							}	
						}
						?>
					</tbody>
					<tfoot>
						<tr>								
							<th><input type='checkbox' id='chkAll' name='checkall' align="left" ></th>
							<th><input type='image' id='submitId' src="/<?php echo v; ?>/Common Data/Templates/buttons/addIcon.png" width='30' height='30' form='formId'/></th>
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
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>	
				</table>
				</form>
			</div>
			<!------------------ End Of Main Table ------------------>	
			
			<!-- transfer the code Below Print button ROSEMIE -->
			<!-------------------- Selection Table -------------------->
			<div style='height: 50%; width:80%; float:left;'><!-- Adjust height if browser had a vertical scroll -->
				<!--UPDATE ALL DUE DATE -->
				<div style="float: right;">
					<!--rhay -->
					<button id="editDueDate" onclick="editDueDateTinyBox()"><?php echo displayText('L3656'); ?></button> <!-- Edit Due Date -->
				</div>
				<form id='formId' name='inputForm2' action='rose_reviewTinyBox.php' method='POST'>
				<table id="table11" class="mytable" cellpadding="0" cellspacing="0">
					<thead>
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
							<th><?php echo displayText('L711')?></th>
							<th><?php echo displayText('L4117')?></th>
							<th><?php echo 'FG' ?></th>
							<th><?php echo displayText('L174')?></th>
							<th><?php echo displayText('L471')?></th>
							<th><?php echo displayText('L91')?></th>
							<th><?php echo displayText('L1026')?></th>
							<th><?php echo displayText('L1027')?></th>
							<th><?php echo displayText('3-6','utf8',0,1,1)?></th>
							<th><?php echo displayText('L3642');//displayText('L763')?></th>
							<th><?php echo displayText('L343');//displayText('L763')?></th>
							<th><?php echo displayText('L3690');//displayText('L763')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$checkingOfReqFlag=0;
						
						$checkingOfReqFlag2=0;
						$noDeliveryProcessFlag=0;
						
						$matCheckYesArray = $matCheckNoArray = $accessoryCheckArray = array();
						$count = 0;
						for($i=0;$i<count($poIdArray);$i++)
						{
						$checkingOfReqFlagArry[$i]=0;
							$sql = "SELECT poId, matCheck, materialComputationId, deliveryType FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i];
							$roReviewQuery = $db->query($sql);
							if($roReviewQuery->num_rows > 0)
							{
								$matCheckQueryResult = $roReviewQuery->fetch_assoc();
								$matCheck = $matCheckQueryResult['matCheck'];
								$materialComputationId = $matCheckQueryResult['materialComputationId'];
								$deliveryType = $matCheckQueryResult['deliveryType'];
								
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
									//~ if($_GET['country']==1) $finishedGoodStockFlag = 'X';//Disabled FG mam jane 2019-06-19
                                  
                                  	if(in_array($poIdArray[$i],array('1473581')))//2021-06-04 leslie
									{
										$finishedGoodStockFlag="O";
									}
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
									
									//~ if(in_array($lotNumberArray[$i],array('20-07-390','20-07-522','20-07-656')))
									//~ if(in_array($poIdArray[$i],array('1458871','1458885','1458900','1459278')))//1459278 is test
									//~ if(in_array($poIdArray[$i],array('1459887','1459344','1459345','1459346','1459347')))//2020-07-28
									//~ if(in_array($poIdArray[$i],array('1459343')))//2020-08-06
									//~ if(in_array($poIdArray[$i],array('1460416','1459853','1459855')))//2020-08-07
									//~ if(in_array($poIdArray[$i],array('1460577','1460578')))//2020-08-11
									//~ if(in_array($poIdArray[$i],array('1460575')))//2020-08-12
									//~ if(in_array($poIdArray[$i],array('1462594')))//2020-09-15
									//~ if(in_array($poIdArray[$i],array('1465384')))//2020-10-27 leslie
									//~ if(in_array($poIdArray[$i],array('1467327','1467326')))//2020-11-27 leslie
									//~ if(in_array($poIdArray[$i],array('1467456')))//2020-12-01 leslie
									//~ if(in_array($poIdArray[$i],array('1467550')))//2020-12-09 leslie
									//~ if(in_array($poIdArray[$i],array('1467294','1467315')))//2020-12-14 leslie
									//~ if(in_array($poIdArray[$i],array('1468061','1468062')))//2020-12-21 leslie
									//~ if(in_array($poIdArray[$i],array('1468242')))//2021-01-08 leslie
									//~ if(in_array($poIdArray[$i],array('1468242')))//2021-01-08 leslie
									//~ if(in_array($poIdArray[$i],array('1468547')))//2021-01-18 leslie
									//~ if(in_array($poIdArray[$i],array('1468935')))//2021-01-30 leslie
									//~ if(in_array($poIdArray[$i],array('1470161','1470268')))//2021-03-18 leslie
									//if(in_array($poIdArray[$i],array('1471781')))//2021-04-16 leslie
                                    //~ if(in_array($poIdArray[$i],array('1471790','1471789','1471791')))//2021-04-20 leslie
                                    //~ if(in_array($poIdArray[$i],array('1472222','1472223','1472224','1472225')))//2021-05-19 jane
                                    //~ if(in_array($poIdArray[$i],array('1472868','1472869','1472870','1472959','1472960','1472961')))//2021-05-20 leslie
                                    //~ if(in_array($poIdArray[$i],array('1472982','1472983','1472984','1472991','1472992','1473003','1473018','1473019','1473020','1473021','1473022','1473023')))//2021-05-27 leslie
                                    //~ if(in_array($poIdArray[$i],array('1473385')))//2021-05-28 leslie
                                    //~ if(in_array($poIdArray[$i],array('1474296','1474297','1474298','1474299','1474300','1474301','1474302','1474303','1474304','1474305','1474306','1474307')))//2021-06-11 leslie
                                    //~ if(in_array($poIdArray[$i],array('1474367')))//2021-06-11 leslie
                                    //~ if(in_array($poIdArray[$i],array('1474456','1474457','1474458','1474459','1474460')))//2021-06-16 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475308')))//2021-06-18 leslie
                                    //if(in_array($poIdArray[$i],array('1475813')))//2021-06-23 leslie                                      
                                    //~ if(in_array($poIdArray[$i],array('1475872')))//2021-06-28 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475307')))//2021-06-29 leslie
                                    //~ if(in_array($poIdArray[$i],array('1476098')))//2021-07-17 leslie
                                    // if(in_array($poIdArray[$i],array('1477779')))//2021-07-27 leslie
                                    // if(in_array($poIdArray[$i],array('1478648')))//2021-08-16 mario / roldan/ princess
                                    if(in_array($poIdArray[$i],array('1483810')))//2021-10-29 roldan
									{
										$materialStockFlag="O";
									}
									
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
									
									//~ if(in_array($poIdArray[$i],array('1459896')))//2020-08-03
									//~ if(in_array($poIdArray[$i],array('1462102')))//2020-09-02
									//~ if(in_array($poIdArray[$i],array('1462648')))//2020-09-02
									//~ if(in_array($poIdArray[$i],array('1463656','1464088','1464137','1464183')))//2020-09-19
									//~ if(in_array($poIdArray[$i],array('1465384')))//2020-10-27 leslie
									//~ if(in_array($poIdArray[$i],array('1465424')))//2020-11-03 leslie
									//~ if(in_array($poIdArray[$i],array('1466357')))//2020-11-10 leslie use different accessories co sir john
									//~ if(in_array($poIdArray[$i],array('1467378','1467380')))//2020-12-02 leslie
									//~ if(in_array($poIdArray[$i],array('1467401','1467397','1467195','1467550')))//2020-12-09 leslie
									//~ if(in_array($poIdArray[$i],array('1468242')))//2021-01-08 leslie
									//~ if(in_array($poIdArray[$i],array('1469277','1469278','1469279','1469280')))//2021-02-15 leslie
									//~ if(in_array($poIdArray[$i],array('1470460')))//2021-03-22 leslie
									//if(in_array($poIdArray[$i],array('1470798','1470806','1470807')))//2021-04-05 leslie
                                    //~ if(in_array($poIdArray[$i],array('1471790','1471789','1471791')))//2021-04-20 leslie
                                    //~ if(in_array($poIdArray[$i],array('1473037','1473038','1473039','1473040')))//2021-05-25 leslie
                                    //~ if(in_array($poIdArray[$i],array('1473927','1473928')))//2021-06-09 leslie
                                    //~ if(in_array($poIdArray[$i],array('1474367')))//2021-06-11 leslie
                                    //~ if(in_array($poIdArray[$i],array('1474456','1474457','1474458','1474459','1474460')))//2021-06-16 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475308')))//2021-06-18 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475307')))//2021-06-29 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475814','1475815')))//2021-06-30 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475816')))//2021-07-06 leslie
                                    //~ if(in_array($poIdArray[$i],array('1475817')))//2021-07-08 leslie
                                    //~ if(in_array($poIdArray[$i],array('1478141','1478142','1478489','1478490')))//2021-07-27 leslie
                                    // if(in_array($poIdArray[$i],array('1478134','1478135')))//2021-07-27 leslie
									if(in_array($poIdArray[$i],array('1483810')))//2021-10-29 roldan
									{
										$accessoryStockFlag = "O";
									}
									
									//$accessoryStockFlag = 'X';
									if($accessoryStockFlag=='X')
									{
										$accessoryStockColor = "LightPink";
										$checkingOfReqFlag=1; // rose 2018-05-24 binalik
										//~ $checkingOfReqFlagArry[$i]=1;
										
										$accessoryCheckArray[] = $poIdArray[$i];
									}
								}
								// -------------------------- No Finished Good Stocks -------------------------								
								
								$customerId = '';
								$RosecustomerDeliveryDate = '';
								$sql = "SELECT customerId,customerDeliveryDate FROM sales_polist WHERE poId = ".$poIdArray[$i]." LIMIT 1";
								$queryPoList = $db->query($sql);
								if($queryPoList AND $queryPoList->num_rows > 0)
								{
									$resultPoList = $queryPoList->fetch_assoc();
									$customerId = $resultPoList['customerId'];
									$RosecustomerDeliveryDate = $resultPoList['customerDeliveryDate'];
								}
								
								if($deliveryType==0)
								{
									$sql = "SELECT deliveryType FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
									$customerAliasQuery = $db->query($sql);
									if($customerAliasQuery->num_rows > 0)
									{
										$customerAliasQueryResult = $customerAliasQuery->fetch_assoc();
										$deliveryType = $customerAliasQueryResult['deliveryType'];									
									}
								}
								
								$interval = 0;
								$landSelected = $airSelected = $seaSelected = '';
								if($deliveryType==1)
								{
									$landSelected = "selected";
									$interval = 5;//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
									$deliveryTypeCaption = 'Land (5 Day)';
								}
								else if($deliveryType==2)
								{
									$airSelected = "selected";
									$interval = 7;
									$deliveryTypeCaption = 'Air (7 Days)';
								}
								else if($deliveryType==3)
								{
									$seaSelected = "selected";
									$interval = 30;
									$deliveryTypeCaption = 'Sea (30 Days)';
								}
								
								//~ $deliveryTypeSelect = "
									//~ <select style='width:75px;'>
										//~ <option value='1' ".$landSelected.">Land (1 Day)</option>
										//~ <option value='2' ".$airSelected.">Air (7 Days)</option>
										//~ <option value='3' ".$seaSelected.">Sea (30 Days)</option>
									//~ </select>
								//~ ";

								//rhayras
									$dueDate = '0000-00-00';
									// $sql = "SELECT dueDate FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i]." LIMIT 1";
									// $queryRoReviewDataTemp = $db->query($sql);
									// if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
									// {
										// $resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
										// $dueDate = $resultRoReviewDataTemp['dueDate'];
										// if($dueDate == "0000-00-00")
										// {
											// list($sectionChange,$leadTime,$sectionChangesArray,$processArray,$adjustment,$tempDueDate) = show_lotSchedule($lotNumberArray[$i]);
											
											// if($adjustment < 0 AND $adjustment > -5)
											// {
												// $str2 = date('Y-m-d', strtotime($adjustment.' days', strtotime($tempDueDate))); 
											// }
											// elseif($adjustment < -5)
											// {
												// $color = "red";
												// $str2 = $tempDueDate;
											// }
											// else
											// {
												// $str2 = $tempDueDate;
											// }
										// }
										// else
										// {
											// $str2 = $dueDate;
										// }
									// }
									// if($dueDate=='0000-00-00')
									// {
										// $dueDate = date("Y-m-d",strtotime($deliveryDateArray[$i]."-".$interval." Days"));
									
										// $dueDate = addDays(-1,$dueDate);
									
										// $sql = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$dueDate."', deliveryType = '".$deliveryType."' WHERE poId = ".$poIdArray[$i]." LIMIT 1";
										// $queryUpdate = $db->query($sql);
										// $str2 = $dueDate;
									// }
							//ROSE START 2019-11-15 use rhay code!!!!! if ano ang del date sa RO list un ang sususndin minus delType then color red if negative lead time //consulted by les
									$sql = "SELECT dueDate, changeDueDateFlag FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i]." LIMIT 1";
									$queryRoReviewDataTemp = $db->query($sql);
									if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
									{
										$resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
										if($resultRoReviewDataTemp['changeDueDateFlag']==0)
										{
											list($sectionChange,$leadTime,$sectionChangesArray,$processArray,$adjustment,$tempDueDate) = show_lotSchedule($lotNumberArray[$i]);	
											if($adjustment < 0 AND $adjustment > -5)
											{
												$str2 = date('Y-m-d', strtotime($adjustment.' days', strtotime($tempDueDate))); 
											}
											elseif($adjustment < -5)
											{
												$color = "red";
												$str2 = $tempDueDate;
											}
											else
											{
												$str2 = $tempDueDate;
											}
											$sql = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$str2."', deldate = '".$RosecustomerDeliveryDate."', answerDate = '".$answerDateArray[$i]."', deliveryType = '".$deliveryType."' WHERE poId = ".$poIdArray[$i]." LIMIT 1";
											//$sql = "UPDATE ppic_roreviewdatatemp SET dueDate = '".$str2."', deliveryType = '".$deliveryType."' WHERE poId = ".$poIdArray[$i]." LIMIT 1";
											$queryUpdate = $db->query($sql);
										}
										else
										{
											$str2 = $resultRoReviewDataTemp['dueDate'];
										}
									}
							//ROSE END 2019-11-15
								echo "<tr class='rowCount'>";								
									echo "<td align='left'><input type='checkbox' class='chkBox2' name='bookingId2[]' value='".$poIdArray[$i]."' />".(++$count)."</td>";
									echo "<td class=''>".$customerAliasArray[$i]."</td>";
									echo "<td class='text-center'>".$poNumberArray[$i]."</td>";
									//~ echo "<td class='text-center'>".$lotNumberArray[$i]."</td>";
									echo "<td class='text-center'><span style='color:blue;cursor:pointer;text-decoration:underline;' onclick=\"window.open('gerald_roReviewPartial.php?poId=".$poIdArray[$i]."', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=100, left=100, width=1200, height=600')\" >".$lotNumberArray[$i]."</span></td>";
									echo "<td class='text-center'>".$partNumberArray[$i]."</td>";
									echo "<td class='text-center'>".$partNameArray[$i]."</td>";
									echo "<td class='text-center'>".$materialSpecificationArray[$i]."</td>";
									echo "<td class='text-center'>".$workingQuantityArray[$i]."</td>";
									echo "<td class='text-center'>".$repeatStatusArray[$i]."</td>";									
									echo "<td class='text-center'>".date('m-d',strtotime($deliveryDateArray[$i]))."</td>";
									echo "<td class='text-center'>".date('m-d',strtotime($answerDateArray[$i]))."</td>";
									echo "<td class='text-center' bgcolor='".$finishedGoodStockColor."'><center><b><a href='/".v."/54 Automated Material Computation Software/ace_viewFinishedGoodComputation.php?inputData=".$poIdArray[$i]."'>".$finishedGoodStockFlag."</a></b></center></td>";
									if($materialStockFlag=='!')
									{
										echo "<td class='text-center' bgcolor='".$materialStockColor."'><center><b><a onclick=\"window.open('gerald_partialTemporaryBooking.php?poId=".$poIdArray[$i]."','windowForPartialBooking','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');\">".$materialStockFlag."</a></b></center></td>";
									}
									else
									{
										echo "<td class='text-center' bgcolor='".$materialStockColor."'><center><b><a href='/".v."/54 Automated Material Computation Software/ace_viewMaterialComputation.php?inputData=".$poIdArray[$i]."'>".$materialStockFlag."</a></b></center></td>";
									}
									echo "<td class='text-center' bgcolor='".$accessoryStockColor."'><center><b><a href='/".v."/54 Automated Material Computation Software/ace_viewAccessoryComputation.php?inputData=".$poIdArray[$i]."'>".$accessoryStockFlag."</a></b></center></td>";
									echo "<td class='text-center'>".$subconFlagArray[$i]."</td>";
									echo "<td class='text-center'>".$bendFlagArray[$i]."</td>";
									echo "<td width='auto' align = 'center' class='matCheckClass' data-po-id='".$poIdArray[$i]."' title='Double Click to edit'>";
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
											else if($matCheck==1)	echo displayText('L1210');
										
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
									echo "<td class='deliveryTypeClass' data-po-id='".$poIdArray[$i]."' title='Double Click to edit'>".$deliveryTypeCaption."</td>";
									echo "<td class='dueDateClass' data-po-id='".$poIdArray[$i]."' title='Double Click to edit' style='color:".$color."'>".$str2."</td>";
									
									if(in_array($customerId,array(28,37)))
									{
										$sql = "UPDATE system_lotlist SET bspFlag = 1 WHERE poId = ".$poIdArray[$i]."";
										$queryUpdate = $db->query($sql);
										
										echo "<td class='negoDateClass' data-po-id='".$poIdArray[$i]."' title='Double Click to edit'></td>";
									}
									else
									{
										echo "<td>".displayText('L161')."</td>";
									}
								echo "</tr>";
							}
						//if($checkingOfReqFlag2==0){ $checkingOfReqFlag=0; } // rose 2018-05-24 commented
						}
						?>
					</tbody>
					<tfoot>
						<tr>
							<th><input type='checkbox' id='chkAll2' name='checkall2' align="left" ></th>
							<th><input type='image' id='deleteButton' src="/<?php echo v; ?>/Common Data/Templates/buttons/deleteIcon.png" width='30' height='30' form='formId'/></th>
							<th><input type='button' onclick="location.href='gerald_roReviewNoMaterial.php'" value="<?php echo displayText('3-6','utf8',0,1,1); ?>"></th>
							<th colspan='2'><input type='button' onclick="location.href='gerald_roReviewNoAccessory.php'" value="<?php echo displayText('L1350'); ?>"></th>	
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
							<th><input type='button' onclick="location.href='/<?php echo v; ?>/54 Automated Material Computation Software/ace_viewSummarizedComputation.php'" value="<?php echo displayText('L3726'); ?>"></th> 
							<th></th>
							<th></th>
							<th></th>
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
			
			
			if(count($accessoryCheckArray) > 0)
			{
				$sql = "UPDATE ppic_roreviewdatatemp SET accessoryCheck = 1 WHERE poId IN(".implode(",",$accessoryCheckArray).")";
				$queryUpdate = $db->query($sql);
			}
			
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
			
			<div id="printz" style='height: 55%; width: 20%; float:right; border:2px solid; border-radius:25px; background-color: white; '>
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
					//$checkingOfReqFlag = 0;//Disabled 2017-12-07 gerald
					if($_GET['country']==2)	$checkingOfReqFlag = 0;//No checking if Japan
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
						$disable = "";
						if($str2 == "")
						{
							//~ $disable = "disabled";
						}
					?>
					<tr><td colspan='2' align=center><input type='submit' name='save' value='<?php echo displayText('L3717'); ?>' <?php echo $disable ?>></td></tr>				
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
</div>
</body>

<!-- -------------------------------------------TABLE FILTER JQUERY----------------------------------------------------------><!-- -------------------------------------------TABLE FILTER JQUERY---------------------------------------------------------->
<?php
	$tWidth = 'auto';
?>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/style2.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/filtergrid3.css" />
<style>.scrollableTable tbody tr:hov<?php echo v; ?>{background: linear-gradient(#DAE2EB, #C1CFDD);}</style>
<style type="text/css">/* Sortable tables */ table.mytable a.sortheader {background-color:#eee;color:#666666;font-weight: bold;text-decoration: none;display: block;}table.mytable span.sortarrow {color: black;text-decoration: none;}</style>
<style type="text/css" media="screen">div#navmenu li a#lnk02{color:#333; font-weight:bold; border-top:2px solid #ff9900;background:#fff;}</style>
<style type="text/css">.scrollableTable tbody {display: block;height:250px;width: <?php echo $tWidth;?>;overflow-y:scroll;}.scrollableTable thead {display: block;width: <?php echo $tWidth;?>;}.scrollableTable tfoot {display: block;width: <?php echo $tWidth;?>;}</style>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/tablefilter_all_min.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/sorttable.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/scroll3.js"></script>
<script>
	if($('#divFullm').css('display') == 'block')
	{
		babalik();
	}
		
	function babalik(){
		var bilang = document.getElementById('bilang');
		var counter = 6;
		var interval = setInterval(()=> {
			counter--;
			bilang.innerText =counter;
			if (counter == 0) {
				clearInterval(interval);
				location.href="/V4/1-15%20Sales%20Software/raymond_salesSoftware.php";
			}
		}, 1000);
		
	}
</script>
<script type="text/javascript">var sizeColWidths = function() {$('#table10 td, #table10 th').css('width', 'auto');$('#table10').removeClass('scrollableTable');$('#table10 tbody').css('width', 'auto');var i=0, colWidth=new Array();$('#table10 th').each(function() {colWidth[i++] = $(this).outerWidth();});$('#table10 tr').each(function() {var i=0;$('th, td', this).each(function() {$(this).css('width', colWidth[i++] + 'px');})});$('#table10').addClass('scrollableTable');$('#table10 tbody').css('width', ($('#table10 thead').width() + 20 ) +'px');};$(document).ready(function() {sizeColWidths()});$(window).resize(function() {sizeColWidths()});</script>
<script language="javascript" type="text/javascript">
	var totRowIndex = tf_Tag(tf_Id('table10'),"tr").length;
    var table10_Props = {
							filters_row_index: 1,
							alternate_rows: true,
							rows_counter: true,
							showRowHeader: true,
                            loader: true,
                            loader_text: "Filtering data...",
							col_number_format: [null,null,null,null],
                            
                            rows_always_visible: [totRowIndex],
                            col_0: 'none',col_1: 'select',col_2: 'input',col_3: 'select',col_4: 'input',col_5: 'select',col_6: 'select',col_7: 'select',col_8: 'select',col_9: 'select',col_10: 'select',col_11: 'select',col_12: 'select',col_13: 'none',col_14: 'select',col_15: 'input',col_16: 'select',
							col_width: ['100px','100px','100px','100px'],   
                            refresh_filters: true
                        };
    var tf10 = setFilterGrid( "table10",table10_Props );
</script>

<script type="text/javascript">var sizeColWidths2 = function() {$('#table11 td, #table11 th').css('width', 'auto');$('#table11').removeClass('scrollableTable');$('#table11 tbody').css('width', 'auto');var i=0, colWidth=new Array();$('#table11 th').each(function() {colWidth[i++] = $(this).outerWidth();});$('#table11 tr').each(function() {var i=0;$('th, td', this).each(function() {$(this).css('width', colWidth[i++] + 'px');})});$('#table11').addClass('scrollableTable');$('#table11 tbody').css('width', ($('#table11 thead').width() + 20 ) +'px');};$(document).ready(function() {sizeColWidths2()});$(window).resize(function() {sizeColWidths2()});</script>
<script language="javascript" type="text/javascript">
	var totRowIndex11 = tf_Tag(tf_Id('table11'),"tr").length;
	//var rosesRowses = tf_Tag(this.tbl,'tbody').length;
    var table11_Props = {                            
							refresh_filters: true,
							filters_row_index: 1,
							alternate_rows: true,
							rows_counter: true,
							showRowHeader: true,
                            loader: true,
                            loader_text: "Filtering data...",
							col_number_format: [null,null,null,null],
                            
                            rows_always_visible: [totRowIndex11],
                            col_0: 'none',col_1: 'select',col_2: 'select',col_3: 'select',col_4: 'input',col_5: 'select',col_6: 'select',col_7: 'select',col_8: 'select',col_9: 'select',col_10: 'select',col_11: 'select',col_12: 'select',col_13: 'select',col_14: 'select',col_15: 'select',
                            col_16: 'select',col_17: 'select',col_18: 'none',col_19: 'none',col_20: 'none',
							col_width: ['100px','100px','100px','100px']
                            
                        };
    var tf11 = setFilterGrid( "table11",table11_Props );
</script>

<!--
<script src="/Common Data/Templates/jquery.js"></script>
-->
<script src="/<?php echo v; ?>/Common Data/Templates/api.jquery.js"></script>
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
			
			$("#submitId").click(function(){
				if ($('input.chkBox').is(":checked") )
				{
					var chk = 'input.chkBox:visible[checked="checked"]:not(:disabled)';
					var chkSize = $(chk).size();
					var chkVal = $(chk).map(function(){
						return $(this).val();
					}).get();
					//alert("ri"+chkVal);
					TINY.box.show({url:'rose_reviewTinyBox.php',post:'workScheduleId='+chkVal+'&action=Add',width:'150px',height:'70px',opacity:10,topsplit:6,animate:false,close:true})
				}
				return false;
			});
				
			$("#deleteButton").click(function(){
				if ($('input.chkBox2').is(":checked") )
				{
					var chk = 'input.chkBox2:visible[checked="checked"]:not(:disabled)';
					var chkSize = $(chk).size();
					var chkVal = $(chk).map(function(){
						return $(this).val();
					}).get();
					//alert("ri"+chkVal);
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
			
			$("td.deliveryTypeClass").dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				if(thisVal.trim()!='')
				{
					var poId = $(this).data('poId');
					var oldVal = $(this).text();
					
					var selectedLand, selectedAir, selectedSea;
					if(oldVal=='Land (5 Day)') 		selectedLand = 'selected';//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
					else if(oldVal=='Air (7 Days)')	selectedAir = 'selected';
					else if(oldVal=='Sea (30 Days)')	selectedSea = 'selected';
					thisObj.text('');
					var select = $("<select id='deliveryTypeSelectId' name='deliveryType' onchange='changeDeliveryType(this,"+poId+")'><option value='1' "+selectedLand+">Land (1 Day)</option><option value='2' "+selectedAir+">Air (7 Days)</option><option value='3' "+selectedSea+">Sea (30 Days)</option></select>");
					select.appendTo(thisObj);
					$("#deliveryTypeSelectId").focus();
					$("#deliveryTypeSelectId").blur(function(){
						var newVal = $(this).val();
						var newValue = '';
						if(newVal=='1') 		newValue = 'Land (5 Day)';//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
						else if(newVal=='2')	newValue = 'Air (7 Days)';
						else if(newVal=='3')	newValue = 'Sea (30 Days)';
						thisObj.text(newValue);	
					});
				}
			});
			
			$("td.dueDateClass").dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				if(thisVal.trim()!='')
				{
					var poId = $(this).data('poId');
					var oldVal = $(this).text();
					
					thisObj.text('');
					var select = $("<input id='dueDateId' type='date' name='dueDate' onchange='changeDueDate(this,"+poId+")' value='"+oldVal+"' style='background-color:yellow;'>");
					select.appendTo(thisObj);
					$("#dueDateId").focus();
					$("#dueDateId").blur(function(){
						var newVal = $(this).val();
						thisObj.text(newVal);	
					});
				}
			});
			
			$("td.negoDateClass").dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				//~ if(thisVal.trim()!='')
				//~ {
					var poId = $(this).data('poId');
					var oldVal = $(this).text();
					
					thisObj.text('');
					var select = $("<input id='negoDateId' type='date' name='negoDate' onchange='changeNegoDate(this,"+poId+")' value='"+oldVal+"' style='background-color:yellow;'>");
					select.appendTo(thisObj);
					$("#negoDateId").focus();
					$("#negoDateId").blur(function(){
						var newVal = $(this).val();
						thisObj.text(newVal);	
					});
				//~ }
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
							console.log(data);
						}
					}
				});
			});			
			
			$("#formId2").submit(function(){
				location.href='rose_finalizeReview.php?saveFlag=1';
				return false;
			});
			
			
			<?php
				if($errorFlag==1)
				{
					//~ if($_SESSION['idNumber']=='0346')
					//~ {
						
					//~ }
					
					?>
					TINY.box.show({html:'<center><h1 style="color:red;">Some items have no process due to unexpected problem, please ask assistance to IT Department<br><br>RO Review Id : <?php echo $reviewId;?></h1></center>',width:'1000',height:'500',opacity:10,topsplit:6,animate:false,close:true})
					<?php
				}
			?>
			
			$('body').removeClass('api-loading');
			$(window).bind('beforeunload',function(){
				$('body').addClass('api-loading');
			});			
		});
		
function editDueDateTinyBox()
{
	TINY.box.show({url:'rhay_updateDueDate.php',width:'300',height:'100',opacity:10,topsplit:6,animate:false,close:true})
}
			
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
function changeDueDate(obj,poId)	{
	var val = obj.value; 
	
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"POST",
		data:{
			ajax:'updateDueDate',
			date:val,
			poId:poId
		},
		success : function(data){
			//~ alert(data);
			//~ console.log(data);
			}
	});
}
function changeNegoDate(obj,poId)	{
	var val = obj.value; 
	
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"POST",
		data:{
			ajax:'updateNegoDate',
			date:val,
			poId:poId
		},
		success : function(data){
			}
	});
}
function changeDeliveryType(obj,poId)	{
	var val = obj.value; 
	
	$.ajax({
		url:"<?php echo $_SERVER['PHP_SELF'];?>",
		type:"POST",
		data:{
			ajax:'updateDueDate',
			deliveryType:val,
			poId:poId
		},
		success : function(data){
			$("td.dueDateClass[data-po-id='"+poId+"']").text(data);
		}
	});
}
</script>

<!-- -----------------------------------START SMALL BOX--------------------------------------------------------------->
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny B<?php echo v; ?>/stylebox.css"/>
<style>
	#box{display:inline-block;padding: 20px}
</style>
<script type="text/javascript">
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
