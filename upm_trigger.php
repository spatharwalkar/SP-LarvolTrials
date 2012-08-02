<?php
require_once('db.php');

// Fire the UPM trigger for ALL records.
function fire_upm_trigger()  
{
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

// Fire the UPM trigger for records having a particular status.
function fire_upm_trigger_st($st)  
{
	$trigger='	UPDATE upm 
				SET end_date_type=end_date_type
				WHERE status="' . $st . '" ';
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

// Fire the UPM trigger for records having end_date in the past.
function fire_upm_trigger_dt()  
{
	$trigger='	UPDATE upm 
				SET end_date_type=end_date_type
				WHERE end_date <= left(now(),10)';
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