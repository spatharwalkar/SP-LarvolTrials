<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('special_chars.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','300');	//5 minutes

if(!isset($_POST['cparams']) && !isset($_POST['params']) && !isset($_POST['results']))  return (false);

//**************
ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<base target='_blank' />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>Online Trial Tracker</title>


</head>
<body>
<?php 
$content = new ContentManager();
$content->setSortParams();
$content->getChangeRange();
$content->chkType();
class ContentManager 
{
	
	private $params 	= array();
	private $fid 		= array();
	private $allfilterarr 	= array();
	private $sortorder;
	private $sort_params 	= array();
	private $sortimg 	= array();
	private $displist 	= array('Enrollment' => 'NCT/enrollment','Region' => 'region', 'Interventions' => 'NCT/intervention_name', 
								'Sponsor' => 'NCT/lead_sponsor', 'Status' => 'NCT/overall_status', 'Conditions' => 'NCT/condition', 
								'Study Dates' => 'NCT/start_date', 'Phase' => 'NCT/phase');
								
	private $imgscale 	= array('style="width:14px;height:14px;"', 'style="width:12px;height:12px;"', 
								'style="width:10px;height:10px;"', 'style="width:8px;height:8px;"', 
								'style="width:6px;height:6px;"');
								
	private $actfilterarr 	= array('nyr'=>'Not yet recruiting', 'r'=>'Recruiting', 'ebi'=>'Enrolling by invitation', 
								'anr'=>'Active, not recruiting', 'a'=>'Available', 'nlr' =>'No longer recruiting');
								
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
	private $activestatus;
	private $inactivestatus;
	private $allstatus;
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
		$this->results_per_page = 100;//$db->set['results_per_page'];
		$this->loggedIn	= $db->loggedIn();
		
		

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

		$this->type = (isset($_POST["list"])) ? ($_POST["list"].'array') : 'activearray' ;
		if(isset($_POST['list']) && $_POST['list'] == 'inactive') { 
			$this->inactflag = 1; 		// checking if any of the inactive filters are set
			
		} else if(isset($_POST['list']) && $_POST['list'] == 'all') { 
			$this->allflag = 1; 	 	// checking if any of the all filters are set
			
		} else { 
			$this->actflag = 1; 		// checking if any of the active filters are set
		}
		
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
	
	function getChangeRange() {
	
		//added for highlighting changes
		if(isset($_POST['edited']) && $_POST['edited'] == 'oneweek') {
			$this->edited = ' -1 week ';
		} else if(isset($_POST['edited']) && $_POST['edited'] == 'onemonth') {
			$this->edited = ' -1 month ';
		} else {
			$this->edited = ' -1 week ';
		}

	}
	
	function commonControls($count, $act, $inact, $all) {
		
		$enumvals = getEnumValues('clinical_study', 'institution_type');
	
		
	
	}
	
	function pagination($page, $count, $params, $leading, $tt_type, $stacktype) {
		
		

	}
	
	function processParams() {
		
		global $logger;
		$return_param	= array();
		$return_param['fin_arr'] = array();
		$return_param['all_records'] = array();
		$return_param['upmDetails'] = array();
		$ins_params		= array();
		$return_param['showRecordsCnt'] = 0;
		$showRecords_inactivearray_Cnt = 0;
		$showRecords_activearray_Cnt = 0;
		$showRecords_allarray_Cnt = 0;
		$return_param['stack_inactive_count'] = 0;
		$return_param['stack_active_count'] = 0;
		$return_param['stack_total_count'] = 0;
		$return_param['inactivearray'] = array();
		$return_param['activearray'] = array();
		$return_param['allarray'] = array();
		
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

			$return_param['link_expiry_date'][$pk] = array();
			$totinactivecount = 0; 
			$totactivecount	 = 0; 
			$totalcount	= 0;
			$all_ids = array();			
			
			//New Link Method
			if(isset($_POST['results'])) {
			
				$e 	= explode(".", $pv);$identifier_for_result_set = '';
				
				if($link_expiry_date != NULL && $link_expiry_date != '')
					$return_param['link_expiry_date'][$pk][] = $link_expiry_date;
				//Retrieving headers
				if($_POST['type'] == 'row') {
				
					if($pk != 0) {
						$res = getLinkDetails('rpt_ott_header', 'header', 'id', $e[0]);
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						if($res['expiry'] != NULL && $res['expiry'] != '')
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
							
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
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						if($res['expiry'] != NULL && $res['expiry'] != '')
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
							
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
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						if($res['expiry'] != NULL && $res['expiry'] != '')
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
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
						$return_param['ltype'][$pk] = htmlentities($res['header']);
						if($res['expiry'] != NULL && $res['expiry'] != '')
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
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
						
						if($res['expiry'] != NULL && $res['expiry'] != '')
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
							
						$search_data_content = $res['result_set'];
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($search_data_content))));
						
					} else if($identifier_for_result_set == '-1') {
					
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $tt);
						
						if($res['expiry'] != NULL && $res['expiry'] != '')
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
						$search_data_content = $res['result_set'];
						if($res['expiry'] != NULL && $res['expiry'] != '')
							$return_param['link_expiry_date'][$pk][] = $res['expiry'];
							
