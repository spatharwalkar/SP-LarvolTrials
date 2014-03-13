#!/usr/bin/php -q
<?php
/*
* ACTIVITY
*	Updation of database and generation of reports based on Scheduler settings. Status 
* of the execution is constantly logged into database tables. Based on the status,
* multiple instances can run simultaneously(each running different tasks).
*
* The total number of tasks that can run in parallel is equal to the total number of
* tasks in the shheduler. Maximum number of tasks that can be initiated by a single 
* call of this script is limited to 3. 
* 
* DESCRIPTION
*  Each of the reports or update items can be in one of the following states
* 1. 'ready' 			- 1
* 2. 'running'			- 2
* 3. 'error'			- 3
* 4. 'cancelled'		- 4
* 5. 'complete'			- 0
* Each time this script is called, the following steps take place. 
* A. Get number of tasks that are newly scheduled to run since the last time the
*	 script was executed.
* B. - If new tasks = 0, check if there are any tasks that have reports/updates 
		that are ready to run and pick 1 such task. If no such tasks terminate.
	 - If new task = 1, pick the task and start execution.
	 - If new tasks >1, pick upto 3 new tasks and start execution. The reports/
		updates of the reamaining tasks are made 'ready'.
		
* The steps invloved in the task execution are below. They are executed in order
* repeatedly till all reports and updates of the selected task are running/completed.
* 1. 'update_status' and 'reports_status' are checked for status 'running. It is then 
* 	checked against all curring running instances of cron.php. In case any report/
*		updte has crashed but the status still shows running, status is changed to error.
* 2. 'schedule' is checked for updates and reports that need to be run. Each update 
*		that needs to run (nct,pubmed) is added to 'update_status' as a separate entry.
* 	Similarly each report that needs to run is added to 'reports_status'. The status
*		is set as 'ready to run'. If same entries are already there, they are ignored.
*		The 'lastrun' time is updated in 'schedule'.
* 3. If 'update_status' has any entry with status 'ready to run' or
*	  'error' updation starts. All the updates will be done one after the other. Only 
*		then will it proceed to report generation.
* 4. If updating is not required OR after it is complete, 'reports_status' is checked
* 		for 'ready to run'/'error'. All these reports will be generated in order one 
*		after the other, status being changed to 'running' and then 'complete'. Mail is
*		sent to the corresponding ID with the report attached.
*/

ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);

//Definition of constants for states
define('READY', 1);
define('RUNNING', 2);
define('ERROR', 3);
define('CANCELLED', 4);
define('COMPLETED', 0);

//Select maximum parallel reports per schedule item
$max_process_per_item=1;

//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
require_once('db.php');

require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once('class.phpmailer.php');


//variables used for running update
global $days_to_fetch;
global $update_id;

/************************************ Step A ****************************************/
$nl = "\n";
$allhours = array();
for($hour = 0; $hour < 24; ++$hour) $allhours[pow(2, $hour)] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
$alldays = array();
$daynames = array(24 => 'Monday', 25 => 'Tuesday', 26 => 'Wednesday', 27 => 'Thursday',
				  28 => 'Friday', 29 => 'Saturday', 30 => 'Sunday');
for($day = 24; $day < 31; ++$day) $alldays[pow(2, $day)] = $daynames[$day];

ini_set('memory_limit','-1');
ini_set('max_execution_time','360000');	//100 hours
sleep(1);	//ensure that we always check and run things after (and not during) the scheduled time


echo($nl);
echo ('<pre>Checking schedule for updates and reports....' . $nl);
//Fetch schedule data 
$schedule = array();
$fetch = array();
$query = 'SELECT * FROM schedule WHERE runtimes!=0';
$res = mysql_query($query) or die('Bad SQL Query getting schedule');
$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;

foreach($tasks as $row)
{
	//Get time when scheduler item was last checked, in Unix time
	$lastrun = strtotime($row['lastrun']);
	//Read schedule of current item and convert to Unix time
	$hours = array();
	$days = array();
	for($power = 0; $power < 24; ++$power)
	{
		$hour = pow(2, $power);
		if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
	}
	
	for($power = 24; $power < 31; ++$power)
	{
		$day = pow(2, $power);
		if($row['runtimes'] & $day) $days[] = $alldays[$day];
	}
	
	$due = false;
	foreach($hours as $hour)
	{
		foreach($days as $day)
		{
			$sched = strtotime($day . $hour, $lastrun);
			$sched2 = strtotime('next ' . $day . $hour, $lastrun);
			if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
			{
				//Break if current item needs to be checked for updates/reports
				$due = true;
				break 2;
			}
		}
	}
	if($due)
	{
		//check for li syncable rows
		if($row['LI_sync'] && is_numeric($row['LI_sync']))
		{
			$LISyncTasks[] = $row;
			continue;
		}		
		//clean stall queries
		if( !is_null($row['clean_stalled_query']) and $row['clean_stalled_query']==1 )
		{
			echo '<br>Clean Up of all Stalled Queries ...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('kill_mysql_proceses.php'); 
			continue;
		}
		//tab count query
		if( !is_null($row['tab_count_entity']) and $row['tab_count_entity']==1 )
		{
			echo '<br>Tab Count for Entities ...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('count_entities_tabs.php'); ///specify file name
			continue;
		}
		//Calculate Master HM cells if scheduled
		if( !is_null($row['calc_HM']) and $row['calc_HM']==1 )
		{
			echo '<br>Calculating Master HM cells...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
		
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('calculate_hm_cells.php');
			if(!calc_cells(NULL,4)) echo '<br><b>Could complete calculating cells, there was an error.<br></b>';
			else continue;
		}
		
		//generate news if scheduled
		if( !is_null($row['generate_news']) and $row['generate_news']==1 )
		{			
			echo '<br>Generating News...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
		
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('generateNews.php');
			generateNews(30);
			continue;
		}

		//Import diseases
		if( !is_null($row['get_diseases']) and $row['get_diseases']==1 )
		{
			echo '<br>Importing Diseases from clinicaltrials.gov ...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('fetch_diseases.php');
			continue;
		}
		//Import disease categories
		if( !is_null($row['get_disease_cat']) and $row['get_disease_cat']==1 )
		{
			echo '<br>Importing Disease categories from clinicaltrials.gov ...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('fetch_disease_categories.php');
			continue;
		}
		
		//Update UPM status (fire the trigger)  if scheduled
		if( !is_null($row['upm_status']) and $row['upm_status']==1 )
		{
			echo '<br>Updating UPM status values ...<br>';
			$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $row['id'] . ' LIMIT 1';
		
			global $logger;
			if(!mysql_query($query))
			{
				$log='Error saving changes to schedule: ' . mysql_error() . '('. mysql_errno() .'), Query:' . $query;
				$logger->fatal($log);
				die($log);
			}
			require_once('upm_trigger.php');
			if(!fire_upm_trigger()) echo '<br><b>Could complete Updating UPM status values, there was an error.<br></b>';
			else 
			{
				echo 'UPM status update completed.<br><br>';
				continue;
			}
		}
		
		
		//Get data of current item(which must be checked for updates/reports)
		$schedule[] = $row;
		$schedule_tasks[]=$row['id'];
		if($row['fetch'] != 'none')
		{
			//Max number of previous days to check for new records for 
			// nct and pubmed database separately
			if($row['fetch'] == 'nct_new') $scrapercode=3; 
			elseif($row['fetch'] == 'pubmed') $scrapercode=9;
			else $scrapercode=0;
			if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
				$fetch[$row['fetch']] = $lastrun;
		}
		
	}
}

$currently_scheduled_tasks=$schedule_tasks;

$current_tasks_count=count($currently_scheduled_tasks);
echo ($current_tasks_count." new tasks found.".$nl);

