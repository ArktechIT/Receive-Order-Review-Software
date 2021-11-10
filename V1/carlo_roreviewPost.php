<?php
include $_SERVER['DOCUMENT_ROOT']."/version.php";
$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
set_include_path($path);
include('PHP Modules/mysqliConnection.php');
include('PHP Modules/anthony_retrieveText.php');
ini_set("display_errors", "on");
$javascriptLib = "/Common Data/Libraries/Javascript/";
$templates = "/Common Data/Libraries/Javascript/";

$requestData= $_REQUEST;
$sqlData = isset($requestData['sqlData']) ? $requestData['sqlData'] : '';
$totalRecords = isset($requestData['totalRecords']) ? $requestData['totalRecords'] : '';

$totalData = $totalRecords;
$totalFiltered = $totalRecords;

$data = array();
$sql = $sqlData;
$sql.=" LIMIT ".$requestData['start']." ,".$requestData['length']."   ";
$query = $db->query($sql);

if($query AND $query->num_rows > 0)
{
    while($result = $query->fetch_assoc()) 
    {  
        $roReviewId = $result['roReviewId'];
        $pcName = $result['roReviewDate'];            
        $ipaddress = $result['roReviewVenue'];
        $remarks = $result['remarks'];
        $view = "<button onclick='viewFunction()'>".$roReviewId."</button>
                    <script>
                    function viewFunction() 
                        {
                         window.open('rose_print.php?reviewId=".$roReviewId."');
                        }
                    </script>";
       
        $roReviewCount = 0;
        $poId=0;
        $customerAlias="";
        $sql = "SELECT poId FROM ppic_roreviewdata WHERE roReviewId = ".$roReviewId;
        $reviewdataQuery = $db->query($sql);
        if($reviewdataQuery->num_rows > 0)
        {
            $roReviewCount =$reviewdataQuery->num_rows;
            while ($resultdataQuery = $reviewdataQuery->fetch_assoc() and $poId==0)
            {
                $poId = $resultdataQuery['poId'];
                $sql = "SELECT customerAlias  FROM sales_customer WHERE customerId IN (SELECT customerId FROM sales_polist WHERE poId=".$poId.")";             
                $getcustomerAlias = $db->query($sql);
                while($getcustomerAliasResult = $getcustomerAlias->fetch_assoc())
                {
                $customerAlias=$getcustomerAliasResult['customerAlias'];
                }
            }
        }

        $nestedData=array(); 
        //$nestedData[] = $number++ + 1;
        $nestedData[] = $roReviewId;
        $nestedData[] = $customerAlias;
        $nestedData[] = $pcName;
        $nestedData[] = $ipaddress;
        $nestedData[] = $roReviewCount;
        $nestedData[] = $view;
        
        $data[] = $nestedData;

    }
}

$json_data = array(
            "draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
            "recordsTotal"    => intval( $totalData ),  // total number of records
            "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
            "data"            => $data   // total data array
            );

echo json_encode($json_data);  // send data as json format
?>

