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
?>

<?php if (!defined('IN_COMMENTICS')) { die('Access Denied.'); } ?>

<?php if (cmtx_setting('show_reply') && cmtx_setting('scroll_reply')) { ?>
<script type="text/javascript">
// <![CDATA[
var ss = {
	fixAllLinks: function() {
		// Get a list of all links in the page
		var allLinks = document.getElementsByTagName('a');
		// Walk through the list
		for (var i=0;i<allLinks.length;i++) {
			var lnk = allLinks[i];
			if (lnk.href.indexOf('<?php echo CMTX_ANCHOR_FORM ?>') != -1) { //Commentics adjustment (1/2)
				if ((lnk.href && lnk.href.indexOf('#') != -1) && ( (lnk.pathname == location.pathname) || ('/'+lnk.pathname == location.pathname) ) && (lnk.search == location.search)) {
					// If the link is internal to the page (begins in #) then attach the smoothScroll function as an onclick event handler
					ss.addEvent(lnk,'click',ss.smoothScroll);
				}
			} //Commentics adjustment (2/2)
		}
	},

  smoothScroll: function(e) {
    // This is an event handler; get the clicked on element,
    // in a cross-browser fashion
    if (window.event) {
      target = window.event.srcElement;
    } else if (e) {
      target = e.target;
    } else return;

    // Make sure that the target is an element, not a text node
    // within an element
    if (target.nodeName.toLowerCase() != 'a') {
      target = target.parentNode;
    }
  
    // Paranoia; check this is an A tag
    if (target.nodeName.toLowerCase() != 'a') return;
  
    // Find the <a name> tag corresponding to this href
    // First strip off the hash (first character)
    anchor = target.hash.substr(1);
    // Now loop all A tags until we find one with that name
    var allLinks = document.getElementsByTagName('a');
    var destinationLink = null;
    for (var i=0;i<allLinks.length;i++) {
      var lnk = allLinks[i];
      if (lnk.name && (lnk.name == anchor)) {
        destinationLink = lnk;
        break;
      }
    }
    if (!destinationLink) destinationLink = document.getElementById(anchor);

    // If we didn't find a destination, give up and let the browser do
    // its thing
    if (!destinationLink) return true;
  
    // Find the destination's position
    var destx = destinationLink.offsetLeft; 
    var desty = destinationLink.offsetTop;
    var thisNode = destinationLink;
    while (thisNode.offsetParent && 
          (thisNode.offsetParent != document.body)) {
      thisNode = thisNode.offsetParent;
      destx += thisNode.offsetLeft;
      desty += thisNode.offsetTop;
    }
  
    // Stop any current scrolling
    clearInterval(ss.INTERVAL);
  
    cypos = ss.getCurrentYPos();
  
    ss_stepsize = parseInt((desty-cypos)/ss.STEPS);
    ss.INTERVAL =
setInterval('ss.scrollWindow('+ss_stepsize+','+desty+',"'+anchor+'")',10);
  
    // And stop the actual click happening
    if (window.event) {
      window.event.cancelBubble = true;
      window.event.returnValue = false;
    }
    if (e && e.preventDefault && e.stopPropagation) {
      e.preventDefault();
      e.stopPropagation();
    }
  },

  scrollWindow: function(scramount,dest,anchor) {
    wascypos = ss.getCurrentYPos();
    isAbove = (wascypos < dest);
    window.scrollTo(0,wascypos + scramount);
    iscypos = ss.getCurrentYPos();
    isAboveNow = (iscypos < dest);
    if ((isAbove != isAboveNow) || (wascypos == iscypos)) {
      // if we've just scrolled past the destination, or
      // we haven't moved from the last scroll (i.e., we're at the
      // bottom of the page) then scroll exactly to the link
      window.scrollTo(0,dest);
      // cancel the repeating timer
      clearInterval(ss.INTERVAL);
      // and jump to the link directly so the URL's right
      location.hash = anchor;
    }
  },

  getCurrentYPos: function() {
    if (document.body && document.body.scrollTop)
      return document.body.scrollTop;
    if (document.documentElement && document.documentElement.scrollTop)
      return document.documentElement.scrollTop;
    if (window.pageYOffset)
      return window.pageYOffset;
    return 0;
  },

  addEvent: function(elm, evType, fn, useCapture) {
    // addEvent and removeEvent
    // cross-browser event handling for IE5+, NS6 and Mozilla
    // By Scott Andrew
    if (elm.addEventListener){
      elm.addEventListener(evType, fn, useCapture);
      return true;
    } else if (elm.attachEvent){
      var r = elm.attachEvent("on"+evType, fn);
      return r;
    } else {
      alert("Handler could not be removed");
    }
  } 
}

ss.STEPS = <?php echo cmtx_setting('scroll_speed'); ?>;

ss.addEvent(window,"load",ss.fixAllLinks);
// ]]>
</script>
<?php } ?>

