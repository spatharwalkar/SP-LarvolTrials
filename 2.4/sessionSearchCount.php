<?php
header('P3P: CP="CAO PSA OUR"');
require_once('db.php');
if(!$db->loggedIn())
{
	//header('Location: ' . urlPath() . 'index.php');
	require('index.php');
	exit;
}
require_once('include.search.php');
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(SITE_NAME); ?></title>
<style media="all" type="text/css">
body{
	border:0;margin:0;padding:0;
	font-family:Verdana, Geneva, sans-serif;
	font-size:small;
}
</style>
</head>
<body>
<?php

/*
	Whenever a user performs a search through the main interface, the params are
	stored in a session variable. This script reads those params and returns the
	total record match COUNT for that search. It also caches.
*/

//validate input
if(!isset($_SESSION['params']) || !is_array($_SESSION['params'])
	|| !isset($_SESSION['counts']) || !is_array($_SESSION['counts'])
	|| !isset($_SESSION['latest']) )
{
	echo('?');
	exit;
}
//check for cached result
//var_dump($_SESSION);
if(isset($_SESSION['counts'][$_SESSION['latest']]))
{
	echo($_SESSION['counts'][$_SESSION['latest']]);
	exit;
}
//calculate result
$count = search($_SESSION['params'][$_SESSION['latest']]['params'],NULL,NULL,$_SESSION['params'][$_SESSION['latest']]['time'],$_SESSION['params'][$_SESSION['latest']]['override']);
$_SESSION['counts'][$_SESSION['latest']] = $count;
echo($count);
echo('</body></html>');
session_write_close();
?>