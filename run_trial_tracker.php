<?php 
ob_start();
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

require_once('include.search.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once 'PHPExcel/IOFactory.php';
require_once('special_chars.php');

class TrialTracker
{
	private $fid = array();
	private $inactiveStatusValues = array();
	private $activeStatusValues = array();
	private $allStatusValues = array();
	private $resultsPerPage = 100;
	private $enumVals = array();
	private $phaseValues = array();
	
	private $statusFilters = array();
	private $phaseFilters = array();
	private $institutionFilters = array();
	private $regionFilters = array();
	
	function TrialTracker()
	{
		$this->fid['nct_id'] 					= '_' . getFieldId('NCT', 'nct_id');
		$this->fid['overall_status'] 			= '_' . getFieldId('NCT', 'overall_status');
		$this->fid['brief_title'] 				= '_' . getFieldId('NCT', 'brief_title');
		$this->fid['sponsor'] 					= '_' . getFieldId('NCT', 'lead_sponsor');
		$this->fid['collaborator'] 				= '_' . getFieldId('NCT', 'collaborator');
		$this->fid['condition'] 				= '_' . getFieldId('NCT', 'condition');
		$this->fid['intervention_name'] 		= '_' . getFieldId('NCT', 'intervention_name');
		$this->fid['phase'] 					= '_' . getFieldId('NCT', 'phase');
		$this->fid['enrollment'] 				= '_' . getFieldId('NCT', 'enrollment');
		$this->fid['enrollment_type'] 			= '_' . getFieldId('NCT', 'enrollment_type');
		$this->fid['start_date'] 				= '_' . getFieldId('NCT', 'start_date');
		$this->fid['acronym'] 					= '_' . getFieldId('NCT', 'acronym');
		$this->fid['inactive_date']				= 'inactive_date';
		$this->fid['institution_type'] 			= 'institution_type';
		$this->fid['region']					= 'region';
		
		$this->inactiveStatusValues = array('wh'=>'Withheld', 'afm'=>'Approved for marketing', 'tna'=>'Temporarily not available', 'nla'=>'No Longer Available', 
									'wd'=>'Withdrawn', 't'=>'Terminated','s'=>'Suspended', 'c'=>'Completed', 'empt'=>'');
									
		$this->activeStatusValues = array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
								'anr'=>'Active, not recruiting', 'av'=>'Available', 'nlr' =>'No longer recruiting');
		$this->allStatusValues = array_merge($this->inactiveStatusValues, $this->activeStatusValues);
		
		$this->enumVals = getEnumValues('clinical_study', 'institution_type');
		$this->phaseValues = array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', 
							'1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', 
							'2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900', 
							'3/4'=>'#FF0000', '3b/4'=>'#FF0000', '4'=>'#FF0000');
		
		$this->statusFilters = array('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Available',
									 'No longer recruiting','Withheld','Approved for marketing', 'Temporarily not available','No Longer Available',
									 'Withdrawn','Terminated', 'Suspended','Completed');
		$this->phaseFilters = array('N/A'=>'0', 'Phase 0'=>'0', 'Phase 0/Phase 1'=>'1', 'Phase 1'=>'1', 'Phase 1a'=>'1', 'Phase 1b'=>'1', 
									'Phase 1a/1b'=>'1', 'Phase 1c'=>'1', 'Phase 1/Phase 2'=>'2', 'Phase 1b/2'=>'2', 'Phase 1b/2a'=>'2', 'Phase 2'=>'2', 
									'Phase 2a'=>'2', 'Phase 2a/2b'=>'2', 'Phase 2a/b'=>'2', 'Phase 2b'=>'2', 'Phase 2/Phase 3'=>'3', 'Phase 2b/3'=>'3',
									'Phase 3'=>'3', 'Phase 3a'=>'3', 'Phase 3b'=>'3', 'Phase 3/Phase 4'=>'4', 'Phase 3b/4'=>'4', 'Phase 4'=>'4');
		$this->institutionFilters = array('Industry lead sponsor','Industry collaborator','Co-op','Other');
		$this->regionFilters = array('US','Canada','Japan','Europe','RestOfWorld');
		
	}
	
	function generateTrialTracker($format, $resultIds, $timeMachine = NULL, $ottType, $globalOptions = array())
	{	
		switch($format)
		{
			case 'xml':
				$this->generateXmlFile($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
			case 'excel':
				$this->generateExcelFile($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
			case 'pdf':
				$this->generatePdfFile($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
			case 'webpage':
				$this->generateOnlineTT($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
			case 'word':
				$this->generateWord();
				break;
			case 'indexed':
				$this->generateOnlineTT($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
			case 'unstackedoldlink':
				$this->generateOnlineTT($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
			case 'stackedoldlink':
				$this->generateOnlineTT($resultIds, $timeMachine, $ottType, $globalOptions);
				break;
		}
	}
	
	function generateExcelFile($resultIds, $timeMachine = NULL, $ottType, $globalOptions)
	{	
		$Values = array();
		
		$timeInterval = '-' . $globalOptions['change'];
							
		$currentYear = date('Y');
		$secondYear	= date('Y')+1;
		$thirdYear	= date('Y')+2;	

		ob_start();
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->getStyle('B1:K2000')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'NCT ID');
		$objPHPExcel->getActiveSheet()->setCellValue('B1' , 'Title');
		$objPHPExcel->getActiveSheet()->setCellValue('C1' , 'N');
		$objPHPExcel->getActiveSheet()->setCellValue('D1' , 'Region');
		$objPHPExcel->getActiveSheet()->setCellValue('E1' , 'Status');
		$objPHPExcel->getActiveSheet()->setCellValue('F1' , 'Sponsor');
		$objPHPExcel->getActiveSheet()->setCellValue('G1' , 'Conditions');
		$objPHPExcel->getActiveSheet()->setCellValue('H1' , 'Interventions');
		$objPHPExcel->getActiveSheet()->setCellValue('I1' , 'Start');
		$objPHPExcel->getActiveSheet()->setCellValue('J1' , 'End');
		$objPHPExcel->getActiveSheet()->setCellValue('K1' , 'Ph');
		$objPHPExcel->getActiveSheet()->setCellValue('L1' , 'Result');
		$objPHPExcel->getActiveSheet()->setCellValue('M1' , '-');
		$objPHPExcel->getActiveSheet()->mergeCells('M1:O1');
		$objPHPExcel->getActiveSheet()->setCellValue('P1' , $currentYear);
		$objPHPExcel->getActiveSheet()->mergeCells('P1:AA1');
		$objPHPExcel->getActiveSheet()->setCellValue('AB1' , $secondYear);
		$objPHPExcel->getActiveSheet()->mergeCells('AB1:AM1');
		$objPHPExcel->getActiveSheet()->setCellValue('AN1' , $thirdYear);
		$objPHPExcel->getActiveSheet()->mergeCells('AN1:AY1');
		$objPHPExcel->getActiveSheet()->setCellValue('AZ1' , '+');
		$objPHPExcel->getActiveSheet()->mergeCells('AZ1:BB1');

		$styleThinBlueBorderOutline = array(
			'borders' => array(
				'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
				'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'),),
			),
		);

		$highlightChange =  array('font' => array('color' => array('rgb' => 'FF0000')));
		
		$objPHPExcel->getActiveSheet()->getStyle('A1:BB1')->applyFromArray($styleThinBlueBorderOutline);
			
		$objPHPExcel->getProperties()->setCreator("The Larvol Group")
										 ->setLastModifiedBy("TLG")
										 ->setTitle("Larvol Trials")
										 ->setSubject("Larvol Trials")
										 ->setDescription("Excel file generated by Larvol Trials")
										 ->setKeywords("Larvol Trials")
										 ->setCategory("Clinical Trials");

		$bgColor = "D5D3E6";
		if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
		{	
			$Ids = array();
			$TrialsInfo = array();
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{
				foreach($resultIds['product'] as $pkey => $pvalue)
				{
					$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id = '" . $pvalue . "' ");
					$row = mysql_fetch_assoc($res);

					
					$TrialsInfo[$pkey]['sectionHeader'] = $row['name']
					. (($row['company'] !== NULL && $row['company'] != '') ? " / (" . $row['company'] . ")" : '');
					$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
							
					$Ids[$pkey]['product'] = $row['id'];
					$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$TrialsInfo[0]['naUpms'] = 
					$this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], implode("', '", $resultIds['product']));
					foreach($resultIds['area'] as $akey => $avalue)
					{
						$res = mysql_query("SELECT `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
						$row = mysql_fetch_assoc($res);
						
						$TrialsInfo[$akey]['sectionHeader'] = $row['name'];
						$Ids[$akey]['product'] = implode("', '", $resultIds['product']);
						$Ids[$akey]['area'] = $row['id'];
					}
				}
				else
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id = '" . $pvalue . "' ");
						$row = mysql_fetch_assoc($res);
						
						$TrialsInfo[$pkey]['sectionHeader'] = $row['name']
						. (($row['company'] !== NULL && $row['company'] != '') ? " / (" . $row['company'] . ")" : '');
						$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
								
						$Ids[$pkey]['product'] = $row['id'];
						$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
					}
				}
			}
			else
			{
				$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id IN ('" . implode(',', $resultIds['product']) . "') ");
				$row = mysql_fetch_assoc($res);
				
				$TrialsInfo[0]['sectionHeader'] = $row['name']
				. (($row['company'] !== NULL && $row['company'] != '') ? " / (" . $row['company'] . ")" : '');
				$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
				
				$Ids[0]['product'] = $row['id'];
				$Ids[0]['area'] = implode("', '", $resultIds['area']);
			}
			
			$Values = $this->processIndexedOTTData($ottType, $Ids, $globalOptions);
			$Values = array_merge($Values, array('TrialsInfo' => $TrialsInfo));
		}
		else if($ottType == 'standalone')
		{
			$Values = $this->processStandaloneOTTData($resultIds, $timeMachine, $globalOptions);
		}
		else
		{	
			if(!is_array($resultIds))
			{
				$resultIds = array($resultIds);
			}
			$Values = $this->processOTTData($ottType, $resultIds, $timeMachine, $linkExpiryDt = array(), $globalOptions);
		}
		
		if($globalOptions['download'] == 'allTrialsforDownload')
		{
			$Trials = $Values['allTrialsforDownload'];
			$count = count($Values['allTrialsforDownload']);
		}
		else
		{
			$Trials = $Values['Trials'];
			$count = count($Values['Trials']);
		}
		
		unset($Values['Trials']);
		unset($Values['allTrialsforDownload']);	
		
		$unMatchedUpms = array();
		foreach($Values['TrialsInfo'] as $tkey => $tvalue)
		{
			if(isset($tvalue['naUpms']))
			{
				$unMatchedUpms = array_merge($unMatchedUpms, $tvalue['naUpms']);
			}
		}
		
		$i = 2;
		$section = '';
		
		foreach($Trials as $tkey => $tvalue)
		{
			$startMonth = date('m',strtotime($tvalue['NCT/start_date']));
			$startYear = date('Y',strtotime($tvalue['NCT/start_date']));
			$endMonth = date('m',strtotime($tvalue['inactive_date']));
			$endYear = date('Y',strtotime($tvalue['inactive_date']));
			
			if($section !== $tvalue['section'])  
			{
				$sectionHeader = $Values['TrialsInfo'][$tvalue['section']]['sectionHeader'];
				$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sectionHeader);
				$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
				$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->applyFromArray(
							array('fill' => array(
											'type'       => PHPExcel_Style_Fill::FILL_SOLID,
											'rotation'   => 0,
											'startcolor' => array('rgb' => 'A2FF97'),
											'endcolor'   => array('rgb' => 'A2FF97')),
								  'borders' => array(
								  			'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF')),
											'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'))),
				));
				$i++;
			}	
			
			$nctId = $tvalue["NCT/nct_id"];
			$ctLink = urlencode('http://clinicaltrials.gov/ct2/show/' . padnct($nctId));
				
			$cellSpan = $i;
			$rowspanLimit = 0;
			
			if(!empty($tvalue['matchedupms'])) 
			{
				$cellSpan = $i;
				$rowspanLimit = count($tvalue['matchedupms']);
				$ct = 0;
				while($ct < $rowspanLimit)
				{
					$cellSpan = $cellSpan+1;
					$ct++;
				}
			}
				
			/////MERGE CELLS AND APPLY BORDER AS - FOR LOOP WAS NOT WORKING SET INDIVIDUALLY
			if(($rowspanLimit+1) > 1)
			{
				$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':A'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':A'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('B' . $i . ':B'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('B' . $i . ':B'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('C' . $i . ':C'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i . ':C'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('D' . $i . ':D'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('D' . $i . ':D'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('E' . $i . ':E'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('E' . $i . ':E'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('F' . $i . ':F'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i . ':F'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('G' . $i . ':G'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('G' . $i . ':G'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('H' . $i . ':H'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('H' . $i . ':H'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('I' . $i . ':I'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('I' . $i . ':I'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('J' . $i . ':J'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('J' . $i . ':J'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->mergeCells('K' . $i . ':K'. $cellSpan);
				$objPHPExcel->getActiveSheet()->getStyle('K' . $i . ':K'. $cellSpan)->applyFromArray($styleThinBlueBorderOutline);
			
				//set default height which contains upm's as these rows does not support auto height cause Merged cells 
				//+ wrap text + autofit row height = not working
				$objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(36);
			}
			/////END PART - MERGE CELLS AND APPLY BORDER AS - FOR LOOP WAS NOT WORKING SET INDIVIDUALLY
				
			$objPHPExcel->getActiveSheet()->getStyle('"A' . $i . ':BB' . $i.'"')->applyFromArray($styleThinBlueBorderOutline);
			$objPHPExcel->getActiveSheet()->getStyle('A1:BA1')->applyFromArray($styleThinBlueBorderOutline);
				
			
			//nct id	
			$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'NCT' . sprintf("%08s", $nctId));
			$objPHPExcel->getActiveSheet()->getCell('A' . $i)->getHyperlink()->setUrl($ctLink);
			if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('A' . $i)->applyFromArray($highlightChange); 
 			     $objPHPExcel->getActiveSheet()->getCell('A' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
			
			//brief title	
			$tvalue["NCT/brief_title"] = fix_special_chars($tvalue["NCT/brief_title"]);
			$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $tvalue["NCT/brief_title"]);
			$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setUrl($ctLink);
			
			if(!empty($tvalue['edited']) && array_key_exists('NCT/brief_title', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/brief_title']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange); 
 			     $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			else
			{
				 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov'); 
			}
			$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			
				
			//enrollment
			if(!empty($tvalue['edited']) && array_key_exists('NCT/enrollment', $tvalue['edited']) 
			&& (getDifference(substr($tvalue['edited']['NCT/enrollment'],16), $tvalue['NCT/enrollment'])))
			{
				 if($tvalue["NCT/enrollment_type"] != '' && $tvalue["NCT/enrollment_type"] == 'Anticipated') 
				 {
				 	 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray(array('font' => array('bold' => true),
																						'color' => array('rgb' => 'CDC9C9')));
				 }
				 else
				 {
				 	 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				 }
				 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/enrollment']); 
			}
			else if($tvalue['new'] == 'y')
			{
				if($tvalue["NCT/enrollment_type"] != '' && $tvalue["NCT/enrollment_type"] == 'Anticipated') 
				 {
				 	 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray(array('font' => array('bold' => true),
																						'color' => array('rgb' => 'CDC9C9')));
				 }
				 else
				 {
				 	 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				 }
				 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			if($tvalue["NCT/enrollment_type"] != '') 
			{
				if($tvalue["NCT/enrollment_type"] == 'Anticipated') 
				{ 
					$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $tvalue["NCT/enrollment"]);
					$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray(array('font' => array('bold' => true),
																						'color' => array('rgb' => 'CDC9C9'))); 
				}
				else if($tvalue["NCT/enrollment_type"] == 'Actual') 
				{
					$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $tvalue["NCT/enrollment"]);
				} 
				else 
				{ 
					$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $tvalue["NCT/enrollment"] . ' (' . $tvalue["NCT/enrollment_type"] . ')');
				}
			} 
			else 
			{
				$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $tvalue["NCT/enrollment"]);
			}
			
			
			//region	
			$tvalue["region"] = fix_special_chars($tvalue["region"]);
			$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $tvalue["region"]);
			if($tvalue['new'] == 'y')
			{
				$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($highlightChange);
				$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
				$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
				
			//status
			$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $tvalue["NCT/overall_status"]);
			if(!empty($tvalue['edited']) && array_key_exists('NCT/overall_status', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/overall_status']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange); 
				 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
				
			//collaborator and lead sponsor	
			$tvalue["NCT/lead_sponsor"] = fix_special_chars($tvalue["NCT/lead_sponsor"]);
			$tvalue["NCT/collaborator"] = fix_special_chars($tvalue["NCT/collaborator"]);
			
			$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $tvalue["NCT/lead_sponsor"] . $tvalue["NCT/collaborator"]);
			
			if(!empty($tvalue['edited']) && (array_key_exists('NCT/lead_sponsor', $tvalue['edited']) || array_key_exists('NCT/collaborator', $tvalue['edited'])))
			{
				$value = '';
				if(array_key_exists('NCT/lead_sponsor', $tvalue['edited']))
				{
					$value .= $tvalue['edited']['NCT/lead_sponsor'];
				}
				
				if(array_key_exists('NCT/collaborator', $tvalue['edited']))
				{
					$value .= $tvalue['edited']['NCT/collaborator'];
				}
				
				 $objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($value); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange); 
				 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
			
			//condition
			$tvalue["NCT/condition"] = fix_special_chars($tvalue["NCT/condition"]);
			$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $tvalue["NCT/condition"]);
			if(!empty($tvalue['edited']) && array_key_exists('NCT/condition', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/condition']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange); 
				 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
			
			//intervention
			$tvalue["NCT/intervention_name"] = fix_special_chars($tvalue["NCT/intervention_name"]);
			$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $tvalue["NCT/intervention_name"]);
			if(!empty($tvalue['edited']) && array_key_exists('NCT/intervention_name', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/intervention_name']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange); 
				 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
			
			//start date
			if(isset($tvalue["NCT/start_date"])
			&& $tvalue["NCT/start_date"] != '' 
			&& $tvalue["NCT/start_date"] !== NULL 
			&& $value["NCT/start_date"] != '0000-00-00')
			{ 	
				$objPHPExcel->getActiveSheet()->setCellValue('I' . $i, date('m/y',strtotime($tvalue["NCT/start_date"])));
			}
			if(!empty($tvalue['edited']) && array_key_exists('NCT/start_date', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/start_date']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange); 
				 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('New record'); 
			}
				
			
			//end date	
			if(isset($tvalue["inactive_date"]) 
			&& $tvalue["inactive_date"] != '' 
			&& $tvalue["inactive_date"] !== NULL 
			&& $tvalue["inactive_date"] != '0000-00-00') 
			{
				$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, date('m/y',strtotime($tvalue["inactive_date"])));
			}
			if(!empty($tvalue['edited']) && array_key_exists('NCT/inactive_date', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/inactive_date']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
 			     $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('New record'); 
			}
				
			
			//phase
			if($tvalue['NCT/phase'] == 'N/A' || $tvalue['NCT/phase'] == '' || $tvalue['NCT/phase'] === NULL)
			{
				$phase = 'N/A';
				$phaseColor = $this->phaseValues['N/A'];
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($tvalue['NCT/phase']));
				$tvalue['NCT/phase'] = str_replace('Phase ', '', trim($tvalue['NCT/phase']));
				$phaseColor = $this->phaseValues[$phase];
			}
			
			$objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $phase);
			if(!empty($tvalue['edited']) && array_key_exists('NCT/phase', $tvalue['edited']))
			{
				 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange);
				 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
				 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip($tvalue['edited']['NCT/phase']); 
			}
			else if($tvalue['new'] == 'y')
			{
				 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
				 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
 			     $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('New record'); 
			}
			
			if($bgColor == "D5D3E6")
			{
				$bgColor = "EDEAFF";
			}
			else 
			{
				$bgColor = "D5D3E6";
			}
				
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i .':K' .$i)->applyFromArray(
					array(
						'alignment' => array('horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
						'fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => $bgColor),
										'endcolor'   => array('rgb' => $bgColor))
					)
				);
					
			$objPHPExcel->getActiveSheet()->getStyle('A1:BA1')->applyFromArray(
					array(
						'font'    	=> array('bold'      	=> true),
						'alignment' => array('horizontal'	=> PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
						'borders'	=> array('top'     		=> array('style' => PHPExcel_Style_Border::BORDER_THIN)),
						'fill'		=> array('type'       => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
											'rotation'   => 90,
											'startcolor' => array('argb' => 'FFA0A0A0'),
											'endcolor'   => array('argb' => 'FFFFFFFF'))
					)
				);
	
			
			$this->trialGnattChartforExcel($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $phaseColor, 
			$tvalue["NCT/start_date"], $tvalue['inactive_date'], $objPHPExcel, $i, 'M');
			
			$i++;
				
			if(isset($tvalue['matchedupms']) && !empty($tvalue['matchedupms'])) 
			{
				foreach($tvalue['matchedupms'] as $mkey => $mvalue)
				{ 
					$stMonth = date('m', strtotime($mvalue['start_date']));
					$stYear = date('Y', strtotime($mvalue['start_date']));
					$edMonth = date('m', strtotime($mvalue['end_date']));
					$edYear = date('Y', strtotime($mvalue['end_date']));
					$upmTitle = htmlformat($mvalue['event_description']);
					
					//rendering diamonds in case of end date is prior to the current year
					$objPHPExcel->getActiveSheet()->getStyle('"L' . $i . ':BB' . $i . '"')->applyFromArray($styleThinBlueBorderOutline);
					if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
					{
						if((!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'result_link') || ($mvalue['new'] == 'y')) 
							$imgColor = 'red';
						else 
							$imgColor = 'black'; 
							
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX(40);
						$objDrawing->setOffsetY(10);
						
						if($mvalue['event_type'] == 'Clinical Data')
						{
							$objDrawing->setPath('images/' . $imgColor . '-diamond.png');
						}
						else if($mvalue['status'] == 'Cancelled')
						{
							$objDrawing->setPath('images/' . $imgColor . '-cancel.png');
						}
						else
						{
							$objDrawing->setPath('images/' . $imgColor . '-checkmark.png');
						}
						$objDrawing->setCoordinates('L' . $i);
						$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setUrl(urlencode($mvalue['result_link']));
						$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setTooltip($upmTitle);
						
					}
					else if($mvalue['status'] == 'Pending')
					{
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX(40);
						$objDrawing->setOffsetY(10);
						$objDrawing->setPath('images/hourglass.png');
						$objDrawing->setCoordinates('L' . $i);
						if($mvalue['event_link'] != '' && $mvalue['event_link'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setUrl(urlencode($mvalue['event_link']));
							$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setTooltip($upmTitle);
						}
					}
					
					
					$this->upmGnattChartforExcel($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'], 
					$mvalue['end_date'], $mvalue['event_link'], $upmTitle, $objPHPExcel, $i, 'M');
					
					$objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(36);
					$i++;	
				}
			}
			
			$section = $tvalue['section'];
		}
		
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(13);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
		$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(12);
		$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(9);
		$objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(12);
		
		$Arr = array('M', 'N','O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ',
					'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB');
		
		foreach($Arr as $akey => $avalue)
		{
			$objPHPExcel->getActiveSheet()->getColumnDimension($avalue)->setWidth(2);
		}
		
		$objPHPExcel->getActiveSheet()->getStyle('A1:K900')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getStyle('A1:K900')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'NCT ID');
		$objPHPExcel->getActiveSheet()->setTitle('Larvol Trials');
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('Calibri');

		$objPHPExcel->createSheet(1);
		$objPHPExcel->setActiveSheetIndex(1);
		$objPHPExcel->getActiveSheet()->setTitle('UPMs');

		$objPHPExcel->getActiveSheet()->getStyle('B1:F200')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'ID');
		$objPHPExcel->getActiveSheet()->setCellValue('B1' , 'Product');
		$objPHPExcel->getActiveSheet()->setCellValue('C1' , 'Event Description');
		$objPHPExcel->getActiveSheet()->setCellValue('D1' , 'Status');
		$objPHPExcel->getActiveSheet()->setCellValue('E1' , 'Conditions');
		$objPHPExcel->getActiveSheet()->setCellValue('F1' , 'Start');
		$objPHPExcel->getActiveSheet()->setCellValue('G1' , 'End');
		$objPHPExcel->getActiveSheet()->setCellValue('H1' , 'Result');
		$objPHPExcel->getActiveSheet()->setCellValue('I1' , '-');
		$objPHPExcel->getActiveSheet()->mergeCells('I1:K1');
		$objPHPExcel->getActiveSheet()->setCellValue('L1' , $currentYear);
		$objPHPExcel->getActiveSheet()->mergeCells('L1:W1');
		$objPHPExcel->getActiveSheet()->setCellValue('X1' , $secondYear);
		$objPHPExcel->getActiveSheet()->mergeCells('X1:AI1');
		$objPHPExcel->getActiveSheet()->setCellValue('AJ1' , $thirdYear);
		$objPHPExcel->getActiveSheet()->mergeCells('AJ1:AU1');
		$objPHPExcel->getActiveSheet()->setCellValue('AV1' , '+');
		$objPHPExcel->getActiveSheet()->mergeCells('AV1:AX1');
		$objPHPExcel->getActiveSheet()->getStyle('A1:AX1')->applyFromArray($styleThinBlueBorderOutline);

		$i = 2;
		/* Display - Unmatched UPM's */
		foreach ($unMatchedUpms as $ukey => $uvalue)
		{
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':AX' . $i . '')->applyFromArray($styleThinBlueBorderOutline);
			
			$eventLink = urlencode($uvalue['event_link']);
			$resultLink = urlencode($uvalue['result_link']);
			
			//upm id
			$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $uvalue["id"]);
			if($uvalue['new'] == 'y')
			{
				$objPHPExcel->getActiveSheet()->getStyle('A' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('A' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('A' . $i)->getHyperlink()->setTooltip('New record');  
				}
			}
				
			
			//product name	
			$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $uvalue["product_name"]);
			if($uvalue["new"] == 'y')
			{
				$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('New record');  
				}
			}
			
			
			//upm description
			$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $uvalue["event_description"]);
			if($eventLink != '' && $eventLink !== NULL)
			{
			$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl(urlencode($eventLink));
			}
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_description'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					if($uvalue['edited']['event_description'] != '' && $uvalue['edited']['event_description'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Previous value: ' . $uvalue['edited']['event_description']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if($uvalue["new"] == 'y')
			{
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('New record');  
				}
			}
			
			
			//upm status
			$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $uvalue["status"]);
			if($uvalue["new"] == 'y')
			{
				$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('New record');  
				}
			}
			
			
			//upm type
			$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $uvalue["event_type"] . ' Milestone');
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_type'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink != NULL)
				 {
					$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['event_type'] != '' && $uvalue['edited']['event_type'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Previous value: '.$uvalue['edited']['event_type']);
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('No Previous value');
					}
				}	
			} 
			else if($uvalue['new'] == 'y') 
			{
				$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink != NULL)
				 {
					$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('New record');
				}	
			}
				
				
			//upm start date
			$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, date('m/y',strtotime($uvalue["start_date"])));
			$dateStyle = (array('font' => array('color' => array('rgb' => '973535'))));
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'start_date'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['start_date'] != '' && $uvalue['edited']['start_date'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Previous value: ' . $uvalue['edited']['start_date']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'start_date_type'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['start_date_type'] != '' && $uvalue['edited']['start_date_type'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Previous value: ' . $uvalue['edited']['start_date_type']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
				if($uvalue['start_date_type'] == 'anticipated') 
				{
					$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($dateStyle);
				}
			}
			else if($uvalue["new"] == 'y')
			{
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('New record');  
				}
				if($uvalue['start_date_type'] == 'anticipated') 
				{
					$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($dateStyle);
				}
			}
			
			
			//upm end date
			$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, date('m/y',strtotime($uvalue["end_date"])));

			$dateStyle = (array('font'    => array('color'     => array('rgb' => '973535'))));
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'end_date'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['end_date'] != '' && $uvalue['edited']['end_date'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Previous value: ' . $uvalue['edited']['end_date']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'end_date_type'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['end_date_type'] != '' && $uvalue['edited']['end_date_type'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Previous value: ' . $uvalue['edited']['end_date_type']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
				if($uvalue['end_date_type'] == 'anticipated') 
				{
					$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($dateStyle);
				}
			}
			else if($uvalue["new"] == 'y')
			{

				$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('New record');  
				}
				if($uvalue['end_date_type'] == 'anticipated') 
				{
					$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($dateStyle);
				}
			}
			
			
			//upm result column
			if($resultLink != '' && $resultLink !== NULL) 
			{
				if((!empty($uvalue['edited']) && $uvalue['edited']['field'] == 'result_link') || ($uvalue['new'] == 'y')) 
					$imgColor = 'red';
				else 
					$imgColor = 'black'; 
					
				$objDrawing = new PHPExcel_Worksheet_Drawing();
				$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
				$objDrawing->setOffsetX(40);
				$objDrawing->setOffsetY(4);
				if($uvalue['event_type'] == 'Clinical Data')
				{
					$objDrawing->setPath('images/' . $imgColor . '-diamond.png');
				}
				else if($uvalue['status'] == 'Cancelled')
				{
					$objDrawing->setPath('images/' . $imgColor . '-cancel.png');
				}
				else
				{
					$objDrawing->setPath('images/' . $imgColor . '-checkmark.png');
				}
				$objDrawing->setCoordinates('H' . $i);
				$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($resultLink);
				$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($uvalue['event_description']);
			}
			elseif($uvalue['status'] == 'Pending')
			{
				$objDrawing = new PHPExcel_Worksheet_Drawing();
				$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
				$objDrawing->setOffsetX(40);
				$objDrawing->setOffsetY(4);
				$objDrawing->setPath('images/hourglass.png');
				$objDrawing->setCoordinates('H' . $i);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($uvalue['event_description']);
				}
			}
			

			$stMonth = date('m', strtotime($uvalue['start_date']));
			$stYear = date('Y', strtotime($uvalue['start_date']));
			$edMonth = date('m', strtotime($uvalue['end_date']));
			$edYear = date('Y', strtotime($uvalue['end_date']));
					
			$this->upmGnattChartforExcel($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $uvalue['start_date'], 
			$uvalue['end_date'], $uvalue['event_link'], $uvalue["event_description"], $objPHPExcel, $i, 'I');
				
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':G' . $i)->applyFromArray(
					array('fill' => array(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array('rgb' => 'C5E5FA'),
							'endcolor'   => array('rgb' => 'C5E5FA'))
					)
				);		
			$i++;
		}
		/* End - Display - Unmatched UPM's */

		$objPHPExcel->getActiveSheet()->getStyle('A1:AX1')->applyFromArray(
				array('font'    => array('bold' => true),
					'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,),
					'borders' => array('top' => array('style' => PHPExcel_Style_Border::BORDER_THIN)),
					'fill' => array('type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
									'rotation'   => 90,
									'startcolor' => array('argb' => 'FFC5E5FA'),
									'endcolor'   => array('argb' => 'FFDBFCFF'))));
									
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(13);			
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);			
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(26);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(12);
		
		$chr = 'I';
		for($c=1; $c<43; $c++)
		{
			$objPHPExcel->getActiveSheet()->getColumnDimension($chr)->setWidth(2);
			$chr++;
		}

		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

		ob_end_clean(); 
			
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="  DTT  _' . date('Y-m-d_H.i.s') . '.xlsx"');
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();

		exit;
	}
	
	function trialGnattChartforExcel($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $bgColor, $startDate, 
	$endDate, &$objPHPExcel, $i, $from)
	{
		if($bgColor == '#00CCFF')
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => '00CCFF'),
										'endcolor'   => array('rgb' => '00CCFF'))
							));
		}
		else if($bgColor == '#99CC00')
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => '99CC00'),
										'endcolor'   => array('rgb' => '99CC00'))
							));
		}
		else if($bgColor == '#FFFF00')
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => 'FFFF00'),
										'endcolor'   => array('rgb' => 'FFFF00'))
							));
		}
		else if($bgColor == '#FF9900')
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => 'FF9900'),
										'endcolor'   => array('rgb' => 'FF9900'))
							));
		}
		else if($bgColor == '#FF0000')
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => 'FF0000'),
										'endcolor'   => array('rgb' => 'FF0000'))
							));
		}
		else if($bgColor == '#BFBFBF')
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => 'BFBFBF'),
										'endcolor'   => array('rgb' => 'BFBFBF'))
							));
		}
		else
		{
			$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
										'rotation'   => 0,
										'startcolor' => array('rgb' => 'BFBFBF'),
										'endcolor'   => array('rgb' => 'BFBFBF'))
							));
		}
		
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate == NULL || $endDate == '0000-00-00')) 
		{
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear == $currentYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear == $secondYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)

				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			}
			else if($endYear > $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i )->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00') 
		{
			$st = $startMonth-1;
			if($startYear < $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			}
			else if($startYear == $currentYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($startYear == $secondYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from++;
			
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($startYear == $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				$objPHPExcel->getActiveSheet()->mergeCells('Y' . $i . ':AJ'. $i);
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from++;
			
				if((12 - ($st+1)) != 0)

				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($startYear > $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($endDate < $startDate) 
		{
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
		} 
		else if($startYear < $currentYear) 
		{
			if($endYear < $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			}
			else if($endYear == $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
					
				if($endMonth == 12) 
				{
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					
				} 
				else 
				{ 
					$inc = $endMonth;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				
					$from = $to;
					$from++;
					$inc = (12-$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			} 
			else if($endYear == $secondYear)
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($endMonth == 12) 
				{
					$to = getColspanforExcelExport($from, 24);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				else 
				{
					$inc = (12+$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					
					$from = $to;
					$from++;
					$inc = (12-$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
					
				if($endMonth == 12) 
				{
					$to = getColspanforExcelExport($from, 36);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				} 
				else 
				{
					$inc=(24+$endMonth);
					$to=getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					
					$from = $to;
					$from++;
					$inc = (12-$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			} 
			else if($endYear > $thirdYear) 
			{ 
				$to = getColspanforExcelExport($from, 42);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			
			if($endYear == $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				if($val != 0)
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to. $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					if((12 - ($st+$val)) != 0)
					{
						$inc = (12 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					
					$from++;
					if((12 - ($st+1)) != 0)
					{
						$inc = (12 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				}
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
					
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear == $secondYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					if((24 - ($val+$st)) != 0)
					{
						$inc = (24 - ($val+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from++;
					if((24 - (1+$st)) != 0)
					{
						$inc = (24 - (1+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				}
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
				
					if((36 - ($val+$st)) != 0)
					{
						$inc = (36 - ($val+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from++;
				
					if((36 - (1+$st)) != 0)
					{
						$inc = (36 - (1+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			}
			else if($endYear > $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			
				$inc = (39 - $st);
				$to = getColspanforExcelExport($from, $inc);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':'. $to . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc=$st;
					$to=getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from=$to;
					$from++;
				}
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					if((12 - ($val+$st)) != 0)
					{
						$inc = (12 - ($val+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from++;
					
					if((12 - (1+$st)) != 0)
					{
						$inc = (12 - (1+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from  =$to;
					$from++;
				}
			
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to .$i )->applyFromArray($bgColor);
					$from = $to;
					$from++;
				
					if((24 - ($val+$st)) != 0)
					{
						$inc=(24 - ($val+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from++;
					
					if((24 - (1+$st)) != 0)
					{
						$inc = (24 - (1+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			} 
			else if($endYear > $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			
				$inc=(27 - $st);
				$to=getColspanforExcelExport($from, $inc);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i .':' . $to . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
				
					if((12 - ($val+$st)) != 0)
					{
						$inc = (12 - ($val+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from++;
					if((12 - (1+$st)) != 0)
					{
						$inc = (12 - (1+$st));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
			}
			else if($endYear > $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
			
				$inc = (15 - $st);
				$to = getColspanforExcelExport($from, $inc);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$objPHPExcel->getActiveSheet()->getStyle($from . $i .':' . $to .$i )->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		}
		else if($startYear > $thirdYear) 
		{
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
				
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
				
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$objPHPExcel->getActiveSheet()->getStyle($from . $i )->applyFromArray($bgColor);
			$from = $to;
			$from++;
			
		} 
	}
	
	function upmGnattChartforExcel($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, 
	$upmLink, $upmTitle, &$objPHPExcel, $i, $from)
	{
		$upmLink = urlencode($upmLink);
		$bgColor = (array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => '9966FF'),
									'endcolor'   => array('rgb' => '9966FF'))
						));
		
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')) 
		{
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if($upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if($upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if($upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if($upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if($upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;	
			} 
			else if($endYear == $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				if($upmLink != '' &&  $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
			} 
			else if($endYear == $secondYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}

					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from++;

				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear > $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		}
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00') 
		{
			$st = $startMonth-1;
			if($startYear < $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($startYear == $currentYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);

						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($startYear == $secondYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':'. $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;

				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($startYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from++;
				
				if((12 - ($st+1)) != 0)
				{
					$inc = (12 - ($st+1));
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($startYear > $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;

				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i )->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($endDate < $startDate) 
		{
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if( $upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
				
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			$from = $to;
			$from++;
		} 
		else if($startYear < $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
	
			if($endYear < $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear == $currentYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($endMonth == 12)
				{
					$inc = $endMonth;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				} 
				else 
				{ 
					$inc = $endMonth;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$inc = (12-$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
			}
			else if($endYear == $secondYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($endMonth == 12) 
				{
					$to = getColspanforExcelExport($from, 24);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				} 
				else 
				{
					$inc = (12+$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$inc = (12-$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 12);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
			}
			else if($endYear == $thirdYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($endMonth == 12) 
				{
					$to = getColspanforExcelExport($from, 36);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				} 
				else 
				{
					$inc = (24+$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					$from = $to;
					$from++;
					
					$inc = (12-$endMonth);
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);

						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					$to = getColspanforExcelExport($from, 3);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
			} 
			else if($endYear > $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 42);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i .':' . $to . $i)->applyFromArray($bgColor);
					if($upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					if((12 - ($st+$val)) != 0)
					{
						$inc = (12 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from++;
					
					if((12 - ($st+1)) != 0)
					{
						$inc = (12 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
					
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear == $secondYear) 
			{ 
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to. $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to .$i)->applyFromArray($bgColor);
					if($upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					if((24 - ($st+$val)) != 0)
					{
						$inc = (24 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					if($upmLink != '' && $upmLink != NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from++;
					
					if((24 - ($st+1)) != 0)
					{
						$inc = (24 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink != NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				if($val != 0) 
				{
					$inc = $val;
					$to  = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':'.$to. $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					if((36 - ($st+$val)) != 0)
					{
						$inc = (36 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from++;
					
					if((36 - ($st+1)) != 0)
					{
						$inc = (36 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear > $thirdYear)
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$from = $to;
					$from++;
				}
				
				$inc = (39 - $st);
				$to = getColspanforExcelExport($from, $inc);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)

				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i .':' . $to . $i)->applyFromArray($bgColor);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					if((12 - ($st+$val)) != 0)
					{
						$inc = (12 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i )->applyFromArray($bgColor);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from++;
					
					if((12 - ($st+1)) != 0)
					{
						$inc = (12 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				if($val != 0) 
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i . ':' . $to . $i)->applyFromArray($bgColor);
					if( $upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					if((24 - ($st+$val)) != 0)
					{
						$inc = (24 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					if( $upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from++;
					
					if((24 - ($st+1)) != 0)
					{
						$inc = (24 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
			} 
			else if($endYear > $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$inc = (27 - $st);
				$to = getColspanforExcelExport($from, $inc);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if($upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i )->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				if($val != 0)
				{
					$inc = $val;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					$objPHPExcel->getActiveSheet()->getStyle($from . $i .':'.$to . $i)->applyFromArray($bgColor);
					if( $upmLink != '' &&  $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
					
					if((12 - ($st+$val)) != 0)
					{
						$inc = (12 - ($st+$val));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
					if($upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from++;
					
					if((12 - ($st+1)) != 0)
					{
						$inc = (12 - ($st+1));
						$to = getColspanforExcelExport($from, $inc);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
						if($upmLink != '' && $upmLink != NULL)
						{
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
							$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
						}
						$from = $to;
						$from++;
					}
				}
				
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}

				$from = $to;
				$from++;
			} 
			else if($endYear > $thirdYear) 
			{
				$to = getColspanforExcelExport($from, 3);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				$to = getColspanforExcelExport($from, 12);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' && $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$from = $to;
				$from++;
				
				if($st != 0)
				{
					$inc = $st;
					$to = getColspanforExcelExport($from, $inc);
					$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
					if( $upmLink != '' && $upmLink !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
						$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
					}
					$from = $to;
					$from++;
				}
				
				$inc = (15 - $st);
				$to = getColspanforExcelExport($from, $inc);
				$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
				if( $upmLink != '' &&  $upmLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
					$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
				}
				$objPHPExcel->getActiveSheet()->getStyle($from . $i)->applyFromArray($bgColor);
				$from = $to;
				$from++;
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if( $upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
				
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if( $upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
				
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if( $upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 12);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if( $upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$from = $to;
			$from++;
			
			$to = getColspanforExcelExport($from, 3);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $i . ':' . $to . $i);
			if( $upmLink != '' && $upmLink !== NULL)
			{
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setUrl($upmLink);
				$objPHPExcel->getActiveSheet()->getCell($from . $i)->getHyperlink()->setTooltip($upmTitle);
			}
			$objPHPExcel->getActiveSheet()->getStyle($from . $i )->applyFromArray($bgColor);
			$from = $to;
			$from++;
		}
	}
	
	function generatePdfFile($resultIds, $timeMachine = NULL, $ottType, $globalOptions)
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		$pdfContent = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
						. '<html xmlns="http://www.w3.org/1999/xhtml">'
						. '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
						. '<title>Larvol PDF Export</title>'
						. '<style type="text/css">'
						.'body { font-family:Arial; font-color:black;}'
						. 'a, a:hover{color:#000000;text-decoration:none;display:block;width:100%; height:100%;}'
						.'td {vertical-align:top; border-right: 0.5px solid blue; border-left:0.5px solid blue; border-top: 0.5px solid blue; border-bottom: 
						0.5px solid blue;}'
						.'tr {border-right: 0.5px solid blue; border-left: 0.5px solid blue; border-top: 0.5px solid blue; border-bottom: 0.5px solid blue;}'
						.'.title { background-color:#EDEAFF;}'
						.'.alttitle { background-color:#D5D3E6;}'
						.'.highlight {color:#FF0000;}'
						.'.manage {table-layout:fixed;border-top:0.5px solid blue;border-left:0.5px solid blue;border-bottom:0.5px solid blue;}'
						.'.manage td{ margin:0; padding:0;}'
						.'.manage th { border-top:0.5px solid blue;	border-left:0.5px solid blue; border-right:0.5px solid blue;color:#0000FF;white-space:nowrap;}'
						.'.newtrial td, .newtrial td a{ color:#FF0000;}'
						.'.bomb { float:left; margin-top:20px; text-align:center;}'
						.'.result {	font-weight:bold;font-size:18px;}'
						.'.norecord { padding:0px; height:auto; line-height:normal; font-weight:normal;	background-color: #EDEAFF; color:#000000;}'
						.'.region {	background-color:#FFFFFF;}'
						.'.altregion { background-color:#F2F2F2;}'
						.'.sectiontitles{ font-family: Arial; font-weight: bold; background-color: #A2FF97;}'
						.'tr.upms td{ text-align: left;background-color:#C5E5FA;}'
						.'tr.upms td a{	color:#0000FF; text-decoration:none;}'
						.'@page {margin-top: 1em; margin-bottom: 2em;}'
						.'.nobr {white-space: nowrap}'
						.'</style></head>'
						.'<body>'
						.'<div align="center"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="200" height="25" id="header" /></div><br/>';
		
		$timeInterval = $this->getDecodedValue($globalOptions['findChangesFrom']);
		$Values = array();
		
		if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
		{	
			$Ids = array();
			$TrialsInfo = array();
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{
				foreach($resultIds['product'] as $pkey => $pvalue)
				{
					$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id = '" . $pvalue . "' ");
					$row = mysql_fetch_assoc($res);
					
					$TrialsInfo[$pkey]['sectionHeader'] = $row['name']
					. (($row['company'] !== NULL && $row['company'] != '') ? " / (" . $row['company'] . ")" : '');
					$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
							
					$Ids[$pkey]['product'] = $row['id'];
					$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$TrialsInfo[0]['naUpms'] = 
					$this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], implode("', '", $resultIds['product']));
					
					foreach($resultIds['area'] as $akey => $avalue)
					{
						$res = mysql_query("SELECT `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
						$row = mysql_fetch_assoc($res);
						
						$TrialsInfo[$akey]['sectionHeader'] = $row['name'];
						$Ids[$akey]['product'] = implode("', '", $resultIds['product']);
						$Ids[$akey]['area'] = $row['id'];
					}
				}
				else
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id = '" . $pvalue . "' ");
						$row = mysql_fetch_assoc($res);
						
						$TrialsInfo[$pkey]['sectionHeader'] = $row['name']
						. (($row['company'] !== NULL && $row['company'] != '') ? " / (" . $row['company'] . ")" : '');
						$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
						
						$Ids[$pkey]['product'] = $row['id'];
						$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
					}
				}
			}
			else
			{
				$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id IN ('" . implode(',', $resultIds['product']) . "') ");
				$row = mysql_fetch_assoc($res);
				
				$TrialsInfo[0]['sectionHeader'] = $row['name']
				. (($row['company'] !== NULL && $row['company'] != '') ? " / (" . $row['company'] . ")" : '');
				$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
				$Ids[0]['product'] = $row['id'];
				$Ids[0]['area'] = implode("', '", $resultIds['area']);
			}
		
			$Values = $this->processIndexedOTTData($ottType, $Ids, $globalOptions);
			$Values = array_merge($Values, array('TrialsInfo' => $TrialsInfo));
		}
		else if($ottType == 'standalone')
		{	
			$Values = $this->processStandaloneOTTData($resultIds, $timeMachine, $globalOptions);
		}
		else
		{
			if(!is_array($resultIds))
			{
				$resultIds = array($resultIds);
			}
			$Values = $this->processOTTData($ottType, $resultIds, $timeMachine, $linkExpiryDt = array(), $globalOptions);
		}
		
		if($globalOptions['download'] == 'allTrialsforDownload')
		{
			$Trials = $Values['allTrialsforDownload'];
			$count = count($Values['allTrialsforDownload']);
		}
		else
		{
			$Trials = $Values['Trials'];
			$count = count($Values['Trials']);
		}
		
		unset($Values['Trials']);
		unset($Values['allTrialsforDownload']);
		
		$start 	= '';
		$last = '';
		$totalPages = '';
		
		$start 	= ($globalOptions['page']-1) * $this->resultsPerPage + 1;
		$last 	= ($globalOptions['page'] * $this->resultsPerPage > $count) ? $count : ($start + $this->resultsPerPage - 1);
		$totalPages = ceil($count / $this->resultsPerPage);
		
		$pdfContent .= $this->displayTrialTableHeader_TCPDF($loggedIn, $globalOptions);
		
		$pdfContent .= $this->displayTrials_TCPDF($globalOptions, $loggedIn, $start, $last, $Trials, $Values['TrialsInfo'], $ottType);
		
		$pdfContent .= '</table></body></html>';
		$pdfContent = preg_replace('/(background-image|background-position|background-repeat):(\w)*\s/', '', $pdfContent);
		
		require_once('tcpdf/config/lang/eng.php');
		require_once('tcpdf/tcpdf.php');  
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// set document information
		//$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Larvol Trials');
		$pdf->SetTitle('Larvol Trials');
		$pdf->SetSubject('Larvol Trials');
		$pdf->SetKeywords('Larvol Trials, Larvol Trials PDF Export');
		
		$pdf->SetFont('verdana', '', 6);
		$pdf->setFontSubsetting(false);
		//set margins
		if($loggedIn)
		{
			$pdf->SetMargins(8.6, 15, 8.6);
		}
		else
		{
			$pdf->SetMargins(13.6, 15, 13.6);
		}
		
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		// remove default header/footer
		$pdf->setPrintHeader(false);

		//set some language-dependent strings
		$pdf->setLanguageArray($l);
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->AddPage();
		
		ini_set('pcre.backtrack_limit',strlen($pdfContent));
		// output the HTML content
		$pdf->writeHTML($pdfContent, true, false, true, false, '');
		ob_end_clean();
		//Close and output PDF document
		$pdf->Output('Larvol PDF_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
		
	}
	
	/***** Functions ONLY FOR TCPDF *****************************/
	function displayTrialTableHeader_TCPDF($loggedIn, $globalOptions = array()) 
	{
		$outputStr ='<table style="border-collapse:collapse;" width="100%" cellpadding="0" cellspacing="0" class="manage">'
			 . '<thead><tr>'. (($loggedIn) ? '<th valign="bottom" align="center" style="width:30px; vertical-align:bottom;" >ID</th>' : '' )
			 . '<th valign="bottom" height="11px" align="center" style="width:93px; vertical-align:bottom;">Title</th>'
			 . '<th valign="bottom" align="center" style="width:18px; vertical-align:bottom;" title="Black: Actual&nbsp;&nbsp;Gray: Anticipated&nbsp;&nbsp;Red: Change greater than 20%">N</th>'
			 . '<th valign="bottom" align="center" style="width:41px; vertical-align:bottom;" title="&quot;ROW&quot; = Rest of World">Region</th>'
			 . '<th valign="bottom" align="center" style="width:60px; vertical-align:bottom;">Interventions</th>'
			 . '<th valign="bottom" align="center" style="width:41px; vertical-align:bottom;">Sponsor</th>'
			 . '<th valign="bottom" align="center" style="width:41px; vertical-align:bottom;">Status</th>'
			 . '<th valign="bottom" align="center" style="width:60px; vertical-align:bottom;">Conditions</th>'
			 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;" title="MM/YY">Start</th>'
			 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;" title="MM/YY">End</th>'
			 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;">Ph</th>'
			 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;">Result</th>'
			  . '<th valign="bottom" align="center" style="width:6px; vertical-align:bottom;" colspan="3">-</th>'
			 . '<th valign="bottom" align="center" style="width:24px; vertical-align:bottom;" colspan="12">' . (date('Y')) . '</th>'
			 . '<th valign="bottom" align="center" style="width:24px; vertical-align:bottom;" colspan="12">' . (date('Y')+1) . '</th>'
			 . '<th valign="bottom" align="center" style="width:24px; vertical-align:bottom;" colspan="12">' . (date('Y')+2) . '</th>'
			 . '<th valign="bottom" align="center" style="width:6px; vertical-align:bottom;" colspan="3">+</th></tr></thead>';
		
		$outputStr.= '<tr style="border:none; border-top:none;">' //Extra row used for Alignment[IMP]
			 . (($loggedIn) ? '<td border="0" style="width:30px; height:0px; border-top:none; border:none;" ></td>' : '' )
			 . '<td border="0" height="0px" style="width:93px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:18px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:41px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:60px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:41px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:41px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:60px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:20px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:20px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:20px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:20px; height:0px; border-top:none; border:none;"></td>'
			 . '<td border="0" style="width:6px; height:0px; border-top:none; border:none;" colspan="3"></td>'
			 . '<td border="0" style="width:24px; height:0px; border-top:none; border:none;" colspan="12"></td>'
			 . '<td border="0" style="width:24px; height:0px; border-top:none; border:none;" colspan="12"></td>'
			 . '<td border="0" style="width:24px; height:0px; border-top:none; border:none;" colspan="12"></td>'
			 . '<td border="0" style="width:6px; height:0px; border-top:none; border:none;" colspan="3"></td></tr>';
			 
		return $outputStr;


	}

	function displayTrials_TCPDF($globalOptions = array(), $loggedIn, $start, $end, $trials, $trialsInfo, $ottType)
	{	
		$currentYear = date('Y');
		$secondYear = (date('Y')+1);
		$thirdYear = (date('Y')+2);
		
		$section = '-1';$outputStr = '';
		$start = $start - 1;
		$sections = array();
		
		$sections = array_map(function($a) { 
		  return $a['section']; 
		},  $trials);
		$sections = array_unique($sections);
		
		if($loggedIn)
		$col_width=548;
		else
		$col_width=518;
		
		for($i=$start; $i<$end; $i++) 
		{ 	
			if($i%2 == 1) 
			{ 
				$rowOneType = 'alttitle';
				$rowOneBGType = 'background-color:#D5D3E6;';
			}	
			else
			{
				$rowOneType = 'title';
				$rowOneBGType = 'background-color:#EDEAFF;';
			}	
			
			$rowspan = 1;
			$enrollStyle = 'color:gray;';
			$titleLinkColor = '#000000;';
			$sectionKey = $trials[$i]['section'];
			
			if(isset($trials[$i]['matchedupms']))  
				$rowspan = count($trials[$i]['matchedupms'])+1; 
			 
			if($section !== $sectionKey)
			{
				if($section != '-1')
				{
					$diff = $sectionKey - $section;

					if(($diff >= 2) && $section != -1)
					{	
						$counter = $section+1;
						for($j = $counter; $j < $sectionKey; $j++)
						{	
							if(!empty($trialsInfo[$j]['naUpms']))
							{
								$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $trialsInfo[$j]['sectionHeader']);
								$naUpmIndex = substr($naUpmIndex, 0, 7);
								
								$outputStr .= '<tr style="page-break-inside:avoid; width:'.$col_width.'px;" nobr="true" class="trialtitles">'
										. '<td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) 
										. '" class="upmpointer sectiontitles"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $trialsInfo[$j]['sectionHeader'] . '</td></tr>';
								$outputStr .= $this->displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $trialsInfo[$j]['naUpms']);
							}
							else
							{	
								$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn)  
								. '" class="sectiontitles">' . $trialsInfo[$j]['sectionHeader'] . '</td></tr>';
							}
							if($globalOptions['onlyUpdates'] == "no")
							{
								$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) 
								. '" class="norecord" align="left">No trials found</td></tr>';
							}
						}
					}
				}
				if(!empty($trialsInfo[$sectionKey]['naUpms']))
				{
					if($ottType == 'rowstacked')
					{
						$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true" class="trialtitles">'
								. '<td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
								. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
								. $this->displayUnMatchedUpms_TCPDF($loggedIn, 'rowstacked', $trialsInfo[$sectionKey]['naUpms'])
								. '<tr style="width:'.$col_width.'px; page-break-inside:avoid;" nobr="true" class="trialtitles">'
								. '<td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles"'
								. ' >' . $trialsInfo[$sectionKey]['sectionHeader'] . '</td></tr>';
					}
					else
					{
						if($ottType != 'colstacked')
							$image = 'down';
						else
							$image = 'up';
						
						$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $trialsInfo[$sectionKey]['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 7);
						
						$outputStr .= '<tr style="width:'.$col_width.'px; page-break-inside:avoid;" nobr="true" class="trialtitles">'
								. '<td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
								. ' onclick="sh(this,\'' . $naUpmIndex . '\');">' 
								. $trialsInfo[$sectionKey]['sectionHeader'] . '</td></tr>';
						$outputStr .= $this->displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $trialsInfo[$sectionKey]['naUpms']);
					}
				}
				else
				{	
					$outputStr .= '<tr style="page-break-inside:avoid; width:'.$col_width.'px;" nobr="true"><td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn)  
								. '" class="sectiontitles">' . $trialsInfo[$sectionKey]['sectionHeader'] . '</td></tr>';
				}
			}
			
			//row starts  
			$outputStr .= '<tr style="width:'.$col_width.'px; height:'.(24).'px; page-break-inside:avoid;" nobr="true" ' . (($trials[$i]['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
			
			//nctid column
			if($loggedIn) 
			{ 
				$outputStr .= '<td style="width:30px; '.$rowOneBGType.'" class="' . $rowOneType . '" ' . (($trials[$i]['new'] == 'y') ? 'title="New record"' : '') 
				. ' ><a style="color:' . $titleLinkColor . '" href="http://clinicaltrials.gov/ct2/show/' 
				. padnct($trials[$i]['NCT/nct_id']) . '" target="_blank">' . $trials[$i]['NCT/nct_id'] . '</a></td>';
			}

			//acroynm and title column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/brief_title', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$nctid]['edited']['NCT/brief_title'];
				$titleLinkColor = '#FF0000;';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
				$titleLinkColor = '#FF0000;';
			}				
			$outputStr .= '<td style="width:93px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . ' ' . $attr . '"><span>'
						. '<a style="color:' . $titleLinkColor . '" href="http://clinicaltrials.gov/ct2/show/' . padnct($trials[$i]['NCT/nct_id']) . '" '
						. 'target="_blank">'; 
			if(isset($trials[$i]['NCT/acronym']) && $trials[$i]['NCT/acronym'] != '') 
			{
				$outputStr .= '<b>' . htmlformat($trials[$i]['NCT/acronym']) . '</b>&nbsp;' . htmlformat($trials[$i]['NCT/brief_title']);
			} 
			else 
			{
				$outputStr .= htmlformat($trials[$i]['NCT/brief_title']);
			}
			$outputStr .= '</a></span></td>';
			
				
			//enrollment column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/enrollment',$trials[$i]['edited']) 
				&& (getDifference(substr($trials[$i]['edited']['NCT/enrollment'],16), $trials[$i]['NCT/enrollment']))) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/enrollment'];
				$enrollStyle = 'color:#973535;';
			}
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
				$enrollStyle = 'color:#973535;';
			}
			$outputStr .= '<td style="width:18px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><span>';
			if($trials[$i]["NCT/enrollment_type"] != '') 
			{
				if($trials[$i]["NCT/enrollment_type"] == 'Anticipated') 
				{ 
					$outputStr .= '<span style="font-weight:bold;' . $enrollStyle . '">' . $trials[$i]["NCT/enrollment"] . '</span>';
				}
				else if($trials[$i]["NCT/enrollment_type"] == 'Actual') 
				{
					$outputStr .= $trials[$i]["NCT/enrollment"];
				} 
				else 
				{ 
					$outputStr .= $trials[$i]["NCT/enrollment"] . ' (' . $trials[$i]["NCT/enrollment_type"] . ')';
				}
			} 
			else 
			{
				$outputStr .= $trials[$i]["NCT/enrollment"];
			}
			$outputStr .= '</span></td>';				


			//region column
			$attr = ' ';
			if($trials[$i]['new'] == 'y')
			{ 
				$attr = 'title="New record"';
			}
			$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" class="' . $rowOneType . '" rowspan="' . $rowspan . '" ' . $attr . '>'
						. '<span>' . $trials[$i]['region'] . '</span></td>';

				
			//intervention name column
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/intervention_name', $trials[$i]['edited']))
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/intervention_name'];
			} 
			else if($trials[$i]['new'] == 'y')
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td style="width:60px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<span>' . $trials[$i]['NCT/intervention_name'] . '</span></td>';


			//collaborator and sponsor column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && (array_key_exists('NCT/collaborator', $trials[$i]['edited']) 
			|| array_key_exists('NCT/lead_sponsor', $trials[$i]['edited']))) 
			{
					
				$attr = ' highlight" title="';
				if(array_key_exists('NCT/lead_sponsor', $trials[$i]['edited']))
				{
					$attr .= $trials[$i]['edited']['NCT/lead_sponsor'] . ' ';
				}
				if(array_key_exists('NCT/collaborator', $trials[$i]['edited'])) 
				{
					$attr .= $trials[$i]['edited']['NCT/collaborator'];
					$enrollStyle = 'color:#973535;';
				}
				$attr .= '';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<span>' . $trials[$i]['NCT/lead_sponsor'] . ' <span style="' . $enrollStyle . '"> ' 
						. $trials[$i]["NCT/collaborator"] . ' </span></span></td>';


			//overall status column
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/overall_status', $trials[$i]['edited'])) 
			{
				$attr = 'class="highlight ' . $rowOneType . ' " title="' . $trials[$i]['edited']['NCT/overall_status'] . '" ';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = 'title="New record" class="' . $rowOneType . '"' ;
			} 
			else 
			{
				$attr = 'class="' . $rowOneType . '"';
			}
			$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" ' . $attr . ' rowspan="' . $rowspan . '">'  
						. '<span>' . $trials[$i]['NCT/overall_status'] . '</span></td>';
				
				
			//condition column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/condition', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/condition'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td style="width:60px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<span>' . $trials[$i]['NCT/condition'] . '</span></td>';
					
				
			//start date column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/start_date', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/start_date'] ;
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '" ><span>'; 
			if($trials[$i]["NCT/start_date"] != '' && $trials[$i]["NCT/start_date"] != NULL && $trials[$i]["NCT/start_date"] != '0000-00-00') 
			{
				$outputStr .= date('m/y',strtotime($trials[$i]["NCT/start_date"]));
			} 
			else 
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</span></td>';
				
				
			//end date column
			$attr = ' ';
			if(isset($trials[$i]['edited']) && array_key_exists('inactive_date', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['inactive_date'] ;
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}	
			$outputStr .= '<td style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType  . $attr . '"><span>'; 
			if($trials[$i]["inactive_date"] != '' && $trials[$i]["inactive_date"] != NULL && $trials[$i]["inactive_date"] != '0000-00-00') 
			{
				$outputStr .= date('m/y',strtotime($trials[$i]["inactive_date"]));
			} 
			else 
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</span></td>';
					
											
			//phase column
			if(isset($trials[$i]['edited']) && array_key_exists('NCT/phase', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/phase'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			if($trials[$i]['NCT/phase'] == 'N/A' || $trials[$i]['NCT/phase'] == '' || $trials[$i]['NCT/phase'] === NULL)
			{
				$phase = 'N/A';
				$phaseColor = $this->phaseValues['N/A'];
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($trials[$i]['NCT/phase']));
				$phaseColor = $this->phaseValues[$phase];
			}
			$outputStr .= '<td align="center" style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' 
						. '<span>' . $phase . '</span></td>';				
			
			$outputStr .= '<td style="width:20px;">&nbsp;</td>';
				
			$startMonth = date('m',strtotime($trials[$i]['NCT/start_date']));
			$startYear = date('Y',strtotime($trials[$i]['NCT/start_date']));
			$endMonth = date('m',strtotime($trials[$i]['inactive_date']));
			$endYear = date('Y',strtotime($trials[$i]['inactive_date']));

			//rendering project completion gnatt chart
			$trialGnattChart = $this->trialGnattChart_TCPDF($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, 
				$trials[$i]['NCT/start_date'], $trials[$i]['inactive_date'], $phaseColor);
				
			$trialGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $trialGnattChart);	
			//$outputStr .= preg_replace('/width:([0-9]*)px;/', '', $trialGnattChart);
			$outputStr .= $trialGnattChart;	
				
			$outputStr .= '</tr>';
			
			//rendering matched upms
			if(isset($trials[$i]['matchedupms']) && !empty($trials[$i]['matchedupms'])) 
			{
				foreach($trials[$i]['matchedupms'] as $mkey => $mvalue) 
				{ 
					$str = '';
					$diamond = '';
					$resultImage = '';
	
					$stMonth = date('m', strtotime($mvalue['start_date']));
					$stYear = date('Y', strtotime($mvalue['start_date']));
					$edMonth = date('m', strtotime($mvalue['end_date']));
					$edYear = date('Y', strtotime($mvalue['end_date']));
					$upmTitle = 'title="' . htmlformat($mvalue['event_description']) . '"';
					
					$outputStr .= '<tr style="page-break-inside:avoid;" nobr="true">';
					
					if($loggedIn) 
					{
						if($mvalue['new'] == 'y')
						{
							$idColor = '#973535';
						}
						else
						{
							$idColor = 'gray';
						}
						$outputStr .= '<td style="width:30px; border-top:none;" class="' . $rowOneType . '"><a style="color:' . $idColor 
						. '" href="' . urlPath() . 'upm.php?search_id=' . $mvalue['id'] . '" target="_blank">' . $mvalue['id'] . '</a></td>';
					}
					
					$outputStr .= '<td style="width:20px; text-align:center;"><br />';
					
					
					if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
					{
						if((!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'result_link') || ($mvalue['new'] == 'y')) 
							$imgColor = 'red';
						else 
							$imgColor = 'black'; 
							
						$outputStr .= '<a href="' . $mvalue['result_link'] . '" style="color:#000;">';
						if($mvalue['event_type'] == 'Clinical Data')
						{
							$outputStr .= '<img src="images/' . $imgColor . '-diamond.png" alt="Diamond" height="6px" width="6px" style="margin:4px;" border="0" />';
						}
						else if($mvalue['status'] == 'Cancelled')
						{
							$outputStr .= '<img src="images/' . $imgColor . '-cancel.png" alt="Cancel" height="6px" width="6px" style="margin:4px;" border="0" />';
						}
						else
						{
							$outputStr .= '<img src="images/' . $imgColor . '-checkmark.png" alt="Checkmark" height="6px" width="6px" style="margin:4px;" border="0" />';
						}
						$outputStr .= '</a>';
					}
					else if($mvalue['status'] == 'Pending')
					{
						if($mvalue['event_link'] != '' && $mvalue['event_link'] !== NULL)
						{
							$outputStr .= '<a href="' . $mvalue['event_link'] . '" target="_blank">'
										. '<img src="images/hourglass.png" alt="Hourglass" height="8px" width="8px" style="margin:3px;" border="0" /></a>';
						}
						else
						{
							$outputStr .= '<img src="images/hourglass.png" alt="Hourglass" height="8px" width="8px" style="margin:3px;" border="0" />';
						}
					}
					$outputStr .= '</td>';
					
					//rendering upm (upcoming project completion) chart
					$upmGnattChart = $this->upmGnattChart_TCPDF($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'],
					$mvalue['end_date'], $mvalue['event_link'], $upmTitle);
					$upmGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $upmGnattChart);
					//$outputStr .= preg_replace('/width:([0-9]*)px;/', '', $upmGnattChart);
					$outputStr .= $upmGnattChart;
					$outputStr .= '</tr>';
				}
			}
			
			//section title
			$section = $trials[$i]['section'];
		}
		
		if(!empty($sections))
		{
			$maxSection = max($sections);
			$maxTrialsInfo = max(array_keys($trialsInfo));
		}
		if($sectionKey == $maxSection && $maxTrialsInfo > $maxSection)
		{
			for($cntr = $maxSection+1; $cntr <= $maxTrialsInfo; $cntr++)
			{
				if(!empty($trialsInfo[$cntr]['naUpms']))
				{
					$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $trialsInfo[$cntr]['sectionHeader']);
					$naUpmIndex = substr($naUpmIndex, 0, 7);
					
					$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true" class="trialtitles">'
							. '<td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) 
							. '" class="upmpointer sectiontitles"'
							. ' onclick="sh(this,\'' . $naUpmIndex . '\');">' 
							. $trialsInfo[$cntr]['sectionHeader'] . '</td></tr>';
					$outputStr .= $this->displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $trialsInfo[$cntr]['naUpms']);
				}
				else
				{	
					$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn)  
								. '" class="sectiontitles">' . $trialsInfo[$cntr]['sectionHeader'] . '</td></tr>';
				}
				if($globalOptions['onlyUpdates'] == "no")
				{
					$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td style="width:'.$col_width.'px;" colspan="' . getColspanBasedOnLogin($loggedIn) 
								. '" class="norecord" align="left">No trials found</td></tr>';
				}
			}
		}
		
		return $outputStr;
	}
	

	function displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $naUpms)
	{
		global $now;
		$outputStr = '';
		if($loggedIn)
		$col_width=548;
		else
		$col_width=518;
		
		if(!empty($naUpms))
		{
			$currentYear = date('Y');
			$secondYear = (date('Y')+1);
			$thirdYear = (date('Y')+2);
			
			$cntr = 0;
			foreach($naUpms as $key => $value)
			{
				$attr = '';
				$resultImage = '';
				$class = 'class = "upms ' . $naUpmIndex . '" ';
				$titleLinkColor = 'color:#000;';
				$dateStyle = 'color:gray;';
				$upmTitle = 'title="' . htmlformat($value['event_description']) . '"';
				
				//Highlighting the whole row in case of new trials
				if($value['new'] == 'y') 
				{
					$class = 'class="upms newtrial ' . $upmHeader . '" ';
				}
				
				//rendering unmatched upms
				$outputStr .= '<tr style="width:'.$col_width.'px; page-break-inside:avoid; background-color:#000;" nobr="true" ' . $class . '>';
				
				//field upm-id
				if($loggedIn)
				{
					if($value['new'] == 'y')
					{
						$titleLinkColor = 'color:#FF0000;';
						$title = ' title = "New record" ';
					}
					$outputStr .= '<td ' . $title . '><a style="' . $titleLinkColor 
							. '" href="' . urlPath() . 'upm.php?search_id=' . $value['id'] . '" target="_blank">' . $value['id'] . '</a></td>';
				}
				
				
				//field upm event description
				if(!empty($value['edited']) && ($value['edited']['field'] == 'event_description')) 
				{
					$titleLinkColor = 'color:#FF0000;';
					$attr = ' highlight'; 
					
					if($value['edited']['event_description'] != '' && $value['edited']['event_description'] !== NULL)
					{
						$title = ' title="Previous value: '. $value['edited']['event_description'] . '" '; 
					}
					else
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if($value['new'] == 'y') 
				{
					$titleLinkColor = 'color:#FF0000;';
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td style="width:253px;" colspan="5" class="' . $rowOneType .  $attr . '" ' . $title . '><span>';
				if($value['event_link'] !== NULL && $value['event_link'] != '') 
				{
					$outputStr .= '<a style="' . $titleLinkColor . '" href="' . $value['event_link'] . '" target="_blank">' . $value['event_description'] . '</a>';
				} 
				else 
				{
					$outputStr .= $value['event_description'];
				}
				$outputStr .= '</span></td>';
				
				
				//field upm status
				$title = '';
				if($value['new'] == 'y')
				{
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td style="width:41px;"class="' . $rowTwoType . '" ' . $title . '><span>' . $value['status'] . '</span></td>';

			
				//field upm event type
				$title = '';
				$attr = '';	
				if(!empty($value['edited']) && ($value['edited']['field'] == 'event_type')) 
				{
					$attr = ' highlight'; 
					if($value['edited']['event_type'] != '' && $value['edited']['event_type'] !== NULL)
					{
						$title = ' title="Previous value: '. $value['edited']['event_type'] . '" '; 
					}

					else
					{
						$title = ' title="No Previous value" ';
					}	
				} 
				else if($value['new'] == 'y') 
				{
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td style="width:60px;" class="' . $rowTwoType . $attr . '" ' . $title . '><span>' . $value['event_type'] . ' Milestone</span></td>';
				
				
				//field upm start date
				$title = '';
				$attr = '';	
				if(!empty($value['edited']) && ($value['edited']['field'] == 'start_date'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;'; 
					if($value['edited']['start_date'] != '' && $value['edited']['start_date'] !== NULL)
					{
						$title = ' title="Previous value: ' . $value['edited']['start_date'] . '" '; 
					} 
					else 
					{
						$title = ' title="No Previous value" ';
					}	
				} 
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'start_date_type'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;';
					if($value['edited']['start_date_type'] != '' && $value['edited']['start_date_type'] !== NULL) 
					{
						$title = ' title="Previous value: ' . $value['edited']['start_date_type'] . '" '; 
					} 
					else 
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if($value['new'] == 'y')
				{
					$title = ' title = "New record" ';
					$dateStyle = 'color:#973535;'; 
				}
				$outputStr .= '<td style="width:20px;" class="' . $rowTwoType . $attr . '" ' . $title . '><span>';
				if($value['start_date_type'] == 'anticipated') 
				{
					$outputStr .= '<span style="font-weight:bold;' . $dateStyle . '">'
					 			. (($value['start_date'] != '' && $value['start_date'] !== NULL && $value['start_date'] != '0000-00-00') ? 
								date('m/y',strtotime($value['start_date'])) : '' )  . '</span>';
				} 
				else 
				{
					$outputStr .= (($value['start_date'] != '' && $value['start_date'] !== NULL && $value['start_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['start_date'])) : '' );
				}
				$outputStr .= '</span></td>';		
				
				
				//field upm end date
				$title = '';
				$attr = '';	
				if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;';
					if($value['edited']['end_date'] != '' && $value['edited']['end_date'] !== NULL)
					{
						$title = ' title="Previous value: ' . $value['edited']['end_date'] . '" '; 
					}
					else 
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date_type'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;'; 
					if($value['edited']['end_date_type'] != '' && $value['edited']['end_date_type'] !== NULL) 
					{
						$title = ' title="Previous value: ' .  $value['edited']['end_date_type'] . '" ';
					} 
					else 
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if($value['new'] == 'y') 
				{
					$title = ' title = "New record" ';
					$dateStyle = 'color:#973535;'; 
				}
				$outputStr .= '<td style="width:20px;" class="' . $rowTwoType . $attr . '" ' . $title . '><span>';
				if($value['end_date_type'] == 'anticipated') 
				{
					$outputStr .= '<span style="font-weight:bold;' . $dateStyle . '">' 
								. (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['end_date'])) : '' ) . '</span>';
				} 
				else 
				{
					$outputStr .= (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['end_date'])) : '');
				}	
				$outputStr .= '</span></td><td style="width:20px;"><span></span></td>';
				
				
				//field upm result 
				$outputStr .= '<td style="width:20px; text-align:center;"><span><br />';
				if($value['result_link'] != '' && $value['result_link'] !== NULL)
				{
					if((!empty($value['edited']) && $value['edited']['field'] == 'result_link') || ($value['new'] == 'y')) 
							$imgColor = 'red';
					else 
						$imgColor = 'black'; 
						
					$outputStr .= '<span ' . $upmTitle . '><a href="' . $value['result_link'] . '" style="color:#000;">';
					if($value['event_type'] == 'Clinical Data')
					{
						$outputStr .= '<img src="images/' . $imgColor . '-diamond.png" alt="Diamond" height="6px" width="6px" style="margin:4px;" border="0" />';
					}
					else if($value['status'] == 'Cancelled')
					{
						$outputStr .= '<img src="images/' . $imgColor . '-cancel.png" alt="Cancel" height="6px" width="6px" style="margin:4px;" border="0" />';
					}
					else
					{
						$outputStr .= '<img src="images/' . $imgColor . '-checkmark.png" alt="Checkmark" height="6px" width="6px" style="margin:4px;" border="0" />';
					}
					$outputStr .= '</a></span>';
				}
				else if($value['status'] == 'Pending')
				{
					$outputStr .= '<span ' . $upmTitle 
					. '><img src="images/hourglass.png" alt="Hourglass" height="8px" width="8px" style="margin:3px; padding:10px;" border="0" /></span>';
				}
				$outputStr .= '</span></td>';		
				
				
				//upm gnatt chart
				$upmGnattChart = $this->upmGnattChart_TCPDF(date('m',strtotime($value['start_date'])), date('Y',strtotime($value['start_date'])), 
								date('m',strtotime($value['end_date'])), date('Y',strtotime($value['end_date'])), $currentYear, $secondYear, $thirdYear, 
								$value['start_date'], $value['end_date'], $value['event_link'], $upmTitle);
				$upmGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $upmGnattChart);
				//$outputStr .= preg_replace('/width:([0-9]*)px;/', '', $upmGnattChart);
				$outputStr .= $upmGnattChart;				
				
				$outputStr .= '</tr>';
			}
		}
		return $outputStr;
	}
	
	function trialGnattChart_TCPDF($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, $bgColor)
	{
		$outputStr = '';
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
			$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px; background-color:' . $bgColor . ';">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td  style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($endYear == $currentYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td style="width:'. ($st*2) .'px;" colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="width:2px; background-color:' . $bgColor . ';">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td  style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			}
			else if($endYear == $secondYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>' 
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . '; width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>' 
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . '; width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td colspan="3" style="background-color:' . $bgColor . '; width:6px;">&nbsp;</td>';
			}
		}
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')
		{
			$st = $startMonth-1;
			if($startYear < $currentYear)
			{
				$outputStr .= '<td colspan="3" style="width:6px; background-color:' . $bgColor . ';">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			}
			else if($startYear == $currentYear) 
			{ 
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . '; width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($startYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			}
			else if($startYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . '; width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($startYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td colspan="3" style="width:6px; background-color:' . $bgColor . ';">&nbsp;</td>';

			}
		} 
		else if($endDate < $startDate) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
			$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
		} 
		else if($startYear < $currentYear) 
		{
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px; background-color:' . $bgColor . ';">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $currentYear) 
			{
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:30px; background-color:' . $bgColor . ';" colspan="15">&nbsp;</td>' 
								. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				} 
				else 
				{ 
					$outputStr .= '<td style="width:'.(($endMonth+3)*2).'px; background-color:' . $bgColor . ';" colspan="' . ($endMonth+3) . '">&nbsp;</td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				}
			}
			else if($endYear == $secondYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:'.(27*2).'px; background-color:' . $bgColor . ';" colspan="27">&nbsp;</td>'
								. '<td  style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
				} 
				else 
				{
					$outputStr .= '<td style="width:'.((15+$endMonth)*2).'px; background-color:' . $bgColor . ';" colspan="' . (15+$endMonth) . '">&nbsp;</td>'
								. '<td style="width:' . ((12-$endMonth)*2) . 'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				}
			} 
			else if($endYear == $thirdYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:'.(39*2).'px; background-color:' . $bgColor . ';" colspan="39">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				} 
				else 
				{
					$outputStr .= '<td style="width:'.((27+$endMonth)*2).'px; background-color:' . $bgColor . ';" colspan="' . (27+$endMonth) . '">&nbsp;</td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				}
			} 
			else if($endYear > $thirdYear)
			{ 
				$outputStr .= '<td colspan="42" style="width:'.(42*2).'px; background-color:' . $bgColor . ';">&nbsp;</td>';		
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((12 - ($st+$val)) != 0) ? '<td style="width:' . ((12 - ($st+$val))*2) . 'px;" colspan="' .(12 - ($st+$val)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $secondYear)
			{ 
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0)
				{
					$outputStr .= '<td style="width:'.($val*2).'px; background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');			
				}
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td  style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((36 - ($val+$st)) != 0) ? '<td style="width:'.((36-($val+$st))*2).'px;" colspan="' .(36 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((36 - (1+$st)) != 0) ? '<td style="width:'.((36-(1+$st))*2).'px;" colspan="' .(36 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(39 - $st) . '" style="width:'.((39-$st)*2).'px; background-color:' . $bgColor . ';">&nbsp;</td>';		
			}
		}
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>' . (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">' . '&nbsp;</td>' : '');
				if($val != 0) 
				{ 
					$outputStr .= '<td style="width:'.($val*2).'px; background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{ 
					$outputStr .= '<td style="background-color:' . $bgColor . ';width:2px;"></td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="12" style="width:24px;">&nbsp;</td><td colspan="3" style="width:6px;">&nbsp;</td>';		
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px;">&nbsp;</td>';
				$outputStr .= '<td colspan="12" style="width:24px;">&nbsp;</td>' . (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';

			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>' . (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(27 - $st) . '" style="background-color:' . $bgColor . '; width:'.((27-$st)*2).'px; ">&nbsp;</td>';		
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . '; width:2px;">&nbsp;</td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="3" style="width:6px;">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>' . (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(15 - $st) . '" style="width:'.((15-$st)*2).'px; background-color:' . $bgColor . ';">&nbsp;</td>';		
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
			$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td colspan="3" style="width:6px; background-color:' . $bgColor . ';">&nbsp;</td>';	
		} 
		return $outputStr;
	}
	
	function upmGnattChart_TCPDF($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, $upmLink, $upmTitle)
	{	
		$outputStr = '';
		$bgColor = 'background-color:#9966FF;';
		$anchorTag = ($upmLink != '' &&  $upmLink !== NULL) ? '<a href="' . $upmLink . '" target="_blank">&nbsp;</a>' : '&nbsp;' ;
		
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00'))
		{
			$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>'. $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($endYear == $currentYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:2px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			}
			else if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:2px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:2px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3" style="width:6px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
		} 
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00') 
		{
			$st = $startMonth-1;
			if($startYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($startYear == $currentYear) 
			{ 
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:2px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($startYear == $secondYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:2px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($startYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:2px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($startYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3" style="width:6px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
		} 
		else if($endDate < $startDate) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
		} 
		else if($startYear < $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
			else if($endYear == $currentYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:'.(($endMonth+3)*2).'px; ' . $bgColor . '" colspan="' . ($endMonth+3) . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				} 
				else 
				{ 
					$outputStr .= '<td style="width:'.(($endMonth+3)*2).'px; ' . $bgColor . '" colspan="' . ($endMonth+3) . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear == $secondYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:'.(27*2).'px; ' . $bgColor . '" colspan="27">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				} 
				else 
				{
					$outputStr .= '<td style="width:'.(($endMonth+15)*2).'px; ' . $bgColor . '" colspan="' . (15+$endMonth) . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear == $thirdYear)
			{ 
				if($endMonth == 12)
				{
					$outputStr .= '<td style="width:'.(39*2).'px; ' . $bgColor . '" colspan="39">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
				else 
				{
					$outputStr .= '<td style="width:'.((27+$endMonth)*2).'px; ' . $bgColor . '" colspan="' . (27+$end_month) . '" ' . $class . '>' 
								. '<div ' . $upm_title . '>' . $anchorTag . '</div></td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td colspan="42" style="width:'.(42*2).'px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '" ><div ' . $upm_title . '>' . $anchorTag . '</div></td>' : '');
				if($val != 0)
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . '" colspan="' . $val . '">'. '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - ($st+$val)) != 0) ? '<td style="width:'.((12-($st+$val))*2).'px;" colspan="' .(12 - ($st+$val)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; ' . $bgColor . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
			else if($endYear == $secondYear) 
			{ 
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .' >' . $anchorTag . '</div></td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; ' . $bgColor . '">' . '<div ' . $upmTitle .' >' . $anchorTag . '</div></td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .'>'. $anchorTag . '</div></td>'
								. (((36 - ($val+$st)) != 0) ? '<td style="width:'.((36-($val+$st))*2).'px;" colspan="' .(36 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '') ;
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; ' . $bgColor . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((36 - (1+$st)) != 0) ? '<td style="width:'.((36-(1+$st))*2).'px;" colspan="' .(36 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '') ;			
				}
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(39 - $st) . '" style="width:'.((39-$st)*2).'px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			}
		} 
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
							
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; ' . $bgColor . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				}
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
							
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .=  '<td style="width:2px; ' . $bgColor . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';

	
			}
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>' . (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(27 - $st) . '" style="width:'.((27-$st)*2).'px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upm_title . '>' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upm_title . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
							
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px; ' . $bgColor . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>'. $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' 
							. (($st != 0) ? '<td style="width:'.($st*2).'px;" colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="' . (15 - $st) . '" style="width:'.((15-$st)*2).'px; ' . $bgColor . '"><div ' . $upmTitle . '>'. $anchorTag . '</div></td>';
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			$outputStr .= '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td colspan="3" style="width:6px; ' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
		}
		
		return $outputStr;	
	}


	/*****END OF Functions ONLY FOR TCPDF *****************************/
	
	function generateXmlFile($resultIds, $timeMachine = NULL, $ottType, $globalOptions)
	{
		$Values = array();
		if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
		{
			$Ids = array();
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{
				foreach($resultIds['product'] as $pkey => $pvalue)
				{
					$Ids[$pkey]['product'] = $pvalue;
					$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					foreach($resultIds['area'] as $akey => $avalue)
					{
						$Ids[$akey]['product'] = implode("', '", $resultIds['product']);
						$Ids[$akey]['area'] = $avalue;
					}
				}
				else
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$Ids[$pkey]['product'] = $pvalue;
						$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
					}
				}
			}
			else
			{
				$Ids[0]['product'] = implode("', '", $resultIds['product']);
				$Ids[0]['area'] = implode("', '", $resultIds['area']);
			}
			
			$Values = $this->processIndexedOTTData($ottType, $Ids, $globalOptions);
		}
		else if($ottType == 'standalone')
		{	
			$Values = $this->processStandaloneOTTData($resultIds, $timeMachine, $globalOptions);
		}
		else
		{
			if(!is_array($resultIds))
			{
				$resultIds = array($resultIds);
			}
			
			$Values = $this->processOTTData($ottType, $resultIds, $timeMachine, $linkExpiryDt = array(), $globalOptions);
		}
		
		//these values are not needed at present
		unset($Values['resultIds']);
		unset($Values['totactivecount']);
		unset($Values['totinactivecount']);
		unset($Values['totalcount']);
		unset($Values['TrialsInfo']);
		
		if($globalOptions['download'] == 'allTrialsforDownload')
		{
			$Trials = $Values['allTrialsforDownload'];
		}
		else
		{
			$Trials = $Values['Trials'];
		}
		
		unset($Values['Trials']);
		unset($Values['allTrialsforDownload']);
		
		foreach($Trials as $key => &$value)
		{
			unset($value['larvol_id']);
			unset($value['section']);
			unset($value['matchedupms']);
			unset($value['edited']);
			unset($value['new']);
			
			foreach($value as $vkey => $val) 
			{
				if(strpos($vkey, 'NCT/') !== FALSE) 
				{
					$nkey = str_replace('NCT/','NCT.',$vkey);
					$value[$nkey] = $val;
					unset($value[$vkey]);
				}
			}
		}
		
		// Build XML
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<results>' . "\n";
		$xml .= toXML($Trials);
		$xml .= "\n" . '</results>';
		
		//Send download
		header("Content-Type: text/xml");
		header("Content-Disposition: attachment;filename=ott.xml");
		header("Content-Transfer-Encoding: binary ");
		echo($xml);
		exit;
	}
	
	function generateOnlineTT($resultIds, $timeMachine = NULL, $ottType, $globalOptions = array())
	{	
		$Values = array();
		$linkExpiry = array();
			
		if($ottType == 'unstacked')
		{
			$Id = explode(".", $resultIds);
			$res = $this->getInfo('rpt_ott_header', array('header', 'id', 'expiry'), 'id', $Id[1]);
			
			if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
			{
				$linkExpiry[] = $res['expiry'];
			}
			
			$t = 'Area: ' . htmlformat(trim($res['header']));
			$this->displayHeader($t);
			
			echo '<input type="hidden" name="results" value="' . $resultIds . '"/>'
					. '<input type="hidden" name="time" value="' . $timeMachine . '"/>'
					. '<input type="hidden" name="v" value="' . $globalOptions['version'] . '"/>';
				
			$Values = $this->processOTTData($ottType, array($resultIds), $timeMachine, $linkExpiry, $globalOptions);
			
			if(!empty($Values['TrialsInfo']))
			{
				echo '<input type="hidden" id="upmstyle" value="expand"/>';
			}
			echo $this->displayWebPage($ottType, $Values['resultIds'], $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], 
			$globalOptions, $timeMachine, $Values['Trials'], $Values['TrialsInfo'], $Values['linkExpiry']);
		}
		else if($ottType == 'rowstacked' || $ottType == 'colstacked')
		{
			if($globalOptions['encodeFormat'] == 'new') 
			{
				$result = unpack("l*", gzinflate(base64_decode(rawurldecode($resultIds))));
				$result = $this->getResultSet($result,  $ottType);
			}
			else
			{
				$result = explode(',', gzinflate(base64_decode($resultIds)));
			}
			
			$Id = explode('.', $result[0]);
			if($ottType == 'colstacked')
			{
				$res = $this->getInfo('rpt_ott_header', array('header', 'id', 'expiry'), 'id', $Id[1]);
				$t = 'Area: ' . htmlformat(trim($res['header']));
			}
			else
			{
				$res = $this->getInfo('rpt_ott_header', array('header', 'id', 'expiry'), 'id', $Id[0]);
				$t = 'Product: ' . htmlformat(trim($res['header']));
			}
			
			if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
			{
				$linkExpiry[] = $res['expiry'];
			}
			
			$this->displayHeader($t);
			
			echo '<input type="hidden" name="results" value="' . $resultIds . '"/>'
					. '<input type="hidden" name="type" value="' . substr($ottType, 0, 3) . '"/>'
					. '<input type="hidden" name="time" value="' . $timeMachine . '"/>'
					. '<input type="hidden" name="format" value="' . $globalOptions['encodeFormat'] . '"/>'
					. '<input type="hidden" name="v" value="' . $globalOptions['version'] . '"/>';
			
			if($ottType == 'rowstacked')
			{
				echo '<input type="hidden" id="upmstyle" value="expand"/>';
			}		
			$Values = $this->processOTTData($ottType, $result, $timeMachine, $linkExpiry, $globalOptions);
			
			echo $this->displayWebPage($ottType, $Values['resultIds'], $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], 
			$globalOptions, $timeMachine, $Values['Trials'], $Values['TrialsInfo'], $Values['linkExpiry']);
		}
		else if($ottType == 'indexed') 
		{	
			$TrialsInfo = array();
			$Ids = array();
			
			$timeInterval = $this->getDecodedValue($globalOptions['findChangesFrom']);
			
			$resultIds['product'] = explode(',', $resultIds['product']);
			$resultIds['area'] = explode(',', $resultIds['area']);
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{	
				$t = 'Area: Total';
				$this->displayHeader($t);
				
				$ottType = 'colstackedindexed';
				
				foreach($resultIds['product'] as $pkey => $pvalue)
				{
					$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id = '" . $pvalue . "' ");
					if(mysql_num_rows($res) > 0)
					{
						while($row = mysql_fetch_assoc($res))
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $row['name'] 
							. (($row['company'] !== NULL && $row['company'] != '') ? " / " . $row['company'] . "" : '');
							$TrialsInfo[$pkey]['naUpms'] = 
							$this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
							
							$Ids[$pkey]['product'] = $row['id'];
							$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
						}
					}
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$res = mysql_query("SELECT `name`, `id` FROM `products` WHERE id IN ('" . implode("','", $resultIds['product']) . "') ");
					$row = mysql_fetch_assoc($res);
					
					$productName = $row['name'];
					$productId = $row['id'];
					
					$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $productId);
					$ottType = 'rowstackedindexed';
					
					$t = 'Product: ' . htmlformat($productName);
					$this->displayHeader($t);
					
					foreach($resultIds['area'] as $akey => $avalue)
					{
						$res = mysql_query("SELECT `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								$TrialsInfo[$akey]['sectionHeader'] = $row['name'];
								
								$Ids[$akey]['product'] = $productId;
								$Ids[$akey]['area'] = $row['id'];
							}
						}
					}
					if(!empty($TrialsInfo[0]['naUpms']))
					{
						echo '<input type="hidden" id="upmstyle" value="expand"/>';
					}
				}
				else
				{
					$res = mysql_query("SELECT `name`, `id` FROM `areas` WHERE id IN ('" . implode("','", $resultIds['area']) . "') ");
					$row = mysql_fetch_assoc($res);
					$areaName = $row['name'];
					$areaId = $row['id'];
					
					$ottType = 'colstackedindexed';
					
					$t = 'Area: ' . htmlformat($areaName);
					$this->displayHeader($t);
					
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id = '" . $pvalue . "' ");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								$TrialsInfo[$pkey]['sectionHeader'] = $row['name']
								. (($row['company'] !== NULL && $row['company'] != '') ? " / " . $row['company'] . "" : '');
								$TrialsInfo[$pkey]['naUpms'] = 
								$this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
								
								$Ids[$pkey]['product'] = $row['id'];
								$Ids[$pkey]['area'] = $areaId;
							}
						}
					}
				}
			}
			else 
			{	
				$res = mysql_query("SELECT `name`, `id` FROM `areas` WHERE id IN ('" . implode(',', $resultIds['area']) . "') ");
				$row = mysql_fetch_assoc($res);
				$Ids[0]['area'] = $row['id'];
				
				$t = 'Area: ' . htmlformat($row['name']);
				$this->displayHeader($t);
				
				$res = mysql_query("SELECT `name`, `id`, `company` FROM `products` WHERE id IN ('" . implode(',', $resultIds['product']) . "') ");
				$row = mysql_fetch_assoc($res);
				
				$Ids[0]['product'] = $row['id'];
				
				$TrialsInfo[0]['sectionHeader'] = $row['name']
				. (($row['company'] !== NULL && $row['company'] != '') ? " / " . $row['company'] . "" : '');
				$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
				if(!empty($TrialsInfo[0]['naUpms']))
				{
					echo '<input type="hidden" id="upmstyle" value="expand"/>';
				}
			}
			
			echo '<input type="hidden" name="p" value="' . $_GET['p'] . '"/><input type="hidden" name="a" value="' . $_GET['a'] . '"/>';
			$Values = $this->processIndexedOTTData($ottType, $Ids, $globalOptions);
			
			echo $this->displayWebPage($ottType, $resultIds, $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], 
			$globalOptions, $timeMachine, $Values['Trials'], $TrialsInfo);
		}
		else if($ottType == 'standalone')
		{

			$nctIds = array();
			$Id = mysql_real_escape_string($resultIds);
			if(!is_numeric($Id))
			{
				die('non-numeric id');
			}
			$query = "SELECT id, name, time FROM `rpt_trial_tracker` WHERE id = '" . $Id . "' ";
			$result = mysql_query($query);
			$row = mysql_fetch_assoc($result);
			
			$res = mysql_query("SELECT nctid FROM `rpt_trial_tracker_trials` WHERE report = '" . $row['id'] . "' ");
			if(mysql_num_rows($res) > 0)
			{
				while($arow = mysql_fetch_assoc($res))
				{
					$nctIds[0][] = $arow['nctid'];
				}
			}
			
			$t = '&nbsp;';
			$this->displayHeader($t);
			
			echo '<input type="hidden" name="id" value="' . $Id . '"/>';
			
			$timeMachine = strtotime($row['time']);
			$globalOptions['sectionHeader'] = htmlspecialchars($row['name']);
			
			$Values = $this->processStandaloneOTTData($nctIds, $timeMachine, $globalOptions);
			
			if(!empty($Values['TrialsInfo']))
			{
				echo '<input type="hidden" id="upmstyle" value="expand"/>';
			}
			unset($globalOptions['sectionHeader']);
			
			echo $this->displayWebPage($ottType, $nctIds, $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], 
			$globalOptions, $timeMachine, $Values['Trials'], $Values['TrialsInfo']);
		}
		else if($ottType == 'unstackedoldlink')
		{
			$params 	= unserialize(gzinflate(base64_decode($resultIds['params'])));
			
			$t = 'Area: ' . $params['columnlabel'];
			$this->displayHeader($t);
			
			echo '<input type="hidden" name="leading" value="' . $resultIds['leading'] . '"/>'
					. '<input type="hidden" name="params" value="' . $resultIds['params'] . '"/>'
					.'<input type="hidden" id="upmstyle" value="expand"/>';
					
			$Values = $this->processOldLinkMethod($ottType, array($resultIds['params']), array($resultIds['leading']), $globalOptions);
			
			echo $this->displayWebPage($ottType, $Values['resultIds'], $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], 
			$globalOptions, $timeMachine, $Values['Trials'], $Values['TrialsInfo']);
		}
		else if($ottType == 'stackedoldlink')
		{
			$cparams 	= unserialize(gzinflate(base64_decode($resultIds['cparams'])));
			
			if($cparams['type'] == 'col')
			{
				$t = 'Area: ' . $cparams['columnlabel'];
				echo '<td class="result">Area: ' . $cparams['columnlabel'] . '</td></tr></table>';
			}
			else
			{
				$t = 'Product: ' . $cparams['rowlabel'];
				echo '<td class="result">Product: ' . $cparams['rowlabel'] . '</td></tr></table>';	
			}
			
			$this->displayHeader($t);
						
			echo '<input type="hidden" name="cparams" value="' . $resultIds['cparams'] . '"/>';
			foreach($resultIds['leading'] as $lkey => $lvalue)
			{
				echo '<input type="hidden" name="leading[' . $lkey . ']" value="' . $lvalue . '"/>';
			}
			foreach($resultIds['params'] as $pkey => $pvalue)
			{
				echo '<input type="hidden" name="params[' . $pkey . ']" value="' . $pvalue . '"/>';
			}
				
			if($cparams['type'] != 'col')
			{
				echo '<input type="hidden" id="upmstyle" value="expand"/>';
			}
				
			$Values = $this->processOldLinkMethod($ottType, $resultIds['params'], $resultIds['leading'], $globalOptions, $cparams);
			
			echo $this->displayWebPage($ottType, $Values['resultIds'], $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], 
			$globalOptions, $timeMachine, $Values['Trials'], $Values['TrialsInfo']);
		}
	}
	
	function processOldLinkMethod($ottType, $params, $leadingIds, $globalOptions = array(), $cparams = array())
	{
		global $logger;
		global $now;
		
		$timeInterval = '-' . $globalOptions['change'];
		
		$Trials = array();
		$TrialsInfo = array();
		
		$Trials['inactiveTrials'] = array();
		$Trials['activeTrials'] = array();
		$Trials['allTrials'] = array();
		$Trials['allTrialsforDownload'] = array();
		
		$totinactivecount = 0;

		$totactivecount = 0;
		$totalcount = 0;
		
		foreach($params as $pkey => $pvalue)
		{
			$activeCount = 0;
			$inactiveCount = 0;
			$totalCount = 0;
			
			$Array = array();
			$Array2 = array();
			
			$larvolIds = array();
			$TrialsInfo[$pkey]['naUpms'] = array();
			
			$Params = array();
			$params1 = array();
			$params2 = array();
			$params3 = array();
			$params4 = array();
			$params5 = array();
			$params6 = array();
			$params7 = array();
			
			$pval = unserialize(gzinflate(base64_decode($pvalue)));
			$timeMachine = $pval['time'];
			
			if(!empty($cparams))
			{	
				if($cparams['type'] == 'row')
				{
					$TrialsInfo[$pkey]['sectionHeader'] = $pval['columnlabel'];
				}
				else
				{
					$TrialsInfo[$pkey]['sectionHeader'] = $pval['rowlabel'];
				}
				$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs($pval['upm'], $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);
			}
			else
			{
				$TrialsInfo[$pkey]['sectionHeader'] = $pval['rowlabel'];
				$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs($pval['upm'], $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);
			}
			
			if($pval['params'] === NULL)
			{ 	
				$packedLeadingIDs = gzinflate(base64_decode($leadingIds[$pkey]));
				$leadingIDs = unpack('l*', $packedLeadingIDs);
				if($packedLeadingIDs === false) $leadingIDs = array();
				
				$sp = new SearchParam();
				$sp->field = 'larvol_id';
				$sp->action = 'search';
				$sp->value = implode(' OR ', $leadingIDs);
				$params2 = array($sp);
			} 
			else 
			{
				$params2 = $pval;
			}
			
			if(isset($globalOptions['status']) && !empty($globalOptions['status'])) 
			{
				$status = array();
				foreach($globalOptions['status'] as $skey => $svalue)
				{
					$svalue =  $this->statusFilters[$svalue];
					$status[] = $this->getEnumIds(getFieldId('NCT', 'overall_status'), $svalue);
				}
				
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'overall_status');
				$sp->action = 'search';
				$sp->value = $status;
				$params3[] = $sp;
				
				unset($status);
			}
		
			if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
			{
				foreach($globalOptions['itype'] as $ikey => $ivalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'institution_type';
					$sp->action = 'search';
					$sp->value 	= $this->institutionFilters[$ivalue];
					$params[] = $sp;
				}
				$params4 = $params;
			}
			
			if(isset($globalOptions['region']) && !empty($globalOptions['region'])) 
			{
				foreach($globalOptions['region'] as $rkey => $rvalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'region';
					$sp->action = 'search';
					$sp->value 	= $this->regionFilters[$rvalue];
					$params[] = $sp;
				}
				$params5 = $params;
				
			}
			
			if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
			{
				$phase = array();
				foreach($globalOptions['phase'] as $pkey => $pvalue)
				{	
					$pvalue = array_keys($this->phaseFilters, $pvalue);
					foreach($pvalue as $key => &$value)
					{	
						$value = $this->getEnumIds(getFieldId('NCT', 'phase'), $value);
					}
					$phase = array_merge($pvalue, $phase);
				}
				
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'phase');
				$sp->action = 'search';
				$sp->value 	= $phase;
				$params6[] = $sp;
				
				unset($phase);
			}
			
			if(isset($globalOptions['enroll']) && $globalOptions['enroll'] != '') 
			{
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'enrollment');
				$sp->action = 'search';
				$sp->value 	= str_replace('-', 'TO', $globalOptions['enroll']);
				$params7[] = $sp;
			}
			
			$Params = array_merge($params1, $params2, $params3, $params4, $params5, $params6);
			if(!empty($params2)) 
			{
				$Array = search($Params,$this->fid, NULL, $timeMachine);
			} 
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($Array as $akey => $avalue) 
			{
				foreach($avalue as $key => $value) 
				{
					if(is_array($value))
					{
						if($key == 'NCT/condition' || $key == 'NCT/intervention_name' || $key == 'NCT/lead_sponsor')
						{
							$Array2[$indx][$key] = implode(', ', $value);
						}
						elseif($key == 'NCT/start_date' || $key == 'inactive_date')
						{
							$Array2[$indx][$key] = $value[0];
						}
						elseif($key == 'NCT/phase' || $key == 'NCT/overall_status' || $key == 'NCT/enrollment' || $key == 'NCT/brief_title')
						{
							$Array2[$indx][$key] = end($value);
						}
						else
						{
							$Array2[$indx][$key] = implode(' ', $value);
						}
					}
					else
					{
						$Array2[$indx][$key] = $value;
					}
				}
				++$indx;
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($Array2 as $rkey => $rvalue) 
			{ 
				$nctId = $rvalue['NCT/nct_id'];
				
				$dataset['trials'] = array();
				$dataset['matchedupms'] = array();
				
				//checking for updated and new trials
				$dataset['trials'] = $this->getTrialUpdates($nctId, $rvalue['larvol_id'], $timeMachine, $timeInterval);
				$dataset['trials'] = array_merge($dataset['trials'], array('section' => $pkey));
				
				//checking for updated and new unmatched upms.
				$dataset['matchedupms'] = $this->getMatchedUPMs($nctId, $timeMachine, $timeInterval);

				if($globalOptions['onlyUpdates'] == "yes")
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
					{
						unset($dataset['trials']['edited']['NCT/acronym']);
					}
					
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($dataset['trials']['edited']['NCT/enrollment']))
					{

						$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
						
						if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
						{
							unset($dataset['trials']['edited']['NCT/enrollment']);
						}
					}
					
					//merge only if updates are found
					foreach($dataset['matchedupms'] as $mkey => & $mvalue) 
					{
						if(empty($mvalue['edited']) && $mvalue['new'] != 'y') 
						{
							unset($mvalue);
						}
					}
					
					//merge only if updates are found
					if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
					{	
						if(!empty($globalOptions['status']))
						{	
							$skeys = array_search($rvalue['NCT/overall_status'], $this->statusFilters);
							if(in_array($skeys, $globalOptions['status']))
							{
								$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(!empty($globalOptions['region']))
						{	
							$rkeys = array_search($rvalue['region'], $this->regionFilters);
							if(in_array($rkeys, $globalOptions['region']))
							{
								$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(!empty($globalOptions['phase']))
						{	
							$pkeys = array_search($rvalue['phase'], $this->phaseFilters);
							if(in_array($pkeys, $globalOptions['phase']))
							{
								$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
						{
							$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
				} 
				else 
				{
					if(!empty($globalOptions['status']))
					{	
						$skeys = array_search($rvalue['NCT/overall_status'], $this->statusFilters);
						if(in_array($skeys, $globalOptions['status']))
						{
							$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(!empty($globalOptions['region']))
					{	
						$rkeys = array_search($rvalue['region'], $this->regionFilters);
						if(in_array($rkeys, $globalOptions['region']))
						{
							$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(!empty($globalOptions['phase']))
					{	
						$pkeys = array_search($rvalue['phase'], $this->phaseFilters);
						if(in_array($pkeys, $globalOptions['phase']))
						{
							$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
					{
						$Trials['allTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
						{
							$Trials['inactiveTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
						else
						{
							$Trials['activeTrials'][$rkey] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
					}
				}
				
				if(!in_array($rvalue['NCT/overall_status'],$this->activeStatusValues) && !in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues)) 
				{ 
					$log 	= 'WARN: A new value "' . $rvalue['NCT/overall_status'] 
					. '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				$Trials['allTrialsforDownload'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
				//getting count of active trials from a common function used in run_heatmap.php and here
				$larvolIds[] = $rvalue['larvol_id'];
				sort($larvolIds); 
				
				$totalCount = count($larvolIds);
				$activeCount = getActiveCount($larvolIds, $timeMachine);
				$inactiveCount = $totalCount - $activeCount; 
			}
			
			$totinactivecount  = $inactiveCount + $totinactivecount;
			$totactivecount	= $activeCount + $totactivecount;
			$totalcount		= $totalcount + $totalCount; 
		}
		
		$Values['totactivecount'] = $totactivecount;
		$Values['totinactivecount'] = $totinactivecount;
		$Values['totalcount'] = $totalcount;
		$Values['Trials'] = $Trials[$globalOptions['type']];
		$Values['TrialsInfo'] = $TrialsInfo;
		$Values['allTrialsforDownload'] = $Trials['allTrialsforDownload'];
		
		return  $Values;
	}
	
	function processStandaloneOTTData($resultIds = array(), $timeMachine = NULL, $globalOptions = array())
	{
		$Ids = array();
		$Values = array();
		$Trials = array();
		$TrialsInfo = array();
		
		$Trials['inactiveTrials'] = array();
		$Trials['activeTrials'] = array();
		$Trials['allTrials'] = array();
		$Trials['allTrialsforDownload'] = array();
		
		$totinactivecount = 0;
		$totactivecount = 0;
		$totalcount = 0;
		foreach($resultIds as $ikey => $ivalue)
		{
			$activeCount = 0;
			$inactiveCount = 0;
			$totalCount = 0;
			
			$Array = array();
			$Array2 = array();
			
			$larvolIds = array();
			$Params = array();
			$params1 = array();
			$params2 = array();
			$params3 = array();
			$params4 = array();
			$params5 = array();
			$params6 = array();
			$params7 = array();
			
			$TrialsInfo[$ikey]['sectionHeader'] = $globalOptions['sectionHeader'];
			
			$sp = new SearchParam();
			$sp->field = '_' . getFieldId('NCT', 'nct_id');
			$sp->action = 'search';
			$sp->value = implode(' OR ', $ivalue);
			$params2 = array($sp);
			
			if(isset($globalOptions['status']) && !empty($globalOptions['status'])) 
			{
				$status = array();
				foreach($globalOptions['status'] as $skey => $svalue)
				{
					$svalue =  $this->statusFilters[$svalue];
					$status[] = $this->getEnumIds(getFieldId('NCT', 'overall_status'), $svalue);
				}
				
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'overall_status');
				$sp->action = 'search';
				$sp->value = $status;
				$params3[] = $sp;
				
				unset($status);
			}
		
			if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
			{
				foreach($globalOptions['itype'] as $ikey => $ivalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'institution_type';
					$sp->action = 'search';
					$sp->value 	= $this->institutionFilters[$ivalue];
					$params[] = $sp;
				}
				$params4 = $params;
			}
			
			if(isset($globalOptions['region']) && !empty($globalOptions['region'])) 
			{
				foreach($globalOptions['region'] as $rkey => $rvalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'region';
					$sp->action = 'search';
					$sp->value 	= $this->regionFilters[$rvalue];
					$params[] = $sp;
				}
				$params5 = $params;
				
			}
			
			if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
			{
				$phase = array();
				foreach($globalOptions['phase'] as $pkey => $pvalue)
				{	
					$pvalue = array_keys($this->phaseFilters, $pvalue);
					foreach($pvalue as $key => &$value)
					{	
						$value = $this->getEnumIds(getFieldId('NCT', 'phase'), $value);
					}
					$phase = array_merge($pvalue, $phase);
				}
				
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'phase');
				$sp->action = 'search';
				$sp->value 	= $phase;
				$params6[] = $sp;
				
				unset($phase);
			}
			
			if(isset($globalOptions['enroll']) && $globalOptions['enroll'] != '') 
			{
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'enrollment');
				$sp->action = 'search';
				$sp->value 	= str_replace('-', 'TO', $globalOptions['enroll']);
				$params7[] = $sp;
			}
			
			if(!empty($globalOptions['sortOrder'])) 
			{
				foreach($globalOptions['sortOrder'] as $skey => $svalue)
				{
					$sp = new SearchParam();
					$sp->field = ($skey != 'inactive_date') ? '_' . getFieldId('NCT', $skey) : $skey;
					$sp->action = (substr($svalue, 1, 1) == 'A') ? 'ascending' : ((substr($svalue, 1, 1) == 'D') ? 'descending' : '');
					$params1[] = $sp;
				}
			}
			
			$Params = array_merge($params1, $params2, $params3, $params4, $params5, $params6,  $params7);
			
			if(!empty($params2)) 
			{
				$Array = search($Params,$this->fid, NULL, $timeMachine);
			} 
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($Array as $akey => $avalue) 
			{
				foreach($avalue as $key => $value) 
				{
					if(is_array($value))
					{
						if($key == 'NCT/condition' || $key == 'NCT/intervention_name' || $key == 'NCT/lead_sponsor')
						{
							$Array2[$indx][$key] = implode(', ', $value);
						}
						elseif($key == 'NCT/start_date' || $key == 'inactive_date')
						{
							$Array2[$indx][$key] = $value[0];
						}
						elseif($key == 'NCT/phase' || $key == 'NCT/overall_status' || $key == 'NCT/enrollment' || $key == 'NCT/brief_title')
						{
							$Array2[$indx][$key] = end($value);
						}
						else
						{
							$Array2[$indx][$key] = implode(' ', $value);
						}
					}
					else
					{
						$Array2[$indx][$key] = $value;
					}
				}
				++$indx;
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($Array2 as $rkey => $rvalue) 
			{ 	
				$nctId = $rvalue['NCT/nct_id'];
				
				$dataset['trials'] = array();
				$dataset['matchedupms'] = array();
				
				//checking for updated and new trials
				$dataset['trials'] = $this->getTrialUpdates($nctId, $rvalue['larvol_id'], $timeMachine, $timeInterval);
				$dataset['trials'] = array_merge($dataset['trials'], array('section' => $ikey));
				
				//checking for updated and new unmatched upms.
				$dataset['matchedupms'] = $this->getMatchedUPMs($nctId, $timeMachine, $timeInterval);

				if($globalOptions['onlyUpdates'] == "yes")
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
					{
						unset($dataset['trials']['edited']['NCT/acronym']);
					}
					
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($dataset['trials']['edited']['NCT/enrollment']))
					{
						$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
						
						if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
						{
							unset($dataset['trials']['edited']['NCT/enrollment']);
						}
					}
					
					//merge only if updates are found
					foreach($dataset['matchedupms'] as $mkey => & $mvalue) 
					{
						if(empty($mvalue['edited']) && $mvalue['new'] != 'y') 
						{
							unset($mvalue);
						}
					}
					
					//merge only if updates are found
					if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
					{	
						if(!empty($globalOptions['status']))
						{	
							$skeys = array_search($rvalue['NCT/overall_status'], $this->statusFilters);
							if(in_array($skeys, $globalOptions['status']))
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(!empty($globalOptions['region']))
						{	
							$rkeys = array_search($rvalue['region'], $this->regionFilters);
							if(in_array($rkeys, $globalOptions['region']))
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(!empty($globalOptions['phase']))
						{	
							$pkeys = array_search($rvalue['phase'], $this->phaseFilters);
							if(in_array($pkeys, $globalOptions['phase']))
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
				} 
				else 
				{	
					if(!empty($globalOptions['status']))
					{	
						$skeys = array_search($rvalue['NCT/overall_status'], $this->statusFilters);
						if(in_array($skeys, $globalOptions['status']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(!empty($globalOptions['region']))
					{	
						$rkeys = array_search($rvalue['region'], $this->regionFilters);
						if(in_array($rkeys, $globalOptions['region']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(!empty($globalOptions['phase']))
					{	
						$pkeys = array_search($rvalue['phase'], $this->phaseFilters);
						if(in_array($pkeys, $globalOptions['phase']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
					{
						$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
						{
							$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
						else
						{
							$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
					}
				}
				$Trials['allTrialsforDownload'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
				if(!in_array($rvalue['NCT/overall_status'],$this->activeStatusValues) && !in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues)) 
				{ 
					$log 	= 'WARN: A new value "' . $rvalue['NCT/overall_status'] 
					. '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				
				//getting count of active trials from a common function used in run_heatmap.php and here
				$larvolIds[] = $rvalue['larvol_id'];
				sort($larvolIds); 
				
				$totalCount = count($larvolIds);
				$activeCount = getActiveCount($larvolIds, $timeMachine);
				$inactiveCount = $totalCount - $activeCount; 
			}
			
			$totinactivecount  = $inactiveCount + $totinactivecount;
			$totactivecount	= $activeCount + $totactivecount;
			$totalcount		= $totalcount + $totalCount; 
		}
		
		$Values['resultIds'] = $resultIds;
		$Values['totactivecount'] = $totactivecount;
		$Values['totinactivecount'] = $totinactivecount;
		$Values['totalcount'] = $totalcount;
		$Values['Trials'] = $Trials[$globalOptions['type']];
		$Values['TrialsInfo'] = $TrialsInfo;
		$Values['allTrialsforDownload'] = $Trials['allTrialsforDownload'];
		
		return  $Values;
	}
	
	function getEnumIds($fieldId, $value)
	{
		$query = "SELECT id FROM `data_enumvals` WHERE field = '" . $fieldId . "' AND value = '" . $value . "' ";
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		return $row['id'];
	}
	
	function processIndexedOTTData($ottType, $Ids = array(), $globalOptions = array())
	{	
		global $logger, $now;
		
		$Trials = array();
		$Trials['inactiveTrials'] = array();
		$Trials['activeTrials'] = array();
		$Trials['allTrials'] = array();
		$Trials['allTrialsforDownload'] = array();
		
		$totinactivecount = 0;
		$totactivecount = 0;
		$totalcount = 0;
		
		$where = '';
		$orderBy = " dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
		$phaseFilters = array('N/A'=>'0', '0'=>'0', '0/1'=>'1', '1'=>'1', '1a'=>'1', '1b'=>'1', '1a/1b'=>'1', '1c'=>'1', 
									'1/2'=>'2', '1b/2'=>'2', '1b/2a'=>'2', '2'=>'2', '2a'=>'2', '2a/2b'=>'2', '2a/b'=>'2', '2b'=>'2', 
									'2/3'=>'3', '2b/3'=>'3','3'=>'3', '3a'=>'3', '3b'=>'3', 
									'3/4'=>'4', '3b/4'=>'4', '4'=>'4');
		
		if($timeMachine === NULL) $timeMachine = $now;
		
		$timeInterval = '-' . $globalOptions['change'];
		
		$fieldNames = array('end_date_lastchanged', 'region_lastchanged', 'brief_title_lastchanged', 'acronym_lastchanged', 'lead_sponsor_lastchanged',
		'overall_status_lastchanged', 'start_date_lastchanged', 'phase_lastchanged', 'enrollment_lastchanged', 
		'collaborator_lastchanged', 'condition_lastchanged', 'intervention_name_lastchanged');
		
		if(isset($globalOptions['status']) && !empty($globalOptions['status'])) 
		{
			$status = array();
			foreach($globalOptions['status'] as $skey => $svalue)
			{
				$status[] = $this->statusFilters[$svalue];
			}
			
			$where .= " AND (dt.`overall_status` IN ('"  . implode("','", $status) . "') )";
			unset($status);
		}
		
		if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
		{
			$itype = array();
			foreach($globalOptions['itype'] as $ikey => $ivalue)
			{
				$itype[] = $this->institutionFilters[$ivalue];
			}
			
			$where .= " AND (dt.`institution_type` IN ('"  . implode("','", $itype) . "') )";
			unset($itype);
		}
		
		if(isset($globalOptions['region']) && !empty($globalOptions['region'])) 
		{
			$region = array();
			foreach($globalOptions['region'] as $rkey => $rvalue)
			{
				$region[] = $this->regionFilters[$rvalue];
			}
			
			$where .= " AND (dt.`region` IN ('"  . implode("','", $region) . "') )";
			unset($region);
		}
		
		if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
		{
			$phase = array();
			foreach($globalOptions['phase'] as $pkey => $pvalue)
			{
				$phase[] = implode("','", array_keys($phaseFilters, $pvalue));
			}
			
			$where .= " AND (dt.`phase` IN ('"  . implode("','", $phase) . "') )";
			unset($phase);
		}
		
		if(isset($globalOptions['enroll']) && $globalOptions['enroll'] != '') 
		{
			$enroll = array();
			$enroll = explode(' - ', $globalOptions['enroll']);
			
			$where .= " AND (dt.`enrollment` >= '"  . $enroll[0] . "' AND dt.`enrollment` <= '"  . $enroll[1] . "' )";
			unset($enroll);
		}
		
		$previousValue = 'Previous value: ';	
		$noPreviousValue = 'No previous value';	
		
		foreach($Ids as $ikey => $ivalue)
		{		
			$inactiveCount = 0;
			$activeCount = 0;
			$index = 0;
			
			$result = array();
			$larvolIds = array();
			$dataset['matchedupms'] = array();
			
			$query = "SELECT dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`,"
					. " dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`enrollment_type`, dt.`intervention_name`,"
					. " dt.`region`, dt.`lastchanged_date`, dt.`phase`, dt.`overall_status`, dt.`lastchanged_date`, dt.`firstreceived_date`, dt.`viewcount` "
					. " FROM `data_trials` dt "
					. " JOIN `product_trials` pt ON dt.`larvol_id` = pt.`trial` "
					. " JOIN `area_trials` at ON dt.`larvol_id` = at.`trial` "
					. " WHERE pt.`product` IN ('" . $ivalue['product'] . "') AND at.`area` IN ('" . $ivalue['area'] . "') " 
					. $where . " ORDER BY " . $orderBy;
			$res = mysql_query($query);
			while($row = mysql_fetch_assoc($res))
			{
				if($row['is_active'] == 1) 
				{
					$activeCount++;
				}
				else
				{
					$inactiveCount++;
				}
				
				$nctId = unpadnct($row['source_id']);
				
				$result[$index]['larvol_id'] = $row['larvol_id'];
				$result[$index]['inactive_date'] = $row['end_date'];
				$result[$index]['region'] = $row['region'];
				$result[$index]['NCT/nct_id'] = $nctId;
				$result[$index]['NCT/brief_title'] = stripslashes($row['brief_title']);
				$result[$index]['NCT/enrollment_type'] = $row['enrollment_type'];
				$result[$index]['NCT/acronym'] = $row['acronym'];
				$result[$index]['NCT/lead_sponsor'] = str_replace('`', ', ', $row['lead_sponsor']);
				$result[$index]['NCT/start_date'] = $row['start_date'];
				$result[$index]['NCT/phase'] = $row['phase'];
				$result[$index]['NCT/enrollment'] = $row['enrollment'];
				$result[$index]['NCT/collaborator'] = str_replace('`', ', ', $row['collaborator']);
				$result[$index]['NCT/condition'] = str_replace('`', ', ', stripslashes($row['condition']));
				$result[$index]['NCT/intervention_name'] = str_replace('`', ', ', $row['intervention_name']);
				$result[$index]['NCT/overall_status'] = $row['overall_status'];
				$result[$index]['NCT/is_active'] = $row['is_active'];
				$result[$index]['section'] = $ikey;
				$result[$index]['new'] = 'n';
				$result[$index]['edited'] = array();
				$result[$index]['viewcount'] = $row['viewcount']; 
				
				if($row['firstreceived_date'] <= date('Y-m-d', $timeMachine) && $row['firstreceived_date'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					$result[$index]['new'] = 'y';
				}
							
				if($row['lastchanged_date'] <= date('Y-m-d', $timeMachine) && $row['lastchanged_date'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{					
					$uquery = "SELECT `end_date_prev`, `region_prev`, `brief_title_prev`, `acronym_prev`, `lead_sponsor_prev`, `overall_status_prev`, "
							. "`overall_status_lastchanged`, `start_date_prev`, `phase_prev`, `enrollment_prev`, `collaborator_prev`, `condition_prev`, "
							. " `intervention_name_prev`, `"
							. implode("`, `", $fieldNames) . "` FROM `data_history` WHERE `larvol_id` = '" . $row['larvol_id'] . "' AND ( (`" 
							. implode('` BETWEEN "' . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . '" AND "' . date('Y-m-d', $timeMachine) 
							. '") OR (`', $fieldNames) . "` BETWEEN '" . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "' AND '" 
							. date('Y-m-d', $timeMachine) . "') ) ";
					$ures = mysql_query($uquery);
					while($arr = mysql_fetch_assoc($ures))
					{
						if($arr['end_date_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['end_date_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['end_date_prev'] != '' && $arr['end_date_prev'] !== NULL)
							{
								$result[$index]['edited']['inactive_date'] = $previousValue . $arr['end_date_prev'];
							}
							else
							{
								$result[$index]['edited']['inactive_date'] = $noPreviousValue;
							}
						}
						
						if($arr['region_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['region_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['region_prev'] != '' && $arr['region_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/region'] = $previousValue . $arr['region_prev'];
							}
							else
							{
								$result[$index]['edited']['NCT/region'] = $noPreviousValue;
							}
						}
						
						if($arr['brief_title_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['brief_title_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['brief_title_prev'] != '' && $arr['brief_title_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/brief_title'] = $previousValue . stripslashes($arr['brief_title_prev']);
							}
							else
							{
								$result[$index]['edited']['NCT/brief_title'] = $noPreviousValue;
							}
						}
						
						if($arr['acronym_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['acronym_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['acronym_prev'] != '' && $arr['acronym_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/acronym'] = $previousValue . $arr['acronym_prev'];
							}
							else
							{
								$result[$index]['edited']['NCT/acronym'] = $noPreviousValue;
							}
						}
						
						if($arr['lead_sponsor_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['lead_sponsor_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['lead_sponsor_prev'] != '' && $arr['lead_sponsor_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/lead_sponsor'] = $previousValue . str_replace('`', ', ', $arr['lead_sponsor_prev']);
							}
							else
							{
								$result[$index]['edited']['NCT/lead_sponsor'] = $noPreviousValue;
							}
						}

						if($arr['start_date_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['start_date_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['start_date_prev'] != '' && $arr['start_date_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/start_date'] = $previousValue . $arr['start_date_prev'];
							}
							else
							{
								$result[$index]['edited']['NCT/start_date'] = $noPreviousValue;
							}
						}

						if($arr['phase_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['phase_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['phase_prev'] != '' && $arr['phase_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/phase'] = $previousValue . $arr['phase_prev'];
							}
							else
							{
								$result[$index]['edited']['NCT/phase'] = $noPreviousValue;
							}
						}
							
						if($arr['enrollment_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['enrollment_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['enrollment_prev'] != '' && $arr['enrollment_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/enrollment'] = $previousValue . $arr['enrollment_prev'];
							}
							else
							{
								$result[$index]['edited']['NCT/enrollment'] = $noPreviousValue;
							}
						}

						if($arr['collaborator_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['collaborator_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['collaborator_prev'] != '' && $arr['collaborator_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/collaborator'] = $previousValue . str_replace('`', ', ', $arr['collaborator_prev']);
							}
							else
							{
								$result[$index]['edited']['NCT/collaborator'] = $noPreviousValue;
							}
						}

						if($arr['condition_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['condition_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['condition_prev'] != '' && $arr['condition_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/condition'] = $previousValue . str_replace('`', ', ', stripslashes($arr['condition_prev']));
							}
							else
							{
								$result[$index]['edited']['NCT/condition'] = $noPreviousValue;
							}
						}

						if($arr['intervention_name_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['intervention_name_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['intervention_name_prev'] != '' && $arr['intervention_name_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/intervention_name'] = $previousValue . str_replace('`', ', ', $arr['intervention_name_prev']);
							}
							else
							{
								$result[$index]['edited']['NCT/intervention_name'] = $noPreviousValue;
							}
						}

						if($arr['overall_status_lastchanged'] < date('Y-m-d', $timeMachine) 
						&& $arr['overall_status_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
						{
							if($arr['overall_status_prev'] != '' && $arr['overall_status_prev'] !== NULL)
							{
								$result[$index]['edited']['NCT/overall_status'] = $previousValue . str_replace('`', ', ', $arr['overall_status_prev']);
							}
							else
							{
								$result[$index]['edited']['NCT/overall_status'] = $noPreviousValue;
							}
						}
					}
				}
				
				$dataset['matchedupms'] = $this->getMatchedUPMs($nctId, $timeMachine, $timeInterval);
				
				if($globalOptions['onlyUpdates'] == "yes")
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($result[$index]['edited']['NCT/acronym']) && !isset($result[$ikey]['NCT/acronym'])) 
					{
						unset($result[$index]['edited']['NCT/acronym']);
					}
					
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($result[$index]['edited']['NCT/enrollment'])) 
					{ 
						$prevValue = substr($result[$index]['edited']['NCT/enrollment'],16);
						if(!getDifference($prevValue, $result[$index]['NCT/enrollment'])) 
						{
							unset($result[$index]['edited']['NCT/enrollment']);
						}
					}
					
					//merge only if updates are found
					foreach($dataset['matchedupms'] as $mkey => & $mvalue) 
					{
						if(empty($mvalue['edited']) && $mvalue['new'] != 'y') 
						{
							unset($mvalue);
						}
					}
					
					if(!empty($result[$index]['edited']) || $result[$index]['new'] == 'y')
					{
						if(!empty($globalOptions['status']))
						{	
							$skeys = array_search($result[$index]['NCT/overall_status'], $this->statusFilters);
							if(in_array($skeys, $globalOptions['status']))
							{
								$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
								if($result[$index]['NCT/is_active'] != 1)
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
								}
							}
						}
                        if(!empty($globalOptions['region']))
    					{	
    						$rkeys = array_search($result[$index]['region'], $this->regionFilters);
    						if(in_array($rkeys, $globalOptions['region']))
    						{
    							$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    							if($result[$index]['NCT/is_active'] != 1)
    							{
    								$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    							}
    							else
    							{
    								$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    							}
    						}
    					}                       
                        if(!empty($globalOptions['phase']))
    					{	
    						$pkeys = array_search($result[$index]['phase'], $this->phaseFilters);
    						if(in_array($pkeys, $globalOptions['phase']))
    						{
    							$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    							if($result[$index]['NCT/is_active'] != 1)
    							{
    								$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    							}
    							else
    							{
    								$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    							}
    						}
    					}
                        if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
    					{
    						$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    						if($result[$index]['NCT/is_active'] != 1)
    						{
    							$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    						}
    						else
    						{
    							$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
    						}
    					}
					}
				}
				else 
				{	
					if(!empty($globalOptions['status']))
					{	
						$skeys = array_search($result[$index]['NCT/overall_status'], $this->statusFilters);
						if(in_array($skeys, $globalOptions['status']))
						{
							$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							if($result[$index]['NCT/is_active'] != 1)
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							}
						}
					}
                    if(!empty($globalOptions['region']))
					{	
						$rkeys = array_search($result[$index]['region'], $this->regionFilters);
						if(in_array($rkeys, $globalOptions['region']))
						{
							$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							if($result[$index]['NCT/is_active'] != 1)
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							}
						}
					}
                    if(!empty($globalOptions['phase']))
					{	
						$pkeys = array_search($result[$index]['phase'], $this->phaseFilters);
						if(in_array($pkeys, $globalOptions['phase']))
						{
							$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							if($result[$index]['NCT/is_active'] != 1)
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
							}
						}
					}					
					if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
					{
						$Trials['allTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
						if($result[$index]['NCT/is_active'] != 1)
						{
							$Trials['inactiveTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
						}
						else
						{
							$Trials['activeTrials'][] = array_merge($dataset['matchedupms'], $result[$index]);
						}
					}
				}
				$Trials['allTrialsforDownload'][] = array_merge($dataset['matchedupms'], $result[$index]);
				
				$index++;
			}	
			$totinactivecount  = $inactiveCount + $totinactivecount;
			$totactivecount	= $activeCount + $totactivecount;
			$totalcount		= $totalcount + $inactiveCount + $activeCount; 
		}
		
		$Values['totactivecount'] = $totactivecount;
		$Values['totinactivecount'] = $totinactivecount;
		$Values['totalcount'] = $totalcount;
		$Values['Trials'] = $Trials[$globalOptions['type']];
		$Values['allTrialsforDownload'] = $Trials['allTrialsforDownload'];
		
		return  $Values;
	}
	
	function processOTTData($ottType, $resultIds, $timeMachine = NULL, $linkExpiryDt = array(), $globalOptions = array())
	{	
		global $logger;
		global $now;
		
		$timeInterval = '-' . $globalOptions['change'];
		
		$Ids = array();
		$Values = array();
		$Trials = array();
		$TrialsInfo = array();
		$linkExpiry = array();
		
		$Trials['inactiveTrials'] = array();
		$Trials['activeTrials'] = array();
		$Trials['allTrials'] = array();
		$Trials['allTrialsforDownload'] = array();
		
		$totinactivecount = 0;
		$totactivecount = 0;
		$totalcount = 0;
		
		foreach($resultIds as $ikey => $ivalue)
		{
			$activeCount = 0;
			$inactiveCount = 0;
			$totalCount = 0;
			
			$linkExpiry[$ikey] = array();
			$Array = array();
			$Array2 = array();
			
			$larvolIds = array();
			$TrialsInfo[$ikey]['naUpms'] = array();
			
			$Params = array();
			$params1 = array();
			$params2 = array();
			$params3 = array();
			$params4 = array();
			$params5 = array();
			$params6 = array();
			$params7 = array();
			
			$Ids = explode('.', $ivalue);
			
			//Retrieving headers
			if($ottType == 'rowstacked') 
			{
				$res = $this->getInfo('rpt_ott_header', array('header', 'id', 'expiry'), 'id', $Ids[1]);
			} 
			else
			{
				$res = $this->getInfo('rpt_ott_header', array('header', 'id', 'expiry'), 'id', $Ids[0]);
			}
			
			if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
			{
				$linkExpiry[$ikey] = array_merge($linkExpiryDt, array($res['expiry']));
			}
			
			$sectionHeader = htmlentities($res['header']);
			
			if($Ids[2] == '-1' || $Ids[2] == '-2') 
			{
				if($Ids[2] == '-2') 
				{
					$res = $this->getInfo('rpt_ott_searchdata', array('result_set', 'id', 'expiry'), 'id', $Ids[3]);
					$params2 = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
				}
				else
				{
					$res = $this->getInfo('rpt_ott_trials', array('result_set', 'id', 'expiry'), 'id', $Ids[3]);
					if($res['result_set'] != '') 
					{
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$params2 = array($sp);
					}
				}
				
				if($res['expiry'] != '' && $res['expiry'] !== NULL)
				{	
					$linkExpiry[$ikey] = array_merge($linkExpiryDt, array($res['expiry']));
				}
				
				if(isset($Ids[4]))
				{	
					$res = $this->getInfo('rpt_ott_upm', array('intervention_name', 'id', 'expiry'), 'id', $Ids[4]);
					if($globalOptions['version'] == 1)
					{
						$res['intervention_name'] = explode('\n', $res['intervention_name']);
					}
					else
					{
						$res['intervention_name'] = explode(',', $res['intervention_name']);
					}
					
					if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
					{
						$linkExpiry[$ikey] = array_merge($linkExpiryDt, array($res['expiry']));
					}
					
					$TrialsInfo[$ikey]['naUpms'] = $this->getUnMatchedUPMs($res['intervention_name'], $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);	
				}
			}
			else
			{
				$searchData = substr($Ids[2],0,3);
				if(dechex($searchData) == '73' && chr($searchData) == 's') 
				{
					$res = $this->getInfo('rpt_ott_searchdata', array('result_set', 'id', 'expiry'), 'id', substr($Ids[2],3));
					$params2 = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
				}
				else
				{
					$res = $this->getInfo('rpt_ott_trials', array('result_set', 'id', 'expiry'), 'id', $Ids[2]);
					if($res['result_set'] != '') 
					{	
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$params2 = array($sp);
					}
				}
				
				if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
				{
					$linkExpiry[$ikey] = array_merge($linkExpiryDt, array($res['expiry']));
				}
				
				if(isset($Ids[3])) 
				{ 
					$res = $this->getInfo('rpt_ott_upm', array('intervention_name', 'id', 'expiry'), 'id', $Ids[3]);
					if($globalOptions['version'] == 1)
					{
						$res['intervention_name'] = explode('\n', $res['intervention_name']);
					}
					else
					{
						$res['intervention_name'] = explode(',', $res['intervention_name']);
					}
					
					if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
					{
						$linkExpiry[$ikey] = array_merge($linkExpiryDt, array($res['expiry']));
					}
					
					$TrialsInfo[$ikey]['naUpms'] = $this->getUnMatchedUPMs($res['intervention_name'], $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);	
				}
			}
			
			$TrialsInfo[$ikey]['sectionHeader'] = $sectionHeader;
			
			if(isset($globalOptions['status']) && !empty($globalOptions['status'])) 
			{
				$status = array();
				foreach($globalOptions['status'] as $skey => $svalue)
				{
					$svalue =  $this->statusFilters[$svalue];
					$status[] = $this->getEnumIds(getFieldId('NCT', 'overall_status'), $svalue);
				}
				
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'overall_status');
				$sp->action = 'search';
				$sp->value = $status;
				$params3[] = $sp;
				
				unset($status);
			}
		
			if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
			{
				foreach($globalOptions['itype'] as $ikey => $ivalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'institution_type';
					$sp->action = 'search';
					$sp->value 	= $this->institutionFilters[$ivalue];
					$params[] = $sp;
				}
				$params4 = $params;
			}
			
			if(isset($globalOptions['region']) && !empty($globalOptions['region'])) 
			{
				foreach($globalOptions['region'] as $rkey => $rvalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'region';
					$sp->action = 'search';
					$sp->value 	= $this->regionFilters[$rvalue];
					$params[] = $sp;
				}
				$params5 = $params;
			}
			
			if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
			{
				$phase = array();
				foreach($globalOptions['phase'] as $pkey => $pvalue)
				{	
					$pvalue = array_keys($this->phaseFilters, $pvalue);
					foreach($pvalue as $key => &$value)
					{	
						$value = $this->getEnumIds(getFieldId('NCT', 'phase'), $value);
					}
					$phase = array_merge($pvalue, $phase);
				}
				
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'phase');
				$sp->action = 'search';
				$sp->value 	= $phase;
				$params6[] = $sp;
				
				unset($phase);
			}
		
			 if(isset($globalOptions['enroll']) && $globalOptions['enroll'] != '') 
			{
				$sp = new SearchParam();
				$sp->field = '_' . getFieldId('NCT', 'enrollment');
				$sp->action = 'search';
				$sp->value 	= str_replace('-', 'TO', $globalOptions['enroll']);
				$params7[] = $sp;
			}
			
			if(!empty($globalOptions['sortOrder'])) 
			{
				foreach($globalOptions['sortOrder'] as $skey => $svalue)
				{
					$sortType = substr($svalue, 1, 1);
					if($sortType == 'A' || $sortType == 'D')
					{
						$sp = new SearchParam();
						$sp->field = ($skey != 'inactive_date') ? '_' . getFieldId('NCT', $skey) : $skey;
						$sp->action = ($sortType == 'A') ? 'ascending' : (($sortType == 'D') ? 'descending' : '');
						$params1[] = $sp;
					}
				}
			}
			
			$Params = array_merge($params1, $params2, $params3, $params4, $params5, $params6,  $params7);
			
			if(!empty($params2)) 
			{
				$Array = search($Params,$this->fid, NULL, $timeMachine);
			} 
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($Array as $akey => $avalue) 
			{
				foreach($avalue as $key => $value) 
				{
					if(is_array($value))
					{
						if($key == 'NCT/condition' || $key == 'NCT/intervention_name' || $key == 'NCT/lead_sponsor')
						{
							$Array2[$indx][$key] = implode(', ', $value);
						}
						elseif($key == 'NCT/start_date' || $key == 'inactive_date')
						{
							$Array2[$indx][$key] = $value[0];
						}
						elseif($key == 'NCT/phase' || $key == 'NCT/overall_status' || $key == 'NCT/enrollment' || $key == 'NCT/brief_title')
						{
							$Array2[$indx][$key] = end($value);
						}
						else
						{
							$Array2[$indx][$key] = implode(' ', $value);
						}
					}
					else
					{
						$Array2[$indx][$key] = $value;
					}
				}
				++$indx;
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($Array2 as $rkey => $rvalue) 
			{ 
				$nctId = $rvalue['NCT/nct_id'];
				
				$dataset['trials'] = array();
				$dataset['matchedupms'] = array();
				
				//checking for updated and new trials
				$dataset['trials'] = $this->getTrialUpdates($nctId, $rvalue['larvol_id'], $timeMachine, $timeInterval);
				$dataset['trials'] = array_merge($dataset['trials'], array('section' => $ikey));
				
				//checking for updated and new unmatched upms.
				$dataset['matchedupms'] = $this->getMatchedUPMs($nctId, $timeMachine, $timeInterval);

				if($globalOptions['onlyUpdates'] == "yes")
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($dataset['trials']['edited']['NCT/acronym']) && !isset($rvalue['NCT/acronym'])) 
					{
						unset($dataset['trials']['edited']['NCT/acronym']);
					}
					
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($dataset['trials']['edited']['NCT/enrollment']))
					{
						$prevValue = substr($dataset['trials']['edited']['NCT/enrollment'],16);
						
						if(!getDifference($prevValue, $rvalue['NCT/enrollment'])) 
						{
							unset($dataset['trials']['edited']['NCT/enrollment']);
						}
					}
					
					//merge only if updates are found
					foreach($dataset['matchedupms'] as $mkey => & $mvalue) 
					{
						if(empty($mvalue['edited']) && $mvalue['new'] != 'y') 
						{
							unset($mvalue);
						}
					}
					
					//merge only if updates are found
					if(!empty($dataset['trials']['edited']) || $dataset['trials']['new'] == 'y')
					{	
						if(!empty($globalOptions['status']))
						{	
							$skeys = array_search($rvalue['NCT/overall_status'], $this->statusFilters);
							if(in_array($skeys, $globalOptions['status']))
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(!empty($globalOptions['region']))
						{	
							$rkeys = array_search($rvalue['region'], $this->regionFilters);
							if(in_array($rkeys, $globalOptions['region']))
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(!empty($globalOptions['phase']))
						{	
							$pkeys = array_search($rvalue['phase'], $this->phaseFilters);
							if(in_array($pkeys, $globalOptions['phase']))
							{
								$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
						}
						if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
				} 
				else 
				{
					if(!empty($globalOptions['status']))
					{	
						$skeys = array_search($rvalue['NCT/overall_status'], $this->statusFilters);
						if(in_array($skeys, $globalOptions['status']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(!empty($globalOptions['region']))
					{	
						$rkeys = array_search($rvalue['region'], $this->regionFilters);
						if(in_array($rkeys, $globalOptions['region']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(!empty($globalOptions['phase']))
					{	
						$pkeys = array_search($rvalue['phase'], $this->phaseFilters);
						if(in_array($pkeys, $globalOptions['phase']))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
					}
					if(empty($globalOptions['status']) && empty($globalOptions['region']) && empty($globalOptions['phase']))
					{
						$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
						{
							$Trials['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
						else
						{
							$Trials['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
					}
				}
				$Trials['allTrialsforDownload'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
				if(!in_array($rvalue['NCT/overall_status'],$this->activeStatusValues) && !in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues)) 
				{ 
					$log 	= 'WARN: A new value "' . $rvalue['NCT/overall_status'] 
					. '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				
				//getting count of active trials from a common function used in run_heatmap.php and here
				$larvolIds[] = $rvalue['larvol_id'];
				sort($larvolIds); 
				
				$totalCount = count($larvolIds);
				$activeCount = getActiveCount($larvolIds, $timeMachine);
				$inactiveCount = $totalCount - $activeCount; 
			}
			
			$totinactivecount  = $inactiveCount + $totinactivecount;
			$totactivecount	= $activeCount + $totactivecount;
			$totalcount		= $totalcount + $totalCount; 
			
			//expiry feature for new link method
			$linkExpiry[$ikey] = array_unique($linkExpiry[$ikey]);
			if(!empty($linkExpiry[$ikey])) 
			{
				usort($linkExpiry[$ikey], "cmpdate");
				if(!empty($linkExpiry[$ikey])) 
				{
					$linkExpiryDate = $linkExpiry[$ikey][0];
					
					if(($linkExpiryDate < date('Y-m-d', $now)) || ($linkExpiryDate < date('Y-m-d',strtotime('+1 week', $now)))) 
					{
						$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week', $now)) . "' WHERE id = '" . $Ids[0] . "' ";
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for row header' . "\n" . $query);
						
						$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week', $now)) . "' WHERE id = '" . $Ids[1] . "' ";
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for col header' . "\n" . $query);
						
						if($Ids[2] == '-1' || $Ids[2] == '-2') 
						{
							if($Ids[2] == '-1')
							{
								$tableName = 'rpt_ott_trials';
							}
							if($Ids[2] == '-2')
							{
								$tableName = 'rpt_ott_searchdata';
							}
							$query = "UPDATE " . $tableName . " SET `expiry` = '" . date('Y-m-d',strtotime('+1 week', $now)) . "' WHERE id = '" . $Ids[3] . "' ";
							$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for trials result set' . "\n" . $query);
							
							if(isset($Ids[4]) && $Ids[4] != '') 
							{
								$query = "UPDATE `rpt_ott_upm` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $Ids[4] . "' ";
								$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for upms' . "\n" . $query);
							}
						}
						else
						{
							$searchData = substr($Ids[2],0,3);
							if(dechex($searchData) == '73' && chr($searchData) == 's') 
							{
								$tableName = 'rpt_ott_searchdata';
							}
							else
							{
								$tableName = 'rpt_ott_trials';
							}
							$query = "UPDATE " . $tableName . " SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $Ids[2] . "' ";
							$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for trials result set' . "\n" . $query);
							
							if(isset($Ids[3]) && $Ids[3] != '') 
							{
								$query = "UPDATE `rpt_ott_upm` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $Ids[3] . "' ";
								$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for upms' . "\n" . $query);
							}
						}
					}
					unset($linkExpiryDate);
				}
			}
		}
		
		$linkExpiryDate = array();
		foreach($linkExpiry as $lkey => $lvalue) 
		{
			foreach($lvalue as $lk => $lv) 
			{
				$linkExpiryDate[] = $lv;
			}
		}
		if(!empty($linkExpiryDate)) 
		{	
			$Values['linkExpiry'] = $linkExpiryDate[0];
			unset($linkExpiryDate);
		}
		
		$Values['resultIds'] = $resultIds;
		$Values['totactivecount'] = $totactivecount;
		$Values['totinactivecount'] = $totinactivecount;
		$Values['totalcount'] = $totalcount;
		$Values['Trials'] = $Trials[$globalOptions['type']];
		$Values['TrialsInfo'] = $TrialsInfo;
		$Values['allTrialsforDownload'] = $Trials['allTrialsforDownload'];
		
		return  $Values;
	}
	
	function displayWebPage($ottType, $resultIds, $totactivecount, $totinactivecount, $totalcount, $globalOptions, $timeMachine = NULL, $Trials, $TrialsInfo, 
	$linkExpiry = NULL)
	{	
		global $db;
		$loggedIn	= $db->loggedIn();
		
		$count = count($Trials);
		$start 	= '';
		$last = '';
		$totalPages = '';
		
		$start 	= ($globalOptions['page']-1) * $this->resultsPerPage + 1;
		$last 	= ($globalOptions['page'] * $this->resultsPerPage > $count) ? $count : ($start + $this->resultsPerPage - 1);
		$totalPages = ceil($count / $this->resultsPerPage);
		
		if(isset($globalOptions['countDetails']) && !empty($globalOptions['countDetails'])) 
		{
			$totactivecount = $globalOptions['countDetails']['a'];
			$totinactivecount = $globalOptions['countDetails']['in'];
			$totalcount = $totactivecount + $totinactivecount;
		}
		$this->displayFilterControls($count, $totactivecount, $totinactivecount, $totalcount, $globalOptions);
		if($totalPages > 1)
		{
			$this->pagination($globalOptions, $totalPages, $timeMachine, $ottType);
		}
		echo $this->displayTrialTableHeader($loggedIn, $globalOptions);
		echo $this->displayTrials($globalOptions, $loggedIn, $start, $last, $Trials, $TrialsInfo, $ottType);
		
		echo '</table>';
		
		echo '<input type="hidden" name="cd" value="' 
		. rawurlencode(base64_encode(gzdeflate(serialize(array('a'=>$totactivecount, 'in'=>$totinactivecount))))). '" />';	
		
		if($totalPages > 1)
		{
			$this->pagination($globalOptions, $totalPages, $timeMachine, $ottType);
		}
		echo '</form><br/>';
		
		if($totalcount > 0 && ($ottType != 'unstackedoldlink' && $ottType != 'stackedoldlink')) 
		{
			$this->downloadOptions($count, $totalcount, $ottType, $resultIds, $timeMachine, $globalOptions);
		}
		echo '<br/><br/>';
		if($linkExpiry !== NULL && $loggedIn)
		{
			echo '<span style="font-size:10px;color:red;">Expires on: ' . $linkExpiry  . '</span>';
		}
		echo '<div style="height:50px;"></div>';	//50Pixels extra space
	}
	
	function downloadOptions($shownCnt, $foundCnt, $ottType, $result, $timeMachine = NULL, $globalOptions) 
	{	
		echo '<div style="height:100px;margin-top:10px;"><div class="drop downldbox"><div class="newtext">Download Options</div>'
				. '<form  id="frmDOptions" name="frmDOptions" method="post" target="_self">'
				. '<input type="hidden" name="ottType" value="' . $ottType . '" />'
				. '<input type="hidden" name="timeMachine" value="' . $timeMachine . '" />';
				foreach($result as $rkey => $rvalue)
				{
					if(is_array($rvalue))
					{
						foreach($rvalue as $rk => $rv)
						{
							echo '<input type="hidden" name="resultIds[' . $rkey . '][' . $rk . ']" value="' . $rv . '" />';
						}
					}
					else
					{
						echo '<input type="hidden" name="resultIds[' . $rkey . ']" value="' . $rvalue . '" />';
					}
				}
				foreach($globalOptions as $gkey => $gvalue)
				{	
					if(is_array($gvalue))
					{	
						foreach($gvalue as $gk => $gv)
						{	
							echo '<input type="hidden" name="globalOptions[' . $gkey . '][' . $gk . ']" value="' . $gv . '" />';
						}
					}
					else
					{	
						echo '<input type="hidden" name="globalOptions[' . $gkey . ']" value="' . $gvalue . '" />';
					}
				}	
		echo '<ul><li><label>Number of Studies: </label></li>'
				. '<li><select id="dOption" name="dOption">'
				. '<option value="shown" selected="selected">' . $shownCnt . ' Shown Studies</option>'
				. '<option value="all">' . $foundCnt . ' Found Studies</option></select></li>'
				. '<li><label>Which Format: </label></li><li><select id="wFormat" name="wFormat">'
				. '<option value="excel" selected="selected">Excel</option><option value="xml">XML</option><option value="pdf">PDF</option></select></li></ul>'
				. '<input type="hidden" name="shownCnt" value="' . $shownCnt . '" />'
				. '<input type="submit" id="btnDownload" name="btnDownload" value="Download File" style="margin-left:8px;"  />'
				. '</form></div></div>';
	}
	
	function displayTrialTableHeader($loggedIn, $globalOptions = array()) 
	{
		$outputStr = '<table width="100%" cellpadding="5" cellspacing="0" class="manage">'
			 . '<tr>' . (($loggedIn) ? '<th width="38px">ID</th>' : '' )
			 . '<th width="250px">Title</th>'
			 . '<th width="30px" title="Black: Actual&nbsp;&nbsp;Gray: Anticipated&nbsp;&nbsp;Red: Change greater than 20%">N</th>'
			 . '<th width="60px" title="&quot;ROW&quot; = Rest of World">Region</th>'
			 . '<th width="115px">Interventions</th>'
			 . '<th width="90px">Sponsor</th>'
			 . '<th width="105px">Status</th>'
			 . '<th width="110px">Conditions</th>'
			 . '<th width="25px" title="MM/YY">Start</th>'
			 . '<th width="25px" title="MM/YY">End</th>'
			 . '<th width="15px">Ph</th>'
			 . '<th width="16px" style="padding-left:0;">Res</th>'
			 . '<th width="4px" colspan="3">-</th>'
			 . '<th width="24px" colspan="12">' . (date('Y')) . '</th>'
			 . '<th width="24px" colspan="12">' . (date('Y')+1) . '</th>'
			 . '<th width="24px" colspan="12">' . (date('Y')+2) . '</th>'
			 . '<th width="4px" colspan="3">+</th></tr>';
		
		return $outputStr;

	}
		
	function getResultSet($resultIds, $stackType)
	{	
		$three = 0;
		$lengthcounter = 0; 
		$string = '';
		$ouput = array();
		
		foreach($resultIds as $value)
		{
			if($lengthcounter == 0)
			{
				$lengthcounter = $value;
				continue;
			}
			$string .= $value . '.';
			$three++;
			if($three == $lengthcounter)
			{
				$output[] = substr($string, 0, -1);
				$three = 0;
				$lengthcounter = 0;
				$string = '';
			}
		}
		
		$id = explode('.', $output[0]);
		foreach($output as $okey => &$ovalue)
		{	
			if($okey != 0)
			{
				$out = array();
				$out = explode('.', $ovalue);
				if($stackType == 'colstacked')
				{
					array_splice($out, 1, 0, $id[1]);
				}
				else
				{
					array_splice($out, 0, 0, $id[0]);
					if(isset($out[4])) 
					{
						array_pop($out);
					}
				}
				$ovalue = implode('.', $out);
			}	
		}
		return $output;
	}
	
	function displayHeader($productAreaInfo)
	{
	
		echo '<form id="frmOtt" name="frmOtt" method="get" target="_self" action="intermediary.php">';
		
		if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') !== FALSE) 
		|| (isset($_GET['LI']) && $_GET['LI'] == 1))
		{
			echo '<input type="hidden" name="LI" value="1" />';
		}
		else
		{
			echo '<table width="100%">'
					. '<tr><td><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
					. '<td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface Work In Progress</span>'
					. '<br/><span style="font-weight:normal;">Send feedback to '
					. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
					. 'larvoltrials@larvol.com</a></span></td>'
					. '<td class="result">' . $productAreaInfo . '</td></tr></table>'
					. '<br clear="all"/><br/>';
		}
	}
	
	function displayFilterControls($shownCount, $activeCount, $inactiveCount, $totalCount, $globalOptions = array())
	{	
		echo '<table width="75%" border="0" cellspacing="0" class="controls">'
				. '<tr><td>Active</td><td>Status</td><td>Institution Type</td>'
				. '<td>Region</td><td>Phase</td><td class="right">Ranges</td></tr>'
				. '<tr><td class="bottom">'
      			. '<input type="radio" name="list" value="1"  id="active_1" '
				. (($globalOptions['type'] == 'activeTrials') ? ' checked="checked" ' : '')
				. ' onchange="javascript: showValues(\'active\');" '
				. '/><label for="active_1">' . $activeCount . ' Active</label><br />'
      			. '<input type="radio" name="list" value="0" id="active_0" '
				. (($globalOptions['type'] == 'inactiveTrials') ? ' checked="checked" ' : '')
				. ' onchange="javascript: showValues(\'inactive\');" '
				. '/><label for="active_0">' . ($totalCount - $activeCount) . ' Inactive</label><br />'
      			. '<input type="radio" name="list" value="2" id="active_2" '
				. (($globalOptions['type'] == 'allTrials') ? ' checked="checked" ' : '')
				. ' onchange="javascript: showValues(\'all\');" '
				. '/><label for="active_2">' . $totalCount . ' All</label><br />' 
				. '</td><td class="bottom"><div class="checkscroll" id="statuscontainer">';
		if($globalOptions['type'] == "inactiveTrials")
		{
			echo '<input type="checkbox" class="status" value="6" ' 
				. (in_array('6', $globalOptions['status']) ? 'checked = "checked" ' : '') . ' />Withheld<br/>'
				. '<input type="checkbox" class="status" value="7" '
				. (in_array('7', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Approved for marketing<br/>'
				. '<input type="checkbox" class="status" value="8" '
				. (in_array('8', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Temporarily not available<br/>'
				. '<input type="checkbox" class="status" value="9" '
				. (in_array('9', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>No Longer Available<br/>'
				. '<input type="checkbox" class="status" value="10" '
				. (in_array('10', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Withdrawn<br/>'
				. '<input type="checkbox" class="status" value="11" '
				. (in_array('11', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Terminated<br/>'
				. '<input type="checkbox" class="status" value="12" '
				. (in_array('12', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Suspended<br/>'
				. '<input type="checkbox" class="status" value="13" '
				. (in_array('13', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Completed<br/>';
		}
		elseif($globalOptions['type'] == "allTrials")
		{
			echo '<input type="checkbox" class="status" value="0" ' 
				 . (in_array('nyr', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Not yet recruiting<br/>'
				 . '<input type="checkbox" class="status" value="1" ' 
				 . (in_array('1', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Recruiting<br/>'
				 . '<input type="checkbox" class="status" value="2" ' 
				 . (in_array('2', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Enrolling by invitation<br/>'
				 . '<input type="checkbox" class="status" value="3" ' 
				 . (in_array('3', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Active, not recruiting<br/>'
				 . '<input type="checkbox" class="status" value="4" ' 
				 . (in_array('4', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Available<br/>'
				 . '<input type="checkbox" class="status" value="5" ' 
				 . (in_array('5', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>No longer recruiting<br/>'
				 . '<input type="checkbox" class="status" value="6" ' 
				 . (in_array('6', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Withheld<br/>'
				 . '<input type="checkbox" class="status" value="7" ' 
				 . (in_array('7', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Approved for marketing<br/>'
				 . '<input type="checkbox" class="status" value="8" ' 
				 . (in_array('8', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Temporarily not available<br/>'
				 . '<input type="checkbox" class="status" value="9" ' 
				 . (in_array('9', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>No Longer Available<br/>'
				 . '<input type="checkbox" class="status" value="10" ' 
				 . (in_array('10', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Withdrawn<br/>'
				 . '<input type="checkbox" class="status" value="11" ' 
				 . (in_array('11', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Terminated<br/>'
				 . '<input type="checkbox" class="status" value="12" ' 
				 . (in_array('12', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Suspended<br/>'
				 . '<input type="checkbox" class="status" value="13" ' 
				 . (in_array('13', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Completed<br/>';
		}
		else
		{
			echo '<input type="checkbox" class="status" value="0" '
				. (in_array('nyr', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Not yet recruiting<br/>'
				. '<input type="checkbox" class="status" value="1" '
				. (in_array('1', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Recruiting<br/>'
				. '<input type="checkbox" class="status" value="2" '
				. (in_array('2', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Enrolling by invitation<br/>'
				. '<input type="checkbox" class="status" value="3" '
				. (in_array('3', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Active, not recruiting<br/>'
				. '<input type="checkbox" class="status" value="4" '
				. (in_array('4', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Available<br/>'
				. '<input type="checkbox" class="status" value="5" '
				. (in_array('5', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>No longer recruiting<br/>';
		}
		echo  '</div></td><td class="bottom">'
				. '<input type="checkbox" value="0" id="institution_type_0" class="institution" '
				. (in_array(0, $globalOptions['itype']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="institution_type_0">Industry lead sponsor</label><br />'
				. '<input type="checkbox" value="1" id="institution_type_1" class="institution" '
				. (in_array(1, $globalOptions['itype']) ? ' checked="checked" ' : '') . '/>'
      			. '<label for="institution_type_1">Industry collaborator</label><br />'
				. '<input type="checkbox" value="2" id="institution_type_2" class="institution" '
				. (in_array(2, $globalOptions['itype']) ? ' checked="checked" ' : '') . '/>'
      			. '<label for="institution_type_2">Co-op</label><br />'
				. '<input type="checkbox" value="3" id="institution_type_3" class="institution" '
				. (in_array(3, $globalOptions['itype']) ? ' checked="checked" ' : '') . '/>'
      			. '<label for="institution_type_3">Other</label>'
				. '</td><td class="bottom">'
				. '<input type="checkbox" value="0" id="region_0" class="region" '
				. (in_array(0, $globalOptions['region']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="region_0">US</label><br />'
				. '<input type="checkbox" value="1" id="region_1" class="region" '
				. (in_array(1, $globalOptions['region']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="region_1">Canada</label><br />'
				. '<input type="checkbox" value="2" id="region_2" class="region" '
				. (in_array(2, $globalOptions['region']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="region_2">Japan</label><br />'
				. '<input type="checkbox" value="3" id="region_3" class="region" '
				. (in_array(3, $globalOptions['region']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="region_3">Europe</label><br />'
				. '<input type="checkbox" value="4" id="region_4" class="region" '
				. (in_array(4, $globalOptions['region']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="region_4">RestOfWorld</label>'
				. '</td><td class="bottom">'
				. '<input type="checkbox" value="0" id="phase_0" class="phase" '
				. (in_array(0, $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_0">0</label><br />'
				. '<input type="checkbox" value="1" id="phase_1" class="phase" '
				. (in_array(1, $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_1">1</label><br />'
				. '<input type="checkbox" value="2" id="phase_2" class="phase" '
				. (in_array(2, $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_2">2</label><br />'
				. '<input type="checkbox" value="3" id="phase_3" class="phase" '
				. (in_array(3, $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_3">3</label><br />'
				. '<input type="checkbox" value="4" id="phase_4" class="phase" '
				. (in_array(4, $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_4">4</label>'
				. '</td><td class="right bottom">'
				. '<div class="demo"><p>'
				. '<label for="amount">Enrollment:</label>'
				. '<input type="text" name="enroll" id="amount" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold;" '
				. ' value="' . ((isset($globalOptions['enroll'])) ? $globalOptions['enroll'] : '' ) . '" />'
				. '<div id="slider-range"></div>'
				. '</p></div><div class="demo"><p>'
				. '<label for="amount3">Highlight changes:</label>'
				. '<input type="text" id="amount3" value="' . (($globalOptions['change'] == '3 months') ? '1 quarter' : $globalOptions['change']) 
				. '" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold;" />'
				. '<div id="slider-range-min"></div></p></div>'
				. '<input type="checkbox" id="showonlyupdated" name="osu" ' 
				. ($globalOptions['onlyUpdates'] == 'yes' ? ' checked="checked" ' : '' ) . ' style="margin-left:20px;" />'
				. '<label for="showonlyupdated" style="font-size:x-small;">Show only updated</label>'
				. '</tr></table><br/><br/>';
		echo '<input type="hidden" name="status" id="status" value="' . implode(',', $globalOptions['status']) . '" />'
				. '<input type="hidden" name="itype" id="itype" value="' . implode(',', $globalOptions['itype']) . '" />'
				. '<input type="hidden" name="region" id="region" value="' . implode(',', $globalOptions['region']) . '" />'
				. '<input type="hidden" name="phase" id="phase" value="' . implode(',', $globalOptions['phase']) . '" />'
				. '<input type="hidden" id="change" name="change" value="' . $globalOptions['change'] . '" />';
				
		echo '<input type="submit" id="Show" value="Show"/>&nbsp;<b>' . $shownCount . '&nbsp;Records</b><span id="addtoright"></span>';	
		echo '<br/><br clear="all" />';
	}
	
	function pagination($globalOptions = array(), $totalPages, $timeMachine = NULL, $ottType)
	{ 	
		$url = 'intermediary.php?';
		
		if($ottType == 'unstacked')
		{
			$url .= 'results=' . $globalOptions['url'];
		}
		else if($ottType == 'rowstacked' || $ottType == 'colstacked')

		{	
			$url .= 'results=' .  $globalOptions['url'];
		}
		else if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
		{
			$url .= $globalOptions['url'];
		}
		else if($ottType == 'standalone')
		{
			$url .= 'id=' . $globalOptions['url'];
		}
		
		if($timeMachine !== NULL)
		{
			$url .= '&amp;time=' . $timeMachine;
		}
		if($globalOptions['version'] != 0)
		{
			$url .= '&amp;v=' . $globalOptions['version'];
		}
		if(isset($globalOptions['type']) && $globalOptions['type'] != 'activeTrials')
		{	
			if($globalOptions['type'] == 'inactiveTrials')
			{	
				$url .= "&amp;list=0";
			}
			else
			{	
				$url .= '&amp;list=2';
			}
		}
		if(isset($globalOptions['change']))
		{
			$url .= '&amp;change=' . $globalOptions['change'];
		}
		if(isset($globalOptions['onlyUpdates']) && $globalOptions['onlyUpdates'] == 'yes')
		{
			$url .= '&amp;osu=' . 'on';
		}
		
		if(isset($globalOptions['status']) && !empty($globalOptions['status'])) 
		{
			$url .= '&amp;status=' . implode(',',$globalOptions['status']);
		}
		if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
		{
			$url .= '&amp;itype=' . implode(',',$globalOptions['itype']);
		}
		if(isset($globalOptions['region']) && !empty($globalOptions['region'])) 
		{
			$url .= '&amp;region=' . implode(',',$globalOptions['region']);
		}
		if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
		{
			$url .= '&amp;phase=' . implode(',',$globalOptions['phase']);
		}
		if(isset($globalOptions['enroll']) && $globalOptions['enroll'] != '') 
		{
			$url .= '&amp;enroll=' . $globalOptions['enroll'];
		}
		
		if(isset($globalOptions['countDetails']) && !empty($globalOptions['countDetails']))
		{
			$url .= '&amp;cd=' . rawurlencode(base64_encode(gzdeflate(serialize($globalOptions['countDetails']))));
		}
		if(isset($globalOptions['encodeFormat']) && $globalOptions['encodeFormat'] != 'old')
		{
			$url .= '&amp;format=' . $globalOptions['encodeFormat'];
		}
		
		if(isset($globalOptions['LI']) && $globalOptions['LI'] == '1')
		{
			$url .= '&amp;LI=1';
		}
		
		$stages = 3;
		
		$paginateStr = '<div class="pagination">';
		
		if($globalOptions['page'] != 1)
		{
			$paginateStr .= '<a href="' . $url . '&page=' . ($globalOptions['page']-1) . '">&laquo; Prev</a>';
		}
		
		if($totalPages < 7 + ($stages * 2))
		{	
			for($counter = 1; $counter <= $totalPages; $counter++)
			{
				if ($counter == $globalOptions['page'])
				{
					$paginateStr .= '<span>' . $counter . '</span>';
				}
				else
				{
					$paginateStr .= '<a href="' . $url . '&page=' . $counter . '">' . $counter . '</a>';
				}
			}
		}
		elseif($totalPages > 5 + ($stages * 2))
		{
			if($globalOptions['page'] < 1 + ($stages * 2))
			{
				for($counter = 1; $counter < 4 + ($stages * 2); $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr .= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr .='<a href="' . $url . '&page=' . $counter . '">' . $counter . '</a>';
					}
				}
				$paginateStr.= '<span>...</span>';
				$paginateStr.= '<a href="' . $url . '&page=' . ($totalPages-1) . '">' .  ($totalPages-1) . '</a>';
				$paginateStr.= '<a href="' . $url . '&page=' . $totalPages . '">' . $totalPages . '</a>';
			}
			elseif($totalPages - ($stages * 2) > $globalOptions['page'] && $globalOptions['page'] > ($stages * 2))
			{
				$paginateStr.= '<a href="' . $url . '&page=1">1</a>';
				$paginateStr.= '<a href="' . $url . '&page=2">2</a>';
				$paginateStr.= '<span>...</span>';
				for($counter = $globalOptions['page'] - $stages; $counter <= $globalOptions['page'] + $stages; $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr.= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr.= '<a href="' . $url . '&page=' . $counter . '">' . $counter . '</a>';
					}
				}
				$paginateStr.= '<span>...</span>';
				$paginateStr.= '<a href="' . $url . '&page=' . ($totalPages-1) . '">' . ($totalPages-1) . '</a>';
				$paginateStr.= '<a href="' . $url . '&page=' . $totalPages . '">' . $totalPages . '</a>';
			}
			else
			{
				$paginateStr .= '<a href="' . $url . '&page=1">1</a>';
				$paginateStr .= '<a href="' . $url . '&page=2">2</a>';
				$paginateStr .= "<span>...</span>";
				for($counter = $totalPages - (2 + ($stages * 2)); $counter <= $totalPages; $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr .= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr .= '<a href="' . $url . '&page=' . $counter . '">' . $counter . '</a>';
					}
				}
			}
		}
		
		if($globalOptions['page'] != $totalPages)
		{
			$paginateStr .= '<a href="' . $url . '&page=' . ($globalOptions['page']+1) . '">Next &raquo;</a>';
		}
		$paginateStr .= '</div>';
		
		echo $paginateStr;
	}
	
	function displayTrials($globalOptions = array(), $loggedIn, $start, $end, $trials, $trialsInfo, $ottType)
	{	
		$currentYear = date('Y');
		$secondYear = (date('Y')+1);
		$thirdYear = (date('Y')+2);
		
		$section = '-1';
		$outputStr = '';
		$start = $start - 1;
		$sections = array();
		
		$sections = array_map(function($a) { 
		  return $a['section']; 
		},  $trials);
		$sections = array_unique($sections);
		
		for($i=$start; $i<$end; $i++) 
		{ 	
			if($i%2 == 1)  
				$rowOneType = 'alttitle';
			else
				$rowOneType = 'title';
			
			$rowspan = 1;
			$enrollStyle = 'color:gray;';
			$titleLinkColor = '#000000;';
			$sectionKey = $trials[$i]['section'];
			
			if(isset($trials[$i]['matchedupms']))  
				$rowspan = count($trials[$i]['matchedupms'])+1; 
			 
			if($section !== $sectionKey)
			{
				if($section != '-1')
				{
					$diff = $sectionKey - $section;
					if(($diff >= 2) && $section != -1)
					{	
						$counter = $section+1;
						for($j = $counter; $j < $sectionKey; $j++)
						{	
							if(!empty($trialsInfo[$j]['naUpms']))
							{
								$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $trialsInfo[$j]['sectionHeader']);
								$naUpmIndex = substr($naUpmIndex, 0, 7);
								
								$outputStr .= '<tr class="trialtitles">'
											. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
											. ' style="background: url(\'images/up.png\') no-repeat left center;"'
											. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
											. $trialsInfo[$j]['sectionHeader'] . '</td></tr>';
								$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $trialsInfo[$j]['naUpms']);
							}
							else
							{	
								$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">'
											. $trialsInfo[$j]['sectionHeader'] . '</td></tr>';
							}
							if($globalOptions['onlyUpdates'] == "no")
							{
								$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord" align="left">No trials found</td></tr>';
							}
						}
					}
				}
				if(!empty($trialsInfo[$sectionKey]['naUpms']))
				{
					if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
					{
						$outputStr .= '<tr class="trialtitles">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. 'style="background: url(\'images/down.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
									. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $trialsInfo[$sectionKey]['naUpms'])
									. '<tr class="trialtitles">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
									. $trialsInfo[$sectionKey]['sectionHeader'] . '</td></tr>';
					}
					else
					{
						if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
							$image = 'up';
						else
							$image = 'down';
						
						$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $trialsInfo[$sectionKey]['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 7);
						
						$outputStr .= '<tr class="trialtitles">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
									. $trialsInfo[$sectionKey]['sectionHeader'] . '</td></tr>';
						$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $trialsInfo[$sectionKey]['naUpms']);
					}
				}
				else
				{	
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">'
								. $trialsInfo[$sectionKey]['sectionHeader'] . '</td></tr>';
				}
			}
			
			//row starts  
			$outputStr .= '<tr ' . (($trials[$i]['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
			
			//nctid column
			if($loggedIn) 
			{ 
				$outputStr .= '<td class="' . $rowOneType . '" ' . (($trials[$i]['new'] == 'y') ? 'title="New record"' : '')
							. ' ><a style="color:' . $titleLinkColor 
							. '" href="' . urlPath() . 'edit_trials.php?larvol_id=' . $trials[$i]['larvol_id'] . '" target="_blank">' 
							. $trials[$i]['NCT/nct_id'] . '</a></td>';
			}
			
			//acroynm and title column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/brief_title', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/brief_title'];
				$titleLinkColor = '#FF0000;';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
				$titleLinkColor = '#FF0000;';
			}				
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse"><a style="color:' 
						. $titleLinkColor . '"  href="http://clinicaltrials.gov/ct2/show/' . padnct($trials[$i]['NCT/nct_id']) . '" target="_blank" ';
			
			if(($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed'))
			{
				$outputStr .= ' onclick="INC_ViewCount('.$trials[$i]['larvol_id'].')">'
							.'<font id="ViewCount_'.$trials[$i]['larvol_id'].'">';
				if($trials[$i]['viewcount'] != '' && $trials[$i]['viewcount'] != NULL && $trials[$i]['viewcount'] > 0)
					$outputStr .= '<font size="1px" style="background-color:#CCCCCC">'.$trials[$i]['viewcount'].'&nbsp;</font>'; 
				$outputStr .= '</font>'; 
			}
			else
				$outputStr .= '>'; 
						
			if(isset($trials[$i]['NCT/acronym']) && $trials[$i]['NCT/acronym'] != '') 
			{
				$outputStr .= '<b>' . htmlformat($trials[$i]['NCT/acronym']) . '</b>&nbsp;' . htmlformat($trials[$i]['NCT/brief_title']);
			} 
			else 
			{
				$outputStr .= htmlformat($trials[$i]['NCT/brief_title']);
			}
			$outputStr .= '</a></div></td>';
			
				
			//enrollment column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/enrollment',$trials[$i]['edited']) 
				&& (getDifference(substr($trials[$i]['edited']['NCT/enrollment'],16), $trials[$i]['NCT/enrollment']))) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/enrollment'];
				$enrollStyle = 'color:#973535;';
			}
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
				$enrollStyle = 'color:#973535;';
			}
			$outputStr .= '<td nowrap="nowrap" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">';
			if($trials[$i]["NCT/enrollment_type"] != '') 
			{
				if($trials[$i]["NCT/enrollment_type"] == 'Anticipated') 
				{ 
					$outputStr .= '<span style="font-weight:bold;' . $enrollStyle . '">' . $trials[$i]["NCT/enrollment"] . '</span>';
				}
				else if($trials[$i]["NCT/enrollment_type"] == 'Actual') 
				{
					$outputStr .= $trials[$i]["NCT/enrollment"];
				} 
				else 
				{ 
					$outputStr .= $trials[$i]["NCT/enrollment"] . ' (' . $trials[$i]["NCT/enrollment_type"] . ')';
				}
			} 
			else 
			{
				$outputStr .= $trials[$i]["NCT/enrollment"];
			}
			$outputStr .= '</div></td>';				


			//region column
			$attr = ' ';
			if($trials[$i]['new'] == 'y')
			{ 
				$attr = 'title="New record"';
			}
			$outputStr .= '<td class="' . $rowOneType . '" rowspan="' . $rowspan . '" ' . $attr . '>' . '<div class="rowcollapse">' 
						. (($trials[$i]['region'] != '' && $trials[$i]['region'] !== NULL) ? $trials[$i]['region'] : '&nbsp;') . '</div></td>';

				
			//intervention name column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/intervention_name', $trials[$i]['edited']))
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/intervention_name'];
			} 
			else if($trials[$i]['new'] == 'y')
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<div class="rowcollapse">' . $trials[$i]['NCT/intervention_name'] . '</div></td>';


			//collaborator and sponsor column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && (array_key_exists('NCT/collaborator', $trials[$i]['edited']) 
			|| array_key_exists('NCT/lead_sponsor', $trials[$i]['edited']))) 
			{
					
				$attr = ' highlight" title="';
				if(array_key_exists('NCT/lead_sponsor', $trials[$i]['edited']))
				{
					$attr .= $trials[$i]['edited']['NCT/lead_sponsor'] . ' ';
				}
				if(array_key_exists('NCT/collaborator', $trials[$i]['edited'])) 
				{
					$attr .= $trials[$i]['edited']['NCT/collaborator'];
					$enrollStyle = 'color:#973535;';
				}
				$attr .= '';
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<div class="rowcollapse">' . $trials[$i]['NCT/lead_sponsor'] . ' <span style="' . $enrollStyle . '"> ' 
						. $trials[$i]["NCT/collaborator"] . ' </span></div></td>';


			//overall status column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/overall_status', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/overall_status'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record' ;
			} 
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' . '<div class="rowcollapse">' 
							. (($trials[$i]['NCT/overall_status'] != '' && $trials[$i]['NCT/overall_status'] !== NULL) ? $trials[$i]['NCT/overall_status'] : '&nbsp;')
							. '</div></td>';
				
				
			//condition column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/condition', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/condition'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<div class="rowcollapse">' . $trials[$i]['NCT/condition'] . '</div></td>';
					
				
			//start date column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/start_date', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/start_date'] ;
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">'; 
			if($trials[$i]["NCT/start_date"] != '' && $trials[$i]["NCT/start_date"] != NULL && $trials[$i]["NCT/start_date"] != '0000-00-00') 
			{
				$outputStr .= date('m/y',strtotime($trials[$i]["NCT/start_date"]));
			} 
			else 
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</div></td>';
				
				
			//end date column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('inactive_date', $trials[$i]['edited'])) 
			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['inactive_date'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}	
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">'; 
			if($trials[$i]["inactive_date"] != '' && $trials[$i]["inactive_date"] != NULL && $trials[$i]["inactive_date"] != '0000-00-00') 
			{
				$outputStr .= date('m/y',strtotime($trials[$i]["inactive_date"]));
			} 
			else 
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</div></td>';
					
											
			//phase column
			$attr = ' ';
			if(!empty($trials[$i]['edited']) && array_key_exists('NCT/phase', $trials[$i]['edited'])) 

			{
				$attr = ' highlight" title="' . $trials[$i]['edited']['NCT/phase'];
			} 
			else if($trials[$i]['new'] == 'y') 
			{
				$attr = '" title="New record';
			}
			if($trials[$i]['NCT/phase'] == 'N/A' || $trials[$i]['NCT/phase'] == '' || $trials[$i]['NCT/phase'] === NULL)
			{
				$phase = 'N/A';
				$phaseColor = $this->phaseValues['N/A'];
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($trials[$i]['NCT/phase']));
				$phaseColor = $this->phaseValues[$phase];
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' 
						. '<div class="rowcollapse">' . $phase . '</div></td>';				
			
			$outputStr .= '<td>&nbsp;</td>';
				
			$startMonth = date('m',strtotime($trials[$i]['NCT/start_date']));
			$startYear = date('Y',strtotime($trials[$i]['NCT/start_date']));
			$endMonth = date('m',strtotime($trials[$i]['inactive_date']));
			$endYear = date('Y',strtotime($trials[$i]['inactive_date']));

			//rendering project completion gnatt chart
			$outputStr .= $this->trialGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, 
				$trials[$i]['NCT/start_date'], $trials[$i]['inactive_date'], $phaseColor);
				
			$outputStr .= '</tr>';
			
			//rendering matched upms
			if(isset($trials[$i]['matchedupms']) && !empty($trials[$i]['matchedupms'])) 
			{
				foreach($trials[$i]['matchedupms'] as $mkey => $mvalue) 
				{ 
					$str = '';
					$diamond = '';
					$resultImage = '';
	
					$stMonth = date('m', strtotime($mvalue['start_date']));
					$stYear = date('Y', strtotime($mvalue['start_date']));
					$edMonth = date('m', strtotime($mvalue['end_date']));
					$edYear = date('Y', strtotime($mvalue['end_date']));
					$upmTitle = 'title="' . htmlformat($mvalue['event_description']) . '"';
					
					$outputStr .= '<tr>';
					
					if($loggedIn) 
					{
						if($mvalue['new'] == 'y')
						{
							$idColor = '#973535';
						}
						else
						{
							$idColor = 'gray';
						}
						$outputStr .= '<td style="border-top:0px;" class="' . $rowOneType . '"><a style="color:' . $idColor 
						. '" href="' . urlPath() . 'upm.php?search_id=' . $mvalue['id'] . '" target="_blank">' . $mvalue['id'] . '</a></td>';
					}
					$outputStr .= '<td style="text-align:center;vertical-align:middle;' . (($mkey != 0) ? 'border-top:0px;' : '') . '">';
					
					$outputStr .= '<div ' . $upmTitle . '>';
					if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
					{
						if((!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'result_link') || ($mvalue['new'] == 'y')) 
							$imgColor = 'red';
						else 
							$imgColor = 'black'; 
							
						$outputStr .= '<a href="' . $mvalue['result_link'] . '" target="_blank">';
						if($mvalue['event_type'] == 'Clinical Data')
						{
							$outputStr .= '<img src="images/' . $imgColor . '-diamond.png" alt="Diamond" style="padding-top: 3px;" border="0" />';
						}
						else if($mvalue['status'] == 'Cancelled')
						{
							$outputStr .= '<img src="images/' . $imgColor . '-cancel.png" alt="Cancel" style="padding-top: 3px;" border="0" />';
						}
						else
						{
							$outputStr .= '<img src="images/' . $imgColor . '-checkmark.png" alt="Checkmark" style="padding-top: 3px;" border="0" />';
						}
						$outputStr .= '</a>';
					}
					else if($mvalue['status'] == 'Pending')
					{
						if($mvalue['event_link'] != '' && $mvalue['event_link'] !== NULL)
						{
							$outputStr .= '<a href="' . $mvalue['event_link'] . '" target="_blank">'
										. '<img src="images/hourglass.png" alt="Hourglass"  border="0" /></a>';
						}
						else
						{
							$outputStr .= '<img src="images/hourglass.png" alt="Hourglass"  border="0" />';
						}
					}
					else
					{
						$outputStr .= '&nbsp;';
					}
					$outputStr .= '</div></td>';
					
					//rendering upm (upcoming project completion) chart
					$outputStr .= $this->upmGnattChart($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'],
					$mvalue['end_date'], $mvalue['event_link'], $upmTitle);
					$outputStr .= '</tr>';
				}
			}
			
			//section title
			$section = $trials[$i]['section'];
		}
		
		if(!empty($sections))
		{
			$maxSection = max($sections);
			$maxTrialsInfo = max(array_keys($trialsInfo));
		}
		if($sectionKey == $maxSection && $maxTrialsInfo > $maxSection)
		{
			for($cntr = $maxSection+1; $cntr <= $maxTrialsInfo; $cntr++)
			{
				if(!empty($trialsInfo[$cntr]['naUpms']))
				{
					$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $trialsInfo[$cntr]['sectionHeader']);
					$naUpmIndex = substr($naUpmIndex, 0, 7);
					
					$outputStr .= '<tr class="trialtitles">'
								. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
								. ' style="background: url(\'images/up.png\') no-repeat left center;"'
								. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
								. $trialsInfo[$cntr]['sectionHeader'] . '</td></tr>';
					$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $trialsInfo[$cntr]['naUpms']);
				}
				else
				{	
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">'
								. $trialsInfo[$cntr]['sectionHeader'] . '</td></tr>';
				}
				if($globalOptions['onlyUpdates'] == "no")
				{
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
				}
			}
		}
		return $outputStr;
	}
		
	function trialGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, $bgColor)
	{
		$outputStr = '';
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')) 
		{
			$outputStr .= '<td colspan="3">&nbsp;</td>';
			$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';	
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="background-color:' . $bgColor . ';">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';	
			} 
			else if($endYear == $currentYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';	
			}
			else if($endYear == $secondYear)
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>' 
							. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>' 
							. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td colspan="3">&nbsp;</td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>'
							. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. '<td colspan="3" style="background-color:' . $bgColor . ';">&nbsp;</td>';
			}
		}
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')
		{
			$st = $startMonth-1;
			if($startYear < $currentYear)
			{
				$outputStr .= '<td colspan="3" style="background-color:' . $bgColor . ';">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';	
			}
			else if($startYear == $currentYear) 
			{ 
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';	
			} 
			else if($startYear == $secondYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>'
							. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
			}
			else if($startYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
							. '<td style="background-color:' . $bgColor . ';width:2px;">&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td colspan="3">&nbsp;</td>';	
			} 
			else if($startYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>'
							. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. '<td colspan="3" style="background-color:' . $bgColor . ';">&nbsp;</td>';
			}
		} 
		else if($endDate < $startDate) 
		{
			$outputStr .= '<td colspan="3">&nbsp;</td>';
			$outputStr .= '<td colspan="12">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
		} 
		else if($startYear < $currentYear) 
		{
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="background-color:' . $bgColor . ';">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $currentYear) 
			{
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="15">&nbsp;</td>' 
								. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
								. '<td colspan="3">&nbsp;</td>';
				} 
				else 
				{ 
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . ($endMonth+3) . '">&nbsp;</td>'
								. '<td style="width:'.(12-$endMonth).'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
								. '<td colspan="3">&nbsp;</td>';
				}
			}
			else if($endYear == $secondYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="27">&nbsp;</td>'
								. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . (15+$endMonth) . '">&nbsp;</td>'
								. '<td colspan="' . (12-$endMonth) . '">&nbsp;</td><td colspan="12">&nbsp;</td>'
								. '<td colspan="3">&nbsp;</td>';
				}
			} 
			else if($endYear == $thirdYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="39">&nbsp;</td>'
								. '<td colspan="3">&nbsp;</td>';
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . (27+$endMonth) . '">&nbsp;</td>'
								. '<td colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td colspan="3">&nbsp;</td>';
				}
			} 
			else if($endYear > $thirdYear)
			{ 
				$outputStr .= '<td colspan="42" style="background-color:' . $bgColor . ';">&nbsp;</td>';		
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $secondYear)
			{ 
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0)
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');			
				}
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';">&nbsp;</td>'

								. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="3">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(39 - $st) . '" style="background-color:' . $bgColor . ';">&nbsp;</td>';		
			}
		}
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">' . '&nbsp;</td>' : '');
				if($val != 0) 
				{ 
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{ 
					$outputStr .= '<td style="background-color:' . $bgColor . ';width:2px;"></td>'
								. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';		
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="3">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(27 - $st) . '" style="background-color:' . $bgColor . ';">&nbsp;</td>';		
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
							. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';" colspan="' . $val . '">&nbsp;</td>'
								. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="background-color:' . $bgColor . ';">&nbsp;</td>'
								. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="3">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3">&nbsp;</td>';
				$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(15 - $st) . '" style="background-color:' . $bgColor . ';">&nbsp;</td>';		
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$outputStr .= '<td colspan="3">&nbsp;</td>';
			$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" style="background-color:' . $bgColor . ';">&nbsp;</td>';	
		} 
		return $outputStr;
	}
	
	function upmGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, $upmLink, $upmTitle)
	{	
		$outputStr = '';
		$bgColor = 'background-color:#9966FF;';
		$anchorTag = ($upmLink != '' &&  $upmLink !== NULL) ? '<a href="' . $upmLink . '" target="_blank">&nbsp;</a>' : '&nbsp;' ;
		
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00'))
		{
			$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td colspan="12"><div ' . $upmTitle . '>'. $anchorTag . '</div></td>'
						. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($endYear == $currentYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			}
			else if($endYear == $secondYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="12">&nbsp;</td><td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
		} 
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00') 
		{
			$st = $startMonth-1;
			if($startYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($startYear == $currentYear) 
			{ 
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($startYear == $secondYear)
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($startYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
			} 
			else if($startYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
		} 
		else if($endDate < $startDate) 
		{
			$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			$outputStr .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="3">&nbsp;</td>';
		} 
		else if($startYear < $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
			else if($endYear == $currentYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . ($endMonth+3) . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				} 
				else 
				{ 
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . ($endMonth+3) . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td style="width:'.(12-$endMonth).'px;" colspan="' . (12-$endMonth) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear == $secondYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="27">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				} 
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . (15+$endMonth) . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="' . (12-$endMonth) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear == $thirdYear)
			{ 
				if($endMonth == 12)
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="39">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . (27+$end_month) . '" ' . $class . '>' 
								. '<div ' . $upm_title . '>' . $anchorTag . '</div></td>'
								. '<td colspan="' . (12-$endMonth) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td colspan="42" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '" ><div ' . $upm_title . '>' . $anchorTag . '</div></td>' : '');
				if($val != 0)
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . $val . '">'. '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			}
			else if($endYear == $secondYear) 
			{ 
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .' >' . $anchorTag . '</div></td>'
								. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '">' . '<div ' . $upmTitle .' >' . $anchorTag . '</div></td>'
								. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				if($val != 0) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .'>'. $anchorTag . '</div></td>'
								. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '') ;
				} 
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '') ;			
				}
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(39 - $st) . '" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			}
		} 
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
							
				if($val != 0) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '">' . '<div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
								. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				}
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
							
				if($val != 0) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .=  '<td style="' . $bgColor . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
	
			}
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				$outputStr .= '<td colspan="' .(27 - $st) . '" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';		
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upm_title . '>' . $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upm_title . '>' . $anchorTag . '</div></td>'
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
							
				if($val != 0) 
				{
					$outputStr .= '<td style="' . $bgColor . '" colspan="' . $val . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="' . $bgColor . '">' . '<div ' . $upmTitle .'>' . $anchorTag . '</div></td>'
								. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
				$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>'. $anchorTag . '</div></td>'
							. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' 
							. (($st != 0) ? '<td colspan="' . $st . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>' : '')
							. '<td colspan="' . (15 - $st) . '" style="' . $bgColor . '"><div ' . $upmTitle . '>'. $anchorTag . '</div></td>';
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$outputStr .= '<td colspan="3"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';
			$outputStr .= '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td colspan="12"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>'
						. '<td colspan="3" style="' . $bgColor . '"><div ' . $upmTitle . '>' . $anchorTag . '</div></td>';	
		}
		
		return $outputStr;	
	}
	
	function getDecodedValue($encodedValue)
	{
		return gzinflate(base64_decode($encodedValue));
	}
	
	function getTrialUpdates($nctId, $larvolId, $timeMachine = NULL, $timeInterval)
	{	
		global $now;
		
		if($timeMachine === NULL) $timeMachine = $now;
		$timeInterval = '-1 ' . $timeInterval;
		
		$updates = array('edited' => array(), 'new' => 'n');
		
		$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'acronym', 'start_date',
							'overall_status','condition', 'intervention_name', 'phase', 'lead_sponsor', 'collaborator');

		$studycatData = mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON "
				. "(`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='" . $nctId . "' AND `dc`.`larvol_id`='" .$larvolId . "'"));

		$res = mysql_query("SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat` "
				. "FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" 
				. join("','", $fieldnames) . "') AND `studycat` = '" . $studycatData['studycat'] 
				. "' AND (`dv`.`superceded`<'" . date('Y-m-d', $timeMachine) . "' AND `dv`.`superceded`>= '" 
				. date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "') ");
		
		while ($row = mysql_fetch_assoc($res)) 
		{
			//getting previous value for updated trials
			$result = mysql_fetch_assoc(mysql_query("SELECT `" . 'val_'.$row['fieldtype'] . "` AS value FROM `data_values` WHERE `studycat` = '" 
				. $studycatData['studycat'] . "' AND `field` =  '" . $row['fieldid'] . "' AND (`superceded`<'" . date('Y-m-d', $timeMachine) 
				. "' AND `superceded`>= '" . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "') "));
		
			$val = $result['value'];
			
			//special case for enum fields
			if($row['fieldtype'] == 'enum') 
			{
				$result = mysql_fetch_assoc(mysql_query("SELECT `value` FROM `data_enumvals` WHERE `field` = '" . $row['fieldid'] . "' AND `id` = '" . $val . "' "));
				$val 	= $result['value'];
			}
			
			if(isset($val) && $val != '')
				$updates['edited']['NCT/'.$row['fieldname']] = 'Previous value: ' . $val;
			else 
				$updates['edited']['NCT/'.$row['fieldname']] = 'No previous value';
		}
		
		$query = "SELECT inactive_date_prev FROM `clinical_study` WHERE larvol_id = '" . $larvolId . "' AND (inactive_date_lastchanged <'" 
			. date('Y-m-d',$timeMachine) . "' AND inactive_date_lastchanged >= '" . date('Y-m-d',strtotime($timeInterval,$timeMachine)) . "')";
		$res = mysql_query($query);
		
		if(mysql_num_rows($res) > 0)
		{	
			$row = mysql_fetch_assoc($res);
			if($row['inactive_date_prev'] !== NULL)
			{
				$updates['edited']['inactive_date'] = 'Previous value: ' . $row['inactive_date_prev'];
			}
			else
			{
				$updates['edited']['inactive_date'] = 'No previous value';
			}
		}
		
		$frd = getFieldId('NCT', 'firstreceived_date');

		$sql = "SELECT cs.larvol_id,dv.val_date 
		FROM clinical_study cs 
		LEFT JOIN data_cats_in_study dcis ON cs.larvol_id = dcis.larvol_id 
		LEFT JOIN data_values dv ON dcis.id = dv.studycat 
		WHERE dv.field='" . $frd . "' and dv.val_date <= '". date('Y-m-d',$timeMachine) . "' 
		AND cs.larvol_id = '" .  $larvolId . "' 
		AND dv.val_date >= '" . date('Y-m-d',strtotime($timeInterval,$timeMachine)) . "' ";
		$reslt = mysql_query($query);		
	
		if(mysql_num_rows($reslt) > 0) 
		{
			$updates['new'] = 'y';
		}
		return $updates;
	}
	
	 function getMatchedUPMs($trialId, $timeMachine = NULL, $timeInterval) 
	{
		global $now;
		$upm['matchedupms'] = array();
		$values = array();
		
		if($timeMachine === NULL) $timeMachine = $now;
		$timeInterval = '-1 ' . $timeInterval;
		
		$result = mysql_query("SELECT id, event_type, corresponding_trial, event_description, event_link, result_link, start_date, end_date, status "
								. "FROM upm WHERE corresponding_trial = '" . $trialId . "' ");
		
		$i = 0;			
		while($row = mysql_fetch_assoc($result)) 
		{
			$upm['matchedupms'][$i]['id'] = $row['id'];
			$upm['matchedupms'][$i]['event_description'] = htmlspecialchars($row['event_description']);
			$upm['matchedupms'][$i]['status'] = $row['status'];
			$upm['matchedupms'][$i]['event_link'] = $row['event_link'];
			$upm['matchedupms'][$i]['result_link'] = $row['result_link'];
			$upm['matchedupms'][$i]['event_type'] = $row['event_type'];
			$upm['matchedupms'][$i]['start_date'] = $row['start_date'];
			$upm['matchedupms'][$i]['start_date_type'] = $row['start_date_type'];
			$upm['matchedupms'][$i]['end_date'] 	= $row['end_date'];
			$upm['matchedupms'][$i]['end_date_type'] = $row['end_date_type'];
				
			//Query for checking updates for upms.
			$sql = "SELECT `id`, `field`, `old_value` FROM `upm_history` "
					. " WHERE `id` = '" . $row['id'] . "' AND (`change_date` < '" . date('Y-m-d', $timeMachine) . "' AND `change_date` >= '" 
					. date('Y-m-d', strtotime($timeInterval ,$timeMachine)) . "') ORDER BY `change_date` DESC LIMIT 0,1 ";
			$res = mysql_query($sql);
			
			$upm['matchedupms'][$i]['edited'] = array();
			$upm['matchedupms'][$i]['new'] = 'n';
			
			while($arr = mysql_fetch_assoc($res)) 
			{
				$upm['matchedupms'][$i]['edited']['id'] = $arr['id'];
				$upm['matchedupms'][$i]['edited']['field'] = $arr['field'];
				$upm['matchedupms'][$i]['edited'][$arr['field']] = $arr['old_value'];
			}
			
			$query = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $row['id'] . "' AND u.`last_update` < '" 
				. date('Y-m-d', $timeMachine) . "' AND u.`last_update` >=  '" . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "' AND uh.`id` IS NULL ";
		
			$ress = mysql_query($query);
			if(mysql_num_rows($ress) > 0)
			{
				$upm['matchedupms'][$i]['new'] = 'y';
			}
			$i++;
		}
		return $upm;	
	}

	function getUnMatchedUPMs($naUpmsRegex, $timeMachine = NULL, $timeInterval = NULL, $onlyUpdates, $productId = NULL)
	{	
		global $now;
		
		$where = array();
		$naUpms = array();
		$i = 0;
		
		if($timeMachine === NULL) $timeMachine = $now;
		$timeInterval = '-1 ' . $timeInterval;
		
		foreach($naUpmsRegex as $ukey => $uvalue)
		{
			$where[] = textEqual('`search_name`', $uvalue);
		}
		
		if(!empty($where))
		{
			$result = mysql_query("SELECT `id`, `name` FROM `products` WHERE ( " . implode(' OR ', $where) . " ) ");
			if(mysql_num_rows($result) > 0) 
			{
				while($rows = mysql_fetch_assoc($result)) 
				{
					$query = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`, `status`, " 
							. " `start_date_type`, `end_date`, `end_date_type` FROM `upm` WHERE `corresponding_trial` IS NULL AND `product` = '" . $rows['id'] 
							. "' ORDER BY `end_date` ASC ";
					$res = mysql_query($query)  or tex('Bad SQL query getting unmatched upms ' . $sql);
					if(mysql_num_rows($res) > 0) 
					{
						while($row = mysql_fetch_assoc($res)) 
						{ 
							$naUpms[$i]['id'] = $row['id'];
							$naUpms[$i]['product_name'] = $rows['name'];
							$naUpms[$i]['event_description'] = htmlspecialchars($row['event_description']);
							$naUpms[$i]['status'] = $row['status'];
							$naUpms[$i]['event_link'] = $row['event_link'];
							$naUpms[$i]['result_link'] = $row['result_link'];
							$naUpms[$i]['event_type'] = $row['event_type'];
							$naUpms[$i]['start_date'] = $row['start_date'];
							$naUpms[$i]['start_date_type'] = $row['start_date_type'];
							$naUpms[$i]['end_date'] 	= $row['end_date'];
							$naUpms[$i]['end_date_type'] = $row['end_date_type'];
							$naUpms[$i]['new'] = 'n';
							$naUpms[$i]['edited'] = array();
							
							$sql = "SELECT `id`, `field`, `old_value` FROM `upm_history` "
									. " WHERE `id` = '" . $row['id'] . "' AND (`change_date` < '" . date('Y-m-d', $timeMachine) 
									. "' AND `change_date` >= '" . date('Y-m-d',strtotime($timeInterval, $timeMachine)) . "') ORDER BY `change_date` DESC LIMIT 0,1 ";
							$ress = mysql_query($sql);
							
							if(mysql_num_rows($ress) > 0) 
							{
								while($roww = mysql_fetch_assoc($ress)) 
								{
									$naUpms[$i]['edited']['id'] = $roww['id'];
									$naUpms[$i]['edited']['field'] = $roww['field'];
									$naUpms[$i]['edited'][$roww['field']] = $roww['old_value'];
								}
							}
							
							$sql = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $value['id'] 
									. "' AND u.`last_update` < '" . date('Y-m-d', $timeMachine) . "' AND u.`last_update` >=  '" 
									. date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "' AND uh.`id` IS NULL ";
							$reslt = mysql_query($sql);
							if(mysql_num_rows($reslt) > 0)
							{
								$naUpms[$i]['new'] = 'y';
							}
							
							if($onlyUpdates == 'yes')
							{
								if(!empty($naUpms[$i]['edited']) && $naUpms[$i]['new'] == 'n') 
								{
									$fldName = $naUpms[$i]['edited']['field'];
									if($naUpms[$i][$fldName] == $naUpms[$i]['edited'][$fldName]) 
									{ 
										unset($naUpms[$i]);
									} 
								} 
								else if(empty($naUpms[$i]['edited']) && $naUpms[$i]['new'] == 'n') 
								{
									unset($naUpms[$i]);
								}
							}
							$i++;
						}
					}
				}
			}
		}
		else
		{
			$productName = mysql_fetch_assoc(mysql_query("SELECT `name` FROM `products` WHERE `id` = '" . $productId . "' "));
			$query = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`, `status`, " 
							. " `start_date_type`, `end_date`, `end_date_type` FROM `upm` WHERE `corresponding_trial` IS NULL AND `product` = '" . $productId 
							. "' ORDER BY `end_date` ASC ";
			$res = mysql_query($query)  or tex('Bad SQL query getting unmatched upms ' . $sql);
			if(mysql_num_rows($res) > 0) 
			{
				while($row = mysql_fetch_assoc($res)) 
				{ 
					$naUpms[$i]['id'] = $row['id'];
					$naUpms[$i]['product_name'] = $productName['name'];
					$naUpms[$i]['event_description'] = htmlspecialchars($row['event_description']);
					$naUpms[$i]['status'] = $row['status'];
					$naUpms[$i]['event_link'] = $row['event_link'];
					$naUpms[$i]['result_link'] = $row['result_link'];
					$naUpms[$i]['event_type'] = $row['event_type'];
					$naUpms[$i]['start_date'] = $row['start_date'];
					$naUpms[$i]['start_date_type'] = $row['start_date_type'];
					$naUpms[$i]['end_date'] 	= $row['end_date'];
					$naUpms[$i]['end_date_type'] = $row['end_date_type'];
					$naUpms[$i]['new'] = 'n';
					$naUpms[$i]['edited'] = array();
					
					$sql = "SELECT `id`, `field`, `old_value` FROM `upm_history` "
							. " WHERE `id` = '" . $row['id'] . "' AND (`change_date` < '" . date('Y-m-d', $timeMachine) 
							. "' AND `change_date` >= '" . date('Y-m-d',strtotime($timeInterval, $timeMachine)) . "') ORDER BY `change_date` DESC LIMIT 0,1 ";
					$ress = mysql_query($sql);
					
					if(mysql_num_rows($ress) > 0) 
					{
						while($roww = mysql_fetch_assoc($ress)) 
						{
							$naUpms[$i]['edited']['id'] = $roww['id'];
							$naUpms[$i]['edited']['field'] = $roww['field'];
							$naUpms[$i]['edited'][$roww['field']] = $roww['old_value'];
						}
					}
					
					$sql = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $value['id'] 
							. "' AND u.`last_update` < '" . date('Y-m-d', $timeMachine) . "' AND u.`last_update` >=  '" 
							. date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "' AND uh.`id` IS NULL ";
					$reslt = mysql_query($sql);
					if(mysql_num_rows($reslt) > 0)
					{
						$naUpms[$i]['new'] = 'y';
					}
					
					if($onlyUpdates == 'yes')
					{
						if(!empty($naUpms[$i]['edited']) && $naUpms[$i]['new'] == 'n') 
						{
							$fldName = $naUpms[$i]['edited']['field'];
							if($naUpms[$i][$fldName] == $naUpms[$i]['edited'][$fldName]) 
							{ 
								unset($naUpms[$i]);
							} 
						} 
						else if(empty($naUpms[$i]['edited']) && $naUpms[$i]['new'] == 'n') 
						{
							unset($naUpms[$i]);
						}
					}
					$i++;
				}
			}
		}
		
		return $naUpms;
	}
	
	function displayUnMatchedUpms($loggedIn, $naUpmIndex, $naUpms)
	{
		global $now;
		$outputStr = '';
		if(!empty($naUpms))
		{
			$currentYear = date('Y');
			$secondYear = (date('Y')+1);
			$thirdYear = (date('Y')+2);
			
			$cntr = 0;
			foreach($naUpms as $key => $value)
			{
				$attr = '';
				$resultImage = '';
				$class = 'class = "upms ' . $naUpmIndex . '" ';
				$titleLinkColor = '';
				$dateStyle = 'color:gray;';
				$upmTitle = 'title="' . htmlformat($value['event_description']) . '"';
				
				
				//Highlighting the whole row in case of new trials
				if($value['new'] == 'y') 
				{
					$class = 'class="upms newtrial ' . $upmHeader . '" ';
				}
				
				//rendering unmatched upms
				$outputStr .= '<tr ' . $class . '>';
				
				//field upm-id
				if($loggedIn)
				{
					if($value['new'] == 'y')
					{
						$titleLinkColor = 'style="color:#FF0000;"';
						$title = ' title = "New record" ';
					}
					$outputStr .= '<td ' . $title . '><a ' . $titleLinkColor . ' href="' . urlPath() . 'upm.php?search_id=' 
								. $value['id'] . '" target="_blank">' . $value['id'] . '</a></td>';
				}
				
				
				//field upm event description
				if(!empty($value['edited']) && ($value['edited']['field'] == 'event_description')) 
				{
					$titleLinkColor = 'style="color:#FF0000;"';
					$attr = ' highlight'; 
					
					if($value['edited']['event_description'] != '' && $value['edited']['event_description'] !== NULL)
					{
						$title = ' title="Previous value: '. $value['edited']['event_description'] . '" '; 
					}
					else
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if($value['new'] == 'y') 
				{
					$titleLinkColor = 'style="color:#FF0000;"';
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td colspan="5" class="' .  $attr . '" ' . $title . '><span>';
				if($value['event_link'] !== NULL && $value['event_link'] != '') 
				{
					$outputStr .= '<a ' . $titleLinkColor . ' href="' . $value['event_link'] . '" target="_blank">' . $value['event_description'] . '</a>';
				} 
				else 
				{
					$outputStr .= $value['event_description'];
				}
				$outputStr .= '</span></td>';
				
				
				//field upm status
				$title = '';
				if($value['new'] == 'y')
				{
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td ' . $title . '><span>' . $value['status'] . '</span></td>';

			
				//field upm event type
				$title = '';
				$attr = '';	
				if(!empty($value['edited']) && ($value['edited']['field'] == 'event_type')) 
				{
					$attr = ' highlight'; 
					if($value['edited']['event_type'] != '' && $value['edited']['event_type'] !== NULL)
					{
						$title = ' title="Previous value: '. $value['edited']['event_type'] . '" '; 
					}
					else
					{
						$title = ' title="No Previous value" ';
					}	
				} 
				else if($value['new'] == 'y') 
				{
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td class="' . $attr . '" ' . $title . '>' . '<span>' . $value['event_type'] . ' Milestone</span></td>';
				
				
				//field upm start date
				$title = '';
				$attr = '';	
				if(!empty($value['edited']) && ($value['edited']['field'] == 'start_date'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;'; 
					if($value['edited']['start_date'] != '' && $value['edited']['start_date'] !== NULL)
					{
						$title = ' title="Previous value: ' . $value['edited']['start_date'] . '" '; 
					} 
					else 
					{
						$title = ' title="No Previous value" ';
					}	
				} 
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'start_date_type'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;';
					if($value['edited']['start_date_type'] != '' && $value['edited']['start_date_type'] !== NULL) 
					{
						$title = ' title="Previous value: ' . $value['edited']['start_date_type'] . '" '; 
					} 
					else 
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if($value['new'] == 'y')
				{
					$title = ' title = "New record" ';
					$dateStyle = 'color:#973535;'; 
				}
				$outputStr .= '<td class="' . $attr . '" ' . $title . '><div class="rowcollapse">';
				if($value['start_date_type'] == 'anticipated') 
				{
					$outputStr .= '<span style="font-weight:bold;' . $dateStyle . '">'
					 			. (($value['start_date'] != '' && $value['start_date'] !== NULL && $value['start_date'] != '0000-00-00') ? 
								date('m/y',strtotime($value['start_date'])) : '&nbsp;' )  . '</span>';
				} 
				else 
				{
					$outputStr .= (($value['start_date'] != '' && $value['start_date'] !== NULL && $value['start_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['start_date'])) : '&nbsp;' );
				}
				$outputStr .= '</div></td>';		
				
				
				//field upm end date
				$title = '';
				$attr = '';	
				if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;';
					if($value['edited']['end_date'] != '' && $value['edited']['end_date'] !== NULL)
					{
						$title = ' title="Previous value: ' . $value['edited']['end_date'] . '" '; 
					}
					else 
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date_type'))
				{
					$attr = ' highlight';
					$dateStyle = 'color:#973535;'; 
					if($value['edited']['end_date_type'] != '' && $value['edited']['end_date_type'] !== NULL) 
					{
						$title = ' title="Previous value: ' .  $value['edited']['end_date_type'] . '" ';
					} 
					else 
					{
						$title = ' title="No Previous value" ';
					}
				} 
				else if($value['new'] == 'y') 
				{
					$title = ' title = "New record" ';
					$dateStyle = 'color:#973535;'; 
				}
				$outputStr .= '<td class="' . $attr . '" ' . $title . '><div class="rowcollapse">';
				if($value['end_date_type'] == 'anticipated') 
				{
					$outputStr .= '<span style="font-weight:bold;' . $dateStyle . '">' 
								. (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['end_date'])) : '&nbsp;' ) . '</span>';
				} 
				else 
				{
					$outputStr .= (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['end_date'])) : '&nbsp;');
				}	
				$outputStr .= '</div></td><td><div class="rowcollapse">&nbsp;</div></td>';
				
				
				//field upm result 
				$outputStr .= '<td style="text-align:center;vertical-align:middle;"><div class="rowcollapse">';
				if($value['result_link'] != '' && $value['result_link'] !== NULL)
				{
					if((!empty($value['edited']) && $value['edited']['field'] == 'result_link') || ($value['new'] == 'y')) 
							$imgColor = 'red';
					else 
						$imgColor = 'black'; 
						
					$outputStr .= '<div ' . $upmTitle . '><a href="' . $value['result_link'] . '" target="_blank">';
					if($value['event_type'] == 'Clinical Data')
					{
						$outputStr .= '<img src="images/' . $imgColor . '-diamond.png" alt="Diamond" border="0" />';
					}
					else if($value['status'] == 'Cancelled')
					{
						$outputStr .= '<img src="images/' . $imgColor . '-cancel.png" alt="Cancel" border="0" />';
					}
					else
					{
						$outputStr .= '<img src="images/' . $imgColor . '-checkmark.png" alt="Checkmark" border="0" />';
					}
					$outputStr .= '</a></div>';
				}
				else if($value['status'] == 'Pending')
				{
					$outputStr .= '<div ' . $upmTitle . '>';
					if($value['event_link'] != '' && $value['event_link'] !== NULL)
					{
						$outputStr .= '<a href="' . $value['event_link'] . '" target="_blank">'
									. '<img src="images/hourglass.png" alt="Hourglass"  border="0" /></a>';
					}
					else
					{
						$outputStr .= '<img src="images/hourglass.png" alt="Hourglass"  border="0" />';
					}
				}
				else
				{
					$outputStr .= '&nbsp;';
				}
				$outputStr .= '</div></td>';		
				
				
				//upm gnatt chart
				$outputStr .= $this->upmGnattChart(date('m',strtotime($value['start_date'])), date('Y',strtotime($value['start_date'])), 
								date('m',strtotime($value['end_date'])), date('Y',strtotime($value['end_date'])), $currentYear, $secondYear, $thirdYear, 
								$value['start_date'], $value['end_date'], $value['event_link'], $upmTitle);
				
				$outputStr .= '</tr>';
			}
		}
		return $outputStr;
	}
	
	
	function getInfo($tablename, $fieldnames, $id, $value)
	{
		$query = "SELECT " . implode(', ', $fieldnames) . " FROM " . $tablename . " WHERE " . $id . " = '" . $value . "' ";
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		
		return $row;
	}
	
}

function htmlformat($str)
{
	$str = fix_special_chars($str);
	return htmlspecialchars($str);
}

function getDifference($valueOne, $valueTwo) 
{
	if($valueOne == 0)
	{
		if($valueTwo > 20)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		$diff = abs(($valueOne - $valueTwo) / $valueOne * 100);
		$diff = round($diff);
		if($diff > 20)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

//get difference between two dates in months
function getColspan($startDate, $endDate) 
{
	$diff = round((strtotime($endDate)-strtotime($startDate))/2628000);
	return $diff;
}

function getColspanforExcelExport($cell, $inc)
{
	for($i = 1; $i < $inc; $i++)
	{
		$cell++;
	}
	return $cell;
}

function getColspanBasedOnLogin($loggedIn)
{
	return $colspan = (($loggedIn) ? 54 : 53 );
}