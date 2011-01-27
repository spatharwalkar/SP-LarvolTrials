<?php
require_once('db.php');
if(!$db->loggedIn() || !isset($_GET['id']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');

$id = mysql_real_escape_string($_GET['id']);
if(!is_numeric($id)) exit;

$param = new SearchParam();
$param->field = 'larvol_id';
$param->action = 'search';
$param->value = $id;

$doc = file_get_contents('templates/summary.htm');
$list = array('larvol_id','_'.getFieldId('NCT','nct_id'));
foreach($db->types as $field => $type)
{
	if(strpos($doc,'#' . $field . '#') !== false) $list[] = $field;
}
$res = search(array($param),$list,1,true);
$study;
foreach($res as $stu) $study = $stu;
$study['NCT/nct_id'] = padnct($study['NCT/nct_id']);
$values = array();
foreach($list as $i => $field)
{
	$values[] = ($study->{$field} === NULL) ? '' : (is_array($study->{$field}) ? implode(', ', $study->{$field}) : $study->{$field});
	$list[$i] = '#' . $field . '#';
}
$doc = str_replace($list, $values, $doc);

//Send headers for file download
header("Pragma: public");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/download");
header("Content-Type: application/msword");
header("Content-Disposition: attachment;filename=summary.doc");
header("Content-Transfer-Encoding: binary ");
echo($doc);
@flush();
?>