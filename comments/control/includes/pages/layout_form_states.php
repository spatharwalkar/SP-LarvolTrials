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

<h3><?php echo CMTX_TITLE_FORM_STATES; ?></h3>
<hr class="title"/>

<?php

if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$state_name = cmtx_sanitize($_POST['state_name']);
$state_email = cmtx_sanitize($_POST['state_email']);
$state_website = cmtx_sanitize($_POST['state_website']);
$state_town = cmtx_sanitize($_POST['state_town']);
$state_country = cmtx_sanitize($_POST['state_country']);

mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$state_name' WHERE `title` = 'state_name'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$state_email' WHERE `title` = 'state_email'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$state_website' WHERE `title` = 'state_website'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$state_town' WHERE `title` = 'state_town'");
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '$state_country' WHERE `title` = 'state_country'");

?>
<div class="success"><?php echo CMTX_MSG_SAVED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<p />

<?php echo CMTX_DESC_LAYOUT_FORM_STATES; ?>

<p />

<form name="layout_form_states" id="layout_form_states" action="index.php?page=layout_form_states" method="post">
<label class='layout_form_states'><?php echo CMTX_FIELD_LABEL_NAME; ?></label>
<?php
$state_name = "<select name='state_name'>
<option value='normal'>" . CMTX_FIELD_VALUE_NORMAL . "</option>
<option value='disable'>" . CMTX_FIELD_VALUE_DISABLE . "</option>
<option value='hide'>" . CMTX_FIELD_VALUE_HIDE . "</option>
</select>";
$state_name = str_ireplace("'".cmtx_setting('state_name')."'", "'".cmtx_setting('state_name')."' selected='selected'", $state_name);
echo $state_name;
?>
<p />
<label class='layout_form_states'><?php echo CMTX_FIELD_LABEL_EMAIL; ?></label>
<?php
$state_email = "<select name='state_email'>
<option value='normal'>" . CMTX_FIELD_VALUE_NORMAL . "</option>
<option value='disable'>" . CMTX_FIELD_VALUE_DISABLE . "</option>
<option value='hide'>" . CMTX_FIELD_VALUE_HIDE . "</option>
</select>";
$state_email = str_ireplace("'".cmtx_setting('state_email')."'", "'".cmtx_setting('state_email')."' selected='selected'", $state_email);
echo $state_email;
?>
<p />
<label class='layout_form_states'><?php echo CMTX_FIELD_LABEL_WEBSITE; ?></label>
<?php
$state_website = "<select name='state_website'>
<option value='normal'>" . CMTX_FIELD_VALUE_NORMAL . "</option>
<option value='disable'>" . CMTX_FIELD_VALUE_DISABLE . "</option>
<option value='hide'>" . CMTX_FIELD_VALUE_HIDE . "</option>
</select>";
$state_website = str_ireplace("'".cmtx_setting('state_website')."'", "'".cmtx_setting('state_website')."' selected='selected'", $state_website);
echo $state_website;
?>
<p />
<label class='layout_form_states'><?php echo CMTX_FIELD_LABEL_TOWN; ?></label>
<?php
$state_town = "<select name='state_town'>
<option value='normal'>" . CMTX_FIELD_VALUE_NORMAL . "</option>
<option value='disable'>" . CMTX_FIELD_VALUE_DISABLE . "</option>
<option value='hide'>" . CMTX_FIELD_VALUE_HIDE . "</option>
</select>";
$state_town = str_ireplace("'".cmtx_setting('state_town')."'", "'".cmtx_setting('state_town')."' selected='selected'", $state_town);
echo $state_town;
?>
<p />
<label class='layout_form_states'><?php echo CMTX_FIELD_LABEL_COUNTRY; ?></label>
<?php
$state_country = "<select name='state_country'>
<option value='normal'>" . CMTX_FIELD_VALUE_NORMAL . "</option>
<option value='disable'>" . CMTX_FIELD_VALUE_DISABLE . "</option>
<option value='hide'>" . CMTX_FIELD_VALUE_HIDE . "</option>
</select>";
$state_country = str_ireplace("'".cmtx_setting('state_country')."'", "'".cmtx_setting('state_country')."' selected='selected'", $state_country);
echo $state_country;
?>
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>