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
		echo('Please <a href="login.php">login</a>!');
	/*}else{
		echo($welcome);
	}*/
	echo('</body></html>');
}
?>
