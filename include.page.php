<?php
$USE_OLD_JQUERY = true;


/**
 * @name tableColumns
 * @tutorial outputs the table field Names
 * @param string $table The table for which columns need to be fetched
 * @return array $columnList
 * @author Jithu Thomas
 */
function tableColumns($table)
{
	global $logger;

	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
			case 'investigator': $actual_table = "entities"; break;
		}	
	
	$query = "SHOW COLUMNS FROM $actual_table";	
	$res = mysql_query($query);
	if($res)
	{	
		while($row = mysql_fetch_assoc($res))
		{
			//added this condition so that area edit page works fine with the view.
			if($row['Field']<>'coverage_area')
			{
				$columnList[] = $row['Field'];
			}
		}
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}


	if($table == 'upm')
	{
		//TODO: make it dynamic.
		//explicitly adding area column for upm as its a one to many in upm_areas
		$columnList[] = 'area';
		$columnList[] = 'larvol_id';
	}

	return $columnList;
}

/**
 * @name tableColumnsDetails
 * @tutorial outputs the table field details
 * @param string $table The table for which columns need to be fetched
 * @return array $columnList
 * @author Jithu Thomas
 */
function tableColumnDetails($table)
{
	global $logger;
	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
			case 'investigator': $actual_table = "entities"; break;
		}	
	$query = "SHOW COLUMNS FROM $actual_table";
	$res = mysql_query($query);
	if($res)
	{
		while($row = mysql_fetch_assoc($res))
		{
			$columnList[] = $row;
		}
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}

	return $columnList;
}

/**
 * @name contentListing
 * @tutorial Provides output of all the table entries in the upm table based on the params $start and $limit
 * @param int $start Start value of sql select query.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function contentListing($start=0,$limit=50,$table,$script,$ignoreFields=array(),$includeFields=array(),$options=array('delete'=>true,'ignoresort'=>array(),'extrasort'=>array()))
{
global $deleteFlag;
$addEdit_flag = isset($options['addEdit_flag']) ? (bool) $options['addEdit_flag'] : FALSE;
if($options['delete']===false)
$deleteFlag=false;
//get search params
$actual_table=$table;
$orig_table=$table;
switch($table)
	{
		case 'products': $actual_table = "entities"; break;
		case 'areas': $actual_table = "entities"; break;
		case 'institutions': $actual_table = "entities"; break;
		case 'moas': $actual_table = "entities"; break;
		case 'moacategories': $actual_table = "entities"; break;
		case 'investigator': $actual_table = "entities"; break;
		case 'diseases': 
			
			/****** MESH ********/
			
			$mesh = $_GET['mesh_display'];
			if ($mesh=='YES') 
			{
				$checked='checked="checked"';
			}
			else 
			{
				$checked = "";
			}
						
			$url=fixurl(array('mesh_display'));

			if ($mesh=='YES') 
				{
					$show_mesh=
					'<form name="mesh" action="'. $url .'&mesh_display=NO" method="POST">'.
					'<b><span style="color:red">Show MeSH</span></b>&nbsp;
					<input type="checkbox" name="mesh_display" value="NO" onClick="submit();"'. $checked .'</span>'.
					'</form><br>';
				}
			else		
				{
					$show_mesh=
					'<form name="mesh" action="'. $url .'&mesh_display=YES" method="POST">'.
					'<b><span style="color:red">Show MeSH</span></b>&nbsp;
					<input type="checkbox" name="mesh_display" value="YES" onClick="submit();"'. $checked .'</span>'.
					'</form><br>';
				}
			/*************/
			
			$actual_table = "entities"; 
			break;
	}
$where = calculateWhere($actual_table,$table);
//calculate sortable fields
//upm area field is included directly from upm custom query so ignore sort& other customizations for upm areas should be done elsewhere.
$query = "SHOW COLUMNS FROM $actual_table";
$res = mysql_query($query);
$sortableRows = $options['extrasort'];
while($row = mysql_fetch_assoc($res))
{
	$type = $row['Type'];
	if(strstr($type,'int(') || $type=='date')
	{
		if(isset($options['ignoresort']) && in_array($row['Field'],$options['ignoresort']))
		continue;
		$sortableRows[] = $row['Field'];
	}
}

$orderBy = (isset($_GET['order_by']))?' ORDER BY '.$_GET['order_by']:null;
$currentOrderBy = $orderBy;
$sortArr = array('ASC','DESC','no_sort');
$sortOrder = null;
$noSort = null;
$currentSortOrder = null;

if($orderBy)
{
	$currentSortOrder = $_GET['sort_order'];
	foreach($sortArr as $value)
	{
		if($value==$_GET['sort_order'])
		break;
	}
	if(current($sortArr) == '')
	{
		$sortOrder = $sortArr[0];
		$sortImg = $sortOrder;
	}
/*	elseif($_GET['search'] && $_GET['sort_order']=='DESC')
	{
		$sortOrder=$_GET['sort_order'];
	}	*/
	elseif(current($sortArr)=='no_sort')
	{
		$sortOrder = null;
		$noSort = '&no_sort=1';
		$sortImg = 'ASC';
	}
	else
	{
		$sortOrder = current($sortArr);
	}


}
if(isset($_GET['no_sort']) && $_GET['no_sort']==1)
{
	$sortImg = '';
}
if(isset($_GET['sort_order']) && $_GET['sort_order']=='ASC' )
{
	$sortImg = 'ASC';
}
if(isset($_GET['sort_order']) && $_GET['sort_order']=='DESC' )
{
	$sortImg = 'DESC';
}

if($table !='upm')
{
	
	if($table =='diseases' && $mesh=="YES")
		$where .= " and class='Disease' and mesh_name!='' ";
	else if($table =='diseases')
	    $where .= " and class='Disease' and (mesh_name='' OR mesh_name IS NULL)";
	if($_GET['no_sort']!=1)
		$query = "select * from $actual_table $where $currentOrderBy $currentSortOrder limit $start , $limit";
	else
		$query = "select * from $actual_table $where limit $start , $limit";

}
elseif($table=='upm')
{
	if($_GET['no_sort']!=1)
	$query = "select upm.`id`, upm.`event_type`, redtags.`name` as redtag, upm.`event_description`, upm.`event_link`, upm.`result_link`, p.`name` as product, upm_areas.`area_id` as area, upm_trials.`larvol_id` as larvol_id, upm.`condition`, upm.`start_date`, upm.`start_date_type`, upm.`end_date`, upm.`end_date_type`, upm.`last_update` from upm left join products p on upm.product=p.id left join upm_areas on upm_areas.upm_id=upm.id left join upm_trials on upm_trials.upm_id = upm.id left join redtags on upm.redtag = redtags.id $where  group by upm.id $currentOrderBy $currentSortOrder limit $start , $limit";
	else
	$query = "select upm.`id`, upm.`event_type`, redtags.`name` as redtag, upm.`event_description`, upm.`event_link`,upm.`result_link`,p.`name` as product, upm_areas.`area_id` as area, upm_trials.`larvol_id` as larvol_id, upm.`condition`, upm.`start_date`, upm.`start_date_type`, upm.`end_date`, upm.`end_date_type`, upm.`last_update` from upm left join products p on upm.product=p.id left join upm_areas on upm_areas.upm_id=upm.id left join upm_trials on upm_trials.upm_id = upm.id left join redtags on upm.redtag = redtags.id $where group by upm.id limit $start , $limit";
}
$res = mysql_query($query) or softDieSession('Cannot get '.$table.' data.'.$query);
$i=0;
$skip=0;

$deleteParams = substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'],'?')+1);
//remove save flag from params 
$deleteParams = str_replace('&save=Save', '', $deleteParams);
$deleteConnector = '&';
echo '<div></div>';

/** Show the checkbox to display mesh diseases. **/

echo $show_mesh;

/************************/

echo '<table border="1" width="99%">';
while ($row = mysql_fetch_assoc($res))
{
	
	if($table == 'areas' && $row['coverage_area'] == 1)
	{
		$defaultTdStyle = 'background-color:#7FBEFF';
	}
	elseif($table == 'diseases' && $row['class'] == 'Disease' && !empty($row['LI_id']) )
	{
		$defaultTdStyle = 'background-color:#7FBEFF';
	}
	else
	{
		$defaultTdStyle = null;
	}
	
	if($i==0)
	{

		echo '<tr style="text-align:center">';
		$j=0;
		foreach($row as $columnName=>$v)
		{
			if(in_array($columnName,$ignoreFields))continue;
			
			echo '<th>';
			$params = null;
			$params = substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'],$script.'.php'));
			$params = $params!=$script.'.php'?substr($params, 0,strpos($params,'order_by')-1):$params;
			
			$connector = $params!=$script.'.php'?'&':'?';
			if(isset($_GET['order_by']) && $_GET['order_by']==$columnName && in_array($columnName,$sortableRows))
			$url = urlPath().$params.$connector.'order_by='.$columnName.'&sort_order='.$sortOrder.$noSort;
			elseif(in_array($columnName,$sortableRows))
			{
			$url = urlPath().$params.$connector.'order_by='.$columnName.'&sort_order=ASC';
			}
			else
			$url=null;
			
			if($url)
			{
				$url.='&entity='.$_REQUEST['entity'];
				echo '<a href="'.$url.'">';
			}
			if($table == 'upm' && $columnName == 'larvol_id')
			echo 'Trial Id';
			else
			echo ucwords(implode(' ',explode('_',$columnName)));
			if($url)
			{
				if($columnName==$_GET['order_by'] || ($j==0 && !isset($_GET['order_by'])))
				{
					$imgSort = $sortImg;
				}
				else
				{
					$imgSort = '';
				}
				echo '</a><a style="border:0" href="'.$url.'"><img style="border:0" src="images/'.strtolower($imgSort).'.png"/></a>';
			}
			echo '</th>';
			$j++;
			$i++;
		}
		if($deleteFlag)
		echo '<th>Del</th>';
		echo '</tr>';
	
		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			if(in_array($columnName,$ignoreFields))continue;
			
			if($columnName == 'id')
			{
				$upmId = $v;
                                $edit_url = '<a href="'.$script.'.php?id='.$v.'&entity='.$_GET['entity'].'&mesh_display='.$_GET['mesh_display'].'">'.$v.'</a>';
                                
				echo '<td style="'.$defaultTdStyle.'">';
				echo $edit_url;
				echo '</a></td>';				
			}else
			if($columnName == 'event_link' || $columnName == 'result_link')
			{
				echo '<td nowrap style="max-width:150px;overflow:hidden"><a  href="'.$v.'">';
				echo $v;
				echo '</a></td>';			
			}
			else
			if($columnName == 'searchdata' && $table=='products')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$actual_table,'id'=>$upmId,'callFrom'=>'contentListingProducts'));
				echo '</td>';				
			}	
			else
			if($columnName == 'searchdata' && ($table=='areas' || $table=='diseases'))
			{
				echo '<td style="'.$defaultTdStyle.'">';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$actual_table,'id'=>$upmId,'callFrom'=>'contentListingAreas'));
				echo '</td>';
			}					
			else
			if($columnName == 'searchdata')
			{
				echo '<td style="'.$defaultTdStyle.'">';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$actual_table,'id'=>$upmId));
				echo '</td>';				
			}
			else
			if($columnName == 'area' && $table == 'upm')
			{
					echo '<td>';
					echo getUpmAreaNames($upmId);
					echo '</td>';
			}
			else
			if($columnName == 'larvol_id' && $table == 'upm')
			{
					echo '<td>';
					echo getUpmLarvolIDs($upmId);
					echo '</td>';
			}
			else
			if($columnName == 'is_active' && $table == 'products')
			{
					echo '<td>';
					echo (($v==='0')?'False':'True');
					echo '</td>';
			}						
			else 
			{
				echo '<td style="'.$defaultTdStyle.'">';
				echo $v;
				echo '</td>';
			}
		}	
		if($deleteFlag)
		echo '<td><a onclick="return upmdelsure();" href="'.$script.'.php?deleteId='.$upmId.'&'.$deleteParams.'"><img src="images/not.png"/ alt="Delete '.$table.' id '.$upmId.'" title="Delete '.$actual_table.' id '.$upmId.'" style="border:0"></a></td>';
		echo '</tr>';	
	}
	else
	{
		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			if(in_array($columnName,$ignoreFields))continue;
			
			if($columnName == 'id')
			{
				$upmId = $v;
                                $edit_url = '<a href="'.$script.'.php?id='.$v.'&entity='.$_GET['entity'].'&mesh_display='.$_GET['mesh_display'].'">'.$v.'</a>';
                                
				echo '<td style="'.$defaultTdStyle.'">';
				echo $edit_url;
				echo '</td>';				
			}else
			if($columnName == 'event_link' || $columnName == 'result_link')
			{
				echo '<td nowrap style="max-width:150px;overflow:hidden" ><a  href="'.$v.'">';
				echo $v;
				echo '</a></td>';				
			}	
			else
			if($columnName == 'searchdata' && $table=='products')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$actual_table,'id'=>$upmId,'callFrom'=>'contentListingProducts'));
				echo '</td>';				
			}	
			else
			if($columnName == 'searchdata' && ($table=='areas' || $table=='diseases'))
			{
				echo '<td style="'.$defaultTdStyle.'">';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$actual_table,'id'=>$upmId,'callFrom'=>'contentListingAreas'));
				echo '</td>';
			}					
			else
			if($columnName == 'searchdata')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$actual_table,'id'=>$upmId));
				echo '</td>';				
			}
			else 
			if($columnName == 'area' && $table == 'upm')
			{
				echo '<td>';
				echo getUpmAreaNames($upmId);
				echo '</td>';
			}
			else
			if($columnName == 'larvol_id' && $table == 'upm')
			{
					echo '<td>';
					echo getUpmLarvolIDs($upmId);
					echo '</td>';
			}
			else
			if($columnName == 'is_active' && $table == 'products')
			{
					echo '<td>';
					echo (($v==='0')?'False':'True');
					echo '</td>';
			}			
			else 
			{
				echo '<td style="'.$defaultTdStyle.'">';
				echo $v;
				echo '</td>';
			}
		}
		if($deleteFlag)
		echo '<td><a onclick="return upmdelsure();" href="'.$script.'.php?deleteId='.$upmId.'&'.$deleteParams.'"><img src="images/not.png"/ alt="Delete '.$table.' id '.$upmId.'" title="Delete '.$actual_table.' id '.$upmId.'" style="border:0"></a></td>';
		echo '</tr>';				
	}
	
	
$i++;
}
echo '</table>';
echo '<br/>';
}

/**
 * @name getUpmAreaNames
 * @tutorial Returns comma separated area names for a upm
 * @author Jithu Thomas
 */
function getUpmAreaNames($upmId)
{
	global $db, $logger;
	$query = "select a.name from upm_areas u left join areas a on a.id=u.area_id where u.upm_id=$upmId";
	$result = mysql_query($query);
	$areaName = array();
	
	if($result)
	{
		while($row = mysql_fetch_assoc($result))
		{
			$areaName[] = $row['name'];
		}
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}
	
	return implode(',', $areaName);
}

/**
 * @name getUpmLarvolIds
 * @tutorial Returns comma separated larvol ids for a upm
 */
function getUpmLarvolIDs($upmId)
{
	global $db, $logger;
	$query = "select ut.larvol_id from upm_trials ut left join upm u on u.id=ut.upm_id where u.id=$upmId";
	$result = mysql_query($query);
	$larvol_id = array();
	
	if($result)
	{
		while($row = mysql_fetch_assoc($result))
		{
			$larvol_id[] = $row['larvol_id'];
		}
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}
	
	return implode(',', $larvol_id);
}

/**
 * @name getUpmLarvolIds
 * @tutorial Returns comma separated larvol ids for a upm
 */
