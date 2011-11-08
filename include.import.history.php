<?php

require_once('db.php');
require_once('include.import.php');
require_once('nct_common.php');
require_once('preindex_trial.php');
ini_set('max_execution_time', '36000'); //10 hours
ob_implicit_flush(true);
ob_end_flush();
$last_id = 0;
$id_field = 0;

function scrape_history($id)
// Get Days
{
	if (!isset($id) or empty($id)  ) 
	{
		die('No ID passed');
	} 

	$id = padnct($id);

	//Get and import the XML for all these new records
	echo('Fetching record content...' . "\n<br />");
	$progress_count = 0;

	echo('<br/> Getting Archive Page for ' . $id . '... - ');
	$url = "http://clinicaltrials.gov/archive/" . $id;
	$doc = new DOMDocument();
	echo('Parsing Archive Page for ' . $id . '... - ');

	echo $url;
	//*****
	for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) {
		$done = $doc->loadHTMLFile($url);
	}

	// Get Rows with "Versions in class"
	$ths = $doc->getElementsByTagName('th');
	unset($doc);
	$loopcounter = 0;

	// flags
	// This case we want this set everytime cause we are building from scratch
	$agentorange = true;
	// $willis=false;    

	$unid = unpadnct($id);
	//$cat = array_search($unid, $existing);
	//if ($cat == null) {
	// New Record
	// In case there was not a standard base set a flag so
	// we know coming in next time we can process the differences
	//Do Nothing.. Will fall into Agent Orange!
	//    $agentorange = true;
	//} 
	//else
	//{
	// Mean existing Record Then clear out all values in the database that pertain
	// To this record per Anthony.
	//get the studycats for the reported IDs that exist in the database
	$query = 'SELECT val_int AS "nct_id",studycat FROM data_values '
			. 'WHERE field=1 AND val_int =' . $unid;

	$res = mysql_query($query);
	if ($res === false)
		return softDie('Bad SQL query getting existing nct_ids');

	while ($row = mysql_fetch_assoc($res)) {
		$cat = $row['studycat'];
	}

	if (isset($cat)) {

		echo "<br>Deleting Existing Data<br>";
		$query = 'delete from data_values where field !=1 and studycat=' . $cat;
		$res = mysql_query($query) ;
		
		if(!$res and ( mysql_errno() <> 1213 and mysql_errno() <> 1205 )  ) // error
			die('Unable to delete existing values' . mysql_error() . '('. mysql_errno() .')');
		
		//TKV  
		//will retry in case of lock wait time timeout  
		if(!$res and ( mysql_errno() == 1213 or mysql_errno() == 1205 )) 
		{
			for ( $retries = 300; $dead_lock and $retries > 0; $retries -- )
			{
				$pid = getmypid();
				$query1 = 'SELECT update_id,process_id FROM update_status_fullhistory where status="2" order by update_id desc limit 1' ;
				$res = mysql_query($query1) or die('Bad SQL query finding ready updates. Query:' . $query1  );
				$res = mysql_fetch_array($res) ;
				if ( isset($res['update_id']) and $res['process_id'] == $pid  )
				{
					$msg='Deadlock found.  Re-trying to get lock...';
					$query1 = 'UPDATE update_status_fullhistory SET er_message=' . $msg . ' WHERE update_id="' . $res['update_id'] .'"';
					$res = mysql_query($query1) or die('Bad SQL query finding ready updates. Query:' . $query1  );
				}
				
				
				sleep(120); 
				$res = mysql_query($query) ;
				if(!$res)
				{
					if (mysql_errno() == 1213 or mysql_errno() == 1205) 
					{ 
						$dead_lock = true;
						$retries --;
					}
					else 
					{
						$dead_lock = false;
						die('Unable to delete existing values' . mysql_error() . '('. mysql_errno() .')');
					}
					
				}
				else 
				{
					$dead_lock = false;
				}
			}
		
		}
		elseif(!$res and mysql_errno() <> 1213 and mysql_errno() <> 1205) 
		{
			die('Unable to delete existing values' . mysql_error() . '('. mysql_errno() .')');
		}
		
		//*************
	}

	$count = 0;
	$studies = 0;
	foreach ($ths as $th) {
		foreach ($th->attributes as $attr) {
			if ($attr->name == 'scope' && $attr->value == 'row') {
				if ($count == 0) {
					// skip first line just initial version
					$initial_date = $th->nodeValue;
					echo "<br/>";
				} else {
					if ($agentorange == true) {
						//Process Left
						// This means there is new stuff for us to review and store.
						echo "<br />Processing Left Column Value for initial <br />";
						echo "Using Date: " .$initial_date;
						ProcessChanges($id, $th->nodeValue, "sdiff-a", $initial_date);

						// Set CatID for later lookup which is the studycat number.
						$query = 'SELECT val_int AS "nct_id",studycat FROM data_values '
								. 'WHERE field=' . $id_field
								. ' AND val_int=' . unpadnct($id);
						$res = mysql_query($query);
						$row = mysql_fetch_assoc($res);
						$cat = $row['studycat'];

						//Retire Agent Orange
						$agentorange = false;
					}

					// We struck gold "new records" process each row following
					echo "New Date Record - Next Row <br />";
					ProcessDiffChanges($id, $th->nodeValue);
					$lastdate=$th->nodeValue;
					//ProcessChanges($id, $th->nodeValue, "sdiff-b");
				}
			
				$count = $count + 1;
			}
			
			unset($attr);
		}
		$studies = $studies + 1;
		unset($th);
		
	}
	unset($ths);

	echo "<hr>";
	$studies = $studies - 2;
	echo($studies . " different studies (including initial) updated for case id: " . $id);
	// See if Agent Orange is Still causing trouble if so ProcessNew
	if ($agentorange == true) {
		ProcessNew($id);
		$agentorange = false;
	}


	echo('<br>End Parsing Archive Page for ' . $id);

	echo('<hr><br>Parse Current Study for all information and non essentials<br>');

	ProcessNonEssentials($id, $lastdate);
	$studyCat=getStudyCat($unid);
	echo('<br>Pre-indexing product related trials for id: ' . $id . '..........<br><br>');
	tindex($studyCat,'products');
	echo('<br>Pre-indexing area related trials for id: ' . $id . '.........<br><br>');
	tindex($studyCat,'areas');
		
		
	echo('<br>Finished Parsing Current Study with this ID.<br />');

	$query = 'UPDATE update_status SET end_time="' . date("Y-m-d H:i:s", strtotime('now')) . '" WHERE update_id="' . $update_id . '"';
	$res = mysql_query($query) or die('Unable to update running' . mysql_error());

	echo('<br>Completely Finished with this ID.<br />');
}
?>  