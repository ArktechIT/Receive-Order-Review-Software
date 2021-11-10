<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/anthony_wholeNumber.php');
	include('PHP Modules/anthony_retrieveText.php');
	include('PHP Modules/gerald_functions.php');
	ini_set("display_errors", "on");
	
	$sqlFilter = isset($_POST['sqlFilter']) ? $_POST['sqlFilter'] : "";
	$_POST = json_decode(str_replace("'",'"',$_POST['filterDataPost']),true);
	$_GET = json_decode(str_replace("'",'"',$_POST['filterDataGet']),true);
	
    function createFilterInput($sqlFilter,$column,$value)
	{
		include('PHP Modules/mysqliConnection.php');
		$return = "<option value=''>".displayText('L490')." </option>";	
		if(in_array($column,array('idNumber', 'firstName', 'surName', 'sectionId', 'status')))
		{
			$columnSql = "a.".$column;
		}
	
		$sql = "SELECT DISTINCT ".$columnSql." FROM hr_employee as a";
		$query = $db->query($sql);
		if($query->num_rows > 0)
		{
			while($result = $query->fetch_array())
			{
				$valueColumn = $valueCaption = $result[$column];
				
				$selected = ($value==$result[$column]) ? 'selected' : '';
				
				if($column=='status')
				{
					if($valueColumn==0)		$valueCaption = 'Inactive';
					else if($valueColumn==1)	$valueCaption = 'Active';
				}

				if($column=='sectionId')
				{
					$sql = "SELECT sectionName FROM ppic_section WHERE sectionId = ".$valueColumn;
					$querySection = $db->query($sql);
					if($querySection AND $querySection->num_rows > 0)
					{
						$resultSection = $querySection->fetch_assoc();
						$valueCaption = $resultSection['sectionName'];
					}
				}

				$string = $string.$counterData;
				if(trim($valueColumn) != "")
				{
					$return .= "<option value='".$valueColumn."' ".$selected.">".$string." ".$valueCaption."</option>";
				}
			}
		}
		return $return;
	}
	
	$idNumber = (isset($_POST['idNumber'])) ? $_POST['idNumber'] : '';
	$firstName = (isset($_POST['firstName'])) ? $_POST['firstName'] : '';
	$surName = (isset($_POST['surName'])) ? $_POST['surName'] : '';
	$status = (isset($_POST['status'])) ? $_POST['status'] : '';
	$sectionId = (isset($_POST['sectionId'])) ? $_POST['sectionId'] : '';
	$includeInactiveFlag = (isset($_POST['includeInactiveFlag'])) ? 1 : 0;
?>
<input type='hidden' name='type' value='' form='formFilter'>
	<table cellpadding="0" cellspacing="0" style='width:100%; background-color:#ffb54d!important;' border='0'> 
		<tr style='font-size:12px;'>
			<td style='width:10%' align='center' ><?php echo displayText('L24');?><!-- CUSTOMER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L224');?><!-- PO NUMBER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L45');?><!-- LOT NUMBER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L28');?><!-- PART NUMBER --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L30');?><!-- PART NAME --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L174');?><!-- MATERIAL --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L31');?><!-- QUANTITY --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L172');?><!-- STATUS --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L62');?><!-- TARGET DATE --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L711');?><!-- DELIVERY DATE --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L91');?><!-- SUBCON --></td>
			<td style='width:10%' align='center' ><?php echo displayText('L1026');?><!-- BENDED --></td>
		</tr>
		<tr>
			<td align='center'>
				<input list='idNumber' name='idNumber' class='api-form' style='width:80%;' value='<?php echo $idNumber;?>' form='formFilter'>
				<datalist id = 'idNumber'>
				<?php echo createFilterInput($sqlFilter,'idNumber',$idNumber);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($idNumber!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='firstName' name='firstName' class='api-form' style='width:80%;' value='<?php echo $firstName;?>' form='formFilter'>
				<datalist id = 'firstName'>
				<?php echo createFilterInput($sqlFilter,'firstName',$firstName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($firstName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<td align='center'>
				<input list='surName' name='surName' class='api-form' style='width:80%;' value='<?php echo $surName;?>' form='formFilter'>
				<datalist id = 'surName'>
				<?php echo createFilterInput($sqlFilter,'surName',$surName);?>
				</datalist>
				&emsp;&nbsp;
				<input type='image' onclick='this.form.submit()' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;margin-left:-18px;margin-bottom:4px;<?php if($surName!='') echo 'background-color:red';?>'>
			</td>
			<!-- <td align='center'><select name='firstName' class='api-form' style='width:100%;' value='<?php echo $firstName;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'firstName',$firstName);?></select><input type='image' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerName!='') echo 'background-color:red';?>'></td> -->
			<!-- <td align='center'><select name='surName' class='api-form' style='width:100%;' value='<?php echo $surName;?>' form='formFilter'><?php echo createFilterInput($sqlFilter,'surName',$surName);?></select><input type='image' src='../Common Data/Templates/images/submitBtn.png' width=15 title='Filter' form='formFilter' style='border:1px solid blue;<?php if($customerAlias!='') echo 'background-color:red';?>'></td> -->
		</tr>
		
		<tr>
			<td colspan='2'>
				<center>
					<button type='submit' class='api-btn' onclick="location.href='';" data-api-title='<?php echo displayText('B7');?>' <?php echo toolTip('L437');?> form='formFilter'></button>
				</center>			
			</td>
		</tr>
	</table>
