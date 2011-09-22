<?php

error_reporting(E_ERROR);
$tab = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

$mapping = array(
    'study_id-org_name' => 'study_id_org_name', //new
    'study_id-org_full_name' => 'study_id_org_full_name', //new
    'study_id-org_study_id' => 'org_study_id', //new
//    'study_id-nct_id' => 'nct_id',
    'is_fda_regulated' => 'is_fda_regulated', //new
    'is_section_801' => 'is_section_801', //new
    'delayed_posting' => 'delayed_posting', //new
    'brief_title-textblock' => 'brief_title',
    'official_title-textblock' => 'official_title',
    'study_sponsor-sponsor-agency' => 'collaborator',  //added collaborator
    'study_sponsor-lead_sponsor-agency' => 'lead_sponsor',
    'resp_party-name_title' => 'responsible_party_name_title',
    'resp_party-organization' => 'responsible_party_organization',
    'oversight_info-regulatory_authority' => 'oversight_authority',
    'oversight_info-has_dmc' => 'has_dmc',
    'brief_summary-textblock' => 'brief_summary',
    'detailed_descr-textblock' => 'detailed_description',
    'status_block-status' => 'overall_status',
    'status_block-date' => 'verification_date',
    'start_date-date' => 'start_date',
    'end_date-date' => 'end_date',
    'last_follow_up_date' => 'completion_date_type',
    'last_follow_up_date-date' => 'completion_date', // check
    'primary_compl_date_type' => 'primary_completion_date_type',
    'primary_compl_date-date' => 'primary_completion_date', // check
    'phase_block-phase' => 'phase',
    'study_type' => 'study_type',
    'design' => 'study_design',
    'number_of_arms' => 'number_of_arms',
    'primary_outcome-measure' => 'primary_outcome_measure',
    'primary_outcome-time_frame' => 'primary_outcome_timeframe',
    'primary_outcome-safety_issue' => 'primary_outcome_safety_issue',
    'secondary_outcome-measure' => 'secondary_outcome_measure',
    'secondary_outcome-time_frame' => 'secondary_outcome_timeframe',
    'secondary_outcome-safety_issue' => 'secondary_outcome_safety_issue',
    'measure-time_frame' => 'primary_outcome_timeframe',
    'measure-safety_issue' => 'primary_outcome_safety_issue',
    'enrollment' => 'enrollment',
    'enrollment_type' => 'enrollment_type',
    'condition' => 'condition',
    'arm_group-arm_group_label' => 'arm_group_label',
    'arm_group-other_name' => 'arm_group_other_name', //new
    'arm_group-arm_type' => 'arm_group_type',
    'arm_group-description-textblock' => 'arm_group_description',
    'intervention-intervent_type' => 'intervention_type',
    'intervention-primary_name' => 'intervention_name',
    'intervention-description-textblock' => 'intervention_description',
    'eligibility-criteria-textblock' => 'criteria',
    'eligibility-healthy_volunteers' => 'healthy_volunteers',
    'eligibility-gender' => 'gender',
    'eligibility-minimum_age' => 'minimum_age',
    'eligibility-maximum_age' => 'maximum_age',
    'investigator-role' => 'investigator_role',
    'investigator-name' => 'investigator_name',
    'investigator-affiliation-agency' => 'investigator_degrees',  //chg  
    'contact-name' => 'contact_name',
    'contact-phone' => 'contact_phone',
    'contact-phone_ext' => 'contact_phone_ext',
    'contact-email' => 'contact_email',
    'location-facility-name' => 'location_name',
    'location-facility-address-city' => 'location_city',
    'location-facility-address-state' => 'location_state',
    'location-facility-address-zip' => 'location_zip',
    'location-facility-address-country' => 'location_country',
    'location-status' => 'location_status',
    'location-contact-name' => 'location_contact_name',
    'location-contact-phone' => 'location_contact_phone',    
    'location-contact-email' => 'location_contact_email',
    'see_also-annotation-textblock' => 'link_description', //chg 
    'see_also-url' => 'link_url', //chg 
    'keyword' => 'keyword', //new
    'initial_release_date' => 'firstreceived_date',
    'last_release_date' => 'lastchanged_date',
    'init_results_release_date' => 'primary_completion_date',
//  *********************
//  ADDED truncated ones
//  *********************
    'email' => 'contact_email',
    'description-textblock' => 'intervention_description',
    'study_id-secondary_id' => 'secondary_id',
);

$level = array();

//returned array maps the IDs to lastchanged dates
function getIDs($type) {
    global $days;
    $fields = 'kp';
    $dstr = 'lup_d';

    $ids = array();
    for ($page = 1; true; ++$page) {
        $fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
        @mysql_fetch_array($fake);
        //load search page and see if it has results, or if we've reached the end of results for the search
        $url = 'http://clinicaltrials.gov/ct2/results?flds=' . $fields . '&' . $dstr . '=' . $days . '&pg=' . $page;
        $doc = new DOMDocument();
        for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
            echo('.');
            @$done = $doc->loadHTMLFile($url);
        }
        $tables = $doc->getElementsByTagName('table');
        $datatable = NULL;
        foreach ($tables as $table) {
            $right = false;
            foreach ($table->attributes as $attr) {
                if ($attr->name == 'class' && $attr->value == 'data_table') {
                    $right = true;
                    break;
                }
            }
            if ($right == true) {
                $datatable = $table;
                break;
            }
        }
        if ($datatable == NULL) {
            echo('Last page reached.' . "\n<br />");
            break;
        }
        unset($tables);
        //Now that we found the table, go through its TDs to find the ones with NCTIDs
        $tds = $datatable->getElementsByTagName('td');
        $pageids = array();
        $upd = NULL; //only for update mode
        foreach ($tds as $td) {
            $hasid = false;
            foreach ($td->attributes as $attr) {
                if ($attr->name == 'style' && $attr->value == 'padding-left:1em;') {
                    $hasid = true;
                    break;
                }
            }
            if ($hasid) {
                if ($type == 'new') { //In new mode, just record IDs
                    $pageids[mysql_real_escape_string($td->nodeValue)] = 1;
                } else { //In update mode, the results alternate between IDs and update times
                    if ($upd === NULL) {
                        $upd = mysql_real_escape_string($td->nodeValue);
                    } else {
                        $pageids[$upd] = strtotime($td->nodeValue);
                        $upd = NULL;
                    }
                }
            }
        }
        echo('Page ' . $page . ': ' . implode(', ', array_keys($pageids)) . "\n<br />");
        $ids = array_merge($ids, $pageids);
    }
    return $ids;
}

