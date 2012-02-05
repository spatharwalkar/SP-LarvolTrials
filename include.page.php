<?php
/**
 * @name contentListing
 * @tutorial Provides output of all the table entries in the upm table based on the params $start and $limit
 * @param int $start Start value of sql select query.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function contentListing($start=0,$limit=50,$table,$script,$ignoreFields=array(),$includeFields=array(),$options=array('delete'=>true,'ignoresort'=>array()))
{
global $deleteFlag;
if($options['delete']===false)
$deleteFlag=false;
	
//get search params
$where = calculateWhere($table);

//calculate sortable fields
$query = "SHOW COLUMNS FROM $table";
$res = mysql_query($query);
$sortableRows = array();
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
if($_GET['no_sort']==1)
{
	$sortImg = '';
}
if($_GET['sort_order']=='ASC' )
{
	$sortImg = 'ASC';
}
if($_GET['sort_order']=='DESC' )
{
	$sortImg = 'DESC';
}

if($table !='upm')
{
	if($_GET['no_sort']!=1)
	$query = "select * from $table $where $currentOrderBy $currentSortOrder limit $start , $limit";
	else
	$query = "select * from $table $where limit $start , $limit";
}
elseif($table=='upm')
{
	if($_GET['no_sort']!=1)
	$query = "select upm.id,upm.event_type,upm.event_description,upm.event_link,upm.result_link,p.name as product,upm.corresponding_trial,upm.start_date,upm.start_date_type,upm.end_date,upm.end_date_type,upm.last_update from upm left join products p on upm.product=p.id $where $currentOrderBy $currentSortOrder limit $start , $limit";
	else
	$query = "select upm.id,upm.event_type,upm.event_description,upm.event_link,upm.result_link,p.name as product,upm.corresponding_trial,upm.start_date,upm.start_date_type,upm.end_date,upm.end_date_type,upm.last_update from upm left join products p on upm.product=p.id $where limit $start , $limit";
}

$res = mysql_query($query) or softDieSession('Cannot get '.$table.' data.'.$query);
$i=0;
$skip=0;

$deleteParams = substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'],'?')+1);
//remove save flag from params 
$deleteParams = str_replace('&save=Save', '', $deleteParams);
$deleteConnector = '&';
echo '<div></div>';
echo '<table border="1" width="99%">';
while ($row = mysql_fetch_assoc($res))
{
	
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
			if($_GET['order_by']==$columnName && in_array($columnName,$sortableRows))
			$url = urlPath().$params.$connector.'order_by='.$columnName.'&sort_order='.$sortOrder.$noSort;
			elseif(in_array($columnName,$sortableRows))
			{
			$url = urlPath().$params.$connector.'order_by='.$columnName.'&sort_order=ASC';
			}
			else
			$url=null;
			
			if($url)
			echo '<a href="'.$url.'">';
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
				echo '<td><a href="'.$script.'.php?id='.$v.'">';
				echo $v;
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
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$table,'id'=>$upmId,'callFrom'=>'contentListingProducts'));
				echo '</td>';				
			}	
			else
			if($columnName == 'searchdata' && $table=='areas')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$table,'id'=>$upmId,'callFrom'=>'contentListingAreas'));
				echo '</td>';
			}					
			else
			if($columnName == 'searchdata')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$table,'id'=>$upmId));
				echo '</td>';				
			}			
			else 
			{
				echo '<td>';
				echo $v;
				echo '</td>';
			}
		}	
		if($deleteFlag)
		echo '<td><a onclick="return upmdelsure();" href="'.$script.'.php?deleteId='.$upmId.'&'.$deleteParams.'"><img src="images/not.png"/ alt="Delete '.$table.' id '.$upmId.'" title="Delete '.$table.' id '.$upmId.'" style="border:0"></a></td>';
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
				echo '<td><a href="'.$script.'.php?id='.$v.'">';
				echo $v;
				echo '</a></td>';				
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
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$table,'id'=>$upmId,'callFrom'=>'contentListingProducts'));
				echo '</td>';				
			}	
			else
			if($columnName == 'searchdata' && $table=='areas')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$table,'id'=>$upmId,'callFrom'=>'contentListingAreas'));
				echo '</td>';
			}					
			else
			if($columnName == 'searchdata')
			{
				echo '<td>';
				echo input_tag(array('Field'=>'searchdata'),$v,array('table'=>$table,'id'=>$upmId));
				echo '</td>';				
			}			
			else 
			{
				echo '<td>';
				echo $v;
				echo '</td>';
			}
		}
		if($deleteFlag)
		echo '<td><a onclick="return upmdelsure();" href="'.$script.'.php?deleteId='.$upmId.'&'.$deleteParams.'"><img src="images/not.png"/ alt="Delete '.$table.' id '.$upmId.'" title="Delete '.$table.' id '.$upmId.'" style="border:0"></a></td>';
		echo '</tr>';				
	}
	
	
$i++;
}
echo '</table>';
echo '<br/>';
}

/**
 * @name calculateWhere
 * @tutorial Outputs the WHERE query substring.
 * Just follow the naming convention of get parameters passed start with search_ and
 * it should automatically create the where clause of your sql.
 * @param $_GET['search_*
 * @author Jithu Thomas
 */