function getUpmSourceIDFrmLarvolIDs($LarvolId)
{
	global $db, $logger;
	$query = "select source_id from `data_trials` where `larvol_id`='".mysql_real_escape_string($LarvolId)."'";
	$SourceIDQuery = mysql_query($query);
	if($SourceIDQuery)
	{
		while($SourceIdFrmLarvolArray = mysql_fetch_assoc($SourceIDQuery))
		$SrcIDfrmLarvol = $SourceIdFrmLarvolArray['source_id'];
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}
	
	return $SrcIDfrmLarvol;
}


/**
 * @name calculateWhere
 * @tutorial Outputs the WHERE query substring.
 * Just follow the naming convention of get parameters passed start with search_ and
 * it should automatically create the where clause of your sql.
 * @param $_GET['search_*
 * @author Jithu Thomas
 */
function calculateWhere($table,$orig_table="")
{
	//pr($_GET);
	$postKeys = array_keys($_GET);
	
	$whereArr = array();
	foreach($postKeys as $keys)
	{
		$explode = explode('search_',$keys);
		if(isset($explode[1]))	
		{
			if(is_array($_GET[$keys]))
			{
				$whereArr[$explode[1]] = $_GET[$keys];
			}
			else if(trim($_GET[$keys]) !='')
			$whereArr[$explode[1]] = $_GET[$keys];
		}
		else
		{
			continue;
		}
	}
	
	
	
	//start bool checkbox filtering
	//TODO: make dynamic with tablecolumndetails & bool tinyint(1) type filtering
	//commented out as for areas unchecked in search is considered as for showing both types of coverage area.
/* 	if($table == 'areas')
	{
		if(!isset($_GET['search_coverage_area']))
		{
			$whereArr['coverage_area'] = 0;
		}
	} */
	//pr($postKeys);die;
	//end bool checkbox filtering
	
	if(count($whereArr)>0)
	{
		$whereKeys = array_keys($whereArr);
		foreach($whereKeys as $k => $v) $whereKeys[$k] = $table . '.`' . $v . '`';
		$whereValues = array_values($whereArr);
		$whereArr = array_map(
							function($whereKeys,$whereValues)
							{
								if(is_array($whereValues) && trim($whereKeys) == 'upm.`larvol_id`')
								{
									$whereValues = array_filter($whereValues);
									if(!empty($whereValues))
									return ' upm_trials.larvol_id IN( '.implode(',',$whereValues).') AND ';
									else
									return;
								}
								else if(is_array($whereValues))
								{
									//TODOmake dynamic
									//return ' '.$whereKeys.' IN( '.implode(',',$whereValues).') AND ';
									return ' upm_areas.area_id IN( '.implode(',',$whereValues).') AND ';
								}								
								//real escape search values
								$whereValues = mysql_real_escape_string($whereValues);
								//check search keys are regex or not.
								$pcre = strlen($whereValues) > 1 && $whereValues[0] == '/' && ($whereValues[strlen($whereValues)-1] == '/' || ($whereValues[strlen($whereValues)-2] == '/' && strlen($whereValues) > 2));
								//if regex pattern then check with a sample query.
								if($pcre)
								{
									$result=validateMaskPCRE($whereValues);
									if(!$result)
									throw new Exception("Bad regex: $whereKeys = $whereValues", 6);
									return ' PREG_RLIKE("' . $whereValues . '",' . $whereKeys . ') AND ';
								}
								if($whereKeys=='upm.`event_description`' || $whereKeys=='products.`name`')
								{
									return ' '.$whereKeys.' LIKE '. '\'%'.$whereValues.'%\' AND ';
								}
								if($whereKeys == 'upm.`id`' || $whereKeys == 'products.`id`' || $whereKeys == 'areas.`id`')
								{
									if(strpos($whereValues,','))
									{
										$whereValues = explode(',',$whereValues);
										$whereValues = array_filter($whereValues);	
										$whereValues = implode(',',$whereValues);
										return ' '.$whereKeys.' IN( '.$whereValues.') AND ';
									}
								}

								return ' '.$whereKeys.' = '. '\''.$whereValues.'\' AND ';
							},
							$whereKeys,
							$whereValues
						);
		
	}
	//searching for explicity searchDataCheck case.
	if(isset($_GET['searchDataCheck']) && $_GET['searchDataCheck'] == 'on')
	{
		$whereArr[] = $table.".searchdata !='' AND ";
	}	
	if(count($whereArr)>0)
	{
		$where = ' WHERE ';
		$where .= implode(' ',$whereArr);
		$where = substr($where,0,-5);
	}
	else
	{
		$where = null;
	}
	
	switch($orig_table)
		{
			case 'products': $class = "Product"; break;
			case 'areas': $class = "Area"; break;
			case 'institutions': $class = "Institution"; break;
			case 'moas': $class = "MOA"; break;
			case 'moacategories': $class = "MOA_Category"; break;
			case 'diseases': $class = "Disease"; break;
			case 'investigator': $class = "Investigator"; break;
		}

	if(!empty($class))
	{
		if(empty($where))
			$where=' where (class = "'.$class.'")';
		else
			$where=$where.' and (class = "'.$class.'")';
	}
	return $where;	

}

/**
 * @name getTotalCount
 * @tutorial Outputs the total table count.
 * @param String $table
 * @author Jithu Thomas
 */
function getTotalCount($table)
{
	global $db, $logger;
	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
			case 'investigator': $actual_table = "entities"; break;
			
		}
	$where = calculateWhere($actual_table,$table);
	if($table == 'upm')
	{
		$query = "select count(distinct upm.id) as cnt from $table left join upm_areas on $table.id=upm_areas.upm_id left join upm_trials on $table.id=upm_trials.upm_id $where";
	}
	elseif($table == 'redtags')
	{
		$query = "select count(name) as cnt from $table $where";
	}
	else
	{
		$query = "select count(id) as cnt from $actual_table $where";
	}
	$res = mysql_query($query);
	if($res)
	{
		$count = mysql_fetch_row($res);
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}

	return $count[0];
}

/**
 * @name deleteData
 * @tutorial Deletes the upm entry for the specific id.
 * @param $id The id field in the upm table.
 * @author Jithu Thomas
 */
function deleteData($id,$table)
{
	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
		}
		if($table=='investigator') return false;
	$query = "delete from $actual_table where id=$id";
	mysql_query($query) or softDieSession('Cannot delete '.$table.'. '.$query);
	echo 'Successfully deleted '.$table.'.';
	
}

/**
 * @name importUpm 
 * @tutorial Outputs the table import form.
 * Default runs for upm table
 * @author Jithu Thomas
 */
function importUpm($script='upm',$table='upm')
{
	echo '<div class="clr">';
	echo '<fieldset>';
	echo '<legend> Import : </legend>';
	echo '<form name="'.$table.'_import" method="post" enctype="multipart/form-data" action="'.$script.'.php">';
	echo '<input name="uploadedfile" type="file" /><br />
		 <input type="submit"  value="Upload File" />';
	echo '</form>';
	echo '</fieldset>';
	echo '</div>';	

}

/**
 * @name input_tag
 * @tutorial Helper function for creating input tag based on the type of the field input.
 * Enum fields give select field html and other fields now return input tag.
 * @param array $row each row of a select column query.
 * @param int $dbVal Value taken for each field during edit.
 * If db value is there, the default value of that field is populated with that value.
 * @author Jithu Thomas
 */
function input_tag($row,$dbVal=null,$options=array())
{
	global $searchData;
	
	//get general input params from options
	$disabled = (isset($options['disabled']) && $options['disabled']===true)?'disabled="disabled"':null;
        //Not using $disabled var above seems to be being used as bool in the code below whereas its value is being set as non-boolean??
	$input_disabled = (isset($options['input_disabled']) && $options['input_disabled']===true)?TRUE:FALSE;
	if(strpos($_SERVER['REQUEST_URI'],'upm'))
		$input_disabled=FALSE;
	
	$altTitle = (isset($options['alttitle']))?$options['alttitle']:null;
	$style = (isset($options['style']))?$options['style']:null;
	
	$type = $row['Type'];
	if(substr($type,0,4)=='enum')
	$type = 'enum';
	if(isset($options['table']) && $options['table']!='' && $row['Field']=='searchdata')
	{
		$type = 'searchdata';
	}
	if(isset($options['deletebox']) && $options['deletebox'] && is_numeric($options['id']))
	{
		$type = 'deletebox';
	}	
	$nameIndex = isset($options['name_index'])?$options['name_index'].'_':null; 
	
	switch($type)
	{
		case 'enum':
                        if($input_disabled) {
                            echo $dbVal; 
                            break;
                        }                 
			$type1 = $row['Type'];
			$search = array('enum','(',')','\'');
			$replace = array('','','','');
			$type1 = str_replace($search, $replace, $type1);
			$optionArr = explode(',',$type1);
			$optionArr = array_map(am2,$optionArr,array_fill(0,count($optionArr),$dbVal));
			if($options['null_options']===true)
			array_unshift($optionArr, '<option value="">Select</option>');
			return '<select '.$style.' name="'.$nameIndex.$row['Field'].'">'.implode('',$optionArr).'</select>';
			break;
			
		case 'searchdata':
                        if($input_disabled) {
                            echo ''; 
                            break;
                        }
			$id = $options['id']?$options['id']:null;
			$table = $options['table'];
			if($searchData!='' && ($options['callFrom']!='contentListingProducts' && $options['callFrom']!='contentListingAreas'))
			{
				$img = 'edit.png';
				$modifier = '[Modified]';
				$delete = '';
			}
			else
			if($dbVal!='')
			{
				$img = 'edit.png';
				$modifier = '[Full]';
				$delete = '&nbsp;<label class="lbldel" style="float:none;"><input type="checkbox" title="Delete" name="delsearch['.$id.']" class="delsearch"></label>';
			}
			else
			{
				$img = 'add.png';
				$modifier = '[Empty]';
				$delete = '';
			}
			if(isset($options['callFrom']) && $options['callFrom']=='addedit')
			{
				$modifier = $modifier;
				if(isset($options['saveStatusForInputTag']) && $options['saveStatusForInputTag'] == 0 && $dbVal!='')
				$modifier = '[Modified]';
			}
			elseif(isset($options['callFrom']) && ($options['callFrom']=='contentListingProducts' || $options['callFrom']=='contentListingAreas'))
			{
				return $modifier;
			}
			else
			{
				$modifier = '';
				$delete = '';
			}
			$task = ($dbVal=='')?'Add':'Edit';
			//echo $dbVal;die;
			$hiddenSearchData = '<input type="hidden" name="searchdata" id="searchdata" value=\''.($dbVal).'\'/>';
			return $hiddenSearchData.'<a class="ajax cboxElement" href="#inline_content"><img id="add_edit_searchdata_img" src="images/'.$img.'" title="'.$task.' Search Data" alt="'.$task.' Search Data"/></a>&nbsp;<span id="search_modifier">'.$modifier.'</span>'.$delete;
			break;
			
		case 'deletebox':
			$id = $options['id'];
			return '&nbsp;<label class="lbldel" style="float:none;" title="'.$altTitle.'" alt="'.$altTitle.'"><input '.$disabled.' type="checkbox" title="'.$altTitle.'" alt="'.$altTitle.'" name="deleteId" value="'.$id.'" class="delsearch"></label>';
			break;
		case 'checkbox':
			$checkedStat = ($dbVal=='on')?'checked="checked"':null;
			return '<input type="checkbox" name="'.$row['Field'].'" id="'.$row['Field'].'" title="'.$altTitle.'" alt="'.$altTitle.'" '.$checkedStat.'/>';
			break;
		case 'link':
			$linkTarget = (isset($options['linkTarget']) && $options['linkTarget']=='_blank')?' target="_blank" ':'';
			return '<a href="'.$row['Field'].'" title="'.$altTitle.'" alt="'.$altTitle.'" '.$linkTarget.'>'.$dbVal.'</a>';
			break;	
		case 'iframe':
			return '<iframe src="'.$row['Field'].'" style="'.(isset($options['style'])?$options['style']:'').'" class="'.(isset($options['class'])?$options['class']:'').'"></iframe>';
			break;
		case 'tinyint(1)':
			if(isset($options) && isset($options['look_for_bool']) && $options['look_for_bool'] === true)
			{
				//normally mysql bool types are handled with a 1/0 for true/false case in php so we implmement the same handler here for this case.
				$checkedStat = ($dbVal=='1')?'checked="checked"':null;
				return '<input type="checkbox" name="'.$nameIndex.$row['Field'].'" id="'.$row['Field'].'" title="'.$altTitle.'" alt="'.$altTitle.'" '.$checkedStat.'/ value="1">';
			}
		default:
                  
			$dateinput = (strpos($row['Field'], 'date') !== false) ? ' class="jdpicker"' : '';
			
			if(is_array($dbVal) && count($dbVal)>0 && isset($options['one_to_many']) && $options['one_to_many']==1)
			{
				foreach($dbVal as $dbValIndividual)
				{
					if(trim($dbValIndividual) != '') 
					{
						$out .= '<tr><td></td><td><input class="'.$nameIndex.$row['Field'].'_autosuggest_multiple" name="'.$nameIndex.$row['Field'].'[]" value="'.$dbValIndividual.'" checked="checked" type="checkbox"> '.$dbValIndividual.' <img style="border:0" title="Delete '.ucfirst($row['Field']).'" alt="Delete '.ucfirst($row['Field']).'" src="images/not.png" class="auto_suggest_multiple_delete"></td></tr>';
					}
				}
				return $out;
			}
			elseif(is_array($dbVal) && count($dbVal)>0 && (isset($options['one_to_many']) || $options['one_to_many']!=1))
			{
				//nodb val support since dbval is array this option is proceeded with a options['one_to_many'] == 1 input_tag call
				return $input_disabled ? "" : '<input '.$style.' type="text" value="" name="'.$nameIndex.$row['Field'].'" id="'.$nameIndex.$row['Field'].'"' . $dateinput . '/>';
			}
			else 
			{
				return $input_disabled ? $dbVal : '<input '.$style.' type="text" value="'.$dbVal.'" name="'.$nameIndex.$row['Field'].'" id="'.$nameIndex.$row['Field'].'"' . $dateinput . '/>';
			}
			break;
	}
}

/**
 * @name saveData
 * @tutorial Saves the table entry/edit forms and inputs from the tab seperated file inputs.
 * @tutorial For products import subroutine return codes are as follow 1=>success, 2=>fail, 3=>skippped, 4=>deleted
 * @param array $post Post array.
 * @param int $import =0 for normal form save and = 1 for tab seperated input.
 * @param array $importKeys Keys for import relates to the fields in the upm table.
 * @param array $importVal Values for each column in the upm table corresponds to a single line in hte import file.
 * @param int $line Line number for error and notice usage. Related to import functionality.
 * @author Jithu Thomas
 */
