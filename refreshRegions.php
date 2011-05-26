<?php
//core script for all region updates.
require_once 'include.derived.php';

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
