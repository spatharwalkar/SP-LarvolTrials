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

<h3><?php echo CMTX_TITLE_EDIT_BAN; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "bans")) {
} else { ?>
<div class="error"><?php echo CMTX_MSG_RECORD_MISSING; ?></div>
<div style="clear: left;"></div>
<div style='margin-top: 10px;'>
<a href="index.php?page=manage_bans"><?php echo CMTX_LINK_BACK; ?></a>
<?php
die();
}
?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$id = $_GET['id'];
$ip_address = $_POST['ip_address'];
$reason = $_POST['reason'];

$id_san = cmtx_sanitize($id);
$ip_address_san = cmtx_sanitize($ip_address);
$reason_san = cmtx_sanitize($reason);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "bans` SET `ip_address` = '$ip_address_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "bans` SET `reason` = '$reason_san' WHERE `id` = '$id_san'");
?>
<div class="success"><?php echo CMTX_MSG_BAN_UPDATED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
$id = $_GET['id'];
$id_san = cmtx_sanitize($id);
$ban_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "bans` WHERE `id` = '$id_san'");
$ban_result = mysql_fetch_assoc($ban_query);
$ip_address = $ban_result["ip_address"];
$reason = $ban_result["reason"];
$time = date("g:ia", strtotime($ban_result["dated"]));
$date = date("jS M Y", strtotime($ban_result["dated"]));
?>

<p />

<form name="edit_ban" id="edit_ban" action="index.php?page=edit_ban&id=<?php echo $id ?>" method="post">
<label class='edit_ban'><?php echo CMTX_FIELD_LABEL_IP_ADDRESS; ?></label> <input type="text" required name="ip_address" size="20" maxlength="250" value="<?php echo $ip_address; ?>"/>
<p />
<label class='edit_ban'><?php echo CMTX_FIELD_LABEL_REASON; ?></label> <input type="text" required name="reason" size="20" maxlength="250" value="<?php echo $reason; ?>"/>
<p />
<label class='edit_ban'><?php echo CMTX_FIELD_LABEL_TIME; ?></label> <input readonly="readonly" type="text" class="readonly" name="time" size="5" maxlength="250" value="<?php echo $time; ?>"/>
<p />
<label class='edit_ban'><?php echo CMTX_FIELD_LABEL_DATE; ?></label> <input readonly="readonly" type="text" class="readonly" name="date" size="12" maxlength="250" value="<?php echo $date; ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
<input type="button" class="button" name="delete" onclick="if(delete_confirmation()){window.location='index.php?page=manage_bans&action=delete&id=<?php echo $id . "&key=" . $_SESSION['cmtx_csrf_key']?>'};" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>"/>
</form>

<p />

<a href="index.php?page=manage_bans"><?php echo CMTX_LINK_BACK; ?></a>