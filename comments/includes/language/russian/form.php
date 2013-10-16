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
define('CMTX_FORM_HEADING', 'Написать отзыв');

/* Form disabled messages */
define('CMTX_ALL_FORMS_DISABLED', 'Добавление коментариев запрещено.');
define('CMTX_THIS_FORM_DISABLED', 'Добавление коментариев к данной странице запрещено.');

/* Open form link */
define('CMTX_OPEN_FORM', 'Открыть форму');

/* JavaScript disabled message */
define('CMTX_JAVASCRIPT_DISABLED', 'JavaScript должен быть включен.');

/* Reply */
define('CMTX_REPLY_MESSAGE', 'Вы отвечаете на');
define('CMTX_REPLY_CANCEL', '[отмена]');
define('CMTX_REPLY_NOBODY', 'Вы не ответили никому.');

/* Required */
define('CMTX_REQUIRED_SYMBOL', '*');
define('CMTX_REQUIRED_SYMBOL_MESSAGE', CMTX_REQUIRED_SYMBOL . ' Обязательные поля');

/* Field labels */
define('CMTX_LABEL_NAME', 'Имя:');
define('CMTX_LABEL_EMAIL', 'Email:');
define('CMTX_LABEL_WEBSITE', 'Сайт:');
define('CMTX_LABEL_TOWN', 'Откуда Вы:');
define('CMTX_LABEL_COUNTRY', 'Страна:');
define('CMTX_LABEL_RATING', 'Оценка:');
define('CMTX_LABEL_COMMENT', 'Отзыв:');
define('CMTX_LABEL_QUESTION', 'Вопрос:');
define('CMTX_LABEL_CAPTCHA', 'Kапчи:');

/* Field titles */
define('CMTX_TITLE_NAME', 'Введите имя');
define('CMTX_TITLE_EMAIL', 'Введите email адрес');
define('CMTX_TITLE_WEBSITE', 'Введите website адрес');
define('CMTX_TITLE_TOWN', 'Введите город');
define('CMTX_TITLE_COMMENT', 'Введите отзыв');
define('CMTX_TITLE_QUESTION', 'Введите ответ на вопрос');
define('CMTX_TITLE_NOTIFY', 'Получать уведомления на почту');
define('CMTX_TITLE_REMEMBER', 'Запомнить входа форме');
define('CMTX_TITLE_PRIVACY', 'Соглашаюсь с условиями');
define('CMTX_TITLE_TERMS', 'Согласиться с условиями');
define('CMTX_TITLE_SUBMIT', 'Добавить отзыв');
define('CMTX_TITLE_PREVIEW', 'Предпросмотр');

/* Note displayed after email field */
define('CMTX_NOTE_EMAIL', '(не для публикации)');

/* Text displayed for JavaScript BB Code prompts */
define('CMTX_PROMPT_ENTER_BULLET', 'Введите элемент списка. Нажмите отменить или оставить пустым, чтобы положить конец списка.');
define('CMTX_PROMPT_ENTER_ANOTHER_BULLET', 'Введите другого элемента списка. Нажмите отменить или оставить пустым, чтобы положить конец списка.');
define('CMTX_PROMPT_ENTER_NUMERIC', 'Введите элемент списка. Нажмите отменить или оставить пустым, чтобы положить конец списка.');
define('CMTX_PROMPT_ENTER_ANOTHER_NUMERIC', 'Введите другого элемента списка. Нажмите отменить или оставить пустым, чтобы положить конец списка.');
define('CMTX_PROMPT_ENTER_LINK', 'Пожалуйста, введите ссылку на сайт');
define('CMTX_PROMPT_ENTER_LINK_TITLE', 'По желанию, вы можете ввести название для ссылки');
define('CMTX_PROMPT_ENTER_EMAIL', 'Пожалуйста, введите адрес электронной почты');
define('CMTX_PROMPT_ENTER_EMAIL_TITLE', 'По желанию, вы можете ввести название для электронной почты');
define('CMTX_PROMPT_ENTER_IMAGE', 'Пожалуйста, введите ссылку на изображение');
define('CMTX_PROMPT_ENTER_VIDEO', 'Пожалуйста, введите ссылку на видео. Поддерживаемые сайты включают в себя:\nYouTube, Vimeo, Metacafe и Dailymotion.');

/* Text displayed for invalid BB Code entries */
define('CMTX_BB_INVALID_LINK', '<i>(недействительный-ссылка)</i>');
define('CMTX_BB_INVALID_EMAIL', '<i>(недействительный-email)</i>');
define('CMTX_BB_INVALID_IMAGE', '<i>(недействительный-изображение)</i>');
define('CMTX_BB_INVALID_VIDEO', '<i>(недействительный-видео)</i>');

/* Text displayed before/after the counter */
define('CMTX_TEXT_COUNTER', '%s');

/* Text displayed before question field */
define('CMTX_TEXT_QUESTION', 'Введите ответ:');

/* Text displayed for Securimage captcha */
define('CMTX_TEXT_SECURIMAGE', 'Enter code:');
define('CMTX_TITLE_SECURIMAGE', 'Enter code from image');
define('CMTX_TITLE_SECURIMAGE_AUDIO', 'Audio');
define('CMTX_TITLE_SECURIMAGE_REFRESH', 'Refresh');

/* Text displayed if ReCaptcha key missing */
define('CMTX_RECAPTCHA_NO_KEY', 'API key(s) missing in ReCaptcha admin page');

/* Text displayed after notify checkbox */
define('CMTX_TEXT_NOTIFY', 'Уведомлеть о новых коментариях по почте.');

/* Text displayed after remember checkbox */
define('CMTX_TEXT_REMEMBER', 'Запомнить данные формы на этом компьютере.');

/* Text displayed after privacy checkbox */
define('CMTX_TEXT_PRIVACY', 'Я прочитал и согласен с  <a href="' . cmtx_comments_folder() . 'agreement/russian/privacy_policy.html" title="политикой конфеденциальности" target="_blank" rel="nofollow">политикой конфеденциальности</a>.');

/* Text displayed after terms checkbox */
define('CMTX_TEXT_TERMS', 'Я прочитал и согласен с  <a href="' . cmtx_comments_folder() . 'agreement/russian/terms_and_conditions.html" title="View terms and conditions" target="_blank" rel="nofollow">пользовательским соглашением</a>.');

/* Text for form submit button */
define('CMTX_SUBMIT_BUTTON', 'Добавить комментарий');

/* Text for form preview button */
define('CMTX_PREVIEW_BUTTON', 'Предпросмотр');

/* Text for form buttons when processing */
define('CMTX_PROCESSING_BUTTON', 'Подождите..');

/* Text for 'powered by' statement */
define('CMTX_POWERED_BY', 'Работает на');

?>