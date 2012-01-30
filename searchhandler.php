<?php

require_once('db.php');

switch($_REQUEST['op']){

	case 'load':
		$dataS = listSearchProc();
		//var_dump($dataS);
		echo($dataS);
		//echo("hello world");
		break;
	case 'getsearchdata':
		getSearchData();
		break;
	case 'saveexists':
		updateSearch();
		echo $_REQUEST['reportname']." saved....";
		break;
	case 'savenew':
		echo(insertSearch());
		break;
	case 'list':
		echo(listSearchForm());
		break;
	case 'runQuery':
		runQuery();
		break;
	case 'testQuery':
		echo(testQuery());
		break;
	case 'gridList':
		echo(listSearchesInGrid());
		break;

}

function listSearchesInGrid()
{
	$page = $_GET['page']; // get the requested page
	$limit = $_GET['rows']; // get how many rows we want to have into the grid
	$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
	$sord = $_GET['sord']; // get the direction
	if(!$sidx) $sidx =1;

	$result = mysql_query("SELECT COUNT(*) AS count FROM saved_searches");
	$row = mysql_fetch_array($result,MYSQL_ASSOC);
	$count = $row['count'];

	if( $count >0 ) {
		$total_pages = ceil($count/$limit);
	} else {
		$total_pages = 0;
	}
	if ($page > $total_pages) $page=$total_pages;
	$start = $limit*$page - $limit; // do not put $limit*($page - 1)
	$SQL = "SELECT a.id, a.name, 'description' as description FROM saved_searches a ORDER BY $sidx $sord LIMIT $start , $limit";
	$result = mysql_query( $SQL ) or die("Couldn t execute query.".mysql_error());

	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	$i=0;
	while($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$responce->rows[$i]['id']=$row[id];
		$responce->rows[$i]['cell']=array($row[id],$row[name],$row[description]);
		$i++;
	}
	echo json_encode($responce);

}

//processes postdata for saving new searches
function insertSearch()
{
	$querytosave=stripslashes($_REQUEST['querytosave']);
	global $db;
	if(!isset($_REQUEST['reportname']) || !strlen($_REQUEST['reportname'])) return;
	$name = mysql_real_escape_string($_REQUEST['reportname']);
	$user = $db->user->id;
	$searchdata = $querytosave;
	$uclause = ($db->user->userlevel != 'user' && $user === NULL) ? 'NULL' : $user;
	$query = 'INSERT INTO saved_searches SET user=' . $uclause . ',name="' . $name . '",searchdata="'
	. base64_encode(serialize($searchdata)) . '"';
	mysql_query($query) or die('Bad SQL query adding saved search');
	$miid = mysql_insert_id();
	mysql_query('COMMIT') or die("Couldn't commit SQL query");
	return $miid;
}

//processes postdata for saving new searches
function updateSearch()
{
	$querytosave=stripslashes($_REQUEST['querytosave']);
	global $db;
	if(!isset($_REQUEST['reportname']) || !strlen($_REQUEST['reportname'])) return;
	$name = mysql_real_escape_string($_REQUEST['reportname']);
	$searchId = mysql_real_escape_string($_REQUEST['searchId']);
	//$user = isset($_POST['saveglobal']) ? NULL : $db->user->id;
	$user = $db->user->id;
	$searchdata = $querytosave;
	$uclause = ($db->user->userlevel != 'user' && $user === NULL) ? 'NULL' : $user;
	$query = 'UPDATE saved_searches SET user=' . $uclause . ',name="' . $name . '",searchdata="'
	. base64_encode(serialize($searchdata)) . '" where id=' . $searchId;
	mysql_query($query) or die('Bad SQL query adding saved search');
	//$miid = mysql_insert_id();
	mysql_query('COMMIT') or die("Couldn't commit SQL query");

}

