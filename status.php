<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('nct_common.php');
require_once('include.import_new.php');
require_once('include.import.history_new.php');
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
global $logger;
//Definition of constants for states
define('READY', 1);
define('RUNNING', 2);
define('ERROR', 3);
define('CANCELLED', 4);
define('COMPLETED', 0);
if(isset($_POST['fullhistory'])) $updttable='update_status_fullhistory'; else $updttable='update_status';

if(isset($_POST['pid']))
{
	if(isset($_POST['upid']))
	{
		if($_POST['action']==1)
		{
			$query = 'UPDATE  ' . $updttable . '   SET status="'.READY.'" WHERE update_id="' . $_POST['upid'].'"';
			if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
		//	$res = mysql_query($query) or die('Bad SQL Query setting update ready status');
			
			/* * */
			if($updttable=='update_status_fullhistory')
			{
			$query = 'select `current_nctid` from  update_status_fullhistory WHERE `update_id`="' . $_POST['upid'].'" limit 1';
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				$row = mysql_fetch_assoc($res); $current_nctid=$row['current_nctid'];
				if($_POST['ttype']=="trial") runnewscraper(true,$current_nctid);
			}
			/**/
			
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
			
			//remapping
				elseif( $_POST['ttype']=="REMAP" )
				{
					$_GET['source']='ALL';
					require('remap_trials.php');
				}
			//
				
		}
		else if($_POST['action']==2)
		{
			$cmd = "kill ".$_POST['pid'];
			exec($cmd, $output, $result);
			
			$query = 'UPDATE ' . $updttable . '  SET status="'.CANCELLED.'" WHERE update_id="' . $_POST['upid'].'"';
			if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
		}
		else if($_POST['action']==3)
		{
			$query = 'DELETE FROM  ' . $updttable . '   WHERE update_id="' . $_POST['upid'].'"';
			if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
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
			$query = 'UPDATE  ' . $updttable . '   SET status="'.CANCELLED.'" WHERE update_id="' . $_POST['upid'].'"';
			if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
		}
	}
	elseif(isset($_POST['runid']))
	{
		if($_POST['action']==4)
		{
			$query = 'UPDATE reports_status SET status="'.CANCELLED.'" WHERE run_id="' . $_POST['runid'].'" AND report_type="' . $_POST['rpttyp'].'" AND type_id="' . $_POST['typeid'].'"';
			if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
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

/*************/
$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="REMAP"';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$remap_status = array();
	while($row = mysql_fetch_assoc($res))
	$remap_status = $row;
	
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
if(count($remap_status)!=0)
{
	echo "$(\"#remap_new\").progressBar();";
	echo "$(\"#remap_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
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
	
	
	
	/******************* status moved from viewstatus *******/
	$query = 'SELECT update_items_total,update_items_progress,current_nctid FROM update_status_fullhistory  ' ;
if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}

$res = mysql_fetch_array($res) ;
//if(isset($res['update_items_total'])) $cid = ((int)$res['current_nctid']);

if(isset($res['update_items_total'])) showprogress();

/*echo " <div align=\"center\"  >
		   <form name='scrapper' method='post' action='status.php'>
				<input type='hidden' name='runscrapper' value='yes'> 
				<input type=\"submit\" value=\"Run full-history scrapper\" style=\"width:226px; height:31px;\" border=\"0\">
			</form>
		</div> ";
*/
function showprogress()
{

	$status = array();
	//Definition of constants for states
	$status[0]="Completed";
	$status[1]="Ready";
	$status[2]="Running";
	$status[3]="Error";
	$status[4]="Cancelled";


	//Get Process IDs of all currently running updates to check crashes
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
		$update_ids[$count_upids] = $row['update_id'];
		$update_pids[$count_upids++] = $row['process_id'];
	}


	$err=array();
	$cmd = "ps aux|grep fullhistory";
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
		
		if(!in_array($update_pids[$i],$running_pids))
		{
			$err[$i]='yes';
		}
		else
		{
			$err[$i]='no';
		}
	}
	$running_pids2=array();
	$cmd = "ps aux|grep status";
	exec($cmd, $output, $result);
	for($i=0;$i < count($output); $i++)
	{
		$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
		$exp_out=explode(" ",$output[$i]);
		$running_pids2[$i]=$exp_out[1];
	}
	$cmd = "ps aux|grep preindex_trials";
	exec($cmd, $output, $result);
	$j=$i+1;
	for($i=0;$i < count($output); $i++)
	{
		$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
		$exp_out=explode(" ",$output[$i]);
		$running_pids2[$j]=$exp_out[1];
		$j++;
	}
	
	
	$running_pids = array_merge($running_pids, $running_pids2);

	for($i=0;$i < $count_upids; $i++)
	{
			if( !in_array($update_pids[$i],$running_pids) and $err[$i]=='yes')
		{
			$query = 'UPDATE update_status_fullhistory SET `status`="'.ERROR.'",`process_id`="0" WHERE `update_id`="' . $update_ids[$i].'"';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		}
			
	}
	
/********STATUS OF FULL HISTORY SCRAPER ********************************************/
	//Get entry corresponding to nct in 'update_status_fullhistory'
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="NCT"';
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$nct_history_status = array();
	while($row = mysql_fetch_assoc($res))
	$nct_history_status = $row;
	
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
	$product_status = array();
	while($row = mysql_fetch_assoc($res))
	$product_status = $row;
	
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
	$area_status = array();
	while($row = mysql_fetch_assoc($res))
	$area_status = $row;




	//Add javascript for each progress bar that has to be shown
	echo "<script type=\"text/javascript\">";

	echo "$(document).ready(function() {";
	if(count($nct_history_status)!=0)
	{
		echo "$(\"#nct_new\").progressBar();";
		echo "$(\"#nct_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
	}
	if(count($product_status)!=0)
	{
		echo "$(\"#product_status\").progressBar();";
		echo "$(\"#product_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
	}
	if(count($area_status)!=0)
	{
		echo "$(\"#area_status\").progressBar();";
		echo "$(\"#area_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
	}
	
	echo "});";

	echo "</script>";
		
	
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\" >NCT history update status</th>";
			echo "</tr>";
		echo "</table>";
		if(count($nct_history_status)!=0)
		{
			echo "<table width=\"100%\" class=\"event\">";
			echo "</table>";
			echo "<table width=\"100%\" class=\"event\">";
				echo "<tr>";
					echo "<td width=\"10%\" align=\"left\" class=\"head\">Status</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Start Time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Last update time</td>";
					echo "<td width=\"25%\" align=\"left\" class=\"head\">Message</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Progress</td>";
					echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$status[$nct_history_status['status']]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_history_status['start_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_history_status['timediff']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_history_status['updated_time']."</td>";
						
					if($nct_history_status['start_time']!="0000-00-00 00:00:00"&&$nct_history_status['end_time']!="0000-00-00 00:00:00"&&$nct_history_status['status']==COMPLETED)
						$nct_update_progress=100;
					else
						$nct_update_progress=number_format(($nct_history_status['update_items_total']==0?0:(($nct_history_status['update_items_progress'])*100/$nct_history_status['update_items_total'])),2);

					echo "<td align=\"left\" class=\"norm\">".$nct_history_status['er_message']."</td>";
					echo "<td align=\"left\" class=\"norm\">";
						echo "<span class=\"progressBar\" id=\"nct_update\">".$nct_update_progress."</span>";
					echo "</td>";
					if($nct_history_status['status']==READY)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="4">';
						echo '<input type="hidden" name="fullhistory" value="yes">';
						echo '<input type="hidden" name="upid" value="'.$nct_history_status['update_id'].'">';
						//echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
					elseif($nct_history_status['status']==RUNNING)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="2">';
						echo '<input type="hidden" name="upid" value="'.$nct_history_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$nct_history_status['process_id'].'">';
						echo '<input type="hidden" name="fullhistory" value="yes">';
						echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
					elseif($nct_history_status['status']==COMPLETED)
					{

					echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="upid" value="'.$nct_history_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$nct_history_status['process_id'].'">';
						echo '<input type="hidden" name="fullhistory" value="yes">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
					else if($nct_history_status['status']==ERROR||$nct_history_status['status']==CANCELLED)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="1">';
						echo '<input type="hidden" name="upid" value="'.$nct_history_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$nct_history_status['process_id'].'">';
						echo '<input type="hidden" name="ttype" value="trial">';
						echo '<input type="hidden" name="fullhistory" value="yes">';
						
						echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
						echo '</form>';
						echo '<form method="post" action="status.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="upid" value="'.$nct_history_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$nct_history_status['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
				echo "</tr>";
			echo "</table>";
		}

}

function runscraper()
{
	global $db;
/*	if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
	{
		header('Location: ' . urlPath() . 'index.php');
		exit;
	}
*/
	echo str_repeat ("   ", 1500);
	echo '<br>';
	global $pr_id;
	global $cid;
	global $maxid;

	ini_set('max_execution_time', '9000000'); //250 hours
	ignore_user_abort(true);
	if(!isset($nct_ids))
	{
		$query="SELECT * FROM nctids limit 1";
		$res=@mysql_query($query);
		if($res)  // temporaray table
		{
			$nct_ids=array();
			$query='select nctid from nctids where id>0';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			echo '<br> Picking up nctids from local table "nctids" <br>';
			while($row = mysql_fetch_assoc($res)) $nct_ids[$row['nctid']] = 1;
			unset($res);unset($row);
			
			
			$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="AREA" order by update_id desc limit 1' ;
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$res = mysql_fetch_array($res) ;
			if ( isset($res['process_id']) )
			{
				$pr_id=$res['process_id'];
				$pid = getmypid();
				$up_id= ((int)$res['update_id']);
				$up_it_pr=((int)$res['update_items_progress']);
				$cid = ((int)$res['current_nctid']); 
				$maxid = ((int)$res['max_nctid']); 
				$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message="",process_id="' . $pid . '"   WHERE status="1" and process_id = "' . $pr_id .'" ;' ;
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				$pr_id=$pid ;
				unset($res);
			}
			
		}

		else 
		{
			
			$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="AREA" order by update_id desc limit 1' ;
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$res = mysql_fetch_array($res) ;
		}
	}	
	if ( isset($res['process_id']) ) // nctids to be picked up from data_values
	{
		$pr_id=$res['process_id'];
		$pid = getmypid();
		$up_id= ((int)$res['update_id']);
		$cid = ((int)$res['current_nctid']); 
		$maxid = ((int)$res['max_nctid']); 
		$up_it_pr=((int)$res['update_items_progress']);
		$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message="",process_id="' . $pid . '"  WHERE process_id = "' . $pr_id .'" ;' ;
		if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		$pr_id=$pid ;
		fetch_records($pid,$cid,$maxid,$up_id);
		exit;
	}

	else  // nctids to be picked up from array
	{ 
/*		$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
		$res = mysql_query($query) or die('Bad SQL query finding highest update id');
		$res = mysql_fetch_array($res) ;
		$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
		$fid = getFieldId('NCT','nct_id');
		
		if(!isset($nct_ids))
		{
			$query = 'SELECT MAX(val_int) AS maxid FROM data_values WHERE `field`=' . $fid;
			$res = mysql_query($query) or die('Bad SQL query finding highest nct_id');
			$res = mysql_fetch_array($res) or die('No nct_id found!');
			$maxid = $res['maxid'];
			$cid = (isset($_GET['start']) && is_numeric($_GET['start'])) ? ((int)$_GET['start']) : 102;  // 102 is the starting NCTID in ct.gov
		}
		else
		{
*/		
		ksort($nct_ids); reset($nct_ids); $val=key($nct_ids);
		if(!isset($cid)) $cid = unpadnct($val);
		end($nct_ids);$val=key($nct_ids); $maxid = unpadnct($val);
//		}


		
	
		$totalncts = count($nct_ids);
	
		$pid = getmypid();

		if (!$totalncts > 0) die("No valid nctids found.");

		//go
		echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
		
		if(!isset($nct_ids)) fetch_records($pid,$cid,$maxid,$up_id,$up_it_pr);
		else fetch_records_2($nct_ids,$pid,$up_id,$maxid,$cid,$up_it_pr);
		
	}

	




}

function runnewscraper($requeued,$current_nctid)
{


	echo('<br>RESUMING SCRAPER..........<br>');
	echo('<br>Current time ' . date('Y-m-d H:i:s', strtotime('now')) . '<br>');
	echo str_repeat ("  ", 1500);
	global $pr_id;
	global $cid;
	global $maxid;
	ini_set('max_execution_time', '9000000'); //250 hours
	ignore_user_abort(true);

	$query="SHOW TABLES FROM " .DB_NAME. " like 'nctids'";
	$res=mysql_query($query);
	$row = mysql_fetch_assoc($res);
	if($row) 
	{
		$query="SELECT * FROM nctids limit 1";
		$res=@mysql_query($query);
		if(!$res) 
		{
			$nct_ids=get_nctids_from_web();
			foreach($nct_ids as $nct_id=>$key)
			{
				$query='insert into `nctids` set nctid="'. padnct($nct_id) .'"';
				if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							echo $log;
							return false;
						}			
			}
			
		}
		else
		{
			$nct_idz=array();
			$query='select nctid from nctids where id>0';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			while($row = mysql_fetch_assoc($res)) 
			{
				if(isset($current_nctid) and $current_nctid>0)
				{
					if(unpadnct($row['nctid'])>=$current_nctid)
						$nct_idz[$row['nctid']] = 1;
				}
				else
					$nct_idz[$row['nctid']] = 1;
			}
		}
	}
	if(!isset($nct_ids))
	{
		$nct_ids=$nct_idz;
		$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="NCT" order by update_id desc limit 1' ;
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$row = mysql_fetch_assoc($res) ;
//		pr($row);
	}	
	if ( isset($row['process_id']) )
	{
		
		$pid = getmypid();
		$up_id= ((int)$row['update_id']);
		$cid = ((int)$row['current_nctid']); 
		$maxid = ((int)$row['max_nctid']); 
		$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message=""  WHERE process_id = "' . $pr_id .'" ;' ;
		if(!$row = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	//	fetch_records($pid,$cid,$maxid,$up_id);
//		exit;
	}

	else
	{

		$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$res = mysql_fetch_array($res) ;
		$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
		$fid = getFieldId('NCT','nct_id');
		if(!isset($nct_ids))
		{
			$query = 'SELECT MAX(nct_id) AS maxid FROM data_nct';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			$res = mysql_fetch_array($res) or die('No nct_id found!');
			$maxid = $res['maxid'];
			$cid = (isset($_GET['start']) && is_numeric($_GET['start'])) ? ((int)$_GET['start']) : 102;  // 102 is the starting NCTID in ct.gov
		}
		else
		{
		
			ksort($nct_ids); reset($nct_ids); $val=key($nct_ids); $cid = unpadnct($val);
			end($nct_ids); $val=key($nct_ids); $maxid = unpadnct($val);
		}

		/***************************************************/
		//$maxid = $cid+40;  
		/***************************************************/

		$cid_=$cid;
		if(!isset($nct_ids))
		{
			for($totalncts=0; $cid_ <= $maxid; $cid_=$cid_+1)
			{
				$vl = validate_nctid($cid_,$maxid);
				if( isset($vl[1] )) 
				{
					$cid_=$vl[1];
					++$totalncts;
				}
				else
				break;
			}
		}
		else
		{
			$totalncts = count($nct_ids);
			echo '<br>';
		}
		$pid = getmypid();

		if ($totalncts > 0)
		{
		
			$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid,trial_type) 
					  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
					  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'", "NCT"  ) ;';
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
		}
		else die("No valid nctids found.");
	}
		
	echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
	echo('<br>Current time ' . date('Y-m-d H:i:s', strtotime('now')) . '<br>');
	echo str_repeat ("  ", 4000);
	$i=1;
	foreach($nct_ids as $nct_id=>$key)
	{
	
		$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" and trial_type="NCT" limit 1 ;' ;
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		$res = mysql_fetch_array($res) ;
		if ( isset($res['update_items_progress'] ) and $res['update_items_progress'] > 0 ) $updtd_items=((int)$res['update_items_progress']); else $updtd_items=0;
		if ( isset($res['update_items_total'] ) and $res['update_items_total'] > 0 ) $tot_items=((int)$res['update_items_total']); else $tot_items=0;
	//	++$i;
		$cid = unpadnct($nct_id);
		$nct_id = padnct($nct_id);
		ProcessNew($nct_id);
		echo('<br>Current time ' . date('Y-m-d H:i:s', strtotime('now')) . '<br>');
		echo str_repeat (" ", 4000);
		$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pid  .'" , update_items_progress= "' . ( ($tot_items >= $updtd_items+$i) ? ($updtd_items+$i) : $tot_items  ) . '" , status="2", current_nctid="'. $cid .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="NCT"  ;' ;
		if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
		@flush();
	}
	$query = 'UPDATE  update_status_fullhistory SET status=0, process_id = "'. $pid  .'" , end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	echo('<br>Done with all IDs.');
	
}
function fetch_records($pr_id,$cid,$maxid,$up_id)
{ 	
	global $nct_ids;
//	pr($nct_ids);
	$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" and trial_type="NCT" limit 1 ;' ;
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_array($res) ;
	if ( isset($res['update_items_progress'] ) and $res['update_items_progress'] > 0 ) $updtd_items=((int)$res['update_items_progress']); else $updtd_items=0;
	if ( isset($res['update_items_total'] ) and $res['update_items_total'] > 0 ) $tot_items=((int)$res['update_items_total']); else $tot_items=0;
	
	$v1=array();
	for($i=1; $cid <= $maxid; $cid=$cid+1)
	{
		if(!isset($nct_ids)) 
		{
			$vl = validate_nctid($cid,$maxid);
		
		}
		else $v1[1]=isset($nct_ids[padnct($cid)]) ? $cid : NULL ;
		if( isset($vl[1] )) 
		{
			$cid=$vl[1];
			scrape_history($cid);
			++$i;
			$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pr_id  .'" , update_items_progress= "' . ( ($tot_items >= $updtd_items+$i) ? ($updtd_items+$i) : $tot_items  ) . '" , status="2", current_nctid="'. $cid .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="NCT"  ;' ;
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
			}
			@flush();
		}
		else
			break;
	}


$query = 'UPDATE  update_status_fullhistory SET status=0, process_id = "'. $pr_id  .'" , end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
echo('<br>Done with all IDs.');

}
function validate_nctid($ncid,$mxid)
{
	
	$query = 'select nct_id from data_nct where nct_id>="'.$ncid.'" and nct_id<="'.$mxid.'" order by nct_id limit 1 ;'; 
	//echo('<br>query=' .$query. '<br>' );
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	$res = mysql_fetch_assoc($res);
	if (isset($res['nct_id']))
		return array(true,$res['nct_id']);
	else
		return array(false,0);
}

function get_nctids_from_web()
{
	$fields = 'k';

	$ids = array();
	for($page = 1; true; ++$page)
	{
		$fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
		@mysql_fetch_array($fake);
		$url = 'http://clinicaltrials.gov/ct2/results?flds=' . $fields . '&pg=' . $page;
		$doc = new DOMDocument();
		for($done=false,$tries=0; $done==false&&$tries<5; $tries++)
		{
			echo('.');
			@$done = $doc->loadHTMLFile($url);
		}
		$tables = $doc->getElementsByTagName('table');
		$datatable = NULL;
		foreach($tables as $table)
		{
			$right = false;
			foreach($table->attributes as $attr)
			{
				if($attr->name == 'class' && $attr->value == 'data_table')
				{
					$right = true;
					break;
				}
			}
			if($right == true)
			{
				$datatable = $table;
				break;
			}
		}
		if($datatable == NULL)
		{
			echo('Last page reached.' . "\n<br />");
			break;
		}
		
/*		if($page >= 5)
		{
			echo('Last page reached.' . "\n<br />");
			break;
		}
*/		
		unset($tables);
		//Now that we found the table, go through its TDs to find the ones with NCTIDs
		$tds = $datatable->getElementsByTagName('td');
		$pageids = array();
		foreach($tds as $td)
		{
			$hasid = false;
			foreach($td->attributes as $attr)
			{
				if($attr->name == 'style' && $attr->value == 'padding-left:1em;')
				{
					$hasid = true;
					break;
				}
			}
			if($hasid)
			{
				$pageids[mysql_real_escape_string($td->nodeValue)] = 1;
			}
			
		}
		echo('<br>Page ' . $page . ': ' . implode(', ', array_keys($pageids)) . "\n<br />");
		echo str_repeat ("   ", 4000);
		$ids = array_merge($ids,$pageids);
		$nl = '<br>';
		$now = strtotime('now');
		echo($nl . 'Current time ' . date('Y-m-d H:i:s', strtotime('now')) . $nl);		

	}
	return $ids;

	

}


	function fetch_records_2($nct_ids,$pr_id,$up_id,$maxid,$cid,$up_it_pr)
	{ 	
		
		$tot_items=count($nct_ids);
		$v1=array();
		$current_id=$cid;
		
		foreach($nct_ids as $key => $val)
		{
			$cid=unpadnct($key);
			if( intval($cid) >= intval($current_id) ) // do not re-import previous ones
			{
				scrape_history($cid);
				$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pr_id  .'" , update_items_progress= "' . ( ($tot_items >= $up_it_pr) ? ($up_it_pr) : $tot_items  ) . '" , status="2", current_nctid="'. $cid .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type="NCT"  ;' ;
				if(!$res = mysql_query($query))
				{
					$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
					$logger->error($log);
					echo $log;
					return false;
				}
				$up_it_pr++;
				@flush();
			}
			
		}


	$query = 'UPDATE  update_status_fullhistory SET status=0, process_id = "'. $pr_id  .'" , end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
	if(!$res = mysql_query($query))
		{
			$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
			$logger->error($log);
			echo $log;
			return false;
		}
	echo('<br>Done with all IDs.');

	}


/********************************/	



	
	
	
	
	
	
	
	
	
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$product_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$product_status1['process_id'].'">';
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
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
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$area_status1['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$area_status1['process_id'].'">';
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
			echo "</tr>";
		echo "</table>";
	}
	
	if(count($remap_status)!=0)
	{
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head2\">Remapping Trials.</th>";
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
				echo "<td align=\"left\" class=\"norm\">".$status[$remap_status['status']]."</td>";
				echo "<td align=\"left\" class=\"norm\">".$remap_status['start_time']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$remap_status['timediff']."</td>";
				echo "<td align=\"left\" class=\"norm\">".$remap_status['updated_time']."</td>";
				if($remap_status['add_items_start_time']!="0000-00-00 00:00:00"&&$remap_status['add_items_complete_time']!="0000-00-00 00:00:00"&&$remap_status['status']==COMPLETED)
					$remap_add_progress=100;
				else
					$remap_add_progress=number_format(($remap_status['add_items_total']==0?0:(($remap_status['add_items_progress'])*100/$remap_status['add_items_total'])),2);
					
				if($remap_status['update_items_start_time']!="0000-00-00 00:00:00"&&$remap_status['update_items_complete_time']!="0000-00-00 00:00:00"&&$remap_status['status']==COMPLETED)
					$remap_update_progress=100;
				else
					$remap_update_progress=number_format(($remap_status['update_items_total']==0?0:(($remap_status['update_items_progress'])*100/$remap_status['update_items_total'])),2);
				
				//echo $nct_status['update_items_complete_time'];
				
//				echo "<td align=\"left\" class=\"norm\">";
//					echo "<span class=\"progressBar\" id=\"nct_new\">".$nct_add_progress."%</span>";
//				echo "</td>";
				echo "<td align=\"left\" class=\"norm\">";
					echo "<span class=\"progressBar\" id=\"remap_update\">".$remap_update_progress."</span>";
				echo "</td>";
				if($remap_status['status']==READY)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="4">';
					echo '<input type="hidden" name="upid" value="'.$remap_status['update_id'].'">';
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="hidden" name="ttype" value="REMAP">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($remap_status['status']==RUNNING)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="2">';
					echo '<input type="hidden" name="upid" value="'.$remap_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$remap_status['process_id'].'">';
					echo '<input type="hidden" name="ttype" value="REMAP">';
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				elseif($remap_status['status']==COMPLETED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$remap_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$remap_status['process_id'].'">';
					echo '<input type="hidden" name="ttype" value="REMAP">';
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
					echo '</form>';
					echo "</td>";
				}
				else if($remap_status['status']==ERROR||$remap_status['status']==CANCELLED)
				{
					echo "<td align=\"center\" class=\"norm\">";
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="1">';
					echo '<input type="hidden" name="upid" value="'.$remap_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$remap_status['process_id'].'">';
					echo '<input type="hidden" name="ttype" value="REMAP">';
					echo '<input type="hidden" name="fullhistory" value="1">';
					echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
					echo '</form>';
					echo '<form method="post" action="status.php">';
					echo '<input type="hidden" name="action" value="3">';
					echo '<input type="hidden" name="upid" value="'.$remap_status['update_id'].'">';
					echo '<input type="hidden" name="pid" value="'.$remap_status['process_id'].'">';
					echo '<input type="hidden" name="ttype" value="REMAP">';
					echo '<input type="hidden" name="fullhistory" value="1">';
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
	
	

echo "</div>";
echo "</body>";
echo "</html>";
?>