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


function cmtx_get_comment_and_replies($id) {

	global $cmtx_mysql_table_prefix, $cmtx_page_id, $cmtx_loop_counter, $cmtx_comment_counter, $cmtx_offset, $cmtx_exit_loop; //globalise variables

	$cmtx_loop_counter++; //increase loop counter by 1

	if (!cmtx_setting('enabled_pagination')) { //if pagination is disabled

			if ($cmtx_comment_counter != 0) { //don't display on first run
				echo "<div class='cmtx_height_between_comments'></div>";
			}

			//switch box number each time
			if ($cmtx_comment_counter % 2 == 0) {
				$alternate = 1;
			} else {
				$alternate = 2;
			}

			//get the details of the comment
			$comments_q = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
			$comments = mysql_fetch_assoc($comments_q);

			//display comment
			echo cmtx_generate_comment (false, $alternate, $comments["id"], $comments["name"], $comments["email"], $comments["website"], $comments["town"], $comments["country"], $comments["rating"], $comments["reply_to"], $comments["comment"], $comments["reply"], $comments["is_admin"], $comments["likes"], $comments["dislikes"], $comments["is_sticky"], $comments["is_locked"], $comments["dated"]);

			$cmtx_comment_counter++; //increase comment counter by 1

	} else { //apply pagination

		if ($cmtx_loop_counter > $cmtx_offset + (cmtx_setting('comments_per_page') + 1)) { //if the page's comments have already been displayed
			$cmtx_exit_loop = true; //exit the loop to save on performance
		}

		if ($cmtx_loop_counter > $cmtx_offset && $cmtx_loop_counter < $cmtx_offset + (cmtx_setting('comments_per_page') + 1)) { //skip unwanted comments

			if ($cmtx_comment_counter != 0) { //don't display on first run
				echo "<div class='cmtx_height_between_comments'></div>";
			}

			//switch box number each time
			if ($cmtx_comment_counter % 2 == 0) {
				$alternate = 1;
			} else {
				$alternate = 2;
			}

			//get the details of the comment
			$comments_q = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
			$comments = mysql_fetch_assoc($comments_q);
	
			//display comment
			echo cmtx_generate_comment (false, $alternate, $comments["id"], $comments["name"], $comments["email"], $comments["website"], $comments["town"], $comments["country"], $comments["rating"], $comments["reply_to"], $comments["comment"], $comments["reply"], $comments["is_admin"], $comments["likes"], $comments["dislikes"], $comments["is_sticky"], $comments["is_locked"], $comments["dated"]);

			$cmtx_comment_counter++; //increase comment counter by 1

		}

	}

	if (cmtx_comment_has_reply($id)) { //if the comment has a reply

		//get all of its replies
		$reply_q = mysql_query("SELECT `id` FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `reply_to` = '$id' AND `is_approved` = '1' AND `page_id` = '$cmtx_page_id' ORDER BY `dated` ASC");

		while ($replies = mysql_fetch_assoc($reply_q)) { //while there are replies

			cmtx_get_comment_and_replies($replies["id"]); //re-call this function to display the reply AND any replies it may have

		}

	}

} //end of get-comment-and-replies function


