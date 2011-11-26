<?php
require_once('db.php');
require_once('include.search.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once 'PHPExcel/IOFactory.php';
require_once('special_chars.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','300');	//5 minutes

if(!isset($_POST['cparams']) && !isset($_POST['params']) && !isset($_POST['results'])) return (false);
$content = new ContentManager();
$content->setSortParams();
$content->chkType();
$non_assoc_upm_params=array();
$unmatched_upm_details = array();
class ContentManager 
{
	
	private $params 	= array();
	private $fid 		= array();
	private $allfilterarr 	= array();
	private $sortorder;
	private $sort_params 	= array();
	private $sortimg 	= array();
						
								
	private $actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
								'anr'=>'Active, not recruiting', 'a'=>'Available','nlr' =>'No longer recruiting');
								
	private $inactfilterarr = array('wh'=>'Withheld', 'afm'=>'Approved for marketing',
								'tna'=>'Temporarily not available', 'nla'=>'No Longer Available', 'wd'=>'Withdrawn', 
								't'=>'Terminated','s'=>'Suspended', 'c'=>'Completed', 'empt'=>'');
	private $phase_arr 		= array('N/A'=>'#BFBFBF', '0'=>'#00CCFF', '0/1'=>'#99CC00', '1'=>'#99CC00', '1a'=>'#99CC00', '1b'=>'#99CC00', '1a/1b'=>'#99CC00', 
					'1c'=>'#99CC00', '1/2'=>'#FFFF00', '1b/2'=>'#FFFF00', '1b/2a'=>'#FFFF00', '2'=>'#FFFF00', '2a'=>'#FFFF00', '2a/2b'=>'#FFFF00', 
					'2a/b'=>'#FFFF00', '2b'=>'#FFFF00', '2/3'=>'#FF9900', '2b/3'=>'#FF9900','3'=>'#FF9900', '3a'=>'#FF9900', '3b'=>'#FF9900', '3/4'=>'#FF0000', 
					'3b/4'=>'#FF0000', '4'=>'#FF0000');
	
	//$nodata = array('action' => array(), 'searchval' => array());
	private $bomb_type_arr = array('sb'=>'small', 'lb'=>'large');
	private $bomb_img_arr = array('sb'=>'sbomb.png', 'lb'=>'lbomb.png');

	private $edited;
	private $e_style;
	private $p_style;
	private $actflag;
	private $inactflag;
	private $allflag;
	private $current_yr;
	private $second_yr;
	private $third_yr;
	private $type;
	private $page;
	private $results_per_page;
	private $dispcount;
	private $activecount;
	private $allcount;
	private $inactivecount;
	private $time_machine;
	private $loggedIn;
	
	public function __construct() {
	
		$db = new DatabaseManager();
		$this->results_per_page = $db->set['results_per_page'];
		$this->loggedIn	= $db->loggedIn();
//		$this->now = $now;
		

		$this->allfilterarr = array_merge($this->actfilterarr, $this->inactfilterarr);	
		
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
		$this->fid['primary_completion_date'] 	= '_' . getFieldId('NCT', 'primary_completion_date');
		$this->fid['completion_date'] 			= '_' . getFieldId('NCT', 'completion_date');
		$this->fid['acronym'] 					= '_' . getFieldId('NCT', 'acronym');
		$this->fid['inactive_date']				= 'inactive_date';
		$this->fid['region']					= 'region';
		
		$this->current_yr	= date('Y');
		$this->second_yr	= date('Y')+1;
		$this->third_yr		= date('Y')+2;

		$this->activecount = 0;
		$this->allcount = 0;
		$this->inactivecount = 0;

		
		if((isset($_POST["dOption"])) and $_POST["dOption"]=='all')
			{
				$_POST['list'] = 'all';
			}

		$this->type = (isset($_POST["list"])) ? ($_POST["list"].'array') : 'activearray' ;
		if(isset($_POST['list']) && $_POST['list'] == 'inactive') { 
			$this->inactflag = 1; 		// checking if any of the inactive filters are set
			
		} else if(isset($_POST['list']) && $_POST['list'] == 'all') {
			$this->allflag = 1; 	 	// checking if any of the all filters are set
			
		} else { 
			$this->actflag = 1; 		// checking if any of the active filters are set
		}
		
	}
	

	
	function commonControls($count, $act, $inact, $all) {
	
		$enumvals = getEnumValues('clinical_study', 'institution_type');
	
	}
	
		
	function processParams() {
		
		$return_param	= array();
		$return_param['fin_arr'] = array();
		$return_param['upmDetails'] = array();
		$ins_params		= array();
		$return_param['showRecordsCnt'] = 0;
		
		if(isset($_POST['results']) && isset($_POST['type'])) {
			
			$this->time_machine = $_POST['time'];
			if(isset($_POST['format']) && $_POST['format'] == 'new') {
				//pack encoding method used to encode data in the url
				$results = unpack("l*", gzinflate(base64_decode(rawurldecode($_POST['results']))));
				$three = 0;
				$lengthcounter = 0; 
				$string = '';
				foreach($results as $vals)
				{
					if($lengthcounter == 0)
					{
						$lengthcounter = $vals;
						continue;
					}
					$string .= $vals . '.';
					$three++;
					if($three == $lengthcounter)
					{
						$output[] = substr($string, 0, -1);
						$three = 0;
						$lengthcounter = 0;
						$string = '';
					}
				}
				$return_param['c_params'] = $output;
			} else { 
				//no specific encoding method i.e. only implode used to encode data in the url
				$return_param['c_params'] = explode(',', gzinflate(base64_decode($_POST['results'])));
			}
			
			$vv = explode('.', $return_param['c_params'][0]);
			if($_POST['type'] == 'col') {
				
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $vv[1]);
				$t = 'Area: ' . $res['header'];
				$link_expiry_date = $res['expiry'];
				
			} else if($_POST['type'] == 'row') {
			
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $vv[0]);
				$t = 'Product: ' . $res['header'];
				$link_expiry_date = $res['expiry'];
			}
			$return_param['params_arr'] = $return_param['c_params'];
				
		} else {
		
			$return_param['c_params'] 	= unserialize(gzinflate(base64_decode($_POST['cparams'])));
			$stack_type = ($return_param['c_params']['type'] == 'col') ? 'rowlabel' : 'columnlabel';
			if($return_param['c_params']['type'] == 'col') {
				$t = 'Area: ' . $return_param['c_params']['columnlabel'];
			} else {
				$t = 'Product: ' . $return_param['c_params']['rowlabel'];
			}
				
					
			$return_param['params_arr'] = $_POST['params'];
		}
		
		if(isset($_POST['institution']) && $_POST['institution'] != '') {
				
			array_push($this->fid, 'institution_type');
			$sp = new SearchParam();
			$sp->field 	= 'institution_type';
			$sp->action = 'search';
			$sp->value 	= $_POST['institution'];
			$ins_params = array($sp);
		}
		
		foreach($return_param['params_arr'] as $pk => $pv) {
		
			$excel_params 	= array();
			$params = array();
			$arr 	= array();
			$arrr 	= array();

			$return_param['fin_arr'][$pk] = array();
			$return_param['link_expiry_date'][$pk] = array();
			$totinactivecount = 0; 
			$totactivecount	 = 0; 
			
			//New Link Method
			if(isset($_POST['results'])) {
			
				$e 	= explode(".", $pv);$identifier_for_result_set = '';
				$return_param['link_expiry_date'][$pk][] = $link_expiry_date;
				//Retrieving headers
				if($_POST['type'] == 'row') {
				
					if($pk != 0) {
						$res = getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						//$tt = $e[1];
						//result set separator as a separate parameter and maintaining backward compatibility
						if($e[1] == '-1' || $e[1] == '-2') {
							$tt = $e[2];
							$identifier_for_result_set = $e[1];
						} else {
							$tt = $e[1];
						}
					} else {
						$res = getLinkDetails('rpt_ott_header', 'header', 'id', $e[1]);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						//$tt = $e[2];
						//result set separator as a separate parameter and maintaining backward compatibility
						if($e[2] == '-1' || $e[2] == '-2') {
							$tt = $e[3];
							$identifier_for_result_set = $e[2];
						} else {
							$tt = $e[2];
						}
					}	
					
				} else if($_POST['type'] == 'col') {
				
					if($pk != 0) {
						$res = getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						//$tt = $e[1];
						//result set separator as a separate parameter and maintaining backward compatibility
						if($e[1] == '-1' || $e[1] == '-2') {
							$tt = $e[2];
							$identifier_for_result_set = $e[1];
						} else {
							$tt = $e[1];
						}
					} else {
						$res = getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						//$tt = $e[2];
						//result set separator as a separate parameter and maintaining backward compatibility
						if($e[2] == '-1' || $e[2] == '-2') {
							$tt = $e[3];
							$identifier_for_result_set = $e[2];
						} else {
							$tt = $e[2];
						}
					}	
				}
				
				//Retrieving params
				//result set separator as a separate parameter and maintaining backward compatibility
				if($identifier_for_result_set == '-1' || $identifier_for_result_set == '-2'){
				
					if($identifier_for_result_set == '-2') {
					
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', $tt);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$search_data_content = $res['result_set'];
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($search_data_content))));
					} else if($identifier_for_result_set == '-1') {
					
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $tt);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						if($res['result_set'] != '') {
							$sp = new SearchParam();
							$sp->field = 'larvol_id';
							$sp->action = 'search';
							$sp->value = str_replace(',', ' OR ', $res['result_set']);
							$excel_params = array($sp);
						} 
						
					}
					
				} else {
				
					$searchdata = substr($tt,0,3);
					if(dechex($searchdata) == '73' && chr($searchdata) == 's') {
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', substr($tt,3));
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$search_data_content = $res['result_set'];
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($search_data_content))));
						
					} else {
						
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $tt);
						$return_param['link_expiry_date'][$pk][] = $res['expiry'];
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$excel_params = array($sp);
					}
				}
			} else {
			
				$excel_params = unserialize(gzinflate(base64_decode($pv)));
				$this->time_machine 	= $excel_params['time'];
				$return_param['ltype'][$pk]	= htmlentities($excel_params[$stack_type]);
			
				if($excel_params['params'] === NULL)
				{ 	
					$packedLeadingIDs = gzinflate(base64_decode($_POST['leading'][$pk]));
					$leadingIDs = unpack('l*', $packedLeadingIDs);
					if($packedLeadingIDs === false) $leadingIDs = array();
					
					$sp = new SearchParam();
					$sp->field = 'larvol_id';
					$sp->action = 'search';
					$sp->value = implode(' OR ', $leadingIDs);
					$excel_params = array($sp);

				} else {	
					$excel_params = $excel_params['params'];
				}
			}
			
			$params = array_merge($this->params, $excel_params, $ins_params);
			if(!empty($excel_params)) {
				$arrr = search($params,$this->fid,NULL,$this->time_machine);
			}else {
				$return_param['activearray'][] = array('section' => $pk);
				$return_param['inactivearray'][] = array('section' => $pk);
				$return_param['allarray'][] = array('section' => $pk);
			}
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($arrr as $k => $v) { 
				foreach($v as $kk => $vv) { 
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
						$arr[$indx][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					else
						$arr[$indx][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
				}
				++$indx;
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($arr as $key => $val) { 
				
				$nct = array();$trial_arr	= array();
				$allUpmDetails = array();
				
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->time_machine, $this->edited);
				$trial_arr[] = $val['NCT/nct_id'] . ', ' . $val['larvol_id'];
				 //checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->time_machine, $this->edited);
				
				if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
			
					if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
					{
						$return_param['fin_arr'][$pk][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
						$return_param['all_records'][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					}
					foreach($allUpmDetails[$val['NCT/nct_id']] as $kk => $vv) {
						if(isset($vv['edited']) && !empty($vv['edited'])) {
							$return_param['upmDetails'][$pk][$val['NCT/nct_id']][] = $vv;
						}
					}

				} else {
					$return_param['fin_arr'][$pk][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$return_param['all_records'][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$return_param['upmDetails'][$pk][$val['NCT/nct_id']] = $allUpmDetails[$val['NCT/nct_id']];
				}
				
				
				if(in_array($val['NCT/overall_status'],$this->inactfilterarr)) {
				
					$totinactivecount++;
					if(isset($_POST['wh']) || isset($_POST['afm']) || isset($_POST['tna']) || isset($_POST['nla']) 
					|| isset($_POST['wd']) || isset($_POST['t']) || isset($_POST['s']) || isset($_POST['c'])) {
							
						$vall = implode(",",array_keys($this->inactfilterarr, $val['NCT/overall_status']));
						if(array_key_exists($vall, $_POST)) {
						
							if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
							
								if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
									$return_param['inactivearray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
									++$showRecords_inactivearray_Cnt;
								}
							} else {
								$return_param['inactivearray'][] = array_merge($val, array('section' => $pk));
								++$showRecords_inactivearray_Cnt;
							}
							
						}
				} else {
					if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
						
							if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
								$return_param['inactivearray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
								++$showRecords_inactivearray_Cnt;
							}
						} else {
							$return_param['inactivearray'][] = array_merge($val, array('section' => $pk));
							++$showRecords_inactivearray_Cnt;
						}
				}
			} else /*if(in_array($val['NCT/overall_status'], $this->actfilterarr) )*/ {
				
					$totactivecount++;
					if(isset($_POST['nyr']) || isset($_POST['r']) || isset($_POST['ebi']) || isset($_POST['anr']) 
						|| isset($_POST['a']) || isset($_POST['nlr'])) {	
					
						$vall = implode(",",array_keys($this->actfilterarr, $val['NCT/overall_status']));
						if(array_key_exists($vall, $_POST)) {
						
							if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
							
								if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
									$return_param['activearray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
									++$showRecords_activearray_Cnt;
								}
							} else {
								$return_param['activearray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
								++$showRecords_activearray_Cnt;
							}
						} 
					} else {
					
						if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
						
							if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
								$return_param['activearray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
								++$showRecords_activearray_Cnt;
							}
						} else { 
							$return_param['activearray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
							++$showRecords_activearray_Cnt;
						}
					}
				}
							
							if(isset($_POST['nyr']) || isset($_POST['r']) || isset($_POST['ebi']) || isset($_POST['anr']) 
							|| isset($_POST['a']) || isset($_POST['wh']) || isset($_POST['afm']) || isset($_POST['tna']) 
							|| isset($_POST['nla']) || isset($_POST['wd']) || isset($_POST['t']) || isset($_POST['s']) 
							|| isset($_POST['c']) || isset($_POST['nlr'])) {	
							
							$vall = implode(",",array_keys($this->allfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_POST)) {
								if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
									if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
										$return_param['allarray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
										++$showRecords_allarray_Cnt;
									}
								} else {
									$return_param['allarray'][] = array_merge($val, array('section' => $pk));
									++$showRecords_allarray_Cnt;
								}
							} 
						} else {
							if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
								if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
									$return_param['allarray'][] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => $pk));
									++$showRecords_allarray_Cnt;
								}
							} else {
								$return_param['allarray'][] = array_merge($val, array('section' => $pk));
								++$showRecords_allarray_Cnt;
							}
						}
					}	
					
					$return_param['showRecordsCnt'] = (isset($_POST["list"])) ? (${'showRecords_'.$_POST["list"].'array_Cnt'}) : ($showRecords_activearray_Cnt);
					$return_param['stack_inactive_count'] 	= $return_param['stack_inactive_count'] + $totinactivecount;
					$return_param['stack_active_count']		= $return_param['stack_active_count'] + $totactivecount;
					$return_param['stack_total_count']		= $return_param['stack_total_count'] + ($totinactivecount + $totactivecount);
				
				} 
						
		
				/*--------------------------------------------------------
				|Variables set for count when filtered by institution_type
				---------------------------------------------------------*/
				if(isset($_POST['instparams']) && $_POST['instparams'] != '') {
					$return_param['insparams'] = $_POST['instparams'];
				} else {
				
					$return_param['insparams']  = rawurlencode(base64_encode(gzdeflate(serialize(array('actcnt' => $return_param['stack_active_count'],
														'inactcnt' => $return_param['stack_inactive_count'])))));
				}
				return $return_param;
			}
	
	function setSortParams() {
	
		$sortorder = array();
		if(!isset($_POST['sortorder'])) { 
			$this->sort_params = "ph-des##ed-asc##sd-asc##os-asc##en-asc##";
		} else {	
			$this->sort_params = $_POST['sortorder'];
		}
		$this->sortorder = array_filter(explode("##", $this->sort_params));
		
		foreach($this->sortorder as $k => $v)
			$this->sortimg[substr($v,0,2)] = substr($v,3);
		
		$fieldname = array('en' => 'enrollment', 'ph' => 'phase', 'os' =>'overall_status', 
				'sd' => 'start_date','ed' => 'completion_date');
							
		foreach($this->sortorder as $k => $v) {
				
			$typ = substr($v, (strpos($v, '-')+1));
			$v = substr($v, 0, strpos($v, '-'));
			$sp = new SearchParam();
			
			if($v == 'ed')
				$sp->field = 'inactive_date';
			else	
				$sp->field = '_' . getFieldId('NCT', $fieldname[$v]);
				
			$sp->action = ($typ == 'des') ? 'descending' : 'ascending';
			$this->params[] = $sp;
		}
		
	}
	
	function chkType() {
	
		
		global $now;
		$process_params = array();
		$process_params['link_expiry_date'] = array();
		global $unmatched_upm_details;
		$header_details = array();
		//Stacked Ott.	
		if(isset($_POST['cparams']) || (isset($_POST['results']) && isset($_POST['type']))) {
			
			//Process the get parameters and extract the information
			$process_params = $this->processParams();
		
			$index = 0;
			
			$first_ids[] = explode('.',$process_params['c_params'][0]);
			
			foreach($process_params['params_arr'] as $k => $v) { 
				global $row_upm_arr;
				$row_upm_arr = array();
				$header_details[$k] = trim($process_params['ltype'][$k]);
				$row_upm_flag = false;
						$vv = explode('.', $v);
						if($k != 0) {
						
							//result set separator as a separate parameter and maintaining backward compatibility
							if($vv[1] == '-1' || $vv[1] == '-2') {
								if(isset($vv[3])) {
								 						
								if(isset($_POST['results']) && $_POST['type'] == 'col') { 
								
									$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[3]);
									if(isset($_POST['v']) && $_POST['v'] == 1)
									{
										$val['intervention_name'] = explode('\n',$val['intervention_name']);
										$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
									}
									else
									{
										$val['intervention_name'] = explode(',',$val['intervention_name']);
										$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
									}
									$unmatched_upm_details[$k] = getNonAssocUpm($val['intervention_name'], $k,$this->time_machine,$this->edited);
								} else if(isset($_POST['results']) && $_POST['type'] == 'row') { 
									
									$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[3]); 
									if(isset($_POST['v']) && $_POST['v'] == 1)
									{
										$row_upm_arr = array_merge($row_upm_arr,explode('\n',$res['intervention_name']));
										$unmatched_upm_details[$k] = getNonAssocUpm(explode('\n',$res['intervention_name']), $k,$this->time_machine,$this->edited);
									}
									else
									{
										$row_upm_arr = array_merge($row_upm_arr,explode(',',$res['intervention_name']));
										$unmatched_upm_details[$k] = getNonAssocUpm(explode(',',$res['intervention_name']), $k,$this->time_machine,$this->edited);
									}
									}
								}
							} else {
								if(isset($vv[2])) { 
						
									if(isset($_POST['results']) && $_POST['type'] == 'col') { 
										
										$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[2]);
										if(isset($_POST['v']) && $_POST['v'] == 1)
										{
											$val['intervention_name'] = explode('\n',$val['intervention_name']);
											$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
										}
										else
										{
											$val['intervention_name'] = explode(',',$val['intervention_name']);
											$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
										}
										$unmatched_upm_details[$k] = getNonAssocUpm($val['intervention_name'], $k,$this->time_machine,$this->edited);
									} else if(isset($_POST['results']) && $_POST['type'] == 'row') { 
										
										$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[2]); 
										if(isset($_POST['v']) && $_POST['v'] == 1)
										{
											$row_upm_arr = array_merge($row_upm_arr,explode('\n',$res['intervention_name']));
											$unmatched_upm_details[$k] = getNonAssocUpm(explode('\n',$res['intervention_name']), $k,$this->time_machine,$this->edited);
										}
										else
										{
											$row_upm_arr = array_merge($row_upm_arr,explode(',',$res['intervention_name']));
											$unmatched_upm_details[$k] = getNonAssocUpm(explode(',',$res['intervention_name']), $k,$this->time_machine,$this->edited);
										}
									}
								}
							}
							
						} else {
						
							//result set separator as a separate parameter and maintaining backward compatibility
							if($vv[2] == '-1' || $vv[2] == '-2') {
							
								if(isset($vv[4])) {
								
									if(isset($_POST['results']) && $_POST['type'] == 'col') { 
									
										$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[4]);
										if(isset($_POST['v']) && $_POST['v'] == 1)
										{
											$val['intervention_name'] = explode('\n',$val['intervention_name']);
											$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
										}
										else
										{
											$val['intervention_name'] = explode(',',$val['intervention_name']);
											$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
										}
										$unmatched_upm_details[$k] = getNonAssocUpm($val['intervention_name'], $k,$this->time_machine,$this->edited);
									} else if(isset($_POST['results']) && $_POST['type'] == 'row') { 
										
										$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[4]); 
										if(isset($_POST['v']) && $_POST['v'] == 1)
											$row_upm_arr = array_merge($row_upm_arr,explode('\n',$res['intervention_name']));
										else
											$row_upm_arr = array_merge($row_upm_arr,explode(',',$res['intervention_name']));
									}
								}
							} else {
								if(isset($vv[3])) { 	
								
									if(isset($_POST['results']) && $_POST['type'] == 'col') { 
								
										$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[4]);
										if(isset($_POST['v']) && $_POST['v'] == 1)
										{
											$val['intervention_name'] = explode('\n',$val['intervention_name']);
											$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
										}
										else
										{
											$val['intervention_name'] = explode(',',$val['intervention_name']);
											$row_upm_arr = array_merge($row_upm_arr,$val['intervention_name']);
										}
										$unmatched_upm_details[$k] = getNonAssocUpm($val['intervention_name'], $k,$this->time_machine,$this->edited);
											
									} else if(isset($_POST['results']) && $_POST['type'] == 'row') { 
									
										$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[3]); 
										//$process_params['link_expiry_date'][$pk][] = $res['expiry'];
										if(isset($_POST['v']) && $_POST['v'] == 1)
											$row_upm_arr = array_merge($row_upm_arr,explode('\n',$res['intervention_name']));
										else
											$row_upm_arr = array_merge($row_upm_arr,explode(',',$res['intervention_name']));
									}
								}
							}
							
						}
						if(isset($_POST['cparams'])) { 
					
							if(isset($_POST['rowupm']) && $process_params['c_params']['type'] == 'row') {
							
								foreach($_POST['rowupm'] as $key => $value) {
									$val = unserialize(gzinflate(base64_decode($value)));
									if(isset($val) && $val != '' && !empty($val)) {
										foreach($val as $valu) { $row_upm_arr[$k] = $valu; }
									}
								}
							} else if($process_params['c_params']['type'] == 'col') {
								
								$val = unserialize(gzinflate(base64_decode($_POST['colupm'][$k])));
								if(isset($val) && $val != '' && !empty($val)) {
									$unmatched_upm_details[$k] = getNonAssocUpm($val, $k,$this->time_machine,$this->edited);
								}
							}
						}
					}
					
					
							
				$index++;
				
				if(!empty($process_params['link_expiry_date'][$pk])) {
						
					$process_params['link_expiry_date'][$pk] = array_unique(array_filter($process_params['link_expiry_date'][$pk]));
					usort($process_params['link_expiry_date'][$pk], "cmpdate");
						
					if(!empty($process_params['link_expiry_date'][$pk])) {
					
					if(($process_params['link_expiry_date'][$pk][0] < date('Y-m-d', $now)) || 
					($process_params['link_expiry_date'][$pk][0] < date('Y-m-d',strtotime('+1 week',$now)))) {
							
						$ids = array();	$searchdata = '';
						$ids = explode('.', $process_params['params_arr'][$pk]);
								
								if($pk != 0) {
									
									if($_POST['type'] == 'col') {
									
										$row_header_id = $ids[0];
										$col_header_id = $first_ids[1];
										
										if($ids[1] == '-1' || $ids[1] == '-2') {
											$trial_id = $ids[2];
//											$upm_id = ((isset($ids[3]) && !empty($ids[3])) ? $ids[3] : '');
										} else {
										
											$searchdata = substr($ids[1],0,3);
											if(dechex($searchdata) == '73' && chr($searchdata) == 's') 
												$trial_id = substr($ids[1],3);
											else
												$trial_id = $ids[1];
												
//											$upm_id = ((isset($ids[2]) && !empty($ids[2])) ? $ids[2] : '');
										}
										
									} elseif($_POST['type'] == 'row') {
									
										$row_header_id = $first_ids[0];
										$col_header_id = $ids[0];
										
										if($ids[1] == '-1' || $ids[1] == '-2') {
											$trial_id = $ids[2];
//											$upm_id = ((isset($ids[3]) && !empty($ids[3])) ? $ids[3] : '');
										} else {
											$searchdata = substr($ids[1],0,3);
											if(dechex($searchdata) == '73' && chr($searchdata) == 's') 
												$trial_id = substr($ids[1],3);
											else
												$trial_id = $ids[1];
												
//											$upm_id = ((isset($ids[2]) && !empty($ids[2])) ? $ids[2] : '');
										}
									}
								} else {
									$row_header_id = $ids[0];
									$col_header_id = $ids[1];
									
									if($ids[2] == '-1' || $ids[2] == '-2') {
										$trial_id = $ids[3];
//										$upm_id = ((isset($ids[4]) && !empty($ids[4])) ? $ids[4] : '');
									} else {
										$searchdata = substr($ids[2],0,3);
										if(dechex($searchdata) == '73' && chr($searchdata) == 's') 
											$trial_id = substr($ids[2],3);
										else
											$trial_id = $ids[2];
											
//										$upm_id = ((isset($ids[3]) && !empty($ids[3])) ? $ids[3] : '');
									}
								}
								
								$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
								. $row_header_id . "' ";
								$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for row header' . "\n" . $query);
						
								$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
								. $col_header_id . "' ";
								$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for column header' . "\n" . $query);
						
								$query = "UPDATE `rpt_ott_trials` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
								. $trial_id . "' ";
								$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for trials result set' . "\n" . $query);
/*						
								if(isset($upm_id) && $upm_id != '') {
									$query = "UPDATE `rpt_ott_upm` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
									. $upm_id . "' ";
									$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for upms' . "\n" . $query);
								}
*/
								
							}
						}
					}
			
			
			if(isset($_POST['trunc'])) {
				$t = unserialize(gzinflate(base64_decode($_POST['trunc'])));
			}
			$shownArr = array();$foundArr = array();
			if($process_params['showRecordsCnt'] > 0) {
			
				$current_type = $this->type;
				$shownArr = array();
				$foundArr = array();
				
				foreach($process_params[$current_type] as $key => $value) {
				
					foreach($value as $kkey => $vvalue){
					if( is_array($vvalue))
					{
					
							unset($vvalue['edited']);
							unset($vvalue['new']);
							unset($vvalue['larvol_id']);
	//						unset($vvalue['inactive_date']);
	//						unset($vvalue['region']);
						foreach($vvalue as $k => $v) {
							if(strpos($k, 'NCT/') !== FALSE) {
								$newkey = str_replace('NCT/','NCT.',$k);
								$vvalue[$newkey] = $v;
								unset($vvalue[$k]);
							}
						}
					}
						
						$shownArr[$process_params[$current_type][$key][$kkey]['NCT/nct_id']] = $vvalue;
						
					}
					
				}
				foreach($process_params['fin_arr'] as $key => $value) {
					foreach($value as $kkey => $vvalue){
						unset($vvalue['edited']);
						unset($vvalue['new']);
						unset($vvalue['larvol_id']);
//						unset($vvalue['inactive_date']);
//						unset($vvalue['region']);
						foreach($vvalue as $k => $v) {
							if(strpos($k, 'NCT/') !== FALSE) {
							
								$newkey = str_replace('NCT/','NCT.',$k);
								$vvalue[$newkey] = $v;
								unset($vvalue[$k]);
							}
						}
						$foundArr[$kkey] = $vvalue;
					}
					
				}
				

				foreach($shownArr as $key => &$value) {
					if( is_array($value))
					{
						unset($value['edited']);
						unset($value['new']);
						unset($value['larvol_id']);
	//					unset($value['inactive_date']);
	//					unset($value['region']);
						
						foreach($value as $k => $v) {
							if(strpos($k, 'NCT/') !== FALSE) {
							
								$newkey = str_replace('NCT/','NCT.',$k);
								$value[$newkey] = $v;
								unset($value[$k]);
							}
						}
				
					}
				}
				;
				
			}
			$ky=0;
			$link_expiry_date = array();
			foreach($process_params['link_expiry_date'] as $key => $value)
				foreach($value as $kkey => $vvalue)
					$link_expiry_date[] = $vvalue;

			//Expiry feature for new link method
			global $non_assoc_upm_params;	
			
			if(isset($_POST['results'])) 
			{
				$results_params 	= explode(".", $_POST['results']);
			

				if(isset($results_params[4])) {
						if(isset($_POST['v']) && $_POST['v'] == 1)
							$non_assoc_upm_params = explode('\n',$res['intervention_name']);
						else
							$non_assoc_upm_params = explode(',',$res['intervention_name']);
					}
				if(isset($results_params[3])) {
						if(isset($_POST['v']) && $_POST['v'] == 1)
							$non_assoc_upm_params = explode('\n',$res['intervention_name']);
						else
							$non_assoc_upm_params = explode(',',$res['intervention_name']);
					}
			}
			else $non_assoc_upm_params	= $excel_params['upm'];
