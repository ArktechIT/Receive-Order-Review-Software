<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);    
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
include('PHP Modules/gerald_functions.php');
include("PHP Modules/rose_prodfunctions.php");
include('../../54 Automated Material Computation Software/ace_materialTemporaryBooking.php');
include('../../54 Automated Material Computation Software/ace_accessoryTemporaryBooking.php');
include('../../54 Automated Material Computation Software/ace_finishedGoodTemporaryBooking.php');
ini_set("display_errors", "on");
/* Database connection end */

// storing  request (ie, get/post) global array to a variable  
$requestData= $_REQUEST;
$sqlData = isset($requestData['sqlData']) ? $requestData['sqlData'] : "";
$totalRecords = (isset($requestData['totalRecords'])) ? $requestData['totalRecords'] : 0;
$totalFiltered = $totalRecords;
$totalData = $totalFiltered;

$data = array();

$sql = $sqlData;
$sql.=" LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
$counter = $requestData['start'];
// $sql = "SELECT lotNumber, targetFinish, processCode FROM view_workschedule WHERE processCode IN(459,460,463) and lotNumber!='19-08-2408'";
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

        $patternRadio = $deliveryTypeCaption = $dueDate = "";
        $sql = "SELECT * FROM ppic_roreviewdatatemptest WHERE poId = ".$poId;
        $queryReviewData = $db->query($sql);
        if($queryReviewData AND $queryReviewData->num_rows > 0)
        {
            $resultReviewData = $queryReviewData->fetch_assoc();
            $dueDate = $resultReviewData['dueDate'];
            $deliveryType = $resultReviewData['deliveryType'];

            if($deliveryType==1)
            {
                $deliveryTypeCaption = 'Land (5 Days)';
            }
            else if($deliveryType==2)
            {
                $deliveryTypeCaption = 'Air (7 Days)';
            }
            else if($deliveryType==3)
            {
                $deliveryTypeCaption = 'Sea (30 Days)';
            }

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
                                    }
                                }
                            }

                            $patternRadio .= "<label style='cursor:pointer;' onclick=\" modalBox(".$partId.", ".$resultPartProcess['patternId'].", ".$poId.");\" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId2'>Pattern(".$resultPartProcess['patternId'].")</label>";
                        }
                    }
                    
                    if($finishedGoodStockFlag=="O")
                    {
                        $patternRadio .= "<label style='cursor:pointer;' onclick=\" modalBox(".$partId.", '-1', ".$poId.");\" title='Click to view process'><input class='patternClass' data-lot='".$lotNumber."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumber."' value='-1' ".$checked." required form='formId2'>FG</label>";
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
        }

        $nestedData = Array();
        $nestedData[] = $poId;
        $nestedData[] = "<input type='checkbox' class='chkBox2' name='bookingId2[]' value='".$poId."' />";
        $nestedData[] = $customerAlias;
        $nestedData[] = $poNumber;
        $nestedData[] = $lotNumber;
        $nestedData[] = $partNumber;
        $nestedData[] = $partNameList;	
        $nestedData[] = $materialSpecification;
        $nestedData[] = $workingQuantity;
        $nestedData[] = $repeatStatus;
        $nestedData[] = date('m-d',strtotime($deliveryDate));
        $nestedData[] = date('m-d',strtotime($answerDate));	
        $nestedData[] = "";	
        $nestedData[] = "";	
        $nestedData[] = "";	
        $nestedData[] = $subconFlag;
        $nestedData[] = $bendFlag;
        // $nestedData[] = "";	
        // $nestedData[] = "";	
        $nestedData[] = $patternRadio;
        $nestedData[] = $deliveryTypeCaption;	
        $nestedData[] = $dueDate;
        if(in_array($customerId,array(28,37)))
        {
            $nestedData[] = "";
        }
        else
        {
            $nestedData[] = "<b>".displayText('L161')."</b>";
        }
        // $nestedData[] = $processCodeFilter;
        // $nestedData[] = $targetFinish;
        // $nestedData[] = $partIdList;	
        $data[] = $nestedData;
    }
}

$json_data = array(
            "draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
            "recordsTotal"    => intval( $totalData ),  // total number of records
            "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
            "data"            => $data   // total data array
    );

echo json_encode($json_data);  // send data as json format
?>