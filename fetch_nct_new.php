<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('preindex_trial_new.php');
require_once('db.php');
require_once('include.import_new.php');
require_once('nct_common.php');
require_once('include.import.history_new.php');
ini_set('max_execution_time', '36000'); //10 hours


//Globals
$days = 0;
$last_id = 0;
$id_field = 0;

if(isset($_GET['days']))
{
	$days_to_fetch = (int)$_GET['days'];
}
if(isset($days_to_fetch))	//$days_to_fetch comes from cron.php normally
{
//	$days = (int)$days_to_fetch;
	$days = 30+(int)$days_to_fetch;
}else{
	die('Need to set $days_to_fetch or $_GET[' . "'days'" . ']');
}

$cron_run = isset($update_id); 	

if($cron_run)
{
	$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days='.$days.' WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running. Query='.$query.' Error:' . mysql_error());
}

echo("\n<br />" . 'Begin updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");

$methode = "update";
$url = "results?flds";

echo('Searching for updated records...' . "\n<br />");
$ids = getIDs('update');

if (count($ids) == 0) {
    echo('There are none!' . "\n<br />");
} 

    echo("<br /><br /> " . count($ids) . ' new updates out of ' . $reportednew . '.' . "\n<br />");
	if($cron_run)
	{
	    $query = 'UPDATE update_status SET update_items_total="' . count($ids) . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
    	$res = mysql_query($query) or die('Unable to update running. Query='.$query.' Error:' . mysql_error());
	}
	
    //Import the XML for all these new records
    echo('Fetching record content...' . "\n<br />");
    $progress_count = 0;
    foreach ($ids as $id => $one) {
		scrape_history(unpadnct($id));
		if($cron_run)
		{
			$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress = update_items_progress+1 WHERE update_id="' . $update_id . '"';
			$res = mysql_query($query) or die('Unable to update running. Query='.$query.' Error:' . mysql_error());
		}
    }
	if($cron_run)
	{
    	$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	    $res = mysql_query($query) or die('Unable to update running. Query='.$query.' Error:' . mysql_error());
	}


if($cron_run)
{
	$query = 'UPDATE update_status SET end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running. Query='.$query.' Error:' . mysql_error());
}
echo('Done with everything.');
?>  