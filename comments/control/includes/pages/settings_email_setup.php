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
?>

<div class='page_help_block'>
<a class='page_help_text' href="http://www.commentics.org/wiki/doku.php?id=admin:<?php echo $_GET['page']; ?>" target="_blank"><?php echo CMTX_LINK_HELP; ?></a>
</div>

<h3><?php echo CMTX_TITLE_EMAIL_SETUP; ?></h3>
<hr class="title"/>

<p />

<?php echo CMTX_DESC_SETTINGS_EMAIL_SETUP; ?>

<p />

<table class="email_setup">
<tr>
<td><img src="images/email_setup/method.png" title="Method" alt="Method"></td> <td><a href="index.php?page=settings_email_method"><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_METHOD; ?></a></td> <td><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_METHOD_INFO; ?></td>
</tr>
<tr>
<td><img src="images/email_setup/sender.png" title="Sender" alt="Sender"></td> <td><a href="index.php?page=settings_email_sender"><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_SENDER; ?></a></td> <td><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_SENDER_INFO; ?></td>
</tr>
<tr>
<td><img src="images/email_setup/signature.png" title="Signature" alt="Signature"></td> <td><a href="index.php?page=settings_email_signature"><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_SIGNATURE; ?></a></td> <td><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_SIGNATURE_INFO; ?></td>
</tr>
<tr>
<td><img src="images/email_setup/test.png" title="Test" alt="Test"></td> <td><a href="index.php?page=settings_email_test"><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_TEST; ?></a></td> <td><?php echo CMTX_FIELD_VALUE_EMAIL_SETUP_TEST_INFO; ?></td>
</tr>
</table>