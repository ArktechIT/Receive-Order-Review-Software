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

$sql = "SELECT * FROM ppic_materialcomputation ".$sqlFilter;
$query = $db->query($sql);
$totalRecords = ($query AND $query->num_rows > 0) ? $query->num_rows : 0;	
$sqlData = $sql;

$purchaseBtn = $tpl->setDataValue("L1345")
                  ->setAttribute([
                    "onclick"   => "window.open('gerald_forMaterialPOV2.php','windowForMaterialPO','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');",
                    "type"      => "button"
                  ])
                  ->createButton();

$printBtn = $tpl->setDataValue("L1201")
                ->setAttribute([
                    "type"      => "submit",
                    "name"      => "submitType",
                    "value"     => "calculate",
                    "form"      => "exportFormId"
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
$previousLink = "gerald_roReviewNoMaterial.php";
createHeader($displayId, $version, $previousLink);
?>
<form action='' method='POST' id='formFilter' autocomplete="off"></form>	
<form action='gerald_roReviewMaterialComputationAjax.php' method='post' target='windowMaterialComputation' onsubmit="window.open('','windowMaterialComputation','left=50,screenX=300,screenY=30,resizable,scrollbars,status,width=700,height=650');return true;" id='exportFormId'></form>
<textarea hidden name='sqlData' form='exportFormId'><?php echo $sqlData;?></textarea>
<div class='container-fluid'>
    <div class='row w3-padding-top'> <!-- row 1 -->
        <div class='col-md-12'>
            <div class='w3-right'>
                <?php
                echo $purchaseBtn.$printBtn.$filterDataBtn.$refreshButton;
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
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L566', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L184', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L74', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L75', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L67', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'><?php echo displayText('L306', 'utf8', 0, 0, 1);?></th>
                    <th class='w3-center' style='vertical-align:middle;'></th>
                    <th class='w3-center' style='vertical-align:middle;'></th>
                    <th class='w3-center' style='vertical-align:middle;'></th>
                    <?php
                    if(in_array($status,array(2,3)) OR ($status==4 AND $_SESSION['idNumber']=='0346'))
                    {
                        ?>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <th class='w3-center' style='vertical-align:middle;'></th>
                        <?php
                    }
                    ?>
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
                        <?php
                        if(in_array($status,array(2,3)) OR ($status==4 AND $_SESSION['idNumber']=='0346'))
                        {
                            ?>
                            <th class='w3-center' style='vertical-align:middle;'></th>
                            <th class='w3-center' style='vertical-align:middle;'></th>
                            <?php
                        }
                        ?>
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
function statusClass(materialComputationId, thisVal){
    $.ajax({
        url:'<?php echo $_SERVER['PHP_SELF'];?>',
        type:'POST',
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
        }
    });
}

function finalQuantityClass(materialComputationId, thisVal){
    $.ajax({
        url:'<?php echo $_SERVER['PHP_SELF'];?>',
        type:'POST',
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
}

function blankingProcessClass(materialComputationId, thisData){
    var thisIto = thisData;
    var thisVal = thisData.value;
    
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
                thisIto.value = '381';
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
}

function unBookClass(materialComputationId, bookingId){
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
}

function jsFunctions(){
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
                url     : "gerald_roReviewMaterialComputationAjax.php", // json datasource
                type    : "POST",  // method  , by default get
                data    : {
                            "sqlData"           : sqlData, // SQL Query POST
                            "sqlFilter"         : sqlFilter,
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

    $("#filterData").on('click', function (event) {
        var customerAlias = "<?php echo $customerAlias; ?>";
        var materialType = "<?php echo $materialType; ?>";
        var thickness = "<?php echo $thickness; ?>";
        var length = "<?php echo $length; ?>";
        var width = "<?php echo $width; ?>";
        var treatment = "<?php echo $treatment; ?>";
        var pvc = "<?php echo $pvc; ?>";
        var status = "<?php echo $status; ?>";

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
                                            url         : 'raymond_roReviewMaterialComputationfilterData.php',
                                            type        : 'POST',
                                            data        : {
                                                                sqlData                 : sqlData,
                                                                sqlFilter               : sqlFilter,
                                                                customerAlias           : customerAlias,
                                                                materialType            : materialType,
                                                                thickness               : thickness,
                                                                length                  : length,
                                                                width                   : width,
                                                                treatment               : treatment,
                                                                pvc                     : pvc,
                                                                status                  : status
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
