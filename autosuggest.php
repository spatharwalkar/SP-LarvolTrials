<?php
require_once 'db.php';
$search = $_GET['query'];
$search = mysql_real_escape_string($search);
$query = "select distinct product from upm where product like '%$search%' order by product asc";
$result =  mysql_query($query);
$data = array();
$json = array();
$suggestions = array();
while($row = mysql_fetch_assoc($result))
{
	$suggestions[] = $row['product'];
}
$json['query'] = $search;
$json['suggestions'] = $suggestions;
$json['data'] = $suggestions;
$data[] = $json;
echo json_encode($json);
die;

