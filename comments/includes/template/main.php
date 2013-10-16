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

<?php
if (cmtx_setting('sort_order_parts') == '1,2') { //display comments first

	if (cmtx_setting('split_screen')) { //side-by-side layout
	
		echo "<table style='width:100%; padding:0px; border:none;'>";
		echo "<tr>";
		echo "<td style='vertical-align:top;'>";
		require_once $cmtx_path . 'includes/template/comments.php'; //load comments
		echo "</td>";
		echo "<td style='width:530px; padding-left:75px; vertical-align:top;'>";
		require_once $cmtx_path . 'includes/template/form.php'; //load form
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		
	} else { //default vertical layout
	
		require_once $cmtx_path . 'includes/template/comments.php'; //load comments
		echo "<div class='cmtx_height_for_divider'></div>"; //height between comments/form
		require_once $cmtx_path . 'includes/template/form.php'; //load form
		
	}
	
} else { //display form first

	if (cmtx_setting('split_screen')) { //side-by-side layout
	
		echo "<table style='width:100%; padding:0px; border:none;'>";
		echo "<tr>";
		echo "<td style='width:450px; vertical-align:top;'>";
		require_once $cmtx_path . 'includes/template/form.php'; //load form
		echo "</td>";
		echo "<td style='width:50px;'></td>";
		echo "<td style='vertical-align:top;'>";
		require_once $cmtx_path . 'includes/template/comments.php'; //load comments
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		
	} else { //default vertical layout
	
		require_once $cmtx_path . 'includes/template/form.php'; //load form
		echo "<div class='cmtx_height_for_divider'></div>"; //height between form/comments
		require_once $cmtx_path . 'includes/template/comments.php'; //load comments
		
	}
	
}
?>