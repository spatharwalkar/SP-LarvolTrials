<?php
session_start();
require_once('db.php');
global $db;
$loggedIn	= $db->loggedIn();

//Count the Number of Views in OTT
if(!is_array($_SESSION['larvolID_array']))
$_SESSION['larvolID_array']=array(); 
if(isset($_GET['larvol_id']) && isset($_GET['op']) && $_GET['op'] == 'Inc_ViewCount')
{
	$larvol_id= $_GET['larvol_id'];
	if(!$loggedIn)
	{
		if(!in_array($larvol_id, $_SESSION['larvolID_array']))
		{
			$INCLarvolID_sql = 'UPDATE data_trials SET viewcount=viewcount+1 WHERE larvol_id='.$larvol_id.'';
			$INCLarvolID= mysql_query($INCLarvolID_sql) or die(mysql_error());
			array_push($_SESSION['larvolID_array'], $larvol_id);
		}
	}	 
	$NewLarvolID_query=mysql_query("select viewcount from data_trials where larvol_id=".$larvol_id."");
	while($res=mysql_fetch_array($NewLarvolID_query))
	$ViewCount=$res['viewcount'];
	
	if($NewLarvolID_query && $ViewCount > 0)
	print '<span class="viewcount" title="Total views">'.$ViewCount.'&nbsp;</span>&nbsp;';	
}

//Count the Number of Views in OHM
if(!is_array($_SESSION['OHM_array']))
$_SESSION['OHM_array']=array(); 
if(isset($_GET['entity1']) && isset($_GET['entity2']) && isset($_GET['Cell_ID']) && isset($_GET['op']) && $_GET['op'] == 'Inc_OHM_ViewCount')
{
	$entity1 = trim($_GET['entity1']);
	$entity2 = trim($_GET['entity2']);
	if(!$loggedIn)
	{
		if(!in_array($entity1.'&'.$entity2, $_SESSION['OHM_array']))
		{
			$INCOHM_sql = "UPDATE `rpt_masterhm_cells` SET viewcount=viewcount+1 WHERE `entity1` = $entity1 AND `entity2` = $entity2";
			$INCOHM= mysql_query($INCOHM_sql) or die(mysql_error());
			array_push($_SESSION['OHM_array'], $entity1.'&'.$entity2);
		}
	}	 
	$NewOHM_query=mysql_query("select viewcount from `rpt_masterhm_cells` where `entity1` = $entity1 AND `entity2` = $entity2");
	while($res=mysql_fetch_array($NewOHM_query))
	$ViewCount=$res['viewcount'];
	
	if($NewOHM_query && $ViewCount > 0)
	print '<font style="color:#206040; font-weight: 900;">Number of Views: </font><font style="color:#000000; font-weight: 900;">'.$ViewCount.'</font><input type="hidden" value="'.$ViewCount.'" id="ViewCount_value_'.trim($_GET['Cell_ID']).'" />';	
}

?>