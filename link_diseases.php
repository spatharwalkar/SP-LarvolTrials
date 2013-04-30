<?php
require_once('db.php');
require_once('include.util.php');
ini_set('memory_limit','-1');
ini_set('error_reporting', E_ALL ^E_NOTICE );

$diseaseids=array();
		
$query = "select distinct id from entities where 
( li_id is null or (mesh_name is not null and mesh_name<>'') )
and class='disease'" ;

		$res = mysql_query($query) or die('Bad SQL query ');
		while($data = mysql_fetch_assoc($res)) $diseaseids[]=$data['id'];

foreach($diseaseids as $diseaseid) index_disease($diseaseid);		
 
function index_disease($diseaseid)
{
	pr('Associating disease id : '.$diseaseid);
	$diseaseids=array();
	
	$query = 'select distinct trial from entity_trials where 
	entity="'.$diseaseid.'"';
	$mesh_trials='';
	$res = mysql_query($query) or die('Bad SQL query ');
	while($data = mysql_fetch_assoc($res)) $mesh_trials.=','.$data['trial'];
	$mesh_trials=substr($mesh_trials,1);
	if(empty($mesh_trials)) return;
	
	$query = 'select distinct entity from entity_trials where 
			  trial in ('.$mesh_trials.') and entity not in 
			  (select id from entities where class="Disease")';
	$productids=array();
	$res = mysql_query($query) or die('Bad SQL query ');
	while($data = mysql_fetch_assoc($res)) $productids[]=$data['entity'];
	
	
	foreach ($productids as $productid)
	{
		$query = 'INSERT into entity_relations set 
				  parent="'.$diseaseid.'",child="'.$productid.'"';

		$res = @mysql_query($query);

	}
}

?>