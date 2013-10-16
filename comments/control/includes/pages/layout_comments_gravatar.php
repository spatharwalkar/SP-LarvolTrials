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

<h3><?php echo CMTX_TITLE_COMMENTS_GRAVATAR; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

if (isset($_POST['show_gravatar'])) { $show_gravatar = 1; } else { $show_gravatar = 0; }
$gravatar_default = $_POST['gravatar_defaults'];
$gravatar_custom = $_POST['gravatar_custom'];
$gravatar_size = $_POST['gravatar_size'];
$gravatar_rating = $_POST['gravatar_ratings'];

$gravatar_default_san = cmtx_sanitize($gravatar_default);
$gravatar_custom_san = cmtx_sanitize($gravatar_custom);
$gravatar_size_san = cmtx_sanitize($gravatar_size);
$gravatar_rating_san = cmtx_sanitize($gravatar_rating);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$show_gravatar' WHERE `title` = 'show_gravatar'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$gravatar_default_san' WHERE `title` = 'gravatar_default'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$gravatar_custom_san' WHERE `title` = 'gravatar_custom'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$gravatar_size_san' WHERE `title` = 'gravatar_size'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$gravatar_rating_san' WHERE `title` = 'gravatar_rating'");
?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_COMMENTS_GRAVATAR; ?>

<p />

<form name="layout_comments_gravatar" id="layout_comments_gravatar" action="index.php?page=layout_comments_gravatar" method="post">
<label class='layout_comments_gravatar'><?php echo CMTX_FIELD_LABEL_ENABLED; ?></label> <?php if (cmtx_setting('show_gravatar')) { ?> <input type="checkbox" checked="checked" name="show_gravatar"/> <?php } else { ?> <input type="checkbox" name="show_gravatar"/> <?php } ?>
<p />
<label class='layout_comments_gravatar'><?php echo CMTX_FIELD_LABEL_GRAVATAR_DEFAULT; ?></label>
<?php
$gravatar_defaults = "<select name='gravatar_defaults' id='gravatar_defaults' onchange=\"show_hide('gravatar');\">
<option value='default'>default</option>
<option value='custom'>custom</option>
<option value='mm'>mm</option>
<option value='identicon'>identicon</option>
<option value='monsterid'>monsterid</option>
<option value='wavatar'>wavatar</option>
<option value='retro'>retro</option>
</select>";
$gravatar_defaults = str_ireplace("'".cmtx_setting('gravatar_default')."'", "'".cmtx_setting('gravatar_default')."' selected='selected'", $gravatar_defaults);
echo $gravatar_defaults;
?>
<?php cmtx_generate_hint(CMTX_HINT_GRAVATAR_DEFAULT); ?>
<div id="gravatar_custom" <?php if (cmtx_setting('gravatar_default') != "custom") { echo "style='display:none;'"; } ?> >
<p />
<label class='layout_comments_gravatar'><?php echo CMTX_FIELD_LABEL_GRAVATAR_CUSTOM; ?></label> <input type="text" required name="gravatar_custom" size="35" maxlength="250" value="<?php echo cmtx_setting('gravatar_custom'); ?>"/>
<?php cmtx_generate_hint(CMTX_HINT_GRAVATAR_CUSTOM); ?>
</div>
<p />
<label class='layout_comments_gravatar'><?php echo CMTX_FIELD_LABEL_GRAVATAR_SIZE; ?></label> <input type="text" required name="gravatar_size" size="1" maxlength="250" value="<?php echo cmtx_setting('gravatar_size'); ?>"/>
<span class='note'><?php echo CMTX_NOTE_PIXELS; ?></span>
<p />
<label class='layout_comments_gravatar'><?php echo CMTX_FIELD_LABEL_GRAVATAR_RATING; ?></label>
<?php
$gravatar_ratings = "<select name='gravatar_ratings'>
<option value='g'>g</option>
<option value='pg'>pg</option>
<option value='r'>r</option>
<option value='x'>x</option>
</select>";
$gravatar_ratings = str_ireplace("'".cmtx_setting('gravatar_rating')."'", "'".cmtx_setting('gravatar_rating')."' selected='selected'", $gravatar_ratings);
echo $gravatar_ratings;
?>
<?php cmtx_generate_hint(CMTX_HINT_GRAVATAR_RATING); ?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>