<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('searchhandler.php');
ini_set('memory_limit','-1');
ini_set('error_reporting', E_ALL ^E_NOTICE );
ini_set('max_execution_time', '7200'); //2hrs
/*	

function tindex() - to preindex a combination of one trial+one product, or  one trial+one area.  
parameters : 
		1.	NCTID (if a single trial is to be indexed)
		2.	either "products" or "areas" as appropriate.
		3.	Array of product ids or array of area ids as appropriate.
		4.	update id - supplied by viewstatus.php when a task is resumed / requed etc.
		5.	current product id - supplied by viewstatus.php when a task is resumed / requed etc.
		6.	product id / area id when a single product or area is to beindexed.

*/
function tindex($sourceid,$cat,$productz=NULL,$up_id=NULL,$cid=NULL,$productID=NULL)
{
	if($cat=='products') 
	{
		$table='entity_trials'; 
		$field='entity';
	}
	else 
	{
		$table='entity_trials'; 
		$field='entity';
	}

	global $logger,$now,$db;
	$cmpny=$cat=='products' ? ', `company`' : '';
	$scraper_run=( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) );
	$DTnow = date('Y-m-d H:i:s',$now);
	if(!isset($i)) $i=0;
	if(is_null($productz))	// array of product ids
	{
		$productz=array();
		if(is_null($productID))
		{
			
			$query = 'SELECT `id`,`name`,`searchdata`' . $cmpny . ' from '. 'entities' .' where searchdata IS NOT NULL and  `searchdata` <>"" ';
			$ttype='ENTITY';
		}
		else 
		{
			$query = 'SELECT `id`,`name`,`searchdata`' . $cmpny . ' from '. 'entities' .' where `searchdata` IS NOT NULL and  `searchdata` <>"" and `id`="' . $productID .'"' ;
			$ttype='ENTITY';
		}
		
		if(!$resu = mysql_query($query))
		{
			$log='Bad SQL query getting  details from '. 'entities' .' table.<br>Query=' . $query;
			$logger->fatal($log);
			echo $log;
			return false;
		}
	//	$productz = mysql_fetch_assoc($resu);
		while($productz[]=mysql_fetch_array($resu));
		
	}
	// remove blanks
	foreach ($productz as $key => $product)
		if( is_null($product) or empty($product) ) unset($productz[$key]);
	
	
	if (!is_null($cid) and !empty($cid) and $cid>0)
	{
		$startid=$cid; 
	}
	else 
	{
		$startid=0;
	}
	$total = count($productz);
	$current = 0;
	$progress = 1;	
	if(count($productz)>0)
	{
		foreach ($productz as $key=>$value)
		{
			$company_name=$cat == 'products' ? explode( ',', $value['company'] ) : '';
			if(!isset($value['id']) or empty($value['id'])) break;
			$cid=$value['id'];
			$searchdata = $value['searchdata'];
			$pid=$value['id'];
			$prid=getmypid();
			if(!is_null($productz) and $pid>=$startid)	
			{
				$cid=$value['id'];
				// get the actual mysql query  
				try	
					{
						$query=buildQuery($searchdata);
					}
				catch(Exception $e)
					{
						echo '<br>Bad Regex in product id ' . $pid .', skipping the product.  Mysql error:' . $e->getMessage().'<br>';
						$log='Bad Regex in product id ' . $pid .'  Error:' . $e->getMessage();
						$logger->error($log);
						continue;
					}
				
					
				if($query=='Invalid Json') // if searchdata contains invalid JSON
				{
					echo '<br> Invalid JSON in table <b>'. 'entities' .'</b> and id=<b>'.$cid.'</b> : <br>';
					echo $searchdata;
					echo '<br>';
					--$total;
					continue;
				}
				
				/* remove overridden trials in case we are indexing a single trial */

				$mystring=$query;
				if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) )
				{
					$findme   = 'UNION';
					$pos = stripos($mystring, $findme);
					if ($pos === false) 
					{
						echo '' ;
					} 
					else 
					{
						$mystring = substr($mystring,0,$pos);
					}
				}
				/****/
				
				
				$findme   = 'where';
				$pos = stripos($mystring, $findme);

				if ($pos === false) 
				{
					
					if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) )
					{
						if($sourceid<>$used_sourceid)
						{
							$new_lid=get_larvolid($sourceid);
							if($new_lid === false or empty($new_lid) )
								continue;
						}
						$used_sourceid=$sourceid;
						if($new_lid !== false and !empty($new_lid) )
							$limit_query=' where( larvol_id = ' .$new_lid .' ) ';
						else
							$limit_query='';
						$query = $mystring . $limit_query ;
					}
					
				} 
				else 
				{
					/********** add source id to condition for quick indexing   *************/
					if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) )
					{
//						$ln=strlen('  `source_id` = "'. $sourceid . '"  and  ');
						if($sourceid<>$used_sourceid)
						{
							$new_lid=get_larvolid($sourceid);
							if($new_lid === false or empty($new_lid) )
								continue;
						}
						$used_sourceid=$sourceid;
						if($new_lid !== false and !empty($new_lid) )
							$limit_query='( larvol_id = ' .$new_lid .' ) and ';
						else
							$limit_query='';
						$query = substr($mystring,0,$pos+6). $limit_query . ' ( ' . substr($mystring,$pos+6) . ' ) ';
					}
					else
					{

						$query = 'SELECT LI_id from '. 'entities' .' where `id`="' . $productID .'" limit 1' ;
						$resu = mysql_query($query);
						$row=mysql_fetch_array($resu);
						
						
						/***********/
							$query = 'SELECT distinct trial from entity_trials where entity = "' . $productID .'" ' ;
							$res2 = mysql_query($query);
							$existing_ids=array();
							while($row = mysql_fetch_assoc($res2))
							{
								$existing_ids[] = $row['trial'];
							}
						if(empty($row['mesh_name'])) //delete only if they are not mesh related indexes
						{
						/************ we are no more deleting existing index.
							$qry='DELETE from '. $table .' where `'. $field . '` = "'. $productID . '"';
							if(!mysql_query($qry))
							{
								$log='Could not delete existing product indexes. Query='.$qry.' Error:' . mysql_error();
								$logger->fatal($log);
								$query = 'update update_status_fullhistory set 
								er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
								mysql_query($query);
								mysql_query('ROLLBACK');
								echo $log;
								return false;
							}
						*/
						}
//						$query = substr($mystring,0,$pos+6). '  ( ' . substr($mystring,$pos+6) . ' ) ';
						$query = $mystring ;
					}
				}
				
				/*******/
				

				if($query=='Invalid Json') 
				{
					echo '<br> Invalid JSON in table <b>'. 'entities' .'</b> and id=<b>'.$cid.'</b> : <br>';
					echo $searchdata;
					echo '<br>';
					--$total;
					continue;
				}
				// replace all intervention_other_name with intervention_name 
				$query=str_replace('intervention_other_name','intervention_name',$query) ;
				//pr($query);
				
				if(!$resu = mysql_query($query))
				{
					$log='Bad SQL query getting larvol_id from data_trials table.<br>Query=' . $query . ' Mysql error:'. mysql_error();
					$logger->fatal($log);
					echo $log;
					// Error in mysql query / invalid searchdata.  Anyway, let us not stop indexing, just ignore this particular trial and continue.
					continue;
					//return false;
				}
				
				$nctidz=array(); // search result
				$lidz=array();
				while($rowz = mysql_fetch_assoc($resu))
				{
					$lidz[] = $rowz['larvol_id'];
					$nctidz[] = $rowz;
				}
				
				//in case of a single product, the total column of status should show the total number of trials.
				if( !is_null($productID) )
					$total=count($nctidz);
				$up_id=mysql_insert_id();
				$query="select er.child from entity_relations er,entities e 
									where er.parent = " . $cid . "
									and er.child=e.id and e.class = 'Institution'";
							$res=mysql_query($query);
							$companyids=array();
							while($row = mysql_fetch_assoc($res)) 
							{ 
								$companyids[] = $row['child'];
							}	
							//get name,search_name of these companies 
							$cids = implode(",", $companyids);
							
				foreach($nctidz as $key => $value)
				{

					if (in_array ($value['larvol_id'], $existing_ids)) 
					{
						continue;
					}
				
					if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) and !empty($value) )
					{
						$srch = array_search_partial($sourceid, $value); 
						if($srch!==false) echo ''; else continue;
					}
					
					$indextable=$cat=='products' ? 'entity_trials' : 'entity_trials';
					$larvol_id=$value['larvol_id'];
					if(!isset($larvol_id) or empty($larvol_id) or is_null($larvol_id)) 
					{
						continue;
					}
					else
					{
						$query="select larvol_id,lead_sponsor from data_trials where larvol_id = " . $larvol_id . " limit 1";
						$res=mysql_query($query);
						if(!empty($res))
						{
							$row = mysql_fetch_assoc($res);
							$lead_sponsor = $row['lead_sponsor'];
						}
						$ownersponsored='No';
						if(!empty($lead_sponsor))
						{
							/******************** Calculate owner sponsored values           */
				
							$query="select name,search_name from entities where id in (" . $cids . ")	and class='institution'";
							$res=mysql_query($query);
							$csearchnames=array();
							while($row = @mysql_fetch_assoc($res)) 
							{ 
							
								//if searchname has multiple entries then store each of them separately into the array
								if(stripos($row['search_name'],"|"))
								{
									$searchname=explode("|", $row['search_name']);
									foreach($searchname as $name) $csearchnames[] = $name;
								}
								else $csearchnames[]=$row['search_name'];
								
								$csearchnames[] = $row['name'];
								
							}
							
								//now loop through the names/searchnames check if any of them matches with the trial's sponsor name
								$ownersponsored='No';
								foreach($csearchnames as $name)
								{
									$lead_sponsor='xxx.'.$lead_sponsor;
									$pos = stripos($lead_sponsor,trim($name));
									if(!empty($pos) and $pos>0 )	
									{
										$ownersponsored='Yes';
										break;
									}
								}
							//*************************************
						}
						if($ownersponsored=='Yes')	
						{
							$reltype = 'ownersponsored';
						}
						else
						{
							$reltype = 'default';
						}
						echo '<br> current time:'. date("Y-m-d H:i:s", strtotime('now')) . '<br>';
						if(trial_indexed($larvol_id,$cat,$cid)) // check if the trial+product/trial+area index already exists
						{
							$query="update entity_trials set relation_type = '". $reltype . "'
							where trial='" . $larvol_id . "' and entity = '" . $cid . "' limit 1";
							$res=mysql_query($query);
							echo '<br>Larvol ID:'.$larvol_id . ' is already indexed. <br>';
						}
						else
						{
							echo '<br>'. date("Y-m-d H:i:s", strtotime('now')) . ' - Indexing Larvol ID:'.$larvol_id . '<br>';
							$query='INSERT INTO `'. $table .'` (`'. $field .'`, `trial`, `relation_type` ) VALUES ("' . $cid . '", "' . $larvol_id .'", "' . $reltype .'") ';
							$res = mysql_query($query);
							if($res === false)
							{
								
								$log = 'Bad SQL query pre-indexing trial***. Query : ' . $query . '<br> MySql Error:'.mysql_error();
								echo $log;
								return false;
							}
						
						}
					
					}
					
				}
				$delids=array();
				foreach($existing_ids as $lid)
				{
					if (in_array ($lid, $lidz)) 
					{
					}
					else
					{
						$delids[]=$lid;
					}
				}
				
				$delids = implode(",", $delids);
				if(!empty($delids))
				{
					$qry2='DELETE from entity_trials  where entity = "'. $productID . '" and trial in
							(' .  $delids . ')';
								if(!mysql_query($qry2))
								{
									$log='Could not delete existing product indexes. Query='.$qry.' Error:' . mysql_error();
									$logger->fatal($log);
									echo $log;
									return false;
								}
				}
				
				
				$proc_id = getmypid();
				$i++;
				@flush();
				
				
			}
			
			
		}
	

	
	}
	

}

