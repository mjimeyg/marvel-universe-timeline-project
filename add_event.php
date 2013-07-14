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
		
		if(mysqli_num_rows($result) == 0)
		{
			
			$user_data = log_new_user($fb_user);
		}
		
		
		
		
		$comic_list = json_encode(sql_query_to_array('*', null, TABLE_COMIC, null, 'title', array('value'=>'id','label'=>'title','icon'=>'image')));
		$character_list = json_encode(sql_query_to_array('*', null, TABLE_CHARACTER, null, 'name', array('value'=>'id','label'=>'name','icon'=>'image')));
		
		$submit = isset($_REQUEST['submit']) ? $_REQUEST['submit'] : false;
		$error = array();
		
		if($submit)
		{
			$success = true;
			$values = array();
			$characters = array();
			if(isset($_REQUEST['comic_id']))
			{
				$comic = sql_query_to_array('id', null, TABLE_COMIC, "title='" . addslashes($_REQUEST['comic_id']) . "'");
				$values['comic'] = $comic[0]['id'];
			}
			else
			{
				$error[] = 'Invalid comic.';
			}
			
			if(isset($_REQUEST['issue_number']))
			{
				$values['issue_number'] = $_REQUEST['issue_number'];
			}
			
			if(isset($_REQUEST['year_published']))
			{
				$values['year_published'] = $_REQUEST['year_published'];
			}
			else
			{
				$error[] = 'Invalid Year.';
			}
			
			if(isset($_REQUEST['month_published']))
			{
				$values['month_published'] = $_REQUEST['month_published'] + 1;
			}
			else
			{
				$error[] = 'Invalid month.';
			}
			
			if(isset($_REQUEST['day_published']))
			{
				$values['day_published'] = $_REQUEST['day_published'];
			}
			else
			{
				$error[] = 'Invalid day.';
			}
			
			if(isset($_REQUEST['comic_summary']))
			{
				$values['summary'] = addslashes($_REQUEST['comic_summary']);
			}
			else
			{
				$error[] = 'Invalid comic summary.';
			}
			if(isset($_REQUEST['character_list']))
			{
				
				$characters = $_REQUEST['character_list'];
				
				
			}
			
			if(isset($_FILES['file']) && $_FILES['file']['error'] == 0)
			{
				$filename = "";
				if($return_val = resize_and_store_image($filename, $image_path . $event_thumbnail_path))
				{
					$error[] = $return_val;
				}
				$values['image'] = $filename;
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
				$values['status'] = 'waiting';
				$sql = build_insert_query(TABLE_EVENT, $values);
				
				if(!mysqli_query($con, $sql))
				{
					$success = false;
					$error[] = mysqli_error($con);
				}
				else
				{
					$event_id = mysqli_insert_id($con);
					
					foreach($characters as $k=>$c)
					{
						$characters[$k] = array(
							'event_id'		=> $event_id,
							'character_id'	=> $c,
						);
					}
					$sql = build_multi_insert_query(TABLE_CHARACTER_EVENTS, $characters);
					
					if(!mysqli_query($con, $sql))
					{
						$success = false;
						$error[] = mysqli_error($con);
					}
					else
					{
					}
				}
			}
		}
		
		if(sizeof($error))
		{
			$errors = implode('<br />', $error);
		
		}
		
		$smarty->assign('success', $success);
		$smarty->assign('errors', $errors);
		$smarty->assign('comic_list', $comic_list);
		$smarty->assign('character_list', $character_list);
		
		$year_published = array();
		for($a = 1939; $a < date('Y'); $a++)
		{
			$year_published[$a] = $a;
		}
		
		$smarty->assign('year_published', $year_published);
		
		$day_published = array();
		for($a = 1; $a < cal_days_in_month(CAL_GREGORIAN, 1, 1939); $a++)
		{
			$day_published[$a] = $a;
		}
		
		$smarty->assign('day_published', $day_published);
		$page = 'add_event.html';
		
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
//$page = 'exception.html';
include("./includes/footer.php");

?>