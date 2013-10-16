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

<h3><?php echo CMTX_TITLE_FLAGGING; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['show_flag'])) { $show_flag = 1; } else { $show_flag = 0; }
$flag_max_per_user = $_POST['flag_max_per_user'];
$flag_min_per_comment = $_POST['flag_min_per_comment'];
if (isset($_POST['flag_disapprove'])) { $flag_disapprove = 1; } else { $flag_disapprove = 0; }

$flag_max_per_user_san = cmtx_sanitize($flag_max_per_user);
$flag_min_per_comment_san = cmtx_sanitize($flag_min_per_comment);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_flag' WHERE `title` = 'show_flag'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$flag_max_per_user_san' WHERE `title` = 'flag_max_per_user'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$flag_min_per_comment_san' WHERE `title` = 'flag_min_per_comment'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$flag_disapprove' WHERE `title` = 'flag_disapprove'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_SETTINGS_FLAGGING; ?>

<p />

<form name="settings_flagging" id="settings_flagging" action="index.php?page=settings_flagging" method="post">
<label class='settings_flagging'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('show_flag')) { ?> <input type="checkbox" checked="checked" name="show_flag"/> <?php } else { ?> <input type="checkbox" name="show_flag"/> <?php } ?>
<p />
<label class='settings_flagging'><?php echo CMTX_FIELD_LABEL_MAX_PER_USER; ?></label> <input type="text" required name="flag_max_per_user" size="1" maxlength="4" value="<?php echo cmtx_setting('flag_max_per_user'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_FLAG_MAX_PER_USER); ?>
<p />
<label class='settings_flagging'><?php echo CMTX_FIELD_LABEL_MIN_PER_COM; ?></label> <input type="text" required name="flag_min_per_comment" size="1" maxlength="4" value="<?php echo cmtx_setting('flag_min_per_comment'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_FLAG_MIN_PER_COM); ?>
<p />
<label class='settings_flagging'><?php echo CMTX_FIELD_LABEL_DISAPPROVE; ?></label> <?php if (cmtx_setting('flag_disapprove')) { ?> <input type="checkbox" checked="checked" name="flag_disapprove"/> <?php } else { ?> <input type="checkbox" name="flag_disapprove"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_FLAG_DISAPPROVE); ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>