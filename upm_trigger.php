<?php
require_once('db.php');

function fire_upm_trigger()  
{
// Fire the UPM trigger
	$trigger='	UPDATE upm 
				SET end_date_type=end_date_type
				WHERE id > "0" ';
	$res = mysql_query($trigger);
	if($res === false)
	{
		$log = 'Could not fire the trigger to update UPM "status" values. mysql_error=' . mysql_error() . ' query=' . $trigger;
		global $logger;
		$logger->fatal($log);
		echo($log);
		return false;
	}
	return true;
}
?>  