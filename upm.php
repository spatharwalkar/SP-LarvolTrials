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
//pr($_GET);die;
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
function validatesearch(){/*if($('#search_product').val()==''){$('#search_product_id').val('');}if($('#search_product_id').val()=='' && $('#search_product').val()!=''){alert('Select a proper product name from the list of suggestions.');return false}else */return true;}
$(document).ready(function(){
	var options, a,b,c,d;

		  options1 = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'product'} ,minChars:3, showOnSelect:true };
		  options2 = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'product'} ,minChars:3, showOnSelect:true };

		  <?php if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id']):?>
		  a = $('#product').autocomplete(options1);
		  <?php endif;?>
		  b = $('#search_product').autocomplete(options2);

		  options_area1 = { 
				  			serviceUrl:'autosuggest.php',
				  			params:{table:<?php echo "'$table'"?>,
						  	field:'area'},
						  	minChars:3,
						  	showOnSelect:true,
						  	onSelect: function (v,d){
								var duplicateFlag = 0;
							  	$('.area_autosuggest_multiple').each(function(){
									if($(this).val() == v)
									{
										alert('This area has already been selected.');
										duplicateFlag = 1;
									}
								  	});

							  	if(duplicateFlag == 1)
							  	{
							  		$('#area').val('');
								  	return false;
							  	}
								$('#area').closest('tr').after(
										'<tr><td></td><td><input type="checkbox" class="area_autosuggest_multiple" name="area[]" value="'+v+'" checked="checked"/> '+v+' <img style="border:0" title="Delete Area" alt="Delete Area" src="images/not.png" class="auto_suggest_multiple_delete"></td></tr>'
										);
								$('#area').val('');
						  	}

						  	 };
		  options_area2 = { serviceUrl:'autosuggest.php',
				  			params:{table:<?php echo "'$table'"?>,
						  	field:'area'} ,
						  	minChars:3,
						  	showOnSelect:true,
						  	onSelect: function (v,d){
								var duplicateFlag = 0;
							  	$('.search_area_autosuggest_multiple').each(function(){
									if($(this).val() == v)
									{
										alert('This area has already been selected.');
										duplicateFlag = 1;
									}
								  	});

							  	if(duplicateFlag == 1)
							  	{
							  		$('#search_area').val('');
								  	return false;
							  	}							  	
									$('#search_area').closest('tr').after(
											'<tr><td></td><td><input type="checkbox" class="search_area_autosuggest_multiple" name="search_area[]" value="'+v+'" checked="checked"/> '+v+' <img style="border:0" title="Delete Area" alt="Delete Area" src="images/not.png" class="auto_suggest_multiple_delete"></td></tr>'
											);
									$('#search_area').val('');
							  	}

				   };

		  <?php if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id']):?>
		  c = $('#area').autocomplete(options_area1);
		  <?php endif;?>
		  d = $('#search_area').autocomplete(options_area2);	


		  //listener for autosuggest delete icon class
		  $('.auto_suggest_multiple_delete').live('click',function(){
			  
				$(this).closest('tr').fadeOut("fast", function(){$(this).remove();});
			  });	  

});
</script>

