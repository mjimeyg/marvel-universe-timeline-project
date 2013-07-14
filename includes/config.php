<?php

include('facebook/facebook.php');

global $con, $file_types;

$mu_server = '****';
$mu_username = '****';
$mu_password = '****';
      
$mu_database = 'marvel_timeline';

$table_prefix = 'mu_';

$fb_config = array(
	'appId'	 => 151142891650131,
	'secret'	 => '****',
);

$fb_page_id = 320015704681091;

$root_path = 'http://www.marvel-timeline.com/';

$image_path = 'images/';

$comic_thumbnail_path = 'comic_thumbnails/';

$event_thumbnail_path = 'event_thumbnails/';

$character_thumbnail_path = 'character_thumbnails/';

define('ACTIVE_USER',		0);
define('BANNED_USER',		2);

// Tables
define('TABLE_ARC',					$table_prefix . 'arc');
define('TABLE_CHARACTER',			$table_prefix . 'character');
define('TABLE_CHARACTER_EVENTS',	$table_prefix . 'character_events');
define('TABLE_COMIC',				$table_prefix . 'comic');
define('TABLE_CONFIG',				$table_prefix . 'config');
define('TABLE_EVENT',				$table_prefix . 'event');
define('TABLE_LOG',					$table_prefix . 'log');
define('TABLE_USERS',				$table_prefix . 'users');

define('FILE_TYPE_IMAGE',	0);
define('FILE_TYPE_BANNED',	1);

// Max file size
define('MAX_FILE_SIZE',		400);

define('DEFAULT_IMAGE_WIDTH',	100);
define('DEFAULT_IMAGE_HEIGHT',	100);

$month_names = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December'
);

$file_types = array(
	FILE_TYPE_IMAGE		=> array(".jpg", ".jpeg", ".png", ".gif"),
	FILE_TYPE_BANNED	=> array(".php", ".js", ".css", ".exe", ".dmg"),
);

$con = mysqli_connect($mu_server, $mu_username, $mu_password, $mu_database);
                    
if(mysqli_connect_errno($con))
{
    echo json_encode(array(
		'error'			=> 'Database Error',
		'error_message'	=> mysqli_connect_errno(),
	));
	exit;	
}

global $facebook, $fb_user, $config;

$sql = "SELECT * FROM mu_config";

$result = mysqli_query($con, $sql);

$config = array();

while($row = mysqli_fetch_array($result))
{
	$config[$row['name']] = $row['value'];
}

$facebook = new Facebook($fb_config);

$fb_user = $facebook->getUser();
?>