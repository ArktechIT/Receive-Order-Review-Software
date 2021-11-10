<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);    
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_wholeNumber.php');
include('PHP Modules/anthony_retrieveText.php');
include('PHP Modules/gerald_functions.php');
ini_set("display_errors", "on");
/* Database connection end */

// storing  request (ie, get/post) global array to a variable  
$requestData= $_REQUEST;
$sqlFilter = isset($requestData['sqlFilter']) ? $requestData['sqlFilter'] : "";
$sqlData = isset($requestData['sqlData']) ? $requestData['sqlData'] : "";
$submitType = (isset($_POST['submitType'])) ? $_POST['submitType'] : '';
$totalRecords = (isset($requestData['totalRecords'])) ? $requestData['totalRecords'] : 0;
$totalFiltered = $totalRecords;
$totalData = $totalFiltered;

$data = array();

$sql = $sqlData;
if($submitType == "")
{
    $sql.=" LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
    $counter = $requestData['start'];
}
else
{
    $counter = 0;
}
$query = $db->query($sql);
if($query->num_rows > 0)
{
    while($result = $query->fetch_array())
    {
        $materialComputationId = $result['materialComputationId'];
        $customerAlias = $result['customerAlias'];
        $materialType = $result['materialType'];
        $thickness = $result['thickness'];
        $length = $result['length'];
        $width = $result['width'];			
        $treatment = $result['treatment'];			
        $pvc = $result['pvc'];			
        $quantity = $result['quantity'];			
        $finalQuantity = $result['finalQuantity'];			
        $status = $result['status'];			
        $idnumber = $result['idnumber'];			
        $inputDateTime = $result['inputDateTime'];			
        $lotNumber = $result['lotNumber'];			
        $blankingProcess = $result['blankingProcess'];			
        
        $pvcStatus = ($pvc==1) ? 'Yes' : 'No';
        
        $quantityInput = "<input type='number' onchange=\"finalQuantityClass('".$materialComputationId."', this.value);\" data-material-computation-id='".$materialComputationId."' class='finalQuantityClass w3-input w3-border' name='qtyPerSheet[]' value='".$finalQuantity."' step='any' form='computeFormId'>";
        
        if($lotNumber=='')
        {
            $selected0 = ($status==0) ? 'selected' : '';
            $selected1 = ($status==1) ? 'selected' : '';
            $selected4 = ($status==4) ? 'selected' : '';
            
            $statusArray = array();
            $statusArray[] = "<option value='0' ".$selected0."></option>";
            $statusArray[] = "<option value='1' ".$selected1.">".displayText('L1345')."</option>";
            $statusArray[] = "<option value='4' ".$selected4.">For Customer Request</option>";
            
            $yonko = "<select onchange=\"statusClass('".$materialComputationId."', this.value);\" class='statusClass w3-input w3-border' data-material-computation-id='".$materialComputationId."'>".implode("",$statusArray)."</select>";
        }
        else
        {
            if($status==0)		$yonko = 'Not Set';
            else if($status==1)	$yonko = 'For Purchase';
            else if($status==2)	$yonko = 'For Subcon';
            else if($status==3)	$yonko = 'For Internal Prime';
            else if($status==4)	$yonko = 'For Customer Request';
            else if($status==5)	$yonko = 'Open PO';
        }
        
        if($compressedInput == "")
        {
            $compressedInput = $materialType."`".$thickness."`".$length."`".$width."`".$quantity."`".$treatment."`".$pvcStatus;
        }
        else
        {
            $compressedInput = $compressedInput."`".$materialType."`".$thickness."`".$length."`".$width."`".$quantity."`".$treatment."`".$pvcStatus;
        }			
        
        $tableContent .= "
            <tr class='internalTrClass' data-index='".$count."'>
                <td><input type='checkbox'><br>".++$count."</td>
                <td>".$customerAlias."</td>
                <td>".$materialType."</td>
                <td>".$thickness."</td>
                <td class='length'>".$length."</td>
                <td class='width'>".$width."</td>
                <td>".$treatment."</td>
                <td>".$pvcStatus."</td>
                <td>".$quantity."</td>
                <td>".$quantityInput."</td>
                <td>".$yonko."</td>
                ".$additionalTD."
            </tr>
        ";

        $nestedData = [];
        $nestedData[] = "<input type='checkbox'><br></b>".++$counter."</b>";
        $nestedData[] = $customerAlias;
        $nestedData[] = $materialType;
        $nestedData[] = $thickness;
        $nestedData[] = $length;
        $nestedData[] = $width;
        $nestedData[] = $treatment;
        $nestedData[] = $pvcStatus;
        $nestedData[] = $quantity;
        $nestedData[] = $quantityInput;
        $nestedData[] = $yonko;

        $additionalTD = "";
        if(strstr($sqlFilter,'status = 2')!==FALSE OR strstr($sqlFilter,'status = 3')!==FALSE OR (strstr($sqlFilter,'status = 4')!==FALSE AND $_SESSION['idNumber']=='0346') AND $_SESSION['idNumber']=='0346')
        {
            $fixedFlag = 0;
            $materialLength = $length;
            $materialWidth = $width;
            
            //~ $finalQuantity = 11;
            
            if($status==2)
            {
                if($length==1250 AND $width==625)
                {
                    //Produced 4 qty per sheet
                    $materialLength = 2500;
                    $materialWidth = 1250;
                    $fixedFlag = 1;
                }
                else if($length==1000 AND $width==500)
                {
                    //Produced 1 qty per sheet
                    $materialLength = 1000;
                    $materialWidth = 500;
                    $fixedFlag = 1;
                }
                else if($length==1000 AND $width==1000)
                {
                    //Produced 2 qty per sheet
                    $materialLength = 2000;
                    $materialWidth = 1000;
                    $fixedFlag = 1;
                }
                else if($length==1219 AND $width==609)
                {
                    //Produced 6 qty per sheet
                    $materialLength = 3657;
                    $materialWidth = 1219;
                    $fixedFlag = 1;
                }
            }
            //~ else if($status==3)
            else if($status==3 OR ($status==4 AND $_SESSION['idNumber']=='0346'))
            {
                if($length==1219 AND $width==609)
                {
                    //Produced 4 qty per sheet
                    $materialLength = 2438;
                    $materialWidth = 1219;
                    $fixedFlag = 1;
                }
                else if($length==1000 AND $width==1000)
                {
                    //Produced 2 qty per sheet
                    $materialLength = 2000;
                    $materialWidth = 1000;
                    $fixedFlag = 1;
                }
            }
            
            $inventoryIdArray = array();
            
            $quantityPerSheet = 0;
            $bookingId = '';
            $bookingIdArray = array();
            $sql = "SELECT DISTINCT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$materialComputationId."|%'";
            $queryBookingDetails = $db->query($sql);
            if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
            {
                while($resultBookingDetails = $queryBookingDetails->fetch_assoc())
                {
                    $bookingIdArray[] = $resultBookingDetails['bookingId'];
                }
                
                //~ $sql = "SELECT inventoryId, bookingQuantity FROM engineering_booking WHERE bookingId = ".$bookingId." LIMIT 1";
                $sql = "SELECT inventoryId, bookingQuantity FROM engineering_booking WHERE bookingId IN(".implode(",",$bookingIdArray).")";
                $queryBooking = $db->query($sql);
                if($queryBooking AND $queryBooking->num_rows > 0)
                {
                    while($resultBooking = $queryBooking->fetch_assoc())
                    {
                        $inventoryIdArray[] = $resultBooking['inventoryId'];
                    }
                }
            }
            else
            {
                $inventoryId = 'No Material';
                $workingQuantity = $sheetCount = 0;
                if($fixedFlag==1)
                {
                    $programP = ($materialLength / $length);
                    $programK = ($materialWidth / $width);
                    
                    $quantityPerSheet = floor($programP) * floor($programK);
                    
                    while($finalQuantity > $workingQuantity)
                    {
                        $workingQuantity += $quantityPerSheet;
                        $sheetCount++;
                    }
                    
                    $initialWorkingQuantity = $finalQuantity;
                    //~ $initialWorkingQuantity = $workingQuantity;
                    
                    if(stristr($customerAlias,'jamco')!==FALSE)//Jamco
                    {
                        $filterSupplier = "AND supplierAlias IN('Jamco','KAPCO','Kapco Manufacturing Inc.')";
                    }
                    else if(stristr($customerAlias,'B/E')!==FALSE)//B/E
                    {
                        $filterSupplier = "AND supplierAlias IN('Metalweb Ltd.','KAPCO','Kapco Manufacturing Inc.','Shs Perforated Materials Inc.','B/e Aerospace','Garmco','MD AEROSPACE')";
                    }
                    
                    $repeatFlag = 1;
                    while($repeatFlag==1)
                    {
                        $repeatFlag = 0;
                        $dataThree = $dataFour = '';
                        $sql = "SELECT inventoryId, supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive, inventoryQuantity FROM warehouse_inventory WHERE type = 1 AND dataSix = 0 AND dataOne LIKE '".$materialType."' AND dataTwo = ".$thickness." AND dataThree = ".$materialLength." AND dataFour = ".$materialWidth." AND dataFive LIKE 'Raw' ".$filterSupplier." ORDER BY stockDate ASC";
                        $queryInventory = $db->query($sql);
                        if($queryInventory AND $queryInventory->num_rows > 0)
                        {
                            while($resultInventory = $queryInventory->fetch_assoc())
                            {
                                $inventoryId = $resultInventory['inventoryId'];
                                $supplierAlias = $resultInventory['supplierAlias'];
                                $dataOne = $resultInventory['dataOne'];
                                $dataTwo = $resultInventory['dataTwo'];
                                $dataThree = $resultInventory['dataThree'];
                                $dataFour = $resultInventory['dataFour'];
                                $dataFive = $resultInventory['dataFive'];
                                $inventoryQuantity = $resultInventory['inventoryQuantity'];
                                
                                // -------------------------- Count Booked Materials ----------------------------------
                                $totalBookingQty = 0;
                                $sql = "SELECT IFNULL(SUM(bookingQuantity),0) as totalBookingQty FROM engineering_booking WHERE inventoryId LIKE '".$inventoryId."' AND bookingStatus IN (0,2)";
                                //echo $sql;
                                $queryBooking = $db->query($sql);
                                if($queryBooking->num_rows > 0)
                                {
                                    $resultBooking = $queryBooking->fetch_assoc();
                                    $totalBookingQty = $resultBooking['totalBookingQty'];
                                    if($totalBookingQty==NULL) $totalBookingQty = 0;
                                }
                                // ------------------------- End Of Count Booked Materials -------------------------------
                                
                                // ------------------------- Count Withdrawn Materials -----------------------------------
                                $totalWithdrawalQty = 0;
                                $sql = "SELECT IFNULL(SUM(withdrawMaterialQuantity),0) as totalWithdrawalQty FROM warehouse_materialwithdrawal WHERE withdrawMaterialId LIKE '".$inventoryId."'";
                                $queryMaterialWithdrawal = $db->query($sql);
                                if($queryMaterialWithdrawal->num_rows > 0)
                                {
                                    $resultMaterialWithdrawal = $queryMaterialWithdrawal->fetch_assoc();
                                    $totalWithdrawalQty = $resultMaterialWithdrawal['totalWithdrawalQty'];
                                    if($totalWithdrawalQty==NULL) $totalWithdrawalQty = 0;
                                }
                                // ------------------------- End Of Count Withdrawn Materials -----------------------------------

                                // ------------------------ Compute Useable Stocks -----------------------------------------------
                                $stock = $inventoryQuantity - ($totalBookingQty + $totalWithdrawalQty);
                                
                                if($stock > 0 AND $stock >= $sheetCount)
                                {
                                    $number = 1;
                                    $sql = "SELECT number FROM ppic_materialcomputationtemporarylot WHERE materialComputationId = ".$materialComputationId." ORDER BY number DESC LIMIT 1";
                                    $queryMaterialComputationTemporaryLot = $db->query($sql);
                                    if($queryMaterialComputationTemporaryLot AND $queryMaterialComputationTemporaryLot->num_rows > 0)
                                    {
                                        $resultMaterialComputationTemporaryLot = $queryMaterialComputationTemporaryLot->fetch_assoc();
                                        $number = ($resultMaterialComputationTemporaryLot['number']+1);
                                    }
                                    
                                    $materialComputationTemporaryLot = $materialComputationId."|".$number;
                                    
                                    $remainingQuantityQuantity = $initialWorkingQuantity;
                                    $workingQuantity = 0;
                                    $shitCount = $sheetCount;
                                    while($shitCount > 0)
                                    {
                                        $workingQty = ($remainingQuantityQuantity >= $quantityPerSheet) ? $quantityPerSheet : $remainingQuantityQuantity;
                                        $remainingQuantityQuantity -= $workingQty;
                                        $workingQuantity += $workingQty;
                                        
                                        $shitCount--;
                                    }
                                    
                                    $sql = "INSERT INTO	`ppic_materialcomputationtemporarylot`
                                                    (	`materialComputationId`,		`number`,		`quantity`)
                                            VALUES	(	'".$materialComputationId."',	'".$number."',	'".$workingQuantity."')";
                                    //~ $queryInsert = $db->query($sql);
                                    
                                    $sql = "INSERT INTO engineering_booking
                                                    (	inventoryId,		bookingQuantity,	bookingDate,	bookingTime,	bookingStatus,	nestingType)
                                            VALUES	(	'".$inventoryId."',	".$sheetCount.",	now(),			now(),			2,				2)";
                                    //~ $insertQuery = $db->query($sql);
                                    if($insertQuery)
                                    {
                                        $bookingId = $db->insert_id;
                                        
                                        $sql = "INSERT INTO	engineering_bookingdetails
                                                        (	bookingId,		lotNumber,								quantity,				status,	materialRequirement)
                                                VALUES	(	".$bookingId.", '".$materialComputationTemporaryLot."',	".$workingQuantity.", 	0,		".$sheetCount.")";
                                        //~ $insertQuery = $db->query($sql);
                                    }
                                    
                                    $inventoryIdArray[] = $inventoryId;
                                    
                                    $initialWorkingQuantity -= $workingQuantity;
                                    
                                    if($initialWorkingQuantity > 0)
                                    {
                                        $repeatFlag = 1;
                                    }
                                    
                                    break;
                                }
                                else
                                {
                                    $inventoryId = 'No Material';
                                }
                            }
                            
                            if($inventoryId=='No Material')
                            {
                                if($sheetCount > 0)
                                {
                                    $sheetCount--;
                                    $workingQuantity -= $quantityPerSheet;
                                    $repeatFlag = 1;
                                }
                                else
                                {
                                    $sheetCount = '';
                                    $dataThree = '';
                                    $dataFour = '';
                                    $sheetCount = '';
                                    $quantityPerSheet = 0;
                                    $bookingId = '';
                                }
                            }
                        }
                        else
                        {
                            $quantityPerSheet = 0;
                        }
                    }
                }
            }
            
            //~ $inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" openTinyBox('','','gerald_subconMaterialBooking.php','materialComputationId=".$materialComputationId."'); \">".$inventoryId."</span>";
            
            $removeBookingButton = ($bookingId!='') ? "<img style='cursor:pointer;' onclick=\"unBookClass('".$materialComputationId."', '".$bookingId."');\" data-booking-id='".$bookingId."' data-material-computation-id='".$materialComputationId."' class='unBookClass' src='/".v."/Common Data/Templates/images/close1.png' height='10' title='Unbook'>" : "";
            
            $inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" TINY.box.show({url:'gerald_subconMaterialBooking.php',post:'materialComputationId=".$materialComputationId."&inventoryId=".$inventoryId."',openjs:function(){jsFunctions();}}); \">".$inventoryId."</span>".$removeBookingButton;
            
            $laserSelected = ($blankingProcess==381) ? 'selected' : '';
            $tppSelected = ($blankingProcess==86) ? 'selected' : '';
            
            $blankingProcessSelect = "
                    <select onchange=\"blankingProcessClass('".$materialComputationId."', this);\" data-material-computation-id='".$materialComputationId."' class='blankingProcessClass w3-input w3-border' name='blankingProcess[]'>
                        <option value='381' ".$laserSelected.">Laser</option>
                        <option value='86' ".$tppSelected.">TPP</option>
                    </select>
                ";
            
            $inventoryIdSpan = implode("<br>",$inventoryIdArray);
            
            $additionalTD = "
                <td class='".$materialComputationId."' data-one='one'>".$inventoryIdSpan."</td>
                <td>".$blankingProcessSelect."</td>
            ";

            $nestedData[] = $inventoryIdSpan;
            $nestedData[] = $blankingProcessSelect;
        }
        $data[] = $nestedData;
    }
}

if($submitType!='')
{
    echo "<form action = '/".v."/54 Automated Material Computation Software/anthony_computationSummaryConverter.php?pdf=2' method = 'POST' id = 'print' ></form>";
    echo "<input type = 'hidden' name = 'specId' value = '".$compressedInput."' form='print'>";
    echo "<input type = 'image' id='printId' src = '/".v."/Common Data/Templates/buttons/printIcon.png' height = '35' width = '50' form='print'>";
    ?><script>document.getElementById('print').submit();</script><?php
}

if($submitType == '')
{
    $json_data = array(
        "draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval( $totalData ),  // total number of records
        "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $data   // total data array
    );

    echo json_encode($json_data);  // send data as json format
}