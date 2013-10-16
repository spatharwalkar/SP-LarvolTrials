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

<h3><?php echo CMTX_TITLE_RICH_SNIPPETS; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['rich_snippets'])) { $rich_snippets = 1; } else { $rich_snippets = 0; }
$rich_snippets_markup = $_POST['rich_snippets_markups'];

$rich_snippets_markup_san = cmtx_sanitize($rich_snippets_markup);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$rich_snippets' WHERE `title` = 'rich_snippets'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$rich_snippets_markup_san' WHERE `title` = 'rich_snippets_markup'");

?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_SETTINGS_RICH_SNIPPETS_1 ?>

<p />

<img src="images/rich_snippets/example.png" title="Example" alt="Example"/>

<p />

<?php echo CMTX_DESC_SETTINGS_RICH_SNIPPETS_2 ?>

<p />

<form name="settings_rich_snippets" id="settings_rich_snippets" action="index.php?page=settings_rich_snippets" method="post">
<label class='settings_rich_snippets'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('rich_snippets')) { ?> <input type="checkbox" checked="checked" name="rich_snippets"/> <?php } else { ?> <input type="checkbox" name="rich_snippets"/> <?php } ?>
<p />
<label class='settings_rich_snippets'><?php echo CMTX_FIELD_LABEL_RICH_SNIPPETS_FORMAT; ?></label>
<?php
$rich_snippets_markups = "<select name='rich_snippets_markups'>
<option value='Microdata'>Microdata</option>
<option value='Microformats'>Microformats</option>
<option value='RDFa'>RDFa</option>
</select>";
$rich_snippets_markups = str_ireplace("'".cmtx_setting('rich_snippets_markup')."'", "'".cmtx_setting('rich_snippets_markup')."' selected='selected'", $rich_snippets_markups);
echo $rich_snippets_markups;
?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>