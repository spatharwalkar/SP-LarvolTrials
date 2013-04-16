<?php
ob_start();
require_once 'db.php';
$search = mysql_real_escape_string($_GET['query']);
$table = mysql_real_escape_string($_GET['table']);
$field = mysql_real_escape_string($_GET['field']);
$hint = mysql_real_escape_string($_GET['hint']);
$c_lid = mysql_real_escape_string($_GET['c_lid']);
$mesh_display=mysql_real_escape_string($_GET['mesh']);
//filter input
$autoSuggestTables = array('areas','upm','products','data_trials', 'redtags', 'trialzilla', 'masterhm','diseases');
if(!in_array($table,$autoSuggestTables))die;

if($table=='upm' && $field=='product')
{
	$query = "select p.id,p.name from products p where p.name like '%$search%' and (p.is_active=1 or p.is_active is null) order by name asc";
}
elseif($table=='upm' && $field=='area')
{
	$query = "select a.id,a.name from areas a where a.name like '%$search%' and a.coverage_area=1 order by name asc";
}
elseif($table=='upm' && $field=='redtag')
{
	$query = "select r.name from redtags r where r.name like '%$search%' order by name asc";
}
elseif($table=='products')
{
    $query = "select distinct $field, description from $table where $field like '%$search%' order by $field asc";	
}
elseif($table=='areas' || $table=='diseases')
{
	$field="display_name";
	$table="entities";
	if($mesh_display=="YES")
	{
		$mesh_condition=" AND mesh_name!=''";
	}
	$query = "select distinct $field, description from $table where $field like '%$search%'  $mesh_condition order by $field asc";
}
elseif($table=='trialzilla')
{
	$query = "select distinct `name`, `description`, `class` from `entities` where `name` like '%$search%' AND `class` IN ('Product','Institution','MOA','MOA_Category') limit 6";
}
elseif($table=='masterhm')
{
	$query = "select distinct `name`, `description`, `class` from `entities` where `name` like '%$search%' AND `class` NOT IN ('MOA_Category') AND (`class` = 'Disease' AND (LI_id IS NOT NULL AND LI_id <> '') OR `class` <> 'Disease') order by $field asc";
}
else
{
	$table="entities";
	$query = "select distinct $field from $table where $field like '%$search%' order by $field asc";
}

if(isset($c_lid) and !empty($c_lid)) 
{
	$query = "select distinct $field from $table where  $field like '%$search%'  and larvol_id<>$c_lid  order by $field asc";
}

if( isset($hint) and !is_null($hint) and strlen($hint)>1 )
$query = "select distinct $field from $table  where ( $field like '%$hint%' ) and ( $field <> '$hint' ) order by $field asc limit 50";

$result =  mysql_query($query);
$data = array();
$json = array();
$suggestions = array();
$datas = array();
$suggestionsType = array();
if($table=='upm' && ($field=='product' || $field=='area' || $field=='redtag'))
{
	while($row = mysql_fetch_assoc($result))
	{
		$suggestions[] = $row['name'];
		if($field=='redtag')
			$datas[] = $row['name'];
		else
			$datas[] = $row['id'];
		$suggestionsType[] = '';
	}	
}
else if($table=='trialzilla' || $table=='masterhm')
{
	while($row = mysql_fetch_assoc($result))
	{
		$suggestions[] = $row['name'];
		if($row['class'] != NULL && $row['class'] != '')
		{
			$suggestionsType[] = $row['class'];	//Display type icon beside suggestion
		}
		else
			$suggestionsType[] = '';	//If suggestion type is NULL make it blank
	}
	$datas = $suggestions;
}
else 
{
	while($row = mysql_fetch_assoc($result))
	{
		$suggestions[] = $row[$field];
		if($table=='products' || $table=='areas')
		{
			if($row['description'] != NULL && $row['description'] != '')
			$datas[] = $row['description'];	//Display Description on Mouseover
			else
			$datas[] = ' ';	//If description is NULL make it blank
		}
		else
		$datas[] = $row[$field];
		$suggestionsType[] = '';
	}	
}
$json['query'] = $search;
$json['suggestions'] = $suggestions;
$json['data'] = $datas;
$json['suggestionsType'] = $suggestionsType;
$data[] = $json;
ob_end_clean();
gzip_compression();
echo json_encode($json);
die;

function gzip_compression() {

    //If no encoding was given - then it must not be able to accept gzip pages
    if( empty($_SERVER['HTTP_ACCEPT_ENCODING']) ) { return false; }

    //If zlib is not ALREADY compressing the page - and ob_gzhandler is set
    if (( ini_get('zlib.output_compression') == 'On'
        OR ini_get('zlib.output_compression_level') > 0 )
        OR ini_get('output_handler') == 'ob_gzhandler' ) {
        return false;
    }

    //Else if zlib is loaded start the compression.
    if ( extension_loaded( 'zlib' ) AND (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) ) {
        ob_start('ob_gzhandler');
    }

}
