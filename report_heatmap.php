<?php
require_once('db.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('run_heatmap.php');
if(isset($_GET['run']))
{
	$id = mysql_real_escape_string($_GET['run']);
	if(is_numeric($id))
	{
		$format = 'xlsx';
		if (isset($_GET['format'])) $format = $_GET['format'];
		runHeatmap($id, false, $format);
		exit;
	}
}
require_once('include.search.php');
require('header.php');

echo('<script type="text/javascript" src="delsure.js"></script>');
postRL();
postEd();
postSM();
echo(reportList());
echo(statMon());
echo(editor());
echo('</body></html>');

//return html for status monitor
function statMon()
{
	global $db;
	$query = 'SELECT id,progress,max,lastUpdate,note,TIME_TO_SEC(TIMEDIFF(NOW(),lastUpdate)) as expire FROM progress WHERE what="heatmap" AND user=' . $db->user->id;
	$res = mysql_query($query) or die('Bad SQL query getting status');
	if(mysql_num_rows($res) == 0) return;
	$out = '<form action="report_heatmap.php" method="post"><fieldset><legend>Status Monitor</legend><dl>';
	while($row = mysql_fetch_assoc($res))
	{
		$expireTime = $row['expire'];
		if($expireTime > 600)
		{
		$query = 'DELETE FROM progress WHERE id=' . mysql_real_escape_string($row['id']) . ' LIMIT 1';
		mysql_query($query) or die('Bad report ID for cancelling run');
		continue;
		}
		//print_r($row);die;
		$query2 = 'SELECT name FROM rpt_heatmap WHERE id=' . $row['note'];
		$res2 = mysql_query($query2) or die('Bad SQL query getting report name');
		$res2 = mysql_fetch_assoc($res2);
		$name = strlen($res2['name']) ? $res2['name'] : ('(report ' . $row['note'] . ')');
		$out .= '<dt>' . $name . ' <input type="image" name="stop[' . $row['id'] 
		. ']" src="images/not.png" title="Cancel"/></dt><dd>' . $row['progress'] . '/' . $row['max'] . '</dd>';
	}
	$out .= '</dl></fieldset></form>';
	return $out;
}

//process POST for status monitor
function postSM()
{
	if(!isset($_POST['stop'])) return;
	foreach($_POST['stop'] as $id => $ok)
	{
		$query = 'DELETE FROM progress WHERE id=' . mysql_real_escape_string($id) . ' LIMIT 1';
		mysql_query($query) or die('Bad report ID for cancelling run');
	}
}

//return html for report editor
function editor()
{
	global $db;
	if(!isset($_GET['id'])) return;
	$id = mysql_real_escape_string(htmlspecialchars($_GET['id']));
	if(!is_numeric($id)) return;
	$query = 'SELECT name,user,footnotes,description,searchdata,bomb,backbone_agent,count_only_active,category FROM rpt_heatmap WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;	//prevent anyone from viewing others' private reports
	$name = $res['name'];
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$bomb = $res['bomb'];
	$backboneAgent = $res['backbone_agent'];
	$countonlyactive = $res['count_only_active'];
	$category = $res['category'];
	
	$unisearchdata = $res['searchdata'] !== NULL;
	$query = 'SELECT `header`,`num`,`type`,searchdata FROM rpt_heatmap_headers WHERE report=' . $id . ' ORDER BY num ASC';
	$res = mysql_query($query) or die('Bad SQL query getting report headers');
	$rows = array();
	$columns = array();
	$rowsearches = array();
	$columnsearches = array();
	while($header = mysql_fetch_array($res))
	{
		if($header['type'] == 'row')
		{
			$rows[$header['num']] = $header['header'];
			$rowsearches[$header['num']] = $header['searchdata'] !== NULL;
		}else{
			$columns[$header['num']] = $header['header'];
			$columnsearches[$header['num']] = $header['searchdata'] !== NULL;
		}
	}
	// SELECT MAX ROW AND MAX COL
	$query = 'SELECT MAX(`num`) AS `num` FROM rpt_heatmap_headers WHERE report=' . $id . ' AND type = \'row\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_row = mysql_fetch_array($res);
	
	$query = 'SELECT MAX(`num`) AS `num` FROM rpt_heatmap_headers WHERE report=' . $id . ' AND type = \'column\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_column = mysql_fetch_array($res);
	
	$query = 'SELECT `row`,`column`,`searchdata` FROM rpt_heatmap_cells WHERE report=' . $id . ' ORDER BY `row`,`column`';
	$res = mysql_query($query) or die('Bad SQL query getting cell data ' . "\n<br />" . $query . "\n<br />" . mysql_error());
	$searchdata = array();
	while($cell = mysql_fetch_array($res))
	{
		if(!isset($searchdata[$cell['row']])) $searchdata[$cell['row']] = array();
		$searchdata[$cell['row']][$cell['column']] = $cell['searchdata'];
	}
	$out = '<script type="text/javascript" src="progress/progress.js"></script>'
		. '<div id="runbuttons"><a href="report_heatmap.php?run=' . $id
		.'" target="runframe" onclick="updateProgress(\'heatmap\');document.getElementById(\'runbuttons\').style.display=\'none\'">'
		. '<img src="images/excel.png" title="Run" style="border:0;"/></a>';
	if($db->user->userlevel != 'user')	//high-priority button
	{
		$out .= ' &nbsp; <a href="report_heatmap.php?run=' . $id
			. '&priority=high" target="runframe" '
			. 'onclick="updateProgress(\'heatmap\');document.getElementById(\'runbuttons\').style.display=\'none\'">'
			. '<input type="image" name="getexcel" src="images/excel_fire.png" title="Run w. HIGH PRIORITY" style="border:0;"/></a>';
	}
	$out .= ' &nbsp; <a href="report_heatmap.php?run=' . $id . '&format=doc'
		. '" target="runframe" onclick="updateProgress(\'heatmap\');document.getElementById(\'runbuttons\').style.display=\'none\'">'
		. '<img src="images/word.png" title="Word" style="border:0"></a>';
	$out .= '</div><iframe style="width:500px;height:4em;" name="runframe"></iframe>'
		. '<div id="progress"></div><div class="info" id="success"></div>'
		. '<br style="margin-top:55px;"/>'
		. '<form action="report_heatmap.php" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
		. '<input type="hidden" name="id" value="' . $id . '" />'
		. '<label>Name: <input type="text" name="reportname" value="' . htmlspecialchars($name)
		. '"/></label>'
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
	$out .= '<br clear="all"/>';
	if($db->user->userlevel != 'user' || $rptu !== NULL)
	{
		$out .= '<input type="submit" name="reportsave" value="Save edits" /> | '
			. '<input type="submit" name="addrow" value="More rows" /> | '
			. '<input type="submit" name="delrow" value="Less rows" /> | '
			. '<input type="submit" name="addcolumn" value="More columns" /> | '
			. '<input type="submit" name="delcolumn" value="Less columns" /> | ';
	}
	$out .= '<input type="submit" name="reportcopy" value="Copy into new" /> | '
			. '<a href="report_inputcheck.php?id=' . $id . '">Input check</a>'
			. '<br /><table class="reportcell"><tr><th>';
	if($unisearchdata)
	{
		$out .= '[Search] <a href="search.php?'
				. htmlspecialchars('report=' . $id . '&rload=' . $id) . '">'
				. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
				. '<input type="image" name="delete[x.x]" src="images/not.png" title="Delete"/>';
	}else{
		$out .= '[Empty] <a href="search.php?'
				. htmlspecialchars('report=' . $id)
				. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
	}
			
	$out .= '</th>';
	foreach($columns as $col => $val)
	{
		$out .= '<th><input type="text" name="columns[' . $col . ']" value="' . $val . '" /><br />';
		if($columnsearches[$col])
		{
			$out .= '[Search] <a href="search.php?'
					. htmlspecialchars('report=' . $id . '&col=' . $col . '&rload=' . $id) . '">'
					. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
					. '<input type="image" name="delete[' . ('x.'.$col) . ']" src="images/not.png" title="Delete"/>';
			
			// LEFT ARROW?
			if($col > 1) $out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
			// RIGHT ARROW?
			if($col < $max_column['num'])
				$out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right"/>';	
			
		}else{
			$out .= '[Empty] <a href="search.php?'
					. htmlspecialchars('report=' . $id . '&col=' . $col)
					. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
			
			// LEFT ARROW?
			if($col > 1) $out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
			// RIGHT ARROW?
			if($col < $max_column['num'])
				$out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right"/>';
		}
		$out .= '</th>';
	}
	$out .= '</tr>';
	foreach($rows as $row => $rval)
	{
		$out .= '<tr><th><input type="text" name="rows[' . $row . ']" value="' . $rval . '" /><br />';
		if($rowsearches[$row])
		{
			$out .= '[Search] <a href="search.php?'
					. htmlspecialchars('report=' . $id . '&row=' . $row . '&rload=' . $id) . '">'
					. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
					. '<input type="image" name="delete[' . ($row.'.x') . ']" src="images/not.png" title="Delete"/>';
		
			// UP ARROW?
			if($row > 1) $out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';	
			// DOWN ARROW?
			if($row < $max_row['num'])
				$out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';
		}else{
			$out .= '[Empty] <a href="search.php?'
					. htmlspecialchars('report=' . $id . '&row=' . $row)
					. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
			
			// UP ARROW?
			if($row > 1) $out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';
			// DOWN ARROW?
			if($row < $max_row['num'])
				$out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';
		}
		$out .= '</th>';
		foreach($columns as $col => $cval)
		{
			$out .= '<td>';
			if(isset($searchdata[$row]) && isset($searchdata[$row][$col]))
			{
				$out .= '[Search] <a href="search.php?'
						. htmlspecialchars('report=' . $id . '&row=' . $row . '&col=' . $col . '&rload=' . $id) . '">'
						. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
						. '<input type="image" name="delete[' . ($row.'.'.$col) . ']" src="images/not.png" title="Delete"/>';
			}else{
				$out .= '[Empty] <a href="search.php?'
						. htmlspecialchars('report=' . $id . '&row=' . $row . '&col=' . $col)
						. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
			}
			$out .= '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</table>'
		. '<fieldset><legend>Footnotes</legend><textarea name="footnotes" cols="45" rows="5">' 
		. $footnotes . '</textarea></fieldset>'
		. '<fieldset><legend>Description</legend><textarea name="description" cols="45" rows="5">' . $description
		. '</textarea></fieldset>';
	$out .= '<fieldset><legend>Options</legend><select multiple name="options[]" size="3">';
	$out .= '<option value="bomb"'.($bomb == "Y" ? ' selected' : '').'>Bomb</option>';
	$out .= '<option value="backbone_agent"'.($backboneAgent == "Y" ? ' selected' : '').'>Backbone Agent</option>';
	$out .=	'<option value="count_only_active"'.($countonlyactive == "Y" ? 'selected' : '').'>Count only active</option>';
	$out .= '</select></fieldset>';
	$out .= '</fieldset></form>';

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
	$query = 'SELECT user FROM rpt_heatmap WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting user for report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) continue;
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;

	// "Copy into new" is the exception for non-admins sending POSTdata about global reports
	if(isset($_POST['reportcopy']))
	{
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name,footnotes,description,searchdata,bomb,backbone_agent,count_only_active,category FROM rpt_heatmap WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query getting old data');
		$res = mysql_fetch_array($res);
		if($res === false) return; //not found

		$searchdata = $res['searchdata'];
		$oldname = mysql_real_escape_string($res['name']);
		$footnotes = mysql_real_escape_string($res['footnotes']);
		$description = mysql_real_escape_string($res['description']);
		$bomb = mysql_real_escape_string($res['bomb']);
		$backboneAgent = mysql_real_escape_string($res['backbone_agent']);
		$countonlyactive = mysql_real_escape_string($res['count_only_active']);
		$category = mysql_real_escape_string($res['category']);
		$query = 'INSERT INTO rpt_heatmap SET name="Copy of ' . (strlen($oldname) ? $oldname : ('report '.$id)) . '",user='
				. $db->user->id . ',footnotes="' . $footnotes . '",description="' . $description . '"'
				. ($searchdata !== NULL ? ',searchdata="' . $searchdata . '"' : '')
				. ',bomb="'.$bomb.'",backbone_agent="'.$backboneAgent.'",count_only_active="'.$countonlyactive.'",category="'.$category.'"';
				
		mysql_query($query) or die('Bad SQL Query saving name');
		$newid = mysql_insert_id();
		$tables = array('rpt_heatmap_headers','rpt_heatmap_cells');
		
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
	$types = array('row','column');
	foreach($types as $t)
	{
		$maxvar = 'max' . $t;
		if(isset($_POST['add'.$t]) || isset($_POST['del'.$t]))
		{
			$query = 'SELECT MAX(num) AS "prevnum" FROM rpt_heatmap_headers WHERE report=' . $id
						. ' AND type="' . $t . '" GROUP BY report LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting max ' . $t . ' number');
			$res = mysql_fetch_array($res);
			if($res !== false) $$maxvar = $res['prevnum'];
		}
		if(isset($_POST['add'.$t]))
		{
			$query = 'INSERT INTO rpt_heatmap_headers SET report=' . $id . ',header="",type="' . $t . '",num='
						. ($$maxvar + 1);
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
		}
		if(isset($_POST['del'.$t]))
		{
			$query = 'DELETE FROM rpt_heatmap_headers WHERE report=' . $id . ' AND num=' . $$maxvar
						. ' AND type="' . $t . '" LIMIT 1';
			mysql_query($query) or die('Bad SQL Query removing ' . $t);
			$query = 'DELETE FROM rpt_heatmap_cells WHERE report=' . $id . ' AND `' . $t . '`=' . $$maxvar;
			mysql_query($query) or die('Bad SQL Query removing cells ' . $query);
		}
	}
	if(isset($_POST['reportsave']))
	{
		$footnotes = mysql_real_escape_string($_POST['footnotes']);
		$description = mysql_real_escape_string($_POST['description']);
		$options = $_POST['options'];
		$owner = $_POST['own'] == 'global' ? 'NULL' : $db->user->id;
		$bomb = is_array($options) && in_array("bomb", $options) ? 'Y' : 'N';
		$backboneAgent = is_array($options) && in_array("backbone_agent", $options) ? 'Y' : 'N';
		$countonlyactive = is_array($options) && in_array("count_only_active", $options) ? 'Y' : 'N';
		
		$query = 'UPDATE rpt_heatmap SET name="' . mysql_real_escape_string($_POST['reportname']) . '",user=' . $owner
					. ',footnotes="' . $footnotes . '",description="' . $description . '",bomb="'
					. $bomb . '",backbone_agent="'.$backboneAgent.'"' . ',count_only_active="'.$countonlyactive.'"' . ',category="'.mysql_real_escape_string($_POST['reportcategory']).'"' 
					. ' WHERE id=' . $id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query saving name');
		foreach($types as $t)
		{
			if(isset($_POST[$t."s"])) {
				foreach($_POST[$t."s"] as $num => $header)
				{
					$query = 'UPDATE rpt_heatmap_headers SET header="' . mysql_real_escape_string($header) 
					. '" WHERE report=' . $id
					. ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
					mysql_query($query) or die('Bad SQL Query saving headers');
				}
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
		$sql = "SELECT id FROM rpt_heatmap_headers WHERE num = $current_row AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE rpt_heatmap_headers SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE rpt_heatmap_headers SET num = '$current_row' WHERE num = '$next_row' AND type = 'row' AND id <> '$current_row_id' AND report = '$id'";
		$query = mysql_query($sql);
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_heatmap_cells WHERE row = $current_row AND report = '$id'";
		$query = mysql_query($sql);
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0)
		{
			while($res=mysql_fetch_array($query))
			{
				if($count == "0") $current_cells_list .= $res[id]; else $current_cells_list .= ',' . $res[id];
				$count++;
			}
			$sql = "UPDATE rpt_heatmap_cells SET row = '$next_row' WHERE id IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
			$sql = "UPDATE rpt_heatmap_cells SET row = '$current_row' WHERE row = '$next_row' AND id NOT IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
		}else{
			$sql = "UPDATE rpt_heatmap_cells SET row = '$current_row' WHERE row = '$next_row' AND report = '$id'";
			$query = mysql_query($sql);	
		}
	}
	
	if(isset($_POST['move_row_up']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_row_up'] = array_keys($_POST['move_row_up']);
		$_POST['move_row_up'] = $_POST['move_row_up'][0];
		
		$current_row=$_POST['move_row_up'];
		$next_row=$_POST['move_row_up']-1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM rpt_heatmap_headers WHERE num = $current_row AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_row_id=$res[0];
		$sql = "UPDATE rpt_heatmap_headers SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'row' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE rpt_heatmap_headers SET num = '$current_row' WHERE num = '$next_row' AND type = 'row' AND id <> '$current_row_id' AND report = '$id'";
		$query = mysql_query($sql);
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_heatmap_cells WHERE row = $current_row AND report = '$id'";
		$query = mysql_query($sql);
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0)
		{
			while($res=mysql_fetch_array($query))
			{
				if($count == "0") $current_cells_list .= $res[id]; else $current_cells_list .= ',' . $res[id];
				$count++;
			}
			$sql = "UPDATE rpt_heatmap_cells SET row = '$next_row' WHERE id IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
			$sql = "UPDATE rpt_heatmap_cells SET row = '$current_row' WHERE row = '$next_row' AND id NOT IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
		}else{
			$sql = "UPDATE rpt_heatmap_cells SET row = '$current_row' WHERE row = '$next_row' AND report = '$id'";
			$query = mysql_query($sql);	
		}		
	}

	if(isset($_POST['move_col_left']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_col_left'] = array_keys($_POST['move_col_left']);
		$_POST['move_col_left'] = $_POST['move_col_left'][0];
		
		$current_column=$_POST['move_col_left'];
		$next_column=$_POST['move_col_left']-1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM rpt_heatmap_headers WHERE num = $current_column AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE rpt_heatmap_headers SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE rpt_heatmap_headers SET num = '$current_column' WHERE num = '$next_column' AND type = 'column' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_heatmap_cells WHERE `column` = $current_column AND report = '$id'";
		$query = mysql_query($sql);
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0)
		{
			while($res=mysql_fetch_array($query))
			{
				if($count == "0") $current_cells_list .= $res[id]; else $current_cells_list .= ',' . $res[id];
				$count++;
			}
			$sql = "UPDATE rpt_heatmap_cells SET `column` = '$next_column' WHERE id IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
			$sql = "UPDATE rpt_heatmap_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND id NOT IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
		}else{
			$sql = "UPDATE rpt_heatmap_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND report = '$id'";
			$query = mysql_query($sql);	
		}		
	}
	
	if(isset($_POST['move_col_right']))
	{
		//the real value is the first key in the array in this value
		$_POST['move_col_right'] = array_keys($_POST['move_col_right']);
		$_POST['move_col_right'] = $_POST['move_col_right'][0];
		
		$current_column=$_POST['move_col_right'];
		$next_column=$_POST['move_col_right']+1;
		
		// UPDATE ROW HEADERS
		$sql = "SELECT id FROM rpt_heatmap_headers WHERE num = $current_column AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		$current_column_id=$res[0];
		$sql = "UPDATE rpt_heatmap_headers SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'column' AND report = '$id'";
		$query = mysql_query($sql);
		$sql = "UPDATE rpt_heatmap_headers SET num = '$current_column' WHERE num = '$next_column' AND type = 'column' AND id <> '$current_column_id' AND report = '$id'";
		$query = mysql_query($sql);
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_heatmap_cells WHERE `column` = $current_column AND report = '$id'";
		$query = mysql_query($sql);
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0)
		{
			while($res=mysql_fetch_array($query))
			{
				if($count == "0") $current_cells_list .= $res[id]; else $current_cells_list .= ',' . $res[id];
				$count++;
			}
			$sql = "UPDATE rpt_heatmap_cells SET `column` = '$next_column' WHERE id IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
			$sql = "UPDATE rpt_heatmap_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND id NOT IN($current_cells_list) AND report = '$id'";
			$query = mysql_query($sql);
		}else{
			$sql = "UPDATE rpt_heatmap_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND report = '$id'";
			$query = mysql_query($sql);	
		}		
	}
	
	if(isset($_POST['delete']) || is_array($_POST['delete']))
	{
		foreach($_POST['delete'] as $co => $val)
		{
			$co = explode('.',$co);
			$row = mysql_real_escape_string($co[0]);
			$col = mysql_real_escape_string($co[1]);
			$query = '';
			if(is_numeric($row) && is_numeric($col))
			{
				$query = 'DELETE FROM rpt_heatmap_cells WHERE `report`=' . $id
						. ' AND `row`=' . $row . ' AND `column`=' . $col . ' LIMIT 1';
			}else if(is_numeric($row) || is_numeric($col)){
				$type = '';
				$num = '';
				if(is_numeric($row))
				{
					$type = '"row"';
					$num = $row;
				}else{
					$type = '"column"';
					$num = $col;
				}
				$query = 'UPDATE rpt_heatmap_headers SET searchdata=NULL WHERE report=' . $id . ' AND `type`=' . $type
							. ' AND num=' . $num . ' LIMIT 1';
			}else{
				$query = 'UPDATE rpt_heatmap SET searchdata=NULL WHERE id=' . $id . ' LIMIT 1';
			}
			mysql_query($query) or die('Bad SQL Query removing search');
		}
	}
	
	if(isset($_POST['search']))	//add search to report
	{
		$query = array();
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		if(isset($_POST['row']) && isset($_POST['col']))	//cell
		{
			$row = mysql_real_escape_string($_POST['row']);
			$col = mysql_real_escape_string($_POST['col']);
			if(is_numeric($row) && is_numeric($col))
			{
				$query = 'DELETE FROM rpt_heatmap_cells WHERE report=' . $id . ' AND `row`=' . $row . ' AND `column`=' . $col
							. ' LIMIT 1';
				mysql_query($query) or die('Bad SQL query replacing cell data');
				$query = array('INSERT INTO rpt_heatmap_cells SET searchdata="',
							   '",report=' . $id . ',`row`=' . $row . ',`column`=' . $col);
			}
		}else if(isset($_POST['row']) || isset($_POST['col'])){	//header
			$type = '';
			$num = '';
			if(is_numeric($_POST['row']))
			{
				$type = '"row"';
				$num = mysql_real_escape_string($_POST['row']);
			}else{
				$type = '"column"';
				$num = mysql_real_escape_string($_POST['col']);
			}
			$query = array('UPDATE rpt_heatmap_headers SET searchdata="',
						   '" WHERE report=' . $id . ' AND `num`=' . $num . ' AND `type`=' . $type . ' LIMIT 1');
		}else{	//overall
			$query = array('UPDATE rpt_heatmap SET searchdata="', '" WHERE id=' . $id . ' LIMIT 1');
		}

	    validateInputPCRE($_POST);//alexvp added 
	    
	    //start simulate a test search before proceeding implementation of sql shield function here.
	if(isset($_POST['oldsearch']))
	{
		$_POST = unserialize(base64_decode($_POST['oldsearch']));
	}
	//array_walk_recursive($_POST,ref_mysql_escape);	//breaks regex by escaping backslashes
	$params = prepareParams($_POST);
	$list = array();
	if(is_array($_POST['display']))
	{
		foreach($_POST['display'] as $field => $ok)
		{
			if(!array_key_exists($field,$db->types)) continue;
			if($ok) $list[] = $field;
		}
	}
	$timeMachinePost = (isset($_POST['time_machine']))?$_POST['time_machine']:null;
	$time_machine = strlen($timeMachinePost) ? strtotime($timeMachinePost) : NULL;
	$override = (isset($_POST['override']))?$_POST['override']:null;
	$override_arr = explode(',', $override);
	if($override_arr === false)
	{
		$override_arr = array();
	}else{
		foreach($override_arr as $key => $value)
		{
			$value = nctidToLarvolid($value);
			if($value === false)
			{
				unset($override_arr[$key]);
			}else{
				$override_arr[$key] = $value;
			}
		}
	}
	//first  run the search in test mode 
	search($params,$list,$page,$time_machine,$override_arr,true);	    
	    //end simulate a test search before proceeding implementation of sql shield function here.	
	    

		unset($_POST['row']);
		unset($_POST['col']);
		unset($_POST['id']);
		unset($_POST['searchname']);
		$query = implode(base64_encode(serialize($_POST)), $query);		

		mysql_query($query) or die('Bad SQL query storing search');
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	}
}

//return html for the report list
function reportList()
{
	global $db;
	$out = '<div style="display:block;float:left;"><form method="post" action="report_heatmap.php" class="lisep">'
			. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
			. '<form name="reportlist" method="post" action="report_heatmap.php" class="lisep" onsubmit="return delsure();">'
			. '<fieldset><legend>Select Report</legend><ul>';
	$query = 'SELECT id,name,user,category FROM rpt_heatmap WHERE user IS NULL OR user=' . $db->user->id . ' ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query retrieving report names');
	$res1 = mysql_query($query) or die('Bad SQL query retrieving report names');
	$categoryArr  = array('');
	$outArr = array();
	while($row = mysql_fetch_array($res1))
	{
		if($row['category'])
		$categoryArr[$row['category']] = $row['category'];
		$outArr[] = $row;
	}
	sort($categoryArr);
	
	foreach($categoryArr as $category)
	{
		$out .= '<li>'.ucwords(strtolower($category)).'<ul>';
		foreach($outArr as $row)
		{
			$ru = $row['user'];
			if($row['category']== $category)
			{
				$out .= '<li' . ($ru === NULL ? ' class="global"' : '') . '><a href="report_heatmap.php?id=' . $row['id'] . '">'
						. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')')) . '</a>';
				if($ru == $db->user->id || ($ru === NULL && $db->user->userlevel != 'user'))
				{
					$out .= ' &nbsp; &nbsp; &nbsp; <input type="image" name="delrep[' . $row['id']. ']" src="images/not.png" title="Delete"/>';
				}
				$out .= '</li>';				
			}
		}
		$out .='</ul></li>';
	}
	$out .= '</ul></fieldset></form></div>';
	return $out;
}

//processes POST for report list
function postRL()
{
	global $db;
	if(isset($_POST['makenew']))
	{ 
		mysql_query('INSERT INTO rpt_heatmap SET name="", user=' . $db->user->id) or die('Bad SQL query creating report');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];

		$types = array('row','column');
		foreach($types as $t)
		{
			$query = 'INSERT INTO rpt_heatmap_headers SET report=' . $id . ',header="",type="' . $t . '",num=1';
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
			$query = 'INSERT INTO rpt_heatmap_headers SET report=' . $id . ',header="",type="' . $t . '",num=2';
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
		}
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			$query = 'SELECT user FROM rpt_heatmap WHERE id=' . $id . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting userid for report');
			$res = mysql_fetch_assoc($res);
			if($res === false) continue;
			$ru = $res['user'];
			if($ru == $db->user->id || ($db->user->userlevel != 'user' && $ru === NULL))
				mysql_query('DELETE FROM rpt_heatmap WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting report');
		}
	}
}
?>