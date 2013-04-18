<?php
require_once('db.php');
if($_SERVER['QUERY_STRING'] == 'logout') $db->logout();

if($db->loggedIn())
{
	require('search.php');
}else{
	require_once('header.php');
	/*$welcome = @file_get_contents('welcome.html');
	if($welcome === false)
	{*/
		echo('Editors: <a href="login.php">login</a> or click <a href="trialzilla.php">here</a> to go to Larvol Sigma');
	/*}else{
		echo($welcome);
	}*/
	echo('</body></html>');
}
?>
