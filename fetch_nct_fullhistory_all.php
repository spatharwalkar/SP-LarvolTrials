<?php
require_once('db.php');
require_once('include.search.php');

if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
echo str_repeat ("   ", 1500);
echo '<br>';
global $pr_id;
global $cid;
global $maxid;
$cmd = "ps aux|grep fullhistory.php";
exec($cmd, $output, $result);
for($i=0;$i < count($output); $i++)
{
	$output[$i] = preg_replace("/ {2,}/", ' ',$output[$i]);
	$exp_out=explode(" ",$output[$i]);
	if (preg_match("/fetch_nct_fullhistory.php/i", $exp_out[11])) die('fetch_nct_fullhistory.php is already running');
}
ini_set('max_execution_time', '36000'); //10 hours
ignore_user_abort(true);

$query = 'SELECT * FROM update_status_fullhistory where status="1" order by update_id desc limit 1' ;
$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
$res = mysql_fetch_array($res) ;

if ( isset($res['process_id']) )
{
	
	$pid = getmypid();
	$up_id= ((int)$res['update_id']);
	$cid = ((int)$res['current_nctid']); 
	$maxid = ((int)$res['max_nctid']); 
	$query = 'UPDATE  update_status_fullhistory SET status= "2"  WHERE process_id = "' . $pr_id .'" ;' ;
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query );
	fetch_records($pid,$cid,$maxid,$up_id);
	exit;
}

else
{
	$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
	$res = mysql_query($query) or die('Bad SQL query finding highest update id');
	$res = mysql_fetch_array($res) ;
	$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
	$fid = getFieldId('NCT','nct_id');
	$query = 'SELECT MAX(val_int) AS maxid FROM data_values WHERE `field`=' . $fid;
	$res = mysql_query($query) or die('Bad SQL query finding highest nct_id');
	$res = mysql_fetch_array($res) or die('No nct_id found!');
	$maxid = $res['maxid'];
	//find start id

	$cid = (isset($_GET['start']) && is_numeric($_GET['start'])) ? ((int)$_GET['start']) : 103;  // 102 is the starting NCTID in ct.gov

	/***************************************************/
	//$maxid = $cid+40;  
	/***************************************************/

	$cid_=$cid;
	
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
	$pid = getmypid();

	if ($totalncts > 0)
	{
	$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,max_nctid) 
			  VALUES ("'.$up_id.'","'. $pid .'","'. 2 .'",
			  "' . $totalncts . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "'. $maxid .'"  ) ;';
	$res = mysql_query($query) or die('Bad SQL query updating update_status_fullhistory. Query:' . $query);
	}
	else die("No valid nctids found.");

	//go
	echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
	fetch_records($pid,$cid,$maxid,$up_id);
}

function fetch_records($pr_id,$cid,$maxid,$up_id)
{ 	
	$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" limit 1 ;' ;
	$res = mysql_query($query) or die('Bad SQL query selecting row from update_status_fullhistory ');
	$res = mysql_fetch_array($res) ;
	if ( isset($res['update_items_progress'] ) and $res['update_items_progress'] > 0 ) $updtd_items=((int)$res['update_items_progress']); else $updtd_items=0;
	if ( isset($res['update_items_total'] ) and $res['update_items_total'] > 0 ) $tot_items=((int)$res['update_items_total']); else $tot_items=0;
	
	for($i=1; $cid <= $maxid; $cid=$cid+1)
	{
		$vl = validate_nctid($cid,$maxid);
		if( isset($vl[1] )) 
		{
		$cid=$vl[1];
		$_GET['id'] = $cid;
		require('fetch_nct_fullhistory.php');
		++$i;
		$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $pr_id  .'" , update_items_progress= "' . ( ($tot_items >= $updtd_items+$i) ? ($updtd_items+$i) : $tot_items  ) . '" , status="2", current_nctid="'. $cid .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" ;' ;
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
	$query = 'select val_int from data_values where field = "1" and val_int>="'.$ncid.'" and val_int<="'.$mxid.'" limit 1 ;'; 
	//echo('<br>query=' .$query. '<br>' );
	$res = mysql_query($query) or die('checking of NCTID  Failed. Query:' . $query );
	$res = mysql_fetch_assoc($res);
	if (isset($res['val_int']))
		return array(true,$res['val_int']);
	else
		return array(false,0);
}
?>