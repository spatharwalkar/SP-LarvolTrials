<?php
require_once('db.php');
require_once('report_common.php');

if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
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
<?php
echo('<script type="text/javascript" src="delsure.js"></script>');
postRL();
postEd();
echo(reportListCommon('rpt_master_heatmap'));
echo(editor());
echo('</body></html>');

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
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . $areaIds[$col] . '" target="_blank" class="ottlink">Click</a>';
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
			$out .= '<a href="intermediary.php?p=' . implode(',', $productIds) . '&a=' . implode(',', $areaIds) . '" target="_blank" class="ottlink">Click</a>';
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
			$out .= '<a href="intermediary.php?p=' . $productIds[$row] . '&a=' . implode(',', $areaIds) . '" target="_blank" class="ottlink">Click</a>';
		}
		$out .='<br/>';
		$out .= '</th>';
		
		foreach($columns as $col => $cval)
		{
			$out .= '<td style="text-align:center;">';
			if(isset($areaIds[$col]) && $areaIds[$col] != NULL && isset($areaIds[$row]) && $areaIds[$row] != NULL)
			{
				$out .= '<a href="intermediary.php?p=' . $productIds[$col] . '&a=' . $areaIds[$row] . '" target="_blank" class="ottlink">Click</a>';
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