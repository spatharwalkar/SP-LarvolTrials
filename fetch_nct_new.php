<?php

require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('preindex_trial.php');
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

$cron_run = isset($update_id); 	// check if being run by cron.php

if($cron_run)
{
	$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days='.$days.' WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
}


echo("\n<br />" . 'Begin updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");

/* Do not need to get new records anymore. New records will be shown in update query since they
  are updated as well. Since we are getting all history for that record that we do not have
  we no longer need distinction.
 */

//Now get updates
$methode = "update";
$url = "results?flds";

echo('Searching for updated records...' . "\n<br />");
$ids = getIDs('update');

if (count($ids) == 0) {
    echo('There are none!' . "\n<br />");
} else {
    // Filter already Captured Records with Todays Update Date.
    // Assumption: A record can only be updated once a day and all changes 
    // will be there.
// **    $ids = filterNewChanges($ids,$existing);
    //Now that we have all updated NCTIDs from all pages, throw out the ones we already have the latest version of
    echo('Checking which records are not already updated on our side...' . "\n<br />");
    $reportednew = count($ids);

    //Find out the ID of the field for lastchanged_date
    $query = 'SELECT data_fields.id AS "last_id" '
            . 'FROM data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
            . 'WHERE data_fields.name="lastchanged_date" AND data_categories.name="NCT" LIMIT 1';
    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query getting field ID of lastchanged_date');
    $res = mysql_fetch_assoc($res);
    if ($res === false)
        return softDie('NCT schema not found!');
    $last_id = $res['last_id'];
    $id_field = "1";

    //get the studycats for the reported IDs that exist in the database
    $query = 'SELECT val_int AS "nct_id",studycat FROM data_values '
            . 'WHERE field=' . $id_field
            . ' AND val_int IN(' . implode(',', array_map('unpadnct', array_keys($ids))) . ')';

    $res = mysql_query($query);
    if ($res === false)
        return softDie('Bad SQL query getting existing nct_ids');

    while ($row = mysql_fetch_assoc($res)) {
        $existing[$row['studycat']] = $row['nct_id'];
    }

    if ($existing != null) {
        //get the lastchanged dates for the studycats
        $query = 'SELECT UNIX_TIMESTAMP(val_date) AS "lastchanged_date",studycat FROM data_values WHERE field=' . $last_id
                . ' AND studycat IN(' . implode(',', array_keys($existing)) . ')';
        $res = mysql_query($query);
        if ($res === false)
            return softDie('Bad SQL query getting lastchanged dates for existing nct_ids');
        while ($row = mysql_fetch_assoc($res)) {//todo: make sure this works
            //This is the reason we stored the IDs as keys instead of values -- we don't need an array search to unset them
            if ($row['lastchanged_date'] >= $ids[padnct($existing[$row['studycat']])])
                unset($ids[padnct($existing[$row['studycat']])]);
        }
    }

    echo("<br /><br /> " . count($ids) . ' new updates out of ' . $reportednew . '.' . "\n<br />");
	if($cron_run)
	{
	    $query = 'UPDATE update_status SET update_items_total="' . count($ids) . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
    	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
	}
	
    //Get and import the XML for all these new records
    echo('Fetching record content...' . "\n<br />");
    $progress_count = 0;
    foreach ($ids as $id => $one) {
		scrape_history(unpadnct($id));
    }
	if($cron_run)
	{
    	$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	    $res = mysql_query($query) or die('Unable to update running');
	}
}

if($cron_run)
{
	$query = 'UPDATE update_status SET end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running' . mysql_error());
}
echo('Done with everything.');
?>  