						$excel_params = unserialize(stripslashes(gzinflate(base64_decode($search_data_content))));
						
					} else {
						
						$res = getLinkDetails('rpt_ott_trials', 'result_set', 'id', $tt);
						
						if($res['expiry'] != NULL && $res['expiry'] != '')
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
				
			} else {
				$return_param['activearray'][] = array('section' => $pk);
				$return_param['inactivearray'][] = array('section' => $pk);
				$return_param['allarray'][] = array('section' => $pk);
			}
			
			//Added to consolidate the data returned in an mutidimensional array format as opposed to earlier 
			//when it was not returned in an mutidimensional array format.
			$indx = 0;
			foreach($arrr as $k => $v) 
			{ 
				foreach($v as $kk => $vv) 
				{ 
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && $kk != 'NCT/lead_sponsor')
					{
						if($kk == 'NCT/start_date' || $kk == 'inactive_date')
							$arr[$indx][$kk] = (is_array($vv)) ? $vv[0] : $vv;
						else
							$arr[$indx][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					}
					else
					{
						$arr[$indx][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
					}
				}
				++$indx;
			}
			
			//Process to check for changes/updates in trials, matched & unmatched upms.
			foreach($arr as $key => $val) { 
				
				$nct = array();
				$allUpmDetails = array();
				
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->time_machine, $this->edited);
				 
				//checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->time_machine, $this->edited);

				if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
			
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($nct[$val['NCT/nct_id']]['edited']['NCT/acronym']) && !isset($nct[$val['NCT/acronym']])) {
						unset($nct[$val['NCT/nct_id']]['edited']['NCT/acronym']);
						$acroynm_field_index = array_search('NCT/acronym', $nct[$val['NCT/nct_id']]['edited']);
						unset($nct[$val['NCT/nct_id']]['edited'][$acroynm_field_index]);
					}
					
					$prev_value = '';
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($nct[$val['NCT/nct_id']]['edited']['NCT/enrollment'])) {
						$prev_value = substr($nct[$val['NCT/nct_id']]['edited']['NCT/enrollment'],16);
						
						if(!getDifference($prev_value, $nct[$val['NCT/enrollment']])) {
							unset($nct[$val['NCT/nct_id']]['edited']['NCT/enrollment']);
							$enroll_field_index = array_search('NCT/enrollment', $nct[$val['NCT/nct_id']]['edited']);
							unset($nct[$val['NCT/nct_id']]['edited'][$enroll_field_index]);
						}
					}
					
					if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y') {
						$return_param['fin_arr'][$pk][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
						$return_param['all_records'][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					}
					foreach($allUpmDetails[$val['NCT/nct_id']] as $kk => $vv) {
						if(isset($vv['edited']) && !empty($vv['edited'])) {
							$return_param['upmDetails'][$val['NCT/nct_id']][] = $vv;
						}
					}
					
				} else {
					$return_param['fin_arr'][$pk][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$return_param['all_records'][$val['NCT/nct_id']] = array_merge($nct[$val['NCT/nct_id']], $val);
					$return_param['upmDetails'][$val['NCT/nct_id']] = $allUpmDetails[$val['NCT/nct_id']];
				}
				
				if(in_array($val['NCT/overall_status'], $this->inactfilterarr)) {
				
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
					
				} else {
				
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
							
					$vall = implode(",",array_keys($this->allfilterarr, $val['NCT/overall_status']));
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
				
				if(!in_array($val['NCT/overall_status'],$this->actfilterarr) && !in_array($val['NCT/overall_status'],$this->inactfilterarr) && 
				!in_array($val['NCT/overall_status'],$this->allfilterarr)) { 
					$log 	= 'WARN: A new value "' . $val['NCT/overall_status'] . '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				
				//getting count of active trials from a common function used in run_heatmap.php and here
				$all_ids[] = $val['larvol_id'];
				sort($all_ids); 
				$totalcount = count($all_ids);
				$totactivecount = getActiveCount($all_ids, $this->time_machine);
				$totinactivecount = $totalcount - $totactivecount; 

			}
			
			$return_param['showRecordsCnt'] = (isset($_POST["list"])) ? (${'showRecords_'.$_POST["list"].'array_Cnt'}) : ($showRecords_activearray_Cnt);
			$return_param['stack_inactive_count'] 	= $return_param['stack_inactive_count'] + $totinactivecount;
			$return_param['stack_active_count']		= $return_param['stack_active_count'] + $totactivecount;
			$return_param['stack_total_count']		= $return_param['stack_total_count'] + $totalcount;
			
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
	
	function chkType() {
	
		global $now;
		global $logger;
		
		$process_params = array();
		$process_params['link_expiry_date'] = array();
		$unmatched_upm_details = array();
		$header_details = array();
		$unmatched_upms_default_style = 'collapse';
		$pdf_content='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol PDF Export</title>
</head>
<style type="text/css">

body {
font-family:Arial;
font-size:8pt;
font-color:black;
}
td,th {
	vertical-align:top;
	
}
.drop {
	height:4.4em;
	margin: 0 0 0 4px;
	display:inline-block;
	vertical-align:top;
	text-align:left;
	background-color:#ffffff;
	opacity:0.92;
	filter:alpha(opacity=92);
	overflow:hidden;
	padding:0 0px 0 0px;
	position:absolute;
	
	border: 0.5px solid black;
}

.title {
	background-color:#EDEAFF;
}

.alttitle {
	background-color:#D5D3E6;
}

.highlight {
	color:#FF0000;
}

.manage {
	font-weight:normal;
	table-layout:fixed;
}

.manage {
	font-weight:normal;
	table-layout:fixed;
}

.manage td{
	border-left:0.5px solid blue;
	border-bottom:0.5px solid blue;
	margin:0;
	padding:0;
	
}

.manage th {

	border-top:0.5px solid blue;
	border-bottom:0.5px solid blue;
	border-left:0.5px solid blue;
	color:#0000FF;
	text-decoration:none;
	white-space:nowrap;
}

.manage th a{
	color:#0000FF;
	text-decoration:none;
}

.newtrial td,  .newtrial td a{
	color:#FF0000;
}
.rowcollapse {
	vertical-align:top;
	
	
	padding-top:0;
	margin:0;
}

.rowcollapse:hover {
	max-height:none;
}

.bomb {
	font-size:8pt;
	float:left;
	margin-top:20px;
	
	
	text-align:center;
}
.result {
	font-weight:bold;
	font-size:8pt;
}

.secondrow th{
	
	padding-left:0;
	padding-right:0;
	border-top:0;
}

.rightborder {
	border-right: 0.5px solid blue;
}
.norecord {
	border-bottom: 0.5px solid blue; 
	border-right: 0.5px solid blue;
	border-top:0;
	padding:0px;
	height:auto;
	line-height:normal;
	font-weight:normal;
	background-color: #EDEAFF;
	color:#000000;
}

.rowcollapse:hover .upmcollapse { display: block; }

.row {
	background-color:#D8D3E0;
	text-align:center;
}

.altrow {
	background-color:#C2BECA;
	text-align:center;
}

.region {
	background-color:#FFFFFF;
}

.altregion {
	background-color:#F2F2F2;
}
.box_rotate {
	-moz-transform: rotate(90deg);
	-o-transform: rotate(90deg);
	-webkit-transform: rotate(90deg);
	writing-mode: tb-rl;

	
	margin:2px;
}

.noborder {
	
	border-right: 0.5px solid blue;
	border-top: 0.5px solid blue;
	border-bottom: 0.5px solid blue;
}
.new {
	height:1.2em;
	
	border:0.5px solid black;
}

.new ul{
	list-style:none;
	margin:5px;
	padding:0px;
}

.new ul li{
	
	float:left;
	margin:2px;
}
.notopbottomborder{
	border-top:none;
}
.borderbottom{
	border-bottom: 0.5px solid blue;
}
.leftrightborderblue{
	border-right: 0.5px solid blue;
	border-left: 0.5px solid blue;	
}
.sectiontitles{
    font-family: Arial;
    font-size: 8pt;
    font-weight: bold;
    background-color: #A2FF97;
}
.zeroborder{
	border-bottom:none;
	border-top:none;
	border-left:none;
	border-right:none;
}
.norightborder{
	border-right:none;
}
.upmheader{
	color:#0000FF;
	font-weight:bold;
}
.rowcollapseupm {
	vertical-align:top;
	max-height:1.3em;
	overflow:visible;
	padding-top:0;
	margin:0;
}
.rowcollapseupm:hover {
	max-height:none;
}
.titleupmodd{
	background-color:#C5E5FA;
}	
.titleupmeven{
	background-color:#95C7E8;
}
tr.upms td, tr.upms th{
	text-decoration:none;
	text-align: center;
	background-color:#C5E5FA;
}

tr.upms th{
	color:#0000FF;
	font-weight:normal;
	border-top:none;
}

tr.upms td.txtleft{
	text-align: left;
}

tr.upms td a{
	color:#0000FF;
	text-decoration:none;
}

a {
text-decoration:none;
width:100%;
height:100%;

}
@page {
margin-top: 1em;
margin-bottom: 2em;
}	
</style>
<body><div align="center"><img src="images/Larvol-Trial-Logo-notag.png" align="center" alt="Main" width="250" height="38" id="header" /></div>
<br><br><br>';
		
		
		
		//Stacked Ott.	
		if(isset($_POST['cparams']) || (isset($_POST['results']) && isset($_POST['type']))) {
		
			//Process the get parameters and extract the information
			$process_params = $this->processParams();
			
			if(isset($_POST['institution']) && $_POST['institution'] != '') {
			
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($process_params['insparams']))));
				$this->commonControls($process_params['showRecordsCnt'], $ins['actcnt'], $ins['inactcnt'], ($ins['actcnt'] + $ins['inactcnt']));
				$foundcount = ($ins['actcnt'] + $ins['inactcnt']);
			} else {
			
				$this->commonControls($process_params['showRecordsCnt'], $process_params['stack_active_count'], $process_params['stack_inactive_count'], 
				$process_params['stack_total_count']);
				$foundcount = $process_params['stack_total_count'];
			}
			
		

			//Pagination
			$page = 1;
			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			if(isset($_POST['pg'])) $page = mysql_real_escape_string($_POST['pg']); 
			if(!is_numeric($page)) die('non-numeric page');
			
			if($_POST['type'] == 'col')
				$count = count($process_params[$this->type]);
			else
				$count = $process_params['showRecordsCnt'];
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;
			
			if($count > $this->results_per_page) {
				if(isset($_POST['results']) && isset($_POST['type'])) {
					$this->pagination($page, $count, NULL, NULL, 'stack', $_POST['type']);
				} else {
					$this->pagination($page, $count, NULL, NULL, 'stack', $process_params['c_params']['type']);
				}
			}
			
			$pdf_content.= $this->displayHeader();
			
			$first_ids[] = explode('.',$process_params['c_params'][0]);
			
			foreach($process_params['params_arr'] as $k => $v) {
			
				$row_upm_arr = array();
				$header_details[$k] = trim($process_params['ltype'][$k]);
				$vv = explode('.', $v);
				
				if($k != 0) {
				
					//result set separator as a separate parameter and maintaining backward compatibility
					if($vv[1] == '-1' || $vv[1] == '-2') {
					
						if(isset($vv[3])) { 
						
							if(isset($_POST['results']) && $_POST['type'] == 'col') { 
							
								$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[3]);
								if(isset($_POST['v']) && $_POST['v'] == 1)
									$val['intervention_name'] = explode('\n',$val['intervention_name']);
								else
									$val['intervention_name'] = explode(',',$val['intervention_name']);
								
								$unmatched_upm_details[$k] = $this->getNonAssocUpm($val['intervention_name'], $k);
								
							} else if(isset($_POST['results']) && $_POST['type'] == 'row') { 
								
								$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[3]); 
								if(isset($_POST['v']) && $_POST['v'] == 1)
									$row_upm_arr = array_merge($row_upm_arr,explode('\n',$res['intervention_name']));
								else
									$row_upm_arr = array_merge($row_upm_arr,explode(',',$res['intervention_name']));
							}
						}
						
					} else {
					
						if(isset($vv[2])) { 
						
							if(isset($_POST['results']) && $_POST['type'] == 'col') { 
								
								$val = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[2]);
								if(isset($_POST['v']) && $_POST['v'] == 1)
									$val['intervention_name'] = explode('\n',$val['intervention_name']);
								else
									$val['intervention_name'] = explode(',',$val['intervention_name']);
								
								$unmatched_upm_details[$k] = $this->getNonAssocUpm($val['intervention_name'], $k);
								
							} else if(isset($_POST['results']) && $_POST['type'] == 'row') { 
								
								$res = getLinkDetails('rpt_ott_upm', 'intervention_name', 'id', $vv[2]); 
								if(isset($_POST['v']) && $_POST['v'] == 1)
									$row_upm_arr = array_merge($row_upm_arr,explode('\n',$res['intervention_name']));
								else
									$row_upm_arr = array_merge($row_upm_arr,explode(',',$res['intervention_name']));
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
									$val['intervention_name'] = explode('\n',$val['intervention_name']);
								else
									$val['intervention_name'] = explode(',',$val['intervention_name']);
								
								$unmatched_upm_details[$k] = $this->getNonAssocUpm($val['intervention_name'], $k);
								
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
									$val['intervention_name'] = explode('\n',$val['intervention_name']);
								else
									$val['intervention_name'] = explode(',',$val['intervention_name']);
								
								$unmatched_upm_details[$k] = $this->getNonAssocUpm($val['intervention_name'], $k);
									
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
							$unmatched_upm_details[$k] = $this->getNonAssocUpm($val, $k);
						}
					}
				}
				
			}
			
			if(isset($row_upm_arr) && !empty($row_upm_arr)) { 
				$unmatched_upms_default_style = 'expand';
				$upm_string = $this->getNonAssocUpm($row_upm_arr, 'rowupm');
				if($upm_string != '') {
					$pdf_content.='<tr class="trialtitles">'
					. '<td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
				. 'style="border-bottom:0.5px solid blue;background-position:left center;"'
					. ' onclick="sh(this,\'rowupm\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . '</td></tr>' . $upm_string;
				} else {
					$pdf_content.='<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) 
					. '" class="notopbottomborder leftrightborderblue sectiontitles">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>';
				}
			} 
			
			if($count > 0) {
				
				$pdf_content.=displayContent($this->displist, $process_params[$this->type], $this->edited, $this->time_machine, $this->pstart, $this->last, 
				$this->phase_arr, $process_params['all_records'], $this->actfilterarr, $this->current_yr, $this->second_yr, $this->third_yr,
				$process_params['upmDetails'], $header_details, $unmatched_upm_details, $unmatched_upms_default_style);
				
			} else {
			
				foreach($process_params['params_arr'] as $k => $v) {
					if($unmatched_upm_details[$k] != '') {
					
						if($unmatched_upms_default_style == 'expand')
							$image = 'down';
						else
							$image = 'up';
						
						$pdf_content.='<tr class="trialtitles">'
						. '<td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
						. 'style="border-bottom:0.5px solid blue;background-position:left center;"'
						. ' onclick="sh(this,\'' . $k . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . trim($header_details[$k]) 
						. '</td></tr>' . $unmatched_upm_details[$k];
						
						if(!isset($_POST['chkOnlyUpdated']))
							$pdf_content.='<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found.</td></tr>'; 
					}
					
				}
				
				if($unmatched_upms_default_style == 'expand' && !isset($_POST['chkOnlyUpdated']))
					$pdf_content.='<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found.</td></tr>'; 
					
			}
			
			if(isset($_POST['trunc'])) {
				$t = unserialize(gzinflate(base64_decode($_POST['trunc'])));
				if($t == 'y') $pdf_content.='<span style="font-size:8pt;;color:red;">Note: all data could not be shown</span>';
			}
			$pdf_content.='</table><br/>';
			
			
			
			$shownArr = array();$foundArr = array();
			if($process_params['showRecordsCnt'] > 0) {
			
				$current_type = $this->type;
				$shownArr = array();
				$foundArr = array();
				
				foreach($process_params[$current_type] as $key => $vvalue) { 
			
					//foreach($value as $kkey => $vvalue){ echo '<pre>';print_r($vvalue);
						unset($vvalue['edited']);
						unset($vvalue['new']);
						unset($vvalue['larvol_id']);
						unset($vvalue['inactive_date']);
//						unset($vvalue['region']);
						foreach($vvalue as $k => $v) {
							if(strpos($k, 'NCT/') !== FALSE) {
							
								$newkey = str_replace('NCT/','NCT.',$k);
								$vvalue[$newkey] = $v;
								unset($vvalue[$k]);
							}
						}
						
						$shownArr[$process_params[$current_type][$key][$kkey]['NCT/nct_id']] = $vvalue;
					//}
				}
				foreach($process_params['fin_arr'] as $key => $value) {
					foreach($value as $kkey => $vvalue){
						unset($vvalue['edited']);
						unset($vvalue['new']);
						unset($vvalue['larvol_id']);
						unset($vvalue['inactive_date']);
						unset($vvalue['region']);
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
				
					unset($value['edited']);
					unset($value['new']);
					unset($value['larvol_id']);
					unset($value['inactive_date']);
//					unset($value['region']);
					
					foreach($value as $k => $v) {
						if(strpos($k, 'NCT/') !== FALSE) {
						
							$newkey = str_replace('NCT/','NCT.',$k);
							$value[$newkey] = $v;
							unset($value[$k]);
						}
					}
				}
				
				$this->downloadOptions($process_params['showRecordsCnt'], $foundcount, $shownArr, $foundArr);
				$pdf_content.='<br/>';
			}
			
			$link_expiry_date = array();
			foreach($process_params['link_expiry_date'] as $key => $value) 
				foreach($value as $kkey => $vvalue) 
					$link_expiry_date[] = $vvalue;
				
			//Expiry feature for new link method
			if(!empty($link_expiry_date) && ($this->loggedIn)) {
				$pdf_content.='<span style="font-size:8pt;color:red;">Expires on: ' . $link_expiry_date[0]  . '</span>';
			}
			
		} else {
			
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
			
				$results_params 	= explode(".", $_POST['results']);
				
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $results_params[0]);
				$rowlabel = trim($res['header']);
				$link_expiry_date[] = $res['expiry'];
				
				$res = getLinkDetails('rpt_ott_header', 'header', 'id', $results_params[1]);
				$columnlabel = trim($res['header']);
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
			if($bomb != '') {
				
			}
				
			
			$arr = array();
			$nct = array();
			
			$allUpmDetails	= array();
			$upmDetails	 	= array();
			$all_ids = array();
			
			$arrr = search($params,$this->fid,NULL,$this->time_machine);
			foreach($arrr as $k => $v) 
			{
				foreach($v as $kk => $vv) 
				{
					if($kk != 'NCT/condition' && $kk != 'NCT/intervention_name' && $kk != 'NCT/lead_sponsor')
					{
						if($kk == 'NCT/start_date' || $kk == 'inactive_date')
							$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? $vv[0] : $vv;
						else
							$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? implode(' ', $vv) : $vv;
					}
					else
					{
						$arr[$v['NCT/nct_id']][$kk] = (is_array($vv)) ? implode(', ', $vv) : $vv;
					}
				}
			}
			
			foreach($arr as $key => $val) { 
			
				//checking for updated and new trials
				$nct[$val['NCT/nct_id']] = getNCT($val['NCT/nct_id'], $val['larvol_id'], $this->time_machine, $this->edited);
				
				if (!is_array($nct[$val['NCT/nct_id']])) { 
					$nct=array();
					$val['NCT/intervention_name'] = '(study not in database)';
				}
				
				//checking for updated and new unmatched upms.
				$allUpmDetails[$val['NCT/nct_id']] = getCorrespondingUPM($val['NCT/nct_id'], $this->time_machine, $this->edited);
				if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
				
					//unsetting value for field acroynm if it has a previous value and no current value
					if(isset($nct[$val['NCT/nct_id']]['edited']['NCT/acronym']) && !isset($nct[$val['NCT/acronym']])) {
						unset($nct[$val['NCT/nct_id']]['edited']['NCT/acronym']);
						$acroynm_field_index = array_search('NCT/acronym', $nct[$val['NCT/nct_id']]['edited']);
						unset($nct[$val['NCT/nct_id']]['edited'][$acroynm_field_index]);
					}
					
					$prev_value = '';
					//unsetting value for field enrollment if the change is less than 20 percent
					if(isset($nct[$val['NCT/nct_id']]['edited']['NCT/enrollment'])) { 
						$prev_value = substr($nct[$val['NCT/nct_id']]['edited']['NCT/enrollment'],16);
						
						if(!getDifference($prev_value, $nct[$val['NCT/enrollment']])) {
							unset($nct[$val['NCT/nct_id']]['edited']['NCT/enrollment']);
							$enroll_field_index = array_search('NCT/enrollment', $nct[$val['NCT/nct_id']]['edited']);
							unset($nct[$val['NCT/nct_id']]['edited'][$enroll_field_index]);
						}
					}
					
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
				
					//$totinactivecount++;
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
				
					//$totactivecount++;
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
				
				if(isset($_POST['nyr']) || isset($_POST['r']) || isset($_POST['ebi']) || isset($_POST['anr']) 
					|| isset($_POST['a']) || isset($_POST['wh']) || isset($_POST['afm']) || isset($_POST['tna']) 
					|| isset($_POST['nla']) || isset($_POST['wd']) || isset($_POST['t']) || isset($_POST['s']) 
					|| isset($_POST['c']) || isset($_POST['nlr'])) {	
							
					$vall = implode(",",array_keys($this->allfilterarr, $val['NCT/overall_status']));
					if(array_key_exists($vall, $_POST)) {
					
						if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
							if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
								$this->allarray[] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => '0'));
						} else {
							$this->allarray[] = array_merge($val, array('section' => '0'));
						}
					} 
				} else {
					if(isset($_POST['chkOnlyUpdated']) && $_POST['chkOnlyUpdated'] == 1) {
						if(!empty($nct[$val['NCT/nct_id']]['edited']) || $nct[$val['NCT/nct_id']]['new'] == 'y')
								$this->allarray[] = array_merge($nct[$val['NCT/nct_id']], $val, array('section' => '0'));
					} else {
						$this->allarray[] = array_merge($val, array('section' => '0'));
					}
				}
				
				if(!in_array($val['NCT/overall_status'],$this->actfilterarr) && !in_array($val['NCT/overall_status'],$this->inactfilterarr) && 
				!in_array($val['NCT/overall_status'],$this->allfilterarr)) { 
					$log 	= 'WARN: A new value "' . $val['NCT/overall_status'] . '" (not listed in the existing rule), was encountered for field overall_status.';
					$logger->warn($log);
					unset($log);
				}
				
				$all_ids[] = $val['larvol_id'];
			}
			
			/*--------------------------------------------------------
			|Variables set for count when filtered by institution_type
			---------------------------------------------------------*/
			if(isset($_POST['instparams']) && $_POST['instparams'] != '') {
			
				$insparams = $_POST['instparams'];
			
			} else {
			
				$insparams = rawurlencode(base64_encode(gzdeflate(serialize(array('actcnt' => $totactivecount,'inactcnt' => $totinactivecount)))));
															
			}
			
			sort($all_ids); 
			$totalcount = count($all_ids);
			
			//getting count of active trials from a common function used in run_heatmap.php and here
			$totactivecount = getActiveCount($all_ids, $this->time_machine);
			$totinactivecount = $totalcount - $totactivecount; 
			$count = count($this->{$this->type});
			
			if(isset($_POST['institution']) && $_POST['institution'] != '') {
				$ins = unserialize(gzinflate(base64_decode(rawurldecode($insparams))));
				$foundcount = ($ins['actcnt'] + $ins['inactcnt']);
				$this->commonControls($count, $ins['actcnt'], $ins['inactcnt'], $totalcount);
			} else {
				$foundcount = ($totactivecount + $totinactivecount);
				$this->commonControls($count, $totactivecount, $totinactivecount, ($totactivecount + $totinactivecount));
			}
			
			$pdf_content.='<br/><br clear="all" />';
			
			
			
			
				
			$this->pstart 	= '';$this->last = '';$this->pend = '';$this->pages = '';
			
			$this->pstart 	= ($page-1) * $this->results_per_page + 1;
			$this->pend 	= $this->pstart + $this->results_per_page - 1;
			$this->pages 	= ceil($count / $this->results_per_page);
			$this->last 	= ($page * $this->results_per_page > $count) ? $count : $this->pend;

			if($count > $this->results_per_page) {
				if(isset($_POST['results']))
					$this->pagination($page, $count, $_POST['results'], $_POST['time'], 'normal', NULL);
				else 
					$this->pagination($page, $count, $_POST['params'], $_POST['leading'], 'normal', NULL);
			}
			$pdf_content.=$this->displayHeader();
			$header_details[] = $rowlabel;
			 
			if(isset($non_assoc_upm_params) && !empty($non_assoc_upm_params)) {
				$unmatched_upms_default_style = 'expand';
				$unmatched_upm_details[0] = $this->getNonAssocUpm($non_assoc_upm_params, '0');
			} 
			
			if($count > 0) {
				
				$pdf_content.=displayContent($this->displist, $this->{$this->type}, $this->edited, $this->time_machine, $this->pstart, $this->last, $this->phase_arr, 
				$fin_arr, $this->actfilterarr, $this->current_yr, $this->second_yr, $this->third_yr, $upmDetails, $header_details, $unmatched_upm_details, 
				$unmatched_upms_default_style);
				
			} else {
			
				if($unmatched_upm_details[0] != '') {
				
					$pdf_content.='<tr class="trialtitles">'
					. '<td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
					. 'style="border-bottom:0.5px solid blue;background-image: url(\'images/down.png\');background-repeat: no-repeat;background-position:left center;"'
					. ' onclick="sh(this,\'0\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . trim($header_details[0]) . '</td></tr>' . $unmatched_upm_details[0];
					
				} else {
					$pdf_content.='<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="notopbottomborder leftrightborderblue sectiontitles">' 
					. trim($header_details[0]) . '</td></tr>';
				}
				$pdf_content.='<tr><td colspan="' . getColspanforNAUpm($this->loggedIn) . '" class="norecord" align="left">No trials found.</td></tr>';
			}
			$pdf_content.='</table><br/>';
			
			
			
			$shownArr = array();
			if($count > 0) {
			
				$shownArr = $this->{$this->type};
				foreach($fin_arr as $key => &$value) {
				
					unset($value['edited']);
					unset($value['new']);
					unset($value['larvol_id']);
					unset($value['inactive_date']);
					unset($value['region']);
					
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
					unset($value['inactive_date']);
					unset($value['region']);
					
					foreach($value as $k => $v) {
						if(strpos($k, 'NCT/') !== FALSE) {
						
							$newkey = str_replace('NCT/','NCT.',$k);
							$value[$newkey] = $v;
							unset($value[$k]);

						}
					}
				}
				
				$this->downloadOptions($count, $foundcount, $shownArr, $fin_arr);
				$pdf_content.='<br/>';
			}
			
			//Expiry feature for new link method
			if(!empty($link_expiry_date)) {
				$link_expiry_date = array_unique(array_filter($link_expiry_date));
				usort($link_expiry_date, "cmpdate");
				if(!empty($link_expiry_date)) {
				
					if($this->loggedIn) {
						$pdf_content.='<span style="font-size:8pt;color:red;">Expires on: ' . $link_expiry_date[0]  . '</span>';
					}
					
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
							$query = "UPDATE `rpt_ott_upm` SET `expiry` = '" . date('Y-m-d',strtotime('+1 week',$now)) . "' WHERE id = '" . $ids[3] . "' ";
							$res = mysql_query($query) or tex('Bad SQL Query setting expiry date for upms' . "\n" . $query);
						}
						
					}
				}
			}
		}
	//print $pdf_content;
	
	require_once("dompdf_new/dompdf_config.inc.php");
