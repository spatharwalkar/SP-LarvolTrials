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

<h3><?php echo CMTX_TITLE_EDIT_SUBSCRIBER; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "subscribers")) {
} else { ?>
<div class="error"><?php echo CMTX_MSG_RECORD_MISSING; ?></div>
<div style="clear: left;"></div>
<div style='margin-top: 10px;'>
<a href="index.php?page=manage_subscribers"><?php echo CMTX_LINK_BACK; ?></a>
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
$name = $_POST['name'];
$email = $_POST['email'];
$page_id = $_POST['page_id'];
$confirmed = $_POST['confirmed'];

$id_san = cmtx_sanitize($id);
$name_san = cmtx_sanitize($name, true, true);
$email_san = cmtx_sanitize($email, true, true);
$page_id_san = cmtx_sanitize($page_id);
$confirmed_san = cmtx_sanitize($confirmed);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "subscribers` SET `name` = '$name_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "subscribers` SET `email` = '$email_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "subscribers` SET `page_id` = '$page_id_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "subscribers` SET `is_confirmed` = '$confirmed_san' WHERE `id` = '$id_san'");
?>
<div class="success"><?php echo CMTX_MSG_SUB_UPDATED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
$id = $_GET['id'];
$id_san = cmtx_sanitize($id);
$subscriber_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "subscribers` WHERE `id` = '$id_san'");
$subscriber_result = mysql_fetch_assoc($subscriber_query);
$name = $subscriber_result["name"];
$email = $subscriber_result["email"];
$confirmed = $subscriber_result["is_confirmed"];
$time = date("g:ia", strtotime($subscriber_result["dated"]));
$date = date("jS M Y", strtotime($subscriber_result["dated"]));

$page_id = $subscriber_result["page_id"];
$page_reference_query = mysql_query("SELECT `reference` FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$page_id'");
$page_reference_result = mysql_fetch_assoc($page_reference_query);
$page_reference = $page_reference_result["reference"];
?>

<p />

<form name="edit_subscriber" id="edit_subscriber" action="index.php?page=edit_subscriber&id=<?php echo $id ?>" method="post">
<label class='edit_subscriber'><?php echo CMTX_FIELD_LABEL_NAME; ?></label> <input type="text" required name="name" size="20" maxlength="250" value="<?php echo $name; ?>"/>
<p />
<label class='edit_subscriber'><?php echo CMTX_FIELD_LABEL_EMAIL; ?></label> <input type="email" required name="email" size="31" maxlength="250" value="<?php echo $email; ?>"/>
<p />
<label class='edit_subscriber'><?php echo CMTX_FIELD_LABEL_PAGE; ?></label> <select name="page_id"><?php
$pages = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "pages` ORDER BY `id` ASC");
while ($page = mysql_fetch_assoc($pages)) { ?>
<option value='<?php echo $page['id'];?>' <?php if ($page_id == $page['id']) { echo "selected='selected'"; } ?>><?php echo $page['reference']; ?></option>
<?php } ?>
</select>
<p />
<label class='edit_subscriber'><?php echo CMTX_FIELD_LABEL_CONFIRMED; ?></label>
<?php if ($confirmed) { ?>
<select name='confirmed'>
<option value='0'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1' selected='selected'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } else { ?>
<select name='confirmed'>
<option value='0' selected='selected'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } ?>
<p />
<label class='edit_subscriber'><?php echo CMTX_FIELD_LABEL_TIME; ?></label> <input readonly="readonly" type="text" class="readonly" name="time" size="5" maxlength="250" value="<?php echo $time; ?>"/>
<p />
<label class='edit_subscriber'><?php echo CMTX_FIELD_LABEL_DATE; ?></label> <input readonly="readonly" type="text" class="readonly" name="date" size="12" maxlength="250" value="<?php echo $date; ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
<input type="button" class="button" name="delete" onclick="if(delete_confirmation()){window.location='index.php?page=manage_subscribers&action=delete&id=<?php echo $id . "&key=" . $_SESSION['cmtx_csrf_key']?>'};" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>"/>
</form>

<p />

<a href="index.php?page=manage_subscribers"><?php echo CMTX_LINK_BACK; ?></a>