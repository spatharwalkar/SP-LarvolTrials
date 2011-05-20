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

//url parameter web=1 is needed to call the script from browser
if($_GET['web']==1)
{
	$timeStart = microtime(true);
	$larvolId = ($_GET['id'])?$_GET['id']:null;
	$action = ($larvolId)?'search':'';
	refreshInactiveDates($larvolId,$action);
	$timeEnd = microtime(true);
	$timeTaken = $timeEnd-$timeStart;
	echo '<br/>Time Taken : '.$timeTaken;
}


/**
 * 
 * @name applyInactiveDate
 * @tutorial Search function used to get the overall_status,completion_date and primary_completion_date values.
 * If larvolId is present function searches for the specific larvolId and updates inactiveDate.
 * If no larvolId is present all available larvolId's are listed and updates inactiveDate
 * @param int $larvolId 
 * @param $action It is either empty string or search. Search is used for individual larvolIds
 * @author Jithu Thomas
 * 
 */
function refreshInactiveDates($larvolId,$action)
{
	$param = new SearchParam();
	$param->field = 'larvol_id';
	$param->action = $action;
	$param->value = $larvolId;
	$param->strong = 1;
	
	$prm = array($param);
	
	$fieldnames = array('overall_status','completion_date','primary_completion_date','overall_status');
	foreach($fieldnames as $name)
	{ 
		
		$param = new SearchParam();
		$param->field = '_'.getFieldId('NCT',$name);
		$param->action ='';
		$param->value = '';
		$param->strong = 1;
		$prm[] = $param;
		$list[] = $param->field;
	
	}	

	
	$res = search($prm,$list,NULL,NULL);
	applyInactiveDate($res);
}


/**
 * 
 * @name applyInactiveDate
 * @tutorial Function applies derived field inactive_date for each search result array passed.
 * @param array $arr is an array of search result from the search() function.
 * @author Jithu Thomas
 */
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
		
		if($inactiveDate =='0000-00-00' || $inactiveDate=='')
		$query  = "update clinical_study set inactive_date=null where larvol_id=$larvolId";
		else
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

