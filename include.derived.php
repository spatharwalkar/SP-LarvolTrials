<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');


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
	}
	$flag = 0;
	foreach($arr as $res)
	{
		$overallStatus = $res['NCT/overall_status'];
		if(is_array($res['NCT/completion_date']))
		{
			$cn=count($res['NCT/completion_date']);
			$completionDate = $res['NCT/completion_date'][$cn-1];
		}
		else $completionDate = $res['NCT/completion_date'];
		if(is_array($res['NCT/primary_completion_date']))
		{
			$cn=count($res['NCT/primary_completion_date']);
			$primaryCompletionDate = $res['NCT/primary_completion_date'][$cn-1];
		}
		else $primaryCompletionDate = $res['NCT/primary_completion_date'];
		$larvolId = $res['larvol_id'];

		$studyCatId = getStudyCat($larvolId);
		$activeStatus = array(
										'Not yet recruiting',
										'Recruiting',
										'Enrolling by invitation',
										'Active, not recruiting',
										'Available'
		);
		
		if($completionDate)
		{
			$addedDate = getAddedDate('completion_date', $studyCatId);
			$inactiveDate = $completionDate;
		}
		elseif($primaryCompletionDate) 
		{
			$addedDate = getAddedDate('primary_completion_date', $studyCatId);
			$inactiveDate = $primaryCompletionDate;
		}
		elseif($overallStatus)
		{
			$addedDate = getAddedDate('overall_status', $studyCatId);
			$inactiveDate = $addedDate;
		}
		
		//fetch and calculate inactive_date_lastchanged and inactive_date_prev
		//get cache ready
		$inactiveDateCache = calculateInactiveDatesFromCacheData($larvolId,$inactiveDate,$addedDate);
		$inactiveDateCache = array_map(function($k,$v){
			return "$k=$v";
		},array_keys($inactiveDateCache),array_values($inactiveDateCache));
		$query  = "update clinical_study set ".implode(',',$inactiveDateCache)." where larvol_id=$larvolId";
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
		echo 'Inactive Date updated for Larvol Id: '.$larvolId.'.<br/>';
	}
	
	
}

function calculateInactiveDatesFromCacheData($larvolId,$inactiveDate,$addedDate)
{
	global $db;
	$query = "select last_change,inactive_date,inactive_date_lastchanged,inactive_date_prev from clinical_study where larvol_id=$larvolId";
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res)){}
	$last_change = $row['last_change'];
	//works for commented code below$inactive_date_old = sqlExplicitNullifier($row['inactive_date'],'date');
	$inactive_date_old = $row['inactive_date'];
	
	//previous memory
	$inactiveDateLastchanged = $row['inactive_date_lastchanged'];
	$inactiveDatePrev = $row['inactive_date_prev'];
	if($inactiveDate != $inactive_date_old)
	{
		
	}
	
	if($inactive_date_old=='')
	{
		$inactive_date_lastchanged = $addedDate;
		$inactive_date_prev = '';
	}
	elseif($inactive_date_old == $inactiveDate)
	{
		$inactive_date_lastchanged = $inactiveDateLastchanged;
		$inactive_date_prev = $inactiveDatePrev;
	}
	else
	{
		$inactive_date_lastchanged = $last_change;
		$inactive_date_prev = $inactive_date_old;
	}
	$inactive_date_lastchanged = sqlExplicitNullifier($inactive_date_lastchanged,'date');
	$inactive_date_prev = sqlExplicitNullifier($inactive_date_prev,'date');
	
	//convert/check for explicit null case
	$inactiveDate = sqlExplicitNullifier($inactiveDate,'date');
	return array('inactive_date'=>$inactiveDate,'inactive_date_lastchanged'=>$inactive_date_lastchanged,'inactive_date_prev'=>$inactive_date_prev);
	
}
//end inactive date functions


function getStudyCat($larvolId)
{
	global $db;
	$query = "select id from data_cats_in_study where larvol_id=$larvolId";
	$res = mysql_query($query);
	$studyCatId = mysql_fetch_row($res);
	$studyCatId = $studyCatId[0];
	return $studyCatId;
}

