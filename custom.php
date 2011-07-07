<?php
require_once('db.php');
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');

require_once('PHPExcel.php');
require_once('PHPExcel/IOFactory.php');
require_once('PHPExcel/Worksheet.php');

$uploadmsg = '';
$types = getEnumValues('data_fields','type');
processList();
$editerrors = processEditor();
$cat_exists = false;
echo('<script type="text/javascript" src="delsure.js"></script>');
?>
<div style="float:right;"><p>A note on data types: Carefully consider the data type for each field in order to make them easily searchable. Changing the type for an existing field will clear the associated data. Note that the &quot;values&quot; box in the editor only applies when the field is of type enum.</p>
 <ul>
  <li><b>int</b> - Integers only. Allows searching a numeric range.</li>
  <li><b>varchar</b> - General purpose &quot;string&quot; datatype with good performance. Max 255 characters.</li>
  <li><b>text</b> - For fields that hold a lot of text (More than 255 chars).</li>
  <li><b>date</b> - Allows ranged searching and recognizes dates in the form YYYY-MM-DD.</li>
  <li><b>enum</b> - Enumeration. Creates a selection box on the search and edit pages. Use only when the field's value must be one of a group of pre-determined strings.&nbsp;Define the possible values in the enum by entering a <span style="color:red;">backtick-seperated (<span style="font-family:courier,fixed-width;font-weight:bold;">`</span>)</span> list in the &quot;values&quot; box for the field. Use of enum saves time by eliminating typing (and therefore typing errors) and is also more efficient compared to using varchar.<br />
Warning: changing the list of allowed values for an existing enum will clear the data for that field.</li>
  <li><b>bool</b> - Boolean value. Creates a dropdown with only 0 and 1. Use for fields that are simple yes/no.</li>
 </ul>
</div>
<?php
echo(catList());
echo(fieldEditor());


echo('</body></html>');

//processes postdata from "category list" form
function processList()
{
	global $db;
	$sourcecat=array();
	foreach($db->sources as $sourc=>$nam) { $sourcecat[]=$nam->categoryName ; }  
	if(isset($_POST['cat_del']) && !is_array($_POST['cat_del'])) return;
	if(isset($_POST['cat_del']))
	{
		foreach($_POST['cat_del'] as $id => $n)
		{
			$query = 'DELETE FROM data_categories WHERE id=' . mysql_real_escape_string($id)
					. ' AND name NOT IN("' . implode('","', $sourcecat) . '") LIMIT 1';
			mysql_query($query) or die('Bad SQL query deleting category'.$query);
		}
	}
}

//returns html for category list -- provides controls for loading and deleting categories
function catList()
{
	global $db;
	$out = '<form action="custom.php" method="post" class="lisep" onsubmit="return delsure();" style="float:left;">'
			. '<fieldset><legend>Field categories</legend>'
			. '<a href="custom.php?edit=-1"><img src="images/add.png" alt="add" width="14" height="14" style="border:0;"/> '
			. 'Add new</a><br/><br/>';
	$query = 'SELECT id,name FROM data_categories';
	$res = mysql_query($query) or die('Bad SQL query getting custom categories');
	$row = mysql_fetch_assoc($res);
	foreach($db->sources as $sourc=>$nam) { if($row['name'] == $nam->categoryName) {$cat_exists=true; break;} else $cat_exists=false; }  
	if($row === false)
	{
		$out .= '(None exist)';
	}else{
		$out .= '<ul>';
		do{
			if($db->user->userlevel != 'root' && $cat_exists) continue;
			$out .= '<li><a href="custom.php?edit=' . $row['id'] . '">'
					. (strlen($row['name']) ? $row['name'] : ('(category ' . $row['id'] . ')'))
					. ' <img src="images/edit.png" alt="edit" border="0"/> </a> &nbsp; '
					. ( $cat_exists ? '' :
							('<input type="image" src="images/not.png" name="cat_del[' . $row['id'] . ']" alt="Delete"/>'))
					. '</li>';
		}while($row = mysql_fetch_assoc($res));
		$out .= '</ul>';
	}
	$out .= '</fieldset></form>';
	return $out;
}

