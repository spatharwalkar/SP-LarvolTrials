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

$table = $script = 'products';

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
function upmdelsure(){ return confirm("Are you sure you want to delete this product?"); }
$(document).ready(function(){
	var options, a,b;
	jQuery(function(){
	  options = { serviceUrl:'autosuggest.php',params:{table:<?php echo "'$table'"?>,field:'name'} };
	  a = $('#name').autocomplete(options);
	  b = $('#search_name').autocomplete(options);
	});
});
</script>
<div class="error">Under Development</div>

<?php 
//Start controller area
//delete controller should come above save controller if delete box is added in the add edit form
if(isset($_GET['deleteId']) && is_numeric($_GET['deleteId']) && $deleteFlag)
{
	deleteData($_GET['deleteId'],$table);
	$pattern = '/(\\?)(deleteId).*?(\\d+)/is';
	$_SERVER['REQUEST_URI'] =  preg_replace($pattern, '', $_SERVER['REQUEST_URI']);
	$_SERVER['REQUEST_URI'] = str_replace($script.'.php&', $script.'.php?', $_SERVER['REQUEST_URI']);
}
//save operation controller
if($_GET['save']=='Save')
{
	saveData($_GET,$table);
}

//import controller
if(isset($_FILES['uploadedfile']) && $_FILES['uploadedfile']['size']>1)
{
	$xmlZip = $_FILES['uploadedfile']['tmp_name'];
	$xml = unzipForXmlImport($xmlZip);
	$success = 0;
	$fail = 0;
	$k=0;
	$xmlImport = new DOMDocument();
	$xmlImport->load($xml);
	//$xmlImport->saveXML()
	//set import keys
	$importKeys = array('LI_id','name','comments','product_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','is_active','created','modified','company','brand_names','generic_names','code_names','approvals','xml');
	
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
		$brand_names = null;
		foreach($product->getElementsByTagName('ProductBrandNames') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('ProductBrandName') as $brandName)
			{
				$brand_names = ($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$brandName->getElementsByTagName('name')->item(0)->nodeValue:$brand_names;
			}
		}
		$generic_names = null;
		foreach($product->getElementsByTagName('ProductGenericNames') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('ProductGenericName') as $brandName)
			{
				$generic_names = ($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$brandName->getElementsByTagName('name')->item(0)->nodeValue:$generic_names;
			}
		}		
		$code_names = null;
		foreach($product->getElementsByTagName('ProductCodeNames') as $brandNames)
		{
			foreach($brandNames->getElementsByTagName('ProductCodeName') as $brandName)
			{
				$code_names = ($brandName->getElementsByTagName('is_active')->item(0)->nodeValue=='True')?$brandName->getElementsByTagName('name')->item(0)->nodeValue:$code_names;
			}
		}

		$approvals = $product->getElementsByTagName('approvals')->item(0)->nodeValue;
		$xmldump = $xmlImport->saveXML($product);
		
		
		$importVal = array('LI_id'=>$product_id,'name'=>$name,'comments'=>$comments,'product_type'=>$product_type,'licensing_mode'=>$licensing_mode,'administration_mode'=>$administration_mode,'discontinuation_status'=>$discontinuation_status,'discontinuation_status_comment'=>$discontinuation_status_comment,'is_key'=>$is_key,'is_active'=>$is_active,'created'=>$created,'modified'=>$modified,'company'=>$company,'brand_names'=>$brand_names,'generic_names'=>$generic_names,'code_names'=>$code_names,'approvals'=>$approvals,'xml'=>$xmldump);
		//ob_start();
		if(saveData(null,$table,1,$importKeys,$importVal,$k))
		{
			$success ++;
		}
		else 
		{			
			$fail ++;
		}
		//ob_end_clean();
	}
	echo 'Imported '.$success.' records, Failed entries '.$fail;

}
//end controller area

//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
$page=0;

//pagination
$ignoreFields = array('searchdata');
pagePagination($limit,$totalCount,$table,$script,$ignoreFields,array('import'=>true));
//pagination controller

//define skip array table fields
$skipArr = array('comments','product_type','licensing_mode','administration_mode','discontinuation_status','discontinuation_status_comment','is_key','is_active','created','modified','company','brand_names','generic_names','code_names','approvals','xml');

echo '<br/>';
echo '<div class="clr">';
//add edit form.
if($_REQUEST['add_new_record']=='Add New Record' || $_REQUEST['id'] && !$_GET['save'])
{
	$id = ($_REQUEST['id'])?$_REQUEST['id']:null;
	echo '<div>';
	addEditUpm($id,$table,$script,array("formOnSubmit"=>"onsubmit=\"return chkbox(this,'delsearch','searchdata');\"",'deletebox'=>true),$skipArr);
	echo '</div>';
	echo '<br/>';
}

//import form
if($_GET['import']=='Import' || $_GET['uploadedfile'])
{
	importUpm('products','products');
}

//normal upm listing
$start = $page*$limit;
contentListing($start,$limit,$table,$script,$skipArr,$includeArr,array('delete'=>false));
echo '</div>';
echo '</html>';