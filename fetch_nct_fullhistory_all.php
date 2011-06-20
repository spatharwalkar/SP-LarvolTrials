<?php
require_once('db.php');
require_once('include.search.php');
ini_set('max_execution_time', '36000'); //10 hours

echo('Finding highest nct_id...<br />'); @flush();
$fid = getFieldId('NCT','nct_id');
$query = 'SELECT MAX(val_int) AS maxid FROM data_values WHERE `field`=' . $fid;
$res = mysql_query($query) or die('Bad SQL query finding highest nct_id');
$res = mysql_fetch_array($res) or die('No nct_id found!');
$maxid = $res['maxid'];
//find start id
$cid = (isset($_GET['start']) && is_numeric($_GET['start'])) ? ((int)$_GET['start']) : 1;
//go
echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
for(; $cid <= $maxid; ++$cid)
{
	$_GET['id'] = $cid;
	require('fetch_nct_fullhistory.php');
	@flush();
}
echo('Done with all IDs.');
?>