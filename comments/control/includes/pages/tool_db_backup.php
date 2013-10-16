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

<h3><?php echo CMTX_TITLE_DATABASE_BACKUP; ?></h3>
<hr class="title"/>

<?php
if (function_exists('system') && is_callable('system')) {
?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$backup_file = "backups/" . cmtx_get_random_key(20)  . ".sql";
if (!empty($cmtx_mysql_port)) {	$cmtx_mysql_host .= ":" . $cmtx_mysql_port; }
$command = cmtx_setting('mysqldump_path') . "mysqldump --host=$cmtx_mysql_host --user=$cmtx_mysql_username --password=$cmtx_mysql_password $cmtx_mysql_database > $backup_file";
system($command);

?>
<div class="success"><?php echo CMTX_MSG_BACKUP_CREATED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
if ( isset($_GET['action']) && $_GET['action'] == "delete" && isset($_GET['id']) && cmtx_check_csrf_url_key()) {
if (cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else {
$id = basename($_GET['id']);
if (ctype_alnum($id) && strlen($id) == 20 && file_exists("backups/" . $id . ".sql")) {
unlink("backups/" . $id . ".sql");
?>
<div class="success"><?php echo CMTX_MSG_BACKUP_DELETED; ?></div>
<div style="clear: left;"></div>
<?php } else { ?>
<div class="error"><?php echo CMTX_MSG_BACKUP_NOT_FOUND; ?></div>
<div style="clear: left;"></div>
<?php } } } ?>

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
$success = 0; $failure = 0;
for ($i = 0; $i < $count; $i++) {
	$id = basename($items[$i]);
	if (ctype_alnum($id) && strlen($id) == 20 && file_exists("backups/" . $id . ".sql")) {
		unlink("backups/" . $id . ".sql");
		$success ++;
	} else {
		$failure ++;
	}
}
?>
<?php if ($success == 1) { ?><div class="success"><?php echo CMTX_MSG_BACKUP_BULK_DELETED; ?></div><?php } ?>
<?php if ($success > 1) { ?><div class="success"><?php printf(CMTX_MSG_BACKUPS_BULK_DELETED, $success); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php if ($failure == 1) { ?><div class="error"><?php echo CMTX_MSG_BACKUP_BULK_NOT_FOUND; ?></div><?php } ?>
<?php if ($failure > 1) { ?><div class="error"><?php printf(CMTX_MSG_BACKUPS_BULK_NOT_FOUND, $failure); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<p />

<?php echo CMTX_DESC_TOOL_DATABASE_BACKUP; ?>

<p />

<form name="db_backup" id="db_backup" action="index.php?page=tool_db_backup" method="post">
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_BACKUP; ?>" value="<?php echo CMTX_BUTTON_BACKUP; ?>"/>
</form>

<br />

<form name="datatables" id="datatables" action="index.php?page=tool_db_backup" method="post">

<table id="data" class="display" summary="Backups">
    <thead>
    	<tr>
			<th style='width:0px;'><input type="checkbox" name="select_all" id="select_all" onclick="bulk_select();"/></th>
        	<th><?php echo CMTX_TABLE_FILENAME; ?></th>
            <th><?php echo CMTX_TABLE_SIZE; ?></th>
            <th><?php echo CMTX_TABLE_DATE_TIME; ?></th>
            <th><?php echo CMTX_TABLE_ACTION; ?></th>
        </tr>
    </thead>
    <tbody>

<?php
$directory = "backups/";
 
$backups = glob($directory . "*.sql");

usort($backups, create_function('$b,$a', 'return filemtime($a) - filemtime($b);'));

$count = 0;
 
foreach ($backups as $backup) {
?>
    	<tr>
			<td><input type="checkbox" name="bulk[]" value="<?php echo str_ireplace(".sql", "", basename($backup)); ?>" onclick="bulk_check();"/></td>
        	<td><?php echo "<a href='" . $backup . "'>" . basename($backup) . "</a>"; ?></td>
            <td><?php echo round(filesize($backup)/1024,2) . " KB"; ?></td>
            <td><?php echo date ("jS F Y g:ia", filemtime($backup)); ?></td>
			<td><a href="<?php echo "index.php?page=tool_db_backup&action=delete&id=" . str_ireplace(".sql", "", basename($backup)) . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/delete.png" class="button_delete" onclick="return delete_confirmation()" title="Delete" alt="Delete"></a></td>
        </tr>	
<?php
$count++;
}
?>

    </tbody>
</table>

<div style="clear: left;"></div>

<div style="margin-top:10px;"></div>

<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="bulk_delete" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>" onclick="return delete_bulk_confirmation()"/>
</form>

<?php
} else {
echo CMTX_MSG_BACKUP_UNABLE;
}
?>