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

if (!isset($cmtx_identifier)) {
	$cmtx_identifier = '';
}

//to help transitioning to cmtx_identifier
if (isset($cmtx_page_id)) {
$cmtx_identifier = $cmtx_page_id;
}

$cmtx_temp = $cmtx_identifier;
unset($cmtx_identifier);
global $cmtx_identifier;
$cmtx_identifier = $cmtx_temp;

$cmtx_temp = $cmtx_reference;
unset($cmtx_reference);
global $cmtx_reference;
$cmtx_reference = $cmtx_temp;

$cmtx_temp = $cmtx_path;
unset($cmtx_path);
global $cmtx_path;
$cmtx_path = $cmtx_temp;

if (isset($cmtx_parameters)) {
	$cmtx_temp = $cmtx_parameters;
	unset($cmtx_parameters);
	global $cmtx_parameters;
	$cmtx_parameters = $cmtx_temp;
}

if (isset($cmtx_set_name_value)) {
	$cmtx_temp = $cmtx_set_name_value;
	unset($cmtx_set_name_value);
	global $cmtx_set_name_value;
	$cmtx_set_name_value = $cmtx_temp;
}

if (isset($cmtx_set_email_value)) {
	$cmtx_temp = $cmtx_set_email_value;
	unset($cmtx_set_email_value);
	global $cmtx_set_email_value;
	$cmtx_set_email_value = $cmtx_temp;
}

if (isset($cmtx_set_website_value)) {
	$cmtx_temp = $cmtx_set_website_value;
	unset($cmtx_set_website_value);
	global $cmtx_set_website_value;
	$cmtx_set_website_value = $cmtx_temp;
}

if (isset($cmtx_set_town_value)) {
	$cmtx_temp = $cmtx_set_town_value;
	unset($cmtx_set_town_value);
	global $cmtx_set_town_value;
	$cmtx_set_town_value = $cmtx_temp;
}

if (isset($cmtx_set_country_value)) {
	$cmtx_temp = $cmtx_set_country_value;
	unset($cmtx_set_country_value);
	global $cmtx_set_country_value;
	$cmtx_set_country_value = $cmtx_temp;
}

global $cmtx_name;

global $cmtx_url;

global $cmtx_page_id;

global $cmtx_mysql_table_prefix;

global $cmtx_db_orig;

global $cmtx_admin_button;

global $cmtx_bb_code_url_attribute;
global $cmtx_bb_code_email_attribute;

global $cmtx_default_name;
global $cmtx_default_email;
global $cmtx_default_website;
global $cmtx_default_town;
global $cmtx_default_country;
global $cmtx_default_rating;
global $cmtx_default_comment;
global $cmtx_default_notify;
global $cmtx_default_remember;
global $cmtx_default_privacy;
global $cmtx_default_terms;

global $cmtx_error;
global $cmtx_error_message;
global $cmtx_error_total;

global $cmtx_approve;
global $cmtx_approve_reason;

global $cmtx_is_admin;
global $cmtx_reply_id;

global $cmtx_loop_counter;
global $cmtx_comment_counter;
global $cmtx_offset;
global $cmtx_perm_counter;
global $cmtx_exit_loop;

//htmLawed
global $C;
global $S;

//AutoEmbed
global $AutoEmbed_stubs;

?>