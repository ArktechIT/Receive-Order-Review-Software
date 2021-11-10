<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);    
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
include('PHP Modules/gerald_functions.php');
include("PHP Modules/rose_prodfunctions.php");
ini_set("display_errors", "on");
$ctrl = new PMSDatabase;
$tpl = new PMSTemplates;

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

if(isset($_POST['type']) AND $_POST['type']=='modalBox')
{
    $partId = $_POST['partId'];
    $patternId = $_POST['patternId'];
    
    echo "
        <table  style='' class='table table-bordered table-striped table-condensed'>
            <thead class='w3-indigo' style='text-transform: uppercase;'>
                <th class='w3-center'>".displayText('L58')."</th>
                <th class='w3-center'>".displayText('L59')."</th>
                <th class='w3-center'>".displayText('L1121')."</th>
            </thead>
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

$customerAliasFilter = isset($_POST['customerAliasFilter']) ? $_POST['customerAliasFilter'] : "";
$poNumberFilter = isset($_POST['poNumberFilter']) ? $_POST['poNumberFilter'] : "";
$partNumberFilter = isset($_POST['partNumberFilter']) ? $_POST['partNumberFilter'] : "";
$partNameFilter = isset($_POST['partNameFilter']) ? $_POST['partNameFilter'] : "";
$materialFilter = isset($_POST['materialFilter']) ? $_POST['materialFilter'] : "";

$addQuery = "";
if($customerAliasFilter != "")
{
    $addQuery .= " AND customerAlias = '".$customerAliasFilter."'";
}

if($poNumberFilter != "")
{
    $addQuery .= " AND poNumber = '".$poNumberFilter."'";
}

if($partNumberFilter != "")
{
    $addQuery .= " AND partNumber = '".$partNumberFilter."'";
}

if($partNameFilter != "")
{
    $addQuery .= " AND partName = '".$partNameFilter."'";
}

if($materialFilter != "")
{
    $addQuery .= " AND dataSeven = '".$materialFilter."'";
}

$customerAliasArray = $poNumberArray = $partNumberArray = $partNameArray = $materialArray = Array ();
$totalRecords = 0;
$sql = "SELECT * FROM view_workschedule WHERE processCode IN(459,460,463) ".$addQuery." AND lotNumber!='19-08-2408'";
$sqlData = $sql;
$viewWorkSechedule = $db->query($sql);
if($viewWorkSechedule AND $viewWorkSechedule->num_rows > 0)
{
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

        $customerAliasArray[] = $customerAlias;
        $poNumberArray[] = $viewWorkSecheduleResult['poNumber'];
        $partNumberArray[] = $viewWorkSecheduleResult['partNumber'];
        $partNameArray[] = $viewWorkSecheduleResult['partName'];
        $materialArray[] = $viewWorkSecheduleResult['dataSeven'];
        
        $totalRecords++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo (displayText("3-8", 'utf8', 0, 2)); ?> v2.0</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/w3css/w3.css">
    <link rel="stylesheet" type="text/css" href="/V3/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.css">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/css/bootstrap.css">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/Font Awesome/css/font-awesome.css">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/Roboto Font/roboto.css">
	<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
	<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
	<style>
        .dataTables_wrapper .dataTables_filter {
			position: absolute;
			text-align: right;
			visibility: hidden;
		}
        
        body
		{
			font-size: 11px;
			font-family: Roboto;
			margin:0px;
			padding:0px;
			background-color:whitesmoke;
		}
	</style>
</head>
<body id="loading" class=''>
    <?php 
    $displayId = "3-8"; # RO REVIEW
    $version = "v2.0";
    $previousLink = "/V3/1-14%20Received%20Order%20Processing/raymond_receivedOrderProcessing.php";
    createHeader($displayId, $version, $previousLink);
    // displayText($displayId, $conversionType='utf8', $viewType=0, $placeholder=0, $characterCase=0)
    ?>
    <form id='formFilter' action='<?php echo $_SERVER['PHP_SELF']; ?>' method='POST'></form>
	<div class="container-fluid"> 
		<div class="row w3-padding-top">
			<div class="col-md-10"> 
                <div class='row'>
			        <div class="col-md-2">
                        <label>CUSTOMER</label>
                        <select form='formFilter' class='w3-input w3-border w3-pale-yellow' name='customerAliasFilter' onchange='this.form.submit();'>
                            <option></option>
                            <?php
                            $customerAliasArray = array_unique($customerAliasArray);
                            sort($customerAliasArray);
                            foreach ($customerAliasArray as $key) 
                            {
                                $selectedCustomer = ($customerAliasFilter == $key) ? "selected" : "";
                                echo "<option ".$selectedCustomer.">".$key."</option>";
                            }
                            ?>
                        </select>
                    </div> 
			        <div class="col-md-2">
                        <label>PO NUMBER</label>
                        <select form='formFilter' class='w3-input w3-border w3-pale-yellow' name='poNumberFilter' onchange='this.form.submit();'>
                            <option></option>
                            <?php
                            $poNumberArray = array_unique($poNumberArray);
                            sort($poNumberArray);
                            foreach ($poNumberArray as $key) 
                            {
                                $selectedPONumber = ($poNumberFilter == $key) ? "selected" : "";
                                echo "<option ".$selectedPONumber.">".$key."</option>";
                            }
                            ?>
                        </select>
                    </div> 
			        <div class="col-md-2">
                        <label>PART NUMBER</label>
                        <select form='formFilter' class='w3-input w3-border w3-pale-yellow' name='partNumberFilter' onchange='this.form.submit();'>
                            <option></option>
                            <?php
                            $partNumberArray = array_unique($partNumberArray);
                            sort($partNumberArray);
                            foreach ($partNumberArray as $key) 
                            {
                                $selectedPartNumber = ($partNumberFilter == $key) ? "selected" : "";
                                echo "<option ".$selectedPartNumber.">".$key."</option>";
                            }
                            ?>
                        </select>
                    </div> 
			        <div class="col-md-2">
                        <label>PART NAME</label>
                        <select form='formFilter' class='w3-input w3-border w3-pale-yellow' name='partNameFilter' onchange='this.form.submit();'>
                            <option></option>
                            <?php
                            $partNameArray = array_unique($partNameArray);
                            sort($partNameArray);
                            foreach ($partNameArray as $key) 
                            {
                                $selectedPartName = ($partNameFilter == $key) ? "selected" : "";
                                echo "<option ".$selectedPartName.">".$key."</option>";
                            }
                            ?>
                        </select>
                    </div> 
			        <div class="col-md-2">
                        <label>MATERIAL TYPE</label>
                        <select form='formFilter' class='w3-input w3-border w3-pale-yellow' name='materialFilter' onchange='this.form.submit();'>
                            <option></option>
                            <?php
                            $materialArray = array_unique($materialArray);
                            sort($materialArray);
                            foreach ($materialArray as $key) 
                            {
                                $selectedMaterial = ($materialFilter == $key) ? "selected" : "";
                                echo "<option ".$selectedMaterial.">".$key."</option>";
                            }
                            ?>
                        </select>
                    </div> 
                </div> 
                <div class='row w3-padding-top'>
			        <div class="col-md-12"> 
                        <label><?php echo (displayText("L41", 'utf8', 0, 0, 1)); // Records ?> : <?php echo $totalRecords; ?></label>&emsp;
                        <table id='mainTableId' style='' class="table table-bordered table-striped table-condensed">
                            <thead class='w3-indigo' style='text-transform: uppercase;'>
                                <th class='w3-center' style='vertical-align:middle;'></th>
                                <th class='w3-center' style='vertical-align:middle;'><input type='checkbox' id='chkAll'></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L24','utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L224', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L45', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L28', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L30', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L174', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L31', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L172', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L711', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L4117', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo 'FG' ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L174', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L471', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L91', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L1026', 'utf8', 0, 0, 1); ?></th>
                                <!-- <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L1027', 'utf8', 0, 0, 1); ?></th> -->
                                <!-- <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('3-6', 'utf8', 0, 0, 1); ?></th> -->
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L763', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L3642', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L343', 'utf8', 0, 0, 1); ?></th>
                                <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L3690', 'utf8', 0, 0, 1); ?></th>
                            </thead>
                            <tbody class='w3-center'>
                            <?php
                            
                            ?>
                            </tbody>
                            <tfoot class='w3-indigo' style='text-transform: uppercase;'>
                                <tr>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <!-- <th class='w3-center' style='vertical-align:middle;'></th> -->
                                    <!-- <th class='w3-center' style='vertical-align:middle;'></th> -->
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                    <th class='w3-center' style='vertical-align:middle;'></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
			</div>
            <div class='col-md-2'>
                <div class='row'>
                    <div class='col-md-6'>
                        <button class="w3-btn w3-round w3-purple w3-btn-block" onclick="location.href='rose_roreviewList.php';"><i class='fa fa-recycle'></i>&emsp;<b><?php echo (displayText('L2033', 'utf8', 0, 0, 1));?></b></button>
                    </div>
                    <div class='col-md-6'>
                        <button class='w3-btn w3-round w3-green w3-btn-block' onclick="location.href='';"><i class='fa fa-refresh'></i>&emsp;<b><?php echo (displayText('L436', 'utf8', 0, 0, 1));?></b></button>
                    </div>
                </div>
                <div class='row w3-padding-top'>
                    <div class='col-md-12'>
			            <button class='w3-btn w3-round w3-blue w3-btn-block' onclick="window.open('/V3/Section Work Schedule Graph/carlo_sectionScheduleGraph.php','BBC','left=50,screenX=20,screenY=60,resizable,scrollbars,status,width=1500,height=700'); return false;" id='schedGraph'><i class='fa fa-bar-chart'></i> <b><?php echo displayText('L3487','utf8',0,0,1); // SCHEDULE GRAPH?></b></button>&nbsp;
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-12'>
			            <button class='w3-btn w3-round w3-pink w3-btn-block' onclick="location.href='gerald_roReviewNoMaterial.php'"><i class='fa fa-calculator'></i>&emsp;<b><?php echo displayText('3-6','utf8',0,1,1); // MATERIAL COMPUTATION ?></b></button>&nbsp;
                    </div>
                </div>
                <div class='row'>
                    <div class='col-md-12'>
			            <button class='w3-btn w3-round w3-pink w3-btn-block' onclick="location.href='gerald_roReviewNoAccessory.php'"><i class='fa fa-calculator'></i>&emsp;<b><?php echo displayText('L1350','utf8',0,0,1); // ACCESSORY COMPUTATION ?></b></button>&nbsp;
                    </div>
                </div>
                <hr>
                <div class='row'>
                    <form id='formId2' name='inputForm' action='rose_finalizeReview.php' method='POST'></form>
                    <div class='col-md-12'>
                        <label><?php echo displayText('L1035', 'utf8', 0, 0, 1)?></label>
                        <input form='formId2' type='text' name='venue' class='w3-input w3-border' value='<?php echo $venue; ?>' required <?php echo $disableFields; ?>  onchange="showvenue(this)">
                    </div>
                    <div class='col-md-12 w3-padding-top'>
                        <label><?php echo displayText('L1036', 'utf8', 0, 0, 1)?></label>
                        <textarea form='formId2' name="participants" class='w3-input w3-border' rows="7" cols="20" <?php echo $disableFields; ?> onchange="showparticipants(this)" required><?php echo $participants; ?></textarea>
                    </div>
                    <div class='col-md-12 w3-padding-top w3-center'>
                        <button form='formId2' class='w3-btn w3-round w3-indigo w3-btn-block' name='save' type='submit'><i class='fa fa-send'></i>&emsp;<b><?php echo displayText('L1076', 'utf8', 0, 0, 1)?></b></button>
                    </div>
                </div>
            </div>
		</div>
	</div>
    <div id='modal-izi'><span class='izimodal-content'></span></div>
</body>
</html>
<script src="/V3/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/jQuery 3.1.1/bootstrap.min.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/jquery-date-range-picker-master/dist/daterangepicker.min.css">
<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/jquery-date-range-picker-master/moment.min.js"></script>
<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/jquery-date-range-picker-master/dist/jquery.daterangepicker.min.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/Bootstrap Multi-Select JS/dist/css/bootstrap-multiselect.css" type="text/css" media="all" />
<script src="/V3/Common Data/Libraries/Javascript/Bootstrap Multi-Select JS/dist/js/bootstrap-multiselect.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/iziModal-master/css/iziModal.css" />
<script src="/V3/Common Data/Libraries/Javascript/iziModal-master/js/iziModal.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/iziToast-master/dist/css/iziToast.css" />
<script src="/V3/Common Data/Libraries/Javascript/iziToast-master/dist/js/iziToast.js"></script>
<script>

function modalBox(partId, patternId, poId){
    $("#modal-izi").iziModal({
        title                   : '<i class="fa fa-flash"></i> DETAILS',
        headerColor             : '#1F4788',
        subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
        width                   : 400,
        fullscreen              : false,
        transitionIn            : 'comingIn',
        transitionOut           : 'comingOut',
        padding                 : 20,
        radius                  : 0,
        top                     : 10,
        restoreDefaultContent   : true,
        closeOnEscape           : true,
        closeButton             : true,
        overlayClose            : false,
        onOpening               : function(modal){
                                    modal.startLoading();
                                    $.ajax({
                                        url         : 'raymond_receivedOrderReview.php',
                                        type        : 'POST',
                                        data        : {
                                                        type        : 'modalBox',
                                                        patternId   : patternId,
                                                        partId      : partId,
                                                        poId        : poId
                                        },
                                        success     : function(data){
                                                        $( ".izimodal-content" ).html(data);
                                                        modal.stopLoading();
                                        }
                                    });
                                },
            onClosed            : function(modal){
                                    $("#modal-izi").iziModal("destroy");
                    }
    });

    $("#modal-izi").iziModal("open");
}

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

$(document).ready(function(){
    var sqlData = "<?php echo $sqlData; ?>";
    var totalRecords = "<?php echo $totalRecords; ?>";
    var hiddenColumn = [ 0 ];

    var dataTable = $('#mainTableId').DataTable( {
        "processing"    : true,
        "ordering"      : false,
        "serverSide"    : true,
        "bInfo" 		: false,
        "ajax":{
            url     :"raymond_receivedOrderReviewAJAX.php", // json datasource
            type    : "post",  // method  , by default get
            data    : {
                        "totalRecords"   	: totalRecords,
                        "sqlData"     	    : sqlData
                        },
            error: function(data){  // error handling
                
                $(".mainTableId-error").html("");
                $("#mainTableId").append('<tbody class="mainTableId-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                $("#mainTableId_processing").css("display","none");
                
            }
        },
        "createdRow": function( row, data, index ) {
            
            var poId = data[0];
            $('td:eq(17)', row).dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				if(thisVal.trim()!='')
				{
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

            $('td:eq(18)', row).dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				if(thisVal.trim()!='')
				{
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

            $('td:eq(19)', row).dblclick(function(){
				var thisObj = $(this);
				var thisVal = thisObj.text();
				//~ if(thisVal.trim()!='')
				//~ {
					var oldVal = $(this).text();
					
					thisObj.text('');
					var select = $("<input id='negoDateId' type='date' name='negoDate' onchange='changeNegoDate(this,"+poId+")' value='"+oldVal+"' style='background-color:yellow;'>");
					select.appendTo(thisObj);
					$("#negoDateId").focus();
					$("#negoDateId").blur(function(){
						var newVal = $(this).val();
                        if(newVal == "") newVal = "<b>N/A</b>";
						thisObj.html(newVal);	
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
				
				/* $.ajax({
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
				}); */
			});
        },
        "columnDefs": [
                        {
                            "targets" 		: hiddenColumn,
                            "visible"		: false,
                            "searchable" 	: true
                        }
        ],
        language	: {
                    processing	: "<span class='loader'></span>"
        },
        fixedColumns:   {
                leftColumns: 0
        },
        // responsive		: true,
        scrollY     	: 505,
        scrollX     	: true,
        scrollCollapse	: false,
        scroller    	: {
            loadingIndicator    : true
        },
        stateSave   	: false
    });
    
});
</script>
