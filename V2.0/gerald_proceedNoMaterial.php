<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='updateRequiredDimension')
		{
			$classType = $_POST['classType'];
			$partId = $_POST['partId'];
			$value = $_POST['value'];
			
			$field = ($classType=='length') ? 'requiredLength' : 'requiredWidth';
			$sql = "UPDATE cadcam_parts SET ".$field." = ".$value." WHERE partId = ".$partId." LIMIT 1";
			//~ $queryUpdate = $db->query($sql);
		}
		exit(0);
	}
	
	function createFilterInput($sqlFilter,$column,$value)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$return = "<option value=''>".displayText('L490')." </option>";
		$sqlOption = "SELECT DISTINCT ".$column." FROM ppic_lotlist ".$sqlFilter." ORDER BY ".$column."";
		
		if($column=='customerAlias')
		{
			$poIdArray = array();
			$sql = "SELECT DISTINCT poId FROM ppic_lotlist ".$sqlFilter;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$poIdArray[] = $resultLotList['poId'];
				}
			}
			
			$customerIdArray = array();
			$sql = "SELECT DISTINCT customerId FROM sales_polist WHERE poId IN(".implode(",",$poIdArray).")";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				while($resultPoList = $queryPoList->fetch_assoc())
				{
					$customerIdArray[] = $resultPoList['customerId'];
				}
			}
			$sqlOption = "SELECT DISTINCT customerAlias FROM sales_customer WHERE customerId IN(".implode(",",$customerIdArray).") ORDER BY customerAlias";
		}
		else if($column=='poNumber')
		{
			$poIdArray = array();
			$sql = "SELECT DISTINCT poId FROM ppic_lotlist ".$sqlFilter;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$poIdArray[] = $resultLotList['poId'];
				}
			}
			
			$sqlOption = "SELECT DISTINCT poNumber FROM sales_polist WHERE poId IN(".implode(",",$poIdArray).") ORDER BY poNumber";
		}
		else if($column=='partNumber')
		{
			$partIdArray = array();
			$sql = "SELECT DISTINCT partId FROM ppic_lotlist ".$sqlFilter;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$partIdArray[] = $resultLotList['partId'];
				}
			}
			
			$sqlOption = "SELECT DISTINCT partNumber FROM cadcam_parts WHERE partId IN(".implode(",",$partIdArray).") ORDER BY partNumber";
		}
		else if($column=='materialType' OR $column=='metalThickness')
		{
			$partIdArray = array();
			$sql = "SELECT DISTINCT partId FROM ppic_lotlist ".$sqlFilter;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$partIdArray[] = $resultLotList['partId'];
				}
			}
			
			$materialSpecIdArray = array();
			$sql = "SELECT DISTINCT materialSpecId FROM cadcam_parts WHERE partId IN(".implode(",",$partIdArray).")";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				while($resultParts = $queryParts->fetch_assoc())
				{
					$materialSpecIdArray[] = $resultParts['materialSpecId'];
				}
			}
			
			if($column=='metalThickness')
			{
				$sqlOption = "SELECT DISTINCT metalThickness FROM cadcam_materialspecs WHERE materialSpecId IN(".implode(",",$materialSpecIdArray).") ORDER BY metalThickness";
			}
			else
			{
				$materialTypeIdArray = array();
				$sql = "SELECT DISTINCT materialTypeId FROM cadcam_materialspecs WHERE materialSpecId IN(".implode(",",$materialSpecIdArray).")";
				$queryMaterialSpecs = $db->query($sql);
				if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
				{
					while($resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc())
					{
						$materialTypeIdArray[] = $resultMaterialSpecs['materialTypeId'];
					}
				}
				
				$sqlOption = "SELECT DISTINCT materialType FROM engineering_materialtype WHERE materialTypeId IN(".implode(",",$materialTypeIdArray).") ORDER BY materialType";
			}
		}
		else if($column=='treatmentName')
		{
			$partIdArray = array();
			$sql = "SELECT DISTINCT partId FROM ppic_lotlist ".$sqlFilter;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$partIdArray[] = $resultLotList['partId'];
				}
			}
			
			$treatmentIdArray = array();
			$sql = "SELECT DISTINCT treatmentId FROM cadcam_parts WHERE partId IN(".implode(",",$partIdArray).")";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$treatmentIdArray[] = $resultLotList['treatmentId'];
				}
			}
			
			$sqlOption = "SELECT DISTINCT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId IN(".implode(",",$treatmentIdArray).") ORDER BY treatmentName";
		}
		else if($column=='PVC')
		{
			$partIdArray = array();
			$sql = "SELECT DISTINCT partId FROM ppic_lotlist ".$sqlFilter;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$partIdArray[] = $resultLotList['partId'];
				}
			}
			
			$sqlOption = "SELECT DISTINCT PVC FROM cadcam_parts WHERE partId IN(".implode(",",$partIdArray).")";
		}
		$query = $db->query($sqlOption);
		if($query->num_rows > 0)
		{
			while($result = $query->fetch_array())
			{
				$valueColumn = $valueCaption = $result[$column];
				
				if($column=='PVC')
				{
					if($valueColumn==0)			$valueCaption = 'No';
					else if($valueColumn==1)	$valueCaption = 'Yes';
				}
				
				$selected = ($value==$result[$column]) ? 'selected' : '';
				
				$return .= "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
			}
		}
		return $return;
	}
	
	$poIdArray = array();
	$sql = "SELECT poId FROM ppic_proceednomaterial";
	$queryProceedNoMaterial = $db->query($sql);
	if($queryProceedNoMaterial AND $queryProceedNoMaterial->num_rows > 0)
	{
		while($resultProceedNoMaterial = $queryProceedNoMaterial->fetch_assoc())
		{
			$poIdArray[] = $resultProceedNoMaterial['poId'];
		}
	}	
	
	$fontSize = (isset($_POST['fontSize'])) ? $_POST['fontSize'] : 14;
	
	$poIds = (isset($_POST['poIds'])) ? $_POST['poIds'] : '';
	$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
	$poNumber = (isset($_POST['poNumber'])) ? $_POST['poNumber'] : '';
	$partNumber = (isset($_POST['poNumber'])) ? $_POST['partNumber'] : '';
	$materialType = (isset($_POST['poNumber'])) ? $_POST['materialType'] : '';
	$metalThickness = (isset($_POST['metalThickness'])) ? $_POST['metalThickness'] : '';
	$treatmentName = (isset($_POST['treatmentName'])) ? $_POST['treatmentName'] : '';
	$itemX = (isset($_POST['itemX'])) ? $_POST['itemX'] : '';
	$itemY = (isset($_POST['itemY'])) ? $_POST['itemY'] : '';
	$PVC = (isset($_POST['PVC'])) ? $_POST['PVC'] : '';
	
	if($customerAlias!='')
	{
		$customerId = '';
		$sql = "SELECT customerId FROM sales_customer WHERE customerAlias LIKE '".$customerAlias."' LIMIT 1";
		$queryCustomer = $db->query($sql);
		if($queryCustomer AND $queryCustomer->num_rows > 0)
		{
			$resultCustomer = $queryCustomer->fetch_assoc();
			$customerId = $resultCustomer['customerId'];
		}		
		
		$poListSqlFilterArray[] = "customerId = ".$customerId."";
	}
	if($poNumber!='')	$poListSqlFilterArray[] = "poNumber LIKE '".$poNumber."'";
	
	if(count($poListSqlFilterArray) > 0)
	{
		$poListSqlFilter = "WHERE poId IN(".implode(",",$poIdArray).") AND ".implode(" AND ",$poListSqlFilterArray)." ";
	
		$filteredPoIdArray = array();
		$sql = "SELECT poId FROM sales_polist ".$poListSqlFilter;
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			while($resultPoList = $queryPoList->fetch_assoc())
			{
				$filteredPoIdArray[] = $resultPoList['poId'];
			}
		}
		$poIdArray = $filteredPoIdArray;
	}
	
	if($materialType!='')
	{
		$materialTypeId = '';
		$sql = "SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '".$materialType."' LIMIT 1";
		$queryMaterialType = $db->query($sql);
		if($queryMaterialType AND $queryMaterialType->num_rows > 0)
		{
			$resultMaterialType = $queryMaterialType->fetch_assoc();
			$materialTypeId = $resultMaterialType['materialTypeId'];
		}
		
		$materialSpecsSqlFilterArray[] = "materialTypeId = ".$materialTypeId."";
	}
	if($metalThickness!='')	$materialSpecsSqlFilterArray[] = "metalThickness = ".$metalThickness."";
	
	if(count($materialSpecsSqlFilterArray) > 0)
	{
		$materialSpecIdArray = array();
		$sql = "SELECT materialSpecId FROM cadcam_materialspecs WHERE ".implode(" AND ",$materialSpecsSqlFilterArray);
		$queryMaterialSpecs = $db->query($sql);
		if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
		{
			while($resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc())
			{
				$materialSpecIdArray[] = $resultMaterialSpecs['materialSpecId'];
			}
		}
		
		$partsSqlFilterArray[] = "materialSpecId IN(".implode(",",$materialSpecIdArray).")";
	}
	
	if($treatmentName!='')
	{
		$treatmentId = '';
		$sql = "SELECT treatmentId FROM cadcam_treatmentprocess WHERE treatmentName LIKE '".$treatmentName."' LIMIT 1";
		$queryMaterialType = $db->query($sql);
		if($queryMaterialType AND $queryMaterialType->num_rows > 0)
		{
			$resultMaterialType = $queryMaterialType->fetch_assoc();
			$treatmentId = $resultMaterialType['treatmentId'];
		}
		
		$partsSqlFilterArray[] = "treatmentId = ".$treatmentId."";
	}
	
	if($partNumber!='')	$partsSqlFilterArray[] = "partNumber LIKE '".$partNumber."'";
	if($itemX!='')	$partsSqlFilterArray[] = "x = ".$itemX."";
	if($itemY!='')	$partsSqlFilterArray[] = "y = ".$itemY."";
	if($PVC!='')	$partsSqlFilterArray[] = "PVC = ".$PVC."";
	
	if(count($partsSqlFilterArray) > 0)
	{
		$partIdArray = array();
		$sql = "SELECT partId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND identifier = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$partIdArray[] = $resultLotList['partId'];
			}
		}
		
		$partsSqlFilter = "WHERE partId IN(".implode(",",$partIdArray).") AND ".implode(" AND ",$partsSqlFilterArray)." ";
	
		$filteredPartIdArray = array();
		$sql = "SELECT partId FROM cadcam_parts ".$partsSqlFilter;
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			while($resultPoList = $queryPoList->fetch_assoc())
			{
				$filteredPartIdArray[] = $resultPoList['partId'];
			}
		}
		if(count($filteredPartIdArray) > 0)	$sqlFilterArray[] = "partId IN(".implode(",",$filteredPartIdArray).")";
	}
	
	$sqlFilter = "WHERE poId IN(".implode(",",$poIdArray).") AND identifier = 1";
	if(count($sqlFilterArray) > 0)
	{
		$sqlFilter .= " AND ".implode(" AND ",$sqlFilterArray)." ";
	}
	
	$sqlFilter .= " GROUP BY poId, partId ORDER BY poId, partId";
	$sql = "SELECT lotNumber FROM ppic_lotlist ".$sqlFilter;
	$query = $db->query($sql);
	$totalRecords = ($query AND $query->num_rows > 0) ? $query->num_rows : 0;	
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('L1347');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/<?php echo v;?>/Common Data/Templates/api.css">
	<script src="/<?php echo v;?>/Common Data/Templates/api.js"></script>
	<style>
		.dropdown {
			position: relative;
			display: inline-block;
		}

		.dropdown-content {
			display: none;
			position: absolute;
			z-index: 1;
		}

		.dropdown:hover .dropdown-content {
			display: block;
		}
		.internalTrClass td {
			font-size:<?php echo $fontSize;?>px!important;
		}
	</style>
