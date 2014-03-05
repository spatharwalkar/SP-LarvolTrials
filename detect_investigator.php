<?php
error_reporting(E_ERROR);
require_once('db.php');
require_once('include.util.php');
ini_set('max_execution_time', '9000000'); //250 hours
ignore_user_abort(true);
global $db;
global $logger;

function detect_inv($source_id=NULL, $larvolid=NULL,  $sourcedb=NULL )
{
	global $logger;
	if(isset($source_id)) // A single trial
	{
		$trial=padnct($source_id);
		$query = 'SELECT `larvol_id`,institution_type FROM data_trials where `source_id`="' . $trial . '"  LIMIT 1';
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
		if($res['institution_type']=='industry_lead_sponsor')
		{
			echo 'Industry lead sponsor trial, skipping......<br>';
//			return false;
		}
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
	elseif(isset($larvolid)) // A single larvol_id
	{
		$trial=$larvolid;
		$query = 'SELECT `larvol_id`, institution_type FROM data_trials where `larvol_id`="' . $trial . '"  LIMIT 1';

		if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$res = mysql_fetch_assoc($res);
		$exists = $res !== false;
		
		if($res['institution_type']=='industry_lead_sponsor')
		{
			echo 'Industry lead sponsor trial, skipping......<br>';
//			return false;
		}
		
		
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

	elseif(isset($sourcedb) and $sourcedb=="ALL")  // All 	NCT trials
	{
		$source='data_nct';
		$query = '	SELECT data_nct.larvol_id FROM data_nct,data_trials
					WHERE data_nct.larvol_id = data_trials.larvol_id AND 
					data_trials.institution_type <> "industry_lead_sponsor" ';

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

			$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="INVESTIGATOR" order by update_id desc limit 1' ;
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

			$fid = getFieldId('NCT','nct_id');
			
			$cid = 0; 
			$cid_=$cid;
			$pid = getmypid();
			$totalncts=count($larvol_ids);
			
			
			$query = 'INSERT into update_status_fullhistory (process_id,status,update_items_total,start_time,max_nctid,trial_type) 
					  VALUES ("'. $pid .'","'. 2 .'",
					  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'", "INVESTIGATOR"  ) ;';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				else
				$up_id=mysql_insert_id();
			
			
			
			echo('<br>Current time ' . date('Y-m-d H:i:s', strtotime('now')) . '<br>');
			echo str_repeat ("  ", 4000);
			$i=1;
			
		}

		/* STATUS */


	if(!isset($cid)) $cid = 0; 	


	if(!isset($larvol_ids)) $larvol_ids=array($larvol_id);
	
	$orig_larvol_id=$larvol_id;
	
	
	$DTnow = date("Y-m-d H:i:s", strtotime('now'));

		$counter=0;

	foreach($larvol_ids as $larvol_id)
	{

		if($cid > $larvol_id) continue; 
		
		$counter++;
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
		if($exists)
			$larvol_id = $res['larvol_id'];
		else
			$larvol_id = $orig_larvol_id;
		$nctid=padnct($res['nct_id']);
		$record_data = $res;
		if(!$exists)
		{
			
			$query = 'SELECT * FROM data_trials where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
			
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$res = mysql_fetch_assoc($res);
			$nctid=$res['source_id'];
			$exists = $res !== false;
			$record_data = $res;
			
			/***** SKIP Industry lead sponsor trials */
			if($res['institution_type']=='industry_lead_sponsor')
			{
				echo 'Industry lead sponsor trial, skipping ' . $res['source_id'] . '......<br>';
//				continue;
			}
			/****************************/
			
		}
		
		$i=0;
		$overall_official_name = $record_data['overall_official_name'];
		$overall_official_affiliation = $record_data['overall_official_affiliation'];
		
		//skip if empty name
		$overall_official_name=trim($overall_official_name);
		if(empty($overall_official_name))
			continue;
			
		pr($nctid. ' => '. $overall_official_name . ' => '. $overall_official_affiliation);
		/********** Split multiple investigator names in same field , and save them as separate entities */
		$investigator_names=array();
		$affiliations=array();
		$facilities = array();
		$locations = array();
		
		//split investigator names
		$pos = strpos($overall_official_name, '`');
		if($pos === false)
		{
			$investigator_names[]=$overall_official_name;
		}
		else
		{
			$investigator_names = explode("`", $overall_official_name);
		}
		
		//split affiliations
		$pos = strpos($overall_official_affiliation, '`');
		if($pos === false)
		{
			$affiliations[]=$overall_official_affiliation;
		}
		else
		{
			$affiliations = explode("`", $overall_official_affiliation);
		}

		// Mark placeholders for the investigators from the `overall_official_name`
		// These will be replaced by the Primary facility later.
		foreach ($investigator_names as $key => $overall_official_name) {
			$facilities[$key] = false;
		}

		// Add investigators from `locations_xml`
		echo('Parsing locations_xml... ');
		$xml = simplexml_load_string(
			$record_data['locations_xml'],
			'SimpleXMLElement', LIBXML_NOWARNING | LIBXML_NOERROR
		);
		if ($xml === false) {
			echo(' Invalid locations_xml. ');
		} 
		else {
			echo(' Adding location-specific investigators... ');
			foreach ($xml->location as $location) {
				$facility = array(
					'name'		=> (string)$location->facility->name,
					'city'		=> (string)$location->facility->address->city,
					'state'		=> (string)$location->facility->address->state,
					'zip'		=> (string)$location->facility->address->zip,
					'country'	=> (string)$location->facility->address->country,
				);
				$num_inv = 0;
				foreach ($location->investigator as $investigator) {
					$num_inv++;
					$investigator_names[] = assemble(' ', array(
						$investigator->first_name, 
						$investigator->middle_name, 
						$investigator->last_name
					));
					$affiliations[] = $facility['name'];
					$facilities[] = $facility;
				}
				$locations[] = array(
					'facility'	=> $facility,
					'num_inv'	=> $num_inv
				);
			}
		}
		
		// Assign Primary location as a facility 
		// to all investigators mentioned in `overall_official_name`
		foreach (array_keys($facilities) as $key) {
			if ($facilities[$key] === false) {
				$facilities[$key] = primary_location($locations, $affiliations[$key]);
			}
		}

		foreach ($investigator_names as $key=>$overall_official_name)
		{
			$overall_official_affiliation = $affiliations[$key];
			$overall_official_name=mysql_real_escape_string($overall_official_name);
			$overall_official_affiliation = mysql_real_escape_string($overall_official_affiliation);
			if (name_is_noisy($overall_official_name)) {
				continue;
			}
			$name_parts = parse_name($overall_official_name);
			if($name_parts['surname'] == "")
				$display_name = $name_parts['first_name'].' '.$name_parts['middle_name'];
			else if($name_parts['first_name'] == "")
				$display_name = $name_parts['surname'].' '.$name_parts['middle_name'];
			else
				$display_name = $name_parts['surname'].', '.$name_parts['first_name'].' '.$name_parts['middle_name'];
			
			
			$query = 'SELECT id FROM entities where class="Investigator"'
					. ' and name = "'.$overall_official_name.'" limit 1';
				
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$res = mysql_fetch_assoc($res);
			$exists = $res !== false;
			
			if(!$exists) {
				$query = 'SELECT id FROM entities where class="Investigator"'
						. ' and first_name = "'.$name_parts['first_name']
						. '" and surname = "'.$name_parts['surname'].'" limit 1';
					
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				$res = mysql_fetch_assoc($res);
				$exists = $res !== false;
			} else {
				$eid = $res['id'];
				
				$query = 'UPDATE IGNORE entities 
					set class="Investigator", name = "'.$overall_official_name.'", display_name = "'.$display_name.'", affiliation = "'.$overall_official_affiliation.'" '
					.', first_name = "'.$name_parts['first_name']
					.'", middle_name = "'.$name_parts['middle_name']
					.'", surname = "'.$name_parts['surname']
					.'", degrees = "'.$name_parts['degrees']
					.'" WHERE id="'.$eid.'"';
					
				if(!mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				
			}
			
			if(!$exists)
			{				
				$query = 'INSERT IGNORE INTO entities 
					set class="Investigator", name = "'.$overall_official_name.'", display_name = "'.$display_name.'", affiliation = "'.$overall_official_affiliation.'" '
					.', first_name = "'.$name_parts['first_name']
					.'", middle_name = "'.$name_parts['middle_name']
					.'", surname = "'.$name_parts['surname']
					.'", degrees = "'.$name_parts['degrees'].'"';

					
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				$eid = mysql_insert_id();
				
				if($eid > 0) {
					$query = 'INSERT INTO entity_trials 
								set entity= "'.$eid.'", trial = "'.$larvol_id.'"';
						
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
				}
				mysql_query('COMMIT');
								
			} else {
				$eid=$res['id'];
				$query = 'SELECT * FROM entity_trials where entity="'.$eid.'" and trial = "'.$larvol_id.'" limit 1'; 
				
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
				{
					
					$query = 'INSERT INTO entity_trials 
									set entity= "'.$eid.'", trial = "'.$larvol_id.'"';
					
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					mysql_query('COMMIT');
				}
				
			}
			add_site($eid, $facilities[$key], $larvol_id);
		}
		$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pid  .'" , update_items_progress= "' . ( ($totalncts >= $updateitems+$counter) ? ($updateitems+$counter) : $totalncts  ) . '" , status="2", current_nctid="'. $larvol_id .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="INVESTIGATOR"  ;' ;
		
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	//	return true;
	}
	return true;
}


// Create facilities and sites and connect the sites to trials.
// 
// A facility is given in the facility section of a location in locations_xml. 
// A site is created from the combination of the investigator and facility given in a single location of locations_xml. 
// One more site is also created from the combination of investigator given in overall_official and the facility of the primary location, if there is one. 
// The primary location is determined by this: 
// (1) If there is only one location, it is the primary. 
// (2) If there is only one location having no entity-specific investigator, it is the primary. 
// (3) Otherwise the primary one is the one having a facility name matching the affiliation of the overall official.
// Any facility is invalid if its name CONTAINS anything matching the name of a company (that is, industry institution. Non-industry insitutions can be valid facility names). 
// Blank fields are invalid.
//
function add_site($eid, $facility, $trial_id)
{
	global $logger;
	if (
		$eid <= 0 ||
		!$facility['name'] ||
		!$facility['city'] ||
		!$facility['country']
	)
	{
		return;
	}
	$company = preg_quote(get_attr('entities', 'company', $eid));
	if ($company && preg_match("/$company/", $facility['name']))
	{
		return;
	}

	mysql_query('START TRANSACTION');

	foreach ($facility as &$v) {
		if (is_string($v)) {
			$v = "'".mysql_real_escape_string($v)."'";
		}
	}
	$query = 
		"INSERT ".
		"INTO `facility` (`name`, `city`, `state`, `zip`, `country`) ".
		"VALUES (".
			implode(', ', array(
				$facility['name'],
				$facility['city'],
				$facility['state'],
				$facility['zip'],
				$facility['country']
			)).
		") ".
		"ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
	if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		mysql_query('ROLLBACK');
		$logger->error($log);
		echo $log;
		return false;
	}
	$fid = mysql_insert_id();

	$query = 
		"INSERT ".
		"INTO `site` (`facility_id`, `investigator_id`) ".
		"VALUES ($fid, $eid) ".
		"ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
	if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		mysql_query('ROLLBACK');
		$logger->error($log);
		echo $log;
		return false;
	}
	$sid = mysql_insert_id();

	$query = 
		"INSERT IGNORE ".
		"INTO `site_trials` (`site_id`, `trial_id`) ".
		"VALUES ($sid, $trial_id)";
	if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		mysql_query('ROLLBACK');
		$logger->error($log);
		echo $log;
		return false;
	}

	mysql_query('COMMIT');
}


// Determine primary location for an overall official represented by their affilitaion
// 
// The primary location is determined by this: 
// (1) If there is only one location, it is the primary. 
// (2) If there is only one location having no entity-specific investigator, it is the primary. 
// (3) Otherwise the primary one is the one having a facility name matching the affiliation of the overall official.
function primary_location($locations, $affiliation)
{
	if (count($locations) == 1) {
		return $locations[0]['facility'];
	}

	$primaries = array();
	foreach ($locations as $l) {
		if ($l['num_inv'] === 0) {
			$primaries[] = $l['facility'];
		}
	}
	if (count($primaries) == 1) {
		return reset($primaries);
	}

	foreach ($locations as $l) {
		if ($l['facility']['name'] === $affiliation) {
			return $l['facility'];
		}
	}

	return FALSE;
}


// Determine if the investigator's name is "noisy" - grounds for investigator disqualification.
// 
// The name is considered "noisy" if the name contains:
// (1) any exclusion word,
// (2) a '@', or 
// (3) the 'name' of any institution in the entities table
function name_is_noisy($name)
{
	if (
		preg_match_all(
			'/(admin|medical|director|trial|monitor|clinical|science|strategy|study)/i', 
			$name
		) > 0
	)
	{
		return TRUE;
	}
	if (strpos($name, '@')) {
		return TRUE;
	}
	// Compare $name to all the institution names in the entities table
	$query = 
		"SELECT COUNT(`name`) ".
		"FROM `entities` ".
		"WHERE '$name' LIKE CONCAT('%', `name`, '%')";
	if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		$logger->error($log);
		echo $log;
		return false;
	}
	$res = mysql_fetch_row($res);
	if ($res[0] > 0) {
		return TRUE;
	}
	return FALSE;
}

			
// Fetch particular column value from given table by row id
function get_attr($table, $attribute_name, $id)
{
	global $logger;
	if (!is_int($id)) {
		return false;
	}
	$query = 
		"SELECT `$attribute_name` ".
		"FROM `$table` ".
		"WHERE `id`=$id ".
		"LIMIT 1";
	if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		$logger->error($log);
		echo $log;
		return false;
	}
	$res = mysql_fetch_row($res);
	return $res[0];
}


