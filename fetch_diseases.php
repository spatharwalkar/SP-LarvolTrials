<?php
require_once 'include.util.php';
$urllist=array();
$NCTdiseasenames = array();

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
ignore_user_abort(true);

pr('Getting list of diseases from clinicaltrials.gov ......'.str_repeat(" ",1025));
get_diseases(); //get list of diseases 

pr('Updating the entity mesh index ......'.str_repeat(" ",1025));
update_diseases(); //sync them with LT's diseases, and also update the mesh index

require_once 'link_diseases.php';  // associate diseases with products

echo '<b>All Done.</b>';

/*********************get list of all diseases and their urls ****************/
function get_diseases()
{
	$url = 'http://clinicaltrials.gov/ct2/search/browse?brwse=cond_alpha_all&brwse-force=true';
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
	global $NCTdiseasenames, $urllist;
	foreach ($d_list as $value) 
	{
		array_push($NCTdiseasenames, $value->nodeValue);  
	}
	
 
	foreach ($NCTdiseasenames as $value) 
	{
		$value='%22'.$value.'%22';
		$value=str_replace(",","%2C",$value);
		array_push($urllist, '&cond='.str_replace(" ","+",$value).'');  
	}
}

function update_diseases()
{
	global $NCTdiseasenames, $urllist;

	//get list of diseases from the database
    $query = 'SELECT id, name, mesh_name from entities where class="Disease"';
   if(!$res = mysql_query($query))
	{
		$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		echo $log;
		return false;
	}
	$LTdiseases=array();//list of diseases in LT
//	pr($NCTdiseasenames);
//	exit;
	$cnt=0;
	while ($row = mysql_fetch_assoc($res))
	{
		$LTdiseases[$cnt]['id']=$row['id'];
		$LTdiseases[$cnt]['name']=$row['name'];
		$LTdiseases[$cnt]['mesh_name']=$row['mesh_name'];
		$cnt++;
	}
	
	$counter=0;
	$counter1=0;
	foreach($NCTdiseasenames as $key=>$NCTdiseasename)
	{
		$dkey=recursive_array_search($NCTdiseasename, $LTdiseases);
		if($dkey!==false) // disease already exists
		{
			/*
			pr($key);
			pr($NCTdiseasenames[$key]);
			pr($urllist[$key]);
			pr($dkey);
			*/
			$diseaseid=$LTdiseases[$dkey][id];
//			if($diseaseid<15789) continue;
			pr('<b>Indexing Disease:'.$NCTdiseasename.'</b>'.str_repeat(" ",1025));
			//exit;
			//pr($urllist[$key]);
			$nctids=fetchNCTIDs($urllist[$key]) ;
			
			update_mesh_preindex($nctids,$diseaseid);
			if($NCTdiseasename<>$LTdiseases[$dkey]['mesh_name'])  //update mesh name if different
			{
				$query='UPDATE entities SET mesh_name="'.$NCTdiseasename.'" where id='.$LTdiseases[$dkey]['id'].' limit 1';
				mysql_query($query);
				//pr($query);
				$counter++;
				if($counter>3)
				{
//				pr($LTdiseases);
				}
			}
		}
		else // new disease, append it.
		{
			pr('<b>Indexing Disease:'.$NCTdiseasename.'</b>'.str_repeat(" ",1025));
			$query='INSERT INTO entities SET class="Disease", name="'.$NCTdiseasename.'", mesh_name="'.$NCTdiseasename.'", display_name="'.$NCTdiseasename.'" ';
			mysql_query($query);
			$diseaseid= mysql_insert_id();
			$nctids=fetchNCTIDs($urllist[$key]) ;
			$counter1++;
			if($counter1>3)
			{
			//	exit;
			}
			update_mesh_preindex($nctids,$diseaseid);
		}
	}
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

function update_mesh_preindex($nctids,$diseaseid)
{
	if(empty($nctids) or !is_array($nctids) or empty($diseaseid))
	{
		//pr('RETURNING FALSE');
		return false;
	}
//	mysql_query("START TRANSACTION");
	$res=mysql_query("delete from entity_mesh_trials where entity=".$diseaseid." ;") or die("Error deleting records from entity_mesh_trials ". mysql_errror());
	foreach ($nctids as $nctid=>$ts)
	{
		$larvol_id=get_larvolID($nctid);
		if(empty($larvol_id))
		{
//			pr('NCTID '.$nctid.' does not exist in database !');
			continue;
		}
		$query='INSERT INTO `entity_mesh_trials` (`entity`, `trial`) 
				VALUES ("' . $diseaseid . '", "' . $larvol_id .'") ';
		$res = mysql_query($query);
		if($res === false)
		{
			$log = 'Bad SQL query adding records to entity_mesh_trials . Query : ' . $query . '<br> MySql Error:'.mysql_error();
			//mysql_query('ROLLBACK');
		 	echo $log;
			return false;
		}
		//$res=mysql_query("select * from entity_mesh_trials where entity=".$diseaseid." and trial=".$nctid." limit 1;") or die("Error in geting data from entity_mesh_trials ". mysql_errror());
	}
//	mysql_query("COMMIT;");
}

function fetchNCTIDs($cond) 
{
	if(empty($cond)) return false;
	$NCTids=array();
    for ($page = 1; true; ++$page) 
	{
		$url='http://clinicaltrials.gov/ct2/results?flds=kp&lup_d=3000&pg='.$page.$cond;	
		pr('Fetching page <b>'.$page.'</b> ......'.str_repeat(" ",1025));
        $doc = new DOMDocument();
        for ($done = false, $tries = 0; $done == false && $tries < 5; $tries++) 
		{
            if ($tries>0) echo('.');
            @$done = $doc->loadHTMLFile($url);
        }
        $tables = $doc->getElementsByTagName('table');
        $datatable = NULL;
        foreach ($tables as $table) 
		{
            $right = false;
            foreach ($table->attributes as $attr) 
			{
				if ($attr->name == 'class' && $attr->value == 'data_table margin-top')
				{
					$correct_datatable = $table;
				}
				if ($attr->name == 'class' && substr($attr->value,0,15) == 'data_table body') 
				{
                    $right = true;
                    break;
                }
            }
            if ($right == true) 
			{
                $datatable = $correct_datatable;
                break;
            }
        }
        if ($datatable == NULL) 
		{
//            echo('Done.' . "\n<br />");
            break;
        }
        unset($tables);
        $tds = $datatable->getElementsByTagName('td');
        $pageids = array();
        $upd = NULL; 
        foreach ($tds as $td) 
		{
            $hasid = false;
            foreach ($td->attributes as $attr) 
			{
                if ($attr->name == 'style' && $attr->value == 'padding-left:1em;') 
				{
                    $hasid = true;
                    break;
                }
            }
            if ($hasid) 
			{
                if ($type == 'new') 
				{ 
                    $pageids[mysql_real_escape_string($td->nodeValue)] = 1;
                } 
				else 
				{ 
                    if ($upd === NULL) 
					{
                        $upd = mysql_real_escape_string($td->nodeValue);
                    } 
					else 
					{
                        $pageids[$upd] = strtotime($td->nodeValue);
                        $upd = NULL;
                    }
                }
            }
        }
//        echo('Completed page ' . $page . '' . "\n<br />");
		//pr($pageids);
        $NCTids = array_merge($NCTids, $pageids);
    }
	//pr($NCTids);
    return $NCTids;
}

function get_larvolID($nctid)
{
	$nctid=trim($nctid);
	$query = "	SELECT `larvol_id`
				FROM `data_trials` 
				WHERE `source_id` like '%".$nctid."%' 
				limit 1
			  ";
	$res1 	= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query. Query=' . $query;
		pr($log.str_repeat(" ",1025));
		return false;
	}
	$lid=mysql_fetch_assoc($res1);
	return $lid['larvol_id'];
}

?>