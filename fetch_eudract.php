<?php
//connect to Sphinx
$sphinx = mysql_connect("127.0.0.1:9306") or die ("Couldn't connect to Sphinx server.");
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('preindex_trial.php');
require_once('db.php');
require_once('include.import_new.php');
require_once('eudract_common.php');
require_once('include.import.eudract.history.php');
ini_set('max_execution_time', '36000'); //10 hours
ignore_user_abort(true);

//Globals
global $logger;
$days = 0;
$last_id = 0;
$id_field = 0;

if(isset($_GET['days']))
{
	$days_to_fetch = (int)$_GET['days'];
}
if(isset($days_to_fetch))	//$days_to_fetch comes from cron.php normally
{
	$days = 30+(int)$days_to_fetch;
}
else
{
	$days=1;
   //die('Need to set $days_to_fetch or $_GET[' . "'days'" . ']');
}

$cron_run = isset($update_id); 	

if($cron_run)
{
	$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days='.$days.' WHERE update_id="1"';
	if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			//pass the control back to cron
			return false;
		}
}


echo("\n<br />" . 'Begin updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");

$methode = "update";
$url = "results?flds";

echo('Searching for updated records...' . "\n<br />");
$ids = getEudraIDs();
if (count($ids) == 0) 
{
    echo('There are none!' . "\n<br />");
	return false;
} 

if(!$cron_run)
{
	$cron_run=true;
	$count=count($ids);
	$update_id="1";
	$query = 'SELECT update_id AS maxid FROM update_status where update_id="1" ' ;
	$res = mysql_query($query) ;
	if(!$res = mysql_query($query))
		{
			$log='Unable to get max id from update_status_fullhistory. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			//pass the control back to cron
			return false;
		}
	$res = mysql_fetch_array($res) ;
	if(!isset($res['maxid']))
	{
	$query = '	INSERT INTO update_status SET 
				update_id="1",
				start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", 
				updated_days="'.$days.'" ,
				update_items_total="' . $count . '",
				update_items_progress="0",
				update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"'; 
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			//pass the control back to cron
			return false;
		}
	}
	else
	{
	$query = '	UPDATE update_status SET 
				start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", 
				updated_days="'.$days.'" ,
				update_items_total="' . $count . '",
				update_items_progress="0",
				update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"
				WHERE update_id="1"'
				; 
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			//pass the control back to cron
			return false;
		}
	}
	
}

///***** get only new updates  */
//$query = 'SELECT UNIX_TIMESTAMP(lastchanged_date) AS "lastchanged_date",source_id FROM data_trials WHERE 
//			source_id IN("' . implode('","', array_keys($ids)) . '")';
//$res = mysql_query($query);
//if ($res === false) return softDie('Bad SQL query getting lastchanged dates for existing nct_ids');
//$totcnt=count($ids);
//while ($row = mysql_fetch_assoc($res)) 
//{
//	if($row['lastchanged_date'] >= $ids[$row['source_id']])
//	{
////		pr($row['source_id']);
//		unset($ids[$row['source_id']]);
//	}
//				
//}
///*********/

$count = count($ids);
//foreach ($ids as $key => $value){
//	$count += count($value);
//}

echo("<br /><br /> New Updates : " . $count . "\n<br />");

	    $query = 'UPDATE update_status SET update_items_total="' . $count . '",update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="1"';
    	
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			//pass the control back to cron
			return false;
		}

	
    //Import the XML for all these new records
    echo('Fetching record content...' . "\n<br />");
    $progress_count = 0;
    
    
    foreach ($ids as $key => $value) 
	{
//		foreach ($value as $country) 
//		{
		//scrape_history($key . ' - ' . $country);
		scrape_history($key , $value);
		echo str_repeat("   ",500).'<br>';
			$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress = update_items_progress+1 WHERE update_id="1"';
			
			if(!$res = mysql_query($query))
			{
				$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				echo $log;
				//pass the control back to cron
				return false;
			}
		
//		}
	}
    	$query = '	UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '",
					end_time="' . date("Y-m-d H:i:s", strtotime('now')) .'", update_items_progress=update_items_total WHERE update_id="1"';
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			//pass the control back to cron
			return false;
		}
		
		if(!mysql_query('COMMIT'))
			{
			$log='There seems to be a problem while committing the transaction Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			mysql_query('ROLLBACK');
			echo $log;
			return false;
			}

echo('Done with everything.');
?>  