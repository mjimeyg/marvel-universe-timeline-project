<?php
include("./includes/header.php");
try
{
	$page = 'faq.html';
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