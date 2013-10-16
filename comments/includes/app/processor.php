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

if (isset($_POST['cmtx_submit']) || isset($_POST['cmtx_sub']) || isset($_POST['cmtx_preview']) || isset($_POST['cmtx_prev'])) { //if data submitted
	
	if (!cmtx_is_form_enabled(false)) { //if form is disabled
		return; //exit file
	}
	
	define('CMTX_PROCESSING', '1'); //define that the form was submitted
	
	$cmtx_ip_address = cmtx_get_ip_address(); //get user's IP address
	
	cmtx_check_if_banned(); //check if user is banned
	
	//initialise a few variables
	$cmtx_approve = false;
	$cmtx_approve_reason = "";
	
	$cmtx_error = false;
	$cmtx_error_message = "";
	$cmtx_error_total = 0;
	
	/* Security Key */
	if (!isset($_POST['cmtx_security_key'])) { //no security key submitted
		cmtx_ban(CMTX_BAN_REASON_NO_SECURITY_KEY); //ban user for no security key
	} else { //if security key submitted
		if (cmtx_setting('security_key') != $_POST['cmtx_security_key']) { //security key incorrect
			cmtx_ban(CMTX_BAN_REASON_INCORRECT_SECURITY_KEY); //ban user for incorrect security key
		}
	}
	
	/* Resubmit Key */
	if (!isset($_POST['cmtx_resubmit_key'])) { //no resubmit key submitted
		cmtx_ban(CMTX_BAN_REASON_NO_RESUBMIT_KEY); //ban user for no resubmit key
	} else { //if resubmit key submitted
		if (!ctype_alnum($_POST['cmtx_resubmit_key']) || cmtx_strlen($_POST['cmtx_resubmit_key']) != 20) { //if resubmit key invalid
			cmtx_ban(CMTX_BAN_REASON_INVALID_RESUBMIT_KEY); //ban user for invalid resubmit key
		}
	}
	
	/* Check Honeypot */
	if (cmtx_setting('check_honeypot')) { //if honeypot-check enabled
		if (!isset($_POST['cmtx_honeypot'])) { //if honeypot not submitted
			cmtx_error(CMTX_ERROR_MESSAGE_MISSING_DATA); //reject user for no honeypot
		} else { //if honeypot submitted
			if (!empty($_POST['cmtx_honeypot'])) { //if honeypot has a value
				cmtx_error(CMTX_ERROR_MESSAGE_HONEYPOT); //reject user for completing honeypot
			}
		}
	}
	
	/* Check Time */
	if (cmtx_setting('check_time')) { //if time-check enabled
		if (!isset($_POST['cmtx_time'])) { //if time not submitted
			cmtx_error(CMTX_ERROR_MESSAGE_MISSING_DATA); //reject user for no time
		} else { //if time submitted
			if ((time() - intval($_POST['cmtx_time'])) < 5) { //if user took less than 5 seconds to submit form
				cmtx_error(CMTX_ERROR_MESSAGE_MIN_TIME); //reject user for taking less than 5 seconds
			}
		}
	}
	
	/* Check Referrer */
	if (cmtx_setting('check_referrer')) {
		if (isset($_SERVER['HTTP_REFERER'])) { //if referrer available
			$cmtx_referrer = cmtx_url_decode($_SERVER['HTTP_REFERER']); //get referrer
			$cmtx_real_url = cmtx_url_decode(str_ireplace("www.", "", parse_url(cmtx_current_page(), PHP_URL_HOST))); //get host of page URL
			if (!empty($cmtx_real_url) && !preg_match('/\.[0-9]+\./i', $cmtx_real_url)) { //if not empty and URL is not an IP address
				if (!stristr($cmtx_referrer, $cmtx_real_url)) { //if referrer does not contain host of page URL
					cmtx_error(CMTX_ERROR_MESSAGE_INCORRECT_REFERRER); //reject user for incorrect referrer
				}
			}
		} else {
			cmtx_error(CMTX_ERROR_MESSAGE_NO_REFERRER); //reject user for no referrer
		}
	}
	
	/* Flood Control Delay */
	if (cmtx_setting('flood_control_delay_enabled') && !$cmtx_is_admin) { //if 'flood control delay' enabled and user is not admin
		cmtx_flood_control_delay();
	}
	
	/* Flood Control Maximum */
	if (cmtx_setting('flood_control_maximum_enabled') && !$cmtx_is_admin) { //if 'flood control maximum' enabled and user is not admin
		cmtx_flood_control_maximum();
	}
	
	/* Name */
	if (!isset($_POST['cmtx_name'])) { //if name not submitted
		$_POST['cmtx_name'] = ""; //set it with an empty value
	}
	if (isset($cmtx_set_name_value) && !empty($cmtx_set_name_value) && cmtx_setting('state_name') != 'normal') { //if login name is set and name field is disabled or hidden
		$_POST['cmtx_name'] = $cmtx_set_name_value; //set it with login name
	}
	$cmtx_name = trim($_POST['cmtx_name']); //remove any space at beginning and end
	if (empty($cmtx_name)) { //if name is empty
		cmtx_error(CMTX_ERROR_MESSAGE_NO_NAME); //reject user for entering no name
	} else {
		cmtx_is_injected($cmtx_name); //check for injection attempt
		cmtx_validate_name($cmtx_name); //validate name
		if (cmtx_setting('reserved_names_enabled') && !$cmtx_is_admin) {
			$cmtx_name = cmtx_check_for_word("reserved_names", true, $cmtx_name, cmtx_setting('reserved_names_action'), CMTX_APPROVE_REASON_RESERVED_NAME, CMTX_ERROR_MESSAGE_RESERVED_NAME, CMTX_BAN_REASON_RESERVED_NAME);
		}
		if (cmtx_setting('dummy_names_enabled')) {
			$cmtx_name = cmtx_check_for_word("dummy_names", true, $cmtx_name, cmtx_setting('dummy_names_action'), CMTX_APPROVE_REASON_DUMMY_NAME, CMTX_ERROR_MESSAGE_DUMMY_NAME, CMTX_BAN_REASON_DUMMY_NAME);
		}
		if (cmtx_setting('banned_names_enabled')) {
			$cmtx_name = cmtx_check_for_word("banned_names", true, $cmtx_name, cmtx_setting('banned_names_action'), CMTX_APPROVE_REASON_BANNED_NAME, CMTX_ERROR_MESSAGE_BANNED_NAME, CMTX_BAN_REASON_BANNED_NAME);
		}
		if (cmtx_setting('one_name_enabled')) {
			cmtx_check_for_one_name($cmtx_name); //check only one word is entered for name
		}
		if (cmtx_setting('fix_name_enabled')) {
			$cmtx_name = cmtx_fix_entry($cmtx_name); //makes first letter uppercase and the rest lowercase
		}
		if (cmtx_setting('detect_link_in_name_enabled')) {
			cmtx_check_for_link($cmtx_name, cmtx_setting('link_in_name_action'), CMTX_APPROVE_REASON_LINK_IN_NAME, CMTX_ERROR_MESSAGE_LINK_IN_NAME, CMTX_BAN_REASON_LINK_IN_NAME); //detect link
		}
		$cmtx_name = cmtx_sanitize($cmtx_name, true, true);
	}

	/* Email */
	if (!isset($_POST['cmtx_email'])) { //if email not submitted
		$_POST['cmtx_email'] = ""; //set it with an empty value
	}
	if (isset($cmtx_set_email_value) && !empty($cmtx_set_email_value) && cmtx_setting('state_email') != 'normal') { //if login email is set and email field is disabled or hidden
		$_POST['cmtx_email'] = $cmtx_set_email_value; //set it with login email
	}
	if (cmtx_setting('enabled_email')) { //if email field is enabled
		$cmtx_email = trim($_POST['cmtx_email']); //remove any space at beginning and end
		if (cmtx_setting('required_email') && empty($cmtx_email)) { //if field is required but value is empty
			cmtx_error(CMTX_ERROR_MESSAGE_NO_EMAIL); //reject user for entering no email address
		}
		if (!empty($cmtx_email)) { //if email value is not empty
			cmtx_is_injected($cmtx_email); //check for injection attempt
			cmtx_validate_email($cmtx_email); //validate email address
			if (cmtx_setting('reserved_emails_enabled') && !$cmtx_is_admin) {
				$cmtx_email = cmtx_check_for_word("reserved_emails", false, $cmtx_email, cmtx_setting('reserved_emails_action'), CMTX_APPROVE_REASON_RESERVED_EMAIL, CMTX_ERROR_MESSAGE_RESERVED_EMAIL, CMTX_BAN_REASON_RESERVED_EMAIL);
			}
			if (cmtx_setting('dummy_emails_enabled')) {
				$cmtx_email = cmtx_check_for_word("dummy_emails", false, $cmtx_email, cmtx_setting('dummy_emails_action'), CMTX_APPROVE_REASON_DUMMY_EMAIL, CMTX_ERROR_MESSAGE_DUMMY_EMAIL, CMTX_BAN_REASON_DUMMY_EMAIL);
			}
			if (cmtx_setting('banned_emails_enabled')) {
				$cmtx_email = cmtx_check_for_word("banned_emails", false, $cmtx_email, cmtx_setting('banned_emails_action'), CMTX_APPROVE_REASON_BANNED_EMAIL, CMTX_ERROR_MESSAGE_BANNED_EMAIL, CMTX_BAN_REASON_BANNED_EMAIL);
			}
			$cmtx_email = cmtx_sanitize($cmtx_email, true, true);
		} else {
			$cmtx_email = "";
		}
	} else {
		$cmtx_email = "";
	}

	/* Website */
	if (!isset($_POST['cmtx_website'])) { //if website not submitted
		$_POST['cmtx_website'] = ""; //set it with an empty value
	}
	if (isset($cmtx_set_website_value) && !empty($cmtx_set_website_value) && cmtx_setting('state_website') != 'normal') { //if login website is set and website field is disabled or hidden
		$_POST['cmtx_website'] = $cmtx_set_website_value; //set it with login website
	}
	if (cmtx_setting('enabled_website')) { //if website field is enabled
		$cmtx_website = trim($_POST['cmtx_website']); //remove any space at beginning and end
		if (cmtx_setting('required_website') && (empty($cmtx_website) || $cmtx_website == "http://")) { //if field is required but value is empty
			cmtx_error(CMTX_ERROR_MESSAGE_NO_WEBSITE); //reject user for entering no website address
		} else if (!empty($cmtx_website) && $cmtx_website != "http://") { //if a website address is entered
			cmtx_is_injected($cmtx_website); //check for injection attempt
			$cmtx_website = cmtx_url_encode_spaces($cmtx_website); //encode spaces
			cmtx_validate_website($cmtx_website); //validate website
			if (cmtx_setting('approve_websites')) { //if entering a website address requires approval
				cmtx_approve(CMTX_APPROVE_REASON_WEBSITE_ENTERED); //approve user for entering website
			}
			if (cmtx_setting('reserved_websites_enabled') && !$cmtx_is_admin) {
				$cmtx_website = cmtx_check_for_word("reserved_websites", false, $cmtx_website, cmtx_setting('reserved_websites_action'), CMTX_APPROVE_REASON_RESERVED_WEBSITE, CMTX_ERROR_MESSAGE_RESERVED_WEBSITE, CMTX_BAN_REASON_RESERVED_WEBSITE);
			}
			if (cmtx_setting('dummy_websites_enabled')) {
				$cmtx_website = cmtx_check_for_word("dummy_websites", false, $cmtx_website, cmtx_setting('dummy_websites_action'), CMTX_APPROVE_REASON_DUMMY_WEBSITE, CMTX_ERROR_MESSAGE_DUMMY_WEBSITE, CMTX_BAN_REASON_DUMMY_WEBSITE);
			}
			if (cmtx_setting('banned_websites_as_website_enabled')) {
				$cmtx_website = cmtx_check_for_word("banned_websites", false, $cmtx_website, cmtx_setting('banned_websites_as_website_action'), CMTX_APPROVE_REASON_BANNED_WEBSITE_IN_WEBSITE, CMTX_ERROR_MESSAGE_BANNED_WEBSITE_IN_WEBSITE, CMTX_BAN_REASON_BANNED_WEBSITE_IN_WEBSITE);
			}
			$cmtx_website = cmtx_sanitize($cmtx_website, true, true); //sanitize website address
		} else {
			$cmtx_website = "http://";
		}
	} else {
		$cmtx_website = "http://";
	}

	/* Town */
	if (!isset($_POST['cmtx_town'])) { //if town not submitted
		$_POST['cmtx_town'] = ""; //set it with an empty value
	}
	if (isset($cmtx_set_town_value) && !empty($cmtx_set_town_value) && cmtx_setting('state_town') != 'normal') { //if login town is set and town field is disabled or hidden
		$_POST['cmtx_town'] = $cmtx_set_town_value; //set it with login town
	}
	if (cmtx_setting('enabled_town')) { //if town field is enabled
		$cmtx_town = trim($_POST['cmtx_town']); //remove any space at beginning and end
		if (cmtx_setting('required_town') && empty($cmtx_town)) { //if field is required but value is empty
			cmtx_error(CMTX_ERROR_MESSAGE_NO_TOWN); //reject user for entering no town
		}
		if (!empty($cmtx_town)) { //if town value is not empty
			cmtx_is_injected($cmtx_town); //check for injection attempt
			cmtx_validate_town($cmtx_town); //validate town
			if (cmtx_setting('reserved_towns_enabled') && !$cmtx_is_admin) {
				$cmtx_town = cmtx_check_for_word("reserved_towns", true, $cmtx_town, cmtx_setting('reserved_towns_action'), CMTX_APPROVE_REASON_RESERVED_TOWN, CMTX_ERROR_MESSAGE_RESERVED_TOWN, CMTX_BAN_REASON_RESERVED_TOWN);
			}
			if (cmtx_setting('dummy_towns_enabled')) {
				$cmtx_town = cmtx_check_for_word("dummy_towns", true, $cmtx_town, cmtx_setting('dummy_towns_action'), CMTX_APPROVE_REASON_DUMMY_TOWN, CMTX_ERROR_MESSAGE_DUMMY_TOWN, CMTX_BAN_REASON_DUMMY_TOWN);
			}
			if (cmtx_setting('banned_towns_enabled')) {
				$cmtx_town = cmtx_check_for_word("banned_towns", true, $cmtx_town, cmtx_setting('banned_towns_action'), CMTX_APPROVE_REASON_BANNED_TOWN, CMTX_ERROR_MESSAGE_BANNED_TOWN, CMTX_BAN_REASON_BANNED_TOWN);
			}
			if (cmtx_setting('fix_town_enabled')) {
				$cmtx_town = cmtx_fix_entry($cmtx_town); //makes first letter uppercase and the rest lowercase
			}
			if (cmtx_setting('detect_link_in_town_enabled')) {
				cmtx_check_for_link($cmtx_town, cmtx_setting('link_in_town_action'), CMTX_APPROVE_REASON_LINK_IN_TOWN, CMTX_ERROR_MESSAGE_LINK_IN_TOWN, CMTX_BAN_REASON_LINK_IN_TOWN); //detect link
			}
			$cmtx_town = cmtx_sanitize($cmtx_town, true, true); //sanitize town
		} else {
			$cmtx_town = "";
		}
	} else {
		$cmtx_town = "";
	}

	/* Country */
	if (!isset($_POST['cmtx_country'])) { //if country not submitted
		$_POST['cmtx_country'] = ""; //set it with an empty value
	}
	if (isset($cmtx_set_country_value) && !empty($cmtx_set_country_value) && cmtx_setting('state_country') != 'normal') { //if login country is set and country field is disabled or hidden
		$_POST['cmtx_country'] = $cmtx_set_country_value; //set it with login country
	}
	if (cmtx_setting('enabled_country')) { //if country field is enabled
		$cmtx_country = trim($_POST['cmtx_country']); //remove any space at beginning and end
		if (cmtx_setting('required_country') && empty($cmtx_country)) { //if field is required but value is empty
			cmtx_error(CMTX_ERROR_MESSAGE_NO_COUNTRY); //reject user for selecting no country
		}
		if (!empty($cmtx_country)) { //if country value is selected
			cmtx_is_injected($cmtx_country); //check for injection attempt
			cmtx_validate_country($cmtx_country); //validate country
			cmtx_find_country($cmtx_country); //find country
			$cmtx_country = cmtx_sanitize($cmtx_country, true, true); //sanitize country
		} else {
			$cmtx_country = "";
		}
	} else {
		$cmtx_country = "";
	}

	/* Rating */
	if (cmtx_setting('repeat_ratings') != "allow" && cmtx_has_rated()) { $cmtx_rating = 0; } else {
		if (!isset($_POST['cmtx_rating'])) { //if rating not submitted
			$_POST['cmtx_rating'] = ""; //set it with an empty value
		}
		if (cmtx_setting('enabled_rating')) { //if rating field is enabled
			$cmtx_rating = trim($_POST['cmtx_rating']); //remove any space at beginning and end
			if (cmtx_setting('required_rating') && empty($cmtx_rating)) { //if field is required but value is empty
				cmtx_error(CMTX_ERROR_MESSAGE_NO_RATING); //reject user for selecting no rating
			}
			if (!empty($cmtx_rating)) { //if rating value is selected
				cmtx_is_injected($cmtx_rating); //check for injection attempt
				cmtx_validate_rating($cmtx_rating); //validate rating
				$cmtx_rating = cmtx_sanitize($cmtx_rating, true, true); //sanitize rating
			} else {
				$cmtx_rating = 0;
			}
		} else {
			$cmtx_rating = 0;
		}
	}

	/* Comment */
	if (!isset($_POST['cmtx_comment'])) { //if comment is not submitted
		$_POST['cmtx_comment'] = ""; //set it with an empty value
	}
	$cmtx_comment = trim($_POST['cmtx_comment']); //remove any space at beginning and end
	if (empty($cmtx_comment)) { //if comment value is empty
		cmtx_error(CMTX_ERROR_MESSAGE_NO_COMMENT); //reject user for entering no comment
	} else { //if comment entered
		if (cmtx_setting('check_repeats_enabled')) {
			cmtx_check_repeats($cmtx_comment, cmtx_setting('check_repeats_action'), CMTX_APPROVE_REASON_REPEATS, CMTX_ERROR_MESSAGE_REPEATS, CMTX_BAN_REASON_REPEATS);
		}
		if (cmtx_setting('mild_swear_words_enabled')) {
			$cmtx_comment = cmtx_check_for_word("mild_swear_words", true, $cmtx_comment, cmtx_setting('mild_swear_words_action'), CMTX_APPROVE_REASON_MILD_SWEARING, CMTX_ERROR_MESSAGE_MILD_SWEARING, CMTX_BAN_REASON_MILD_SWEARING);
		}
		if (cmtx_setting('strong_swear_words_enabled')) {
			$cmtx_comment = cmtx_check_for_word("strong_swear_words", true, $cmtx_comment, cmtx_setting('strong_swear_words_action'), CMTX_APPROVE_REASON_STRONG_SWEARING, CMTX_ERROR_MESSAGE_STRONG_SWEARING, CMTX_BAN_REASON_STRONG_SWEARING);
		}
		if (cmtx_setting('spam_words_enabled')) {
			$cmtx_comment = cmtx_check_for_word("spam_words", true, $cmtx_comment, cmtx_setting('spam_words_action'), CMTX_APPROVE_REASON_SPAMMING, CMTX_ERROR_MESSAGE_SPAMMING, CMTX_BAN_REASON_SPAMMING);
		}
		if (cmtx_setting('check_capitals_enabled')) {
			cmtx_comment_check_capitals($cmtx_comment); //check for too many capitals
		}
		if (cmtx_setting('detect_link_in_comment_enabled')) {
			cmtx_check_for_link($cmtx_comment, cmtx_setting('link_in_comment_action'), CMTX_APPROVE_REASON_LINK_IN_COMMENT, CMTX_ERROR_MESSAGE_LINK_IN_COMMENT, CMTX_BAN_REASON_LINK_IN_COMMENT); //detect link
		}
		if (cmtx_setting('approve_images')) {
			cmtx_comment_detect_image($cmtx_comment); //detect images in comment
		}
		if (cmtx_setting('approve_videos')) {
			cmtx_comment_detect_video($cmtx_comment); //detect videos in comment
		}
		if (cmtx_setting('banned_websites_as_comment_enabled')) {
			$cmtx_comment = cmtx_check_for_word("banned_websites", false, $cmtx_comment, cmtx_setting('banned_websites_as_comment_action'), CMTX_APPROVE_REASON_BANNED_WEBSITE_IN_COMMENT, CMTX_ERROR_MESSAGE_BANNED_WEBSITE_IN_COMMENT, CMTX_BAN_REASON_BANNED_WEBSITE_IN_COMMENT);
		}
		$cmtx_comment = cmtx_sanitize($cmtx_comment, true, false); //converts to HTML entities
		if (cmtx_setting('enabled_bb_code')) {
			$cmtx_comment = cmtx_comment_add_bb_code($cmtx_comment); //convert BB Code to HTML
		}
		if (cmtx_setting('enabled_smilies')) {
			$cmtx_comment = cmtx_comment_add_smilies($cmtx_comment); //convert smilies to HTML
			cmtx_check_maximum_smilies($cmtx_comment); //check comment meets maximum smilies requirement
		}
		$cmtx_comment = cmtx_purify($cmtx_comment); //purify HTML
		$cmtx_comment = cmtx_comment_parse_links($cmtx_comment); //convert links to HTML
		if (cmtx_setting('comment_line_breaks')) {
			$cmtx_comment = cmtx_comment_add_breaks($cmtx_comment); //add line breaks (<br /> and <p></p>)
		} else {
			$cmtx_comment = cmtx_comment_remove_breaks($cmtx_comment); //remove line breaks
		}
		cmtx_comment_deny_long_words($cmtx_comment); //check for extremely long words
		cmtx_comment_minimum($cmtx_comment); //check comment meets minimum requirements
		cmtx_comment_maximum($cmtx_comment); //check comment meets maximum requirements
		cmtx_comment_max_lines($cmtx_comment); //check comment for too many lines
		$cmtx_comment = cmtx_sanitize($cmtx_comment, false, true); //complete sanitization
		cmtx_comment_resubmit(); //check comment is new
	}
	
	/* Reply To */
	if (!isset($_POST['cmtx_reply_id'])) { //if reply ID not submitted
		$_POST['cmtx_reply_id'] = 0; //set it with a zero value
	}
	if (cmtx_setting('show_reply')) { //if reply field is enabled
		$cmtx_reply_id = trim($_POST['cmtx_reply_id']); //remove any space at beginning and end
		cmtx_is_injected($cmtx_reply_id); //check for injection attempt
		cmtx_validate_reply($cmtx_reply_id, $cmtx_page_id); //validate reply
		$cmtx_reply_to = cmtx_sanitize($cmtx_reply_id, true, true); //sanitize reply
	} else {
		$cmtx_reply_to = 0;
	}
		
	/* Question */
	if (cmtx_session_set() && isset($_SESSION['cmtx_question']) && $_SESSION['cmtx_question'] == cmtx_setting('session_key')) {} else {
		if ((!isset($_POST['cmtx_user_answer']) || !isset($_POST['cmtx_real_answer'])) && cmtx_setting('enabled_question')) {
			cmtx_error(CMTX_ERROR_MESSAGE_NO_ANSWER); //reject user for entering no answer
		} else {
			if (cmtx_setting('enabled_question')) { //if question field enabled
				$cmtx_user_answer = trim(strtolower($_POST['cmtx_user_answer'])); //get user answer
				$cmtx_real_answer = trim(strtolower($_POST['cmtx_real_answer'])); //get real answer
				if (empty($cmtx_user_answer)) { //if no answer entered
					cmtx_error(CMTX_ERROR_MESSAGE_NO_ANSWER); //reject user for entering no answer
				} else { //if answer entered
					if (!in_array($cmtx_user_answer, explode("|", $cmtx_real_answer))) { //if answers do not match
						cmtx_error(CMTX_ERROR_MESSAGE_WRONG_ANSWER); //reject user for entering wrong answer
					} else {
						if (cmtx_session_set()) { //if there's a session
							$_SESSION['cmtx_question'] = cmtx_setting('session_key'); //add question completion to session
						}
					}
				}
			}
		}
	}
	
	/* Captcha */
	if (cmtx_session_set() && isset($_SESSION['cmtx_captcha']) && $_SESSION['cmtx_captcha'] == cmtx_setting('session_key')) {} else {
		//Securimage
		if (cmtx_setting('enabled_captcha') && cmtx_setting('captcha_type') == 'securimage') {
			if (extension_loaded('gd') && function_exists('imagettftext')) {
				if (!isset($_POST['cmtx_captcha_code'])) {
					cmtx_error(CMTX_ERROR_MESSAGE_NO_CAPTCHA); //reject user for entering no captcha value
				} else {
					$cmtx_captcha = trim($_POST['cmtx_captcha_code']); //get and trim entered captcha value
					if (empty($cmtx_captcha)) { //if no captcha value entered
						cmtx_error(CMTX_ERROR_MESSAGE_NO_CAPTCHA); //reject user for entering no captcha value
					} else { //if captcha value entered
						require_once $cmtx_path . "securimage/securimage.php"; //load captcha script
						$securimage = new Securimage();
						if ($securimage->check($cmtx_captcha) == false) { //if entered captcha value invalid
							cmtx_error(CMTX_ERROR_MESSAGE_WRONG_CAPTCHA); //reject user for entering wrong captcha value
						} else {
							if (cmtx_session_set()) { //if there's a session
								$_SESSION['cmtx_captcha'] = cmtx_setting('session_key'); //add captcha completion to session
							}
						}
					}
				}
			}
		}
		//ReCaptcha
		if (cmtx_setting('enabled_captcha') && cmtx_setting('captcha_type') == 'recaptcha') {
			if (cmtx_setting('recaptcha_public_key') == '' || cmtx_setting('recaptcha_private_key') == '') {} else {
				if (function_exists('fsockopen') && is_callable('fsockopen')) {
					if ((!isset($_POST['recaptcha_challenge_field']) || !isset($_POST['recaptcha_response_field']))) {
						cmtx_error(CMTX_ERROR_MESSAGE_NO_CAPTCHA); //reject user for entering no captcha value
					} else {
						$cmtx_captcha = trim($_POST['recaptcha_response_field']); //get and trim entered captcha value
						if (empty($cmtx_captcha)) { //if no captcha value entered
							cmtx_error(CMTX_ERROR_MESSAGE_NO_CAPTCHA); //reject user for entering no captcha value
						} else { //if captcha value entered
							require_once $cmtx_path . 'includes/recaptcha/recaptchalib.php'; //load captcha script
							$cmtx_recaptcha_private_key = cmtx_setting('recaptcha_private_key');
							$cmtx_recaptcha_response = recaptcha_check_answer($cmtx_recaptcha_private_key, $cmtx_ip_address, $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
							if (!$cmtx_recaptcha_response->is_valid) { //if entered captcha value invalid
								cmtx_error(CMTX_ERROR_MESSAGE_WRONG_CAPTCHA); //reject user for entering wrong captcha value
							} else {
								if (cmtx_session_set()) { //if there's a session
									$_SESSION['cmtx_captcha'] = cmtx_setting('session_key'); //add captcha completion to session
								}
							}
						}
					}
				}
			}
		}	
	}
	
	/* Akismet */
	if (cmtx_setting('akismet_enabled') && function_exists('fsockopen') && is_callable('fsockopen')) {
		if (cmtx_akismet($cmtx_name, $cmtx_email, $cmtx_website, $cmtx_comment)) {
			cmtx_approve(CMTX_APPROVE_REASON_AKISMET); //approve user for failing Akismet test
		}
	}

	cmtx_check_maximums(); //check field data does not exceed the maximum lengths
	
	if ($cmtx_is_admin) { $cmtx_is_admin = 1; } else { $cmtx_is_admin = 0; } //prepare for database
	
	if ($cmtx_error) { //if there were any errors
	
		//build the error box
		$cmtx_box = "<div class='cmtx_error_box'>";
		if ($cmtx_error_total == 1) { //if only 1 error message
			$cmtx_box .= "<div class='cmtx_error_message_line_1'>";
			$cmtx_box .= sprintf(CMTX_ERROR_NUMBER, $cmtx_error_total);
			$cmtx_box .= "</div>";
			$cmtx_box .= "<div class='cmtx_error_message_line_2'>";
			$cmtx_box .= CMTX_ERROR_CORRECTION;
			$cmtx_box .= "</div>";
		} else { //more than 1 error message
			$cmtx_box .= "<div class='cmtx_error_message_line_1'>";
			$cmtx_box .= sprintf(CMTX_ERRORS_NUMBER, $cmtx_error_total);
			$cmtx_box .= "</div>";
			$cmtx_box .= "<div class='cmtx_error_message_line_2'>";
			$cmtx_box .= CMTX_ERRORS_CORRECTION;
			$cmtx_box .= "</div>";
		}
		$cmtx_box .= "<div class='cmtx_error_details'>";
		$cmtx_box .= "<ul>" . $cmtx_error_message . "</ul>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div style='clear: left;'></div>";
		
		cmtx_repopulate(); //repopulate the form with submitted values
		
	} else if (isset($_POST['cmtx_preview']) || isset($_POST['cmtx_prev'])) { //if preview was selected
		
		//remove any escapes from data
		$cmtx_name = cmtx_strip_slashes($cmtx_name);
		$cmtx_email = cmtx_strip_slashes($cmtx_email);
		$cmtx_website = cmtx_strip_slashes($cmtx_website);
		$cmtx_town = cmtx_strip_slashes($cmtx_town);
		$cmtx_country = cmtx_strip_slashes($cmtx_country);
		$cmtx_comment = cmtx_strip_slashes($cmtx_comment);
		
		//build the preview box using submitted values
		$cmtx_box = cmtx_generate_comment (true, 1, 0, $cmtx_name, $cmtx_email, $cmtx_website, $cmtx_town, $cmtx_country, $cmtx_rating, '0', $cmtx_comment, '', $cmtx_is_admin, 0, 0, 0, 0, date("Y-m-d H:i:s"));
		
		$cmtx_box .= "<div style='clear: left;'></div>";
		
		cmtx_repopulate(); //repopulate the form with submitted values
	
	} else if (cmtx_approval_needed()) { //if approval needed
	
		if (!cmtx_page_exists()) { //if page does not exist
			cmtx_create_page(); //create it now
		}
	
		if (cmtx_setting('approve_comments')) { //if approving all comments
			$cmtx_approve_reason = CMTX_APPROVE_REASON_ALL;
		} else {
			$cmtx_approve_reason = substr_replace($cmtx_approve_reason, "", -2); //remove ending line break
		}
		
		cmtx_notify_admin_new_comment_approve($cmtx_name, $cmtx_comment); //notify admin of new comment
		
		$cmtx_approve_reason = cmtx_sanitize($cmtx_approve_reason, true, true); //sanitize approve reason
		
		//insert user's comment into 'comments' database table
		mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "comments` (`name`, `email`, `website`, `town`, `country`, `rating`, `reply_to`, `comment`, `reply`, `ip_address`, `page_id`, `is_approved`, `approval_reasoning`, `is_admin`, `is_sent`, `sent_to`, `likes`, `dislikes`, `is_sticky`, `is_locked`, `is_verified`, `dated`) VALUES ('$cmtx_name', '$cmtx_email', '$cmtx_website', '$cmtx_town', '$cmtx_country', '$cmtx_rating', '$cmtx_reply_to', '$cmtx_comment', '', '$cmtx_ip_address', '$cmtx_page_id', 0, '$cmtx_approve_reason', '$cmtx_is_admin', 0, 0, 0, 0, 0, 0, 0, NOW())");
		
		//build the approval box
		$cmtx_box = "<div class='cmtx_approval_box'>";
		$cmtx_box .= "<div class='cmtx_approval_message_line_1'>";
		$cmtx_box .= CMTX_APPROVAL_OPENING;
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div class='cmtx_approval_message_line_2'>";
		$cmtx_box .= CMTX_APPROVAL_TEXT;
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div style='clear: left;'></div>";
		
		//add new subscriber
		if (cmtx_setting('enabled_notify') && isset($_POST['cmtx_notify']) && cmtx_setting('enabled_email') && !empty($cmtx_email) && !cmtx_subscriber_exists($cmtx_email, $cmtx_page_id) && !cmtx_subscriber_bad($cmtx_email)) {
			cmtx_add_subscriber($cmtx_name, $cmtx_email, $cmtx_page_id);
		}
		
		if ( (isset($_POST['cmtx_remember'])) || (!cmtx_setting('enabled_remember') && cmtx_setting('form_cookie')) ) {
			cmtx_set_form_cookie($cmtx_name, $cmtx_email, $cmtx_website, $cmtx_town, $cmtx_country); //save form inputs
			$cmtx_default_name = cmtx_strip_slashes(cmtx_decode($cmtx_name));
			$cmtx_default_email = cmtx_strip_slashes(cmtx_decode($cmtx_email));
			$cmtx_default_website = cmtx_strip_slashes(cmtx_decode($cmtx_website));
			$cmtx_default_town = cmtx_strip_slashes(cmtx_decode($cmtx_town));
			$cmtx_default_country = cmtx_strip_slashes(cmtx_decode($cmtx_country));
		}
		
		if (cmtx_session_set()) { //if there's a session
			$_SESSION['cmtx_resubmit_key'] = $_POST['cmtx_resubmit_key']; //add resubmit key to session
		}
		
		$cmtx_reply_id = 0; //reset the reply id
		
		if (cmtx_session_set()) { //if there's a session
			$_SESSION['cmtx_question'] = ''; //reset session
			$_SESSION['cmtx_captcha'] = ''; //reset session
		}
		
	} else { //if comment is a success (no approval required)
	
		if (!cmtx_page_exists()) { //if page does not exist
			cmtx_create_page(); //create it now
		}
		
		//insert user's comment into 'comments' database table
		mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "comments` (`name`, `email`, `website`, `town`, `country`, `rating`, `reply_to`, `comment`, `reply`, `ip_address`, `page_id`, `is_approved`, `approval_reasoning`, `is_admin`, `is_sent`, `sent_to`, `likes`, `dislikes`, `is_sticky`, `is_locked`, `is_verified`, `dated`) VALUES ('$cmtx_name', '$cmtx_email', '$cmtx_website', '$cmtx_town', '$cmtx_country', '$cmtx_rating', '$cmtx_reply_to', '$cmtx_comment', '', '$cmtx_ip_address', '$cmtx_page_id', 1, '', '$cmtx_is_admin', 0, 0, 0, 0, 0, 0, 0, NOW())");
		
		//build the success box
		$cmtx_box = "<div class='cmtx_success_box'>";
		$cmtx_box .= "<div class='cmtx_success_message_line_1'>";
		$cmtx_box .= CMTX_SUCCESS_OPENING;
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div class='cmtx_success_message_line_2'>";
		$cmtx_box .= CMTX_SUCCESS_TEXT;
		$cmtx_box .= "</div>";
		$cmtx_box .= "</div>";
		$cmtx_box .= "<div style='clear: left;'></div>";
		
		//add new subscriber
		if (cmtx_setting('enabled_notify') && isset($_POST['cmtx_notify']) && cmtx_setting('enabled_email') && !empty($cmtx_email) && !cmtx_subscriber_exists($cmtx_email, $cmtx_page_id) && !cmtx_subscriber_bad($cmtx_email) && !$cmtx_is_admin) {
			cmtx_add_subscriber($cmtx_name, $cmtx_email, $cmtx_page_id);
		}
		
		//notify subscribers of new comment
		if (cmtx_setting('enabled_notify')) {
			if ($cmtx_is_admin) {
				cmtx_notify_subscribers($cmtx_name, $cmtx_comment, $cmtx_page_id);
			} else {
				if (!cmtx_setting('approve_notifications')) {
					cmtx_notify_subscribers($cmtx_name, $cmtx_comment, $cmtx_page_id);
				}
			}
		}
		
		cmtx_notify_admin_new_comment_okay($cmtx_name, $cmtx_comment); //notify admin of new comment
		
		if ( (isset($_POST['cmtx_remember'])) || (!cmtx_setting('enabled_remember') && cmtx_setting('form_cookie')) ) {
			cmtx_set_form_cookie($cmtx_name, $cmtx_email, $cmtx_website, $cmtx_town, $cmtx_country); //save form inputs
			$cmtx_default_name = cmtx_strip_slashes(cmtx_decode($cmtx_name));
			$cmtx_default_email = cmtx_strip_slashes(cmtx_decode($cmtx_email));
			$cmtx_default_website = cmtx_strip_slashes(cmtx_decode($cmtx_website));
			$cmtx_default_town = cmtx_strip_slashes(cmtx_decode($cmtx_town));
			$cmtx_default_country = cmtx_strip_slashes(cmtx_decode($cmtx_country));
		}
		
		if (cmtx_session_set()) { //if there's a session
			$_SESSION['cmtx_resubmit_key'] = $_POST['cmtx_resubmit_key']; //add resubmit key to session
		}
		
		$cmtx_reply_id = 0; //reset the reply id
		
		if (cmtx_session_set()) { //if there's a session
			$_SESSION['cmtx_question'] = ''; //reset session
			$_SESSION['cmtx_captcha'] = ''; //reset session
		}
		
	}
	
} //end of if-data-submitted
?>