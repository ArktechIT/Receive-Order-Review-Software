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

$inputDateTime = (isset($_GET['inputDateTime'])) ? $_GET['inputDateTime'] : '';
$poIdArray = array();
$sql = "SELECT poId FROM ppic_roreviewdatatemp WHERE matCheck = 1";
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
$partNumber = (isset($_POST['partNumber'])) ? $_POST['partNumber'] : '';
$materialType = (isset($_POST['materialType'])) ? $_POST['materialType'] : '';
$metalThickness = (isset($_POST['metalThickness'])) ? $_POST['metalThickness'] : '';
$treatmentName = (isset($_POST['treatmentName'])) ? $_POST['treatmentName'] : '';
$itemX = (isset($_POST['itemX'])) ? $_POST['itemX'] : '';
$itemY = (isset($_POST['itemY'])) ? $_POST['itemY'] : '';
$PVC = (isset($_POST['PVC'])) ? $_POST['PVC'] : '';

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

$materialSpecsSqlFilterArray = array();
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
    //CHICHA
    $filterCustomer .= "dataOne = '".$materialTypeId."' AND ";
}
if($metalThickness!='')
{
    $materialSpecsSqlFilterArray[] = "metalThickness = ".$metalThickness."";
    //CHICHA
    $filterCustomer .= "decimalOne = '".$metalThickness."' AND ";
}

$partsSqlFilterArray = array();
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
    $sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentName LIKE '".$treatmentName."' LIMIT 1";
    $queryMaterialType = $db->query($sql);
    if($queryMaterialType AND $queryMaterialType->num_rows > 0)
    {
        $resultMaterialType = $queryMaterialType->fetch_assoc();
        $treatmentId = $resultMaterialType['treatmentName'];
    }
    
    $partsSqlFilterArray[] = "treatmentId = ".$treatmentId."";
    //CHICHA
    $filterCustomer .= "dataTwo = '".$treatmentId."' AND ";
}

if($partNumber!='')
{
    $partsSqlFilterArray[] = "partNumber LIKE '".$partNumber."'";
    //CHICHA
    $filterCustomer .= "partNumber = '".$partNumber."' AND ";
}
if($itemX!='')
{
    $partsSqlFilterArray[] = "x = ".$itemX."";
    //CHICHA
    $filterCustomer .= "partId = ".$partIdX." AND ";
}

if($itemY!='')	$partsSqlFilterArray[] = "y = ".$itemY."";
if($PVC!='')	$partsSqlFilterArray[] = "PVC = ".$PVC."";

$sqlFilterArray = array();
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
    //echo $partsSqlFilter;

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
//CHICHA
if($_GET['openLot'] == 1)
{
    $sqlFilter = "WHERE lotNumber IN (SELECT lotNumber FROM view_workschedule WHERE ".$filterCustomer." processCode IN (432,430,431,312) AND workingQuantity > 0 AND lotNumber NOT IN (SELECT lotNumber FROM engineering_bookingdetails WHERE bookingId IN (SELECT bookingId FROM `engineering_booking` WHERE bookingIncharge=0 AND bookingStatus=2)) ORDER BY targetFinish ASC)";
    //echo $sqlFilter;
}

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
        $sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$resultLotList['lotNumber']."' LIMIT 1";
        $queryBookingDetails = $db->query($sql);
        if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
        {
            $lotNumberArray[] = $resultLotList['lotNumber'];
        }
    }
    
    $sqlFilter .= " AND lotNumber NOT IN('".implode("','",$lotNumberArray)."')";
}


$sqlFilter .= " GROUP BY poId, partId ORDER BY poId, partId";

$sql = "SELECT lotNumber, poId, partId, SUM(workingQuantity) as workingQuantity, partLevel, patternId FROM ppic_lotlist ".$sqlFilter;
$query = $db->query($sql);
$totalRecords = ($query AND $query->num_rows > 0) ? $query->num_rows : 0;
$sqlData = $sql;

$checked = "";
if($_GET['openLot']==1)
{
    $checked = "checked";
}

$calcButton = $tpl->setDataValue("L1340")
                  ->setAttribute([
                    "id"        => "calculateId",
                    "value"     => "calculate",
                    "form"      => "exportFormId",
                    "type"      => "submit"
                  ])
                  ->createButton();

$refreshButton = $tpl->setDataValue("L436")
                ->setAttribute([
                    "onclick"  => "location.href=''",
                ])
                ->createButton();

$filterDataBtn = $tpl->setDataValue("L437")
                    ->setAttribute("id","filterData")
                    ->createButton();

$title = displayText('3-6', 'utf8', 0, 1)." v2.0";
PMSTemplates::includeHeader($title);

