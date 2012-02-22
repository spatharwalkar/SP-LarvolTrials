<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('searchhandler.php');


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
		$table='product_trials'; 
		$field='product';
	}
	else 
	{
		$table='area_trials'; 
		$field='area';
	}

	global $logger;
	global $now;
	global $db;
	$scraper_run=( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) );
	$DTnow = date('Y-m-d H:i:s',$now);
	if(!isset($i)) $i=0;
	if(is_null($productz))	// array of product ids
	{
		$productz=array();
		if(is_null($productID))
		{
			$query = 'SELECT `id`,`name`,`searchdata` from '. $cat .' where searchdata IS NOT NULL and  `searchdata` <>"" ';
			$ttype=$cat=='products' ? 'PRODUCT1' : 'AREA1';
		}
		else 
		{
			$query = 'SELECT `id`,`name`,`searchdata` from '. $cat .' where `searchdata` IS NOT NULL and  `searchdata` <>"" and `id`="' . $productID .'"' ;
			$ttype=$cat=='products' ? 'PRODUCT2' : 'AREA2';
		}
		
		if(!$resu = mysql_query($query))
		{
			$log='Bad SQL query getting  details from '. $cat .' table.<br>Query=' . $query;
			$logger->fatal($log);
			echo $log;
			exit;
		}
	//	$productz = mysql_fetch_assoc($resu);
	
		while($productz[]=mysql_fetch_array($resu));
	}
	// remove blanks
	foreach ($productz as $key => $product)
		if( is_null($product) or empty($product) ) unset($productz[$key]);
	
	if(!is_null($cid) and !empty($cid) and $cid>0) $startid=$cid; 
	else $startid=0;
	$total=count($productz);
	$current = 0;
	$progress=0;	
	if(count($productz)>0)
	{
	
		foreach ($productz as $key=>$value)
		{
			
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
					echo '<br> Invalid JSON in table <b>'. $cat .'</b> and id=<b>'.$cid.'</b> : <br>';
					echo $searchdata;
					echo '<br>';
					--$total;
					if($up_id and !$scraper_run) 
					{
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",status="2",update_items_total = "' . $total . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							echo $log;
							return false;
						}
					}
					
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
				
				if(!mysql_query('BEGIN'))
				{
					$log='Unable to begin transaction. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					exit;
				}
				
				if ($pos === false) 
				{
					$log='Error in MySql Query :' . $query;
					$logger->fatal($log);
					echo $log;
					exit;
				} 
				else 
				{
					/********** add source id to condition for quick indexing   *************/
					if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) )
					{
//						$ln=strlen('  `source_id` = "'. $sourceid . '"  and  ');
						$query = substr($mystring,0,$pos+6). ' ( `source_id` = "'. $sourceid . '" )  and  ( ' . substr($mystring,$pos+6) . ' ) ';
					}
					else
					{
						//delete existing product/area indexes
						$qry='DELETE from '. $table .' where `'. $field . '` = "'. $productID . '"';
						if(!mysql_query($qry))
						{
							$log='Could not delete existing product indexes. Query='.$qry.' Error:' . mysql_error();
							$logger->fatal($log);
							mysql_query('ROLLBACK');
							echo $log;
							exit;
						}
//						$query = substr($mystring,0,$pos+6). '  ( ' . substr($mystring,$pos+6) . ' ) ';
						$query = $mystring ;
					}
				}
				
				/*******/
				
