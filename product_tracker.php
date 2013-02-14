<?php
require_once('db.php');
require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

if(!isset($_REQUEST['id'])) return;
$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));
if(!is_numeric($id)) return;

if($_POST['dwformat'])
{
	Download_reports();
	exit;
}

////Process Report Tracker
function showProductTracker($id, $TrackerType)
{
	$HTMLContent = '';
	$Return = DataGenerator($id, $TrackerType);
	$uniqueId = uniqid();

	///Required Data restored
	$data_matrix = $Return['matrix'];
	$Report_DisplayName = $Return['report_name'];
	$id = $Return['id'];
	$rows = $Return['rows'];
	$columns = $Return['columns'];
	$productIds = $Return['ProductIds'];
	$inner_columns = $Return['inner_columns'];
	$inner_width = $Return['inner_width'];
	$column_width = $Return['column_width'];
	$ratio = $Return['ratio'];
	$areaId = $Return['areaId'];
	$column_interval = $Return['column_interval'];
	$TrackerType = $Return['TrackerType'];
	
	$HTMLContent .= TrackerCommonCSS($uniqueId, $TrackerType);
	
	if($TrackerType=='PTH' || $TrackerType=='CPTH')
	$HTMLContent .= TrackerHeaderHTMLContent($id, $Report_DisplayName, $TrackerType);
	
	$HTMLContent .= TrackerHTMLContent($data_matrix, $id, $rows, $columns, $productIds, $inner_columns, $inner_width, $column_width, $ratio, $areaId, $column_interval, $TrackerType, $uniqueId);
	
	$HTMLContent .= TrackerCommonJScript($uniqueId);
	
	if($TrackerType=='PTH' || $TrackerType=='CPTH')
	$HTMLContent .= "<script language=\"javascript\" type=\"text/javascript\">change_view_".$uniqueId."_();</script>";
	
	return $HTMLContent;

}
///End of Process Report Tracker

function DataGenerator($id, $TrackerType)
{
	global $db;
	global $now;

	$rows = array();
	$productIds = array();
	$rowsDisplayName = array();
	$rowsTagName = array();
	
	//IMP DATA
	$data_matrix=array();
	
	///// No of columns in our graph
	$columns = 10;
	$inner_columns = 10;
	$column_width = 80;
	$max_count = 0;

	$Report_DisplayName = NULL;
	$areaId = NULL;
	//END DATA
	
	if($TrackerType == 'PTH')	//PT=PRODUCT TRACKER (MAIN PT PAGE)
	{
		$query = 'SELECT `name`, `user`, `footnotes`, `description`, `category`, `shared`, `total`, `dtt`, `display_name` FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
		$res = mysql_fetch_array($res) or die('Report not found.');
		$Report_DisplayName=$res['display_name'];
		
		$query = 'SELECT `num`,`type`,`type_id`, `display_name`, `category`, `tag` FROM `rpt_masterhm_headers` WHERE `report`=' . $id . ' AND type = \'product\' ORDER BY num ASC';
		$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
		while($header = mysql_fetch_array($res))
		{
			if(!in_array($header['type_id'], $productIds)) //Duplicate ids avoided
			{
				if($header['type_id'] != NULL)
				{
					$productIds[] = $header['type_id'];
					$rowsTagName[] = $header['tag'];
				}
			}
		}
		
		// SELECT MAX NUM of Area
		$query = 'SELECT MAX(`num`) AS `num` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\'';
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);

		// Max Area Id
		$query = 'SELECT `type_id` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' AND type = \'area\' AND `num`='.$header['num'];
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$areaId = $header['type_id'];
	}
	else if($TrackerType == 'CPT' || $TrackerType=='CPTH')	//CPT=COMPANY PRODUCT TRACKER CPTH=COMPANY PRODUCT TRACKER HEADER
	{
		$query = 'SELECT `name`, `id` FROM `entities` WHERE `class`="Institution" and id=' . $id;
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$Report_DisplayName = $header['name'];
		$productIds = GetProductsFromCompany($header['id']);
		$id=$header['id'];
	}
	else if($TrackerType == 'MPT')	//MPT=MOA PRODUCT TRACKER
	{
		$query = 'SELECT `name`, `id` FROM `entities` WHERE `class`="MOA" and id=' . $id;
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$Report_DisplayName = $header['name'];
		$productIds = GetProductsFromMOA($header['id']);
		$id=$header['id'];
	}
	else if($TrackerType == 'MCPT')	//MMPT= MOA CATEGORY PRODUCT TRACKER
	{
		$query = 'SELECT `name`, `id` FROM `entities` WHERE `class`="MOA_Category" and id=' . $id;
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$Report_DisplayName = $header['name'];
		$productIds = GetProductsFromMOACategory($header['id']);
		$id=$header['id'];
	}
	else if($TrackerType == 'DPT')	//DPT=DISEASE PRODUCT TRACKER
	{
		$query = 'SELECT `name`, `id` FROM `entities` WHERE `class`="Disease" and id=' . $id;
		$res = mysql_query($query) or die(mysql_error());
		$header = mysql_fetch_array($res);
		$Report_DisplayName = $header['name'];
		$productIds = GetProductsFromDisease($header['id']);
		$id=$header['id'];
	}
	
	$rowsCompanyName=array();
	$rowsDescription=array();
	foreach($productIds as $key=> $product)
	{
		$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description, company FROM `entities` WHERE `class`='Product' and id = '" . $product . "' "));
		$rows[$key] = $result['name'];
		if($result['company'] != NULL && trim($result['company']) != '')
		{
			$result['company']=str_replace(',',', ',$result['company']);
			$result['company']=str_replace(',  ',', ',$result['company']);
			$rowsCompanyName[$key] = ' / '.$result['company'];
		} 
		$rowsDescription[$key] = $result['description'];
	}
	
	
	foreach($rows as $row => $rval)
	{
		/// Fill up all data in Data Matrix only, so we can sort all data at one place
		$data_matrix[$row]['productName'] = $rval;
		$data_matrix[$row]['product_CompanyName'] = $rowsCompanyName[$row];
		$data_matrix[$row]['productIds'] = $productIds[$row];
		$data_matrix[$row]['productTag'] = $rowsTagName[$row];
		
		if(isset($productIds[$row]) && $productIds[$row] != NULL)
		{
			///// Initialize data
			$data_matrix[$row]['active']=0;
				
			$data_matrix[$row]['total']=0;
			
			$data_matrix[$row]['indlead']=0;
			
			$data_matrix[$row]['total_phase_na']=0;
			$data_matrix[$row]['active_phase_na']=0;
			$data_matrix[$row]['indlead_phase_na']=0;
			$data_matrix[$row]['total_phase_0']=0;
			$data_matrix[$row]['active_phase_0']=0;
			$data_matrix[$row]['indlead_phase_0']=0;
			$data_matrix[$row]['total_phase_1']=0;
			$data_matrix[$row]['active_phase_1']=0;
			$data_matrix[$row]['indlead_phase_1']=0;
			$data_matrix[$row]['total_phase_2']=0;
			$data_matrix[$row]['active_phase_2']=0;
			$data_matrix[$row]['indlead_phase_2']=0;
			$data_matrix[$row]['total_phase_3']=0;
			$data_matrix[$row]['active_phase_3']=0;
			$data_matrix[$row]['indlead_phase_3']=0;
			$data_matrix[$row]['total_phase_4']=0;
			$data_matrix[$row]['active_phase_4']=0;
			$data_matrix[$row]['indlead_phase_4']=0;
			
			//// To avoid multiple queries to database, we are quering only one time and retrieveing all data and seprating each type
			if($TrackerType == 'PTH')
			{
				$phase_query = "SELECT dt.`is_active`, dt.`phase`, dt.`institution_type` FROM rpt_masterhm_cells rpt JOIN product_trials pt ON (rpt.`product` = pt.`product`) JOIN area_trials ar ON (rpt.`area` = ar.`area`) JOIN data_trials dt ON (dt.`larvol_id` = pt.`trial` && dt.`larvol_id` = ar.`trial`) WHERE pt.`product`='" . $productIds[$row] . "' AND ar.`area`='". $areaId ."'";
			}
			else if($TrackerType == 'DPT')
			{
				$phase_query = "SELECT dt.`is_active`, dt.`phase`, dt.`institution_type` FROM data_trials dt JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) WHERE et.`entity`='" . $productIds[$row] ."' AND dt.`larvol_id` IN (SELECT dt2.`larvol_id` FROM data_trials dt2 JOIN entity_trials et2 ON (dt2.`larvol_id` = et2.`trial`) WHERE et2.`entity`='" . $id ."')";
			}
			else
			{
				$phase_query = "SELECT dt.`is_active`, dt.`phase`, dt.`institution_type` FROM data_trials dt JOIN entity_trials et ON (dt.`larvol_id` = et.`trial`) WHERE et.`entity`='" . $productIds[$row] ."'";
			}
			
			$phase_res = mysql_query($phase_query) or die(mysql_error());
			while($phase_row=mysql_fetch_array($phase_res))
			{
				$data_matrix[$row]['total']++;
				if($phase_row['is_active'])
				{
					$data_matrix[$row]['active']++;
					if($phase_row['institution_type'] == 'industry_lead_sponsor')
					$data_matrix[$row]['indlead']++;
				}
					
				if($phase_row['phase'] == 'N/A' || $phase_row['phase'] == '' || $phase_row['phase'] === NULL)
				{
					$data_matrix[$row]['total_phase_na']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_na']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_na']++;
					}
				}
				else if($phase_row['phase'] == '0')
				{
					$data_matrix[$row]['total_phase_0']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_0']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_0']++;
					}
				}
				else if($phase_row['phase'] == '1' || $phase_row['phase'] == '0/1' || $phase_row['phase'] == '1a' 
				|| $phase_row['phase'] == '1b' || $phase_row['phase'] == '1a/1b' || $phase_row['phase'] == '1c')
				{
					$data_matrix[$row]['total_phase_1']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_1']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_1']++;
					}
				}
				else if($phase_row['phase'] == '2' || $phase_row['phase'] == '1/2' || $phase_row['phase'] == '1b/2' 
				|| $phase_row['phase'] == '1b/2a' || $phase_row['phase'] == '2a' || $phase_row['phase'] == '2a/2b' 
				|| $phase_row['phase'] == '2a/b' || $phase_row['phase'] == '2b')
				{
					$data_matrix[$row]['total_phase_2']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_2']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_2']++;
					}
				}
				else if($phase_row['phase'] == '3' || $phase_row['phase'] == '2/3' || $phase_row['phase'] == '2b/3' 
				|| $phase_row['phase'] == '3a' || $phase_row['phase'] == '3b')
				{
					$data_matrix[$row]['total_phase_3']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_3']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_3']++;
					}
				}
				else if($phase_row['phase'] == '4' || $phase_row['phase'] == '3/4' || $phase_row['phase'] == '3b/4')
				{
					$data_matrix[$row]['total_phase_4']++;
					if($phase_row['is_active'])
					{
						$data_matrix[$row]['active_phase_4']++;
						if($phase_row['institution_type'] == 'industry_lead_sponsor')
						$data_matrix[$row]['indlead_phase_4']++;
					}	
				}
			}	//// End of while
			if($data_matrix[$row]['total'] > $max_count)
			$max_count = $data_matrix[$row]['total'];
		}
		else
		{
			$data_matrix[$row]['active']=0;
			$data_matrix[$row]['total']=0;
			$data_matrix[$row]['indlead']=0;
			
			$data_matrix[$row]['total_phase_na']=0;
			$data_matrix[$row]['active_phase_na']=0;
			$data_matrix[$row]['indlead_phase_na']=0;
			$data_matrix[$row]['total_phase_0']=0;
			$data_matrix[$row]['active_phase_0']=0;
			$data_matrix[$row]['indlead_phase_0']=0;
			$data_matrix[$row]['total_phase_1']=0;
			$data_matrix[$row]['active_phase_1']=0;
			$data_matrix[$row]['indlead_phase_1']=0;
			$data_matrix[$row]['total_phase_2']=0;
			$data_matrix[$row]['active_phase_2']=0;
			$data_matrix[$row]['indlead_phase_2']=0;
			$data_matrix[$row]['total_phase_3']=0;
			$data_matrix[$row]['active_phase_3']=0;
			$data_matrix[$row]['indlead_phase_3']=0;
			$data_matrix[$row]['total_phase_4']=0;
			$data_matrix[$row]['active_phase_4']=0;
			$data_matrix[$row]['indlead_phase_4']=0;
			
			if($data_matrix[$row]['total'] < $max_count)
			$max_count = $data_matrix[$row]['total'];
		}
	}
	
	/// This function willl Sort multidimensional array according to industry lead column
	$data_matrix = sortTwoDimensionArrayByKey($data_matrix,'indlead');
	
	///// No of inner columns
	$original_max_count = $max_count;
	$max_count = ceil(($max_count / $columns)) * $columns;
	$column_interval = $max_count / $columns;
	$inner_columns = 10;
	$inner_width = $column_width  / $inner_columns;
	
	if($max_count > 0)
	$ratio = ($columns * $inner_columns) / $max_count;
	
	///All Data send
	$Return['matrix'] = $data_matrix;
	$Return['report_name'] = $Report_DisplayName;
	$Return['id'] = $id;
	$Return['rows'] = $rows;
	$Return['columns'] = $columns;
	$Return['ProductIds'] = $productIds;
	$Return['inner_columns'] = $inner_columns;
	$Return['inner_width'] = $inner_width;
	$Return['column_width'] = $column_width;
	$Return['ratio'] = $ratio;
	$Return['areaId'] = $areaId;
	$Return['column_interval'] = $column_interval;
	$Return['TrackerType'] = $TrackerType;
	
	return $Return;
}
//// End of Data Generator	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Larvol Trials :: Product Tracker</title>
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script src="scripts/jquery-1.7.1.min.js"></script>
<script src="scripts/jquery-ui-1.8.17.custom.min.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<script type="text/javascript" src="scripts/iepngfix_tilebg.js"></script>
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link href="css/themes/cupertino/jquery-ui-1.8.17.custom.css" rel="stylesheet" type="text/css" media="screen" />
<style type="text/css">
body { font-family:Verdana; font-size: 13px;}
.report_name {
	font-weight:bold;
	font-size:18px;
}

					
</style>
<?php
function TrackerCommonCSS($uniqueId, $TrackerType)
{
	$htmlContent = '';
	$htmlContent = '<style type="text/css">

					/* As in IE6 hover css does not works, below htc file is added which contains js script which will be executed only in IE, the script convert simple as well as complex hover css into compatible format for IE6 by replacing hover by class css - this file is used so that help tab as well as product selector will work in IE6 without any changes of code as well as css code and script can be also useful for making other css to work in IE6 like :hover and :active for IE6+, and additionally :focus for IE7 and IE8. */
					ul, li, '.$uniqueId.'slideout { behavior:url("css/csshover3.htc"); }
					img { behavior: url("css/iepngfix.htc"); }					
					a, a:hover{ height:100%; width:100%; display:block; text-decoration:none;}
					
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
					
					#slideout_'.$uniqueId.' {
						position: fixed;
						_position:absolute;
						top: '.(($TrackerType != 'PTH' && $TrackerType != 'CPTH') ? '200':'80').'px;
						right: 0;
						margin: 12px 0 0 0;
					}
					
					.slideout_inner {
						position:absolute;
						top: '.(($TrackerType != 'PTH' && $TrackerType != 'CPTH') ? '200':'80').'px;
						right: -255px;
						display:none;
					}
					
					#slideout_'.$uniqueId.':hover .slideout_inner{
						display : block;
						position:absolute;
						top: 2px;
						right: 0px;
						width: 280px;
						z-index:10;
					}
					
					.table-slide{
						border:1px solid #000;
						height:100px;
						width:280px;
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
					
					.downldbox {
						height:auto;
						width:310px;
						font-weight:bold;
					}
					
					.downldbox ul{
						list-style:none;
						margin:5px;
						padding:0px;
					}
					
					.downldbox ul li{
						width: 130px;
						float:left;
						margin:2px;
					}
					
					.dropmenudiv{
						position:absolute;
						top: 0;
						border: 1px solid #DDDDDD; /*THEME CHANGE HERE*/
						/*border-bottom-width: 0;*/
						font:normal 12px Verdana;
						line-height:18px;
						z-index:100;
						background-color: white;
						width: 50px;
						visibility: hidden;
					}
					
					.break_words{
						word-wrap: break-word;
					}
					
					.tag {
						color:#120f3c;
						font-weight:bold;
					}
					
					.graph_bottom {
						border-bottom:1px solid #CCCCCC;
					}
					
					th { 
						font-weight:normal; 
					}
					
					.last_tick_height {
						height:4px;
					}
					
					.last_tick_width {
						width:4px;
					}
					
					.graph_top {
						border-top:1px solid #CCCCCC;
					}
					
					.graph_right {
						border-right:1px solid #CCCCCC;
					}
					
					.graph_rightWhite {
						border-right:1px solid #FFFFFF;
					}
					
					.prod_col {
						width:420px;
						max-width:420px;
						word-wrap: break-word;
					}
					
					.side_tick_height {
						height:1px;
						line-height:1px;
					}
					
					.graph_gray {
						background-color:#CCCCCC;
					}
					
					.graph_blue {
						background-color:#00ccff;
					}
					
					.graph_green {
						background-color:#99cc00;
					}
					
					.graph_yellow {
						background-color:#ffff00;
					}
					
					.graph_orange {
						background-color:#ff9900;
					}
					
					.graph_red {
						background-color:#ff0000;
					}
					
					.Link {
					height:20px;
					min-height:20px;
					max-height:20px;
					padding:0px;
					margin:0px;
					_height:20px;
					}
					
					.tag {
					color:#120f3c;
					font-weight:normal;
					}
					</style>';
	return $htmlContent;				
}

