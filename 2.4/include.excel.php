<?php
//return the contents of one MS Excel file containing the message
function messageInExcel($msg)
{
	// Create excel file object
	$objPHPExcel = new PHPExcel();

	// Build sheet
	$objPHPExcel->setActiveSheetIndex(0);
	$sheet = $objPHPExcel->getActiveSheet();
	
	$sheet->SetCellValue('A1', $msg);
	$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
	
	//$objWriter->save('php://output');
	//@flush();
	$tempfile = tempnam(sys_get_temp_dir(), 'exc');
	if($tempfile === false) tex('Unable to create temp file');
	$objWriter->save($tempfile);
	$content = file_get_contents($tempfile);
	unlink($tempfile);
	return $content;
}
?>