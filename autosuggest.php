<?php
ob_start();
require_once 'db.php';
$search = mysql_real_escape_string($_GET['query']);
$table = mysql_real_escape_string($_GET['table']);
$field = mysql_real_escape_string($_GET['field']);
$hint = mysql_real_escape_string($_GET['hint']);
$c_lid = mysql_real_escape_string($_GET['c_lid']);
//filter input
$autoSuggestTables = array('areas','upm','products','data_trials');
if(!in_array($table,$autoSuggestTables))die;

if($table=='upm' && $field=='product')
{
	$query = "select p.id,p.name from products p where p.name like '%$search%' and (p.is_active=1 or p.is_active is null) order by name asc";
}
elseif($table=='upm' && $field=='area')
{
	$query = "select a.id,a.name from areas a where a.name like '%$search%' and a.coverage_area=1 order by name asc";
}
elseif($table=='products' || $table=='areas')
{
	$query = "select distinct $field, description from $table where $field like '%$search%' order by $field asc";
}
else
{
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
if($table=='upm' && ($field=='product' || $field=='area'))
{
	while($row = mysql_fetch_assoc($result))
	{
		$suggestions[] = $row['name'];
		$datas[] = $row['id'];
	}	
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
	}	
}

$json['query'] = $search;
$json['suggestions'] = $suggestions;
$json['data'] = $datas;
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
