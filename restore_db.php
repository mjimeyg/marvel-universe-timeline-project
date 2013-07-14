<?php

session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
global $con;
$old_error_handler = set_error_handler("mu_error_handler");

$xml = simplexml_load_file('timeline_to_xml.xml');

$kids = $xml->xpath('//event');

foreach($kids as $a)
{
	
	$sql = "UPDATE " . TABLE_EVENT . " SET summary='" . addslashes((string)$a->description) . "' WHERE id=" . $a->id;
	echo $sql . '<br />';
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
echo 'DONE!';
?>