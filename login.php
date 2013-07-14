<?php
include("./includes/header.php");
try
{
	global $facebook;
	
	$redirect = $_GET['redirect'];
	
	if(!isset($_GET['code']))
	{
		$params = array(
			'scope'			=> 'manage_pages',
			'redirect_uri'	=> './login.php?redirect=' . urlencode($_GET['redirect']),
		);
		$url = $facebook->getLoginUrl($params);
	
		$smarty->assign('login_url', $url);
		
	
		
		header("location:" . $url);
	}
	
	$facebook->setExtendedAccessToken();

	$_SESSION['fb_access_token'] = $facebook->getAccessToken();
	
	$url = $redirect;
	
	$smarty->assign('login_url', $url);
	
	$page = 'login.html';	
	
}
catch(FacebookApiException $ex)
{
	$smarty->assign('exception', $ex->getResult());
}

include("./includes/footer.php");

?>