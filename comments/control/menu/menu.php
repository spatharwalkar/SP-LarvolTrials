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

<?php
if (!defined('IN_COMMENTICS')) { die('Access Denied.'); }
?>

<div id="ddtopmenubar" class="mattblackmenu">
<ul>
<li><a href="index.php?page=dashboard"><?php echo CMTX_MENU_TITLE_DASHBOARD; ?></a></li>
<?php if (!cmtx_restrict_page("manage")) { ?> <li><a href="" rel="manage" onclick="return false;"><?php echo CMTX_MENU_TITLE_MANAGE; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("layout")) { ?> <li><a href="" rel="layout" onclick="return false;"><?php echo CMTX_MENU_TITLE_LAYOUT; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings")) { ?> <li><a href="" rel="settings" onclick="return false;"><?php echo CMTX_MENU_TITLE_SETTINGS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("tasks")) { ?> <li><a href="" rel="tasks" onclick="return false;"><?php echo CMTX_MENU_TITLE_TASKS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("reports")) { ?> <li><a href="" rel="reports" onclick="return false;"><?php echo CMTX_MENU_TITLE_REPORTS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("tools")) { ?> <li><a href="" rel="tools" onclick="return false;"><?php echo CMTX_MENU_TITLE_TOOLS; ?></a></li> <?php } ?>
<li><a href="" rel="help" onclick="return false;"><?php echo CMTX_MENU_TITLE_HELP; ?></a></li>
<li><a href="index.php?page=log_out"><?php echo CMTX_MENU_TITLE_LOG_OUT; ?></a></li>
</ul>
</div>

<script type="text/javascript">
ddlevelsmenu.setup("ddtopmenubar", "topbar");
</script>

<?php if (!cmtx_restrict_page("manage")) { ?>
<ul id="manage" class="ddsubmenustyle">
<?php if (!cmtx_restrict_page("manage_comments")) { ?> <li><a href="index.php?page=manage_comments"><?php echo CMTX_MENU_MANAGE_COMMENTS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("manage_pages")) { ?> <li><a href="index.php?page=manage_pages"><?php echo CMTX_MENU_MANAGE_PAGES; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("manage_administrators")) { ?> <li><a href="index.php?page=manage_administrators"><?php echo CMTX_MENU_MANAGE_ADMINS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("manage_bans")) { ?> <li><a href="index.php?page=manage_bans"><?php echo CMTX_MENU_MANAGE_BANS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("manage_subscribers")) { ?> <li><a href="index.php?page=manage_subscribers"><?php echo CMTX_MENU_MANAGE_SUBSCRIBERS; ?></a></li> <?php } ?>
</ul>
<?php } ?>

<?php if (!cmtx_restrict_page("layout")) { ?>
<ul id="layout" class="ddsubmenustyle">
<?php if (!cmtx_restrict_page("layout_order")) { ?> <li><a href="index.php?page=layout_order"><?php echo CMTX_MENU_LAYOUT_ORDER; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("layout_comments")) { ?>
<li>
<a href="#"><?php echo CMTX_MENU_LAYOUT_COMMENTS; ?></a>
	<ul>
	<?php if (!cmtx_restrict_page("layout_comments_enabled")) { ?> <li><a href="index.php?page=layout_comments_enabled"><?php echo CMTX_MENU_LAYOUT_COMMENTS_ENABLED; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_general")) { ?> <li><a href="index.php?page=layout_comments_general"><?php echo CMTX_MENU_LAYOUT_COMMENTS_GENERAL; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_pagination")) { ?> <li><a href="index.php?page=layout_comments_pagination"><?php echo CMTX_MENU_LAYOUT_COMMENTS_PAGINATION; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_sort_by")) { ?> <li><a href="index.php?page=layout_comments_sort_by"><?php echo CMTX_MENU_LAYOUT_COMMENTS_SORT_BY; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_replies")) { ?> <li><a href="index.php?page=layout_comments_replies"><?php echo CMTX_MENU_LAYOUT_COMMENTS_REPLIES; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_social")) { ?> <li><a href="index.php?page=layout_comments_social"><?php echo CMTX_MENU_LAYOUT_COMMENTS_SOCIAL; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_gravatar")) { ?> <li><a href="index.php?page=layout_comments_gravatar"><?php echo CMTX_MENU_LAYOUT_COMMENTS_GRAVATAR; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_comments_read_more")) { ?> <li><a href="index.php?page=layout_comments_read_more"><?php echo CMTX_MENU_LAYOUT_COMMENTS_READ_MORE; ?></a></li> <?php } ?>
	</ul>
</li>
<?php } ?>
<?php if (!cmtx_restrict_page("layout_form")) { ?>
<li>
<a href="#"><?php echo CMTX_MENU_LAYOUT_FORM; ?></a>
	<ul>
	<?php if (!cmtx_restrict_page("layout_form_enabled")) { ?> <li><a href="index.php?page=layout_form_enabled"><?php echo CMTX_MENU_LAYOUT_FORM_ENABLED; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_required")) { ?> <li><a href="index.php?page=layout_form_required"><?php echo CMTX_MENU_LAYOUT_FORM_REQUIRED; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_defaults")) { ?> <li><a href="index.php?page=layout_form_defaults"><?php echo CMTX_MENU_LAYOUT_FORM_DEFAULTS; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_general")) { ?> <li><a href="index.php?page=layout_form_general"><?php echo CMTX_MENU_LAYOUT_FORM_GENERAL; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_maximums")) { ?> <li><a href="index.php?page=layout_form_maximums"><?php echo CMTX_MENU_LAYOUT_FORM_MAXIMUMS; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_sort_order")) { ?>
	<li>
	<a href="#"><?php echo CMTX_MENU_LAYOUT_FORM_SORT_ORDER; ?></a>
		<ul>
		<?php if (!cmtx_restrict_page("layout_form_sort_order_fields")) { ?> <li><a href="index.php?page=layout_form_sort_order_fields"><?php echo CMTX_MENU_LAYOUT_FORM_SORT_ORDER_FIELDS; ?></a></li> <?php } ?>
		<?php if (!cmtx_restrict_page("layout_form_sort_order_buttons")) { ?> <li><a href="index.php?page=layout_form_sort_order_buttons"><?php echo CMTX_MENU_LAYOUT_FORM_SORT_ORDER_BUTTONS; ?></a></li> <?php } ?>
		</ul>
	</li>
	<?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_bb_code")) { ?> <li><a href="index.php?page=layout_form_bb_code"><?php echo CMTX_MENU_LAYOUT_FORM_BB_CODE; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_smilies")) { ?> <li><a href="index.php?page=layout_form_smilies"><?php echo CMTX_MENU_LAYOUT_FORM_SMILIES; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_questions")) { ?> <li><a href="index.php?page=layout_form_questions"><?php echo CMTX_MENU_LAYOUT_FORM_QUESTIONS; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_captchas")) { ?>
	<li>
	<a href="#"><?php echo CMTX_MENU_LAYOUT_FORM_CAPTCHAS; ?></a>
		<ul>
		<?php if (!cmtx_restrict_page("layout_form_captchas_securimage")) { ?> <li><a href="index.php?page=layout_form_captchas_securimage"><?php echo CMTX_MENU_LAYOUT_FORM_CAPTCHAS_SECURIMAGE; ?></a></li> <?php } ?>
		<?php if (!cmtx_restrict_page("layout_form_captchas_recaptcha")) { ?> <li><a href="index.php?page=layout_form_captchas_recaptcha"><?php echo CMTX_MENU_LAYOUT_FORM_CAPTCHAS_RECAPTCHA; ?></a></li> <?php } ?>
		</ul>
	</li>
	<?php } ?>
	<?php if (!cmtx_restrict_page("layout_form_states")) { ?> <li><a href="index.php?page=layout_form_states"><?php echo CMTX_MENU_LAYOUT_FORM_STATES; ?></a></li> <?php } ?>
	</ul>
