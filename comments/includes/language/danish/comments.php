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
define('CMTX_ANCHOR_COMMENTS', '#cmtx_comments');

/* Heading */
define('CMTX_COMMENTS_HEADING', 'Kommentarer');

/* No comments message */
define('CMTX_NO_COMMENTS', 'Bliv den første til at kommentere.');

/* Sort By title */
define('CMTX_TITLE_SORT_BY', 'Sorteringsorden');

/* Sort By items */
define('CMTX_SORT_1', 'Nyeste');
define('CMTX_SORT_2', 'Ældste');
define('CMTX_SORT_3', 'Brugbar');
define('CMTX_SORT_4', 'Ubrugelig');
define('CMTX_SORT_5', 'Positiv');
define('CMTX_SORT_6', 'Vigtig');

/* Topic */
define('CMTX_TOPIC_INTRO', 'Emne');

/* Star titles */
define('CMTX_TITLE_FULL_STAR', 'Hel stjerne');
define('CMTX_TITLE_HALF_STAR', 'Halv stjerne');
define('CMTX_TITLE_EMPTY_STAR', 'Tom stjerne');

/* Says */
define('CMTX_SAYS', 'skrev ...');

/* Read More */
define('CMTX_READ_MORE', '... Læs mere');
define('CMTX_TITLE_READ_MORE', 'Læs hele kommentaren');

/* Admin Reply */
define('CMTX_REPLY_INTRO', 'Admin:');

/* Date */
define('CMTX_TODAY', 'I dag');
define('CMTX_YESTERDAY', 'I går');

/* Like Dislike */
define('CMTX_TITLE_LIKE', 'Stem denne kommentar op');
define('CMTX_TITLE_DISLIKE', 'Stem denne kommentar ned');
define('CMTX_VOTE_LIKE', 'Tak for din stemme');
define('CMTX_VOTE_DISLIKE', 'Tak for din stemme');
define('CMTX_VOTE_NO_COMMENT', 'Denne kommentar findes ikke længere');
define('CMTX_VOTE_OWN_COMMENT', 'Du kan ikke stemme på dine egne kommentarer');
define('CMTX_VOTE_ALREADY_VOTED', 'Du har allerede stemt på denne kommentar');
define('CMTX_VOTE_BANNED', 'Du er tidligere blevet banned');

/* Flag */
define('CMTX_FLAG', 'Rapporter');
define('CMTX_TITLE_FLAG', 'Rapporter denne kommentar');
define('CMTX_FLAG_CONFIRM', 'Er du sikker på, at du vil rapportere denne kommentar?');
define('CMTX_FLAG_NO_COMMENT', 'Denne kommentar findes ikke længere');
define('CMTX_FLAG_OWN_COMMENT', 'Du kan ikke rapportere dine egne kommentarer');
define('CMTX_FLAG_ADMIN_COMMENT', 'Du kan ikke rapportere en kommentar fra admin');
define('CMTX_FLAG_BANNED', 'Du er tidligere blevet banned');
define('CMTX_FLAG_REPORT_LIMIT', 'Du kan ikke rapportere flere kommentarer');
define('CMTX_FLAG_ALREADY_REPORTED', 'Du har allerede rapporteret denne kommentar');
define('CMTX_FLAG_ALREADY_FLAGGED', 'Denne kommentar er allerede blevet rapporteret');
define('CMTX_FLAG_ALREADY_VERIFIED', 'Denne kommentar er allerede blevet godkendt');
define('CMTX_FLAG_REPORT_SENT', 'Tak for din rapportering');

/* Permalink */
define('CMTX_PERMALINK', 'Permalink');
define('CMTX_TITLE_PERMALINK', 'Permalink for denne kommentar');

/* Reply */
define('CMTX_REPLY', 'Svar');
define('CMTX_TITLE_REPLY', 'Svar på denne kommentar');

/* RSS */
define('CMTX_RSS_THIS_PAGE', 'Feed for denne side');
define('CMTX_RSS_ALL_PAGES', 'Feed for alle sider');
define('CMTX_TITLE_RSS_THIS', 'Abonner på RSS-feed for denne side');
define('CMTX_TITLE_RSS_ALL', 'Abonner på RSS-feed for alle sider');

/* Page Number */
define('CMTX_INFO_PAGE', 'Side %d af %d');

/* Pagination */
define('CMTX_PAGINATION_FIRST', '[først]');
define('CMTX_PAGINATION_PREVIOUS', '&lt;'); // < character
define('CMTX_PAGINATION_NEXT', '&gt;'); // > character
define('CMTX_PAGINATION_LAST', '[sidst]');
define('CMTX_TITLE_PAG_FIRST', 'først');
define('CMTX_TITLE_PAG_PREVIOUS', 'forrige');
define('CMTX_TITLE_PAG_NEXT', 'næste');
define('CMTX_TITLE_PAG_LAST', 'sidste');

?>