function TrackerCommonJScript($uniqueId)
{
	$htmlContent = '';
	
	//Script for view change
	$htmlContent .= "<script language=\"javascript\" type=\"text/javascript\">
					function change_view_".$uniqueId."_()
					{
						var limit = document.getElementById('".$uniqueId."_Tot_rows').value;
						var dwcount = document.getElementById('".$uniqueId."_dwcount');
						$.browser.ie = /msie/.test(navigator.userAgent.toLowerCase()); 
				
						var i=0;
						for(i=0;i<limit;i++)
						{
							if(dwcount.value == 'active')
							{
								var row_type = document.getElementById('".$uniqueId."_active_Graph_Row_A_'+i);
								if(row_type != null && row_type != '')
								{
									if(!$.browser.ie)
									{
										document.getElementById('".$uniqueId."_active_Graph_Row_A_'+i).style.display = 'table-row';
										document.getElementById('".$uniqueId."_active_Graph_Row_B_'+i).style.display = 'table-row';
										document.getElementById('".$uniqueId."_active_Graph_Row_C_'+i).style.display = 'table-row';
									}
									else
									{
										document.getElementById('".$uniqueId."_active_Graph_Row_A_'+i).style.display = 'inline';
										document.getElementById('".$uniqueId."_active_Graph_Row_B_'+i).style.display = 'inline';
										document.getElementById('".$uniqueId."_active_Graph_Row_C_'+i).style.display = 'inline';
									}
									document.getElementById('".$uniqueId."_total_Graph_Row_A_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_total_Graph_Row_B_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_total_Graph_Row_C_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_indlead_Graph_Row_A_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_indlead_Graph_Row_B_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_indlead_Graph_Row_C_'+i).style.display = 'none';
								}
							}
							else if(dwcount.value == 'total')
							{
								var row_type = document.getElementById('".$uniqueId."_total_Graph_Row_A_'+i);
								if(row_type != null && row_type != '')
								{
									document.getElementById('".$uniqueId."_active_Graph_Row_A_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_active_Graph_Row_B_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_active_Graph_Row_C_'+i).style.display = 'none';
									if(!$.browser.ie)
									{
										document.getElementById('".$uniqueId."_total_Graph_Row_A_'+i).style.display = 'table-row';
										document.getElementById('".$uniqueId."_total_Graph_Row_B_'+i).style.display = 'table-row';
										document.getElementById('".$uniqueId."_total_Graph_Row_C_'+i).style.display = 'table-row';
									} 
									else 
									{
										document.getElementById('".$uniqueId."_total_Graph_Row_A_'+i).style.display = 'inline';
										document.getElementById('".$uniqueId."_total_Graph_Row_B_'+i).style.display = 'inline';
										document.getElementById('".$uniqueId."_total_Graph_Row_C_'+i).style.display = 'inline';
									}
									document.getElementById('".$uniqueId."_indlead_Graph_Row_A_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_indlead_Graph_Row_B_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_indlead_Graph_Row_C_'+i).style.display = 'none';
								}
							}
							else
							{
								var row_type = document.getElementById('".$uniqueId."_indlead_Graph_Row_A_'+i);
								if(row_type != null && row_type != '')
								{
									document.getElementById('".$uniqueId."_active_Graph_Row_A_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_active_Graph_Row_B_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_active_Graph_Row_C_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_total_Graph_Row_A_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_total_Graph_Row_B_'+i).style.display = 'none';
									document.getElementById('".$uniqueId."_total_Graph_Row_C_'+i).style.display = 'none';
									if(!$.browser.ie)
									{
										document.getElementById('".$uniqueId."_indlead_Graph_Row_A_'+i).style.display = 'table-row'; 
										document.getElementById('".$uniqueId."_indlead_Graph_Row_B_'+i).style.display = 'table-row';
										document.getElementById('".$uniqueId."_indlead_Graph_Row_C_'+i).style.display = 'table-row';
									}
									else 
									{
										document.getElementById('".$uniqueId."_indlead_Graph_Row_A_'+i).style.display = 'inline';
										document.getElementById('".$uniqueId."_indlead_Graph_Row_B_'+i).style.display = 'inline';
										document.getElementById('".$uniqueId."_indlead_Graph_Row_C_'+i).style.display = 'inline';
									}
								}
							}
						}	
					}
					</script>";
					//Script for view change ends

	//Script for Fixed header while resize
	$htmlContent .= "<script type=\"text/javascript\">
       				 var currentFixedHeader_".$uniqueId.";
       				 var currentGhost_".$uniqueId.";
					 var ScrollOn_".$uniqueId." = false;
		
					//Start - Header recreation in case of window resizing
					$(window).resize(function() {
							$.fn.reverse = [].reverse;
							var createGhostHeader_".$uniqueId." = function (header, topOffset, leftOffset) {
        			        // Recreate heaaderin case of window resizing even if there is current ghost header exists
        			       if (currentGhost_".$uniqueId.")
            		        $(currentGhost_".$uniqueId.").remove();
                
           			     var realTable = $(header).parents('#".$uniqueId."_ProdTrackerTable');
                
            		    var headerPosition = $(header).offset();
           			    var tablePosition = $(realTable).offset();
                
          			    var container = $('<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"vertical-align:middle; background-color:#FFFFFF;\" id=\"".$uniqueId."_ProdTrackerTable1\"></table>');
                
             		   // Copy attributes from old table (may not be what you want)
          		      for (var i = 0; i < realTable[0].attributes.length; i++) {
          		          var attr = realTable[0].attributes[i];
						 //We are not manually copying table attributes so below line is commented cause it does not work in IE6 and IE7
            	        //container.attr(attr.name, attr.value);
            		    }
                                
                	// Set up position of fixed row
          		    container.css({
                    	position: 'fixed',
                   		top: -topOffset,
                    	left: (-$(window).scrollLeft() + leftOffset),
                    	width: $(realTable).outerWidth()
                	});
                
                	// Create a deep copy of our actual header and put it in our container
                	var newHeader = $(header).clone().appendTo(container);
                
                	var collection2 = $(newHeader).find('td');
               	 
                	// TODO: Copy the width of each <td> manually
                	$(header).find('td').each(function () {
                	    var matchingElement = $(collection2.eq($(this).index()));
                	    $(matchingElement).width(this.offsetWidth + 0.5);
                	});
				
                	currentGhost_".$uniqueId." = container;
                	currentFixedHeader_".$uniqueId." = header;
                
                	// Add this fixed row to the same parent as the table
                	$(table_".$uniqueId.").parent().append(currentGhost_".$uniqueId.");
                	return currentGhost_".$uniqueId.";
            	};

            	var currentScrollTop_".$uniqueId." = $(window).scrollTop();

            	var activeHeader_".$uniqueId." = null;
            	var table_".$uniqueId." = $('#".$uniqueId."_ProdTrackerTable').first();
            	var tablePosition_".$uniqueId." = table_".$uniqueId.".offset();
            	var tableHeight_".$uniqueId." = table_".$uniqueId.".height();
            
            	var lastHeaderHeight_".$uniqueId." = $(table_".$uniqueId.").find('thead').last().height();
            	var topOffset_".$uniqueId." = 0;
            
            	if(tableHeight_".$uniqueId." != 0)//check if table is visible in tab then only create ghost header
				{
					// Check that the table is visible and has space for a header
            		if (tablePosition_".$uniqueId.".top + tableHeight_".$uniqueId." - lastHeaderHeight_".$uniqueId." >= currentScrollTop_".$uniqueId.")
            		{
                		var lastCheckedHeader_".$uniqueId." = null;
                		// We do these in reverse as we want the last good header
                		var headers_".$uniqueId." = $(table_".$uniqueId.").find('thead').reverse().each(function () {
                			var position_".$uniqueId." = $(this).offset();
                		   
                		   	if (position_".$uniqueId.".top <= currentScrollTop_".$uniqueId.")
                		   	{
                		       	activeHeader_".$uniqueId." = this;
                		       	return false;
                		   	}
                		   
                		   	lastCheckedHeader_".$uniqueId." = this;
                		});
                	
                		if (lastCheckedHeader_".$uniqueId.")
                		{
                		    var offset_".$uniqueId." = $(lastCheckedHeader_".$uniqueId.").offset();
                		    if (offset_".$uniqueId.".top - currentScrollTop_".$uniqueId." < $(activeHeader_".$uniqueId.").height())
                		        topOffset_".$uniqueId." = $(activeHeader_".$uniqueId.").height() - (offset_".$uniqueId.".top - currentScrollTop_".$uniqueId.") + 1;
                		}
            		}
            		// No row is needed, get rid of one if there is one
            		if (activeHeader_".$uniqueId." == null && currentGhost_".$uniqueId.")
	            	{
	            	    currentGhost_".$uniqueId.".remove();
		
    		            currentGhost_".$uniqueId." = null;
    	    	        currentFixedHeader_".$uniqueId." = null;
    	        	}
    	        
    	        	// We have what we need, make a fixed header row
    	        	if (activeHeader_".$uniqueId.")
					{
    	            	createGhostHeader_".$uniqueId."(activeHeader_".$uniqueId.", topOffset_".$uniqueId.", ($('#".$uniqueId."_ProdTrackerTable').offset().left));
					}
				}//end of if for checking table is visible or not in tab
			});
			//End - Header recreation in case of window resizing";
		
    //Script for Fixed header while resize
	$htmlContent .= "///Start - Header creation or align header incase of scrolling
					$(window).scroll(function() {
    		        $.fn.reverse = [].reverse;
					if(!ScrollOn_".$uniqueId.")
					{
    		        	ScrollOn_".$uniqueId." = true;
					}
    		        var createGhostHeader_".$uniqueId." = function (header_".$uniqueId.", topOffset_".$uniqueId.", leftOffset_".$uniqueId.") {
    		            // Don't recreate if it is the same as the current one
    		            if (header_".$uniqueId." == currentFixedHeader_".$uniqueId." && currentGhost_".$uniqueId.")
        		        {
            		        currentGhost_".$uniqueId.".css('top', -topOffset_".$uniqueId." + \"px\");
							currentGhost_".$uniqueId.".css('left',(-$(window).scrollLeft() + leftOffset_".$uniqueId.") + \"px\");
        		            return currentGhost_".$uniqueId.";
        		        }
        		     
       		        if (currentGhost_".$uniqueId.")
       	             $(currentGhost_".$uniqueId.").remove();
                
       		         var realTable_".$uniqueId." = $(header_".$uniqueId.").parents('#".$uniqueId."_ProdTrackerTable');
        	        
            	    var headerPosition_".$uniqueId." = $(header_".$uniqueId.").offset();
            	    var tablePosition_".$uniqueId." = $(realTable_".$uniqueId.").offset();
                
            	    var container_".$uniqueId." = $('<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" style=\"vertical-align:middle; background-color:#FFFFFF;\" id=\"".$uniqueId."_ProdTrackerTable1\"></table>');
                
                	// Copy attributes from old table (may not be what you want)
               		for (var i = 0; i < realTable_".$uniqueId."[0].attributes.length; i++) {
                	    var attr_".$uniqueId." = realTable_".$uniqueId."[0].attributes[i];
						//We are not manually copying table attributes so below line is commented cause it does not work in IE6 and IE7
                	    //container.attr(attr.name, attr.value);
                	}
                                
                	// Set up position of fixed row
                	container_".$uniqueId.".css({
                	    position: 'fixed',
                	    top: -topOffset_".$uniqueId.",
                	    left: (-$(window).scrollLeft() + leftOffset_".$uniqueId."),
                	    width: $(realTable_".$uniqueId.").outerWidth()
                	});
                
                	// Create a deep copy of our actual header and put it in our container
                	var newHeader_".$uniqueId." = $(header_".$uniqueId.").clone().appendTo(container_".$uniqueId.");
                	
                	var collection2_".$uniqueId." = $(newHeader_".$uniqueId.").find('td');
                	
                	// TODO: Copy the width of each <td> manually
                	$(header_".$uniqueId.").find('td').each(function () {
                	    var matchingElement_".$uniqueId." = $(collection2_".$uniqueId.".eq($(this).index()));
                	    $(matchingElement_".$uniqueId.").width(this.offsetWidth + 0.5);
                	});
				
                	currentGhost_".$uniqueId." = container_".$uniqueId.";
                	currentFixedHeader_".$uniqueId." = header_".$uniqueId.";
                
                	// Add this fixed row to the same parent as the table
                	$(table_".$uniqueId.").parent().append(currentGhost_".$uniqueId.");
                	return currentGhost_".$uniqueId.";
            	};

            	var currentScrollTop_".$uniqueId." = $(window).scrollTop();
            	var activeHeader_".$uniqueId." = null;
            	var table_".$uniqueId." = $('#".$uniqueId."_ProdTrackerTable').first();
            	var tablePosition_".$uniqueId." = table_".$uniqueId.".offset();
            	var tableHeight_".$uniqueId." = table_".$uniqueId.".height();
				var lastHeaderHeight_".$uniqueId." = $(table_".$uniqueId.").find('thead').last().height();
            	var topOffset_".$uniqueId." = 0;
           
		   		if(tableHeight_".$uniqueId." != 0)//check if table is visible in tab then only create ghost header
		   		{
					// Check that the table is visible and has space for a header
            		if (tablePosition_".$uniqueId.".top + tableHeight_".$uniqueId." - lastHeaderHeight_".$uniqueId." >= currentScrollTop_".$uniqueId.")
            		{
            		    var lastCheckedHeader_".$uniqueId." = null;
            		    // We do these in reverse as we want the last good header
            		    var headers_".$uniqueId." = $(table_".$uniqueId.").find('thead').reverse().each(function () {
            		        var position_".$uniqueId." = $(this).offset();
            		        
            		        if (position_".$uniqueId.".top <= currentScrollTop_".$uniqueId.")
            		        {
            		            activeHeader_".$uniqueId." = this;
            		            return false;
            		        }
            		        
            		        lastCheckedHeader_".$uniqueId." = this;
            			});
                	
            		  	if (lastCheckedHeader_".$uniqueId.")
            		 	{
            		       	var offset_".$uniqueId." = $(lastCheckedHeader_".$uniqueId.").offset();
            		       	if (offset_".$uniqueId.".top - currentScrollTop_".$uniqueId." < $(activeHeader_".$uniqueId.").height())
            		       	    topOffset_".$uniqueId." = $(activeHeader_".$uniqueId.").height() - (offset_".$uniqueId.".top - currentScrollTop_".$uniqueId.") + 1;
            		   	}
            		}
					// No row is needed, get rid of one if there is one
            		if (activeHeader_".$uniqueId." == null && currentGhost_".$uniqueId.")	
	            	{
	            	    currentGhost_".$uniqueId.".remove();
	
		                currentGhost_".$uniqueId." = null;
		                currentFixedHeader_".$uniqueId." = null;
		            }
	            
		            // We have what we need, make a fixed header row
		            if (activeHeader_".$uniqueId.")
					{
		                createGhostHeader_".$uniqueId."(activeHeader_".$uniqueId.", topOffset_".$uniqueId.", ($('#".$uniqueId."_ProdTrackerTable').offset().left));
					}
				}//end of if - checking table visible in tab
	        });
			///End - Header creation or align header incase of scrolling
		</script>";
		
		return $htmlContent;
}
?>
</head>
<body bgcolor="#FFFFFF" style="background-color:#FFFFFF;">
<?php 

function TrackerHeaderHTMLContent($id, $Report_DisplayName, $TrackerType)
{	
	if($TrackerType == 'PT')
		$Report_Name = ((trim($Report_DisplayName) != '' && $Report_DisplayName != NULL)? trim($Report_DisplayName):'report '.$id.'');
	if($TrackerType == 'CPTH')
		$Report_Name = $Report_DisplayName.' Company';
	
	$htmlContent = '';
	
	if( ( (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') == FALSE) || !isset($_SERVER['HTTP_REFERER']) ) && ( !isset($_REQUEST['LI']) || $_REQUEST['LI'] != 1) )
	{
		$htmlContent .= '<table cellspacing="0" cellpadding="0" width="100%" style="background-color:#FFFFFF;">'
					   . '<tr><td width="33%" style="background-color:#FFFFFF;"><img src="images/Larvol-Trial-Logo-notag.png" alt="Main" width="327" height="47" /></td>'
					   . '<td width="34%" align="center" style="background-color:#FFFFFF;" nowrap="nowrap"><span style="color:#ff0000;font-weight:normal;margin-left:40px;">Interface work in progress</span>'
					   . '<br/><span style="font-weight:normal;">Send feedback to '
					   . '<a style="display:inline;color:#0000FF;" target="_self" href="mailto:larvoltrials@larvol.com">'
					   . 'larvoltrials@larvol.com</a></span></td>'
					   . '<td width="33%" align="right" style="background-color:#FFFFFF; padding-right:20px;" class="report_name">Name: ' . htmlspecialchars($Report_Name) . ' Product Tracker</td></tr></table><br/>';
	}
	return $htmlContent;
}

function TrackerHTMLContent($data_matrix, $id, $rows, $columns, $productIds, $inner_columns, $inner_width, $column_width, $ratio, $areaId, $column_interval, $TrackerType, $uniqueId)
{				
	if(count($productIds) == 0) return 'No Products Found';
	
	require_once('tcpdf/config/lang/eng.php');
	require_once('tcpdf/tcpdf.php');  
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	
	$Line_Width = 20;
	$phase_legend_nums = array('4', '3', '2', '1', '0', 'na');
	

	$htmlContent = '';
	$htmlContent .= '<br style="line-height:11px;"/>'
					.'<form action="product_tracker.php" method="post">'
					. '<table width="264px" border="0" cellspacing="0" cellpadding="0" class="controls" align="center">'
					. '<tr>'
					. '<td class="bottom right"><select id="'.$uniqueId.'_dwcount" name="dwcount" onchange="change_view_'.$uniqueId.'_();">'
					. '<option value="indlead" selected="selected">Active industry trials</option>'
					. '<option value="active">Active trials</option>'
					. '<option value="total">All trials</option></select></td>'
					. '<td class="bottom right">'
					. '<div style="border:1px solid #000000; float:right; margin-top: 0px; padding:2px; color:#000000;" id="'.$uniqueId.'_chromemenu"><a rel="'.$uniqueId.'_dropmenu"><span style="padding:2px; padding-right:4px; background-position:left center; background-repeat:no-repeat; background-image:url(\'./images/save.png\'); cursor:pointer; ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b><font color="#000000">Export</font></b></span></a></div>'
					. '</td>'
					. '</tr>'
					. '</table>';
				
	$htmlContent  .= '<div id="'.$uniqueId.'_dropmenu" class="dropmenudiv" style="width: 310px;">'
					.'<div style="height:100px; padding:6px;"><div class="downldbox"><div class="newtext">Download options</div>'
					. '<input type="hidden" name="id" id="'.$uniqueId.'_id" value="' . $id . '" />'
					. '<input type="hidden" name="TrackerType" id="'.$uniqueId.'_TrackerType" value="'. $TrackerType .'" />'
					. '<ul><li><label>Which format: </label></li>'
					. '<li><select id="'.$uniqueId.'_dwformat" name="dwformat" size="3" style="height:50px">'
					//. '<option value="exceldown" selected="selected">Excel</option>'
					. '<option value="pdfdown" selected="selected">PDF</option>'
					. '<option value="excelchartdown">Excel Chart</option>'
					. '<option value="tsvdown">TSV</option>'
					. '</select></li>'
					. '</ul>'
					. '<input type="submit" name="download" title="Download" value="Download file" style="margin-left:8px;"  />'
					. '</div></div>'
					. '</div><script type="text/javascript">cssdropdown.startchrome("'.$uniqueId.'_chromemenu");</script>'
					. '</form>';
				
						
	$htmlContent .= '<table border="0" align="center" width="'.(420+8+($inner_columns*$columns*8)+8+10).'px" style="vertical-align:middle;" cellpadding="0" cellspacing="0" id="'.$uniqueId.'_ProdTrackerTable">'
				    . '<thead>';
	//scale
	//Row to keep alignement perfect at time of floating headers
	$htmlContent .= '<tr class="side_tick_height"><th class="prod_col" width="420px">&nbsp;</th><th width="8px" class="graph_rightWhite">&nbsp;</th>';
	for($j=0; $j < $columns; $j++)
	{
		for($k=0; $k < $inner_columns; $k++)
		$htmlContent .= '<th width="8px" colspan="1" '. (($k == ($inner_columns-1)) ? 'class="graph_rightWhite" ':'' ) .'>&nbsp;</th>';
	}
	$htmlContent .= '<th width="8px"></th></tr>';


	$htmlContent .= '<tr><th class="prod_col" align="right">Trials</th><th width="8px" class="graph_rightWhite">&nbsp;</th>';
	$htmlContent .= '<th align="right" class="graph_rightWhite" colspan="1" width="8px">0</th>';
	for($j=0; $j < $columns; $j++)
	{
		if($column_interval == 0){
			$htmlContent .= '<th align="right" class="graph_rightWhite" colspan="'.$inner_columns.'">'.($j+1 == $columns ? ($j+1) * $column_interval : "").'</th>';
		}else{
			$htmlContent .= '<th align="right" class="graph_rightWhite" colspan="'.$inner_columns.'">'.(($j+1) * $column_interval).'</th>';
		}
	}		
	$htmlContent .= '</tr>';
	
	$htmlContent .= '<tr class="last_tick_height"><th class="last_tick_height prod_col"><font style="line-height:4px;">&nbsp;</font></th><th class="graph_right"><font style="line-height:4px;">&nbsp;</font></th>';
	for($j=0; $j < $columns; $j++)
	$htmlContent .= '<th colspan="'.$inner_columns.'" class="graph_right graph_bottom"><font style="line-height:4px;">&nbsp;</font></th>';
	$htmlContent .= '<th></th></tr>';
	
	
	$htmlContent .='</thead>';
	//scale ends

	$htmlContent .= '<tr class="side_tick_height"><th class="prod_col" width="420px">&nbsp;</th><th width="8px" class="graph_right">&nbsp;</th>';
	for($j=0; $j < $columns; $j++)
	{
		for($k=0; $k < $inner_columns; $k++)
		$htmlContent .= '<th width="8px" colspan="1" class="'. (($k == ($inner_columns-1)) ? 'graph_right':'' ) .'">&nbsp;</th>';
	}
	$htmlContent .= '<th width="8px"></th></tr>';

	for($incr=0; $incr < count($rows); $incr++)
	{	
		$row = $incr;
		
		$commonPart1 = trim(urlPath()) .'intermediary.php?e1=' . $data_matrix[$row]['productIds'];
		$commonPart2 = '';
		if($TrackerType == 'PTH') $commonPart2 = '&e2=' . $areaId . '&hm='.$id;
		if($TrackerType == 'DPT') $commonPart2 = '&e2=' . $id;
		$industryLink = $commonPart1 . $commonPart2 . '&list=1&itype=0';
		$activeLink = $commonPart1 . $commonPart2 . '&list=1';
		$totalLink = $commonPart1 . $commonPart2 . '&list=2';
		
		$htmlContent .= '<tr class="side_tick_height"><th class="prod_col side_tick_height">&nbsp;</th><th class="graph_right">&nbsp;</th>';
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th colspan="'.$inner_columns.'" class="graph_right">&nbsp;</th>';
		}
		$htmlContent .= '<th></th></tr>';
		
		////// Color Graph - Bar Starts
		
		//// Code for Indlead
		$Err = IndleadCountErr($data_matrix, $row, $ratio);
		
		$Max_ValueKey = Max_ValueKey($data_matrix[$row]['indlead_phase_na'], $data_matrix[$row]['indlead_phase_0'], $data_matrix[$row]['indlead_phase_1'], $data_matrix[$row]['indlead_phase_2'], $data_matrix[$row]['indlead_phase_3'], $data_matrix[$row]['indlead_phase_4']);
						
		$htmlContent .= '<tr id="'.$uniqueId.'_indlead_Graph_Row_A_'.$row.'"><th align="right" class="prod_col" id="'.$uniqueId.'_ProdCol_'.$row.'" rowspan="3"><a href="'. $industryLink . '" target="_blank" style="text-decoration:underline;">'.formatBrandName($data_matrix[$row]['productName'], 'product').$data_matrix[$row]['product_CompanyName'].'</a>'.((trim($data_matrix[$row]['productTag']) != '') ? ' <font class="tag">['.$data_matrix[$row]['productTag'].']</font>':'').'</th><th class="graph_right" rowspan="3">&nbsp;</th>';
	
		///Below function will derive number of lines required to display product name, as our graph size is fixed due to fixed scale, we can calculate approx max area  
		///for product column. From that we can calculate extra height which will be distributed to up and down rows of graph bar, So now IE6/7 as well as chrome will not 
		///have issue of unequal distrirbution of extra height due to rowspan and bar will remain in middle, without use of JS.
		$ExtraAdjusterHeight = (($pdf->getNumLines($data_matrix[$row]['productName'].$data_matrix[$row]['product_CompanyName'], ((650)*17/90)) * $Line_Width)  - 20) / 2;
		
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th height="'.$ExtraAdjusterHeight.'px" colspan="'.$inner_columns.'" class="graph_right"><font style="line-height:1px;">&nbsp;</font></th>';
		}
		$htmlContent .= '<th></th></tr><tr id="'.$uniqueId.'_indlead_Graph_Row_B_'.$row.'" class="Link" >';
		
		$total_cols = $inner_columns * $columns;
		$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['indlead']);
		$phase_space = 0;
	
		foreach($phase_legend_nums as $key => $phase_nums)
		{
			if($data_matrix[$row]['indlead_phase_'.$phase_nums] > 0)
			{
				$Color = getClassNColorforPhase($phase_nums);
				$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['indlead_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
				$phase_space =  $phase_space + $Mini_Bar_Width;					
				$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="Link '.$Color[0].'" title="'.$data_matrix[$row]['indlead_phase_'.$phase_nums].'" style="height:20px; _height:20px;"><a href="'. $industryLink . '&phase='.$phase_nums . '" target="_blank" class="Link" >&nbsp;</a></th>';
			}
		}
	
		$remain_span = $total_cols - $phase_space;
		
		if($remain_span > 0)
		$htmlContent .= DrawExtraHTMLCells($phase_space, $inner_columns, $remain_span);
		
		$htmlContent .= '<th></th></tr><tr id="'.$uniqueId.'_indlead_Graph_Row_C_'.$row.'">';
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th height="'.$ExtraAdjusterHeight.'px" colspan="'.$inner_columns.'" class="graph_right"><font style="line-height:1px;">&nbsp;</font></th>';
		}
		$htmlContent .= '<th></th></tr>';
	
		//// Code for Active
		$Err = ActiveCountErr($data_matrix, $row, $ratio);
	
		$Max_ValueKey = Max_ValueKey($data_matrix[$row]['active_phase_na'], $data_matrix[$row]['active_phase_0'], $data_matrix[$row]['active_phase_1'], $data_matrix[$row]['active_phase_2'], $data_matrix[$row]['active_phase_3'], $data_matrix[$row]['active_phase_4']);
					
		$htmlContent .= '<tr style="display:none;" id="'.$uniqueId.'_active_Graph_Row_A_'.$row.'"><th align="right" class="prod_col" rowspan="3"><a href="'. $activeLink . '" target="_blank" style="text-decoration:underline;">'.formatBrandName($data_matrix[$row]['productName'], 'product').$data_matrix[$row]['product_CompanyName'].'</a>'.((trim($data_matrix[$row]['productTag']) != '') ? ' <font class="tag">['.$data_matrix[$row]['productTag'].']</font>':'').'</th><th class="graph_right" rowspan="3">&nbsp;</th>';
	
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th height="'.$ExtraAdjusterHeight.'px" colspan="'.$inner_columns.'" class="graph_right"><font style="line-height:1px;">&nbsp;</font></th>';
		}
		$htmlContent .= '<th></th></tr><tr style="display:none;" id="'.$uniqueId.'_active_Graph_Row_B_'.$row.'" class="Link">';
		
		$total_cols = $inner_columns * $columns;
		$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['active']);
		$phase_space = 0;
	
		foreach($phase_legend_nums as $key => $phase_nums)
		{
			if($data_matrix[$row]['active_phase_'.$phase_nums] > 0)
			{
				$Color = getClassNColorforPhase($phase_nums);
				$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['active_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
				$phase_space =  $phase_space + $Mini_Bar_Width;					
				$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="Link '.$Color[0].'" title="'.$data_matrix[$row]['active_phase_'.$phase_nums].'" style="height:20px; _height:20px;"><a href="'. $activeLink . '&phase='.$phase_nums . '" target="_blank" class="Link" >&nbsp;</a></th>';
			}
		}
	
		$remain_span = $total_cols - $phase_space;
		
		if($remain_span > 0)
		$htmlContent .= DrawExtraHTMLCells($phase_space, $inner_columns, $remain_span);
		
		$htmlContent .= '<th></th></tr><tr style="display:none;" id="'.$uniqueId.'_active_Graph_Row_C_'.$row.'">';
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th height="'.$ExtraAdjusterHeight.'px" colspan="'.$inner_columns.'" class="graph_right"><font style="line-height:1px;">&nbsp;</font></th>';
		}
		$htmlContent .= '<th></th></tr>';
	
		//// Code for Total
		$Err = TotalCountErr($data_matrix, $row, $ratio);
		
		$Max_ValueKey = Max_ValueKey($data_matrix[$row]['total_phase_na'], $data_matrix[$row]['total_phase_0'], $data_matrix[$row]['total_phase_1'], $data_matrix[$row]['total_phase_2'], $data_matrix[$row]['total_phase_3'], $data_matrix[$row]['total_phase_4']);
	
		$htmlContent .= '<tr style="display:none;" id="'.$uniqueId.'_total_Graph_Row_A_'.$row.'"><th align="right" class="prod_col" rowspan="3"><a href="'. $totalLink . '" target="_blank" style="text-decoration:underline;">'.formatBrandName($data_matrix[$row]['productName'], 'product').$data_matrix[$row]['product_CompanyName'].'</a>'.((trim($data_matrix[$row]['productTag']) != '') ? ' <font class="tag">['.$data_matrix[$row]['productTag'].']</font>':'').'</th><th class="graph_right" rowspan="3">&nbsp;</th>';
	
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th height="'.$ExtraAdjusterHeight.'px" colspan="'.$inner_columns.'" class="graph_right"><font style="line-height:1px;">&nbsp;</font></th>';
		}
		$htmlContent .= '<th></th></tr><tr style="display:none;" id="'.$uniqueId.'_total_Graph_Row_B_'.$row.'" class="Link" >';
	
		$total_cols = $inner_columns * $columns;
		$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['total']);
		$phase_space = 0;
	
		foreach($phase_legend_nums as $key => $phase_nums)
		{
			if($data_matrix[$row]['total_phase_'.$phase_nums] > 0)
			{
				$Color = getClassNColorforPhase($phase_nums);
				$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['total_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
				$phase_space =  $phase_space + $Mini_Bar_Width;					
				$htmlContent .= '<th colspan="'.$Mini_Bar_Width.'" class="Link '.$Color[0].'" title="'.$data_matrix[$row]['total_phase_'.$phase_nums].'" style="height:20px; _height:20px;"><a href="'. $totalLink . '&phase='.$phase_nums . '" target="_blank" class="Link" >&nbsp;</a></th>';
			}
		}
	
		$remain_span = $total_cols - $phase_space;
	
		if($remain_span > 0)
		$htmlContent .= DrawExtraHTMLCells($phase_space, $inner_columns, $remain_span);
		
		$htmlContent .= '<th></th></tr><tr style="display:none;" id="'.$uniqueId.'_total_Graph_Row_C_'.$row.'">';
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th height="'.$ExtraAdjusterHeight.'px" colspan="'.$inner_columns.'" class="graph_right"><font style="line-height:1px;">&nbsp;</font></th>';
		}
		$htmlContent .= '<th></th></tr>';
		
		////// End Of - Color Graph - Bar Starts
		
		$htmlContent .= '<tr class="side_tick_height"><th class="prod_col side_tick_height">&nbsp;</th><th class="'. (($incr == (count($rows)-1)) ? '':'graph_bottom') .' graph_right">&nbsp;</th>';
		for($j=0; $j < $columns; $j++)
		{
			$htmlContent .= '<th colspan="'.$inner_columns.'" class="graph_right">&nbsp;</th>';
		}
		$htmlContent .= '<th></th></tr>';
	}			   

	//Draw scale			   
	$htmlContent .= '<tr class="last_tick_height"><th class="last_tick_height prod_col"><font style="line-height:4px;">&nbsp;</font></th><th class="graph_right"><font style="line-height:4px;">&nbsp;</font></th>';
	for($j=0; $j < $columns; $j++)
	$htmlContent .= '<th colspan="'.$inner_columns.'" class="graph_top graph_right"><font style="line-height:4px;">&nbsp;</font></th>';
	$htmlContent .= '<th></th></tr>';
	/* Current no need of lower scale
	$htmlContent .= '<tr><th class="prod_col"></th><th class="graph_rightWhite"></th>';
	for($j=0; $j < $columns; $j++)
	$htmlContent .= '<th align="right" class="graph_rightWhite" colspan="'.(($j==0)? $inner_columns+1 : $inner_columns).'">'.(($j+1) * $column_interval).'</th>';
	$htmlContent .= '</tr>';
	//End of draw scale
	*/						
	$htmlContent .= '</table>';

	//// Common Data
	$htmlContent .= '<input type="hidden" value="'.count($rows).'" name="Tot_rows" id="'.$uniqueId.'_Tot_rows" />';
	////// End of Common Data
	
	///Add HELP Tab here only
	$htmlContent .= '<div id="slideout_'.$uniqueId.'">
    					<img src="images/help.png" alt="Help" />
    					<div class="slideout_inner">
        					<table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" class="table-slide">
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
					</div>';


	return $htmlContent;
}

