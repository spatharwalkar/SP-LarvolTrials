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

/* Anchors */
define('CMTX_ANCHOR_FORM', '#cmtx_form');

/* Heading */
define('CMTX_FORM_HEADING', 'Add Comment');

/* Form disabled messages */
define('CMTX_ALL_FORMS_DISABLED', 'Adding comments has been disabled.');
define('CMTX_THIS_FORM_DISABLED', 'Adding comments has been disabled for this page.');

/* Open form link */
define('CMTX_OPEN_FORM', 'Open the form');

/* JavaScript disabled message */
define('CMTX_JAVASCRIPT_DISABLED', 'JavaScript must be enabled for certain features to work.');

/* Reply */
define('CMTX_REPLY_MESSAGE', 'You are replying to');
define('CMTX_REPLY_CANCEL', '[cancel]');
define('CMTX_REPLY_NOBODY', 'You are not replying to anyone.');

/* Required */
define('CMTX_REQUIRED_SYMBOL', '*');
define('CMTX_REQUIRED_SYMBOL_MESSAGE', CMTX_REQUIRED_SYMBOL . ' Required information');

/* Field labels */
define('CMTX_LABEL_NAME', 'Name:');
define('CMTX_LABEL_EMAIL', 'Email:');
define('CMTX_LABEL_WEBSITE', 'Website:');
define('CMTX_LABEL_TOWN', 'Town:');
define('CMTX_LABEL_COUNTRY', 'Country:');
define('CMTX_LABEL_RATING', 'Rating:');
define('CMTX_LABEL_COMMENT', 'Comment:');
define('CMTX_LABEL_QUESTION', 'Question:');
define('CMTX_LABEL_CAPTCHA', 'Captcha:');

/* Field titles */
define('CMTX_TITLE_NAME', 'Enter name');
define('CMTX_TITLE_EMAIL', 'Enter email address');
define('CMTX_TITLE_WEBSITE', 'Enter website address');
define('CMTX_TITLE_TOWN', 'Enter town');
define('CMTX_TITLE_COMMENT', 'Enter comment');
define('CMTX_TITLE_QUESTION', 'Enter answer to question');
define('CMTX_TITLE_NOTIFY', 'Receive email notifications');
define('CMTX_TITLE_REMEMBER', 'Remember my form inputs');
define('CMTX_TITLE_PRIVACY', 'Agree to privacy policy');
define('CMTX_TITLE_TERMS', 'Agree to terms and conditions');
define('CMTX_TITLE_SUBMIT', 'Add Comment');
define('CMTX_TITLE_PREVIEW', 'Preview');

/* Note displayed after email field */
define('CMTX_NOTE_EMAIL', '(will not be published)');

/* Text displayed for JavaScript BB Code prompts */
define('CMTX_PROMPT_ENTER_BULLET', 'Enter a list item. Click cancel or leave blank to end the list.');
define('CMTX_PROMPT_ENTER_ANOTHER_BULLET', 'Enter another list item. Click cancel or leave blank to end the list.');
define('CMTX_PROMPT_ENTER_NUMERIC', 'Enter a list item. Click cancel or leave blank to end the list.');
define('CMTX_PROMPT_ENTER_ANOTHER_NUMERIC', 'Enter another list item. Click cancel or leave blank to end the list.');
define('CMTX_PROMPT_ENTER_LINK', 'Please enter the link of the website');
define('CMTX_PROMPT_ENTER_LINK_TITLE', 'Optionally, you can also enter a title for the link');
define('CMTX_PROMPT_ENTER_EMAIL', 'Please enter the email address');
define('CMTX_PROMPT_ENTER_EMAIL_TITLE', 'Optionally, you can also enter a title for the email address');
define('CMTX_PROMPT_ENTER_IMAGE', 'Please enter the link of the image');
define('CMTX_PROMPT_ENTER_VIDEO', 'Please enter the link of the video. Supported sites include:\nYouTube, Vimeo, MetaCafe and Dailymotion.');

/* Text displayed for invalid BB Code entries */
define('CMTX_BB_INVALID_LINK', '<i>(invalid-link)</i>');
define('CMTX_BB_INVALID_EMAIL', '<i>(invalid-email)</i>');
define('CMTX_BB_INVALID_IMAGE', '<i>(invalid-image)</i>');
define('CMTX_BB_INVALID_VIDEO', '<i>(invalid-video)</i>');

/* Text displayed before/after the counter */
define('CMTX_TEXT_COUNTER', '%s');

/* Text displayed before question field */
define('CMTX_TEXT_QUESTION', 'Enter answer:');

/* Text displayed for Securimage captcha */
define('CMTX_TEXT_SECURIMAGE', 'Enter code:');
define('CMTX_TITLE_SECURIMAGE', 'Enter code from image');
define('CMTX_TITLE_SECURIMAGE_AUDIO', 'Audio');
define('CMTX_TITLE_SECURIMAGE_REFRESH', 'Refresh');

/* Text displayed if ReCaptcha key(s) missing */
define('CMTX_RECAPTCHA_NO_KEY', 'API key(s) missing in ReCaptcha admin page');

/* Text displayed after notify checkbox */
define('CMTX_TEXT_NOTIFY', 'Notify me of new comments via email.');

/* Text displayed after remember checkbox */
define('CMTX_TEXT_REMEMBER', 'Remember my form details on this computer.');

/* Text displayed after privacy checkbox */
define('CMTX_TEXT_PRIVACY', 'I have read and understand the <a href="' . cmtx_comments_folder() . 'agreement/english/privacy_policy.html" title="View privacy policy" target="_blank" rel="nofollow">privacy policy</a>.');

/* Text displayed after terms checkbox */
define('CMTX_TEXT_TERMS', 'I have read and agree to the <a href="' . cmtx_comments_folder() . 'agreement/english/terms_and_conditions.html" title="View terms and conditions" target="_blank" rel="nofollow">terms and conditions</a>.');

/* Text for form submit button */
define('CMTX_SUBMIT_BUTTON', 'Add Comment');

/* Text for form preview button */
define('CMTX_PREVIEW_BUTTON', 'Preview');

/* Text for form buttons when processing */
define('CMTX_PROCESSING_BUTTON', 'Please Wait..');

/* Text for 'powered by' statement */
define('CMTX_POWERED_BY', 'Powered by');

?>