function parse_name($name)
{
	$parts["first_name"]	= '';
	$parts["middle_name"]	= '';
	$parts["surname"] 	= '';
	$parts["degrees"] 	= '';

	# 1.
	$name = preg_replace("/[.()]/", "", $name);

	# 2.
	$name_degree = explode(",", $name, 2);
	if (count($name_degree) == 2) {
		$name = $name_degree[0];
		$parts["degrees"] = $name_degree[1];
	}
	else {
		$parts["degrees"] = "";
	}

	# 3.
	$name = trim($name);
	if (preg_match('/^(.+)\s([^\s]+)\s*$/', $name, $matches)) {
		$parts["surname"] = $matches[2];
		$name = $matches[1];
	}
	else {
		$parts["surname"] = $name;
		$name = "";
	}
	
	# 4.
	$name = trim($name);
	$name_middlename = explode(" ", $name, 2);
	if (count($name_middlename) == 2) {
		$parts["first_name"] = $name_middlename[0];
		$parts["middle_name"] = $name_middlename[1];
	}
	else {
		$parts["first_name"] = $name;
	}

	# 5.
	foreach ($parts as &$val) {
		$val = trim($val);
		$val = preg_replace("/,/", " ", $val);
		$val = preg_replace("/ +/", " ", $val);
	}

	return $parts;
}





?>
