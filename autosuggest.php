<?php
ob_start();
require_once 'db.php';
$search = mysql_real_escape_string($_GET['query']);
$table = mysql_real_escape_string($_GET['table']);
$field = mysql_real_escape_string($_GET['field']);

//filter input
$autoSuggestTables = array('areas','upm','product');
if(!in_array($table,$autoSuggestTables))die;

$query = "select distinct $field from $table where $field like '%$search%' order by $field asc";
$result =  mysql_query($query);
$data = array();
$json = array();
$suggestions = array();
while($row = mysql_fetch_assoc($result))
{
	$suggestions[] = $row[$field];
}
$json['query'] = $search;
$json['suggestions'] = $suggestions;
$json['data'] = $suggestions;
$data[] = $json;
ob_end_clean();
echo json_encode($json);
die;

