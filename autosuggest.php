<?php
ob_start();
require_once 'db.php';
$search = mysql_real_escape_string($_GET['query']);
$table = mysql_real_escape_string($_GET['table']);
$field = mysql_real_escape_string($_GET['field']);

//filter input
$autoSuggestTables = array('areas','upm','products');
if(!in_array($table,$autoSuggestTables))die;

if($table=='upm' && $field=='product')
$query = "select p.id,p.name from products p where p.name like '%$search%' and (p.is_active=1 or p.is_active is null) order by name asc";
else
$query = "select distinct $field from $table where $field like '%$search%' order by $field asc";
$result =  mysql_query($query);
$data = array();
$json = array();
$suggestions = array();
$datas = array();
if($table=='upm' && $field=='product')
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
