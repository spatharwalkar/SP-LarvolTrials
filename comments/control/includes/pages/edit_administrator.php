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

<h3><?php echo CMTX_TITLE_EDIT_ADMIN; ?></h3>
<hr class="title"/>

<?php
$admin_id = cmtx_get_admin_id();
if (mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `is_super` = '1' AND `id` = '$admin_id'")) == 0) {
die("<p />" . CMTX_MSG_ADMIN_SUPER_ONLY);
}
?>

<?php
if (isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "admins")) {
} else { ?>
<div class="error"><?php echo CMTX_MSG_RECORD_MISSING; ?></div>
<div style="clear: left;"></div>
<div style='margin-top: 10px;'>
<a href="index.php?page=manage_administrators"><?php echo CMTX_LINK_BACK; ?></a>
<?php
die();
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

$id = $_GET['id'];
$username = $_POST['username'];
if (!empty($_POST['password_1'])) { $password = md5($_POST['password_1']); }
$email = $_POST['email'];
$is_enabled = $_POST['enabled'];
if (isset($_POST['restrict_pages'])) { $restrict_pages = 1; } else { $restrict_pages = 0; }
if (isset($_POST['allowed_pages'])) { $allowed_pages = implode(",", $_POST['allowed_pages']); } else { $allowed_pages = ""; }

$allowed_pages = str_ireplace("edit_comment", "edit_comment,spam", $allowed_pages);

$allowed_pages = str_ireplace("settings_email_setup", "settings_email_setup,settings_email_method,settings_email_sender,settings_email_signature,settings_email_test", $allowed_pages);

$id_san = cmtx_sanitize($id);
$username_san = cmtx_sanitize($username);
if (!empty($_POST['password_1'])) { $password_san = cmtx_sanitize($password); }
$email_san = cmtx_sanitize($email);
$is_enabled_san = cmtx_sanitize($is_enabled);
$allowed_pages_san = cmtx_sanitize($allowed_pages);

if (mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `username` = '$username_san' AND `id` != '$id_san'"))) {

?>
<div class="error"><?php echo CMTX_MSG_ADMIN_EXISTS; ?></div>
<div style="clear: left;"></div>
<?php

} else if ((!$is_enabled) && mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `is_super` = '1' AND `id` = '$id_san'"))) {

?>
<div class="error"><?php echo CMTX_MSG_ADMIN_SUPER_DISABLE; ?></div>
<div style="clear: left;"></div>
<?php

} else if (($restrict_pages) && mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `is_super` = '1' AND `id` = '$id_san'"))) {

?>
<div class="error"><?php echo CMTX_MSG_ADMIN_SUPER_RESTRICT; ?></div>
<div style="clear: left;"></div>
<?php

} else {

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `username` = '$username_san' WHERE `id` = '$id_san'");
if (!empty($_POST['password_1'])) { mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `password` = '$password_san' WHERE `id` = '$id_san'"); }
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `email` = '$email_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `is_enabled` = '$is_enabled_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `restrict_pages` = '$restrict_pages' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "admins` SET `allowed_pages` = '$allowed_pages_san' WHERE `id` = '$id_san'");

?>
<div class="success"><?php echo CMTX_MSG_ADMIN_UPDATED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php } ?>

<?php
$id = $_GET['id'];
$id_san = cmtx_sanitize($id);
$administrators = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `id` = '$id_san'");
$administrator = mysql_fetch_assoc($administrators);
$username = $administrator["username"];
$email = $administrator["email"];
$enabled = $administrator["is_enabled"];
$restrict_pages = $administrator["restrict_pages"];
$allowed_pages = $administrator["allowed_pages"];
$time = date("g:ia", strtotime($administrator["dated"]));
$date = date("jS M Y", strtotime($administrator["dated"]));
?>

<p />

<form name="administrator" id="administrator" action="index.php?page=edit_administrator&id=<?php echo $id ?>" method="post" onsubmit="return check_passwords()";>
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_USERNAME; ?></label> <input type="text" required name="username" size="12" maxlength="250" value="<?php echo cmtx_sanitize($username, true, false); ?>"/>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_NEW_PASSWORD; ?></label> <input type="password" name="password_1" size="20" maxlength="250"/>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_REPEAT_PASSWORD; ?></label> <input type="password" name="password_2" size="20" maxlength="250"/>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_EMAIL_ADDRESS; ?></label> <input type="email" required name="email" size="30" maxlength="250" value="<?php echo $email; ?>"/>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label>
<?php if ($enabled) { ?>
<select name='enabled'>
<option value='0'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1' selected='selected'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } else { ?>
<select name='enabled'>
<option value='0' selected='selected'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } ?>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_RESTRICT_PAGES; ?></label> <?php if ($restrict_pages) { ?> <input type="checkbox" checked="checked" name="restrict_pages" onclick="show_hide('allowed_pages');"/> <?php } else { ?> <input type="checkbox" name="restrict_pages" onclick="show_hide('allowed_pages');"/> <?php } ?>
<br />
<div id="allowed_pages" style="display:none;">
<label class='edit_administrator'>&nbsp;</label> <span class='note'><?php echo CMTX_NOTE_ALLOWED; ?></span>
<p />
<?php
echo cmtx_page_checkbox("manage", $id, "0") . "<span class='menu_checkbox'>Manage</span>" . "<p />";
	echo cmtx_page_checkbox("manage_comments", $id, "20") . "Comments" . "<br />";
		echo cmtx_page_checkbox("edit_comment", $id, "40") . "Edit" . "<br />";
	echo cmtx_page_checkbox("manage_pages", $id, "20") . "Pages" . "<br />";
		echo cmtx_page_checkbox("edit_page", $id, "40") . "Edit" . "<br />";
	echo cmtx_page_checkbox("manage_administrators", $id, "20") . "Admins" . "<br />";
		echo cmtx_page_checkbox("edit_administrator", $id, "40") . "Edit" . "<br />";
	echo cmtx_page_checkbox("manage_bans", $id, "20") . "Bans" . "<br />";
		echo cmtx_page_checkbox("edit_ban", $id, "40") . "Edit" . "<br />";
	echo cmtx_page_checkbox("manage_subscribers", $id, "20") . "Subscribers" . "<br />";
		echo cmtx_page_checkbox("edit_subscriber", $id, "40") . "Edit" . "<p />";
		
echo cmtx_page_checkbox("layout", $id, "0") . "<span class='menu_checkbox'>Layout</span>" . "<p />";
	echo cmtx_page_checkbox("layout_order", $id, "20") . "Order" . "<p />";
		echo cmtx_page_checkbox("layout_comments", $id, "20") . "<b>Comments</b>" . "<p />";
			echo cmtx_page_checkbox("layout_comments_enabled", $id, "40") . "Enabled" . "<br />";
			echo cmtx_page_checkbox("layout_comments_general", $id, "40") . "General" . "<br />";
			echo cmtx_page_checkbox("layout_comments_pagination", $id, "40") . "Pagination" . "<br />";
			echo cmtx_page_checkbox("layout_comments_sort_by", $id, "40") . "Sort By" . "<br />";
			echo cmtx_page_checkbox("layout_comments_replies", $id, "40") . "Replies" . "<br />";
			echo cmtx_page_checkbox("layout_comments_social", $id, "40") . "Social" . "<br />";
			echo cmtx_page_checkbox("layout_comments_gravatar", $id, "40") . "Gravatar" . "<br />";
			echo cmtx_page_checkbox("layout_comments_read_more", $id, "40") . "Read More" . "<p />";
		echo cmtx_page_checkbox("layout_form", $id, "20") . "<b>Form</b>" . "<p />";
			echo cmtx_page_checkbox("layout_form_enabled", $id, "40") . "Enabled" . "<br />";
			echo cmtx_page_checkbox("layout_form_required", $id, "40") . "Required" . "<br />";
			echo cmtx_page_checkbox("layout_form_defaults", $id, "40") . "Defaults" . "<br />";
			echo cmtx_page_checkbox("layout_form_general", $id, "40") . "General" . "<br />";
			echo cmtx_page_checkbox("layout_form_maximums", $id, "40") . "Maximums" . "<br />";
			echo cmtx_page_checkbox("layout_form_sort_order", $id, "40") . "<b>Sort Order</b>" . "<br />";
				echo cmtx_page_checkbox("layout_form_sort_order_fields", $id, "60") . "Fields" . "<br />";
				echo cmtx_page_checkbox("layout_form_sort_order_buttons", $id, "60") . "Buttons" . "<br />";
			echo cmtx_page_checkbox("layout_form_bb_code", $id, "40") . "BB Code" . "<br />";
			echo cmtx_page_checkbox("layout_form_smilies", $id, "40") . "Smilies" . "<br />";
			echo cmtx_page_checkbox("layout_form_questions", $id, "40") . "Questions" . "<br />";
				echo cmtx_page_checkbox("edit_question", $id, "60") . "Edit" . "<br />";
			echo cmtx_page_checkbox("layout_form_captchas", $id, "40") . "<b>Captchas</b>" . "<br />";
				echo cmtx_page_checkbox("layout_form_captchas_securimage", $id, "60") . "Securimage" . "<br />";
				echo cmtx_page_checkbox("layout_form_captchas_recaptcha", $id, "60") . "ReCaptcha" . "<br />";	
			echo cmtx_page_checkbox("layout_form_states", $id, "40") . "States" . "<p />";
		echo cmtx_page_checkbox("layout_powered", $id, "20") . "Powered" . "<p />";

echo cmtx_page_checkbox("settings", $id, "0") . "<span class='menu_checkbox'>Settings</span>" . "<p />";
	echo cmtx_page_checkbox("settings_administrator", $id, "20") . "Administrator" . "<br />";
	echo cmtx_page_checkbox("settings_admin_detection", $id, "20") . "Admin Detection" . "<br />";
	echo cmtx_page_checkbox("settings_akismet", $id, "20") . "Akismet" . "<br />";
	echo cmtx_page_checkbox("settings_approval", $id, "20") . "Approval" . "<br />";
	echo cmtx_page_checkbox("settings_email", $id, "20") . "<b>Email</b>" . "<br />";
		echo cmtx_page_checkbox("settings_email_setup", $id, "40") . "Setup" . "<br />";
		echo cmtx_page_checkbox("settings_email_editor", $id, "40") . "<b>Editor</b>" . "<br />";
			echo cmtx_page_checkbox("settings_email_editor_user", $id, "60") . "<b>User</b>" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_user_subscriber_confirmation", $id, "80") . "Subscriber Confirmation" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_user_subscriber_notification", $id, "80") . "Subscriber Notification" . "<br />";
			echo cmtx_page_checkbox("settings_email_editor_admin", $id, "60") . "<b>Admin</b>" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_admin_email_test", $id, "80") . "Email Test" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_admin_new_ban", $id, "80") . "New Ban" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_admin_new_comment_approve", $id, "80") . "New Comment: Approve" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_admin_new_comment_okay", $id, "80") . "New Comment: Okay" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_admin_new_flag", $id, "80") . "New Flag" . "<br />";
				echo cmtx_page_checkbox("settings_email_editor_admin_reset_password", $id, "80") . "Reset Password" . "<br />";
	echo cmtx_page_checkbox("settings_error_reporting", $id, "20") . "Error Reporting" . "<br />";
		echo cmtx_page_checkbox("error_log_frontend", $id, "40") . "Frontend" . "<br />";
		echo cmtx_page_checkbox("error_log_backend", $id, "40") . "Backend" . "<br />";
	echo cmtx_page_checkbox("settings_flagging", $id, "20") . "Flagging" . "<br />";
	echo cmtx_page_checkbox("settings_flooding", $id, "20") . "Flooding" . "<br />";
	echo cmtx_page_checkbox("settings_language", $id, "20") . "Language" . "<br />";
	echo cmtx_page_checkbox("settings_maintenance", $id, "20") . "Maintenance" . "<br />";
	echo cmtx_page_checkbox("settings_processor", $id, "20") . "<b>Processor</b>" . "<br />";
		echo cmtx_page_checkbox("settings_processor_name", $id, "40") . "Name" . "<br />";
			echo cmtx_page_checkbox("list_reserved_names", $id, "60") . "Reserved Names" . "<br />";
			echo cmtx_page_checkbox("list_dummy_names", $id, "60") . "Dummy Names" . "<br />";
			echo cmtx_page_checkbox("list_banned_names", $id, "60") . "Banned Names" . "<br />";
		echo cmtx_page_checkbox("settings_processor_email", $id, "40") . "Email" . "<br />";
			echo cmtx_page_checkbox("list_reserved_emails", $id, "60") . "Reserved Emails" . "<br />";
			echo cmtx_page_checkbox("list_dummy_emails", $id, "60") . "Dummy Emails" . "<br />";
			echo cmtx_page_checkbox("list_banned_emails", $id, "60") . "Banned Emails" . "<br />";
		echo cmtx_page_checkbox("settings_processor_town", $id, "40") . "Town" . "<br />";
			echo cmtx_page_checkbox("list_reserved_towns", $id, "60") . "Reserved Towns" . "<br />";
			echo cmtx_page_checkbox("list_dummy_towns", $id, "60") . "Dummy Towns" . "<br />";
			echo cmtx_page_checkbox("list_banned_towns", $id, "60") . "Banned Towns" . "<br />";
		echo cmtx_page_checkbox("settings_processor_website", $id, "40") . "Website" . "<br />";
			echo cmtx_page_checkbox("list_reserved_websites", $id, "60") . "Reserved Websites" . "<br />";
			echo cmtx_page_checkbox("list_dummy_websites", $id, "60") . "Dummy Websites" . "<br />";
			echo cmtx_page_checkbox("list_banned_websites", $id, "60") . "Banned Websites" . "<br />";
		echo cmtx_page_checkbox("settings_processor_comment", $id, "40") . "Comment" . "<br />";
			echo cmtx_page_checkbox("list_spam_words", $id, "60") . "Spam Words" . "<br />";
			echo cmtx_page_checkbox("list_mild_swear_words", $id, "60") . "Mild Swear Words" . "<br />";
			echo cmtx_page_checkbox("list_strong_swear_words", $id, "60") . "Strong Swear Words" . "<br />";
	echo cmtx_page_checkbox("settings_rich_snippets", $id, "20") . "Rich Snippets" . "<br />";
	echo cmtx_page_checkbox("settings_rss", $id, "20") . "RSS" . "<br />";
	echo cmtx_page_checkbox("settings_security", $id, "20") . "Security" . "<br />";
	echo cmtx_page_checkbox("settings_system", $id, "20") . "System" . "<br />";
	echo cmtx_page_checkbox("settings_viewers", $id, "20") . "Viewers" . "<p />";
		
