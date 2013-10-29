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
add_include_path('../../');

define('IN_COMMENTICS', 'true');
define('CMTX_IN_ADMIN', 'true');

session_start();
ob_start();

$cmtx_path = '../'; //set the path

require '../includes/db/connect.php'; //connect to database
if (!$cmtx_db_ok) { die(); }

require 'includes/functions/general.php'; //load functions

require 'includes/language/' . cmtx_setting('language_backend') . '/login.php'; //load language file for login
require 'includes/language/' . cmtx_setting('language_backend') . '/dashboard.php'; //load language file for dashboard
require 'includes/language/' . cmtx_setting('language_backend') . '/menu.php'; //load language file for menu
require 'includes/language/' . cmtx_setting('language_backend') . '/titles.php'; //load language file for titles
require 'includes/language/' . cmtx_setting('language_backend') . '/descriptions.php'; //load language file for descriptions
require 'includes/language/' . cmtx_setting('language_backend') . '/links.php'; //load language file for links
require 'includes/language/' . cmtx_setting('language_backend') . '/messages.php'; //load language file for messages
require 'includes/language/' . cmtx_setting('language_backend') . '/fields.php'; //load language file for fields
require 'includes/language/' . cmtx_setting('language_backend') . '/notes.php'; //load language file for notes
require 'includes/language/' . cmtx_setting('language_backend') . '/hints.php'; //load language file for hints
require 'includes/language/' . cmtx_setting('language_backend') . '/tables.php'; //load language file for tables
require 'includes/language/' . cmtx_setting('language_backend') . '/buttons.php'; //load language file for buttons
require 'includes/language/' . cmtx_setting('language_backend') . '/prompts.php'; //load language file for prompts

cmtx_error_reporting('includes/logs/errors.log'); //error reporting

cmtx_set_time_zone(cmtx_setting('time_zone')); //set the time zone
/*
require_once 'includes/auth.php'; //authorise login

if (isset($_GET['page']) && $_GET['page'] == "log_out") {
	cmtx_log_out("logout");
}
*/

$cwd = getcwd();
chdir ('../..');
require_once('db.php');
chdir ($cwd);

if(!$db->loggedIn() || ($db->user->userlevel != 'admin' && $db->user->userlevel != 'root'))
{
	die('Log in to LT as admin or root first');
}

