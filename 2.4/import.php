<?php
require_once('db.php');
require_once('include.import.php');
require_once('class.zip.php');
require_once('krumo/class.krumo.php');
if(!$db->loggedIn())
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
ini_set('memory_limit','-1');
ini_set('max_execution_time','36000');	//10 hours
require('header.php');
$corrupt = array();
$msg = '';
echo(fileform());
if(count($corrupt))
{
	echo('<div class="info" id="error" '
			. 'style="float:left;text-align:left;clear:both;margin-left:10px;color:#F00;">'
			. 'Warning: ' . count($corrupt) . ' files had errors. Click the array below to read them.</div>');
	echo('<br clear="all"/><div id="krumo">');
	krumo($corrupt);
	$msg = array($msg);
	krumo($msg);
	echo('</div>');
}
echo('</body></html>');

//returns HTML for the file input form and handles its own form submits
function fileform()
{
	global $db;
	global $corrupt;
	global $msg;
	$form = '<script type="text/javascript" src="progress/progress.js"></script>'
				. '<form action="import.php" method="post" enctype="multipart/form-data" name="importform" '
				. 'style="text-align:left;" onsubmit="updateProgress()">'
				. 'uploaded files must be .xml -- import multiple files by uploading a .zip containing them<br />'
				. '<input type="file" name="xmlinput" /><br /><input type="submit" name="submit" value="Send" /></form>'
				. '<br clear="all" /><div id="progress"></div>';
	mysql_query('DELETE FROM progress WHERE what="parse" AND user=' . $db->user->id
				. ' AND lastUpdate < DATE_SUB(NOW(),INTERVAL 1 MINUTE)');
	$res = mysql_query('SELECT progress,max FROM progress WHERE what="parse" AND user=' . $db->user->id . ' LIMIT 1');
	$res = mysql_fetch_assoc($res);
	if($res !== false)
	{
		echo('<span class="error">Warning: Same action already in progress ('
				. $res['progress'] . ' / ' . $res['max'] . ')</span><br clear="all"/>');
	}
	
	if(isset($_FILES['xmlinput']) && $_FILES['xmlinput']['size']<=0)
	{
		$form .= '<div class="error" style="float:left;text-align:left;clear:both;margin-left:10px;color:#F00;">'
					. 'Error uploading file -- check server filesize limit</div>';
	}
	else if(isset($_FILES['xmlinput']) && $_FILES['xmlinput']['error']!=0)
	{
		$form .= '<div class="error" style="float:left;text-align:left;clear:both;margin-left:10px;color:#F00;">'
					. 'Error ' . $_FILES['xmlinput']['error'] . '</div>';
	}
	else if(isset($_FILES['xmlinput']) && $_FILES['xmlinput']['size']>0)
	{
		$added = 0;
		$ext = strtolower(substr($_FILES['xmlinput']['name'],-3));
		if($ext == 'zip')
		{
			$zipfile = new zipfile();
			$xmlfiles = $zipfile->read_zip($_FILES['xmlinput']['tmp_name']);
			$entries = count($xmlfiles);
			mysql_query('INSERT INTO progress SET created=NOW(),user=' . $db->user->id . ',what="parse",max=' . $entries);
			$pid = mysql_insert_id();
			ob_start();
			foreach($xmlfiles as $zip_entry)
			{
				if($zip_entry['error']=='')
				{
					$xml = $zip_entry['data'];
					if($xml !== false)
					{
						$xml = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOWARNING|LIBXML_NOERROR);
						if($xml !== false)
						{
							++$added;
							$query = 'UPDATE progress SET progress=' . $added . ',note=3 WHERE id=' . $pid . ' LIMIT 1';
							mysql_query($query) or die('Error updating progress!<br />'.mysql_error().'<br />'.$query);
							if(!addRecord($xml))
							{
								$corrupt[] = 'Data error.';
								--$added;
							}
						}else{
							$corrupt[] = 'Failed to parse XML';
						}
					}else{
						$corrupt[] = 'Failed to decode zip entry';
					}
				}else{
					if(isset($zip_entry['name'])) $corrupt[$zip_entry['name']] = $zip_entry['error'];
					else $corrupt[] = $zip_entry['error'];
				}
			}
			$ob = ob_get_contents();
			if(strlen($ob)) $msg = $ob;
			ob_end_clean();
			mysql_query('DELETE FROM progress WHERE id=' . $pid . ' LIMIT 1');
		}else if($ext == 'xml'){
			$xml = file_get_contents($_FILES['xmlinput']['tmp_name']);
			$xml = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOWARNING|LIBXML_NOERROR);
			if($xml !== false)
			{
				++$added;
				mysql_query('INSERT INTO progress SET created=NOW(),user=' . $db->user->id . ',what="parse",max=1,progress=1');
				$pid = mysql_insert_id();
				ob_start();
				if(!addRecord($xml))
				{
					$corrupt[] = 'Data error.';
					--$added;
				}
				$ob = ob_get_contents();
				if(strlen($ob)) $msg = $ob;
				ob_end_clean();
				mysql_query('DELETE FROM progress WHERE id=' . $pid . ' LIMIT 1');
			}
		}else{
			return $form.'<div class="error" style="float:left;text-align:left;clear:both;margin-left:10px;color:#F00;">'
					. 'Invalid file type</div>';
		}
		$form .= '<div class="info" id="success" style="float:left;text-align:left;clear:both;margin-left:10px;">'
					. 'Uploaded file success! ' . $added . ' record(s) added.</div>';
	}else{
		$form .= '<div class="info" id="success"></div>';
	}
	return $form;
}
?>