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

<h3><?php echo CMTX_TITLE_SECURITY; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['check_referrer'])) { $check_referrer = 1; } else { $check_referrer = 0; }
if (isset($_POST['check_db_file'])) { $check_db_file = 1; } else { $check_db_file = 0; }
if (isset($_POST['check_honeypot'])) { $check_honeypot = 1; } else { $check_honeypot = 0; }
if (isset($_POST['check_time'])) { $check_time = 1; } else { $check_time = 0; }
$ban_cookie_days = $_POST['ban_cookie_days'];

$ban_cookie_days_san = cmtx_sanitize($ban_cookie_days);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$check_referrer' WHERE `title` = 'check_referrer'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$check_db_file' WHERE `title` = 'check_db_file'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$check_honeypot' WHERE `title` = 'check_honeypot'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$check_time' WHERE `title` = 'check_time'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$ban_cookie_days_san' WHERE `title` = 'ban_cookie_days'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_SETTINGS_SECURITY; ?>

<p />

<form name="settings_security" id="settings_security" action="index.php?page=settings_security" method="post">
<label class='settings_security'><?php echo CMTX_FIELD_LABEL_CHECK_REFERRER; ?></label> <?php if (cmtx_setting('check_referrer')) { ?> <input type="checkbox" checked="checked" name="check_referrer"/> <?php } else { ?> <input type="checkbox" name="check_referrer"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_CHECK_REFERRER); ?>
<p />
<label class='settings_security'><?php echo CMTX_FIELD_LABEL_CHECK_DB_FILE; ?></label> <?php if (cmtx_setting('check_db_file')) { ?> <input type="checkbox" checked="checked" name="check_db_file"/> <?php } else { ?> <input type="checkbox" name="check_db_file"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_CHECK_DB_FILE); ?>
<p />
<label class='settings_security'><?php echo CMTX_FIELD_LABEL_CHECK_HONEYPOT; ?></label> <?php if (cmtx_setting('check_honeypot')) { ?> <input type="checkbox" checked="checked" name="check_honeypot"/> <?php } else { ?> <input type="checkbox" name="check_honeypot"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_CHECK_HONEYPOT); ?>
<p />
<label class='settings_security'><?php echo CMTX_FIELD_LABEL_CHECK_TIME; ?></label> <?php if (cmtx_setting('check_time')) { ?> <input type="checkbox" checked="checked" name="check_time"/> <?php } else { ?> <input type="checkbox" name="check_time"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_CHECK_TIME); ?>
<p />
<label class='settings_security'><?php echo CMTX_FIELD_LABEL_BAN_COOKIE; ?></label> <input type="text" required name="ban_cookie_days" size="1" maxlength="3" value="<?php echo cmtx_setting('ban_cookie_days'); ?>"/> <span class='note'><?php echo CMTX_NOTE_DAYS; ?></span>
<?php cmtx_generate_hint(CMTX_HINT_BAN_COOKIE); ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>