</li>
<?php } ?>
<?php if (!cmtx_restrict_page("layout_powered")) { ?> <li><a href="index.php?page=layout_powered"><?php echo CMTX_MENU_LAYOUT_POWERED; ?></a></li> <?php } ?>
</ul>
<?php } ?>

<?php if (!cmtx_restrict_page("settings")) { ?>
<ul id="settings" class="ddsubmenustyle">
<?php if (!cmtx_restrict_page("settings_administrator")) { ?> <li><a href="index.php?page=settings_administrator"><?php echo CMTX_MENU_TITLE_SETTINGS_ADMINISTRATOR; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_admin_detection")) { ?> <li><a href="index.php?page=settings_admin_detection"><?php echo CMTX_MENU_TITLE_SETTINGS_ADMIN_DETECTION; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_akismet")) { ?> <li><a href="index.php?page=settings_akismet"><?php echo CMTX_MENU_TITLE_SETTINGS_AKISMET; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_approval")) { ?> <li><a href="index.php?page=settings_approval"><?php echo CMTX_MENU_TITLE_SETTINGS_APPROVAL; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_email")) { ?>
<li>
<a href="#"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL; ?></a>
	<ul>
	<?php if (!cmtx_restrict_page("settings_email_setup")) { ?> <li><a href="index.php?page=settings_email_setup"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_SETUP; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("settings_email_editor")) { ?>
	<li>
	<a href="#"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR; ?></a>
		<ul>
			<?php if (!cmtx_restrict_page("settings_email_editor_user")) { ?>
			<li>
			<a href="#"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_USER; ?></a>
				<ul>
				<?php if (!cmtx_restrict_page("settings_email_editor_user_subscriber_confirmation")) { ?> <li><a href="index.php?page=settings_email_editor_user_subscriber_confirmation"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_USER_SUBSCRIBER_CONFIRMATION; ?></a></li> <?php } ?>
				<?php if (!cmtx_restrict_page("settings_email_editor_user_subscriber_notification")) { ?> <li><a href="index.php?page=settings_email_editor_user_subscriber_notification"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_USER_SUBSCRIBER_NOTIFICATION; ?></a></li> <?php } ?>
				</ul>
			</li>
			<?php } ?>
			<?php if (!cmtx_restrict_page("settings_email_editor_admin")) { ?>
			<li>
			<a href="#"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN; ?></a>
				<ul>
				<?php if (!cmtx_restrict_page("settings_email_editor_admin_email_test")) { ?> <li><a href="index.php?page=settings_email_editor_admin_email_test"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN_EMAIL_TEST; ?></a></li> <?php } ?>
				<?php if (!cmtx_restrict_page("settings_email_editor_admin_new_ban")) { ?> <li><a href="index.php?page=settings_email_editor_admin_new_ban"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN_NEW_BAN; ?></a></li> <?php } ?>
				<?php if (!cmtx_restrict_page("settings_email_editor_admin_new_comment_approve")) { ?> <li><a href="index.php?page=settings_email_editor_admin_new_comment_approve"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN_NEW_COMMENT_APPROVE; ?></a></li> <?php } ?>
				<?php if (!cmtx_restrict_page("settings_email_editor_admin_new_comment_okay")) { ?> <li><a href="index.php?page=settings_email_editor_admin_new_comment_okay"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN_NEW_COMMENT_OKAY; ?></a></li> <?php } ?>
				<?php if (!cmtx_restrict_page("settings_email_editor_admin_new_flag")) { ?> <li><a href="index.php?page=settings_email_editor_admin_new_flag"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN_NEW_FLAG; ?></a></li> <?php } ?>
				<?php if (!cmtx_restrict_page("settings_email_editor_admin_reset_password")) { ?> <li><a href="index.php?page=settings_email_editor_admin_reset_password"><?php echo CMTX_MENU_TITLE_SETTINGS_EMAIL_EDITOR_ADMIN_RESET_PASSWORD; ?></a></li> <?php } ?>
				
				</ul>
			</li>
			<?php } ?>
		</ul>
	</li>
	<?php } ?>
	</ul>
</li>
<?php } ?>
<?php if (!cmtx_restrict_page("settings_error_reporting")) { ?> <li><a href="index.php?page=settings_error_reporting"><?php echo CMTX_MENU_TITLE_SETTINGS_ERROR_REPORTING; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_flagging")) { ?> <li><a href="index.php?page=settings_flagging"><?php echo CMTX_MENU_TITLE_SETTINGS_FLAGGING; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_flooding")) { ?> <li><a href="index.php?page=settings_flooding"><?php echo CMTX_MENU_TITLE_SETTINGS_FLOODING; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_language")) { ?> <li><a href="index.php?page=settings_language"><?php echo CMTX_MENU_TITLE_SETTINGS_LANGUAGE; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_maintenance")) { ?> <li><a href="index.php?page=settings_maintenance"><?php echo CMTX_MENU_TITLE_SETTINGS_MAINTENANCE; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_processor")) { ?>
<li>
<a href="#"><?php echo CMTX_MENU_TITLE_SETTINGS_PROCESSOR; ?></a>
	<ul>
	<?php if (!cmtx_restrict_page("settings_processor_name")) { ?> <li><a href="index.php?page=settings_processor_name"><?php echo CMTX_MENU_TITLE_SETTINGS_PROCESSOR_NAME; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("settings_processor_email")) { ?> <li><a href="index.php?page=settings_processor_email"><?php echo CMTX_MENU_TITLE_SETTINGS_PROCESSOR_EMAIL; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("settings_processor_town")) { ?> <li><a href="index.php?page=settings_processor_town"><?php echo CMTX_MENU_TITLE_SETTINGS_PROCESSOR_TOWN; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("settings_processor_website")) { ?> <li><a href="index.php?page=settings_processor_website"><?php echo CMTX_MENU_TITLE_SETTINGS_PROCESSOR_WEBSITE; ?></a></li> <?php } ?>
	<?php if (!cmtx_restrict_page("settings_processor_comment")) { ?> <li><a href="index.php?page=settings_processor_comment"><?php echo CMTX_MENU_TITLE_SETTINGS_PROCESSOR_COMMENT; ?></a></li> <?php } ?>
	</ul>	
</li>
<?php } ?>
<?php if (!cmtx_restrict_page("settings_rich_snippets")) { ?> <li><a href="index.php?page=settings_rich_snippets"><?php echo CMTX_MENU_TITLE_SETTINGS_RICH_SNIPPETS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_rss")) { ?> <li><a href="index.php?page=settings_rss"><?php echo CMTX_MENU_TITLE_SETTINGS_RSS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_security")) { ?> <li><a href="index.php?page=settings_security"><?php echo CMTX_MENU_TITLE_SETTINGS_SECURITY; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_system")) { ?> <li><a href="index.php?page=settings_system"><?php echo CMTX_MENU_TITLE_SETTINGS_SYSTEM; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("settings_viewers")) { ?> <li><a href="index.php?page=settings_viewers"><?php echo CMTX_MENU_TITLE_SETTINGS_VIEWERS; ?></a></li> <?php } ?>
</ul>
<?php } ?>

