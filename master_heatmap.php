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
if($_POST['htmldown'] || $_POST['pdfdown'] || $_POST['exceldown'])
Download_reports();
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
<link href="css/popup_form.css" rel="stylesheet" type="text/css" media="all" />
<script type="text/javascript" src="scripts/popup-window.js"></script>
<?php
echo('<script type="text/javascript" src="delsure.js"></script>');
postRL();
postEd();
echo(reportListCommon('rpt_master_heatmap'));
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
	$query = 'SELECT name,user,footnotes,description,category FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;	//prevent anyone from viewing others' private reports
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
					$data_matrix[$row][$col]['bomb']['title']='Edit Bomb';
				}
				elseif($cell_data['bomb'] == 'large')
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='lbomb.png';
					$data_matrix[$row][$col]['bomb']['alt']='Large Bomb';
					$data_matrix[$row][$col]['bomb']['style']='width:18px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Bomb';
				}
				else
				{
					$data_matrix[$row][$col]['bomb']['value']=$cell_data['bomb'];
					$data_matrix[$row][$col]['bomb']['src']='trans.gif';
					$data_matrix[$row][$col]['bomb']['alt']='None';
					$data_matrix[$row][$col]['bomb']['style']='width:18px; height:20px;';
					$data_matrix[$row][$col]['bomb']['title']='Edit Bomb';
				}
				
				$data_matrix[$row][$col]['filing']=$cell_data['filing'];
				
				if($cell_data['highest_phase'] == 'N/A' || $cell_data['highest_phase'] == '' || $cell_data['highest_phase'] === NULL)
				{
					$data_matrix[$row][$col]['color']='background-color:#BFBFBF;';
				}
				else if($cell_data['highest_phase'] == '0')
				{
					$data_matrix[$row][$col]['color']='background-color:#00CCFF;';
				}
				else if($cell_data['highest_phase'] == '1' || $cell_data['highest_phase'] == '0/1' || $cell_data['highest_phase'] == '1a' 
				|| $cell_data['highest_phase'] == '1b' || $cell_data['highest_phase'] == '1a/1b' || $cell_data['highest_phase'] == '1c')
				{
					$data_matrix[$row][$col]['color']='background-color:#99CC00;';
				}
				else if($cell_data['highest_phase'] == '2' || $cell_data['highest_phase'] == '1/2' || $cell_data['highest_phase'] == '1b/2' 
				|| $cell_data['highest_phase'] == '1b/2a' || $cell_data['highest_phase'] == '2a' || $cell_data['highest_phase'] == '2a/2b' 
				|| $cell_data['highest_phase'] == '2a/b' || $cell_data['highest_phase'] == '2b')
				{
					$data_matrix[$row][$col]['color']='background-color:#FFFF00;';
				}
				else if($cell_data['highest_phase'] == '3' || $cell_data['highest_phase'] == '2/3' || $cell_data['highest_phase'] == '2b/3' 
				|| $cell_data['highest_phase'] == '3a' || $cell_data['highest_phase'] == '3b')
				{
					$data_matrix[$row][$col]['color']='background-color:#FF9900;';
				}
				else if($cell_data['highest_phase'] == '4' || $cell_data['highest_phase'] == '3/4' || $cell_data['highest_phase'] == '3b/4')
				{
					$data_matrix[$row][$col]['color']='background-color:#FF0000;';	
				}
			}
			else
			{
				$col_active_total[$col]=$col_total[$col];
				$row_active_total[$row]=$row_total[$row];
				$col_count_total[$col]=$col_total[$col];
				$row_count_total[$row]=$row_total[$row];
				$data_matrix[$row][$col]['active']=0;
				$data_matrix[$row][$col]['total']=0;
				$data_matrix[$row][$col]['bomb_auto']['src']='';
				$data_matrix[$row][$col]['bomb']['src']='';
				$data_matrix[$row][$col]['bomb_explain']='';
				$data_matrix[$row][$col]['filing']='';
				$data_matrix[$row][$col]['color']='background-color:#DDF;';
			}
		}
	}


	$out = '<script type="text/javascript" src="progress/progress.js"></script>'
		. '<br><div id="runbuttons" style="width:300px;"><form action="master_heatmap.php" method="post">'
		. '<input type="hidden" name="id" value="' . $id . '" />';
	if(isset($_POST['total']) && $_POST['total'] == "on")
	{
		$out .='<input type="hidden" name="total_col" value="on" />';
	}
	$out .='<input type="image" name="htmldown[]" src="images/html.png" title="HTML Download" />&nbsp;&nbsp;'
		. '<input type="image" name="pdfdown[]" src="images/pdf.png" title="PDF Download" />&nbsp;&nbsp;'
		. '<input type="image" name="exceldown[]" src="images/excel_new.png" title="Excel Download" /></div></form>';		
	
	$out .= '<br clear="both" />'
		. '<form action="master_heatmap.php" onsubmit="return chkbox(0,\'delrepe\');" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
		. '<input type="hidden" name="id" value="' . $id . '" />'
		. '<label>Name: <input type="text" name="reportname" value="' . htmlspecialchars($name) . '"/></label>'
		. '<label>Category: <input type="text" name="reportcategory" value="' . htmlspecialchars($category)
		. '"/></label>';		
	if($db->user->userlevel != 'user')
	{
		$out .= ' Ownership: '
			. '<label><input type="radio" name="own" value="global" '
			. ($rptu === NULL ? 'checked="checked"' : '')
			. '/>Global</label> '
			. '<label><input type="radio" name="own" value="mine" '
			. ($rptu !== NULL ? 'checked="checked"' : '')
			. '/>Mine</label>';
	}else{
		$out .= ' Ownership: ' . ($rptu === NULL ? 'Global' : 'Mine');
	}
	
	//total column checkbox
	$out .= ' <label><input type="checkbox" name="total" ' . (isset($_POST['total']) && $_POST['total'] == "on" ? 'checked="checked"' : '') . ' />Total</label>';
	
	$out .= '<br clear="all"/>';
	if($db->user->userlevel != 'user' || $rptu !== NULL)
	{
		$out .= '<input type="submit" name="reportsave" value="Save edits" /> | '
				. '<input type="submit" name="addproduct" value="More rows" /> | '
				. '<input type="submit" name="addarea" value="More columns" /> | ';
	}
	$out .= '<input type="submit" name="reportcopy" value="Copy into new" />'
			. '<br /><table class="reportcell"><tr><th></th>';
			
	foreach($columns as $col => $val)
	{
		$out .= '<th><input type="text" id="areas' . $col . '" name="areas[' . $col . ']" value="' . $val . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'areas'.$col.'\')" /><br />';
				
		$out .= 'Column : '.$col.' ';
		
		// LEFT ARROW?
		if($col > 1) $out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
		// RIGHT ARROW?
		if($col < $max_column['num']) $out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right"/>';
			
		$out .='&nbsp;&nbsp;';	
		$out .= '<label class="lbldeln"><input class="delrepe" type="checkbox" name="deletecol[' . $col . ']" title="Delete Column '.$col.'"/></label>';
		$out .='<br/>';
		if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
		{
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col] . '" target="_blank" class="ottlink" title="Total Records"><b title="Active Records">'.$col_active_total[$col].', </b>'.$col_count_total[$col].'</a>';
		}
		$out .='<br/>';
		$out .= '</th>';
	}
	//if total checkbox is selected
	if(isset($_POST['total']) && $_POST['total'] == "on")
	{
		$out .= '<th width="150px">';
		if(!empty($productIds) && !empty($areaIds))
		{
			$productIds = array_filter($productIds);
			$areaIds = array_filter($areaIds);
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds) . '" target="_blank" class="ottlink" title="Total Records"><b title="Active Records">'.$active_total.', </b>'.$count_total.'</a>';
		}
		$out .= '</th>';
	}
	$out .= '</tr>';
	foreach($rows as $row => $rval)
	{
		$out .= '<tr><th><input type="text" id="products' . $row . '"  name="products[' . $row . ']" value="' . $rval . '" autocomplete="off" '
				. ' onkeyup="javascript:autoComplete(\'products'.$row.'\')" /><br />';
		
		$out .= 'Row : '.$row.' ';
		// UP ARROW?
		if($row > 1) $out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';
		// DOWN ARROW?
		if($row < $max_row['num']) $out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';
		
		$out .='&nbsp;&nbsp;';	
		$out .= '<label class="lbldeln"><input class="delrepe" type="checkbox" name="deleterow[' . $row . ']" title="Delete Column '.$row.'"/></label>';
		$out .='<br/>';
		if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
		{
			$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds) . '" target="_blank" class="ottlink" title="Total Records"><b title="Active Records">'.$row_active_total[$row].', </b>'.$row_count_total[$row].'</a>';
		}
		$out .='<br/>';
		$out .= '</th>';
		
		foreach($columns as $col => $cval)
		{
			$out .= '<td valign="middle"; style="text-align:center;'.$data_matrix[$row][$col]['color'].'"><br><br>';
			
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
			{
				if($data_matrix[$row][$col]['bomb_auto']['src'] != '' && $data_matrix[$row][$col]['bomb_auto']['src'] != NULL)
				$out .= '<img align="left" title="'.$data_matrix[$row][$col]['bomb_auto']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb_auto']['src'].'" style="'.$data_matrix[$row][$col]['bomb_auto']['style'].' padding-left:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb_auto']['alt'].'"  />';
				
				$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col] . '" target="_blank" class="ottlink" title="Total Records"><b title="Active Records">'.$data_matrix[$row][$col]['active'].', </b>'.$data_matrix[$row][$col]['total'].'</a>';
				
				$out .= '<img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'"'
			.'onclick="popup_show(\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" /><br><br>';
				$out .= '<img align="right" title="Edit Filing" src="images/'. (($data_matrix[$row][$col]['filing'] == NULL && $data_matrix[$row][$col]['filing'] == '') ? 'edit.png' : 'file.png' ) .'" style="width:14px; height:16px; vertical-align:top; cursor:pointer;'.(($data_matrix[$row][$col]['filing'] == NULL && $data_matrix[$row][$col]['filing'] == '') ? ' background-color:#CCCCCC;' : '' ).'" alt="Edit Filing" onclick="popup_show(\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';

				$out .= '<div class="popup_form" id="bombpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
						.'<div class="menu_form_header" id="bombpopup_drag_'.$row.'_'.$col.'">'
						.'<img class="menu_form_exit" align="right" id="bombpopup_exit_'.$row.'_'.$col.'" src="images/fancy_close.png" style="width:30px; height:30px;" '		
						.'alt="" />&nbsp;&nbsp;&nbsp;Edit Bomb Details<br />'
						.'</div>'
						.'<div class="menu_form_body">'
						.'<table style="background-color:#fff;">'
						.'<tr><th style="background-color:#fff;">Bomb:</th></tr>'
						.'<tr><td style="background-color:#fff;">'
						.'<select class="field" name="bomb['.$row.']['.$col.']">';
					
				$out .= '<option value="" '.(($data_matrix[$row][$col]['bomb']['value'] == '' || $data_matrix[$row][$col]['bomb']['value'] == NULL) ? ' selected="selected"' : '') .'></option>';
			    $out .= '<option value="none" '.(($data_matrix[$row][$col]['bomb']['value'] == 'none') ? ' selected="selected"' : '') .'>None</option>';
				$out .= '<option value="small" '.(($data_matrix[$row][$col]['bomb']['value'] == 'small') ? ' selected="selected"' : '') .'>Small Bomb</option>';
				$out .= '<option value="large" '.(($data_matrix[$row][$col]['bomb']['value'] == 'large') ? ' selected="selected"' : '') .'>Large Bomb</option>';
						
							
						$out .= '</select><br /><br /></td></tr>'
						.'<tr><th style="background-color:#fff;">Bomb Explanation:</th></tr>'
						.'<tr><td style="background-color:#fff;">'
						.'<textarea name="bomb_explain['.$row.']['.$col.']" style="overflow:scroll;" rows="5" cols="20">'. $data_matrix[$row][$col]['bomb_explain'] .'</textarea>'
						.'</td></tr>'
						.'<tr><th style="background-color:#fff;">&nbsp;</th></tr>'
						.'<tr>'
						.'<td align="center" style="background-color:#fff;">'
						.'<input type="hidden" name="cell_prod['.$row.']['.$col.']" value="'. $productIds[$row] .'" />'
						.'<input type="hidden" name="cell_area['.$row.']['.$col.']" value="' . $areaIds[$col] . '" />'
						.'<input name="bomb_popup['.$row.']['.$col.']" class="btn" type="submit" value="Update" />'	
						.'</td>'
						.'</tr>'
						.'</table>'
						.'</div>'
						.'</div>';	//Pop-Up Form for Bomb Editing Ends Here
			
						
						$out .= '<div class="popup_form" id="filingpopup_'.$row.'_'.$col.'" style="display: none;">'	//Pop-Up Form for Bomb Editing Starts Here
						.'<div class="menu_form_header" id="filingpopup_drag_'.$row.'_'.$col.'">'
						.'<img class="menu_form_exit" align="right" id="filingpopup_exit_'.$row.'_'.$col.'" src="images/fancy_close.png" style="width:30px; height:30px;" '		
						.'alt="" />&nbsp;&nbsp;&nbsp;Edit Filing'
						.'</div>'
						.'<div class="menu_form_body">'
						.'<table style="background-color:#fff;">';
						
						$out .= '<tr><th style="background-color:#fff;">Filing:</th></tr>'
						.'<tr><td style="background-color:#fff;">'
						.'<textarea name="filing['.$row.']['.$col.']" id="filing" style="overflow:scroll;" rows="5" cols="20">'. $data_matrix[$row][$col]['filing'] .'</textarea>'
						.'</td></tr>'
						.'<tr><th style="background-color:#fff;">&nbsp;</th></tr>'
						.'<tr>'
						.'<td align="center" style="background-color:#fff;">'
						.'<input name="filing_popup['.$row.']['.$col.']" class="btn" type="submit" value="Update" />'	
						.'</td>'
						.'</tr>'
						.'</table>'
						.'</div>'
						.'</div>';


			}else{
				$out .= '';
			}
			
			$out .= '</td>';
					
		}
		//if total checkbox is selected
		if(isset($_POST['total']) && $_POST['total'] == "on")
		{
			$out .= '<th width="150px">&nbsp;</th>';
		}
		
		$out .= '</tr>';
	}
	$out .= '</table>'
			. '<fieldset><legend>Footnotes</legend><textarea name="footnotes" cols="45" rows="5">' 
			. $footnotes . '</textarea></fieldset>'
			. '<fieldset><legend>Description</legend><textarea name="description" cols="45" rows="5">' . $description
			. '</textarea></fieldset>';
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
	$query = 'SELECT name,user,footnotes,description,category FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting master heatmap report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;	//prevent anyone from viewing others' private reports
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
					$data_matrix[$row][$col]['bomb']['src']='trans.gif';
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
				$col_active_total[$col]=$col_total[$col];
				$row_active_total[$row]=$row_total[$row];
				$col_count_total[$col]=$col_total[$col];
				$row_count_total[$row]=$row_total[$row];
				$data_matrix[$row][$col]['active']=0;
				$data_matrix[$row][$col]['total']=0;
				$data_matrix[$row][$col]['bomb_auto']['src']='';
				$data_matrix[$row][$col]['bomb']['src']='';
				$data_matrix[$row][$col]['bomb_explain']='';
				$data_matrix[$row][$col]['filing']='';
				$data_matrix[$row][$col]['color']='background-color:#DDF;';
				$data_matrix[$row][$col]['color_code']='DDF';
			}
		}
	}
		
	if($_POST['pdfdown'] || $_POST['htmldown'])
	{
	
		$pdfContent .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
						. '<html xmlns="http://www.w3.org/1999/xhtml">'
						. '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
						. '<title>Larvol Master Heatmap Export</title>'
						. '<style type="text/css">'
						. 'body { font-family:Verdana; font-color:black; font-size: 16px;}'
						. 'a, a:hover{color:#000000;text-decoration:none; height:100%;}';
						
						if($_POST['pdfdown']) //0.5px border value does not work for Chrome and IE and 1px border looks dark in PDF
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
						. '<tr><td width="300px" align="left"><b>Name: </b>'. htmlspecialchars($name) .'</td>'
						. '<td width="300px" align="left"><b>Category: </b>'. htmlspecialchars($category) .'</td></tr>'
						. '</table>'
						. '</div><br /><br/>';
						
		$pdfContent .= '<div align="center">'
						. '<table style="border-collapse:collapse; background-color:#DDF;">'
						. '<tr><th>&nbsp;</th>';
				
		foreach($columns as $col => $val)
		{
			$pdfContent .= '<th width="150px"><div align="center">'. $val .'<br />';
					
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && !empty($productIds))
			{
				$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col]. '" target="_blank" title="Total Records"><b title="Active Records">'.$col_active_total[$col].', </b>'.$col_count_total[$col].'</a>';
			}
			$pdfContent .='</div></th>';
		}
		//if total checkbox is selected
		if(isset($_POST['total_col']) && $_POST['total_col'] == "on")
		{
			$pdfContent .= '<th width="150px"><div align="center">';
			if(!empty($productIds) && !empty($areaIds))
			{
				$productIds = array_filter($productIds);
				$areaIds = array_filter($areaIds);
				$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds). '" target="_blank" title="Total Records"><b title="Active Records">'.$active_total.', </b>'.$count_total.'</a>';
			}
			$pdfContent .= '</div></th>';
		}
		$pdfContent .= '</tr>';
		
		foreach($rows as $row => $rval)
		{
			$pdfContent .= '<tr  style="page-break-inside:avoid;" nobr="true"><th width="150px"><div align="center">' . $rval . '<br />';
					
			if(isset($productIds[$row]) && $productIds[$row] != NULL && !empty($areaIds))
			{
				$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds). '" target="_blank" class="ottlink" title="Total Records"><b title="Active Records">'.$row_active_total[$row].', </b>'.$row_count_total[$row].'</a>';
			}
			$pdfContent .= '</div></th>';
			
			foreach($columns as $col => $cval)
			{
				$pdfContent .= '<td width="150px" style="text-align:center; '.$data_matrix[$row][$col]['color'].'" align="center">';
				
				if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($productIds[$row]) && $productIds[$row] != NULL)
				{
					if($data_matrix[$row][$col]['bomb_auto']['src'] != '' && $data_matrix[$row][$col]['bomb_auto']['src'] != NULL)
					$pdfContent .= '<img align="left" title="'.$data_matrix[$row][$col]['bomb_auto']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb_auto']['src'].'" style="'.$data_matrix[$row][$col]['bomb_auto']['style'].' padding-left:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb_auto']['alt'].'"  />';
				
					$pdfContent .= '<a href="'. urlPath() .'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col]. '" target="_blank" title="Total Records"><b title="Active Records">'.$data_matrix[$row][$col]['active'].', </b>'.$data_matrix[$row][$col]['total'].'</a>';
				
					$pdfContent .= '<img align="right" title="'.$data_matrix[$row][$col]['bomb']['title'].'" src="'. urlPath() .'images/'.$data_matrix[$row][$col]['bomb']['src'].'" style="'.$data_matrix[$row][$col]['bomb']['style'].' vertical-align:middle; padding-right:10px; cursor:pointer;" alt="'.$data_matrix[$row][$col]['bomb']['alt'].'"'
			.'onclick="popup_show(\'bombpopup_'.$row.'_'.$col.'\', \'bombpopup_drag_'.$row.'_'.$col.'\', \'bombpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';
					if($_POST['htmldown']) //As there is no need for following code to be executed for PDF
					{
						$pdfContent .= '<br/><br/><img align="right" title="Filing Details" src="'. urlPath() .'images/'. (($data_matrix[$row][$col]['filing'] == NULL && $data_matrix[$row][$col]['filing'] == '') ? 'edit.png' : 'file.png' ) .'" style="width:14px; height:16px; vertical-align:top; cursor:pointer;'.(($data_matrix[$row][$col]['filing'] == NULL && $data_matrix[$row][$col]['filing'] == '') ? ' background-color:#CCCCCC;' : '' ).'" alt="Filing Details" onclick="popup_show(\'filingpopup_'.$row.'_'.$col.'\', \'filingpopup_drag_'.$row.'_'.$col.'\', \'filingpopup_exit_'.$row.'_'.$col.'\', \'mouse\', -10, -10);" />';

					
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
			if(isset($_POST['total_col']) && $_POST['total_col'] == "on")
			{
				$pdfContent .= '<th>&nbsp;</th>';
			}
		
			$pdfContent .= '</tr>';
		}
		$pdfContent .= '</table></div><br /><br/>'
						. '<div align="center"><table align="center" style="border-collapse:collapse; vertical-align:middle; padding:10px; background-color:#DDF;">'
						. '<tr><td width="300px" align="left"><b>Footnotes: </b><br/><div style="padding-left:10px;">'. $footnotes .'</div></td>'
						. '<td width="300px" align="left"><b>Description: </b><br/><div style="padding-left:10px;">'. $description .'</div></td></tr>'
						. '</table></div>'
						. '</body>'
						. '</html>';

		//echo $pdfContent;
		if($_POST['pdfdown'])
		{
			require_once('tcpdf/config/lang/eng.php');
			require_once('tcpdf/tcpdf.php');  
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			// set document information
			//$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('Larvol Trials');
			$pdf->SetTitle('Larvol Trials');
			$pdf->SetSubject('Larvol Trials');
			$pdf->SetKeywords('Larvol Trials, Larvol Trials PDF Export');
			$pdf->SetFont('verdana', '', 8);
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
		if($_POST['htmldown'])
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
	
	if($_POST['exceldown'])
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
				$cell= num2char($col).'1';
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $val.' ('.$col_active_total[$col].', '.$col_count_total[$col].')');
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col])); 
 			    $objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip('Active record, Total Records');
				$objPHPExcel->getActiveSheet()->getColumnDimension(num2char($col))->setWidth(18);
				
				$objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->applyFromArray(
      									array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      											'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
     											'rotation'   => 0,
      											'wrap'       => true));
			}
		}
		
		if(isset($_POST['total_col']) && $_POST['total_col'] == "on")
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
				$cell='A'.($row+1);
				$objPHPExcel->getActiveSheet()->setCellValue($cell, $rval.' ('.$row_active_total[$row].', '.$row_count_total[$row].')');
				$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds))); 
 			    $objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip('Active record, Total Records');
				
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
					if($data_matrix[$row][$col]['bomb_auto']['src'] != '' && $data_matrix[$row][$col]['bomb_auto']['src'] != NULL && $data_matrix[$row][$col]['bomb']['src'] != 'trans.gif')
					{
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX(10);
						$objDrawing->setOffsetY(3);
						$objDrawing->setPath('images/'.$data_matrix[$row][$col]['bomb_auto']['src']);
						$objDrawing->setDescription($data_matrix[$row][$col]['bomb_auto']['title']);
						$objDrawing->setCoordinates($cell);
					}
					
					
					$objPHPExcel->getActiveSheet()->setCellValue($cell, ' ('.$data_matrix[$row][$col]['active'].', '.$data_matrix[$row][$col]['total'].')');
					$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . $productIds[$row] . '&a=' . $areaIds[$col])); 
 			    	$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setTooltip('Filing:- '. $data_matrix[$row][$col]['filing']);
					
					if($data_matrix[$row][$col]['bomb']['src'] != '' && $data_matrix[$row][$col]['bomb']['src'] != NULL && $data_matrix[$row][$col]['bomb']['src'] !='trans.gif')
					{
						$objDrawing = new PHPExcel_Worksheet_Drawing();
						$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
						$objDrawing->setOffsetX(110);
						$objDrawing->setOffsetY(3);
						$objDrawing->setPath('images/'.$data_matrix[$row][$col]['bomb']['src']);
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
		
		if(isset($_POST['total_col']) && $_POST['total_col'] == "on")
		{
			$cell = num2char(count($col)+2).'1';
			$objPHPExcel->getActiveSheet()->setCellValue($cell, 'Total ('.$active_total.', '.$count_total.')');
			$objPHPExcel->getActiveSheet()->getCell($cell)->getHyperlink()->setUrl(urlencode(urlPath() . 'intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds)));
		}
		
		$row = count($rows) + 1;
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, '');
		$objPHPExcel->getActiveSheet()->SetCellValue('A' . ++$row, 'Report name:');
		$objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, substr($name,0,250));
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
	
			
		if(/*connection_aborted()*/true)	//connection_aborted doesn't work due to PHP bug 
		{
			/*ob_start();
			$tempfile = tempnam(sys_get_temp_dir(), 'exc');
			if($tempfile === false) exit;//tex('Unable to create temp file');
			$objWriter->save($tempfile);
			$content = file_get_contents($tempfile);
			unlink($tempfile);
			$mail = new PHPMailer();
			$from = 'no-reply@' . $_SERVER['SERVER_NAME'];
			if(strlen($_SERVER['SERVER_NAME'])) $mail->SetFrom($from);
			$mail->AddAddress($db->user->email);
			$mail->Subject = SITE_NAME . ' manual report ' . date("Y-m-d H.i.s", $now) . ' - ' . substr($name,0,20);
			$mail->Body = 'Attached is the report you generated earlier.';
			$current_filename=substr($name,0,20).'_'.date('Y-m-d_H.i.s', $now);
			$mail->AddStringAttachment($content,
									   $current_filename.'.xlsx',
									   'base64',
									   'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','','manual report');		
			
			@$mail->Send();
			ob_end_clean();*/
		}
		
	} //Excel Function Ends
}

