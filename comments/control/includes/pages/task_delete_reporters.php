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

<h3><?php echo CMTX_TITLE_TASK_DELETE_REPORTERS; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['enabled'])) { $task_enabled_delete_reporters = 1; } else { $task_enabled_delete_reporters = 0; }
$days_to_delete_reporters = $_POST['days'];

$days_to_delete_reporters_san = cmtx_sanitize($days_to_delete_reporters);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$task_enabled_delete_reporters' WHERE `title` = 'task_enabled_delete_reporters'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$days_to_delete_reporters_san' WHERE `title` = 'days_to_delete_reporters'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_TASK_DELETE_REPORTERS; ?>

<p />

<form name="task_delete_reporters" id="task_delete_reporters" action="index.php?page=task_delete_reporters" method="post">
<label class='task_delete_reporters'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('task_enabled_delete_reporters')) { ?> <input type="checkbox" checked="checked" name="enabled"/> <?php } else { ?> <input type="checkbox" name="enabled"/> <?php } ?>
<p />
<label class='task_delete_reporters'><?php echo CMTX_FIELD_LABEL_DAYS; ?></label> <input type="text" required name="days" size="1" maxlength="4" value="<?php echo cmtx_setting('days_to_delete_reporters'); ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>