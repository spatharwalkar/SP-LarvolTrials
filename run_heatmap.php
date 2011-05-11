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

function runHeatmap($id, $return = false, $format = "xlsx")
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

	//get report name
	$query = 'SELECT name,footnotes,description,searchdata,bomb,backbone_agent,count_only_active,id  FROM rpt_heatmap WHERE id=' 
	. $id . ' LIMIT 1';
	$time_start = microtime(true);
	$resu = mysql_query($query) or tex('Bad SQL query getting report name');
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() get report name and other data.';
	$logger->info($log);
	unset($log);
	
	$info = mysql_fetch_array($resu) or tex('Report not found.'); 
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

	//Get headers
	$rows = array();
	$columns = array();
	$rowsearch = array();
	$columnsearch = array();
	$query = 'SELECT `header`,`num`,`type`,searchdata FROM rpt_heatmap_headers WHERE report=' . $id;
	$time_start = microtime(true);
	$resu = mysql_query($query) or tex('Bad SQL query getting headers');
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() get headers.';
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
		${$ovar}[$header['num']] = ($header['searchdata']===NULL ?
											$nodata :
											removeNullSearchdata($unpacked));
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
		$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'", total="'
		.(count($rows) * count($columns)).
		'", progress="0" WHERE run_id="' .$run_id .'" AND report_type ="0" AND type_id="' .$type_id .'"';
		if(!mysql_query($query))
		{
			$log = 'Bad SQL Query updating heatmap report total. Error: '.mysql_error();
			$logger->fatal($log);
			die($log);
		}
	}

	//get searchdata
	$searchdata = array();$coldata = array();
	$query = 'SELECT `row`,`column`,`searchdata` FROM rpt_heatmap_cells WHERE report=' . $id;
	$time_start = microtime(true);
	$resu = mysql_query($query) or tex('Bad SQL query getting searchdata');
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() get searchdata.';
	$logger->info($log);
	unset($log);
	
	while($cell = mysql_fetch_array($resu))
	{
		if(!isset($searchdata[$cell['row']])) $searchdata[$cell['row']] = array();
		$unpack = unserialize(base64_decode($cell['searchdata']));
		$searchdata[$cell['row']][$cell['column']] = removeNullSearchdata($unpack);	
		$coldata[$cell['column']][$cell['row']] = removeNullSearchdata($unpack);	
	} 
	
	$maxrow = max(array_keys($rowsearch));
	$maxcol = max(array_keys($columnsearch));
	
	for($row = 1; $row <= $maxrow; ++$row)
		for($col = 1; $col <= $maxcol; ++$col)
			if(!isset($searchdata[$row][$col]))
				$searchdata[$row][$col] = $nodata;

	//get search results
	$phases = array('N/A', 'Phase 0', 'Phase 0/Phase 1', 'Phase 1', 'Phase 1/Phase 2', 'Phase 2',
					'Phase 2/Phase 3', 'Phase 3', 'Phase 3/Phase 4', 'Phase 4');
	$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
	//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
	$p_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF0000', 'FF0000');
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
		if($res === false) return softDie('Bad SQL query getting a phase enum value');
		$res = mysql_fetch_assoc($res); if($res === false) return softDie('Phase not found.');
		$phase_enumvals[$pkey] = $res['id'];
	}
	/*
	In CD this fills in the hasPhase array
	-
	-
	*/
	$results = array();
	
	//added for Stacked Trial Tracker
	//creating column link
	$collink = array();
	foreach($coldata as $col => $colData) {
	
		foreach($colData as $row => $cell) {
		
			$c_time_machine = array();//unsetting the array for every cell.
			$c_columnparams = array('action' => $columnsearch[$col]['action'], 'searchval' => $columnsearch[$col]['searchval'], 
									'negate' => $columnsearch[$col]['negate'],
									'multifields' => $columnsearch[$col]['multifields'], 
									'multivalue' => $columnsearch[$col]['multivalue']);
			$c_columnparams = prepareParams($c_columnparams);
			
			$c_cellparams = array('action' => $cell['action'],'searchval' => $cell['searchval'], 
									'negate' => $cell['negate'], 'multifields' => $cell['multifields'], 
									'multivalue' => $cell['multivalue']);
			$c_cellparams = prepareParams($c_cellparams);
			
			$c_params = array_merge($c_columnparams, $c_cellparams);
			
			//in case of any one of the array has timemachine parameters defined
			if(strlen($searchdata[$row][$col]['time_machine']) || strlen($columnsearch[$col]['time_machine'])) {
					
					array_push($c_time_machine, $cell['time_machine'], $columnsearch[$col]['time_machine']);
					$c_time_machine = array_filter($c_time_machine);	//removing empt values of the array
					usort($c_time_machine, "cmpdate"); //sorting the array
					sort($c_time_machine); //sorting the array further for time precision
					$c_time_machine = strtotime(end($c_time_machine)); //getting the latest date
		
			} else { //in case of timemachine  parameters not defined
				$c_time_machine = $now;
			}
			$c_override = $columnsearch[$col]['override'] . ',' . $cell['override'];
			$c_override = explode(',', $c_override);
			
			if($c_override === false)
			{
				$c_override = array();
			}else{
				foreach($c_override as $key => $value)
				{
					$value = nctidToLarvolid($value);
					if($value === false)
					{
						unset($c_override[$key]);
					}else{
						$c_override[$key] = $value;
					}
				}
			}
			
			foreach($c_params as $key => $sp)	//remove sorts
			{
				if(in_array($sp->action,array('ascending','descending'))) unset($c_params[$key]);
			}
			
			if(!isset($collink[$col])) $rowlink[$col] = array();
			$collink[$col][$row] = new Result();

			$c_ids = search($c_params,array(),NULL,$c_time_machine,$c_override);
			$c_ids = array_keys($c_ids);
			
			if ($backboneAgent) { 
				$agent = getBackboneAgent($c_params);
				if ($agent != null)
					$c_ids = applyBackboneAgent($c_ids, $agent->value);
			}
			$c_count = '';
			if($countactive) {
				if(is_array($c_ids) && !empty($c_ids))
					$c_count = getActiveCount($c_ids, $c_time_machine);
			} else {	
				$c_count = count($c_ids); 
			}
			
			$collink[$col][$row]->num = $c_count;
			
			if ($bomb)
				 $c_bomb = getBomb($c_ids);
			else
				$c_bomb = "";

			//fill in hyperlink
			if($c_count < 500)
			{
				//pass all IDs
				$packedIDs = '';
				if($countactive) { //for count active no need to check count more than 0 in order to link even if count is zero
					if(is_array($c_ids) && !empty($c_ids)) {
					
						$evcode = '$packedIDs = pack("l*",' . implode(',', $c_ids) . ');';
						eval($evcode);
					}
				} else if($c_count > 0) {
				
					$evcode = '$packedIDs = pack("l*",' . implode(',', $c_ids) . ');';
					eval($evcode);
				}
				$collink[$col][$row]->{'link'} = "&leading[$row]=" . rawurlencode(base64_encode(gzdeflate($packedIDs)));
				$collink[$col][$row]->{'link'} .= "&params[$row]=" 
					. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => NULL,
																		   'time' => $c_time_machine,
																		   'count' => $c_count,
																		   'rowlabel' =>$rows[$row],
																		   'bomb' => $c_bomb)))));
			} else {
				//pass search terms and metadata
				$collink[$col][$row]->{'link'} .= "&params[$row]="
					. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => $params,
																		   'time' => $c_time_machine,
																		   'rowlabel' =>$rows[$row],
																		   'bomb' => $c_bomb)))));
			}
		}
	}
	
	$rowlink = array();
	foreach($searchdata as $row => $rowData)
	{ 
		foreach($rowData as $column => $cell)
		{ 
			$time_machine = array();//unsetting the array for every cell.
			
			//get searchdata
			$globalparams = array('action' => $oversearch['action'], 'searchval' => $oversearch['searchval'], 
									'negate' => $oversearch['negate'], 'multifields' => $oversearch['multifields'], 
									'multivalue' => $oversearch['multivalue']);
			$globalparams = prepareParams($globalparams);
			
			$columnparams = array('action' => $columnsearch[$column]['action'], 
									'searchval' => $columnsearch[$column]['searchval'],
									'negate' => $columnsearch[$column]['negate'], 
									'multifields' => $columnsearch[$column]['multifields'], 
									'multivalue' => $columnsearch[$column]['multivalue']);
			$columnparams = prepareParams($columnparams);
									
			$rowparams = array('action' => $rowsearch[$row]['action'], 'searchval' => $rowsearch[$row]['searchval'], 
									'negate' => $rowsearch[$row]['negate'],
									'multifields' => $rowsearch[$row]['multifields'], 
									'multivalue' => $rowsearch[$row]['multivalue']);
			$rowparams = prepareParams($rowparams);
			
			$cellparams = array('action' => $cell['action'], 'searchval' => $cell['searchval'], 
									'negate' => $cell['negate'], 'multifields' => $cell['multifields'], 
									'multivalue' => $cell['multivalue']);
			$cellparams = prepareParams($cellparams);
			$params = array_merge($globalparams, $columnparams, $rowparams, $cellparams);
			
			//added for Stacked Trial Tracker
			$r_time_machine = array();//unsetting the array for every cell.
			$r_rowparams = array('action' => $rowsearch[$row]['action'], 'searchval' => $rowsearch[$row]['searchval'], 
									'negate' => $rowsearch[$row]['negate'],'multifields' => $rowsearch[$row]['multifields'], 
									'multivalue' => $rowsearch[$row]['multivalue']);
			$r_rowparams = prepareParams($r_rowparams);
			
			$r_cellparams = array('action' => $cell['action'], 'searchval' => $cell['searchval'], 'negate' => $cell['negate'], 
									'multifields' => $cell['multifields'], 'multivalue' => $cell['multivalue']);
			$r_cellparams = prepareParams($r_cellparams);
			
			$r_params = array_merge($r_rowparams, $r_cellparams);
			
			//in case of any one of the array has timemachine parameters defined
			if(strlen($cell['time_machine']) || strlen($rowsearch[$row]['time_machine']) || 
								strlen($columnsearch[$column]['time_machine']) || strlen($oversearch['time_machine'])) {
					
					array_push($time_machine, $cell['time_machine'], $rowsearch[$row]['time_machine'], 
					$columnsearch[$column]['time_machine'], $oversearch['time_machine']);
					$time_machine = array_filter($time_machine);	//removing empt values of the array
					usort($time_machine, "cmpdate"); //sorting the array
					sort($time_machine); //sorting the array further for time precision
					$time_machine = strtotime(end($time_machine)); //getting the latest date
		
			} else { //in case of timemachine  parameters not defined
				$time_machine = $now;
			}
			
			//in case of any one of the array has timemachine parameters defined
			if(strlen($cell['time_machine']) || strlen($rowsearch[$row]['time_machine'])) {
					
					array_push($r_time_machine, $cell['time_machine'], $rowsearch[$row]['time_machine']);
					$r_time_machine = array_filter($r_time_machine);	//removing empt values of the array
					usort($r_time_machine, "cmpdate"); //sorting the array
					sort($r_time_machine); //sorting the array further for time precision
					$r_time_machine = strtotime(end($r_time_machine)); //getting the latest date
		
			} else { //in case of timemachine  parameters not defined
				$r_time_machine = $now;
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

			$r_override = $rowsearch[$row]['override'] . ',' . $cell['override'];
			$r_override = explode(',', $r_override);
			
			if($r_override === false)
			{
				$r_override = array();
			}else{
				foreach($r_override as $key => $value)
				{
					$value = nctidToLarvolid($value);
					if($value === false)
					{
						unset($r_override[$key]);
					}else{
						$r_override[$key] = $value;
					}
				}
			}
			
			if(empty($params))	continue;
			if(array_filter_recursive($params,'nonempty') == array_filter_recursive($globalparams,'nonempty')) continue;
			foreach($params as $key => $sp)	//remove sorts
			{
				if(in_array($sp->action,array('ascending','descending'))) unset($params[$key]);
			}
			
			foreach($r_params as $key => $sp)	//remove sorts
			{
				if(in_array($sp->action,array('ascending','descending'))) unset($r_params[$key]);
			}

			if(!isset($results[$row])) $results[$row] = array();
			$results[$row][$column] = new Result();

			if(!isset($rowlink[$row])) $rowlink[$row] = array();
			$rowlink[$row][$column] = new Result();
			
			mysql_query('BEGIN') or tex("Couldn't begin SQL transaction");
			
			//get record IDs 
			$all_ids = search($params,array(),NULL,$time_machine,$override);
			$r_ids = search($r_params,array(),NULL,$r_time_machine,$r_override);
			
			if($all_ids === false)
			{
				mysql_query('ROLLBACK');
				sleep(2);
				mysql_query('DELETE FROM progress WHERE id=' . $pid . ' LIMIT 1');
				if($return)
				{
					return messageInExcel("Search (count) failed." . $SEARCH_ERR);
				}else{
					return softDie("Search (count) failed." . $SEARCH_ERR);
					//return false;
				}
			}
			
			$all_ids = array_keys($all_ids);
			$r_ids = array_keys($r_ids);
			
			if ($backboneAgent) { 
				$agent = getBackboneAgent($params);
				if ($agent != null)
					$all_ids = applyBackboneAgent($all_ids, $agent->value);
				
				$r_agent = getBackboneAgent($r_params);
				if ($r_agent != null)
					$r_ids = applyBackboneAgent($r_ids, $r_agent->value);
			}
			
			$rescount = '';$r_count = '';
			if($countactive) {
				if(is_array($all_ids) && !empty($all_ids))
					$rescount = getActiveCount($all_ids, $time_machine);
					
				if(is_array($r_ids) && !empty($r_ids))
					$r_count = getActiveCount($r_ids, $r_time_machine);
			} else {
				
				$rescount = count($all_ids); 
				$r_count = count($r_ids); 
			}
			
			$results[$row][$column]->num = $rescount;
			$rowlink[$row][$column]->num = $r_count;
			
			if ($bomb) {
				 $results[$row][$column]->bomb = getBomb($all_ids);
				 $r_bomb = getBomb($r_ids);
			} else {
				$results[$row][$column]->bomb = "";
				$r_bomb = "";
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
					$res = mysql_query($query) or tex('Bad SQL query getting maximum phase '.$query."\n" . mysql_error());
					$time_end = microtime(true);
					$time_taken = $time_end-$time_start;
					$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:function runHeatmap() get maximum phase.';
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
			
			//fill in hyperlink
			if($rescount < 500)
			{ 	
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
					
				$results[$row][$column]->{'link'} = 'leading='
					. rawurlencode(base64_encode(gzdeflate($packedIDs)));
				
				//pass metadata
				$results[$row][$column]->{'link'} .= '&params='
					. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => NULL,
																		   'time' => $time_machine,
																		   'name' => substr($name,0,40),
																		   'rundate' => date("Y-m-d H:i:s",$now),
																		   'count' => $rescount,
																		   'rowlabel' => $rows[$row],
																		   'columnlabel' =>$columns[$column],
																		   'bomb' => $results[$row][$column]->bomb)))));
																		   
				$results[$row][$column]->reportname = substr($name,0,40);
				$results[$row][$column]->rundate = date("Y-m-d H:i:s",$now);
				$results[$row][$column]->time_machine = $time_machine;
			}else{ 
				//pass search terms and metadata
				$results[$row][$column]->{'link'} = 'params='
					. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => $params,
																		   'time' => $time_machine,
																		   'name' => substr($name,0,40),
																		   'rundate' => date("Y-m-d H:i:s",$now),
																		   'rowlabel' => $rows[$row],
																		   'columnlabel' =>$columns[$column],
																		   'bomb' => $results[$row][$column]->bomb)))));
				$results[$row][$column]->reportname = substr($name,0,40);
				$results[$row][$column]->rundate = date("Y-m-d H:i:s",$now);
				$results[$row][$column]->time_machine = $time_machine;
			}
			
			
			if($r_count < 500) {
			
				//pass all IDs
				$packedIDs = '';
				if($countactive) { //for count active no need to check count more than 0 in order to link even if count is zero
					if(is_array($r_ids) && !empty($r_ids)) {
					
						$evcode = '$packedIDs = pack("l*",' . implode(',', $r_ids) . ');';
						eval($evcode);
					}
				} else if($r_count > 0) {
					$evcode = '$packedIDs = pack("l*",' . implode(',', $r_ids) . ');';
					eval($evcode);
				}
				$rowlink[$row][$column]->{'link'} = "&leading[$column]=" . rawurlencode(base64_encode(gzdeflate($packedIDs)));
				$rowlink[$row][$column]->{'link'} .= "&params[$column]="
							. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => NULL,
																		   'time' => $r_time_machine,
																		   'count' => $r_count,
																		   'columnlabel' =>$columns[$column],
																		   'bomb' => $r_bomb)))));
			} else {
				$rowlink[$row][$column]->{'link'} = "&params[$column]="
					. rawurlencode(base64_encode(gzdeflate(serialize(array('params' => $r_params,
																		   'time' => $r_time_machine,
																		   'columnlabel' =>$columns[$column],
																		   'bomb' => $r_bomb)))));

			}
			
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
					$log = 'Bad SQL Query updating heatmap report progress. Error: '.mysql_error();
					$logger->fatal($log);
					die($log);
				}
				if(mysql_affected_rows() == 0) exit;
				sleep(20);
			}
		}
	}

	$info["pid"] = $pid;
	if ($format == "xlsx")
	return heatmapAsExcel($info, $rows, $columns, $results, $p_colors, $return, $phasenums,$optionsSelected, $rowlink, $collink);
	else
		return heatmapAsWord($info, $rows, $columns, $results, $p_colors, $return, $phasenums,$optionsSelected);

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
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");

		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header("Content-Type: application/msword");
		header("Content-Disposition: attachment;filename=" . substr($name,0,20) . '_' . date('Y-m-d_H.i.s') . '.doc');
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
		$mail->AddStringAttachment($out,
					   substr($name,0,20).'_'.date('Y-m-d_H.i.s', $now).'.doc',
					   'base64',
					   'Content-Type: application/msword');
		@$mail->Send();
		ob_end_clean();
		exit;
	}
}

