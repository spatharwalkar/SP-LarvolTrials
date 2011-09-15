<?php
require_once('db.php');
if(!$db->loggedIn() || !isset($_GET['id']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
ob_start();
require_once('include.search.php');

$id = mysql_real_escape_string($_GET['id']);
if(!is_numeric($id)) exit;

$param = new SearchParam();
$param->field = 'larvol_id';
$param->action = 'search';
$param->value = $id;

$doc = file_get_contents('templates/summary.htm'); 

foreach($db->types as $field=>$type)
{
	//if(!array_key_exists('larvol_id',$list)) 
	$list[] = $field;
}

$override = array(1);
$res = search(array($param),$list,1,strtotime(date('Y-m-d H:i:s')));
$study;
foreach($res as $stu) $study = $stu; 
$study['NCT/nct_id'] = padnct($study['NCT/nct_id']);

$values = array();
$fields = array();

foreach($study as $key=>$val)
{
	if($val != NULL) { 
		if(is_array($val)) {
			$values[$key] = implode(', ', $val );	
		} else {
			$values[$key] = $val ;
		}

		if($key == "NCT/primary_outcome_measure" || $key == "NCT/secondary_outcome_measure") {
			$key = substr($key,0,-8);
		}
		if(strpos($key, "NCT/") !== false) {
			$fields[$key] = "%#(.*?)".substr($key,4)."(.*?)#%";
			//regex to match field names(which contains NCT as a prefix) and values in the template file

		} else {
			$fields[$key] = "%#(.*?)".$key."(.*?)#%";//regex to match the field names(which does not contain NCT as a prefix) and values in the template file
		}
		
		
	} 
}

/*from inwards to outwards 
first preg_replace to replace the replacements text from the template file with the matched values from the db
second preg_replace to empty the replacement text for which a match has not been found.
*/
$doc = preg_replace("%#(.*?)#%", "",preg_replace($fields, $values, $doc));
ob_end_clean();
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