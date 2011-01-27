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
function search($params=array(),$list=array('overall_status','brief_title'),$page=1,$time=NULL,$override=array())
{
	if($time !== NULL) $time = '"' . date('Y-m-d H:i:s',$time) . '"';
	$optimizer_hints = ($_GET['priority'] == 'high') ? 'HIGH_PRIORITY ' : '';
	
	/*
	New QUERY strategy:
	Split the conditions into separate queries, retrieve all IDs, and merge together
		SELECT clinical_study.larvol_id
		FROM
		(data_values AS dv 
		 	LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id
			LEFT JOIN clinical_study ON i.larvol_id=clinical_study.larvol_id
		)
		WHERE `field`=13 AND val_enum IN(6,7) AND superceded IS NULL;
	...do this for each condition, merge using "in("
	*/
	foreach($params as $key => $value) $params[$key] = clone $value;

	global $db;
	global $SEARCH_ERR;
	$normal_conditions = array();
	$global_conditions = array();
	$normal_sort = array();
	$global_sort = array();
	$strong_exclusions = array();
	try{
		foreach($params as $param)
		{
			$global = (is_array($param->field) ? $param->field[0][0] : $param->field[0]) != '_';
			$type = $db->types[(is_array($param->field) ? $param->field[0] : $param->field)];
			switch($param->action)
			{
				//all that needs to be stored for sort args is the field number and type
				//store a separate sortargs for global fields that just stores the field name
				case 'ascending':
				if($global)
				{
					$global_sort[] = '`clinical_study`.' . $param->field;
				}else{
					$normal_sort[substr($param->field,1)] = $type;
				}
				break;
				
				case 'descending':
				if($global)
				{
					$global_sort[] = '`clinical_study`.' . $param->field . ' DESC';
				}else{
					$normal_sort[substr($param->field,1)] = $type . ' DESC';
				}
				break;
				
				case 'require':
				case 'search':
				if($global)
				{
					$param->field = '`clinical_study`.' . $param->field;
					if($param->action == 'require')
					{
						$global_conditions[] = $param->field . ' IS NOT NULL';
					}else{
						switch($type)
						{
							//rangeable
							case 'date':
							case 'datetime':
							case 'int':
							$ORd = explode(' OR ', $param->value);
							foreach($ORd as $key => $term)
							{
								if(strpos($term, ' TO ') !== false)
								{
									$range = explode(' TO ', $term);
									$ORd[$key] = '(' . $param->field . ' BETWEEN ' . $range[0] . ' AND ' . $range[1] . ')';
								}else{
									$ORd[$key] = '(' . $param->field . '=' . $term . ')';
								}
							}
							$cond = implode(' OR ', $ORd);
							$global_conditions[] = ($param->negate ? 'NOT ' : '') . '(' . $cond . ')';
							break;
							//normal
							case 'tinyint':
							case 'enum':
							$eq = is_array($param->value) ? (' IN("' . implode('","',$param->value) . '")') : ('=' . $param->value);
							$global_conditions[] = $param->field . ($param->negate ? ' NOT' : '') . $eq;
							break;
							//regexable
							case 'varchar':
							case 'text':
							if(strlen($param->value)) $global_conditions[] = textEqual($param->field,$param->value);
							if($param->negate !== false && strlen($param->negate))
								$global_conditions[] = 'NOT ' . textEqual($param->field,$param->negate);
						}
					}
				}else{
					$field;
					if(is_array($param->field))	//take the underscore off the field "name" to get the ID
					{
						$field = 'dv.`field` IN(' . implode(',', array_map('highPass', $param->field)) . ')';
					}else{
						$field = 'dv.`field`=' . substr($param->field,1);
					}
					if($param->action == 'require')
					{
						$normal_conditions[] = $field . ' AND dv.val_' . $type . ' IS NOT NULL';
					}else{
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
									$ORd[$key] = '(dv.val_' . $type . ' BETWEEN ' . $range[0] . ' AND ' . $range[1] . ')';
								}else{
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
								$normal_conditions[] = $cond;
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
								$normal_conditions[] = $cond;
							}
							break;
							//enum is special
							case 'enum':
							$enumq = is_array($param->value) ? (' IN(' . implode(',',$param->value) . ')') : ('=' . $param->value);
							$cond = $field . ' AND dv.val_enum' . $enumq;
							if($param->negate && $param->strong)
							{
								$strong_exclusions[] = $cond;
							}else{
								if($param->negate) $cond = $field . ' AND NOT (dv.val_enum' . $enumq . ')';
								$normal_conditions[] = $cond;
							}
							break;
							//regexable
							case 'varchar':
							case 'text':
							if(!is_array($param->field))	//normal single-field param
							{
								if(strlen($param->value))
									$normal_conditions[] = $field . ' AND ' . textEqual('dv.val_' . $type,$param->value);
								if($param->negate !== false && strlen($param->negate))
								{
									if($param->strong)
									{
										$strong_exclusions[] = $field . ' AND ' . textEqual('dv.val_' . $type,$param->negate);
									}else{
										$normal_conditions[] = $field . ' AND NOT ' . textEqual('dv.val_' . $type,$param->negate);
									}
								}
							}else{	//Merge varchar and text multifields
								if(strlen($param->value))
								{
									$normal_conditions[] = $field . ' AND (' . textEqual('dv.val_text',$param->value) . ' OR '
														. textEqual('dv.val_varchar',$param->value) . ')';
								}
								if($param->negate !== false && strlen($param->negate))
								{
									if($param->strong)
									{
										$strong_exclusions[] = $field . ' AND (' . textEqual('dv.val_text',$param->value) . ' OR '
														. textEqual('dv.val_varchar',$param->value) . ')';
									}else{
										$normal_conditions[] = $field . ' AND NOT (' . textEqual('dv.val_text',$param->value) . ' OR '
														. textEqual('dv.val_varchar',$param->value) . ')';
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
	
	//execute the queries and gather results
	$resid_set = array();
	$bigquery = array();
	foreach($normal_conditions as $cond)
	{
		$query = 'SELECT ' . $optimizer_hints . 'DISTINCT clinical_study.larvol_id AS "larvol_id" FROM (data_values AS dv '
			. 'LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id '
			. 'LEFT JOIN clinical_study ON i.larvol_id=clinical_study.larvol_id) WHERE ' . $cond . ' AND ';
		if($time === NULL)
		{
			$query .= 'dv.superceded IS NULL ';
		}else{
			$query .= 'dv.added<' . $time . ' AND (dv.superceded>' . $time . ' OR dv.superceded IS NULL) ';
		}
		$bigquery[] = $query;
	}
	foreach($global_conditions as $cond)
	{
		$query = 'SELECT larvol_id FROM clinical_study WHERE ' . $cond;
		$bigquery[] = $query;
	}
	if(empty($normal_conditions) && empty($global_conditions))
	{
		$bigquery = 'SELECT larvol_id FROM clinical_study ';
	}

	if(is_array($bigquery))
	{
		$cap = '';
		for($i=1; $i < count($bigquery); ++$i) $cap .= ')';
		$bigquery = implode(' AND clinical_study.larvol_id IN(',$bigquery) . $cap;
	}
	foreach($strong_exclusions as $key => $cond)
	{
		$query = 'SELECT ' . $optimizer_hints . 'DISTINCT clinical_study.larvol_id AS "larvol_id" FROM (data_values AS dv '
			. 'LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id '
			. 'LEFT JOIN clinical_study ON i.larvol_id=clinical_study.larvol_id) WHERE ' . $cond . ' AND ';
		if($time === NULL)
		{
			$query .= 'dv.superceded IS NULL ';
		}else{
			$query .= 'dv.added<' . $time . ' AND (dv.superceded>' . $time . ' OR dv.superceded IS NULL) ';
		}
		$strong_exclusions[$key] = $query;
	}
	//var_dump($bigquery);exit;
	if(!empty($strong_exclusions))
	{
		//if there are ONLY strong exclusions, there won't be a WHERE clause yet at this point
		$prefix = (empty($normal_conditions) && empty($global_conditions)) ? ' WHERE ' : ' AND ';
		$bigquery .=  $prefix . 'clinical_study.larvol_id NOT IN(' . implode(' UNION ', $strong_exclusions) . ')';
	}
	if(!empty($override))
	{
		mysql_query('DROP TABLE IF EXISTS ulid');
		mysql_query('CREATE TEMPORARY TABLE ulid (larvol_id int NOT NULL)');
		mysql_query('INSERT INTO ulid VALUES ' . implode(',', parenthesize($override)));
		$bigquery .= ' UNION SELECT larvol_id FROM ulid';
	}
	
	if($list === NULL)	//option to return total number of records instead of full search results
	{
		$bigquery = 'SELECT COUNT(larvol_id) AS "ctotal" FROM (' . $bigquery . ') AS resultset';
		$res = mysql_query($bigquery);
		if($res === false) return softDie('Bad SQL query on search: ' . $bigquery . "<br />\n" . mysql_error());
		$row = mysql_fetch_assoc($res) or die('Total not found.');
		return $row['ctotal'];
	}
	//var_dump($bigquery);exit;
	if($list === false)	//option to return the SQL query
	{
		return $bigquery;
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
	$sorting = !empty($normal_sort) || !empty($global_sort);
	if(!$sorting)	$bigquery .= $limit;	//Can only limit if we're not sorting.
	$res = mysql_query($bigquery);
	if($res === false) return softDie('Bad SQL query on search: ' . $bigquery . "<br />\n" . mysql_error());
	while($row = mysql_fetch_assoc($res)) $resid_set[] = $row['larvol_id'];
	if($sorting)
	{
		$timereq = '';
		if($time === NULL)
		{
			$timereq = 'dv.superceded IS NULL ';
		}else{
			$timereq = 'dv.added<' . $time . ' AND (dv.superceded>' . $time . ' OR dv.superceded IS NULL) ';
		}
		foreach($normal_sort as $field => $type)
		{
			$query = 'SELECT clinical_study.larvol_id FROM '
				. 'data_values AS dv LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id '
				. 'LEFT JOIN clinical_study ON i.larvol_id=clinical_study.larvol_id '
				. 'WHERE ' . $timereq . 'AND `field` = 56 AND clinical_study.larvol_id IN('
				. implode(',', $resid_set) . ') ORDER BY dv.val_' . $type . ' ' . $limit;
			$resid_set = array();
			$res = mysql_query($query);
			if($res === false) return softDie('Bad SQL query on sort: ' . $query . "<br />\n" . mysql_error());
			while($row = mysql_fetch_assoc($res)) $resid_set[] = $row['larvol_id'];
			break;
		}
		if(empty($normal_sort))
		foreach($global_sort as $field)
		{
			$query = 'SELECT larvol_id FROM clinical_study WHERE larvol_id IN(' . implode(',', $resid_set)
						. ') ORDER BY ' . $field . ' ' . $limit;
			$resid_set = array();
			$res = mysql_query($query);
			if($res === false) return softDie('Bad SQL query on sort: ' . $query . "<br />\n" . mysql_error());
			while($row = mysql_fetch_assoc($res)) $resid_set[] = $row['larvol_id'];
			break;
		}
	}
	//At this point $query contains all the conditions and gets us the right ID list with limits for result page
	$recordsData = getRecords($resid_set,$list,$time);
	$retdata = array();
	foreach($resid_set as $id) $retdata[$id] = $recordsData[$id];
	return $retdata;
}

function getField($params, $field) {
	$id = '_'.getFieldId('NCT', $field);
	foreach ($params as $param) {
		$fields = is_array($param->field) ? $param->field :
			array($param->field);
		foreach ($fields as $field)
			if ($field == $id)
				return $param;
	}
	return null;
}

function getBackboneAgent($params) {
	return getField($params, "intervention_name");
}

function applyBackboneAgent($ids, $term) {
	if (count($ids) == 0)
		return array();
	$ids = implode(",", $ids);
	$interventionId = getFieldId("NCT", "intervention_name");
	$rs = mysql_query("SELECT DISTINCT i.larvol_id AS id
	FROM data_cats_in_study AS i
	INNER JOIN data_values AS dv ON i.id = dv.studycat AND
		dv.field = ".$interventionId."
	WHERE i.larvol_id IN (".$ids.") AND dv.superceded IS NULL AND
		dv.val_varchar <> '".mysql_real_escape_string($term)."'");
	$result = array();
	while ($row = mysql_fetch_assoc($rs))
		$result[] = $row["id"];
	return $result;
}

function getBomb($ids) {
	if (count($ids) == 0)
		return "";
	$overallStatusId = getFieldId("NCT", "overall_status");
	$phaseId = getFieldId("NCT","phase");
	$terminatedId = getEnumvalId($overallStatusId, "Terminated");
	$suspendedId = getEnumvalId($overallStatusId, "Suspended");
	$bombStatuses = getEnumvalId($overallStatusId, "Active, not recruiting").",".
		getEnumvalId($overallStatusId, "Not yet recruiting").",".
		getEnumvalId($overallStatusId, "Recruiting").",".
		getEnumvalId($overallStatusId, "Enrolling by invitation");
	$ids = implode(",", $ids);
	$past = "'".date("Y-m-d H:i:s", time() - (int)(0.1*1.5*24*3600))."'";
	$rs = mysql_query("SELECT i.larvol_id AS id FROM data_values AS dv
	LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id
	WHERE dv.field = ".$overallStatusId." AND
		dv.val_enum IN (".$terminatedId.",".$suspendedId.") AND
		i.larvol_id IN (".$ids.") AND dv.added < ".$past." AND
		dv.superceded IS NULL");
	$trials = array();
	while ($row = mysql_fetch_assoc($rs))
		$trials[] = $row["id"];
	if (count($trials) == 0)
		return "";
	$trials = implode(",", $trials);
	$rs = mysql_query("SELECT MAX(val_enum) AS phase FROM data_values AS dv
	LEFT JOIN data_cats_in_study AS i ON dv.studycat=i.id
	WHERE dv.field = ".$phaseId." AND dv.superceded IS NULL AND
		i.larvol_id IN (".$ids.") AND i.larvol_id NOT IN (".$trials.")");
	$row = mysql_fetch_assoc($rs);
	$phase = $row["phase"];
	$rs = mysql_query("SELECT 1 FROM data_values AS dv1
	INNER JOIN data_cats_in_study AS i ON dv1.studycat=i.id
	INNER JOIN data_values AS dv2 ON dv2.studycat=i.id
	WHERE dv1.field = ".$phaseId." AND dv1.superceded IS NULL AND
		dv1.val_enum = ".$phase." AND i.larvol_id IN (".$ids.") AND
		i.larvol_id NOT IN (".$trials.") AND
		dv2.field = ".$overallStatusId." AND dv2.superceded IS NULL AND
		dv2.val_enum IN (".$bombStatuses.")");
	if (mysql_fetch_assoc($rs))
		return "sb";
	return "lb";
}
//Helper for above and below. Convert to anonymous function when gentoo gets php 5.3 support
function highPass($v){return substr($v,1);}

//return an array of study maps corresponding to $ids, with only $fields populated
function getRecords($ids,$fields,$time)
{
	//var_dump($fields);@flush();exit;
	global $db;
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
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting global fields for result list<br />'.$query);
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
		//var_dump($query);@flush();exit;
		$res = mysql_query($query);
		if($res === false) return softDie('Bad SQL query getting data for result set<br />'.$query.'<br />'.mysql_error());
		while($row = mysql_fetch_assoc($res))
		{	
			$id = $row['larvol_id'];
			$place = $row['category'] . '/' . $row['name'];
			$val = $row[$row['type']];
			if(isset($result[$id][$place]))
			{
				if(is_array($result[$id][$place]))
				{
					$result[$id][$place][$id] = $val;
				}else{
					$result[$id][$place] = array($result[$id][$place], $val);
				}
			}else{
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
	$query = 'SELECT data_fields.id AS "id" '
		. 'FROM data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="' . $category . '" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting field ID of ' . $category . '/' . $name);
	$res = mysql_fetch_assoc($res);
	if($res === false) return softDie('Field ' . $name . ' not found in category ' . $category . '!');
	return $res['id'];
}

//gets the ID of an enum value (from data_enumvals) given the field ID and string value
function getEnumvalId($fieldId,$value)
{
	if($value === NULL || $value === 'NULL') return 'NULL';
	$query = 'SELECT id FROM data_enumvals WHERE `field`=' . $fieldId . ' AND `value`="' . $value . '" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting ID of enumval ' . $value . ' in field ' . $fieldId);
	$res = mysql_fetch_assoc($res);
	if($res === false) return softDie('Enumval ' . $value . ' invalid for field ' . $fieldId . '!');
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
	$id = (int)$id;
	if(!is_numeric($id)) return false;
	$field = getFieldId('NCT','nct_id');
	if($field === false) return false;
	$query = 'SELECT larvol_id FROM data_values LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id'
			. ' WHERE superceded IS NULL AND `field`=' . $field . ' AND val_int=' . ((int)unpadnct($id)) . ' LIMIT 1';
	$res = mysql_query($query);
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

//alexvp added function 
function validateMaskPCRE($s)
{
    $s=addslashes($s);
	$query = "SELECT PREG_CHECK('$s')";

	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query on search: ' . $query . "<br />\n" . mysql_error());

	list($check)=mysql_fetch_row($res);
	return ($check==1); // pcre ok?
}

function validateInputPCRE($post)
{
    global $db;

    $badFields=array();

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
			$res = mysql_query($query) or die('Bad SQL query getting field name for '.$CFid);
			list($fieldName)=mysql_fetch_row($res);
			$fieldName=str_replace("_"," ",$fieldName);
			$badFields[$fieldName]=$mask;
		}
		if($pcre2 && !validateMaskPCRE($mask2))
		{
			//need in field name !
			$CFid = substr($field,1);
			$query = 'SELECT name FROM data_fields WHERE id=' . $CFid;
			$res = mysql_query($query) or die('Bad SQL query getting field name for '.$CFid);
			list($fieldName)=mysql_fetch_row($res);
			$fieldName=str_replace("_"," ",$fieldName);
			$badFields[$fieldName]=$mask2;
		}
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
	    die();
	}
}
//alexvp end
?>