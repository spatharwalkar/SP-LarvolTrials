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

/* Error box */
define('CMTX_ERROR_NUMBER', 'Извиняваме се, но е открита %d грешка при обработката на Вашия коментар.');
define('CMTX_ERRORS_NUMBER', 'Извиняваме се, но са открити %d грешки при обработката на Вашия коментар.');
define('CMTX_ERROR_CORRECTION', 'Моля, поправете тази грешка и изпратете отново формата:');
define('CMTX_ERRORS_CORRECTION', 'Моля, поправете тези грешки и изпратете отново формата:');

/* Preview box */
define('CMTX_PREVIEW_TEXT', 'Преглед');

/* Approval box */
define('CMTX_APPROVAL_OPENING', 'Благодарим Ви.');
define('CMTX_APPROVAL_TEXT', 'Вашият коментар очаква одобрение.');

/* Success box */
define('CMTX_SUCCESS_OPENING', 'Благодарим Ви.');
define('CMTX_SUCCESS_TEXT', 'Вашият коментар е добавен.');

/* Error messages */
define('CMTX_ERROR_MESSAGE_NO_NAME', 'Полето за името не може да бъде празно. Моля, въведете името си.');
define('CMTX_ERROR_MESSAGE_ONE_NAME', 'Само едно име може да бъде въведено в полето. Моля, въведете своето име.');
define('CMTX_ERROR_MESSAGE_INVALID_NAME', 'Името трябва да започва с буква и може да съдържа знака & . \'');
define('CMTX_ERROR_MESSAGE_RESERVED_NAME', 'Въведеното име е запазено. Моля, изберете друго име.');
define('CMTX_ERROR_MESSAGE_BANNED_NAME', 'Въведеното име е забранено. Моля, изберете друго име.');
define('CMTX_ERROR_MESSAGE_DUMMY_NAME', 'Въведеното име не е Вашето. Моля, въведете истинското си име.');
define('CMTX_ERROR_MESSAGE_LINK_IN_NAME', 'Въведеното име съдържа връзка. Моля, въведете истинското си име.');
define('CMTX_ERROR_MESSAGE_NO_EMAIL', 'Полето за имейла не може да бъде празно. Моля, въведете Вашия имейл.');
define('CMTX_ERROR_MESSAGE_INVALID_EMAIL', 'Въведеният имейл не е верен. Моля, проверете въведеното.');
define('CMTX_ERROR_MESSAGE_RESERVED_EMAIL', 'Въведеният имейл е запазен. Моля, въведете Вашия имейл.');
define('CMTX_ERROR_MESSAGE_BANNED_EMAIL', 'Въведеният имейл е забранен. Моля, въведете друг имейл.');
define('CMTX_ERROR_MESSAGE_DUMMY_EMAIL', 'Въведеният имейл не е Ваш. Моля, въведете Вашия имейл.');
define('CMTX_ERROR_MESSAGE_NO_WEBSITE', 'Полето за уеб сайт не може да остане празно. Моля, въведете Вашия уеб сайт.');
define('CMTX_ERROR_MESSAGE_INVALID_WEBSITE', 'Въведеният уеб сайт не е верен. Моля, проверете въведеното.');
define('CMTX_ERROR_MESSAGE_RESERVED_WEBSITE', 'Въведеният уеб сайт е запазен. Моля, въведете Вашия уеб сайт.');
define('CMTX_ERROR_MESSAGE_BANNED_WEBSITE_IN_WEBSITE', 'Въведеният уеб сайт е забранен. Моля да го изтриете.');
define('CMTX_ERROR_MESSAGE_BANNED_WEBSITE_IN_COMMENT', 'Уеб сайтът във Вашия коментар е забранен. Моля да го изтриете.');
define('CMTX_ERROR_MESSAGE_DUMMY_WEBSITE', 'Въведеният уеб сайт не е Ваш. Моля, въведете Вашия уеб сайт.');
define('CMTX_ERROR_MESSAGE_NO_TOWN', 'Полето за града не може да бъде празно. Моля, въведете Вашия град.');
define('CMTX_ERROR_MESSAGE_INVALID_TOWN', 'Градът трябва да започва с буква и може да съдържа знака & . \'');
define('CMTX_ERROR_MESSAGE_RESERVED_TOWN', 'Въведеният град е запазен. Моля, въведете друг град.');
define('CMTX_ERROR_MESSAGE_BANNED_TOWN', 'Въведеният град е забранен. Моля, въведете друг град.');
define('CMTX_ERROR_MESSAGE_DUMMY_TOWN', 'Въведеният град не е Вашият. Моля, въведете Вашия град.');
define('CMTX_ERROR_MESSAGE_LINK_IN_TOWN', 'Въведеният град съдържа връзка. Моля, въведете Вашия град.n.');
define('CMTX_ERROR_MESSAGE_NO_COUNTRY', 'Не е избрано от полето за държава. Моля, изберете държава.');
define('CMTX_ERROR_MESSAGE_INVALID_COUNTRY', 'Избраната държава е невалидна. Моля да се свържете с администратора.');
define('CMTX_ERROR_MESSAGE_COUNTRY_SEARCH', 'Избраната държава не може да бъде намерена. Моля, опитайте отново.');
define('CMTX_ERROR_MESSAGE_NO_RATING', 'Не сте избрали в полето за рейтинг. Моля изберете рейтинг.');
define('CMTX_ERROR_MESSAGE_INVALID_RATING', 'Избраният рейтинг е невалиден. Моля да се свържете с администратора.');
define('CMTX_ERROR_MESSAGE_INVALID_REPLY', 'Коментара, на който отговаряте, е невалиден. Моля, опитайте отново.');
define('CMTX_ERROR_MESSAGE_NO_COMMENT', 'Полето за коментар не може да е празно. Моля, въведете Вашия коментар.');
define('CMTX_ERROR_MESSAGE_COMMENT_MIN', 'Въведеният коментар е твърде кратък. Моля, въведете по-дълъг коментар.');
define('CMTX_ERROR_MESSAGE_COMMENT_MAX', 'Въведеният коментар е твърде дълъг. Моля, въведете по-кратък коментар.');
define('CMTX_ERROR_MESSAGE_COMMENT_MAX_LINES', 'Въведеният коментар съдържа твърде много редове. Моля, въведете по-малко редове.');
define('CMTX_ERROR_MESSAGE_COMMENT_RESUBMIT', 'Изпратили сте този коментар. Моля, изпратете друг коментар.');
define('CMTX_ERROR_MESSAGE_SMILIES_MAX', 'Въведеният коментар съдържа твърде много усмивки (Max: %d).');
define('CMTX_ERROR_MESSAGE_MILD_SWEARING', 'Въведеният коментар съдържа обидни думи. Моля, изтрийте тези думи.');
define('CMTX_ERROR_MESSAGE_STRONG_SWEARING', 'Псуването не е позволено. Моля, премахнете псувните от Вашия коментар.');
define('CMTX_ERROR_MESSAGE_SPAMMING', 'Спаменето не е позволено. Моля, премахнете спама от Вашия коментар.');
define('CMTX_ERROR_MESSAGE_LONG_WORD', 'Въведеният коментар съдържа дълга дума. Моля да я съкратите или изтриете.');
define('CMTX_ERROR_MESSAGE_CAPITALS', 'Въведеният коментар съдържа твърде много главни букви. Моля, въведете по-малко главни букви.');
define('CMTX_ERROR_MESSAGE_LINK_IN_COMMENT', 'Въведеният коментар съдържа връзка. Моля да изтриете връзката.');
define('CMTX_ERROR_MESSAGE_REPEATS', 'Въведеният коментар съдържа повтарящи се символи. Моля да ги изтриете.');
define('CMTX_ERROR_MESSAGE_NO_ANSWER', 'Полето за въпроса не може да остане празно. Моля, въведете отговора.');
define('CMTX_ERROR_MESSAGE_WRONG_ANSWER', 'Отговорът на въпроса не е верен. Моля, опитайте отново.');
define('CMTX_ERROR_MESSAGE_NO_CAPTCHA', 'Полето за сигурност не може да бъде празно. Моля, въведете символите.');
define('CMTX_ERROR_MESSAGE_WRONG_CAPTCHA', 'Символите за изображението за сигурност не са верни. Моля, опитайте отново.');
define('CMTX_ERROR_MESSAGE_FLOOD_CONTROL_DELAY', 'Вашият последен коментар е изпратен скоро. Моля, изчакайте повече.');
define('CMTX_ERROR_MESSAGE_FLOOD_CONTROL_MAXIMUM', 'Изпратили сте скоро няколко коментара. Моля, изчакайте малко.');
define('CMTX_ERROR_MESSAGE_NO_REFERRER', 'Моля, направете Вашия браузър да изпраща информация за референта.');
define('CMTX_ERROR_MESSAGE_INCORRECT_REFERRER', 'Референтът предполага, че сте изпратили от друг сайт.');
define('CMTX_ERROR_MESSAGE_MAXIMUMS', 'Моля, направете Вашия браузър да се съобразява с максималната дължина на полето.');
define('CMTX_ERROR_MESSAGE_HONEYPOT', 'Скрито поле, използвано да хваща ботове, е било попълнено. Моля, оставете го празно.');
define('CMTX_ERROR_MESSAGE_MIN_TIME', 'Тази форма е изпратена твърде бързо. Моля, използвайте повече време.');
define('CMTX_ERROR_MESSAGE_MISSING_DATA', 'Липсва необходима информация. Моля, изпратете отново формата.');