function saveData($post,$table,$import=0,$importKeys=array(),$importVal=array(),$line=null, $extraData=array())
{
	global $now;
	global $db;
	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
			case 'therapeuticareas': $actual_table = "entities"; break;
			case 'investigator': $actual_table = "entities"; break;
			
		}
	if($import ==1 && $table=='redtags')
	{
		//post values are already escaped before coming here.\
		$existingRegTagName = array();
		
		$insertCnt = 0;
		$insertFailCnt = 0;
		$updateCnt = 0;
		$updateFailCnt = 0;
		$deleteCnt= 0;
		$deleteFailCnt= 0;
		$skipCnt = 0;
		$skipEnumCnt = 0;
		$invalidEnumSkipCnt = 0;
		$updateSkipCnt = 0;
		foreach($post as $redTagArray)
		{
			
			if(!in_array($redTagArray['type'], $extraData['redTagEnums']))
			{
				softDieSession('Invalid enum type used :'.implode(',', $redTagArray));
				$invalidEnumSkipCnt++;
				continue;
			}
			$typeWithoutQuotes = $redTagArray['type'];
			$redTagArray['name'] = "'".$redTagArray['name']."'";
			$redTagArray['type'] = "'".$redTagArray['type']."'";
			$name = $redTagArray['name'];
			$type = $redTagArray['type'];	
				
				
			//search for existing keys then reinsert it if type has changed
			$query = "select `name`,`type` from `$table` where `name`=$name limit 1";
			$result = mysql_query($query);
			$update = false;
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$existingRegTagName[] = $name;
				$currentType = $row['type'];
			}
			
			if($update)
			{
				//update
				if($currentType != $typeWithoutQuotes)
				{
					echo $currentType."---".$typeWithoutQuotes."---".$name."<br/>";
					if($actual_table<>'entities')
						$query = "update `$actual_table` set `name`=$name, `type`=$type where `name`=$name";
					else
						$query = "update `$actual_table` set `name`=$name, `class`=$type where `name`=$name";
					if(mysql_query($query))
					{
						$updateCnt++;
					}
					else
					{
						$updateFailCnt++;
						softDieSession('updating redtags failed.<br/>'.$query,0,1,'error');
					}
					
				}
				else
				{
					$updateSkipCnt++;
				}
			}
			else
			{
				//insert
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$redTagArray).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into `$actual_table` (".implode(',',$importKeys).") values (".implode(',',$redTagArray).")";
				}
				else
				{
					$query = "insert into `entities` (".implode(',',$importKeys)." ,class ) values (".implode(',',$redTagArray). ', "'. $class .'"' .")";
					if($table=='institutions') {	$query = str_replace("type", "category", $query); $query = str_replace("search_terms", "search_name", $query); }
				}
				if(mysql_query($query))
				{
					$insertCnt++;
				}
				else 
				{
					$insertFailCnt++;
					softDieSession('inserting redtags failed.<br/>'.$query);
				}
				$existingRegTagName[] = $name;
			}
		}
		
		//delete non existing redtags
		if(count($existingRegTagName)>0)
		{
			$query = "delete from $actual_table where `name` not in(".implode(',',$existingRegTagName).")";
			if(mysql_query($query))
			{
				$deleteCnt = mysql_affected_rows();
			}
			else 
			{
				$deleteFailCnt++;
				die('Deleting invalid redtags failed.<br/>'.$query);
			}
		}

		return array('insertCnt'=>$insertCnt, 'insertFailCnt'=>$insertFailCnt,'updateCnt'=>$updateCnt,'updateFailCnt'=>$updateFailCnt, 'deleteCnt'=>$deleteCnt, 'deleteFailCnt'=>$deleteFailCnt, 'updateSkipCnt'=>$updateSkipCnt, 'invalidEnumSkipCnt'=>$invalidEnumSkipCnt);
	}	
	
	if($import ==1 && $table=='upm')
	{
		$importVal = array_map(validateImport,$importKeys,$importVal);
		$query = "insert into upm (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
		if(mysql_query($query))
		{
			return true;
		}
		else
		{
			softDieSession('Cannot import row data at line '.$line.'<br/>');
			return false;
		}
		
	}
	if($import==1 && $table=='products')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$esclid = mysql_real_escape_string($importVal['LI_id']);
		$escname = mysql_real_escape_string($importVal['name']);
		$query = "select id,searchdata from products where LI_id='{$esclid}' OR name='{$escname}' limit 1";
		$result = mysql_query($query);
		$update = false;
		if($result)
		{
			ob_start();
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$id = $row['id'];
				$searchData = $row['searchdata'];
			}
			ob_end_clean();
			if($update)
			{
				if($importVal['is_active'] == 0)
				{
					//check if database has NULL searchdata and no UPM/MHM references.. in case of no linkages we can delete the product.
					//return false can show as failed attempt on higher level controller so return 4 code is meant as delete.
					$upmReferenceCount = count(getProductUpmAssociation($id));
					$MHMReferenceCount = getMHMAssociation($id);
					if($upmReferenceCount==0 && $MHMReferenceCount==0 && $searchData=='')
					{
						deleteData($id, $actual_table);
						return 4;
					}
				}			
				$importVal = array_map(am1,$importKeys,array_values($importVal));
				$query = "update $actual_table set ".implode(',',$importVal)." where id=".$id;
			}
			else 
			{
				//if insert check the product is_active. We dont need it in an import, skipping...
				if($importVal['is_active'] == 0)
				{
					//skipping.
					//return false can show as failed attempt on higher level controller.
					return 3;
				}
				
				$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
				
				
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$importKeys)."`,`class` ) values (".implode(',',$importVal). ', "'. $class .'"' .")";
					if($table=='institutions') {	$query = str_replace("type", "category", $query); $query = str_replace("search_terms", "search_name", $query); }
				}	
					
								
				
			}
			if(mysql_query($query))
			{
				if($id)
				{
					$_GET['id'] = $id;
					$ProdID = $id;
				}
				else 
				{
					$_GET['id'] = mysql_insert_id();
					$ProdID = $_GET['id'];
				}
				ob_start();
				require 'index_product.php';
				ob_end_clean();	
				
				//Insert product instituttion association
				ob_start();
				foreach($extraData['institutionIdsArray'] as $KeyInst=> $InstId)
				{
					$escInstlid = mysql_real_escape_string($InstId);
					$escInstname = mysql_real_escape_string($extraData['institutionNamesArray'][$KeyInst]);
					$Instquery = "select id from institutions where `LI_id`='{$escInstlid}' OR `name`='{$escInstname}' limit 1";
					$Instresult = mysql_query($Instquery);
					
					$InstPresent = false;
					if($Instresult)
					{
						while($Instrow = mysql_fetch_assoc($Instresult))
						{
							$InstPresent = true;
							$InstIdLocal = $Instrow['id'];
						}
						$InstAssoInsert = false;
						if($InstPresent)
						{
							//$InstAssocquery = "select institution from products_institutions where `institution`='{$InstIdLocal}' AND `product`='{$ProdID}' limit 1";
							$InstAssocquery = "select child from entity_relations where `child`='{$InstIdLocal}' AND `parent`='{$ProdID}' limit 1";
							$InstAssocresult = mysql_query($InstAssocquery);
							$InstAssocPresent = false;
							while($InstAssocrow = mysql_fetch_assoc($InstAssocresult))
							{
								$InstAssocPresent = true;
							}
							//if(!$InstAssocPresent) $InstAssoInsert = true;
							//making this always true so that it can delete existing associations and add the ones received from API.
							$InstAssoInsert = true;
						}
						else
						{
							require_once 'fetch_li_institutions.php';
							fetch_li_institution_individual($InstId);
							$Instquery_2 = "select id from institutions where `LI_id`='{$escInstlid}' OR `name`='{$escInstname}' limit 1";
							$Instresult_2 = mysql_query($Instquery_2);
							$InstPresent = false;
							while($Instrow_2 = mysql_fetch_assoc($Instresult_2))
							{
								$InstPresent = true;
								$InstIdLocal = $Instrow_2['id'];
							}
							if($InstPresent) $InstAssoInsert = true;
						}
						if($InstAssoInsert)
						{
							//$InstAssocInsertquery = "INSERT INTO products_institutions (`product`, `institution`) VALUES ('{$ProdID}','{$InstIdLocal}')";
							//first remove existing institution associations for this product
							$qry=" DELETE FROM entity_relations where parent = '". $ProdID ."' and child in 
								( select id from entities where class=\"Institution\") limit 10 ";
							$delok = mysql_query($qry);
							if(!$delok)
							{
								pr('Cannot remove old entity relations  <br> Query='.$qry.'<br>');
								pr(mysql_errno());
								pr(mysql_error());
								return false;
							}
							
							$InstAssocInsertquery = "INSERT INTO entity_relations values ('" .  $ProdID  . "', '" . $InstIdLocal . "')";
							$InstAssocInsertresult = mysql_query($InstAssocInsertquery);
							if(!$InstAssocInsertresult)
							{
								pr('Cannot update entity relations  <br>Query:'.$InstAssocInsertquery.'<br/>');
								pr(mysql_errno());
								pr(mysql_error());
								return false;
							}
							else
							{
								if(!mysql_query('COMMIT'))
								{
									$log='There seems to be a problem while committing the transaction. Error:' . mysql_error();
									$logger->error($log);
									mysql_query('ROLLBACK');
									echo $log;
									return false;
								}
							}
						}
						else  // delete the institution assocition if no institution id is given in the xml
						{
							$qry=" DELETE FROM entity_relations where parent = '". $ProdID ."' and child in 
								( select id from entities where class=\"Institution\") limit 10 ";
							$delok = mysql_query($qry);
							if(!$delok)
							{
								pr('Cannot remove old entity relations  <br> Query='.$qry.'<br>');
								pr(mysql_errno());
								pr(mysql_error());
								return false;
							}
						
						}
					}
					else
					{
						global $logger;
						$log 	= 'ERROR: Bad SQL query for institutions sync inside product sync. ' . $Instquery . mysql_error();
						$logger->error($log);
						unset($log);
					}
				}	
				ob_end_clean();	
				//End of Insert product institue association
			
				//Insert product Moa association
				ob_start();
				foreach($extraData['moaIdsArray'] as $KeyMoa=> $MoaId)
				{
					$escMoalid = mysql_real_escape_string($MoaId);
					$escMoaname = mysql_real_escape_string($extraData['moaNamesArray'][$KeyMoa]);
					$Moaquery = "select id from `entities` where `class`='MOA' and (`LI_id`='{$escMoalid}' OR `name`='{$escMoaname}') limit 1";
					$Moaresult = mysql_query($Moaquery);
					$MoaPresent = false;
					if($Moaresult)
					{
						while($Moarow = mysql_fetch_assoc($Moaresult))
						{
							$MoaPresent = true;
							$MoaIdLocal = $Moarow['id'];
						}
						$MoaAssoInsert = false;
						if($MoaPresent)
						{
							$MoaAssocquery = "select `parent` from `entity_relations` where `child`='{$MoaIdLocal}' AND `parent`='{$ProdID}' limit 1";
							$MoaAssocresult = mysql_query($MoaAssocquery);
							$MoaAssocPresent = false;
							while($MoaAssocrow = mysql_fetch_assoc($MoaAssocresult))
							{
								$MoaAssocPresent = true;
							}
							if(!$MoaAssocPresent) $MoaAssoInsert = true;
						}
						else
						{
							require_once 'fetch_li_moas.php';
							fetch_li_moa_individual($MoaId);
							$Moaquery_2 = "select id from `entities` where `class`='MOA' and (`LI_id`='{$escMoalid}' OR `name`='{$escMoaname}') limit 1";
							$Moaresult_2 = mysql_query($Moaquery_2);
							$MoaPresent = false;
							while($Moarow_2 = mysql_fetch_assoc($Moaresult_2))
							{
								$MoaPresent = true;
								$MoaIdLocal = $Moarow_2['id'];
							}
							if($MoaPresent) $MoaAssoInsert = true;
						}
						if($MoaAssoInsert)
						{
							$MoaAssocInsertquery = "INSERT INTO `entity_relations` (`parent`, `child`) VALUES ('{$ProdID}','{$MoaIdLocal}')";
							$MoaAssocInsertresult = mysql_query($MoaAssocInsertquery);
						}
					}
					else
					{
						global $logger;
						$log 	= 'ERROR: Bad SQL query for moa sync inside product sync. ' . $Moaquery . mysql_error();
						$logger->error($log);
						unset($log);
					}
				}
				ob_end_clean();	
				//End of Insert product Moa association
				
				return 1;
			}
			else
			{
				echo 'Product Id : '.$product_id.' Fail !! <br/>'."\n";
				softdie('Cannot import product id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
				//softdie('Cannot import product id '.$importVal['LI_id'].'<br/>');
				return 2;
			}
		}
		else
		{
			global $logger;
			$log 	= 'ERROR: Bad SQL query for MOA Sync. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
			softdie('Cannot import product id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
			return 2;
		}
	}
	
	if($import==1 && $table=='institutions')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$esclid = mysql_real_escape_string($importVal['LI_id']);
		$escname = mysql_real_escape_string($importVal['name']);
		$query = "select id from institutions where LI_id='{$esclid}' OR name='{$escname}' limit 1";
		$result = mysql_query($query);
		if($result)
		{
			$update = false;
			ob_start();
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$id = $row['id'];
			}
			ob_end_clean();
			if($update)
			{
				if($importVal['is_active'] == 0)
				{
					deleteData($id, $actual_table);
					return 4;
				}			
				$importVal = array_map(am1,$importKeys,array_values($importVal));
				$query = "update $actual_table set ".implode(',',$importVal)." where id=".$id;
			}
			else 
			{
				//if insert check the institution is_active. We dont need it in an import, skipping...
				if($importVal['is_active'] == 0)
				{
					//skipping.
					//return false can show as failed attempt on higher level controller.
					return 3;
				}
				
				$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
				
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$importKeys)."`,`class` ) values (".implode(',',$importVal). ', "'. $class .'"' .")";
					if($table=='institutions') {	$query = str_replace("type", "category", $query); $query = str_replace("search_terms", "search_name", $query); }
				}	
				
				
			}
			if(mysql_query($query))
			{
				if($id)
				{
					$_GET['id'] = $id;
				}
				else 
				{
					$_GET['id'] = mysql_insert_id();
				}
				return 1;
			}
			else
			{
				echo 'Institution Id : '.$importVal['LI_id'].' Fail !! <br/>'."\n";
				softdie('Cannot import institution id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
				//softdie('Cannot import product id '.$importVal['LI_id'].'<br/>');
				return 2;
			}
		}
		else
		{
			global $logger;
			$log 	= 'ERROR: Bad SQL query for Institution sync. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
			echo 'Institution Id : '.$importVal['LI_id'].' Fail !! <br/>'."\n";
			return 2;
		}
	}
	
	if($import==1 && $table=='moas')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$esclid = mysql_real_escape_string($importVal['LI_id']);
		$escname = mysql_real_escape_string($importVal['name']);
		$query = "select id from `entities` where `class`='MOA' and (LI_id='{$esclid}' OR name='{$escname}') limit 1";
		$result = mysql_query($query);
		$update = false;
		if($result)
		{
			ob_start();
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$id = $row['id'];
			}
			ob_end_clean();
			if($update)
			{
				if($importVal['is_active'] == 0)
				{
					deleteData($id, 'entities');
					return 4;
				}			
				$importVal = array_map(am1,$importKeys,array_values($importVal));
				$query = "update `entities` set ".implode(',',$importVal)." where id=".$id;
			}
			else 
			{
				//if insert check the moa is_active. We dont need it in an import, skipping...
				if($importVal['is_active'] == 0)
				{
					//skipping.
					//return false can show as failed attempt on higher level controller.
					return 3;
				}
				
				$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
				
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$importKeys)."`,`class` ) values (".implode(',',$importVal). ', "'. $class .'"' .")";
				}	
				
				
				
			}
			if(mysql_query($query))
			{
				if($id)
				{
					$_GET['id'] = $id;
					$MOAID = $id;
				}
				else 
				{
					$_GET['id'] = mysql_insert_id();
					$MOAID = $_GET['id'];
				}
				
				//Insert MOA and MOA Category association
				ob_start();
				foreach($extraData['MoaCategoryIdsArray'] as $KeyMoaCat=> $MoaCatId)
				{
					$escMoaCatlid = mysql_real_escape_string($MoaCatId);
					$MoaCatquery = "select id from `entities` where `LI_id`='{$escMoaCatlid}' and `class`='MOA_Category' limit 1";
					$MoaCatresult = mysql_query($MoaCatquery);
					$MoaCatPresent = false;
					if($MoaCatresult)
					{
						while($MoaCatrow = mysql_fetch_assoc($MoaCatresult))
						{
							$MoaCatPresent = true;
							$MoaCatIdLocal = $MoaCatrow['id'];
						}
						$MoaCatAssoInsert = false;
						if($MoaCatPresent)
						{
							$MoaCatAssocquery = "select `parent` from `entity_relations` where `parent`='{$MoaCatIdLocal}' AND `child`='{$MOAID}' limit 1";
							$MoaCatAssocresult = mysql_query($MoaCatAssocquery);
							$MoaCatAssocPresent = false;
							while($MoaCatAssocrow = mysql_fetch_assoc($MoaCatAssocresult))
							{
								$MoaCatAssocPresent = true;
							}
							if(!$MoaCatAssocPresent) $MoaCatAssoInsert = true;
						}
						else
						{
							require_once 'fetch_li_moacategories.php';
							fetch_li_moacategory_individual($MoaCatId);
							$MoaCatquery_2 = "select id from `entities` where `LI_id`='{$escMoaCatlid}' and `class`='MOA_Category' limit 1";
							$MoaCatresult_2 = mysql_query($MoaCatquery_2);
							$MoaCatPresent = false;
							while($MoaCatrow_2 = mysql_fetch_assoc($MoaCatresult_2))
							{
								$MoaCatPresent = true;
								$MoaCatIdLocal = $MoaCatrow_2['id'];
							}
							if($MoaCatPresent) $MoaCatAssoInsert = true;
						}
						if($MoaCatAssoInsert)
						{
							$MoaCatAssocInsertquery = "INSERT INTO `entity_relations` (`parent`, `child`) VALUES ('{$MoaCatIdLocal}','{$MOAID}')";
							$MoaCatAssocInsertresult = mysql_query($MoaCatAssocInsertquery);
						}
					}
					else
					{
						global $logger;
						$log 	= 'ERROR: Bad SQL query for Moa Category sync inside moa sync. ' . $MoaCatquery . mysql_error();
						$logger->error($log);
						unset($log);
					}
				}
				ob_end_clean();	
				//End of Insert product MOA and MOA Category association
				
				return 1;
			}
			else
			{
				echo 'Moa Id : '.$importVal['LI_id'].' Fail !! <br/>'."\n";
				softdie('Cannot import moa id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
				//softdie('Cannot import product id '.$importVal['LI_id'].'<br/>');
				return 2;
			}
		}
		else
		{
			global $logger;
			$log 	= 'ERROR: Bad SQL query for MOA Sync. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
			softdie('Cannot import moa id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
			return 2;
		}
	}
	
	if($import==1 && $table=='moacategories')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$esclid = mysql_real_escape_string($importVal['LI_id']);
		$escname = mysql_real_escape_string($importVal['name']);
		$query = "select id from `entities` where `class`='MOA_Category' and (LI_id='{$esclid}' OR name='{$escname}') limit 1";
		$result = mysql_query($query);
		$update = false;
		if($result)
		{
			ob_start();
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$id = $row['id'];
			}
			ob_end_clean();
			if($update)
			{
				if($importVal['is_active'] == 0)
				{
					deleteData($id, 'entities');
					return 4;
				}			
				$importVal = array_map(am1,$importKeys,array_values($importVal));
				$query = "update `entities` set ".implode(',',$importVal)." where id=".$id;
			}
			else 
			{
				//if insert check the moa is_active. We dont need it in an import, skipping...
				if($importVal['is_active'] == 0)
				{
					//skipping.
					//return false can show as failed attempt on higher level controller.
					return 3;
				}
				
				$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
				
				
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$importKeys)."`,`class` ) values (".implode(',',$importVal). ', "'. $class .'"' .")";
					if($table=='institutions') {	$query = str_replace("type", "category", $query); $query = str_replace("search_terms", "search_name", $query); }
				}	
				
				
			}
			if(mysql_query($query))
			{
				if($id)
				{
					$_GET['id'] = $id;
				}
				else 
				{
					$_GET['id'] = mysql_insert_id();
				}
				return 1;
			}
			else
			{
				echo 'Moa category Id : '.$importVal['LI_id'].' Fail !! <br/>'."\n";
				softdie('Cannot import moa category id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
				//softdie('Cannot import product id '.$importVal['LI_id'].'<br/>');
				return 2;
			}
		}
		else
		{
			global $logger;
			$log 	= 'ERROR: Bad SQL query for MOA Category Sync. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
			softdie('Cannot import moa category id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
			return 2;
		}
	}
	
	if($import==1 && $table=='diseases')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$esclid = mysql_real_escape_string($importVal['LI_id']);
		$escname = mysql_real_escape_string($importVal['name']);
		$query = "select id from `entities` where `class`='Disease' and (LI_id='{$esclid}' OR name='{$escname}') limit 1";
		$result = mysql_query($query);
		$update = false;
		if($result)
		{
			ob_start();
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$id = $row['id'];
			}
			ob_end_clean();
			if($update)
			{
				if($importVal['is_active'] == 0)
				{
					deleteData($id, 'entities');
					return 4;
				}			
				$importVal = array_map(am1,$importKeys,array_values($importVal));
				$query = "update `entities` set ".implode(',',$importVal)." where id=".$id;
			}
			else 
			{
				//if insert check the moa is_active. We dont need it in an import, skipping...
				if($importVal['is_active'] == 0)
				{
					//skipping.
					//return false can show as failed attempt on higher level controller.
					return 3;
				}
				
				$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
				
				
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$importKeys)."`,`class` ) values (".implode(',',$importVal). ', "'. $class .'"' .")";
					if($table=='institutions') {	$query = str_replace("type", "category", $query); $query = str_replace("search_terms", "search_name", $query); }
				}	
				
				
			}
			if(mysql_query($query))
			{
				if($id)
				{
					$_GET['id'] = $id;
				}
				else 
				{
					$_GET['id'] = mysql_insert_id();
				}
				return 1;
			}
			else
			{
				echo 'Disease Id : '.$importVal['LI_id'].' Fail !! <br/>'."\n";
				softdie('Cannot import disease id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
				return 2;
			}
		}
		else
		{
			global $logger;
			$log 	= 'ERROR: Bad SQL query for Disease Sync. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
			softdie('Cannot import disease id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
			return 2;
		}
	}
	
	if($import==1 && $table=='therapeuticareas')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$esclid = mysql_real_escape_string($importVal['LI_id']);
		$escname = mysql_real_escape_string($importVal['name']);
		$query = "select id from `entities` where `class`='Therapeutic_Area' and (LI_id='{$esclid}' OR name='{$escname}') limit 1";
		$result = mysql_query($query);
		$update = false;
		if($result)
		{
			ob_start();
			while($row = mysql_fetch_assoc($result))
			{
				$update = true;
				$id = $row['id'];
			}
			ob_end_clean();
			if($update)
			{
				if($importVal['is_active'] == 0)
				{
					deleteData($id, 'entities');
					return 4;
				}			
				$importVal = array_map(am1,$importKeys,array_values($importVal));
				$query = "update `entities` set ".implode(',',$importVal)." where id=".$id;
			}
			else 
			{
				//if insert check the moa is_active. We dont need it in an import, skipping...
				if($importVal['is_active'] == 0)
				{
					//skipping.
					//return false can show as failed attempt on higher level controller.
					return 3;
				}
				
				$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
				
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
					case 'therapeuticareas': $class = "Therapeutic_Area"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into `entities` (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$importKeys)."`) values (".implode(',',$importVal).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$importKeys)."`,`class` ) values (".implode(',',$importVal). ', "'. $class .'"' .")";
				}	
				
				
				
			}
			if(mysql_query($query))
			{
				if($id)
				{
					$_GET['id'] = $id;
					$TherapeuticArea_ID = $id;
				}
				else 
				{
					$_GET['id'] = mysql_insert_id();
					$TherapeuticArea_ID = $_GET['id'];
				}
				
				//Insert Therapeutic Area and Disease association
				ob_start();
				$SkipDiseaseArray = array();
				foreach($extraData['DiseaseIdsArray'] as $KeyDisease=> $DiseaseId)
				{
					$escDiseaselid = mysql_real_escape_string($DiseaseId);
					$Diseasequery = "select id from `entities` where `LI_id`='{$escDiseaselid}' and `class`='Disease' limit 1";
					$Diseaseresult = mysql_query($Diseasequery);
					$DiseasePresent = false;
					if($Diseaseresult)
					{
						while($Diseaserow = mysql_fetch_assoc($Diseaseresult))
						{
							$DiseasePresent = true;
							$DiseaseIdLocal = $Diseaserow['id'];
						}
						$DiseaseAssoInsert = false;
						if($DiseasePresent)
						{
							$DiseaseAssocquery = "select `parent` from `entity_relations` where `parent`='{$TherapeuticArea_ID}' AND `child`='{$DiseaseIdLocal}' limit 1";
							$DiseaseAssocresult = mysql_query($DiseaseAssocquery);
							$DiseaseAssocPresent = false;
							while($DiseaseAssocrow = mysql_fetch_assoc($DiseaseAssocresult))
							{
								$DiseaseAssocPresent = true;
							}
							if(!$DiseaseAssocPresent) $DiseaseAssoInsert = true;
						}
						else
						{
							//IF DISEASE IS NOT PRESENT IN DB JUST PUT IT IN SKIPP DISEASE ARRAY OF THAT TA
							$SkipDiseaseArray[] = $DiseaseId;
						}
						if($DiseaseAssoInsert)
						{
							$DiseaseAssocInsertquery = "INSERT INTO `entity_relations` (`parent`, `child`) VALUES ('{$TherapeuticArea_ID}','{$DiseaseIdLocal}')";
							$DiseaseAssocInsertresult = mysql_query($DiseaseAssocInsertquery);
						}
					}
					else
					{
						global $logger;
						$log 	= 'ERROR: Bad SQL query for Disease sync inside Therapeutic Area sync. ' . $Diseasequery . mysql_error();
						$logger->error($log);
						unset($log);
					}
				}
				
				if(count($SkipDiseaseArray) > 0)
				{
					global $logger;
					$log 	= 'WARN: '. count($SkipDiseaseArray) .' Diseases skipped inside Therapeutic Area sync of having LI ID '.$importVal['LI_id'].'. Following disease skipped( '. implode(',',$SkipDiseaseArray) .' )';
					$logger->error($log);
					unset($log);
				}
				ob_end_clean();	
				//End of Insert product MOA and MOA Category association
				
				return 1;
			}
			else
			{
				echo 'Therapeutic Area Id : '.$importVal['LI_id'].' Fail !! <br/>'."\n";
				softdie('Cannot import Therapeutic Area Id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
				//softdie('Cannot import product id '.$importVal['LI_id'].'<br/>');
				return 2;
			}
		}
		else
		{
			global $logger;
			$log 	= 'ERROR: Bad SQL query for Therapeutic Area Sync. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
			softdie('Cannot import Therapeutic Area Id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
			return 2;
		}
	}
	
	if(isset($post['delsearch']) && is_array($post['delsearch']))
	{
		foreach($post['delsearch'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			if($db->user->userlevel != 'user')
			{
				$post['searchdata'] = '';
				$q = "UPDATE $actual_table SET searchdata=null WHERE id=$id LIMIT 1";
				mysql_query($q) or softDieSession('Bad SQL query deleting searchdata');
			}
		}
		unset($post['delsearch']);
	}
	if(isset($post['deleteId']))
	{
		unset($post['deleteId']);
	}
	
	$id = ($post['id'])?$post['id']:null;	
	//precheck before attepting to insert/update
	if($table=='products' || $table=='areas'  || $table=='diseases')
	{
		if(isset($post['searchdata']) && $post['searchdata']!='')
		{
			require_once 'searchhandler.php';
			$stat = json_decode(testQuery(1,1,$post['searchdata']));
			if($stat->status==0)
			{
				softDieSession('Cannot insert '.$actual_table.' entry. Search data corrupt!<br/>'.$stat->message);
				return 0;
			}
		}
	}

	//end precheck
	//column input filtering
	
	$columnList = tableColumns($table);
	
	//start bool checkbox filtering 
	//TODO: make dynamic with tablecolumndetails & bool tinyint(1) type filtering
	if($table == 'areas')
	{
		if(!isset($post['coverage_area']))
		{
			$post['coverage_area'] = 0;
		}
	}
	//end bool checkbox filtering
	
	$post = array_intersect_key($post, array_flip($columnList));
	if(!$id)//insert
	{
		//no longer required as input column filtering is in place now. unset($post['save']);
		if($table=='upm')
		{	
			$post['last_update'] = 	date('Y-m-d',$now);
			if(is_array($post['area']) && count($post['area'])>0)
			{		
				$upm_area = $post['area'];
				unset($post['area']);
			}
			
			if(is_array($post['larvol_id']) && count($post['larvol_id'])>0)
			{		
				$upm_larvolids = $post['larvol_id'];
				$upm_larvolids = array_filter($upm_larvolids);
				unset($post['larvol_id']);
			}
		}
		
		if($table=='products' && $post['name']=='')
		{
			softDieSession('Cannot insert '.$table.' entry. Product name cannot empty.');
			return 0;
		}
		$postKeys = array_keys($post);
		$post = array_map(am,$postKeys,array_values($post));
		$newpostKeys=$postKeys;
		$newpost=$post;
		if($table=='upm' && array_search('area', $postKeys) )
		{
			$areaKey=array_search('area', $postKeys);
			unset($newpostKeys[$areaKey]);
			unset($newpost[$areaKey]);
		}
		
		if($table=='upm' && array_search('larvol_id', $postKeys) )
		{
			$LarvolIdKey=array_search('larvol_id', $postKeys);
			unset($newpostKeys[$LarvolIdKey]);
			unset($newpost[$LarvolIdKey]);
		}
		
				
				switch($table)
				{
					case 'products': $class = "Product"; break;
					case 'areas': $class = "Area"; break;
					case 'institutions': $class = "Institution"; break;
					case 'moas': $class = "MOA"; break;
					case 'moacategories': $class = "MOA_Category"; break;
					case 'diseases': $class = "Disease"; break;
				}
				if($table=='investigator') return false;
				if( $table == 'moacategories' or $table == 'moas'  )
				{
					$query = "insert into entities (`".implode('`,`',$newpostKeys)."`) values (".implode(',',$newpost).")";
				}
				elseif( empty($class) )
				{
					$query = "insert into $actual_table (`".implode('`,`',$newpostKeys)."`) values (".implode(',',$newpost).")";
				}
				else
				{
					$query = "insert into `entities` (`".implode('`,`',$newpostKeys)."`,`class` ) values (".implode(',',$newpost). ', "'. $class .'"' .")";
					if($table=='institutions') {	$query = str_replace("type", "category", $query); $query = str_replace("search_terms", "search_name", $query); }
				}	
			
		
		if(mysql_query($query))
		{
			if($table=='products')
			{
				$_REQUEST['id'] = mysql_insert_id();
			}
			if($table=='areas'  || $table=='diseases')
			{
				$_REQUEST['id'] = mysql_insert_id();
			}
			
			if($table=='upm')
			{
				$InsertPnt = mysql_insert_id();
				fillUpmAreas($InsertPnt,$upm_area);
				fillUpmLarvolIDs($InsertPnt,$upm_larvolids);
				//Insert new upm records history
				$newHistory = array('id'=>$InsertPnt, 'change_date'=>"'".date('Y-m-d H:i:s')."'", 'field'=>"'".'new'."'", 'old_value'=>"''", 'new_value'=>"''", 'user'=>"'".$db->user->id."'");
				$newHistoryquery = "insert into upm_history (`".implode('`,`',array_keys($newHistory))."`) values (".implode(',',$newHistory).")";
				mysql_query($newHistoryquery)or softdieSession('Cannot update history for upm id '.$InsertPnt);

			}
			return 1;
		}
		else
		{
			$merr = mysql_error();
			softDieSession('Cannot insert '.$table.' entry '. $merr );
			return 0;
		}
	}
	else//update
	{
		//pr($post);die;
		if($table=='upm')
		{
			
			
			//the area data is stored in temporary array to be processed after update
			if(is_array($post['area']) && count($post['area'])>0)
			{		
				$upm_area = $post['area'];
				unset($post['area']);
			}
			
			if(is_array($post['larvol_id']) && count($post['larvol_id'])>0)
			{		
				$upm_larvolids = $post['larvol_id'];
				$upm_larvolids = array_filter($upm_larvolids);
				unset($post['larvol_id']);
			}
			
			$query = "select * from $table where id=$id";
			$res = mysql_query($query)or softDieSession('Updating invalid row');
			while($row = mysql_fetch_assoc($res))
			{
				$historyArr = $row;
			}
			//fetch previous upm area string.
			$upmHistoryAreaString = getUpmAreaNames($id);
			//Put previous larvol id
			$upmHistoryLarvolIDs = getUpmLarvolIDs($id);
			//last update not needed for upm_history
			unset($historyArr['last_update']);
			unset($historyArr['status']);
			unset($historyArr['event_type']);
			//remove post action name from insert query.
			//no longer required as input column filtering is in place now. unset($post['save']);
			global $post_tmp;
			$post_tmp = $post;
			$historyArr = array_diff_assoc($historyArr,$post);
			$historyArr = array_map(function($a,$b){
				global $post_tmp;
				global $now;
				global $db;
				$change_date = date('Y-m-d H:i:s',$now);
				return array('id'=>$post_tmp['id'],'change_date'=>"'".$change_date."'",'field'=>"'".$a."'",'old_value'=>"'".mysql_real_escape_string($b)."'",'new_value'=>"'".mysql_real_escape_string($post_tmp[$a])."'",'user'=>$db->user->id);
				},array_keys($historyArr),$historyArr);
			unset($post_tmp);
				
			//changed nowarray_pop($post);	
			$post['last_update'] = date('Y-m-d',$now);
			
			$newpostKeys=array_keys($post);
			$newpost=array_values($post);
			if($table=='upm' && array_search('area', $newpostKeys) )
			{
				$areaKey=array_search('area', $newpostKeys);
				unset($newpostKeys[$areaKey]);
				unset($newpost[$areaKey]);
			}
			
			if($table=='upm' && array_search('larvol_id', $newpostKeys) )
			{
				$LarvolIdKey=array_search('larvol_id', $newpostKeys);
				unset($newpostKeys[$LarvolIdKey]);
				unset($newpost[$LarvolIdKey]);
			}			

			$postnew = array_map(am1,$newpostKeys,$newpost);
			$post = array_map(am1,array_keys($post),array_values($post));
		}
		else
		{
			//remove post action name from insert query.
			unset($post['save']);
			if($table=='products' && $post['name']=='')
			{
				softDieSession('Cannot update '.$table.' entry. Product name cannot empty.');
				return 0;
			}			
			$post = array_map(am1,array_keys($post),array_values($post));
			//pr($post);//die;
		}
		if($table<>'upm') $postnew = $post;

		// Fix for the issue Duplicate entry '' for key 'investigatorname'
		if($_REQUEST['entity'] and $_REQUEST['entity']<>'investigator')
		{
			if($postnew['first_name'])
				unset $postnew['first_name'];
			if($postnew['surname'])
				unset $postnew['surname'];
		}
		//
		
		$query = "update $actual_table set ".implode(',',$postnew)." where id=".$id;
		if(mysql_query($query))
		{
			//fire success actions upon successful save.
			if($table=='upm')
			{
				
				//update upm area one to many association
				fillUpmAreas($id,$upm_area);
				fillUpmLarvolIDs($id,$upm_larvolids);
				
				//process upm history
				foreach($historyArr as $history)
				{
					/* Replace product id and redtag id by name while storing in history table*/
					if($history["field"] == "'product'")
					{
						$history["old_value"] = "'".getUPMProdOrRedtagName("products", $history["old_value"])."'";
						$history["new_value"] = "'".getUPMProdOrRedtagName("products", $history["new_value"])."'"; 
					}
					else if($history["field"] == "'redtag'")
					{
						$history["old_value"] = "'".getUPMProdOrRedtagName("redtags", $history["old_value"])."'";
						$history["new_value"] = "'".getUPMProdOrRedtagName("redtags", $history["new_value"])."'"; 
					}
					
					$query = "insert into upm_history (`".implode('`,`',array_keys($history))."`) values (".implode(',',$history).")";
					mysql_query($query)or softdieSession('Cannot update history for upm id '.$historyArr['id']);
				}
				$upmCurrentAreaString = getUpmAreaNames($id);	
				if($upmHistoryAreaString != $upmCurrentAreaString)
				{
					
					$history = array('id'=>$id, 'change_date'=>"'".date('Y-m-d H:i:s')."'", 'field'=>"'".'area'."'", 'old_value'=>"'".$upmHistoryAreaString."'", 'new_value'=>"'".$upmCurrentAreaString."'", 'user'=>"'".$db->user->id."'");
					$query = "insert into upm_history (`".implode('`,`',array_keys($history))."`) values (".implode(',',$history).")";
					mysql_query($query)or softdieSession('Cannot update history for upm id '.$id);
				}
				
				$upmCurrentLarvolIDs = getUpmLarvolIDs($id);	
				if($upmHistoryLarvolIDs != $upmCurrentLarvolIDs)
				{
					
					$history = array('id'=>$id, 'change_date'=>"'".date('Y-m-d H:i:s')."'", 'field'=>"'".'larvol_id'."'", 'old_value'=>"'".$upmHistoryLarvolIDs."'", 'new_value'=>"'".$upmCurrentLarvolIDs."'", 'user'=>"'".$db->user->id."'");
					$query = "insert into upm_history (`".implode('`,`',array_keys($history))."`) values (".implode(',',$history).")";
					mysql_query($query)or softdieSession('Cannot update history for upm id '.$id);
				}
			}
			
			return 1;			
		}
		else
		{
			softDieSession('Cannot update '.$table.mysql_error().'<br/>'.$query.' entry');	
			return 0;	
		}
		
	}
}

