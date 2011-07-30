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
				$query = 'UPDATE update_status_fullhistory SET status="'.READY.'" WHERE update_id="' . $_POST['upid'].'"';
				$res = mysql_query($query) or die('Bad SQL Query setting update ready status');
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
else die ('<br> Nothing to display.');

function showprogress()
{

	//Definition of constants for states
	define('READY', 1);
	define('RUNNING', 2);
	define('ERROR', 3);
	define('CANCELLED', 4);
	define('COMPLETED', 0);

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
		//If update_status_fullhistory is running and corresponding process ID is not running
		if(!in_array($update_pids[$i],$running_pids))
		{
			//Update status set to 'error'
			$query = 'UPDATE update_status_fullhistory SET status="'.ERROR.'",process_id="0" WHERE update_id="' . $update_ids[$i].'"';
			$res = mysql_query($query) or die('Bad SQL Query setting update error status');
		}
	}
			
	//Get entry corresponding to nct in 'update_status_fullhistory'
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
						`update_items_total`,`update_items_progress`,TIMEDIFF(updated_time, start_time) AS timediff,
						`update_items_complete_time` FROM update_status_fullhistory ';
	$res = mysql_query($query) or die('Bad SQL Query getting update_status_fullhistory');
	$nct_status = array();
	while($row = mysql_fetch_assoc($res))
		$nct_status = $row;

	
	//Add javascript for each progress bar that has to be shown
	echo "<script type=\"text/javascript\">";

	echo "$(document).ready(function() {";
	if(count($nct_status)!=0)
	{
		echo "$(\"#nct_new\").progressBar();";
		echo "$(\"#nct_update\").progressBar({ barImage: 'images/progressbg_orange.gif'} );";
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
					echo "<td width=\"20%\" align=\"left\" class=\"head\">Status</td>";
					echo "<td width=\"20%\" align=\"left\" class=\"head\">Start Time</td>";
					echo "<td width=\"19%\" align=\"left\" class=\"head\">Excution run time</td>";
					echo "<td width=\"19%\" align=\"left\" class=\"head\">Last update time</td>";
					echo "<td width=\"17%\" align=\"left\" class=\"head\">Progress</td>";
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
			echo "</table>";
		}
		
		




}
?>
</body>