<?php
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once('searchhandler.php');
ini_set('memory_limit','-1');
ini_set('error_reporting', E_ALL ^E_NOTICE );
/*	

function pmtindex() - to preindex a combination of one abstract+one product, or  one abstract+one area.  
parameters : 
		1.	scrapper_run boolean
		2.	Array of entities
		3.	update id - supplied by viewstatus.php when a task is resumed / requed etc.
		4.	current product id - supplied by viewstatus.php when a task is resumed / requed etc.
		5.	product id / area id when a single product or area is to beindexed.
		6.	Array of pubmed abstract IDs

*/
function pmtindex($scraper_run=false,$productz=NULL,$up_id=NULL,$cid=NULL,$productID=NULL,$pmIDs)
{
	$table='entity_abstracts'; 
	$field='entity';

	global $logger,$now,$db;
	
	$DTnow = date('Y-m-d H:i:s',$now);
	if(!isset($i)) $i=0;
	if(is_null($productz))	// array of product ids
	{
		$productz=array();
		if(is_null($productID))
		{
			
			$query = 'SELECT `id`,`name`,`searchdata`,`search_name`' . ' from '. 'entities' .' where class IN ("Product","Disease","Institution") ';
			$ttype='ENTITY';
		}
		else 
		{
			$query = 'SELECT `id`,`name`,`searchdata`,`search_name`' . ' from '. 'entities' .' where `id`="' . $productID .'"' ;
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
			if(!isset($value['id']) or empty($value['id'])) break;
			$cid=$value['id'];
			$searchdata = $value['searchdata'];
			if(!isset($searchdata) or is_null($searchdata) or empty($searchdata)) {
				$name = $value['name'];
				$name = str_replace('/',' ',$name);
				$searchdata = '{"reportid":"9000","override":"","columndata":[],"sortdata":[],"groupdata":[],"wheredata":[{"columnname":"","opname":"Regex","chainname":"OR","columnvalue":"/'.$name.'/i"}';
				$searchname = $value['search_name'];
				$searchname = str_replace('/',' ',$searchname);
				if(isset($searchname) and !is_null($searchname) and !empty($searchname)) {
					$searchdata .= ',{"columnname":"","opname":"Regex","chainname":"OR","columnvalue":"/'.$searchname.'/i"}';
				}
				$searchdata .= ']}';
			}
			$pid=$value['id'];
			$prid=getmypid();
			if(!is_null($productz) and $pid>=$startid)	
			{
				$cid=$value['id'];
				// get the actual mysql query  
				try	
					{
						$query=buildPubmedQuery($searchdata,$pmIDs);
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
					
					if($up_id and !$scraper_run) 
					{
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="2",update_items_total = "' . $total . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
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
				
				

				$mystring=$query;
				
				/****/
				
				//Start the transaction.
				
				if(!mysql_query('SET autocommit = 0;'))
				{
					$log='Unable to begin transaction. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					$query = 'update update_status_fullhistory set 
					er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
					mysql_query($query);
					echo $log;
					return false;
				}
				if(!mysql_query('START TRANSACTION'))
				{
					$log='Unable to begin transaction. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					$query = 'update update_status_fullhistory set 
					er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
					mysql_query($query);
					echo $log;
					return false;
				}
				
				
				$findme   = 'where';
				$pos = stripos($mystring, $findme);
				/*
				if(!mysql_query('BEGIN'))
				{
					$log='Unable to begin transaction. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					echo $log;
					return false;
				}
				*/
				if ($pos === false) 
				{
					$log='Error in MySql Query (no "where" clause is used in the query)  :' . $query;
					$logger->warn($log);
					mysql_query('ROLLBACK');
//					echo $log;
					//return false;
				//	exit;
					continue;
				} 
				else 
				{					
						//delete existing product/area indexes					
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
						$query = $mystring ;					
				}
				

				if($query=='Invalid Json') 
				{
					echo '<br> Invalid JSON in table <b>'. 'entities' .'</b> and id=<b>'.$cid.'</b> : <br>';
					echo $searchdata;
					echo '<br>';
					--$total;
					if($up_id and !$scraper_run)
					{
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="2",update_items_total = "' . $total . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
						
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							mysql_query('ROLLBACK');
							echo $log;
							return false;
						}
					}
					if($scraper_run)
					{
						//insert new row in status
						$query = 'SELECT MAX(update_id) AS maxid FROM update_status_fullhistory' ;
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							mysql_query('ROLLBACK');
							echo $log;
							return false;
						}
						$res = mysql_fetch_array($res) ;
						$up_id = (isset($res['maxid'])) ? ((int)$res['maxid'])+1 : 1;
						$prid = getmypid();
				
						$query = 'INSERT into update_status_fullhistory (update_id,process_id,status,update_items_total,start_time,trial_type,item_id) 
						  VALUES ("'.$up_id.'","'. $prid .'","'. 2 .'",
						  "' . $total . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . $ttype . '" , "' . $pid . '" ) ;';

						//************/
					
				
						$query = '	update update_status_fullhistory set process_id="'. $prid . '",';
						$query .= '	er_message="Invalid JSON. table:'. 'entities' .', id:'.$cid.'",status="2",';
						$query .= '	update_items_total = "' . $total . '",trial_type="' . $ttype . '" ';
						$query .= '	where update_id= "'. $up_id .'" limit 1' ; 
						
						if(!$res = mysql_query($query))
						{
							$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
							$logger->error($log);
							mysql_query('ROLLBACK');
							echo $log;
							return false;
						}
						
					}
					continue;
				}
			
				if(!$resu = mysql_query($query))
				{
					$log='Bad SQL query getting pm_id from pubmed_abstracts table.<br>Query=' . $query . ' Mysql error:'. mysql_error();
					$logger->fatal($log);
					$query = 'update update_status_fullhistory set 
					er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
					//mysql_query($query);
					//mysql_query('ROLLBACK');
					echo $log,"\n";
					exit(-1);
					// Error in mysql query / invalid searchdata.  Anyway, let us not stop indexing, just ignore this particular abstract and continue.
					continue;
					//return false;
				}
				
				$nctidz=array(); // search result
				while($nctidz[]=mysql_fetch_array($resu));
				//in case of a single product, the total column of status should show the total number of abstracts.
				if( !is_null($productID) )
					$total=count($nctidz);
				
				if( $up_id and !$scraper_run) // task already exists, just update it.
				{	
					if($current==0)
					{
						++$current;
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="'. 2 . '",update_items_total = "' . $total . '",start_time = "'. date("Y-m-d H:i:s", strtotime('now')) . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
					}
					else
					{
						++$current;
						$query = 'update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="'. 2 . '",update_items_total = "' . $total . '",trial_type="' . $ttype . '" where update_id= "'. $up_id .'" limit 1' ; 
					}
				}
				elseif(!$scraper_run)  // insert new status row
				{
			
					$prid = getmypid();
				
					$query = 'INSERT into update_status_fullhistory (process_id,status,update_items_total,start_time,trial_type,item_id) 
						  VALUES ( "'. $prid .'","'. 2 .'",
						  "' . $total . '","'. date("Y-m-d H:i:s", strtotime('now')) .'", "' . $ttype . '" , "' . $pid . '" ) ;';
				
				}
				
				if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						$query = 'update update_status_fullhistory set 
						er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
						mysql_query($query);
						echo $log;
						mysql_query('ROLLBACK');
						return false;
					}
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
					$pm_id=$value['pm_id'];
					if(!isset($pm_id) or empty($pm_id) or is_null($pm_id)) 
					{
						continue;
					}
					else
					{				
						//echo '<br> current time:'. date("Y-m-d H:i:s", strtotime('now')) . '<br>';
						if(abstract_indexed($pm_id,$cid)) // check if the trial+product/abstract+area index already exists
						{
							echo '<br>Pubmed ID:'.$pm_id . ' is already indexed. <br>';
						}
						else
						{
							echo '<br>'. date("Y-m-d H:i:s", strtotime('now')) . ' - Indexing Pubmed ID:'.$pm_id . '<br>';
							$query='INSERT INTO `'. $table .'` (`'. $field .'`, `abstract` ) VALUES ("' . $cid . '", "' . $pm_id .'") ';
							$res = mysql_query($query);
							if($res === false)
							{
								$log = 'Bad SQL query pre-indexing abstract***. Query : ' . $query . '<br> MySql Error:'.mysql_error();
								mysql_query('ROLLBACK');
								$query = 'update update_status_fullhistory set 
								er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
								mysql_query($query);
								$logger->fatal($log);
								echo $log;
								return false;
							}
						
						}
						if( !is_null($productID) and !$scraper_run )	
						{
						
							$query = 'update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="'. 2 . '",
										  trial_type="' . $ttype . '", update_items_total=' . $total . ',update_items_progress=' . ++$progress . ', updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  where update_id= "'. $up_id .'" limit 1'  ; 
							if(!$res = mysql_query($query))
							{
								$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
								$logger->error($log);
								mysql_query('ROLLBACK');
								echo $log;
								return false;
							}
							if(!mysql_query('COMMIT'))
							{
								$log='Error - could not commit transaction. Query='.$query.' Error:' . mysql_error();
								$logger->fatal($log);
								mysql_query('ROLLBACK');
								$query = 'update update_status_fullhistory set 
								er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
								mysql_query($query);
								echo $log;
								return false;
							}
						}
						
					}
					
				
				}
				
				if(!mysql_query('COMMIT'))
				{
					$log='Error - could not commit transaction. Query='.$query.' Error:' . mysql_error();
					$logger->fatal($log);
					mysql_query('ROLLBACK');
					$query = 'update update_status_fullhistory set 
					er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
					mysql_query($query);
					echo $log;
					return false;
				}
				$proc_id = getmypid();
				$i++;

			//update status
				if( is_null($productID) and !$scraper_run )	
				{
					$query = 'update update_status_fullhistory set process_id="'. $prid . '",er_message="",status="'. 2 . '",
									  trial_type="' . $ttype . '", update_items_total=' . $total . ',update_items_progress=' . ++$progress . ', updated_time="' . date("Y-m-d H:i:s", strtotime('now')) . '"  where update_id= "'. $up_id .'" limit 1'  ; 
					if(!$res = mysql_query($query))
					{
						$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
						$logger->error($log);
						echo $log;
						return false;
					}
				}								
				@flush();				
			}
			
			
		}
		$query = 'UPDATE update_status_fullhistory 
				  SET status="'. 0 . '", er_message="",update_items_progress=update_items_total  
				  WHERE update_id= "'. $up_id .'" LIMIT 1'  ; 
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
					$query = 'UPDATE update_status_fullhistory 
							  SET er_message="' . $log . '" 
							  WHERE update_id= "'. $up_id .'" limit 1' ; 
					mysql_query($query);
					echo $log;
					return false;
				}
		if(!$scraper_run)
		{
			$query = 'UPDATE update_status_fullhistory 
					  SET status="'. 0 . '",er_message="", update_items_progress=update_items_total  
					  WHERE update_id= "'. $up_id .'" limit 1'  ; 
			if(!$res = mysql_query($query))
			{
				$log='There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
				$logger->error($log);
				$query = 'update update_status_fullhistory set 
					er_message="' . $log . '" where update_id= "'. $up_id .'" limit 1' ; 
					mysql_query($query);
				echo $log;
				return false;
			}
		}
	}
	
	
	
	elseif(isset($productID) and !empty($productID))
	{
	
		$query = '	SELECT LI_id from '. 'entities' .' where `id`="' . $productID .'" limit 1' ;
					$resu = mysql_query($query);
					$row=mysql_fetch_array($resu);
					if(!empty($row['LI_id'])) //delete only if they are not mesh related indexes
					{
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
					}
	}
}

/*
Function abstract_indexed() - to check if a combination of abstract+product / abstract+area is alrady indexed.
Parameters :
	1.	pm id
	2.	product id or area id
*/
function abstract_indexed($pm_id,$cid)
{
	$indextable= 'entity_abstracts' ;
	$columnname= 'entity';

	global $logger;

	$query = 'SELECT `abstract` from  `' . $indextable . '` where `abstract`="' . $pm_id . '" and `'. $columnname .'`= "' . $cid . '" ';
	$res1 		= mysql_query($query) ;
	if($res1===false)
	{
		$log = 'Bad SQL query checking abstract index status. Query=' . $query;
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

?>