/**
 * @name pagePagination
 * @tutorial Provides pagination output for the upm input page.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function fillUpmAreas($upmId,$areaIds=array())
{

//	if(!is_array($areaIds))	return false;
	global $db;
	//get current upm areas
	$query = "select * from `upm_areas` where `upm_id`=$upmId";
	$result = mysql_query($query);
	$currentAreas = array();
	while($row = mysql_fetch_assoc($result))
	{
		$currentAreas[] = $row['area_id'];
	}
	//add new areas
	$newAreas = @array_diff($areaIds,$currentAreas);
	if(count($newAreas)>0)
	{
		$newAreas = array_map(function($upm_id,$area_id){
			
			return "(".$upm_id.",".$area_id.")";
			
		},array_fill(0, (count($newAreas)), $upmId),$newAreas);
		
		$insertAreasQuery = "insert into `upm_areas` (upm_id,area_id) values ".implode(',',$newAreas);
		
		if(!mysql_query($insertAreasQuery))
		{
			softDieSession('Cannot insert new areas into upm_areas table');
		}
	}
	//delete old areas
	$deletedAreas = @array_diff($currentAreas,$areaIds);
	if(count($currentAreas)>0 and empty($areaIds))
		$deletedAreas=$currentAreas;
	if(count($deletedAreas)>0)
	{
		$deletedAreasQuery = "delete from `upm_areas` where `upm_id`=$upmId and `area_id` in (".implode(',',$deletedAreas).")";
		if(!mysql_query($deletedAreasQuery))
		{
			softDieSession('Cannot delete areas from upm_areas table');
		}		
	}
}

/*
 * Fill new larvol ids in database
 * Replace sourceID by larvolID if any in input
 */
