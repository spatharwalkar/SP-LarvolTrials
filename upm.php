<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
require_once('include.util.php');
require_once 'include.page.php';
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
//declare all globals
global $db;
global $page;
global $deleteFlag;
$table = $script = 'upm';
//calulate delete flag
if($db->user->userlevel == 'admin')
$deleteFlag = 1;
else
$deleteFlag = null;

//reset controller
if($_GET['reset'])
header('Location: ' . urlPath() . 'upm.php');
require('header.php');
?>

<script type="text/javascript">
function upmdelsure(){ return confirm("Are you sure you want to delete this upm?"); }
function validateedit(){if(/*$('#product_id').val()==''*/false){alert('Select a proper product name from the list of suggestions.');return false}else return true;}
function validatesearch(){if(/*$('#search_product').val()==''*/false){$('#search_product_id').val('');}if($('#search_product_id').val()=='' && $('#search_product').val()!=''){alert('Select a proper product name from the list of suggestions.');return false}else return true;}
$(document).ready(function(){
	var options, a,b;
	jQuery(function(){
		  options1 = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'product'},onSelect: function(value, data){ $('#product_id').val(data);} };
		  options2 = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'product'},onSelect: function(value, data){ $('#search_product_id').val(data);} };
		  a = $('#product').autocomplete(options1);
		  b = $('#search_product').autocomplete(options2);
		});
});
</script>

<?php
//Start controller area
//save operation controller
if($_GET['save']=='Save')
{
	$_GET['product'] = $_GET['product_id'];
	unset($_GET['product_id']);
	saveData($_GET,$table);
}
//delete controller
if(isset($_GET['deleteId']) && is_numeric($_GET['deleteId']) && $deleteFlag)
{
	deleteData($_GET['deleteId'],$table);
	$pattern = '/(\\?)(deleteId).*?(\\d+)/is';
	$_SERVER['REQUEST_URI'] =  preg_replace($pattern, '', $_SERVER['REQUEST_URI']);
	$_SERVER['REQUEST_URI'] = str_replace($script.'.php&', $script.'.php?', $_SERVER['REQUEST_URI']);
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
			if(saveData(null,$table,1,$importKeys,$importVal,$k))
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
//search controller sud come above pagination call since search is embedded in it.
if(isset($_GET['search']) && $_GET['search']=='Search')
{
	$_GET['search_product'] = $_GET['search_product_id'];
	unset($_GET['search_product_id']);

}
//End controller area


//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
$page=0;

//pagination
pagePagination($limit,$totalCount,$table,$script,array(),array("import"=>false,"formOnSubmit"=>"onsubmit=\"return validatesearch();\""));
//pagination controller

echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_GET['add_new_record']=='Add New Record' || $_GET['id'] && !$_GET['save'])
{
	$id = ($_GET['id'])?$_GET['id']:null;
	echo '<div>';
	addEditUpm($id,$table,$script,array("formOnSubmit"=>"onsubmit=\"return validateedit();\"",'deletebox'=>false),array('last_update'));
	echo '</div>';
}

//import controller
if($_GET['import']=='Import' || $_GET['uploadedfile'])
{
	importUpm();
}

//normal upm listing
$start = $page*$limit;
$ignoreSort = array('product');
contentListing($start,$limit,$table,$script,array(),array(),array('delete'=>true,'ignoresort'=>$ignoreSort));
echo '</div>';
echo '</html>';
