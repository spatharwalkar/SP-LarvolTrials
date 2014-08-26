<?php
require_once('db.php');
require_once('include.util.php');
require_once('db.php');
require_once('include.import_pm.php');
require_once('pm_common.php');
ini_set('max_execution_time', '36000'); //10 hours
ignore_user_abort(true);
ini_set('error_reporting', E_ALL );
//Globals
global $logger;
$days = 0;
$last_id = 0;

if(isset($_GET['days']))
{
	$days = (int)$_GET['days'];
	run_incremental_scraper($days);
}

function run_incremental_scraper($days=NULL)
{
	global $update_id;
	$cron_run = isset($update_id); 	
	if(is_null($days)) $days = 1;
	if($days>10) $days=10;
	$update_id=9;
	if($cron_run)
	{
		
		$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '", updated_days='.$days.' ,  update_items_progress = "0"  WHERE update_id="' . $update_id . '"';
		if(!$res = mysql_query($query))
			{
				$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				echo $log;
				//pass the control back to cron
				return false;
			}
	}

	echo("\n<br />" . 'Begin pubmed updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");

	echo('Searching for pubmed records...' . "\n<br />");
	if(ok2runPMscraper())
		$ids = getIDs($days);
	else
	{
		$log= '<br> Pubmed scraper is not allowed to run at this time ( '.$day.'  '. date('h:ia', time()) .')';
		global $logger;	
		$logger->error($log);
		echo $log;
		die();
		return false;
	}
	
	if (count($ids) == 0) 
	{
		echo('There are none!' . "\n<br />");
		return false;
	} 
	$totcnt=count($ids);

	echo("<br /><br /> " . count($ids) . ' pubmed updates '. "\n<br />");
		if($cron_run)
		{
			
			$query = 'UPDATE update_status 
					  SET update_items_total="' . count($ids) . '",
					  update_items_start_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" 
					  WHERE update_id="' . $update_id . '"';
					  
			if(!$res = mysql_query($query))
			{
				$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				echo $log;
				//pass the control back to cron
				return false;
			}
		}
		
		
		echo('Fetching record content...' . "\n<br />");
		$progress_count = 2;
		$current_timestamp = time();
		foreach ($ids as $id => $one) 
		{

			if(ok2runPMscraper())
				ProcessNew($one);
			else
			{
				$log= '<br> Pubmed scraper is not allowed to run at this time ( '.$day.'  '. date('h:ia', time()) .')';
				global $logger;	
				$logger->error($log);
				echo $log;
				die();
				return false;
			}
			
			if($current_timestamp == time()) 
			{
				sleep(1);
				$current_timestamp = time();
			}
			if($cron_run)
			{
				$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_progress = update_items_progress+' . $progress_count . ' WHERE update_id="' . $update_id . '"';
				$progress_count = 1;
				if(!$res = mysql_query($query))
				{
					$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					
					return false;
				}
			}
		}
		if($cron_run)
		{
			$query = 'UPDATE update_status SET update_items_progress=update_items_total,updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '",update_items_complete_time ="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';

			if(!$res = mysql_query($query))
			{
				$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
				$logger->fatal($log);
				echo $log;
				
				return false;
			}
		}


	if($cron_run)
	{
		$query = 'UPDATE update_status SET update_items_progress=update_items_total, end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
		if(!$res = mysql_query($query))
		{
			$log='Unable to update update_status. Query='.$query.' Error:' . mysql_error();
			$logger->fatal($log);
			echo $log;
			
			return false;
		}
	}
	echo('Done with everything.');
}


?>  