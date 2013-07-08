<?php
// Use sessions instead of cookies
//setcookie('li_user',$_GET['u'],time()+(3600*24*365*31),'/','.larvoltrials.com'); //31 years
// Start session 
@session_start();
// store session data
$_SESSION['li_user']=$_GET['u'];
$userip=$_SERVER['REMOTE_ADDR'];
$userid=$_GET['u'];
$cwd = getcwd();
	chdir ("..");
	require_once('db.php');
	chdir ($cwd);
$query = 'SELECT `ip`,`id` FROM li_login WHERE ip="'.$userip.'" and id="'.$userid.'" limit 1' ;
$res = mysql_query($query) or die('Bad SQL Query getting login info');
$row = mysql_fetch_assoc($res);
if($row['ip'] && $row['ip'] == $userip)
{
	$_SESSION['li_user']=$_GET['u'];
}	
else 
{
	$query = 'INSERT INTO li_login set ip="'.$userip.'", id="'.$userid.'" ' ;
	$res = mysql_query($query) or die('Bad SQL Query inserting login info');
	$_SESSION['li_user']=$_GET['u'];
}

//$_SESSION['li_user']=$_GET['u'];

header('Content-Type: image/gif');
readfile('../images/beacon.gif');

?>
