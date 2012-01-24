<?php
require_once('db.php');
require_once('preindex_trial_new.php');
ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();

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