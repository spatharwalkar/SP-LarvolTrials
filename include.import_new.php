<?php
require_once('db.php');
require_once ('include.derived.php');
$newtrial='NO';
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

/**** EUDRACT BEGIN ************/
	$eudra_status=array
	(
	"Ongoing" => "Ongoing",
	"Restarted" => "Ongoing",
	"Completed"=> "Completed",
	"Temporarily Halted" => "Suspended",
	"Prematurely Ended"=> "Terminated",
	"Not Authorised"=> "Not Authorised",
	"Prohibited by National Competent Authority"=> "Prohibited",
	"Suspended by National Competent Authority"=> "Suspended"
	);
	
	$eudra_status_order=array
	(
	"Not Authorised"=> 1,
	"Prohibited"=> 2,
	"Suspended"=> 3,
	"Terminated"=> 4, 	
	"Temporarily Not Available" => 5,
	"Ongoing" => 6,
	"Completed"=> 7
	);

	$eudra_status_is_active=array
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
	
	function addEudraValToEudraCT($larvol_id, $fieldname, $value,$lastchanged_date,$oldtrial,$ins_type,$end_date)
	{
		$nullvalue='NO';
		if(	$fieldname=='enrollment' and(is_null($value) or empty($value) or $value=='') )
		{
			$value = null;
			$nullvalue='YES';
			//		return true;
		}

		$lastchanged_date = normal('date',$lastchanged_date);
		global $now;
		global $logger;
		$DTnow = date('Y-m-d H:i:s',$now);

	    $raw_value=$value;
		$value=mysql_real_escape_string($value);
		$raw_value=mysql_real_escape_string($raw_value);

		$dn_array=array
		(
			'dummy', 'larvol_id'  ,'national_competent_authority'  ,'trial_type'  ,'trial_status'  ,'start_date' ,'firstreceived_date' ,'member_state_concerned'  ,'eudract_id'  ,'full_title'  ,'lay_title'  ,'abbr_title'  ,'sponsor_protocol_code'  ,'isrctn_id'  ,'nct_id'  ,'who_urtn'  ,'other_name'  ,'other_id'  ,'is_pip'  ,'pip_emad_number'  ,'sponsor_name'  ,'sponsor_country'  ,'sponsor_status'  ,'support_org_name'  ,'support_org_country'  ,'contact_org_name'  ,'contact_point_func_name'  ,'street_address'  ,'city'  ,'postcode'  ,'country'  ,'phone'  ,'fax'  ,'email'  ,'imp_role'  ,'imp_auth'  ,'imp_trade_name'  ,'marketing_auth_holder'  ,'marketing_auth_country'  ,'imp_orphan'  ,'imp_orphan_number'  ,'product_name'  ,'product_code'  ,'product_pharm_form'  ,'product_paediatric_form'  ,'product_route'  ,'inn'  ,'cas'  ,'sponsor_code'  ,'other_desc_name'  ,'ev_code'  ,'concentration_unit'  ,'concentration_type'  ,'concentration_number' ,'imp_active_chemical'  ,'imp_active_bio'  ,'type_at'  ,'type_somatic_cell'  ,'type_gene'  ,'type_tissue'  ,'type_combo_at'  ,'type_cat_class'  ,'type_cat_number'  ,'type_combo_device_not_at'  ,'type_radio'  ,'type_immune'  ,'type_plasma'  ,'type_extract'  ,'type_recombinant'  ,'type_gmo'  ,'type_herbal'  ,'type_homeopathic'  ,'type_other'  ,'type_other_name'  ,'placebo_used'  ,'placebo_form'  ,'placebo_route'  ,'condition'  ,'lay_condition'  ,'therapeutic_area'  ,'dra_version'  ,'dra_level'  ,'dra_code'  ,'dra_organ_class'  ,'dra_rare'  ,'main_objective'  ,'secondary_objective'  ,'has_sub_study'  ,'sub_studies'  ,'inclusion_criteria'  ,'exclusion_criteria'  ,'primary_endpoint'  ,'primary_endpoint_timeframe'  ,'secondary_endpoint'  ,'secondary_endpoint_timeframe'  ,'scope_diagnosis'  ,'scope_prophylaxis'  ,'scope_therapy'  ,'scope_safety'  ,'scope_efficacy'  ,'scope_pharmacokinectic'  ,'scope_pharmacodynamic'  ,'scope_bioequivalence'  ,'scope_dose_response'  ,'scope_pharmacogenetic'  ,'scope_pharmacogenomic'  ,'scope_pharmacoeconomic'  ,'scope_other'  ,'scope_other_description'  ,'tp_phase1_human_pharmacology'  ,'tp_first_administration_humans'  ,'tp_bioequivalence_study'  ,'tp_other'  ,'tp_other_description'  ,'tp_phase2_explatory'  ,'tp_phase3_confirmatory'  ,'tp_phase4_use'  ,'design_controlled'  ,'design_randomised'  ,'design_open'  ,'design_single_blind'  ,'design_double_blind'  ,'design_parallel_group'  ,'design_crossover'  ,'design_other'  ,'design_other_description'  ,'comp_other_products'  ,'comp_placebo'  ,'comp_other'  ,'comp_descr'  ,'comp_number_arms'  ,'single_site'  ,'multi_site'  ,'number_of_sites'  ,'multiple_member_state'  ,'number_sites_eea'  ,'eea_both_inside_outside'  ,'eea_outside_only'  ,'eea_inside_outside_regions'  ,'has_data_mon_comm'  ,'definition_of_end'  ,'dur_est_member_years'  ,'dur_est_member_months'  ,'dur_est_member_days'  ,'dur_est_all_years'  ,'dur_est_all_months'  ,'dur_est_all_days'  ,'age_has_under18'  ,'age_number_under18'  ,'age_has_in_utero'  ,'age_number_in_utero'  ,'age_has_preterm_newborn'  ,'age_number_preterm_newborn'  ,'age_has_newborn'  ,'age_number_newborn'  ,'age_has_infant_toddler'  ,'age_number_infant_toddler'  ,'age_has_children'  ,'age_number_children'  ,'age_has_adolescent'  ,'age_number_adolescent'  ,'age_has_adult'  ,'age_number_adult'  ,'age_has_elderly'  ,'age_number_elderly'  ,'gender_female'  ,'gender_male'  ,'subjects_healthy_volunteers'  ,'subjects_patients'  ,'subjects_vulnerable'  ,'subjects_childbearing_no_contraception'  ,'subjects_childbearing_with_contraception'  ,'subjects_pregnant'  ,'subjects_nursing'  ,'subjects_emergency'  ,'subjects_incapable_consent'  ,'subjects_incapable_consent_details'  ,'subjects_other'  ,'subjects_other_details'  ,'enrollment_memberstate'  ,'enrollment_intl_eea'  , 'enrollment_intl_all' ,'aftercare'  ,'inv_network_org'  ,'inv_network_country'  ,'committee_third_first_auth'  ,'committee_first_auth_third'  ,'review_decision'  ,'review_decision_date'  ,'review_opinion'  ,'review_opinion_reason'  ,'review_opinion_date'  ,'end_status'  ,'end_date_global' 
		);
	   $as=array_search($fieldname,$dn_array);

	   if ( isset($as) and $as)
	   {
			$query = 'SELECT `' .$fieldname. '`  FROM data_eudract WHERE `larvol_id`="'. $larvol_id . '" limit 1';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$row = mysql_fetch_assoc($res);

			$change = ($row[$fieldname]===null and $value !== null) or ($value != $row[$fieldname]);

			if(!mysql_query('BEGIN'))
			{
				$log='Could not begin transaction.   SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			if(1)
			{

				$dn_array=array('dummy', 'larvol_id'  ,'national_competent_authority'  ,'trial_type'  ,'trial_status'  ,'start_date' ,'firstreceived_date' ,'member_state_concerned'  ,'eudract_id'  ,'full_title'  ,'lay_title'  ,'abbr_title'  ,'sponsor_protocol_code'  ,'isrctn_id'  ,'nct_id'  ,'who_urtn'  ,'other_name'  ,'other_id'  ,'is_pip'  ,'pip_emad_number'  ,'sponsor_name'  ,'sponsor_country'  ,'sponsor_status'  ,'support_org_name'  ,'support_org_country'  ,'contact_org_name'  ,'contact_point_func_name'  ,'street_address'  ,'city'  ,'postcode'  ,'country'  ,'phone'  ,'fax'  ,'email'  ,'imp_role'  ,'imp_auth'  ,'imp_trade_name'  ,'marketing_auth_holder'  ,'marketing_auth_country'  ,'imp_orphan'  ,'imp_orphan_number'  ,'product_name'  ,'product_code'  ,'product_pharm_form'  ,'product_paediatric_form'  ,'product_route'  ,'inn'  ,'cas'  ,'sponsor_code'  ,'other_desc_name'  ,'ev_code'  ,'concentration_unit'  ,'concentration_type'  ,'concentration_number' ,'imp_active_chemical'  ,'imp_active_bio'  ,'type_at'  ,'type_somatic_cell'  ,'type_gene'  ,'type_tissue'  ,'type_combo_at'  ,'type_cat_class'  ,'type_cat_number'  ,'type_combo_device_not_at'  ,'type_radio'  ,'type_immune'  ,'type_plasma'  ,'type_extract'  ,'type_recombinant'  ,'type_gmo'  ,'type_herbal'  ,'type_homeopathic'  ,'type_other'  ,'type_other_name'  ,'placebo_used'  ,'placebo_form'  ,'placebo_route'  ,'condition'  ,'lay_condition'  ,'therapeutic_area'  ,'dra_version'  ,'dra_level'  ,'dra_code'  ,'dra_organ_class'  ,'dra_rare'  ,'main_objective'  ,'secondary_objective'  ,'has_sub_study'  ,'sub_studies'  ,'inclusion_criteria'  ,'exclusion_criteria'  ,'primary_endpoint'  ,'primary_endpoint_timeframe'  ,'secondary_endpoint'  ,'secondary_endpoint_timeframe'  ,'scope_diagnosis'  ,'scope_prophylaxis'  ,'scope_therapy'  ,'scope_safety'  ,'scope_efficacy'  ,'scope_pharmacokinectic'  ,'scope_pharmacodynamic'  ,'scope_bioequivalence'  ,'scope_dose_response'  ,'scope_pharmacogenetic'  ,'scope_pharmacogenomic'  ,'scope_pharmacoeconomic'  ,'scope_other'  ,'scope_other_description'  ,'tp_phase1_human_pharmacology'  ,'tp_first_administration_humans'  ,'tp_bioequivalence_study'  ,'tp_other'  ,'tp_other_description'  ,'tp_phase2_explatory'  ,'tp_phase3_confirmatory'  ,'tp_phase4_use'  ,'design_controlled'  ,'design_randomised'  ,'design_open'  ,'design_single_blind'  ,'design_double_blind'  ,'design_parallel_group'  ,'design_crossover'  ,'design_other'  ,'design_other_description'  ,'comp_other_products'  ,'comp_placebo'  ,'comp_other'  ,'comp_descr'  ,'comp_number_arms'  ,'single_site'  ,'multi_site'  ,'number_of_sites'  ,'multiple_member_state'  ,'number_sites_eea'  ,'eea_both_inside_outside'  ,'eea_outside_only'  ,'eea_inside_outside_regions'  ,'has_data_mon_comm'  ,'definition_of_end'  ,'dur_est_member_years'  ,'dur_est_member_months'  ,'dur_est_member_days'  ,'dur_est_all_years'  ,'dur_est_all_months'  ,'dur_est_all_days'  ,'age_has_under18'  ,'age_number_under18'  ,'age_has_in_utero'  ,'age_number_in_utero'  ,'age_has_preterm_newborn'  ,'age_number_preterm_newborn'  ,'age_has_newborn'  ,'age_number_newborn'  ,'age_has_infant_toddler'  ,'age_number_infant_toddler'  ,'age_has_children'  ,'age_number_children'  ,'age_has_adolescent'  ,'age_number_adolescent'  ,'age_has_adult'  ,'age_number_adult'  ,'age_has_elderly'  ,'age_number_elderly'  ,'gender_female'  ,'gender_male'  ,'subjects_healthy_volunteers'  ,'subjects_patients'  ,'subjects_vulnerable'  ,'subjects_childbearing_no_contraception'  ,'subjects_childbearing_with_contraception'  ,'subjects_pregnant'  ,'subjects_nursing'  ,'subjects_emergency'  ,'subjects_incapable_consent'  ,'subjects_incapable_consent_details'  ,'subjects_other'  ,'subjects_other_details'  ,'enrollment_memberstate'  ,'enrollment_intl_eea'  ,'enrollment_intl_all'  ,'aftercare'  ,'inv_network_org'  ,'inv_network_country'  ,'committee_third_first_auth'  ,'committee_first_auth_third'  ,'review_decision'  ,'review_decision_date'  ,'review_opinion'  ,'review_opinion_reason'  ,'review_opinion_date'  ,'end_status'  ,'end_date_global');
				$as=array_search($fieldname,$dn_array);

				if ( isset($as) and $as)
				{
					if($fieldname=='end_date')
					{
						$query = 'SELECT `' .$fieldname. '`, `lastchanged_date`  FROM data_trials WHERE `larvol_id`="'. $larvol_id . '" limit 1';
					}
					else
						$query = 'SELECT data_eudract.`' .$fieldname. '`, data_trials.`lastchanged_date` FROM data_eudract, data_trials WHERE data_eudract.larvol_id = data_trials.larvol_id and data_trials.`larvol_id`="'. $larvol_id . '" limit 1';

					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					$row = mysql_fetch_assoc($res);
					$olddate=$row['lastchanged_date'];
					$oldval=$row[$fieldname];
					$value=mysql_real_escape_string($value);

					$query = 'update `data_eudract` set `' . $fieldname . '` = "' . $value .'" where `larvol_id`="' .$larvol_id . '"  limit 1'  ;
					if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
				}
			}
	   	 
	   }
	   else
	   {
			logDataErr('<br>To Eudra CT: Not present in dataschema : IGNORED the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog
	   }
	   return true;
	}	
	function getEudraValue($fieldname, $value)
	{
		//normalize the input
		if(!is_array($value))
		{
			$ynbool = ynbool($value);
			$tt=strpos('a'.$fieldname, "_date")  ;
			if(isset($tt) and !empty($tt))
			{
				$value = normal('date',$value);
			}
			elseif ($ynbool != 'NULL')
			{
				$value = $ynbool;
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
			$num_max = 0;
			foreach($value as $key => $v)
			{
				$tt=strpos('a'.$fieldname, "_date")  ;
				$ynbool = ynbool($v);
				if(isset($tt) and !empty($tt))
				{
					$newval = normal('date',(string)$v);
				}
				elseif(is_numeric($v))
				{
					$newval = normal('int',(int)$v);
					if($newval > $num_max)
					{
						$num_max = $new_val;
					}
					else 
					{
						$new_val =$num_max;
					}
				} 	 	
				elseif ($ynbool != 'NULL')
				{
					$newval = $ynbool;
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
		return $value;
	}

	function addEudraValToDataTrial($larvol_id, $fieldname, $value,$lastchanged_date,$oldtrial,$ins_type,$end_date)
	{
		$nullvalue='NO';
		if(	$fieldname=='enrollment' and(is_null($value) or empty($value) or $value=='') )
		{
			$value = null;
			$nullvalue='YES';
		}

		$lastchanged_date = normal('date',$lastchanged_date);
		global $now;
		global $logger;
		$DTnow = date('Y-m-d H:i:s',$now);

		$raw_value=$value;
		if(is_null($value))
		{
			//do nothing
			//echo("Field is null");
		}
		else
		{
		$value=mysql_real_escape_string($value);
		$raw_value=mysql_real_escape_string($raw_value);
		}
		

		$dn_array=array
		(
				'dummy', 'larvol_id', 'source_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type',  'study_pop', 'sampling_method', 'criteria', 'inclusion_criteria', 'exclusion_criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801', 'viewcount', 'ages'
				);

				$as=array_search($fieldname,$dn_array);

				if ( isset($as) and $as)
				{
					$query = 'SELECT `' .$fieldname. '`, `lastchanged_date`  FROM data_trials WHERE `larvol_id`="'. $larvol_id . '" limit 1';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					$row = mysql_fetch_assoc($res);
					$olddate=$row['lastchanged_date'];
					$oldval=$row[$fieldname];

					//check if the data is manually overridden
					$dn_array1=array
					(
					'dummy', 'larvol_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
					);
					$as1=array_search($fieldname,$dn_array1);
					if ( isset($as1) and $as1)
					{
						$query = 'SELECT `' .$fieldname. '` FROM data_manual WHERE `larvol_id`="'. $larvol_id . '" and `' .$fieldname. '` is not null limit 1';
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							mysql_query('ROLLBACK');
							echo $log;
							return false;
						}

						$row = mysql_fetch_assoc($res);
						$overridden = $row !== false;
						if($overridden and !empty($row[$fieldname]))
						$value=mysql_real_escape_string($row[$fieldname]);
					}
					//
					if($nullvalue=='YES')
					{
						$raw_value=null;
						$value=null;
					}
					// set all fields to null if their value is "ND"
					if($value=='ND')
					{
						$value=null;
					}

					$dt_array=array
					(
				'dummy', 'larvol_id', 'source_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type',  'study_pop', 'sampling_method', 'criteria', 'inclusion_criteria', 'exclusion_criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801', 'viewcount', 'ages'
				);
				$as=array_search($fieldname,$dt_array);

				if ( isset($as) and $as)
				{
					if(is_null($value)) $query = 'update data_trials set `' . $fieldname . '` = null , lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					else $query = 'update data_trials set `' . $fieldname . '` = "' . $value .'", lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;

					if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					// update sphinx index
					if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
					{
						global $sphinx;
						update_sphinx_index($larvol_id);
					}
					
					$query = 'SELECT `larvol_id` FROM data_history where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					$res = mysql_fetch_assoc($res);
					$exists = $res !== false;
					$oldval=mysql_real_escape_string($oldval);

					if($fieldname=='end_date')
					{
						$value=$end_date;
					}

					$val1=str_replace("\\", "", $oldval);
					$val2=str_replace("\\", "", $value);
					$cond1=( $exists and trim($val1)<>trim($val2) and $oldval<>'0000-00-00' and !empty($oldval) );


					if ($fieldname=='phase' and ( is_null($oldval) or strlen(trim($oldval)) ==0 or empty($oldval)) )
					$cond2=false; else $cond2=true;
					
					if($cond1 and $cond2 and $nullvalue=='NO' and $oldval<> "ND")
					{
						$query = 'update data_history set `' . $fieldname . '_prev` = "' . $oldval .'", `' . $fieldname . '_lastchanged` = "' . $olddate .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
						if(!mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							mysql_query('ROLLBACK');
							echo $log;
							return false;
						}

					}
					else
					{
						if(  $oldtrial  and $nullvalue=='NO' and $oldval<> "ND" )
						{
							$val1=str_replace("\\", "", $oldval);
							$val2=str_replace("\\", "", $value);
							$cond1=( trim($val1)<>trim($val2) and $oldval<>'0000-00-00' and !empty($oldval) );
							if ($fieldname=='phase' and ( is_null($oldval) or strlen(trim($oldval)) ==0 or empty($oldval)) )
							$cond2=false; else $cond2=true;
							if($cond1 and $cond2)
							{
								$query = 'insert into `data_history` set `' . $fieldname . '_prev` = "' . mysql_real_escape_string($oldval) .'", `' . $fieldname . '_lastchanged` = "' . $olddate .'" , `larvol_id`="' .$larvol_id . '" ' ;
								if(!mysql_query($query))
								{
									$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
									$logger->error($log);
									mysql_query('ROLLBACK');
									echo $log;
									return false;
								}
							}
						}
					}


				}

				}


				return true;

	}
	
	function eudraCalcStudyDesign($rec)
	{
		$strval = '';
		if((strlen($rec->design_controlled) <> 0) && ($rec->design_controlled == 1))
		{
			$strval = $strval . 'design_controlled' . '`' ;
		}
		if((strlen($rec->design_randomised) <> 0) && ($rec->design_randomised == 1))
		{
			$strval = $strval  . 'design_randomised' . '`';
		}
		if((strlen($rec->design_open) <> 0) && ($rec->design_open == 1))
		{
			$strval = $strval  . 'design_open' . '`';
		}
		if((strlen($rec->design_single_blind) <> 0) && ($rec->design_single_blind == 1))
		{
			$strval = $strval  . 'design_single_blind' . '`';
		}
		if((strlen($rec->design_double_blind) <> 0) && ($rec->design_double_blind == 1))
		{
			$strval = $strval  . 'design_double_blind' . '`';
		}
		if((strlen($rec->design_parallel_group) <> 0) && ($rec->design_parallel_group == 1))
		{
			$strval = $strval  . 'design_parallel_group' . '`';
		}
		if((strlen($rec->design_crossover) <> 0) && ($rec->design_crossover == 1))
		{
			$strval = $strval  . 'design_crossover' . '`';
		}
		if((strlen($rec->design_other) <> 0) && ($rec->design_other == 1))
		{
			$strval = $strval  . 'design_other' . '`';
		}
		if(strlen($strval) == 0)
		{
			$strval = $rec->design_other_description;
		}
		else
		{
			$strval = substr($strval, 0, -1); //remove the last '`'
		}
		return $strval;
	}

	function eudraEnrollment($enrollment_all, $member_state)
	{
		/*
		if((strlen($enrollment_all) <> 0)  && ($enrollment_all == 1))
		{
			return $enrollment_all;
		}
		*/
		// check if enrollment_all is > 0, and if yes, pick it.
		if( !empty($enrollment_all) && $enrollment_all > 0 )
		{
			return $enrollment_all;
		}
		else
		{
			return $member_state;
		}
	}

	function eudraGender($male, $female)
	{
		if((strlen($male) <> 0) && ($male == 1) && (strlen($female) <> 0) && ($female == 1))
		{
			return 1;
		}
		else if((strlen($male) <> 0) && ($male == 1))
		{
			return 1;
		}
		else if((strlen($female) <> 0) && ($female == 1))
		{
			return 1;
		}
		else
		{
			return 0;
		}

	}

	function eudraPhase($phase1, $phase2, $phase3, $phase4)
	{
		if((strlen($phase1) <> 0) && ($phase1 == 1))
		{
			return "1";
		}
		else if((strlen($phase2) <> 0) && ($phase2 == 1))
		{
			return "2";
		}
		else if((strlen($phase3) <> 0) && ($phase3 == 1))
		{
			return "3";
		}
		else if((strlen($phase4) <> 0) && ($phase4 == 1))
		{
			return "4";
		}

		return "1";

	}

	function eudraInterventionType($rec)
	{
		$strval = '';
		if((strlen($rec->type_at) <> 0) && ($rec->type_at == 1))
		{
			$strval = $strval  . 'type_at' . '`';
		}
		if((strlen($rec->type_somatic_cell) <> 0) && ($rec->type_somatic_cell == 1))
		{
			$strval = $strval . 'type_somatic_cell' . '`' ;
		}
		if((strlen($rec->type_gene) <> 0) && ($rec->type_gene == 1))
		{
			$strval = $strval  . 'type_gene' . '`';
		}
		if((strlen($rec->type_tissue) <> 0) && ($rec->type_tissue == 1))
		{
			$strval = $strval  . 'type_tissue' . '`';
		}
		if((strlen($rec->type_combo_at) <> 0) && ($rec->type_combo_at == 1))
		{
			$strval = $strval  . 'type_combo_at' . '`';
		}
		if((strlen($rec->type_cat_class) <> 0) && ($rec->type_cat_class == 1))
		{
			$strval = $strval  . 'type_cat_class' . '`';
		}
		if((strlen($rec->type_cat_number) <> 0) && ($rec->type_cat_number == 1))
		{
			$strval = $strval  . 'type_cat_number' . '`';
		}
		if((strlen($rec->type_combo_device_not_at) <> 0) && ($rec->type_combo_device_not_at == 1))
		{
			$strval = $strval  . 'type_combo_device_not_at' . '`';
		}
		if((strlen($rec->type_radio) <> 0) && ($rec->type_radio == 1))
		{
			$strval = $strval  . 'type_radio' . '`';
		}
		if((strlen($rec->type_immune) <> 0) && ($rec->type_immune == 1))
		{
			$strval = $strval . 'type_immune'  . '`';
		}
		if((strlen($rec->type_plasma) <> 0) && ($rec->type_plasma == 1))
		{
			$strval = $strval  . 'type_plasma' . '`';
		}
		if((strlen($rec->type_extract) <> 0) && ($rec->type_extract == 1))
		{
			$strval = $strval  . 'type_extract' . '`';
		}
		if((strlen($rec->type_recombinant) <> 0) && ($rec->type_recombinant == 1))
		{
			$strval = $strval  . 'type_recombinant' . '`';
		}
		if((strlen($rec->type_gmo) <> 0) && ($rec->type_gmo == 1))
		{
			$strval = $strval  . 'type_gmo' . '`';
		}
		if((strlen($rec->type_herbal) <> 0) && ($rec->type_herbal == 1))
		{
			$strval = $strval  . 'type_herbal' . '`';
		}
		if((strlen($rec->type_homeopathic) <> 0) && ($rec->type_homeopathic == 1))
		{
			$strval = $strval  . 'type_homeopathic' . '`';
		}
		if((strlen($rec->type_other) <> 0) && ($rec->type_other == 1))
		{
			$strval = $strval  . 'type_other' . '`';
		}
		
		if(strlen($strval) == 0)
		{
			$strval = $rec->type_other_name;
		}
	    else
		{
			$strval = substr($strval, 0, -1); //remove the last '`'
		}
		return $strval;
	}
	function eudraAge($rec)
	{
		$strval = '';
		if((strlen($rec->age_has_under18) <> 0) && ($rec->age_has_under18 == 1))
		{
			$strval = $strval  . 'age_has_under18' . '`';
		}
		if((strlen($rec->age_has_in_utero) <> 0) && ($rec->age_has_in_utero == 1))
		{
			$strval = $strval  . 'age_has_in_utero' . '`';
		}
		if((strlen($rec->age_has_preterm_newborn) <> 0) && ($rec->age_has_preterm_newborn == 1))
		{
			$strval = $strval  . 'age_has_preterm_newborn' . '`';
		}
		if((strlen($rec->age_has_newborn) <> 0) && ($rec->age_has_newborn == 1))
		{
			$strval = $strval  . 'age_has_newborn' . '`';
		}
		if((strlen($rec->age_has_infant_toddler) <> 0) && ($rec->age_has_infant_toddler == 1))
		{
			$strval = $strval  . 'age_has_infant_toddler' . '`';
		}
		if((strlen($rec->age_has_children) <> 0) && ($rec->age_has_children == 1))
		{
			$strval = $strval  . 'age_has_children' . '`';
		}
		if((strlen($rec->age_has_adolescent) <> 0) && ($rec->age_has_adolescent == 1))
		{
			$strval = $strval . 'age_has_adolescent'  . '`';
		}
		if((strlen($rec->age_has_adult) <> 0) && ($rec->age_has_adult == 1))
		{
			$strval = $strval  . 'age_has_adult' . '`';
		}
		if((strlen($rec->age_has_elderly) <> 0) && ($rec->age_has_elderly == 1))
		{
			$strval = $strval  . 'age_has_elderly' . '`';
		}
		
	    if(strlen($strval) > 0)
		{
			$strval = substr($strval, 0, -1); //remove the last '`'
		}
		return $strval;
	}
	function addMultiVal($input)
	{
		if(!empty($input))
		{
			return '`' . $input;
		}
		return '';
	}

	function eudraStatus($input)
	{
		global $eudra_status, $eudra_status_order;
		$vals = explode("`", $input);
		$max = 0;
		$max_status = "";
		foreach ($vals as $val)
		{
			$status = $eudra_status[$val];
			$order = $eudra_status_order[$status];
			if($order > $max)
			{
			 $max=$order;
			 $max_status = $status;
			}
		}
		return $max_status;
		
	}
	
	function eudraRegion($input)
	{

		$vals = explode("`", $input);
		$arr = array();
		foreach ($vals as $val)
		{
			//		echo("before transform: ");
			//		var_dump($val);
			//		echo("<br>");
			$region=getRegions($val);
			if($region=='other') $region='RoW';
			array_push($arr, $region);
		}
		$uniq_arr = array_unique($arr);
		$output="";
		$cnt=count($uniq_arr);
		$c1=1;
		foreach($uniq_arr as $value)
		{
			if($c1<$cnt) $output .= $value."`";
			else $output .= $value;
			$c1++;
		}
		return $output;
	}
	

	function eudraDate($array, $is_min)
	{
		if($array == null)
		{
			return null;
		}
		$cnt = count($array);
		if($cnt == 0) return null;

		$out = normal('date', $array[0]);
		foreach ($array as $val)
		{
			$new_val =  normal('date', $val);
			if($is_min)
			{
				if($new_val < $min_date)
				{
					$out = $new_val;
				}
			}
			else
			{
				if($new_val > $min_date)
				{
					$out = $new_val;
				}
			}
		}

		return $out;
	}

	function eudraCountry($input)
	{
		$vals = explode("`", $input);//split
		$output="";
		$cnt=count($vals);
		$c1=1;
		foreach ($vals as $val)
		{
			$arr = explode("-", $val);//split by eiphen and taken first value
			$value = trim($arr[0]);
			if($c1<$cnt) $output .= $value."`";
			else $output .= $value;
			$c1++;
		}
		return $output;

	}
	
	// Add or update a Eudract record from a SimpleXML object.
	function addEudraCT($record,$stdt)
	{
		global $db;
		global $instMap;
		global $now;
		global $logger;
		global $eudract_last_updated_date;

		global $eudra_status;

		global $eudra_status_is_active;

		if($record === false) return false;

		$DTnow = date('Y-m-d H:i:s',$now);


		// TODO:  Since same eud number is stored for multiple countries.. Need to make the
		// id of the case the number + countrys

		// Get Coutry
		//$i = strpos($rec['national_competent_authority'][0], "-");
		//$country = substr($rec['national_competent_authority'][0], 0, $i);

		//$eud_id = $rec['eudract_id'][0] . " - " . $country;
		$eud_id = $record['eudract_id'][0];

		//    $eud_id = $rec['eudract_number'][0];
		echo "<br>EUD ID: " . $eud_id . "<br>";


		//$query = 'SELECT `larvol_id` FROM data_trials where `source_id`="' . $eud_id . '"  LIMIT 1';
		$query = 'SELECT `larvol_id` FROM data_trials where `source_id` like "%' . $eud_id . '%"  LIMIT 1';
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

			if(!mysql_query('BEGIN'))
			{
				$log='There seems to be a problem with beginning the transaction.   SQL Query:'.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				echo $log;
				exit;
			}
			$query = 'INSERT INTO data_trials SET `source_id`="' . $eud_id . '", `start_date`="' . $stdt . '" ' ;
			if(!mysql_query($query))
			{
				$log='There seems to be a problem with the SQL  Query:'.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				mysql_query('ROLLBACK');
				echo $log;
				exit;
			}
			$larvol_id = mysql_insert_id();
			$query = 'INSERT INTO data_eudract SET `larvol_id`=' . $larvol_id . ',eudract_id="' . $eud_id .'" , `start_date`="' . $stdt . '" ';
			if(!mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				mysql_query('ROLLBACK');
				echo $log;
				exit;
			}
			if(!mysql_query('COMMIT'))
			{
				$log='There seems to be a problem while committing the transaction  Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				mysql_query('ROLLBACK');
				echo $log;
				return false;
			}

		}
		$recordArray = array();
		foreach($record as $fieldname => $value)
		{
//			             echo("FieldName: ");
//						var_dump($fieldname);
//						echo("  Value: ");
//						var_dump($value);
//						echo("<br>");
			$recordArray [$fieldname] = getEudraValue($fieldname, $value);
		}
		$rec = (object)$recordArray;
		//All Mappings here
		global $eudra_status_is_active;
		/*************************************/
		$brief_title = strlen($rec -> lay_title) == 0 ? $rec->full_title : $rec->lay_title;
		$detailed_descr = $rec->main_objective . '`' . $rec->secondary_objective;
		$study_design = eudraCalcStudyDesign($rec);
		$enrollment = eudraEnrollment($rec->enrollment_intl_all, $rec->enrollment_memberstate);
		$criteria = $rec->inclusion_criteria . '`' . $rec->exclusion_criteria;
		$gender = eudraGender($rec->gender_male, $rec->gender_female);
		$phase = eudraPhase($rec->tp_phase1_human_pharmacology,$rec->tp_phase2_explatory,
		$rec->tp_phase3_confirmatory,$rec->tp_phase4_use);
		$condition = $rec->condition . addMultiVal($rec->lay_condition) . addMultiVal($rec->therapeutic_area);
		$intervention_type = eudraInterventionType($rec);
		$intervention_name = $rec->product_name . addMultiVal($rec->product_code) . addMultiVal($rec->product_pharm_form) . addMultiVal($rec->imp_trade_name);
		$ages = eudraAge($rec);
		$overall_status = eudraStatus($rec->trial_status);
		$is_active_overall = $eudra_status_is_active[$overall_status];
		$country = eudraCountry($rec->member_state_concerned);
		$region=eudraRegion($country);
		//$ins_type=getInstitutionType($rec->support_org_name,$rec->sponsor_name,$larvol_id);
		
		/* institution type */
		$qitp = 'SELECT institution_type FROM data_manual where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		if(!$ritp = mysql_query($qitp))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$ritp = mysql_fetch_assoc($ritp);
		if($ritp['institution_type'])
		{
			//store the value from data_manual
			$ins_type	=	$ritp['institution_type'];
		}
		else	
		{	$ins_type	=	getInstitutionType($rec->support_org_name,$rec->sponsor_name,$larvol_id);	}

		/*************/		
		
		/** REMOVE initial backticks */
		if(substr($brief_title,0,1)=='`') $brief_title=(substr($brief_title,1));
		if(substr($detailed_descr,0,1)=='`') $detailed_descr=(substr($detailed_descr,1));
		if(substr($study_design,0,1)=='`') $study_design=(substr($study_design,1));
		if(substr($enrollment,0,1)=='`') $enrollment=(substr($enrollment,1));
		if(substr($criteria,0,1)=='`') $criteria=(substr($criteria,1));
		if(substr($gender,0,1)=='`') $gender=(substr($gender,1));
		if(substr($phase,0,1)=='`') $phase=(substr($phase,1));
		if(substr($condition,0,1)=='`') $condition=(substr($condition,1));
		if(substr($intervention_type,0,1)=='`') $intervention_type=(substr($intervention_type,1));
		if(substr($intervention_name,0,1)=='`') $intervention_name=(substr($intervention_name,1));
		if(substr($ages,0,1)=='`') $ages=(substr($ages,1));
		if(substr($overall_status,0,1)=='`') $overall_status=(substr($overall_status,1));
		if(substr($is_active_overall,0,1)=='`') $is_active_overall=(substr($is_active_overall,1));
		if(substr($country ,0,1)=='`') $country =(substr($country ,1));
		if(substr($region,0,1)=='`') $region=(substr($region,1));
		if(substr($ins_type,0,1)=='`') $ins_type=(substr($ins_type,1));
		/*****************/
		
		/** FIX for  "`" separator displayed instead of "," */
		$region=str_replace("`", ", ", $region);
		/*****************/
        
		//All Dates
		$firstreceived_date = eudraDate($record[firstreceived_date], true);//get minimum
	    $start_date = eudraDate($record[start_date], true);
	    $end_date = eudraDate($record[end_date_global], false);
	    
	    //modify data going to eudra also w.r.t dates
	    $recordArray[firstreceived_date] = $firstreceived_date;
	    //$recordArray[start_date] = $start_date;
		$recordArray[start_date] = $stdt;
	    $recordArray[end_date_global] = $end_date;
		
		/*************************************/

		//Go through the parsed XML structure and pick out the data
		$record_data =array('brief_title' => $brief_title,
						//'acronym' => $rec->abbr_title,
						//unmapped abbr_title 
						'acronym' => null,
						'official_title' => $rec->full_title,
						 'lead_sponsor' => $rec->sponsor_name,
		                'collaborator' => $rec->support_org_name,
	                    'detailed_description' => $detailed_descr,
						'overall_status' => $overall_status,
	                    'is_active' => $is_active_overall,
						//'start_date' => $start_date, 	
	    				'start_date' => $stdt, 	
						'end_date' => $end_date,
	                    'study_design' => $study_design,
	                    'enrollment' => $enrollment,
	                    'criteria' => $criteria,
	                    'inclusion_criteria' => $rec->inclusion_criteria,
	'exclusion_criteria' => $rec->exclusion_criteria,
	'gender' => $gender,
	'healthy_volunteers' => $rec->subjects_healthy_volunteers,
	'firstreceived_date' => $firstreceived_date,
	'phase' => $phase,
	'condition' => $condition,
						'arm_group_description' => $rec->comp_other_products,
	'intervention_type' => $intervention_type,
	'intervention_name' => $intervention_name,
	'primary_outcome_measure' => $rec->primary_endpoint,
	'primary_outcome_timeframe' => $rec->primary_endpoint_timeframe,
	'secondary_outcome_measure' => $rec->secondary_endpoint,
	'primary_outcome_timeframe' => $rec->secondary_endpoint_timeframe,
	'location_city' => $rec->city,
	'location_zip' => $rec->postcode,
	'location_country' => $country,
	'investigator_name' => $rec->contact_point_func_name,
	'route_of_administration' => $rec->product_route,
	'ages' => $ages,
	'region' => $region,
	'institution_type' => $ins_type);	
		
		$end_date=normal('date',(string)$record_data->end_date);
		
		foreach($record_data as $fieldname => $value)
		if(!addEudraValToDataTrial($larvol_id, $fieldname, $value, $eudract_last_updated_date, $oldtrial,NULL,$end_date))
		logDataErr('<br>To Data Trial: Could not save the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog

		foreach($recordArray as $fieldname => $value)
		if(!addEudraValToEudraCT($larvol_id, $fieldname, $value, $eudract_last_updated_date, $oldtrial,NULL,$end_date))
		logDataErr('<br>To Eudra CT: Could not save the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog
//		
//		
//		//$inactive = $record_data['is_active'];
//		//$query = 'update data_trials set `institution_type`="' .$ins_type. '",`region`="'.$region.'", `is_active`='.$inactive.'  where `larvol_id`="' .$larvol_id . '" limit 1' ;
//		$query = 'update data_trials set `institution_type`="' .$ins_type. '",`region`="'.$region.'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//		if(!mysql_query($query))
//		{
//			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
//			$logger->error($log);
//			echo $log;
//			return false;
//		}

 // Remap the trial if it is already merged with another trial
		$query = 'select larvol_id from `data_nct` where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$res = mysql_fetch_assoc($res);
		$mergedTrial = $res !== false;
		if($mergedTrial)
		{
			require_once("remap_trials.php");
			remaptrials(null,$larvol_id,null);
			return true;
		}
	}
	
/******************EUDRACT END *********/	
//calculate field Ids and store in an array since it requires db call
//prefetch recurring derived fields' calculation data.


//Adds a new record of any recognized type from a simpleXML object.
//Autodetects the type if none is specified.
function addRecord($in, $type='unspec',$stdt=null)
{
	static $types = array('clinical_study' => 'nct', 'PubmedArticle' => 'pubmed', 'EudraCT' => 'EudraCT', 'isrctn' => 'isrctn');
	$type = strtolower($type);
	if($type == 'unspec') $type = $types[$in->getName()];
	
	switch($type)
	{
		case 'nct':
		return addNCT($in);
		case 'pubmed':
		return addPubmed($in);
		case 'eudract':
		return addEudraCT($in,$stdt);
		case 'isrctn':
		return addisrctn($in);
	}
	return false;
}

// Add or update a NCT record from a SimpleXML object.
function addNCT($rec)
{
	global $db;
	global $instMap;
	global $now;
	global $logger;
	global $newtrial;
	if($rec === false) return false;
	
	$DTnow = date('Y-m-d H:i:s',$now);
	$nct_id = unpadnct($rec->id_info->nct_id);

		
	$query = 'SELECT `larvol_id` FROM data_trials where `source_id` like "%' . $rec->id_info->nct_id . '%"  LIMIT 1';
	
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
		$newtrial='YES';
		if(!mysql_query('BEGIN'))
		{
			$log='There seems to be a problem with beginning the transaction.   SQL Query:'.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			exit;
		}
		$query = 'INSERT INTO data_trials SET `source_id`="' . $rec->id_info->nct_id . '"' ;
		if(!mysql_query($query))
		{
			$log='There seems to be a problem with the SQL  Query:'.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			mysql_query('ROLLBACK');
			echo $log;
			exit;
		}
		$larvol_id = mysql_insert_id();
		$query = 'INSERT INTO data_nct SET `larvol_id`=' . $larvol_id . ',nct_id="' . $nct_id .'"';
		if(!mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			mysql_query('ROLLBACK');
			echo $log;
			exit;
		}
		if(!mysql_query('COMMIT'))
		{
			$log='There seems to be a problem while committing the transaction  Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			mysql_query('ROLLBACK');
			echo $log;
			return false;
		}
		
	}
	
	 // echo '<pre>'; print_r($rec); echo '</pre>';
	
	if(isset($rec->status_block->brief_summary->textblock) and !empty($rec->status_block->brief_summary->textblock)) $bsummary=$rec->status_block->brief_summary->textblock;
else $bsummary=$rec->brief_summary->textblock;
if(isset($rec->status_block->detailed_descr->textblock) and !empty($rec->status_block->detailed_descr->textblock)) $ddesc=$rec->status_block->detailed_descr->textblock;
elseif(isset($rec->detailed_description->textblock) and !empty($rec->detailed_description->textblock)) $ddesc=$rec->detailed_description->textblock;
else $ddesc=$rec->detailed_descr->textblock;
	
	//Go through the parsed XML structure and pick out the data
	$record_data =array('brief_title' => $rec->brief_title,
						'official_title' => $rec->official_title,
						'keyword' => $rec->keyword,
						//'brief_summary' => $rec->brief_summary->textblock,
						 'brief_summary' => $bsummary,
						//'detailed_description' => $rec->detailed_description->textblock,
						  'detailed_description' => $ddesc,
						'why_stopped' => $rec->why_stopped,
						'study_design' => $rec->study_design,
						'biospec_descr' => $rec->biospec_descr->textblock,
						'study_pop' => $rec->eligibility->study_pop->textblock,
						'criteria' => $rec->eligibility->criteria->textblock,
						'biospec_retention' => $rec->biospec_retention,
						'completion_date_type' => $rec->completion_date['type'],
						'primary_completion_date_type' => $rec->primary_completion_date['type'],
						'enrollment_type' => $rec->enrollment['type'],
						'sampling_method' => $rec->eligibility->sampling_method,
						'rank' => $rec['rank'],
						'org_study_id' => $rec->id_info->org_study_id,
						'download_date' => substr($rec->required_header->download_date,
												  strpos($rec->required_header->download_date,' on ')+4),
						'acronym' => $rec->acronym,
						'lead_sponsor' => $rec->sponsors->lead_sponsor->agency,
						'source' => $rec->source,
						'has_dmc' => ynbool($rec->oversight_info->has_dmc),
						'overall_status' => $rec->overall_status,
						'start_date' => $rec->start_date,
						'end_date' => $rec->end_date,
						'completion_date' => $rec->completion_date,
						'primary_completion_date' => $rec->primary_completion_date,
						'phase' => $rec->phase,
						'study_type' => $rec->study_type,
						'number_of_arms' => $rec->number_of_arms,
						'number_of_groups' => $rec->number_of_groups,
						'enrollment' => $rec->enrollment,
						'gender' => strtolower($rec->eligibility->gender),
						'minimum_age' => strtoyears($rec->eligibility->minimum_age),
						'maximum_age' => strtoyears($rec->eligibility->maximum_age),
						'healthy_volunteers' => ynbool($rec->eligibility->healthy_volunteers),
						'contact_name' => assemble(' ', array($rec->overall_contact->first_name,
															  $rec->overall_contact->middle_name,
															  $rec->overall_contact->last_name)),
						'contact_degrees' => $rec->overall_contact->degrees,
						'contact_phone' => $rec->overall_contact->phone,
						'contact_phone_ext' => $rec->overall_contact->phone_ext,
						'contact_email' => $rec->overall_contact->email,
						'backup_name' => assemble(' ', array($rec->overall_contact_backup->first_name,
															  $rec->overall_contact_backup->middle_name,
															  $rec->overall_contact_backup->last_name)),
						'backup_degrees' => $rec->overall_contact_backup->degrees,
						'backup_phone' => $rec->overall_contact_backup->phone,
						'backup_phone_ext' => $rec->overall_contact_backup->phone_ext,
						'backup_email' => $rec->overall_contact_backup->email,
						'verification_date' => $rec->verification_date,
						'lastchanged_date' => $rec->lastchanged_date,
						'firstreceived_date' => $rec->firstreceived_date,
						'responsible_party_name_title' => $rec->responsible_party->name_title,
						'responsible_party_organization' => $rec->responsible_party->party_organization);
						
						if( ( !isset($record_data['enrollment']) or is_null($record_data['enrollment']) or empty($record_data['enrollment']) ) )
							$record_data['enrollment'] = $rec->eligibility->expected_enrollment;

	if(!is_array($rec->keyword)) 
	{
		$record_data['keyword'] = array();
		foreach($rec->keyword as $kw) $record_data['keyword'][] = $kw;
	}
	$record_data['secondary_id'] = array();
	foreach($rec->id_info->secondary_id as $sid) $record_data['secondary_id'][] = $sid;
	$record_data['nct_alias'] = array();
	foreach($rec->id_info->nct_alias as $nal) $record_data['nct_alias'][] = $nal;
	$record_data['collaborator'] = array();
	foreach($rec->sponsors->collaborator as $cola) $record_data['collaborator'][] = $cola->agency;
	$record_data['oversight_authority'] = array();
	foreach($rec->oversight_info->authority as $auth) $record_data['oversight_authority'][] = $auth;
	$record_data['primary_outcome_measure'] = array();
	$record_data['primary_outcome_timeframe'] = array();
	$record_data['primary_outcome_safety_issue'] = array();
	foreach ($rec->primary_outcome as $out) 
	{
		if(!isset($out->measure) or empty($out->measure))
			$record_data['primary_outcome_measure'][]=$out;
		if(isset($out->description->textblock) and !empty($out->description->textblock))
			$record_data['primary_outcome_measure'][]=$out->description->textblock;
		if(isset($out->description) and !empty($out->description))
			$record_data['primary_outcome_measure'][]=$out->description;
        $record_data['primary_outcome_measure'][] = $out->measure;
        $record_data['primary_outcome_timeframe'][] = $out->time_frame;
        $record_data['primary_outcome_safety_issue'][] = ynbool($out->safety_issue);
    }

	$record_data['secondary_outcome_measure'] = array();
	$record_data['secondary_outcome_timeframe'] = array();
	$record_data['secondary_outcome_safety_issue'] = array();

	foreach ($rec->secondary_outcome as $out) {
	
		if(isset($out->measure))    
		{
			$record_data['secondary_outcome_measure'][] = $out->measure;
		}
        else $record_data['secondary_outcome_measure'][] = $out;
		$record_data['secondary_outcome_timeframe'][] = $out->time_frame;
		$record_data['secondary_outcome_safety_issue'][] = ynbool($out->safety_issue);
    }
	
	$record_data['condition'] = array();
	foreach($rec->condition as $con) $record_data['condition'][] = $con;	
	$record_data['arm_group_label'] = array();
	$record_data['arm_group_type'] = array();
	$record_data['arm_group_description'] = array();
	foreach($rec->arm_group as $ag)
	{
		$record_data['arm_group_label'][] = $ag->arm_group_label;
		$record_data['arm_group_type'][] = $ag->arm_group_type;
		$record_data['arm_group_description'][] = $ag->description;
	}
	$record_data['intervention_type'] = array();
	$record_data['intervention_name'] = array();
	$record_data['intervention_other_name'] = array();
	$record_data['intervention_description'] = array();
	foreach($rec->intervention as $inter)
	{
		$record_data['intervention_name'][] = $inter->intervention_name;
		$record_data['intervention_description'][] = $inter->description;
		$record_data['intervention_type'][] = $inter->intervention_type;
		foreach($inter->arm_group_label as $agl) $record_data['arm_group_label'][] = $agl;
		foreach($inter->other_name as $oname) $record_data['intervention_name'][] = $oname;
	}
	$record_data['overall_official_name'] = array();
	$record_data['overall_official_degrees'] = array();
	$record_data['overall_official_role'] = array();
	$record_data['overall_official_affiliation'] = array();
	foreach($rec->overall_official as $oa)
	{
		$record_data['overall_official_name'][] = assemble(' ', array($oa->first_name, $oa->middle_name, $oa->last_name));
		$record_data['overall_official_degrees'][] = $oa->degrees;
		$record_data['overall_official_affiliation'][] = $oa->affiliation;
		$record_data['overall_official_role'][] = $oa->role;
	}
	$record_data['location_name'] = array();
	$record_data['location_city'] = array();
	$record_data['location_state'] = array();
	$record_data['location_zip'] = array();
	$record_data['location_country'] = array();
	$record_data['location_status'] = array();
	$record_data['location_contact_name'] = array();
	$record_data['location_contact_degrees'] = array();
	$record_data['location_contact_phone'] = array();
	$record_data['location_contact_phone_ext'] = array();
	$record_data['location_contact_email'] = array();
	$record_data['location_backup_name'] = array();
	$record_data['location_backup_degrees'] = array();
	$record_data['location_backup_phone'] = array();
	$record_data['location_backup_phone_ext'] = array();
	$record_data['location_backup_email'] = array();
	$record_data['investigator_name'] = array();
	$record_data['investigator_degrees'] = array();
	$record_data['investigator_role'] = array();
	foreach($rec->location as $loc)
	{
		$record_data['location_name'][] = $loc->facility->name;
		$record_data['location_city'][] = $loc->facility->address->city;
		$record_data['location_state'][] = $loc->facility->address->state;
		$record_data['location_zip'][] = $loc->facility->address->zip;
		$record_data['location_country'][] = $loc->facility->address->country;
		$record_data['location_status'][] = $loc->status;
		$record_data['location_contact_name'][] = assemble(' ', array($loc->contact->first_name,
																	  $loc->contact->middle_name,
																	  $loc->contact->last_name));
		$record_data['location_contact_degrees'][] = $loc->contact->degrees;
		$record_data['location_contact_phone'][] = $loc->contact->phone;
		$record_data['location_contact_phone_ext'][] = $loc->contact->phone_ext;
		$record_data['location_contact_email'][] = $loc->contact->email;
		$record_data['location_backup_name'][] = assemble(' ', array($loc->contact_backup->first_name,
																	  $loc->contact_backup->middle_name,
																	  $loc->contact_backup->last_name));
		$record_data['location_backup_degrees'][] = $loc->contact_backup->degrees;
		$record_data['location_backup_phone'][] = $loc->contact_backup->phone;
		$record_data['location_backup_phone_ext'][] = $loc->contact_backup->phone_ext;
		$record_data['location_backup_email'][] = $loc->contact_backup->email;
		foreach($loc->investigator as $inv)
		{
			$record_data['investigator_name'][] = assemble(' ', array($inv->first_name, $inv->middle_name, $inv->last_name));
			$record_data['investigator_degrees'][] = $inv->degrees;
			$record_data['investigator_role'][] = $inv->role;
		}
	}	
	$record_data['link_url'] = array();
	$record_data['link_description'] = array();
	foreach($rec->{'link'} as $lnk)
	{
		$record_data['link_url'][] = $lnk->url;
		$record_data['link_description'][] = $lnk->description;
	}
	$record_data['reference_citation'] = array();
	$record_data['reference_PMID'] = array();
	foreach($rec->reference as $ref)
	{
		$record_data['reference_citation'][] = $ref->citation;
		$record_data['reference_PMID'][] = $ref->PMID;
	}
	$record_data['results_reference_citation'] = array();
	$record_data['results_reference_PMID'] = array();
	foreach($rec->results_reference as $ref)
	{
		$record_data['results_reference_citation'][] = $ref->citation;
		$record_data['results_reference_PMID'][] = $ref->PMID;
	}
	
	/***** TKV
	****** Detect and pick all irregular phases that exist in one or several of the various title or description fields */
	global $array1,$array2;
	$phases_regex='/phase4|phase2\/3|phase 2a\/2b|phase 1\/2|Phase l\/Phase ll|phase 1b\/2a|phase 1a\/1b|Phase 1a\/b|phase 3b\/4|Phase I\/III|Phase I\/II|Phase2b\/3|phase 1b\/2|phase 2a\/b|phase 1a|phase 1b|Phase 1C|Phase III(?![a-z.-\\/])|phase II(?![a-z.-\\/])|Phase I(?![a-z.-\\/])|phase 2a|PHASEII|PHASE iii|phase 2b|phase iib|phase iia|phase 3a|phase 3b|Phase I-II/i';
	preg_match_all($phases_regex, $record_data['brief_title'], $matches);
	$currentPhase=$record_data['phase'];
	$currentPhaseIndex=array_search($record_data['phase'],$array1);


	
	if(!count($matches[0]) >0 )
	{
	preg_match_all($phases_regex, $record_data['official_title'], $matches);
	}

	//commented below code so that it does not look in brief_summary for alternate phase values.
	/*
	if(!count($matches[0]) >0 )
	{
	preg_match_all($phases_regex, $record_data['brief_summary'], $matches);
	}
	*/
	if(count($matches[0]) >0 )
	{

		$v=array_search(ucwords($matches[0][0]),$array1,false);
		
		if($v!==false)
		{
			$record_data['phase']=$array2[$v];
		}
		else
		{
			$cnt=count($matches[0]);

			$record_data['phase']=strtolower($matches[0][0]);
			
			$phval='P'.substr($record_data['phase'],1);
			
			switch ($phval) 
			{
			case 'Phase 1a/b':
				$record_data['phase']='Phase 1a/b';
				break;
			case 'Phase2b/3':
				$record_data['phase']='Phase 2b/3';
				break;
			case 'Phase 1c':
				$record_data['phase']='Phase 1c';
				break;
			case 'Phase i/ii':
				$record_data['phase']='Phase 1/Phase 2';
				break;
			case 'Phase i/iii':
				$record_data['phase']='Phase 2/Phase 3';
				break;
			case 'Phase i/phase ii':
				$record_data['phase']='Phase 1/Phase 2';
				break;
			case 'Phase 1/2':
				$record_data['phase']='Phase 1/Phase 2';
				break;
			case 'Phase 2/3':
				$record_data['phase']='Phase 2/Phase 3';
				break;
			case 'Phase2/3':
				$record_data['phase']='Phase 2/Phase 3';
				break;
			case 'Phase 3/4':
				$record_data['phase']='Phase 3/Phase 4';
				break;
			case 'Phase4':
				$record_data['phase']='Phase 4';
				break;
			case 'Phase iib':
				$record_data['phase']='Phase 2b';
				break;
			case 'Phase 2a/2b':
				$record_data['phase']='Phase 2a/2b';
				break;
			case 'Phase 1b/2a':
				$record_data['phase']='Phase 1b/2a';
				break;
			case 'Phase 1a/1b':
				$record_data['phase']='Phase 1a/1b';
				break;
			case 'Phase 3b/4':
				$record_data['phase']='Phase 3b/4';
				break;
			case 'Phase 1b/2':
				$record_data['phase']='Phase 1b/2';
				break;
			case 'Phase 2a/b':
				$record_data['phase']='Phase 2a/b';
				break;
			case 'Phase 1a':
				$record_data['phase']='Phase 1a';
				break;
			case 'Phase 1b':
				$record_data['phase']='Phase 1b';
				break;
			case 'Phase iii':
				$record_data['phase']='Phase 3';
				break;
			case 'Phase ii':
				$record_data['phase']='Phase 2';
				break;
			case 'Phase i':
				$record_data['phase']='Phase 1';
				break;
			case 'Phase 2a':
				$record_data['phase']='Phase 2a';
				break;
			case 'Phase 3a':
				$record_data['phase']='Phase 3a';
				break;
			case 'Phase iia':
				$record_data['phase']='Phase 2a';
				break;
			case 'Phase 2b':
				$record_data['phase']='Phase 2b';
				break;
			case 'Phase 3b':
				$record_data['phase']='Phase 3b';
				break;
			case 'Phase iib':
				$record_data['phase']='Phase 2b';
				break;
			case 'Phaseii':
				$record_data['phase']='Phase 2';
				break;
			case 'Phase i-ii':
				$record_data['phase']='Phase 1/Phase 2';
				break;
				
				
			}
		}
	}

	$newPhaseIndex=array_search($record_data['phase'],$array1);

	if( isset($newPhaseIndex) and isset($currentPhaseIndex) and ($currentPhaseIndex > $newPhaseIndex) )
		$record_data['phase'] = $currentPhase;
	
	
	//****
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
	}
	if(isset($c_date) and !is_null($c_date)) $end_date=$c_date;
	else $end_date=$pc_date;
	foreach($record_data as $fieldname => $value)
	{
		if(!addval($larvol_id, $fieldname, $value,$record_data['lastchanged_date'],$oldtrial,NULL,$end_date,$rec->id_info->nct_id))
			logDataErr('<br>Could not save the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog
	}		

	
	
	
	/* institution type */
		$qitp = 'SELECT institution_type FROM data_manual where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		if(!$ritp = mysql_query($qitp))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$ritp = mysql_fetch_assoc($ritp);
		if($ritp['institution_type'])
		{
			//store the value from data_manual
			$ins_type	=	$ritp['institution_type'];
		}
		else	
		{	$ins_type	=	getInstitutionType($record_data['collaborator'],$record_data['lead_sponsor'],$larvol_id);	}
		

		/*************/		
	
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
/*	
	global $fieldIDArr,$fieldITArr,$fieldRArr;
	//Calculate Inactive Dates
	refreshInactiveDates($larvol_id, 'search',$fieldIDArr);		
    //Determine institution type
	refreshInstitutionType($larvol_id,'search',$fieldITArr);	
	//Calculate regions
	refreshRegions($larvol_id,'search',$fieldRArr);
	//Calculate inclusion and exclusion criteria
	refreshCriteria($larvol_id,'search',$fieldCRITArr);
*/	
		// update sphinx index
		if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
		{
			global $sphinx;
			update_sphinx_index($larvol_id);
		}

	
	return true;
}

function addval($larvol_id, $fieldname, $value,$lastchanged_date,$oldtrial,$ins_type,$end_date,$sourceid=null)
{
	global $newtrial;
	$nullvalue='NO';
	if(	$fieldname=='enrollment' and(is_null($value) or empty($value) or $value=='') )	
	{
		$value = null;
		$nullvalue='YES';
//		return true;
	}

	$lastchanged_date = normal('date',$lastchanged_date);
	global $now;
	global $logger;
	$DTnow = date('Y-m-d H:i:s',$now);

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
	
	global $array1,$array2;
	
	if($fieldname=="phase") 
	{
		$v=array_search($value,$array1,false);
		if($v!==false)
		{
			$value=$array2[$v];
			$raw_value=$array1[$v];
		}
	}
	else $raw_value=$value;
	$value=mysql_real_escape_string($value);
	$raw_value=mysql_real_escape_string($raw_value);
	
	$dn_array=array
	(
	'dummy', 'larvol_id', 'nct_id', 'download_date', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'lead_sponsor_class', 'collaborator', 'collaborator_class', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'completion_date', 'completion_date_type', 'primary_completion_date', 'primary_completion_date_type', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'contact_name', 'contact_phone', 'contact_phone_ext', 'contact_email', 'backup_name', 'backup_phone', 'backup_phone_ext', 'backup_email', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'nct_alias', 'condition', 'secondary_id', 'oversight_authority', 'rank', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'link_url', 'link_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'reference_citation', 'reference_PMID', 'results_reference_citation', 'results_reference_PMID', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_status', 'location_contact_name', 'location_contact_phone', 'location_contact_phone_ext', 'location_contact_email', 'location_backup_name', 'location_backup_phone', 'location_backup_phone_ext', 'location_backup_email', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
	);
	$as=array_search($fieldname,$dn_array);
	
	if ( isset($as) and $as)
	{
	
						
		$query = 'SELECT `' .$fieldname. '`  FROM data_nct WHERE `larvol_id`="'. $larvol_id . '" limit 1';
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$row = mysql_fetch_assoc($res);
		
		$change = ($row[$fieldname]===null and $value !== null) or ($value != $row[$fieldname]);
		
		if(!mysql_query('BEGIN'))
		{
			$log='Could not begin transaction.   SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		if(1)
		{
				
			$dn_array=array
			(
			'dummy', 'larvol_id', 'nct_id', 'download_date', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'lead_sponsor_class', 'collaborator', 'collaborator_class', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'completion_date', 'completion_date_type', 'primary_completion_date', 'primary_completion_date_type', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'contact_name', 'contact_phone', 'contact_phone_ext', 'contact_email', 'backup_name', 'backup_phone', 'backup_phone_ext', 'backup_email', 'verification_date', 'lastchanged_date', 'firstreceived_date',  'org_study_id', 'phase', 'nct_alias', 'condition', 'secondary_id', 'rank', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_description', 'link_url', 'link_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'reference_citation', 'reference_PMID', 'results_reference_citation', 'results_reference_PMID', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_contact_name', 'location_contact_phone', 'location_contact_phone_ext', 'location_contact_email', 'location_backup_name', 'location_backup_phone', 'location_backup_phone_ext', 'location_backup_email',  'keyword', 'is_fda_regulated', 'is_section_801'
			);
			$as=array_search($fieldname,$dn_array);
			
			if ( isset($as) and $as)
			{
				if($fieldname=='end_date')
				{
					$query = 'SELECT `' .$fieldname. '`, `lastchanged_date`  FROM data_trials WHERE `larvol_id`="'. $larvol_id . '" limit 1';
				}
				else
					$query = 'SELECT `' .$fieldname. '`, `lastchanged_date`  FROM data_nct WHERE `larvol_id`="'. $larvol_id . '" limit 1';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					mysql_query('ROLLBACK');
					echo $log;
					return false;
				}
				$row = mysql_fetch_assoc($res);
				
				//always take the lastchanged_date from data_trials/data_nct to store in data_history
				$olddate=$row['lastchanged_date'];
				$oldval=$row[$fieldname];
	
				//check if the data is manually overridden
				$dn_array1=array
				(
					'dummy', 'larvol_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
				);
				$as1=array_search($fieldname,$dn_array1);
				if ( isset($as1) and $as1)
				{
					$query = 'SELECT `' .$fieldname. '` FROM data_manual WHERE `larvol_id`="'. $larvol_id . '" and `' .$fieldname. '` is not null limit 1';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}

					$row = mysql_fetch_assoc($res);
					$overridden = $row !== false;
					if($overridden and !empty($row[$fieldname]))
						$value=mysql_real_escape_string($row[$fieldname]);
				}				
				//
				if($nullvalue=='YES')
				{
					$raw_value=null;
					$value=null;
				}
				$query = 'update `data_nct` set `' . $fieldname . '` = "' . $raw_value .'", `lastchanged_date` = "' .$lastchanged_date.'" where `larvol_id`="' .$larvol_id . '"  limit 1'  ;
				
				if(!mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					mysql_query('ROLLBACK');
					echo $log;
					return false;
				}

				$dt_array=array
				(
				'dummy', 'larvol_id', 'source_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type',  'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name',  'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
				);
				$as=array_search($fieldname,$dt_array);
				
				if ( isset($as) and $as)
				{
					if(is_null($value)) $query = 'update data_trials set `' . $fieldname . '` = null , lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					else $query = 'update data_trials set `' . $fieldname . '` = "' . $value .'", lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					if($fieldname=='end_date')
					{ 
						$query = 'update data_trials set `' . $fieldname . '` = "' . $end_date .'", lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					}
					
					if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					
					// update sphinx index
					if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
					{
						global $sphinx;
						update_sphinx_index($larvol_id);
					}

					
					// validation of first received date.  
					// if first recieved date in archived version is older than that of the regular version, 
					// then store the archived version's date, else store regular version's date.
					if($fieldname=='firstreceived_date' and !is_null($sourceid) and $newtrial=='YES') 
					{
						$url = "http://clinicaltrials.gov/archive/" .$sourceid;
						$doc = new DOMDocument();

						for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
							$done = $doc->loadHTMLFile($url);
						}

						$ths = $doc->getElementsByTagName('th');
						foreach ($ths as $th) {
							foreach ($th->attributes as $attr) {
								if ($attr->name == 'scope' && $attr->value == 'row') {
										$archive_fdate = $th->nodeValue; // date
										echo "<br/>";
										break 2;
								   
								}
							}
						}
						
						
						$archive_fdate=normal('date',str_replace("_","-",$archive_fdate));
						if($archive_fdate>$value) // if it is older than regular date, then insert in data manual.
						{
							$query = 'SELECT `larvol_id` FROM data_manual 
							WHERE `larvol_id`="'. $larvol_id . '" limit 1';
							if(!$res = mysql_query($query))
							{
								$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
								$logger->error($log);
								echo $log;
								return false;
							}

							$row = mysql_fetch_assoc($res);
							$overridden = $row !== false;
							if($overridden)
							{
								$query = '	update data_manual 
											set `firstreceived_date` = "'.$archive_fdate.'"
											WHERE `larvol_id`="'. $larvol_id . '"  limit 1';
								if(!$res = mysql_query($query))
								{
									$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
									$logger->error($log);
									echo $log;
									return false;
								}
								$query = '	update data_trials 
											set `firstreceived_date` = "'.$archive_fdate.'"
											WHERE `larvol_id`="'. $larvol_id . '"  limit 1';
								if(!$res = mysql_query($query))
								{
									$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
									$logger->error($log);
									echo $log;
									return false;
								}
								// record updated, now update sphinx index too.
								if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
								{
									global $sphinx;
									update_sphinx_index($larvol_id);
								}
								
							}
							else
							{
							$query = '	insert into data_manual 
											set `firstreceived_date` = "'.$archive_fdate.'",
											`larvol_id`="'. $larvol_id . '" ';
								if(!$res = mysql_query($query))
								{
									$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
									$logger->error($log);
									echo $log;
									return false;
								}
							$query = '	update data_trials 
											set `firstreceived_date` = "'.$archive_fdate.'"
											WHERE `larvol_id`="'. $larvol_id . '"  limit 1';
								if(!$res = mysql_query($query))
								{
									$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
									$logger->error($log);
									echo $log;
									return false;
								}
								// update sphinx index
								if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
								{
									global $sphinx;
									update_sphinx_index($larvol_id);
								}

							}
						}
					
					
					}
					
					
					$query = 'SELECT `larvol_id` FROM data_history where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					$res = mysql_fetch_assoc($res);
					$exists = $res !== false;
					$oldval=mysql_real_escape_string($oldval);
					
					global $array1,$array2;

					if (trim($fieldname)=='phase')
					{
						$v=array_search($oldval,$array1,false);
						if($v!==false)
						{
							$oldval=$array2[$v];
						}
					}
					
					if($fieldname=='end_date') 
					{
						$value=$end_date;
					}
					
					$val1=str_replace("\\", "", $oldval);
					$val2=str_replace("\\", "", $value);
					$cond1=( $exists and trim($val1)<>trim($val2) and $oldval<>'0000-00-00' and !empty($oldval) );
					
					
					if ($fieldname=='phase' and ( is_null($oldval) or strlen(trim($oldval)) ==0 or empty($oldval)) )
						$cond2=false; else $cond2=true;
		
					if($cond1 and $cond2 and $nullvalue=='NO')
					{
						$query = 'update data_history set `' . $fieldname . '_prev` = "' . $oldval .'", `' . $fieldname . '_lastchanged` = "' . $olddate .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
						if(!mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							mysql_query('ROLLBACK');
							echo $log;
							return false;
						}
						
					}
					else
					{
						if(  $oldtrial  and $nullvalue=='NO' )
						{
							$val1=str_replace("\\", "", $oldval);
							$val2=str_replace("\\", "", $value);
							$cond1=( trim($val1)<>trim($val2) and $oldval<>'0000-00-00' and !empty($oldval) );
							if ($fieldname=='phase' and ( is_null($oldval) or strlen(trim($oldval)) ==0 or empty($oldval)) )
								$cond2=false; else $cond2=true;
							if($cond1 and $cond2)
							{
								$query = 'insert into `data_history` set `' . $fieldname . '_prev` = "' . mysql_real_escape_string($oldval) .'", `' . $fieldname . '_lastchanged` = "' . $olddate .'" , `larvol_id`="' .$larvol_id . '" ' ;
								if(!mysql_query($query))
								{
									$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
									$logger->error($log);
									mysql_query('ROLLBACK');
									echo $log;
									return false;
								}
							}
						}
					}
				
				
				}
								
			}
			
			
		}
	
		if(!mysql_query('COMMIT'))
					{
						$log='Could not commit transaction. Rolling back transaction...   SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
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
			$query = 'update `data_trials` set `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'", end_date = "' . $cdate . '"  where `larvol_id`="' .$larvol_id . '" limit 1' ;
			
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
			$query = 'update `data_trials` set  `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" , end_date = "' . $pcdate . '" where `larvol_id`="' .$larvol_id . '" limit 1' ;
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
				$query = 'update `data_trials` set `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'", end_date = "' . $cdate . '"  where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = "' . $cdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
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
		
		// update sphinx index
		if(isset($larvol_id) and !empty($larvol_id) and $larvol_id>0)
		{
			global $sphinx;
			update_sphinx_index($larvol_id);
		}

						
	return true;
	}
}

//Process/escape/quote/etc a value straight from raw XML to a form that can go into the insert statement.
function esc($type, $value)
{
	if(!strlen($value) || $value === NULL) return 'NULL';
	switch($type)
	{
		case 'varchar':
		case 'text':
		return '"' . mysql_real_escape_string($value) . '"';
		
		case 'date':
		return '"' . $value . '"';
		
		case 'enum':	//at this point an enum should be an id into data_enumvals and not the value string
		case 'int':
		case 'bool':
		return $value;
	}
	
}

//Some data need to be changed a little to fit in the database
function normalize($type, $value)
{
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

// To avoid out of index errors for eud.
function get_key($arr, $key) {
    /*    if ($key == "Country")
      {
      echo "Country: " . $arr[$key];
      }
     */
    return isset($arr[$key]) ? $arr[$key] : null;
}

?>