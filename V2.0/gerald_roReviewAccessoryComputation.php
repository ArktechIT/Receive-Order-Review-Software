<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	if($_POST['ajaxType']=='updateStatus')
	{
		$accessoryComputationId = $_POST['accessoryComputationId'];
		$value = $_POST['value'];
		
		$sql = "UPDATE ppic_accessorycomputation SET status = ".$value." WHERE accessoryComputationId = ".$accessoryComputationId." LIMIT 1";
		$queryUpdate = $db->query($sql);
		
		if(isset($_POST['sqlFilter']))
		{
			$sqlFilter = $_POST['sqlFilter'];
			$status = $_POST['status'];
			echo createFilterInput($sqlFilter,'status',$status);
		}
	}
	else if($_POST['ajaxType']=='updateFinalQuantity')
	{
		$accessoryComputationId = $_POST['accessoryComputationId'];
		$value = $_POST['value'];
		
		$sql = "UPDATE ppic_accessorycomputation SET finalQuantity = ".$value." WHERE accessoryComputationId = ".$accessoryComputationId." LIMIT 1";
		$queryUpdate = $db->query($sql);
	}
	
	function createFilterInput($sqlFilter,$column,$value)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$return = "<option value=''>".displayText('L490')." </option>";
		$sqlOption = "SELECT DISTINCT ".$column." FROM ppic_accessorycomputation ".$sqlFilter." ORDER BY ".$column."";
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
	$accessoryNumber = (isset($_POST['accessoryNumber'])) ? $_POST['accessoryNumber'] : '';
	$accessoryName = (isset($_POST['accessoryName'])) ? $_POST['accessoryName'] : '';
	$accessoryDescription = (isset($_POST['accessoryDescription'])) ? $_POST['accessoryDescription'] : '';
	
	
	if($customerAlias!='')	$sqlFilterArray[] = "customerAlias LIKE '".$customerAlias."'";
	if($accessoryNumber!='')	$sqlFilterArray[] = "accessoryNumber LIKE '".$accessoryNumber."'";
	if($accessoryName!='')	$sqlFilterArray[] = "accessoryName LIKE '".$accessoryName."'";
	if($accessoryDescription!='')	$sqlFilterArray[] = "accessoryDescription LIKE '".$accessoryDescription."'";
	
	$sqlFilter = "WHERE lotNumber =''";
	//~ if($_SESSION['idNumber']=='0346')	$sqlFilter = "WHERE inputDateTime = '2017-10-16 09:02:07'";
	if($inputDateTime!='')	$sqlFilter = "WHERE inputDateTime = '".$inputDateTime."'";
	if(count($sqlFilterArray) > 0)
	{
		$sqlFilter .= " AND ".implode(" AND ",$sqlFilterArray)." ";
	}
	
	$sql = "SELECT accessoryComputationId FROM ppic_accessorycomputation ".$sqlFilter;
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
	<form action='gerald_roReviewMaterialComputationAjax.php' method='post' target='windowMaterialComputation' onsubmit="window.open('','windowMaterialComputation','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');return true;" id='exportFormId'></form>
	<input type='hidden' name='sqlFilter' value="<?php echo $sqlFilter;?>" form='exportFormId'>
	<div class="api-row">
		<div class="api-top api-col api-left-buttons" style='width:30%'>
			<button class='api-btn api-btn-home' onclick="location.href='/<?php echo v; ?>/dashboard.php';" data-api-title='<?php echo displayText('L434');?>' <?php echo toolTip('L434');?>></button>
			<button class='api-btn api-btn-back' onclick="location.href='gerald_roReviewNoAccessory.php';" data-api-title='<?php echo displayText('L1072');?>' <?php echo toolTip('L1072');?>></button>
		</div>
		
		<div class="api-top api-col api-title" style='width:35%;'>
			<h2 style='border-style:double;border-color:black;border-radius:2px;padding:0 2px;'><?php echo displayText('L1350'); ?></h2>
		</div>
		<div class="api-top api-col api-right-buttons" style='width:35%'>
			<button class='api-btn' type='submit' name='submitType' value='calculate' style='width:33%' data-api-title='<?php echo displayText('L1345'); ?>' onclick="window.open('gerald_forAccessoryPO.php','windowForMaterialPO','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');"></button>