if (!isset($_GET['page']) || (!file_exists("includes/pages/" . basename($_GET['page']) . ".php"))) {
	header("Location: " . "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?page=dashboard");
	die();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Commentics: Admin Panel</title>

<meta name="robots" content="noindex"/>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<link rel="stylesheet" type="text/css" href="css/panel.css"/>
<link rel="stylesheet" type="text/css" href="css/general.css"/>

<link rel="stylesheet" type="text/css" href="menu/ddlevelsmenu-base.css"/>
<link rel="stylesheet" type="text/css" href="menu/ddlevelsmenu-topbar.css"/>
<script type="text/javascript" src="menu/ddlevelsmenu.js"></script>

<script type="text/javascript" src="js/tooltip.js"></script>

<?php if ($_GET['page'] == "layout_order" || $_GET['page'] == "layout_form_sort_order_fields" || $_GET['page'] == "layout_form_sort_order_buttons") { ?>
<script type="text/javascript" src="js/scriptaculous/lib/prototype.js"></script>
<script type="text/javascript" src="js/scriptaculous/src/scriptaculous.js"></script>
<?php } else { ?>
<link rel="stylesheet" type="text/css" href="table/css/demo_page.css"/>
<link rel="stylesheet" type="text/css" href="table/css/demo_table.css"/>
<script type="text/javascript" language="javascript" src="table/js/jquery.js"></script>
<script type="text/javascript" language="javascript" src="table/js/jquery.dataTables.js"></script>
<script type="text/javascript">
// <![CDATA[
$(document).ready(function() {
$('#data').dataTable( {
"aaSorting": [ ]
<?php switch ($_GET['page']) { ?>
<?php case "manage_comments":?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,null,null,<?php if (cmtx_setting('enabled_notify')) { echo "null,"; } if (cmtx_setting('show_flag')) { echo "null,null,"; } ?>null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php case "manage_pages": ?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,null,null,null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php case "manage_administrators": ?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,null,null,null,null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php case "manage_bans": ?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php case "manage_subscribers": ?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,null,null,null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php case "layout_form_questions": ?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php case "tool_db_backup": ?>,"aoColumns": [{ "bSearchable": false, "bSortable": false },null,null,null,{ "bSearchable": false, "bSortable": false }] <?php break; ?>
<?php } ?>
} );
} );
// ]]>
</script>
<?php } ?>

<?php if ($_GET['page'] == "edit_comment" && cmtx_setting('enabled_wysiwyg')) { ?>
<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
// <![CDATA[
tinyMCE.init({
	
	// General options
	mode : "textareas",
	theme : "advanced",
	plugins : "inlinepopups,preview,searchreplace,paste,fullscreen",

	// Editing options
	forced_root_block : false,
	remove_linebreaks : false,
	verify_html : false,
	relative_urls : false,
	convert_urls : false,
	element_format : "xhtml",
	entity_encoding : "raw",

	// Layout options
	height : "225",

	// Style formats
	style_formats : [
		{title : 'Bold text', inline : 'b'},
		{title : 'Red text', inline : 'span', styles : {color : '#ff0000'}},
		{title : 'Red header', block : 'h1', styles : {color : '#ff0000'}},
		{title : 'Example 1', inline : 'span', classes : 'example1'},
		{title : 'Example 2', inline : 'span', classes : 'example2'},
		{title : 'Table styles'},
		{title : 'Table row 1', selector : 'tr', classes : 'tablerow1'}
	],
	
	// Theme options
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cut,copy,paste,|,bullist,numlist,|,undo,redo,|,link,unlink,image,|,forecolor,backcolor,sub,sup,hr,|,charmap,search,|,preview,help,code,fullscreen",
	theme_advanced_buttons3 : "",
	theme_advanced_buttons4 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_resizing : true
		
});
// ]]>
</script>
<?php } ?>

<script type="text/javascript">
// <![CDATA[
function check_passwords() {
if (document.administrator.password_1.value == document.administrator.password_2.value) {
return true;
} else {
alert('<?php echo cmtx_escape_js(CMTX_PROMPT_PASSWORDS) ?>');
return false;
}
}
// ]]>
</script>

<script type="text/javascript">
// <![CDATA[
function delete_confirmation() {
var answer = confirm('<?php echo cmtx_escape_js(CMTX_PROMPT_DELETE) ?>')
if (answer) {
return true;
} else {
return false;
}
}
// ]]>
</script>

<script type="text/javascript">
// <![CDATA[
function delete_bulk_confirmation() {
var answer = confirm('<?php echo cmtx_escape_js(CMTX_PROMPT_DELETE_BULK) ?>')
if (answer) {
return true;
} else {
return false;
}
}
// ]]>
</script>

<script type="text/javascript">
// <![CDATA[
function show_hide(id) {
if (id == "php") {
	document.getElementById('smtp').style.display = "none";
	document.getElementById('sendmail').style.display = "none";
} else if (id == "smtp") {
	document.getElementById('smtp').style.display = "inline";
	document.getElementById('sendmail').style.display = "none";
} else if (id == "sendmail") {
	document.getElementById('smtp').style.display = "none";
	document.getElementById('sendmail').style.display = "inline";
} else if (id == "wildcards") {
	if (document.getElementById('wildcards').style.display == 'none') {
		document.getElementById('wildcards').style.display = "inline";
	} else {
		document.getElementById('wildcards').style.display = "none";
	}
} else if (id == "pages") {
	if (document.getElementById('pages').style.display == 'none') {
		document.getElementById('pages').style.display = "inline";
	} else {
		document.getElementById('pages').style.display = "none";
	}
} else if (id == "allowed_pages") {
	if (document.getElementById('allowed_pages').style.display == 'none') {
		document.getElementById('allowed_pages').style.display = "inline";
	} else {
		document.getElementById('allowed_pages').style.display = "none";
	}
} else if (id == "gravatar") {
	var e = document.getElementById('gravatar_defaults');
	if (e.options[e.selectedIndex].value == 'custom') {
		document.getElementById('gravatar_custom').style.display = "inline";
	} else {
		document.getElementById('gravatar_custom').style.display = "none";
	}
}
}
// ]]>
</script>

<script type="text/javascript">
// <![CDATA[
function bulk_select() {
if (document.getElementById('select_all').checked) {
	for (var i = 0; i < document.getElementById('datatables').elements.length; i++) {
		document.getElementById('datatables').elements[i].checked = true;
	}
} else {
	for (var i = 0; i < document.getElementById('datatables').elements.length; i++) {
		document.getElementById('datatables').elements[i].checked = false;
	}
}
}
// ]]>
</script>

<script type="text/javascript">
// <![CDATA[
function bulk_check() {
var all_checked = true;
for (var i = 0; i < document.getElementById('datatables').elements.length; i++) {
	if (document.getElementById('datatables').elements[i].type == 'checkbox' && document.getElementById('datatables').elements[i].id != 'select_all'  && !document.getElementById('datatables').elements[i].checked) {
		all_checked = false;
	}
}
if (all_checked) {
	document.getElementById('select_all').checked = true;
} else {
	document.getElementById('select_all').checked = false;
}
}
// ]]>
</script>

<?php if ($_GET['page'] == "report_viewers" && cmtx_setting('viewers_refresh_enabled')) { ?>
<meta http-equiv="refresh" content="<?php echo cmtx_setting('viewers_refresh_time'); ?>">
<?php } ?>

</head>
<body>

<a href="index.php?page=dashboard"><img src="images/commentics/logo.png" class="logo" title="Commentics" alt="Commentics"/></a>

<?php
require 'menu/menu.php';

echo "<p />";

if (file_exists("../admin/")) {
?>
<span class='negative'>The admin folder has not been renamed.</span>
<p />
This is an important security step.
<p />
To rename the admin folder, load your FTP software (e.g. FileZilla) and rename the folder below:
<br />
<i>http://www.domain.com/comments<b>/admin/</b></i>
<p />
Then, in your web browser, navigate to your renamed admin folder:
<br />
<i>http://www.domain.com/comments<b>/renamed_admin_folder/</b></i>
<p />
Enter the name of your renamed admin folder in Settings -> System.
<?php
die();
}

if (file_exists("../installer/")) {
?>
<span class='negative'>The installer folder has not been deleted.</span>
<p />
This is an important security step.
<p />
To delete the installer folder, load your FTP software (e.g. FileZilla) and delete the folder below:
<br />
<i>http://www.domain.com/comments<b>/installer/</b></i>
<p />
Then refresh this page.
<p />
<input type="button" class="button" name="refresh" title="<?php echo CMTX_BUTTON_REFRESH; ?>" value="<?php echo CMTX_BUTTON_REFRESH; ?>" onclick="window.location.reload()"/>
<?php
die();
}

if (isset($_POST['chmod'])) {
cmtx_check_csrf_form_key();
@chmod("../includes/db/details.php", 0444);
}
if (isset($_POST['check'])) {
cmtx_check_csrf_form_key();
mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '0' WHERE `title` = 'check_db_file'");
}
if (cmtx_setting('check_db_file') && !isset($_POST['check']) && is_writable("../includes/db/details.php")) {
?>
<span class='negative'>The database file is writable.</span>
<p />
This is an important security step.
<p />
To protect this file, please click the 'Set Permission' button below.
<p />
If that fails then you may have to disable the check.
<p />
<form name="db_file" id="db_file" action="index.php?page=dashboard" method="post">
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="chmod" title="<?php echo CMTX_BUTTON_CHMOD; ?>" value="<?php echo CMTX_BUTTON_CHMOD; ?>"/>
<input type="submit" class="button" name="check" title="<?php echo CMTX_BUTTON_CHECK; ?>" value="<?php echo CMTX_BUTTON_CHECK; ?>"/>
</form>
<?php
die();
}

if (isset($_POST['check_url'])) {
	cmtx_check_csrf_form_key();
	if ($_POST['comments_url_action'] == "1") {
		$_SESSION['cmtx_comments_url'] = "1";
		?><script type="text/javascript">window.location.href = "index.php?page=settings_system"</script><?php
	} else {
		mysql_query("UPDATE `" . $cmtx_mysql_table_prefix . "settings` SET `value` = '0' WHERE `title` = 'check_comments_url'");
	}
}
if (cmtx_setting('check_comments_url') && !isset($_SESSION['cmtx_comments_url'])) {
$setting = cmtx_url_decode(str_ireplace("www.", "", parse_url(cmtx_setting('url_to_comments_folder'), PHP_URL_HOST)) . parse_url(cmtx_setting('url_to_comments_folder'), PHP_URL_PATH));
$the_url = cmtx_url_decode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
if (stripos($the_url, $setting) === false) {
?>
<span class='negative'>The 'Comments URL' setting is incorrect.</span>
<p />
The URL to the /comments/ folder in Settings -> System appears to be incorrect:
<p />
<?php echo cmtx_setting('url_to_comments_folder'); ?>
<p />
<form name="comments_url" id="comments_url" action="index.php?page=dashboard" method="post">
<input type="radio" checked="checked" name="comments_url_action" value="1"> I will fix this now.
<br />
<input type="radio" name="comments_url_action" value="2"> It's actually correct.
<p />
<?php cmtx_set_csrf_form_key(); ?>
<input type="submit" class="button" name="check_url" title="<?php echo CMTX_BUTTON_UPDATE; ?>" value="<?php echo CMTX_BUTTON_UPDATE; ?>"/>
</form>
<?php
die();
}
}

if (cmtx_setting('check_comments_url') && !isset($_SESSION['cmtx_comments_url'])) {
	if (cmtx_setting('check_referrer')) {
		if (isset($_SERVER['HTTP_REFERER'])) {
			$referrer = cmtx_url_decode($_SERVER['HTTP_REFERER']);
			$host = cmtx_url_decode(str_ireplace("www.", "", parse_url(cmtx_setting('url_to_comments_folder'), PHP_URL_HOST)));
			if (!empty($host) && !preg_match('/\.[0-9]+\./i', $host)) {
				if (!stristr($referrer, $host)) {
					?>
					<span class='negative'>The referrer has external origin.</span>
					<p />
					You have arrived at this page from outside of the admin panel.
					<p />
					Please access this page through the menu above.
					<?php
					die();
				}
			}
		}
	}
}

if (cmtx_restrict_page($_GET['page'])) {
	echo "<h3>Page Restricted</h3>";
	echo "<hr class='title'/>";
	echo "You don't have permission to view this page.";
	die();
}

$access_log = mysql_query("SELECT * FROM `" . $cmtx_mysql_table_prefix . "access`");
$total = mysql_num_rows($access_log);
if ($total >= 100) {
mysql_query("DELETE FROM `" . $cmtx_mysql_table_prefix . "access` ORDER BY `dated` ASC LIMIT 1");
}

if (file_exists("includes/pages/" . basename($_GET['page']) . ".php")) {

	$admin_id = cmtx_get_admin_id();
	$username = cmtx_sanitize($_SESSION['cmtx_username']);
	$page = cmtx_sanitize(basename($_GET['page']));
	$ip_address = cmtx_get_ip_address();
	mysql_query("INSERT INTO `" . $cmtx_mysql_table_prefix . "access` (`admin_id`, `username`, `ip_address`, `page`, `dated`) VALUES ('$admin_id', '$username', '$ip_address','$page', NOW());");

	require 'includes/pages/' . basename($_GET['page']) . '.php';
	
} else {

	require 'includes/pages/dashboard.php';

}


function add_include_path ($path)
{
    foreach (func_get_args() AS $path)
    {
        if (!file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir'))
        {
            trigger_error("Include path '{$path}' not exists", E_USER_WARNING);
            continue;
        }
       
        $paths = explode(PATH_SEPARATOR, get_include_path());
       
        if (array_search($path, $paths) === false)
            array_push($paths, $path);
       
        set_include_path(implode(PATH_SEPARATOR, $paths));
    }
}
?>
</body>
</html>