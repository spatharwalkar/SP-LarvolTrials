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
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description FROM `products` WHERE id = '" . $header['type_id'] . "' "));
			$rows[$header['num']] = $result['name'];
			$rowsDisplayName[$header['num']] = $result['display_name'];
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
			$active_total=$cell_data['count_active']+$active_total;
			$count_total=$cell_data['count_total']+$count_total;
			
			if($cell_data['count_active'] != '' && $cell_data['count_active'] != NULL)
				$data_matrix[$row][$col]['active']=$cell_data['count_active'];
			else
				$data_matrix[$row][$col]['active']=0;
			
			if($cell_data['count_total'] != '' && $cell_data['count_total'] != NULL)
				$data_matrix[$row][$col]['total']=$cell_data['count_total'];
			else
				$data_matrix[$row][$col]['total']=0;
			
			$data_matrix[$row][$col]['bomb_explain']=$cell_data['bomb_explain'];
			
			$data_matrix[$row][$col]['last_update']=$cell_data['last_update'];
			
			$data_matrix[$row][$col]['start_style'] = 'title="Active Trials" style="color:#000000;"';
			
			if($data_matrix[$row][$col]['last_update'] <= date('Y-m-d', $now) && $data_matrix[$row][$col]['last_update'] >= date('Y-m-d', strtotime('-1 year', $now)))
			{
				$data_matrix[$row][$col]['update_class'] = '1 year';
			} 
			if($data_matrix[$row][$col]['last_update'] <= date('Y-m-d', $now) && $data_matrix[$row][$col]['last_update'] >= date('Y-m-d', strtotime('-3 months', $now)))
			{
				$data_matrix[$row][$col]['update_class'] = '1 quarter';
			} 
			if($data_matrix[$row][$col]['last_update'] <= date('Y-m-d', $now) && $data_matrix[$row][$col]['last_update'] >= date('Y-m-d', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['update_class'] = '1 month';
				$data_matrix[$row][$col]['start_style'] = 'title="Updated On: '.$data_matrix[$row][$col]['last_update'].'"; style="color:#FF0000;"';
			} 
			if($data_matrix[$row][$col]['last_update'] <= date('Y-m-d', $now) && $data_matrix[$row][$col]['last_update'] >= date('Y-m-d', strtotime('-2 weeks', $now)))
			{
				$data_matrix[$row][$col]['update_class'] = '2 weeks';
			} 
			if($data_matrix[$row][$col]['last_update'] <= date('Y-m-d', $now) && $data_matrix[$row][$col]['last_update'] >= date('Y-m-d', strtotime('-1 week', $now)))
			{
				$data_matrix[$row][$col]['update_class'] = '1 week';
			}
			
				
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
				$data_matrix[$row][$col]['bomb']['src']='sbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Small Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:10px; height:11px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			elseif($cell_data['bomb'] == 'large')
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='lbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:18px; height:20px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			else
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='square.png';
				$data_matrix[$row][$col]['bomb']['alt']='None';
				$data_matrix[$row][$col]['bomb']['style']='width:18px; height:20px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			
			$data_matrix[$row][$col]['filing']=$cell_data['filing'];
			
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
		}
		else
		{
			$data_matrix[$row][$col]['active']=0;
			$data_matrix[$row][$col]['total']=0;
			$col_active_total[$col]=0+$col_active_total[$col];
			$row_active_total[$row]=0+$row_active_total[$row];
			$col_count_total[$col]=0+$col_count_total[$col];
			$row_count_total[$row]=0+$row_count_total[$row];
			$data_matrix[$row][$col]['bomb_auto']['src']='';
			$data_matrix[$row][$col]['bomb']['src']='';
			$data_matrix[$row][$col]['bomb_explain']='';
			$data_matrix[$row][$col]['filing']='';
			$data_matrix[$row][$col]['color']='background-color:#DDF;';
			$data_matrix[$row][$col]['color_code']='DDF';
			$data_matrix[$row][$col]['update_class']='';
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
<style type="text/css">
body { font-family:Verdana; font-size: 14px;}
a, a:hover{color:#000000;text-decoration:none; height:100%;}
td, th {vertical-align:top; padding-top:10px; padding-left:10px; border-right: 1px solid blue; border-left:1px solid blue; border-top: 1px solid blue; border-bottom:1px solid blue;}
tr {border-right: 1px solid blue; border-left: 1px solid blue; border-top: 1px solid blue; border-bottom: 1px solid blue;}
<?php print file_get_contents('css/popup_form.css') ?>
<?php print file_get_contents('css/themes/cupertino/jquery-ui-1.8.17.custom.css') ?>
.active{font-weight:bold;}
.total{visibility:hidden;}
.comma_sep{visibility:hidden;}
</style>
<script language="javascript" type="text/javascript">
function change_view()
{
	var limit = document.getElementById('Last_HM').value;
	var view_type = document.getElementById('view_type');
	var range = document.getElementById('amount').value;
	var i=1;
	for(i=1;i<=limit;i++)
	{
		var cell_val=document.getElementById("Cell_val_"+i).value;
		var cell_val_Arr = cell_val.split(',');
		
		if(cell_val_Arr[3] != '' && cell_val_Arr[3] != null)
		var title= 'Updated On:'+ cell_val_Arr[3];
		else
		{
			if(view_type.value == 'active')
			var title='Active Trials';
			else if(view_type.value == 'total')
			var title='All Trials (Active+Inactive)';
			else if(view_type.value == 'both')
			var title='Active Trials, Total Trials';
			
			var common_title=title;
		}
		
		var disp='';
		if(range == "1 week" && (cell_val_Arr[2] == "1 week"))
		disp='<font style="color:#FF0000;" title="'+title+'">';
		else if(range == "2 weeks" && (cell_val_Arr[2] == "1 week" || cell_val_Arr[2] == "2 weeks"))
		disp='<font style="color:#FF0000;" title="'+title+'">';
		else if(range == "1 month" && (cell_val_Arr[2] == "1 week" || cell_val_Arr[2] == "2 weeks" || cell_val_Arr[2] == "1 month"))
		disp='<font style="color:#FF0000;" title="'+title+'">';
		else if(range == "1 quarter" && (cell_val_Arr[2] == "1 week" || cell_val_Arr[2] == "2 weeks" || cell_val_Arr[2] == "1 month" || cell_val_Arr[2] == "1 quarter"))
		disp='<font style="color:#FF0000;" title="'+title+'">';
		else if(range == "1 year" && (cell_val_Arr[2] == "1 week" || cell_val_Arr[2] == "2 weeks" || cell_val_Arr[2] == "1 month" || cell_val_Arr[2] == "1 quarter" || cell_val_Arr[2] == "1 year"))
		disp='<font style="color:#FF0000;" title="'+title+'">';
		else
		disp='<font style="color:#000000;" title="'+common_title+'">';
		
		if(view_type.value == 'active')
		document.getElementById("Cell_Text_"+i).innerHTML = disp+'<b>'+cell_val_Arr[0]+'</b></font>';
		else if(view_type.value == 'total')
		document.getElementById("Cell_Text_"+i).innerHTML = disp+cell_val_Arr[1]+'</font>';
		else if(view_type.value == 'both')
		document.getElementById("Cell_Text_"+i).innerHTML = disp+'<b>'+cell_val_Arr[0]+'</b>,'+cell_val_Arr[1]+'</font>';
	}
}

function timeEnum($timerange)
{
	switch($timerange)
	{
		case 1: $timerange = "1 week"; break;
		case 2: $timerange = "2 weeks"; break;
		case 3: $timerange = "1 month"; break;
		case 4: $timerange = "1 quarter"; break;
		case 5: $timerange = "1 year"; break;
	}
	
	return $timerange;
}

$(function() 
{
	$("#slider-range-min").slider({
		range: "min",
		value: 3,
		min: 1,
		max: 5,
		step:1,
		slide: function( event, ui ) {
			$("#amount").val(timeEnum(ui.value));
			change_view();
		}
	});
	$timerange = "1 month";
	$("#amount").val($timerange);
	
});
</script>
</head>

<body>
<div align="center"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="200" height="25" id="header" /></div><br/>
<?php 

$online_HMCounter=0;

$htmlContent .= '<div align="center">'
			. '<table align="center" style="border-collapse:collapse; padding:10px; background-color:#DDF;">'
			. '<tr style="page-break-inside:avoid; padding-left:10px; padding-bottom:10px; " nobr="true"><td width="380px" align="left"><b>Name: </b>'. htmlspecialchars($name) .'</td>'
			. '<td width="380px" align="left" style="padding-left:10px; padding-bottom:10px; "><b>Category: </b>'. htmlspecialchars($category) .'</td></tr>'
			. '<tr style="page-break-inside:avoid;" nobr="true"><td width="380px" align="left"><b>Display Mode: </b>'
			. '<select id="view_type" name="view_type" onchange="change_view()">'
			. '<option value="active" '.((1)?'selected="selected"':'').'>Only Active Count</option>'
			. '<option value="total">Only Total Count</option>'
			. '<option value="both">Active & Total Count</option></select></td>'
			. '<td width="380px" align="left" style="padding-left:10px; padding-bottom:10px; "><b>Highlight updates: </b>'
			. '<input type="text" id="amount" value="1 month" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold; background-color:#DDF; font-family:Verdana; font-size: 14px;" onchange="highlight_view();" />'
			. '<br/><div id="slider-range-min" style="width:320px; margin:10px 10px 0 10px;" align="right"></div></td></tr>'
			. '</table>'
			. '</div><br /><br/>';
						
$htmlContent .= '<div align="center">'
			. '<table style="border-collapse:collapse; background-color:#DDF; padding-top:5px;">'
			. '<thead><tr style="page-break-inside:avoid;" nobr="true"><th>&nbsp;</th>';
						
foreach($columns as $col => $val)
{
	$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
	$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
	$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
		
	$htmlContent .= '<th width="150px" '.$caltTitle.'><div align="center">'. $val .'<br />';
	
	if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
	{
		$online_HMCounter++;
		$count_val='<input type="hidden" value="'.$col_active_total[$col].','.$col_count_total[$col].'" name="Cell_val_'.$online_HMCounter.'" id="Cell_val_'.$online_HMCounter.'" /><font id="Cell_Text_'.$online_HMCounter.'" title="Active Trials"><b>'.$col_active_total[$col].'</b></font>';
		
		$htmlContent .= '<a href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" target="_blank">'.$count_val.'</a>';
	}
		$htmlContent .='</div></th>';
}
		
//if total checkbox is selected
if($toal_fld)
{
	$htmlContent .= '<th width="150px"><div align="center">';
	if(!empty($productIds) && !empty($areaIds))
	{
		$online_HMCounter++;
		$count_val='<input type="hidden" value="'.$active_total.','.$count_total.'" name="Cell_val_'.$online_HMCounter.'" id="Cell_val_'.$online_HMCounter.'" /><font id="Cell_Text_'.$online_HMCounter.'" title="Active Trials"><b>'.$active_total.'</b></font>';
		
		$productIds = array_filter($productIds);
		$areaIds = array_filter($areaIds);
		$htmlContent .= '<a href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '" target="_blank">'.$count_val.'</a>';
	}
	$htmlContent .= '</div></th>';
}

$htmlContent .= '</tr></thead>';
				
foreach($rows as $row => $rval)
{
	$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval;
	$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
	$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
	$htmlContent .= '<tr  style="page-break-inside:avoid;" nobr="true"><th width="150px" '.$raltTitle.'><div align="center">' . $rval . '<br />';
			
	if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
	{
		$online_HMCounter++;
		$count_val='<input type="hidden" value="'.$row_active_total[$row].','.$row_count_total[$row].'" name="Cell_val_'.$online_HMCounter.'" id="Cell_val_'.$online_HMCounter.'" /><font id="Cell_Text_'.$online_HMCounter.'" title="Active Trials"><b>'.$row_active_total[$row].'</b></font>';
		
		$htmlContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '" target="_blank" class="ottlink">'.$count_val.'</a>';
	}
	$htmlContent .= '</div></th>';
	
	foreach($columns as $col => $cval)
	{
		$htmlContent .= '<td width="150px" style="text-align:center; '.$data_matrix[$row][$col]['color'].'" align="center">&nbsp;&nbsp;&nbsp;&nbsp;';
	
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			
			$online_HMCounter++;
			$count_val='<input type="hidden" value="'.$data_matrix[$row][$col]['active'].','.$data_matrix[$row][$col]['total'].','.$data_matrix[$row][$col]['update_class'].','.$data_matrix[$row][$col]['last_update'].'" name="Cell_val_'.$online_HMCounter.'" id="Cell_val_'.$online_HMCounter.'" /><font '.$data_matrix[$row][$col]['start_style'].' id="Cell_Text_'.$online_HMCounter.'"><b>'.$data_matrix[$row][$col]['active'].'</b></font>';
			
				
			$htmlContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '" target="_blank" title="'. $title .'">'. $count_val.'</a>';
					
			if($data_matrix[$row][$col]['bomb']['src'] != 'square.png') //When bomb has square dont include it in pdf as size is big and no use
			{	$htmlContent .= '<img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'"'
			.'onclick="popup_show(\'bomb\', '.count($rows).','.count($columns).',\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';				
			}
			
			
			if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
				$htmlContent .= '<br/><br/><img align="right" title="Filing Details" src="'. urlPath() .'images/file.png" style="width:14px; height:16px; vertical-align:top; cursor:pointer; background-color:#CCCCCC;" alt="Filing" onclick="popup_show(\'filing\', '.count($rows).','.count($columns).',\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';

					
				$htmlContent .= '<div class="popup_form" id="bombpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
							.'<div class="menu_form_header" id="bombpopup_drag_'.$row.'_'.$col.'">'
							.'<img class="menu_form_exit" align="right" id="bombpopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '			
							.'alt="" />&nbsp;&nbsp;&nbsp;Bomb Details<br />'
							.'</div>'
							.'<div class="menu_form_body">'
							.'<table style="background-color:#fff; border:none;">'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Bomb: '. $data_matrix[$row][$col]['bomb']['alt'] .'<br/><br/></th></tr>';
								
							
						
				$htmlContent .= '<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Bomb Explanation:</th></tr>'
							.'<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;">'. $data_matrix[$row][$col]['bomb_explain'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>';	//Pop-Up Form for Bomb Editing Ends Here
			
						
				$htmlContent .= '<div class="popup_form" id="filingpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
							.'<div class="menu_form_header" id="filingpopup_drag_'.$row.'_'.$col.'">'
							.'<img class="menu_form_exit" align="right" id="filingpopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '		
							.'alt="" />&nbsp;&nbsp;&nbsp;Filing Details'
							.'</div>'
							.'<div class="menu_form_body">'
							.'<table style="background-color:#fff;">';
							
				$htmlContent .= '<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Filing:</th></tr>'
							.'<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;" id="filing">'. $data_matrix[$row][$col]['filing'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>';
						


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
			. '<div align="center"><table align="center" style="border-collapse:collapse; vertical-align:middle; padding:10px; background-color:#DDF;">'
			. '<tr style="page-break-inside:avoid;" nobr="true"><td width="380px" align="left"><b>Footnotes: </b><br/><div style="padding-left:10px;"><br/>'. $footnotes .'</div></td>'
			. '<td width="380px" align="left"><b>Description: </b><br/><div style="padding-left:10px;"><br/>'. $description .'</div></td></tr>'
			. '</table></div>';
print $htmlContent;
?>

</body>
</html>
