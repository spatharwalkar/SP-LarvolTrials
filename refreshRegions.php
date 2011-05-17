<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

$larvolId = ($_GET['id'])?$_GET['id']:null;
$action = ($larvolId)?'search':'require';

$param = new SearchParam();
$param->field = 'larvol_id';
$param->action = $action;
$param->value = $larvolId;
$param->strong = 1;
$time = time('now');

$prm = array($param);

$fieldnames = array('location_country');
foreach($fieldnames as $name)
{ 
	
	$param = new SearchParam();
	$param->field = fieldNameToPaddedId($name);
	$param->action = 'require';
	$param->value = '';
	$param->strong = 1;
	$prm[] = $param;
	$list[] = fieldNameToPaddedId($name);

}	

$res = search($prm,$list,NULL,NULL);

applyRegions($res);

function applyRegions($arr)
{
	global $db;
	if(count($arr)>0)
	{
		mysql_query('BEGIN') or die('Cannot begin transaction');
		echo 'Starting transaction.<br/>';
	}
	else 
	{
		die('No records to update');
	}	
	
	$flag = 0;
	$flag1 = 0;
	$regionArr = regionMapping();
	foreach($arr as $res)
	{	
		$larvolId = $res['larvol_id'];
		$locationCountry = $res['NCT/location_country'];
		if(is_array($locationCountry))
		$locationCountry = $locationCountry[0];
		foreach($regionArr as $countryName=>$code)
		{
			if($countryName == $locationCountry)
			{
				$flag1 = 1;
				break;
			}
		}
		if($flag1 != 1)
		$code = 'other';
		
		$flag1 = 0;
		
		$query  = "update clinical_study set region='".$code."' where larvol_id=$larvolId";
		if(mysql_query($query))
		{
			$flag=1;
		}
		else
		{
			die('Cannot update inactive_date. '.$query);
		}		
	}
	if($flag == 1)
	{
		mysql_query('COMMIT') or die('Cannot commit transaction');
		echo 'Transaction commited successfully.';
	}	
}

//Get field IDs for names
// - for the $list argument, search() takes IDs prepended with a padding character (stripped by highPass())
// - didn't find the alternative, so I wrote this
function fieldNameToPaddedId($name)
{
	$query = 'SELECT data_fields.id AS data_field_id FROM '
		. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="NCT" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) tex('Bad SQL query getting field ID of ' . $name);
	$res = mysql_fetch_assoc($res);
	if($res === false) tex('NCT schema not found!');
	return '_' . $res['data_field_id'];
}

function regionMapping()
{
	static $row = null;
	static $auNz = null;
	static $eu = null;
	static $ca = null;
	static $jp = null;
	static $us = null;
	static $uk = null;
	
	if($row == null)
	$row = file('institutions/row.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($auNz == null)
	$auNz = file('institutions/au_nz.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($eu == null)
	$eu = file('institutions/eu.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($ca == null)
	$ca = file('institutions/ca.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	
	if($jp == null)
	$jp = file('institutions/jp.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);	
	
	if($us == null)
	$us = file('institutions/us.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);	
	
	if($uk == null)	
	$uk = file('institutions/uk.txt',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);	
	
	$out = array();
	
	foreach($row as $tmp)
	{
		$out[$tmp] = 'ROW';
	}
	
	foreach($auNz as $tmp)
	{
		$out[$tmp] = 'AU/NZ';
	}
	
	foreach($eu as $tmp)
	{
		$out[$tmp] = 'EU';
	}

	foreach($ca as $tmp)
	{
		$out[$tmp] = 'CA';
	}


	foreach($jp as $tmp)
	{
		$out[$tmp] = 'JP';
	}

	foreach($us as $tmp)
	{
		$out[$tmp] = 'US';
	}

	foreach($uk as $tmp)
	{
		$out[$tmp] = 'UK';
	}		
	
	return $out;
	
}