function calculateWhere($table)
{
	$postKeys = array_keys($_GET);
	$whereArr = array();
	foreach($postKeys as $keys)
	{
		$explode = explode('search_',$keys);
		if(isset($explode[1]))	
		{
			if(trim($_GET[$keys]) !='')
			$whereArr[$explode[1]] = $_GET[$keys];
		}
		else
		{
			continue;
		}
	}
	if(count($whereArr)>0)
	{
		$whereKeys = array_keys($whereArr);
		foreach($whereKeys as $k => $v) $whereKeys[$k] = $table . '.' . $v;
		$whereValues = array_values($whereArr);
		$whereArr = array_map(
							function($whereKeys,$whereValues)
							{

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
								if($whereKeys=='upm.event_description' || $whereKeys=='products.name')
								{
									return ' '.$whereKeys.' LIKE '. '\'%'.$whereValues.'%\' AND ';
								}								
								return ' '.$whereKeys.' = '. '\''.$whereValues.'\' AND ';
							},
							$whereKeys,
							$whereValues
						);
		
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
	global $db;
	$where = calculateWhere($table);
	$query = "select count(id) as cnt from $table $where";
	$res = mysql_query($query);
	$count = mysql_fetch_row($res);
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
	$query = "delete from $table where id=$id";
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
			
		default:
			$dateinput = (strpos($row['Field'], 'date') !== false) ? ' class="jdpicker"' : '';
			return '<input '.$style.' type="text" value="'.$dbVal.'" name="'.$nameIndex.$row['Field'].'" id="'.$nameIndex.$row['Field'].'"' . $dateinput . '/>';
			break;
	}
}

/**
 * @name saveData
 * @tutorial Saves the table entry/edit forms and inputs from the tab seperated file inputs.
 * @param array $post Post array.
 * @param int $import =0 for normal form save and = 1 for tab seperated input.
 * @param array $importKeys Keys for import relates to the fields in the upm table.
 * @param array $importVal Values for each column in the upm table corresponds to a single line in hte import file.
 * @param int $line Line number for error and notice usage. Related to import functionality.
 * @author Jithu Thomas
 */
