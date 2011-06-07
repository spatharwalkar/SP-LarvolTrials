<?php
require_once('db.php');
require_once('include.search.php');


/**
 * @name refreshLarvolIds
 * @tutorial start inactive date functions.
 * Calling this function all the available larvol_id's
 *  are retrieved and Inactive dates are updated.
 * @author Jithu Thomas
 */
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

/**
 * @name calculateDateFieldIds
 * @tutorial Calculate the field id's of fields required
 * for calculating inactive dates
 * @author Jithu Thomas
 */
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
 * @name refreshInactiveDates
 * @tutorial Search function used to get the overall_status,completion_date and primary_completion_date values.
 * If larvolId is present function searches for the specific larvolId and updates inactiveDate.
 * If no larvolId is present all available larvolId's are listed and updates inactiveDate
 * @param int $larvolId 
 * @param $action It is either empty string or search. Search is used for individual larvolIds
 * @author Jithu Thomas
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
/**
 * @name refreshRegionLarvolIds
 * @tutorial Calling this function all the available larvol_id's
 * are retrieved and Inactive dates are updated.
 * @author Jithu Thomas
 */
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

/**
 * @name calculateRegionFieldIds
 * @tutorial Calculate the field id's of fields required
 * for calculating regions
 * @author Jithu Thomas
 */
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
			$countryArr = array();
			foreach($locationCountry as $country)
			{
				$countryArr[] = $country;
			}
			$countryArr = array_unique($countryArr);
			$locationCountry = $countryArr;
		}
		$codeArr = array();
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
						$codeArr[] = $code;
						
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
		$code = implode(', ',array_unique($codeArr));
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

/**
 * @name regionMapping
 * @tutorial Returns an array of all regions mapped with with countries and corresponding 
 * larvol region field defenitions. Reads all .txt files from the directory derived/region.
 * File name convention for retreiving $regionEntry which is stored in db is eg: us.txt will have db entry US and au_nz will have entry AU/NZ.
 * @author Jithu Thomas
 */
function regionMapping()
{
	$out = array();
	if ($handle = opendir('derived/region'))
	{
	    while (false !== ($file = readdir($handle)))
	    {
	        if (substr($file,-4)=='.txt')
	        {
	            $regionEntry = strtoupper(str_replace('_','/',substr($file,0,strpos($file,'.txt'))));
	            $regionFile = file('derived/region/'.$file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	        	foreach($regionFile as $countryList)
				{
					$out[$countryList] = $regionEntry;
				}	            
	        }
	    }
	    closedir($handle);
	}
	else
	{
		die('Cannot open directory derived/region.');
	}
	
	return $out;
	
}

