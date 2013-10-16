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
require 'includes/language/' . cmtx_setting('language_frontend') . '/rss.php';

if (!cmtx_setting('rss_enabled')) {
	die(CMTX_RSS_FEATURE_DISABLED);
}

if (!cmtx_is_administrator()) { //if not administrator
	if (cmtx_in_maintenance()) { //check if under maintenance
		die();
	}
}

header('Content-Type:text/xml; charset=utf-8');

/* Error Reporting */
cmtx_error_reporting('includes/logs/errors.log');

/* Time Zone */
cmtx_set_time_zone(cmtx_setting('time_zone'));

if (isset($_GET['id']) && ctype_digit($_GET['id']) && cmtx_strlen($_GET['id']) < 10) { //if page ID is in URL and it validates

	$id = (int) $_GET['id'];
	$id = cmtx_sanitize($id, true, true);
	$query = "SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `is_approved` = '1' AND `page_id` = '$id' ORDER BY `dated` DESC"; //get page's items

} else {
	$query = "SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `is_approved` = '1' ORDER BY `dated` DESC"; //get all items
}

/* Last Build Date */
$lbd_query = $query . " LIMIT 1";
$lbd_query = mysql_query($lbd_query);
if (mysql_num_rows($lbd_query)) {
$lbd_result = mysql_fetch_assoc($lbd_query);
$last_build_date = date("r", strtotime($lbd_result["dated"]));
}

/* Most Recent */
if (cmtx_setting('rss_most_recent_enabled')) {
	$query .= " LIMIT " . cmtx_setting('rss_most_recent_amount');
}

$result = mysql_query($query);

echo 
'<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
	<channel>
		<title>' . cmtx_encode(cmtx_setting('rss_title')) . '</title>
		<link>' . cmtx_url_encode(cmtx_setting('rss_link')) . '</link>
		<description>' . cmtx_encode(cmtx_setting('rss_description')) . '</description>
		<language>' . cmtx_setting('rss_language') . '</language>';
		if (isset($last_build_date)) {
		echo '
		<lastBuildDate>' . $last_build_date . '</lastBuildDate>';
		}
		echo '
		<generator>Commentics</generator>
		<ttl>60</ttl>'; //time to live
		if (cmtx_setting('rss_image_enabled')) {
		echo '
		<image>
			<url>' . cmtx_url_encode(cmtx_setting('rss_image_url')) . '</url>
			<title>' . cmtx_encode(cmtx_setting('rss_title')) . '</title>
			<link>' . cmtx_url_encode(cmtx_setting('rss_link')) . '</link>
			<width>' . cmtx_setting('rss_image_width') . '</width>
			<height>' . cmtx_setting('rss_image_height') . '</height>
		</image>';
		}

		while ($comments = mysql_fetch_assoc($result)) {
		$pages_query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "pages` WHERE `id` = '" . $comments["page_id"] . "'");
		$pages = mysql_fetch_assoc($pages_query);
		$title = $comments["name"] . CMTX_RSS_COMMENT_POSTER;
		$link = $pages["url"];
		$comment = $comments["comment"];
		$dated = date("r", strtotime($comments["dated"]));
		$guid = $comments["id"];
		echo '
		<item>
			<title>' . $title . '</title>
			<link>' . $link . '</link>
			<description><![CDATA[' . $comment . ']]></description>
			<pubDate>' . $dated . '</pubDate>
			<guid isPermaLink="false">' . $guid . '</guid>
		</item>';
		}

	echo '
	</channel>
</rss>';
?>