<?php
require_once('db.php');
require_once('include.util.php');
require_once('include.derived.php');
require_once('include.import.php');
global $db;
global $logger;
error_reporting(E_ERROR);
if($_GET['l']&&$_GET['f']&&$_GET['s'])
{
	$xx=get_field_value($_GET['l'],$_GET['f'],$_GET['s']);
}
function get_field_value($larvol_id,  $field_name, $source)
{
	if( empty($larvol_id) or empty($field_name)  )
		return false;
	global $logger;

		$query = 'SELECT * FROM data_manual where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$res = mysql_fetch_assoc($res);
		
		
		/****************/
		// data_manual
		$dm_mappings=array();
		$dm_mappings['acronym']=$res['acronym'];
		$dm_mappings['ages']=$res['ages'];
		$dm_mappings['arm_group_description']=$res['arm_group_description'];
		$dm_mappings['arm_group_label']=$res['arm_group_label'];
		$dm_mappings['arm_group_type']=$res['arm_group_type'];
		$dm_mappings['brief_summary']=$res['brief_summary'];
		$dm_mappings['brief_title']=$res['brief_title'];
		$dm_mappings['collaborator']=$res['collaborator'];
		$dm_mappings['condition']=$res['condition'];
		$dm_mappings['criteria']=$res['criteria'];
		$dm_mappings['detailed_description']=$res['detailed_description'];
		$dm_mappings['end_date']=$res['end_date'];
		$dm_mappings['enrollment']=$res['enrollment'];
		$dm_mappings['enrollment_type']=$res['enrollment_type'];
		$dm_mappings['firstreceived_date']=$res['firstreceived_date'];
		$dm_mappings['gender']=$res['gender'];
		$dm_mappings['has_dmc']=$res['has_dmc'];
		$dm_mappings['healthy_volunteers']=$res['healthy_volunteers'];
		$dm_mappings['institution_type']=$res['institution_type'];
		$dm_mappings['intervention_description']=$res['intervention_description'];
		$dm_mappings['intervention_name']=$res['intervention_name'];
		$dm_mappings['intervention_type']=$res['intervention_type'];
		$dm_mappings['is_active']=$res['is_active'];
		$dm_mappings['is_fda_regulated']=$res['is_fda_regulated'];
		$dm_mappings['is_section_801']=$res['is_section_801'];
		$dm_mappings['keyword']=$res['keyword'];
		$dm_mappings['larvol_id']=$res['larvol_id'];
		$dm_mappings['lastchanged_date']=$res['lastchanged_date'];
		$dm_mappings['lead_sponsor']=$res['lead_sponsor'];
		$dm_mappings['location_city']=$res['location_city'];
		$dm_mappings['location_country']=$res['location_country'];
		$dm_mappings['location_name']=$res['location_name'];
		$dm_mappings['location_state']=$res['location_state'];
		$dm_mappings['location_zip']=$res['location_zip'];
		$dm_mappings['maximum_age']=$res['maximum_age'];
		$dm_mappings['minimum_age']=$res['minimum_age'];
		$dm_mappings['number_of_arms']=$res['number_of_arms'];
		$dm_mappings['number_of_groups']=$res['number_of_groups'];
		$dm_mappings['official_title']=$res['official_title'];
		$dm_mappings['org_study_id']=$res['org_study_id'];
		$dm_mappings['overall_status']=$res['overall_status'];
		$dm_mappings['phase']=$res['phase'];
		$dm_mappings['primary_outcome_measure']=$res['primary_outcome_measure'];
		$dm_mappings['primary_outcome_safety_issue']=$res['primary_outcome_safety_issue'];
		$dm_mappings['primary_outcome_timeframe']=$res['primary_outcome_timeframe'];
		$dm_mappings['region']=$res['region'];
		$dm_mappings['sampling_method']=$res['sampling_method'];
		$dm_mappings['secondary_id']=$res['secondary_id'];
		$dm_mappings['secondary_outcome_measure']=$res['secondary_outcome_measure'];
		$dm_mappings['secondary_outcome_safety_issue']=$res['secondary_outcome_safety_issue'];
		$dm_mappings['secondary_outcome_timeframe']=$res['secondary_outcome_timeframe'];
		$dm_mappings['source']=$res['source'];
		$dm_mappings['source_id']=$res['source_id'];
		$dm_mappings['completion_date']=$res['completion_date'];
		$dm_mappings['primary_completion_date']=$res['primary_completion_date'];
		$dm_mappings['start_date']=$res['start_date'];
		$dm_mappings['study_design']=$res['study_design'];
		$dm_mappings['study_pop']=$res['study_pop'];
		$dm_mappings['study_type']=$res['study_type'];
		$dm_mappings['verification_date']=$res['verification_date'];
		$dm_mappings['why_stopped']=$res['why_stopped'];
		
		if(!empty($dm_mappings[$field_name]))
		{
			return $dm_mappings[$field_name];
		}
		
		//nothing in data_manual, so continue.
		if(!empty($source))
		{
			$query = 'SELECT * FROM data_'. strtolower($source) . ' where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
			if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
			$res = mysql_fetch_assoc($res);
			$exists = $res !== false;
			if(!$exists)
				return false;
		}
		$nct_exists=false;
		if(empty($source) or strtoupper($source)<>'NCT')
		{
			$query = 'SELECT larvol_id FROM data_nct where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
			if(!$res_nct = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					echo $log;
					return false;
				}
			$res_nct = mysql_fetch_assoc($res_nct);
			$nct_exists = $res_nct !== false;
			
			
			
		}
		//data_nct
		if( strtoupper($source)=='NCT'  or $nct_exists )
		{
		
		if($nct_exists)
			{
				$query = 'SELECT * FROM data_nct where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
				if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						echo $log;
						return false;
					}
				$res = mysql_fetch_assoc($res);
			}
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
	
		
			$dn_mappings=array();
			$dn_mappings['source_id']=padnct($res['nct_id']);
			$dn_mappings['acronym']=$res['acronym'];
			$dn_mappings['arm_group_description']=$res['arm_group_description'];
			$dn_mappings['arm_group_label']=$res['arm_group_label'];
			$dn_mappings['arm_group_type']=$res['arm_group_type'];
			$dn_mappings['brief_summary']=$res['brief_summary'];
			$dn_mappings['brief_title']=$res['brief_title'];
			$dn_mappings['collaborator']=$res['collaborator'];
			$dn_mappings['condition']=$res['condition'];
			$dn_mappings['criteria']=$res['criteria'];
			$dn_mappings['detailed_description']=$res['detailed_description'];
			$dn_mappings['end_date']=$res['end_date'];
			if(isset($res['primary_completion_date']) and !is_null($res['primary_completion_date'])) 
				$dn_mappings['end_date']=$res['primary_completion_date'];
			else 
				$dn_mappings['end_date']=$res['completion_date'];
			$dn_mappings['completion_date']=$res['completion_date'];
			$dn_mappings['primary_completion_date']=$res['primary_completion_date'];
			$dn_mappings['enrollment']=$res['enrollment'];
			$dn_mappings['enrollment_type']=$res['enrollment_type'];
			$dn_mappings['firstreceived_date']=$res['firstreceived_date'];
			$dn_mappings['gender']=$res['gender'];
			$dn_mappings['has_dmc']=$res['has_dmc'];
			$dn_mappings['healthy_volunteers']=$res['healthy_volunteers'];
			$dn_mappings['intervention_description']=$res['intervention_description'];
			$dn_mappings['intervention_name']=$res['intervention_name'];
			$dn_mappings['intervention_type']=$res['intervention_type'];
			$dn_mappings['region']=getRegions($res['location_country']);
				if($dn_mappings['region']=='other') $dn_mappings['region']='RoW';
			$dn_mappings['is_fda_regulated']=$res['is_fda_regulated'];
			$dn_mappings['is_section_801']=$res['is_section_801'];
			$dn_mappings['keyword']=$res['keyword'];
			$dn_mappings['larvol_id']=$res['larvol_id'];
			$dn_mappings['lastchanged_date']=$res['lastchanged_date'];
			$dn_mappings['lead_sponsor']=$res['lead_sponsor'];
			$dn_mappings['location_city']=$res['location_city'];
			$dn_mappings['location_country']=$res['location_country'];
			$dn_mappings['location_name']=$res['location_name'];
			$dn_mappings['location_state']=$res['location_state'];
			$dn_mappings['location_zip']=$res['location_zip'];
			$dn_mappings['maximum_age']=$res['maximum_age'];
			$dn_mappings['minimum_age']=$res['minimum_age'];
			$dn_mappings['number_of_arms']=$res['number_of_arms'];
			$dn_mappings['number_of_groups']=$res['number_of_groups'];
			$dn_mappings['official_title']=$res['official_title'];
			$dn_mappings['org_study_id']=$res['org_study_id'];
			$dn_mappings['overall_status']=$res['overall_status'];
			
			$v=array_search(trim($res['phase']),$array1,false);
			if($v!==false)
				{
					$dn_mappings['phase']=$array2[$v];
				}
			else
			$dn_mappings['primary_outcome_measure']=$res['primary_outcome_measure'];
			$dn_mappings['primary_outcome_safety_issue']=$res['primary_outcome_safety_issue'];
			$dn_mappings['primary_outcome_timeframe']=$res['primary_outcome_timeframe'];
			$dn_mappings['sampling_method']=$res['sampling_method'];
			$dn_mappings['secondary_id']=$res['secondary_id'];
			$dn_mappings['secondary_outcome_measure']=$res['secondary_outcome_measure'];
			$dn_mappings['institution_type']=getInstitutionType($res['collaborator'],$res['lead_sponsor'],$larvol_id); 
			$dn_mappings['secondary_outcome_safety_issue']=$res['secondary_outcome_safety_issue'];
			$dn_mappings['secondary_outcome_timeframe']=$res['secondary_outcome_timeframe'];
			$dn_mappings['source']=$res['source'];
			$dn_mappings['start_date']=$res['start_date'];
			$dn_mappings['study_design']=$res['study_design'];
			$dn_mappings['study_pop']=$res['study_pop'];
			$dn_mappings['study_type']=$res['study_type'];
			$dn_mappings['verification_date']=$res['verification_date'];
			$dn_mappings['why_stopped']=$res['why_stopped'];
			
			$inactiveStatus = 
			array
			(
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
			
			$x=array_search($res['overall_status'],$inactiveStatus);
			if($x) $isactive=0; else $isactive=1;
			$dn_mappings['is_active']=$isactive;
			
			
			
			return $dn_mappings[$field_name];
		}
		
		//
		elseif(strtoupper($source)=='EUDRACT' and ($nct_exists !== true) )
		{
			$mappings=array();
			$mappings['source_id']=$res['eudract_id'];
			if(empty($res['lay_title'])) 
				$mappings['brief_title']=$res['full_title'];
			else
				$mappings['brief_title']=$res['lay_title'];
			$mappings['acronym']=$res['abbr_title'];
			$mappings['official_title']=$res['full_title'];
			$mappings['lead_sponsor']=$res['sponsor_name'];
			$mappings['detailed_description']=$res['main_objective'];
			$mappings['overall_status']=$res['trial_status'];
			$mappings['start_date']=$res['start_date'];
			$mappings['institution_type']=getInstitutionType($res['support_org_name'],$res['sponsor_name'],$res['larvol_id']);
			$mappings['end_date']=$res['end_date_global'];
			$mappings['end_date_global']=$res['end_date_global'];
			
			$mappings['inclusion_criteria']=$res['inclusion_criteria'];
			$mappings['exclusion_criteria']=$res['exclusion_criteria'];
			$mappings['firstreceived_date']=$res['firstreceived_date'];
			
			
			$e_is_active=array
			(
			"Ongoing"=> "1",
			"Restarted"=> "1",
			"Completed"=> "0",
			"Temporarily Halted"=> "0",
			"Prematurely Ended"=> "0",
			"Not Authorised"=> "0",
			"Prohibited by National Competent Authority"=> "0",
			"Suspended by National Competent Authority"=> "0"
			);
			
			$mappings['is_active']=$e_is_active[$res['trial_status']];
			
			if($res['gender_female']==1 && $res['gender_male']==1)
				$mappings['gender']='both';
			elseif($res['gender_female']==1)
				$mappings['gender']='female';
			elseif($res['gender_male']==1)
				$mappings['gender']='male';
			$mappings['phase'] = eudraPhase($res['tp_phase1_human_pharmacology'],$res['tp_phase2_explatory'],
					$res['tp_phase3_confirmatory'],$res['tp_phase4_use']);
			$mappings['enrollment']=$res['enrollment_memberstate'];
			$mappings['condition']=$res['condition'];
			$mp='';
			if(!empty($res['product_name']))  $mp.= '`'.$res['product_name'];
			if(!empty($res['product_code']))  $mp.= '`'.$res['product_code'];
			if(!empty($res['product_pharm_form']))  $mp.='`'.$res['imp_trade_name'];
			if(!empty($res['imp_trade_name']))  $mp.='`'.$res['imp_trade_name'];
			$mappings['intervention_name'] = substr($mp,1);
			$mappings['primary_outcome_measure']=$res['primary_endpoint'];
			$mappings['location_city']=$res['city'];
			$mappings['secondary_outcome_measure']=$res['secondary_endpoint'];
			$agestr='';
			if($res['age_has_under18']==1) $agestr.='`age_has_under18';
			if($res['age_has_in_utero']==1) $agestr.='`age_has_in_utero';
			if($res['age_has_preterm_newborn']==1) $agestr.='`age_has_preterm_newborn';
			if($res['age_has_infant_toddler']==1) $agestr.='`age_has_infant_toddler';
			if($res['age_has_children']==1) $agestr.='`age_has_children';
			if($res['age_has_adolescent']==1) $agestr.='`age_has_adolescent';
			if($res['age_has_adult']==1) $agestr.='`age_has_adult';
			if($res['age_has_elderly']==1) $agestr.='`age_has_elderly';
			if(substr($agestr,0,1)=='`') $agestr=substr($agestr,1);
			$mappings['ages']=$agestr;
			return $mappings[$field_name];
			
			/*
arm_group_type & intervention NA
			study_design - NA
			location_zip NA
			location_country NA
			source & brief_summary only in manually entered trials.
			*/
		}
		

	return false;
}

/******************
below ones are missing:

`data_manual`.`inclusion_criteria`,
`data_manual`.`exclusion_criteria`,
`data_manual`.`viewcount`,

`data_nct`.`institution_type`,
`data_nct`.`is_active`,
`data_nct`.`region`,
`data_nct`.`ages`


		
**********************************/
		
		/****************/
		
		


?>
