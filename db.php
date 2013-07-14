<?php

session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
global $con;
$old_error_handler = set_error_handler("mu_error_handler");

$sql = "SELECT * FROM " . TABLE_EVENT;

$result = mysqli_query($con, $sql);

while($row = mysqli_fetch_array($result))
{
	$description = nl2br($row['summary']);
	
	$sql = "UPDATE " . TABLE_EVENT . " SET summary='" . addslashes($description) . "' WHERE id=" . $row['id'];
	
	$result1 = mysqli_query($con, $sql);
	
	if(!$result1)
	{
		echo 'ERROR: ' . $row['id'] . '<br />';
	}
	
	if(mysqli_errno($con))
	{
		print_r(mysqli_error($con) . '<br />');
	}
}

mysqli_free_result($result);

echo 'DONE!';
?>