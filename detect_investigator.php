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
			return false;
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
			return false;
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
				continue;
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
		
		foreach ($investigator_names as $key=>$overall_official_name)
		{
			$overall_official_affiliation = $affiliations[$key];
			$overall_official_name=mysql_real_escape_string($overall_official_name);
			$overall_official_affiliation = mysql_real_escape_string($overall_official_affiliation);
			$failwords = preg_match_all('/(admin|medical|director|trial|monitor|clinical|science|strategy|study)/i', $overall_official_name);
			if($failwords > 0) continue;
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
		}
		$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pid  .'" , update_items_progress= "' . ( ($totalncts >= $updateitems+$counter) ? ($updateitems+$counter) : $totalncts  ) . '" , status="2", current_nctid="'. $larvol_id .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="INVESTIGATOR"  ;' ;
		
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	//	return true;
	}
	return true;
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