//			return;
			global $unmatched_upm_details;		
//			$unmatched_upm_details[$ky] = getNonAssocUpm($non_assoc_upm_params, $ky,$this->time_machine,$this->edited);
//			$ky++;
			
			create_excel($process_params,$unmatched_upm_details,$this->time_machine,$this->edited);		
			
			
		} else {
			global $non_assoc_upm_params;
			$page = 1;
			if(isset($_POST['page'])) $page = mysql_real_escape_string($_POST['page']);
			if(!is_numeric($page)) die('non-numeric page');

			$totinactivecount = 0;
			$totactivecount = 0;
			
			$excel_params 	= array();
			$results_params = array();
			$ins_params 	= array();
			$fin_arr 		= array();
			$link_expiry_date	= array();
			$non_assoc_upm_params = array();
			$this->inactivearray = array();
			$this->activearray = array();
			$this->allarray = array();
			
			if(isset($_POST['results'])) {
				$excel_params 	= explode(".", $_POST['results']);
				$results_params 	= explode(".", $_POST['results']);
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $excel_params[0]);
				$rowlabel = $res['header'];
				$link_expiry_date[] = $res['expiry'];
				
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $excel_params[1]);
				$columnlabel = $res['header'];
				$link_expiry_date[] = $res['expiry'];
				
				if($results_params[2] == '-1' || $results_params[2] == '-2') { 
					if($results_params[2] == '-2') {
					
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', $results_params[3]);
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
						$link_expiry_date[] = $res['expiry'];
						
					} else if($results_params[2] == '-1') { 
						
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $results_params[3]);
						$link_expiry_date[] = $res['expiry'];
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$excel_params = array($sp);
					}
					if(isset($results_params[4])) {
					
						$link_expiry_date[]	  = $res['expiry'];
						$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $results_params[4]);
						if(isset($_POST['v']) && $_POST['v'] == 1)
							$non_assoc_upm_params = explode('\n',$res['intervention_name']);
						else
							$non_assoc_upm_params = explode(',',$res['intervention_name']);
						global $unmatched_upm_details;
						$unmatched_upm_details[$key] = getNonAssocUpm($non_assoc_upm_params, $key,$this->time_machine,$this->edited);
					}
				} else {
					if(strpos($results_params[2],'s') !== FALSE) {
					
						$res = getLinkDetails('rpt_ott_searchdata', 'result_set', 'id', substr($results_params[2],1));
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($res['result_set']))));
						$link_expiry_date[] = $res['expiry'];
						
					} else {
					
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $results_params[2]);
						$link_expiry_date[] = $res['expiry'];
						$sp = new SearchParam();
						$sp->field = 'larvol_id';
						$sp->action = 'search';
						$sp->value = str_replace(',', ' OR ', $res['result_set']);
						$excel_params = array($sp);
					}
					if(isset($results_params[3])) {
					
						$link_expiry_date[]	  = $res['expiry'];
						$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $results_params[3]);
						if(isset($_POST['v']) && $_POST['v'] == 1)
							$non_assoc_upm_params = explode('\n',$res['intervention_name']);
						else
							$non_assoc_upm_params = explode(',',$res['intervention_name']);
							
						global $unmatched_upm_details;
						$unmatched_upm_details[$key] = getNonAssocUpm($non_assoc_upm_params, $key,$this->time_machine,$this->edited);

					}
				}
				$bomb = (isset($_POST['bomb'])) ? $_POST['bomb'] : '';
				$this->time_machine = $_POST['time'];
				
			} else {
				$excel_params 	= unserialize(gzinflate(base64_decode($_POST['params'])));
				$rowlabel 		= $excel_params['rowlabel'];
				$columnlabel 	= $excel_params['columnlabel'];
				$bomb			= $excel_params['bomb'];  //added for bomb indication
				$this->time_machine = $excel_params['time'];
				$non_assoc_upm_params	= $excel_params['upm'];
				if($excel_params['params'] === NULL)
				{ 	
					$packedLeadingIDs = gzinflate(base64_decode($_POST['leading']));
					$leadingIDs = unpack('l*', $packedLeadingIDs);
					if($packedLeadingIDs === false) $leadingIDs = array();
					
					$sp = new SearchParam();
					$sp->field = 'larvol_id';
					$sp->action = 'search';
					$sp->value = implode(' OR ', $leadingIDs);
					$excel_params = array($sp);
					
				} else {
					$excel_params = $excel_params['params'];
				}
				if($excel_params === false)
				{
					$results = count($leadingIDs);
				}
			}
			if(isset($_POST['institution']) && $_POST['institution'] != '') {
				array_push($this->fid, 'institution_type');
				$sp = new SearchParam();
				$sp->field 	= 'institution_type';
				$sp->action = 'search';
				$sp->value 	= $_POST['institution'];
				$ins_params = array($sp);
			}
			$params = array_merge($this->params, $excel_params, $ins_params);
			$arr = array();
			$nct = array();
			$trial_arr 		= array();
			$allUpmDetails	= array();
			$upmDetails	 	= array();
			$arrr = search($params,$this->fid,NULL,$this->time_machine);
			foreach($arrr as $k => $v) {
				foreach($v as $kk => $vv) {
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && 'NCT/lead_sponsor')
						$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;//$arr[$k][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					else
						$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;//$arr[$k][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
				}
			}
			
			foreach($arr as $key => $val) { 
			
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->time_machine, $this->edited);
				
				if (!is_array($nct[$val['NCT/nct_id']])) { 
					$nct=array();
					$val['NCT/intervention_name'] = '(study not in database)';
				}
				$trial_arr[] = $val['NCT/nct_id'] . ', ' . $val['larvol_id']; 
				
				//checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->time_machine, $this->edited);
				if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
				
					if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
						$fin_arr[$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
						
					foreach($allUpmDetails[$val['NCT/nct_id']] as $kk => $vv) {
						if(isset($vv['edited']) && !empty($vv['edited'])) {
							$upmDetails[$val['NCT/nct_id']][] = $vv;
						}
					}
				} else {
					$fin_arr[$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$upmDetails[$val['NCT/nct_id']] = $allUpmDetails[$val['NCT/nct_id']];
				}
				
				if(in_array($val['NCT/overall_status'],$this->inactfilterarr)) {
					
					$totinactivecount++;
					if(isset($_POST['wh']) || isset($_POST['afm']) || isset($_POST['tna']) || isset($_POST['nla']) 
						|| isset($_POST['wd']) || isset($_POST['t']) || isset($_POST['s']) || isset($_POST['c'])) {
							
						$vall = implode(",",array_keys($this->inactfilterarr, $val['NCT/overall_status']));
						if(array_key_exists($vall, $_POST)) {
						
							if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
								if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
									$this->inactivearray[] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => '0'));
							} else {
								$this->inactivearray[] = array_merge($val, array('section' => '0'));
							}
						} 
					} else {
						if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
							if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
								$this->inactivearray[] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => '0'));
						} else {
							$this->inactivearray[] = array_merge($val, array('section' => '0'));
						}
					}
				} else {
					$totactivecount++;
					if(isset($_POST['nyr']) || isset($_POST['r']) || isset($_POST['ebi']) || isset($_POST['anr']) 
						|| isset($_POST['a']) || isset($_POST['nlr'])) {	
					
						$vall = implode(",",array_keys($this->actfilterarr, $val['NCT/overall_status']));
						if(array_key_exists($vall, $_POST)) {
							if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
								if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
									$this->activearray[] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => '0'));
							} else {
								$this->activearray[] = array_merge($val, array('section' => '0'));
							}
						} 
					} else {
						if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
							if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
								$this->activearray[] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => '0'));
						} else {
							$this->activearray[] = array_merge($val, array('section' => '0'));
						}
					}
				
				}

			}
			
			if(isset($_POST['instparams']) && $_POST['instparams'] != '') {
			
				$insparams = $_POST['instparams'];
			
			} else {
			
				$insparams = rawurlencode(base64_encode(gzdeflate(serialize(array('actcnt' => $totactivecount,'inactcnt' => $totinactivecount)))));
			}
			
			foreach($fin_arr as $key => $new_arr) {
				if($this->inactflag == 1) { 
					
					if(in_array($new_arr['NCT/overall_status'], $this->inactfilterarr)) {
						
						if(isset($_POST['wh']) || isset($_POST['afm']) || isset($_POST['tna']) || isset($_POST['nla']) 
						|| isset($_POST['wd']) || isset($_POST['t']) || isset($_POST['s']) || isset($_POST['c'])) {
							
							$vall = implode(",",array_keys($this->inactfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_POST)) {
								$this->inactivearray[] = $new_arr;
								$this->inactivecount++;		
							} 
						} else {
								$this->inactivearray[] = $new_arr;
								$this->inactivecount++;	
						}
					}
				
				} else if($this->allflag == 1) {
					 
					if(in_array($new_arr['NCT/overall_status'], $this->allfilterarr)) {
						
						if(isset($_POST['nyr']) || isset($_POST['r']) || isset($_POST['ebi']) || isset($_POST['anr']) 
						|| isset($_POST['a']) || isset($_POST['wh']) || isset($_POST['afm']) || isset($_POST['tna']) 
						|| isset($_POST['nla']) || isset($_POST['wd']) || isset($_POST['t']) || isset($_POST['s']) 
						|| isset($_POST['c']) || isset($_POST['nlr']) ) {	
						
						$vall = implode(",",array_keys($this->allfilterarr, $new_arr['NCT/overall_status']));
						if(array_key_exists($vall, $_POST)) {
						
							$this->allarray[] = $new_arr;
							$this->allcount++;	
						} 
					} else {
					
						$this->allarray[] = $new_arr;	
						$this->allcount++;
					}
				}	
			
				} else {
			
					if(in_array($new_arr['NCT/overall_status'], $this->actfilterarr) ) {
						if(isset($_POST['nyr']) || isset($_POST['r']) || isset($_POST['ebi']) || isset($_POST['anr']) 
						|| isset($_POST['a']) || isset($_POST['nlr'])) {
						
							$vall = implode(",",array_keys($this->actfilterarr, $new_arr['NCT/overall_status']));
							if(array_key_exists($vall, $_POST)) { 
							
								$this->activearray[] = $new_arr;
								$this->activecount++;	
							} 
						} else {
						
							$this->activearray[] = $new_arr;	
							$this->activecount++;
						}	
					}

				}
			}
			
			$count = count($this->{$this->type});
			
			if(isset($_POST['institution']) && $_POST['institution'] != '') {
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($insparams))));
				$foundcount = ($ins['actcnt'] + $ins['inactcnt']);
				$this->commonControls($count, $ins['actcnt'], $ins['inactcnt'], ($ins['actcnt'] + $ins['inactcnt']));
			} else {
				$foundcount = ($totactivecount + $totinactivecount);
				$this->commonControls($count, $totactivecount, $totinactivecount, ($totactivecount + $totinactivecount));
			}

			
			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;

			}
			if($count > 0) {
			
				
			} 
			$shownArr = array();
			if($count > 0) {
			
				$shownArr = $this->{$this->type};
				foreach($fin_arr as $key => &$value) {
				
					unset($value['edited']);
					unset($value['new']);
					unset($value['larvol_id']);
//					unset($value['inactive_date']);
//					unset($value['region']);
					
					foreach($value as $k => $v) {
						if(strpos($k, 'NCT/') !== FALSE) {
						
							$newkey = str_replace('NCT/','NCT.',$k);
							$value[$newkey] = $v;
							unset($value[$k]);
						}
					}
				}
				foreach($shownArr as $key => &$value) {
				
					unset($value['edited']);
					unset($value['new']);
					unset($value['larvol_id']);
//					unset($value['inactive_date']);
//					unset($value['region']);
					
					foreach($value as $k => $v) {
						if(strpos($k, 'NCT/') !== FALSE) {
						
							$newkey = str_replace('NCT/','NCT.',$k);
							$value[$newkey] = $v;
							unset($value[$k]);
						}
					}
				}
				global $unmatched_upm_details;	
				create_excel($shownArr,$unmatched_upm_details,$this->time_machine,$this->edited);
				
			}
			
			//Expiry feature for new link method
			if(!empty($link_expiry_date)) {
				$link_expiry_date = array_unique(array_filter($link_expiry_date));
				usort($link_expiry_date, "cmpdate");
				if(!empty($link_expiry_date)) {
				

					
					$ids = explode(".", $_POST['results']);
					if(($link_expiry_date[0] < date('Y-m-d', $now)) || ($link_expiry_date[0] < date('Y-m-d',strtotime('+1 week',$now)))) {
					
						$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[0] . "' ";
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for row header' . "\n" . $query);
						
						$query = "UPDATE `rpt_ott_header` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[1] . "' ";
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for col header' . "\n" . $query);
						
						if(strpos($ids[2],'s') !== FALSE) {
							$query = "UPDATE `rpt_ott_searchdata` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
							. $ids[2] . "' ";
						} else {
							$query = "UPDATE `rpt_ott_trials` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" 
							. $ids[2] . "' ";
						}
						$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for trials result set' . "\n" . $query);
						
						if(isset($ids[3]) && $ids[3] != '') {
/*
						$query = "UPDATE `rpt_ott_upm` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[3] . "' ";
							$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for upms' . "\n" . $query);
*/
						}
						
					}
				}
			}
		}
	}
	
	function getCorrespondingUPM($trial_id, $time, $edited) {
	
	$upm = array();
	$values = array();
					
	$result = mysql_query("SELECT id, event_type, product, corresponding_trial, id, event_description, event_link, result_link, start_date, end_date 
					FROM upm WHERE corresponding_trial = '" . $trial_id . "' ");
	
	$i = 0;			
	while($row = mysql_fetch_assoc($result)) {
	
		$upm[$i] = array($row['event_description'], $row['corresponding_trial'], $row['id'], $row['product'], $row['event_link'], $row['start_date'], $row['end_date'], $row['result_link'],$row['event_type'],);
		
		//Query for checking updates for upms.
		$sql = "SELECT `id`, `event_type`, `product`, `corresponding_trial`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $row['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$upm[$i]['edited'] = array();
		while($arr = mysql_fetch_assoc($res)) {
			$upm[$i]['edited'] = array($arr['event_type'], $arr['event_description'], $arr['event_link'], $arr['result_link'], 
									$arr['start_date'], $arr['start_date_type'], $arr['end_date'], $arr['end_date_type'],);
		}
		$i++;
	}
	return $upm;
}
	
	
	

	function getNonAssocUpm($non_assoc_upm_params, $trialheader,$tm,$ed) {
		
		global $now;

		$upm_arr = array();$record_arr = array();$unmatched_upm_arr = array();
		
		$upm_arr = getNonAssocUpmRecords($non_assoc_upm_params);
		$record_arr = getUnmatchedUpmChanges($upm_arr, $tm, $ed);
		
		foreach($record_arr as $key => $val) {
			
			if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
			
			 	if(!empty($val['edited']) && $val['new'] == 'n') {
					if( ($val['event_description'] == $val['edited']['event_description']) && ($val['event_link'] == $val['edited']['event_link']) && 
					($val['event_type'] == $val['edited']['event_type']) && ($val['start_date'] == $val['edited']['start_date']) && 
					($val['start_date_type'] == $val['edited']['start_date_type']) && ($val['end_date'] == $val['edited']['end_date']) && 
					($val['end_date_type'] == $val['edited']['end_date_type']) ){ 
						unset($record_arr[$key]);
					} 
				} else if(empty($val['edited']) && $val['new'] == 'n') {
					unset($record_arr[$key]);
				}
			} 		
		}
		
		if(!empty($record_arr)) {
		
			$cntr = 0;
			$i=0;
			
			
			if( isset($upm_string) and is_array($upm_string) )
					echo "";
				else
					$upm_string=array();
					
			foreach($record_arr as $key => $val) {
				
				$title = '';$attr = '';$result_image = '';
				$class = 'class = "upms ' . $trialheader . '" ';
				$title_link_color = 'color:#000;';
				$date_style = 'color:gray;';
				$upm_title = 'title="' . $val['event_description'] . '"';
				
				if($cntr%2 == 1) {
		
					$row_type_one = 'alttitle';
					$row_type_two = 'altrow';
					
				} else {
				
					$row_type_one = 'title';
					$row_type_two = 'row';
				}	
				
				//Highlighting the whole row in case of new trials
				if($val['new'] == 'y') {
					$class = 'class="upms newtrial ' . $trialheader . '" ';
				}
				
		
						
				if(!empty($val['edited']) && $val['edited']['event_description'] != $val['event_description']) {
				
					$title_link_color = 'color:#FF0000;';$attr = ' highlight'; 
					if($val['edited']['event_description'] != '' || $val['edited']['event_description'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['event_description'] . '" '; 
					else
						$title = ' title="No Previous value" ';
						
				} else if($val['new'] == 'y') {
					$title_link_color = 'color:#FF0000;';
					$title = ' title = "New record" ';
				}
				if( isset($upm_string) and is_array($upm_string) )
					echo "";
				else
					$upm_string=array();
					
				if(!is_null($val['product']) and !empty($val['product'])) {
					$upm_string[$i]['product'] =  $val['product'] ;
				} else {
					$upm_string[$i]['product'] =  "";
				}
				
				if(!is_null($val['corresponding_trial']) and !empty($val['corresponding_trial'])) {
					$upm_string[$i]['corresponding_trial'] =  $val['corresponding_trial'] ;
				} else {
					$upm_string[$i]['corresponding_trial'] =  "";
				}
				
				$upm_string[$i]['id'] =  $val['id'] ;
				
				if($val['event_link'] != NULL && $val['event_link'] != '') {
					$upm_string[$i]['event_link'] =  $val['event_link'] ;
					$upm_string[$i]['event_description'] =  $val['event_description'] ;
				} else {
					$upm_string[$i]['event_description'] =  $val['event_description'] ;
				}
				
					
				$upm_string[$i]['title'] =  $title ;
				if($val['event_link'] != NULL && $val['event_link'] != '') {
					$upm_string[$i]['event_link'] =  $val['event_link'] ;
					$upm_string[$i]['event_description'] =  $val['event_description'] ;
				} else {
					$upm_string[$i]['event_description'] =  $val['event_description'] ;
				}
				
				if($val['result_link'] != NULL && $val['result_link'] != '') {
					$upm_string[$i]['result_link'] = $val['result_link'];
					$upm_string[$i]['status'] = 'Occurred';
				} else {
					$upm_string[$i]['result_link'] = "";
				
					if($val['end_date'] == NULL || $val['end_date'] == '' || $val['end_date'] == '0000-00-00') {
						$upm_string[$i]['status'] =  'Cancelled';
					} else if($val['end_date'] < date('Y-m-d', $now)) {
						$upm_string[$i]['status'] =  'Pending';
					} else if($val['end_date'] > date('Y-m-d', $now)) {
						$upm_string[$i]['status'] =  'Upcoming';
					}
					
				}
				
				if(!empty($val['edited']) && $val['edited']['event_type'] != $val['event_type']) {
			
					if($val['edited']['event_type'] != '' && $val['edited']['event_type'] != NULL)
						$upm_string[$i]['old_value'] =   $val['edited']['event_type'] ; 
						
				} 
				
				$upm_string[$i]['condition'] =   $val['event_type'] . ' Milestone';
				
				if(!empty($val['edited']) && $val['edited']['start_date'] != $val['start_date']){
					if($val['edited']['start_date'] != '' && $val['edited']['start_date'] != NULL)
						$upm_string[$i]['old_start_date'] = $val['edited']['start_date']; 
						
				} 
				if(!empty($val['edited']) && $val['edited']['start_date_type'] != $val['start_date_type']){
					if($val['edited']['start_date_type'] != '' && $val['edited']['start_date_type'] != NULL) {
						$upm_string[$i]['old_start_date_type'] = $val['edited']['start_date_type']; 
					} 
				} 
								
				if($val['start_date_type'] == 'anticipated') {
				$upm_string[$i]['start_date'] =  (($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' )  ;
				} else {
					$upm_string[$i]['start_date'] =  (($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' );
				}
				
				if(!empty($val['edited']) && $val['edited']['end_date'] != $val['end_date']){
				
					if($val['edited']['end_date'] != '' && $val['edited']['end_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['end_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				if(!empty($val['edited']) && $val['edited']['end_date_type'] != $val['end_date_type']){
				
					
					if($val['edited']['end_date_type'] != '' && $val['edited']['end_date_type'] != NULL) {
						$title = ' title="Previous value: ' . 
						(($val['edited']['end_date'] != $val['end_date']) ? $val['edited']['end_date'] : '' ) 
						. ' ' . $val['edited']['end_date_type'] . '" '; 
					} else {
						$title = ' title="No Previous value" ';
					}
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				
			
				if($val['end_date_type'] == 'anticipated') {
					
					$upm_string[$i]['end_date'] =  (($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '' ) ;
				} else {
					
					$upm_string[$i]['end_date'] =  (($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '');
				}	
				
				
				if(!empty($val['edited']) && ($val['result_link'] != $val['edited']['result_link'])) {
					if($val['result_link'] != '' && $val['result_link'] != NULL) {
						$result_image = (($val['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );

					}
				} else {
					if($val['result_link'] != '' && $val['result_link'] != NULL) {
						$result_image = (($val['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
						
					}
				}
				
				if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') && 
				($val['end_date'] < date('Y-m-d', $now)) && ($val['result_link'] == NULL || $val['result_link'] == '')){
						$upm_string[$i]['upm_title'] =  $upm_title ;
				}
/*				
				$upm_string .= getUPMChart(date('m',strtotime($val['start_date'])), date('Y',strtotime($val['start_date'])), 
				date('m',strtotime($val['end_date'])), date('Y',strtotime($val['end_date'])), $this->current_yr, $this->second_yr, $this->third_yr, 
				$val['start_date'], $val['end_date'], $val['event_link'], $upm_title);
*/			
		
				$i++;
				
				$cntr++;
			}
		} 
		
		return $upm_string;
	}
	
function getNonAssocUpmRecords($non_assoc_upm_params) {
	
	$where = '';$upms = array();$i = 0;
	foreach($non_assoc_upm_params as $key => $val){
		$where .= textEqual('`search_name`',$val) . ' OR ';
	}
	
	$result = mysql_query("SELECT `id` FROM `products` WHERE ( " . substr($where,0,-4) . " ) ");
	if(mysql_num_rows($result) > 0) {
	
		while($rows = mysql_fetch_assoc($result)) {
	
		$sql = "SELECT `id`, `corresponding_trial`, `product`,`event_description`, `event_link`, `result_link`, `event_type`, `start_date`, `start_date_type`, `end_date`, `end_date_type`  FROM `upm` WHERE `corresponding_trial` IS NULL AND `product` = '" . $rows['id'] 
				. "' ORDER BY `end_date` ASC ";
				
		$res = mysql_query($sql)  or tex('Bad SQL query getting unmatched upms ' . $sql);
		
		if(mysql_num_rows($res) > 0) {
		
			while($row = mysql_fetch_assoc($res)) { 
			
				$upms[$i]['id'] = $row['id'];
			$upms[$i]['corresponding_trial'] = $row['corresponding_trial'];
			$upms[$i]['product'] = $row['product'];
			$upms[$i]['event_description'] = htmlspecialchars($row['event_description']);
			$upms[$i]['event_link'] = $row['event_link'];
			$upms[$i]['result_link'] = $row['result_link'];
			$upms[$i]['event_type'] = $row['event_type'];
			$upms[$i]['start_date'] = $row['start_date'];
			$upms[$i]['start_date_type'] = $row['start_date_type'];
			$upms[$i]['end_date'] 	= $row['end_date'];
			$upms[$i]['end_date_type'] = $row['end_date_type'];
				
				$i++;
			}
		}
		
	}
	}
	
	return $upms;
}
	
//return NCT fields given an NCTID
function getNCT($nct_id,$larvol_id,$time,$edited)
{	
						
	$study = array('edited' => array(), 'new' => 'n');
	
	$fieldnames = array('nct_id', 'brief_title', 'enrollment', 'enrollment_type', 'acronym', 'start_date', 'overall_status',
	'condition', 'intervention_name', 'phase', 'lead_sponsor', 'collaborator');

	$studycatData = mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON "
	. "(`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='" . $nct_id . "' AND `dc`.`larvol_id`='" .$larvol_id . "'"));

	$res = mysql_query("SELECT DISTINCT `df`.`name` AS `fieldname`, `df`.`id` AS `fieldid`, `df`.`type` AS `fieldtype`, `dv`.`studycat` "
		. "FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('" 
		. join("','",$fieldnames) . "') AND `studycat` = '" . $studycatData['studycat'] 
		. "' AND (`dv`.`superceded`<'" . date('Y-m-d',$time) . "' AND `dv`.`superceded`>= '" . date('Y-m-d',strtotime($edited,$time)) . "') ");

	while ($row = mysql_fetch_assoc($res)) {
	 	
		$study['edited'][] = 'NCT/'.$row['fieldname'];
		
		//getting previous value for updated trials
		$val = '';
		$result = mysql_fetch_assoc(mysql_query("SELECT `" . 'val_'.$row['fieldtype'] ."` AS value FROM `data_values` WHERE `studycat` = '" 
		. $studycatData['studycat'] . "' AND `field` =  '" . $row['fieldid'] . "' AND (`superceded`<'" . date('Y-m-d',$time) 
		. "' AND `superceded`>= '" . date('Y-m-d',strtotime($edited,$time)) . "') "));
		
		$val = $result['value'];
		
		//special case for enum fields
		if($row['fieldtype'] == 'enum') {
			$result = mysql_fetch_assoc(mysql_query("SELECT `value` FROM `data_enumvals` WHERE `field` = '" . $row['fieldid'] . "' AND `id` = '" . $val . "' "));
			$val 	= $result['value'];
		}
			
		if(isset($val) && $val != '')
			$study['edited']['NCT/'.$row['fieldname']] = 'Previous value: ' . $val;
		else 
			$study['edited']['NCT/'.$row['fieldname']] = 'No previous value';
	}
	
	$sql = "SELECT `clinical_study`.`larvol_id` FROM `clinical_study` WHERE `clinical_study`.`import_time` <= '" 
		. date('Y-m-d',$time) . "' AND `clinical_study`.`larvol_id` = '" .  $larvol_id
		. "' AND `clinical_study`.`import_time` >= '" . date('Y-m-d',strtotime($edited,$time)) . "' ";
		
	$result = mysql_query($sql);		

	if(mysql_num_rows($result) >= 1) {
		$study['new'] = 'y';
	} 
	
	return $study;
}


	

function getUnmatchedUpmChanges($record_arr, $time, $edited) {

	foreach($record_arr as $key => $value) {
	
		$sql = "SELECT `id`, `event_type`, `event_description`, `product`, `corresponding_trial`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $value['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$record_arr[$key]['edited'] = array();
		$record_arr[$key]['new'] = 'n';
		
		if(mysql_num_rows($res) > 0) {
			while($row = mysql_fetch_assoc($res)) {
			
				$record_arr[$key]['edited']['id'] = $row['id'];
				$record_arr[$key]['edited']['corresponding_trial'] = $row['corresponding_trial'];
				$record_arr[$key]['edited']['product'] = $row['product'];
				$record_arr[$key]['edited']['event_description'] = htmlspecialchars($row['event_description']);
				$record_arr[$key]['edited']['event_link'] = $row['event_link'];
				$record_arr[$key]['edited']['result_link'] = $row['result_link'];
				$record_arr[$key]['edited']['event_type'] = $row['event_type'];
				$record_arr[$key]['edited']['start_date'] = $row['start_date'];
				$record_arr[$key]['edited']['start_date_type'] = $row['start_date_type'];
				$record_arr[$key]['edited']['end_date'] 	= $row['end_date'];
				$record_arr[$key]['edited']['end_date_type'] = $row['end_date_type'];
				
			}
		}
		
		$sql = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $value['id'] . "' AND u.`last_update` < '" 
				. date('Y-m-d',$time) . "' AND u.`last_update` >=  '" . date('Y-m-d',strtotime($edited,$time)) . "' AND uh.`id` IS NULL ";
		
		$result = mysql_query($sql);
		if(mysql_num_rows($result) != 0)
			$record_arr[$key]['new'] = 'y';
	}
	
	return $record_arr;
}

function getLinkDetails($tablename, $fieldname, $parameters, $param_value) {

	$query = "SELECT `" . $fieldname . "`, `expiry` FROM " . $tablename . " WHERE " . $parameters . " = '" . mysql_real_escape_string($param_value) . "' ";
	$res = mysql_fetch_assoc(mysql_query($query));
	
	return $res;
}
/********************export begins/*/
function create_excel($process_params,$upm_string,$tm,$ed)
{
ob_start();
//$upm_string=array_unique($upm_string);
if(isset($upm_string) and is_array($upm_string)) 
{
//$upm_string=array_unique($upm_string);
}

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

$styleThinBlueBorderOutline = array(
	'borders' => array(
		'inside' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF0000FF'),
		),
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF0000FF'),
		),
	),
);

$objPHPExcel->getActiveSheet()->getStyle('A1:K1')->applyFromArray($styleThinBlueBorderOutline);
			

$objPHPExcel->getProperties()->setCreator("The Larvol Group")
										 ->setLastModifiedBy("TLG")
										 ->setTitle("Larvol Trials")
										 ->setSubject("Larvol Trials")
										 ->setDescription("Excel file generated by Larvol Trials")
										 ->setKeywords("Larvol Trials")
										 ->setCategory("Clinical Trials");
										 



$bgcol="D5D3E6";

$stacked=false;
if(isset($_POST['list']) and $_POST['list'] == 'all' )
	{
	$stacked=true;
	$excelarray=$process_params['allarray'];
	unset($process_params['allarray']);
	}
elseif(isset($process_params['activearray']))
	{
//	echo '<pre>activearray </pre>';
//	return;
	$stacked=true;
	$excelarray=$process_params['activearray'];
	unset($process_params['activearray']);
	}
elseif(isset($process_params['inactivearray']))
	{
	$stacked=true;
	$excelarray=$process_params['inactivearray'];
	unset($process_params['inactivearray']);
	}
elseif(isset($process_params['allarray']))
	{
	$stacked=true;
	$excelarray=$process_params['allarray'];
	unset($process_params['allarray']);
	}
else
	{
	$stacked=false;
	$excelarray=$process_params;
	unset($process_params);
	}

//echo '<pre>excelarray'; print_r( $excelarray); echo '</pre>';
//return;
$i=2;
$ii=0;

$newupmarray=array();
foreach ($upm_string as $ke=>$valu)
{
	if(isset($valu) and is_array($valu)) 
	{
	
	foreach ($valu as $key => $value)
	$newupmarray[$value['id']][$key]=$value;
	}
}


///Part added to set start date as only one if there are multiple dates exists in start date field
$start_setter=count($excelarray);
if($start_setter>0)
{
	for ($dump=0; $dump<$start_setter; $dump++)
	{
	if($excelarray[$dump]['NCT/start_date'] != '' && $excelarray[$dump]['NCT/start_date'] != NULL && $excelarray[$dump]['NCT/start_date'] != '0000-00-00')
	$excelarray[$dump]['NCT/start_date']=substr($excelarray[$dump]['NCT/start_date'],0,10);
	if($excelarray[$dump]['NCT.start_date'] != '' && $excelarray[$dump]['NCT.start_date'] != NULL && $excelarray[$dump]['NCT.start_date'] != '0000-00-00')
	$excelarray[$dump]['NCT.start_date']=substr($excelarray[$dump]['NCT.start_date'],0,10);
	}
}				
///part end -date setter


if($stacked)
{
	foreach ($excelarray as $key=>$value)
	{
	
	if($value['section'] > $ii)
		$ii=$value['section'];
		
		if( isset($process_params['ltype'][$ii]) and isset($value['section']) and $value['section']==$ii and isset($value["NCT/brief_title"]) )  
		{
			$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $process_params['ltype'][$ii]);
			$objPHPExcel->getActiveSheet()->mergeCells('A' . $i . ':K'. $i);
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':K'. $i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => 'A2FF97'
							),
							'endcolor'   => array
							(
								'rgb' => 'A2FF97'
							)
						)
					)
				);
				
				
			$i++;
			$ii++;
		}	
		//$value=$val1;
		
			if( (isset($value['section']) or $_POST['list']== 'all') and ( isset($value["NCT/brief_title"]) and !empty($value["NCT/brief_title"]) ) )
			{
				
			$objPHPExcel->getActiveSheet()->getStyle('"A' . $i . ':K' . $i.'"')->applyFromArray($styleThinBlueBorderOutline);
			$objPHPExcel->getActiveSheet()->getStyle('A1:K1')->applyFromArray($styleThinBlueBorderOutline);
			if($value["NCT/nct_id"]>0)
			$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'NCT' . sprintf("%08s",$value["NCT/nct_id"]));
			
 			$value["NCT/brief_title"]=fix_special_chars($value["NCT/brief_title"]);
			$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value["NCT/brief_title"]);
			
			$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setUrl('http://clinicaltrials.gov/ct2/show/'. $objPHPExcel->getActiveSheet()->getCell('A' . $i)->getValue() );
			$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov');
			$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			if(isset($value["NCT/enrollment"])) 				$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value["NCT/enrollment"]);
			
			$value["region"]=fix_special_chars($value["region"]);
			$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value["region"]);
			
			if(isset($value["NCT/overall_status"])) 			$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value["NCT/overall_status"]);
			
			
			if(isset($value["NCT/lead_sponsor"])) 				
			{
			$value["NCT/lead_sponsor"]=fix_special_chars($value["NCT/lead_sponsor"]);
			$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $value["NCT/lead_sponsor"]);
			}
			
			if(isset($value["NCT/condition"])) 					
			{
			$value["NCT/condition"]=fix_special_chars($value["NCT/condition"]);
			$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value["NCT/condition"]);
			}
			
			if(isset($value["NCT/intervention_name"])) 			
			{
			$value["NCT/intervention_name"]=fix_special_chars($value["NCT/intervention_name"]);
			$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $value["NCT/intervention_name"]);
			}
			
			if(isset($value["NCT/start_date"])) 				$objPHPExcel->getActiveSheet()->setCellValue('I' . $i, date('m/y',strtotime($value["NCT/start_date"])));
			//if(isset($value["NCT/primary_completion_date"])) 	$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $value["NCT/primary_completion_date"]);
			if(isset($value["inactive_date"])) 	$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, date('m/y',strtotime($value["inactive_date"])));
			if(isset($value["NCT/phase"])) 						$objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $value["NCT/phase"]);
			if($bgcol=="D5D3E6") $bgcol="EDEAFF"; 	else $bgcol="D5D3E6";
			
			$objPHPExcel->getActiveSheet()->getStyle('A' . $i .':J' .$i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => $bgcol
							),
							'endcolor'   => array
							(
								'rgb' => $bgcol
							)
						)
					)
				);
				
			
			$objPHPExcel->getActiveSheet()->getStyle('A1:K1')->applyFromArray
			(
				array
				(
					'font'    => array
					(
						'bold'      => true
					),
					'alignment' => array
					(
						'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					),
					'borders' => array
					(
						'top'     => array
						(
							'style' => PHPExcel_Style_Border::BORDER_THIN
						)
					),
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
						'rotation'   => 90,
						'startcolor' => array
						(
							'argb' => 'FFA0A0A0'
						),
						'endcolor'   => array
						(
							'argb' => 'FFFFFFFF'
						)
					)
				)
			);

			/*
			if(
				isset($value["NCT/overall_status"]) and 
				(
					$value["NCT/overall_status"]=="Completed" or 
					$value["NCT/overall_status"]=="Approved for marketing" or 
					$value["NCT/overall_status"]=="Temporarily not available" or 
					$value["NCT/overall_status"]=="No Longer Available" or 
					$value["NCT/overall_status"]=="Withdrawn" or 
					$value["NCT/overall_status"]=="Terminated" or 
					$value["NCT/overall_status"]=="Suspended" or 
					$value["NCT/overall_status"]=="Completed"
				)
			)	$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':K' . $i)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
			*/
			
			if(isset($value["NCT/phase"]) and $value["NCT/phase"]=="Phase 0")
			{
			
				$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => '00CCFF'
							),
							'endcolor'   => array
							(
								'rgb' => '00CCFF'
							)
						)
					)
				);
			
			
			}

			if(isset($value["NCT/phase"]) and ( $value["NCT/phase"]=="Phase 1"  or $value["NCT/phase"]=="Phase 0/Phase 1"))
			{
			
				$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => '99CC00'
							),
							'endcolor'   => array
							(
								'rgb' => '99CC00'
							)
						)
					)
				);
			
			
			}
			if(isset($value["NCT/phase"]) and ( $value["NCT/phase"]=="Phase 2" or $value["NCT/phase"]=="Phase 1/Phase 2"))
			{
			
				$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => 'FFFF00'
							),
							'endcolor'   => array
							(
								'rgb' => 'FFFF00'
							)
						)
					)
				);
			
			
			}
			
			if(isset($value["NCT/phase"]) and ($value["NCT/phase"]=="Phase 3" or  $value["NCT/phase"]=="Phase 2/Phase 3"))
			{
			
				$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => 'FF9900'
							),
							'endcolor'   => array
							(
								'rgb' => 'FF9900'
							)
						)
					)
				);
			
			
			}

			if(isset($value["NCT/phase"]) and ( $value["NCT/phase"]=="Phase 4" or $value["NCT/phase"]=="Phase 3/Phase 4"))
			{
			
				$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
				(
					array
					(
						'fill' => array
						(
							'type'       => PHPExcel_Style_Fill::FILL_SOLID,
							'rotation'   => 0,
							'startcolor' => array
							(
								'rgb' => 'FF0000'
							),
							'endcolor'   => array
							(
								'rgb' => 'FF0000'
							)
						)
					)
				);
			}
			$i++;
		}
		
	}
}
//UNSTACKED
else
{
	foreach ($excelarray as $key=>$value)
	{
	if( isset($value['section']) or $_POST['list']== 'all' )
		{
		
		$objPHPExcel->getProperties()->setCreator("The Larvol Group")
									 ->setLastModifiedBy("TLG")
									 ->setTitle("Larvol Trials")
									 ->setSubject("Larvol Trials")
									 ->setDescription("Excel file generated by Larvol Trials")
									 ->setKeywords("Larvol Trials")
									 ->setCategory("Clinical Trials");
		$objPHPExcel->getActiveSheet()->getStyle('"A' . $i . ':K' . $i . '"')->applyFromArray($styleThinBlueBorderOutline);
		
		$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'NCT' . sprintf("%08s",$value["NCT.nct_id"]));
		
		$value["NCT.brief_title"]=fix_special_chars($value["NCT.brief_title"]);
		$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value["NCT.brief_title"]);
		
		$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setUrl('http://clinicaltrials.gov/ct2/show/'. $objPHPExcel->getActiveSheet()->getCell('A' . $i)->getValue() );
		$objPHPExcel->getActiveSheet()->getCell('B' . $i)->getHyperlink()->setTooltip('Source - ClinicalTrials.gov');
		$objPHPExcel->getActiveSheet()->getStyle('B' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		if(isset($value["NCT.enrollment"])) 				$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value["NCT.enrollment"]);
		
		$value["region"]=fix_special_chars($value["region"]);
		$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value["region"]);
		
		if(isset($value["NCT.overall_status"])) 			$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value["NCT.overall_status"]);
		
		if(isset($value["NCT.lead_sponsor"]))
		{
			$value["NCT.lead_sponsor"]=fix_special_chars($value["NCT.lead_sponsor"]);
			$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $value["NCT.lead_sponsor"]);
		}
		
		if(isset($value["NCT.condition"])) 					
		{
			$value["NCT.condition"]=fix_special_chars($value["NCT.condition"]);
			$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value["NCT.condition"]);
		}
		
		if(isset($value["NCT.intervention_name"])) 			
		{
			$value["NCT.intervention_name"]=fix_special_chars($value["NCT.intervention_name"]);
			$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, $value["NCT.intervention_name"]);
		}
		if(isset($value["NCT.start_date"])) 				$objPHPExcel->getActiveSheet()->setCellValue('I' . $i, date('m/y',strtotime($value["NCT.start_date"])));
		//if(isset($value["NCT.primary_completion_date"])) 	$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, $value["NCT.primary_completion_date"]);
		if(isset($value["inactive_date"])) 	$objPHPExcel->getActiveSheet()->setCellValue('J' . $i, date('m/y',strtotime($value["inactive_date"])));
		if(isset($value["NCT.phase"])) 						$objPHPExcel->getActiveSheet()->setCellValue('K' . $i, $value["NCT.phase"]);
		if($bgcol=="D5D3E6") $bgcol="EDEAFF"; 	else $bgcol="D5D3E6";
		
		$objPHPExcel->getActiveSheet()->getStyle('A' . $i .':J' .$i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => $bgcol
						),
						'endcolor'   => array
						(
							'rgb' => $bgcol
						)
					)
				)
			);
			
		
		$objPHPExcel->getActiveSheet()->getStyle('A1:K1')->applyFromArray
		(
			array
			(
				'font'    => array
				(
					'bold'      => true
				),
				'alignment' => array
				(
					'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
				),
				'borders' => array
				(
					'top'     => array
					(
						'style' => PHPExcel_Style_Border::BORDER_THIN
					)
				),
				'fill' => array
				(
					'type'       => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
					'rotation'   => 90,
					'startcolor' => array
					(
						'argb' => 'FFA0A0A0'
					),
					'endcolor'   => array
					(
						'argb' => 'FFFFFFFF'
					)
				)
			)
		);

		/*
		if(
			isset($value["NCT.overall_status"]) and 
			(
				$value["NCT.overall_status"]=="Completed" or 
				$value["NCT.overall_status"]=="Approved for marketing" or 
				$value["NCT.overall_status"]=="Temporarily not available" or 
				$value["NCT.overall_status"]=="No Longer Available" or 
				$value["NCT.overall_status"]=="Withdrawn" or 
				$value["NCT.overall_status"]=="Terminated" or 
				$value["NCT.overall_status"]=="Suspended" or 
				$value["NCT.overall_status"]=="Completed"
			)
		)	$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':K' . $i)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
		*/
		
		if(isset($value["NCT.phase"]) and $value["NCT.phase"]=="Phase 0")
		{
		
			$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => '00CCFF'
						),
						'endcolor'   => array
						(
							'rgb' => '00CCFF'
						)
					)
				)
			);
		
		
		}

		if(isset($value["NCT.phase"]) and ( $value["NCT.phase"]=="Phase 1"  or $value["NCT.phase"]=="Phase 0/Phase 1"))
		{
		
			$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => '99CC00'
						),
						'endcolor'   => array
						(
							'rgb' => '99CC00'
						)
					)
				)
			);
		
		
		}
		if(isset($value["NCT.phase"]) and ( $value["NCT.phase"]=="Phase 2" or $value["NCT.phase"]=="Phase 1/Phase 2"))
		{
		
			$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => 'FFFF00'
						),
						'endcolor'   => array
						(
							'rgb' => 'FFFF00'
						)
					)
				)
			);
		
		
		}
		
		if(isset($value["NCT.phase"]) and ($value["NCT.phase"]=="Phase 3" or  $value["NCT.phase"]=="Phase 2/Phase 3"))
		{
		
			$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => 'FF9900'
						),
						'endcolor'   => array
						(
							'rgb' => 'FF9900'
						)
					)
				)
			);
		
		
		}

		if(isset($value["NCT.phase"]) and ( $value["NCT.phase"]=="Phase 4" or $value["NCT.phase"]=="Phase 3/Phase 4"))
		{
		
			$objPHPExcel->getActiveSheet()->getStyle('K' . $i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => 'FF0000'
						),
						'endcolor'   => array
						(
							'rgb' => 'FF0000'
						)
					)
				)
			);
		
		
		}
		$i++;
	}
	}
	
}

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(13);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(5);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(35);
$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(9);
$objPHPExcel->getActiveSheet()->getStyle('B1:K900')->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->getStyle('A1:K200')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'NCT ID');
$objPHPExcel->getActiveSheet()->setTitle('Larvol Trials');
$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('Calibri');



