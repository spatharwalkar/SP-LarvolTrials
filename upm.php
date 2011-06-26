<?php

require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
require_once('include.util.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
//declare all globals
global $db;
global $page;
global $deleteFlag;
//calulate delete flag
if($db->user->userlevel == 'admin')
$deleteFlag = 1;
else
$deleteFlag = null;

//reset controller
if($_GET['reset'])
header('Location: ' . urlPath() . 'upm.php');
require('header.php');
echo('<script type="text/javascript">
function upmdelsure(){ return confirm("Are you sure you want to delete this upm?"); }
</script>');

echo '<div class="error">Under Development</div>';
//Start controller area
//save operation controller
if($_GET['save']=='Save')
{
	saveUpm($_GET);
}
//delete controller
if(isset($_GET['deleteId']) && is_numeric($_GET['deleteId']) && $deleteFlag)
{
	deleteUpm($_GET['deleteId']);
	$pattern = '/(\\?)(deleteId).*?(\\d+)/is';
	$_SERVER['REQUEST_URI'] =  preg_replace($pattern, '', $_SERVER['REQUEST_URI']);
	$_SERVER['REQUEST_URI'] = str_replace('upm.php&', 'upm.php?', $_SERVER['REQUEST_URI']);
}
//import controller
if(isset($_FILES['uploadedfile']) && $_FILES['uploadedfile']['size']>1)
{
	$tsv = $_FILES['uploadedfile']['tmp_name'];
	$row = file($tsv,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
	$success = 0;
	$fail = 0;
	foreach($row as $k=>$v)
	{
		if($k==0)
		{
			$importKeys = explode("\t",$v);
		}
		else 
		{
			$importVal = explode("\t",$v);
			$importVal = array_map(function ($v){return "'".mysql_real_escape_string($v)."'";},$importVal);
			if(saveUpm(null,1,$importKeys,$importVal,$k))
			{
				$success ++;
			}
			else 
			{			
				$fail ++;
			}
		}

	}
	echo 'Imported '.$success.' records, Failed entries '.$fail;
}
//End controller area


//set docs per list
$limit = 50;
$totalCount = getTotalUpmCount();
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
$page=0;

//pagination
upmPagination($limit,$totalCount);
//pagination controller

echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_GET['add_new_record']=='Add New Record' || $_GET['id'] && !$_GET['save'])
{
	$id = ($_GET['id'])?$_GET['id']:null;
	echo '<div>';
	addEditUpm($id);
	echo '</div>';
}

//import controller
if($_GET['import']=='Import' || $_GET['uploadedfile'])
{
	importUpm();
}

//normal upm listing
$start = $page*$limit;
upmListing($start,$limit);
echo '</div>';
echo '</html>';


/**
 * @name upmPagination
 * @tutorial Provides pagination output for the upm input page.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function upmPagination($limit,$totalCount)
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
	
	echo '<form name="pager" method="get" action="upm.php"><fieldset class="floatl">'
		 	. '<legend>Page ' . $visualPage . ' of '.$maxPage
			. ': records '.$pstart.'-'.$pend.' of '.$totalCount
			. '</legend>'
			. '<input type="submit" name="jump" value="Jump" style="width:0;height:0;border:0;padding:0;margin:0;"/> '
			. '<input name="page" type="hidden" value="' . $page . '" /><input name="search" type="hidden" value="1" />'
			. ($pstart > 1 ? '<input type="submit" name="back" value="&lt; Back" onclick="javascript:history(-1);return false;" />' : '')
			. ' <input type="text" name="jumpno" value="' . $visualPage . '" size="6" />'
			. '<input type="submit" name="jump" value="Jump" /> '
			. '<input type="submit" name="next" value="Next &gt;" />'
			. '<input type="hidden" value="'.$oldVal.'" name="oldval">'
			. '</fieldset>'
			. '<fieldset class="floatl">'
			. '<legend> Actions: </legend>'
			. '<input type="submit" value="Add New Record" name="add_new_record">'
			. '<input type="submit" value="Import" name="import">'
			. '</fieldset>';
			
	echo  '<fieldset class="">'
			. '<legend> Search: </legend>';

	$query = "SHOW COLUMNS FROM upm";
	$res = mysql_query($query);
	echo '<table>';
	while($row = mysql_fetch_assoc($res))
	{
		$dbVal = null;
		if(isset($_GET) && isset($_GET['search_'.$row['Field']]) && $_GET['search_'.$row['Field']] !='' && !isset($_GET['reset']))
		{
			$dbVal = $_GET['search_'.$row['Field']];
		}
		echo '<tr><td>'.ucwords(implode(' ',explode('_',$row['Field']))) .' : </td><td>'.input_tag($row,$dbVal,array('null_options'=>true,'name_index'=>'search')).'</td></tr>';
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
 * @name upmListing
 * @tutorial Provides output of all the upm entries in the upm table based on the params $start and $limit
 * @param int $start Start value of sql select query.
 * @param int $limit The total limit of records defined in the controller.
 * @author Jithu Thomas
 */
function upmListing($start=0,$limit=50)
{
global $deleteFlag;
	
//get search params
$where = calculateWhere();

//calculate sortable fields
$query = "SHOW COLUMNS FROM upm";
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
	elseif($_GET['search'] && $_GET['sort_order']=='DESC')
	{
		$sortOrder=$_GET['sort_order'];
	}	
	elseif(current($sortArr)=='no_sort')
	{
		$sortOrder = null;
		$noSort = '&no_sort=1';
		$sortImg = $sortOrder;
	}
	else
	{
		$sortOrder = current($sortArr);
		$sortImg = $sortOrder;
	}


}
if($sortOrder ==null && !$noSort)
{
'ASC';
$sortImg = 'ASC';
}
else
{
$sortOrder;
}

if($_GET['no_sort']!=1)
$query = "select * from upm $where $currentOrderBy $currentSortOrder limit $start , $limit";
else
$query = "select * from upm $where limit $start , $limit";

$res = mysql_query($query) or die('Cannot get upm data.'.$query);
$i=0;
$skip=0;

$deleteParams = substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'],'?')+1);
$deleteConnector = '&';
echo '<div></div>';
echo '<table border="1" width="99%">';
while ($row = mysql_fetch_assoc($res))
{
	
	if($i==0)
	{

		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			echo '<th>';
			$params = null;
			$params = substr($_SERVER['REQUEST_URI'],strpos($_SERVER['REQUEST_URI'],'upm.php'));
			$params = $params!='upm.php'?substr($params, 0,strpos($params,'order_by')-1):$params;
			
			$connector = $params!='upm.php'?'&':'?';
			if($_GET['order_by']==$columnName && in_array($columnName,$sortableRows))
			$url = urlPath().$params.$connector.'order_by='.$columnName.'&sort_order='.$sortOrder.$noSort;
			elseif(in_array($columnName,$sortableRows))
			$url = urlPath().$params.$connector.'order_by='.$columnName.'&sort_order=ASC';
			else
			$url=null;
			
			if($url)
			echo '<a href="'.$url.'">';
			echo ucwords(implode(' ',explode('_',$columnName)));
			if($url)
			echo '<img src="images/'.strtolower($sortImg).'.png"/></a>';
			echo '</th>';
			$i++;
		}
		if($deleteFlag)
		echo '<th>Del</th>';
		echo '</tr>';
	
		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			if($columnName == 'id')
			{
				$upmId = $v;
				echo '<td><a href="upm.php?id='.$v.'">';
				echo $v;
				echo '</a></td>';				
			}else
			if($columnName == 'event_link' || $columnName == 'result_link')
			{
				$upmId = $v;
				echo '<td nowrap style="max-width:150px;overflow:hidden"><a  href="'.$v.'">';
				echo $v;
				echo '</a></td>';			
			}			
			else 
			{
				echo '<td>';
				echo $v;
				echo '</td>';
			}
		}	
		if($deleteFlag)
		echo '<td><a onclick="return upmdelsure();" href="upm.php?deleteId='.$upmId.'&'.$deleteParams.'"><img src="images/not.png"/ alt="Delete" style="border:0"></a></td>';
		echo '</tr>';	
	}
	else
	{
		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			if($columnName == 'id')
			{
				$upmId = $v;
				echo '<td><a href="upm.php?id='.$v.'">';
				echo $v;
				echo '</a></td>';				
			}else
			if($columnName == 'event_link' || $columnName == 'result_link')
			{
				$upmId = $v;
				echo '<td nowrap style="max-width:150px;overflow:hidden" ><a  href="'.$v.'">';
				echo $v;
				echo '</a></td>';				
			}	
			else 
			{
				echo '<td>';
				echo $v;
				echo '</td>';
			}
		}
		if($deleteFlag)
		echo '<td><a onclick="return upmdelsure();" href="upm.php?deleteId='.$upmId.'&'.$deleteParams.'"><img src="images/not.png"/ alt="Delete" style="border:0"></a></td>';
		echo '</tr>';				
	}
	
	
