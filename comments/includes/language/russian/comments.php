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
define('CMTX_COMMENTS_HEADING', 'Отзывы');

/* No comments message */
define('CMTX_NO_COMMENTS', 'Отзывов пока нет.');

/* Sort By title */
define('CMTX_TITLE_SORT_BY', 'Сортировать по');

/* Sort By items */
define('CMTX_SORT_1', 'Новые');
define('CMTX_SORT_2', 'Старые');
define('CMTX_SORT_3', 'Полезные');
define('CMTX_SORT_4', 'Бесполезные');
define('CMTX_SORT_5', 'Положительные');
define('CMTX_SORT_6', 'Отрицательные');

/* Topic */
define('CMTX_TOPIC_INTRO', 'Вы просматриваете');

/* Star titles */
define('CMTX_TITLE_FULL_STAR', 'Полная звезда');
define('CMTX_TITLE_HALF_STAR', 'Половина звезды');
define('CMTX_TITLE_EMPTY_STAR', 'Пустая звезда');

/* Says */
define('CMTX_SAYS', 'говорит...');

/* Read More */
define('CMTX_READ_MORE', '... Читать дальше');
define('CMTX_TITLE_READ_MORE', 'Читать отзыв полностью');

/* Admin Reply */
define('CMTX_REPLY_INTRO', 'Администратор:');

/* Date */
define('CMTX_TODAY', 'Сегодня');
define('CMTX_YESTERDAY', 'Вчера');

/* Like Dislike */
define('CMTX_TITLE_LIKE', 'Проголосовать за отзыв');
define('CMTX_TITLE_DISLIKE', 'Проголосовать против отзыва');
define('CMTX_VOTE_LIKE', 'Спасибо за Ваш голос');
define('CMTX_VOTE_DISLIKE', 'Спасибо за Ваш голос');
define('CMTX_VOTE_NO_COMMENT', 'Данный комментарий больше не существует');
define('CMTX_VOTE_OWN_COMMENT', 'Вы не можете голосовать за собственный отзыв');
define('CMTX_VOTE_ALREADY_VOTED', 'Вы уже голосовали за данный отзыв');
define('CMTX_VOTE_BANNED', 'Вы были забанены');

/* Flag */
define('CMTX_FLAG', 'Метка');
define('CMTX_TITLE_FLAG', 'Пожаловаться на отзыв');
define('CMTX_FLAG_CONFIRM', 'Вы уверены, что хотите пожаловаться на данный отзыв?');
define('CMTX_FLAG_NO_COMMENT', 'Данный комментарий больше не существует');
define('CMTX_FLAG_OWN_COMMENT', 'Вы не можете пожаловаться на собственный отзыв');
define('CMTX_FLAG_ADMIN_COMMENT', 'You cannot report an admin comment');
define('CMTX_FLAG_BANNED', 'Вы были забанены');
define('CMTX_FLAG_REPORT_LIMIT', 'Вы больше не можете жаловаться на отзывы');
define('CMTX_FLAG_ALREADY_REPORTED', 'Вы уже жаловались на данный отзыв');
define('CMTX_FLAG_ALREADY_FLAGGED', 'Данный отзыв уже помечен');
define('CMTX_FLAG_ALREADY_VERIFIED', 'Данный отзыв был проверен');
define('CMTX_FLAG_REPORT_SENT', 'Спасибо за сообщение');

/* Permalink */
define('CMTX_PERMALINK', 'Permalink');
define('CMTX_TITLE_PERMALINK', 'Permalink for this comment');

/* Reply */
define('CMTX_REPLY', 'Ответить');
define('CMTX_TITLE_REPLY', 'Ответить на комментарий');

/* RSS */
define('CMTX_RSS_THIS_PAGE', 'Эта страница');
define('CMTX_RSS_ALL_PAGES', 'Все страницы');
define('CMTX_TITLE_RSS_THIS', 'Получать RSS оповещения для этой страницы ');
define('CMTX_TITLE_RSS_ALL', 'Получать RSS оповещения для всех страниц');

/* Page Number */
define('CMTX_INFO_PAGE', 'Страница %d из %d');

/* Pagination */
define('CMTX_PAGINATION_FIRST', '[В начало]');
define('CMTX_PAGINATION_PREVIOUS', '&lt;'); // < character
define('CMTX_PAGINATION_NEXT', '&gt;'); // > character
define('CMTX_PAGINATION_LAST', '[В конец]');
define('CMTX_TITLE_PAG_FIRST', 'первое');
define('CMTX_TITLE_PAG_PREVIOUS', 'предыдущее');
define('CMTX_TITLE_PAG_NEXT', 'следующее');
define('CMTX_TITLE_PAG_LAST', 'последнее');

?>