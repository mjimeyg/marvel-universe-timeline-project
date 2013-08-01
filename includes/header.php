<?php
session_start();
include('includes/config.php');
include_once 'includes/functions.php';
require_once('smarty/libs/Smarty.class.php');
include_once 'language/en.php';
global $con;

create_session();


$old_error_handler = set_error_handler("mu_error_handler");
//print_r($_SESSION);
$url="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

$smarty = new Smarty();

$smarty->template_dir = 'templates/main/';
$smarty->compile_dir = 'templates/templates_c/';
$smarty->config_dir = 'templates/configs/';
$smarty->cache_dir = 'templates/cache/';

$smarty->clearAllCache();

$smarty->assign('image_path', $image_path);
$smarty->assign('event_thumbnail_path', $event_thumbnail_path);
$smarty->assign('comic_thumbnail_path', $comic_thumbnail_path);
$smarty->assign('character_thumbnail_path', $character_thumbnail_path);

$smarty->assign('site_url', $root_path);
$smarty->assign('encoded_url', urlencode($url));
$smarty->assign('php_self', $_SERVER['PHP_SELF']);

$smarty->assign('lang', $lang);
$smarty->assign('month_names', $month_names);

$smarty->assign('is_logged_in', is_logged_in());

if(strpos($_SERVER['PHP_SELF'], 'view_event') || strpos($_SERVER['PHP_SELF'], 'index.php?id'))
{
	$sql = "SELECT *, " . TABLE_EVENT . ".image AS event_image FROM " . TABLE_EVENT . " INNER JOIN " . TABLE_COMIC . " ON " . TABLE_EVENT . ".comic=" . TABLE_COMIC . ".id WHERE " . TABLE_EVENT . ".id=" . $_GET['id'];
	
	$result = mysqli_query($con, $sql);
	
	$event = mysqli_fetch_array($result);
	
	$smarty->assign('event_id', $event['id']);
	$smarty->assign('event_title', $event['title']);
	$smarty->assign('event_description', $event['description']);
	$smarty->assign('event_link', $event['link']);
	$smarty->assign('event_image', $event['image']);
	$smarty->assign('site_name', $page_title . ' - ' . $event['title']);
	$smarty->assign('event_path', 'index.php?id=' . $_GET['id']);
}
else
{
	//$smarty->assign('event_title', $event['title']);
	$smarty->assign('event_description', $event['description']);
	$smarty->assign('event_link', $event['link']);
	$smarty->assign('event_image', $event['image']);
	
	$smarty->assign('event_path', $_SERVER['PHP_SELF']);
}




?>

