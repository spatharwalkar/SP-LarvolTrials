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

<h3><?php echo CMTX_TITLE_COMMENTS_READ_MORE; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['enabled'])) { $show_read_more = 1; } else { $show_read_more = 0; }
$read_more_limit = $_POST['read_more_limit'];

$read_more_limit_san = cmtx_sanitize($read_more_limit);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_read_more' WHERE `title` = 'show_read_more'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$read_more_limit_san' WHERE `title` = 'read_more_limit'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_COMMENTS_READ_MORE; ?>

<p />

<form name="layout_comments_read_more" id="layout_comments_read_more" action="index.php?page=layout_comments_read_more" method="post">
<label class='layout_comments_read_more'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('show_read_more')) { ?> <input type="checkbox" checked="checked" name="enabled"/> <?php } else { ?> <input type="checkbox" name="enabled"/> <?php } ?>
<p />
<label class='layout_comments_read_more'><?php echo CMTX_FIELD_LABEL_LIMIT; ?></label> <input type="text" required name="read_more_limit" size="1" maxlength="250" value="<?php echo cmtx_setting('read_more_limit'); ?>"/> <span class='note'><?php echo CMTX_NOTE_CHARS; ?></span>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>