//process POST for editor
function postEd()
{
	global $db;
	if(!isset($_POST['id'])) return;
	$id = mysql_real_escape_string($_POST['id']);
	if(!is_numeric($id)) return;
	
	$_GET['id'] = $id;	//This is so the editor will load the report we are about to (maybe?) save
	
	// block any user from modifying other peoples private reports and block non-admins from modifying global reports
	$query = 'SELECT user FROM `rpt_masterhm` WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting user for master heatmap report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) continue;
	if(count($res)==0){ die('Not found.'); }
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;

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
	
	if($rptu === NULL && $db->user->userlevel == 'user') return;

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
		$owner = (isset($_POST['own']) && $_POST['own'] == 'global' )? 'NULL' : $db->user->id;
		$category = mysql_real_escape_string($_POST['reportcategory']);
		
		$query = 'UPDATE rpt_masterhm SET name="' . mysql_real_escape_string($_POST['reportname']) . '",user=' . $owner
					. ',footnotes="' . $footnotes . '",description="' . $description . '"'
					. ',category="' . $category . '"' . ' WHERE id=' . $id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query saving name');
		
		foreach($types as $t)
		{	
			foreach($_POST[$t."s"] as $num => $header)
			{
				if($header != "") 
				{
					$query = "select id from " . $t . "s where name='" . $header . "' ";
					$row = mysql_fetch_assoc(mysql_query($query)) or die('Bad SQL Query getting ' . $t . ' names ');
					
					$query = 'UPDATE rpt_masterhm_headers SET type_id="' . mysql_real_escape_string($row['id']) 
					. '" WHERE report=' . $id . ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
					mysql_query($query) or die('Bad SQL Query saving ' . $t . ' names ');
				}
			}
		}//exit;
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
	
	if(isset($_POST['bomb_popup']))
	{
	foreach($_POST['bomb_popup'] as $row => $cols)
	foreach($cols as $col => $data);
	
	$bomb=$_POST['bomb'][$row][$col];
	$bomb_explain=mysql_real_escape_string($_POST['bomb_explain'][$row][$col]);
	$id=$_POST['id'];
	$prod=$_POST['cell_prod'][$row][$col];
	$area=$_POST['cell_area'][$row][$col];
	$query = "UPDATE `rpt_masterhm_cells` set `bomb` = '$bomb', `bomb_explain` = '$bomb_explain' WHERE `product` = $prod AND `area` = $area";
	mysql_query($query) or die ('Bad SQL Query updating Bomb Information.<br/>'.$query);
	}
	
	if(isset($_POST['filing_popup']))
	{
	foreach($_POST['filing_popup'] as $row => $cols)
	foreach($cols as $col => $data);
	
	$filing=mysql_real_escape_string($_POST['filing'][$row][$col]);
	$id=$_POST['id'];
	$prod=$_POST['cell_prod'][$row][$col];
	$area=$_POST['cell_area'][$row][$col];
	$query = "UPDATE `rpt_masterhm_cells` set `filing` = '$filing' WHERE `product` = $prod AND `area` = $area";
	mysql_query($query) or die ('Bad SQL Query updating Bomb Information.<br/>'.$query);
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