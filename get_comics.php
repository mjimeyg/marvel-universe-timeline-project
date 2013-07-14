<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
global $con;
$old_error_handler = set_error_handler("mu_error_handler");


$sql = "SELECT * FROM mu_comic";

if(isset($_REQUEST['comic_id']))
{
	$sql .= " WHERE title LIKE '%" . $_REQUEST['comic_id'] . "%'";
}

$sql .= " ORDER BY title";
$result = mysqli_query($con, $sql);

$comics = array();

while($row = mysqli_fetch_array($result))
{
   $comics[] = array(
   		'value'				=> $row['id'],
		'label'				=> $row['title'],
		'image'				=> $row['image'],
		'strip'				=> $row['strip'],
		'added_by'			=> $row['added_by'],
		'edited_by'			=> $row['edited_by'],
   );
}

mysqli_free_result($result);


echo json_encode($comics);
?>