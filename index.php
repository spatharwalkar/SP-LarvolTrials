<?php
require_once('db.php');
if($_SERVER['QUERY_STRING'] == 'logout') $db->logout();

if($db->loggedIn())
{
	require('newsearch.php');
}else{
	require_once('header.php');
	/*$welcome = @file_get_contents('welcome.html');
	if($welcome === false)
	{*/
		echo('Editors: <a href="login.php">login</a> or click <a href="sigma/">here</a> to go to Larvol Sigma');
	/*}else{
		echo($welcome);
	}*/
	echo('</body></html>');
}
?>
