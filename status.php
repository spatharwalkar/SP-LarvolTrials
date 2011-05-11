<?php
require_once('db.php');
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

//Extra javascript and css for status page
$_GET['header']='<script type="text/javascript" src="progressbar/jquery.js"></script>
<script type="text/javascript" src="progressbar/jquery.progressbar.js"></script>
<link href="css/status.css" rel="stylesheet" type="text/css" media="all" />
';
require('header.php');

//Definition of constants for states
define('READY', 1);
define('RUNNING', 2);
define('ERROR', 3);
define('CANCELLED', 4);
define('COMPLETED', 0);

if(isset($_GET['pid']))
{
	if(isset($_GET['upid']))
	{
		if($_GET['action']==1)
		{
			$query = 'UPDATE update_status SET status="'.READY.'" WHERE update_id="' . $_GET['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update ready status');
		}
		else if($_GET['action']==2)
		{
			$cmd = "kill ".$_GET['pid'];
			exec($cmd, $output, $result);
			
			$query = 'UPDATE update_status SET status="'.CANCELLED.'" WHERE update_id="' . $_GET['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update cancelled status');
		}
		else if($_GET['action']==3)
		{
			$query = 'DELETE FROM update_status WHERE update_id="' . $_GET['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query deleting update status');
		}		
	}
	else if(isset($_GET['runid']))
	{
		if($_GET['action']==1)
		{
			$query = 'UPDATE reports_status SET status="'.READY.'" WHERE process_id="'.$_GET['pid'].'" AND run_id="' . $_GET['runid'].'" AND report_type="' . $_GET['rpttyp'].'" AND type_id="' . $_GET['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting report error status');
		}
		else if($_GET['action']==2)
		{
			$cmd = "kill ".$_GET['pid'];
			exec($cmd, $output, $result);
			
			$query = 'UPDATE reports_status SET status="'.CANCELLED.'" WHERE process_id="'.$_GET['pid'].'" AND run_id="' . $_GET['runid'].'" AND report_type="' . $_GET['rpttyp'].'" AND type_id="' . $_GET['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting report cancelled status');
		}
		else if($_GET['action']==3)
		{
			$query = 'DELETE FROM reports_status WHERE process_id="'.$_GET['pid'].'" AND run_id="' . $_GET['runid'].'" AND report_type="' . $_GET['rpttyp'].'" AND type_id="' . $_GET['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query deleting from report status');
		}
	}
}
else
{
	if(isset($_GET['upid']))
	{
		if($_GET['action']==4)
		{			
			$query = 'UPDATE update_status SET status="'.CANCELLED.'" WHERE update_id="' . $_GET['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update cancelled status');
		}
	}
	elseif(isset($_GET['runid']))
	{
		if($_GET['action']==4)
		{
			$query = 'UPDATE reports_status SET status="'.CANCELLED.'" WHERE run_id="' . $_GET['runid'].'" AND report_type="' . $_GET['rpttyp'].'" AND type_id="' . $_GET['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting report cancelled status');
		}
	}
}

$status = array();
//Definition of constants for states
$status[0]="Completed";
$status[1]="Ready";
$status[2]="Running";
$status[3]="Error";
$status[4]="Cancelled";


//Check for crashed updates/reports before displaying the status 
		
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
		$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $report_run_ids[$i].'" AND report_type="' . $report_rpt_typ[$i].'" AND type_id="' . $report_typ_ids[$i].'"';
		//$res = mysql_query($query) or die('Bad SQL Query setting report error status');
	}
}

		
//Get entry corresponding to nct in 'update_status'
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE update_id="0"';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$nct_status = array();
while($row = mysql_fetch_assoc($res))
	$nct_status = $row;
	

//Get entry corresponding to pubmed in 'update_status'
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE update_id="1"';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$pubmed_status = array();
while($row = mysql_fetch_assoc($res))
	$pubmed_status = $row;
	
//Get all heatmap reports from 'report_status'
$query = 'SELECT `run_id`,`process_id`,`report_type`,`type_id`,`status`,`total`,`progress`,
					`start_time`,`update_time`,TIMEDIFF(update_time, start_time) AS timediff FROM reports_status WHERE report_type="0"';
$res = mysql_query($query) or die('Bad SQL Query getting report_status');
$heatmap_status = array();
while($row = mysql_fetch_assoc($res))
	$heatmap_status[] = $row;
	

//Get all update scan reports from 'report_status'
$query = 'SELECT `run_id`,`process_id`,`report_type`,`type_id`,`status`,`total`,`progress`,
					`start_time`,`update_time`,TIMEDIFF(update_time, start_time) AS timediff FROM reports_status WHERE report_type="2"';
