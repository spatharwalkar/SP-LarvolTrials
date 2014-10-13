<?php
require_once('db.php');

/**
 * @name generateNews
 * @tutorial Function to generate news items. Calls the generateTrialNews procedure.
 * @param Int $days Identify trials that qualify Red Tags criteria in the past $days
 */

function generateNews($days)
{
	if (!isset($days) or empty($days) or $days <= 0 )
	{
		die('Number of days is not set');
	}
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Identifying Red Tags for trials updated in the past ' . $days . ' days...');
	ob_flush();
	flush();
	$query = 'call generateTrialNews(' . $days . ')';
	$res = mysql_query($query);
	if ($res === false)
		return softDie('Bad SQL query "call generateTrialNews('.$days.')"');
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Finished ...');
}

function generatePubmedNewsUsingID($sourceid)
{
	if (!isset($sourceid) or empty($sourceid) or $sourceid <= 0 )
	{
		pr('Source ID not provided.');
		return false;
	}
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Generating news for ' . $sourceid . ' ...');
	
	/************ index the abstract */
	$queryX = 'SELECT `pm_id` FROM  pubmed_abstracts where `source_id` =  ' . $sourceid . '  LIMIT 1';
	
	if(!$resX = mysql_query($queryX))
		{
			$log='There seems to be a problem with the SQL Query.  Query = :'.$queryX.' Error:' . mysql_error();
			echo $log;
			return false;
		}
	$resX = mysql_fetch_assoc($resX);
	$pm_id = $resX['pm_id'];
	if (isset($_POST['pm_id']))
	{
		require_once('preindex_pmabstract.php');
		pmtindex(false,NULL,NULL,NULL,NULL,array($pm_id));
	}
	/*************/
	ob_flush();
	flush();
	$query = 'call generatePubmedNewsUsingID(' . $sourceid . ')';
	$res = mysql_query($query);
	if ($res === false)
		return softDie('Bad SQL query "call generatePubmedNewsUsingID('.$sourceid.')"');
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Finished ...');
}
?>