function fillUpmLarvolIDs($upmId,$LarvolIDs=array())
{

	global $db, $logger;
	//get current upm larvolids
	$query = "select * from `upm_trials` where `upm_id`=$upmId";
	$result = mysql_query($query);
	$currentLarvolIDs = array();
	while($row = mysql_fetch_assoc($result))
	{
		$currentLarvolIDs[] = $row['larvol_id'];
	}
	//add new larvol ids
	///Replace source id by larvol id if any
	$TempLarvolIDs =  array();
	
	if(count($LarvolIDs) > 0)
	{
		foreach($LarvolIDs as $key=> $IDs)
		{
			if(strpos(" ".$IDs." ", "NCT") || strpos(" ".$IDs." ", "-"))
			{
				$SourceIDQuery = "select larvol_id from `data_trials` where `source_id` LIKE '%".mysql_real_escape_string($IDs)."%'";
				$SourceIDQueryRes = mysql_query($SourceIDQuery);
				if($SourceIDQueryRes)
				{
					while($LarvolIDfrmSrcArray = mysql_fetch_assoc($SourceIDQueryRes))
					$LarvolIDfrmSrc = $LarvolIDfrmSrcArray['larvol_id'];
					if($LarvolIDfrmSrc != NULL && $LarvolIDfrmSrc != '')
					$TempLarvolIDs[] = $LarvolIDfrmSrc;
				}
				else
				{
					$log 	= 'ERROR: Bad SQL query. ' . $SourceIDQuery . mysql_error();
					$logger->error($log);
					unset($log);
				}
			}
			else
			{
				$TempLarvolIDs[] = $IDs;
			}
		}
	}
	$LarvolIDs = $TempLarvolIDs;
	
	$newLarvolIDs = @array_diff($LarvolIDs,$currentLarvolIDs);
	if(count($newLarvolIDs)>0)
	{
		$newLarvolIDs = array_map(function($upm_id,$larvol_id){
			
			return "(".$upm_id.",".$larvol_id.")";
			
		},array_fill(0, (count($newLarvolIDs)), $upmId),$newLarvolIDs);
		
		$insertLarvolIDsQuery = "insert into `upm_trials` (upm_id,larvol_id) values ".implode(',',$newLarvolIDs);
		
		if(!mysql_query($insertLarvolIDsQuery))
		{
			softDieSession('Cannot insert new larvol id into upm_trials table');
		}
	}
	//delete old areas
	$deletedLarvolIDs = @array_diff($currentLarvolIDs,$LarvolIDs);
	if(count($currentLarvolIDs)>0 and empty($LarvolIDs))
		$deletedLarvolIDs=$currentLarvolIDs;
	if(count($deletedLarvolIDs)>0)
	{
		$deletedLarvolIDsQuery = "delete from `upm_trials` where `upm_id`=$upmId and `larvol_id` in (".implode(',',$deletedLarvolIDs).")";
		if(!mysql_query($deletedLarvolIDsQuery))
		{
			softDieSession('Cannot delete larvol ids from upm_trials table');
		}		
	}
}


/**
 * @name pagePagination
 * @tutorial Provides pagination output for the upm input page.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function pagePagination($limit,$totalCount,$table,$script,$ignoreFields=array(),$options=array('import'=>true,'searchDataCheck'=>false,'search'=>true,'add_new_record'=>true),$entity=null)
{ 
        $addEdit_flag = (isset($options['addEdit_flag'])) ? $options['addEdit_flag'] : FALSE;
	global $page;	
	$actual_table=$table;
	switch($table)
	{
		case 'products': $actual_table = "entities"; break;
		case 'areas': $actual_table = "entities"; break;
		case 'institutions': $actual_table = "entities"; break;
		case 'moas': $actual_table = "entities"; break;
		case 'moacategories': $actual_table = "entities"; break;
		case 'diseases': $actual_table = "entities"; break;
		case 'investigator': $actual_table = "entities"; break;
	}
	
	$formOnSubmit = isset($options['formOnSubmit'])?$options['formOnSubmit']:null;
	if(isset($_GET['next']))
	$page = $_GET['oldval']+1;
	elseif(isset($_GET['back']))
	$page = $_GET['oldval']-1;	
	elseif(isset($_GET['jump']))
	$page = $_GET['jumpno']-1;
	$visualPage = $page+1;
	$maxPage = ceil($totalCount/$limit);
	if($entity) $_GET['entity']=$entity;
	$oldVal = $page;
		
	$pend  = ($visualPage*$limit)<=$totalCount?$visualPage*$limit:$totalCount;
	$pstart = (($pend - $limit+1)>0)?$pend - $limit+1:0;
	
	echo '<form name="pager" method="get" '.$formOnSubmit.' action="'.$script.'.php"><fieldset class="floatl">'
		 	. '<legend>Page ' . $visualPage . ' of '.$maxPage
			. ': records '.$pstart.'-'.$pend.' of '.$totalCount
			. '</legend>'
			. '<input name="entity" type="hidden" value="' . $_REQUEST['entity'] . '" />'
			. '<input name="mesh_display" type="hidden" value="' . $_REQUEST['mesh_display'] . '" />'
			. '<input type="submit" name="jump" value="Jump" style="width:0;height:0;border:0;padding:0;margin:0;"/> '
			. '<input name="page" type="hidden" value="' . $page . '" /><input name="search" type="hidden" value="1" />'
			. ($pstart > 1 ? '<input type="submit" name="back" value="&lt; Back"/>' : '')
			. ' <input type="text" name="jumpno" value="' . $visualPage . '" size="6" />'
			. '<input type="submit" name="jump" value="Jump" /> '
			. ($visualPage<$maxPage?'<input type="submit" name="next" value="Next &gt;" />':'')
			. '<input type="hidden" value="'.$oldVal.'" name="oldval">'
			. '</fieldset>';
	echo '<fieldset class="floatl">';
	echo '<legend> Actions: </legend>';
	if(isset($options['add_new_record']) && $options['add_new_record']!==false)
	{
     //       if($addEdit_flag == TRUE) { 
                echo '<input type="submit" value="Add New Record" name="add_new_record">';
     //       }
	}
		if($options['import'])
		echo '<input type="submit" value="Import" name="import">';
		echo '</fieldset>';
	//$_SESSION['page_errors'] = array('Sql error','What is this error.');
	if(isset($_SESSION['page_errors']) && is_array($_SESSION['page_errors']) && count($_SESSION['page_errors'])>0)
	{
		echo '<fieldset class="floatl">';
		echo '<legend> Errors: </legend>';
		echo '<ul>';
		foreach($_SESSION['page_errors'] as $err)
		{
			echo '<li class="error">'.$err.'</li>';
		}
		echo '</ul>';
		unset($_SESSION['page_errors']);
		echo '</fieldset>';
	}
	echo '<br/>';
	if(isset($options['search']) && $options['search'] == true):
				
	echo  '<fieldset class="">'
			. '<legend> Search: </legend>';

	echo '<table>';
	if($options['searchDataCheck'])
	{
		$searchDataCheckStatus = (isset($_GET['searchDataCheck']))?$_GET['searchDataCheck']:null;
		echo '<tr>';
		echo '<td>Search Data : </td><td>'.input_tag(array('Type'=>'checkbox','Field'=>'searchDataCheck'),$searchDataCheckStatus,array('alttitle'=>'Search for records having search data.')).'</td>';
		echo '</tr>';
	}
	
	$res = tableColumnDetails($table);
	//while($row = mysql_fetch_assoc($res))
	//TODO:INJECT UPM.AREAS
	/**
	 * Array
	 (
	 [Field] => area
	 [Type] => int(10) unsigned
	 [Null] => YES
	 [Key] => MUL
	 [Default] =>
	 [Extra] =>
	 )
	 *
	 */		
	
	//TODO: make it dynamic for future one to many relationship and custom non existing columns
	if($table == 'upm')
	{
		$res[] = array(
				
				 'Field' => 'area',
				 'Type' => 'int(10) unsigned',
				 'Null' => 'YES',
				 'Key' => 'MUL',
				 'Default' => '',
				 'Extra' => '',
				
				);
		$res[] = array(
	
				'Field' => 'larvol_id',
				'Type' => 'int(10) unsigned',
				'Null' => 'YES',
				'Key' => 'MUL',
				'Default' => '',
				'Extra' => '',
	
		);
	
		$res = ArrangeTableColumns($res, array('redtag'=>2));
	}
	foreach($res as $row)
	{
		//pr($row);
		if(!in_array($row['Field'],$ignoreFields))
		{
			$dbVal = null;
			if(isset($_GET) && isset($_GET['search_'.$row['Field']]) && $_GET['search_'.$row['Field']] !='' && !isset($_GET['reset']))
			{
				$dbVal = $_GET['search_'.$row['Field']];
			}
			
			///default
			if($row['Field']!='larvol_id' || $table!='upm')
			{
				echo '<tr><td>'.ucwords(implode(' ',explode('_',$row['Field']))) .' : </td><td>'.input_tag($row,$dbVal,array('null_options'=>true,'name_index'=>'search', 'look_for_bool'=>true)).'</td></tr>';
			}
			else if($row['Field']=='larvol_id' && $table=='upm')
			{
				echo '<tr>';
				echo '<td>Trial ID : </td><td><input type="text" value="" name="search_'.$row['Field'].'[]" id="search_'.$row['Field'].'[]" /> <img style="border:0; height:20px; width:20px; vertical-align:middle;" title="Add Trial Id" alt="Add Trial Id" src="images/add.gif" class="add_multiple_larvol_id"></td>';
				echo '</tr>';

			}
		
			if($row['Field']=='larvol_id' && $table=='upm' && is_array($dbVal) && count($dbVal)>0)
			{
				foreach($dbVal as $key=>$dbValIndividual)
				{
					if($dbValIndividual != NULL && $dbValIndividual != '')
					print '<tr><td></td><td><input name="search_'.$row['Field'].'[]" value="'.$dbValIndividual.'" checked="checked" type="checkbox"> <font title="Larvol Id">'.$dbValIndividual.'</font>'.((getUpmSourceIDFrmLarvolIDs($dbValIndividual) != NULL && getUpmSourceIDFrmLarvolIDs($dbValIndividual) != '') ? ' <font title="Source Id">['.implode("] [",explode("`",getUpmSourceIDFrmLarvolIDs($dbValIndividual))).']</font>':'').' <img style="border:0; vertical-align:middle;" title="Delete Trial Id" alt="Delete Trial Id" src="images/not.png" class="auto_suggest_multiple_delete"></td></tr>';
				}
			}
			else if(is_array($dbVal) && count($dbVal)>0)
			{
				echo input_tag($row,$dbVal,array('null_options'=>true,'name_index'=>'search','one_to_many'=>1));
			}
		}
		
	}
	echo '<tr><td colspan="2"><input type="submit" value="Search" name="search"><input type="submit" value="Reset" name="reset"></td></tr>';
	echo '</table>';		
	echo '</fieldset>';		
	$orderBy = (isset($_GET['order_by']))?$_GET['order_by']:null;
	$currentOrderBy = $orderBy;
	$sortArr = array('ASC','DESC','no_sort');
	$sortOrder = null;
	$noSort = null;
	if($orderBy)
	{
		$sortOrder=$_GET['sort_order'];
	}
	if($orderBy)	
	echo '<input type="hidden" name="order_by" value="'.$orderBy.'"/>';	
	if($noSort)
	echo '<input type="hidden" name="no_sort" value="1"/>';
	if($sortOrder)
	echo '<input type="hidden" name="sort_order" value="'.$sortOrder.'"/>';			
	if($table == 'upm')
	{
		//echo '<input type="hidden" id="search_product_id" name="search_product_id" value=""/>';
	}
	endif;//search active or not if
	
	echo '</form>';

				
