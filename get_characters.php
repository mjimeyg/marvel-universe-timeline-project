<?php
include_once 'includes/config.php';
include_once 'includes/functions.php';
global $con;
$old_error_handler = set_error_handler("mu_error_handler");


$sql = "SELECT * FROM mu_character WHERE name LIKE '%" . $_REQUEST['character_name'] . "%' ORDER BY name";

$result = mysqli_query($con, $sql);

if(!$result)
{
	$error = array(
		'error'			=> 'SQL Error',
		'error_message'	=> mysqli_error($con),
	);
	
	echo json_encode($error);
	exit;
}

$characters = array();
while($row = mysqli_fetch_array($result))
{
	$characters[] = array(
		'value'		=> $row['id'],
		'label'		=> $row['name'],
		//'icon'		=> $row['image'],
		//'desc'		=> $row['href'],
		//'added_by'	=> $row['added_by'],
		//'edited_by'	=> $row['edited_by'],
	);
}

echo json_encode($characters);
?>