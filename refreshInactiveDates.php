<?php
//core script for all inactive date updates.
require_once 'include.derived.php';

ini_set('max_execution_time','360000');	//100 hours

$timeStart = microtime(true);
$larvolId = ($_GET['id'])?$_GET['id']:null;
$action = ($larvolId)?'search':'';
if($larvolId)
{
	$fieldArr = calculateDateFieldIds();
	refreshInactiveDates($larvolId,$action,$fieldArr);
}
else
{
	refreshLarvolIds();
}
$timeEnd = microtime(true);
$timeTaken = $timeEnd-$timeStart;
echo '<br/>Time Taken : '.$timeTaken;
