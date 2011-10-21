<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');

if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
if(!isset($_POST['mode'])) 
{
echo ' 

<form name="mode" action="fetch_nct_fullhistory_all.php" method="POST">
<div align="center"><br><br><br><br><hr />
<input type="radio" name="mode" value="db" checked> Use database for validating NCTIDs 
&nbsp; &nbsp; &nbsp;
<input type="radio" name="mode" value="web"> Use clinicaltrials.gov for validating NCTIDs
&nbsp; &nbsp; &nbsp;
<input type="submit" name="submit" value="Start Import" />
<hr />
</div>
</form>
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
if($_POST['mode']=='web') $nct_ids=get_nctids_from_web();
if(!isset($nct_ids))
{
	$query = 'SELECT * FROM update_status_fullhistory where status="1" and trial_type="NCT" order by update_id desc limit 1' ;
	$res = mysql_query($query) or die('Bad SQL query finding ready updates ');
	$res = mysql_fetch_array($res) ;
}	
if ( isset($res['process_id']) )
{
	
	$pid = getmypid();
	$up_id= ((int)$res['update_id']);
	$cid = ((int)$res['current_nctid']); 
	$maxid = ((int)$res['max_nctid']); 
	$query = 'UPDATE  update_status_fullhistory SET status= "2",er_message=""  WHERE process_id = "' . $pr_id .'" ;' ;
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
		reset($nct_ids); $val=key($nct_ids); $cid = unpadnct($val);
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

	//go
	echo('Refreshing from: ' . $cid . ' to: ' . $maxid . '<br />'); @flush();
	fetch_records($pid,$cid,$maxid,$up_id);
}

function fetch_records($pr_id,$cid,$maxid,$up_id)
{ 	
	$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" and trial_type="NCT" limit 1 ;' ;
	$res = mysql_query($query) or die('Bad SQL query selecting row from update_status_fullhistory ');
	$res = mysql_fetch_array($res) ;
	if ( isset($res['update_items_progress'] ) and $res['update_items_progress'] > 0 ) $updtd_items=((int)$res['update_items_progress']); else $updtd_items=0;
	if ( isset($res['update_items_total'] ) and $res['update_items_total'] > 0 ) $tot_items=((int)$res['update_items_total']); else $tot_items=0;
	
	$v1=array();
	for($i=1; $cid <= $maxid; $cid=$cid+1)
	{
		if(!isset($nct_ids)) $vl = validate_nctid($cid,$maxid);
		else $v1[1]=isset($nct_ids[padnct($cid)]) ? $cid : NULL ;
		if( isset($vl[1] )) 
		{
		$cid=$vl[1];
		$_GET['id'] = $cid;
		require('fetch_nct_fullhistory.php');
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
	$query = 'select val_int from data_values where field = "1" and val_int>="'.$ncid.'" and val_int<="'.$mxid.'" order by val_int limit 1 ;'; 
	//echo('<br>query=' .$query. '<br>' );
	$res = mysql_query($query) or die('checking of NCTID  Failed. Query:' . $query );
	$res = mysql_fetch_assoc($res);
	if (isset($res['val_int']))
		return array(true,$res['val_int']);
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
			echo ' . ';
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


?>