function cmtx_comment_has_reply($id) {

	global $cmtx_mysql_table_prefix; //globalise variables

	if (mysql_num_rows(mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `reply_to` = '$id' AND `is_approved` = '1'"))) {
		return true;
	} else {
		return false;
	}
	
} //end of comment-has-reply function


function cmtx_get_reply_to($id) {

	global $cmtx_mysql_table_prefix; //globalise variables

	$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$id'");
	$result = mysql_fetch_assoc($query);

	return $result["reply_to"];

} //end of get-reply-to function


function cmtx_get_reply_depth($id) {

	global $cmtx_mysql_table_prefix; //globalise variables

	$reply_depth = 0;
	$reply_to = cmtx_get_reply_to($id);

	if ($reply_to != 0) {
		$reply_depth++;
	}

	while ($reply_to != 0) {

		$query = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `id` = '$reply_to' AND `is_approved` = '1'");
		$result = mysql_fetch_assoc($query);

		if (mysql_num_rows($query)) {

			$reply_to = $result["reply_to"];

			if ($reply_to != 0) {
				$reply_depth++;
			}

		}

	}

	return $reply_depth;

} //end of get-reply-depth function


function cmtx_generate_comment ($is_preview, $alternate, $id, $name, $email, $website, $town, $country, $rating, $reply_to, $comment, $reply, $is_admin, $likes, $dislikes, $is_sticky, $is_locked, $dated) { //generate comment

	$cmtx_box = ""; //initialise box

	for ($i = 1; $i <= cmtx_get_reply_depth($id); $i++) {
		if (cmtx_setting('reply_arrow') && $i == cmtx_get_reply_depth($id)) {
			$cmtx_box .= "<div class='cmtx_reply_arrow'>"; //add the reply arrow
		}
		$cmtx_box .= '<div class="cmtx_reply_indent">'; //indent the reply
	}

	$perm = "";
	if (isset($_GET['cmtx_perm']) && ctype_digit($_GET['cmtx_perm'])) {
		$cmtx_perm = (int) $_GET['cmtx_perm'];
		if ($cmtx_perm == $id) {
			$perm = " cmtx_permalink_box";
		}
	}

	if ($alternate == 1) { //if it's the first box

		if (!$reply_to && !$is_admin) {
			$cmtx_box .= "<div class='cmtx_comment_box_1$perm' id='cmtx_perm_$id'>"; //comment and not admin
		} else if ($reply_to && !$is_admin) {
			$cmtx_box .= "<div class='cmtx_reply_box_1$perm' id='cmtx_perm_$id'>"; //reply and not admin
		} else if (!$reply_to && $is_admin) {
			$cmtx_box .= "<div class='cmtx_admin_comment_box_1$perm' id='cmtx_perm_$id'>"; //comment and is admin
		} else if ($reply_to && $is_admin) {
			$cmtx_box .= "<div class='cmtx_admin_reply_box_1$perm' id='cmtx_perm_$id'>"; //reply and is admin
		}

	} else { //if it's the second box

		if (!$reply_to && !$is_admin) {
			$cmtx_box .= "<div class='cmtx_comment_box_2$perm' id='cmtx_perm_$id'>"; //comment and not admin
		} else if ($reply_to && !$is_admin) {
			$cmtx_box .= "<div class='cmtx_reply_box_2$perm' id='cmtx_perm_$id'>"; //reply and not admin
		} else if (!$reply_to && $is_admin) {
			$cmtx_box .= "<div class='cmtx_admin_comment_box_2$perm' id='cmtx_perm_$id'>"; //comment and is admin
		} else if ($reply_to && $is_admin) {
			$cmtx_box .= "<div class='cmtx_admin_reply_box_2$perm' id='cmtx_perm_$id'>"; //reply and is admin
		}

	}

	//Sticky (1/2)
	if ($is_sticky) {
		$cmtx_box .= "<div class='cmtx_sticky_image'>";
	}

	//Gravatar (1/2)
	if (cmtx_setting('show_gravatar')) {
		$cmtx_box .= "<div class='cmtx_gravatar_block'>";
		$gravatar_parameter = "&amp;r=" . cmtx_setting('gravatar_rating');
		if (cmtx_setting('gravatar_default') != "default") {
			if (cmtx_setting('gravatar_default') == "custom") {
				$gravatar_parameter .= "&amp;d=" . cmtx_url_encode(cmtx_setting('gravatar_custom'));
			} else {
				$gravatar_parameter .= "&amp;d=" . cmtx_setting('gravatar_default');
			}
		}
		$cmtx_box .= "<img src='http://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . ".png?s=" . cmtx_setting('gravatar_size') . $gravatar_parameter . "' alt='Gravatar' title='Gravatar'/>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div style='clear: right;'></div>";
		$cmtx_box .= "<div style='margin-left:" . (cmtx_setting('gravatar_size') + 5) . "px;'>";
	}

	//Rating
	if (cmtx_setting('show_rating') && $rating != 0) {
		$cmtx_box .= "<div class='cmtx_rating_block'>";
		if ($rating == 1) {
			$cmtx_box .= cmtx_star_full(1);
			$cmtx_box .= cmtx_star_empty(4);
		} else if ($rating == 2) {
			$cmtx_box .= cmtx_star_full(2);
			$cmtx_box .= cmtx_star_empty(3);
		} else if ($rating == 3) {
			$cmtx_box .= cmtx_star_full(3);
			$cmtx_box .= cmtx_star_empty(2);
		} else if ($rating == 4) {
			$cmtx_box .= cmtx_star_full(4);
			$cmtx_box .= cmtx_star_empty(1);
		} else if ($rating == 5) {
			$cmtx_box .= cmtx_star_full(5);
		}
		$cmtx_box .= "</div>";
	}

	//Name and Website
	if (cmtx_setting('show_website') && !empty($website) && $website != "http://") {
		$cmtx_website_attribute = ""; //initialize variable
		if (cmtx_setting('website_new_window')) { $cmtx_website_attribute = " target='_blank'"; } //if website should open in new window
		if (cmtx_setting('website_nofollow')) { $cmtx_website_attribute .= " rel='nofollow'";	} //if website should contain nofollow tag
		$cmtx_box .= "<a class='cmtx_name_with_website_text' href='" . $website . "'$cmtx_website_attribute>" . $name . "</a>";
	} else {
		$cmtx_box .= "<span class='cmtx_name_without_website_text'>";
		$cmtx_box .= $name;
		$cmtx_box .= "</span>";
	}

	//Town and Country
	if (cmtx_setting('show_town') && !empty($town) && cmtx_setting('show_country') && !empty($country)) {
		$cmtx_box .= "<span class='cmtx_town_country_text'>";
		$cmtx_box .= " (" . $town . ", " . $country . ")";
		$cmtx_box .= "</span>";
	} else if (cmtx_setting('show_town') && !empty($town)) {
		$cmtx_box .= "<span class='cmtx_town_country_text'>";
		$cmtx_box .= " (" . $town . ")";
		$cmtx_box .= "</span>";
	} else if (cmtx_setting('show_country') && !empty($country)) {
		$cmtx_box .= "<span class='cmtx_town_country_text'>";
		$cmtx_box .= " (" . $country . ")";
		$cmtx_box .= "</span>";
	}

	//Says...
	if (cmtx_setting('show_says')) {
		$cmtx_box .= "<span class='cmtx_says_text'>";
		$cmtx_box .= " " . CMTX_SAYS;
		$cmtx_box .= "</span>";
	}

	$cmtx_box .= "<div class='cmtx_height_above_comment_text'></div>";

	//Comment
	$cmtx_box .= "<div class='cmtx_comment_text'>";
	if (cmtx_setting('show_read_more') && !$is_preview && cmtx_strlen(strip_tags($comment)) > cmtx_setting('read_more_limit')) {
		$comment_less = str_ireplace("<br />", " ", $comment);
		$comment_less = str_ireplace("<br/>", " ", $comment_less);
		$comment_less = str_ireplace("<br>", " ", $comment_less);
		$comment_less = str_ireplace("<p></p>", " ", $comment_less);
		$comment_less = str_ireplace("<p />", " ", $comment_less);
		$comment_less = str_ireplace("<p/>", " ", $comment_less);
		$comment_less = strip_tags($comment_less);
		$comment_cut = substr($comment_less, 0, cmtx_setting('read_more_limit'));
		$comment_less = substr($comment_cut, 0, strrpos($comment_cut, " "));
		$cmtx_box .= "<div id='cmtx_comment_less_" . $id . "'>";
		$cmtx_box .= $comment_less;
		$cmtx_box .= " <a href='" . cmtx_url_encode(cmtx_current_page()) . "' class='cmtx_read_more_link' title='" . CMTX_TITLE_READ_MORE . "' rel='nofollow' onclick='cmtx_read_more($id);return false;'>" . CMTX_READ_MORE . "</a>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div id='cmtx_comment_more_" . $id . "' style='display:none;'>";
		$cmtx_box .= $comment;
		$cmtx_box .= "</div>";
	} else {
		$cmtx_box .= $comment;
	}
	$cmtx_box .= "</div>";

	//Admin Reply
	if (!empty($reply)) {
		$cmtx_box .= "<div class='cmtx_height_above_reply_text'></div>";
		$cmtx_box .= "<div class='cmtx_reply_intro'>";
		$cmtx_box .= CMTX_REPLY_INTRO;
		$cmtx_box .= "</div>";
		$cmtx_box .= " ";
		$cmtx_box .= "<div class='cmtx_reply_text'>";
		$cmtx_box .= $reply;
		$cmtx_box .= "</div>";
	}

	$cmtx_box .= "<div class='cmtx_height_below_comment_text'></div>";

	//Preview Message
	if ($is_preview) {
		$cmtx_box .= "<div class='cmtx_preview_text'>";
		$cmtx_box .= CMTX_PREVIEW_TEXT;
		$cmtx_box .= "</div>";
	}

	$cmtx_box .= "<div class='cmtx_buttons_block'>";

	//Reply
	if (cmtx_setting('show_reply') && !$is_preview) {
		$cmtx_box .= "<div class='cmtx_reply_block'>";
		$cmtx_box .= "<div class='cmtx_buttons'>";
		if (cmtx_get_reply_depth($id) < cmtx_setting('reply_depth') && !$is_locked) {
			$cmtx_box .= "<a href='" . cmtx_url_encode(cmtx_current_page() . CMTX_ANCHOR_FORM) . "' id='cmtx_reply_" . $id . "' class='cmtx_reply_enabled' title='" . CMTX_TITLE_REPLY . "' rel='nofollow' onclick='";
			if (cmtx_setting('hide_form')) {
				$cmtx_box .= "cmtx_open_form();";
			}
			$cmtx_box .= "document.getElementById(\"cmtx_hide_reply\").style.display=\"block\";";
			$cmtx_box .= "document.getElementById(\"cmtx_reply_id\").value=\"" . $id . "\";";
			$cmtx_box .= "document.getElementById(\"cmtx_reply_message\").innerHTML=\"" . cmtx_define(CMTX_REPLY_MESSAGE) . " " . $name . '. ' . "\";";
			$cmtx_box .= "document.getElementById(\"cmtx_reset_reply\").style.display=\"inline\"'>";
			$cmtx_box .= "<img src='" . cmtx_comments_folder() . "images/buttons/reply.png' alt='Reply' title='" . CMTX_TITLE_REPLY . "'/>" . CMTX_REPLY . "</a>";
		} else {
			$cmtx_box .= "<a href='" . cmtx_url_encode(cmtx_current_page()) . "' id='cmtx_reply_" . $id . "' class='cmtx_reply_disabled' title='' rel='nofollow' onclick='return false;'>";
			$cmtx_box .= "<img src='" . cmtx_comments_folder() . "images/buttons/reply.png' alt='Reply' title=''/>" . CMTX_REPLY . "</a>";
		}
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
	}

	//Permalink
	if (cmtx_setting('show_permalink') && !$is_preview) {
		$cmtx_box .= "<div class='cmtx_permalink_block'>";
		$cmtx_box .= "<div class='cmtx_buttons'>";
		$cmtx_box .= "<a class='cmtx_permalink' href='" . cmtx_get_permalink($id) . "' id='cmtx_permalink_" . $id . "' title='" . CMTX_TITLE_PERMALINK . "' rel='nofollow'><img src='" . cmtx_comments_folder() . "images/buttons/permalink.png' alt='Permalink' title='" . CMTX_TITLE_PERMALINK . "'/>" . CMTX_PERMALINK . "</a>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
	}

	//Flag
	if (cmtx_setting('show_flag') && !$is_preview) {
		$cmtx_box .= "<div class='cmtx_flag_block'>";
		$cmtx_box .= "<div class='cmtx_buttons'>";
		$cmtx_box .= "<a class='cmtx_flag' href='" . cmtx_url_encode(cmtx_current_page()) . "' id='cmtx_flag_" . $id . "' title='" . CMTX_TITLE_FLAG . "' rel='nofollow'><img src='" . cmtx_comments_folder() . "images/buttons/flag.png' alt='Flag' title='" . CMTX_TITLE_FLAG . "'/>" . CMTX_FLAG . "</a>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
	}

	//Like/Dislike
	if ((cmtx_setting('show_like') || cmtx_setting('show_dislike')) && !$is_preview) {
		$cmtx_box .= "<div class='cmtx_like_block'>";
		$cmtx_box .= "<div class='cmtx_buttons'>";
		if (cmtx_setting('show_like')) {
			$cmtx_box .= "<a class='cmtx_vote cmtx_like' href='" . cmtx_url_encode(cmtx_current_page()) . "' id='cmtx_like_" . $id . "' title='" . CMTX_TITLE_LIKE . "' rel='nofollow'><img src='" . cmtx_comments_folder() . "images/buttons/like.png' alt='Like' title='" . CMTX_TITLE_LIKE . "'/>" . $likes . "</a>";
		}
		if (cmtx_setting('show_dislike')) {
			$cmtx_box .= "<a class='cmtx_vote cmtx_dislike' href='" . cmtx_url_encode(cmtx_current_page()) . "' id='cmtx_dislike_" . $id . "' title='" . CMTX_TITLE_DISLIKE . "' rel='nofollow'><img src='" . cmtx_comments_folder() . "images/buttons/dislike.png' alt='Dislike' title='" . CMTX_TITLE_DISLIKE . "'/>" . $dislikes . "</a>";
		}
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
	}

	$cmtx_box .= "</div>";

	//Date
	if (cmtx_setting('show_date')) {
		$cmtx_box .= "<div class='cmtx_date_text'>";
		if (date("Y-m-d", strtotime($dated)) == date("Y-m-d")) { //if comment's date is today
			$cmtx_box .= CMTX_TODAY . " " . date(cmtx_setting('time_format'), strtotime($dated));
		} else if (date("Y-m-d", strtotime($dated)) == date("Y-m-d", mktime(date("H"), date("i"), date("s"), date("m"), date("d")-1, date("Y")))) { //if comment's date is yesterday
			$cmtx_box .= CMTX_YESTERDAY . " " . date(cmtx_setting('time_format'), strtotime($dated));
		} else {
			$cmtx_box .= date(cmtx_setting('date_time_format'), strtotime($dated));
		}
		$cmtx_box .= "</div>";
	}

	//Sticky (2/2)
	if ($is_sticky) {
		$cmtx_box .= "</div>";
	}

	//Gravatar (2/2)
	if (cmtx_setting('show_gravatar')) {
		$cmtx_box .= "</div>";
	}

	$cmtx_box .= "</div>"; //end div

	for ($i = 1; $i <= cmtx_get_reply_depth($id); $i++) {
		if (cmtx_setting('reply_arrow') && $i == cmtx_get_reply_depth($id)) {
			$cmtx_box .= "</div>";
		}
		$cmtx_box .= "</div>";
	}

	return $cmtx_box;

} //end of generate-comment function


function cmtx_paginate ($current_page, $range_of_pages, $total_pages) { //display pagination

	if ($current_page > 1) { //if not on page 1
		echo " <a href='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_page=1" . cmtx_get_query("page") . CMTX_ANCHOR_COMMENTS) . "' title='" . CMTX_TITLE_PAG_FIRST . "'>" . CMTX_PAGINATION_FIRST . "</a> "; // show link to go back to page 1
		$previous_page = $current_page - 1; //get previous page number
		echo " <a href='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_page=$previous_page" . cmtx_get_query("page") . CMTX_ANCHOR_COMMENTS) . "' title='" . CMTX_TITLE_PAG_PREVIOUS . "'>" . CMTX_PAGINATION_PREVIOUS . "</a> "; //show link to go back 1 page
	}

	for ($x = ($current_page - $range_of_pages); $x < (($current_page + $range_of_pages) + 1); $x++) { //loop to show links to range of pages around current page
		if (($x > 0) && ($x <= $total_pages)) { //if it's a valid page number
			if ($x == $current_page) { //if we're on current page
				echo " $x "; //show it but don't make it a link
			} else { //if not current page
				echo " <a href='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_page=$x" . cmtx_get_query("page") . CMTX_ANCHOR_COMMENTS) . "' title='$x'>$x</a> "; //make it a link
			}
		}
	}

	if ($current_page != $total_pages) { //if not on last page, show forward and last page links
		$next_page = $current_page + 1; //get next page number
		echo " <a href='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_page=$next_page" . cmtx_get_query("page") . CMTX_ANCHOR_COMMENTS) . "' title='" . CMTX_TITLE_PAG_NEXT . "'>" . CMTX_PAGINATION_NEXT . "</a> "; //display forward link for next page 
		echo " <a href='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_page=$total_pages" . cmtx_get_query("page") . CMTX_ANCHOR_COMMENTS) . "' title='" . CMTX_TITLE_PAG_LAST . "'>" . CMTX_PAGINATION_LAST . "</a> "; //display forward link for last page
	}

} //end of paginate function


