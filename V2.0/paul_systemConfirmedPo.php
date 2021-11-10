<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors", "on");
?>
<html lang = "en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../Common Data/Templates/Bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../Common Data/Templates/Bootstrap/w3css/w3.css">
<script src="../Common Data/Templates/Bootstrap/js/jquery.min.js"></script>
<script src="../Common Data/Templates/Bootstrap/js/bootstrap.min.js"></script>
<style>
	body
	{
		margin: 10px;
		padding: 10px;
	}
	.w3-input
	{
		width: 400px;
		height: 50px;
	}
</style>
</head>
<?php
	
	if(isset($_POST['Submit']))
	{
		$supplyId = (isset($_POST['materialType'])) ? $_POST['materialType'] : ""; //Supply Id
		$sheetQuantity = (isset($_POST['sheetQuantity'])) ? $_POST['sheetQuantity'] : "";
		$remarks = (isset($_POST['remarks'])) ? $_POST['remarks'] : "";
		$pvc = (isset($_POST['pvc'])) ? $_POST['pvc'] : 0;
		
		if($supplyId != '' AND $sheetQuantity != '')
		{
			echo $sql = "SELECT lotNumber FROM system_confirmedmaterialpo WHERE materialId = ".$supplyId." AND pvc = ".$pvc." AND poNumber = ''";
			$queryConfirmedMaterialPo = $db->query($sql);
			if($queryConfirmedMaterialPo AND $queryConfirmedMaterialPo->num_rows > 0)
			{
				exit(0);
				echo "<script>
					alert('Error!');
					location.href = 'paul_systemConfirmedPo.php';
				</script>";
			}
			else
			{
				$lotNumber = createPurchasingLotNumber($supplyId,1);
				
				$sql = "INSERT INTO system_confirmedmaterialpo (materialId, sheetQuantity, pvc,lotNumber,dateAdded,employeeId,remarks) VALUES (".$supplyId.", ".$sheetQuantity.", ".$pvc.",'".$lotNumber."',now(),'".$_SESSION['idNumber']."','".$remarks."')";
				$queryInsert = $db->query($sql);
				if($queryInsert)
				{
					$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$sheetQuantity." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryUpdate = $db->query($sql);
					
					echo "<script>
							alert('Success');
							location.href = 'paul_systemConfirmedPo.php';
						</script>";
				}
				else
				{
					echo "<script>
						alert('Error!');
						location.href = 'paul_systemConfirmedPo.php';
					</script>";
				}
			}
		}
		else
		{
			echo "<script>
				alert('Error2!');
				location.href = 'paul_systemConfirmedPo.php';
			</script>";
		}
		exit(0);
	}
?>
<body>
	<table style = 'float:left;'>
		<tr>
			<td>
				<a href = "../../Purchasing Management System/Material PO Management Software/gerald_materialPurchasingList.php"><span class = "pull-left glyphicon glyphicon-chevron-left" style = "font-size: 40px;" title = "Back"></span></a>
			</td>
			<td>
				<a href = "/dashboard.php"><span class = "pull-right glyphicon glyphicon-home" style = "font-size: 40px;" title = "Main Menu"></span></a>
			</td>
		</tr>
	</table>
	 
	<form id = "formId" method = "POST" action = "paul_systemConfirmedPo.php"></form>
	
	<br>&nbsp;<br>
	
	<center>
		<h2><u>Command / Add Materials</u></h2>
		
		<label>Supplier</label>
		<select name = "supplierName" id = "supplierName" class = "w3-input w3-card-12">
			<option value = ""></option>
			<?php
				$sql = "SELECT DISTINCT(supplierId) FROM purchasing_supplierproducts WHERE supplyType = 1";
				$query1 = $db->query($sql);
				if($query1 AND $query1->num_rows > 0)
				{
					while($result1 = $query1->fetch_assoc())
					{
						$supplierId = $result1['supplierId'];
						
						$sql = "SELECT supplierAlias FROM purchasing_supplier WHERE supplierId = ".$supplierId."";
						$query2 = $db->query($sql);
						if($query2 AND $query2->num_rows > 0)
						{
							while($result2 = $query2->fetch_assoc())
							{
								$supplierAlias = $result2['supplierAlias'];
								?>
								<option value = "<?php echo $supplierId; ?>"><?php echo $supplierAlias; ?></option>
								<?php
							}
						}
					}
				}
				?>
		</select><br>
		
		<input type = "checkbox" name = "pvc" value = "1" id='pvcCheck' form = "formId"><label style = "font-size: 10px;">&nbsp;with PVC</label>
		<br>
		<div id = "data" name = "materialType" id = "materialType" form = "formId"> <!-- SupplyId -->
		</div><br>
		
		<input type = "number" class ="w3-input w3-card-12 w3-border-black w3-hover-yellow" name = "sheetQuantity" id = "sheetQuantity" min = "1" placeholder = "Sheet Quantity" form = "formId"><br>
		
		<textarea form = "formId" name="remarks" id="remarks" class = 'w3-input w3-card-12' placeholder="Remarks..." ></textarea>
		<br>
		<input type = "Submit" name = "Submit" value = "Submit" class = "btn btn-danger w3-card-12" form = "formId">
	
	</center>
</body>

<script>
	$(document).ready(function(){	
		$("#supplierName").change(function(){
			var supplierId = $(this).val();
			var pvc = ($("#pvcCheck").prop("checked")) ? 1 : 0;
			$.ajax({
				url:"paul_systemConfirmedPoAjax.php?ajax=materialType",
				data:{supplierId: supplierId,pvc:pvc},
				type:"POST",
				error: function(){
					alert('Error!');
				},
				success: function(data){
					console.log(data);
					$("#data").html(data);
				}
			});			
		});		
		$("#pvcCheck").change(function(){
			var pvc = ($(this).prop("checked")) ? 1 : 0;
			var supplierId = $("#supplierName").val();
			$.ajax({
				url:"paul_systemConfirmedPoAjax.php?ajax=materialType",
				data:{supplierId: supplierId,pvc:pvc},
				type:"POST",
				error: function(){
					alert('Error!');
				},
				success: function(data){
					console.log(data);
					$("#data").html(data);
				}
			});			
		});		
	});
</script>
</html>	
	
