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
?>