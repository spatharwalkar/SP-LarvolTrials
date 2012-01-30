<?php
require_once 'db.php';
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
		if(json_last_error()<>0)	return 'Invalid Json';
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

?>