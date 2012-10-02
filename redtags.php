<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.util.php');
require_once 'include.page.php';
require_once 'PHPExcel/IOFactory.php';

error_reporting(E_ALL);

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

$table = $script = 'redtags';

require('header.php');

echo '<div class="error">Under Development</div>';


//import controller
if(isset($_FILES['uploadedfile']) && $_FILES['uploadedfile']['size']>1)
{
	$xlsx = $_FILES['uploadedfile']['tmp_name'];
	$ext = array_reverse(explode('.',$_FILES['uploadedfile']['name']));
	if($ext[0]=='xlsx' || $ext[0]=='xls')
	{
		//code for import of xls$xml = unzipForXmlImport($xmlZip);
		$objphpExcel = PHPExcel_IOFactory::load($xlsx);
		$phpExcel = $objphpExcel->getActiveSheet();
		$phpExcelRange = $phpExcel->getMergeCells();
		//pr($phpExcel->getMergeCells());
		//$rangeDetails = PHPExcel_Cell::splitRange($range);
		
		//echo '<table border=1>' . "\n";
		foreach ($phpExcel->getRowIterator() as $row)
		{
			//echo '<tr>' . "\n";
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);
			foreach ($cellIterator as $cell)
			{
				if(in_array($cell->getRow(),array(1,2,3)))
				{
					continue;
				}
				
				$cellVal = $cell->getValue();
				foreach($phpExcelRange as $range)
				{
					if($cell->isInRange($range))
					{
						//$inRange = 'inrange';
						$rangeDetails = PHPExcel_Cell::splitRange($range);
						$cellVal = $phpExcel->getCell($rangeDetails[0][0])->getValue();
						break;
					}
					else
					{
						$inRange = '';
					}
				}

				switch ($cell->getColumn())
				{
					case 'A':
						$aVal = $cellVal;
						break;
					case 'B':
						$bVal = $cellVal;
						break;
					case 'C':
						$cVal = $cellVal;
						break;
				}
				
				//echo '<td>' . $cellVal .'</td>' . "\n";
			}
			
			if($cVal !='')
			{
				$enum = trim($aVal.' '.$bVal);
				$enum = str_replace("  ", " ", $enum);
				$enum = implode(' ',array_unique(explode(' ',$enum)));
				$out[] = array('name'=>mysql_real_escape_string($cVal), 'type'=>mysql_real_escape_string($enum));
			}
		
		}
		$redTagEnums = getEnumValues($table,'type');
		$save = saveData($out, $table, 1, array('`name`','`type`'), null, null, array('redTagEnums'=>$redTagEnums));
		//pr($out);die;
	}
	else
	{
		$msg = "Use xls format for the red tags import.";
		softDieSession($msg);
	}
/* 	$xml = $xmlZip;
	$success = 0;
	$fail = 0;
	$k=0;
	$xmlImport = new DOMDocument();
	$xmlImport->load($xml);
	//$xmlImport->saveXML()
	//set import keys
	$out = parseProductsXmlAndSave($xmlImport,$table); */
	softDieSession("Imported ".$save['insertCnt']." records.");
	softDieSession("Updated ".$save['updateCnt']." records.");
	softDieSession("Deleted ".$save['deleteCnt']." records.");
	softDieSession("Failed Import ".$save['insertFailCnt']." records.");
	softDieSession("Failed Update ".$save['updateFailCnt']." records.");
	softDieSession("Delete Operation Failed ".$save['deleteFailCnt']." time.");
	softDieSession("Skipped Updates ".$save['updateSkipCnt']." records.");
	softDieSession("Invalid Types skipped ".$save['invalidEnumSkipCnt']." records.");

}
//end controller


//set docs per list
$limit = 50;
$totalCount = getTotalCount($table);
$maxPage = $totalCount%$limit;
if(!isset($_GET['oldval']))
	$page=0;

//pagination
$ignoreFields = array();
pagePagination($limit,$totalCount,$table,$script,$ignoreFields,array('import'=>true,'search'=>false,'add_new_record'=>false));

echo '<br/>';
echo '<div class="clr">';

//import form
if($_REQUEST['import']=='Import' || $_REQUEST['uploadedfile'])
{
	importUpm('redtags','redtags');
}

//normal upm listing
$start = $page*$limit;
contentListing($start,$limit,$table,$script,array(),array(),array('delete'=>false));
echo '</div>';
echo '</html>';