function getSearchData()
{
	if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id']))	return;
	//load search from Saved Search

	$ssid = mysql_real_escape_string($_REQUEST['id']);
	//$query = 'SELECT * FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL)' . ' LIMIT 1';
	$query = 'SELECT * FROM saved_searches WHERE id=' . $ssid . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting searchdata');
	$row = mysql_fetch_array($res);
	//if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.

	//$show_value = 'showSearchData("' . $_GET['id'] . '");';
	//echo($show_value);
	$data = unserialize(base64_decode($row['searchdata']));
	$res_ret->searchdata=$data;
	$res_ret->name= $row['name'];
	$res_ret->id= $row['id'];
	echo json_encode($res_ret);


}

//returns HTML for saved searches controller
//not using any more since we have a search grid separately
function listSearchForm()
{
	global $db;

	$out = "<ul class='treeview' id='treeview_9000'>";
	$out .= "<li class='list'>" . "Load saved search";
	//$out .= "</li>";
	$out .= '<ul style="display:block;">';
	$query = 'SELECT id,name,user FROM saved_searches WHERE user=' . $db->user->id . ' OR user IS NULL ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query getting saved search list');
	while($ss = mysql_fetch_assoc($res))
	{
		$out .= "<li class='item'> <a href='#' onclick='showSearchData(\"" . $ss['id'] . "\",\"" . htmlspecialchars($ss['name']) . "\");return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
		//$out .= "<li class='item'> <a href='javascript:void();return false;'>" . htmlspecialchars($ss['name']) . "</a></li>";
		//$out .= "<li class='item'>"  . htmlspecialchars($ss['name']) . "</li>";
		//$out .= "<li>"  . htmlspecialchars($ss['name']) . "</li>";

	}
	$out .= "</ul>";
	$out .= "</li>";
	//$out .= "</ul>";
	return $out;
}

function listSearchProc()
{
	global $db;

	$ssid = mysql_real_escape_string($_REQUEST['searchId']);
	$query = 'SELECT searchdata FROM saved_searches WHERE id=' . $ssid . ' AND (user=' . $db->user->id . ' or user IS NULL)'
	. ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting searchdata');
	$row = mysql_fetch_array($res);
	if($row === false) return;	//In this case, either the ID is invalid or it doesn't belong to the current user.
	unserialize(base64_decode($row['searchdata']));
	return $row;
}

function testQuery()
{
	$jsonData=$_REQUEST['data'];
	$actual_query= "";
	try {
		$actual_query= buildQuery($jsonData, false);
	}
	catch(Exception $e)
	{
		return $e->getMessage();
	}

	$result = mysql_query($actual_query);
	if (mysql_errno()) {
		$error = "MySQL error ".mysql_errno().": ".mysql_error()."\n<br>When executing:<br>\n$actual_query\n<br>";
		return $error;
	}
	else
	{
		return "Great! SQL Query has no syntax issues" ."\n<br>When executing:<br>\n$actual_query\n<br>";
	}

}

function runQuery()
{
	global $db;
	$jsonData=$_REQUEST['data'];
	$actual_query= buildQuery($jsonData, false);
	$count_query =  buildQuery($jsonData, true);
	$page = $_GET['page']; // get the requested page
	$limit = $_GET['rows']; // get how many rows we want to have into the grid
	$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
	$sord = $_GET['sord']; // get the direction
	if(!$sidx) $sidx =1;

	$result = mysql_query($count_query);
	
	//	pr($result);
	
	$row = mysql_fetch_array($result,MYSQL_ASSOC);
	$count = $row['count'];
	if( $count >0 ) {
		$total_pages = ceil($count/$limit);
	} else {
		$total_pages = 0;
	}
	if ($page > $total_pages) $page=$total_pages;
	$start = $limit*$page - $limit; // do not put $limit*($page - 1)
	if ($start<0) $start = 0;
	//$SQL = "$actual_query ORDER BY $sidx $sord LIMIT $start , $limit";
	$SQL = "$actual_query LIMIT $start , $limit";
	$result = mysql_query( $SQL ) or die("Couldn't execute query.".mysql_error());
	if(!isset($responce)) $responce = new stdClass();

	$responce->page = $page;
	$responce->total = $total_pages;
	$responce->records = $count;
	$i=0;
	while($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
		$responce->rows[$i]=$row;
		$i++;
	}
	
//	pr($responce->rows);
	
	foreach ($responce->rows as $fieldname => $value)
	{
		$x=0;
		foreach ($value  as $field => $val)
		{

			if($x==0)
			{
				$x=1;
				
				$responce->rows[$fieldname]['view'][] = '<a href="edit_trials.php?larvol_id='.$responce->rows[$fieldname]['larvol_id'].'"><img src="images/view.png" border="0"></a>';
				if($db->loggedIn() and ($db->user->userlevel=='admin'||$db->user->userlevel=='root'))
					{
						$responce->rows[$fieldname]['edit'][] = '<a href="edit_trials.php?larvol_id='.$responce->rows[$fieldname]['larvol_id'].'&mode=edit"><img src="images/jedit.png" border="0"></a>';				
					}
			}
		}
		//	
	}
	
//	pr($responce->rows);

	$ret_val = json_encode($responce);
	echo($ret_val);


}

