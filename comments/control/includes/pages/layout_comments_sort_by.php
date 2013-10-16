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

<h3><?php echo CMTX_TITLE_COMMENTS_SORT_BY; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['show_sort_by'])) { $show_sort_by = 1; } else { $show_sort_by = 0; }
if (isset($_POST['show_sort_by_1'])) { $show_sort_by_1 = 1; } else { $show_sort_by_1 = 0; }
if (isset($_POST['show_sort_by_2'])) { $show_sort_by_2 = 1; } else { $show_sort_by_2 = 0; }
if (isset($_POST['show_sort_by_3'])) { $show_sort_by_3 = 1; } else { $show_sort_by_3 = 0; }
if (isset($_POST['show_sort_by_4'])) { $show_sort_by_4 = 1; } else { $show_sort_by_4 = 0; }
if (isset($_POST['show_sort_by_5'])) { $show_sort_by_5 = 1; } else { $show_sort_by_5 = 0; }
if (isset($_POST['show_sort_by_6'])) { $show_sort_by_6 = 1; } else { $show_sort_by_6 = 0; }

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by' WHERE `title` = 'show_sort_by'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by_1' WHERE `title` = 'show_sort_by_1'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by_2' WHERE `title` = 'show_sort_by_2'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by_3' WHERE `title` = 'show_sort_by_3'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by_4' WHERE `title` = 'show_sort_by_4'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by_5' WHERE `title` = 'show_sort_by_5'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_sort_by_6' WHERE `title` = 'show_sort_by_6'");

?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<form name="layout_comments_sort_by" id="layout_comments_sort_by" action="index.php?page=layout_comments_sort_by" method="post">

<?php echo CMTX_DESC_LAYOUT_COMMENTS_SORT_BY_1 ?>

<p />

<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('show_sort_by')) { ?> <input type="checkbox" checked="checked" name="show_sort_by"/> <?php } else { ?> <input type="checkbox" name="show_sort_by"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_ENABLED); ?>

<p />

<?php echo CMTX_DESC_LAYOUT_COMMENTS_SORT_BY_2 ?>

<p />

<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_SORT_BY_1 ?></label> <?php if (cmtx_setting('show_sort_by_1')) { ?> <input type="checkbox" checked="checked" name="show_sort_by_1"/> <?php } else { ?> <input type="checkbox" name="show_sort_by_1"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_1); ?>
<p />
<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_SORT_BY_2 ?></label> <?php if (cmtx_setting('show_sort_by_2')) { ?> <input type="checkbox" checked="checked" name="show_sort_by_2"/> <?php } else { ?> <input type="checkbox" name="show_sort_by_2"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_2); ?>
<p />
<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_SORT_BY_3 ?></label> <?php if (cmtx_setting('show_sort_by_3')) { ?> <input type="checkbox" checked="checked" name="show_sort_by_3"/> <?php } else { ?> <input type="checkbox" name="show_sort_by_3"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_3); ?>
<p />
<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_SORT_BY_4 ?></label> <?php if (cmtx_setting('show_sort_by_4')) { ?> <input type="checkbox" checked="checked" name="show_sort_by_4"/> <?php } else { ?> <input type="checkbox" name="show_sort_by_4"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_4); ?>
<p />
<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_SORT_BY_5 ?></label> <?php if (cmtx_setting('show_sort_by_5')) { ?> <input type="checkbox" checked="checked" name="show_sort_by_5"/> <?php } else { ?> <input type="checkbox" name="show_sort_by_5"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_5); ?>
<p />
<label class='layout_comments_sort_by'><?php echo CMTX_FIELD_LABEL_SORT_BY_6 ?></label> <?php if (cmtx_setting('show_sort_by_6')) { ?> <input type="checkbox" checked="checked" name="show_sort_by_6"/> <?php } else { ?> <input type="checkbox" name="show_sort_by_6"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_SORT_BY_6); ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>