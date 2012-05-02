<?php
require_once('db.php');
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
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
$name = $res['name'];
$footnotes = htmlspecialchars($res['footnotes']);
$description = htmlspecialchars($res['description']);
$category = $res['category'];
	
$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
$rows = array();
$columns = array();
$areaIds = array();
$productIds = array();
$columnsDisplayName = array();
$rowsDisplayName = array();

$rows_category = array('');
$columns_category = array('');
$columns_categorySpan  = array();
$rows_categorySpan = array();
while($header = mysql_fetch_array($res))
{
	if($header['type'] == 'area')
	{
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, display_name, description FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
			$columns[$header['num']] = $result['name'];
			//$columnsDisplayName[$header['num']] = $result['display_name'];
			$columnsDisplayName[$header['num']] = $header['display_name'];	///Display name from master hm header table
			$columnsDescription[$header['num']] = $result['description'];
			$header['category']=trim($header['category']);
			if($header['category'] == NULL || trim($header['category']) == '')
			$header['category'] = 'Undefined';
		}
		else
		{
			$columns[$header['num']] = $header['type_id'];
			
			$header['category'] = 'Undefined';
		}
		$areaIds[$header['num']] = $header['type_id'];
		$columns_categoryArr[$header['category']] = $header['category'];
		$columns_categorySpan[$header['category']] = $columns_categorySpan[$header['category']]+1;
		$columnsCategoryName[$header['num']] = $header['category'];
	}
	else
	{
		if($header['type_id'] != NULL)
		{
			$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `products` WHERE id = '" . $header['type_id'] . "' "));
			$rows[$header['num']] = $result['name'];
			if($result['company'] != NULL && trim($result['company']) != '') $rows[$header['num']] = $result['name'].' / '.$result['company'];
			$rowsDescription[$header['num']] = $result['description'];
			$header['category']=trim($header['category']);
			if($header['category'] == NULL || trim($header['category']) == '')
			$header['category'] = 'Undefined';
		}
		else
		{
			$rows[$header['num']] = $header['type_id'];
			
			$header['category'] = 'Undefined';
		}
		$productIds[$header['num']] = $header['type_id'];
		$rows_categoryArr[$header['category']] = $header['category'];
		$rows_categorySpan[$header['category']] = $rows_categorySpan[$header['category']]+1;
		$rowsCategoryName[$header['num']] = $header['category'];
		$rowsCategoryPrintStatus[$header['category']] = 0;
	}
}

/////Rearrange Data according to Category //////////
$new_rows = array();
$new_columns = array();
foreach($columns_categoryArr as $columns_category)
{
	foreach($columns as $col => $cval)
	{
		if($columnsCategoryName[$col] == $columns_category)
		{
			$new_columns[$col]=$cval;
		}
	}
}

foreach($rows_categoryArr as $rows_category)
{
	foreach($rows as $row => $rval)
	{
		if($rowsCategoryName[$row] == $rows_category)
		{
			$new_rows[$row]=$rval;
		}
	}
}
$rows=$new_rows;
$columns=$new_columns;
/////Rearrange Completes //////////

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
				$data_matrix[$row][$col]['bomb']['style']='width:17px; height:17px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			elseif($cell_data['bomb'] == 'large')
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
				$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
				$data_matrix[$row][$col]['bomb']['style']='width:17px; height:17px;';
				$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
			}
			else
			{
				$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
				$data_matrix[$row][$col]['bomb']['src']='new_square.png';
				$data_matrix[$row][$col]['bomb']['alt']='None';
				$data_matrix[$row][$col]['bomb']['style']='width:17px; height:17px;';
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
			//$data_matrix[$row][$col]['cell_start_title'] = 'Active Trials';
			
			$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
			$data_matrix[$row][$col]['bomb']['style'] = $data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; cursor:pointer;';
			$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
			$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
			$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
			
			
			
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
<?php if($db->loggedIn()) { //No Date-Picker for NON-LoggedIN Users  ?>	
<script type="text/javascript" src="date/jquery.date_input.js"></script>
<script type="text/javascript" src="scripts/date/jquery.jdpicker.js"></script>
<script type="text/javascript" src="date/init.js"></script>
<?php } ?>
<link href="date/date_input.css" rel="stylesheet" type="text/css" media="all" />
<link href="scripts/date/jdpicker.css" rel="stylesheet" type="text/css" media="screen" />
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="screen" />
<style type="text/css">
body { font-family:Verdana; font-size: 13px;}
a, a:hover{color:#000000;text-decoration:none; height:100%;}
.display td, .display th {font-weight:normal; background-color:#DDF; vertical-align:middle;}
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
<style type="text/css">
.tooltip {
	border-bottom: 1px dotted #000000; color: #000000; outline: none;
	cursor:default; text-decoration: none;
}
.tooltip span {
	border-radius: 5px 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; 
	box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1); -webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1); -moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);
	font-family:Verdana; font-size: 12px;
	position: absolute; 
	margin-left: 0; width: 280px; display: none; z-index: 0;
}
.classic { padding: 0.8em 1em; }
.classic {background: #FFFFAA; border: 1px solid #FFAD33; }

#slideout {
	position: fixed;
	top: 40px;
	right: 0;
	margin: 12px 0 0 0;
}

