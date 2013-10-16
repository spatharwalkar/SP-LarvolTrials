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

$cmtx_ratings = '<select name="cmtx_rating" title="' . cmtx_define(CMTX_TITLE_RATING) . '">
<option value="">' . cmtx_define(CMTX_TOP_RATING) . '</option>
<option value="1">1 - ' . cmtx_define(CMTX_RATING_ONE) . '</option>
<option value="2">2 - ' . cmtx_define(CMTX_RATING_TWO) . '</option>
<option value="3">3 - ' . cmtx_define(CMTX_RATING_THREE) . '</option>
<option value="4">4 - ' . cmtx_define(CMTX_RATING_FOUR) . '</option>
<option value="5">5 - ' . cmtx_define(CMTX_RATING_FIVE) . '</option>
</select>';

$cmtx_rated = '<select name="cmtx_rating" disabled="disabled" title="' . cmtx_define(CMTX_HAS_RATED) . '">
<option value="">' . cmtx_define(CMTX_HAS_RATED) . '</option>
</select>';

?>