function ProcessNew($id) {

    echo "<hr>Processing new Record " . $id . "<br/>";

    echo('Getting XML for ' . $id . '... - ');
    $xml = file_get_contents('http://www.clinicaltrials.gov/show/' . $id . '?displayxml=true');
    echo('Parsing XML... - ');
    $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR);
    if ($xml === false) {
        echo('Parsing failed for this record.' . "\n<br />");
    } else {
        echo('Importing... - ');
        if (addRecord($xml, 'nct') === false) {
            echo('Import failed for this record.' . "\n<br />");
        } else {
            echo('Record imported.' . "\n<br />");
        }
    }

    echo('Done Processing new items: .' . $id . "\n<hr><br />");
}

function filterNewChanges($ids, &$existing) {
    global $last_id;
    global $id_field;


    return $ids;
}

function validateEnums($val) 
{
	$eval1=$val;
	if ( isset($eval1) and ( (substr($eval1,0,1)=="'") or (substr($eval1,0,1)=='"') )  and ( (substr($eval1,-1)=="'") or (substr($eval1,-1)=='"') )  )
			return substr($eval1,1,-1);
	$enum1 = array('Phase 1'=>"I", 'Phase 2'=>"II", 'Phase 3'=>"III", 'Phase 4'=>"IV", 'N/A'=>"Not Applicable");
	$enum2 = array('Phase 1'=>"1", 'Phase 2'=>"2", 'Phase 3'=>"3", 'Phase 4'=>"4", 'Phase 2/Phase 3'=>"Phase 2-3");
	$enum3 = array('Phase 1'=>"PHASE I", 'Phase 2'=>"PHASE II", 'Phase 3'=>"PHASE III", 'Phase 4'=>"PHASE IV");
	$ev1=array_search($eval1,$enum1,false);
	$ev2=array_search($eval1,$enum2,false);
	$ev3=array_search($eval1,$enum3,false);
	if ( isset($ev1) and $ev1  )
		return $ev1;
	elseif (isset($ev2) and $ev2  )
		return $ev2;
	else
		return $ev3;
}

function ProcessChanges($id, $date, $column, $initial_date=NULL) {
    // Now Go To changes Site and parse differences
    $url = "http://clinicaltrials.gov/archive/" . $id . "/" . $date . "/changes";

    $docpc = new DOMDocument();
    echo $tab . '<hr>Parsing Archive Changes Page for ' . $id . ' and Date: ' . $date . '... - ';

    echo $url . " - ";

    for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
        //echo('.');
        @$done = $docpc->loadHTMLFile($url);
    }


    // Get Div of the section we are interested in so no repeats. Each
    // Page lists differences twice.
    $div = $docpc->getElementsByTagName('div');
    $datatable = NULL;
    foreach ($div as $table) {
        $right = false;
        foreach ($table->attributes as $attr) {
            if ($attr->name == 'id' && $attr->value == 'sdiff-full') {
                $right = true;
                break;
            }
        }
        if ($right == true) {
            $datatable = $table;
            break;
        }
    }
    if ($datatable == NULL) {
        echo('End of page reached.' . "\n<br />");
        echo('Returning Page To Continue.' . "\n<br />");
        return;
    }
    unset($div);

    // Now step through the family of trs combining into most recent xml version
    $trs = $datatable->getElementsByTagName('td');
    $field;

    foreach ($trs as $tr) {
        foreach ($tr->attributes as $attr) {
            if ($attr->name == 'class' && $attr->value == $column) {
                $innerHTML .= $tr->ownerDocument->saveXML($tr);
            }
        }
        $fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
        @mysql_fetch_array($fake);
    }

//    print $innerHTML;

    unset($trs);
    unset($docpc);

    $innerHTML = strip_tags($innerHTML);
    $innerHTML = htmlspecialchars_decode($innerHTML);
    // Replace the Anticipated Types with nada
    //   Watch Might have to put back in
    //   $innerHTML = str_replace("type=\"Anticipated\"", "", $innerHTML);


    $xml = simplexml_load_string($innerHTML);
    if ($xml === false) {
        echo('Parsing failed for this record.' . "\n<br />");
    }

    unset($innerHTML);
    if (isset($initial_date) and !empty($initial_date)) {
		addNCT_history($xml, $id, $initial_date);
      } else {
		addNCT_history($xml, $id, $date);
    }

    echo $tab . 'End Parsing Archive Changes Page for ' . $id . ' and Date: ' . $date . '<hr>';
}