function cmtx_star_full ($amount) { //star full

	$star_full = '';

	for ($counter=1; $counter<=$amount; $counter++) {
		$star_full .= "<img src='" . cmtx_comments_folder() . "images/stars/star_full.png' title='" . CMTX_TITLE_FULL_STAR . "' alt='Full Star' class='cmtx_star_image'/>";
	}

	return $star_full;

} //end of star-full function


function cmtx_star_empty ($amount) { //star empty

	$star_empty = '';

	for ($counter=1; $counter<=$amount; $counter++) {
		$star_empty .= "<img src='" . cmtx_comments_folder() . "images/stars/star_empty.png' title='" . CMTX_TITLE_EMPTY_STAR . "' alt='Empty Star' class='cmtx_star_image'/>";
	}

	return $star_empty;

} //end of star-empty function


function cmtx_star_full_avg ($amount) { //star full for average rating

	$star_full = '';

	for ($counter=1; $counter<=$amount; $counter++) {
		$star_full .= "<img src='" . cmtx_comments_folder() . "images/stars/star_full.png' title='" . CMTX_TITLE_FULL_STAR . "' alt='Full Star' class='cmtx_star_image_avg'/>";
	}

	return $star_full;

} //end of star-full-avg function


function cmtx_star_half_avg ($amount) { //star half for average rating

	$star_half = '';

	for ($counter=1; $counter<=$amount; $counter++) {
		$star_half .= "<img src='" . cmtx_comments_folder() . "images/stars/star_half.png' title='" . CMTX_TITLE_HALF_STAR . "' alt='Half Star' class='cmtx_star_image_avg'/>";
	}

	return $star_half;

} //end of star-half-avg function


