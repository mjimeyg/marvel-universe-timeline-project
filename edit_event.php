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
		
		$accounts = $facebook->api('/me/accounts', 'get');
				
		$authorised = false;
		$page_access_token;
		foreach($accounts['data'] as $a)
		{
			
			if($a['id'] == $fb_page_id)
			{
				$authorised = true;
				$page_access_token = $a['access_token'];
			}
		}
		
		
		$comic_list = json_encode(sql_query_to_array('*', null, TABLE_COMIC, null, 'title', array('value'=>'id','label'=>'title','icon'=>'image')));
		$character_list = json_encode(sql_query_to_array('*', null, TABLE_CHARACTER, null, 'name', array('value'=>'id','label'=>'name','icon'=>'image')));
		
		$submit = isset($_REQUEST['submit']) ? $_REQUEST['submit'] : false;
		$error = array();
		
		if(isset($_REQUEST['id']) || isset($_REQUEST['submit']))
		{
			$sql = "SELECT * FROM " . TABLE_EVENT . "  WHERE id=" . $_REQUEST['id'];
			
			$result = mysqli_query($con, $sql);
			
			$event = mysqli_fetch_array($result);
			
			mysqli_free_result($result);
			
			$sql = "SELECT * FROM " . TABLE_COMIC . "  WHERE id=" . $event['comic'];
			
			$result = mysqli_query($con, $sql);
			
			$comic = mysqli_fetch_array($result);
			
			mysqli_free_result($result);
			
			$sql = "SELECT * FROM " . TABLE_CHARACTER . " INNER JOIN " . TABLE_CHARACTER_EVENTS . " ON " . TABLE_CHARACTER . ".id = " . TABLE_CHARACTER_EVENTS . ".character_id  WHERE event_id=" . $event['id'] . " ORDER BY name";
			
			$result = mysqli_query($con, $sql);
			
			$characters = array();
			$character_ids = array();
			while($row = mysqli_fetch_array($result))
			{
				$characters[] = $row;
				$character_ids[] = $row['character_id'];
			}
			
			mysqli_free_result($result);
			
		}
		if($submit)
		{
			$success = true;
			$values = array();
			$characters = array();
			
			$ori_comic = $_REQUEST['ori_comic'];
			$ori_issue_number = $_REQUEST['ori_issue_number'];
			$ori_year = $_REQUEST['ori_year_published'];
			$ori_month = $_REQUEST['ori_month_published'];
			$ori_day = $_REQUEST['ori_day_published'];
			$ori_image = $_REQUEST['ori_image'];
			$ori_summary = $_REQUEST['ori_summary'];
			$ori_characters = json_decode(str_replace("'", '"', $_REQUEST['ori_characters']), true);
			$ori_edited = json_decode(str_replace("'", '"', stripslashes($_REQUEST['ori_edited'])), true);
			
			if(!is_array($ori_edited))
			{
				$ori_edited = array();
			}
			$curr_time = time();
			
			$edit_list = "";
			if(isset($_REQUEST['comic_id']))
			{
				
				
				$sql = "SELECT * FROM " . TABLE_COMIC . "  WHERE title='" . $_REQUEST['comic_id'] . "'";
				
				$result = mysqli_query($con, $sql);
				
				$comic = mysqli_fetch_array($result);
				
				mysqli_free_result($result);
				if($comic['id'] != $ori_comic)
				{
					$values['comic'] = $comic['id'];
					$edit_list .= "<li>Comic Title: " . $_REQUEST['comic_id'] . "</li>";
				}
				
				
			}
			
			if(isset($_REQUEST['issue_number']) && ($_REQUEST['issue_number'] != $ori_issue_number))
			{
				$values['issue_number'] = $_REQUEST['issue_number'];
				$edit_list .= "<li>Issue Number: " . $_REQUEST['issue_number'] . "</li>";
			}
			
			if(isset($_REQUEST['year_published']) && ($_REQUEST['year_published'] != $ori_year))
			{
				$values['year_published'] = $_REQUEST['year_published'];
				$edit_list .= "<li>Year: " . $_REQUEST['year_published'] . "</li>";
			}
			
			if(isset($_REQUEST['month_published']) && ($_REQUEST['month_published'] + 1 != $ori_month))
			{
				$values['month_published'] = $_REQUEST['month_published'] + 1;
				$edit_list .= "<li>Month: " . $_REQUEST['month_published'] . "</li>";
			}
			
			if(isset($_REQUEST['day_published']) && ($_REQUEST['day_published'] != $ori_day))
			{
				$values['day_published'] = $_REQUEST['day_published'];
				$edit_list .= "<li>Day: " . $_REQUEST['day_published'] . "</li>";
			}
			/*$diff = new Diff(explode("\n", $ori_summary), explode("\n", $_REQUEST['comic_summary']));
			$renderer = new Diff_Renderer_Html_Inline;
			echo html_entity_decode($diff->render($renderer));*/
			if(isset($_REQUEST['comic_summary']) && (strcmp($_REQUEST['comic_summary'], $ori_summary) != 0))
			{
				
				$values['summary'] = $_REQUEST['comic_summary'];
				
				$edit_list .= "<li>Summary: " . $values['summary'] . "</li>";
			}
			
			if(isset($_REQUEST['character_list']))
			{
				
				if(sizeof(array_diff($_REQUEST['character_list'], $ori_characters)))
				{
					$add_characters = array_diff($_REQUEST['character_list'], $ori_characters);
				}
				
				if(sizeof(array_diff($ori_characters, $_REQUEST['character_list'])))
				{
					$rem_characters = array_diff($ori_characters, $_REQUEST['character_list']);
				}
				
			}
			
			if(isset($_FILES['file']) && ($_FILES['file']['error'] == 0))
			{
				$ori_md5 = md5_file(realpath($image_path . $event_thumbnail_path) . '/' . $ori_image);
				$new_md5 = md5_file($_FILES['file']['name']);
				//echo $ori_md5 . '/' . $new_md5;
				
					$filename = "";
					if($return_val = resize_and_store_image($filename, $image_path . $event_thumbnail_path))
					{
						$error[] = $return_val;
					}
					$values['image'] = $filename;
				
			}
			
			
			if(!sizeof($values) && !sizeof(array_diff($_REQUEST['character_list'], $ori_characters)))
			{
				$error[] = 'No values were supplied.';
			}
			if(sizeof($error))
			{
				$success = false;
			}
			else
			{
				
				if(sizeof($values))
				{
					foreach($values as $a=>$b)
					{
						$values[$a] = addslashes($b);
					}
					$sql = build_update_query(TABLE_EVENT, $values, "id=" . $_REQUEST['id']);
					
					if(!mysqli_query($con, $sql))
					{
						$success = false;
						$error[] = mysqli_error($con);
						$error[] = $sql;
					}
				}
				
				if(!sizeof($errors))
				{
					
					if(sizeof($add_characters))
					{
						$n_characters = array();
						foreach($add_characters as $k=>$c)
						{
							$n_characters[] = array(
								'event_id'		=> $_REQUEST['id'],
								'character_id'	=> $c,
							);
						}
						$sql = build_multi_insert_query(TABLE_CHARACTER_EVENTS, $n_characters);
						
						if(!mysqli_query($con, $sql))
						{
							$success = false;
							$error[] = mysqli_error($con);
						}
						else
						{
							$values['characters_added'] = $add_characters;
							$edit_list .= "<li>Characters Added: " . implode(', ', $add_characters) . "</li>";
						}
					}
					if(sizeof($rem_characters))
					{
						foreach($rem_characters as $k=>$c)
						{
							$rem_characters[$k] = 'character_id=' . $c;
						}
						
						$where = implode(" AND ", $rem_characters);
						
						$sql = "DELETE FROM " . TABLE_CHARACTER_EVENTS . " WHERE $where";
						
						if(!mysqli_query($con, $sql))
						{
							$success = false;
							$error[] = $sql;
							$error[] = mysqli_error($con);
						}
						else
						{
							$values['characters_deleted'] = $rem_characters;
							$edit_list .= "<li>Characters Removed: " . implode(', ', $rem_characters) . "</li>";
						}
					}
					if(!is_array($ori_edited))
					{
						$ori_edited = array();
					}
					$ori_edited[$curr_time] = array($fb_user => $edit_list);
					//print_r($ori_edited);
					
					$sql = "UPDATE " . TABLE_EVENT . " SET edited_by='" . addslashes(json_encode($ori_edited)) . "' WHERE id=" . $_REQUEST['id'];
					
					if(!mysqli_query($con, $sql))
					{
						$success = false;
						$error[] = $sql;
						$error[] = mysqli_error($con);
					}
				}
				
			}
		}
		if(isset($_REQUEST['id']) || isset($_REQUEST['submit']))
		{
			$sql = "SELECT * FROM " . TABLE_EVENT . "  WHERE id=" . $_REQUEST['id'];
			
			$result = mysqli_query($con, $sql);
			
			$event = mysqli_fetch_array($result);
			
			mysqli_free_result($result);
			
			$sql = "SELECT * FROM " . TABLE_COMIC . "  WHERE id=" . $event['comic'];
			
			$result = mysqli_query($con, $sql);
			
			$comic = mysqli_fetch_array($result);
			
			mysqli_free_result($result);
			
			$sql = "SELECT * FROM " . TABLE_CHARACTER . " INNER JOIN " . TABLE_CHARACTER_EVENTS . " ON " . TABLE_CHARACTER . ".id = " . TABLE_CHARACTER_EVENTS . ".character_id  WHERE event_id=" . $event['id'] . " ORDER BY name";
			
			$result = mysqli_query($con, $sql);
			
			$characters = array();
			$character_ids = array();
			while($row = mysqli_fetch_array($result))
			{
				$characters[] = $row;
				$character_ids[] = $row['character_id'];
			}
			
			mysqli_free_result($result);
			
			$edit_history = json_decode($event['edited_by'], true);
		}
		
		
		if(sizeof($error))
		{
			$errors = implode('<br />', $error);
		
		}
		
		$smarty->assign('success', $success);
		$smarty->assign('errors', $errors);
		
		$smarty->assign('comic_list',$comic_list);
		$smarty->assign('character_list',$character_list);
		$smarty->assign('id', $_GET['id']);
		$event['summary'] = addslashes(htmlentities($event['summary']));
		$smarty->assign('event', $event);
		$smarty->assign('comic', $comic);
		$smarty->assign('characters', $characters);
		$smarty->assign('character_ids', str_replace('"', "'", $character_ids));
		$smarty->assign('ori_characters', str_replace('"', "'", json_encode($character_ids)));
		$smarty->assign('ori_edited', htmlentities($event['edited_by']));
		
		$year_published = array();
		for($a = 0; $a <= date('Y'); $a++)
		{
			$year_published[$a] = $a;
		}
		$smarty->assign('year_published', $year_published);
		
		$day_published = array();
		for($a = 1; $a <= cal_days_in_month(CAL_GREGORIAN, 1, 1939); $a++)
		{
			$day_published[$a] = $a;
		}
		
		$smarty->assign('day_published', $day_published);
		
		$page = 'edit_event.html';
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