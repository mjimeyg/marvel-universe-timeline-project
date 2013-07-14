<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
global $con;
$old_error_handler = set_error_handler("mu_error_handler");


$empty_image = $root_path . $image_path . 'empty_image.jpg';


$categories = array();

$sql = "SELECT * FROM mu_comic";
if(isset($_REQUEST['comic_where']))
{
	$sql .= " WHERE " . $_REQUEST['comic_where'];
}
$sql .= " ORDER BY title";

$result = mysqli_query($con, $sql);

while($row = mysqli_fetch_array($result))
{
	//echo 'path:' . realpath($image_path . $comic_thumbnail_path . $row['image']);
	if(file_exists(realpath($image_path . $comic_thumbnail_path . $row['image'])))
	{
		$comic_image = $root_path . $image_path . $comic_thumbnail_path . $row['image'];
	}
	else
	{
		$comic_image = $empty_image;
	}
	
	
	$sql = "SELECT * FROM mu_event WHERE comic = {$row['id']} ORDER BY year_published, month_published, day_published";

	$result1 = mysqli_query($con, $sql);
	
	if(mysqli_num_rows($result1) > 0)
	{
		$categories[$row['title']] = array(
			'id'			=> $row['id'],
			'title'			=> $row['title'],
			'description'	=> '',
			'link'			=> '',
			'image'			=> $comic_image,
		);
		
		while($row1 = mysqli_fetch_array($result1))
		{
			
			if(file_exists($image_path . $event_thumbnail_path . $row1['image']))
			{
				$event_image = $root_path . $image_path . $event_thumbnail_path . $row1['image'];
			}
			else
			{
				$event_image = $empty_image;
			}
			$categories[$row['title']]['events'][$row1['id']] = array(
				'id'		=> $row1['id'],
				'title'		=> $row['title'] . ': #' . $row1['issue_number'],
				'description'	=> $row1['summary'],
				'link'			=> '',
				'image'			=> $event_image,
				'date'			=> array(
									'month' => $row1['month_published'], 
									'day'	=> $row1['day_published'], 
									'year'	=> $row1['year_published'],
								),
			);
			
			$sql = "SELECT * FROM mu_character INNER JOIN mu_character_events ON mu_character.id = mu_character_events.character_id WHERE event_id = {$row1['id']} ORDER BY name";
		
			$result2 = mysqli_query($con, $sql);
			
			$characters = array();
			while($row2 = mysqli_fetch_array($result2))
			{
				//print_r($row2['name']);
				$characters[] = '<li><img src="' . $image_path . $character_thumbnail_path . $row2['image'] . '"><a href"' . $row2['href'] . '" class="character_name">' . $row2['name'] . '</a></li>';
			}
			//print_r($characters);
			if(sizeof($characters))
			{
				$categories[$row['title']]['events'][$row1['id']]['description'] .= '<div class="character_list"><h2>Characters:</h2><ul>' . implode("",$characters) . '</ul>';
			}
			mysqli_free_result($result2);
			
		}
	}
	mysqli_free_result($result1);
}

mysqli_free_result($result);

if(isset($_GET['type']) && ($_REQUEST['type'] == 'xml'))
{
	$xml = toXml($categories, 'timeline');
	header("Content-type: text/xml; charset=utf-8");
	echo $xml;
}
else
{
	echo json_encode($categories);
}
//print_r($categories);
?>