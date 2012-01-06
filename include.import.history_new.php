<?php

require_once('db.php');
require_once('include.import_new.php');
require_once('nct_common.php');
//require_once('preindex_trial.php');
ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();
$last_id = 0;
$id_field = 0;

function scrape_history($id)
// Get Days
{
	if (!isset($id) or empty($id)  ) 
	{
		die('No ID passed');
	} 

	$id = padnct($id);
	$unid = unpadnct($id);
	

	ProcessNew($id);
/*	
	echo('<br>End Parsing Archive Page for ' . $id);

	echo('<hr><br>Parse Current Study for all information and non essentials<br>');

//	ProcessNonEssentials($id, $lastdate);

	$studyCat=getStudyCatId($unid);
	echo('<br>Pre-indexing product related trials for id: ' . $id . '..........<br><br>');
	tindex($studyCat,'products');
	echo('<br>Pre-indexing area related trials for id: ' . $id . '.........<br><br>');
	tindex($studyCat,'areas');
		
*/		
	echo('<br>Finished Parsing Current Study with this ID.<br />');

	$query = 'UPDATE update_status SET end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running' . mysql_error());

	echo('<br>Completely Finished with this ID.<br />');
}
?>  