<?php
require_once('db.php');
require_once ('include.derived.php');

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

//calculate field Ids and store in an array since it requires db call
//prefetch recurring derived fields' calculation data.


//Adds a new record of any recognized type from a simpleXML object.
//Autodetects the type if none is specified.
function addRecord($in, $type='unspec')
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
		return addEudraCT($in);
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
	if($rec === false) return false;
	
	$DTnow = date('Y-m-d H:i:s',$now);
	$nct_id = unpadnct($rec->id_info->nct_id);

	
	
	$query = 'SELECT `larvol_id` FROM data_trials where `source_id`="' . $rec->id_info->nct_id . '"  LIMIT 1';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query determining existence of record');
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
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction to create record from XML");
		$query = 'INSERT INTO data_trials SET `source_id`="' . $rec->id_info->nct_id . '"' ;
		if(mysql_query($query) === false) return softDie('Bad SQL query adding new record');
		$larvol_id = mysql_insert_id();
		$query = 'INSERT INTO data_nct SET `larvol_id`=' . $larvol_id . ',nct_id="' . $nct_id .'"';
		if(mysql_query($query) === false) return softDie('Bad SQL query adding nct_id');
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction to create record from XML");
	}
	
	//echo '<pre>'; print_r($rec); echo '</pre>';
	
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
		foreach($inter->other_name as $oname) $record_data['intervention_other_name'][] = $oname;
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
	$phases_regex='/phase4|phase2\/3|phase 2a\/2b|phase 1\/2|Phase l\/Phase ll|phase 1b\/2a|phase 1a\/1b|Phase 1a\/b|phase 3b\/4|Phase I\/II|Phase2b\/3|phase 1b\/2|phase 2a\/b|phase 1a|phase 1b|Phase 1C|Phase III(?![a-z.-\\/])|phase II(?![a-z.-\\/])|Phase I(?![a-z.-\\/])|phase 2a|PHASEII|PHASE iii|phase 2b|phase iib|phase iia|phase 3a|phase 3b/i';
	preg_match_all($phases_regex, $record_data['brief_title'], $matches);

	if(!count($matches[0]) >0 )
	{
	preg_match_all($phases_regex, $record_data['official_title'], $matches);
	}

		 // pr($record_data);
	//pr($matches);

	if(!count($matches[0]) >0 )
	{
	preg_match_all($phases_regex, $record_data['brief_summary'], $matches);
	}

	if(count($matches[0]) >0 )
	{

		$cnt=count($matches[0]);

		$record_data['phase']=ucwords($matches[0][0]);
		
		switch ($record_data['phase']) 
		{
		case 'Phase 1a/b':
			$record_data['phase']='Phase 1a/b';
			break;
		case 'Phase2b/3':
			$record_data['phase']='Phase 2b/3';
			break;
		case 'Phase 1C':
			$record_data['phase']='Phase 1c';
			break;
		case 'Phase I/II':
			$record_data['phase']='Phase 1/Phase 2';
			break;
		case 'Phase l/Phase ll':
			$record_data['phase']='Phase 1/Phase 2';
			break;
		case 'phase 1/2':
			$record_data['phase']='Phase 1/Phase 2';
			break;
		case 'phase 2/3':
			$record_data['phase']='Phase 2/Phase 3';
			break;
		case 'phase2/3':
			$record_data['phase']='Phase 2/Phase 3';
			break;
		case 'phase 3/4':
			$record_data['phase']='Phase 3/Phase 4';
			break;
		case 'phase4':
			$record_data['phase']='Phase 4';
			break;
		case 'Phase iib' or 'Phase iib':
			$record_data['phase']='Phase 2b';
			break;
		}
		
		
	}

	//****

	

	foreach($record_data as $fieldname => $value)
		if(!addval($larvol_id, $fieldname, $value,$record_data['lastchanged_date'],$oldtrial))
			logDataErr('<br>Could not save the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog
			
			
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
	if(mysql_query($query) === false) return softDie('Bad SQL query saving institution type in data_trials. query:'.$query.'<br>');

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
	return true;
}

