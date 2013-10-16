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

<script type="text/javascript">
// <![CDATA[
function signature_preview() {
text = document.getElementById('signature').value;
text = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
text = text.replace(/\n/g, '<br/>');
text = 'Hello,<br/><br/>This is a sample email to help you to decide your preferred signature.<br/><br/>Adjust the signature below to see a live preview of what it will look like.<br/><br/>Regards,<br/><br/>' + text;
document.getElementById('signature_preview').innerHTML = text;
}
// ]]>
</script>

<div class='page_help_block'>
<a class='page_help_text' href="http://www.commentics.org/wiki/doku.php?id=admin:<?php echo $_GET['page']; ?>" target="_blank"><?php echo CMTX_LINK_HELP; ?></a>
</div>

<h3><?php echo CMTX_TITLE_EMAIL_SIGNATURE; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$signature = $_POST['signature'];

$signature_san = cmtx_sanitize($signature);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$signature_san' WHERE `title` = 'signature'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_SETTINGS_EMAIL_SIGNATURE; ?>

<p />

<form name="settings_email_signature" id="settings_email_signature" action="index.php?page=settings_email_signature" method="post">
<label class='settings_email_signature'><?php echo CMTX_FIELD_LABEL_PREVIEW; ?></label> <div id="signature_preview" class="signature_preview"></div>
<div style="clear: left;"></div>
<p />
<label class='settings_email_signature'><?php echo CMTX_FIELD_LABEL_SIGNATURE; ?></label> <textarea name="signature" id="signature" cols="46" rows="4" onkeyup="signature_preview();"><?php echo cmtx_setting('signature'); ?></textarea>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>

<script type="text/javascript">signature_preview();</script>

<a href="index.php?page=settings_email_setup"><?php echo CMTX_LINK_BACK; ?></a>