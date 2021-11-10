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

if(isset($_POST['submitName']) AND $_POST['action'] == 'Add')
{
	$workScheduleId2 = (isset($_POST['workScheduleId2'])) ? $_POST['workScheduleId2'] : "";
	$workScheduleIdArray2 = explode(",",$workScheduleId2);		
	foreach($workScheduleIdArray2 as $val2)
	{
		//~ $sql = "INSERT INTO ppic_roreviewdatatemp(poId) VALUES (".$val2.")";
		$sql = "INSERT INTO ppic_roreviewdatatemp(poId,matCheck) VALUES (".$val2.",1)";
		$insertQuery = $db->query($sql);

		$sql = "INSERT INTO ppic_roreviewdatatemptest(poId,matCheck) VALUES (".$val2.",1)";
		$insertQuery = $db->query($sql);
	}
	
	//~ header('location:rose_roreviewSoftwareTest.php');
	header('location:rose_roreviewSoftware.php');
}
else if(isset($_POST['submitName']) AND $_POST['action'] == 'Delete')
{
	$workScheduleId2 = (isset($_POST['workScheduleId2'])) ? $_POST['workScheduleId2'] : "";
	$workScheduleIdArray2 = explode(",",$workScheduleId2);
	foreach($workScheduleIdArray2 as $val2)
	{
		$sql = "DELETE FROM ppic_roreviewdatatemp WHERE poId = ".$val2;
		$deleteQuery = $db->query($sql);
		
		// -------------------------------------- Unbook Material and Finished Goods Temporary Booking --------------------------------------------------------
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$val2." AND identifier = 1";
		$unbookinglotListQuery = $db->query($sql);
		while($unbookinglotListQueryResult = $unbookinglotListQuery->fetch_assoc())
		{
			/* Gerald 2018-10-18 
			$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$unbookinglotListQueryResult['lotNumber']."'";
			$bookingDetailsQuery = $db->query($sql);
			while($bookingDetailsQueryResult = $bookingDetailsQuery->fetch_assoc())
			{
				//~ $sql = "SELECT COUNT(lotNumber) FROM engineering_bookingdetails WHERE bookingId = ".$bookingDetailsQueryResult['bookingId'];//2018-06-14 Gerald
				$sql = "SELECT lotNumber FROM engineering_bookingdetails WHERE bookingId = ".$bookingDetailsQueryResult['bookingId'];
				$bookingCountQuery = $db->query($sql);
				if($bookingCountQuery->num_rows == 1)
				{
					$sql = "DELETE FROM engineering_booking WHERE bookingId = ".$bookingDetailsQueryResult['bookingId'];
					//echo $sql."<br>";
					$deleteQuery = $db->query($sql);
				}				
				
				$sql = "DELETE FROM engineering_bookingdetails WHERE lotNumber LIKE '".$unbookinglotListQueryResult['lotNumber']."'";
				//echo $sql."<br>";
				$deleteQuery = $db->query($sql);
			}
			*/
			
			$sql = "DELETE FROM system_finishedgoodbooking WHERE lotNumber LIKE '".$unbookinglotListQueryResult['lotNumber']."'";
			//echo $sql."<br>";
			$deleteQuery = $db->query($sql);
		}
		// -------------------------------------- End Of Unbook Material and Finished Goods Temporary Booking -------------------------------------------------
		
		// -------------------------------------- Unbook Accessory Booking -------------------------------------------------
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$val2." AND identifier = 2";
		$unbookinglotListQuery = $db->query($sql);
		while($unbookinglotListQueryResult = $unbookinglotListQuery->fetch_assoc())
		{
			$sql = "DELETE FROM system_accessorybooking WHERE lotNumber LIKE '".$unbookinglotListQueryResult['lotNumber']."'";
			//echo $sql."<br>";
			$deleteQuery = $db->query($sql);
		}
		// -------------------------------------- End Of Unbook Accessory Booking -------------------------------------------------
	}
	
	//~ header('location:rose_roreviewSoftwareTest.php');
	header('location:rose_roreviewSoftware.php');
}
else
{
	$workScheduleId = (isset($_POST['workScheduleId'])) ? $_POST['workScheduleId'] : "";
	echo "<input type='hidden' value='".$workScheduleId."' name='workScheduleId2' form='machineForm'>";
	echo "<input type='hidden' value='".$_POST['action']."' name='action' form='machineForm'>";
?>
	<table>
	<tr>
		<td colspan=2>
			<center><font color=red>Are You Sure You Want To <?php echo $_POST['action']; ?>?</font></center>
		</td>
	</tr>
	<tr>		
		<td>			
			<form action='<?php echo $_SERVER['PHP_SELF'];?>' method='post' id='machineForm'>			
				<center><input type='submit' name='submitName' value='Confirm' form='machineForm' class="button"></center>
			</form>	
		</td>
	</tr>	
<?php
}
?>
