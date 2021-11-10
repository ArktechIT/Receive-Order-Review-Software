<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	
	class AutomaticROReview
	{
		function __construct()
		{
			include($_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/PHP Modules/mysqliConnection.php");
			include($_SERVER['DOCUMENT_ROOT']."/".v."/54 Automated Material Computation Software/ace_materialTemporaryBooking.php");
			include($_SERVER['DOCUMENT_ROOT']."/".v."/54 Automated Material Computation Software/ace_accessoryTemporaryBooking.php");
			include($_SERVER['DOCUMENT_ROOT']."/".v."/54 Automated Material Computation Software/ace_finishedGoodTemporaryBooking.php");			
			
			$this->db = $db;
		}
		
		function getForRoReviewLots()
		{
			$db = $this->db;
			
			$sql = "SELECT lotNumber FROM view_workschedule WHERE processCode = 459 ORDER BY targetFinish";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$this->reviewLot($resultWorkSchedule['lotNumber']);
				}
			}
		}
		
		function reviewLot($lotNumber)
		{
			$db = $this->db;
			
			$sql = "SELECT poId, partId, workingQuantity, patternId, partLevel FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$poId = $resultLotList['poId'];
				$partId = $resultLotList['partId'];
				$workingQuantity = $resultLotList['workingQuantity'];
				$patternId = $resultLotList['patternId'];
				$partLevel = $resultLotList['partLevel'];
				
				$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId."";
				$queryPartProcess = $db->query($sql);
				if($queryPartProcess AND $queryPartProcess->num_rows == 1)
				{
					$resultPartProcess = $queryPartProcess->fetch_assoc();
					
					if($patternId!=$resultPartProcess['patternId'])
					{
						$sql = "UPDATE ppic_lotlist SET patternId = '".$resultPartProcess['patternId']."' WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$patternId = $resultPartProcess['patternId'];
					}
				}
				else
				{
					return false;//Multiple Pattern
				}
				
				$sql = "SELECT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND (processCode = 144 OR processCode = 94) LIMIT 1";
				$queryPartProcess = $db->query($sql);
				if($queryPartProcess AND $queryPartProcess->num_rows == 0)
				{
					return false;//No Delivery Process or Documentation
				}
				
				$customerId = $poNumber = $customerDeliveryDate = '';
				$sql = "SELECT customerId, poNumber, customerDeliveryDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
				$queryPoList = $db->query($sql);
				if($queryPoList AND $queryPoList->num_rows > 0)
				{
					$resultPoList = $queryPoList->fetch_assoc();
					$customerId = $resultPoList['customerId'];
					$poNumber = $resultPoList['poNumber'];
					$customerDeliveryDate = $resultPoList['customerDeliveryDate'];
				}
				
				$deliveryType = 0;
				$sql = "SELECT deliveryType FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
				$queryCustomer = $db->query($sql);
				if($queryCustomer AND $queryCustomer->num_rows > 0)
				{
					$resultCustomer = $queryCustomer->fetch_assoc();
					$deliveryType = $resultCustomer['deliveryType'];
				}
				
				if($deliveryType!=1) return false;//Not Land
				
				if($_GET['country']==2 AND $customerId==215 AND strstr($poNumber,'IPO')!==FALSE)//GIGA-OYAMA
				{
					$finishedGoodStockFlag = 'X';
				}
				else
				{
					$finishedGoodStockFlag = finishedGoodTemporaryBooking($poId);
				}
				
				if($finishedGoodStockFlag=="O")
				{
					$sql = "UPDATE ppic_lotlist SET patternId = -1 WHERE lotNumber LIKE '".$lotNumberArray[$i]."' LIMIT 1";
					$queryUpdate = $db->query($sql);
					
					$patternId = -1;
				}
				else
				{
					$materialStockFlag = materialTemporaryBooking($poId);
					$accessoryStockFlag = accessoryTemporaryBooking($poId);
					
					if($materialStockFlag!="O" OR $accessoryStockFlag!="O")
					{
						return false;//No Material or No Accessory
					}
				}
				
				return true;
			}
		}
	}
?>