/* Messages displayed to user when banned */
define('CMTX_BAN_MESSAGE_BANNED_NOW', '<p>Току-що бяхте баннати.</p><p>Това може да се дължи на различни причини, включително псуване, спамене, опит за хакване.</p><p>Ако смятате, че това е грешка, моля да се свържете с администратора на уеб сайта и да цитирате своя IP адрес.</p>');
define('CMTX_BAN_MESSAGE_BANNED_PREVIOUSLY', 'Извинете, били сте баннати преди.');

/* Ban reasons */
define('CMTX_BAN_REASON_NO_SECURITY_KEY', 'Няма ключ за сигурност.');
define('CMTX_BAN_REASON_INCORRECT_SECURITY_KEY', 'Неправилно въведен ключ за сигурност.');
define('CMTX_BAN_REASON_NO_RESUBMIT_KEY', 'Не е изпратен ключ.');
define('CMTX_BAN_REASON_INVALID_RESUBMIT_KEY', 'Невалиден ключ.');
define('CMTX_BAN_REASON_INJECTION', 'Опит за injection.');
define('CMTX_BAN_REASON_RESERVED_NAME', 'Въведено е запазено име.');
define('CMTX_BAN_REASON_BANNED_NAME', 'Въведено е баннато име.');
define('CMTX_BAN_REASON_DUMMY_NAME', 'Въведено е фалшиво име.');
define('CMTX_BAN_REASON_LINK_IN_NAME', 'Въведена е връзка в името.');
define('CMTX_BAN_REASON_RESERVED_EMAIL', 'Въведен е запазен имейл.');
define('CMTX_BAN_REASON_BANNED_EMAIL', 'Въведен е баннат имейл.');
define('CMTX_BAN_REASON_DUMMY_EMAIL', 'Въведен е фалшив имейл.');
define('CMTX_BAN_REASON_RESERVED_WEBSITE', 'Въведен е запазен адрес на уеб сайт.');
define('CMTX_BAN_REASON_BANNED_WEBSITE_IN_WEBSITE', 'Въведен е баннат уеб сайт в полето уеб сайт.');
define('CMTX_BAN_REASON_BANNED_WEBSITE_IN_COMMENT', 'Въведен е баннат уеб сайт в полето коментар.');
define('CMTX_BAN_REASON_DUMMY_WEBSITE', 'Въведен е фалшив уеб сайт.');
define('CMTX_BAN_REASON_RESERVED_TOWN', 'Въведен е запазен град.');
define('CMTX_BAN_REASON_BANNED_TOWN', 'Въведен е баннат град.');
define('CMTX_BAN_REASON_DUMMY_TOWN', 'Въведен е фалшив град.');
define('CMTX_BAN_REASON_LINK_IN_TOWN', 'Въведена е връзка в полето град.');
define('CMTX_BAN_REASON_MILD_SWEARING', 'Слаба псувня.');
define('CMTX_BAN_REASON_STRONG_SWEARING', 'Силна псувня.');
define('CMTX_BAN_REASON_SPAMMING', 'Спам.');
define('CMTX_BAN_REASON_CAPITALS', 'Твърде много главни букви.');
define('CMTX_BAN_REASON_LINK_IN_COMMENT', 'Въведена е връзка в коментара.');
define('CMTX_BAN_REASON_REPEATS', 'Въведени са повторения в коментара.');