/*
Function trial_indexed() - to check if a combination of trial+product / trial+area is alrady indexed.
Parameters :
	1.	larvol id
	2.	"products" or "areas" as appropriate
	3.	product id or area id
*/
function trial_indexed($larvol_id,$cat,$cid)
{
	$indextable=$cat=='products' ? 'entity_trials' : 'entity_trials';
	$columnname=$cat=='products' ? 'entity' : 'entity';
//	$nctid=padnct($nctid);
	global $logger;
//	$query = 'SELECT trial from  ' . $indextable . ' where trial="' . $nctid . '" LIMIT 1';
	$query = 'SELECT `trial` from  `' . $indextable . '` where `trial`="' . $larvol_id . '" and `'. $columnname .'`= "' . $cid . '" ';
	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query checking trial index status. Query=' . $query;
		$logger->fatal($log);
		echo $log;
		return false;
	}
	$resu=array();
	while($resu[]=mysql_fetch_array($res1));
	$res2=$resu;
	foreach ($res2 as $key=>$value)
	{
		if(!isset($value) or empty($value) or is_null($value) ) unset($resu[$key]);
	}
	if(count($resu)>0)
	{
		return($resu);
	}
	else
	{
		return false;
	}

}

function array_search_partial($string, $array)
{
	foreach($array as $aval)
	{
		$pos = stripos("xx".$aval, $string);
		if( $pos !== false ) 
		{
			return true;
		}
	}
	
	return false;
}

function get_larvolid($osid)
{
$osid=trim($osid);
$query = "
				SELECT `larvol_id`
				FROM `data_trials` 
				WHERE `source_id` like '%".$osid."%' limit 1
				";
		$res1 	= mysql_query($query) ;
		if($res1===false)
		{
			$log = 'Bad SQL query. Query=' . $query;
			$logger->fatal($log);
			pr($log);
			return false;
		}
		$st=mysql_fetch_assoc($res1);
		return $st['larvol_id'];
}

function in_array_r($needle, $haystack, $strict = false) 
{
    foreach ($haystack as $item) 
	{
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) 
		{
            return true;
        }
    }

    return false;
}

?>