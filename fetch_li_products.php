<?php
require_once 'db.php';
require_once 'include.page.php';
ini_set('max_execution_time','360000');	//100 hours

/**
* @name fetch_li_products
* @tutorial Function to sync LI products with LT products. All data are retrieved from LI in xml format.
* @param String $lastrun Last Run where the number on the end is a unix timestamp for
* how early you want to go back looking for changes. Subtract 24 hours from the timestamp
* to avoid timezone errors. Passing 0 returns the ID of every product
* Only do this when lastrun is at the defult value (when the item has never been run).
* @author Jithu Thomas
*/
function fetch_li_products($lastrun)
{
	//calculate 24 hours from last run.
	$lastRunMinus24H = strtotime('- 24 hours',$lastrun);
	
	//fetch all available li products within the $lastRunMinus24H timeframe.
	
	$liXmlProductList = file_get_contents('http://admin.larvolinsight.com/LT/Services/Products/Changed.ashx?timestamp='.$lastRunMinus24H);
	$xmlImportProductList = new DOMDocument();
	$xmlImportProductList->loadXML($liXmlProductList);
	foreach($xmlImportProductList->getElementsByTagName('Product_ID') as $Product_ID)
	{
		$Product_ID = $Product_ID->nodeValue;
		fetch_li_product_individual($Product_ID);
	}
}
/**
 * @name fetch_li_product_individual
 * @tutorial Function to sync individual LI products with LT products. All data are retrieved from LI in xml format.
 * @param String $Product_ID = products.LI_id in LT 
 * @author Jithu Thomas
 */
function fetch_li_product_individual($Product_ID)
{
	$liXmlProduct = file_get_contents('http://admin.larvolinsight.com/LT/Services/Products/Detail.ashx?product_id='.$Product_ID);
	$xmlImportProduct = new DOMDocument();
	$xmlImportProduct->loadXML($liXmlProduct);
	$out = parseProductsXmlAndSave($xmlImportProduct,'products');
	echo 'Imported '.$out['success'].' records, Failed entries '.$out['fail']."<br/>\n";	
}

//controller
$timeStamp = '';
$timeStamp = $_GET['li_product_sync_timestamp'];
if($timeStamp !=='' && is_numeric($timeStamp))
{
	fetch_li_products($timeStamp);
}
$fetchLiScriptProductIndividualId = $_GET['fetch_li_script_product_individual_id'];
if($fetchLiScriptProductIndividualId !='')
{
	fetch_li_product_individual($fetchLiScriptProductIndividualId);
}