function heatmapAsExcel($info, $rows, $columns, $results, $p_colors, $return, $phasenums,$optionsSelected=array(), $rowlink, $collink) {
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
	
	foreach($rows as $row => $header)
	{
		$link = '';$r_flag = false;
		$link	= urlPath() . 'intermediary.php?';
		$link	.= 'cparams=' . rawurlencode(base64_encode(gzdeflate(serialize(array('type' => 'row',
																		'name' => substr($name,0,40),
																		'rundate' => date("Y-m-d H:i:s",$now),
																		'rowlabel' => $rows[$row])))));
		foreach($columns as $k => $v) {	
		
			if($countactive) {
				if(strlen($rowlink[$row][$k]->num)) {
					$link	.= $rowlink[$row][$k]->{'link'};
					$r_flag = true;
				}
			} else if($rowlink[$row][$k]->num) {
				$link	.= $rowlink[$row][$k]->{'link'};
				$r_flag = true;
			}
		}																
		$rlink = addYourls($link,$results->reportname);
		
		$cell = 'A' . ($row+1);
		$sheet->SetCellValue($cell, $header);
		if($r_flag == true)
			$sheet->getCell($cell)->getHyperlink()->setUrl($rlink);
	}
	
	foreach($columns as $col => $header)
	{
		$link = '';$c_flag = false;
		$link	= urlPath() . 'intermediary.php?';
		$link	.= 'cparams=' . rawurlencode(base64_encode(gzdeflate(serialize(array('type' => 'col',
																		'name' => substr($name,0,40),
																		'rundate' => date("Y-m-d H:i:s",$now),
																		'columnlabel' => $columns[$col])))));
		foreach($rows as $k => $v) {	
		
			if($countactive) {
				if(strlen($collink[$col][$k]->num)) {
					$link	.= $collink[$col][$k]->{'link'};
					$c_flag = true;
				}
			} else if($collink[$col][$k]->num) {
				$link	.= $collink[$col][$k]->{'link'};
				$c_flag = true;
			}
		}																
		$colink = addYourls($link,$results->reportname);
		$cell = num2char($col) . '1';
		$sheet->SetCellValue($cell, $header);
		if($c_flag == true)
			$sheet->getCell($cell)->getHyperlink()->setUrl($colink);
	}
	/*echo "<pre>";print_r($rowlink);
	echo "<pre>";print_r($collink);
	exit;*/
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
	
	//exit;
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
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Content-Disposition: attachment;filename=" . substr($name,0,20) . '_' . date('Y-m-d_H.i.s') . '.xlsx');
		
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
			$mail->AddStringAttachment($content,
									   substr($name,0,20).'_'.date('Y-m-d_H.i.s', $now).'.xlsx',
									   'base64',
									   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
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