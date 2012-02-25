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
<?php
		if(isset($_REQUEST['id']))	//load search from Saved Search
		{
			$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
			$searchDbData = getSearchData($table, 'searchdata', $id);
			//$show_value = 'loadQueryData("' . $data . '");';
			//$show_value = "loadQueryData('" . $searchDbData . "');";
			$show_value = "searchDbData = '" . $searchDbData . "';";
			echo($show_value);

		}
		else
		{
			$show_value = "searchDbData = '';";
			echo($show_value);
		}
		?>
function upmdelsure(){ return confirm("Are you sure you want to delete this area?"); }
$(document).ready(function(){
	var options, a,b;
	jQuery(function(){
	  options = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'name'} };
	  if($('#name').length>0)
	  a = $('#name').autocomplete(options);
	  b = $('#search_name').autocomplete(options);
	});
	$(".ajax").colorbox({
		onComplete:function(){ loadQueryData($('#searchdata').val());},
		onClosed:function(){ newSearch(); },
		inline:true, 
		width:"100%",
		height:"100%"
			});
	$("#inline_outer").hide();	
});
</script>
<div class="error">Under Development</div>

<?php 
//Start controller area
//Start controller area
//save operation controller
if($_GET['save']=='Save')
{
	$saveStatus = saveData($_GET,$table);
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
if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id'] && !$_GET['save'] || $saveStatus===0)
{
	$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
	echo '<div>';
	addEditUpm($id,$table,$script,array("formOnSubmit"=>"onsubmit=\"return chkbox(this,'delsearch','searchdata');\"",'deletebox'=>true,'saveStatus'=>$saveStatus));
	echo '</div>';
	echo '<br/>';
}


//normal upm listing
$start = $page*$limit;
contentListing($start,$limit,$table,$script,array(),array(),array('delete'=>false));
echo '</div>';
?>
<div id="inline_outer" >
<div id="inline_content">
<table>

<tr>

<td>
<div class="querybuilder" ></div>
</td>

<td valign="top" style="padding-top: 15px">
<table width="200px">
<tr>
<td class="graybk" style="text-align: center; font-weight: bold">
Actions</td>
</tr>
<tr>
<td style="padding-left: 30px;"><input type="submit"
style="width: 100px" onclick="testSQL();return false;"
value="Test Query" id="btnTest" /></td>
</tr>
</table>
</td>
</tr>
<tr>
	<td style="padding-left: 30px;"><input type="submit"
	style="width: 100px" onclick="submitSearch();return false;"
	value="Submit" id="btnSubmit" /></td>
</tr>
</table>
</div>
</div>
<?php 
require_once 'querybuilder.php';
?>
<script type="text/javascript">
$(document).ready(function () {

});

    function testSQL()
    {
        var jsonData = getQueryData();   
          $.ajax({
					type: 'GET',
					url:  'searchhandler.php' + '?op=testQuery',
					data: 'data=' + jsonData,
					beforeSend:function (){
						$("#3009").show();
						$("#3009").html('<div style="height:50px;text-align: center;z-index: 99;"><img width="100" height="100" src="images/loading.gif" alt="loading..." title="loading..."/></div>');
						},
					success: function (data) {
							$("#3009").show();
        					$("#3009").html(data);
        		            //$("#3009").attr("style", "visibility:show");
        		        	
					}
        	});
        return;
    		
  }
    
    function runSQL()
    {
        var jsonData = getQueryData();   
        var url = 'queryresults.php' + '?op=runQuery&data=' + jsonData;
        window.location.href=url;
        return;
    }

    function submitSearch()
    {
    	//function to get the JSON data of the search
    	var jsonData = getQueryData(); 
    	$('#searchdata').val(jsonData);
    	if($('#searchdata').val() != searchDbData)
    	$('#search_modifier').html('[Modified]');
    	if(jsonData=='')
    	{
        	$('#add_edit_searchdata_img').attr('src','images/add.png');
    	}
    	else
    	{
        	$('#add_edit_searchdata_img').attr('src','images/edit.png');
        }
    	$("#3009").hide();
    	$('.ajax').colorbox.close();
    	$(".ajax").colorbox({
    		onComplete:function(){ loadQueryData($('#searchdata').val());},
    		onClosed:function(){ newSearch(); },
    		inline:true, 
    		width:"100%",
    		height:"100%"
    			});    	
    	return false;

    }
  </script>
<?php 
echo '</html>';