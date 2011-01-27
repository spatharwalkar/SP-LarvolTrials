<?php
require_once('krumo/class.krumo.php');
require_once('db.php');
require_once('include.search.php');
require_once('include.import.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');
echo('<form name="inspectform" method="get" action="inspect.php">'
			. '<fieldset><legend>Enter the Larvol ID of the record you want to look up.</legend>'
			. '<input type="text" name="larvol_id" /> &nbsp; <input type="submit" name="inspect" value="Lookup" />'
			. '</fieldset></form>');
mysql_query('BEGIN');
if(is_numeric($_POST['larvol_id']))
{
	$_GET['larvol_id'] = $_POST['larvol_id'];
}
if(is_numeric($_GET['larvol_id']))
{
	if($db->user->userlevel != 'user') customPost();
	
	$id = mysql_real_escape_string($_GET['larvol_id']);	
	$res = getRecordData($id);
	
	if($res === NULL)
	{
		echo('<p class="error">' . $id . ': ID not found.</p>');
	}else{
		echo('<a href="summary.php?id=' . $id
			. '"><img src="images/word.png" style="border:0;margin:1em;" title="Summary"/></a>'
			. '<br clear="all"/><p>Click on any object in the record below to collapse or expand it.<br />'
			. 'Scroll down to find the annotation editor and history.</p>');
		krumo($res);
		customFields($id);
	}
}
mysql_query('COMMIT');
echo('<script type="text/javascript" src="krumopen.js"></script>');
echo('</body></html>');

//gets all data for the given record including historical data
function getRecordData($id)
{
	$ret = array();
	$query = 'SELECT data_values.val_int AS "val_int", data_values.val_bool AS "val_bool", '
			. 'data_values.val_varchar AS "val_varchar", data_values.val_date AS "val_date", data_values.val_text AS "val_text", '
			. 'data_enumvals.`value` AS "val_enum", data_values.added AS "added", data_values.superceded AS "superceded", '
			. 'data_fields.`name` AS "fieldname", data_fields.`type` AS "type" '
			. 'FROM data_values '
			. 'LEFT JOIN data_cats_in_study ON data_values.studycat=data_cats_in_study.id '
			. 'LEFT JOIN data_fields ON data_values.`field`=data_fields.id '
			. 'LEFT JOIN data_enumvals ON data_values.val_enum=data_enumvals.id '
			. 'WHERE data_cats_in_study.larvol_id=' . $id;
	$res = mysql_query($query) or die('Bad SQL query getting record data');
	$row = mysql_fetch_assoc($res);
	if($row === false) return NULL;
	do{
		$val = $row['val_'.$row['type']];
		if(!isset($ret[$row['fieldname']])) $ret[$row['fieldname']] = array();
		$sortkey = strtotime($row['added']);
		while(isset($ret[$row['fieldname']][$sortkey])) $sortkey .= '.';
		$ret[$row['fieldname']][$sortkey] = array('value' => $val, 'added' => $row['added'], 'superceded' => $row['superceded']);
	}while($row = mysql_fetch_assoc($res));
	foreach($ret as $field => $valueArr) ksort($ret[$field]);
	return $ret;
}

