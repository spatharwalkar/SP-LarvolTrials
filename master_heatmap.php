<?php
require_once('db.php');
require_once('report_common.php');

require_once('PHPExcel.php');
require_once('PHPExcel/Writer/Excel2007.php');
require_once('include.excel.php');
require_once('class.phpmailer.php');

ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours

if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
if($_POST['dwformat'])
{
	if($_POST['dwformat']=='htmldown')
		header('Location: ' . urlPath() . 'online_heatmap.php?id='.$_POST['id']);
	else
		Download_reports();
}
else {
require('header.php');
?>
<script type="text/javascript">
function autoComplete(fieldID)
{	
	$(function()
	{
		if($('#'+fieldID).length > 0)
		{	
			var pattern1 =/products/g;
			var pattern2 =/areas/g;
			
			if(pattern1.test(fieldID))
			{	
				var a = $('#'+fieldID).autocomplete({
							serviceUrl:'autosuggest.php',
							params:{table:'products', field:'name'},
							minChars:3 
				});
			}
			else if(pattern2.test(fieldID))
			{
				var a = $('#'+fieldID).autocomplete({
							serviceUrl:'autosuggest.php',
							params:{table:'areas', field:'name'},
							minChars:3 
				});
			}
		}
	});
}
</script>
<script type="text/javascript">
function update_icons(type, row, col, tot_rows, tot_cols, BG_color)
{
	if(type=='phase4')
	{
	  if(document.getElementById("phase4opt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("phase4_val_"+row+"_"+col).value='1';
		document.getElementById("phase4_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="phase4img_'+row+'_'+col+'" title="Red cell override" src="images/phase4.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Phase4_Override"/>&nbsp;';
	  }
	  else
	  {
	 	// if(!confirm("Do you really want to unset phase4_override")) {document.getElementById("phase4opt_"+row+"_"+col).checked = true; return true;}
		 document.getElementById("phase4_val_"+row+"_"+col).value='0';
		 document.getElementById("phase4_pos_"+row+"_"+col).innerHTML = '';
	  }
	}
	
	if(type=='bomb')
	{
	  if(document.getElementById("bombopt_"+row+"_"+col).checked == true)
	  {
	  	//document.getElementById("bombpopup_"+row+"_"+col).style.display = 'block';
		
		var bk_bomb = document.getElementById("bk_bombselect_"+row+"_"+col).value;
		
		var bk_bomb = bk_bomb.replace(/\s+/g, '') ;
		if(bk_bomb == 'small')
		{
			document.getElementById("bombselect_"+row+"_"+col).value='small';
			var bomb_src='new_sbomb.png';
		}
		else if(bk_bomb == "large")
		{
			document.getElementById("bombselect_"+row+"_"+col).value='large';
			var bomb_src='new_lbomb.png';
		}
		else if(bk_bomb == 'none')
		{
			document.getElementById("bombselect_"+row+"_"+col).value='large';
			var bomb_src='new_lbomb.png';
		}
		
		document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="bombimg_'+row+'_'+col+'" title="Edit Large Bomb Details" src="images/'+bomb_src+'" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Large Bomb" onclick="popup_show(\'bomb\', '+tot_rows+','+tot_cols+',\'bombpopup_'+row+'_'+col+'\', \'bombpopup_drag_'+row+'_'+col+'\', \'bombpopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
		
		document.getElementById("bomb_explain_"+row+"_"+col).value = document.getElementById("bk_bomb_explain_"+row+"_"+col).value;
		}
	  else
	  {
	 	 document.getElementById("bomb_pos_"+row+"_"+col).innerHTML = '';
		 document.getElementById("bombselect_"+row+"_"+col).value='none';
		 document.getElementById("bomb_explain_"+row+"_"+col).value='';
		 document.getElementById("bombpopup_"+row+"_"+col).style.display = 'none';
	  }
	}
	
	if(type=='filing')
	{
	  if(document.getElementById("filingopt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("filing_pos_"+row+"_"+col).innerHTML = '<img align="middle" id="filingimg_'+row+'_'+col+'" title="Edit Filing" src="images/new_file.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Edit Filing" onclick="popup_show(\'filing\', '+tot_rows+','+tot_cols+',\'filingpopup_'+row+'_'+col+'\', \'filingpopup_drag_'+row+'_'+col+'\', \'filingpopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
		document.getElementById("filing_"+row+"_"+col).value=document.getElementById("bk_filing_"+row+"_"+col).value;
		//document.getElementById("filingpopup_"+row+"_"+col).style.display = 'block';
	  }
	  else
	  {
	 	document.getElementById("filing_pos_"+row+"_"+col).innerHTML = '';
		 document.getElementById("filing_"+row+"_"+col).value = '';
		 document.getElementById("filingpopup_"+row+"_"+col).style.display = 'none';
	  }
	}
	
	if(type=='phaseexp')
	{
	  if(document.getElementById("phaseexpopt_"+row+"_"+col).checked == true)
	  {
	  	document.getElementById("phaseexp_pos_"+row+"_"+col).innerHTML = '<img id="Phase_Explain_'+row+'_'+col+'" src="images/phaseexp.png" title="Edit Phase Explain" style="width: 20px; height: 20px; vertical-align: bottom; cursor: pointer; " alt="Phase Explain" onclick="popup_show(\'phaseexp\', '+tot_rows+','+tot_cols+',\'phaseexppopup_'+row+'_'+col+'\', \'phaseexppopup_drag_'+row+'_'+col+'\', \'phaseexppopup_exit_'+row+'_'+col+'\', \'mouse\', -10, -10);" />&nbsp;';
		document.getElementById("phase_explain_"+row+"_"+col).value=document.getElementById("bk_phase_explain_"+row+"_"+col).value;
		//document.getElementById("phaseexppopup_"+row+"_"+col).style.display = 'block';
	  }
	  else
	  {
	 	 document.getElementById("phaseexp_pos_"+row+"_"+col).innerHTML = '';
		 document.getElementById("phase_explain_"+row+"_"+col).value = '';
		 document.getElementById("phaseexppopup_"+row+"_"+col).style.display = 'none';
	  }
	}
}
function bicon_change(option, bomb_id)
{
	var bomb = document.getElementById('bomb_id');

	if(option.value == 'small')
	{
		bomb_id.src='images/new_sbomb.png';
		bomb_id.title='Edit Small Bomb Details';
		bomb_id.style.height='20px';
		bomb_id.style.width='20px';
	}
	else if(option.value == 'large')
	{
		bomb_id.src='images/new_lbomb.png';
		bomb_id.title='Edit Large Bomb Details';
		bomb_id.style.height='20px';
		bomb_id.style.width='20px';
	}
	else
	{
		bomb_id.src='images/new_square.png';
		bomb_id.title='Edit Bomb Details';
		bomb_id.style.height='20px';
		bomb_id.style.width='20px';
	}	

}

function getCookie_value(name) 
{
	var nameEQ = name + "=";
	var ca = document.cookie.split( ';');
	for( var i=0;i < ca.length;i++) 
	{
	var c = ca[i];
	while ( c.charAt( 0)==' ') c = c.substring( 1,c.length);
	if ( c.indexOf( nameEQ) == 0) return c.substring( nameEQ.length,c.length);
	}
	return null;
}

function tree_grid_cookie(category_name)	///Categories listed in cookies will only be collapsed
{
	var present_flg=0; var New_cookie='';
	var Cookie_value=getCookie_value('tree_grid_cookie');
	if(Cookie_value != null && Cookie_value != "")
	{
		var Cookie_value_Arr = Cookie_value.split('****');
			
		for(var i=0; i<Cookie_value_Arr.length; i++)
		{
			if(Cookie_value_Arr[i] != '' && Cookie_value_Arr[i] != null)
			{
				if(Cookie_value_Arr[i] == escape(category_name))	///Check if category already present in our cookie, if present escape it.
					present_flg=1;
				else
				{
					if(New_cookie=='')
					New_cookie = Cookie_value_Arr[i];
					else
					New_cookie = New_cookie+'****'+Cookie_value_Arr[i];
				}
			}
			
		}
		if(!present_flg) New_cookie = New_cookie+'****'+escape(category_name);	//If cookie doesn't have category add it
		Cookie_value=New_cookie;
	}
	else
	{
		Cookie_value=escape(category_name);
	}
			
	var today = new Date();
 	var expire = new Date();//Cookie_value="";
 	expire.setTime(today.getTime() + 60*60*24*365*1000);
 	document.cookie ="tree_grid_cookie="+Cookie_value+ ";expires="+expire.toGMTString();
}

function validate(rows, cols)
{
	flag=0;
	for(pt1=1; pt1<=rows; pt1++)
	{
		for(pt2=1; pt2<=cols; pt2++)
		{
			var element = document.getElementById('phase4_val_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_phase4_val_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value != bk_element.value && bk_element.value==1)
			flag=1;
			
			var element = document.getElementById('bombselect_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_bombselect_'+pt1+'_'+pt2);
			var element_expl = document.getElementById('bomb_explain_'+pt1+'_'+pt2);
			var bk_element_expl = document.getElementById('bk_bomb_explain_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if((element.value.replace(/\s+/g, '') == 'none') && (bk_element.value.replace(/\s+/g, '') != 'none'))
			flag=1;
			
			var element = document.getElementById('filing_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_filing_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value.replace(/\s+/g, '') =='' && bk_element.value.replace(/\s+/g, '') != '')
			flag=1;
			
			var element = document.getElementById('phase_explain_'+pt1+'_'+pt2);
			var bk_element = document.getElementById('bk_phase_explain_'+pt1+'_'+pt2);
			if((element != null && element !='') && (bk_element != null && bk_element !=''))
			if(element.value.replace(/\s+/g, '') =='' && bk_element.value.replace(/\s+/g, '') != '')
			flag=1;
		}
	}
	if(flag)
	{
		return confirm("You are going to Delete Some items from Report. Are you sure?");
	}
	else
	{
		return chkbox(0,'delrepe');
	}
}

</script>
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="css/chromestyle2.css" />
<script type="text/javascript" src="scripts/popup-window.js"></script>
<script type="text/javascript" src="scripts/chrome.js"></script>
<?php
echo('<script type="text/javascript" src="delsure.js"></script>');

postRL();
postEd();

echo(reportListCommon('rpt_master_heatmap'));

if(!isset($_POST['delrep']) && !is_array($_POST['delrep'])) ///Below Function Should be skipped after delete Otherwise we will get report not found error after delete
echo(editor());

echo('</body></html>');
}
//return html for report editor
function editor()
{
	global $db;
	
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
	
	if($shared && $rptu !== NULL)
	$owner_type="shared";
	else if($rptu !== NULL && $rptu == $db->user->id)
	$owner_type="mine";
	else if($rptu === NULL)
	$owner_type="global";
	
	$query = 'SELECT `num`,`type`,`type_id` FROM `rpt_masterhm_headers` WHERE report=' . $id . ' ORDER BY num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report headers');
	$rows = array();
	$columns = array();
	$areaIds = array();
	$productIds = array();
	while($header = mysql_fetch_array($res))
	{
		if($header['type'] == 'area')
		{
			if($header['type_id'] != NULL)
			{
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name FROM `areas` WHERE id = '" . $header['type_id'] . "' "));
				$columns[$header['num']] = $result['name'];
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
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name FROM `products` WHERE id = '" . $header['type_id'] . "' "));
				$rows[$header['num']] = $result['name'];
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
	$data_matrix=array();
	$active_total=0;
	$count_total=0;
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
				$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
				
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
				
				$data_matrix[$row][$col]['phase_explain']=trim($cell_data['phase_explain']);

				$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
				
				$data_matrix[$row][$col]['phase4_override']=$cell_data['phase4_override'];
				
				if($cell_data['bomb_auto'] == 'small')
				{
					$data_matrix[$row][$col]['bomb_auto']['value']=$cell_data['bomb_auto'];
					$data_matrix[$row][$col]['bomb_auto']['src']='sbomb.png';
					$data_matrix[$row][$col]['bomb_auto']['alt']='Small Bomb';
					$data_matrix[$row][$col]['bomb_auto']['style']='width:9px; height:11px;';
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
					$data_matrix[$row][$col]['bomb_auto']['style']='width:18px; height:11px;';
					$data_matrix[$row][$col]['bomb_auto']['title']='';
				}
				
				
				if($cell_data['bomb'] == 'small')
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_sbomb.png';
					$data_matrix[$row][$col]['bomb']['alt']='Small Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Small Bomb Details';
				}
				elseif($cell_data['bomb'] == 'large')
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
					$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Large Bomb Details';
				}
				else
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_square.png';
					$data_matrix[$row][$col]['bomb']['alt']='None';
					$data_matrix[$row][$col]['bomb']['style']='width:20px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Bomb';
				}
				
				$data_matrix[$row][$col]['filing']=trim($cell_data['filing']);
				
				
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
					$data_matrix[$row][$col]['color_code']='F9900';
				}
				else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
				{
					$data_matrix[$row][$col]['color']='background-color:#FF0000;';	
					$data_matrix[$row][$col]['color_code']='FF0000';
				}
				
				$data_matrix[$row][$col]['last_update']=$cell_data['last_update'];
				$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
				$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
				$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
				$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
				$data_matrix[$row][$col]['phase4_override_lastchanged']=$cell_data['phase4_override_lastchanged'];
				
				$data_matrix[$row][$col]['active_prev']=$cell_data['count_active_prev'];
				$data_matrix[$row][$col]['total_prev']=$cell_data['count_total_prev'];
				$data_matrix[$row][$col]['indlead_prev']=$cell_data['count_active_indlead_prev'];
				
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
			}
		}
	}
	
	if($_GET['view_type']=='total')
	{
		$title="All Trials (Active+Inactive)";
		$view_tp='total';
	}
	else if($_GET['view_type']=='indlead')
	{
		$title="Active Industry Lead Sponsor Trials";
		$view_tp='indlead';
	}
	else
	{
		$title="Active Trials";
		$view_tp='active';
	}


	$out = '<br/>&nbsp;&nbsp;<b>View Type: </b> <select id="view_type" name="view_type" onchange="window.location.href=\'master_heatmap.php?id='.$_GET['id'].'&view_type=\'+this.value+\'\'">'
			. '<option value="active" '.(($_GET['view_type']!='total' && $_GET['view_type']!='indlead')? "selected=\"selected\"":"").'>Active Trials</option>'
			. '<option value="total" '.(($_GET['view_type']=='total')? "selected=\"selected\"":"").'>All Trials</option>'
			. '<option value="indlead"'.(($_GET['view_type']=='indlead')? "selected=\"selected\"":"").'>Active Industry Trials</option></select><br/>'
			. '<form action="master_heatmap.php" method="post">'
			. '<fieldset><legend>Download Option</legend>'
			. '<input type="hidden" name="id" value="' . $id . '" />';
	if($toal_fld)
	{
		$out .='<input type="hidden" name="total_col" value="1" />';
	}
	$out .='<b>Which Format: </b><select id="dwformat" name="dwformat"><option value="htmldown" selected="selected">HTML</option>'
		. '<option value="exceldown">Excel</option>'
		. '<option value="pdfdown">PDF</option>'
		. '</select><br/><br/>';
	$out .='<b>Counts Display: </b><select id="dwcount" name="dwcount">'
		. '<option value="active" '.(($view_tp=='active')? "selected=\"selected\"":"").'>Active Trials</option>'
		. '<option value="total" '.(($view_tp=='total')? "selected=\"selected\"":"").'>All Trials</option>'
		. '<option value="indlead" '.(($view_tp=='indlead')? "selected=\"selected\"":"").'>Active Industry Trials</option></select><br/><br/><input type="submit" name="download" value="Download" title="Download" />'
		. '</fieldset></form>';	
		
	/*$out .='<input type="image" name="htmldown[]" src="images/html.png" title="HTML Download" />&nbsp;&nbsp;'
		. '<input type="image" name="pdfdown[]" src="images/pdf.png" title="PDF Download" />&nbsp;&nbsp;'
		. '<input type="image" name="exceldown[]" src="images/excel_new.png" title="Excel Download" /></div></form>';		*/
	$disabled=0;
	if(($owner_type == 'shared' && $rptu != $db->user->id) || ($owner_type == 'global' && $db->user->userlevel == 'user'))
	$disabled=1;
	$out .= '<br clear="both" />'
		. '<form action="master_heatmap.php" onsubmit="return validate('.count($rows).','.count($columns).');" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
		. '<input type="hidden" name="id" value="' . $id . '" />'
		. '<label>Name: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="reportname" value="' . htmlspecialchars($name) . '"/></label>'
		. '<label>Category: <input type="text" '.(($disabled) ? ' readonly="readonly" ':'').' '
		. 'name="reportcategory" value="' . htmlspecialchars($category)
		. '"/></label>';		
	if($db->user->userlevel != 'user')
	{
		$out .= ' Ownership: '
			. '<label><input type="radio" name="own" value="shared" '
			. ($owner_type == 'shared' ? 'checked="checked"' : '')
			. (($owner_type == 'shared' && $rptu != $db->user->id) ? ' readonly="readonly" ':'')
			. '/>Shared</label> '
			. '<label><input type="radio" name="own" value="global" '
			. ($owner_type == 'global' ? 'checked="checked"' : '')
			. (($owner_type == 'shared' && $rptu != $db->user->id) ? ' readonly="readonly" ':'')
			. '/>Global</label> '
			. '<label><input type="radio" name="own" value="mine" '
			. ($owner_type == 'mine' ? 'checked="checked"' : '')
			. (($owner_type == 'shared' && $rptu != $db->user->id) ? ' readonly="readonly" ':'')
			. '/>Mine</label>';
	}else{
		$out .= ' Ownership: '
			. ($owner_type == 'shared' ? 'Shared' : '')
			. ($owner_type == 'global' ? 'Global' : '')
			. ($owner_type == 'mine' ? 'Mine' : '');
	}
	
	//total column checkbox
	$out .= ' <label><input '.(($disabled) ? ' readonly="readonly" ':'').' type="checkbox" name="total"  value="1" ' . (($toal_fld) ? 'checked="checked"' : '') . ' />Total</label>';
	
	$out .= '<br clear="all"/>';
	if($db->user->userlevel != 'user' || $rptu !== NULL)
	{
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
		$out .= '<input type="submit" name="reportsave" value="Save edits" /> | '
				.'<input type="submit" name="addproduct" value="More rows" /> | '
				. '<input type="submit" name="addarea" value="More columns" /> | ';
	}
	$out .= '<input type="submit" name="reportcopy" value="Copy into new" /> | '
			. '<a href="masterhm_report_inputcheck.php?id=' . $id . '">Input check</a>'
			. '<br /><table class="reportcell"><tr><th></th>';
			
	foreach($columns as $col => $val)
	{
		$out .= '<th><input type="text" id="areas' . $col . '" name="areas[' . $col . ']" value="' . $val . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'areas'.$col.'\')" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
				
		$out .= 'Column : '.$col.' ';
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
		{
			// LEFT ARROW?
			if($col > 1) $out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
			// RIGHT ARROW?
			if($col < $max_column['num']) $out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right" />';
				
			$out .='&nbsp;&nbsp;';	
			$out .= '<label class="lbldeln"><input type="checkbox" name="deletecol[' . $col . ']" title="Delete Column '.$col.'"/></label>';
		}
		$out .='<br/>';
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
		{
			if($view_tp=='active')
			{
				$count_val='<b>'.$col_active_total[$col].'</b>';
			}
			else if($view_tp=='total')
			{
				$count_val=$col_count_total[$col];
			}
			else if($view_tp =='indlead')
			{
				$count_val=$col_indlead_total[$col];
			}
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col] . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		
			$out .= '<br><a href="calculate_hm_cells.php?area=' . $areaIds[$col] . '" target="_blank"  title="Recalculate values">'. 'Recalculate' .'</a>';
			
			


		}
		$out .='<br/>';
		$out .= '</th>';
	}
	//if total checkbox is selected
	if($toal_fld)
	{
		$out .= '<th width="150px">';
		if(!empty($productIds) && !empty($areaIds))
		{
			if($view_tp=='active')
			{
				$count_val='<b>'.$active_total.'</b>';
			}
			else if($view_tp=='total')
			{
				$count_val=$count_total;
			}
			else if($view_tp == 'indlead')
			{
				$count_val='<b>'.$indlead_total.'</b>';
			}
				
			$productIds = array_filter($productIds);
			$areaIds = array_filter($areaIds);
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds) . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		}
		$out .= '</th>';
	}
	$out .= '</tr>';
	foreach($rows as $row => $rval)
	{
		$out .= '<tr><th><input type="text" id="products' . $row . '"  name="products[' . $row . ']" value="' . $rval . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'products'.$row.'\')" '.(($disabled) ? ' readonly="readonly" ':'').' /><br />';
		
		$out .= 'Row : '.$row.' ';
		
		if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
		{
			// UP ARROW?
			if($row > 1) $out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';
			// DOWN ARROW?
			if($row < $max_row['num']) $out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';
			
			$out .='&nbsp;&nbsp;';	
			$out .= '<label class="lbldeln"><input type="checkbox" name="deleterow[' . $row . ']" title="Delete Column '.$row.'" /></label>';
		}
		$out .='<br/>';
		if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
		{
			if($view_tp=='active')
			{
				$count_val='<b>'.$row_active_total[$row].'</b>';
			}
			else if($view_tp=='total')
			{
				$count_val=$row_count_total[$row];
			}
			else if($view_tp == 'indlead')
			{
				$count_val='<b>'.$row_indlead_total[$row].'</b>';
			}
				
			$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds) . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a>';
		}
		$out .='<br/>';
		$out .= '</th>';
		
		foreach($columns as $col => $cval)
		{
			$out .= '<td valign="middle" align="center" style="text-align:center;'.$data_matrix[$row][$col]['color'].'"><br>';
			
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
			{
				if($data_matrix[$row][$col]['bomb_auto']['src'] != '' && $data_matrix[$row][$col]['bomb_auto']['src'] != NULL)
				$out .= '<img title="'.$data_matrix[$row][$col]['bomb_auto']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb_auto']['src'].'" style="'.$data_matrix[$row][$col]['bomb_auto']['style'].' cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb_auto']['alt'].'"  />';
				
				if($view_tp=='active')
				{
					$count_val='<b>'.$data_matrix[$row][$col]['active'].'</b>';
					$prev_count_val='<b>'.$data_matrix[$row][$col]['active'].'</b>';
				}
				else if($view_tp=='total')
				{
					$count_val=$data_matrix[$row][$col]['total'];
					$prev_count_val=$data_matrix[$row][$col]['total'];
				}
				else if($view_tp == 'indlead')
				{
					$count_val='<b>'.$data_matrix[$row][$col]['indlead'].'</b>';
					$prev_count_val='<b>'.$data_matrix[$row][$col]['indlead_prev'].'</b>';
				}
					
				$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col] . '" target="_blank" class="ottlink" title="'.$title.'">'.$count_val.'</a><br/><br/>';
				
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['phase4_override']) ? '1':'0') . ' " name="phase4_val['.$row.']['.$col.']" id="phase4_val_'.$row.'_'.$col.'" />';
				$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['phase4_override']) ? '1':'0') . ' " name="bk_phase4_val['.$row.']['.$col.']" id="bk_phase4_val_'.$row.'_'.$col.'" />';
				
				$out .= '<input type="hidden" name="cell_prod['.$row.']['.$col.']" value="'. $productIds[$row] .'" />'
						.'<input type="hidden" name="cell_area['.$row.']['.$col.']" value="' . $areaIds[$col] . '" />';
				
				
				$out .= '<div style="float:left;"><font id="phase4_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['phase4_override'])
				$out .= '<img align="middle" id="phase4img_'.$row.'_'.$col.'" title="Red cell override" src="images/phase4.png" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Phase4_Override"/>&nbsp;';
				$out .= '</font>';
				
				$out .= '<font id="bomb_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png')
				$out .= '<img align="middle" id="bombimg_'.$row.'_'.$col.'" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:bottom; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'"'
			.'onclick="popup_show(\'bomb\', '.count($rows).','.count($columns).',\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				$out .= '</font>';
			
				$out .= '<font id="filing_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
				$out .= '<img align="middle" id="filingimg_'.$row.'_'.$col.'" title="Edit Filing" src="images/'. (($data_matrix[$row][$col]['filing'] == NULL && $data_matrix[$row][$col]['filing'] == '') ? 'new_edit.png' : 'new_file.png' ) .'" style="width:20px; height:20px; vertical-align:bottom; cursor:pointer;" alt="Edit Filing" onclick="popup_show(\'filing\', '.count($rows).','.count($columns).',\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				$out .= '</font>';
				
				$out .= '<font id="phaseexp_pos_'.$row.'_'.$col.'">';
				if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
				$out .= '<img align="middle" id="Phase_Explain_'.$row.'_'.$col.'" src="images/phaseexp.png" title="Edit Phase Explain" style="width: 20px; height: 20px; vertical-align: bottom; cursor: pointer;" alt="Phase Explain" onclick="popup_show(\'phaseexp\', '.count($rows).','.count($columns).',\'phaseexppopup_'.$row.'_'.$col.'\', \'phaseexppopup_drag_'.$row.'_'.$col.'\', \'phaseexppopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />&nbsp;';
				$out .= '</font></div>';
				
				
				$out .= '<div align="right" style="height:25px; vertical-align: bottom;" class="chromestyle" id="chromemenu_'.$row.'_'.$col.'"><ul><li><a rel="dropmenu_'.$row.'_'.$col.'"><b>+<b></a></li></ul></div>';
				
				
				
				$out .= '<div id="dropmenu_'.$row.'_'.$col.'" class="dropmenudiv" style="width: 180px;">'
					 .'<a style="vertical-align:bottom;"><input  id="bombopt_'.$row.'_'.$col.'"  name="bombopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png') ? 'checked="checked"':'') . ' onchange="update_icons(\'bomb\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" '.(($disabled) ? ' readonly="readonly" ':'').' />Small/Large Bomb&nbsp;<img align="right" src="images/lbomb.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="filingopt_'.$row.'_'.$col.'"  name="filingopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '') ? 'checked="checked"':'') . '  onchange="update_icons(\'filing\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" '.(($disabled) ? ' readonly="readonly" ':'').' />Filing&nbsp;<img align="right" src="images/file.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="phase4opt_'.$row.'_'.$col.'"  name="phase4opt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['phase4_override']) ? 'checked="checked"':'') . '  onchange="update_icons(\'phase4\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')"  '.(($disabled) ? ' readonly="readonly" ':'').' />Phase4_Override&nbsp;<img align="right" src="images/phase4.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'<a style="vertical-align:bottom;"><input  id="phaseexpopt_'.$row.'_'.$col.'"  name="phaseexpopt_'.$row.'_'.$col.'" type="checkbox" value="1" ' . (($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '') ? 'checked="checked"':'') . '  onchange="update_icons(\'phaseexp\','.$row.','.$col.', '.count($rows).','.count($columns).',\''.$data_matrix[$row][$col]['color_code'].'\')" '.(($disabled) ? ' readonly="readonly" ':'').' />Phase Explain&nbsp;<img align="right" src="images/phaseexp.png"  style="vertical-align:bottom; width:11px; height:11px;"/></a>'
					 .'</div>';
					 
				$out .= '<script type="text/javascript">cssdropdown.startchrome("chromemenu_'.$row.'_'.$col.'");</script>';
				
				
				$out .= '<div class="popup_form" id="bombpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
						.'<div class="menu_form_header" id="bombpopup_drag_'.$row.'_'.$col.'" style="width:300px;">'
						.'<img class="menu_form_exit" align="right" id="bombpopup_exit_'.$row.'_'.$col.'" src="images/fancy_close.png" style="width:30px; height:30px; " '		
						.'alt="" />&nbsp;&nbsp;&nbsp;'
						.'</div>'
						.'<div class="menu_form_body">'
						.'<table style="background-color:#fff;">'
						.'<tr><td style="background-color:#fff;">'
						.'<font style="color:#206040; font-weight: 900;"><br/>&nbsp;Bomb Value: </font> <font style="color:#000000; font-weight: 900;">';
						
						$out .='<select '.(($disabled) ? ' readonly="readonly" ':'').' id="bombselect_'.$row.'_'.$col.'" onchange="bicon_change(bombselect_'.$row.'_'.$col.', bombimg_'.$row.'_'.$col.')" class="field" name="bomb['.$row.']['.$col.']">';
						$out .= '<option value="none" '.(($data_matrix[$row][$col]['bomb']['value'] == 'none' || $data_matrix[$row][$col]['bomb']['value'] == '' || $data_matrix[$row][$col]['bomb']['value'] == NULL) ? ' selected="selected"' : '') .'>None</option>';
						$out .= '<option value="small" '.(($data_matrix[$row][$col]['bomb']['value'] == 'small') ? ' selected="selected"' : '') .'>Small Bomb</option>';
						$out .= '<option value="large" '.(($data_matrix[$row][$col]['bomb']['value'] == 'large') ? ' selected="selected"' : '') .'>Large Bomb</option>';
						$out .= '</select><br/><br/></font><font style="color:#206040; font-weight: 900;">&nbsp;Bomb Details: <br/></font><textarea align="left" '.(($disabled) ? ' readonly="readonly" ':'').' name="bomb_explain['.$row.']['.$col.']" id="bomb_explain_'.$row.'_'.$col.'"  rows="5" cols="20" style="overflow:scroll; width:280px; height:80px; padding-left:10px; ">'. $data_matrix[$row][$col]['bomb_explain'] .'</textarea>';
						
						$out .= '<input type="hidden" value=" ' . (($data_matrix[$row][$col]['bomb']['value'] == 'none' || $data_matrix[$row][$col]['bomb']['value'] == '' || $data_matrix[$row][$col]['bomb']['value'] == NULL) ? 'none':$data_matrix[$row][$col]['bomb']['value']) . ' " name="bk_bomb['.$row.']['.$col.']" id="bk_bombselect_'.$row.'_'.$col.'" />'
						.'<textarea name="bk_bomb_explain['.$row.']['.$col.']" id="bk_bomb_explain_'.$row.'_'.$col.'" style="overflow:scroll; display:none;" rows="5" cols="20">'. $data_matrix[$row][$col]['bomb_explain'] .'</textarea>'
						.'</td></tr>'
						.'</table>'
						.'</div>'
						.'</div>';	//Pop-Up Form for Bomb Editing Ends Here
			
						
						$out .= '<div class="popup_form" id="filingpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
						.'<div class="menu_form_header" id="filingpopup_drag_'.$row.'_'.$col.'" style="width:300px;">'
						.'<img class="menu_form_exit" align="right" id="filingpopup_exit_'.$row.'_'.$col.'" src="images/fancy_close.png" style="width:30px; height:30px;" '		
						.'alt="" />&nbsp;&nbsp;&nbsp;'
						.'</div>'
						.'<div class="menu_form_body">'
						.'<table style="background-color:#fff;">';
						
						$out .= '<tr><td style="background-color:#fff;">'
						.'<font style="color:#206040; font-weight: 900;">&nbsp;Filing Details: <br/></font><textarea align="left" '.(($disabled) ? ' readonly="readonly" ':'').' id="filing_'.$row.'_'.$col.'" name="filing['.$row.']['.$col.']"  rows="5" cols="20" style="overflow:scroll; width:280px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['filing'] .'</textarea>'
						.'<textarea id="bk_filing_'.$row.'_'.$col.'" name="bk_filing['.$row.']['.$col.']" style="overflow:scroll; display:none;" rows="5" cols="20">'. $data_matrix[$row][$col]['filing'] .'</textarea>'
						.'</td></tr>'
						.'<tr><th style="background-color:#fff;">&nbsp;</th></tr>'
						.'</table>'
						.'</div>'
						.'</div>'; ///Pop-up Form for Filing Ends Here	
						
						
						$out .= '<div class="popup_form" id="phaseexppopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
							.'<div class="menu_form_header" id="phaseexppopup_drag_'.$row.'_'.$col.'" style="width:300px;">'
							.'<img class="menu_form_exit" align="right" id="phaseexppopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '		
							.'alt="" />&nbsp;&nbsp;&nbsp;'
							.'</div>'
							.'<div class="menu_form_body">'
							.'<table style="background-color:#fff;">';
							
						$out .= '<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<font style="color:#206040; font-weight: 900;">&nbsp;Phase Explain: <br/></font><textarea align="left" '.(($disabled) ? ' readonly="readonly" ':'').' id="phase_explain_'.$row.'_'.$col.'" name="phase_explain['.$row.']['.$col.']"  rows="5" cols="20" style="overflow:scroll; width:280px; height:60px; padding-left:10px; ">'. $data_matrix[$row][$col]['phase_explain'] .'</textarea>'
							.'<textarea id="bk_phase_explain_'.$row.'_'.$col.'" name="bk_phase_explain['.$row.']['.$col.']" style="overflow:scroll; display:none;" rows="5" cols="20">'. $data_matrix[$row][$col]['phase_explain'] .'</textarea>'
							//.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;" id="filing">'. $data_matrix[$row][$col]['phase_explain'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>'; ///Pop-up Form for Phase Explain Ends Here		


			}else{
				$out .= '';
			}
			
			$out .= '</td>';
					
		}
		//if total checkbox is selected
		if($toal_fld)
		{
			$out .= '<th width="150px">&nbsp;</th>';
		}
		
		$out .= '</tr>';
	}
	$out .= '</table>'
			. '<fieldset><legend>Footnotes</legend><textarea '.(($disabled) ? ' readonly="readonly" ':'').' name="footnotes" cols="45" rows="5">' 
			. $footnotes . '</textarea></fieldset>'
			. '<fieldset><legend>Description</legend><textarea '.(($disabled) ? ' readonly="readonly" ':'').' name="description" cols="45" rows="5">' . $description
			. '</textarea></fieldset>';
	if($owner_type == 'mine' || ($owner_type == 'global' && $db->user->userlevel != 'user') || ($owner_type == 'shared' && $rptu == $db->user->id))
	{
		$out .= '<br/><br/><br/><br/><div align="left"><fieldset style="margin-top:50px; padding:8px;"><legend>Advanced</legend>'
				. '<label class="lbldeln"><input class="delrepe" type="checkbox" name="delrep['.$id.']" title="Delete" /></label>' 
				. '&nbsp;&nbsp;&nbsp;&nbsp;Delete This Master Heatmap Report</fieldset></div>';
	};
	$out .= '</form>';

	return $out;
}

