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
		if($_POST['ajaxType']=='updateStatus')
		{
			$materialComputationId = $_POST['materialComputationId'];
			$value = $_POST['value'];
			
			$sql = "UPDATE ppic_materialcomputation SET status = ".$value." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$bookingIdArray = array();
			$sql = "SELECT DISTINCT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$materialComputationId."|%'";
			$queryBookingDetails = $db->query($sql);
			if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
			{
				while($resultBookingDetails = $queryBookingDetails->fetch_assoc())
				{
					$bookingIdArray[] = $resultBookingDetails['bookingId'];
					
					$sql = "DELETE FROM engineering_bookingdetails WHERE bookingId IN(".implode(",",$bookingIdArray).")";
					$query = $db->query($sql);
					if($query)
					{
						$sql = "DELETE FROM engineering_booking WHERE bookingId IN(".implode(",",$bookingIdArray).")";
						$query = $db->query($sql);
						
						$sql = "DELETE FROM ppic_materialcomputationtemporarylot WHERE materialComputationId = ".$materialComputationId."";
						$query = $db->query($sql);
					}
				}
			}
			
			if(isset($_POST['sqlFilter']))
			{
				$sqlFilter = $_POST['sqlFilter'];
				$status = $_POST['status'];
				echo createFilterInput($sqlFilter,'status',$status);
			}
		}
		else if($_POST['ajaxType']=='updateFinalQuantity')
		{
			$materialComputationId = $_POST['materialComputationId'];
			$value = $_POST['value'];
			
			$sql = "UPDATE ppic_materialcomputation SET finalQuantity = ".$value." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryUpdate = $db->query($sql);
			
			$bookingIdArray = array();
			$sql = "SELECT DISTINCT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$materialComputationId."|%'";
			$queryBookingDetails = $db->query($sql);
			if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
			{
				while($resultBookingDetails = $queryBookingDetails->fetch_assoc())
				{
					$bookingIdArray[] = $resultBookingDetails['bookingId'];
					
					$sql = "DELETE FROM engineering_bookingdetails WHERE bookingId IN(".implode(",",$bookingIdArray).")";
					$query = $db->query($sql);
					if($query)
					{
						$sql = "DELETE FROM engineering_booking WHERE bookingId IN(".implode(",",$bookingIdArray).")";
						$query = $db->query($sql);
						
						$sql = "DELETE FROM ppic_materialcomputationtemporarylot WHERE materialComputationId = ".$materialComputationId."";
						$query = $db->query($sql);
					}
				}
			}			
		}
		else if($_POST['ajaxType']=='updateBlankingProcess')
		{
			$materialComputationId = $_POST['materialComputationId'];
			$value = $_POST['value'];
			
			//~ if($value==86)
			//~ {
				//~ $sql = "UPDATE ppic_materialcomputation SET length = (length-3), width = (width-3) WHERE materialComputationId = ".$materialComputationId." AND blankingProcess != 86 LIMIT 1";
				//~ $queryUpdate = $db->query($sql);
			//~ }
			//~ else if($value==381)
			//~ {
				//~ $sql = "UPDATE ppic_materialcomputation SET length = (length+3), width = (width+3) WHERE materialComputationId = ".$materialComputationId." AND blankingProcess = 86 LIMIT 1";
				//~ $queryUpdate = $db->query($sql);
			//~ }
			
			$sql = "UPDATE ppic_materialcomputation SET blankingProcess = ".$value." WHERE materialComputationId = ".$materialComputationId." LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
		else if($_POST['ajaxType']=='unBook')
		{
			$bookingId = $_POST['bookingId'];
			$materialComputationId = $_POST['materialComputationId'];
			
			$sql = "DELETE FROM engineering_bookingdetails WHERE bookingId = ".$bookingId." AND lotNumber = '".$materialComputationId."' LIMIT 1";
			$query = $db->query($sql);
			if($query)
			{
				$sql = "DELETE FROM engineering_booking WHERE bookingId = ".$bookingId." LIMIT 1";
				$query = $db->query($sql);
				
				$inventoryId = 'No Material';
				$inventoryIdSpan = "<span style='cursor:pointer;color:blue;text-decoration:underline;' onclick=\" apiOpenModalBox({url:'gerald_subconMaterialBooking.php',post:'materialComputationId=".$materialComputationId."&inventoryId=".$inventoryId."',customFunction:function(){jsFunctions();}}); \">".$inventoryId."</span>";
				
				$data = array(
					'dataOne' 		=>	"<td class='".$materialComputationId."' data-one='one'>".$inventoryIdSpan."</td>",
					'dataTwo' 		=>	"<td class='".$materialComputationId."' data-two='two'></td>",
					'dataThree' 		=>	"<td class='".$materialComputationId."' data-three='three'></td>",
					'dataFour' 		=>	"<td class='".$materialComputationId."' data-four='four'></td>",
					);
				echo json_encode($data);
			}
		}
		exit(0);
	}
	
	function createFilterInput($sqlFilter,$column,$value)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$return = "<option value=''>".displayText('L490')." </option>";
		$sqlOption = "SELECT DISTINCT ".$column." FROM ppic_materialcomputation ".$sqlFilter." ORDER BY ".$column."";
		$query = $db->query($sqlOption);
		if($query->num_rows > 0)
		{
			while($result = $query->fetch_array())
			{
				$valueColumn = $valueCaption = $result[$column];
				
				if($column=='pvc')
				{
					if($valueColumn==0)			$valueCaption = 'No';
					else if($valueColumn==1)	$valueCaption = 'Yes';
				}
				else if($column=='status')
				{
					if($valueColumn==0)			$valueCaption = 'Not Set';
					else if($valueColumn==1)	$valueCaption = 'For Purchase';
					else if($valueColumn==2)	$valueCaption = 'For Subcon';
					else if($valueColumn==3)	$valueCaption = 'For Internal Prime';
					else if($valueColumn==4)	$valueCaption = 'For Customer Request';
					else if($valueColumn==5)	$valueCaption = 'Open PO';
				}
				
				$selected = ($value==$result[$column]) ? 'selected' : '';
				
				$return .= "<option value='".$valueColumn."' ".$selected.">".$valueCaption."</option>";
			}
		}
		return $return;
	}
	
	$fontSize = (isset($_POST['fontSize'])) ? $_POST['fontSize'] : 14;
	
	$inputDateTime = (isset($_GET['inputDateTime'])) ? $_GET['inputDateTime'] : '';
	
	$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
	$materialType = (isset($_POST['materialType'])) ? $_POST['materialType'] : '';
	$thickness = (isset($_POST['thickness'])) ? $_POST['thickness'] : '';
	$length = (isset($_POST['length'])) ? $_POST['length'] : '';
	$width = (isset($_POST['width'])) ? $_POST['width'] : '';
	$treatment = (isset($_POST['treatment'])) ? $_POST['treatment'] : '';
	$pvc = (isset($_POST['pvc'])) ? $_POST['pvc'] : '';
	$status = (isset($_POST['status'])) ? $_POST['status'] : '';
	
	
	if($customerAlias!='')	$sqlFilterArray[] = "customerAlias LIKE '".$customerAlias."'";
	if($materialType!='')	$sqlFilterArray[] = "materialType LIKE '".$materialType."'";
	if($thickness!='')	$sqlFilterArray[] = "thickness = ".$thickness."";
	if($length!='')	$sqlFilterArray[] = "length = ".$length."";
	if($width!='')	$sqlFilterArray[] = "width = ".$width."";
	if($treatment!='')	$sqlFilterArray[] = "treatment LIKE '".$treatment."'";
	if($pvc!='')	$sqlFilterArray[] = "pvc = ".$pvc."";
	if($status!='')	$sqlFilterArray[] = "status = ".$status."";
	
	$sqlFilter = "WHERE lotNumber =''";
	//~ if($_SESSION['idNumber']=='0346')	$sqlFilter = "WHERE inputDateTime = '2017-10-16 09:02:07'";
	if($inputDateTime!='')	$sqlFilter = "WHERE inputDateTime = '".$inputDateTime."'";
	if(count($sqlFilterArray) > 0)
	{
		$sqlFilter .= " AND ".implode(" AND ",$sqlFilterArray)." ";
	}
	
	$sql = "SELECT materialComputationId FROM ppic_materialcomputation ".$sqlFilter;
	$query = $db->query($sql);
	$totalRecords = ($query AND $query->num_rows > 0) ? $query->num_rows : 0;	
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo displayText('3-6','utf8',0,1,1); ?></title>
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
	<form action='gerald_roReviewMaterialComputationAjax.php' method='post' target='windowMaterialComputation' onsubmit="window.open('','windowMaterialComputation','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');return true;" id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
			<button class='api-btn api-btn-home' onclick="location.href='/<?php echo v; ?>/dashboard.php';" data-api-title='<?php echo displayText('L434');?>' <?php echo toolTip('L434');?>></button>
			<button class='api-btn api-btn-back' onclick="location.href='gerald_roReviewNoMaterial.php';" data-api-title='<?php echo displayText('L1072');?>' <?php echo toolTip('L1072');?>></button>
		</div>
		
		<div class="api-top api-col api-title" style='width:35%;'>
			<h2 style='border-style:double;border-color:black;border-radius:2px;padding:0 2px;'><?php echo displayText('3-6','utf8',0,1,1); ?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:35%'>
