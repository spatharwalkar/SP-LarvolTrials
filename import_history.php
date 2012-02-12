<?php
require_once('db.php');
require_once('include.util.php');
require_once('import_history.php');
ini_set('max_execution_time', '36000'); //10 hours
global $logger;
global $db;

ignore_user_abort(true);
//store all fieldnames of data_trials in array
$dt_fields=array
		(
		'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'condition', 'secondary_id', 'oversight_authority', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_status', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
		);

		
//get all larvol_ids into an array 		
$query = 'SELECT `larvol_id`,`source_id` from data_trials' ;
if(!$resu = mysql_query($query))
{
	$log='Bad SQL query getting  larvol_id from data_trials .<br>Query=' . $query . 'Error:' . mysql_error();
	$logger->fatal($log);
	echo $log;
	exit;
}
$larvol_ids=array();	
while ($row = mysql_fetch_assoc($resu))
{
	$larvol_ids[$row['larvol_id']]=$row['source_id'];
}
//exit;

//define arrays for phase values for later use
$long_phases=array
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
	
$short_phases=array
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


$counter=0;
$cntr=0;
foreach ($larvol_ids as $larvol_id => $nct_id)
{
	$studycat=getstudycat(unpadnct($nct_id));
	
	foreach ($dt_fields as $field) 
	{
		//check if this field already has any history in the new schema	
		$query = 'SELECT `' . $field .'_prev`,`' . $field .'_lastchanged`,  `larvol_id` from data_history where `larvol_id`= "' .$larvol_id. '"  limit 1' ;
		if(!$resu = mysql_query($query))
		{
			$log='Bad SQL query getting data from data_history .<br>Query=' . $query . 'Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			exit;
		}
		$history = mysql_fetch_assoc($resu);
		$val=$history[$field .'_prev'];
		$dt=$history[$field .'_lastchanged'];
		if(	count($history)==3 ) $exists_in_history=true; else $exists_in_history=false;
		
		
		if(	$exists_in_history and !is_null($val) and !is_null($dt) and ($val==0 or !empty($val)) and !empty($dt) and $val<>'0000-00-00' )
		{
			//history already exists do nothing.
			continue;
		}
		else
		{
			//no history found, import from old schema.
			
			$studycat_exists=( isset($studycat) and !is_null($studycat) and !empty($studycat) );
			
			if(!$studycat_exists)
			{
				continue;
			}
			else
			{
				$query = 'SELECT id,type FROM data_fields WHERE name="' . $field . '" AND category="1" LIMIT 1';
				if(!$resu = mysql_query($query))
				{
					$log='Bad SQL query getting data from data_fields .<br>Query=' . $query . 'Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					exit;
				}
				$field_info = mysql_fetch_assoc($resu);
				
				$query = 'SELECT `val_' . $field_info['type'] . '`,`superceded`,`studycat` from data_values 
						  where `studycat`= "' .$studycat. '" and superceded is not null and 
						  `field`="' .$field_info['id'] . '" order by superceded desc, id desc limit 1' ;
				
				if(!$resu = mysql_query($query))
				{
					$log='Bad SQL query getting data from data_values .<br>Query=' . $query . 'Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					exit;
				}
				$imported_history = mysql_fetch_assoc($resu);
				$imp_val=$imported_history['val_' . $field_info['type']];
				$imp_dt =$imported_history['superceded'];
				
				// check if any valid data was imported
				if(	$imported_history and !is_null($imp_val) and !is_null($imp_dt) and ( $field_info['type']=='int' or !empty($imp_val) ) and !empty($imp_dt) )
				{
				
				//escape data before adding to database
					$imp_val=mysql_real_escape_string($imp_val);
					$imp_dt=mysql_real_escape_string($imp_dt);
					
					//get value of enums if required
					if($field_info['type']=='enum')
					{
						$query = 'SELECT `value` from data_enumvals where id="' . $imp_val .'" limit 1';
					
						if(!$resu = mysql_query($query))
						{
							$log='Bad SQL query getting data from data_enumvals .<br>Query=' . $query . 'Error:' . mysql_error();
							$logger->fatal($log);
							echo $log;
							exit;
						}
						$enm = mysql_fetch_assoc($resu);
						
						$imp_val=$enm['value'];
						
						//make phase value short
						if($field=='phase')
						{
							$v=array_search($imp_val,$long_phases,false);
							if($v!==false)
							{
								$imp_val=$short_phases[$v];
							}
							else
							{
								continue;
							}
						}
					}
				
					
					if($exists_in_history)  // record already exists in the history table, so just update it
					{
//							pr(date("Y-m-d H:i:s", strtotime('now')).' - Updating '.$field.' of Larvol Id '.$larvol_id.', NCT ID:'. $nct_id);
						$query = 'update data_history set `'. $field .'_prev' . '` = "'. $imp_val . '", 
								 `' . $field .'_lastchanged' .'` = "' . $imp_dt . '"'
								 . ' where `larvol_id` = "' . $larvol_id .'" limit 1' ;
								 
						if(!$resu = mysql_query($query))
						{
							$log='Bad SQL query updating data in data_values .<br>Query=' . $query . 'Error:' . mysql_error();
							$logger->fatal($log);
							echo $log;
							exit;
						}
					}
					else // record does not exist in history table, so insert a new row.
					{
//						pr(date("Y-m-d H:i:s", strtotime('now')).' - Updating '.$field.' of Larvol Id '.$larvol_id.', NCT ID:'. $nct_id);
						$query = 'INSERT INTO data_history 
								  set `larvol_id` = "' . $larvol_id . '", `'. $field .'_prev' . '` = "'. $imp_val . '",  
								  `' . $field .'_lastchanged' .'` = "' . $imp_dt .'"';
								 
				
						if(!$resu = mysql_query($query))
						{
							$log='Bad SQL query inserting data into data_values .<br>Query=' . $query . 'Error:' . mysql_error();
							$logger->fatal($log);
							echo $log;
							exit;
						}
					}
				}
				
				else
				{
				continue;
				}

			}
		//exit;
	
	
		}
	}		
	//pr('<b>'.++$counter.'. '.date("Y-m-d H:i:s", strtotime('now')).' - Finished Updating Larvol Id '.$larvol_id.', NCT ID:'. $nct_id.'.</b>');

		echo ('<br>'.++$counter.'. '.date("Y-m-d H:i:s", strtotime('now')).' - Finished Updating Larvol Id '.$larvol_id.', NCT ID:'. $nct_id.'<br>');

	
}

function getstudycat($nctid)
{
global $logger;
if(!isset($nctid) or empty($nctid)) return false;
$query = 'SELECT studycat from data_values where val_int = "' . $nctid . '" and field = "1" limit 1 ';
	if(!$resu = mysql_query($query))
	{
		$log='Bad SQL query getting  studycat .<br>Query=' . $query;
		$logger->fatal($log);
		echo $log;
		exit;
	}
	$resu=mysql_fetch_array($resu);
	return $resu['studycat'];
}

?>