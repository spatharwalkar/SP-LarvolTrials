<?php
require_once('db.php');
require_once('include.import_new.php');
require_once('eudract_common.php');
require_once('include.import.eudract.history.php');

ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();


if (isset($_GET['id'])) 
{
    $id = $_GET['id'];
} else 
{
    die('No ID passed');
}

$ids = getEudraIDs($id);
if (count($ids) == 0) 
{
    echo('There are none!' . "\n<br />");
	return false;
} 


$count = count($ids);

    echo("<br /><br /> New Updates : " . $count . "\n<br />");
	
    //Import the XML for all these new records
    echo('Fetching record content...' . "\n<br />");
    $progress_count = 0;
    
    
    foreach ($ids as $key => $value) 
	{
		scrape_history($key , $value);
	}


?>  