function buildQuery($data, $isCount=false)
{
	$actual_query = "";
	try {
		$jsonData=$data;
		$filterData = json_decode($jsonData, true, 10);
		if(is_array($filterData))
		array_walk_recursive($filterData, 'searchHandlerBackTicker','columnname');
		if(is_array($filterData))
		array_walk_recursive($filterData['columndata'], 'searchHandlerBackTicker','columnas');
		$alias= " dt"; //data_trial table alias

		$where_datas = $filterData["wheredata"];
		$select_columns=$filterData["columndata"];
		$override_vals = trim($filterData["override"]);
		$sort_datas = $filterData["sortdata"];
		$isOverride = !empty($override_vals);

		$select_str = getSelectString($select_columns, $alias);
		$where_str = getWhereString($where_datas, $alias);
		$sort_str = getSortString($sort_datas, $alias);


		if($isOverride)
		{
			if($isCount)
			{
		  $actual_query .= "SELECT((";
			}
			else
			{
		  $actual_query .= "(";
			}
		}

		$actual_query .= "SELECT ";

		if($isCount)
		{
	  $actual_query .= 	"COUNT(*) AS count";
		}
		else
		{
			$actual_query .= 	$select_str;
		}

		$actual_query .= " FROM data_trials " . $alias;

		if(strlen(trim($where_str)) != 0)
		{
			$actual_query .= " WHERE " .$where_str;
		}

		if((!$isCount) && (strlen(trim($sort_str)) != 0))//Sort
		{
			$actual_query .= " ORDER BY " . $sort_str;
		}

		if($isOverride)//override string present
		{

	  $override_str = getNCTOverrideString($override_vals, $alias, $select_str, $isCount);

	  if($isCount)
	  {
	  	$actual_query .=  ") + (" . $override_str . ")) AS count";
	  }
	  else
	  {
	  	$actual_query .= ") UNION (" . $override_str . ")";
	  }

		}
	}
	catch(Exception $e)
	{
		throw $e;
	}

	return $actual_query;
}

function getNCTOverrideString($data, $alias, $select_str, $isCount)
{
	$override_str = $data;
	$return = " SELECT ";
	if($isCount)
	{
		$return .= "COUNT(*) AS count ";
	}
	else
	{
		$return .= $select_str;
	}
	$return .=  " FROM data_trials " . $alias . " WHERE "
	. $alias . ".larvol_id IN (" .  $override_str . ")";
	return $return;


}

function getSelectString($data, $alias)
{
	$query = $alias . "." . "larvol_id, " . $alias . "." . "source_id, ";
	$select_columns = $data;
	if(!empty($select_columns))
	{
		foreach($select_columns as $selectcolumn)
		{
			$query .= $alias . "." . $selectcolumn["columnname"] . " AS " . $selectcolumn["columnas"] . ", ";
		}
	}
	$query = substr($query, 0, -2); //strip last comma
	return $query;

}

function getSortString($data, $alias)
{
	$query = '';
	$sort_columns = $data;
	if(empty($sort_columns))
	{
		return $query;
	}
	foreach($sort_columns as $sort_column)
	{
		$sort_as = $sort_column["columnas"];
		$sorttype = $sort_as=="Ascending"? "asc" : "desc";
		$query .= $alias . "." . $sort_column["columnname"] . " "  . $sorttype . ", ";
	}
	$query = substr($query, 0, -2); //strip last comma
	return $query;

}


