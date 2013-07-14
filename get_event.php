<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';

$old_error_handler = set_error_handler("mu_error_handler");

$con = mysql_connect($mu_server, $mu_username, $mu_password);
                    
if(!$con)
{
    trigger_error('Could not connect to the database: ' . mysql_errno());
}

mysql_select_db($mu_database);

$comics = array();

$sql = "SELECT * FROM mu_comic ORDER BY title";

$result = mysql_query($sql);

while($row = mysql_fetch_array($result))
{
	$comics[$row['id']] = $row;
	
	$sql = "SELECT * FROM mu_event WHERE comic = {$row['id']} ORDER BY year_published, month_published, day_published";


	$result1 = mysql_query($sql);
	
	while($row1 = mysql_fetch_array($result1))
	{
		
		$comics[$row['id']]['events'][$row1['id']] = $row1;
		
		$sql = "SELECT * FROM mu_character INNER JOIN mu_character_events ON mu_character.id = mu_character_events.character_id WHERE comic_id = {$row['id']} ORDER BY name";

		$result2 = mysql_query($sql);
		
		while($row2 = mysql_fetch_array($result2))
		{
			$comics[$row['id']]['events'][$row1['id']]['characters'] = $row2;
		}
		
		mysql_free_result($result2);
		
	}
	
	mysql_free_result($result1);
}

mysql_free_result($result);

echo json_encode($comics);
?>