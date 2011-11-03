<?php
ob_start();
require_once 'db.php';
$search = mysql_real_escape_string($_GET['query']);
$table = mysql_real_escape_string($_GET['table']);
$field = mysql_real_escape_string($_GET['field']);

//filter input
$autoSuggestTables = array('areas','upm','product');
if(!in_array($table,$autoSuggestTables))die;

if($table=='upm' && $field=='product')
$query = "select p.id,p.name from products p where p.name like '%$search%' and p.is_active=1 order by name asc";
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
echo json_encode($json);
die;

