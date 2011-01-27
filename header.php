<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo(SITE_NAME); ?></title>
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
<?php echo($_GET['header']); ?>
</head>
<body>
<?php
echo('<a href="index.php" style="text-align:center;display:block;width:100%;">'
	 //. '<img src="images/logo.png" alt="Main" width="720" height="168" id="header" />'
	 //. '<img src="images/larvol_trials.png" alt="Main" width="363" height="99" id="header" />'
	 . '<img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" />'
	 . '</a><div id="bar"><div id="nav">');
if($db->loggedIn())
{
	echo('Search (<a href="search.php">Main</a>,<a href="search_simple.php">Simple</a>) :: <a href="inspect.php">ID Lookup</a>');
	echo(' :: <a href="import.php">XML Import</a> ');
	if($db->user->userlevel=='admin'||$db->user->userlevel=='root')
	{
		echo('::');
		echo('<div class="drop">Admin<br />'
			. '<a href="admin_users.php">Users</a><br />'
			. '<a href="custom.php">Field editor</a><br />'
			. '<a href="schedule.php">Scheduler</a><br />'
			. '<a href="admin_settings.php">Settings</a><br />'
			. '</div>');
	}
	echo('::<div class="drop">Reports<br />'
		. '<a href="report_heatmap.php">Heatmaps</a><br />'
		. '<a href="report_update.php">Update Scan</a><br />'
		. '<a href="report_competitor.php">Competitor Dashboard</a><br />'
		. '<a href="report_trial_tracker.php">Trial Tracker</a>'
		. '</div>');
	echo('</div>Welcome, <a href="profile.php">'
		. htmlspecialchars($db->user->username) . '</a> :: <a href="index.php?logout">Logout</a> &nbsp; </div>');
}else{
	echo('<a href="index.php">Main</a></div><a href="login.php">Login</a> &nbsp; </div>');
}

?>