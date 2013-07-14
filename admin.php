<?php
include("./includes/header.php");



try
{
	if(!is_logged_in())
	{
		$fb_user = 0;
		print_r("You must be logged in to use these pages.");
		
	}
	else
	{
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
		
		if(!$authorised)
		{
?>
<h1>You are not authorised to view this page!</h1>
<?php
		}
		else
		{
			if(isset($_GET['id']) && ($_GET['mode'] == 'publish'))
			{
				if(post_event_to_fb($_GET['id'], $page_access_token))
				{
					$action_result = 'Event was successfully published.';
				}
				else
				{
					$action_result = 'Failed to publish result.';
				}
			}
			$sql = "SELECT " . TABLE_EVENT . ".id AS id, issue_number, title, summary, " . TABLE_EVENT . ".image, date_added FROM " . TABLE_EVENT . " INNER JOIN " . TABLE_COMIC . " ON " . TABLE_EVENT . ".comic=" . TABLE_COMIC . ".id WHERE status='waiting'";
			
			$result = mysqli_query($con, $sql);
			//echo $sql;
			if(mysqli_num_rows($result))
			{
				$latest_events = array();
				while($row = mysqli_fetch_array($result))
				{
					$latest_events[] = $row;
				}

?>
<span class="action_result"><? echo $action_result; ?></span>
<h3>Number of events awaiting publish: <?php echo mysqli_num_rows($result); ?></h3>
<table cellspacing="0">
	<?php
			foreach($latest_events as $e)
			{
	?>
    	<tr>
        	<td><?php echo $e['id']; ?></td>
        	<td><img src="<?php echo './' . $image_path . $event_thumbnail_path . $e['image']; ?>" /><p><strong>Date Added:&nbsp;</strong><em><?php echo substr($e['date_added'], 0, strpos($e['date_added'], ' ')); ?></em></p><a href="<?php echo $_SERVER['PHP_SELF'] . '?mode=publish&id=' . $e['id']; ?>"><br /><img src="includes/create_fb_button.php?label=Publish" /></a></td>
            <td><h2><?php echo $e['title'] . "&nbsp;#" . $e['issue_number']; ?></h2><hr /><div><?php echo $e['summary']; ?></div></td>
            
        </tr>
    <?php
			}
	?>
</table>

<?php
			}
			else
			{
?>
<h2>There are no events waiting to be published.</h2>
<?php
			}
			
?>
<h2>Report Log</h2>


<?php
		}
	}
}
catch(FacebookApiException $ex)
{
	$login_url = $facebook->getLoginUrl();
	
	header('Location:' . $login_url);
}
catch(OAuthException $ex)
{
	print_r($ex);
}
catch(Exception $ex)
{
	print_r($ex);
}
include("./includes/footer.php");

?>