<?php if (!cmtx_restrict_page("tasks")) { ?>
<ul id="tasks" class="ddsubmenustyle">
<?php if (!cmtx_restrict_page("task_delete_bans")) { ?> <li><a href="index.php?page=task_delete_bans"><?php echo CMTX_MENU_TITLE_TASK_DELETE_BANS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("task_delete_reporters")) { ?> <li><a href="index.php?page=task_delete_reporters"><?php echo CMTX_MENU_TITLE_TASK_DELETE_REPORTERS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("task_delete_subscribers")) { ?> <li><a href="index.php?page=task_delete_subscribers"><?php echo CMTX_MENU_TITLE_TASK_DELETE_SUBSCRIBERS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("task_delete_voters")) { ?> <li><a href="index.php?page=task_delete_voters"><?php echo CMTX_MENU_TITLE_TASK_DELETE_VOTERS; ?></a></li> <?php } ?>
</ul>
<?php } ?>

<?php if (!cmtx_restrict_page("reports")) { ?>
<ul id="reports" class="ddsubmenustyle">
<?php if (!cmtx_restrict_page("report_access")) { ?> <li><a href="index.php?page=report_access"><?php echo CMTX_MENU_TITLE_REPORT_ACCESS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("report_permissions")) { ?> <li><a href="index.php?page=report_permissions"><?php echo CMTX_MENU_TITLE_REPORT_PERMISSIONS; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("report_phpinfo")) { ?> <li><a href="index.php?page=report_phpinfo"><?php echo CMTX_MENU_TITLE_REPORT_PHPINFO; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("report_version")) { ?> <li><a href="index.php?page=report_version"><?php echo CMTX_MENU_TITLE_REPORT_VERSION; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("report_viewers")) { ?> <li><a href="index.php?page=report_viewers"><?php echo CMTX_MENU_TITLE_REPORT_VIEWERS; ?></a></li> <?php } ?>
</ul>
<?php } ?>