// Add or update a NCT record from a SimpleXML object.
function addNCT_history($rec, $id, $date) {
    global $db;
    global $instMap;
    global $now;
    if ($rec === false)
        return false;

    $DTnow = date('Y-m-d H:i:s', $now);
    //$nct_id = unpadnct($rec->id_info->nct_id);
    $nct_id = unpadnct($id);

    //Find out the ID of the field for nct_id, and the ID of the "NCT" category.
    static $id_field = NULL;
    static $nct_cat = NULL;
    if ($id_field === NULL || $nct_cat === NULL) {
        $query = 'SELECT data_fields.id AS "nct_id",data_categories.id AS "nct_cat" FROM '
                . 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
                . 'WHERE data_fields.name="nct_id" AND data_categories.name="NCT" LIMIT 1';
        $res = mysql_query($query);
        if ($res === false)
            return softDie('Bad SQL query getting field ID of nct_id');
        $res = mysql_fetch_assoc($res);
        if ($res === false)
            return softDie('NCT schema not found!');
        $id_field = $res['nct_id'];
        $nct_cat = $res['nct_cat'];
    }


    mysql_query('SET SESSION wait_timeout=900');

    if (!mysql_query('BEGIN')) {
        echo ("Couldn't begin SQL transaction to create record from XML:" . mysql_error());
        echo "<br/>Reconnecting to DB";
        mysql_close();
        $db = new DatabaseManager();
        // Start Trans again
        mysql_query('BEGIN') or die("Couldn't begin SQL transaction to create record from XML after reconnect:" . mysql_error());
    }

    //Detect if this record already exists (if not, add it, and associate it with the category NCT)
    //Then, get the larvol_id and the ID of the studycat it has for NCT.
    $query = 'SELECT studycat,larvol_id FROM '
            . 'data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
            . 'WHERE field=' . $id_field . ' AND val_int=' . $nct_id . ' LIMIT 1';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query determining existence of record');
    $res = mysql_fetch_assoc($res);
    $exists = $res !== false;
    $studycat = NULL;
    $larvol_id = NULL;
    if ($exists) {
        $studycat = $res['studycat'];
        $larvol_id = $res['larvol_id'];
    } else {
        $query = 'INSERT INTO clinical_study SET import_time="' . date('Y-m-d H:i:s', $now) . '"';
        if (mysql_query($query) === false)
            return softDie('Bad SQL query adding new record');
        $larvol_id = mysql_insert_id();
        $query = 'INSERT INTO data_cats_in_study SET larvol_id=' . $larvol_id . ',category=' . $nct_cat;
        if (mysql_query($query) === false)
            return softDie('Bad SQL query adding new record to category');
        $studycat = mysql_insert_id();
        
        $no_dat=false;
	if( !isset($nct_id) ) $no_dat=true;
	elseif( empty($nct_id) and !is_numeric($nct_id) ) $no_dat=true;        
        
        if($no_dat == false)
        {
            $query = 'INSERT INTO data_values SET field=' . $id_field . ',`added`="' . $DTnow . '",studycat=' . $studycat
                . ',val_int=' . $nct_id;
            if (mysql_query($query) === false)
            return softDie('Bad SQL query adding nct_id');
        }
    }

    //Determine institution type
    $institution_type = 'other';
    $all_sponsors = array();

    if ($rec->study_sponsors->lead_sponsor != null) {
        foreach ($rec->study_sponsors->lead_sponsor as $cola) {
            $all_sponsors[] = "" . $cola->agency;
        }
    }

    $all_sponsors[] = "" . $rec->study_sponsors->lead_sponsor->agency;
    foreach ($all_sponsors as $a_sponsor) {
        if (strlen($a_sponsor) && isset($instMap[$a_sponsor])) {
            $institution_type = $instMap[$a_sponsor];
            if ($institution_type == 'industry')
                break;
        }
    }
    $query = 'UPDATE clinical_study SET institution_type="' . $institution_type . '" WHERE larvol_id=' . $larvol_id . ' LIMIT 1';
    if (mysql_query($query) === false)
        return softDie('Bad SQL query recording institution type');
	
    //Go through the parsed XML structure and pick out the data
    $record_data = array('brief_title' => $rec->brief_title->textblock,
        'official_title' => $rec->official_title->textblock,
        'brief_summary' => $rec->status_block->brief_summary->textblock,
        'detailed_description' => $rec->status_block->detailed_descr->textblock,
        'why_stopped' => $rec->why_stopped,
//        'study_design' => $rec->study_design,
		'study_design' => $rec->design,
        'biospec_descr' => $rec->biospec_descr->textblock,
        'study_pop' => $rec->eligibility->study_pop->textblock,
        'criteria' => $rec->eligibility->criteria->textblock,
        'biospec_retention' => $rec->biospec_retention,
        'completion_date_type' => $rec->completion_date['type'],
        'primary_completion_date_type' => $rec->primary_compl_date['type'],
        'enrollment_type' => $rec->enrollment['type'],
        'sampling_method' => $rec->eligibility->sampling_method,
        'rank' => $rec['rank'],
        'org_study_id' => $rec->study_id->org_study_id,
        'download_date' => substr($rec->required_header->download_date, strpos($rec->required_header->download_date, ' on ') + 4),
        'acronym' => $rec->acronym,
        'lead_sponsor' => $rec->study_sponsor->lead_sponsor->agency,
        'source' => $rec->source,
        'has_dmc' => ynbool($rec->oversight_info->has_dmc),
        'overall_status' => $rec->status_block->status,
        'start_date' => $rec->start_date->date,
        'end_date' => $rec->end_date->date,
        'completion_date' => $rec->last_follow_up_date->date,
        'primary_completion_date' => $rec->primary_compl_date->date,
        'phase' => $rec->phase_block->phase,
        'study_type' => $rec->study_type,
        'number_of_arms' => $rec->number_of_arms,
        'number_of_groups' => $rec->number_of_groups,
        'enrollment' => $rec->enrollment,
        'gender' => strtolower($rec->eligibility->gender),
        'minimum_age' => $rec->eligibility->minimum_age,
        'maximum_age' => $rec->eligibility->maximum_age,
        'healthy_volunteers' => ynbool($rec->eligibility->healthy_volunteers),
//        'contact_name' => $rec->contact->name,
//        'contact_degrees' => $rec->contact->degrees,
//        'contact_phone' => $rec->contact->phone,
//        'contact_phone_ext' => $rec->contact->phone_ext,
//        'contact_email' => $rec->contact->email,
        'backup_name' => $rec->contact_backup->name,
        'backup_degrees' => $rec->contact_backup->degrees,
        'backup_phone' => $rec->contact_backup->phone,
        'backup_phone_ext' => $rec->contact_backup->phone_ext,
        'backup_email' => $rec->contact_backup->email,
        'verification_date' => $rec->status_block->date,
        'lastchanged_date' => $rec->last_release_date,
        'firstreceived_date' => $rec->initial_release_date,
        'responsible_party_name_title' => $rec->resp_party->name_title,
        'responsible_party_organization' => $rec->resp_party->organization);

    // It appears can be more then one contact now
    $record_data['contact_name'] = array();
    $record_data['contact_degrees'] = array();
    $record_data['contact_phone'] = array();
    $record_data['contact_phone_ext'] = array();
    $record_data['contact_email'] = array();

    foreach ($rec->contact as $sid) {
        $record_data['contact_name'][] = $sid->name;
        $record_data['contact_degrees'][] = $sid->degrees;
        $record_data['contact_phone'][] = $sid->phone;
        $record_data['contact_phone_ext'][] = $sid->phone_ext;
        $record_data['contact_email'][] = $sid->email;
    }

    $record_data['secondary_id'] = array();
    foreach ($rec->study_id->secondary_id as $sid)
        $record_data['secondary_id'][] = $sid;
    $record_data['nct_alias'] = array();
    foreach ($rec->id_info->nct_alias as $nal)
        $record_data['nct_alias'][] = $nal;
    $record_data['collaborator'] = array();
    foreach ($rec->sponsors->collaborator as $cola)
        $record_data['collaborator'][] = $cola->agency;
    $record_data['oversight_authority'] = array();
    foreach ($rec->oversight_info->regulatory_authority as $auth)
        $record_data['oversight_authority'][] = $auth;
    $record_data['primary_outcome_measure'] = array();
    $record_data['primary_outcome_timeframe'] = array();
    $record_data['primary_outcome_safety_issue'] = array();
    foreach ($rec->primary_outcome as $out) {
        $record_data['primary_outcome_measure'][] = $out->measure;
        $record_data['primary_outcome_timeframe'][] = $out->time_frame;
        $record_data['primary_outcome_safety_issue'][] = ynbool($out->safety_issue);
    }
    $record_data['secondary_outcome_measure'] = array();
    $record_data['secondary_outcome_timeframe'] = array();
    $record_data['secondary_outcome_safety_issue'] = array();
    foreach ($rec->secondary_outcome as $out) {
        $record_data['secondary_outcome_measure'][] = $out->measure;
        $record_data['secondary_outcome_timeframe'][] = $out->time_frame;
        $record_data['secondary_outcome_safety_issue'][] = ynbool($out->safety_issue);
    }
    $record_data['condition'] = array();
    foreach ($rec->condition as $con)
        $record_data['condition'][] = $con;

    $record_data['arm_group_label'] = array();
    $record_data['arm_group_type'] = array();
    $record_data['arm_group_description'] = array();
    foreach ($rec->arm_group as $ag) {
        $record_data['arm_group_label'][] = $ag->arm_group_label;
        $record_data['arm_group_type'][] = $ag->arm_type;
        $record_data['arm_group_description'][] = $ag->description->textblock;
    }

    $record_data['intervention_type'] = array();
    $record_data['intervention_name'] = array();
    $record_data['intervention_other_name'] = array();
    $record_data['intervention_description'] = array();
    foreach ($rec->intervention as $inter) {
        $record_data['intervention_name'][] = $inter->primary_name;
        $record_data['intervention_description'][] = $inter->description->textblock;
        $record_data['intervention_type'][] = $inter->intervent_type;
        foreach ($inter->arm_group_label as $agl)
            $record_data['arm_group_label'][] = $agl;
        foreach ($inter->other_name as $oname)
            $record_data['intervention_other_name'][] = $oname;
    }
    $record_data['overall_official_name'] = array();
    $record_data['overall_official_degrees'] = array();
    $record_data['overall_official_role'] = array();
    $record_data['overall_official_affiliation'] = array();
    foreach ($rec->investigator as $oa) {
        $record_data['overall_official_name'][] = $oa->name;
        $record_data['overall_official_degrees'][] = $oa->degrees;
        $record_data['overall_official_affiliation'][] = $oa->affiliation->agency;
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

    foreach ($rec->location as $loc) {
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
        
        foreach ($loc->investigator as $inv) {
            $record_data['investigator_name'][] = assemble(' ', array($inv->first_name, $inv->middle_name, $inv->last_name));
            $record_data['investigator_degrees'][] = $inv->degrees;
            $record_data['investigator_role'][] = $inv->role;
        }
    }
    $record_data['link_url'] = array();
    $record_data['link_description'] = array();
    foreach ($rec->see_also as $lnk) {
        $record_data['link_url'][] = $lnk->url;
        $record_data['link_description'][] = $lnk->annotation->textblock;
    }
    $record_data['reference_citation'] = array();
    $record_data['reference_PMID'] = array();
    foreach ($rec->reference as $ref) {
        $record_data['reference_citation'][] = $ref->citation;
        $record_data['reference_PMID'][] = $ref->PMID;
    }
    $record_data['results_reference_citation'] = array();
    $record_data['results_reference_PMID'] = array();
    foreach ($rec->results as $ref) {
        $record_data['results_reference_citation'][] = $ref->reference->citation;
        $record_data['results_reference_PMID'][] = $ref->reference->PMID;
    }

/***** TKV
****** Detect and pick all irregular phases that exist in one or several of the various title or description fields */
$phases_regex='/phase 2a\/2b|phase 1b\/2a|phase 1a\/1b|Phase 1a\/b|phase 3b\/4|Phase2b\/3|phase 1b\/2|phase 2a\/b|phase 1a|phase 1b|Phase 1C|phase 2a|phase 2b|phase 3a|phase 3b/i';
preg_match_all($phases_regex, $record_data['brief_title'], $matches);

if(!count($matches[0]) >0 )
{
preg_match_all($phases_regex, $record_data['official_title'], $matches);
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
    }
	
	
}
//****
//     //import everything
	$pid = getmypid();
    foreach ($record_data as $fieldname => $value)
        if (!addval_d($studycat, $nct_cat, $fieldname, $value, $date))
		{
			$pid = getmypid();
			$query = 'SELECT update_id,process_id FROM update_status_fullhistory where status="2" order by update_id desc limit 1' ;
			$res = mysql_query($query) or die('Bad SQL query finding ready updates. Query:' . $query  );
			$res = mysql_fetch_array($res) ;
			if ( isset($res['update_id']) and $res['process_id'] == $pid  )
			{
				$msg='Data error in ' . $nct_id . '.';
				$query = 'UPDATE update_status_fullhistory SET status="3", er_message=' . $msg .' WHERE update_id="' . $res['update_id'] .'"';
				$res = mysql_query($query) or die('Bad SQL query finding ready updates. Query:' . $query  );
				mysql_query('COMMIT') or die("Couldn't commit SQL transaction. Query:".$query);
				exit;
			}
			else
            return softDie('Data error in ' . $nct_id . '.');
		}
    mysql_query('COMMIT') or die("Couldn't commit SQL transaction to create records from XML");
	global $fieldArr;
	refreshInactiveDates($larvol_id, 'search',$fieldArr);		
    return true;
}

