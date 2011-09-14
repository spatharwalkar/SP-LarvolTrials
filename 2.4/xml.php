<?php
require_once('db.php');
if(!$db->loggedIn() || !isset($_POST['params']) || !isset($_POST['list']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

header("Pragma: public");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");

// Get serialized data and query the database.
$params = unserialize(base64_decode($_POST['params']));
$list = unserialize(base64_decode($_POST['list']));
$time = base64_decode($_POST['time']);
if($params === false || $list === false) die('Unable to parse search results -- it might work if you just try it again');
$time = strlen($time) ? $time : NULL;
//$source = unserialize(base64_decode($_POST['searchresults']));
$source = search($params,$list,NULL,$time);

//process data -- Pad the NCTID out and remove unset values
foreach($source as $id => $study)
{
	$newstudy = array();
	if(isset($study['NCT/nct_id']))
	{
		$newstudy['NCT.nct_id'] = padnct($study['NCT/nct_id']);
	}

	foreach($study as $field => $value)
	{
		if($field == 'NCT/nct_id') continue;
		$newstudy[str_replace('/','.',$field)] = $value;
	}
	$source[$id] = $newstudy;
}
unset_nulls($source);
unset_nulls($source);

// Build XML
$xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<results>' . "\n" . toXML($source) . "\n" . '</results>';

//Send download
header("Content-Type: text/xml");
header("Content-Disposition: attachment;filename=data.xml");
header("Content-Transfer-Encoding: binary ");
echo($xml);
@flush();
?>