function addval($larvol_id, $fieldname, $value,$lastchanged_date,$oldtrial,$ins_type)
{
	
	$lastchanged_date = normal('date',$lastchanged_date);
	global $now;
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
		$res = mysql_query($query);
		
		if($res === false) return softDie('Bad SQL query getting value');
		$row = mysql_fetch_assoc($res);
		
		$change = ($row[$fieldname]===null and $value !== null) or ($value != $row[$fieldname]);
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction to update record from XML");
		if(1)
		{
				
			$dn_array=array
			(
			'dummy', 'larvol_id', 'nct_id', 'download_date', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'lead_sponsor_class', 'collaborator', 'collaborator_class', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'completion_date', 'completion_date_type', 'primary_completion_date', 'primary_completion_date_type', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'contact_name', 'contact_phone', 'contact_phone_ext', 'contact_email', 'backup_name', 'backup_phone', 'backup_phone_ext', 'backup_email', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'nct_alias', 'condition', 'secondary_id', 'oversight_authority', 'rank', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'link_url', 'link_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'reference_citation', 'reference_PMID', 'results_reference_citation', 'results_reference_PMID', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_status', 'location_contact_name', 'location_contact_phone', 'location_contact_phone_ext', 'location_contact_email', 'location_backup_name', 'location_backup_phone', 'location_backup_phone_ext', 'location_backup_email', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
			);
			$as=array_search($fieldname,$dn_array);
			
			if ( isset($as) and $as)
			{
				$query = 'SELECT `' .$fieldname. '`, `lastchanged_date`  FROM data_nct WHERE `larvol_id`="'. $larvol_id . '" limit 1';
				$res = mysql_query($query);
				if($res === false) return softDie('Bad SQL query getting value');
				$row = mysql_fetch_assoc($res);
				$olddate=$row['lastchanged_date'];
				$oldval=$row[$fieldname];
				$value=mysql_real_escape_string($value);
				
				//check if the data is manually overridden
				$dn_array1=array
				(
					'dummy', 'larvol_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'condition', 'secondary_id', 'oversight_authority', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'location_status', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
				);
				$as1=array_search($fieldname,$dn_array1);
				if ( isset($as1) and $as1)
				{
					$query = 'SELECT `' .$fieldname. '` FROM data_manual WHERE `larvol_id`="'. $larvol_id . '" and `' .$fieldname. '` is not null limit 1';
					$res = mysql_query($query);
					if($res === false) return softDie('Bad SQL query checking data in data_manual. Query:'.$query);
					$row = mysql_fetch_assoc($res);
					$overridden = $row !== false;
					if($overridden and !empty($row[$fieldname]))
						$value=mysql_real_escape_string($row[$fieldname]);
				}				
				//
				
				$query = 'update `data_nct` set `' . $fieldname . '` = "' . $raw_value .'", `lastchanged_date` = "' .$lastchanged_date.'" where `larvol_id`="' .$larvol_id . '"  limit 1'  ;
				
				if(mysql_query($query) === false) return softDie('Bad SQL query saving value in datanct. query:'.$query.'<br>');
				
				$dt_array=array
				(
				'dummy', 'larvol_id', 'source_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'condition', 'secondary_id', 'oversight_authority', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'location_status', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
				);
				$as=array_search($fieldname,$dt_array);
				
				if ( isset($as) and $as)
				{
					$query = 'update data_trials set `' . $fieldname . '` = "' . $value .'", lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					
					if(mysql_query($query) === false) return softDie('Bad SQL query saving value in data_trials. query:'.$query.'<br>');

					$query = 'SELECT `larvol_id` FROM data_history where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
					$res = mysql_query($query);
					if($res === false) return softDie('Bad SQL query determining existence of record in data history');
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
					
					$val1=str_replace("\\", "", $oldval);
					$val2=str_replace("\\", "", $value);
					$cond1=( $exists and trim($val1)<>trim($val2) and $oldval<>'0000-00-00' and !empty($oldval) );
					
					
					if ($fieldname=='phase' and ( is_null($oldval) or strlen(trim($oldval)) ==0 or empty($oldval)) )
						$cond2=false; else $cond2=true;
			
		
					if($cond1 and $cond2)
					{
						$query = 'update data_history set `' . $fieldname . '_prev` = "' . $oldval .'", `' . $fieldname . '_lastchanged` = "' . $olddate .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
						if(mysql_query($query) === false) return softDie('Bad SQL query saving value in data_history. query:'.$query.'<br>');
					}
					else
					{
						if(  $oldtrial )
						{
							$val1=str_replace("\\", "", $oldval);
							$val2=str_replace("\\", "", $value);
							$cond1=( trim($val1)<>trim($val2) and $oldval<>'0000-00-00' and !empty($oldval) );
							if ($fieldname=='phase' and ( is_null($oldval) or strlen(trim($oldval)) ==0 or empty($oldval)) )
								$cond2=false; else $cond2=true;
							if($cond1 and $cond2)
							{
								$query = 'insert into `data_history` set `' . $fieldname . '_prev` = "' . mysql_real_escape_string($oldval) .'", `' . $fieldname . '_lastchanged` = "' . $olddate .'" , `larvol_id`="' .$larvol_id . '" ' ;
								if(mysql_query($query) === false) return softDie('Bad SQL query saving value  Query='.$query);
							}
						}
					}
				
				
				}
								
			}
			
			
		}
		mysql_query('COMMIT') or die("Couldn't COMMIT SQL transaction to update record from XML");
		
		
		$query = 'select `completion_date`,`primary_completion_date`,`criteria` from `data_nct` where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		$res = mysql_query($query);
		if($res === false) return softDie('Bad SQL query determining existence of record in data nct');
		$res = mysql_fetch_assoc($res);
		$exists = $res !== false;
				
		$cdate=$res['completion_date'];
		$pcdate=$res['primary_completion_date'];
		$str=$res['criteria'];
		$str=criteria_process($str);
		$str['inclusion']=mysql_real_escape_string($str['inclusion']);
		$str['exclusion']=mysql_real_escape_string($str['exclusion']);
			
		if( !is_null($cdate) and  $cdate <>'0000-00-00')	// completion date
		{
			$cdate=normalize('date',$cdate);
			$query = 'update `data_trials` set `end_date` = "' . $cdate . '", `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//			$query = 'update data_trials set end_date = "' . $cdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
			if(mysql_query($query) === false) return softDie('Bad SQL query updating end date  in data_trials. query:'.$query.'<br>');
		}
		elseif( !is_null($pcdate) and  $pcdate <>'0000-00-00') 	// primary completion date
		{
			$pcdate=normalize('date',$pcdate);
			$query = 'update `data_trials` set `end_date` = "' . $pcdate . '", `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//			$query = 'update data_trials set end_date = "' . $pcdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
			if(mysql_query($query) === false) return softDie('Bad SQL query updating end date  in data_trials. query:'.$query.'<br>');
		}
		
		
		else	
		{
		
			$query = 'select `is_active`, `lastchanged_date` from `data_trials` where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
			$res = mysql_query($query);

			if($res === false) return softDie('Bad SQL query determining existence of record in data trials');
			$res = mysql_fetch_assoc($res);
			$cdate=$res['lastchanged_date'];
			$is_active=$res['is_active'];
			global $ins_type;
			if( !is_null($cdate) and  $cdate <>'0000-00-00' and !is_null($is_active) and $is_active<>1) // last changed date
			{
				$cdate=normalize('date',$cdate);
				$query = 'update `data_trials` set `end_date` = "' . $cdate . '", `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = "' . $cdate . '" where larvol_id="' .$larvol_id . '"  limit 1' ;
				if(mysql_query($query) === false) return softDie('Bad SQL query updating end date  in data_trials. query:'.$query.'<br>');
			}
			else	// replace with null
			{
				$query = 'update `data_trials` set `end_date` = null, `inclusion_criteria` = "'. $str['inclusion'] . '", `exclusion_criteria` = "'. $str['exclusion'] .'" where `larvol_id`="' .$larvol_id . '" limit 1' ;
//				$query = 'update data_trials set end_date = null where larvol_id="' .$larvol_id . '"  limit 1' ;
				if(mysql_query($query) === false) return softDie('Bad SQL query updating end date  in data_trials. query:'.$query.'<br>');
			}
			
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