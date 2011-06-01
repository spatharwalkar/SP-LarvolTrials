<?php
ini_set('max_execution_time','360000');	//100 hours
require_once('db.php');
require_once('include.search.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}


//start inactive date functions


function refreshLarvolIds()
{
	global $db;
	
	//calculate field Ids and store in an array since it requires db call
	$fieldArr = calculateDateFieldIds();

	$query = "select larvol_id from clinical_study";
	$res = mysql_query($query);
	while($row = mysql_fetch_array($res))
	{
		$larvolId = $row['larvol_id'];
		echo 'Larvol Id :'.$larvolId.'<br/>';
		refreshInactiveDates($larvolId, 'search',$fieldArr);
	}
}


function calculateDateFieldIds()
{
	$fieldnames = array('completion_date','primary_completion_date','overall_status');
	$fieldArr = array();
	foreach($fieldnames as $name)
	{
		$fieldArr[$name] = getFieldId('NCT',$name);
	}
	return $fieldArr;
}

/**
 * 
 * @name refreshInactiveDates
 * @tutorial Search function used to get the overall_status,completion_date and primary_completion_date values.
 * If larvolId is present function searches for the specific larvolId and updates inactiveDate.
 * If no larvolId is present all available larvolId's are listed and updates inactiveDate
 * @param int $larvolId 
 * @param $action It is either empty string or search. Search is used for individual larvolIds
 * @author Jithu Thomas
 * 
 */
function refreshInactiveDates($larvolId,$action,$fieldArr)
{
	$param = new SearchParam();
	$param->field = 'larvol_id';
	$param->action = $action;
	$param->value = $larvolId;
	$param->strong = 1;
	
	$prm = array($param);
	
	$fieldnames = array('completion_date','primary_completion_date','overall_status');
	foreach($fieldnames as $name)
	{ 
		
		$param = new SearchParam();
		$param->field = '_'.$fieldArr[$name];
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
		echo 'Transaction commited successfully.<br/>';
	}
	
	
}


//end inactive date functions


//start region functions

function refreshRegionLarvolIds()
{
	global $db;
	
	//calculate field Ids and store in an array since it requires db call
	$fieldArr = calculateRegionFieldIds();

	$query = "select larvol_id from clinical_study";
	$res = mysql_query($query);
	while($row = mysql_fetch_array($res))
	{
		$larvolId = $row['larvol_id'];
		echo 'Larvol Id :'.$larvolId.'<br/>';
		refreshRegions($larvolId, 'search',$fieldArr);
	}
}

function calculateRegionFieldIds()
{
	$fieldnames = array('location_country');
	$fieldArr = array();
	foreach($fieldnames as $name)
	{
		$fieldArr[$name] = getFieldId('NCT',$name);
	}
	return $fieldArr;
}

/**
 * 
 * @name refreshRegions
 * @tutorial Search function used to get the location_country.
 * If larvolId is present function searches for the specific larvolId and updates regions.
 * If no larvolId is present all available larvolId's are listed and updates regions
 * @param int $larvolId 
 * @param var $action It is either empty string or search. Search is used for individual larvolIds
 * @param array $fieldArr field arrays are calculated seperately to avoid unnecessary db calls for repeated calls to this function.
 * @author Jithu Thomas
 * 
 */
function refreshRegions($larvolId,$action,$fieldArr)
{
	$param = new SearchParam();
	$param->field = 'larvol_id';
	$param->action = $action;
	$param->value = $larvolId;
	$param->strong = 1;
	
	$prm = array($param);
	
	$fieldnames = array('location_country');
	foreach($fieldnames as $name)
	{ 
		
		$param = new SearchParam();
		$param->field = '_'.$fieldArr[$name];
		$param->action ='';
		$param->value = '';
		$param->strong = 1;
		$prm[] = $param;
		$list[] = $param->field;
	
	}	

	
	$res = search($prm,$list,NULL,NULL);
	if(count($res)>0)
	{
		applyRegions($res);
	}
	else
	{
		mysql_query('BEGIN') or softdie('Cannot begin transaction');
		echo 'Starting transaction.<br/>';	
		$query  = "update clinical_study set region=null where larvol_id=$larvolId";	
		if(mysql_query($query))
		{
			mysql_query('COMMIT') or softdie('Cannot commit transaction');
			echo 'Transaction commited successfully.<br/>';			
		}
		else
		{
			softdie('Cannot update region. '.$query);
		}			
	}
	
	
}	
/**
 * 
 * @name applyInactiveDate
 * @tutorial Function applies derived field regions for each search result array passed.
 * @param array $arr is an array of search result from the search() function.
 * @author Jithu Thomas
 */
function applyRegions($arr)
{
	global $db;
	if(count($arr)>0)
	{
		mysql_query('BEGIN') or die('Cannot begin transaction');
		echo 'Starting transaction.<br/>';
	}
/*	else 
	{
		softdie('No records to update.<br/>');
	}*/	
	
	$flag = 0;
	$flag1 = 0;
	$flag2 = 0;
	$regionArr = regionMapping();
	foreach($arr as $res)
	{	
		$larvolId = $res['larvol_id'];
		$locationCountry = $res['NCT/location_country'];
		if(is_array($locationCountry))
		{
			$tmp1 = array();
			foreach($locationCountry as $tmp)
			{
				$tmp1[] = $tmp;
			}
			$tmp1 = array_unique($tmp1);
			$locationCountry = $tmp1;
		}
		$tmp1 = array();
		foreach($regionArr as $countryName=>$code)
		{
			if(is_array($locationCountry))
			{
				foreach($locationCountry as $tmp)
				{
					if($countryName == $tmp)
					{
						$flag1=1;
						$flag2=1;
						$tmp1[] = $code;
						
					}
				}
				
			}
			else
			{
				if($countryName == $locationCountry)
				{
					$flag1 = 1;
					break;
				}
			}
		}
		if($flag2 ==1)
		$code = implode(',',array_unique($tmp1));
		if($flag1 != 1)
		$code = 'other';
		
		$flag1 = 0;
		
		$query  = "update clinical_study set region='".$code."' where larvol_id=$larvolId";
		if(mysql_query($query))
		{
			$flag=1;
		}
		else
		{
			softdie('Cannot update region. '.$query);
		}		
	}
	if($flag == 1)
	{
		mysql_query('COMMIT') or softdie('Cannot commit transaction');
		echo 'Transaction commited successfully.<br/>';
	}	
}


function regionMapping()
{
	static $row = null;
	static $auNz = null;
	static $eu = null;
	static $ca = null;
	static $jp = null;
	static $us = null;
	static $uk = null;
	
	if($row == null)
	$row = file('institutions/row.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($auNz == null)
	$auNz = file('institutions/au_nz.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($eu == null)
	$eu = file('institutions/eu.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($ca == null)
	$ca = file('institutions/ca.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($jp == null)
	$jp = file('institutions/jp.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);	
	
	if($us == null)
	$us = file('institutions/us.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);	
	
	if($uk == null)	
	$uk = file('institutions/uk.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);	
	
	$out = array();
	
	foreach($row as $tmp)
	{
		$out[$tmp] = 'ROW';
	}
	
	foreach($auNz as $tmp)
	{
		$out[$tmp] = 'AU/NZ';
	}
	
	foreach($eu as $tmp)
	{
		$out[$tmp] = 'EU';
	}

	foreach($ca as $tmp)
	{
		$out[$tmp] = 'CA';
	}


	foreach($jp as $tmp)
	{
		$out[$tmp] = 'JP';
	}

	foreach($us as $tmp)
	{
		$out[$tmp] = 'US';
	}

	foreach($uk as $tmp)
	{
		$out[$tmp] = 'UK';
	}		
	
	return $out;
	
}