echo '<br/>';	
	
}




/**
 * @name addEditUpm
 * @tutorial Provides output of the insert/edit form.
 * @param int $id If the param $id is present edit option is activated.	
 * $skip array if any fields in the upm table needs to be skipped when 
 * showing the upm entry form just append in this array
 * @author Jithu Thomas
 */
function addEditUpm($id,$table,$script,$options=array(),$skipArr=array())
{
	global $searchData;
	global $db;
	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
			case 'investigator': $actual_table = "entities"; break;
		}
	$searchType = calculateSearchType($db->sources,unserialize(base64_decode($searchData)));
	$insertEdit = 'Insert';
	$area = $larvol_id = $source_id = array();
	$formOnSubmit = isset($options['formOnSubmit'])?$options['formOnSubmit']:null;
	$formStyle = isset($options['formStyle'])?$options['formStyle']:null;
	$mainTableStyle = isset($options['mainTableStyle'])?$options['mainTableStyle']:null;
	$addEditGlobalInputStyle = isset($options['addEditGlobalInputStyle'])?$options['addEditGlobalInputStyle']:null;

        $addEdit_flag = isset($options['addEdit_flag'])?$options['addEdit_flag']:FALSE;
        $input_disabled = !$addEdit_flag;
	$defaultOptions = array('input_disabled' => $input_disabled);

	//get current details if the id is passed.
	if($id)
	{
		$insertEdit = 'Edit';
		
		if($table=='upm')
		{
			$query = "SELECT u.`id`, u.`event_type`, u.`event_description`, u.`event_link`, u.`result_link`, p.`name` AS product, ar.`name` as area, upmt.`larvol_id` as larvol_id, redtags.`name` as redtag, redtags.`id` as redtag_id, u.`condition`, dt.`source_id` as source_id, u.`status`, u.`start_date`, u.`start_date_type`, u.`end_date`, u.`end_date_type`,u.`last_update`, p.`id` as product_id FROM upm u LEFT JOIN products p ON u.product=p.id LEFT JOIN upm_areas a ON u.id=a.upm_id left join areas ar on ar.id=a.area_id left join upm_trials upmt on upmt.upm_id = u.id left join data_trials dt on dt.larvol_id = upmt.larvol_id left join redtags on u.`redtag` = redtags.`id` WHERE u.id=$id";
		}
		else
		{
			$where = calculateWhere($actual_table,$table);
			if(empty($where))	$query = "SELECT * FROM $actual_table WHERE id=$id";
			else				$query = "SELECT * FROM $actual_table $where and id=$id";
		}
		$res = mysql_query($query) or die('Cannot get details for this '.$table.' id');
		if($table == 'upm')
		{
			while($row = mysql_fetch_assoc($res))
			{
				$upmDetails = $row;
				$upm_product_id = isset($upmDetails['product_id'])?$upmDetails['product_id']:null;
				$upm_redtag_id = isset($upmDetails['redtag_id'])?$upmDetails['redtag_id']:null;
				if (!in_array($row['area'], $area))
				$area[] = $row['area'];
				if (!in_array($row['larvol_id'], $larvol_id))
				{
					$larvol_id[] = $row['larvol_id'];
					$source_id[] = $row['source_id'];
				}
			}
			$upmDetails['area'] = $area;
			$upmDetails['larvol_id'] = $larvol_id;
			$upmDetails['source_id'] = $source_id;
		}
		else
		{	
			while($row = mysql_fetch_assoc($res))
			{
				$upmDetails = $row;
				$upm_product_id = isset($upmDetails['product_id'])?$upmDetails['product_id']:null;
			}
		}
	}
	
	$columns = array();
	//$query = "SHOW COLUMNS FROM $table";
	//$res = mysql_query($query)or die('Cannot fetch column names from '.$table.' table.');
	$res = tableColumnDetails($actual_table);
	//TODO: make it dynamic for future one to many relationship and custom non existing columns
	if($table == 'upm')
	{
		$res[] = array(
	
				'Field' => 'area',
				'Type' => 'int(10) unsigned',
				'Null' => 'YES',
				'Key' => 'MUL',
				'Default' => '',
				'Extra' => '',
	
		);
		$res[] = array(
	
				'Field' => 'larvol_id',
				'Type' => 'int(10) unsigned',
				'Null' => 'YES',
				'Key' => 'MUL',
				'Default' => '',
				'Extra' => '',
	
		);
		
		$res = ArrangeTableColumns($res, array('redtag'=>2));
	}	
	$i=0;
	

	
	echo '<div class="clr">';
	echo '<fieldset>';
	echo '<legend> '.$insertEdit.': </legend>';
	echo '<form '.$formStyle.' id="umpInput" name="umpInput" '.$formOnSubmit.' method="POST" action="'.$script.'.php?entity='.$_REQUEST['entity'].'">';
	echo '<table '.$mainTableStyle.'>';
	//while($row = mysql_fetch_assoc($res))
	foreach($res as $row)
	{
		if($i==0)
		{
			echo '<input type="hidden" name="id" value="'.$id.'"/>';
			$i++;
			continue;
		}
		if( $insertEdit == 'Insert' && $row['Field'] == 'class' )
		{
			continue;
		}
		if(in_array($row['Field'], $skipArr))
		{
			continue;
		}
		$dbVal = isset($upmDetails[$row['Field']])?$upmDetails[$row['Field']]:null;
		
		//check products table for LI_id
		if($table=='products' && $row['Field']=='LI_id' && trim($dbVal)!='')
		{
			$options['deletebox'] = false;
		}
		
		if(isset($options['saveStatus'])&& $options['saveStatus']===0)
		{
			$dbVal = (isset($_REQUEST[$row['Field']]) && $_REQUEST[$row['Field']]!='')?$_REQUEST[$row['Field']]:$dbVal;
			$saveStatusForInputTag=0;
			
		}
		else 
		{
			$saveStatusForInputTag = 1;
		}
		
		if($row['Field'] == 'searchdata')
		{
			if($searchType ===false && ($table=='areas' || $table=='products' || $table=='diseases'))
			{
				$searchType = calculateSearchType($db->sources,unserialize(base64_decode($dbVal)));
			}			
			echo '<tr><td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td>';
			echo '<td>';
			echo input_tag($row,$dbVal,array('table'=>$actual_table,'id'=>$id,'callFrom'=>'addedit','saveStatusForInputTag'=>$saveStatusForInputTag, 'input_disabled'=>$input_disabled));
			echo '</td></tr>';	
			$i++;
			continue;			
		}
		if($row['Field']=='status' && $script=='upm')
		{
			echo '<tr>';
			echo '<td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td><td>'.$dbVal.'</td>';
			echo '</tr>';		
			$i++;	
			continue;
		}
		if($table == 'areas')
		{
			$defaultOptions['look_for_bool'] = true;
		}
		
		$defaultOptions['style'] = $addEditGlobalInputStyle;
		
		if($table=='institutions' or $table=='moas' or $table=='moacategories')
			$ProductReadOnlyArr = array('name','search_name','LI_id','client_name','is_active','class','description','display_name','category','searchdata','comments','product_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','created','modified','company','brand_names','generic_names','code_names','approvals','class');
		elseif($table=='products')
			$ProductReadOnlyArr = array('comments','is_active','product_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','created','modified','company','brand_names','generic_names','code_names','approvals','display_name');
		else
			$ProductReadOnlyArr = array('is_active');


		
		///default
		if($row['Field']!='larvol_id' || $table!='upm')
		{ 
			if(in_array($row['Field'], $ProductReadOnlyArr))
			{
				echo '<tr>';
				if($row['Field'] == 'is_key')
				{
					echo '<td>Key : </td><td>'.(($dbVal==='0')?'False':'True').'</td>';
				}
				else
				{
					$defaultOptionsReadonly = $defaultOptions;
					$defaultOptionsReadonly['style'] .= ' readonly="readonly" ';
					echo '<td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td><td>'.input_tag($row,$dbVal,$defaultOptionsReadonly).'</td>';
				}
				echo '</tr>';
			}
			else
			{
				echo '<tr>';
				echo '<td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td><td>'.input_tag($row,$dbVal,$defaultOptions).'</td>';
				echo '</tr>';
			}
		}
		else if($row['Field']=='larvol_id' && $table=='upm')
		{
			echo '<tr>';
			echo '<td>Trial ID : </td><td><input type="text" value="" name="'.$row['Field'].'[]" id="'.$row['Field'].'[]" /> <img style="border:0; height:20px; width:20px; vertical-align:middle;" title="Add Trial Id" alt="Add Trial Id" src="images/add.gif" class="add_multiple_larvol_id"></td>';
			echo '</tr>';

		}
		if($row['Field']=='area' && $table=='upm' && is_array($dbVal) && count($dbVal)>0)
		{
			//add explicit code for multiple 
			echo input_tag($row,$dbVal,array('null_options'=>true,'one_to_many'=>1));
		}
		
		if($row['Field']=='larvol_id' && $table=='upm' && is_array($dbVal) && count($dbVal)>0)
		{
			foreach($dbVal as $key=>$dbValIndividual)
			{
				if($dbValIndividual != NULL && $dbValIndividual != '')
				print '<tr><td></td><td><input class="'.$row['Field'].'_autosuggest_multiple"  name="'.$row['Field'].'[]" value="'.$dbValIndividual.'" checked="checked" type="checkbox"> <font title="Larvol Id">'.$dbValIndividual.'</font> <font title="Source Id">['.implode("] [",explode("`",$upmDetails['source_id'][$key])).']</font> <img style="border:0" title="Delete Trial Id" alt="Delete Trial Id" src="images/not.png" class="auto_suggest_multiple_delete"></td></tr>';
			}
		}
		
	}
	if(($table == 'products' || $table == 'areas') && $searchType!==false)
	{
		echo '<tr>';
		echo '<td>Type : </td><td>'.($searchType==1?'Auto':'SemiAuto').'</td>';
		echo '</tr>';		
	}
	$altTitle='Delete';
	if($table=='products')
	{
		$upmReference = getProductUpmAssociation($id);
		$upmReferenceCount = count($upmReference);
		$MHMReferenceCount = getMHMAssociation($id);
		$disabled = ($upmReferenceCount>0 || $MHMReferenceCount>0)?true:false;
		$altTitle = $disabled?'Cannot delete product as it is linked to other upms/MHM\'s. See References.':$altTitle;
		echo '<tr>';
		echo '<td>Active : </td><td>'.(($upmDetails['is_active']==='0')?'False':'True').'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>References : </td><td>'.(($upmReferenceCount>0)?input_tag(array('Type'=>'link','Field'=>'upm.php?search_id='.implode(',',$upmReference)),$upmReferenceCount,array('alttitle'=>'Click here to see the UPM references.','linkTarget'=>'_blank')):$upmReferenceCount).' UPM</td>';
		echo '</tr>';
	}
	
	if(in_array($table, array('areas', 'diseases', 'entities', 'moas', 'institutions', 'products')))
	{
		$MHMReferenceCount = getMHMAssociation($id);
		$disabled = ($MHMReferenceCount>0)?true:false;
		if($table!='entities') $entityType =substr($table, 0, -1); else $entityType = 'entity';
		$altTitle = $disabled?'Cannot delete '.$entityType.' as it is linked to other MHM\'s. See References.':$altTitle;
		echo '<tr>';
		echo '<td>References : </td><td>'.(($MHMReferenceCount>0)?input_tag(array('Type'=>'link','Field'=>'master_heatmap.php?HMSearchId='.$id),$MHMReferenceCount,array('alttitle'=>'Click here to see the MHM references.','linkTarget'=>'_blank')):$MHMReferenceCount).' MHM</td>';
		echo '</tr>';
	}	
	/*********** disabled delete
	
	if($options['deletebox']===true && $id)
	{
		echo '<tr>';
		echo '<td>Delete : </td><td>'.input_tag(null,null,array('deletebox'=>true,'id'=>$id,'disabled'=>$disabled,'alttitle'=>$altTitle)).'</td>';
		echo '</tr>';
	}
	*/
	if($searchData)
	echo '<input type="hidden" name="searchdata" value="'.$searchData.'">';
	if($table=='upm')
	{
		//echo '<input type="hidden" id="product_id" name="product_id" value="'.$upm_product_id.'">';
	}
	if($table=='products') 
	{
		$lnk='<a href="intermediary.php?p=' . $_GET['id'] . '" target="_blank">';
	}
		
	elseif($table=='areas' || $table=='diseases') 
	{
		$lnk='<a href="intermediary.php?a=' . $_GET['id'] . '" target="_blank">';
	}
	
	if(isset($lnk)) 
	{
		$lnk2='</a>';
	}
	else
	{
		$lnk="";$lnk2="";
	}
	
	if( ($table=='products' || $table=='areas'  || $table=='diseases') && isset($options['preindexProgress']) && isset($options['preindexStatus']) && $id)
	{
		$status = array('Completed','Ready','Running','Error','Cancelled');
		echo "<tr><td>".$lnk."Preindex".$lnk2." Status:</td><td align=\"left\" class=\"norm\">";
		echo "<span class=\"progressBar\" id=\"product_update\">{$options['preindexProgress']}</span>";
		echo "&nbsp;<span>{$status[$options['preindexStatus']['status']]}.</span>";
		echo "&nbsp;<span>".((isset($options['preindexStatus']['er_message']) && $options['preindexStatus']['er_message']!='')? $options['preindexStatus']['er_message'].'.': '')."</span>";
		echo "</td></tr>";	
	}
	if(strpos($_SERVER['REQUEST_URI'],'upm'))
		$input_disabled=FALSE;
	echo '<tr>&nbsp;<td></td><td>
	<input type="hidden" name="entity" value="'.$_REQUEST['entity'].'"/>
	'.($input_disabled ? '<input type="button" onclick="history.go(-1)" value="Go back" />' : '<input name ="save" type="submit" value="Save"/>').'</td>';
	echo '</table>';
	echo '</form>';
	//upm history 
	if($table=='upm'  && $insertEdit=='Edit')
	{
		echo upmChangeLog($id);
	}
	echo '</fieldset>';
	echo '</div>';
}

