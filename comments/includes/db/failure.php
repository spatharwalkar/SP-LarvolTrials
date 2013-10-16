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


function cmtx_db_error_general() { //display a general database error to user

	echo "<h3>Commentics</h3>";
	echo "<div style='margin-bottom: 10px;'></div>";
	echo "<p>Sorry, there is a database problem.</p>";
	echo "<p>Please check back shortly. Thanks.</p>";

} //end of db-error-general function


function cmtx_db_error_connect() { //display a database connection error to admin

	if (defined("CMTX_IN_ADMIN")) {
		echo "<img src='images/commentics/logo.png' style='padding-bottom:15px' title='Commentics' alt='Commentics'/>";
		echo "<br />";
	}
	echo "Sorry, there is a <span style='font-weight:bold;color:#CC0000;'>database connection</span> problem.";
	echo "<p></p>";
	echo "Please open <b>/comments/includes/db/details.php</b>";
	echo "<ol>";
	echo "<li>Is the <b>\$cmtx_mysql_username</b> value correct?</li>";
	echo "<li>Is the <b>\$cmtx_mysql_password</b> value correct?</li>";
	echo "<li>Is the <b>\$cmtx_mysql_host</b> value correct?</li>";
	echo "<li>Is the <b>\$cmtx_mysql_port</b> value correct?</li>";
	echo "</ol>";
	echo "If the above is ok, it may be that your database server is down.";

} //end of db-error-connect function


function cmtx_db_error_select() { //display a database selection error to admin

	if (defined("CMTX_IN_ADMIN")) {
		echo "<img src='images/commentics/logo.png' style='padding-bottom:15px' title='Commentics' alt='Commentics'/>";
		echo "<br />";
	}
	echo "Sorry, there is a <span style='font-weight:bold;color:#CC0000;'>database selection</span> problem.";
	echo "<p></p>";
	echo "The following are steps to help diagnose it:";
	echo "<ol>";
	echo "<li>Did you create the database?</li><br/>";
	echo "<li>Does the database still exist?</li><br/>";
	echo "<li>";
	echo "<i>(a)</i> Open the file /comments/includes/db/details.php";
	echo "<br/>";
	echo "<i>(b)</i> Check the value of <b>\$cmtx_mysql_database</b>";
	echo "<br/>";
	echo "<i>(c)</i> Does it match with the database you created?";
	echo "</li>";
	echo "<br/>";
	echo "<li>Does your MySQL user have the right privileges?</li>";
	echo "<br/>";
	echo "<li>Try setting normal file permissions for details.php</li>";
	echo "</ol>";

} //end of db-error-select function


function cmtx_db_error_table() { //display a database tables error to admin

	echo "<img src='images/commentics/logo.png' style='padding-bottom:15px' title='Commentics' alt='Commentics'/>";
	echo "<br />";
	echo "Sorry, there is a <span style='font-weight:bold;color:#CC0000;'>database tables</span> problem.";
	echo "<p></p>";
	echo "<u>If you haven't yet installed the script</u>";
	echo "<ul>";
	echo "<li>You need to run the installer (domain.com/comments/installer/)</li>";
	echo "</ul>";
	echo "<p></p>";
	echo "<u>If you have already installed the script</u>";
	echo "<ul>";
	echo "<li>Please open <b>/comments/includes/db/details.php</b></li>";
	echo "<li>Is the <b>\$cmtx_mysql_table_prefix</b> value correct?</li><br/>";
	echo "<li>Using <i>phpMyAdmin</i>, check that the database tables exist.</li>";
	echo "</ul>";

} //end of db-error-table function
?>