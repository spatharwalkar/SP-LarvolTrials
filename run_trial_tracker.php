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
require_once('include.util.php');
$li_user=null;

class TrialTracker
{
	private $inactiveStatusValues = array();
	private $activeStatusValues = array();
	private $allStatusValues = array();
	private $phaseValues = array();
	private $statusFilters = array();
	private $phaseFilters = array();
	private $institutionFilters = array();
	private $regionFilters = array();
	
	private $resultsPerPage = 100;
	private $timeMachine;
	private $timeInterval;
	private $fieldNames = array();
	
	function TrialTracker()
	{
		$this->inactiveStatusValues = array('Withheld', 'Approved for marketing', 'Temporarily not available', 'No Longer Available', 
									'Withdrawn', 'Terminated','Suspended', 'Completed');
									
		$this->activeStatusValues = array('Not yet recruiting', 'Recruiting', 'Enrolling by invitation', 
								'Active, not recruiting', 'Available', 'No longer recruiting');
		$this->allStatusValues = array_merge($this->activeStatusValues, $this->inactiveStatusValues);
		
		$this->phaseValues = array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', 
							'1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', 
							'2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900', 
							'3/4'=>'#FF0000', '3b/4'=>'#FF0000', '4'=>'#FF0000');
		
		$this->statusFilters = array('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Available',
									 'No longer recruiting','Withheld','Approved for marketing', 'Temporarily not available','No Longer Available',
									 'Withdrawn','Terminated', 'Suspended','Completed');
									 
		$this->phaseFilters = array('N/A'=>'na', '0'=>'0', '0/1'=>'1', '1'=>'1', '1a'=>'1', '1b'=>'1', '1a/1b'=>'1', '1c'=>'1', 
									'1/2'=>'2', '1b/2'=>'2', '1b/2a'=>'2', '2'=>'2', '2a'=>'2', '2a/2b'=>'2', '2a/b'=>'2', '2b'=>'2', 
									'2/3'=>'3', '2b/3'=>'3','3'=>'3', '3a'=>'3', '3b'=>'3', '3/4'=>'4', '3b/4'=>'4', '4'=>'4');
		
		$this->regionFilters = array('US','Canada','Japan','Europe','RestOfWorld');
		
		$this->institutionFilters = getEnumValues('clinical_study', 'institution_type');
				