$i++;
}
echo '</table>';
echo '<br/>';
}

/**
 * @name addEditUpm
 * @tutorial Provides output of the insert/edit form.
 * @param int $id If the param $id is present edit option is activated.
 * @author Jithu Thomas
 */
function addEditUpm($id)
{
	$insertEdit = 'Insert';
	if($id)
	{
		$insertEdit = 'Edit';
		$query = "SELECT * FROM upm WHERE id=$id";
		$res = mysql_query($query) or die('Cannot update this upm id');
		while($row = mysql_fetch_assoc($res))
		{
			$upmDetails = $row;
		}
	}
	
	$columns = array();
	$query = "SHOW COLUMNS FROM upm";
	$res = mysql_query($query)or die('Cannot fetch column names from upm table.');
	$i=0;
	
	/*	
	$skip array if any fields in the upm table needs to be skipped when 
	showing the upm entry form just append in this array
	*/
	$skipArr = array('last_update');
	
	echo '<div class="clr">';
	echo '<fieldset>';
	echo '<legend> '.$insertEdit.': </legend>';
	echo '<form name="umpInput" method="get" action="upm.php">';
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
		echo '<tr>';
		echo '<td>'.ucwords(implode(' ',explode('_',$row['Field']))).' : </td><td>'.input_tag($row,$dbVal).'</td>';
		echo '</tr>';
	}
	echo '<tr>&nbsp;<td></td><td><input name ="save" type="submit" value="Save"/></td>';
	echo '</table>';
	echo '</form>';
	echo '</fieldset>';
	echo '</div>';
}