//no threading needed for LI sync so doing it now....
//start LI sync
echo count($LISyncTasks)." new LI sync tasks found.".$nl;
if(isset($LISyncTasks) and !empty($LISyncTasks))
{
	foreach($LISyncTasks as $syncTask)
	{
		$lastRunBkupFlg = false;
		$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $syncTask['id'] . ' LIMIT 1';
		if(mysql_query($query))
		{
			echo 'LI sync schedule lastrun updated.'.$nl;
		}
		else
		{
			die('Bad SQL query setting LI sync lastrun in schedule. Error: '.mysql_error());
		}
		echo 'LI Sync in process...'.$nl;
		
		//all below data is from scehdule.php to show which bit uses which sync as we use bitmask.
		//Adding or modifying any sycn here should also modify in cron.php as well to kept all correct
		//'LI_sync1' = 'LI Product Sync';
		//'LI_sync2' = 'LI Disease Sync';
		//'LI_sync4' = 'LI Institutions Sync';
		//'LI_sync8' = 'LI MOAs Sync';
		//'LI_sync16' = 'LI MOA Categories Sync';
		//'LI_sync32' = 'LI Therapeutic Sync';
		$LastSyncId = 32;	//Match it from schedule.php
		//end
	
		$LI_syncDecode = $syncTask['LI_sync'];
		while($LI_syncDecode)
		{
			if($LastSyncId <= $LI_syncDecode)
			{
				if($LastSyncId == 16)
				{
					//LI MOA Categories Sync
					require_once 'fetch_li_moacategories.php';
					$processOutput = fetch_li_moacategories(strtotime($syncTask['lastrun']));
					if($processOutput['exitProcess']) { $lastRunBkupFlg = true; break;}
				}
				else if($LastSyncId == 8)
				{
					//LI MOAs Sync
					require_once 'fetch_li_moas.php';
					$processOutput = fetch_li_moas(strtotime($syncTask['lastrun']));
					if($processOutput['exitProcess']) { $lastRunBkupFlg = true; break;}				
				}
				else if($LastSyncId == 4)
				{
					//LI Institutions Sync
					require_once 'fetch_li_institutions.php';
					$processOutput = fetch_li_institutions(strtotime($syncTask['lastrun']));
					if($processOutput['exitProcess']) { $lastRunBkupFlg = true; break;}

				}
				else if($LastSyncId == 2)
				{
					//LI Disease Sync
					require_once 'fetch_li_diseases.php';
					$processOutput = fetch_li_diseases(strtotime($syncTask['lastrun']));
					if($processOutput['exitProcess']) { $lastRunBkupFlg = true; break;}					
				}
				else if($LastSyncId == 1)
				{
					//LI Product Sync
					require_once 'fetch_li_products.php';
					$processOutput = fetch_li_products(strtotime($syncTask['lastrun']));
					if($processOutput['exitProcess']) { $lastRunBkupFlg = true; break;}
					
				}
				
				$LI_syncDecode = $LI_syncDecode - $LastSyncId;
				$LastSyncId = $LastSyncId/2;
			}
			else
			{
				$LastSyncId = $LastSyncId/2;
			}
			
		}
		
		//echo date('Y-m-d H:i:s',$now);die;
		if($lastRunBkupFlg)	//if process is exited due to bad data set lastrun to previous value
		{
			$query = 'UPDATE schedule SET lastrun="' . $syncTask['lastrun'] . '" WHERE id=' . $syncTask['id'] . ' LIMIT 1';
			if(mysql_query($query))
			{
				echo 'LI sync schedule lastrun reupdated to previous lastrun as sync process not completed due to bad data.'.$nl;
			}
			else
			{
				die('Bad SQL query setting LI sync lastrun in schedule. Error: '.mysql_error());
			}
		}
	}
}
//end LI sync

mysql_close($db->db_link) or die("Error disconnecting from database server!");
/************************************ Step A ****************************************/


/************************************ Step B ****************************************/

