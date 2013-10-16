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

<h3><?php echo CMTX_TITLE_TEXT_FINDER; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
}
?>

<p />

<?php echo CMTX_DESC_TOOL_TEXT_FINDER; ?>

<p />

<form name="text_finder" id="text_finder" action="index.php?page=tool_text_finder" method="post">
<input type="radio" checked="checked" name="location" value="1"/> <?php echo CMTX_FIELD_VALUE_FRONTEND; ?>
<br />
<input type="radio" name="location" value="0"/> <?php echo CMTX_FIELD_VALUE_BACKEND; ?>
<p />
<input type="radio" checked="checked" name="case" value="1"/> <?php echo CMTX_FIELD_VALUE_CASE_SENSITIVE; ?>
<br />
<input type="radio" name="case" value="0"/> <?php echo CMTX_FIELD_VALUE_CASE_INSENSITIVE; ?>
<p />
<?php echo CMTX_FIELD_LABEL_TEXT; ?> &nbsp; <input type="text" required name="text" size="20"/>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_SEARCH; ?>" value="<?php echo CMTX_BUTTON_SEARCH; ?>"/>
</form>

<p />

<?php
if (isset($_POST['submit']) && !cmtx_setting('is_demo')) {

cmtx_check_csrf_form_key();

?>
<div style='margin-bottom: 25px;'></div>
<h3><?php echo CMTX_TITLE_TEXT_FINDER_RESULT; ?></h3>
<hr class="title"/>
<?php
$text_found = false;
if (!empty($_POST['text'])) {
	if ($_POST['location']) {
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/comments.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/countries.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/form.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/page.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/processor.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/ratings.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/rss.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "../includes/language/" . cmtx_setting('language_frontend') . "/subscribers.php", $_POST['case']);
	} else {
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/buttons.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/dashboard.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/descriptions.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/fields.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/hints.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/links.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/login.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/menu.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/messages.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/notes.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/prompts.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/tables.php", $_POST['case']);
		cmtx_text_finder ($_POST['text'], "includes/language/" . cmtx_setting('language_backend') . "/titles.php", $_POST['case']);
	}
}
if (!$text_found) {
	echo CMTX_FIELD_VALUE_NOT_FOUND;
}
}
?>