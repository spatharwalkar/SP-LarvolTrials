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

<h3><?php echo CMTX_TITLE_RECAPTCHA; ?></h3>
<hr class="title"/>

<?php
if (function_exists('fsockopen') && is_callable('fsockopen')) {
?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['enabled'])) { $captcha_type = 'recaptcha'; } else { $captcha_type = 'securimage'; }
$recaptcha_public_key = $_POST['recaptcha_public_key'];
$recaptcha_private_key = $_POST['recaptcha_private_key'];
$recaptcha_theme = $_POST['recaptcha_themes'];
$recaptcha_language = $_POST['recaptcha_languages'];

$recaptcha_public_key_san = cmtx_sanitize($recaptcha_public_key);
$recaptcha_private_key_san = cmtx_sanitize($recaptcha_private_key);
$recaptcha_theme_san = cmtx_sanitize($recaptcha_theme);
$recaptcha_language_san = cmtx_sanitize($recaptcha_language);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$captcha_type' WHERE `title` = 'captcha_type'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$recaptcha_public_key_san' WHERE `title` = 'recaptcha_public_key'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$recaptcha_private_key_san' WHERE `title` = 'recaptcha_private_key'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$recaptcha_theme_san' WHERE `title` = 'recaptcha_theme'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$recaptcha_language_san' WHERE `title` = 'recaptcha_language'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_FORM_RECAPTCHA; ?>

<p />

<form name="layout_form_captchas_recaptcha" id="layout_form_captchas_recaptcha" action="index.php?page=layout_form_captchas_recaptcha" method="post">
<label class='layout_form_captchas_recaptcha'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('captcha_type') == 'recaptcha') { ?> <input type="checkbox" checked="checked" name="enabled"/> <?php } else { ?> <input type="checkbox" name="enabled"/> <?php } ?>
<p />
<label class='layout_form_captchas_recaptcha'><?php echo CMTX_FIELD_LABEL_RECAPTCHA_PUBLIC_KEY; ?></label> <input type="text" name="recaptcha_public_key" size="55" maxlength="250" value="<?php echo cmtx_setting('recaptcha_public_key'); ?>"/>
<p />
<label class='layout_form_captchas_recaptcha'><?php echo CMTX_FIELD_LABEL_RECAPTCHA_PRIVATE_KEY; ?></label> <input type="text" name="recaptcha_private_key" size="55" maxlength="250" value="<?php echo cmtx_setting('recaptcha_private_key'); ?>"/>
<p />
<label class='layout_form_captchas_recaptcha'><?php echo CMTX_FIELD_LABEL_RECAPTCHA_THEME; ?></label>
<?php
$recaptcha_themes = "<select name='recaptcha_themes'>
<option value='red'>red</option>
<option value='white'>white</option>
<option value='blackglass'>blackglass</option>
<option value='clean'>clean</option>
</select>";
$recaptcha_themes = str_ireplace("'".cmtx_setting('recaptcha_theme')."'", "'".cmtx_setting('recaptcha_theme')."' selected='selected'", $recaptcha_themes);
echo $recaptcha_themes;
?>
<p />
<label class='layout_form_captchas_recaptcha'><?php echo CMTX_FIELD_LABEL_RECAPTCHA_LANGUAGE; ?></label>
<?php
$recaptcha_languages = "<select name='recaptcha_languages'>
<option value='en'>english</option>
<option value='nl'>dutch</option>
<option value='fr'>french</option>
<option value='de'>german</option>
<option value='pt'>portuguese</option>
<option value='ru'>russian</option>
<option value='es'>spanish</option>
<option value='tr'>turkish</option>
</select>";
$recaptcha_languages = str_ireplace("'".cmtx_setting('recaptcha_language')."'", "'".cmtx_setting('recaptcha_language')."' selected='selected'", $recaptcha_languages);
echo $recaptcha_languages;
?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>

<?php
} else {
echo CMTX_MSG_RECAPTCHA_UNABLE;
}
?>