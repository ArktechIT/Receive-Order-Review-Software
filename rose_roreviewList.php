<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors", "on");
?>
<!--
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
-->
<?php
	$tWidth = 'auto';
?>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />	
    <title><?php echo displayText('L1037');?></title>
    <link id='link' rel="stylesheet" href="/<?php echo v; ?>/Common Data/style.css" type="text/css" media="screen" />
</head>
<?php include('Templates/bodytop.php') ?>
<table>
	<tr>
		<td height="40" width="100">
			<?php echo mainMenu(1); ?>
		</td>
		<td border="0" width="25">
		</td>

		<td bgcolor="LIGHTGRAY" height="40" width="100">
			<a href="rose_roreviewSoftware2.php"><font face><center>ADD</center></font></a>
		</td>
	</tr>
</table>
<center>
<div style="border:2px solid;border-radius:25px;display:inline-block;width:auto;height:auto;padding: 20px;">
	<div class="">
	<table>
		<tr><td colspan='2'><h2 class="art-postheader"><center><?php echo displayText('L1037')?></center></h2></td></tr>
		<tr>
			<td>
			<form id='formId' action='rose_tinyBoxAdd.php' method='POST'>
			<table id="table10" class="mytable" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<th><?php echo displayText('L1038')?></th>
						<th><?php echo displayText('L24')?></th>
						<th><?php echo displayText('L292')?></th>
						<th><?php echo displayText('L1039')?></th>
						<th><?php echo displayText('L43')?></th>
						<th><?php echo displayText('L188')?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						$sql = "SELECT * FROM ppic_roreviewdetails where roReviewId>2";
						$querySchedule = $db->query($sql);
						while ($resultSchedule = $querySchedule->fetch_assoc())
						{
						 	$roReviewId = $resultSchedule['roReviewId'];
						 	$roReviewDate = $resultSchedule['roReviewDate'];
						 	$roReviewVenue = $resultSchedule['roReviewVenue'];										
							
							$roReviewCount = 0;
							$poId=0;
							$customerAlias="";
							$sql = "SELECT poId FROM ppic_roreviewdata where roReviewId = ".$roReviewId;
							$reviewdataQuery = $db->query($sql);
							if($reviewdataQuery->num_rows > 0)
							{
							$roReviewCount =$reviewdataQuery->num_rows;
								while ($resultdataQuery = $reviewdataQuery->fetch_assoc() and $poId==0)
								{
									$poId = $resultdataQuery['poId'];
									$sql = "SELECT customerAlias  FROM sales_customer WHERE customerId IN (Select customerId from sales_polist where poId=".$poId." )";				
									$getcustomerAlias = $db->query($sql);
									while($getcustomerAliasResult = $getcustomerAlias->fetch_assoc())
									{
									$customerAlias=$getcustomerAliasResult['customerAlias'];
									}
								}
							}
							if($roReviewCount>0)
							{
							?>
								<tr class="rowCount <?php echo $backgroundColor; ?>">
									<!--
									<td align='left'><input type='checkbox' class='chkBox' name='bookingId[]' <?php echo $disable;?> value='<?php echo $poId;?>' /></td>
									-->
									<td class="text-center"><?php echo $roReviewId;?></td>
									<td class="text-center"><?php echo $customerAlias;?></td>
									<td class="text-center"><?php echo $roReviewDate;?></td>
									<td class="text-center"><?php echo $roReviewVenue;?></td>								
									<td class="text-center"><?php echo $roReviewCount;?></td>								
									<td class="text-center"><img onclick="window.open('rose_print.php?reviewId=<?php echo $roReviewId; ?>', '_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, top=100, left=100, width=1130, height=700')" src='../Common Data/Templates/images/view1.png' width='15' height='15' alt='VIEW' title='displayText('L336')' ></td>								
								</tr>
								<?php
							}
						}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th></th>
						<th></th>
						<th></th>
						<th></th>	
						
					</tr>
				</tfoot>	
			</table>
			</form>
			</td>
			
		</tr>		
	</table>
	
	</div>