<?php if (cmtx_setting('show_like') || cmtx_setting('show_dislike') || cmtx_setting('show_flag')) { ?>
<script type="text/javascript">
// <![CDATA[
if (typeof jQuery == "undefined") {
document.write("<scr" + "ipt type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js\"></scr" + "ipt>");
}
// ]]>
</script>
<?php } ?>

<?php if (cmtx_setting('show_like') || cmtx_setting('show_dislike')) { ?>
<script type="text/javascript">
// <![CDATA[
jQuery(document).ready(function() {

jQuery(".cmtx_vote").click(function() {

var id = jQuery(this).attr("id");
var parent = jQuery(this);

if (id.indexOf('dislike') != -1) {
	var type = "dislike";
} else {
	var type = "like";
}


jQuery.ajax({
type: "POST",
url: "<?php echo cmtx_comments_folder() . "vote.php"?>",
data: {id: id, type: type},
cache: false,

success: function(html) {
	parent.html(html);
}

});

return false;

});
});
// ]]>
</script>
<?php } ?>

<?php if (cmtx_setting('show_flag')) { ?>
<script type="text/javascript">
// <![CDATA[
jQuery(document).ready(function() {

jQuery(".cmtx_flag").click(function() {

var proceed = true;

var answer = confirm('<?php echo cmtx_escape_js(CMTX_FLAG_CONFIRM) ?>');
if (!answer) { proceed = false; }

if (proceed) {

	var id = jQuery(this).attr("id");
	var parent = jQuery(this);

	jQuery.ajax({
	type: "POST",
	url: "<?php echo cmtx_comments_folder() . "flag.php"?>",
	data: {id: id},
	cache: false,

	success: function(html) {
		parent.html(html);
	}
	
	});
	
}

return false;

});
});
// ]]>
</script>
<?php } ?>

<?php if (cmtx_setting('show_read_more')) { ?>
<script type="text/javascript">
// <![CDATA[
function cmtx_read_more(id) {
document.getElementById("cmtx_comment_less_" + id).style.display = "none";
document.getElementById("cmtx_comment_more_" + id).style.display = "inline";
}
// ]]>
</script>
<?php } ?>

<?php
//Permalink (Calculation Only)
if (isset($_GET['cmtx_perm']) && ctype_digit($_GET['cmtx_perm'])) {

	global $cmtx_perm_counter;
	
	$cmtx_sort = cmtx_get_sort_by();
	
	$cmtx_comments_query = mysql_query("SELECT `id` FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `reply_to` = '0' AND `is_approved` = '1' AND `page_id` = '$cmtx_page_id' ORDER BY $cmtx_sort;");

	$cmtx_perm_counter = 0;
	$cmtx_exit_loop = false;

	while ($cmtx_comments = mysql_fetch_assoc($cmtx_comments_query)) {

		cmtx_calc_permalink($cmtx_comments["id"]);
		
		if ($cmtx_exit_loop) {
			break;
		}

	}
}
?>

<?php
//get number of approved comments for current page
$cmtx_number_of_comments = cmtx_number_of_comments();
?>

<h3 class="cmtx_comments_heading">
<a id="<?php echo str_ireplace("#", "", CMTX_ANCHOR_COMMENTS); ?>"></a>
<?php echo CMTX_COMMENTS_HEADING; ?>
<?php if (cmtx_setting('show_comment_count') && $cmtx_number_of_comments) { ?> <span class="cmtx_comments_count"><?php echo " (" . $cmtx_number_of_comments . ")";?></span> <?php } ?>
</h3>

<div class="cmtx_height_below_comments_heading"></div>

