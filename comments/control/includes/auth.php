<?php
/*
Copyright © 2009-2013 Commentics Development Team [commentics.org]
License: GNU General Public License v3.0
		 http://www.commentics.org/license/

This file is part of Commentics.

Commentics is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Commentics is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Commentics. If not, see <http://www.gnu.org/licenses/>.

Text to help preserve UTF-8 file encoding: 汉语漢語.
*/

if (!defined('IN_COMMENTICS')) { die('Access Denied.'); }

if (!isset($_SESSION['cmtx_username']) && !isset($_SESSION['cmtx_password']) && isset($_POST['username']) && isset($_POST['password'])) {

	cmtx_check_attempts();

	$account = cmtx_valid_account($_POST['username'], md5($_POST['password']));

	if ($account == "1") { //Disabled
	
		header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?action=disabled");
		die();
	
	} else if ($account == "2") { //Locked

		header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?action=locked");
		die();
	
	} else if ($account == "3") { //Okay
	
		session_regenerate_id(true);
		$_SESSION['cmtx_username'] = $_POST['username'];
		$_SESSION['cmtx_password'] = md5($_POST['password']);
		$_SESSION['cmtx_csrf_key'] = cmtx_get_random_key(20);
		$_SESSION['cmtx_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$_SESSION['cmtx_user_lang'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$_SESSION['cmtx_ip_address'] = cmtx_get_ip_address();
		session_write_close();
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `last_login` = NOW() WHERE `id` = '" . cmtx_get_admin_id() . "'");
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `resets` = '0' WHERE `id` = '" . cmtx_get_admin_id() . "'");
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `login_attempts` = '0' WHERE `id` = '" . cmtx_get_admin_id() . "'");
		cmtx_delete_attempts();
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "logins` SET `dated` = NOW() ORDER BY `dated` ASC LIMIT 1");
		header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?page=dashboard");
		die();
		
	} else { //Wrong
		
		if (!cmtx_setting('is_demo')) {
			cmtx_add_attempt();
		}
		
		header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?action=attempt");
		die();
		
	}

} else if (isset($_SESSION['cmtx_username']) && isset($_SESSION['cmtx_password']) && cmtx_valid_account($_SESSION['cmtx_username'], $_SESSION['cmtx_password']) == "3") {

	//currently logged in, no action required.
	
	//verify user-agent
	if ($_SESSION['cmtx_user_agent'] != $_SERVER['HTTP_USER_AGENT']) {
		cmtx_log_out("exit");
	}
	
	//verify user-language
	if ($_SESSION['cmtx_user_lang'] != $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
		cmtx_log_out("exit");
	}
	
	//verify ip-address
	if ($_SESSION['cmtx_ip_address'] != cmtx_get_ip_address()) {
		cmtx_log_out("exit");
	}

} else if (isset($_SESSION['cmtx_username']) && isset($_SESSION['cmtx_password']) && cmtx_valid_account($_SESSION['cmtx_username'], $_SESSION['cmtx_password']) != "3") {

	//logged in, but shouldn't be

	cmtx_log_out("exit");
	
} else if (isset($_GET['page']) && $_GET['page'] == "reset") {

	?>
	<!DOCTYPE html>
	<html>
	<head>
	<title>Commentics: Reset</title>
	<meta name="robots" content="noindex"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="css/login.css"/>
	<link rel="stylesheet" type="text/css" href="css/general.css"/>
	</head>
	<body>
	<div style="position:absolute; height:auto; width:auto; margin-top:-70px; margin-left:-120px; top:50%; left:50%;">
	<form name="reset" id="reset" action="index.php?page=reset" method="post">
	<fieldset style="width:auto;">
	<legend><?php echo CMTX_RESET_FIELDSET; ?></legend>
	<?php echo CMTX_RESET_EMAIL; ?> <input type="email" required autofocus name="email"/>
	<br />
	<div style="margin-bottom:5px;"></div>
	<input class='button' type='submit' title='<?php echo CMTX_RESET_BUTTON; ?>' value='<?php echo CMTX_RESET_BUTTON; ?>'/>
	</fieldset>
	</form>
	<div style="text-align:center; margin-top:10px;">
	<?php
	if (isset($_POST['email'])) {
	
		if (cmtx_setting('is_demo')) {
		
			echo "<span class='negative'>" . CMTX_RESET_DEMO . "</span><p />";
		
		} else {

			$email = cmtx_sanitize($_POST['email']);

			if (mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `email` = '$email'"))) {
			
				$admin_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `email` = '$email'");
				$admin_result = mysql_fetch_assoc($admin_query);
				
				$resets = $admin_result["resets"];
				
				if ($resets >= 5) {
					echo "<span class='negative'>" . CMTX_RESET_LIMIT . "</span><p />";
				} else {
				
					$resets++;
					mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `resets` = '$resets' WHERE `email` = '$email'");
				
					$username = $admin_result["username"];
				
					$password = cmtx_get_random_key(10);
					
					$reset_password_email_file = "../includes/emails/" . cmtx_setting('language_frontend') . "/admin/reset_password.txt"; //build path to reset password email file
					$body = file_get_contents($reset_password_email_file); //get the file's contents
					
					$admin_link = cmtx_url_encode_spaces(cmtx_setting('url_to_comments_folder') . cmtx_setting('admin_folder')) . "/"; //build admin panel link
					
					//convert email variables with actual variables
					$body = str_ireplace('[username]', $username, $body);
					$body = str_ireplace('[password]', $password, $body);
					$body = str_ireplace('[admin link]', $admin_link, $body);
					$body = str_ireplace('[signature]', cmtx_setting('signature'), $body);
					
					//send email
					cmtx_email($email, null, cmtx_setting('admin_reset_password_subject'), $body, cmtx_setting('admin_reset_password_from_email'), cmtx_setting('admin_reset_password_from_name'), cmtx_setting('admin_reset_password_reply_to'));
					
					$password = md5($password);
					$password = cmtx_sanitize($password);
					
					mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `password` = '$password' WHERE `email` = '$email'");
				
					echo "<span class='positive'>" . CMTX_RESET_SENT . "</span>";
				
				}
				
			} else {
			
				echo "<span class='negative'>" . CMTX_RESET_ADDR . "</span>";
				
			}
		
		}
	
	}
	?>
	</div>
	<div style="text-align:center; margin-top:10px;">
	<span class="login_link"><a href="index.php" title="<?php echo CMTX_RESET_LOGIN; ?>"><?php echo CMTX_RESET_LOGIN; ?></a></span>
	</div>
	</div>
	</body>
	</html>
	<?php
	die();

} else {

	?>
	<!DOCTYPE html>
	<html>
	<head>
	<title>Commentics: Login</title>
	<meta name="robots" content="noindex"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link rel="stylesheet" type="text/css" href="css/login.css"/>
	<link rel="stylesheet" type="text/css" href="css/general.css"/>
	</head>
	<body>
	<div style="position:absolute; height:auto; width:auto; margin-top:-70px; margin-left:-120px; top:50%; left:50%;">
	<form name="login" id="login" action="index.php?page=dashboard" method="post">
	<fieldset style="width:auto;">
	<legend><?php echo CMTX_LOGIN_FIELDSET; ?></legend>
	<label style='float:left; width:70px;'><?php echo CMTX_LOGIN_USERNAME; ?></label> <input type="text" required autofocus name="username" style="width:150px"/>
	<br />
	<label style='float:left; width:70px;'><?php echo CMTX_LOGIN_PASSWORD; ?></label> <input type="password" required name="password" style="width:150px"/>
	<br />
	<div style="margin-bottom:5px;"></div>
	<input class='button' type='submit' title='<?php echo CMTX_LOGIN_BUTTON; ?>' value='<?php echo CMTX_LOGIN_BUTTON; ?>'/>
	</fieldset>
	</form>
	<div style="text-align:center; margin-top:10px;">
	<?php
	if (isset($_GET['action'])) {
		if ($_GET['action'] == "disabled") {
			echo "<span class='negative'>" . CMTX_LOGIN_DISABLED . "</span>";
		} if ($_GET['action'] == "locked") {
			echo "<span class='negative'>" . CMTX_LOGIN_LOCKED . "</span>";
		} else if ($_GET['action'] == "attempt") {
			echo "<span class='negative'>" . CMTX_LOGIN_ATTEMPT . "</span>";
		} else if ($_GET['action'] == "failed") {
			echo "<span class='negative'>" . CMTX_LOGIN_FAILED . "</span>";
		} else if ($_GET['action'] == "logout") {
			echo "<span class='positive'>" . CMTX_LOGIN_LOGOUT . "</span>";
		} else if ($_GET['action'] == "exit") {
			echo "<span class='negative'>" . CMTX_LOGIN_EXIT . "</span>";
		}
	}
	?>
	</div>
	<div style="text-align:center; margin-top:10px;">
	<span class="reset_link"><a href="index.php?page=reset" title="<?php echo CMTX_LOGIN_RESET; ?>"><?php echo CMTX_LOGIN_RESET; ?></a></span>
	</div>
	</div>
	</body>
	</html>
	<?php
	die();

}
?>