</head>
<body class='api-loading'>
	<form action='gerald_proceedNoMaterialAjax.php' method='post' target='windowMaterialComputation' onsubmit="return confirm('Are you sure you want to calculate?'); window.open('','windowMaterialComputation','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');return true;" id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
			<button class='api-btn api-btn-home' onclick="location.href='/<?php echo v;?>/dashboard.php';" data-api-title='<?php echo displayText('L434');?>' <?php echo toolTip('L434');?>></button>
		</div>
		
		<div class="api-top api-col api-title" style='width:35%;'>
			<h2 style='border-style:double;border-color:black;border-radius:2px;padding:0 2px;'><?php echo displayText('L1347');?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:35%'>
			<button class='api-btn' type='submit' name='submitType' value='calculate' style='width:33%' data-api-title='CALCULATE' form='exportFormId'></button>
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436');?>' <?php echo toolTip('L436');?>></button>
		</div>
		
		<div class="api-col" style='width:100%;height:92vh;'>
			<!-------------------- Filters -------------------->
			<form action='' method='post' id='formFilter' autocomplete="off"></form>	
			<table cellpadding="0" cellspacing="0" border="0" style='width:100%;'>
				<tr style='font-size:12px;'>
					<td style='width:10%' align='center' ><?php echo displayText('L24'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L25'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L28'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L566'); ?>	</td>
					<td style='width:10%' align='center' ><?php echo displayText('L184'); ?>	</td>
					<td style='width:10%' align='center' ><?php echo displayText('L67'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L70'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L71'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L306'); ?>	</td>
					<td align='left' style=''></td>
				</tr>
				<tr>
					<td><select name='customerAlias' class='api-form' style='width:78%;' value='<?php echo $customerAlias;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerAlias!='') echo 'background-color:red';?>'></td>
					<td><input list='poNumber' name='poNumber' class='api-form' style='width:78%;' value='<?php echo $poNumber;?>' form='formFilter'><datalist id='poNumber' class='classDataList'><?php echo createFilterInput($sqlFilter,'poNumber',$poNumber);?></datalist>&nbsp;			<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($poNumber!='') echo 'background-color:red';?>'></td>
					<td><input list='partNumber' name='partNumber' class='api-form' style='width:78%;' value='<?php echo $partNumber;?>' form='formFilter'><datalist id='partNumber' class='classDataList'><?php echo createFilterInput($sqlFilter,'partNumber',$partNumber);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($partNumber!='') echo 'background-color:red';?>'></td>
					<td><input list='materialType' name='materialType' class='api-form' style='width:78%;' value='<?php echo $materialType;?>' form='formFilter'><datalist id='materialType' class='classDataList'><?php echo createFilterInput($sqlFilter,'materialType',$materialType);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($materialType!='') echo 'background-color:red';?>'></td>
					<td><select name='metalThickness' class='api-form' style='width:78%;' value='<?php echo $metalThickness;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'metalThickness',$metalThickness);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($metalThickness!='') echo 'background-color:red';?>'></td>
					<td><select name='treatmentName' class='api-form' style='width:78%;' value='<?php echo $treatmentName;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'treatmentName',$treatmentName);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($treatmentName!='') echo 'background-color:red';?>'></td>
					<td><input type='text' name='itemX' class='api-form' style='width:78%;' value='<?php echo $itemX;?>' form='formFilter'>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($itemX!='') echo 'background-color:red';?>'></td>
					<td><input type='text' name='itemY' class='api-form' style='width:78%;' value='<?php echo $itemY;?>' form='formFilter'>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v;?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($itemY!='') echo 'background-color:red';?>'></td>
					<td><select name='PVC' class='api-form' style='width:78%;' value='<?php echo $PVC;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'PVC',$PVC);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($PVC!='') echo 'background-color:red';?>'></td>
					<td><button type='submit' class='api-btn' onclick="location.href='';" data-api-title='<?php echo displayText('B7');?>' <?php echo toolTip('L437');?> form='formFilter'></button></td>
				</tr>
			</table>
			<!------------------ End Filters ------------------>
			
			<!-------------------- Contents -------------------->
			
			<?php echo displayText('L41'); ?> : <span><?php echo $totalRecords; ?></span>&nbsp;&nbsp;&nbsp;&nbsp;
			<div style='height: 89%;'><!-- Adjust height if browser had a vertical scroll -->
				<table id='mainTableId' class="api-table-fixedheader api-table-design2" data-counter='-1' data-detail-type='left'>
					<thead>
						<tr>
							<th></th>
							<th <?php echo toolTip('L24');?>><?php echo displayText('L24');?></th>
							<th <?php echo toolTip('L24');?>><?php echo displayText('L25');?></th>
							<th <?php echo toolTip('L28');?>><?php echo displayText('L28');?></th>
							<th <?php echo toolTip('L226');?>><?php echo displayText('L226');?></th>
							<th <?php echo toolTip('L31');?>><?php echo displayText('L31');?></th>
							<th <?php echo toolTip('L566');?>><?php echo displayText('L566');?></th>
							<th <?php echo toolTip('L184');?>><?php echo displayText('L184');?></th>
							<th <?php echo toolTip('L67');?>><?php echo displayText('L67');?></th>
							<th <?php echo toolTip('L70');?>><?php echo displayText('L70');?></th>
							<th <?php echo toolTip('L71');?>><?php echo displayText('L71');?></th>
							<th <?php echo toolTip('L305');?>><?php echo displayText('L1492');?></th>
							<th <?php echo toolTip('L306');?>><?php echo displayText('L306');?></th>
							<th <?php echo toolTip('L74');?>><?php echo displayText('L74');?></th>
							<th <?php echo toolTip('L75');?>><?php echo displayText('L75');?></th>
							<th <?php echo toolTip('L307');?>><?php echo displayText('L307');?></th>
							<th <?php echo toolTip('L308');?>><?php echo displayText('L308');?></th>
						</tr>
					</thead>
					<tbody>
						
					</tbody>
					<tfoot>
						<tr>
							<th><input type='checkbox' name='checkall' id='chkAll'></th>
							<th><label for='chkAll'><?php echo displayText('L326'); ?></label></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
			</div>
			<!------------------ End Contents ------------------>			
			
		</div>
	</div>
</body>
<script src="/<?php echo v; ?>/Common Data/Templates/jquery.js"></script>
<script src="/<?php echo v; ?>/Common Data/Templates/api.jquery.js"></script>
<script>
	function colorThis(obj)
	{
		$("td.tempClass").css('background-color','');
		$("td").removeClass("tempClass");
		$(obj).parents("td").prop('class','tempClass');
		$("td.tempClass").css('background-color','orange');
	}
	
	$(function(){
		$("#mainTableId").apiQuickTable({
			url:'gerald_proceedNoMaterialAjax.php',
			filterSql:"<?php echo $sqlFilter." ".$sqlSort; ?>",
			recordCount:parseFloat("<?php echo $totalRecords/50;?>"),
			customFunction:function(){
				
				$("input.length,input.width").change(function(){
					var thisVal = $(this).val();
					var partId = $(this).data('partId');
					var classType = $(this).attr('class').split(" ")[0];
					/*
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						data:{
							ajaxType:'updateRequiredDimension',
							classType:classType,
							partId:partId,
							value:thisVal
						},
						success:function(data){
							//~ alert(data);
						}
					});*/
				});
				
			}
		});
		
		$("select.api-form").change(function(){
			if($(this).val()=='')	this.form.submit();
		});
		
		$('body').removeClass('api-loading');
		$(window).bind('beforeunload',function(){
			$('body').addClass('api-loading');
		});
	});
	
	//  -------------------------------------------------- For Modal Box Javascript Code -------------------------------------------------- //
	function jsFunctions(){
		
	}	
	//  ------------------------------------------------ END For Modal Box Javascript Code ------------------------------------------------ //
</script>
<!-- -----------------------------------Tiny Box------------------------------------------------------------- -->
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/stylebox.css" />
<script type="text/javascript">
function openTinyBox(w,h,url,post,iframe,html,left,top)
{
	var windowWidth = $(window).width();
	var windowHeight = $(window).height();
	TINY.box.show({
		url:url,width:w,height:h,post:post,html:html,opacity:20,topsplit:6,animate:false,close:true,iframe:iframe,left:left,top:top,
		boxid:'box',
		openjs:function(){
			if($("#tableDiv").length != 0 )
			{
				var windowHeight = $(window).height() / 1.5;
				var tinyBoxHeight = $("#box").height();
				if(tinyBoxHeight > (windowHeight))
				{
					$("#tableDiv").css({'overflow-y':'scroll','overflow-x':'hidden','height':(windowHeight) + 'px'});
					$("#box").css('height',(windowHeight) +'px');
					$("#box").css('width',($("#box").width() + 20 ) +'px');
				}
			}
		}
	});
}
</script>   
<!-- -----------------------------------END SMALL BOX----------------------------------------------------------------> 
</html>
