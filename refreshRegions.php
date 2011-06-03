<?php
//core script for all region updates.
require_once 'include.derived.php';

ini_set('max_execution_time','360000');	//100 hours

$timeStart = microtime(true);
$larvolId = ($_GET['id'])?$_GET['id']:null;
$action = ($larvolId)?'search':'';
if($larvolId)
{
	$fieldArr = calculateRegionFieldIds();
	refreshRegions($larvolId,$action,$fieldArr);
}
else
{
	refreshRegionLarvolIds();
}
$timeEnd = microtime(true);
$timeTaken = $timeEnd-$timeStart;
echo '<br/>Time Taken : '.$timeTaken;
