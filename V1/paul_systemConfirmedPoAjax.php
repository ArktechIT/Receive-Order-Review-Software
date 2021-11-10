<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	ini_set("display_errors", "on");
	
	$ajax = $_GET['ajax'];
	if($ajax = 'materialType')
	{
		echo "<select class = 'w3-input w3-card-12' name = 'materialType' form = 'formId'>";
		echo "<option value=''></option>";
		$supplierId = $_POST['supplierId'];
		$pvcPost = (isset($_POST['pvc']) AND $_POST['pvc']==1) ? 1 : 0;
		
		$productIdArray = array();
		$sql = "SELECT productId FROM purchasing_supplierproducts WHERE supplierId = ".$supplierId." AND supplyType = 1";
		$querySupplierProducts = $db->query($sql);
		if($querySupplierProducts AND $querySupplierProducts->num_rows > 0)
		{
			while($resultSupplierProducts = $querySupplierProducts->fetch_assoc())
			{
				$productIdArray[] = $resultSupplierProducts['productId'];
			}
		}
		
		$supplyChoices = '';
		$supplyIdArray = array();
		$sql = "SELECT supplyId FROM purchasing_supplierproductlinking WHERE productId IN(".implode(",",$productIdArray).") AND supplyType = 1";
		$query = $db->query($sql);
		if($query AND $query->num_rows > 0)
		{
			while($result = $query->fetch_assoc())
			{
				$supplyIdArray[] = $result['supplyId'];
			}
			
			
			
			/*
			$sql = "SELECT materialTreatmentId, materialId, treatmentId FROM purchasing_materialtreatment WHERE materialTreatmentId IN (".implode(",", $supplyIdArray).")";
			$queryMaterialTreatment = $db->query($sql);
			if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
			{
				while($resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc())
				{
					$materialTreatmentId = $resultMaterialTreatment['materialTreatmentId'];
					$materialId = $resultMaterialTreatment['materialId'];
					$treatmentId = $resultMaterialTreatment['treatmentId'];
					
					$treatmentName = '';
					$sql = "SELECT `treatmentName` FROM cadcam_treatmentprocess WHERE treatmentId = ".$treatmentId." LIMIT 1";
					$queryTreatment = $db->query($sql);
					if($queryTreatment->num_rows > 0)
					{
						$resultTreatment = $queryTreatment->fetch_array();
						$treatmentName = $resultTreatment['treatmentName'];
					}
					
					$sql = "SELECT materialSpecId, materialId, length, width FROM purchasing_material WHERE materialId = ".$materialId."";
					$query1 = $db->query($sql);
					if($query1 AND $query1->num_rows > 0)
					{
						while($result1 = $query1->fetch_assoc())
						{
							$materialSpecId = $result1['materialSpecId'];
							$materialId = $result1['materialId'];
							$length = $result1['length'];
							$width = $result1['width'];
						
							$materialTypeId = $thickness = '';
							$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." LIMIT 1";
							$queryMaterialSpecs = $db->query($sql);
							if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
							{
								$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
								$materialTypeId = $resultMaterialSpecs['materialTypeId'];
								$thickness = $resultMaterialSpecs['metalThickness'];
							}
							
							$materialType = '';
							$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId."";
							$query2 = $db->query($sql);
							if($query2 AND $query2->num_rows > 0)
							{
								while($result2 = $query2->fetch_assoc())
								{
									$materialType = $result2['materialType'];
									//~ echo "<option value = ".$materialTreatmentId.">".$materialType." ".$thickness." ".$length." X ".$width."</option>"."<br>";
									echo "<option value = ".$materialTreatmentId.">".$materialType." ".$thickness." ".$length." X ".$width." ".$treatmentName."</option>";
								}
							}
						}
					}
				}
			}*/
			
			$sql = "SELECT materialTreatmentId, materialId, treatmentId, pvc FROM purchasing_materialtreatment WHERE materialTreatmentId IN (".implode(",", $supplyIdArray).") AND pvc = ".$pvcPost."";
			$queryMaterialTreatment = $db->query($sql);
			if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
			{
				while($resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc())
				{
					$materialTreatmentId = $resultMaterialTreatment['materialTreatmentId'];
					$materialId = $resultMaterialTreatment['materialId'];
					$treatmentId = $resultMaterialTreatment['treatmentId'];
					$pvc = ($resultMaterialTreatment['pvc']==1) ? ' w/PVC' : '';
					
					$sql = "SELECT `materialId`, `materialSpecId`, `thickness`, `length`, `width`, `spare` FROM `purchasing_material` WHERE materialId = ".$materialId." LIMIT 1";
					$queryMaterial = $db->query($sql);
					if($queryMaterial->num_rows > 0)
					{
						$resultMaterial = $queryMaterial->fetch_array();
						
						$materialTypeId = '';
						$sql = "SELECT materialTypeId FROM cadcam_materialspecs WHERE materialSpecId = ".$resultMaterial['materialSpecId']." LIMIT 1";
						$queryMaterialSpecs = $db->query($sql);
						if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
						{
							$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
							$materialTypeId = $resultMaterialSpecs['materialTypeId'];
						}
						
						$materialType = '';
						//~ $sql = "SELECT materialType FROM purchasing_materialtype WHERE suppliermaterialID = ".$materialTypeId." LIMIT 1";
						$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
						$queryMaterialType = $db->query($sql);
						if($queryMaterialType->num_rows > 0)
						{
							$resultMaterialType = $queryMaterialType->fetch_array();
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
						
						//~ $materialDimensionSpecArray[$resultMaterial['materialId']] = $materialType." t".$resultMaterial['thickness']." ".$resultMaterial['length']." x ".$resultMaterial['width']." ".$treatmentName;
						
						$selected = ($supplyId == $materialTreatmentId) ? 'selected' : '';
						//~ $selected = ($supplyId == $resultMaterial['materialId']) ? 'selected' : '';

						//$supplyChoices .= "<option value='".$resultMaterial['materialId']."' ".$selected.">".$resultMaterial['materialId']." = ".$materialType." t".$resultMaterial['thickness']." ".$resultMaterial['length']." x ".$resultMaterial['width']." ".$treatmentName."</option>";	

						$arrayOuput[$materialTreatmentId] = $materialType." t".$resultMaterial['thickness']." ".$resultMaterial['length']." x ".$resultMaterial['width']." ".$treatmentName.$pvc;
						//~ $arrayOuput[$resultMaterial['materialId']] = $materialType." t".$resultMaterial['thickness']." ".$resultMaterial['length']." x ".$resultMaterial['width']." ".$treatmentName;
							//$supplyChoices .= "<option>".$arrayOuput[$resultMaterial['materialId']]."</option>";	
						
					}
				}	
						asort($arrayOuput);

						foreach ($arrayOuput as $key => $item) {
							  $arrayOuput[$key] = $item;
							  $supplyChoices .= "<option value='".$key."' ".$selected.">".$item."</option>";
							}
			}
		}
		echo $supplyChoices;
		echo "</select>";
	}
?>

