<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
    $path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
    set_include_path($path);   
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
    include('PHP Modules/gerald_functions.php');
	include('../54 Automated Material Computation Software/ace_materialTemporaryBooking.php');
	include('../54 Automated Material Computation Software/ace_accessoryTemporaryBooking.php');
	include('../54 Automated Material Computation Software/ace_finishedGoodTemporaryBooking.php');
    ini_set("display_errors", "on");

    $data = array();
    $poIdArray = array();
    $customerAliasArray = array();
    $poNumberArray = array();
    $lotNumberArray = array();
    $partNumberArray = array();
    $materialSpecificationArray = array();
    $workingQuantityArray = array();
    $repeatStatusArray = array();
    $targetFinishArray = array();
    $deliveryDateArray = array();
    $subconFlagArray = array();
    $bendFlagArray = array();		
    
    $sql = "SELECT lotNumber, targetFinish FROM view_workschedule WHERE processCode IN(459) AND status = 0";
    $viewWorkSechedule = $db->query($sql);
    while ($viewWorkSecheduleResult = $viewWorkSechedule->fetch_assoc())
    {
        $targetFinish = $viewWorkSecheduleResult['targetFinish'];
        $lotNumber = $viewWorkSecheduleResult['lotNumber'];
    
        $workingQuantity=0;
        $poId=0;
        $partId=0;				
        $sql = "SELECT poId, partId, workingQuantity FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."'";
        $lotListQuery = $db->query($sql);
        if($lotListQuery AND $lotListQuery->num_rows > 0)
        {
            $lotListQueryResult = $lotListQuery->fetch_assoc();
            $poId = $lotListQueryResult['poId'];
            $partId = $lotListQueryResult['partId'];					
            $workingQuantity = $lotListQueryResult['workingQuantity'];					 		
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
        
        $sql = "SELECT customerId, poNumber, deliveryDate FROM sales_polist WHERE poId IN(".$poId.")";
        $poListQuery = $db->query($sql);
        if($poListQuery->num_rows > 0)
        {
            $poListQueryResult = $poListQuery->fetch_assoc();
            $customerId = $poListQueryResult['customerId'];		
            $poNumber = $poListQueryResult['poNumber'];		
            $deliveryDate = $poListQueryResult['deliveryDate'];		
            
            $sql = "SELECT customerAlias FROM sales_customer WHERE customerId IN(".$customerId.")";
            $customerAliasQuery = $db->query($sql);
            if($customerAliasQuery->num_rows > 0)
            {
                $customerAliasQueryResult = $customerAliasQuery->fetch_assoc();
                $customerAlias = $customerAliasQueryResult['customerAlias'];									
            }
        }
        
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
            
            $bendFlag="NO";
            $sql = "SELECT partId FROM cadcam_partprocess WHERE partId IN(".$partId.") and  processCode>0 and processCode<25";
            $queryBend = $db->query($sql);
            if($queryBend->num_rows > 0)
            {
                $bendFlag="YES";									
            }
        }
        
        $poIdArray[] = $poId;
        $customerAliasArray[] = $customerAlias;
        $poNumberArray[] = $poNumber;
        $lotNumberArray[] = $lotNumber;
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
    }


    $checkingOfReqFlag=0;
    $checkingOfReqFlag2=0;
    $noDeliveryProcessFlag=0;
    
    $matCheckYesArray = $matCheckNoArray = array();
    
    for($i=0;$i<count($poIdArray);$i++)
    {
        $checkingOfReqFlagArry[$i]=0;
        $sql = "SELECT poId, matCheck, materialComputationId FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i];
        $roReviewQuery = $db->query($sql);
        if($roReviewQuery->num_rows > 0)
        {
            $matCheckQueryResult = $roReviewQuery->fetch_assoc();
            $matCheck = $matCheckQueryResult['matCheck'];
            $materialComputationId = $matCheckQueryResult['materialComputationId'];
            
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
                //$accessoryStockFlag = 'X';
                if($accessoryStockFlag=='X')
                {
                    $accessoryStockColor = "LightPink";
                    $checkingOfReqFlag=1; // rose 2018-05-24 binalik
                    //~ $checkingOfReqFlagArry[$i]=1;
                }
            }
            // -------------------------- No Finished Good Stocks -------------------------								
            
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
                $sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND (processCode = 144 OR 94)";
                $queryCheckDeliveryProcess = $db->query($sql);
                if($queryCheckDeliveryProcess AND $queryCheckDeliveryProcess->num_rows > 0)
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
                                        $sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumberArray[$i]."' LIMIT 1";
                                        $queryUpdate = $db->query($sql);
                                    }
                                }
                            }
                            $patternRadio .= "<label style='cursor:pointer;' onclick=\" apiOpenModalBox({url:'".$_SERVER['PHP_SELF']."',post:'type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&poId=".$poIdArray[$i]."',mask:true,customFunction:function(){jsFunctions();}}) \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId2'>Pattern(".$resultPartProcess['patternId'].")</label>";
                            //~ $patternRadio .= "<label style='cursor:pointer;' onclick=\" openTinyBox('','','".$_SERVER['PHP_SELF']."','','type=modalBox&partId=".$partId."&patternId=".$resultPartProcess['patternId']."&'); \" title='Click to view process'><input class='patternClass' data-lot='".$lotNumberArray[$i]."' data-parentlot='".$parentLot."' type='radio' name='patternId".$lotNumberArray[$i]."' value='".$resultPartProcess['patternId']."' ".$checked." required form='formId'>Pattern(".$resultPartProcess['patternId'].")</label>";
                        }
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
                    }
                }
            }

            $nestedData = array();
			$nestedData[] = "<input type='checkbox' class='chkBox2' id='chk[]' name='bookingId2[]' value='".$poIdArray[$i]."' />";
			$nestedData[] = $customerAliasArray[$i];
			$nestedData[] = $poNumberArray[$i];
			$nestedData[] = $lotNumberArray[$i];
			$nestedData[] = $partNumberArray[$i];
			$nestedData[] = $materialSpecificationArray[$i];
			$nestedData[] = $workingQuantityArray[$i];
			$nestedData[] = $repeatStatusArray[$i];
			$nestedData[] = date('m-d',strtotime($deliveryDateArray[$i]));
			$nestedData[] = "<a href='/".v."/54 Automated Material Computation Software/ace_viewFinishedGoodComputation.php?inputData=".$poIdArray[$i]."'>".$finishedGoodStockFlag."</a>";
			$nestedData[] = "<a href='/".v."/54 Automated Material Computation Software/ace_viewMaterialComputation.php?inputData=".$poIdArray[$i]."'>".$materialStockFlag."</a>";
			$nestedData[] = "<a href='/".v."/54 Automated Material Computation Software/ace_viewMaterialComputation.php?inputData=".$poIdArray[$i]."'>".$materialStockFlag."</a>";
			$nestedData[] = "<a href='/".v."/54 Automated Material Computation Software/ace_viewAccessoryComputation.php?inputData=".$poIdArray[$i]."'>".$accessoryStockFlag."</a>";
			$nestedData[] = $subconFlagArray[$i];
			$nestedData[] = $bendFlagArray[$i];
            
            if($checkingOfReqFlagArry[$i]==1)
            {
                if($matCheck==0) $check = "No";
                else if($matCheck==1) $check = "Yes";
                
                $nestedData[] = $check;
                
                if($matCheck==0){ $checkingOfReqFlag2=1; }
                
                if($matCheck==0)	$matCheckYesArray[] = $poIdArray[$i];
            }
            else
            {
                $nestedData[] = "<input type = 'hidden' name = 'matCheck' value = '".$poIdArray[$i]."_1'>";
                
                if($matCheck==1) $matCheckNoArray[] = $poIdArray[$i];
            }
                $nestedData[] = $materialComputationStatus;
                $nestedData[] = $patternRadio;
            $data[] = $nestedData;
        }
    //if($checkingOfReqFlag2==0){ $checkingOfReqFlag=0; } // rose 2018-05-24 commented
    }


    $json_data = array(
        "draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval( $totalData ),  // total number of records
        "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $data   // total data array
    );

    echo json_encode($json_data);  // send data as json format	
				
?>