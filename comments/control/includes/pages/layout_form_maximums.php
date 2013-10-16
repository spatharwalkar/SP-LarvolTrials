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

<h3><?php echo CMTX_TITLE_FORM_MAXIMUMS; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$field_maximum_name = $_POST['field_maximum_name'];
$field_maximum_email = $_POST['field_maximum_email'];
$field_maximum_website = $_POST['field_maximum_website'];
$field_maximum_town = $_POST['field_maximum_town'];
$comment_maximum_characters = $_POST['comment_maximum_characters'];
$field_maximum_question = $_POST['field_maximum_question'];
$field_maximum_captcha = $_POST['field_maximum_captcha'];

$field_maximum_name_san = cmtx_sanitize($field_maximum_name);
$field_maximum_email_san = cmtx_sanitize($field_maximum_email);
$field_maximum_website_san = cmtx_sanitize($field_maximum_website);
$field_maximum_town_san = cmtx_sanitize($field_maximum_town);
$comment_maximum_characters_san = cmtx_sanitize($comment_maximum_characters);
$field_maximum_question_san = cmtx_sanitize($field_maximum_question);
$field_maximum_captcha_san = cmtx_sanitize($field_maximum_captcha);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$field_maximum_name_san' WHERE `title` = 'field_maximum_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$field_maximum_email_san' WHERE `title` = 'field_maximum_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$field_maximum_website_san' WHERE `title` = 'field_maximum_website'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$field_maximum_town_san' WHERE `title` = 'field_maximum_town'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$comment_maximum_characters_san' WHERE `title` = 'comment_maximum_characters'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$field_maximum_question_san' WHERE `title` = 'field_maximum_question'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$field_maximum_captcha_san' WHERE `title` = 'field_maximum_captcha'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_FORM_MAXIMUMS; ?>

<p />

<form name="layout_form_maximums" id="layout_form_maximums" action="index.php?page=layout_form_maximums" method="post">
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_NAME; ?></label> <input type="text" required name="field_maximum_name" size="1" maxlength="250" value="<?php echo cmtx_setting('field_maximum_name'); ?>"/>
<p />
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_EMAIL; ?></label> <input type="text" required name="field_maximum_email" size="1" maxlength="250" value="<?php echo cmtx_setting('field_maximum_email'); ?>"/>
<p />
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_WEBSITE; ?></label> <input type="text" required name="field_maximum_website" size="1" maxlength="250" value="<?php echo cmtx_setting('field_maximum_website'); ?>"/>
<p />
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_TOWN; ?></label> <input type="text" required name="field_maximum_town" size="1" maxlength="250" value="<?php echo cmtx_setting('field_maximum_town'); ?>"/>
<p />
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_COMMENT; ?></label> <input type="text" required name="comment_maximum_characters" size="3" maxlength="250" value="<?php echo cmtx_setting('comment_maximum_characters'); ?>"/>
<p />
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_QUESTION; ?></label> <input type="text" required name="field_maximum_question" size="1" maxlength="250" value="<?php echo cmtx_setting('field_maximum_question'); ?>"/>
<p />
<label class='layout_form_maximums'><?php echo CMTX_FIELD_LABEL_CAPTCHA; ?></label> <input type="text" required name="field_maximum_captcha" size="1" maxlength="250" value="<?php echo cmtx_setting('field_maximum_captcha'); ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>