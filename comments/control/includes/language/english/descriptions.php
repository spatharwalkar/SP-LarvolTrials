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

define('CMTX_DESC_LAYOUT_ORDER_1', 'Drag & drop the elements below to determine the sort order of the main parts.');
define('CMTX_DESC_LAYOUT_ORDER_2', 'Split the screen into a side-by-side layout. You will need the width to do this.');
define('CMTX_DESC_LAYOUT_COMMENTS_ENABLED', 'These settings determine which parts of the comments and their outer area are enabled.');
define('CMTX_DESC_LAYOUT_COMMENTS_GENERAL', 'This section contains general comment settings.');
define('CMTX_DESC_LAYOUT_COMMENTS_PAGINATION', 'These settings relate to the layout of the pagination.');
define('CMTX_DESC_LAYOUT_COMMENTS_SORT_BY_1', 'This setting determines whether the Sort By feature is enabled.');
define('CMTX_DESC_LAYOUT_COMMENTS_SORT_BY_2', 'These settings are to control which of the items are enabled.');
define('CMTX_DESC_LAYOUT_COMMENTS_REPLIES', 'These settings are for the reply feature.');
define('CMTX_DESC_LAYOUT_COMMENTS_SOCIAL_1', 'These settings are for the social links.');
define('CMTX_DESC_LAYOUT_COMMENTS_SOCIAL_2', 'These settings are to control which of the links are enabled.');
define('CMTX_DESC_LAYOUT_COMMENTS_GRAVATAR', 'A <b>Gravatar</b> is a user\'s personal image, hosted at gravatar.com, which is retrieved according to their email address.<p/>If the user does not have a Gravatar then the default one is displayed. See <a href="http://en.gravatar.com/site/implement/images/" target="_blank">here</a> for the possible options below.');
define('CMTX_DESC_LAYOUT_COMMENTS_READ_MORE', 'These settings are for the Read More link.');
define('CMTX_DESC_LAYOUT_FORM_ENABLED', 'These settings determine which parts of the form are enabled.');
define('CMTX_DESC_LAYOUT_FORM_REQUIRED', 'These settings determine which parts of the form are required.');
define('CMTX_DESC_LAYOUT_FORM_DEFAULTS', 'This section allows default values to be set.');
define('CMTX_DESC_LAYOUT_FORM_GENERAL', 'This section contains general form settings.');
define('CMTX_DESC_LAYOUT_FORM_MAXIMUMS', 'These settings determine the maximum lengths.');
define('CMTX_DESC_LAYOUT_FORM_SORT_ORDER_FIELDS', 'Drag & drop the elements below to determine the sort order of the form fields.');
define('CMTX_DESC_LAYOUT_FORM_SORT_ORDER_BUTTONS', 'Drag & drop the elements below to determine the sort order of the form buttons.');
define('CMTX_DESC_LAYOUT_FORM_BB_CODE', 'These settings are for the form\'s BB Code tags.');
define('CMTX_DESC_LAYOUT_FORM_SMILIES', 'These settings are for the form\'s smiley images.');
define('CMTX_DESC_LAYOUT_FORM_SECURIMAGE', 'Securimage is a built-in image captcha. You can customize the look below.');
define('CMTX_DESC_LAYOUT_FORM_RECAPTCHA', 'ReCaptcha is a free and external image captcha service to prevent spam.<p/>It\'s secure, widely used, and helps digitize books. Get your API keys <a href="http://www.google.com/recaptcha" target="_blank">here</a>.');
define('CMTX_DESC_LAYOUT_FORM_STATES', 'These settings are for when supplying login details to the form.');
define('CMTX_DESC_LAYOUT_POWERED', 'These settings relate to the \'Powered by\' statement.');
define('CMTX_DESC_SETTINGS_ADMIN', 'These settings are for the admin panel administrator.');
define('CMTX_DESC_SETTINGS_ADMIN_DETECTION', 'This section allows for the detection of the administrator.');
define('CMTX_DESC_SETTINGS_AKISMET', 'Akismet is an external, free, automated service used to identify comments as spam. Get your API key <a href="http://akismet.com/" target="_blank">here</a>.<p/>Identified comments will require approval. The word \'Akismet\' will appear in the comment\'s <b>Notes</b> section.');
define('CMTX_DESC_SETTINGS_APPROVAL', 'These settings relate to the approval of new comments.<p /><b>Note</b>: Detailed options are in Settings -> Processor.');
define('CMTX_DESC_SETTINGS_EMAIL_SETUP', 'Set up your email settings and preferences here.');
define('CMTX_DESC_SETTINGS_EMAIL_METHOD', 'Select the email transport method to use.');
define('CMTX_DESC_SETTINGS_EMAIL_SENDER', 'Change your sender details here to update <b>all</b> the emails.<p />Or use the \'Settings -> Email -> Editor\' to do it individually.');
define('CMTX_DESC_SETTINGS_EMAIL_SIGNATURE', 'This is your signature for the emails.');
define('CMTX_DESC_SETTINGS_EMAIL_TEST', 'Send an email to test your email settings and preferences.<p />The email will be sent to <b>%s</b>.');
define('CMTX_DESC_SETTINGS_EMAIL_SUB_CONFIRMATION', 'This is the confirmation email the user receives when they subscribe to a page.');
define('CMTX_DESC_SETTINGS_EMAIL_SUB_NOTIFICATION', 'This is the email the user receives when they are notified of a new comment.');
define('CMTX_DESC_SETTINGS_EMAIL_EMAIL_TEST', 'This is the email sent from the \'Settings -> Email -> Setup\' page.');
define('CMTX_DESC_SETTINGS_EMAIL_NEW_BAN', 'This is the email the administrator receives when there is a new ban.');
define('CMTX_DESC_SETTINGS_EMAIL_NEW_COMMENT_APPROVE', 'This is the email the administrator receives when there is a new comment that requires approval.');
define('CMTX_DESC_SETTINGS_EMAIL_NEW_COMMENT_OKAY', 'This is the email the administrator receives when there is a new comment that is okay.');
define('CMTX_DESC_SETTINGS_EMAIL_NEW_FLAG', 'This is the email the administrator receives when a new comment is flagged.');
define('CMTX_DESC_SETTINGS_EMAIL_RESET_PASSWORD', 'This is the email the administrator receives when resetting the password.');
define('CMTX_DESC_SETTINGS_ERROR_REPORTING', 'Enable these settings to produce any possible errors. Useful for debugging.');
define('CMTX_DESC_SETTINGS_FLAGGING', 'These settings relate to the report / flag feature.');
define('CMTX_DESC_SETTINGS_FLOODING', 'These settings relate to the submission of multiple comments over a short period of time.');
define('CMTX_DESC_SETTINGS_LANGUAGE', 'Select the desired language for the pages.');
define('CMTX_DESC_SETTINGS_MAINTENANCE', 'Enable this setting to put the script in maintenance mode. Useful during upgrades.<p /><b>Note</b>: The administrator is excluded from maintenance mode.');
define('CMTX_DESC_SETTINGS_PROCESSING_NAME', 'These settings relate to the processing of the name field.');
define('CMTX_DESC_SETTINGS_PROCESSING_EMAIL', 'These settings relate to the processing of the email field.');
define('CMTX_DESC_SETTINGS_PROCESSING_TOWN', 'These settings relate to the processing of the town field.');
define('CMTX_DESC_SETTINGS_PROCESSING_WEBSITE', 'These settings relate to the processing of the website field.');
define('CMTX_DESC_SETTINGS_PROCESSING_COMMENT', 'These settings relate to the processing of the comment field.');
define('CMTX_DESC_WILDCARDS', 'Wildcard symbols <b>are</b> supported. Searches are case-insensitive.');
define('CMTX_DESC_WILDCARDS_1', 'By using the wildcard symbol (*), you can match any characters. For example the user enters the word "<b>Newcastle</b>". The following cases would give these results:');
define('CMTX_DESC_WILDCARDS_2', 'The searches are case-insensitive, so for example the word "<b>Newcastle</b>" could be matched by Newcastle, newcastle, or even nEwCaStLe.');
define('CMTX_DESC_WILDCARDS_3', 'Each word should be on a new line. Empty lines are ignored.');
define('CMTX_DESC_WILDCARDS_A', 'Although the wildcard symbol (*) is supported, it is not necessary here. For example the user enters the email address "<b>test@somesite.com</b>". The following cases would give these results:');
define('CMTX_DESC_WILDCARDS_B', 'The searches are case-insensitive, so for example the email address "<b>test@somesite.com</b>" could be matched by somesite, SOMESITE, or even SoMeSiTe.');
define('CMTX_DESC_WILDCARDS_C', 'Each case should be on a new line. Empty lines are ignored.');
define('CMTX_DESC_WILDCARDS_I', 'Although the wildcard symbol (*) is supported, it is not necessary here. For example the user enters the website address "<b>http://www.somesite.com</b>". The following cases would give these results:');
define('CMTX_DESC_WILDCARDS_II', 'The searches are case-insensitive, so for example the website address "<b>http://www.somesite.com</b>" could be matched by somesite, SOMESITE, or even SoMeSiTe.');
define('CMTX_DESC_WILDCARDS_III', 'Each case should be on a new line. Empty lines are ignored.');
define('CMTX_DESC_WILDCARD_FOUND', '(Found)');
define('CMTX_DESC_WILDCARD_NOT_FOUND', '(Not found)');
define('CMTX_DESC_SETTINGS_RICH_SNIPPETS_1', '<b>Rich Snippets</b> is a way of marking-up certain types of data so that it appears in a specially displayed format in the search engine results pages, making it easier for users to decide whether to click to your site.<p />In Commentics the type of data which is marked-up is the <b>average rating</b>. It can be marked-up with any of 3 formats: Microdata, Microformats or RDFa. I recommend <b>Microformats</b> because it uses valid xHTML.<p />This is an example of how this feature looks:');
define('CMTX_DESC_SETTINGS_RICH_SNIPPETS_2', 'To use this feature, both the <b>Average Rating</b> (Layout -> Comments -> Enabled) and the <b>Topic</b> (Layout -> Comments -> Enabled) must also be enabled.<p />After you have enabled this feature, you can test it <a href="http://www.google.com/webmasters/tools/richsnippets" target="_blank">here</a>. You must have at least one rating for the mark-up to be added.');
define('CMTX_DESC_SETTINGS_RSS', 'These settings are for the RSS comment feed.');
define('CMTX_DESC_SETTINGS_SECURITY', 'These settings relate to the security of the script.');
define('CMTX_DESC_SETTINGS_SYSTEM', 'These settings relate to the system. Be careful when changing these.');
define('CMTX_DESC_SETTINGS_VIEWERS', 'These settings relate to the Reports -> Viewers feature.');
define('CMTX_DESC_TASK_DELETE_BANS', 'This task automatically deletes bans that are older than the configured time period.');
define('CMTX_DESC_TASK_DELETE_REPORTERS', 'This task automatically deletes reporters that are older than the configured time period.');
define('CMTX_DESC_TASK_DELETE_SUBSCRIBERS', 'This task automatically deletes subscribers who have been unconfirmed for longer than the configured time period.');
define('CMTX_DESC_TASK_DELETE_VOTERS', 'This task automatically deletes voters that are older than the configured time period.');
define('CMTX_DESC_REPORT_ACCESS', 'This report shows the last 100 pages that the administrator(s) has viewed.');
define('CMTX_DESC_REPORT_PERMISSIONS', 'This report checks whether your file and folder permissions are set correctly:');
define('CMTX_DESC_REPORT_VERSION_1', 'The installed version of Commentics is');
define('CMTX_DESC_REPORT_VERSION_2', 'Below is a history of your upgrades.');
define('CMTX_DESC_REPORT_VIEWERS', 'The following people or search engines are currently viewing your page(s).');
define('CMTX_DESC_TOOL_DATABASE_BACKUP', 'Create a backup of the database.<p/><b>Note</b>: It is strongly advised that you download these backups to your computer.');
define('CMTX_DESC_TOOL_OPTIMIZE_TABLES', 'This tool will optimize all of the database tables. This speeds up the database and helps avoid data corruption.<p/><b>Note</b>: For a normal site, running this tool every couple of weeks should suffice.');
define('CMTX_DESC_TOOL_TEXT_FINDER', 'Search the language files for a specific word (or phrase).');

?>