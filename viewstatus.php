<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>NCT history update status</title>
<link href="css/main.css" rel="stylesheet" type="text/css" media="all" />
<!--[if (lte IE 6)]>
<link href="css/IE6fixes.css" rel="stylesheet" type="text/css" media="all" />
<![endif]-->
<link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
<link href="krumo/skin.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="date/jquery.js"></script>
<script type="text/javascript" src="date/jquery.date_input.js"></script>
<script type="text/javascript" src="date/init.js"></script>
<script type="text/javascript" src="krumo/krumo.js"></script>
<script type="text/javascript" src="progressbar/jquery.js"></script>
<script type="text/javascript" src="progressbar/jquery.progressbar.js"></script>
<link href="css/status.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('nct_common.php');
require_once('include.import_new.php');
require_once('include.import.history_new.php');
	
	define('READY', 1);
	define('RUNNING', 2);
	define('ERROR', 3);
	define('CANCELLED', 4);
	define('COMPLETED', 0);

/*	
if(isset($_POST['runscrapper']))
	require_once('fetch_nct_fullhistory_all.php');
*/
	
if(isset($_POST['pid']))
	{
		if(isset($_POST['upid']))
		{
			if($_POST['action']==1)
			{
				$query = 'UPDATE update_status_fullhistory SET status="'.READY.'" WHERE update_id="' . $_POST['upid'].'"';
				$res = mysql_query($query) or die('Bad SQL Query setting update ready status');
				$query = 'select current_nctid from  update_status_fullhistory WHERE update_id="' . $_POST['upid'].'" limit 1';
				$res = mysql_query($query) or die('Bad SQL Query setting update ready status');
				$row = mysql_fetch_assoc($res); $current_nctid=$row['current_nctid'];
				if($_POST['ttype']=="trial") runnewscraper(true,$current_nctid);
				elseif( $_POST['ttype']=="area" )
				{
					$_GET['productid']=0;
					require('preindex_trials_all.php');
				}
				elseif( $_POST['ttype']=="product" )
				{
					$_GET['areaid']=0;
					require('preindex_trials_all.php');
				}

				
			}
			else if($_POST['action']==2)
			{
				$cmd = "kill ".$_POST['pid'];
				exec($cmd, $output, $result);
				
				$query = 'UPDATE update_status_fullhistory SET status="'.CANCELLED.'" WHERE update_id="' . $_POST['upid'].'"';
				$res = mysql_query($query) or die('Bad SQL Query setting update cancelled status');
			}
			else if($_POST['action']==3)
			{
				$query = 'DELETE FROM update_status_fullhistory WHERE update_id="' . $_POST['upid'].'"';
				$res = mysql_query($query) or die('Bad SQL Query deleting update status');
			}		
		}
		
	}
	else
	{
	if(isset($_POST['upid']))
		{
			if($_POST['action']==4)
			{			
				$query = 'UPDATE update_status_fullhistory SET status="'.CANCELLED.'" WHERE update_id="' . $_POST['upid'].'"';
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


$query = 'SELECT update_items_total,update_items_progress,current_nctid FROM update_status_fullhistory  ' ;
$res = mysql_query($query) or die('Bad SQL query checking update status');

$res = mysql_fetch_array($res) ;
//if(isset($res['update_items_total'])) $cid = ((int)$res['current_nctid']);

if(isset($res['update_items_total'])) showprogress();

/*echo " <div align=\"center\"  >
		   <form name='scrapper' method='post' action='viewstatus.php'>
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
	$res = mysql_query($query) or die('Bad SQL Query getting process IDs of updates. Query: '. $query .'Error: '.mysql_error());
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
	$cmd = "ps aux|grep viewstatus";
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
			$query = 'UPDATE update_status_fullhistory SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update error status');
		}
			
	}
	
/********STATUS OF FULL HISTORY SCRAPER ********************************************/
	//Get entry corresponding to nct in 'update_status_fullhistory'
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="NCT"';
	$res = mysql_query($query) or die('Bad SQL Query getting update_status_fullhistory');
	$nct_status = array();
	while($row = mysql_fetch_assoc($res))
	$nct_status = $row;
	
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="PRODUCT"';
	$res = mysql_query($query) or die('Bad SQL Query getting update_status_fullhistory');
	$product_status = array();
	while($row = mysql_fetch_assoc($res))
	$product_status = $row;
	
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory where trial_type="AREA"';
	$res = mysql_query($query) or die('Bad SQL Query getting update_status_fullhistory');
	$area_status = array();
	while($row = mysql_fetch_assoc($res))
	$area_status = $row;




	//Add javascript for each progress bar that has to be shown
	echo "<script type=\"text/javascript\">";

	echo "$(document).ready(function() {";
	if(count($nct_status)!=0)
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
		
	echo "<div class=\"container\">";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head1\" >NCT history update status</th>";
			echo "</tr>";
		echo "</table>";
		if(count($nct_status)!=0)
		{
			echo "<table width=\"100%\" class=\"event\">";
			echo "</table>";
			echo "<table width=\"100%\" class=\"event\">";
				echo "<tr>";
					echo "<td width=\"10%\" align=\"left\" class=\"head\">Status</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Start Time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Last update time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Progress</td>";
					echo "<td width=\"25%\" align=\"left\" class=\"head\">Message</td>";
					echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$status[$nct_status['status']]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_status['start_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_status['timediff']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_status['updated_time']."</td>";
						
					if($nct_status['start_time']!="0000-00-00 00:00:00"&&$nct_status['end_time']!="0000-00-00 00:00:00"&&$nct_status['status']==COMPLETED)
						$nct_update_progress=100;
					else
						$nct_update_progress=number_format(($nct_status['update_items_total']==0?0:(($nct_status['update_items_progress'])*100/$nct_status['update_items_total'])),2);

					echo "<td align=\"left\" class=\"norm\">";
						echo "<span class=\"progressBar\" id=\"nct_update\">".$nct_update_progress."</span>";
					echo "</td>";
					echo "<td align=\"left\" class=\"norm\">".$nct_status['er_message']."</td>";
					if($nct_status['status']==READY)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="4">';
						echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
						//echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
					elseif($nct_status['status']==RUNNING)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="viewstatus.php">';
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
						echo '<form method="post" action="viewstatus.php">';
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
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="1">';
						echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$nct_status['process_id'].'">';
						echo '<input type="hidden" name="ttype" value="trial">';
						echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
						echo '</form>';
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="upid" value="'.$nct_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$nct_status['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
				echo "</tr>";
			echo "</table></div>";
		}
/********STATUS OF PRODUCT PREINDEXING ********************************************/

	echo "<div class=\"container\">";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head1\" >Preindexing status - Products</th>";
			echo "</tr>";
		echo "</table>";
		if(count($product_status)!=0)
		{
			echo "<table width=\"100%\" class=\"event\">";
			echo "</table>";
			echo "<table width=\"100%\" class=\"event\">";
				echo "<tr>";
					echo "<td width=\"10%\" align=\"left\" class=\"head\">Status</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Start Time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Last update time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Progress</td>";
					echo "<td width=\"25%\" align=\"left\" class=\"head\">Message</td>";
					echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$status[$product_status['status']]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$product_status['start_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$product_status['timediff']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$product_status['updated_time']."</td>";
						
					if($product_status['start_time']!="0000-00-00 00:00:00"&&$product_status['end_time']!="0000-00-00 00:00:00"&&$product_status['status']==COMPLETED)
						$product_update_progress=100;
					else
						$product_update_progress=number_format(($product_status['update_items_total']==0?0:(($product_status['update_items_progress'])*100/$product_status['update_items_total'])),2);

					echo "<td align=\"left\" class=\"norm\">";
						echo "<span class=\"progressBar\" id=\"product_update\">".$product_update_progress."</span>";
					echo "</td>";
					echo "<td align=\"left\" class=\"norm\">".$product_status['er_message']."</td>";
					if($product_status['status']==READY)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="4">';
						echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
						//echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
					elseif($product_status['status']==RUNNING)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="viewstatus.php">';
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
						echo '<form method="post" action="viewstatus.php">';
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
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="1">';
						echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
						echo '<input type="hidden" name="ttype" value="product">';
						echo '<input type="hidden" name="mode" value="start">';
						echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
						echo '</form>';
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="upid" value="'.$product_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$product_status['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
				echo "</tr>";
			echo "</table></div>";
		}
/********STATUS OF AREA PREINDEXING ********************************************/

	echo "<div class=\"container\">";
		echo "<table width=\"100%\" class=\"event\">";
			echo "<tr>";
				echo "<th width=\"100%\" align=\"center\" class=\"head1\" >Preindexing status - Area</th>";
			echo "</tr>";
		echo "</table>";
		if(count($area_status)!=0)
		{
			echo "<table width=\"100%\" class=\"event\">";
			echo "</table>";
			echo "<table width=\"100%\" class=\"event\">";
				echo "<tr>";
					echo "<td width=\"10%\" align=\"left\" class=\"head\">Status</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Start Time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Excution run time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Last update time</td>";
					echo "<td width=\"15%\" align=\"left\" class=\"head\">Progress</td>";
					echo "<td width=\"25%\" align=\"left\" class=\"head\">Message</td>";
					echo "<td width=\"5%\" align=\"center\" class=\"head\">Action</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align=\"left\" class=\"norm\">".$status[$area_status['status']]."</td>";
					echo "<td align=\"left\" class=\"norm\">".$area_status['start_time']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$area_status['timediff']."</td>";
					echo "<td align=\"left\" class=\"norm\">".$area_status['updated_time']."</td>";
						
					if($area_status['start_time']!="0000-00-00 00:00:00"&&$area_status['end_time']!="0000-00-00 00:00:00"&&$area_status['status']==COMPLETED)
						$area_update_progress=100;
					else
						$area_update_progress=number_format(($area_status['update_items_total']==0?0:(($area_status['update_items_progress'])*100/$area_status['update_items_total'])),2);

					echo "<td align=\"left\" class=\"norm\">";
						echo "<span class=\"progressBar\" id=\"area_update\">".$area_update_progress."</span>";
					echo "</td>";
					echo "<td align=\"left\" class=\"norm\">".$area_status['er_message']."</td>";
					if($area_status['status']==READY)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="4">';
						echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
						//echo '<input type="image" src="images/not.png" title="Cancel" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
					elseif($area_status['status']==RUNNING)
					{
						echo "<td align=\"center\" class=\"norm\">";
						echo '<form method="post" action="viewstatus.php">';
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
						echo '<form method="post" action="viewstatus.php">';
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
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="1">';
						echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
						echo '<input type="hidden" name="ttype" value="area">';
						echo '<input type="hidden" name="mode" value="start">';
						echo '<input type="image" src="images/check.png" title="Add" style="border=0px;">';
						echo '</form>';
						echo '<form method="post" action="viewstatus.php">';
						echo '<input type="hidden" name="action" value="3">';
						echo '<input type="hidden" name="upid" value="'.$area_status['update_id'].'">';
						echo '<input type="hidden" name="pid" value="'.$area_status['process_id'].'">';
						echo '<input type="image" src="images/not.png" title="Delete" style="border=0px;">';
						echo '</form>';
						echo "</td>";
					}
				echo "</tr>";
			echo "</table></div>";
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
			$res = mysql_query($query);
			if($res === false) die('Bad SQL query getting nctids from local table');
			echo '<br> Picking up nctids from local table "nctids" <br>';
			while($row = mysql_fetch_assoc($res)) $nct_ids[$row['nctid']] = 1;
			unset($res);unset($row);
			
			
			$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="AREA" order by update_id desc limit 1' ;
			$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
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
				$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
				$pr_id=$pid ;
				unset($res);
			}
			
		}

		else 
		{
			
			$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="AREA" order by update_id desc limit 1' ;
			$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
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
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
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
				$res = mysql_query($query);
				if($res === false) die('Bad SQL query adding nctid into local table.Query='.$query);
			}
			
		}
		else
		{
			$nct_idz=array();
			$query='select nctid from nctids where id>0';
			$res = mysql_query($query);
			if($res === false) die('Bad SQL query getting nctids from local table');
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
		$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
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
		$row = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
	//	fetch_records($pid,$cid,$maxid,$up_id);
//		exit;
	}

	else
	{

		$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
		$res = mysql_query($query) or die('Bad SQL query finding highest update id');
		$res = mysql_fetch_array($res) ;
		$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
		$fid = getFieldId('NCT','nct_id');
		if(!isset($nct_ids))
		{
			$query = 'SELECT MAX(nct_id) AS maxid FROM data_nct';
			$res = mysql_query($query) or die('Bad SQL query finding highest nct_id');
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
			$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
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
		$res = mysql_query($query) or die('Bad SQL query selecting row from update_status_fullhistory ');
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
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
		@flush();
	}
	$query = 'UPDATE  update_status_fullhistory SET status=0, process_id = "'. $pid  .'" , end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
	echo('<br>Done with all IDs.');
	
}
function fetch_records($pr_id,$cid,$maxid,$up_id)
{ 	
	global $nct_ids;
//	pr($nct_ids);
	$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" and trial_type="NCT" limit 1 ;' ;
	$res = mysql_query($query) or die('Bad SQL query selecting row from update_status_fullhistory ');
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
		$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
		@flush();
		}
		else
		break;
	}


$query = 'UPDATE  update_status_fullhistory SET status=0, process_id = "'. $pr_id  .'" , end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
echo('<br>Done with all IDs.');

}
function validate_nctid($ncid,$mxid)
{
	
	$query = 'select nct_id from data_nct where nct_id>="'.$ncid.'" and nct_id<="'.$mxid.'" order by nct_id limit 1 ;'; 
	//echo('<br>query=' .$query. '<br>' );
	$res = mysql_query($query) or die('checking of NCTID  Failed. Query:' . $query );
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
				$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
				$up_it_pr++;
				@flush();
			}
			
		}


	$query = 'UPDATE  update_status_fullhistory SET status=0, process_id = "'. $pr_id  .'" , end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
	echo('<br>Done with all IDs.');

	}
	
?>

</body>