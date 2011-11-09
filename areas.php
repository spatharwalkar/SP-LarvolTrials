<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
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
global $searchFormData;
$searchFormData = null;

$table = $script = 'areas';

//save search data
if(isset($_POST['searchformdata']))
{
	/*Start - Removing fields for which action is set to None (zero) or empty data - for ticket no. 42 over Trac*/ 
	$_POST = removeNullSearchdata($_POST);
	if($_POST['time_machine'] == '' || $_POST['time_machine'] == NULL)
		unset($_POST['time_machine']);
	
	if($_POST['override'] == '' || $_POST['override'] == NULL)
		unset($_POST['override']);
		
	if(empty($_POST['multifields']) || empty($_POST['multifields']['varchar+text'])) {
		unset($_POST['multifields']);
		unset($_POST['multivalue']);
	}	
		
	if(empty($_POST['action']))
		unset($_POST['action']);
	
	if(empty($_POST['searchval']))
		unset($_POST['searchval']);
	
	if(empty($_POST['negate']))
		unset($_POST['negate']);
		
	unset($_POST['searchformdata']);
	$areasId = $_POST['id'];
	unset($_POST['id']);
	/* End - Removing fields for which action is set to None (zero) or empty data*/	
	$searchData = null;
	$searchData = base64_encode(serialize($_POST));
	
}
//calulate delete flag
if($db->user->userlevel == 'admin')
$deleteFlag = 1;
else
$deleteFlag = null;

//reset controller
if($_GET['reset'])
header('Location: ' . urlPath() . $script.'.php');
require('header.php');
echo('<script type="text/javascript" src="delsure.js"></script>');
?>
<script type="text/javascript">
function upmdelsure(){ return confirm("Are you sure you want to delete this area?"); }
$(document).ready(function(){
	var options, a,b;
	jQuery(function(){
	  options = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'name'} };
	  if($('#name').length>0)
	  a = $('#name').autocomplete(options);
	  b = $('#search_name').autocomplete(options);
	});
});
</script>
<div class="error">Under Development</div>

<?php 
//Start controller area
//Start controller area
//save operation controller
if($_GET['save']=='Save')
{
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

//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
$page=0;

//pagination
$ignoreFields = array('searchdata');
pagePagination($limit,$totalCount,$table,$script,$ignoreFields,array('import'=>false));
//pagination controller

echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id'] && !$_GET['save'])
{
	$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
	echo '<div>';
	addEditUpm($id,$table,$script,array("formOnSubmit"=>"onsubmit=\"return chkbox(this,'delsearch','searchdata');\""));
	echo '</div>';
	echo '<br/>';
}


//normal upm listing
$start = $page*$limit;
contentListing($start,$limit,$table,$script,array());
echo '</div>';
echo '</html>';