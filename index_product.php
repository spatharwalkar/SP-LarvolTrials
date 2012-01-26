<?php
require_once('db.php');
require_once('preindex_trial_new.php');
ini_set('max_execution_time', '36000'); //10 hours


if (isset($_GET['id'])) 
{
    $productID = $_GET['id'];
} else 
{
    die('No ID passed');
}

tindex(NULL,'products',NULL,NULL,NULL,$productID);
echo '<br><br>All done.<br>';

?>  