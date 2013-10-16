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
define('CMTX_ANCHOR_FORM', '#cmtx_form');

/* Heading */
define('CMTX_FORM_HEADING', 'Добави коментар');

/* Form disabled messages */
define('CMTX_ALL_FORMS_DISABLED', 'Добавянето на коментари е изключено.');
define('CMTX_THIS_FORM_DISABLED', 'Добавянето на коментари е изключено на тази страница.');

/* Open form link */
define('CMTX_OPEN_FORM', 'Отворете формата');

/* JavaScript disabled message */
define('CMTX_JAVASCRIPT_DISABLED', 'Трябва да включите JavaScript, за да работят някои функции.');

/* Reply */
define('CMTX_REPLY_MESSAGE', 'Вие отговаряте на');
define('CMTX_REPLY_CANCEL', '[Откажи]');
define('CMTX_REPLY_NOBODY', 'Вие не отговаряте на никого.');

/* Required */
define('CMTX_REQUIRED_SYMBOL', '*');
define('CMTX_REQUIRED_SYMBOL_MESSAGE', CMTX_REQUIRED_SYMBOL . ' Необходима информация');

/* Field labels */
define('CMTX_LABEL_NAME', 'Име:');
define('CMTX_LABEL_EMAIL', 'Имейл:');
define('CMTX_LABEL_WEBSITE', 'Уеб сайт:');
define('CMTX_LABEL_TOWN', 'Град:');
define('CMTX_LABEL_COUNTRY', 'Държава:');
define('CMTX_LABEL_RATING', 'Оценка:');
define('CMTX_LABEL_COMMENT', 'Коментар:');
define('CMTX_LABEL_QUESTION', 'Въпрос:');
define('CMTX_LABEL_CAPTCHA', 'Код:');

/* Field titles */
define('CMTX_TITLE_NAME', 'Въведете име');
define('CMTX_TITLE_EMAIL', 'Въведете имейл');
define('CMTX_TITLE_WEBSITE', 'Въведете уеб сайт');
define('CMTX_TITLE_TOWN', 'Въведете град');
define('CMTX_TITLE_COMMENT', 'Въведете коментар');
define('CMTX_TITLE_QUESTION', 'Въведете отговор на въпроса');
define('CMTX_TITLE_NOTIFY', 'Искам да получавам известия по имейл');
define('CMTX_TITLE_REMEMBER', 'Запомни въведения текст');
define('CMTX_TITLE_PRIVACY', 'Съгласен съм с Условията за защита на личните данни');
define('CMTX_TITLE_TERMS', 'Съгласен съм с другите условия');
define('CMTX_TITLE_SUBMIT', 'Добави коментар');
define('CMTX_TITLE_PREVIEW', 'Покажи');

/* Note displayed after email field */
define('CMTX_NOTE_EMAIL', '(няма да бъде публикуван)');

/* Text displayed for JavaScript BB Code prompts */
define('CMTX_PROMPT_ENTER_BULLET', 'Добавете от списъка. Натиснете отказвам или оставете празно, за да приключите със списъка.');
define('CMTX_PROMPT_ENTER_ANOTHER_BULLET', 'Добавете друго от списъка. Натиснете отказвам или оставете празно, за да приключите със списъка');
define('CMTX_PROMPT_ENTER_NUMERIC', 'Добавете от списъка. Натиснете отказвам или оставете празно, за да приключите със списъка.');
define('CMTX_PROMPT_ENTER_ANOTHER_NUMERIC', 'Добавете друго от списъка. Натиснете отказвам или оставете празно, за да приключите със списъка.');
define('CMTX_PROMPT_ENTER_LINK', 'Моля въведете линка към уеб сайта');
define('CMTX_PROMPT_ENTER_LINK_TITLE', 'Допълнително, можете да добавите и заглавие за връзката');
define('CMTX_PROMPT_ENTER_EMAIL', 'Моля въведете имейл адреса');
define('CMTX_PROMPT_ENTER_EMAIL_TITLE', 'Допълнително, можете да добавите и заглавие за имейл адреса');
define('CMTX_PROMPT_ENTER_IMAGE', 'Моля въведете връзката за изображението');
define('CMTX_PROMPT_ENTER_VIDEO', 'Моля въведете връзката за видеото. Поддържаните сайтове са:\nYouTube, Vimeo, MetaCafe и Dailymotion.');

/* Text displayed for invalid BB Code entries */
define('CMTX_BB_INVALID_LINK', '<i>(невалидна-връзка)</i>');
define('CMTX_BB_INVALID_EMAIL', '<i>(невалиден-имейл)</i>');
define('CMTX_BB_INVALID_IMAGE', '<i>(невалидно-изображение)</i>');
define('CMTX_BB_INVALID_VIDEO', '<i>(невалидно-видео)</i>');

/* Text displayed before/after the counter */
define('CMTX_TEXT_COUNTER', '%s');

/* Text displayed before question field */
define('CMTX_TEXT_QUESTION', 'Въведете отговора:');
/* Text displayed for Securimage captcha */
define('CMTX_TEXT_SECURIMAGE', 'Enter code:');
define('CMTX_TITLE_SECURIMAGE', 'Enter code from image');
define('CMTX_TITLE_SECURIMAGE_AUDIO', 'Audio');
define('CMTX_TITLE_SECURIMAGE_REFRESH', 'Refresh');

/* Text displayed if ReCaptcha key(s) missing */
define('CMTX_RECAPTCHA_NO_KEY', 'API ключ(ове) липсват е Settings -> ReCaptcha');

/* Text displayed after notify checkbox */
define('CMTX_TEXT_NOTIFY', 'Информирай ме за нови коментари по имейл.');

/* Text displayed after remember checkbox */
define('CMTX_TEXT_REMEMBER', 'Запомни детайлите ми на този компютър.');

/* Text displayed after privacy checkbox */
define('CMTX_TEXT_PRIVACY', 'Прочетох и разбрах <a href="' . cmtx_comments_folder() . 'agreement/bulgarian/privacy_policy.html" title="Разгледай Условията за защита на личните данни" target="_blank" rel="nofollow">Условията за защита на личните данни</a>.');

/* Text displayed after terms checkbox */
define('CMTX_TEXT_TERMS', 'Прочетох и се съгласявам с <a href="' . cmtx_comments_folder() . 'agreement/bulgarian/terms_and_conditions.html" title="Разгледай другите условия" target="_blank" rel="nofollow">другите условия</a>.');

/* Text for form submit button */
define('CMTX_SUBMIT_BUTTON', 'Добави коментар');

/* Text for form preview button */
define('CMTX_PREVIEW_BUTTON', 'Прегледай');

/* Text for form buttons when processing */
define('CMTX_PROCESSING_BUTTON', 'Моля изчакайте..');

/* Text for 'powered by' statement */
define('CMTX_POWERED_BY', 'Осъществено от');

?>