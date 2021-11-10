<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	print_r($POST);
	if(isset($_POST['ajaxType']))
	{
		if($_POST['ajaxType']=='updateRequiredDimension')
		{
			$classType = $_POST['classType'];
			$partId = $_POST['partId'];
			$workingQuantity = $_POST['workingQuantity'];
			$value = $_POST['value'];
			
			$field = ($classType=='length') ? 'requiredLength' : 'requiredWidth';
			$sql = "UPDATE cadcam_parts SET ".$field." = ".$value." WHERE partId = ".$partId." LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$processCode = '';
			$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode IN(86,52,381,98,392) LIMIT 1";
			$queryPartProcess = $db->query($sql);
			if($queryPartProcess->num_rows > 0)
			{
				$resultPartProcess = $queryPartProcess->fetch_array();
				$processCode = $resultPartProcess['processCode'];
			}
			
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
			
			$customerId = $x = $y = '';
			$matLength = $matWidth = 0;
			$sql = "SELECT customerId, x, y, requiredLength, requiredWidth FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_assoc();
				$customerId = $resultParts['customerId'];
				$x = $resultParts['x'];
				$y = $resultParts['y'];
				$matLength = $resultParts['requiredLength'];
				$matWidth = $resultParts['requiredWidth'];
			}
			
			if($matLength > 0 AND $matWidth > 0 AND $blankingProcess!='')
			{
				$qtyPerSheet = computeQtyPerSheet($x,$y,$matLength,$matWidth,$blankingProcess,$customerId);
				$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0;
				
				echo $qtyPerSheet."|".$requirement;
			}
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
	
	//~ $inputDateTime = (isset($_GET['inputDateTime'])) ? $_GET['inputDateTime'] : '2017-12-07 10:20:57';
	$inputDateTime = (isset($_GET['inputDateTime'])) ? $_GET['inputDateTime'] : '';
	
	//~ if($_SESSION['idNumber']=='0346')
	//~ {
		//~ $inputDateTime = '';
	//~ }
	
	$poIdArray = array();
	$sql = "SELECT poId FROM ppic_roreviewdatatemp WHERE accessoryCheck = 1";
	if($inputDateTime!='')
	{
		$materialComputationIdArray = array();
		$sql = "SELECT materialComputationId FROM ppic_materialcomputation WHERE inputDateTime = '".$inputDateTime."'";
		$queryMaterialComputation = $db->query($sql);
		if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
		{
			while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
			{
				$materialComputationIdArray[] = $resultMaterialComputation['materialComputationId'];
			}
		}
		$sql = "SELECT poId FROM ppic_proceednomaterial WHERE materialComputationId IN(".implode(", ",$materialComputationIdArray).")";
	}
	//~ echo $sql;
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
	$accessoryNumber = (isset($_POST['accessoryNumber'])) ? $_POST['accessoryNumber'] : '';
	$accessoryName = (isset($_POST['accessoryName'])) ? $_POST['accessoryName'] : '';
	$accessoryDescription = (isset($_POST['accessoryDescription'])) ? $_POST['accessoryDescription'] : '';
	
	//CHICHA 12-18-17
	// $partIdArray = Array();
	// $sql = "SELECT partId FROM cadcam_parts WHERE x = ".$itemX."";
	// //echo $sql;
	// $queryX = $db->query($sql);
	// if($queryX AND $queryX->num_rows > 0)
	// {
	// 	while($resultX = $queryX->fetch_assoc())
	// 	{
	// 		$partIdXArray[] = $resultX['partId'];
	// 	}		
	// $partIdX = implode(",",$partIdXArray);
	// }


	$poListSqlFilterArray = array();
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
		//CHICHA 12-18-17
		$filterCustomer = "customerAlias = '".$customerAlias."' AND ";

	}
	if($poNumber!='')	
		{
			$poListSqlFilterArray[] = "poNumber LIKE '".$poNumber."'";
			//CHICHA 12-18-17
			$filterCustomer .= "poNumber = '".$poNumber."' AND ";
		}
	
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
	$accessorySqlFilterArray = array();
	if($accessoryNumber!='')
	{
		$accessorySqlFilterArray[] = "accessoryNumber LIKE '".$accessoryNumber."'";
	}
	if($accessoryName!='')
	{
		$accessorySqlFilterArray[] = "accessoryName LIKE '".$accessoryName."'";
	}
	if($accessoryDescription!='')
	{
		$accessorySqlFilterArray[] = "accessoryDescription LIKE '".$accessoryDescription."'";
	}
	
	$sqlFilterArray = array();
	if(count($accessorySqlFilterArray) > 0)
	{
		$accessoryIdArray = array();
		$sql = "SELECT partId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND identifier = 2";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$accessoryIdArray[] = $resultLotList['partId'];
			}
		}
		
		$accessorySqlFilter = "WHERE accessoryId IN(".implode(",",$accessoryIdArray).") AND ".implode(" AND ",$accessorySqlFilterArray)." ";
	
		$filteredAccessoryIdArray = array();
		$sql = "SELECT accessoryId FROM cadcam_accessories ".$accessorySqlFilter;
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			while($resultPoList = $queryPoList->fetch_assoc())
			{
				$filteredAccessoryIdArray[] = $resultPoList['accessoryId'];
			}
		}
		if(count($filteredAccessoryIdArray) > 0)	$sqlFilterArray[] = "partId IN(".implode(",",$filteredAccessoryIdArray).")";
	}
	
	$sqlFilter = "WHERE poId IN(".implode(",",$poIdArray).") AND identifier = 2";
	
	//~ $sqlFilter = "WHERE poId IN(".implode(",",$poIdArray).") AND identifier = 1 AND lotNumber NOT IN (SELECT lotNumber FROM engineering_bookingdetails WHERE bookingId IN (SELECT bookingId FROM `engineering_booking` WHERE bookingIncharge=0 AND bookingStatus=2))";
	//CHICHA
	/*
	if($_GET['openLot'] == 1)
	{
		$sqlFilter = "WHERE lotNumber IN (SELECT lotNumber FROM view_workschedule WHERE ".$filterCustomer." processCode IN (432,430,431,312) AND workingQuantity > 0 AND lotNumber NOT IN (SELECT lotNumber FROM engineering_bookingdetails WHERE bookingId IN (SELECT bookingId FROM `engineering_booking` WHERE bookingIncharge=0 AND bookingStatus=2)) ORDER BY targetFinish ASC)";
		//echo $sqlFilter;
	}*/
	
	if(count($sqlFilterArray) > 0)
	{
		$sqlFilter .= " AND ".implode(" AND ",$sqlFilterArray)." ";
	}
	
	$lotNumberArray = array();
	$sql = "SELECT lotNumber FROM ppic_lotlist ".$sqlFilter;
	$queryLotList = $db->query($sql);
	if($queryLotList AND $queryLotList->num_rows > 0)
	{
		while($resultLotList = $queryLotList->fetch_assoc())
		{
			$sql = "SELECT listId FROM system_accessorybooking WHERE lotNumber LIKE '".$resultLotList['lotNumber']."' LIMIT 1";
			$queryBookingDetails = $db->query($sql);
			if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
			{
				$lotNumberArray[] = $resultLotList['lotNumber'];
			}
		}
		
		$sqlFilter .= " AND lotNumber NOT IN('".implode("','",$lotNumberArray)."')";
	}
	
	//~ if($_SESSION['idNumber']=='0346') $sqlFilter = "WHERE lotNumber IN('18-08-5743','18-08-5745','18-08-5746','18-08-5747','18-08-5748','18-08-5749','18-08-5750','18-08-5758','18-08-5759','18-08-5760','18-08-7250','18-08-7251','18-08-7252','18-08-7253')";
	//~ if($_SESSION['idNumber']=='0346') $sqlFilter = "WHERE lotNumber IN('18-08-8690')";
	$sqlFilter .= " GROUP BY poId, partId ORDER BY poId, partId";

	$sql = "SELECT lotNumber FROM ppic_lotlist ".$sqlFilter;
		//~ echo $sql;
	//
	$query = $db->query($sql);
	$totalRecords = ($query AND $query->num_rows > 0) ? $query->num_rows : 0;
	
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('L1350'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/api.css">
	<script src="/<?php echo v; ?>/Common Data/Templates/api.js"></script>
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.css">
	<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetalert2/sweetalert2.min.js"></script>
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
	<!--form action='gerald_roReviewNoMaterialAjax.php' method='post' target='windowMaterialComputation' onsubmit="window.open('','windowMaterialComputation','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');return true;" id='exportFormId'></form-->
	<form action='gerald_roReviewNoAccessoryAjax.php' method='post' id='exportFormId' ></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
			<button class='api-btn api-btn-home' onclick="location.href='/<?php echo v; ?>/dashboard.php';" data-api-title='<?php echo displayText('L434');?>' <?php echo toolTip('L434');?>></button>
			<button class='api-btn api-btn-back' onclick="location.href='rose_roreviewSoftware.php';" data-api-title='<?php echo displayText('L1072');?>' <?php echo toolTip('L1072');?>></button>
		</div>
		
		<div class="api-top api-col api-title" style='width:35%;'>
			<h2 style='border-style:double;border-color:black;border-radius:2px;padding:0 2px;'><?php echo displayText('L1350'); ?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:35%'>
			
			<!---------- CHICHA ---------->
			<form>
				<?php
				$checked = "";
				 if($_GET['openLot']==1)
				 {
				 	$checked = "checked";
				 }
				 ?>
<!--
				<input <?php echo $checked; ?> type="checkbox" id="myCheck"	name="myCheck" onchange="myFunction()">
-->
			</form>

			<?php
				if(count($sqlFilterArray) == 0)
				{
					?><button class='api-btn' id='calculateId' type='submit' value='calculate' style='width:33%' data-api-title='<?php echo displayText('L1340'); ?>' form='exportFormId'></button><?php
				}
			?>
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436');?>' <?php echo toolTip('L436');?>></button>
		</div>
		
		<div class="api-col" style='width:100%;height:92vh;'>
			<!-------------------- Filters -------------------->
			<form action='' method='post' id='formFilter' autocomplete="off"></form>	
			<table cellpadding="0" cellspacing="0" border="0" style='width:100%;'>
				<tr style='font-size:12px;'>
					<td style='width:10%' align='center' ><?php echo displayText('L24'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L25'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L96'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L97'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L506'); ?>	</td>
					<td align='left' style=''></td>
				</tr>
				<tr>
					<td><select name='customerAlias' class='api-form' style='width:78%;' value='<?php echo $customerAlias;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerAlias!='') echo 'background-color:red';?>'></td>
					<td><input list='poNumber' name='poNumber' class='api-form' style='width:78%;' value='<?php echo $poNumber;?>' form='formFilter'><datalist id='poNumber' class='classDataList'><?php echo createFilterInput($sqlFilter,'poNumber',$poNumber);?></datalist>&nbsp;			<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($poNumber!='') echo 'background-color:red';?>'></td>
					<td><input list='accessoryNumber' name='accessoryNumber' class='api-form' style='width:78%;' value='<?php echo $accessoryNumber;?>' form='formFilter'><datalist id='accessoryNumber' class='classDataList'><?php echo createFilterInput($sqlFilter,'accessoryNumber',$accessoryNumber);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($accessoryNumber!='') echo 'background-color:red';?>'></td>
					<td><input list='accessoryName' name='accessoryName' class='api-form' style='width:78%;' value='<?php echo $accessoryName;?>' form='formFilter'><datalist id='accessoryName' class='classDataList'><?php echo createFilterInput($sqlFilter,'accessoryName',$accessoryName);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($accessoryName!='') echo 'background-color:red';?>'></td>
					<td><input list='accessoryDescription' name='accessoryDescription' class='api-form' style='width:78%;' value='<?php echo $accessoryDescription;?>' form='formFilter'><datalist id='accessoryDescription' class='classDataList'><?php echo createFilterInput($sqlFilter,'accessoryDescription',$accessoryDescription);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($accessoryDescription!='') echo 'background-color:red';?>'></td>
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
							<th <?php echo toolTip('L24');?>><?php echo displayText('L45');?></th>
							<th <?php echo toolTip('L96');?>><?php echo displayText('L96');?></th>
							<th <?php echo toolTip('L97');?>><?php echo displayText('L97');?></th>
							<th <?php echo toolTip('L506');?>><?php echo displayText('L506');?></th>
							<th <?php echo toolTip('L31');?>><?php echo displayText('L31');?></th>
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
<!---------- CHICHA ---------->
<script>
function myFunction()
{
	var stat = "<?php echo $checked; ?>";
	if(stat != "")
	{
		location.href="gerald_roReviewNoMaterial.php";
	}
	else
	{
	   location.href="gerald_roReviewNoMaterial.php?openLot=1";
	}
}
</script>


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
			url:'gerald_roReviewNoAccessoryAjax.php',
			filterSql:"<?php echo $sqlFilter." ".$sqlSort; ?>",
			recordCount:parseFloat("<?php echo $totalRecords/50;?>"),
			customFunction:function(){
				
				$("input.length,input.width").change(function(){
					var thisVal = $(this).val();
					var partId = $(this).data('partId');
					var classType = $(this).attr('class').split(" ")[0];
					
					var index = $("input."+classType).index(this);
					var workingQuantity = parseFloat($("td.workingQuantity:eq("+(index)+")").text());
					
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						data:{
							ajaxType:'updateRequiredDimension',
							classType:classType,
							partId:partId,
							workingQuantity:workingQuantity,
							value:thisVal
						},
						success:function(data){
							if(data.trim()!='')
							{
								//~ var qtyPerSheet = parseFloat(data);
								//~ var workingQuantity = parseFloat($("td.workingQuantity:eq("+(index)+")").text());
								//~ var requirement = (qtyPerSheet > 0) ? workingQuantity / qtyPerSheet : 0;
								//~ $("td.qtyPerSheet:eq("+(index)+")").text(qtyPerSheet);
								//~ $("td.requirement:eq("+(index)+")").text(requirement);
								var arrayPart = data.split("|");
								$("td.qtyPerSheet:eq("+(index)+")").text(arrayPart[0]);
								$("td.requirement:eq("+(index)+")").text(arrayPart[1]);								
							}
						}
					});
				});
				
				$("input.statusClass").change(function(){
					
				});
				
			}
		});
		
		$("#calculateId").click(function(){
			if($(this).attr('name')!='submitType')
			{
				swal({
					title: 'This will overwrite recent computation.\nAre you sure you want to proceed?',
					//~ //text: '<?php echo displayText('L1208');//By clicking this button means that you have already printed the PO. Are you sure you want to finish this PO? ?>',
					type: 'info',
					showCancelButton: true,
					allowOutsideClick: false,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Yes'
				}).then(function(){
					$("#calculateId").attr('name','submitType').click();
				})
				return false;
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