function addval_d($studycat, $category_id, $fieldname, $value, $date) {
    // Use Date Passed in or of the record updated instead of actual date
    // so data matches up per Anthony.

    $DTnow = str_replace("_", "-", $date);

    //Find the field id (and get it's type) using the name
    $query = 'SELECT id,type FROM data_fields WHERE name="' . $fieldname . '" AND category=' . $category_id . ' LIMIT 1';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query getting field');
    $res = mysql_fetch_assoc($res);
    if ($res === false)
        return softDie('Field not found.' . $query);
    $field = $res['id'];
    $type = $res['type'];

    //normalize the input
    if (!is_array($value))
        $value = array($value);
    foreach ($value as $key => $v)
        $value[$key] = normalize($type, (string) $v);

    //Detect if the "new" value is a change
		if( !isset($value) ) $no_dat=true;
	elseif( empty($value) and !is_numeric($value) ) $no_dat=true;
	
    $query = 'SELECT id,val_' . $type . ' AS "val" FROM data_values WHERE '
			. 'val_' . $type . ' != \'\' AND val_' . $type . ' IS NOT NULL AND '
            . 'studycat=' . $studycat . ' AND field=' . $field . ' AND superceded IS NULL';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query getting value');
    $oldids = array();
    $oldvals = array();
	$t_value=array_flip($value);
    while ($row = mysql_fetch_assoc($res)) {
        
        if ($type == 'enum' && $row['val'] !== NULL) {
            $query2 = 'SELECT `value` FROM data_enumvals WHERE id=' . $row['val'] . ' LIMIT 1';
            $res2 = mysql_query($query2);
            if ($res2 === false)
                return softDie('Bad SQL query getting enumval');
            $res2 = mysql_fetch_assoc($res2);
            if ($res2 === false)
                return softDie('enumval not found');
            $row['val'] = $res2['value'];
        }

        if ( array_key_exists($row['val'], $t_value) )
		{
			$tvar2=$t_value[$row['val']];
			unset($value[$tvar2]);
		}
		else
		{
			$oldvals[] = $row['val'];
			$oldids[] = $row['id'];
		}
    }
	
    sort($value);
	$value=array_unique($value);
	$value=array_values($value);

    sort($oldvals);
	$oldvals=array_unique($oldvals);
	$oldvals=array_values($oldvals);
	
    $change = !($value == $oldvals);
	$no_dat=false;
	if( !isset($value) ) $no_dat=true;
	elseif( empty($value) and !is_numeric($value) ) $no_dat=true;
	//If the new value set is different, mark the old values as old and insert the new ones.
    if ($change and !$no_dat) {
        if (count($oldids)) 
		{
            $query = 'UPDATE data_values SET superceded="' . $DTnow . '" WHERE id IN(' . implode(',', $oldids) . ')';
			
            $res1=mysql_query($query);
			if( !$res1  and ( mysql_errno() <> 1213 and mysql_errno() <> 1205 )  ) // error
				return softDie('Bad SQL query marking old values' . mysql_error() . '<br />' . $query);
	
			//TKV  
			//will retry in case of lock wait time timeout  
			if( !$res1 and ( mysql_errno() == 1213 or mysql_errno() == 1205 )) 
			{
				for ( $retries = 300; $dead_lock and $retries > 0; $retries -- )
				{
					$pid = getmypid();
					$query1 = 'SELECT update_id,process_id FROM update_status_fullhistory where status="2" order by update_id desc limit 1' ;
					$res = mysql_query($query1) or die('Bad SQL query finding ready updates. Query:' . $query1  );
					$res = mysql_fetch_array($res) ;
					if ( isset($res['update_id']) and $res['process_id'] == $pid  )
					{
						$msg='Lock wait timed out. Re-trying to get lock...';
						$query1 = 'UPDATE update_status_fullhistory SET er_message=' . $msg . ' WHERE update_id="' . $res['update_id'] .'"';
						$res = mysql_query($query1) or die('Bad SQL query finding ready updates. Query:' . $query1  );
					}
				
				
					sleep(10); 
					$res = mysql_query($query) ;
					if(!$res)
					{
						if (mysql_errno() == 1213 or mysql_errno() == 1205) 
						{ 
							$dead_lock = true;
							$retries --;
						}
						else 
						{
							$dead_lock = false;
							die('Unable to delete existing values' . mysql_error() . '('. mysql_errno() .')');
						}
						
					}
					else 
					{
						$dead_lock = false;
					}
				}
			}
			
	
	//*************
            
        }
		
        foreach ($value as $val) 
		{
            if ($type == 'enum') {
                if (!strlen($val)) {
                    $val = NULL;
                } else {
				// evaluate enums before proceeding
				
					$evl=validateEnums($val);
					if($evl) 
					{
					echo '<br>New enum value "'. $evl . '" assigned instead of "' . $val . '"';
					$val=$evl;
					}
                    $query = 'SELECT id FROM data_enumvals WHERE `field`=' . $field . ' AND `value`="' . mysql_real_escape_string($val)
                            . '" LIMIT 1';
                    $res = mysql_query($query);
                    if ($res === false)
                        return softDie('Bad SQL query getting enumval id');
                    $res = mysql_fetch_array($res);
                    if ($res === false)
					{
						$pid = getmypid();
						$query = 'SELECT update_id,process_id FROM update_status_fullhistory where status="2" order by update_id desc limit 1' ;
						$res = mysql_query($query) or die('Bad SQL query finding ready updates. Query:' . $query  );
						$res = mysql_fetch_array($res) ;

						if ( isset($res['update_id']) and $res['process_id'] == $pid  )
						{
							$upid=$res['update_id'];
							$msg='Error:Invalid enumval <b>' . $val . '</b> for field <b>' . $fieldname . '</b> (studycat='. $studycat .')' ;
							$query = 'UPDATE update_status_fullhistory SET status="3", er_message="' . $msg . '" WHERE update_id= "' . $upid .'" ';
							$res = mysql_query($query) or die('Bad SQL query finding ready updates. Query:' . $query  );
							mysql_query('COMMIT') or die("Couldn't commit SQL transaction. Query:".$query);
							exit;
						}
						else
                        return softDie('Invalid enumval "' . $val . '" for field "' . $fieldname . '"');
					}
                    $val = $res['id'];
                }
            }
			if( !isset($val) )
			{
				$no_dat=true;
				
			}
			elseif( empty($val) and !is_numeric($val) )
			{
				$no_dat=true;
				
			}
			else $no_dat=false;
			
            if (!is_null($val) and !$no_dat) 
			{
					$query = 'INSERT INTO data_values SET `added`="' . $DTnow . '",'
							. '`field`=' . $field . ',studycat=' . $studycat . ',val_' . $type . '=' . esc($type, $val);
					if (mysql_query($query) === false)
						return softDie('Bad SQL query saving value');
			}
						
        }
        $query = 'UPDATE clinical_study SET last_change="' . $DTnow . '" '
                . 'WHERE larvol_id=(SELECT larvol_id FROM data_cats_in_study WHERE id=' . $studycat . ') LIMIT 1';
        if (mysql_query($query) === false)
            return softDie('Bad SQL query recording changetime');
    }
    return true;
}

