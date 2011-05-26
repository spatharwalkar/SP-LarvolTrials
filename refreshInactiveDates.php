<?php
//core script for all inactive date updates.
require_once 'include.derived.php';


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