function am($k,$v)
{
	if($k=='corresponding_trial')
	{
//		$v = unpadnct($v);
	}		
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date','oldproduct', 'product', 'area', 'redtag', 'LI_id');
	if(in_array($k,$explicitNullFields) && $v=='')
	{
		$v = 'null';
		return mysql_real_escape_string($v);
	}
	return "'".mysql_real_escape_string($v)."'";
}
function am1($k,$v)
{
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date','oldproduct','product','area','searchdata', 'redtag', 'LI_id');
	if($k=='corresponding_trial')
	{
//		$v = unpadnct($v);
	}		
	if(in_array($k,$explicitNullFields) && $v=='')
	{
		$v = 'null';
		return "`".$k."`=".mysql_real_escape_string($v);
	}	
	return "`".$k."`='".mysql_real_escape_string($v)."'";
}
function am2($v,$dbVal)
{
	if($dbVal== $v)
	return '<option value="'.$v.'" selected="selcted">'.$v.'</option>';
	else 
	return '<option value="'.$v.'">'.$v.'</option>';
}

function validateImport($k,$v)
{
	if($k=='corresponding_trial')
	{
//		$v = unpadnct($v);
	}	
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date');
	if(in_array($k,$explicitNullFields) && !is_numeric($v))
	{
		
		$v = 'null';
	}

	return $v;
}

function unzipForXmlImport($file)
{
    $zip = zip_open($file);
    if(is_resource($zip))
    {
        while(($zip_entry = zip_read($zip)) !== false)
        {
            $xml = zip_entry_name($zip_entry);
            $xmlFile = substr($file,0,strripos($file, DIRECTORY_SEPARATOR)).DIRECTORY_SEPARATOR.$xml;
            file_put_contents($xmlFile, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
            return $xmlFile;
        }
    }
} 

/**
 * @name getProductUpmAssociation
 * @tutorial Provides the upms linked to products.
 * @param int $id product id.
 * @author Jithu Thomas
 */
function getProductUpmAssociation($id)
{
	$query = "select u.id from upm u left join products p on u.product=p.id where p.id=$id";
	$result = mysql_query($query);
	$out = array();
	while($row = mysql_fetch_assoc($result))
	{
		$out[] = $row['id'];
	}
	return $out;	
}

/**
* @name getMHMAssociation
* @tutorial Provides count of mhm's linked to areas/products
* @param int $id areas/products id.
* @param string $type[areas/products]
* @author Jithu Thomas
*/
function getMHMAssociation($id,$type='')
{
	$query = "select count(distinct(h.`report`)) as cnt from rpt_masterhm_headers h where h.type_id='$id'";
	$result = mysql_query($query);
	while($row = mysql_fetch_assoc($result))
	{
		return $row['cnt'];
	}

}

/**
* @name calculateSearchType
* @tutorial Returns the search type based on search data array.
* The type is either Auto or Semi Auto.Semi Auto is when the only
* fields being searched on are source ID fields (such as NCT/nct_id).
* This function returs 1 for Auto and 0 for SemiAuto & false for invalid/null searchdata;
* @param Array $sourceArr We can get from the global variable $db->sources.
* @param Array $searchArr is normally the $_POST array of the search form submission after input filtering.
* @author Jithu Thomas
*/
function calculateSearchType($sourceArr,$searchArr)
{
	if(isset($searchArr['searchval']) && is_array($searchArr['searchval']) && count($searchArr)>0)
	{
		$searchSourceIdArr = array_map(function($id){ $tmp = explode('_',$id);return $tmp[1];},array_keys($searchArr['searchval']));
		$sourceIdArr = array_map(function($id){return $id->fieldId;},$sourceArr);
		foreach($searchSourceIdArr as $id)
		{
			if(!in_array($id,$sourceIdArr))
			return 1;
		}
		return 0;
	}
	else
	{
		//softDieSession('Invalid search data used for auto/semi auto calculation.');
		return false;
	}
}

/**
* @name getUpmHistory
* @tutorial returns an array of upm change history for a specific upm.
* @param $id upm id.
* @param $limit max number of changes to be shown.
* @return array of upm changes or false if no upm changes are present.
* @author Jithu Thomas
*/
function getUpmHistory($id,$limit=null)
{
	$query = "SELECT uh.*,u.username as username FROM upm_history uh left join users u on uh.user=u.id WHERE uh.id=$id ORDER BY change_date DESC";
	if(is_numeric($limit) && $limit>0)
	{
		$query .= " LIMIT $limit";
	}
	$result  = mysql_query($query);
	if(mysql_num_rows($result) <=0)
	return false;
	
	while($row = mysql_fetch_assoc($result))
	{
		$out[] = $row;
	}
	return $out;
}

/**
* @name upmChangeLog
* @tutorial generates upm change logs.
* @param $id upm id.
* @return html table of upm change logs.
* @author Jithu Thomas
*/
function upmChangeLog($id)
{
	$historyArr = getUpmHistory($id);
	$out = '<br/><table>';
	$out .= '<tr><th colspan="5">Change History</th></tr>';
	if(!is_array($historyArr) || count($historyArr)<=0)
	{
		$out .= '<tr><td>No Change History</td></tr></table>';
		return $out;
	}
	
	// if history present.
	$out .= '<tr><td>Change Date</td><td>Field</td><td>Old Value</td><td>New Value</td><td>User</td>';
	foreach($historyArr as $history)
	{
		$out .= '<tr>';
		$out .= '<td>';
		$out .= $history['change_date'];
		$out .= '</td>';
		$out .= '<td>';
		$out .= $history['field'];
		$out .= '</td>';
		$out .= '<td>';
		$out .= $history['old_value'];
		$out .= '</td>';
		$out .= '<td>';
		$out .= $history['new_value'];
		$out .= '</td>';
		$out .= '<td>';
		$out .= $history['username'];
		$out .= '</td>';
		$out .= '</tr>';
	}
	$out .= '</table>';
	return $out;
}
/**
 * @name softDieSession
 * @tutorial Logs die messages into session log for future usage & also logs into the logger.
 * @param $out error output.
 * @param $raw 1=outputs message to screen 0 = no output displayed
 * @param $log 1 = logs to logger
 * @param $level if $log = 1 takes the logging level threshold.
 * @author Jithu Thomas
 */	
function softDieSession($out,$raw=0,$log=0,$level='')
{
	$_SESSION['page_errors'][] = $out;
	if($raw==1)
	echo $out.'<br/>';
	return false;
}

/**
* @name getSearchData
* @tutorial returns the search data string for a table
* @param $table,$searchdata,$id
* @author Jithu Thomas
*/
function getSearchData($table,$searchdata,$id)
{
	global $db;
	
	$actual_table=$table;
	switch($table)
		{
			case 'products': $actual_table = "entities"; break;
			case 'areas': $actual_table = "entities"; break;
			case 'institutions': $actual_table = "entities"; break;
			case 'moas': $actual_table = "entities"; break;
			case 'moacategories': $actual_table = "entities"; break;
			case 'diseases': $actual_table = "entities"; break;
		}
	$query = "select $searchdata from $actual_table where id=$id";
	$result = mysql_query($query);
	$out = null;
	while($row = mysql_fetch_assoc($result))
	{
		$out = $row[$searchdata];
		break;
	}
	return $out;
}

/**
* @name parseProductsXmlAndSave
* @tutorial parse and get ready products xml for saving.
* @param $table,$searchdata,$id
* @author Jithu Thomas
*/
function parseProductsXmlAndSave($xmlImport,$table)
{
	$importKeys = array('LI_id','name','comments','product_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','is_active','created','modified','company','brand_names','generic_names','code_names','approvals','xml');
	$success = $fail = $skip = $delete = 0;
	foreach($xmlImport->getElementsByTagName('Product') as $product)
	{
		$importVal = array();
		$product_id = $product->getElementsByTagName('product_id')->item(0)->nodeValue;
		$prodname = $product->getElementsByTagName('name')->item(0)->nodeValue;
		$comments = $product->getElementsByTagName('comments')->item(0)->nodeValue;
		$product_type = $product->getElementsByTagName('product_type')->item(0)->nodeValue;
		$licensing_mode = $product->getElementsByTagName('licensing_mode')->item(0)->nodeValue;
		$administration_mode = $product->getElementsByTagName('administration_mode')->item(0)->nodeValue;
		$discontinuation_status = $product->getElementsByTagName('discontinuation_status')->item(0)->nodeValue;
		$discontinuation_status_comment = $product->getElementsByTagName('discontinuation_status_comment')->item(0)->nodeValue;
		$is_key = ($product->getElementsByTagName('is_key')->item(0)->nodeValue == 'True')?1:0;
		$is_active = ($product->getElementsByTagName('is_active')->item(0)->nodeValue == 'True')?1:0;
		$created = date('y-m-d H:i:s',time($product->getElementsByTagName('created')->item(0)->nodeValue));
		$modified = date('y-m-d H:i:s',time($product->getElementsByTagName('modified')->item(0)->nodeValue));
	}
	
	$implodeStringForNames = ', ';
	
	//Get Company names
	$company = array();
	foreach($xmlImport->getElementsByTagName('Institutions') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('Institution') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$company[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
		}
	}
	$company = implode($implodeStringForNames,$company);
	
	//Get product brand names
	$brand_names = array();
	foreach($xmlImport->getElementsByTagName('ProductBrandNames') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('ProductBrandName') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$brand_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
		}
	}
	$brand_names = implode($implodeStringForNames,$brand_names);
		
	//Get Generic names
	$generic_names = array();
	foreach($xmlImport->getElementsByTagName('ProductGenericNames') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('GenericName') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$generic_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
		}
	}
	$generic_names = implode($implodeStringForNames,$generic_names);
	
	//Get Product Code names
	$code_names = array();
	foreach($xmlImport->getElementsByTagName('ProductCodeNames') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('CodeName') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$code_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
		}
	}
	$code_names = implode($implodeStringForNames,$code_names);
	
	$approvals = $xmlImport->getElementsByTagName('ProductApprovals')->item(0)->nodeValue;
	
	$xmldump = $xmlImport->saveXML($xmlImport);
	
	//Get associated institution id and names
	$inst_ids = array();
	$inst_names = array();
	foreach($xmlImport->getElementsByTagName('Institutions') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('Institution') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$inst_ids[] = $brandName->getElementsByTagName('institution_id')->item(0)->nodeValue:null;
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$inst_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
		}
	}
	
	//Get associated moa id and names
	$moa_ids = array();
	$moa_names = array();
	foreach($xmlImport->getElementsByTagName('MOAs') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('MOA') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$moa_ids[] = $brandName->getElementsByTagName('moa_id')->item(0)->nodeValue:null;
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$moa_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
		}
	}
			
	$importVal = array('LI_id'=>$product_id,'name'=>$prodname,'comments'=>$comments,'product_type'=>$product_type,'licensing_mode'=>$licensing_mode,'administration_mode'=>$administration_mode,'discontinuation_status'=>$discontinuation_status,'discontinuation_status_comment'=>$discontinuation_status_comment,'is_key'=>$is_key,'is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'company'=>$company,'brand_names'=>$brand_names,'generic_names'=>$generic_names,'code_names'=>$code_names,'approvals'=>$approvals,'xml'=>$xmldump);
	if(($product_id == NULL && trim($product_id) == '') || ($prodname == NULL && trim($prodname) == ''))
		return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>true);
	//var_dump($importVal);
	//ob_start();
	$out = saveData(null,$table,1,$importKeys,$importVal,$k,array('institutionIdsArray'=>$inst_ids, 'institutionNamesArray'=>$inst_names, 'moaIdsArray'=>$moa_ids, 'moaNamesArray'=>$moa_names));
	if($out ==1)
	{
		$success ++;
		ob_start();
		echo 'Product Id : '.$product_id.' Done .. <br/>'."\n";
		ob_end_flush();
	}
	elseif($out==2) 
	{
		echo 'Product Id : '.$product_id.' Fail !! <br/>'."\n";
		$fail ++;
	}
	elseif($out==3)
	{
		echo 'Product Id : '.$product_id.' Skipped !! <br/>'."\n";
		$skip ++;
	}	
	elseif($out==4)
	{
		echo 'Product Id : '.$product_id.' Deleted !! <br/>'."\n";
		$delete ++;
	}			
	//ob_end_clean();
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>false);
}

/**
* @name getPreindexProgress
* @tutorial Get preindexing progress data,
* Currently it supports products/areas page after save operation preindex progress data.
* @param $type type of preindex data. Eg: PRODUCT2,AREA2
* @author Jithu Thomas
*/
function getPreindexProgress($type,$itemId)
{
	global $db;
	$type = mysql_real_escape_string($type);
	$itemId = mysql_real_escape_string($itemId);
	$query = 'SELECT `update_id`,`process_id`,`start_time`,`updated_time`,`status`,
							`update_items_total`,`update_items_progress`,`er_message`,TIMEDIFF(updated_time, start_time) AS timediff,
							`update_items_complete_time` FROM update_status_fullhistory where trial_type="'.$type.'" and item_id='.$itemId;
	if(!$res = mysql_query($query))
	{
		$msg = 'There seems to be a problem with the SQL Query:'.$query.' Error:' . mysql_error();
		softDieSession($msg,$raw=0,1);
		return false;
	}	
	
	$status = array();
	while($row = mysql_fetch_assoc($res))
	$status = $row;	
	
	return $status;
}

