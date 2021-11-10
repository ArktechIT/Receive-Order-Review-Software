<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('PHP Modules/anthony_retrieveText.php'); ?>

	<div style="width: 100%">
		<center>
			<span><?php echo displayText('L343');?></span>
			<form  method='post' id='dueDateForm'>
				<input type="date" name="dueDate" id="dueDate" form="dueDateForm"><br>
				<center><input type='submit' name='submitDueDate' value='Update' form='dueDateForm' class="button"></center>
			</form>
		</center>
	</div>