function saveData($post,$table,$import=0,$importKeys=array(),$importVal=array(),$line=null)
{
	global $now;
	global $db;
	//import save
	if($import ==1 && $table=='upm')
	{
		$importVal = array_map(validateImport,$importKeys,$importVal);
		$query = "insert into upm (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
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
		$query = "select id from products where LI_id='{$esclid}' OR name='{$escname}' limit 1";
		$result = mysql_query($query);
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
			$importVal = array_map(am1,$importKeys,array_values($importVal));
			$query = "update $table set ".implode(',',$importVal)." where id=".$id;
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
			$query = "insert into $table (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
		}
		if(mysql_query($query))
		{
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
	
	if(isset($post['delsearch']) && is_array($post['delsearch']))
	{
		foreach($post['delsearch'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			if($db->user->userlevel != 'user')
			{
				$post['searchdata'] = '';
				$q = "UPDATE $table SET searchdata=null WHERE id=$id LIMIT 1";
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
	if(!$id)//insert
	{
		unset($post['save']);
		if($table=='upm')
		{	
			$post['last_update'] = 	date('Y-m-d',$now);		
		}
		if($table=='products' && $post['name']=='')
		{
			softDieSession('Cannot insert '.$table.' entry. Product name cannot empty.');
			return 0;
		}
		$postKeys = array_keys($post);
		$post = array_map(am,$postKeys,array_values($post));
		$query = "insert into $table (".implode(',',$postKeys).") values(".implode(',',$post).")";
		if(mysql_query($query))
		{
			if($table=='products')
			{
				ob_start();
				$_GET['id'] = mysql_insert_id();
				require 'index_product.php';
				ob_end_clean();
			}
			if($table=='areas')
			{
				ob_start();
				$_GET['id'] = mysql_insert_id();
				require 'index_area.php';
				ob_end_clean();				
			}
			return 1;
		}
		else
		{
			softDieSession('Cannot insert '.$table.' entry');
			return 0;
		}
	}
	else//update
	{
		if($table=='upm')
		{
			
			$query = "select * from $table where id=$id";
			$res = mysql_query($query)or softDieSession('Updating invalid row');
			while($row = mysql_fetch_assoc($res))
			{
				$historyArr = $row;
			}
			//last update not needed for upm_history
			unset($historyArr['last_update']);
			unset($historyArr['status']);
			//remove post action name from insert query.
			unset($post['save']);
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
		$query = "update $table set ".implode(',',$post)." where id=".$id;
		if(mysql_query($query))
		{
			//fire success actions upon successful save.
			if($table=='upm')
			{
				foreach($historyArr as $history)
				{
					$query = "insert into upm_history (".implode(',',array_keys($history)).") values (".implode(',',$history).")";
					mysql_query($query)or softdieSession('Cannot update history for upm id '.$historyArr['id']);
				}				
			}
			
			if($table=='products')
			{
				ob_start();
				require 'index_product.php';
				ob_end_clean();
			}
			if($table=='areas')
			{
				ob_start();
				require 'index_area.php';
				ob_end_clean();
			}			
		}
		else
		{
			softDieSession('Cannot update '.$table.mysql_error().' entry');		
		}
		
	}
}

/**
 * @name pagePagination
 * @tutorial Provides pagination output for the upm input page.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function pagePagination($limit,$totalCount,$table,$script,$ignoreFields=array(),$options=array('import'=>true))
{
	global $page;	
	$formOnSubmit = isset($options['formOnSubmit'])?$options['formOnSubmit']:null;
	if(isset($_GET['next']))
	$page = $_GET['oldval']+1;
	elseif(isset($_GET['back']))
	$page = $_GET['oldval']-1;	
	elseif(isset($_GET['jump']))
	$page = $_GET['jumpno']-1;
	
	
	$visualPage = $page+1;
	$maxPage = ceil($totalCount/$limit);
	
	$oldVal = $page;
		
	$pend  = ($visualPage*$limit)<=$totalCount?$visualPage*$limit:$totalCount;
	$pstart = (($pend - $limit+1)>0)?$pend - $limit+1:0;
	
	echo '<form name="pager" method="get" '.$formOnSubmit.' action="'.$script.'.php"><fieldset class="floatl">'
		 	. '<legend>Page ' . $visualPage . ' of '.$maxPage
			. ': records '.$pstart.'-'.$pend.' of '.$totalCount
			. '</legend>'
			. '<input type="submit" name="jump" value="Jump" style="width:0;height:0;border:0;padding:0;margin:0;"/> '
			. '<input name="page" type="hidden" value="' . $page . '" /><input name="search" type="hidden" value="1" />'
			. ($pstart > 1 ? '<input type="submit" name="back" value="&lt; Back"/>' : '')
			. ' <input type="text" name="jumpno" value="' . $visualPage . '" size="6" />'
			. '<input type="submit" name="jump" value="Jump" /> '
			. ($visualPage<$maxPage?'<input type="submit" name="next" value="Next &gt;" />':'')
			. '<input type="hidden" value="'.$oldVal.'" name="oldval">'
			. '</fieldset>'
			. '<fieldset class="floatl">'
			. '<legend> Actions: </legend>'
			. '<input type="submit" value="Add New Record" name="add_new_record">';
		if($options['import'])
		echo '<input type="submit" value="Import" name="import">';
		echo '</fieldset>';
	//$_SESSION['page_errors'] = array('Sql error','What is this error.');
	if(is_array($_SESSION['page_errors']) && count($_SESSION['page_errors'])>0)
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
	echo  '<fieldset class="">'
			. '<legend> Search: </legend>';

	$query = "SHOW COLUMNS FROM $table";
	$res = mysql_query($query);
	echo '<table>';
	while($row = mysql_fetch_assoc($res))
	{
		if(!in_array($row['Field'],$ignoreFields))
		{
			$dbVal = null;
			if(isset($_GET) && isset($_GET['search_'.$row['Field']]) && $_GET['search_'.$row['Field']] !='' && !isset($_GET['reset']))
			{
				$dbVal = $_GET['search_'.$row['Field']];
			}
			echo '<tr><td>'.ucwords(implode(' ',explode('_',$row['Field']))) .' : </td><td>'.input_tag($row,$dbVal,array('null_options'=>true,'name_index'=>'search')).'</td></tr>';
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
	$searchType = calculateSearchType($db->sources,unserialize(base64_decode($searchData)));
	$insertEdit = 'Insert';
	$formOnSubmit = isset($options['formOnSubmit'])?$options['formOnSubmit']:null;
	$formStyle = isset($options['formStyle'])?$options['formStyle']:null;
	$mainTableStyle = isset($options['mainTableStyle'])?$options['mainTableStyle']:null;
	$addEditGlobalInputStyle = isset($options['addEditGlobalInputStyle'])?$options['addEditGlobalInputStyle']:null;
	if($id)
	{
		$insertEdit = 'Edit';
		
		if($table=='upm')
		$query = "SELECT u.id,u.event_type,u.event_description,u.event_link,u.result_link,p.name AS product,u.corresponding_trial,u.start_date,u.start_date_type,u.end_date,u.end_date_type,u.last_update,p.id as product_id FROM upm u LEFT JOIN products p ON u.product=p.id WHERE u.id=$id";
		else
		$query = "SELECT * FROM $table WHERE id=$id";
		$res = mysql_query($query) or die('Cannot update this '.$table.' id');
		while($row = mysql_fetch_assoc($res))
		{
			$upmDetails = $row;
			$upm_product_id = isset($upmDetails['product_id'])?$upmDetails['product_id']:null;
		}
	}
	
	$columns = array();
	$query = "SHOW COLUMNS FROM $table";
	$res = mysql_query($query)or die('Cannot fetch column names from '.$table.' table.');
	$i=0;
	

	
	echo '<div class="clr">';
	echo '<fieldset>';
	echo '<legend> '.$insertEdit.': </legend>';
	echo '<form '.$formStyle.' id="umpInput" name="umpInput" '.$formOnSubmit.' method="get" action="'.$script.'.php">';
	echo '<table '.$mainTableStyle.'>';
	while($row = mysql_fetch_assoc($res))
	{
		if($i==0)
		{
			echo '<input type="hidden" name="id" value="'.$id.'"/>';
			$i++;
			continue;
		}
		if(in_array($row['Field'], $skipArr))
		{
			continue;
		}
		$dbVal = isset($upmDetails[$row['Field']])?$upmDetails[$row['Field']]:null;
		if(isset($options['saveStatus'])&& $options['saveStatus']===0)
		{
			$dbVal = (isset($_GET[$row['Field']]) && $_GET[$row['Field']]!='')?$_GET[$row['Field']]:$dbVal;
			
		}
		
		if($row['Field'] == 'searchdata')
		{
			if($searchType ===false && ($script=='areas' || $script=='products'))
			{
				$searchType = calculateSearchType($db->sources,unserialize(base64_decode($dbVal)));
			}			
			echo '<tr><td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td>';
			echo '<td>';
			echo input_tag($row,$dbVal,array('table'=>$table,'id'=>$id,'callFrom'=>'addedit'));
			echo '</td></tr>';	
			$i++;
			continue;			
		}			
		echo '<tr>';
		echo '<td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td><td>'.input_tag($row,$dbVal,array('style'=>$addEditGlobalInputStyle)).'</td>';
		echo '</tr>';
	}
	if(($script == 'products' || $script == 'areas') && $searchType!==false)
	{
		echo '<tr>';
		echo '<td>Type : </td><td>'.($searchType==1?'Auto':'SemiAuto').'</td>';
		echo '</tr>';		
	}
	if($options['deletebox']===true && $id)
	{
		$altTitle='Delete';
		if($script=='products')
		{
			$upmReferenceCount = getProductUpmAssociation($id);
			$MHMReferenceCount = getMHMAssociation($id,'product');
			$disabled = ($upmReferenceCount>0 || $MHMReferenceCount>0)?true:false;
			$altTitle = $disabled?'Cannot delete product as it is linked to other upms/MHM\'s. See References.':$altTitle;
			echo '<tr>';
			echo '<td>References : </td><td>'.$upmReferenceCount.' UPM</td>';
			echo '</tr>';	
			echo '<tr>';
			echo '<td>References : </td><td>'.$MHMReferenceCount.' MHM</td>';
			echo '</tr>';
		}
		if($script=='areas')
		{
			$MHMReferenceCount = getMHMAssociation($id,'product');
			$disabled = ($MHMReferenceCount>0)?true:false;
			$altTitle = $disabled?'Cannot delete area as it is linked to other MHM\'s. See References.':$altTitle;
			echo '<tr>';
			echo '<td>References : </td><td>'.$MHMReferenceCount.' MHM</td>';
			echo '</tr>';
		}		
		echo '<tr>';
		echo '<td>Delete : </td><td>'.input_tag(null,null,array('deletebox'=>true,'id'=>$id,'disabled'=>$disabled,'alttitle'=>$altTitle)).'</td>';
		echo '</tr>';
	}
	if($searchData)
	echo '<input type="hidden" name="searchdata" value="'.$searchData.'">';
	if($table=='upm')
	{
		//echo '<input type="hidden" id="product_id" name="product_id" value="'.$upm_product_id.'">';
	}
	echo '<tr>&nbsp;<td></td><td><input name ="save" type="submit" value="Save"/></td>';
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
		$v = unpadnct($v);
	}		
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date','oldproduct');
	if(in_array($k,$explicitNullFields) && $v=='')
	{
		$v = 'null';
		return mysql_real_escape_string($v);
	}
	return "'".mysql_real_escape_string($v)."'";
}
function am1($k,$v)
{
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date','oldproduct','product','searchdata');
	if($k=='corresponding_trial')
	{
		$v = unpadnct($v);
	}		
	if(in_array($k,$explicitNullFields) && $v=='')
	{
		$v = 'null';
		return $k."=".mysql_real_escape_string($v);
	}	
	return $k."='".mysql_real_escape_string($v)."'";
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
		$v = unpadnct($v);
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
	$query = "select count(u.id) as cnt from upm u left join products p on u.product=p.id where p.id=$id";
	$result = mysql_query($query);
	while($row = mysql_fetch_assoc($result))
	{
		return $row['cnt'];
	}
	
}

/**
* @name getMHMAssociation
* @tutorial Provides count of mhm's linked to areas/products
* @param int $id areas/products id.
* @param string $type[areas/products]
* @author Jithu Thomas
*/
function getMHMAssociation($id,$type)
{
	$query = "select count(h.type_id) as cnt from rpt_masterhm_headers h where h.type='$type' and h.type_id='$id'";
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
	$out = '<table>';
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
 * @tutorial Logs die messages into session log for future usage.
 * @param $out error output.
 * @author Jithu Thomas
 */	
function softDieSession($out,$raw=0)
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
	$query = "select $searchdata from $table where id=$id";
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
	$success = $fail = $skip = 0;
	foreach($xmlImport->getElementsByTagName('Product') as $product)
	{
		$importVal = array();
		$product_id = $product->getElementsByTagName('product_id')->item(0)->nodeValue;
		$name = $product->getElementsByTagName('name')->item(0)->nodeValue;
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
		
		foreach($product->getElementsByTagName('Institutions') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('Institution') as $brandName)
			{
				$company = $brandName->getElementsByTagName('name')->item(0)->nodeValue;
			}
		}		
		$brand_names = array();
		foreach($product->getElementsByTagName('ProductBrandNames') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('ProductBrandName') as $brandName)
			{
				($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$brand_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
			}
		}
		$brand_names = implode(',',$brand_names);
		
		$generic_names = array();
		foreach($product->getElementsByTagName('ProductGenericNames') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('ProductGenericName') as $brandName)
			{
				($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$generic_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
			}
		}
		$generic_names = implode(',',$generic_names);
				
		$code_names = array();
		foreach($product->getElementsByTagName('ProductCodeNames') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('ProductCodeName') as $brandName)
			{
				($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$code_names[] = $brandName->getElementsByTagName('name')->item(0)->nodeValue:null;
			}
		}
		$code_names = implode(',',$code_names);

		$approvals = $product->getElementsByTagName('approvals')->item(0)->nodeValue;
		$xmldump = $xmlImport->saveXML($product);
		
		
		$importVal = array('LI_id'=>$product_id,'name'=>$name,'comments'=>$comments,'product_type'=>$product_type,'licensing_mode'=>$licensing_mode,'administration_mode'=>$administration_mode,'discontinuation_status'=>$discontinuation_status,'discontinuation_status_comment'=>$discontinuation_status_comment,'is_key'=>$is_key,'is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'company'=>$company,'brand_names'=>$brand_names,'generic_names'=>$generic_names,'code_names'=>$code_names,'approvals'=>$approvals,'xml'=>$xmldump);
		//ob_start();
		$out = saveData(null,$table,1,$importKeys,$importVal,$k);
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
		//ob_end_clean();
	}
	return array('success'=>$success,'fail'=>$fail,'skip'=>$skip);
}