function DrawExtraHTMLCells($phase_space, $inner_columns, $remain_span)
{
	$aq_sp = 0;
	while($aq_sp < $phase_space)
	$aq_sp = $aq_sp + $inner_columns;
	
	$extra_sp = $aq_sp - $phase_space;
	if($extra_sp > 0)
	$extraHTMLContent .= '<th colspan="'.($extra_sp).'" class="graph_right Link">&nbsp;</th>';
	
	$remain_span = $remain_span - $extra_sp;
	while($remain_span > 0)
	{
		$extraHTMLContent .= '<th colspan="'.($inner_columns).'" class="graph_right Link">&nbsp;</th>';
		$remain_span = $remain_span - $inner_columns;
	}
	
	return $extraHTMLContent;
}
if(isset($_REQUEST['id']) && (isset($_REQUEST['TrackerType']) && $_REQUEST['TrackerType'] == 'CPTH'))
print showProductTracker($_REQUEST['id'], 'CPTH');	//CPTH - COMPANY PRODUCT TRACKER WITH HEADER
else if(isset($_REQUEST['id']))
print showProductTracker($_REQUEST['id'], 'PTH');	//PTH - Normal PRODUCT TRACKER WITH HEADER
?>
<?
if($db->loggedIn() && (strpos($_SERVER['HTTP_REFERER'], 'larvolinsight') == FALSE))
{
	$cpageURL = 'http://';
	$cpageURL .= $_SERVER["SERVER_NAME"].urldecode($_SERVER["REQUEST_URI"]);
	echo '<a href="li/larvolinsight.php?url='. $cpageURL .'"><span style="color:red;font-weight:bold;margin-left:10px;">LI view</span></a><br>';
}
?>
</body>
</html>
<?php
function Download_reports()
{
	ob_start();
	if(!isset($_REQUEST['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_REQUEST['id']));
	if(!is_numeric($id)) return;
	$TrackerType = $_REQUEST['TrackerType'];
	$Return = DataGenerator($id, $TrackerType);
	///Required Data restored
	$data_matrix = $Return['matrix'];
	$Report_DisplayName = $Return['report_name'];
	$id = $Return['id'];
	$rows = $Return['rows'];
	$columns = $Return['columns'];
	$productIds = $Return['ProductIds'];
	$inner_columns = $Return['inner_columns'];
	$inner_width = $Return['inner_width'];
	$column_width = $Return['column_width'];
	$ratio = $Return['ratio'];
	$areaId = $Return['areaId'];
	$column_interval = $Return['column_interval'];
	
	$total_cols = $inner_columns * $columns;
	
	$phase_legend_nums = array('4', '3', '2', '1', '0', 'na');
	
	$Report_Name = htmlspecialchars((trim($Report_DisplayName) != '' && $Report_DisplayName != NULL)? trim($Report_DisplayName):'report '.$id.'');
	
	$commonPart2 = '';
	if($TrackerType == 'PTH') $commonPart2 = '&e2=' . $areaId . '&hm='.$id;
	if($TrackerType == 'DPT') $commonPart2 = '&e2=' . $id;
	
	if($_POST['dwcount']=='active')
	{
		$tooltip=$title="Active trials";
		$pdftitle="Active trials";
		$link_part = $commonPart2.'&list=1';
		$mode = 'active';
	}
	elseif($_POST['dwcount']=='total')
	{
		$pdftitle=$tooltip=$title="All trials (Active + Inactive)";
		$link_part = $commonPart2.'&list=2';
		$mode = 'total';
	}
	else
	{
		$tooltip=$title="Active industry lead sponsor trials";
		$pdftitle="Active industry lead sponsor trials";
		$link_part = $commonPart2.'&list=1&itype=0';
		$mode = 'indlead';
	}
	
	$TrackerName = (($TrackerType== 'CPT') ? ' Company':'').(($TrackerType== 'MPT') ? ' MOA':'').(($TrackerType== 'DPT') ? ' Disease':'').(($TrackerType== 'CPTH') ? ' Company':'').(($TrackerType== 'MCPT') ? ' MOA Category':'');
	
	if($_POST['dwformat']=='exceldown')
	{
	  	$name = htmlspecialchars(strlen($name)>0?$name:('report '.$id.''));
		
		$Prod_Col = 'A';
		$Start_Char = 'B';
		
		// Create excel file object
		$objPHPExcel = new PHPExcel();
	
		// Set properties
		$objPHPExcel->getProperties()->setCreator(SITE_NAME);
		$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
		$objPHPExcel->getProperties()->setTitle(substr($name,0,20));
		$objPHPExcel->getProperties()->setSubject(substr($name,0,20));
		$objPHPExcel->getProperties()->setDescription(substr($name,0,20));
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(8);
		$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('verdana_old'); 
	
		// Build sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle(substr($name,0,20));
		//$objPHPExcel->getActiveSheet()->getStyle('A1:AA2000')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(36);
		
		$Excel_HMCounter = 0;
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, 'Report name:');
		$objPHPExcel->getActiveSheet()->mergeCells('B' . $Excel_HMCounter . ':BH' . $Excel_HMCounter);
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, $Report_Name .$TrackerName.' Product Tracker');
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$Excel_HMCounter, 'Display Mode:');
		$objPHPExcel->getActiveSheet()->mergeCells('B' . $Excel_HMCounter . ':BH' . $Excel_HMCounter);
		$objPHPExcel->getActiveSheet()->getStyle('B' . $Excel_HMCounter)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $Excel_HMCounter, $tooltip);
		
		/// Extra Row
		$Excel_HMCounter++;
		$from = $Start_Char;
		$from++;
		for($j=0; $j < $columns; $j++)
		{
			$to = getColspanforExcelExport($from, $inner_columns);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$from = $to;
			$from++;
		}
		
		/// Set Dimension
		$Char = $Start_Char;
		$objPHPExcel->getActiveSheet()->getColumnDimension($Char)->setWidth(1);
		$Char++;
		for($j=0; $j < ($columns+1); $j++)
		{
			for($k=0; $k < $inner_columns; $k++)
			{
				$objPHPExcel->getActiveSheet()->getColumnDimension($Char)->setWidth(1);
				$Char++;
			}
		}
		
		for($incr=0; $incr < count($rows); $incr++)
		{	
			$row = $incr;
			$Excel_HMCounter++;
	
			////// Color Graph - Bar Starts
				
			//// Code for Indlead
			if(isset($data_matrix[$row]['productIds']) && $data_matrix[$row]['productIds'] != NULL && !empty($areaId))
			{
				/// Product Column
				$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
				$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
				
				$cell = $Prod_Col . $Excel_HMCounter;
				$objPHPExcel->getActiveSheet()->SetCellValue($cell, $data_matrix[$row]['productName'].$data_matrix[$row]['product_CompanyName'].((trim($data_matrix[$row]['productTag']) != '') ? ' ['.$data_matrix[$row]['productTag'].']':''));
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlPath() . 'intermediary.php?e1=' . $data_matrix[$row]['productIds'].$link_part); 
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
				if($rdesc)
 			    {
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->setAuthor('Description:');
 			    	$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun('Description:');
 			    	$objCommentRichText->getFont()->setBold(true);
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun("\r\n");
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun($rdesc);
 			    } 
				$from = $Start_Char;
				
				//// Limit product names so that they will not overlap other cells
				$white_font['font']['color']['rgb'] = 'FFFFFF';
				$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray($white_font);
				$objPHPExcel->getActiveSheet()->setCellValue($from . $Excel_HMCounter, '.');
				$from++;
				
				//// Graph starts
				if($mode == 'indlead')
				{
					$Err = IndleadCountErr($data_matrix, $row, $ratio);
					$Max_ValueKey = Max_ValueKey($data_matrix[$row]['indlead_phase_na'], $data_matrix[$row]['indlead_phase_0'], $data_matrix[$row]['indlead_phase_1'], $data_matrix[$row]['indlead_phase_2'], $data_matrix[$row]['indlead_phase_3'], $data_matrix[$row]['indlead_phase_4']);
					$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['indlead']);
					$phase_space = 0;
					
					foreach($phase_legend_nums as $key => $phase_nums)
					{
						if($data_matrix[$row]['indlead_phase_'.$phase_nums] > 0)
						{
							$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['indlead_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
							$phase_space =  $phase_space + $Mini_Bar_Width;
							$url = urlPath() . 'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part . '&phase=' . $phase_nums;
							$from = CreatePhaseCellforExcelExport($from, $Mini_Bar_Width, $url, $Excel_HMCounter, $data_matrix[$row]['indlead_phase_'.$phase_nums], $phase_nums, $objPHPExcel);
						}
					}
				}
				else if ($mode == 'active')
				{
					$Err = ActiveCountErr($data_matrix, $row, $ratio);
					$Max_ValueKey = Max_ValueKey($data_matrix[$row]['active_phase_na'], $data_matrix[$row]['active_phase_0'], $data_matrix[$row]['active_phase_1'], $data_matrix[$row]['active_phase_2'], $data_matrix[$row]['active_phase_3'], $data_matrix[$row]['active_phase_4']);
					$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['active']);
					$phase_space = 	0;
					
					foreach($phase_legend_nums as $key => $phase_nums)
					{
						if($data_matrix[$row]['active_phase_'.$phase_nums] > 0)
						{
							$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['active_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
							$phase_space =  $phase_space + $Mini_Bar_Width;
							$url = urlPath() . 'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part . '&phase=' . $phase_nums;
							$from = CreatePhaseCellforExcelExport($from, $Mini_Bar_Width, $url, $Excel_HMCounter, $data_matrix[$row]['active_phase_'.$phase_nums], $phase_nums, $objPHPExcel);
						}
					}
				}
				else
				{
					$Err = TotalCountErr($data_matrix, $row, $ratio);
					$Max_ValueKey = Max_ValueKey($data_matrix[$row]['total_phase_na'], $data_matrix[$row]['total_phase_0'], $data_matrix[$row]['total_phase_1'], $data_matrix[$row]['total_phase_2'], $data_matrix[$row]['total_phase_3'], $data_matrix[$row]['total_phase_4']);
					$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['total']);
					$phase_space = 	0;
					
					foreach($phase_legend_nums as $key => $phase_nums)
					{
						if($data_matrix[$row]['total_phase_'.$phase_nums] > 0)
						{
							$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['total_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
							$phase_space =  $phase_space + $Mini_Bar_Width;
							$url = urlPath() . 'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part . '&phase=' . $phase_nums;
							$from = CreatePhaseCellforExcelExport($from, $Mini_Bar_Width, $url, $Excel_HMCounter, $data_matrix[$row]['total_phase_'.$phase_nums], $phase_nums, $objPHPExcel);
						}
					}
				}
				
				$remain_span = $total_cols - $phase_space;
		
				if($remain_span > 0)
				{
					$aq_sp = 0;
					while($aq_sp < $phase_space)
					$aq_sp = $aq_sp + $inner_columns;
					
					$extra_sp = $aq_sp - $phase_space;
					if($extra_sp > 0)
					{
						$to = getColspanforExcelExport($from, $extra_sp);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$from = $to;
						$from++;
					}
					
					$remain_span = $remain_span - $extra_sp;
					while($remain_span > 0)
					{
						$to = getColspanforExcelExport($from, $inner_columns);
						$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
						$from = $to;
						$from++;
						
						$remain_span = $remain_span - $inner_columns;
					}
				} // End of remain span
			}	/// End of data check
			////// End Of - Color Graph - Bar
		}	/// End of rows foreach		
		
		
		$Excel_HMCounter++;
		$from = $Start_Char;
		$from++;
		for($j=0; $j < $columns; $j++)
		{
			$to = getColspanforExcelExport($from, $inner_columns);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$from = $to;
			$from++;
		}
		$objPHPExcel->getActiveSheet()->getRowDimension($Excel_HMCounter)->setRowHeight(5);
			
		$Excel_HMCounter++;
		$from = $Start_Char;
		$from++;
		
		$to = getColspanforExcelExport($from, 2);
		$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
		$objPHPExcel->getActiveSheet()->SetCellValue($from . $Excel_HMCounter, 0);
		$from = $to;
		$from++;
			
		for($j=0; $j < $columns; $j++)
		{
			$to = getColspanforExcelExport($from, (($j==0)? $inner_columns : $inner_columns));
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$objPHPExcel->getActiveSheet()->SetCellValue($from . $Excel_HMCounter, (($j+1) * $column_interval));
			$from = $to;
			$from++;
		}
		//$objPHPExcel->getActiveSheet()->getRowDimension($Excel_HMCounter)->setRowHeight(5);
		
		/// Extra Row
		$Excel_HMCounter++;
		
		
		/////Phase Legend
		$Excel_HMCounter++;
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . $Excel_HMCounter, 'Phase:');
		
		$phases = array('N/A', 'Phase 0', 'Phase 1', 'Phase 2', 'Phase 3', 'Phase 4');
		$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
		$phase_legend_nums = array('N/A', '0', '1', '2', '3', '4');
		//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
		$p_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
		$phase_legend_colors = array('BFBFBF', '00CCFF', '99CC00', 'FFFF00', 'FF9900', 'FF0000');
		
		$from = $Start_Char;
		$from++;
		foreach($p_colors as $key => $color)
		{
			$to = getColspanforExcelExport($from, $inner_columns);
			$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':'. $to . $Excel_HMCounter);
			$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getFill()->getStartColor()->setRGB($color);
			$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->setValueExplicit($phasenums[$key], PHPExcel_Cell_DataType::TYPE_STRING);
			$from = $to;
			$from++;
		}
			
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			
		//ob_end_clean(); 
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Larvol_' . substr($Report_Name,0,20) . '_Product_Analytic_Excel_' . date('Y-m-d_H.i.s') . '.xlsx"');
			
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();
	} //Excel Function Ends
	
	if($_POST['dwformat']=='tsvdown')
	{
		$TSV_data = "";
		
		$TSV_data = "Product Name \t Phase 4 \t Phase 3 \t Phase 2 \t Phase 1 \t Phase 0 \t Phase N/A \n";
		
		for($incr=0; $incr < count($rows); $incr++)
		{	
			$row = $incr;
			
			if(isset($data_matrix[$row]['productIds']) && $data_matrix[$row]['productIds'] != NULL)
			{
				$TSV_data .= $data_matrix[$row]['productName'].$data_matrix[$row]['product_CompanyName'] . ((trim($data_matrix[$row]['productTag']) != '') ? ' ['.$data_matrix[$row]['productTag'].']':''). " \t ";
				if($mode == 'indlead')
				{
					$TSV_data .= $data_matrix[$row]['indlead_phase_4'] ." \t ". $data_matrix[$row]['indlead_phase_3'] ." \t ". $data_matrix[$row]['indlead_phase_2'] ." \t ". $data_matrix[$row]['indlead_phase_1'] ." \t ". $data_matrix[$row]['indlead_phase_0'] ." \t ". $data_matrix[$row]['indlead_phase_na'] ." \n";
				}
				else if($mode == 'active')
				{
					$TSV_data .= $data_matrix[$row]['active_phase_4'] ." \t ". $data_matrix[$row]['active_phase_3'] ." \t ". $data_matrix[$row]['active_phase_2'] ." \t ". $data_matrix[$row]['active_phase_1'] ." \t ". $data_matrix[$row]['active_phase_0'] ." \t ". $data_matrix[$row]['active_phase_na'] ." \n";
				}
				else
				{
					$TSV_data .= $data_matrix[$row]['total_phase_4'] ." \t ". $data_matrix[$row]['total_phase_3'] ." \t ". $data_matrix[$row]['total_phase_2'] ." \t ". $data_matrix[$row]['total_phase_1'] ." \t ". $data_matrix[$row]['total_phase_0'] ." \t ". $data_matrix[$row]['total_phase_na'] ." \n";
				}
			}
		}
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-type: application/force-download"); 
		header("Content-Type: application/tsv");
		header('Content-Disposition: attachment;filename="' . substr($Report_Name,0,20) . '_Product_Tracker_' . date('Y-m-d_H.i.s'). '.tsv"');
		header("Content-Transfer-Encoding: binary ");
		echo $TSV_data;
	}	/// TSV FUNCTION ENDS HERE
	
	if($_POST['dwformat']=='pdfdown')
	{
		require_once('tcpdf/tcpdf.php');  
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		//$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Larvol Trials');
		$pdf->SetTitle('Larvol Trials');
		$pdf->SetSubject('Larvol Trials');
		$pdf->SetKeywords('Larvol Trials Product Analytics, Larvol Trials Product Analytics PDF Export');
		// In pdf we have used two kinds of font- Actual text we are going to display will have size 8
		// While at other places like displying space in subcolumns of graph cell, we have used font size as 6, 
		// cause to display font 8 or 7 we require more width upto 2mm
		// we can't allocate 2mm width as we have total 100 subcolumsn of graph which leads to 200mm size only for Bar of Graph (total page size in normal orientation has only 210mm width including margin) so its not possible to have 8/7 font at any other places of graph otherwise PDF gets broken.
		
		$pdf->SetFont('verdana_old', '', 6);
		$pdf->setFontSubsetting(false);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
			
		// remove default header/footer
		$pdf->setPrintHeader(false);
		//set some language-dependent strings
		$pdf->setLanguageArray($l);
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->AddPage();
		
		$font_height = 6;
		$FontEight_font_height = 8;
		$Page_Width = 192;
		$product_Col_Width = 50;
		$Line_Height = 3.6;	/// Line height for font of size 6
		$FontEight_Line_Height = 3.96;	/// Line height for font of size 8
		$Min_One_Liner = 4.5;
		$Tic_dimension = 1;
		$subColumn_width = 1.4;
		
		$pdf->SetFont('verdanab', '', 8);	//Set font size as 8
		
		$Repo_Heading = $Report_Name.$TrackerName.' Product Tracker, '.$pdftitle;
		$current_StringLength = $pdf->GetStringWidth($Repo_Heading, 'verdanab', '', 8);
		$pdf->MultiCell($Page_Width, '', $Repo_Heading, $border=0, $align='C', $fill=0, $ln=1, '', '', $reseth=true, $stretch=0, $ishtml=true, $autopadding=true, $maxh=0);
		$pdf->Ln(5);
		$pdf->SetFont('verdana_old', '', 6);	//Reset font size as 6
		$pdf->setCellPaddings(0, 0, 0, 0);
		$pdf->setCellMargins(0, 0, 0, 0);
		
		$Main_X = $pdf->GetX();
		$Main_Y = $pdf->GetY();
		
		for($incr=0; $incr < count($rows); $incr++)
		{	
			$row = $incr;
			
			$dimensions = $pdf->getPageDimensions();
			//Height calculation depending on product name
			$rowcount = 0;
			
			$pdf->SetFont('verdana_old', '', 8);	//set font size as 8
 			//work out the number of lines required
			$rowcount = $pdf->getNumLines($data_matrix[$row]['productName'].$data_matrix[$row]['product_CompanyName'].((trim($data_matrix[$row]['productTag']) != '') ? ' ['.$data_matrix[$row]['productTag'].']':''), $product_Col_Width, $reseth = false, $autopadding = false, $cellpadding = '', $border = 0);
			$pdf->SetFont('verdana_old', '', 6);	//Reset font size as 6
			
			if($rowcount < 1) $rowcount = 1;
 			$startY = $pdf->GetY();
			$row_height = $rowcount * $FontEight_Line_Height;	//Apply line height of font size 8
			
			if($rowcount <= 1)
			$Extra_Spacing = 0;
			else
			$Extra_Spacing = ($row_height - $Line_Height) / 2;
			/// Next Row Height + Last Tick Row Height
			$Total_Height = 0;
			$Total_Height = $Tic_dimension + $row_height + $Tic_dimension + $Tic_dimension + $font_height;
			
			if (($startY + $Total_Height) + $dimensions['bm'] > ($dimensions['hk']))
			{
				//this row will cause a page break, draw the bottom border on previous row and give this a top border
				CreateLastTickBorder($pdf, $product_Col_Width, $Tic_dimension, $columns, $inner_columns, $subColumn_width, $column_interval);
				$pdf->AddPage();
			}
			
			$ln=0;
			$Main_X = $pdf->GetX();
			$Main_Y = $pdf->GetY();
			/// Bypass product column
			$Place_X = $Main_X+$product_Col_Width;
			$Place_Y = $Main_Y;
			
			if($row==0)
				$border = array('mode' => 'ext', 'TR' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			else
				$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
			$Place_X = $Place_X+$Tic_dimension;
			$Place_Y = $Place_Y;
			for($j=0; $j < $columns; $j++)
			{
				for($k=0; $k < $inner_columns; $k++)
				{
					if($k == $inner_columns-1 && $row!=0)
					$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					else
					$border = 0;
					if($j == $columns-1 && $k == $inner_columns-1) 
					$ln=1;
					
					$pdf->MultiCell($subColumn_width, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
					
					if($j == $columns-1 && $k == $inner_columns-1) 
					$Place_Y = $Place_Y+$Tic_dimension;
					$Place_X = $Place_X+$subColumn_width;
					
				}
			}
			
			$pdf->SetX($Main_X);
			$pdf->SetY($Place_Y);
			
			$Place_X = $pdf->GetX();
			$Place_Y = $pdf->GetY();
		
			$ln=0;
			$pdfContent = '<div align="right" style="vertical-align:top; float:none;"><a style="color:#000000; text-decoration:none;" href="'. urlPath() .'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part . '" target="_blank" title="'. $title .'">'.$data_matrix[$row]['productName'].$data_matrix[$row]['product_CompanyName'].'</a>'.((trim($data_matrix[$row]['productTag']) != '') ? ' <font style="color:#120f3c;">['.$data_matrix[$row]['productTag'].']</font>':'').'</div>';
			$border = array('mode' => 'ext', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			
			$pdf->SetFont('freesans', ' ', 8, '', false); // Font size as 8
			$pdf->MultiCell($product_Col_Width, $row_height, $pdfContent, $border=0, $align='R', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$row_height);
			$pdf->SetFont('verdana_old', '', 6);	//Reset font size as 6
			
			$Place_X = $Place_X + $product_Col_Width;
			if($row==0)
				$border = array('mode' => 'ext', 'TB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)), 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,255)));
			else
				$border = array('mode' => 'ext', 'B' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)), 'LT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,255)));
			$pdf->MultiCell($Tic_dimension, $Line_Height, '', $border=0, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$row_height);
			
			$Place_X = $Place_X + $Tic_dimension;
			$Middle_Place = $Place_X;
			
			///// Part added to divide extra space formed by multiple rows of product name
			if($Extra_Spacing > 0)
			{
				$ln=0;
				$Place_X = $Middle_Place;
				$Place_Y = $Place_Y;
				for($j=0; $j < $columns; $j++)
				{
					for($k=0; $k < $inner_columns; $k++)
					{
						if($k == $inner_columns-1)
						$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else if ($k == 0 && $j==0)
						$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else
						$border = 0;
						if($j == $columns-1 && $k == $inner_columns-1) 
						$ln=1;
						
						$pdf->MultiCell($subColumn_width, $Extra_Spacing, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Extra_Spacing);
					
						if($j == $columns-1 && $k == $inner_columns-1) 
						$Place_Y = $Place_Y+$Extra_Spacing;
						
						$Place_X = $Place_X+$subColumn_width;
						
					}
				}
			}
			///// End of Part added to divide extra space formed by multiple rows of product name
			
			$Place_X = $Middle_Place;
			//// Graph starts
			if($mode == 'indlead')
			{
				$Err = IndleadCountErr($data_matrix, $row, $ratio);
				$Max_ValueKey = Max_ValueKey($data_matrix[$row]['indlead_phase_na'], $data_matrix[$row]['indlead_phase_0'], $data_matrix[$row]['indlead_phase_1'], $data_matrix[$row]['indlead_phase_2'], $data_matrix[$row]['indlead_phase_3'], $data_matrix[$row]['indlead_phase_4']);
				$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['indlead']);
				$phase_space = 0;
				
				foreach($phase_legend_nums as $key => $phase_nums)
				{
					if($data_matrix[$row]['indlead_phase_'.$phase_nums] > 0)
					{
						$border = setStyleforPDFExport($phase_nums, $pdf);
						$Width = $subColumn_width;
						$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['indlead_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
						$phase_space =  $phase_space + $Mini_Bar_Width;
						
						$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['indlead_phase_'.$phase_nums], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
						$m=0;
						while($m < $Mini_Bar_Width)
						{
							$Color = getClassNColorforPhase($phase_nums);
							$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#'.$Color[1].'; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part .'&phase='. $phase_nums . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
							$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
							$Place_X = $Place_X + $Width;
							$m++;
						}
					}
				} /// Foreach ends
			} 
			else if($mode == 'active')
			{
				$Err = ActiveCountErr($data_matrix, $row, $ratio);
				$Max_ValueKey = Max_ValueKey($data_matrix[$row]['active_phase_na'], $data_matrix[$row]['active_phase_0'], $data_matrix[$row]['active_phase_1'], $data_matrix[$row]['active_phase_2'], $data_matrix[$row]['active_phase_3'], $data_matrix[$row]['active_phase_4']);
				$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['active']);
				$phase_space = 0;
				
				foreach($phase_legend_nums as $key => $phase_nums)
				{
					if($data_matrix[$row]['active_phase_'.$phase_nums] > 0)
					{
						$border = setStyleforPDFExport($phase_nums, $pdf);
						$Width = $subColumn_width;
						$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['active_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
						$phase_space =  $phase_space + $Mini_Bar_Width;
						
						$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['active_phase_'.$phase_nums], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
						$m=0;
						while($m < $Mini_Bar_Width)
						{
							$Color = getClassNColorforPhase($phase_nums);
							$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#'.$Color[1].'; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part .'&phase='. $phase_nums . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
							$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
							$Place_X = $Place_X + $Width;
							$m++;
						}
					}
				} ///Foreach ends
			} 
			else if($mode == 'total')
			{
				$Err = TotalCountErr($data_matrix, $row, $ratio);
				
				$Max_ValueKey = Max_ValueKey($data_matrix[$row]['total_phase_na'], $data_matrix[$row]['total_phase_0'], $data_matrix[$row]['total_phase_1'], $data_matrix[$row]['total_phase_2'], $data_matrix[$row]['total_phase_3'], $data_matrix[$row]['total_phase_4']);
					
				$Total_Bar_Width = ceil($ratio * $data_matrix[$row]['total']);
				$phase_space = 0;
				
				foreach($phase_legend_nums as $key => $phase_nums)
				{
					if($data_matrix[$row]['total_phase_'.$phase_nums] > 0)
					{
						$border = setStyleforPDFExport($phase_nums, $pdf);
						$Width = $subColumn_width;
						$Mini_Bar_Width = CalculateMiniBarWidth($ratio, $data_matrix[$row]['total_phase_'.$phase_nums], $phase_nums, $Max_ValueKey, $Err, $Total_Bar_Width);
						$phase_space =  $phase_space + $Mini_Bar_Width;
						
						$pdf->Annotation($Place_X, $Place_Y, ($Width*$Mini_Bar_Width), $Line_Height, $data_matrix[$row]['total_phase_'.$phase_nums], array('Subtype'=>'Caret', 'Name' => 'Comment', 'T' => 'Trials', 'Subj' => 'Information', 'C' => array()));	
						
						$m=0;
						while($m < $Mini_Bar_Width)
						{
							$Color = getClassNColorforPhase($phase_nums);
							$pdfContent = '<div align="center" style="vertical-align:top; float:none;"><a style="color:#'.$Color[1].'; text-decoration:none; line-height:2px;" href="'. urlPath() .'intermediary.php?e1=' . $data_matrix[$row]['productIds'] . $link_part .'&phase='. $phase_nums . '" target="_blank" title="'. $title .'">&nbsp;</a></div>';
							$pdf->MultiCell($Width, $Line_Height, $pdfContent, $border=0, $align='C', $fill=1, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
							$Place_X = $Place_X + $Width;
							$m++;
						}
					}
				} ///Foreach ends
			}
			
			$total_cols = $inner_columns * $columns;
			$remain_span = $total_cols - $phase_space;
		
			if($remain_span > 0)
			{
				$aq_sp = 0;
				while($aq_sp < $phase_space)
				$aq_sp = $aq_sp + $inner_columns;
				
				$extra_sp = $aq_sp - $phase_space;
				if($extra_sp > 0)
				{
					$Width = $extra_sp * $subColumn_width;
					$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					$pdf->MultiCell($Width, $Line_Height, '', $border=0, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
					$Place_X = $Place_X + $Width;
				}
				
				$remain_span = $remain_span - $extra_sp;
				while($remain_span > 0)
				{
					$Width = $inner_columns * $subColumn_width;
					$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					$pdf->MultiCell($Width, $Line_Height, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
					$Place_X = $Place_X + $Width;
					$remain_span = $remain_span - $inner_columns;
				//	if($remain_span <= $inner_columns )
				//	$ln=1;
				}
			} // End of remain span
			
			///EXTRA CELL FOR MAKING LINEBREAK
			$ln=1;
			$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell(1, $Line_Height, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=true, $autopadding=false, $maxh=$Line_Height);
			$Place_Y = $Place_Y + $Line_Height;
			///// Part added to divide extra space formed by multiple rows of product name
			if($Extra_Spacing > 0)
			{
				$ln=0;
				$Place_X = $Middle_Place;
				$Place_Y = $Place_Y;
				for($j=0; $j < $columns; $j++)
				{
					for($k=0; $k < $inner_columns; $k++)
					{
						if($k == $inner_columns-1)
						$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else if ($k == 0 && $j==0)
						$border = array('mode' => 'int', 'L' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
						else
						$border = 0;
						if($j == $columns-1 && $k == $inner_columns-1) 
						$ln=1;
						
						$pdf->MultiCell($subColumn_width, $Extra_Spacing, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Extra_Spacing);
					
						if($j == $columns-1 && $k == $inner_columns-1) 
						$Place_Y = $Place_Y+$Extra_Spacing;
						
						$Place_X = $Place_X+$subColumn_width;
						
					}
				}
			}
			///// End of Part added to divide extra space formed by multiple rows of product name
			
			$ln=0;
			$Place_X = $Main_X;
			$Place_Y = $Place_Y;
			/// Bypass product column
			$Place_X =$Place_X+$product_Col_Width;
			$Place_Y = $Place_Y;
			$border = array('mode' => 'ext', 'RB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
			$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
			$Place_X = $Place_X+$Tic_dimension;
			$Place_Y = $Place_Y;
			for($j=0; $j < $columns; $j++)
			{
				for($k=0; $k < $inner_columns; $k++)
				{
					if($k == $inner_columns-1)
					$border = array('mode' => 'ext', 'R' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
					else
					$border = 0;
					if($j == $columns-1 && $k == $inner_columns-1) 
					$ln=1;
					
					$pdf->MultiCell($subColumn_width, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
					
					if($j == $columns-1 && $k == $inner_columns-1) 
					$Place_Y = $Place_Y+$Tic_dimension;
					
					$Place_X = $Place_X+$subColumn_width;
					
				}
			}
			
			$pdf->SetX($Main_X);
			$pdf->SetY($Place_Y);
		}
		
		
		
		CreateLastTickBorder($pdf, $product_Col_Width, $Tic_dimension, $columns, $inner_columns, $subColumn_width, $column_interval);
			
		ob_end_clean();
		//Close and output PDF document
		$pdf->Output(''. substr($Report_Name,0,20) .'_Product_Tracker_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
	}	/// End of PDF Function
	
	//Start of Real Chart Excel
	if($_POST['dwformat']=='excelchartdown')
	{
		$Repo_Heading = $Report_Name.', '.$pdftitle;
		
		$objPHPExcel = new PHPExcel();
		$WorksheetName = 'Product_Tracker';
		$objPHPExcel->getActiveSheet()->setTitle($WorksheetName);
		$sheetPHPExcel = $objPHPExcel->getActiveSheet();
		
		//Create Input Array for Excel Sheet in required format
		$ExcelChartArray = array();	///Input array
		
		$FirstGraphPnt = 4;
		if(count($rows) < 6)
			$LastGraphPnt = round($FirstGraphPnt + (count($rows) * 4));
		else
			$LastGraphPnt = round($FirstGraphPnt + (count($rows) * 2.6));
		
		//Start placing data after the 20 rows plus after our graph ends
		$CurrentExcelRow = $LastGraphPnt + 20;
		$DataStartRow = $CurrentExcelRow;
		
		$DataColumns = array('BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH');
		
		//Add Phase Array to Input Array
		$CurrentExcelChartArray = array(  '', 'phase N/A', 'phase 0', 'phase 1', 'phase 2', 'phase 3', 'phase 4');
		$ExcelChartArray[] = $CurrentExcelChartArray;
		
		foreach($DataColumns as $colId=>$colName)
		{
			$objPHPExcel->getActiveSheet()->setCellValue($colName.$CurrentExcelRow, $CurrentExcelChartArray[$colId]);
			//Set row dimenstion minimum as dont want to view data
			$objPHPExcel->getActiveSheet()->getRowDimension($CurrentExcelRow)->setRowHeight(0.1);
		
		}
		
		//Add each product data array to Input Array
		for($decr=(count($rows) - 1); $decr >= 0 ; $decr--)
		{
			$CurrentExcelChartArray = array();
			$currentRow = $decr;
			if(isset($data_matrix[$currentRow]['productIds']) && $data_matrix[$currentRow]['productIds'] != NULL)
			{
				if($_POST['dwcount']=='active')
					$CurrentExcelChartArray = array($data_matrix[$currentRow]['productName'].$data_matrix[$currentRow]['product_CompanyName'], $data_matrix[$currentRow]['active_phase_na'], $data_matrix[$currentRow]['active_phase_0'], $data_matrix[$currentRow]['active_phase_1'], $data_matrix[$currentRow]['active_phase_2'], $data_matrix[$currentRow]['active_phase_3'], $data_matrix[$currentRow]['active_phase_4']);
				else 
					if($_POST['dwcount']=='total')
						$CurrentExcelChartArray = array($data_matrix[$currentRow]['productName'].$data_matrix[$currentRow]['product_CompanyName'], $data_matrix[$currentRow]['total_phase_na'], $data_matrix[$currentRow]['total_phase_0'], $data_matrix[$currentRow]['total_phase_1'], $data_matrix[$currentRow]['total_phase_2'], $data_matrix[$currentRow]['total_phase_3'], $data_matrix[$currentRow]['total_phase_4']);
					else
						$CurrentExcelChartArray = array($data_matrix[$currentRow]['productName'].$data_matrix[$currentRow]['product_CompanyName'], $data_matrix[$currentRow]['indlead_phase_na'], $data_matrix[$currentRow]['indlead_phase_0'], $data_matrix[$currentRow]['indlead_phase_1'], $data_matrix[$currentRow]['indlead_phase_2'], $data_matrix[$currentRow]['indlead_phase_3'], $data_matrix[$currentRow]['indlead_phase_4']);
			}
			else
			{
				$CurrentExcelChartArray = array('', 0, 0, 0, 0, 0, 0);
			}
			
			$ExcelChartArray[] = $CurrentExcelChartArray;
			$CurrentExcelRow++;
			foreach($DataColumns as $colId=>$colName)
			{
				$objPHPExcel->getActiveSheet()->setCellValue($colName.$CurrentExcelRow, $CurrentExcelChartArray[$colId]);
			}
			
			//Set row dimenstion zero as dont want to view data
			$objPHPExcel->getActiveSheet()->getRowDimension($CurrentExcelRow)->setRowHeight(0.1);
		}
		//End of Input Array
		
		//Below will automatically places data starting from 'A' column but we are putting manually as we dont want to start from column 'A'
		//$sheetPHPExcel->fromArray($ExcelChartArray);
		
		//Add reference to data columns
		$labels = $values = array();
		foreach($DataColumns as $colName)
		{
			//set width of data columns minimum as we dont want to view this data
			$objPHPExcel->getActiveSheet()->getColumnDimension($colName)->setWidth(0.1);
			if($colName == 'BA') continue;
			$labels[] = new PHPExcel_Chart_DataSeriesValues('String', $WorksheetName.'!$'.$colName.'$'.$DataStartRow, null, 1);
			$values[] = new PHPExcel_Chart_DataSeriesValues('Number', $WorksheetName.'!$'.$colName.'$'.($DataStartRow+1).':$'.$colName.'$'.($DataStartRow + count($rows)), null, 4);
		}
		
		$categories = array(
		  new PHPExcel_Chart_DataSeriesValues('String', $WorksheetName.'!$'.$DataColumns[0].'$'.($DataStartRow+1).':$'.$DataColumns[0].'$'.($DataStartRow + count($rows)), null, 4),
		);
	
		$series = new PHPExcel_Chart_DataSeries(
		  PHPExcel_Chart_DataSeries::TYPE_BARCHART,       // plotType
		  PHPExcel_Chart_DataSeries::GROUPING_STACKED,    // plotGrouping
		  array(5, 4, 3, 2, 1, 0),                        // plotOrder
		  $labels,                                        // plotLabel
		  $categories,                                    // plotCategory
		  $values                                         // plotValues
		);
		
		$series->setPlotDirection(PHPExcel_Chart_DataSeries::DIRECTION_BAR);
		$plotarea = new PHPExcel_Chart_PlotArea(null, array($series));
		$legend = new PHPExcel_Chart_Legend(PHPExcel_Chart_Legend::POSITION_RIGHT, null, false);
		$title = new PHPExcel_Chart_Title('');
		$X_Label = new PHPExcel_Chart_Title('Products');
		$Y_Label = new PHPExcel_Chart_Title('Number of Trials');
		$chart = new PHPExcel_Chart(
		  'Product Tracker',                                // name
		  $title,                                           // title
		  $legend,                                        	// legend
		  $plotarea,                                      	// plotArea
		  true,                                          	// plotVisibleOnly
		  0,                                              	// displayBlanksAs
		 $X_Label,                                          // xAxisLabel
		 $Y_Label                                           // yAxisLabel
		);

		$chart->setTopLeftPosition('A'.$FirstGraphPnt);
		$chart->setBottomRightPosition('T'.$LastGraphPnt);
		$sheetPHPExcel->addChart($chart);
		$Writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$Writer->setIncludeCharts(TRUE);
		
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		
		//Set report name
		$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Report name:');
		$objPHPExcel->getActiveSheet()->mergeCells('B1:AA1');
		$objPHPExcel->getActiveSheet()->getStyle('B1')->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		
		$objPHPExcel->getActiveSheet()->SetCellValue('B1', $Report_Name.$TrackerName.' Product Tracker');
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Display Mode:');
		$objPHPExcel->getActiveSheet()->mergeCells('B2:AA2');
		$objPHPExcel->getActiveSheet()->getStyle('B2')->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => false));
		$objPHPExcel->getActiveSheet()->SetCellValue('B2', $tooltip);
		
		$objPHPExcel->getProperties()->setCreator(SITE_NAME);
		$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
		$objPHPExcel->getProperties()->setTitle(substr($name,0,20));
		$objPHPExcel->getProperties()->setSubject(substr($name,0,20));
		$objPHPExcel->getProperties()->setDescription(substr($name,0,20));
		
		ob_end_clean(); 
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . substr($Report_Name,0,20) . '_Product_Tracker_' . date('Y-m-d_H.i.s') . '.xlsx"');
		header("Content-Transfer-Encoding: binary ");
		
		$Writer->save('php://output');
	}
	//End of Real Chart Excel
}

function getColspanforExcelExport($cell, $inc)
{
	for($i = 1; $i < $inc; $i++)
	{
		$cell++;
	}
	return $cell;
}

function getBGColorforExcelExport($phase)
{
	if($phase == '0')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => '00CCFF'),
									'endcolor'   => array('rgb' => '00CCFF'))
						));
	}
	else if($phase == '1')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => '99CC00'),
									'endcolor'   => array('rgb' => '99CC00'))
						));
	}
	else if($phase == '2')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'FFFF00'),
									'endcolor'   => array('rgb' => 'FFFF00'))
						));
	}
	else if($phase == '3')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'FF9900'),
									'endcolor'   => array('rgb' => 'FF9900'))
						));
	}
	else if($phase == '4')
	{
		$bgColor = (array('fill' => array('type'       => PHPExcel_Style_Fill::FILL_SOLID,
									'rotation'   => 0,
									'startcolor' => array('rgb' => 'FF0000'),
									'endcolor'   => array('rgb' => 'FF0000'))
						));
	}
	else if($phase == 'na')
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
	
	return $bgColor;
}

