<?php
//Count the Nummber of View of Records diplayes in OTT
require_once('db.php');
global $db;
$loggedIn	= $db->loggedIn();

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
	print '<font size="1px" style="background-color:#CCCCCC">'.$ViewCount.'&nbsp;</font>';	
}
?>