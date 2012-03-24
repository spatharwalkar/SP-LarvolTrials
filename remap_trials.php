<?
error_reporting(E_ERROR);
require_once('db.php');
require_once('include.util.php');
require_once ('include.derived.php');
require_once('preindex_trial.php');
//require_once ('include.import.php');
ini_set('max_execution_time', '9000000'); //250 hours
ignore_user_abort(true);
global $db;
global $logger;

if(isset($_GET['trial'])) // A single trial
{
	$trial=padnct($_GET['trial']);
	$query = 'SELECT `larvol_id` FROM data_trials where `source_id`="' . $trial . '"  LIMIT 1';

	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_assoc($res);
	$exists = $res !== false;
	$oldtrial=$exists;
	$larvol_id = NULL;
	if($exists)
	{
		$larvol_id = $res['larvol_id'];
	}
	else 
	{
		echo 'Invalid trail';
		return false;
	}

}
elseif(isset($_GET['larvol_id'])) // A single larvol_id
{
	$trial=$_GET['larvol_id'];
	$query = 'SELECT `larvol_id` FROM data_trials where `larvol_id`="' . $trial . '"  LIMIT 1';

	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_assoc($res);
	$exists = $res !== false;
	$oldtrial=$exists;
	$larvol_id = NULL;
	if($exists)
	{
		$larvol_id = $res['larvol_id'];
	}
	else 
	{
		echo 'Invalid larvol_id';
		return false;
	}
	
}
elseif(isset($_GET['source']) and $_GET['source']=="ALL")  // Entire database
{

$query = 'SELECT `larvol_id` FROM data_trials';

	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	
	$exists = $res !== false;
	$oldtrial=$exists;
	$larvol_ids = array();
	while ($row = mysql_fetch_assoc($res)) $larvol_ids[] = $row[larvol_id];
	asort($larvol_ids);
}

elseif(isset($_GET['source']) and !empty($_GET['source']))  // single data source (eg. data_nct, data_eudract etc.)
{
$source='data_'.$_GET['source'];
$query = 'SELECT `larvol_id` FROM '. $source .' ';

	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	
	$exists = $res !== false;
	$oldtrial=$exists;
	$larvol_ids = array();
	while ($row = mysql_fetch_assoc($res)) $larvol_ids[] = $row[larvol_id];
	asort($larvol_ids);
}
else return false;

	/* status */

		$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="REMAP" order by update_id desc limit 1' ;
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$res = mysql_fetch_array($res) ;
		
	if ( isset($res['process_id']) )
	{
		
		$pid = getmypid();
		$up_id= ((int)$res['update_id']);
		$cid = ((int)$res['current_nctid']); 
		$maxid = ((int)$res['max_nctid']); 
		$updateitems= ((int)$res['update_items_progress']);
		$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message=""  WHERE process_id = "' . $pr_id .'" ;' ;
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
	//	fetch_records($pid,$cid,$maxid,$up_id);
	//	exit;
	}
	else
	{

		$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$res = mysql_fetch_array($res) ;
		$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
		$fid = getFieldId('NCT','nct_id');
		
		$cid = 0; 
		$cid_=$cid;
		$pid = getmypid();
		$totalncts=count($larvol_ids);
		
		
		$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid,trial_type) 
				  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
				  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'", "REMAP"  ) ;';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		
		
		
//		echo('Remapping from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
		echo('<br>Current time ' . date('Y-m-d H:i:s', strtotime('now')) . '<br>');
		echo str_repeat ("  ", 4000);
		$i=1;
		
	}

	/* STATUS */


if(!isset($cid)) $cid = 0; 	


if(!isset($larvol_ids)) $larvol_ids=array($larvol_id);

$DTnow = date("Y-m-d H:i:s", strtotime('now'));

