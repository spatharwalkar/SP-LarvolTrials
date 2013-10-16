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

<h3><?php echo CMTX_TITLE_FORM_QUESTIONS; ?></h3>
<hr class="title"/>

<?php
if (isset($_GET['notice']) && $_GET['notice'] == "dismiss" && cmtx_check_csrf_url_key()) {
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '0' WHERE `title` = 'notice_layout_form_questions'");
} else {
if (cmtx_setting('notice_layout_form_questions')) { ?>
<div class="info"><?php echo CMTX_MSG_NOTICE_LAYOUT_FORM_QUESTIONS . " <a href='index.php?page=layout_form_questions&notice=dismiss&key=" . $_SESSION['cmtx_csrf_key'] . "'>" . CMTX_LINK_DISMISS . "</a>"; ?></div>
<div style="clear: left;"></div>
<?php } } ?>

<?php
if (isset($_POST['submit']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['submit'])) {

cmtx_check_csrf_form_key();

$question = $_POST['question'];
$answer = $_POST['answer'];

$question = cmtx_sanitize($question);
$answer = cmtx_sanitize($answer);

mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "questions` (`question`, `answer`) VALUES ('$question', '$answer');");
?>
<div class="success"><?php echo CMTX_MSG_QUESTION_ADDED; ?></div>
<div style="clear: left;"></div>
<?php } ?>

<?php
if (isset($_GET['action']) && $_GET['action'] == "delete" && isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_record_exists($_GET['id'], "questions") && cmtx_check_csrf_url_key()) {
if (cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else {
$id = $_GET['id'];
$id = cmtx_sanitize($id);
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "questions` WHERE `id` = '$id'");
?>
<div class="success"><?php echo CMTX_MSG_QUESTION_DELETED; ?></div>
<div style="clear: left;"></div>
<?php } } ?>

<?php
if (isset($_POST['bulk_delete']) && isset($_POST['bulk']) && cmtx_setting('is_demo')) {
?>
<div class="warning"><?php echo CMTX_MSG_DEMO; ?></div>
<div style="clear: left;"></div>
<?php
} else if (isset($_POST['bulk_delete']) && isset($_POST['bulk'])) {
cmtx_check_csrf_form_key();
$items = $_POST['bulk'];
$count = count($items);
for ($i = 0; $i < $count; $i++) {
	$id = $items[$i];
	$id = cmtx_sanitize($id);
	mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "questions` WHERE `id` = '$id'");
}
?>
<?php if ($count == 1) { ?><div class="success"><?php echo CMTX_MSG_QUESTION_BULK_DELETED; ?></div><?php } ?>
<?php if ($count > 1) { ?><div class="success"><?php printf(CMTX_MSG_QUESTIONS_BULK_DELETED, $count); ?></div><?php } ?>
<div style="clear: left;"></div>
<?php
}
?>

<p />

<form name="add_question" id="add_question" action="index.php?page=layout_form_questions" method="post">
<?php echo CMTX_FIELD_LABEL_QUESTION; ?> <input type="text" required name="question" size="50" maxlength="250"/>&nbsp;
<?php echo CMTX_FIELD_LABEL_ANSWER; ?> <input type="text" required name="answer" size="15" maxlength="250"/>&nbsp;
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="submit" title="<?php echo CMTX_BUTTON_ADD_QUESTION; ?>" value="<?php echo CMTX_BUTTON_ADD_QUESTION; ?>"/>
</form>

<br />

<form name="datatables" id="datatables" action="index.php?page=layout_form_questions" method="post">

<table id="data" class="display" summary="Questions">
    <thead>
    	<tr>
			<th style='width:0px;'><input type="checkbox" name="select_all" id="select_all" onclick="bulk_select();"/></th>
        	<th><?php echo CMTX_TABLE_QUESTION; ?></th>
            <th><?php echo CMTX_TABLE_ANSWER; ?></th>
            <th><?php echo CMTX_TABLE_ACTION; ?></th>
        </tr>
    </thead>
    <tbody>

<?php
$questions = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "questions`");
while ($question = mysql_fetch_assoc($questions)) {
?>
    	<tr>
			<td><input type="checkbox" name="bulk[]" value="<?php echo $question["id"]; ?>" onclick="bulk_check();"/></td>
        	<td><?php echo $question["question"]; ?></td>
            <td><?php echo $question["answer"]; ?></td>
			<td>
			<a href="<?php echo "index.php?page=edit_question&id=" . $question["id"];?>"><img src="images/buttons/edit.png" class="button_edit" title="Edit" alt="Edit"></a>
			<a href="<?php echo "index.php?page=layout_form_questions&action=delete&id=" . $question["id"] . "&key=" . $_SESSION['cmtx_csrf_key'];?>"><img src="images/buttons/delete.png" class="button_delete" onclick="return delete_confirmation()" title="Delete" alt="Delete"></a>
			</td>
        </tr>	
<?php } ?>

    </tbody>
</table>

<div style="clear: left;"></div>

<div style="margin-top:10px;"></div>

<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="bulk_delete" title="<?php echo CMTX_BUTTON_DELETE; ?>" value="<?php echo CMTX_BUTTON_DELETE; ?>" onclick="return delete_bulk_confirmation()"/>
</form>