//process postdata from field editor form. Returns an array of errors if there are any.
function processEditor()
{
	global $db;
	global $types;
	global $uploadmsg;
	global $now;
	$DTnow = date('Y-m-d H:i:s',$now);
	$errors = array();
	$id = -1;
	if(!isset($_POST['id']) || !is_numeric($id = mysql_real_escape_string($_POST['id']))) return $errors;
	mysql_query('BEGIN') or die("Couldn't begin SQL transaction");
	if(isset($_POST['savecat']))
	{
		$query = 'SELECT name FROM data_categories WHERE id=' . $id . ' LIMIT 1';
		$res = mysql_query($query) or die('Bad SQL query checking for existence of indicated record');
		$row = mysql_fetch_assoc($res);
		if($row === false) $id=-1;
		foreach($db->sources as $sourc=>$nam) { if($row['name'] == $nam->categoryName) {$cat_exists=true; break;} else $cat_exists=false; }  
		if($db->user->userlevel != 'root' && $cat_exists) return;
		if($id == -1)	//new category
		{
			$query = 'INSERT INTO data_categories SET name="' . mysql_real_escape_string($_POST['name']) . '"';
			mysql_query($query) or die('Bad SQL query adding category');
			$id = mysql_insert_id();
			$_POST['id'] = $id; //this is so the edit form loads the newly made category
		}else{		//edit existing
			$query = 'UPDATE data_categories SET name="' . mysql_real_escape_string($_POST['name'])
					. '" WHERE id=' . $id . ' LIMIT 1';
			mysql_query($query) or die('Bad SQL query updating category');
			if(isset($_POST['fieldname']) && is_array($_POST['fieldname']))
			{
				foreach($_POST['fieldname'] as $fieldId => $fieldname)
				{
					$fieldId = mysql_real_escape_string($fieldId);
					if(isset($_POST['del'][$fieldId]))
					{
						$query = 'DELETE FROM data_fields WHERE id=' . $fieldId . ' LIMIT 1';
						mysql_query($query) or die('Bad SQL query removing field');
						continue;
					}
					
					$fieldname = mysql_real_escape_string($fieldname);
					$type = mysql_real_escape_string($_POST['type'][$fieldId]);
					if(!in_array($type, $types))
					{
						$errors[$fieldId] = 'Invalid type';
						continue;
					}
					/*Checking the field ID is enough to uniquely identify the record, but we also
						check the category to prevent cross-editing from invalid postdata
					*/
					$query = 'UPDATE data_fields SET name="' . $fieldname . '",type="' . $type . '" WHERE id='
								. $fieldId . ' AND category=' . $id . ' LIMIT 1';
					mysql_query($query) or die('Bad SQL query updating field');
					if($type == 'enum')
					{
						$values = explode('`',$_POST['enumvals'][$fieldId]);
						$query = 'SELECT id,value FROM data_enumvals WHERE field=' . $fieldId;
						$evres = mysql_query($query) or die('Bad SQL query getting enumvals');
						$del = array();
						$ins = array();
						$cur = array();
						while($evrow = mysql_fetch_assoc($evres))
						{
							if(!in_array($evrow['value'],$values)) $del[$evrow['id']] = $evrow['value'];
							$cur[$evrow['id']] = $evrow['value'];
						}
						foreach($values as $ev) if(!in_array($ev,$cur)) $ins[] = $ev;
						if(count($del))
						{
							$query = 'DELETE FROM data_enumvals WHERE id IN(' . implode(',',array_keys($del)) . ')';
							mysql_query($query) or die('Bad SQL query clearing old enum values');
						}
						foreach($ins as $ev)
						{
							$query = 'INSERT INTO data_enumvals SET field=' . $fieldId . ',value="'
										. mysql_real_escape_string($ev) . '"';
							mysql_query($query) or die('Bad SQL query adding enum values');
						}
					}
				}
			}
		}
		if( strlen($newname = mysql_real_escape_string($_POST['newname'])) &&
			strlen($newtype = mysql_real_escape_string($_POST['newtype'])) )
		{
			if(!in_array($newtype, $types))
			{
				$errors['new'] = 'Invalid type.';
			}else{
				$query = 'INSERT INTO data_fields SET name="' . $newname . '",type="' . $newtype . '",category=' . $id;
				mysql_query($query) or die('Bad SQL query adding field '.mysql_error());
				$fieldId = mysql_insert_id();
				if($newtype == 'enum')
				{
					$newvals = explode('`',$_POST['newvals']);
					foreach($newvals as $newval)
					{
						$query = 'INSERT INTO data_enumvals SET field=' . $fieldId . ',value="'
									. mysql_real_escape_string($newval) . '"';
						mysql_query($query) or die('Bad SQL query adding enum values');
					}
				}
			}
		}
	}
	//handle file upload
	if(isset($_FILES['cfile']) && $_FILES['cfile']['size']<=0)
	{
		$errors[] = 'Error uploading file -- check server filesize limit';
	}
	else if(isset($_FILES['cfile']) && $_FILES['cfile']['error']!=0)
	{
		$errors[] = 'Error ' . $_FILES['cfile']['error'];
	}
	else if(isset($_FILES['cfile']) && $_FILES['cfile']['size']>0)
	{
		$catd = getFields($id);
		$objPHPExcel = PHPExcel_IOFactory::load($_FILES['cfile']['tmp_name']);
		$objPHPExcel->setActiveSheetIndex(0);
		$sheet = $objPHPExcel->getActiveSheet();
		$fields = array();
		for($col = 'B'; ($fname = $sheet->getCell($col.'1')->getValue()) !== NULL; ++$col)
		{
			foreach($catd as $field)
			{
				if($field->name == $fname)
				{
					$fields[$col] = $field;
					break;
				}
			}
			if(!isset($fields[$col])) $errors[] = ('Unrecognized field: ' . htmlspecialchars($fname));
		}
		$cols = array_keys($fields);
		for($row = 2; ($nct = $sheet->getCell('A'.$row)->getValue()) !== NULL; ++$row)
		{
			//$sid = unpadnct($nct);	//used to be nct_id, now just larvol_id
			$sid = $nct;
			$query = 'SELECT id FROM data_cats_in_study WHERE category=' . $id . ' AND larvol_id=' . $sid . ' LIMIT 1';
			$res = mysql_query($query) or die('Bad SQL query getting studycat');
			$res = mysql_fetch_assoc($res);
			$studycat = NULL;
			if($res === false)
			{
				$query = 'INSERT INTO data_cats_in_study SET category=' . $id . ',larvol_id=' . $sid;
				mysql_query($query) or die('Bad SQL query adding category to study');
				$studycat = mysql_insert_id();
			}else{
				$studycat = $res['id'];
			}
			foreach($cols as $col)
			{
				$val = $sheet->getCell($col.$row)->getValue();
				$xval = mysql_real_escape_string($val);
				$query = 'DELETE FROM data_values WHERE studycat=' . $studycat . ' AND `field`=' . $fields[$col]->id;
				mysql_query($query) or die('Bad SQL query clearing old data');
				$query = 'INSERT INTO data_values SET studycat=' . $studycat . ',`added`="' . $DTnow . '",`field`='
							. $fields[$col]->id . ',val_';
				switch($fields[$col]->type)
				{
					case 'bool':
					case 'int':
					if(0 == strlen($xval)) $xval = 'NULL';
					$query .= 'int=' . $xval;
					break;
					
					case 'enum':
					if(0 == strlen($xval))
					{
						$xval = 'NULL';
					}else{
						$query2 = 'SELECT id FROM data_enumvals WHERE `field`=' . $fields[$col]->id . ' AND `value`="' . $xval . '"';
						$res = mysql_query($query2) or die('Bad SQL query getting enumval');
						$res = mysql_fetch_assoc($res);
						if($res === false)
						{
							$errors[] = 'Unrecognized enum value: ' . $val . ' in row ' . $sid . ' col ' . $col;
							$xval = 'NULL';
						}else{
							$xval = $res['id'];
						}
					}
					$query .= 'enum=' . $xval;
					break;
					
					case 'varchar':
					case 'text':
					case 'date':
					$query .= $fields[$col]->type . '="' . $xval . '"';
				}
				mysql_query($query) or die('Bad SQL query recording value');
			}
		}
		$uploadmsg = 'Uploaded file success!.';
	}
	mysql_query('COMMIT') or die("Couldn't commit SQL transaction");
	@unlink('cache/types.dat');
	return $errors;
}