function getWhereString($data, $alias)
{
	$wheredatas = $data;
    if(empty($wheredatas))
	{
	   return '';
	}
	$wheres = array();
	$wcount = 0;
	$prevchain = ' ';
	try {

		foreach($wheredatas as $where_data)
		{
			$op_name = $where_data["opname"];
			$column_name = $where_data["columnname"];
			$column_value = $where_data["columnvalue"];
			$chain_name = $where_data["chainname"];
			$op_string = getOperator($op_name, $column_name, $column_value);
			$wstr = " " . $prevchain . " " . $op_string;
			$wstr = str_replace('%f', $alias . "." . $column_name,$wstr);
			$pos = strpos($op_string,'%s1');

			if($pos === false) {
				$wstr = str_replace('%s', $column_value, $wstr);
			}
			else {
				$xx = split('and;endl', $column_value);//and;endl
				$wstr = str_replace('%s1', $xx[0],$wstr);
				$wstr = str_replace('%s2', $xx[1],$wstr);
			}
			$prevchain = $chain_name;
			$wheres[$wcount++] = $wstr;
		}
		$wherestr = implode(' ', $wheres);
		$pos = strpos($prevchain,'.');
		if($pos === false)
		{
			//do nothing
		}
		else
		{
			$wherestr .= str_replace('.', '', $prevchain);//if . is present remove it and empty
		}
		//                if($pos == true)
		//                    $wherestr .= $prevchain;
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $wherestr;


}

function getOperator($opname, $column_name, $column_value)
{
	$val = '';
	try {
		switch($opname){
			case 'EqualTo':
				$val = "%f='%s'";
				break;
			case 'NotEqualTo':
				$val= "%f!='%s'";
				break;
			case 'StartsWith':
				$val ="%f LIKE '%s%'";
				break;
			case 'NotStartsWith':
				$val ="NOT(%f LIKE '%s%')";
				break;
			case 'Contains':
				$val ="%f LIKE '%%s%'";
				break;
			case 'NotContains':
				$val ="NOT(%f LIKE '%%s%')";
				break;
			case 'BiggerThan':
				$val ="%f>'%s'";
				break;
			case 'BiggerOrEqualTo':
				$val ="%f>='%s'";
				break;
			case 'SmallerThan':
				$val ="%f<'%s'";
				break;
			case 'SmallerOrEqualTo':
				$val ="%f<='%s'";
				break;
			case 'InBetween':
				$val ="%f BETWEEN '%s1' AND '%s2'";
				break;
			case 'NotInBetween':
				$val ="not(%f BETWEEN '%s1' AND '%s2')";
				break;

			case 'IsIn':
				$val ="%f IN (%s)";
				break;
			case 'IsNotIn':
				$val ="NOT(%f IN (%s))";
				break;
			case 'IsNull':
				$val ="%f IS NULL";
				break;
			case 'NotNull':
				$val ="%f IS NOT NULL";
				break;
			case 'Regex':
				$val = textEqual($column_name, $column_value);
				break;
			case 'NotRegex':
				$val = 'NOT (' . textEqual($column_name, $column_value) . ')';
				break;

		}
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $val;
}


//Outputs SQL expression to match text -- auto-detects use of regex and selects comparison method automatically
function textEqual($field,$value)
{
	//	$pcre = strlen($value) > 1
	//	&& $value[0] == '/'
	//	&& ($value[strlen($value)-1] == '/' || ($value[strlen($value)-2] == '/' && strlen($value) > 2));
	//	if($pcre)
	{
		//alexvp added exception
		$result=validateMaskPCRE($value);
		if(!$result)
		throw new Exception("Bad regex: $field = $value", 6);
		return 'PREG_RLIKE("' . '%s' . '",' . '%f' . ')';
	}
	//	else{
	//		return '%f' . '="' . '%s' . '"';
	//	}
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
		global $logger;
		$logger->fatal($log);
		return softDie($log);
	}

	list($check)=mysql_fetch_row($res);
	return ($check==1); // pcre ok?
}

?>