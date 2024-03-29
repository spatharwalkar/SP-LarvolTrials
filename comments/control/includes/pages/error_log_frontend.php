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

<h3><?php echo CMTX_TITLE_ERROR_LOG_FRONTEND; ?></h3>
<hr class="title"/>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$file = "../includes/logs/errors.log";
$handle = fopen($file,"w");
fputs($handle, "");
fclose($handle);
?>
<div class="success"><?php echo CMTX_MSG_LOG_RESET; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php
$data = file_get_contents('../includes/logs/errors.log');
?>

<p />

<form name="error_log_frontend" id="error_log_frontend" action="index.php?page=error_log_frontend" method="post">
<textarea name="frontend_errors" cols="" rows="15" style="width:100%"><?php echo $data; ?></textarea>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_RESET; ?>" value="<?php echo CMTX_BUTTON_RESET; ?>"/>
</form>

<p />

<a href="index.php?page=settings_error_reporting"><?php echo CMTX_LINK_BACK; ?></a>