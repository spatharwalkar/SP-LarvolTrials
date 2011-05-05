<?php
require_once('db.php');



$SEARCH_ERR = NULL;

/* Searches the database. Assumes input is already valid/escaped
	$params - an array of SearchParam objects
	$list - an array of field names which will be returned. Same format as in class SearchParam
	$page - which page of search results to return; start/length auto calculated using results-per-page in settings.
			Use NULL to get all.
			Use a string pair to manually specify start and length, ex. "10,5"
	$time - Timestamp for searching with Time Machine feature -- leave NULL to search current data
	$override - an array of larvol_id numbers that will always match this search regardless of the criteria
	RETURNS an array of Study objects where the keys are the larvol_id of each record,
		UNLESS $list is NULL, in which case the search returns the number of records matching the search
			OR $list is (false), in which case the SQL query is returned.
*/
function search($params=array(),$list=array('overall_status','brief_title'),$page=1,$time=NULL,$override=array(),$test=false)
{ 
	//logger variable in db.php
	global $logger;
	
	if($time !== NULL) $time = '"' . date('Y-m-d H:i:s',$time) . '"';
	$timecond='';
	if($time === NULL)
	{
		$timecond = 'dv.superceded IS NULL ';
	}else{
		$timecond = 'dv.added<' . $time . ' AND (dv.superceded>' . $time . ' OR dv.superceded IS NULL) ';
	}
	
	$priorityGet = (isset($_GET['priority']))? $_GET['priority'] : '';
	$optimizer_hints = ($priorityGet == 'high') ? 'HIGH_PRIORITY ' : '';
	
	//avoid screwing up the params for the caller, considering objects are references
	foreach($params as $key => $value) $params[$key] = clone $value;

	global $db;
	global $SEARCH_ERR;
	$conditions = array();	//includes 'requires' and 'weak exclusions'
	$g_conds = array();		//same as $conditions but for global fields
	$strong_exclusions = array();
	$sorts = array();	//array of Sort objects for sorting on normal fields
	$g_sorts = array();	//array of global field names to sort on (including DESC for descending)
	try{ 
		foreach($params as $param)
		{
			$global = (is_array($param->field) ? $param->field[0][0] : $param->field[0]) != '_';
			$type = $db->types[(is_array($param->field) ? $param->field[0] : $param->field)];
			switch($param->action)
			{ 
				case 'ascending':
				if($global)
				{
					$g_sorts[] = '`' . $param->field . '`';
				}else{
					$sorts[] = new Sort(substr($param->field,1));
				}
				break;
				
				case 'descending':
				if($global)
				{
					$g_sorts[] = '`' . $param->field . '` DESC';
				}else{
					$sorts[] = new Sort(substr($param->field,1),true);
				}
				break;
				
				case 'require':
				case 'search':
				if($global)
				{
					$field = '`clinical_study`.`' . $param->field . '`';
					if($param->action == 'require')
					{
						$g_conds[] = $field . ' IS NOT NULL';
					}else{  //in this case we're searching
						switch($type)
						{ 
							//rangeable
							case 'date':
							case 'int':
							$ORd = explode(' OR ', $param->value);
							foreach($ORd as $key => $term)
							{
								if(strpos($term, ' TO ') !== false)
								{
									$range = explode(' TO ', $term);
									if($type == 'date') $range = array_map(function($dt){return '"'.$dt.'"';},$range);
									$ORd[$key] = '(' . $field . ' BETWEEN ' . $range[0] . ' AND ' . $range[1] . ')';
								}else{
									if($type == 'date') $term = '"' . $term . '"';
									$ORd[$key] = '(' . $field . '=' . $term . ')';
								}
							}
							$cond = implode(' OR ', $ORd);
							if($param->negate) $cond = 'NOT (' . $cond . ')';
							$g_conds[]=$cond;
							break;
							//normal
							case 'bool':
							$cond = $field . '=' . $param->value;
							if($param->negate) $cond = 'NOT (' . $cond . ')';
							$g_conds[]=$cond;
							break;
							//enum is special
							case 'enum':
							$cond = $field
								. (is_array($param->value) ? (' IN("'.implode('","',$param->value).'")') : ('="'.$param->value.'"'));
							if($param->negate) $cond = 'NOT (' . $cond . ')';
							$g_conds[] = $cond;
							break;
							//regexable
							case 'varchar':
							case 'text':
							if(strlen($param->value)) $g_conds[] = textEqual($field,$param->value);
							if($param->negate !== false && strlen($param->negate))
							{
								$g_conds[] = 'NOT (' . textEqual($field,$param->negate) . ')';
							}
						}
					}
				}else{	//non-global field
					$field;
					if(is_array($param->field))	//take the underscore off the field "name" to get the ID
					{
						$field = 'dv.`field` IN(' . implode(',', array_map('highPass', $param->field)) . ')';
					}else{
						$field = 'dv.`field`=' . substr($param->field,1);
					}
					if($param->action == 'require')
					{
						$conditions[] = $field . ' AND dv.val_' . $type . ' IS NOT NULL';
					}else{  //in this case we're searching
						switch($type)
						{ 
							//rangeable
							case 'date':
							case 'int':
							$ORd = explode(' OR ', $param->value);
							foreach($ORd as $key => $term)
							{
								if(strpos($term, ' TO ') !== false)
								{
									$range = explode(' TO ', $term);
									if($type == 'date') $range = array_map(function($dt){return '"'.$dt.'"';},$range);
									$ORd[$key] = '(dv.val_' . $type . ' BETWEEN ' . $range[0] . ' AND ' . $range[1] . ')';
								}else{
									if($type == 'date') $term = '"' . $term . '"';
									$ORd[$key] = '(dv.val_' . $type . '=' . $term . ')';
								}
							}
							$cond = implode(' OR ', $ORd);
							$cond = $field . ' AND (' . $cond . ')';
							if($param->negate && $param->strong)
							{
								$strong_exclusions[] = $cond;
							}else{
								if($param->negate) $cond = 'NOT (' . $cond . ')';
								$conditions[] = $cond;
							}
							break;
							//normal
							case 'bool':
							$cond = $field . ' AND dv.val_bool=' . $param->value;
							if($param->negate && $param->strong)
							{
								$strong_exclusions[] = $cond;
							}else{
								if($param->negate) $cond = 'NOT (' . $cond . ')';
								$conditions[] = $cond;
							}
							break;
							//enum is special
							case 'enum':
							$enumq = is_array($param->value) ? (' IN("'.implode('","',$param->value).'")') : ('="'.$param->value.'"');
							$cond = $field . ' AND dv.val_enum' . $enumq;
							if($param->negate && $param->strong)
							{
								$strong_exclusions[] = $cond;
							}else{
								if($param->negate) $cond = $field . ' AND NOT (dv.val_enum' . $enumq . ')';
								//if($param->negate) $cond = 'NOT (' . $cond . ')';
								$conditions[] = $cond;
							}
							break;
							//regexable
							case 'varchar':
							case 'text':
							if(!is_array($param->field))	//normal single-field param
							{
								if(strlen($param->value))
									$conditions[] = $field . ' AND ' . textEqual('dv.val_' . $type,$param->value);
								if($param->negate !== false && strlen($param->negate))
								{
									if($param->strong)
									{
										$strong_exclusions[] = $field . ' AND ' . textEqual('dv.val_' . $type,$param->negate);
									}else{
										//$conditions[] = 'NOT (' . $field . ' AND ' . textEqual('dv.val_' . $type,$param->negate) . ')';
										$conditions[] = $field . ' AND NOT ' . textEqual('dv.val_' . $type,$param->negate);
									}
								}
							}else{	//Merge varchar and text multifields
								if(strlen($param->value))
								{
									$conditions[] = $field . ' AND (' . textEqual('dv.val_text',$param->value) . ' OR '
														. textEqual('dv.val_varchar',$param->value) . ')';
								}
								if($param->negate !== false && strlen($param->negate))
								{
									if($param->strong)
									{
										$strong_exclusions[] = $field . ' AND (' . textEqual('dv.val_text',$param->negate) . ' OR '
														. textEqual('dv.val_varchar',$param->negate) . ')';
									}else{
										$conditions[] = $field . ' AND NOT (' . textEqual('dv.val_text',$param->value) . ' OR '
														. textEqual('dv.val_varchar',$param->value) . ')';
										/*$conditions[] = 'NOT (' . $field . ' AND (' . textEqual('dv.val_text',$param->negate) . ' OR '
														. textEqual('dv.val_varchar',$param->negate) . '))';*/
									}
								}
							}
						}
					}
				}
			}
		}
	
	}catch(Exception $e){
		$SEARCH_ERR = $e->getMessage();
		return softDie($e->getMessage());
	}
	
	//prechecking search conditions before going for executing large search queries if search() is run in test mode
	if($test == true)
	{
		if(!precheckSearchSql($conditions, $g_conds, $strong_exclusions))
		{
	
		    $getVars=isset($_POST['getVars'])?$_POST['getVars']:"";
			echo "<h2>";
			echo "Please, correct search parameters.";
			echo "</h2>";
			echo('<form method="post" action="' . ($_POST['simple']?'search_simple.php':'search.php') . '?'.$getVars.'">'
				. '<input name="oldsearch" type="hidden" value="' . base64_encode(serialize($_POST)) . '" />'
				. '<input type="submit" name="back2s" value="Edit Search" /></form>');
				
				//is a special case -- this one should only have a log level of Warn instead of the standard Fatal.
				$logger->warn('Bad fields present. Correct search parameters');
		    die();
		
		}else
		{
			return true;
		}
	}
	
	/*One condition that would normally go in one of the chains must
		be pulled out and done separately or else performance will be poor
	*/
	$lone_cond = '';
	if(!empty($conditions))
	{
		$key = max(array_keys($conditions));
		$lone_cond = $conditions[$key] . ' AND ' . $timecond;
		unset($conditions[$key]);
	}else if(!empty($g_conds)){
		$key = max(array_keys($g_conds));
		$lone_cond = $g_conds[$key];
		unset($g_conds[$key]);
	}else if(!empty($strong_exclusions)){
		$lone_cond = 1;
		//$key = max(array_keys($strong_exclusions));
		//$lone_cond = 'NOT (' . $strong_exclusions[$key] . ' AND ' . $timecond . ')';
		//unset($strong_exclusions[$key]);
	}else{
		$lone_cond = NULL;
	}
	
	//execute the queries and gather results
	foreach($conditions as $i => $cond)
	{
		$query = 'SET @conds_' . $i . ' := '
				. '(SELECT GROUP_CONCAT(DISTINCT i.larvol_id) AS "larvol_id" '
				. 'FROM (data_values AS dv LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id) WHERE ' . $cond . ' AND ';
		$query .= $timecond;
		if($i > 0) $query .= ' AND FIND_IN_SET(i.larvol_id, @conds_' . ($i-1) . ') > 0';
		$query .= ')';
		//var_dump($query);
		$time_start = microtime(true);
		$res = mysql_query($query);
		if($res === false)
		{
			$log = 'Bad SQL query applying search condition: ' . $query;
			return softDie($log);	
		}
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:execute the queries and gather results';
		$logger->info($log);
		unset($log);
		//
	}
	
	foreach($g_conds as $i => $cond)
	{
		$ii = $i + count($conditions);
		$query = 'SET @conds_' . $ii . ' := '
				. '(SELECT GROUP_CONCAT(larvol_id) FROM clinical_study WHERE ' . $cond;
		if($ii > 0) $query .= ' AND FIND_IN_SET(larvol_id, @conds_' . ($ii-1) . ') > 0';
		$query .= ')';
		$time_start = microtime(true);
		$res = mysql_query($query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:global conditions loop';
		$logger->info($log);
		unset($log);
		
		if($res === false)
		{
			$log = 'Bad SQL query applying search condition (global field)'.mysql_error().$query;
			return softDie($log);
		}
	}
	
	if(!empty($strong_exclusions))
	{
		$seq = array();
		foreach($strong_exclusions as $cond)
		{
			$query = 'SELECT DISTINCT i.larvol_id AS "larvol_id" FROM '
					. '(data_values AS dv LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id) WHERE ' . $cond . ' AND ';
			$query .= $timecond;
			$seq[] = $query;
		}
		$seq = 'SET @seq_union := (SELECT GROUP_CONCAT(larvol_id) as "larvol_id" FROM ('
				. implode(' UNION ',$seq) . ') AS resultset)';
		$time_start = microtime(true);
		$seq = mysql_query($seq);
		if($seq === false)
		{
			$log = 'Bad SQL query applying strong exclusions';
			return softDie($log);
		}
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$seq.'#Comments:strong exclusions present';
		$logger->info($log);
		unset($log);
		
	}
	
	$bigquery;
	if(!empty($override))	//if there are nct overrides, start building the bigquery to include them now
	{ 
		$drop_query = 'DROP TABLE IF EXISTS ulid';
		$time_start = microtime(true);
		mysql_query($drop_query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$drop_query.'#Comments:overrides dropping table ulid';
		$logger->info($log);
		unset($log);
		
		$create_temp_query = 'CREATE TEMPORARY TABLE ulid (larvol_id int NOT NULL)';
		$time_start = microtime(true);
		mysql_query($create_temp_query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$create_temp_query.'#Comments:overrides, creating temporary table ulid';
		$logger->info($log);
		unset($log);	
		
		$insert_query = 'INSERT INTO ulid VALUES ' . implode(',', parenthesize($override));//temp variable only for logging purpose
		$time_start = microtime(true);
		mysql_query($insert_query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$insert_query.'#Comments:overrides,inserting into ulid';
		$logger->info($log);
		unset($log);	
		
		$bigquery = ' UNION SELECT larvol_id FROM ulid';
		//$bigquery = ' OR larvol_id IN(SELECT larvol_id FROM ulid)';//slow
	}

	$sortjoins = '';
	$orderby = array();
	if(!empty($sorts))
	{
		$catjoin = ($lone_cond === NULL) ? 'clinical_study' : 'i';
		$sortcats = array();
		foreach($sorts as &$sort)
		{
			$query = 'SELECT category,`type` FROM data_fields WHERE id=' . $sort->field . ' LIMIT 1';
			$res = mysql_query($query);
			if($res === false)
			{
				$log = 'Bad SQL query getting category of field for sorting';
				return softDie($log);
			}
			$res = mysql_fetch_assoc($res);
			if($res === false)
			{
				$log = 'Sort field not found.';
				return softDie($log);
			}
			$sort->type = $res['type'];
			$cat = $res['category'];
			$sortcats[$cat] = $cat;
			$sort->category = $cat;
		}unset($sort);
		foreach($sortcats as $cat)
		{
			$sft = 'i_s' . $cat;
			$sortjoins .= ' LEFT JOIN data_cats_in_study as ' . $sft . ' ON ' . $sft . '.larvol_id=' . $catjoin . '.larvol_id AND '
						. $sft . '.category=1';
		}unset($cat);
		foreach($sorts as $sort)
		{
			$sft = 'dv_s' . $sort->field;
			$esft = $sft . '_e';
			$sortjoins .= ' LEFT JOIN data_values as ' . $sft . ' ON i_s' . $sort->category . '.id='
						. $sft . '.studycat AND ' . $sft . '.`field`=' . $sort->field . ' AND ' . str_replace('dv',$sft,$timecond);
			$by = $sft . '.val_' . $sort->type;
			if($sort->type == 'enum')
			{
				$sortjoins .= ' LEFT JOIN data_enumvals AS ' . $esft . ' ON ' . $esft . '.id=' . $sft . '.val_enum';
				$by = $esft . '.`value`';
			}
			if($sort->desc) $by .= ' DESC';
			$orderby[] = $by;
		}unset($sort);
	}

	if($lone_cond === NULL)	//in this case, there were no search parameters
	{
		$bigquery = 'SELECT clinical_study.larvol_id FROM clinical_study' . $sortjoins;
	}else{	//There were search parameters, so use them in the main query
		$bigconds = array();
		$bigconds[] = '(' . $lone_cond . ')';
		if(!empty($conditions) || !empty($g_conds))
			$bigconds[] = 'FIND_IN_SET(i.larvol_id, @conds_' . (count($conditions)+count($g_conds)-1) . ') > 0';
		if(!empty($strong_exclusions))
			$bigconds[] = 'NOT FIND_IN_SET(i.larvol_id, @seq_union ) > 0';
		if(empty($bigconds))
		{
			$bigconds = '1';
		}else{
			$bigconds = implode(' AND ', $bigconds);
		}
		$bigquery = 'SELECT DISTINCT i.larvol_id AS "larvol_id" FROM '
					. '(data_values AS dv '
					. 'LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id '
					. 'LEFT JOIN clinical_study ON i.larvol_id=clinical_study.larvol_id)' . $sortjoins
					. ' WHERE (' . $bigconds . ')' . $bigquery;
	}

	if($list === NULL)	//option to return total number of records instead of full search results
	{
		$bigquery = 'SELECT COUNT(larvol_id) AS "ctotal" FROM (' . $bigquery . ') AS resultset';
		$time_start = microtime(true);
		$res = mysql_query($bigquery);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$bigquery.'#Comments:option to return total number of records instead of full search results';
		$logger->info($log);
		unset($log);	
				
		
		if($res === false)
		{
			$log = 'Bad SQL query on count search: ' . $bigquery . "<br />\n" . mysql_error();
			return softDie($log);
		}
		$row = mysql_fetch_assoc($res);
		if($row === false)
		{
			$log = 'Total not found.';
			$logger->fatal($log);
			die($log);
			unset($log);	
		}
		
		return $row['ctotal'];
	}

	if($list === false)	//option to return the SQL query
	{
		return $bigquery;
	}

	if(!empty($g_sorts) || !empty($orderby))
	{
		$orderby = implode(',', array_merge($g_sorts,$orderby));
		$bigquery .= ' ORDER BY ' . $orderby;
	}

	//apply limit
	$limit = '';
	$start = '';
	$end = '';
	$length = '';
	if($page !== NULL)
	{
		if(strpos($page,',') === false)
		{
			$start = ($page - 1) * $db->set['results_per_page'];
			$end = $page * $db->set['results_per_page'] - 1;
			$length = $db->set['results_per_page'];
			$limit = $start . ',' . $length;
		}else{
			$limit = $page;
		}
		$limit = ' LIMIT ' . $limit;
	}
	$bigquery .= $limit;
	//var_dump($bigquery);exit;
	//Do search and get result IDs for the page
	$time_start = microtime(true);
	$res = mysql_query($bigquery);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$bigquery.'#Comments:do search and get result IDs for the page';
	$logger->info($log);
	unset($log);	
	
	if($res === false)
	{
		$log = 'Bad SQL query on search: ' . $bigquery . "<br />\n" . mysql_error();
		return softDie($log);
	}
	$resid_set = array();
	while($row = mysql_fetch_assoc($res)) $resid_set[] = $row['larvol_id'];
	//get requested data for the page
	
	$recordsData = getRecords($resid_set,$list,$time);
	
	$retdata = array();
	foreach($resid_set as $id) $retdata[$id] = $recordsData[$id];	//preserve sorting
	return $retdata;
}

//todo: this probably should not be NCT-centric
function getField($params, $field)
{ 
	$id = '_'.getFieldId('NCT', $field);
	foreach ($params as $param)
	{
		$fields = is_array($param->field) ? $param->field : array($param->field);
		foreach($fields as $field)
			if($field == $id) return $param;
	}
	return null;
}

function getBackboneAgent($params)
{
	return getField($params, "intervention_name");
}

function applyBackboneAgent($ids, $term)
{
	//logger variable in db.php
	global $logger;	
	
	if(count($ids) == 0) return array();
	$ids = implode(",", $ids);
	$interventionId = getFieldId("NCT", "intervention_name");
	$apply_backbone_agent_query = "SELECT DISTINCT i.larvol_id AS id
			FROM data_cats_in_study AS i
			INNER JOIN data_values AS dv ON i.id = dv.studycat AND
			dv.field = ".$interventionId."
			WHERE i.larvol_id IN (".$ids.") AND dv.superceded IS NULL AND
			dv.val_varchar <> '".mysql_real_escape_string($term)."'";
	$time_start = microtime(true);
	$rs = mysql_query($apply_backbone_agent_query);
	
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$apply_backbone_agent_query.'#Comments:apply backbone agent';
	$logger->info($log);
	unset($log);	
	
	$result = array();
	while ($row = mysql_fetch_assoc($rs))
		$result[] = $row["id"];
	return $result;
}

function getActiveCount($all_ids, $time)
{
	//logger variable in db.php
	global $logger;	
		
	$time = '"' . date('Y-m-d H:i:s',$time) . '"';
	if(!is_array($all_ids) || empty($all_ids)) return 0;
	$ids = implode(', ',$all_ids);
	$overallStatusId = getFieldId("NCT", "overall_status");
	$activeStatuses = getEnumvalId($overallStatusId, "Active, not recruiting").",".
	getEnumvalId($overallStatusId, "Not yet recruiting").",".
	getEnumvalId($overallStatusId, "Recruiting").",".
	getEnumvalId($overallStatusId, "Enrolling by invitation").",".
	getEnumvalId($overallStatusId, "Active, not recruiting").",".
	getEnumvalId($overallStatusId, "Available");
	
	$query = "SELECT i.larvol_id,dv.field,dv.val_enum AS id FROM data_values AS dv
					LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id
					WHERE i.larvol_id IN (".$ids.") AND dv.field = ".$overallStatusId." AND
					dv.val_enum IN (".$activeStatuses.") AND dv.added < " . $time 
					. " AND ( dv.superceded>" . $time . " OR dv.superceded IS NULL) ";
	$time_start = microtime(true);					
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:get active count';
	$logger->info($log);
	unset($log);	
	
	$id_set = array();
	if($res === false)
	{
		$log = 'Bad SQL query on active status : ' . $query . "<br />\n" . mysql_error();
		return softDie($log);
	}
	while($row = mysql_fetch_array($res)) $id_set[] = $row['id'];
	
	return count($id_set);
}

function getBomb($ids)
{
	//logger variable in db.php
	global $logger;
		
	if (count($ids) == 0)
		return "";
	$overallStatusId = getFieldId("NCT", "overall_status");//echo "<pre>";print_r($ids);exit;
	$phaseId = getFieldId("NCT","phase");
	$terminatedId = getEnumvalId($overallStatusId, "Terminated");
	$suspendedId = getEnumvalId($overallStatusId, "Suspended");
	$bombStatuses = getEnumvalId($overallStatusId, "Active, not recruiting").",".
		getEnumvalId($overallStatusId, "Not yet recruiting").",".
		getEnumvalId($overallStatusId, "Recruiting").",".
		getEnumvalId($overallStatusId, "Enrolling by invitation");
	$ids = implode(",", $ids);
	$past = "'".date("Y-m-d H:i:s", time() - (int)(0.1*1.5*24*3600))."'";
	
	$get_bomb_query1 = "SELECT i.larvol_id AS id FROM data_values AS dv
			LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id
			WHERE dv.field = ".$overallStatusId." AND
			dv.val_enum IN (".$terminatedId.",".$suspendedId.") AND
			i.larvol_id IN (".$ids.") AND dv.added < ".$past." AND
			dv.superceded IS NULL";
	$time_start = microtime(true);	
	$rs = mysql_query($get_bomb_query1);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$get_bomb_query1.'#Comments:get trial rows';
	$logger->info($log);
	unset($log);	
	
	$trials = array();
	while ($row = mysql_fetch_assoc($rs))
		$trials[] = $row["id"];
	if (count($trials) == 0)
		return "";
	$trials = implode(",", $trials);
	$get_bomb_query2 = "SELECT MAX(val_enum) AS phase FROM data_values AS dv
			LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id
			WHERE dv.field = ".$phaseId." AND dv.superceded IS NULL AND
			i.larvol_id IN (".$ids.") AND i.larvol_id NOT IN (".$trials.")";
	$time_start = microtime(true);
	$rs = mysql_query($get_bomb_query2);
	$row = mysql_fetch_assoc($rs);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$get_bomb_query2.'#Comments:get phase';
	$logger->info($log);
	unset($log);	
	
	$phase = $row["phase"];
	$get_bomb_query3 = "SELECT 1 FROM data_values AS dv1
			INNER JOIN data_cats_in_study AS i ON dv1.studycat=i.id
			INNER JOIN data_values AS dv2 ON dv2.studycat=i.id
			WHERE dv1.field = ".$phaseId." AND dv1.superceded IS NULL AND
			dv1.val_enum = ".$phase." AND i.larvol_id IN (".$ids.") AND
			i.larvol_id NOT IN (".$trials.") AND
			dv2.field = ".$overallStatusId." AND dv2.superceded IS NULL AND
			dv2.val_enum IN (".$bombStatuses.")";
	$time_start = microtime(true);
	$rs = mysql_query($get_bomb_query3);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$get_bomb_query3.'#Comments:find sb or lb';
	$logger->info($log);
	unset($log);	
	
	if (mysql_fetch_assoc($rs))
		return "sb";
	return "lb";
}
//Helper for above and below. todo: convert to anonymous function now that gentoo has php 5.3 support
function highPass($v){return substr($v,1);}

//return an array of study maps corresponding to $ids, with only $fields populated
function getRecords($ids,$fields,$time)
{
	
	global $db;
	//logger variable in db.php
	global $logger;	
	$result = array();
	if(empty($ids)) return $result;
	$global = array('larvol_id');
	if(($k = array_search('larvol_id',$fields)) !== false)
	{
		unset($fields[$k]);
	}
	if(($k = array_search('institution_type',$fields)) !== false)
	{
		$global[] = 'institution_type';
		unset($fields[$k]);
	}
	if(($k = array_search('import_time',$fields)) !== false)
	{
		$global[] = 'import_time';
		unset($fields[$k]);
	}
	if(($k = array_search('last_change',$fields)) !== false)
	{
		$global[] = 'last_change';
		unset($fields[$k]);
	}
	$fields = array_map('highPass', $fields);
	$query = 'SELECT ' . implode(',', $global) . ' FROM clinical_study WHERE larvol_id IN(' . implode(',', $ids) . ')';
	
	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:get records query 1';
	$logger->info($log);
	unset($log);	
		
	if($res === false)
	{
		$log = 'Bad SQL query getting global fields for result list<br />'.$query;
		return softDie($log);
	}
	while($row = mysql_fetch_assoc($res))
	{
		$result[$row['larvol_id']] = array('larvol_id' => $row['larvol_id']);
		foreach($row as $field => $value)
		{
			$result[$row['larvol_id']][$field] = $value;
		}
	}
	if(count($fields))
	{
		if($time === NULL)
		{
			$time = 'data_values.superceded IS NULL';
		}else{
			$time = 'data_values.added<' . $time . ' AND (data_values.superceded>' . $time . ' OR data_values.superceded IS NULL) ';
		}
		$table = 'data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
				. 'LEFT JOIN data_fields ON data_values.`field`=data_fields.id '
				. 'LEFT JOIN data_enumvals ON data_values.val_enum=data_enumvals.id '
				. 'LEFT JOIN data_categories ON data_fields.category=data_categories.id';
		$query = 'SELECT data_values.val_int AS "int",data_values.val_bool AS "bool",data_values.val_varchar AS "varchar",'
				. 'data_values.val_date AS "date",data_enumvals.`value` AS "enum",data_values.val_text AS "text",'
				. 'data_cats_in_study.larvol_id AS "larvol_id",data_fields.`type` AS "type",data_fields.`name` AS "name",'
				. 'data_categories.`name` AS "category" FROM ' . $table
				. ' WHERE ' . $time
				. ' AND data_values.`field` IN(' . implode(',', $fields) . ') AND larvol_id IN(' . implode(',', $ids) . ')';
		
				
		$time_start = microtime(true);
		$res = mysql_query($query);
		$time_end = microtime(true);
		$time_taken = $time_end-$time_start;
		$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:get records query 2';
		$logger->info($log);
		unset($log);	
		
		if($res === false)
		{
			$log = 'Bad SQL query getting data for result set<br />'.$query.'<br />'.mysql_error();
			return softDie($log);
		}

		while($row = mysql_fetch_assoc($res))
		{
			$id = $row['larvol_id'];
			$place = $row['category'] . '/' . $row['name']; //fully qualified field name
			$val = $row[$row['type']];
			//check if we already have a value for this field and ID
			if(isset($result[$id][$place]))
			{
				//now we know the value will have to be an array
				//check if there are already multiple values here
				if(is_array($result[$id][$place]))
				{
					//Add the new value to the existing array
					 $result[$id][$place][] = $val;
				}else{
					//Existing value was singular, so we turn it into an array and add the new value.
					$result[$id][$place] = array($result[$id][$place], $val);
				}
			}else{
				//No previous value, so this value goes in the slot by itself.
				$result[$id][$place] = $val;
			}
		}
	}
	
	return $result;
}

//Adds the search params to session data (if not already there) and marks it
//as being the latest
function storeParams($params)
{
	if(!isset($_SESSION['params']) || !is_array($_SESSION['params']))
	{
		$_SESSION['params'] = array();
	}
	if(!isset($_SESSION['counts']) || !is_array($_SESSION['counts']))
	{
		$_SESSION['counts'] = array();
	}
	$pos = array_search($params, $_SESSION['params']);
	if($pos !== false)
	{
		$_SESSION['latest'] = $pos;
	}else{
		$pos = count($_SESSION['params']);
		$_SESSION['params'][$pos] = $params;
		$_SESSION['latest'] = $pos;
	}
	session_write_close();
}

//gets the ID of a field given the name (and category)
//returns false on failure.
function getFieldId($category,$name)
{
	//logger variable in db.php
	global $logger;	
	
	$query = 'SELECT data_fields.id AS "id" '
		. 'FROM data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="' . $category . '" LIMIT 1';
		
	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:get field id query 2';
	$logger->info($log);
	unset($log);	
	
	if($res === false)
	{
		$log = 'Bad SQL query getting field ID of ' . $category . '/' . $name;
		return softDie($log);
	}
	$res = mysql_fetch_assoc($res);
	if($res === false)
	{
		$log = 'Field ' . $name . ' not found in category ' . $category . '!';
		return softDie($log);
	}
	return $res['id'];
}

//gets the ID of an enum value (from data_enumvals) given the field ID and string value
function getEnumvalId($fieldId,$value)
{
	//logger variable in db.php
	global $logger;	
	
	if($value === NULL || $value === 'NULL') return 'NULL';
	$query = 'SELECT id FROM data_enumvals WHERE `field`=' . $fieldId . ' AND `value`="' . $value . '" LIMIT 1';
	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:get enum val id';
	$logger->info($log);
	unset($log);	
		
	if($res === false)
	{
		$log = 'Bad SQL query getting ID of enumval ' . $value . ' in field ' . $fieldId;
		return softDie($log);
	}
	$res = mysql_fetch_assoc($res);
	if($res === false)
	{
		$log = 'Enumval ' . $value . ' invalid for field ' . $fieldId . '!';
		return softDie($log);
	}
	return $res['id'];
}

//Outputs SQL expression to match text -- auto-detects use of regex and selects comparison method automatically
function textEqual($field,$value)
{
	$pcre = strlen($value) > 1
			&& $value[0] == '/'
			&& ($value[strlen($value)-1] == '/' || ($value[strlen($value)-2] == '/' && strlen($value) > 2));
	if($pcre)
	{
	    //alexvp added exception 
	    $result=validateMaskPCRE($value);
	    if(!$result)
	    	throw new Exception("Bad regex: $field = $value", 6);
		return 'PREG_RLIKE("' . $value . '",' . $field . ')';
	}else{
		return $field . '="' . $value . '"';
	}
}

/* converts a field spec from the format:
	table/column
	to:
	`table`.`column` AS "table/column"
*/
function fieldsplit($f, $alias = true)
{
	global $db;
	if(substr($f,0,1) == '_') return $f; //Custom fields don't get modified
	$table = '';
	$field = '';
	$as = '';
	$ex = explode('/',$f);
	if(strpos($f,'/') === false)
	{
		$ex[1] = $ex[0];
		$ex[0] = 'clinical_study';
	}

	if(!in_array($ex[0],$db->rel_tab))
	{
		$db->rel_tab[] = $ex[0];
	}

	$table = '`' . $ex[0] . '`';
	$field = '`' . $ex[1] . '`';

	$retval = $table . '.' . $field . $as;
	return $retval;
}

//Takes an array of raw searchdata and removes the non-action elements
function removeNullSearchdata($data)
{
	if(!is_array($data)){ return ;  }
	$search = $data['search'];
	$display = $data['display'];
	$action = $data['action'];
	$searchval = $data['searchval'];
	$negate = is_array($data['negate']) ? $data['negate'] : array();
	$page = $data['page'];
	$multifields = is_array($data['multifields']) ? $data['multifields'] : array();
	$multivalue = $data['multivalue'];
	$time_machine = $data['time_machine'];
	$override = $data['override'];
	foreach($action as $field => $actval)
	{
		if($actval == "0")
		{
			unset($action[$field]);
			unset($searchval[$field]);
			unset($negate[$field]);
		}
	}
	return array('search' => $search, 'display' => $display, 'action' => $action, 'searchval' => $searchval, 'negate' => $negate,
				 'page' => $page, 'multifields' => $multifields, 'multivalue' => $multivalue, 'time_machine' => $time_machine,
				 'override' => $override);
}

//takes postdata from search form, returns an array of searchparams ready to feed into the search
function prepareParams($post)
{
	global $db;
	$params = array();
	$negate = array();
	if(is_array($post['negate']))
	{
		foreach($post['negate'] as $field => $ok)
		{
			if(!array_key_exists($field,$db->types)) continue;
			if(!strlen($ok))
			{
				$negate[$field] = false;
			}else if($db->types[$field] == 'date'){
				$svals = explode(' TO ',$ok);
				foreach($svals as $skey => $svalue)
				{
					$svals[$skey] = date("Y-m-d", strtotime($svalue));
				}
				$negate[$field] = implode(' TO ', $svals);
			}else if($db->types[$field] == 'datetime'){
				$svals = explode(' TO ',$ok);
				foreach($svals as $skey => $svalue)
				{
					$svals[$skey] = date("Y-m-d H:i:s", strtotime($svalue));
				}
				$negate[$field] = implode(' TO ', $svals);
			}else{
				$negate[$field] = $ok;
			}
		}
	}
	foreach($post['action'] as $field => $action)
	{
		if(!in_array($action,array('search','ascending','descending','require')) || !array_key_exists($field,$db->types))
		{
			continue;
		}
		
		$par = new SearchParam();
		$par->field = $field;
		$par->action = $action;
		if($action == 'search')
		{
			$sval = $post['searchval'][$field];
			if($db->types[$field] == 'date')
			{
				$svals = explode(' TO ',$sval);
				foreach($svals as $skey => $svalue)
				{
					$svals[$skey] = date("Y-m-d", strtotime($svalue));
				}
				$sval = implode(' TO ', $svals);
			}else if($db->types[$field] == 'datetime'){
				$svals = explode(' TO ',$sval);
				foreach($svals as $skey => $svalue)
				{
					$svals[$skey] = date("Y-m-d H:i:s", strtotime($svalue));
				}
				$sval = implode(' TO ', $svals);
			}
			$par->value = $sval;
		}
		$par->negate = $negate[$field];
		if(isset($post['weak'][$field])) $par->strong = false;
		$params[] = $par;
	}
	if(isset($post['multifields']) && count($post['multifields']))
	{
		foreach($post['multifields'] as $type => $fnames)
		{
			$par = new SearchParam();
			$par->field = $fnames;
			$par->action = 'search';
			$par->value = $post['multivalue'][$type];
			$params[] = $par;
		}
	}
	return $params;
}

//converts an nct_id to a larvol_id. Returns boolean false on failure.
function nctidToLarvolid($id)
{
	//logger variable in db.php
	global $logger;	

	$id = (int)$id;
	if(!is_numeric($id)) return false;
	$field = getFieldId('NCT','nct_id');
	if($field === false) return false;
	$query = 'SELECT larvol_id FROM data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id'
			. ' WHERE superceded IS NULL AND `field`=' . $field . ' AND val_int=' . ((int)unpadnct($id)) . ' LIMIT 1';
	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:nctid to larvol id';
	$logger->info($log);
	unset($log);	
	
	if($res === false) return false;
	$res = mysql_fetch_assoc($res);
	if($res === false) return false;
	return $res['larvol_id'];
}

//returns all elements (as dataDiff) that are different between two ClinicalStudy objects
function objDiff($a,$b,$pre='')
{
	global $db;
	$pre .= strlen($pre) ? '/' : '';
	$ret = array();
	foreach($b as $prop => $val)
	{
		$field = $pre . $prop;
		if(is_array($val) && isset($a->{$prop}))
		{
			foreach($val as $key => $aval)
			{
				if(is_object($aval))
				{
					$ret = array_merge($ret,objDiff($a->{$prop}[$key],$b->{$prop}[$key], $field));
				}else{
					$c1 = $a->{$prop}[$key];
					$c2 = $b->{$prop}[$key];
					if($c1 != $c2)
					{
						$nr = new dataDiff();
						$nr->field = $field;
						$nr->oldval = $a->{$prop}[$key];
						$nr->newval = $b->{$prop}[$key];
						$ret[] = $nr;
					}
				}
			}
		}else{
			$c1 = $a->{$prop};
			$c2 = $b->{$prop};
			if($c1 != $c2)
			{
				$nr = new dataDiff();
				$nr->field = $field;
				$nr->oldval = $a->{$prop};
				$nr->newval = $b->{$prop};
				$ret[] = $nr;
			}
		}
	}
	return $ret;
}

class dataDiff
{
	public $field; //(in SearchParam "field" format)
	public $oldval;
	public $newval;
}

//Information needed to do a sort on a Normal Field
class Sort
{
	public $field;		//field number in data_fields
	public $desc = false;	//true if sort is descending
	public $category;
	public $type;
	function __construct($field, $desc = false)
	{
		$this->field = $field;
		$this->desc = $desc;
	}
}

function validateMaskPCRE($s)
{
	//logger variable in db.php
	global $logger;	
	
	$s=addslashes($s);
	$query = "SELECT PREG_CHECK('$s')";

	$time_start = microtime(true);
	$res = mysql_query($query);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:validateMaskPCRE';
	$logger->info($log);
	unset($log);	
	if($res === false)
	{
		$log = 'Bad SQL query on search: ' . $query . "<br />\n" . mysql_error();
		return softDie($log);
	}

	list($check)=mysql_fetch_row($res);
	return ($check==1); // pcre ok?
}

function validateInputPCRE($post)
{
    global $db;
	//logger variable in db.php
	global $logger;	    

    $badFields=array();
	if(isset($post['action']) && is_array($post['action']))
	{
		foreach($post['action'] as $field => $action)
		{
		    //skip not valid fields
			if(!in_array($action,array('search')) || !array_key_exists($field,$db->types))
				continue;
	
			//skip ! text or varchar
			$fldType=$db->types[$field];
			if( ($fldType!="text") AND ($fldType!="varchar")) 
				continue;
	
			$mask=$post['searchval'][$field];
			$pcre = strlen($mask) > 1
				&& $mask[0] == '/'
				&& ($mask[strlen($mask)-1] == '/' || ($mask[strlen($mask)-2] == '/' && strlen($mask) > 2));
	
			$mask2=$post['negate'][$field];
			$pcre2 = strlen($mask2) > 1
				&& $mask2[0] == '/'
				&& ($mask2[strlen($mask2)-1] == '/' || ($mask2[strlen($mask2)-2] == '/' && strlen($mask2) > 2));
	
			if($pcre && !validateMaskPCRE($mask))
			{
				//need in field name !
				$CFid = substr($field,1);
				$query = 'SELECT name FROM data_fields WHERE id=' . $CFid;
				$time_start = microtime(true);
				$res = mysql_query($query);
				if($res === false)
				{
					$log = 'Bad SQL query getting field name for '.$CFid;
					$logger->fatal($log);
					die($log);
					unset($log);
				}
				$time_end = microtime(true);
				$time_taken = $time_end-$time_start;
				$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:validateInputPCRE query 1';
				$logger->info($log);
				unset($log);	
				list($fieldName)=mysql_fetch_row($res);
				$fieldName=str_replace("_"," ",$fieldName);
				$badFields[$fieldName]=$mask;
			}
			if($pcre2 && !validateMaskPCRE($mask2))
			{
				//need in field name !
				$CFid = substr($field,1);
				$query = 'SELECT name FROM data_fields WHERE id=' . $CFid;
				$time_start = microtime(true);
				$res = mysql_query($query);
				if($res === false)
				{
					$log = 'Bad SQL query getting field name for '.$CFid;
					$logger->fatal($log);
					die($log);
					unset($log);
				}
				$time_end = microtime(true);
				$time_taken = $time_end-$time_start;
				$log = 'Time_Taken:'.$time_taken.'#Query_Details:'.$query.'#Comments:validateInputPCRE query 2';
				$logger->info($log);
				unset($log);	
				list($fieldName)=mysql_fetch_row($res);
				$fieldName=str_replace("_"," ",$fieldName);
				$badFields[$fieldName]=$mask2;
			}
		}
	}
	if(isset($post['multivalue']) && is_array($post['multivalue']) && $post['multivalue']['varchar+text']);
	{
		$mask = $post['multivalue']['varchar+text'];
		$pcre = strlen($mask) > 1
			&& $mask[0] == '/'
			&& ($mask[strlen($mask)-1] == '/' || ($mask[strlen($mask)-2] == '/' && strlen($mask) > 2));
		if($pcre && !validateMaskPCRE($mask))
		$badFields['varchar+text']=$mask;
	}
	if($badFields)
	{
	    $getVars=isset($_POST['getVars'])?$_POST['getVars']:"";
		echo "<h2>";
		echo "Please, correct next regular expressions";
		echo "<ul>";
		foreach($badFields as $name=>$mask)
			echo "<li>$name =>  $mask";
		echo "</ul>";
		echo "</h2>";
		echo('<form method="post" action="' . ($_POST['simple']?'search_simple.php':'search.php') . '?'.$getVars.'">'
			. '<input name="oldsearch" type="hidden" value="' . base64_encode(serialize($_POST)) . '" />'
			. '<input type="submit" name="back2s" value="Edit Search" /></form>');
			
			//is a special case -- this one should only have a log level of Warn instead of the standard Fatal.
			$logger->warn('Bad fields present. Correct next regular expression');
	    die();
	}
}
//alexvp end

/**
   * cmpdate()
   * @param int $a
   * @param int $b
   * date comparison callback used in run_heatmap.php, run_competitor.php
**/
function cmpdate($a, $b) {
	if ($a == $b) return 0;
	    return (strtotime($a) < strtotime($b))? -1 : 1;
}

/**
 * 
 * @name precheckSearchSql
 * @tutorial Function simulates search params for errors prior to executing the big search queries.
 * The parameters are arrays which hold the different search parameters.
 * Sample queries are generated and executed for all the 3 parameters and if any of them fails search is not proceeded.
 * @param array $conditions
 * @param array $g_conds
 * @param array $strong_exclusions
 * @author Jithu Thomas
 */

function precheckSearchSql($conditions,$g_conds,$strong_exclusions)
{
	global $db;
	if(isset($conditions) && is_array($conditions) && count($conditions)>0)
	{
		$tmpSql = 'SELECT 1 FROM data_values dv WHERE';
		$where = '';		
		foreach($conditions as $tmp)
		{
			$where .= ' '.$tmp.' ';
		}
		$tmpSql .=$where.' LIMIT 0';
		if(!mysql_query($tmpSql))
		return false;
	}
	
	if(isset($g_conds) && is_array($g_conds) && count($g_conds)>0)
	{
		$tmpSql = 'SELECT 1 FROM clinical_study WHERE';
		$where = '';
		foreach($g_conds as $tmp)
		{
			$where .= ' '.$tmp.' ';
		}
		$tmpSql .=$where.' LIMIT 0';
		if(!mysql_query($tmpSql))
		return false;
	}	
	
	if(isset($strong_exclusions) && is_array($strong_exclusions) && count($strong_exclusions)>0)
	{
		$tmpSql = 'SELECT 1 FROM data_values dv WHERE';
		$where = '';		
		foreach($strong_exclusions as $tmp)
		{
			$where .= ' '.$tmp.' ';
		}
		$tmpSql .=$where.' LIMIT 0';
		if(!mysql_query($tmpSql))
		return false;
	}	

	//if all the above condtions are a go.
	return true;
}

?>