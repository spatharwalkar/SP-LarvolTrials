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
		die('Source ID not provided.');
	}
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Generating news for ' . $sourceid . ' ...');
	ob_flush();
	flush();
	$query = 'call generatePubmedNewsUsingID(' . $sourceid . ')';
	$res = mysql_query($query);
	if ($res === false)
		return softDie('Bad SQL query "call generatePubmedNewsUsingID('.$sourceid.')"');
	echo('<br><b>' . date('Y-m-d H:i:s') .'</b> - Finished ...');
}
?>