$displayId = "3-6";
$version = "v2.0";
$previousLink = "rose_roreviewSoftware.php";
createHeader($displayId, $version, $previousLink);
?>
<form action='' method='POST' id='formFilter' autocomplete="off"></form>	
<form action='gerald_roReviewNoMaterialAjax.php' method='POST' id='exportFormId' ></form>
<textarea hidden name='sqlData' form='exportFormId'><?php echo $sqlData;?></textarea>
<div class='container-fluid'>
    <div class='row w3-padding-top'> <!-- row 1 -->
        <div class='col-md-12'>
            <div class='w3-right'>
                <input <?php echo $checked; ?> type="checkbox" id="myCheck"	name="myCheck" onchange="myFunction()">
                <?php
                echo $calcButton.$filterDataBtn.$refreshButton;
                ?>
            </div>
        </div>
    </div>
    <div class='row w3-padding-top'>  <!-- row 2 -->
        <div class='col-md-12'>
            <label><?php echo displayText("L41", "utf8", 0, 0, 1)." : ". $totalRecords; ?></label>
			<table id='mainTableId' class="table table-bordered table-striped table-condensed">
				<thead class='w3-indigo' style='text-transform:uppercase;'>
                    <th class='w3-center' style='vertical-align:middle;'></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L24', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L25', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L45', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L28', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L226', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L3446', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L31', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L566', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L184', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L67', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L70', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L71', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L1492', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L306', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L329', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L74', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L75', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L307', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L308', 'utf8', 0, 0, 1);?></th>
				</thead>
				<tbody class='w3-center'>
					
				</tbody>
				<tfoot class='w3-indigo' >
                    <tr>
                        <th class='w3-center' style='vertical-align:middle;'><input type='checkbox' name='checkall' id='chkAll'></th>
                        <th class='w3-center' style='vertical-align:middle;'><label for='chkAll'><?php echo displayText('L326', 'utf8', 0, 0, 1); ?></label></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                    </tr>
				</tfoot>
			</table>
        </div>
    </div>
</div>
<div id='modal-izi-filter'><span class='izimodal-content-filter'></span></div>
<?php
PMSTemplates::includeFooter();
?>
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

function lengthWidth(partId, workingQuantity, val, classType){
    // alert(partId+" "+workingQuantity+" "+val+" "+classType);
    $.ajax({
        url:'<?php echo $_SERVER['PHP_SELF'];?>',
        type:'POST',
        data:{
            ajaxType        : 'updateRequiredDimension',
            classType       : classType,
            partId          : partId,
            workingQuantity : workingQuantity,
            value           : val
        },
        success:function(data){
            if(data.trim()!='')
            {
                var arrayPart = data.split("|");
                // $("td.qtyPerSheet:eq("+(index)+")").text(arrayPart[0]);
                // $("td.requirement:eq("+(index)+")").text(arrayPart[1]);								
            }
        }
    });
}

$(document).ready(function(){
    var sqlData = "<?php echo $sqlData; ?>";
    var sqlFilter = "<?php echo $sqlFilter; ?>";
    var totalRecords = "<?php echo $totalRecords; ?>";
    var dataTable = $('#mainTableId').DataTable( {
		"searching"     : false,
		"processing"    : true,
		"ordering"      : false,
		"serverSide"    : true,
		"bInfo"         : false,
		"ajax"          : {
                url     : "gerald_roReviewNoMaterialAjax.php", // json datasource
                type    : "POST",  // method  , by default get
                data    : {
                            "sqlData"           : sqlData, // SQL Query POST
                            "totalRecords"      : totalRecords
                },
                error   : function(){  // error handling
                            $(".mainTableId-error").html("");
                            $("#mainTableId").append('<tbody class="mainTableId-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                            $("#mainTableId_processing").css("display","none");
                }
		},
        "createdRow": function( row, data, index ) {
        
        },
		"columnDefs"    : [
		
        ],
		fixedColumns    : true,
		deferRender     : true,
		scrollY         : 530,
		scrollX         : false,
		scroller        : {
			loadingIndicator    : true
		},
		stateSave       : false
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
            });

            return false;
        }
    });

    $("#filterData").on('click', function (event) {
        var customerAlias = "<?php echo $customerAlias; ?>";
        var poNumber = "<?php echo $poNumber; ?>";
        var partNumber = "<?php echo $partNumber; ?>";
        var materialType = "<?php echo $materialType; ?>";
        var metalThickness = "<?php echo $metalThickness; ?>";
        var treatmentName = "<?php echo $treatmentName; ?>";
        var itemX = "<?php echo $itemX; ?>";
        var itemY = "<?php echo $itemY; ?>";
        var PVC = "<?php echo $PVC; ?>";

        $("#modal-izi-filter").iziModal({
            title                   : '<i class="fa fa-flash"></i> <?php echo strtoupper(displayText("B7")); ?>',
            headerColor             : '#1F4788',
            subtitle                : '<b><?php echo strtoupper(date('F d, Y'));?></b>',
            width                   : 1200,
            fullscreen              : false,
            transitionIn            : 'comingIn',
            transitionOut           : 'comingOut',
            padding                 : 20,
            radius                  : 0,
            top                     : 10,
            restoreDefaultContent   : true,
            closeOnEscape           : true,
            closeButton             : true,
            overlayClose            : false,
            onOpening               : function(modal){
                                        modal.startLoading();
                                        $.ajax({
                                            url         : 'raymond_filterData.php',
                                            type        : 'POST',
                                            data        : {
                                                                sqlData                 : sqlData,
                                                                sqlFilter               : sqlFilter,
                                                                customerAlias           : customerAlias,
                                                                poNumber                : poNumber,
                                                                partNumber              : partNumber,
                                                                materialType            : materialType,
                                                                metalThickness          : metalThickness,
                                                                treatmentName           : treatmentName,
                                                                itemX                   : itemX,
                                                                itemY                   : itemY,
                                                                PVC                     : PVC
                                            },
                                            success     : function(data){
                                                            $( ".izimodal-content-filter" ).html(data);
                                                            modal.stopLoading();
                                            }
                                        });
                                    },
                onClosed            : function(modal){
                                        $("#modal-izi-filter").iziModal("destroy");
                        }
        });

        $("#modal-izi-filter").iziModal("open");
    });
});
</script>
