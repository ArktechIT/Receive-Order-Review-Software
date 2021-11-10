<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
    $path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
    set_include_path($path); 
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors", "on");
	
    $sqlFilter = isset($_POST['sqlFilter']) ? $_POST['sqlFilter'] : "";
    
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
    

    $sql = "SELECT id, lotNumber, targetFinish FROM view_workschedule WHERE processCode IN(459) AND status = 0";
    $viewWorkSechedule = $db->query($sql);
    while ($viewWorkSecheduleResult = $viewWorkSechedule->fetch_assoc())
    {
        $id = $viewWorkSecheduleResult['id'];
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
        $customerAliasArray[$customerAlias][] = $id;
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
?>
<input type='hidden' name='type' value='' form='formFilter'>
	<table cellpadding="0" cellspacing="0" style='width:100%; background-color:#ffb54d!important;' border='0'> 
		<tr style='font-size:12px;'>
			<td style='width:10%' align='center' ><?php echo displayText('L24');?><!-- CUSTOMER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L224');?><!-- PO NUMBER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L45');?><!-- LOT NUMBER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L28');?><!-- PART NUMBER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L30');?><!-- PART NAME --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L174');?><!-- MATERIAL --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L31');?><!-- QUANTITY --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L172');?><!-- STATUS --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L62');?><!-- TARGET DATE --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L711');?><!-- DELIVERY DATE --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L91');?><!-- SUBCON --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L1026');?><!-- BENDED --></td>
		</tr>
		<tr>
			<td align='center'>
				<!-- <input list='customerFilter' name='customerFilter' class='api-form' style='width:80%;' value='<?php echo $idNumber;?>' form='formFilter' autocomplete='off'> -->
				<select id = 'customerFilter' name='customerFilter' class='api-form' style='width:80%;' form='formFilter' >
                <?php
                    // $customerDataFilterArray = array_unique($customerAliasArray);
                    echo "<option></option>";
                    foreach ($customerAliasArray as $key => $value) 
                    {
                        if($key != "")
                        {
                            $valData = implode(",",$value);
                            echo "<option value='".$valData."'>".$key."</option>";
                        }
                    }
                ?>
				</select>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($idNumber!='') echo 'background-color:red';?>'>
			</td>
		</tr>
		
		<tr>
			<td colspan='2'>
				<center>
					<button type='submit' class='api-btn' onclick="location.href='';" data-api-title='<?php echo displayText('B7');?>' <?php echo toolTip('L437');?> form='formFilter'></button>
				</center>			
			</td>
		</tr>
	</table>
