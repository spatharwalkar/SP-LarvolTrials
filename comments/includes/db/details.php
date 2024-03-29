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

if(strpos(getcwd(), 'comments') !== false){chdir ("..");}
require_once('settings.php');

//larvol: replaced separate db connection details with ones pulled from main config
//ENTER DATABASE INFORMATION HERE*****************************************************
$cmtx_mysql_database = DB_NAME;				// The name of the database you created
$cmtx_mysql_username = DB_USER; 				// Your MySQL username
$cmtx_mysql_password = DB_PASS;			 	// Your MySQL password
$cmtx_mysql_host = DB_SERVER;			// Usually 'localhost'. Can also be an IP address.
$cmtx_mysql_port = '';					// In most cases leave blank.
$cmtx_mysql_table_prefix = 'commentics_';			// In most cases leave blank.
//************************************************************************************

?>