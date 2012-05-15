<?php

require_once('db.php');
require_once('include.import.php');
require_once('nct_common.php');
require_once('include.search.php');
//require_once('preindex_trial.php');

ini_set('max_execution_time', '36000'); //10 hours

ignore_user_abort(true);
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
        echo('<br/> Getting Archive Page for ' . $id . '... - ');
        $url = "http://clinicaltrials.gov/archive/" . $id;
        $doc = new DOMDocument();
        echo('Parsing Archive Page for ' . $id . '... - ');

        echo $url;
        //*****
        for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
            $done = $doc->loadHTMLFile($url);
        }

        // Get Rows with "Versions in class"
        $ths = $doc->getElementsByTagName('th');
        $loopcounter = 0;

        // flags
        $agentorage = false;
        // $willis=false;    

        $unid = unpadnct($id);
        $cat = array_search($unid, $existing);
        if ($cat == null) {
            // New Record
            // In case there was not a standard base set a flag so
            // we know coming in next time we can process the differences
            //Do Nothing.. Will fall into Agent Orange!
            $agentorange = true;
        } else {
            // Get Max Last Updated Date.
            $query = 'SELECT val_date FROM data_values '
                    . 'WHERE field=' . $last_id
                    . ' AND studycat = ' . $cat . ' and superceded is null';
            $res = mysql_query($query);

            if ($res === false)
                return softDie('Bad SQL query getting last update time for existing case');

            $row = mysql_fetch_assoc($res);

            $dateindb = $row['val_date'];

            // Set GoldMine Flag so if we find new records we know to process
            // everything after that.
            $goldmine = false;
        }
        $count = 0;

		foreach ($ths as $th) {
            foreach ($th->attributes as $attr) {
                if ($attr->name == 'scope' && $attr->value == 'row') {
                    if ($count == 0) {
                        // skip first line just initial version
						$initial_date = $th->nodeValue;
                        echo "<br/>";
                    } else {

                        if ($agentorange == true) {
                            //Process Left
                            // This means there is new stuff for us to review and store.
                            echo "<br />Processing Left Column Value for initial <br />";
                            //ProcessChanges($id, $th->nodeValue, "sdiff-a");
							if(isset($initial_date))
								ProcessChanges($id, $th->nodeValue, "sdiff-a", $initial_date);
							else
								ProcessChanges($id, $th->nodeValue, "sdiff-a");
                            // Set CatID for later lookup which is the studycat number.
                            $query = 'SELECT val_int AS "nct_id",studycat FROM data_values '
                                    . 'WHERE field=' . $id_field
                                    . ' AND val_int=' . unpadnct($id);
                            $res = mysql_query($query);
                            $row = mysql_fetch_assoc($res);
                            $cat = $row['studycat'];

                            //Retire Agent Orange
                            $agentorange = false;
                        }

                        if ($goldmine == true) {
                            // We struck gold "new records" process each row following
                            echo "New Date Record - Next Row <br />";
                            ProcessChanges($id, $th->nodeValue, "sdiff-b");
                        } else {

                            $convertdaterow = str_replace("_", "-", $th->nodeValue);
                            //echo "<hr>Value: " . $th->nodeValue . " = " . $convertdaterow . " <br />";
                            //echo "Value in DB: " . $dateindb . " <br />";

                            if (strtotime($convertdaterow) > strtotime($dateindb)) {
                                // This means there is new stuff for us to review and store.
                                echo "New Date Record <br />";
                                ProcessChanges($id, $th->nodeValue, "sdiff-b");

                                // Set Gold Mind for future Records
                                $goldmine = true;
                            } else {
                                echo "Less Then Last Date - skipping";
                                // Keep Alive..
                                $fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
                                @mysql_fetch_array($fake);
                            }
                        }
                    }
                    $count = $count + 1;
                }
            }
        }

        // See if Agent Orange is Still causing trouble if so ProcessNew
        if ($agentorange == true) {
            ProcessNew($id);
            $agentorange = false;
        }

        unset($ths);
        unset($doc);
		
		/*** Preindex the trial */
		/*
		echo '<br>Starting preindexing of '. $id . 'Time: '. date("Y-m-d H:i:s", strtotime('now')) . str_repeat (" .....",1);
		$studyCat=getStudyCatId($unid);
		tindex($studyCat,'products');
		tindex($studyCat,'areas');
		echo '<br><br>Completed preindexing of '. $id . 'Time: '. date("Y-m-d H:i:s", strtotime('now')) . str_repeat (" ......",1);
		*/
        echo('End Parsing Archive Page for ' . $id);

        $progress_count++;
		if($cron_run)
		{
        	$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress="' . $progress_count . '" WHERE update_id="' . $update_id . '"';
	        $res = mysql_query($query) or die('Unable to update running');
		}
//		echo('<br/>Snoring<br/>');
//        sleep(rand(5, 15));
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