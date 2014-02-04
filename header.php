<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title><?php echo(SITE_NAME); ?></title>
<link href="css/main.css" rel="stylesheet" type="text/css" media="all" />
<link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
<link href="scripts/date/jdpicker.css" rel="stylesheet" type="text/css" media="screen" /> 
<link href="krumo/skin.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/colorbox.css" rel="stylesheet" type="text/css" media="all" />
<?php if($USE_OLD_JQUERY){?>
  <script type="text/javascript" src="date/jquery.js"></script>
<?php }else{ ?>  
<script type="text/javascript" src="scripts/jquery-1.9.1.js"></script>
<?php } ?>  
<script type="text/javascript" src="date/jquery.date_input.js"></script>
<script type="text/javascript" src="scripts/date/jquery.jdpicker.js"></script>
<script type="text/javascript" src="date/init.js"></script>
<script type="text/javascript" src="krumo/krumo.js"></script>
<script type="text/javascript" src="scripts/autosuggest/jquery.autocomplete-min.js"></script>
<script type="text/javascript" src="scripts/colorbox/jquery.colorbox-min.js"></script>
<script type="text/javascript" src="scripts/jquery.sqlbuilder-0.06.js"></script>

<?php echo($HEADER_INCLUDES);
if($db->user->userlevel == 'public') die('<p>Access Forbidden : It seems that you are logged in as a public user. To continue access to <b>LT</b> please <a href = ' . urlPath() . 'index.php?logout><b>logout</b></a> and <b>login</b> again as <b>Admin</b>.</p>'); // Modified By PK
?>

<link rel="stylesheet" type="text/css" href="css/jquery.sqlbuilder.css" />
<style type="text/css">
#sqlreport
{
	/* border: 1px solid #ccc;*/
	position: relative;
	width: 1200px;
	height: 600px;
	margin: 5px;
	padding: 5px;
	font-family: "Verdana" , "Tahoma" , Arial;
	font-size: 12px;
	overflow: auto;
}
.sameLine 
{
  text-align: left;
  vertical-align:middle;
  display: table-cell;
  /*min-width: 2px;
  padding-right: 2px;*/
}

.sqlbuild
{
	/*       border: 1px solid #ccc; /*	float:left;*/
	position: relative;
	width: 800px;
	margin: 5px;
	padding: 5px;
	font-family: "Verdana" , "Tahoma" , Arial;
	font-size: 12px;
	overflow: auto;
}
.sqlsyntaxhelp
{
	font-family: "Verdana" , "Tahoma" , Arial;
	font-size: 10px;
	color: #FF0000;
}
</style>
<?php if($_GET && isset($_GET['header'])) { echo($_GET['header']); }
include_once('ga.php');
echo ga("LT");
?>
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
	echo('<a href="search.php">Search</a> :: <a href="edit_trials.php">ID Lookup</a>');
	echo(' :: ');
	echo('<div class="drop">Editing<br/>'
	.'<a href="edit_trials.php">Trial Entry</a><br/>'
	.'<a href="upm.php">UPM</a><br/>'
	.'<a href="entities.php?entity=areas">Areas</a><br/>'
	.'<a href="entities.php?entity=products">Products</a><br/>'
	.'<a href="entities.php?entity=diseases">Diseases</a><br/>'
	.'<a href="entities.php?entity=diseasecategory">Disease Category</a><br/>'
	.'<a href="entities.php?entity=moas">MOAs</a><br/>'
	.'<a href="entities.php?entity=moacategories"> MOA Categories </a><br/>'
	.'<a href="entities.php?entity=institutions">Institutions</a><br/>'
	.'<a href="entities.php?entity=investigator">Investigators</a><br/>')
	;
	if($db->user->userlevel=='admin'||$db->user->userlevel=='root' || $db->user->userlevel=='user')
		echo ('<a href="entities.php?entity=entities">Entities</a><br/>');
	echo ('<a href="redtags.php">Redtags</a><br/>'
	.'<a href="edit_news.php">News</a><br/>'
	.'</div>');
	
	if($db->user->userlevel=='root')
		$dbpage='<a href="database.php">Database</a><br />';
	else
		$dbpage='';
	
	if($db->user->userlevel=='admin'||$db->user->userlevel=='root')
	{
		echo('::');
		echo('<div class="drop">Admin<br />'
			. '<a href="admin_users.php">Users</a><br />'
			. '<a href="schedule.php">Scheduler</a><br />'
			. '<a href="status.php">Status</a><br />'
			. $dbpage
			. '<a href="admin_settings.php">Settings</a><br />'			
			. '<a href="upt.php">Unknown Product Trials</a><br />'
			. '<a href="admin_ga.php">Google Analytics Profiles</a><br />'			
			. '</div>');
	}
	echo('::<div class="drop">Heatmap<br />'
		. '<a href="master_heatmap.php">Old HM Editor</a><br />'
		. '<a href="heatmap_edit.php">New HM Editor</a><br />'
		. '</div>');
	echo('</div>Welcome, <a href="profile.php">'
		. htmlspecialchars($db->user->username) . '</a> :: <a href="index.php?logout">Logout</a> &nbsp; </div>');
}else{
	echo('<a href="index.php">Main</a></div><a href="login.php">Login</a> &nbsp; </div>');
}

?>