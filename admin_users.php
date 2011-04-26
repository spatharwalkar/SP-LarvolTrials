<?php
require_once('db.php');
if(!$db->loggedIn() || ($db->user->userlevel!='admin' && $db->user->userlevel!='root'))
{
	header('Location: ' . urlPath() . 'index.php');
	exit;
}
require('header.php');
$levels = getEnumValues('users','userlevel');

echo(userAdder());
echo(userControl());
echo('</body></html>');

//returns HTML for adding users. Also handles its own form submits
function userAdder()
{
	global $db;
	$error = array();
	$result = array();
	$username='';
	$email='';
	$userlevel='';
	if(isset($_POST['submit']))
	{
		$result = $db->register($_POST['username'], $_POST['email'], $_POST['userlevel']);
		if(is_array($result))
		{
			$error = $result;
			$username = htmlspecialchars($_POST['username']);
			$email = htmlspecialchars($_POST['email']);
			$userlevel = $_POST['userlevel'];
		}else{
			if($result !== true)
			{
				$error['userlevel'] = 'User created but password email not sent. Password is ' . $result;
			}
		}
	}
	
	$out = '<form action="admin_users.php" method="post" name="newuser" style="float:right;"><fieldset>'
			. '<legend>Create new account</legend>'
			. '<label>Username:<br /><input name="username" type="text" value="' . $username . '" />'
			. '<span class="error">' . (!empty($error['username']) ? $error['username'] : '') . '</span></label><br clear="all"/>'
			. '<label>Email address:<br /><input name="email" type="text" value="' . $email . '" />'
			. '<span class="error">' . (!empty($error['email']) ? $error['email'] : '') . '</span></label><br clear="all"/>'
			. '<label>Userlevel:<br />' . userlevelDropdown(-1,$userlevel)
			. '<span class="error">' . (!empty($error['userlevel']) ? $error['userlevel'] : '') . '</span></label><br clear="all"/>'
			. '<input type="submit" name="submit" value="Submit" /></fieldset></form>';
	return $out;
}