function Download_reports()
{
	ob_start();
	global $db;
	global $now;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_POST['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT name,user,footnotes,description,category,shared FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	$shared = $res['shared'];
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
				$result =  mysql_fetch_assoc(mysql_query("SELECT id, name, description FROM `products` WHERE id = '" . $header['type_id'] . "' "));
				$rows[$header['num']] = $result['name'];
				$rowsDisplayName[$header['num']] = '';
				$rowsDescription[$header['num']] = $result['description'];
			}
			else
			{
				$rows[$header['num']] = $header['type_id'];
			}
			$productIds[$header['num']] = $header['type_id'];
		}
	}
/* 	echo '<pre>';
	print_r($rows);
	print_r($columnsDisplayName);
	print_r($columnsDescription);
	print_r($rowsDisplayName);
	print_r($rowsDescription);
	die; */
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
				$indlead_total=$cell_data['count_active_indlead']+$indlead_total;
				
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
				
				$data_matrix[$row][$col]['bomb_explain']=trim($cell_data['bomb_explain']);
				
				$data_matrix[$row][$col]['phase_explain']=trim($cell_data['phase_explain']);
				
				
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
					$data_matrix[$row][$col]['exec_bomb']['src']='sbomb.png'; //Excel bomb image
					$data_matrix[$row][$col]['bomb']['alt']='Small Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:15px; height:15px;';
					$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
				}
				elseif($cell_data['bomb'] == 'large')
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_lbomb.png';
					$data_matrix[$row][$col]['exec_bomb']['src']='lbomb.png';
					$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:15px; height:15px;';
					$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
				}
				else
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='new_square.png';
					$data_matrix[$row][$col]['exec_bomb']['src']='new_square.png';
					$data_matrix[$row][$col]['bomb']['alt']='None';
					$data_matrix[$row][$col]['bomb']['style']='width:15px; height:15px;';
					$data_matrix[$row][$col]['bomb']['title']='Bomb Details';
				}
				
				$data_matrix[$row][$col]['filing']=trim($cell_data['filing']);
				
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
				$data_matrix[$row][$col]['count_lastchanged']=$cell_data['count_lastchanged'];
				$data_matrix[$row][$col]['bomb_lastchanged']=$cell_data['bomb_lastchanged'];
				$data_matrix[$row][$col]['filing_lastchanged']=$cell_data['filing_lastchanged'];
				$data_matrix[$row][$col]['phase_explain_lastchanged']=$cell_data['phase_explain_lastchanged'];
				
				$data_matrix[$row][$col]['active_prev']=$cell_data['count_active_prev'];
				$data_matrix[$row][$col]['total_prev']=$cell_data['count_total_prev'];
				$data_matrix[$row][$col]['indlead_prev']=$cell_data['count_active_indlead_prev'];
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
			}
		}
	}
	
	$count_fillbomb=0;	
	if($_POST['dwcount']=='active')
	{
		$tooltip=$title="Active Trials";
		$pdftitle="<b>Active Trials</b>";
	}
	elseif($_POST['dwcount']=='total')
	{
		$pdftitle=$tooltip=$title="All Trials (Active + Inactive)";
	}
	else
	{
		$tooltip=$title="Active Industry Lead Sponsor Trials";
		$pdftitle="<b>Active Industry Lead Sponsor Trials</b>";
	}
		
	if($_POST['dwformat']=='pdfdown' || $_POST['dwformat']=='htmldown')
	{
	
		$pdfContent .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
						. '<html xmlns="http://www.w3.org/1999/xhtml">'
						. '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
						. '<title>Larvol Master Heatmap Export</title>'
						. '<style type="text/css">'
						. 'body { font-family:Verdana; font-color:black; font-size: 16px;}'
						. 'a, a:hover{color:#000000;text-decoration:none; height:100%;}';
						
						if($_POST['dwformat']=='pdfdown') //0.5px border value does not work for Chrome and IE and 1px border looks dark in PDF
						{
							$pdfContent .= 'td, th {vertical-align:top; padding-top:10px; border-right: 0.5px solid blue; border-left:0.5px solid blue; border-top: 0.5px solid blue; border-bottom:0.5px solid blue;}';
							$pdfContent .= 'tr {border-right: 0.5px solid blue; border-left: 0.5px solid blue; border-top: 0.5px solid blue; border-bottom: 0.5px solid blue;}';
						}
						else
						{
							$pdfContent .= 'td, th {vertical-align:top; padding-top:10px; border-right: 1px solid blue; border-left:1px solid blue; border-top: 1px solid blue; border-bottom:1px solid blue;}';
							$pdfContent .= 'tr {border-right: 1px solid blue; border-left: 1px solid blue; border-top: 1px solid blue; border-bottom: 1px solid blue;}';
						}
							
		$pdfContent .= '@page {margin-top: 1em; margin-bottom: 2em;}'
						. '.nobr {white-space: nowrap}'
						. '</style>'
						. '<style type="text/css">'.file_get_contents('css/popup_form.css').'</style>'
						. '</head>'
						. '<body bgcolor="#FFFFFF">'
						. '<div align="center"><img src="'.  urlPath() .'images/Larvol-Trial-Logo-notag.png" alt="Main" width="200" height="25" id="header" /></div><br/>';

		$pdfContent .= '<div align="center">'
						. '<table align="center" style="border-collapse:collapse; padding:10px; background-color:#DDF;">'
						. '<tr style="page-break-inside:avoid;" nobr="true"><td width="300px" align="left"><b>Name: </b>'. htmlspecialchars($name) .'</td>'
						. '<td width="300px" align="left"><b>Category: </b>'. htmlspecialchars($category) .'</td></tr>'
						. '<tr style="page-break-inside:avoid;" nobr="true"><td width="300px" align="left" colspan="2"><b>Display Mode: </b>'. $pdftitle .'</td></tr>'
						. '</table>'
						. '</div><br /><br/>';
						
		$pdfContent .= '<div align="center">'
						. '<table style="border-collapse:collapse; background-color:#DDF; padding-top:5px;">'
						. '<thead><tr style="page-break-inside:avoid;" nobr="true"><th>&nbsp;</th>';
				
		foreach($columns as $col => $val)
		{
			$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
			$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
			$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
				
			$pdfContent .= '<th width="150px" '.$caltTitle.'><div align="center">'. $val .'<br />';
			
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=$col_active_total[$col];
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=$col_count_total[$col];
				}
				else
				{
					$count_val=$col_indlead_total[$col];
				}
				$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" target="_blank" title="'. $title .'">'.$count_val.'</a>';
			}
			$pdfContent .='</div></th>';
		}
		//if total checkbox is selected
		if(isset($_POST['total_col']) && $_POST['total_col'] == "1")
		{
			$pdfContent .= '<th width="150px"><div align="center">';
			if(!empty($productIds) && !empty($areaIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=$active_total;
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=$count_total;
				}
				else
				{
					$count_val=$indlead_total;
				}
				$productIds = array_filter($productIds);
				$areaIds = array_filter($areaIds);
				$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '" target="_blank" title="'. $title .'">'.$count_val.'</a>';
			}
			$pdfContent .= '</div></th>';
		}
		$pdfContent .= '</tr></thead>';
		
		if($_POST['dwformat']=='pdfdown')//Extra row for alignment in PDF
		{
			$pdfContent .= '<tr style="page-break-inside:avoid;" nobr="true"><th height="0px" style="height:0px; border-top:none; border:none;">&nbsp;</th>';
			foreach($columns as $col => $val)
				$pdfContent .= '<th height="0px" style="height:0px; border-top:none; border:none;">&nbsp;</th>';
			if(isset($_POST['total_col']) && $_POST['total_col'] == "1")
				$pdfContent .= '<th height="0px" style="height:0px; border-top:none; border:none;">&nbsp;</th>';
			$pdfContent .= '</tr>';		
		}
		
		foreach($rows as $row => $rval)
		{
			//$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval;
			$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
			$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
			$pdfContent .= '<tr  style="page-break-inside:avoid;" nobr="true"><th width="150px" '.$raltTitle.'><div align="center">' . $rval . '<br />';
					
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=$row_active_total[$row];
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=$row_count_total[$row];
				}
				else
				{
					$count_val=$row_indlead_total[$row];
				}
				$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '" target="_blank" class="ottlink" title="'. $title .'">'.$count_val.'</a>';
			}
			$pdfContent .= '</div></th>';
			
			foreach($columns as $col => $cval)
			{
				$pdfContent .= '<td width="150px" align="center" style="text-align:center; '.$data_matrix[$row][$col]['color'].'" align="center">&nbsp;&nbsp;&nbsp;&nbsp;';
				
				if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
				{
					
					if($_POST['dwcount']=='active')
					{
						$count_val=$data_matrix[$row][$col]['active'];
					}
					elseif($_POST['dwcount']=='total')
					{
						$count_val=$data_matrix[$row][$col]['total'];
					}
					else
					{
						$count_val=$data_matrix[$row][$col]['indlead'];
					}
				
					$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '" target="_blank" title="'. $title .'">'. $count_val.'</a><br/>';
					
					if($_POST['dwformat']=='htmldown' && $data_matrix[$row][$col]['bomb']['src'] != 'square.png') //When bomb has square dont include it in pdf as size is big and no use
					{	$pdfContent .= '<img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'"'
			.'onclick="popup_show(\'bomb\', '.count($rows).','.count($columns).',\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';				}
			
			
					if($_POST['dwformat']=='pdfdown') //As in PDF alignment not works space added to align it properly	
					{
						if($data_matrix[$row][$col]['bomb']['src'] != 'new_square.png')
						{
							$pdfContent .= '&nbsp;<img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'. $data_matrix[$row][$col]['bomb']['style'] .' vertical-align:bottom; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'" />';
							if($data_matrix[$row][$col]['bomb_explain'] != NULL && $data_matrix[$row][$col]['bomb_explain'] != '' && $data_matrix[$row][$col]['bomb']['src'] != 'square.png')
							{
								$count_fillbomb++;
								$pdfContent .= '('.$count_fillbomb.')';
							}
						}
						
						if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
						{
							$pdfContent .= '&nbsp;<img align="right" title="Filing Details" src="'. urlPath() .'images/new_file.png" style="width:15px; height:15px; vertical-align:bottom; cursor:pointer;" alt="Filing" />';
							
								$count_fillbomb++;
								$pdfContent .= '('.$count_fillbomb.')';
						}
						
						if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
						{
							$pdfContent .= '&nbsp;<img align="right" title="Phase Explain" src="'. urlPath() .'images/phaseexp.png" style="width:15px; height:15px; vertical-align:bottom; cursor:pointer;" alt="Phase Explain" />';
							
								$count_fillbomb++;
								$pdfContent .= '('.$count_fillbomb.')';
						}
					}	

					if($_POST['dwformat']=='htmldown') //As there is no need for following code to be executed for PDF
					{
						if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
						$pdfContent .= '<br/><br/><img align="right" title="Filing Details" src="'. urlPath() .'images/file.png" style="width:14px; height:16px; vertical-align:top; cursor:pointer; background-color:#CCCCCC;" alt="Filing" onclick="popup_show(\'filing\', '.count($rows).','.count($columns).',\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';

					
						$pdfContent .= '<div class="popup_form" id="bombpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
								.'<div class="menu_form_header" id="bombpopup_drag_'.$row.'_'.$col.'">'
								.'<img class="menu_form_exit" align="right" id="bombpopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '			
								.'alt="" />&nbsp;&nbsp;&nbsp;Bomb Details<br />'
								.'</div>'
								.'<div class="menu_form_body">'
								.'<table style="background-color:#fff; border:none;">'
								.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Bomb: '. $data_matrix[$row][$col]['bomb']['alt'] .'<br/><br/></th></tr>';
								
							
						
						$pdfContent .= '<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Bomb Explanation:</th></tr>'
							.'<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;">'. $data_matrix[$row][$col]['bomb_explain'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>';	//Pop-Up Form for Bomb Editing Ends Here
			
						
						$pdfContent .= '<div class="popup_form" id="filingpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
							.'<div class="menu_form_header" id="filingpopup_drag_'.$row.'_'.$col.'">'
							.'<img class="menu_form_exit" align="right" id="filingpopup_exit_'.$row.'_'.$col.'" src="'. urlPath() .'images/fancy_close.png" style="width:30px; height:30px;" '		
							.'alt="" />&nbsp;&nbsp;&nbsp;Filing Details'
							.'</div>'
							.'<div class="menu_form_body">'
							.'<table style="background-color:#fff;">';
							
						$pdfContent .= '<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">Filing:</th></tr>'
							.'<tr style="background-color:#fff; border:none;"><td style="background-color:#fff; border:none;">'
							.'<div align="left" width="200px" style="overflow:scroll; width:200px; height:150px; padding-left:10px;" id="filing">'. $data_matrix[$row][$col]['filing'] .'</div>'
							.'</td></tr>'
							.'<tr style="background-color:#fff; border:none;"><th style="background-color:#fff; border:none;">&nbsp;</th></tr>'
							.'</table>'
							.'</div>'
							.'</div>';
					}	


				}else{
					$pdfContent .= '';
					}
				$pdfContent .= '</td>';
			}
			//if total checkbox is selected
			if(isset($_POST['total_col']) && $_POST['total_col'] == "1")
			{
				$pdfContent .= '<th>&nbsp;</th>';
			}
		
			$pdfContent .= '</tr>';
		}
		$pdfContent .= '</table></div><br /><br/>'
						. '<div align="center"><table align="center" style="border-collapse:collapse; vertical-align:middle; padding:10px; background-color:#DDF;">'
						. '<tr style="page-break-inside:avoid;" nobr="true"><td width="300px" align="left"><b>Footnotes: </b><br/><div style="padding-left:10px;">'. $footnotes .'</div></td>'
						. '<td width="300px" align="left"><b>Description: </b><br/><div style="padding-left:10px;">'. $description .'</div></td></tr>'
						. '</table></div>';
						
		if($_POST['dwformat']=='pdfdown' && $count_fillbomb > 0)
		{
			$pdfContent .= '<br style="page-break-before:always;" /><br/>'
							. '<div align="center"><table align="center" width="100%" style="border-collapse:collapse; vertical-align:middle; background-color:#DDF;">'
							. '<thead><tr style="border-bottom:none;"><th height="11px" style="width:30px; border-bottom:none;" align="left">No.</th><th style="width:70px; border-bottom:none;" align="left">Image</th><th style="width:675px; border-bottom:none;" align="left">Details</th></tr></thead>'
							. '<tr height="1px" style="border:none; border-top:none;"><td style="width:30px; height:0px; border-top:none; border:none;"></td><td style="width:70px; height:0px; border-top:none; border:none;"></td><td style="width:675px; height:0px; border-top:none; border:none;"></td></tr>';
								
			$count_fillbomb_again=0;
			foreach($rows as $row => $rval)
			foreach($columns as $col => $cval)
			{
				if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
				{
					if($data_matrix[$row][$col]['bomb_explain'] != NULL && $data_matrix[$row][$col]['bomb_explain'] != '' && $data_matrix[$row][$col]['bomb']['src'] != 'square.png')
					{
						$count_fillbomb_again++;
						$pdfContent .=  '<tr style="page-break-inside:avoid;" nobr="true"><td align="left"><div style="padding-left:10px;">'. $count_fillbomb_again .'</div></td>'
							. '<td align="left" width="30px"><div style="padding-left:10px;"><img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'. $data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'" /></div></td>'
							. '<td align="left"><div style="padding-left:10px;">'. $data_matrix[$row][$col]['bomb_explain'] .'</div></td></tr>';
					}
						
					if($data_matrix[$row][$col]['filing'] != NULL && $data_matrix[$row][$col]['filing'] != '')
					{
						$count_fillbomb_again++;
						$pdfContent .=  '<tr style="page-break-inside:avoid;" nobr="true"><td align="left" width="30px"><div style="padding-left:10px;">'. $count_fillbomb_again .'</div></td>'
							. '<td align="left" width="30px"><div style="padding-left:10px;"><img align="right" title="Filing Details" src="'. urlPath() .'images/new_file.png" style="width:15px; height:15px; vertical-align:top; cursor:pointer;" alt="Filing" /></div></td>'
							. '<td align="left"><div style="padding-left:10px;">'. $data_matrix[$row][$col]['filing'] .'</div></td></tr>';
					}
					
					if($data_matrix[$row][$col]['phase_explain'] != NULL && $data_matrix[$row][$col]['phase_explain'] != '')
					{
						$count_fillbomb_again++;
						$pdfContent .=  '<tr style="page-break-inside:avoid;" nobr="true" ><td align="left" width="30px"><div style="padding-left:10px;">'. $count_fillbomb_again .'</div></td><td align="left" width="30px"><div style="padding-left:10px;"><img align="right" title="Phase Explain" src="images/phaseexp.png" style="width:15px; height:15px; vertical-align:top; cursor:pointer;" alt="Filing" /></div></td><td align="left"><div style="padding-left:10px;">'. $data_matrix[$row][$col]['phase_explain'] .'</div></td></tr>';
					}
				}				
			}
			
			$pdfContent .= '</table></div>';			
		}
						
		$pdfContent .=  '</body>'
						. '</html>';
						
		//echo $pdfContent;
		if($_POST['dwformat']=='pdfdown')
		{
			require_once('tcpdf/config/lang/eng.php');
			require_once('tcpdf/tcpdf.php');  
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			// set document information
			//$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('Larvol Trials');
			$pdf->SetTitle('Larvol Trials');
			$pdf->SetSubject('Larvol Trials');
			$pdf->SetKeywords('Larvol Trials Master Heatmap, Larvol Trials Master Heatmap PDF Export');
			$pdf->SetFont('verdana', '', 8);
			$pdf->setFontSubsetting(false);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
			
			$pdf->setPageOrientation('l');
			
			// remove default header/footer
			$pdf->setPrintHeader(false);
			//set some language-dependent strings
			$pdf->setLanguageArray($l);
			//set auto page breaks
			$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
			$pdf->AddPage();
			$pdfContent = preg_replace('/width="[0-9]{0,}(px){1}"/', '', $pdfContent);
			//$pdfContent = preg_replace('/width="[0-9]{0,}(px){1}"/', 'width="20px"', $pdfContent);
			ini_set('pcre.backtrack_limit',strlen($pdfContent));
			// output the HTML content
			$pdf->writeHTML($pdfContent, true, false, true, false, '');
			ob_end_clean();
			//Close and output PDF document
			$pdf->Output('Larvol_'. substr($name,0,20) .'_PDF_Report_'. date("Y-m-d_H.i.s") .'.pdf', 'D');
		
		}//PDF Function Ends
		//var_dump(urlPath());
		//echo htmlspecialchars($pdfContent);
		if($_POST['dwformat']=='htmldown')
		{	
			$filename = 'Larvol_'. substr($name,0,20) .'_HTML_Report_'. date("Y-m-d_H.i.s").'.html';
			//!$handle = fopen($filename, 'w');
			//fwrite($handle, $pdfContent);
			//fclose($handle);
			$pdfContent.='<script>'.file_get_contents('scripts/popup-window.js').'</script>';
			//header("Cache-Control: public");
			//header("Content-Description: File Transfer");
			//header("Content-Length: ". filesize("$filename").";");
			header("Content-Disposition: attachment; filename=$filename");
			//header("Content-Type: application/octet-stream; "); 
			//header("Content-Transfer-Encoding: binary");
			echo $pdfContent;
			//readfile($filename);
		}//HTML Function Ends
		
	}//Pdf & HTML Function Ends
	
	if($_POST['dwformat']=='exceldown')
	{
	  	
		// Create excel file object
		$objPHPExcel = new PHPExcel();
	
		// Set properties
		$objPHPExcel->getProperties()->setCreator(SITE_NAME);
		$objPHPExcel->getProperties()->setLastModifiedBy(SITE_NAME);
		$objPHPExcel->getProperties()->setTitle(substr($name,0,20));
		$objPHPExcel->getProperties()->setSubject(substr($name,0,20));
		$objPHPExcel->getProperties()->setDescription(substr($name,0,20));
	
		// Build sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle(substr($name,0,20));
		//$objPHPExcel->getActiveSheet()->getStyle('A1:AA2000')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(18);
		
		
		foreach($columns as $col => $val)
		{
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
			{
				if($_POST['dwcount']=='active')
				{
					$count_val=' ('. $col_active_total[$col] .')';
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=' ('. $col_count_total[$col] .')';
				}
				else
				{
					$count_val=' ('. $col_indlead_total[$col].')';
				}
				
				$cell= num2char($col).'1';
				//TODO
				$val = (isset($columnsDisplayName[$col]) && $columnsDisplayName[$col] != '')?$columnsDisplayName[$col]:$val;
				$cdesc = (isset($columnsDescription[$col]) && $columnsDescription[$col] != '')?$columnsDescription[$col]:null;
				$caltTitle = (isset($cdesc) && $cdesc != '')?' alt="'.$cdesc.'" title="'.$cdesc.'" ':null;
								
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $val.$count_val);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]));
				
				if($cdesc)
				{
					$objPHPExcel->getActiveSheet()->getComment($cell)->setAuthor('Description:');
					$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun('Description:');
					$objCommentRichText->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun("\r\n");
					$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun($cdesc);					
				}
				
 			    $objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
				$objPHPExcel->getActiveSheet()->getColumnDimension(num2char($col))->setWidth(18);
				
				$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
			}
		}
		
		if(isset($_POST['total_col']) && $_POST['total_col'] == "1")
		{
			$objPHPExcel->getActiveSheet()->getColumnDimension(num2char($col+1))->setWidth(18);
			$objPHPExcel->getActiveSheet()->getStyle(num2char($col+1))->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
		}
		
		foreach($rows as $row => $rval)
		{
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
			{
				
				if($_POST['dwcount']=='active')
				{
					$count_val=' ('. $row_active_total[$row] .')';
				}
				elseif($_POST['dwcount']=='total')
				{
					$count_val=' ('. $row_count_total[$row] .')';
				}
				else
				{
					$count_val=' ('.$row_indlead_total[$row].')';
				}
				
				$cell='A'.($row+1);
				//TODO
				//$rval = (isset($rowsDisplayName[$row]) && $rowsDisplayName[$row] != '')?$rowsDisplayName[$row]:$rval;
				$rdesc = (isset($rowsDescription[$row]) && $rowsDescription[$row] != '')?$rowsDescription[$row]:null;
				$raltTitle = (isset($rdesc) && $rdesc != '')?' alt="'.$rdesc.'" title="'.$rdesc.'" ':null;
				
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $rval.$count_val);
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds))); 
 			    $objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
 			    
 			    if($rdesc)
 			    {
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->setAuthor('Description:');
 			    	$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun('Description:');
 			    	$objCommentRichText->getFont()->setBold(true);
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun("\r\n");
 			    	$objPHPExcel->getActiveSheet()->getComment($cell)->getText()->createTextRun($rdesc);
 			    } 			    
				
				/*$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));*/
			}
			
			foreach($columns as $col => $cval)
			{
				$cell = num2char($col) . ($row + 1);
				if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
				{
					if($_POST['dwcount']=='active')
					{
						$count_val=$data_matrix[$row][$col]['active'];
					}
					elseif($_POST['dwcount']=='total')
					{
						$count_val=$data_matrix[$row][$col]['total'];
					}
					else
					{
						$count_val=' ('.$data_matrix[$row][$col]['indlead'].')';
					}
					
					
					$objPHPExcel->getActiveSheet()->setCellValue($cell, $count_val);
					$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col])); 
 			    	$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip."\nFiling:- ". $data_matrix[$row][$col]['filing'] ."\nBomb Details:- ". $data_matrix[$row][$col]['bomb_explain']."\nPhase Explain:- ". $data_matrix[$row][$col]['phase_explain']);
					
					if($data_matrix[$row][$col]['exec_bomb']['src'] != '' && $data_matrix[$row][$col]['exec_bomb']['src'] != NULL && $data_matrix[$row][$col]['exec_bomb']['src'] !='new_square.png')
					{
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX(100);
						$objDrawing->setOffsetY(3);
						$objDrawing->setPath('images/'.$data_matrix[$row][$col]['exec_bomb']['src']);
						$objDrawing->setDescription($data_matrix[$row][$col]['bomb']['title']);
						$objDrawing->setCoordinates($cell);
					}
					
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->getStartColor()->setRGB($data_matrix[$row][$col]['color_code']);
					$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
				}
			}
		}
		
		if(isset($_POST['total_col']) && $_POST['total_col'] == "1")
		{
			if($_POST['dwcount']=='active')
			{
				$count_val=' ('. $active_total .')';
			}
			elseif($_POST['dwcount']=='total')
			{
				$count_val=' ('. $count_total .')';
			}
			else
			{
				$count_val='Total ('.$indlead_total.')';
			}
					
			$cell = num2char(count($columns)+1).'1';
			$objPHPExcel->getActiveSheet()->setCellValue($cell, $count_val);
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds)));
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip($tooltip);
		}
		
		$row = count($rows) + 1;
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, '');
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Report name:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, substr($name,0,250));
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Display Mode:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $tooltip);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Footnotes:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $footnotes);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Description:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $description);
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Runtime:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row++, date("Y-m-d H:i:s", $now));
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . $row, 'Legend:');
		$col = 'A';
		//get search results
		$phases = array('N/A', 'Phase 0', 'Phase 0/Phase 1', 'Phase 1', 'Phase 1a', 'Phase 1b', 'Phase 1a/1b', 'Phase 1c', 'Phase 1/Phase 2', 'Phase 1b/2', 
						'Phase 1b/2a', 'Phase 2','Phase 2a', 'Phase 2a/2b', 'Phase 2a/b', 'Phase 2b', 'Phase 2/Phase 3', 'Phase 2b/3','Phase 3', 'Phase 3a', 
						'Phase 3b', 'Phase 3/Phase 4', 'Phase 3b/4', 'Phase 4');
		$phasenums = array(); foreach($phases as $k => $p)  $phasenums[$k] = str_ireplace(array('phase',' '),'',$p);
		$phase_legend_nums = array('N/A', '0', '0/1', '1', '1/2', '2', '2/3', '3', '3/4', '4');
		//$p_colors = array('DDDDDD', 'BBDDDD', 'AADDEE', '99DDFF', 'DDFF99', 'FFFF00', 'FFCC00', 'FF9900', 'FF7711', 'FF4422');
		$p_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', '99CC00', '99CC00', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 'FFFF00', 
		'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF9900', 'FF9900', 'FF9900', 'FF0000', 'FF0000', 'FF0000');
		$phase_legend_colors = array('BFBFBF', '00CCFF', '99CC00', '99CC00', 'FFFF00', 'FFFF00', 'FF9900', 'FF9900', 'FF0000', 'FF0000');
	
		foreach($p_colors as $key => $color)
		{
			$cell = ++$col . $row;
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getFill()->getStartColor()->setRGB($color);
			$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
			$objPHPExcel->getActiveSheet()->getCell($cell)->setValueExplicit($phasenums[$key], PHPExcel_Cell_DataType::TYPE_STRING);
		}
			
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			
		ob_end_clean(); 
		
		header("Pragma: public");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download");
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Larvol_' . substr($name,0,20) . '_Excel_Report_' . date('Y-m-d_H.i.s') . '.xlsx"');
			
		header("Content-Transfer-Encoding: binary ");
		$objWriter->save('php://output');
		@flush();
	} //Excel Function Ends
}

