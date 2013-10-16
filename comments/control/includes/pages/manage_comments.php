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

<h3><?php echo CMTX_TITLE_COMMENTS; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['notice']) && $_GET['notice'] == "dismiss" && cmtx_check_csrf_url_key()) {
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '0' WHERE `title` = 'notice_manage_comments'");
} else {
if (cmtx_setting('notice_manage_comments')) { ?>
<div class="info"><?php echo CMTX_MSG_NOTICE_MANAGE_COMMENTS . " <a href='index.php?page=manage_comments&notice=dismiss&key=" . $_SESSION['cmtx_csrf_key'] . "'>" . CMTX_LINK_DISMISS . "</a>"; ?></div>
<div style="clear: left;"></div>
<?php } } ?>

<?php
if (isset($_POST['spam']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['spam'])) {

cmtx_check_csrf_form_key();

$id = $_POST['id'];
$id = cmtx_sanitize($id);
$comment_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
$comment_result = mysql_fetch_assoc($comment_query);
$name = $comment_result["name"];
$email = $comment_result["email"];
$website = $comment_result["website"];
$ip_address = $comment_result["ip_address"];

if ($_POST['delete'] == "delete_this") {
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	cmtx_delete_replies($id);
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "voters` WHERE `comment_id` = '$id'");
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `comment_id` = '$id'");
} else { //delete all
	$spam_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `ip_address` = '$ip_address'");
	while ($spam = mysql_fetch_assoc($spam_query)) {
		$id = $spam["id"];
		$id = cmtx_sanitize($id);
		mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
		cmtx_delete_replies($id);
		mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "voters` WHERE `comment_id` = '$id'");
		mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `comment_id` = '$id'");
	}
}

if ($_POST['ban'] == "do_ban") {
	mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "bans` (`ip_address`, `reason`, `unban`, `dated`) VALUES ('$ip_address', '" . cmtx_sanitize(CMTX_FIELD_VALUE_SPAM) . "', '0', NOW());");
}

if (isset($_POST['ban_name'])) {
	$file = "../includes/words/banned_names.txt";
	$handle = fopen($file, "a");
	fputs($handle, "\r\n" . $name);
	fclose($handle);
}

if (isset($_POST['ban_email'])) {
	$file = "../includes/words/banned_emails.txt";
	$handle = fopen($file, "a");
	fputs($handle, "\r\n" . $email);
	fclose($handle);
}