<?php
//Start controller area
//save operation controller
if($_REQUEST['save']=='Save')
{
	$query = "select id from products where name='{$_REQUEST['product']}'";
	$res = mysql_query($query);
	$pid = null;
	while($row = mysql_fetch_assoc($res))
	{
		$pid = $row['id'];
	}
	
	if(is_array($_REQUEST['area']) && count($_REQUEST['area'])>0)
	{
		$requestAreaTmp = $_REQUEST['area'];
		
		$_REQUEST['area'] = array_map(function($area){
			return "'$area'";
		},$_REQUEST['area']);
		
		$query = "select id from areas where areas.name in (".implode(',',$_REQUEST['area']).")";
		$res = mysql_query($query);
		$aid = null;
		while($row = mysql_fetch_assoc($res))
		{
			$aid[] = $row['id'];
		}
	}

	
	unset($_REQUEST['product_id']);
	//$_GET['product'] = $_GET['product_id'];
	$_REQUEST = array_merge($_GET, $_POST); 
	$_REQUEST['product'] = $pid;
	$_REQUEST['area'] = $aid;
	$saveStatus = saveData($_REQUEST,$table);
	if(!$pid) 
	{
		softDieSession('Wrong/no product name selected.');
	}
}
//delete controller
if(isset($_REQUEST['deleteId']) && is_numeric($_REQUEST['deleteId']) && $deleteFlag)
{
	deleteData($_REQUEST['deleteId'],$table);
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



//run search controller part1 for upm.product/products.id conversion used for pagination counts then the normal GET values are replaced for prefilling searchform data.
if(isset($_GET['search']))
{
	//$_GET['search_product'] = $_GET['search_product_id'];
	//unset($_GET['search_product_id']);
	$query = "select id from products where name='{$_GET['search_product']}' and name !=''";
	$res = mysql_query($query);
	$pid = null;
	while($row = mysql_fetch_assoc($res))
	{
		$pid = $row['id'];
	}
	if($pid)
	{
		$search_product_tmp_name = $_GET['search_product'];
		$_GET['search_product'] = $pid;
	}
	
	if(is_array($_GET['search_area']) && count($_GET['search_area'])>0)
	{
		$search_area_tmp_name = $_GET['search_area'];
		$_GET['search_area'] = array_map(function($area){
			return "'$area'";
		},$_GET['search_area']);
		$query = "select id from areas where name in (".implode(',',$_GET['search_area']).") and name !=''";
		$res = mysql_query($query);
		$aid = null;
		while($row = mysql_fetch_assoc($res))
		{
			$aid[] = $row['id'];
		}
		if(is_array($aid) && count($aid)>0)
		{
			$_GET['search_area'] = $aid;
		}
	}
	

}

//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
$page=0;

//search controller part2 should come before pagination call since search is embedded in it needs prefilled values.
if(isset($_GET['search']))
{
	if($pid)
	{
		$_GET['search_product'] = $search_product_tmp_name;
	}
	if($aid)
	{
		$_GET['search_area'] = $search_area_tmp_name;
	}	
}


//pagination
pagePagination($limit,$totalCount,$table,$script,array(),array("import"=>false,"formOnSubmit"=>"onsubmit=\"return validatesearch();\""));
//pagination controller


//search controller part3 should come after pagination call since search is embedded in it needs prefilled values.
if(isset($_GET['search']))
{
	if($pid)
	{
		$_GET['search_product'] = $pid;
	}
	
	if($aid)
	{
		$_GET['search_area'] = $aid;
	}	
}
//end search controller


echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id'] || $saveStatus===0)
{
	$addEditFormStyle = $mainTableStyle = 'style="width:100%"';
	$addEditGlobalInputStyle = 'style="width:97%;min-width:200px;"';
	$id = ($_GET['id'])?$_GET['id']:null;
	echo '<div>';
	addEditUpm($id,$table,$script,array("formOnSubmit"=>"onsubmit=\"return validateedit();\"",'deletebox'=>false,'saveStatus'=>$saveStatus,'formStyle'=>$addEditFormStyle,'mainTableStyle'=>$mainTableStyle,'addEditGlobalInputStyle'=>$addEditGlobalInputStyle),array('last_update'));
	echo '</div>';
}

//import controller
if($_REQUEST['import']=='Import' || $_REQUEST['uploadedfile'])
{
	importUpm();
}

//normal upm listing
$start = $page*$limit;
$ignoreSort = array('product','area');
contentListing($start,$limit,$table,$script,array(),array(),array('delete'=>true,'ignoresort'=>$ignoreSort));
echo '</div>';
echo '</html>';