function ProcessDiffChanges($id, $date) {

    global $level;
    // Now Go To changes Site and parse differences
    $url = "http://clinicaltrials.gov/archive/" . $id . "/" . $date . "/changes";

    $docpc = new DOMDocument();
    echo $tab . '<hr>Parsing Archive Changes Page for ' . $id . ' and Date: ' . $date . '... - ';

    echo $url . " - <br>";
    $time = date("h:i:s A"); 
    echo 'Time: ' . $time . '</br>';
    $nct_id = unpadnct($id);

    //Find out the ID of the field for nct_id, and the ID of the "NCT" category.
    static $nct_cat = NULL;
    static $id_field = NULL;
    if ($nct_cat === NULL) {
        $query = 'SELECT data_fields.id AS "nct_id",data_categories.id AS "nct_cat" FROM '
                . 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
                . 'WHERE data_fields.name="nct_id" AND data_categories.name="NCT" LIMIT 1';
        $res = mysql_query($query);
        if ($res === false)
            return softDie('Bad SQL query getting field ID of nct_id');
        $res = mysql_fetch_assoc($res);
        if ($res === false)
            return softDie('NCT schema not found!');
        $nct_cat = $res['nct_cat'];
        $id_field = $res['nct_id'];
    }

    // Get StudyCat
    $studycat = null;
    $query = 'SELECT studycat FROM '
            . 'data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
            . 'WHERE field=' . $id_field . ' AND val_int=' . $nct_id . ' LIMIT 1';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query determining existence of record');
    $res = mysql_fetch_assoc($res);
    $exists = $res !== false;
    $studycat = NULL;
    if ($exists) {
        $studycat = $res['studycat'];
        echo "StudyCat: " . $studycat . "<br>";
    } else {
        echo "Unable to Find StudyCat in DB";
        return;
    }

    for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
        echo('!');
        @$done = $docpc->loadHTMLFile($url);
    }    
    
    $div = $docpc->getElementsByTagName('table');
    // Get Second Table
    unset($docpc);
    
    $datatable = NULL;
    $tablecount=0;
    foreach ($div as $table) {
        $right = false;
        foreach ($table->attributes as $attr) {
            if ($attr->name == 'class' && $attr->value == 'sdiff navLinks')
            {
                $tablecount=$tablecount+1;
                if($tablecount==2)
                {
                    // Take Second sdiff navLinks for the trimmed version
                    $right = true;
                    break;                    
                }
            }
        }
        if ($right == true) {
            $datatable = $table;
            break;
            
        }
    }
    
    if ($datatable == NULL) {
        echo('End of page reached.' . "\n<br />");
        echo('Returning Page To Continue.' . "\n<br />");
        unset($div);
        return;
    }
    unset($div);

    // Have right section no look for any div that does not equal "sdiff-collapsed"
    // Now step through the family of trs combining into most recent xml version
    $trs = $datatable->getElementsByTagName('tr');
    unset($datatable);    
    
    foreach ($trs as $tr) {
        foreach ($tr->attributes as $attr) {

            if ($attr->name == 'class' && $attr->value != "sdiff-unc") {
                // Check to See if Add/Delete/Modify

                if ($attr->name == 'class' && $attr->value == "sdiff-chg") {
                    // Change
                    $action = "change";
                    prepXMP($tr, $action, $studycat, $nct_cat, $date);
                } else if ($attr->name == 'class' && $attr->value == "sdiff-del") {
                    //Delete
                    $action = "delete";
                    prepXMP($tr, $action, $studycat, $nct_cat, $date);
                } else if ($attr->name == 'class' && $attr->value == "sdiff-add") {
                    // Add
                    $action = "add";
                    prepXMP($tr, $action, $studycat, $nct_cat, $date);
                 }
                // Else Dont Care
            } else {
                // Get Structure
                ParseStructure($tr);
            }
        }
        unset($tr);
        $fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
        @mysql_fetch_array($fake);
    }

    unset($trs);
    
    $time = date("h:i:s A"); 
    echo 'End Time: ' . $time . '</br>';

    return;
}

