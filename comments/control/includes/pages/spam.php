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

<h3><?php echo CMTX_TITLE_SPAM; ?></h3>
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
$id = $_GET['id'];
$id = cmtx_sanitize($id);
$comment_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
$comment_result = mysql_fetch_assoc($comment_query);
$name = $comment_result["name"];
$email = $comment_result["email"];
$website = $comment_result["website"];
?>

<p />

<form name="spam" id="spam" action="index.php?page=manage_comments" method="post">
<input type="hidden" name="id" value="<?php echo $id; ?>"/>
<input type="radio" checked="checked" name="delete" value="delete_this"/> <?php echo CMTX_FIELD_VALUE_SPAM_DELETE_THIS; ?>
<br />
<input type="radio" name="delete" value="delete_all"/> <?php echo CMTX_FIELD_VALUE_SPAM_DELETE_ALL; ?>
<p />
<input type="radio" checked="checked" name="ban" value="do_ban"/> <?php echo CMTX_FIELD_VALUE_SPAM_DO_BAN; ?>
<br />
<input type="radio" name="ban" value="no_ban"/> <?php echo CMTX_FIELD_VALUE_SPAM_NO_BAN; ?>
<p />
<input type="checkbox" name="ban_name"/> <?php printf(CMTX_FIELD_VALUE_SPAM_NAME, $name); ?>
<?php if (!empty($email)) { ?>
<br />
<input type="checkbox" name="ban_email"/> <?php printf(CMTX_FIELD_VALUE_SPAM_EMAIL, $email); ?>
<?php } ?>
<?php if ($website != "http://") { ?>
<br />
<input type="checkbox" name="ban_website"/> <?php printf(CMTX_FIELD_VALUE_SPAM_WEBSITE, $website); ?>
<?php } ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="spam" title="<?php echo CMTX_BUTTON_CONFIRM; ?>" value="<?php echo CMTX_BUTTON_CONFIRM; ?>"/>
</form>

<p />

<a href="index.php?page=edit_comment&id=<?php echo $id ?>"><?php echo CMTX_LINK_BACK; ?></a>