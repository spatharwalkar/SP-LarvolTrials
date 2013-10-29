<?php
$cwd = getcwd();
chdir ("..");
require_once('db.php');
chdir ($cwd);
$user = $db->user->id;
if(isset($_GET['user']))
{
	$user = (int)$_GET['user'];
}
echo('Profile for user ' . $user . '<br />');
$query = 'SELECT username,userlevel,realname,country,linkedin_url FROM users WHERE id=' . $user . ' LIMIT 1';
$res = mysql_query($query);
if($res === false) die("Couldn't find user.");
$res = mysql_fetch_assoc($res);
foreach($res as $field => $value)
{
	if($field == 'userlevel')
	{
		echo('User Type: ' . ($value == 'public' ? 'Member' : 'Larvol Representative'));
	}else{
		echo($field . ': ' . $value);
	}
	echo('<br />');
}
$query = 'SELECT COUNT(*) AS `total` FROM commentics_comments WHERE userid=' . $user . ' AND `name` != ""';
$res = mysql_query($query);
if($res === false) die("Couldn't get comment count.");
$res = mysql_fetch_assoc($res);
$res = $res['total'];
echo('User has made ' . $res . ' comments.');
?>