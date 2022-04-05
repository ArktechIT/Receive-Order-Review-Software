<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	$reviewId = $_GET['reviewId'];
	$reviewId1 = (isset($_GET['reviewId1'])) ? $_GET['reviewId1'] : '';
	
	$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
	$queryDelete = $db->query($sql);	
	
	$lotNoArray = $poIdArray = array();
	$count = 0;
	$sql = "SELECT poId FROM ppic_roreviewdatatemp";
	$queryPoId= $db->query($sql);
	if($queryPoId->num_rows == 0)
	{
		$sql = "SELECT poId FROM ppic_roreviewdata WHERE roReviewId = ".$reviewId."";
		$queryPoId = $db->query($sql);
	}
	
	if($reviewId1!='')
	{
		$reviewId = $reviewId1;
		$sql = "SELECT poId FROM ppic_roreviewdata WHERE roReviewId = ".$reviewId."";
		$queryPoId = $db->query($sql);	
	}
	
	if($queryPoId->num_rows > 0)
	{
		while($resultPoId = $queryPoId->fetch_assoc())
		{
			$poIdArray[] = $resultPoId['poId'];
			//~ if($resultPoId['poId']==1439267) continue;
			//~ generateScheduleItems($resultPoId['poId'],'',0,0);

		}

		//~ generateScheduleItems($poIdArray,'',0,0);
		
		$sql = "SELECT lotNumber, poId, patternId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND identifier = 1 AND partLevel = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumber = $resultLotList['lotNumber'];
				$poId = $resultLotList['poId'];
				$patternId = $resultLotList['patternId'];

				if($_SESSION['idNumber']=='0346')
				{
					if($patternId=='-1')
					{
						$sql = "SELECT FROM system_finishedgoodbooking WHERE lotNumber LIKE '{$lotNumber}' LIMIT 1";
						$queryFGBooking = $db->query($sql);
						if($queryFGBooking AND $queryFGBooking->num_rows > 0)
						{
							$lotsArray = [];
							$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2)";
							$queryLot = $db->query($sql);
							if($queryLot AND $queryLot->num_rows > 0)
							{
								while($resultLot = $queryLot->fetch_assoc())
								{
									$lotsArray[] = $resultLot['lotNumber'];
								}
							}
							$sql = "DELETE FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotsArray)."') AND processOrder > 0";
							$queryDelete = $db->query($sql);
							$sql = "UPDATE ppic_workschedule SET status = 0, actualFinish = '0000-00-00', employeeId = '' WHERE lotNumber IN('".implode("','",$lotsArray)."') AND processCode IN(324,459) AND processOrder = 0 LIMIT 2";
							$queryUpdate = $db->query($sql);
						}
					}
				}
				
				$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 324 AND status = 0 LIMIT 1";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					generateScheduleItems($poId,'',0,0);
					$lotNoArray[] = $lotNumber;					
				}
			}
		}
		
		$sql = "UPDATE ppic_workschedule SET employeeIdStart = '".$_SESSION['idNumber']."', actualStart = NOW() WHERE lotNumber IN('".implode("','",$lotNoArray)."') AND processCode = 324 AND status = 0";
		$queryUpdate = $db->query($sql);
	}
	
	if($reviewId1!='')
	{
		header("location:gerald_scheduleSql.php?reviewId1=".$reviewId1."");
	}
	else
	{
		header("location:gerald_scheduleSql.php?reviewId=".$reviewId."");
	}
?>