function ParseStructure($data) {
    global $level;

    $divs = $data->getElementsByTagName('div');
    $end_tag = null;
    $last = false;
    
    unset($data);
    
    foreach ($divs as $div) {
        foreach ($div->attributes as $attr) {
            // Just look for start tag

            if ($attr->name == 'class' && (strpos($attr->value, "sds") !== false)) {
                pop_structure($attr->value, $div->nodeValue);
            }
        }
        unset($div);
    }

    unset($divs);

    return;
} 

function prepXMP($data, $action, $studycat, $nct_cat, $date) {

    global $level;
    
    // Logic for which column see if needs to be different.
    if ($action == "change") {
        $column = "sdiff-b";
    } else if ($action == "add") {
        $column = "sdiff-b";
    } else if ($action == "delete") {
        $column = "sdiff-a";
    }

    $right = false;

    // Get Column of interest.
    $elements = $data->getElementsByTagName('td');
    unset($data);

    foreach ($elements as $element) {
        foreach ($element->attributes as $attr) {
		
			
			/******************************************/
			if ($attr->name == 'class' && $column == "sdiff-b" && $attr->value == "sdiff-a") 
			{
			$datatable1 = $element;
			}
			/*******************************************/
		}
		foreach ($element->attributes as $attr) {	
            if ($attr->name == 'class' && $attr->value == $column) {
                $right = true;
                break;
            }
            if ($right == true) {
                $datatable = $element;
                break;
            }
        }
        if ($right == true) {
            $datatable = $element;
            break;
        }
    }
    if ($datatable == NULL) {
        echo('Nothing Found in Comparision ID-2');
        unset($elements);
        return;
    }
	
	/************************************************/
	if(isset($datatable1) && !empty($datatable1))
	{
		$value1="";
		$divs = $datatable1->getElementsByTagName('div');
		unset($datatable1);
	
		$last = false;

		foreach ($divs as $div) {
			foreach ($div->attributes as $attr) {
				if ($attr->name == 'class' && (strpos($attr->value, "sds") !== false)) {
//					pop_structure($attr->value, $div->nodeValue);
		 } else if ($attr->name == 'class' && (strpos($attr->value, "sdz") !== false)) {
					// Do Nothing
					// Just End Tag
				} else if ($attr->name == 'class' && (strpos($attr->value, "sda") !== false)) {
					// Its a Tag Type
//					pop_structure($attr->value, $div->nodeValue);
				} else {
					$value1 = $div->nodeValue;
					// Check to see if Value is Type
					if (strpos($value1, "type=") !== false) {
						$value1 = trim($value1);
						$value1 = substr($value1, 6, -1);
	//                    $return = process_change($tag, $tag, $value, $nct_cat, $studycat, $date, $action);
	 
					}
				}
			}
			unset($div);
		}
	}
	/************************************************/

    $divs = $datatable->getElementsByTagName('div');
    unset($datatable);
    unset($elements);
    
    $last = false;

    foreach ($divs as $div) {
        foreach ($div->attributes as $attr) {
            if ($attr->name == 'class' && (strpos($attr->value, "sds") !== false)) {
                pop_structure($attr->value, $div->nodeValue);
     } else if ($attr->name == 'class' && (strpos($attr->value, "sdz") !== false)) {
                // Do Nothing
                // Just End Tag
            } else if ($attr->name == 'class' && (strpos($attr->value, "sda") !== false)) {
                // Its a Tag Type
                pop_structure($attr->value, $div->nodeValue);
            } else {
                $value = $div->nodeValue;
                // Check to see if Value is Type
                if (strpos($value, "type=") !== false) {
                    $value = trim($value);
                    $value = substr($value, 6, -1);
                    $tag = build_key();
                    $tag = $tag . "_type";
                    $return = process_change($tag, $tag, $value, $nct_cat, $studycat, $date, $action,$value1);
 
                } else if ($value != ">") {
                    $tag = build_key();
                    $return = process_change($tag, $tag, $value, $nct_cat, $studycat, $date, $action,$value1);
                }
            }
        }
        unset($div);
    }

    unset($divs);

    return;
}