//handles post from custom fields form
function customPost()
{
	global $now;
	global $db;
	$DTnow = date('Y-m-d H:i:s',$now);
	$id = mysql_real_escape_string($_POST['larvol_id']);
	if(!is_numeric($id)) return;
	if(is_array($_POST['cats']))
	{
		$newcats = array_map('mysql_real_escape_string',$_POST['cats']);
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		$query = 'SELECT category FROM data_cats_in_study WHERE larvol_id=' . $id;
		$res = mysql_query($query) or die('Bad SQL query finding old cats');
		$oldcats = array();
		while($row = mysql_fetch_assoc($res))
		{
			$oldcats[] = $row['category'];
		}
		$addcats = array();
		$delcats = array();
		foreach($newcats as $newcat) if(is_numeric($newcat) && !in_array($newcat,$oldcats)) $addcats[] = $newcat;
		foreach($oldcats as $oldcat) if(!in_array($oldcat,$newcats)) $delcats[] = $oldcat;
		if(count($delcats))
		{
			$query = 'DELETE data_cats_in_study.* FROM '
					. 'data_cats_in_study LEFT JOIN data_categories ON data_cats_in_study.category=data_categories.id '
					. 'WHERE larvol_id=' . $id . ' AND category IN(' . implode(',',$delcats) . ') AND data_categories.name NOT IN("'
					. implode('","', $db->sourceCats) . '")';
			mysql_query($query) or die('Bad SQL query removing old cats'.$query);
		}
		foreach($addcats as $addcat)
		{
			$query = 'INSERT INTO data_cats_in_study SET larvol_id=' . $id . ',category=' . $addcat;
			mysql_query($query) or die('Bad SQL query adding new cat');
		}
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	}
	if(is_array($_POST['fieldval']))
	{
		$newvals = $_POST['fieldval'];
		mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
		foreach($newvals as $fieldid => $val)
		{
			$val = explode('`', $val);
			//get metadata
			$fid = (int)$fieldid;
			$query = 'SELECT `category`,`type`,`name` FROM data_fields WHERE id=' . $fid;
			$res = mysql_query($query) or die('Bad SQL query getting category');
			$res = mysql_fetch_assoc($res) or die('Invalid field');
			$fieldname = $res['name'];
			$category = $res['category'];
			$type = $res['type'];
			$query = 'SELECT id FROM data_cats_in_study WHERE larvol_id=' . $id . ' AND category=' . $category . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting studycat'.mysql_error().$query);
			$res = mysql_fetch_assoc($res) or die('Study not in category');
			$studycat = $res['id'];
			foreach($val as $key => $dp)
			{
				if($dp == '' || $dp == 'NULL') $val[$key] = NULL;
			}
			
			//get the current values
			$query = 'SELECT id,val_' . $type . ' AS "val" FROM data_values WHERE studycat=' . $studycat
						. ' AND superceded IS NULL AND field=' . $fid;
			$res = mysql_query($query) or die('Bad SQL query getting previous values');
			$oldvals = array();
			while($row = mysql_fetch_assoc($res))
			{
				$oldvals[$row['id']] = $row['val'];
			}
			$ids = array_keys($oldvals);

			//if the input is the same as what exists, do nothing
			$test_oldvals = $oldvals;
			$test_val = $val;
			sort($test_oldvals);
			sort($test_val);
			foreach($test_oldvals as $key => $dp)
			{
				$dp = trim($dp);
				$dp = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $dp);
				$dp = ereg_replace(" {2,}", ' ',$dp);
				$test_oldvals[$key] = $dp;
				if($dp == '' || $dp === NULL) unset($test_oldvals[$key]);
			}
			foreach($test_val as $key => $dp)
			{
				$dp = trim($dp);
				$dp = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $dp);
				$dp = ereg_replace(" {2,}", ' ',$dp);
				$test_val[$key] = $dp;
				if($dp == '' || $dp === NULL) unset($test_val[$key]);
			}
			if($test_oldvals == $test_val) continue;
			
			//update existing data points as superceded
			if(count($oldvals))
			{
				$query = 'UPDATE data_values SET superceded="' . $DTnow . '" WHERE id IN(' . implode(',', $ids) . ')';
				mysql_query($query) or die('Bad SQL query dating old values'.mysql_error().$query);
			}
			
			//add the new data
			$val = array_map('mysql_real_escape_string', $val);
			foreach($val as $dp)
			{
				$query = 'INSERT INTO data_values SET field=' . $fid . ',`added`="' . $DTnow . '",studycat=' . $studycat
						. ',val_' . $type . '=';
				switch($type)
				{
					case 'int':
					case 'bool':
					case 'enum':
					$query .= $dp;
					break;
	
					case 'varchar':
					case 'text':
					$query .= ($dp === NULL) ? $dp : ('"' . $dp . '"');
					break;
					
					case 'date':
					$query .= ($dp === NULL) ? $dp : ('"' . date("Y-m-d",strtotime($dp)) . '"');
					break;
				}
				mysql_query($query) or die('Bad SQL query adding custom field data');
			}
		}
		mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	}
}