echo cmtx_page_checkbox("tasks", $id, "0") . "<span class='menu_checkbox'>Tasks</span>" . "<p />";
	echo cmtx_page_checkbox("task_delete_bans", $id, "20") . "Delete Bans" . "<br />";
	echo cmtx_page_checkbox("task_delete_reporters", $id, "20") . "Delete Reporters" . "<br />";
	echo cmtx_page_checkbox("task_delete_subscribers", $id, "20") . "Delete Subscribers" ."<br />";
	echo cmtx_page_checkbox("task_delete_voters", $id, "20") . "Delete Voters" . "<p />";
	
echo cmtx_page_checkbox("reports", $id, "0") . "<span class='menu_checkbox'>Reports</span>" . "<p />";
	echo cmtx_page_checkbox("report_access", $id, "20") . "Access" . "<br />";
	echo cmtx_page_checkbox("report_permissions", $id, "20") . "Permissions" . "<br />";
	echo cmtx_page_checkbox("report_phpinfo", $id, "20") . "PHP Info" . "<br />";
	echo cmtx_page_checkbox("report_version", $id, "20") . "Version" . "<br />";
	echo cmtx_page_checkbox("report_viewers", $id, "20") . "Viewers" . "<p />";

echo cmtx_page_checkbox("tools", $id, "0") . "<span class='menu_checkbox'>Tools</span>" . "<p />";
	echo cmtx_page_checkbox("tool_db_backup", $id, "20") . "Database Backup" . "<br />";
	echo cmtx_page_checkbox("tool_optimize_tables", $id, "20") . "Optimize Tables" . "<br />";
	echo cmtx_page_checkbox("tool_text_finder", $id, "20") . "Text Finder";
?>
</div>
<?php if ($restrict_pages) { ?>
<script type="text/javascript">show_hide('allowed_pages')</script>
<?php } ?>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_TIME; ?></label> <input readonly="readonly" type="text" class="readonly" name="time" size="5" maxlength="250" value="<?php echo $time; ?>"/>
<p />
<label class='edit_administrator'><?php echo CMTX_FIELD_LABEL_DATE; ?></label> <input readonly="readonly" type="text" class="readonly" name="date" size="12" maxlength="250" value="<?php echo $date; ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
<input type="button" class="button" name="delete" onclick="if(delete_confirmation()){window.location='index.php?page=manage_administrators&action=delete&id=<?php echo $id . "&key=" . $_SESSION['cmtx_csrf_key']?>'};" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>"/>
</form>

<p />

<a href="index.php?page=manage_administrators"><?php echo CMTX_LINK_BACK; ?></a>