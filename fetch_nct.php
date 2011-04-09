<?php
require_once('db.php');
require_once('include.import.php');

if(isset($_GET['maxrun'])) ini_set('max_execution_time','36000');	//10 hours
$days = 0;
if(isset($_GET['days']))
{
	$days = (int)$_GET['days'];
}else{
	die('Need to set $_GET[\'days\']');
}

//Find out the ID of the field for nct_id, and the ID of the "NCT" category.
$query = 'SELECT data_fields.id AS "nct_id",data_categories.id AS "nct_cat" FROM '
		. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="nct_id" AND data_categories.name="NCT" LIMIT 1';
$res = mysql_query($query);
if($res === false) return softDie('Bad SQL query getting field ID of nct_id');
$res = mysql_fetch_assoc($res);
if($res === false) return softDie('NCT schema not found!');
$id_field = $res['nct_id'];
$nct_cat = $res['nct_cat'];

echo("\n<br />" . 'Begin updating. Going back ' . $days . ' days.' . "\n<br />" . "\n<br />");
echo('Searching for new records...' . "\n<br />");
$ids = getIDs('new');
if(count($ids) == 0)
{
	echo('There are none!' . "\n<br />");
}else{
	//Now that we have all new NCTIDs from all pages, throw out the ones we already have in the database
	echo('Checking which records are not already stored here...' . "\n<br />");
	$reportednew = count($ids);
	$query = 'SELECT val_int AS "nct_id" FROM data_values WHERE field=' . $id_field
			. ' AND val_int IN(' . implode(',',array_map('unpadnct',array_keys($ids))) . ')';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query checking DB for existing values: ' . $query . "\n<br />" . mysql_error());
	while($row = mysql_fetch_assoc($res))
	{
		//This is the reason we stored the IDs as keys instead of values -- we don't need an array search to unset them
		unset($ids[padnct($row['nct_id'])]);
	}
	$query = 'UPDATE update_status SET add_items_total="' . count($ids)	. '",add_items_start_time="' . date("Y-m-d H:i:s",strtotime('now')).'" WHERE update_id="'.$_GET['update_id'].'"';
	$res = mysql_query($query) or die('Unable to update running'.mysql_error());
	echo(count($ids) . ' new records out of ' . $reportednew . '.' . "\n<br />");
	//Get and import the XML for all these new records
	echo('Fetching record content...' . "\n<br />");
	$progress_count=0;
	foreach($ids as $id => $one)
	{
		echo('Getting XML for ' . $id . '... - ');
		$xml = file_get_contents('http://www.clinicaltrials.gov/show/' . $id . '?displayxml=true');
		echo('Parsing XML... - ');
		$xml = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOWARNING|LIBXML_NOERROR);
		if($xml === false)
		{
			echo('Parsing failed for this record.' . "\n<br />");
		}else{
			echo('Importing... - ');
			if(addRecord($xml,'nct') === false)
			{
				echo('Import failed for this record.' . "\n<br />");
			}else{
				echo('Record imported.' . "\n<br />");
			}
		}
		$progress_count++;
		$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now'))	. '",add_items_progress="' . $progress_count.'" WHERE update_id="'.$_GET['update_id'].'"';
		$res = mysql_query($query) or die('Unable to update running');
	}
	
	$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now'))	. '",add_items_complete_time ="' . date("Y-m-d H:i:s",strtotime('now')).'" WHERE update_id="'.$_GET['update_id'].'"';
	$res = mysql_query($query) or die('Unable to update running');
	
}
echo('Done with new items.' . "\n<br />" . "\n<br />");

