<?php
require_once('db.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require_once('run_competitor.php');
if(isset($_GET['run']))
{
	$id = mysql_real_escape_string($_GET['run']);
	if(is_numeric($id))
	{
		$format = 'xlsx';
		if (isset($_GET['format'])) $format = $_GET['format'];
		runCompetitor($id, false, $format);
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
	$query = 'SELECT id,progress,max,note FROM progress WHERE what="competitor" AND user=' . $db->user->id;
	$res = mysql_query($query) or die('Bad SQL query getting status');
	if(mysql_num_rows($res) == 0) return;
	
	$out = '<form action="report_competitor.php" method="post"><fieldset><legend>Status Monitor</legend><dl>';
	while($row = mysql_fetch_assoc($res))
	{
		$query2 = 'SELECT name FROM rpt_competitor WHERE id=' . $row['note'];
		$res2 = mysql_query($query2) or die('Bad SQL query getting report name');
		$res2 = mysql_fetch_assoc($res2);
		$name = strlen($res2['name']) ? $res2['name'] : ('(report ' . $row['note'] . ')');
		$out .= '<dt>' . $name . ' <input type="image" name="stop[' . $row['id'] . ']" src="images/not.png" title="Cancel"/></dt><dd>'
				. $row['progress'] . '/' . $row['max'] . '</dd>';
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
	$query = 'SELECT name,user,footnotes,description,searchdata FROM rpt_competitor WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting report');
	$res = mysql_fetch_array($res) or die('Report not found.');
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;	//prevent anyone from viewing others' private reports
	$name = $res['name'];
	$footnotes = htmlspecialchars($res['footnotes']);
	$description = htmlspecialchars($res['description']);
	$unisearchdata = $res['searchdata'] !== NULL;
	$query = 'SELECT `header`,`num`,`type`,searchdata FROM rpt_competitor_headers WHERE report=' . $id . ' ORDER BY num ASC';
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
	$query = 'SELECT MAX(`num`) AS `num` FROM rpt_competitor_headers WHERE report=' . $id . ' AND type = \'row\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_row = mysql_fetch_array($res);
	
	$query = 'SELECT MAX(`num`) AS `num` FROM rpt_competitor_headers WHERE report=' . $id . ' AND type = \'column\'';
	$res = mysql_query($query) or die(mysql_error());
	$max_column = mysql_fetch_array($res);
	
	$query = 'SELECT `row`,`column`,`searchdata` FROM rpt_competitor_cells WHERE report=' . $id . ' ORDER BY `row`,`column`';
	$res = mysql_query($query) or die('Bad SQL query getting cell data ' . "\n<br />" . $query . "\n<br />" . mysql_error());
	$searchdata = array();
	while($cell = mysql_fetch_array($res))
	{
		if(!isset($searchdata[$cell['row']])) $searchdata[$cell['row']] = array();
		$searchdata[$cell['row']][$cell['column']] = $cell['searchdata'];
	}
	$out = '<script type="text/javascript" src="progress/progress.js"></script>'
		. '<div id="runbuttons"><a href="report_competitor.php?run=' . $id
		.'" target="runframe" onclick="updateProgress(\'competitor\');document.getElementById(\'runbuttons\').style.display=\'none\'">'
		. '<img src="images/excel.png" title="Run" style="border:0;"/></a>';
	if($db->user->userlevel != 'user')	//high-priority button
	{
		$out .= ' &nbsp; <a href="report_competitor.php?run=' . $id
			. '&priority=high" target="runframe" '
			. 'onclick="updateProgress(\'competitor\');document.getElementById(\'runbuttons\').style.display=\'none\'">'
			. '<input type="image" name="getexcel" src="images/excel_fire.png" title="Run w. HIGH PRIORITY" style="border:0;"/></a>';
	}
	$out .= ' &nbsp; <a href="report_competitor.php?run=' . $id . '&format=word" target="runframe" '
		. 'onclick="updateProgress(\'competitor\');document.getElementById(\'runbuttons\').style.display=\'none\'">'
		. '<img src="images/word.png" title="Word" style="border:0;"></a>';
	$out .= '</div><iframe style="width:0;height:0;" name="runframe"></iframe>'
		. '<div id="progress"></div><div class="info" id="success"></div>'
		. '<br style="margin-top:55px;"/>'
		. '<form action="report_competitor.php" method="post"><fieldset><legend>Edit report ' . $id . '</legend>'
		. '<input type="hidden" name="id" value="' . $id . '" />'
		. '<label>Name: <input type="text" name="reportname" value="' . htmlspecialchars($name)
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
			. '<a href="report_inputcheck.php?id=' . $id . '&amp;type=competitor">Input check</a>'
			. '<br /><table class="reportcell"><tr><th>';
	if($unisearchdata)
	{
		$out .= '[Search] <a href="search.php?'
				. htmlspecialchars('competitor=' . $id . '&cload=' . $id) . '">'
				. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
				. '<input type="image" name="delete[x.x]" src="images/not.png" title="Delete"/>';
	}else{
		$out .= '[Empty] <a href="search.php?'
				. htmlspecialchars('competitor=' . $id)
				. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
	}
	
	$out .= '</th>';
	foreach($columns as $col => $val)
	{
		$out .= '<th><input type="text" name="columns[' . $col . ']" value="' . $val . '" /><br />';
		if($columnsearches[$col])
		{
			$out .= '[Search] <a href="search.php?'
					. htmlspecialchars('competitor=' . $id . '&col=' . $col . '&cload=' . $id) . '">'
					. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
					. '<input type="image" name="delete[' . ('x.'.$col) . ']" src="images/not.png" title="Delete"/>';

			// LEFT ARROW?
			
			if($col > 1) {
				$out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';
			}
			
			// RIGHT ARROW?
			if($col < $max_column['num']) {
				$out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right"/>';	
			}

		}else{
			$out .= '[Empty] <a href="search.php?'
					. htmlspecialchars('competitor=' . $id . '&col=' . $col)
					. '"><img src="images/add.png" alt="Add" title="Add"/></a>';

			// LEFT ARROW?
			
			if($col > 1) {
				$out .= ' <input type="image" name="move_col_left[' . $col . ']" src="images/left.png" title="Left"/>';	
			}
			
			// RIGHT ARROW?
			if($col < $max_column['num']) {
				$out .= ' <input type="image" name="move_col_right[' . $col . ']" src="images/right.png" title="Right"/>';	
			}
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
					. htmlspecialchars('competitor=' . $id . '&row=' . $row . '&cload=' . $id) . '">'
					. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
					. '<input type="image" name="delete[' . ($row.'.x') . ']" src="images/not.png" title="Delete"/>';
					
			// UP ARROW?
			
			if($row > 1) {
				$out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';	
			}
			
			// DOWN ARROW?
			if($row < $max_row['num']) {
				$out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';	
			}

		}else{
			$out .= '[Empty] <a href="search.php?'
					. htmlspecialchars('competitor=' . $id . '&row=' . $row)
					. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
					
			// UP ARROW?
			
			if($row > 1) {
				$out .= ' <input type="image" name="move_row_up[' . $row . ']" src="images/asc.png" title="Up"/>';
			}
			
			// DOWN ARROW?
			if($row < $max_row['num']) {
				$out .= ' <input type="image" name="move_row_down[' . $row . ']" src="images/des.png" title="Down"/>';	
			}
		}
		$out .= '</th>';
		foreach($columns as $col => $cval)
		{
			$out .= '<td>';
			if(isset($searchdata[$row]) && isset($searchdata[$row][$col]))
			{
				$out .= '[Search] <a href="search.php?'
						. htmlspecialchars('competitor=' . $id . '&row=' . $row . '&col=' . $col . '&cload=' . $id) . '">'
						. '<img src="images/edit.png" alt="Edit" title="Edit"/></a> - '
						. '<input type="image" name="delete[' . ($row.'.'.$col) . ']" src="images/not.png" title="Delete"/>';
			}else{
				$out .= '[Empty] <a href="search.php?'
						. htmlspecialchars('competitor=' . $id . '&row=' . $row . '&col=' . $col)
						. '"><img src="images/add.png" alt="Add" title="Add"/></a>';
			}
			$out .= '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</table>'
		. '<fieldset><legend>Footnotes</legend><textarea name="footnotes" cols="45" rows="5">' . $footnotes . '</textarea></fieldset>'
		. '<fieldset><legend>Description</legend><textarea name="description" cols="45" rows="5">' . $description
		. '</textarea></fieldset>';
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
	$query = 'SELECT user FROM rpt_competitor WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting user for report id');
	$res = mysql_fetch_assoc($res);
	if($res === false) continue;
	$rptu = $res['user'];
	if($rptu !== NULL && $rptu != $db->user->id) return;

	// "Copy into new" is the exception for non-admins sending POSTdata about global reports
	if(isset($_POST['reportcopy']))
	{
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT name,footnotes,description,searchdata FROM rpt_competitor WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL Query getting old data');
		$res = mysql_fetch_array($res);
		if($res === false) return; //not found
		$searchdata = $res['searchdata'];
		$oldname = mysql_real_escape_string($res['name']);
		$footnotes = mysql_real_escape_string($res['footnotes']);
		$description = mysql_real_escape_string($res['description']);
		$query = 'INSERT INTO rpt_competitor SET name="Copy of ' . (strlen($oldname) ? $oldname : ('report '.$id))
				. '",user=' . $db->user->id . ',footnotes="' . $footnotes . '",description="' . $description . '"'
				. ($searchdata !== NULL ? ',searchdata="' . $searchdata . '"' : '');
		mysql_query($query) or die('Bad SQL Query saving name');
		$newid = mysql_insert_id();
		$tables = array('rpt_competitor_headers','rpt_competitor_cells');
		
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
				mysql_query($query) or die('Bad SQL query copying data');
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
			$query = 'SELECT MAX(num) AS "prevnum" FROM rpt_competitor_headers WHERE report=' . $id
						. ' AND type="' . $t . '" GROUP BY report LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting max ' . $t . ' number');
			$res = mysql_fetch_array($res);
			if($res !== false) $$maxvar = $res['prevnum'];
		}
		if(isset($_POST['add'.$t]))
		{
			$query = 'INSERT INTO rpt_competitor_headers SET report=' . $id . ',header="",type="' . $t . '",num='
						. ($$maxvar + 1);
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
		}
		if(isset($_POST['del'.$t]))
		{
			$query = 'DELETE FROM rpt_competitor_headers WHERE report=' . $id . ' AND num=' . $$maxvar
						. ' AND type="' . $t . '" LIMIT 1';
			mysql_query($query) or die('Bad SQL Query removing ' . $t);
			$query = 'DELETE FROM rpt_competitor_cells WHERE report=' . $id . ' AND `' . $t . '`=' . $$maxvar;
			mysql_query($query) or die('Bad SQL Query removing cells ' . $query);
		}
	}
	if(isset($_POST['reportsave']))
	{
		$footnotes = mysql_real_escape_string($_POST['footnotes']);
		$description = mysql_real_escape_string($_POST['description']);
		$owner = $_POST['own'] == 'global' ? 'NULL' : $db->user->id;
		$query = 'UPDATE rpt_competitor SET name="' . mysql_real_escape_string($_POST['reportname']) . '",user=' . $owner
					. ',footnotes="' . $footnotes . '",description="' . $description . '"'
					. ' WHERE id=' . $id . ' LIMIT 1';
		mysql_query($query) or die('Bad SQL Query saving name');
		foreach($types as $t)
		{
			foreach($_POST[$t.'s'] as $num => $header)
			{
				$query = 'UPDATE rpt_competitor_headers SET header="' . mysql_real_escape_string($header) . '" WHERE report=' . $id
							. ' AND num=' . $num . ' AND type="' . $t . '" LIMIT 1';
				mysql_query($query) or die('Bad SQL Query saving headers');
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
		
		$sql = "SELECT id FROM rpt_competitor_headers WHERE num = $current_row AND type = 'row' AND report = '$id'";
		////echo $sql;
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		//var_dump($res);
		$current_row_id=$res[0];
		$sql = "UPDATE rpt_competitor_headers SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'row' AND report = '$id'";
		////echo $sql;
		$query = mysql_query($sql);
		
		$sql = "UPDATE rpt_competitor_headers SET num = '$current_row' WHERE num = '$next_row' AND type = 'row' AND id <> '$current_row_id' AND report = '$id'";
		////echo $sql;
		$query = mysql_query($sql);
		
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_competitor_cells WHERE row = $current_row AND report = '$id'";
		$query = mysql_query($sql);
		//echo $sql;
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0) {
			while($res=mysql_fetch_array($query)) {
				if($count == "0") {
					$current_cells_list .= $res[id];
				}
				else{
					$current_cells_list .= ',' . $res[id];
				}
				$count++;
			}
			
			$sql = "UPDATE rpt_competitor_cells SET row = '$next_row' WHERE id IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
			
			$sql = "UPDATE rpt_competitor_cells SET row = '$current_row' WHERE row = '$next_row' AND id NOT IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
		}
		else{
			$sql = "UPDATE rpt_competitor_cells SET row = '$current_row' WHERE row = '$next_row' AND report = '$id'";
			//echo $sql;
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
		
		$sql = "SELECT id FROM rpt_competitor_headers WHERE num = $current_row AND type = 'row' AND report = '$id'";
		////echo $sql;
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		//var_dump($res);
		$current_row_id=$res[0];
		$sql = "UPDATE rpt_competitor_headers SET num = '$next_row' WHERE id = '$current_row_id' AND type = 'row' AND report = '$id'";
		////echo $sql;
		$query = mysql_query($sql);
		
		$sql = "UPDATE rpt_competitor_headers SET num = '$current_row' WHERE num = '$next_row' AND type = 'row' AND id <> '$current_row_id' AND report = '$id'";
		////echo $sql;
		$query = mysql_query($sql);
		
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_competitor_cells WHERE row = $current_row AND report = '$id'";
		$query = mysql_query($sql);
		//echo $sql;
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0) {
			while($res=mysql_fetch_array($query)) {
				if($count == "0") {
					$current_cells_list .= $res[id];
				}
				else{
					$current_cells_list .= ',' . $res[id];
				}
				$count++;
			}
			
			$sql = "UPDATE rpt_competitor_cells SET row = '$next_row' WHERE id IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
			
			$sql = "UPDATE rpt_competitor_cells SET row = '$current_row' WHERE row = '$next_row' AND id NOT IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
		}
		else{
			$sql = "UPDATE rpt_competitor_cells SET row = '$current_row' WHERE row = '$next_row' AND report = '$id'";
			//echo $sql;
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
		
		$sql = "SELECT id FROM rpt_competitor_headers WHERE num = $current_column AND type = 'column' AND report = '$id'";
		//echo $sql;
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		//var_dump($res);
		$current_column_id=$res[0];
		$sql = "UPDATE rpt_competitor_headers SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'column' AND report = '$id'";
		//echo $sql;
		$query = mysql_query($sql);
		
		$sql = "UPDATE rpt_competitor_headers SET num = '$current_column' WHERE num = '$next_column' AND type = 'column' AND id <> '$current_column_id' AND report = '$id'";
		//echo $sql;
		$query = mysql_query($sql);
		
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_competitor_cells WHERE `column` = $current_column AND report = '$id'";
		$query = mysql_query($sql);
		//echo $sql;
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0) {
			while($res=mysql_fetch_array($query)) {
				if($count == "0") {
					$current_cells_list .= $res[id];
				}
				else{
					$current_cells_list .= ',' . $res[id];
				}
				$count++;
			}
			
			$sql = "UPDATE rpt_competitor_cells SET `column` = '$next_column' WHERE id IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
			
			$sql = "UPDATE rpt_competitor_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND id NOT IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
		}
		else{
			$sql = "UPDATE rpt_competitor_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND report = '$id'";
			//echo $sql;
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
		
		$sql = "SELECT id FROM rpt_competitor_headers WHERE num = $current_column AND type = 'column' AND report = '$id'";
		//echo $sql;
		$query = mysql_query($sql);
		$res=mysql_fetch_row($query);
		//var_dump($res);
		$current_column_id=$res[0];
		$sql = "UPDATE rpt_competitor_headers SET num = '$next_column' WHERE id = '$current_column_id' AND type = 'column' AND report = '$id'";
		//echo $sql;
		$query = mysql_query($sql);
		
		$sql = "UPDATE rpt_competitor_headers SET num = '$current_column' WHERE num = '$next_column' AND type = 'column' AND id <> '$current_column_id' AND report = '$id'";
		//echo $sql;
		$query = mysql_query($sql);
		
		// UPDATE CELLS
		$sql = "SELECT id FROM rpt_competitor_cells WHERE `column` = $current_column AND report = '$id'";
		$query = mysql_query($sql);
		//echo $sql;
		$count=0;
		$current_cells_list="";
		$tmp_total = mysql_num_rows($query);
		if($tmp_total > 0) {
			while($res=mysql_fetch_array($query)) {
				if($count == "0") {
					$current_cells_list .= $res[id];
				}
				else{
					$current_cells_list .= ',' . $res[id];
				}
				$count++;
			}
			
			$sql = "UPDATE rpt_competitor_cells SET `column` = '$next_column' WHERE id IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
			
			$sql = "UPDATE rpt_competitor_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND id NOT IN($current_cells_list) AND report = '$id'";
			//echo $sql;
			$query = mysql_query($sql);
		}
		else{
			$sql = "UPDATE rpt_competitor_cells SET `column` = '$current_column' WHERE `column` = '$next_column' AND report = '$id'";
			//echo $sql;
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
				$query = 'DELETE FROM rpt_competitor_cells WHERE `report`=' . $id
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
				$query = 'UPDATE rpt_competitor_headers SET searchdata=NULL WHERE report=' . $id . ' AND `type`=' . $type
							. ' AND num=' . $num . ' LIMIT 1';
			}else{
				$query = 'UPDATE rpt_competitor SET searchdata=NULL WHERE id=' . $id . ' LIMIT 1';
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
				$query = 'DELETE FROM rpt_competitor_cells WHERE report=' . $id . ' AND `row`=' . $row . ' AND `column`=' . $col
							. ' LIMIT 1';
				mysql_query($query) or die('Bad SQL query replacing cell data');
				$query = array('INSERT INTO rpt_competitor_cells SET searchdata="',
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
			$query = array('UPDATE rpt_competitor_headers SET searchdata="',
						   '" WHERE report=' . $id . ' AND `num`=' . $num . ' AND `type`=' . $type . ' LIMIT 1');
		}else{	//overall
			$query = array('UPDATE rpt_competitor SET searchdata="', '" WHERE id=' . $id . ' LIMIT 1');
		}

	    validateInputPCRE($_POST);//alexvp added 

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
	$out = '<div style="display:block;float:left;"><form method="post" action="report_competitor.php" class="lisep">'
			. '<input type="submit" name="makenew" value="Create new" style="float:none;" /></form><br clear="all"/>'
			. '<form name="reportlist" method="post" action="report_competitor.php" class="lisep" onsubmit="return delsure();">'
			. '<fieldset><legend>Select Competitor Dashboard</legend><ul>';
	$query = 'SELECT id,name,user FROM rpt_competitor WHERE user IS NULL OR user=' . $db->user->id . ' ORDER BY user';
	$res = mysql_query($query) or die('Bad SQL query retrieving report names');
	while($row = mysql_fetch_array($res))
	{
		$ru = $row['user'];
		$out .= '<li' . ($ru === NULL ? ' class="global"' : '') . '><a href="report_competitor.php?id=' . $row['id'] . '">'
				. htmlspecialchars(strlen($row['name'])>0?$row['name']:('(report '.$row['id'].')')) . '</a>';
		if($ru == $db->user->id || ($ru === NULL && $db->user->userlevel != 'user'))
		{
			$out .= ' &nbsp; &nbsp; &nbsp; <input type="image" name="delrep[' . $row['id']
				. ']" src="images/not.png" title="Delete"/>';
		}
		$out .= '</li>';
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
		mysql_query('INSERT INTO rpt_competitor SET name="",user=' . $db->user->id) or die('Bad SQL query creating report');
		$_GET['id'] = mysql_insert_id();
		$id = $_GET['id'];

		$types = array('row','column');
		foreach($types as $t)
		{
			$query = 'INSERT INTO rpt_competitor_headers SET report=' . $id . ',header="",type="' . $t . '",num=1';
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
			$query = 'INSERT INTO rpt_competitor_headers SET report=' . $id . ',header="",type="' . $t . '",num=2';
			mysql_query($query) or die('Bad SQL Query adding ' . $t);
		}
	}
	if(isset($_POST['delrep']) && is_array($_POST['delrep']))
	{
		foreach($_POST['delrep'] as $id => $ok)
		{
			$id = mysql_real_escape_string($id);
			if(!is_numeric($id)) continue;
			$query = 'SELECT user FROM rpt_competitor WHERE id=' . $id . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting userid for report');
			$res = mysql_fetch_assoc($res);
			if($res === false) continue;
			$ru = $res['user'];
			if($ru == $db->user->id || ($db->user->userlevel != 'user' && $ru === NULL))
				mysql_query('DELETE FROM rpt_competitor WHERE id=' . $id . ' LIMIT 1') or die('Bad SQL query deleting report');
		}
	}
}
?>