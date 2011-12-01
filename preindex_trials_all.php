<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('preindex_trial.php');
global $logger;
if( (!isset($_POST['mode']) or $_POST['mode']<>'start') and !isset($_GET['productid']) and !isset($_GET['areaid']) )
{
	echo ' 
	<form name="mode" action="preindex_trials_all.php" method="POST">
	<div align="center"><br><br><br><br><br><br><br><br><hr /><br>
	<input type="hidden" value="start" name="mode">
	&nbsp; &nbsp; &nbsp;
	&nbsp; &nbsp; &nbsp;
	<input type="submit" name="submit" value=" Preindex  ALL  products  and  areas " /><br><br>
	<hr /><br>
	</div>
	</form><br>
	 ';
	 exit;
}
echo str_repeat ("   ", 1500);
echo '<br>';
global $pr_id;
global $cid;
global $maxid;
ini_set('max_execution_time', '9000000'); //250 hours
ignore_user_abort(true);

//get product ids
if(isset($_GET['productid'])) $and = ' and id=' . $_GET['productid'] ;
else $and = '';
$productz=array();
$query = 'SELECT id,name,searchdata from products where searchdata IS NOT NULL ' . $and ;
if(!$resu = mysql_query($query))
{
	$log='Bad SQL query getting  details from products table.<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	exit;
}
while($productz[]=mysql_fetch_array($resu));

//get area ods
if(isset($_GET['areaid'])) $and = ' and id=' . $_GET['areaid'] ;
else $and = '';
$areaz=array();
$query = 'SELECT id,name,searchdata from areas where searchdata IS NOT NULL ' . $and ;
if(!$resu = mysql_query($query))
{
	$log='Bad SQL query getting  details from areas table.<br>Query=' . $query;
	$logger->fatal($log);
	echo $log;
	exit;
}
while($areaz[]=mysql_fetch_array($resu));


/*///x/x/x/x/x/x/x*/
$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="PRODUCT" order by update_id desc limit 1' ;
$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
$res = mysql_fetch_array($res) ;
	
if ( isset($res['process_id']) )
{
	$pr_id=$res['process_id'];
	$pid = getmypid();
	$up_id= ((int)$res['update_id']);
	$cid = ((int)$res['current_nctid']); 
	$maxid = ((int)$res['max_nctid']); 
	
	$cid_=$cid;
	$totalncts = count($productz);
	$pid = getmypid();

	$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message=""  WHERE update_id = "' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
}

else
{
	$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
	$res = mysql_query($query) or die('Bad SQL query finding highest update id');
	$res = mysql_fetch_array($res) ;
	$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;

	if(!isset($productz))
	{
		$query = 'SELECT MAX(id) AS maxid FROM products ';
		$res = mysql_query($query) or die('Bad SQL query finding highest nct_id');
		$res = mysql_fetch_array($res) or die('No product id found!');
		$maxid = $res['maxid'];
		$cid = (isset($_GET['start']) && is_numeric($_GET['start'])) ? ((int)$_GET['start']) : 1;  
	}
	else
	{
		reset($productz); $val=key($productz); $cid = $val;
		end($productz); $val=key($productz); $maxid = $val;
	}


	$cid_=$cid;
	$totalncts = count($productz);
	$pid = getmypid();

	if ( $totalncts > 0 and !(isset($_GET['productid']) and $_GET['productid']==0) )
	{
	$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid,trial_type) 
			  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
			  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'", "PRODUCT")';
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}
	//else die("No valid product ids found.");
}
// go for products
$pid = getmypid();
echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
echo('<br>Associating trials with all PRODUCTS ..<br><br>');
tindex(NULL,'products',$productz,$up_id,$cid);
$query = 'UPDATE  update_status_fullhistory SET status=0, end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );


/************************************************/
/************************************************/

$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="AREA" order by update_id desc limit 1' ;
$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
$res = mysql_fetch_array($res) ;
	
if ( isset($res['process_id']) )
{
	$pr_id=$res['process_id'];
	$pid = getmypid();
	$up_id= ((int)$res['update_id']);
	$cid = ((int)$res['current_nctid']); 
	$maxid = ((int)$res['max_nctid']); 
	
	$cid_=$cid;
	$totalncts = count($areaz);
	$pid = getmypid();

	$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message=""  WHERE update_id = "' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
}

else
{
	$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
	$res = mysql_query($query) or die('Bad SQL query finding highest update id');
	$res = mysql_fetch_array($res) ;
	$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;

	if(!isset($areaz))
	{
		$query = 'SELECT MAX(id) AS maxid FROM areas ';
		$res = mysql_query($query) or die('Bad SQL query finding highest nct_id');
		$res = mysql_fetch_array($res) or die('No Area id found!');
		$maxid = $res['maxid'];
		$cid = (isset($_GET['start']) && is_numeric($_GET['start'])) ? ((int)$_GET['start']) : 1;  
	}
	else
	{
		reset($areaz); $val=key($areaz); $cid = $val;
		end($areaz); $val=key($areaz); $maxid = $val;
	}


	$cid_=$cid;
		$totalncts = count($areaz);
	$pid = getmypid();

	if ( $totalncts > 0 and !(isset($_GET['areaid']) and $_GET['areaid']==0) )
	{
	$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid,trial_type) 
			  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
			  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'", "AREA")';
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}
	else die("No valid area ids found.");
}
	//go
	echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
	
	echo('<br>Associating trials with all AREAS ..<br><br>');
	tindex(NULL,'areas',$areaz,$up_id,$cid);
	$query = 'UPDATE  update_status_fullhistory SET status=0,  end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $up_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
/*///x/x/x/x/x/x/x*/



