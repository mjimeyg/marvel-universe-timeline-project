<?php
include("./includes/header.php");
try
{
	if(!is_logged_in())
	{
		$fb_user = 0;
		$smarty->assign('exception', "You must be logged in to use these pages.");
		$page = 'exception.html';
	}
	else
	{
		$sql = "SELECT * FROM mu_users WHERE id=" . $fb_user;
		
		$result = mysqli_query($con, $sql);
		
		if(!$result)
		{
			$user_data = log_new_user($fb_user);
		}
		if($user_data['status'] != ACTIVE_USER)
		{
			$error['error'] = "Unauthorised User!";
			$error['error_message'] = "You are not authorised to use this page.";
			echo json_encode($error);
			exit;
		}
		
		$submit = isset($_REQUEST['submit']) ? $_REQUEST['submit'] : false;
		$error = array();
		
		if($submit)
		{
			$success = true;
			$values = array();
			if(isset($_REQUEST['character_name']))
			{
				$values['name'] = addslashes($_REQUEST['character_name']);
			}
			else
			{
				$error[] = 'Invalid character name.';
			}
			
			if(isset($_FILES['file']) && $_FILES['file']['error'] == 0)
			{
				$filename = "";
				if($return_val = resize_and_store_image($filename, $image_path . $character_thumbnail_path))
				{
					$error[] = $return_val;
				}
				$values['image'] = $filename;
			}
			
			if(isset($_REQUEST['character_source']))
			{
				$values['href'] = $_REQUEST['character_source'];
			}
			if(!sizeof($values))
			{
				$error[] = 'No values were supplied.';
			}
			$values['added_by'] = $fb_user;
			if(sizeof($error))
			{
				$success = false;
			}
			else
			{
				$sql = build_insert_query(TABLE_CHARACTER, $values);
				
				if(!mysqli_query($con, $sql))
				{
					$success = false;
					$error[] = mysqli_error($con);
				}
			}
		}
		
		if(sizeof($error))
		{
			$errors = implode('<br />', $error);
		
		}
		$smarty->assign('success', $success);
		$smarty->assign('errors', $errors);
		$page = 'add_character.html';
	}
}
catch(FacebookApiException $ex)
{
	$smarty->assign('exception', 'You need to be logged in to view this page.');
	$page = 'exception.html';
}
catch(Exception $ex)
{
	$smarty->assign('exception', $ex->getMessage());
	$page = 'exception.html';
}
include("./includes/footer.php");

?>