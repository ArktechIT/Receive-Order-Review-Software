<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors","on");
	
	$reviewId = $_GET['reviewId'];
	
	$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
	$queryDelete = $db->query($sql);	
	
	$lotNoArray = $poIdArray = array();
	$count = 0;
	$sql = "SELECT poId FROM ppic_roreviewdatatemp";
	$queryPoId= $db->query($sql);
	if($queryPoId->num_rows > 0)
	{
		while($resultPoId = $queryPoId->fetch_assoc())
		{
			generateSchedule($resultPoId['poId'],0,0);
		}
	}
	
	header("location:rose_roreviewSoftware.php?reviewId=".$reviewId."");
?>
