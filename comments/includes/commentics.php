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

/*************************************************************** DIRECT ACCESS **********************************************************/
if (!defined('IN_COMMENTICS')) { die('Access Denied.'); }
/****************************************************************************************************************************************/


/*************************************************************** VARIABLES (1/2) ********************************************************/
require_once $cmtx_path . 'includes/variables/set_variables.php'; //set variables for page
/****************************************************************************************************************************************/


/*************************************************************** FUNCTIONS **************************************************************/
require_once $cmtx_path . 'includes/functions/page.php'; //load functions file for page
require_once $cmtx_path . 'includes/functions/comments.php'; //load functions file for comments
require_once $cmtx_path . 'includes/functions/processor.php'; //load functions file for processor
require_once $cmtx_path . 'includes/functions/form.php'; //load functions file for form
/****************************************************************************************************************************************/


/*************************************************************** DATABASE (1/2) *********************************************************/
require_once $cmtx_path . 'includes/db/connect.php'; //connect to database
if ($cmtx_db_ok) { //if database connection okay
/****************************************************************************************************************************************/


/*************************************************************** LANGUAGE ***************************************************************/
require_once $cmtx_path . 'includes/language/' . cmtx_setting('language_frontend') . '/page.php'; //load language file for page
require_once $cmtx_path . 'includes/language/' . cmtx_setting('language_frontend') . '/comments.php'; //load language file for comments
require_once $cmtx_path . 'includes/language/' . cmtx_setting('language_frontend') . '/processor.php'; //load language file for processor
require_once $cmtx_path . 'includes/language/' . cmtx_setting('language_frontend') . '/form.php'; //load language file for form
require_once $cmtx_path . 'includes/language/' . cmtx_setting('language_frontend') . '/countries.php'; //load language file for countries
require_once $cmtx_path . 'includes/language/' . cmtx_setting('language_frontend') . '/ratings.php'; //load language file for ratings
/****************************************************************************************************************************************/


/*************************************************************** ERROR REPORTING ********************************************************/
cmtx_error_reporting($cmtx_path . 'includes/logs/errors.log');
/****************************************************************************************************************************************/


/*************************************************************** ADMIN DETECTION ********************************************************/
$cmtx_is_admin = cmtx_is_administrator(); //detect admin
/****************************************************************************************************************************************/


/*************************************************************** FORM VALUES ************************************************************/
cmtx_load_form_login(); //load login values
cmtx_load_form_defaults(); //load default values
cmtx_load_form_cookie(); //load cookie values
/****************************************************************************************************************************************/


/*************************************************************** MAINTENANCE (1/2) ******************************************************/
if (!cmtx_in_maintenance()) { //if not in maintenance
/****************************************************************************************************************************************/


/*************************************************************** PAGE SETUP *************************************************************/
cmtx_page_setup(); //page setup
/****************************************************************************************************************************************/


/*************************************************************** TIME ZONE **************************************************************/
cmtx_set_time_zone(cmtx_setting('time_zone')); //set the time zone
/****************************************************************************************************************************************/


/*************************************************************** UNBAN USER *************************************************************/
cmtx_unban_user(); //unban user if requested by admin
/****************************************************************************************************************************************/


/*************************************************************** LOAD PROCESSOR *********************************************************/
require_once $cmtx_path . 'includes/app/processor.php'; //load file for form processor
/****************************************************************************************************************************************/


/*************************************************************** DISPLAY DATA ***********************************************************/
require_once $cmtx_path . 'includes/template/main.php'; //load main template
/****************************************************************************************************************************************/


/*************************************************************** VIEWERS ****************************************************************/
if (cmtx_setting('viewers_enabled')) { //if viewers feature is enabled
	if (!$cmtx_is_admin) { cmtx_add_viewer(); } //add viewer if not administrator
}
/****************************************************************************************************************************************/


/*************************************************************** TASK SYSTEM ************************************************************/
require_once $cmtx_path . 'includes/tasks/tasks.php'; //load task system
/****************************************************************************************************************************************/


/*************************************************************** MAINTENANCE (2/2) ******************************************************/
} //end of if-not-in-maintenance
/****************************************************************************************************************************************/


/*************************************************************** DATABASE (2/2) *********************************************************/
cmtx_reconnect_db(); //reconnect original connection
} //end of is-database-connection-okay
/****************************************************************************************************************************************/


/*************************************************************** VARIABLES (2/2) ********************************************************/
require_once $cmtx_path . 'includes/variables/unset_variables.php'; //unset variables for page
/****************************************************************************************************************************************/
?>