<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
require_once('db.php');

//ini_set('error_reporting', E_ALL ^ E_NOTICE);
global $logger;
$table = 'products';
$table1 = 'data_trials';
$script = 'edit_trials';

// The table is not displayed properly in Chrome, but works fine in MSIE and FireFox.  Something to do with Doctype
// So a hack is used to fix the issue. 
if(stripos($_SERVER['HTTP_USER_AGENT'],'chrome')) echo '<!DOCTYPE>';
		
require_once('header.php');	
global $db;

	
	$lid = $_REQUEST['id'];//`source_id`,`is_sourceless`
	$query = "
	SELECT *
	FROM `data_manual`
	WHERE `larvol_id` = $lid limit 1
	";
	$res1 		= mysql_query($query) ;
	
	
	if($res1===false)
	{
	$log = 'Bad SQL query. Query=' . $query;
	$logger->fatal($log);
	echo $log;
	die($log);
	}
	
	$query = "
			SELECT *  
			FROM `data_trials` 
			WHERE `larvol_id` = $lid limit 1
			";
	$res2 		= mysql_query($query) ;
	
	if($res2===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		die($log);
	}

	
	$query = "
	SELECT *
	FROM `data_eudract`
	WHERE `larvol_id` = $lid limit 1
	";
	$res3 		= mysql_query($query) ;
	
	if($res3===false)
	{
	$log = 'Bad SQL query. Query=' . $query;
	$logger->fatal($log);
	echo $log;
	die($log);
	}

	
	$query = "
	SELECT *
	FROM `data_nct`
	WHERE `larvol_id` = $lid limit 1
	";
	$res4 		= mysql_query($query) ;
	
	if($res3===false)
	{
	$log = 'Bad SQL query. Query=' . $query;
	$logger->fatal($log);
	echo $log;
	die($log);
	}

	
	$query = "
	SELECT *
	FROM `data_nct`
	WHERE `larvol_id` = $lid limit 1
	";
	$res4 		= mysql_query($query) ;
	
	if($res4===false)
	{
	$log = 'Bad SQL query. Query=' . $query;
	$logger->fatal($log);
	echo $log;
	die($log);
	}
	
	$query = "
	SELECT *
	FROM `data_history`
	WHERE `larvol_id` = $lid limit 1
	";
	$res5 		= mysql_query($query) ;
	
	if($res5===false)
	{
	$log = 'Bad SQL query. Query=' . $query;
	$logger->fatal($log);
	echo $log;
	die($log);
	}
	
	$manuals=mysql_fetch_assoc($res1);
	$trials=mysql_fetch_assoc($res2);
	$eudracts=mysql_fetch_assoc($res3);
	$ncts=mysql_fetch_assoc($res4);
	$history=mysql_fetch_assoc($res5);
	
?>

</head>
<?php
/********* check if fieldname exists */ 
$query = 	"
			SELECT `COLUMN_NAME` 
			FROM `INFORMATION_SCHEMA`.`COLUMNS` 
			WHERE `TABLE_NAME`='data_trials'
			";

	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query getting column names from data schema . Query=' . $query;
		$logger->fatal($log);
		echo $log;
		die($log);
	}
	$cols=array();
	//$cols[]='dummy';
	while($x=mysql_fetch_assoc($res1)) $cols[]=$x['COLUMN_NAME'];
	
	$fields = array();
	foreach($cols as $col){
		$fields[$col] = ucfirst(str_replace( "_", " ", $col));
	}
	
?>
<script type="text/javascript" src="scripts/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="screen" />
 <script>
$(function() {
$( "#trial_tabs" ).tabs();
});
</script>
 <style>
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

#FoundResultsTb a {
display:inline;
}
.selectTab
{
	background-image:url(images/selectTab.png); 
	background-repeat:repeat-x;
}

.Tab
{
	background-image:url(images/Tab.png); 
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
	border-top:#d333333 solid 1px;
}

.list_fileds{list-style-type:none; padding-bottom: 20px;}
label{font-weight:bold; margin-bottom: -6px;background: none;}
.ui-widget-content{background: none;}
.ui-widget{font-family:Arial;}
</style>
<div id="container-trial"  style="margin:8px">

<?php  
//echo '<pre>';
//print_r($opts);
//print_r($hint);
?>	
<br />

		
<div id="trial_tabs" class="trial_tabs" style="">
	<ul>
		<li><a href="#tab-manual">Manual</a></li>
		<li><a href="#tab-eudract">EudraCT</a></li>
		<li><a href="#tab-nct">NCT</a></li>
		<li><a href="#tab-trial">Current Value</a></li>
		<li><a href="#tab-prev">Previous Value</a></li>
		<li><a href="#tab-lastchangedon">Last changed on</a></li>
	</ul>

	<div id="tab-manual">
	<?php foreach($fields as $field_key => $field_value){ ?>
		<li class="list_fileds" id="manual_<?php echo $field_key;?>">
			<label><?php echo $field_value?> : </label>
					<?php echo (isset($manuals[$field_key]) ? $manuals[$field_key] : '');?>
		</li>
	<?php } ?>
	</div>

	<div id="tab-eudract">
	<?php foreach($fields as $field_key => $field_value){ ?>
		<li class="list_fileds" id="eudract_<?php echo $field_key;?>">
			<label><?php echo $field_value?> : </label>
					<?php echo (isset($eudracts[$field_key]) ? $eudracts[$field_key] : '');?>
		</li>
	<?php } ?>
	</div>
	
	<div id="tab-nct">
	<?php foreach($fields as $field_key => $field_value){ ?>
		<li class="list_fileds" id="nct_<?php echo $field_key;?>">
			<label><?php echo $field_value?> : </label>
					<span><?php echo (isset($ncts[$field_key]) ? $ncts[$field_key] : '');?></span>
		</li>
	<?php } ?>
	</div>

	<div id="tab-trial">
	<?php foreach($fields as $field_key => $field_value){ ?>
		<li class="list_fileds" id="trial_<?php echo $field_key;?>">
			<label><?php echo $field_value?> : </label>
					<?php echo (isset($trials[$field_key]) ? $trials[$field_key] : '');?>
		</li>
	<?php } ?>
	</div>

	<div id="tab-prev">
	<?php foreach($fields as $field_key => $field_value){ ?>
		<li class="list_fileds" id="prev_<?php echo $field_key;?>">
			<label><?php echo $field_value?> : </label>
					<?php echo (isset($history[$field_key.'_prev']) ? $history[$field_key.'_prev'] : '');?>
		</li>
	<?php } ?>
	</div>
	
	<div id="tab-lastchangedon">
	<?php foreach($fields as $field_key => $field_value){ ?>
		<li class="list_fileds" id="lastchangedon_<?php echo $field_key;?>">
			<label><?php echo $field_value?> : </label>
					<?php echo (isset($history[$field_key.'_lastchanged']) ? $history[$field_key.'_lastchanged'] : '');?>
		</li>
	<?php } ?>
	</div>
	
</div>



</div>