$res = mysql_query($query) or die('Bad SQL Query getting report_status');
$updatescan_status = array();
while($row = mysql_fetch_assoc($res))
	$updatescan_status[] = $row;

/*
//Get all Competitor Dashboard reports from 'report_status'
$query = 'SELECT `run_id`,`process_id`,`report_type`,`type_id`,`status`,`total`,`progress`,
					`start_time`,`update_time`,TIMEDIFF(update_time, start_time) AS timediff FROM reports_status WHERE report_type="1"';
$res = mysql_query($query) or die('Bad SQL Query getting report_status');
$comdash_status = array();
while($row = mysql_fetch_assoc($res))
	$comdash_status[] = $row;
*/

//Get scheduler item names
$query = 'SELECT id,name FROM `schedule`;';
$res = mysql_query($query) or die('Bad SQL Query getting report_status');
$schedule_item = array();
while($row = mysql_fetch_assoc($res))
	$schedule_item[$row['id']] = $row['name'];
	

//Add javascript for each progress bar that has to be shown
echo "<script type=\"text/javascript\">";

echo "$(document).ready(function() {";
if(count($nct_status)!=0)
{
	echo "$(\"#nct_new\").progressBar();";
	echo "$(\"#nct_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}
if(count($pubmed_status)!=0)
{
	echo "$(\"#pubmed_new\").progressBar();";
	echo "$(\"#pubmed_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}
for($i=0;$i < count($heatmap_status);$i++)
{
	echo "$(\"#heatmap$i\").progressBar({ barImage: 'images/progressbg_red.gif'} );";
}
for($i=0;$i < count($updatescan_status);$i++)
{
	echo "$(\"#updatescan$i\").progressBar({ barImage: 'images/progressbg_black.gif'} );";
}
/*
for($i=0;$i < count($comdash_status);$i++)
{
	echo "$(\"#comdash$i\").progressBar({ barImage: 'images/progressbg_yellow.gif'} );";
}
*/
echo "});";

echo "</script>";
	
echo "<div class=\"container\">";
	echo "<table width=\"100%\" class=\"event\">";
		echo "<tr>";
			echo "<th width=\"100%\" align=\"center\" class=\"head1\" >Updates</th>";
		echo "</tr>";
	echo "</table>";
	if(count($nct_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">nct database</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"10%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"16%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"16%\" align=\"left\" class=\"head\">Last update time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Update Records</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$nct_status['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$nct_status['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$nct_status['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$nct_status['updated_time']."</td>";
				if($nct_status['add_items_start_time']!="0000-00-00 00:00:00"&&$nct_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$nct_status['status']==COMPLETED)
					$nct_add_progress=100;
				else
					$nct_add_progress=number_format(($nct_status['add_items_total']==0?0:(($nct_status['add_items_progress'])*100/$nct_status['add_items_total'])),2);
					
				if($nct_status['update_items_start_time']!="0000-00-00 00:00:00"&&$nct_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$nct_status['status']==COMPLETED)
					$nct_update_progress=100;
				else
					$nct_update_progress=number_format(($nct_status['update_items_total']==0?0:(($nct_status['update_items_progress'])*100/$nct_status['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"nct_update\">".$nct_update_progress."</span>";
				echo "</td>";
				if($nct_status['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<a href="status.php?action=4&amp;upid='.$nct_status['update_id']. 
					'"><img src="images/not.png" title="Cancel" border=0/></a>';
					echo "</td>";
				}
				elseif($nct_status['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<a href="status.php?action=2&amp;upid='.$nct_status['update_id'].'&amp;pid=' . $nct_status['process_id'] . 
					'"><img src="images/not.png" title="Cancel" border=0/></a>';
					echo "</td>";
				}
				elseif($nct_status['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<a href="status.php?action=3&amp;upid='.$nct_status['update_id'].'&amp;pid=' . $nct_status['process_id'] . 
					'"><img src="images/not.png" title="Delete" border=0/></a>';
					echo "</td>";
				}
				else if($nct_status['status']==ERROR||$nct_status['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<a href="status.php?action=1&amp;upid='.$nct_status['update_id'].'&amp;pid=' . $nct_status['process_id'] . 
					'"><img src="images/check.png" title="Add" border=0/></a> ';
					echo '<a href="status.php?action=3&amp;upid='.$nct_status['update_id'].'&amp;pid=' . $nct_status['process_id'] . 
					'"><img src="images/not.png" title="Delete" border=0/></a>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	if(count($pubmed_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >pubmed database</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"10%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"16%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"16%\" align=\"left\" class=\"head\">Last update time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Update Records</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			if($pubmed_status['add_items_start_time']!="0000-00-00 00:00:00"&&$pubmed_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$pubmed_status['status']==COMPLETED)
					$pubmed_add_progress=100;
			else
				$pubmed_add_progress=number_format(($pubmed_status['add_items_total']==0?"0":(($pubmed_status['add_items_progress'])*100/$pubmed_status['add_items_total'])),2);
				
			if($pubmed_status['update_items_start_time']!="0000-00-00 00:00:00"&&$pubmed_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$pubmed_status['status']==COMPLETED)
				$pubmed_update_progress=100;
			else
				$pubmed_update_progress=number_format(($pubmed_status['update_items_total']==0?"0":(($pubmed_status['update_items_progress'])*100/$pubmed_status['update_items_total'])),2);
			
			echo "<tr>";
			echo "<td align=\"left\" class=\"norm\">".$status[$pubmed_status['status']]."</td>";
			echo "<td align=\"left\" class=\"norm\">".$pubmed_status['start_time']."</td>";
			echo "<td align=\"left\" class=\"norm\">".$pubmed_status['timediff']."</td>";
			echo "<td align=\"left\" class=\"norm\">".$pubmed_status['updated_time']."</td>";
			echo "<td align=\"left\" class=\"norm\">";
				echo "<span class=\"progressBar\" id=\"pubmed_new\">".$pubmed_add_progress."%</span>";
			echo "</td>";
			echo "<td align=\"left\" class=\"norm\">";
				echo "<span class=\"progressBar\" id=\"pubmed_update\">".$pubmed_update_progress."%</span>";
			echo "</td>";
			if($pubmed_status['status']==READY)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<a href="status.php?action=4&amp;upid='.$pubmed_status['update_id'].
				'"><img src="images/not.png" title="Cancel" border=0/></a>';
				echo "</td>";
			}
			elseif($pubmed_status['status']==RUNNING)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<a href="status.php?action=2&amp;upid='.$pubmed_status['update_id'].'&amp;pid=' . $pubmed_status['process_id'] . 
				'"><img src="images/not.png" title="Cancel" border=0/></a>';
				echo "</td>";
			}
			elseif($pubmed_status['status']==COMPLETED)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<a href="status.php?action=3&amp;upid='.$pubmed_status['update_id'].'&amp;pid=' . $pubmed_status['process_id'] . 
				'"><img src="images/not.png" title="Delete" border=0/></a>';
				echo "</td>";
			}
			else if($pubmed_status['status']==ERROR||$pubmed_status['status']==CANCELLED)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<a href="status.php?action=1&amp;upid='.$pubmed_status['update_id'].'&amp;pid=' . $pubmed_status['process_id'] . 
				'"><img src="images/check.png" title="Add" border=0/></a> ';
				echo '<a href="status.php?action=3&amp;upid='.$pubmed_status['update_id'].'&amp;pid=' . $pubmed_status['process_id'] . 
				'"><img src="images/not.png" title="Delete" border=0/></a>';
				echo "</td>";
			}
			echo "</tr>";
		echo "</table>";
	}
			
	echo "<br/>";
	echo "<br/>";
	
	echo "<table width=\"100%\" class=\"event\">";
		echo "<tr>";
			echo "<th width=\"100%\" align=\"center\" class=\"head1\" >Reports</th>";
		echo "</tr>";
	echo "</table>";
	if(count($heatmap_status)>0)
	{				
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >Heatmap</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"10%\" align=\"left\" class=\"head\">Scheduler Item</td>";
				echo "<td width=\"9%\" align=\"left\" class=\"head\">Item ID</td>";
				echo "<td width=\"9%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Last update time</td>";
				echo "<td width=\"18%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
				
			for($i=0;$i < count($heatmap_status);$i++)
			{
				echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$schedule_item[($heatmap_status[$i]['run_id'])]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$heatmap_status[$i]['type_id']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$status[$heatmap_status[$i]['status']]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$heatmap_status[$i]['start_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$heatmap_status[$i]['timediff']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$heatmap_status[$i]['update_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">";
						echo "<span class=\"progressBar\" id=\"heatmap$i\">".number_format(($heatmap_status[$i]['total']==0?"0":(($heatmap_status[$i]['progress'])*100/$heatmap_status[$i]['total'])),2)."%</span>";
					echo "</td>";
					echo "<td align=\"center\" class=\"norm\">";
					if($heatmap_status[$i]['status']==READY)
					{
						echo '<a href="status.php?action=4&amp;runid='.$heatmap_status[$i]['run_id'].'&amp;typeid='.$heatmap_status[$i]['type_id'].'&amp;rpttyp='.$heatmap_status[$i]['report_type']. '">';
						echo '<img src="images/not.png" title="Cancel" border=0/></a>';
					}
					elseif($heatmap_status[$i]['status']==RUNNING)
					{
						echo '<a href="status.php?action=2&amp;runid='.$heatmap_status[$i]['run_id'].'&amp;typeid='.$heatmap_status[$i]['type_id'].'&amp;rpttyp='.$heatmap_status[$i]['report_type'].
						'&amp;pid=' . $heatmap_status[$i]['process_id'] . '">';
						echo '<img src="images/not.png" title="Cancel" border=0/></a>';
					}
					elseif($heatmap_status[$i]['status']==COMPLETED)
					{
						echo '<a href="status.php?action=3&amp;runid='.$heatmap_status[$i]['run_id'].'&amp;typeid='.$heatmap_status[$i]['type_id'].'&amp;rpttyp='.$heatmap_status[$i]['report_type'].
						'&amp;pid=' . $heatmap_status[$i]['process_id'] . '">';
						echo '<img src="images/not.png" title="Delete" border=0/></a>';
					}
					else if($heatmap_status[$i]['status']==ERROR||$heatmap_status[$i]['status']==CANCELLED)
					{
						echo '<a href="status.php?action=1&amp;runid='.$heatmap_status[$i]['run_id'].'&amp;typeid='.$heatmap_status[$i]['type_id'].'&amp;rpttyp='.$heatmap_status[$i]['report_type'].
						'&amp;pid=' . $heatmap_status[$i]['process_id'] . '">';
						echo '<img src="images/check.png" title="Add" border=0/></a> ';
						echo '<a href="status.php?action=3&amp;runid='.$heatmap_status[$i]['run_id'].'&amp;typeid='.$heatmap_status[$i]['type_id'].'&amp;rpttyp='.$heatmap_status[$i]['report_type'].
						'&amp;pid=' . $heatmap_status[$i]['process_id'] . '">';
						echo '<img src="images/not.png" title="Delete" border=0/></a>';
					}
					echo "</td>";
				echo "</tr>";
			}
		echo "</table>";
	}
	if(count($updatescan_status)>0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >Update Scan</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"10%\" align=\"left\" class=\"head\">Scheduler Item</td>";
				echo "<td width=\"9%\" align=\"left\" class=\"head\">Item ID</td>";
				echo "<td width=\"9%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Last update time</td>";
				echo "<td width=\"18%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
				
			for($i=0;$i < count($updatescan_status);$i++)
			{
				echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$schedule_item[($updatescan_status[$i]['run_id'])]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$updatescan_status[$i]['type_id']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$status[$updatescan_status[$i]['status']]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$updatescan_status[$i]['start_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$updatescan_status[$i]['timediff']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$updatescan_status[$i]['update_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">";
						echo "<span class=\"progressBar\" id=\"updatescan$i\">".number_format(($updatescan_status[$i]['total']==0?"0":(($updatescan_status[$i]['progress'])*100/$updatescan_status[$i]['total'])),2)."%</span>";
					echo "</td>";
					echo "<td align=\"center\" class=\"norm\">";
					if($updatescan_status[$i]['status']==READY)
					{
						echo '<a href="status.php?action=4&amp;runid='.$updatescan_status[$i]['run_id'].'&amp;typeid='.$updatescan_status[$i]['type_id'].'&amp;rpttyp='.$updatescan_status[$i]['report_type'].'"><img src="images/not.png" title="Cancel" border=0/></a>';
					}
					elseif($updatescan_status[$i]['status']==RUNNING)
					{
						echo '<a href="status.php?action=2&amp;runid='.$updatescan_status[$i]['run_id'].'&amp;typeid='.$updatescan_status[$i]['type_id'].'&amp;rpttyp='.$updatescan_status[$i]['report_type'].
						'&amp;pid=' . $updatescan_status[$i]['process_id'] . '"><img src="images/not.png" title="Cancel" border=0/></a>';
					}
					elseif($updatescan_status[$i]['status']==COMPLETED)
					{
						echo '<a href="status.php?action=3&amp;runid='.$updatescan_status[$i]['run_id'].'&amp;typeid='.$updatescan_status[$i]['type_id'].'&amp;rpttyp='.$updatescan_status[$i]['report_type'].
						'&amp;pid=' . $updatescan_status[$i]['process_id'] . '">';
						echo '<img src="images/not.png" title="Delete" border=0/></a>';
					}
					else if($updatescan_status[$i]['status']==ERROR||$updatescan_status[$i]['status']==CANCELLED)
					{
						echo '<a href="status.php?action=1&amp;runid='.$updatescan_status[$i]['run_id'].'&amp;typeid='.$updatescan_status[$i]['type_id'].'&amp;rpttyp='.$updatescan_status[$i]['report_type'].
						'&amp;pid=' . $updatescan_status[$i]['process_id'] . '">';
						echo '<img src="images/check.png" title="Add" border=0/></a> ';
						echo '<a href="status.php?action=3&amp;runid='.$updatescan_status[$i]['run_id'].'&amp;typeid='.$updatescan_status[$i]['type_id'].'&amp;rpttyp='.$updatescan_status[$i]['report_type'].
						'&amp;pid=' . $updatescan_status[$i]['process_id'] . '">';
						echo '<img src="images/not.png" title="Delete" border=0/></a>';
					}
					echo "</td>";
				echo "</tr>";
			}
		echo "</table>";
	}
	/*
	if(count($comdash_status)>0)
	{
		$cd_running=0;
		for($i=0;$i < count($comdash_status);$i++)
		{
			if($comdash_status[$i]['status']==RUNNING)
				$cd_running=1;
		}
		
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >Competitor Dashboard</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"10%\" align=\"left\" class=\"head\">Scheduler Item</td>";
				echo "<td width=\"9%\" align=\"left\" class=\"head\">Item ID</td>";
				echo "<td width=\"9%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Last update time</td>";
				echo "<td width=\"18%\" align=\"left\" class=\"head\">Progress</td>";
				if($cd_running==1)
					echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
				
			for($i=0;$i < count($comdash_status);$i++)
			{
				if($comdash_status[$i]['status']==READY)
				{
					echo "<tr>";
						echo "<td align=\"left\" class=\"norm\">".$schedule_item[($comdash_status[$i]['run_id'])]."</td>";
						echo "<td align=\"left\" class=\"norm\">".$comdash_status[$i]['type_id']."</td>";
						echo "<td align=\"left\" class=\"norm\">".$status[$comdash_status[$i]['status']]."</td>";
						echo "<td align=\"left\" class=\"norm\">NA</td>";
						echo "<td align=\"left\" class=\"norm\">NA</td>";
						echo "<td align=\"left\" class=\"norm\">NA</td>";
						echo "<td align=\"left\" class=\"norm\">";
							echo "<span class=\"progressBar\" id=\"comdash$i\">0%</span>";
						echo "</td>";
						if($comdash_status[$i]['status']!=RUNNING)
						{
							echo "<td align=\"center\" class=\"norm\"></td>";
						}
					echo "</tr>";
				}
				else
				{
					echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$schedule_item[($comdash_status[$i]['run_id'])]."</td>";
						echo "<td align=\"left\" class=\"norm\">".$comdash_status[$i]['type_id']."</td>";
						echo "<td align=\"left\" class=\"norm\">".$status[$comdash_status[$i]['status']]."</td>";
						echo "<td align=\"left\" class=\"norm\">".$comdash_status[$i]['start_time']."</td>";
						echo "<td align=\"left\" class=\"norm\">".$comdash_status[$i]['timediff']."</td>";
						echo "<td align=\"left\" class=\"norm\">".$comdash_status[$i]['update_time']."</td>";
						echo "<td align=\"left\" class=\"norm\">";
							echo "<span class=\"progressBar\" id=\"comdash$i\">".number_format(($comdash_status[$i]['total']==0?"0":(($comdash_status[$i]['progress'])*100/$comdash_status[$i]['total'])),2)."%</span>";
						echo "</td>";
						echo "<td align=\"center\" class=\"norm\">";
						if($comdash_status[$i]['status']==RUNNING)
						{
							echo '<a href="status.php?runid='.$comdash_status[$i]['run_id'].'&amp;typeid='.$comdash_status[$i]['type_id'].'&amp;rpttyp='.$comdash_status[$i]['report_type'].
							'&amp;pid=' . $comdash_status[$i]['process_id'] . '"><img src="images/not.png" title="Cancel"/></a>';
						}
						echo "</td>";
					echo "</tr>";
				}
			}
		echo "</table>";
	}
	*/
echo "</div>";
echo "</body>";
echo "</html>";
?>