function getFieldData($fieldName)
{
	global $db;
	$query = "select * from data_fields where `name` = '$fieldName'";
	$res = mysql_query($query);
	$fieldData = mysql_fetch_row($res);
	$fieldData = $fieldData[0];
	return $fieldData;	
}

function getAddedDate($fieldName,$studyCatId)
{
	global $db;
/* 	$inactiveStatus = array(
											'Withheld',
											'Approved for marketing',
											'Temporarily not available',
											'No Longer Available',
											'Withdrawn',
											'Terminated',
											'Suspended',
											'Completed'
	); 
	
	$query = "SELECT dv.added FROM data_values dv LEFT JOIN data_fields df ON df.id=dv.field
						LEFT JOIN data_enumvals de ON de.field=df.id WHERE dv.studycat=$studyCatId 
						AND df.name='$fieldName' AND de.value in ('".implode(',',$inactiveStatus)."') ORDER BY dv.added ASC";*/
	$fieldId = getFieldData($fieldName);
	$fieldId = $fieldId['id'];
	$query = "select * from data_values where studycat=$studyCatId  and field=$fieldId order by id desc limit 1";
				
	$res = mysql_query($query);
	while($row = mysql_fetch_assoc($res))
	{
		break;
	}
	$addedDate = date('Y-m-d',strtotime($row['added']));
	return $addedDate;
}

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
		$query  = "update clinical_study set region=null where larvol_id=$larvolId";	
		if(mysql_query($query))
		{
			mysql_query('COMMIT') or softdie('Cannot commit transaction');
			echo 'Region updated for Larvol Id: '.$larvolId.'.<br/>';	
		}
		else
		{
			softdie('Cannot update region. '.$query);
		}			
	}
	
	
}	
/**
 * @name applyRegions
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
		echo 'Region updated for Larvol Id: '.$larvolId.'.<br/>';	
	}	
}

function getRegions($locationCountry)
{
	global $db;
	
	$flag = 0;
	$flag1 = 0;
	$flag2 = 0;
	$regionArr = regionMapping();

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
		
		return $code;
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
	            $regionEntry = str_replace('_','/',substr($file,0,strpos($file,'.txt')));
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

//start institution_type functions

/* Gets the full institution mapping from disk.
	Search relies on the institution_type field in the database, not this.
*/
function institutionMapping()
{
	
	$out = array();
	if ($handle = opendir('derived/institution_type'))
	{
	    while (false !== ($file = readdir($handle)))
	    {
	        if (substr($file,-4)=='.txt')
	        {
	            $institutionEntry = str_replace('_','/',substr($file,0,strpos($file,'.txt')));
	            $institutionFile = file('derived/institution_type/'.$file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	        	foreach($institutionFile as $institutionList)
				{
					$out[$institutionList] = $institutionEntry;
				}	            
	        }
	    }
	    closedir($handle);
	}
	else
	{
		die('Cannot open directory derived/institution_type.');
	}
		
	return $out;
}

/**
 * @name refreshRegionLarvolIds
 * @tutorial Calling this function all the available larvol_id's
 * are retrieved and Institution Types are updated.
 * @author Jithu Thomas
 */
function refreshInstitutionTypeLarvolIds()
{
	global $db;
	
	//calculate field Ids and store in an array since it requires db call
	$fieldArr = calculateInstitutionTypeFieldIds();

	$query = "select larvol_id from clinical_study";
	$res = mysql_query($query);
	while($row = mysql_fetch_array($res))
	{
		$larvolId = $row['larvol_id'];
		refreshInstitutionType($larvolId, 'search',$fieldArr);
	}	
}


/**
 * @name calculateInstitutionTypeFieldIds
 * @tutorial Calculate the field id's of fields required
 * for calculating institution type
 * @author Jithu Thomas
 */
function calculateInstitutionTypeFieldIds()
{
	$fieldnames = array('collaborator','lead_sponsor');
	$fieldArr = array();
	foreach($fieldnames as $name)
	{
		$fieldArr[$name] = getFieldId('NCT',$name);
	}
	return $fieldArr;
}

/**
 * 
 * @name refreshInstitutionType
 * @tutorial Search function used to get the institution_type.
 * If larvolId is present function searches for the specific larvolId and updates regions.
 * If no larvolId is present all available larvolId's are listed and updates regions
 * @param int $larvolId 
 * @param var $action It is either empty string or search. Search is used for individual larvolIds
 * @param array $fieldArr field arrays are calculated seperately to avoid unnecessary db calls for repeated calls to this function.
 * @author Jithu Thomas
 * 
 */

function refreshInstitutionType($larvolId,$action,$fieldArr)
{
	$param = new SearchParam();
	$param->field = 'larvol_id';
	$param->action = $action;
	$param->value = $larvolId;
	$param->strong = 1;
	
	$prm = array($param);
	//pr($fieldArr);die;
	$fieldnames = array('collaborator','lead_sponsor');
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
	foreach($res as $res);
	if(isset($res['larvol_id']) && is_numeric($res['larvol_id']))
	applyInstitutionType($res);
}

/**
 * @name applyInstitutionType
 * @tutorial Function applies derived field institution_type for each search result array passed.
 * @param array $arr is an array of search result from the search() function.
 * @author Jithu Thomas
 */
function applyInstitutionType($arr)
{
	global $db;
	$institution_type = 'other';
	$lead_sponsors = array();
	$collaborators = array();
	$instMap = institutionMapping();
	$larvol_id = $arr['larvol_id'];
	
	//create the generic array for institution_type decision making.
	if(isset($arr['NCT/collaborator']))
	{
		if(is_array($arr['NCT/collaborator']))
		{
			foreach($arr['NCT/collaborator'] as $sponsor)
			{
				$collaborators[] = $sponsor;
			}
		}
		else
		{
			$collaborators[] = $arr['NCT/collaborator'];
		}
		
	}
	if(isset($arr['NCT/lead_sponsor']))
	{
		if(is_array($arr['NCT/lead_sponsor']))
		{
			foreach($arr['NCT/lead_sponsor'] as $sponsor)
			{
				$lead_sponsors[] = $sponsor;
			}
		}
		else
		{
			$lead_sponsors[] = $arr['NCT/lead_sponsor'];
		}
		
	}
	foreach($lead_sponsors as $a_sponsor)
	{
		if(strlen($a_sponsor) && isset($instMap[$a_sponsor]))
		{
			$institution_type = $instMap[$a_sponsor];
			if($institution_type == 'industry')
			{
				$institution_type = 'industry_lead_sponsor';
				break;
			} 
		}
	}
	if($institution_type != 'industry_lead_sponsor')
	{
		foreach($collaborators as $a_sponsor)
		{
			if(strlen($a_sponsor) && isset($instMap[$a_sponsor]))
			{
				$institution_type = $instMap[$a_sponsor];
				if($institution_type == 'industry')
				{
					$institution_type = 'industry_collaborator';
					break;
				} 
			}
		}		
	}
	echo 'Updating institution_type for larvol_id : '.$larvol_id.'<br/>';
	$query = 'UPDATE clinical_study SET institution_type="' . $institution_type . '" WHERE larvol_id=' . $larvol_id . ' LIMIT 1';
	if(mysql_query($query) === false) return softDie('Bad SQL query recording institution type<br/>'.$query);
	
}


function getInstitutionType($collaborator,$lead_sponsor,$larvol_id)
{
	global $db;
	$institution_type = 'other';
	$lead_sponsors = array();
	$collaborators = array();
	$instMap = institutionMapping();
//	pr($instMap);	
	//create the generic array for institution_type decision making.
	if(isset($collaborator))
	{
		if(is_array($collaborator))
		{
			foreach($collaborator as $sponsor)
			{
				$collaborators[] = $sponsor;
			}
		}
		else
		{
			$collaborators[] = $collaborator;
		}
		
	}
	if(isset($lead_sponsor))
	{
		if(is_array($lead_sponsor))
		{
			foreach($lead_sponsor as $sponsor)
			{
				$lead_sponsors[] = $lead_sponsor;
			}
		}
		else
		{
			$lead_sponsors[] = $lead_sponsor;
		}
		
	}
	foreach($lead_sponsors as $a_sponsor)
	{
		$a_sponsor=trim($a_sponsor);
		if( strlen($a_sponsor) && isset($instMap[$a_sponsor]) )
		{
			$institution_type = $instMap[$a_sponsor];
			if($institution_type == 'industry')
			{
				$institution_type = 'industry_lead_sponsor';
				break;
			} 
		}
	}
	if($institution_type != 'industry_lead_sponsor')
	{
		foreach($collaborators as $a_sponsor)
		{
			$a_sponsor=trim($a_sponsor);
			if(strlen($a_sponsor) && strlen($instMap[$a_sponsor]))
			{
				$institution_type = $instMap[$a_sponsor];
				if($institution_type == 'industry')
				{
					$institution_type = 'industry_collaborator';
					break;
				} 
			}
		}		
	}
	return $institution_type ;
	
}


//start Criteria functions
/**
 * @name refreshCriteriaLarvolIds
 * @tutorial Calling this function all the available larvol_id's
 * are retrieved and Inclusion Criteria, Exclusion Criteria, Not Specified are updated.
 * @author Sachin Fasale
 */
function refreshCriteriaLarvolIds()
{
	global $db;
	
	//calculate field Ids and store in an array since it requires db call
	$fieldArr = calculateCriteriaFieldIds();

	$query = "select larvol_id from clinical_study";
	$res = mysql_query($query);
	while($row = mysql_fetch_array($res))
	{
		$larvolId = $row['larvol_id'];
		echo 'Larvol Id :'.$larvolId.'<br/>';
		refreshCriteria($larvolId, 'search',$fieldArr);
	}
}

/**
 * @name calculateCriteriaFieldIds
 * @tutorial Calculate the field id's of fields required
 * for calculating Criteria
 * @author Sachin Fasale
 */
function calculateCriteriaFieldIds()
{
	$fieldnames = array('criteria');
	$fieldArr = array();
	foreach($fieldnames as $name)
	{
		$fieldArr[$name] = getFieldId('NCT',$name);
	}
	return $fieldArr;
}

/**
 * 
 * @name refreshCriteria
 * @tutorial Search function used to get the Criteria.
 * If larvolId is present function searches for the specific larvolId and updates Criteria.
 * If no larvolId is present all available larvolId's are listed and updates Criteria
 * @param int $larvolId 
 * @param var $action It is either empty string or search. Search is used for individual larvolIds
 * @param array $fieldArr field arrays are calculated seperately to avoid unnecessary db calls for repeated calls to this function.
 * @author Sachin Fasale
 * 
 */
function refreshCriteria($larvolId,$action,$fieldArr)
{
	$param = new SearchParam();
	$param->field = 'larvol_id';
	$param->action = $action;
	$param->value = $larvolId;
	$param->strong = 1;
	
	$prm = array($param);
	
	$fieldnames = array('criteria');
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
		applyCriteria($res);
	}
	else
	{
		mysql_query('BEGIN') or softdie('Cannot begin transaction');
		$query  = "update clinical_study set inclusion_criteria=null, exclusion_criteria=null where larvol_id=$larvolId";	
		if(mysql_query($query))
		{
			mysql_query('COMMIT') or softdie('Cannot commit transaction');
			echo ' Criteria updated for Larvol Id: '.$larvolId.'.<br/>';	
		}
		else
		{
			softdie('Cannot update Criteria. '.$query);
		}			
	}
	
	
}	
/**
 * @name apply Criteria
 * @tutorial Function applies derived field Inclusion Criteria, Exclusion Criteria, Not Specified Criteria for each search result array passed.
 * @param array $arr is an array of search result from the search() function.
 * @author Sachin Fasale
 */