/*********/
$objPHPExcel->createSheet(1);
$objPHPExcel->setActiveSheetIndex(1);
$objPHPExcel->getActiveSheet()->setTitle('UPMs');

$objPHPExcel->getActiveSheet()->getStyle('B1:F200')->getAlignment()->setWrapText(true);
$objPHPExcel->getActiveSheet()->setCellValue('A1' , 'Corresponding Trial');
$objPHPExcel->getActiveSheet()->setCellValue('B1' , 'Product');
$objPHPExcel->getActiveSheet()->setCellValue('C1' , 'Event Description');
$objPHPExcel->getActiveSheet()->setCellValue('D1' , 'Status');
$objPHPExcel->getActiveSheet()->setCellValue('E1' , 'Conditions');
$objPHPExcel->getActiveSheet()->setCellValue('F1' , 'Start');
$objPHPExcel->getActiveSheet()->setCellValue('G1' , 'End');
$objPHPExcel->getActiveSheet()->setCellValue('H1' , 'Result link');

$styleThinBlueBorderOutline = array(
	'borders' => array(
		'inside' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF0000FF'),
		),
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF0000FF'),
		),
	),
);

$objPHPExcel->getActiveSheet()->getStyle('A1:H1')->applyFromArray($styleThinBlueBorderOutline);