spl_autoload_register('DOMPDF_autoload');  
$dompdf = new DOMPDF();
$dompdf->set_paper( 'letter', 'letter' ); 
$dompdf->load_html($pdf_content);
$dompdf->render();
ob_end_clean();

$dompdf->stream("Larvol PDF_". date('Y-m-d_H.i.s') .".pdf");
	}
	
	function displayHeader() {
	
		$pdf_content.='<table style="border-collapse:collapse;" width="100%" cellpadding="0" cellspacing="0" class="manage">'
			 . '<thead><tr>'
			 . (($this->loggedIn) ? '<th rowspan="2" >ID</th>' : '' )
			 . '<th cellpadding="0"
cellspacing="0" rowspan="2">Title</th>'
			 . '<th rowspan="2" title="Black: Actual&nbsp;&nbsp;Gray: Anticipated&nbsp;&nbsp;Red: Change greater than 20%">'
			 . '<a>N</a></th>'
			 . '<th rowspan="2" title="&quot;EU&quot; = European Union&nbsp;&quot;ROW&quot; = Rest of World">Region</th>'
			 . '<th rowspan="2">Interventions</th>'
			  . '<th rowspan="2" >Sponsor</th>'
			 . '<th rowspan="2">'
			 . '<a target="_self" >Status</a></th>'
			 . '<th rowspan="2">Conditions</th>'
			 . '<th rowspan="2" title="MM/YY">'
			 . '<a target="_self" >Start</a></th>'
			 . '<th rowspan="2" title="MM/YY">'
			 . '<a target="_self">End</a></th>'
			 . '<th rowspan="2">'
			 . '<a target="_self">Ph</a></th>'
			 . '<th rowspan="2">result</th>'
			 . '<th colspan="36" style="width:72px;"><span>&nbsp;</span></th>'
			 . '<th colspan="3" style="padding:0px; border-left:0px;" class="rightborder">&nbsp;</th></tr>'
			 . '<tr>';
		
		/*$pdf_content.='<th>';
		if(array_key_exists('en', $this->sortimg)) {
		
			$img = $this->sortimg['en'];
			$img_style = array_search('en-' . $img, $this->sortorder);
			$pdf_content.="<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		$pdf_content.='</th><th>';
		
		if(array_key_exists('os', $this->sortimg)) {
		
			$img = $this->sortimg['os'];
			$img_style = array_search('os-' . $img, $this->sortorder);
			$pdf_content.="<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		$pdf_content.='</th><th>';
		
		if(array_key_exists('sd', $this->sortimg)) {
		
			$img = $this->sortimg['sd'];
			$img_style = array_search('sd-' . $img, $this->sortorder);
			$pdf_content.="<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		$pdf_content.='</th><th>';
		
		if(array_key_exists('ed', $this->sortimg)) {
		
			$img = $this->sortimg['ed'];
			$img_style = array_search('ed-' . $img, $this->sortorder);
			$pdf_content.="<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		$pdf_content.='</th><th>';
		
		if(array_key_exists('ph', $this->sortimg)) {
		
			$img = $this->sortimg['ph'];
			$img_style = array_search('ph-' . $img, $this->sortorder);
			$pdf_content.="<img src='images/".$img.".png' ".$this->imgscale[$img_style]." border='0' alt='Sort' />";
		}
		$pdf_content.='</th>';*/
		$pdf_content.='<th colspan="12" style="width:24px;">' . $this->current_yr . '</th>'
			 . '<th colspan="12" style="width:24px;">' . $this->second_yr . '</th>'
			 . '<th colspan="12" style="width:24px;">' . $this->third_yr . '</th>'
			 . '<th colspan="3" class="rightborder">+</th></tr></thead>';
			 
	return $pdf_content;		 

	}
	
	function getNonAssocUpm($non_assoc_upm_params, $trialheader) {
		
		global $now;

		$upm_arr = array();$record_arr = array();$unmatched_upm_arr = array();$upm_string = '';
		$upm_arr = getNonAssocUpmRecords($non_assoc_upm_params);
		$record_arr = getUnmatchedUpmChanges($upm_arr, $this->time_machine, $this->edited);
		
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
						
			foreach($record_arr as $key => $val) {
			
				$title = '';$attr = '';$result_image = '';
				$class = 'class = "upms ' . $trialheader . '" ';
				$title_link_color = 'color:#000;';
				$date_style = 'color:gray;';
				$upm_title = 'title="' . htmlformat($val['event_description']) . '"';
				
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
				
				$upm_string .= '<tr ' . $class . ' style="background-color:#000;">';
				
				if($this->loggedIn) {
				
					if($val['new'] == 'y') {
						$title_link_color = 'color:#FF0000;';
						$title = ' title = "New record" ';
					}
					$upm_string .= '<td style="text-align:left;" ' . $title .
					 '><a style="' . $title_link_color . '" href="upm.php?search_id=' . $val['id'] . '">' . $val['id'] . '</a></td>';
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
				
				$upm_string .= '<td colspan="5" class="' . $row_type_one .  $attr . ' titleupm titleupmodd txtleft" ' . $title . '>';
				if($val['event_link'] != NULL && $val['event_link'] != '') {
					$upm_string .= '<a style="' . $title_link_color . '" href="' . $val['event_link'] . '">' . $val['event_description'] . '</a>';
				} else {
					$upm_string .= $val['event_description'];
				}
				$upm_string .= '</td>';
				
				$upm_string .= '<td class="' . $row_type_two . ' titleupmodd">';
				
				if($val['result_link'] != NULL && $val['result_link'] != '') {
					$upm_string .= 'Occurred';
				} else {
				
					if($val['end_date'] == NULL || $val['end_date'] == '' || $val['end_date'] == '0000-00-00') {
						$upm_string .= 'Cancelled';
					} else if($val['end_date'] < date('Y-m-d', $now)) {
						$upm_string .= 'Pending';
					} else if($val['end_date'] > date('Y-m-d', $now)) {
						$upm_string .= 'Upcoming';
					}
					
				}
				
				$upm_string .= '</td>';
				
				$title = '';$attr = '';	
				if(!empty($val['edited']) && $val['edited']['event_type'] != $val['event_type']) {
				
					$attr = ' highlight'; 
					if($val['edited']['event_type'] != '' && $val['edited']['event_type'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['event_type'] . '" '; 
					else
						$title = ' title="No Previous value" ';
						
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
				}
				$upm_string .= '<td class="' . $row_type_two . $attr . ' titleupmodd" ' . $title 
								. '>' . $val['event_type'] . ' Milestone</td>';
				
				$title = '';$attr = '';	
				if(!empty($val['edited']) && $val['edited']['start_date'] != $val['start_date']){
				
					$attr = ' highlight';$date_style = 'color:#973535;'; 
					if($val['edited']['start_date'] != '' && $val['edited']['start_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['start_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
						
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				if(!empty($val['edited']) && $val['edited']['start_date_type'] != $val['start_date_type']){
				
					$attr = ' highlight';$date_style = 'color:#973535;';
					if($val['edited']['start_date_type'] != '' && $val['edited']['start_date_type'] != NULL) {
						$title = ' title="Previous value: ' . 
						(($val['edited']['start_date'] != $val['start_date']) ? $val['edited']['start_date'] : '' ) 
						. ' ' .$val['edited']['start_date_type'] . '" '; 
					} else {
						$title = ' title="No Previous value" ';
					}
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
								
				$upm_string .= '<td  class="' . $row_type_two . $attr . ' titleupmodd txtleft" ' . $title . '>';
				if($val['start_date_type'] == 'anticipated') {
				$upm_string .= '<span style="font-weight:bold;' . $date_style . '">' 
				. (($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' )   
				. '</span>';
				} else {
					$upm_string .= 
					(($val['start_date'] != '' && $val['start_date'] != NULL && $val['start_date'] != '0000-00-00') ? date('m/y',strtotime($val['start_date'])) : '' );
				}
				
				$upm_string .= '</td>';
				
				$title = '';$attr = '';	
				if(!empty($val['edited']) && $val['edited']['end_date'] != $val['end_date']){
				
					$attr = ' highlight';$date_style = 'color:#973535;';
					if($val['edited']['end_date'] != '' && $val['edited']['end_date'] != NULL)
						$title = ' title="Previous value: '. $val['edited']['end_date'] . '" '; 
					else 
						$title = ' title="No Previous value" ';
				} else if($val['new'] == 'y') {
					$title = ' title = "New record" ';
					$date_style = 'color:#973535;'; 
				}
				if(!empty($val['edited']) && $val['edited']['end_date_type'] != $val['end_date_type']){
				
					$attr = ' highlight';$date_style = 'color:#973535;'; 
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
				
				$upm_string .= '<td class="' . $row_type_two . $attr . ' titleupmodd txtleft" ' . $title . '>';
				if($val['end_date_type'] == 'anticipated') {
					$upm_string .= '<span style="font-weight:bold;' . $date_style . '">' 
					. (($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '' ) 
					. '</span>';
				} else {
					$upm_string .=  
					(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') ? date('m/y',strtotime($val['end_date'])) : '');
				}	
				
				$upm_string .= '</td><td class="titleupmodd"><span class="rowcollapse"></span></td>';
				$upm_string .= '<td class="titleupmodd"><span class="rowcollapse">';
				
				if(!empty($val['edited']) && ($val['result_link'] != $val['edited']['result_link'])) {
					if($val['result_link'] != '' && $val['result_link'] != NULL) {
						$result_image = (($val['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
						$upm_string .= '<span ' . $upm_title . '><a href="' . $val['result_link'] . '" style="color:#000;">'
						. '<img src="images/red-' . $result_image . '.png" alt="' . $result_image . '" style="padding-top: 3px; width: 8px; height: 8px;" border="0" /></a></span>';
					}
				} else {
					if($val['result_link'] != '' && $val['result_link'] != NULL) {
						$result_image = (($val['event_type'] == 'Clinical Data') ? 'diamond' : 'checkmark' );
						$upm_string .= '<span ' . $upm_title . '><a href="' . $val['result_link'] . '" style="color:#000;">'
						. '<img src="images/black-' . $result_image . '.png" alt="' . $result_image . '" style="padding-top: 3px; width: 8px; height: 8px;" border="0" /></a></span>';
					}
				}
				
				if(($val['end_date'] != '' && $val['end_date'] != NULL && $val['end_date'] != '0000-00-00') && 
				($val['end_date'] < date('Y-m-d', $now)) && ($val['result_link'] == NULL || $val['result_link'] == '')){
						$upm_string .= '<span ' . $upm_title . '><img src="images/hourglass.png" style="padding-top: 3px; width: 8px; height: 8px;" alt="hourglass" border="0" /></span>';
				}
				$upm_string .= '</span></td>';
				
				$upm_string .= getUPMChart(date('m',strtotime($val['start_date'])), date('Y',strtotime($val['start_date'])), 
				date('m',strtotime($val['end_date'])), date('Y',strtotime($val['end_date'])), $this->current_yr, $this->second_yr, $this->third_yr, 
				$val['start_date'], $val['end_date'], $val['event_link'], $upm_title);
		
		
				$upm_string .= '</tr>';
				
				$cntr++;
			}
		} 
		
		return $upm_string;
	}
	
	function downloadOptions($showncount, $foundcount, $shownlist, $foundlist) {

		
	}
	
}

function getColspanforNAUpm($loggedIn) {
	return $colspan = (($loggedIn) ? 51 : 50 );
	
}

function displayContent($fieldlist, $type_arr, $edited, $gentime, $start, $last, $phase_arr, $fin_arr, $actfilterarr, $current_yr, $second_yr, $third_yr, $upmDetails, $header_details, $unmatched_upm_details, $unmatched_upms_default_style) 
{	
	$db = new DatabaseManager();
	$previous_section = '';
	$start = $start -1;
	for($i=$start;$i<$last;$i++) 
	{
			
		if(isset($type_arr[$i]['NCT/nct_id'])) {
		
			//displaying section headers
			if($type_arr[$i]['section'] !== $previous_section) {
				
				if($unmatched_upm_details[$type_arr[$i]['section']] != '') {
				
					if($unmatched_upms_default_style == 'expand')
						$image = 'down';
					else
						$image = 'up';
				
				$pdf_content.='<tr class="trialtitles">'
				. '<td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
				. 'style="border-bottom:0.5px solid blue;background-position:left center;"'
				. ' onclick="sh(this,\'' . $type_arr[$i]['section'] . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . trim($header_details[$type_arr[$i]['section']]) 
				. '</td></tr>' . $unmatched_upm_details[$type_arr[$i]['section']];
				
				} else {
					$pdf_content.= '<tr><td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="notopbottomborder leftrightborderblue sectiontitles">' 
						. trim($header_details[$type_arr[$i]['section']]) . '</td></tr>';
				}
			}
		
			$rowspan = 1;
			$nctid =  $type_arr[$i]['NCT/nct_id'];
			
			///Below line is added just to display border at the footer of PDF table
			$pdf_content.= '<tr><td colspan="' . getColspanforNAUpm($db->loggedIn()) . '"></td></tr>';
						
			$ph = str_replace('Phase ', '', trim($type_arr[$i]['NCT/phase']));
			
			$start_month = date('m',strtotime($type_arr[$i]['NCT/start_date']));
			$start_year = date('Y',strtotime($type_arr[$i]['NCT/start_date']));
			$end_month = date('m',strtotime($type_arr[$i]["inactive_date"]));
			$end_year = date('Y',strtotime($type_arr[$i]["inactive_date"]));
		
			$enroll_style = 'color:gray;';
			$title_link_color = '#000000;';
			
			$attr = ' ';
			if(isset($fin_arr[$nctid]['edited']) && in_array('NCT/brief_title',$fin_arr[$nctid]['edited'])) {
				$attr = ' highlight" title="' . $fin_arr[$nctid]['edited']['NCT/brief_title'];
				$title_link_color = '#FF0000;';
			} else if($fin_arr[$nctid]['new'] == 'y') {
				$attr = '" title="New record';
				$title_link_color = '#FF0000;';
			}
			
			if(isset($upmDetails[$nctid])) {
				
				$rowspan = count($upmDetails[$nctid])+1;
			}
		
			if($i%2 == 1) {
				$row_type_one = 'alttitle';
			} else {
				$row_type_one = 'title';
			}
		
			$pdf_content.= '<tr ' . (($fin_arr[$nctid]['new'] == 'y') ? 'class="newtrial" ' : ''). ' >';
			
			//dispalying NCT Id for logged in users
			if($db->loggedIn()) {
				$pdf_content.= '<td class="' . $row_type_one . '" rowspan="' . $rowspan . '" ' . (($fin_arr[$nctid]['new'] == 'y') ? 'title="New record"' : '') 
				. ' ><a style="color:' . $title_link_color . '" href="http://clinicaltrials.gov/ct2/show/' . padnct($nctid) . '">' 
				. $type_arr[$i]['NCT/nct_id'] . '</a></td>';
			}
				
			$pdf_content.='<td rowspan="' . $rowspan . '" class="' . $row_type_one . ' ' . $attr . '">' 
				. '<a style="color:' . $title_link_color 
				. '" href="http://clinicaltrials.gov/ct2/show/' . padnct($nctid) . '">';
		
			if(isset($type_arr[$i]['NCT/acronym']) && $type_arr[$i]['NCT/acronym'] != '') {
				$pdf_content.= '<b>' . htmlformat($type_arr[$i]['NCT/acronym']) . '</b>&nbsp;' . htmlformat($type_arr[$i]['NCT/brief_title']);
						
			} else {
				$pdf_content.= htmlformat($type_arr[$i]['NCT/brief_title']);
			}
					
			$pdf_content.='</a></td>';
			
			foreach($fieldlist as $k => $v) {
			
				$attr = ' ';
				$val = htmlformat($type_arr[$i][$v]);
				if($v == "NCT/enrollment"){
				
					if(isset($fin_arr[$nctid]['edited']) && in_array($v,$fin_arr[$nctid]['edited']) 
					&& (getDifference(substr($fin_arr[$nctid]['edited']['NCT/enrollment'],16), $val))) {
					
						$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v];
						$enroll_style = 'color:#973535;';
						
					}	else if($fin_arr[$nctid]['new'] == 'y') {
					
						$attr = '" title="New record';
						$enroll_style = 'color:#973535;';
					}
					$pdf_content.='<td nowrap="nowrap" rowspan="' . $rowspan . '" class="' . $row_type_one 
					. $attr . '">';
					
						if($type_arr[$i]["NCT/enrollment_type"] != '') {
						
							if($type_arr[$i]["NCT/enrollment_type"] == 'Anticipated') { 
								$pdf_content.='<span style="font-weight:bold;' . $enroll_style . '">' . $val . '</span>';
								
							} else if($type_arr[$i]["NCT/enrollment_type"] == 'Actual') {
								$pdf_content.=$val;
								
							} else { 
								$pdf_content.=$val . ' (' . $type_arr[$i]["NCT/enrollment_type"] . ')';
							}
						} else {
							$pdf_content.=$val;
						}
					$pdf_content.='</td>';  
					
				} else if($v == "NCT/start_date") {
				
					if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
						$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v] ;
					} else if($fin_arr[$nctid]['new'] == 'y') {
						$attr = '" title="New record';
					}
					
					$pdf_content.='<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '" >'; 
					if($type_arr[$i]["NCT/start_date"] != '' && $type_arr[$i]["NCT/start_date"] != NULL && $type_arr[$i]["NCT/start_date"] != '0000-00-00') {
						$pdf_content.=date('m/y',strtotime($type_arr[$i]["NCT/start_date"]));
					} else {
						$pdf_content.='&nbsp;';
					}
					  
					$pdf_content.='</td>';
					
					$attr = '';
					if($fin_arr[$nctid]['new'] == 'y') 
						$attr = ' title="New record" ';
						
					$pdf_content.='<td rowspan="' . $rowspan . '" class="' . $row_type_one . '" ' . $attr . '>';
					if($type_arr[$i]["inactive_date"] != '' && $type_arr[$i]["inactive_date"] != NULL && $type_arr[$i]["inactive_date"] != '0000-00-00') {
						$pdf_content.=date('m/y',strtotime($type_arr[$i]["inactive_date"]));
					} else {
						$pdf_content.='&nbsp;';
					}
					$pdf_content.='</td>';
					
				} else if($v == "NCT/overall_status") {
			
					if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
						$attr = 'class="highlight ' . $row_type_one . ' " title="' . $fin_arr[$nctid]['edited'][$v] . '" ';
					} else if($fin_arr[$nctid]['new'] == 'y') {
						$attr = 'title="New record" class="' . $row_type_one . '"' ;
					} else {
						$attr = 'class="' . $row_type_one . '"';
					}
						
					$pdf_content.='<td ' . $attr . ' rowspan="' . $rowspan . '">'  
						.'' . $val . '</td>';
				
				
				} else if($v == "NCT/condition") {
				
					if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
						$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v];
					} else if($fin_arr[$nctid]['new'] == 'y') {
						$attr = '" title="New record';
					}
					
					$pdf_content.='<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '">'
						. '' . implode(", ",array_unique(explode(",", str_replace(", ",",",$val)))) . '</td>';
						
				
				} else if($v == "NCT/intervention_name") {
				
					if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])){
						$attr = ' highlight" title="' . $fin_arr[$nctid]['edited'][$v];
					} else if($fin_arr[$nctid]['new'] == 'y') {
						$attr = '" title="New record';
					}
					
					$pdf_content.='<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '">'
						. '' . implode(", ",array_unique(explode(",", str_replace(", ",",",$val)))) . '</td>';
					
				
				} else if($v == "NCT/phase") {
				
					if(isset($fin_arr[$nctid]['edited']) && in_array($v, $fin_arr[$nctid]['edited'])) {
						$attr = 'class="highlight" title="' . $fin_arr[$nctid]['edited'][$v] . '" ';
					} else if($fin_arr[$nctid]['new'] == 'y') {
						$attr = 'title="New record"';
					}
					
					if($ph != '' && $ph !== NULL) {
						$phase = $ph;
						$ph_color = $phase_arr[$ph];
					} else {
						$phase = 'N/A';
						$ph_color = $phase_arr['N/A'];
					}
					$pdf_content.='<td rowspan="' . $rowspan . '" style="background-color:' . $ph_color . ';" ' . $attr . '>'
						. '' . $phase . '</td>';
				
				
				} else if($v == "NCT/lead_sponsor") { 
				
				
					if(isset($fin_arr[$nctid]['edited']) && (in_array($v, $fin_arr[$nctid]['edited']) 
						|| in_array('NCT/collaborator', $fin_arr[$nctid]['edited']))) {
						
						$attr = ' highlight" title="';
						if(in_array($v, $fin_arr[$nctid]['edited']))
							$attr .= $fin_arr[$nctid]['edited'][$v] . ' ';
						
						if(in_array('NCT/collaborator', $fin_arr[$nctid]['edited'])) {
							$attr .= $fin_arr[$nctid]['edited']['NCT/collaborator'];
							$enroll_style = 'color:#973535;';
						}
						$attr .= '';
					} else if($fin_arr[$nctid]['new'] == 'y') {
						$attr = '" title="New record';
	
					}
					$pdf_content.='<td rowspan="' . $rowspan . '" class="' . $row_type_one . $attr . '">'
						. '' . $val . ' <span style="' . $enroll_style . '"> ' 
						. $type_arr[$i]["NCT/collaborator"] . ' </span></td>';
					
				} else if($v == 'region') {
				
					if($fin_arr[$nctid]['new'] == 'y') 
						$attr = 'title="New record"';
					
					$pdf_content.='<td class="' . $row_type_one . '" rowspan="' . $rowspan . '" ' . $attr . '>'
					. '' . $val . '</td>';
				} 
			}
			
			$pdf_content.='<td>&nbsp;</td>';
			
			//rendering project completion chart
			$pdf_content.= getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, 
			$ph_color, $type_arr[$i]['NCT/start_date'], $type_arr[$i]['inactive_date']);
			
			$pdf_content.='</tr>';
		
			if(isset($upmDetails[$nctid]) && !empty($upmDetails[$nctid])) {
				
				foreach($upmDetails[$nctid] as $k => $v) { 
				
					$str = '';$diamond = '';$result_image = '';
	
					$st_month = date('m',strtotime($v[2]));
					$st_year = date('Y',strtotime($v[2]));
					$ed_month = date('m',strtotime($v[3]));
					$ed_year = date('Y',strtotime($v[3]));
					$upm_link = $v[1];
					$upm_result_link = $v[4];
					$upm_title = 'title="' . htmlformat($v[0]) . '"';
					
					$pdf_content.='<tr>';
					
					//rendering diamonds in case of end date is prior to the current year
					$pdf_content.='<td valign="middle" style="text-align:center;' . (($k < count($upmDetails[$nctid])-1) ? 'border-bottom:0;' : '' ) . '"><div  align="center" valign="middle" style="page-break-before: avoid;">';
					
					if(!empty($upmDetails[$nctid][$k]['edited']) && ($v[4] != $upmDetails[$nctid][$k]['edited'][3])) {
					
						if($upm_result_link != '' && $upm_result_link != NULL) {
						
							$result_image = (($v[5] == 'Clinical Data') ? 'diamond' : 'checkmark' );
							$pdf_content.='<span ' . $upm_title . '><a href="' . $upm_result_link . '" style="color:#000;">'
							. '<img src="images/red-' . $result_image . '.png" alt="' . $result_image . '" style="width: 8px; height: 8px; padding-top: 3px;" border="0" /></a></span>';
						}
					} else if($upmDetails[$nctid][$k]['new'] == 'y') {
					
						$result_image = (($v[5] == 'Clinical Data') ? 'diamond' : 'checkmark' );
						$pdf_content.='<span ' . $upm_title . '>';
						if(upm_result_link != '' && $upm_result_link != NULL) {
						
							$pdf_content.='<a href="' . $upm_result_link . '" style="color:#000;"><img src="images/red-' . $result_image . '.png" alt="' 
							. $result_image . '" style="padding-top: 3px; width: 8px; height: 8px;" border="0" /></a>';
							
						} else {
							$pdf_content.='<img src="images/red-' . $result_image . '.png" alt="' . $result_image . '" style="padding-top: 3px; width: 8px; height: 8px;" border="0" />';
						}
						$pdf_content.='</span>';
							
					} else {
					
						if($upm_result_link != '' && $upm_result_link != NULL) {
						
							$result_image = (($v[5] == 'Clinical Data') ? 'diamond' : 'checkmark' );
							$pdf_content.='<span ' . $upm_title . '><a href="' . $upm_result_link . '" style="color:#000;">'
							. '<img src="images/black-' . $result_image . '.png"  style="padding-top: 3px; width: 8px; height: 8px;" alt="' . $result_image . '" border="0" /></a></span>';
						}
					}
					
					if(($v[3] != '' && $v[3] != NULL && $v[3] != '0000-00-00') && ($v[3] < date('Y-m-d')) && ($upm_result_link == NULL || $upm_result_link == '')){
						$pdf_content.='<span ' . $upm_title . '><img src="images/hourglass.png" style="padding-top: 3px; width: 8px; height: 8px;" alt="hourglass" border="0" /></span>';
					}
					$pdf_content.='</div></td>';
					
					//rendering upm (upcoming project completion) chart
					$pdf_content.= getUPMChart($st_month, $st_year, $ed_month, $ed_year, $current_yr, $second_yr, $third_yr, $v[2], 
					$v[3], $upm_link, $upm_title);
					$pdf_content.='</tr>';
				}
			}
		
		} else {
			
			if($unmatched_upm_details[$type_arr[$i]['section']] != '') {
				
					if($unmatched_upms_default_style == 'expand')
						$image = 'down';
					else
						$image = 'up';
				
				$pdf_content.='<tr class="trialtitles">'
				. '<td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="upmpointer notopbottomborder leftrightborderblue sectiontitles" '
				. 'style="border-bottom:0.5px solid blue;background-position:left center;"'
				. ' onclick="sh(this,\'' . $type_arr[$i]['section'] . '\');">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . trim($header_details[$type_arr[$i]['section']]) 
				. '</td></tr>' . $unmatched_upm_details[$type_arr[$i]['section']];
				
				if(!isset($_POST['chkOnlyUpdated']))
				$pdf_content.='<tr><td colspan="' . getColspanforNAUpm($db->loggedIn()) . '" class="norecord" align="left">No trials found.</td></tr>'; 
				
			} /*else {
				echo '<tr><td colspan="50" class="notopbottomborder leftrightborderblue sectiontitles">' 
					. trim($header_details[$type_arr[$i]['section']]) . '</td></tr>';
			}*/
		
			
		}
		$previous_section = $type_arr[$i]['section'];
	}
return $pdf_content;
}

//get difference between two dates in months
function getColspan($start_dt, $end_dt) {
	
	$diff = round((strtotime($end_dt)-strtotime($start_dt))/2628000);
	return $diff;

}

//calculating the project completion chart in which the year ranges from the current year and next-to-next year
function getCompletionChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $bg_color, $start_date, $end_date){

	$attr_two = 'class="rightborder"';
	if(($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') && ($end_date == '' || $end_date == NULL || $end_date == '0000-00-00')) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	

	} else if($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') {
	
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';
		}
	} else if($end_date == '' || $end_date == NULL || $end_date == '0000-00-00') {
	
		$st = $start_month-1;
		if($start_year < $current_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
						
		} else if($start_year == $current_yr) { 
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' 
				. $attr_two . '>&nbsp;</td>';	
					

		} else if($start_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'

				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
		} else if($start_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '')
				. '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '')
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';	
				
		} else if($start_year > $third_yr){
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';
		}
			
	} else if($end_date < $start_date) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
	} else if($start_year < $current_yr) {
		
		if($end_year < $current_yr) {
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
		  
		} else if($end_year == $current_yr) {
		 
			if($end_month == 12) {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="12">&nbsp;</td>' 
						. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			} else { 
				$value = '<td style="background-color:' . $bg_color . ';" colspan="' . $end_month . '">&nbsp;</td>'
						. '<td colspan="' . (12-$end_month) . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			}
		} else if($end_year == $second_yr) { 
		 
			if($end_month == 12) {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="24">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			} else {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="' . (12+$end_month) . '">&nbsp;</td>'
						. '<td colspan="' . (12-$end_month) . '">&nbsp;</td><td colspan="12">&nbsp;</td>'
						. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			}
	
		} else if($end_year == $third_yr) { 
		
			if($end_month == 12) {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="36">&nbsp;</td>'
					. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
			} else {
				$value = '<td style="background-color:' . $bg_color . ';" colspan="' . (24+$end_month) . '">&nbsp;</td>'
				. '<td colspan="' . (12-$end_month) . '">&nbsp;</td>'
				. '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';

			}
		 
		} else if($end_year > $third_yr) { 
			$value = '<td colspan="39" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}	
	
	} else if($start_year == $current_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '">&nbsp;</td>' : '');
			}
			$value .= '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
		
		} else if($end_year == $second_yr) { 
		 
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
		
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');			
			}
			$value .= '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
	
		} else if($end_year == $third_yr) {
				
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
						. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '">&nbsp;</td>' : '');
			}
			$value .= '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
	
		} else if($end_year > $third_yr){
		
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(39 - $st) . '" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}
		
	} else if($start_year == $second_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $second_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">' . '&nbsp;</td>' : '');
				
				if($val != 0) { 
					$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
							. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
				} else { 
					$value .= '<td style="background-color:' . $bg_color . ';"></td>'
							. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$value .= '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';		
		
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				if($val != 0) {
					$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
							. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '">&nbsp;</td>' : '');
				} else {
					$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
							. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '">&nbsp;</td>' : '');
				}
				$value .= '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';

		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(27 - $st) . '" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
		}
		
	} else if($start_year == $third_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;	
		if($end_year == $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>'
				. '<td colspan="12">&nbsp;</td>'
				. (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
				
			if($val != 0) {
				$value .= '<td style="background-color:' . $bg_color . ';" colspan="' . $val . '">&nbsp;</td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '">&nbsp;</td>' : '');
			} else {
				$value .= '<td style="background-color:' . $bg_color . ';">&nbsp;</td>'
					. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '">&nbsp;</td>' : '');
			}
			$value .= '<td colspan="3" ' . $attr_two . '>&nbsp;</td>';
		
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(15 - $st) . '" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';		
						
		}
			
	} else if($start_year > $third_yr) {
	
			$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
				. '<td colspan="3" style="background-color:' . $bg_color . ';" ' . $attr_two . '>&nbsp;</td>';	
			
	} 
	return $value;
}

