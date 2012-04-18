<?php
require_once('db.php');
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}

global $db;
global $now;
if(!isset($_GET['id'])) return;
$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
if(!is_numeric($id)) return;
$query = 'SELECT name,user,footnotes,description,category,shared,total FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
$res = mysql_fetch_array($res) or die('Report not found.');
$rptu = $res['user'];
$shared = $res['shared'];
$toal_fld=$res['total'];
if($rptu !== NULL && $rptu != $db->user->id && !$shared) return;	//prevent anyone from viewing others' private reports
$name = $res['name'];
$footnotes = htmlspecialchars($res['footnotes']);
$description = htmlspecialchars($res['description']);
$category = $res['category'];
	
$query = 'SELECT `num`,`type`,`type_id` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
$rows = array();
$columns = array();
$areaIds = array();
$productIds = array();
$columnsDisplayName = array();
$rowsDisplayName = array();

while($header = mysql_fetch_array($res))
{
	if($header['type'] == 'area')
	{
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
			$columns[$header['num']] = $result['name'];
			$columnsDisplayName[$header['num']] = $result['display_name'];
			$columnsDescription[$header['num']] = $result['description'];
		}
		else
		{
			$columns[$header['num']] = $header['type_id'];
		}
		$areaIds[$header['num']] = $header['type_id'];
	}
	else
	{
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . $header['type_id'] . "' "));
			$rows[$header['num']] = $result['name'];
			if($result['company'] != NULL && trim($result['company']) != '') $rows[$header['num']] = $result['name'].' / '.$result['company'];
			$rowsDescription[$header['num']] = $result['description'];
		}
		else
		{
			$rows[$header['num']] = $header['type_id'];
		}
		$productIds[$header['num']] = $header['type_id'];
	}
}
	// SELECT MAX ROW AND MAX COL
$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'product\'';
$res = mysql_query($query) or die(mysql_error());
$max_row = mysql_fetch_array($res);

$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\'';
$res = mysql_query($query) or die(mysql_error());
$max_column = mysql_fetch_array($res);