.slideout_inner {
	position:absolute;
	top: 40px;
	right: -255px;
	display:none;
}

#slideout:hover .slideout_inner{
	display : block;
	position:absolute;
	top: 2px;
	right: 0px;
	width: 280px;
	z-index:10;
}

.table-slide{
	border:1px solid #000;
}
.table-slide td{
	border-right:1px solid #000;
	padding:8px;
	padding-right:20px;
	border-bottom:1px solid #000;
}

.gray {
	background-color:#CCCCCC;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.blue {
	background-color:#00ccff;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.green {
	background-color:#99cc00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.yellow {
	background-color:#ffff00;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.orange {
	background-color:#ff9900;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.red {
	background-color:#ff0000;
	width: 35px;
	height: 18px;
	float: left;
	text-align: center;
	margin-right: 1px;
	padding-top:3px;
}

.box_rotate {
	-moz-transform: rotate(270deg); /* For Firefox */
	-o-transform: rotate(270deg); /* For Opera */
	-webkit-transform: rotate(270deg); /* For Safari and Chrome */
	writing-mode: tb-rl; /* For IE */
	max-width:20px;
	max-height:400px;
	margin:2px;
	height:auto;
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
					}
					
				}	
			}
		
			
			
			if(font_element != null && font_element != '')
			{
				
				///Change Cell Border Color
				var record_cdate= new Date(Cell_values_Arr[6]);	//Record Update Date
				
				
				/*if((record_cdate <= st_limit) && (record_cdate >= ed_limit)) //Compare record Change Dates
				{
					document.getElementById("Cell_ID_"+i).style.border = "#FF0000 solid";
					//if(Cell_values_Arr[14]=='FF0000')
					document.getElementById("Cell_ID_"+i).style.backgroundColor = "#FFFFFF";
					document.getElementById("Div_ID_"+i).title = "Record Updated On: "+ Cell_values_Arr[7];
					
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
						document.getElementById("Div_ID_"+i).title = "";
						//if(Cell_values_Arr[14]=='FF0000')
						document.getElementById("Cell_ID_"+i).style.backgroundColor = "#"+Cell_values_Arr[14];
					}
				}*/
				
				
				
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
					document.getElementById("Count_CDate_"+i).style.display = "inline";
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
					document.getElementById("Count_CDate_"+i).style.display = "none"
				}
					
				///Change Bomb Color
				var bomb_cdate= new Date(Cell_values_Arr[10]);	//Bomb Chnage Date
				var bomb_ele= document.getElementById("Cell_Bomb_"+i);	//Bomb Element
				
				if(bomb_ele != null && bomb_ele != '')
				{
					if((bomb_cdate <= st_limit) && (bomb_cdate >= ed_limit)) //Compare Bomb Change Dates
					{
						document.getElementById("Cell_Bomb_"+i).title = "Bomb Data Updated On: "+ Cell_values_Arr[11];
						
						if(Cell_values_Arr[15] == 'large')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/newred_lbomb.png";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Large Bomb" src="images/newred_lbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
						else if(Cell_values_Arr[15] == 'small')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/newred_sbomb.png";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Small Bomb" src="images/newred_sbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
						//document.getElementById("Bomb_CDate_"+i).style.display = "inline";
						
						if(latest_date < bomb_cdate || latest_date == '')
						{
							qualify_title = "Bomb Data Updated On: "+ Cell_values_Arr[11];
							latest_date = bomb_cdate;
						}
					}
					else
					{
						//document.getElementById("Bomb_CDate_"+i).style.display = "none"
						
						if(Cell_values_Arr[15] == 'large')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/new_lbomb.png";
							document.getElementById("Cell_Bomb_"+i).title = "Large Bomb";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Large Bomb" src="images/new_lbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
						else if(Cell_values_Arr[15] == 'small')
						{
							document.getElementById("Cell_Bomb_"+i).src = "images/new_sbomb.png";
							document.getElementById("Cell_Bomb_"+i).title = "Small Bomb";
							document.getElementById("Bomb_Img_"+i).innerHTML = '<img title="Small Bomb" src="images/new_sbomb.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						}
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
						document.getElementById("Cell_Filing_"+i).src = "images/newred_file.png";
						//document.getElementById("Filing_CDate_"+i).style.display = "inline";
						document.getElementById("Filing_Img_"+i).innerHTML = '<img title="Filing" src="images/newred_file.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						if(latest_date < filing_cdate || latest_date == '')
						{
							qualify_title = "Filing Data Updated On: "+ Cell_values_Arr[13];
							latest_date = filing_cdate;
						}
					}
					else
					{
						document.getElementById("Cell_Filing_"+i).title = "Filing Details";
						document.getElementById("Cell_Filing_"+i).src = "images/new_file.png";
						//document.getElementById("Filing_CDate_"+i).style.display = "none"
						document.getElementById("Filing_Img_"+i).innerHTML = '<img title="Filing" src="images/new_file.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
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
						document.getElementById("Cell_Phase_"+i).src = "images/phaseexp_red.png";
						//document.getElementById("PhaseExp_CDate_"+i).style.display = "inline";
						document.getElementById("Phaseexp_Img_"+i).innerHTML = '<img title="Phase Explain" src="images/phaseexp_red.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
						if(latest_date < phaseexp_cdate || latest_date == '')
						{
							qualify_title = "Phase Explain Updated On: "+ Cell_values_Arr[17];
							latest_date = phaseexp_cdate;
						}
					}
					else
					{
						document.getElementById("Cell_Phase_"+i).title = "Phase Explain";
						document.getElementById("Cell_Phase_"+i).src = "images/phaseexp.png";
						//document.getElementById("PhaseExp_CDate_"+i).style.display = "none"
						document.getElementById("Phaseexp_Img_"+i).innerHTML = '<img title="Phase Explain" src="images/phaseexp.png"  style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" />&nbsp;';
					}
				}
				
				///Change Phase4 Details
				var phase4_cdate= new Date(Cell_values_Arr[18]);	//Phase4 Chnage Date
				var phase4_ele= document.getElementById("Red_Cell_"+i);	//Phase4 Element
				
				if(phase4_ele != null && phase4_ele != '')
				{
					var phase4_presence_ele= document.getElementById("Phase4_Presence_"+i);
					if((phase4_cdate <= st_limit) && (phase4_cdate >= ed_limit)) //Compare Filing Change Dates
					{
						document.getElementById("Red_Cell_"+i).style.display = "inline";
						document.getElementById("Red_Cell_"+i).title = "Red Cell Override";
						//document.getElementById("Red_Cell_CDate_"+i).style.display = "inline";
						if(latest_date < phase4_cdate || latest_date == '')
						{
							qualify_title = "Red Cell Override On: "+ Cell_values_Arr[19];
							latest_date = phase4_cdate;
						}
					}
					else
					{
						document.getElementById("Red_Cell_"+i).title = "Red Cell Override";
						document.getElementById("Red_Cell_"+i).style.display = "none";
						//document.getElementById("Red_Cell_CDate_"+i).style.display = "none";
						document.getElementById("Red_Cell_"+i).style.display = "inline";
					}
				}
				
				///Change Hign Phase Details
				var high_phase_cdate= new Date(Cell_values_Arr[20]);	//High Phase Chnage Date
				var high_phase_ele= document.getElementById("Highest_Phase_"+i);	//high phase Element
				
				if(high_phase_ele != null && high_phase_ele != '')
				{
					if((high_phase_cdate <= st_limit) && (high_phase_cdate >= ed_limit)) //Compare highest phase Change Dates
					{
						document.getElementById("Highest_Phase_"+i).style.display = "inline";
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
						document.getElementById("Highest_Phase_"+i).style.display = "none"
					}
				}
				
				if(qualify_title != '')
				{	
					document.getElementById("ToolTip_Visible_"+i).value = "1"
					
					document.getElementById("Cell_ID_"+i).style.border = "#FF0000 solid";
					//if(Cell_values_Arr[14]=='FF0000')
					document.getElementById("Cell_ID_"+i).style.backgroundColor = "#FFFFFF";
					
					document.getElementById("Div_ID_"+i).title = '';
					document.getElementById("Cell_Link_"+i).title = '';
					if(bomb_ele != null && bomb_ele != '')
					document.getElementById("Cell_Bomb_"+i).title = '';
					if(filing_ele != null && filing_ele != '')
					document.getElementById("Cell_Filing_"+i).title = '';
					if(phaseexp_ele != null && phaseexp_ele != '')
					document.getElementById("Cell_Phase_"+i).title = '';
				}
				else
				{
					var bomb_presence_ele= document.getElementById("Bomb_Presence_"+i);
					if((phaseexp_ele != null && phaseexp_ele != '') || (filing_ele != null && filing_ele != '') || (bomb_presence_ele != null && bomb_presence_ele != '') || (phase4_ele != null && phase4_ele != ''))
					{
						document.getElementById("ToolTip_Visible_"+i).value = "1"
					}
					else
					{
						document.getElementById("ToolTip_Visible_"+i).value = "0"
					}
					document.getElementById("Cell_ID_"+i).style.border = "#"+Cell_values_Arr[14]+" solid";
					document.getElementById("Cell_ID_"+i).style.backgroundColor = "#"+Cell_values_Arr[14];
					document.getElementById("Div_ID_"+i).title = '';
					document.getElementById("Cell_Link_"+i).title = '';
					if(bomb_ele != null && bomb_ele != '')
					document.getElementById("Cell_Bomb_"+i).title = '';
					if(filing_ele != null && filing_ele != '')
					document.getElementById("Cell_Filing_"+i).title = '';
					if(phaseexp_ele != null && phaseexp_ele != '')
					document.getElementById("Cell_Phase_"+i).title = '';
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

<?php if(!$db->loggedIn()) { ?>		
		$("#slider-range-min").slider({	//Single Slider - For NOT LoogedIN Users
		range: "min",
		value: 3,
		min: 0,
		max: 5,
		step:1,
		slide: function( event, ui ) {
			$("#endrange").val(timeEnum(ui.value));
			change_view();
		}
	});
	$timerange = "1 month ago";
	$("#endrange").val($timerange);
<?php } else { ?>
//highlight changes slider
		$("#slider-range-min").slider({	//Double Slider - For LoggedIN Users
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
<?php } ?>

	
});

function display_tooltip(type, id)
{
	var tooltip_ele = document.getElementById("ToolTip_ID_"+id);
	var tooltip_val_ele = document.getElementById("ToolTip_Visible_"+id);
	if((tooltip_ele != null && tooltip_ele != '') && (tooltip_val_ele != null && tooltip_val_ele != ''))
	{
		if(type =='on' && tooltip_val_ele.value==1)
		{
			tooltip_ele.style.display = "block";
			tooltip_ele.style.zIndex = "99";
		}
		else
		{
			tooltip_ele.style.display = "none";
			tooltip_ele.style.zIndex = "0";
		}
	}
}
</script>
<script type="text/javascript">
	//Count the Number of View of Records
	function INC_ViewCount(product, area, cell_id)
	{
		 $.ajax({
						type: 'GET',
						url:  'viewcount.php' + '?op=Inc_OHM_ViewCount&product=' + product +'&area=' + area + '&Cell_ID=' + cell_id,
						success: function (data) {
	        					//alert(data);
	        					//$("#ViewCount_"+cell_id).html(data);
	        		   }
				});
	        return;
	}
	</script>
</head>

<body>
<div id="slideout">
    <img src="images/help.png" alt="Help" />
    <div class="slideout_inner">
        <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" class="table-slide">
        <tr><td width="15%"><img title="Large Bomb" src="images/new_lbomb.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Large Bomb</td></tr>
        <tr><td><img title="Large Bomb" src="images/newred_lbomb.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Large Bomb (Updated)</td></tr>
        <tr><td><img title="Small Bomb" src="images/new_sbomb.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Small Bomb</td></tr>
        <tr><td><img title="Large Bomb" src="images/newred_sbomb.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Small Bomb (Updated)</td></tr>
        <tr><td><img title="Filing" src="images/new_file.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Filing Details</td></tr>
        <tr><td><img title="Filing" src="images/newred_file.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Filing Details (Updated)</td></tr>
        <tr><td><img title="Phase Explain" src="images/phaseexp.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Phase Explain</td></tr>
        <tr><td><img title="Phase Explain" src="images/phaseexp_red.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Phase Explain (Updated)</td></tr>
        <tr><td><img title="Red Border" src="images/outline.png"  style="width:17px; height:17px; cursor:pointer;" /></td><td>Red Border (Record Updated)</td></tr>
        <tr><td colspan="2" style="padding-right: 1px;">
         <div style="float:left;padding-top:3px;">Phase&nbsp;</div>
         <div class="gray">N/A</div>
         <div class="blue">0</div>
         <div class="green">1</div>
         <div class="yellow">2</div>
         <div class="orange">3</div>
         <div class="red">4</div>
         </td></tr>
        </table>
    </div>
</div>

<?php 

$online_HMCounter=0;

$htmlContent .= '<table width="100%" style="background-color:#FFFFFF;">'
				. '<tr><td style="background-color:#FFFFFF;"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" id="header" /></td>'
				. '<td style="background-color:#FFFFFF;" nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface Work In Progress</span>'
				. '<br/><span style="font-weight:normal;">Send feedback to '
				. '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
				. 'larvoltrials@larvol.com</a></span></td>'
				. '<td style="background-color:#FFFFFF;" class="result">Name: ' . htmlspecialchars($name) . '</td></tr></table><br/>'
				
				. '<table width="900px" border="0" cellspacing="0" class="controls" align="center">'
				. '<tr><th>View Mode</th><th>Range</th><th class="right">Download Option</th></tr>'
				. '<tr>'
				. '<td class="bottom"><p style="margin-top:10px;margin-right:5px;"><select id="view_type" name="view_type" onchange="change_view()">'
				. '<option value="indlead" selected="selected">Active Industry Trials</option>'
				. '<option value="active">Active Trials</option>'
				. '<option value="total">All Trials</option></select></p></td>'
				. '<td style="background-color:#FFFFFF; width:380px;" class="bottom"><div class="demo"><p style="margin-top:10px;">'
				. '<label for="startrange" style="float:left;margin-left:15px;"><b>Highlight updates:</b></label>'
				. '<input type="text" id="startrange" name="sr" value="now" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold; background-color:#FFFFFF; font-family:Verdana; font-size: 13px;" class="jdpicker" />'
				. '<label style="color:#f6931f;float:left;">-</label> '
				. '<input type="text" id="endrange"  name="er" value="1 month ago" readonly="readonly" style="border:0; color:#f6931f; font-weight:bold; background-color:#FFFFFF; font-family:Verdana; font-size: 13px;" class="jdpicker" />'
				. '<br/><div id="slider-range-min" style="width:320px; margin:10px 10px 0 10px;margin-left:20px;" align="left"></div></p></div></td>'
				. '<td style="background-color:#FFFFFF;" class="bottom right"><p style="margin-top:10px;margin-left:5px;">'
				. '<form action="master_heatmap.php" method="post">'
				. '<input type="hidden" name="id" value="' . $id . '" />'
				. '<b style="margin-left:5px;">Which Format: </b><select id="dwformat" name="dwformat">'
				. '<option value="exceldown" selected="selected">Excel</option>'
				. '<option value="pdfdown">PDF</option>'
				. '</select><br/><br/>'
				. '<b style="margin-left:5px;">Counts Display: </b><select id="dwcount" name="dwcount">'
				. '<option value="indlead" selected="selected">Active Industry Trials</option>'
				. '<option value="active">Active Trials</option>'
				. '<option value="total">All Trials</option>'
				. '</select><br/><br/><input type="submit" name="download" value="Download" title="Download" style="margin-left:5px;" />'
				. '</form>'	
				. '</p></td>'
				. '</tr>'
				. '</table>'
				. '<br clear="all"/><br/>';
						
$htmlContent .= '<div align="center">'
			. '<table style="padding-top:5px; height:100%; vertical-align:middle;" class="display">'
			. '<thead><tr style="page-break-inside:avoid; height:100%;" nobr="true"><th style="background-color:#FFFFFF;">&nbsp;</th><th style="background-color:#FFFFFF;">&nbsp;</th>';
						
foreach($columns_categoryArr as $columns_category)
{
	$online_HMCounter++;
	$htmlContent .= '<th style="background-color:#FFFFFF; '.(($columns_category != 'Undefined') ? 'border-left:#000000 solid 2px; border-top:#000000 solid 2px; border-right:#000000 solid 2px;':'').'" id="Cell_ID_'.$online_HMCounter.'" colspan="'.$columns_categorySpan[$columns_category].'" width="80px" '.$caltTitle.'><b>'.(($columns_category != 'Undefined') ? $columns_category:'').'</b></th>';
}

$htmlContent .= '</tr><tr style="page-break-inside:avoid; height:100%;" nobr="true"><th style="background-color:#FFFFFF;">&nbsp;</th><th>&nbsp;</th>';


foreach($columns as $col => $val)
{
	$online_HMCounter++;
	$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
	$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
	$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
	$cat = (isset($columnsCategoryName[$col]) && $columnsCategoryName[$col] != '')? ' ('.$columnsCategoryName[$col].') ':'';
		
	$htmlContent .= '<th id="Cell_ID_'.$online_HMCounter.'" width="80px" '.$caltTitle.'>';
	
	if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
	{
		$htmlContent .= '<input type="hidden" value="'.$col_active_total[$col].',endl,'.$col_count_total[$col].',endl,'.$col_indlead_total[$col].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" target="_blank">'.$val.'</a>';
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
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" target="_blank"><font id="Tot_ID_'.$online_HMCounter.'">'.$active_total.'</font></a>';
	}
	$htmlContent .= '</div></th>';
}

$htmlContent .= '</tr></thead>';
				
foreach($rows as $row => $rval)
{
	
	$cat = (isset($rowsCategoryName[$row]) && $rowsCategoryName[$row] != '')? $rowsCategoryName[$row]:'Undefined';
	
	$height="100%";
	if($cat != 'Undefined')
	$height=((strlen($rowsCategoryName[$row])*8)/$rows_categorySpan[$cat]).'px'; //Calculate height
	$htmlContent .= '<tr height="'.$height.'" style="page-break-inside:avoid; vertical-align:middle; max-height:100%;">';
	
	if($rowsCategoryPrintStatus[$cat] == 0)
	{
		$online_HMCounter++;
		$htmlContent .='<th align="left" style="vertical-align:bottom; padding-top:4px; background-color:#FFFFFF; '.(($cat != 'Undefined') ? ' border-left:#000000 solid 2px; border-top:#000000 solid 2px; border-bottom:#000000 solid 2px;' : '' ).'" rowspan="'.$rows_categorySpan[$cat].'" id="Cell_ID_'.$online_HMCounter.'"><div class="box_rotate"><b>'.(($cat != 'Undefined') ? $cat:'').'</b></div></th>';
		$rowsCategoryPrintStatus[$cat] = 1;
	}
	
	$online_HMCounter++;
	//$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval; //Commente as as planned to ignore display name in Product only
	$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
	$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
	
	
	
	
	$htmlContent .='<th class="product_col" style="padding-left:4px; height:auto; vertical-align:middle;" id="Cell_ID_'.$online_HMCounter.'" '.$raltTitle.'><div align="left" style="vertical-align:middle; height:100%;">';
			
	if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
	{
		$htmlContent .= '<input type="hidden" value="'.$row_active_total[$row].',endl,'.$row_count_total[$row].',endl,'.$row_indlead_total[$row].'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
		$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />';
		
		$htmlContent .= '<a id="Cell_Link_'.$online_HMCounter.'" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '&list=1&sr=now&er=1 month ago" target="_blank" class="ottlink">'.$rval.'&nbsp;</a>';
	}
	$htmlContent .= '</div></th>';
	
	foreach($columns as $col => $cval)
	{
		$online_HMCounter++;
		$htmlContent .= '<td class="tooltip" valign="middle" id="Cell_ID_'.$online_HMCounter.'" style="'. (($data_matrix[$row][$col]['total'] != 0) ? $data_matrix[$row][$col]['cell_start_style'] : 'background-color:#d9d9d9; border:#d9d9d9 solid;') .' padding:1px; min-width:110px; height:100%; vertical-align:middle; text-align:center; " align="center" onmouseover="display_tooltip(\'on\','.$online_HMCounter.');" onmouseout="display_tooltip(\'off\','.$online_HMCounter.');">';
	
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL && $data_matrix[$row][$col]['total'] != 0)
		{
			
			$htmlContent .= '<div id="Div_ID_'.$online_HMCounter.'" style="'.$data_matrix[$row][$col]['div_start_style'].' width:100%; height:100%;  vertical-align:middle; float:none; display:table;">';
			
			$htmlContent .= '<input type="hidden" value="'.$data_matrix[$row][$col]['active'].',endl,'.$data_matrix[$row][$col]['total'].',endl,'.$data_matrix[$row][$col]['indlead'].',endl,'.$data_matrix[$row][$col]['active_prev'].',endl,'.$data_matrix[$row][$col]['total_prev'].',endl,'.$data_matrix[$row][$col]['indlead_prev'].',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['last_update'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['last_update'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['count_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['count_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['bomb_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['filing_lastchanged'])).',endl,'.$data_matrix[$row][$col]['color_code'].',endl,'.$data_matrix[$row][$col]['bomb']['value'].',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase_explain_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['phase4_override_lastchanged'])).',endl,'.date('m/d/Y H:i:s', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).',endl,'.date('F d, Y', strtotime($data_matrix[$row][$col]['highest_phase_lastchanged'])).',endl,\''.$data_matrix[$row][$col]['highest_phase_prev'].'\'" name="Cell_values_'.$online_HMCounter.'" id="Cell_values_'.$online_HMCounter.'" />';
			
			$htmlContent .= '<input type="hidden" value="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '" name="Link_value_'.$online_HMCounter.'" id="Link_value_'.$online_HMCounter.'" />&nbsp;';
				
			$htmlContent .= '<a onclick="INC_ViewCount(' . trim($productIds[$row]) . ',' . trim($areaIds[$col]) . ',' . $online_HMCounter .')" style="'.$data_matrix[$row][$col]['count_start_style'].' height:100%; vertical-align:middle; padding-top:0px; padding-bottom:0px; line-height:13px;" id="Cell_Link_'.$online_HMCounter.'" href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '&list=1&sr=now&er=1 month ago" target="_blank" title="'. $title .'"><font id="Font_ID_'.$online_HMCounter.'">'. $data_matrix[$row][$col]['active'] .'</font></a>&nbsp;';
					
			if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') //When bomb has square dont include it in pdf as size is big and no use
			$htmlContent .= '<img id="Cell_Bomb_'.$online_HMCounter.'" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'"  style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle;" />&nbsp;';				
			
			
			
			if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
			$htmlContent .= '<img id="Cell_Filing_'.$online_HMCounter.'" src="images/new_file.png" title="Filing Details" style="width:17px; height:17px; vertical-align:middle; cursor:pointer;" alt="Filing" />&nbsp;';
				
			if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
			$htmlContent .= '<img id="Cell_Phase_'.$online_HMCounter.'" src="images/phaseexp.png" title="Phase Explain" style="width:17px; height:17px; vertical-align:middle; cursor:pointer;" alt="Phase Explain" />&nbsp;';

			$htmlContent .= '</div>'; ///Div complete to avoid panel problem
					
			//Tool Tip Starts Here
			$htmlContent .= '<span id="ToolTip_ID_'.$online_HMCounter.'" class="classic" style="text-align:left;">'
							.'<input type="hidden" value="0" name="ToolTip_Visible_'.$online_HMCounter.'" id="ToolTip_Visible_'.$online_HMCounter.'" />';	
				
			$htmlContent .= '<font id="Count_CDate_'.$online_HMCounter.'" style="'.(($data_matrix[$row][$col]['active_prev'] != NULL && $data_matrix[$row][$col]['active_prev'] != '')? 'display:inline;':'display:none;').'"><font style="color:#206040; font-weight: 900;">Count </font><font style="color:#206040; font-weight: 900;">Updated From : </font><font id="Popup_Count_ID_'.$online_HMCounter.'" style="color:#000000; font-weight: 900;">'. $data_matrix[$row][$col]['active_prev'] .'</font><br/></font>';
							
			if($data_matrix[$row][$col]['phase4_override'])	
			$htmlContent .= '<font id="Red_Cell_'.$online_HMCounter.'"><font style="color:#206040; font-weight: 900;""><img src="images/phase4.png" title="Red Cell Override" style="width:17px; height:17px; vertical-align:middle; cursor:pointer; padding-bottom:2px;" alt="Red Cell Override" />&nbsp;</font></font><font style="color:#FF0000; font-weight: 900;"><font style="color:#206040; font-weight: 900;">: </font>'. (($data_matrix[$row][$col]['phase4_override'])? '"ON"<input type="hidden" value="1" name="Phase4_Presence_'.$online_HMCounter.'" id="Phase4_Presence_'.$online_HMCounter.'" />':'"OFF"<input type="hidden" value="0" name="Phase4_Presence_'.$online_HMCounter.'" id="Phase4_Presence_'.$online_HMCounter.'" />').'</font><br/></font>';
							
			if($data_matrix[$row][$col]['highest_phase_prev'] != NULL && $data_matrix[$row][$col]['highest_phase_prev'] != '')
			$htmlContent .= '<font id="Highest_Phase_'.$online_HMCounter.'"><font style="color:#206040; font-weight: 900;">Highest Phase Updated </font><font style="color:#206040; font-weight: 900;">From : </font> <font style="color:#000000; font-weight: 900;">Phase '.$data_matrix[$row][$col]['highest_phase_prev'].'</font></br></font>';
							
							
							
			if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png')
			{
				$htmlContent .= '<font style="color:#000000; font-weight: 900;" id="Bomb_Img_'.$online_HMCounter.'">'.$data_matrix[$row][$col]['bomb']['alt'].' </font>'.(($data_matrix[$row][$col]['bomb_explain'] != NULL && $data_matrix[$row][$col]['bomb_explain'] != '')? '<font style="color:#206040; font-weight: 900;">: </font>'. $data_matrix[$row][$col]['bomb_explain'] .'<input type="hidden" value="1" name="Bomb_Presence_'.$online_HMCounter.'" id="Bomb_Presence_'.$online_HMCounter.'" />':'' ).'</br>';
			}
							
			if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
			{
				$htmlContent .= '<font style="color:#206040; font-weight: 900;" id="Filing_Img_'.$online_HMCounter.'">Filing </font><font style="color:#206040; font-weight: 900;">: </font>'. $data_matrix[$row][$col]['filing'] .'</br>';
			}
			
			if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
			{
				$htmlContent .= '<font style="color:#206040; font-weight: 900;" id="Phaseexp_Img_'.$online_HMCounter.'">Phase Explain </font><font style="color:#206040; font-weight: 900;">: </font>'. $data_matrix[$row][$col]['phase_explain'] .'</br>';
			}
							
			$htmlContent .='</span>';	//Tool Tip Ends Here
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
		
$htmlContent .= '<input type="hidden" value="'.$online_HMCounter.'" name="Last_HM" id="Last_HM" /></table></div><br /><br/>';

if(($footnotes != NULL && trim($footnotes) != '') && ($description != NULL && trim($description) != ''))
{
	$htmlContent .='<div align="center"><table align="center" style="vertical-align:middle; padding:10px; background-color:#DDF;">'
				. '<tr style="page-break-inside:avoid;" nobr="true">'
				. '<td width="380px" align="left">'. (($footnotes != NULL && trim($footnotes) != '') ? '<b>Footnotes: </b><br/><div style="padding-left:10px;"><br/>'. $footnotes .'</div>' : '' ).'</td>'
				. '<td width="380px" align="left">'. (($description != NULL && trim($description) != '') ? '<b>Description: </b><br/><div style="padding-left:10px;"><br/>'. $description .'</div>' : '' ).'</td></tr>'
				. '</table></div>';
}
			
print $htmlContent;
?>

</body>
</html>
<script language="javascript" type="text/javascript">
var winWidth = $(window).width();
var docWidth = $(document).width();
//adjust for too small resolutions
if (docWidth > winWidth)
{
//alert("Horizontal Scrollbar Present");
$('.product_col').css('min-width','400px');
}
change_view();
</script>