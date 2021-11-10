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

$searchBtn = $tpl->setDataValue("B5")
               ->setAttribute([
                    "name"      => "search",
                    "form"      => "formFilter"
               ])
               ->createButton();

$sqlFilter = (isset($_POST['sqlFilter'])) ? $_POST['sqlFilter'] : '';
$customerAlias = (isset($_POST['customerAlias'])) ? $_POST['customerAlias'] : '';
$materialType = (isset($_POST['materialType'])) ? $_POST['materialType'] : '';
$thickness = (isset($_POST['thickness'])) ? $_POST['thickness'] : '';
$length = (isset($_POST['length'])) ? $_POST['length'] : '';
$width = (isset($_POST['width'])) ? $_POST['width'] : '';
$treatment = (isset($_POST['treatment'])) ? $_POST['treatment'] : '';
$pvc = (isset($_POST['pvc'])) ? $_POST['pvc'] : '';
$status = (isset($_POST['status'])) ? $_POST['status'] : '';

echo "<div class='row'>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L24', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='customerAlias' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'customerAlias',$customerAlias);
        echo "</select>";
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
        echo "<select name='thickness' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'thickness',$thickness);
        echo "</select>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L74', 'utf8', 0, 0, 1)."</label>";
        echo "<input list='length' name='length' class='w3-input w3-border' value='".$length."' form='formFilter'>";
        echo "<datalist id='length'>";
            echo createFilterInput($sqlFilter,'length',$length);
        echo "</datalist>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L75', 'utf8', 0, 0, 1)."</label>";
        echo "<input list='width' name='width' class='w3-input w3-border' value='".$width."' form='formFilter'>";
        echo "<datalist id='width'>";
            echo createFilterInput($sqlFilter,'width',$width);
        echo "</datalist>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L67', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='treatment' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'treatment',$treatment);
        echo "</select>";
    echo "</div>";
echo "</div>";
echo "<div class='row w3-padding-top'>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L306', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='pvc' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'pvc',$pvc);
        echo "</select>";
    echo "</div>";
    echo "<div class='col-md-2'>";
        echo "<label>".displayText('L172', 'utf8', 0, 0, 1)."</label>";
        echo "<select name='status' class='w3-input w3-border' form='formFilter'>";
            echo createFilterInput($sqlFilter,'status',$status);
        echo "</select>";
    echo "</div>";
echo "</div>";
echo "<div class='row w3-padding-top'>";
    echo "<div class='col-md-12 w3-center'>";
        echo $searchBtn;
    echo "</div>";
echo "</div>";
?>