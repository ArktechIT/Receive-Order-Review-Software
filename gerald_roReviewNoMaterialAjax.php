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
$sqlData = isset($requestData['sqlData']) ? $requestData['sqlData'] : "";
$submitType = (isset($_POST['submitType'])) ? $_POST['submitType'] : '';
$totalRecords = (isset($requestData['totalRecords'])) ? $requestData['totalRecords'] : 0;
$totalFiltered = $totalRecords;
$totalData = $totalFiltered;

$data = array();

$sql = $sqlData;
if($submitType != "calculate")
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
        $lotNumber = $result['lotNumber'];
        $poId = $result['poId'];
        $partId = $result['partId'];
        $workingQuantity = $result['workingQuantity'];
        $partLevel = $result['partLevel'];
        $patternId = $result['patternId'];
        
        $poNumber = $customerId = '';
        $sql = "SELECT poNumber, customerId FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
        $queryPoList = $db->query($sql);
        if($queryPoList AND $queryPoList->num_rows > 0)
        {
            $resultPoList = $queryPoList->fetch_assoc();
            $poNumber = $resultPoList['poNumber'];
            $customerId = $resultPoList['customerId'];
        }
        
        $partNumber = $revisionId = $partNote = $materialSpecId = $PVC = $x = $y = $treatmentId = '';
        $requiredLength = $requiredWidth = 0;
        $sql = "SELECT partNote, partNumber, revisionId, partNote, customerId, materialSpecId, PVC, x, y, treatmentId, requiredLength, requiredWidth FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
        $queryParts = $db->query($sql);
        if($queryParts AND $queryParts->num_rows > 0)
        {
            $resultParts = $queryParts->fetch_assoc();
            $partNumber = $resultParts['partNumber'];
            $revisionId = $resultParts['revisionId'];
            $partNote = $resultParts['partNote'];
            //$customerId = $resultParts['customerId'];
            $materialSpecId = $resultParts['materialSpecId'];
            $PVC = $resultParts['PVC'];
            $x = $resultParts['x'];
            $y = $resultParts['y'];
            $treatmentId = $resultParts['treatmentId'];
            $requiredLength = $resultParts['requiredLength'];
            $requiredWidth = $resultParts['requiredWidth'];
            $partNote = $resultParts['partNote'];
        }
        
        $customerAlias = '';
        $sql = "SELECT customerAlias FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
        $queryCustomer = $db->query($sql);
        if($queryCustomer AND $queryCustomer->num_rows > 0)
        {
            $resultCustomer = $queryCustomer->fetch_assoc();
            $customerAlias = $resultCustomer['customerAlias'];
        }
        
        $materialTypeId = $metalThickness = '';
        $sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
        $queryMaterialSpecs = $db->query($sql);
        if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
        {
            $resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
            $materialTypeId = $resultMaterialSpecs['materialTypeId'];
            $metalThickness = $resultMaterialSpecs['metalThickness'];
        }
        
        $materialType = '';
        $sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
        $queryMaterialType = $db->query($sql);
        if($queryMaterialType AND $queryMaterialType->num_rows > 0)
        {
            $resultMaterialType = $queryMaterialType->fetch_assoc();
            $materialType = $resultMaterialType['materialType'];
        }
        
        $treatmentName = '';
        $sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId = ".$treatmentId." LIMIT 1";
        $queryTreatmentProcess = $db->query($sql);
        if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
        {
            $resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
            $treatmentName = $resultTreatmentProcess['treatmentName'];
        }
        
        $sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode = 1 LIMIT 1";
        $queryCheckBend = $db->query($sql);
        $bendStatus = ($queryCheckBend->num_rows > 0) ? 'Yes' : 'No';
        
        $pvcStatus = ($PVC=='1') ? 'Yes' : 'No';
        
        $dateNeededTemp = '0000-00-00';
        $sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(312,430,431,432) AND status = 0";
        $queryWorkSchedule = $db->query($sql);
        if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
        {
            $resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
            $dateNeededTemp = $resultWorkSchedule['targetFinish'];
        }
        
        $blankingProcessCodeArray = ($_GET['country']==2) ? array(314,372,378,381,382,401,403,499) : array(86,52,381,98,392);
        
        $processCode = $firstProcessCode = '';
        $sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode NOT IN(312,430,431,432,136) AND patternId = ".$patternId." ORDER BY processOrder LIMIT 1";
        $queryPartProcess = $db->query($sql);
        if($queryPartProcess->num_rows > 0)
        {
            $resultPartProcess = $queryPartProcess->fetch_array();
            $firstProcessCode = $resultPartProcess['processCode'];
        }
        
        if(in_array($firstProcessCode,$blankingProcessCodeArray))
        {
            $processCode = $firstProcessCode;
        }
        
        if($_GET['country']==1)
        {
            $blankingProcess = '';
            if($processCode==86)
            {
                $blankingProcess = 'TPP';
            }
            else if($processCode==381)
            {
                $blankingProcess = 'Laser';
            }
            else if($processCode==52)
            {
                $blankingProcess = 'Press';
            }
            else if(in_array($processCode,array(328,98,392)))
            {
                $blankingProcess = 'Cutting';
            }
        }
        else if($_GET['country']==2)
        {
            $blankingProcess = '';
            if(in_array($processCode,array(372,381,382,401,403,499)))
            {
                $blankingProcess = 'TPP';
            }
            else if(in_array($processCode,array(314,378)))
            {
                $blankingProcess = 'Laser';
            }
        }
        
        $matLength = ($requiredLength > 0) ? $requiredLength : '';
        $matWidth = ($requiredWidth > 0) ? $requiredWidth : '';
        
        if($customerId==45 AND $treatmentId > 0)
        {
            if($materialType=='2024T3P')
            {
                if($matLength==2438 AND $matWidth==1219)
                {
                    $matLength = $matWidth = '';
                }
            }
            else
            {
                if($matLength==2000 AND $matWidth==1000)
                {
                    $matLength = $matWidth = '';
                }
            }
        }
        
        $inputFlag = 0;
        if(($matLength=='' OR $matWidth=='') AND $blankingProcess!='')
        {
            $inputFlag = 1;
            
            if($_GET['country']==1)
            {
                //material sizes
                if($customerId==45)
                {
                    if($materialType=='2024T3P')
                    {
                        if($treatmentId > 0)
                        {
                            $matLength = 1219;
                            $matWidth = 609;
                        }
                        else
                        {
                            $matLength = 2438;
                            $matWidth = 1219;
                        }
                    }
                    else
                    {
                        if($treatmentId > 0)
                        {
                            $matLength = 1000;
                            $matWidth = 1000;
                        }
                        else
                        {
                            $matLength = 2000;
                            $matWidth = 1000;
                        }
                    }
                }
                else
                {
                    if(in_array($metalThickness, array(1.5,0.8)) AND $materialType == '1050A H14')
                    {
                        $matLength = 2500;
                        $matWidth = 1250;
                    }
                    // else if(in_array($metalThickness, array(0.7,0.5,0.71,0.8,2.5,1.2)) AND $materialType == '6082T6')
                    else if(in_array($metalThickness, array(0.7,0.5,0.71,0.8,2.5,1.2)) AND stristr($materialType,'6082T6')!==FALSE)
                    {
                        //~ $matLength = 2000;
                        //~ $matWidth = 1000;
                        //With treated 1000 x 1000
                        
                        if($treatmentId > 0)
                        {
                            $matLength = 1000;
                            $matWidth = 1000;
                        }
                        else
                        {							
                            $matLength = 2000;
                            $matWidth = 1000;
                        }
                    }
                    // else if(in_array($metalThickness, array(1.5,3.0,1.0,2.0)) AND $materialType == '6082T6')
                    else if(in_array($metalThickness, array(1.5,3.0,1.0,2.0)) AND stristr($materialType,'6082T6')!==FALSE)
                    {
                        // if($blanking == 'Laser')
                        // {
                            $matLength = 2500;
                            $matWidth = 1250;
                        // }
                        // else if($blanking == 'TPP')
                        // {
                        // 	$length = 1250;
                        // 	$width = 625;
                        // }
                        
                        // with treated 1250 x 609
                        
                        if($treatmentId > 0)
                        {
                            $matLength = 1250;
                            $matWidth = 609;
                        }
                        else
                        {							
                            $matLength = 2500;
                            $matWidth = 1250;
                        }
                        
                    }
                    // else if($metalThickness == 0.9 AND $materialType == '6082T6')
                    else if($metalThickness == 0.9 AND stristr($materialType,'6082T6')!==FALSE)
                    {
                        $matLength = 1524;
                        $matWidth = 609;
                    }
                    else if(in_array($metalThickness, array(1.0,0.7)) AND in_array($materialType, array('MS2007','MS2009')))
                    {
                        $matLength = 1220;
                        $matWidth = 609;
                    }
                    else if(in_array($metalThickness, array(0.8,1.0,0.7,1.6,0.9,1.2,1.5,0.5)) AND in_array($materialType, array('SUS 304','SUS 304L','SUS 304 2B','SUS304 SGB3')))
                    {
                        $matLength = 2500;
                        $matWidth = 1250;
                    }
                    else if($materialType == '2024-T3')
                    {
                        $matLength = 3657;
                        $matWidth = 1219;
                    }
                    else if($materialType == 'SPHC')
                    {
                        $matLength = 1254;
                        $matWidth = 1219;
                    }
                    else if(in_array($materialType, array('SPCC-SD','SPCC')))
                    {
                        $matLength = 1219;
                        $matWidth = 1000;
                    }
                    else if(in_array($metalThickness, array(1.2, 1.0)) AND in_array($materialType, array('SECC1', 'SECC')))
                    {
                        $matLength = 1219;
                        $matWidth = 1000;
                    }

                    if(trim($matLength)=="" or trim($matWidth)=="" or $materialType=="")
                    {
                        $materialTypeId = '';
                        $sql = "SELECT suppliermaterialID FROM purchasing_materialtype WHERE materialType LIKE '".$materialType."' LIMIT 1";
                        $queryMaterialType = $db->query($sql);
                        if($queryMaterialType AND $queryMaterialType->num_rows > 0)
                        {
                            $resultMaterialType = $queryMaterialType->fetch_assoc();
                            $materialTypeId = $resultMaterialType['suppliermaterialID'];
                        }
                        
                        $sql = "SELECT length, width FROM purchasing_material WHERE materialTypeId = ".$materialTypeId." AND thickness = ".$metalThickness." AND length >= ".$x." AND width >= ".$y." LIMIT 1";
                        $queryMaterial = $db->query($sql);
                        if($queryMaterial AND $queryMaterial->num_rows > 0)
                        {
                            $resultMaterial = $queryMaterial->fetch_assoc();
                            $matLength = $resultMaterial['length'];
                            $matWidth = $resultMaterial['width'];
                        }
						else
						{
							$sql = "SELECT requiredLength, requiredWidth FROM cadcam_parts WHERE materialSpecId = ".$materialSpecId." AND treatmentId = ".$treatmentId." AND requiredLength >= ".$x." AND requiredWidth >= ".$y." LIMIT 1";
							$queryMaterial = $db->query($sql);
							if($queryMaterial AND $queryMaterial->num_rows > 0)
							{
								$resultMaterial = $queryMaterial->fetch_assoc();
								$matLength = $resultMaterial['requiredLength'];
								$matWidth = $resultMaterial['requiredWidth'];
							}
						}
                    }
                }
            }
            
            $sql = "UPDATE cadcam_parts SET requiredLength = ".$matLength.", requiredWidth = ".$matWidth." WHERE partId = ".$partId." LIMIT 1";
            $queryUpdate = $db->query($sql);
        }
        
        $rodFlag = 0;
        if($_GET['country']==1)
        {
			if(in_array($materialSpecId,array(1390,1639,1640,1514,1515,1696)))	$rodFlag = 1;
		}        
        
        $excemptFlag = 0;
        $materialLengthInput = $materialWidthInput = "";
        $qtyPerSheetInput = $qtyPerSheet = $requirement = 0;
        //~ if((($matLength > 0 AND $matWidth > 0) OR $inputFlag==1) AND $blankingProcess!='' AND !in_array($materialSpecId,array(1390,1639,1640,1514,1515,1696)))
        if((($matLength > 0 AND $matWidth > 0) OR $inputFlag==1) AND $blankingProcess!='' AND $rodFlag==0)
        {
            $qtyPerSheetInput = $qtyPerSheet = computeQtyPerSheet($x,$y,$matLength,$matWidth,$blankingProcess,$customerId,$partId);
            
            $disableInput = '';
            if($requiredLength!='' AND $requiredWidth!='' AND $blankingProcess!='')
            {
                $sql = "SELECT length, width, quantityPerSheet FROM engineering_quantitypersheet WHERE partId = ".$partId." AND blankingProcess = ".$processCode." AND length = ".$requiredLength." AND width = ".$requiredWidth." LIMIT 1";
                $queryQuantityPerSheet = $db->query($sql);
                if($queryQuantityPerSheet AND $queryQuantityPerSheet->num_rows > 0)
                {
                    $resultQuantityPerSheet = $queryQuantityPerSheet->fetch_assoc();
                    $matLength = $resultQuantityPerSheet['length'];
                    $matWidth = $resultQuantityPerSheet['width'];
                    $qtyPerSheet = $resultQuantityPerSheet['quantityPerSheet'];
                    
                    $qtyPerSheetInput = "<span style='color:blue;' title='Fixed'>".$qtyPerSheet."</span>";
                    
                    $excemptFlag = 1;
                }
            }
            
            //~ if($_SESSION['idNumber']=='0346')
            //~ {
				//~ $blankingProcess = "((($matLength > 0 AND $matWidth > 0) OR $inputFlag==1) AND $blankingProcess!='' AND $rodFlag==0)";
			//~ }
            
            //~ $materialLengthInput = "<input type='number' data-part-id='".$partId."' class='length api-form' name='matLength[]' value='".$matLength."' style='width:100px;' step='any' ".$disableInput." form='computeFormId'>";
            //~ $materialWidthInput = "<input type='number' data-part-id='".$partId."' class='width api-form' name='matWidth[]' value='".$matWidth."' style='width:100px;' step='any' ".$disableInput." form='computeFormId'>";				
            
            $materialLengthInput = "<input type='number' onchange=\"lengthWidth('".$partId."', '".$workingQuantity."', this.value, 'length');\" data-part-id='".$partId."' class='length w3-input w3-border' name='matLength[]' value='".$matLength."' style='width:100px;' step='any' form='computeFormId'>";
            $materialWidthInput = "<input type='number' onchange=\"lengthWidth('".$partId."', '".$workingQuantity."', this.value, 'width');\" data-part-id='".$partId."' class='width w3-input w3-border' name='matWidth[]' value='".$matWidth."' style='width:100px;' step='any' form='computeFormId'>";				
            
            $requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0;
            
            if($submitType!='')
            {
                if($requirement <= 0 AND $excemptFlag==0)
                {
                    echo "<form action='gerald_roReviewNoMaterial.php' method='POST' id='formForm'></form>";
                    echo "<input type='hidden' name='partNumber' value='".$partNumber."' form='formForm'>";
                    ?>
                    <script>
                        alert("Zero requirement detected for Part Number <?php echo $partNumber;?>");
                        document.getElementById('formForm').submit();
                    </script>
                    <?php
                    exit(0);
                }
            }
        }
        
        $dateNeeded = $dateNeededTemp;
        
        if($requirement > 0)
        {
            $arrayKey = $materialType."`".$metalThickness."`".$matLength."`".$matWidth."`".$treatmentName."`".$pvcStatus."`".$customerAlias;
            
            if(!isset($dataDateNeededArray[$arrayKey])) $dataDateNeededArray[$arrayKey] = array();
            
            $dataDateNeededArray[$arrayKey][] = $dateNeeded;
            
            if(!isset($requirementArray[$arrayKey])) $requirementArray[$arrayKey] = 0;
            $requirementArray[$arrayKey] += $requirement;
            
            if(!isset($poIdArray[$arrayKey])) $poIdArray[$arrayKey] = array();
            $poIdArray[$arrayKey][] = $poId;
            
            if(!isset($lotNumberArray[$arrayKey])) $lotNumberArray[$arrayKey] = array();
            $lotNumberArray[$arrayKey][] = $lotNumber;
            
            $requirementDataArray[$lotNumber] = $requirement;
        }

        $nestedData = [];
        $nestedData[] = "<input type='checkbox'><br></b>".++$counter."</b>";
        $nestedData[] = $customerAlias;
        $nestedData[] = $poNumber;
        $nestedData[] = $lotNumber;
        $nestedData[] = $partNumber;
        $nestedData[] = $revisionId;
        $nestedData[] = $partNote;
        $nestedData[] = $workingQuantity;
        $nestedData[] = $materialType;
        $nestedData[] = $metalThickness;
        $nestedData[] = $treatmentName;
        $nestedData[] = $x;
        $nestedData[] = $y;
        $nestedData[] = $bendStatus;
        $nestedData[] = $pvcStatus;
        $nestedData[] = $blankingProcess;
        $nestedData[] = $materialLengthInput.$asdasd;
        $nestedData[] = $materialWidthInput;
        //~ $nestedData[] = $qtyPerSheet;
        $nestedData[] = $qtyPerSheetInput;
        $nestedData[] = $requirement;
        $data[] = $nestedData;
    }

    if($submitType=='calculate')
    {
        if(count($requirementArray) > 0)
        {
            $materialComputationIdArray = array();
            $sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE lotNumber LIKE '' AND status = 2";
            $queryMaterialComputation = $db->query($sql);
            if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
            {
                while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
                {
                    $materialComputationIdArray[] = $resultMaterialComputation['materialComputationId'];
                }
                
                $bookingIdArray = array();
                $sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber IN('".implode("','",$materialComputationIdArray)."')";
                $queryBookingDetails = $db->query($sql);
                if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
                {
                    while($resultBookingDetails = $queryBookingDetails->fetch_assoc())
                    {
                        $bookingIdArray[] = $resultBookingDetails['bookingId'];
                    }
                }
                
                $sql = "DELETE FROM engineering_bookingdetails WHERE bookingId IN(".implode(",",$bookingIdArray).")";
                //~ $queryBooking = $db->query($sql);
                
                $sql = "DELETE FROM engineering_booking WHERE bookingId IN(".implode(",",$bookingIdArray).") AND bookingStatus = 2";
                //~ $queryBooking = $db->query($sql);
            }
            
            $materialComputationIdDeleteArray = array();
            $sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE lotNumber LIKE ''";
            $queryMaterialComputationDetails = $db->query($sql);
            if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
            {
                while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
                {
                    $materialComputationIdDeleteArray[] = $resultMaterialComputationDetails['materialComputationId'];
                }
                
                $sql = "DELETE FROM ppic_materialcomputationdetails WHERE materialComputationId IN(".implode(",",$materialComputationIdDeleteArray).")";
                $queryDelete = $db->query($sql);
            }
            
            $sql = "DELETE FROM ppic_materialcomputation WHERE lotNumber LIKE ''";
            $queryDelete = $db->query($sql);
            
            $sqlMain = "INSERT INTO `ppic_materialcomputation`(`customerAlias`, `materialType`, `thickness`, `length`, `width`, `treatment`, `pvc`, `quantity`, `finalQuantity`, `status`, `idnumber`, `inputDateTime`, `lotNumber`, `dateNeeded`) VALUES ";
            $sqlValuesArray = array();
            $counter = 0;
            $compressedInput = '';	
            foreach($requirementArray as $key=>$val)
            {
                $keyArray = explode("`",$key);
                
                $statusArray = array();
                $statusArray[] = "<option value='0'>Not Set</option>";
                $statusArray[] = "<option value='1'>".displayText('L1345')."</option>";
                if($keyArray[5] != 'Raw')
                {
                    $statusArray[] = "<option value='2'>For Subcon</option>";
                    
                    $primeFlag = (strstr($keyArray[5],'prime')!==FALSE) ? 1 : 0;
                    
                    if($primeFlag==1 AND strstr($keyArray[6],'jamco')!==FALSE)
                    {
                        $statusArray[] = "<option value='3'>For Internal Prime</option>";
                    }
                }
                $statusArray[] = "<option value='4'>For Customer Request</option>";
                $statusArray[] = "<option value='5'>Open PO</option>";
                
                $pvcStatus = ($keyArray[5]=='Yes') ? 1 : 0;
                
                $status = 0;
                
                $materialParameter = 0;
                $sql = "SELECT materialParameter FROM sales_customer WHERE customerAlias = '".$keyArray[6]."' LIMIT 1";
                $queryCustomer = $db->query($sql);
                if($queryCustomer AND $queryCustomer->num_rows > 0)
                {
                    $resultCustomer = $queryCustomer->fetch_assoc();
                    $materialParameter = $resultCustomer['materialParameter'];
                }
                
                if($materialParameter==0)	$status = 1;
                else if($materialParameter==1)	$status = 4;
                
                $sqlValues = "('".$keyArray[6]."','".$keyArray[0]."','".$keyArray[1]."','".$keyArray[2]."','".$keyArray[3]."','".$keyArray[4]."','".$pvcStatus."','".ceil($val)."','".ceil($val)."','".$status."','".$_SESSION['idNumber']."',NOW(),'','".$dateNeeded."')";
                
                $sqlValuesArray[] = $sqlValues;
                $counter++;
                if($counter==50)
                {
                    $sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
                    $queryUpdate = $db->query($sqlInsert);
                    $sqlValuesArray = array();
                    $counter = 0;
                }						
                
                $yonko = "<select class='statusClass api-form'>".implode("",$statusArray)."</select>";
                
                if($compressedInput == "")
                {
                    $compressedInput = $keyArray[0]."`".$keyArray[1]."`".$keyArray[2]."`".$keyArray[3]."`".ceil($val)."`".$keyArray[4]."`".$keyArray[5];
                }
                else
                {
                    $compressedInput = $compressedInput."`".$keyArray[0]."`".$keyArray[1]."`".$keyArray[2]."`".$keyArray[3]."`".ceil($val)."`".$keyArray[4]."`".$keyArray[5];
                }
            }

            if($counter > 0)
            {
                $sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
                $queryUpdate = $db->query($sqlInsert);
            }
            
            $arrayKey = $materialType."`".$metalThickness."`".$matLength."`".$matWidth."`".$treatmentName."`".$pvcStatus."`".$customerAlias;
            $sql = "SELECT materialComputationId, CONCAT(materialType,'`',thickness,'`',length,'`',width,'`',treatment,'`',IF(pvc=1,'Yes','No'),'`',customerAlias) as uniqueKey FROM ppic_materialcomputation WHERE lotNumber = ''";
            $queryMaterialComputation = $db->query($sql);
            if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
            {
                while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
                {
                    $materialComputationId = $resultMaterialComputation['materialComputationId'];
                    $uniqueKey = $resultMaterialComputation['uniqueKey'];
                    
                    $sql = "UPDATE ppic_roreviewdatatemp SET materialComputationId = '".$materialComputationId."' WHERE poId IN(".implode(",",$poIdArray[$uniqueKey]).")";
                    $queryUpdate = $db->query($sql);
                    
                    $sql = "INSERT INTO ppic_materialcomputationdetails
                                    (	`materialComputationId`,		`lotNumber`,	`workingQuantity`)
                            SELECT 		'".$materialComputationId."',	`lotNumber`,	`workingQuantity`
                            FROM		ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray[$uniqueKey])."')";
                    $queryInsert = $db->query($sql);
                    
                    $reqNestongArray = array();
                    $sql = "SELECT listId, lotNumber FROM ppic_materialcomputationdetails WHERE materialComputationId = ".$materialComputationId."";
                    $queryMaterialComputationDetails = $db->query($sql);
                    if($queryMaterialComputationDetails AND $queryMaterialComputationDetails->num_rows > 0)
                    {
                        while($resultMaterialComputationDetails = $queryMaterialComputationDetails->fetch_assoc())
                        {
                            $listId = $resultMaterialComputationDetails['listId'];
                            $lotNumber = $resultMaterialComputationDetails['lotNumber'];
                            
                            $requirement = $requirementDataArray[$lotNumber];
                            
                            $nestingProcess = 0;
                            $nestingDate = '0000-00-00';
                            $sql = "SELECT processCode, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(312,430,431,432) AND status = 0 LIMIT 1";
                            $queryWorkSchedule = $db->query($sql);
                            if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
                            {
                                $resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
                                $nestingProcess = $resultWorkSchedule['processCode'];
                                $nestingDate = $resultWorkSchedule['targetFinish'];
                            }
                            
                            if(!isset($reqNestongArray[$nestingProcess])) $reqNestongArray[$nestingProcess] = 0;
                            $reqNestongArray[$nestingProcess] += $requirement;
                            
                            $sql = "UPDATE ppic_materialcomputationdetails SET requirement = ".$requirement.", nestingDate = '".$nestingDate."', nestingProcess = '".$nestingProcess."' WHERE listId = ".$listId." LIMIT 1";
                            $queryUpdate = $db->query($sql);
                        }
                        
                        $totalReq = 0;
                        if(count($reqNestongArray) > 1)
                        {
							foreach($reqNestongArray as $req)
							{
								$totalReq += ceil($req);
							}
							
							$sql = "UPDATE ppic_materialcomputation SET quantity = '".$totalReq."', finalQuantity = '".$totalReq."' WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
							$queryUpdate = $db->query($sql);
						}
                    }
                }
            }
            
            header('location:gerald_roReviewMaterialComputation.php');
            exit(0);
        }
    }
}

if($submitType != 'calculate')
{
    $json_data = array(
        "draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
        "recordsTotal"    => intval( $totalData ),  // total number of records
        "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
        "data"            => $data   // total data array
    );

    echo json_encode($json_data);  // send data as json format
}
