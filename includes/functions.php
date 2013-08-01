<?php
function mu_error_handler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

    switch ($errno) {
    case E_USER_ERROR:
        
        break;

    case E_USER_WARNING:
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

    case E_USER_NOTICE:
        
        break;

    default:
        
        break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

/**
*	Function adds new user to database and returns false on SUCCESS or an error array on failure.
**/
function log_new_user($user_id)
{
	global $con;
	$sql = "INSERT INTO " . TABLE_USERS . "(id, status) VALUES($user_id, 0)";
	
	if(!mysqli_query($con, $sql))
	{
		return array(
			'error'		=> "Database Error!",
			'error_message'	=> mysqli_error(),
			'error_location' => "log_user($user_id)"
		);
	}
	else
	{
		return false;
	}
}

function fb_login()
{
	try
	{
		global $facebook;
		
		$params = array(
			'scope'			=> 'manage_pages',
			'redirect_uri'	=> php_self(),
		);
		$url = $facebook->getLoginUrl($params);
	
		header("Location:" . $url);
	
		
		
	}
	catch(FacebookApiException $ex)
	{
		trigger_error($ex->getResult());
	}
}

function is_logged_in()
{
	global $facebook, $fb_config, $session;
	try
	{
		
		$result = $facebook->api('/me', 'get');
		
		if(isset($result['id']))
		{
			$session['access_token'] = $facebook->getAccessToken();
			return true;
		}
		
		if(isset($session['access_token']))
		{
			$facebook->setAccessToken($session['access_token']);
			$result = $facebook->api('/me', 'get');
			
			if(isset($result['id']))
			{
				return true;
			}
		}
		
		if(isset($_COOKIE['fbsr_' . $fb_config['appId']]))
		{
			$facebook->setAccessToken($_COOKIE['fbsr_' . $fb_config['appId']]);
			$result = $facebook->api('/me', 'get');
			
			if(isset($result['id']))
			{
				$session['access_token'] = $facebook->getAccessToken();
				return true;
			}
		}
	}
	catch(FacebookApiException $ex)
	{
		return false;
	}
}

function is_a_bot($ip, $ua)
{
	global $con;
	
	$sql = "SELECT * FROM " . TABLE_BOTS . " WHERE ip_address='$ip' AND uas='$ua'";
	
	$result = mysqli_query($con, $sql);
	
	if($result->num_rows > 0)
	{
		return mysqli_fetch_assoc($result);
	}
	else
	{
		return false;
	}
}

function find_session()
{
	global $con;
	
	$ua = $_SERVER['HTTP_USER_AGENT'];
	$ip = $_SERVER['REMOTE_ADDR'];
	
	$sql = "SELECT * FROM " . TABLE_SESSIONS . " WHERE ip_address='$ip' AND user_agent='$ua'";
	
	$result = mysqli_query($con, $sql);
	
	if($result->num_rows > 0)
	{
		$row = mysqli_fetch_assoc($result);
		
		mysqli_free_result($result);
		
		return $row;
	}
	else
	{
		
		return false;
	}
}

function create_session()
{
	global $con, $session;
	
	$ua = $_SERVER['HTTP_USER_AGENT'];
	$ip = $_SERVER['REMOTE_ADDR'];
	
	$bot = is_a_bot($ip, $us);
	
	if(isset($_SESSION['mu_session']))
	{
		$session = $_SESSION['mu_session'];
	}
	elseif(!($session = find_session()))
	{
		
		
		$sql = "INSERT INTO " . TABLE_SESSIONS . "(ip_address, user_agent, bot) VALUES('$ip','" . addslashes($ua) . "','" . (!$bot ? 0 : json_encode($bot)) . "')";
		
		$result = mysqli_query($con, $sql);
		
		if(!$result)
		{
			print_r(mysqli_error($con));
			throw new Exception("Could not enter session into database.", 0);
			
		}
		
		$session = find_session();
		
		if(!$session)
		{
			throw new Exception("Unknown error whilst creating session.", 0);
		}
		else
		{
			$_SESSION['mu_session'] = $session;
		}
	}
	
	if(!$bot)
	{
		update_visitors_log($ip);
	}
	update_session();
}

function update_session()
{
	global $con, $session, $fb_config;
	
	$values = array();
	
	$values['last_activity'] = date('Y-m-d H:i:s');
	
	$values['last_page'] = $_SERVER['PHP_SELF'];
	
	if(is_logged_in())
	{
		$values['access_token'] = $session['access_token'];
	}
	
	$sql = build_update_query(TABLE_SESSIONS, $values, "id=" . $session['id']);
	
	$result = mysqli_query($con, $sql);
	
	if(!$result)
	{
		throw new Exception(mysqli_error($con));
	}
	
	array_merge($session, $values);
	
	$_SESSION['mu_session'] = $session;
	
	return true;
}

function update_visitors_log($ip)
{
	global $con;
	
	$sql = "SELECT * FROM " . TABLE_VISITORS . " WHERE ip_address='" . $ip . "'";
	
	$result = mysqli_query($con, $sql);
	
	
	
	if(mysqli_num_rows($result) > 0)
	{
		$row = mysqli_fetch_array($result);
		
		if(strtotime($row['last_visit']) < strtotime('-1 day',time()))
		{
		
			$sql = "UPDATE " . TABLE_VISITORS . " SET visits=" . ($row['visits'] + 1) . ", last_visit=NOW() WHERE ip_address='" . $ip . "'";
			//echo $sql;
			mysqli_query($con, $sql);
		}
	}
	else
	{
		$sql = "INSERT INTO " . TABLE_VISITORS . "(ip_address, visits) VALUES('" . $ip . "',1)";
		
		mysqli_query($con, $sql);
	}
}

function build_update_query($table, $values, $where)
{
	foreach($values as $key=>$value)
	{
		$values[$key] = "$key='$value'";
	}
	
	$values = implode(", ", $values);
	
	$sql = "UPDATE $table SET $values WHERE $where";
	
	return $sql;
}

function build_insert_query($table, $values)
{
	$keys = array_keys($values);
	
	$keys = implode(",", $keys);
	
	$values = implode("','", $values);
	
	$sql = "INSERT INTO $table($keys) VALUES('$values')";
	
	return $sql;
}

function build_multi_insert_query($table, $values)
{
	$keys = array_keys($values[0]);
	
	$keys = implode(",", $keys);
	
	$temp_values = array();
	
	foreach($values as $v)
	{
		$temp_values[] = "('" . implode("','", $v) . "')";
	}
	
	$values = implode(",", $temp_values);
	
	$sql = "INSERT INTO $table($keys) VALUES $values";
	
	return $sql;
}

function sql_query_to_array($fields, $index_field, $table, $where, $order_by, $field_map)
{
	global $con;
	
	if(is_array($fields))
	{
		$fields = implode(',', $fields);
	}
	
	$sql = "SELECT $fields FROM $table";
	
	if(isset($where) && $where != null)
	{
		$sql .= " WHERE $where";
	}
	
	if(isset($order_by))
	{
		$sql .= " ORDER BY $order_by";
	}
	
	$result = mysqli_query($con, $sql);
	
	$return_array = array();
	
	while($row = mysqli_fetch_assoc($result))
	{
		if(is_array($field_map))
		{
			
			$temp_row = array();
			
			foreach($field_map as $key=>$value)
			{
				$temp_row[$key] = $row[$value];
			}
			
		}
		else
		{
			$temp_row = $row;
		}
		if($index_field == null)
		{
			$return_array[] = $temp_row;
		}
		else
		{
			$return_array[$row[$index_field]] = $temp_row;
		}
	}
	
	return $return_array;
}

function resize_and_store_image(&$filename, $location, $new_width, $new_height)
{
	$errors			= array();
	
	$image 			= $_FILES['file']['name'];
	$uploaded_file 	= $_FILES['file']['tmp_name'];
	
	$filename		= stripslashes($image);
	$ext		= get_file_extension($filename, FILE_TYPE_IMAGE);
	
	if(!$ext)
	{
		return 'Invalid extension.';
	}
	
	$size = filesize($uploaded_file);
	
	if($size > MAX_FILE_SIZE*1024)
	{
		return 'File too big.';
	}
	
	if($ext == ".jpg" || $ext == ".jpeg")
	{
		$src = imagecreatefromjpeg($uploaded_file);
	}
	else if($ext == ".png")
	{
		$src = imagecreatefrompng($uploaded_file);
	}
	else
	{
		$src = imagecreatefromgif($uploaded_file);
	}
	
	list($width, $height) = getimagesize($uploaded_file);
	
	if(!isset($new_height))
	{
		$new_height = DEFAULT_IMAGE_HEIGHT;
	}
	
	if(!isset($new_width))
	{
		$new_width = DEFAULT_IMAGE_WIDTH;
	}
	
	$new_height = ($height / $width) * $new_width;
	
	$tmp = imagecreatetruecolor($new_width, $new_height);
	
	imagecopyresampled($tmp, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	
	$new_filename = time() . ".jpg";
	$path = realpath($location) . "/$new_filename";
	
	$filename = "./$location$new_filename";
	imagejpeg($tmp, $path, 100);
	
	imagedestroy($src);
	imagedestroy($tmp);
	
	$filename = $new_filename;
	return false;
}

function get_file_extension($filename, $type)
{
	global $file_types;
	$index = strrpos($filename, ".");
	
	if(!$index)
	{
		return false;
	}
	
	$ext = substr($filename, $index);
	
	if(strlen($ext) < 2)
	{
		return false;
	}
	
	if(isset($type))
	{
		
		if(!in_array($ext, $file_types[$type]))
		{
			return false;
		}
		
		if(in_array($ext, $file_types[FILE_TYPE_BANNED]))
		{
			return false;
		}
		
	}
	return $ext;
}

function post_event_to_fb($id, $access_token)
{
	try
	{
		global $con, $root_path, $image_path, $event_thumbnail_path, $facebook, $fb_page_id;
		
		$sql = "SELECT *, " . TABLE_EVENT . ".image AS event_image FROM " . TABLE_EVENT . " INNER JOIN " . TABLE_COMIC . " ON " . TABLE_EVENT . ".comic=" . TABLE_COMIC . ".id WHERE " . TABLE_EVENT . ".id=" . $id;
		
		$result = mysqli_query($con, $sql);
		
		$event = mysqli_fetch_array($result);
		
		if($event['status'] == 'waiting')
		{
			$msg = array(
				'link'			=> $root_path . 'index.php?event_id=' . $id,
				'description'	=> strip_tags($event['summary']),
				'title'			=> $event['title'] . ' #' . $event['issue_number'],
				'name'			=> $event['title'] . ' #' . $event['issue_number'],
				'message'		=> 'COMIC ADDED!',
				'picture'		=> $root_path . $image_path . $event_thumbnail_path . $event['event_image'],
				'access_token'	=> $access_token
			);
			
			$result = $facebook->api('/' . $fb_page_id . '/feed', 'post', $msg);
			if(isset($result['id']))
			{
				$sql = build_update_query(TABLE_EVENT, array('status' => 'published'), 'id=' . $_GET['id']);
				
				if(!mysqli_query($con, $sql))
				{
					return false;
				}
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			throw new Exception($event['title'] . ' #' . $event['issue_number'] . ' has already been published to Facebook.');
		}
	}
	catch(FacebookApiException $ex)
	{
		//trigger_error($ex->getMessage());
		return false;
	}
	catch(OAuthException $ex)
	{
		return false;
	}
	catch(Exception $ex)
	{
		return false;
	}
}

function toXml($data, $rootNodeName = 'data', $xml=null)
{
	// turn off compatibility mode as simple xml throws a wobbly if you don't.
	if (ini_get('zend.ze1_compatibility_mode') == 1)
	{
		ini_set ('zend.ze1_compatibility_mode', 0);
	}

	if ($xml == null)
	{
		$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
	}

	// loop through the data passed in.
	foreach($data as $key => $value)
	{
		// no numeric keys in our xml please!
		if (is_numeric($key))
		{
			// make string key...
			$key = "unknownNode_". (string) $key;
		}

		// replace anything not alpha numeric
		$key = preg_replace('/[^a-z]/i', '', $key);

		// if there is another array found recrusively call this function
		if (is_array($value))
		{
			$node = $xml->addChild($key);
			// recrusive call.
			toXml($value, $rootNodeName, $node);
		}
		else 
		{
			// add single node.
							$value = htmlentities($value);
			$xml->addChild($key,$value);
		}

	}
	// pass back as string. or simple xml object if you want!
	return $xml->asXML();
}

if(!function_exists('php_self'))
{
   function php_self($dropqs=true) {
   $url = sprintf('%s://%s%s',
     empty($_SERVER['HTTPS']) ? (@$_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http') : 'http',
     $_SERVER['SERVER_NAME'],
     $_SERVER['REQUEST_URI']
   );
   
   $parts = parse_url($url);
   
   $port = $_SERVER['SERVER_PORT'];
   $scheme = $parts['scheme'];
   $host = $parts['host'];
   $path = @$parts['path'];
   $qs   = @$parts['query'];
   
   $port or $port = ($scheme == 'https') ? '443' : '80';
   
   if (($scheme == 'https' && $port != '443')
      || ($scheme == 'http' && $port != '80')) {
     $host = "$host:$port";
   }
   $url = "$scheme://$host$path";
   if ( ! $dropqs)
     return "{$url}?{$qs}";
   else
     return $url;
   }
}
?>