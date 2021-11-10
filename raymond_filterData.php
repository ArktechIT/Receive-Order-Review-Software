<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);    
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
include('PHP Modules/gerald_functions.php');
include("PHP Modules/rose_prodfunctions.php");
ini_set("display_errors", "on");
$ctrl = new PMSDatabase;
$tpl = new PMSTemplates;

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

$searchBtn = $tpl->setDataValue("B5")
               ->setAttribute([
                    "name"      => "search",
                    "form"      => "formFilter"
               ])
               ->createButton();

$sqlFilter = (isset($_POST['sqlFilter'])) ? $_POST['sqlFilter'] : '';
$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
$poNumber = (isset($_POST['poNumber'])) ? $_POST['poNumber'] : '';
$partNumber = (isset($_POST['partNumber'])) ? $_POST['partNumber'] : '';
$materialType = (isset($_POST['materialType'])) ? $_POST['materialType'] : '';
$metalThickness = (isset($_POST['metalThickness'])) ? $_POST['metalThickness'] : '';
$treatmentName = (isset($_POST['treatmentName'])) ? $_POST['treatmentName'] : '';
$itemX = (isset($_POST['itemX'])) ? $_POST['itemX'] : '';
$itemY = (isset($_POST['itemY'])) ? $_POST['itemY'] : '';
$PVC = (isset($_POST['PVC'])) ? $_POST['PVC'] : '';

echo "<div class='row'>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L24', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='customerAlias' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);
        echo "</select>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L25', 'utf8', 0, 0, 1)."</label>";
        echo "<input list='poNumber' name='poNumber' class='w3-input w3-border' value='".$poNumber."' form='formFilter'>";
        echo "<datalist id='poNumber'>";
            echo createFilterInput($sqlFilter,'poNumber',$poNumber);
        echo "</datalist>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L28', 'utf8', 0, 0, 1)."</label>";
        echo "<input list='partNumber' name='partNumber' class='w3-input w3-border' value='".$partNumber."' form='formFilter'>";
        echo "<datalist id='partNumber'>";
            echo createFilterInput($sqlFilter,'partNumber',$partNumber);
        echo "</datalist>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L566', 'utf8', 0, 0, 1)."</label>";
        echo "<input list='materialType' name='materialType' class='w3-input w3-border' value='".$materialType."' form='formFilter'>";
        echo "<datalist id='materialType'>";
            echo createFilterInput($sqlFilter,'materialType',$materialType);
        echo "</datalist>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L184', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='metalThickness' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'metalThickness',$metalThickness);
        echo "</select>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L67', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='treatmentName' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'treatmentName',$treatmentName);
        echo "</select>";
    echo "</div>";
echo "</div>";
echo "<div class='row w3-padding-top'>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L70', 'utf8', 0, 0, 1)."</label>";
        echo "<input type='text' name='itemX' class='w3-input w3-border' value='".$itemX."' form='formFilter'>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L71', 'utf8', 0, 0, 1)."</label>";
        echo "<input type='text' name='itemY' class='w3-input w3-border' value='".$itemY."' form='formFilter'>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L306', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='PVC' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'PVC',$PVC);
        echo "</select>";
    echo "</div>";
echo "</div>";
echo "<div class='row w3-padding-top'>";
    echo "<div class='col-md-12 w3-center'>";
        echo $searchBtn;
    echo "</div>";
echo "</div>";
?>