<?php
require_once 'include.util.php';

$NCTcontent = '';

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
ignore_user_abort(true);

pr('Getting list of industry institutions from clinicaltrials.gov ......');
get_industries(); //get list of industries 

create_industries(); //save in text file

echo '<b>All Done.</b>';

/*********************get list of all industries ****************/
function get_industries()
{
	$url = 'http://clinicaltrials.gov/ct2/search/browse?brwse=spns_cat_INDUSTRY&brwse-force=true';
	$doc = new DOMDocument();
	for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) 
	{
		if ($tries>0) echo('.');
		@$done = $doc->loadHTMLFile($url);
	}
	
	$divs = $doc->getElementsByTagName('div');
	$divdata = NULL;
	
	foreach ($divs as $div) 
	{
		$ok = false;
		foreach ($div->attributes as $attr) 
		{
			if ($attr->name == 'id' && $attr->value == 'body-copy-browse')
			{
				$data = $div;
				$ok = true;
				break;
			}
		}
		if ($ok == true) 
		{
			$divdata = $data;
			break;
		}
	}
	
	if ($divdata == NULL) 
	{
		echo('Nothing to import'. "\n<br />");
		exit;
	}

	$data = $divdata->nodeValue;

	//replace story and stories from string and prepare an arry with industry names 
	$industries_str = str_replace('See Sponsor/Collaborators by Category > Industry', '', $data);
	$industries_replaced = preg_replace('/[0-9]{1,4}?\sstudy|[0-9]{1,4}?\sstudies/', '{***}', $industries_str);
	$industries = explode('{***}', $industries_replaced);
	
	global $NCTcontent;
	foreach ($industries as $industry) 
	{
		$industry = iconv('UTF-8', 'ISO-8859-1', $industry);
		$industry = trim($industry);
		$NCTcontent .= $industry.PHP_EOL;
	}
	
}

//create text file with this array
function create_industries()
{
	global $NCTcontent;

	$handle = fopen('derived/institution_type/industry.txt', 'w+'); // binary update mode
	fwrite($handle, $NCTcontent);
	fclose($handle);

}


?>