function applyCriteria($arr)
{
	global $db;
	if(count($arr)>0)
	{
		mysql_query('BEGIN') or die('Cannot begin transaction');
	}
/*	else 
	{
		softdie('No records to update.<br/>');
	}*/	
	
	$flag = 0;
	$flag1 = 0;
	$flag2 = 0;
	
	foreach($arr as $res)
	{	
		$larvolId = $res['larvol_id'];
		$criteria = $res['NCT/criteria'];
		if(is_array($criteria))
		{
			$criteria=array_unique($criteria);
			$criteriaArr = '';
			foreach($criteria as $criteria1)
			{
				$criteriaArr.= $criteria1."\n";
			}
			//$criteriaArr = array_unique($criteriaArr);
			$criteria = $criteriaArr;
		}
		$criteriaArr = array();
		//var_dump($criteria);
		$total_data=criteria_process($criteria);
		
		if($total_data['inclusion'] != '' || $total_data['inclusion'] != NULL)
		$incl_data=mysql_real_escape_string($total_data['inclusion']);
		else
		$incl_data=null;
		
		if($total_data['exclusion'] != '' || $total_data['exclusion'] != NULL)
		$excl_data=mysql_real_escape_string($total_data['exclusion']);
		else
		$excl_data=null;
		
		if($total_data['ntspecified'] != '' || $total_data['ntspecified'] != NULL)
		$ntspec_data=mysql_real_escape_string($total_data['ntspecified']);
		else
		$ntspec_data=null;
		
		//print '<br><br><br><br>Inclusion Criteria'; print $incl_data; print '<br><br><br><br> '.$larvolId.'Exclusion Criteria';
		//print $excl_data;
		
		$query  = "update clinical_study set `inclusion_criteria`='".$incl_data."', `exclusion_criteria`='".$excl_data."' where `larvol_id`=$larvolId";
		if(mysql_query($query))
		{
			$flag=1;
		}
		else
		{
			softdie('Cannot update Criteria. '.$query);
		}		
	}
	if($flag == 1)
	{
		mysql_query('COMMIT') or softdie('Cannot commit transaction');
		echo 'Criteria updated for Larvol Id: '.$larvolId.'.<br/>';	
	}	
}

