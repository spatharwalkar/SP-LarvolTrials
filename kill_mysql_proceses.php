<?php  
require_once('db.php');

//echo DB_USER_SIGMA."------<br/>";
$result = mysql_query("SHOW FULL PROCESSLIST"); 

// writing all the long running query to log file for review purpose
$processList  = "KILLED QUERY LOG ".date('m-d-Y H:i:s').PHP_EOL;

while ($row = mysql_fetch_assoc($result)) {

	//to log the quries for review purpose, we may comment it out later
	//$processList  .= $row['Id'].'::'.$row['User']."::".$row['Time']."::".$row['Info'];
		
	// if Query the query run from SIGMA and has been running since last 10 mins or more, we are killing the process.
	if($row['User'] == DB_USER_SIGMA && $row['Time'] >= 600) {
		$process_id = $row['Id'];
		mysql_query("KILL $process_id");	
	}
}

// logging the killed queries
global $logger;
$logger->error($processList);

mysql_free_result($result);