<!--
			<button class='api-btn' type='submit' name='submitType' value='calculate' style='width:33%' data-api-title='<?php echo displayText('L1345'); ?>' onclick="window.open('<?php echo ($_SESSION['idNumber']=='0346') ? 'gerald_forMaterialPOV2.php' : 'gerald_forMaterialPO.php';?>','windowForMaterialPO','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');"></button>
-->
			<button class='api-btn' type='submit' name='submitType' value='calculate' style='width:33%' data-api-title='<?php echo displayText('L1345'); ?>' onclick="window.open('gerald_forMaterialPOV2.php','windowForMaterialPO','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');"></button>
			<button class='api-btn' type='submit' name='submitType' value='calculate' style='width:33%' data-api-title='<?php echo displayText('L1201');?>' form='exportFormId'></button>
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436');?>' <?php echo toolTip('L436');?>></button>
		</div>
		
		<div class="api-col" style='width:100%;height:92vh;'>
			<!-------------------- Filters -------------------->
			<form action='' method='post' id='formFilter' autocomplete="off"></form>
			<table cellpadding="0" cellspacing="0" border="0" style='width:100%;'>
				<tr style='font-size:12px;'>
					<td style='width:10%' align='center' ><?php echo displayText('L24'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L566'); ?>	</td>
					<td style='width:10%' align='center' ><?php echo displayText('L184'); ?>	</td>
					<td style='width:10%' align='center' ><?php echo displayText('L74'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L75'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L67'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L306'); ?>	</td>
					<td style='width:10%' align='center' ><?php //echo displayText('L306'); ?>	</td>
					<td align='left' style=''></td>
				</tr>
				<tr>
					<td><select name='customerAlias' class='api-form' style='width:78%;' value='<?php echo $customerAlias;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerAlias!='') echo 'background-color:red';?>'></td>
					<td><input list='materialType' name='materialType' class='api-form' style='width:78%;' value='<?php echo $materialType;?>' form='formFilter'><datalist id='materialType' class='classDataList'><?php echo createFilterInput($sqlFilter,'materialType',$materialType);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($materialType!='') echo 'background-color:red';?>'></td>
					<td><select name='thickness' class='api-form' style='width:78%;' value='<?php echo $thickness;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'thickness',$thickness);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($thickness!='') echo 'background-color:red';?>'></td>
					<td><input list='length' name='length' class='api-form' style='width:78%;' value='<?php echo $length;?>' form='formFilter'><datalist id='length' class='classDataList'><?php echo createFilterInput($sqlFilter,'length',$length);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($length!='') echo 'background-color:red';?>'></td>
					<td><input list='width' name='width' class='api-form' style='width:78%;' value='<?php echo $width;?>' form='formFilter'><datalist id='width' class='classDataList'><?php echo createFilterInput($sqlFilter,'width',$width);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($width!='') echo 'background-color:red';?>'></td>
					<td><select name='treatment' class='api-form' style='width:78%;' value='<?php echo $treatment;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'treatment',$treatment);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($treatment!='') echo 'background-color:red';?>'></td>
					<td><select name='pvc' class='api-form' style='width:78%;' value='<?php echo $pvc;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'pvc',$pvc);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($pvc!='') echo 'background-color:red';?>'></td>
					<td><select name='status' class='api-form' style='width:78%;' value='<?php echo $status;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'status',$status);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($pvc!='') echo 'background-color:red';?>'></td>
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
							<th <?php echo toolTip('L566');?>><?php echo displayText('L566');?></th>
							<th <?php echo toolTip('L184');?>><?php echo displayText('L184');?></th>
							<th <?php echo toolTip('L74');?>><?php echo displayText('L74');?></th>
							<th <?php echo toolTip('L75');?>><?php echo displayText('L75');?></th>							
							<th <?php echo toolTip('L67');?>><?php echo displayText('L67');?></th>
							<th <?php echo toolTip('L306');?>><?php echo displayText('L306');?></th>
							<th></th>
							<th></th>
							<th></th>
							<?php
								//~ if(in_array($status,array(2,3)))
								if(in_array($status,array(2,3)) OR ($status==4 AND $_SESSION['idNumber']=='0346'))
								{
									?>
									<th></th>
<!--
									<th></th>
									<th></th>
									<th></th>
-->
									<th></th>
									<?php
								}
							?>
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
							<?php
								//~ if(in_array($status,array(2,3)))
								if(in_array($status,array(2,3)) OR ($status==4 AND $_SESSION['idNumber']=='0346'))
								{
									?>
									<th></th>
<!--
									<th></th>
									<th></th>
									<th></th>
-->
									<th></th>
									<?php
								}
							?>							
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
			url:'gerald_roReviewMaterialComputationAjax.php',
			filterSql:"<?php echo $sqlFilter." ".$sqlSort; ?>",
			recordCount:parseFloat("<?php echo $totalRecords/50;?>"),
			customFunction:function(){
				
				$("select.statusClass").change(function(){
					var thisVal = $(this).val();
					var materialComputationId = $(this).data('materialComputationId');
					
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						data:{
							ajaxType:'updateStatus',
							materialComputationId:materialComputationId,
							value:thisVal,
							sqlFilter:"<?php echo $sqlFilter;?>",
							status:"<?php echo $status;?>"
						},
						success:function(data){
							<?php
								if($status!='')
								{
									?>
									$("#formFilter").submit();
									<?php
								}
								else
								{
									?>
									$("select[name=status]").html(data);
									<?php
								}
							?>
							//~ alert(data);
						}
					});
				});
				
				$("input.finalQuantityClass").change(function(){
					var thisVal = $(this).val();
					var materialComputationId = $(this).data('materialComputationId');
					
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						data:{
							ajaxType:'updateFinalQuantity',
							materialComputationId:materialComputationId,
							value:thisVal
						},
						success:function(data){
							<?php
								if($status!='')
								{
									?>
									$("#formFilter").submit();
									<?php
								}
							?>
							//~ alert(data);
						}
					});
				});
				
				$("select.blankingProcessClass").change(function(){
					var thisIto = $(this);
					var thisVal = $(this).val();
					var materialComputationId = $(this).data('materialComputationId');

					//~ var index = $("select.blankingProcessClass").index(thisIto);
					//~ var matLength = $("td.length:eq("+index+")").text();
					//~ var matWidth = $("td.width:eq("+index+")").text();
					
					if(thisVal==86)
					{
						swal({
							title: 'This process will change the actual length and width of material.\nAre you sure you want to proceed?',
							//~ //text: '<?php echo displayText('L1208');//By clicking this button means that you have already printed the PO. Are you sure you want to finish this PO? ?>',
							type: 'info',
							showCancelButton: true,
							allowOutsideClick: false,
							confirmButtonColor: '#3085d6',
							cancelButtonColor: '#d33',
							confirmButtonText: 'Yes'
						}).then(function(){
							$.ajax({
								url:'<?php echo $_SERVER['PHP_SELF'];?>',
								type:'post',
								data:{
									ajaxType:'updateBlankingProcess',
									materialComputationId:materialComputationId,
									value:thisVal
								},
								success:function(data){
									//alert(thisVal);
									//~ $("td.length:eq("+index+")").text((parseFloat(matLength)-3).toFixed(2));
									//~ $("td.width:eq("+index+")").text((parseFloat(matWidth)-3).toFixed(2));
								}
							});
						}, function (dismiss) {
							if (dismiss === 'cancel') {
								thisIto.val('381');
							}
						});
					}
					else
					{
						$.ajax({
							url:'<?php echo $_SERVER['PHP_SELF'];?>',
							type:'post',
							data:{
								ajaxType:'updateBlankingProcess',
								materialComputationId:materialComputationId,
								value:thisVal
							},
							success:function(data){
								//alert(thisVal);
								//~ $("td.length:eq("+index+")").text((parseFloat(matLength)+3).toFixed(2));
								//~ $("td.width:eq("+index+")").text((parseFloat(matWidth)+3).toFixed(2));
							}
						});
					}
				});	
				
				$("img.unBookClass").click(function(){
					var bookingId = $(this).data('bookingId');
					var materialComputationId = $(this).data('materialComputationId');
					
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						dataType:'json',
						data:{
							ajaxType:'unBook',
							bookingId:bookingId,
							materialComputationId:materialComputationId
						},
						success:function(data){
							if(data)
							{
								$("td."+materialComputationId+"[data-one=one]").replaceWith(data.dataOne);
								$("td."+materialComputationId+"[data-two=two]").replaceWith(data.dataTwo);
								$("td."+materialComputationId+"[data-three=three]").replaceWith(data.dataThree);
								//~ $("td."+materialComputationId+"[data-four=four]").replaceWith(data.dataFour);
							}
						}
					});
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
		$(".api-table-fixedheader").apiFixedTableHeader();
		
		$("span.inventoryIdClass").click(function(){
			var inventoryId = $(this).data("inventory-id");
			var materialComputationId = $("input[name=materialComputationId]").val();
			
			$.ajax({
				url:'gerald_subconMaterialBooking.php',
				type:'post',
				dataType:'json',
				data:{
					ajaxType:'updateMaterial',
					materialComputationId:materialComputationId,
					inventoryId:inventoryId
				},
				success:function(data){
					if(data)
					{
						$("td."+materialComputationId+"[data-one=one]").replaceWith(data.dataOne);
						$("td."+materialComputationId+"[data-two=two]").replaceWith(data.dataTwo);
						$("td."+materialComputationId+"[data-three=three]").replaceWith(data.dataThree);
						$("td."+materialComputationId+"[data-four=four]").replaceWith(data.dataFour);
						
						apiOpenModalBox({remove:true});
						//~ alert(data.dataTwo);
						//~ magulang.html(data);
					}
				}
			});
		});
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
