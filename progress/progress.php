<?php
chdir('..');			//Go up to the site root
require_once('db.php');	//Process login
if(!$db->loggedIn()) exit;

//If ID is defined, we return the Progress and Maximum for the requested progress item
//Otherwise, we return the ID of the latest progress item (for the current user) matching the requested action type
$query = '';
$fail = 0;
if(isset($_GET['id']) && is_numeric($_GET['id']))
{
	$query = 'SELECT progress,max AS "maximum" FROM progress WHERE id=' . mysql_real_escape_string($_GET['id']) . ' LIMIT 1';
	@$res = mysql_query($query);
	if($res === false) $fail = 1;
	@$res = mysql_fetch_assoc($res);
	if($res === false) $fail = 2;
}else if(isset($_GET['what'])){
	$query = 'SELECT id FROM progress WHERE what="' . mysql_real_escape_string($_GET['what']) . '" AND user=' . $db->user->id
				. ' AND connected=0 ORDER BY created DESC LIMIT 1';
	@$res = mysql_query($query);
	if($res === false) $fail = 1;
	@$res = mysql_fetch_assoc($res);
	if($res === false) $fail = 2;
	if($fail == 0) mysql_query('UPDATE progress SET connected=1 WHERE id=' . $res['id'] . ' LIMIT 1');
}else{
	$fail = 3;
}
$res['fail'] = $fail;
echo('(' . json_encode($res) . ');');
?>