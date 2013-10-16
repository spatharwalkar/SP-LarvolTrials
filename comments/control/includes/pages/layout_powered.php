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

<h3><?php echo CMTX_TITLE_POWERED; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$powered_by = $_POST['powered_by'];
if (isset($_POST['powered_by_new_window'])) { $powered_by_new_window = 1; } else { $powered_by_new_window = 0; }

$powered_by_san = cmtx_sanitize($powered_by);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$powered_by_san' WHERE `title` = 'powered_by'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$powered_by_new_window' WHERE `title` = 'powered_by_new_window'");

?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_POWERED; ?>

<p />

<form name="layout_powered" id="layout_powered" action="index.php?page=layout_powered" method="post">
<label class='layout_powered'><?php echo CMTX_FIELD_LABEL_DISPLAY; ?></label>
<select name='powered_by'>
<?php if (cmtx_setting('powered_by') == "off") { ?>
<option value='off' selected='selected'><?php echo CMTX_FIELD_VALUE_OFF; ?></option>
<option value='text'><?php echo CMTX_FIELD_VALUE_TEXT; ?></option>
<option value='image'><?php echo CMTX_FIELD_VALUE_IMAGE; ?></option>
<?php } else if (cmtx_setting('powered_by') == "text") { ?>
<option value='off'><?php echo CMTX_FIELD_VALUE_OFF; ?></option>
<option value='text' selected='selected'><?php echo CMTX_FIELD_VALUE_TEXT; ?></option>
<option value='image'><?php echo CMTX_FIELD_VALUE_IMAGE; ?></option>
<?php } else if (cmtx_setting('powered_by') == "image") { ?>
<option value='off'><?php echo CMTX_FIELD_VALUE_OFF; ?></option>
<option value='text'><?php echo CMTX_FIELD_VALUE_TEXT; ?></option>
<option value='image' selected='selected'><?php echo CMTX_FIELD_VALUE_IMAGE; ?></option>
<?php } ?>
</select>
<p />
<label class='layout_powered'><?php echo CMTX_FIELD_LABEL_NEW_WINDOW; ?></label> <?php if (cmtx_setting('powered_by_new_window')) { ?> <input type="checkbox" checked="checked" name="powered_by_new_window"/> <?php } else { ?> <input type="checkbox" name="powered_by_new_window"/> <?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_NEW_WIN); ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>