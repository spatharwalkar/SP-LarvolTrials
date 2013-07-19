<?php
require_once 'include.util.php';
$urllist=array();
$NCTcategorynames = array();

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
ignore_user_abort(true);

pr('Getting list of disease categories from clinicaltrials.gov ......'.str_repeat(" ",1025));
get_disease_categories(); //get list of disease categories 

pr('Associating disease categories with diseases......'.str_repeat(" ",1025));
update_disease_categories(); //sync them with LT's disease categories

//require_once 'link_disease_categories.php';  // associate disease categories with diseases

echo '<b>All Done.</b>';

/*********************get list of all disease categories and their urls ****************/
function get_disease_categories()
{
	$url = 'http://clinicaltrials.gov/ct2/search/browse?brwse=cond_cat';
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

	//loop through the div and get disease names, and the url of their list of studies
	$uls = $divdata->getElementsByTagName('ul');
	$ok=false;
	foreach ($uls as $ul) 
	{
		foreach ($ul->attributes as $attr) 
		{
			if ($attr->name == 'id' && $attr->value == 'conditions') 
			{
				$ok = true;
				$uldata=$ul;
				break 2;
			}
		}
	}
	
	$d_list = $uldata->getElementsByTagName('a');
	global $NCTcategorynames, $urllist;
	$urllist=array();
	foreach ($d_list as $value) 
	{
		$NCTcategorynames[]=$value->nodeValue;  
		$urllist[]=$value->getAttribute( 'href' );  
	}
	
}

function update_disease_categories()
{
	global $NCTcategorynames, $urllist;

	//get list of disease categories from the database
    $query = 'SELECT id, name, mesh_name from entities where class="Disease_Category"';
   if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		echo $log;
		return false;
	}
	$LTdisease_categories=array();//list of disease categories in LT
	$cnt=0;
	while ($row = mysql_fetch_assoc($res))
	{
		$LTdisease_categories[$cnt]['id']=$row['id'];
		$LTdisease_categories[$cnt]['name']=$row['name'];
		$LTdisease_categories[$cnt]['mesh_name']=$row['mesh_name'];
		$cnt++;
	}
	
	$counter=0;
	$counter1=0;
	foreach($NCTcategorynames as $key=>$NCTcategoryname)
	{
		$dkey=recursive_array_search($NCTcategoryname, $LTdisease_categories);
		if($dkey!==false) // category already exists
		{
			pr('<b>Syncing existing category:'.$NCTcategoryname.'</b>'.str_repeat(" ",1025));
			$categoryid=$LTdisease_categories[$dkey][id];
			$NCTdiseasenames=fetch_Diseases($urllist[$key]) ;
			if($NCTcategoryname<>$LTdisease_categories[$dkey]['mesh_name'])  //update mesh name if different
			{
				$query='UPDATE entities SET mesh_name="'.$NCTcategoryname.'" where id='.$LTdisease_categories[$dkey]['id'].' limit 1';
				mysql_query($query);
				$counter++;
			}
			associate_Disease_Category($NCTdiseasenames,$categoryid);
		}
		else // new disease category, append it.
		{
			pr('<b>Syncing new category:'.$NCTcategoryname.'</b>'.str_repeat(" ",1025));
			$query='INSERT INTO entities SET class="Disease_Category", name="'.$NCTcategoryname.'", mesh_name="'.$NCTcategoryname.'", display_name="'.$NCTcategoryname.'" ';
			mysql_query($query);
			$categoryid= mysql_insert_id();
			$NCTdiseasenames=fetch_Diseases($urllist[$key]) ;
			associate_Disease_Category($NCTdiseasenames,$categoryid);

			$counter1++;

		}
	}
			
//			associate_Disease_Category($NCTdiseasenames,$categoryid);

}

function recursive_array_search($needle,$haystack) 
{
    foreach($haystack as $key=>$value) 
	{
        $current_key=$key;
        if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) 
		{
            return $current_key;
        }
    }
    return false;
}

function associate_Disease_Category($NCTdiseasenames,$categoryid)
{
	if(empty($NCTdiseasenames) or !is_array($NCTdiseasenames) or empty($categoryid))
	{
		return false;
	}
//	mysql_query("START TRANSACTION");
	$res=mysql_query("delete from entity_relations where parent=".$categoryid." ;") or die("Error deleting records from entity_relations ". mysql_errror());
	foreach ($NCTdiseasenames as $NCTdiseasename)
	{
		$disease_id=get_disease_id($NCTdiseasename);
		if(empty($disease_id))
		{
//			pr('empty disease id for '.$NCTdiseasename);
			continue;
		}
		$query='INSERT INTO `entity_relations` (`parent`, `child`) 
				VALUES ("' . $categoryid . '", "' . $disease_id .'") ';
		$res1 	= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			pr($log.str_repeat(" ",1025));
			return false;
		}
		
	}


}

function fetch_Diseases($cond) 
{
	if(empty($cond)) return false;

	$url='http://clinicaltrials.gov'.$cond.'&brwse-force=true';	
	pr($url);
//		if($page>2) break;
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
	
	if ($divdata === NULL) 
	{
		echo('Nothing to import'. "\n<br />");
	}

	//loop through the div and get disease names 
	$uls = $divdata->getElementsByTagName('ul');
	$ok=false;
	foreach ($uls as $ul) 
	{
		foreach ($ul->attributes as $attr) 
		{
			if ($attr->name == 'id' && $attr->value == 'conditions') 
			{
				$ok = true;
				$uldata=$ul;
				break 2;
			}
		}
	}
	
	$d_list = $uldata->getElementsByTagName('a');
	$NCTdiseasenames=array();
	foreach ($d_list as $value) 
	{
		$NCTdiseasenames[]=$value->nodeValue;  
	}
//	pr($NCTdiseasenames);
    return $NCTdiseasenames;
}

function get_disease_id($NCTdiseasename)
{
//	$NCTdiseasename=trim($NCTdiseasename);
	$query = "	SELECT id
				FROM entities 
				WHERE class= 'Disease' and  `mesh_name` = \"".$NCTdiseasename."\" 
				limit 1
			  ";
	$res1 	= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		pr($log.str_repeat(" ",1025));
		return false;
	}
	$did=mysql_fetch_assoc($res1);
	return $did['id'];
}

?>