// displays form for viewing and editing this record's data for custom fields
function customFields($id)
{
	global $db;
	mysql_query('BEGIN') or die("Couldn't begin SQL query");
	$query = 'SELECT id,name FROM data_categories';
	$res = mysql_query($query) or die('Bad SQL query getting CFCs');
	$cats = array();
	while($row = mysql_fetch_assoc($res))
	{
		$cats[$row['id']] = $row['name'];
	}
	$query = 'SELECT data_cats_in_study.id AS "id",data_cats_in_study.category AS "category",name FROM data_cats_in_study '
			. 'LEFT JOIN data_categories ON data_categories.id=data_cats_in_study.category '
			. 'WHERE larvol_id=' . $id;
	$res = mysql_query($query) or die('Bad SQL query getting applied CFCs');
	$currentCats = array();
	while($row = mysql_fetch_assoc($res))
	{
		$query = 'SELECT id,name,type FROM data_fields WHERE category=' . $row['category'];
		$res2 = mysql_query($query) or die('Bad SQL query getting fields');
		$fields = array();
		while($row2 = mysql_fetch_assoc($res2))
		{
			$enumvals = array();
			if($row2['type'] == 'enum')
			{
				$query = 'SELECT id,value FROM data_enumvals WHERE field=' . $row2['id'];
				$res3 = mysql_query($query) or die('Bad SQL query getting enumvals');
				while($row3 = mysql_fetch_assoc($res3))
				{
					$enumvals[$row3['id']] = $row3['value'];
				}
			}
			$fields[$row2['id']] = new CustomField($row2['id'], $row2['name'], $row2['type'], $enumvals);
			$query = 'SELECT val_' . $row2['type'];
			$query .= ' AS "val" FROM data_values';
			$query .= ' WHERE field=' . $row2['id'] . ' AND studycat=' . $row['id'] . ' AND superceded IS NULL';
			$boxval = array();
			$resval = mysql_query($query) or die('Bad SQL query getting value'.mysql_error().$query);
			while($row3 = mysql_fetch_assoc($resval))
			{
				$boxval[] = $row3['val'];
			}

			switch($row2['type'])
			{
				case 'enum':
				case 'bool':
				$fields[$row2['id']]->value = !count($boxval) ? NULL : (int)$boxval[0];
				break;
				
				default:
				$fields[$row2['id']]->value = implode('`',$boxval);
			}
		}
		$currentCats[$row['category']]['fields'] = $fields;
		$currentCats[$row['category']]['name'] = $row['name'];
	}
	mysql_query('COMMIT') or die("Couldn't commit SQL query");
	//put together html
	$limiter = $db->user->userlevel == 'user';
	$dis = $limiter ? ' disabled="disabled"' : '';
	$out = '<form method="post" action="inspect.php"><fieldset><legend>Select applicable categories for current record</legend>'
			. '<select name="cats[]" size="5" multiple="multiple"' . $dis . '><option value="NULL"> </option>';
	foreach($cats as $catid => $name)
	{
		$out .= '<option value="' . $catid . '"' . (in_array($catid,array_keys($currentCats))?' selected="selected"':'') . '>'
				. $name . '</option>';
	}
	$out .= '</select><br clear="all"/><span class="info">Ctrl-click to select multiple.<br />'
			. 'Removing categories from the list will throw out the associated data!</span><br clear="all"/>'
			. ($limiter ? '' : '<input type="submit" name="savecats" value="Apply" />')
			. '<input type="hidden" name="larvol_id" value="' . $id
			. '" /></fieldset></form><br clear="all"/>';
	$out .= '<form action="inspect.php" method="post"><fieldset><legend>Editable data</legend>'
			. 'Separate multiple values in a field by using a backtick (`)'
			. '<input type="hidden" name="larvol_id" value="' . $id . '" /><br clear="all"/>';
	if(count($currentCats)==0) $out .= '(No categories apply)';
	foreach($currentCats as $id => $cc)
	{
		$out .= '<fieldset style="float:left;"><legend>' . htmlspecialchars($cc['name']) . '</legend><dl>';
		foreach($cc['fields'] as $field)
		{
			$out .= '<dt>' . htmlspecialchars($field->name) . '</dt><dd>';
			switch($field->type)
			{
				case 'int':
				case 'varchar':
				$out .= '<input type="text" name="fieldval[' . $field->id . ']" value="' . htmlspecialchars($field->value)
						. '"' . $dis . '/>';
				break;
				
				case 'date':
				$out .= '<input type="text" name="fieldval[' . $field->id . ']" value="' . htmlspecialchars($field->value)
						. '" class="date_input"' . $dis . '/>';
				break;
				
				case 'text':
				$out .= '<textarea name="fieldval[' . $field->id . ']" rows="3" cols="21"' . $dis . '>'
						. htmlspecialchars($field->value) . '</textarea>';
				break;
				
				case 'enum':
				$out .= '<select name="fieldval[' . $field->id . ']"' . $dis . '><option value="NULL"'
						. ($field->value===NULL ? ' selected="selected"' : '') . '> </option>';
				foreach($field->enumvals as $evid => $ev)
				{
					$out .= '<option value="' . $evid . '"'
							. ($field->value===$evid ? ' selected="selected"' : '')
							. '>' . $ev . '</option>';
				}
				$out .= '</select>';
				break;
				
				case 'bool':
				$out .= '<select name="fieldval[' . $field->id . ']"' . $dis . '>'
						. '<option value="NULL"' . ($field->value===NULL ? ' selected="selected"' : '')
						. '> </option><option value="0"' . ($field->value===0 ? ' selected="selected"' : '')
						. '>0</option><option value="1"' . ($field->value===1 ? ' selected="selected"' : '') . '>1</option></select>';
				break;
			}
			$out .= '</dd>';
		}
		$out .= '</dl></fieldset>';
	}
	$out .= '<br clear="all"/>';
	if(count($currentCats) > 0 && !$limiter) $out .= '<input type="submit" name="savedata" value="Save edits" />';
	$out .= '</fieldset></form>';
	echo($out);
}

?>