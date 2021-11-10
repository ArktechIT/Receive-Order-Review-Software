<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include("PHP Modules/mysqliConnection.php");
	include('PHP Modules/anthony_retrieveText.php');
	ini_set("display_errors","on");
	
	$sql = "SELECT materialType, thickness, length, width, treatment, pvc, finalQuantity FROM ppic_materialcomputation WHERE status = 1 AND lotNumber = ''";
	$queryMaterialComputation = $db->query($sql);
	if($queryMaterialComputation AND $queryMaterialComputation->num_rows > 0)
	{
		while($resultMaterialComputation = $queryMaterialComputation->fetch_assoc())
		{
			$materialType = $resultMaterialComputation['materialType'];
			$thickness = $resultMaterialComputation['thickness'];
			$length = $resultMaterialComputation['length'];
			$width = $resultMaterialComputation['width'];
			$treatment = $resultMaterialComputation['treatment'];
			$pvc = $resultMaterialComputation['pvc'];
			$finalQuantity = $resultMaterialComputation['finalQuantity'];
			
			$materialTypeId = '';
			$sql = "SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '".$materialType."' LIMIT 1";
			$queryMaterialType = $db->query($sql);
			if($queryMaterialType AND $queryMaterialType->num_rows > 0)
			{
				$resultMaterialType = $queryMaterialType->fetch_assoc();
				$materialTypeId = $resultMaterialType['materialTypeId'];
			}
			
			$materialSpecId = '';
			$sql = "SELECT materialSpecId FROM cadcam_materialspecs WHERE materialTypeId = ".$materialTypeId." AND metalThickness = ".$thickness." LIMIT 1";
			$queryMaterialSpecs = $db->query($sql);
			if($queryMaterialSpecs AND $queryMaterialSpecs->num_rows > 0)
			{
				$resultMaterialSpecs = $queryMaterialSpecs->fetch_assoc();
				$materialSpecId = $resultMaterialSpecs['materialSpecId'];
			}
			
			$materialId = '';
			$sql = "SELECT materialId FROM purchasing_material WHERE (materialSpecId = ".$materialSpecId." OR (materialTypeId = ".$materialTypeId." AND thickness = ".$thickness.")) AND length = ".$length." AND width = ".$width." LIMIT 1";
			$queryMaterial = $db->query($sql);
			if($queryMaterial AND $queryMaterial->num_rows > 0)
			{
				$resultMaterial = $queryMaterial->fetch_assoc();
				$materialId = $resultMaterial['materialId'];
			}
			
			$cadcamTreatmentId = '';
			$sql = "SELECT treatmentId FROM cadcam_treatmentprocess WHERE treatmentName LIKE '".$treatment."' LIMIT 1";
			$queryTreatmentProcess = $db->query($sql);
			if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
			{
				$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
				$cadcamTreatmentId = $resultTreatmentProcess['treatmentId'];
			}
			
			$materialTreatmentId = '';
			$sql = "SELECT materialTreatmentId FROM purchasing_materialtreatment WHERE materialId = ".$materialId." AND treatmentId = ".$cadcamTreatmentId." LIMIT 1";
			$queryMaterialTreatment = $db->query($sql);
			if($queryMaterialTreatment AND $queryMaterialTreatment->num_rows > 0)
			{
				$resultMaterialTreatment = $queryMaterialTreatment->fetch_assoc();
				echo "<br>".$materialTreatmentId = $resultMaterialTreatment['materialTreatmentId'];
			}
		}
	}
?>