function getClassNColorforPhase($phase)
{
	$Color = array();
	if($phase == '0')
	{
		$Color[0] = 'graph_blue';
		$Color[1] = 'BFBFBF';
	}
	else if($phase == '1')
	{
		$Color[0] = 'graph_green';
		$Color[1] = '99CC00';
	}
	else if($phase == '2')
	{
		$Color[0] = 'graph_yellow';
		$Color[1] = 'FFFF00';
	}
	else if($phase == '3')
	{
		$Color[0] = 'graph_orange';
		$Color[1] = 'FF9900';
	}
	else if($phase == '4')
	{
		$Color[0] = 'graph_red';
		$Color[1] = 'FF0000';
	}
	else if($phase == 'na')
	{
		$Color[0] = 'graph_gray';
		$Color[1] = 'BFBFBF';
	}
	else
	{
		$Color[0] = 'graph_gray';
		$Color[1] = 'BFBFBF';
	}
	
	return $Color;
}

function CreatePhaseCellforExcelExport($from, $Mini_Bar_Width, $url, $Excel_HMCounter, $countValue, $phase, &$objPHPExcel)
{
	$to = getColspanforExcelExport($from, $Mini_Bar_Width);
	$objPHPExcel->getActiveSheet()->mergeCells($from . $Excel_HMCounter . ':' . $to . $Excel_HMCounter);
	$objPHPExcel->getActiveSheet()->getStyle($from . $Excel_HMCounter)->applyFromArray(getBGColorforExcelExport($phase));
	$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setUrl($url); 
	$objPHPExcel->getActiveSheet()->getCell($from . $Excel_HMCounter)->getHyperlink()->setTooltip($countValue);
	$from = $to;
	$from++;
	
	return $from;
}

