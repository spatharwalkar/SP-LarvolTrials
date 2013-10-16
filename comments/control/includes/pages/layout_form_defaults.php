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

<h3><?php echo CMTX_TITLE_FORM_DEFAULTS; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$default_name = $_POST['default_name'];
$default_email = $_POST['default_email'];
$default_website = $_POST['default_website'];
$default_town = $_POST['default_town'];
$default_country = $_POST['cmtx_country'];
if ($default_country == "blank") { $default_country = ""; }
$default_rating = $_POST['cmtx_rating'];
if ($default_rating == "blank") { $default_rating = ""; }
$default_comment = $_POST['default_comment'];
if (isset($_POST['default_notify'])) { $default_notify = 1; } else { $default_notify = 0; }
if (isset($_POST['default_remember'])) { $default_remember = 1; } else { $default_remember = 0; }

$default_name_san = cmtx_sanitize($default_name);
$default_email_san = cmtx_sanitize($default_email);
$default_website_san = cmtx_sanitize($default_website);
$default_town_san = cmtx_sanitize($default_town);
$default_country_san = cmtx_sanitize($default_country);
$default_rating_san = cmtx_sanitize($default_rating);
$default_comment_san = cmtx_sanitize($default_comment);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_name_san' WHERE `title` = 'default_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_email_san' WHERE `title` = 'default_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_website_san' WHERE `title` = 'default_website'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_town_san' WHERE `title` = 'default_town'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_country_san' WHERE `title` = 'default_country'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_rating_san' WHERE `title` = 'default_rating'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_comment_san' WHERE `title` = 'default_comment'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_notify' WHERE `title` = 'default_notify'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$default_remember' WHERE `title` = 'default_remember'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_FORM_DEFAULTS; ?>

<p />

<?php require '../includes/language/' . cmtx_setting('language_frontend') . '/countries.php'; ?>
<?php require '../includes/language/' . cmtx_setting('language_frontend') . '/ratings.php'; ?>

<form name="layout_form_defaults" id="layout_form_defaults" action="index.php?page=layout_form_defaults" method="post">
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_NAME; ?></label> <input type="text" name="default_name" size="33" maxlength="250" value="<?php echo cmtx_setting('default_name'); ?>"/>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_EMAIL; ?></label> <input type="email" name="default_email" size="33" maxlength="250" value="<?php echo cmtx_setting('default_email'); ?>"/>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_WEBSITE; ?></label> <input type="text" name="default_website" size="33" maxlength="250" value="<?php echo cmtx_setting('default_website'); ?>"/>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_TOWN; ?></label> <input type="text" name="default_town" size="33" maxlength="250" value="<?php echo cmtx_setting('default_town'); ?>"/>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_COUNTRY; ?></label>
<?php
require '../includes/template/countries.php';
if (cmtx_setting('default_country')) {
	$cmtx_countries = str_ireplace('"'.cmtx_setting('default_country').'"', '"'.cmtx_setting('default_country').'" selected="selected"', $cmtx_countries);
}
echo $cmtx_countries;
?>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_RATING; ?></label>
<?php
require '../includes/template/ratings.php';
if (cmtx_setting('default_rating')) {
	$cmtx_ratings = str_ireplace('"'.cmtx_setting('default_rating').'"', '"'.cmtx_setting('default_rating').'" selected="selected"', $cmtx_ratings);
}
echo $cmtx_ratings;
?>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_COMMENT; ?></label> <textarea name="default_comment" cols="41" rows="6"><?php echo cmtx_setting('default_comment'); ?></textarea>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_NOTIFY; ?></label> <?php if (cmtx_setting('default_notify')) { ?> <input type="checkbox" checked="checked" name="default_notify"/> <?php } else { ?> <input type="checkbox" name="default_notify"/> <?php } ?>
<p />
<label class='layout_form_defaults'><?php echo CMTX_FIELD_LABEL_COOKIE; ?></label> <?php if (cmtx_setting('default_remember')) { ?> <input type="checkbox" checked="checked" name="default_remember"/> <?php } else { ?> <input type="checkbox" name="default_remember"/> <?php } ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>