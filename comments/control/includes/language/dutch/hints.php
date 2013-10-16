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

define('CMTX_HINT_SEND', 'Stuur een e-mailbericht van deze opmerking aan de abonnees.');
define('CMTX_HINT_VERIFY', 'Verify this comment to reset and stop any further reports. This will unflag a flagged comment.');
define('CMTX_HINT_STICKY', 'Plak dit commentaar aan de top.');
define('CMTX_HINT_LOCKED', 'Lock reacties voor deze reactie.');

define('CMTX_HINT_COMMENTS_ORDER', 'De standaard volgorde van weergave van de opmerkingen.');
define('CMTX_HINT_DISPLAY_COMMENT_COUNT', 'Geef het commentaar telling na de titel.');
define('CMTX_HINT_DISPLAY_SAYS', 'Toont het woord <i>zegt</i> achter de naam van de gebruiker.');
define('CMTX_HINT_JS_VOTE_OK', 'Toon een JavaScript berichten box als de Like/Dislike stem okay is.');

define('CMTX_HINT_PAGINATION_ENABLED', 'Moeten de berichten over meerdere paginas worden verdeeld?');
define('CMTX_HINT_PAGINATION_TOP', 'Toon de paginatie links boven het berichten gebied.');
define('CMTX_HINT_PAGINATION_BOTTOM', 'Toon de paginatie links onder het berichten gebied.');
define('CMTX_HINT_PAGINATION_PER_PAGE', 'Hoeveel berichten wilt u per pagina tonen.');
define('CMTX_HINT_PAGINATION_RANGE', 'Het aantal links om aan iedere zijde van de huidige pagina te tonen.');

define('CMTX_HINT_SORT_BY_ENABLED', 'Geef het Sorteren op links.');
define('CMTX_HINT_SORT_BY_1', 'Om dit te laten werken op de datum moet ook worden ingeschakeld.');
define('CMTX_HINT_SORT_BY_2', 'Om dit te laten werken op de datum moet ook worden ingeschakeld.');
define('CMTX_HINT_SORT_BY_3', 'Om dit te laten werken de Like-functie moet ook worden ingeschakeld.');
define('CMTX_HINT_SORT_BY_4', 'Om dit te laten werken de Dislike functie moet worden ingeschakeld.');
define('CMTX_HINT_SORT_BY_5', 'Om dit te laten werken de rating functie moet worden ingeschakeld.');
define('CMTX_HINT_SORT_BY_6', 'Om dit te laten werken de rating functie moet worden ingeschakeld.');

define('CMTX_HINT_SHOW_REPLY', 'Toon de reply/reageer link binnenin de berichten box.');
define('CMTX_HINT_REPLY_DEPTH', 'Hoeveel levels van reacties wilt u toestaan voordat de reply/reageer link wordt uitgeschakeld. Voer een aantal in van 1 of hoger. Zorg ervoor dat de breedte van de reacties past binnenin uw eigen pagina/website.');
define('CMTX_HINT_REPLY_ARROW', 'Wel of geen pijl naast de reacties.');
define('CMTX_HINT_SCROLL_REPLY', 'Scroll langzaam naar beneden naar het formulier na het klikken op de reply/reageer link.');

define('CMTX_HINT_SOCIAL_ENABLED', 'Toon de social sharing links.');

define('CMTX_HINT_GRAVATAR_DEFAULT', 'mm is de mysterieuze man. Kies standaard voor de eigenlijke standaard.');
define('CMTX_HINT_GRAVATAR_CUSTOM', 'Enter the URL of the image that you want as the default.');
define('CMTX_HINT_GRAVATAR_RATING', 'Het publiek type. G is geschikt voor alle doelgroepen.');

define('CMTX_HINT_DISPLAY_JS_MSG', 'Toon een waarschuwings bericht als JavaScript is uitgeschakeld in de web browser van de gebruiker.');
define('CMTX_HINT_DISPLAY_AST_SYMBOL', 'Toon een asterisk symbol (*) naast de verplichte velden.');
define('CMTX_HINT_DISPLAY_AST_MSG', 'Toon een asterisk bericht:<br/>* verplichte informatie');
define('CMTX_HINT_DISPLAY_EMAIL_NOTE', 'Toon het email bericht:<br/> (wordt niet getoond)');
define('CMTX_HINT_HIDE_FORM', 'Hide the form and show a link to display it.');
define('CMTX_HINT_FORM_COOKIE', 'Store a form cookie with user details. This setting only works if you do not give the user the option to choose.');
define('CMTX_HINT_FORM_COOKIE_DAYS', 'The amount of days before the form cookie expires.');
define('CMTX_HINT_REPEAT_RATINGS', 'Wat te doen met het waarderingsveld als de gebruker al heeft gestemd.');
define('CMTX_HINT_AGREE_TO_PREVIEW', 'Moet de gebruiker akkoord gaan met de privacy policy en de algemene voorwarden voordat hij een previeuw krijgt van het bericht?');

define('CMTX_HINT_SECURIMAGE_LENGTH', 'The number of characters to show inside the captcha image.');
define('CMTX_HINT_SECURIMAGE_PERTURBATION', 'The character distortion. 1.0 is high distortion. The higher the number, the more distortion.');
define('CMTX_HINT_SECURIMAGE_LINES', 'How many lines to draw over the image. The more, the harder.');
define('CMTX_HINT_SECURIMAGE_NOISE', 'The level of noise (random dots) to place on the image, 0-10.');