		$this->fieldNames = array('end_date_lastchanged', 'region_lastchanged', 'brief_title_lastchanged', 'acronym_lastchanged', 'lead_sponsor_lastchanged',
							'overall_status_lastchanged', 'phase_lastchanged', 'enrollment_lastchanged', 'enrollment_type_lastchanged',
							'collaborator_lastchanged', 'condition_lastchanged', 'intervention_name_lastchanged', 'start_date_lastchanged');
	}
	
	function generateTrialTracker($format, $resultIds, $globalOptions = array())
	{	
		switch($format)
		{
			case 'excel':
				$this->generateExcelFile($resultIds, $globalOptions);
				break;
			case 'pdf':
				$this->generatePdfFile($resultIds, $globalOptions);
				break;
			case 'tsv':
				$this->generateTsvFile($resultIds, $globalOptions);
				break;
			case 'indexed':
				$this->generateOnlineTT($resultIds, $globalOptions);
				break;
			case 'entities':
				$this->generateEntitiesTT($resultIds, $globalOptions);
				break;
			default:
				$this->generateOnlineTT($resultIds, $globalOptions);
				break;
		}
	}
	
	function generateExcelFile($resultIds, $globalOptions)
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		
		$Values = array();
		
		$this->timeParams($globalOptions);

		$currentYear = date('Y');
		$secondYear	= date('Y')+1;
		$thirdYear	= date('Y')+2;	
		
		$SpaceIcon = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

		ob_start();
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->getStyle('B1:K2000')->getAlignment()->setWrapText(false);
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
		$manualChange =  array('font' => array('color' => array('rgb' => 'FF7700')));
		
		$objPHPExcel->getActiveSheet()->getStyle('A1:BB1')->applyFromArray($styleThinBlueBorderOutline);
		$objPHPExcel->getActiveSheet()->getStyle('A1:BB1')->getFont()->setSize(8);
			
		$objPHPExcel->getProperties()->setCreator("The Larvol Group")
										 ->setLastModifiedBy("TLG")
										 ->setTitle("Larvol Trials")
										 ->setSubject("Larvol Trials")
										 ->setDescription("Excel file generated by Larvol Trials")
										 ->setKeywords("Larvol Trials")
										 ->setCategory("Clinical Trials");

		$bgColor = "D5D3E6";
		
		$Ids = array();
		$TrialsInfo = array();
		
		if(isset($resultIds['e1']) && isset($resultIds['e2']))
		{
			$Arr = $this->processEntitiesParams($resultIds, $globalOptions);
			
			$pId = $Arr['pId'];
			$TrialsInfo = $Arr['TrialsInfo'];
		
			$Values = $this->compileEntitiesData($pId, $TrialsInfo, $resultIds, $globalOptions, 'excel');
			
			unset($pId, $TrialsInfo);
		}
		else 
		{
			if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '')
			{
				$Arr = $this->processHmParams($resultIds, $globalOptions);
			}
			else
			{
				$Arr = $this->processNonHmParams($resultIds, $globalOptions);
			}
			$ottType = $Arr['ottType'];
			$Ids = $Arr['Ids'];
			$TrialsInfo = $Arr['TrialsInfo'];
			
			if(isset($globalOptions['JSON_search']))
			{	
				$Values = $this->compileJsonData($globalOptions, 'excel');
			}
			else
			{
				$Values = $this->compileOTTData2($ottType, $TrialsInfo, $Ids, $globalOptions, 'excel');
			}
			
			unset($Ids, $productSelector, $TrialsInfo);
		}
		
		$i = 2;
		$naUpms = array();
		
		foreach($Values['Data'] as $tkey => $tvalue)
		{
			if(isset($tvalue['naUpms']))
			{
				$naUpms = array_merge($naUpms, $tvalue['naUpms']);
			}
			
			$tvalue['sectionHeader'] = strip_tags($tvalue['sectionHeader']);
			
			if($globalOptions['includeProductsWNoData'] == "off")
			{
				if(isset($tvalue['naUpms']) || isset($tvalue['Trials']))
				{
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $tvalue['sectionHeader']);
					$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
                                        $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->getFont()->setSize(8);
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
			}
			else
			{
				$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $tvalue['sectionHeader']);
				$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
                                $objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->getFont()->setSize(8);
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
                        
		if(isset($tvalue['Trials']) && !empty($tvalue['Trials']))
			{
				foreach($tvalue['Trials'] as $dkey => $dvalue)
				{
					$startMonth = date('m',strtotime($dvalue['start_date']));
					$startYear = date('Y',strtotime($dvalue['start_date']));
					$endMonth = date('m',strtotime($dvalue['end_date']));
					$endYear = date('Y',strtotime($dvalue['end_date']));
					
					$nctId = $dvalue["nct_id"];
					$nctIdText = padnct($nctId);
					
					if(isset($dvalue['manual_is_sourceless']))
					{
						$ctLink = $dvalue['source'];
					}
					else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') === FALSE)
					{	
						$nctIdText = unpadnct($nctId);
						$ctLink = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $nctId;
					}
					else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') !== FALSE)
					{
						$ctLink = 'http://clinicaltrials.gov/ct2/show/' . padnct($nctId);
					}
					else 
					{ 
						$ctLink = 'javascript:void(0)';
					}
					$ctLink = urlencode($ctLink);
                                        
					$cellSpan = $i;
					$rowspanLimit = 0;
					
					if(isset($dvalue['upms'])) 
					{
						$cellSpan = $i;
						$rowspanLimit = count($dvalue['upms']);
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
						$objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(15);
					}
					/////END PART - MERGE CELLS AND APPLY BORDER AS - FOR LOOP WAS NOT WORKING SET INDIVIDUALLY
					
					$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB' . $i)->applyFromArray($styleThinBlueBorderOutline);
					$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB' . $i)->getFont()->setSize(8);
					$objPHPExcel->getActiveSheet()->getStyle('A1:BA1')->applyFromArray($styleThinBlueBorderOutline);
					
					//nct id	
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $nctIdText);
					$objPHPExcel->getActiveSheet()->getCell('A' . $i)->getHyperlink()->setUrl($ctLink);
					if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('A' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('A' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					
					//brief title	
					$dvalue["brief_title"] = fix_special_chars($dvalue["brief_title"]);
					$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $dvalue["brief_title"]);
					$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setUrl($ctLink);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('brief_title', $dvalue['edited']))
						{
							$dvalue['edited']['brief_title'] = substr($dvalue['edited']['brief_title'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip($dvalue['edited']['brief_title']);
						}
						else if($dvalue['new'] == 'y')
						{
							$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange); 
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_brief_title'] != '' && $dvalue['manual_brief_title'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($manualChange); 
							if($dvalue['brief_title_prev'] == $dvalue['brief_title'])
							{
								$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Manual curation.');
							}
							elseif($dvalue['brief_title_prev'] != '' && $dvalue['brief_title_prev'] !== NULL)
							{
								$dvalue['brief_title_prev'] = 'Manual curation. Original value: ' . substr($dvalue['brief_title_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip($dvalue['brief_title_prev']);
							}
						}
						else
						{
							 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov'); 
						}
					}
					else
					{
						if($dvalue['manual_brief_title'] != '' && $dvalue['manual_brief_title'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($manualChange); 
							if($dvalue['brief_title_prev'] == $dvalue['brief_title'])
							{
								$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Manual curation.');
							}
							elseif($dvalue['brief_title_prev'] != '' && $dvalue['brief_title_prev'] !== NULL)
							{
								$dvalue['original_brief_title'] = 'Manual curation. Original value: ' . substr($dvalue['brief_title_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip($dvalue['brief_title_prev']);
							}
						}
						else if(!empty($dvalue['edited']) && array_key_exists('brief_title', $dvalue['edited']))
						{
							$dvalue['edited']['brief_title'] = substr($dvalue['edited']['brief_title'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip($dvalue['edited']['brief_title']);
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						else
						{
							 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov'); 
						}
					}
					$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
					
					
					//enrollment
					$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $dvalue["enrollment"]);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('enrollment', $dvalue['edited']))
						{
							$dvalue['edited']['enrollment'] = substr($dvalue['edited']['enrollment'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($dvalue['edited']['enrollment']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_enrollment'] != '' && $dvalue['manual_enrollment'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['enrollment_prev'] == $dvalue['enrollment'])
							{
								 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Manual curation.');
							}
							elseif($dvalue['enrollment_prev'] != '' && $dvalue['enrollment_prev'] !== NULL)
							{
								$dvalue['enrollment_prev'] = 'Manual curation. Original value: ' . substr($dvalue['enrollment_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($dvalue['enrollment_prev']);
							}
						}
					}
					else
					{
						if($dvalue['manual_enrollment'] != '' && $dvalue['manual_enrollment'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['enrollment_prev'] == $dvalue['enrollment'])
							{
								 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Manual curation.');
							}
							elseif($dvalue['enrollment_prev'] != '' && $dvalue['enrollment_prev'] !== NULL)
							{
								$dvalue['enrollment_prev'] = 'Manual curation. Original value: ' . substr($dvalue['enrollment_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($dvalue['enrollment_prev']);
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('enrollment', $dvalue['edited']))
						{
							$dvalue['edited']['enrollment'] = substr($dvalue['edited']['enrollment'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($dvalue['edited']['enrollment']); 
						}
						else if($dvalue['new'] == 'y')
						{
							$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					//region	
					$dvalue["region"] = fix_special_chars($dvalue["region"]);
					$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $dvalue["region"]);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if($dvalue['new'] == 'y')
						{
							$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
							$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_region'] != '' && $dvalue['manual_region'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
							if($dvalue['region_prev'] == $dvalue['region'])
							{
								$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['region_prev'] != '' && $dvalue['region_prev'] !== NULL)
							{
								$dvalue['region_prev'] = 'Manual curation. Original value: ' . substr($dvalue['region_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip($dvalue['region_prev']);
							}
						}
					}
					else
					{
						if($dvalue['manual_region'] != '' && $dvalue['manual_region'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
							if($dvalue['region_prev'] == $dvalue['region'])
							{
								$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['region_prev'] != '' && $dvalue['region_prev'] !== NULL)
							{
								$dvalue['region_prev'] = 'Manual curation. Original value: ' . substr($dvalue['region_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip($dvalue['region_prev']);
							}
							
						}
						elseif($dvalue['new'] == 'y')
						{
							$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
							$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					
					
					//status
					$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $dvalue["overall_status"]);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('overall_status', $dvalue['edited']))
						{
							$dvalue['edited']['overall_status'] = substr($dvalue['edited']['overall_status'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip($dvalue['edited']['overall_status']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_overall_status'] != '' && $dvalue['manual_overall_status'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['overall_status_prev'] == $dvalue['overall_status'])
							{	
								$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['overall_status_prev'] != '' && $dvalue['overall_status_prev'] !== NULL)
							{	
								$dvalue['overall_status_prev'] = 'Manual curation. Original value: ' . substr($dvalue['overall_status_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip($dvalue['overall_status_prev']); 
							}
						}
					}
					else
					{
						if($dvalue['manual_overall_status'] != '' && $dvalue['manual_overall_status'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['overall_status_prev'] == $dvalue['overall_status'])
							{
								$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['overall_status_prev'] != '' && $dvalue['overall_status_prev'] !== NULL)
							{
								$dvalue['overall_status_prev'] = 'Manual curation. Original value: ' . substr($dvalue['overall_status_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip($dvalue['overall_status_prev']); 
							}
						}
						else if(!empty($dvalue['edited']) && array_key_exists('overall_status', $dvalue['edited']))
						{
							$dvalue['edited']['overall_status'] = substr($dvalue['edited']['overall_status'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip($dvalue['edited']['overall_status']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					
					//collaborator and lead sponsor	
					$dvalue["lead_sponsor"] = fix_special_chars($dvalue["lead_sponsor"]);
					$dvalue["collaborator"] = fix_special_chars($dvalue["collaborator"]);
					if($dvalue['lead_sponsor'] != '' && $dvalue['collaborator'] != ''
					&& $dvalue['lead_sponsor'] != NULL && $dvalue['collaborator'] != NULL)
					{
						$dvalue["lead_sponsor"] .= ', ';
					}
							
					$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $dvalue["lead_sponsor"] . $dvalue["collaborator"]);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && (array_key_exists('lead_sponsor', $dvalue['edited']) || array_key_exists('collaborator', $dvalue['edited'])))
						{
							$value = '';
							if(array_key_exists('lead_sponsor', $dvalue['edited']))
							{
								$value .= $dvalue['edited']['lead_sponsor'];
							}
							if(array_key_exists('lead_sponsor', $dvalue['edited']) && array_key_exists('collaborator', $dvalue['edited']))
							{
								$value .=  ', ';
							}
							if(array_key_exists('collaborator', $dvalue['edited']))
							{
								$value .= $dvalue['edited']['collaborator'];
							}

							$value = substr($value, 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($value); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif(($dvalue['manual_lead_sponsor'] != '' && $dvalue['manual_lead_sponsor'] !== NULL) 
						|| ($dvalue['manual_collaborator'] != '' && $dvalue['manual_collaborator'] !== NULL))
						{
							$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
							
							if($dvalue['manual_lead_sponsor'] != '' && $dvalue['manual_lead_sponsor'] !== NULL)
							{
								if($dvalue['lead_sponsor_prev'] == $dvalue['lead_sponsor'])
								{
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
								}
								elseif($dvalue['lead_sponsor_prev'] != '' && $dvalue['lead_sponsor_prev'] !== NULL)
								{	
									$dvalue['lead_sponsor_prev'] = 'Manual curation. Original value: ' . substr($dvalue['lead_sponsor_prev'], 0, 210);
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($dvalue['lead_sponsor_prev']); 
								}
							}
							else
							{
								if($dvalue['collaborator_prev'] == $dvalue['collaborator'])
								{
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
								}
								elseif($dvalue['collaborator_prev'] != '' && $dvalue['collaborator_prev'] !== NULL)
								{	
									$dvalue['collaborator_prev'] = 'Manual curation. Original value: ' . substr($dvalue['collaborator_prev'], 0, 210);
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($dvalue['collaborator_prev']); 
								}
							}
						}
					}
					else
					{
						if(($dvalue['manual_lead_sponsor'] != '' && $dvalue['manual_lead_sponsor'] !== NULL) 
						|| ($dvalue['manual_collaborator'] != '' && $dvalue['manual_collaborator'] !== NULL))
						{
							$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
							
							if($dvalue['manual_lead_sponsor'] != '' && $dvalue['manual_lead_sponsor'] !== NULL)
							{
								if($dvalue['lead_sponsor_prev'] == $dvalue['lead_sponsor'])
								{
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
								}
								elseif($dvalue['lead_sponsor_prev'] != '' && $dvalue['lead_sponsor_prev'] !== NULL)
								{	
									$dvalue['lead_sponsor_prev'] = 'Manual curation. Original value: ' . substr($dvalue['lead_sponsor_prev'], 0, 210);
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($dvalue['lead_sponsor_prev']); 
								}
							}
							else
							{
								if($dvalue['collaborator_prev'] == $dvalue['collaborator'])
								{
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
								}
								elseif($dvalue['collaborator_prev'] != '' && $dvalue['collaborator_prev'] !== NULL)
								{	
									$dvalue['collaborator_prev'] = 'Manual curation. Original value: ' . substr($dvalue['collaborator_prev'], 0, 210);
									$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($dvalue['collaborator_prev']); 
								}
							}
						}
						elseif(!empty($dvalue['edited']) && (array_key_exists('lead_sponsor', $dvalue['edited']) || array_key_exists('collaborator', $dvalue['edited'])))
						{
							$value = '';
							if(array_key_exists('lead_sponsor', $dvalue['edited']))
							{
								$value .= $dvalue['edited']['lead_sponsor'];
							}
							if(array_key_exists('lead_sponsor', $dvalue['edited']) && array_key_exists('collaborator', $dvalue['edited']))
							{
								$value .=  ', ';
							}
							if(array_key_exists('collaborator', $dvalue['edited']))
							{
								$value .= $dvalue['edited']['collaborator'];
							}
							$value = substr($value, 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($value); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					
					//condition
					$dvalue["condition"] = fix_special_chars($dvalue["condition"]);
					$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $dvalue["condition"]);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('condition', $dvalue['edited']))
						{
							$dvalue['edited']['condition'] = substr($dvalue['edited']['condition'], 0, 250);
							$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($dvalue['edited']['condition']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_condition'] != '' && $dvalue['manual_condition'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['condition_prev'] == $dvalue['condition'])
							{	
								$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['condition_prev'] != '' && $dvalue['condition_prev'] !== NULL)
							{	
								$dvalue['condition_prev'] = 'Manual curation. Original value: ' . substr($dvalue['condition_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($dvalue['condition_prev']); 
							}
						}
					}
					else
					{
						if($dvalue['manual_condition'] != '' && $dvalue['manual_condition'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['condition_prev'] == $dvalue['condition'])
							{	
								$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['condition_prev'] != '' && $dvalue['condition_prev'] !== NULL)
							{	
								$dvalue['condition_prev'] = 'Manual curation. Original value: ' . substr($dvalue['condition_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($dvalue['condition_prev']); 
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('condition', $dvalue['edited']))
						{
							$dvalue['edited']['condition'] = substr($dvalue['edited']['condition'], 0, 250);
							$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($dvalue['edited']['condition']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
						
					//intervention
					$dvalue["intervention_name"] = fix_special_chars($dvalue["intervention_name"]);
					$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $dvalue["intervention_name"]);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('intervention_name', $dvalue['edited']))
						{
							$dvalue['edited']['intervention_name'] = substr($dvalue['edited']['intervention_name'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($dvalue['edited']['intervention_name']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_intervention_name'] != '' && $dvalue['manual_intervention_name'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['intervention_name_prev'] == $dvalue['intervention_name'])
							{
								$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['intervention_name_prev'] != '' && $dvalue['intervention_name_prev'] !== NULL)
							{	
								$dvalue['intervention_name_prev'] = 'Manual curation. Original value: ' . substr($dvalue['intervention_name_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($dvalue['intervention_name_prev']); 
							}
						}
					}
					else
					{
						if($dvalue['manual_intervention_name'] != '' && $dvalue['manual_intervention_name'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($manualChange); 
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['intervention_name_prev'] == $dvalue['intervention_name'])
							{
								$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							if($dvalue['intervention_name_prev'] != '' && $dvalue['intervention_name_prev'] !== NULL)
							{	
								$dvalue['intervention_name_prev'] = 'Manual curation. Original value: ' . substr($dvalue['intervention_name_prev'], 0, 210);
								$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($dvalue['intervention_name_prev']); 
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('intervention_name', $dvalue['edited']))
						{
							$dvalue['edited']['intervention_name'] = substr($dvalue['edited']['intervention_name'], 0, 255);
							$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange);
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($dvalue['edited']['intervention_name']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					
					//start date
					if(isset($dvalue["start_date"])
					&& $dvalue["start_date"] != '' 
					&& $dvalue["start_date"] !== NULL 
					&& $dvalue["start_date"] != '0000-00-00')
					{ 	
						$objPHPExcel->getActiveSheet()->setCellValue('I' . $i, date('m/y',strtotime($dvalue["start_date"])));
					}
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('start_date', $dvalue['edited']))
						{
							 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($dvalue['edited']['start_date']); 
						}
						elseif($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_start_date'] != '' && $dvalue['manual_start_date'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink); 
							if($dvalue['start_date_prev'] == $dvalue['start_date'])
							{
								$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['start_date_prev'] != '' && $dvalue['start_date_prev'] !== NULL)
							{
								$dvalue['start_date_prev'] = 'Manual curation. Original value: ' . $dvalue['start_date_prev'];
								$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($dvalue['start_date_prev']); 
							}
						}
					}
					else
					{
						if($dvalue['manual_start_date'] != '' && $dvalue['manual_start_date'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink); 
							if($dvalue['start_date_prev'] == $dvalue['start_date'])
							{
								$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['start_date_prev'] != '' && $dvalue['start_date_prev'] !== NULL)
							{
								$dvalue['start_date_prev'] = 'Manual curation. Original value: ' . $dvalue['start_date_prev'];
								$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($dvalue['start_date_prev']); 
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('start_date', $dvalue['edited']))
						{
							 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($dvalue['edited']['start_date']); 
						}
						elseif($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					
					//end date	
					if(isset($dvalue["end_date"]) 

					&& $dvalue["end_date"] != '' 
					&& $dvalue["end_date"] !== NULL 
					&& $dvalue["end_date"] != '0000-00-00') 
					{
						$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, date('m/y',strtotime($dvalue["end_date"])));
					}
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('end_date', $dvalue['edited']))
						{
							 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip($dvalue['edited']['end_date']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_end_date'] != '' && $dvalue['manual_end_date'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
							if($dvalue['end_date_prev'] == $dvalue['end_date'])
							{
								$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['end_date_prev'] != '' && $dvalue['end_date_prev'] !== NULL)
							{
								$dvalue['end_date_prev'] = 'Manual curation. Original value: ' . $dvalue['end_date_prev'];
								$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip($dvalue['end_date_prev']); 
							}
						}
					}
					else
					{
						if($dvalue['manual_end_date'] != '' && $dvalue['manual_end_date'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($manualChange);
							$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
							if($dvalue['end_date_prev'] == $dvalue['end_date'])
							{
								$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['end_date_prev'] != '' && $dvalue['end_date_prev'] !== NULL)
							{
								$dvalue['end_date_prev'] = 'Manual curation. Original value: ' . $dvalue['end_date_prev'];
								$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip($dvalue['end_date_prev']); 
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('end_date', $dvalue['edited']))
						{
							 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip($dvalue['edited']['end_date']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
							 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('New record'); 
						}
					}
					
					
					//phase
					if($dvalue['phase'] == 'N/A' || $dvalue['phase'] == '' || $dvalue['phase'] === NULL)
					{
						$phase = 'N/A';
						$phaseColor = $this->phaseValues['N/A'];
					}
					else
					{
						$phase = str_replace('Phase ', '', trim($dvalue['phase']));
						$dvalue['phase'] = str_replace('Phase ', '', trim($dvalue['phase']));
						$phaseColor = $this->phaseValues[$phase];
					}
					$objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $phase);
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('phase', $dvalue['edited']))
						{
							 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip($dvalue['edited']['phase']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('New record'); 
						}
						elseif($dvalue['manual_phase'] != '' && $dvalue['manual_phase'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
							$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['phase_prev'] == $dvalue['phase'])
							{	
								$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['phase_prev'] != '' && $dvalue['phase_prev'] !== NULL)
							{
								$dvalue['phase_prev'] = 'Manual curation. Original value: ' . $dvalue['phase_prev'];
								$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip($dvalue['phase_prev']); 
							}
						}
					}
					else
					{
						if($dvalue['manual_phase'] != '' && $dvalue['manual_phase'] !== NULL)
						{
							$objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
							$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
							if($dvalue['phase_prev'] == $dvalue['phase'])
							{	
								$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							elseif($dvalue['phase_prev'] != '' && $dvalue['phase_prev'] !== NULL)
							{
								$dvalue['phase_prev'] = 'Manual curation. Original value: ' . $dvalue['phase_prev'];
								$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip($dvalue['phase_prev']); 
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('phase', $dvalue['edited']))
						{
							 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange);
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip($dvalue['edited']['phase']); 
						}
						else if($dvalue['new'] == 'y')
						{
							 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
							 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('New record'); 
						}
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
					$dvalue["start_date"], $dvalue['end_date'], $objPHPExcel, $i, 'M');
					
					$i++;
					
					if(isset($dvalue['upms']) && !empty($dvalue['upms'])) 
					{
						foreach($dvalue['upms'] as $mkey => $mvalue)
						{ 
							$stMonth = date('m', strtotime($mvalue['start_date']));
							$stYear = date('Y', strtotime($mvalue['start_date']));
							$edMonth = date('m', strtotime($mvalue['end_date']));
							$edYear = date('Y', strtotime($mvalue['end_date']));
							$upmTitle = htmlformat($mvalue['event_description']);
							
							$mvalue['event_link'] = urlencode(trim($mvalue['event_link']));
							$mvalue['result_link'] = urlencode(trim($mvalue['result_link']));
							
							if(!$loggedIn && !$this->liLoggedIn())
							{
								$mvalue['event_link'] = NULL;
							}
								
							//rendering diamonds in case of end date is prior to the current year
							$objPHPExcel->getActiveSheet()->getStyle('L' . $i . ':BB' . $i)->applyFromArray($styleThinBlueBorderOutline);
							$objPHPExcel->getActiveSheet()->getStyle('L' . $i . ':BB' . $i)->getFont()->setSize(8);
							if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
							{
								if(!$loggedIn && !$this->liLoggedIn())
								{
									$mvalue['result_link'] = NULL;
								}
							
								/*if((isset($mvalue['edited']) && $mvalue['edited']['field'] == 'result_link') || ($mvalue['new'] == 'y')) 
									$imgColor = 'red';
								else 
									$imgColor = 'black'; 
									
								$objDrawing = new PHPExcel_Worksheet_Drawing();
								$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
								$objDrawing->setOffsetX(40);
								$objDrawing->setOffsetY(10);
								$objDrawing->setCoordinates('L' . $i);*/
								
								if($mvalue['event_type'] == 'Clinical Data')
								{
									$icon = '&#9830;';
								}
								else if($mvalue['status'] == 'Cancelled')
								{
									$icon = '&#10006;';
								}
								else
								{
									$icon = '&#10004;';
								}
								
								$objPHPExcel->getActiveSheet()->setCellValue('L' . $i, html_entity_decode($SpaceIcon.$icon.$SpaceIcon, ENT_QUOTES, 'UTF-8'));
								$objPHPExcel->getActiveSheet()->getStyle('L' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
								if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
								{
									$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setUrl($mvalue['result_link']);
									$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setTooltip(substr($upmTitle,0,255));
								}
								
							}
							else if($mvalue['status'] == 'Pending')
							{
							
								$icon = '&#8987;';
								$objPHPExcel->getActiveSheet()->setCellValue('L' . $i, html_entity_decode($SpaceIcon.$icon.$SpaceIcon, ENT_QUOTES, 'UTF-8'));
								$objPHPExcel->getActiveSheet()->getStyle('L' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
								if($mvalue['event_link'] != '' && $mvalue['event_link'] !== NULL)
								{
									$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setUrl($mvalue['event_link']);
									$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setTooltip(substr($upmTitle,0,255));
								}
							}
							
							
							$this->upmGnattChartforExcel($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'], 
							$mvalue['end_date'], $mvalue['event_link'], $upmTitle, $objPHPExcel, $i, 'M');
							
							$objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(15);
							$i++;	
						}
					}
				
				}
			}
			else
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					if(isset($tvalue['naUpms']) || isset($tvalue['Trials']))
					{
						if($globalOptions['onlyUpdates'] == "no")
						{
							$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'No trials found');
							$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
							$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->applyFromArray(
											array('fill' => array(
													'type'       => PHPExcel_Style_Fill::FILL_SOLID,
													'rotation'   => 0,
													'startcolor' => array('rgb' => 'EDEAFF'),
													'endcolor'   => array('rgb' => 'EDEAFF')),
											'borders' => array(
														'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF')),
														'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'))),
							));
							$i++;
						}
					}
				}
				else
				{
					if($globalOptions['onlyUpdates'] == "no")
					{
						$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'No trials found');
						$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
						$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->applyFromArray(
										array('fill' => array(
												'type'       => PHPExcel_Style_Fill::FILL_SOLID,
												'rotation'   => 0,
												'startcolor' => array('rgb' => 'EDEAFF'),
												'endcolor'   => array('rgb' => 'EDEAFF')),
										'borders' => array(
													'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF')),
													'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'))),
						));
						$i++;
					}
				}
			}
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
		
		$objPHPExcel->getActiveSheet()->getStyle('A1:K900')->getAlignment()->setWrapText(false);
		$objPHPExcel->getActiveSheet()->getStyle('A1:K900')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'NCT ID');
		$objPHPExcel->getActiveSheet()->setTitle('Larvol Trials');
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('Calibri');

		$objPHPExcel->createSheet(1);
		$objPHPExcel->setActiveSheetIndex(1);
		$objPHPExcel->getActiveSheet()->setTitle('UPMs');

		$objPHPExcel->getActiveSheet()->getStyle('B1:F200')->getAlignment()->setWrapText(false);
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

		$objPHPExcel->getActiveSheet()->getStyle('A1:AX1')->getFont()->setSize(8);

		$i = 2;
		
		/* Display - Unmatched UPM's */
		foreach ($naUpms as $ukey => $uvalue)
		{
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':AX' . $i . '')->applyFromArray($styleThinBlueBorderOutline);
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':AX' . $i . '')->getFont()->setSize(8);
			
			$eventLink = urlencode(trim($uvalue['event_link']));
			$resultLink = urlencode(trim($uvalue['result_link']));
			
			if(!$loggedIn && !$this->liLoggedIn())
			{
				$eventLink = NULL;
			}
			
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
			$pId =  $uvalue["product"];
			if(isset($Values['Data'][$pId]))
			{
				$uvalue["product"] = strip_tags($Values['Data'][$pId]['sectionHeader']);
			}
			$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $uvalue["product"]);
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
				$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($eventLink);
			}
			if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_description'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					if($uvalue['edited']['event_description'] != '' && $uvalue['edited']['event_description'] !== NULL)
					{
						$uvalue['edited']['event_description'] = 'Previous value: ' . substr($uvalue['edited']['event_description'], 0, 230);
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($uvalue['edited']['event_description']); 
					}

					else
					{
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_link'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					if($uvalue['edited']['event_link'] != '' && $uvalue['edited']['event_link'] !== NULL)
					{
						$uvalue['edited']['event_link'] = 'Previous value: ' . substr($uvalue['edited']['event_link'], 0, 230);
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip($uvalue['edited']['event_link']); 
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
			if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_type'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink != NULL)
				 {
					$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['event_type'] != '' && $uvalue['edited']['event_type'] !== NULL)
					{
						$uvalue['edited']['event_type'] = 'Previous value: ' . substr($uvalue['edited']['event_type'], 0, 230);
						$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip($uvalue['edited']['event_type']);
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
			if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'start_date'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['start_date'] != '' && $uvalue['edited']['start_date'] !== NULL)
					{
						$uvalue['edited']['start_date'] = 'Previous value: ' . $uvalue['edited']['start_date'];
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($uvalue['edited']['start_date']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'start_date_type'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['start_date_type'] != '' && $uvalue['edited']['start_date_type'] !== NULL)
					{
						$uvalue['edited']['start_date_type'] = 'Previous value: ' . $uvalue['edited']['start_date_type'];
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip($uvalue['edited']['start_date_type']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
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
			}
			
			
			//upm end date
			$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, date('m/y',strtotime($uvalue["end_date"])));
			if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'end_date'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['end_date'] != '' && $uvalue['edited']['end_date'] !== NULL)
					{
						$uvalue['edited']['end_date'] = 'Previous value: ' . $uvalue['edited']['end_date'];
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($uvalue['edited']['end_date']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if(isset($uvalue['edited']) && ($uvalue['edited']['field'] == 'end_date_type'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['end_date_type'] != '' && $uvalue['edited']['end_date_type'] !== NULL)
					{
						$uvalue['edited']['end_date_type'] = 'Previous value: ' . $uvalue['edited']['end_date_type'];
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip($uvalue['edited']['end_date_type']); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
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
			}
			
			
			//upm result column
			if($resultLink != '' && $resultLink !== NULL) 
			{
				if(!$loggedIn && !$this->liLoggedIn())
				{
					$resultLink = NULL;
				}
								
				if($uvalue['event_type'] == 'Clinical Data')
				{
					$icon = '&#9830;';
				}
				else if($uvalue['status'] == 'Cancelled')
				{
					$icon = '&#10006;';
				}
				else
				{
					$icon = '&#10004;';
				}
				
				$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, html_entity_decode($SpaceIcon.$icon.$SpaceIcon, ENT_QUOTES, 'UTF-8'));
				$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$uvalue['event_description'] = substr($uvalue['event_description'], 0, 255);
				if($resultLink != '' && $resultLink !== NULL) 
				{
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($resultLink);
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($uvalue['event_description']);
				}
			}
			elseif($uvalue['status'] == 'Pending')
			{
		
				$icon = '&#8987;';
				$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, html_entity_decode($SpaceIcon.$icon.$SpaceIcon, ENT_QUOTES, 'UTF-8'));
				$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$uvalue['event_description'] = substr($uvalue['event_description'], 0, 255);
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($eventLink);
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip($uvalue['event_description']);
				}
			}
			

			$stMonth = date('m', strtotime($uvalue['start_date']));
			$stYear = date('Y', strtotime($uvalue['start_date']));
			$edMonth = date('m', strtotime($uvalue['end_date']));
			$edYear = date('Y', strtotime($uvalue['end_date']));
					
			$this->upmGnattChartforExcel($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $uvalue['start_date'], 
			$uvalue['end_date'], $eventLink, $uvalue["event_description"], $objPHPExcel, $i, 'I');
			
                        
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
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
		header('Content-Disposition: attachment;filename="  DTT  _' . date('Y-m-d_H.i.s') . '.xlsx"');
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();

		exit;
	}
	
	function generateTsvFile($resultIds, $globalOptions)
	{	
		$Values = array();
	
		$Ids = array();
		$TrialsInfo = array();
		$Trials = array();
		
		$this->timeParams($globalOptions);
		
		if(isset($resultIds['e1']) && isset($resultIds['e2']))
		{
			$Arr = $this->processHmParams($resultIds, $globalOptions);
			
			$pId = $Arr['pId'];
			$TrialsInfo = $Arr['TrialsInfo'];
		
			$Values = $this->compileEntitiesData($pId, $TrialsInfo, $resultIds, $globalOptions, 'tsv');
			
			unset($pId, $TrialsInfo);
		}
		else 
		{
			if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '')
			{
				$Arr = $this->processHmParams($resultIds, $globalOptions);
			}
			else
			{
				$Arr = $this->processNonHmParams($resultIds, $globalOptions);
			}
			
			$ottType = $Arr['ottType'];
			$Ids = $Arr['Ids'];
			$TrialsInfo = $Arr['TrialsInfo'];
			
			if(isset($globalOptions['JSON_search']))
			{
				$Values = $this->compileJsonData($globalOptions, 'tsv');
			}
			else
			{
				$Values = $this->compileOTTData($ottType, $TrialsInfo, $Ids, $globalOptions, 'tsv');
			}
			
			unset($Ids, $productSelector, $TrialsInfo);
		}
		
		foreach($Values['Data'] as $tkey => $tvalue)
		{
			unset($Values['sectionHeader']);
			
			if(isset($tvalue['Trials']))
			{
				foreach($tvalue['Trials'] as $tkey => & $tvalue)
				{
					$Trials[] = $tvalue;
				}
			}
		}
		unset($Values);
		
		$outputStr = "NCT ID \t Title \t N \t Region \t Status \t Sponsor \t Condition \t Interventions \t Start \t End \t Ph \n";
		
		foreach($Trials as $key => $value)
		{
			$startDate = '';
			$endDate = '';
			$phase = '';
			
			if($value["start_date"] != '' && $value["start_date"] !== NULL && $value["start_date"] != '0000-00-00')
			{
				$startDate =  date('m/Y', strtotime($value["start_date"]));
				
			}
			if($value["end_date"] != '' && $value["end_date"] !== NULL && $value["end_date"] != '0000-00-00')
			{
				$endDate = date('m/Y', strtotime($value["end_date"]));
			}
			
			if($value['phase'] == 'N/A' || $value['phase'] == '' || $value['phase'] === NULL)
			{
				$phase = 'N/A';
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($value['phase']));
			}
			
			$outputStr .= $value['nct_id'] . "\t" . $value['brief_title'] . "\t" . $value['enrollment'] . "\t" . $value['region'] . "\t"
						. $value['overall_status'] . "\t" . $value['lead_sponsor'];
			if($value['lead_sponsor'] != '' && $value['collaborator'] != ''
			&& $value['lead_sponsor'] != NULL && $value['collaborator'] != NULL)
			{
				$outputStr .= ', ';
			}
			$outputStr .= $value['collaborator'] . "\t" . $value['condition'] . "\t" . $value['intervention_name'] 
							. "\t" . $startDate . "\t" . $endDate . "\t". $phase . "\n";		
		}
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-type: application/force-download"); 
		header("Content-Type: application/tsv");
		header('Content-Disposition: attachment;filename="DTT_Export_' . date('Y-m-d') . '.tsv"');
		header("Content-Transfer-Encoding: binary ");
		echo $outputStr;
		exit();  
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
		//$upmLink = urlencode($upmLink);
		$upmTitle = substr($upmTitle,0,255); //Take 255 characters only to disply as tooltip
		$bgColor = (array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => '9966FF'),
									'endcolor'   => array('rgb' => '9966FF'))
						));
						
		$hoverText = '';
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00'))
		{
			$hoverText = '';
		}
		elseif($startDate == '' || $startDate === NULL || $startDate == '0000-00-00')
		{
			$hoverText = date('M Y', strtotime($endDate));
		}
		elseif($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')
		{
			$hoverText = date('M Y', strtotime($startDate));
		}
		elseif($endDate < $startDate)
		{
			$hoverText = date('M Y', strtotime($endDate));
		}
		else
		{
			$hoverText = date('M Y', strtotime($startDate)) . ' - ' . date('M Y', strtotime($endDate));
		}
		

		$upmTitle = $hoverText . ' ' . $upmTitle;
		
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
	
	function generatePdfFile($resultIds, $globalOptions)
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		
		$currentYear = date('Y');
		$secondYear = date('Y')+1;
		$thirdYear = date('Y')+2;
		
		$pdfContent = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
						. '<html xmlns="http://www.w3.org/1999/xhtml">'
						. '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
						. '<title>Larvol PDF Export</title>';
		$pdfStyle	 = 	'<style type="text/css">'
						. 'body { font-family:Arial; font-color:black; font-size:8px;}'
						. 'a, a:hover{color:#000000;text-decoration:none;display:block;width:100%; height:100%;}'
						.'td {vertical-align:top; border-right: 0.5px solid blue; border-left:0.5px solid blue; border-top: 0.5px solid blue; border-bottom: 
						0.5px solid blue;}'
						.'tr {border-right: 0.5px solid blue; border-left: 0.5px solid blue; border-top: 0.5px solid blue; border-bottom: 0.5px solid blue;}'
						.'.title { background-color:#EDEAFF;}'
						.'.alttitle { background-color:#D5D3E6;}'
						.'.highlight {color:#FF0000;}'
						.'.manual {color:#FF7700;}'
						.'.manage {table-layout:fixed;border-top:0.5px solid blue;border-left:0.5px solid blue;border-bottom:0.5px solid blue;}'
						.'.manage td{ margin:0; padding:0;}'
						.'.manage th { border-top:0.5px solid blue;	border-left:0.5px solid blue; border-right:0.5px solid blue;color:#0000FF;white-space:nowrap;}'
						.'.newtrial td, .newtrial td a{ color:#FF0000;}'
						.'.bomb { float:left; margin-top:20px; text-align:center;}'
						.'.result {	font-weight:bold;font-size:18px;}'
						.'.norecord { padding:0px; height:auto; line-height:normal; font-weight:normal;	background-color: #EDEAFF; color:#000000;}'
						.'.sectiontitles{ font-family: Arial; font-weight: bold; background-color: #A2FF97;}'
						.'tr.upms td{ text-align: left;background-color:#C5E5FA;}'
						.'tr.upms td a{	color:#0000FF; text-decoration:none;}'
						.'@page {margin-top: 1em; margin-bottom: 2em;}'
						.'.nobr {white-space: nowrap}'
						.'.startdatehighlight {border-right-color: red}'
						.'.tag {color:#120f3c; font-weight:bold;}'
						.'</style>';	
		
		$pdfContent .= $pdfStyle;
		$pdfContent .='</head>'
						.'<body>'
						.'<div align="center"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="200" height="25" id="header" /></div><br/>';
		
		$headerContent = $pdfContent;
		
		require_once('tcpdf/config/lang/eng.php');
		require_once('tcpdf/tcpdf.php');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		// set document information
		//$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Larvol Trials');
		$pdf->SetTitle('Larvol Trials');
		$pdf->SetSubject('Larvol Trials');
		$pdf->SetKeywords('Larvol Trials, Larvol Trials PDF Export');
		
		$pdf->SetFont('verdana', '', 8);
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

		
		track_time($n,'HTML to PDF conversion started');
		$time_start = microtime(true);
		
		$headerContent = preg_replace('/(background-image|background-position|background-repeat):(\w)*\s/', '', $headerContent);
		$pdf->writeHTML($headerContent, true, false, true, false, '');
		
		$Values = array();
		$Ids = array();
		$TrialsInfo = array();
		
		if(isset($resultIds['e1']) && isset($resultIds['e2']))
		{
			$Arr = $this->processEntitiesParams($resultIds, $globalOptions);
			
			$pId = $Arr['pId'];
			$TrialsInfo = $Arr['TrialsInfo'];
		
			$Values = $this->compileEntitiesData($pId, $TrialsInfo, $resultIds, $globalOptions, 'excel');
			
			unset($pId, $TrialsInfo);
		}
		else 
		{
			if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '')
			{
				$Arr = $this->processHmParams($resultIds, $globalOptions);
			}
			else
			{
				$Arr = $this->processNonHmParams($resultIds, $globalOptions);
			}
			
			$ottType = $Arr['ottType'];
			$Ids = $Arr['Ids'];
			$TrialsInfo = $Arr['TrialsInfo'];
			
			if(isset($globalOptions['JSON_search']))
			{
				$Values = $this->compileJsonData($globalOptions, 'tsv');
			}
			else
			{	
				$Values = $this->compileOTTData($ottType, $TrialsInfo, $Ids, $globalOptions, 'pdf');	
			}
			
			unset($Ids, $productSelector, $TrialsInfo);
		}
		
		
		$tableHeader ='<table style="border-collapse:collapse;" width="100%" cellpadding="0" cellspacing="0" class="manage">'
						 . '<thead><tr>'. (($loggedIn) ? '<th valign="bottom" align="center" style="width:30px; vertical-align:bottom;" >ID</th>' : '' )
						 . '<th valign="bottom" height="11px" align="center" style="width:93px; vertical-align:bottom;">Title</th>'
						 . '<th valign="bottom" align="center" style="width:18px; vertical-align:bottom;" title="Black: Actual&nbsp;&nbsp;Gray: Anticipated&nbsp;&nbsp;Red: Change greater than 20%">N</th>'
						 . '<th valign="bottom" align="center" style="width:41px; vertical-align:bottom;" title="&quot;RoW&quot; = Rest of World">Region</th>'
						 . '<th valign="bottom" align="center" style="width:60px; vertical-align:bottom;">Interventions</th>'
						 . '<th valign="bottom" align="center" style="width:41px; vertical-align:bottom;">Sponsor</th>'
						 . '<th valign="bottom" align="center" style="width:41px; vertical-align:bottom;">Status</th>'
						 . '<th valign="bottom" align="center" style="width:60px; vertical-align:bottom;">Conditions</th>'
						 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;" title="MM/YY">Start</th>'
						 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;" title="MM/YY">End</th>'
						 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;">Ph</th>'
						 . '<th valign="bottom" align="center" style="width:20px; vertical-align:bottom;">Result</th>'
						 . '<th valign="bottom" align="center" style="width:6px; vertical-align:bottom;" colspan="3">-</th>'
						 . '<th valign="bottom" align="center" style="width:24px; vertical-align:bottom;" colspan="12">' . $currentYear . '</th>'
						 . '<th valign="bottom" align="center" style="width:24px; vertical-align:bottom;" colspan="12">' . $secondYear . '</th>'
						 . '<th valign="bottom" align="center" style="width:24px; vertical-align:bottom;" colspan="12">' . $thirdYear . '</th>'
						 . '<th valign="bottom" align="center" style="width:6px; vertical-align:bottom;" colspan="3">+</th></tr></thead>'
						 . '<tr style="border:none; border-top:none;">'
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
						 . '<td border="0" style="width:6px; height:0px; border-top:none; border:none;" colspan="3"></td></tr></table>';
		
		$tableHeader = preg_replace('/(background-image|background-position|background-repeat):(\w)*\s/', '', $tableHeader);
		//print table header with style
		$pdf->writeHTML($pdfStyle. $tableHeader, true, false, true, false, '');
		
		$counter = 0;
		$outputStr = '';
		$sectionId = '';
		
		if($loggedIn)
			$col_width=548;
		else
			$col_width=518;
		
		$Trials = array();
		
		
		foreach($Values['Data'] as $dkey => $dvalue)
		{
			$outputStr = '';
			$sectionHeader = $dvalue['sectionHeader'];
			if($ottType == 'rowstacked')
			{
				$naUpms = array();
				if($globalOptions['page'] == 1)
				{
					$naUpms = $Values['Data'][0]['naUpms'];
					unset($dvalue[0]['naUpms']);
				}
			}
			else
			{
				$naUpms = $dvalue['naUpms'];
			}
				
			if(isset($dvalue['Trials']) && !empty($dvalue['Trials']))
			{	
				//Rendering Upms
				$outputStr .= $this->dUnmatchedUpmsPdf($globalOptions, $ottType, $sectionHeader, $naUpms, 'n');
				
				foreach($dvalue['Trials'] as $tkey => $tvalue)
				{
					if($counter%2 == 1) 
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
					$titleLinkColor = '#000000;';
					$nctId = $tvalue['NCT/nct_id'];	
					
					if(isset($tvalue['upms']))  
						$rowspan = count($tvalue['upms'])+1; 
		
					
					//row starts  
					$outputStr .= '<tr style="width:' . $col_width . 'px; height:'.(24).'px; page-break-inside:avoid;" nobr="true" ' 
								. (($tvalue['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
						
						
					//nctid column
					if(isset($tvalue['manual_is_sourceless']))
					{
						$href = $tvalue['source'];
					}
					else if(isset($tvalue['source_id']) && strpos($tvalue['source_id'], 'NCT') === FALSE)
					{	
						$href = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $nctId;
					}
					else if(isset($tvalue['source_id']) && strpos($tvalue['source_id'], 'NCT') !== FALSE)
					{
						$href = 'http://clinicaltrials.gov/ct2/show/' . padnct($nctId);
					}
					else 
					{ 
						$href = 'javascript:void(0);';
					}
					if($loggedIn) 
					{ 
						$outputStr .= '<td style="width:30px; '.$rowOneBGType.'" class="' . $rowOneType . '" ' . (($tvalue['new'] == 'y') ? 'title="New record"' : '') 
									. ' ><a style="color:' . $titleLinkColor . '" href="' . $href . '" target="_blank">' . $nctId . '</a></td>';
					}
			
			
					//acroynm and title column
					$attr = ' ';
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('brief_title', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['brief_title'];
							$titleLinkColor = '#FF0000;';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
							$titleLinkColor = '#FF0000;';
						}
						elseif($tvalue['manual_brief_title'] != '' && $tvalue['manual_brief_title'] !== NULL)
						{
							if($tvalue['brief_title_prev'] == $tvalue['brief_title'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['brief_title_prev'] != '' && $tvalue['brief_title_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['brief_title_prev'];
							}
							$titleLinkColor = '#FF7700';
						}
					}
					else
					{
						if($tvalue['manual_brief_title'] != '' && $tvalue['manual_brief_title'] !== NULL)
						{
							if($tvalue['original_brief_title'] == $tvalue['brief_title'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['brief_title_prev'] != '' && $tvalue['brief_title_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['brief_title_prev'];
							}
							$titleLinkColor = '#FF7700';
						}
						elseif(!empty($tvalue['edited']) && array_key_exists('brief_title', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['brief_title'];
							$titleLinkColor = '#FF0000;';
						} 
						elseif($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
							$titleLinkColor = '#FF0000;';
						}
					}
					$outputStr .= '<td style="width:93px;' . $rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . ' ' . $attr . '"><span>'
								. '<a style="color:' . $titleLinkColor . '" href="' . $href . '"  target="_blank">' . htmlformat($tvalue['brief_title']) 
								. '</a></span></td>';
						
							
					//enrollment column
					$attr = ' ';
					$highlightFlag = true;
					if($globalOptions['onlyUpdates'] != "yes")
					{
						$prevValue = substr($tvalue['edited']['enrollment'], 16);
						$highlightFlag = getDifference($prevValue, $tvalue['enrollment']);
					}
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('enrollment', $tvalue['edited']) && $highlightFlag) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['enrollment'];
						}
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_enrollment'] != '' && $tvalue['manual_enrollment'] !== NULL)
						{
							if($tvalue['enrollment_prev'] == $tvalue['enrollment'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['enrollment_prev'] != '' && $tvalue['enrollment_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['enrollment_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_enrollment'] != '' && $tvalue['manual_enrollment'] !== NULL)
						{
							if($tvalue['enrollment_prev'] == $tvalue['enrollment'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['enrollment_prev'] != '' && $tvalue['enrollment_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['enrollment_prev'];
							}
						}
						elseif(!empty($tvalue['edited']) && array_key_exists('enrollment', $tvalue['edited']) && $highlightFlag) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['enrollment'];
						}
		
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td style="width:18px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><span>'
								. $tvalue["enrollment"] . '</span></td>';				
			
			
					//region column
					$attr = ' ';
					if(isset($tvalue['manual_is_sourceless']))
					{
						if($tvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_region'] != '' && $tvalue['manual_region'] !== NULL)
						{
							if($tvalue['region_prev'] == $tvalue['region'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['region_prev'] != '' && $tvalue['region_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['region_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_region'] != '' && $tvalue['manual_region'] !== NULL)
						{
							if($tvalue['region_prev'] == $tvalue['region'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['region_prev'] != '' && $tvalue['region_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['region_prev'];
							}
						}
						elseif($tvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" class="' . $rowOneType . '" rowspan="' . $rowspan . '" ' . $attr . '>'
								. '<span>' . $tvalue['region'] . '</span></td>';
			
						
					//intervention name column
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('intervention_name', $tvalue['edited']))
						{
							$attr = ' highlight" title="' . $tvalue['edited']['intervention_name'];
						} 
						else if($tvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_intervention_name'] != '' && $tvalue['manual_intervention_name'] !== NULL)
						{
							if($tvalue['intervention_name_prev'] == $tvalue['intervention_name'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['intervention_name_prev'] != '' && $tvalue['intervention_name_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['intervention_name_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_intervention_name'] != '' && $tvalue['manual_intervention_name'] !== NULL)
						{
							if($tvalue['intervention_name_prev'] == $tvalue['intervention_name'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['intervention_name_prev'] != '' && $tvalue['intervention_name_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['intervention_name_prev'];
							}
						}
						elseif(!empty($tvalue['edited']) && array_key_exists('intervention_name', $tvalue['edited']))
						{
							$attr = ' highlight" title="' . $tvalue['edited']['intervention_name'];
						} 
						else if($tvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td style="width:60px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<span>' . $tvalue['intervention_name'] . '</span></td>';
			
			
					//collaborator and sponsor column
					$attr = ' ';
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && (array_key_exists('collaborator', $tvalue['edited']) 
						|| array_key_exists('lead_sponsor', $tvalue['edited']))) 
						{
								
							$attr = ' highlight" title="';
							if(array_key_exists('lead_sponsor', $tvalue['edited']))
							{
								$attr .= $tvalue['edited']['lead_sponsor'];
							}
							if(array_key_exists('lead_sponsor', $tvalue['edited']) && array_key_exists('collaborator', $tvalue['edited']))
							{
								$attr .=  ', ';
							}
							if(array_key_exists('collaborator', $tvalue['edited'])) 
							{
								$attr .= $tvalue['edited']['collaborator'];
							}
							$attr .= '';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif(($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL) 
						|| ($tvalue['manual_collaborator'] != '' && $tvalue['manual_collaborator'] !== NULL))
						{
							if($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL)
							{
								if($tvalue['lead_sponsor_prev'] == $tvalue['lead_sponsor'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								elseif($tvalue['lead_sponsor_prev'] != '' && $tvalue['lead_sponsor_prev'] !== NULL)
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['lead_sponsor_prev'];
								}
							}
							else
							{
								if($tvalue['collaborator_prev'] == $tvalue['collaborator'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								elseif($tvalue['collaborator_prev'] != '' && $tvalue['collaborator_prev'] !== NULL)
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['collaborator_prev'];
								}
							}
						}
					}
					else
					{
						if(($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL) 
						|| ($tvalue['manual_collaborator'] != '' && $tvalue['manual_collaborator'] !== NULL))
						{
							if($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL)
							{
								if($tvalue['lead_sponsor_prev'] == $tvalue['lead_sponsor'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								elseif($tvalue['lead_sponsor_prev'] != '' && $tvalue['lead_sponsor_prev'] !== NULL)
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['lead_sponsor_prev'];
								}
							}
							else
							{
								if($tvalue['collaborator_prev'] == $tvalue['collaborator'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								elseif($tvalue['collaborator_prev'] != '' && $tvalue['collaborator_prev'] !== NULL)
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['collaborator_prev'];
								}
							}
						}
						elseif(!empty($tvalue['edited']) && (array_key_exists('collaborator', $tvalue['edited']) 
						|| array_key_exists('lead_sponsor', $tvalue['edited']))) 
						{
								
							$attr = ' highlight" title="';
							if(array_key_exists('lead_sponsor', $tvalue['edited']))
							{
								$attr .= $tvalue['edited']['lead_sponsor'];
							}
							if(array_key_exists('lead_sponsor', $tvalue['edited']) && array_key_exists('collaborator', $tvalue['edited']))
							{
								$attr .=  ', ';
							}
							if(array_key_exists('collaborator', $tvalue['edited'])) 
							{
								$attr .= $tvalue['edited']['collaborator'];
							}
							$attr .= '';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<span>' . $tvalue['lead_sponsor'];
					if($tvalue['lead_sponsor'] != '' && $tvalue['collaborator'] != ''
					&& $tvalue['lead_sponsor'] != NULL && $tvalue['collaborator'] != NULL)
					{
						$outputStr .= ', ';
					}
					$outputStr .= $tvalue["collaborator"] . '</span></td>';
			
					//overall status column
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('overall_status', $tvalue['edited'])) 
						{
							$attr = 'class="highlight ' . $rowOneType . ' " title="' . $tvalue['edited']['overall_status'] . '" ';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = 'title="New record" class="' . $rowOneType . '"' ;
						}
						elseif($tvalue['manual_overall_status'] != '' && $tvalue['manual_overall_status'] !== NULL)
						{
							if($tvalue['original_overall_status'] == $tvalue['overall_status'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['original_overall_status'];
							}
						} 
					}
					else
					{
						if($tvalue['manual_overall_status'] != '' && $tvalue['manual_overall_status'] !== NULL)
						{
							if($tvalue['overall_status_prev'] == $tvalue['overall_status'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['overall_status_prev'] != '' && $tvalue['overall_status_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['original_overall_status'];
							}
						}
						else if(!empty($tvalue['edited']) && array_key_exists('overall_status', $tvalue['edited'])) 
						{
							$attr = 'class="highlight ' . $rowOneType . ' " title="' . $tvalue['edited']['overall_status'] . '" ';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = 'title="New record" class="' . $rowOneType . '"' ;
						}
					}
					$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" ' . $attr . ' rowspan="' . $rowspan . '">'  
								. '<span>' . $tvalue['overall_status'] . '</span></td>';
							
							
					//condition column
					$attr = ' ';
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('condition', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['condition'];
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_condition'] != '' && $tvalue['manual_condition'] !== NULL)
						{
							if($tvalue['condition_prev'] == $tvalue['condition'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['condition_prev'] != '' && $tvalue['condition_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['condition_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_condition'] != '' && $tvalue['manual_condition'] !== NULL)
						{
							if($tvalue['condition_prev'] == $tvalue['condition'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['condition_prev'] != '' && $tvalue['condition_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['condition_prev'];
							}
						}
						else if(!empty($tvalue['edited']) && array_key_exists('condition', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['condition'];
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					
					$outputStr .= '<td style="width:60px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<span>' . $tvalue['condition'] . '</span></td>';
							
								
					//start date column
					$attr = ' ';
					$borderLeft = '';
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('start_date', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['start_date'] ;
							$borderLeft = 'startdatehighlight';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_start_date'] != '' && $tvalue['manual_start_date'] !== NULL)
						{
							if($tvalue['start_date_prev'] == $tvalue['start_date'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['start_date_prev'] != '' && $tvalue['start_date_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['start_date_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_start_date'] != '' && $tvalue['manual_start_date'] !== NULL)
						{
							if($tvalue['start_date_prev'] == $tvalue['start_date'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['start_date_prev'] != '' && $tvalue['start_date_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['start_date_prev'];
							}
						}
						elseif(!empty($tvalue['edited']) && array_key_exists('start_date', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['start_date'];
							$borderLeft = 'startdatehighlight';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '" ><span>'; 
					if($tvalue["start_date"] != '' && $tvalue["start_date"] != NULL && $tvalue["start_date"] != '0000-00-00') 
					{
						$outputStr .= date('m/y',strtotime($tvalue["start_date"]));
					} 
					else 
					{
						$outputStr .= '&nbsp;';
					}
					$outputStr .= '</span></td>';
							
							
					//end date column
					$attr = ' ';
					$borderRight = '';
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('end_date', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['end_date'];
							$borderRight = 'border-right:1px solid red;';
						} 
						elseif($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_end_date'] != '' && $tvalue['manual_end_date'] !== NULL)
						{
							if($tvalue['end_date_prev'] == $tvalue['end_date'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['end_date_prev'] != '' && $tvalue['end_date_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['end_date_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_end_date'] != '' && $tvalue['manual_end_date'] !== NULL)
						{
							if($tvalue['end_date_prev'] == $tvalue['end_date'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['end_date_prev'] != '' && $tvalue['end_date_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['end_date_prev'];
							}
						}
						else if(!empty($tvalue['edited']) && array_key_exists('end_date', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['end_date'];
							$borderRight = 'border-right:1px solid red;';
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType  . $attr . '"><span>'; 
					if($tvalue["end_date"] != '' && $tvalue["end_date"] != NULL && $tvalue["end_date"] != '0000-00-00') 
					{
						$outputStr .= date('m/y',strtotime($tvalue["end_date"]));
					} 
					else 
					{
						$outputStr .= '&nbsp;';
					}
					$outputStr .= '</span></td>';
								
														
					//phase column
					if(isset($tvalue['manual_is_sourceless']))
					{
						if(!empty($tvalue['edited']) && array_key_exists('phase', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['phase'];
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif($tvalue['manual_phase'] != '' && $tvalue['manual_phase'] !== NULL)
						{
							if($tvalue['phase_prev'] == $tvalue['phase'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['phase_prev'] != '' && $tvalue['phase_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['phase_prev'];
							}
						}
					}
					else
					{
						if($tvalue['manual_phase'] != '' && $tvalue['manual_phase'] !== NULL)
						{
							if($tvalue['phase_prev'] == $tvalue['phase'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							elseif($tvalue['phase_prev'] != '' && $tvalue['phase_prev'] !== NULL)
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['phase_prev'];
							}
						}
						elseif(!empty($tvalue['edited']) && array_key_exists('phase', $tvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $tvalue['edited']['phase'];
						} 
						else if($tvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					if($tvalue['phase'] == 'N/A' || $tvalue['phase'] == '' || $tvalue['phase'] === NULL)
					{
						$phase = 'N/A';
					}
					else
					{
						$phase = str_replace('Phase ', '', trim($tvalue['phase']));
					}
					$phaseColor = $this->phaseValues[$phase];
					$outputStr .= '<td align="center" style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' 
								. '<span>' . $phase . '</span></td>';				
				
					$outputStr .= '<td style="width:20px;">&nbsp;</td>';
					
					$startMonth = date('m',strtotime($tvalue['start_date']));
					$startYear = date('Y',strtotime($tvalue['start_date']));
					$endMonth = date('m',strtotime($tvalue['end_date']));
					$endYear = date('Y',strtotime($tvalue['end_date']));
		
		
					//rendering project completion gnatt chart
					$trialGnattChart = $this->trialGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, 
						$tvalue['start_date'], $tvalue['end_date'], $phaseColor, $borderRight, $borderLeft);
					
					
					$trialGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $trialGnattChart);	
					$outputStr .= $trialGnattChart;	
					
					$outputStr .= '</tr>';
				
				
					//rendering matched upms
					if(isset($tvalue['upms']) && !empty($tvalue['upms'])) 
					{
						foreach($tvalue['upms'] as $mkey => $mvalue) 
						{ 
							$str = '';
							$diamond = '';
							$resultImage = '';
			
							$stMonth = date('m', strtotime($mvalue['start_date']));
							$stYear = date('Y', strtotime($mvalue['start_date']));
							$edMonth = date('m', strtotime($mvalue['end_date']));
							$edYear = date('Y', strtotime($mvalue['end_date']));
							$upmTitle = htmlformat($mvalue['event_description']);
							
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
							
							if(!$loggedIn && !$this->liLoggedIn())
							{
								$mvalue['event_link'] = NULL;
							}
							
							$outputStr .= '<td style="width:20px; text-align:center;"><br />';
							
		
							if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
							{
								if(!$loggedIn && !$this->liLoggedIn())
								{
									$mvalue['result_link'] = NULL;
								}
									
								if((isset($mvalue['edited']) && $mvalue['edited']['field'] == 'result_link') || ($mvalue['new'] == 'y')) 
									$imgColor = 'red';
								else 
									$imgColor = 'black'; 
								
								if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
								{
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
								else
								{
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
								}
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
							
							$upmBorderLeft = '';
							if(isset($mvalue['edited']) && $mvalue['edited']['field'] == 'start_date')
							{
								$upmBorderLeft = 'startdatehighlight';
							}
								
		
							$upmBorderRight = '';
							if(isset($mvalue['edited']) && $mvalue['edited']['field'] == 'end_date' && $mvalue['edited']['end_date'] !== NULL && $mvalue['edited']['end_date'] != '')
							{
								$upmBorderRight = 'border-right:1px solid red;';
							}
							
							//rendering upm (upcoming project completion) chart
							$upmGnattChart = $this->upmGnattChart($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'],
							$mvalue['end_date'], $mvalue['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft, $tvalue['larvol_id']);
							
							$upmGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $upmGnattChart);
							
							$outputStr .= $upmGnattChart;
							$outputStr .= '</tr>';
						}
					}
					
					++$counter;
				}
			}
			else
			{
				//Rendering Upms
				$outputStr .= $this->dUnmatchedUpmsPdf($globalOptions, $ottType, $sectionHeader, $naUpms, 'y');
			}
			$outputStr = preg_replace('/(background-image|background-position|background-repeat):(\w)*\s/', '', $outputStr);
			$outputStr = '<table style="border-collapse:collapse;" width="100%" cellpadding="0" cellspacing="0" class="manage">'.$outputStr.'</table>';
			$pdf->writeHTML($pdfStyle.$outputStr, true, false, true, false, '');
		}
		
		$pdfContent .= $outputStr;
		
		//echo "<br/><br/>===>".
		$pdfContent .= '</table></body></html>';
		$endTableContent = '</body></html>';
		$pdf->writeHTML($endTableContent, true, false, true, false, '');
		//exit;
		$pdfContent = preg_replace('/(background-image|background-position|background-repeat):(\w)*\s/', '', $pdfContent);
		
		$time_end = microtime(true);
		track_time_diff($n,'HTML to PDF conversion ended, time taken in miliseconds', $time_start, $time_end);
		ob_end_clean();
		//Close and output PDF document
		track_time($n,'PDF download start');
		$time_start = microtime(true);
		$pdf->Output('Larvol PDF_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
		$time_end = microtime(true);
		track_time_diff($n,'PDF download end, total time elapsed', $time_start, $time_end);
		
	}
	
	function dUnmatchedUpmsPdf($globalOptions, $ottType, $sectionHeader, $naUpms, $noRecordRow = 'y')
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		
		$outputStr = '';
		
		if($globalOptions['includeProductsWNoData'] == "off")
		{
			if(!empty($naUpms))
			{
				$outputStr .= $this->displayUpmHeadersPdf($ottType, $naUpms, $sectionHeader);
				if($noRecordRow == 'y')
				{
					if($globalOptions['onlyUpdates'] == "no")
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
					}
				}
			}
			else
			{
				if($noRecordRow == 'n')
				{
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">' . $sectionHeader . '</td></tr>';
				}
			}
		}
		else
		{
			if(!empty($naUpms))
			{
				$outputStr .= $this->displayUpmHeadersPdf($ottType, $naUpms, $sectionHeader);
			}
			else
			{
				$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">' . $sectionHeader . '</td></tr>';
			}
			if($noRecordRow == 'y')
			{
				if($globalOptions['onlyUpdates'] == "no")
				{
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
				}
			}
		}
		return $outputStr;
	}
	
	function displayUpmHeadersPdf($ottType, $naUpms, $sectionHeader)
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		
		$outputStr = '';
		if($loggedIn)
			$col_width=548;
		else
			$col_width=518;
			
		$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $sectionHeader);
		$naUpmIndex = substr($naUpmIndex, 0, 15);
		
		if($ottType == 'rowstacked')
		{
			$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
						. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
						. 'style="background: url(\'images/down.png\') no-repeat left center;"'
						. ' onclick="sh(this,\'rowstacked\');" style="width:' . $col_width . 'px;">&nbsp;</td></tr>'
						. $this->displayUnMatchedUpmsPdf($loggedIn, $naUpmIndex, $naUpms)
						. '<tr class="trialtitles" style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true">'
						. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles" style="width:' . $col_width . 'px;">' 
						. $sectionHeader . '</td></tr>';
		}
		else
		{
			if($ottType == 'colstacked')
				$image = 'up';
			else
				$image = 'down';
			
			$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
						. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
						. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
						. ' onclick="sh(this,\'' . $naUpmIndex . '\');" style="width:' . $col_width . 'px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
						. $sectionHeader . '</td></tr>';
			$outputStr .= $this->displayUnMatchedUpmsPdf($loggedIn, $naUpmIndex, $naUpms);
		}
		
		return $outputStr;
	}
	
	function displayUnMatchedUpmsPdf($loggedIn, $naUpmIndex, $naUpms)
	{
		global $now;
		
		if($loggedIn)
			$col_width=570;
		else
			$col_width=548;
			
		$outputStr = '';
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
			$upmTitle = htmlformat($value['event_description']);
			
			$upmBorderLeft = '';
			if(isset($value['edited']) && $value['edited']['field'] == 'start_date')
			{
				$upmBorderLeft = 'startdatehighlight';
			}
			
			//Highlighting the whole row in case of new trials
			if($value['new'] == 'y') 
			{
				$class = 'class="upms newtrial ' . $naUpmIndex . '" ';
			}
			
			//rendering unmatched upms
			$outputStr .= '<tr style="width:'.$col_width.'px; page-break-inside:avoid; background-color:#000;" nobr="true" ' . $class . '>';
			
			
			//field upm-id
			$title = '';
			$attr = '';	
			if($loggedIn)
			{
				if($value['new'] == 'y')
				{
					$titleLinkColor = 'style="color:#FF0000;"';
					$title = ' title = "New record" ';
				}
				$outputStr .= '<td style="width:30px;" ' . $title . '><a ' . $titleLinkColor . ' href="' . urlPath() . 'upm.php?search_id=' 
							. $value['id'] . '" target="_blank">' . $value['id'] . '</a></td>';
			}
			
			if(!$loggedIn && !$this->liLoggedIn())
			{
				$value['event_link'] = NULL;
			}
			
			//field upm event description
			$title = '';
			$attr = '';	
			if(isset($value['edited']) && ($value['edited']['field'] == 'event_description')) 
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
			else if(isset($value['edited']) && ($value['edited']['field'] == 'event_link')) 
			{
				$titleLinkColor = 'style="color:#FF0000;"';
				$attr = ' highlight'; 
				
				if($value['edited']['event_link'] != '' && $value['edited']['event_link'] !== NULL)
				{
					$title = ' title="Previous value: '. $value['edited']['event_link'] . '" '; 
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
			$outputStr .= '<td style="width:273px;" colspan="5" class="' .  $attr . '" ' . $title . '><span>';
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
			$attr = '';	
			if($value['new'] == 'y')
			{
				$title = ' title = "New record" ';
			}
			$outputStr .= '<td style="width:41px;" ' . $title . '><span>' . $value['status'] . '</span></td>';

		
			//field upm event type
			$title = '';
			$attr = '';	
			if(isset($value['edited']) && ($value['edited']['field'] == 'event_type')) 
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
			$outputStr .= '<td style="width:60px;" class="' . $attr . '" ' . $title . '><span>' . $value['event_type'] . ' Milestone</span></td>';
			
			
			//field upm end date
			$title = '';
			$attr = '';	
			$upmBorderRight = '';
			
			if(isset($value['edited']) && ($value['edited']['field'] == 'end_date'))
			{
				$attr = ' highlight';
				$upmBorderRight = 'border-right-color:red;';
				
				if($value['edited']['end_date'] != '' && $value['edited']['end_date'] !== NULL)
				{
					$title = ' title="Previous value: ' . $value['edited']['end_date'] . '" '; 
				}
				else 
				{
					$title = ' title="No Previous value" ';
				}
			} 
			else if(isset($value['edited']) && ($value['edited']['field'] == 'end_date_type'))
			{
				$attr = ' highlight';
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
			$outputStr .= '<td style="width:20px;"  class="' . $attr . '" ' . $title . '><span>';
			
			$outputStr .= (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
								date('m/y',strtotime($value['end_date'])) : '&nbsp;');
								
			$outputStr .= '</span></td><td style="width:20px;"><span>&nbsp;</span></td>';
			
			
			//field upm result 
			$stYear = date('Y',strtotime($value['start_date']));
			$stMonth = date('m',strtotime($value['start_date']));
			$outputStr .= '<td style="width:20px;text-align:center;vertical-align:middle;" ';
			if($stYear < $currentYear)
			{
				$outputStr .= ' class="' . $upmBorderLeft . '" ';
			}
			$outputStr .= '>';
			
			if($value['result_link'] != '' && $value['result_link'] !== NULL)
			{
				if(!$loggedIn && !$this->liLoggedIn())
				{
					$value['result_link'] = NULL;
				}
							
				if((isset($value['edited']) && $value['edited']['field'] == 'result_link') || ($value['new'] == 'y')) 
						$imgColor = 'red';
				else 
					$imgColor = 'black'; 
				
				$outputStr .= '<div title="' . $upmTitle . '">';
				if($value['result_link'] != '' && $value['result_link'] !== NULL)
				{
					$outputStr .= '<a href="' . $value['result_link'] . '" ' . $target . '>';
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
					$outputStr .= '</a>';
				}
				else
				{
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
				}
				$outputStr .= '</div>';
			}
			else if($value['status'] == 'Pending')
			{
				$outputStr .= '<span title="' . $upmTitle . '">';
				if($value['event_link'] != '' && $value['event_link'] !== NULL)
				{
					$outputStr .= '<a href="' . $value['event_link'] . '" target="_blank">'
								. '<img src="images/hourglass.png" alt="Hourglass"  border="0" /></a>';
				}
				else
				{
					$outputStr .= '<img src="images/hourglass.png" alt="Hourglass"  border="0" />';
				}
				$outputStr .= '</span>';
			}
			else
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</td>';		
			
			
			//upm gnatt chart
			$outputStr .= $this->upmGnattChart($stMonth, $stYear, 
							date('m',strtotime($value['end_date'])), date('Y',strtotime($value['end_date'])), $currentYear, $secondYear, $thirdYear, 
							$value['start_date'], $value['end_date'], $value['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft);
			
			$outputStr = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $outputStr);
			$outputStr .= '</tr>';
		}
		
		return $outputStr;
	}
	
	function getProductHmHeaders($hmId, $productIds) 
	{
		global $logger;
		
		$productSelector = array();
		$naUpms = array();
		$TrialsInfo = array();
		$Ids = array();
		
		$where = " WHERE rmh.`report` = '" . $hmId . "' AND rmh.`type` = 'row' ";
		if(!empty($productIds) && !is_array($productIds))	$productIds=array($productIds);
		if(!empty($productIds))
		{
			$where .= " AND pr.`id` IN ('" . implode("','", $productIds) . "') OR pr.LI_id IN ('" . implode("','", $productIds) . "') ";
		}
		
		$Query = "SELECT pr.`id`, pr.`name`, pr.`display_name` as dispname,pr.`class`,pr.`company`, pr.`discontinuation_status`, rmh.`display_name`, rmh.`category`, rmh.`tag` "
						. " FROM `rpt_masterhm_headers` rmh "
						. " JOIN `entities` pr ON pr.`id` = rmh.`type_id` "
						. $where . " ORDER BY rmh.`num` ASC ";
		$Res = m_query(__LINE__,$Query);
		
		if($Res)
		{
			if(mysql_num_rows($Res) > 0)
			{
				while($row = mysql_fetch_assoc($Res))
				{
					$disContinuedTxt = "";
					$sectionHeader = "";
					
					$productIds[] = $productId = $row['id'];
					
					if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
					{
						$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
					}
					
					$productSelector[$productId] = $row['name'];
					if($row['class']=='Product')
						$sectionHeader = formatBrandName($row['name'], 'product');
					elseif(!empty($row['display_name']))
						$sectionHeader = formatBrandName($row['display_name'], 'area');
					else
						$sectionHeader = formatBrandName($row['name'], 'area');
					
					if($row['class']=='Product')	
					$row['company'] = GetCompanyNames($productId);
					
					if($row['company'] !== NULL && $row['company'] != '')
					{
						$productSelector[$productId] .= " / <i>" . $row['company'] . "</i>";
						$sectionHeader .= " / <i>" . $row['company'] . "</i>";
					}
						
					if($row['tag'] != '' && $row['tag'] !== NULL)
					{
						$sectionHeader .= " <span class='tag'>[" . $row['tag'] . "]</span>";
					}
					
					$sectionHeader .= $disContinuedTxt;
					
					$TrialsInfo[$productId]['Id'] = $productId;
					$TrialsInfo[$productId]['sectionHeader'] = $sectionHeader;
					
					$Ids[$productId]['product'] = $productId;
					
					unset($disContinuedTxt);
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		return array('Ids' => $Ids, 'TrialsInfo' => $TrialsInfo, 'productSelector' => $productSelector);
	}
	
	function getAreaHmHeaders($hmId, $areaIds, $dttFlag = false, $lastRow = false)
	{
	
		global $logger;
		if(!empty($areaIds) && !is_array($areaIds))	$areaIds=array($areaIds);
		$productSelector = array();
		$TrialsInfo = array();
		$Ids = array();
		
		$dtt = 0;
		
		$Query = "SELECT dtt FROM `rpt_masterhm` WHERE id = '" . $hmId . "' ";
		$Res = m_query(__LINE__,$Query);
		
		if($Res)
		{
			$Row = mysql_fetch_assoc($Res);
			$dtt = $Row['dtt'];
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		$where = " 1 ";
		$orderby = " ORDER BY rmh.`num` ASC ";
		$limit = " ";
		
		if(!empty($areaIds))
		{
			$where .= " AND rmh.`type_id` IN ('" . implode("', '", $areaIds) . "') ";
		}
		
		if($lastRow)
		{
			$orderby = " ORDER BY rmh.`num` DESC ";
			$limit = " LIMIT 0,1 ";
		}
		
		$Query = "SELECT rmh.`display_name`, rmh.`type_id`, rmh.`category`, ar.`class`, ar.`display_name` AS global_display_name "
					. " FROM `rpt_masterhm_headers` rmh "
					. " JOIN `entities` ar ON  rmh.`type_id` = ar.`id` "
					. " WHERE " . $where . " AND ar.class<>'Product' AND rmh.`report` = '" . $hmId . "' AND rmh.`type` = 'column' " . $orderby . $limit;
		$Res = m_query(__LINE__,$Query);
		
		if($Res)
		{
			if(mysql_num_rows($Res) > 0)
			{
				while($row = mysql_fetch_assoc($Res))
				{
					$sectionHeader = "";
					$areaId = $row['type_id'];
					
					if($row['class']=='Product')
						$sectionHeader = formatBrandName($row['name'], 'product');
					elseif(!empty($row['display_name']))
						$sectionHeader = formatBrandName($row['display_name'], 'area');
					else
						$sectionHeader = formatBrandName($row['class'].' ' . $areaId, 'area');

					$Ids[$areaId]['area'] = $areaId;
					$productSelector[$areaId] = $sectionHeader;
					
					$TrialsInfo[$areaId]['sectionHeader'] = $sectionHeader;
					$TrialsInfo[$areaId]['Id'] = $areaId;
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		if($dtt && $dttFlag)
		{
			array_pop($Ids);
			array_pop($TrialsInfo);
			array_pop($productSelector);
		}

		return array('Ids' => $Ids, 'TrialsInfo' => $TrialsInfo, 'productSelector' => $productSelector);
	}
	
	function processHmParams($resultIds, $globalOptions, $displayType = 'fileExport')
	{
		global $logger;
		$hmId = $globalOptions['hm'];
		
		$aDetails = array();
		$pDetails = array();
		
		$Ids = array();
		$TrialsInfo = array();
		$productSelector = array();
		
		$tHeader = '';
		$ottType = '';
		
		/************** add support for old parameters (p & a)  *********/
			if( (!empty($resultIds['product']) or !empty($resultIds['area'])) and ( empty($resultIds['e1']) and empty($resultIds['e2']) )  )
			{
				$resultIds['e1']=$resultIds['product'];
				$resultIds['e2']=$resultIds['area'];
			}
		/*****************/

		/********* find entity class ****/
			$new_P_array=array();
			$new_A_array=array();
			if(!empty($resultIds['e1']))
			{
				if(!is_array($resultIds['e1']))	$tempar=array($resultIds['e1']);
				else $tempar=$resultIds['e1'];
				foreach($tempar as $eid)
				{
					$enres = m_query(__LINE__,'SELECT `id`,`class` from entities where id="' . $eid . '" limit 1 ');
					if($enres)	$enrow = mysql_fetch_assoc($enres);
					if($enrow and $enrow['class']=='Product' )	$new_P_array[]=$eid;
					else	$new_A_array[]=$eid;
				}
			}
			if(!empty($resultIds['e2']))
			{
				if(!is_array($resultIds['e2']))	$tempar=array($resultIds['e2']);
				else $tempar=$resultIds['e2'];
				foreach($tempar as $eid)
				{
					$enres = m_query(__LINE__,'SELECT `id`,`class` from entities where id="' . $eid . '" limit 1 ');
					if($enres)	$enrow = mysql_fetch_assoc($enres);
					if($enrow and $enrow['class']=='Product' )	$new_P_array[]=$eid;
					else	$new_A_array[]=$eid;
				}
			}
	/*		
			if($resultIds['e2']) unset($resultIds['e2']);			
			if($resultIds['e1']) unset($resultIds['e1']);
			if(!empty($new_A_array)) $resultIds['e2']=$new_A_array;
			if(!empty($new_P_array)) $resultIds['e1']=$new_P_array;
			unset($new_A_array);			
			unset($new_P_array);
		/****************************/
		
		if(count($resultIds['e1']) > 1 && count($resultIds['e2']) > 1)
		{
			$ottType = 'colstacked';
			$tHeader = 'Area: Total';
			$productIds  = $resultIds['e1'];
			$productIds .= ','. $resultIds['e2'];

			
			$pDetails = $this->getProductHmHeaders($hmId, $productIds);
			foreach($pDetails['Ids'] as $ikey => $ivalue)
			{
				$pDetails['Ids'][$ikey]['area'] = implode("','", $resultIds['e2']);
			}
			
			$Ids = $pDetails['Ids'];
			$TrialsInfo = $pDetails['TrialsInfo'];
			$productSelector = $pDetails['productSelector'];
			
			unset($pDetails);
		}
		else if(count($resultIds['e2']) > 1)
		{
			$naUpms = array();
			$ottType = 'rowstacked';
			$areaIds = $resultIds['e2'];
			
			if(empty($resultIds['e1']))
			{
				$tHeader = 'All Products';
				$productIds = array();
				$Query = "SELECT GROUP_CONCAT(type_id) AS type_id FROM `rpt_masterhm_headers` WHERE `report` = '" . $hmId . "' AND `type` = 'row' ";
				$Res = m_query(__LINE__,$Query);
				
				if($Res)
				{
					if(mysql_num_rows($Res) > 0)
					{
						$Row = mysql_fetch_assoc($Res);
						$productIds = explode(',', $Row['type_id']);
					}
				}
				else
				{
					$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
					$logger->error($log);
					unset($log);
				}
			}
			else
			{
				$tHeader = '';
				$productIds = $resultIds['e1'];
				
				$Query = "SELECT `name`, `id`, `class`,`display_name` FROM `entities` WHERE id IN ('" . implode("','", $productIds) . "') OR LI_id IN ('" . implode("','", $productIds) . "') ";
				$Res = m_query(__LINE__,$Query);
				
				if($Res)
				{
					if(mysql_num_rows($Res) > 0)
					{
						$Row = mysql_fetch_assoc($Res);
						if($Res['class']<>'Product' and !empty($Res['display_name']) )
						{
							$tHeader = 'Area: ';
							$tHeader .= htmlformat(strip_tags($Row['display_name']));
						}
						else
						{
							$tHeader = 'Product: '.__LINE__;
							$tHeader .= htmlformat(strip_tags($Row['name']));
						}
						
							
					}
				}
				else
				{
					$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
					$logger->error($log);
					unset($log);
				}
			}
			
			$aDetails = $this->getAreaHmHeaders($hmId, $areaIds, false);
			foreach($aDetails['Ids'] as $ikey => $ivalue)
			{
				$aDetails['Ids'][$ikey]['product'] = implode("','", $productIds);
			}
			
			$Ids = $aDetails['Ids'];
			$TrialsInfo = $aDetails['TrialsInfo'];
			$productSelector = $aDetails['productSelector'];
			unset($aDetails);
		}
		else if(count($resultIds['e1']) > 1)
		{
			$ottType = 'colstacked';
			$productIds = $resultIds['e1'];
			
			if(empty($resultIds['e2']))
			{
				$tHeader = 'All Areas';
				$areaIds = array();
				
				$aDetails = $this->getAreaHmHeaders($hmId, $areaIds, true);
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$areaIds[] = $value['Id'];
				}
				unset($aDetails);
			}
			else
			{
				$tHeader = 'Area: ';
				$areaIds = $resultIds['e2'];
				
				$aDetails = $this->getAreaHmHeaders($hmId, $areaIds, false);
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= $value['sectionHeader'];
				}
				unset($aDetails);
			}
			
			$pDetails = $this->getProductHmHeaders($hmId, $resultIds['e1']);
			foreach($pDetails['Ids'] as $ikey => $ivalue)
			{
				$pDetails['Ids'][$ikey]['area'] = implode("','", $areaIds);
			}
			
			$Ids = $pDetails['Ids'];
			$TrialsInfo = $pDetails['TrialsInfo'];
			$productSelector = $pDetails['productSelector'];
			unset($pDetails);
		}
		else
		{
			if(empty($resultIds['e1']) && empty($resultIds['e2']))
			{
				$ottType = 'colstacked';
				$tHeader = 'Area: ';
				$areaIds = array();
				
				//fetching area(last column) from hm
				$aDetails = $this->getAreaHmHeaders($hmId, $areaIds, false, $lastRow = true);
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= strip_tags($value['sectionHeader']);
					$areaIds = $value['Id'];
				}
				
				$pDetails = $this->getProductHmHeaders($hmId, array());
				foreach($pDetails['Ids'] as $ikey => $ivalue)
				{
					$pDetails['Ids'][$ikey]['area'] = $areaIds;
				}
				
				$Ids = $pDetails['Ids'];
				$TrialsInfo = $pDetails['TrialsInfo'];
				$productSelector = $pDetails['productSelector'];
				unset($aDetails);
				unset($pDetails);
			}
			else if(empty($resultIds['e1']))
			{
				$ottType = 'colstacked';
				$tHeader = 'Area: ';
				
				$areaIds = $resultIds['e2'];
				if(!empty($areaIds) && !is_array($areaIds))	$areaIds=array($areaIds);
				$aDetails = $this->getAreaHmHeaders($hmId, $areaIds, false);
				
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= strip_tags($value['sectionHeader']);
				}
				
				$pDetails = $this->getProductHmHeaders($hmId, array());
				foreach($pDetails['Ids'] as $ikey => $ivalue)
				{
					$pDetails['Ids'][$ikey]['area'] = implode("','", $areaIds);
				}
				
				$Ids = $pDetails['Ids'];
				$TrialsInfo = $pDetails['TrialsInfo'];
				$productSelector = $pDetails['productSelector'];
				unset($aDetails);
				unset($pDetails);
			}
			else if(empty($resultIds['e2']))
			{
				$ottType = 'rowstacked';
				$tHeader = '';
				
				$productIds = $resultIds['e1'];
				if(!empty($productIds) && !is_array($productIds))	$productIds=array($productIds);
				$Query = "SELECT `name`, `id`, `class`,`display_name`  FROM `entities` WHERE id IN ('" . implode("','", $productIds) . "') OR LI_id IN ('" . implode("','", $productIds) . "') ";
				$Res = m_query(__LINE__,$Query);
				
				if($Res)
				{
					if(mysql_num_rows($Res) > 0)
					{
						$row = mysql_fetch_assoc($Res);
						if($row['class']<>'Product' and !empty($row['display_name']) )
						{
							$tHeader = 'Area: ';
							$tHeader .= strip_tags(htmlformat($row['display_name']));
						}
						elseif($row['class']<>'Product')
						{
							$tHeader = 'Area: ';
							$tHeader .= strip_tags(htmlformat($row['name']));
						}
						else
						{
							$tHeader = 'Product: ';
							$tHeader .= strip_tags(htmlformat($row['name']));
						}
					}
				}
						
				else
				{
					$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
					$logger->error($log);
					unset($log);
				}
				
				$aDetails = $this->getAreaHmHeaders($hmId, array(), true);
				if(!empty($productIds) && !is_array($productIds))	$productIds=array($productIds);
				foreach($aDetails['Ids'] as $ikey => $ivalue)
				{
					$aDetails['Ids'][$ikey]['product'] = implode("','", $productIds);
				}
				
				$Ids = $aDetails['Ids'];
				$TrialsInfo = $aDetails['TrialsInfo'];
				$productSelector = $aDetails['productSelector'];
				unset($aDetails);
			}
			else
			{	
				$ottType = 'indexed';
				$tHeader = 'Area: ';
				
				$areaIds = $resultIds['e2'];
				$productIds = $resultIds['e1'];
				
				$aDetails = $this->getAreaHmHeaders($hmId, $areaIds, false);
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= strip_tags($value['sectionHeader']);
				}
				if(!empty($areaIds) && !is_array($areaIds))	$areaIds=array($areaIds);
				$pDetails = $this->getProductHmHeaders($hmId, $productIds);
				foreach($pDetails['Ids'] as $ikey => $ivalue)
				{
					$pDetails['Ids'][$ikey]['area'] = implode("','", $areaIds);
				}
				
				$Ids = $pDetails['Ids'];
				$TrialsInfo = $pDetails['TrialsInfo'];
				$productSelector = $pDetails['productSelector'];
				unset($aDetails);
				unset($pDetails);
			}
		}
		
		return array('tHeader' => $tHeader, 'ottType' => $ottType,'Ids' => $Ids, 'TrialsInfo' => $TrialsInfo, 'productSelector' => $productSelector);
	}
	
	function processNonHmParams($resultIds, $globalOptions, $displayType = 'fileExport')
	{
		$aDetails = array();
		$pDetails = array();
		
		$Ids = array();
		$TrialsInfo = array();
		$productSelector = array();
		
		$tHeader = '';
		$ottType = '';
		if(count($resultIds['e1']) > 1 && count($resultIds['e2']) > 1)
		{
			$ottType = 'colstacked';
			$tHeader = 'Area: Total';
			
			$productIds = $resultIds['e1'];
			$productIds .= ','. $resultIds['e2'];
			
			$pDetails = $this->getProductHeaders($productIds);
			
			foreach($pDetails['Ids'] as $ikey => $ivalue)
			{
				$pDetails['Ids'][$ikey]['area'] = implode("','", $resultIds['e2']);
			}
			
			$Ids = $pDetails['Ids'];
			$TrialsInfo = $pDetails['TrialsInfo'];
			$productSelector = $pDetails['productSelector'];
			
			unset($pDetails);
		}
		else if(count($resultIds['e2']) > 1)
		{
			$ottType = 'rowstacked';
			$areaIds = $resultIds['e2'];
				
			if(empty($resultIds['e1']))
			{
				$tHeader = '';
				$productIds = array();
			}
			else
			{
				$tHeader = 'Product: ';
				$productIds = $resultIds['e1'];
				
				$pDetails = $this->getProductHeaders($productIds, $onlyUpdates);
				
				foreach($pDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= strip_tags($value['sectionHeader']);
				}
			}
			
			$aDetails = $this->getAreaHeaders($areaIds);
			
			foreach($aDetails['Ids'] as $ikey => $ivalue)
			{
				$aDetails['Ids'][$ikey]['product'] = implode("','", $productIds);
			}
			
			$Ids = $aDetails['Ids'];
			$TrialsInfo = $aDetails['TrialsInfo'];
			$productSelector = $aDetails['productSelector'];
			unset($pDetails);
			unset($aDetails);
		}
		else if(count($resultIds['e1']) > 1)
		{
			$ottType = 'colstacked';
			$productIds = $resultIds['e1'];
				
			if(empty($resultIds['e2']))
			{
				$tHeader = '';
				$areaIds = array();
			}
			else
			{
				$tHeader = 'Area: ';
				$areaIds = $resultIds['e2'];
				
				$aDetails = $this->getAreaHeaders($areaIds);
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= strip_tags($value['sectionHeader']);
				}
				unset($aDetails);			
			}
			
			$pDetails = $this->getProductHeaders($productIds, $onlyUpdates);
			foreach($pDetails['Ids'] as $ikey => $ivalue)
			{
				$pDetails['Ids'][$ikey]['area'] = implode("','", $areaIds);
			}
			
			$Ids = $pDetails['Ids'];
			$TrialsInfo = $pDetails['TrialsInfo'];
			$productSelector = $pDetails['productSelector'];
			
			unset($pDetails);
		}
		else
		{
			if(empty($resultIds['e1']) && empty($resultIds['e2']))
			{
				$ottType = 'indexed';
				$tHeader = '';
			}
			else if(empty($resultIds['e1']))
			{
				$ottType = 'rowstacked';
				$tHeader = '';
				
				$areaIds = $resultIds['e2'];

				$aDetails = $this->getAreaHeaders($areaIds);
				foreach($aDetails['Ids'] as $akey => $value)
				{
					$aDetails['Ids'][$akey]['product'] = '';
				}
				
				$Ids = $aDetails['Ids'];
				$TrialsInfo = $aDetails['TrialsInfo'];
				$productSelector = $aDetails['productSelector'];
				
				unset($aDetails);
			}
			else if(empty($resultIds['e2']))
			{
				$ottType = 'indexed';
				$tHeader = '';
				
				$productIds = $resultIds['e1'];
				$pDetails = $this->getProductHeaders($productIds);
				foreach($pDetails['Ids'] as $ikey => $ivalue)
				{
					$pDetails['Ids'][$ikey]['area'] = '';
				}
				
				$Ids = $pDetails['Ids'];
				$TrialsInfo = $pDetails['TrialsInfo'];
				$productSelector = $pDetails['productSelector'];
				
				unset($pDetails);
			}
			else
			{	
				$ottType = 'indexed';
				$tHeader = 'Area: ';
				
				$areaIds = $resultIds['e2'];
				$productIds = $resultIds['e1'];
				
				$aDetails = $this->getAreaHeaders($areaIds);
				foreach($aDetails['TrialsInfo'] as $akey => $value)
				{
					$tHeader .= strip_tags($value['sectionHeader']);
				}
				
				$pDetails = $this->getProductHeaders($productIds, $onlyUpdates);
				foreach($pDetails['Ids'] as $ikey => $ivalue)
				{
					$pDetails['Ids'][$ikey]['area'] = implode("','", $areaIds);
				}
				
				$Ids = $pDetails['Ids'];
				$TrialsInfo = $pDetails['TrialsInfo'];
				$productSelector = $pDetails['productSelector'];
				
				unset($aDetails);
				unset($pDetails);
			}
		}
		
		return array('tHeader' => $tHeader, 'ottType' => $ottType,'Ids' => $Ids, 'TrialsInfo' => $TrialsInfo, 'productSelector' => $productSelector);
	}
	
	function getProductHeaders($productIds)
	{
		global $logger;
		
		$productSelector = array();
		$TrialsInfo = array();
		$Ids = array();
		
		$Query = "SELECT `name`, `id`, `class`,`display_name`,`company`, `discontinuation_status`, `discontinuation_status_comment` "
					. " FROM `entities` WHERE id IN ('" . implode("','", $productIds) . "') OR LI_id IN ('" . implode("','", $productIds) . "') "
					. " ORDER BY FIELD(`id`, " . implode(",", $productIds) . ") ";
		$Res = m_query(__LINE__,$Query);
		
		if($Res)
		{	
			if(mysql_num_rows($Res) > 0)
			{	
				while($row = mysql_fetch_assoc($Res))
				{
					$disContinuedTxt = "";
					$sectionHeader = "";
					
					$productIds[] = $productId = $row['id'];
					
					if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
					{
						$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
					}
					
					if($row['class']=='Product')
					{
						$sectionHeader = formatBrandName($row['name'], 'product');
						$productSelector[$productId] = $row['name'];	
					}
					elseif(!empty($row['display_name']))
					{
						$sectionHeader = formatBrandName($row['display_name'], 'area');
						$productSelector[$productId] = $row['display_name'];
					}
					else
					{
						$sectionHeader = formatBrandName($row['class'].' ' . $areaId, 'area');
						$productSelector[$productId] = $row['class'].' ' . $areaId;
					}
					
					if($row['class']=='Product')	
					$row['company'] = GetCompanyNames($productId);
					
					if($row['company'] !== NULL && $row['company'] != '')
					{
						$sectionHeader .= " / <i>" . $row['company'] . "</i>";
						$productSelector[$productId] .= " / <i>" . $row['company'] . "</i>";
					}
			
					
					$sectionHeader .= $disContinuedTxt;
					
					$TrialsInfo[$productId]['Id'] = $productId;
					$TrialsInfo[$productId]['sectionHeader'] = $sectionHeader;
					
					$Ids[$productId]['product'] = $productId;
					
					unset($disContinuedTxt);
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		return array('Ids' => $Ids, 'TrialsInfo' => $TrialsInfo, 'productSelector' => $productSelector);
	}
	
	function getAreaHeaders($areaIds)
	{
		global $logger;
		
		$productSelector = array();
		$TrialsInfo = array();
		$Ids = array();
		
		$Query = "SELECT `display_name`, `name`, `id`, `class`,`category` FROM `entities` WHERE id IN ('" . implode("','", $areaIds) . "') ";
		$Res = m_query(__LINE__,$Query);
		
		if($Res)
		{
			if(mysql_num_rows($Res) > 0)
			{
				while($row = mysql_fetch_assoc($Res))
				{
					$sectionHeader = "";
					$areaId = $row['id'];
					
					if($row['category'] != '' && $row['category'] !== NULL)
					{
						$sectionHeader = $row['category'];
					}
					if($row['class']=='Product')
					{
						$sectionHeader = formatBrandName($row['name'], 'product');
						$productSelector[$areaId] = $row['name'];	
					}
					elseif(!empty($row['display_name']))
					{
						$sectionHeader = formatBrandName($row['display_name'], 'area');
						$productSelector[$areaId] = $row['display_name'];
					}
					else
					{
						$sectionHeader = formatBrandName($row['class'].' ' . $areaId, 'area');
						$productSelector[$areaId] = $row['class'].' ' . $areaId;
					}
				
					$Ids[$areaId]['area'] = $areaId;
					$productSelector[$areaId] = $sectionHeader;
					
					$TrialsInfo[$areaId]['sectionHeader'] = $sectionHeader;
					$TrialsInfo[$areaId]['Id'] = $areaId;
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		return array('Ids' => $Ids, 'TrialsInfo' => $TrialsInfo, 'productSelector' => $productSelector);
	}
	
	function timeParams($globalOptions)
	{
		if(in_array($globalOptions['startrange'], $globalOptions['Highlight_Range']))
		{
			$timeMachine = str_replace('ago', '', $globalOptions['startrange']);
			$timeMachine = trim($timeMachine);
			$timeMachine = '-' . (($timeMachine == '1 quarter') ? '3 months' : $timeMachine);
		}
		else
		{
			$timeMachine = trim($globalOptions['startrange']);
			$timeMachine = (($timeMachine == '1 quarter') ? '3 months' : $timeMachine);
		}
		$this->timeMachine = strtotime($timeMachine);
		
		if(in_array($globalOptions['endrange'], $globalOptions['Highlight_Range']))
		{
			$timeInterval = str_replace('ago', '', $globalOptions['endrange']);
			$timeInterval = trim($timeInterval);
			$timeInterval = '-' . (($timeInterval == '1 quarter') ? '3 months' : $timeInterval);
		}
		else
		{
			$timeInterval = trim($globalOptions['endrange']);
			$timeInterval = (($timeInterval == '1 quarter') ? '3 months' : $timeInterval);
		}
		$this->timeInterval = $timeInterval;
	}
	
	function processEntitiesParams($resultIds, $globalOptions)
	{
		global $logger;
		
		$ottType = 'entities';
		
		$disContinuedTxt = "";
		$sectionHeader = "";
		$tHeader = 'Area: ';
		
		$TrialsInfo = array();
		
		$ids = implode("','", $resultIds);
		
		$query = "SELECT id, name, class, display_name FROM entities WHERE id IN ('" . $ids . "') ";
		$res = m_query(__LINE__, $query);
		
		if($res)
		{
			while($row = mysql_fetch_assoc($res))
			{
				$id = $row['id'];
				if($row['class'] == 'Product')
				{
					$pId = $id;
					$sectionHeader = formatBrandName($row['name'], 'product');
				
					$row['company'] = GetCompanyNames($pId);
					
					if($row['company'] !== NULL && $row['company'] != '')
					{
						$sectionHeader .= " / <i>" . $row['company'] . "</i>";
					}
					if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
					{
						$sectionHeader .= " <span style='color:gray'>Discontinued</span>";
					}
					
					$TrialsInfo[$id]['Id'] = $id;
					$TrialsInfo[$id]['sectionHeader'] = $sectionHeader;
				}
				else
				{
					if($row['display_name'] != '' && $row['display_name'] !== NULL)
					{
						$tHeader .= $row['display_name'];
					}
					else
					{
						$tHeader .= $id;
					}
				}
			}
		}
		else 
		{
			$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		return array('tHeader' => $tHeader, 'ottType' => $ottType, 'pId' => $pId, 'TrialsInfo' => $TrialsInfo);
	}
	
	function compileEntitiesData($pId, $TrialsInfo = array(), $Ids = array(), $globalOptions = array(), $display = 'web')
	{
		global $logger;
		
		$Values = array();
		$Values['Data'] = array();
		$Values['activecount'] = 0;
		$Values['inactivecount'] = 0;
		$Values['totalcount'] = 0;
		$Values['count'] = 0;
		
		$naUpms = array();
		$larvolIds = array();
		
		$startRange = date('Y-m-d', strtotime($this->timeInterval, $this->timeMachine));
		$endRange = date('Y-m-d', $this->timeMachine);
		
		$previousValue = 'Previous value: ';	
		$noPreviousValue = 'No previous value';	
		
		$lstart = ($globalOptions['page']-1) * $this->resultsPerPage;
		$limit = " LIMIT " . $lstart . ", 100 ";
		$Pids='';$Aids='';
		foreach($Ids as $key=>$value) 
		{
			$Pids .= ",".$Ids[$key]['product'];
			$Aids .= ",".$Ids[$key]['area'];
		}
		$Pids=substr($Pids,1); $Aids=substr($Aids,1);
		$where = " WHERE 1 ";
		$join = "";
		
		$join .= " JOIN `entity_trials` et ON dt.`larvol_id` = et.`trial` AND et.`entity` IN (" . $Pids . ") ";
		$join .= " JOIN `entity_trials` et2 ON dt.`larvol_id` = et2.`trial` AND et2.`entity` IN (" . $Aids . ") ";
				
		$join .= " LEFT OUTER JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id` "
					. " LEFT OUTER JOIN `data_history` dh ON dh.`larvol_id` = dt.`larvol_id` ";
		
		$query = "SELECT SQL_CALC_FOUND_ROWS dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`,"
					. " dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`intervention_name`,"
					. " dt.`region`, dt.`phase`, dt.`firstreceived_date`, dt.`viewcount`, dt.`source`, "
					. " dm.`is_sourceless` AS manual_is_sourceless, dm.`brief_title` AS manual_brief_title, dm.`acronym` AS manual_acronym, "
					. " dm.`lead_sponsor` AS manual_lead_sponsor, dm.`collaborator` AS manual_collaborator,"
					. " dm.`condition` AS manual_condition, dm.`overall_status` AS manual_overall_status, dm.`region` AS manual_region,"
					. " dm.`start_date` AS manual_start_date, dm.`end_date` AS manual_end_date, dm.`enrollment` AS manual_enrollment, "
					. " dm.`intervention_name` AS manual_intervention_name, dm.`phase` AS manual_phase, dh.`brief_title_prev`, "
					. " dh.`end_date_prev`, dh.`lead_sponsor_prev`, dh.`collaborator_prev`, dh.`condition_prev`, dh.`overall_status_prev`, "
					. " dh.`start_date_prev`, dh.`enrollment_prev`, dh.`intervention_name_prev`, dh.`phase_prev`, dh.`region_prev`, dh.`brief_title_lastchanged`, "
					. " dh.`end_date_lastchanged`, dh.`lead_sponsor_lastchanged`, dh.`collaborator_lastchanged`, dh.`condition_lastchanged`, "
					. " dh.`overall_status_lastchanged`, dh.`start_date_lastchanged`, dh.`enrollment_lastchanged`, dh.`intervention_name_lastchanged`, "
					. " dh.`phase_lastchanged`, dh.`region_lastchanged` "
					. " FROM `data_trials` dt ";
					
		//calculating count value only for webpage display and not for file exports				
		if($display == 'web')
		{
			$tQuery = "SELECT COUNT(*) AS totalcount "
						. " FROM `data_trials` dt ";
			$tQuery .=  $join . $where;
			$tRes = m_query(__LINE__, $tQuery);
			if($tRes)
			{
				$tRow = mysql_fetch_assoc($tRes);
				$Values['totalcount'] = $tRow['totalcount'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $tQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			$aQuery = "SELECT COUNT(*) AS activecount "
						. " FROM `data_trials` dt ";
			$aQuery .=  $join . $where . " AND dt.`is_active` = 1 ";
			$aRes = m_query(__LINE__, $aQuery);
			if($aRes)
			{
				$aRow = mysql_fetch_assoc($aRes);
				$Values['activecount'] = $aRow['activecount'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $aQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			$Values['inactivecount'] = $Values['totalcount'] - $Values['activecount'];
		}
		
		//Filtering Options
		$filters = $this->getActiveFilters($globalOptions);
		
		if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinxSearch']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if(isset($globalOptions['sphinx_s']))
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinx_s']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if($globalOptions['onlyUpdates'] == "yes")
		{
			$where .= " AND ((dt.`firstreceived_date` BETWEEN '" . $startRange . "' AND '" . $endRange . "') OR (`" . implode('` BETWEEN "' . $startRange . '" AND "' . $endRange . '") OR (`', $this->fieldNames) . "` BETWEEN '" . $startRange . "' AND '" . $endRange . "') )";
		}
		
		$Query = $query . $join . $where;
		
		//limit clause for pagination in webpage display and unsetting section headers which are not required in each page
		if($display == 'web')
		{
			$Query .= $filters . $orderBy . $limit;
		}
		else//without limit clause for file exports
		{
			if($globalOptions['dOption'] == 'all')
			{
				$Query .= $orderBy;
			}
			else
			{
				$Query .= $filters . $orderBy;
			}
		}
		
		$Values['Data'] = $TrialsInfo;
		unset($TrialsInfo);
		
		//fetching unmatched upms
		$naUpms = $this->getUnMatchedUpms($globalOptions, array($pId), 'entities');
		foreach($naUpms as $nkey => $nvalue)
		{
			$Values['Data'][$pId]['naUpms'] = $nvalue;
		}
		
		$res = m_query(__LINE__,$Query);
		
		if($res)
		{
			if(mysql_num_rows($res) > 0)
			{
				while($row = mysql_fetch_assoc($res))
				{	
					$result = array();
					
					$larvolIds[] = $larvolId = $row['larvol_id'];
				
					if(substr($row['source_id'], 0, 3) == "NCT")
					{ 
						$nctId = unpadnct(substr($row['source_id'], 0, 11));
					}
					else
					{
						$nctId = $row['source_id'];
					}
					
					$result['sectionid'] 	= $pId;
					
					$result['larvol_id'] 	= $larvolId;
					$result['nct_id'] 		= $nctId;
					
					if(strlen(trim($row['source_id'])) > 15)
					{
						$result['full_id'] 	= $row['source_id'];
					}
					else
					{
						$result['full_id'] 	= $nctId;
					}
					$result['id_for_upm'] 	= $row['source_id'];
					
					$result['brief_title'] 	= $row['brief_title'];
					$result['acronym'] 		= $row['acronym'];
					
					if($row['acronym'] != '') 
					{
						$result['brief_title'] = $this->replaceRedundantAcroynm($row['acronym'], $row['brief_title']);
					}
					
					$result['region'] 			= $row['region'];
					$result['lead_sponsor'] 	= str_replace('`', ', ', $row['lead_sponsor']);
					$result['start_date'] 		= $row['start_date'];
					$result['end_date'] 		= $row['end_date'];
					$result['phase'] 			= $row['phase'];
					$result['enrollment'] 		= $row['enrollment'];
					$result['collaborator'] 	= str_replace('`', ', ', $row['collaborator']);
					$result['condition'] 		= str_replace('`', ', ', $row['condition']);
					$result['intervention_name']= str_replace('`', ', ', $row['intervention_name']);
					$result['overall_status'] 	= $row['overall_status'];
					$result['is_active'] 		= $row['is_active'];
					$result['new'] 				= 'n';
					
					$result['viewcount'] 			= $row['viewcount']; 
					$result['source'] 				= $row['source']; 
					$result['source_id'] 			= $row['source_id']; 
					$result['sponsor_owned'] 		= $row['sponsor_owned'];
					
					$result['manual_brief_title'] 		= $row['manual_brief_title']; 
					$result['manual_acronym'] 			= $row['manual_acronym']; 
					$result['manual_lead_sponsor'] 		= $row['manual_lead_sponsor']; 
					$result['manual_collaborator'] 		= $row['manual_collaborator']; 
					$result['manual_condition'] 		= $row['manual_condition']; 
					$result['manual_overall_status']	= $row['manual_overall_status']; 
					$result['manual_start_date'] 		= $row['manual_start_date']; 
					$result['manual_end_date'] 			= $row['manual_end_date']; 
					$result['manual_enrollment'] 		= $row['manual_enrollment']; 
					$result['manual_intervention_name'] = $row['manual_intervention_name']; 
					$result['manual_phase'] 			= $row['manual_phase'];
					$result['manual_region'] 			= $row['manual_region'];
					$result['manual_is_sourceless'] 	= $row['manual_is_sourceless'];
					
					$result['end_date_prev'] 		= $row['end_date_prev']; 
					$result['lead_sponsor_prev'] 	= $row['lead_sponsor_prev']; 
					$result['brief_title_prev'] 	= $row['brief_title_prev']; 
					$result['collaborator_prev'] 	= $row['collaborator_prev']; 
					$result['condition_prev'] 		= $row['condition_prev']; 
					$result['overall_status_prev'] 	= $row['overall_status_prev']; 
					$result['start_date_prev']		= $row['start_date_prev']; 
					$result['enrollment_prev'] 		= $row['enrollment_prev']; 
					$result['intervention_name_prev'] = $row['intervention_name_prev']; 
					$result['phase_prev'] 		= $row['phase_prev']; 
					$result['region_prev'] 		= $row['region_prev']; 
					
					if($row['start_date_lastchanged'] <= $endRange && $row['start_date_lastchanged'] >= $startRange)
					{
						if($row['start_date_prev'] != '' && $row['start_date_prev'] !== NULL)
						{
							$result['edited']['start_date'] = $previousValue . $row['start_date_prev'];
						}
						else
						{
							$result['edited']['start_date'] = $noPreviousValue;
						}
					}
					
					if($row['end_date_lastchanged'] <= $endRange && $row['end_date_lastchanged'] >= $startRange)
					{
						if($row['end_date_prev'] != '' && $row['end_date_prev'] !== NULL)
						{
							$result['edited']['end_date'] = $previousValue . $row['end_date_prev'];
						}
						else
						{
							$result['edited']['end_date'] = $noPreviousValue;
						}
					}
					
					if($row['region_lastchanged'] <= $endRange && $row['region_lastchanged'] >= $startRange)
					{
						if($row['region_prev'] != '' && $row['region_prev'] !== NULL)
						{
							$result['edited']['region'] = $previousValue . $row['region_prev'];
						}
						else
						{
							$result['edited']['region'] = $noPreviousValue;
						}
					}
					
					if($row['brief_title_lastchanged'] <= $endRange && $row['brief_title_lastchanged'] >= $startRange)
					{
						if($row['brief_title_prev'] != '' && $row['brief_title_prev'] !== NULL)
						{
							$result['edited']['brief_title'] = $previousValue . $row['brief_title_prev'];
						}
						else
						{
							$result['edited']['brief_title'] = $noPreviousValue;
						}
					}
					
					if($row['lead_sponsor_lastchanged'] <= $endRange && $row['lead_sponsor_lastchanged'] >= $startRange)
					{
						if($row['lead_sponsor_prev'] != '' && $row['lead_sponsor_prev'] !== NULL)
						{
							$row['lead_sponsor_prev'] 	= str_replace('`', ', ', $row['lead_sponsor_prev']);
							$result['edited']['lead_sponsor'] = $previousValue . $row['lead_sponsor_prev'];
						}
						else
						{
							$result['edited']['lead_sponsor'] = $noPreviousValue;
						}
					}
					
					if($row['phase_lastchanged'] <= $endRange && $row['phase_lastchanged'] >= $startRange)
					{
						if($row['phase_prev'] != '' && $row['phase_prev'] !== NULL)
						{
							$result['edited']['phase'] = $previousValue . $row['phase_prev'];
						}
						else
	
						{
							$result['edited']['phase'] = $noPreviousValue;
						}
					}
					
					if($row['enrollment_lastchanged'] <= $endRange && $row['enrollment_lastchanged'] >= $startRange)
					{
						if($row['enrollment_prev'] != '' && $row['enrollment_prev'] !== NULL)
						{
							$result['edited']['enrollment'] = $previousValue . $row['enrollment_prev'];
						}
						else
						{
							$result['edited']['enrollment'] = $noPreviousValue;
						}
					}
					
					if($row['collaborator_lastchanged'] <= $endRange && $row['collaborator_lastchanged'] >= $startRange)
					{
						if($row['collaborator_prev'] != '' && $row['collaborator_prev'] !== NULL)
						{
							$row['collaborator_prev'] = str_replace('`', ', ', $row['collaborator_prev']);
							$result['edited']['collaborator'] = $previousValue . $row['collaborator_prev'];
						}
						else
						{
							$result['edited']['collaborator'] = $noPreviousValue;
						}
					}
					
					if($row['condition_lastchanged'] <= $endRange && $row['condition_lastchanged'] >= $startRange)
					{
						if($row['condition_prev'] != '' && $row['condition_prev'] !== NULL)
						{
							$row['condition_prev'] = str_replace('`', ', ', $row['condition_prev']);
							$result['edited']['condition'] = $previousValue . $row['condition_prev'];
						}
						else
						{
							$result['edited']['condition'] = $noPreviousValue;
						}
					}
					
					if($row['intervention_name_lastchanged'] <= $endRange && $row['intervention_name_lastchanged'] >= $startRange)
					{
						if($row['intervention_name_prev'] != '' && $row['intervention_name_prev'] !== NULL)
						{
							$row['intervention_name_prev'] = str_replace('`', ', ', $row['intervention_name_prev']);
							$result['edited']['intervention_name'] = $previousValue . $row['intervention_name_prev'];
						}
						else
						{
							$result['edited']['intervention_name'] = $noPreviousValue;
						}
					}
					
					if($row['overall_status_lastchanged'] <= $endRange && $row['overall_status_lastchanged'] >= $startRange)
					{
						if($row['overall_status_prev'] != '' && $row['overall_status_prev'] !== NULL)
						{
							$row['overall_status_prev'] = str_replace('`', ', ', $row['overall_status_prev']);
							$result['edited']['overall_status'] = $previousValue . $row['overall_status_prev'];
						}
						else
						{
							$result['edited']['overall_status'] = $noPreviousValue;
						}
					}
				
					if($row['firstreceived_date'] <= $endRange && $row['firstreceived_date'] >= $startRange)
					{
						$result['new'] = 'y';
					}
					
					$Values['Data'][$pId]['Trials'][] = $result;
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		//fetching active count
		$cQuery = "SELECT FOUND_ROWS() AS total";
		$cRes = m_query(__LINE__, $cQuery);
		if($cRes)
		{
			$cRow = mysql_fetch_assoc($cRes);
			$Values['count'] = $cRow['total'];
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $cQuery . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		//fetching matched upms
		$dataUpms = $this->getMatchedUpms($globalOptions, $larvolIds, 'entities');
		foreach($Values['Data'] as $dkey => & $dvalue)
		{
			$Id = $dvalue['Id'];
			if(isset($dvalue['Trials']))
			{
				foreach($dvalue['Trials'] as $tkey => & $tvalue)
				{
					if(isset($dataUpms[$tkey]))
					{
						$tvalue['upms'] = $dataUpms[$tkey];
					}
				}
			}
		}
		unset($dataUpms);
					
		return  $Values;
	}
	
	function generateEntitiesTT($resultIds, $globalOptions = array())
	{	
		$Values = array();
		$Values['Data'] = array();
		$Values['activecount'] = 0;
		$Values['inactivecount'] = 0;
		$Values['totalcount'] = 0;
		$Values['count'] = 0;
		
		$productSelectorTitle = 'Select Products';
		$productSelector = array();
		
		$this->timeParams($globalOptions);
		
		echo '<form id="frmOtt" name="frmOtt" method="get" target="_self" action="intermediary.php">'
				.'<input type="hidden" name="e1" value="' . $resultIds['e1'] . '" />'
				. '<input type="hidden" name="e2" value="' . $resultIds['e2'] . '" />';
		
		$resultIds['e1'] = explode(',', trim($resultIds['e1']));
		$resultIds['e2'] = explode(',', trim($resultIds['e2']));
		
		$resultIds['e1'] = array_filter($resultIds['e1']);
		$resultIds['e2'] = array_filter($resultIds['e2']);
			
		if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '')
		{
			echo '<input type="hidden" name="hm" value="' . $globalOptions['hm'] . '" />';
			$Arr = $this->processHmParams($resultIds, $globalOptions, 'webPage');
		}
		else
		{
			$Arr = $this->processNonHmParams($resultIds, $globalOptions, 'webPage');
		}
		
		$this->displayHeader($Arr['tHeader']);
			
		$ottType = $Arr['ottType'];
		$productSelector = $Arr['productSelector'];
		$Ids = $Arr['Ids'];
		$TrialsInfo = $Arr['TrialsInfo'];
		
		if(isset($globalOptions['JSON_search']))
		{
			echo '<input type="hidden" name="JSON_search" value=\'' . $globalOptions['JSON_search'] . '\' />';
			$Values = $this->compileJsonData($globalOptions);
		}
		else
		{
			$Values = $this->compileOTTData2($ottType, $TrialsInfo, $Ids, $globalOptions);
		}
		
		echo $this->displayWebPage($ottType, $resultIds, $Values, $productSelector, $globalOptions);
		
		unset($ottType, $productSelector, $Ids, $TrialsInfo, $Arr);
	}
	
	
	function generateOnlineTT($resultIds, $globalOptions = array())
	{	
		$Values = array();
		$Values['Data'] = array();
		$Values['activecount'] = 0;
		$Values['inactivecount'] = 0;
		$Values['totalcount'] = 0;
		$Values['count'] = 0;
		
		$productSelectorTitle = 'Select Products';
		$productSelector = array();
		
		$this->timeParams($globalOptions);
		
		echo '<form id="frmOtt" name="frmOtt" method="get" target="_self" action="intermediary.php">'
				.'<input type="hidden" name="p" value="' . $resultIds['product'] . '" />'
				. '<input type="hidden" name="a" value="' . $resultIds['area'] . '" />';
		
		$resultIds['product'] = explode(',', trim($resultIds['product']));
		$resultIds['area'] = explode(',', trim($resultIds['area']));
		
		$resultIds['product'] = array_filter($resultIds['product']);
		//$resultIds['product'] = array_unique($resultIds['product']);
			
		$resultIds['area'] = array_filter($resultIds['area']);
			
		if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '')
		{
			echo '<input type="hidden" name="hm" value="' . $globalOptions['hm'] . '" />';
			$Arr = $this->processHmParams($resultIds, $globalOptions, 'webPage');
		}
		else
		{
			$Arr = $this->processNonHmParams($resultIds, $globalOptions, 'webPage');
		}
		
		$this->displayHeader($Arr['tHeader']);
			
		$ottType = $Arr['ottType'];
		$productSelector = $Arr['productSelector'];
		$Ids = $Arr['Ids'];
		$TrialsInfo = $Arr['TrialsInfo'];
		
		if(isset($globalOptions['JSON_search']))
		{
			echo '<input type="hidden" name="JSON_search" value=\'' . $globalOptions['JSON_search'] . '\' />';
			$Values = $this->compileJsonData($globalOptions);
		}
		else
		{
			$Values = $this->compileOTTData($ottType, $TrialsInfo, $Ids, $globalOptions);
		}
		
		echo $this->displayWebPage($ottType, $resultIds, $Values, $productSelector, $globalOptions);
		
		unset($ottType, $productSelector, $Ids, $TrialsInfo, $Arr);
	}
	
	//Filtering Options
	function getActiveFilters($globalOptions = array())
	{
		global $maxEnrollLimit;
		
		$filters = " ";
		if(isset($globalOptions['status']) && !empty($globalOptions['status'])) 
		{
			$status = array();
			foreach($globalOptions['status'] as $skey => $svalue)
			{
				$status[] = $this->statusFilters[$svalue];
			}
			
			$filters .= " AND (dt.`overall_status` IN ('"  . implode("','", $status) . "') )";
			unset($status);
		}
		
		if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
		{
			$itype = array();
			foreach($globalOptions['itype'] as $ikey => $ivalue)
			{
				$itype[] = $this->institutionFilters[$ivalue];
			}
			
			$filters .= " AND (dt.`institution_type` IN ('"  . implode("','", $itype) . "') )";
			unset($itype);
		}
		
		if(isset($globalOptions['region']) && !empty($globalOptions['region'])) 
		{
			$region = array();
			$filters .= " AND (";
			foreach($globalOptions['region'] as $rkey => $rvalue)
			{
				$r = $this->regionFilters[$rvalue];
				if($r == 'RestOfWorld')
					$region[] = " (dt.`region` = '" . $this->regionFilters[$rvalue] . "' OR  dt.`region` = 'RoW') ";
				else
					$region[] = " (dt.`region` = '" . $this->regionFilters[$rvalue] . "' ) ";
			}
			$filters .= implode(' OR ', $region);
			$filters .= " ) ";
			unset($region);
		}
		
		if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
		{
			$phase = array();
			foreach($globalOptions['phase'] as $pkey => $pvalue)
			{
				$ph = array_keys($this->phaseFilters, $pvalue);
				$phase = array_merge($phase, $ph);
			}
			
			$filters .= " AND (dt.`phase` IN ('"  . implode("','", $phase) . "') )";
			unset($phase);
		}
		
		if($globalOptions['type'] == 'activeTrials') 
		{
			$filters .= " AND (dt.`is_active` = 1) ";
		}
		else if($globalOptions['type'] == 'inactiveTrials') 
		{
			$filters .= " AND (dt.`is_active` != 1) ";
		}
		
		if($globalOptions['enroll'] != '0' && $globalOptions['enroll'] != ('0-'.$maxEnrollLimit))
		{
			$enroll = explode('-', $globalOptions['enroll']);
			
			if($enroll[0] == '0')
			{
				$filters .= " AND (dt.`enrollment` <= '" . $enroll[1] . "' ) " ;
			}
			else if($enroll[1] == $maxEnrollLimit)
			{
				$filters .= " AND (dt.`enrollment` >= '" . $enroll[0] . "') " ;
			}
			else
			{
				$filters .= " AND (dt.`enrollment` >= '" . $enroll[0] . "' AND dt.`enrollment` <= '" . $enroll[1] . "' ) " ;
			}
		}
		
		return $filters;
	}
	
	function compileJsonData($globalOptions = array(), $display = 'web')
	{	
		global $logger;
		
		$startRange = date('Y-m-d', strtotime($this->timeInterval, $this->timeMachine));
		$endRange = date('Y-m-d', $this->timeMachine);
		
		$lstart = ($globalOptions['page']-1) * $this->resultsPerPage;
		$limit = " LIMIT " . $lstart . ", 100 ";
		
		$Values['Data'] = array();
		$Values['activecount'] = 0;
		$Values['inactivecount'] = 0;
		$Values['totalcount'] = 0;
		$Values['count'] = 0;
		
		$Query = "";
		
		$where = " WHERE 1 ";
		if($globalOptions['onlyUpdates'] == "yes")
		{
			$where .= " AND ((dt.`firstreceived_date` BETWEEN '" . $startRange . "' AND '" . $endRange . "') OR (`" . implode('` BETWEEN "' . $startRange . '" AND "' . $endRange . '") OR (`', $this->fieldNames) . "` BETWEEN '" . $startRange . "' AND '" . $endRange . "') )";
		}
		
		if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinxSearch']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if(isset($globalOptions['sphinx_s']))
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinx_s']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		$filters = $this->getActiveFilters($globalOptions);
		
		try 
		{
			$jsonData = $globalOptions['JSON_search'];
			$filterData = json_decode($jsonData, true, 10);
			
			if(is_array($filterData))
			{
				array_walk_recursive($filterData, 'searchHandlerBackTicker','columnname');
				array_walk_recursive($filterData['columndata'], 'searchHandlerBackTicker','columnas');
			}
			
			$where_datas = $filterData["wheredata"];
			$select_columns=$filterData["columndata"];
			$override_vals = trim($filterData["override"]);
			$sort_datas = $filterData["sortdata"];
			$isOverride = !empty($override_vals);
			
			unset($filterData);
			foreach($sort_datas as $ky => $vl )
			{
				if($vl["columnname"] == '`All`')
				unset($sort_datas[$ky]);
			}
			
			$prod_flag=0; $area_flag=0; $prod_col=0; $area_col=0;
			if(is_array($where_datas) && !empty($where_datas))
			{
				foreach($where_datas as $where_data)
				{
					if($where_data["columnname"] == '`product`')
					$prod_flag=1;
					if($where_data["columnname"] == '`area`')
					$area_flag=1;
				}
			}
			
			if(is_array($select_columns) && !empty($select_columns))
			{
				foreach($select_columns as $selectcolumn)
				{
					if($selectcolumn["columnname"] == '`product`')
					{
						$prod_flag=1;
						$prod_col=1;	//This will need in overrriding Query
					}
					if($selectcolumn["columnname"] == '`area`')
					{
						$area_flag=1;
						$area_col=1;	//This will need in overrriding Query
					}
				}
			}
			
			if(is_array($sort_datas) && !empty($sort_datas) && (!$prod_flag || !$area_flag))
			{
				foreach($sort_datas as $sort_column)
				{
					if($sort_column["columnas"] == '`product`')
					$prod_flag=1;
					if($sort_column["columnas"] == '`area`')
					$area_flag=1;
				}
			}
			
			$from = " FROM data_trials dt";
			
			if($prod_flag)
			{
				$from .= " JOIN product_trials pt ON pt.`trial`= dt.`larvol_id`";
			}
			if($area_flag)
			{
				$from .= " JOIN area_trials at ON at.`trial`= dt.`larvol_id`";
			}
			
			$from .= " LEFT OUTER JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id` "
					. " LEFT OUTER JOIN `data_history` dh ON dh.`larvol_id` = dt.`larvol_id` ";
					
			if($isOverride)
			{
				$override_str_arr = explode(',', $override_vals);
				$override_str_arr = array_map(function($v){
					$v = trim($v);
					return "'".padnct($v)."' ";
				}, $override_str_arr);
					
				$Query .= "(";
			}
			
			$select = " dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`,"
						. " dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`intervention_name`,"
						. " dt.`region`, dt.`phase`, dt.`firstreceived_date`, dt.`viewcount`, dt.`source`, "
						. " dm.`is_sourceless` AS manual_is_sourceless, dm.`brief_title` AS manual_brief_title, dm.`acronym` AS manual_acronym, "
						. " dm.`lead_sponsor` AS manual_lead_sponsor, dm.`collaborator` AS manual_collaborator,"
						. " dm.`condition` AS manual_condition, dm.`overall_status` AS manual_overall_status, dm.`region` AS manual_region,"
						. " dm.`start_date` AS manual_start_date, dm.`end_date` AS manual_end_date, dm.`enrollment` AS manual_enrollment, "
						. " dm.`intervention_name` AS manual_intervention_name, dm.`phase` AS manual_phase, dh.`brief_title_prev`, "
						. " dh.`end_date_prev`, dh.`lead_sponsor_prev`, dh.`collaborator_prev`, dh.`condition_prev`, dh.`overall_status_prev`, "
						. " dh.`start_date_prev`, dh.`enrollment_prev`, dh.`intervention_name_prev`, dh.`phase_prev`, dh.`region_prev`, dh.`brief_title_lastchanged`, "
						. " dh.`end_date_lastchanged`, dh.`lead_sponsor_lastchanged`, dh.`collaborator_lastchanged`, dh.`condition_lastchanged`, "
						. " dh.`overall_status_lastchanged`, dh.`start_date_lastchanged`, dh.`enrollment_lastchanged`, dh.`intervention_name_lastchanged`, "
						. " dh.`phase_lastchanged`, dh.`region_lastchanged` ";
						
			$Query .= " SELECT SQL_CALC_FOUND_ROWS " . $select . $from . $where;
			
			$where_str = get_WhereString($where_datas, 'dt', 'pt', 'at');
			$sort_str = getSortString($sort_datas, $alias, $pd_alias, $ar_alias);
			
			if(strlen(trim($where_str)) != 0 || strlen($filters) != 0)
			{
				if(strlen($filters) != 0) 
				{
					if($display == 'web')
					{
						$Query .= $filters;
					}
					else
					{
						if($globalOptions['type'] != 'allTrials')
						{
							$Query .= $filters;
						}
					}
				}
				if(strlen(trim($where_str)) != 0 && strlen($filters) != 0) $Query .= " AND ";
				if(strlen(trim($where_str)) != 0) $Query .= $where_str;
			}
			
			$orderBy = " ORDER BY ";
			if((strlen(trim($sort_str)) != 0))//Sort
			{
				$orderBy .= $sort_str;
			}
			else
			{
				$orderBy .= "dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
			}
	
			$Query .= $orderBy;
			
			if($isOverride)//override string present
			{
				$Query .= ") UNION ALL (SELECT " . $select . $from . $where;
				if($display == 'web')
				{
					$Query .= $filters;
				}
				else
				{
					if($globalOptions['dOption'] == 'all')
					{
						$Query .= $filters;
					}
				}
				$Query .= " AND LEFT(dt.source_id,11) IN (" . implode(',', $override_str_arr) . "))";
			}
			
			
			if($display == 'web')
			{
				$tQuery = "SELECT COUNT(*) AS totalcount " . $from . $where . " AND " . $where_str;
				$aQuery = "SELECT COUNT(*) AS activecount " . $from . $where . " AND dt.`is_active` = 1 AND " . $where_str;
				
				if($isOverride)//override string present
				{
					$tQuery .= " UNION SELECT COUNT(*) AS totalcount " . $from . $where . " AND LEFT(dt.source_id,11) IN (" . implode(',', $override_str_arr) . ")";
					$aQuery .= " UNION SELECT COUNT(*) AS activecount " . $from . $where . " AND dt.`is_active` = 1 AND LEFT(dt.source_id,11) IN (" . implode(',',$override_str_arr) . ")";
				}
				
				$tRes = m_query(__LINE__,$tQuery);
				if($tRes)
				{
					$tRow = mysql_fetch_assoc($tRes);
					$Values['totalcount'] = $tRow['totalcount'];
				}
				else
				{
					$log 	= 'ERROR: Bad SQL query. ' . $tQuery . mysql_error();
					$logger->error($log);
					unset($log);
				}
				
				$aRes = m_query(__LINE__, $aQuery);
				if($aRes)
				{
					$aRow = mysql_fetch_assoc($aRes);
					$Values['activecount'] = $aRow['activecount'];
				}
				else
				{
					$log 	= 'ERROR: Bad SQL query. ' . $aQuery . mysql_error();
					$logger->error($log);
					unset($log);
				}
				$Values['inactivecount'] = $Values['totalcount'] - $Values['activecount'];
				
				$Query .= $limit;
			}
			
			$res = m_query(__LINE__,$Query);
			
			if($res)
			{
				if(mysql_num_rows($res) > 0)
				{
					while($row = mysql_fetch_assoc($res))
					{	
						$result = array();
						
						$larvolIds[] = $larvolId = $row['larvol_id'];
					
						if(substr($row['source_id'], 0, 3) == "NCT")
						{ 
							$nctId = unpadnct(substr($row['source_id'], 0, 11));
						}
						else
						{
							$nctId = $row['source_id'];
						}
						
						$result['sectionid'] = 0;
						
						$result['larvol_id'] 	= $larvolId;
						$result['nct_id'] 		= $nctId;
						if(strlen(trim($row['source_id'])) > 15)
						{
							$result['full_id'] 	= $row['source_id'];
						}
						else
						{
							$result['full_id'] 	= $nctId;
						}
						$result['id_for_upm'] 	= $row['source_id'];
						
						$result['brief_title'] 	= $row['brief_title'];
						$result['acronym'] 		= $row['acronym'];
						
						if($row['acronym'] != '') 
						{
							$result['brief_title'] = $this->replaceRedundantAcroynm($row['acronym'], $row['brief_title']);
						}
						
						$result['region'] 			= $row['region'];
						$result['lead_sponsor'] 	= str_replace('`', ', ', $row['lead_sponsor']);
						$result['start_date'] 		= $row['start_date'];
						$result['end_date'] 		= $row['end_date'];
						$result['phase'] 			= $row['phase'];
						$result['enrollment'] 		= $row['enrollment'];
						$result['collaborator'] 	= str_replace('`', ', ', $row['collaborator']);
						$result['condition'] 		= str_replace('`', ', ', $row['condition']);
						$result['intervention_name']= str_replace('`', ', ', $row['intervention_name']);
						$result['overall_status'] 	= $row['overall_status'];
						$result['is_active'] 		= $row['is_active'];
						$result['new'] 				= 'n';
						
						$result['viewcount'] 			= $row['viewcount']; 
						$result['source'] 				= $row['source']; 
						$result['source_id'] 			= $row['source_id']; 
						$result['sponsor_owned'] 		= $row['sponsor_owned'];
						
						$result['manual_brief_title'] 		= $row['manual_brief_title']; 
						$result['manual_acronym'] 			= $row['manual_acronym']; 
						$result['manual_lead_sponsor'] 		= $row['manual_lead_sponsor']; 
						$result['manual_collaborator'] 		= $row['manual_collaborator']; 
						$result['manual_condition'] 		= $row['manual_condition']; 
						$result['manual_overall_status']	= $row['manual_overall_status']; 
						$result['manual_start_date'] 		= $row['manual_start_date']; 
						$result['manual_end_date'] 			= $row['manual_end_date']; 
						$result['manual_enrollment'] 		= $row['manual_enrollment']; 
						$result['manual_intervention_name'] = $row['manual_intervention_name']; 
						$result['manual_phase'] 			= $row['manual_phase'];
						$result['manual_region'] 			= $row['manual_region'];
						$result['manual_is_sourceless'] 	= $row['manual_is_sourceless'];
						
						$result['end_date_prev'] 		= $row['end_date_prev']; 
						$result['lead_sponsor_prev'] 	= $row['lead_sponsor_prev']; 
						$result['brief_title_prev'] 	= $row['brief_title_prev']; 
						$result['collaborator_prev'] 	= $row['collaborator_prev']; 
						$result['condition_prev'] 		= $row['condition_prev']; 
						$result['overall_status_prev'] 	= $row['overall_status_prev']; 
						$result['start_date_prev']		= $row['start_date_prev']; 
						$result['enrollment_prev'] 		= $row['enrollment_prev']; 
						$result['intervention_name_prev'] = $row['intervention_name_prev']; 
						$result['phase_prev'] 		= $row['phase_prev']; 
						$result['region_prev'] 		= $row['region_prev']; 
						
						if($row['start_date_lastchanged'] <= $endRange && $row['start_date_lastchanged'] >= $startRange)
						{
							if($row['start_date_prev'] != '' && $row['start_date_prev'] !== NULL)
							{
								$result['edited']['start_date'] = $previousValue . $row['start_date_prev'];
							}
							else
							{
								$result['edited']['start_date'] = $noPreviousValue;
							}
						}
						
						if($row['end_date_lastchanged'] <= $endRange && $row['end_date_lastchanged'] >= $startRange)
						{
							if($row['end_date_prev'] != '' && $row['end_date_prev'] !== NULL)
							{
								$result['edited']['end_date'] = $previousValue . $row['end_date_prev'];
							}
							else
							{
								$result['edited']['end_date'] = $noPreviousValue;
							}
						}
						
						if($row['region_lastchanged'] <= $endRange && $row['region_lastchanged'] >= $startRange)
						{
							if($row['region_prev'] != '' && $row['region_prev'] !== NULL)
							{
								$result['edited']['region'] = $previousValue . $row['region_prev'];
							}
							else
							{
								$result['edited']['region'] = $noPreviousValue;
							}
						}
						
						if($row['brief_title_lastchanged'] <= $endRange && $row['brief_title_lastchanged'] >= $startRange)
						{
							if($row['brief_title_prev'] != '' && $row['brief_title_prev'] !== NULL)
							{
								$result['edited']['brief_title'] = $previousValue . $row['brief_title_prev'];
							}
							else
							{
								$result['edited']['brief_title'] = $noPreviousValue;
							}
						}
						
						if($row['lead_sponsor_lastchanged'] <= $endRange && $row['lead_sponsor_lastchanged'] >= $startRange)
						{
							if($row['lead_sponsor_prev'] != '' && $row['lead_sponsor_prev'] !== NULL)
							{
								$row['lead_sponsor_prev'] 	= str_replace('`', ', ', $row['lead_sponsor_prev']);
								$result['edited']['lead_sponsor'] = $previousValue . $row['lead_sponsor_prev'];
							}
							else
							{
								$result['edited']['lead_sponsor'] = $noPreviousValue;
							}
						}
						
						if($row['phase_lastchanged'] <= $endRange && $row['phase_lastchanged'] >= $startRange)
						{
							if($row['phase_prev'] != '' && $row['phase_prev'] !== NULL)
							{
								$result['edited']['phase'] = $previousValue . $row['phase_prev'];
							}
							else
		
							{
								$result['edited']['phase'] = $noPreviousValue;
							}
						}
						
						if($row['enrollment_lastchanged'] <= $endRange && $row['enrollment_lastchanged'] >= $startRange)
						{
							if($row['enrollment_prev'] != '' && $row['enrollment_prev'] !== NULL)
							{
								$result['edited']['enrollment'] = $previousValue . $row['enrollment_prev'];
							}
							else
							{
								$result['edited']['enrollment'] = $noPreviousValue;
							}
						}
						
						if($row['collaborator_lastchanged'] <= $endRange && $row['collaborator_lastchanged'] >= $startRange)
						{
							if($row['collaborator_prev'] != '' && $row['collaborator_prev'] !== NULL)
							{
								$row['collaborator_prev'] = str_replace('`', ', ', $row['collaborator_prev']);
								$result['edited']['collaborator'] = $previousValue . $row['collaborator_prev'];
							}
							else
							{
								$result['edited']['collaborator'] = $noPreviousValue;
							}
						}
						
						if($row['condition_lastchanged'] <= $endRange && $row['condition_lastchanged'] >= $startRange)
						{
							if($row['condition_prev'] != '' && $row['condition_prev'] !== NULL)
							{
								$row['condition_prev'] = str_replace('`', ', ', $row['condition_prev']);
								$result['edited']['condition'] = $previousValue . $row['condition_prev'];
							}
							else
							{
								$result['edited']['condition'] = $noPreviousValue;
							}
						}
						
						if($row['intervention_name_lastchanged'] <= $endRange && $row['intervention_name_lastchanged'] >= $startRange)
						{
							if($row['intervention_name_prev'] != '' && $row['intervention_name_prev'] !== NULL)
							{
								$row['intervention_name_prev'] = str_replace('`', ', ', $row['intervention_name_prev']);
								$result['edited']['intervention_name'] = $previousValue . $row['intervention_name_prev'];
							}
							else
							{
								$result['edited']['intervention_name'] = $noPreviousValue;
							}
						}
						
						if($row['overall_status_lastchanged'] <= $endRange && $row['overall_status_lastchanged'] >= $startRange)
						{
							if($row['overall_status_prev'] != '' && $row['overall_status_prev'] !== NULL)
							{
								$row['overall_status_prev'] = str_replace('`', ', ', $row['overall_status_prev']);
								$result['edited']['overall_status'] = $previousValue . $row['overall_status_prev'];
							}
							else
							{
								$result['edited']['overall_status'] = $noPreviousValue;
							}
						}
					
						if($row['firstreceived_date'] <= $endRange && $row['firstreceived_date'] >= $startRange)
						{
							$result['new'] = 'y';
						}
						
						$Values['Data'][0]['Trials'][$larvolId] = $result;
					}
				}
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			//fetching active count
			$cQuery = "SELECT FOUND_ROWS() AS total";
			$cRes = m_query(__LINE__, $cQuery);
			if($cRes)
			{
				$cRow = mysql_fetch_assoc($cRes);
				$Values['count'] = $cRow['total'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $cQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
		}
		catch(Exception $e)
		{
			throw $e;
		}
		return  $Values;
	}
	
	function compileOTTData2($ottType, $TrialsInfo = array(), $Ids = array(), $globalOptions = array(), $display = 'web')
	{	
		global $logger;
		
		$Values['Data'] = array();
		$Values['activecount'] = 0;
		$Values['inactivecount'] = 0;
		$Values['totalcount'] = 0;
		$Values['count'] = 0;
		
		$naUpms = array();
		
		$larvolIds = array();
		
		$pIds = array();
		$aIds = array();
		
		$pIds = array_map(function($item) { return $item['product']; }, $Ids);
		$pIds = array_filter($pIds);	
		$pIds = array_unique($pIds);	
		
		$aIds = array_map(function($item) { return $item['area']; }, $Ids);	
		$aIds = array_filter($aIds);
		$aIds = array_unique($aIds);
		
		$startRange = date('Y-m-d', strtotime($this->timeInterval, $this->timeMachine));
		$endRange = date('Y-m-d', $this->timeMachine);
		
		$previousValue = 'Previous value: ';	
		$noPreviousValue = 'No previous value';	
		
		$lstart = ($globalOptions['page']-1) * $this->resultsPerPage;
		$limit = " LIMIT " . $lstart . ", 100 ";
		
		if($ottType == 'rowstacked')
		{
			$orderBy = " ORDER BY FIELD(at.`entity`, " . implode(",", $aIds) . "), dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
		}
		else
		{
			$orderBy = " ORDER BY FIELD(pt.`entity`, " . implode(",", $pIds) . "), dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
		}
		
		$where = " WHERE 1 ";
		$join = "";
		
		$query = "SELECT SQL_CALC_FOUND_ROWS dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`,"
						. " dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`intervention_name`,"
						. " dt.`region`, dt.`phase`, dt.`firstreceived_date`, dt.`viewcount`, dt.`source`, "
						. " dm.`is_sourceless` AS manual_is_sourceless, dm.`brief_title` AS manual_brief_title, dm.`acronym` AS manual_acronym, "
						. " dm.`lead_sponsor` AS manual_lead_sponsor, dm.`collaborator` AS manual_collaborator,"
						. " dm.`condition` AS manual_condition, dm.`overall_status` AS manual_overall_status, dm.`region` AS manual_region,"
						. " dm.`start_date` AS manual_start_date, dm.`end_date` AS manual_end_date, dm.`enrollment` AS manual_enrollment, "
						. " dm.`intervention_name` AS manual_intervention_name, dm.`phase` AS manual_phase, dh.`brief_title_prev`, "
						. " dh.`end_date_prev`, dh.`lead_sponsor_prev`, dh.`collaborator_prev`, dh.`condition_prev`, dh.`overall_status_prev`, "
						. " dh.`start_date_prev`, dh.`enrollment_prev`, dh.`intervention_name_prev`, dh.`phase_prev`, dh.`region_prev`, dh.`brief_title_lastchanged`, "
						. " dh.`end_date_lastchanged`, dh.`lead_sponsor_lastchanged`, dh.`collaborator_lastchanged`, dh.`condition_lastchanged`, "
						. " dh.`overall_status_lastchanged`, dh.`start_date_lastchanged`, dh.`enrollment_lastchanged`, dh.`intervention_name_lastchanged`, "
						. " dh.`phase_lastchanged`, dh.`region_lastchanged` ";
		
		if(!empty($pIds))
		{
			$query .= ", pt.`entity` AS productid ";
			$where .= " AND pt.`entity` IN ('" . implode("','", $pIds) . "') ";
			$join .= " JOIN `entity_trials` pt ON dt.`larvol_id` = pt.`trial` ";
			$join .= " JOIN `entities` prdt ON pt.`entity` = prdt.id ";
		}
		if(!empty($aIds))
		{
			$query .= ", at.`entity` AS areaid ";
			$where .= " and at.`entity` IN ('" . implode("','", $aIds) . "') ";	
			$join .= " JOIN `entity_trials` at ON dt.`larvol_id` = at.`trial` ";
			$join .= " JOIN `entities` areas ON at.`entity` = areas.id ";
		}
		$query .= " FROM `data_trials` dt ";
		$query .= $join . " LEFT OUTER JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id` "
						. " LEFT OUTER JOIN `data_history` dh ON dh.`larvol_id` = dt.`larvol_id` ";
		
		//calculating count value only for webpage display and not for file exports				
		if($display == 'web')
		{
			$tQuery = "SELECT COUNT(*) AS totalcount "
						. " FROM `data_trials` dt ";
			$tQuery .=  $join . $where;
			$tRes = m_query(__LINE__,$tQuery);
			if($tRes)
			{
				$tRow = mysql_fetch_assoc($tRes);
				$Values['totalcount'] = $tRow['totalcount'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $tQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			$aQuery = "SELECT COUNT(*) AS activecount "
						. " FROM `data_trials` dt ";
			$aQuery .=  $join . $where . " AND dt.`is_active` = 1 ";
			$aRes = m_query(__LINE__,$aQuery);
			if($aRes)
			{
				$aRow = mysql_fetch_assoc($aRes);
				$Values['activecount'] = $aRow['activecount'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $aQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			$Values['inactivecount'] = $Values['totalcount'] - $Values['activecount'];
		}
		
		//Filtering Options
		$filters = $this->getActiveFilters($globalOptions);
		
		if(isset($globalOptions['product']) && !empty($globalOptions['product']))
		{	
			if($ottType == 'rowstacked')
			{
				$diff = array_diff($aIds, $globalOptions['product']);
				foreach($diff as $key => $value)
				{
					unset($aIds[$key]);
					unset($TrialsInfo[$key]);
				}
			}
			else
			{	
				$diff = array_diff($pIds, $globalOptions['product']);
				foreach($diff as $key => $value)
				{
					unset($pIds[$key]);
					unset($TrialsInfo[$key]);
				}
			}
			$where = " WHERE 1 ";
			if(!empty($pIds))
			{
				$where .= " AND pt.`entity` IN ('" . implode("','", $pIds) . "') ";
			}
			if(!empty($aIds))
			{
				$where .= " AND at.`entity` IN ('" . implode("','", $aIds) . "') ";	
			}
		}
		
		if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinxSearch']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if(isset($globalOptions['sphinx_s']))
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinx_s']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if($globalOptions['onlyUpdates'] == "yes")
		{
			$where .= " AND ((dt.`firstreceived_date` BETWEEN '" . $startRange . "' AND '" . $endRange . "') OR (`" . implode('` BETWEEN "' . $startRange . '" AND "' . $endRange . '") OR (`', $this->fieldNames) . "` BETWEEN '" . $startRange . "' AND '" . $endRange . "') )";
		}
		
		$Query = $query . $where;	
		
		//limit clause for pagination in webpage display and unsetting section headers which are not required in each page
		if($display == 'web')
		{
			$Query .= $filters . $orderBy . $limit;
		}
		else//without limit clause for file exports
		{
			if($globalOptions['dOption'] == 'all')
			{
				$Query .= $orderBy;
			}
			else
			{
				$Query .= $filters . $orderBy;
			}
		}
		
		$Values['Data'] = $TrialsInfo;
		unset($TrialsInfo);
		
		//fetching unmatched upms
		$naUpms = $this->getUnMatchedUpms($globalOptions, $pIds);
              
		foreach($naUpms as $nkey => $nvalue)
		{
			if($ottType != 'rowstacked')
			{
				$Values['Data'][$nkey]['naUpms'] = $nvalue;
			}
			else
			{
				$Values['Data'][0]['naUpms'] = $nvalue;
			}
		}
		$res = m_query(__LINE__,$Query);
		
		if($res)
		{
			if(mysql_num_rows($res) > 0)
			{
				while($row = mysql_fetch_assoc($res))
				{	
					$result = array();
					
					$larvolIds[] = $larvolId = $row['larvol_id'];
				
					$pId = $row['productid'];
					if($ottType == 'rowstacked')
					{
						$pId = $row['areaid'];
					}
					
					if(substr($row['source_id'], 0, 3) == "NCT")
					{ 
						$nctId = unpadnct(substr($row['source_id'], 0, 11));
					}
					else
					{
						$nctId = $row['source_id'];
					}
					
					if($ottType == 'rowstacked')
					{
						$result['sectionid'] 	= $row['areaid'];
					}
					else
					{
						$result['sectionid'] 	= $row['productid'];
					}
					
					$result['larvol_id'] 	= $larvolId;
					$result['nct_id'] 		= $nctId;
					if(strlen(trim($row['source_id'])) > 15)
					{
						$result['full_id'] 	= $row['source_id'];
					}
					else
					{
						$result['full_id'] 	= $nctId;
					}
					$result['id_for_upm'] 	= $row['source_id'];
					
					
					
					$result['brief_title'] 	= $row['brief_title'];
					$result['acronym'] 		= $row['acronym'];
					
					if($row['acronym'] != '') 
					{
						$result['brief_title'] = $this->replaceRedundantAcroynm($row['acronym'], $row['brief_title']);
					}
					
					$result['region'] 			= $row['region'];
					$result['lead_sponsor'] 	= str_replace('`', ', ', $row['lead_sponsor']);
					$result['start_date'] 		= $row['start_date'];
					$result['end_date'] 		= $row['end_date'];
					$result['phase'] 			= $row['phase'];
					$result['enrollment'] 		= $row['enrollment'];
					$result['collaborator'] 	= str_replace('`', ', ', $row['collaborator']);
					$result['condition'] 		= str_replace('`', ', ', $row['condition']);
					$result['intervention_name']= str_replace('`', ', ', $row['intervention_name']);
					$result['overall_status'] 	= $row['overall_status'];
					$result['is_active'] 		= $row['is_active'];
					$result['new'] 				= 'n';
					
					$result['viewcount'] 			= $row['viewcount']; 
					$result['source'] 				= $row['source']; 
					$result['source_id'] 			= $row['source_id']; 
					$result['sponsor_owned'] 		= $row['sponsor_owned'];
					
					$result['manual_brief_title'] 		= $row['manual_brief_title']; 
					$result['manual_acronym'] 			= $row['manual_acronym']; 
					$result['manual_lead_sponsor'] 		= $row['manual_lead_sponsor']; 
					$result['manual_collaborator'] 		= $row['manual_collaborator']; 
					$result['manual_condition'] 		= $row['manual_condition']; 
					$result['manual_overall_status']	= $row['manual_overall_status']; 
					$result['manual_start_date'] 		= $row['manual_start_date']; 
					$result['manual_end_date'] 			= $row['manual_end_date']; 
					$result['manual_enrollment'] 		= $row['manual_enrollment']; 
					$result['manual_intervention_name'] = $row['manual_intervention_name']; 
					$result['manual_phase'] 			= $row['manual_phase'];
					$result['manual_region'] 			= $row['manual_region'];
					$result['manual_is_sourceless'] 	= $row['manual_is_sourceless'];
					
					$result['end_date_prev'] 		= $row['end_date_prev']; 
					$result['lead_sponsor_prev'] 	= $row['lead_sponsor_prev']; 
					$result['brief_title_prev'] 	= $row['brief_title_prev']; 
					$result['collaborator_prev'] 	= $row['collaborator_prev']; 
					$result['condition_prev'] 		= $row['condition_prev']; 
					$result['overall_status_prev'] 	= $row['overall_status_prev']; 
					$result['start_date_prev']		= $row['start_date_prev']; 
					$result['enrollment_prev'] 		= $row['enrollment_prev']; 
					$result['intervention_name_prev'] = $row['intervention_name_prev']; 
					$result['phase_prev'] 		= $row['phase_prev']; 
					$result['region_prev'] 		= $row['region_prev']; 
					
					if($row['start_date_lastchanged'] <= $endRange && $row['start_date_lastchanged'] >= $startRange)
					{
						if($row['start_date_prev'] != '' && $row['start_date_prev'] !== NULL)
						{
							$result['edited']['start_date'] = $previousValue . $row['start_date_prev'];
						}
						else
						{
							$result['edited']['start_date'] = $noPreviousValue;
						}
					}
					
					if($row['end_date_lastchanged'] <= $endRange && $row['end_date_lastchanged'] >= $startRange)
					{
						if($row['end_date_prev'] != '' && $row['end_date_prev'] !== NULL)
						{
							$result['edited']['end_date'] = $previousValue . $row['end_date_prev'];
						}
						else
						{
							$result['edited']['end_date'] = $noPreviousValue;
						}
					}
					
					if($row['region_lastchanged'] <= $endRange && $row['region_lastchanged'] >= $startRange)
					{
						if($row['region_prev'] != '' && $row['region_prev'] !== NULL)
						{
							$result['edited']['region'] = $previousValue . $row['region_prev'];
						}
						else
						{
							$result['edited']['region'] = $noPreviousValue;
						}
					}
					
					if($row['brief_title_lastchanged'] <= $endRange && $row['brief_title_lastchanged'] >= $startRange)
					{
						if($row['brief_title_prev'] != '' && $row['brief_title_prev'] !== NULL)
						{
							$result['edited']['brief_title'] = $previousValue . $row['brief_title_prev'];
						}
						else
						{
							$result['edited']['brief_title'] = $noPreviousValue;
						}
					}
					
					if($row['lead_sponsor_lastchanged'] <= $endRange && $row['lead_sponsor_lastchanged'] >= $startRange)
					{
						if($row['lead_sponsor_prev'] != '' && $row['lead_sponsor_prev'] !== NULL)
						{
							$row['lead_sponsor_prev'] 	= str_replace('`', ', ', $row['lead_sponsor_prev']);
							$result['edited']['lead_sponsor'] = $previousValue . $row['lead_sponsor_prev'];
						}
						else
						{
							$result['edited']['lead_sponsor'] = $noPreviousValue;
						}
					}
					
					if($row['phase_lastchanged'] <= $endRange && $row['phase_lastchanged'] >= $startRange)
					{
						if($row['phase_prev'] != '' && $row['phase_prev'] !== NULL)
						{
							$result['edited']['phase'] = $previousValue . $row['phase_prev'];
						}
						else
	
						{
							$result['edited']['phase'] = $noPreviousValue;
						}
					}
					
					if($row['enrollment_lastchanged'] <= $endRange && $row['enrollment_lastchanged'] >= $startRange)
					{
						if($row['enrollment_prev'] != '' && $row['enrollment_prev'] !== NULL)
						{
							$result['edited']['enrollment'] = $previousValue . $row['enrollment_prev'];
						}
						else
						{
							$result['edited']['enrollment'] = $noPreviousValue;
						}
					}
					
					if($row['collaborator_lastchanged'] <= $endRange && $row['collaborator_lastchanged'] >= $startRange)
					{
						if($row['collaborator_prev'] != '' && $row['collaborator_prev'] !== NULL)
						{
							$row['collaborator_prev'] = str_replace('`', ', ', $row['collaborator_prev']);
							$result['edited']['collaborator'] = $previousValue . $row['collaborator_prev'];
						}
						else
						{
							$result['edited']['collaborator'] = $noPreviousValue;
						}
					}
					
					if($row['condition_lastchanged'] <= $endRange && $row['condition_lastchanged'] >= $startRange)
					{
						if($row['condition_prev'] != '' && $row['condition_prev'] !== NULL)
						{
							$row['condition_prev'] = str_replace('`', ', ', $row['condition_prev']);
							$result['edited']['condition'] = $previousValue . $row['condition_prev'];
						}
						else
						{
							$result['edited']['condition'] = $noPreviousValue;
						}
					}
					
					if($row['intervention_name_lastchanged'] <= $endRange && $row['intervention_name_lastchanged'] >= $startRange)
					{
						if($row['intervention_name_prev'] != '' && $row['intervention_name_prev'] !== NULL)
						{
							$row['intervention_name_prev'] = str_replace('`', ', ', $row['intervention_name_prev']);
							$result['edited']['intervention_name'] = $previousValue . $row['intervention_name_prev'];
						}
						else
						{
							$result['edited']['intervention_name'] = $noPreviousValue;
						}
					}
					
					if($row['overall_status_lastchanged'] <= $endRange && $row['overall_status_lastchanged'] >= $startRange)
					{
						if($row['overall_status_prev'] != '' && $row['overall_status_prev'] !== NULL)
						{
							$row['overall_status_prev'] = str_replace('`', ', ', $row['overall_status_prev']);
							$result['edited']['overall_status'] = $previousValue . $row['overall_status_prev'];
						}
						else
						{
							$result['edited']['overall_status'] = $noPreviousValue;
						}
					}
				
					if($row['firstreceived_date'] <= $endRange && $row['firstreceived_date'] >= $startRange)
					{
						$result['new'] = 'y';
					}
					
					$Values['Data'][$pId]['Trials'][$larvolId] = $result;
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		//fetching active count
		$cQuery = "SELECT FOUND_ROWS() AS total";
		$cRes = m_query(__LINE__, $cQuery);
		if($cRes)
		{
			$cRow = mysql_fetch_assoc($cRes);
			$Values['count'] = $cRow['total'];
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $cQuery . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		
		//fetching matched upms
		$dataUpms = $this->getMatchedUpms($globalOptions, $larvolIds);
		foreach($Values['Data'] as $dkey => & $dvalue)
		{
			$Id = $dvalue['Id'];
			if(isset($dvalue['Trials']))
			{
				foreach($dvalue['Trials'] as $tkey => & $tvalue)
				{
					if(isset($dataUpms[$tkey]))
					{
						$tvalue['upms'] = $dataUpms[$tkey];
					}
				}
			}
		}
		unset($dataUpms);
		
		return  $Values;
	}
	
	
	function compileOTTData($ottType, $TrialsInfo = array(), $Ids = array(), $globalOptions = array(), $display = 'web')
	{	
		global $logger;
		
		$Values['Data'] = array();
		$Values['activecount'] = 0;
		$Values['inactivecount'] = 0;
		$Values['totalcount'] = 0;
		$Values['count'] = 0;
		
		$naUpms = array();
		
		$larvolIds = array();
		
		$pIds = array();
		$aIds = array();
		
		$pIds = array_map(function($item) { return $item['product']; }, $Ids);
		$pIds = array_filter($pIds);	
		$pIds = array_unique($pIds);	
		
		$aIds = array_map(function($item) { return $item['area']; }, $Ids);	
		$aIds = array_filter($aIds);
		$aIds = array_unique($aIds);
		
		$startRange = date('Y-m-d', strtotime($this->timeInterval, $this->timeMachine));
		$endRange = date('Y-m-d', $this->timeMachine);
		
		$previousValue = 'Previous value: ';	
		$noPreviousValue = 'No previous value';	
		
		$lstart = ($globalOptions['page']-1) * $this->resultsPerPage;
		$limit = " LIMIT " . $lstart . ", 100 ";
		
		if($ottType == 'rowstacked')
		{
			$orderBy = " ORDER BY FIELD(at.`area`, " . implode(",", $aIds) . "), dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
		}
		else
		{
			$orderBy = " ORDER BY FIELD(pt.`product`, " . implode(",", $pIds) . "), dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
		}
		
		$where = " WHERE 1 ";
		$join = "";
		
		$query = "SELECT SQL_CALC_FOUND_ROWS dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`,"
						. " dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`intervention_name`,"
						. " dt.`region`, dt.`phase`, dt.`firstreceived_date`, dt.`viewcount`, dt.`source`, "
						. " dm.`is_sourceless` AS manual_is_sourceless, dm.`brief_title` AS manual_brief_title, dm.`acronym` AS manual_acronym, "
						. " dm.`lead_sponsor` AS manual_lead_sponsor, dm.`collaborator` AS manual_collaborator,"
						. " dm.`condition` AS manual_condition, dm.`overall_status` AS manual_overall_status, dm.`region` AS manual_region,"
						. " dm.`start_date` AS manual_start_date, dm.`end_date` AS manual_end_date, dm.`enrollment` AS manual_enrollment, "
						. " dm.`intervention_name` AS manual_intervention_name, dm.`phase` AS manual_phase, dh.`brief_title_prev`, "
						. " dh.`end_date_prev`, dh.`lead_sponsor_prev`, dh.`collaborator_prev`, dh.`condition_prev`, dh.`overall_status_prev`, "
						. " dh.`start_date_prev`, dh.`enrollment_prev`, dh.`intervention_name_prev`, dh.`phase_prev`, dh.`region_prev`, dh.`brief_title_lastchanged`, "
						. " dh.`end_date_lastchanged`, dh.`lead_sponsor_lastchanged`, dh.`collaborator_lastchanged`, dh.`condition_lastchanged`, "
						. " dh.`overall_status_lastchanged`, dh.`start_date_lastchanged`, dh.`enrollment_lastchanged`, dh.`intervention_name_lastchanged`, "
						. " dh.`phase_lastchanged`, dh.`region_lastchanged` ";
		
		if(!empty($pIds))
		{
			$query .= ", pt.`product` AS productid ";
			$where .= " AND pt.`product` IN ('" . implode("','", $pIds) . "') ";
			$join .= " JOIN `product_trials` pt ON dt.`larvol_id` = pt.`trial` ";
		}
		if(!empty($aIds))
		{
			$query .= ", at.`area` AS areaid ";
			$where .= " AND at.`area` IN ('" . implode("','", $aIds) . "') ";	
			$join .= " JOIN `area_trials` at ON dt.`larvol_id` = at.`trial` ";
		}
		$query .= " FROM `data_trials` dt ";
		$query .= $join . " LEFT OUTER JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id` "
						. " LEFT OUTER JOIN `data_history` dh ON dh.`larvol_id` = dt.`larvol_id` ";
		
		//calculating count value only for webpage display and not for file exports				
		if($display == 'web')
		{
			$tQuery = "SELECT COUNT(*) AS totalcount "
						. " FROM `data_trials` dt ";
			$tQuery .=  $join . $where;
			$tRes = m_query(__LINE__,$tQuery);
			if($tRes)
			{
				$tRow = mysql_fetch_assoc($tRes);
				$Values['totalcount'] = $tRow['totalcount'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $tQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			$aQuery = "SELECT COUNT(*) AS activecount "
						. " FROM `data_trials` dt ";
			$aQuery .=  $join . $where . " AND dt.`is_active` = 1 ";
			$aRes = m_query(__LINE__,$aQuery);
			if($aRes)
			{
				$aRow = mysql_fetch_assoc($aRes);
				$Values['activecount'] = $aRow['activecount'];
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $aQuery . mysql_error();
				$logger->error($log);
				unset($log);
			}
			
			$Values['inactivecount'] = $Values['totalcount'] - $Values['activecount'];
		}
		
		//Filtering Options
		$filters = $this->getActiveFilters($globalOptions);
		
		if(isset($globalOptions['product']) && !empty($globalOptions['product']))
		{	
			if($ottType == 'rowstacked')
			{
				$diff = array_diff($aIds, $globalOptions['product']);
				foreach($diff as $key => $value)
				{
					unset($aIds[$key]);
					unset($TrialsInfo[$key]);
				}
			}
			else
			{	
				$diff = array_diff($pIds, $globalOptions['product']);
				foreach($diff as $key => $value)
				{
					unset($pIds[$key]);
					unset($TrialsInfo[$key]);
				}
			}
			$where = " WHERE 1 ";
			if(!empty($pIds))
			{
				$where .= " AND pt.`product` IN ('" . implode("','", $pIds) . "') ";
			}
			if(!empty($aIds))
			{
				$where .= " AND at.`area` IN ('" . implode("','", $aIds) . "') ";	
			}
		}
		
		if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinxSearch']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if(isset($globalOptions['sphinx_s']))
		{
			$lIds = get_sphinx_idlist($globalOptions['sphinx_s']);
			if($lIds != '')
			{
				$where .= " AND dt.`larvol_id` IN (" . $lIds . ") ";
			}
		}
		
		if($globalOptions['onlyUpdates'] == "yes")
		{
			$where .= " AND ((dt.`firstreceived_date` BETWEEN '" . $startRange . "' AND '" . $endRange . "') OR (`" . implode('` BETWEEN "' . $startRange . '" AND "' . $endRange . '") OR (`', $this->fieldNames) . "` BETWEEN '" . $startRange . "' AND '" . $endRange . "') )";
		}
		
		$Query = $query . $where;	
		
		//limit clause for pagination in webpage display and unsetting section headers which are not required in each page
		if($display == 'web')
		{
			$Query .= $filters . $orderBy . $limit;
		}
		else//without limit clause for file exports
		{
			if($globalOptions['dOption'] == 'all')
			{
				$Query .= $orderBy;
			}
			else
			{
				$Query .= $filters . $orderBy;
			}
		}
		
		$Values['Data'] = $TrialsInfo;
		unset($TrialsInfo);
		
		//fetching unmatched upms
		$naUpms = $this->getUnMatchedUpms($globalOptions, $pIds);
              
		foreach($naUpms as $nkey => $nvalue)
		{
			if($ottType != 'rowstacked')
			{
				$Values['Data'][$nkey]['naUpms'] = $nvalue;
			}
			else
			{
				$Values['Data'][0]['naUpms'] = $nvalue;
			}
		}
		
		$res = m_query(__LINE__,$Query);
		if($res)
		{
			if(mysql_num_rows($res) > 0)
			{
				while($row = mysql_fetch_assoc($res))
				{	
					$result = array();
					
					$larvolIds[] = $larvolId = $row['larvol_id'];
				
					$pId = $row['productid'];
					if($ottType == 'rowstacked')
					{
						$pId = $row['areaid'];
					}
					
					if(substr($row['source_id'], 0, 3) == "NCT")
					{ 
						$nctId = unpadnct(substr($row['source_id'], 0, 11));
					}
					else
					{
						$nctId = $row['source_id'];
					}
					
					if($ottType == 'rowstacked')
					{
						$result['sectionid'] 	= $row['areaid'];
					}
					else
					{
						$result['sectionid'] 	= $row['productid'];
					}
					
					$result['larvol_id'] 	= $larvolId;
					$result['nct_id'] 		= $nctId;
					if(strlen(trim($row['source_id'])) > 15)
					{
						$result['full_id'] 	= $row['source_id'];
					}
					else
					{
						$result['full_id'] 	= $nctId;
					}
					$result['id_for_upm'] 	= $row['source_id'];
					
					
					
					$result['brief_title'] 	= $row['brief_title'];
					$result['acronym'] 		= $row['acronym'];
					
					if($row['acronym'] != '') 
					{
						$result['brief_title'] = $this->replaceRedundantAcroynm($row['acronym'], $row['brief_title']);
					}
					
					$result['region'] 			= $row['region'];
					$result['lead_sponsor'] 	= str_replace('`', ', ', $row['lead_sponsor']);
					$result['start_date'] 		= $row['start_date'];
					$result['end_date'] 		= $row['end_date'];
					$result['phase'] 			= $row['phase'];
					$result['enrollment'] 		= $row['enrollment'];
					$result['collaborator'] 	= str_replace('`', ', ', $row['collaborator']);
					$result['condition'] 		= str_replace('`', ', ', $row['condition']);
					$result['intervention_name']= str_replace('`', ', ', $row['intervention_name']);
					$result['overall_status'] 	= $row['overall_status'];
					$result['is_active'] 		= $row['is_active'];
					$result['new'] 				= 'n';
					
					$result['viewcount'] 			= $row['viewcount']; 
					$result['source'] 				= $row['source']; 
					$result['source_id'] 			= $row['source_id']; 
					$result['sponsor_owned'] 		= $row['sponsor_owned'];
					
					$result['manual_brief_title'] 		= $row['manual_brief_title']; 
					$result['manual_acronym'] 			= $row['manual_acronym']; 
					$result['manual_lead_sponsor'] 		= $row['manual_lead_sponsor']; 
					$result['manual_collaborator'] 		= $row['manual_collaborator']; 
					$result['manual_condition'] 		= $row['manual_condition']; 
					$result['manual_overall_status']	= $row['manual_overall_status']; 
					$result['manual_start_date'] 		= $row['manual_start_date']; 
					$result['manual_end_date'] 			= $row['manual_end_date']; 
					$result['manual_enrollment'] 		= $row['manual_enrollment']; 
					$result['manual_intervention_name'] = $row['manual_intervention_name']; 
					$result['manual_phase'] 			= $row['manual_phase'];
					$result['manual_region'] 			= $row['manual_region'];
					$result['manual_is_sourceless'] 	= $row['manual_is_sourceless'];
					
					$result['end_date_prev'] 		= $row['end_date_prev']; 
					$result['lead_sponsor_prev'] 	= $row['lead_sponsor_prev']; 
					$result['brief_title_prev'] 	= $row['brief_title_prev']; 
					$result['collaborator_prev'] 	= $row['collaborator_prev']; 
					$result['condition_prev'] 		= $row['condition_prev']; 
					$result['overall_status_prev'] 	= $row['overall_status_prev']; 
					$result['start_date_prev']		= $row['start_date_prev']; 
					$result['enrollment_prev'] 		= $row['enrollment_prev']; 
					$result['intervention_name_prev'] = $row['intervention_name_prev']; 
					$result['phase_prev'] 		= $row['phase_prev']; 
					$result['region_prev'] 		= $row['region_prev']; 
					
					if($row['start_date_lastchanged'] <= $endRange && $row['start_date_lastchanged'] >= $startRange)
					{
						if($row['start_date_prev'] != '' && $row['start_date_prev'] !== NULL)
						{
							$result['edited']['start_date'] = $previousValue . $row['start_date_prev'];
						}
						else
						{
							$result['edited']['start_date'] = $noPreviousValue;
						}
					}
					
					if($row['end_date_lastchanged'] <= $endRange && $row['end_date_lastchanged'] >= $startRange)
					{
						if($row['end_date_prev'] != '' && $row['end_date_prev'] !== NULL)
						{
							$result['edited']['end_date'] = $previousValue . $row['end_date_prev'];
						}
						else
						{
							$result['edited']['end_date'] = $noPreviousValue;
						}
					}
					
					if($row['region_lastchanged'] <= $endRange && $row['region_lastchanged'] >= $startRange)
					{
						if($row['region_prev'] != '' && $row['region_prev'] !== NULL)
						{
							$result['edited']['region'] = $previousValue . $row['region_prev'];
						}
						else
						{
							$result['edited']['region'] = $noPreviousValue;
						}
					}
					
					if($row['brief_title_lastchanged'] <= $endRange && $row['brief_title_lastchanged'] >= $startRange)
					{
						if($row['brief_title_prev'] != '' && $row['brief_title_prev'] !== NULL)
						{
							$result['edited']['brief_title'] = $previousValue . $row['brief_title_prev'];
						}
						else
						{
							$result['edited']['brief_title'] = $noPreviousValue;
						}
					}
					
					if($row['lead_sponsor_lastchanged'] <= $endRange && $row['lead_sponsor_lastchanged'] >= $startRange)
					{
						if($row['lead_sponsor_prev'] != '' && $row['lead_sponsor_prev'] !== NULL)
						{
							$row['lead_sponsor_prev'] 	= str_replace('`', ', ', $row['lead_sponsor_prev']);
							$result['edited']['lead_sponsor'] = $previousValue . $row['lead_sponsor_prev'];
						}
						else
						{
							$result['edited']['lead_sponsor'] = $noPreviousValue;
						}
					}
					
					if($row['phase_lastchanged'] <= $endRange && $row['phase_lastchanged'] >= $startRange)
					{
						if($row['phase_prev'] != '' && $row['phase_prev'] !== NULL)
						{
							$result['edited']['phase'] = $previousValue . $row['phase_prev'];
						}
						else
	
						{
							$result['edited']['phase'] = $noPreviousValue;
						}
					}
					
					if($row['enrollment_lastchanged'] <= $endRange && $row['enrollment_lastchanged'] >= $startRange)
					{
						if($row['enrollment_prev'] != '' && $row['enrollment_prev'] !== NULL)
						{
							$result['edited']['enrollment'] = $previousValue . $row['enrollment_prev'];
						}
						else
						{
							$result['edited']['enrollment'] = $noPreviousValue;
						}
					}
					
					if($row['collaborator_lastchanged'] <= $endRange && $row['collaborator_lastchanged'] >= $startRange)
					{
						if($row['collaborator_prev'] != '' && $row['collaborator_prev'] !== NULL)
						{
							$row['collaborator_prev'] = str_replace('`', ', ', $row['collaborator_prev']);
							$result['edited']['collaborator'] = $previousValue . $row['collaborator_prev'];
						}
						else
						{
							$result['edited']['collaborator'] = $noPreviousValue;
						}
					}
					
					if($row['condition_lastchanged'] <= $endRange && $row['condition_lastchanged'] >= $startRange)
					{
						if($row['condition_prev'] != '' && $row['condition_prev'] !== NULL)
						{
							$row['condition_prev'] = str_replace('`', ', ', $row['condition_prev']);
							$result['edited']['condition'] = $previousValue . $row['condition_prev'];
						}
						else
						{
							$result['edited']['condition'] = $noPreviousValue;
						}
					}
					
					if($row['intervention_name_lastchanged'] <= $endRange && $row['intervention_name_lastchanged'] >= $startRange)
					{
						if($row['intervention_name_prev'] != '' && $row['intervention_name_prev'] !== NULL)
						{
							$row['intervention_name_prev'] = str_replace('`', ', ', $row['intervention_name_prev']);
							$result['edited']['intervention_name'] = $previousValue . $row['intervention_name_prev'];
						}
						else
						{
							$result['edited']['intervention_name'] = $noPreviousValue;
						}
					}
					
					if($row['overall_status_lastchanged'] <= $endRange && $row['overall_status_lastchanged'] >= $startRange)
					{
						if($row['overall_status_prev'] != '' && $row['overall_status_prev'] !== NULL)
						{
							$row['overall_status_prev'] = str_replace('`', ', ', $row['overall_status_prev']);
							$result['edited']['overall_status'] = $previousValue . $row['overall_status_prev'];
						}
						else
						{
							$result['edited']['overall_status'] = $noPreviousValue;
						}
					}
				
					if($row['firstreceived_date'] <= $endRange && $row['firstreceived_date'] >= $startRange)
					{
						$result['new'] = 'y';
					}
					
					$Values['Data'][$pId]['Trials'][$larvolId] = $result;
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		//fetching active count
		$cQuery = "SELECT FOUND_ROWS() AS total";
		$cRes = m_query(__LINE__, $cQuery);
		if($cRes)
		{
			$cRow = mysql_fetch_assoc($cRes);
			$Values['count'] = $cRow['total'];
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $cQuery . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		
		//fetching matched upms
		$dataUpms = $this->getMatchedUpms($globalOptions, $larvolIds);
		foreach($Values['Data'] as $dkey => & $dvalue)
		{
			$Id = $dvalue['Id'];
			if(isset($dvalue['Trials']))
			{
				foreach($dvalue['Trials'] as $tkey => & $tvalue)
				{
					if(isset($dataUpms[$tkey]))
					{
						$tvalue['upms'] = $dataUpms[$tkey];
					}
				}
			}
		}
		unset($dataUpms);
		
		return  $Values;
	}
	
	function coverageAreaUpmIds($globalOptions = array())
	{
		global $logger;
		$upmIds = array();
		
		if(isset($globalOptions['hm']) && $globalOptions['hm'] != '')
		{
			$hmId = $globalOptions['hm'];
			
			$Query = "SELECT ar.`class`, ar.`id` "
					. " FROM `rpt_masterhm_headers` rmh "
					. " JOIN `entities` ar ON  rmh.`type_id` = ar.`id` "
					. " WHERE rmh.`report` = '" . $hmId . "' AND rmh.`type` = 'column' "
					. " ORDER BY rmh.`num` DESC LIMIT 0,1 ";
			$Res = m_query(__LINE__, $Query);
			if($Res)
			{
				$Row = mysql_fetch_assoc($Res);
				if($Row['class']=='Disease')
				{
					$query = "SELECT upm_id FROM upm_areas where area_id = '" . $Row['id'] . "' ";
					$res = m_query(__LINE__, $query);
					if($res)
					{
						 while($row = mysql_fetch_assoc($res))
						 {
							 $upmIds[] = $row['upm_id'];
						 }
					}
					else
					{
						$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
						$logger->error($log);
						unset($log);
					}
				}
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $Query . mysql_error();
				$logger->error($log);
				unset($log);
			}
		}
		
		return $upmIds;
	}
        
	function getUnMatchedUpms($globalOptions = array(), $productIds = array(), $ottType = 'indexed')
	{
		global $logger;
		
		$Ids = array();
		$onlyUpdates = $globalOptions['onlyUpdates'];
		$subQuery = "";
	
		if($ottType != 'entities')
		{
			$Ids = $this->coverageAreaUpmIds($globalOptions);
		}
		
		if(!empty($Ids))
		{
			$subQuery = " AND ( u.`id` IN ('" . implode("', '", $Ids) . "') OR ua.`upm_id` IS NULL ) ";
		}
                
		$result = array();
		$upmIds = array();
		$upmHistory = array();
		
		$query = "SELECT u.`id`, ut.`larvol_id`, u.`product`, u.`event_description`, u.`event_link`, "
                        . " u.`result_link`, u.`start_date`, u.`end_date`, u.`status`, u.`last_update`,  u.`condition`, "
                        . " rt.`name` AS redtag, rt.`type` AS redtagtype "
                        . " FROM `upm` u "
                        . " LEFT OUTER JOIN `upm_trials` ut ON ut.`upm_id` = u.`id` "
                        . " LEFT OUTER JOIN `redtags` rt ON rt.`id` = u.`redtag` "
                        . " LEFT OUTER JOIN `upm_areas` ua ON ua.`upm_id` = u.`id` "
                        . " WHERE ut.`larvol_id` IS NULL "
                        . " AND u.`product` IN ('" . implode("', '", $productIds) . "') "
                        . $subQuery
                        . " ORDER BY u.`end_date` ASC, u.`start_date` ASC ";
		$res = m_query(__LINE__,$query);
		if($res)
		{
			if(mysql_num_rows($res) > 0)
			{
				while($row = mysql_fetch_assoc($res))
				{
					$productId = $row['product'];
					$upmIds[] = $upmId = $row['id'];
					
					$result[$productId][$upmId]['new']	= 'n';
					
					$result[$productId][$upmId]['id'] 			= $row['id'];
					$result[$productId][$upmId]['event_description'] = htmlspecialchars($row['event_description']);
					$result[$productId][$upmId]['status']			= $row['status'];
					$result[$productId][$upmId]['event_link'] 		= $row['event_link'];
					$result[$productId][$upmId]['result_link'] 		= $row['result_link'];
					$result[$productId][$upmId]['condition'] 		= $row['condition'];
                                        
                    if($row['redtagtype'] != '' && $row['redtagtype'] !== NULL)
                    {
                        $result[$productId][$upmId]['event_type']           = $row['redtagtype'];
                    }
                    else
                    {
                        $result[$productId][$upmId]['event_type']           = $row['event_type'];
                    }
					$result[$productId][$upmId]['start_date'] 		= $row['start_date'];
					$result[$productId][$upmId]['end_date'] 		= $row['end_date'];
					$result[$productId][$upmId]['product'] 			= $row['product'];
					$result[$productId][$upmId]['redtag'] 			= $row['redtag'];
				}
				
				$upmHistory = $this->getUpmHistory($upmIds);
				
				foreach($result as $rkey => & $rvalue)
				{	
					foreach($rvalue as $key => & $value)
					{	
						if(isset($upmHistory[$key]))
						{
							$value = array_merge($value, $upmHistory[$key]);
						}
						else
						{
							if($onlyUpdates == "yes" && $result[$rkey][$key]['new'] == "n")
							{
								unset($result[$rkey][$key]);
							}
						}
					}
				}
				unset($upmHistory);
			}
			else
			{
				$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
				$logger->error($log);
				unset($log);
			}
		}
		
		return $result;
	}
	
	function getMatchedUpms($globalOptions = array(), $larvolIds = array(), $ottType = 'indexed')
	{
		global $logger;
		
		$Ids = array();
		$onlyUpdates = $globalOptions['onlyUpdates'];
		$subQuery = "";
         
		if($ottType != 'entities')
		{
			$Ids = $this->coverageAreaUpmIds($globalOptions);
		}      
		
		if(!empty($Ids))
		{
			$subQuery = " AND ( u.`id` IN ('" . implode("', '", $Ids) . "') OR ua.`upm_id` IS NULL ) ";
		}
                
		$result = array();
		$upmIds = array();
		$upmHistory = array();
		
		$query = " SELECT u.`id`, ut.`larvol_id`, u.`product`, u.`event_type`, u.`event_description`, u.`event_link`, "
					. " u.`result_link`, u.`start_date`, u.`end_date`, u.`status`, u.`last_update`, "
                    . " rt.`type` AS redtagtype "
					. " FROM upm u "
					. " LEFT OUTER JOIN upm_trials ut ON u.`id` = ut.`upm_id` "
                    . " LEFT OUTER JOIN `redtags` rt ON rt.`id` = u.`redtag` "
                    . " LEFT OUTER JOIN `upm_areas` ua ON ua.`upm_id` = u.`id` "
					. " WHERE ut.`larvol_id` IN ('" . implode("', '", $larvolIds) . "') "
                    . $subQuery
					. " ORDER BY u.`end_date` ASC, u.`start_date` ASC ";	
					
		$res = m_query(__LINE__,$query);
		if($res)
		{
			if(mysql_num_rows($res) > 0)
			{
				while($row = mysql_fetch_assoc($res))
				{
					$larvolId =  $row['larvol_id'];
					$upmIds[] = $upmId = $row['id'];
					
					$result[$larvolId][$upmId]['new']	= 'n';
					
					$result[$larvolId][$upmId]['id'] = $upmId;
					$result[$larvolId][$upmId]['event_description'] = htmlspecialchars($row['event_description']);
					$result[$larvolId][$upmId]['status'] 		= $row['status'];
					$result[$larvolId][$upmId]['event_link'] 	= $row['event_link'];
					$result[$larvolId][$upmId]['result_link']	= $row['result_link'];
                                        
                                        if($row['redtagtype'] != '' && $row['redtagtype'] !== NULL)
                                        {
                                            $result[$larvolId][$upmId]['event_type']    = $row['redtagtype'];
                                        }
                                        else
                                        {
                                            $result[$larvolId][$upmId]['event_type']    = $row['event_type'];
                                        }
                                        
					$result[$larvolId][$upmId]['start_date'] 	= $row['start_date'];
					$result[$larvolId][$upmId]['end_date'] 		= $row['end_date'];
					$result[$larvolId][$upmId]['product'] 		= $row['product'];
				}
				
				$upmHistory = $this->getUpmHistory($upmIds);
				
				foreach($result as $rkey => & $rvalue)
				{
					foreach($rvalue as $key => & $value)
					{
						if(isset($upmHistory[$key]))
						{
							$value = array_merge($value, $upmHistory[$key]);
						}
						else
						{
							if($onlyUpdates == "yes" && $result[$rkey][$key]['new'] == "n")
							{
								unset($result[$rkey][$key]);
							}
						}
					}
				}
				unset($upmHistory);
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		return $result;
	}
	
	function getUpmHistory($upmIds = array())
	{	
		$result = array();
		$startRange = date('Y-m-d', strtotime($this->timeInterval ,$this->timeMachine));
		$endRange = date('Y-m-d', $this->timeMachine);
		
		$query = "SELECT `id`, `field`, `old_value`, MAX(`change_date`) AS change_date FROM `upm_history` "
					. " WHERE `id` IN ('" . implode("','", $upmIds) . "') AND (CAST(`change_date` AS DATE) <= '" . $endRange . "' AND "
					. " CAST(`change_date` AS DATE) >= '" . $startRange . "') GROUP BY `id` ";
		$res = m_query(__LINE__,$query);
		if($res)
		{

			while($row = mysql_fetch_assoc($res))
			{
				$upmId = $row['id'];
				$field = $row['field'];
				
				
				if($field == 'new')
				{
					$result[$upmId]['new']	= 'y';
				}
				else
				{
					$result[$upmId]['edited']['id'] = $upmId;
					$result[$upmId]['edited']['field'] 	= $field;
					$result[$upmId]['edited'][$field] = $row['old_value'];
				}
			}
		}
		else
		{
			$log 	= 'ERROR: Bad SQL query. ' . $query . mysql_error();
			$logger->error($log);
			unset($log);
		}
		
		return $result;
	}
	
	function displayWebPage($ottType, $resultIds, $Values, $productSelector = array(), $globalOptions)
	{
		global $db, $maxEnrollLimit;
		$loggedIn	= $db->loggedIn();
		
		if($ottType == 'indexed')
			$globalOptions['includeProductsWNoData'] = "on";
			
		echo '<input type="hidden" name="pr" id="product" value="' . implode(',', $globalOptions['product']) . '" />';
		
		$count = $Values['count'];
		$totalPages = ceil($count / $this->resultsPerPage);
		
		$paginate = $this->pagination($globalOptions, $totalPages, $loggedIn);
		
		$urlParams = array();
		parse_str($paginate[0], $urlParams);
		
		natcasesort($productSelector);
		
		$this->displayFilterControls($productSelector, $count, $Values['activecount'], $Values['inactivecount'], $Values['totalcount'], $globalOptions, $ottType, $loggedIn);
		echo '<div id="parent">';
		echo '<div class="advanced" id="togglefilters"><img src="images/funnel.png" alt="Show Filter" style="vertical-align:bottom;" />&nbsp;Advanced</div>'
				. '<div class="records">' . $count . '&nbsp;Trials</div>';
		
		foreach($urlParams as $key => $value) 
		{
			if(strpos($key, 'amp;') !== FALSE)
			{
				$newKey = str_replace('amp;', '', $key);
				$urlParams[$newKey] = $value;
				unset($urlParams[$key]);
			}
		}
		
		echo '<div id="outercontainer"><p style="overflow:hidden;margin: 0;">';
		
		$lParams = array();
		if($globalOptions['type'] == 'inactiveTrials')
		{
			$lUrl = '';
			$lParams =  array_replace($urlParams, array('list' => '1'));
			$lUrl = http_build_query($lParams);
			
			echo '<span class="filters"><label>Inactive Trials</label>'
				. '<a href="intermediary.php?' . $lUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
		}
		else if($globalOptions['type'] == 'allTrials')
		{
			$lUrl = '';
			$lParams =  array_replace($urlParams, array('list' => '1'));
			$lUrl = http_build_query($lParams);
			
			echo '<span class="filters"><label>All Trials</label>'
				. '<a href="intermediary.php?' . $lUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';;
		}
		
		$sFilters = array();
		$sParams = array();
		if($globalOptions['type'] == "activeTrials")
		{
			$sFilters = $this->activeStatusValues;
		}
		else if($globalOptions['type'] == "inactiveTrials")
		{
			$sFilters = $this->inactiveStatusValues;
		}
		else if($globalOptions['type'] == "allTrials")
		{
			$sFilters = $this->allStatusValues;
		}
		foreach($globalOptions['status'] as $key => $value)
		{	
			$sUrl = '';
			$sUrl = $urlParams['status'];
			$sUrl = str_replace(',,', ',', str_replace($value, '', $sUrl));
			
			$sParams =  array_replace($urlParams, array('status' => $sUrl));
			$sUrl = http_build_query($sParams);
			
			echo '<span class="filters"><label>' .  $sFilters[$value] . '</label>'
				. '<a href="intermediary.php?' . $sUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
		}
		unset($sFilters);
		unset($sParams);
		unset($key);
		unset($value);
		
		$iParams = array();
		foreach($globalOptions['itype'] as $key => $value)
		{
			$iUrl = '';
			$iUrl = $urlParams['itype'];
			$iUrl = str_replace(',,', ',', str_replace($value, '', $iUrl));

			$iParams =  array_replace($urlParams, array('itype' => $iUrl));
			$iUrl = http_build_query($iParams);

			$val = $this->institutionFilters[$value];
			$val = str_replace('_', ' ', ucfirst($val));
			echo '<span class="filters"><label>' . $val . '</label>'
					. '<a href="intermediary.php?' . $iUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';

		}
		unset($iParams);
		unset($key);
		unset($value);
		
		$rParams = array();
		foreach($globalOptions['region'] as $key => $value)
		{
			$rUrl = '';
			$rUrl = $urlParams['region'];
			$rUrl = str_replace(',,', ',', str_replace($value, '', $rUrl));

			$rParams =  array_replace($urlParams, array('region' => $rUrl));
			$rUrl = http_build_query($rParams);
			
			echo '<span class="filters"><label>' .  $this->regionFilters[$value] . '</label>'
				. '<a href="intermediary.php?' . $rUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
		}
		unset($rParams);
		unset($key);
		unset($value);
		
		$phases = array('na' => 'N/A', '0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4');
		$pParams = array();
		foreach($globalOptions['phase'] as $key => $value)
		{
			if(array_key_exists($value, $phases))
			{
				$pUrl = '';
				$pUrl = $urlParams['phase'];
				$pUrl = str_replace(',,', ',', str_replace($value, '', $pUrl));
				
				$pParams =  array_replace($urlParams, array('phase' => $pUrl));
				$pUrl = http_build_query($pParams);

				echo '<span class="filters"><label>Phase ' .  $phases[$value] . '</label>'
				. '<a href="intermediary.php?' . $pUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
			}
		}
		unset($phases);
		unset($pParams);
		unset($key);
		unset($value);
		
		$hParams = array();
		if($globalOptions['startrange'] != "now" || $globalOptions['endrange'] != "1 month")
		{
			$hUrl = '';
			$hParams =  array_replace($urlParams, array('sr' => 'now', 'er' => '1 month'));
			$hUrl = http_build_query($hParams);
			
			echo '<span class="filters"><label>' . $globalOptions['startrange'] . ' - ' . $globalOptions['endrange'] . '</label>'
					. '<a href="intermediary.php?' . $hUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
		}
		unset($hParams);
		
		$oParams = array();
		if($globalOptions['onlyUpdates'] != 'no')
		{
			$oUrl = '';
			$oParams =  array_replace($urlParams, array('osu' => 'off'));
			$oUrl = http_build_query($oParams);
			
			echo '<span class="filters"><label>Only updates</label>'
				. '<a href="intermediary.php?' . $oUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
		}
		unset($oParams);
		
		$eParams = array();
		if($globalOptions['enroll'] != '0' && $globalOptions['enroll'] != ('0-'.$maxEnrollLimit))
		{
			$ev = str_replace('-', ' - ', $globalOptions['enroll']);
			
			$e = explode('-', $globalOptions['enroll']);
			if($e[1] == $maxEnrollLimit)
			{
				$ev = $e[0] . ' - ' . $e[1] . '+';
			}
			
			$eUrl = '';
			unset($urlParams['enroll']);
			$eParams =  $urlParams;
			$eUrl = http_build_query($eParams);
			
			echo '<span class="filters"><label>' . $ev . '</label>'
					. '<a href="intermediary.php?' . $eUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
		}
		unset($eParams);
		
		$dParams = array();
		if($globalOptions['includeProductsWNoData'] == 'on')
		{	
			if($ottType != 'indexed' && $ottType != 'entities')
			{
				$dUrl = '';
				$dParams =  array_replace($urlParams, array('ipwnd' => 'off'));

				$dUrl = http_build_query($dParams);
				$title = (($ottType == 'colstacked') ? 'Products' : 'Areas');
				echo '<span class="filters"><label>' . $title . ' with no data</label>'
						. '<a href="intermediary.php?' . $dUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
			}
		}
		unset($dParams);
		
		$tParams = array();
		if(!empty($globalOptions['product']))
		{
			foreach($globalOptions['product'] as $key => $value)
			{
				$tUrl = '';
				$tUrl = $urlParams['pr'];
				$tUrl = str_replace(',,', ',', str_replace($value, '', $tUrl));
				
				$tParams =  array_replace($urlParams, array('pr' => $tUrl));
				$tUrl = http_build_query($tParams);
			
				echo '<span class="filters"><label>' . $productSelector[$value] . '</label>'
						. '<a href="intermediary.php?' . $tUrl . '"><img src="images/black-cancel.png" alt="Remove Filter" /></a></span>';
			}
		}
		unset($tParams);
		unset($key);
		unset($value);
		
		echo '</p></div>';
		if($totalPages > 1)
		{
			echo $paginate[1];
		}
		
		echo '<div  id="fulltextsearchbox">'
			. '<input type="text" name="ss" autocomplete="off" style="width:153px;" value="' . $globalOptions['sphinxSearch'] . '" />'
			. '</div>';
		
		$resetUrl = 'intermediary.php?';
		$resetUrl .= $globalOptions['url'];
		
		$resetUrl .= str_replace(',', '&', $globalOptions['resetLink']);
		$resetUrl = htmlentities($resetUrl);
		
		echo '<div id="buttons">'
			. '<input type="submit" id="Show" value="Search" class="searchbutton" />&nbsp;'
			. '<a style="display:inline;" href="' . urlPath() . $resetUrl . '">'
			. '<input type="button" value="Reset" id="reset" class="resetbutton" onclick="javascript: window.location.href(\'' . urlPath() . $resetUrl . '\')" /></a></div>'
			. '<div class="milestones" style="width:155px;margin-right: 10px;"><div id="addtoright"></div></div>'
			. '<div class="export" id="chromemenu" style="width:64px;"><div><a rel="dropmenu"><b style="margin-left:16px;">Export</b></a></div></div>'
			. '</div>';
				
		echo '<input type="hidden" name="rflag" value="1" /><input type="hidden" name="rlink" value="' . $globalOptions['resetLink'] . '" />';
		if(isset($globalOptions['sphinx_s']))
		{
			echo '<input type="hidden" name="sphinx_s" value="' . $globalOptions['sphinx_s'] . '" />';
		}
		
		echo '<table cellpadding="0" cellspacing="0" class="manage">'
					 . '<tr>' . (($loggedIn) ? '<th style="width:70px;">ID</th>' : '' )
					 . '<th style="width:270px;">Title</th>'
					 . '<th style="width:30px;" title="Red: Change greater than 20%">N</th>'
					 . '<th style="width:64px;" title="&quot;RoW&quot; = Rest of World">Region</th>'
					 . '<th style="width:100px;">Interventions</th>'
					 . '<th style="width:90px;">Sponsor</th>'
					 . '<th style="width:105px;">Status</th>'
					 . '<th style="width:100px;">Conditions</th>'
					 . '<th title="MM/YY" style="width:33px;">End</th>'
					 . '<th style="width:25px;">Ph</th>'
					 . '<th style="width:25px;">Res</th>'
					 . '<th colspan="3" style="width:12px;">-</th>'
					 . '<th colspan="12" style="width:32px;">' . (date('Y')) . '</th>'
					 . '<th colspan="12" style="width:32px;">' . (date('Y')+1) . '</th>'
					 . '<th colspan="12" style="width:32px;">' . (date('Y')+2) . '</th>'
					 . '<th colspan="3" style="width:12px;">+</th></tr>';
		
		if($count > 0)
		{
			echo $this->displayTrials($globalOptions, $loggedIn, $Values, $ottType, $totalPages);
		}
		else
		{
			$outputStr = '';
			foreach($Values['Data'] as $dkey => $dvalue)
			{
				$sectionHeader = $dvalue['sectionHeader'];
				if($ottType == 'rowstacked')
				{
					$naUpms = $Values['Data'][0]['naUpms'];
					unset($Values['Data'][0]['naUpms']);
				}
				else
				{
					$naUpms = $dvalue['naUpms'];
				}
				
				$outputStr .= $this->dUnmatchedUpms($globalOptions, $ottType, $sectionHeader, $naUpms);
			}
			
			echo $outputStr;
		}
		
		echo '</table>';
		
		echo '<input type="hidden" name="enroll" value="' . $globalOptions['enroll'] . '" />';	
		
		if($totalPages > 1)
		{
			echo '<div style="height:10px;">&nbsp;</div>';
			echo $paginate[1];
			//$this->pagination($globalOptions, $totalPages, $timeMachine, $ottType, $loggedIn);
		}
		echo '</form><br/>';
		
		if($Values['totalcount'] > 0) 
		{
			echo '<div id="dropmenu" class="dropmenudiv" style="width: 310px;">'
				. $this->downloadOptions($count, $Values['totalcount'], $ottType, $resultIds, $globalOptions)
				. '</div><script type="text/javascript">cssdropdown.startchrome("chromemenu");</script>';
		}
		echo '<br/><br/><div style="height:50px;"></div>';
	}
	
	function dUnmatchedUpms($globalOptions, $ottType, $sectionHeader, $naUpms, $noRecordRow = 'y')
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		
		$outputStr = '';
		
		if($globalOptions['includeProductsWNoData'] == "off")
		{
			if(!empty($naUpms))
			{
				$outputStr .= $this->displayUpmHeaders($ottType, $naUpms, $sectionHeader);
				if($noRecordRow == 'y')
				{
					if($globalOptions['onlyUpdates'] == "no")
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
					}
				}
			}
			else
			{
				if($noRecordRow == 'n')
				{
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">' . $sectionHeader . '</td></tr>';
				}
			}
		}
		else
		{
			if(!empty($naUpms))
			{
				$outputStr .= $this->displayUpmHeaders($ottType, $naUpms, $sectionHeader);
			}
			else
			{
				$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">' . $sectionHeader . '</td></tr>';
			}
			if($noRecordRow == 'y')
			{
				if($globalOptions['onlyUpdates'] == "no")
				{
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
				}
			}
		}
		return $outputStr;
	}
	
	function displayUpmHeaders($ottType, $naUpms, $sectionHeader)
	{
		global $db;
		$loggedIn	= $db->loggedIn();
		$outputStr = '';
		
		$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $sectionHeader);
		$naUpmIndex = substr($naUpmIndex, 0, 15);
			
		if($ottType == 'rowstacked')
		{
			$outputStr .= '<tr class="trialtitles">'
						. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
						. 'style="background: url(\'images/down.png\') no-repeat left center;"'
						. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;</td></tr>'
						. $this->displayUnMatchedUpms($ottType, $loggedIn, $naUpmIndex, $naUpms)
						. '<tr class="trialtitles">'
						. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
						. $sectionHeader . '</td></tr>';
		}
		else
		{
			if($ottType == 'indexed')
				$image = 'down';
			else
				$image = 'up';
			
			$outputStr .= '<tr class="trialtitles">'
						. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
						. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
						. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
						. $sectionHeader . '</td></tr>';
			$outputStr .= $this->displayUnMatchedUpms($ottType, $loggedIn, $naUpmIndex, $naUpms);
		}
		
		return $outputStr;
	}
	
	function downloadOptions($shownCnt, $foundCnt, $ottType, $result, $globalOptions) 
	{	
		$downloadOptions = '<div style="height:180px; padding:6px;"><div class="downldbox"><div class="newtext">Download Options</div>'
							. '<form  id="frmDOptions" name="frmDOptions" method="post" target="_self" action="">'
							. '<input type="hidden" name="ottType" value="' . $ottType . '" />';
		foreach($result as $rkey => $rvalue)
		{
			if(is_array($rvalue))
			{
				foreach($rvalue as $rk => $rv)
				{
					$downloadOptions .= '<input type="hidden" name="resultIds[' . $rkey . '][' . $rk . ']" value="' . $rv . '" />';
				}
			}
			else
			{
				$downloadOptions .= '<input type="hidden" name="resultIds[' . $rkey . ']" value="' . $rvalue . '" />';
			}

		}
		foreach($globalOptions as $gkey => $gvalue)
		{	
			if(is_array($gvalue))
			{	
				foreach($gvalue as $gk => $gv)
				{	
					$downloadOptions .= '<input type="hidden" name="globalOptions[' . $gkey . '][' . $gk . ']" value=\'' . $gv . '\' />';
				}
			}
			else
			{	
				$downloadOptions .= '<input type="hidden" name="globalOptions[' . $gkey . ']" value=\'' . $gvalue . '\' />';
			}
		}	
		$downloadOptions .= '<ul><li><label>Number of Studies: </label></li>'
							. '<li><select id="dOption" name="dOption" size="2" style="height:54px;">'
							. '<option value="shown" selected="selected">' . $shownCnt . ' Shown Studies</option>'
							. '<option value="all">' . $foundCnt . ' Found Studies</option></select></li>'
							. '<li><label>Which Format: </label></li>'
							. '<li><select id="wFormat" name="wFormat" size="3" style="height:54px;">'
							. '<option value="excel" selected="selected">Excel</option>'
							//comment the following line to hide pdf export
							. '<option value="pdf">PDF</option>'
							. '<option value="tsv">TSV</option>'
							. '</select></li></ul>'
							. '<input type="hidden" name="shownCnt" value="' . $shownCnt . '" />'
							. '<input type="submit" id="btnDownload" name="btnDownload" value="Download File" style="margin-left:8px;"  />'
							. '</form></div></div>';
		
		return $downloadOptions;
	}
	
	function displayHeader($productAreaInfo)
	{
		global $li_user;
		
		if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') !== FALSE) 
		|| (isset($_GET['LI']) && $_GET['LI'] == 1)   )
		{
			echo '<input type="hidden" name="LI" value="1" />';
			$li_user='YES';
		}
		elseif((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'trialzilla') !== FALSE) 
		|| (isset($_REQUEST['LS']) && $_REQUEST['LS'] == "1")   )
		{
			$_GET['LS']="1";
			$li_user=null;
			echo '<input type="hidden" name="LS" value="1" />';
			echo '<table width="100%">'
					. '<tr><td><img src="images/larvol_sigma_logo.gif" alt="Main" width="358" height="61" id="header" /></td>'
					. '<td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface work in progress</span>'
					. '<br/><span style="font-weight:normal;">Send feedback to '
					. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
					. 'larvoltrials@larvol.com</a></span></td>'
					. '<td class="result">' . $productAreaInfo . '</td></tr></table>';
		}
		
		else
		{
			$li_user=null;
			echo '<table width="100%">'
					. '<tr><td><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
					. '<td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface work in progress</span>'
					. '<br/><span style="font-weight:normal;">Send feedback to '
					. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
					. 'larvoltrials@larvol.com</a></span></td>'
					. '<td class="result">' . $productAreaInfo . '</td></tr></table>';
		}
	}
	
	function displayFilterControls($productSelector = array(), $shownCount, $activeCount, $inactiveCount, $totalCount, $globalOptions = array(), $ottType, $loggedIn)
	{	
		echo '<table border="0" cellspacing="0" class="controls" align="center" style="_width:100%; table-layout: fixed;display: none;">'
				. '<tr><td colspan="5" style="border: none;height:29px;"></td></tr>'
				. '<tr><th style="width:113px">Active</th><th style="width:210px">Status</th>'
				. '<th style="width:180px">Sponsor type</th>'
				. '<th style="width:80px">Region</th>'
				. '<th style="width:50px">Phase</th>'
				. '<th class="right" style="width:340px">Filter</th></tr>'
				. '<tr><td class="bottom" style="padding-right:5px;"><div style="width:113px">'
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
				. '</div></td><td class="bottom"><div class="checkscroll" id="statuscontainer" style="width:210px">';
		if($globalOptions['type'] == "inactiveTrials")
		{
			echo '<input type="checkbox" class="status" value="6" ' 
				. (in_array('6', $globalOptions['status']) ? 'checked = "checked" ' : '') . ' />Withheld<br/>'
				. '<input type="checkbox" class="status" value="7" '
				. (in_array('7', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Approved for marketing<br/>'
				. '<input type="checkbox" class="status" value="8" '
				. (in_array('8', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Temporarily not available<br/>'
				. '<input type="checkbox" class="status" value="9" '
				. (in_array('9', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>No longer available<br/>'
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
				 . (in_array('0', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Not yet recruiting<br/>'
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

				 . (in_array('9', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>No longer available<br/>'
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
				. (in_array('0', $globalOptions['status']) ? 'checked = "checked" ' : '') . '/>Not yet recruiting<br/>'
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
		echo  '</div></td>';
		
		if(!empty($this->institutionFilters))
		{
			echo '<td class="bottom"><div style="width:180px">';
			foreach($this->institutionFilters as $ikey => $ivalue)
			{
				echo '<input type="checkbox" value="' . $ikey . '" id="institution_type_' . $ikey . '" class="institution" '
						. (in_array($ikey, $globalOptions['itype']) ? ' checked="checked" ' : '') . '/>'
						. '<label for="institution_type_' . $ikey . '">' . str_replace('_', ' ', ucfirst($ivalue)) . '</label><br />';
			}
			echo '</div></td>';
		}
		
		echo '<td class="bottom"><div style="width:80px">'
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
				. '<label for="region_4">RoW</label>'
				. '</div></td>'
				. '<td class="bottom"><div style="width:50px">'
				. '<input type="checkbox" value="na" id="phase_na" class="phase" '
				. (in_array('na', $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_na">N/A</label><br />'
				. '<input type="checkbox" value="0" id="phase_0" class="phase" '
				. (in_array('0', $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_0">0</label><br />'
				. '<input type="checkbox" value="1" id="phase_1" class="phase" '
				. (in_array('1', $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_1">1</label><br />'
				. '<input type="checkbox" value="2" id="phase_2" class="phase" '
				. (in_array('2', $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_2">2</label><br />'
				. '<input type="checkbox" value="3" id="phase_3" class="phase" '
				. (in_array('3', $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_3">3</label><br />'
				. '<input type="checkbox" value="4" id="phase_4" class="phase" '
				. (in_array('4', $globalOptions['phase']) ? ' checked="checked" ' : '') . '/>'
				. '<label for="phase_4">4</label>'
				. '</div></td>'
				. '<td class="right" style="border-bottom:0px"><div class="demo" style="width:340px"><p>';
		
		if($loggedIn) 
		{
			echo '<label for="startrange" style="float:left;">Highlight changes:</label>'
					. '<input type="text" id="startrange" name="sr" value="' . $globalOptions['startrange'] . '" class="jdpicker" />'
					. '<label style="color:#f6931f;float:left;">-</label>'
					. '<input type="text" id="endrange"  name="er" value="' . $globalOptions['endrange'] 
					. '" style="width:auto;margin-left:15px;" class="jdpicker" />'
					. '<br/><div id="slider-range-min" align="left"></div></p>';
		}
		else
		{
			echo '<label>Highlight changes:</label>'
					. '<input type="hidden" id="startrange" name="sr" value="' . $globalOptions['startrange'] . '" />'
					. '<input type="text" id="endrange" name="er" value="' . $globalOptions['endrange'] 
					. '" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold;" />'
					. '<div id="slider-range-min" align="left"></div></p>';
		}
			
		echo '<input type="checkbox" id="showonlyupdated" name="osu" ' 
				. ($globalOptions['onlyUpdates'] == 'yes' ? ' checked="checked" ' : '' ) . ' />'
				. '<label for="showonlyupdated" style="font-size:x-small;">Show only changed items</label>'
				. '</div><br/><div class="demo"><p><label for="amount">Enrollment:</label>'
				. '<input type="text" id="amount" style="border:0; color:#f6931f; font-weight:bold;" autocomplete="off" />'
				. '<div id="slider-range" align="left"></div>'
				. '</p></div>';
		if($ottType != 'indexed' && $ottType != 'entities')
		{
			$title = (($ottType == 'colstacked') ? 'products' : 'areas');
			echo '<br/><input type="checkbox" id="ipwnd" name="ipwnd" ' . (($globalOptions['includeProductsWNoData'] == "on") ? 'checked="checked"' : '') . ' />'
				. '<label style="font-size:x-small;" for="ipwnd">Include ' . $title . ' with no data</label>';
		}
		
		/*echo '<br/><input type="checkbox" id="tspo" name="tspo" ' . (($globalOptions['showTrialsSponsoredByProductOwner'] == "on") ? 'checked="checked"' : '') . ' />'
				. '<label style="font-size:x-small;" for="tspo">Show only trials sponsored by product owner</label>';*/
				
		echo  '</td></tr><tr>'
				. '<td class="bottom">&nbsp;</td><td class="bottom">&nbsp;</td>'
				. '<td class="bottom">&nbsp;</td><td class="bottom">&nbsp;</td>'
				. '<td class="bottom">&nbsp;</td><td class="right bottom">';
		
		if(!empty($productSelector)
		&& ($ottType != 'indexed'))
		{
			echo '<div id="menuwrapper" style="vertical-align:bottom;margin-left: 2px;"><ul>';
			if(isset($globalOptions['product']) && !empty($globalOptions['product']))
			{	
				if(count($globalOptions['product']) > 1)
					$tTitle = count($globalOptions['product']) . ' ' . $title  . ' selected';
				else
					$tTitle = $productSelector[$globalOptions['product'][0]];
					
				echo '<li class="arrow"><a href="javascript: void(0);">' . $tTitle . '</a>';

			}
			else
			{	
				echo '<li class="arrow" style="height:23px;"><a href="javascript: void(0);">Select ' . $title . '</a>';
			}
			
			echo '<ul id="productbox">';
			foreach($productSelector as $infkey => $infvalue)
			{
				echo '<li><a href="javascript: void(0);">'
					. '<input type="checkbox" value="' . $infkey . '" id="product_' . $infkey . '" class="product" style="margin-right:5px;" ' 
					. ((in_array($infkey, $globalOptions['product'])) ? 'checked="checked"' : '') . ' />' 
					. $infvalue . '</a></li>';
			}
			echo '</ul></li></ul></div>';
		}		
		else
		{
			echo '&nbsp;';
		}
		echo '</td></tr>'
			. '<tr><td colspan="5" style="border: none;height:29px;"></td></tr></table>'
			. '<input type="hidden" name="status" id="status" value="' . implode(',', $globalOptions['status']) . '" />'
			. '<input type="hidden" name="itype" id="itype" value="' . implode(',', $globalOptions['itype']) . '" />'
			. '<input type="hidden" name="region" id="region" value="' . implode(',', $globalOptions['region']) . '" />'
			. '<input type="hidden" name="phase" id="phase" value="' . implode(',', $globalOptions['phase']) . '" />';
	}

	function pagination($globalOptions = array(), $totalPages, $loggedIn)
	{ 	
		$url = $globalOptions['url'];
		
		if(isset($globalOptions['startrange']))
		{
			$url .= '&amp;sr=' . $globalOptions['startrange'];
		}
		if(isset($globalOptions['endrange']))
		{
			$url .= '&amp;er=' . $globalOptions['endrange'];
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
		if(isset($globalOptions['enroll']) && $globalOptions['enroll'] != '0') 
		{
			$url .= '&amp;enroll=' . $globalOptions['enroll'];
		}
		
		if(isset($globalOptions['LI']) && $globalOptions['LI'] == '1')
		{
			$url .= '&amp;LI=1';
		}
		
		if(isset($globalOptions['product']) && !empty($globalOptions['product']))
		{
			$url .= '&amp;pr=' . implode(',', $globalOptions['product']);
		}
		
		if(isset($globalOptions['includeProductsWNoData']) && $globalOptions['includeProductsWNoData'] == "on")
		{
			$url .= '&amp;ipwnd=on';
		}
		
		if(isset($globalOptions['sphinx_s']))
		{
			$url .= '&amp;sphinx_s=' . $globalOptions['sphinx_s'];
		}
		
		if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
		{
			$url .= '&amp;ss=' . $globalOptions['sphinxSearch'];
		}

		if(isset($globalOptions['showTrialsSponsoredByProductOwner']) && $globalOptions['showTrialsSponsoredByProductOwner'] == "on")
		{
			$url .= '&amp;tspo=on';
		}
		
		if(isset($globalOptions['hm']) && $globalOptions['hm'] != '')
		{
			$url .= '&amp;hm=' . $globalOptions['hm'];
		}
		
		if(isset($globalOptions['resetLink']))
		{
			$url .= '&amp;rlink=' . $globalOptions['resetLink'];
		}
		$url .= '&amp;rflag=1';
		$stages = 1;
		
		$rootUrl = 'intermediary.php?';
		$paginateStr = '<div class="pagination">';
		///ALL Quotation Marks SIGN REPLACED BY Apostrophe, CAUSE JSON DATA URL GET PROBLEM WITH double quote.
		// globalOptions Should always have Apostrophe instead of quote sign or data will not be passed
		if($globalOptions['page'] != 1)
		{
			$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . ($globalOptions['page']-1) . '\'>&laquo;</a>';
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
					$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
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
						$paginateStr .='<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
					}
				}
				$paginateStr.= '<span>...</span>';
				$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . ($totalPages-1) . '\'>' .  ($totalPages-1) . '</a>';
				$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
			}
			elseif($totalPages - ($stages * 2) > $globalOptions['page'] && $globalOptions['page'] > ($stages * 2))
			{
				$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=1\'>1</a>';
				$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=2\'>2</a>';
				$paginateStr.= '<span>...</span>';
				for($counter = $globalOptions['page'] - $stages; $counter <= $globalOptions['page'] + $stages; $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr.= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
					}
				}
				$paginateStr.= '<span>...</span>';
				$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . ($totalPages-1) . '\'>' . ($totalPages-1) . '</a>';
				$paginateStr.= '<a href=\'' . $rootUrl . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
			}
			else
			{
				$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=1\'>1</a>';
				$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=2\'>2</a>';
				$paginateStr .= "<span>...</span>";
				for($counter = $totalPages - (2 + ($stages * 2)); $counter <= $totalPages; $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr .= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
					}
				}
			}
		}
		
		if($globalOptions['page'] != $totalPages)
		{
			$paginateStr .= '<a href=\'' . $rootUrl . $url . '&page=' . ($globalOptions['page']+1) . '\'>&raquo;</a>';
		}
		$paginateStr .= '</div>';
		
		return array($url, $paginateStr);
	}
	
	function displayTrials($globalOptions = array(), $loggedIn, $Values, $ottType, $totalPages)
	{
		$currentYear = date('Y');
		$secondYear = (date('Y')+1);
		$thirdYear = (date('Y')+2);
		
		$counter = 0;
		$outputStr = '';
		$sectionId = '';
		
		$Ids = array_map(function($item) { return $item['Id']; }, $Values['Data']);
		$Ids = array_values($Ids);
		$Ids = array_filter($Ids);
		
		$Trials = array();
		foreach($Values['Data'] as $key => & $value)
		{
			if(isset($value['Trials']))
			{
				$Trials = array_merge($Trials, $value['Trials']);
				unset($value['Trials']);
			}
		}
		
		//if the start sections has 0 trials
		if($globalOptions['page'] == 1)
		{
			$startIndex = 0;
			$endIndex = array_search($Trials[0]['sectionid'], $Ids);
			if($startIndex < $endIndex)
			{
				for($i=($startIndex);$i<$endIndex;$i++)
				{
					$index = $Ids[$i];
					$sectionHeader = $Values['Data'][$index]['sectionHeader'];
					
					if($ottType == 'rowstacked')
					{
						$naUpms = $Values['Data'][0]['naUpms'];
						unset($Values['Data'][0]['naUpms']);
					}
					else
					{
						$naUpms = $Values['Data'][$index]['naUpms'];
					}
					
					//Rendering Upms
					$outputStr .= $this->dUnmatchedUpms($globalOptions, $ottType, $sectionHeader, $naUpms);
				}
			}
		}
		
		foreach($Trials as $tkey => $tvalue)
		{
			if($tvalue['sectionid'] != $sectionId)
			{	
				if($sectionId != '')
				{
					$nUpms = array();
					$missedElements = array();
					$startIndex = array_search($sectionId, $Ids);
					$endIndex = array_search($tvalue['sectionid'], $Ids);
					
					if($endIndex === false) 
					{
						$endIndex = count($Ids) - 1;
					}
					$missedElements = array_slice($Ids, ($startIndex+1), ($endIndex - $startIndex - 1));
					
					if(!empty($missedElements))
					{
						foreach($missedElements as $mkey => $mvalue)

						{
							$sHeader = $Values['Data'][$mvalue]['sectionHeader'];
							$nUpms = $Values['Data'][$mvalue]['naUpms'];
							
							//Rendering Upms
							$outputStr .= $this->dUnmatchedUpms($globalOptions, $ottType, $sHeader, $nUpms);
						}
					}
				}
				
				$sectionId = $tvalue['sectionid'];
				$sectionHeader =  $Values['Data'][$sectionId]['sectionHeader'];
				if($ottType == 'rowstacked')
				{
					$naUpms = array();
					if($globalOptions['page'] == 1)
					{
						$naUpms = $Values['Data'][0]['naUpms'];
						unset($Values['Data'][0]['naUpms']);
					}
				}
				else
				{
					$naUpms = $Values['Data'][$sectionId]['naUpms'];
				}
				
				//Rendering Upms
				$outputStr .= $this->dUnmatchedUpms($globalOptions, $ottType, $sectionHeader, $naUpms, 'n');
			}
			
			if($counter%2 == 1) 
				$rowOneType = 'alttitle';
			else
				$rowOneType = 'title';
			
			$rowspan = 1;
			$titleLinkColor = '#000000;';
		
			if(isset($tvalue['upms']))  
				$rowspan = count($tvalue['upms'])+1; 
				
			//row starts  
			$outputStr .= '<tr ' . (($tvalue['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
			
			
			//nctid column
			if($loggedIn) 
			{ 
				$outputStr .= '<td class="' . $rowOneType . '" ' . (($tvalue['new'] == 'y') ? 'title="New record"' : ''). ' ><div class="rowcollapse">';
				if(strpos($tvalue['full_id'], 'NCT') !== FALSE)
				{
					$tvalue['full_id'] = str_replace('`', "\n", $tvalue['full_id']);
				}
				$outputStr .= '<a style="color:' . $titleLinkColor . '" href="' . urlPath() . 'edit_trials.php?larvol_id=' . $tvalue['larvol_id'] 
								. '" target="_blank">' . $tvalue['full_id'] . '</a></div></td>';
			}
			
			
			//acroynm and title column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{	
				if(isset($tvalue['edited']) && array_key_exists('brief_title', $tvalue['edited']) && str_replace('Previous value: ', '', $tvalue['edited']['brief_title']) <> $tvalue['brief_title']) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['brief_title'];
					$titleLinkColor = '#FF0000;';
				} 
				elseif($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
					$titleLinkColor = '#FF0000;';
				}
				elseif($tvalue['manual_brief_title'] != '' && $tvalue['manual_brief_title'] !== NULL)
				{
					if($tvalue['brief_title_prev'] == $tvalue['brief_title'])
					{
						$attr = ' manual" title="Manual curation.';
						$titleLinkColor = '#FF7700';
					}
					elseif($tvalue['brief_title_prev'] != '' && $tvalue['brief_title_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['brief_title_prev'];
						$titleLinkColor = '#FF7700';
					}
					
				}
			}
			else
			{ 	
				if($tvalue['manual_brief_title'] != '' && $tvalue['manual_brief_title'] !== NULL)
				{
					if($tvalue['brief_title_prev'] == $tvalue['brief_title'])
					{
						$attr = ' manual" title="Manual curation.';
						$titleLinkColor = '#FF7700';
					}
					elseif($tvalue['brief_title_prev'] != '' && $tvalue['brief_title_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['brief_title_prev'];
						$titleLinkColor = '#FF7700';
					}
				}
				elseif(isset($tvalue['edited']) && array_key_exists('brief_title', $tvalue['edited']) && str_replace('Previous value: ', '', $tvalue['edited']['brief_title']) <> $tvalue['brief_title']) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['brief_title'];
					$titleLinkColor = '#FF0000;';
				} 
				elseif($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
					$titleLinkColor = '#FF0000;';
				}
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<div class="rowcollapse"><a style="color:' . $titleLinkColor . '"  ';
			
			if(isset($tvalue['manual_is_sourceless']))
			{	
				$outputStr .= ' href="' . $tvalue['source'] . '" ';
			}
			else if(isset($tvalue['source_id']) && strpos($tvalue['source_id'], 'NCT') === FALSE)
			{	
				$outputStr .= ' href="https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $tvalue['nct_id'] . '" ';
			}
			else if(isset($tvalue['source_id']) && strpos($tvalue['source_id'], 'NCT') !== FALSE)
			{	
				$outputStr .= ' href="http://clinicaltrials.gov/ct2/show/' . padnct($tvalue['nct_id']) . '" ';
			}
			else 
			{ 	
				$outputStr .= ' href="javascript:void(0);" ';
			}
			
			$outputStr .= ' target="_blank" ';
			
			$outputStr .= ' onclick="INC_ViewCount(' . $tvalue['larvol_id'] . ')"><font id="ViewCount_' . $tvalue['larvol_id'] . '">';
			if($tvalue['viewcount'] != '' && $tvalue['viewcount'] != NULL && $tvalue['viewcount'] > 0)
			{
				$outputStr .= '<span class="viewcount" title="Total views">' . $tvalue['viewcount'].'&nbsp;</span>&nbsp;'; 
			}
			$outputStr .= '</font>'; 
			$outputStr .= htmlformat($tvalue['brief_title']) . '</a></div></td>';
			
			
			//enrollment column
			$attr = ' ';
			$highlightFlag = true;
			if($globalOptions['onlyUpdates'] != "yes")
			{
				$prevValue = substr($tvalue['edited']['enrollment'], 16);
				$highlightFlag = getDifference($prevValue, $tvalue['NCT/enrollment']);
			}
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && array_key_exists('enrollment', $tvalue['edited']) && $highlightFlag) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['enrollment'];
				}
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
				elseif($tvalue['manual_enrollment'] != '' && $tvalue['manual_enrollment'] !== NULL)
				{
					if($tvalue['enrollment_prev'] == $tvalue['enrollment'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['enrollment_prev'] != '' && $tvalue['enrollment_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['enrollment_prev'];
					}
				}
			}
			else
			{
				if($tvalue['manual_enrollment'] != '' && $tvalue['manual_enrollment'] !== NULL)
				{
					if($tvalue['enrollment_prev'] == $tvalue['enrollment'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['enrollment_prev'] != '' && $tvalue['enrollment_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['enrollment_prev'];
					}
				}
				elseif(!empty($tvalue['edited']) && array_key_exists('enrollment', $tvalue['edited']) && $highlightFlag) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['enrollment'];
				}
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
			}
			$outputStr .= '<td nowrap="nowrap" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">'
						. $tvalue["enrollment"] . '</div></td>';	
			
			
			//region column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if($tvalue['new'] == 'y')
				{
					$attr = '" title="New record';
				}
				elseif($tvalue['manual_region'] != '' && $tvalue['manual_region'] !== NULL)
				{
					if($tvalue['region_prev'] == $tvalue['region'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['region_prev'] != '' && $tvalue['region_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['region_prev'];
					}
				}
			}
			else
			{
				if($tvalue['manual_region'] != '' && $tvalue['manual_region'] !== NULL)
				{
					if($tvalue['region_prev'] == $tvalue['region'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['region_prev'] != '' && $tvalue['region_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['region_prev'];
					}
				}
				elseif($tvalue['new'] == 'y')
				{
					$attr = '" title="New record';
				}
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">' 
						. (($tvalue['region'] != '' && $tvalue['region'] !== NULL) ? $tvalue['region'] : '&nbsp;') . '</div></td>';	
						
			
			//intervention name column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && array_key_exists('intervention_name', $tvalue['edited']))
				{
					$attr = ' highlight" title="' . $tvalue['edited']['intervention_name'];
				} 
				else if($tvalue['new'] == 'y')
				{
					$attr = '" title="New record';
				}
				elseif($tvalue['manual_intervention_name'] != '' && $tvalue['manual_intervention_name'] !== NULL)
				{
					if($tvalue['intervention_name_prev'] == $tvalue['intervention_name'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['intervention_name_prev'] != '' && $tvalue['intervention_name_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['intervention_name_prev'];
					}
				}
			}
			else
			{
				if($tvalue['manual_intervention_name'] != '' && $tvalue['manual_intervention_name'] !== NULL)
				{
					if($tvalue['intervention_name_prev'] == $tvalue['intervention_name'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['intervention_name_prev'] != '' && $tvalue['intervention_name_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['intervention_name_prev'];
					}
				}
				elseif(!empty($tvalue['edited']) && array_key_exists('intervention_name', $tvalue['edited']) && str_replace('Previous value: ', '', $tvalue['edited']['intervention_name']) <> $tvalue['intervention_name'])
				{
					$attr = ' highlight" title="' . $tvalue['edited']['intervention_name'];
				} 
				else if($tvalue['new'] == 'y')
				{
					$attr = '" title="New record';
				}
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<div class="rowcollapse">' . $tvalue['intervention_name'] . '</div></td>';	
						
			
			//collaborator and sponsor column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && (array_key_exists('collaborator', $tvalue['edited']) 
				|| array_key_exists('lead_sponsor', $tvalue['edited']))) 
				{
					$attr = ' highlight" title="';
					if(array_key_exists('lead_sponsor', $tvalue['edited']))
					{
						$attr .= $tvalue['edited']['lead_sponsor'];
					}
					if(array_key_exists('lead_sponsor', $tvalue['edited']) && array_key_exists('collaborator', $tvalue['edited']))
					{
						$attr .=  ', ';
					}
					if(array_key_exists('collaborator', $tvalue['edited'])) 
					{
						$attr .= $tvalue['edited']['collaborator'];
					}
					$attr .= '';
				} 
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
				elseif(($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL) 
				|| ($tvalue['manual_collaborator'] != '' && $tvalue['manual_collaborator'] !== NULL))
				{
					if($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL)
					{
						if($tvalue['lead_sponsor_prev'] == $tvalue['lead_sponsor'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						elseif($tvalue['lead_sponsor_prev'] != '' && $tvalue['lead_sponsor_prev'] !== NULL)
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['lead_sponsor_prev'];
						}
					}
					else
					{
						if($tvalue['collaborator_prev'] == $tvalue['collaborator'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						elseif($tvalue['collaborator_prev'] != '' && $tvalue['collaborator_prev'] !== NULL)
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['collaborator_prev'];
						}
					}
				}
			}
			else
			{
				if(($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL) 
				|| ($tvalue['manual_collaborator'] != '' && $tvalue['manual_collaborator'] !== NULL))
				{
					if($tvalue['manual_lead_sponsor'] != '' && $tvalue['manual_lead_sponsor'] !== NULL)
					{
						if($tvalue['lead_sponsor_prev'] == $tvalue['lead_sponsor'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						elseif($tvalue['lead_sponsor_prev'] != '' && $tvalue['lead_sponsor_prev'] !== NULL)
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['lead_sponsor_prev'];
						}
					}
					else
					{
						if($tvalue['collaborator_prev'] == $tvalue['collaborator'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						elseif($tvalue['collaborator_prev'] != '' && $tvalue['collaborator_prev'] !== NULL)
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['collaborator_prev'];
						}
					}
				}
				elseif(!empty($tvalue['edited']) && (array_key_exists('collaborator', $tvalue['edited']) 
				|| array_key_exists('lead_sponsor', $tvalue['edited'])) && ( str_replace('Previous value: ', '', $tvalue['edited']['lead_sponsor']) <> $tvalue['lead_sponsor'] or str_replace('Previous value: ', '', $tvalue['edited']['collaborator']) <> $tvalue['collaborator'] )) 
				{
					$attr = ' highlight" title="';
					if(array_key_exists('lead_sponsor', $tvalue['edited']))
					{
						$attr .= $tvalue['edited']['lead_sponsor'];
					}
					if(array_key_exists('lead_sponsor', $tvalue['edited']) && array_key_exists('collaborator', $tvalue['edited']))
					{
						$attr .=  ', ';
					}
					if(array_key_exists('collaborator', $tvalue['edited'])) 
					{
						$attr .= $tvalue['edited']['collaborator'];
					}
					$attr .= '';
				} 
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">' . $tvalue['lead_sponsor'];
			if($tvalue['lead_sponsor'] != '' && $tvalue['collaborator'] != ''
			&& $tvalue['lead_sponsor'] != NULL && $tvalue['collaborator'] != NULL)
			{
				$outputStr .= ', ';
			}
			$outputStr .= $tvalue["collaborator"] . '</div></td>';
						
						
			//overall status column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && array_key_exists('overall_status', $tvalue['edited'])) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['overall_status'];
				} 
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record' ;
				} 
				elseif($tvalue['manual_overall_status'] != '' && $tvalue['manual_overall_status'] !== NULL)
				{
					if($tvalue['overall_status_prev'] == $tvalue['overall_status'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['overall_status_prev'] != '' && $tvalue['overall_status_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['overall_status_prev'];
					}
				}
			}
			else
			{
				if($tvalue['manual_overall_status'] != '' && $tvalue['manual_overall_status'] !== NULL)
				{
					if($tvalue['overall_status_prev'] == $tvalue['overall_status'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['overall_status_prev'] != '' && $tvalue['overall_status_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['overall_status_prev'];
					}
				}
				elseif(!empty($tvalue['edited']) && array_key_exists('overall_status', $tvalue['edited']) && str_replace('Previous value: ', '', $tvalue['edited']['overall_status']) <> $tvalue['overall_status']) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['overall_status'];
				} 
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record' ;
				} 
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">' 
						. (($tvalue['overall_status'] != '' && $tvalue['overall_status'] !== NULL) ? $tvalue['overall_status'] : '&nbsp;')
						. '</div></td>';
						
						
			//condition column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && array_key_exists('condition', $tvalue['edited'])) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['condition'];
				} 
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
				elseif($tvalue['manual_condition'] != '' && $tvalue['manual_condition'] !== NULL)
				{
					if($tvalue['condition_prev'] == $tvalue['condition'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['condition_prev'] != '' && $tvalue['condition_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['condition_prev'];
					}
				}
			}
			else
			{
				if($tvalue['manual_condition'] != '' && $tvalue['manual_condition'] !== NULL)
				{
					if($tvalue['condition_prev'] == $tvalue['condition'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['condition_prev'] != '' && $tvalue['condition_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['condition_prev'];
					}
				}
				elseif(!empty($tvalue['edited']) && array_key_exists('condition', $tvalue['edited']) && str_replace('Previous value: ', '', $tvalue['edited']['condition']) <> $tvalue['condition']) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['condition'];
				} 
				else if($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
						. '<div class="rowcollapse">' . $tvalue['condition'] . '</div></td>';
						
			
			$borderLeft = '';	
			if(!empty($tvalue['edited']) && array_key_exists('start_date', $tvalue['edited']))
			{
				$borderLeft = 'startdatehighlight';
			}
					
			//end date column
			$attr = ' ';
			$borderRight = '';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && array_key_exists('end_date', $tvalue['edited'])) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['end_date'];
					$borderRight = 'border-right-color:red;';
				} 
				elseif($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}	
				elseif($tvalue['manual_end_date'] != '' && $tvalue['manual_end_date'] !== NULL)
				{
					if($tvalue['end_date_prev'] == $tvalue['end_date'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['end_date_prev'] != '' && $tvalue['end_date_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['end_date_prev'];
					}
				}
			}
			else

			{
				if($tvalue['manual_end_date'] != '' && $tvalue['manual_end_date'] !== NULL)
				{
					if($tvalue['end_date_prev'] == $tvalue['end_date'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['end_date_prev'] != '' && $tvalue['end_date_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['end_date_prev'];
					}
				}
				elseif(!empty($tvalue['edited']) && array_key_exists('end_date', $tvalue['edited']) && str_replace('Previous value: ', '', $tvalue['edited']['end_date']) <> $tvalue["end_date"]) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['end_date'];
					$borderRight =  'border-right-color:red;';
				} 
				elseif($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}	
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">'; 
			if($tvalue["end_date"] != '' && $tvalue["end_date"] != NULL && $tvalue["end_date"] != '0000-00-00') 
			{
				$outputStr .= date('m/y',strtotime($tvalue["end_date"]));
			} 
			else 
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</div></td>';
			
			
			//phase column
			$attr = ' ';
			if(isset($tvalue['manual_is_sourceless']))
			{
				if(!empty($tvalue['edited']) && array_key_exists('phase', $tvalue['edited'])) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['phase'];
				} 
				elseif($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
				elseif($tvalue['manual_phase'] != '' && $tvalue['manual_phase'] !== NULL)
				{
					if($tvalue['phase_prev'] == $tvalue['phase'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['phase_prev'] != '' && $tvalue['phase_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['phase_prev'];
					}
				}
			}
			else
			{
				if($tvalue['manual_phase'] != '' && $tvalue['manual_phase'] !== NULL)
				{
					if($tvalue['phase_prev'] == $tvalue['phase'])
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($tvalue['phase_prev'] != '' && $tvalue['phase_prev'] !== NULL)
					{
						$attr = ' manual" title="Manual curation. Original value: ' . $tvalue['phase_prev'];
					}
				}
				elseif(!empty($tvalue['edited']) && array_key_exists('phase', $tvalue['edited']) && ( str_replace('Previous value: ', '', trim($tvalue['edited']['phase'])) <> trim($tvalue['phase'])) ) 
				{
					$attr = ' highlight" title="' . $tvalue['edited']['phase'];
				} 
				elseif($tvalue['new'] == 'y') 
				{
					$attr = '" title="New record';
				}
			}
			
			if($tvalue['phase'] == 'N/A' || $tvalue['phase'] == '' || $tvalue['phase'] === NULL)
			{
				$phase = 'N/A';
				$phaseColor = $this->phaseValues['N/A'];
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($tvalue['phase']));
				$phaseColor = $this->phaseValues[$phase];
			}
			$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' 
						. '<div class="rowcollapse">' . $phase . '</div></td>';				
			
			$startMonth = date('m',strtotime($tvalue['start_date']));
			$startYear = date('Y',strtotime($tvalue['start_date']));
			$endMonth = date('m',strtotime($tvalue['end_date']));
			$endYear = date('Y',strtotime($tvalue['end_date']));
			
			if($startYear < $currentYear)
			{
				$outputStr .= '<td class="' . $borderLeft . '">&nbsp;</td>';
			}
			else
			{
				$outputStr .= '<td>&nbsp;</td>';
			}

			//rendering project completion gnatt chart
			$outputStr .= $this->trialGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, 
				$tvalue['start_date'], $tvalue['end_date'], $phaseColor, $borderRight, $borderLeft);
				
			$outputStr .= '</tr>';	
			
			//rendering matched upms
			if(isset($tvalue['upms']) && !empty($tvalue['upms'])) 
			{
				foreach($tvalue['upms'] as $mkey => $mvalue) 
				{ 
					$incViewCount = true;
					$str = '';
					$diamond = '';
					$resultImage = '';
	
					$stMonth = date('m', strtotime($mvalue['start_date']));
					$stYear = date('Y', strtotime($mvalue['start_date']));
					$edMonth = date('m', strtotime($mvalue['end_date']));
					$edYear = date('Y', strtotime($mvalue['end_date']));
					$upmTitle = htmlformat($mvalue['event_description']);
					
					$upmBorderLeft = '';
					if(isset($mvalue['edited']) && $mvalue['edited']['field'] == 'start_date')
					{
						$upmBorderLeft = 'startdatehighlight';
					}
					
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
					
					if(!$loggedIn && !$this->liLoggedIn())
					{
						$mvalue['event_link'] = NULL;
					}
					
					$outputStr .= '<td ';
					if($stYear < $currentYear)
					{
						$outputStr .= 'class="' . $upmBorderLeft . '"';
					}
					
					$outputStr .= ' style="text-align:center;vertical-align:middle;' . (($mkey != 0) ? 'border-top:0px;' : '') . '">';
					
					$outputStr .= '<div ' . $upmTitle . '>';
					if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
					{
						if(!$loggedIn && !$this->liLoggedIn())
						{
							$mvalue['result_link'] = NULL;
						}
						
						if((isset($mvalue['edited']) && $mvalue['edited']['field'] == 'result_link') || ($mvalue['new'] == 'y')) 
							$imgColor = 'red';
						else 
							$imgColor = 'black'; 
						
						
						if($mvalue['result_link'] != '' && $mvalue['result_link'] !== NULL)
						{
							$outputStr .= '<a href="' . $mvalue['result_link'] . '" target="_blank">';
							if(strcasecmp($mvalue['event_type'], 'Clinical Data') == 0)
							{
								$outputStr .= '<img src="images/' . $imgColor . '-diamond.png" alt="Diamond"';
							}
							else if($mvalue['status'] == 'Cancelled')
							{
								$outputStr .= '<img src="images/' . $imgColor . '-cancel.png" alt="Cancel"';
							}
							else
							{
								$outputStr .= '<img src="images/' . $imgColor . '-checkmark.png" alt="Checkmark"';
							}
							$outputStr .= ' style="padding-top: 3px;" border="0" onclick="INC_ViewCount('.$tvalue['larvol_id'].')" /></a>';
						}
						else
						{
							if(strcasecmp($mvalue['event_type'], 'Clinical Data') == 0)
							{
								$outputStr .= '<img src="images/' . $imgColor . '-diamond.png" alt="Diamond"';
							}
							else if($mvalue['status'] == 'Cancelled')

							{
								$outputStr .= '<img src="images/' . $imgColor . '-cancel.png" alt="Cancel"';
							}
							else
							{
								$outputStr .= '<img src="images/' . $imgColor . '-checkmark.png" alt="Checkmark"';
							}
							$outputStr .= ' style="padding-top: 3px;" border="0" onclick="INC_ViewCount('.$tvalue['larvol_id'].')" />';
						}
					}
					else if($mvalue['status'] == 'Pending')
					{
						$icon = '<img src="images/hourglass.png" alt="Hourglass"  border="0" onclick="INC_ViewCount(' . $tvalue['larvol_id'] . ')" />';
						if($mvalue['event_link'] != '' && $mvalue['event_link'] !== NULL)
						{	
							$outputStr .= '<a href="' . $mvalue['event_link'] . '" target="_blank">' . $icon . '</a>';
						}
						else
						{
							$outputStr .= $icon;
						}
					}
					else
					{
						$outputStr .= '&nbsp;';
					}
					$outputStr .= '</div></td>';
					
					$upmBorderRight = '';
					if(isset($mvalue['edited']) && $mvalue['edited']['field'] == 'end_date')
					{
						$upmBorderRight = 'border-right-color:red;';
					}
					
					//rendering upm (upcoming project completion) chart
					$outputStr .= $this->upmGnattChart($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'],
					$mvalue['end_date'], $mvalue['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft, $tvalue['larvol_id'], $incViewCount);
					$outputStr .= '</tr>';
				}
			}
			
			++$counter;
		}
		
		//if the end sections has 0 trials
		if($globalOptions['page'] == $totalPages)
		{
			$startIndex = array_search($Trials[count($Trials)-1]['sectionid'], $Ids)+1;
			$endIndex = count($Ids);
			if($startIndex < $endIndex)
			{
				for($i=$startIndex;$i<$endIndex;$i++)
				{
					$index = $Ids[$i];
					$sectionHeader = $Values['Data'][$index]['sectionHeader'];
					$naUpms = $Values['Data'][$index]['naUpms'];
					
					//Rendering Upms
					$outputStr .= $this->dUnmatchedUpms($globalOptions, $ottType, $sectionHeader, $naUpms);
				}
			}
		}
		
		return $outputStr;
	}
	
	function trialGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, $bgColor, $borderRight, $borderLeft)
	{
		$outputStr = '';
		$hoverText = '';
		$bgColor = 'background-color:' . $bgColor . ';';

		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00'))
		{
			$hoverText = '';
		}
		elseif($startDate == '' || $startDate === NULL || $startDate == '0000-00-00')
		{
			$hoverText = ' title="' . date('M Y', strtotime($endDate)) . '" ';
		}
		elseif($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')
		{
			$hoverText = ' title="' . date('M Y', strtotime($startDate)) . '" ';
		}
		elseif($endDate < $startDate)
		{
			$hoverText = ' title="' . date('M Y', strtotime($endDate)) . '" ';
		}
		else
		{
			$hoverText = ' title="' . date('M Y', strtotime($startDate)) . ' - ' . date('M Y', strtotime($endDate)) . '" ';
		}
		
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
				$outputStr .= '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td  style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($endYear == $currentYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'. ($st*2) .'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor. $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:' . ((12-($st+1))*2) . 'px;" colspan="' . (12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td  style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			}
			else if($endYear == $secondYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:' . ($st*2) . 'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>'
							. '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';
			}
		}
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')
		{
			$st = $startMonth-1;
			if($startYear < $currentYear)
			{
				$outputStr .= '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			}
			else if($startYear == $currentYear) 
			{ 
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($startYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			}
			else if($startYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($startYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>'
							. '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';

			}
		} 
		else if($endDate < $startDate) 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td  style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($endYear == $currentYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'. ($st*2) .'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td  style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			}
			else if($endYear == $secondYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>' 
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>'
							. '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';
			}
		} 
		else if($startYear < $currentYear) 
		{
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $currentYear) 
			{
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:30px;' . $bgColor . $borderRight . '" colspan="15" ' . $hoverText . '>&nbsp;</td>' 
								. '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				} 
				else 
				{ 
					$outputStr .= '<td style="width:'.(($endMonth+3)*2).'px;' . $bgColor . $borderRight . '" colspan="' 
								. ($endMonth+3) . '" ' . $hoverText . '>&nbsp;</td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				}
			}
			else if($endYear == $secondYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:'.(27*2).'px;' . $bgColor . $borderRight . '" colspan="27" ' 
								. $hoverText . '>&nbsp;</td>'
								. '<td  style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
				} 
				else 
				{
					$outputStr .= '<td style="width:'.((15+$endMonth)*2).'px;' . $bgColor . $borderRight 
								. '" colspan="' . (15+$endMonth) . '" ' . $hoverText . '>&nbsp;</td>'
								. '<td style="width:' . ((12-$endMonth)*2) . 'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				}
			} 
			else if($endYear == $thirdYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td style="width:'.(39*2).'px;' . $bgColor . $borderRight . '" colspan="39" ' . $hoverText . '>&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				} 
				else 
				{
					$outputStr .= '<td style="width:'.((27+$endMonth)*2).'px;' . $bgColor . $borderRight 
								. '" colspan="' . (27+$endMonth) . '" ' . $hoverText . '>&nbsp;</td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '">&nbsp;</td>'
								. '<td style="width:6px;" colspan="3">&nbsp;</td>';
				}
			} 
			else if($endYear > $thirdYear)
			{ 
				$outputStr .= '<td colspan="42" style="width:'.(42*2).'px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';		
			}	
		} 
		else if($startYear == $currentYear) 
		{	
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px;' . $bgColor . $borderRight . '" colspan="' . $val . '" ' . $hoverText . '>&nbsp;</td>'
								. (((12 - ($st+$val)) != 0) ? '<td style="width:' . ((12 - ($st+$val))*2) . 'px;" colspan="' .(12 - ($st+$val)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
								. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>'
							. '<td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $secondYear)
			{ 
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				if($val != 0)
				{
					$outputStr .= '<td style="width:'.($val*2).'px;' . $bgColor . $borderRight . '" colspan="' . $val . '" ' . $hoverText . '>&nbsp;</td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');			
				}
				$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td><td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear == $thirdYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td  style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td  style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px;' . $bgColor . $borderRight . '" colspan="' . $val . '" ' . $hoverText . '>&nbsp;</td>'
								. (((36 - ($val+$st)) != 0) ? '<td style="width:'.((36-($val+$st))*2).'px;" colspan="' .(36 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
								. (((36 - (1+$st)) != 0) ? '<td style="width:'.((36-(1+$st))*2).'px;" colspan="' .(36 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear)
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" colspan="3" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td colspan="' .(39 - $st) . '" style="width:'.((39-$st)*2).'px;' . $bgColor . $borderRight . '" ' 
							. $hoverText . '>&nbsp;</td>';		
			}
		}
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">' . '&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}			
				if($val != 0) 
				{ 
					$outputStr .= '<td style="width:'.($val*2).'px;' . $bgColor . $borderRight . '" colspan="' . $val . '" ' . $hoverText . '>&nbsp;</td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{ 
					$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '></td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="12" style="width:24px;">&nbsp;</td><td colspan="3" style="width:6px;">&nbsp;</td>';		
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td colspan="3" style="width:6px;">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td colspan="12" style="width:24px;">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td colspan="12" style="width:24px;" class="' . $borderLeft . '">&nbsp;</td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px;' . $bgColor . $borderRight . '" colspan="' . $val . '" ' . $hoverText . '>&nbsp;</td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';

			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';

				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td colspan="' .(27 - $st) . '" style="width:' . ((27-$st)*2) . 'px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';		
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px;' . $bgColor . $borderRight . '" colspan="' . $val . '" ' . $hoverText . '>&nbsp;</td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} 
				else 
				{
					$outputStr .= '<td style="width:2px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$outputStr .= '<td colspan="3" style="width:6px;">&nbsp;</td>';
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>'
							. '<td style="width:24px;" colspan="12">&nbsp;</td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
								. '<td style="width:'.($st*2).'px;" colspan="' . $st . '" class="' . $borderLeft . '">&nbsp;</td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>';
				}
				$outputStr .= '<td colspan="' .(15 - $st) . '" style="width:'.((15-$st)*2).'px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';		
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3">&nbsp;</td>';
			$outputStr .= '<td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td style="width:24px;" colspan="12">&nbsp;</td>'
						. '<td style="width:24px;" colspan="12" class="' . $borderLeft . '">&nbsp;</td>'
						. '<td colspan="3" style="width:6px;' . $bgColor . $borderRight . '" ' . $hoverText . '>&nbsp;</td>';	
		} 
		return $outputStr;
	}
	
	function upmGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, $startDate, $endDate, $upmLink, $upmTitle, $upmBorderRight, $upmBorderLeft, $larvolId = NULL, $incViewCount = false)
	{	
		$outputStr = '';
		$bgColor = 'background-color:#9966FF;';
		$anchorTag = ($upmLink != '' &&  $upmLink !== NULL) ? '<a href="' . $upmLink . '" target="_blank">&nbsp;</a>' : '&nbsp;' ;
		
		if($incViewCount === true && $larvolId !== NULL)
		{
			$incViewCountLink = ' onclick="INC_ViewCount(' . $larvolId . ')" ';
		}
		
		$hoverText = '';
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00'))
		{
			$hoverText = '';
		}
		elseif($startDate == '' || $startDate === NULL || $startDate == '0000-00-00')
		{
			$hoverText = date('M Y', strtotime($endDate));
		}
		elseif($endDate == '' || $endDate === NULL || $endDate == '0000-00-00')
		{
			$hoverText = date('M Y', strtotime($startDate));
		}
		elseif($endDate < $startDate)
		{
			$hoverText = date('M Y', strtotime($endDate));
		}
		else
		{
			$hoverText = date('M Y', strtotime($startDate)) . ' - ' . date('M Y', strtotime($endDate));
		}
		$upmTitle = $hoverText . ' ' . $upmTitle;
		
		if(($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') && ($endDate == '' || $endDate === NULL || $endDate == '0000-00-00'))
		{
			$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">'. $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
						. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
		} 
		else if($startDate == '' || $startDate === NULL || $startDate == '0000-00-00') 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td colspan="3" ' . $incViewCountLink . ' style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. ' <div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			} 
			else if($endYear == $currentYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			}
			else if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12" class="' . $upmBorderLeft . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			}
		} 
		else if($endDate == '' || $endDate === NULL || $endDate == '0000-00-00') 
		{
			$st = $startMonth-1;
			if($startYear < $currentYear) 
			{
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			} 
			else if($startYear == $currentYear) 
			{ 
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			} 
			else if($startYear == $secondYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">'
								. $anchorTag . '</div></td>';
				}
				$outputStr .='<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			} 
			else if($startYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			} 
			else if($startYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			}
		} 
		else if($endDate < $startDate) 
		{
			$st = $endMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	

			} 
			else if($endYear == $currentYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:' . ($st*2) . 'px;"  class="' . $upmBorderLeft . '" colspan="' . $st . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			}
			else if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '')
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
			} 
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			}
		} 
		else if($startYear < $currentYear) 

		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear < $currentYear) 
			{
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			}
			else if($endYear == $currentYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.(($endMonth+3)*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . ($endMonth+3) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				} 
				else 
				{ 
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.(($endMonth+3)*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . ($endMonth+3) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear == $secondYear) 
			{ 
				if($endMonth == 12) 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.(27*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="27">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				} 
				else 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.(($endMonth+15)*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . (15+$endMonth) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear == $thirdYear)
			{ 
				if($endMonth == 12)
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.(39*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="39">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.((27+$endMonth)*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . (27+$endMonth) . '">' 
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.((12-$endMonth)*2).'px;" colspan="' . (12-$endMonth) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="42" style="width:'.(42*2).'px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';		
			}	
		} 
		else if($startYear == $currentYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $currentYear) 
			{
				if($st != 0)
				{
					$outputStr .=  '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '" ><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .=  '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				if($val != 0)
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.($val*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . $val . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((12 - ($st+$val)) != 0) ? '<td style="width:'.((12-($st+$val))*2).'px;" colspan="' .(12 - ($st+$val)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((12 - ($st+1)) != 0) ? '<td style="width:'.((12-($st+1))*2).'px;" colspan="' .(12 - ($st+1)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			}

			else if($endYear == $secondYear) 
			{ 
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.($val*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . $val . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			} 
			else if($endYear == $thirdYear) 
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '">' . '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td style="width:'.($val*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . $val . '">'
								. '<div title="' . $upmTitle . '">'. $anchorTag . '</div></td>'
								. (((36 - ($val+$st)) != 0) ? '<td style="width:'.((36-($val+$st))*2).'px;" colspan="' .(36 - ($val+$st)) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '') ;
				} 
				else 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((36 - (1+$st)) != 0) ? '<td style="width:'.((36-(1+$st))*2).'px;" colspan="' .(36 - (1+$st)) . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '') ;			
				}
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			} 
			else if($endYear > $thirdYear)
			{
				if($st != 0)
				{
					$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:6px;" class="' . $upmBorderLeft . '" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="' .(39 - $st) . '" style="width:'.((39-$st)*2).'px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';		
			}
		} 
		else if($startYear == $secondYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;
			if($endYear == $secondYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.($val*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . $val . '">' . '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{

					$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">' . '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');
				}
				$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';		
			} 
			else if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.($val*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . $val . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((24 - ($val+$st)) != 0) ? '<td style="width:'.((24-($val+$st))*2).'px;" colspan="' .(24 - ($val+$st)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .=  '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
								. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. (((24 - (1+$st)) != 0) ? '<td style="width:'.((24-(1+$st))*2).'px;" colspan="' .(24 - (1+$st)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:6px;" colspan="3"><div ' . $upmTitle . '">' . $anchorTag . '</div></td>';
			}
			else if($endYear > $thirdYear)
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="' .(27 - $st) . '" style="width:'.((27-$st)*2).'px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';		
			}
		} 
		else if($startYear == $thirdYear) 
		{
			$val = getColspan($startDate, $endDate);
			$st = $startMonth-1;	
			if($endYear == $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				if($val != 0) 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:'.($val*2).'px; ' . $bgColor . $upmBorderRight . '" colspan="' . $val . '">'
								. '<div title="' . $upmTitle .'">' . $anchorTag . '</div></td>'
								. (((12 - ($val+$st)) != 0) ? '<td style="width:'.((12-($val+$st))*2).'px;" colspan="' .(12 - ($val+$st)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');
				} 
				else 
				{
					$outputStr .= '<td ' . $incViewCountLink . ' style="width:2px; ' . $bgColor . $upmBorderRight . '">'
								. '<div title="' . $upmTitle .'">' . $anchorTag . '</div></td>'
								. (((12 - (1+$st)) != 0) ? '<td style="width:'.((12-(1+$st))*2).'px;" colspan="' .(12 - (1+$st)) . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>' : '');			
				}
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			} 
			else if($endYear > $thirdYear) 
			{
				$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
							. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">'. $anchorTag . '</div></td>';
				if($st != 0)
				{
					$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
								. '<td style="width:'.($st*2).'px;" class="' . $upmBorderLeft . '" colspan="' . $st . '"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				else
				{
					$outputStr .= '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
				}
				$outputStr .= '<td ' . $incViewCountLink . ' colspan="' . (15 - $st) . '" style="width:'.((15-$st)*2).'px; ' . $bgColor . $upmBorderRight . '">'
							. '<div title="' . $upmTitle . '">'. $anchorTag . '</div></td>';
			}
		} 
		else if($startYear > $thirdYear) 
		{
			$outputStr .= '<td style="width:6px;" colspan="3"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';
			$outputStr .= '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
						. '<td style="width:24px;" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
						. '<td style="width:24px;" class="' . $upmBorderLeft . '" colspan="12"><div title="' . $upmTitle . '">' . $anchorTag . '</div></td>'
						. '<td ' . $incViewCountLink . ' colspan="3" style="width:6px; ' . $bgColor . $upmBorderRight . '">'
						. '<div title="' . $upmTitle . '">' . $anchorTag . '</div></td>';	
		}
		
		return $outputStr;	
	}
	
	function displayUnMatchedUpms($ottType, $loggedIn, $naUpmIndex, $naUpms)
	{
		global $now;
		$outputStr = '';
		$currentYear = date('Y');
		$secondYear = (date('Y')+1);
		$thirdYear = (date('Y')+2);
		
		$cntr = 0;
		
		$display = 'style="display: none;"';
		if($ottType == 'indexed' || $ottType == 'rowstacked')
		{
			$display = 'style="display: table-row;"';
		}
		
		foreach($naUpms as $key => $value)
		{
			$attr = '';
			$resultImage = '';
			$class = 'class = "upms ' . $naUpmIndex . '" ';
			$titleLinkColor = '';
			$upmTitle = htmlformat($value['event_description']);
			
			$upmBorderLeft = '';
			if(isset($value['edited']) && $value['edited']['field'] == 'start_date')
			{
				$upmBorderLeft = 'startdatehighlight';
			}
			
			//Highlighting the whole row in case of new trials
			if($value['new'] == 'y') 
			{
				$class = 'class="upms newtrial ' . $naUpmIndex . '" ';
			}
			
			if($value['redtag'] != '' && $value['redtag'] !== NULL)
			{
				$eventStr = $value['redtag'];
				if($value['event_description'] != '' && $value['event_description'] !== NULL)
				{
					$eventStr .= ': ' . $value['event_description'];
				}
				
				$value['event_description'] = $eventStr;
			}
			
			//rendering unmatched upms
			$outputStr .= '<tr ' . $class . ' ' . $display . '>';
			
			
			//field upm-id
			$title = '';
			$attr = '';	
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
			
			if(!$loggedIn && !$this->liLoggedIn())
			{
				$value['event_link'] = NULL;
			}
			
			//field upm event description
			$title = '';
			$attr = '';	
			if(isset($value['edited']) && ($value['edited']['field'] == 'event_description')) 
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
			else if(isset($value['edited']) && ($value['edited']['field'] == 'event_link')) 
			{
				$titleLinkColor = 'style="color:#FF0000;"';
				$attr = ' highlight'; 
				
				if($value['edited']['event_link'] != '' && $value['edited']['event_link'] !== NULL)
				{
					$title = ' title="Previous value: '. $value['edited']['event_link'] . '" '; 
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
			$outputStr .= '<td colspan="5" class="' .  $attr . '" ' . $title . '><div class="rowcollapse">';
			if($value['event_link'] !== NULL && $value['event_link'] != '') 
			{
				$outputStr .= '<a ' . $titleLinkColor . ' href="' . $value['event_link'] . '" target="_blank">' . $value['event_description'] . '</a>';
			} 
			else 
			{
				$outputStr .= $value['event_description'];
			}
			$outputStr .= '</div></td>';
			
			
			//field upm status
			$title = '';
			$attr = '';	
			if($value['new'] == 'y')
			{
				$title = ' title = "New record" ';
			}
			$outputStr .= '<td ' . $title . '><div class="rowcollapse">' . $value['status'] . '</div></td>';

		
			//field upm event type
			$title = '';
			$attr = '';	
			if(isset($value['edited']) && ($value['edited']['field'] == 'condition')) 
			{

				$attr = ' highlight'; 
				if($value['edited']['condition'] != '' && $value['edited']['condition'] !== NULL)
				{
					$title = ' title="Previous value: '. $value['edited']['condition'] . '" '; 
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
			$outputStr .= '<td class="' . $attr . '" ' . $title . '><div class="rowcollapse">' . $value['condition'] . '</div></td>';
			
			
			//field upm end date
			$title = '';
			$attr = '';	
			$upmBorderRight = '';
			
			if(isset($value['edited']) && ($value['edited']['field'] == 'end_date'))
			{
				$attr = ' highlight';
				$upmBorderRight = 'border-right-color:red;';
				
				if($value['edited']['end_date'] != '' && $value['edited']['end_date'] !== NULL)
				{
					$title = ' title="Previous value: ' . $value['edited']['end_date'] . '" '; 
				}
				else 
				{
					$title = ' title="No Previous value" ';
				}
			} 
			else if(isset($value['edited']) && ($value['edited']['field'] == 'end_date_type'))
			{
				$attr = ' highlight';
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
			
			$outputStr .= (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
								date('m/y',strtotime($value['end_date'])) : '&nbsp;');
								
			$outputStr .= '</div></td><td><div class="rowcollapse">&nbsp;</div></td>';
			
			
			//field upm result 
			$stYear = date('Y',strtotime($value['start_date']));
			$stMonth = date('m',strtotime($value['start_date']));
			$outputStr .= '<td style="text-align:center;vertical-align:middle;" ';
			if($stYear < $currentYear)
			{
				$outputStr .= ' class="' . $upmBorderLeft . '" ';
			}
			$outputStr .= '>';
			
			if($value['result_link'] != '' && $value['result_link'] !== NULL)
			{
				if(!$loggedIn && !$this->liLoggedIn())
				{
					$value['result_link'] = NULL;
				}
							
				if((isset($value['edited']) && $value['edited']['field'] == 'result_link') || ($value['new'] == 'y')) 
						$imgColor = 'red';
				else 
					$imgColor = 'black'; 
				
				$outputStr .= '<div title="' . $upmTitle . '">';
				if($value['result_link'] != '' && $value['result_link'] !== NULL)
				{
					$outputStr .= '<a href="' . $value['result_link'] . '" ' . $target . '>';
					if(strcasecmp($value['event_type'], 'Clinical Data') == 0)
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
					$outputStr .= '</a>';
				}
				else
				{
					if(strcasecmp($value['event_type'], 'Clinical Data') == 0)
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
				}
				$outputStr .= '</div>';
			}
			else if($value['status'] == 'Pending')
			{
				$outputStr .= '<div title="' . $upmTitle . '">';
				if($value['event_link'] != '' && $value['event_link'] !== NULL)
				{
					$outputStr .= '<a href="' . $value['event_link'] . '" target="_blank">'
								. '<img src="images/hourglass.png" alt="Hourglass"  border="0" /></a>';
				}
				else
				{
					$outputStr .= '<img src="images/hourglass.png" alt="Hourglass"  border="0" />';
				}
				$outputStr .= '</div>';
			}
			else
			{
				$outputStr .= '&nbsp;';
			}
			$outputStr .= '</td>';		
			
			
			//upm gnatt chart
			$outputStr .= $this->upmGnattChart($stMonth, $stYear, 
							date('m',strtotime($value['end_date'])), date('Y',strtotime($value['end_date'])), $currentYear, $secondYear, $thirdYear, 
							$value['start_date'], $value['end_date'], $value['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft);
			
			$outputStr .= '</tr>';
		}
		
		return $outputStr;
	}
	
	function replaceRedundantAcroynm($Acroynm, $briefTitle)
	{
		$extract = substr($briefTitle, 0, strlen($Acroynm));
		
		if($extract === $Acroynm)
		{
			$output = $briefTitle;
		}
		else
		{
			$output = $Acroynm . ' ' . $briefTitle;
		}
		
		return $output;
	}
	
	function liLoggedIn()
	{
		global $li_user;
		if( isset($_COOKIE['li_user']) or isset($_SESSION['li_user']) or (isset($li_user) and $li_user == 'YES') )
		{
			return true;
		}
		return false;
	}
}

function get_WhereString($data, $alias, $pd_alias, $ar_alias)
{
	$wheredatas = $data;
    if(empty($wheredatas))
	{
	   return '';
	}
	$wheres = array();
	$wcount = 0;
	$prevchain = ' ';
	try {

		foreach($wheredatas as $wh_key => $where_data)
		{
			$op_name = $where_data["opname"];
			$column_name = $where_data["columnname"];
			$column_value = $where_data["columnvalue"];
			$chain_name = $where_data["chainname"];
			if($column_name == '`product`' || $column_name == '`area`')
				$column_name='`id`';
				
			$op_string = getOperator($op_name, $column_name, $column_value);
			$wstr = " " . $prevchain . " " . $op_string;
			
			if($where_data["columnname"] == '`product`')
				$wstr = str_replace('%f', $pd_alias . "." . $column_name,$wstr);
			elseif($where_data["columnname"] == '`area`')
				$wstr = str_replace('%f', $ar_alias . "." . $column_name,$wstr);
			else
				$wstr = str_replace('%f', $alias . "." . $column_name,$wstr);
			
			$pos = strpos($op_string,'%s1');

			if($pos === false) {
				$wstr = str_replace('%s', $column_value, $wstr);
			}
			else {
				$xx = explode('and;endl', $column_value);//and;endl
				$wstr = str_replace('%s1', $xx[0],$wstr);
				$wstr = str_replace('%s2', $xx[1],$wstr);
			}
			$prevchain = $chain_name;
			$wheres[$wcount++] = $wstr;
		}
		$wherestr = implode(' ', $wheres);
		$pos = strpos($prevchain,'.');
		if($pos === true)
		{
			$wherestr .= str_replace('.', '', $prevchain);//if . is present remove it and empty
		}
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $wherestr;
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
		return true;
	}
	else
	{
		$diff = abs(($valueOne - $valueTwo) / $valueOne * 100);
		$diff = round($diff);
		if($diff >= 20)
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
	$startMonth = date('n', strtotime($startDate));
	$startDay = date('j', strtotime($startDate));
	$startYear = date('Y', strtotime($startDate));
	
	$endMonth = date('n', strtotime($endDate));
	$endYear = date('Y', strtotime($endDate));
	
	
	$startDate = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	
	$endDate = mktime(0, 0, 0, $endMonth, 30, $endYear);
	
	$diff = round(($endDate-$startDate)/2628000);
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
	return $colspan = (($loggedIn) ? 53 : 52 );
}

function iszero($element) { return $element != ''; }

function m_query($n,$q)
{	
	global $logger;
	$time_start = microtime(true);
	$res = mysql_query($q);
	$time_end = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'TIME:'.$time_taken.'  QUERY:'.$q.'  LINE# '.$n;
	$logger->debug($log);
	unset($log);
	return $res;
}

function track_time($n,$q)
{
	global $logger;
	$time_start = microtime(true);
	$log = 'TIME:'.$time_start.'  Process start time:'.$q.'  LINE# '.$n;
	$logger->debug($log);
	unset($log);
}

function track_time_diff($n,$q, $time_start, $time_end)
{
	global $logger;
	//$time_start = microtime(true);
	$time_taken = $time_end-$time_start;
	$log = 'TIME ELAPSED:'.$time_taken.'  Operaton:'.$q.'  LINE# '.$n;
	$logger->debug($log);
	unset($log);
}
?>