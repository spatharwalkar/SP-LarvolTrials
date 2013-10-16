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

<h3><?php echo CMTX_TITLE_EMAIL_TEST; ?></h3>
<hr class="title"/>

<?php
$admin_id = cmtx_get_admin_id();
$administrator = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `id` = '$admin_id'");
$administrator = mysql_fetch_assoc($administrator);
$email = $administrator["email"];
?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$admin_email_test_email_file = '../includes/emails/' . cmtx_setting('language_frontend') . '/admin/email_test.txt'; //build path to admin email test email file
$body = file_get_contents($admin_email_test_email_file); //get the file's contents
$admin_link = cmtx_url_encode_spaces(cmtx_setting('url_to_comments_folder') . cmtx_setting('admin_folder')) . "/"; //build admin panel link
$body = str_ireplace('[admin link]', $admin_link, $body);
$body = str_ireplace('[signature]', cmtx_setting('signature'), $body);
cmtx_email($email, null, cmtx_setting('admin_email_test_subject'), $body, cmtx_setting('admin_email_test_from_email'), cmtx_setting('admin_email_test_from_name'), cmtx_setting('admin_email_test_reply_to'));

?>
<div class="success"><?php echo CMTX_MSG_EMAIL_SENT; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php printf(CMTX_DESC_SETTINGS_EMAIL_TEST, $email); ?>

<p />

<form name="settings_email_test" id="settings_email_test" action="index.php?page=settings_email_test" method="post">
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_SEND; ?>" value="<?php echo CMTX_BUTTON_SEND; ?>"/>
</form>

<a href="index.php?page=settings_email_setup"><?php echo CMTX_LINK_BACK; ?></a>