//process POST for editor
function postEd()
{
	global $db;
	global $now;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string($_POST['id']);
	if(!is_numeric($id)) return;
	
	$_GET['id'] = $id;	//This is so the editor will load the report we are about to (maybe?) save
	
	// block any user from modifying other peoples private reports and block non-admins from modifying global reports
	$query = 'SELECT user,shared FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting user for master heatmap report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) return;	///Replaced "Continue" by "Return" cause continue was giving "Cannot break/continue 1 level" error when report deleted and continue should only be used to escape through loop not function
	if(count($res)==0){ die('Not found.'); }
	$rptu = $res['user'];
	$shared = $res['shared'];
	if($rptu !== NULL && $rptu != $db->user->id && !$shared) return;

	// "Copy into new" is the exception for non-admins sending POSTdata about global reports
	if(isset($_POST['reportcopy']))
	{
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name,footnotes,description,category FROM rpt_masterhm WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query getting old data');
		$res = mysql_fetch_array($res);
		if($res === false) return; //not found

		$oldname = mysql_real_escape_string($res['name']);
		$footnotes = mysql_real_escape_string($res['footnotes']);
		$description = mysql_real_escape_string($res['description']);
		$category = mysql_real_escape_string($res['category']);
		$query = 'INSERT INTO rpt_masterhm SET name="Copy of ' . (strlen($oldname) ? $oldname : ('report '.$id)) . '",user='
				. $db->user->id . ',footnotes="' . $footnotes . '",description="' . $description . '"' . ',category="'.$category.'"';
				
		mysql_query($query) or die('Bad SQL Query saving name');
		$newid = mysql_insert_id();
		$tables = array('rpt_masterhm_headers');
		
		foreach($tables as $tab)
		{
			$query = 'SELECT * FROM ' . $tab . ' WHERE report=' . $id;
			$res = mysql_query($query) or die('Bad SQL query getting report info');
			while($orow = mysql_fetch_assoc($res))
			{
				$orow['report'] = $newid;
				foreach($orow as $key => $value)
				{
					if($value === NULL)
					{
						$value = 'NULL';
					}else{
						$value = mysql_real_escape_string($value);
						if(!is_numeric($value)) $value = '"' . $value . '"';
					}
					if($key != 'id') $orow['`'.$key.'`'] = $value;
					unset($orow[$key]);
				}
				$query = 'INSERT INTO ' . $tab . '(' . implode(',', array_keys($orow)) . ') VALUES(' . implode(',', $orow) . ')';
				mysql_query($query) or die('Bad SQL query copying data ' . $query . mysql_error());
			}
		}
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
		$_GET['id'] = $newid;
	}
	
	if(($rptu === NULL && $db->user->userlevel == 'user') || ($rptu !== NULL && $rptu != $db->user->id)) return;

	$maxrow = 0;
	$maxcolumn = 0;
	$types = array('product','area');
	foreach($types as $t)
	{
		$maxvar = 'max' . $t;
		if(isset($_POST['add'.$t]) || isset($_POST['del'.$t]))
		{
			$query = 'SELECT MAX(num) AS "prevnum" FROM rpt_masterhm_headers WHERE report=' . $id
						. ' AND type="' . $t . '" GROUP BY report LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting max ' . $t . ' number');
			$res = mysql_fetch_array($res);
			if($res !== false) $$maxvar = $res['prevnum'];
		}
		if(isset($_POST['add'.$t]))
		{
			$query = 'INSERT INTO rpt_masterhm_headers SET report=' . $id . ',type="' . $t . '",num=' . ($$maxvar + 1);
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
		}
		if(isset($_POST['del'.$t]))
		{
			$query = 'DELETE FROM rpt_masterhm_headers WHERE report=' . $id . ' AND num=' . $$maxvar . ' AND type="' . $t . '" LIMIT 1';
			mysql_query($query) or die('Bad SQL Query removing ' . $t);
		}
	}
	if(isset($_POST['reportsave']))
	{
		$footnotes = mysql_real_escape_string($_POST['footnotes']);
		$description = mysql_real_escape_string($_POST['description']);
		
		if(isset($_POST['own']) && $_POST['own'] == 'global')
		{
			$owner='NULL'; $shared=0;
		} else if(isset($_POST['own']) && $_POST['own'] == 'shared')
		{
			$owner=$db->user->id; $shared=1;
		} else
		{
			$owner=$db->user->id; $shared=0;
		}
		
		if(isset($_POST['total']) && $_POST['total']==1)
		$total_col=1;
		else
		$total_col=0;
		
		$category = mysql_real_escape_string($_POST['reportcategory']);
		
		$query = 'UPDATE rpt_masterhm SET name="' . mysql_real_escape_string($_POST['reportname']) . '",user=' . $owner
					. ',footnotes="' . $footnotes . '",description="' . $description . '"'
					. ',category="' . $category . '",shared="' . $shared . '",total="' . $total_col . '"' . ' WHERE id=' . $id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query saving name');
		
		foreach($types as $t)
		{	
			foreach($_POST[$t."s"] as $num => $header)
			{
				if($header != "") 
				{
					$query = "select id from " . $t . "s where name='" . mysql_real_escape_string($header) . "' ";
					$row = mysql_fetch_assoc(mysql_query($query)) or die('Bad SQL Query getting ' . $t . ' names ');
					
					$query = 'UPDATE rpt_masterhm_headers SET type_id="' . mysql_real_escape_string($row['id']) 
					. '" WHERE report=' . $id . ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
					mysql_query($query) or die('Bad SQL Query saving ' . $t . ' names ');
				}
			}
		}//exit;
		
		if(isset($_POST['cell_prod']) && !empty($_POST['cell_prod']))
		{
			foreach($_POST['cell_prod'] as $row => $data)
			foreach($data as $col => $value)
			{
				$prod=$_POST['cell_prod'][$row][$col];
				$area=$_POST['cell_area'][$row][$col];
				$filing=mysql_real_escape_string($_POST['filing'][$row][$col]);
				$bomb=$_POST['bomb'][$row][$col];
				$bomb_explain=mysql_real_escape_string($_POST['bomb_explain'][$row][$col]);
				$phase_explain=mysql_real_escape_string($_POST['phase_explain'][$row][$col]);
				$phase4_val=mysql_real_escape_string($_POST['phase4_val'][$row][$col]);
				
				$up_time=date('Y-m-d H:i:s', $now);
				
				$originDT_query = "SELECT `bomb`, `bomb_explain`, `filing`, `phase_explain`, `phase4_override` FROM `rpt_masterhm_cells` WHERE `product` = $prod AND `area` = $area";
				$originDT=mysql_query($originDT_query) or die ('Bad SQL Query getting Original Bomb and Filing Information Before Updating.<br/>'.$query);
				$originDT = mysql_fetch_array($originDT);
				
				$query = "UPDATE `rpt_masterhm_cells` set ";
				
				if($bomb != $originDT['bomb'] || $_POST['bomb_explain'][$row][$col] != $originDT['bomb_explain'])
				$query .="`bomb` = '$bomb', `bomb_explain` = '$bomb_explain', `bomb_lastchanged`= '$up_time', ";
				
				if($filing != $originDT['filing'])
				$query .="`filing` = '$filing', `filing_lastchanged`= '$up_time', ";
				
				if($phase_explain != $originDT['phase_explain'])
				$query .="`phase_explain` = '$phase_explain', `phase_explain_lastchanged`= '$up_time', ";
				
				if($phase4_val != $originDT['phase4_override'])
				$query .="`phase4_override` = '$phase4_val', `phase4_override_lastchanged`= '$up_time', ";
				
				$query .= "`last_update`= '$up_time' WHERE `product` = $prod AND `area` = $area";
				mysql_query($query) or die ('Bad SQL Query updating Bomb and Filing Information.<br/>'.$query);
			}
		}
	}

	if(isset($_POST['move_row_down']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_row_down'] = array_keys($_POST['move_row_down']);
		$_POST['move_row_down'] = $_POST['move_row_down'][0];
		
		$current_row=$_POST['move_row_down'];
		$next_row=$_POST['move_row_down']+1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_row AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$current_row' WHERE num = '$next_row' AND type = 'product' AND id <> '$current_row_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if(isset($_POST['move_row_up']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_row_up'] = array_keys($_POST['move_row_up']);
		$_POST['move_row_up'] = $_POST['move_row_up'][0];
		
		$current_row=$_POST['move_row_up'];
		$next_row=$_POST['move_row_up']-1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_row AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'product' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$current_row' WHERE num = '$next_row' AND type = 'product' AND id <> '$current_row_id' AND report = '$id'";
		$query = mysql_query($sql);
	}

	if(isset($_POST['move_col_left']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_col_left'] = array_keys($_POST['move_col_left']);
		$_POST['move_col_left'] = $_POST['move_col_left'][0];
		
		$current_column=$_POST['move_col_left'];
		$next_column=$_POST['move_col_left']-1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_column AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$sql 
		= "UPDATE `rpt_masterhm_headers` SET num = '$current_column' WHERE num = '$next_column' AND type = 'area' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if(isset($_POST['move_col_right']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_col_right'] = array_keys($_POST['move_col_right']);
		$_POST['move_col_right'] = $_POST['move_col_right'][0];
		
		$current_column=$_POST['move_col_right'];
		$next_column=$_POST['move_col_right']+1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM `rpt_masterhm_headers` WHERE num = $current_column AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE `rpt_masterhm_headers` SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'area' AND report = '$id'";
		$query = mysql_query($sql);
		$sql 
		= "UPDATE `rpt_masterhm_headers` SET num = '$current_column' WHERE num = '$next_column' AND type = 'area' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
	}
	
	if((isset($_POST['deleterow']) && is_array($_POST['deleterow'])) || (isset($_POST['deletecol']) && is_array($_POST['deletecol'])))
	{	
		mysql_query('BEGIN');
		if(isset($_POST['deleterow']) && is_array($_POST['deleterow']))
		{
			foreach($_POST['deleterow'] as $delRow=>$stat)
			{
				//delete the row
				$query = "DELETE FROM `rpt_masterhm_headers` WHERE report= $id AND `num` = $delRow AND `type` = 'product' ";
				mysql_query($query) or die ('Bad SQL Query removing column.');
				
			}
			//after all delete rows reorder rows
			$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = 'product' ORDER BY `num` ASC ";
			$result = mysql_query($query);
			$cnt = mysql_num_rows($result);
			if($cnt>0)
			{
				$i=1;
				while($row = mysql_fetch_assoc($result))
				{
					$query = "UPDATE `rpt_masterhm_headers` set `num` = $i WHERE `report` = $id and `type` = 'product' AND `num` = ".$row['num'];
					mysql_query($query) or die ('Bad SQL Query updating rows with new values after delete row/s operation.<br/>'.$query);
					
					$i++;
				}
			}
			
		}
		if(isset($_POST['deletecol']) && is_array($_POST['deletecol']))
		{
			foreach($_POST['deletecol'] as $delCol=>$stat)
			{
				//delete the column
				$query = "DELETE FROM `rpt_masterhm_headers` WHERE `report`= $id AND `num` = $delCol AND `type` = 'area' ";
				mysql_query($query) or die ('Bad SQL Query removing column.');
			}	
			//after all delete columns reorder columns
			$query = "SELECT num FROM `rpt_masterhm_headers` WHERE `report` = $id AND `type` = 'area' ORDER BY `num` ASC";
			$result = mysql_query($query);
			$cnt = 0;
			$cnt = mysql_num_rows($result);
			if($cnt>0)
			{
				$i=1;
				while($row = mysql_fetch_assoc($result))
				{
					$query = "UPDATE `rpt_masterhm_headers` set `num` = $i WHERE `report` = $id and `type` = 'area' AND `num` = ".$row['num'];
					mysql_query($query) or die ('Bad SQL Query updating columns with new values after delete row/s operation.<br/>'.$query);

					$i++;
				}
			}				
		}
		
		mysql_query('COMMIT');
	}
	
}

//processes POST for report list
function postRL()
{
	global $db;
	if(isset($_POST['makenew']))
	{ 
		mysql_query('INSERT INTO `rpt_masterhm` SET name="", user=' . $db->user->id) or die('Bad SQL query creating master heatmap report');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];

		$types = array('product','area');
		foreach($types as $t)
		{
			$query = 'INSERT INTO `rpt_masterhm_headers` SET report=' . $id . ',type="' . $t . '",num=1';
			mysql_query($query) or die('Bad SQL Query adding ' . $t . ' in master heatmap report');
			$query = 'INSERT INTO `rpt_masterhm_headers` SET report=' . $id . ',type="' . $t . '",num=2';
			mysql_query($query) or die('Bad SQL Query adding ' . $t . ' in master heatmap report');
		}
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			$query = 'SELECT user FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting userid for master heatmap report');
			$res = mysql_fetch_assoc($res);
			if($res === false) continue;
			$ru = $res['user'];
			if($ru == $db->user->id || ($db->user->userlevel != 'user' && $ru === NULL))
				mysql_query('DELETE FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting master heatmap report');
		}
	}
}
?>