/**
 * @name All Functions for getting Inclusion Criteria, Exclusion Criteria, Not Specified Criteria from Criteria value
 * get_header- gives headers in particular line
 * check_exclusion - checks line is in Inclusion, Exclusion or Not Specified Criteria
 * process_criteria - Processess all things
 * @author Sachin Fasale
 */
function get_header($header_text)
{
	$header=array();
	$i=0;
	preg_match('/(.*:)(.*)/',$header_text, $hd);
	
	while($hd[1]) //Extract all Headers
	{
		if(preg_match('/(.*:)(.*)/',$header_text, $hd)) //Check if Header is Present
		{
			preg_match('/(.*:)(.*)/',$header_text, $hd);
			$header[$i]['val']=$hd[1];		//Assign value
			$header[$i]['incl']='New';		//Assign its New value for inclusion criteria
			$header[$i]['excl']='New';		//Assign its New value for exclusion criteria
			$header[$i]['ntspec']='New';	//Assign its New value for Not Specified criteria
			$header[$i]['spcl']='No';		//Assign is it a Special Header i.e which contains subheaders
			
			//$header_text = preg_replace('/('.$hd[1].')/','',$header_text); 
			$header_text=str_replace($hd[1],'',$header_text);	//Replace Encountered Header part with blank
			
			preg_match('/(.*:)(.*)/',$header_text, $hd); // Check if more header present
			//if($hd[1])
			//$header[$i]['spcl']='Yes';
			
			$i++;
		}
	//var_dump($header);		
	}
	//var_dump($header_text);
	$return[0]=$header;
	//var_dump($header);
	$return[1]=$header_text;
	return 	$return;	
}

