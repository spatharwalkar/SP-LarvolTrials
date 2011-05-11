<?php
require_once('db.php');
require_once('include.import.php');

global $days_to_fetch;
global $update_id;

if(isset($_GET['maxrun'])) ini_set('max_execution_time','36000');	//10 hours
$days = 0;
/*
if(isset($_GET['days']))
{
	$days = (int)$_GET['days'];
}else{
	die('Need to set $_GET[\'days\']');
}
*/
if(isset($days_to_fetch))
{
	$days = (int)$days_to_fetch;
}else{
	die('Need to set $days_to_fetch');
}

//Find out the ID of the field for PMID, and the ID of the "PubMed" category.
$query = 'SELECT data_fields.id AS "pmid",data_categories.id AS "pubmed_cat" FROM '
		. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="PMID" AND data_categories.name="PubMed" LIMIT 1';
$res = mysql_query($query);
if($res === false) return softDie('Bad SQL query getting field ID of nct_id');
$res = mysql_fetch_assoc($res);
if($res === false) return softDie('NCT schema not found!');
$id_field = $res['pmid'];
$pubmed_cat = $res['pubmed_cat'];

echo("\n<br />" . 'Begin updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");
echo('Searching for new records...' . "\n<br />");

?>