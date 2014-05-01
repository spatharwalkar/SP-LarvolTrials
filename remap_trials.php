<?
error_reporting(E_ERROR);
require_once('db.php');
require_once('include.util.php');
require_once ('include.derived.php');
require_once('preindex_trial.php');
require_once ('include.import.php');
ini_set('max_execution_time', '9000000'); //250 hours
ignore_user_abort(true);
global $db;
global $logger;
//remaptrials(null,null,'ALL');
function remaptrials($source_id=NULL, $larvolid=NULL,  $sourcedb=NULL, $storechanges=NULL  )
{
	global $logger;
	if(isset($source_id)) // A single trial
	{
		if(strlen($source_id)<=10) 
			$trial=padnct($source_id);
		else 
			$trial = $source_id;
		$query = 'SELECT `larvol_id` FROM data_trials where `source_id` like "' . $trial . '%"  LIMIT 1';
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
	elseif(isset($larvolid)) // A single larvol_id
	{
		$trial=$larvolid;
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
	elseif(isset($sourcedb) and $sourcedb=="ALL")  // Entire database
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
		while ($row = mysql_fetch_assoc($res)) 
		{
			$larvol_ids[] = $row[larvol_id];
		}
		asort($larvol_ids);
	}

	elseif(isset($sourcedb) and !empty($sourcedb))  // single data source (eg. data_nct, data_eudract etc.)
	{
		$source='data_'.$sourcedb;
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

			$fid = getFieldId('NCT','nct_id');
			
			$cid = 0; 
			$cid_=$cid;
			$pid = getmypid();
			$totalncts=count($larvol_ids);
			
			
			$query = 'INSERT into update_status_fullhistory ( process_id,status,update_items_total,start_time,max_nctid,trial_type) 
					  VALUES ("'. $pid .'","'. 2 .'",
					  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'", "REMAP"  ) ;';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
			$up_id=mysql_insert_id();
			
			
	//		echo('Remapping from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
			echo('<br>Current time ' . date('Y-m-d H:i:s', strtotime('now')) . '<br>');
			echo str_repeat ("  ", 4000);
			$i=1;
			
		}

		/* STATUS */


	if(!isset($cid)) $cid = 0; 	


	if(!isset($larvol_ids)) $larvol_ids=array($larvol_id);
	
	$orig_larvol_id=$larvol_id;
	
	
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
		if(isset($sourcedb) and $sourcedb=='eudract')
			$query = 'SELECT * FROM data_eudract where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		else
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
		
		if(isset($sourcedb) and $sourcedb=='eudract') 
			$nctid=$res['nct_id'];
		else
			$nctid=padnct($res['nct_id']);
		$record_data = $res;
		if($exists)
		{
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
		}
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
			
		}
		
		// changed condition so that field primary_completion_date takes precedence over completion_date.
		if(isset($pc_date) and !is_null($pc_date)) $end_date=$pc_date;
		else $end_date=$c_date;
		

		$i=0;
		foreach($record_data as $fieldname => $value)
		{
			if(!remap($larvol_id, $fieldname, $value,$record_data['lastchanged_date'],$oldtrial,NULL,$end_date,$phase_value,$sourcedb))
			logDataErr('<br>Could not save the value of <b>' . $fieldname . '</b>, Value: ' . $value );//Log in errorlog
			$i++;
			
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
		
		$inactive=null;
		
		$query = 'SELECT overall_status FROM data_manual where `larvol_id`="' . $larvol_id . '"  LIMIT 1';
		
			if(!$res2 = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$res2 = mysql_fetch_assoc($res2);
			
			
			if($res2['overall_status'])
				$record_data['overall_status']=$res2['overall_status'];
		if(isset($record_data['overall_status']))
		{
			$x=array_search($record_data['overall_status'],$inactiveStatus);
			if($x) $inactive=0; else $inactive=1;
		}
		/************* store history incase of value change */
					$query = 'SELECT institution_type, `region`,is_active  FROM data_trials WHERE `larvol_id`="'. $larvol_id . '" limit 1';
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					$row = mysql_fetch_assoc($res);
					$olddate=mysql_real_escape_string($row['lastchanged_date']);
					$oldval1=mysql_real_escape_string($row['institution_type']);
					$oldval2=mysql_real_escape_string($row['region']);
					$oldval3=mysql_real_escape_string($row['is_active']);

					
			
						if( !empty($ins_type) or !empty($region) or !is_null($inactive) )
						{
							$value1=mysql_real_escape_string($ins_type);
							$value2=mysql_real_escape_string($region);
							$value3=$inactive;
						}
					
						if(empty($value1)) $str1=" institution_type = null "; else $str1=' institution_type = "' . $value1 .'"';
						if(empty($value2)) $str2=", region = null "; else $str2=', region = "' . $value2 .'"';
						
						
						if(is_null($value3)) $str3="  "; elseif($value3==1 or $value3==0) $str3=', is_active = "' . $value3 .'"';
						
						
					$query = 'update data_trials set '. $str1 . $str2 . $str3  .'  , lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;
					if(!mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						mysql_query('ROLLBACK');
						echo $log;
						return false;
					}
					
					/* UPDATE owner sponsored institution type ==> DISABLED THIS AS LOGIC IS INCORRECT 
					
					//see if the trial has a sponsor
					$query="select lead_sponsor from data_trials where larvol_id='" .$larvol_id . "' limit 1";
					$res=mysql_query($query);
					$row = mysql_fetch_assoc($res);
					$sponsor=$row['lead_sponsor'];
					if(!empty($sponsor))
					{
						//get all products related to this trial
						$query="select et.entity from entity_trials et, entities e where et.trial='" .$larvol_id . "' and et.entity=e.id and e.class='product'";
						$res=mysql_query($query);
						$productids=array();
						while($row = mysql_fetch_assoc($res)) 
						{ 
							$productids[] = $row['entity'];
						}	
						
						//get all companies associated with these products
						$pids = implode(",", $productids);
						if(!empty($productids))
						{
							$query="select er.child from entity_relations er,entities e 
									where er.parent in (" . $pids . ")
									and er.child=e.id and e.class = 'Institution'";
							$res=mysql_query($query);
							$companyids=array();
							while($row = mysql_fetch_assoc($res)) 
							{ 
								$companyids[] = $row['child'];
							}	
							//get name,search_name of these companies 
							$cids = implode(",", $companyids);
							$query="select name,search_name from entities where id in (" . $cids . ")	and class='institution'";
							$res=mysql_query($query);
							$csearchnames=array();
							while($row = mysql_fetch_assoc($res)) 
							{ 
								//if searchname has multiple names then store each of them separately into the array
								if(stripos($row['search_name'],"|"))
								{
									$searchname=explode("|", $row['search_name']);
									foreach($searchname as $name) $csearchnames[] = $name;
								}
								else $csearchnames[]=$row['search_name'];
								
								$csearchnames[] = $row['name'];
								
							}
							//now loop through the list and see if any of them matches with the trial's sponsor name
							$ownersponsored='No';
							foreach($csearchnames as $name)
							{
								$sponsor='xxx.'.$sponsor;
								$pos = stripos($sponsor,trim($name));
								if(!empty($pos) and $pos>0 )	
								{
									$ownersponsored='Yes';
									break;
								}
							}
							if($ownersponsored=='Yes')
							{
								$query="update data_trials set institution_type = 'owner_sponsored' where larvol_id='" .$larvol_id . "' limit 1 ";
								$res=mysql_query($query);
							}
						}
						
					}
					************************/
						
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

					$oldval1=str_replace("\\", "", $oldval1);
					$oldval2=str_replace("\\", "", $oldval2);
					$oldval3=str_replace("\\", "", $oldval3);
					$value1=str_replace("\\", "", $value1);
					$value2=str_replace("\\", "", $value2);
					$value3=str_replace("\\", "", $value3);
					
					if (trim($oldval1)<>trim($value1) and !empty($oldval1)) 
						{
							$str1 = ' institution_type_prev = "'. $oldval1 .'", institution_type_lastchanged = "' . $olddate .'"'; 
							$comma=", ";
						}	
					else 
						{
							$str1 = "";
							$comma="";
						}
					if (trim($oldval2)<>trim($value2) and !empty($oldval2))
						{
							$str2 = $comma . ' region_prev = "'. $oldval2 .'", region_lastchanged = "' . $olddate .'"'; 
							$comma=", ";
						}	
					elseif(strlen(str1)<5) 
						{
							$str2 = "";
							$comma="";
						}
					else
						{
							$str2 = "";
							$comma=", ";
						}
					if (trim($oldval3)<>trim($value3) and !empty($oldval3)) 
						$str3 = $comma . ' is_active_prev = "'. $oldval3 .'", is_active_lastchanged = "' . $olddate .'"';  else $str3 = "";
					$cond1 = strlen($str1.$str2.$str3)>5;
					if($exists and $cond1 and (isset($storechanges) && $storechanges=='YES') )
					{
						$query = 'update data_history set ' . $str1.$str2.$str3  .'  where `larvol_id`="' .$larvol_id . '" limit 1' ;
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
						if(  $cond1  and (isset($storechanges) && $storechanges=='YES') )
						{
						$query = 'insert into data_history set ' . $str1.$str2.$str3  .'  limit 1' ;
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

		
		/*****************/
		
			echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Remapping of trial : ' . $nctid . ' completed.' .   str_repeat("     ",300) );
			
			tindex($nctid,'products');
			tindex($nctid,'areas');
			echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Preindexing of trial : ' . $nctid . ' completed.' .   str_repeat("     ",300) );
			if(!isset($updateitems)) $updateitems=0;
			$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pid  .'" , update_items_progress= "' . ( ($totalncts >= $updateitems+$counter) ? ($updateitems+$counter) : $totalncts  ) . '" , status="2", current_nctid="'. $larvol_id .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="REMAP"  ;' ;
			
			$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	//	return true;
	}
	return true;
}
function remap($larvol_id, $fieldname, $value,$lastchanged_date,$oldtrial,$ins_type,$end_date,$phase_value,$sourcedb=null)
{
	
	$lastchanged_date = normal('date',$lastchanged_date);
	global $logger,$nctid;
	$DTnow = date("Y-m-d H:i:s", strtotime('now'));
	
	require_once('field_mappings.php');
	if( ($fieldname=='source_id' and substr($value,0,3)<>'NCT') or $fieldname=='eudract_id' ) //Eudract trial. Set its completion date using global end date
	{
		$fieldvalue=get_field_value($larvol_id,  'end_date_global', 'EUDRACT');
		update_history($larvol_id,'completion_date',$fieldvalue,$lastchanged_date);
	}
	elseif( ($fieldname=='source_id' and substr($value,0,3)=='NCT')  or $fieldname=='nct_id'  ) //NCT trial. 
	{
				$fieldvalue=get_field_value($larvol_id,  'completion_date', 'NCT');
				update_history($larvol_id,'completion_date',$fieldvalue,$lastchanged_date);
				$fieldvalue=get_field_value($larvol_id,  'primary_completion_date', 'NCT');
				update_history($larvol_id,'primary_completion_date',$fieldvalue,$lastchanged_date);
			
	}
	$fieldvalue=get_field_value($larvol_id,  $fieldname, $sourcedb);
	update_history($larvol_id,$fieldname,$fieldvalue,$lastchanged_date);

	//normalize the input
	// Commented existing remap code, will now use the new centralized function get_field_value() to get values.
	/*
	if(!is_array($value)) 
	{
		
		$tt=strpos('a'.$fieldname, "_date")  ;
		if(isset($tt) and !empty($tt))
		{
			$value = normal('date',$value);
		}
		elseif(is_numeric($value)) $value = normal('int',(int)$value); 
		else   $value = preg_replace( '/\s+/', ' ', trim( $value ) );
		if($fieldname=="phase") $value=$phase_value;
	}
	
	elseif(is_numeric($value[0])) $value=max($value); 
	elseif($fieldname=="phase") $value=max($phase_value);
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
	if(isset($sourcedb) and $sourcedb=='eudract') 
	{
		$dn_array=array
		(
	   'dummy', 'larvol_id'  ,'national_competent_authority'  ,'trial_type'  ,'trial_status'  ,'start_date' ,'firstreceived_date' ,'member_state_concerned'  ,'eudract_id'  ,'full_title'  ,'lay_title'  ,'abbr_title'  ,'sponsor_protocol_code'  ,'isrctn_id'  ,'nct_id'  ,'who_urtn'  ,'other_name'  ,'other_id'  ,'is_pip'  ,'pip_emad_number'  ,'sponsor_name'  ,'sponsor_country'  ,'sponsor_status'  ,'support_org_name'  ,'support_org_country'  ,'contact_org_name'  ,'contact_point_func_name'  ,'street_address'  ,'city'  ,'postcode'  ,'country'  ,'phone'  ,'fax'  ,'email'  ,'imp_role'  ,'imp_auth'  ,'imp_trade_name'  ,'marketing_auth_holder'  ,'marketing_auth_country'  ,'imp_orphan'  ,'imp_orphan_number'  ,'product_name'  ,'product_code'  ,'product_pharm_form'  ,'product_paediatric_form'  ,'product_route'  ,'inn'  ,'cas'  ,'sponsor_code'  ,'other_desc_name'  ,'ev_code'  ,'concentration_unit'  ,'concentration_type'  ,'concentration_number' ,'imp_active_chemical'  ,'imp_active_bio'  ,'type_at'  ,'type_somatic_cell'  ,'type_gene'  ,'type_tissue'  ,'type_combo_at'  ,'type_cat_class'  ,'type_cat_number'  ,'type_combo_device_not_at'  ,'type_radio'  ,'type_immune'  ,'type_plasma'  ,'type_extract'  ,'type_recombinant'  ,'type_gmo'  ,'type_herbal'  ,'type_homeopathic'  ,'type_other'  ,'type_other_name'  ,'placebo_used'  ,'placebo_form'  ,'placebo_route'  ,'condition'  ,'lay_condition'  ,'therapeutic_area'  ,'dra_version'  ,'dra_level'  ,'dra_code'  ,'dra_organ_class'  ,'dra_rare'  ,'main_objective'  ,'secondary_objective'  ,'has_sub_study'  ,'sub_studies'  ,'inclusion_criteria'  ,'exclusion_criteria'  ,'primary_endpoint'  ,'primary_endpoint_timeframe'  ,'secondary_endpoint'  ,'secondary_endpoint_timeframe'  ,'scope_diagnosis'  ,'scope_prophylaxis'  ,'scope_therapy'  ,'scope_safety'  ,'scope_efficacy'  ,'scope_pharmacokinectic'  ,'scope_pharmacodynamic'  ,'scope_bioequivalence'  ,'scope_dose_response'  ,'scope_pharmacogenetic'  ,'scope_pharmacogenomic'  ,'scope_pharmacoeconomic'  ,'scope_other'  ,'scope_other_description'  ,'tp_phase1_human_pharmacology'  ,'tp_first_administration_humans'  ,'tp_bioequivalence_study'  ,'tp_other'  ,'tp_other_description'  ,'tp_phase2_explatory'  ,'tp_phase3_confirmatory'  ,'tp_phase4_use'  ,'design_controlled'  ,'design_randomised'  ,'design_open'  ,'design_single_blind'  ,'design_double_blind'  ,'design_parallel_group'  ,'design_crossover'  ,'design_other'  ,'design_other_description'  ,'comp_other_products'  ,'comp_placebo'  ,'comp_other'  ,'comp_descr'  ,'comp_number_arms'  ,'single_site'  ,'multi_site'  ,'number_of_sites'  ,'multiple_member_state'  ,'number_sites_eea'  ,'eea_both_inside_outside'  ,'eea_outside_only'  ,'eea_inside_outside_regions'  ,'has_data_mon_comm'  ,'definition_of_end'  ,'dur_est_member_years'  ,'dur_est_member_months'  ,'dur_est_member_days'  ,'dur_est_all_years'  ,'dur_est_all_months'  ,'dur_est_all_days'  ,'age_has_under18'  ,'age_number_under18'  ,'age_has_in_utero'  ,'age_number_in_utero'  ,'age_has_preterm_newborn'  ,'age_number_preterm_newborn'  ,'age_has_newborn'  ,'age_number_newborn'  ,'age_has_infant_toddler'  ,'age_number_infant_toddler'  ,'age_has_children'  ,'age_number_children'  ,'age_has_adolescent'  ,'age_number_adolescent'  ,'age_has_adult'  ,'age_number_adult'  ,'age_has_elderly'  ,'age_number_elderly'  ,'gender_female'  ,'gender_male'  ,'subjects_healthy_volunteers'  ,'subjects_patients'  ,'subjects_vulnerable'  ,'subjects_childbearing_no_contraception'  ,'subjects_childbearing_with_contraception'  ,'subjects_pregnant'  ,'subjects_nursing'  ,'subjects_emergency'  ,'subjects_incapable_consent'  ,'subjects_incapable_consent_details'  ,'subjects_other'  ,'subjects_other_details'  ,'enrollment_memberstate'  ,'enrollment_intl_eea'  ,'enrollment_intl_all'  ,'aftercare'  ,'inv_network_org'  ,'inv_network_country'  ,'committee_third_first_auth'  ,'committee_first_auth_third'  ,'review_decision'  ,'review_decision_date'  ,'review_opinion'  ,'review_opinion_reason'  ,'review_opinion_date'  ,'end_status'  ,'end_date_global' 
	   );
	}
	else
	{
		$dn_array=array
		(
			'dummy', 'larvol_id', 'nct_id', 'download_date', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'lead_sponsor_class', 'collaborator', 'collaborator_class', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'completion_date', 'completion_date_type', 'primary_completion_date', 'primary_completion_date_type', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'biospec_retention', 'biospec_descr', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'contact_name', 'contact_phone', 'contact_phone_ext', 'contact_email', 'backup_name', 'backup_phone', 'backup_phone_ext', 'backup_email', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'responsible_party_name_title', 'responsible_party_organization', 'org_study_id', 'phase', 'nct_alias', 'condition', 'secondary_id', 'oversight_authority', 'rank', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name', 'intervention_other_name', 'intervention_description', 'link_url', 'link_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'reference_citation', 'reference_PMID', 'results_reference_citation', 'results_reference_PMID', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'location_status', 'location_contact_name', 'location_contact_phone', 'location_contact_phone_ext', 'location_contact_email', 'location_backup_name', 'location_backup_phone', 'location_backup_phone_ext', 'location_backup_email', 'locations_xml', 'investigator_name', 'investigator_role', 'overall_official_name', 'overall_official_role', 'overall_official_affiliation', 'keyword', 'is_fda_regulated', 'is_section_801'
		);
	}
		$dt_array=array
			(
				'dummy', 'larvol_id', 'source_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'is_active', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type',  'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name',  'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'locations_xml', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
			);
		$dm_array=array
			(
				'dummy', 'larvol_id', 'brief_title', 'acronym', 'official_title', 'lead_sponsor', 'collaborator', 'institution_type', 'source', 'has_dmc', 'brief_summary', 'detailed_description', 'overall_status', 'why_stopped', 'start_date', 'end_date', 'study_type', 'study_design', 'number_of_arms', 'number_of_groups', 'enrollment', 'enrollment_type', 'study_pop', 'sampling_method', 'criteria', 'gender', 'minimum_age', 'maximum_age', 'healthy_volunteers', 'verification_date', 'lastchanged_date', 'firstreceived_date', 'org_study_id', 'phase', 'condition', 'secondary_id', 'arm_group_label', 'arm_group_type', 'arm_group_description', 'intervention_type', 'intervention_name',  'intervention_description', 'primary_outcome_measure', 'primary_outcome_timeframe', 'primary_outcome_safety_issue', 'secondary_outcome_measure', 'secondary_outcome_timeframe', 'secondary_outcome_safety_issue', 'location_name', 'location_city', 'location_state', 'location_zip', 'location_country', 'region', 'keyword', 'is_fda_regulated', 'is_section_801'
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
				$overridden_enddate=null;
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
						if($fieldname=='phase' and ( empty($row[$fieldname]) or $row[$fieldname]=='N/A' ) )
						{
							$value=mysql_real_escape_string($phase_value);
						}
						else
						{
							$value=mysql_real_escape_string($row[$fieldname]);
						}
						if($fieldname=='end_date' and  !empty($row[$fieldname]) )
						{
							$value=mysql_real_escape_string($row[$fieldname]);
							$overridden_enddate=$value;
						}
					}

				}
				
				update_history($larvol_id,$fieldname,$value,null);
								
							
			}
		
	
	
		*/
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
		if(!empty($overridden_enddate)) $cdate=$overridden_enddate;
		/*********/
		if( !is_null($cdate) and  $cdate <>'0000-00-00' )	// completion date
		{
			$cdate=normalize('date',$cdate);
		}
		
		elseif( !is_null($pcdate) and  $pcdate <>'0000-00-00') 	// primary completion date
		{
		
			$pcdate=normalize('date',$pcdate);
				
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
			
			}

		}
		
		/*************/
		
				
		if( !is_null($pcdate) and  $pcdate <>'0000-00-00' and $fieldname=='end_date') 	// primary completion date
		{
		
			$pcdate=normalize('date',$pcdate);
			update_history($larvol_id,'end_date',$pcdate,$lastchanged_date);
		}
		elseif( !is_null($cdate) and  $cdate <>'0000-00-00' and $fieldname=='end_date')	// completion date
		{
			$cdate=normalize('date',$cdate);
			update_history($larvol_id,'end_date',$cdate,$lastchanged_date);
					
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
				update_history($larvol_id,'end_date',$cdate,$lastchanged_date);
					
			}
			
			elseif($fieldname=='end_date')	// replace with null
			{
				update_history($larvol_id,'end_date',null,$lastchanged_date);
			}
				
			
		}
		//
		if($fieldname=='primary_completion_date')  // can use any fieldname here, this to ensure that the updation is done only once.
		{

			update_history($larvol_id,'inclusion_criteria',$str['inclusion'],$lastchanged_date);
			update_history($larvol_id,'exclusion_criteria',$str['exclusion'],$lastchanged_date);
		}


		// get value using the new centralized function

		return true;

}

function update_history($larvol_id,$fld,$val,$lastchanged_date)
{

			if(empty($larvol_id) or empty($fld) or empty($val)) return "";
			$query = 'SELECT `' . $fld . '` FROM data_trials WHERE `larvol_id`="'. $larvol_id . '" limit 1';
			if(!$res = mysql_query($query)) return false;
			$row = mysql_fetch_assoc($res);
			$oldval1=mysql_real_escape_string($row[$fld]);
			$value1=mysql_real_escape_string($val);
		
			if(empty($value1)) $str1='`'.$fld .'`'. "  = null "; else $str1= '`'.$fld .'`'. ' = "' . $value1 .'"';

			 if(empty($lastchanged_date)) $lastchanged_date = (string)date("Y-m-d", strtotime('now'));
			 
			 
			$query = 'update data_trials set '. $str1 . '  , lastchanged_date = "' .$lastchanged_date.'" where larvol_id="' .$larvol_id . '"  limit 1' ;

			if(!mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				mysql_query('ROLLBACK');
				echo $log;
				return false;
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

			$oldval1=str_replace("\\", "", $oldval1);
			$value1=str_replace("\\", "", $value1);
			
			if (trim($oldval1)<>trim($value1)) 
				{
					$str1 = $fld .'_prev' . ' = "'. $oldval1 .'", '. $fld . '_lastchanged = "' . $lastchanged_date .'"'; 
				}
			else $str1="";	
			
			$cond1 = strlen($str1)>5;
			if($exists and $cond1  and (isset($storechanges) && $storechanges=='YES') )
			{
				$query = 'update data_history set ' . $str1  .'  where `larvol_id`="' .$larvol_id . '" limit 1' ;
				if(!mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					mysql_query('ROLLBACK');
					echo $log;
					return false;
				}

			}
			else
			{
				if(  $cond1  and (isset($storechanges) && $storechanges=='YES') )
				{
				$query = 'insert into data_history set ' . $str1 .' , larvol_id="' .$larvol_id . '"' ;
				if(!mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					//$logger->error($log);
					mysql_query('ROLLBACK');
					echo $log;
					return false;
				}
				}
			}
}
?>