<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
ini_set("display_errors", "on");
$javascriptLib = "/Common Data/Libraries/Javascript/";
$templates = "/Common Data/Libraries/Javascript/";

$sql = "SELECT * FROM ppic_roreviewdetails WHERE roReviewId>0";
$query = $db->query($sql);

$sql1 = $sql;
$totalRecords = $query->num_rows;

?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
    <meta charset="UTF-8">
    <meta name="description" content="Task List">
    <meta name="author" content="RG">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Font Awesome/css/font-awesome.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/Roboto Font/roboto.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/css/bootstrap.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/css/bootstrap-theme.css">
	<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Templates/Bootstrap/w3css/w3.css"> 
	<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Jquery Charts/jquery-1.11.1.min.js"></script>
	<script src="/<?php echo v; ?>/Common Data/Templates/Bootstrap/bootstrap-combobox-master/js/bootstrap-combobox.js"></script>
	<link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetAlert2/dist/sweetalert2.css">
	<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/sweetAlert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.css">
	
        <style type="text/css">

    		body
    		{
    			font-size: 11px;
    			font-family: Arial;
    			background-color: dimgray;
    		}

             #fixTop {
                position:fixed;
                width:100%;
                z-index: 5000;
            }
            .dataTables_wrapper.dataTables_filter {
                float: right;
                text-align: right;
                visibility: hidden;
            }
            .table-hover tbody tr td, .table-hover tbody tr th { transition: 0.2s; }
            .table-hover tbody tr:hover td, .table-hover tbody tr:hover th 
            {
              background-color: gray;
              color: white;
              opacity: 1;
            }

            /* Set a style for the button */
            .cssbutton {
            background-color: #4CAF50;
            color: white;
            padding: 8px 10px;
            margin: 8px 5px;
            border: none;
            cursor: pointer;
            width: 10%;
            opacity: 0.9;
                        }

            .cssbutton:hover {
            opacity: 1;
                             }
           
        </style>
    </head>
<body>
<form id='formFilter' action='<?php echo $_SERVER['PHP_SELF'];?>' method='POST'></form>

<div class="container-fluid">
    <div class="row">
        <div class="w3-container w3-gainsboro w3-padding" id='fixTop'>
            <div class="col-md-5">
                
            </div>
        </div>
    </div>

    <div class="row">
     <br><br><p></p>
        <div class='w3-container w3-padding'>
            <div class="col-md-12 w3-padding">
                <div class='w3-container w3-padding w3-white w3-card-2'>
                    <div class='panel panel-default'>

<button class="cssbutton"><?php echo displayText('L1');?></button>

    <h3 align="center"><?php echo displayText('L1037');?></h3>

<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Templates/mdBoostrap/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Templates/mdBoostrap/js/tether.min.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Templates/mdBoostrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Templates/mdBoostrap/js/mdb.min.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui-1.12.0.css" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/bootstrap.min.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery Balloon/jquery.balloon.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/tinybox.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Tiny Box/stylebox.css"/>

                         <table class='table table-bordered table-condensed table-hover' id="mainTableId">
                            <thead class='w3-darkstateblue thead'>
                                <!--<th class='w3-center' width='5'><?php echo displayText('L843');?></th> -->
                               
                                <th class='w3-center' width="100"><?php echo displayText('L1038');?></th>
                                <th class='w3-center' width="100"><?php echo displayText('L24');?></th>
                                <th class='w3-center' width="100"><?php echo displayText('L292');?></th>
                                <th class='w3-center' width="100"><?php echo displayText('L1039');?></th>
                                <th class='w3-center' width="100"><?php echo displayText('L43');?></th> 
                                <th class='w3-center' width="100"><?php echo displayText('L188');?></th>
                            </thead>
                            <tbody class='tbody'>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-ui.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jQuery 3.1.1/bootstrap.min.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/jquery-date-range-picker-master/dist/daterangepicker.min.css">
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jquery-date-range-picker-master/moment.min.js"></script>
<script type="text/javascript" src="/<?php echo v; ?>/Common Data/Libraries/Javascript/jquery-date-range-picker-master/dist/jquery.daterangepicker.min.js"></script>
<script src="/<?php echo v; ?>/Common Data/Templates/api.jquery.js"></script>
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Super Quick Table/datatables.min.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/Bootstrap Multi-Select JS/dist/css/bootstrap-multiselect.css" type="text/css" media="all" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/Bootstrap Multi-Select JS/dist/js/bootstrap-multiselect.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziModal-master/css/iziModal.css" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziModal-master/js/iziModal.js"></script>
<link rel="stylesheet" href="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziToast-master/dist/css/iziToast.css" />
<script src="/<?php echo v; ?>/Common Data/Libraries/Javascript/iziToast-master/dist/js/iziToast.js"></script>

<script>

  $(document).ready(function() {
        var sqlData = "<?php echo $sql1; ?>";
        var totalRecords = "<?php echo $totalRecords; ?>";
        var dataTable = $('#mainTableId').DataTable( {
            "processing"    : true,
            "ordering"      : true,
            "serverSide"    : true,
            "searching"     : false,
            "bInfo"         : false,
            "ajax"          : {
                    url     : "carlo_roreviewPost.php", // json datasource
                    type    : "post",  // method  , by default get
                    data    : {
                                "sqlData"           : sqlData,
                                "totalRecords"      : totalRecords
                               },
                    error: function(data){  // error handling
                        $(".mainTableId-error").html("");
                        $("#mainTableId").append('<tbody class="mainTableId-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                        $("#mainTableId_processing").css("display","none");
                        
                }
            },
            language    : {
                        processing  : "<span class='loader'></span>"
            },
            fixedColumns:   {
                    leftColumns: 0
            },
            scrollY         : 530,
            //scrollX      : true,
            scrollCollapse  : false,
            scroller        : {
            loadingIndicator: true
            },
            stateSave       : false
        });
        console.log(dataTable);
    });

</script>