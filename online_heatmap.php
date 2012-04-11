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
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description, company FROM `products` WHERE id = '" . $header['type_id'] . "' "));
			$rows[$header['num']] = $result['name'];
			if($result['company'] != NULL && trim($result['company']) != '') $rows[$header['num']] = $result['name'].' / '.$result['company'];
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
			$col_indlead_total[$col]=$cell_data['count_active_indlead']+$col_count_indlead[$col];
			$row_indlead_total[$row]=$cell_data['count_active_indlead']+$row_count_indlead[$row];
			$active_total=$cell_data['count_active']+$active_total;
			$count_total=$cell_data['count_total']+$count_total;
			$indlead_total=$cell_data['count_indlead']+$count_indlead;
			
			$data_matrix[$row][$col]['active']=$cell_data['count_active'];
			$data_matrix[$row][$col]['total']=$cell_data['count_total'];
			$data_matrix[$row][$col]['indlead']=$cell_data['count_active_indlead'];
			
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
			
			$data_matrix[$row][$col]['cell_start_style'] = 'title="Active Trials" style="'.$data_matrix[$row][$col]['color'].'"';
			
			/////Calculate Record Update Class
			 
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['last_update'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['last_update'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['cell_start_style'] = 'title="Record Updated On: '.$data_matrix[$row][$col]['last_update'].'"; style="'.$data_matrix[$row][$col]['color'].' border:#FF0000 solid;"';
			} 
			
			/////Calculate Count Update Class
			$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
			
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['count_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				if($data_matrix[$row][$col]['active_prev']==$cell_data['count_active_prev'])
				{
					$data_matrix[$row][$col]['count_start_style'] = 'title="Active Count Changed from: '.$cell_data['count_active_prev'] .' On:'. $data_matrix[$row][$col]['count_lastchanged'].'"; style="color:#FF0000; font-weight:bold;"';
					if($data_matrix[$row][$col]['color_code']=='FF0000')
					$data_matrix[$row][$col]['count_start_style'] = 'title="Active Count Changed from: '.$cell_data['count_active_prev'] .' On:'. $data_matrix[$row][$col]['count_lastchanged'].'"; style="color:#FF0000;  background-color:#FFFFFF; font-weight:bold;"';
				}

			} 
			
			
			$data_matrix[$row][$col]['bomb']['style'] = $data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; cursor:pointer;';
				
			/////Calculate Bomb Update Class
			$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
			
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['bomb_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['bomb']['title'] = 'Bomb Updated On: '.$data_matrix[$row][$col]['bomb_lastchanged'];
				//$data_matrix[$row][$col]['bomb']['style'] = $data_matrix[$row][$col]['bomb']['style'].' border:#FF0000 solid;';
				
				if($cell_data['bomb'] == 'small')
				$data_matrix[$row][$col]['bomb']['src']='newred_sbomb.png';
				if($cell_data['bomb'] == 'large')
				$data_matrix[$row][$col]['bomb']['src']='newred_lbomb.png';
			} 
			
			/////Calculate Filling Update Class
			$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
			
			$data_matrix[$row][$col]['filing_src']='images/new_file.png';
			
			$data_matrix[$row][$col]['filing_start_style'] = 'title="Filing Details" style="width:20px; height:20px; vertical-align:top; cursor:pointer;"';
			 
			if(date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['filing_lastchanged'])) <= date('m/d/Y H:i:s', $now) && date('m/d/Y H:i:s',strtotime($data_matrix[$row][$col]['filing_lastchanged'])) >= date('m/d/Y H:i:s', strtotime('-1 month', $now)))
			{
				$data_matrix[$row][$col]['filing_src']='images/newred_file.png';
				$data_matrix[$row][$col]['filing_start_style'] = 'title="Filing Details Updated On: '.$data_matrix[$row][$col]['filing_lastchanged'].'"; style="width:20px; height:20px; vertical-align:middle; cursor:pointer;"';
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
.display td, .display th {font-weight:normal; background-color:#DDF; vertical-align:top; padding-top:10px; padding-left:10px; /*border-right: 1px solid blue; border-left:1px solid blue; border-top: 1px solid blue; border-bottom:1px solid blue;*/}
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
	}
	switch(end_range)
	{
		case 'now': ed_limit = today; break;
		case '1 week ago': ed_limit = one_week; break;
		case '2 weeks ago': ed_limit = two_week; break;
		case '1 month ago': ed_limit = one_month; break;
		case '1 quarter ago': ed_limit = three_month; break;
		case '1 year ago': ed_limit = one_year; break;
	}
		
	var i=1;
	for(i=1;i<=limit;i++)
	{
		var cell_exist=document.getElementById("Cell_values_"+i);
		if(cell_exist != null && cell_exist != '')
		{
		
			var cell_val=document.getElementById("Cell_values_"+i).value;
			var Cell_values_Arr = cell_val.split(',');
			
			
			///Change Cell Border Color
			var record_cdate= new Date(Cell_values_Arr[6]);	//Record Update Date
			
			if((record_cdate <= st_limit) && (record_cdate >= ed_limit)) //Compare Count Change Dates
			{
				document.getElementById("Cell_ID_"+i).style.border = "#FF0000 solid";
				document.getElementById("Cell_ID_"+i).title = "Record Updated On: "+ Cell_values_Arr[7];
			}
			else
			{
				if(Cell_values_Arr[14] != '' && Cell_values_Arr[14] != null && Cell_values_Arr[14] != 'undefined')
				{
					document.getElementById("Cell_ID_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
					document.getElementById("Cell_ID_"+i).title = "";
				}
			}
			
			var cell_link_val=document.getElementById("Link_value_"+i).value;
		
			/////Change Count
			var font_element=document.getElementById("Font_ID_"+i);
			
			if(view_type.value == 'active')
			{
				if(font_element != null && font_element != '')
				document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[0];
				document.getElementById("Cell_Link_"+i).href = cell_link_val+'&list=1&sr='+start_range+'&er='+end_range;
				document.getElementById("Cell_Link_"+i).title = "Active Trials";
			}
			else if(view_type.value == 'total')
			{
				if(font_element != null && font_element != '')
				document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[1];
				document.getElementById("Cell_Link_"+i).href = cell_link_val+'&list=2&sr='+start_range+'&er='+end_range;
				document.getElementById("Cell_Link_"+i).title = "Total Trials (Active + Inactive)";
			}
			else if(view_type.value == 'indlead')
			{
				if(font_element != null && font_element != '')
				document.getElementById("Font_ID_"+i).innerHTML = Cell_values_Arr[2];
				document.getElementById("Cell_Link_"+i).href = cell_link_val+'&list=1&itype=0&sr='+start_range+'&er='+end_range;
				document.getElementById("Cell_Link_"+i).title = "Active Industry Lead Sponsor Trials";
			}
			
			if(font_element != null && font_element != '')
			{
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
					}
					if(view_type.value == 'total')	//Compare Total values
					{
						document.getElementById("Cell_Link_"+i).title = "Total Count Changed from: "+ Cell_values_Arr[4] +" On: "+ Cell_values_Arr[9];
						document.getElementById("Cell_Link_"+i).style.color = "#FF0000";
						document.getElementById("Cell_Link_"+i).style.fontWeight = "bold";
						if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_Link_"+i).style.backgroundColor = "#FFFFFF";
					}
					if(view_type.value == 'active')	//Compare Industry Lead Sponsor values
					{
						document.getElementById("Cell_Link_"+i).title = "Active Count Changed from: "+ Cell_values_Arr[3] +" On: "+ Cell_values_Arr[9];
						document.getElementById("Cell_Link_"+i).style.color = "#FF0000";
						document.getElementById("Cell_Link_"+i).style.fontWeight = "bold";
						if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_Link_"+i).style.backgroundColor = "#FFFFFF";
					}
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
					}
					else
					{
						document.getElementById("Cell_Bomb_"+i).title = "Bomb Details";
						
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
					}
					else
					{
						document.getElementById("Cell_Filing_"+i).title = "Filing Details";
						//document.getElementById("Cell_Filing_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
						document.getElementById("Cell_Filing_"+i).src = "images/new_file.png";
					}
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
				. '<option value="active" selected="selected">Only Active Count</option>'
				. '<option value="total">Only Total Count</option>'
				. '<option value="indlead">Active Industry</option></select></p></td>'
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
			. '<table style="padding-top:5px;" class="display">'
			. '<thead><tr style="page-break-inside:avoid;" nobr="true"><th>&nbsp;</th>';
						
foreach($columns as $col => $val)
{
	$online_HMCounter++;
	$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
	$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
	$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
		
	$htmlContent .= '<th id="Cell_ID_'.$online_HMCounter.'" width="80px" '.$caltTitle.'>';
	
	if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
	{
		$htmlContent .= '<input type="hidden" value="'.$col_active_total[$col].','.$col_count_total[$col].','.$col_indlead_total[$col].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
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
		$htmlContent .= '<input type="hidden" value="'.$active_total.','.$count_total.','.$indlead_total.'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$productIds = array_filter($productIds);
		$areaIds = array_filter($areaIds);
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" title="Active Trials" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" target="_blank">'.$active_total.'</a>';
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
	$htmlContent .= '<tr style="page-break-inside:avoid;"><th id="Cell_ID_'.$online_HMCounter.'" '.$raltTitle.'><div align="center">';
			
	if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
	{
		$htmlContent .= '<input type="hidden" value="'.$row_active_total[$row].','.$row_count_total[$row].','.$row_indlead_total[$row].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" title="Active Trials" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" target="_blank" class="ottlink">'.$rval.'&nbsp;</a>';
	}
	$htmlContent .= '</div></th>';
	
	foreach($columns as $col => $cval)
	{
		$online_HMCounter++;
		$htmlContent .= '<td id="Cell_ID_'.$online_HMCounter.'" width="110px" '. $data_matrix[$row][$col]['cell_start_style'] .' align="center">';
	
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			
			$htmlContent .= '<input type="hidden" value="'.$data_matrix[$row][$col]['active'].','.$data_matrix[$row][$col]['total'].','.$data_matrix[$row][$col]['indlead'].','.$data_matrix[$row][$col]['active_prev'].','.$data_matrix[$row][$col]['total_prev'].','.$data_matrix[$row][$col]['indlead_prev'].','.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['last_update'])).','.$data_matrix[$row][$col]['last_update'].','.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['count_lastchanged'])).','.$data_matrix[$row][$col]['count_lastchanged'].','.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).','.$data_matrix[$row][$col]['bomb_lastchanged'].','.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).','.$data_matrix[$row][$col]['filing_lastchanged'].','.$data_matrix[$row][$col]['color_code'].','.$data_matrix[$row][$col]['bomb']['value'].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
			$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />&nbsp;';
				
			$htmlContent .= '<a '.$data_matrix[$row][$col]['count_start_style'].' id="Cell_Link_'.$online_HMCounter.'" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '&list=1&sr=now&er=1 month ago" target="_blank" title="'. $title .'"><font id="Font_ID_'.$online_HMCounter.'">'. $data_matrix[$row][$col]['active'] .'</font></a>&nbsp;';
					
			if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') //When bomb has square dont include it in pdf as size is big and no use
			{	$htmlContent .= '<img id="Cell_Bomb_'.$online_HMCounter.'" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'"  style="'.$data_matrix[$row][$col]['bomb']['style'].'" '
			.'onclick="popup_show(\'bomb\', '.count($rows).','.count($columns).',\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';				
			}
			
			
			if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
				$htmlContent .= '<img id="Cell_Filing_'.$online_HMCounter.'" src="'. $data_matrix[$row][$col]['filing_src'] .'" '. $data_matrix[$row][$col]['filing_start_style'] .' alt="Filing" onclick="popup_show(\'filing\', '.count($rows).','.count($columns).',\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				
				if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
				$htmlContent .= '<img id="Cell_Phase_'.$online_HMCounter.'" src="'. urlPath() .'images/phaseexp.png" title="Phase Explain" style="width: 20px; height: 20px; vertical-align: middle; cursor: pointer;" alt="Phase Explain" onclick="popup_show(\'phaseexp\', '.count($rows).','.count($columns).',\'phaseexppopup_'.$row.'_'.$col.'\', \'phaseexppopup_drag_'.$row.'_'.$col.'\', \'phaseexppopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';

					
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
							.'</div>'; ///Pop-up Form for Filing Ends Here
							
				$htmlContent .= '<div class="popup_form" id="phaseexppopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
							.'<div class="menu_form_header" id="phaseexppopup_drag_'.$row.'_'.$col.'">'
							.'<img class="menu_form_exit" align="right" id="phaseexppopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '		
							.'alt="" />&nbsp;&nbsp;&nbsp;Phase Explain'
							.'</div>'
							.'<div class="menu_form_body">'
							.'<table style="background-color:#fff;">';
							
				$htmlContent .= '<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Phase Explain:</th></tr>'
							.'<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;" id="filing">'. $data_matrix[$row][$col]['phase_explain'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>'; ///Pop-up Form for Phase Explain Ends Here			
						


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