$row_total=array();
$col_total=array();
$active_total=0;
$count_total=0;
$data_matrix=array();
foreach($rows as $row => $rval)
{
	foreach($columns as $col => $cval)
	{
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			$cell_query = 'SELECT * FROM rpt_masterhm_cells WHERE `product`=' . $productIds[$row] . ' AND `area`='. $areaIds[$col] .'';
			$cell_res = mysql_query($cell_query) or die(mysql_error());
			$cell_data = mysql_fetch_array($cell_res);
			
			$col_active_total[$col]=$cell_data['count_active']+$col_active_total[$col];
			$row_active_total[$row]=$cell_data['count_active']+$row_active_total[$row];
			$col_count_total[$col]=$cell_data['count_total']+$col_count_total[$col];
			$row_count_total[$row]=$cell_data['count_total']+$row_count_total[$row];
			$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_indlead_total[$col];
			$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_indlead_total[$row];
			$active_total=$cell_data['count_active']+$active_total;
			$count_total=$cell_data['count_total']+$count_total;
			$indlead_total=$cell_data['count_indlead']+$indlead_total;
			
			if($cell_data['count_active'] != '' && $cell_data['count_active'] != NULL)
				$data_matrix[$row][$col]['active']=$cell_data['count_active'];
				else
				$data_matrix[$row][$col]['active']=0;
				
				if($cell_data['count_total'] != '' && $cell_data['count_total'] != NULL)
				$data_matrix[$row][$col]['total']=$cell_data['count_total'];
				else
				$data_matrix[$row][$col]['total']=0;
				
				if($cell_data['count_active_indlead'] != '' && $cell_data['count_active_indlead'] != NULL)
				$data_matrix[$row][$col]['indlead']=$cell_data['count_active_indlead'];
				else
				$data_matrix[$row][$col]['indlead']=0;
			
			$data_matrix[$row][$col]['active_prev']=$cell_data['count_active_prev'];
			$data_matrix[$row][$col]['total_prev']=$cell_data['count_total_prev'];
			$data_matrix[$row][$col]['indlead_prev']=$cell_data['count_active_indlead_prev'];
			
			$data_matrix[$row][$col]['phase_explain']=trim($cell_data['phase_explain']);
			
			$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
			
			$data_matrix[$row][$col]['filing']=trim($cell_data['filing']);
			
				
			if($cell_data['bomb_auto'] == 'small')
			{
				$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
				$data_matrix[$row][$col]['bomb_auto']['src']='sbomb.png';
				$data_matrix[$row][$col]['bomb_auto']['alt']='Small Bomb';
				$data_matrix[$row][$col]['bomb_auto']['style']='width:10px; height:11px;';
				$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
			}
			elseif($cell_data['bomb_auto'] == 'large')
			{
				$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
				$data_matrix[$row][$col]['bomb_auto']['src']='lbomb.png';
				$data_matrix[$row][$col]['bomb_auto']['alt']='Large Bomb';
				$data_matrix[$row][$col]['bomb_auto']['style']='width:18px; height:20px;';
				$data_matrix[$row][$col]['bomb_auto']['title']='Suggested';
			}
			else
			{
				$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
				$data_matrix[$row][$col]['bomb_auto']['src']='trans.gif';
				$data_matrix[$row][$col]['bomb_auto']['alt']='None';
				$data_matrix[$row][$col]['bomb_auto']['style']='width:10px; height:11px;';
				$data_matrix[$row][$col]['bomb_auto']['title']='';
			}
			
			
			if($cell_data['bomb'] == 'small')
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_sbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Small Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			elseif($cell_data['bomb'] == 'large')
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			else
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_square.png';
				$data_matrix[$row][$col]['bomb']['alt']='None';
				$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			
			
			$data_matrix[$row][$col]['phase4_override']=$cell_data['phase4_override'];
			$data_matrix[$row][$col]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
			
			$data_matrix[$row][$col]['highest_phase_prev']=$cell_data['highest_phase_prev'];
			$data_matrix[$row][$col]['highest_phase_lastchanged']=$cell_data['highest_phase_lastchanged'];
			$data_matrix[$row][$col]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
			
			
			
			if($cell_data['highest_phase'] == 'N/A' || $cell_data['highest_phase'] == '' || $cell_data['highest_phase'] === NULL)
			{
				$data_matrix[$row][$col]['color']='background-color:#BFBFBF;';
				$data_matrix[$row][$col]['color_code']='BFBFBF';
			}
			else if($cell_data['highest_phase'] == '0')
			{
				$data_matrix[$row][$col]['color']='background-color:#00CCFF;';
				$data_matrix[$row][$col]['color_code']='00CCFF';
			}
			else if($cell_data['highest_phase'] == '1' || $cell_data['highest_phase'] == '0/1' || $cell_data['highest_phase'] == '1a' 
			|| $cell_data['highest_phase'] == '1b' || $cell_data['highest_phase'] == '1a/1b' || $cell_data['highest_phase'] == '1c')
			{
				$data_matrix[$row][$col]['color']='background-color:#99CC00;';
				$data_matrix[$row][$col]['color_code']='99CC00';
			}
			else if($cell_data['highest_phase'] == '2' || $cell_data['highest_phase'] == '1/2' || $cell_data['highest_phase'] == '1b/2' 
			|| $cell_data['highest_phase'] == '1b/2a' || $cell_data['highest_phase'] == '2a' || $cell_data['highest_phase'] == '2a/2b' 
			|| $cell_data['highest_phase'] == '2a/b' || $cell_data['highest_phase'] == '2b')
			{
				$data_matrix[$row][$col]['color']='background-color:#FFFF00;';
				$data_matrix[$row][$col]['color_code']='FFFF00';
			}
			else if($cell_data['highest_phase'] == '3' || $cell_data['highest_phase'] == '2/3' || $cell_data['highest_phase'] == '2b/3' 
			|| $cell_data['highest_phase'] == '3a' || $cell_data['highest_phase'] == '3b')
			{
				$data_matrix[$row][$col]['color']='background-color:#FF9900;';
				$data_matrix[$row][$col]['color_code']='FF9900';
			}
			else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
			{
				$data_matrix[$row][$col]['color']='background-color:#FF0000;';
				$data_matrix[$row][$col]['color_code']='FF0000';	
			}
			
			if($cell_data['phase4_override'])
			{
				$data_matrix[$row][$col]['color']='background-color:#FF0000;';
				$data_matrix[$row][$col]['color_code']='FF0000';
			}
			
			$data_matrix[$row][$col]['last_update']=$cell_data['last_update'];
			
			$data_matrix[$row][$col]['div_start_style'] = $data_matrix[$row][$col]['color'];
			$data_matrix[$row][$col]['cell_start_title'] = 'Active Trials';
			
			/////Calculate Record Update Class
			 $latest_date='';
			 $qualify_title='';
			 
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['last_update'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['last_update'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				if($data_matrix[$row][$col]['color_code']=='FF0000')
				$data_matrix[$row][$col]['cell_start_style'] = 'background-color:#FFFFFF; border:#FF0000 solid;';
				else
				$data_matrix[$row][$col]['cell_start_style'] = $data_matrix[$row][$col]['color'].' border:#FF0000 solid;';
				$data_matrix[$row][$col]['cell_start_title'] = 'Record Updated On: '.date('F d, Y', strtotime($data_matrix[$row][$col]['last_update']));
				
				if($latest_date < date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged'])) || $latest_date != '')
				{
					$qualify_title=$data_matrix[$row][$col]['cell_start_title'];
					$latest_date = date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['last_update']));
				}
			}
			else
			{
				$data_matrix[$row][$col]['cell_start_style'] = $data_matrix[$row][$col]['color'].' border:#'.$data_matrix[$row][$col]['color_code'].' solid;';
			} 
			
			/////Calculate Count Update Class
			$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
			
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['count_start_title'] = 'Active Count Changed from: '. $cell_data['count_active_prev'] .' On:'. date('F d, Y', strtotime($data_matrix[$row][$col]['count_lastchanged']));
				$data_matrix[$row][$col]['count_start_style'] = 'color:#FF0000; font-weight:bold;';
				if($data_matrix[$row][$col]['color_code']=='FF0000')
				$data_matrix[$row][$col]['count_start_style'] = 'color:#FF0000;  background-color:#FFFFFF; font-weight:bold;';
				if($latest_date < date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged'])) || $latest_date != '')
				{
					$qualify_title=$data_matrix[$row][$col]['count_start_title'];
					$latest_date = date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged']));
				}
			} 
			
			
			$data_matrix[$row][$col]['bomb']['style'] = $data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; cursor:pointer;';
				
			/////Calculate Bomb Update Class
			$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
			
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['bomb']['title'] = 'Bomb Updated On: '.date('F d, Y', strtotime($data_matrix[$row][$col]['bomb_lastchanged']));
				//$data_matrix[$row][$col]['bomb']['style'] = $data_matrix[$row][$col]['bomb']['style'].' border:#FF0000 solid;';
				
				if($cell_data['bomb'] == 'small')
				$data_matrix[$row][$col]['bomb']['src']='newred_sbomb.png';
				if($cell_data['bomb'] == 'large')
				$data_matrix[$row][$col]['bomb']['src']='newred_lbomb.png';
				
				if($latest_date < date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) || $latest_date != '')
				{
					$qualify_title=$data_matrix[$row][$col]['bomb']['title'];
					$latest_date = date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['bomb_lastchanged']));
				}
			} 
			
			/////Calculate Filling Update Class
			$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
			
			$data_matrix[$row][$col]['filing_src']='images/new_file.png';
			
			$data_matrix[$row][$col]['filing_start_title'] = 'Filing Details';
			$data_matrix[$row][$col]['filing_start_style'] = 'width:20px; height:20px; vertical-align:top; cursor:pointer;';
			 
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['filing_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['filing_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['filing_src']='images/newred_file.png';
				$data_matrix[$row][$col]['filing_start_title'] = 'Filing Details Updated On: '.date('F d, Y', strtotime($data_matrix[$row][$col]['filing_lastchanged']));
				$data_matrix[$row][$col]['filing_start_style'] = 'width:20px; height:20px; vertical-align:middle; cursor:pointer;';
				
				if($latest_date < date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['filing_lastchanged'])) || $latest_date != '')
				{
					$qualify_title=$data_matrix[$row][$col]['filing_start_title'];
					$latest_date = date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['filing_lastchanged']));
				}
			} 
			
			
			/////Calculate Phase Explain Update Class
			$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
			
			$data_matrix[$row][$col]['phaseexp_src']='images/phaseexp.png';
			
			$data_matrix[$row][$col]['phaseexp_start_title'] = 'Phase Explain';
			$data_matrix[$row][$col]['phaseexp_start_style'] = 'width:20px; height:20px; vertical-align:top; cursor:pointer;';
			 
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['phaseexp_src']='images/phaseexp_red.png';
				$data_matrix[$row][$col]['phaseexp_start_title'] = 'Filing Details Updated On: '.date('F d, Y', strtotime($data_matrix[$row][$col]['filing_lastchanged']));
				$data_matrix[$row][$col]['phaseexp_start_style'] = 'width:20px; height:20px; vertical-align:middle; cursor:pointer;';
				
				if($latest_date < date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])) || $latest_date != '')
				{
					$qualify_title=$data_matrix[$row][$col]['phaseexp_start_title'];
					$latest_date = date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['phase_explain_lastchanged']));
				}
			}
			
			 if($qualify_title != '')
			 {
			 	if($data_matrix[$row][$col]['color_code']=='FF0000')
				$data_matrix[$row][$col]['cell_start_style'] = 'background-color:#FFFFFF; border:#FF0000 solid;';
				else
				$data_matrix[$row][$col]['cell_start_style'] = $data_matrix[$row][$col]['color'].' border:#FF0000 solid;';
				
				$data_matrix[$row][$col]['cell_start_title'] = $qualify_title;
			 	$data_matrix[$row][$col]['count_start_title'] = $qualify_title;
			 	$data_matrix[$row][$col]['bomb']['title'] = $qualify_title;
			 	$data_matrix[$row][$col]['filing_start_title'] = $qualify_title;
			 	$data_matrix[$row][$col]['phaseexp_start_title'] = $qualify_title;
			}
		}
		else
		{
			$data_matrix[$row][$col]['active']=0;
			$data_matrix[$row][$col]['total']=0;
			$data_matrix[$row][$col]['indlead']=0;
			$col_active_total[$col]=0+$col_active_total[$col];
			$row_active_total[$row]=0+$row_active_total[$row];
			$col_count_total[$col]=0+$col_count_total[$col];
			$row_count_total[$row]=0+$row_count_total[$row];
			$col_count_indlead[$col]=0+$col_count_indlead[$col];
			$row_count_indlead[$row]=0+$row_count_indlead[$row];
			$data_matrix[$row][$col]['bomb_auto']['src']='';
			$data_matrix[$row][$col]['bomb']['src']='';
			$data_matrix[$row][$col]['bomb_explain']='';
			$data_matrix[$row][$col]['filing']='';
			$data_matrix[$row][$col]['color']='background-color:#DDF;';
			$data_matrix[$row][$col]['color_code']='DDF';
			$data_matrix[$row][$col]['record_update_class']='';
		}
	}
}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials :: Online Heatmap</title>
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script src="scripts/jquery-1.7.1.min.js"></script>
<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="date/jquery.date_input.js"></script>
<script type="text/javascript" src="scripts/date/jquery.jdpicker.js"></script>
<script type="text/javascript" src="date/init.js"></script>
<link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
<link href="scripts/date/jdpicker.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="screen" />
<style type="text/css">
body { font-family:Verdana; font-size: 13px;}
a, a:hover{color:#000000;text-decoration:none; height:100%;}
.display td, .display th {font-weight:normal; background-color:#DDF; vertical-align:top; /*border-right: 1px solid blue; border-left:1px solid blue; border-top: 1px solid blue; border-bottom:1px solid blue;*/}
tr {/*border-right: 1px solid blue; border-left: 1px solid blue; border-top: 1px solid blue; border-bottom: 1px solid blue;*/}
.active{font-weight:bold;}
.total{visibility:hidden;}
.comma_sep{visibility:hidden;}
.result {
	font-weight:bold;
	font-size:18px;
}
.manual {
color:#FF7700;
}
	
.jdpicker_w
{
width:90px;
float:left;
}
.jdpicker
{
border:0;
color:#f6931f;
font-weight:bold;
width:100px;
}
.demo div{
	margin:0 10px 0 10px;
}
.controls td{
	border-bottom:1px solid #44F;
	border-right:1px solid #44F;
	padding: 10px 0 0 3px;
    vertical-align: top;
}
.controls th{
	font-weight:normal;
	border-bottom: 1px solid #4444FF;
    border-right: 1px solid #4444FF;
}
.right{
	border-right:0px !important;
}

.bottom{
	border-bottom:0px !important;
}
.controls input{
	margin:0.1em;
}
</style>
<script language="javascript" type="text/javascript">
function change_view()
{
	///Date format set cause some date format does not work in IE
	var today = new Date("<?php print date('m/d/Y H:i:s', strtotime('now', $now)); ?>");	// "mm/dd/yyyy hh:mm:ss"  
	var one_week = new Date("<?php print date('m/d/Y H:i:s', strtotime('-1 Week', $now)); ?>");
	var two_week = new Date("<?php print date('m/d/Y H:i:s', strtotime('-2 Weeks', $now)); ?>");
	var one_month = new Date("<?php print date('m/d/Y H:i:s', strtotime('-1 Month', $now)); ?>");
	var three_month = new Date("<?php print date('m/d/Y H:i:s', strtotime('-3 Months', $now)); ?>");
	var one_year = new Date("<?php print date('m/d/Y H:i:s', strtotime('-1 Year', $now)); ?>");
	
	var limit = document.getElementById('Last_HM').value;
	var view_type = document.getElementById('view_type');
	var start_range = document.getElementById('startrange').value;
	var end_range = document.getElementById('endrange').value;
	
	var st_limit, ed_limit;
	
	switch(start_range)
	{
		case 'now': st_limit = today; break;
		case '1 week ago': st_limit = one_week; break;
		case '2 weeks ago': st_limit = two_week; break;
		case '1 month ago': st_limit = one_month; break;
		case '1 quarter ago': st_limit = three_month; break;
		case '1 year ago': st_limit = one_year; break;
		default: start_range = start_range.replace(/\s+/g, '') ;	//Remove space in between
				 var date_arr = start_range.split('-'); 
				 var st_limit = date_arr[1] + "/" + date_arr[2] + "/" + date_arr[0] + " 23:59:59";	///As date Picker format is NOT Supported by by Javascript in IE, manual creation in required format
				 var st_limit = new Date(st_limit);
				 break;
	}
	switch(end_range)
	{
		case 'now': ed_limit = today; break;
		case '1 week ago': ed_limit = one_week; break;
		case '2 weeks ago': ed_limit = two_week; break;
		case '1 month ago': ed_limit = one_month; break;
		case '1 quarter ago': ed_limit = three_month; break;
		case '1 year ago': ed_limit = one_year; break;
		default: end_range = end_range.replace(/\s+/g, '') ;
				 var date_arr = end_range.split('-');
				 var ed_limit = date_arr[1] + "/" + date_arr[2] + "/" + date_arr[0] + " 00:00:01"; ///As date Picker format is NOT Supported by by Javascript in IE, manual creation in required format
				 var ed_limit = new Date(ed_limit);
				 break;
	}
		
	var i=1;
	for(i=1;i<=limit;i++)
	{
		var cell_exist=document.getElementById("Cell_values_"+i);
		var latest_date='';
		var qualify_title='';
		if(cell_exist != null && cell_exist != '')
		{
		
			var cell_val=document.getElementById("Cell_values_"+i).value;
			var Cell_values_Arr = cell_val.split(',endl,');
			
			/////Change Count
			var font_element=document.getElementById("Font_ID_"+i);	//Ceck if cell has font element so we can chnage cont value
			
			var tot_element=document.getElementById("Tot_ID_"+i); 	// Check if total column exists
			
			var cell_link_val=document.getElementById("Link_value_"+i).value;	//Check in cell has link
			
			
			
			
			
			if(cell_link_val != '' && cell_link_val != null)
			{
				if(view_type.value == 'active')
				{
					document.getElementById("Cell_Link_"+i).href = cell_link_val+'&list=1&sr='+start_range+'&er='+end_range;
					
					if(tot_element != null && tot_element != '')
					document.getElementById("Tot_ID_"+i).innerHTML = Cell_values_Arr[0];
					
					if(font_element != null && font_element != '')
					{
						document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[0];
						document.getElementById("Popup_Count_ID_"+i).innerHTML = Cell_values_Arr[3];
						document.getElementById("Cell_Link_"+i).title = "Active Trials";
					}
				}
				else if(view_type.value == 'total')
				{
					document.getElementById("Cell_Link_"+i).href = cell_link_val+'&list=2&sr='+start_range+'&er='+end_range;
					
					if(tot_element != null && tot_element != '')
					document.getElementById("Tot_ID_"+i).innerHTML = Cell_values_Arr[1];
					
					if(font_element != null && font_element != '')
					{
						document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[1];
						document.getElementById("Popup_Count_ID_"+i).innerHTML = Cell_values_Arr[4];
						document.getElementById("Cell_Link_"+i).title = "Total Trials (Active + Inactive)";
					}
				}
				else if(view_type.value == 'indlead')
				{
					document.getElementById("Cell_Link_"+i).href = cell_link_val+'&list=1&itype=0&sr='+start_range+'&er='+end_range;
					
					if(tot_element != null && tot_element != '')
					document.getElementById("Tot_ID_"+i).innerHTML = Cell_values_Arr[0];
					
					if(font_element != null && font_element != '')
					{
						document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[2];
						document.getElementById("Popup_Count_ID_"+i).innerHTML = Cell_values_Arr[5];
						document.getElementById("Cell_Link_"+i).title = "Active Industry Lead Sponsor Trials";
					}
					
				}	
			}
		
			
			
			if(font_element != null && font_element != '')
			{
				
				///Change Cell Border Color
				var record_cdate= new Date(Cell_values_Arr[6]);	//Record Update Date
				
				
				if((record_cdate <= st_limit) && (record_cdate >= ed_limit)) //Compare record Change Dates
				{
					document.getElementById("Cell_ID_"+i).style.border = "#FF0000 solid";
					if(Cell_values_Arr[14]=='FF0000')
					document.getElementById("Cell_ID_"+i).style.backgroundColor = "#FFFFFF";
					document.getElementById("Div_ID_"+i).title = "Record Updated On: "+ Cell_values_Arr[7];
					document.getElementById("Last_CDate_"+i).style.display = "block";
					if(latest_date < record_cdate || latest_date == '')
					{
						qualify_title = "Last Update On: "+ Cell_values_Arr[7];
						latest_date = record_cdate;
					}
				}
				else
				{
					if(Cell_values_Arr[14] != '' && Cell_values_Arr[14] != null && Cell_values_Arr[14] != 'undefined')
					{
						document.getElementById("Cell_ID_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Last_CDate_"+i).style.display = "none";
						document.getElementById("Div_ID_"+i).title = "";
						if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_ID_"+i).style.backgroundColor = "#"+Cell_values_Arr[14];
					}
				}
				
				
				
				///Change Count Color
				var count_cdate= new Date(Cell_values_Arr[8]);	//Count Chnage Date
				
				if((count_cdate <= st_limit) && (count_cdate >= ed_limit)) //Compare Count Change Dates
				{
					if(view_type.value == 'indlead')	//Compare Industry Lead Sponsor values
					{
						document.getElementById("Cell_Link_"+i).title = "Active Industry Lead Count Changed from: "+ Cell_values_Arr[5] +" On: "+ Cell_values_Arr[9];
						document.getElementById("Cell_Link_"+i).style.color = "#FF0000";
						document.getElementById("Cell_Link_"+i).style.fontWeight = "bold";
						if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_Link_"+i).style.backgroundColor = "#FFFFFF";
						if(latest_date < count_cdate || latest_date == '')
						{
							qualify_title = "Active Industry Lead Count Changed from: "+ Cell_values_Arr[5] +" On: "+ Cell_values_Arr[9];
							latest_date = count_cdate;
						}
					}
					if(view_type.value == 'total')	//Compare Total values
					{
						document.getElementById("Cell_Link_"+i).title = "Total Count Changed from: "+ Cell_values_Arr[4] +" On: "+ Cell_values_Arr[9];
						document.getElementById("Cell_Link_"+i).style.color = "#FF0000";
						document.getElementById("Cell_Link_"+i).style.fontWeight = "bold";
						if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_Link_"+i).style.backgroundColor = "#FFFFFF";
						if(latest_date < count_cdate || latest_date == '')
						{
							qualify_title = "Total Count Changed from: "+ Cell_values_Arr[4] +" On: "+ Cell_values_Arr[9];
							latest_date = count_cdate;
						}
					}
					if(view_type.value == 'active')	//Compare Industry Lead Sponsor values
					{
						document.getElementById("Cell_Link_"+i).title = "Active Count Changed from: "+ Cell_values_Arr[3] +" On: "+ Cell_values_Arr[9];
						document.getElementById("Cell_Link_"+i).style.color = "#FF0000";
						document.getElementById("Cell_Link_"+i).style.fontWeight = "bold";
						if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_Link_"+i).style.backgroundColor = "#FFFFFF";
						if(latest_date < count_cdate || latest_date == '')
						{
							qualify_title = "Active Count Changed from: "+ Cell_values_Arr[3] +" On: "+ Cell_values_Arr[9];
							latest_date = count_cdate;
						}
					}
					document.getElementById("Count_CDate_"+i).style.display = "block";
				}
				else	//Make Count to normal state if there is no change
				{
					if(view_type.value == 'active')
					{
							document.getElementById("Cell_Link_"+i).title = "Active Trials";
					}
					else if(view_type.value == 'total')
					{
						document.getElementById("Cell_Link_"+i).title = "Total Trials (Active + Inactive)";
					}
					else if(view_type.value == 'indlead')
					{
						document.getElementById("Cell_Link_"+i).title = "Active Industry Lead Sponsor Trials";
					}
					document.getElementById("Cell_Link_"+i).style.color = "#000000";
					document.getElementById("Cell_Link_"+i).style.backgroundColor = "#"+Cell_values_Arr[14];
					document.getElementById("Cell_Link_"+i).style.fontWeight = "normal";
					document.getElementById("Count_CDate_"+i).style.display = "none";
				}
					
				///Change Bomb Color
				var bomb_cdate= new Date(Cell_values_Arr[10]);	//Bomb Chnage Date
				var bomb_ele= document.getElementById("Cell_Bomb_"+i);	//Bomb Element
				
				if(bomb_ele != null && bomb_ele != '')
				{
					if((bomb_cdate <= st_limit) && (bomb_cdate >= ed_limit)) //Compare Bomb Change Dates
					{
						document.getElementById("Cell_Bomb_"+i).title = "Bomb Data Updated On: "+ Cell_values_Arr[11];
						//document.getElementById("Cell_Bomb_"+i).style.border = "#FF0000 solid";
						
						if(Cell_values_Arr[15] == 'large')
						document.getElementById("Cell_Bomb_"+i).src = "images/newred_lbomb.png";
						else if(Cell_values_Arr[15] == 'small')
						document.getElementById("Cell_Bomb_"+i).src = "images/newred_sbomb.png";
						document.getElementById("Bomb_CDate_"+i).style.display = "block";
						
						if(latest_date < bomb_cdate || latest_date == '')
						{
							qualify_title = "Bomb Data Updated On: "+ Cell_values_Arr[11];
							latest_date = bomb_cdate;
						}
					}
					else
					{
						document.getElementById("Cell_Bomb_"+i).title = "Bomb Details";
						document.getElementById("Bomb_CDate_"+i).style.display = "none";
						
						if(Cell_values_Arr[15] == 'large')
						document.getElementById("Cell_Bomb_"+i).src = "images/new_lbomb.png";
						else if(Cell_values_Arr[15] == 'small')
						document.getElementById("Cell_Bomb_"+i).src = "images/new_sbomb.png";
					}
				}
				
				///Change Filing Color
				var filing_cdate= new Date(Cell_values_Arr[12]);	//Filing Chnage Date
				var filing_ele= document.getElementById("Cell_Filing_"+i);	//Bomb Element
				
				if(filing_ele != null && filing_ele != '')
				{
					if((filing_cdate <= st_limit) && (filing_cdate >= ed_limit)) //Compare Filing Change Dates
					{
						document.getElementById("Cell_Filing_"+i).title = "Filing Data Updated On: "+ Cell_values_Arr[13];
						//document.getElementById("Cell_Filing_"+i).style.border = "#FF0000 solid";
						document.getElementById("Cell_Filing_"+i).src = "images/newred_file.png";
						document.getElementById("Filing_CDate_"+i).style.display = "block";
						if(latest_date < filing_cdate || latest_date == '')
						{
							qualify_title = "Filing Data Updated On: "+ Cell_values_Arr[13];
							latest_date = filing_cdate;
						}
					}
					else
					{
						document.getElementById("Cell_Filing_"+i).title = "Filing Details";
						//document.getElementById("Cell_Filing_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Cell_Filing_"+i).src = "images/new_file.png";
						document.getElementById("Filing_CDate_"+i).style.display = "none";
					}
				}
				
				///Change Phase Explain Color
				var phaseexp_cdate= new Date(Cell_values_Arr[16]);	//Filing Chnage Date
				var phaseexp_ele= document.getElementById("Cell_Phase_"+i);	//Bomb Element
				
				if(phaseexp_ele != null && phaseexp_ele != '')
				{
					if((phaseexp_cdate <= st_limit) && (phaseexp_cdate >= ed_limit)) //Compare Filing Change Dates
					{
						document.getElementById("Cell_Phase_"+i).title = "Phase Explain Updated On: "+ Cell_values_Arr[17];
						//document.getElementById("Cell_Filing_"+i).style.border = "#FF0000 solid";
						document.getElementById("Cell_Phase_"+i).src = "images/phaseexp_red.png";
						document.getElementById("PhaseExp_CDate_"+i).style.display = "block";
						if(latest_date < phaseexp_cdate || latest_date == '')
						{
							qualify_title = "Phase Explain Updated On: "+ Cell_values_Arr[17];
							latest_date = phaseexp_cdate;
						}
					}
					else
					{
						document.getElementById("Cell_Phase_"+i).title = "Phase Explain";
						//document.getElementById("Cell_Filing_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Cell_Phase_"+i).src = "images/phaseexp.png";
						document.getElementById("PhaseExp_CDate_"+i).style.display = "none";
					}
				}
				
				///Change Phase4 Details
				var phase4_cdate= new Date(Cell_values_Arr[18]);	//Phase4 Chnage Date
				var phase4_ele= document.getElementById("Red_Cell_"+i);	//Phase4 Element
				
				if(phase4_ele != null && phase4_ele != '')
				{
					if((phase4_cdate <= st_limit) && (phase4_cdate >= ed_limit)) //Compare Filing Change Dates
					{
						document.getElementById("Red_Cell_"+i).style.visibility = "visible";
						document.getElementById("Red_Cell_"+i).title = "Red Cell Override";
						if(latest_date < phase4_cdate || latest_date == '')
						{
							qualify_title = "Red Cell Override On: "+ Cell_values_Arr[19];
							latest_date = phase4_cdate;
						}
					}
					else
					{
						document.getElementById("Red_Cell_"+i).title = "Red Cell Override";
						//document.getElementById("Cell_Filing_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Red_Cell_"+i).style.visibility = "hidden";
					}
				}
				
				///Change Hign Phase Details
				var high_phase_cdate= new Date(Cell_values_Arr[20]);	//High Phase Chnage Date
				var high_phase_ele= document.getElementById("Highest_Phase_"+i);	//high phase Element
				
				if(high_phase_ele != null && high_phase_ele != '')
				{
					if((high_phase_cdate <= st_limit) && (high_phase_cdate >= ed_limit)) //Compare highest phase Change Dates
					{
						document.getElementById("Highest_Phase_"+i).style.display = "block";
						document.getElementById("Highest_Phase_"+i).title = "Highest Phase";
						if(latest_date < high_phase_cdate || latest_date == '')
						{
							qualify_title = "Highest Phase Updated From: Phase "+ Cell_values_Arr[22] +"On "+ Cell_values_Arr[21];
							latest_date = high_phase_cdate;
						}
					}
					else
					{
						document.getElementById("Highest_Phase_"+i).title = "Highest Phase";
						//document.getElementById("Cell_Filing_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Highest_Phase_"+i).style.display = "none";
					}
				}
				
				if(qualify_title != '')
				{
					document.getElementById("Cell_ID_"+i).style.border = "#FF0000 solid";
					if(Cell_values_Arr[14]=='FF0000')
					document.getElementById("Cell_ID_"+i).style.backgroundColor = "#FFFFFF";
					
					document.getElementById("Div_ID_"+i).title = qualify_title;
					document.getElementById("Cell_Link_"+i).title = qualify_title;
					if(bomb_ele != null && bomb_ele != '')
					document.getElementById("Cell_Bomb_"+i).title = qualify_title;
					if(filing_ele != null && filing_ele != '')
					document.getElementById("Cell_Filing_"+i).title = qualify_title;
					if(phaseexp_ele != null && phaseexp_ele != '')
					document.getElementById("Cell_Phase_"+i).title = qualify_title;
				}
			
			}	///Font Element If Ends
		} /// Cell Data Exists if Ends
	}	/// For Loop Ends
}

function timeEnum($timerange)
	{
		switch($timerange)
		{
			case 0: $timerange = "now"; break;
			case 1: $timerange = "1 week ago"; break;
			case 2: $timerange = "2 weeks ago"; break;
			case 3: $timerange = "1 month ago"; break;
			case 4: $timerange = "1 quarter ago"; break;
			case 5: $timerange = "1 year ago"; break;
		}
		return $timerange;
	}

$(function() 
{
	//highlight changes slider
		$("#slider-range-min").slider({
			range: true,
			min: 0,
			max: 5,
			step: 1,
			values: [ 0, 3 ],
			slide: function(event, ui) {
				$("#startrange").val(timeEnum(ui.values[0]));
				$("#endrange").val(timeEnum(ui.values[1]));
				change_view();
			}
		});
	
});
</script>
</head>

<body>
<?php 

$online_HMCounter=0;

$htmlContent .= '<table width="100%" style="background-color:#FFFFFF;">'
				. '<tr><td style="background-color:#FFFFFF;"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
				. '<td style="background-color:#FFFFFF;" nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface Work In Progress</span>'
				. '<br/><span style="font-weight:normal;">Send feedback to '
				. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
				. 'larvoltrials@larvol.com</a></span></td>'
				. '<td style="background-color:#FFFFFF;" class="result">Name: ' . htmlspecialchars($name) . '</td></tr></table><br/>'
				
				. '<table width="600px" border="0" cellspacing="0" class="controls" align="center">'
				. '<tr><th>View Mode</th><th class="right">Range</th></tr>'
				. '<tr>'
				. '<td class="bottom"><p style="margin-top:10px;margin-right:10px;"><select id="view_type" name="view_type" onchange="change_view()">'
				. '<option value="active" selected="selected">Active Trials</option>'
				. '<option value="total">All Trials</option>'
				. '<option value="indlead">Active Industry Trials</option></select></p></td>'
				. '<td style="background-color:#FFFFFF;" class="bottom right"><div class="demo"><p style="margin-top:10px;">'
				. '<label for="startrange" style="float:left;margin-left:15px;"><b>Highlight updates:</b></label>'
				. '<input type="text" id="startrange" name="sr" value="now" class="jdpicker" />'
				. '<label style="color:#f6931f;float:left;">-</label> '
				. '<input type="text" id="endrange"  name="er" value="1 month ago" style="width:auto;margin-left:15px;" class="jdpicker" />'
				. '<br/><div id="slider-range-min" style="width:320px; margin:10px 10px 0 10px;margin-left:40px;" align="left"></div></p></div></td>'
				. '</tr>'
				. '</table>'
				. '<br clear="all"/><br/>';
						
$htmlContent .= '<div align="center">'
			. '<table style="padding-top:5px; height:100%;" class="display">'
			. '<thead><tr style="page-break-inside:avoid; height:100%;" nobr="true"><th>&nbsp;</th>';
						
foreach($columns as $col => $val)
{
	$online_HMCounter++;
	$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
	$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
	$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
		
	$htmlContent .= '<th id="Cell_ID_'.$online_HMCounter.'" width="80px" '.$caltTitle.'>';
	
	if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
	{
		$htmlContent .= '<input type="hidden" value="'.$col_active_total[$col].',endl,'.$col_count_total[$col].',endl,'.$col_indlead_total[$col].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" title="Active Trials" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" target="_blank">'.$val.'</a>';
	}
		$htmlContent .='</div></th>';
}
		
//if total checkbox is selected
if($toal_fld)
{
	$online_HMCounter++;
	$htmlContent .= '<th id="Cell_ID_'.$online_HMCounter.'" width="80px"><div align="center">';
	if(!empty($productIds) && !empty($areaIds))
	{
		$productIds = array_filter($productIds);
		$areaIds = array_filter($areaIds);
		$htmlContent .= '<input type="hidden" value="'.$active_total.',endl,'.$count_total.',endl,'.$indlead_total.'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" title="Active Trials" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" target="_blank"><font id="Tot_ID_'.$online_HMCounter.'">'.$active_total.'</font></a>';
	}
	$htmlContent .= '</div></th>';
}

$htmlContent .= '</tr></thead>';
				
foreach($rows as $row => $rval)
{
	$online_HMCounter++;
	//$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval; //Commente as as planned to ignore display name in Product only
	$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
	$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
	$htmlContent .= '<tr style="page-break-inside:avoid;"><th style="width:200px; height:100%;" id="Cell_ID_'.$online_HMCounter.'" '.$raltTitle.'><div align="center">';
			
	if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
	{
		$htmlContent .= '<input type="hidden" value="'.$row_active_total[$row].',endl,'.$row_count_total[$row].',endl,'.$row_indlead_total[$row].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" title="Active Trials" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" target="_blank" class="ottlink">'.$rval.'&nbsp;</a>';
	}
	$htmlContent .= '</div></th>';
	
	foreach($columns as $col => $cval)
	{
		$online_HMCounter++;
		$htmlContent .= '<td id="Cell_ID_'.$online_HMCounter.'" width="110px" style="'. $data_matrix[$row][$col]['cell_start_style'] .' padding:2px; height:100%;" align="center">';
	
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			
			$htmlContent .= '<div id="Div_ID_'.$online_HMCounter.'" title="'. $data_matrix[$row][$col]['cell_start_title'] .'" style="'.$data_matrix[$row][$col]['div_start_style'].' width:100%; height:100%; " onclick="popup_show(\'allpopup\', '.count($rows).','.count($columns).',\'allpopup_'.$row.'_'.$col.'\', \'allpopup_drag_'.$row.'_'.$col.'\', \'allpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);">';
			
			$htmlContent .= '<input type="hidden" value="'.$data_matrix[$row][$col]['active'].',endl,'.$data_matrix[$row][$col]['total'].',endl,'.$data_matrix[$row][$col]['indlead'].',endl,'.$data_matrix[$row][$col]['active_prev'].',endl,'.$data_matrix[$row][$col]['total_prev'].',endl,'.$data_matrix[$row][$col]['indlead_prev'].',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['last_update'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['last_update'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['count_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['count_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).',endl,'.$data_matrix[$row][$col]['color_code'].',endl,'.$data_matrix[$row][$col]['bomb']['value'].',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).',endl,\''.$data_matrix[$row][$col]['highest_phase_prev'].'\'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
			$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />&nbsp;';
				
			$htmlContent .= '<a style="'.$data_matrix[$row][$col]['count_start_style'].' height:100%;" id="Cell_Link_'.$online_HMCounter.'" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '&list=1&sr=now&er=1 month ago" target="_blank" title="'. $title .'"><font id="Font_ID_'.$online_HMCounter.'">'. $data_matrix[$row][$col]['active'] .'</font></a>&nbsp;';
					
			if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') //When bomb has square dont include it in pdf as size is big and no use
			{	$htmlContent .= '<img id="Cell_Bomb_'.$online_HMCounter.'" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'"  style="'.$data_matrix[$row][$col]['bomb']['style'].'" '
			.'onclick="popup_show(\'bomb\', '.count($rows).','.count($columns).',\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />'
			.'&nbsp;';				
			}
			
			
				if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
				$htmlContent .= '<img id="Cell_Filing_'.$online_HMCounter.'" src="'. $data_matrix[$row][$col]['filing_src'] .'" title="'. $data_matrix[$row][$col]['filing_start_title'] .'" style="'. $data_matrix[$row][$col]['filing_start_style'] .'" alt="Filing" onclick="popup_show(\'filing\', '.count($rows).','.count($columns).',\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				
				if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
				$htmlContent .= '<img id="Cell_Phase_'.$online_HMCounter.'" src="'. $data_matrix[$row][$col]['phaseexp_src'] .'" title="'. $data_matrix[$row][$col]['phaseexp_start_title'] .'" style="'. $data_matrix[$row][$col]['phaseexp_start_style'] .'" alt="Phase Explain" onclick="popup_show(\'phaseexp\', '.count($rows).','.count($columns).',\'phaseexppopup_'.$row.'_'.$col.'\', \'phaseexppopup_drag_'.$row.'_'.$col.'\', \'phaseexppopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';

				$htmlContent .= '</div>'; ///Div complete to avoid panel problem
					
				//All Pop-Up Form for Editing Starts Here
				$htmlContent .= '<div class="popup_form" id="allpopup_'.$row.'_'.$col.'" style="display: none;  background-color:#fff; width:440px;" >'	
							.'<div class="menu_form_header" id="allpopup_drag_'.$row.'_'.$col.'" style="width:440px;">'
							.'<img class="menu_form_exit" align="right" id="allpopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '			
							.'alt="" />&nbsp;&nbsp;&nbsp;All Details<br />'
							.'</div>'
							.'<div class="menu_form_body" style="width:440px;">'
							.'<table style="background-color:#fff; border:none;" cellpadding="5" cellspacing="5">'
							.'<tr style="background-color:#fff; border:none;" id="Last_CDate_'.$online_HMCounter.'">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.''. (($data_matrix[$row][$col]['last_update']!=NULL && $data_matrix[$row][$col]['last_update']!='') ? '<font style="color:#206040; font-weight: 900;"></br>&nbsp;Last Update On: </font><font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['last_update'])).'</font>':'') .'</br>'
							.'<td></tr>'
							.'<tr style="background-color:#fff; border:none;" id="Count_CDate_'.$online_HMCounter.'">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.'<font style="color:#206040; font-weight: 900;">&nbsp;Count Updated From: </font><font id="Popup_Count_ID_'.$online_HMCounter.'" style="color:#000000; font-weight: 900;">'. $data_matrix[$row][$col]['active_prev'] .'</font>'. (($data_matrix[$row][$col]['count_lastchanged'] != NULL && $data_matrix[$row][$col]['count_lastchanged'] != '') ? '<font style="color:#206040; font-weight: 900;"> On </font> <font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['count_lastchanged'])).'</font>' : '').'<td></tr>';
							
						$htmlContent .= '<tr style="background-color:#fff; border:none;'.(($data_matrix[$row][$col]['phase4_override'])? 'display:block;':'display:none;').'">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.'<font style="color:#206040; font-weight: 900;">&nbsp;Red cell override: </font><font style="color:#FF0000; font-weight: 900;">"ON"</font><font id="Red_Cell_'.$online_HMCounter.'">'. (($data_matrix[$row][$col]['phase4_override_lastchanged'] != NULL && $data_matrix[$row][$col]['phase4_override_lastchanged'] != '') ? '<font style="color:#206040; font-weight: 900;">&nbsp;Updated On </font> <font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).'</font>' : '').'</font>'
							.'<td></tr>';
							
							$htmlContent .= '<tr id="Highest_Phase_'.$online_HMCounter.'" style="background-color:#fff; border:none;'.(($data_matrix[$row][$col]['highest_phase_prev'] != NULL && $data_matrix[$row][$col]['highest_phase_prev'] != '')? 'display:block;':'display:none;').'">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.'<font style="color:#206040; font-weight: 900;">&nbsp;Highest Phase Updated From: </font> <font style="color:#000000; font-weight: 900;">Phase '.$data_matrix[$row][$col]['highest_phase_prev'].'</font>'. (($data_matrix[$row][$col]['highest_phase_lastchanged'] != NULL && $data_matrix[$row][$col]['highest_phase_lastchanged'] != '') ? '<font style="color:#206040; font-weight: 900;">&nbsp; On </font> <font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).'</font>' : '').'</br>'
							.'<td></tr>';
							
							
							
						if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png')
						{
							$htmlContent .= '<tr style="background-color:#fff; border:none;">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.'<font id="Bomb_CDate_'.$online_HMCounter.'">'. (($data_matrix[$row][$col]['bomb_lastchanged'] != NULL && $data_matrix[$row][$col]['bomb_lastchanged'] != '') ? '<font style="color:#206040; font-weight: 900;">&nbsp;Bomb Updated On: </font><font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).'<br/></font>' : '') .'</font><font style="color:#206040; font-weight: 900;">&nbsp;Bomb Value: </font> <font style="color:#000000; font-weight: 900;">'.$data_matrix[$row][$col]['bomb']['alt'].'<br/></font>'.(($data_matrix[$row][$col]['bomb_explain'] != NULL && $data_matrix[$row][$col]['bomb_explain'] != '')? '<font style="color:#206040; font-weight: 900;">&nbsp;Bomb Details: <br/></font><textarea align="left" readonly="readonly"  rows="5" cols="20" style="overflow:scroll; width:400px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['bomb_explain'] .'</textarea>':'' ).'</br></br>'
							.'<td></tr>';
						}
							
						if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
						{
							$htmlContent .= '<tr style="background-color:#fff; border:none;">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.'<font id="Filing_CDate_'.$online_HMCounter.'">'. (($data_matrix[$row][$col]['filing_lastchanged'] != NULL && $data_matrix[$row][$col]['filing_lastchanged'] != '') ? '<font style="color:#206040; font-weight: 900;">&nbsp;Filing Updated On: </font><font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).'<br/></font>' :'') .'</font><font style="color:#206040; font-weight: 900;">&nbsp;Filing Details: <br/></font><textarea align="left" readonly="readonly"  rows="5" cols="20" style="overflow:scroll; width:400px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['filing'] .'</textarea></br></br>'
							.'<td></tr>';
						}
						if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
						{
							$htmlContent .= '<tr style="background-color:#fff; border:none;">'
							.'<td align="left" style="background-color:#fff; border:none;">'
							.'<font id="PhaseExp_CDate_'.$online_HMCounter.'">'. (($data_matrix[$row][$col]['phase_explain_lastchanged'] != NULL && $data_matrix[$row][$col]['phase_explain_lastchanged'] != '') ? '<font style="color:#206040; font-weight: 900;">&nbsp;Phase Explain Updated On: </font><font style="color:#000000; font-weight: 900;">'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).'<br/></font>' :'') .'</font><font style="color:#206040; font-weight: 900;">&nbsp;Phase Explain: <br/></font><textarea align="left" readonly="readonly"  rows="5" cols="20" style="overflow:scroll; width:400px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['phase_explain'] .'</textarea></br>'
							.'<td></tr>';
						}
							
							$htmlContent .='</table>'
							.'</div>'
							.'</div>';	//Pop-Up Form for All Editing Ends Here
		}
		else
		{
			$htmlContent .= '';
		}
		
		$htmlContent .= '</td>';
	}//Columns For loop Ends
	
	//if total checkbox is selected
	if($toal_fld)
	{
		$htmlContent .= '<th>&nbsp;</th>';
	}
		
	$htmlContent .= '</tr>';
} //Main Data For loop ends
		
$htmlContent .= '<input type="hidden" value="'.$online_HMCounter.'" name="Last_HM" id="Last_HM" /></table></div><br /><br/>'
			. '<div align="center"><table align="center" style="vertical-align:middle; padding:10px; background-color:#DDF;">'
			. '<tr style="page-break-inside:avoid;" nobr="true"><td width="380px" align="left"><b>Footnotes: </b><br/><div style="padding-left:10px;"><br/>'. $footnotes .'</div></td>'
			. '<td width="380px" align="left"><b>Description: </b><br/><div style="padding-left:10px;"><br/>'. $description .'</div></td></tr>'
			. '</table></div>';
print $htmlContent;
?>

</body>
</html>
