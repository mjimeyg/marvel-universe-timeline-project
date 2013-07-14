<?php
include("./includes/header.php");

if(!isset($_GET['id']))
{
	trigger_error('Invalid event id.');
}

?>
<script language="javascript" type="text/javascript">
	setTimeout(function(){
	window.location = "./index.php?event_id=<?php echo $_GET['id']; ?>";
	}, 3000);
</script>
<h2>Redirecting...</h2>

<p>This is an old link, please update you book marks, we are redirecting you to the correct page or you can click the link below:</p>

<a href="./index.php?event_id=<?php echo $_GET['id']; ?>">./index.php?event_id=<?php echo $_GET['id']; ?></a>


<?php

include("./includes/footer.php");

?>