$array1=array
		(
		'N/A',
		'Phase 0',
		'Phase 0/Phase 1',
		'Phase 1',
		'Phase 1/Phase 2',
		'Phase 2',
		'Phase 2/Phase 3',
		'Phase 3',
		'Phase 3/Phase 4',
		'Phase 4',
		'Phase 1a',
		'Phase 1a/1b',
		'Phase 1b',
		'Phase 1b/2',
		'Phase 1b/2a',
		'Phase 1c',
		'Phase 2a',
		'Phase 2a/2b',
		'Phase 2a/b',
		'Phase 2b',
		'Phase 2b/3',
		'Phase 3a',
		'Phase 3b',
		'Phase 3b/4'
		);
		
		$array2=array
		(
		'N/A',
		'0',
		'0/1',
		'1',
		'1/2',
		'2',
		'2/3',
		'3',
		'3/4',
		'4',
		'1a',
		'1a/1b',
		'1b',
		'1b/2',
		'1b/2a',
		'1c',
		'2a',
		'2a/2b',
		'2a/b',
		'2b',
		'2b/3',
		'3a',
		'3b',
		'3b/4'
		);

/**/
$counter=0;

foreach($larvol_ids as $larvol_id)
{


	if($cid > $larvol_id) continue; 
	
	$counter++;
//	if($counter>250) break;
	$query = 'SELECT * FROM data_nct where `larvol_id`="' . $larvol_id . '"  LIMIT 1';

		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$res = mysql_fetch_assoc($res);
		$exists = $res !== false;
		$larvol_id = $res['larvol_id'];
		$nctid=padnct($res['nct_id']);
		$record_data = $res;
		
	foreach($record_data as $fieldname => $value)
	{
		if($fieldname=='completion_date') 
		{

			$c_date = normal('date',(string)$value);
		}
		if($fieldname=='primary_completion_date') 
		{

			$pc_date = normal('date',(string)$value);
		}
		if($fieldname=="phase") 
		{

			$phase_value=null;
			$v=array_search($value,$array1,false);
			if($v!==false)
			{
				$phase_value=$array2[$v];
			}
		}
		
	}

	if(isset($c_date) and !is_null($c_date)) $end_date=$c_date;
	else $end_date=$pc_date;
	

	$i=0;
	foreach($record_data as $fieldname => $value)
	{
		if(!remap($larvol_id, $fieldname, $value,$record_data['lastchanged_date'],$oldtrial,NULL,$end_date,$phase_value))
		logDataErr('<br>Could not save the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog
		$i++;
		
	}
			
			
	//calculate institution type
	$ins_type=getInstitutionType($record_data['collaborator'],$record_data['lead_sponsor'],$larvol_id);

	//calculate region
	$region=getRegions($record_data['location_country']);
	if($region=='other') $region='RoW';
	//calculate active or inactive
	$inactiveStatus = 
		array(
			'test string',
			'Withheld',
			'Approved for marketing',
			'Temporarily not available',
			'No Longer Available',
			'Withdrawn',
			'Terminated',
			'Suspended',
			'Completed'	
			);
	
	$inactive=1;
	if(isset($record_data['overall_status']))
	{
		$x=array_search($record_data['overall_status'],$inactiveStatus);
		if($x) $inactive=0; else $inactive=1;
	}

	$query = 'update data_trials set `institution_type`="' .$ins_type. '",`region`="'.$region.'", `is_active`='.$inactive.'  where `larvol_id`="' .$larvol_id . '" limit 1' ;	

	if(!mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Remapping of trial : ' . $nctid . ' completed.' .   str_repeat("     ",300) );
		
		tindex($nctid,'products');
		tindex($nctid,'areas');
		echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Preindexing of trial : ' . $nctid . ' completed.' .   str_repeat("     ",300) );
		if(!isset($updateitems)) $updateitems=0;
		$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pid  .'" , update_items_progress= "' . ( ($totalncts >= $updateitems+$counter) ? ($updateitems+$counter) : $totalncts  ) . '" , status="2", current_nctid="'. $larvol_id .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="REMAP"  ;' ;
		
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
//	return true;
}
function remap($larvol_id, $fieldname, $value,$lastchanged_date,$oldtrial,$ins_type,$end_date,$phase_value)
{

	$lastchanged_date = normal('date',$lastchanged_date);
	global $logger,$nctid;
	$DTnow = date("Y-m-d H:i:s", strtotime('now'));

	//normalize the input
	
	if(!is_array($value)) 
	{
		
		$tt=strpos('a'.$fieldname, "_date")  ;
		if(isset($tt) and !empty($tt))
		{
			$value = normal('date',$value);
		}
		elseif(is_numeric($value)) $value = normal('int',(int)$value); 
		else   $value = preg_replace( '/\s+/', ' ', trim( $value ) );
	}

	elseif(is_numeric($value[0])) $value=max($value); 
	elseif($fieldname=="phase") $value=max($value);
	else
	{
		$value=array_unique($value);
	
		$newval="";
		$cnt=count($value);
		$c1=1;
		foreach($value as $key => $v) 
		{
			$tt=strpos('a'.$fieldname, "_date")  ;
			if(isset($tt) and !empty($tt))
			{
				$newval = normal('date',(string)$v);
				
			}
			elseif(is_numeric($v)) 
			{
			$newval = normal('int',(int)$v);
			}
		
			else 
			{
				$v = normal('varchar',(string)$v);
				if($c1<$cnt) $newval .= $v."`";
				else $newval .= $v;
			}
			$c1++;
				
		}
		$value=$newval;
	}
	

	$value=mysql_real_escape_string($value);
	
	$dn_array=array
			(
				'dummy', 'larvol_id', 'nct_id', 'download_date', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'lead_sponsor_class', 'collaborator', 'collaborator_class', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'completion_date', 'completion_date_type', 'primary_completion_date', 'primary_completion_date_type', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'contact_name', 'contact_phone', 'contact_phone_ext', 'contact_email', 'backup_name', 'backup_phone', 'backup_phone_ext', 'backup_email', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'nct_alias', 'condition', 'secondary_id', 'oversight_authority', 'rank', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'link_url', 'link_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'reference_citation', 'reference_PMID', 'results_reference_citation', 'results_reference_PMID', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_status', 'location_contact_name', 'location_contact_phone', 'location_contact_phone_ext', 'location_contact_email', 'location_backup_name', 'location_backup_phone', 'location_backup_phone_ext', 'location_backup_email', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
			);
		$dt_array=array
			(
				'dummy', 'larvol_id', 'source_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type',  'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
			);
		$dm_array=array
			(
				'dummy', 'larvol_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
			);
	
	$as=array_search($fieldname,$dn_array);
	
	if ( isset($as) and $as)
	{
						
			$as1=array_search($fieldname,$dt_array);
			
			if ( isset($as1) and $as1)
			{
				if($fieldname=='end_date')
				{
					$value=$end_date;
				}
				$value=mysql_real_escape_string($value);
	
				//check if the data is manually overridden
			
				$as2=array_search($fieldname,$dm_array);
				if ( isset($as2) and $as2)
				{
					
					$query = 'SELECT `' .$fieldname. '` FROM data_manual WHERE `larvol_id`="'. $larvol_id . '" and `' .$fieldname. '` is not null limit 1';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
//						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}

					$row = mysql_fetch_assoc($res);
					$overridden = $row !== false;
					if($overridden and !empty($row[$fieldname]))
					{
						$value=mysql_real_escape_string($row[$fieldname]);
						
					
					}
				}
								
					$query = 'update data_trials set `' . $fieldname . '` = "' . $value .'", lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
//						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
								
			}
		
	
	
		
		$query = 'select `completion_date`,`primary_completion_date`,`criteria` from `data_nct` where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			
		$res = mysql_fetch_assoc($res);
		$exists = $res !== false;
				
		$cdate=$res['completion_date'];
		$pcdate=$res['primary_completion_date'];
		$str=$res['criteria'];
		$str=criteria_process($str);
		$str['inclusion']=mysql_real_escape_string($str['inclusion']);
		$str['exclusion']=mysql_real_escape_string($str['exclusion']);
		
		/*********/
		if( !is_null($cdate) and  $cdate <>'0000-00-00' )	// completion date
		{
			$cdate=normalize('date',$cdate);
			$query = 'update `data_trials` set `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
			
			if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					
		}
		
		elseif( !is_null($pcdate) and  $pcdate <>'0000-00-00') 	// primary completion date
		{
		
			$pcdate=normalize('date',$pcdate);
			$query = 'update `data_trials` set  `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//			$query = 'update data_trials set end_date = "' . $pcdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
			if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					
		}

		
		else	
		{
				
			$query = 'select `is_active`, `lastchanged_date` from `data_trials` where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
			if(!$res=mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}

			
			$res = mysql_fetch_assoc($res);
			$cdate=$res['lastchanged_date'];
			$is_active=$res['is_active'];
			global $ins_type;
			
			if( !is_null($cdate) and  $cdate <>'0000-00-00' and !is_null($is_active) and $is_active<>1 ) // last changed date
			{
				
				$cdate=normalize('date',$cdate);
				$query = 'update `data_trials` set `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = "' . $cdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
				if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
				
			}

			else	// replace with null
			{
						
				$query = 'update `data_trials` set `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = null where larvol_id="' .$larvol_id . '"  limit 1' ;
				if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					
			}
		}
		
		/*************/
				
		if( !is_null($cdate) and  $cdate <>'0000-00-00' and $fieldname=='end_date')	// completion date
		{
		
		
			$cdate=normalize('date',$cdate);
			$query = 'update `data_trials` set `end_date` = "' . $cdate . '", `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
			
//			$query = 'update data_trials set end_date = "' . $cdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
			if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					
		}
		
		elseif( !is_null($pcdate) and  $pcdate <>'0000-00-00' and $fieldname=='end_date') 	// primary completion date
		{
		
			$pcdate=normalize('date',$pcdate);
			$query = 'update `data_trials` set `end_date` = "' . $pcdate . '", `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//			$query = 'update data_trials set end_date = "' . $pcdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
			if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
			
		}
		
		
		else	
		{
		
			$query = 'select `is_active`, `lastchanged_date` from `data_trials` where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
			if(!$res=mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}

			
			$res = mysql_fetch_assoc($res);
			$cdate=$res['lastchanged_date'];
			$is_active=$res['is_active'];
			
			global $ins_type;
			if( !is_null($cdate) and  $cdate <>'0000-00-00' and !is_null($is_active) and $is_active<>1 and $fieldname=='end_date') // last changed date
			{
				$cdate=normalize('date',$cdate);
				$query = 'update `data_trials` set `end_date` = "' . $cdate . '", `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = "' . $cdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
				if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					
			}
			
			elseif($fieldname=='end_date')	// replace with null
			{
			
				$query = 'update `data_trials` set `end_date` = null, `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = null where larvol_id="' .$larvol_id . '"  limit 1' ;
				if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					
			}
				
			
		}
	return true;
	}

}

function normalize($type, $value)
{
	global $nctid;
    $value = preg_replace( '/\s+/', ' ', trim( $value ) );         
    if ($value == " " || $value == "") return NULL;
	if(!strlen($value) || $value === NULL) return NULL;
	switch($type)
	{
		case 'varchar':
		case 'text':
		case 'enum':
		return $value;
		
		case 'date':
		return date('Y-m-d', strtotime($value));
		
		case 'int':
		case 'bool':
            if ($value == "Yes") {
                return 1;
            } else {
                return (int) $value;
            }
	}
}
function normal($type, $value)
{
	global $nctid;
    $value = preg_replace( '/\s+/', ' ', trim( $value ) );         
    if ($value == " " || $value == "") return NULL;
	if(!strlen($value) || $value === NULL) return NULL;
	switch($type)
	{
		case 'varchar':
		case 'text':
		case 'enum':
		return $value;
		
		case 'date':
		return date('Y-m-d', strtotime($value));
		
		case 'int':
		case 'bool':
            if ($value == "Yes") {
                return 1;
            } else {
                return (int) $value;
            }
	}
}
?>