</div>
</center>
<!-- -------------------------------------------TABLE FILTER JQUERY----------------------------------------------------------><!-- -------------------------------------------TABLE FILTER JQUERY---------------------------------------------------------->
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/style2.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/filtergrid3.css" />
<style>.scrollableTable tbody tr:hover{background: linear-gradient(#DAE2EB, #C1CFDD);}</style>
<style type="text/css">/* Sortable tables */ table.mytable a.sortheader {background-color:#eee;color:#666666;font-weight: bold;text-decoration: none;display: block;}table.mytable span.sortarrow {color: black;text-decoration: none;}</style>
<style type="text/css" media="screen">div#navmenu li a#lnk02{color:#333; font-weight:bold; border-top:2px solid #ff9900;background:#fff;}</style>
<style type="text/css">.scrollableTable tbody {display: block;height:250px;width: <?php echo $tWidth;?>;overflow-y:scroll;}.scrollableTable thead {display: block;width: <?php echo $tWidth;?>;}.scrollableTable tfoot {display: block;width: <?php echo $tWidth;?>;}</style>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/tablefilter_all_min.js" language="javascript" type="text/javascript"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/sorttable.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Table Filter/scroll3.js"></script>
<script type="text/javascript">var sizeColWidths = function() {$('#table10 td, #table10 th').css('width', 'auto');$('#table10').removeClass('scrollableTable');$('#table10 tbody').css('width', 'auto');var i=0, colWidth=new Array();$('#table10 th').each(function() {colWidth[i++] = $(this).outerWidth();});$('#table10 tr').each(function() {var i=0;$('th, td', this).each(function() {$(this).css('width', colWidth[i++] + 'px');})});$('#table10').addClass('scrollableTable');$('#table10 tbody').css('width', ($('#table10 thead').width() + 20 ) +'px');};$(document).ready(function() {sizeColWidths()});$(window).resize(function() {sizeColWidths()});</script>
<script language="javascript" type="text/javascript">
	var totRowIndex = tf_Tag(tf_Id('table10'),"tr").length;
    var table10_Props = {                            
							filters_row_index: 1,
							alternate_rows: true,
							rows_counter: true,
							showRowHeader: true,
                            loader: true,
                            loader_text: "Filtering data...",
							col_number_format: [null,null,null,null],
                            
                            rows_always_visible: [totRowIndex],
                            col_0: 'select',col_1: 'select',col_2: 'select',col_3: 'select',col_4: 'none',col_5: 'select',col_6: 'select',
							col_width: ['100px','100px','100px','100px'],   
                            refresh_filters: true
                        };
    var tf10 = setFilterGrid( "table10",table10_Props );
</script>
<script>
$(function(){
		$('#chkAll').change(function(e) {
				$('input.chkBox').attr('checked', false);
				$('input.chkBox').parent().parent().css('background', '');
				$('input.chkBox:visible:not(:disabled)').attr('checked', this.checked);
				if($('input.chkBox:visible:not(:disabled)').is(':checked'))
				{
					$('input.chkBox:visible:not(:disabled)').parent().parent().css('background', 'linear-gradient(#DAE2EB, #C1CFDD)');
				}
				else
				{
					$('input.chkBox:visible:not(:disabled)').parent().parent().css('background', '');
				}
			});
		});
		$("#submitId").click(function(){
				if ($('input.chkBox').is(":checked") )
				{
					var chk = 'input.chkBox:visible[checked="checked"]:not(:disabled)';
					var chkSize = $(chk).size();
					var chkVal = $(chk).map(function(){
						return $(this).val();
					}).get();
					//alert("ri"+chkVal);
					TINY.box.show({url:'rose_tinyBoxAdd.php',post:'workScheduleId='+chkVal+'&sectionProcess=<?php echo $sectionProcess;?>&special=<?php echo $special;?>',width:'',height:'',opacity:10,topsplit:6,animate:false,close:true})
				}
				return false;
			});
</script>

<!-- -----------------------------------START SMALL BOX--------------------------------------------------------------->
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/stylebox.css"/>
<style>
	#box{display:inline-block;padding: 20px}
</style>
<script type="text/javascript">
function openTinyBox(w,h,url,html,post,iframe,left,top)
{
	TINY.box.show({
		url:url,width:w,height:h,post:post,opacity:50,topsplit:6,animate:false,close:true,iframe:iframe,left:left,top:top,html:html,
		boxid:'box',
		openjs:function(){
			<?php unset($_GET['date']);?>
		}
	});
}	
</script>
<!-- -----------------------------------END SMALL BOX----------------------------------------------------------------> 