//returns HTML for user management. Also handles its own form submits
function userControl()
{
	global $db;
	global $levels;
	mysql_query('BEGIN') or die("Couldn't begin SQL transaction for user mgmt");

	if(isset($_POST['useredit_save']) && is_array($_POST['useredit_save']))
	{
		foreach($_POST['useredit_save'] as $id => $apstr)
		{
			$id = mysql_real_escape_string($id);
			if(isset($_POST['useredit_email'][$id])){
			      $email = mysql_real_escape_string($_POST['useredit_email'][$id]);
			 }
		     else{
			    $email = "";
			   }
			if(isset($_POST['useredit_userlevel'][$id])){
			    $userlevel = $_POST['useredit_userlevel'][$id];
			}
			else
			{
			  $userlevel = "";
			
			}
			if(!in_array($userlevel,$levels))
			{
				$userlevel = '';
			}else{
				$userlevel = ',userlevel="' . mysql_real_escape_string($userlevel) . '"';
			}
			
			if(is_numeric($id) && filter_var($email, FILTER_VALIDATE_EMAIL))
			{
				//prevent non-root users from modifying root users
				$no_root = ($db->user->userlevel == 'root') ? '' : ' AND userlevel!="root"';
				$query = 'UPDATE users SET email="' . $email . '"' . $userlevel . ' WHERE id='
							. $id . $no_root . ' LIMIT 1';
				mysql_query('BEGIN') or die("Couldn't begin transaction");
				mysql_query($query) or die('Bad SQL query for saving edits to user');
				mysql_query('DELETE FROM user_grants WHERE `user`=' . $db->user->id) or die('Bad SQL query editing permissions');
				$query = array();
				foreach($_POST['grant'][$id] as $pname => $plid)
					if(is_numeric($plid))
						$query[] = '(' . $id . ',' . ((int)$plid) . ')';
				if(!empty($query))
				{
					$query = 'INSERT INTO user_grants (`user`,`permission`) VALUES ' . implode(',',$query);
					mysql_query($query) or die('Bad SQL query saving permissions'.$query);
				}
				mysql_query('COMMIT') or die("Couldn't commit transaction");
			}
			if(isset($_POST['useredit_del'][$id]))
			{
				$no_root = ' AND userlevel!="root"';
				$query = 'DELETE FROM users WHERE id=' . $id . $no_root . ' LIMIT 1';
				mysql_query($query) or die('Bad SQL query for saving edits to user');
			}
			break;
		}
	}
	
	$out = '<form name="userform" method="post" action="admin_users.php"><fieldset><legend>User Management</legend>';
	$query = 'SELECT id,username,email,userlevel FROM users ORDER BY userlevel DESC,id';
	$res = mysql_query($query) or die('Bad SQL query getting user details');
	if($res === false)
	{
		$out .= 'No users.';
	}else{
		$query2 = 'SELECT * FROM user_permissions ORDER BY `id`';
		$res2 = mysql_query($query2) or die('Bad SQL query getting permissions');
		$pm = array();
		while($row2 = mysql_fetch_assoc($res2))
		{
			if(!isset($pm[$row2['name']])) $pm[$row2['name']] = array();
			$pm[$row2['name']][$row2['level']] = $row2['id'] . '.' . $row2['type'];
		}
		
		$typeColors = array('readonly'=>'1C00EE', 'contained'=>'009FB4', 'editing'=>'00D60A', 'admin'=>'E67300', 'core'=>'FF0000');
		$out .= '<table><tr><th>id</th><th>Username</th><th>email</th><th>userlevel</th><th>permissions</th>'
				. '<th>Delete</th><th>Save</th></tr>';
		while($row = mysql_fetch_assoc($res))
		{
			$query3 = 'SELECT user_permissions.`name` AS "name",MAX(`level`) AS "level" '
						. 'FROM user_grants LEFT JOIN user_permissions ON user_grants.permission=user_permissions.id '
						. 'WHERE user_grants.`user`=' . $row['id'] . ' GROUP BY user_permissions.`name`';
			$res3 = mysql_query($query3) or die("Bad SQL query getting user's permissions");
			$userp = array();
			while($row3 = mysql_fetch_assoc($res3))
			{
				$userp[$row3['name']] = $row3['level'];
			}
			$isRoot = ($row['userlevel'] == 'root');
			$condis = ($isRoot && ($db->user->userlevel != 'root')) ? 'disabled="disabled" ' : '';
			$out .= '<tr><td>' . $row['id'] . '</td><td>' . htmlspecialchars($row['username'])
					. '</td><td><input type="text" name="useredit_email[' . $row['id'] . ']" ' . $condis . 'value="'
					. htmlspecialchars($row['email']) . '" /></td><td>' . userlevelDropdown($row['id'],$row['userlevel']) . '</td>'
					. '<td class="permSel"> (Mouseover)<br /><br clear="all"/>';
			foreach($pm as $name => $levelList)
			{
				if(!isset($userp[$name])) $userp[$name] = 0;
				$out .= $name . '<select name="grant[' . $row['id'] . '][' . $name . ']">'
						. '<option value="NULL"' . ($userp[$name] == 0 ? ' selected="selected"' : '') . '>0</option>';
				foreach($levelList as $level => $data)
				{
					$data = explode('.', $data);
					$id = $data[0]; $type = $data[1];
					$out .= '<option value="' . $id . '" style="color:#' . $typeColors[$type] . ';"'
							. ($userp[$name] == $level ? ' selected="selected"' : '') . '>' . $level . '</option>';
				}
				$out .= '</select><br clear="all"/>';
			}
			$out .= '</td>'
					//. '<td>' . ($isRoot?'&nbsp;':'<input type="submit" name="useredit_del['.$row['id'].']" value="X" />') . '</td>'
					. '<td>'.($isRoot?'&nbsp;':'<input type="checkbox" name="useredit_del['.$row['id'].']" />').'</td>'
					. '<td><input type="submit" name="useredit_save[' . $row['id'] . ']" value="Save" /></td>'
					. '</tr>';
		}
		$out .= '</table>';
	}
	mysql_query('COMMIT') or die("Couldn't commit SQL transaction for user mgmt");
	return $out . '</fieldset></form>';
}

function userlevelDropdown($id,$current)
{
	global $levels;
	$isRoot = ($current=='root');
	$availableLevels = $isRoot ? $levels : array_filter($levels,'notRoot');
	if($id == -1)
	{
		$out = '<select name="userlevel">';
	}else{
		$out = '<select name="useredit_userlevel[' . $id . ']" ' . ($isRoot?'disabled="disabled"':'') . '>';
	}
	foreach($availableLevels as $level)
	{
		$out .= '<option value="' . $level . '" ' . ($level==$current?'selected="selected"':'') . '>' . $level . '</option>';
	}
	return $out . '</select>';
}

function notRoot($check)
{
	return $check!='root';
}

?>