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

global $Sphinx_search;

class TrialTracker
{
	private $fid = array();
	private $inactiveStatusValues = array();
	private $activeStatusValues = array();
	private $allStatusValues = array();
	private $resultsPerPage = 100;
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
		
		$this->inactiveStatusValues = array('Withheld', 'Approved for marketing', 'Temporarily not available', 'No Longer Available', 
									'Withdrawn', 'Terminated','Suspended', 'Completed');
									
		$this->activeStatusValues = array('Not yet recruiting', 'Recruiting', 'Enrolling by invitation', 
								'Active, not recruiting', 'Available', 'No longer recruiting');
		$this->allStatusValues = array_merge($this->inactiveStatusValues, $this->activeStatusValues);
		
		$this->phaseValues = array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', 
							'1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', 
							'2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900', 
							'3/4'=>'#FF0000', '3b/4'=>'#FF0000', '4'=>'#FF0000');
		
		$this->statusFilters = array('Not yet recruiting','Recruiting','Enrolling by invitation','Active, not recruiting','Available',
									 'No longer recruiting','Withheld','Approved for marketing', 'Temporarily not available','No Longer Available',
									 'Withdrawn','Terminated', 'Suspended','Completed');
		$this->phaseFilters = array('N/A'=>'na', 'Phase 0'=>'0', 'Phase 0/Phase 1'=>'1', 'Phase 1'=>'1', 'Phase 1a'=>'1', 'Phase 1b'=>'1', 
									'Phase 1a/1b'=>'1', 'Phase 1c'=>'1', 'Phase 1/Phase 2'=>'2', 'Phase 1b/2'=>'2', 'Phase 1b/2a'=>'2', 'Phase 2'=>'2', 
									'Phase 2a'=>'2', 'Phase 2a/2b'=>'2', 'Phase 2a/b'=>'2', 'Phase 2b'=>'2', 'Phase 2/Phase 3'=>'3', 'Phase 2b/3'=>'3',
									'Phase 3'=>'3', 'Phase 3a'=>'3', 'Phase 3b'=>'3', 'Phase 3/Phase 4'=>'4', 'Phase 3b/4'=>'4', 'Phase 4'=>'4');
		
		$this->regionFilters = array('US','Canada','Japan','Europe','RestOfWorld');
		
		$this->institutionFilters = getEnumValues('clinical_study', 'institution_type');
	}
	
	function generateTrialTracker($format, $resultIds, $timeMachine = NULL, $ottType, $globalOptions = array())
	{	
		global $Sphinx_search;
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
			case 'tsv':
				$this->generateTsvFile($resultIds, $timeMachine, $ottType, $globalOptions);
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
			case 'indexed_search':
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
		$timeMachine = strtotime($timeMachine);

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
					
		$currentYear = date('Y');
		$secondYear	= date('Y')+1;
		$thirdYear	= date('Y')+2;	

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
		$objPHPExcel->getActiveSheet()->getStyle('A1:BB1')->getFont()->setSize(10);
			
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
					$prow = $this->getProductId(array($pvalue));
					$disContinuedTxt = '';
					if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
					{
						$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
						$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);
						$disContinuedTxt = ' Discontinued';
					}
					else
					{
						$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
					}
					
					if($prow['company'] !== NULL && $prow['company'] != '')
						$TrialsInfo[$pkey]['sectionHeader'] .= " / (" . $prow['company'] . ")";
						
					if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
					{
						$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
						if(mysql_num_rows($tag_res) > 0)
						{
							while($tag_row = mysql_fetch_assoc($tag_res))
							{
								if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
									$TrialsInfo[$pkey]['sectionHeader'] .= " [" . $tag_row['tag'] ."] ";
							}
						}
					}
					
					$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
					$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
							
					$Ids[$pkey]['product'] = $prow['id'];
					$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
				}
			}
			else if((count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && ($resultIds['area'][0] == NULL || trim($resultIds['area'][0]) == "")) || (count($resultIds['area']) >= 1 && count($resultIds['product']) == 1 && ($resultIds['product'][0] == NULL || trim($resultIds['product'][0]) == ""))) //Condition For Only Product OR When Only Area is Given
			{
				if(count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && $resultIds['area'][0] == NULL && trim($resultIds['area'][0]) == '' && $resultIds['product'][0] != NULL && trim($resultIds['product'][0]) != '')
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$prow = $this->getProductId(array($pvalue));
						$disContinuedTxt = '';
						if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
							$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);	
							$disContinuedTxt = ' Discontinued';
						}
						else
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
						}
						
						if($prow['company'] !== NULL && $prow['company'] != '')
							$TrialsInfo[$pkey]['sectionHeader'] .= " / (" . $prow['company'] . ")";

						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
						{
							$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
							if(mysql_num_rows($tag_res) > 0)
							{
								while($tag_row = mysql_fetch_assoc($tag_res))
								{
									if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
										$TrialsInfo[$pkey]['sectionHeader'] .= " [" . $tag_row['tag'] ."]";
								}
							}
						}
					
						$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
						$TrialsInfo[$pkey]['naUpms'] = 
						$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
						
						$Ids[$pkey]['product'] = $prow['id'];
						$Ids[$pkey]['area'] = '';
					}
				}
				else
				{
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['type_id'];	//if area has no display name, just display id
									
									$Ids[$akey]['product'] = '';
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$TrialsInfo[$akey]['sectionHeader'] = "Area ".$avalue;
									
									$Ids[$akey]['product'] = '';
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									if($row['id'] != '' && $row['id'] != NULL && $avalue != '' && $avalue != NULL)
									{
										$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['id'];
										$Ids[$akey]['area'] = $row['id'];
									}
									else /// For case we dont have product names, area names
									{
										$TrialsInfo[$akey]['sectionHeader'] = '';
										$Ids[$akey]['area'] = '';
									}
									
									$Ids[$akey]['product'] = '';
								}
							}
						}
					}
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$prow = $this->getProductId($resultIds['product']);

					$TrialsInfo[0]['naUpms'] = 
					$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['type_id'];	//if area has no display name, just display id
									
									$Ids[$akey]['product'] = $prow['id'];
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$TrialsInfo[$akey]['sectionHeader'] = "Area ".$avalue;
									
									$Ids[$akey]['product'] = $prow['id'];
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
							$row = mysql_fetch_assoc($res);
							
							$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['id'];
							$Ids[$akey]['area'] = $row['id'];
							$Ids[$akey]['product'] = $prow['id'];
						}
					}
				}
				else
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$prow = $this->getProductId(array($pvalue));
						$disContinuedTxt = '';
						if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
							$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);
							$disContinuedTxt = ' Discontinued';
						}
						else
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
						}
						
						if($prow['company'] !== NULL && $prow['company'] != '')
							$TrialsInfo[$pkey]['sectionHeader'] .= " / (" . $prow['company'] . ")";
						
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
						{
							$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
							if(mysql_num_rows($tag_res) > 0)
							{
								while($tag_row = mysql_fetch_assoc($tag_res))
								{
									if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
										$TrialsInfo[$pkey]['sectionHeader'] .= " [" . $tag_row['tag'] ."]";
								}
							}
						}
					
						$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
						$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
								
						$Ids[$pkey]['product'] = $prow['id'];
						$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
					}
				}
			}
			else
			{
				$prow = $this->getProductId($resultIds['product']);
				$disContinuedTxt = '';
				if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
				{
					$TrialsInfo[0]['sectionHeader'] = $prow['name'];
					$TrialsInfo[0]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);	
					$disContinuedTxt = ' Discontinued';
				}
				else
				{
					$TrialsInfo[0]['sectionHeader'] = $prow['name'];
				}
				
				if($prow['company'] !== NULL && $prow['company'] != '')
					$TrialsInfo[0]['sectionHeader'] .= " / (" . $prow['company'] . ")";
				
				if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
				{
					$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
					if(mysql_num_rows($tag_res) > 0)
					{
						while($tag_row = mysql_fetch_assoc($tag_res))
						{
							if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
								$TrialsInfo[0]['sectionHeader'] .= " [" . $tag_row['tag'] ."]";
						}
					}
				}
					
				$TrialsInfo[0]['sectionHeader'] .= $disContinuedTxt;	
				$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
				
				$Ids[0]['product'] = $prow['id'];
				$Ids[0]['area'] = implode("', '", $resultIds['area']);
			}
			
			
			if(isset($globalOptions['product']) && !empty($globalOptions['product']) && $globalOptions['download'] != 'allTrialsforDownload')
			{	
				foreach($TrialsInfo as $tikey => $tivalue)
				{
					if(!(in_array($tikey, $globalOptions['product'])))
					{
						unset($TrialsInfo[$tikey]);
						unset($Ids[$tikey]);
					}
				}
				$TrialsInfo = array_values($TrialsInfo);
				$Ids = array_values($Ids);
			}
			
			$Values = $this->processIndexedOTTData($TrialsInfo, $ottType, $Ids, $timeMachine, $globalOptions);
		}
		else
		{	
			if(!is_array($resultIds))
			{
				$resultIds = array($resultIds);
			}
			$Values = $this->processOTTData($ottType, $resultIds, $timeMachine, $linkExpiryDt = array(), $globalOptions);
			
			if(isset($globalOptions['product']) && !empty($globalOptions['product']) && $globalOptions['download'] != 'allTrialsforDownload')
			{	
				foreach($Values['Trials'] as $tkey => $tvalue)
				{
					if(!(in_array($tkey, $globalOptions['product'])))
					{
						unset($Values['Trials'][$tkey]);
					}
				}
				$Values['Trials'] = array_values($Values['Trials']);
			}
		}
		
		//these values are not needed at present
		unset($Values['totactivecount']);
		unset($Values['totinactivecount']);
		unset($Values['totalcount']);
		
		
		$unMatchedUpms = array();
		if($globalOptions['download'] == 'allTrialsforDownload')
		{
			$type = 'allTrialsforDownload';
		}
		else
		{
			$type = $globalOptions['type'];
		}
		
		$i = 2;
		
		foreach($Values['Trials'] as $tkey => $tvalue)
		{
			$unMatchedUpms = array_merge($unMatchedUpms, $tvalue['naUpms']);
			
			if($globalOptions['includeProductsWNoData'] == "off")
			{
				if(!empty($tvalue['naUpms']) || !empty($tvalue[$type]))
				{
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $tvalue['sectionHeader']);
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
			}
			else
			{
				$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $tvalue['sectionHeader']);
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
			
			foreach($tvalue[$type] as $dkey => $dvalue)
			{
				$startMonth = date('m',strtotime($dvalue['NCT/start_date']));
				$startYear = date('Y',strtotime($dvalue['NCT/start_date']));
				$endMonth = date('m',strtotime($dvalue['inactive_date']));
				$endYear = date('Y',strtotime($dvalue['inactive_date']));
				
				$nctId = $dvalue["NCT/nct_id"];
				$nctIdText = padnct($nctId);
				if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
				{
					if(isset($dvalue['manual_is_sourceless']))
					{
						$ctLink = urlencode($dvalue['source']);
					}
					else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') === FALSE)
					{	
						$nctIdText = unpadnct($nctId);
						$ctLink = urlencode('https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $nctId);
					}
					else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') !== FALSE)
					{
						$ctLink = urlencode('http://clinicaltrials.gov/ct2/show/' . padnct($nctId));
					}
					else 
					{ 
						$ctLink = urlencode('javascript:void(0)');
					}
				}
				else
				{
					if($dvalue['NCT/nct_id'] !== '' && $dvalue['NCT/nct_id'] !== NULL)
					{
						$ctLink = urlencode('http://clinicaltrials.gov/ct2/show/' . padnct($nctId));
					}
					else 
					{ 
						$ctLink = urlencode('javascript:void(0)');
					}
				}
				
				$cellSpan = $i;
				$rowspanLimit = 0;
				
				if(!empty($dvalue['matchedupms'])) 
				{
					$cellSpan = $i;
					$rowspanLimit = count($dvalue['matchedupms']);
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
				
				$objPHPExcel->getActiveSheet()->getStyle('"A' . $i . ':BB' . $i.'"')->applyFromArray($styleThinBlueBorderOutline);
				$objPHPExcel->getActiveSheet()->getStyle('"A' . $i . ':BB' . $i.'"')->getFont()->setSize(10);
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
				$dvalue["NCT/brief_title"] = fix_special_chars($dvalue["NCT/brief_title"]);
				$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $dvalue["NCT/brief_title"]);
				$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setUrl($ctLink);
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/brief_title', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/brief_title'],0,255));  //We can display only 255 character as tooltip in Excel
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_brief_title']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($manualChange); 
						if($dvalue['original_brief_title'] == $dvalue['NCT/brief_title'])
						{
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Manual curation.');
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_brief_title']);
						}
					}
					else
					{
						 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov'); 
					}
				}
				else
				{
					if(isset($dvalue['manual_brief_title']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($manualChange); 
						if($dvalue['original_brief_title'] == $dvalue['NCT/brief_title'])
						{
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Manual curation.');
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_brief_title']);
						}
					}
					else if(!empty($dvalue['edited']) && array_key_exists('NCT/brief_title', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('B' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/brief_title'],0,255));  //We can display only 255 character as tooltip in Excel
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
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/enrollment', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/enrollment'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_enrollment']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_enrollment'] == $dvalue['NCT/enrollment'])
						{
							 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Manual curation.');
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_enrollment']);
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_enrollment']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_enrollment'] == $dvalue['NCT/enrollment'])
						{
							 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Manual curation.');
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_enrollment']);
						}
					}
					elseif(!empty($dvalue['edited']) && array_key_exists('NCT/enrollment', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/enrollment'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				if($dvalue["NCT/enrollment_type"] != '') 
				{
					if($dvalue["NCT/enrollment_type"] == 'Anticipated' || $dvalue["NCT/enrollment_type"] == 'Actual') 
					{ 
						$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $dvalue["NCT/enrollment"]);
					}
					else 
					{ 
						$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $dvalue["NCT/enrollment"] . ' (' . $dvalue["NCT/enrollment_type"] . ')');
					}
				} 
				else 
				{
					$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $dvalue["NCT/enrollment"]);
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
					elseif(isset($dvalue['manual_region']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
						$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
					}
				}
				else
				{
					if(isset($dvalue['manual_region']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
						$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
					}
					elseif($dvalue['new'] == 'y')
					{
						$objPHPExcel->getActiveSheet()->getStyle('D' . $i)->applyFromArray($highlightChange);
						$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setUrl($ctLink); 
						$objPHPExcel->getActiveSheet()->getCell('D' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
				
				
				//status
				$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $dvalue["NCT/overall_status"]);
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/overall_status', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/overall_status'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_overall_status']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_overall_status'] == $dvalue['NCT/overall_status'])
						{	
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{	
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_overall_status']); 
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_overall_status']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_overall_status'] == $dvalue['NCT/overall_status'])
						{
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_overall_status']); 
						}
					}
					else if(!empty($dvalue['edited']) && array_key_exists('NCT/overall_status', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/overall_status'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('E' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
				
				//collaborator and lead sponsor	
				$dvalue["NCT/lead_sponsor"] = fix_special_chars($dvalue["NCT/lead_sponsor"]);
				$dvalue["NCT/collaborator"] = fix_special_chars($dvalue["NCT/collaborator"]);
				$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $dvalue["NCT/lead_sponsor"] . $dvalue["NCT/collaborator"]);
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && (array_key_exists('NCT/lead_sponsor', $dvalue['edited']) || array_key_exists('NCT/collaborator', $dvalue['edited'])))
					{
						$value = '';
						if(array_key_exists('NCT/lead_sponsor', $dvalue['edited']))
						{
							$value .= $dvalue['edited']['NCT/lead_sponsor'];
						}
						
						if(array_key_exists('NCT/collaborator', $tvalue['edited']))
						{
							$value .= $dvalue['edited']['NCT/collaborator'];
						}
						$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip(substr($value,0,255)); 
					}
					else if($tvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_lead_sponsor']) || isset($dvalue['manual_collaborator']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
						if(isset($dvalue['manual_lead_sponsor']))
						{
							if($dvalue['original_lead_sponsor'] == $dvalue['NCT/lead_sponsor'])
							{
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							else
							{	
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_lead_sponsor']); 
							}
						}
						else
						{
							if($dvalue['original_collaborator'] == $dvalue['NCT/collaborator'])
							{
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							else
							{	
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_collaborator']); 
							}
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_lead_sponsor']) || isset($dvalue['manual_collaborator']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
						if(isset($dvalue['manual_lead_sponsor']))
						{
							if($dvalue['original_lead_sponsor'] == $dvalue['NCT/lead_sponsor'])
							{
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							else
							{	
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_lead_sponsor']); 
							}
						}
						else
						{
							if($dvalue['original_collaborator'] == $dvalue['NCT/collaborator'])
							{
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
							}
							else
							{	
								$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_collaborator']); 
							}
						}
					}
					elseif(!empty($dvalue['edited']) && (array_key_exists('NCT/lead_sponsor', $dvalue['edited']) || array_key_exists('NCT/collaborator', $dvalue['edited'])))
					{
						$value = '';
						if(array_key_exists('NCT/lead_sponsor', $dvalue['edited']))
						{
							$value .= $dvalue['edited']['NCT/lead_sponsor'];
						}
						
						if(array_key_exists('NCT/collaborator', $tvalue['edited']))
						{
							$value .= $dvalue['edited']['NCT/collaborator'];
						}
						$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip(substr($value,0,255)); 
					}
					else if($tvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
				
				//condition
				$dvalue["NCT/condition"] = fix_special_chars($dvalue["NCT/condition"]);
				$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $dvalue["NCT/condition"]);
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/condition', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/condition'],0,255)); 
					}
					else if($tvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_condition']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_condition'] == $dvalue['NCT/condition'])
						{	
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{	
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_condition']); 
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_condition']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_condition'] == $dvalue['NCT/condition'])
						{	
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{	
							$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_condition']); 
						}
					}
					elseif(!empty($dvalue['edited']) && array_key_exists('NCT/condition', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/condition'],0,255)); 
					}
					else if($tvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
					
				//intervention
				$dvalue["NCT/intervention_name"] = fix_special_chars($dvalue["NCT/intervention_name"]);
				$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $dvalue["NCT/intervention_name"]);
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/intervention_name', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/intervention_name'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_intervention_name']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_intervention_name'] == $dvalue['NCT/intervention_name'])
						{
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{	
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_intervention_name']); 
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_intervention_name']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($manualChange); 
						$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_intervention_name'] == $dvalue['NCT/intervention_name'])
						{
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{	
							$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_intervention_name']); 
						}
					}
					elseif(!empty($dvalue['edited']) && array_key_exists('NCT/intervention_name', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/intervention_name'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('H' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
				
				//start date
				if(isset($dvalue["NCT/start_date"])
				&& $dvalue["NCT/start_date"] != '' 
				&& $dvalue["NCT/start_date"] !== NULL 
				&& $dvalue["NCT/start_date"] != '0000-00-00')
				{ 	
					$objPHPExcel->getActiveSheet()->setCellValue('I' . $i, date('m/y',strtotime($dvalue["NCT/start_date"])));
				}
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/start_date', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($dvalue['edited']['NCT/start_date']); 
					}
					elseif($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_start_date']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink); 
						if($dvalue['original_start_date'] == $dvalue['start_date'])
						{
							$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_start_date']); 
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_start_date']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink); 
						if($dvalue['original_start_date'] == $dvalue['start_date'])
						{
							$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_start_date']); 
						}
					}
					elseif(!empty($dvalue['edited']) && array_key_exists('NCT/start_date', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip($dvalue['edited']['NCT/start_date']); 
					}
					elseif($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('I' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('I' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
				
				//end date	
				if(isset($dvalue["inactive_date"]) 
				&& $dvalue["inactive_date"] != '' 
				&& $dvalue["inactive_date"] !== NULL 
				&& $dvalue["inactive_date"] != '0000-00-00') 
				{
					$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, date('m/y',strtotime($dvalue["inactive_date"])));
				}
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($dvalue['edited']) && array_key_exists('NCT/inactive_date', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/inactive_date'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_end_date']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
						if($dvalue['original_end_date'] == $dvalue['inactive_date'])
						{
							$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_end_date']); 
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_end_date']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($manualChange);
						$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
						if($dvalue['original_end_date'] == $dvalue['inactive_date'])
						{
							$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_end_date']); 
						}
					}
					elseif(!empty($dvalue['edited']) && array_key_exists('NCT/inactive_date', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/inactive_date'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('J' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setUrl($ctLink); 
						 $objPHPExcel->getActiveSheet()->getCell('J' . $i)->getHyperlink()->setTooltip('New record'); 
					}
				}
				
				
				//phase
				if($dvalue['NCT/phase'] == 'N/A' || $dvalue['NCT/phase'] == '' || $dvalue['NCT/phase'] === NULL)
				{
					$phase = 'N/A';
					$phaseColor = $this->phaseValues['N/A'];
				}
				else
				{
					$phase = str_replace('Phase ', '', trim($dvalue['NCT/phase']));
					$dvalue['NCT/phase'] = str_replace('Phase ', '', trim($dvalue['NCT/phase']));
					$phaseColor = $this->phaseValues[$phase];
				}
				$objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $phase);
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(!empty($tvalue['edited']) && array_key_exists('NCT/phase', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/phase'],0,255)); 
					}
					else if($dvalue['new'] == 'y')
					{
						 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
						 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('New record'); 
					}
					elseif(isset($dvalue['manual_phase']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
						$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_phase'] == $dvalue['NCT/phase'])
						{	
							$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_phase']); 
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_phase']))
					{
						$objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange); 
						$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
						if($dvalue['original_phase'] == $dvalue['NCT/phase'])
						{	
							$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('Manual curation.'); 
						}
						else
						{
							$objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip('Manual curation. Original value: ' . $dvalue['original_phase']); 
						}
					}
					elseif(!empty($tvalue['edited']) && array_key_exists('NCT/phase', $dvalue['edited']))
					{
						 $objPHPExcel->getActiveSheet()->getStyle('K' . $i)->applyFromArray($highlightChange);
						 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setUrl($ctLink);
						 $objPHPExcel->getActiveSheet()->getCell('K' . $i)->getHyperlink()->setTooltip(substr($dvalue['edited']['NCT/phase'],0,255)); 
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
				$dvalue["NCT/start_date"], $dvalue['inactive_date'], $objPHPExcel, $i, 'M');
				
				$i++;
				
				if(isset($dvalue['matchedupms']) && !empty($dvalue['matchedupms'])) 
				{
					foreach($dvalue['matchedupms'] as $mkey => $mvalue)
					{ 
						$stMonth = date('m', strtotime($mvalue['start_date']));
						$stYear = date('Y', strtotime($mvalue['start_date']));
						$edMonth = date('m', strtotime($mvalue['end_date']));
						$edYear = date('Y', strtotime($mvalue['end_date']));
						$upmTitle = htmlformat($mvalue['event_description']);
						
						//rendering diamonds in case of end date is prior to the current year
						$objPHPExcel->getActiveSheet()->getStyle('"L' . $i . ':BB' . $i . '"')->applyFromArray($styleThinBlueBorderOutline);
						$objPHPExcel->getActiveSheet()->getStyle('"L' . $i . ':BB' . $i.'"')->getFont()->setSize(10);
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
							$objPHPExcel->getActiveSheet()->getCell('L' . $i)->getHyperlink()->setTooltip(substr($upmTitle,0,255));
							
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
			
			if(empty($tvalue[$type]) && $globalOptions['onlyUpdates'] == "no")
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					if(isset($tvalue['naUpms']) && !empty($tvalue['naUpms']))
					{
						$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'No trials found');
						$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
						$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->applyFromArray(
										array('borders' => array(
													'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF')),
													'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'))),
						));
						$i++;
					}
				}
				else
				{
					$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'No trials found');
					$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':BB'. $i);
					$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':BB'. $i)->applyFromArray(
									array('borders' => array(
												'inside' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF')),
												'outline' => array('style' => PHPExcel_Style_Border::BORDER_THIN, 'color' => array('argb' => 'FF0000FF'))),
					));
					$i++;
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
		$objPHPExcel->getActiveSheet()->setCellValue('AI1' , $thirdYear);
		$objPHPExcel->getActiveSheet()->mergeCells('AJ1:AU1');
		$objPHPExcel->getActiveSheet()->setCellValue('AV1' , '+');
		$objPHPExcel->getActiveSheet()->mergeCells('AV1:AX1');
		$objPHPExcel->getActiveSheet()->getStyle('A1:AX1')->applyFromArray($styleThinBlueBorderOutline);
		$objPHPExcel->getActiveSheet()->getStyle('A1:AX1')->getFont()->setSize(10);

		$i = 2;
		/* Display - Unmatched UPM's */
		foreach ($unMatchedUpms as $ukey => $uvalue)
		{
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':AX' . $i . '')->applyFromArray($styleThinBlueBorderOutline);
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':AX' . $i . '')->getFont()->setSize(10);
			
			$eventLink = urlencode(trim($uvalue['event_link']));
			$resultLink = urlencode(trim($uvalue['result_link']));
			
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
				$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setUrl($eventLink);
			}
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_description'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					if($uvalue['edited']['event_description'] != '' && $uvalue['edited']['event_description'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip(substr('Previous value: ' . $uvalue['edited']['event_description'],0,255)); 
					}
					else
					{
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip('No Previous value'); 
					}
				}
			}
			else if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'event_link'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('C' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					if($uvalue['edited']['event_link'] != '' && $uvalue['edited']['event_link'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('C' . $i)->getHyperlink()->setTooltip(substr('Previous value: ' . $uvalue['edited']['event_link'],0,255)); 
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
						$objPHPExcel->getActiveSheet()->getCell('E' . $i)->getHyperlink()->setTooltip(substr('Previous value: '.$uvalue['edited']['event_type'],0,255));
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
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'start_date'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('F' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['start_date'] != '' && $uvalue['edited']['start_date'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip(substr('Previous value: ' . $uvalue['edited']['start_date'],0,255)); 
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
						$objPHPExcel->getActiveSheet()->getCell('F' . $i)->getHyperlink()->setTooltip(substr('Previous value: ' . $uvalue['edited']['start_date_type'],0,255)); 
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
			if(!empty($uvalue['edited']) && ($uvalue['edited']['field'] == 'end_date'))
			{
				$objPHPExcel->getActiveSheet()->getStyle('G' . $i)->applyFromArray($highlightChange);
				if($eventLink != '' && $eventLink !== NULL)
				{
					$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setUrl($eventLink);
					if($uvalue['edited']['end_date'] != '' && $uvalue['edited']['end_date'] !== NULL)
					{
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip(substr('Previous value: ' . $uvalue['edited']['end_date'],0,255)); 
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
						$objPHPExcel->getActiveSheet()->getCell('G' . $i)->getHyperlink()->setTooltip(substr('Previous value: ' . $uvalue['edited']['end_date_type'],0,255)); 
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
				$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip(substr($uvalue['event_description'],0,255));
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
					$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip(substr($uvalue['event_description'],0,255));
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
	
	function generateTsvFile($resultIds, $timeMachine, $ottType, $globalOptions)
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
		$timeMachine = strtotime($timeMachine);

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
		
		$Values = array();
	
		if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
		{	
			$Ids = array();
			$TrialsInfo = array();
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{
				foreach($resultIds['product'] as $pkey => $pvalue)
				{	
					$Ids[$pkey]['product'] = $pvalue;
					$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
				}
			}
			else if((count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && ($resultIds['area'][0] == NULL || trim($resultIds['area'][0]) == "")) || (count($resultIds['area']) >= 1 && count($resultIds['product']) == 1 && ($resultIds['product'][0] == NULL || trim($resultIds['product'][0]) == ""))) //Condition For Only Product OR When Only Area is Given
			{
				if(count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && $resultIds['area'][0] == NULL && trim($resultIds['area'][0]) == '' && $resultIds['product'][0] != NULL && trim($resultIds['product'][0]) != '')
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$Ids[$pkey]['product'] = $pvalue;
						$Ids[$pkey]['area'] = '';
					}
				}
				else
				{
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$Ids[$akey]['product'] = '';
							
							$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
								$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$Ids[$akey]['product'] = '';
							$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									if($row['id'] != '' && $row['id'] != NULL && $avalue != '' && $avalue != NULL)
									{
										$Ids[$akey]['area'] = $row['id'];
									}
									else /// For case we dont have product names, area names
									{
										$Ids[$akey]['area'] = '';
									}
								}
							}
						}
					}
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$prow = $resultIds['product'][0];
					
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$Ids[$akey]['product'] = $prow;
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$Ids[$akey]['product'] = $prow;
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$Ids[$akey]['area'] = $avalue;
							$Ids[$akey]['product'] = $prow;
						}
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
				$Ids[0]['product'] = $resultIds['product'][0];
				$Ids[0]['area'] = implode("', '", $resultIds['area']);
			}
			
			
			if(isset($globalOptions['product']) && !empty($globalOptions['product']) && $globalOptions['download'] != 'allTrialsforDownload')
			{	
				foreach($TrialsInfo as $tikey => $tivalue)
				{
					if(!(in_array($tikey, $globalOptions['product'])))
					{
						unset($TrialsInfo[$tikey]);
						unset($Ids[$tikey]);
					}
				}
				$TrialsInfo = array_values($TrialsInfo);
				$Ids = array_values($Ids);
			}
			
			$Values = $this->processIndexedOTTData($TrialsInfo, $ottType, $Ids, $timeMachine, $globalOptions);
		}
		else
		{
			if(!is_array($resultIds))
			{
				$resultIds = array($resultIds);
			}
			
			$Values = $this->processOTTData($ottType, $resultIds, $timeMachine, $linkExpiryDt = array(), $globalOptions);
			
			if(isset($globalOptions['product']) && !empty($globalOptions['product']) && $globalOptions['download'] != 'allTrialsforDownload')
			{	
				foreach($Values['Trials'] as $tkey => $tvalue)
				{
					if(!(in_array($tkey, $globalOptions['product'])))
					{
						unset($Values['Trials'][$tkey]);
					}
				}
				$Values['Trials'] = array_values($Values['Trials']);
			}
		}
		
		unset($Ids);		
		unset($TrialsInfo);
		unset($Values['totactivecount']);
		unset($Values['totinactivecount']);
		unset($Values['totalcount']);
		
		$Trials['activeTrials'] = array();
		$Trials['inactiveTrials'] = array();
		$Trials['allTrials'] = array();
		$Trials['allTrialsforDownload'] = array();
		
		foreach($Values['Trials'] as $tkey => $tvalue)
		{
			$Trials['allTrialsforDownload'] = array_merge($Trials['allTrialsforDownload'], $tvalue['allTrialsforDownload']);
			
			$Trials['activeTrials'] = array_merge($Trials['activeTrials'], $tvalue['activeTrials']);
			$Trials['inactiveTrials'] = array_merge($Trials['inactiveTrials'], $tvalue['inactiveTrials']);
			$Trials['allTrials'] = array_merge($Trials['allTrials'], $tvalue['allTrials']);
			
		}
		unset($Values);		
		

		if($globalOptions['download'] == 'allTrialsforDownload')
		{
			$type = 'allTrialsforDownload';
		}
		else
		{
			$type = $globalOptions['type'];
		}
		
		$outputStr = "";
		$outputStr = "NCT ID \t Title \t N \t Region \t Status \t Sponsor \t Condition \t Interventions \t Start \t End \t Ph \n";
		
		foreach($Trials[$type] as $key => $value)
		{
			$startDate = '';
			$endDate = '';
			$phase = '';
			
			if($value["NCT/start_date"] != '' && $value["NCT/start_date"] !== NULL && $value["NCT/start_date"] != '0000-00-00')
			{
				$startDate =  date('m/Y', strtotime($value["NCT/start_date"]));
				
			}
			if($value["inactive_date"] != '' && $value["inactive_date"] !== NULL && $value["inactive_date"] != '0000-00-00')
			{
				$endDate = date('m/Y', strtotime($value["inactive_date"]));
			}
			
			if($value['NCT/phase'] == 'N/A' || $value['NCT/phase'] == '' || $value['NCT/phase'] === NULL)
			{
				$phase = 'N/A';
			}
			else
			{
				$phase = str_replace('Phase ', '', trim($value['NCT/phase']));
			}
			
			$outputStr .= $value['NCT/nct_id'] . "\t" . $value['NCT/brief_title'] . "\t" . $value['NCT/enrollment'] . "\t" . $value['region'] . "\t"
						. $value['NCT/overall_status'] . "\t" . $value['NCT/lead_sponsor'] . " " . $value['NCT/collaborator'] . "\t" . $value['NCT/condition']
						. "\t" . $value['NCT/intervention_name'] . "\t" . $startDate . "\t" . $endDate . "\t". $phase . "\n";
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
		$upmLink = urlencode($upmLink);
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
						.'.manual {color:#FF7700;}'
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
						.'.startdatehighlight {border-right-color: red}'
						.'.tag {color:#120f3c; font-weight:bold;}'
						.'</style></head>'
						.'<body>'
						.'<div align="center"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="200" height="25" id="header" /></div><br/>';
		

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
		$timeMachine = strtotime($timeMachine);

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
		
		$Values = array();
		
		if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
		{	
			$Ids = array();
			$TrialsInfo = array();
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{
				foreach($resultIds['product'] as $pkey => $pvalue)
				{	
					$prow = $this->getProductId(array($pvalue));
					$disContinuedTxt = '';
					if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
					{
						$TrialsInfo[$pkey]['sectionHeader'] = "<span style='color:gray'>" . $prow['name'] . "</span>";
						$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);
						$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
					}
					else
					{
						$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
					}
					if($prow['company'] !== NULL && $prow['company'] != '')
						$TrialsInfo[$pkey]['sectionHeader'] .= " / (" . $prow['company'] . ")";
					
					if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
					{
						$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
						if(mysql_num_rows($tag_res) > 0)
						{
							while($tag_row = mysql_fetch_assoc($tag_res))
							{
								if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
									$TrialsInfo[$pkey]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
							}
						}
					}
							
					$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
					$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
							
					$Ids[$pkey]['product'] = $prow['id'];
					$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
				}
			}
			else if((count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && ($resultIds['area'][0] == NULL || trim($resultIds['area'][0]) == "")) || (count($resultIds['area']) >= 1 && count($resultIds['product']) == 1 && ($resultIds['product'][0] == NULL || trim($resultIds['product'][0]) == ""))) //Condition For Only Product OR When Only Area is Given
			{
				if(count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && $resultIds['area'][0] == NULL && trim($resultIds['area'][0]) == '' && $resultIds['product'][0] != NULL && trim($resultIds['product'][0]) != '')
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$prow = $this->getProductId(array($pvalue));
						$disContinuedTxt = '';
						if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
						{
							$TrialsInfo[$pkey]['sectionHeader'] = "<span style='color:gray'>" . $prow['name'] . "</span>";
							$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);	
							$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
						}
						else
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
						}
						
						if($prow['company'] !== NULL && $prow['company'] != '')
							$TrialsInfo[$pkey]['sectionHeader'] .= " / (" . $prow['company'] . ")";
						
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
						{
							$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
							if(mysql_num_rows($tag_res) > 0)
							{
								while($tag_row = mysql_fetch_assoc($tag_res))
								{
									if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
										$TrialsInfo[$pkey]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
								}
							}
						}
					
						$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
								
						$TrialsInfo[$pkey]['naUpms'] = 
						$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
						
						$Ids[$pkey]['product'] = $prow['id'];
						$Ids[$pkey]['area'] = '';
					}
				}
				else
				{
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['type_id'];	//if area has no display name, just display id
									
									$Ids[$akey]['product'] = '';
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$TrialsInfo[$akey]['sectionHeader'] = "Area ".$avalue;
									
									$Ids[$akey]['product'] = '';
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									if($row['id'] != '' && $row['id'] != NULL && $avalue != '' && $avalue != NULL)
									{
										$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['id'];
										$Ids[$akey]['area'] = $row['id'];
									}
									else /// For case we dont have product names, area names
									{
										$TrialsInfo[$akey]['sectionHeader'] = '';
										$Ids[$akey]['area'] = '';
									}
									
									$Ids[$akey]['product'] = '';
								}
							}
						}
					}
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$prow = $this->getProductId($resultIds['product']);
					$TrialsInfo[0]['naUpms'] = 
					$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
					
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['type_id'];	//if area has no display name, just display id
									
									$Ids[$akey]['product'] = $prow['id'];
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$TrialsInfo[$akey]['sectionHeader'] = "Area ".$avalue;
									
									$Ids[$akey]['product'] = $prow['id'];
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id = '" . $avalue . "' ");
							$row = mysql_fetch_assoc($res);
							
							$TrialsInfo[$akey]['sectionHeader'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['id'];
							$Ids[$akey]['area'] = $row['id'];

							$Ids[$akey]['product'] = $prow['id'];
						}
					}
				}
				else
				{
					foreach($resultIds['product'] as $pkey => $pvalue)
					{	
						$prow = $this->getProductId(array($pvalue));
						$disContinuedTxt = '';
						if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
						{
							$TrialsInfo[$pkey]['sectionHeader'] = "<span style='color:gray'>" . $prow['name'] . "</span>";
							$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($prow['discontinuation_status_comment']);		
							$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";	
						}
						else
						{
							$TrialsInfo[$pkey]['sectionHeader'] = $prow['name'];
						}
						
						if($prow['company'] !== NULL && $prow['company'] != '')
							$TrialsInfo[$pkey]['sectionHeader'] .= " / (" . $prow['company'] . ")";
						
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
						{
							$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
							if(mysql_num_rows($tag_res) > 0)
							{
								while($tag_row = mysql_fetch_assoc($tag_res))
								{
									if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
										$TrialsInfo[$pkey]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
								}
							}
						}
							
						$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
						$TrialsInfo[$pkey]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);
						
						$Ids[$pkey]['product'] = $prow['id'];
						$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
					}
				}
			}
			else
			{
				$prow = $this->getProductId($resultIds['product']);

				if($prow['discontinuation_status'] !== NULL && $prow['discontinuation_status'] != 'Active')
				{
					$TrialsInfo[0]['sectionHeader'] = "<span style='color:gray'>" . $prow['name'] . "</span>";
					$TrialsInfo[0]['dStatusComment'] = 	strip_tags($prow['discontinuation_status_comment']);		
				}
				else
				{
					$TrialsInfo[0]['sectionHeader'] = $prow['name'];
				}
				
				if($prow['company'] !== NULL && $prow['company'] != '')
					$TrialsInfo[0]['sectionHeader'] .= " / (" . $prow['company'] . ")";
					

				if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
				{
					$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $prow['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
					if(mysql_num_rows($tag_res) > 0)
					{
						while($tag_row = mysql_fetch_assoc($tag_res))
						{
							if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
								$TrialsInfo[0]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
						}
					}
				}
						
				$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $prow['id']);

				$Ids[0]['product'] = $prow['id'];
				$Ids[0]['area'] = implode("', '", $resultIds['area']);
			}
			
			if(isset($globalOptions['product']) && !empty($globalOptions['product']) && $globalOptions['download'] != 'allTrialsforDownload')
			{	
				foreach($TrialsInfo as $tikey => $tivalue)
				{
					if(!(in_array($tikey, $globalOptions['product'])))
					{
						unset($TrialsInfo[$tikey]);
						unset($Ids[$tikey]);
					}
				}
				$TrialsInfo = array_values($TrialsInfo);
				$Ids = array_values($Ids);
			}
			
			$Values = $this->processIndexedOTTData($TrialsInfo, $ottType, $Ids, $timeMachine, $globalOptions);
		}
		else
		{
			if(!is_array($resultIds))
			{
				$resultIds = array($resultIds);
			}
			
			$Values = $this->processOTTData($ottType, $resultIds, $timeMachine, $linkExpiryDt = array(), $globalOptions);
			
			if(isset($globalOptions['product']) && !empty($globalOptions['product']) && $globalOptions['download'] != 'allTrialsforDownload')
			{	
				foreach($Values['Trials'] as $tkey => $tvalue)
				{
					if(!(in_array($tkey, $globalOptions['product'])))
					{
						unset($Values['Trials'][$tkey]);
					}
				}
				$Values['Trials'] = array_values($Values['Trials']);
			}
		}
		
		//these values are not needed at present
		unset($Values['totactivecount']);
		unset($Values['totinactivecount']);
		unset($Values['totalcount']);
		
		$pdfContent .= $this->displayTrialTableHeader_TCPDF($loggedIn, $globalOptions);
		
		$pdfContent .= $this->displayTrials_TCPDF($globalOptions, $loggedIn, $Values, $ottType);
		
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
		
		//echo '<br/>outputStr-->'.$outputStr;exit; 
		return $outputStr;
	}

	function displayTrials_TCPDF($globalOptions = array(), $loggedIn, $Values, $ottType)
	{	
		$currentYear = date('Y');
		$secondYear = (date('Y')+1);
		$thirdYear = (date('Y')+2);
		
		$Trials = array();
		if($globalOptions['download'] == 'allTrialsforDownload')
		{
			$type = 'allTrialsforDownload';
		}
		else
		{
			$type = $globalOptions['type'];
		}
		
		$outputStr = '';
		$counter = 0;
		
		if($loggedIn)
			$col_width=548;
		else
			$col_width=518;
		
		foreach($Values['Trials'] as $tkey => $tvalue)
		{
			//Rendering Upms
			if($globalOptions['includeProductsWNoData'] == "off")
			{	
				if(!empty($tvalue['naUpms']) && !empty($tvalue[$type]))
				{
					if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
					{
						$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. 'style="background: url(\'images/down.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'rowstacked\');" style="width:' . $col_width . 'px;">&nbsp;</td></tr>'
									. $this->displayUnMatchedUpms_TCPDF($loggedIn, 'rowstacked', $tvalue['naUpms'])
									. '<tr class="trialtitles" style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles" style="width:' . $col_width . 'px;">' 
									. $tvalue['sectionHeader'] . '</td></tr>';
					}
					else
					{
						if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
							$image = 'up';
						else
							$image = 'down';
						
						$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $tvalue['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 15);
						
						$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'' . $naUpmIndex . '\');" style="width:' . $col_width . 'px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
									. $tvalue['sectionHeader'] . '</td></tr>';
						$outputStr .= $this->displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $tvalue['naUpms']);
					}
				}
				else if(!empty($tvalue['naUpms']) && empty($tvalue[$type]))
				{
					if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
					{
						$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. 'style="background: url(\'images/down.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'rowstacked\');" style="width:' . $col_width . 'px;">&nbsp;</td></tr>'
									. $this->displayUnMatchedUpms_TCPDF($loggedIn, 'rowstacked', $tvalue['naUpms'])
									. '<tr class="trialtitles" style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles" style="width:' . $col_width . 'px;">' 
									. $tvalue['sectionHeader'] . '</td></tr>';
					}
					else
					{
						if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
							$image = 'up';
						else
							$image = 'down';
						
						$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $tvalue['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 15);
						
						$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'' . $naUpmIndex . '\');" style="width:' . $col_width . 'px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
									. $tvalue['sectionHeader'] . '</td></tr>';
						$outputStr .= $this->displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $tvalue['naUpms']);
					}
				}
				else if(empty($tvalue['naUpms']) && !empty($tvalue[$type]))
				{
					$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td colspan="' 
							. getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles" style="width:' . $col_width . 'px;">'
							. $tvalue['sectionHeader'] . '</td></tr>';
				}
			}
			else
			{
				if(isset($tvalue['naUpms']) && !empty($tvalue['naUpms']))
				{
					if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
					{
						$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. 'style="background: url(\'images/down.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'rowstacked\');" style="width:' . $col_width . 'px;">&nbsp;</td></tr>'
									. $this->displayUnMatchedUpms_TCPDF($loggedIn, 'rowstacked', $tvalue['naUpms'])
									. '<tr class="trialtitles" style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles" style="width:' . $col_width . 'px;">' 
									. $tvalue['sectionHeader'] . '</td></tr>';
					}
					else
					{
						if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
							$image = 'up';
						else
							$image = 'down';
						
						$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $tvalue['sectionHeader']);
						$naUpmIndex = substr($naUpmIndex, 0, 15);
						
						$outputStr .= '<tr class="trialtitles" style=" width:' . $col_width . 'px; page-break-inside:avoid;" nobr="true">'
									. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
									. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
									. ' onclick="sh(this,\'' . $naUpmIndex . '\');" style="width:' . $col_width . 'px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
									. $tvalue['sectionHeader'] . '</td></tr>';
						$outputStr .= $this->displayUnMatchedUpms_TCPDF($loggedIn, $naUpmIndex, $tvalue['naUpms']);
					}
				}
				else
				{
					$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td colspan="' 
								. getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles" style="width:' . $col_width . 'px;">'
								. $tvalue['sectionHeader'] . '</td></tr>';
				}
			}
			
			foreach($tvalue[$type] as $dkey => $dvalue)
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
				
				if(isset($dvalue['matchedupms']))  
					$rowspan = count($dvalue['matchedupms'])+1; 

				$nctId = $dvalue['NCT/nct_id'];
				if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
				{
					if(isset($dvalue['manual_is_sourceless']))
					{
						$href = $dvalue['source'];
					}
					else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') === FALSE)
					{	
						$href = 'https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $dvalue['NCT/nct_id'];
					}
					else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') !== FALSE)
					{
						$href = 'http://clinicaltrials.gov/ct2/show/' . padnct($dvalue['NCT/nct_id']);
					}
					else 
					{ 
						$href = 'javascript:void(0);';
					}
				}
				else
				{
					if($dvalue['NCT/nct_id'] !== '' && $dvalue['NCT/nct_id'] !== NULL)
					{
						$href = 'http://clinicaltrials.gov/ct2/show/' . padnct($dvalue['NCT/nct_id']);
					}
					else 
					{ 
						$href = 'javascript:void(0);';
					}
				}
				
				//row starts  
				$outputStr .= '<tr style="width:' . $col_width . 'px; height:'.(24).'px; page-break-inside:avoid;" nobr="true" ' 
							. (($dvalue['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
			
			
				//nctid column
				if($loggedIn) 
				{ 
					$outputStr .= '<td style="width:30px; '.$rowOneBGType.'" class="' . $rowOneType . '" ' . (($dvalue['new'] == 'y') ? 'title="New record"' : '') 
								. ' ><a style="color:' . $titleLinkColor . '" href="' . $href . '" target="_blank">' . $nctId . '</a></td>';
				}


				//acroynm and title column
				$attr = ' ';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/brief_title', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/brief_title'];
						$titleLinkColor = '#FF0000;';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
						$titleLinkColor = '#FF0000;';
					}
					elseif(isset($dvalue['manual_brief_title']))
					{
						if($dvalue['original_brief_title'] == $dvalue['NCT/brief_title'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_brief_title'];
						}
						$titleLinkColor = '#FF7700';
					}
				}
				else
				{
					if(isset($dvalue['manual_brief_title']))
					{
						if($dvalue['original_brief_title'] == $dvalue['NCT/brief_title'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_brief_title'];
						}
						$titleLinkColor = '#FF7700';
					}
					elseif(!empty($dvalue['edited']) && array_key_exists('NCT/brief_title', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/brief_title'];
						$titleLinkColor = '#FF0000;';
					} 
					elseif($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
						$titleLinkColor = '#FF0000;';
					}
				}
				$outputStr .= '<td style="width:93px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . ' ' . $attr . '"><span>'
							. '<a style="color:' . $titleLinkColor . '" ';
				$outputStr .= ' href="' . $href . '"  target="_blank">';			 
				if(isset($dvalue['NCT/acronym']) && $dvalue['NCT/acronym'] != '') 
				{
					$dvalue['NCT/brief_title'] = $this->replaceRedundantAcroynm($dvalue['NCT/acronym'], $dvalue['NCT/brief_title']);
					$outputStr .= htmlformat($dvalue['NCT/acronym']) . ' ' . htmlformat($dvalue['NCT/brief_title']);
				} 
				else 
				{
					$outputStr .= htmlformat($dvalue['NCT/brief_title']);
				}
				$outputStr .= '</a></span></td>';
			
				
				//enrollment column
				$attr = ' ';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/enrollment',$dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/enrollment'];
					}
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_enrollment']))
					{
						if($dvalue['original_enrollment'] == $dvalue['NCT/enrollment'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_enrollment'];
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_enrollment']))
					{
						if($dvalue['original_enrollment'] == $dvalue['NCT/enrollment'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_enrollment'];
						}
					}
					elseif(isset($dvalue['edited']) && array_key_exists('NCT/enrollment',$dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/enrollment'];
					}

					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
				}
				$outputStr .= '<td style="width:18px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><span>';
				if($dvalue["NCT/enrollment_type"] != '') 
				{
					if($dvalue["NCT/enrollment_type"] == 'Anticipated' || $dvalue["NCT/enrollment_type"] == 'Actual') 
					{ 
						$outputStr .= $dvalue["NCT/enrollment"];
					}
					else 
					{ 
						$outputStr .= $dvalue["NCT/enrollment"] . ' (' . $dvalue["NCT/enrollment_type"] . ')';
					}
				} 
				else 
				{
					$outputStr .= $dvalue["NCT/enrollment"];
				}
				$outputStr .= '</span></td>';				


				//region column
				$attr = ' ';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if($dvalue['new'] == 'y')
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_region']))
					{
						$attr = ' manual" title="Manual curation.';
					}
				}
				else
				{
					if(isset($dvalue['manual_region']))
					{
						$attr = ' manual" title="Manual curation.';
					}
					elseif($dvalue['new'] == 'y')
					{
						$attr = '" title="New record';
					}
				}
				$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" class="' . $rowOneType . '" rowspan="' . $rowspan . '" ' . $attr . '>'
							. '<span>' . $dvalue['region'] . '</span></td>';

				
				//intervention name column
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/intervention_name', $dvalue['edited']))
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/intervention_name'];
					} 
					else if($dvalue['new'] == 'y')
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_intervention_name']))
					{
						if($dvalue['original_intervention_name'] == $dvalue['NCT/intervention_name'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_intervention_name'];
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_intervention_name']))
					{
						if($dvalue['original_intervention_name'] == $dvalue['NCT/intervention_name'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_intervention_name'];
						}
					}
					elseif(isset($dvalue['edited']) && array_key_exists('NCT/intervention_name', $dvalue['edited']))
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/intervention_name'];
					} 
					else if($dvalue['new'] == 'y')
					{
						$attr = '" title="New record';
					}
				}
				$outputStr .= '<td style="width:60px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
							. '<span>' . $dvalue['NCT/intervention_name'] . '</span></td>';


				//collaborator and sponsor column
				$attr = ' ';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && (array_key_exists('NCT/collaborator', $dvalue['edited']) 
					|| array_key_exists('NCT/lead_sponsor', $dvalue['edited']))) 
					{
							
						$attr = ' highlight" title="';
						if(array_key_exists('NCT/lead_sponsor', $dvalue['edited']))
						{
							$attr .= $dvalue['edited']['NCT/lead_sponsor'] . ' ';
						}
						if(array_key_exists('NCT/collaborator', $dvalue['edited'])) 
						{
							$attr .= $dvalue['edited']['NCT/collaborator'];
						}
						$attr .= '';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_lead_sponsor']) || isset($dvalue['manual_collaborator']))
					{
						if(isset($dvalue['manual_lead_sponsor']))
						{
							if($dvalue['original_lead_sponsor'] == $dvalue['NCT/lead_sponsor'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_lead_sponsor'];
							}
						}
						else
						{
							if($dvalue['original_collaborator'] == $dvalue['NCT/collaborator'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_collaborator'];
							}
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_lead_sponsor']) || isset($dvalue['manual_collaborator']))
					{
						if(isset($dvalue['manual_lead_sponsor']))
						{
							if($dvalue['original_lead_sponsor'] == $dvalue['NCT/lead_sponsor'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_lead_sponsor'];
							}
						}
						else
						{
							if($dvalue['original_collaborator'] == $dvalue['NCT/collaborator'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_collaborator'];
							}
						}
					}
					elseif(isset($dvalue['edited']) && (array_key_exists('NCT/collaborator', $dvalue['edited']) 
					|| array_key_exists('NCT/lead_sponsor', $dvalue['edited']))) 
					{
							
						$attr = ' highlight" title="';
						if(array_key_exists('NCT/lead_sponsor', $dvalue['edited']))
						{
							$attr .= $dvalue['edited']['NCT/lead_sponsor'] . ' ';
						}
						if(array_key_exists('NCT/collaborator', $dvalue['edited'])) 
						{
							$attr .= $dvalue['edited']['NCT/collaborator'];
						}
						$attr .= '';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
				}
				$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
							. '<span>' . $dvalue['NCT/lead_sponsor'] . ' ' . $dvalue["NCT/collaborator"] . '</span></td>';


				//overall status column
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/overall_status', $dvalue['edited'])) 
					{
						$attr = 'class="highlight ' . $rowOneType . ' " title="' . $dvalue['edited']['NCT/overall_status'] . '" ';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = 'title="New record" class="' . $rowOneType . '"' ;
					}
					else if(isset($dvalue['manual_overall_status']))
					{
						if($dvalue['original_overall_status'] == $dvalue['NCT/overall_status'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_overall_status'];
						}
					} 
				}
				else
				{
					if(isset($dvalue['manual_overall_status']))
					{
						if($dvalue['original_overall_status'] == $dvalue['NCT/overall_status'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_overall_status'];
						}
					}
					else if(isset($dvalue['edited']) && array_key_exists('NCT/overall_status', $dvalue['edited'])) 
					{
						$attr = 'class="highlight ' . $rowOneType . ' " title="' . $dvalue['edited']['NCT/overall_status'] . '" ';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = 'title="New record" class="' . $rowOneType . '"' ;
					}
				}
				$outputStr .= '<td style="width:41px; '.$rowOneBGType.'" ' . $attr . ' rowspan="' . $rowspan . '">'  
							. '<span>' . $dvalue['NCT/overall_status'] . '</span></td>';
				
				
				//condition column
				$attr = ' ';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/condition', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/condition'];
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					else if(isset($dvalue['manual_condition']))
					{
						if($dvalue['original_condition'] == $dvalue['NCT/condition'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_condition'];
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_condition']))
					{
						if($dvalue['original_condition'] == $dvalue['NCT/condition'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_condition'];
						}
					}
					else if(isset($dvalue['edited']) && array_key_exists('NCT/condition', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/condition'];
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
				}
				
				$outputStr .= '<td style="width:60px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
							. '<span>' . $dvalue['NCT/condition'] . '</span></td>';
				
					
				//start date column
				$attr = ' ';
				$borderLeft = '';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/start_date', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/start_date'] ;
						$borderLeft = 'startdatehighlight';
					} 
					else if($trials[$i]['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_start_date']))
					{
						if($dvalue['original_start_date'] == $dvalue['start_date'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_start_date'];
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_start_date']))
					{
						if($dvalue['original_start_date'] == $dvalue['start_date'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_start_date'];
						}
					}
					elseif(isset($dvalue['edited']) && array_key_exists('NCT/start_date', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/start_date'];
						$borderLeft = 'startdatehighlight';
					} 
					else if($trials[$i]['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					
				}
				$outputStr .= '<td style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '" ><span>'; 
				if($dvalue["NCT/start_date"] != '' && $dvalue["NCT/start_date"] != NULL && $dvalue["NCT/start_date"] != '0000-00-00') 
				{
					$outputStr .= date('m/y',strtotime($dvalue["NCT/start_date"]));
				} 
				else 
				{
					$outputStr .= '&nbsp;';
				}
				$outputStr .= '</span></td>';
				
				
				//end date column
				$attr = ' ';
				$borderRight = '';
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('inactive_date', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['inactive_date'];
						$borderRight = 'border-right:1px solid red;';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_end_date']))
					{
						if($dvalue['original_end_date'] == $dvalue['inactive_date'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_end_date'];
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_end_date']))
					{
						if($dvalue['original_end_date'] == $dvalue['inactive_date'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_end_date'];
						}
					}
					else if(isset($dvalue['edited']) && array_key_exists('inactive_date', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['inactive_date'];
						$borderRight = 'border-right:1px solid red;';
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
				}
				$outputStr .= '<td style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType  . $attr . '"><span>'; 
				if($dvalue["inactive_date"] != '' && $dvalue["inactive_date"] != NULL && $dvalue["inactive_date"] != '0000-00-00') 
				{
					$outputStr .= date('m/y',strtotime($dvalue["inactive_date"]));
				} 
				else 
				{
					$outputStr .= '&nbsp;';
				}
				$outputStr .= '</span></td>';
					
											
				//phase column
				if(isset($dvalue['manual_is_sourceless']))
				{
					if(isset($dvalue['edited']) && array_key_exists('NCT/phase', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/phase'];
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
					elseif(isset($dvalue['manual_phase']))
					{
						if($dvalue['original_phase'] == $dvalue['NCT/phase'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_phase'];
						}
					}
				}
				else
				{
					if(isset($dvalue['manual_phase']))
					{
						if($dvalue['original_phase'] == $dvalue['NCT/phase'])
						{
							$attr = ' manual" title="Manual curation.';
						}
						else
						{
							$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_phase'];
						}
					}
					elseif(isset($dvalue['edited']) && array_key_exists('NCT/phase', $dvalue['edited'])) 
					{
						$attr = ' highlight" title="' . $dvalue['edited']['NCT/phase'];
					} 
					else if($dvalue['new'] == 'y') 
					{
						$attr = '" title="New record';
					}
				}
				if($dvalue['NCT/phase'] == 'N/A' || $dvalue['NCT/phase'] == '' || $dvalue['NCT/phase'] === NULL)
				{
					$phase = 'N/A';
					$phaseColor = $this->phaseValues['N/A'];
				}
				else
				{
					$phase = str_replace('Phase ', '', trim($dvalue['NCT/phase']));
					$phaseColor = $this->phaseValues[$phase];
				}
				$outputStr .= '<td align="center" style="width:20px; '.$rowOneBGType.'" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' 
							. '<span>' . $phase . '</span></td>';				
			
				$outputStr .= '<td style="width:20px;">&nbsp;</td>';
				
				$startMonth = date('m',strtotime($dvalue['NCT/start_date']));
				$startYear = date('Y',strtotime($dvalue['NCT/start_date']));
				$endMonth = date('m',strtotime($dvalue['inactive_date']));
				$endYear = date('Y',strtotime($dvalue['inactive_date']));


				//rendering project completion gnatt chart
				$trialGnattChart = $this->trialGnattChart($startMonth, $startYear, $endMonth, $endYear, $currentYear, $secondYear, $thirdYear, 
					$dvalue['NCT/start_date'], $dvalue['inactive_date'], $phaseColor, $borderRight, $borderLeft);
				
				
				$trialGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $trialGnattChart);	
				$outputStr .= $trialGnattChart;	
				
				$outputStr .= '</tr>';
			
			
				//rendering matched upms
				if(isset($dvalue['matchedupms']) && !empty($dvalue['matchedupms'])) 
				{
					foreach($dvalue['matchedupms'] as $mkey => $mvalue) 
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
						
						$upmBorderLeft = '';
						if(!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'start_date')
						{
							$upmBorderLeft = 'startdatehighlight';
						}
							
						$upmBorderRight = '';
						if(!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'end_date' && $mvalue['edited']['end_date'] !== NULL && $mvalue['edited']['end_date'] != '')
						{
							$upmBorderRight = 'border-right:1px solid red;';
						}
						
						//rendering upm (upcoming project completion) chart
						$upmGnattChart = $this->upmGnattChart($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'],
						$mvalue['end_date'], $mvalue['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft, $dvalue['larvol_id']);
						
						$upmGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $upmGnattChart);
						
						$outputStr .= $upmGnattChart;
						$outputStr .= '</tr>';
					}
				}
				
				$counter++;
			}
			
			
			if(empty($tvalue[$type]) && $globalOptions['onlyUpdates'] == "no")
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					if(isset($tvalue['naUpms']) && !empty($tvalue['naUpms']))
					{
						$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td colspan="' 
							. getColspanBasedOnLogin($loggedIn) . '" class="norecord" style="width:' . $col_width . 'px;">No trials found</td></tr>';
					}
				}
				else
				{
					$outputStr .= '<tr style=" width:'.$col_width.'px; page-break-inside:avoid;" nobr="true"><td colspan="' 
							. getColspanBasedOnLogin($loggedIn) . '" class="norecord" style="width:' . $col_width . 'px;">No trials found</td></tr>';
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
			$col_width=570;
		else
			$col_width=548;
		
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
				$upmTitle = htmlformat($value['event_description']);
				
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
					$outputStr .= '<td style="width:30px;" ' . $title . '><a style="' . $titleLinkColor 
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
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'event_link')) 
				{
					$titleLinkColor = 'color:#FF0000;';
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
                $upmBorderLeft = '';
				if(!empty($value['edited']) && ($value['edited']['field'] == 'start_date'))
				{
					$attr = ' highlight';
                    $upmBorderLeft = 'startdatehighlight';
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
				}
				$outputStr .= '<td style="width:20px;" class="' . $rowTwoType . $attr . '" ' . $title . '><span>';
				$outputStr .= (($value['start_date'] != '' && $value['start_date'] !== NULL && $value['start_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['start_date'])) : '&nbsp;' );
				$outputStr .= '</span></td>';

				
				//field upm end date
				$title = '';
				$attr = '';	
				$upmBorderRight = '';
				if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date'))
				{
					$attr = ' highlight';
					$upmBorderRight = 'border-right:1px solid red;';
					
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
				}
				$outputStr .= '<td style="width:20px;" class="' . $rowTwoType . $attr . '" ' . $title . '><span>';
				$outputStr .= (($value['end_date'] != '' && $value['end_date'] !== NULL && $value['end_date'] != '0000-00-00') ? 
									date('m/y',strtotime($value['end_date'])) : '');
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
				$upmGnattChart = $this->upmGnattChart(date('m',strtotime($value['start_date'])), date('Y',strtotime($value['start_date'])), 
								date('m',strtotime($value['end_date'])), date('Y',strtotime($value['end_date'])), $currentYear, $secondYear, $thirdYear, 
								$value['start_date'], $value['end_date'], $value['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft);
								
				$upmGnattChart = preg_replace('/&nbsp;/', '<img src="images/trans_big.gif" />', $upmGnattChart);
				
				//$outputStr .= preg_replace('/width:([0-9]*)px;/', '', $upmGnattChart);
				$outputStr .= $upmGnattChart;				
				
				$outputStr .= '</tr>';
			}
		}
		return $outputStr;
	}

	function getProductId($productIds = array())
	{
		$res = mysql_query("SELECT `id`, `name`, `company`, `discontinuation_status`, `discontinuation_status_comment` FROM `products` WHERE id IN ('" . implode(',', $productIds) . "') OR LI_id IN ('" . implode(',', $productIds) . "') ");
		$prow = mysql_fetch_assoc($res);
		
		return $prow;
	}

	/*****END OF Functions ONLY FOR TCPDF *****************************/
	
	function generateOnlineTT($resultIds, $timeMachine = NULL, $ottType, $globalOptions = array())
	{	
		$Values = array();
		$linkExpiry = array();
		$productSelectorTitle = 'Select Products';
		$productSelector = array();
		global $sphinx;
		global $Sphinx_search;
		if($ottType == 'unstacked')
		{
			$Id = explode(".", $resultIds);
			$res = $this->getInfo('rpt_ott_header', array('header', 'id', 'expiry'), 'id', $Id[1]);
			
			if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
			{
				$linkExpiry[] = $res['expiry'];
			}
			
			$this->displayHeader($t);
			
			echo '<input type="hidden" name="results" value="' . $resultIds . '"/>'
					. '<input type="hidden" name="time" value="' . $timeMachine . '"/>'
					. '<input type="hidden" name="v" value="' . $globalOptions['version'] . '"/>';
				
			$Values = $this->processOTTData($ottType, array($resultIds), $timeMachine, $linkExpiry, $globalOptions);
			
			if(!empty($Values['Trials'][0]['naUpms']))
			{
				echo '<input type="hidden" id="upmstyle" value="expand"/>';
			}
			
			echo $this->displayWebPage($productSelectorTitle, $ottType, $Values['resultIds'], $timeMachine, $Values, array(), $globalOptions, $Values['linkExpiry']);
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
			
			$TrialsInfo = array_map(function($a) { 
		 		return $a['sectionHeader']; 
			},  $Values['Trials']);
			natcasesort($TrialsInfo);
			
			echo $this->displayWebPage($productSelectorTitle, $ottType, $Values['resultIds'], $timeMachine, $Values, $TrialsInfo, $globalOptions, $Values['linkExpiry']);
		}
		else if($ottType == 'indexed') 
		{	
			$TrialsInfo = array();
			$Ids = array();
			$disContinuedTxt = '';
			
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
			$timeMachine = strtotime($timeMachine);

	
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
			
			$resultIds['product'] = explode(',', trim($resultIds['product']));
			$resultIds['area'] = explode(',', trim($resultIds['area']));
			
			if(count($resultIds['product']) > 1 && count($resultIds['area']) > 1)
			{	
				$t = 'Area: Total';
				$this->displayHeader($t);
				
				$ottType = 'colstackedindexed';
				
				foreach($resultIds['product'] as $pkey => $pvalue)
				{
					$disContinuedTxt = '';
					$res = mysql_query("SELECT `name`, `id`, `company`, `discontinuation_status`, `discontinuation_status_comment` FROM `products` WHERE id = '" . $pvalue . "' OR LI_id = '" . $pvalue . "' ");
					if(mysql_num_rows($res) > 0)
					{
						while($row = mysql_fetch_assoc($res))
						{
							if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
							{
								$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
								$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($row['discontinuation_status_comment']);
								$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
							}
							else
							{
								$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
							}
							
							$productSelector[$pkey] = $row['name'];
							
							if($row['company'] !== NULL && $row['company'] != '')
							{
								$TrialsInfo[$pkey]['sectionHeader'] .= " / <i>" . $row['company'] . "</i>";
								$productSelector[$pkey] .= " / <i>" . $row['company'] . "</i>";
							}
							
							if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
							{
								$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $pvalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
								if(mysql_num_rows($tag_res) > 0)
								{
									while($tag_row = mysql_fetch_assoc($tag_res))
									{
										if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
										{
											$TrialsInfo[$pkey]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
										}
									}
								}
							}
							
							$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
							$TrialsInfo[$pkey]['naUpms'] = 
							$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
							
							$Ids[$pkey]['product'] = $row['id'];
							$Ids[$pkey]['area'] = implode("', '", $resultIds['area']);
						}
					}
				}
			}
			else if((count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && ($resultIds['area'][0] == NULL || trim($resultIds['area'][0]) == "")) || (count($resultIds['area']) >= 1 && count($resultIds['product']) == 1 && ($resultIds['product'][0] == NULL || trim($resultIds['product'][0]) == ""))) //Condition For Only Product OR When Only Area is Given
			{
				if(count($resultIds['product']) >= 1 && count($resultIds['area']) == 1 && $resultIds['area'][0] == NULL && trim($resultIds['area'][0]) == '' && $resultIds['product'][0] != NULL && trim($resultIds['product'][0]) != '')
				{
					$t = '';
					$this->displayHeader($t);
					
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$disContinuedTxt = '';
						$res = mysql_query("SELECT `name`, `id`, `company`, `discontinuation_status`, `discontinuation_status_comment` FROM `products` WHERE id = '" . $pvalue . "' OR LI_id = '" . $pvalue . "' ");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
								{
									$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
									$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($row['discontinuation_status_comment']);
									$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
								}
								else
								{
									$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
								}
								
								$productSelector[$pkey] = $row['name'];
								
								if($row['company'] !== NULL && $row['company'] != '')
								{
									$TrialsInfo[$pkey]['sectionHeader'] .= " / <i>" . $row['company'] . "</i>";
									$productSelector[$pkey] .= " / <i>" . $row['company'] . "</i>";
								}
								
								if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
								{
									$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $pvalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
									if(mysql_num_rows($tag_res) > 0)
									{
										while($tag_row = mysql_fetch_assoc($tag_res))
										{
											if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
											{
												$TrialsInfo[$pkey]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
											}
										}
									}
								}
				
								$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
								$TrialsInfo[$pkey]['naUpms'] = 
								$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
								
								$Ids[$pkey]['product'] = $row['id'];
								$Ids[$pkey]['area'] = '';
							}
						}
					}
				}
				else
				{
					$t = '';
					$this->displayHeader($t);
					
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id`, `category` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$sectionHeader = '';
									if($row['category'] != '' && $row['category'] !== NULL)
									{
										$sectionHeader = $row['category'];
									}
									
									if($row['display_name'] != '' && $row['display_name'] !== NULL)
									{
										$sectionHeader .= ' ' . $row['display_name'];
									}
									else
									{
										$sectionHeader .= ' Area ' . $row['type_id'];
									}
									
									$TrialsInfo[$akey]['sectionHeader'] = $sectionHeader;
									
									$Ids[$akey]['product'] = '';
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$TrialsInfo[$akey]['sectionHeader'] = "Area " . $avalue;
									
									$Ids[$akey]['product'] = '';
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$res = mysql_query("SELECT `display_name`, `name`, `id`, `category` FROM `areas` WHERE id = '" . $avalue . "' ");
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									if($row['id'] != '' && $row['id'] != NULL && $avalue != '' && $avalue != NULL)
									{
										$sectionHeader = '';
										if($row['category'] != '' && $row['category'] !== NULL)
										{
											$sectionHeader = $row['category'];
										}
										
										if($row['display_name'] != '' && $row['display_name'] !== NULL)
										{
											$sectionHeader .= ' ' . $row['display_name'];
										}
										else
										{
											$sectionHeader .= ' Area ' . $row['id'];
										}
										
										$TrialsInfo[$akey]['sectionHeader'] = $sectionHeader;
										$Ids[$akey]['area'] = $row['id'];
									}
									else /// For case we dont have product names, area names
									{
										$TrialsInfo[$akey]['sectionHeader'] = '';
										$Ids[$akey]['area'] = '';
									}
									
									$Ids[$akey]['product'] = '';
									
								}
							}
						}
					}
				}
				if(!empty($TrialsInfo[0]['naUpms']))
				{
					echo '<input type="hidden" id="upmstyle" value="expand"/>';
				}
			}
			else if(count($resultIds['product']) > 1 || count($resultIds['area']) > 1)
			{
				if(count($resultIds['area']) > 1)
				{
					$productSelectorTitle = 'Select Areas';
					
					$res = mysql_query("SELECT `name`, `id` FROM `products` WHERE id IN ('" . implode("','", $resultIds['product']) 
							. "') OR LI_id IN ('" . implode(',', $resultIds['product']) . "') ");
					$row = mysql_fetch_assoc($res);
					
					$productName = $row['name'];
					$productId = $row['id'];
					
					$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $productId);
					$ottType = 'rowstackedindexed';
					
					$t = 'Product: ' . htmlformat($productName);
					$this->displayHeader($t);
					
					foreach($resultIds['area'] as $akey => $avalue)
					{
						if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
						{
							$res = mysql_query("SELECT `display_name`, `type_id`, `category` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $avalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
							
							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$sectionHeader = '';
									if($row['category'] != '' && $row['category'] !== NULL)
									{
										$sectionHeader = $row['category'];
									}
									
									if($row['display_name'] != '' && $row['display_name'] !== NULL)
									{
										$sectionHeader .= ' ' . $row['display_name'];
									}
									else
									{
										$sectionHeader .= ' Area ' . $row['type_id'];
									}
									
									$TrialsInfo[$akey]['sectionHeader'] = $sectionHeader;	//if area has no display name, just display id
									
									$Ids[$akey]['product'] = $productId;
									$Ids[$akey]['area'] = $row['type_id'];
								}
							}
							else	//if area not found in report, just display id
							{
									$TrialsInfo[$akey]['sectionHeader'] = "Area " . $avalue;
									
									$Ids[$akey]['product'] = $productId;
									$Ids[$akey]['area'] = $avalue;
							}
						}
						else	//if no hm field
						{
							$res = mysql_query("SELECT `display_name`, `name`, `id`, `category` FROM `areas` WHERE id = '" . $avalue . "' ");

							if(mysql_num_rows($res) > 0)
							{
								while($row = mysql_fetch_assoc($res))
								{
									$sectionHeader = '';
									if($row['category'] != '' && $row['category'] !== NULL)
									{
										$sectionHeader = $row['category'];
									}
									
									if($row['display_name'] != '' && $row['display_name'] !== NULL)
									{
										$sectionHeader .= ' ' . $row['display_name'];
									}
									else
									{
										$sectionHeader .= ' Area ' . $row['id'];
									}
									$TrialsInfo[$akey]['sectionHeader'] = $sectionHeader;
									
									$Ids[$akey]['product'] = $productId;
									$Ids[$akey]['area'] = $row['id'];
								}
							}
						}
						
						$productSelector[$akey] = $TrialsInfo[$akey]['sectionHeader'];
					}
					if(!empty($TrialsInfo[0]['naUpms']))
					{
						echo '<input type="hidden" id="upmstyle" value="expand"/>';
					}
				}
				else
				{
					if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
					{
						$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` IN ('" . implode("','", $resultIds['area']) . "') AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								$areaName = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['type_id'];	//if area has no display name, just display id
								$areaId = $row['type_id'];
							}
						}
						else	//if area not found in report, just display id
						{
							$areaName = "Area " . $avalue;
							$areaId = $avalue;
						}
					}
					else
					{
						$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id IN ('" . implode("','", $resultIds['area']) . "') ");
						$row = mysql_fetch_assoc($res);
						$areaName = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['id'];
						$areaId = $row['id'];
					}
					
					$ottType = 'colstackedindexed';
					
					$t = 'Area: ' . htmlformat($areaName);
					$this->displayHeader($t);
					
					foreach($resultIds['product'] as $pkey => $pvalue)
					{
						$disContinuedTxt = '';
						$res = mysql_query("SELECT `name`, `id`, `company`, `discontinuation_status`, `discontinuation_status_comment` FROM `products` WHERE id = '" . $pvalue . "' OR LI_id = '" . $pvalue . "' ");
						if(mysql_num_rows($res) > 0)
						{
							while($row = mysql_fetch_assoc($res))
							{
								if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
								{
									$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
									$TrialsInfo[$pkey]['dStatusComment'] = strip_tags($row['discontinuation_status_comment']);
									$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
								}
								else
								{
									$TrialsInfo[$pkey]['sectionHeader'] = $row['name'];
								}
								
								$productSelector[$pkey] = $row['name'];
								
								if($row['company'] !== NULL && $row['company'] != '')
								{
									$TrialsInfo[$pkey]['sectionHeader'] .= " / <i>" . $row['company'] . "</i>";
									$productSelector[$pkey] .= " / <i>" . $row['company'] . "</i>";
								}
								
								if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
								{
									$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $pvalue . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
									if(mysql_num_rows($tag_res) > 0)
									{
										while($tag_row = mysql_fetch_assoc($tag_res))
										{
											if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
											{
												$TrialsInfo[$pkey]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
											}
										}
									}
								}
								
								$TrialsInfo[$pkey]['sectionHeader'] .= $disContinuedTxt;
								$TrialsInfo[$pkey]['naUpms'] = 
								$this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
									
								$Ids[$pkey]['product'] = $row['id'];
								$Ids[$pkey]['area'] = $areaId;
							}
						}
					}
				}
			}
			else 
			{	
				if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve display name from heatmap report
				{
					$res = mysql_query("SELECT `display_name`, `type_id` FROM `rpt_masterhm_headers` WHERE `type_id` IN ('" . implode("','", $resultIds['area']) . "') AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'area'");
					if(mysql_num_rows($res) > 0)
					{
						while($row = mysql_fetch_assoc($res))
						{
							$Ids[0]['area'] = $row['type_id'];
							$areaName = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['type_id'];	//if area has no display name, just display id
									
							$t = 'Area: ' . htmlformat($areaName);
						}
					}
					else	//if area not found in report, just display id
					{
						$Ids[0]['area'] = $row['type_id'];
						$areaName = "Area ".$avalue;
						
						$t = 'Area: ' . htmlformat($areaName);
					}
				}
				else
				{
					$res = mysql_query("SELECT `display_name`, `name`, `id` FROM `areas` WHERE id IN ('" . implode(',', $resultIds['area']) . "') ");
					$row = mysql_fetch_assoc($res);
					$Ids[0]['area'] = $row['id'];
					$row['name'] = ($row['display_name'] != '' && $row['display_name'] !== NULL) ? $row['display_name'] : "Area ".$row['id'];
					$t = 'Area: ' . htmlformat($row['name']);
				}
				$this->displayHeader($t);
				
				$disContinuedTxt = '';
				$res = mysql_query("SELECT `name`, `id`, `company`, `discontinuation_status`, `discontinuation_status_comment` FROM `products` WHERE id IN ('" . implode(',', $resultIds['product']) 
						. "') OR LI_id IN ('" . implode(',', $resultIds['product']) . "') ");
				$row = mysql_fetch_assoc($res);
				
				$Ids[0]['product'] = $row['id'];
				
				if($row['discontinuation_status'] !== NULL && $row['discontinuation_status'] != 'Active')
				{
					$TrialsInfo[0]['sectionHeader'] = $row['name'];
					$TrialsInfo[0]['dStatusComment'] = strip_tags($row['discontinuation_status_comment']);
					$disContinuedTxt = " <span style='color:gray'>Discontinued</span>";
				}
				else
				{
					$TrialsInfo[0]['sectionHeader'] = $row['name'];
				}
				
				$productSelector[0] = $row['name'];
				
				if($row['company'] !== NULL && $row['company'] != '')
				{
					$TrialsInfo[0]['sectionHeader'] .= " / <i>" . $row['company'] . "</i>";
					$productSelector[0] .= " / <i>" . $row['company'] . "</i>"; 
				}
				
				if(isset($globalOptions['hm']) && trim($globalOptions['hm']) != '' && $globalOptions['hm'] != NULL)	//If hm field set, retrieve product tag from heatmap report
				{
					$tag_res = mysql_query("SELECT `tag` FROM `rpt_masterhm_headers` WHERE `type_id` = '" . $row['id'] . "' AND `report` = '". $globalOptions['hm'] ."' AND `type` = 'product'");
					if(mysql_num_rows($tag_res) > 0)
					{
						while($tag_row = mysql_fetch_assoc($tag_res))
						{
							if(trim($tag_row['tag']) != '' && $tag_row['tag'] != NULL)
							{
								$TrialsInfo[0]['sectionHeader'] .= " <font class=\"tag\">[" . $tag_row['tag'] . "]</font>";
							}
						}
					}
				}
				
				$TrialsInfo[0]['sectionHeader'] .= $disContinuedTxt;		
				$TrialsInfo[0]['naUpms'] = $this->getUnMatchedUPMs(array(), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates'], $row['id']);
				
				if(!empty($TrialsInfo[0]['naUpms']))
				{
					echo '<input type="hidden" id="upmstyle" value="expand"/>';
				}
			}
			
			echo '<input type="hidden" name="p" value="' . $_REQUEST['p'] . '"/><input type="hidden" name="a" value="' . $_REQUEST['a'] . '"/>';
			
			if(isset($_REQUEST['JSON_search']))
			echo '<input type="hidden" name="JSON_search" value=\'' . $_REQUEST['JSON_search'] . '\'/>';
			
			if(isset($_REQUEST['hm']) && trim($_REQUEST['hm']) != '' && $_REQUEST['hm'] != NULL)
			echo '<input type="hidden" name="hm" value="' . $_REQUEST['hm'] . '"/>';
			
			$Values = $this->processIndexedOTTData($TrialsInfo, $ottType, $Ids, $timeMachine, $globalOptions);
			unset($TrialsInfo);
			echo $this->displayWebPage($productSelectorTitle, $ottType, $resultIds, $timeMachine, $Values, $productSelector, $globalOptions);
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

			echo $this->displayWebPage($productSelectorTitle, $ottType, array(), $timeMachine, $Values, array(), array(), $globalOptions);
		}
		else if($ottType == 'stackedoldlink')
		{
			$cparams 	= unserialize(gzinflate(base64_decode($resultIds['cparams'])));
			
			if($cparams['type'] == 'col')
			{
				$t = 'Area: ' . $cparams['columnlabel'];
			}
			else
			{
				$t = 'Product: ' . $cparams['rowlabel'];
				$ottType = 'rowstacked';
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
			
			echo $this->displayWebPage($productSelectorTitle, $ottType, array(), $timeMachine, $Values, array(), array(), $globalOptions);
		}
	}
	
	function processOldLinkMethod($ottType, $params, $leadingIds, $globalOptions = array(), $cparams = array())
	{
		global $logger;
		
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
		$timeMachine = strtotime($timeMachine);

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
		
		$Values = array();
		$Values['Trials'] = array();
		
		$totinactivecount = 0;
		$totactivecount = 0;
		$totalcount = 0;
		
		$params = array_values($params);
		$leadingIds = array_values($leadingIds);
		
		foreach($params as $pkey => $pvalue)
		{
			$activeCount = 0;
			$inactiveCount = 0;
			$totalCount = 0;
			
			$Array = array();
			$Array2 = array();
			
			$larvolIds = array();
			$Values['Trials'][$pkey]['naUpms'] = array();
			$Values['Trials'][$pkey]['activeTrials'] = array();
			$Values['Trials'][$pkey]['inactiveTrials'] = array();
			$Values['Trials'][$pkey]['allTrials'] = array();
			$Values['Trials'][$pkey]['allTrialsforDownload'] = array();
			
			$Params = array();
			$params1 = array();
			$params2 = array();
			$params3 = array();
			
			$pval = unserialize(gzinflate(base64_decode($pvalue)));
			
			if(!empty($cparams))
			{	
				if($cparams['type'] == 'row')
				{
					$Values['Trials'][$pkey]['sectionHeader'] = $pval['columnlabel'];
					if($pkey == 0)
					{	
						$Values['Trials'][$pkey]['naUpms'] = $this->getUnMatchedUPMs(array($pval['upm']), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);
					}
				}
				else
				{
					$Values['Trials'][$pkey]['sectionHeader'] = $pval['rowlabel'];
					$Values['Trials'][$pkey]['naUpms'] = $this->getUnMatchedUPMs(array($pval['upm']), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);
				}
			}
			else
			{
				$Values['Trials'][$pkey]['sectionHeader'] = $pval['rowlabel'];
				$Values['Trials'][$pkey]['naUpms'] = $this->getUnMatchedUPMs(array($pval['upm']), array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);
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
			
			if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
			{
				foreach($globalOptions['itype'] as $ikey => $ivalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'institution_type';
					$sp->action = 'search';
					$sp->value 	= $this->institutionFilters[$ivalue];
					$params3[] = $sp;
				}
				$params3 = $params3;
			}
			
			$Params = array_merge($params1, $params2, $params3);
			if(!empty($params2)) 
			{
				$Array = search($Params,$this->fid, NULL, $timeMachine);
			} 
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($Array as $akey => $avalue) 
			{
				if(!isset($avalue['NCT/enrollment']) || $avalue['NCT/enrollment'] > 1000000)
				{
					$avalue['NCT/enrollment'] = NULL;
				}
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
				$Values['Trials'][$pkey]['allTrialsforDownload'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
				
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
						if(!empty($globalOptions['status']) && !empty($globalOptions['phase']) && !empty($globalOptions['region']))
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(in_array($rvalue['NCT/overall_status'], $status) 
							&& in_array($rvalue['NCT/phase'], $phase) 
							&& !empty($matchedRegion))
							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
							unset($phase);
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else if(!empty($globalOptions['status']) && !empty($globalOptions['phase'])) 
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							if(in_array($rvalue['NCT/overall_status'], $status) 
							&& in_array($rvalue['NCT/phase'], $phase))
							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
							unset($phase);
						}
						else if(!empty($globalOptions['phase']) && !empty($globalOptions['region']))
						{
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(in_array($rvalue['NCT/phase'], $phase) 
							&& !empty($matchedRegion))
							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($phase);
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else if(!empty($globalOptions['status']) && !empty($globalOptions['region']))
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(in_array($rvalue['NCT/overall_status'], $status) 
							&& !empty($matchedRegion))
							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else if(!empty($globalOptions['status']))
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							if(in_array($rvalue['NCT/overall_status'], $status))

							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
						}
						else if(!empty($globalOptions['phase']))
						{
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							if(in_array($rvalue['NCT/phase'], $phase))
							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($phase);
						}
						else if(!empty($globalOptions['region']))
						{
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(!empty($matchedRegion))
							{
								$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else
						{	
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'], $this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						if($globalOptions['enroll'] != '0')
						{
							$enroll = explode(' - ', $globalOptions['enroll']);
							
							if($result[$index]['NCT/enrollment'] === NULL || $result[$index]['NCT/enrollment'] == '')
							{
								$result[$index]['NCT/enrollment'] = 0;
							}
							
							if(strpos($enroll[1], '+') !== FALSE)
							{
								if($result[$index]['NCT/enrollment'] < $enroll[0])
								{	
									foreach($Values['Trials'][$pkey]['allTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$pkey]['allTrials'][$k]);
											$Values['Trials'][$pkey]['allTrials'] = array_values($Values['Trials'][$pkey]['allTrials']);
										}
									}
								
									foreach($Values['Trials'][$pkey]['inactiveTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$pkey]['inactiveTrials'][$k]);
											$Values['Trials'][$pkey]['inactiveTrials'] = array_values($Values['Trials'][$pkey]['inactiveTrials']);
										}
									}
								
									foreach($Values['Trials'][$pkey]['activeTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$pkey]['activeTrials'][$k]);
											$Values['Trials'][$pkey]['activeTrials'] = array_values($Values['Trials'][$pkey]['activeTrials']);
										}
									}
								}
							}
							else
							{
								if($result[$index]['NCT/enrollment'] < $enroll[0] || $result[$index]['NCT/enrollment'] > $enroll[1])
								{	
									foreach($Values['Trials'][$pkey]['allTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$pkey]['allTrials'][$k]);
											$Values['Trials'][$pkey]['allTrials'] = array_values($Values['Trials'][$pkey]['allTrials']);
										}
									}
								
									foreach($Values['Trials'][$pkey]['inactiveTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$pkey]['inactiveTrials'][$k]);
											$Values['Trials'][$pkey]['inactiveTrials'] = array_values($Values['Trials'][$pkey]['inactiveTrials']);
										}
									}
								
									foreach($Values['Trials'][$pkey]['activeTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$pkey]['activeTrials'][$k]);
											$Values['Trials'][$pkey]['activeTrials'] = array_values($Values['Trials'][$pkey]['activeTrials']);
										}
									}
								}
							}
							
						}
					}
				} 
				else 
				{
					if(!empty($globalOptions['status']) && !empty($globalOptions['phase']) && !empty($globalOptions['region']))
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
						
						if(in_array($rvalue['NCT/overall_status'], $status) 
						&& in_array($rvalue['NCT/phase'], $phase) 
						&& !empty($matchedRegion))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
						unset($phase);
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else if(!empty($globalOptions['status']) && !empty($globalOptions['phase'])) 
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						if(in_array($rvalue['NCT/overall_status'], $status) 
						&& in_array($rvalue['NCT/phase'], $phase))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
						unset($phase);
					}
					else if(!empty($globalOptions['phase']) && !empty($globalOptions['region']))
					{
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
						
						if(in_array($rvalue['NCT/phase'], $phase) 
						&& !empty($matchedRegion))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($phase);
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else if(!empty($globalOptions['status']) && !empty($globalOptions['region']))
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
						
						if(in_array($rvalue['NCT/overall_status'], $status) 
						&& !empty($matchedRegion))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else if(!empty($globalOptions['status']))
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						if(in_array($rvalue['NCT/overall_status'], $status))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
					}
					else if(!empty($globalOptions['phase']))
					{
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						if(in_array($rvalue['NCT/phase'], $phase))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($phase);
					}
					else if(!empty($globalOptions['region']))
					{
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
						
						if(!empty($matchedRegion))
						{
							$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{

								$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else
					{	
						$Values['Trials'][$pkey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						if(in_array($rvalue['NCT/overall_status'], $this->inactiveStatusValues))
						{
							$Values['Trials'][$pkey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
						else
						{
							$Values['Trials'][$pkey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
					}
					
					if($globalOptions['enroll'] != '0')
					{
						$enroll = explode(' - ', $globalOptions['enroll']);
						
						if($result[$index]['NCT/enrollment'] === NULL || $result[$index]['NCT/enrollment'] == '')
						{
							$result[$index]['NCT/enrollment'] = 0;
						}
						
						if(strpos($enroll[1], '+') !== FALSE)
						{
							if($result[$index]['NCT/enrollment'] < $enroll[0])
							{	
								foreach($Values['Trials'][$pkey]['allTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$pkey]['allTrials'][$k]);
										$Values['Trials'][$pkey]['allTrials'] = array_values($Values['Trials'][$pkey]['allTrials']);
									}
								}
							
								foreach($Values['Trials'][$pkey]['inactiveTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$pkey]['inactiveTrials'][$k]);
										$Values['Trials'][$pkey]['inactiveTrials'] = array_values($Values['Trials'][$pkey]['inactiveTrials']);
									}
								}
							
								foreach($Values['Trials'][$pkey]['activeTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$pkey]['activeTrials'][$k]);
										$Values['Trials'][$pkey]['activeTrials'] = array_values($Values['Trials'][$pkey]['activeTrials']);
									}
								}
							}
						}
						else
						{
							if($result[$index]['NCT/enrollment'] < $enroll[0] || $result[$index]['NCT/enrollment'] > $enroll[1])
							{	
								foreach($Values['Trials'][$pkey]['allTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$pkey]['allTrials'][$k]);
										$Values['Trials'][$pkey]['allTrials'] = array_values($Values['Trials'][$pkey]['allTrials']);
									}
								}
							
								foreach($Values['Trials'][$pkey]['inactiveTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$pkey]['inactiveTrials'][$k]);
										$Values['Trials'][$pkey]['inactiveTrials'] = array_values($Values['Trials'][$pkey]['inactiveTrials']);
									}
								}
							
								foreach($Values['Trials'][$pkey]['activeTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$pkey]['activeTrials'][$k]);
										$Values['Trials'][$pkey]['activeTrials'] = array_values($Values['Trials'][$pkey]['activeTrials']);
									}
								}
							}
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
		
		return  $Values;
	}
	
	function getEnumIds($fieldId, $value)
	{
		$query = "SELECT id FROM `data_enumvals` WHERE field = '" . $fieldId . "' AND value = '" . $value . "' ";
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		return $row['id'];
	}
	
	function processIndexedOTTData($TrialsInfo = array(), $ottType, $Ids = array(), $timeMachine = NULL, $globalOptions = array())
	{	
		global $logger;
		global $Sphinx_search;
		
		$totinactivecount = 0;
		$totactivecount = 0;
		$totalcount = 0;

		
		//sphinx_search
		$larvolIds = array();
		$sphinxSearchFlag = true;
		if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
		{
			$larvolIds = get_sphinx_idlist($globalOptions['sphinxSearch']);
			if($larvolIds != '')
			{
				$larvolIds = str_replace("'", "", $larvolIds);
				$larvolIds = explode(',', $larvolIds);
				$larvolIds = array_filter($larvolIds);
			}
			else
			{
				$larvolIds = array();
			}
		}
		
		$where = '';
		$orderBy = " dt.`phase` DESC, dt.`end_date` ASC, dt.`start_date` ASC, dt.`overall_status` ASC, dt.`enrollment` ASC ";
		$phaseFilters = array('N/A'=>'na', '0'=>'0', '0/1'=>'1', '1'=>'1', '1a'=>'1', '1b'=>'1', '1a/1b'=>'1', '1c'=>'1', 
									'1/2'=>'2', '1b/2'=>'2', '1b/2a'=>'2', '2'=>'2', '2a'=>'2', '2a/2b'=>'2', '2a/b'=>'2', '2b'=>'2', 
									'2/3'=>'3', '2b/3'=>'3','3'=>'3', '3a'=>'3', '3b'=>'3', '3/4'=>'4', '3b/4'=>'4', '4'=>'4');
		
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
		$timeMachine = strtotime($timeMachine);

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
		
		//Filtering Options
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
			$where .= " AND (";
			foreach($globalOptions['region'] as $rkey => $rvalue)
			{
				$r = $this->regionFilters[$rvalue];
				if($r == 'RestOfWorld')
					$region[] = " (dt.`region` LIKE '%" . $this->regionFilters[$rvalue] . "%' OR  dt.`region` LIKE '%RoW%') ";
				else
					$region[] = " (dt.`region` LIKE '%" . $this->regionFilters[$rvalue] . "%' ) ";
			}
			$where .= implode(' OR ', $region);
			$where .= " ) ";
			unset($region);
		}
		
		if(isset($globalOptions['phase']) && !empty($globalOptions['phase'])) 
		{
			$phase = array();
			foreach($globalOptions['phase'] as $pkey => $pvalue)
			{
				$ph = array_keys($phaseFilters, $pvalue);
				$phase = array_merge($phase, $ph);
			}
			
			$where .= " AND (dt.`phase` IN ('"  . implode("','", $phase) . "') )";
			unset($phase);
		}
		
		if($globalOptions['enroll'] != '0')
		{
			$enroll = explode(' - ', $globalOptions['enroll']);
		
			if(strpos($enroll[1], '+') !== FALSE)
			{
				if($enroll[0] == 0)
					$where .= " AND (dt.`enrollment` >= '" . $enroll[0] . "' OR  dt.`enrollment` = '' OR dt.`enrollment` IS NULL) " ;
				else
					$where .= " AND (dt.`enrollment` >= '" . $enroll[0] . "' ) " ;
			}
			else
			{
				if($enroll[0] == 0)
					$where .= " AND (dt.`enrollment` = '' OR dt.`enrollment` IS NULL OR dt.`enrollment` >= '" . $enroll[0] . "' AND dt.`enrollment` <= '" . $enroll[1] . "' ) " ;
				else
					$where .= " AND (dt.`enrollment` >= '" . $enroll[0] . "' AND dt.`enrollment` <= '" . $enroll[1] . "' ) " ;
			}
		}
					
		if(isset($globalOptions['JSON_search'])  or isset($Sphinx_search))
		{
			$Ids=array('Search Result' => 'Search'); //Set ID's Array so loop will be executed atleast one time
		}
		
		foreach($Ids as $ikey => $ivalue)
		{	
			
			$TrialsInfo[$ikey]['activeTrials'] = array();
			$TrialsInfo[$ikey]['inactiveTrials'] = array();
			$TrialsInfo[$ikey]['allTrials'] = array();
			$TrialsInfo[$ikey]['allTrialsforDownload'] = array();
				
			$inactiveCount = 0;
			$activeCount = 0;
			
			$result = array();
			
			global $Sphinx_search;
			if(isset($Sphinx_search))
			{
				$idlist = get_sphinx_idlist($Sphinx_search);
			}
			
			if(isset($globalOptions['JSON_search']))
			{
			
				$query = Build_OTT_Query($globalOptions['JSON_search'], $where );
				if( isset($idlist) and !empty($idlist) )
				{
					$pos = strpos(strtoupper($query),'WHERE');
					if ($pos === false) 
					{
						$pos = strpos(strtoupper($query),'ORDER');
						$str1=substr($query,0,$pos);
						$str2=substr($query,$pos);
						$query=$str1.' where larvol_id IN ( '. $idlist . ' ) ' . $str2;
					}
					else 
					{
						$pos = strpos(strtoupper($query),'ORDER');
						$str1=substr($query,0,$pos);
						$str2=substr($query,$pos);
						$query=$str1.' AND ( larvol_id IN ( '. $idlist . ' ) ) ' . $str2;
					}
				}
				
				$fullRecordQry = Build_OTT_Query($globalOptions['JSON_search'], '');
				if( isset($idlist) and !empty($idlist) )
				{
					$pos = strpos(strtoupper($fullRecordQry),'WHERE');
					if ($pos === false) 
					{
						$pos = strpos( strtoupper($fullRecordQry),'ORDER');
						$str1=substr($fullRecordQry,0,$pos);
						$str2=substr($fullRecordQry,$pos);
						$fullRecordQry=$str1.' where larvol_id IN ( '. $idlist . ' ) ' . $str2;
					}
					else 
					{
						$pos = strpos(strtoupper($fullRecordQry),'ORDER');
						$str1=substr($fullRecordQry,0,$pos);
						$str2=substr($fullRecordQry,$pos);
						$fullRecordQry=$str1.' AND ( larvol_id IN ( '. $idlist . ' ) ) ' . $str2;
					}
				}
			}
			else
			{
				$query = "SELECT dt.`larvol_id`, dt.`source_id`, dt.`brief_title`, dt.`acronym`, dt.`lead_sponsor`, dt.`collaborator`, dt.`condition`,"
						. " dt.`overall_status`, dt.`is_active`, dt.`start_date`, dt.`end_date`, dt.`enrollment`, dt.`enrollment_type`, dt.`intervention_name`,"
						. " dt.`region`, dt.`lastchanged_date`, dt.`phase`, dt.`firstreceived_date`, dt.`viewcount`, dt.`source`,"
						. " dm.`larvol_id` AS manual_larvol_id, dm.`is_sourceless` AS manual_is_sourceless, dm.`brief_title` AS manual_brief_title,"
						. " dm.`acronym` AS manual_acronym, dm.`lead_sponsor` AS manual_lead_sponsor, dm.`collaborator` AS manual_collaborator,"
						. " dm.`condition` AS manual_condition, dm.`overall_status` AS manual_overall_status, dm.`region` AS manual_region,"
						. " dm.`end_date` AS manual_end_date, dm.`enrollment` AS manual_enrollment, dm.`enrollment_type` AS manual_enrollment_type,"
						. " dm.`intervention_name` AS manual_intervention_name, dm.`phase` AS manual_phase, "
						. " dn.`brief_title` AS original_brief_title, dn.`acronym` AS original_acronym, dn.`lead_sponsor` AS original_lead_sponsor, "
						. " dn.`collaborator` AS original_collaborator, dn.`condition` AS original_condition, dn.`overall_status` AS original_overall_status, "
						. " dn.`end_date` AS original_end_date, dn.`enrollment` AS original_enrollment, pt.`sponsor_owned` AS sponsor_owned, "
						. " dn.`enrollment_type` AS original_enrollment_type, dn.`intervention_name` AS original_intervention_name, dn.`phase` AS original_phase "
						. " FROM `data_trials` dt ";
						
				if(!isset($idlist) or empty($idlist))
				{
					if($ivalue['product'] != '')	//When Product is blank do not process Product in Query
						$query .= " JOIN `product_trials` pt ON dt.`larvol_id` = pt.`trial` ";
					
					if($ivalue['area'] !='' )	//When Area is blank do not process Area in Query
						$query .= " JOIN `area_trials` at ON dt.`larvol_id` = at.`trial` ";
					
					$query .= " LEFT JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id` "
							. " LEFT JOIN `data_nct` dn ON dt.`larvol_id` = dn.`larvol_id` "
							. " WHERE ";
							
					if($ivalue['product'] != '')	//When Product is blank do not process Product in Query
						$query .= "pt.`product` IN ('" . $ivalue['product'] . "') ";
						
					if($ivalue['product'] != '' && $ivalue['area']  != '')
						$query .= "AND " ;
						
					if($ivalue['area'] !='' )	//When Area is blank do not process Area in Query
						$query .= "at.`area` IN ('" . $ivalue['area'] . "') " ;
					
					$fullRecordQry = $query . " ORDER BY " . $orderBy;	
					
					$query .= $where . " ORDER BY " . $orderBy;
				}
				else
				{
					$query .= " LEFT JOIN `data_manual` dm ON dt.`larvol_id` = dm.`larvol_id` "
							. " LEFT JOIN `data_nct` dn ON dt.`larvol_id` = dn.`larvol_id` "
							. " WHERE 1=1 ";
					
					$fullRecordQry = $query . " ORDER BY " . $orderBy;	
					$pos = strpos( strtoupper($fullRecordQry),'ORDER');
					$str1=substr($fullRecordQry,0,$pos);
					$str2=substr($fullRecordQry,$pos);
					$fullRecordQry=$str1.' AND dt.larvol_id IN ( '. $idlist . ' ) ' . $str2;
					
					$query .= $where . " ORDER BY " . $orderBy;
					$pos = strpos(strtoupper($query),'ORDER');
					$str1=substr($query,0,$pos);
					$str2=substr($query,$pos);
					$query=$str1.' AND dt.larvol_id IN ( '. $idlist . ' ) ' . $str2;
				}
			}
			unset($idlist);
			
			$res = mysql_query($query);
			while($row = mysql_fetch_assoc($res))
			{	
				$result = $this->processData($ikey, $row, $timeMachine, $timeInterval);
				
				if(isset($globalOptions['sphinxSearch']) && $globalOptions['sphinxSearch'] != '')
				{	
					if(in_array($result['larvol_id'], $larvolIds))
					{
						$sphinxSearchFlag = true;
					}
					else
					{
						$sphinxSearchFlag = false;
					}
				}
				else
				{
					$sphinxSearchFlag = true;
				}
				
				if($globalOptions['onlyUpdates'] == "yes")
				{
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($result['edited']['NCT/acronym']) && !isset($result['NCT/acronym'])) 
					{
						unset($result['edited']['NCT/acronym']);
					}
					
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($result['edited']['NCT/enrollment'])) 
					{ 
						$prevValue = substr($result['edited']['NCT/enrollment'],16);
						if(!getDifference($prevValue, $result['NCT/enrollment'])) 
						{
							unset($result['edited']['NCT/enrollment']);
						}
					}
					
					foreach($result['matchedupms'] as $mkey => & $mvalue) 
					{
						if(empty($mvalue['edited']) && $mvalue['new'] != 'y') 
						{
							unset($mvalue);
						}
					}
					
					if(!empty($result['edited']) || $result['new'] == 'y')
					{
						if($sphinxSearchFlag == true)
						{
							if($globalOptions['showTrialsSponsoredByProductOwner'] == "on")
							{
								if($result['sponsor_owned'])
								{
									$TrialsInfo[$ikey]['allTrials'][] = $result;
									if($row['is_active'] == 1) 
									{
										$TrialsInfo[$ikey]['activeTrials'][] = $result;
									}
									else
									{
										$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
									}
								}
							}
							else
							{
								$TrialsInfo[$ikey]['allTrials'][] = $result;
								if($row['is_active'] == 1) 
								{
									$TrialsInfo[$ikey]['activeTrials'][] = $result;
								}
								else
								{
									$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
								}
							}
						}
						else
						{
							if($globalOptions['showTrialsSponsoredByProductOwner'] == "on")
							{
								if($result['sponsor_owned'])
								{
									$TrialsInfo[$ikey]['allTrials'][] = $result;
									if($row['is_active'] == 1) 
									{
										$TrialsInfo[$ikey]['activeTrials'][] = $result;
									}
									else
									{
										$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
									}
								}
							}
							else
							{
								$TrialsInfo[$ikey]['allTrials'][] = $result;
								if($row['is_active'] == 1) 
								{
									$TrialsInfo[$ikey]['activeTrials'][] = $result;
								}
								else
								{
									$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
								}
							}
						}
					}
				}
				else
				{
					if($sphinxSearchFlag == true)
					{	
						if($globalOptions['showTrialsSponsoredByProductOwner'] == "on")
						{
							if($result['sponsor_owned'] == 1)
							{
								$TrialsInfo[$ikey]['allTrials'][] = $result;
								if($row['is_active'] == 1) 
								{
									$TrialsInfo[$ikey]['activeTrials'][] = $result;
								}
								else
								{
									$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
								}
							}
						}
						else
						{
							$TrialsInfo[$ikey]['allTrials'][] = $result;
							if($row['is_active'] == 1) 
							{
								$TrialsInfo[$ikey]['activeTrials'][] = $result;
							}
							else
							{
								$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
							}
						}
					}
					else
					{
						if($globalOptions['showTrialsSponsoredByProductOwner'] == "on")
						{
							if($result['sponsor_owned'] == 1)
							{
								$TrialsInfo[$ikey]['allTrials'][] = $result;
								if($row['is_active'] == 1) 
								{
									$TrialsInfo[$ikey]['activeTrials'][] = $result;
								}
								else
								{
									$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
								}
							}
						}
						else
						{
							$TrialsInfo[$ikey]['allTrials'][] = $result;
							if($row['is_active'] == 1) 
							{
								$TrialsInfo[$ikey]['activeTrials'][] = $result;
							}
							else
							{
								$TrialsInfo[$ikey]['inactiveTrials'][] = $result;
							}
						}
					}
				}
			}	
			$fullRecordRes = mysql_query($fullRecordQry);
			while($fRow = mysql_fetch_assoc($fullRecordRes))
			{
				if($fRow['is_active'] == 1) 
				{
					$activeCount++;
				}
				else
				{
					$inactiveCount++;
				}

				$TrialsInfo[$ikey]['allTrialsforDownload'][] = $this->processData($ikey, $fRow, $timeMachine, $timeInterval);
			}
			
			$totinactivecount  = $inactiveCount + $totinactivecount;
			$totactivecount	= $activeCount + $totactivecount;
			$totalcount		= $totalcount + $inactiveCount + $activeCount; 
		}
		
		$Values['totactivecount'] = $totactivecount;
		$Values['totinactivecount'] = $totinactivecount;
		$Values['totalcount'] = $totalcount;
		$Values['Trials'] = $TrialsInfo;
		
		return  $Values;
	}
	
	function processData($ikey, $dataRow = array(), $timeMachine, $timeInterval)
	{
		$matchedUpms = array();
		$fieldNames = array('end_date_lastchanged', 'region_lastchanged', 'brief_title_lastchanged', 'acronym_lastchanged', 'lead_sponsor_lastchanged',
							'overall_status_lastchanged', 'phase_lastchanged', 'enrollment_lastchanged', 'enrollment_type_lastchanged',
							'collaborator_lastchanged', 'condition_lastchanged', 'intervention_name_lastchanged', 'start_date_lastchanged');
							
		$previousValue = 'Previous value: ';	
		$noPreviousValue = 'No previous value';	
							
		$nctId = unpadnct($dataRow['source_id']);
		
		$result['larvol_id'] 			= $dataRow['larvol_id'];
		$result['inactive_date'] 		= $dataRow['end_date'];
		$result['region'] 				= $dataRow['region'];
		$result['NCT/nct_id'] 			= $nctId;
		$result['NCT/brief_title'] 		= stripslashes($dataRow['brief_title']);
		$result['NCT/enrollment_type'] 	= $dataRow['enrollment_type'];
		$result['NCT/acronym'] 			= $dataRow['acronym'];
		$result['NCT/lead_sponsor'] 	= str_replace('`', ', ', $dataRow['lead_sponsor']);
		$result['NCT/start_date'] 		= $dataRow['start_date'];
		$result['NCT/phase'] 			= $dataRow['phase'];
		$result['NCT/enrollment'] 		= $dataRow['enrollment'];
		$result['NCT/collaborator'] 	= str_replace('`', ', ', $dataRow['collaborator']);
		$result['NCT/condition'] 		= str_replace('`', ', ', stripslashes($dataRow['condition']));
		$result['NCT/intervention_name']= str_replace('`', ', ', stripslashes($dataRow['intervention_name']));
		$result['NCT/overall_status'] 	= $dataRow['overall_status'];
		$result['NCT/is_active'] 		= $dataRow['is_active'];
		$result['new'] 					= 'n';
		$result['edited'] 				= array();
		$result['viewcount'] 			= $dataRow['viewcount']; 
		$result['source'] 				= $dataRow['source']; 
		$result['source_id'] 			= $dataRow['source_id']; 
		$result['section'] 				= $ikey; 
		$result['sponsor_owned'] 			= $dataRow['sponsor_owned'];
		
		$result['manual_larvol_id'] 		= $dataRow['manual_larvol_id']; 
		$result['manual_brief_title'] 		= $dataRow['manual_brief_title']; 
		$result['manual_acronym'] 			= $dataRow['manual_acronym']; 
		$result['manual_lead_sponsor'] 		= $dataRow['manual_lead_sponsor']; 
		$result['manual_collaborator'] 		= $dataRow['manual_collaborator']; 
		$result['manual_condition'] 		= $dataRow['manual_condition']; 
		$result['manual_overall_status']	= $dataRow['manual_overall_status']; 
		$result['manual_start_date'] 		= $dataRow['manual_start_date']; 
		$result['manual_end_date'] 			= $dataRow['manual_end_date']; 
		$result['manual_enrollment'] 		= $dataRow['manual_enrollment']; 
		$result['manual_intervention_name'] = $dataRow['manual_intervention_name']; 
		$result['manual_phase'] 			= $dataRow['manual_phase'];
		$result['manual_region'] 			= $dataRow['manual_region'];
		$result['manual_is_sourceless'] 	= $dataRow['manual_is_sourceless'];
		
		$result['original_brief_title'] 	= $dataRow['original_brief_title']; 
		$result['original_acronym'] 		= $dataRow['original_acronym']; 
		$result['original_lead_sponsor'] 	= $dataRow['original_lead_sponsor']; 
		$result['original_collaborator'] 	= $dataRow['original_collaborator']; 
		$result['original_condition'] 		= $dataRow['original_condition']; 
		$result['original_overall_status']	= $dataRow['original_overall_status']; 
		$result['original_start_date'] 		= $dataRow['original_start_date']; 
		$result['original_end_date'] 		= $dataRow['original_end_date']; 
		$result['original_enrollment'] 		= $dataRow['original_enrollment']; 
		$result['original_intervention_name'] = $dataRow['original_intervention_name']; 
		$result['original_phase'] 			= $dataRow['original_phase'];
		$result['original_region'] 			= $dataRow['original_region'];
						
		if($dataRow['firstreceived_date'] <= date('Y-m-d', $timeMachine) && $dataRow['firstreceived_date'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
		{
			$result['new'] = 'y';
		}
			
		if($dataRow['lastchanged_date'] <= date('Y-m-d', $timeMachine) && $dataRow['lastchanged_date'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
		{	
			$query = "SELECT `end_date_prev`, `region_prev`, `brief_title_prev`, `acronym_prev`, `lead_sponsor_prev`, `overall_status_prev`, "
					. "`overall_status_lastchanged`, `start_date_prev`, `phase_prev`, `enrollment_prev`, `enrollment_type_prev`,`collaborator_prev`, "
					. " `condition_prev`, `intervention_name_prev`, `"
					. implode("`, `", $fieldNames) . "` FROM `data_history` WHERE `larvol_id` = '" . $dataRow['larvol_id'] . "' AND ( (`" 
					. implode('` BETWEEN "' . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . '" AND "' . date('Y-m-d', $timeMachine) 
					. '") OR (`', $fieldNames) . "` BETWEEN '" . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "' AND '" 
					. date('Y-m-d', $timeMachine) . "') ) ";
			$res = mysql_query($query);
			while($row = mysql_fetch_assoc($res))
			{
				if($row['end_date_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['end_date_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['end_date_prev'] != '' && $row['end_date_prev'] !== NULL)
					{
						$result['edited']['inactive_date'] = $previousValue . $row['end_date_prev'];
					}
					else
					{
						$result['edited']['inactive_date'] = $noPreviousValue;
					}
				}
				
				if($row['region_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['region_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['region_prev'] != '' && $row['region_prev'] !== NULL)
					{
						$result['edited']['NCT/region'] = $previousValue . $row['region_prev'];
					}
					else
					{
						$result['edited']['NCT/region'] = $noPreviousValue;
					}
				}
				
				if($row['brief_title_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['brief_title_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['brief_title_prev'] != '' && $row['brief_title_prev'] !== NULL)
					{
						$result['edited']['NCT/brief_title'] = $previousValue . stripslashes($row['brief_title_prev']);
					}
					else
					{
						$result['edited']['NCT/brief_title'] = $noPreviousValue;
					}
				}
				
				if($row['acronym_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['acronym_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['acronym_prev'] != '' && $row['acronym_prev'] !== NULL)
					{
						$result['edited']['NCT/acronym'] = $previousValue . $row['acronym_prev'];
					}
					else
					{
						$result['edited']['NCT/acronym'] = $noPreviousValue;
					}
				}
				
				if($row['lead_sponsor_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['lead_sponsor_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['lead_sponsor_prev'] != '' && $row['lead_sponsor_prev'] !== NULL)
					{
						$result['edited']['NCT/lead_sponsor'] = $previousValue . str_replace('`', ', ', $row['lead_sponsor_prev']);
					}
					else
					{
						$result['edited']['NCT/lead_sponsor'] = $noPreviousValue;
					}
				}

				if($row['start_date_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['start_date_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['start_date_prev'] != '' && $row['start_date_prev'] !== NULL)
					{
						$result['edited']['NCT/start_date'] = $previousValue . $row['start_date_prev'];
					}
					else
					{
						$result['edited']['NCT/start_date'] = $noPreviousValue;
					}
				}

				if($row['phase_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['phase_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['phase_prev'] != '' && $row['phase_prev'] !== NULL)
					{
						$result['edited']['NCT/phase'] = $previousValue . $row['phase_prev'];
					}
					else

					{
						$result['edited']['NCT/phase'] = $noPreviousValue;
					}
				}
					
				if($row['enrollment_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['enrollment_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['enrollment_prev'] != '' && $row['enrollment_prev'] !== NULL)
					{
						$result['edited']['NCT/enrollment'] = $previousValue . $row['enrollment_prev'];
					}
					else
					{
						$result['edited']['NCT/enrollment'] = $noPreviousValue;
					}
				}

				if($row['collaborator_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['collaborator_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['collaborator_prev'] != '' && $row['collaborator_prev'] !== NULL)
					{
						$result['edited']['NCT/collaborator'] = $previousValue . str_replace('`', ', ', $row['collaborator_prev']);
					}
					else
					{
						$result['edited']['NCT/collaborator'] = $noPreviousValue;
					}
				}

				if($row['condition_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['condition_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['condition_prev'] != '' && $row['condition_prev'] !== NULL)
					{
						$result['edited']['NCT/condition'] = $previousValue . str_replace('`', ', ', stripslashes($row['condition_prev']));
					}
					else
					{
						$result['edited']['NCT/condition'] = $noPreviousValue;
					}
				}

				if($row['intervention_name_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['intervention_name_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['intervention_name_prev'] != '' && $row['intervention_name_prev'] !== NULL)
					{
						$result['edited']['NCT/intervention_name'] = $previousValue . str_replace('`', ', ', $row['intervention_name_prev']);
					}
					else
					{
						$result['edited']['NCT/intervention_name'] = $noPreviousValue;
					}
				}

				if($row['overall_status_lastchanged'] <= date('Y-m-d', $timeMachine) 
				&& $row['overall_status_lastchanged'] >= date('Y-m-d', strtotime($timeInterval, $timeMachine)))
				{
					if($row['overall_status_prev'] != '' && $row['overall_status_prev'] !== NULL)
					{
						$result['edited']['NCT/overall_status'] = $previousValue . str_replace('`', ', ', $row['overall_status_prev']);
					}
					else
					{
						$result['edited']['NCT/overall_status'] = $noPreviousValue;
					}
				}
			}
		}
		//echo '<pre>';print_r($result);
		$matchedUpms = $this->getMatchedUPMs($nctId, $timeMachine, $timeInterval);
		
		$result = array_merge($result, $matchedUpms);
		return $result;
	}
	
	function processOTTData($ottType, $resultIds, $timeMachine = NULL, $linkExpiryDt = array(), $globalOptions = array())
	{	
		global $logger;
		
		$Ids = array();
		$linkExpiry = array();
		$Values = array();
		$Values['Trials'] = array();
		
		$totinactivecount = 0;
		$totactivecount = 0;
		$totalcount = 0;

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
		$timeMachine = strtotime($timeMachine);

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
		
		foreach($resultIds as $ikey => $ivalue)
		{
			$activeCount = 0;
			$inactiveCount = 0;
			$totalCount = 0;
			
			$linkExpiry[$ikey] = array();
			$Array = array();
			$Array2 = array();
			
			$larvolIds = array();
			$Values['Trials'][$ikey]['naUpms'] = array();
			$Values['Trials'][$ikey]['activeTrials'] = array();
			$Values['Trials'][$ikey]['inactiveTrials'] = array();
			$Values['Trials'][$ikey]['allTrials'] = array();
			$Values['Trials'][$ikey]['allTrialsforDownload'] = array();
			
			$Params = array();
			$params1 = array();
			$params2 = array();
			$params3 = array();
			
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
					$res = $this->getInfo('rpt_ott_upm', array('intervention_name', 'intervention_name_negate','id', 'expiry'), 'id', $Ids[4]);
					
					if($globalOptions['version'] == 1)
					{
						$res['intervention_name'] = explode('\n', $res['intervention_name']);
						$res['intervention_name_negate'] = explode('\n', $res['intervention_name_negate']);
					}
					else
					{
						$res['intervention_name'] = explode(',', $res['intervention_name']);
						$res['intervention_name_negate'] = array();
					}
					
					if($res['expiry'] != '' &&  $res['expiry'] !== NULL)
					{
						$linkExpiry[$ikey] = array_merge($linkExpiryDt, array($res['expiry']));
					}
					
					$Values['Trials'][$ikey]['naUpms'] = $this->getUnMatchedUPMs($res['intervention_name'], $res['intervention_name_negate'], $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);	
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
					
					$Values['Trials'][$ikey]['naUpms'] = $this->getUnMatchedUPMs($res['intervention_name'],array(), $timeMachine, $timeInterval, $globalOptions['onlyUpdates']);	
				}
			}
			
			$Values['Trials'][$ikey]['sectionHeader'] = $sectionHeader;
			
			if(isset($globalOptions['itype']) && !empty($globalOptions['itype'])) 
			{
				foreach($globalOptions['itype'] as $iskey => $isvalue)
				{
					$sp = new SearchParam();
					$sp->field 	= 'institution_type';
					$sp->action = 'search';
					$sp->value 	= $this->institutionFilters[$isvalue];
					$params[] = $sp;
				}
				$params3 = $params;
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
			
			$Params = array_merge($params1, $params2, $params3);
			
			if(!empty($params2)) 
			{
				$Array = search($Params,$this->fid, NULL, $timeMachine);
			} 
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($Array as $akey => $avalue) 
			{
				if(!isset($avalue['NCT/enrollment']) || $avalue['NCT/enrollment'] > 1000000)
				{
					$avalue['NCT/enrollment'] = NULL;
				}
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
				$Values['Trials'][$ikey]['allTrialsforDownload'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
				
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
						if(!empty($globalOptions['status']) && !empty($globalOptions['phase']) && !empty($globalOptions['region']))
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(in_array($rvalue['NCT/overall_status'], $status) 
							&& in_array($rvalue['NCT/phase'], $phase) 
							&& !empty($matchedRegion))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
							unset($phase);
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else if(!empty($globalOptions['status']) && !empty($globalOptions['phase'])) 
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	

								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							if(in_array($rvalue['NCT/overall_status'], $status) 
							&& in_array($rvalue['NCT/phase'], $phase))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);

								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
							unset($phase);
						}
						else if(!empty($globalOptions['phase']) && !empty($globalOptions['region']))
						{
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(in_array($rvalue['NCT/phase'], $phase) 
							&& !empty($matchedRegion))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($phase);
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else if(!empty($globalOptions['status']) && !empty($globalOptions['region']))
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(in_array($rvalue['NCT/overall_status'], $status) 
							&& !empty($matchedRegion))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else if(!empty($globalOptions['status']))
						{
							$status = array();
							foreach($globalOptions['status'] as $skey => $svalue)
							{
								$status[] = $this->statusFilters[$svalue];
							}
							
							if(in_array($rvalue['NCT/overall_status'], $status))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($status);
						}
						else if(!empty($globalOptions['phase']))
						{
							$phase = array();
							foreach($globalOptions['phase'] as $pkey => $pvalue)
							{	
								$ph = array_keys($this->phaseFilters, $pvalue);
								$phase = array_merge($phase, $ph);
							}
							
							if(in_array($rvalue['NCT/phase'], $phase))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($phase);
						}
						else if(!empty($globalOptions['region']))
						{
							$region = array();
							foreach($globalOptions['region'] as $rkey => $rgvalue)
							{
								$region[] = $this->regionFilters[$rgvalue];
							}
							
							$trialRegion = array();
							$trialRegion = explode(',', $result[$index]['region']);
							$matchedRegion = array_intersect($region, $trialRegion);
							
							if(!empty($matchedRegion))
							{
								$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
								{
									$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
								else
								{
									$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
								}
							}
							
							unset($region);
							unset($trialRegion);
							unset($matchedRegion);
						}
						else
						{	
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'], $this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						if($globalOptions['enroll'] != '0')
						{
							$enroll = explode(' - ', $globalOptions['enroll']);

							if($result[$index]['NCT/enrollment'] === NULL || $result[$index]['NCT/enrollment'] == '')
							{
								$result[$index]['NCT/enrollment'] = 0;
							}
							
							if(strpos($enroll[1], '+') !== FALSE)
							{
								if($result[$index]['NCT/enrollment'] < $enroll[0])
								{	
									foreach($Values['Trials'][$ikey]['allTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$ikey]['allTrials'][$k]);
											$Values['Trials'][$ikey]['allTrials'] = array_values($Values['Trials'][$ikey]['allTrials']);
										}
									}
								
									foreach($Values['Trials'][$ikey]['inactiveTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$ikey]['inactiveTrials'][$k]);
											$Trials['inactiveTrials'] = array_values($Values['Trials'][$ikey]['inactiveTrials']);
										}
									}
								
									foreach($Values['Trials'][$ikey]['activeTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$ikey]['activeTrials'][$k]);
											$Values['Trials'][$ikey]['activeTrials'] = array_values($Values['Trials'][$ikey]['activeTrials']);
										}
									}
								}
							}
							else
							{

								if($result[$index]['NCT/enrollment'] < $enroll[0] || $result[$index]['NCT/enrollment'] > $enroll[1])
								{	
									foreach($Values['Trials'][$ikey]['allTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$ikey]['allTrials'][$k]);
											$Values['Trials'][$ikey]['allTrials'] = array_values($Values['Trials'][$ikey]['allTrials']);
										}
									}
									
									foreach($Values['Trials'][$ikey]['inactiveTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$ikey]['inactiveTrials'][$k]);
											$Values['Trials'][$ikey]['inactiveTrials'] = array_values($Values['Trials'][$ikey]['inactiveTrials']);
										}
									}
								
									foreach($Trials['activeTrials'] as $k => $v)
									{
										if($v['NCT/nct_id'] == $nctId)
										{
											unset($Values['Trials'][$ikey]['activeTrials'][$k]);
											$Values['Trials'][$ikey]['activeTrials'] = array_values($Values['Trials'][$ikey]['activeTrials']);
										}
									}
								}
							}
						}
					}
				} 
				else 
				{
					if(!empty($globalOptions['status']) && !empty($globalOptions['phase']) && !empty($globalOptions['region']))
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
							
						if(in_array($rvalue['NCT/overall_status'], $status) 
						&& in_array($rvalue['NCT/phase'], $phase) 
						&& !empty($matchedRegion))
						{
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
						unset($phase);
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else if(!empty($globalOptions['status']) && !empty($globalOptions['phase'])) 
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						if(in_array($rvalue['NCT/overall_status'], $status) 
						&& in_array($rvalue['NCT/phase'], $phase))
						{
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
						unset($phase);
					}
					else if(!empty($globalOptions['phase']) && !empty($globalOptions['region']))
					{
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
						
						if(in_array($rvalue['NCT/phase'], $phase) 
						&& !empty($matchedRegion))
						{
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($phase);
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else if(!empty($globalOptions['status']) && !empty($globalOptions['region']))
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
						
						if(in_array($rvalue['NCT/overall_status'], $status) 
						&& !empty($matchedRegion))
						{
							$Trials['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else if(!empty($globalOptions['status']))
					{
						$status = array();
						foreach($globalOptions['status'] as $skey => $svalue)
						{
							$status[] = $this->statusFilters[$svalue];
						}
						
						if(in_array($rvalue['NCT/overall_status'], $status))
						{
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($status);
					}
					else if(!empty($globalOptions['phase']))
					{
						$phase = array();
						foreach($globalOptions['phase'] as $pkey => $pvalue)
						{	
							$ph = array_keys($this->phaseFilters, $pvalue);
							$phase = array_merge($phase, $ph);
						}
						
						if(in_array($rvalue['NCT/phase'], $phase))
						{
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($phase);
					}
					else if(!empty($globalOptions['region']))
					{
						$region = array();
						foreach($globalOptions['region'] as $rkey => $rgvalue)
						{
							$region[] = $this->regionFilters[$rgvalue];
						}
						
						$trialRegion = array();
						$trialRegion = explode(',', $result[$index]['region']);
						$matchedRegion = array_intersect($region, $trialRegion);
							
						if(!empty($matchedRegion))
						{
							$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							if(in_array($rvalue['NCT/overall_status'],$this->inactiveStatusValues))
							{
								$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
							else
							{
								$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
							}
						}
						
						unset($region);
						unset($trialRegion);
						unset($matchedRegion);
					}
					else
					{	
						$Values['Trials'][$ikey]['allTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						if(in_array($rvalue['NCT/overall_status'], $this->inactiveStatusValues))
						{
							$Values['Trials'][$ikey]['inactiveTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
						else
						{
							$Values['Trials'][$ikey]['activeTrials'][] = array_merge($dataset['trials'], $rvalue, $dataset['matchedupms']);
						}
					}
					
					if($globalOptions['enroll'] != '0')
					{
						$enroll = explode(' - ', $globalOptions['enroll']);

						if($result[$index]['NCT/enrollment'] === NULL || $result[$index]['NCT/enrollment'] == '')
						{
							$result[$index]['NCT/enrollment'] = 0;
						}
						
						if(strpos($enroll[1], '+') !== FALSE)
						{
							if($result[$index]['NCT/enrollment'] < $enroll[0])
							{	
								foreach($Values['Trials'][$ikey]['allTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$ikey]['allTrials'][$k]);
										$Values['Trials'][$ikey]['allTrials'] = array_values($Values['Trials'][$ikey]['allTrials']);
									}
								}
							
								foreach($Values['Trials'][$ikey]['inactiveTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$ikey]['inactiveTrials'][$k]);
										$Values['Trials'][$ikey]['inactiveTrials'] = array_values($Values['Trials'][$ikey]['inactiveTrials']);
									}
								}
							
								foreach($Values['Trials'][$ikey]['activeTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$ikey]['activeTrials'][$k]);
										$Values['Trials'][$ikey]['activeTrials'] = array_values($Values['Trials'][$ikey]['activeTrials']);
									}
								}
							}
						}
						else
						{
							if($result[$index]['NCT/enrollment'] < $enroll[0] || $result[$index]['NCT/enrollment'] > $enroll[1])
							{	
								foreach($Values['Trials'][$ikey]['allTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$ikey]['allTrials'][$k]);
										$Values['Trials'][$ikey]['allTrials'] = array_values($Values['Trials'][$ikey]['allTrials']);
									}
								}
							
								foreach($Values['Trials'][$ikey]['inactiveTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$ikey]['inactiveTrials'][$k]);
										$Values['Trials'][$ikey]['inactiveTrials'] = array_values($Values['Trials'][$ikey]['inactiveTrials']);
									}
								}
							
								foreach($Values['Trials'][$ikey]['activeTrials'] as $k => $v)
								{
									if($v['NCT/nct_id'] == $nctId)
									{
										unset($Values['Trials'][$ikey]['activeTrials'][$k]);
										$Values['Trials'][$ikey]['activeTrials'] = array_values($Values['Trials'][$ikey]['activeTrials']);
									}
								}
							}
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
		
		return  $Values;
	}
	
	function displayWebPage($productSelectorTitle, $ottType, $resultIds, $timeMachine = NULL, $Values, $productSelector = array(), $globalOptions, $linkExpiry = NULL)
	{	
		global $db;
		$loggedIn	= $db->loggedIn();
		
		if($ottType == 'indexed' || $ottType == 'unstacked' || $ottType == 'unstackedoldlink')
			$globalOptions['includeProductsWNoData'] = "on";
			
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
		$timeMachine = strtotime($timeMachine);
		
		if(isset($globalOptions['product']) && !empty($globalOptions['product']))
		{	
			foreach($Values['Trials'] as $key => $value)
			{	
				if(!(in_array($key, $globalOptions['product'])))
				{
					unset($Values['Trials'][$key]);
				}
			}
			$Values['Trials'] = array_values($Values['Trials']);
		}
		
		echo '<input type="hidden" name="pr" id="product" value="' . implode(',', $globalOptions['product']) . '" />';
		
		$count = 0;
		foreach($Values['Trials'] as $tkey => $tvalue)
		{
			$count += count($tvalue[$globalOptions['type']]);
		}
		
		$start 	= '';
		$last = '';
		$totalPages = '';
		
		$start 	= ($globalOptions['page']-1) * $this->resultsPerPage + 1;
		$last 	= ($globalOptions['page'] * $this->resultsPerPage > $count) ? $count : ($start + $this->resultsPerPage - 1);
		$totalPages = ceil($count / $this->resultsPerPage);
		
		if($Values['totalcount'] != 0 && $globalOptions['minEnroll'] == 0 && $globalOptions['maxEnroll'] == 0)
		{
			$enrollments = array();
			
			foreach($Values['Trials'] as $tkey => $tvalue)
			{
				foreach($tvalue['allTrialsforDownload'] as $akey => $avalue)
				{
					$enrollments[] = $avalue['NCT/enrollment'];
				}
			}

			$globalOptions['minEnroll'] = 0;
			$globalOptions['maxEnroll'] = max($enrollments);
		}
		else
		{
			$globalOptions['minEnroll'] = $globalOptions['minEnroll'];
			$globalOptions['maxEnroll'] = $globalOptions['maxEnroll'];		
		}
		
		if(isset($globalOptions['countDetails']) && !empty($globalOptions['countDetails']) 
		&& $ottType != 'indexed' && $ottType != 'colstackedindexed' && $ottType != 'rowstackedindexed') 
		{
			$Values['totactivecount'] = $globalOptions['countDetails']['a'];
			$Values['totinactivecount'] = $globalOptions['countDetails']['in'];
			$Values['totalcount'] = $Values['totactivecount'] + $Values['totinactivecount'];
		}
		
		natcasesort($productSelector);
		
		$this->displayFilterControls($productSelector, $productSelectorTitle, $count, $Values['totactivecount'], $Values['totinactivecount'], $Values['totalcount'], $globalOptions, $ottType, $loggedIn);
		
		if($totalPages > 1)
		{
			$this->pagination($globalOptions, $totalPages, $timeMachine, $ottType, $loggedIn);
		}
		
		echo '<input type="text" name="ss" autocomplete="off" style="width:300px;" value="' . $globalOptions['sphinxSearch'] . '" />';
		echo '<div style="float: right;padding-top:4px; vertical-align:bottom; height:22px;" id="chromemenu"><a rel="dropmenu">'
				. '<span style="padding:2px;border:1px solid; color:#000000; background-position:left center; background-repeat:no-repeat; background-image:url(\'./images/save.png\'); cursor:pointer;">'
				. '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Export</b></span></a></div>'
				. '<div style="float: right;margin-right: 10px; vertical-align:bottom; padding-top:4px; height:22px;"><span id="addtoright"></span></div>';
				
		echo '<br/><br/>';
		
		echo $this->displayTrialTableHeader($loggedIn, $globalOptions);
		if($count > 0)
		{	
			echo $this->displayTrials($totalPages, $globalOptions, $loggedIn, $start, $last, $Values, $ottType);
		}
		else
		{	
			$outputStr = '';
			
			foreach($Values['Trials'] as $tkey => $tvalue)
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					if(isset($tvalue['naUpms']) && !empty($tvalue['naUpms']))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $tvalue['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $tvalue['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $tvalue['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $tvalue['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $tvalue['naUpms']);
						}
						
						//No trial found row not shown when show only changed items is selected
						if($globalOptions['onlyUpdates'] == "no")
						{
							$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
						}
					}
				}
				else
				{
					if(isset($tvalue['naUpms']) && !empty($tvalue['naUpms']))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $tvalue['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $tvalue['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $tvalue['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $tvalue['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $tvalue['naUpms']);
						}
					}
					else
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">'
									. $tvalue['sectionHeader'] . '</td></tr>';
					}
					//No trial found row not shown when show only changed items is selected
					if($globalOptions['onlyUpdates'] == "no")
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
					}
				}
				
			}
			echo $outputStr;
		}
		
		echo '</table>';
		
		echo '<input type="hidden" name="cd" value="' 
			. rawurlencode(base64_encode(gzdeflate(serialize(array('a' => $Values['totactivecount'], 'in' => $Values['totinactivecount']))))) . '" />';
		echo '<input type="hidden" name="minenroll" id="minenroll" value="' . $globalOptions['minEnroll'] 
			. '" /><input type="hidden" name="maxenroll" id="maxenroll" value="' . $globalOptions['maxEnroll'] . '" />';	
		
		if($totalPages > 1)
		{
			echo '<div style="height:10px;">&nbsp;</div>';
			$this->pagination($globalOptions, $totalPages, $timeMachine, $ottType, $loggedIn);
		}
		echo '</form><br/>';
		
		if($Values['totalcount'] > 0 && ($ottType != 'unstackedoldlink' && $ottType != 'stackedoldlink')) 
		{
			echo '<div id="dropmenu" class="dropmenudiv" style="width: 310px;">'
				. $this->downloadOptions($count, $Values['totalcount'], $ottType, $resultIds, $globalOptions)
				. '</div><script type="text/javascript">cssdropdown.startchrome("chromemenu");</script>';
			
		}
		echo '<br/><br/>';
		if($linkExpiry !== NULL && $loggedIn)
		{
			echo '<span style="font-size:10px;color:red;">Expires on: ' . $linkExpiry  . '</span>';
		}
		echo '<div style="height:50px;"></div>';	//50Pixels extra space
	}
	
	function downloadOptions($shownCnt, $foundCnt, $ottType, $result, $globalOptions) 
	{	
		$downloadOptions = '<div style="height:180px; padding:6px;"><div class="downldbox"><div class="newtext">Download Options</div>'
							. '<form  id="frmDOptions" name="frmDOptions" method="post" target="_self">'
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
							. '<option value="pdf">PDF</option>'
							. '<option value="tsv">TSV</option>'
							. '</select></li></ul>'
							. '<input type="hidden" name="shownCnt" value="' . $shownCnt . '" />'
							. '<input type="submit" id="btnDownload" name="btnDownload" value="Download File" style="margin-left:8px;"  />'
							. '</form></div></div>';
		
		return $downloadOptions;
	}
	
	function displayTrialTableHeader($loggedIn, $globalOptions = array()) 
	{
		$outputStr = '<table cellpadding="0" cellspacing="0" class="manage">'
			 . '<tr>' . (($loggedIn) ? '<th width="38px">ID</th>' : '' )
			 . '<th style="width:270px;margin:0px;padding:0px;">Title</th>'
			 . '<th style="width:30px" title="Red: Change greater than 20%">N</th>'
			 . '<th style="width:65px" title="&quot;RoW&quot; = Rest of World">Region</th>'
			 . '<th style="width:100px">Interventions</th>'
			 . '<th style="width:90px">Sponsor</th>'
			 . '<th style="width:100px">Status</th>'
			 . '<th style="width:100px">Conditions</th>'
			 . '<th style="width:27px" title="MM/YY">End</th>'
			 . '<th style="width:25px">Ph</th>'
			 . '<th style="width:22px">Res</th>'
			 . '<th style="width:15px" colspan="3">-</th>'
			 . '<th style="width:35px" colspan="12">' . (date('Y')) . '</th>'
			 . '<th style="width:34px" colspan="12">' . (date('Y')+1) . '</th>'
			 . '<th style="width:30px" colspan="12">' . (date('Y')+2) . '</th>'
			 . '<th style="width:15px" colspan="3">+</th></tr>';
		
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
		
		if(isset($_REQUEST['sphinx_s']))
			{
				echo '<input type="hidden" name="sphinx_s" value="'.$_REQUEST['sphinx_s'].'" />';
			}
		elseif(isset($globalOptions['sphinx_s']))
			{
				echo '<input type="hidden" name="sphinx_s" value="'.$globalOptions['sphinx_s'].'" />';
			}
		
		if((isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') !== FALSE) 
		|| (isset($_GET['LI']) && $_GET['LI'] == 1))
		{
			echo '<input type="hidden" name="LI" value="1" />';
		}
		else
		{
			echo '<table width="100%">'
					. '<tr><td><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
					. '<td nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface work in progress</span>'
					. '<br/><span style="font-weight:normal;">Send feedback to '
					. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
					. 'larvoltrials@larvol.com</a></span></td>'
					. '<td class="result">' . $productAreaInfo . '</td></tr></table>'
					. '<br clear="all"/><br/>';
		}
	}
	
	function displayFilterControls($productSelector = array(), $productSelectorTitle, $shownCount, $activeCount, $inactiveCount, $totalCount, $globalOptions = array(), $ottType, $loggedIn)
	{	
		echo '<table border="0" cellspacing="0" class="controls" align="center" style="_width:100%; table-layout: fixed">'
				. '<tr><th style="width:113px">Active</th><th style="width:210px">Status</th>'
				. '<th style="width:170px">Institution type</th>'
				. '<th style="width:80px">Region</th><th style="width:50px">Phase</th><th class="right" style="width:340px">Filter</th></tr>'
				. '<tr><td class="bottom" style="padding-right:5px;">'
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
			echo '<td class="bottom">';
			foreach($this->institutionFilters as $ikey => $ivalue)
			{
				echo '<input type="checkbox" value="' . $ikey . '" id="institution_type_' . $ikey . '" class="institution" '
						. (in_array($ikey, $globalOptions['itype']) ? ' checked="checked" ' : '') . '/>'
						. '<label for="institution_type_' . $ikey . '">' . str_replace('_', ' ', ucfirst($ivalue)) . '</label><br />';
			}
			echo '</td>';
		}
		
		echo '<td class="bottom"><input type="checkbox" value="0" id="region_0" class="region" '
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
				. '</td><td class="bottom">'
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
				. '</td><td class="right" style="border-bottom:0px">'
				. '<div class="demo"><p>';
		
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
			echo '<label for="amount3">Highlight changes:</label>'
					. '<input type="hidden" id="startrange" name="sr" value="' . $globalOptions['startrange'] . '" />'
					. '<input type="text" id="endrange" name="er" value="' . $globalOptions['endrange'] 
					. '" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold;" />'
					. '<div id="slider-range-min" align="left"></div></p>';
		}
			
		echo '<input type="checkbox" id="showonlyupdated" name="osu" ' 
				. ($globalOptions['onlyUpdates'] == 'yes' ? ' checked="checked" ' : '' ) . ' />'
				. '<label for="showonlyupdated" style="font-size:x-small;">Show only changed items</label>'
				. '</div><br/><div class="demo"><p><label for="amount">Enrollment:</label>'
				. '<input type="text" name="enroll" id="amount" style="border:0; color:#f6931f; font-weight:bold;" '
				. ' value="' . ((isset($globalOptions['enroll'])) ? $globalOptions['enroll'] : '' ) . '" autocomplete="off" />'
				. '<div id="slider-range" align="left"></div>'
				. '</p></div>';
		if($ottType != 'unstacked' && $ottType != 'indexed' && $ottType != 'unstackedoldlink')
		{
			$title = strtolower(str_replace('Select', '', $productSelectorTitle));
			echo '<br/><input type="checkbox" id="ipwnd" name="ipwnd" ' . (($globalOptions['includeProductsWNoData'] == "on") ? 'checked="checked"' : '') . ' />'
				. '<label style="font-size:x-small;" for="ipwnd">Include ' . $title . ' with no data</label>';
		}
		
		echo '<br/><input type="checkbox" id="tspo" name="tspo" ' . (($globalOptions['showTrialsSponsoredByProductOwner'] == "on") ? 'checked="checked"' : '') . ' />'
				. '<label style="font-size:x-small;" for="tspo">Show only trials sponsored by product owner</label>';
				
		echo  '</tr><tr>'
				. '<td class="bottom">&nbsp;</td><td class="bottom">&nbsp;</td>'
				. '<td class="bottom">&nbsp;</td><td class="bottom">&nbsp;</td>'
				. '<td class="bottom">&nbsp;</td><td class="right bottom">';
				
		if(!empty($productSelector)
		&& ($ottType != 'unstacked' && $ottType != 'indexed' && $ottType != 'unstackedoldlink'))
		{
			echo '<div id="menuwrapper" style="vertical-align:bottom;margin-left: 2px;"><ul>';
			if(isset($globalOptions['product']) && !empty($globalOptions['product']))
			{	
				if(count($globalOptions['product']) > 1)
					$tTitle = count($globalOptions['product']) . strtolower(str_replace('Select', '', $productSelectorTitle)) . ' selected';
				else
					$tTitle = $productSelector[$globalOptions['product'][0]];
					
				echo '<li class="arrow"><a href="javascript: void(0);">' . $tTitle . '</a>';

			}
			else
			{	
				echo '<li class="arrow" style="height:23px;"><a href="javascript: void(0);">' . $productSelectorTitle . '</a>';
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
		echo '</td></tr></table><br/><br/>'
				. '<input type="hidden" name="status" id="status" value="' . implode(',', $globalOptions['status']) . '" />'
				. '<input type="hidden" name="itype" id="itype" value="' . implode(',', $globalOptions['itype']) . '" />'
				. '<input type="hidden" name="region" id="region" value="' . implode(',', $globalOptions['region']) . '" />'
				. '<input type="hidden" name="phase" id="phase" value="' . implode(',', $globalOptions['phase']) . '" />';
		
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
		if(isset($globalOptions['encodeFormat']) && $globalOptions['encodeFormat'] != 'old')
		{
			$url .= '&amp;format=' . $globalOptions['encodeFormat'];
		}
		if(isset($globalOptions['LI']) && $globalOptions['LI'] == '1')
		{
			$url .= '&amp;LI=1';
		}
		echo '<div style="float:left;margin-right:10px;">'
				. '<input type="submit" id="Show" value="" class="searchbutton" />&nbsp;<a style="display:inline;" href="' . $url . '">'
				. '<input type="button" value="" id="reset" class="resetbutton" onclick="javascript: window.location.href(\'' . urlPath() . $url . '\')" /></a>'
				. '&nbsp;&nbsp;&nbsp;<b>' . $shownCount . '&nbsp;Records</b></div>';
	}
	
	function pagination($globalOptions = array(), $totalPages, $timeMachine = NULL, $ottType, $loggedIn)
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
		
		if(isset($globalOptions['startrange']))
		{
			$url .= '&amp;sr=' . $globalOptions['startrange'];
		}
		if(isset($globalOptions['endrange']))
		{
			$url .= '&amp;er=' . $globalOptions['endrange'];
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
		
		if(isset($globalOptions['product']) && !empty($globalOptions['product']))
		{
			$url .= '&amp;pr=' . implode(',', $globalOptions['product']);
		}
		
		if(isset($globalOptions['includeProductsWNoData']) && $globalOptions['includeProductsWNoData'] == "on")
		{
			$url .= '&amp;ipwnd=on';
		}
		
		if(isset($_REQUEST['sphinx_s']))
		{
			$url .= '&amp;sphinx_s=' . $_REQUEST['sphinx_s'];
		}
		if( !isset($_REQUEST['sphinx_s']) and isset($globalOptions['sphinx_s']))
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
		
		$stages = 2;
		
		$paginateStr = '<div class="pagination" style="float: left; padding-top:2px; vertical-align:bottom;">';
		///ALL Quotation Marks SIGN REPLACED BY Apostrophe, CAUSE JSON DATA URL GET PROBLEM WITH double quote.
		// globalOptions Should always have Apostrophe instead of quote sign or data will not be passed
		if($globalOptions['page'] != 1)
		{
			$paginateStr .= '<a href=\'' . $url . '&page=' . ($globalOptions['page']-1) . '\'>&laquo; Prev</a>';
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
					$paginateStr .= '<a href=\'' . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
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
						$paginateStr .='<a href=\'' . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
					}
				}
				$paginateStr.= '<span>...</span>';
				$paginateStr.= '<a href=\'' . $url . '&page=' . ($totalPages-1) . '\'>' .  ($totalPages-1) . '</a>';
				$paginateStr.= '<a href=\'' . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
			}
			elseif($totalPages - ($stages * 2) > $globalOptions['page'] && $globalOptions['page'] > ($stages * 2))
			{
				$paginateStr.= '<a href=\'' . $url . '&page=1\'>1</a>';
				$paginateStr.= '<a href=\'' . $url . '&page=2\'>2</a>';
				$paginateStr.= '<span>...</span>';
				for($counter = $globalOptions['page'] - $stages; $counter <= $globalOptions['page'] + $stages; $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr.= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr.= '<a href=\'' . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
					}
				}
				$paginateStr.= '<span>...</span>';
				$paginateStr.= '<a href=\'' . $url . '&page=' . ($totalPages-1) . '\'>' . ($totalPages-1) . '</a>';
				$paginateStr.= '<a href=\'' . $url . '&page=' . $totalPages . '\'>' . $totalPages . '</a>';
			}
			else
			{
				$paginateStr .= '<a href=\'' . $url . '&page=1\'>1</a>';
				$paginateStr .= '<a href=\'' . $url . '&page=2\'>2</a>';
				$paginateStr .= "<span>...</span>";
				for($counter = $totalPages - (2 + ($stages * 2)); $counter <= $totalPages; $counter++)
				{
					if ($counter == $globalOptions['page'])
					{
						$paginateStr .= '<span>' . $counter . '</span>';
					}
					else
					{
						$paginateStr .= '<a href=\'' . $url . '&page=' . $counter . '\'>' . $counter . '</a>';
					}
				}
			}
		}
		
		if($globalOptions['page'] != $totalPages)
		{
			$paginateStr .= '<a href=\'' . $url . '&page=' . ($globalOptions['page']+1) . '\'>Next &raquo;</a>';
		}
		$paginateStr .= '</div>';
		
		echo $paginateStr;
	}
	
	function displayTrials($totalPages, $globalOptions = array(), $loggedIn, $start, $end, $Values, $ottType)
	{	
		$currentYear = date('Y');
		$secondYear = (date('Y')+1);
		$thirdYear = (date('Y')+2);
		
		$displayFlag = false;
		$outputStr = '';
		
		$start = $start - 1;
		$counter = 0;
		$finalkey = 0;
		
		foreach($Values['Trials'] as $vkey => $vvalue)
		{
			if(($counter >= $start && $counter < $end))
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					//Rendering Upms
					if(isset($vvalue['naUpms']) && !empty($vvalue['naUpms']) && !empty($vvalue[$globalOptions['type']]))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $vvalue['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $vvalue['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $vvalue['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $vvalue['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $vvalue['naUpms']);
						}
					}
					else if(isset($vvalue['naUpms']) && !empty($vvalue['naUpms']) && empty($vvalue[$globalOptions['type']]))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $vvalue['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $vvalue['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $vvalue['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $vvalue['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $vvalue['naUpms']);
						}
					}
					else if(empty($vvalue['naUpms']) && !empty($vvalue[$globalOptions['type']]))
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">'
									. $vvalue['sectionHeader'] . '</td></tr>';
					}
				}
				else
				{
					//Rendering Upms
					if(isset($vvalue['naUpms']) && !empty($vvalue['naUpms']))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $vvalue['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $vvalue['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $vvalue['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $vvalue['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $vvalue['naUpms']);
						}
					}
					else
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">'
									. $vvalue['sectionHeader'] . '</td></tr>';
					}
				}
				
				$displayFlag = true;
				$finalkey = $vkey;
			}
			
			foreach($vvalue[$globalOptions['type']] as $dkey => $dvalue)
			{	
				if($counter >= $start && $counter < $end)
				{	
					if(($displayFlag == false) && isset($globalOptions['page']) && $globalOptions['page'] > 1)
					{	
						$naUpms = $vvalue['naUpms'];//$Values['Trials'][$dvalue['section']]['naUpms'];
						$sectionHeader = $vvalue['sectionHeader'];//$Values['Trials'][$dvalue['section']]['sectionHeader'];
						
						//Rendering Upms
						if(isset($naUpms) && !empty($naUpms))
						{
							if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
							{
								$outputStr .= '<tr class="trialtitles">'
											. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
											. 'style="background: url(\'images/down.png\') no-repeat left center;"'
											. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
											. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $naUpms)
											. '<tr class="trialtitles">'
											. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
											. $sectionHeader . '</td></tr>';
							}
							else
							{
								if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
									$image = 'up';
								else
									$image = 'down';
								
								$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $sectionHeader);
								$naUpmIndex = substr($naUpmIndex, 0, 15);
								
								$outputStr .= '<tr class="trialtitles">'
											. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
											. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
											. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
											. $sectionHeader . '</td></tr>';
								$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $naUpms);
							}
						}
						else
						{
							$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  
										. '" class="sectiontitles">' . $sectionHeader . '</td></tr>';
						}
						
						$displayFlag = true;
						$finalkey = $vkey;
					}
					
					if($counter%2 == 1) 
						$rowOneType = 'alttitle';
					else
						$rowOneType = 'title';
					
					$rowspan = 1;
					$titleLinkColor = '#000000;';
				
					if(isset($dvalue['matchedupms']))  
						$rowspan = count($dvalue['matchedupms'])+1; 
						
					//row starts  
					$outputStr .= '<tr ' . (($dvalue['new'] == 'y') ? 'class="newtrial" ' : ''). '>';  
					
					
					//nctid column
					if($loggedIn) 
					{ 
						$outputStr .= '<td class="' . $rowOneType . '" ' . (($dvalue['new'] == 'y') ? 'title="New record"' : ''). ' >';
						if($ottType == 'indexed' || $ottType == 'colstackedindexed' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<a style="color:' . $titleLinkColor . '" href="' . urlPath() . 'edit_trials.php?larvol_id=' . $dvalue['larvol_id'] 
										. '" target="_blank">' . $dvalue['NCT/nct_id'] . '</a>';
						}
						else
						{
							$outputStr .= $dvalue['NCT/nct_id'] . '</a>';
						}
						$outputStr .= '</td>';
					}
					
					
					//acroynm and title column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{	
						if(!empty($dvalue['edited']) && array_key_exists('NCT/brief_title', $dvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/brief_title'];
							$titleLinkColor = '#FF0000;';
						} 
						elseif($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
							$titleLinkColor = '#FF0000;';
						}
						elseif(isset($dvalue['manual_brief_title']))
						{
							if($dvalue['original_brief_title'] == $dvalue['NCT/brief_title'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_brief_title'];
							}
							$titleLinkColor = '#FF7700';
						}
					}
					else
					{ 	
						if(isset($dvalue['manual_brief_title']))
						{
							if($dvalue['original_brief_title'] == $dvalue['NCT/brief_title'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_brief_title'];
							}
							$titleLinkColor = '#FF7700';
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('NCT/brief_title', $dvalue['edited']) &&   str_replace('Previous value: ', '', $dvalue['edited']['NCT/brief_title'])<> $dvalue['NCT/brief_title']) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/brief_title'];
							$titleLinkColor = '#FF0000;';
						} 
						elseif($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
							$titleLinkColor = '#FF0000;';
						}
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<div class="rowcollapse"><a style="color:' . $titleLinkColor . '"  ';
					
					if($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed')
					{	
						if(isset($dvalue['manual_is_sourceless']))
						{	
							$outputStr .= ' href="' . $dvalue['source'] . '" ';
						}
						else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') === FALSE)
						{	
							$outputStr .= ' href="https://www.clinicaltrialsregister.eu/ctr-search/search?query=' . $dvalue['NCT/nct_id'] . '" ';
						}
						else if(isset($dvalue['source_id']) && strpos($dvalue['source_id'], 'NCT') !== FALSE)
						{	
							$outputStr .= ' href="http://clinicaltrials.gov/ct2/show/' . padnct($dvalue['NCT/nct_id']) . '" ';
						}
						else 
						{ 	
							$outputStr .= ' href="javascript:void(0);" ';
						}
					}
					else
					{
						if($dvalue['NCT/nct_id'] !== '' && $dvalue['NCT/nct_id'] !== NULL)
						{
							$outputStr .= ' href="http://clinicaltrials.gov/ct2/show/' . padnct($dvalue['NCT/nct_id']) . '" ';
						}
						else 
						{ 
							$outputStr .= ' href="javascript:void(0);" ';
						}
					}
					
					$outputStr .= ' target="_blank" ';
					
					if(($ottType == 'indexed' || $ottType == 'rowstackedindexed' || $ottType == 'colstackedindexed'))
					{
						$outputStr .= ' onclick="INC_ViewCount(' . $dvalue['larvol_id'] . ')"><font id="ViewCount_' . $dvalue['larvol_id'] . '">';
						if($dvalue['viewcount'] != '' && $dvalue['viewcount'] != NULL && $dvalue['viewcount'] > 0)
						{
							$outputStr .= '<span class="viewcount" title="Total views">' . $dvalue['viewcount'].'&nbsp;</span>&nbsp;'; 
						}
						$outputStr .= '</font>'; 
					}
					else
						$outputStr .= '>'; 
								
					if(isset($dvalue['NCT/acronym']) && $dvalue['NCT/acronym'] != '') 
					{
						$dvalue['NCT/brief_title'] = $this->replaceRedundantAcroynm($dvalue['NCT/acronym'], $dvalue['NCT/brief_title']);
						$outputStr .= htmlformat($dvalue['NCT/acronym']) . ' ' . htmlformat($dvalue['NCT/brief_title']);
					} 
					else 
					{
						$outputStr .= htmlformat($dvalue['NCT/brief_title']);
					}
					
					
					//enrollment column
					$attr = ' ';
					$highlightFlag = true;
					if($globalOptions['onlyUpdates'] != "yes")
					{
						$prevValue = substr($dvalue['edited']['NCT/enrollment'], 16);
						$highlightFlag = getDifference($prevValue, $dvalue['NCT/enrollment']);
					}
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('NCT/enrollment', $dvalue['edited']) && $highlightFlag) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/enrollment'];
						}
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif(isset($dvalue['manual_enrollment']))
						{
							if($dvalue['original_enrollment'] == $dvalue['NCT/enrollment'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_enrollment'];
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_enrollment']))
						{
							if($dvalue['original_enrollment'] == $dvalue['NCT/enrollment'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_enrollment'];
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('NCT/enrollment', $dvalue['edited']) && $highlightFlag) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/enrollment'];
						}
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td nowrap="nowrap" rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">';
					if($dvalue["NCT/enrollment_type"] != '') 
					{
						if($dvalue["NCT/enrollment_type"] == 'Anticipated' || $dvalue["NCT/enrollment_type"] == 'Actual') 
						{ 
							$outputStr .= $dvalue["NCT/enrollment"];
						}
						else 
						{ 
							$outputStr .= $dvalue["NCT/enrollment"] . ' (' . $dvalue["NCT/enrollment_type"] . ')';
						}
					} 
					else 
					{
						$outputStr .= $dvalue["NCT/enrollment"];
					}
					$outputStr .= '</div></td>';	
					
					
					//region column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if($dvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
						elseif(isset($dvalue['manual_region']))
						{
							$attr = ' manual" title="Manual curation.';
						}
					}
					else
					{
						if(isset($dvalue['manual_region']))
						{
							$attr = ' manual" title="Manual curation.';
						}
						elseif($dvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' . '<div class="rowcollapse">' 
								. (($dvalue['region'] != '' && $dvalue['region'] !== NULL) ? $dvalue['region'] : '&nbsp;') . '</div></td>';	
								
					
					//intervention name column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('NCT/intervention_name', $dvalue['edited']))
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/intervention_name'];
						} 
						else if($dvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
						elseif(isset($dvalue['manual_intervention_name']))
						{
							if($dvalue['original_intervention_name'] == $dvalue['NCT/intervention_name'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_intervention_name'];
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_intervention_name']))
						{
							if($dvalue['original_intervention_name'] == $dvalue['NCT/intervention_name'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_intervention_name'];
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('NCT/intervention_name', $dvalue['edited']) && str_replace('Previous value: ', '', $dvalue['edited']['NCT/intervention_name'])<>$dvalue['NCT/intervention_name'])
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/intervention_name'];
						} 
						else if($dvalue['new'] == 'y')
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<div class="rowcollapse">' . $dvalue['NCT/intervention_name'] . '</div></td>';	
								
					
					//collaborator and sponsor column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && (array_key_exists('NCT/collaborator', $dvalue['edited']) 
						|| array_key_exists('NCT/lead_sponsor', $dvalue['edited']))) 
						{
							$attr = ' highlight" title="';
							if(array_key_exists('NCT/lead_sponsor', $dvalue['edited']))
							{
								$attr .= $dvalue['edited']['NCT/lead_sponsor'] . ' ';
							}
							if(array_key_exists('NCT/collaborator', $dvalue['edited'])) 
							{
								$attr .= $dvalue['edited']['NCT/collaborator'];
							}
							$attr .= '';
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif(isset($dvalue['manual_lead_sponsor']) || isset($dvalue['manual_collaborator']))
						{
							if(isset($dvalue['manual_lead_sponsor']))
							{
								if($dvalue['original_lead_sponsor'] == $dvalue['NCT/lead_sponsor'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								else
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_lead_sponsor'];
								}
							}
							else
							{
								if($dvalue['original_collaborator'] == $dvalue['NCT/collaborator'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								else
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_collaborator'];
								}
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_lead_sponsor']) || isset($dvalue['manual_collaborator']))
						{
							if(isset($dvalue['manual_lead_sponsor']))
							{
								if($dvalue['original_lead_sponsor'] == $dvalue['NCT/lead_sponsor'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								else
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_lead_sponsor'];
								}
							}
							else
							{
								if($dvalue['original_collaborator'] == $dvalue['NCT/collaborator'])
								{
									$attr = ' manual" title="Manual curation.';
								}
								else
								{
									$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_collaborator'];
								}
							}
						}
						elseif(!empty($dvalue['edited']) && (array_key_exists('NCT/collaborator', $dvalue['edited']) 
						|| array_key_exists('NCT/lead_sponsor', $dvalue['edited'])) && ( str_replace('Previous value: ', '', $dvalue['edited']['NCT/lead_sponsor'])<>$dvalue['NCT/lead_sponsor'] or str_replace('Previous value: ', '', $dvalue['edited']['NCT/collaborator'])<>$dvalue['NCT/collaborator'] )) 
						{
							$attr = ' highlight" title="';
							if(array_key_exists('NCT/lead_sponsor', $dvalue['edited']))
							{
								$attr .= $dvalue['edited']['NCT/lead_sponsor'] . ' ';
							}
							if(array_key_exists('NCT/collaborator', $dvalue['edited'])) 
							{
								$attr .= $dvalue['edited']['NCT/collaborator'];
							}
							$attr .= '';
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<div class="rowcollapse">' . $dvalue['NCT/lead_sponsor'] . ' ' . $dvalue["NCT/collaborator"] . '</div></td>';
								
								
					//overall status column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('NCT/overall_status', $dvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/overall_status'];
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record' ;
						} 
						elseif(isset($dvalue['manual_overall_status']))
						{
							if($dvalue['original_overall_status'] == $dvalue['NCT/overall_status'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_overall_status'];
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_overall_status']))
						{
							if($dvalue['original_overall_status'] == $dvalue['NCT/overall_status'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_overall_status'];
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('NCT/overall_status', $dvalue['edited']) && str_replace('Previous value: ', '', $dvalue['edited']['NCT/overall_status'])<>$dvalue['NCT/overall_status']) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/overall_status'];
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record' ;
						} 
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' . '<div class="rowcollapse">' 
								. (($dvalue['NCT/overall_status'] != '' && $dvalue['NCT/overall_status'] !== NULL) ? $dvalue['NCT/overall_status'] : '&nbsp;')
								. '</div></td>';
								
								
					//condition column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('NCT/condition', $dvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/condition'];
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						else if(isset($dvalue['manual_condition']))
						{
							if($dvalue['original_condition'] == $dvalue['NCT/condition'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_condition'];
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_condition']))
						{
							if($dvalue['original_condition'] == $dvalue['NCT/condition'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_condition'];
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('NCT/condition', $dvalue['edited']) && str_replace('Previous value: ', '', $dvalue['edited']['NCT/condition'])<>$dvalue['NCT/condition']) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/condition'];
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">'
								. '<div class="rowcollapse">' . $dvalue['NCT/condition'] . '</div></td>';
								
					
					$borderLeft = '';	
					if(!empty($dvalue['edited']) && array_key_exists('NCT/start_date', $dvalue['edited']))
					{
						$borderLeft = 'startdatehighlight';
					}
							
					//end date column
					$attr = ' ';
					$borderRight = '';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('inactive_date', $dvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['inactive_date'];
							$borderRight = 'border-right-color:red;';
						} 
						else if($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}	
						elseif(isset($dvalue['manual_end_date']))
						{
							if($dvalue['original_end_date'] == $dvalue['inactive_date'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_end_date'];
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_end_date']))
						{
							if($dvalue['original_end_date'] == $dvalue['inactive_date'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_end_date'];
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('inactive_date', $dvalue['edited']) && str_replace('Previous value: ', '', $dvalue['edited']['inactive_date'])<>$dvalue["inactive_date"]) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['inactive_date'];
							$borderRight =  'border-right-color:red;';
						} 
						elseif($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}	
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '"><div class="rowcollapse">'; 
					if($dvalue["inactive_date"] != '' && $dvalue["inactive_date"] != NULL && $dvalue["inactive_date"] != '0000-00-00') 
					{
						$outputStr .= date('m/y',strtotime($dvalue["inactive_date"]));
					} 
					else 
					{
						$outputStr .= '&nbsp;';
					}
					$outputStr .= '</div></td>';
					
					
					//phase column
					$attr = ' ';
					if(isset($dvalue['manual_is_sourceless']))
					{
						if(!empty($dvalue['edited']) && array_key_exists('NCT/phase', $dvalue['edited'])) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/phase'];
						} 
						elseif($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
						elseif(isset($dvalue['manual_phase']))
						{
							if($dvalue['original_phase'] == $dvalue['NCT/phase'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_phase'];
							}
						}
					}
					else
					{
						if(isset($dvalue['manual_phase']))
						{
							if($dvalue['original_phase'] == $dvalue['NCT/phase'])
							{
								$attr = ' manual" title="Manual curation.';
							}
							else
							{
								$attr = ' manual" title="Manual curation. Original value: ' . $dvalue['original_phase'];
							}
						}
						elseif(!empty($dvalue['edited']) && array_key_exists('NCT/phase', $dvalue['edited']) && ( str_replace('Previous value: ', '', trim($dvalue['edited']['NCT/phase'])) <> trim($dvalue['NCT/phase'])) ) 
						{
							$attr = ' highlight" title="' . $dvalue['edited']['NCT/phase'];
						} 
						elseif($dvalue['new'] == 'y') 
						{
							$attr = '" title="New record';
						}
					}
					
					if($dvalue['NCT/phase'] == 'N/A' || $dvalue['NCT/phase'] == '' || $dvalue['NCT/phase'] === NULL)
					{
						$phase = 'N/A';
						$phaseColor = $this->phaseValues['N/A'];
					}
					else
					{
						$phase = str_replace('Phase ', '', trim($dvalue['NCT/phase']));
						$phaseColor = $this->phaseValues[$phase];
					}
					$outputStr .= '<td rowspan="' . $rowspan . '" class="' . $rowOneType . $attr . '">' 
								. '<div class="rowcollapse">' . $phase . '</div></td>';				
					
					$startMonth = date('m',strtotime($dvalue['NCT/start_date']));
					$startYear = date('Y',strtotime($dvalue['NCT/start_date']));
					$endMonth = date('m',strtotime($dvalue['inactive_date']));
					$endYear = date('Y',strtotime($dvalue['inactive_date']));
					
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
						$dvalue['NCT/start_date'], $dvalue['inactive_date'], $phaseColor, $borderRight, $borderLeft);
						
					$outputStr .= '</tr>';	
					
					//rendering matched upms
					if(isset($dvalue['matchedupms']) && !empty($dvalue['matchedupms'])) 
					{
						foreach($dvalue['matchedupms'] as $mkey => $mvalue) 
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
							if(!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'start_date')
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
							
							$outputStr .= '<td ';
							if($stYear < $currentYear)
							{
								$outputStr .= 'class="' . $upmBorderLeft . '"';
							}
							
							$outputStr .= ' style="text-align:center;vertical-align:middle;' . (($mkey != 0) ? 'border-top:0px;' : '') . '">';
							
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
								$outputStr .= ' style="padding-top: 3px;" border="0" onclick="INC_ViewCount('.$dvalue['larvol_id'].')" /></a>';
							}
							else if($mvalue['status'] == 'Pending')
							{
								$icon = '<img src="images/hourglass.png" alt="Hourglass"  border="0" onclick="INC_ViewCount(' . $dvalue['larvol_id'] . ')" />';
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
							if(!empty($mvalue['edited']) && $mvalue['edited']['field'] == 'end_date')
							{
								$upmBorderRight = 'border-right-color:red;';
							}
							
							//rendering upm (upcoming project completion) chart
							$outputStr .= $this->upmGnattChart($stMonth, $stYear, $edMonth, $edYear, $currentYear, $secondYear, $thirdYear, $mvalue['start_date'],
							$mvalue['end_date'], $mvalue['event_link'], $upmTitle, $upmBorderRight, $upmBorderLeft, $dvalue['larvol_id'], $incViewCount);
							$outputStr .= '</tr>';
						}
					}	
					
				}
				$counter++;
				//$displayFlag = true;
			}
			
			if($counter >= $start && $counter < $end && empty($vvalue[$globalOptions['type']]) && $globalOptions['onlyUpdates'] == "no")
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					if(isset($vvalue['naUpms']) && !empty($vvalue['naUpms']))
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
					}
				}
				else
				{
					$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
				}
			}
		}
		
		if($globalOptions['page'] == $totalPages && $finalkey < $vkey)
		{
			for($index = $finalkey+1; $index <= $vkey; $index++)
			{
				if($globalOptions['includeProductsWNoData'] == "off")
				{
					if(isset($Values['Trials'][$index]['naUpms']) && !empty($Values['Trials'][$index]['naUpms']))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $Values['Trials'][$index]['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $Values['Trials'][$index]['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $Values['Trials'][$index]['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $Values['Trials'][$index]['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $Values['Trials'][$index]['naUpms']);
						}
						if($globalOptions['onlyUpdates'] == "no")
						{
							$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
						}
					}
				}
				else
				{
					//Rendering Upms
					if(isset($Values['Trials'][$index]['naUpms']) && !empty($Values['Trials'][$index]['naUpms']))
					{
						if($ottType == 'rowstacked' || $ottType == 'rowstackedindexed')
						{
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. 'style="background: url(\'images/down.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'rowstacked\');">&nbsp;</td></tr>'
										. $this->displayUnMatchedUpms($loggedIn, 'rowstacked', $Values['Trials'][$index]['naUpms'])
										. '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="sectiontitles">' 
										. $Values['Trials'][$index]['sectionHeader'] . '</td></tr>';
						}
						else
						{
							if($ottType == 'colstacked' || $ottType == 'colstackedindexed')
								$image = 'up';
							else
								$image = 'down';
							
							$naUpmIndex = preg_replace('/[^a-zA_Z0-9]/i', '', $Values['Trials'][$index]['sectionHeader']);
							$naUpmIndex = substr($naUpmIndex, 0, 15);
							
							$outputStr .= '<tr class="trialtitles">'
										. '<td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="upmpointer sectiontitles"'
										. ' style="background: url(\'images/' . $image . '.png\') no-repeat left center;"'
										. ' onclick="sh(this,\'' . $naUpmIndex . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' 
										. $Values['Trials'][$index]['sectionHeader'] . '</td></tr>';
							$outputStr .= $this->displayUnMatchedUpms($loggedIn, $naUpmIndex, $Values['Trials'][$index]['naUpms']);
						}
					}
					else
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn)  . '" class="sectiontitles">'
									. $Values['Trials'][$index]['sectionHeader'] . '</td></tr>';
					}
					if($globalOptions['onlyUpdates'] == "no")
					{
						$outputStr .= '<tr><td colspan="' . getColspanBasedOnLogin($loggedIn) . '" class="norecord">No trials found</td></tr>';
					}
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
	
	function getTrialUpdates($nctId, $larvolId, $timeMachine = NULL, $timeInterval)
	{	
		global $now;
		
		if($timeMachine === NULL) $timeMachine = $now;
		
		$updates = array('edited' => array(), 'new' => 'n');
		
		$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'acronym', 'overall_status','condition', 
							'intervention_name', 'phase', 'lead_sponsor', 'collaborator', 'start_date');

		$studycatData = mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON "
				. "(`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='" . $nctId . "' AND `dc`.`larvol_id`='" .$larvolId . "'"));

		$res = mysql_query("SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat` "
				. "FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" 
				. join("','", $fieldnames) . "') AND `studycat` = '" . $studycatData['studycat'] 
				. "' AND (`dv`.`superceded`<= '" . date('Y-m-d', $timeMachine) . "' AND `dv`.`superceded`>= '" 
				. date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "') ");
		
		while ($row = mysql_fetch_assoc($res)) 
		{
			//getting previous value for updated trials
			$result = mysql_fetch_assoc(mysql_query("SELECT `" . 'val_'.$row['fieldtype'] . "` AS value FROM `data_values` WHERE `studycat` = '" 
				. $studycatData['studycat'] . "' AND `field` =  '" . $row['fieldid'] . "' AND (`superceded` <= '" . date('Y-m-d', $timeMachine) 
				. "' AND `superceded` >= '" . date('Y-m-d', strtotime($timeInterval, $timeMachine)) . "') "));
		
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
		
		$query = "SELECT inactive_date_prev FROM `clinical_study` WHERE larvol_id = '" . $larvolId . "' AND (inactive_date_lastchanged <= '" 
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
		
		$result = mysql_query("SELECT id, event_type, corresponding_trial, event_description, event_link, result_link, start_date, end_date, status "
								. "FROM upm WHERE corresponding_trial = '" . $trialId . "' ORDER BY `end_date` ASC, `start_date` ASC ");
		
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
					. " WHERE `id` = '" . $row['id'] . "' AND (CAST(`change_date` AS DATE) <= '" . date('Y-m-d', $timeMachine) 
					. "' AND CAST(`change_date` AS DATE) >= '" . date('Y-m-d', strtotime($timeInterval ,$timeMachine)) 
					. "') ORDER BY `change_date` DESC LIMIT 0,1 ";
			$res = mysql_query($sql);
			
			$upm['matchedupms'][$i]['edited'] = array();
			$upm['matchedupms'][$i]['new'] = 'n';
			
			while($arr = mysql_fetch_assoc($res)) 
			{
				$upm['matchedupms'][$i]['edited']['id'] = $arr['id'];
				$upm['matchedupms'][$i]['edited']['field'] = $arr['field'];
				$upm['matchedupms'][$i]['edited'][$arr['field']] = $arr['old_value'];
			}
			
			$query = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $row['id'] . "' AND u.`last_update` <= '" 
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

	function getUnMatchedUPMs($naUpmsRegex, $naUpmsNegateRegex,$timeMachine = NULL, $timeInterval = NULL, $onlyUpdates, $productId = NULL)
	{	
		global $now;
		
		$where = '';
		$naUpms = array();
		$i = 0;
		
		if($timeMachine === NULL) $timeMachine = $now;
		
		$naUpmsRegex = array_filter($naUpmsRegex);
		$naUpmsNegateRegex = array_filter($naUpmsNegateRegex);
		
		if(!empty($naUpmsRegex))
		{	$where = ' ( ';
			foreach($naUpmsRegex as $ukey => $uvalue)
			{
				$where .= textEqual('`search_name`', $uvalue) . ' OR ';
			}
			$where = substr($where, 0, -3);
			$where .= ' ) ';
		}
		
		if(!empty($naUpmsNegateRegex))
		{	
			if(!empty($naUpmsRegex))
			{
				$where .= ' AND ';
			}
			
			$where .= ' (`id` NOT IN (SELECT `id` FROM `upm` WHERE ( ';
			foreach($naUpmsNegateRegex as $nkey => $nvalue)
			{
				$where .= textEqual('`search_name`', $nvalue) . ' OR ';
			}
			$where = substr($where, 0, -3);
			$where .= ' ) ) )';
		}
		
		if(!empty($where) && $where != "")
		{	
			$result = mysql_query("SELECT `id`, `name` FROM `products` WHERE " . $where . " ");
			if(mysql_num_rows($result) > 0) 
			{
				while($rows = mysql_fetch_assoc($result)) 
				{
					$query = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`, `status`, " 
							. " `start_date_type`, `end_date`, `end_date_type` FROM `upm` WHERE `corresponding_trial` IS NULL AND `product` = '" . $rows['id'] 
							. "' ORDER BY `end_date` ASC, `start_date` ASC ";
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
									. " WHERE `id` = '" . $row['id'] . "' AND (CAST(`change_date` AS DATE) <= '" . date('Y-m-d', $timeMachine) 
									. "' AND CAST(`change_date` AS DATE) >= '" . date('Y-m-d',strtotime($timeInterval, $timeMachine)) . "') ORDER BY `change_date` DESC LIMIT 0,1 ";
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
							
							$sql = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $row['id'] 
									. "' AND u.`last_update` <= '" . date('Y-m-d', $timeMachine) . "' AND u.`last_update` >=  '" 
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
							. " WHERE `id` = '" . $row['id'] . "' AND (CAST(`change_date` AS DATE) <= '" . date('Y-m-d', $timeMachine) 
							. "' AND CAST(`change_date` AS DATE) >= '" . date('Y-m-d',strtotime($timeInterval, $timeMachine)) . "') ORDER BY `change_date` DESC LIMIT 0,1 ";
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
					
					$sql = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $row['id'] 
							. "' AND u.`last_update` <= '" . date('Y-m-d', $timeMachine) . "' AND u.`last_update` >=  '" 
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
				$upmTitle = htmlformat($value['event_description']);
				
				$upmBorderLeft = '';
				if(!empty($value['edited']) && $value['edited']['field'] == 'start_date')
				{
					$upmBorderLeft = 'startdatehighlight';
				}
				
				//Highlighting the whole row in case of new trials
				if($value['new'] == 'y') 
				{
					$class = 'class="upms newtrial ' . $naUpmIndex . '" ';
				}
				
				//rendering unmatched upms
				$outputStr .= '<tr ' . $class . '>';
				
				
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
				
				
				//field upm event description
				$title = '';
				$attr = '';	
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
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'event_link')) 
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
				$outputStr .= '<td class="' . $attr . '" ' . $title . '><div class="rowcollapse">' . $value['event_type'] . ' Milestone</div></td>';
				
				
				//field upm end date
				$title = '';
				$attr = '';	
				$upmBorderRight = '';
				
				if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date'))
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
				else if(!empty($value['edited']) && ($value['edited']['field'] == 'end_date_type'))
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
	
	function replaceRedundantAcroynm($Acroynm, $briefTitle)
	{
		$pattern = '~^\(*"*' . $Acroynm . '*\)*:*~';
		$replacement = '';
		$result = preg_replace($pattern, $replacement, $briefTitle);
		
		return $result;
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

function Build_OTT_Query($data, $Passed_where)
{
	$actual_query = "";
	try {
		$jsonData=$data;
		$filterData = json_decode($jsonData, true, 10);
		if(is_array($filterData))
		array_walk_recursive($filterData, 'searchHandlerBackTicker','columnname');
		if(is_array($filterData))
		array_walk_recursive($filterData['columndata'], 'searchHandlerBackTicker','columnas');
		$alias= " dt"; //data_trial table alias
		$pd_alias= " pd"; //Products table alias
		$ar_alias= " ar"; //Areas table alias
		
		$where_datas = $filterData["wheredata"];
		$select_columns=$filterData["columndata"];
		$override_vals = trim($filterData["override"]);
		$sort_datas = $filterData["sortdata"];
		$isOverride = !empty($override_vals);
		
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
		
		//$select_str = getSelectString($select_columns, $alias, $pd_alias, $ar_alias);	////////////// CURRENTLY WE DONE NEED THIS PART AS OTT HAS FIXED COLUMNS
		$select_str = "".$alias.".`larvol_id`, ".$alias.".`source_id`, ".$alias.".`brief_title`, ".$alias.".`acronym`, ".$alias.".`lead_sponsor`, ".$alias.".`collaborator`, ".$alias.".`condition`,"
					. " ".$alias.".`overall_status`, ".$alias.".`is_active`, ".$alias.".`start_date`, ".$alias.".`end_date`, ".$alias.".`enrollment`, ".$alias.".`enrollment_type`, ".$alias.".`intervention_name`,"
					. " ".$alias.".`region`, ".$alias.".`lastchanged_date`, ".$alias.".`phase`, ".$alias.".`overall_status`, ".$alias.".`lastchanged_date`, ".$alias.".`firstreceived_date`, ".$alias.".`viewcount` ";
		
		$where_str = get_WhereString($where_datas, $alias, $pd_alias, $ar_alias);
		$sort_str = getSortString($sort_datas, $alias, $pd_alias, $ar_alias);


		if($isOverride)
		{
			$actual_query .= "(";
		}

		$actual_query .= "SELECT ";

		$actual_query .= $select_str;
		

		$actual_query .= " FROM data_trials " . $alias;
		
		if($prod_flag)
		$actual_query .= " JOIN product_trials pt ON (pt.`trial`=".$alias.".`larvol_id`) JOIN products ". $pd_alias ." ON (". $pd_alias .".`id`=pt.`product`)";
		
		if($area_flag)
		$actual_query .= " JOIN area_trials at ON (at.`trial`=".$alias.".`larvol_id`) JOIN areas ". $ar_alias ." ON (". $ar_alias .".`id`=at.`area`)";

		
		if(strlen(trim($where_str)) != 0 || strlen(trim($Passed_where)) != 0)
		{
			$actual_query .= " WHERE ";
			if(strlen(trim($Passed_where)) != 0) 
			{
				$Passed_where = substr($Passed_where, 4);
				$actual_query .= $Passed_where;
			}
			if(strlen(trim($where_str)) != 0 && strlen(trim($Passed_where)) != 0) $actual_query .= " AND ";
			if(strlen(trim($where_str)) != 0) $actual_query .= $where_str;
		}

		if((strlen(trim($sort_str)) != 0))//Sort
		{
			$actual_query .= " ORDER BY " . $sort_str;
		}
		else
		{
			$actual_query .=" ORDER BY ".$alias.".`phase` DESC, ".$alias.".`end_date` ASC, ".$alias.".`start_date` ASC, ".$alias.".`overall_status` ASC, ".$alias.".`enrollment` ASC ";	//Default Sort
		}

		if($isOverride)//override string present
		{

	 		$override_str = getNCTOverrideString($override_vals, $alias, $pd_alias, $ar_alias, $select_str, $isCount, $prod_col, $area_col);
	  		$actual_query .= ") UNION (" . $override_str . ")";
	  	}
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $actual_query;
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
		if($pos === false)
		{
			//do nothing
		}
		else
		{
			$wherestr .= str_replace('.', '', $prevchain);//if . is present remove it and empty
		}
		//                if($pos == true)
		//                    $wherestr .= $prevchain;
	}
	catch(Exception $e)
	{
		throw $e;
	}
	return $wherestr;
}
?>