//				$query.= ' and (  `source_id` = "'. $sourceid . '" )';
//				pr($query);
				
				if($query=='Invalid Json') 
				{
					echo '<br> Invalid JSON in table <b>'. $cat .'</b> and id=<b>'.$cid.'</b> : <br>';
					echo $searchdata;
					echo '<br>';
					--$total;
					if($up_id and !$scraper_run)
					{
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",status="2",update_items_total = "' . $total . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
						
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							echo $log;
							return false;
						}
					}
					continue;
				}
				if(!$resu = mysql_query($query))
				{
					$log='Bad SQL query getting larvol_id from data_trials table.<br>Query=' . $query;
					$logger->fatal($log);
					mysql_query('ROLLBACK');
					echo $log;
					exit;
				}
				
				$nctidz=array(); // search result
				while($nctidz[]=mysql_fetch_array($resu));
				
				//in case of a single product, the total column of status should show the total number of trials.
				if( !is_null($productID) )
					$total=count($nctidz);
				
				if( $up_id and !$scraper_run) // task already exists, just update it.
				{	
					if($current==0)
					{
						++$current;
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",status="'. 2 . '",update_items_total = "' . $total . '",start_time = "'. date("Y-m-d H:i:s", strtotime('now')) . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
					}
					else
					{
						++$current;
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",status="'. 2 . '",update_items_total = "' . $total . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
					}
				}
				elseif(!$scraper_run)  // insert new status row
				{
					$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
					$res = mysql_fetch_array($res) ;
					$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
					$prid = getmypid();
				
					$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,trial_type) 
						  VALUES ("'.$up_id.'","'. $prid .'","'. 2 .'",
						  "' . $total . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . $ttype . '"  ) ;';
				
				}
				
				if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
				
				
				foreach($nctidz as $key => $value)
				{
					if( isset($sourceid) and !is_null($sourceid) and !empty($sourceid) )
					{
						$srch = array_search($sourceid, $value); 
						if($srch!==false) echo ''; else continue;
					}
					
					$indextable=$cat=='products' ? 'product_trials' : 'area_trials';
					$larvol_id=$value['larvol_id'];
					if(!isset($larvol_id) or empty($larvol_id) or is_null($larvol_id)) 
					{
						continue;
					}
					else
					{
					
						if(trial_indexed($larvol_id,$cat,$cid)) // check if the trial+product/trial+area index already exists
						{
							echo '<br>Larvol ID:'.$larvol_id . ' is already indexed. <br>';
						}
						else
						{
							echo '<br>Indexing Larvol ID:'.$larvol_id . '<br>';
							$query='INSERT INTO `'. $table .'` (`'. $field .'`, `trial`) VALUES ("' . $cid . '", "' . $larvol_id .'") ';
							$res = mysql_query($query);
							if($res === false)
							{
								$log = 'Bad SQL query pre-indexing trial***. Query : ' . $query . '<br> MySql Error:'.mysql_error();
								mysql_query('ROLLBACK');
								$logger->fatal($log);
								die($log);
							}
						
						}
						if( !is_null($productID) and !$scraper_run )	
						{
						
							$query = 'update update_status_fullhistory set process_id="'. $prid . '",status="'. 2 . '",
										  trial_type="' . $ttype . '", update_items_total=' . $total . ',update_items_progress=' . ++$progress . ', updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  where update_id= "'. $up_id .'" limit 1'  ; 
							if(!$res = mysql_query($query))
							{
								$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
								$logger->error($log);
								echo $log;
								return false;
							}
							if(!mysql_query('COMMIT'))
							{
								$log='Error - could not commit transaction. Query='.$query.' Error:' . mysql_error();
								$logger->fatal($log);
								mysql_query('ROLLBACK');
								echo $log;
								exit;
							}
						}
						
					}
					
				
				}
				
				if(!mysql_query('COMMIT'))
				{
					$log='Error - could not commit transaction. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					mysql_query('ROLLBACK');
					echo $log;
					exit;
				}
				$proc_id = getmypid();
				$i++;
			//	$ttype=$cat=='products' ? 'PRODUCT' : 'AREA';
				//update status
				if( is_null($productID) and !$scraper_run )	
					{
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",status="'. 2 . '",
										  trial_type="' . $ttype . '", update_items_total=' . $total . ',update_items_progress=' . ++$progress . ', updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  where update_id= "'. $up_id .'" limit 1'  ; 
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							echo $log;
							return false;
						}
					}
				/***************** 
				$query = 'SELECT update_items_progress,update_items_total FROM update_status_fullhistory WHERE update_id="' . $up_id .'" and trial_type="' . $ttype . '" limit 1 ' ;
				if(!$res = mysql_query($query))
				{
					$log='Bad SQL query selecting rows from update_status_fullhistory. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					
					exit;
				}
				
				$res = mysql_fetch_array($res) ;
				if ( isset($res['update_items_progress'] ) and $res['update_items_progress'] > 0 ) $updtd_items=((int)$res['update_items_progress']); else $updtd_items=0;
				if ( isset($res['update_items_total'] ) and $res['update_items_total'] > 0 ) $tot_items=((int)$res['update_items_total']); else $tot_items=0;
		
				$query = ' UPDATE  update_status_fullhistory SET process_id = "'. $proc_id  .'" , update_items_progress= "' . ( ($tot_items >= $updtd_items+$i) ? ($updtd_items+$i) : $tot_items  ) . '" , status="2", current_nctid="'. $cid .'", updated_time="' . date("Y-m-d H:i:s", strtotime('now'))  . '" WHERE update_id="' . $up_id .'" and trial_type= "' . $ttype . '"  ';
				
				if(!$res = mysql_query($query))
				{
					$log='Unable to update update_status_fullhistory. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					return false;
				}
				
				****************/
				
				@flush();
				
				
			}
	
			
		}
		if(!$scraper_run)
		{
			$query = 'update update_status_fullhistory set status="'. 0 . '", update_items_progress=update_items_total  where update_id= "'. $up_id .'" limit 1'  ; 
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				echo $log;
				return false;
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
	$indextable=$cat=='products' ? 'product_trials' : 'area_trials';
	$columnname=$cat=='products' ? 'product' : 'area';
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
		die($log);
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

?>