function stripnode($value) {
    $value = htmlspecialchars($value);

    $badchars = array("&lt;", "&gt;", "/");
    $value = str_replace($badchars, "", $value);

    return $value;
}

function getFieldName($value) {
    $fieldname = NULL;
    global $mapping;

    $fieldname = $mapping[$value];

    if ($fieldname == NULL) {
        echo "WARNING: Unable to find fieldname: " . $value . ", match in mapping.<br>";
    }

    return $fieldname;
}

function commit_diff($studycat, $category_id, $fieldname, $value, $date, $operation,$value1) {
    // Use Date Passed in or of the record updated instead of actual date
    // so data matches up per Anthony.
    $DTnow = str_replace("_", "-", $date);
    //Find the field id (and get it's type) using the name
    $query = 'SELECT id,type FROM data_fields WHERE name="' . $fieldname . '" AND category=' . $category_id . ' LIMIT 1';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query getting field');
    $res = mysql_fetch_assoc($res);
    if ($res === false)
        return softDie('Field not found.' . $query);
    $field = $res['id'];
    $type = $res['type'];

    //normalize the input
    if (!is_array($value))
        $value = array($value);
    foreach ($value as $key => $v)
        $value[$key] = normalize($type, (string) $v);

    echo "Action: " . $operation . " - ";
    if ($operation == "delete" || $operation == "change") {
		if($operation=="change")
			$query = 'UPDATE data_values SET superceded="' . $DTnow . '" WHERE studycat=' . $studycat . ' AND superceded is NULL  and added < "' . $DTnow . '" and val_' . $type . '= "' . $value1 . '" and field=' . $field . '  ';
		else
			$query = 'UPDATE data_values SET superceded="' . $DTnow . '" WHERE studycat=' . $studycat . ' AND superceded is NULL  and added < "' . $DTnow . '"  and field=' . $field . '  ';
		if (mysql_query($query) === false)
            return softDie('Bad SQL query marking old values' . mysql_error() . '<br />' . $query);
    }

    if ($operation == "add" || $operation == "change") {
        foreach ($value as $val) {
            if ($type == 'enum') {
                if (!strlen($val)) {
                    $val = NULL;
                } else {
                    $query = 'SELECT id FROM data_enumvals WHERE `field`=' . $field . ' AND `value`="' . mysql_real_escape_string($val)
                            . '" LIMIT 1';
                    $res = mysql_query($query);
                    if ($res === false)
                        return softDie('Bad SQL query getting enumval id');
                    $res = mysql_fetch_array($res);
                    if ($res === false)
                        return softDie('Invalid enumval "' . $val . '" for field "' . $fieldname . '"');
                    $val = $res['id'];
                }
            }
            $query = 'INSERT INTO data_values SET `added`="' . $DTnow . '",'
                    . '`field`=' . $field . ',studycat=' . $studycat . ',val_' . $type . '=' . esc($type, $val);

            if (mysql_query($query) === false)
                return softDie('Bad SQL query saving value');
        }
    }
    $query = 'UPDATE clinical_study SET last_change="' . $DTnow . '" '
            . 'WHERE larvol_id=(SELECT larvol_id FROM data_cats_in_study WHERE id=' . $studycat . ') LIMIT 1';
    if (mysql_query($query) === false)
        return softDie('Bad SQL query recording changetime');

    return true;
}

