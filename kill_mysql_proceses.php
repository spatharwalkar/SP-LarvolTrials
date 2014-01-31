<?php  
require_once('db.php');

$connectSigmaUser = mysql_connect(DB_SERVER, DB_USER_SIGMA, DB_PASS_SIGMA) or die("Error connecting to database server!");

$result = mysql_query("SHOW FULL PROCESSLIST", $connectSigmaUser);

// writing all the long running query to log file for review purpose
$processList  = "KILLED QUERY LOG ".date('m-d-Y H:i:s').PHP_EOL;

while ($row = mysql_fetch_assoc($result)) {

	//to log the quries for review purpose, we may comment it out later
	//$processList  .= $row['Id'].'::'.$row['User']."::".$row['Time']."::".$row['Info'];
	
	// if Query the query run from SIGMA and has been running since last 10 mins or more, we are killing the process.
	if($row['Time'] >= 600) {
		$process_id = $row['Id'];
		if(mysql_query("KILL $process_id", $connectSigmaUser))
		$processList  .= $row['Id'].'::'.$row['User']."::".$row['Time']."::".$row['Info'];
	}
}

// logging the killed queries
global $logger;
$logger->warn($processList);

//freeing the result of processlist
mysql_free_result($result);

//closing the DB_USER_SIGMA's DB connection
mysql_close($connectSigmaUser);
