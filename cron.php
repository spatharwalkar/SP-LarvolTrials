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

//Mapping of type code to name
$rtype_name=array(0=>"Heatmap","Updatescan");

require_once('db.php');
require_once('run_updatereport.php');
require_once('run_heatmap.php');

require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once('class.phpmailer.php');

//variables used for running report
global $run_id;
global $report_type;
global $type_id;

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
echo ('<pre>Checking schedule for updates and reports...' . $nl);
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
		$schedule_tasks[]=$row['id'];
		if($row['fetch'] != 'none')
		{
			//Max number of previous days to check for new records for 
			// nct and pubmed database separately
			if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
				$fetch[$row['fetch']] = $lastrun;
		}
	}
}

$currently_scheduled_tasks=$schedule_tasks;

$current_tasks_count=count($currently_scheduled_tasks);

echo ($current_tasks_count." new tasks found.".$nl);

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
		//Get all data from 'reports_status'
		$query = 'SELECT DISTINCT `run_id` FROM reports_status';
		$res = mysql_query($query) or die('Bad SQL Query getting run_id from reports_status');
		$run_id_list = array();
		$count=0;
		while($row = mysql_fetch_assoc($res))
			$run_id_list[$count++] = $row['run_id'];
			
		for($i=0;$i < count($run_id_list);$i++)
		{
			$query = 'SELECT `status` FROM reports_status WHERE run_id='.$run_id_list[$i];
			$res = mysql_query($query) or die('Bad SQL Query getting status from reports_status');
			$ready_status_count[$i]=0;
			$running_status_count[$i]=0;
			while($row = mysql_fetch_assoc($res))
			{
				if($row['status']==READY)
					$ready_status_count[$i]++;
				else if($row['status']==RUNNING)
					$running_status_count[$i]++;
			}
		}
		$schedule_item_found=0;
		for($i=0;$i < ($max_process_per_item);$i++)
		{
			for($j=0;$j < count($run_id_list);$j++)
			{
				if($ready_status_count[$j]>0&&$running_status_count[$j]==$i)
				{
					$selected_schedule_item=$run_id_list[$j];
					$schedule_item_found=1;
					echo ($nl."Schedule item ID selected for execution ".$selected_schedule_item.$nl.$nl);
					$now = strtotime('now');
					echo($nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl);
					break 2;
				}
			}
		}
		
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
			
			//Get Process IDs of all currently running reports
			$query = 'SELECT `run_id`,`type_id`,`report_type`,`process_id` FROM reports_status WHERE `status`='.RUNNING;
			$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
			$count_rpids=0;
			while($row = mysql_fetch_assoc($res))
			{
				$report_run_ids[$count_rpids] = $row['run_id'];
				$report_typ_ids[$count_rpids] = $row['type_id'];
				$report_rpt_typ[$count_rpids] = $row['report_type'];
				$report_pids[$count_rpids++] = $row['process_id'];
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
						case 4:
						$updtname='calc_hm_cells'; 
						break;
					}
					//Update status set to 'error'
					echo($updtname  .' database updation error. Requeueing it.' . $nl);
					$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
					$res = mysql_query($query) or die('Bad SQL Query setting update error status');
				}
			}
			
			//Check if any report has terminated abruptly
			for($i=0;$i < $count_rpids; $i++)
			{
				//If report_status is running and corresponding process ID is not running
				if(!in_array($report_pids[$i],$running_pids))
				{
					//Report status set to 'error'
					echo('Item ID '.$report_run_ids[$i].' - '.$rtype_name[$report_rpt_typ[$i]].' - '.$report_typ_ids[$i].' error. Requeueing it.' . $nl);
					$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
					$res = mysql_query($query) or die('Bad SQL Query setting report error status');
				}
			}
			/************************************ Step 1 ****************************************/			
			
			
			/************************************ Step 2 ****************************************/
			echo($nl);
			echo ('Checking schedule for updates and reports...' . $nl);
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
				$update_status[$count++] = $row['status'];
			
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
						case 'calc_hm_cells':
						$updtid=4;
						break;
					}
					if($update_status[$count]==COMPLETED)
					{
						//Remove previous entry corresponding to completed update
						$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
						if($res==1)
							echo('Removed previous entry for '.$s.$nl);
						
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET update_items_progress=0,  update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
					}
					else if ($update_status[$count]==READY)
					{
						//Since entry with 'ready' status already exists, update it retaining the state
					echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
					}
					else if ($update_status[$count]==CANCELLED)
					{
						echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
						echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
					}
					else if ($update_status[$count]==ERROR)
					{
						//Since entry with 'error' status already exists, leave as is and inform user
						echo('Update of  '.$s.' database encountered error during previous execution.' . $nl);
						echo ('Please add the report manaully from Status page to ensure it runs. '. $nl);
					}
					else if ($update_status[$count]==RUNNING)
					{
						//No action if update is already running
						echo('Update of  '.$s.' database already running currently.' . $nl);
					}
					else
					{
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET update_items_progress=0, update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
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
					
					
					//Check if scheduled item has any heatmap reports
					$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
					$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item. Error: '.mysql_error());
					$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
					foreach($results as $row)
					{
						$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
						$res2 = mysql_query($query) or die('Bad SQL query getting heatmap report name. Error: '.mysql_error());
						$row2 = mysql_fetch_assoc($res2);
						if($row2 === false)
						{
							echo('Heatmap not found.' . $nl);
							continue;
						}
						$query = 'SELECT * FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
						$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
						$row3 = mysql_fetch_assoc($res3);
						if($row3['status']==READY)
						{
							echo('Entry to generate report already present - heatmap ' . $row['heatmap'] . $nl);
						}
						else if($row3['status']==RUNNING)
						{
							echo('Report already running - heatmap ' . $row['heatmap'] . $nl);
						}
						else if($row3['status']==CANCELLED)
						{
							//echo('Report requeued after cancellation - heatmap ' . $row['heatmap'] . $nl);
							echo ('Report was cancelled during previous execution - heatmap ' . $row['heatmap'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else if($row3['status']==ERROR)
						{
							echo ('Report encountered an error during previous execution - heatmap ' . $row['heatmap'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else
						{
							//Delete the previously completed report entry if it exists
							$query = 'DELETE FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
							$res5=mysql_query($query);
							if($res5==1)
								echo('Deleted previous entry to generate report - heatmap ' . $row['heatmap'] . $nl);
							
							//Add new entry with status ready
							$query = 'INSERT INTO reports_status SET  run_id="' . $item['id']	. '",type_id="' . $row['heatmap']	. '",report_type="0",status="'.READY.'"';
							$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
							echo('Adding entry to generate report - heatmap ' . $row['heatmap'] . $nl);
							
						}
					}
					
					
					//Check if scheduled item has any updatescan reports
					$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
					$res = mysql_query($query) or die('Bad SQL query getting updatescans for item. Error: '.mysql_error());
					$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
					foreach($results as $row)
					{
						$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
						$res2 = mysql_query($query) or die('Bad SQL query getting report name. Error: '.mysql_error());
						$row2 = mysql_fetch_assoc($res2);
						if($row2 === false)
						{
							echo('updatescan not found.' . $nl);
							continue;
						}
						$query = 'SELECT * FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
						$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
						$row3 = mysql_fetch_assoc($res3);
						if($row3['status']==READY)
						{
							echo('Entry to generate report already present - updatescan ' . $row['updatescan'] . $nl);
						}
						else if($row3['status']==RUNNING)
						{
							echo('Report already running - updatescan ' . $row['updatescan'] . $nl);
						}
						else if($row3['status']==CANCELLED)
						{
							//echo('Report requeued after cancellation - updatescan ' . $row['updatescan'] . $nl);
							echo('Report was cancelled during previous execution - updatescan ' . $row['updatescan'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else if($row3['status']==ERROR)
						{
							echo ('Report encountered an error during previous execution - updatescan ' . $row['updatescan'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else
						{
							//Delete the previously completed report entry if it exists
							$query = 'DELETE FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
							$res5=mysql_query($query);
							if($res5==1)
								echo('Deleted previous entry to generate report - updatescan ' . $row['updatescan'] . $nl);
							
							//Add new entry with status ready
							$query = 'INSERT INTO reports_status SET  run_id="' . $item['id']	.'",type_id="' . $row['updatescan']. '",report_type="2",status="'.READY.'"';
							$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
							echo('Adding entry to generate report - updatescan ' . $row['updatescan'] . $nl);
						
						}
					}
				}
				echo('Done checking for scheduled reports.' . $nl);
			}
			else
				echo('No new scheduled reports.' . $nl);
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
				$update_status[$count++] = $row['status'];
				//Update flag which checks if all updates are complete
				if($row['status']!=COMPLETED)
					$all_updates_complete=0;
			}
				
			//Get all data from 'reports_status'
			$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status';
			$res = mysql_query($query) or die('Bad SQL Query getting reports_status');
			$run_status = array();
			$count=0;
			while($row = mysql_fetch_assoc($res))
				$run_status[$count++] = $row['status'];
			
			
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
						case 4:
						$updtname='calc_hm_cells'; 
						break;
					}
					if($updtname=='calc_hm_cells')
					{
						require_once('calculate_hm_cells.php');
						calc_cells(NULL,$run_updates[$i]['update_id']);
					}
					else
					{
						//Start the update execution
						$filename = 'fetch_' . $updtname . '.php';
						echo('Invoking: ' . $filename . '...</pre>' . $nl);
						$days_to_fetch=$run_updates[$i]['updated_days'];
						$update_id=$run_updates[$i]['update_id'];
						require_once($filename);
						echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
					}	
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
				$update_status[$count++] = $row['status'];
			
			
			//Get list of all reports(running and ready to run)
			$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status WHERE run_id='.$selected_schedule_item;
			$res = mysql_query($query) or die('Bad SQL Query getting report_status');
			$run_ids = array();
			$run_rpttype = array();
			$run_status = array();
			$count=0;
			while($row = mysql_fetch_assoc($res))
			{
				$run_ids[$count] = $row['run_id'];
				$run_typids[$count] = $row['type_id'];
				$run_rpttype[$count] = $row['report_type'];
				$run_status[$count++] = $row['status'];
			}
			
			if(!count($run_ids))
			{
				echo('No report scheduled.' . $nl);
				posix_kill(getmypid(),2);
			}
			else if(in_array(READY,$run_status))
			{
				//Find the ready to run report and break out
				$run_flag=0;
				for($i=0;$i< $count;$i++)
				{
					if($run_status[$i]==RUNNING)
						echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is already running.'. $nl);
					else if ($run_status[$i]==COMPLETED)
						echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' has finished running.'. $nl);
					else if($run_status[$i]==READY)
					{
						echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is ready to run.'. $nl);
						$current_run_item=$i;
						$run_flag=1;
						break;
					}
				}
				if($run_flag==0)
				{
					echo("All scheduled reports are currently running.");
					posix_kill(getmypid(),2);
				}
			}
			else
			{
				$run_flag=0;
				for($i=0;$i< $count;$i++)
				{
					if($run_status[$i]==RUNNING)
						$run_flag=1;
				}
				if($run_flag==1)
					echo('All reports are running.' . $nl);
				else
					echo('No report scheduled.' . $nl);
				
				posix_kill(getmypid(),2);
			}

			$files = array();
			$_GET['noheaders'] = 1;
			
			$run_id=$run_ids[$i];
			$report_type=$run_rpttype[$current_run_item];
			$type_id=$run_typids[$i];
			if($run_rpttype[$current_run_item]==0)
			{
				$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query getting report name');
				$row = mysql_fetch_assoc($res);
				
				$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to running');
				
				try{
						$files[$row['name']] = runHeatmap($run_typids[$current_run_item], true);
					}catch(Exception $e){
						$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
					}
					
				$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to done');
			}
			else if($run_rpttype[$current_run_item]==1)
			{
				$query = 'SELECT name FROM rpt_update WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query getting report name');
				$row = mysql_fetch_assoc($res);
				
				$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to running');
				
				try{
						$files[$row['name']] = runUpdateReport($run_typids[$current_run_item], true);
					}catch(Exception $e){
						$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
					}
					
				$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to done');
			}
			
			$query = 'SELECT name FROM schedule WHERE id='.$run_ids[$current_run_item];
			$res = mysql_query($query) or die('Bad SQL Query get schedule item name');
			$sch_item = mysql_fetch_assoc($res);
			
			echo('Report generated for - '.$rtype_name. ' ' . $run_ids[$current_run_item] . $nl);
			//Send mail with attached report
			$query = 'SELECT emails FROM schedule WHERE id=' . $run_ids[$current_run_item] . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting report name');
			$item = mysql_fetch_assoc($res);
			if(count($files))
			{
				echo(' Sending to: ' . $item['emails'] . ' ... ');
				$mail = new PHPMailer();
				$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
				if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
				$emails = explode(',', $item['emails']);
				foreach($emails as $email) $mail->AddAddress($email);
				$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $sch_item['name']. ' ('.$row['name'].')';
				$mail->Body = 'Attached is the report ' . $row['name'].' indicated in the schedule item ' . $sch_item['name'];
				$current_filename=		   substr($fname,0,20).'_'.date('Y-m-d_H.i.s');
				foreach($files as $fname => $file)
				{
					$current_filename=		   substr($fname,0,20).'_'.date('Y-m-d_H.i.s');
					$mail->AddStringAttachment($file,
											   $current_filename.'.xlsx',
											   'base64',
											   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',$emails,'scheduled reports');		
				}
				$mail->Send();
					echo(' -Mail sent.' . $nl);
			}
			else
			{
				echo(' -No files to send.' . $nl);
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
			
			//Get Process IDs of all currently running reports
			$query = 'SELECT `run_id`,`type_id`,`report_type`,`process_id` FROM reports_status WHERE `status`='.RUNNING;
			$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
			$count_rpids=0;
			while($row = mysql_fetch_assoc($res))
			{
				$report_run_ids[$count_rpids] = $row['run_id'];
				$report_typ_ids[$count_rpids] = $row['type_id'];
				$report_rpt_typ[$count_rpids] = $row['report_type'];
				$report_pids[$count_rpids++] = $row['process_id'];
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
						case 4:
						$updtname='calc_hm_cells'; 
						break;
					}
					echo($updtname.' database updation error. Requeueing it.' . $nl);
					$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
					$res = mysql_query($query) or die('Bad SQL Query setting update error status');
				}
			}
			
			//Check if any report has terminated abruptly
			for($i=0;$i < $count_rpids; $i++)
			{
				//If report_status is running and corresponding process ID is not running
				if(!in_array($report_pids[$i],$running_pids))
				{
					//Report status set to 'error'
					echo('Item ID '.$report_run_ids[$i].' - '.$rtype_name[$report_rpt_typ[$i]].' - '.$report_typ_ids[$i].' error. Requeueing it.' . $nl);
					$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
					$res = mysql_query($query) or die('Bad SQL Query setting report error status');
				}
			}
			/************************************ Step 1 ****************************************/			
			
			
			/************************************ Step 2 ****************************************/
			echo($nl);
			echo ('Checking schedule for updates and reports...' . $nl);
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
				$update_status[$count++] = $row['status'];
			
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
						case 'calc_hm_cells':
						$updtid=4;
						break;
						
					}
					if($update_status[$count]==COMPLETED)
					{
						//Remove previous entry corresponding to completed update
						$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
						if($res==1)
							echo('Removed previous entry for '.$s.$nl);
						
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						
						
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
					}
					else if ($update_status[$count]==READY)
					{
						//Since entry with 'ready' status already exists, update it retaining the state
					echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
					}
					else if ($update_status[$count]==CANCELLED)
					{
						echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
						echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
					}
					else if ($update_status[$count]==ERROR)
					{
						//Since entry with 'error' status already exists, leave as is and inform user
						echo('Update of  '.$s.' database encountered error during previous execution..' . $nl);
						echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
					}
					else if ($update_status[$count]==RUNNING)
					{
						//No action if update is already running
						echo('Update of  '.$s.' database already running currently.' . $nl);
					}
					else
					{
						//Add new entry with status ready
						echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
						$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
						
						
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
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
					
					
					//Check if scheduled item has any heatmap reports
					$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
					$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item. Error: '.mysql_error());
					$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
					foreach($results as $row)
					{
						$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
						$res2 = mysql_query($query) or die('Bad SQL query getting heatmap report name. Error: '.mysql_error());
						$row2 = mysql_fetch_assoc($res2);
						if($row2 === false)
						{
							echo('Heatmap not found.' . $nl);
							continue;
						}
						$query = 'SELECT * FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
						$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
						$row3 = mysql_fetch_assoc($res3);
						if($row3['status']==READY)
						{
							echo('Entry to generate report already present - heatmap ' . $row['heatmap'] . $nl);
						}
						else if($row3['status']==RUNNING)
						{
							echo('Report already running - heatmap ' . $row['heatmap'] . $nl);
						}
						else if($row3['status']==CANCELLED)
						{
							//echo('Report requeued after cancellation - heatmap ' . $row['heatmap'] . $nl);
							echo ('Report was cancelled during previous execution - heatmap ' . $row['heatmap'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else if($row3['status']==ERROR)
						{
							echo ('Report encountered an error during previous execution - heatmap ' . $row['heatmap'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else
						{
							//Delete the previously completed report entry if it exists
							$query = 'DELETE FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
							$res5=mysql_query($query);
							if($res5==1)
								echo('Deleted previous entry to generate report - heatmap ' . $row['heatmap'] . $nl);
							
							//Add new entry with status ready
							$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	. '",type_id="' . $row['heatmap']	. '",report_type="0",status="'.READY.'"';
							$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
							echo('Adding entry to generate report - heatmap ' . $row['heatmap'] . $nl);
							
						}
					}
					
			
					//Check if scheduled item has any updatescan reports
					$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
					$res = mysql_query($query) or die('Bad SQL query getting updatescans for item. Error: '.mysql_error());
					$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
					foreach($results as $row)
					{
						$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
						$res2 = mysql_query($query) or die('Bad SQL query getting report name. Error: '.mysql_error());
						$row2 = mysql_fetch_assoc($res2);
						if($row2 === false)
						{
							echo('updatescan not found.' . $nl);
							continue;
						}
						$query = 'SELECT * FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
						$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
						$row3 = mysql_fetch_assoc($res3);
						if($row3['status']==READY)
						{
							echo('Entry to generate report already present - updatescan ' . $row['updatescan'] . $nl);
						}
						else if($row3['status']==RUNNING)
						{
							echo('Report already running - updatescan ' . $row['updatescan'] . $nl);
						}
						else if($row3['status']==CANCELLED)
						{
							//echo('Report requeued after cancellation - updatescan ' . $row['updatescan'] . $nl);
							echo('Report was cancelled during previous execution - updatescan ' . $row['updatescan'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else if($row3['status']==ERROR)
						{
							echo ('Report encountered an error during previous execution - updatescan ' . $row['updatescan'] . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
						}
						else
						{
							//Delete the previously completed report entry if it exists
							$query = 'DELETE FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
							$res5=mysql_query($query);
							if($res5==1)
								echo('Deleted previous entry to generate report - updatescan ' . $row['updatescan'] . $nl);
							
							//Add new entry with status ready
							$query = 'INSERT INTO reports_status SET  run_id="' . $item['id']	.'",type_id="' . $row['updatescan']. '",report_type="2",status="'.READY.'"';
							$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
							echo('Adding entry to generate report - updatescan ' . $row['updatescan'] . $nl);
						
						}
					}
				}
				echo('Done checking for scheduled reports.' . $nl);
			}
			else
				echo('No new scheduled reports.' . $nl);
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
				$update_status[$count++] = $row['status'];
				//Update flag which checks if all updates are complete
				if($row['status']!=COMPLETED)
					$all_updates_complete=0;
			}
				
			//Get all data from 'reports_status'
			$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status';
			$res = mysql_query($query) or die('Bad SQL Query getting reports_status');
			$run_status = array();
			$count=0;
			while($row = mysql_fetch_assoc($res))
				$run_status[$count++] = $row['status'];
			
			
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
						case 4:
						$updtname='calc_hm_cells'; 
						break;
					}
					if($updtname=='calc_hm_cells')
					{
						require_once('calculate_hm_cells.php');
						calc_cells(NULL,$run_updates[$i]['update_id']);
					}
					else
					{
					//Start the update execution
					$filename = 'fetch_' . $updtname . '.php';
					echo('Invoking ' . $filename . '...</pre>' . $nl);
					$days_to_fetch=$run_updates[$i]['updated_days'];
					$update_id=$run_updates[$i]['update_id'];
					require_once($filename);
					echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
					}
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
				$update_status[$count++] = $row['status'];
			
			
			//Get list of all reports(running and ready to run)
			$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status WHERE run_id='.$selected_schedule_item;
			$res = mysql_query($query) or die('Bad SQL Query getting report_status');
			$run_ids = array();
			$run_rpttype = array();
			$run_status = array();
			$count=0;
			while($row = mysql_fetch_assoc($res))
			{
				$run_ids[$count] = $row['run_id'];
				$run_typids[$count] = $row['type_id'];
				$run_rpttype[$count] = $row['report_type'];
				$run_status[$count++] = $row['status'];
			}
			
			if(!count($run_ids))
			{
				echo('No report scheduled.' . $nl);
				posix_kill(getmypid(),2);
			}
			else if(in_array(READY,$run_status))
			{
				//Find the ready to run report and break out
				$run_flag=0;
				for($i=0;$i< $count;$i++)
				{
					if($run_status[$i]==RUNNING)
						echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is already running.'. $nl);
					else if ($run_status[$i]==COMPLETED)
						echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' has finished running.'. $nl);
					else if($run_status[$i]==READY)
					{
						echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is ready to run.'. $nl);
						$current_run_item=$i;
						$run_flag=1;
						break;
					}
				}
				if($run_flag==0)
				{
					echo("All scheduled reports are currently running.");
					posix_kill(getmypid(),2);
				}
			}
			else
			{
				$run_flag=0;
				for($i=0;$i< $count;$i++)
				{
					if($run_status[$i]==RUNNING)
						$run_flag=1;
				}
				if($run_flag==1)
					echo('All reports are running.' . $nl);
				else
					echo('No report scheduled.' . $nl);
				
				posix_kill(getmypid(),2);
			}
			
			$files = array();
			$_GET['noheaders'] = 1;

			$run_id=$run_ids[$i];
			$report_type=$run_rpttype[$current_run_item];
			$type_id=$run_typids[$i];
			if($run_rpttype[$current_run_item]==0)
			{
				$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query getting report name');
				$row = mysql_fetch_assoc($res);
				
				$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to running');
				
				try{
						$files[$row['name']] = runHeatmap($run_typids[$current_run_item], true);
					}catch(Exception $e){
						$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
					}
					
				$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to done');
			}
			else if($run_rpttype[$current_run_item]==1)
			{
				$query = 'SELECT name FROM rpt_update WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query getting report name');
				$row = mysql_fetch_assoc($res);
				
				$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to running');
				
				try{
						$files[$row['name']] = runUpdateReport($run_typids[$current_run_item], true);
					}catch(Exception $e){
						$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
					}
					
				$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
				$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to done');
			}
			
			$query = 'SELECT name FROM schedule WHERE id='.$run_ids[$current_run_item];
			$res = mysql_query($query) or die('Bad SQL Query get schedule item name');
			$sch_item = mysql_fetch_assoc($res);
			
			echo('Report generated for - '.$rtype_name. ' ' . $run_ids[$current_run_item] . $nl);
			//Send mail with attached report
			$query = 'SELECT emails FROM schedule WHERE id=' . $run_ids[$current_run_item] . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting report name');
			$item = mysql_fetch_assoc($res);
			if(count($files))
			{
				echo(' Sending to: ' . $item['emails'] . ' ... ');
				$mail = new PHPMailer();
				$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
				if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
				$emails = explode(',', $item['emails']);
				foreach($emails as $email) $mail->AddAddress($email);
				$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $sch_item['name']. ' ('.$row['name'].')';
				$mail->Body = 'Attached is the report ' . $row['name'].' indicated in the schedule item ' . $sch_item['name'];
				
				foreach($files as $fname => $file)
				{
					$mail->AddStringAttachment($file,
											   substr($fname,0,20).'_'.date('Y-m-d_H.i.s').'.xlsx',
											   'base64',
											   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
				}
				$mail->Send();
					echo(' -Mail sent.' . $nl);
			}
			else
			{
				echo(' -No files to send.' . $nl);
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
						
						//Get Process IDs of all currently running reports
						$query = 'SELECT `run_id`,`type_id`,`report_type`,`process_id` FROM reports_status WHERE `status`='.RUNNING;
						$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
						$count_rpids=0;
						while($row = mysql_fetch_assoc($res))
						{
							$report_run_ids[$count_rpids] = $row['run_id'];
							$report_typ_ids[$count_rpids] = $row['type_id'];
							$report_rpt_typ[$count_rpids] = $row['report_type'];
							$report_pids[$count_rpids++] = $row['process_id'];
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
									case 4:
									$updtname='calc_hm_cells';
									break;
									
								}
								//Update status set to 'error'
								echo($updtname.' database updation error. Requeueing it.' . $nl);
								$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
								$res = mysql_query($query) or die('Bad SQL Query setting update error status');
							}
						}
						
						//Check if any report has terminated abruptly
						for($i=0;$i < $count_rpids; $i++)
						{
							//If report_status is running and corresponding process ID is not running
							if(!in_array($report_pids[$i],$running_pids))
							{
								//Report status set to 'error'
								echo('Item ID '.$report_run_ids[$i].' - '.$rtype_name[$report_rpt_typ[$i]].' - '.$report_typ_ids[$i].' error. Requeueing it.' . $nl);
								$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
								$res = mysql_query($query) or die('Bad SQL Query setting report error status');
							}
						}
						/************************************ Step 1 ****************************************/
						
						
						
						/************************************ Step 2 ****************************************/
						echo($nl);
						echo ('Checking schedule for updates and reports...' . $nl);
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
							$update_status[$count++] = $row['status'];
						
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
									case 'calc_hm_cells':
									$updtid=4;
									break;
									
								}
								if($update_status[$count]==COMPLETED)
								{
									//Remove previous entry corresponding to completed update
									$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
									if($res==1)
										echo('Removed previous entry for '.$s.$nl);
									
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
								}
								else if ($update_status[$count]==READY)
								{
									//Since entry with 'ready' status already exists, update it retaining the state
								echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
								}
								else if ($update_status[$count]==CANCELLED)
								{
									echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
									echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
								}
								else if ($update_status[$count]==ERROR)
								{
									//Since entry with 'error' status already exists, leave as is and inform user
									echo('Update of  '.$s.' database encountered error during previous execution...' . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs ...'. $nl);
								}
								else if ($update_status[$count]==RUNNING)
								{
									//No action if update is already running
									echo('Update of  '.$s.' database already running currently.' . $nl);
								}
								else
								{
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
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
								
								
								//Check if scheduled item has any heatmap reports
								$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
								$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item. Error: '.mysql_error());
								$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
								foreach($results as $row)
								{
									$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
									$res2 = mysql_query($query) or die('Bad SQL query getting heatmap report name. Error: '.mysql_error());
									$row2 = mysql_fetch_assoc($res2);
									if($row2 === false)
									{
										echo('Heatmap not found.' . $nl);
										continue;
									}
									$query = 'SELECT * FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
									$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
									$row3 = mysql_fetch_assoc($res3);
									if($row3['status']==READY)
									{
										echo('Entry to generate report already present - heatmap ' . $row['heatmap'] . $nl);
									}
									else if($row3['status']==RUNNING)
									{
										echo('Report already running - heatmap ' . $row['heatmap'] . $nl);
									}
									else if($row3['status']==CANCELLED)
									{
										//echo('Report requeued after cancellation - heatmap ' . $row['heatmap'] . $nl);
										echo ('Report was cancelled during previous execution - heatmap ' . $row['heatmap'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else if($row3['status']==ERROR)
									{
										echo ('Report encountered an error during previous execution - heatmap ' . $row['heatmap'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else
									{
										//Delete the previously completed report entry if it exists
										$query = 'DELETE FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
										$res5=mysql_query($query);
										if($res5==1)
											echo('Deleted previous entry to generate report - heatmap ' . $row['heatmap'] . $nl);
										
										//Add new entry with status ready
										$query = 'INSERT INTO reports_status SET  run_id="' . $item['id']	. '",type_id="' . $row['heatmap']	. '",report_type="0",status="'.READY.'"';
										$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
										echo('Adding entry to generate report - heatmap ' . $row['heatmap'] . $nl);
										
									}
								}
								
								
								//Check if scheduled item has any updatescan reports
								$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
								$res = mysql_query($query) or die('Bad SQL query getting updatescans for item. Error: '.mysql_error());
								$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
								foreach($results as $row)
								{
									$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
									$res2 = mysql_query($query) or die('Bad SQL query getting report name. Error: '.mysql_error());
									$row2 = mysql_fetch_assoc($res2);
									if($row2 === false)
									{
										echo('updatescan not found.' . $nl);
										continue;
									}
									$query = 'SELECT * FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
									$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
									$row3 = mysql_fetch_assoc($res3);
									if($row3['status']==READY)
									{
										echo('Entry to generate report already present - updatescan ' . $row['updatescan'] . $nl);
									}
									else if($row3['status']==RUNNING)
									{
										echo('Report already running - updatescan ' . $row['updatescan'] . $nl);
									}
									else if($row3['status']==CANCELLED)
									{
										//echo('Report requeued after cancellation - updatescan ' . $row['updatescan'] . $nl);
										echo('Report was cancelled during previous execution - updatescan ' . $row['updatescan'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else if($row3['status']==ERROR)
									{
										echo ('Report encountered an error during previous execution - updatescan ' . $row['updatescan'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else
									{
										//Delete the previously completed report entry if it exists
										$query = 'DELETE FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
										$res5=mysql_query($query);
										if($res5==1)
											echo('Deleted previous entry to generate report - updatescan ' . $row['updatescan'] . $nl);
										
										//Add new entry with status ready
										$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	.'",type_id="' . $row['updatescan']. '",report_type="2",status="'.READY.'"';
										$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
										echo('Adding entry to generate report - updatescan ' . $row['updatescan'] . $nl);
									
									}
								}
							}
							echo('Done checking for scheduled reports.' . $nl);
						}
						else
							echo('No new scheduled reports.' . $nl);
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
							$update_status[$count++] = $row['status'];
							//Update flag which checks if all updates are complete
							if($row['status']!=COMPLETED)
								$all_updates_complete=0;
						}
							
						//Get all data from 'reports_status'
						$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status';
						$res = mysql_query($query) or die('Bad SQL Query getting reports_status');
						$run_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
							$run_status[$count++] = $row['status'];
						
						
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
									case 4:
									$updtname='calc_hm_cells'; 
									break;
									
								}
								if($updtname=='calc_hm_cells')
								{
									require_once('calculate_hm_cells.php');
									calc_cells(NULL,$run_updates[$i]['update_id']);
								}
								else
								{
								$filename = 'fetch_' . $updtname . '.php';
								echo('Invoking:- ' . $filename . '...</pre>' . $nl);
								$days_to_fetch=$run_updates[$i]['updated_days'];
								$update_id=$run_updates[$i]['update_id'];
								require_once($filename);
								echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
								}
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
							$update_status[$count++] = $row['status'];
						
						
						//Get list of all reports(running and ready to run)
						$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status WHERE run_id='.$selected_schedule_item;
						$res = mysql_query($query) or die('Bad SQL Query getting report_status');
						$run_ids = array();
						$run_rpttype = array();
						$run_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
						{
							$run_ids[$count] = $row['run_id'];
							$run_typids[$count] = $row['type_id'];
							$run_rpttype[$count] = $row['report_type'];
							$run_status[$count++] = $row['status'];
						}
						
						if(!count($run_ids))
						{
							echo('No report scheduled.' . $nl);
							posix_kill(getmypid(),2);
						}
						else if(in_array(READY,$run_status)/*||in_array(CANCELLED,$run_status)*/)
						{
							//Find the ready to run report and break out
							$run_flag=0;
							for($i=0;$i< $count;$i++)
							{
								if($run_status[$i]==RUNNING)
									echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is already running.'. $nl);
								else if ($run_status[$i]==COMPLETED)
									echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' has finished running.'. $nl);
								else if($run_status[$i]==READY)
								{
									echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is ready to run.'. $nl);
									$current_run_item=$i;
									$run_flag=1;
									break;
								}
							}
							if($run_flag==0)
							{
								echo("All scheduled reports are currently running.");
								posix_kill(getmypid(),2);
							}
						}
						else
						{
							$run_flag=0;
							for($i=0;$i< $count;$i++)
							{
								if($run_status[$i]==RUNNING)
									$run_flag=1;
							}
							if($run_flag==1)
								echo('All reports are running.' . $nl);
							else
								echo('No report scheduled.' . $nl);
							
							posix_kill(getmypid(),2);
						}
						
						$files = array();
						$_GET['noheaders'] = 1;
						
						$run_id=$run_ids[$i];
						$report_type=$run_rpttype[$current_run_item];
						$type_id=$run_typids[$i];
						if($run_rpttype[$current_run_item]==0)
						{
							$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
							$res = mysql_query($query) or die('Bad SQL query getting report name');
							$row = mysql_fetch_assoc($res);
							
							$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to running');
							
							try{
									$files[$row['name']] = runHeatmap($run_typids[$current_run_item], true);
								}catch(Exception $e){
									$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
								}
								
							$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to done');
						}
						else if($run_rpttype[$current_run_item]==1)
						{
							$query = 'SELECT name FROM rpt_update WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
							$res = mysql_query($query) or die('Bad SQL query getting report name');
							$row = mysql_fetch_assoc($res);
							
							$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to running');
							
							try{
									$files[$row['name']] = runUpdateReport($run_typids[$current_run_item], true);
								}catch(Exception $e){
									$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
								}
								
							$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to done');
						}
						
						$query = 'SELECT name FROM schedule WHERE id='.$run_ids[$current_run_item];
						$res = mysql_query($query) or die('Bad SQL Query get schedule item name');
						$sch_item = mysql_fetch_assoc($res);
						
						echo('Report generated for - '.$rtype_name. ' ' . $run_ids[$current_run_item] . $nl);
						//Send mail with attached report
						$query = 'SELECT emails FROM schedule WHERE id=' . $run_ids[$current_run_item] . ' LIMIT 1';
						$res = mysql_query($query) or die('Bad SQL query getting report name');
						$item = mysql_fetch_assoc($res);
						if(count($files))
						{
							echo(' Sending to: ' . $item['emails'] . ' ... ');
							$mail = new PHPMailer();
							$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
							if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
							$emails = explode(',', $item['emails']);
							foreach($emails as $email) $mail->AddAddress($email);
							$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $sch_item['name']. ' ('.$row['name'].')';
							$mail->Body = 'Attached is the report ' . $row['name'].' indicated in the schedule item ' . $sch_item['name'];
							
							foreach($files as $fname => $file)
							{
								$mail->AddStringAttachment($file,
														   substr($fname,0,20).'_'.date('Y-m-d_H.i.s').'.xlsx',
														   'base64',
														   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
							}
							$mail->Send();
								echo(' -Mail sent.' . $nl);
						}
						else
						{
							echo(' -No files to send.' . $nl);
						}
						/************************************ Step 4 ****************************************/
						posix_kill(getmypid(),1);
					}
				}			
			}
			else
			{
				sleep(10);
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
						
						//Get Process IDs of all currently running reports
						$query = 'SELECT `run_id`,`type_id`,`report_type`,`process_id` FROM reports_status WHERE `status`='.RUNNING;
						$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
						$count_rpids=0;
						while($row = mysql_fetch_assoc($res))
						{
							$report_run_ids[$count_rpids] = $row['run_id'];
							$report_typ_ids[$count_rpids] = $row['type_id'];
							$report_rpt_typ[$count_rpids] = $row['report_type'];
							$report_pids[$count_rpids++] = $row['process_id'];
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
									case 4:
									$updtname='calc_hm_cells'; 
									break;
									
								}
								//Update status set to 'error'
								echo($updtname.' database updation error. Requeueing it.' . $nl);
								$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
								$res = mysql_query($query) or die('Bad SQL Query setting update error status');
							}
						}
						
						//Check if any report has terminated abruptly
						for($i=0;$i < $count_rpids; $i++)
						{
							//If report_status is running and corresponding process ID is not running
							if(!in_array($report_pids[$i],$running_pids))
							{
								//Report status set to 'error'
								echo('Item ID '.$report_run_ids[$i].' - '.$rtype_name[$report_rpt_typ[$i]].' - '.$report_typ_ids[$i].' error. Requeueing it.' . $nl);
								$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
								$res = mysql_query($query) or die('Bad SQL Query setting report error status');
							}
						}
						/************************************ Step 1 ****************************************/
						
						
						
						/************************************ Step 2 ****************************************/
						echo($nl);
						echo ('Checking schedule for updates and reports...' . $nl);
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
							$update_status[$count++] = $row['status'];
						
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
									case 'calc_hm_cells':
									$updtid=4;
									break;
									
								}
								if($update_status[$count]==COMPLETED)
								{
									//Remove previous entry corresponding to completed update
									$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
									if($res==1)
										echo('Removed previous entry for '.$s.$nl);
									
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status  SET update_items_progress="0",  update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
								}
								else if ($update_status[$count]==READY)
								{
									//Since entry with 'ready' status already exists, update it retaining the state
								echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid .'"';
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
								}
								else if ($update_status[$count]==CANCELLED)
								{
									echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
									echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
								}
								else if ($update_status[$count]==ERROR)
								{
									//Since entry with 'error' status already exists, leave as is and inform user
									echo('Update of  '.$s.' database encountered error during previous execution....' . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs-- '. $nl);
								}
								else if ($update_status[$count]==RUNNING)
								{
									//No action if update is already running
									echo('Update of  '.$s.' database already running currently.' . $nl);
								}
								else
								{
									//Add new entry with status ready
									echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
									$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
									
									
									$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
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
								
								
								//Check if scheduled item has any heatmap reports
								$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
								$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item. Error: '.mysql_error());
								$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
								foreach($results as $row)
								{
									$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
									$res2 = mysql_query($query) or die('Bad SQL query getting heatmap report name. Error: '.mysql_error());
									$row2 = mysql_fetch_assoc($res2);
									if($row2 === false)
									{
										echo('Heatmap not found.' . $nl);
										continue;
									}
									$query = 'SELECT * FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
									$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
									$row3 = mysql_fetch_assoc($res3);
									if($row3['status']==READY)
									{
										echo('Entry to generate report already present - heatmap ' . $row['heatmap'] . $nl);
									}
									else if($row3['status']==RUNNING)
									{
										echo('Report already running - heatmap ' . $row['heatmap'] . $nl);
									}
									else if($row3['status']==CANCELLED)
									{
										//echo('Report requeued after cancellation - heatmap ' . $row['heatmap'] . $nl);
										echo ('Report was cancelled during previous execution - heatmap ' . $row['heatmap'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else if($row3['status']==ERROR)
									{
										echo ('Report encountered an error during previous execution - heatmap ' . $row['heatmap'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else
									{
										//Delete the previously completed report entry if it exists
										$query = 'DELETE FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
										$res5=mysql_query($query);
										if($res5==1)
											echo('Deleted previous entry to generate report - heatmap ' . $row['heatmap'] . $nl);
										
										//Add new entry with status ready
										$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	. '",type_id="' . $row['heatmap']	. '",report_type="0",status="'.READY.'"';
										$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
										echo('Adding entry to generate report - heatmap ' . $row['heatmap'] . $nl);
										
									}
								}

								
								//Check if scheduled item has any updatescan reports
								$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
								$res = mysql_query($query) or die('Bad SQL query getting updatescans for item. Error: '.mysql_error());
								$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
								foreach($results as $row)
								{
									$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
									$res2 = mysql_query($query) or die('Bad SQL query getting report name. Error: '.mysql_error());
									$row2 = mysql_fetch_assoc($res2);
									if($row2 === false)
									{
										echo('updatescan not found.' . $nl);
										continue;
									}
									$query = 'SELECT * FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
									$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
									$row3 = mysql_fetch_assoc($res3);
									if($row3['status']==READY)
									{
										echo('Entry to generate report already present - updatescan ' . $row['updatescan'] . $nl);
									}
									else if($row3['status']==RUNNING)
									{
										echo('Report already running - updatescan ' . $row['updatescan'] . $nl);
									}
									else if($row3['status']==CANCELLED)
									{
										//echo('Report requeued after cancellation - updatescan ' . $row['updatescan'] . $nl);
										echo('Report was cancelled during previous execution - updatescan ' . $row['updatescan'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else if($row3['status']==ERROR)
									{
										echo ('Report encountered an error during previous execution - updatescan ' . $row['updatescan'] . $nl);
										echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
									}
									else
									{
										//Delete the previously completed report entry if it exists
										$query = 'DELETE FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
										$res5=mysql_query($query);
										if($res5==1)
											echo('Deleted previous entry to generate report - updatescan ' . $row['updatescan'] . $nl);
										
										//Add new entry with status ready
										$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	.'",type_id="' . $row['updatescan']. '",report_type="2",status="'.READY.'"';
										$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
										echo('Adding entry to generate report - updatescan ' . $row['updatescan'] . $nl);
									
									}
								}
							}
							echo('Done checking for scheduled reports.' . $nl);
						}
						else
							echo('No new scheduled reports.' . $nl);
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
							$update_status[$count++] = $row['status'];
							//Update flag which checks if all updates are complete
							if($row['status']!=COMPLETED)
								$all_updates_complete=0;
						}
							
						//Get all data from 'reports_status'
						$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status';
						$res = mysql_query($query) or die('Bad SQL Query getting reports_status');
						$run_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
							$run_status[$count++] = $row['status'];
						
						
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
									case 4:
									$updtname='calc_hm_cells'; 
									break;
									
								}
								if($updtname=='calc_hm_cells')
								{
									require_once('calculate_hm_cells.php');
									calc_cells(NULL,$run_updates[$i]['update_id']);
								}
								else
								{
									//Start the update execution
									$filename = 'fetch_' . $updtname . '.php';
									echo('Invoking.- ' . $filename . '...</pre>' . $nl);
									$days_to_fetch=$run_updates[$i]['updated_days'];
									$update_id=$run_updates[$i]['update_id'];
									echo '<br>'.($filename). '<br>';
									echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
								}	
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
							$update_status[$count++] = $row['status'];
						
						
						//Get list of all reports(running and ready to run)
						$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status WHERE run_id='.$selected_schedule_item;
						$res = mysql_query($query) or die('Bad SQL Query getting report_status');
						$run_ids = array();
						$run_rpttype = array();
						$run_status = array();
						$count=0;
						while($row = mysql_fetch_assoc($res))
						{
							$run_ids[$count] = $row['run_id'];
							$run_typids[$count] = $row['type_id'];
							$run_rpttype[$count] = $row['report_type'];
							$run_status[$count++] = $row['status'];
						}
						
						if(!count($run_ids))
						{
							echo('No report scheduled.' . $nl);
							posix_kill(getmypid(),2);
						}
						else if(in_array(READY,$run_status))
						{
							//Find the ready to run report and break out
							$run_flag=0;
							for($i=0;$i< $count;$i++)
							{
								if($run_status[$i]==RUNNING)
									echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is already running.'. $nl);
								else if ($run_status[$i]==COMPLETED)
									echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' has finished running.'. $nl);
								else if($run_status[$i]==READY)
								{
									echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is ready to run.'. $nl);
									$current_run_item=$i;
									$run_flag=1;
									break;
								}
							}
							if($run_flag==0)
							{
								echo("All scheduled reports are currently running.");
								posix_kill(getmypid(),2);
							}
						}
						else
						{
							$run_flag=0;
							for($i=0;$i< $count;$i++)
							{
								if($run_status[$i]==RUNNING)
									$run_flag=1;
							}
							if($run_flag==1)
								echo('All reports are running.' . $nl);
							else
								echo('No report scheduled.' . $nl);
							
							posix_kill(getmypid(),2);
						}
						
						$files = array();
						$_GET['noheaders'] = 1;
						
						$run_id=$run_ids[$i];
						$report_type=$run_rpttype[$current_run_item];
						$type_id=$run_typids[$i];
						if($run_rpttype[$current_run_item]==0)
						{
							$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
							$res = mysql_query($query) or die('Bad SQL query getting report name');
							$row = mysql_fetch_assoc($res);
							
							$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to running');
							
							try{
									$files[$row['name']] = runHeatmap($run_typids[$current_run_item], true);
								}catch(Exception $e){
									$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
								}
								
							$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to done');
						}
						else if($run_rpttype[$current_run_item]==1)
						{
							$query = 'SELECT name FROM rpt_update WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
							$res = mysql_query($query) or die('Bad SQL query getting report name');
							$row = mysql_fetch_assoc($res);
							
							$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to running');
							
							try{
									$files[$row['name']] = runUpdateReport($run_typids[$current_run_item], true);
								}catch(Exception $e){
									$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
								}
								
							$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
							$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to done');
						}
						
						$query = 'SELECT name FROM schedule WHERE id='.$run_ids[$current_run_item];
						$res = mysql_query($query) or die('Bad SQL Query get schedule item name');
						$sch_item = mysql_fetch_assoc($res);
						
						echo('Report generated for - '.$rtype_name. ' ' . $run_ids[$current_run_item] . $nl);
						//Send mail with attached report
						$query = 'SELECT emails FROM schedule WHERE id=' . $run_ids[$current_run_item] . ' LIMIT 1';
						$res = mysql_query($query) or die('Bad SQL query getting report name');
						$item = mysql_fetch_assoc($res);
						if(count($files))
						{
							echo(' Sending to: ' . $item['emails'] . ' ... ');
							$mail = new PHPMailer();
							$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
							if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
							$emails = explode(',', $item['emails']);
							foreach($emails as $email) $mail->AddAddress($email);
							$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $sch_item['name']. ' ('.$row['name'].')';
							$mail->Body = 'Attached is the report ' . $row['name'].' indicated in the schedule item ' . $sch_item['name'];
							
							foreach($files as $fname => $file)
							{
								$mail->AddStringAttachment($file,
														   substr($fname,0,20).'_'.date('Y-m-d_H.i.s').'.xlsx',
														   'base64',
														   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
							}
							$mail->Send();
								echo(' -Mail sent.' . $nl);
						}
						else
						{
							echo(' -No files to send.' . $nl);
						}
						/************************************ Step 4 ****************************************/
						posix_kill(getmypid(),1);
					}
				}			
			}
		}
		else
		{
			sleep(10);
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
					
					//Get Process IDs of all currently running reports
					$query = 'SELECT `run_id`,`type_id`,`report_type`,`process_id` FROM reports_status WHERE `status`='.RUNNING;
					$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
					$count_rpids=0;
					while($row = mysql_fetch_assoc($res))
					{
						$report_run_ids[$count_rpids] = $row['run_id'];
						$report_typ_ids[$count_rpids] = $row['type_id'];
						$report_rpt_typ[$count_rpids] = $row['report_type'];
						$report_pids[$count_rpids++] = $row['process_id'];
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
								case 4:
								$updtname='calc_hm_cells'; 
								break;
								
							}
							//Update status set to 'error'
							echo($updtname.' database updation error. Requeueing it.' . $nl);
							$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
							$res = mysql_query($query) or die('Bad SQL Query setting update error status');
						}
					}
					
					//Check if any report has terminated abruptly
					for($i=0;$i < $count_rpids; $i++)
					{
						//If report_status is running and corresponding process ID is not running
						if(!in_array($report_pids[$i],$running_pids))
						{
							//Report status set to 'error'
							echo('Item ID '.$report_run_ids[$i].' - '.$rtype_name[$report_rpt_typ[$i]].' - '.$report_typ_ids[$i].' error. Requeueing it.' . $nl);
							$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
							$res = mysql_query($query) or die('Bad SQL Query setting report error status');
						}
					}
					/************************************ Step 1 ****************************************/
					
					
					
					/************************************ Step 2 ****************************************/
					echo($nl);
					echo ('Checking schedule for updates and reports...' . $nl);
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
						$update_status[$count++] = $row['status'];
					
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
								case 'calc_hm_cells':
								$updtid=4;
								break;
							
							}
							if($update_status[$count]==COMPLETED)
							{
								//Remove previous entry corresponding to completed update
								$query = 'DELETE FROM update_status WHERE update_id="' . $updtid .'"';
								$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
								if($res==1)
									echo('Removed previous entry for '.$s.$nl);
								
								//Add new entry with status ready
								echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
								$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
								
								
							$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
							}
							else if ($update_status[$count]==READY)
							{
								//Since entry with 'ready' status already exists, update it retaining the state
							echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
								$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid.'"';
							$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
							}
							else if ($update_status[$count]==CANCELLED)
							{
								echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
								echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
							}
							else if ($update_status[$count]==ERROR)
							{
								//Since entry with 'error' status already exists, leave as is and inform user
								echo('Update of  '.$s.' database encountered error during previous execution.....' . $nl);
								echo ('Please add the report manaully from Status page to ensure it runs.  - '. $nl);
							}
							else if ($update_status[$count]==RUNNING)
							{
								//No action if update is already running
								echo('Update of  '.$s.' database already running currently.' . $nl);
							}
							else
							{
								//Add new entry with status ready
								echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
								$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
								
								
								$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
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
							
							
							//Check if scheduled item has any heatmap reports
							$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
							$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item. Error: '.mysql_error());
							$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
							foreach($results as $row)
							{
								$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
								$res2 = mysql_query($query) or die('Bad SQL query getting heatmap report name. Error: '.mysql_error());
								$row2 = mysql_fetch_assoc($res2);
								if($row2 === false)
								{
									echo('Heatmap not found.' . $nl);
									continue;
								}
								$query = 'SELECT * FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
								$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
								$row3 = mysql_fetch_assoc($res3);
								if($row3['status']==READY)
								{
									echo('Entry to generate report already present - heatmap ' . $row['heatmap'] . $nl);
								}
								else if($row3['status']==RUNNING)
								{
									echo('Report already running - heatmap ' . $row['heatmap'] . $nl);
								}
								else if($row3['status']==CANCELLED)
								{
									//echo('Report requeued after cancellation - heatmap ' . $row['heatmap'] . $nl);
									echo ('Report was cancelled during previous execution - heatmap ' . $row['heatmap'] . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
								}
								else if($row3['status']==ERROR)
								{
									echo ('Report encountered an error during previous execution - heatmap ' . $row['heatmap'] . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
								}
								else
								{
									//Delete the previously completed report entry if it exists
									$query = 'DELETE FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
									$res5=mysql_query($query);
									if($res5==1)
										echo('Deleted previous entry to generate report - heatmap ' . $row['heatmap'] . $nl);
									
									//Add new entry with status ready
									$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	. '",type_id="' . $row['heatmap']	. '",report_type="0",status="'.READY.'"';
									$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
									echo('Adding entry to generate report - heatmap ' . $row['heatmap'] . $nl);
									
								}
							}
							
							
							//Check if scheduled item has any updatescan reports
							$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
							$res = mysql_query($query) or die('Bad SQL query getting updatescans for item. Error: '.mysql_error());
							$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
							foreach($results as $row)
							{
								$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
								$res2 = mysql_query($query) or die('Bad SQL query getting report name. Error: '.mysql_error());
								$row2 = mysql_fetch_assoc($res2);
								if($row2 === false)
								{
									echo('updatescan not found.' . $nl);
									continue;
								}
								$query = 'SELECT * FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
								$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
								$row3 = mysql_fetch_assoc($res3);
								if($row3['status']==READY)
								{
									echo('Entry to generate report already present - updatescan ' . $row['updatescan'] . $nl);
								}
								else if($row3['status']==RUNNING)
								{
									echo('Report already running - updatescan ' . $row['updatescan'] . $nl);
								}
								else if($row3['status']==CANCELLED)
								{
									//echo('Report requeued after cancellation - updatescan ' . $row['updatescan'] . $nl);
									echo('Report was cancelled during previous execution - updatescan ' . $row['updatescan'] . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
								}
								else if($row3['status']==ERROR)
								{
									echo ('Report encountered an error during previous execution - updatescan ' . $row['updatescan'] . $nl);
									echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
								}
								else
								{
									//Delete the previously completed report entry if it exists
									$query = 'DELETE FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
									$res5=mysql_query($query);
									if($res5==1)
										echo('Deleted previous entry to generate report - updatescan ' . $row['updatescan'] . $nl);
									
									//Add new entry with status ready
									$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	.'",type_id="' . $row['updatescan']. '",report_type="2",status="'.READY.'"';
									$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
									echo('Adding entry to generate report - updatescan ' . $row['updatescan'] . $nl);
								
								}
							}
						}
						echo('Done checking for scheduled reports.' . $nl);
					}
					else
						echo('No new scheduled reports.' . $nl);
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
						$update_status[$count++] = $row['status'];
						//Update flag which checks if all updates are complete
						if($row['status']!=COMPLETED)
							$all_updates_complete=0;
					}
						
					//Get all data from 'reports_status'
					$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status';
					$res = mysql_query($query) or die('Bad SQL Query getting reports_status');
					$run_status = array();
					$count=0;
					while($row = mysql_fetch_assoc($res))
						$run_status[$count++] = $row['status'];
					
					
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
								case 4:
								$updtname='calc_hm_cells'; 
								break;
								
							}
							if($updtname=='calc_hm_cells')
							{
								require_once('calculate_hm_cells.php');
								calc_cells(NULL,$run_updates[$i]['update_id']);
							}
							else
							{
								$filename = 'fetch_' . $updtname . '.php';
								echo('Invoking-: ' . $filename . '...</pre>' . $nl);
								$days_to_fetch=$run_updates[$i]['updated_days'];
								$update_id=$run_updates[$i]['update_id'];
								echo '<br>'.($filename). '<br>';
								echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
							}	
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
						$update_status[$count++] = $row['status'];
					
					
					//Get list of all reports(running and ready to run)
					$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status WHERE run_id='.$selected_schedule_item;
					$res = mysql_query($query) or die('Bad SQL Query getting report_status');
					$run_ids = array();
					$run_rpttype = array();
					$run_status = array();
					$count=0;
					while($row = mysql_fetch_assoc($res))
					{
						$run_ids[$count] = $row['run_id'];
						$run_typids[$count] = $row['type_id'];
						$run_rpttype[$count] = $row['report_type'];
						$run_status[$count++] = $row['status'];
					}
					
					if(!count($run_ids))
					{
						echo('No report scheduled.' . $nl);
						posix_kill(getmypid(),2);
					}
					else if(in_array(READY,$run_status))
					{
						//Find the ready to run report and break out
						$run_flag=0;
						for($i=0;$i< $count;$i++)
						{
							if($run_status[$i]==RUNNING)
								echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is already running.'. $nl);
							else if ($run_status[$i]==COMPLETED)
								echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' has finished running.'. $nl);
							else if($run_status[$i]==READY)
							{
								echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is ready to run.'. $nl);
								$current_run_item=$i;
								$run_flag=1;
								break;
							}
						}
						if($run_flag==0)
						{
							echo("All scheduled reports are currently running.");
							posix_kill(getmypid(),2);
						}
					}
					else
					{
						$run_flag=0;
						for($i=0;$i< $count;$i++)
						{
							if($run_status[$i]==RUNNING)
								$run_flag=1;
						}
						if($run_flag==1)
							echo('All reports are running.' . $nl);
						else
							echo('No report scheduled.' . $nl);
						
						posix_kill(getmypid(),2);
					}
					
					$files = array();
					$_GET['noheaders'] = 1;
					
					$run_id=$run_ids[$i];
					$report_type=$run_rpttype[$current_run_item];
					$type_id=$run_typids[$i];
					if($run_rpttype[$current_run_item]==0)
					{
						$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
						$res = mysql_query($query) or die('Bad SQL query getting report name');
						$row = mysql_fetch_assoc($res);
						
						$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
						$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to running');
						
						try{
								$files[$row['name']] = runHeatmap($run_typids[$current_run_item], true);
							}catch(Exception $e){
								$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
							}
							
						$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
						$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to done');
					}
					else if($run_rpttype[$current_run_item]==1)
					{
						$query = 'SELECT name FROM rpt_update WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
						$res = mysql_query($query) or die('Bad SQL query getting report name');
						$row = mysql_fetch_assoc($res);
						
						$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
						$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to running');
						
						try{
								$files[$row['name']] = runUpdateReport($run_typids[$current_run_item], true);
							}catch(Exception $e){
								$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
							}
							
						$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
						$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to done');
					}
					
					$query = 'SELECT name FROM schedule WHERE id='.$run_ids[$current_run_item];
					$res = mysql_query($query) or die('Bad SQL Query get schedule item name');
					$sch_item = mysql_fetch_assoc($res);
					
					echo('Report generated for - '.$rtype_name. ' ' . $run_ids[$current_run_item] . $nl);
					//Send mail with attached report
					$query = 'SELECT emails FROM schedule WHERE id=' . $run_ids[$current_run_item] . ' LIMIT 1';
					$res = mysql_query($query) or die('Bad SQL query getting report name');
					$item = mysql_fetch_assoc($res);
					if(count($files))
					{
						echo(' Sending to: ' . $item['emails'] . ' ... ');
						$mail = new PHPMailer();
						$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
						if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
						$emails = explode(',', $item['emails']);
						foreach($emails as $email) $mail->AddAddress($email);
						$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $sch_item['name']. ' ('.$row['name'].')';
						$mail->Body = 'Attached is the report ' . $row['name'].' indicated in the schedule item ' . $sch_item['name'];
						
						foreach($files as $fname => $file)
						{
							$mail->AddStringAttachment($file,
													   substr($fname,0,20).'_'.date('Y-m-d_H.i.s').'.xlsx',
													   'base64',
													   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
						}
						$mail->Send();
							echo(' -Mail sent.' . $nl);
					}
					else
					{
						echo(' -No files to send.' . $nl);
					}
					/************************************ Step 4 ****************************************/
					posix_kill(getmypid(),1);
				}
			}
		}
	}
	else
	{
		sleep(5);
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
				
				//Get Process IDs of all currently running reports
				$query = 'SELECT `run_id`,`type_id`,`report_type`,`process_id` FROM reports_status WHERE `status`='.RUNNING;
				$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
				$count_rpids=0;
				while($row = mysql_fetch_assoc($res))
				{
					$report_run_ids[$count_rpids] = $row['run_id'];
					$report_typ_ids[$count_rpids] = $row['type_id'];
					$report_rpt_typ[$count_rpids] = $row['report_type'];
					$report_pids[$count_rpids++] = $row['process_id'];
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
							case 4:
							$updtname='calc_hm_cells'; 
							break;
							
						}
						//Update status set to 'error'
						echo($updtname.' database updation error. Requeueing it.' . $nl);
						$query = 'UPDATE update_status SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
						$res = mysql_query($query) or die('Bad SQL Query setting update error status');
					}
				}
				
				//Check if any report has terminated abruptly
				for($i=0;$i < $count_rpids; $i++)
				{
					//If report_status is running and corresponding process ID is not running
					if(!in_array($report_pids[$i],$running_pids))
					{
						//Report status set to 'error'
						echo('Item ID '.$report_run_ids[$i].' - '.$rtype_name[$report_rpt_typ[$i]].' - '.$report_typ_ids[$i].' error. Requeueing it.' . $nl);
						$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
						$res = mysql_query($query) or die('Bad SQL Query setting report error status');
					}
				}
				/************************************ Step 1 ****************************************/
				
				
				
				/************************************ Step 2 ****************************************/
				echo($nl);
				echo ('Checking schedule for updates and reports...' . $nl);
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
					$update_status[$count++] = $row['status'];
				
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
							case 'calc_hm_cells':
							$updtid=4;
							break;
							
						}
						if($update_status[$count]==COMPLETED)
						{
							//Remove previous entry corresponding to completed update
							$query = 'DELETE FROM update_status WHERE update_id="' . $updtid.'"';
							$res = mysql_query($query) or die('Bad SQL query removing update_status entry. Error: '.mysql_error());
							if($res==1)
								echo('Removed previous entry for '.$s.$nl);
							
							//Add new entry with status ready
							echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
							$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
							
							
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
						}
						else if ($update_status[$count]==READY)
						{
							//Since entry with 'ready' status already exists, update it retaining the state
						echo('Refreshing entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
							$query = 'UPDATE update_status SET updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'" WHERE update_id="' . $updtid.'"';
						$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
						}
						else if ($update_status[$count]==CANCELLED)
						{
							echo('Update of  '.$s.' database already was cancelled during previous execution.' . $nl);
							echo ('Please add the update manaully from Status page to ensure it runs '. $nl);
						}
						else if ($update_status[$count]==ERROR)
						{
							//Since entry with 'error' status already exists, leave as is and inform user
							echo('Update of  '.$s.' database encountered error during previous execution......' . $nl);
							echo ('Please add the report manaully from Status page to ensure it runs--. '. $nl);
						}
						else if ($update_status[$count]==RUNNING)
						{
							//No action if update is already running
							echo('Update of  '.$s.' database already running currently.' . $nl);
						}
						else
						{
							//Add new entry with status ready
							echo('Adding entry to update '.$s.' database fetching records from previous '. (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).' days.' . $nl);
							$query = 'INSERT INTO update_status SET  update_items_progress="0", update_id="' . $updtid	.'",updated_days="' . (ceil(($now - $lastrun) / 60 / 60 / 24) + 2).'",status="'.READY.'"';
							
							
							$res = mysql_query($query) or die('Bad SQL query updating update_status. Error: '.mysql_error());
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
						
						
						//Check if scheduled item has any heatmap reports
						$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
						$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item. Error: '.mysql_error());
						$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
						foreach($results as $row)
						{
							$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
							$res2 = mysql_query($query) or die('Bad SQL query getting heatmap report name. Error: '.mysql_error());
							$row2 = mysql_fetch_assoc($res2);
							if($row2 === false)
							{
								echo('Heatmap not found.' . $nl);
								continue;
							}
							$query = 'SELECT * FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
							$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
							$row3 = mysql_fetch_assoc($res3);
							if($row3['status']==READY)
							{
								echo('Entry to generate report already present - heatmap ' . $row['heatmap'] . $nl);
							}
							else if($row3['status']==RUNNING)
							{
								echo('Report already running - heatmap ' . $row['heatmap'] . $nl);
							}
							else if($row3['status']==CANCELLED)
							{
								//echo('Report requeued after cancellation - heatmap ' . $row['heatmap'] . $nl);
								echo ('Report was cancelled during previous execution - heatmap ' . $row['heatmap'] . $nl);
								echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
							}
							else if($row3['status']==ERROR)
							{
								echo ('Report encountered an error during previous execution - heatmap ' . $row['heatmap'] . $nl);
								echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
							}
							else
							{
								//Delete the previously completed report entry if it exists
								$query = 'DELETE FROM reports_status WHERE report_type="0" AND run_id="' . $item['id']	. '" AND type_id="' . $row['heatmap']	. '"';
								$res5=mysql_query($query);
								if($res5==1)
									echo('Deleted previous entry to generate report - heatmap ' . $row['heatmap'] . $nl);
								
								//Add new entry with status ready
								$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	. '",type_id="' . $row['heatmap']	. '",report_type="0",status="'.READY.'"';
								$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
								echo('Adding entry to generate report - heatmap ' . $row['heatmap'] . $nl);
								
							}
						}
						
						//Check if scheduled item has any updatescan reports
						$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
						$res = mysql_query($query) or die('Bad SQL query getting updatescans for item. Error: '.mysql_error());
						$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
						foreach($results as $row)
						{
							$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
							$res2 = mysql_query($query) or die('Bad SQL query getting report name. Error: '.mysql_error());
							$row2 = mysql_fetch_assoc($res2);
							if($row2 === false)
							{
								echo('updatescan not found.' . $nl);
								continue;
							}
							$query = 'SELECT * FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
							$res3=mysql_query($query) or die('Bad SQL query getting report_status. Error: '.mysql_error());
							$row3 = mysql_fetch_assoc($res3);
							if($row3['status']==READY)
							{
								echo('Entry to generate report already present - updatescan ' . $row['updatescan'] . $nl);
							}
							else if($row3['status']==RUNNING)
							{
								echo('Report already running - updatescan ' . $row['updatescan'] . $nl);
							}
							else if($row3['status']==CANCELLED)
							{
								//echo('Report requeued after cancellation - updatescan ' . $row['updatescan'] . $nl);
								echo('Report was cancelled during previous execution - updatescan ' . $row['updatescan'] . $nl);
								echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
							}
							else if($row3['status']==ERROR)
							{
								echo ('Report encountered an error during previous execution - updatescan ' . $row['updatescan'] . $nl);
								echo ('Please add the report manaully from Status page to ensure it runs '. $nl);
							}
							else
							{
								//Delete the previously completed report entry if it exists
								$query = 'DELETE FROM reports_status WHERE report_type="2" AND run_id="' . $item['id']	. '" AND type_id="' . $row['updatescan']	. '"';
								$res5=mysql_query($query);
								if($res5==1)
									echo('Deleted previous entry to generate report - updatescan ' . $row['updatescan'] . $nl);
								
								//Add new entry with status ready
								$query = 'INSERT INTO reports_status SET run_id="' . $item['id']	.'",type_id="' . $row['updatescan']. '",report_type="2",status="'.READY.'"';
								$res4 = mysql_query($query) or die('Bad SQL query updating report_status. Error: '.mysql_error());
								echo('Adding entry to generate report - updatescan ' . $row['updatescan'] . $nl);
							
							}
						}
					}
					echo('Done checking for scheduled reports.' . $nl);
				}
				else
					echo('No new scheduled reports.' . $nl);
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
					$update_status[$count++] = $row['status'];
					//Update flag which checks if all updates are complete
					if($row['status']!=COMPLETED)
						$all_updates_complete=0;
				}
					
				//Get all data from 'reports_status'
				$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status';
				$res = mysql_query($query) or die('Bad SQL Query getting reports_status');
				$run_status = array();
				$count=0;
				while($row = mysql_fetch_assoc($res))
					$run_status[$count++] = $row['status'];
				
				
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
							case 4:
							$updtname='calc_hm_cells'; 
							break;
							
						}
						if($updtname=='calc_hm_cells')
						{
							require_once('calculate_hm_cells.php');
							calc_cells(NULL,$run_updates[$i]['update_id']);
						}
						else
						{
							//Start the update execution
							$filename = 'fetch_' . $updtname . '.php';
							echo('Invoking:: ' . $filename . '...</pre>' . $nl);
							$days_to_fetch=$run_updates[$i]['updated_days'];
							$update_id=$run_updates[$i]['update_id'];
							echo '<br>'.($filename). '<br>';
							echo($nl . '<pre>Done with ' . $filename . '.' . $nl);
						}	
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
					$update_status[$count++] = $row['status'];
				
				
				//Get list of all reports(running and ready to run)
				$query = 'SELECT `run_id`,`type_id`,`report_type`,`status` FROM reports_status WHERE run_id='.$selected_schedule_item;
				$res = mysql_query($query) or die('Bad SQL Query getting report_status');
				$run_ids = array();
				$run_rpttype = array();
				$run_status = array();
				$count=0;
				while($row = mysql_fetch_assoc($res))
				{
					$run_ids[$count] = $row['run_id'];
					$run_typids[$count] = $row['type_id'];
					$run_rpttype[$count] = $row['report_type'];
					$run_status[$count++] = $row['status'];
				}
				
				if(!count($run_ids))
				{
					echo('No report scheduled.' . $nl);
					posix_kill(getmypid(),2);
				}
				else if(in_array(READY,$run_status))
				{
					//Find the ready to run report and break out
					$run_flag=0;
					for($i=0;$i< $count;$i++)
					{
						if($run_status[$i]==RUNNING)
							echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is already running.'. $nl);
						else if ($run_status[$i]==COMPLETED)
							echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' has finished running.'. $nl);
						else if($run_status[$i]==READY)
						{
							echo('Item ID '.$run_ids[$i].' - '.$rtype_name[$run_rpttype[$i]].' - '.$run_typids[$i].' is ready to run.'. $nl);
							$current_run_item=$i;
							$run_flag=1;
							break;
						}
					}
					if($run_flag==0)
					{
						echo("All scheduled reports are currently running.");
						posix_kill(getmypid(),2);
					}
				}
				else
				{
					$run_flag=0;
					for($i=0;$i< $count;$i++)
					{
						if($run_status[$i]==RUNNING)
							$run_flag=1;
					}
					if($run_flag==1)
						echo('All reports are running.' . $nl);
					else
						echo('No report scheduled.' . $nl);
					
					posix_kill(getmypid(),2);
				}
				
				$files = array();
				$_GET['noheaders'] = 1;
				
				$run_id=$run_ids[$i];
				$report_type=$run_rpttype[$current_run_item];
				$type_id=$run_typids[$i];
				if($run_rpttype[$current_run_item]==0)
				{
					$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
					$res = mysql_query($query) or die('Bad SQL query getting report name');
					$row = mysql_fetch_assoc($res);
					
					$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
					$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to running');
					
					try{
							$files[$row['name']] = runHeatmap($run_typids[$current_run_item], true);
						}catch(Exception $e){
							$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
						}
						
					$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="0" AND type_id="' .$run_typids[$current_run_item] .'"';
					$res = mysql_query($query) or die('Bad SQL Query updating heatmap report status to done');
				}
				else if($run_rpttype[$current_run_item]==1)
				{
					$query = 'SELECT name FROM rpt_update WHERE id=' . $run_typids[$current_run_item] . ' LIMIT 1';
					$res = mysql_query($query) or die('Bad SQL query getting report name');
					$row = mysql_fetch_assoc($res);
					
					$query = 'UPDATE reports_status SET start_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.RUNNING.'", process_id="'.$pid.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
					$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to running');
					
					try{
							$files[$row['name']] = runUpdateReport($run_typids[$current_run_item], true);
						}catch(Exception $e){
							$files[$row['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
						}
						
					$query = 'UPDATE reports_status SET update_time="' . date("Y-m-d H:i:s",strtotime('now')).'",complete_time="' . date("Y-m-d H:i:s",strtotime('now')).'",status="'.COMPLETED.'" WHERE run_id="' .$run_ids[$current_run_item] .'" AND report_type ="2" AND type_id="' .$run_typids[$current_run_item] .'"';
					$res = mysql_query($query) or die('Bad SQL Query updating updatscan report status to done');
				}
				
				$query = 'SELECT name FROM schedule WHERE id='.$run_ids[$current_run_item];
				$res = mysql_query($query) or die('Bad SQL Query get schedule item name');
				$sch_item = mysql_fetch_assoc($res);
				
				echo('Report generated for - '.$rtype_name. ' ' . $run_ids[$current_run_item] . $nl);
				//Send mail with attached report
				$query = 'SELECT emails FROM schedule WHERE id=' . $run_ids[$current_run_item] . ' LIMIT 1';
				$res = mysql_query($query) or die('Bad SQL query getting report name');
				$item = mysql_fetch_assoc($res);
				if(count($files))
				{
					echo(' Sending to: ' . $item['emails'] . ' ... ');
					$mail = new PHPMailer();
					$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
					if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
					$emails = explode(',', $item['emails']);
					foreach($emails as $email) $mail->AddAddress($email);
					$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $sch_item['name']. ' ('.$row['name'].')';
					$mail->Body = 'Attached is the report ' . $row['name'].' indicated in the schedule item ' . $sch_item['name'];
					
					foreach($files as $fname => $file)
					{
						$mail->AddStringAttachment($file,
												   substr($fname,0,20).'_'.date('Y-m-d_H.i.s').'.xlsx',
												   'base64',
												   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
					}
					$mail->Send();
						echo(' -Mail sent.' . $nl);
				}
				else
				{
					echo(' -No files to send.' . $nl);
				}
				/************************************ Step 4 ****************************************/
				posix_kill(getmypid(),1);
			}
		}
	}
}
/************************************ Step B ****************************************/
?>