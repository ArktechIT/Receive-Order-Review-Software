<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);

if(!isset($_SESSION))
{
    include('Templates/mysqliConnection.php');
    $_SESSION = array();
    $sql = "SELECT * FROM hr_employee WHERE idNumber = '0412' ";
    $queryEmployee = $db->query($sql);
    if($queryEmployee AND $queryEmployee->num_rows > 0)
    {
        $resultEmployee = $queryEmployee->fetch_assoc();
        $idNumber = $resultEmployee['idNumber'];
        $employeeId = $resultEmployee['employeeId'];
        $sectionId = $resultEmployee['sectionId'];
        $departmentId = $resultEmployee['departmentId'];

        $sql = "SELECT * FROM system_users where employeeId = '".$employeeId."'";
        $queryAccounts = $db->query($sql);
        if($queryAccounts AND $queryAccounts->num_rows > 0)
        {
            $resultAccounts = $queryAccounts->fetch_assoc();
            $userName = $resultAccounts['userName'];
            $password = $resultAccounts['userPassword'];
            $userType = $resultAccounts['userType'];

            $_SESSION['userID'] = $userName;
            $_SESSION['password'] = $password;
            $_SESSION['userPassword'] = $password;
            $_SESSION['sectionId'] = $sectionId;
            $_SESSION['userType'] = trim($userType);
            $_SESSION['userName'] = $userName;
            $_SESSION['employeeId'] = $employeeId;
            $_SESSION['idNumber'] = $idNumber;
            $_SESSION['departmentId'] = $departmentId;
        }
    }
}

include('PHP Modules/mysqliConnection.php');
include('PHP Modules/gerald_functions.php');
include('PHP Modules/rhay_function.php');
include('PHP Modules/rose_prodfunctions.php');
include('PHP Modules/anthony_retrieveText.php');
include('../../54 Automated Material Computation Software/ace_materialTemporaryBooking.php');
include('../../54 Automated Material Computation Software/ace_accessoryTemporaryBooking.php');
include('../../54 Automated Material Computation Software/ace_finishedGoodTemporaryBooking.php');
ini_set("display_errors", "on");

