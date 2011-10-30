<?php

require_once('db.php');
require_once('include.import.php');
require_once('nct_common.php');
require_once('include.import.history.php');

// DW
ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();
$last_id = 0;
$id_field = 0;

// Get Days

if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die('No ID passed');
}

scrape_history($id);

?>  