/*
Function to get product or redtag name from there id's to store it in history table
*/
function getUPMProdOrRedtagName($table, $value)
{
	global $db, $logger;
	$query = "select `name` from $table where id=".$value."";
	$res = mysql_query($query);
	if($res)
	{
		$val = null;
		while($row = mysql_fetch_assoc($res))
		{
			$val = $row['name'];
		}
	}
	else
	{
		$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
		$logger->error($log);
		unset($log);
	}

	return $val;
}

/*
Small common function to arrange table columns as we want to display irrespective of database
*/
function ArrangeTableColumns($columnList, $NewFieldPos)
{
	//Specify field names and there new positions as we want in below format
	//$NewFieldPos = array('redtag'=>3);
	foreach($NewFieldPos as $FieldName => $Pos)
	{
		foreach($columnList as $FieldDetailsKey => $FieldDetails)
		if($FieldDetails['Field'] == $FieldName)
		$CurPos = $FieldDetailsKey;
		
		$newColumnList = array(); $i = 0;
		foreach($columnList as $FieldDetailsKey => $FieldDetails)
		{
			if($FieldDetails['Field'] == $FieldName)
			continue;
			if($i == $Pos)
			$newColumnList[] = $columnList[$CurPos];
			$i++;
			$newColumnList[] = $FieldDetails;
		}
		$columnList = $newColumnList;
	}
	return $columnList;
}

/**
* @name parseInstitutionsXmlAndSave
* @tutorial parse and get ready Institutions xml for saving.
* @param $table,$searchdata,$id
*/
function parseInstitutionsXmlAndSave($xmlImport,$table)
{
	$importKeys = array('LI_id','name','category','display_name','is_active','created','modified','search_name','client_name','xml');
	$success = $fail = $skip = $delete = 0;
	foreach($xmlImport->getElementsByTagName('Institution') as $institution)
	{
		$importVal = array();
		$institution_id = $institution->getElementsByTagName('institution_id')->item(0)->nodeValue;
		$institution_name = $institution->getElementsByTagName('name')->item(0)->nodeValue;
		$type = $institution->getElementsByTagName('type')->item(0)->nodeValue;
		$display_name = $institution->getElementsByTagName('display_name')->item(0)->nodeValue;
		$is_active = ($institution->getElementsByTagName('is_active')->item(0)->nodeValue == 'True')?1:0;
		$created = date('y-m-d H:i:s',time($institution->getElementsByTagName('created')->item(0)->nodeValue));
		$modified = date('y-m-d H:i:s',time($institution->getElementsByTagName('modified')->item(0)->nodeValue));
		$search_terms = $institution->getElementsByTagName('search_terms')->item(0)->nodeValue;		
	}
	
	$implodeStringForNames = ', ';
	
	//Get product brand names
	$client_names = array();
	foreach($xmlImport->getElementsByTagName('InstitutionClients') as $brandNames)
	{
		foreach($brandNames->getElementsByTagName('Client') as $brandName)
		{
			($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$client_names[] = $brandName->getElementsByTagName('client_name')->item(0)->nodeValue:null;
		}
	}
	$client_names = implode($implodeStringForNames,$client_names);
			
	$xmldump = $xmlImport->saveXML($xmlImport);
		
	$importVal = array('LI_id'=>$institution_id,'name'=>$institution_name,'type'=>$type,'display_name'=>$display_name,'is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'search_terms'=>$search_terms,'client_name'=>$client_names,'xml'=>$xmldump);
	if(($institution_id == NULL && trim($institution_id) == '') || ($institution_name == NULL && trim($institution_name) == ''))
		return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>true);
	//var_dump($importVal);
	//ob_start();
	$out = saveData(null,$table,1,$importKeys,$importVal,$k);
	if($out ==1)
	{
		$success ++;
		ob_start();
		echo 'Institution Id : '.$institution_id.' Done .. <br/>'."\n";
		ob_end_flush();
	}
	elseif($out==2) 
	{
		echo 'Institution Id : '.$institution_id.' Fail !! <br/>'."\n";
		$fail ++;
	}
	elseif($out==3)
	{
		echo 'Institution Id : '.$institution_id.' Skipped !! <br/>'."\n";
		$skip ++;
	}	
	elseif($out==4)
	{
		echo 'Institution Id : '.$institution_id.' Deleted !! <br/>'."\n";
		$delete ++;
	}			
	//ob_end_clean();
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>false);
}

/**
* @name parseMoasXmlAndSave
* @tutorial parse and get ready Moas xml for saving.
* @param $table,$searchdata,$id
*/
function parseMoasXmlAndSave($xmlImport,$table)
{
	$importKeys = array('LI_id', 'name', 'class', 'display_name', 'is_active', 'created', 'modified','xml');
	$success = $fail = $skip = $delete = 0;
	foreach($xmlImport->getElementsByTagName('Moa') as $moa)
	{
		$importVal = array();
		$moa_id = $moa->getElementsByTagName('moa_id')->item(0)->nodeValue;
		$moa_name = $moa->getElementsByTagName('name')->item(0)->nodeValue;
		$display_name = $moa->getElementsByTagName('display_name')->item(0)->nodeValue;
		$is_active = ($moa->getElementsByTagName('is_active')->item(0)->nodeValue == 'True')?1:0;
		$created = date('y-m-d H:i:s',time($moa->getElementsByTagName('created')->item(0)->nodeValue));
		$modified = date('y-m-d H:i:s',time($moa->getElementsByTagName('modified')->item(0)->nodeValue));
		
		$implodeStringForNames = ', ';
	
		$xmldump = $xmlImport->saveXML($moa);
	}
	
	//Get associated moa categories id and names
	$moa_categories_ids = array();
	foreach($xmlImport->getElementsByTagName('MoaCategories') as $MoaCategories)
	{
		foreach($MoaCategories->getElementsByTagName('MoaCategory') as $MoaCategory)
		{
			$moa_categories_ids[] = $MoaCategory->getElementsByTagName('moa_category_id')->item(0)->nodeValue;
		}
	}
			
	$importVal = array('LI_id'=>$moa_id,'name'=>$moa_name,'class'=>'MOA', 'display_name'=>$display_name,'is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'xml'=>$xmldump);
	
	if(($moa_id == NULL && trim($moa_id) == '') || ($moa_name == NULL && trim($moa_name) == ''))
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>true);
	
	//var_dump($moa_categories_ids);
	//var_dump($importVal);
	//ob_start();
	$out = saveData(null,$table,1,$importKeys,$importVal,$k, array('MoaCategoryIdsArray'=>$moa_categories_ids));
	if($out ==1)
	{
		$success ++;
		ob_start();
		echo 'Moa Id : '.$moa_id.' Done .. <br/>'."\n";
		ob_end_flush();
	}
	elseif($out==2) 
	{
		echo 'Moa Id : '.$moa_id.' Fail !! <br/>'."\n";
		$fail ++;
	}
	elseif($out==3)
	{
		echo 'Moa Id : '.$moa_id.' Skipped !! <br/>'."\n";
		$skip ++;
	}		
	elseif($out==4)
	{
		echo 'Moa Id : '.$moa_id.' Deleted !! <br/>'."\n";
		$delete ++;
	}			
	//ob_end_clean();
	
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>false);
}

/**
* @name parseMoacategoriesXmlAndSave
* @tutorial parse and get ready Moa categories xml for saving.
* @param $table,$searchdata,$id
*/
function parseMoacategoriesXmlAndSave($xmlImport,$table)
{
	$importKeys = array('LI_id', 'name', 'class', 'is_active', 'created', 'modified','xml');
	$success = $fail = $skip = $delete = 0;
	foreach($xmlImport->getElementsByTagName('MoaCategory') as $moacategory)
	{
		$importVal = array();
		$moacategory_id = $moacategory->getElementsByTagName('moa_category_id')->item(0)->nodeValue;
		$moacategory_name = $moacategory->getElementsByTagName('name')->item(0)->nodeValue;
		$is_active = ($moacategory->getElementsByTagName('is_active')->item(0)->nodeValue == 'True')?1:0;
		$created = date('y-m-d H:i:s',time($moacategory->getElementsByTagName('created')->item(0)->nodeValue));
		$modified = date('y-m-d H:i:s',time($moacategory->getElementsByTagName('modified')->item(0)->nodeValue));
		$optional_name = $moacategory->getElementsByTagName('optional_name')->item(0)->nodeValue;		
		
		$implodeStringForNames = ', ';
	
		$xmldump = $xmlImport->saveXML($moacategory);
			
		$importVal = array('LI_id'=>$moacategory_id,'name'=>$moacategory_name,'class'=>'MOA_Category','is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'xml'=>$xmldump);
		
		if(($moacategory_id == NULL && trim($moacategory_id) == '') || ($moacategory_name == NULL && trim($moacategory_name) == ''))
		return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>true);
		
		//var_dump($importVal);
		//ob_start();
		$out = saveData(null,$table,1,$importKeys,$importVal,$k);
		if($out ==1)
		{
			$success ++;
			ob_start();
			echo 'Moacategory Id : '.$moacategory_id.' Done .. <br/>'."\n";
			ob_end_flush();
		}
		elseif($out==2) 
		{
			echo 'Moacategory Id : '.$moacategory_id.' Fail !! <br/>'."\n";
			$fail ++;
		}
		elseif($out==3)
		{
			echo 'Moacategory Id : '.$moacategory_id.' Skipped !! <br/>'."\n";
			$skip ++;
		}		
		elseif($out==4)
		{
			echo 'Moacategory Id : '.$moacategory_id.' Deleted !! <br/>'."\n";
			$delete ++;
		}			
		//ob_end_clean();
	}
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>false);
}

/**
* @name parseDiseasesXmlAndSave
* @tutorial parse and get ready Moa categories xml for saving.
* @param $table,$searchdata,$id
*/
function parseDiseasesXmlAndSave($xmlImport,$table)
{
	$importKeys = array('LI_id', 'name', 'is_active', 'created', 'modified','xml');
	$success = $fail = $skip = $delete = 0;
	foreach($xmlImport->getElementsByTagName('area') as $area)
	{
		$importVal = array();
		$area_id = $area->getElementsByTagName('area_id')->item(0)->nodeValue;
		$area_name = $area->getElementsByTagName('name')->item(0)->nodeValue;
		$is_active = ($area->getElementsByTagName('is_active')->item(0)->nodeValue == 'True')?1:0;
		$created = date('y-m-d H:i:s',time($area->getElementsByTagName('created')->item(0)->nodeValue));
		$modified = date('y-m-d H:i:s',time($area->getElementsByTagName('modified')->item(0)->nodeValue));
		$display_option = $area->getElementsByTagName('display_option')->item(0)->nodeValue;		
		
		$implodeStringForNames = ', ';
	
		$xmldump = $xmlImport->saveXML($area);
			
		$importVal = array('LI_id'=>$area_id, 'name'=>$area_name, 'is_active'=>$is_active, 'created'=>$created, 'modified'=>$modified, 'xml'=>$xmldump);
		
		if(($area_id == NULL && trim($area_id) == '') || ($area_name == NULL && trim($area_name) == ''))
		return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>true);
		
		//var_dump($importVal);
		//ob_start();
		$out = saveData(null,$table,1,$importKeys,$importVal,$k);
		if($out ==1)
		{
			$success ++;
			ob_start();
			echo 'Disease Id : '.$area_id.' Done .. <br/>'."\n";
			ob_end_flush();
		}
		elseif($out==2) 
		{
			echo 'Disease Id : '.$area_id.' Fail !! <br/>'."\n";
			$fail ++;
		}
		elseif($out==3)
		{
			echo 'Disease Id : '.$area_id.' Skipped !! <br/>'."\n";
			$skip ++;
		}		
		elseif($out==4)
		{
			echo 'Disease Id : '.$area_id.' Deleted !! <br/>'."\n";
			$delete ++;
		}			
		//ob_end_clean();
	}
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>false);
}


/**
* @name parseTherapeuticAreasXmlAndSave
* @tutorial parse and get ready Therapeutic Areas xml for saving.
* @param $table,$searchdata,$id
*/
function parseTherapeuticAreasXmlAndSave($xmlImport,$table)
{
	$importKeys = array('LI_id', 'name', 'is_active', 'created', 'modified', 'display_name', 'xml');
	$success = $fail = $skip = $delete = 0;
	foreach($xmlImport->getElementsByTagName('TherapeuticArea') as $TherapeuticArea)
	{
		$importVal = array();
		$TherapeuticArea_id = $TherapeuticArea->getElementsByTagName('therapeutic_area_id')->item(0)->nodeValue;
		$TherapeuticArea_name = $TherapeuticArea->getElementsByTagName('name')->item(0)->nodeValue;
		$is_active = ($TherapeuticArea->getElementsByTagName('is_active')->item(0)->nodeValue == 'True')?1:0;
		$created = date('y-m-d H:i:s',time($TherapeuticArea->getElementsByTagName('created')->item(0)->nodeValue));
		$modified = date('y-m-d H:i:s',time($TherapeuticArea->getElementsByTagName('modified')->item(0)->nodeValue));
		$display_name = $TherapeuticArea->getElementsByTagName('display_name')->item(0)->nodeValue;		
		
		$implodeStringForNames = ', ';
	
		$xmldump = $xmlImport->saveXML($TherapeuticArea);
			
		$importVal = array('LI_id'=>$TherapeuticArea_id,'name'=>$TherapeuticArea_name,'is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'display_name'=>$display_name,'xml'=>$xmldump);
		
		if(($TherapeuticArea_id == NULL && trim($TherapeuticArea_id) == '') || ($TherapeuticArea_name == NULL && trim($TherapeuticArea_name) == ''))
		return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>true);
	
	}
	
	//Get associated disease ids
	$area_ids = array();
	foreach($xmlImport->getElementsByTagName('Areas') as $Diseases)
	{
		foreach($Diseases->getElementsByTagName('area') as $Disease)
		{
			$area_ids[] = $Disease->getElementsByTagName('area_id')->item(0)->nodeValue;
		}
	}
	
	//var_dump($importVal);
	//ob_start();
	$out = saveData(null,$table,1,$importKeys,$importVal,$k, array('DiseaseIdsArray'=>$area_ids));
	if($out ==1)
	{
		$success ++;
		ob_start();
		echo 'Therapeutic Area Id : '.$TherapeuticArea_id.' Done .. <br/>'."\n";
		ob_end_flush();
	}
	elseif($out==2) 
	{
		echo 'Therapeutic Area Id : '.$TherapeuticArea_id.' Fail !! <br/>'."\n";
		$fail ++;
	}
	elseif($out==3)
	{
		echo 'Therapeutic Area Id : '.$TherapeuticArea_id.' Skipped !! <br/>'."\n";
		$skip ++;
	}		
	elseif($out==4)
	{
		echo 'Therapeutic Area Id : '.$TherapeuticArea_id.' Deleted !! <br/>'."\n";
		$delete ++;
	}			
	//ob_end_clean();
	
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip,'delete'=>$delete, 'exitProcess'=>false);
}

function fixurl($filter = array()) 
{
	/*
	$pageURL = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https://" : "http://";
	$pageURL .= $_SERVER["SERVER_NAME"];
	if ($_SERVER["SERVER_PORT"] != "80") 
	{
		$pageURL .= ":".$_SERVER["SERVER_PORT"];
	}
	*/
	$pageURL = $_SERVER["REQUEST_URI"];

	if (strlen($_SERVER["QUERY_STRING"]) > 0) 
	{
		$pageURL = rtrim(substr($pageURL, 0, -strlen($_SERVER["QUERY_STRING"])), '?');
	}

	$query = $_GET;
	foreach ($filter as $key) 
	{
		unset($query[$key]);
	}

	if (sizeof($query) > 0) 
	{
		$pageURL .= '?' . http_build_query($query);
	}

	return $pageURL;
}

?>