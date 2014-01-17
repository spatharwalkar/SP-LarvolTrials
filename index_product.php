<?php
require_once('db.php');
require_once('preindex_trial.php');
ini_set('max_execution_time', '36000'); //10 hours
ignore_user_abort(true);

if(isset($_GET['connection_close']) && $_GET['connection_close']==1)
{
	ob_start();
	// get the size of the output
	$size = ob_get_length();
	
	// send headers to tell the browser to close the connection
	header("Content-Length: $size");
	header('Connection: close');
	
	// flush all output
	ob_end_flush();
	ob_flush();
	flush();
	// close current session
	if (session_id()) session_write_close();
}

if (isset($_GET['id'])) 
{
    $productID = $_GET['id'];
} else 
{
    die('No ID passed');
}
tindex(NULL,'products',NULL,NULL,NULL,$productID);
echo '<br><br>All done.<br>';

// recalculate mhm cells without recording changes incase of regex change
if(isset($_GET['ignore_changes']) and !empty($productID))
{
	$parameters=array(); 
	$parameters['product']=$productID;
	require_once('calculate_hm_cells.php');
	calc_cells($parameters,NULL,$_GET['ignore_changes']);
}


?>  