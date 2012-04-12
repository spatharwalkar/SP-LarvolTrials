<?php
require_once('db.php');

require_once('include.search.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once('class.phpmailer.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
if(isset($_GET['direct_run_heatmap_id'])) runHeatmap((int)$_GET['direct_run_heatmap_id'], false);

/* Runs the heatmap with the given ID, outputs to Excel
	If $return is true, the file is returned from this function
	If $return is false, it will go to the browser in a download.
	$format is "xlsx" for Excel and "doc" for Word
*/

function runHeatmap($id, $return = false, $format = "xlsx", $expire = false)
{

	//variables used for report status
	global $run_id;
	global $report_type;
	global $type_id;
	global $logger;
	/*
	if($return)
	{
		//Get variables corresponding to the primary key in reports_status
		if(isset($_GET['run_id']))
		{
			$run_id = (int)$_GET['run_id'];
		}else{
			$logger = 'Need to set $_GET[\'run_id\']';
			$logger->fatal($log);
			die($log);
		}
		
		if(isset($_GET['report_type']))
		{
			$report_type = (int)$_GET['report_type'];
		}else{
			$log = 'Need to set $_GET[\'report_type\']';
			$logger->fatal($log);
			die($log);
		}
		
		if(isset($_GET['type_id']))
		{
			$type_id = (int)$_GET['type_id'];
		}else{
			$log = 'Need to set $_GET[\'type_id\']';
			$logger->fatal($log);
			die($log);
		}
	}
	*/
	global $now;
	global $db;
	global $SEARCH_ERR;
	if(!$return)
	{ 
		ignore_user_abort(true);
		//the connection-close code is only here because the connection_closed check we do later doesn't work (PHP bug)
		//so we have to just assume it's closed. And of course close it here to safeguard that assumption.
		/*ob_start();
		echo('Report will be sent by email');
		$size = ob_get_length();
		header("Content-Length: $size");
		header('Connection: close');
		ob_end_flush();
		ob_flush();
		@flush();*/
	}

	if(!is_numeric($id)) tex('non-numeric id!');
	$nodata = array('action'=>array(), 'searchval'=>array(), 'negate'=>array(), 'multifields'=>array(), 'multivalue'=>array());

	//link generation variable
	$link_generation_method = 'db';
	if($link_generation_method == 'db')
		$boundary = 5000;
	else
		$boundary = 500;
				
	//get report name
	$query = 'SELECT name,footnotes,description,searchdata,bomb,backbone_agent,count_only_active,id FROM rpt_heatmap WHERE id=' . $id . ' LIMIT 1';
	$time_start = microtime(true);
	$resu 		= mysql_query($query) or tex('Bad SQL query getting report name and other details ' . $query);
	$time_end 	= microtime(true);
	$time_taken = $time_end-$time_start;
	$log 		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() get report name and other details.';
	$logger->info($log);
	unset($log);
	
	$info = mysql_fetch_array($resu) or tex('Report not found. ' . $query); 
	$name = $info['name'];
	$footnotes = $info['footnotes'];
	$description = $info['description'];
	$oversearch = ($info['searchdata']===NULL?$nodata:removeNullSearchdata(unserialize(base64_decode($info['searchdata']))));
	
	$bomb 			= $info['bomb'] == 'Y';
	$backboneAgent 	= $info['backbone_agent'] == 'Y';
	$countactive 	= $info['count_only_active'] == 'Y';
	
	//options selected array
	$optionsSelected = array();
	$optionsSelected['bomb'] = $bomb;
	$optionsSelected['backboneAgent'] = $backboneAgent;
	$optionsSelected['countactive'] = $countactive;
	
	unset($oversearch['search']);
	unset($oversearch['display']);
	unset($oversearch['page']);
	if(strlen($name) == 0) $name = 'Report ' . $id;

	$intervention_name_field_id = '_' . getFieldId('NCT', 'intervention_name');
	
	//Get headers
	$rows = array();
	$columns = array();
	$rowsearch = array();
	$columnsearch = array();
	$query = 'SELECT `header`,`num`,`type`,searchdata FROM rpt_heatmap_headers WHERE report=' . $id;
	$time_start = microtime(true);
	$resu = mysql_query($query) or tex('Bad SQL query getting headers ' . $query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() getting headers.';
	$logger->info($log);
	unset($log);	
	while($header = mysql_fetch_array($resu))
	{
		if(!strlen($header['header'])) $header['header'] = $header['type'] . ' ' . $header['num'];
		$var = $header['type'] . 's';
		${$var}[$header['num']] = $header['header'];
		$ovar = $header['type'] . 'search';
		$unpacked = unserialize(base64_decode($header['searchdata']));
		if(!is_array($unpacked['multifields'])) $unpacked['multifields'] = array(); 
		${$ovar}[$header['num']] = ($header['searchdata']===NULL ? $nodata : removeNullSearchdata($unpacked));
	}
	//start progress bar
	$pid = NULL;
	if(!$return)
	{
		$query = 'INSERT INTO progress SET created=NOW(),user=' . $db->user->id . ',what="heatmap",max='
				. (count($rows) * count($columns)) . ',note=' . $id;
		mysql_query($query);
		$pid = mysql_insert_id();
	}
	else
	{
		$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'", total="' .(count($rows) * count($columns)).
		'", progress="0" WHERE run_id="' .$run_id .'" AND report_type ="0" AND type_id="' .$type_id .'"';
		if(!mysql_query($query))
		{
			$log =  'Bad SQL Query updating heatmap report total. Error: ' . $query . "\n" . mysql_error();
			$logger->fatal($log);
			die($log);
		}
	}

	//get searchdata
	$searchdata = array();
	$query = 'SELECT `row`,`column`,`searchdata` FROM rpt_heatmap_cells WHERE report=' . $id;
	$time_start = microtime(true);
	$resu = mysql_query($query) or tex('Bad SQL query getting searchdata ' . $query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() getting searchdata.';
	$logger->info($log);
	unset($log);
	
	while($cell = mysql_fetch_array($resu))
	{
		if(!isset($searchdata[$cell['row']])) $searchdata[$cell['row']] = array();
		$unpack = unserialize(base64_decode($cell['searchdata']));
		$searchdata[$cell['row']][$cell['column']] = removeNullSearchdata($unpack);
	} 
	
	$maxrow = max(array_keys($rowsearch));
	$maxcol = max(array_keys($columnsearch));
	
	for($row = 1; $row <= $maxrow; ++$row)
		for($col = 1; $col <= $maxcol; ++$col)
			if(!isset($searchdata[$row][$col]))
				$searchdata[$row][$col] = $nodata;

	//get search results
	$phases = array('N/A', 'Phase 0', 'Phase 0/Phase 1', 'Phase 1', 'Phase 1a', 'Phase 1b', 'Phase 1a/1b', 'Phase 1c', 'Phase 1/Phase 2', 'Phase 1b/2', 
					'Phase 1b/2a', 'Phase 2','Phase 2a', 'Phase 2a/2b', 'Phase 2a/b', 'Phase 2b', 'Phase 2/Phase 3', 'Phase 2b/3','Phase 3', 'Phase 3a', 
					'Phase 3b', 'Phase 3/Phase 4', 'Phase 3b/4', 'Phase 4');
	$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
	$phase_legend_nums = array('N/A', '0', '0/1', '1', '1/2', '2', '2/3', '3', '3/4', '4');
	//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
	$p_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', '99CC00', '99CC00', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 
	'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF9900', 'FF9900', 'FF9900', 'FF0000', 'FF0000', 'FF0000');
	$phase_legend_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF0000', 'FF0000');
	$phase_fid = getFieldId('NCT','phase');
	$phase_enumvals = array();
	foreach($phases as $pkey => $pval)
	{
		$query = 'SELECT id FROM data_enumvals WHERE field=' . $phase_fid . ' AND value="' . $pval . '"';
		$time_start = microtime(true);
		$res = mysql_query($query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() get phase enum value.';
		$logger->info($log);
		unset($log);
		if($res === false) return softDie('Bad SQL query getting a phase enum value ' . $query);
		$res = mysql_fetch_assoc($res); if($res === false) return softDie('Phase not found.');
		$phase_enumvals[$pkey] = $res['id'];
	}
	/*
	In CD this fills in the hasPhase array
	-
	-
	*/
	
	$results = array();
	$row_upms = array();$col_upms = array();
	foreach($searchdata as $row => $rowData)
	{ 	
		foreach($rowData as $column => $cell)
		{ 
			
//			$time_machine = array();//unsetting the array for every cell.
			$time_machine = $now;
			//get searchdata
			$globalparams = array('action' => $oversearch['action'], 'searchval' => $oversearch['searchval'], 
									'negate' => $oversearch['negate'], 'multifields' => $oversearch['multifields'], 
									'multivalue' => $oversearch['multivalue'], 'weak' => $oversearch['weak']);
			$globalparams = prepareParams($globalparams);
			
			$columnparams = array('action' => $columnsearch[$column]['action'], 
									'searchval' => $columnsearch[$column]['searchval'],
									'negate' => $columnsearch[$column]['negate'], 
									'multifields' => $columnsearch[$column]['multifields'], 
									'multivalue' => $columnsearch[$column]['multivalue'], 'weak' => $columnsearch[$column]['weak']);
			$columnparams = prepareParams($columnparams);
									
			$rowparams = array('action' => $rowsearch[$row]['action'], 'searchval' => $rowsearch[$row]['searchval'], 
									'negate' => $rowsearch[$row]['negate'],
									'multifields' => $rowsearch[$row]['multifields'], 
									'multivalue' => $rowsearch[$row]['multivalue'], 'weak' => $rowsearch[$row]['weak']);
			$rowparams = prepareParams($rowparams);
			
			$cellparams = array('action' => $cell['action'], 'searchval' => $cell['searchval'], 
									'negate' => $cell['negate'], 'multifields' => $cell['multifields'], 
									'multivalue' => $cell['multivalue'], 'weak' => $cell['weak']);
			$cellparams = prepareParams($cellparams);
			$params = array_merge($globalparams, $columnparams, $rowparams, $cellparams);
			
			//in case of any one of the array has timemachine parameters defined
			if(strlen($cell['time_machine']) || strlen($rowsearch[$row]['time_machine']) || 
								strlen($columnsearch[$column]['time_machine']) || strlen($oversearch['time_machine'])) {
					
					array_push($time_machine, $cell['time_machine'], $rowsearch[$row]['time_machine'], 
					$columnsearch[$column]['time_machine'], $oversearch['time_machine']);
					$time_machine = array_filter($time_machine);	//removing empt values of the array
					usort($time_machine, "cmpdate"); //sorting the array
					sort($time_machine); //sorting the array further for time precision
			//		$time_machine = strtotime(end($time_machine)); //getting the latest date
					$time_machine = $now;
		
			} else { //in case of timemachine  parameters not defined
				$time_machine = $now;
			}
					
			$override = $oversearch['override'] . ',' . $columnsearch[$column]['override'] . ','
						. $rowsearch[$row]['override'] . ',' . $cell['override'];
			$override = explode(',', $override);
			
			if($override === false)
			{
				$override = array();
			}else{
				foreach($override as $key => $value)
				{
					$value = nctidToLarvolid($value);
					if($value === false)
					{
						unset($override[$key]);
					}else{
						$override[$key] = $value;
					}
				}
			}
			
			if(empty($params))	continue;
			if(array_filter_recursive($params,'nonempty') == array_filter_recursive($globalparams,'nonempty')) continue;
			foreach($params as $key => $sp)	//remove sorts
			{
				if(in_array($sp->action,array('ascending','descending'))) unset($params[$key]);
			}
			
			if(!isset($results[$row])) $results[$row] = array();
			$results[$row][$column] = new Result();

			mysql_query('BEGIN') or tex("Couldn't begin SQL transaction");
			
			//get record IDs 
			$all_ids = search($params,array(),NULL,$time_machine,$override);
			
			if($all_ids === false)
			{
				mysql_query('ROLLBACK');
//				sleep(2);
				mysql_query('DELETE FROM progress WHERE id=' . $pid . ' LIMIT 1');
				global $logger;
				$log='Search failed.  So excel is returned with message Search (count failed)';
				$logger->fatal($log);

				if($return)
				{
					return messageInExcel("Search (count) failed." . $SEARCH_ERR);
				}else{
					return softDie("Search (count) failed." . $SEARCH_ERR);
					//return false;
				}
			}
			
			$all_ids = array_keys($all_ids);
			
			//ids are sorted in ascending order.
			sort($all_ids); 
			
			if ($backboneAgent) { 
				$agent = getBackboneAgent($params);
				if ($agent != null)
					$all_ids = applyBackboneAgent($all_ids, $agent->value);
			}
			
			$rescount = '';
			if($countactive) {
				if(is_array($all_ids) && !empty($all_ids))
					$rescount = getActiveCount($all_ids, $time_machine);
					
			} else {
				$rescount = count($all_ids); 
			}
			
			$results[$row][$column]->num = $rescount;
			
			if($bomb) {
				 $results[$row][$column]->bomb = getBomb($all_ids);
			} else {
				$results[$row][$column]->bomb = "";
			}
				
			//get maximum phase
			if($countactive || $rescount)
			{  
				$key = 0;
				if(is_array($all_ids) && !empty($all_ids))
				{
					$datetime = '"' . date('Y-m-d H:i:s',$time_machine) . '"';
					$query = 'SELECT MAX(val_enum) AS "phase" FROM data_values AS dv '
							. 'LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id '
							. 'LEFT JOIN clinical_study ON i.larvol_id=clinical_study.larvol_id WHERE '
							. 'dv.added<' . $datetime . ' AND (dv.superceded>' . $datetime . ' OR dv.superceded IS NULL) '
							. 'AND `field`=' . $phase_fid . ' AND clinical_study.larvol_id IN(' . implode(',', $all_ids) . ')';
					$time_start = microtime(true);
					$res = mysql_query($query) or tex('Bad SQL query getting maximum phase for row ' . $row . ' column ' . $column . "\n" . $query 
					. "\n" . mysql_error());
					$time_end = microtime(true);
					$time_taken = $time_end-$time_start;
					$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() getting maximum phase for row '. $row . ' column ' 
					. $column;
					$logger->info($log);
					unset($log);
					
					$res = mysql_fetch_assoc($res);
					
					if($res !== false)
					{
						$phase = $res['phase'];
						$key = array_search($phase, $phase_enumvals);
						if($key === false) $key = 0;
					}
				}
				$results[$row][$column]->color = $p_colors[$key];
			} 
			/*
				In CD this part does a second search for IDs
			-
			-
			-
			-
			*/
			
			//Added for displaying unmatched upms in intermediary page
			/*$cell_upm = array();
			$global_multi_upm_params = array();$col_multi_upm_params = array();$row_multi_upm_params = array();$cell_multi_upm_params = array();
			$global_searchval_upm_params = array();$col_searchval_upm_params = array();$row_searchval_upm_params = array();$cell_searchval_upm_params = array();*/
			
			$global_upm_params = array(); $global_negate_upm_params = array();
			$col_upm_params = array(); $col_negate_upm_params = array();
			$row_upm_params = array(); $row_negate_upm_params = array();
			$cell_upm_params = array(); $cell_negate_upm_params = array();
			
			if(isset($oversearch['multifields']['varchar+text']) && in_array($intervention_name_field_id,$oversearch['multifields']['varchar+text'])) {
				$global_upm_params[] = $oversearch['multivalue']['varchar+text'];
			}
			if(isset($oversearch['searchval']) && array_key_exists($intervention_name_field_id,$oversearch['searchval'])) {
				$global_upm_params[] = $oversearch['searchval'][$intervention_name_field_id];
			}
			if(isset($oversearch['negate']) && array_key_exists($intervention_name_field_id,$oversearch['negate'])) {
				$global_negate_upm_params[] = $oversearch['negate'][$intervention_name_field_id];
			}
			
			if(isset($columnsearch[$column]['multifields']['varchar+text']) && 
			in_array($intervention_name_field_id,$columnsearch[$column]['multifields']['varchar+text'])) {
				$col_upm_params[] = $columnsearch[$column]['multivalue']['varchar+text'];
			}
			if(isset($columnsearch[$column]['searchval']) && array_key_exists($intervention_name_field_id,$columnsearch[$column]['searchval'])) {
				$col_upm_params[] = $columnsearch[$column]['searchval'][$intervention_name_field_id];
			}
			if(isset($columnsearch[$column]['negate']) && array_key_exists($intervention_name_field_id,$columnsearch[$column]['negate'])) {
				$col_negate_upm_params[] = $columnsearch[$column]['negate'][$intervention_name_field_id];
			}
			
			if(isset($rowsearch[$row]['multifields']['varchar+text']) && in_array($intervention_name_field_id,$rowsearch[$row]['multifields']['varchar+text'])) {
				$row_upm_params[] = $rowsearch[$row]['multivalue']['varchar+text'];
			}
			if(isset($rowsearch[$row]['searchval']) && array_key_exists($intervention_name_field_id,$rowsearch[$row]['searchval'])) {
				$row_upm_params[] = $rowsearch[$row]['searchval'][$intervention_name_field_id];
			}
			if(isset($rowsearch[$row]['negate']) && array_key_exists($intervention_name_field_id,$rowsearch[$row]['negate'])) {
				$row_negate_upm_params[] = $rowsearch[$row]['negate'][$intervention_name_field_id];
			}
			
			if(isset($cell['multifields']['varchar+text']) && in_array($intervention_name_field_id,$cell['multifields']['varchar+text'])) {
				$cell_upm_params[] = $cell['multivalue']['varchar+text'];
			}
			if(isset($cell['searchval']) && array_key_exists($intervention_name_field_id,$cell['searchval'])) {
				$cell_upm_params[] = $cell['searchval'][$intervention_name_field_id];
			}
			if(isset($cell['negate']) && array_key_exists($intervention_name_field_id,$cell['negate'])) {
				$cell_negate_upm_params[] = $cell['negate'][$intervention_name_field_id];
			}
			
			$upm_params = $row_upms[$row][$column] = $col_upms[$row][$column] = array_unique(array_filter(array_merge(
															$global_upm_params, $col_upm_params, $row_upm_params, $cell_upm_params)));
			$upm_negate_params = array_unique(array_filter(array_merge(
									$global_negate_upm_params, $col_negate_upm_params, $row_negate_upm_params, $cell_negate_upm_params)));
															
			if($link_generation_method == 'db') {
			
				//row labels
				//checking whether the row header id already exists and if not inserting a new record into the rpt_ott_header table
				$row_id 	= '';
				$query 		= "SELECT `id` FROM `rpt_ott_header` WHERE `header` = '" . mysql_real_escape_string($rows[$row]) . "' ";
				$time_start = microtime(true);
				$res		= mysql_query($query) or tex('Bad SQL query getting id for the row headers for row ' . $row . ' column ' . $column . "\n" . $query);
				$time_end 	= microtime(true);
				$time_taken = $time_end-$time_start;
				$log 		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: getting id for the row headers for row ' . $row . ' column ' 
				. $column;
				$logger->info($log);
				unset($log);
				
				if(mysql_num_rows($res) > 0) {
					$res 	= mysql_fetch_assoc($res);
					$row_id = $res['id'];
				} else {
					
					if($expire) {
						$query 		= "INSERT INTO `rpt_ott_header`(`header`, `created`, `expiry`, `last_referenced`) VALUES('" 
									. mysql_real_escape_string($rows[$row]) . "', NOW(), '" . date('Y-m-d',strtotime('+2 weeks',$now)) . "' , NOW()) ";
					} else {
						
						$query 		= "INSERT INTO `rpt_ott_header`(`header`, `created`, `last_referenced`) VALUES('" . mysql_real_escape_string($rows[$row]) 
									. "', NOW(), NOW()) ";
					}
					$time_start = microtime(true);
					$res 		= mysql_query($query) or tex('Bad SQL Query saving row headers for row ' . $row . ' column ' . $column . "\n" . $query);
					$row_id 	= mysql_insert_id();
					$time_end 	= microtime(true);
					$time_taken = $time_end-$time_start;
					$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: saving row headers for row ' . $row . ' column ' 
					. $column;
					$logger->info($log);
					unset($log);
				}
				
				//column labels
				//checking whether the column header id already exists and if not inserting a new record into the rpt_ott_header table
				$column_id	= '';
				$query 		= "SELECT `id` FROM `rpt_ott_header` WHERE `header` = '" . mysql_real_escape_string($columns[$column]) . "' ";
				$time_start = microtime(true);
				$res 		= mysql_query($query) or tex('Bad SQL query getting id for the column headers for row ' . $row . ' column ' . $column . "\n" . $query);
				$time_end	= microtime(true);
				$time_taken	= $time_end-$time_start;
				$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: getting id for the column headers for row ' . $row . ' column ' 
				. $column;
				$logger->info($log);
				unset($log);
				
				if(mysql_num_rows($res) > 0) {
					$res = mysql_fetch_assoc($res);
					$column_id = $res['id'];
				} else {
					if($expire) {
						$query 		= "INSERT INTO `rpt_ott_header`(`header`, `created`, `expiry`, `last_referenced`) VALUES('" 
									. mysql_real_escape_string($columns[$column]) . "', NOW(), '" . date('Y-m-d',strtotime('+2 weeks',$now)) . "', NOW()) ";
					} else {
						$query 		= "INSERT INTO `rpt_ott_header`(`header`, `created`, `last_referenced`) VALUES('" . mysql_real_escape_string($columns[$column]) 
									. "', NOW(), NOW()) ";
					}
					$time_start = microtime(true);
					$res 		= mysql_query($query) or tex('Bad SQL Query saving column headers for row ' . $row . ' column ' . $column . "\n" . $query);
					$column_id 	= mysql_insert_id();
					$time_end	= microtime(true);
					$time_taken	= $time_end-$time_start;
					$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: saving column headers for row ' . $row . ' column ' . $column;
					$logger->info($log);
					unset($log);
				}
								
				$upm_id = '';
				if(!empty($upm_params) || !empty($upm_negate_params)) {
				
					natsort($upm_params);
					natsort($upm_negate_params);
					
					//upm values
					$upm_result_set = implode("\\n",$upm_params);
					$upm_negate_result_set = implode("\\n", $upm_negate_params);
					
					//checking whether the upm id already exists and if not inserting a new record into the rpt_ott_upm table
					$query 		= "SELECT `id` FROM `rpt_ott_upm` WHERE `intervention_name` = '" . mysql_real_escape_string($upm_result_set) 
								. "' AND `intervention_name_negate` = '" . mysql_real_escape_string($upm_negate_result_set) . "' ";
					$time_start = microtime(true);
					$res 	= mysql_query($query) or tex('Bad SQL query getting id for the upm result_set for row ' . $row . ' column ' . $column . "\n" . $query);
					$time_end	= microtime(true);
					$time_taken	= $time_end-$time_start;
					$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: getting id for upm result_set for row ' . $row . ' column ' . $column;
					$logger->info($log);
					unset($log);
					
					if(mysql_num_rows($res) > 0) {
						$res 	= mysql_fetch_assoc($res);
						$upm_id = $res['id'];
					} else {
						if($expire) {
							$query 		= "INSERT INTO `rpt_ott_upm`(`intervention_name`, `intervention_name_negate`, `created`, `expiry`, `last_referenced`) VALUES('" 
										. mysql_real_escape_string($upm_result_set) . "', '" . mysql_real_escape_string($upm_negate_result_set) . "', NOW(), '" . date('Y-m-d',strtotime('+2 weeks',$now)) . "', NOW()) ";
						} else {
							$query 		= "INSERT INTO `rpt_ott_upm`(`intervention_name`, `intervention_name_negate`, `created`, `last_referenced`) VALUES('" 
										. mysql_real_escape_string($upm_result_set) . "', '" . mysql_real_escape_string($upm_negate_result_set) . "', NOW(), NOW()) ";
						}
						$time_start = microtime(true);
						$res 		= mysql_query($query) or tex('Bad SQL Query saving upm result_set for row ' . $row . ' column ' . $column . "\n" . $query);
						$upm_id 	= mysql_insert_id();
						$time_end	= microtime(true);
						$time_taken	= $time_end-$time_start;
						$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: saving upm result_set for row ' . $row . ' column ' . $column;
						$logger->info($log);
						unset($log);
					}
				}	
			}
			
			//fill in hyperlink
			if($rescount < $boundary)
			{ 	
				if($link_generation_method == 'db') {
					
					$trials_id = '';
					//generating the link even if there are no trials, to be able to see the matched/unmatched upms
					/*$flag = false;
					if($countactive) { //for count active no need to check count more than 0 in order to link even if count is zero
						if(is_array($all_ids) && !empty($all_ids)) {
							$flag = true;
						}
					} else if($rescount > 0) {
						$flag = true;
					}
					if($flag == true) {*/
					$id_result_set = implode(",",$all_ids);//all ids
					//checking whether the trials id already exists and if not inserting a new record into the rpt_ott_trials table
					$query 		= "SELECT `id` FROM `rpt_ott_trials` WHERE `result_set` = '" . $id_result_set . "' ";
					$time_start = microtime(true);
					$res 	= mysql_query($query) or tex('Bad SQL query getting id for the trials result_set for row ' . $row . ' column ' . $column . "\n" . $query);
					$time_end	= microtime(true);
					$time_taken	= $time_end-$time_start;
					$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: getting id for trials result_set for row ' . $row . ' column ' 
					. $column;
					$logger->info($log);
					unset($log);
					
					if(mysql_num_rows($res) > 0) {
						$res = mysql_fetch_assoc($res);
						$trials_id = $res['id'];
					} else {
						if($expire) {
							$query 		= "INSERT INTO `rpt_ott_trials`(`result_set`, `created`, `expiry`, `last_referenced`) VALUES('" . $id_result_set 
										. "', NOW(), '" . date('Y-m-d',strtotime('+2 weeks',$now)) . "', NOW()) ";
						} else {
							$query 		= "INSERT INTO `rpt_ott_trials`(`result_set`, `created`, `last_referenced`) VALUES('" . $id_result_set . "', NOW(), NOW()) ";
						}
						$time_start = microtime(true);
						$res 		= mysql_query($query) or tex('Bad SQL Query saving trials result_set for row ' . $row . ' column ' . $column . "\n" . $query);
						$trials_id 	= mysql_insert_id();
						$time_end	= microtime(true);
						$time_taken	= $time_end-$time_start;
						$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: saving trials result_set for row ' . $row . ' column ' . $column;
						$logger->info($log);
						unset($log);
					}
					
					//$results[$row][$column]->{'link'} = 'results=' . $row_id . '.' . $column_id . '.' . $trials_id;
					
					/*added a separator(to identify whether it is from searchdata table ot trials result set table) 
					for both the cases - ids count greater and less than the set boundary 
					as compared to earlier when the separator was added only when the ids count crossed the boundary. */
					$results[$row][$column]->{'link'} = 'results=' . $row_id . '.' . $column_id . '.-1.' . $trials_id;
					
					if($upm_id != '')
						$results[$row][$column]->{'link'} .= '.' . $upm_id;
					if($bomb)
						$results[$row][$column]->{'link'} .= '&bomb=' . $results[$row][$column]->bomb;
						
					$results[$row][$column]->{'link'} .= '&time=' . $time_machine . '&v=1';	
					//}
				} else {	
					//pass all IDs
					$packedIDs = '';
					if($countactive) { //for count active no need to check count more than 0 in order to link even if count is zero
						if(is_array($all_ids) && !empty($all_ids)) {
							$evcode = '$packedIDs = pack("l*",' . implode(',', $all_ids) . ');';
							eval($evcode);
						}
					} else if($rescount > 0) {
						$evcode = '$packedIDs = pack("l*",' . implode(',', $all_ids) . ');';
						eval($evcode);
					}
					
					$results[$row][$column]->{'link'} = 'leading=' . rawurlencode(base64_encode(gzdeflate($packedIDs)));
					//pass metadata
					$results[$row][$column]->{'link'} .= '&params='
						. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => NULL,
																			   'time' => $time_machine,
																			   'rowlabel' => $rows[$row],
																			   'columnlabel' =>$columns[$column],
																			   'bomb' => $results[$row][$column]->bomb,
																			   'upm' => $upm_params)))));
				}
				
			} else {
			 	
				if($link_generation_method == 'db') {
				
					$search_result_set = base64_encode(gzdeflate(mysql_real_escape_string(serialize($params))));
					//checking whether the trials id already exists and if not inserting a new record into the rpt_ott_trials table
					$query 		= "SELECT `id` FROM `rpt_ott_searchdata` WHERE `result_set` = '" . $search_result_set . "' ";
					$time_start = microtime(true);
					$res 	= mysql_query($query) or tex('Bad SQL query getting id for the searchdata result_set for row ' . $row . ' column ' . $column 
					. "\n" . $query);
					$time_end	= microtime(true);
					$time_taken	= $time_end-$time_start;
					$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: getting id for the searchdata result_set for row ' . $row 
					. ' column ' . $column;
					$logger->info($log);
					unset($log);
					
					if(mysql_num_rows($res) > 0) {
						$res = mysql_fetch_assoc($res);
						$searchdata_id = $res['id'];
					} else {
						if($expire) {
							$query 		= "INSERT INTO `rpt_ott_searchdata`(`result_set`, `created`, `expiry`, `last_referenced`) VALUES('" . $search_result_set 
										. "', NOW(), '" . date('Y-m-d',strtotime('+2 weeks',$now)) . "', NOW()) ";
						} else {
							$query 		= "INSERT INTO `rpt_ott_searchdata`(`result_set`, `created`, `last_referenced`) VALUES('" . $search_result_set 
										. "', NOW(), NOW()) ";
						}
						$time_start = microtime(true);
						$res 		= mysql_query($query) or tex('Bad SQL Query saving searchdata result_set for row ' . $row . ' column ' . $column 
						. "\n" . $query);
						$searchdata_id = mysql_insert_id();
						$time_end	= microtime(true);
						$time_taken	= $time_end-$time_start;
						$log		= 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments: saving searchdata result_set for row ' . $row 
						. ' column ' . $column;
						$logger->info($log);
						unset($log);
					}
					
					//$results[$row][$column]->{'link'} = 'results=' . $row_id . '.' . $column_id . '.s' . $searchdata_id;
					/*added the separator as a separate parameter as compared to earlier 
					where it was appended in the beginning of the searchdata id. (see line above i.e. line no. 668) */
					$results[$row][$column]->{'link'} = 'results=' . $row_id . '.' . $column_id . '.-2.' . $searchdata_id;
					
					if($upm_id != '')
						$results[$row][$column]->{'link'} .= '.' . $upm_id;
					if($bomb)
						$results[$row][$column]->{'link'} .= '&bomb=' . $results[$row][$column]->bomb;
						
					$results[$row][$column]->{'link'} .= '&time=' . $time_machine . '&v=1';	
				} else {
					//pass search terms and metadata
					$results[$row][$column]->{'link'} = 'params='
						. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => $params,
																			   'time' => $time_machine,
																			   'rowlabel' => $rows[$row],
																			   'columnlabel' =>$columns[$column],
																			   'bomb' => $results[$row][$column]->bomb,
																			   'upm' => $upm_params)))));
				}
			}
			$results[$row][$column]->reportname = substr($name,0,40);
			$results[$row][$column]->rundate = date("Y-m-d H:i:s",$now);
			$results[$row][$column]->time_machine = $time_machine;
			
			mysql_query('COMMIT') or tex("Couldn't commit SQL transaction");
			if(!$return)
			{
				mysql_query('UPDATE progress SET progress=progress+1 WHERE id=' . $pid . ' LIMIT 1');
				if(mysql_affected_rows() == 0) exit;
			}
			else
			{
				$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).
				'", progress=progress+1 WHERE run_id="' .$run_id .'" AND report_type ="0" AND type_id="' .$type_id .'"';
				$res = mysql_query($query);
				if($res === false)
				{
					$log = 'Bad SQL Query updating heatmap report progress. ' . $query . ' Error: '.mysql_error();
					$logger->fatal($log);
					die($log);
				}
				if(mysql_affected_rows() == 0) exit;
