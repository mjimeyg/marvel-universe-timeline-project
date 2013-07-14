<?php
session_start();
include_once 'includes/config.php';
include_once 'includes/functions.php';
global $con;
$old_error_handler = set_error_handler("mu_error_handler");


$empty_image = $root_path . $image_path . 'empty_image.jpg';


$dom = new DOMDocument('1.0', 'utf-8');

$timeline = $dom->createElement('timeline');

$sql = "SELECT * FROM mu_comic ORDER BY title";

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
	$category = $dom->createElement('category');
	
	$cat_id = $dom->createElement('id');
	$cat_id_text = $dom->createTextNode($row['id']);
	
	$cat_id->appendChild($cat_id_text);
	$category->appendChild($cat_id);
	
	$cat_title = $dom->createElement('title');
	$cat_title_text = $dom->createTextNode($row['title']);
	
	$cat_title->appendChild($cat_title_text);
	$category->appendChild($cat_title);
	
	/*$cat_decription = $dom->createElement('description');
	$cat_decription_text = $dom->createCDATASection('');
	
	$cat_decription->appendChild($cat_decription_text);
	$category->appendChild($cat_decription);
	
	$cat_link = $dom->createElement('link');
	$cat_link_text = $dom->createCDATASection('');
	
	$cat_link->appendChild($cat_link_text);
	$category->appendChild($cat_link);*/
	
	$cat_image = $dom->createElement('image');
	$cat_image_text = $dom->createCDATASection($comic_image);
	
	$cat_image->appendChild($cat_image_text);
	$category->appendChild($cat_image);
	
	
	$sql = "SELECT * FROM mu_event WHERE comic = {$row['id']} ORDER BY year_published, month_published, day_published";


	$result1 = mysqli_query($con, $sql);
	
	$events = $dom->createElement('events');
	while($row1 = mysqli_fetch_array($result1))
	{
		$event = $dom->createElement('event');
		if(file_exists($image_path . $event_thumbnail_path . $row1['image']))
		{
			$event_image = $root_path . $image_path . $event_thumbnail_path . $row1['image'];
		}
		else
		{
			$event_image = $empty_image;
		}
		
		$event_id = $dom->createElement('id');
		$event_id_text = $dom->createTextNode($row1['id']);
		
		$event_id->appendChild($event_id_text);
		$event->appendChild($event_id);
		
		$event_title = $dom->createElement('title');
		$event_title_text = $dom->createCDATASection($row['title'] . ': #' . $row1['issue_number']);
		
		$event_title->appendChild($event_title_text);
		$event->appendChild($event_title);
		
		$event_description = $dom->createElement('description');
		$event_description_text = $dom->createCDATASection($row1['summary']);
		
		$event_description->appendChild($event_description_text);
		$event->appendChild($event_description);
		
		$event_link = $dom->createElement('link');
		$event_link_text = $dom->createCDATASection('');
		
		$event_link->appendChild($event_link_text);
		$event->appendChild($event_link);
		
		$event_image = $dom->createElement('image');
		$event_image_text = $dom->createCDATASection($event_image);
		
		$event_image->appendChild($event_image_text);
		$event->appendChild($event_image);
		
		$event_date = $dom->createElement('date');
		$event_date_text = $dom->createTextNode($row1['month_published'] . '-' . $row1['day_published'] . '-' . $row1['year_published']);
		
		$event_date->appendChild($event_date_text);
		$event->appendChild($event_date);
		
		$sql = "SELECT * FROM mu_character INNER JOIN mu_character_events ON mu_character.id = mu_character_events.character_id WHERE event_id = {$row1['id']} ORDER BY name";
	
		$result2 = mysqli_query($con, $sql);
		
		$characters = null;
		while($row2 = mysqli_fetch_array($result2))
		{
			if($characters == null)
			{
				$characters = $dom->createElement('characters');
			}
			$character = $dom->createElement('character');
			
			$character_image = $dom->createElement('image');
			$character_image_text = $dom->createCDATASection($image_path . $character_thumbnail_path . $row2['image']);
			
			$character_image->appendChild($character_image_text);
			$character->appendChild($character_image);
			
			$character_link = $dom->createElement('link');
			$character_link_text = $dom->createCDATASection($row2['href']);
			
			$character_link->appendChild($character_link_text);
			$character->appendChild($character_link);
			
			$character_name = $dom->createElement('name');
			$character_name_text = $dom->createTextNode($row2['name']);
			
			$character_name->appendChild($character_name_text);
			$character->appendChild($character_name);
			
			$characters->appendChild($character);
		}
		//print_r($characters);
		
		mysqli_free_result($result2);
		if($characters != null)
		{
			$event->appendChild($characters);
		}
		$events->appendChild($event);
	}
	
	mysqli_free_result($result1);
	$category->appendChild($events);
	$timeline->appendChild($category);
}

mysqli_free_result($result);

$dom->appendChild($timeline);
header("Content-type: text/xml; charset=utf-8");
echo $dom->saveXML();
//print_r($categories);
?>