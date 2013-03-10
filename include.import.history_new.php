<?php

require_once('db.php');
require_once('include.import_new.php');
require_once('nct_common.php');
require_once('preindex_trial.php');
//require_once('preindex_trial_old.php');  // TO BE REMOVED LATER
ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();

function scrape_history($id)
{
	if (!isset($id) or empty($id)  ) 
	{
		return 'No NCTID passed';
	} 
	
	$id = padnct($id);
	$unid = unpadnct($id);
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Refreshing trial : ' . $id .   str_repeat("     ",300) . '<br>');
		
	ProcessNew($id);
	
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Preindexing trial : ' . $id .   str_repeat("     ",300) );
	tindex(padnct($id),'products');
	//tindex_old(padnct($id),'products');  // to be removed
	
	tindex(padnct($id),'areas');
	//tindex_old(padnct($id),'areas');  // to be removed
	
	$query = 'UPDATE update_status SET `end_time`="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE `update_id`="' . $update_id . '"';
	if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Completely Finished with  ID : ' . $id .    str_repeat("     ",300) . '<br>');
	
}
?>  