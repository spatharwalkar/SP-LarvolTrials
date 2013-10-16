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

<h3><?php echo CMTX_TITLE_EDIT_COMMENT; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "comments")) {
} else { ?>
<div class="error"><?php echo CMTX_MSG_RECORD_MISSING; ?></div>
<div style="clear: left;"></div>
<div style='margin-top: 10px;'>
<a href="index.php?page=manage_comments"><?php echo CMTX_LINK_BACK; ?></a>
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
$website = $_POST['website'];
$town = $_POST['town'];
$country = $_POST['cmtx_country'];
if ($country == "blank") { $country = ""; }
$rating = $_POST['cmtx_rating'];
if ($rating == "blank") { $rating = ""; }
$comment = $_POST['comment'];
$reply = $_POST['reply'];
$page_id = $_POST['page_id'];
$reply_to = $_POST['reply_to'];
$is_approved = $_POST['is_approved'];
$is_sticky = $_POST['is_sticky'];
$is_locked = $_POST['is_locked'];

$id_san = cmtx_sanitize($id);
$name_san = cmtx_sanitize($name, true, true);
$email_san = cmtx_sanitize($email, true, true);
$website_san = cmtx_url_encode_spaces($website);
$website_san = cmtx_sanitize($website_san, true, true);
$town_san = cmtx_sanitize($town, true, true);
$country_san = cmtx_sanitize($country, true, true);
$rating_san = cmtx_sanitize($rating, true, true);
$comment_san = cmtx_sanitize($comment);
$reply_san = cmtx_sanitize($reply);
$page_id_san = cmtx_sanitize($page_id);
$reply_to_san = cmtx_sanitize($reply_to);
$is_approved_san = cmtx_sanitize($is_approved);
$is_sticky_san = cmtx_sanitize($is_sticky);
$is_locked_san = cmtx_sanitize($is_locked);

if (!$is_approved) {
cmtx_unapprove_replies($id);
}

if (isset($_POST['send']) && $_POST['send'] == "1") {
cmtx_notify_subscribers($name, $comment, $id, $page_id);
$is_approved_san = "1";
}

if (isset($_POST['verify'])) {
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `reports` = '0' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_verified` = '1' WHERE `id` = '$id_san'");
$is_approved_san = "1";
}

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `name` = '$name_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `email` = '$email_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `website` = '$website_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `town` = '$town_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `country` = '$country_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `rating` = '$rating_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `comment` = '$comment_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `reply` = '$reply_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `page_id` = '$page_id_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `page_id` = '$page_id_san' WHERE `reply_to` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `reply_to` = '$reply_to_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_approved` = '$is_approved_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_sticky` = '$is_sticky_san' WHERE `id` = '$id_san'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_locked` = '$is_locked_san' WHERE `id` = '$id_san'");

?>
<div class="success"><?php echo CMTX_MSG_COMMENT_UPDATED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
$id = $_GET['id'];
$id_san = cmtx_sanitize($id);
$comment_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id_san'");
$comment_result = mysql_fetch_assoc($comment_query);
$name = $comment_result["name"];
$email = $comment_result["email"];
$website = $comment_result["website"];
$town = $comment_result["town"];
$country = $comment_result["country"];
$rating = $comment_result["rating"];
$comment = $comment_result["comment"];
$reply = $comment_result["reply"];
$reply_to = $comment_result["reply_to"];
$ip_address = $comment_result["ip_address"];
$is_approved = $comment_result["is_approved"];
$approval_reasoning = $comment_result["approval_reasoning"];
$is_sent = $comment_result["is_sent"];
$sent_to = $comment_result["sent_to"];
$likes = $comment_result["likes"];
$dislikes = $comment_result["dislikes"];
$reports = $comment_result["reports"];
$is_sticky = $comment_result["is_sticky"];
$is_locked = $comment_result["is_locked"];
$is_verified = $comment_result["is_verified"];

$time = date("g:ia", strtotime($comment_result["dated"]));
$date = date("jS M Y", strtotime($comment_result["dated"]));

$page_id = $comment_result["page_id"];
$page_id_san = cmtx_sanitize($page_id);
$page_reference_query = mysql_query("SELECT `reference` FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$page_id_san'");
$page_reference_result = mysql_fetch_assoc($page_reference_query);
$page_reference = $page_reference_result["reference"];
?>

<p />

<?php require '../includes/language/' . cmtx_setting('language_frontend') . '/countries.php'; ?>
<?php require '../includes/language/' . cmtx_setting('language_frontend') . '/ratings.php'; ?>

<form name="edit_comment" id="edit_comment" action="index.php?page=edit_comment&id=<?php echo $id ?>" method="post">
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_NAME; ?></label> <input type="text" required name="name" size="33" maxlength="250" value="<?php echo $name; ?>"/>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_EMAIL; ?></label> <input type="email" name="email" size="33" maxlength="250" value="<?php echo $email; ?>"/>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_WEBSITE; ?></label> <input type="text" name="website" size="33" maxlength="250" value="<?php echo $website; ?>"/>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_TOWN; ?></label> <input type="text" name="town" size="33" maxlength="250" value="<?php echo $town; ?>"/>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_COUNTRY; ?></label>
<?php
require '../includes/template/countries.php';
if (!empty($country)) {
	$cmtx_countries = str_ireplace('"'.$country.'"', '"'.$country.'" selected="selected"', $cmtx_countries);
}
echo $cmtx_countries;
?>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_RATING; ?></label>
<?php
require '../includes/template/ratings.php';
if (!empty($rating)) {
	$cmtx_ratings = str_ireplace('"'.$rating.'"', '"'.$rating.'" selected="selected"', $cmtx_ratings);
}
echo $cmtx_ratings;
?>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_COMMENT; ?></label> <textarea name="comment" cols="39" rows="6"><?php echo cmtx_sanitize($comment, true, false); ?></textarea>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_REPLY; ?></label> <textarea name="reply" cols="39" rows="6"><?php echo cmtx_sanitize($reply, true, false); ?></textarea>

<br /><hr class="separator"/><br />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_PAGE; ?></label> <select name="page_id">
<?php
$pages = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "pages` ORDER BY `id` ASC");
while ($page = mysql_fetch_assoc($pages)) { ?>
<option value='<?php echo $page['id'];?>' <?php if ($page_id == $page['id']) { echo "selected='selected'"; } ?>><?php echo $page['reference']; ?></option>
<?php } ?>
</select>

<p />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_REPLY_TO; ?></label>
<select name="reply_to">
<option value="0"<?php if (!$reply_to) { echo " selected='selected'"; } ?>><?php echo CMTX_FIELD_VALUE_NOBODY; ?></option>
<?php
$comments = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `page_id` = '$page_id_san' AND `id` != '$id_san' ORDER BY `dated` DESC");
while ($comment = mysql_fetch_assoc($comments)) {
echo "<option value='" . $comment['id'] . "'";
if ($reply_to == $comment['id']) { echo " selected='selected'"; }
echo ">" . $comment['name'] . " - " . date("jS M Y", strtotime($comment["dated"])) . " - " . date("g:ia", strtotime($comment["dated"])) . "</option>";
}
?>
</select>

<p />

<hr class="separator"/><br />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_APPROVED; ?></label>
<?php if ($is_approved) { ?>
<select name='is_approved'>
<option value='0'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1' selected='selected'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } else { ?>
<select name='is_approved'>
<option value='0' selected='selected'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } ?>

<p />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_NOTES; ?></label> <?php if (empty($approval_reasoning)) { echo CMTX_FIELD_VALUE_NONE; } else { echo $approval_reasoning; } ?>

<?php if (cmtx_setting('enabled_notify')) { ?>
<br /><hr class="separator"/><br />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_SEND; ?></label>
<?php if ($is_sent) {
echo CMTX_FIELD_VALUE_SENT_TO . " " . $sent_to;
if ($sent_to == 1) { echo " " . CMTX_FIELD_VALUE_SUBSCRIBER; } else { echo " " . CMTX_FIELD_VALUE_SUBSCRIBERS; }
} else {  ?>
<select name='send'>
<option value='0' selected='selected'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php cmtx_generate_hint(CMTX_HINT_SEND); ?>
<?php } ?>
<br />
<?php } ?>

<?php if (cmtx_setting('show_like') || cmtx_setting('show_dislike')) { ?>
<br /><hr class="separator"/><br />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_LIKES; ?></label>
<?php echo $likes; ?>
<?php if ($likes == 1) { echo " " . CMTX_FIELD_VALUE_ONE_LIKE; } else { echo " " . CMTX_FIELD_VALUE_MANY_LIKES; } ?>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_DISLIKES; ?></label>
<?php echo $dislikes; ?>
<?php if ($dislikes == 1) { echo " " . CMTX_FIELD_VALUE_ONE_DISLIKE; } else { echo " " . CMTX_FIELD_VALUE_MANY_DISLIKES; } ?>
<?php } ?>

<?php if (cmtx_setting('show_flag')) {
?><br /><hr class="separator"/><br />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_REPORTS; ?></label><?php
if ($reports == 0) { echo CMTX_FIELD_VALUE_NO_REPORTS; }
if ($reports == 1) { echo CMTX_FIELD_VALUE_ONE_REPORT; }
if ($reports > 1) { echo CMTX_FIELD_VALUE_THERE_ARE . " " . $reports . " " . CMTX_FIELD_VALUE_REPORTS; }
?>
<p /><label class='edit_comment'><?php echo CMTX_FIELD_LABEL_VERIFY; ?></label><?php
if ($is_verified) {
echo CMTX_FIELD_VALUE_VERIFIED;
} else {
?><input type="checkbox" name="verify"/><?php
cmtx_generate_hint(CMTX_HINT_VERIFY);
}
}
?>

<hr class="separator"/><br />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_STICKY; ?></label>
<?php if ($is_sticky) { ?>
<select name='is_sticky'>
<option value='0'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1' selected='selected'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } else { ?>
<select name='is_sticky'>
<option value='0' selected='selected'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_STICKY); ?>

<p />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_LOCKED; ?></label>
<?php if ($is_locked) { ?>
<select name='is_locked'>
<option value='0'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1' selected='selected'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } else { ?>
<select name='is_locked'>
<option value='0' selected='selected'><?php echo CMTX_FIELD_VALUE_NO; ?></option>
<option value='1'><?php echo CMTX_FIELD_VALUE_YES; ?></option>
</select>
<?php } ?>
<?php cmtx_generate_hint(CMTX_HINT_LOCKED); ?>

<br /><hr class="separator"/><br />

<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_IP_ADDRESS; ?></label> <input readonly="readonly" type="text" class="readonly" name="ip_address" size="12" maxlength="39" value="<?php echo $ip_address; ?>"/>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_TIME; ?></label> <input readonly="readonly" type="text" class="readonly" name="time" size="5" maxlength="250" value="<?php echo $time; ?>"/>
<p />
<label class='edit_comment'><?php echo CMTX_FIELD_LABEL_DATE; ?></label> <input readonly="readonly" type="text" class="readonly" name="date" size="12" maxlength="250" value="<?php echo $date; ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
<input type="button" class="button" name="delete" onclick="if(delete_confirmation()){window.location='index.php?page=manage_comments&action=delete&id=<?php echo $id . "&key=" . $_SESSION['cmtx_csrf_key']?>'};" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>"/>
<input type="button" class="button" name="spam" onclick="window.location='index.php?page=spam&id=<?php echo $id ?>';" title="<?php echo CMTX_BUTTON_SPAM; ?>" value="<?php echo CMTX_BUTTON_SPAM; ?>"/>
</form>

<p />

<a href="index.php?page=manage_comments"><?php echo CMTX_LINK_BACK; ?></a>