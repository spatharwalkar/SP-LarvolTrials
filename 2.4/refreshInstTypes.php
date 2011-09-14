<?php
require_once('db.php');
require_once 'include.derived.php';
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

ini_set('max_execution_time','360000');	//100 hours

$timeStart = microtime(true);
$larvolId = ($_GET['id'])?$_GET['id']:null;
$action = ($larvolId)?'search':'';
if($larvolId)
{
	$fieldArr = calculateInstitutionTypeFieldIds();
	refreshInstitutionType($larvolId,$action,$fieldArr);
}
else
{
	refreshInstitutionTypeLarvolIds();
}
$timeEnd = microtime(true);
$timeTaken = $timeEnd-$timeStart;
echo '<br/>Time Taken : '.$timeTaken;


die;
//old code
mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
$query = 'SELECT nct_id,lead_sponsor FROM clinical_study';
$res = mysql_query($query) or die('Bad SQL query getting studies');
while($row = mysql_fetch_assoc($res))
{
	$id = $row['nct_id'];
	echo($id . ' ');
	$insts = array($row['lead_sponsor']);
	$query2 = 'SELECT collaborator FROM sponsors WHERE nct_id=' . $id;
	$res2 = mysql_query($query2) or die('Bad SQL query getting collaborators');
	while($row2 = mysql_fetch_assoc($res2)) $insts[] = $row2['collaborator'];
	$type = 'other';
	foreach($insts as $inst)
	{
		if(isset($mapping[$inst]))
		{
			$type = $mapping[$inst];
			if($type == 'industry') break;
		}
	}
	echo($type . '<br/>');
	$query2 = 'UPDATE clinical_study SET institution_type="' . $type . '" WHERE nct_id=' . $id . ' LIMIT 1';
	mysql_query($query2) or die('Bad SQL query updating institution type');
}
mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
echo('Done!');
?>