<!--
			<button class='api-btn' type='submit' name='submitType' value='calculate' style='width:33%' data-api-title='<?php echo displayText('L1201');?>' form='exportFormId'></button>
-->
			<button class='api-btn api-btn-refresh' onclick="location.href='';" style='width:33%' data-api-title='<?php echo displayText('L436');?>' <?php echo toolTip('L436');?>></button>
		</div>
		
		<div class="api-col" style='width:100%;height:92vh;'>
			<!-------------------- Filters -------------------->
			<form action='' method='post' id='formFilter' autocomplete="off"></form>
			<table cellpadding="0" cellspacing="0" border="0" style='width:100%;'>
				<tr style='font-size:12px;'>
					<td style='width:10%' align='center' ><?php echo displayText('L24'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L96'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L97'); ?>		</td>
					<td style='width:10%' align='center' ><?php echo displayText('L506'); ?>	</td>
					<td style='width:10%' align='center' ><?php //echo displayText('L306'); ?>	</td>
					<td align='left' style=''></td>
				</tr>
				<tr>
					<td><select name='customerAlias' class='api-form' style='width:78%;' value='<?php echo $customerAlias;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);?></select>&nbsp;														<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerAlias!='') echo 'background-color:red';?>'></td>
					<td><input list='accessoryNumber' name='accessoryNumber' class='api-form' style='width:78%;' value='<?php echo $accessoryNumber;?>' form='formFilter'><datalist id='accessoryNumber' class='classDataList'><?php echo createFilterInput($sqlFilter,'accessoryNumber',$accessoryNumber);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($accessoryNumber!='') echo 'background-color:red';?>'></td>
					<td><input list='accessoryName' name='accessoryName' class='api-form' style='width:78%;' value='<?php echo $accessoryName;?>' form='formFilter'><datalist id='accessoryName' class='classDataList'><?php echo createFilterInput($sqlFilter,'accessoryName',$accessoryName);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($accessoryName!='') echo 'background-color:red';?>'></td>
					<td><input list='accessoryDescription' name='accessoryDescription' class='api-form' style='width:78%;' value='<?php echo $accessoryDescription;?>' form='formFilter'><datalist id='accessoryDescription' class='classDataList'><?php echo createFilterInput($sqlFilter,'accessoryDescription',$accessoryDescription);?></datalist>&nbsp;<input type='image' onclick='this.form.submit()' src='/<?php echo v; ?>/Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($accessoryDescription!='') echo 'background-color:red';?>'></td>
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
							<th <?php echo toolTip('L96');?>><?php echo displayText('L96');?></th>
							<th <?php echo toolTip('L97');?>><?php echo displayText('L97');?></th>
							<th <?php echo toolTip('L506');?>><?php echo displayText('L506');?></th>
							<th></th>
							<th></th>
							<th></th>
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
			url:'gerald_roReviewAccessoryComputationAjax.php',
			filterSql:"<?php echo $sqlFilter." ".$sqlSort; ?>",
			recordCount:parseFloat("<?php echo $totalRecords/50;?>"),
			customFunction:function(){
				
				$("select.statusClass").change(function(){
					var thisVal = $(this).val();
					var accessoryComputationId = $(this).data('accessoryComputationId');
					
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						data:{
							ajaxType:'updateStatus',
							accessoryComputationId:accessoryComputationId,
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
					var accessoryComputationId = $(this).data('accessoryComputationId');
					
					$.ajax({
						url:'<?php echo $_SERVER['PHP_SELF'];?>',
						type:'post',
						data:{
							ajaxType:'updateFinalQuantity',
							accessoryComputationId:accessoryComputationId,
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