function  setStyleforPDFExport($phase, &$pdf)
{
	if($phase == '0')
	{
		$pdf->SetFillColor(0,204,255);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,204,255)));
	}
	else if($phase == '1')
	{
		$pdf->SetFillColor(153,204,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(153,204,0)));
	}
	else if($phase == '2')
	{
		$pdf->SetFillColor(255,255,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,255,0)));
	}
	else if($phase == '3')
	{
		$pdf->SetFillColor(255,153,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,153,0)));
	}
	else if($phase == '4')
	{
		$pdf->SetFillColor(255,0,0);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255,0,0)));
	}
	else if($phase == 'na')
	{
		$pdf->SetFillColor(191,191,191);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(191,191,191)));
	}
	else
	{
		$pdf->SetFillColor(191,191,191);
		$border = array('mode' => 'int', 'LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(191,191,191)));
	}
	
	return $border;
}

function CreateLastTickBorder(&$pdf, $product_Col_Width, $Tic_dimension, $columns, $inner_columns, $subColumn_width, $column_interval)
{
	$ln=0;
	$Main_X = $pdf->GetX();
	$Main_Y = $pdf->GetY();
	/// Bypass product column
	$pdf->MultiCell($product_Col_Width, $Tic_dimension, 'Trials', 0, $align='R', $fill=0, $ln, $Main_X, $Main_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);

	$Place_X = $Main_X+$product_Col_Width;
	$Place_Y = $Main_Y;
	/// SET NOT REQUIRED BORDERS TO WHITE COLORS THAT WILL MAKE TABLE COMPACT OTHERWISE HEIGHT/WIDTH ISSUE HAPPENS
	$border = array('mode' => 'ext', 'RT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
	$pdf->MultiCell($Tic_dimension, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
	$Place_X = $Main_X+$Tic_dimension;
	$Place_Y = $Main_Y;
	for($j=0; $j < $columns; $j++)
	{
		$Width = $inner_columns * $subColumn_width;
		$border = array('mode' => 'ext', 'RT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(204,204,204)));
		$pdf->MultiCell($Width, $Tic_dimension, '', $border, $align='C', $fill=0, $ln, '', '', $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=$Tic_dimension);
		$Place_X = $Main_X+$Width;
		
		if($j == $columns-1) 
		$Place_Y = $Place_Y+$Tic_dimension;
	}
	$pdf->SetX($Main_X);
	$pdf->SetY($Place_Y);
	
	$ln=0;
	$Main_X = $pdf->GetX();
	$Main_Y = $pdf->GetY();
	/// Bypass product column
	$Place_X = $Main_X+$product_Col_Width;
	$Place_Y = $Main_Y;
	/// SET NOT REQUIRED BORDERS TO WHITE COLORS THAT WILL MAKE TABLE COMPACT OTHERWISE HEIGHT/WIDTH ISSUE HAPPENS
	$border = 0;
	$pdf->MultiCell(($Tic_dimension * 2.5), $Tic_dimension, '0', $border, $align='R', $fill=0, $ln, $Place_X, $Place_Y, $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
	$Place_X = $Main_X+$Tic_dimension;
	$Place_Y = $Main_Y;
	for($j=0; $j < $columns; $j++)
	{
		if($j==0)
		$Width = ($inner_columns * $subColumn_width);
		else
		$Width = $inner_columns * $subColumn_width;
		$border = 0;
		$pdf->MultiCell($Width, $Tic_dimension, ($column_interval == 0 ? ($j+1 == $columns ? ($j+1) * $column_interval : "") :($j+1) * $column_interval), $border, $align='R', $fill=0, $ln, '', '', $reseth=false, $stretch=0, $ishtml=false, $autopadding=false, $maxh=0);
		$Place_X = $Main_X+$Width;
			
		if($j == $columns-1) 
		$Place_Y = $Place_Y+$Tic_dimension;
	}
	$pdf->SetX($Main_X);
	$pdf->SetY($Place_Y);
}

function Max_ValueKey($valna, $val0, $val1, $val2, $val3, $val4)
{
$key = 'na';
$max = $valna;

	if($max < $val0)
	{
		$max = $val0;
		$key = '0';
	}
	
	if($max < $val1)
	{
		$max = $val1;
		$key = '1';
	}
	
	if($max < $val2)
	{
		$max = $val2;
		$key = '2';
	}
	
	if($max < $val3)
	{
		$max = $val3;
		$key = '3';
	}
	
	if($max < $val4)
	{
		$max = $val4;
		$key = '4';
	}
	
	return $key;
}

function sortTwoDimensionArrayByKey($arr, $arrKey, $sortOrder=SORT_DESC)
{
	if(is_array($arr) && count($arr) > 0)
	{
		foreach ($arr as $key => $row)
		{
			$key_arr[$key] = $row[$arrKey];
		}
		array_multisort($key_arr, $sortOrder, $arr);
	}
	return $arr;
}

function CalculateMiniBarWidth($Ratio, $countValue, $Key, $Max_ValueKey, $Err, $Total_Bar_Width)
{
	if(round($Ratio * $countValue) > 0)
		$Mini_Bar_Width = round($Ratio * $countValue);
	else
		$Mini_Bar_Width = 1;
	
	if($Max_ValueKey == $Key && $Mini_Bar_Width > 1 && $Mini_Bar_Width > $Err)
	$Mini_Bar_Width = $Mini_Bar_Width - $Err;
	
	if(($Total_Bar_Width - $Mini_Bar_Width) > 0)
		$Total_Bar_Width = $Total_Bar_Width - $Mini_Bar_Width;
	else
		$Mini_Bar_Width = $Total_Bar_Width;
		
		return $Mini_Bar_Width;
}

function IndleadCountErr($data_matrix, $row, $ratio)
{
	$Rounded = (($data_matrix[$row]['indlead_phase_4'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_4'])) + (($data_matrix[$row]['indlead_phase_3'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_3'])) + (($data_matrix[$row]['indlead_phase_2'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_2'])) + (($data_matrix[$row]['indlead_phase_1'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_1'])) + (($data_matrix[$row]['indlead_phase_0'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_0'])) + (($data_matrix[$row]['indlead_phase_na'] > 0 && round($ratio * $data_matrix[$row]['indlead_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['indlead_phase_na']));
	$Actual = ($ratio * $data_matrix[$row]['indlead_phase_4']) + ($ratio * $data_matrix[$row]['indlead_phase_3']) + ($ratio * $data_matrix[$row]['indlead_phase_2']) + ($ratio * $data_matrix[$row]['indlead_phase_1']) + ($ratio * $data_matrix[$row]['indlead_phase_0'])+ ($ratio * $data_matrix[$row]['indlead_phase_na']);
	$Err = floor($Rounded - $Actual);
	
	return $Err;
}

function ActiveCountErr($data_matrix, $row, $ratio)
{
	$Rounded = (($data_matrix[$row]['active_phase_4'] > 0 && round($ratio * $data_matrix[$row]['active_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_4'])) + (($data_matrix[$row]['active_phase_3'] > 0 && round($ratio * $data_matrix[$row]['active_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_3'])) + (($data_matrix[$row]['active_phase_2'] > 0 && round($ratio * $data_matrix[$row]['active_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_2'])) + (($data_matrix[$row]['active_phase_1'] > 0 && round($ratio * $data_matrix[$row]['active_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_1'])) + (($data_matrix[$row]['active_phase_0'] > 0 && round($ratio * $data_matrix[$row]['active_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_0'])) + (($data_matrix[$row]['active_phase_na'] > 0 && round($ratio * $data_matrix[$row]['active_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['active_phase_na']));
	$Actual = ($ratio * $data_matrix[$row]['active_phase_4']) + ($ratio * $data_matrix[$row]['active_phase_3']) + ($ratio * $data_matrix[$row]['active_phase_2']) + ($ratio * $data_matrix[$row]['active_phase_1']) + ($ratio * $data_matrix[$row]['active_phase_0'])+ ($ratio * $data_matrix[$row]['active_phase_na']);
	$Err = floor($Rounded - $Actual);
	
	return $Err;
}

function TotalCountErr($data_matrix, $row, $ratio)
{
	$Rounded = (($data_matrix[$row]['total_phase_4'] > 0 && round($ratio * $data_matrix[$row]['total_phase_4']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_4'])) + (($data_matrix[$row]['total_phase_3'] > 0 && round($ratio * $data_matrix[$row]['total_phase_3']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_3'])) + (($data_matrix[$row]['total_phase_2'] > 0 && round($ratio * $data_matrix[$row]['total_phase_2']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_2'])) + (($data_matrix[$row]['total_phase_1'] > 0 && round($ratio * $data_matrix[$row]['total_phase_1']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_1'])) + (($data_matrix[$row]['total_phase_0'] > 0 && round($ratio * $data_matrix[$row]['total_phase_0']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_0'])) + (($data_matrix[$row]['total_phase_na'] > 0 && round($ratio * $data_matrix[$row]['total_phase_na']) < 1) ? 1:round($ratio * $data_matrix[$row]['total_phase_na']));
	$Actual = ($ratio * $data_matrix[$row]['total_phase_4']) + ($ratio * $data_matrix[$row]['total_phase_3']) + ($ratio * $data_matrix[$row]['total_phase_2']) + ($ratio * $data_matrix[$row]['total_phase_1']) + ($ratio * $data_matrix[$row]['total_phase_0'])+ ($ratio * $data_matrix[$row]['total_phase_na']);
	$Err = floor($Rounded - $Actual);
	
	return $Err;
}

/* Function to get Product Id's from Institution id */
function GetProductsFromCompany($companyID)
{
	global $db;
	global $now;
	$Products = array();
	/***************************below changes done so that report is generated from old relations table products_institutions   */
//	$query = "SELECT et.`id` FROM `entities` et LEFT JOIN `entity_relations` er ON(et.`id` = er.`parent`) WHERE et.`class`='Product' AND er.`child`='" . mysql_real_escape_string($companyID) . "'";
	$query = "SELECT pt.`id` FROM `products` pt LEFT JOIN `products_institutions` pi ON(pt.`id` = pi.`product`)  WHERE pi.`institution`='" . mysql_real_escape_string($companyID) . "'";
	/**************************/
	
	$res = mysql_query($query) or die('Bad SQL query getting products from institution id in PT');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$Products[] = $row['id'];
		}
	}
	return array_filter(array_unique($Products));
}

/* Function to get Product Id's from MOA id */
function GetProductsFromMOA($moaID)
{
	global $db;
	global $now;
	$Products = array();
	$query = "SELECT et.`id` FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`)  WHERE et.`class`='Product' and er.`child`='" . mysql_real_escape_string($moaID) . "'";
	$res = mysql_query($query) or die('Bad SQL query getting products from moa id in PT');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$Products[] = $row['id'];
		}
	}
	return array_filter(array_unique($Products));
}

//Get producrs froms disease
function GetProductsFromDisease($DiseaseID)
{
	global $db;
	global $now;
	$Products = array();
	$query = "SELECT DISTINCT `id` FROM `entities` e JOIN `entity_trials` et ON(et.`entity` = e.`id`) WHERE e.`class` = 'Product' AND et.trial IN (select et2.`trial` FROM `entity_trials` et2 WHERE et2.`entity`='" . mysql_real_escape_string($DiseaseID) . "')";
	$res = mysql_query($query) or die('Bad SQL query getting products from Disease id in PT');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$Products[] = $row['id'];
		}
	}
	return array_filter(array_unique($Products));
}

/* Function to get Product Id's from MOA Category id */
function GetProductsFromMOACategory($moaCatID)
{
	global $db;
	global $now;
	$Products = array();
	$query = "SELECT et.`id` FROM `entities` et JOIN `entity_relations` er ON(et.`id` = er.`parent`)  WHERE et.`class`='Product' and er.`child` IN (SELECT et2.`id` FROM `entities` et2 JOIN `entity_relations` er2 ON(et2.`id` = er2.`child`)  WHERE et2.`class`='MOA' AND er2.`parent`='". mysql_real_escape_string($moaCatID) ."')";
	$res = mysql_query($query) or die('Bad SQL query getting products from moa id in PT');
	
	if($res)
	{
		while($row = mysql_fetch_array($res))
		{
			$Products[] = $row['id'];
		}
	}
	return array_filter(array_unique($Products));
}

?>