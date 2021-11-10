<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);
include('PHP Modules/mysqliConnection.php');
ini_set("display_errors", "on");

if(!isset($_SESSION['idNumber']))
{
	echo "Please Log-in first!";
	exit(0);
}

//~ $save = (isset($_POST['save'])) ? $_POST['save'] : "";	
$save = (isset($_GET['saveFlag'])) ? $_GET['saveFlag'] : "";	
if($save!="")
{
	// $sql = "select poId from ppic_roreviewdatatemp where matCheck=0";
	// $matCheckQuery=$db->query($sql);
	// if($matCheckQuery->num_rows > 0)
	// {
	// header("Location: rose_roreviewSoftware.php?MatError=1");
	// }

	// else
	// {

		$designReviewTF = "";
		$productionSchedulingTF = "";
		$materialBookingTF = "";
		$productionTF = "";
		$subconDeliveryTF = "";
		$receivingSubconTF = "";
		$delivery = "";
		$venue = "";
		$title = "";
		$remarks = "";
		$errorRose=0;

		$sql = "SELECT * FROM ppic_roreviewdetailstemp";
		$queryPoId= $db->query($sql);
		if($queryPoId->num_rows > 0)
		{
			$resultPoId = $queryPoId->fetch_assoc();
			$designReviewTF = $resultPoId['designReviewTF'];
			$productionSchedulingTF = $resultPoId['productionSchedulingTF'];
			$materialBookingTF = $resultPoId['materialBookingTF'];
			$productionTF = $resultPoId['productionTF'];
			$subconDeliveryTF = $resultPoId['subconDeliveryTF'];
			$receivingSubconTF = $resultPoId['receivingSubconTF'];
			$delivery = $resultPoId['delivery'];
			$venue = $resultPoId['venue'];
			$title = $resultPoId['title'];
			$participants = $resultPoId['participants'];
			$remarks = $resultPoId['remarks'];
		}
		
		$t = preg_split('/\r\n|[\r\n]/', $participants);
		for($i=0;$i<count($t);$i++) 
		{
			if(trim($t[$i])!="")
			{
				$sql = "SELECT surName FROM hr_employee where idNumber like '".trim($t[$i])."'";
				$participantNameQuery = $db->query($sql);
				if($participantNameQuery->num_rows == 0)
				{
					$errorRose=1;
					break;
				}
			}
		}
		
		if($errorRose==0)
		{	
			$sqlInsert = "INSERT INTO ppic_roreviewdetails(roReviewDate,roReviewVenue) VALUES (now(),'".$venue."')";				
			$query = $db->query($sqlInsert);
		
			$roReviewId=0;
			$sql = "SELECT roReviewId FROM ppic_roreviewdetails order by roReviewId Desc";
			$queryReviewId= $db->query($sql);
			if($queryReviewId->num_rows > 0)
			{
				$resultReviewId = $queryReviewId->fetch_assoc();
				$roReviewId = $resultReviewId['roReviewId'];
			}
		
			if($roReviewId > 0)
			{
				$employee="";	
				
				for($i=0;$i<count($t);$i++) 
				{
					$employee = trim($t[$i]);
					if(trim($employee)!="")
					{
						$sqlInsert = "INSERT INTO ppic_roreviewparticipants(roReviewId,employeeId) VALUES (".$roReviewId.",'".trim($employee)."')";				
						$query = $db->query($sqlInsert);				
					}
				}
		
				$sql = "SELECT poId,dueDate,deldate,answerDate FROM ppic_roreviewdatatemp";
				$queryPoId= $db->query($sql);
				if($queryPoId->num_rows > 0)
				{
					while($resultPoId = $queryPoId->fetch_assoc())
					{
						$poId = $resultPoId['poId'];
						$deldateRose = $resultPoId['deldate'];
						$dueDateRose = $resultPoId['dueDate'];
						$answerDate = $resultPoId['answerDate'];
						$sqlInsert = "INSERT INTO ppic_roreviewdata(roReviewId,poId,designReviewTF,productionSchedulingTF,materialBookingTF,productionTF,subconDeliveryTF,receivingSubconTF,delivery,dueDate,answerDate) VALUES (".$roReviewId.",".$poId.",'".$designReviewTF."','".$productionSchedulingTF."','".$materialBookingTF."','".$productionTF."','".$subconDeliveryTF."','".$receivingSubconTF."','".$deldateRose."','".$dueDateRose."','".$answerDate."')";				
						//exit(0);
						$query = $db->query($sqlInsert);	
				
						$partIdPO = 0;
						$lotNumberPO = "";
						$sql = "SELECT partId FROM sales_polist WHERE poId LIKE '".$poId."'";
						$queryPoIdPart = $db->query($sql);
						if($queryPoIdPart AND $queryPoIdPart->num_rows > 0)
						{
							$resultPoIdPart= $queryPoIdPart->fetch_assoc();
							$partIdPO = $resultPoIdPart['partId'];
						
							$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." and partId = ".$partIdPO." and identifier=1";
							$queryPoIdLot = $db->query($sql);
							if($queryPoIdLot AND $queryPoIdLot->num_rows > 0)
							{
								while($resultPoIdLot= $queryPoIdLot->fetch_assoc())
								{
									$lotNumberPO = $resultPoIdLot['lotNumber'];	
									
									//~ if($_SESSION['idNumber']!='0346')//Activated 2020-07-22
									//~ {
										$sqlUpdate = "UPDATE ppic_workschedule SET employeeId = '".$_SESSION['idNumber']."', actualEnd = now(), actualFinish = now(), status = 1 where lotNumber = '".$lotNumberPO."' and processCode IN(459)";
										$queryUpdate = $db->query($sqlUpdate);
									//~ }
								}
							}
						}
					}
				}
				
				$sql = "INSERT INTO ppic_proceednomaterial
								(	poId,	remarks,	date,	materialComputationId,	idNumber)
						SELECT 		poId,	remarks,	NOW(),	materialComputationId,	'".$_SESSION['idNumber']."'
						FROM		ppic_roreviewdatatemp WHERE matCheck = 1";
				$queryInsert = $db->query($sql);
				
				$materialComputationIdArray = array();
				$sql = "SELECT DISTINCT materialComputationId FROM ppic_roreviewdatatemp";
				$queryRoReviewDataTemp = $db->query($sql);
				if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
				{
					while($resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc())
					{
						$materialComputationIdArray[] = $resultRoReviewDataTemp['materialComputationId'];
					}
				}
				
				$sql = "UPDATE ppic_materialcomputation SET lotNumber = 'nolot' WHERE materialComputationId IN(".implode(",",$materialComputationIdArray).") AND lotNumber = ''";
				$queryUpdate = $db->query($sql);
				
				header("Location: gerald_schedulingComputation.php?reviewId=".$roReviewId."");
				exit(0);
			}

			$sqlDelete = "DELETE FROM ppic_roreviewdatatemp";	$queryDelete = $db->query($sqlDelete);
			$sqlDelete = "DELETE FROM ppic_roreviewdetailstemp";	$queryDelete = $db->query($sqlDelete);

			header("Location: rose_roreviewSoftware.php?reviewId=".$roReviewId."");
		}
		else
		{
			header("Location: rose_roreviewSoftware.php?View=1&error=1");
		}
	//}
}
?>
