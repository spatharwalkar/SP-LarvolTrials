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
<a class='page_help_text' href="" onclick="show_hide('pages');return false;"><?php echo CMTX_LINK_OPTIONS; ?></a> /
<a class='page_help_text' href="http://www.commentics.org/wiki/doku.php?id=admin:<?php echo $_GET['page']; ?>" target="_blank"><?php echo CMTX_LINK_HELP; ?></a>
</div>

<h3><?php echo CMTX_TITLE_PAGES; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['delay_pages'])) { $delay_pages = 1; } else { $delay_pages = 0; }
if (isset($_POST['lower_pages'])) { $lower_pages = 1; } else { $lower_pages = 0; }
if (!isset($_POST['enabled_form'])) { $enabled_form = 1; } else { $enabled_form = 0; }
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$delay_pages' WHERE `title` = 'delay_pages'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$lower_pages' WHERE `title` = 'lower_pages'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$enabled_form' WHERE `title` = 'enabled_form'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
if (isset($_GET['action']) && $_GET['action'] == "delete" && isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "pages") && cmtx_check_csrf_url_key()) {
if (cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else {
$id = $_GET['id'];
$id = cmtx_sanitize($id);
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$id'");
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "subscribers` WHERE `page_id` = '$id'");
$comments = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `page_id` = '$id'");
while ($comment = mysql_fetch_assoc($comments)) {
$comment_id = $comment["id"];
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "voters` WHERE `comment_id` = '$comment_id'");
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `comment_id` = '$comment_id'");
}
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `page_id` = '$id'");
?>
<div class="success"><?php echo CMTX_MSG_PAGE_DELETED; ?></div>
<div style="clear: left;"></div>
<?php } } ?>

<?php
if (isset($_POST['bulk_delete']) && isset($_POST['bulk']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['bulk_delete']) && isset($_POST['bulk'])) {
cmtx_check_csrf_form_key();
$items = $_POST['bulk'];
$count = count($items);
for ($i = 0; $i < $count; $i++) {
	$id = $items[$i];
	$id = cmtx_sanitize($id);
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$id'");
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "subscribers` WHERE `page_id` = '$id'");
	$comments = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `page_id` = '$id'");
	while ($comment = mysql_fetch_assoc($comments)) {
	$comment_id = $comment["id"];
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "voters` WHERE `comment_id` = '$comment_id'");
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `comment_id` = '$comment_id'");
	}
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `page_id` = '$id'");
}
?>
<?php if ($count == 1) { ?><div class="success"><?php echo CMTX_MSG_PAGE_BULK_DELETED; ?></div><?php } ?>
<?php if ($count > 1) { ?><div class="success"><?php printf(CMTX_MSG_PAGES_BULK_DELETED, $count); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<p />

<div id="pages" style="display:none;">
<div class="options_box">
<form name="page_options" id="page_options" action="index.php?page=manage_pages" method="post">
<?php if (cmtx_setting('delay_pages')) { ?> <input type="checkbox" checked="checked" name="delay_pages"/> <?php } else { ?> <input type="checkbox" name="delay_pages"/> <?php } ?>
<?php echo CMTX_FIELD_VALUE_DELAY_PAGES; ?>
<br />
<?php if (cmtx_setting('lower_pages')) { ?> <input type="checkbox" checked="checked" name="lower_pages"/> <?php } else { ?> <input type="checkbox" name="lower_pages"/> <?php } ?>
<?php echo CMTX_FIELD_VALUE_LOWER_PAGES; ?>
<br />
<?php if (!cmtx_setting('enabled_form')) { ?> <input type="checkbox" checked="checked" name="enabled_form"/> <?php } else { ?> <input type="checkbox" name="enabled_form"/> <?php } ?>
<?php echo CMTX_FIELD_VALUE_ENABLED_FORM; ?>
<div style='margin-bottom: 5px;'></div>
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>
</div>
</div>

<p />

<form name="datatables" id="datatables" action="index.php?page=manage_pages" method="post">

<table id="data" class="display" summary="Pages">
    <thead>
    	<tr>
			<th style='width:0px;'><input type="checkbox" name="select_all" id="select_all" onclick="bulk_select();"/></th>
			<th><?php echo CMTX_TABLE_IDENTIFIER; ?></th>
        	<th><?php echo CMTX_TABLE_REFERENCE; ?></th>
            <th><?php echo CMTX_TABLE_URL; ?></th>
			<th><?php echo CMTX_TABLE_FORM_ENABLED; ?></th>
            <th><?php echo CMTX_TABLE_DATE_TIME; ?></th>
            <th><?php echo CMTX_TABLE_ACTION; ?></th>
        </tr>
    </thead>
    <tbody>

<?php
$pages = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "pages` ORDER BY `id` ASC");
while ($page = mysql_fetch_assoc($pages)) {
?>
    	<tr>
			<td><input type="checkbox" name="bulk[]" value="<?php echo $page["id"]; ?>" onclick="bulk_check();"/></td>
			<td><?php echo $page["identifier"]; ?></td>
        	<td><?php echo $page["reference"]; ?></td>
            <td><?php echo "<a href='" . $page["url"] . "' target='_blank'>" . $page["url"] . "</a>"; ?></td>
			<td><?php if ($page["is_form_enabled"]) { echo CMTX_TABLE_YES; } else { echo CMTX_TABLE_NO; } ?></td>
            <td><span style="display:none;"><?php echo date("YmdHis", strtotime($page["dated"])); ?></span><?php echo date("jS F Y g:ia", strtotime($page["dated"])); ?></td>
			<td>
			<a href="<?php echo "index.php?page=edit_page&id=" . $page["id"];?>"><img src="images/buttons/edit.png" class="button_edit" title="Edit" alt="Edit"></a>
			<a href="<?php echo "index.php?page=manage_pages&action=delete&id=" . $page["id"] . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/delete.png" class="button_delete" onclick="return delete_confirmation()" title="Delete" alt="Delete"></a>
			</td>
        </tr>	
<?php } ?>

    </tbody>
</table>

<div style="clear: left;"></div>

<div style="margin-top:10px;"></div>

<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="bulk_delete" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>" onclick="return delete_bulk_confirmation()"/>
</form>