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
?>

<div class='page_help_block'>
<a class='page_help_text' href="http://www.commentics.org/wiki/doku.php?id=admin:<?php echo $_GET['page']; ?>" target="_blank"><?php echo CMTX_LINK_HELP; ?></a>
</div>

<h3><?php echo CMTX_TITLE_ADMIN_DETECTION; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['notice']) && $_GET['notice'] == "dismiss" && cmtx_check_csrf_url_key()) {
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '0' WHERE `title` = 'notice_settings_admin_detection'");
} else {
if (cmtx_setting('notice_settings_admin_detection')) { ?>
<div class="info"><?php echo CMTX_MSG_NOTICE_SETTINGS_ADMIN_DETECTION . " <a href='index.php?page=settings_admin_detection&notice=dismiss&key=" . $_SESSION['cmtx_csrf_key'] . "'>" . CMTX_LINK_DISMISS . "</a>"; ?></div>
<div style="clear: left;"></div>
<?php } } ?>

<?php
$admin_id = cmtx_get_admin_id();
?>

<?php
$detection = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `id` = '$admin_id'");
$detection = mysql_fetch_assoc($detection);
$ip_address = $detection["ip_address"];
if ($ip_address != cmtx_get_ip_address() && !cmtx_setting('is_demo')) {
	mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `ip_address` = '". cmtx_get_ip_address() . "' WHERE `id` = '$admin_id'");
	?>
	<div class="info"><?php echo CMTX_MSG_IP_ADDRESS_UPDATED; ?></div>
	<div style="clear: left;"></div>
	<?php
}
?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['detect_admin'])) { $detect_admin = 1; } else { $detect_admin = 0; }
$detect_method = $_POST['detect_methods'];
$ip_address = $_POST['ip_address'];
$cookie_key = $_POST['cookie_key'];

if (isset($_POST['add_cookie'])) {
@setcookie("Commentics-Admin", $cookie_key, 60*60*24*cmtx_setting('admin_cookie_days') + time(), '/');
}

if (isset($_POST['remove_cookie'])) {
@setcookie("Commentics-Admin", "", time() - 3600, '/');
}

$detect_method_san = cmtx_sanitize($detect_method);
$ip_address_san = cmtx_sanitize($ip_address);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `detect_admin` = '$detect_admin' WHERE `id` = '$admin_id'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `detect_method` = '$detect_method_san' WHERE `id` = '$admin_id'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `ip_address` = '$ip_address_san' WHERE `id` = '$admin_id'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
$detection = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `id` = '$admin_id'");
$detection = mysql_fetch_assoc($detection);
$detect_admin = $detection["detect_admin"];
$detect_method = $detection["detect_method"];
$ip_address = $detection["ip_address"];
$cookie_key = $detection["cookie_key"];
?>

<p />

<?php echo CMTX_DESC_SETTINGS_ADMIN_DETECTION; ?>

<p />

<form name="settings_admin_detection" id="settings_admin_detection" action="index.php?page=settings_admin_detection" method="post">
<label class='settings_admin_detection'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if ($detect_admin) { ?> <input type="checkbox" checked="checked" name="detect_admin"/> <?php } else { ?> <input type="checkbox" name="detect_admin"/> <?php } ?>
<p />
<label class='settings_admin_detection'><?php echo CMTX_FIELD_LABEL_METHOD; ?></label>
<?php
$detect_methods = "<select name='detect_methods'>
<option value='ip_address'>" . rtrim(CMTX_FIELD_LABEL_IP_ADDRESS, ':') . "</option>
<option value='cookie'>" . CMTX_FIELD_VALUE_COOKIE . "</option>
<option value='either'>" . CMTX_FIELD_VALUE_EITHER . "</option>
<option value='both'>" . CMTX_FIELD_VALUE_BOTH . "</option>
</select>";
$detect_methods = str_ireplace("'".$detect_method."'", "'".$detect_method."' selected='selected'", $detect_methods);
echo $detect_methods;
?>
<p />
<label class='settings_admin_detection'><?php echo CMTX_FIELD_LABEL_IP_ADDRESS; ?></label> <input type="text" required name="ip_address" size="12" maxlength="39" value="<?php echo $ip_address; ?>"/>
<p />
<input type="hidden" name="cookie_key" value="<?php echo $cookie_key; ?>"/>
<?php if (isset($_POST['remove_cookie'])) { ?>
<label class='settings_admin_detection'><?php echo CMTX_FIELD_LABEL_ADD_COOKIE; ?></label> <input type="checkbox" name="add_cookie"/>
<?php } else if ( (isset($_COOKIE['Commentics-Admin']) && $_COOKIE['Commentics-Admin'] == $cookie_key) || (isset($_POST['add_cookie'])) ) { ?>
<label class='settings_admin_detection'><?php echo CMTX_FIELD_LABEL_DEL_COOKIE; ?></label> <input type="checkbox" name="remove_cookie"/>
<?php } else { ?>
<label class='settings_admin_detection'><?php echo CMTX_FIELD_LABEL_ADD_COOKIE; ?></label> <input type="checkbox" name="add_cookie"/>
<?php } ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>