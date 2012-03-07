<?php
require_once('db.php');
require_once('preindex_trial.php');
ini_set('max_execution_time', '36000'); //10 hours
ignore_user_abort(true);

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

if (isset($_GET['id'])) 
{
    $productID = $_GET['id'];
} else 
{
    die('No ID passed');
}

tindex(NULL,'areas',NULL,NULL,NULL,$productID);
echo '<br><br>All done.<br>';

?>  