function cmtx_star_empty_avg ($amount) { //star empty for average rating

	$star_empty = '';

	for ($counter=1; $counter<=$amount; $counter++) {
		$star_empty .= "<img src='" . cmtx_comments_folder() . "images/stars/star_empty.png' title='" . CMTX_TITLE_EMPTY_STAR . "' alt='Empty Star' class='cmtx_star_image_avg'/>";
	}

	return $star_empty;

} //end of star-empty-avg function


function cmtx_number_of_comments() { //get total number of comments

	global $cmtx_mysql_table_prefix, $cmtx_page_id; //globalise variables

	$result = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `is_approved` = '1' AND `page_id` = '$cmtx_page_id'");

	$total = mysql_num_rows($result);

	return $total;

} //end of number_of_comments function


function cmtx_number_of_ratings() { //get total number of ratings

	global $cmtx_mysql_table_prefix, $cmtx_page_id; //globalise variables

	$result = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `page_id` = '$cmtx_page_id' AND `rating` != '0' AND `is_approved` = '1'");

	$total = mysql_num_rows($result);

	return $total;

} //end of number_of_ratings function


function cmtx_average_rating() { //get average rating

	global $cmtx_mysql_table_prefix, $cmtx_page_id; //globalise variables

	$result = mysql_query("SELECT AVG(rating) FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `is_approved` = '1' AND `rating` != '0' AND `page_id` = '$cmtx_page_id'");

	$average = mysql_fetch_assoc($result);

	$average = round($average["AVG(rating)"] / 0.5) * 0.5;

	return $average;

} //end of average-rating function


