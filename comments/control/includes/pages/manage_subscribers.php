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

<h3><?php echo CMTX_TITLE_SUBSCRIBERS; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$name = $_POST['name'];
$email = $_POST['email'];
$page_id = $_POST['page_id'];

$is_unique = FALSE;
while (!$is_unique) {
	$token = cmtx_get_random_key(20);
	if (mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "subscribers` WHERE `token` = '$token'")) == 0) {
		$is_unique = TRUE;
	}
}

$name = cmtx_sanitize($name, true, true);
$email = cmtx_sanitize($email, true, true);
$page_id = cmtx_sanitize($page_id);

mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "subscribers` (`name`, `email`, `page_id`, `token`, `is_confirmed`, `dated`) VALUES ('$name', '$email', '$page_id', '$token', '1', NOW());");
?>
<div class="success"><?php echo CMTX_MSG_SUB_ADDED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
if (isset($_GET['action']) && $_GET['action'] == "delete" && isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "subscribers") && cmtx_check_csrf_url_key()) {
if (cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else {
$id = $_GET['id'];
$id = cmtx_sanitize($id);
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "subscribers` WHERE `id` = '$id'");
?>
<div class="success"><?php echo CMTX_MSG_SUB_DELETED; ?></div>
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
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "subscribers` WHERE `id` = '$id'");
}
?>
<?php if ($count == 1) { ?><div class="success"><?php echo CMTX_MSG_SUB_BULK_DELETED; ?></div><?php } ?>
<?php if ($count > 1) { ?><div class="success"><?php printf(CMTX_MSG_SUBS_BULK_DELETED, $count); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<p />

<form name="add_subscriber" id="add_subscriber" action="index.php?page=manage_subscribers" method="post">
<?php echo CMTX_FIELD_LABEL_NAME; ?> <input type="text" required name="name" size="12" maxlength="250"/>&nbsp;
<?php echo CMTX_FIELD_LABEL_EMAIL; ?> <input type="email" required name="email" size="30" maxlength="250"/>&nbsp;
<?php echo CMTX_FIELD_LABEL_PAGE; ?> <select name="page_id"> <?php
$pages = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "pages` ORDER BY `id` ASC");
while ($page = mysql_fetch_assoc($pages)) { ?>
<option value='<?php echo $page['id'];?>'><?php echo $page['reference']; ?></option>
<?php } ?>
</select>&nbsp;
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_ADD_SUB; ?>" value="<?php echo CMTX_BUTTON_ADD_SUB; ?>"/>
</form>

<br />

<form name="datatables" id="datatables" action="index.php?page=manage_subscribers" method="post">

<table id="data" class="display" summary="Subscribers">
    <thead>
    	<tr>
			<th style='width:0px;'><input type="checkbox" name="select_all" id="select_all" onclick="bulk_select();"/></th>
        	<th><?php echo CMTX_TABLE_NAME; ?></th>
            <th><?php echo CMTX_TABLE_EMAIL; ?></th>
            <th><?php echo CMTX_TABLE_PAGE; ?></th>
			<th><?php echo CMTX_TABLE_CONFIRMED; ?></th>
			<th><?php echo CMTX_TABLE_DATE_TIME; ?></th>
            <th><?php echo CMTX_TABLE_ACTION; ?></th>
        </tr>
    </thead>
    <tbody>

<?php
$subscribers = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "subscribers` ORDER BY `dated` DESC");
while ($subscriber = mysql_fetch_assoc($subscribers)) {
?>
    	<tr>
			<td><input type="checkbox" name="bulk[]" value="<?php echo $subscriber["id"]; ?>" onclick="bulk_check();"/></td>
        	<td><?php echo $subscriber["name"]; ?></td>
            <td><?php echo $subscriber["email"]; ?></td>
			<?php
			$page_id = $subscriber["page_id"];
			$page_reference_query = mysql_query("SELECT `reference` FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$page_id'");
			$page_reference_result = mysql_fetch_assoc($page_reference_query);
			?>
			<td><?php echo $page_reference_result["reference"]; ?></td>
			<td><?php if ($subscriber["is_confirmed"]) { echo CMTX_TABLE_YES; } else { echo CMTX_TABLE_NO; } ?></td>
            <td><span style="display:none;"><?php echo date("YmdHis", strtotime($subscriber["dated"])); ?></span><?php echo date("jS F Y g:ia", strtotime($subscriber["dated"])); ?></td>
			<td>
			<a href="<?php echo "index.php?page=edit_subscriber&id=" . $subscriber["id"];?>"><img src="images/buttons/edit.png" class="button_edit" title="Edit" alt="Edit"></a>
			<a href="<?php echo "index.php?page=manage_subscribers&action=delete&id=" . $subscriber["id"] . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/delete.png" class="button_delete" onclick="return delete_confirmation()" title="Delete" alt="Delete"></a>
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