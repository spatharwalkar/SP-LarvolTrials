#!/usr/bin/php -q
<?php
/* Grab lock file to ensure that only one instance of this script runs at once.
Otherwise, long-running reports (etc) could make it stack up multiple copies of the scheduler doing the same tasks.
*/
$ex = file_exists('cache/cronlock');
if(!$ex) file_put_contents('cache/cronlock','1');
$fp = fopen('cache/cronlock', "r+");
if(!flock($fp, LOCK_EX | LOCK_NB))	die("Previous instance still running from previous invocation. Terminating this one.");

require_once('db.php');
require_once('run_updatereport.php');
require_once('run_heatmap.php');
require_once('run_competitor.php');

require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once('class.phpmailer.php');

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
$now = strtotime('now');
echo($nl . '<html><pre>Running main schedule executor.' . $nl . 'Current time ' . date('Y-m-d H:i:s', $now) . $nl
		. 'Checking which schedule items need to run...' . $nl);
mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
$schedule = array();
$fetch = array();
$query = 'SELECT `id`,`name`,`fetch`,`runtimes`,`lastrun`,`emails` FROM schedule WHERE runtimes!=0';
$res = mysql_query($query) or die('Bad SQL Query getting schedule');
$tasks = array(); while($row = mysql_fetch_assoc($res)) $tasks[] = $row;
foreach($tasks as $row)	//determine what has to run
{
	$lastrun = strtotime($row['lastrun']);
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
				$due = true;
				break 2;
			}
		}
	}
	echo(str_pad('Item ' . $row['id'] . ': ' . $row['name'], 50) . ' - ');
	if($due)
	{
		echo('Yes');
		$schedule[] = $row;
		if($row['fetch'] != 'none')
		{
			echo('+' . $row['fetch']);
			if(!isset($fetch[$row['fetch']]) || $fetch[$row['fetch']] < $lastrun)
				$fetch[$row['fetch']] = $lastrun;
		}
	}else{
		echo('No');
	}
	echo($nl);
}
if(!count($fetch))
{
	echo('Not updating the database on this run.' . $nl);
}else{
	echo('Will update database as per above records that have a + sign.' . $nl);
	$fetchers = $fetch;
	foreach($fetchers as $s => $lastrun)	//run database updaters
	{
		$filename = 'fetch_' . $s . '.php';
		echo('Invoking ' . $filename . '...</pre>' . $nl);
		$_GET['days'] = max(ceil(($now-$lastrun)/60/60/24)+2, 30);
		require_once($filename);
		echo($nl . '<pre>Done with ' . $filename . '.' . $nl);		
	}
	echo($nl . 'Done with updates. Continuing.' . $nl);
}
echo('Running scheduled reports...' . $nl);
$_GET['noheaders'] = 1;
foreach($schedule as $item)
{
	$query = 'UPDATE schedule SET lastrun="' . date("Y-m-d H:i:s",$now) . '" WHERE id=' . $item['id'] . ' LIMIT 1';
	mysql_query($query) or die('Bad SQL query setting lastrun');
	$files = array();
	echo('Checking for reports for item ' . $item['id'] . $nl);
	
	$query = 'SELECT heatmap FROM schedule_heatmaps WHERE schedule=' . $item['id'];
	$res = mysql_query($query) or die('Bad SQL query getting heatmaps for item');
	$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
	foreach($results as $row)
	{
		echo('Item indicates heatmap ' . $row['heatmap'] . $nl);
		$query = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['heatmap'] . ' LIMIT 1';
		$res2 = mysql_query($query) or die('Bad SQL query getting report name');
		$row2 = mysql_fetch_assoc($res2);
		if($row2 === false)
		{
			echo('Heatmap not found.' . $nl);
			continue;
		}
		
		try{
			$files[$row2['name']] = runHeatmap($row['heatmap'], true);
		}catch(Exception $e){
			$files[$row2['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
		}
	}
	
	$query = 'SELECT competitor FROM schedule_competitor WHERE schedule=' . $item['id'];
	$res = mysql_query($query) or die('Bad SQL query getting competitordashboards for item | ' . mysql_error() . ' | ' . $query);
	$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
	foreach($results as $row)
	{
		echo('Item indicates CD ' . $row['competitor'] . $nl);
		$query = 'SELECT name FROM rpt_competitor WHERE id=' . $row['competitor'] . ' LIMIT 1';
		$res2 = mysql_query($query) or die('Bad SQL query getting report name');
		$row2 = mysql_fetch_assoc($res2);
		if($row2 === false)
		{
			echo('CD not found.' . $nl);
			continue;
		}
		
		try{
			$files[$row2['name']] = runCompetitor($row['competitor'], true);
		}catch(Exception $e){
			$files[$row2['name']] = messageInExcel('Report failed with message: ' . $e->getMessage());
		}
	}
	
	$query = 'SELECT updatescan FROM schedule_updatescans WHERE schedule=' . $item['id'];
	$res = mysql_query($query) or die('Bad SQL query getting updatescans for item');
	$results = array();	while($row = mysql_fetch_assoc($res)) $results[] = $row;
	foreach($results as $row)
	{
		echo('Item indicates updatescan ' . $row['updatescan'] . $nl);
		$query = 'SELECT name FROM rpt_update WHERE id=' . $row['updatescan'] . ' LIMIT 1';
		$res2 = mysql_query($query) or die('Bad SQL query getting report name');
		$row2 = mysql_fetch_assoc($res2);
		if($row2 === false)
		{
			echo('updatescan not found.' . $nl);
			continue;
		}
		
		$files[$row2['name']] = runUpdateReport($row['updatescan'], true);
	}
	
	echo('Reports collected for item.');
	if(count($files))
	{
		echo(' Sending to: ' . $item['emails'] . ' ... ');
		$mail = new PHPMailer();
		$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
		if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
		$emails = explode(',', $item['emails']);
		foreach($emails as $email) $mail->AddAddress($email);
		$mail->Subject = SITE_NAME . ' scheduled reports ' . date("Y-m-d H.i.s") . ' - ' . $item['name'];
		$mail->Body = 'Attached are all reports indicated in the schedule item ' . $item['name'];
		foreach($files as $fname => $file)
		{
			$mail->AddStringAttachment($file,
									   substr($fname,0,20).'_'.date('Y-m-d_H.i.s').'.xlsx',
									   'base64',
									   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');		
		}
		@$mail->Send();
		echo(' -Mail sent.' . $nl);
	}else{
		echo(' -No files to send.' . $nl);
	}
}
echo('Done with everything.' . $nl);
mysql_query('COMMIT') or die("Couldn't end SQL transaction");
echo('</pre></html>');
?>