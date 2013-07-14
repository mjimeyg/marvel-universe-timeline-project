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
		$comic_list = json_encode(sql_query_to_array('*', null, TABLE_COMIC, null, 'title', array('value'=>'id','label'=>'title','icon'=>'image')));
		if($user_data['status'] != ACTIVE_USER)
		{
			$error['error'] = "Unauthorised User!";
			$error['error_message'] = "You are not authorised to use this page.";
			echo json_encode($error);
			exit;
		}
		
		$submit = isset($_REQUEST['submit']) ? $_REQUEST['submit'] : false;
		$error = array();
		
		$comic_title = "";
		$comic_image = "";
		if(isset($_REQUEST['id']))
		{
			$sql = "SELECT * FROM " . TABLE_COMIC . " WHERE id=" . $_GET['id'];
			
			$result = mysqli_query($con, $sql);
			
			while($row = mysqli_fetch_array($result))
			{
				$comic_title = $row['title'];
				$comic_image = $row['image'];
				
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
			$ori_edited = $_REQUEST['ori_edited'];
			
			if(isset($_REQUEST['comic_title']) && ($ori_edited != $_REQUEST['comic_title']))
			{
				$values['title'] = $_REQUEST['comic_title'];
			}
			
			
			if(isset($_FILES['file']) && ($_FILES['file']['error'] == 0))
			{
				
					$filename = "";
					if($return_val = resize_and_store_image($filename, $image_path . $comic_thumbnail_path))
					{
						$error[] = $return_val;
					}
					$values['image'] = $filename;
				
			}
			
			
			if(!sizeof($values))
			{
				$error[] = 'No values were supplied.';
			}
			$edited_by = json_decode(str_replace("'", '"', $ori_edited), true);
			
			$edited_by[] = array('fb_id'=>$fb_user, 'time'=> time());
			$values['edited_by'] = json_encode($edited_by);
			if(sizeof($error))
			{
				$success = false;
			}
			else
			{
				$sql = build_update_query(TABLE_COMIC, $values, "id=" . $_REQUEST['id']);
				
				if(!mysqli_query($con, $sql))
				{
					$success = false;
					$error[] = mysqli_error($con);
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
		
		$smarty->assign('success', $success);
		$smarty->assign('errors', $errors);
		
		$smarty->assign('id', $_GET['id']);
		
		$smarty->assign('values', $values);
		$smarty->assign('comic_title', $comic_title);
		$smarty->assign('comic_image', $comic_image);
		$smarty->assign('edited_by', $edited_by);
		
		$smarty->assign('comic_list', $comic_list);
		
		$page= 'edit_comic.html';
	}
}
catch(FacebookApiException $ex)
{
	$smarty->assign('exception', 'You need to be logged in to view this page.');
	$page = 'exception.html';
}
catch(Exception $ex)
{
	$smarty->assign('eception', $ex->getMessage());
	$page = 'exception.html';
}
include("./includes/footer.php");

?>