$i=2;
if(isset($newupmarray) and is_array($newupmarray))
	{
	foreach ($newupmarray as $ke=>$valu)
	{
	if(isset($valu) and is_array($valu)) {
	foreach ($valu as $key => $value)
	{
		$objPHPExcel->getActiveSheet()->getStyle('"A' . $i . ':H' . $i . '"')->applyFromArray($styleThinBlueBorderOutline);
		$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $value["corresponding_trial"] );
		$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $value["product"] );
		$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $value["event_description"] );
		$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $value["status"]);
		$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $value["condition"]);
		$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $value["start_date"]);
		$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $value["end_date"]);
		$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, "Link");
		
		if( (!isset($value["result_link"]) or empty($value["result_link"])) )
		{
			$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, '');
		}
		else
		{
			$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setUrl($value["result_link"]);
			$objPHPExcel->getActiveSheet()->getCell('H' . $i)->getHyperlink()->setTooltip('Result Link');
			$objPHPExcel->getActiveSheet()->getStyle('H' . $i)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		}
		
		
		$objPHPExcel->getActiveSheet()->getStyle('A' . $i . ':H' . $i )->applyFromArray
			(
				array
				(
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_SOLID,
						'rotation'   => 0,
						'startcolor' => array
						(
							'rgb' => 'C5E5FA'
						),
						'endcolor'   => array
						(
							'rgb' => 'C5E5FA'
						)
					)
				)
			);		
		
		
		
		
		$i++;
	}
	}
	}
	}
$objPHPExcel->getActiveSheet()->getStyle('A1:H1')->applyFromArray
			(
				array
				(
					'font'    => array
					(
						'bold'      => true
					),
					'alignment' => array
					(
						'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					),
					'borders' => array
					(
						'top'     => array
						(
							'style' => PHPExcel_Style_Border::BORDER_THIN
						)
					),
					'fill' => array
					(
						'type'       => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
						'rotation'   => 90,
						'startcolor' => array
						(
							'argb' => 'FFC5E5FA'
						),
						'endcolor'   => array
						(
							'argb' => 'FFDBFCFF'
						)
					)
				)
			);
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);			
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(40);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(26);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(12);		




/********/











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
?>