function getUPMChart($start_month, $start_year, $end_month, $end_year, $current_yr, $second_yr, $third_yr, $start_date, $end_date, $upm_link, $upm_title)
{
	
	$attr_two = 'class="rightborder"';
	$background_color = 'background-color:#9966FF;';
	
	
	if(($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') && ($end_date == '' || $end_date == NULL || $end_date == '0000-00-00')) {
	
		$value = '<td colspan="12"><span ' . $upm_title . '>' 
			.(( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
			. '<td colspan="12"><span ' . $upm_title . '>'
			. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
			. '<td colspan="12"><span ' . $upm_title . '>' . (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;')  
			. '</span></td>'
			. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>' 
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	

	} else if($start_date == '' || $start_date == NULL || $start_date == '0000-00-00') {
	
		$st = $end_month-1;
		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
						
		} else if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td style="' . $background_color . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
					
		} else if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>' 
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td style="' . $background_color . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
					
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td style="' . $background_color . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
				
		} else if($end_year > $third_yr){
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
		}
	} else if($end_date == '' || $end_date == NULL || $end_date == '0000-00-00') {
	
		$st = $start_month-1;
		if($start_year < $current_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
						
		} else if($start_year == $current_yr) { 
			
			$value = (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td style="' . $background_color . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
					
		} else if($start_year == $second_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td style="' . $background_color . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
					
		} else if($start_year == $third_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
			 	. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td style="' . $background_color . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
				
		} else if($start_year > $third_yr){
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
		}
			
	} else if($end_date < $start_date) {
	
		$value = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>'
					. '<td colspan="12">&nbsp;</td><td colspan="3" ' . $attr_two . '>&nbsp;</td>';
					
	} else if($start_year < $current_yr) {

		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;

		if($end_year < $current_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
			. '<td colspan="12"><span ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
			. '<td colspan="12"><span ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
			. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
			. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
		  
		} else if($end_year == $current_yr) { 
		
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="' . $end_month . '">' 
				. '<span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
				
			} else { 
			
				$value = '<td style="' . $background_color . '" colspan="' . $end_month . '">' . '<span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="' . (12-$end_month) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
				
			}
		} else if($end_year == $second_yr) { 
		 
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="24">' . '<span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
				
			} else {
			
				$value = '<td style="' . $background_color . '" colspan="' . (12+$end_month) . '">' . '<span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="' . (12-$end_month) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;'). '</span></td>';
				
			}
	
		} else if($end_year == $third_yr) { 
			
			if($end_month == 12) {
			
				$value = '<td style="' . $background_color . '" colspan="36">' . '<span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
				
			} else {
			
				$value = '<td style="' . $background_color . '" colspan="' . (24+$end_month) . '" ' . $class . '>' 
				. '<span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="' . (12-$end_month) . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'

				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
			}
		 
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="39" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';		
		}	
	
	} else if($start_year == $current_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $current_yr) {
			
			$value = (($st != 0) ? '<td colspan="' . $st . '" ><span ' . $upm_title . '>'
			. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">'
						. '<span ' . $upm_title . '>'
						. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((12 - ($st+$val)) != 0) ? '<td colspan="' .(12 - ($st+$val)) . '"  style="' . $lineheight . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">'
						. '<span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((12 - ($st+1)) != 0) ? '<td colspan="' .(12 - ($st+1)) . '"  style="' . $lineheight . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');			
			}
			
			$value .= '<td colspan="12"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
					. '<td colspan="12"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
					. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
		
		} else if($end_year == $second_yr) { 
		 
			$value = (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">'
						. '<span ' . $upm_title .' >'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<span ' . $upm_title .' >'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');			
			}
			
			$value .= '<td colspan="12"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
					. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
	
		} else if($end_year == $third_yr) {
				
			$value = (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<span ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((36 - ($val+$st)) != 0) ? '<td colspan="' .(36 - ($val+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '') ;
			} else {
				$value .= '<td style="' . $background_color . '">'
						. '<span ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((36 - (1+$st)) != 0) ? '<td colspan="' .(36 - (1+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '') ;			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
	
		} else if($end_year > $third_yr){
		
			$value = (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(39 - $st) . '" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';		
		}
		
	} else if($start_year == $second_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;
		if($end_year == $second_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>' 
					. (( $upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			}
			
			$value .= '<td colspan="12"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
					. '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';		
		
		} else if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
					. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
					. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
					
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<span ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((24 - ($val+$st)) != 0) ? '<td colspan="' .(24 - ($val+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<span ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((24 - (1+$st)) != 0) ? '<td colspan="' .(24 - (1+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');			
			}
			$value .= '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';

		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12">&nbsp;</td>' . (($st != 0) ? '<td colspan="' . $st . '">&nbsp;</td>' : '');
			$value .= '<td colspan="' .(27 - $st) . '" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';		
		}
		
	} else if($start_year == $third_yr) {
	
		$val = getColspan($start_date, $end_date);
		$st = $start_month-1;	
		if($end_year == $third_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
				
			if($val != 0) {
				$value .= '<td style="' . $background_color . '" colspan="' . $val . '">' . '<span ' . $upm_title .'>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((12 - ($val+$st)) != 0) ? '<td colspan="' .(12 - ($val+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');
			} else {
				$value .= '<td style="' . $background_color . '">' . '<span ' . $upm_title .'>' 
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
						. (((12 - (1+$st)) != 0) ? '<td colspan="' .(12 - (1+$st)) . '"><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '');			
			}
			
			$value .= '<td colspan="3" ' . $attr_two . '><span ' . $upm_title . '>'
						. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
		
		} else if($end_year > $third_yr) {
		
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' 
				. (($st != 0) ? '<td colspan="' . $st . '"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>' : '')
				. '<td colspan="' . (15 - $st) . '" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';
		
		}
			
	} else if($start_year > $third_yr) {
	
			$value = '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="12"><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>'
				. '<td colspan="3" style="' . $background_color . '" ' . $attr_two . '><span ' . $upm_title . '>'
				. (($upm_link != '' &&  $upm_link != NULL) ? '<a href="' . $upm_link . '">&nbsp;</a>' : '&nbsp;') . '</span></td>';	
				
	}
	return $value;	
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

//Get html content by passing through htmlspecialchars
function htmlformat($str)
{
	$str=fix_special_chars($str);
	return htmlspecialchars($str);
}

//getting corresponding UPM details for each of the trials
function getCorrespondingUPM($trial_id, $time, $edited) {
	
	$upm = array();
	$values = array();
					
	$result = mysql_query("SELECT id, event_type, corresponding_trial, event_description, event_link, result_link, start_date, end_date 
					FROM upm WHERE corresponding_trial = '" . $trial_id . "' ");
	
	$i = 0;			
	while($row = mysql_fetch_assoc($result)) {
	
		$upm[$i] = array($row['event_description'], $row['event_link'], $row['start_date'], $row['end_date'], $row['result_link'],$row['event_type'],);
		
		//Query for checking updates for upms.
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $row['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$upm[$i]['edited'] = array();
		$upm[$i]['new'] = 'n';
		while($arr = mysql_fetch_assoc($res)) {
			$upm[$i]['edited'] = array($arr['event_type'], $arr['event_description'], $arr['event_link'], $arr['result_link'], 
									$arr['start_date'], $arr['start_date_type'], $arr['end_date'], $arr['end_date_type'],);
		}
		
		$query = " SELECT u.id FROM `upm` u LEFT JOIN `upm_history` uh ON u.`id` = uh.`id` WHERE u.`id` = '" . $row['id'] . "' AND u.`last_update` < '" 
				. date('Y-m-d',$time) . "' AND u.`last_update` >=  '" . date('Y-m-d',strtotime($edited,$time)) . "' AND uh.`id` IS NULL ";
		
		$ress = mysql_query($query);
		if(mysql_num_rows($ress) != 0)
			$upm[$i]['new'] = 'y';
			
		$i++;
	}
	return $upm;
}

//get records for non associated upms
function getNonAssocUpmRecords($non_assoc_upm_params) {
	
	$where = '';$upms = array();$i = 0;
	foreach($non_assoc_upm_params as $key => $val){
		$where .= textEqual('`search_name`',$val) . ' OR ';
	}
	
	$result = mysql_query("SELECT `id` FROM `products` WHERE ( " . substr($where,0,-4) . " ) ");
	if(mysql_num_rows($result) > 0) {
	
		while($rows = mysql_fetch_assoc($result)) {
	

		$sql = "SELECT `id`, `event_description`, `event_link`, `result_link`, `event_type`, `start_date`,
				`start_date_type`, `end_date`, `end_date_type` FROM `upm` WHERE `corresponding_trial` IS NULL AND `product` = '" . $rows['id'] 
				. "' ORDER BY `end_date` ASC ";
		$res = mysql_query($sql)  or tex('Bad SQL query getting unmatched upms ' . $sql);
		
		if(mysql_num_rows($res) > 0) {
		
			while($row = mysql_fetch_assoc($res)) { 
			
				$upms[$i]['id'] = $row['id'];
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

function getUnmatchedUpmChanges($record_arr, $time, $edited) {

	foreach($record_arr as $key => $value) {
	
		$sql = "SELECT `id`, `event_type`, `event_description`, `event_link`, `result_link`, `start_date`, `start_date_type`, `end_date`, `end_date_type` "
				. " FROM `upm_history` WHERE `id` = '" . $value['id'] . "' AND (`superceded` < '" . date('Y-m-d',$time) . "' AND `superceded` >= '" 
				. date('Y-m-d',strtotime($edited,$time)) . "') ORDER BY `superceded` DESC LIMIT 0,1 ";
		$res = mysql_query($sql);
		
		$record_arr[$key]['edited'] = array();
		$record_arr[$key]['new'] = 'n';
		
		if(mysql_num_rows($res) > 0) {
			while($row = mysql_fetch_assoc($res)) {
			
				$record_arr[$key]['edited']['id'] = $row['id'];
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

function getDifference($value_one, $value_two) {

	$diff = abs(($value_one - $value_two) / $value_one * 100);
	$diff = round($diff);
	if($diff > 20)
		return true;
	else
		return false;
}
?> 
</body>
</html>