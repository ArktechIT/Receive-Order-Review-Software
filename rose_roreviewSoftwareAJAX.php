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
    
    $requestData= $_REQUEST;
    $sqlFilterData = $requestData['sqlFilterData'];

    $sql = "SELECT lotNumber, targetFinish FROM view_workschedule WHERE processCode IN(459) AND status = 0 ".$sqlFilterData;
    // $sql .= " LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
    $viewWorkSechedule = $db->query($sql);
    $totalRecords = $viewWorkSechedule->num_rows;
    // $totalData = $totalRecords;
    // $totalFiltered = $totalData;
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


    for($i=0;$i<count($poIdArray);$i++)
    {
        $sql = "SELECT poId FROM ppic_roreviewdatatemp WHERE poId = ".$poIdArray[$i];
        $selectedItemQuery = $db->query($sql);
        if(!$selectedItemQuery->num_rows > 0)
        {
            $nestedData = array();
			$nestedData[] = "<input type='checkbox' class='chkBox' id='chk[]' name='bookingId[]' value='".$poIdArray[$i]."' />".$i;
			$nestedData[] = $customerAliasArray[$i];
			$nestedData[] = $poNumberArray[$i];
			$nestedData[] = "<a href='' onclick=\"window.open('/".v."/16 Lot Details Management Software/ace_lotDetails.php?inputLot=".$lotNumberArray[$i]."', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=100, left=100, width=1200, height=600')\" >".$lotNumberArray[$i]."</a>";
			$nestedData[] = $partNumberArray[$i];
			$nestedData[] = $partNameArray[$i];
			$nestedData[] = $materialSpecificationArray[$i];
			$nestedData[] = $workingQuantityArray[$i];
			$nestedData[] = $repeatStatusArray[$i];
			$nestedData[] = date('m-d',strtotime($targetFinishArray[$i]));
			$nestedData[] = date('m-d',strtotime($deliveryDateArray[$i]));
			$nestedData[] = $subconFlagArray[$i];
			$nestedData[] = $bendFlagArray[$i];
            if(file_exists("../../Document Management System/Arktech Folder/ARK_".$partIdListArray[$i].".pdf")>0)
            {
                $pdfDrawing = "<span style='color:blue;cursor:pointer;text-decoration:underline;' onclick=\" window.open('/".v."/20 Document Management System/gerald_viewPdf.php?file=Arktech Folder/ARK_".$partIdListArray[$i].".pdf','myWindow2','left=50,screenX=20,screenY=60,resizable,scrollbars,status,width=700,height=500'); return false;\"><img src='/".v."/Common Data/Templates/images/view1.png' height='15'); \"></span>";
            }
            else
            { 
                $pdfDrawing = ""; 
            }
            
            $nestedData[] = $pdfDrawing;
            
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