function am($k,$v)
{
	$explicitNullFields = array('corresponding_trial');
	if(in_array($k,$explicitNullFields) && $v=='')
	{
		$v = 'null';
		return mysql_real_escape_string($v);
	}
	return "'".mysql_real_escape_string($v)."'";
}
function am1($k,$v)
{
	$explicitNullFields = array('corresponding_trial');
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
	$explicitNullFields = array('corresponding_trial');
	if(in_array($k,$explicitNullFields) && !is_numeric($v))
	{
		
		$v = 'null';
	}
	return $v;
}


/**
 * @name saveUpm
 * @tutorial Saves the upm entry/edit forms and inputs from the tab seperated file inputs.
 * @param array $post Post array.
 * @param int $import =0 for normal form save and = 1 for tab seperated input.
 * @param array $importKeys Keys for import relates to the fields in the upm table.
 * @param array $importVal Values for each column in the upm table corresponds to a single line in hte import file.
 * @param int $line Line number for error and notice usage. Related to import functionality.
 * @author Jithu Thomas
 */
function saveUpm($post,$import=0,$importKeys=array(),$importVal=array(),$line=null)
{
	global $now;
	//import save
	if($import ==1)
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
	
	$id = ($post['id'])?$post['id']:null;	
	if(!$id)//insert
	{
		array_pop($post);	
		$post['last_update'] = 	date('Y-m-d',$now);		
		$postKeys = array_keys($post);
		$post = array_map(am,$postKeys,array_values($post));
		$query = "insert into upm (".implode(',',$postKeys).") values(".implode(',',$post).")";
		mysql_query($query)or die('Cannot insert upm entry');
	}
	else//update
	{
		$query = "select * from upm where id=$id";
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
		$query = "update upm set ".implode(',',$post)." where id=".$id;
		mysql_query($query)or die('Cannot update upm entry');		
	}
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
	$type = $row['Type'];
	if(substr($type,0,4)=='enum')
	$type = 'enum';
	
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
			
		default:
			return '<input type="text" value="'.$dbVal.'" name="'.$nameIndex.$row['Field'].'"/>';
			break;
	}
}


/**
 * @name importUpm
 * @tutorial Outputs the upm import form.
 * @author Jithu Thomas
 */
function importUpm()
{
	echo '<div class="clr">';
	echo '<fieldset>';
	echo '<legend> Import : </legend>';
	echo '<form name="upm_import" method="post" enctype="multipart/form-data" action="upm.php">';
	echo '<input name="uploadedfile" type="file" /><br />
		 <input type="submit"  value="Upload File" />';
	echo '</form>';
	echo '</fieldset>';
	echo '</div>';	

}
/**
 * @name getTotalUpmCount
 * @tutorial Outputs the total upm count.
 * @author Jithu Thomas
 */
function getTotalUpmCount()
{
global $db;
$where = calculateWhere();
$query = "select count(id) as cnt from upm $where";
$res = mysql_query($query);
$count = mysql_fetch_row($res);
return $count[0];
}

/**
 * @name calculateWhere
 * @tutorial Outputs the WHERE query substring.
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
 * @name deleteUpm
 * @tutorial Deletes the upm entry for the specific id.
 * @param $id The id field in the upm table.
 * @author Jithu Thomas
 */
function deleteUpm($id)
{
	$query = "delete from upm where id=$id";
	mysql_query($query) or softdie('Cannot delete upm. '.$query);
	echo 'Successfully deleted upm.';
	
}