$type = isset($_POST['type']) ? $_POST['type'] : "";
if($type == "autoRefresh")
{
    $totalRecords = 0;
    $sql = "SELECT lotNumber, targetFinish, processCode FROM view_workschedule WHERE processCode IN(459,460,463) and lotNumber!='19-08-2408'";
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

            $sql = "SELECT roDataTempId FROM ppic_roreviewdatatemptest WHERE poId  = ".$poId;
            $queryCheck = $db->query($sql);
            if($queryCheck AND $queryCheck->num_rows == 0)
            {
                $sql = "INSERT INTO ppic_roreviewdatatemptest(poId,matCheck) VALUES (".$poId.",1)";
                $insertQuery = $db->query($sql);
            }
            $totalRecords++;
        }
    }

    $checkingOfReqFlagArry[$i]=0;
    $patternRadio = $deliveryTypeCaption = $str2 = "";
    $sql = "SELECT poId, matCheck, materialComputationId, deliveryType FROM ppic_roreviewdatatemptest";
    $roReviewQuery = $db->query($sql);
    if($roReviewQuery->num_rows > 0)
    {
        while($matCheckQueryResult = $roReviewQuery->fetch_assoc())
        {
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
            
            if($_GET['country']==2 AND $customerAlias=='GIGA-OYAMA' AND strstr($poNumber,'IPO')!==FALSE)
            {
                $finishedGoodStockColor = "LightPink";
                $finishedGoodStockFlag = 'X';
            }
            else
            {
                $finishedGoodStockColor = "LightGreen";
                $finishedGoodStockFlag = finishedGoodTemporaryBooking($poId);
                //~ if($_GET['country']==1) $finishedGoodStockFlag = 'X';//Disabled FG mam jane 2019-06-19
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
                $materialStockFlag = materialTemporaryBooking($poId);
                //$materialStockFlag = 'X';
                
                //~ if(in_array($lotNumber,array('20-07-390','20-07-522','20-07-656')))
                //~ if(in_array($poId,array('1458871','1458885','1458900','1459278')))//1459278 is test
                //~ if(in_array($poId,array('1459887','1459344','1459345','1459346','1459347')))//2020-07-28
                //~ if(in_array($poId,array('1459343')))//2020-08-06
                //~ if(in_array($poId,array('1460416','1459853','1459855')))//2020-08-07
                //~ if(in_array($poId,array('1460577','1460578')))//2020-08-11
                if(in_array($poId,array('1460575')))//2020-08-12
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
                
                $accessoryStockFlag = accessoryTemporaryBooking($poId);
                
                if(in_array($poId,array('1459896')))//2020-08-03
                {
                    $accessoryStockFlag = "O";
                }
                
                //$accessoryStockFlag = 'X';
                if($accessoryStockFlag=='X')
                {
                    $accessoryStockColor = "LightPink";
                    $checkingOfReqFlag=1; // rose 2018-05-24 binalik
                    //~ $checkingOfReqFlagArry[$i]=1;
                    
                    $accessoryCheckArray[] = $poId;
                }
            }
            // -------------------------- No Finished Good Stocks -------------------------								
            
            $noDeliveryExemption = 0;
            if($_GET['country']==2 AND $customerAlias=='MRT')
            {
                $noDeliveryExemption = 1;
            }
            
            $patternRadio = "N/A";
            $partId = $patternId = 0;
            $sql = "SELECT partId, patternId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' AND identifier = 1 LIMIT 1";
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
                                        $sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
                                        $queryUpdate = $db->query($sql);
                                    }
                                }
                            }
                            $patternRadio .= "<label style='cursor:pointer;' onclick=\" modalBox(".$partId.", ".$resultPartProcess['patternId'].", ".$poId.");\" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId2'>Pattern(".$resultPartProcess['patternId'].")</label>";
                            //~ $patternRadio .= "<label style='cursor:pointer;' onclick=\" openTinyBox('','','".$_SERVER['PHP_SELF']."','','type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&'); \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId'>Pattern(".$resultPartProcess['patternId'].")</label>";

                            // $patternRadio .= "<label style='cursor:pointer;' onclick=\" modalBox(".$partId.", ".$resultPartProcess['patternId'].", ".$poId.");\">Pattern(".$resultPartProcess['patternId'].")</label>";
                        }
                    }
                    
                    if($finishedGoodStockFlag=="O")
                    {
                        //~ $patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox&partId=".$partId."&patternId=-1&inventoryId=".$inventoryId."&lotNumber=".$lotNumber."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='-1' ".$checked." required form='formId'>FG</label>";
                        //~ $fgCaption = "<span style='color:green;' >(FG)</span>";
                        
                        $patternRadio .= "<label style='cursor:pointer;' onclick=\" modalBox(".$partId.", '-1', ".$poId.");\" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='-1' ".$checked." required form='formId2'>FG</label>";
                        $sql = "UPDATE ppic_lotlist SET patternId = -1 WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
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
                        if($poNumber=='IPO 1058')	$noDeliveryProcessFlag = 0;//by leslie 2019-08-06
                        if($poNumber=='IPO 1070')	$noDeliveryProcessFlag = 0;//by leslie 2019-09-06
                        if($poNumber=='IPO 1089')	$noDeliveryProcessFlag = 0;//by leslie 2019-10-17
                        if($poNumber=='IPO 1166')	$noDeliveryProcessFlag = 0;//by leslie 2020-05-23
                        if($poNumber=='IPO 1174')	$noDeliveryProcessFlag = 0;//by leslie 2020-06-19
                        if($poNumber=='IPO 1180')	$noDeliveryProcessFlag = 0;//by leslie 2020-07-19
                    }
                }
            }
            
            $customerId = '';
            $RosecustomerDeliveryDate = '';
            $sql = "SELECT customerId,customerDeliveryDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
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
            
            $dueDate = '0000-00-00';
            //ROSE START 2019-11-15 use rhay code!!!!! if ano ang del date sa RO list un ang sususndin minus delType then color red if negative lead time //consulted by les
            $sql = "SELECT dueDate, changeDueDateFlag FROM ppic_roreviewdatatemptest WHERE poId = ".$poId." LIMIT 1";
            $queryRoReviewDataTemp = $db->query($sql);
            if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
            {
                $resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
                if($resultRoReviewDataTemp['changeDueDateFlag']==0)
                {
                    list($sectionChange,$leadTime,$sectionChangesArray,$processArray,$adjustment,$tempDueDate) = show_lotSchedule($lotNumber);	
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
                    $sql = "UPDATE ppic_roreviewdatatemptest SET dueDate = '".$str2."', deldate = '".$RosecustomerDeliveryDate."', answerDate = '".$answerDate."', deliveryType = '".$deliveryType."' WHERE poId = ".$poId." LIMIT 1";
                    //$sql = "UPDATE ppic_roreviewdatatemptest SET dueDate = '".$str2."', deliveryType = '".$deliveryType."' WHERE poId = ".$poId." LIMIT 1";
                    $queryUpdate = $db->query($sql);
                }
                else
                {
                    $str2 = $resultRoReviewDataTemp['dueDate'];
                }
            }
        }
    }
    
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/w3css/w3.css">
    <link rel="stylesheet" type="text/css" href="/V3/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="/V3/Common Data/Libraries/Javascript/Super Quick Table/dataTables.checkboxes.css">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/css/bootstrap.css">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/Font Awesome/css/font-awesome.css">
	<link rel="stylesheet" href="/V3/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/Roboto Font/roboto.css">
