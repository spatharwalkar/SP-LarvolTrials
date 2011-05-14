<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

$larvolId = ($_GET['id'])?$_GET['id']:null;
$action = ($larvolId)?'search':'require';

$param = new SearchParam();
$param->field = 'larvol_id';
$param->action = $action;
$param->value = $larvolId;
$param->strong = 1;
$time = time('now');

$prm = array($param);

$fieldnames = array('overall_status','brief_title','completion_date','primary_completion_date','overall_status');
foreach($fieldnames as $name)
{ 
	
	$param = new SearchParam();
	$param->field = fieldNameToPaddedId($name);
	$param->action = 'require';
	$param->value = '';
	$param->strong = 1;
	$prm[] = $param;
	$list[] = fieldNameToPaddedId($name);

}	
	$time = time('now');	
//$list = array('_1','_124');

$res = search($prm,$list,NULL,NULL);
applyInactiveDate($res);

//Get field IDs for names
// - for the $list argument, search() takes IDs prepended with a padding character (stripped by highPass())
// - didn't find the alternative, so I wrote this
function fieldNameToPaddedId($name)
{
	$query = 'SELECT data_fields.id AS data_field_id FROM '
		. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="NCT" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) tex('Bad SQL query getting field ID of ' . $name);
	$res = mysql_fetch_assoc($res);
	if($res === false) tex('NCT schema not found!');
	return '_' . $res['data_field_id'];
}

function applyInactiveDate($arr=array())
{
	global $db;
	if(count($arr)>0)
	{
		mysql_query('BEGIN') or die('Cannot being transaction');
		echo 'Starting transaction.<br/>';
	}
	$flag = 0;
	foreach($arr as $res)
	{
		$overallStatus = $res['NCT/overall_status'];
		$completionDate = $res['NCT/completion_date'];
		$primaryCompletionDate = $res['NCT/primary_completion_date'];
		$larvolId = $res['larvol_id'];
		
		if($completionDate)
		{
			$inactiveDate = $completionDate;
		}
		elseif($primaryCompletionDate) 
		{
			$inactiveDate = $primaryCompletionDate;
		}
		elseif($overallStatus)
		{
			
			$activeStatus = array(
								'Not yet recruiting',
								'Recruiting',
								'Enrolling by invitation',
								'Active, not recruiting',
								'Available'
								);
			$inactiveStatus = array(
								'Withheld',
								'Approved for marketing',
								'Temporarily not available',
								'No Longer Available',
								'Withdrawn',
								'Terminated',
								'Suspended',
								'Completed'
								);								
			$query = "select id from data_cats_in_study where larvol_id=$larvolId";
			$res = mysql_query($query);
			$studyCatId = mysql_fetch_row($res);
			$studyCatId = $studyCatId[0];
			$query = "SELECT dv.added FROM data_values dv LEFT JOIN data_fields df ON df.id=dv.field 
					LEFT JOIN data_enumvals de ON de.field=df.id WHERE dv.studycat=$studyCatId 
					AND df.name='overall_status' AND de.value in ('".implode(',',$inactiveStatus)."') ORDER BY dv.added ASC";
			
			$res = mysql_query($query);
			$addedDate = mysql_fetch_row($res);
			$addedDate = $addedDate[0];
			
			$inactiveDate = $addedDate;
			
			
		}
		
		$query  = "update clinical_study set inactive_date='".$inactiveDate."' where larvol_id=$larvolId";
		if(mysql_query($query))
		{
			$flag=1;
		}
		else
		{
			die('Cannot update inactive_date. '.$query);
		}
	}
	if($flag == 1)
	{
		mysql_query('COMMIT') or die('Cannot commit transaction');
		echo 'Transaction commited successfully.';
	}
	
	
}

