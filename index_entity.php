<?php
require_once('db.php');
require_once('preindex_trial.php');
//require_once('preindex_trial_old.php');  // TO BE REMOVED LATER
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
$query = 'SELECT `class` FROM entities WHERE id="'.$productID.'" limit 1';
$res = mysql_query($query) or die('Bad SQL Query getting entity class');
$row = mysql_fetch_assoc($res); 
if($row['class']=='Product')	$entity='products';
else	$entity='areas';

//tindex_old(NULL,$entity,NULL,NULL,NULL,$productID);  // TO BE REMOVED
tindex(NULL,$entity,NULL,NULL,NULL,$productID);
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