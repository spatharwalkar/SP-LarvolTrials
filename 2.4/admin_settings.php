<?php
require_once('db.php');
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');

echo(settingsControl());
	
echo('</body></html>');


	
//returns HTML for settings management. Also handles its own form submits
function settingsControl()
{
	global $db;
	global $logger;
	
	if(! mysql_query('BEGIN'))
	{
		$log = "Couldn't begin SQL transaction for settings mgmt";
		$logger->fatal($log);
		die($log);
	}

	if(isset($_POST['settingsedit_save']))
	{
		foreach($_POST['settingsedit_settings'] as $settings => $apstr)
		{
						
			 $settings = mysql_real_escape_string($settings);
			 $value = mysql_real_escape_string($_POST['settingsedit_settings'][$settings]);
						
			if(trim($value)!='')
			{
				$query = 'UPDATE settings SET value="' . $value . '" WHERE name="'. $settings . '" LIMIT 1';
				if(!mysql_query($query))
				{
					$log = 'Bad SQL query for saving edits to settings';
					$logger->fatal($log);
					die($log);
				}
				$db->set[$settings] = $value;
			}
		}
					
	}
	
	$out = '<form name="settingsform" method="post" action="admin_settings.php"><fieldset><legend>Settings Management</legend>';
	if(isset($res) && $res === false)
	{
		$out .= 'No Settings.';
	}else{
		$out .= '<table><tr><th>Name</th><th>Value</th></tr>';
		foreach($db->set as $name => $value)
		{
					$out .= '<tr><td>' . $name . '</td><td><input type="text" name="settingsedit_settings[' . $name . ']" value="'. htmlspecialchars($value) . '" />'
					. '</td></tr>';
		}
		$out .='<tr><td colspan="2"><input type="submit" name="settingsedit_save" value="Save edits" /></td></tr>';
		$out .= '</table>';
	}
	if(!mysql_query('COMMIT'))
	{
		$log = "Couldn't commit SQL transaction for settings mgmt";
		$logger->fatal($log);
		die($log);
	}
	return $out . '</fieldset></form>';
}


?>