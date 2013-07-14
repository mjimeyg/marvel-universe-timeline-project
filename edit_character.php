<?php
include("./includes/header.php");
try
{
	if(!is_logged_in())
	{
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
		else
		{
			$user_data = mysqli_fetch_array($result);
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
		
		$character_name = "";
		$character_image = "";
		$character_source = "";
		if(isset($_REQUEST['id']))
		{
			$sql = "SELECT * FROM " . TABLE_CHARACTER . " WHERE id=" . $_GET['id'];
			
			$result = mysqli_query($con, $sql);
			
			while($row = mysqli_fetch_array($result))
			{
				$character_name = $row['name'];
				$character_image = $row['image'];
				$character_source = $row['href'];
				$edited_by = $row['edited_by'];
			}
			
			mysqli_free_result($result);
		}
		
		if($submit)
		{
			$success = true;
			$values = array();
			
			$ori_name = $_REQUEST['ori_name'];
			$ori_image = $_REQUEST['ori_image'];
			$ori_source = $_REQUEST['ori_source'];
			
			if(isset($_REQUEST['character_name']) && ($_REQUEST['character_name'] != $ori_name))
			{
				$values['name'] = $_REQUEST['character_name'];
			}
			
			
			if(isset($_FILES['file']) && ($_FILES['file']['error'] == 0))
			{
				
					$filename = "";
					if($return_val = resize_and_store_image($filename, $image_path . $character_thumbnail_path))
					{
						$error[] = $return_val;
					}
					$values['image'] = $filename;
				
			}
			
			if(isset($_REQUEST['character_source']) && ($ori_source != $_REQUEST['character_source']))
			{
				$values['href'] = $_REQUEST['character_source'];
			}
			if(!sizeof($values))
			{
				$error[] = 'No values were supplied.';
			}
			$edited_by = json_decode($edited_by, true);
			$edited_by[] = array('fb_id'=>$fb_user, 'time'=> time());
			$values['edited_by'] = json_encode($edited_by);
			if(sizeof($error))
			{
				$success = false;
			}
			else
			{
				$sql = build_update_query(TABLE_CHARACTER, $values, "id=" . $_REQUEST['id']);
				
				if(!mysqli_query($con, $sql))
				{
					$success = false;
					$error[] = mysqli_error($con);
					$error[] = "SQL: " . $sql;
				}
			}
		}
		
		if($success)
		{
			if(!isset($values['name']))
			{
				$values['name'] = $ori_name;
			}
			if(!isset($values['image']))
			{
				$values['image'] = $ori_image;
			}
			if(!isset($values['href']))
			{
				$values['href'] = $ori_source;
			}
		
		}
		
		if(sizeof($error))
		{
			$errors = implode('<br />', $error);
		
		}
		
		$character_list = json_encode(sql_query_to_array('*', null, TABLE_CHARACTER, null, 'name', array('value'=>'id','label'=>'name','icon'=>'image')));
		
		$smarty->assign('success', $success);
		$smarty->assign('errors', $errors);
		$smarty->assign('id', $_REQUEST['id']);
		$smarty->assign('character_source', $character_source);
		$smarty->assign('character_name', $character_name);
		$smarty->assign('character_image', $character_image);
		$smarty->assign('character_list', $character_list);
		$page = 'edit_character.html';
	}

}
catch(FacebookApiException $ex)
{
	echo 'You need to be logged in to view this page.';
}
catch(Exception $ex)
{
	echo $ex->getMessage();
}
include("./includes/footer.php");

?>