function cmtx_get_permalink($id) { //build the permalink

	preg_match("/cmtx_sort=[0-9]/", $_SERVER['REQUEST_URI'], $match);

	if (!empty($match[0])) {
		$sort = $match[0] . "&amp;";
	} else {
		$sort = "";
	}

	$url = cmtx_get_page_url();

	if (strstr($url, '?') && strstr($url, '=')) {
		$url .= "&amp;" . $sort . "cmtx_perm=" . $id . "#cmtx_perm_" . $id;
	} else {
		$url .= "?" . $sort . "cmtx_perm=" . $id . "#cmtx_perm_" . $id;
	}

	return $url;

} //end of get-permalink function


function cmtx_calc_permalink($id) { //calculate the page of the permalink

	global $cmtx_mysql_table_prefix, $cmtx_page_id, $cmtx_perm_counter, $cmtx_exit_loop;

	$cmtx_perm_counter ++;

	$cmtx_perm = (int) $_GET['cmtx_perm'];

	if ($cmtx_perm == $id) {
		$cmtx_page = ceil($cmtx_perm_counter / cmtx_setting('comments_per_page'));
		$_GET['cmtx_page'] = strval($cmtx_page);
		$cmtx_exit_loop = true; //exit the loop to save on performance
	}

	if (cmtx_comment_has_reply($id)) {

		//get all of its replies
		$reply_q = mysql_query("SELECT `id` FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `reply_to` = '$id' AND `is_approved` = '1' AND `page_id` = '$cmtx_page_id' ORDER BY `dated` ASC");

		while ($replies = mysql_fetch_assoc($reply_q)) { //while there are replies

			cmtx_calc_permalink($replies["id"]); //re-call this function to calculate the reply AND any replies it may have

		}

	}

} //end of calc-permalink function


