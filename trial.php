<?php
//connect to Sphinx
if(!isset($sphinx) or empty($sphinx)) $sphinx = @mysql_connect("127.0.0.1:9306") or $sphinx=false;
require_once('db.php');

//ini_set('error_reporting', E_ALL ^ E_NOTICE);
global $logger;


// The table is not displayed properly in Chrome, but works fine in MSIE and FireFox.  Something to do with Doctype
// So a hack is used to fix the issue. 
//if(stripos($_SERVER['HTTP_USER_AGENT'],'chrome')) echo '<!DOCTYPE>';
		
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
	
	foreach($fields as $field => $field_name)
	{
		$mapping_eudract[$field] 	= $field;
		$mapping_nct[$field] 		= $field;
	}
	$mapping_eudract['source_id'] = 'eudract_id';
	$mapping_nct['source_id'] 		= 'nct_id';
	
?>
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

table#table-trial
{
width:100%;
table-layout:fixed;
}
#container-trial #table-trial th,
#container-trial #table-trial td
{
width: 15%;
word-wrap: break-word;
height: 50px;
}
</style>
<div id="container-trial"  style="margin:8px">

<br />

<div id="tab-eudract">
Source Eudract:
<?php 
if($eudracts['eudract_id'] != ''){
	$ctLink = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . padeudra($eudracts['eudract_id']);
	echo "<a href='".$ctLink."'>$ctLink</a>";
}else{
	echo 'None';
}

?>
</div>

<div id="tab-nct">
Source Nct:
<?php 
if($ncts['nct_id'] != ''){
	$ctLink = 'http://clinicaltrials.gov/ct2/show/' . padnct($ncts['nct_id']);
	echo "<a href='".$ctLink."'>$ctLink</a>";
}

?>
</div>


<p>&nbsp;</p>

<table cellspacing="0" cellpadding="5" id="table-trial">
        <thead>
            <tr>
            <th>Fields</th>
            <th>Manual</th>
            <th>Eudract</th>
            <th>NCT</th>
            <th>Current Value</th>
            <th>Previous Value</th>
            <th>Last changed on</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 0; foreach($fields as $field_key => $field_name){ $i++;?>
            <tr><th><?php echo $i.'. '.$field_name?> </th>
            <td><?php echo (isset($manuals[$field_key]) ? $manuals[$field_key] : '');?></td>
            <td><?php echo (isset($eudracts[$mapping_eudract[$field_key]] ) ? $eudracts[$mapping_eudract[$field_key]] : '');?></td>
            <td><?php echo (isset($ncts[$mapping_nct[$field_key]]) ? $ncts[$mapping_nct[$field_key]] : '');?></td>
            <td><?php echo (isset($trials[$field_key]) ? $trials[$field_key] : '');?></td>
            <td><?php echo (isset($history[$field_key.'_prev']) ? $history[$field_key.'_prev'] : '');?></td>
            <td><?php echo (isset($history[$field_key.'_lastchanged']) ? $history[$field_key.'_lastchanged'] : '');?></td></tr>
		<?php } ?>
        </tbody>
</table>
    

</div>