define('CMTX_HINT_APPROVE_COMMENTS', 'If this is selected, the admin must always approve the comment.');
define('CMTX_HINT_APPROVE_NOTIFICATIONS', 'If this is selected, the admin must always approve the notification email which is sent to subscribers.');
define('CMTX_HINT_TRUST_USERS', 'If this is selected, the admin does not need to approve the comment if the user has previously posted an approved comment.');

define('CMTX_HINT_FLAG_MAX_PER_USER', 'The maximum number of reports a user can submit.');
define('CMTX_HINT_FLAG_MIN_PER_COM', 'The minimum number of reports before a comment is flagged.');
define('CMTX_HINT_FLAG_DISAPPROVE', 'Moet een bericht worden gerapporteerd voordat het wordt afgekeurd?');

define('CMTX_HINT_FLOODING_DELAY', 'Whether the user should have to wait before posting again.');
define('CMTX_HINT_FLOODING_MAXIMUM', 'Whether to limit the amount of comments the user can post within a defined period.');
define('CMTX_HINT_FLOODING_ALL_PAGES', 'Whether to apply to only the same page or all the pages.');

define('CMTX_HINT_ONE_NAME', 'Wijs een bericht af wanneer meer dan 1 naam wordt ingevoerd.');
define('CMTX_HINT_FIX_NAME', 'Hoofdletter voor de eerste letter.<br />Overige letters worden kleine letters.');

define('CMTX_HINT_FIX_TOWN', 'Hoofdletter voor de eerste letter.<br />Overige letters worden kleine letters.');

define('CMTX_HINT_APPROVE_WEBSITE', 'Handmatig bericht goedkeuren als een gebruiker een website adres invult in het website veld.');
define('CMTX_HINT_PING', 'Controleerd of de website bestaat. je server moet in staat zijn dit te kunnen doen.');
define('CMTX_HINT_NEW_WIN', 'Open link(s) in nieuw scherm (tab).');
define('CMTX_HINT_NO_FOLLOW', 'Voeg <i>rel=nofollow</i> tag toe aan links zodat search engines ze niet kunnen volgen. Goed voor SEO.');
define('CMTX_HINT_CONVERT_LINKS', 'Zorg ervoor dat ingevoerde links klikbaar zijn.');
define('CMTX_HINT_CONVERT_EMAILS', 'Zorg ervoor dat ingevoerde email adressen klikbaar zijn.');
define('CMTX_HINT_LINE_BREAKS', 'Respecteer dat de gebruiker de enter toets gebruikt on een nieuwe regel te starten.');
define('CMTX_HINT_MASK', 'Vervang vloekende woorden met dit stuk tekst.');
define('CMTX_HINT_MAX_CAPITALS', 'Detecteer het dat een gebruiker een grote hoeveelheid hoofdletters invoerd.');
define('CMTX_HINT_DETECT_REPEATS', 'Detecteer het dat een gebruiker meer dan 3 dezelfde letters achter elkaar invoerd.');

define('CMTX_HINT_CHECK_REFERRER', 'Of het nu om te controleren of het formulier is verzonden vanaf uw eigen website.');
define('CMTX_HINT_CHECK_DB_FILE', 'Of het nu om te controleren of de database gegevens bestand, includes/db/details.php, is alleen-lezen.');
define('CMTX_HINT_CHECK_HONEYPOT', 'Whether to add an input, hidden by CSS, to be left empty. Bots tend to fill in every form field.');
define('CMTX_HINT_CHECK_TIME', 'Whether to check that it took less than 5 seconds to submit the form. Bots often submit forms instantly without waiting.');
define('CMTX_HINT_BAN_COOKIE', 'Het aantal dagen voordat het verbod cookie verloopt.');

define('CMTX_HINT_ADMIN_FOLDER', 'De naam van je hernoemde admin folder.');
define('CMTX_HINT_TIME_ZONE', 'De tijdzone van jouw locatie/land.');
define('CMTX_HINT_COMMENTS_URL', 'De absolute URL van jouw berichtenscript folder.');
define('CMTX_HINT_MYSQL_DUMP', 'Als je problemen hebt met de database backup tool moet je het server path opgeven naar je MySQLDump file.');
define('CMTX_HINT_WYSIWYG', 'Moet de WYSIWYG (What You See Is What You Get) HTML editor ingeschakeldc worden voor het bewerken van de berichten pagina?');
define('CMTX_HINT_LIMIT_COMMENTS', 'Om de prestaties te verbeteren, blijkt alleen dit bedrag van de resultaten in Manage -> Comments.');
define('CMTX_HINT_ADMIN_COOKIE_DAYS', 'The amount of days before the admin detection cookie expires.');

define('CMTX_HINT_VISITOR_ENABLED', 'Of bezoeker activiteit moet worden gevolgd en geregistreerd.');
define('CMTX_HINT_VISITOR_TIMEOUT', 'De tijd alvorens een online kijker wordt als actief en dus niet langer die de pagina.');
define('CMTX_HINT_VISITOR_REFRESH', 'Of het nu om automatisch het Extra Reports -> Viewers pagina.');
define('CMTX_HINT_VISITOR_INTERVAL', 'Hoe vaak naar het Tools Reports -> Viewers pagina.');

?>