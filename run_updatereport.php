<?php
require_once('db.php');

require_once('include.search.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('class.phpmailer.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

/* Runs the update report with the given ID, outputs to Excel
	If $return is true, the file is returned from this function
	If $return is false, it will go to the browser in a download.
*/
function runUpdateReport($id, $return = false)
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
			$log = 'Need to set $_GET[\'run_id\']';
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
	if($return)
	{
		$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'", total="0'.
		'", progress="0" WHERE run_id="' .$run_id .'" AND report_type ="2" AND type_id="' .$type_id .'"';
		$res = mysql_query($query);
		if($res ===false)
		{
			$log = 'Bad SQL Query updating updatescan report total. Error: '.mysql_error();
			$logger->fatal($log);
			die($log);
		}
	}
	
	if(!is_numeric($id)) return;
	//if(mysql_query('BEGIN') === false) return softDie("Couldn't begin SQL transaction");
	$query = 'SELECT name,start,end,criteria,searchdata,getnew,footnotes,description FROM rpt_update WHERE id=' . $id . ' LIMIT 1';
	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:run_updatereport.php get report setup.';
	$logger->info($log);
	unset($log);
	
	if($res === false) return softDie('Bad SQL query getting report setup');
	$rpt = mysql_fetch_assoc($res); if($rpt === false) return softDie('Report not found');
	$name = strlen($rpt['name']) ? $rpt['name'] : 'Report ' . $id;
	$footnotes = $rpt['footnotes'];
	$description = $rpt['description'];
	$getnew = $rpt['getnew'] == 1;
	
	$conds = array();
	
	//get SQL fragment listing allowable IDs based on searchdata
	if($rpt['searchdata'] !== NULL)
	{
		$params = unserialize(base64_decode($rpt['searchdata']));
		$time_machine = strlen($params['time_machine']) ? $params['time_machine'] : NULL;
		$override = strlen($params['override']) ? $params['override'] : '';
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
		//array_walk_recursive($params,ref_mysql_escape);
		$params = prepareParams($params);
		$searchcond = 'larvol_id IN(' . search($params,false,NULL,$time_machine,$override) . ')';
		$conds[] = 'dcis.' . $searchcond;
	}
	
	//require that changes happen in the time frame given by user
	$start = '';
	$end = '';
	$timecond = '';
	if($rpt['start'] !== NULL) $start = '"'.date("Y-m-d H:i:s", strtotime($rpt['start'])).'"';
	if($rpt['end'] !== NULL) $end = '"'.date("Y-m-d H:i:s", strtotime($rpt['end'])).'"';
	if($rpt['start'] !== NULL && $rpt['end'] !== NULL)
	{
		$timecond = ' BETWEEN ' . $start . ' AND ' . $end;
	}else if($rpt['start'] === NULL && $rpt['end'] !== NULL){
		$timecond = '<' . $end;
	}else if($rpt['start'] !== NULL && $rpt['end'] === NULL){
		$timecond = '>' . $start;
	}
	
	//get list of fields being watched (and for what values, if specified)
	$criteria = array('watch' => array(), 'req_from' => array(), 'from' => array(), 'req_to' => array(), 'to' => array());
	if($rpt['criteria'] !== NULL) $criteria = unserialize(base64_decode($rpt['criteria']));
	
	if(!count($criteria['watch']) && !$getnew)
	{
		echo('Nothing to watch');
		return true;
	}
	$fieldnames = array('NULL' => '');
	$results = array();
	if(count($criteria['watch']))
	{
		$conds[] = 'd1.`field` IN(' . implode(',', array_keys($criteria['watch'])) . ')';
		
		//require that changes happen in the time frame given by user
		$conds[] = 'd1.superceded' . $timecond;
		
		$conds = implode(' AND ', $conds);
		if(strlen($conds)) $conds = ' WHERE ' . $conds;
		$query = 'SELECT d1.id AS "id", d1.`field` AS "field", d1.val_int AS "int", d1.val_bool AS "bool", '
				. 'd1.val_varchar AS "varchar", d1.val_date AS "date", de.`value` AS "enum", '
				. 'd1.val_text AS "text", d1.superceded AS "superceded", data_fields.`type` AS "type", '
				. 'd1.studycat AS "studycat", dcis.larvol_id AS "larvol_id", data_fields.`name` AS "fieldname" '
				. 'FROM data_values AS d1 '
				. 'LEFT JOIN data_cats_in_study AS dcis ON d1.studycat=dcis.id '
				. 'LEFT JOIN data_enumvals AS de ON d1.val_enum=de.id '
				. 'LEFT JOIN data_fields ON d1.`field`=data_fields.id'
				. $conds;
		$time_start = microtime(true);
		$res = mysql_query($query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:run_updatereport.php get changes for right larvol_id,time,field.';
		$logger->info($log);
		unset($log);
		
		if($res === false) return softDie('Bad SQL query getting changes for right larvol_id,time,field');
		while($row = mysql_fetch_assoc($res))
		{
			$type = $row['type'];
			$val = $row[$type];
			$fid = $row['field'];
			$rkey = $row['larvol_id'] . '.' . $fid . '.' . $row['superceded'];
			$studycat = $row['studycat'];
			
			if(isset($criteria['req_from'][$fid]) && isset($criteria['from'][$fid]))
			{
				if($val != $criteria['from'][$fid]) continue;	//check for correct change-from value
			}

			$sval = '';
			$scond = '';
			switch($type)
			{
				case 'int':
				case 'bool':
				$sval = 'data_values.val_' . $type;
				$scond = (int)$criteria['to'][$fid];
				break;
					
				case 'date':
				case 'varchar':
				case 'text':
				$sval = 'data_values.val_' . $type;
				$scond = $criteria['to'][$fid];
				break;
					
				case 'enum':
				$sval = 'data_enumvals.`value`';
				$scond = $criteria['to'][$fid];
			}
			//Get to-vals even if they're not being checked becuase we need them for the output
			$query2 = 'SELECT ' . $sval . ' AS "val" FROM '
					. 'data_values LEFT JOIN data_enumvals ON data_values.val_enum=data_enumvals.id '
					. 'WHERE data_values.`field`=' . $fid . ' AND data_values.studycat=' . $studycat
					. ' AND data_values.added="' . $row['superceded'] . '"';
			$time_start = microtime(true);		
			$res2 = mysql_query($query2);
			$time_end = microtime(true);
			$time_taken = $time_end-$time_start;
			$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query2.'#Comments:run_updatereport.php checking change-to.';
			$logger->info($log);
			unset($log);
			
			if($res2 === false) return softDie('Bad SQL query checking change-to');
			$toVals = array();
			while($row2 = mysql_fetch_assoc($res2))
			{
				$toVals[] = $row2['val'];
			}

			if(isset($criteria['req_to'][$fid]) && isset($criteria['to'][$fid]))
			{
				if(!in_array($scond,$toVals)) continue;	//check for correct change-to value
			}
			
			//add data to results.
			$fieldnames[$fid] =  $row['fieldname'];
			if(!isset($results[$rkey])) $results[$rkey] = array('to' => array(), 'from' => array());
			if(!in_array($val, $results[$rkey]['from'])) $results[$rkey]['from'][] = $val;
			$results[$rkey]['to'] = array_unique(array_merge($results[$rkey]['to'], $toVals));
		}
	}
	
	//check for new records
	if($getnew)
	{
		$query = 'SELECT larvol_id,import_time FROM clinical_study WHERE import_time' . $timecond;
		if(strlen($searchcond)) $query .= ' AND ' . $searchcond;
		$time_start = microtime(true);
		$res = mysql_query($query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query2.'#Comments:run_updatereport.php check for new records.';
		$logger->info($log);
		unset($log);
		
		if($res === false) return softDie('Bad SQL query getting new records');
		while($row = mysql_fetch_assoc($res))
		{
			$results[$row['larvol_id'] . '.NULL.' . $row['import_time']] = false;
		}
	}

	$fid_nctid = getFieldId('NCT','nct_id');
	$fid_phase = getFieldId('NCT','phase');
	$fid_intname = getFieldId('NCT','intervention_name');
	$fid_title = getFieldId('NCT','brief_title');
	$fid_firstrd = getFieldId('NCT','firstreceived_date');
	$fid_lastcd = getFieldId('NCT','lastchanged_date');
	$nct_data = array_unique(array_map('lid_from_rkey', array_keys($results)));
	if(count($nct_data)) $nct_data = array_combine($nct_data, $nct_data);
	
	if($return)
	{
		$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'", total="'.(count($nct_data)).
		'", progress="0" WHERE run_id="' .$run_id .'" AND report_type ="2" AND type_id="' .$type_id .'"';
		$res = mysql_query($query);
		if($res === false)
		{
			$log  = 'Bad SQL Query updating updatescan report total. Error: '.mysql_error();
			$logger->fatal($log);
			die($log);
		}
	}
	
	foreach($nct_data as $larvol_id => $val)
	{
		$template = array('nct_id' => array(), 'phase' => array(), 'intervention_name' => array(), 'title' => array(),
									'firstreceived_date' => array(), 'lastchanged_date' => array());
		$nct_data[$larvol_id] = $template;
		$query = 'SELECT data_values.val_int AS "int", data_values.val_varchar AS "varchar", data_values.val_bool AS "bool", '
				. 'data_values.val_date AS "date", data_values.val_text AS "text", data_enumvals.`value` AS "enum", '
				. 'data_fields.`type` AS "type", data_fields.`name` AS "fname"'
				. ' FROM data_values '
				. 'LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
				. 'LEFT JOIN data_fields ON data_values.`field`=data_fields.id '
				. 'LEFT JOIN data_enumvals ON data_values.val_enum=data_enumvals.id '
				. 'WHERE data_cats_in_study.larvol_id=' . $larvol_id . ' AND superceded IS NULL AND '
				. 'data_values.`field` IN('
				. implode(',', array($fid_nctid, $fid_phase, $fid_intname, $fid_title, $fid_firstrd, $fid_lastcd)) . ')';
		$time_start = microtime(true);
		$res = mysql_query($query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query2.'#Comments:run_updatereport.php get nct-data.';
		$logger->info($log);
		unset($log);
		
		if($res === false) return softDie('Bad SQL query getting nct-data');
		while($row = mysql_fetch_assoc($res))
		{
			$type = $row['type'];
			$fieldname = $row['fname'];
			$nct_data[$larvol_id][$fieldname][] = $row[$type];
		}
		if($template == $nct_data[$larvol_id]) unset($nct_data[$larvol_id]);
		
		if($return)
		{
			$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).
			'", progress=progress+1 WHERE run_id="' .$run_id .'" AND report_type ="2" AND type_id="' .$type_id .'"';
			$res = mysql_query($query);
			if($res === false)
			{
				$log = 'Bad SQL Query updating updatescan report progress. Error: '.mysql_error();
				$logger->fatal($log);
				die($log);
			}
			if(mysql_affected_rows() == 0) exit;
		}
	}
	//if(mysql_query('COMMIT') === false) return softDie("Couldn't commit SQL transaction");

	// Create excel file object
	$objPHPExcel = new PHPExcel();
	// Set properties
	$objPHPExcel->getProperties()->setCreator(SITE_NAME);
	$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
	$objPHPExcel->getProperties()->setTitle($name);
	$objPHPExcel->getProperties()->setSubject($name);
	$objPHPExcel->getProperties()->setDescription($name);
	// Build sheet
	$objPHPExcel->setActiveSheetIndex(0);
	$sheet = $objPHPExcel->getActiveSheet();
	$sheet->setTitle(substr($name,0,31));
	$sheet->SetCellValue('A1', 'larvol_id');
	$sheet->SetCellValue('B1', 'NCT id');
	$sheet->SetCellValue('C1', 'field');
	$sheet->SetCellValue('D1', 'when');
	$sheet->SetCellValue('E1', 'old value');
	$sheet->SetCellValue('F1', 'new value');
	$sheet->SetCellValue('G1', '');
	$sheet->SetCellValue('H1', 'phase (current)');
	$sheet->SetCellValue('I1', 'intervention_name (current)');
	$sheet->SetCellValue('J1', 'title (current)');
	$sheet->SetCellValue('K1', 'firstreceived_date');
	$sheet->SetCellValue('L1', 'lastchanged_date (current)');

	$line = 2;
	foreach($results as $rkey => $val)
	{
		$rkey = explode('.', $rkey);
		$new = $rkey[1] === 'NULL';
		$larvol_id = $rkey[0];
		$fname = $new ? '(New record)' : $fieldnames[$rkey[1]];
		$when = $rkey[2];
		$nct = isset($nct_data[$larvol_id]);
		$from = $new ? '' : implode("\n", $val['from']);
		$to = $new ? '' : implode("\n", $val['to']);
		if(!$new && ($from == $to)) continue;
		
		$sheet->SetCellValue('A'.$line, $larvol_id);
		if($nct)
		{
			$paddedId = padnct($nct_data[$larvol_id]['nct_id'][0]);
			$nctlink = '=hyperlink("http://www.clinicaltrials.gov/ct2/show/' . $paddedId . '","' . $paddedId . '")';
			$sheet->SetCellValue('B'.$line, $nctlink);
		}else{
			$sheet->SetCellValue('B'.$line, '(non-NCT)');
		}
		$sheet->SetCellValue('C'.$line, $fname);
		$sheet->SetCellValue('D'.$line, $when);
		$sheet->SetCellValue('E'.$line, $from);
		$sheet->SetCellValue('F'.$line, $to);
		$sheet->SetCellValue('G'.$line, '');
		if($nct)
		{
			$sheet->SetCellValue('H'.$line, $nct_data[$larvol_id]['phase'][0]);
			$sheet->SetCellValue('I'.$line, implode("\n",$nct_data[$larvol_id]['intervention_name']));
			$sheet->SetCellValue('J'.$line, implode("\n",$nct_data[$larvol_id]['title']));
			$sheet->SetCellValue('K'.$line, implode("\n",$nct_data[$larvol_id]['firstreceived_date']));
			$sheet->SetCellValue('L'.$line, implode("\n",$nct_data[$larvol_id]['lastchanged_date']));
		}
		
		++$line;
	}
	
	
	$sheet->SetCellValue('A' . $line, '');
	$sheet->SetCellValue('A' . ++$line, 'Footnotes:');
	$sheet->SetCellValue('B' . $line, $footnotes);
	$sheet->SetCellValue('A' . ++$line, 'Description:');
	$sheet->SetCellValue('B' . $line, $description);
	
	//Create output writer
	$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	
	global $logger;
	$log = null;
	$log = ob_get_contents();
	$log = str_replace("\n", '', $log);
	if($log)
	$logger->error($log);	
	ob_end_clean();	
	
	if($return === false)
	{
		//Send download
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
		exit;
	}

	if($return === true)
	{
		$tempfile = tempnam(sys_get_temp_dir(), 'exc');
		if($tempfile === false) return softDie('Unable to create temp file');
		$objWriter->save($tempfile);
		$content = file_get_contents($tempfile);
		unlink($tempfile);
		return $content;
	}
}

function lid_from_rkey($in)
{
	$in = explode('.', $in);
	return (int)$in[0];
}
?>