//When a category is selected for editing or saving, this will return html for a field editor
function fieldEditor()
{
	global $types;
	global $editerrors;
	global $uploadmsg;
	$id = NULL;
	//Don't even show this editor when the page is first loaded or when given garbage data
	if(isset($_GET['edit']))
	{
		$id = mysql_real_escape_string($_GET['edit']);
	}else if(isset($_POST['id'])){
		$id = mysql_real_escape_string($_POST['id']);
	}
	if(!is_numeric($id)) return '';
	//the editor itself
	$out = '<form method="post" action="custom.php" style="float:left;"><fieldset><legend>';
	$query = 'SELECT name FROM data_categories WHERE id=' . $id . ' LIMIT 1';
	$res = mysql_query($query) or die('Bad SQL query getting category name');
	$row = mysql_fetch_assoc($res);
	$catName = '';
	$catd = array();
	if($row === false)	//Bad ID or new category
	{
		$out .= 'New category';
	}else{		//Load existing category
		$catName = htmlspecialchars($row['name']);
		$out .= 'Edit category';
		$catd = getFields($id);
	}
	$out .= '</legend>Category Name: <input type="text" name="name" value="' . $catName
			. '"/><input type="hidden" name="id" value="' . htmlspecialchars($id) . '"/>'
			. '<table><tr><th colspan="4">Fields</th></tr><tr><th>Delete?</th><th>Name</th><th>Type</th><th>Values</th></tr>';
	foreach($catd as $field)
	{
		$out .= '<tr><th><input type="checkbox" name="del[' . $field->id . ']"/></th><td><input type="text" name="fieldname['
				. $field->id . ']" value="' . $field->name . '"/></td><td>'
				. makeDropdown('type[' . $field->id . ']',$types,false,$field->type)
				. '</td><td><input type="text" name="enumvals[' . $field->id . ']" value="' . implode('`',$field->enumvals)
				. '"/></td></tr>';
	}
	$out .= '<tr><td>(+New)</td><td><input type="text" name="newname"/></td><td>' . makeDropdown('newtype',$types,false)
			. '</td><td><input type="text" name="newvals"/></td></tr></table><input type="submit" name="savecat" value="Save"/>'
			. '</fieldset></form>';
	//a mass data uploader
	$out .= '<form action="custom.php" method="post" enctype="multipart/form-data">'
			. '<fieldset><legend>Mass data upload for this category</legend>'
			. '<input type="file" name="cfile" /><input type="submit" name="uploadbutton" value="Submit" />'
			. '<br />' . $uploadmsg
			. '<br /><span class="info">Columns are fields, rows are Larvol IDs</span></fieldset>'
			. '<input type="hidden" name="id" value="' . htmlspecialchars($id) . '"/></form>';
	$out .= '<span class="error">' . implode('<br />', $editerrors) . '</span>';
	return $out;
}

function getFields($id)
{
	$catd = array();
	$query = 'SELECT id,name,type FROM data_fields WHERE category=' . $id;
	$res = mysql_query($query) or die('Bad SQL query getting fields in category');
	while($row = mysql_fetch_assoc($res))
	{
		$enumvals = array();
		if($row['type'] == 'enum')
		{
			$query2 = 'SELECT id,value FROM data_enumvals WHERE field=' . $row['id'];
			$res2 = mysql_query($query2) or die('Bad SQL query getting enumvals');
			while($row2 = mysql_fetch_assoc($res2))
			{
				$enumvals[$row2['id']] = htmlspecialchars($row2['value']);
			}
		}
		$catd[] = new CustomField(htmlspecialchars($row['id']), htmlspecialchars($row['name']),
									htmlspecialchars($row['type']),$enumvals);
	}
	return $catd;
}
?>