<?php if (!cmtx_restrict_page("tools")) { ?>
<ul id="tools" class="ddsubmenustyle">
<?php if (!cmtx_restrict_page("tool_db_backup")) { ?> <li><a href="index.php?page=tool_db_backup"><?php echo CMTX_MENU_TITLE_TOOLS_DB_BACKUP; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("tool_optimize_tables")) { ?> <li><a href="index.php?page=tool_optimize_tables"><?php echo CMTX_MENU_TITLE_TOOLS_OPTIMIZE_TABLES; ?></a></li> <?php } ?>
<?php if (!cmtx_restrict_page("tool_text_finder")) { ?> <li><a href="index.php?page=tool_text_finder"><?php echo CMTX_MENU_TITLE_TOOLS_TEXT_FINDER; ?></a></li> <?php } ?>
</ul>
<?php } ?>

<ul id="help" class="ddsubmenustyle">
<li><a href="http://www.commentics.org/support/knowledgebase.php" target="_blank"><?php echo CMTX_MENU_TITLE_HELP_FAQ; ?></a></li>
<li><a href="http://www.commentics.org/forum/" target="_blank"><?php echo CMTX_MENU_TITLE_HELP_FORUM; ?></a></li>
<li><a href="http://www.commentics.org/support/knowledgebase.php?article=16" target="_blank"><?php echo CMTX_MENU_TITLE_HELP_DONATE; ?></a></li>
<li><a href="http://www.commentics.org/license/" target="_blank"><?php echo CMTX_MENU_TITLE_HELP_LICENSE; ?></a></li>
</ul>