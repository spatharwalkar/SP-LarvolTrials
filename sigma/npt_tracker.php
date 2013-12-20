<?php
	header('P3P: CP="CAO PSA OUR"');
	session_start();
	//connect to Sphinx
	if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
	
	$_REQUEST['sourcepg']='TZP';
	$cwd = getcwd();
	chdir ("..");
	require_once('db.php');
	require_once('include.util.php');
	require_once('intermediary.php');
	chdir ($cwd);
	require_once('disease_tracker.php');
	require_once('investigators_tracker.php');
	require_once('news_tracker.php');
	
	$e1 = NULL;
	if( ($_REQUEST['er']=='1%20month') or ($_REQUEST['er']=='1 month') )
		$_REQUEST['er']='1 month';
	
	$tid = NULL;
	if($_REQUEST['tid'] != NULL && $_REQUEST['tid'] != '' && isset($_REQUEST['tid']))
	{
		/*$tid = $_REQUEST['tid'];
		$query = 'SELECT `intervention_name` FROM `data_trials` WHERE `larvol_id` in(' . mysql_real_escape_string($tid).')';
		$res = mysql_query($query) or die($query.' '.mysql_error());
		$header = mysql_fetch_array($res);*/
		$ProductName = $_REQUEST['nptname'];		
	}
	$page = 1;
	if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']))
	{
		$page = mysql_real_escape_string($_REQUEST['page']);
	}	
	$tidArr = explode(',',$tid);
	$TabTrialsCount = count($tidArr);
	$meta_title = 'Larvol Sigma'; //default value
	$meta_title = isset($ProductName) ? $ProductName. ' - '.$meta_title : $meta_title;	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $meta_title; ?></title>
	<style type="text/css">
		body
		{
			font-family:Arial;
			font-size:14px;
			color:#000000;
		}

		a {color:#1122cc;}      /* unvisited link */
		a:visited {color:#6600bc;}  /* visited link */
		/*a:hover {color:#FF00FF;}  /* mouse over link */
		/*a:active {color:#0000FF;}  /* selected link */

		.ReportHeading
		{
			color: rgb(83, 55, 130);
			font-size: xx-large;
			font-weight: bold;
		}

		.SearchBttn1
		{
			width:100px;
			height:35px;
			background-color:#4f2683;
			font-weight:bold;
			color:#FFFFFF;
		}

		.FoundResultsTb
		{
			background-color:#aa8ece;
			border:0;
			border-top:#4f2683 solid 2px;
		}

		#FoundResultsTb a 
		{
			display:inline;
		}
	</style>
	<!--tab css-->
	<style>
		.selectTab
		{
			background-image:url(../images/selectTab.png); 
			background-repeat:repeat-x;
		}

		.Tab
		{
			background-image:url(../images/Tab.png); 
			background-repeat:repeat-x;
		}

		#disease_tabs a
		{
			text-decoration:none;
			color:#000000;
			font-size:13px;
			font-family:Arial, Helvetica, sans-serif;
			display:block;
		}

		#diseaseTab_content
		{
			background-color: #ffffff;
			padding: 30px;
			border-top:#333333 solid 1px;
		}
	</style>
	<script src="scripts/jquery-1.7.1.min.js"></script>
	<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
	<script type="text/javascript" src="scripts/chrome.js"></script>
	<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
</head>

<body>
	<?php include "searchbox.php";?>
	<!-- Number of Results -->
	<br/>
	<table width="100%" border="0" class="FoundResultsTb">
		<tr>
			<td width="50%" style="border:0; font-weight:bold; padding-left:5px; color:#FFFFFF; font-size:23px; vertical-align:middle;" align="left">
				<table><tr>
				<?php 
					print '<td style="vertical-align:top;"><a style="color:#FFFFFF; display:inline; text-decoration:underline;" href="npt_tracke.php?tid='.$tid.'&nptname='.$ProductName.'">'.$ProductName.'</a>'.'&nbsp; </td>';
				?>
				</tr></table>
			</td>
			<td width="50%" align="right" style="border:0; font-weight:bold; padding-right:5px;">			
			<?php
			if($db->loggedIn()) {
				echo('<div style="padding-left:10px;float:right;">Welcome, <a  style="display:inline;"  href="profile.php">'
					. htmlspecialchars($db->user->username) . '</a> :: <a  style="float:right;width:50px;"  href="login.php?logout=true">Logout</a> &nbsp; </div>');
			} else {
				echo ('<div style="padding-left:10px;float:right;font-size:18px;"><a href="login.php">login</a></div>');
			}
			?>			
			</td>
		</tr>
	</table>

	<!-- Displaying Records -->
	<br/>
	<table width="100%" border="0" style="" cellpadding="0" cellspacing="0">
		<tr>
			<td align="center">
				<?php
				print '<div id="diseaseTab_content" align="center">';
				chdir ("..");
				DisplayOTT(); //SHOW OTT 	
				chdir ("$cwd");				
				print '</div>';
				?>
			</td>
		</tr>

	</table>
	<br/><br/>	
	<?php include "footer.php" ;?>
	</body>
</html>