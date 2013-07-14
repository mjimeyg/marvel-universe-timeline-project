<?php
session_start();
include('./includes/header.php');

if($_SESSION['status'] != ACTIVE)
{
    trigger_error("You are not authorised to use this page.", E_USER_ERROR);
}

if(!isset($_REQUEST['id']))
{
	trigger_error("An invalid event id was passed or no id was passed.  The ID was: {$_REQUEST['event_id']}", E_USER_ERROR);
}
$submit = $_POST['submit'];
$event_id = $_REQUEST['id'];



$template_file = 'delete_event.html';

if($submit)
{
	$smarty->assign('deleted', true);
	
	$sql = "UPDATE mu_event SET status='delete' WHERE id={$event_id}";
	
	if(!mysql_query($sql))
	{
		trigger_error("Failed to delete event!", E_USER_ERROR);
	}
	
	$sql = "UPDATE mu_character_events SET status='delete' WHERE comic_id={$event_id}";
	
	if(!mysql_query($sql))
	{
		trigger_error("Failed to delete character events!", E_USER_ERROR);
	}
	
}
else
{
	$smarty->assign('deleted', false);
	
	$event = get_event($event_id);
	
	$event['summary'] = nl2br($event['summary']);

	$smarty->assign('comic', $event);
	$smarty->assign('id', $event_id);
}
include('./includes/footer.php');
?>

         