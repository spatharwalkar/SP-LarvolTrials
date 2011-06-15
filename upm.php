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
require('header.php');
echo '<div class="error">Under Development</div>';


//declare all globals
global $db;
global $page;
//set docs per list
$limit = 50;
$totalCount = getTotalUpmCount();
$maxPage = $totalCount%$limit;
if(!isset($_POST['oldval']))
$page=0;




//save operation
if($_POST['save']=='Save')
{
	saveUpm($_POST);
}
//

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

//pagination
upmPagination($limit,$totalCount);
//pagination controller



echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_POST['add_new_record']=='Add New Record' || $_GET['id'])
{
	$id = ($_GET['id'])?$_GET['id']:null;
	echo '<div>';
	addEditUpm($id);
	echo '</div>';
}

//import controller
if($_POST['import']=='Import' || $_POST['uploadedfile'])
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
	
	if(isset($_POST['next']))
	$page = $_POST['oldval']+1;
	elseif(isset($_POST['back']))
	$page = $_POST['oldval']-1;	
	elseif(isset($_POST['jump']))
	$page = $_POST['jumpno']-1;
	
	
	$visualPage = $page+1;
	$maxPage = ceil($totalCount/$limit);
	
	$oldVal = $page;
		
		$pend  = ($visualPage*$limit)<=$totalCount?$visualPage*$limit:$totalCount;
		$pstart = (($pend - $limit+1)>0)?$pend - $limit+1:0;
		echo('<form name="pager" method="post" action="upm.php"><fieldset class="floatl">'
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
				. '</fieldset>'
				. '</form>');

				
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
$query = "select * from upm limit $start, $limit";
$res = mysql_query($query) or die('Cannot get upm data');
$i=0;
$skip=0;
echo '<div></div>';
echo '<table border="1">';
while ($row = mysql_fetch_assoc($res))
{
	
	if($i==0)
	{

		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			echo '<th >';
			echo ucwords(implode(' ',explode('_',$columnName)));
			echo '</th>';
			$i++;
		}
		echo '</tr>';
	
		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			if($columnName == 'id')
			{
				echo '<td><a href="upm.php?id='.$v.'">';
				echo $v;
				echo '</td></a>';				
			}
			else 
			{
				echo '<td>';
				echo $v;
				echo '</td>';
			}
		}	
		echo '</tr>';	
	}
	else
	{
		echo '<tr style="text-align:center">';
		foreach($row as $columnName=>$v)
		{
			if($columnName == 'id')
			{
				echo '<td><a href="upm.php?id='.$v.'">';
				echo $v;
				echo '</td></a>';				
			}
			else 
			{
				echo '<td>';
				echo $v;
				echo '</td>';
			}
		}
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
	if($id)
	{
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
	echo '<form name="umpInput" method="post" action="upm.php">';
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
	return "'".mysql_real_escape_string($v)."'";
}
function am1($k,$v)
{
	return $k."='".mysql_real_escape_string($v)."'";
}
function am2($v,$dbVal)
{
	if($dbVal== $v)
	return '<option value="'.$v.'" selected="selcted">'.$v.'</option>';
	else 
	return '<option value="'.$v.'">'.$v.'</option>';
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
function input_tag($row,$dbVal)
{
	$type = $row['Type'];
	if(substr($type,0,4)=='enum')
	$type = 'enum';
	
	switch($type)
	{
		case 'enum':
			$type1 = $row['Type'];
			$search = array('enum','(',')','\'');
			$replace = array('','','','');
			$type1 = str_replace($search, $replace, $type1);
			$optionArr = explode(',',$type1);
			$optionArr = array_map(am2,$optionArr,array_fill(0,count($optionArr),$dbVal));
			return '<select name="'.$row['Field'].'">'.implode('',$optionArr).'</select>';
			break;
			
		default:
			return '<input type="text" value="'.$dbVal.'" name="'.$row['Field'].'"/>';
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
$query = "select count(id) as cnt from upm";
$res = mysql_query($query);
$count = mysql_fetch_row($res);
return $count[0];
}