function process_change($begin_tag, $end_tag, $value, $nct_cat, $studycat, $date, $action,$value1) {

    $fieldname = getFieldName($begin_tag);

    if ($fieldname != NULL) {
        // Call to REPLACE

        if (!commit_diff($studycat, $nct_cat, $fieldname, $value, $date, $action,$value1))
            return softDie('Data error in ' . $studycat . '.');
        else {
            echo "DB Commit: " . $begin_tag . " -> " . $value . "</br>";
        }

        return 1;
    }

    return 0;
}

function build_key() {
    global $level;

    $result = implode('-', $level);

    return $result;
}

function pop_structure($attr_value, $node_value) {
    global $level;
    
    // Get Level
    $holder = substr($attr_value, 3, 1);
    // Subtract one to skip clinical-study
    $holder = $holder - 1;
    if ($holder >= 0) {
        $level[$holder] = stripnode($node_value);
    }
    // Clear Out Rest
    $i = $holder + 1;
    while (isset($level[$i])) {
        unset($level[$i]);
        $i = $i + 1;
    }
    //print_r($level);
}


function ProcessNonEssentials($id, $date) {

    global $level;
    // Now Go To changes Site and parse differences
    $url = "http://clinicaltrials.gov/archive/" . $id . "/" . $date . "/changes";

    $docpc = new DOMDocument();
    echo $tab . '<hr>Parsing Archive Changes Page for NONESSENTIALS - ' . $id . ' and Date: ' . $date . '... - ';

    echo $url . " - <br>";
    $time = date("h:i:s A"); 
    echo 'Time: ' . $time . '</br>';
    $nct_id = unpadnct($id);

    //Find out the ID of the field for nct_id, and the ID of the "NCT" category.
    static $nct_cat = NULL;
    static $id_field = NULL;
    if ($nct_cat === NULL) {
        $query = 'SELECT data_fields.id AS "nct_id",data_categories.id AS "nct_cat" FROM '
                . 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
                . 'WHERE data_fields.name="nct_id" AND data_categories.name="NCT" LIMIT 1';
        $res = mysql_query($query);
        if ($res === false)
            return softDie('Bad SQL query getting field ID of nct_id');
        $res = mysql_fetch_assoc($res);
        if ($res === false)
            return softDie('NCT schema not found!');
        $nct_cat = $res['nct_cat'];
        $id_field = $res['nct_id'];
    }

    // Get StudyCat
    $studycat = null;
    $query = 'SELECT studycat FROM '
            . 'data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
            . 'WHERE field=' . $id_field . ' AND val_int=' . $nct_id . ' LIMIT 1';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query determining existence of record');
    $res = mysql_fetch_assoc($res);
    $exists = $res !== false;
    $studycat = NULL;
    if ($exists) {
        $studycat = $res['studycat'];
        echo "StudyCat: " . $studycat . "<br>";
    } else {
        echo "Unable to Find StudyCat in DB";
        return;
    }

    for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
        echo('!');
        @$done = $docpc->loadHTMLFile($url);
    }    
    
    $div = $docpc->getElementsByTagName('table');
    // Get Second Table
    unset($docpc);
    
    $datatable = NULL;
    $tablecount=0;
    foreach ($div as $table) {
        $right = false;
        foreach ($table->attributes as $attr) {
            if ($attr->name == 'class' && $attr->value == 'sdiff navLinks')
            {
                $tablecount=$tablecount+1;
                if($tablecount==1)
                {
                    // Take First sdiff navLinks for the nonessential version
                    $right = true;
                    break;                    
                }
            }
        }
        if ($right == true) {
            $datatable = $table;
            break;
            
        }
    }
    
    if ($datatable == NULL) {
        echo('End of page reached.' . "\n<br />");
        echo('Returning Page To Continue.' . "\n<br />");
        unset($div);
        return;
    }
    unset($div);

    // Now we have right section 
    
    $column = "sdiff-b"; 
    // Get Column of interest.
    
    $elements = $datatable->getElementsByTagName('td');
    unset($datatable);
  
    foreach ($elements as $element) {
        foreach ($element->attributes as $attr) {
            if ($attr->name == 'class' && $attr->value == $column) {
                $divs = $element->getElementsByTagName('div');
                unset($element);    

                // Now we have right column. Look for nonessentials.
                foreach ($divs as $div) {
                    foreach ($div->attributes as $attr) {
                        if ($attr->name == 'class' && (strpos($attr->value, "sds") !== false)) {
                            pop_structure($attr->value, $div->nodeValue);
                        } else if ($attr->name == 'class' && (strpos($attr->value, "sdz") !== false))  {
                            // Do Nothing
                            // Just End Tag
                        } else if ($attr->name == 'class' && (strpos($attr->value, "sda") !== false)) {
                            // Its a Tag Type
                            pop_structure($attr->value, $div->nodeValue);
                        } else {
                            $value = $div->nodeValue;
							// Only look for nonessentials don't care if this is a type.
                            if ($value != ">") {
                                $tag = build_key();
                                // In this case only interest if tage has location- or contact- in the structure.
                                if ((strpos($tag, "location-") !== false) || 
                                    (strpos($tag, "keyword") !== false) || 
                                    (strpos($tag, "contact-") !== false))
                                {
                                    $return = process_change($tag, $tag, $value, $nct_cat, $studycat, $date, "add");
                                }
                           }
                        }
                    }
                    unset($div);
                }
            }        
            unset($divs);
        }
    }

    
    $time = date("h:i:s A"); 
    echo 'End Time: ' . $time . '</br>';

    return;
}

?>