function check_exclusion($line) //Check line is in Exclusion Criteria
{
	if(preg_match('/(.*)No (.*)/',$line, $out))	/// No inbetween
	{
		return 1;
	}
	elseif(preg_match('/^No (.*)/',$line, $out))	//Start with no
	{
		return 1;
	}
	elseif(preg_match('/(.*) no (.*)/',$line, $out))	///inbetween no
	{
		return 1;
	}
	elseif(preg_match('/^no (.*)/',$line, $out))	///start with no
	{
		return 1;
	}
	elseif(preg_match('/(.*)ineligible (.*)/',$line, $out) || preg_match('/(.*)Ineligible (.*)/',$line, $out))
	{
		return 1;
	}
	elseif(preg_match('/(.*)Not specified(.*)/',$line, $out) || preg_match('/(.*)Not Specified(.*)/',$line, $out) || preg_match('/(.*)not specified(.*)/',$line, $out))
	{
		return 2;
	}
	elseif(preg_match('/(.*)Not (.*)/',$line, $out))	////inbetween not
	{
		return 1;
	}
	elseif(preg_match('/^Not (.*)/',$line, $out))	///start with not
	{
		return 1;
	}
	elseif(preg_match('/(.*)not (.*)/',$line, $out))	///inbetween not
	{
		return 1;
	}
	elseif(preg_match('/^not (.*)/',$line, $out))	///start with not
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

function criteria_process($text)
{
	
	$text=str_replace("/","########",$text); //Replace / with ########(uncommon string) as it causes problems in some string functions
	
	$text=str_replace("Inclusion Criteria:","Inclusion Criteria",$text);
	$text=str_replace("Exclusion Criteria:","Exclusion Criteria",$text);
	$text=str_replace("Inclusion Criteria","Inclusion Criteria",$text);
	$text=str_replace("Exclusion Criteria","Exclusion Criteria",$text);
	
	$text=str_replace("inclusion criteria:","Inclusion Criteria",$text);
	$text=str_replace("exclusion criteria:","Exclusion Criteria",$text);
	$text=str_replace("inclusion criteria","Inclusion Criteria",$text);
	$text=str_replace("exclusion criteria","Exclusion Criteria",$text);
	
	$text=str_replace("Inclusion criteria:","Inclusion Criteria",$text);
	$text=str_replace("Exclusion criteria:","Exclusion Criteria",$text);
	$text=str_replace("Inclusion criteria","Inclusion Criteria",$text);
	$text=str_replace("Exclusion criteria","Exclusion Criteria",$text);
	
	$text=str_replace("INCLUSION CRITERIA:","Inclusion Criteria",$text);
	$text=str_replace("EXCLUSION CRITERIA:","Exclusion Criteria",$text);
	$text=str_replace("INCLUSION CRITERIA","Inclusion Criteria",$text);
	$text=str_replace("EXCLUSION CRITERIA","Exclusion Criteria",$text);
	
	$text=str_replace('Inclusion Criteria','Inclusion Criteria: \n',$text); //Replace all Inclusion Criteria with Inclusion Criteria: to make it like header
	$text=str_replace('Exclusion Criteria','Exclusion Criteria: \n',$text); //Replace all Exclusion Criteria with Exclusion Criteria: to make it like header

	//This adds \n before heading as in many cases where text is contneous and we are unable to detect end of line
	$text=preg_replace('/([A-Z]{1}[a-z]+){0,1}(\s){0,1}(-){0,1}[A-Z]{1,}[a-z\s\/]+[a-z]+:{1}/','\n $0 \n',$text);
	
	$text=preg_replace('/([A-Z]{1}[A-Z]+){0,1}(\s){0,1}[A-Z]{1,}[A-Z\s\/]+[A-Z]+:{1}/','\n $0 \n',$text); 
	
	$text=str_replace(' - ',' \n - ',$text);
	$text=preg_replace('/(--){1}([A-Z-a-z\s]+)(--){1}/','\n $2: \n',$text);
	
	//var_dump($text); 
	
	//add \n at sentence end
	$re = '/# Split sentences on whitespace between them.
    (?<=                # Begin positive lookbehind.
      [.!?]             # Either an end of sentence punct,
    | [.!?][\'"]        # or end of sentence punct and quote.
    )                   # End positive lookbehind.
    (?<!                # Begin negative lookbehind.
      Mr\.              # Skip either "Mr."
    | Mrs\.             # or "Mrs.",
    | Ms\.              # or "Ms.",
    | Jr\.              # or "Jr.",
    | Dr\.              # or "Dr.",
    | Prof\.            # or "Prof.",
    | Sr\.              # or "Sr.",
	| i\.e\.            # or "i.e.",
                        # or... (you get the idea).
    )                   # End negative lookbehind.
    \s+                 # Split on whitespace between sentences.
    /ix';

	$text=preg_replace($re,'$0 \n $1',$text); 
	
	//var_dump($text);
	//$text=str_replace(".","\n",$text); //Replace . with \n as it causes problems in some string functions
	$text=preg_replace("/\n/",'\n',$text);
	$data=explode('\n', $text);
	
	//var_dump($data);
	
	$incl_print_data='';
	$excl_print_data='';
	
	$incl_header=0;
	$excl_header=0;
	
	$diff_criteria_present_flag=0;
	$header=array();
	
	/* This part added for compressing data by removing redundant data but no idea till now */
	$compress='';
	for($m=0;$m< count($data); $m++)
	{
		if(trim($data[$m]) != '' && $data[$m] != NULL && trim($data[$m]) != " ") //check if data presents
		{
		$data[$m]=trim($data[$m]);
		$compress.=$data[$m].' \n';
		}
	}
	$compress=str_replace("/","########",$compress);
	/* End part- This part added for compressing data by removing redundant data but no idea till now */
	
	$data=explode('\n', $compress);
	
	for($m=0;$m< count($data); $m++)	//// for loop of data ---- first level
	{
		//print $data[$i].'<br>';
			
		if(trim($data[$m]) != '' && $data[$m] != NULL && trim($data[$m]) != " ") //check if data presents - First level
		{
			$line=trim($data[$m]);
			
			if(!$incl_header)
			{
				if(strpos($line,'Inclusion') || preg_match('/(.*)Inclusion(.*)/',$line, $out11) || preg_match('/(.*)INCLUSION(.*)/',$line, $out11) || preg_match('/(.*)inclusion(.*)/',$line, $out11))
				{
				$incl_header=1;
				$excl_header=0;
				}
			}
			
			if(!$excl_header)
			{
				if(strpos($line,'Exclusion') || preg_match('/(.*)Exclusion(.*)/',$line, $out11) || preg_match('/(.*)EXCLUSION(.*)/',$line, $out11) || preg_match('/(.*)exclusion(.*)/',$line, $out11))
				{
				$excl_header=1;
				$incl_header=0;
				}
			}
		
			preg_match('/(.*:)(.*)/',$line, $hd1);  //Check if Line contains Header is Present
			
			if($hd1[1]) //If header present Execute this part
			{
				if($prev=='header')
				$header[count($header)-1]['spcl']='Yes';
				$prev='header';
				$return=get_header($line);
				//var_dump($hd1[1]);
				$line=trim($return[1]);
				
				
				$new_header=$return[0];
				
				$j=count($header);
				for($i=count($new_header)-1;$i>=0; $i--)
				{
					$header[$j]['val']=$new_header[$i]['val'];    ///fill new headers in our headers list
					$header[$j]['incl']=$new_header[$i]['incl'];
					$header[$j]['excl']=$new_header[$i]['excl'];
					$header[$j]['ntspec']=$new_header[$i]['ntspec'];
					$header[$j]['spcl']=$new_header[$i]['spcl'];
					
					$j++;
				}
				
			}///header present if ends
			else
			{
				$prev='line';
			}
			//var_dump($header);
			
			///If line does not belongs to exclusion criteria section divide it at a no/not points so part without negation part will go to inclusion other to exclusion
			if($excl_header != 1)	
			{
				$line = preg_replace('/ not /','\n not ',$line);
				$line = preg_replace('/ Not /','\n Not ',$line);
				$line = preg_replace('/ no /','\n no ',$line);
				$line = preg_replace('/ No /','\n No ',$line);
			}
			$data2=explode('\n', $line);
			for($m2=0;$m2< count($data2); $m2++)	/// data for loop -- second level
			{
				//print $data[$i].'<br>';
				if(trim($data2[$m2]) != '' && $data2[$m2] != NULL && trim($data2[$m2]) != " ") //check if data presents -- second level
				{
					$line=trim($data2[$m2]);
					
					/////Part to add lines in various sections
					$checker=check_exclusion($line);
							
					if($excl_header == 1 && $checker !=2)
					$checker=1;
					
					if(!$checker) //Check line is in inclusion or exclsion criteria
					{	
						$i=0;
				
						if($line != '' && $line != NULL)
						{
							$prev='line';
							while($i < count($header))
							{
								if(($i<(count($header)-count($new_header))) && $header[$i]['spcl'] != 'Yes')
								$header[$i]['incl']='Old';
						
								if($header[$i]['incl'] == 'New')
								{
									if(!strpos($header[$i]['val'],'Inclusion') && !preg_match('/(.*)Inclusion(.*)/',$header[$i]['val'], $out22) && !preg_match('/(.*)INCLUSION(.*)/',$header[$i]['val'], $out22) && !preg_match('/(.*)EXCLUSION(.*)/',$header[$i]['val'], $out22) && !preg_match('/(.*)Exclusion(.*)/',$header[$i]['val'], $out22))
										$incl_print_data.="\n".trim($header[$i]['val'])."\n";
									$header[$i]['incl']='Old';			//make headers status old if we displayed it for inclusion
								}
								$i++;
							}
							
							$incl_print_data.=trim($line)."\n";
						}
					} 
					elseif($checker == 2) 
					{
						$i=0;
						if($line != '' && $line != NULL)
						{
							$prev='line';
							while($i < count($header))
							{
								if(($i<(count($header)-count($new_header))) && $header[$i]['spcl'] != 'Yes')
								$header[$i]['ntspec']='Old';
						
								if($header[$i]['ntspec'] == 'New')
								{
									$ntspec_print_data.="\n".trim($header[$i]['val'])."\n";
									$header[$i]['ntspec']='Old';			//make headers status old if we displayed it for exclusion
								}
								$i++;
							}
							$ntspec_print_data.=trim($line)."\n";
						}
					} 
					else 
					{
						$i=0;
						if($line != '' && $line != NULL)
						{
							$prev='line';
							while($i < count($header))
							{
								if(($i<(count($header)-count($new_header))) && $header[$i]['spcl'] != 'Yes')
								$header[$i]['excl']='Old';
					
								if($header[$i]['excl'] == 'New')
								{
									if(!strpos($header[$i]['val'],'Exclusion') && !preg_match('/(.*)Inclusion(.*)/',$header[$i]['val'], $out22) && !preg_match('/(.*)INCLUSION(.*)/',$header[$i]['val'], $out22) && !preg_match('/(.*)EXCLUSION(.*)/',$header[$i]['val'], $out22) && !preg_match('/(.*)Exclusion(.*)/',$header[$i]['val'], $out22))
										$excl_print_data.="\n".trim($header[$i]['val'])."\n";
									$header[$i]['excl']='Old';			//make headers status old if we displayed it for exclusion
								}
								$i++;
							}
							$diff_criteria_present_flag=1; //make flag one if we get any line belongs to exclusion criteria
								
							$excl_print_data.=trim($line)."\n";
						}
					}	////End of part to add in different sections
				}	////End of if for checking data presence - Second level
			}	///End of for loop of data ends - Second level
		}	//data present if ends - First level
	}	///for loop ends of data counter	- First level
	
	
	$incl_print_data=str_replace("########","/",$incl_print_data);
	$excl_print_data=str_replace("########","/",$excl_print_data);
	$ntspec_print_data=str_replace("########","/",$ntspec_print_data);
	
	$total_data=array();
	$total_data['inclusion']=$incl_print_data;
	$total_data['exclusion']=$excl_print_data;
	$total_data['ntspecified']=$ntspec_print_data;
  
	return $total_data;

	
} //end of process function

/***** All functions ends belonging to calculating Separated Criteria *****/