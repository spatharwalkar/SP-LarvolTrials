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

define('IN_COMMENTICS', 'true');

//set the path
$cmtx_path = '';

/* Database Connection */
require 'includes/db/connect.php'; //connect to database
if (!$cmtx_db_ok) { die(); }

//load functions file
require 'includes/functions/page.php';

//load language file
require 'includes/language/' . cmtx_setting('language_frontend') . '/comments.php';

if (!cmtx_setting('show_flag')) {
	die();
}

if (!cmtx_is_administrator()) { //if not administrator
	if (cmtx_in_maintenance()) { //check if under maintenance
		die();
	}
}

/* Error Reporting */
cmtx_error_reporting('includes/logs/errors.log');

/* Time Zone */
cmtx_set_time_zone(cmtx_setting('time_zone'));

$ip_address = cmtx_get_ip_address(); //get user's IP address

echo "<img src='" . cmtx_comments_folder() . "images/buttons/flag.png' alt='Flag' title='" . CMTX_TITLE_FLAG . "'/>" . CMTX_FLAG;

if (isset($_POST['id'])) {

	$id = $_POST['id'];
	$id = str_ireplace('cmtx_flag_', '', $id);
	if (!ctype_digit($id)) { die(); }
	$id = cmtx_sanitize($id, true, true);

	//check if comment exists
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	$count = mysql_num_rows($query);
	if ($count == 0) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_NO_COMMENT) . "');</script>";
		die();
	}

	//check if user is reporting own comment
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id' AND `ip_address` = '$ip_address'");
	$count = mysql_num_rows($query);
	if ($count > 0) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_OWN_COMMENT) . "');</script>";
		die();
	}

	//check if user is reporting admin comment
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id' AND `is_admin` = '1'");
	$count = mysql_num_rows($query);
	if ($count > 0) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_ADMIN_COMMENT) . "');</script>";
		die();
	}

	//check if user is banned
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "bans` WHERE `ip_address` = '$ip_address'");
	$count = mysql_num_rows($query);
	if ($count > 0) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_BANNED) . "');</script>";
		die();
	}

	//check how many reports user has submitted
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `ip_address` = '$ip_address'");
	$count = mysql_num_rows($query);

	if ($count >= cmtx_setting('flag_max_per_user')) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_REPORT_LIMIT) . "');</script>";
		die();
	}

	//check if user has already reported this comment
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "reporters` WHERE `ip_address` = '$ip_address' AND `comment_id` = '$id'");
	$count = mysql_num_rows($query);

	if ($count > 0) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_ALREADY_REPORTED) . "');</script>";
		die();
	}

	//check if comment has already been flagged
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	$result = mysql_fetch_assoc($query);
	$count = $result["reports"];

	if ($count >= cmtx_setting('flag_min_per_comment')) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_ALREADY_FLAGGED) . "');</script>";
		die();
	}

	//check if comment has already been verified
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id' AND `is_verified` = '1'");
	$count = mysql_num_rows($query);

	if ($count > 0) {
		echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_ALREADY_VERIFIED) . "');</script>";
		die();
	}	


	//report comment

	mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `reports` = `reports` + 1 WHERE `id` = '$id'");
	mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "reporters` (`comment_id`, `ip_address`, `dated`) values ('$id', '$ip_address', NOW())");

	echo "<script language='javascript' type='text/javascript'>alert('" . cmtx_escape_js(CMTX_FLAG_REPORT_SENT) . "');</script>";


	//check if comment should be flagged
	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	$result = mysql_fetch_assoc($query);
	$count = $result["reports"];

	if ($count == cmtx_setting('flag_min_per_comment')) {

		if (cmtx_setting('flag_disapprove')) {
			mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "comments` SET `is_approved` = '0' WHERE `id` = '$id'");
			cmtx_unapprove_replies($id);
		}

		//send email

		$admin_new_comment_flag_email_file = 'includes/emails/' . cmtx_setting('language_frontend') . '/admin/new_flag.txt'; //build path to admin new flag email file
		$body = file_get_contents($admin_new_comment_flag_email_file); //get the file's contents

		$comment_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
		$comment_result = mysql_fetch_assoc($comment_query);

		$page_id = $comment_result["page_id"];

		$page_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '$page_id'");
		$page_result = mysql_fetch_assoc($page_query);

		$page_reference = cmtx_decode($page_result["reference"]);
		$page_url = cmtx_decode($page_result["url"]);
		$poster = cmtx_decode($comment_result["name"]);
		$comment = cmtx_prepare_comment_for_email($comment_result["comment"], false);
		$admin_link = cmtx_url_encode_spaces(cmtx_setting('url_to_comments_folder') . cmtx_setting('admin_folder')) . '/'; //build admin panel link

		//convert email variables with actual variables
		$body = str_ireplace('[page reference]', $page_reference, $body);
		$body = str_ireplace('[page url]', $page_url, $body);
		$body = str_ireplace('[poster]', $poster, $body);
		$body = str_ireplace('[comment]', $comment, $body);
		$body = str_ireplace('[admin link]', $admin_link, $body);
		$body = str_ireplace('[signature]', cmtx_setting('signature'), $body);

		//select administrators from database
		$admins = mysql_query("SELECT `email` FROM `" . $cmtx_mysql_table_prefix . "admins` WHERE `receive_email_new_flag` = '1' AND `is_enabled` = '1'");

		while ($admin = mysql_fetch_assoc($admins)) { //while there are administrators

			$email = $admin["email"]; //get administrator email address

			cmtx_email($email, null, cmtx_setting('admin_new_flag_subject'), $body, cmtx_setting('admin_new_flag_from_email'), cmtx_setting('admin_new_flag_from_name'), cmtx_setting('admin_new_flag_reply_to'));

		}

	}

}
?>