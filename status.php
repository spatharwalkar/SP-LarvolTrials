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

if(isset($_POST['pid']))
{
	if(isset($_POST['upid']))
	{
		if($_POST['action']==1)
		{
			$query = 'UPDATE update_status SET status="'.READY.'" WHERE update_id="' . $_POST['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update ready status');
			
			//status for preindexing
				if( $_POST['ttype']=="area" )
				{
					$_GET['productid']=0;
					require('preindex_trials_all.php');
				}
				elseif( $_POST['ttype']=="product" )
				{
					$_GET['areaid']=0;
					require('preindex_trials_all.php');
				}
			//	
				
		}
		else if($_POST['action']==2)
		{
			$cmd = "kill ".$_POST['pid'];
			exec($cmd, $output, $result);
			
			$query = 'UPDATE update_status SET status="'.CANCELLED.'" WHERE update_id="' . $_POST['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update cancelled status');
		}
		else if($_POST['action']==3)
		{
			$query = 'DELETE FROM update_status WHERE update_id="' . $_POST['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query deleting update status');
		}		
	}
	else if(isset($_POST['runid']))
	{
		if($_POST['action']==1)
		{
			$query = 'UPDATE reports_status SET status="'.READY.'" WHERE process_id="'.$_POST['pid'].'" AND run_id="' . $_POST['runid'].'" AND report_type="' . $_POST['rpttyp'].'" AND type_id="' . $_POST['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting report error status');
		}
		else if($_POST['action']==2)
		{
			$cmd = "kill ".$_POST['pid'];
			exec($cmd, $output, $result);
			
			$query = 'UPDATE reports_status SET status="'.CANCELLED.'" WHERE process_id="'.$_POST['pid'].'" AND run_id="' . $_POST['runid'].'" AND report_type="' . $_POST['rpttyp'].'" AND type_id="' . $_POST['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting report cancelled status');
		}
		else if($_POST['action']==3)
		{
			$query = 'DELETE FROM reports_status WHERE process_id="'.$_POST['pid'].'" AND run_id="' . $_POST['runid'].'" AND report_type="' . $_POST['rpttyp'].'" AND type_id="' . $_POST['typeid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query deleting from report status');
		}
	}
}
else
{
	if(isset($_POST['upid']))
	{
		if($_POST['action']==4)
		{			
			$query = 'UPDATE update_status SET status="'.CANCELLED.'" WHERE update_id="' . $_POST['upid'].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update cancelled status');
		}
	}
	elseif(isset($_POST['runid']))
	{
		if($_POST['action']==4)
		{
			$query = 'UPDATE reports_status SET status="'.CANCELLED.'" WHERE run_id="' . $_POST['runid'].'" AND report_type="' . $_POST['rpttyp'].'" AND type_id="' . $_POST['typeid'].'"';
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

$query = 'SELECT `update_id`,`process_id` FROM update_status_fullhistory WHERE `status`='. RUNNING . ' and (trial_type="AREA1" or trial_type="PRODUCT1")';
$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Error: '.mysql_error());
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


//Get Process IDs of all currently running preindexers to check crashes
	$query = 'SELECT `update_id`,`process_id` FROM update_status_fullhistory WHERE `status`='.RUNNING;
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$count_upids=0;
	
	while($row = mysql_fetch_assoc($res))
	{
		$preindex_ids[$count_upids] = $row['update_id'];
		$preindex_pids[$count_upids++] = $row['process_id'];
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

//Check if any preindexer has terminated abruptly
for($i=0;$i < $count_rpids; $i++)
{
	//If report_status is running and corresponding process ID is not running
	if(!in_array($preindex_pids[$i],$running_pids))
	{
		//Report status set to 'error'
		$query = 'UPDATE reports_status SET status="'.ERROR.'",process_id="0" WHERE run_id="' . $preindex_ids[$i].'';
		//$res = mysql_query($query) or die('Bad SQL Query setting report error status');
	}
}

		
//Get entry corresponding to nct in 'update_status'
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE (update_id="0")';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$nct_status = array();
while($row = mysql_fetch_assoc($res))
	$nct_status = $row;
	
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE (update_id="3")';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$nct_newstatus = array();
while($row = mysql_fetch_assoc($res))
	$nct_newstatus = $row;
	
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE update_id="4"';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$calc_status = array();
while($row = mysql_fetch_assoc($res))
	$calc_status = $row;
	
/*************/
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="PRODUCT2"';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$product_status = array();
	while($row = mysql_fetch_assoc($res))
	$product_status = $row;
	
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="AREA2"';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$area_status = array();
	while($row = mysql_fetch_assoc($res))
	$area_status = $row;



/******************/

/*************/
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="PRODUCT1"';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$product_status1 = array();
	while($row = mysql_fetch_assoc($res))
	$product_status1 = $row;
	
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="AREA1"';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$area_status1 = array();
	while($row = mysql_fetch_assoc($res))
	$area_status1 = $row;



/******************/
	
	

//Get entry corresponding to eudract in 'update_status'
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE update_id="1"';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$eudract_status = array();
while($row = mysql_fetch_assoc($res))
	$eudract_status = $row;
	
//Get entry corresponding to isrctn in 'update_status'
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,`add_items_total`,`add_items_progress`,
					`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
					`add_items_complete_time`, `update_items_complete_time` FROM update_status WHERE update_id="2"';
$res = mysql_query($query) or die('Bad SQL Query getting update_status');
$isrctn_status = array();
while($row = mysql_fetch_assoc($res))
	$isrctn_status = $row;
	
	
	
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
if(count($nct_newstatus)!=0)
{
	echo "$(\"#nct_new2\").progressBar();";
	echo "$(\"#nct_update2\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}

if(count($product_status)!=0)
{
	echo "$(\"#product_new\").progressBar();";
	echo "$(\"#product_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}

if(count($area_status)!=0)
{
	echo "$(\"#area_new\").progressBar();";
	echo "$(\"#area_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}

if(count($product_status1)!=0)
{
	echo "$(\"#product_new1\").progressBar();";
	echo "$(\"#product_update1\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}

if(count($area_status1)!=0)
{
	echo "$(\"#area_new1\").progressBar();";
	echo "$(\"#area_update1\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}


if(count($eudract_status)!=0)
{
	echo "$(\"#eudract_new\").progressBar();";
	echo "$(\"#eudract_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}
if(count($isrctn_status)!=0)
{
	echo "$(\"#isrctn_new\").progressBar();";
	echo "$(\"#isrctn_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
}
if(count($calc_status)!=0)
{
	echo "$(\"#calc_new\").progressBar();";
	echo "$(\"#calc_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
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
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
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
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"nct_update\">".$nct_update_progress."</span>";
				echo "</td>";
				if($nct_status['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($nct_status['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($nct_status['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($nct_status['status']==ERROR||$nct_status['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_status['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	if(count($nct_newstatus)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">nct database (new)</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$nct_newstatus['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$nct_newstatus['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$nct_newstatus['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$nct_newstatus['updated_time']."</td>";
				if($nct_newstatus['add_items_start_time']!="0000-00-00 00:00:00"&&$nct_newstatus['add_items_complete_time']!="0000-00-00 00:00:00"&&$nct_newstatus['status']==COMPLETED)
					$nct_newadd_progress=100;
				else
					$nct_newadd_progress=number_format(($nct_newstatus['add_items_total']==0?0:(($nct_newstatus['add_items_progress'])*100/$nct_newstatus['add_items_total'])),2);
					
				if($nct_newstatus['update_items_start_time']!="0000-00-00 00:00:00"&&$nct_newstatus['update_items_complete_time']!="0000-00-00 00:00:00"&&$nct_newstatus['status']==COMPLETED)
					$nct_newupdate_progress=100;
				else
					$nct_newupdate_progress=number_format(($nct_newstatus['update_items_total']==0?0:(($nct_newstatus['update_items_progress'])*100/$nct_newstatus['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"nct_update2\">".$nct_newupdate_progress."</span>";
				echo "</td>";
				if($nct_newstatus['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$nct_newstatus['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($nct_newstatus['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$nct_newstatus['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_newstatus['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($nct_newstatus['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$nct_newstatus['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_newstatus['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($nct_newstatus['status']==ERROR||$nct_newstatus['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$nct_newstatus['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_newstatus['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$nct_newstatus['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$nct_newstatus['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	if(count($product_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">Preindexing - Single Product</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$product_status['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$product_status['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$product_status['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$product_status['updated_time']."</td>";
				if($product_status['add_items_start_time']!="0000-00-00 00:00:00"&&$product_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$product_status['status']==COMPLETED)
					$product_add_progress=100;
				else
					$product_add_progress=number_format(($product_status['add_items_total']==0?0:(($product_status['add_items_progress'])*100/$product_status['add_items_total'])),2);
					
				if($product_status['update_items_start_time']!="0000-00-00 00:00:00"&&$product_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$product_status['status']==COMPLETED)
					$product_update_progress=100;
				else
					$product_update_progress=number_format(($product_status['update_items_total']==0?0:(($product_status['update_items_progress'])*100/$product_status['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"product_update\">".$product_update_progress."</span>";
				echo "</td>";
				if($product_status['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($product_status['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($product_status['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($product_status['status']==ERROR||$product_status['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	if(count($area_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">Preindexing - Single Area</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$area_status['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$area_status['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$area_status['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$area_status['updated_time']."</td>";
				if($area_status['add_items_start_time']!="0000-00-00 00:00:00"&&$area_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$area_status['status']==COMPLETED)
					$area_add_progress=100;
				else
					$area_add_progress=number_format(($area_status['add_items_total']==0?0:(($area_status['add_items_progress'])*100/$area_status['add_items_total'])),2);
					
				if($area_status['update_items_start_time']!="0000-00-00 00:00:00"&&$area_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$area_status['status']==COMPLETED)
					$area_update_progress=100;
				else
					$area_update_progress=number_format(($area_status['update_items_total']==0?0:(($area_status['update_items_progress'])*100/$area_status['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"area_update\">".$area_update_progress."</span>";
				echo "</td>";
				if($area_status['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($area_status['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($area_status['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($area_status['status']==ERROR||$area_status['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	/***********/
	
	if(count($product_status1)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">Preindexing - ALL Products</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$product_status1['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$product_status1['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$product_status1['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$product_status1['updated_time']."</td>";
				if($product_status1['add_items_start_time']!="0000-00-00 00:00:00"&&$product_status1['add_items_complete_time']!="0000-00-00 00:00:00"&&$product_status1['status']==COMPLETED)
					$product_add_progress1=100;
				else
					$product_add_progress1=number_format(($product_status1['add_items_total']==0?0:(($product_status1['add_items_progress'])*100/$product_status1['add_items_total'])),2);
					
				if($product_status1['update_items_start_time']!="0000-00-00 00:00:00"&&$product_status1['update_items_complete_time']!="0000-00-00 00:00:00"&&$product_status1['status']==COMPLETED)
					$product_update_progress1=100;
				else
					$product_update_progress1=number_format(($product_status1['update_items_total']==0?0:(($product_status1['update_items_progress'])*100/$product_status1['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"product_update1\">".$product_update_progress1."</span>";
				echo "</td>";
				if($product_status1['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$product_status1['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($product_status1['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$product_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status1['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($product_status1['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$product_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status1['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($product_status1['status']==ERROR||$product_status1['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$product_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status1['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$product_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status1['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	if(count($area_status1)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">Preindexing - ALL Areas</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$area_status1['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$area_status1['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$area_status1['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$area_status1['updated_time']."</td>";
				if($area_status1['add_items_start_time']!="0000-00-00 00:00:00"&&$area_status1['add_items_complete_time']!="0000-00-00 00:00:00"&&$area_status1['status']==COMPLETED)
					$area_add_progress1=100;
				else
					$area_add_progress1=number_format(($area_status1['add_items_total']==0?0:(($area_status1['add_items_progress'])*100/$area_status1['add_items_total'])),2);
					
				if($area_status1['update_items_start_time']!="0000-00-00 00:00:00"&&$area_status1['update_items_complete_time']!="0000-00-00 00:00:00"&&$area_status1['status']==COMPLETED)
					$area_update_progress1=100;
				else
					$area_update_progress1=number_format(($area_status1['update_items_total']==0?0:(($area_status1['update_items_progress'])*100/$area_status1['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"area_update1\">".$area_update_progress1."</span>";
				echo "</td>";
				if($area_status1['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$area_status1['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($area_status1['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$area_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status1['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($area_status1['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$area_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status1['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($area_status1['status']==ERROR||$area_status1['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$area_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status1['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$area_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status1['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	/*************/
	if(count($calc_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">Calculate HM cells</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td align=\"left\" class=\"norm\">".$status[$calc_status['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$calc_status['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$calc_status['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$calc_status['updated_time']."</td>";
				if($calc_status['add_items_start_time']!="0000-00-00 00:00:00"&&$calc_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$calc_status['status']==COMPLETED)
					$calc_add_progress=100;
				else
					$calc_add_progress=number_format(($calc_status['add_items_total']==0?0:(($calc_status['add_items_progress'])*100/$calc_status['add_items_total'])),2);
					
				if($calc_status['update_items_start_time']!="0000-00-00 00:00:00"&&$calc_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$calc_status['status']==COMPLETED)
					$calc_update_progress=100;
				else
					$calc_update_progress=number_format(($calc_status['update_items_total']==0?0:(($calc_status['update_items_progress'])*100/$calc_status['update_items_total'])),2);
				
				//echo $calc_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"calc_update\">".$calc_update_progress."</span>";
				echo "</td>";
				if($calc_status['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$calc_status['update_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($calc_status['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$calc_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$calc_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($calc_status['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$calc_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$calc_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($calc_status['status']==ERROR||$calc_status['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$calc_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$calc_status['process_id'].'">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$calc_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$calc_status['process_id'].'">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	
	if(count($eudract_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >eudract database</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			if($eudract_status['add_items_start_time']!="0000-00-00 00:00:00"&&$eudract_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$eudract_status['status']==COMPLETED)
					$eudract_add_progress=100;
			else
				$eudract_add_progress=number_format(($eudract_status['add_items_total']==0?"0":(($eudract_status['add_items_progress'])*100/$eudract_status['add_items_total'])),2);
				
			if($eudract_status['update_items_start_time']!="0000-00-00 00:00:00"&&$eudract_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$eudract_status['status']==COMPLETED)
				$eudract_update_progress=100;
			else
				$eudract_update_progress=number_format(($eudract_status['update_items_total']==0?"0":(($eudract_status['update_items_progress'])*100/$eudract_status['update_items_total'])),2);
			
			echo "<tr>";
			echo "<td align=\"left\" class=\"norm\">".$status[$eudract_status['status']]."</td>";
			echo "<td align=\"left\" class=\"norm\">".$eudract_status['start_time']."</td>";
			echo "<td align=\"left\" class=\"norm\">".$eudract_status['timediff']."</td>";
			echo "<td align=\"left\" class=\"norm\">".$eudract_status['updated_time']."</td>";
//			echo "<td align=\"left\" class=\"norm\">";
//				echo "<span class=\"progressBar\" id=\"eudract_new\">".$eudract_add_progress."%</span>";
//			echo "</td>";
			echo "<td align=\"left\" class=\"norm\">";
				echo "<span class=\"progressBar\" id=\"eudract_update\">".$eudract_update_progress."%</span>";
			echo "</td>";
			if($eudract_status['status']==READY)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="4">';
				echo '<input type="hidden" name="upid" value="'.$eudract_status['update_id'].'">';
				echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			elseif($eudract_status['status']==RUNNING)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="2">';
				echo '<input type="hidden" name="upid" value="'.$eudract_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$eudract_status['process_id'].'">';
				echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			elseif($eudract_status['status']==COMPLETED)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="3">';
				echo '<input type="hidden" name="upid" value="'.$eudract_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$eudract_status['process_id'].'">';
				echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			else if($eudract_status['status']==ERROR||$eudract_status['status']==CANCELLED)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="1">';
				echo '<input type="hidden" name="upid" value="'.$eudract_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$eudract_status['process_id'].'">';
				echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
				echo '</form>';
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="3">';
				echo '<input type="hidden" name="upid" value="'.$eudract_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$eudract_status['process_id'].'">';
				echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			echo "</tr>";
		echo "</table>";
	}
	
	if(count($isrctn_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >isrctn database</th>";
			echo "</tr>";
		echo "</table>";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
				echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
				echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
//				echo "<td width=\"19%\" align=\"left\" class=\"head\">New Records</td>";
				echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
				echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
			echo "</tr>";
			if($isrctn_status['add_items_start_time']!="0000-00-00 00:00:00"&&$isrctn_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$isrctn_status['status']==COMPLETED)
					$isrctn_add_progress=100;
			else
				$isrctn_add_progress=number_format(($isrctn_status['add_items_total']==0?"0":(($isrctn_status['add_items_progress'])*100/$isrctn_status['add_items_total'])),2);
				
			if($isrctn_status['update_items_start_time']!="0000-00-00 00:00:00"&&$isrctn_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$isrctn_status['status']==COMPLETED)
				$isrctn_update_progress=100;
			else
				$isrctn_update_progress=number_format(($isrctn_status['update_items_total']==0?"0":(($isrctn_status['update_items_progress'])*100/$isrctn_status['update_items_total'])),2);
			
			echo "<tr>";
			echo "<td align=\"left\" class=\"norm\">".$status[$isrctn_status['status']]."</td>";
			echo "<td align=\"left\" class=\"norm\">".$isrctn_status['start_time']."</td>";
			echo "<td align=\"left\" class=\"norm\">".$isrctn_status['timediff']."</td>";
			echo "<td align=\"left\" class=\"norm\">".$isrctn_status['updated_time']."</td>";
//			echo "<td align=\"left\" class=\"norm\">";
//				echo "<span class=\"progressBar\" id=\"isrctn_new\">".$isrctn_add_progress."%</span>";
//			echo "</td>";
			echo "<td align=\"left\" class=\"norm\">";
				echo "<span class=\"progressBar\" id=\"isrctn_update\">".$isrctn_update_progress."%</span>";
			echo "</td>";
			if($isrctn_status['status']==READY)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="4">';
				echo '<input type="hidden" name="upid" value="'.$isrctn_status['update_id'].'">';
				echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			elseif($isrctn_status['status']==RUNNING)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="2">';
				echo '<input type="hidden" name="upid" value="'.$isrctn_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$isrctn_status['process_id'].'">';
				echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			elseif($isrctn_status['status']==COMPLETED)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="3">';
				echo '<input type="hidden" name="upid" value="'.$isrctn_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$isrctn_status['process_id'].'">';
				echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
				echo '</form>';
				echo "</td>";
			}
			else if($isrctn_status['status']==ERROR||$isrctn_status['status']==CANCELLED)
			{
				echo "<td align=\"center\" class=\"norm\">";
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="1">';
				echo '<input type="hidden" name="upid" value="'.$isrctn_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$isrctn_status['process_id'].'">';
				echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
				echo '</form>';
				echo '<form method="post" action="status.php">';
				echo '<input type="hidden" name="action" value="3">';
				echo '<input type="hidden" name="upid" value="'.$isrctn_status['update_id'].'">';
				echo '<input type="hidden" name="pid" value="'.$isrctn_status['process_id'].'">';
				echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
				echo '</form>';
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
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="4">';
						echo '<input type="hidden" name="runid" value="'.$heatmap_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$heatmap_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$heatmap_status[$i]['report_type'].'">';
						echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
					}
					elseif($heatmap_status[$i]['status']==RUNNING)
					{
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="2">';
						echo '<input type="hidden" name="runid" value="'.$heatmap_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$heatmap_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$heatmap_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$heatmap_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
					}
					elseif($heatmap_status[$i]['status']==COMPLETED)
					{
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="runid" value="'.$heatmap_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$heatmap_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$heatmap_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$heatmap_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
					}
					else if($heatmap_status[$i]['status']==ERROR||$heatmap_status[$i]['status']==CANCELLED)
					{
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="1">';
						echo '<input type="hidden" name="runid" value="'.$heatmap_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$heatmap_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$heatmap_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$heatmap_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
						echo '</form>';
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="runid" value="'.$heatmap_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$heatmap_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$heatmap_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$heatmap_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
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
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="4">';
						echo '<input type="hidden" name="runid" value="'.$updatescan_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$updatescan_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$updatescan_status[$i]['report_type'].'">';
						echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
					}
					elseif($updatescan_status[$i]['status']==RUNNING)
					{
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="2">';
						echo '<input type="hidden" name="runid" value="'.$updatescan_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$updatescan_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$updatescan_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$updatescan_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
					}
					elseif($updatescan_status[$i]['status']==COMPLETED)
					{
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="runid" value="'.$updatescan_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$updatescan_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$updatescan_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$updatescan_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
					}
					else if($updatescan_status[$i]['status']==ERROR||$updatescan_status[$i]['status']==CANCELLED)
					{
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="1">';
						echo '<input type="hidden" name="runid" value="'.$updatescan_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$updatescan_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$updatescan_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$updatescan_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
						echo '</form>';
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="runid" value="'.$updatescan_status[$i]['run_id'].'">';
						echo '<input type="hidden" name="typeid" value="'.$updatescan_status[$i]['type_id'].'">';
						echo '<input type="hidden" name="rpttyp" value="'.$updatescan_status[$i]['report_type'].'">';
						echo '<input type="hidden" name="pid" value="'.$updatescan_status[$i]['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
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