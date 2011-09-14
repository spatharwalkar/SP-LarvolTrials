<?php
require_once('db.php');
if(!$db->loggedIn() || !isset($_POST['params']) || !isset($_POST['list']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel5.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

header("Pragma: public");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");

/* Generate MS Excel file of search results. This consists of:
	- Build MS Excel file containing captured search result data from $_POST
	- Output the final file to the browser in a forced download.
*/

// Get serialized data and query the database.
$params = unserialize(base64_decode($_POST['params']));
$list = unserialize(base64_decode($_POST['list']));
$time = base64_decode($_POST['time']);
if($params === false || $list === false) die('Unable to parse search results -- it might work if you just try it again');
$time = strlen($time) ? $time : NULL;
//$source = unserialize(base64_decode($_POST['searchresults']));
$source = search($params,$list,NULL,$time);

$objPHPExcel = new PHPExcel();

// Set properties
$objPHPExcel->getProperties()->setCreator(SITE_NAME);
$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
$objPHPExcel->getProperties()->setTitle(SITE_NAME . ' data');
$objPHPExcel->getProperties()->setSubject(SITE_NAME . ' data');
$objPHPExcel->getProperties()->setDescription(SITE_NAME . ' data -- search results from ' . date("Y-m-d, H:i:s"));

// Build sheet
$objPHPExcel->setActiveSheetIndex(0);
$sheet = $objPHPExcel->getActiveSheet();
$sheet->setTitle('Data');
$prep = array();
$columns = array();	//the KEYS of this array store column names; all the values are 1
foreach($source as $id => $record)
{
	$row = array();
	$row['nct_id'] = $id;
	foreach($record as $field => $value)
	{
		if($value === NULL) continue;
		if(!is_array($value))
		{
			$columns[$field] = 1;
			$row[$field] = $value;
		}else{
			foreach($value as $arrval)
			{
				if(!is_object($arrval))
				{
					$columns[$field] = 1;
					if(!isset($row[$field]))
					{
						$row[$field] = $arrval;
					}else{
						$row[$field] .= "\n" . $arrval;
					}
				}else{
					foreach($arrval as $propname => $propval)
					{
						if($propval === NULL) continue;
						$col = $field . '/' . $propname;
						if(!is_array($propval))
						{
							$columns[$col] = 1;
							if(!isset($row[$col]))
							{
								$row[$col] = $propval;
							}else{
								$row[$col] .= "\n" . $propval;
							}
						}else{
							foreach($propval as $arrval2)
							{
								if(!is_object($arrval2))
								{
									$columns[$col] = 1;
									if(!isset($row[$col]))
									{
										$row[$col] = $arrval2;
									}else{
										$row[$col] .= "\n" . $arrval2;
									}
								}else{
									foreach($arrval2 as $prop2name => $prop2val)
									{
										if($prop2val === NULL) continue;
										$col = $field . '/' . $propname . '/' . $prop2name;
										$columns[$col] = 1;
										if(!isset($row[$col]))
										{
											$row[$col] = $prop2val;
										}else{
											$row[$col] .= "\n" . $prop2val;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	$prep[] = $row;
}

$sheet->SetCellValue('A1', 'nct_id');
$letter = 'A';
foreach($columns as $col => $one)
{
	$columns[$col] = $letter;		//now $columns maps column names to the spreadsheet column letter
	$sheet->SetCellValue($letter++ . '1', $col);	//write header on each column in the spreadsheet
}
unset($letter);
$rownum = 2;
foreach($prep as $row)
{
	$padId = padnct($row['nct_id']);
	foreach($columns as $col => $letter)
	{
		$content = $row[$col];
		if($col == 'nct_id') $content = '=hyperlink("http://clinicaltrials.gov/show/' . $padId . '","' . $padId . '")';
		$sheet->SetCellValue($letter . $rownum, $content);
	}
	$rownum++;
}

//Create output writer
$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

//Send download
header("Content-Type: application/force-download");
header("Content-Type: application/vnd.ms-excel");
header("Content-Type: application/download");
header("Content-Disposition: attachment;filename=data.xls");
header("Content-Transfer-Encoding: binary ");
$objWriter->save('php://output');
@flush();
?>