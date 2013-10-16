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

<h3><?php echo CMTX_TITLE_SECURIMAGE; ?></h3>
<hr class="title"/>

<?php
if (extension_loaded('gd') && function_exists('imagettftext')) {
?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['enabled'])) { $captcha_type = 'securimage'; } else { $captcha_type = 'recaptcha'; }
$securimage_width = cmtx_sanitize($_POST['securimage_width']);
$securimage_height = cmtx_sanitize($_POST['securimage_height']);
$securimage_length = cmtx_sanitize($_POST['securimage_length']);
$securimage_perturbation = cmtx_sanitize($_POST['securimage_perturbation']);
$securimage_lines = cmtx_sanitize($_POST['securimage_lines']);
$securimage_noise = cmtx_sanitize($_POST['securimage_noise']);
$securimage_text_color = cmtx_sanitize($_POST['securimage_text_color']);
$securimage_line_color = cmtx_sanitize($_POST['securimage_line_color']);
$securimage_back_color = cmtx_sanitize($_POST['securimage_back_color']);
$securimage_noise_color = cmtx_sanitize($_POST['securimage_noise_color']);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$captcha_type' WHERE `title` = 'captcha_type'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_width' WHERE `title` = 'securimage_width'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_height' WHERE `title` = 'securimage_height'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_length' WHERE `title` = 'securimage_length'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_perturbation' WHERE `title` = 'securimage_perturbation'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_lines' WHERE `title` = 'securimage_lines'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_noise' WHERE `title` = 'securimage_noise'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_text_color' WHERE `title` = 'securimage_text_color'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_line_color' WHERE `title` = 'securimage_line_color'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_back_color' WHERE `title` = 'securimage_back_color'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$securimage_noise_color' WHERE `title` = 'securimage_noise_color'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_FORM_SECURIMAGE; ?>

<p />

<form name="layout_form_captchas_securimage" id="layout_form_captchas_securimage" action="index.php?page=layout_form_captchas_securimage" method="post">
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('captcha_type') == 'securimage') { ?> <input type="checkbox" checked="checked" name="enabled"/> <?php } else { ?> <input type="checkbox" name="enabled"/> <?php } ?>
<p />
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_WIDTH; ?></label> <input type="text" required name="securimage_width" size="1" maxlength="250" value="<?php echo cmtx_setting('securimage_width'); ?>"/>
<p />
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_HEIGHT; ?></label> <input type="text" required name="securimage_height" size="1" maxlength="250" value="<?php echo cmtx_setting('securimage_height'); ?>"/>
<p />
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_LENGTH; ?></label> <input type="text" required name="securimage_length" size="1" maxlength="250" value="<?php echo cmtx_setting('securimage_length'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_SECURIMAGE_LENGTH); ?>
<p />
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_PERTURBATION; ?></label> <input type="text" required name="securimage_perturbation" size="1" maxlength="250" value="<?php echo cmtx_setting('securimage_perturbation'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_SECURIMAGE_PERTURBATION); ?>
<p />
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_LINES; ?></label> <input type="text" required name="securimage_lines" size="1" maxlength="250" value="<?php echo cmtx_setting('securimage_lines'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_SECURIMAGE_LINES); ?>
<p />
<label class='layout_form_captchas_securimage_1'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_NOISE; ?></label> <input type="text" required name="securimage_noise" size="1" maxlength="250" value="<?php echo cmtx_setting('securimage_noise'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_SECURIMAGE_NOISE); ?>
<div class='sub-heading'><?php echo "Styling"; ?></div>
<label class='layout_form_captchas_securimage_2'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_TEXT_COLOR; ?></label> <input type="text" required name="securimage_text_color" size="6" maxlength="250" value="<?php echo cmtx_setting('securimage_text_color'); ?>"/>
<p />
<label class='layout_form_captchas_securimage_2'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_LINE_COLOR; ?></label> <input type="text" required name="securimage_line_color" size="6" maxlength="250" value="<?php echo cmtx_setting('securimage_line_color'); ?>"/>
<p />
<label class='layout_form_captchas_securimage_2'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_BACK_COLOR; ?></label> <input type="text" required name="securimage_back_color" size="6" maxlength="250" value="<?php echo cmtx_setting('securimage_back_color'); ?>"/>
<p />
<label class='layout_form_captchas_securimage_2'><?php echo CMTX_FIELD_LABEL_SECURIMAGE_NOISE_COLOR; ?></label> <input type="text" required name="securimage_noise_color" size="6" maxlength="250" value="<?php echo cmtx_setting('securimage_noise_color'); ?>"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>

<?php
} else {
echo CMTX_MSG_SECURIMAGE_UNABLE;
}
?>