<?php
if ($cmtx_number_of_comments == 0) { //if no comments

	echo "<span class='cmtx_no_comments_text'>";
	echo CMTX_NO_COMMENTS;
	echo "</span>";

} else { //if there are comments



	/* *** Topic *** */
	if (cmtx_setting('show_topic')) {

		if (isset($cmtx_set_topic) && !empty($cmtx_set_topic)) {
			$cmtx_topic = cmtx_sanitize($cmtx_set_topic, true, false);
		} else {
			$cmtx_topic = cmtx_get_page_reference();
		}

		if (cmtx_setting('rich_snippets') && cmtx_setting('show_average_rating') && cmtx_average_rating() != 0) {
		
			$cmtx_rich_snippets = true;
		
			if (cmtx_setting('rich_snippets_markup') == "Microdata") {
				echo "<div itemscope='itemscope' itemtype='http://data-vocabulary.org/Review-aggregate'>";
				echo "<div class='cmtx_topic_block'>";
				echo "<span class='cmtx_topic_intro'>" . CMTX_TOPIC_INTRO . "</span>: ";
				echo "<span class='cmtx_topic_page' itemprop='itemreviewed'>" . $cmtx_topic . "</span>";
				echo "</div>";
			} else if (cmtx_setting('rich_snippets_markup') == "Microformats") {
				echo "<div class='hreview-aggregate'>";
				echo "<div class='cmtx_topic_block'>";
				echo "<span class='cmtx_topic_intro'>" . CMTX_TOPIC_INTRO . "</span>: ";
				echo "<span class='item'><span class='fn cmtx_topic_page'>" . $cmtx_topic . "</span></span>";
				echo "</div>";
			} else if (cmtx_setting('rich_snippets_markup') == "RDFa") {
				echo "<div xmlns:v='http://rdf.data-vocabulary.org/#' typeof='v:Review-aggregate'>";
				echo "<div class='cmtx_topic_block'>";
				echo "<span class='cmtx_topic_intro'>" . CMTX_TOPIC_INTRO . "</span>: ";
				echo "<span class='cmtx_topic_page' property='v:itemreviewed'>" . $cmtx_topic . "</span>";
				echo "</div>";
			}
		
		} else {
			echo "<div class='cmtx_topic_block'>";
			echo "<span class='cmtx_topic_intro'>" . CMTX_TOPIC_INTRO . "</span>: ";
			echo "<span class='cmtx_topic_page'>" . $cmtx_topic . "</span>";
			echo "</div>";
		}

	}


	
	/* *** Pagination (Calculation Only) *** */
	$cmtx_total_pages = ceil($cmtx_number_of_comments / cmtx_setting('comments_per_page'));

	if (isset($_GET['cmtx_page']) && ctype_digit($_GET['cmtx_page'])) {
		$cmtx_current_page = (int) $_GET['cmtx_page']; //get the current page
	} else {
		$cmtx_current_page = 1; //or set a default
	}

	if ($cmtx_current_page > $cmtx_total_pages) { //if current page is greater than total pages
	   $cmtx_current_page = $cmtx_total_pages; //set current page to last page
	}

	if ($cmtx_current_page < 1) { //if current page is less than first page
	   $cmtx_current_page = 1; //set current page to first page
	}

	$cmtx_offset = ($cmtx_current_page - 1) * cmtx_setting('comments_per_page'); //the offset of the list, based on current page



	/* *** Sort By *** */
	if (cmtx_setting('show_sort_by')) {

	echo "<div class='cmtx_sort_block'>";

	echo "<select id='cmtx_sort_by' title='" . CMTX_TITLE_SORT_BY . "' onchange='window.location.href = this.options[selectedIndex].value;'>";

	if (cmtx_setting('show_sort_by_1') && cmtx_setting('show_date')) {
		if ( (isset($_GET['cmtx_sort']) && $_GET['cmtx_sort'] == "1") || (!isset($_GET['cmtx_sort']) && cmtx_setting('comments_order') == "1") ) {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=1" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "' selected='selected'>". CMTX_SORT_1 . "</option>";
		} else {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=1" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "'>". CMTX_SORT_1 . "</option>";
		}
	}
	if (cmtx_setting('show_sort_by_2') && cmtx_setting('show_date')) {
		if ( (isset($_GET['cmtx_sort']) && $_GET['cmtx_sort'] == "2") || (!isset($_GET['cmtx_sort']) && cmtx_setting('comments_order') == "2") ) {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=2" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "' selected='selected'>". CMTX_SORT_2 . "</option>";
		} else {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=2" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "'>". CMTX_SORT_2 . "</option>";
		}
	}
	if (cmtx_setting('show_sort_by_3') && cmtx_setting('show_like')) {
		if ( (isset($_GET['cmtx_sort']) && $_GET['cmtx_sort'] == "3") || (!isset($_GET['cmtx_sort']) && cmtx_setting('comments_order') == "3") ) {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=3" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "' selected='selected'>". CMTX_SORT_3 . "</option>";
		} else {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=3" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "'>". CMTX_SORT_3 . "</option>";
		}
	}
	if (cmtx_setting('show_sort_by_4') && cmtx_setting('show_dislike')) {
		if ( (isset($_GET['cmtx_sort']) && $_GET['cmtx_sort'] == "4") || (!isset($_GET['cmtx_sort']) && cmtx_setting('comments_order') == "4") ) {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=4" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "' selected='selected'>". CMTX_SORT_4 . "</option>";
		} else {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=4" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "'>". CMTX_SORT_4 . "</option>";
		}
	}
	if (cmtx_setting('show_sort_by_5') && cmtx_setting('show_rating')) {
		if ( (isset($_GET['cmtx_sort']) && $_GET['cmtx_sort'] == "5") || (!isset($_GET['cmtx_sort']) && cmtx_setting('comments_order') == "5") ) {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=5" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "' selected='selected'>". CMTX_SORT_5 . "</option>";
		} else {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=5" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "'>". CMTX_SORT_5 . "</option>";
		}
	}
	if (cmtx_setting('show_sort_by_6') && cmtx_setting('show_rating')) {
		if ( (isset($_GET['cmtx_sort']) && $_GET['cmtx_sort'] == "6") || (!isset($_GET['cmtx_sort']) && cmtx_setting('comments_order') == "6") ) {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=6" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "' selected='selected'>". CMTX_SORT_6 . "</option>";
		} else {
			echo "<option value='" . cmtx_url_encode(strtok(cmtx_current_page(), "?") . "?cmtx_sort=6" . cmtx_get_query("sort") . CMTX_ANCHOR_COMMENTS) . "'>". CMTX_SORT_6 . "</option>";
		}
	}

	echo "</select>";

	echo "</div>";
	}



	if (cmtx_setting('show_topic') || cmtx_setting('show_sort_by')) {
	echo "<div style='clear: both;'></div>";
	echo "<div class='cmtx_height_below_sort_and_topic'></div>";
	}



	/* *** Average Rating *** */
	echo "<div class='cmtx_average_rating_block'>";
	if (cmtx_setting('show_average_rating')) {

	$cmtx_average_rating = cmtx_average_rating();

	$cmtx_output_average_rating = '';

	switch ($cmtx_average_rating) {
	case 1:
	$cmtx_output_average_rating .= cmtx_star_full_avg(1);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(4);
	break;
	case 1.5:
	$cmtx_output_average_rating .= cmtx_star_full_avg(1);
	$cmtx_output_average_rating .= cmtx_star_half_avg(1);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(3);
	break;
	case 2:
	$cmtx_output_average_rating .= cmtx_star_full_avg(2);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(3);
	break;
	case 2.5:
	$cmtx_output_average_rating .= cmtx_star_full_avg(2);
	$cmtx_output_average_rating .= cmtx_star_half_avg(1);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(2);
	break;
	case 3:
	$cmtx_output_average_rating .= cmtx_star_full_avg(3);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(2);
	break;
	case 3.5:
	$cmtx_output_average_rating .= cmtx_star_full_avg(3);
	$cmtx_output_average_rating .= cmtx_star_half_avg(1);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(1);
	break;
	case 4:
	$cmtx_output_average_rating .= cmtx_star_full_avg(4);
	$cmtx_output_average_rating .= cmtx_star_empty_avg(1);
	break;
	case 4.5:
	$cmtx_output_average_rating .= cmtx_star_full_avg(4);
	$cmtx_output_average_rating .= cmtx_star_half_avg(1);
	break;
	case 5:
	$cmtx_output_average_rating .= cmtx_star_full_avg(5);
	break;
	}

	if ($cmtx_average_rating != 0) {
		echo $cmtx_output_average_rating . " ";
		echo "<span class='cmtx_average_rating_text'>";
		if (isset($cmtx_rich_snippets)) {
		
			if (cmtx_setting('rich_snippets_markup') == "Microdata") {
				echo "<span itemprop='rating' itemscope='itemscope' itemtype='http://data-vocabulary.org/Rating'>";
				echo "<span itemprop='average'>" . $cmtx_average_rating . "</span>";
				echo "/";
				echo "<span itemprop='best'>5</span>";
				echo "</span>";
				echo " (<span itemprop='votes'>" . cmtx_number_of_ratings() . "</span>)";
				echo "</span>";
			} else if (cmtx_setting('rich_snippets_markup') == "Microformats") {
				echo "<span class='rating'>";
				echo "<span class='average'>" . $cmtx_average_rating . "</span>";
				echo "/";
				echo "<span class='best'>5</span>";
				echo "</span>";
				echo " (<span class='votes'>" . cmtx_number_of_ratings() . "</span>)";
				echo "</span>";
			} else if (cmtx_setting('rich_snippets_markup') == "RDFa") {
				echo "<span rel='v:rating'>";
				echo "<span typeof='v:Rating'>";
				echo "<span property='v:average'>" . $cmtx_average_rating . "</span>";
				echo "/";
				echo "<span property='v:best'>5</span>";
				echo "</span>";
				echo "</span>";
				echo " (<span property='v:votes'>" . cmtx_number_of_ratings() . "</span>)";
				echo "</span>";
			}
		
		} else {
			echo $cmtx_average_rating . "/5 (" . cmtx_number_of_ratings() . ")</span>";
		}
	}

	}
	echo "</div>";


	
	/* *** Pagination (Top) *** */
	echo "<div class='cmtx_pagination_block_top'>";
	if (cmtx_setting('enabled_pagination') && cmtx_setting('show_pagination_top') && $cmtx_total_pages > 1) {
		cmtx_paginate($cmtx_current_page, cmtx_setting('range_of_pages'), $cmtx_total_pages);
	}
	echo "</div>";



	/* *** Social *** */
	echo "<div class='cmtx_social_block'>";
	if (cmtx_setting('show_social')) {
	
	$cmtx_social_url = cmtx_url_encode_spaces(cmtx_get_page_url());
	$cmtx_social_title = cmtx_url_encode_spaces(cmtx_get_page_reference());
	
	$cmtx_social_url = str_ireplace("&amp;", "%26", $cmtx_social_url); //convert &amp; to %26
	$cmtx_social_title = str_ireplace("&amp;", "%26", $cmtx_social_title); //convert &amp; to %26

	$cmtx_social_attribute = ""; //initialize variable

	if (cmtx_setting('social_new_window')) {
		$cmtx_social_attribute = " target='_blank'";
	}

	echo "<div class='cmtx_social_images'>";

	if (cmtx_setting('show_social_facebook')) {
		echo "<a href='http://www.facebook.com/sharer.php?u=" . $cmtx_social_url . "&amp;t=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/facebook.png' class='cmtx_social_image' title='Facebook' alt='Facebook'/></a>";
	}
	if (cmtx_setting('show_social_delicious')) {
		echo "<a href='http://delicious.com/post?url=" . $cmtx_social_url . "&amp;title=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/delicious.png' class='cmtx_social_image' title='del.icio.us' alt='del.icio.us'/></a>";
	}
	if (cmtx_setting('show_social_stumbleupon')) {
		echo "<a href='http://www.stumbleupon.com/submit?url=" . $cmtx_social_url . "&amp;title=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/stumbleupon.png' class='cmtx_social_image' title='StumbleUpon' alt='StumbleUpon'/></a>";
	}
	if (cmtx_setting('show_social_digg')) {
		echo "<a href='http://digg.com/submit?phase=2&amp;url=" . $cmtx_social_url . "&amp;title=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/digg.png' class='cmtx_social_image' title='Digg' alt='Digg'/></a>";
	}
	if (cmtx_setting('show_social_technorati')) {
		echo "<a href='http://technorati.com/faves?add=" . $cmtx_social_url . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/technorati.png' class='cmtx_social_image' title='Technorati' alt='Technorati'/></a>";
	}
	if (cmtx_setting('show_social_google')) {
		echo "<a href='https://plus.google.com/share?url=" . $cmtx_social_url . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/google.png' class='cmtx_social_image' title='Google+' alt='Google+'/></a>";
	}
	if (cmtx_setting('show_social_reddit')) {
		echo "<a href='http://reddit.com/submit?url=" . $cmtx_social_url . "&amp;title=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/reddit.png' class='cmtx_social_image' title='Reddit' alt='Reddit'/></a>";
	}
	if (cmtx_setting('show_social_myspace')) {
		echo "<a href='http://www.myspace.com/Modules/PostTo/Pages/?u=" . $cmtx_social_url . "&amp;t=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/myspace.png' class='cmtx_social_image' title='MySpace' alt='MySpace'/></a>";
	}
	if (cmtx_setting('show_social_twitter')) {
		echo "<a href='http://twitter.com/home?status=" . $cmtx_social_title . "%20-%20" . $cmtx_social_url . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/twitter.png' class='cmtx_social_image' title='Twitter' alt='Twitter'/></a>";
	}
	if (cmtx_setting('show_social_linkedin')) {
		echo "<a href='http://www.linkedin.com/shareArticle?mini=true&amp;url=" . $cmtx_social_url . "&amp;title=" . $cmtx_social_title . "' rel='nofollow'$cmtx_social_attribute><img src='" . cmtx_comments_folder() . "images/social/linkedin.png' class='cmtx_social_image' title='LinkedIn' alt='LinkedIn'/></a>";
	}

	echo "</div>";
	}
	echo "</div>";



	echo "<div style='clear: both;'></div>";



	/* *** Comments *** */
	echo "<div class='cmtx_height_above_comment_boxes'></div>";

	$cmtx_sort = cmtx_get_sort_by();

	$cmtx_comments_query = mysql_query("SELECT `id` FROM `" . $cmtx_mysql_table_prefix . "comments` WHERE `reply_to` = '0' AND `is_approved` = '1' AND `page_id` = '$cmtx_page_id' ORDER BY $cmtx_sort;"); //get comments from database

	$cmtx_loop_counter = 0;
	$cmtx_comment_counter = 0;
	$cmtx_exit_loop = false;
	
	while ($cmtx_comments = mysql_fetch_assoc($cmtx_comments_query)) { //while there are comments

		cmtx_get_comment_and_replies($cmtx_comments["id"]);

		if ($cmtx_exit_loop) {
			break;
		}

	}

	echo "<div class='cmtx_height_below_comment_boxes'></div>";



	/* *** RSS *** */
	echo "<div class='cmtx_rss_block'>";
	if (cmtx_setting('rss_enabled')) {
	if (cmtx_setting('show_rss_this_page') || cmtx_setting('show_rss_all_pages')) {
	if (cmtx_setting('show_rss_this_page')) { ?>
	<a href="<?php echo cmtx_comments_folder() . "rss.php?id=" . $cmtx_page_id;?>" rel="nofollow"><img src="<?php echo cmtx_comments_folder() . "images/misc/rss.jpg";?>" class="cmtx_rss_image" title="<?php echo CMTX_TITLE_RSS_THIS; ?>" alt="RSS"/></a>
	<a href="<?php echo cmtx_comments_folder() . "rss.php?id=" . $cmtx_page_id;?>" title="<?php echo CMTX_TITLE_RSS_THIS; ?>" rel="nofollow"><?php echo CMTX_RSS_THIS_PAGE ?></a>
	&nbsp;
	<?php }
	if (cmtx_setting('show_rss_all_pages')) { ?>
	<a href="<?php echo cmtx_comments_folder() . "rss.php";?>" rel="nofollow"><img src="<?php echo cmtx_comments_folder() . "images/misc/rss.jpg";?>" class="cmtx_rss_image" title="<?php echo CMTX_TITLE_RSS_ALL; ?>" alt="RSS"/></a>
	<a href="<?php echo cmtx_comments_folder() . "rss.php";?>" title="<?php echo CMTX_TITLE_RSS_ALL; ?>" rel="nofollow"><?php echo CMTX_RSS_ALL_PAGES ?></a>
	<?php }
	}
	}
	echo "</div>";



	/* *** Pagination (Bottom) *** */
	echo "<div class='cmtx_pagination_block_bottom'>";
	if (cmtx_setting('enabled_pagination') && cmtx_setting('show_pagination_bottom') && $cmtx_total_pages > 1) {
		cmtx_paginate($cmtx_current_page, cmtx_setting('range_of_pages'), $cmtx_total_pages);
	}
	echo "</div>";



	/* *** Page Number *** */
	echo "<div class='cmtx_page_number_block'>";
	if (cmtx_setting('show_page_number')) { //if enabled
		echo "<span class='cmtx_page_number_text'>";
		if (cmtx_setting('enabled_pagination')) { //if pagination enabled
			printf(CMTX_INFO_PAGE, $cmtx_current_page, $cmtx_total_pages); //display page number
		}
		echo "</span>";
	}
	echo "</div>";



	if (isset($cmtx_rich_snippets)) {
		echo "</div>";
	}

}

echo "<div style='clear: left;'></div>";