//Now get updates
echo('Searching for updated records...' . "\n<br />");
$ids = getIDs('update');
if(count($ids) == 0)
{
	echo('There are none!' . "\n<br />");
}else{
	//Now that we have all updated NCTIDs from all pages, throw out the ones we already have the latest version of
	echo('Checking which records are not already updated on our side...' . "\n<br />");
	$reportednew = count($ids);
	
	//Find out the ID of the field for lastchanged_date
	$query = 'SELECT data_fields.id AS "last_id" '
		. 'FROM data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="lastchanged_date" AND data_categories.name="NCT" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting field ID of lastchanged_date');
	$res = mysql_fetch_assoc($res);
	if($res === false) return softDie('NCT schema not found!');
	$last_id = $res['last_id'];

	//get the studycats for the reported IDs that exist in the database
	$query = 'SELECT val_int AS "nct_id",studycat FROM data_values '
			. 'WHERE field=' . $id_field
			. ' AND val_int IN(' . implode(',',array_map('unpadnct',array_keys($ids))) . ')';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting existing nct_ids');
	$existing = array();
	while($row = mysql_fetch_assoc($res))
	{
		$existing[$row['studycat']] = $row['nct_id'];
	}
	
	//get the lastchanged dates for the studycats
	$query = 'SELECT UNIX_TIMESTAMP(val_date) AS "lastchanged_date",studycat FROM data_values WHERE field=' . $last_id
			. ' AND studycat IN(' . implode(',', array_keys($existing)) . ')';
	$res = mysql_query($query);
	if($res === false) return softDie('Bad SQL query getting lastchanged dates for existing nct_ids');
	while($row = mysql_fetch_assoc($res))
	{//todo: make sure this works
		//This is the reason we stored the IDs as keys instead of values -- we don't need an array search to unset them
		if($row['lastchanged_date'] >= $ids[padnct($existing[$row['studycat']])])	unset($ids[padnct($existing[$row['studycat']])]);
	}

	echo(count($ids) . ' new updates out of ' . $reportednew . '.' . "\n<br />");
	
	$query = 'UPDATE update_status SET update_items_total="' . count($ids)	. '",update_items_start_time="' . date("Y-m-d H:i:s",strtotime('now')).'" WHERE update_id="'.$_GET['update_id'].'"';
	$res = mysql_query($query) or die('Unable to update running'.mysql_error());
	
	//Get and import the XML for all these new records
	echo('Fetching record content...' . "\n<br />");
	$progress_count=0;
	foreach($ids as $id => $one)
	{
		echo('Getting XML for ' . $id . '... - ');
		$xml = file_get_contents('http://www.clinicaltrials.gov/show/' . $id . '?displayxml=true');
		echo('Parsing XML... - ');
		$xml = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOWARNING|LIBXML_NOERROR);
		if($xml === false)
		{
			echo('Parsing failed for this record.' . "\n<br />");
		}else{
			echo('Importing... - ');
			if(addrecord($xml,'nct') === false)
			{
				echo('Import failed for this record.' . "\n<br />");
			}else{
				echo('Record imported.' . "\n<br />");
			}
		}
		$progress_count++;
		$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now'))	. '",update_items_progress="' . $progress_count.'" WHERE update_id="'.$_GET['update_id'].'"';
		$res = mysql_query($query) or die('Unable to update running');
	}
	
	$query = 'UPDATE update_status SET updated_time="' . date("Y-m-d H:i:s",strtotime('now'))	. '",update_items_complete_time ="' . date("Y-m-d H:i:s",strtotime('now')).'" WHERE update_id="'.$_GET['update_id'].'"';
	$res = mysql_query($query) or die('Unable to update running');
	
}
echo('Done with updated items.' . "\n<br />" . "\n<br />");
echo('Done with new and updated items.');

//returned array maps the IDs to lastchanged dates
function getIDs($type)
{
	global $days;
	$fields = '';
	$dstr = '';
	switch($type)
	{
		case 'new':
		$fields = 'k';
		$dstr = 'rcv_d';
		break;
		
		case 'update':
		$fields = 'kp';
		$dstr = 'lup_d';
		break;
		
		default:
		return false;
	}

	$ids = array();
	for($page = 1; true; ++$page)
	{
		$fake = mysql_query('SELECT larvol_id FROM clinical_study LIMIT 1'); //keep alive
		@mysql_fetch_array($fake);
		//load search page and see if it has results, or if we've reached the end of results for the search
		$url = 'http://clinicaltrials.gov/ct2/results?flds=' . $fields . '&' . $dstr . '=' . $days . '&pg=' . $page;
		$doc = new DOMDocument();
		for($done=false,$tries=0; $done==false&&$tries<5; $tries++)
		{
			echo('.');
			@$done = $doc->loadHTMLFile($url);
		}
		$tables = $doc->getElementsByTagName('table');
		$datatable = NULL;
		foreach($tables as $table)
		{
			$right = false;
			foreach($table->attributes as $attr)
			{
				if($attr->name == 'class' && $attr->value == 'data_table')
				{
					$right = true;
					break;
				}
			}
			if($right == true)
			{
				$datatable = $table;
				break;
			}
		}
		if($datatable == NULL)
		{
			echo('Last page reached.' . "\n<br />");
			break;
		}
		unset($tables);
		//Now that we found the table, go through its TDs to find the ones with NCTIDs
		$tds = $datatable->getElementsByTagName('td');
		$pageids = array();
		$upd = NULL; //only for update mode
		foreach($tds as $td)
		{
			$hasid = false;
			foreach($td->attributes as $attr)
			{
				if($attr->name == 'style' && $attr->value == 'padding-left:1em;')
				{
					$hasid = true;
					break;
				}
			}
			if($hasid)
			{
				if($type=='new') //In new mode, just record IDs
				{
					$pageids[mysql_real_escape_string($td->nodeValue)] = 1;
				}else{	//In update mode, the results alternate between IDs and update times
					if($upd === NULL)
					{
						$upd = mysql_real_escape_string($td->nodeValue);
					}else{
						$pageids[$upd] = strtotime($td->nodeValue);
						$upd = NULL;
					}
				}
			}
		}
		echo('Page ' . $page . ': ' . implode(', ', array_keys($pageids)) . "\n<br />");
		$ids = array_merge($ids,$pageids);
	}
	return $ids;
}
?>