function cmtx_get_sort_by() { //get Sort By or set default

	if (isset($_GET['cmtx_sort'])) {
		switch ($_GET['cmtx_sort']) {
			case "1":
			$cmtx_sort = "`is_sticky` DESC, `dated` DESC"; //newest
			break;
			case "2":
			$cmtx_sort = "`is_sticky` DESC, `dated` ASC"; //oldest
			break;
			case "3":
			$cmtx_sort = "`is_sticky` DESC, `likes` DESC"; //likes
			break;
			case "4":
			$cmtx_sort = "`is_sticky` DESC, `dislikes` DESC"; //dislikes
			break;
			case "5":
			$cmtx_sort = "`is_sticky` DESC, `rating` DESC"; //positive
			break;
			case "6":
			$cmtx_sort = "`is_sticky` DESC, `rating` = 0, `rating` ASC"; //critical
			break;
			default:
			if (cmtx_setting('comments_order') == "1") {
				$cmtx_sort = "`is_sticky` DESC, `dated` DESC"; //newest
			} else if (cmtx_setting('comments_order') == "2") {
				$cmtx_sort = "`is_sticky` DESC, `dated` ASC"; //oldest
			} else if (cmtx_setting('comments_order') == "3") {
				$cmtx_sort = "`is_sticky` DESC, `likes` DESC"; //likes
			} else if (cmtx_setting('comments_order') == "4") {
				$cmtx_sort = "`is_sticky` DESC, `dislikes` DESC"; //dislikes
			} else if (cmtx_setting('comments_order') == "5") {
				$cmtx_sort = "`is_sticky` DESC, `rating` DESC"; //positive
			} else if (cmtx_setting('comments_order') == "6") {
				$cmtx_sort = "`is_sticky` DESC, `rating` = 0, `rating` ASC"; //critical
			}
		}
	} else {
			if (cmtx_setting('comments_order') == "1") {
				$cmtx_sort = "`is_sticky` DESC, `dated` DESC"; //newest
			} else if (cmtx_setting('comments_order') == "2") {
				$cmtx_sort = "`is_sticky` DESC, `dated` ASC"; //oldest
			} else if (cmtx_setting('comments_order') == "3") {
				$cmtx_sort = "`is_sticky` DESC, `likes` DESC"; //likes
			} else if (cmtx_setting('comments_order') == "4") {
				$cmtx_sort = "`is_sticky` DESC, `dislikes` DESC"; //dislikes
			} else if (cmtx_setting('comments_order') == "5") {
				$cmtx_sort = "`is_sticky` DESC, `rating` DESC"; //positive
			} else if (cmtx_setting('comments_order') == "6") {
				$cmtx_sort = "`is_sticky` DESC, `rating` = 0, `rating` ASC"; //critical
			}
	}

	return $cmtx_sort;

} //end of get-sort-by function
?>