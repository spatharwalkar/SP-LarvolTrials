<?php 
require_once('db.php');
if((!$db->loggedIn() || !isset($_GET['id'])) && !isset($_GET['noheaders']))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('include.search.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

$id = mysql_real_escape_string($_GET['id']);
if(!is_numeric($id)) tex('non-numeric id!');

//get report name
$query = 'SELECT name,time,edited,output_template FROM rpt_trial_tracker WHERE id=' . $id . ' LIMIT 1';
$resu = mysql_query($query) or tex('Bad SQL query getting report name');
$resu = mysql_fetch_array($resu) or tex('Report not found.');

$name 	= $resu['name'];
$time	= 'today';
$edited	= 'today';
$type 	= $resu['output_template'];

if (trim($resu['time'])!=''){
	$time=$resu['time'];
}
if (trim($resu['edited'])!=''){
	$edited=$resu['edited'];
}
if(strlen($name) == 0) $name = 'Report ' . $id;

$query = 'SELECT * FROM rpt_trial_tracker_trials WHERE report=' . $id;
$res = mysql_query($query) or die('Bad SQL query getting report trials');
$trials = array();
while($trial = mysql_fetch_array($res))
{echo $trial['nctid'];
	$nct = getNCT($trial['nctid'],$time,$edited);
	if (!is_array($nct)) { 
		$nct=array();
		$trial['NCT/intervention_name'] = '(study not in database)';
	}
	$trials[$trial['nctid']] = array_merge($nct, $trial);
}

if($type == 'Color A') {
		$out = "<head>
				<style type='text/css'>
				.manage {color:#000080; border-top: 1px solid #000080; color:#000080;color:#000; 
						border-left: 1px solid #000080; font-family:Calibri; mso-fareast-font-family:Calibri;}
				.manage th {color:#000080; border-right: 1px solid #000080;border-bottom: 1px solid #000080;}
				.manage td {color:#000080;color:#000; border-right: 1px solid #000080;border-bottom: 1px solid #000080;}
				</style>
				</head>";
				
	$out .= '<table border="0" width="100%" cellpadding="5" cellspacing="0" class="manage">'
		. '<tr><th rowspan="2" width="2%" nowrap="nowrap">Tumor Type</th>'
		. '<th rowspan="2" width="5%" nowrap="nowrap">Patient Population<br/>(linked to details)</th>'
		. '<th rowspan="2" width="25%" nowrap="nowrap">Trial Details</th>'
		. '<th rowspan="2" width="8%">Sponsor</th>'
		. '<th rowspan="2" width="3%">Size</th>'
		. '<th rowspan="2" width="15%">Start-End</th>'
		. '<th rowspan="2" width="10%" nowrap="nowrap">Status<br/><span style="color:#ff0000;">[Weekly Update]</span></th>'
		. '<th rowspan="2" width="5%">Ph</th>'
		. '<th width="30%" nowrap="nowrap" colspan="36">Projected Completion</th></tr>'
		. '<tr><th width="10%" colspan="12">' .(date('Y')-1). '</th><th width="10%" colspan="12">' .date('Y')
		. '</th><th width="10%" colspan="12">' .(date('Y')+1). '</th></tr>';

	foreach($trials as $nctid => $trial) {
	
		$end_date =		($trial['NCT/completion_date']) ? $trial['NCT/completion_date'] : $trial['NCT/primary_completion_date'];
		
		//checking if the field 'NCT/lead_sponsor' has more than one value.
		if(is_array($trial['NCT/lead_sponsor'])) {
			$lead_sponsor = implode(',', $trial['NCT/lead_sponsor']);
		} else {
			$lead_sponsor = $trial['NCT/lead_sponsor'];
		}
	
		//checking if the field 'NCT/enrollment' has more than one value.
		if(is_array($trial['NCT/enrollment'])) {
			$enrollment = implode(',', $trial['NCT/enrollment']);
		} else {
			$enrollment = $trial['NCT/enrollment'];
		}
			
		$phase_arr = array('N/A'=>'#bfbfbf','0'=>'#44cbf5','0/1'=>'#99CC00','1'=>'#99CC00','1/2'=>'#ffff00','2'=>'#ffff00',
						'2/3'=>'#ff9900','3'=>'#ff9900','3/4'=>'#ff0000','4'=>'#ff0000');
		
		$ph = str_replace('Phase ', '', $trial['NCT/phase']);
		$phase = ($trial['NCT/phase']=='N/A') ? $ph : ('P' . $ph);
		
		$str = '';
				
		if(date('Y',strtotime($trial['NCT/start_date'])) < (date('Y')-1)) {
	
			if(date('Y',strtotime($end_date)) < (date('Y')-1)) {
			
				$str = '<td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td><td colspan="12">&nbsp;</td>';
						
			
			} else if(date('Y',strtotime($end_date)) == (date('Y')-1)) { 
			
				$str = '<td colspan="' . date('m',strtotime($end_date)) 
						. '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (12-date('m',strtotime($end_date))) . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';
	
			} else if(date('Y',strtotime($end_date)) == date('Y')) {
			 
				$str = '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($end_date))  
						. '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (12-date('m',strtotime($end_date))) . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';
			
			} else if(date('Y',strtotime($end_date)) == (date('Y')+1)) {
			 
				$str = '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						.'<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($end_date)) . '" style="background-color:' 
						. $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (12-date('m',strtotime($end_date))) . '">&nbsp;</td>';
			
			} else {
			
				$str = '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>';

			}		
		
		
		} else if(date('Y',strtotime($trial['NCT/start_date'])) == (date('Y')-1)) {
		
			$val = getColspan($trial['NCT/start_date'], $end_date);
			if(date('Y',strtotime($end_date)) == (date('Y')-1)) {
			
				$str = '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . (12-date('m',strtotime($trial['NCT/start_date']))) 
						. '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';
			
			} else if(date('Y',strtotime($end_date)) == date('Y')) {
			 
				if(date('m',strtotime($end_date)) >= 12) {
					$str = '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . $val . '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';
	
				} else {
					$str = '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . $val . '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (24-($val+date('m',strtotime($trial['NCT/start_date'])))) . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';
	
				}
			
			} else if(date('Y',strtotime($end_date)) == (date('Y')+1)) {
			
				 if(date('m',strtotime($end_date)) >= 12) {
				 
					$str = '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . (36-date('m',strtotime($trial['NCT/start_date']))) 
						. '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>';
	
				 } else {
				 
					$str = '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . $val . '" style="background-color:' 
						. $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (36-($val+date('m',strtotime($trial['NCT/start_date'])))) . '">&nbsp;</td>';
				 }
			
			} else {
			
				$str = '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
				. '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
				. '<td colspan="12" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>';
			}
		
		} else if(date('Y',strtotime($trial['NCT/start_date'])) == date('Y')) {
			
			$val = getColspan($trial['NCT/start_date'], $end_date);
			if(date('Y',strtotime($end_date)) == date('Y')) {
			
				if(date('m',strtotime($end_date)) >= 12) { 
				
					$str = '<td colspan="12">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . $val . '"  style="background-color:' 
						. $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';

				} else {  
				
					$str = '<td colspan="12">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . $val . '"  style="background-color:' 
						. $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (12-($val+date('m',strtotime($trial['NCT/start_date'])))) . '">&nbsp;</td>'
						. '<td colspan="12">&nbsp;</td>';

				}
			
			} else if(date('Y',strtotime($end_date)) == (date('Y')+1)) {
			
				if(date('m',strtotime($end_date)) >= 12) {
				
					$str = '<td colspan="12">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. (($val >= 24) ? '<td colspan="' . (24 - date('m',strtotime($trial['NCT/start_date']))) 
						. '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>' :
						'<td colspan="' . $val . '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>');

				} else {
				
					$str = '<td colspan="12">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . $val . '" style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>'
						. '<td colspan="' . (24 -($val+date('m',strtotime($trial['NCT/start_date'])))) . '">&nbsp;</td>';

				}

			} else {
				$str = '<td colspan="12">&nbsp;</td>'
						. '<td colspan="' . date('m',strtotime($trial['NCT/start_date'])) . '">&nbsp;</td>'
						. '<td colspan="' . (24-date('m',strtotime($trial['NCT/start_date']))) 
						. '"  style="background-color:' . $phase_arr[$ph] . '">&nbsp;</td>';
			}
		}
		
		$out .= '<tr><td>' . $trial['tumor_type'] . '</td>'
				. '<td><a href="http://clinicaltrials.gov/ct2/show/' . padnct($trial['nctid']) . '">' 
				. $trial['patient_population'] . '</a></td>'
				. '<td>' . $trial['trials_details'] . '</td>'
				. '<td style="'.((in_array('NCT/lead_sponsor',$trial['changedFields']))?'background-color:#FF8080;':'').'">' 
				. $lead_sponsor . '</td>'
				. '<td style="'.((in_array('NCT/enrollment',$trial['changedFields']))?'background-color:#FF8080;':'').'">' 
				. $enrollment . '</td>'
				. '<td nowrap="nowrap" style="' . ((in_array('NCT/start_date',$trial['changedFields'])) ? 'background-color:#FF8080;':'') . '">' . date('m/y',strtotime($trial['NCT/start_date'])) . '-' 
				. date('m/y',strtotime($end_date)) . '</td>'
				. '<td style="'.((in_array('NCT/overall_status',$trial['changedFields']))?'background-color:#FF8080;':'').'">' 
				. $trial['NCT/overall_status'] . '</td>'
				. '<td style="background-color:' . $phase_arr[$ph] . ';' 
				. (in_array('NCT/phase',$trial['changedFields']) ? ('border-collapse: collapse;border:2px solid #FF8080;') : '' ) . '">' . $phase . '</td>'
				. $str . '</tr>';
	}
}
else {

	$out = '<table border="1" class="MsoNormalTable"'
		. '<tr><th>NCTID</th>'
		. '<th>Intervention</th>'
		. '<th>Tumor Type</th>'
		. '<th>Patient Population</th>'
		. '<th>Trials Details</th>'
		. '<th>Lead Sponsor</th>'
		. '<th>Collaborator</th>'
		. '<th>Enrollment</th>'
		. '<th>Start Date</th>'
		. '<th>End Date</th>'
		. '<th>Overall Status</th>'
		. '<th>Phase</th>'
		. '<th>Randomized Controlled Trial</th>'
		. '<th>Data Release</th></tr>';

	foreach($trials as $nctid => $trial) {
	
		if (is_array($trial['NCT/intervention_name'])) {
			$intervention_name = implode(', ',$trial['NCT/intervention_name']);
		}else{
			$intervention_name = $trial['NCT/intervention_name'];
		}
		if ($intervention_name == '') $intervention_name = '(no intervention)';
	
		$end_date =		($trial['NCT/completion_date'])?$trial['NCT/completion_date']:$trial['NCT/primary_completion_date'];
	
		
		//checking if the field 'NCT/collaborator' has more than one value.
		if(is_array($trial['NCT/collaborator'])) {
			$collaborator = implode(',', $trial['NCT/collaborator']);
		} else {
			$collaborator = $trial['NCT/collaborator'];
		}
	
		//checking if the field 'NCT/lead_sponsor' has more than one value.
		if(is_array($trial['NCT/lead_sponsor'])) {
			$lead_sponsor = implode(',', $trial['NCT/lead_sponsor']);
		} else {
			$lead_sponsor = $trial['NCT/lead_sponsor'];
		}
	
		//checking if the field 'NCT/enrollment' has more than one value.
		if(is_array($trial['NCT/enrollment'])) {
			$enrollment = implode(',', $trial['NCT/enrollment']);
		} else {
			$enrollment = $trial['NCT/enrollment'];
		}
	
		$out .= '<tr><td><a href="http://clinicaltrials.gov/ct2/show/' . padnct($trial['nctid']) . '">'
			. padnct($nctid) . '</a></td>'
			. '<td>' . $intervention_name . '</td>'
			. '<td>' . $trial['tumor_type'] . '</td>'
			. '<td>' . $trial['patient_population'] . '</td>'
			. '<td>' . $trial['trials_details'] . '</td>'
			. '<td style="'.((in_array('NCT/lead_sponsor',$trial['changedFields']))?'background-color:#FF8080;':'').'">' . $lead_sponsor . '</td>'
			. '<td style="'.((in_array('NCT/collaborator',$trial['changedFields']))?'background-color:#FF8080;':'').'">' . $collaborator . '</td>'
			. '<td style="'.((in_array('NCT/enrollment',$trial['changedFields']))?'background-color:#FF8080;':'').'">' . 
	$enrollment . '</td>'
			. '<td style="'.((in_array('NCT/start_date',$trial['changedFields']))?'background-color:#FF8080;':'').'">' . $trial['NCT/start_date'] . '</td>'
			. '<td>' . $end_date . '</td>'
			. '<td style="'.((in_array('NCT/overall_status',$trial['changedFields']))?'background-color:#FF8080;':'').'">' . $trial['NCT/overall_status'] . '</td>'
			. '<td style="'.((in_array('NCT/phase',$trial['changedFields']))?'background-color:#FF8080;':'').'">' . str_replace('Phase ', 'P', $trial['NCT/phase']) . '</td>'
			. '<td>' . $trial['randomized_controlled_trial'] . '</td>'
			. '<td>' . $trial['data_release'] . '</td></tr>';
	}
}
$out .= '</table>';

//Just show HTML if debugging
if (isset($_GET['debug'])) {echo($out);exit;}

//Prep MS Word document
$doc = file_get_contents('templates/general.htm');
$doc = explode('#content#',$doc);
$doc = implode($out, $doc);

//Send headers for file download
header("Pragma: public");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/download");
header("Content-Type: application/msword");
header("Content-Disposition: attachment;filename=trial-tracker-" . substr($name,0,20) . ".doc");
header("Content-Transfer-Encoding: binary ");
echo($doc);
@flush();

//return NCT fields given an NCTID
function getNCT($nct_id,$time,$changesChecker)
{
	$param = new SearchParam();
	$param->field = fieldNameToPaddedId('nct_id');
	$param->action = 'search';
	$param->value = $nct_id;

	$fieldnames = array('nct_id','intervention_name','lead_sponsor','collaborator','enrollment','start_date','completion_date','primary_completion_date','overall_status','phase');
	foreach($fieldnames as $name) { 
		$list[] = fieldNameToPaddedId($name);
	}

	$res = search(array($param),$list,NULL,strtotime($time));
	//echo '<pre style="background-color:80FF80;">'.print_r($res,true).'</pre>';

	foreach($res as $stu) $study = $stu;

	$studycatData=mysql_fetch_assoc(mysql_query("SELECT `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_cats_in_study` `dc` ON (`dc`.`id`=`dv`.`studycat`) WHERE `dv`.`field`='1' AND `dv`.`val_int`='".$nct_id."' AND `dc`.`larvol_id`='".$study['larvol_id']."'"));
	//echo '<pre>'.print_r($studycatData,true).'</pre>';exit;

	$sql="SELECT DISTINCT `df`.`name` AS `fieldname`, `dv`.`studycat` FROM `data_values` `dv` LEFT JOIN `data_fields` `df` ON (`df`.`id`=`dv`.`field`) WHERE `df`.`name` IN ('".join("','",$fieldnames)."') AND `studycat`='".$studycatData['studycat']."' AND (`dv`.`superceded`<'".date('Y-m-d',strtotime($time))."' AND `dv`.`superceded`>='".date('Y-m-d',strtotime($changesChecker,strtotime($time)))."')";
//echo '<p>'.$sql.'</p>';
//	echo '<p>Time: '.$time.'; '.date('Y-m-d H:i:s',strtotime($time)).'</p>';
//	echo date('Y-m-d H:i:s',strtotime('today'));

    $changedFields=mysql_query($sql);
	$study['changedFields']=array();
	while ($row=mysql_fetch_assoc($changedFields)){
		$study['changedFields'][]='NCT/'.$row['fieldname'];
	}


	return $study;
}

//Get field IDs for names
// - for the $list argument, search() takes IDs prepended with a padding character (stripped by highPass())
// - didn't find the alternative, so I wrote this
function fieldNameToPaddedId($name)
{
	$query = 'SELECT data_fields.id AS data_field_id FROM '
		. 'data_fields LEFT JOIN data_categories ON data_fields.category=data_categories.id '
		. 'WHERE data_fields.name="' . $name . '" AND data_categories.name="NCT" LIMIT 1';
	$res = mysql_query($query);
	if($res === false) tex('Bad SQL query getting field ID of ' . $name);
	$res = mysql_fetch_assoc($res);
	if($res === false) tex('NCT schema not found!');
	return '_' . $res['data_field_id'];
}

//get difference between two dates in months
function getColspan($start_dt, $end_dt) {

	$diff = ceil((strtotime($end_dt)-strtotime($start_dt))/2628000);
	return $diff;

}
?>


