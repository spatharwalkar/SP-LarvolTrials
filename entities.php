<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once 'include.page.php';

//Allow only for users already logged in.
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

if(stripos($_REQUEST['searchdata'],'"ignore_changes":"no"')>2)
{	
	$_GET['ignore_changes']='no';
}
elseif(stripos($_REQUEST['searchdata'],'"ignore_changes":"yes"')>2)
{
	$_GET['ignore_changes']='yes';
}
	
global $db,$page,$deleteFlag,$searchFormData;
$searchFormData = null;

$table = $_REQUEST['entity'];
$script = 'entities';

if(isset($_POST['searchformdata']))
{
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
	$searchData = null;
	$searchData = base64_encode(serialize($_POST));
}

if($db->user->userlevel == 'admin')
$deleteFlag = 1;
else
$deleteFlag = null;

$_GET['header']='<script type="text/javascript" src="progressbar/jquery.progressbar.js"></script>
<link href="css/status.css" rel="stylesheet" type="text/css" media="all" />'."\n";
if(is_numeric($_REQUEST['id']))
{
	$entityStatus = getPreindexProgress('ENTITY',$_REQUEST['id']);
	if($entityStatus['update_items_start_time']!="0000-00-00 00:00:00"&&$entityStatus['update_items_complete_time']!="0000-00-00 00:00:00"&&$entityStatus['status']==COMPLETED)
	$entityPreindexProgress=100;
	else
	$entityPreindexProgress=number_format(($entityStatus['update_items_total']==0?0:(($entityStatus['update_items_progress'])*100/$entityStatus['update_items_total'])),2);
}

if($_GET['reset'])
header('Location: ' . urlPath() . $script.'.php' . '?entity='.$_REQUEST['entity'] );
require('header.php');
//reset header controller
unset($_GET['header']);
//
echo('<script type="text/javascript" src="delsure.js"></script>');
?>
<script type="text/javascript">
$('#product_update').progressBar({ 	
    barImage : {
		0:  'images/progressbg_red.gif',
		30: 'images/progressbg_orange.gif',
		70: 'images/progressbg_green.gif'
	},
} );
<?php
		if(isset($_REQUEST['id']))	
		{
			$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
			$searchDbData = getSearchData($table, 'searchdata', $id);
			$show_value = "searchDbData = '" . $searchDbData . "';";
			echo($show_value);
		}
		else
		{
			$show_value = "searchDbData = '';";
			echo($show_value);
		}		
		?>
