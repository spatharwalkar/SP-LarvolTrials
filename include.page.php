<?php
/**
 * @name contentListing
 * @tutorial Provides output of all the table entries in the upm table based on the params $start and $limit
 * @param int $start Start value of sql select query.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function contentListing($start=0,$limit=50,$table,$script,$ignoreFields=array())
{
global $deleteFlag;
	
//get search params
$where = calculateWhere();

//calculate sortable fields
$query = "SHOW COLUMNS FROM $table";
$res = mysql_query($query);
$sortableRows = array();
while($row = mysql_fetch_assoc($res))
{
	$type = $row['Type'];
	if(strstr($type,'int(') || $type=='date')
	{
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

if($_GET['no_sort']!=1)
$query = "select * from $table $where $currentOrderBy $currentSortOrder limit $start , $limit";
else
$query = "select * from $table $where limit $start , $limit";

$res = mysql_query($query) or die('Cannot get '.$table.' data.'.$query);
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
function calculateWhere()
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
	$where = calculateWhere();
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
	mysql_query($query) or softdie('Cannot delete '.$table.'. '.$query);
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
	$type = $row['Type'];
	if(substr($type,0,4)=='enum')
	$type = 'enum';
	if(isset($options['table']) && $options['table']!='' && $row['Field']=='searchdata')
	{
		$type = 'searchdata';
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
			return '<select name="'.$nameIndex.$row['Field'].'">'.implode('',$optionArr).'</select>';
			break;
			
		case 'searchdata':
			$id = $options['id']?$options['id']:null;
			$table = $options['table'];
			if($searchData!='')
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
			else
			{
				$modifier = '';
				$delete = '';
			}
			$task = ($dbVal=='')?'Add':'Edit';
			return '<a href="search.php?'.$table.'='.$id.'"><img src="images/'.$img.'" title="'.$task.' Search Data" alt="'.$task.' Search Data"/></a>&nbsp;'.$modifier.$delete;
			break;
			
		default:
			$dateinput = (strpos($row['Field'], 'date') !== false) ? ' class="jdpicker"' : '';
			return '<input type="text" value="'.$dbVal.'" name="'.$nameIndex.$row['Field'].'" id="'.$nameIndex.$row['Field'].'"' . $dateinput . '/>';
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
			softdie('Cannot import row data at line '.$line.'<br/>');
			return false;
		}
		
	}
	
	if($import==1 && $table=='products')
	{
		ini_set('max_execution_time','360000');	//100 hours
		//check for insert update case
		$query = "select id from products where LI_id='{$importVal['LI_id']}' OR name='{$importVal['name']}' limit 1";
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
			$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
			$query = "insert into $table (".implode(',',$importKeys).") values (".implode(',',$importVal).")";
		}
		if(mysql_query($query))
		{
			return true;
		}
		else
		{
			softdie('Cannot import product id '.$importVal['LI_id'].'<br/>'.$query.'<br/>');
			//softdie('Cannot import product id '.$importVal['LI_id'].'<br/>');
			return false;
		}
	}
	
	if(isset($post['delsearch']) && is_array($post['delsearch']))
	{
		foreach($post['delsearch'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			if($db->user->userlevel != 'user')
				mysql_query("UPDATE $table SET searchdata='' WHERE id=$id LIMIT 1") or die('Bad SQL query deleting searchdata');
		}
		unset($post['delsearch']);
	}
	
	$id = ($post['id'])?$post['id']:null;	
	if(!$id)//insert
	{
		array_pop($post);
		if($table=='upm')
		{	
			$post['last_update'] = 	date('Y-m-d',$now);		
		}
		$postKeys = array_keys($post);
		$post = array_map(am,$postKeys,array_values($post));
		$query = "insert into $table (".implode(',',$postKeys).") values(".implode(',',$post).")";
		mysql_query($query)or die('Cannot insert '.$table.' entry');
	}
	else//update
	{
		if($table=='upm')
		{
			
			$query = "select * from $table where id=$id";
			$res = mysql_query($query)or softdie('Updating invalid row');
			while($row = mysql_fetch_assoc($res))
			{
				$historyArr = $row;
			}
			//copy new index added from last_update for the upm_history table
			$historyArr['added'] = $historyArr['last_update'];
			//last update not needed for upm_history
			unset($historyArr['last_update']);
			//superceded is the current date 
			$historyArr['superceded']=date('Y-m-d',$now);
			$historyArrKeys = array_keys($historyArr);		
			$historyArr = array_map(am,array_keys($historyArr),$historyArr);
			$query = "insert into upm_history (".implode(',',$historyArrKeys).") values (".implode(',',$historyArr).")";
			mysql_query($query)or softdie('Cannot update history for upm id '.$historyArr['id']);
			//remove post action name from insert query.
			array_pop($post);	
			$post['last_update'] = date('Y-m-d',$now);
			$post = array_map(am1,array_keys($post),array_values($post));
		}
		else
		{
			//remove post action name from insert query.
			array_pop($post);
			$post = array_map(am1,array_keys($post),array_values($post));
		}
		$query = "update $table set ".implode(',',$post)." where id=".$id;
		mysql_query($query)or die('Cannot update '.$table.' entry');		
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
	
	echo '<form name="pager" method="get" action="'.$script.'.php"><fieldset class="floatl">'
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
	$insertEdit = 'Insert';
	$formOnSubmit = isset($options['formOnSubmit'])?$options['formOnSubmit']:null;
	if($id)
	{
		$insertEdit = 'Edit';
		$query = "SELECT * FROM $table WHERE id=$id";
		$res = mysql_query($query) or die('Cannot update this upm id');
		while($row = mysql_fetch_assoc($res))
		{
			$upmDetails = $row;
		}
	}
	
	$columns = array();
	$query = "SHOW COLUMNS FROM $table";
	$res = mysql_query($query)or die('Cannot fetch column names from '.$table.' table.');
	$i=0;
	

	
	echo '<div class="clr">';
	echo '<fieldset>';
	echo '<legend> '.$insertEdit.': </legend>';
	echo '<form name="umpInput" '.$formOnSubmit.' method="get" action="'.$script.'.php">';
	echo '<table>';
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
		
		if($row['Field'] == 'searchdata')
		{
			echo '<tr><td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td>';
			echo '<td>';
			echo input_tag($row,$dbVal,array('table'=>$table,'id'=>$id,'callFrom'=>'addedit'));
			echo '</td></tr>';	
			$i++;
			continue;			
		}			
		echo '<tr>';
		echo '<td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td><td>'.input_tag($row,$dbVal).'</td>';
		echo '</tr>';
	}
	if($searchData)
	echo '<input type="hidden" name="searchdata" value="'.$searchData.'">';
	echo '<tr>&nbsp;<td></td><td><input name ="save" type="submit" value="Save"/></td>';
	echo '</table>';
	echo '</form>';
	echo '</fieldset>';
	echo '</div>';
}

function am($k,$v)
{
	if($k=='corresponding_trial')
	{
		$v = unpadnct($v);
	}		
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date');
	if(in_array($k,$explicitNullFields) && $v=='')
	{
		$v = 'null';
		return mysql_real_escape_string($v);
	}
	return "'".mysql_real_escape_string($v)."'";
}
function am1($k,$v)
{
	$explicitNullFields = array('corresponding_trial','event_link','result_link','start_date','end_date');
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