<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
require_once('include.util.php');
require_once('upmSearchCount.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');
echo '<div class="error">Under Development</div>';


//declare all globals
global $db;

//set docs per list
$limit = 50;
$totalCount = getTotalUpmCount();
$maxPage = $totalCount%$limit;
$page = 0;


//controller
	//pr($_POST);
	
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
upmPagination($page,$limit);
//pagination controller


//normal listing
echo '<br/>';
echo '<div class="clr">';
if($_POST['add_new_record']=='Add New Record' || $_GET['id'])
{
	$id = ($_GET['id'])?$_GET['id']:null;
	echo '<div>';
	addEditUpm($id);
	echo '</div>';
}

if($_POST['import']=='Import' || $_POST['uploadedfile'])
{
	importUpm();
}

upmListing($page,$limit);
echo '</div>';




function upmPagination($page,$limit)
{
		$visualPage = $page+1;
		$pend  = $visualPage*$limit;
		echo('<form name="pager" method="post" action="upm.php"><fieldset>'
	//		 	. '<legend>Page ' . $page . ' of ' . ceil($results / $db->set['results_per_page'])
			 	. '<legend>Page ' . $visualPage . ' '
	//			. ': records ' . $pstart . '-' . (($page*$db->set['results_per_page']>$results)?$results:$pend) . ' of ' . $results
				. ': records '.$visualPage.'-'.$pend.' of <iframe src="upmSearchCount.php?web=1"></iframe>'
				. '</legend>'
				. '<input type="submit" name="jump" value="Jump" style="width:0;height:0;border:0;padding:0;margin:0;"/> '
				. '<input name="page" type="hidden" value="' . $page . '" /><input name="search" type="hidden" value="1" />'
				. ($pstart > 1 ? '<input type="submit" name="back" value="&lt; Back" />' : '')
				. ' <input type="text" name="jumpno" value="' . $visualPage . '" size="6" />'
				. '<input type="submit" name="jump" value="Jump" /> '
				//. ($pend < $results ? '<input type="submit" name="next" value="Next &gt;" />' : '')
				. '<input type="submit" name="next" value="Next &gt;" />'
				. '<input type="submit" value="Add New Record" name="add_new_record">'
				. '<input type="submit" value="Import" name="import">'
				. '</fieldset></form>');

				
echo '<br/>';	
	
}

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

function addEditUpm($id)
{
	if($id)
	{
		$query = "select * from upm where id=$id";
		$res = mysql_query($query) or die('Cannot update this upm id');
		while($row = mysql_fetch_assoc($res))
		{
			$upmDetails = $row;
		}
	}
	
	$columns = array();
	$query = "show columns from upm";
	$res = mysql_query($query)or die('Cannot fetch column names from upm table.');
	$i=0;
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

function saveUpm($post,$import=0,$importKeys=array(),$importVal=array(),$line=null)
{
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
		$post = array_map(am,array_keys($post),array_values($post));
		array_pop($post);		
		$query = "insert into upm values(".implode(',',$post).")";
		mysql_query($query)or die('Cannot insert upm entry');
	}
	else//update
	{
		$post = array_map(am1,array_keys($post),array_values($post));
		array_pop($post);		
		$query = "update upm set ".implode(',',$post)." where id=".$id;
		mysql_query($query)or die('Cannot update upm entry');		
	}
}
/*
 * 
 * */
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
echo '</html>';