if (isset($_POST['ban_website'])) {
	$file = "../includes/words/banned_websites.txt";
	$handle = fopen($file, "a");
	fputs($handle, "\r\n" . $website);
	fclose($handle);
}
?>
<div class="success"><?php echo CMTX_MSG_SPAM_REMOVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
if (isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "comments") && cmtx_check_csrf_url_key()) {
if (cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else {
if ($_GET['action'] == "delete") {
	$id = $_GET['id'];
	$id = cmtx_sanitize($id);
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	cmtx_delete_replies($id);
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "voters` WHERE `comment_id` = '$id'");
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `comment_id` = '$id'");
	?>
	<div class="success"><?php echo CMTX_MSG_COMMENT_DELETED; ?></div>
	<div style="clear: left;"></div>
	<?php
} else if ($_GET['action'] == "approve") {
	if (cmtx_is_approved($_GET['id'])) {
		?>
		<div class="error"><?php echo CMTX_MSG_COMMENT_ALREADY_APPROVED; ?></div>
		<div style="clear: left;"></div>
		<?php
	} else {
		$id = $_GET['id'];
		$id = cmtx_sanitize($id);
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_approved` = '1' WHERE `id` = '$id'");
		?>
		<div class="success"><?php echo CMTX_MSG_COMMENT_APPROVED; ?></div>
		<div style="clear: left;"></div>
		<?php
	}
} else if ($_GET['action'] == "send" && cmtx_setting('enabled_notify')) {
	if (cmtx_is_sent($_GET['id'])) {
		?>
		<div class="error"><?php echo CMTX_MSG_COMMENT_ALREADY_SENT; ?></div>
		<div style="clear: left;"></div>
		<?php
	} else {
		$id = $_GET['id'];
		$id = cmtx_sanitize($id);
		$comment_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
		$comment_result = mysql_fetch_assoc($comment_query);
		$name = $comment_result["name"];
		$comment = $comment_result["comment"];
		$page_id = $comment_result["page_id"];
		cmtx_notify_subscribers($name, $comment, $id, $page_id);
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_approved` = '1' WHERE `id` = '$id'");
		?>
		<div class="success"><?php echo CMTX_MSG_COMMENT_SENT; ?></div>
		<div style="clear: left;"></div>
		<?php
	}
}
?>
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
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	cmtx_delete_replies($id);
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "voters` WHERE `comment_id` = '$id'");
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `comment_id` = '$id'");
}
?>
<?php if ($count == 1) { ?><div class="success"><?php echo CMTX_MSG_COMMENT_BULK_DELETED; ?></div><?php } ?>
<?php if ($count > 1) { ?><div class="success"><?php printf(CMTX_MSG_COMMENTS_BULK_DELETED, $count); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<?php
if (isset($_POST['bulk_approve']) && isset($_POST['bulk']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['bulk_approve']) && isset($_POST['bulk'])) {
cmtx_check_csrf_form_key();
$items = $_POST['bulk'];
$count = count($items);
$success = 0; $failure = 0;
for ($i = 0; $i < $count; $i++) {
	$id = $items[$i];
	$id = cmtx_sanitize($id);
	if (cmtx_is_approved($id)) {
		$failure ++;
	} else {
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_approved` = '1' WHERE `id` = '$id'");
		$success ++;
	}
}
?>
<?php if ($success == 1) { ?><div class="success"><?php echo CMTX_MSG_COMMENT_BULK_APPROVED; ?></div><?php } ?>
<?php if ($success > 1) { ?><div class="success"><?php printf(CMTX_MSG_COMMENTS_BULK_APPROVED, $success); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php if ($failure == 1) { ?><div class="error"><?php echo CMTX_MSG_COMMENT_BULK_ALREADY_APPROVED; ?></div><?php } ?>
<?php if ($failure > 1) { ?><div class="error"><?php printf(CMTX_MSG_COMMENTS_BULK_ALREADY_APPROVED, $failure); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<?php
if (isset($_POST['bulk_send']) && isset($_POST['bulk']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['bulk_send']) && isset($_POST['bulk'])) {
cmtx_check_csrf_form_key();
$items = $_POST['bulk'];
$count = count($items);
$success = 0; $failure = 0;
for ($i = 0; $i < $count; $i++) {
	$id = $items[$i];
	$id = cmtx_sanitize($id);
	if (cmtx_is_sent($id)) {
		$failure ++;
	} else {
		$comment_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
		$comment_result = mysql_fetch_assoc($comment_query);
		$name = $comment_result["name"];
		$comment = $comment_result["comment"];
		$page_id = $comment_result["page_id"];
		cmtx_notify_subscribers($name, $comment, $id, $page_id);
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_approved` = '1' WHERE `id` = '$id'");
		$success ++;
	}
}
?>
<?php if ($success == 1) { ?><div class="success"><?php echo CMTX_MSG_COMMENT_BULK_SENT; ?></div><?php } ?>
<?php if ($success > 1) { ?><div class="success"><?php printf(CMTX_MSG_COMMENTS_BULK_SENT, $success); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php if ($failure == 1) { ?><div class="error"><?php echo CMTX_MSG_COMMENT_BULK_ALREADY_SENT; ?></div><?php } ?>
<?php if ($failure > 1) { ?><div class="error"><?php printf(CMTX_MSG_COMMENTS_BULK_ALREADY_SENT, $failure); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<p />

<form name="datatables" id="datatables" action="index.php?page=manage_comments" method="post">

<table id="data" class="display" summary="Comments">
    <thead>
    	<tr>
			<th style='width:0px;'><input type="checkbox" name="select_all" id="select_all" onclick="bulk_select();"/></th>
        	<th><?php echo CMTX_TABLE_NAME; ?></th>
			<th><?php echo CMTX_TABLE_PAGE; ?></th>
			<th><?php echo CMTX_TABLE_COMMENT; ?></th>
			<th><?php echo CMTX_TABLE_APPROVED; ?></th>
			<?php if (cmtx_setting('enabled_notify')) { ?>
			<th><?php echo CMTX_TABLE_SENT; ?></th>
			<?php } ?>
			<?php if (cmtx_setting('show_flag')) { ?>
			<th><?php echo CMTX_TABLE_REPORTS; ?></th>
			<th><?php echo CMTX_TABLE_FLAGGED; ?></th>
			<?php } ?>
			<th><?php echo CMTX_TABLE_DATE_TIME; ?></th>
            <th><?php echo CMTX_TABLE_ACTION; ?></th>
        </tr>
    </thead>
    <tbody>

<?php
$comments = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` ORDER BY `dated` DESC LIMIT " . cmtx_setting('limit_comments'));
while ($comment = mysql_fetch_assoc($comments)) {
?>
    	<tr>
			<td><input type="checkbox" name="bulk[]" value="<?php echo $comment["id"]; ?>" onclick="bulk_check();"/></td>
			<?php $id = $comment["id"]; ?>
			<td><?php echo $comment["name"]; ?></td>
			<?php
			$page_id = $comment["page_id"];
			$page_reference_query = mysql_query("SELECT `reference` FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$page_id'");
			$page_reference_result = mysql_fetch_assoc($page_reference_query);
			?>
			<td><?php echo $page_reference_result["reference"]; ?></td>
			<?php
			$comment1 = $comment["comment"];
			$comment1 = str_ireplace("<br />", " ", $comment1);
			$comment1 = str_ireplace("<p></p>", " ", $comment1);
			$comment1 = strip_tags($comment1);
			$comment1 = cmtx_decode($comment1);
			$comment1 = substr($comment1, 0, 75);
			$comment1 = htmlspecialchars($comment1, ENT_NOQUOTES, 'UTF-8');
			?>
			<td><?php echo $comment1; ?></td>
			<td><?php if ($comment["is_approved"]) { echo CMTX_TABLE_YES; } else { echo CMTX_TABLE_NO; } ?></td>
			<?php if (cmtx_setting('enabled_notify')) { ?>
			<td><?php if ($comment["is_sent"]) { echo CMTX_TABLE_YES; } else { echo CMTX_TABLE_NO; } ?></td>
			<?php } ?>
			<?php if (cmtx_setting('show_flag')) {
			echo "<td>" . $comment["reports"] . "</td>";
			echo "<td>";
			if ($comment["reports"] >= cmtx_setting('flag_min_per_comment')) { echo CMTX_TABLE_YES; } else { echo CMTX_TABLE_NO; }
			echo "</td>";
			}
			?>
            <td><span style="display:none;"><?php echo date("YmdHis", strtotime($comment["dated"])); ?></span><?php echo date("jS F Y g:ia", strtotime($comment["dated"])); ?></td>
			<td>
			<a href="<?php echo "index.php?page=manage_comments&action=approve&id=" . $comment["id"] . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/approve.png" class="button_approve" title="Approve" alt="Approve"></a>
			<?php if (cmtx_setting('enabled_notify')) { ?> <a href="<?php echo "index.php?page=manage_comments&action=send&id=" . $comment["id"] . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/send.png" class="button_send" title="Send to email subscribers" alt="Send"></a> <?php } ?>
			<a href="<?php echo "index.php?page=edit_comment&id=" . $comment["id"];?>"><img src="images/buttons/edit.png" class="button_edit" title="Edit" alt="Edit"></a>
			<a href="<?php echo "index.php?page=manage_comments&action=delete&id=" . $comment["id"] . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/delete.png" class="button_delete" onclick="return delete_confirmation()" title="Delete" alt="Delete"></a>
			</td>
        </tr>	
<?php } ?>

    </tbody>
</table>

<div style="clear: left;"></div>

<div style="margin-top:10px;"></div>

<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="bulk_approve" title="<?php echo CMTX_BUTTON_APPROVE; ?>" value="<?php echo CMTX_BUTTON_APPROVE; ?>"/>
<?php if (cmtx_setting('enabled_notify')) { ?><input type="submit" class="button" name="bulk_send" title="Send to email subscribers" value="<?php echo CMTX_BUTTON_SEND; ?>"/> <?php } ?>
<input type="submit" class="button" name="bulk_delete" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>" onclick="return delete_bulk_confirmation()"/>
</form>