/* Approval reasons */
define('CMTX_APPROVE_REASON_ALL', 'Одобри всички.');
define('CMTX_APPROVE_REASON_RESERVED_NAME', 'Въведено е запазено име.');
define('CMTX_APPROVE_REASON_BANNED_NAME', 'Въведено е баннато име.');
define('CMTX_APPROVE_REASON_DUMMY_NAME', 'Въведено е фалшиво име.');
define('CMTX_APPROVE_REASON_LINK_IN_NAME', 'Въвели сте връзка в полето за име.');
define('CMTX_APPROVE_REASON_RESERVED_EMAIL', 'Въведен е запазен имейл адрес.');
define('CMTX_APPROVE_REASON_BANNED_EMAIL', 'Въведен е баннат имейл адрес.');
define('CMTX_APPROVE_REASON_DUMMY_EMAIL', 'Въведен е фалшив имейл адрес.');
define('CMTX_APPROVE_REASON_WEBSITE_ENTERED', 'Уеб сайтът е въведен.');
define('CMTX_APPROVE_REASON_RESERVED_WEBSITE', 'В коментара е добавен запазен уеб сайт.');
define('CMTX_APPROVE_REASON_BANNED_WEBSITE_IN_WEBSITE', 'В полето за уеб сайт е добавен баннат уеб сайт.');
define('CMTX_APPROVE_REASON_BANNED_WEBSITE_IN_COMMENT', 'В коментара е добавен баннат уеб сайт.');
define('CMTX_APPROVE_REASON_DUMMY_WEBSITE', 'Въведен е фалшив адрес на уеб сайт.');
define('CMTX_APPROVE_REASON_RESERVED_TOWN', 'Въведен е запазен град.');
define('CMTX_APPROVE_REASON_BANNED_TOWN', 'Въведен е баннат град.');
define('CMTX_APPROVE_REASON_DUMMY_TOWN', 'Въведен е фалшив град.');
define('CMTX_APPROVE_REASON_LINK_IN_TOWN', 'Добавена е връзка в град.');
define('CMTX_APPROVE_REASON_LINK_IN_COMMENT', 'Връзката е въведена.');
define('CMTX_APPROVE_REASON_REPEATS', 'Повторенията са въведени.');
define('CMTX_APPROVE_REASON_IMAGE_ENTERED', 'Изображението е въведено.');
define('CMTX_APPROVE_REASON_VIDEO_ENTERED', 'Видеото е въведено.');
define('CMTX_APPROVE_REASON_MILD_SWEARING', 'Слаба псувня.');
define('CMTX_APPROVE_REASON_STRONG_SWEARING', 'Силна псувня.');
define('CMTX_APPROVE_REASON_SPAMMING', 'Спам.');
define('CMTX_APPROVE_REASON_CAPITALS', 'Твърде много главни букви.');
define('CMTX_APPROVE_REASON_AKISMET', 'Akismet.');

?>