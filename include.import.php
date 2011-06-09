<?php
require_once('db.php');
require_once ('include.derived.php');

$instMap = institutionMapping();

//calculate field Ids and store in an array since it requires db call
$fieldArr = calculateDateFieldIds();

//Adds a new record of any recognized type from a simpleXML object.
//Autodetects the type if none is specified.
function addRecord($in, $type='unspec')
{
	static $types = array('clinical_study' => 'nct', 'PubmedArticle' => 'pubmed', 'EudraCT' => 'EudraCT');
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

	//Find out the ID of the field for nct_id, and the ID of the "NCT" category.
	static $id_field = NULL;
	static $nct_cat = NULL;
	if($id_field === NULL || $nct_cat === NULL)
	{
		$query = 'SELECT data_fields.id AS "nct_id",data_categories.id AS "nct_cat" FROM '
				. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
				. 'WHERE data_fields.name="nct_id" AND data_categories.name="NCT" LIMIT 1';
		$res = mysql_query($query);
		if($res === false) return softDie('Bad SQL query getting field ID of nct_id');
		$res = mysql_fetch_assoc($res);
		if($res === false) return softDie('NCT schema not found!');
		$id_field = $res['nct_id'];
		$nct_cat = $res['nct_cat'];
	}

	mysql_query('BEGIN') or die("Couldn't begin SQL transaction to create record from XML");
	//Detect if this record already exists (if not, add it, and associate it with the category NCT)
	//Then, get the larvol_id and the ID of the studycat it has for NCT.
	$query = 'SELECT studycat,larvol_id FROM '
			. 'data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
			. 'WHERE field=' . $id_field . ' AND val_int=' . $nct_id . ' LIMIT 1';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query determining existence of record');
	$res = mysql_fetch_assoc($res);
	$exists = $res !== false;
	$studycat = NULL;
	$larvol_id = NULL;
	if($exists)
	{
		$studycat = $res['studycat'];
		$larvol_id = $res['larvol_id'];
	}else{
		$query = 'INSERT INTO clinical_study SET import_time="' . date('Y-m-d H:i:s',$now) . '"';
		if(mysql_query($query) === false) return softDie('Bad SQL query adding new record');
		$larvol_id = mysql_insert_id();
		$query = 'INSERT INTO data_cats_in_study SET larvol_id=' . $larvol_id . ',category=' . $nct_cat;
		if(mysql_query($query) === false) return softDie('Bad SQL query adding new record to category');
		$studycat = mysql_insert_id();
		$query = 'INSERT INTO data_values SET field=' . $id_field . ',`added`="' . $DTnow . '",studycat=' . $studycat
					. ',val_int=' . $nct_id;
		if(mysql_query($query) === false) return softDie('Bad SQL query adding nct_id');
	}
	
	//Determine institution type
	$institution_type = 'other';
	$all_sponsors = array();
	foreach($rec->sponsors->collaborator as $cola)
	{
		$all_sponsors[] = "" . $cola->agency;
	}
	$all_sponsors[] = "" . $rec->sponsors->lead_sponsor->agency;
	foreach($all_sponsors as $a_sponsor)
	{
		if(strlen($a_sponsor) && isset($instMap[$a_sponsor]))
		{
			$institution_type = $instMap[$a_sponsor];
			if($institution_type == 'industry') break;
		}
	}
	$query = 'UPDATE clinical_study SET institution_type="' . $institution_type . '" WHERE larvol_id=' . $larvol_id . ' LIMIT 1';
	if(mysql_query($query) === false) return softDie('Bad SQL query recording institution type');
	
	//Go through the parsed XML structure and pick out the data
	$record_data =array('brief_title' => $rec->brief_title,
						'official_title' => $rec->official_title,
						'brief_summary' => $rec->brief_summary->textblock,
						'detailed_description' => $rec->detailed_description->textblock,
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
	foreach($rec->primary_outcome as $out)
	{
		$record_data['primary_outcome_measure'][] = $out->measure;
		$record_data['primary_outcome_timeframe'][] = $out->time_frame;
		$record_data['primary_outcome_safety_issue'][] = ynbool($out->safety_issue);
	}
	$record_data['secondary_outcome_measure'] = array();
	$record_data['secondary_outcome_timeframe'] = array();
	$record_data['secondary_outcome_safety_issue'] = array();
	foreach($rec->secondary_outcome as $out)
	{
		$record_data['secondary_outcome_measure'][] = $out->measure;
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

	//import everything

	foreach($record_data as $fieldname => $value)
		if(!addval($studycat, $nct_cat, $fieldname, $value))
			logDataErr('Data error - NCTID:' . padnct($nct_id) . ', Field Name:' . $fieldname . ', Value: ' . $value);//Log in errorlog

	mysql_query('COMMIT') or die("Couldn't commit SQL transaction to create records from XML");
	global $fieldArr;
	refreshInactiveDates($larvol_id, 'search',$fieldArr);		
	return true;
}



// DW Add or update a EudraCT record 
function addEudraCT($rec) {

    global $db;
    global $instMap;
    global $now;
    if ($rec === false)
        return false;

    $DTnow = date('Y-m-d H:i:s', $now);
    
    // TODO:  Since same eud number is stored for multiple countries.. Need to make the
    // id of the case the number + countrys
    
    // Get Coutry
    $i = strpos($rec['competent_authority'][0], "-");
    $country = substr($rec['competent_authority'][0], 0, $i);
    
    $eud_id = $rec['eudract_number'][0] . "-" . $country;
    
//    $eud_id = $rec['eudract_number'][0];
    echo "<br>EUD ID: " . $eud_id . "<br>";

    //Find out the ID of the field for eudract_number, and the ID of the "EudraCT" category.
    static $id_field = NULL;
    static $eud_cat = NULL;
    if ($id_field === NULL || $nct_cat === NULL) {
        $query = 'SELECT data_fields.id AS "eudract_id",data_categories.id AS "eud_cat" FROM '
                . 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
                . 'WHERE data_fields.name="eudract_id" AND data_categories.name="EudraCT" LIMIT 1';
        $res = mysql_query($query);
        if ($res === false)
            return softDie('Bad SQL query getting field ID of eudract_id');
        $res = mysql_fetch_assoc($res);
        if ($res === false)
            return softDie('EUD schema not found!');
        $id_field = $res['eudract_id'];
        $eud_cat = $res['eud_cat'];
    }

    mysql_query('BEGIN') or die("Couldn't begin SQL transaction to create record from XML");
    //Detect if this record already exists (if not, add it, and associate it with the category NCT)
    //Then, get the larvol_id and the ID of the studycat it has for NCT.
    $query = 'SELECT studycat,larvol_id FROM '
            . 'data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
            . 'WHERE field=' . $id_field . ' AND val_varchar="' . $eud_id . '" LIMIT 1';
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
        $query = 'INSERT INTO data_cats_in_study SET larvol_id=' . $larvol_id . ',category=' . $eud_cat;
        if (mysql_query($query) === false)
            return softDie('Bad SQL query adding new record to category');
        $studycat = mysql_insert_id();
        $query = 'INSERT INTO data_values SET field=' . $id_field . ',`added`="' . $DTnow . '",studycat=' . $studycat
                . ',val_varchar="' . $eud_id . '"';
        if (mysql_query($query) === false)
            return softDie('Bad SQL query adding eud_id');
    }

    echo "StudyCat: " . $studycat . ":<br>";
    //import everything
    foreach ($rec as $fieldname => $value) {
        if ($fieldname != "") {
            //         foreach ($value as $valueitem) {
            //  echo "Field: " . $fieldname . ", Value: " . $valueitem . "<br>";
            if (!addval($studycat, $eud_cat, $fieldname, $value))
                return softDie('Data error in ' . $eud_id . '. Fieldname: ' . $fieldname . ': ' . $value);
            //          }
        }
    }
    mysql_query('COMMIT') or die("Couldn't commit SQL transaction to create records from XML");
    return true;
}

// Add or update a PubMed record from a SimpleXML object.
function addPubmed($rec)
{
	
}

/* Adds values to the database for a given record.
	Handles any escaping/processing through esc($type, $value).
	Handles historical recordkeeping.
*/
function addval($studycat, $category_id, $fieldname, $value)
{
	global $now;
	$DTnow = date('Y-m-d H:i:s',$now);

	//Find the field id (and get it's type) using the name
	$query = 'SELECT id,type FROM data_fields WHERE name="' . $fieldname . '" AND category=' . $category_id . ' LIMIT 1';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting field');
	$res = mysql_fetch_assoc($res);
	if($res === false) return softDie('Field not found.'.$query);
	$field = $res['id'];
	$type = $res['type'];
	
	//normalize the input
	if(!is_array($value)) $value = array($value);
	foreach($value as $key => $v) $value[$key] = normalize($type,(string)$v);
	
	//Detect if the "new" value is a change
	$query = 'SELECT id,val_' . $type . ' AS "val" FROM data_values WHERE '
			. 'studycat=' . $studycat . ' AND field=' . $field . ' AND superceded IS NULL';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting value');
	$oldids = array();
	$oldvals = array();
	while($row = mysql_fetch_assoc($res))
	{
		$oldids[] = $row['id'];
		if($type == 'enum' && $row['val'] !== NULL)
		{
			$query2 = 'SELECT `value` FROM data_enumvals WHERE id=' . $row['val'] . ' LIMIT 1';
			$res2 = mysql_query($query2);
			if($res2 === false) return softDie('Bad SQL query getting enumval');
			$res2 = mysql_fetch_assoc($res2);
			if($res2 === false) return softDie('enumval not found');
			$row['val'] = $res2['value'];
		}
		$oldvals[] = $row['val'];
	}
	sort($value);
	sort($oldvals);
	$change = !($value == $oldvals);
	
	//If the new value set is different, mark the old values as old and insert the new ones.
	if($change)
	{
		if(count($oldids))
		{
			$query = 'UPDATE data_values SET superceded="' . $DTnow . '" WHERE id IN(' . implode(',', $oldids) . ')';
			if(mysql_query($query) === false) return softDie('Bad SQL query marking old values'.mysql_error().'<br />'.$query);
		}
		foreach($value as $val)
		{
			if($type == 'enum')
			{
				if(!strlen($val))
				{
					$val = NULL;
				}else{
					$query = 'SELECT id FROM data_enumvals WHERE `field`=' . $field . ' AND `value`="' . mysql_real_escape_string($val)
							. '" LIMIT 1';
					$res = mysql_query($query);
					if($res === false) return softDie('Bad SQL query getting enumval id');
					$res = mysql_fetch_array($res);
					if($res === false) return softDie('Invalid enumval "' . $val . '" for field "' . $fieldname . '"');
					$val = $res['id'];
				}
			}
			$query = 'INSERT INTO data_values SET `added`="' . $DTnow . '",'
					. '`field`=' . $field . ',studycat=' . $studycat . ',val_' . $type . '=' . esc($type, $val);
			if(mysql_query($query) === false) return softDie('Bad SQL query saving value');
		}
		$query = 'UPDATE clinical_study SET last_change="' . $DTnow . '" '
			. 'WHERE larvol_id=(SELECT larvol_id FROM data_cats_in_study WHERE id=' . $studycat . ') LIMIT 1';
		if(mysql_query($query) === false) return softDie('Bad SQL query recording changetime');
	}
	return true;
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
// DW
    $value = preg_replace( '/\s+/', ' ', trim( $value ) );         
    if ($value == " " || $value == "") return NULL;
// DW
        
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