<?php
require_once('db.php');
require_once('include.util.php');
require_once('db.php');
require_once('include.import_pm.php');
require_once('pm_common.php');
ini_set('max_execution_time', '36000'); //10 hours
ignore_user_abort(true);
ini_set('error_reporting', E_ALL  );
//Globals
global $logger;
$days = 0;
$last_id = 0;

if(isset($_GET['days']))
{
	$days = (int)$_GET['days'];
	run_incremental_scraper($days);
}

function run_incremental_scraper($days=NULL)
{
	global $update_id;
	if(is_null($days)) $days = 1;
	else $days=$days+1;
	if($days>10) $days=10;
	$update_id=9;
	

	echo("\n<br />" . 'Begin pubmed updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");

	echo('Searching for pubmed records...' . "\n<br />");
	if(ok2runPMscraper())
		$ids = getIDs($days);
	else
	{
		$log= '<br> Pubmed scraper is not allowed to run at this time ( '.$day.'  '. date('h:ia', time()) .')';
		global $logger;	
		$logger->error($log);
		echo $log;
		die();
		return false;
	}
	
	if (count($ids) == 0) 
	{
		echo('There are none!' . "\n<br />");
		return false;
	} 
	$totcnt=count($ids);

	echo("<br /><br /> " . count($ids) . ' pubmed updates '. "\n<br />");
	echo('Fetching record content...' . "\n<br />");
	$progress_count = 2;
	$current_timestamp = time();
	
	/***** insert pubmed ids into a TEMPORARY MYSQL TABLE so that they can be used for indexing */
	/***** */
	$q1='DROP TEMPORARY TABLE IF EXISTS temp_table_1';
	mysql_query($q1) or die ("Query :".$q1. " Sql error : ".mysql_error());
	
	$q1 = "CREATE TEMPORARY TABLE temp_table_1 (`pubmed_id` int(15) NOT NULL)"; 
	mysql_query($q1) or die ("Query :".$q1. " Sql error : ".mysql_error());
	
	$pmids='';
	$counter=0;
	foreach($ids as $y)
	{
		
		if(!empty($pmids))
			$pmids.=',('.$y.')';
		else
			$pmids.='('.$y.')';
		$counter++;
		if($counter>=1000)
		{
			$q1 = " INSERT INTO temp_table_1 (`pubmed_id`) VALUES " . $pmids;
			if(!empty($pmids)) 
			{
				mysql_query($q1) or die($q1. "  "  . mysql_error());
			}
			$pmids='';
		}
		
	}
	$q1 = " INSERT INTO temp_table_1 (`pubmed_id`) VALUES " . $pmids;
		
	if(!empty($pmids)) 
	{
		mysql_query($q1) or die($q1. "  "  . mysql_error());
	}
	
	/************/
	/************/
	$counter=0;
	foreach ($ids as $id => $one) 
	{

		if(ok2runPMscraper())
			ProcessNew($one);
		else
		{
			$log= '<br> Pubmed scraper is not allowed to run at this time ( '.$day.'  '. date('h:ia', time()) .')';
			global $logger;	
			$logger->error($log);
			echo $log;
			die();
			return false;
		}
		
		if($current_timestamp == time()) 
		{
			sleep(1);
			$current_timestamp = time();
		}
		$counter++;
		
	}
		
	require_once('preindex_pmabstract.php');
	pmtindex(false,NULL,NULL,NULL,NULL,NULL,'YES');
	
	echo('Done with everything.');
}


?>  