//Current tasks count 0 indicates no newly scheduled tasks have been found
if($current_tasks_count==0)
{
	mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
	mysql_select_db(DB_NAME) or die("Could not find database on server!");
	//increase result length in bytes  for the GROUP_CONCAT() function  (default is 1024)
	mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");	
	//Check if there are any privious tasks that are yet to be run
	if(empty($selected_schedule_item))
	{
		$schedule_item_found=0;

		//If no tasks found terminate
		if($schedule_item_found==0)
		{
			echo ("All tasks are already running.".$nl);
			echo ($nl."Stopping execution.".$nl.$nl);
			echo('</pre>');
			die();
		}
	}
	
	//Keep checking in current process till all updates & reports are running/completed
	while(1)
	{			
		$pid = pcntl_fork();

		if($pid)
		{
			//Wait till child process completes execution/crashes
			pcntl_waitpid($pid, $status, WUNTRACED);
			if ($status==1)
			{
				echo ($nl."Continuing execution...".$nl.$nl);
				
			}
			else if ($status==2)
			{
				echo ($nl."Stopping execution.".$nl.$nl);
				echo('</pre>');
				die();
			}
			else
			{
				echo ($nl."Crash detected. Continuing execution skipping crashed item...".$nl.$nl);
			}
		}
		else
		{
			//Get the PID of child process
			$pid=getmypid();			
			
			/************************************ Step 1 ****************************************/
			$now = strtotime('now');
			echo($nl . 'Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
			echo($nl);
			
			echo ('Checking for any updates or reports that have crashed..' . $nl);	
			//Get Process IDs of all currently running updates
			$query = 'SELECT `update_id`,`process_id` FROM update_status WHERE `status`='.RUNNING;
			$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
			$count_upids=0;
			while($row = mysql_fetch_assoc($res))
			{
				$update_ids[$count_upids] = $row['update_id'];
				$update_pids[$count_upids++] = $row['process_id'];
			}
			
		
			//Get list of all currently running 
			$cmd = "ps aux|grep php";
			exec($cmd, $output, $result);
			for($i=0;$i < count($output); $i++)
			{
				$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
				$exp_out=explode(" ",$output[$i]);
				$running_pids[$i]=$exp_out[1];
			}
			
			//Check if any update has terminated abruptly
			for($i=0;$i < $count_upids; $i++)
			{
				//If update_status is running and corresponding process ID is not running
				if(!in_array($update_pids[$i],$running_pids))
				{
					switch($update_ids[$i])
					{
						case 0:
						$updtname='nct';
						break;
						case 1:
						$updtname='eudract';
						break;
						case 2:
						$updtname='isrctn';
						break;
						case 3:
						$updtname='nct_new';
						break;
						case 9:
						$updtname='pubmed';
						break;
					}
					//Update status set to 'error'
					echo($updtname  .' database updation error. Requeueing it.' . $nl);
					$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
					$res = mysql_query($query) or die('Bad SQL Query setting update error status');
				}
			}
			
			/************************************ Step 1 ****************************************/			
			
			
			/************************************ Step 2 ****************************************/
			echo($nl);
			echo ('Checking schedule for updates and reports..' . $nl);
			//Fetch schedule data 
			$schedule = array();
			$fetch = array();
			$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
			$res = mysql_query($query) or die('Bad SQL Query getting schedule');
			$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
			
			foreach($tasks as $row)
			{
				//Get time when scheduler item was last checked, in Unix time
				$lastrun = strtotime($row['lastrun']);
				//Read schedule of current item and convert to Unix time
				$hours = array();
				$days = array();
				for($power = 0; $power < 24; ++$power)
				{
					$hour = pow(2, $power);
					if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
				}
				
				for($power = 24; $power < 31; ++$power)
				{
					$day = pow(2, $power);
					if($row['runtimes'] & $day) $days[] = $alldays[$day];
				}
				
				$due = false;
				foreach($hours as $hour)
				{
					foreach($days as $day)
					{
						$sched = strtotime($day . $hour, $lastrun);
						$sched2 = strtotime('next ' . $day . $hour, $lastrun);
						if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
						{
							//Break if current item needs to be checked for updates/reports
							$due = true;
							break 2;
						}
					}
				}
				if($due)
				{
					//Get data of current item(which must be checked for updates/reports)
					$schedule[] = $row;
					if($row['fetch'] != 'none')
					{
						//Max number of previous days to check for new records for 
						// nct and pubmed database separately
						if($row['fetch'] == 'nct_new') $scrapercode=3;
						elseif($row['fetch'] == 'pubmed') $scrapercode=9;
						else $scrapercode=0;
						if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
							$fetch[$row['fetch']] = $lastrun;
					}
				}
			}
			//Get all entries in 'update_status'
			$query = 'SELECT `update_id`,`status` FROM update_status';
			$res = mysql_query($query) or die('Bad SQL Query getting update_status');
			$update_status = array();
			$count=0;
			while($row = mysql_fetch_assoc($res))
			{
				$update_status['id'.$count] = $row['update_id'];
				$update_status[$count++] = $row['status'];
			}
			
			//Check if any updates(nct/pubmed) have been newly scheduled and add to update_status
			if(count($fetch))
			{
				$fetchers = $fetch;
				$count=0;
				foreach($fetchers as $s => $lastrun)
				{
					switch($s)
					{
						case 'nct':
						$updtid=0;
						break;
						case 'eudract':
						$updtid=1;
						break;
						case 'isrctn':
						$updtid=2;
						break;
						case 'nct_new':
						$updtid=3;
						break;
						case 'pubmed':
						$updtid=9;
						break;
					}
					if($update_status[$count]==COMPLETED and $scrapercode==$updtid )
					{
						//Remove previous entry corresponding to completed update
						$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
						if($res==1)
							echo('Removed previous entry for '.$s.$nl);
						
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET update_items_progress=0,  update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
					}
					else if ($update_status[$count]==READY and $scrapercode==$updtid )
					{
						//Since entry with 'ready' status already exists, update it retaining the state
					echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
					}
					else if ($update_status[$count]==CANCELLED and $scrapercode==$updtid )
					{
						echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
						echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
					}
					else if ($update_status[$count]==ERROR and $scrapercode==$updtid )
					{
						//Since entry with 'error' status already exists, leave as is and inform user
						echo('Update of  '.$s.' database encountered error during previous execution.' . $nl);
						echo ('Please add the report manaully from Status page to ensure it runs. '. $nl);
					}
					else if ($update_status[$count]==RUNNING and $scrapercode==$updtid )
					{
						//No action if update is already running
						echo('Update of  '.$s.' database already running currently.' . $nl);
					}
					else
					{
						//Remove previous entry corresponding to completed update
						$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
						if($res==1)
							echo('Removed previous entry for '.$s.$nl);
							
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET update_items_progress=0, update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
					}
					$count++;
				}
				echo('Done checking for scheduled updates.' . $nl);
			}
			else
				echo('No new scheduled updates.' . $nl);
		
		
			//Check for newly scheduled reports and add to 'reports_status'
			if(count($schedule))
			{
				foreach($schedule as $item)
				{
					//Lastrun time in schedule set to current time, indicates update and reports in 
					//schedule taken care of
					$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
					mysql_query($query) or die('Bad SQL query setting lastrun in schedule. Error: '.mysql_error());
				}
			}
			/************************************ Step 2 ****************************************/
			
			
			/************************************ Step 3 ****************************************/
			echo($nl);
			//Get all data from 'update_status'
			$query = 'SELECT `update_id`,`status` FROM update_status';
			$res = mysql_query($query) or die('Bad SQL Query getting update_status');
			$update_status = array();
			$count=0;
			$all_updates_complete=1;
			while($row = mysql_fetch_assoc($res))
			{
			
				$update_status['id'.$count] = $row['update_id'];
				$update_status[$count++] = $row['status'];
				
			
				//Update flag which checks if all updates are complete
				if($row['status']!=COMPLETED)
					$all_updates_complete=0;
			}
				
			//No updates to run, move onto reports
			if(!count($update_status))
			{
				echo('No update scheduled.' . $nl);
			}
			//Step 2
			else if(in_array(RUNNING,$update_status))
			{
				echo('An update is currently running.' . $nl);
			}
			else if($all_updates_complete==1)
			{
				echo('All updates have been completed.' . $nl);
			}
			//Step 3
			else
			{
				//Search for entries in 'update_status' which are ready to run
				$query = 'SELECT * FROM update_status WHERE status='.READY;//.' OR status='.CANCELLED;
				$res = mysql_query($query) or die('Bad SQL Query getting update_status');
				while($row = mysql_fetch_assoc($res))
					$run_updates[] = $row;
				
				
				//Run updates for 'nct' and 'pubmed' one after the other in the current instance
				for($i=0;$i< count($run_updates);$i++)
				{
					//Set status to 'running' in 'update_status'
					$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
					$res1 = mysql_query($query) or die('Bad SQL Query setting update status to running');
					
					switch($run_updates[$i]['update_id'])
					{
						case 0:
						$updtname='nct';
						break;
						case 1:
						$updtname='eudract';
						break;
						case 2:
						$updtname='isrctn';
						break;
						case 3:
						$updtname='nct_new';
						break;
						case 9:
						$updtname='pubmed';
						break;
					}

						//Start the update execution
						if($updtname=='pubmed') $updtname='pm';
						if($updtname=='nct_new') $updtname='nct';
						$filename = 'fetch_' . $updtname . '.php';
						echo('Invoking: ' . $filename . '...</pre>' . $nl);
						$days_to_fetch=$run_updates[$i]['updated_days'];
						$update_id=$run_updates[$i]['update_id'];
						require_once($filename);
						run_incremental_scraper($days_to_fetch);
						echo($nl . '<pre>Done with ' . $filename . '.' . $nl);

						//Set status to 'complete' in 'update_status'
						$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now')).'",end_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
						$res2 = mysql_query($query) or die('Bad SQL Query setting update status to complete');
					
				}
			}
			
			/*********************************** Step 3 ***************************************/
			
			
			/*********************************** Step 4 ***************************************/
			echo($nl);
			
			//Refresh 'update_status'
			$query = 'SELECT `update_id`,`status` FROM update_status';
			$res = mysql_query($query) or die('Bad SQL Query getting update_status');
			$update_status = array();
			$count=0;
			
			while($row = mysql_fetch_assoc($res))
			{
				$update_status['id'.$count] = $row['update_id'];
				$update_status[$count++] = $row['status'];
			}
			
			/************************************ Step 4 ****************************************/
			posix_kill(getmypid(),1);
		}
	}
}
//1 current task has been found so it is selected to run in the current instance
elseif($current_tasks_count==1)
{
	mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
	mysql_select_db(DB_NAME) or die("Could not find database on server!");
	mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");	
	$selected_schedule_item=$currently_scheduled_tasks[0];
	echo ($nl."Schedule item ID selected for execution ".$selected_schedule_item.$nl.$nl);
		
	$now = strtotime('now');
	echo($nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
	
	//Keep checking in current process till all updates & reports are running/completed
	while(1)
	{			
		$pid = pcntl_fork();

		if($pid)
		{
			//Wait till child process completes execution/crashes
			pcntl_waitpid($pid, $status, WUNTRACED);
			if ($status==1)
			{
				echo ($nl."Continuing execution...".$nl.$nl);
				
			}
			else if ($status==2)
			{
				echo ($nl."Stopping execution.".$nl.$nl);
				echo('</pre>');
				die();
			}
			else
			{
				echo ($nl."Crash detected. Continuing execution skipping crashed item...".$nl.$nl);
			}
		}
		else
		{
			//Get the PID of child process
			$pid=getmypid();
			
			/************************************ Step 1 ****************************************/
			$now = strtotime('now');
			echo($nl . 'Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
			echo($nl);
			
			echo ('Checking for any updates or reports that have crashed..' . $nl);	
			//Get Process IDs of all currently running updates
			$query = 'SELECT `update_id`,`process_id` FROM update_status WHERE `status`='.RUNNING;
			$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
			$count_upids=0;
			while($row = mysql_fetch_assoc($res))
			{
				$update_ids[$count_upids] = $row['update_id'];
				$update_pids[$count_upids++] = $row['process_id'];
			}
			//Get list of all currently running 
			$cmd = "ps aux|grep php";
			exec($cmd, $output, $result);
			for($i=0;$i < count($output); $i++)
			{
				$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
				$exp_out=explode(" ",$output[$i]);
				$running_pids[$i]=$exp_out[1];
			}
			
			//Check if any update has terminated abruptly
			for($i=0;$i < $count_upids; $i++)
			{
				//If update_status is running and corresponding process ID is not running
				if(!in_array($update_pids[$i],$running_pids))
				{
					//Update status set to 'error'
					switch($update_ids[$i])
					{
						case 0:
						$updtname='nct';
						break;
						case 1:
						$updtname='eudract';
						break;
						case 2:
						$updtname='isrctn';
						break;
						case 3:
						$updtname='nct_new';
						break;
						case 9:
						$updtname='pubmed';
						break;
					}
					echo($updtname.' database updation error. Requeueing it.' . $nl);
					$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
					$res = mysql_query($query) or die('Bad SQL Query setting update error status');
				}
			}
			
			
			/************************************ Step 2 ****************************************/
			echo($nl);
			echo ('Checking schedule for updates and reports...=' . $nl);
			//Fetch schedule data 
			$schedule = array();
			$fetch = array();
			$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
			$res = mysql_query($query) or die('Bad SQL Query getting schedule');
			$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
			
			foreach($tasks as $row)
			{
				//Get time when scheduler item was last checked, in Unix time
				$lastrun = strtotime($row['lastrun']);
				//Read schedule of current item and convert to Unix time
				$hours = array();
				$days = array();
				for($power = 0; $power < 24; ++$power)
				{
					$hour = pow(2, $power);
					if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
				}
				
				for($power = 24; $power < 31; ++$power)
				{
					$day = pow(2, $power);
					if($row['runtimes'] & $day) $days[] = $alldays[$day];
				}
				
				$due = false;
				foreach($hours as $hour)
				{
					foreach($days as $day)
					{
						$sched = strtotime($day . $hour, $lastrun);
						$sched2 = strtotime('next ' . $day . $hour, $lastrun);
						if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
						{
							//Break if current item needs to be checked for updates/reports
							$due = true;
							break 2;
						}
					}
				}
				if($due)
				{
					//Get data of current item(which must be checked for updates/reports)
					$schedule[] = $row;
					if($row['fetch'] != 'none')
					{
						//Max number of previous days to check for new records for 
						// nct and pubmed database separately
						if($row['fetch'] == 'nct_new') $scrapercode=3; 
						elseif($row['fetch'] == 'pubmed') $scrapercode=9; 			
						else $scrapercode=0;
						if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
							$fetch[$row['fetch']] = $lastrun;
					}
				}
			}
			//Get all entries in 'update_status'
			$query = 'SELECT `update_id`,`status` FROM update_status';
			$res = mysql_query($query) or die('Bad SQL Query getting update_status');
			$update_status = array();
			$count=0;
			
			while($row = mysql_fetch_assoc($res))
			{
				$update_status['id'.$count] = $row['update_id'];
				$update_status[$count++] = $row['status'];
			}
			
			//Check if any updates(nct/pubmed) have been newly scheduled and add to update_status
			if(count($fetch))
			{
				$fetchers = $fetch;
				$count=0;
				
				foreach($fetchers as $s => $lastrun)
				{
					switch($s)
					{
						case 'nct':
						$updtid=0;
						break;
						case 'eudract':
						$updtid=1;
						break;
						case 'isrctn':
						$updtid=2;
						break;
						case 'nct_new':
						$updtid=3;
						break;
						case 'pubmed':
						$updtid=9;
						break;
					}
					
					if($update_status[$count]==COMPLETED and $scrapercode==$updtid )
					{
						//Remove previous entry corresponding to completed update
						$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
						if($res==1)
							echo('Removed previous entry for '.$s.$nl);
						
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						
						
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
					}
					else if ($update_status[$count]==READY and $scrapercode==$updtid )
					{
						//Since entry with 'ready' status already exists, update it retaining the state
					echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
					}
					else if ($update_status[$count]==CANCELLED and $scrapercode==$updtid )
					{
						echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
						echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
					}
					else if ($update_status[$count]==ERROR and $scrapercode==$updtid )
					{
						//Since entry with 'error' status already exists, leave as is and inform user
						echo('Update of  '.$s.' database encountered error during previous execution..' . $nl);
						echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
					}
					else if ($update_status[$count]==RUNNING and $scrapercode==$updtid )
					{
						//No action if update is already running
						echo('Update of  '.$s.' database already running currently.' . $nl);
					}
					else
					{
						//Remove previous entry corresponding to completed update
						$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
						if($res==1)
							echo('Removed previous entry for '.$s.$nl);
							
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						
						
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
					}
					$count++;
				}
				echo('Done checking for scheduled updates.' . $nl);
			}
			else
				echo('No new scheduled updates.' . $nl);
		
		
			//Check for newly scheduled reports and add to 'reports_status'
			if(count($schedule))
			{
				foreach($schedule as $item)
				{
					//Lastrun time in schedule set to current time, indicates update and reports in 
					//schedule taken care of
					$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
					mysql_query($query) or die('Bad SQL query setting lastrun in schedule. Error: '.mysql_error());
				}
			}
		
			
			/************************************ Step 3 ****************************************/
			echo($nl);
			//Get all data from 'update_status'
			$query = 'SELECT `update_id`,`status` FROM update_status';
			$res = mysql_query($query) or die('Bad SQL Query getting update_status');
			$update_status = array();
			$count=0;
			$all_updates_complete=1;
			while($row = mysql_fetch_assoc($res))
			{
				$update_status['id'.$count] = $row['update_id'];
				$update_status[$count++] = $row['status'];
			
				//Update flag which checks if all updates are complete
				if($row['status']!=COMPLETED)
					$all_updates_complete=0;
			}
				
			
			//No updates to run, move onto reports
			if(!count($update_status))
			{
				echo('No update scheduled.' . $nl);
			}
			//Step 2
			else if(in_array(RUNNING,$update_status))
			{
				echo('An update is currently running.' . $nl);
			}
			else if($all_updates_complete==1)
			{
				echo('All updates have been completed.' . $nl);
			}
			//Step 3
			else
			{
				//Search for entries in 'update_status' which are ready to run
				$query = 'SELECT * FROM update_status WHERE status='.READY;//.' OR status='.CANCELLED;
				$res = mysql_query($query) or die('Bad SQL Query getting update_status');
				while($row = mysql_fetch_assoc($res))
					$run_updates[] = $row;
				
				
				//Run updates for 'nct' and 'pubmed' one after the other in the current instance
				for($i=0;$i< count($run_updates);$i++)
				{
					//Set status to 'running' in 'update_status'
					$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
					$res1 = mysql_query($query) or die('Bad SQL Query setting update status to running');
					switch($run_updates[$i]['update_id'])
					{
						case 0:
						$updtname='nct';
						break;
						case 1:
						$updtname='eudract';
						break;
						case 2:
						$updtname='isrctn';
						break;
						case 3:
						$updtname='nct_new';
						break;
						case 9:
						$updtname='pubmed';
						break;
					}

					//Start the update execution
					if($updtname=='pubmed') $updtname='pm';
					if($updtname=='nct_new') $updtname='nct';
					$filename = 'fetch_' . $updtname . '.php';
					echo('Invoking ' . $filename . '...</pre>' . $nl);
					$days_to_fetch=$run_updates[$i]['updated_days'];
					$update_id=$run_updates[$i]['update_id'];
					require_once($filename);
					run_incremental_scraper($days_to_fetch);
					echo($nl . '<pre>Done with ' . $filename . '.' . $nl);

					//Set status to 'complete' in 'update_status'
					$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now')).'",end_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
					$res2 = mysql_query($query) or die('Bad SQL Query setting update status to complete');
					
				}
			}
			
			/*********************************** Step 3 ***************************************/
			
			
			/*********************************** Step 4 ***************************************/
			echo($nl);
			
			//Refresh 'update_status'
			$query = 'SELECT `update_id`,`status` FROM update_status';
			$res = mysql_query($query) or die('Bad SQL Query getting update_status');
			$update_status = array();
			$count=0;
			while($row = mysql_fetch_assoc($res))
			{
				$update_status['id'.$count] = $row['update_id'];
				$update_status[$count++] = $row['status'];
			}			
			
			/************************************ Step 4 ****************************************/
			posix_kill(getmypid(),1);
		}
	}
}
//more than 2 current tasks found, so the process is forked and the parent and child run one task each
elseif($current_tasks_count>1)
{
	$pid = pcntl_fork();
	if($pid)
	{
		if($current_tasks_count>2)
		{
			$pid = pcntl_fork();
			if($pid)
			{
				sleep(15);
				mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
				mysql_select_db(DB_NAME) or die("Could not find database on server!");
				mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");	
				//$nl=$nl."parent";
				$selected_schedule_item=$currently_scheduled_tasks[2];
				echo ($nl."Schedule item ID selected for execution ".$selected_schedule_item.$nl.$nl);
				$now = strtotime('now');
				echo($nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);		
				
				//Keep checking in current process till all updates & reports are running/completed
				while(1)
				{			
					$pid = pcntl_fork();

					if($pid)
					{
						//Wait till child process completes execution/crashes
						pcntl_waitpid($pid, $status, WUNTRACED);
						if ($status==1)
						{
							echo ($nl."Continuing execution...".$nl.$nl);
							
						}
						else if ($status==2)
						{
							echo ($nl."Stopping execution.".$nl.$nl);
							echo('</pre>');
							die();
						}
						else
						{
							echo ($nl."Crash detected. Continuing execution skipping crashed item...".$nl.$nl);
						}
					}
					else
					{
						//Get the PID of child process
						$pid=getmypid();
					
						
						/************************************ Step 1 ****************************************/
						$now = strtotime('now');
						echo($nl . 'Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
						echo($nl);
						
						echo ('Checking for any updates or reports that have crashed..' . $nl);	
						//Get Process IDs of all currently running updates
						$query = 'SELECT `update_id`,`process_id` FROM update_status WHERE `status`='.RUNNING;
						$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
						$count_upids=0;
						while($row = mysql_fetch_assoc($res))
						{
							$update_ids[$count_upids] = $row['update_id'];
							$update_pids[$count_upids++] = $row['process_id'];
						}
						
						//Get list of all currently running 
						$cmd = "ps aux|grep php";
						exec($cmd, $output, $result);
						for($i=0;$i < count($output); $i++)
						{
							$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
							$exp_out=explode(" ",$output[$i]);
							$running_pids[$i]=$exp_out[1];
						}
						
						//Check if any update has terminated abruptly
						for($i=0;$i < $count_upids; $i++)
						{
							//If update_status is running and corresponding process ID is not running
							if(!in_array($update_pids[$i],$running_pids))
							{
								switch($update_ids[$i])
								{
									case 0:
									$updtname='nct';
									break;
									case 1:
									$updtname='eudract';
									break;
									case 2:
									$updtname='isrctn';
									break;
									case 3:
									$updtname='nct_new';
									break;
									case 9:
									$updtname='pubmed';
									break;
								
								}
								//Update status set to 'error'
								echo($updtname.' database updation error. Requeueing it.' . $nl);
								$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
								$res = mysql_query($query) or die('Bad SQL Query setting update error status');
							}
						}
						
					
						
						/************************************ Step 2 ****************************************/
						echo($nl);
						echo ('Checking schedule for updates and reports..=.' . $nl);
						//Fetch schedule data 
						$schedule = array();
						$fetch = array();
						$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
						$res = mysql_query($query) or die('Bad SQL Query getting schedule');
						$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
						
						foreach($tasks as $row)
						{
							//Get time when scheduler item was last checked, in Unix time
							$lastrun = strtotime($row['lastrun']);
							//Read schedule of current item and convert to Unix time
							$hours = array();
							$days = array();
							for($power = 0; $power < 24; ++$power)
							{
								$hour = pow(2, $power);
								if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
							}
							
							for($power = 24; $power < 31; ++$power)
							{
								$day = pow(2, $power);
								if($row['runtimes'] & $day) $days[] = $alldays[$day];
							}
							
							$due = false;
							foreach($hours as $hour)
							{
								foreach($days as $day)
								{
									$sched = strtotime($day . $hour, $lastrun);
									$sched2 = strtotime('next ' . $day . $hour, $lastrun);
									if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
									{
										//Break if current item needs to be checked for updates/reports
										$due = true;
										break 2;
									}
								}
							}
							if($due)
							{
								//Get data of current item(which must be checked for updates/reports)
								$schedule[] = $row;
								if($row['fetch'] != 'none')
								{
								if($row['fetch'] == 'nct_new') $scrapercode=3; 
								elseif($row['fetch'] == 'pubmed') $scrapercode=9; 
								
								else $scrapercode=0;
									//Max number of previous days to check for new records for 
									// nct and pubmed database separately
									if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
										$fetch[$row['fetch']] = $lastrun;
								}
							}
						}
						//Get all entries in 'update_status'
						$query = 'SELECT `update_id`,`status` FROM update_status';
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						$update_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
						{
							$update_status['id'.$count] = $row['update_id'];
							$update_status[$count++] = $row['status'];
						}
						
						//Check if any updates(nct/pubmed) have been newly scheduled and add to update_status
						if(count($fetch))
						{
							$fetchers = $fetch;
							$count=0;
							foreach($fetchers as $s => $lastrun)
							{
								switch($s)
								{
									case 'nct':
									$updtid=0;
									break;
									case 'eudract':
									$updtid=1;
									break;
									case 'isrctn':
									$updtid=2;
									break;
									case 'nct_new':
									$updtid=3;
									break;
									case 'pubmed':
									$updtid=9;
									break;
								}
								if($update_status[$count]==COMPLETED and $scrapercode==$update_status['id'.$count] )
								{
									//Remove previous entry corresponding to completed update
									$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
									if($res==1)
										echo('Removed previous entry for '.$s.$nl);
									
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
								}
								else if ($update_status[$count]==READY and $scrapercode==$update_status['id'.$count] )
								{
									//Since entry with 'ready' status already exists, update it retaining the state
								echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
								}
								else if ($update_status[$count]==CANCELLED and $scrapercode==$update_status['id'.$count] )
								{
									echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
									echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
								}
								else if ($update_status[$count]==ERROR and $scrapercode==$update_status['id'.$count] )
								{
									//Since entry with 'error' status already exists, leave as is and inform user
									echo('Update of  '.$s.' database encountered error during previous execution...' . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs ...'. $nl);
								}
								else if ($update_status[$count]==RUNNING and $scrapercode==$update_status['id'.$count] )
								{
									//No action if update is already running
									echo('Update of  '.$s.' database already running currently.' . $nl);
								}
								else
								{
									//Remove previous entry corresponding to completed update
									$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
									if($res==1)
										echo('Removed previous entry for '.$s.$nl);
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
								}
								$count++;
							}
							echo('Done checking for scheduled updates.' . $nl);
						}
						else
							echo('No new scheduled updates.' . $nl);
					
					
						//Check for newly scheduled reports and add to 'reports_status'
						if(count($schedule))
						{
							foreach($schedule as $item)
							{
								//Lastrun time in schedule set to current time, indicates update and reports in 
								//schedule taken care of
								$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
								mysql_query($query) or die('Bad SQL query setting lastrun in schedule. Error: '.mysql_error());
							}
							echo('Done checking for scheduled reports.' . $nl);
						}
						/************************************ Step 2 ****************************************/
						
						
						/************************************ Step 3 ****************************************/
						echo($nl);
						//Get all data from 'update_status'
						$query = 'SELECT `update_id`,`status` FROM update_status';
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						$update_status = array();
						$count=0;
						$all_updates_complete=1;
						while($row = mysql_fetch_assoc($res))
						{
							
							$update_status['id'.$count] = $row['update_id'];
							$update_status[$count++] = $row['status'];
			
							//Update flag which checks if all updates are complete
							if($row['status']!=COMPLETED)
								$all_updates_complete=0;
						}
							
						//No updates to run, move onto reports
						if(!count($update_status))
						{
							echo('No update scheduled.' . $nl);
						}
						//Step 2
						else if(in_array(RUNNING,$update_status))
						{
							echo('An update is currently running.' . $nl);
						}
						else if($all_updates_complete==1)
						{
							echo('All updates have been completed.' . $nl);
						}
						//Step 3
						else
						{
							//Search for entries in 'update_status' which are ready to run
							$query = 'SELECT * FROM update_status WHERE status='.READY;//.' OR status='.CANCELLED;
							$res = mysql_query($query) or die('Bad SQL Query getting update_status');
							while($row = mysql_fetch_assoc($res))
								$run_updates[] = $row;
							
							
							//Run updates for 'nct' and 'pubmed' one after the other in the current instance
							for($i=0;$i< count($run_updates);$i++)
							{
								//Set status to 'running' in 'update_status'
								$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
								$res1 = mysql_query($query) or die('Bad SQL Query setting update status to running');
								
								//Start the update execution
								switch($run_updates[$i]['update_id'])
								{
									case 0:
									$updtname='nct';
									break;
									case 1:
									$updtname='eudract';
									break;
									case 2:
									$updtname='isrctn';
									break;
									case 3:
									$updtname='nct_new';
									break;
									case 9:
									$updtname='pubmed';
									break;
								}
								if($updtname=='pubmed') $updtname='pm';
								if($updtname=='nct_new') $updtname='nct';
								$filename = 'fetch_' . $updtname . '.php';
								echo('Invoking:- ' . $filename . '...</pre>' . $nl);
								$days_to_fetch=$run_updates[$i]['updated_days'];
								$update_id=$run_updates[$i]['update_id'];
								require_once($filename);
								run_incremental_scraper($days_to_fetch);
								echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
								
								//Set status to 'complete' in 'update_status'
								$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now')).'",end_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
								$res2 = mysql_query($query) or die('Bad SQL Query setting update status to complete');
								
							}
						}
						
						/*********************************** Step 3 ***************************************/
						
						
						/*********************************** Step 4 ***************************************/
						echo($nl);
						
						//Refresh 'update_status'
						$query = 'SELECT `update_id`,`status` FROM update_status';
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						$update_status = array();
						$count=0;

						while($row = mysql_fetch_assoc($res))
						{
							$update_status['id'.$count] = $row['update_id'];
							$update_status[$count++] = $row['status'];
						}
						
						/************************************ Step 4 ****************************************/
						posix_kill(getmypid(),1);
					}
				}			
			}
			else
			{
				sleep(1);
				mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
				mysql_select_db(DB_NAME) or die("Could not find database on server!");
				mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");	
				//$nl=$nl."child2";
				$selected_schedule_item=$currently_scheduled_tasks[1];
				echo ($nl."Schedule item ID selected for execution ".$selected_schedule_item.$nl.$nl);
				$now = strtotime('now');
				echo($nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);		
				
				//Keep checking in current process till all updates & reports are running/completed
				while(1)
				{			
					$pid = pcntl_fork();

					if($pid)
					{
						//Wait till child process completes execution/crashes
						pcntl_waitpid($pid, $status, WUNTRACED);
						if ($status==1)
						{
							echo ($nl."Continuing execution...".$nl.$nl);
							
						}
						else if ($status==2)
						{
							echo ($nl."Stopping execution.".$nl.$nl);
							echo('</pre>');
							die();
						}
						else
						{
							echo ($nl."Crash detected. Continuing execution skipping crashed item...".$nl.$nl);
						}
					}
					else
					{
						//Get the PID of child process
						$pid=getmypid();
						
						
						/************************************ Step 1 ****************************************/
						$now = strtotime('now');
						echo($nl . 'Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
						echo($nl);
						
						echo ('Checking for any updates or reports that have crashed..' . $nl);	
						//Get Process IDs of all currently running updates
						$query = 'SELECT `update_id`,`process_id` FROM update_status WHERE `status`='.RUNNING;
						$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
						$count_upids=0;
						while($row = mysql_fetch_assoc($res))
						{
							$update_ids[$count_upids] = $row['update_id'];
							$update_pids[$count_upids++] = $row['process_id'];
						}
						
						
						//Get list of all currently running 
						$cmd = "ps aux|grep php";
						exec($cmd, $output, $result);
						for($i=0;$i < count($output); $i++)
						{
							$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
							$exp_out=explode(" ",$output[$i]);
							$running_pids[$i]=$exp_out[1];
						}
						
						//Check if any update has terminated abruptly
						for($i=0;$i < $count_upids; $i++)
						{
							//If update_status is running and corresponding process ID is not running
							if(!in_array($update_pids[$i],$running_pids))
							{
								switch($update_ids[$i])
								{
									case 0:
									$updtname='nct';
									break;
									case 1:
									$updtname='eudract';
									break;
									case 2:
									$updtname='isrctn';
									break;
									case 3:
									$updtname='nct_new';
									break;
									case 9:
									$updtname='pubmed';
									break;
									
								}
								//Update status set to 'error'
								echo($updtname.' database updation error. Requeueing it.' . $nl);
								$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
								$res = mysql_query($query) or die('Bad SQL Query setting update error status');
							}
						}
						
						/************************************ Step 2 ****************************************/
						echo($nl);
						echo ('Checking schedule for updates and reports...*' . $nl);
						//Fetch schedule data 
						$schedule = array();
						$fetch = array();
						$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
						$res = mysql_query($query) or die('Bad SQL Query getting schedule');
						$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
						
						foreach($tasks as $row)
						{
							//Get time when scheduler item was last checked, in Unix time
							$lastrun = strtotime($row['lastrun']);
							//Read schedule of current item and convert to Unix time
							$hours = array();
							$days = array();
							for($power = 0; $power < 24; ++$power)
							{
								$hour = pow(2, $power);
								if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
							}
							
							for($power = 24; $power < 31; ++$power)
							{
								$day = pow(2, $power);
								if($row['runtimes'] & $day) $days[] = $alldays[$day];
							}
							
							$due = false;
							foreach($hours as $hour)
							{
								foreach($days as $day)
								{
									$sched = strtotime($day . $hour, $lastrun);
									$sched2 = strtotime('next ' . $day . $hour, $lastrun);
									if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
									{
										//Break if current item needs to be checked for updates/reports
										$due = true;
										break 2;
									}
								}
							}
							if($due)
							{
								//Get data of current item(which must be checked for updates/reports)
								$schedule[] = $row;
								if($row['fetch'] != 'none')
								{
								if($row['fetch'] == 'nct_new') $scrapercode=3; 
								elseif($row['fetch'] == 'pubmed') $scrapercode=9;
								else $scrapercode=0;
									//Max number of previous days to check for new records for 
									// nct and pubmed database separately
									if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
										$fetch[$row['fetch']] = $lastrun;
								}
							}
						}
						//Get all entries in 'update_status'
						$query = 'SELECT `update_id`,`status` FROM update_status';
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						$update_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
						{
							$update_status['id'.$count] = $row['update_id'];
							$update_status[$count++] = $row['status'];
						}
						
						//Check if any updates(nct/pubmed) have been newly scheduled and add to update_status
						if(count($fetch))
						{
							$fetchers = $fetch;
							$count=0;
							foreach($fetchers as $s => $lastrun)
							{
								switch($s)
								{
									case 'nct':
									$updtid=0;
									break;
									case 'eudract':
									$updtid=1;
									break;
									case 'isrctn':
									$updtid=2;
									break;
									case 'nct_new':
									$updtid=3;
									break;
									case 'pubmed':
									$updtid=9;
									break;
									
								}
								if($update_status[$count]==COMPLETED and $scrapercode==$update_status['id'.$count] )
								{
									//Remove previous entry corresponding to completed update
									$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
									if($res==1)
										echo('Removed previous entry for '.$s.$nl);
									
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status  SET update_items_progress="0",  update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
								}
								else if ($update_status[$count]==READY and $scrapercode==$update_status['id'.$count] )
								{
									//Since entry with 'ready' status already exists, update it retaining the state
								echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
								}
								else if ($update_status[$count]==CANCELLED and $scrapercode==$update_status['id'.$count] )
								{
									echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
									echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
								}
								else if ($update_status[$count]==ERROR and $scrapercode==$update_status['id'.$count] )
								{
									//Since entry with 'error' status already exists, leave as is and inform user
									echo('Update of  '.$s.' database encountered error during previous execution....' . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs-- '. $nl);
								}
								else if ($update_status[$count]==RUNNING and $scrapercode==$update_status['id'.$count] )
								{
									//No action if update is already running
									echo('Update of  '.$s.' database already running currently.' . $nl);
								}
								else
								{
								//Remove previous entry corresponding to completed update
								$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
								$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
								if($res==1)
									echo('Removed previous entry for '.$s.$nl);
									
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
								}
								$count++;
							}
							echo('Done checking for scheduled updates.' . $nl);
						}
						else
							echo('No new scheduled updates.' . $nl);
					
					
						//Check for newly scheduled reports and add to 'reports_status'
						if(count($schedule))
						{
							foreach($schedule as $item)
							{
								//Lastrun time in schedule set to current time, indicates update and reports in 
								//schedule taken care of
								$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
								mysql_query($query) or die('Bad SQL query setting lastrun in schedule. Error: '.mysql_error());
								echo('Checking for reports for item ' . $item['id'] .' - '.$item['name']. $nl);
								
								
						/************************************ Step 2 ****************************************/
						
						
						/************************************ Step 3 ****************************************/
						echo($nl);
						//Get all data from 'update_status'
						$query = 'SELECT `update_id`,`status` FROM update_status';
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						$update_status = array();
						$count=0;
						$all_updates_complete=1;
						while($row = mysql_fetch_assoc($res))
						{
							$update_status['id'.$count] = $row['update_id'];
							$update_status[$count++] = $row['status'];
			
							//Update flag which checks if all updates are complete
							if($row['status']!=COMPLETED)
								$all_updates_complete=0;
						}
							
												
						//No updates to run, move onto reports
						if(!count($update_status))
						{
							echo('No update scheduled.' . $nl);
						}
						//Step 2
						else if(in_array(RUNNING,$update_status))
						{
							echo('An update is currently running.' . $nl);
						}
						else if($all_updates_complete==1)
						{
							echo('All updates have been completed.' . $nl);
						}
						//Step 3
						else
						{
							//Search for entries in 'update_status' which are ready to run
							$query = 'SELECT * FROM update_status WHERE status='.READY;//.' OR status='.CANCELLED;
							$res = mysql_query($query) or die('Bad SQL Query getting update_status');
							while($row = mysql_fetch_assoc($res))
								$run_updates[] = $row;
							
							
							//Run updates for 'nct' and 'pubmed' one after the other in the current instance
							for($i=0;$i< count($run_updates);$i++)
							{
								//Set status to 'running' in 'update_status'
								$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
								$res1 = mysql_query($query) or die('Bad SQL Query setting update status to running');
								
								switch($run_updates[$i]['update_id'])
								{
									case 0:
									$updtname='nct';
									break;
									case 1:
									$updtname='eudract';
									break;
									case 2:
									$updtname='isrctn';
									break;
									case 3:
									$updtname='nct_new';
									break;
									case 9:
									$updtname='pubmed';
									break;
								
								}

									//Start the update execution
									if($updtname=='pubmed') $updtname='pm';
									if($updtname=='nct_new') $updtname='nct';
									$filename = 'fetch_' . $updtname . '.php';
									echo('Invoking.- ' . $filename . '...</pre>' . $nl);
									$days_to_fetch=$run_updates[$i]['updated_days'];
									$update_id=$run_updates[$i]['update_id'];
									require_once($filename);
									run_incremental_scraper($days_to_fetch);
									echo($nl . '<pre>Done with ' . $filename . '.' . $nl);

									//Set status to 'complete' in 'update_status'
									$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now')).'",end_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
									$res2 = mysql_query($query) or die('Bad SQL Query setting update status to complete');
								
							}
						}
						
						/*********************************** Step 3 ***************************************/
						
												
						/*********************************** Step 4 ***************************************/
						echo($nl);
						
						//Refresh 'update_status'
						$query = 'SELECT `update_id`,`status` FROM update_status';
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						$update_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
						{
							$update_status['id'.$count] = $row['update_id'];
							$update_status[$count++] = $row['status'];
						}						
						
					
						/************************************ Step 4 ****************************************/
						posix_kill(getmypid(),1);
					}
				}			
			}
		}
		if(1)
		{
			sleep(1);
			mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
			mysql_select_db(DB_NAME) or die("Could not find database on server!");
			mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");	
			//$nl=$nl."parent";
			$selected_schedule_item=$currently_scheduled_tasks[1];
			echo ($nl."Schedule item ID selected for execution ".$selected_schedule_item.$nl.$nl);
			$now = strtotime('now');
			echo($nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);		
			
			//Keep checking in current process till all updates & reports are running/completed
			while(1)
			{			
				$pid = pcntl_fork();

				if($pid)
				{
					//Wait till child process completes execution/crashes
					pcntl_waitpid($pid, $status, WUNTRACED);
					if ($status==1)
					{
						echo ($nl."Continuing execution...".$nl.$nl);
						
					}
					else if ($status==2)
					{
						echo ($nl."Stopping execution.".$nl.$nl);
						echo('</pre>');
						die();
					}
					else
					{
						echo ($nl."Crash detected. Continuing execution skipping crashed item...".$nl.$nl);
					}
				}
				else
				{
					//Get the PID of child process
					$pid=getmypid();
					
					
					/************************************ Step 1 ****************************************/
					$now = strtotime('now');
					echo($nl . 'Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
					echo($nl);
					
					echo ('Checking for any updates or reports that have crashed..' . $nl);	
					//Get Process IDs of all currently running updates
					$query = 'SELECT `update_id`,`process_id` FROM update_status WHERE `status`='.RUNNING;
					$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
					$count_upids=0;
					while($row = mysql_fetch_assoc($res))
					{
						$update_ids[$count_upids] = $row['update_id'];
						$update_pids[$count_upids++] = $row['process_id'];
					}
					
					
					//Get list of all currently running 
					$cmd = "ps aux|grep php";
					exec($cmd, $output, $result);
					for($i=0;$i < count($output); $i++)
					{
						$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
						$exp_out=explode(" ",$output[$i]);
						$running_pids[$i]=$exp_out[1];
					}
					
					//Check if any update has terminated abruptly
					for($i=0;$i < $count_upids; $i++)
					{
						//If update_status is running and corresponding process ID is not running
						if(!in_array($update_pids[$i],$running_pids))
						{
							switch($update_ids[$i])
							{
								case 0:
								$updtname='nct';
								break;
								case 1:
								$updtname='eudract';
								break;
								case 2:
								$updtname='isrctn';
								break;
								case 3:
								$updtname='nct_new';
								break;
								case 9:
								$updtname='pubmed';
								break;
								
							}
							//Update status set to 'error'
							echo($updtname.' database updation error. Requeueing it.' . $nl);
							$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
							$res = mysql_query($query) or die('Bad SQL Query setting update error status');
						}
					}
					
					
					/************************************ Step 2 ****************************************/
					echo($nl);
					echo ('Checking schedule for updates and reports/...' . $nl);
					//Fetch schedule data 
					$schedule = array();
					$fetch = array();
					$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
					$res = mysql_query($query) or die('Bad SQL Query getting schedule');
					$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
					
					foreach($tasks as $row)
					{
						//Get time when scheduler item was last checked, in Unix time
						$lastrun = strtotime($row['lastrun']);
						//Read schedule of current item and convert to Unix time
						$hours = array();
						$days = array();
						for($power = 0; $power < 24; ++$power)
						{
							$hour = pow(2, $power);
							if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
						}
						
						for($power = 24; $power < 31; ++$power)
						{
							$day = pow(2, $power);
							if($row['runtimes'] & $day) $days[] = $alldays[$day];
						}
						
						$due = false;
						foreach($hours as $hour)
						{
							foreach($days as $day)
							{
								$sched = strtotime($day . $hour, $lastrun);
								$sched2 = strtotime('next ' . $day . $hour, $lastrun);
								if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
								{
									//Break if current item needs to be checked for updates/reports
									$due = true;
									break 2;
								}
							}
						}
						if($due)
						{
							//Get data of current item(which must be checked for updates/reports)
							$schedule[] = $row;
							if($row['fetch'] != 'none')
							{
							if($row['fetch'] == 'nct_new') $scrapercode=3; 
							elseif($row['fetch'] == 'pubmed') $scrapercode=9;
							else $scrapercode=0;
								//Max number of previous days to check for new records for 
								// nct and pubmed database separately
								if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
									$fetch[$row['fetch']] = $lastrun;
							}
						}
					}
					//Get all entries in 'update_status'
					$query = 'SELECT `update_id`,`status` FROM update_status';
					$res = mysql_query($query) or die('Bad SQL Query getting update_status');
					$update_status = array();
					$count=0;
					while($row = mysql_fetch_assoc($res))
					{
						$update_status['id'.$count] = $row['update_id'];
						$update_status[$count++] = $row['status'];
					}
					
					//Check if any updates(nct/pubmed) have been newly scheduled and add to update_status
					if(count($fetch))
					{
						$fetchers = $fetch;
						$count=0;
						foreach($fetchers as $s => $lastrun)
						{
							switch($s)
							{
								case 'nct':
								$updtid=0;
								break;
								case 'eudract':
								$updtid=1;
								break;
								case 'isrctn':
								$updtid=2;
								break;
								case 'nct_new':
								$updtid=3;
								break;
								case 'pubmed':
								$updtid=9;
								break;
							}
							if($update_status[$count]==COMPLETED and $scrapercode==$update_status['id'.$count] )
							{
								//Remove previous entry corresponding to completed update
								$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
								$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
								if($res==1)
									echo('Removed previous entry for '.$s.$nl);
								
								//Add new entry with status ready
								echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
								$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
								
								
							$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
							}
							else if ($update_status[$count]==READY and $scrapercode==$update_status['id'.$count] )
							{
								//Since entry with 'ready' status already exists, update it retaining the state
							echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
								$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid.'"';
							$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
							}
							else if ($update_status[$count]==CANCELLED and $scrapercode==$update_status['id'.$count] )
							{
								echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
								echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
							}
							else if ($update_status[$count]==ERROR and $scrapercode==$update_status['id'.$count] )
							{
								//Since entry with 'error' status already exists, leave as is and inform user
								echo('Update of  '.$s.' database encountered error during previous execution.....' . $nl);
								echo ('Please add the report manaully from Status page to ensure it runs.  - '. $nl);
							}
							else if ($update_status[$count]==RUNNING and $scrapercode==$update_status['id'.$count] )
							{
								//No action if update is already running
								echo('Update of  '.$s.' database already running currently.' . $nl);
							}
							else
							{
								//Remove previous entry corresponding to completed update
								$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
								$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
								if($res==1)
									echo('Removed previous entry for '.$s.$nl);
								//Add new entry with status ready
								echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
								$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
								
								
								$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
							}
							$count++;
						}
						echo('Done checking for scheduled updates.' . $nl);
					}
					else
						echo('No new scheduled updates.' . $nl);
				
				
					//Check for newly scheduled reports and add to 'reports_status'
					if(count($schedule))
					{
						foreach($schedule as $item)
						{
							//Lastrun time in schedule set to current time, indicates update and reports in 
							//schedule taken care of
							$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
							mysql_query($query) or die('Bad SQL query setting lastrun in schedule. Error: '.mysql_error());
							echo('Checking for reports for item ' . $item['id'] .' - '.$item['name']. $nl);
						}
					}
					/************************************ Step 2 ****************************************/
					
					
					/************************************ Step 3 ****************************************/
					echo($nl);
					//Get all data from 'update_status'
					$query = 'SELECT `update_id`,`status` FROM update_status';
					$res = mysql_query($query) or die('Bad SQL Query getting update_status');
					$update_status = array();
					$count=0;
					$all_updates_complete=1;
					while($row = mysql_fetch_assoc($res))
					{
						$update_status['id'.$count] = $row['update_id'];
						$update_status[$count++] = $row['status'];
					
						//Update flag which checks if all updates are complete
						if($row['status']!=COMPLETED)
							$all_updates_complete=0;
					}
					
					//No updates to run, move onto reports
					if(!count($update_status))
					{
						echo('No update scheduled.' . $nl);
					}
					//Step 2
					else if(in_array(RUNNING,$update_status))
					{
						echo('An update is currently running.' . $nl);
					}
					else if($all_updates_complete==1)
					{
						echo('All updates have been completed.' . $nl);
					}
					//Step 3
					else
					{
						//Search for entries in 'update_status' which are ready to run
						$query = 'SELECT * FROM update_status WHERE status='.READY;//.' OR status='.CANCELLED;
						$res = mysql_query($query) or die('Bad SQL Query getting update_status');
						while($row = mysql_fetch_assoc($res))
							$run_updates[] = $row;
						
						
						//Run updates for 'nct' and 'pubmed' one after the other in the current instance
						for($i=0;$i< count($run_updates);$i++)
						{
							//Set status to 'running' in 'update_status'
							$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
							$res1 = mysql_query($query) or die('Bad SQL Query setting update status to running');
							
							//Start the update execution
							switch($run_updates[$i]['update_id'])
							{
								case 0:
								$updtname='nct';
								break;
								case 1:
								$updtname='eudract';
								break;
								case 2:
								$updtname='isrctn';
								break;
								case 3:
								$updtname='nct_new';
								break;
								case 9:
								$updtname='pubmed';
								break;
								
							}
							if($updtname=='pubmed') $updtname='pm';
							if($updtname=='nct_new') $updtname='nct';
							$filename = 'fetch_' . $updtname . '.php';
							echo('Invoking-: ' . $filename . '...</pre>' . $nl);
							$days_to_fetch=$run_updates[$i]['updated_days'];
							$update_id=$run_updates[$i]['update_id'];
							require_once($filename);
							run_incremental_scraper($days_to_fetch);
							echo($nl . '<pre>Done with ' . $filename . '.' . $nl);

							//Set status to 'complete' in 'update_status'
							$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now')).'",end_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
							$res2 = mysql_query($query) or die('Bad SQL Query setting update status to complete');
							
						}
					}
					
					/*********************************** Step 3 ***************************************/
					
											
					/*********************************** Step 4 ***************************************/
					echo($nl);
					
					//Refresh 'update_status'
					$query = 'SELECT `update_id`,`status` FROM update_status';
					$res = mysql_query($query) or die('Bad SQL Query getting update_status');
					$update_status = array();
					$count=0;
					while($row = mysql_fetch_assoc($res))
					{
						$update_status['id'.$count] = $row['update_id'];
						$update_status[$count++] = $row['status'];
					}
					
					/************************************ Step 4 ****************************************/
					posix_kill(getmypid(),1);
				}
			}
		}
	}
	if(1)
	{
		sleep(1);
		mysql_connect(DB_SERVER,DB_USER,DB_PASS) or die("Error connecting to database server!");
		mysql_select_db(DB_NAME) or die("Could not find database on server!");
		mysql_query('SET SESSION group_concat_max_len = 1000000') or die("Couldn't set group_concat_max_len");
		//$nl=$nl."child1";
		$selected_schedule_item=$currently_scheduled_tasks[0];
		echo ($nl."Schedule item ID selected for execution ".$selected_schedule_item.$nl.$nl);
		$now = strtotime('now');
		echo($nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);		
		
		//Keep checking in current process till all updates & reports are running/completed
		while(1)
		{
			$pid = pcntl_fork();

			if($pid)
			{
				//Wait till child process completes execution/crashes
				pcntl_waitpid($pid, $status, WUNTRACED);
				if ($status==1)
				{
					echo ($nl."Continuing execution...".$nl.$nl);
					
				}
				else if ($status==2)
				{
					echo ($nl."Stopping execution.".$nl.$nl);
					echo('</pre>');
					die();
				}
				else
				{
					echo ($nl."Crash detected. Continuing execution skipping crashed item...".$nl.$nl);
				}
			}
			else
			{
				//Get the PID of child process
				$pid=getmypid();
				
				
				/************************************ Step 1 ****************************************/
				$now = strtotime('now');
				echo($nl . 'Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
				echo($nl);
				
				echo ('Checking for any updates or reports that have crashed..' . $nl);	
				//Get Process IDs of all currently running updates
				$query = 'SELECT `update_id`,`process_id` FROM update_status WHERE `status`='.RUNNING;
				$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
				$count_upids=0;
				while($row = mysql_fetch_assoc($res))
				{
					$update_ids[$count_upids] = $row['update_id'];
					$update_pids[$count_upids++] = $row['process_id'];
				}
				
				
				//Get list of all currently running 
				$cmd = "ps aux|grep php";
				exec($cmd, $output, $result);
				for($i=0;$i < count($output); $i++)
				{
					$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
					$exp_out=explode(" ",$output[$i]);
					$running_pids[$i]=$exp_out[1];
				}
				
				//Check if any update has terminated abruptly
				for($i=0;$i < $count_upids; $i++)
				{
					//If update_status is running and corresponding process ID is not running
					if(!in_array($update_pids[$i],$running_pids))
					{
						switch($update_ids[$i])
						{
							case 0:
							$updtname='nct';
							break;
							case 1:
							$updtname='eudract';
							break;
							case 2:
							$updtname='isrctn';
							break;
							case 3:
							$updtname='nct_new';
							break;
							case 9:
							$updtname='pubmed';
							break;
						}
						//Update status set to 'error'
						echo($updtname.' database updation error. Requeueing it.' . $nl);
						$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
						$res = mysql_query($query) or die('Bad SQL Query setting update error status');
					}
				}
				
				
				/************************************ Step 2 ****************************************/
				echo($nl);
				echo ('Checking schedule for updates and reports...7' . $nl);
				//Fetch schedule data 
				$schedule = array();
				$fetch = array();
				$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
				$res = mysql_query($query) or die('Bad SQL Query getting schedule');
				$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
				$scrapercode=array();
				foreach($tasks as $row)
				{
					//Get time when scheduler item was last checked, in Unix time
					$lastrun = strtotime($row['lastrun']);
					//Read schedule of current item and convert to Unix time
					$hours = array();
					$days = array();
					for($power = 0; $power < 24; ++$power)
					{
						$hour = pow(2, $power);
						if($row['runtimes'] & $hour) $hours[] = $allhours[$hour];
					}
					
					for($power = 24; $power < 31; ++$power)
					{
						$day = pow(2, $power);
						if($row['runtimes'] & $day) $days[] = $alldays[$day];
					}
					
					$due = false;
					foreach($hours as $hour)
					{
						foreach($days as $day)
						{
							$sched = strtotime($day . $hour, $lastrun);
							$sched2 = strtotime('next ' . $day . $hour, $lastrun);
							if(($lastrun < $sched && $sched < $now) || ($lastrun < $sched2 && $sched2 < $now))
							{
								//Break if current item needs to be checked for updates/reports
								$due = true;
								break 2;
							}
						}
					}
					
					if($due)
					{
						//Get data of current item(which must be checked for updates/reports)
						$schedule[] = $row;
						if($row['fetch'] != 'none')
						{
						
						if($row['fetch'] == 'nct_new') $scrapercode[]=3; 
						elseif($row['fetch'] == 'pubmed') $scrapercode[]=9; 
						else $scrapercode[]=0;
							//Max number of previous days to check for new records for 
							// nct and pubmed database separately
							if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
								$fetch[$row['fetch']] = $lastrun;
						}
					}
				}
				//Get all entries in 'update_status'
				$query = 'SELECT `update_id`,`status` FROM update_status';
				$res = mysql_query($query) or die('Bad SQL Query getting update_status');
				$update_status = array();
				$count=0;
				while($row = mysql_fetch_assoc($res))
				{
					$update_status['id'.$count] = $row['update_id'];
					$update_status[$count++] = $row['status'];
				}
				
				//Check if any updates(nct/pubmed) have been newly scheduled and add to update_status
				if(count($fetch))
				{
					$fetchers = $fetch;
					$count=0;
					foreach($fetchers as $s => $lastrun)
					{
						switch($s)
						{
							case 'nct':
							$updtid=0;
							break;
							case 'eudract':
							$updtid=1;
							break;
							case 'isrctn':
							$updtid=2;
							break;
							case 'nct_new':
							$updtid=3;
							break;
							case 'pubmed':
							$updtid=9;
							break;
							
							
						}
					//	echo '<br>scf code=' . $scrapercode .  'updtid' . $updtid . 'updt st id' . $update_status['id'.$count] .'<br>';
						pr($scrapercode);
						if($update_status[$count]==COMPLETED and $scrapercode[$count]==$updtid )
						{
							//Remove previous entry corresponding to completed update
							$query = 'DELETE FROM update_status WHERE update_id="' . $updtid.'"';
							$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
							if($res==1)
								echo('Removed previous entry for '.$s.$nl);
							
							//Add new entry with status ready
							echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
							$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
							
							
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
						}
						else if ($update_status[$count]==READY and $scrapercode[$count]==$updtid )
						{
							//Since entry with 'ready' status already exists, update it retaining the state
						echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
							$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid.'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
						}
						else if ($update_status[$count]==CANCELLED and $scrapercode[$count]==$updtid )
						{
							echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
							echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
						}
						else if ($update_status[$count]==ERROR and $scrapercode[$count]==$updtid )
						{
							//Since entry with 'error' status already exists, leave as is and inform user
							echo('Update of  '.$s.' database encountered error during previous execution......' . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs--. '. $nl);
						}
						else if ($update_status[$count]==RUNNING and $scrapercode[$count]==$updtid )
						{
							//No action if update is already running
							echo('Update of  '.$s.' database already running currently.' . $nl);
						}
						else
						{
							//Remove previous entry corresponding to completed update
							$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
							$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
							if($res==1)
								echo('Removed previous entry for '.$s.$nl);
							
							//Add new entry with status ready
							echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
							$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
							
							
							$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error() . 'Query:' . $query);
						}
						$count++;
					}
					echo('Done checking for scheduled updates.' . $nl);
				}
				else
					echo('No new scheduled updates.' . $nl);
			
			
				//Check for newly scheduled reports and add to 'reports_status'
				if(count($schedule))
				{
					foreach($schedule as $item)
					{
						//Lastrun time in schedule set to current time, indicates update and reports in 
						//schedule taken care of
						$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",strtotime('now')) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
						mysql_query($query) or die('Bad SQL query setting lastrun in schedule. Error: '.mysql_error());
						echo('Checking for reports for item ' . $item['id'] .' - '.$item['name']. $nl);
						
						
						
					}
				}

				/************************************ Step 2 ****************************************/
				
				
				/************************************ Step 3 ****************************************/
				echo($nl);
				//Get all data from 'update_status'
				$query = 'SELECT `update_id`,`status` FROM update_status';
				$res = mysql_query($query) or die('Bad SQL Query getting update_status');
				$update_status = array();
				$count=0;
				$all_updates_complete=1;
				while($row = mysql_fetch_assoc($res))
				{
					$update_status['id'.$count] = $row['update_id'];
					$update_status[$count++] = $row['status'];
				
					//Update flag which checks if all updates are complete
					if($row['status']!=COMPLETED)
						$all_updates_complete=0;
				}
					
				
				//No updates to run, move onto reports
				if(!count($update_status))
				{
					echo('No update scheduled.' . $nl);
				}
				//Step 2
				else if(in_array(RUNNING,$update_status))
				{
					echo('An update is currently running.' . $nl);
				}
				else if($all_updates_complete==1)
				{
					echo('All updates have been completed.' . $nl);
				}
				//Step 3
				else
				{
					//Search for entries in 'update_status' which are ready to run
					$query = 'SELECT * FROM update_status WHERE status='.READY;//.' OR status='.CANCELLED;
					$res = mysql_query($query) or die('Bad SQL Query getting update_status');
					while($row = mysql_fetch_assoc($res))
						$run_updates[] = $row;
					
					
					//Run updates for 'nct' and 'pubmed' one after the other in the current instance
					for($i=0;$i< count($run_updates);$i++)
					{
						//Set status to 'running' in 'update_status'
						$query = 'UPDATE update_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
						$res1 = mysql_query($query) or die('Bad SQL Query setting update status to running');
						switch($run_updates[$i]['update_id'])
						{
							case 0:
							$updtname='nct';
							break;
							case 1:
							$updtname='eudract';
							break;
							case 2:
							$updtname='isrctn';
							break;
							case 3:
							$updtname='nct_new';
							break;
							case 9:
							$updtname='pubmed';
							break;
						}

							//Start the update execution
							if($updtname=='pubmed') $updtname='pm';
							if($updtname=='nct_new') $updtname='nct';
							$filename = 'fetch_' . $updtname . '.php';
							echo('Invoking:: ' . $filename . '...</pre>' . $nl);
							$days_to_fetch=$run_updates[$i]['updated_days'];
							$update_id=$run_updates[$i]['update_id'];
							require_once($filename);
							run_incremental_scraper($days_to_fetch);
							echo($nl . '<pre>Done with ' . $filename . '.' . $nl);

							//Set status to 'complete' in 'update_status'
							$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now')).'",end_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE update_id="' .$run_updates[$i]['update_id'] .'"';
							$res2 = mysql_query($query) or die('Bad SQL Query setting update status to complete');
						
					}
				}
				
				/*********************************** Step 3 ***************************************/
				
				
				
				/*********************************** Step 4 ***************************************/
				echo($nl);
				
				//Refresh 'update_status'
				$query = 'SELECT `update_id`,`status` FROM update_status';
				$res = mysql_query($query) or die('Bad SQL Query getting update_status');
				$update_status = array();
				$count=0;
				while($row = mysql_fetch_assoc($res))
				{
					$update_status['id'.$count] = $row['update_id'];
					$update_status[$count++] = $row['status'];
				}
				
				
				/************************************ Step 4 ****************************************/
				posix_kill(getmypid(),1);
			}
		}
	}
}
}
}
/************************************ Step B ****************************************/
?>