</head>
<style>
    body
    {
        font-size: 11px;
        font-family: Roboto;
        background-color:whitesmoke;
    }
</style>
<body>
    <div class='container-fluid'>
        <div class="row">
			<div class="w3-container w3-indigo w3-padding w3-card-2">
				<div class="col-md-12">
					<label class='w3-large' style='text-transform:;'><b>RO REVIEW - AUTO RUN</b></label>
				</div>
			</div>
		</div>
    </div>
    <div class='container w3-padding-top'>
        <div class="row">
			<div class="w3-container w3-center">
				<div class="col-md-12">
                    <div id="clock"></div>
                    <h1><span id='refreshCounter'>0</span></h1>
                </div>
            </div>
        </div>
        <div class="row">
			<div class="w3-container w3-center">
				<div class="col-md-12">
                    <p id = "asd"></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<script src="/V3/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/jQuery 3.1.1/bootstrap.min.js"></script>
<script src="/V3/Common Data/Libraries/Javascript/jQuery.countdown-master/dist/jquery.countdown.js"></script>
<script type="text/javascript">
function timerCounter(interval){
    var fiveSeconds = new Date().getTime() + interval;
    $('#clock').countdown(fiveSeconds, {elapse: true}).on('update.countdown', function(event) {
        var $this = $(this);
        if (event.elapsed) 
        {
            $this.html(event.strftime('<span class="w3-large"><b>Timer : <span>%H:%M:%S</span></b></span>'));
        } 
        else 
        {
            $this.html(event.strftime('<span class="w3-large"><b>Timer : <span>%H:%M:%S</span></b></span>'));
        }
    });
}
		
$(document).ready(function() {
    var x = 0;
    var interval = 1000 * 60 * 5;
    timerCounter(interval);
    setInterval(function(){
        x = x+1;
        $.ajax({
            url 	: 'insertTemp.php',
            type 	: 'POST',
            data 	: {
                            type            : 'autoRefresh',
                      },
            success : function(data){
                        $("#refreshCounter").html(x);
                        $("#asd").html(data);
                        timerCounter(interval);
            }
        });
    },interval+1000);
});
</script>
<script type="text/javascript" src="/V3/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/V3/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />