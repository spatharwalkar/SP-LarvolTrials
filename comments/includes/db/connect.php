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

@$cmtx_db_orig = mysql_query("SELECT DATABASE();");
@$cmtx_db_orig = mysql_result($cmtx_db_orig, 0);

require_once 'details.php'; //load database details
require_once 'failure.php'; //load failure messages

$cmtx_db_ok = true;

if (!empty($cmtx_mysql_port)) {
	$cmtx_mysql_host .= ":" . $cmtx_mysql_port;
}

@$cmtx_connection = mysql_connect ($cmtx_mysql_host, $cmtx_mysql_username, $cmtx_mysql_password);
if (!$cmtx_connection) {
	if (defined("CMTX_IN_INSTALLER") || defined("CMTX_IN_ADMIN")) {
		cmtx_db_error_connect();
	} else {
		cmtx_db_error_general();
	}
	$cmtx_db_ok = false;
	return;
}

@$cmtx_database = mysql_select_db ($cmtx_mysql_database);
if (!$cmtx_database) {
	if (defined("CMTX_IN_INSTALLER") || defined("CMTX_IN_ADMIN")) {
		cmtx_db_error_select();
	} else {
		cmtx_db_error_general();
	}
	$cmtx_db_ok = false;
	return;
}

if (mysql_num_rows(mysql_query("SHOW TABLES LIKE '" . $cmtx_mysql_table_prefix . "comments'")) == 0) {
	if (defined("CMTX_IN_ADMIN")) {
		cmtx_db_error_table();
		$cmtx_db_ok = false;
		return;
	} else if (defined("CMTX_IN_INSTALLER")) {
	} else {
		cmtx_db_error_general();
		$cmtx_db_ok = false;
		return;
	}
}

if (function_exists('mysql_set_charset')) {
	mysql_set_charset('utf8');
} else {
	mysql_query("SET NAMES 'UTF8'");
}

?>