//				sleep(20);
			}
		}
	}
	
	$info["pid"] = $pid;
	if ($format == "xlsx")
		return heatmapAsExcel($info, $rows, $columns, $results, $phase_legend_colors, $return, $phase_legend_nums, $optionsSelected, $row_upms, $col_upms,
		$link_generation_method);
	else
		return heatmapAsWord($info, $rows, $columns, $results, $phase_legend_colors, $return, $phase_legend_nums, $optionsSelected);

}

function heatmapAsWord($info, $rows, $columns, $results, $p_colors, $return, $phasenums,$optionsSelected=array()) {
	global $now, $db;
	$countactive = $info['count_only_active'] == 'Y';
	$footnotes = $info['footnotes'];
	$description = $info['description'];
	$name = $info['name'];
	if(strlen($name) == 0) $name = 'Report ' . $info['id'];
	$pid = $info["pid"];

	$out = '<table border="1" cellpadding="0" cellspacing="0">';
	$out .= '<tr><td>&nbsp;</td>';
	foreach ($columns as $header) {
		$out .= '<td>'.htmlspecialchars($header).'</td>';
	}
	$out .= '</tr>';
	foreach ($rows as $row => $header) {
		$out .= '<tr><td>'.htmlspecialchars($header).'</td>';
		foreach ($columns as $col => $a) {
			if (isset($results[$row][$col])) {
				$result = $results[$row][$col];
				$color = ($result->color === NULL) ? 'DDDDDD' : $result->color;
				$out .= '<td bgcolor="'.$color.'">';
				if($result->bomb != '')
				{
					if ($result->bomb == 'sb') {
						$file = 'sbomb.png';
						$alt = 'small bomb';
					}
					else {
						$file = 'lbomb.png';
						$alt = 'large bomb';
					}
					$image = getimagesize(dirname(__FILE__).DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR.$file);
					$out .= '<img src="http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/images/'.$file
					.'" alt="'.$alt.'" '.$image[3].'>';
				}
				
				if($countactive) {
				//for count active no need to check if count is more than 0 in order to provide link
					if(strlen($result->num)) { 
					
						$clink = urlPath() . 'intermediary.php?' . $result->{'link'};
						$clink = addYourls($clink, $result->reportname);
						$out .= '<a href="'.$clink.'">'.htmlspecialchars($result->num).'</a>';
					} else {
						$out .= htmlspecialchars($result->num);
					}
				
				} else if($result->num) {
				
					$clink = urlPath() . 'intermediary.php?' . $result->{'link'};
					$clink = addYourls($clink, $result->reportname);
					$out .= '<a href="'.$clink.'">'.htmlspecialchars($result->num).'</a>';
				
				} else {
					$out .= '&nbsp;';
				}
			
				$out .= '</td>';
			}
			else
				$out .= '<td>&nbsp;</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</table><br>';
	
	$out .= 'Report name: '.substr($name,0,250).'<br>';
	$out .= 'Footnotes: '.htmlspecialchars($footnotes).'<br>';
	$out .= 'Description: '.htmlspecialchars($description).'<br>';
	$out .= 'Runtime: '.date("Y-m-d H:i:s", $now).'<br><br>';
	if(count($optionsSelected)>1)
	{
		$options =  ($optionsSelected['bomb']==1)?'Bomb, ':'';
		$options .= ($optionsSelected['backboneAgent']==1)?'Backbone Agent, ':'';
		$options .= ($optionsSelected['countactive']==1)?'Count only active ':'';
		$out .= 'Options Selected: '.$options.'<br><br>';
	}
	$out .= 'Legend:<br>';
	$out .= '<table border="1" cellspacing="0" cellpadding="0"><tr>';
	$width = (int)(100/count($p_colors));
	foreach($p_colors as $key => $color)
	{
		$out .= '<td bgcolor="'.$color.'" width="'.$width.'%" align="center">'.$phasenums[$key].'</td>';
	}
	$out .= '</tr></table>';

	$template = file_get_contents('templates/general.htm');
	$out = str_replace('#content#', $out, $template);

	if ($return) {
		return $out;
	}
	else {
		
		global $logger;
		$log = null;
		$log = ob_get_contents();
		$log = str_replace("\n", '', $log);
		if($log)
		$logger->error($log);		
		ob_end_clean();
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");

		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header("Content-Type: application/msword");
		header('Content-Disposition: attachment;filename="' . substr($name,0,20) . '_' . date('Y-m-d_H.i.s') . '.doc"');
		header("Content-Transfer-Encoding: binary ");
		echo($out);
		@flush();

		ob_start();
		mysql_query('DELETE FROM progress WHERE id=' . $pid . ' LIMIT 1');
		$mail = new PHPMailer();
		$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
		if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
		$mail->AddAddress($db->user->email);
		$mail->Subject = SITE_NAME . ' manual report ' . date("Y-m-d H.i.s", $now) . ' - ' . substr($name,0,20);
		$mail->Body = 'Attached is the report you generated earlier.';
		$current_filename=substr($name,0,20).'_'.date('Y-m-d_H.i.s', $now);
		$mail->AddStringAttachment($out,
					   $current_filename.'.doc',
					   'base64',
					   'Content-Type: application/msword','','manual report');
		
					   
		@$mail->Send();
		ob_end_clean();
		exit;
	}
}

function heatmapAsExcel($info, $rows, $columns, $results, $p_colors, $return, $phasenums,$optionsSelected=array(), $row_upms, $col_upms, $link_generation_method) {

	global $now, $db;
	$countactive = $info['count_only_active'] == 'Y';
	$footnotes = $info['footnotes'];
	$description = $info['description'];
	$name = $info['name'];
	if(strlen($name) == 0) $name = 'Report ' . $info['id'];
	$pid = $info["pid"];

	// Create excel file object
	$objPHPExcel = new PHPExcel();

	// Set properties
	$objPHPExcel->getProperties()->setCreator(SITE_NAME);
	$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
	$objPHPExcel->getProperties()->setTitle(substr($name,0,20));
	$objPHPExcel->getProperties()->setSubject(substr($name,0,20));
	$objPHPExcel->getProperties()->setDescription(substr($name,0,20));

	// Build sheet
	$objPHPExcel->setActiveSheetIndex(0);
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle(substr($name,0,20));
	
	ksort($rows);ksort($columns);
	foreach($rows as $row => $header)
	{
		$cell = 'A' . ($row+1);
		$sheet->SetCellValue($cell, $header);
		
		//added for Stacked Trial Tracker
		$link	= urlPath() . 'intermediary.php?';
		
		if($link_generation_method == 'db') {
			$new_sub_link = '';
			$link	.= 'type=row';
		} else {
			$flag = false;
			//parameter set to display msg in OTT that all records couldnt be shown due to yourls link limit and the 
			//exceeding ones are truncated. - by default set to N
			$t_link = rawurlencode(base64_encode(gzdeflate(serialize('n'))));
			$link	.= 'cparams=' . rawurlencode(base64_encode(gzdeflate(serialize(array('type' => 'row', 'rowlabel' => $rows[$row])))));
		}
		
		$index = 0;
		foreach($columns as $k => $v) {
			
			if($link_generation_method == 'db') {
				//generating the link even if there are no trials, to be able to see the matched/unmatched upms
				/*if($countactive) {
					if(strlen($results[$row][$k]->num)) {
						++$index;
						$new_sub_link .= '&' . str_replace("results","results[$index]",$results[$row][$k]->{'link'});
					}
				} else if($results[$row][$k]->num) {*/
					++$index;
					$new_sub_link .= '&' . str_replace("results","results[$index]",$results[$row][$k]->{'link'});
				//}
			} else {
				$sub_link = '';
				if($countactive) {
					if(strlen($results[$row][$k]->num)) {
						$sub_link .= '&' . 
						str_replace('params', "params[$k]", str_replace('leading', "leading[$k]", $results[$row][$k]->{'link'}));
						$sub_link .= "&rowupm[$k]=" . rawurlencode(base64_encode(gzdeflate(serialize($row_upms[$row][$k]))));
						$flag = true;
					}
				} else if($results[$row][$k]->num) {
					$sub_link .= '&' . 
					str_replace('params', "params[$k]", str_replace('leading', "leading[$k]", $results[$row][$k]->{'link'}));
					$sub_link .= "&rowupm[$k]=" . rawurlencode(base64_encode(gzdeflate(serialize($row_upms[$row][$k]))));
					$flag = true;
				}
				//total link limit - 2000 and length of truncate msg param - 20, (2000-20) = 1980
				if((strlen($link) + strlen($sub_link)) < 1980){ 
					$link .= $sub_link;
				} else {
					//in case the link is exceeding the limit and has been truncated, parameter is set to Y.
					$t_link = rawurlencode(base64_encode(gzdeflate(serialize('y'))));
				}
			}
		}
		
		if($link_generation_method == 'db') {
		
			$new_sub_link = parse_url($new_sub_link);
			parse_str($new_sub_link['path'], $myArray);
			
			if(!empty($myArray)) {
				foreach($myArray['results'] as $k => &$v)  {
					$vvv = explode('.', $v);
					if($k != 1) {
						unset($vvv[0]);//removing redundant row headers
						$v = implode('.', $vvv);
					}
					$v = count($vvv) . '.' . $v;
				}
				
				$str = implode(',', $myArray['results']);
				
				//$str = str_replace('.', ',', str_replace('s', 0x73, $str));
				//removing the hexadecimal conversion of the separator as the separator is now sent in integer format as a separate parameter
				//see line no. 594 and 670
				$str = str_replace('.', ',', $str);
				
				$evcode = '$packedIDs = pack("l*",' . $str . ');';
				eval($evcode);				
				$link .= '&results=' . rawurlencode(base64_encode(gzdeflate($packedIDs))) . '&time=' . $myArray['time'] . '&format=new&v=1';	;
				
				//$link .= '&results=' . urlencode(base64_encode(gzdeflate(implode(',', $myArray['results'])))) . '&time=' . $myArray['time'];
				$link = addYourls($link,$results[$row][$k]->reportname);
				$sheet->getCell($cell)->getHyperlink()->setUrl($link);
			}
		} else {
			$link .= '&trunc=' . $t_link;
			$link = addYourls($link,$results->reportname);
			if($flag == true)
				$sheet->getCell($cell)->getHyperlink()->setUrl($link);
		}
	}
	
	foreach($columns as $col => $header)
	{
		$cell = num2char($col) . '1';
		$sheet->SetCellValue($cell, $header);
		
		//added for Stacked Trial Tracker
		$link	= urlPath() . 'intermediary.php?';
		
		if($link_generation_method == 'db') {
			$new_sub_link = '';
			$link	.= 'type=col';		
		} else {
			$flag = false;
			/*parameter set to display msg in OTT that all records couldnt be shown due to yourls link limit and the 
			exceeding ones are truncated. - by default set to N*/
			$t_link = rawurlencode(base64_encode(gzdeflate(serialize('n'))));
			$link	.= 'cparams=' . rawurlencode(base64_encode(gzdeflate(serialize(array('type' => 'col', 'columnlabel' => $columns[$col])))));
		}

		$index = 0;
		foreach($rows as $k => $v) {	
		
			if($link_generation_method == 'db') {
				//generating the link even if there are no trials, to be able to see the matched/unmatched upms
				/*if($countactive) {
					if(strlen($results[$k][$col]->num)) {
						++$index;
						$new_sub_link .= '&' . str_replace("results","results[$index]",$results[$k][$col]->{'link'});
					}
				} else if($results[$k][$col]->num) {*/
					++$index;
					$new_sub_link .= '&' . str_replace("results","results[$index]",$results[$k][$col]->{'link'});
				//}
			} else {
				$sub_link = '';
				if($countactive) {
					if(strlen($results[$k][$col]->num)) {
						$sub_link .= '&' . 
						str_replace('params', "params[$k]", str_replace('leading', "leading[$k]", $results[$k][$col]->{'link'}));
						$sub_link .= "&colupm[$k]=" . rawurlencode(base64_encode(gzdeflate(serialize($col_upms[$k][$col]))));
						$flag = true;
					}
				} else if($results[$k][$col]->num) {
					$sub_link .= '&' . 
					str_replace('params', "params[$k]", str_replace('leading', "leading[$k]", $results[$k][$col]->{'link'}));
					$sub_link .= "&colupm[$k]=" . rawurlencode(base64_encode(gzdeflate(serialize($col_upms[$k][$col]))));
					$flag = true;
				}
				//total link limit - 2000 and length of truncate msg param - 20, (2000-20) = 1980
				if((strlen($link) + strlen($sub_link)) < 1980){ //echo "<br/>less<br/><br/>";
					$link .= $sub_link;
				} else { 
					//in case the link is exceeding the limit and has been truncated, parameter is set to Y.
					$t_link = rawurlencode(base64_encode(gzdeflate(serialize('y'))));
				}
			}
		}
		
		if($link_generation_method == 'db') {
		
			$new_sub_link = parse_url($new_sub_link);
			parse_str($new_sub_link['path'], $myArray);
			
			if(!empty($myArray)) {
				foreach($myArray['results'] as $k => &$v) { 
					$vvv = explode('.', $v);
					if($k != 1) {
						unset($vvv[1]);
						$v = implode('.', $vvv);
					}
					$v = count($vvv) . '.' . $v;
				}
				
				
				$str = implode(',', $myArray['results']);
				
				//$str = str_replace('.', ',', str_replace('s', 0x73, $str));
				//removing the hexadecimal conversion of the separator as the separator is now sent in integer format as a separate parameter
				//see line no. 594 and 670
				$str = str_replace('.', ',', $str);
				
				$evcode = '$packedIDs = pack("l*",' . $str . ');';
				eval($evcode);
				$link .= '&results=' . rawurlencode(base64_encode(gzdeflate($packedIDs))) . '&time=' . $myArray['time'] . '&format=new&v=1';
				
				//$link .= '&results=' . urlencode(base64_encode(gzdeflate(implode(',', $myArray['results'])))) . '&time=' . $myArray['time'];
				$link = addYourls($link,$results[$k][$col]->reportname);
				$sheet->getCell($cell)->getHyperlink()->setUrl($link);
			}
			
		} else {
			$link .= '&trunc=' . $t_link;
			$link = addYourls($link,$results->reportname);
			if($flag == true)
				$sheet->getCell($cell)->getHyperlink()->setUrl($link);
		}
	}
	
	foreach($results as $row => $rowData)
	{
		foreach($rowData as $col => $result)
		{
			$cell = num2char($col) . ($row + 1);
			$color = ($result->color === NULL) ? 'DDDDDD' : $result->color;
			$sheet->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$sheet->getStyle($cell)->getFill()->getStartColor()->setRGB($color);
			
			if($countactive) {
				//for count active no need to check if count is more than 0 in order to provide link
				if(strlen($result->num)) { 
				
					$clink = urlPath() . 'intermediary.php?' . $result->{'link'};
					$clink = addYourls($clink,$result->reportname);
					$sheet->getCell($cell)->getHyperlink()->setUrl($clink);
				}
				$sheet->SetCellValue($cell, $result->num);
				
			} else if($result->num) {
				
				$clink = urlPath() . 'intermediary.php?' . $result->{'link'};
				$clink = addYourls($clink,$result->reportname);
				$sheet->SetCellValue($cell, $result->num);
				$sheet->getCell($cell)->getHyperlink()->setUrl($clink);
					
			} else {
				$sheet->SetCellValue($cell, ' ');
			}
			
			if($result->bomb != "")
			{
				$drawing = new PHPExcel_Worksheet_Drawing();
				if($result->bomb == "sb")
				{
					$drawing->setName("small bomb");
					$drawing->setDescription("small bomb");
					$drawing->setPath("./images/sbomb.png");
				}else{
					$drawing->setName("large bomb");
					$drawing->setDescription("large bomb");
					$drawing->setPath("./images/lbomb.png");
				}
				$drawing->setWorksheet($sheet);
				$drawing->setCoordinates($cell);
			}
		}
	}
	
	
	$row = count($rows) + 1;
	$sheet->SetCellValue('A' . ++$row, '');
	$sheet->SetCellValue('A' . ++$row, 'Report name:');
	$sheet->SetCellValue('B' . $row, substr($name,0,250));
	$sheet->SetCellValue('A' . ++$row, 'Footnotes:');
	$sheet->SetCellValue('B' . $row, $footnotes);
	$sheet->SetCellValue('A' . ++$row, 'Description:');
	$sheet->SetCellValue('B' . $row, $description);
	$sheet->SetCellValue('A' . ++$row, 'Runtime:');
	$sheet->SetCellValue('B' . $row++, date("Y-m-d H:i:s", $now));

	if(count($optionsSelected)>1)
	{
		$options =  ($optionsSelected['bomb']==1)?'Bomb, ':'';
		$options .= ($optionsSelected['backboneAgent']==1)?'Backbone Agent, ':'';
		$options .= ($optionsSelected['countactive']==1)?'Count only active ':'';
		$sheet->SetCellValue('A' . ++$row, 'Options Selected:');
		$sheet->SetCellValue('B' . $row++, $options);
	}	
	
	$sheet->SetCellValue('A' . $row, 'Legend:');
	$col = 'A';
	foreach($p_colors as $key => $color)
	{
		$cell = ++$col . $row;
		$sheet->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$sheet->getStyle($cell)->getFill()->getStartColor()->setRGB($color);
		$sheet->getStyle($cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$sheet->getCell($cell)->setValueExplicit($phasenums[$key], PHPExcel_Cell_DataType::TYPE_STRING);
	}

	//Create output writer
	$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	//Send download or return contents
	if(!$return)
	{
		global $logger;
		$log = null;
		$log = ob_get_contents();
		$log = str_replace("\n", '', $log);
		if($log)
		$logger->error($log);		
		ob_end_clean(); 
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . substr($name,0,20) . '_' . date('Y-m-d_H.i.s') . '.xlsx"');
		
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();

		mysql_query('DELETE FROM progress WHERE id=' . $pid . ' LIMIT 1');
		if(/*connection_aborted()*/true)	//connection_aborted doesn't work due to PHP bug 
		{
			ob_start();
			$tempfile = tempnam(sys_get_temp_dir(), 'exc');
			if($tempfile === false) exit;//tex('Unable to create temp file');
			$objWriter->save($tempfile);
			$content = file_get_contents($tempfile);
			unlink($tempfile);
			$mail = new PHPMailer();
			$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
			if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
			$mail->AddAddress($db->user->email);
			$mail->Subject = SITE_NAME . ' manual report ' . date("Y-m-d H.i.s", $now) . ' - ' . substr($name,0,20);
			$mail->Body = 'Attached is the report you generated earlier.';
			$current_filename=substr($name,0,20).'_'.date('Y-m-d_H.i.s', $now);
			$mail->AddStringAttachment($content,
									   $current_filename.'.xlsx',
									   'base64',
									   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','','manual report');		
			
			@$mail->Send();
			ob_end_clean();
		}
		exit;
	}else{
		$tempfile = tempnam(sys_get_temp_dir(), 'exc');
		if($tempfile === false) tex('Unable to create temp file');
		$objWriter->save($tempfile);
		$content = file_get_contents($tempfile);
		unlink($tempfile);
		return $content;
	}
}
?>