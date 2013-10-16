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

<h3><?php echo CMTX_TITLE_EMAIL_SENDER; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['notice']) && $_GET['notice'] == "dismiss" && cmtx_check_csrf_url_key()) {
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '0' WHERE `title` = 'notice_settings_email_sender'");
} else {
if (cmtx_setting('notice_settings_email_sender')) { ?>
<div class="info"><?php echo CMTX_MSG_NOTICE_SETTINGS_EMAIL_SENDER . " <a href='index.php?page=settings_email_sender&notice=dismiss&key=" . $_SESSION['cmtx_csrf_key'] . "'>" . CMTX_LINK_DISMISS . "</a>"; ?></div>
<div style="clear: left;"></div>
<?php } } ?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$setup_from_name = $_POST['setup_from_name'];
$setup_from_email = $_POST['setup_from_email'];
$setup_reply_to = $_POST['setup_reply_to'];

$setup_from_name_san = cmtx_sanitize($setup_from_name);
$setup_from_email_san = cmtx_sanitize($setup_from_email);
$setup_reply_to_san = cmtx_sanitize($setup_reply_to);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'setup_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'setup_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'setup_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'subscriber_confirmation_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'subscriber_confirmation_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'subscriber_confirmation_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'subscriber_notification_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'subscriber_notification_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'subscriber_notification_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'admin_email_test_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'admin_email_test_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'admin_email_test_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'admin_new_ban_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'admin_new_ban_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'admin_new_ban_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'admin_new_comment_approve_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'admin_new_comment_approve_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'admin_new_comment_approve_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'admin_new_comment_okay_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'admin_new_comment_okay_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'admin_new_comment_okay_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'admin_new_flag_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'admin_new_flag_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'admin_new_flag_reply_to'");

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_name_san' WHERE `title` = 'admin_reset_password_from_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_from_email_san' WHERE `title` = 'admin_reset_password_from_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$setup_reply_to_san' WHERE `title` = 'admin_reset_password_reply_to'");

?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_SETTINGS_EMAIL_SENDER; ?>

<p />

<form name="settings_email_sender" id="settings_email_sender" action="index.php?page=settings_email_sender" method="post">
<label class='settings_email_sender'><?php echo CMTX_FIELD_LABEL_FROM_NAME; ?></label> <input type="text" required name="setup_from_name" size="20" maxlength="250" value="<?php echo cmtx_setting('setup_from_name'); ?>"/>
<p />
<label class='settings_email_sender'><?php echo CMTX_FIELD_LABEL_FROM_EMAIL; ?></label> <input type="email" required name="setup_from_email" size="35" maxlength="250" value="<?php echo cmtx_setting('setup_from_email'); ?>"/>
<p />
<label class='settings_email_sender'><?php echo CMTX_FIELD_LABEL_REPLY_EMAIL; ?></label> <input type="email" required name="setup_reply_to" size="35" maxlength="250" value="<?php echo cmtx_setting('setup_reply_to'); ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>

<a href="index.php?page=settings_email_setup"><?php echo CMTX_LINK_BACK; ?></a>