function upmdelsure(){ return confirm("Are you sure you want to delete this entity?"); }
$(document).ready(function(){
	var options, a,b;
	jQuery(function(){
	  options = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'name'}, showOnSelect:true };
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
//delete controller should come above save controller if delete box is added in the add edit form
if(isset($_REQUEST['deleteId']) && is_numeric($_REQUEST['deleteId']) && $deleteFlag)
{
	deleteData($_REQUEST['deleteId'],$table);
	$pattern = '/(\\?)(deleteId).*?(\\d+)/is';
	$_SERVER['REQUEST_URI'] =  preg_replace($pattern, '', $_SERVER['REQUEST_URI']);
	$_SERVER['REQUEST_URI'] = str_replace($script.'.php&', $script.'.php?', $_SERVER['REQUEST_URI']);
}
//save operation controller
if($_REQUEST['save']=='Save')
{
	//defined readonly table fields here as well to avoid them in query will be better
	$ReadOnlyArr = array('comments','entity_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','created','modified','company','brand_names','generic_names','code_names','approvals');
	foreach($ReadOnlyArr as $Rfield)
	{
		if(array_key_exists($Rfield,$_GET))
		unset($_GET[$Rfield]);
		if(array_key_exists($Rfield,$_POST))
		unset($_POST[$Rfield]);
	}
	$searchDataOld = $_REQUEST['id']?getSearchData('entities', 'searchdata', $_REQUEST['id']):null;	
	$_REQUEST = array_merge($_GET, $_POST); 
	$saveStatus = saveData($_REQUEST,$table);
}

//import controller
if(isset($_FILES['uploadedfile']) && $_FILES['uploadedfile']['size']>1)
{
	$xmlZip = $_FILES['uploadedfile']['tmp_name'];
	$ext = array_reverse(explode('.',$_FILES['uploadedfile']['name']));
	if($ext[0]=='zip')
	$xml = unzipForXmlImport($xmlZip);
	elseif($ext[0]=='xml')
	$xml = $xmlZip;
	$success = 0;
	$fail = 0;
	$k=0;
	$xmlImport = new DOMDocument();
	$xmlImport->load($xml);
	//$xmlImport->saveXML()
	//set import keys
	switch($table)
	{
		case 'products': $out = parseProductsXmlAndSave($xmlImport,$table); break;
		case 'institutions': $out = parseInstitutionsXmlAndSave($xmlImport,$table); break;
		case 'moas': $out = parseMoasXmlAndSave($xmlImport,$table); break;
		case 'moacategories': $out = parseMoacategoriesXmlAndSave($xmlImport,$table); break;
	}
	if($out) echo 'Imported '.$out['success'].' records, Failed entries '.$out['fail'].', Skipped entries'.$out['skip'].', Deleted entries'.$out['delete'];

}
//end controller area

//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
$page=0;

//pagination
if($table=='products')
{
	$ignoreFields = array('searchdata','xml','old_id','display_name','client_name','mesh_name');
	$skipArr = array('xml','old_id','is_active','display_name','client_name','mesh_name');
}
elseif($table=='diseases')
{
	$ignoreFields = array('administration_mode','approvals','brand_names','client_name','code_names','comments','company','created','discontinuation_status_comment','discontinuation_status','generic_names','is_active','is_key','licensing_mode','modified','old_id','product_type','search_name','xml','searchdata');
	$skipArr = array('xml','old_id','is_active','client_name','comments','product_type','licensing_mode','administrative_mode','created','modified','company','brand_names','generic_names','code_names','approvals','search_name','administration_mode','discontinuation_status','is_key','discontinuation_status_comment');
}
elseif($table=='institutions' or $table=='moas' or $table=='moacategories')
{
	$ignoreFields = array('administration_mode','approvals','created','discontinuation_status_comment','discontinuation_status','generic_names','is_active','is_key','licensing_mode','modified','old_id','product_type','searchdata','mesh_name');
	$skipArr = array('xml','old_id','class','searchdata','comments','created','modified','company','brand_names','generic_names','code_names','approvals','administration_mode','discontinuation_status','is_key','discontinuation_status_comment','mesh_name');
}
else
{
	$ignoreFields = array('LI_id','administration_mode','approvals','brand_names','client_name','code_names','comments','company','created','discontinuation_status_comment','discontinuation_status','generic_names','is_active','is_key','licensing_mode','modified','old_id','product_type','search_name','xml','searchdata','mesh_name');
	$skipArr = array('xml','LI_id','old_id','is_active','client_name','comments','product_type','licensing_mode','administrative_mode','created','modified','company','brand_names','generic_names','code_names','approvals','search_name','administration_mode','discontinuation_status','is_key','discontinuation_status_comment','mesh_name');
}
$entity=$_REQUEST['entity'];
pagePagination($limit,$totalCount,$table,$script,$ignoreFields, array('import'=>true,'searchDataCheck'=>true,'add_new_record'=>true,'search'=>true,$entity));
//pagination controller

//define skip array table fields


echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id'] && !$_REQUEST['save'] || $saveStatus===0)
{
	$addEditFormStyle = $mainTableStyle = 'style="width:100%"';
	$addEditGlobalInputStyle = 'style="width:99%;min-width:200px;"';
	$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
	echo '<div>';
	addEditUpm($id,$table,$script,array("formOnSubmit"=>"onsubmit=\"return chkbox(this,'delsearch','searchdata');\"",'deletebox'=>true,'formStyle'=>$addEditFormStyle,'mainTableStyle'=>$mainTableStyle,'addEditGlobalInputStyle'=>$addEditGlobalInputStyle,'saveStatus'=>$saveStatus,'preindexProgress'=>$entityPreindexProgress,'preindexStatus'=>$entityStatus),$skipArr);
	echo '</div>';
	echo '<br/>';
}
/*
//import form
if($_REQUEST['import']=='Import' || $_REQUEST['uploadedfile'])
{
	importUpm('products','products');
}
*/
//define skip array table fields
$skipArr = array('comments','entity_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','old_id','searchdata','created','modified','brand_names','generic_names','code_names','approvals','xml','display_name', 'class', 'category');

$ExtraSortFields = array(0=>'LI_id',1=>'name',2=>'company');
//normal upm listing
$start = $page*$limit;
contentListing($start,$limit,$table,$script,$skipArr,$includeArr,array('delete'=>false,'ignoresort'=>array('is_active'),'extrasort'=>$ExtraSortFields));
echo '</div>';
/* echo '<div class="querybuilder" id="inline_content">
</div></div>'; */
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
		if(jsonData.length > 500)
		{
			requestType = 'POST';
		}
		else
		{
			requestType = 'GET';
		}        
          $.ajax({
					type: requestType,
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
    	
    	var ermsg = 'Null search data not allowed. If search data is already there & you are trying to remove it, please delete search data using the delete checkbox';
    	if(jsonData !='') 
    	var jsonDataArr = eval('('+jsonData+')');
    	if(jsonData =='')
    	{
			alert(ermsg);
			return false;        	
    	}
    	if(jsonDataArr.wheredata == '')
    	{
			alert(ermsg);
			return false;
    	}
    	
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
    $('#product_update').progressBar();
  </script>
  
<?php 
//preindex after successful save and change of search data as a seperate worker thread
if($saveStatus == 1 && $searchDataOld != $_REQUEST['searchdata'])
{
	// added GET parameter ignore_changes to force recalculate of mhm cells without recording changes.
	echo input_tag(array('Type'=>'iframe','Field'=>'index_entity.php?id='.$_REQUEST['id'].'&connection_close=1&ignore_changes='.$_GET['ignore_changes']),null,array('style'=>"display:none"));
	unset($_GET['ignore_changes']);
}
//add predindex for full delete through a seperate worker thread
if(isset($_REQUEST['delsearch']) && is_array($_REQUEST['delsearch']))
{
	foreach($_REQUEST['delsearch'] as $delId => $wok)
	{
		echo input_tag(array('Type'=>'iframe','Field'=>urlPath().'index_entity.php?id='.$delId.'&connection_close=1&ignore_changes='.$_GET['ignore_changes']),null,array('style'=>"display:none"));
	}
}
echo '</html>';

function source_id_list_changed($oldd,$newd)
{
	$string='{"columnname":"source_id","opname":"Regex","chainname"';
	$pos = strpos($oldd, $string);
	$pos1 = strpos($newd, $string);

	
	if ($pos === false && $pos1 === false) // source id list DOES NOT EXIST. 
	{
		unset($oldd);
		unset($newd);
		return false;
	}
	

	if( get_source_id_list($oldd) <> get_source_id_list($newd) ) // CHANGE in  the source id list
	{
		unset($oldd);
		unset($newd);
		return true;
	}
	
	else  // NO CHANGE in the source id list.
	{
		unset($oldd);
		unset($newd);
		return false;
	}
}

function get_source_id_list($list)	
{
	$string='"source_id","opname":"Regex","chainname"';
	$pos_start = strpos($list, $string);
	if(isset($pos_start) and !empty($pos_start))
	{
		$pos_2 = strpos($list, '"columnvalue":',$pos_start);
		$strlen=strlen('"columnvalue":"');
		$pos_start=$pos_2;
		$pos_end = strpos($list, '"}',$pos_start);
		$listA=substr($list,$pos_start+$strlen,$pos_end-($pos_start+$strlen));
	}
	else
		$listA="";
	return $listA;
}

?>