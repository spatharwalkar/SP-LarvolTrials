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
define('CMTX_ERROR_NUMBER', 'Der opstod %d fejl ved behandling af din kommentar.');
define('CMTX_ERRORS_NUMBER', 'Der opstod %d fejl ved behandling af din kommentar.');
define('CMTX_ERROR_CORRECTION', 'Ret fejlen og prøv igen:');
define('CMTX_ERRORS_CORRECTION', 'Ret fejlene og prøv igen:');

/* Preview box */
define('CMTX_PREVIEW_TEXT', 'Smugkig, ikke indsendt!');

/* Approval box */
define('CMTX_APPROVAL_OPENING', 'Tak.');
define('CMTX_APPROVAL_TEXT', 'Din kommentar afventer godkendelse.');

/* Success box */
define('CMTX_SUCCESS_OPENING', 'Tak.');
define('CMTX_SUCCESS_TEXT', 'Din kommentar er tilføjet.');

/* Error messages */
define('CMTX_ERROR_MESSAGE_NO_NAME', 'Navnefeltet skal udfyldes, indtast dit navn.');
define('CMTX_ERROR_MESSAGE_ONE_NAME', 'Du kan kun angive et enkelt navn i navnefeltet.');
define('CMTX_ERROR_MESSAGE_INVALID_NAME', 'Navnet skal starte med et bogstav og kan evt. indeholde tegnene  - & . \'');
define('CMTX_ERROR_MESSAGE_RESERVED_NAME', 'Det indtastede navn benyttes allerede af en anden. Angiv et andet navn.');
define('CMTX_ERROR_MESSAGE_BANNED_NAME', 'Det indtastede navn er ikke tilladt at bruge her. Indtast et andet navn.');
define('CMTX_ERROR_MESSAGE_DUMMY_NAME', 'Du har indtastet et falsk navn. Indtast dit tigtige navn.');
define('CMTX_ERROR_MESSAGE_LINK_IN_NAME', 'Navnet indeholder et link. Indtast dit rigtige navn uden link.');
define('CMTX_ERROR_MESSAGE_NO_EMAIL', 'Mailfeltet skal udfyldes. Indtast din mailadresse.');
define('CMTX_ERROR_MESSAGE_INVALID_EMAIL', 'Du har indtastet en ugyldig mailadresse. Tjek den indtastede adresse for fejl.');
define('CMTX_ERROR_MESSAGE_RESERVED_EMAIL', 'Den indtastede mailadresse er reserveret. Indtast din mailadresse.');
define('CMTX_ERROR_MESSAGE_BANNED_EMAIL', 'Den indtastede mailadresse er ikke tilladt. Indtast en anden adresse.');
define('CMTX_ERROR_MESSAGE_DUMMY_EMAIL', 'Den indtastede mailadresse er ikke din egen. Indtast din mailadresse.');
define('CMTX_ERROR_MESSAGE_NO_WEBSITE', 'Hjemmesidefeltet skal udfyldes. Indtast adressen til din hjemmeside.');
define('CMTX_ERROR_MESSAGE_INVALID_WEBSITE', 'Den indtastede hjemmesideadresse ugyldig. Tjek den indtastede adresse for fejl.');
define('CMTX_ERROR_MESSAGE_RESERVED_WEBSITE', 'Den indtastede hjemmesideadresse er reserveret. Indtast din hjemmesideadresse.');
define('CMTX_ERROR_MESSAGE_BANNED_WEBSITE_IN_WEBSITE', 'Den indtastede hjemmesideadresse er ikke tilladt. Indtast en anden adresse.');
define('CMTX_ERROR_MESSAGE_BANNED_WEBSITE_IN_COMMENT', 'Den indtastede hjemmesideadresse i din kommentar er ikke tilladt her.');
define('CMTX_ERROR_MESSAGE_DUMMY_WEBSITE', 'Den indtastede hjemmesideadresse er ikke din egen. Indtast din hjemmesideadresse.');
define('CMTX_ERROR_MESSAGE_NO_TOWN', 'Byfeltet skal udfyldes. Indtast din by');
define('CMTX_ERROR_MESSAGE_INVALID_TOWN', 'Bynavnet skal starte med et bogstav og kan evt. indeholde tegnene  - & . \'');
define('CMTX_ERROR_MESSAGE_RESERVED_TOWN', 'Det indtastede bynavn kan ikke bruges. Indtast en anden by.');
define('CMTX_ERROR_MESSAGE_BANNED_TOWN', 'Det indtastede bynavn er ikke tilladt. Indtast en anden by.');
define('CMTX_ERROR_MESSAGE_DUMMY_TOWN', 'Det indtastede bynavn er ikke din by. Indtast din egen by.');
define('CMTX_ERROR_MESSAGE_LINK_IN_TOWN', 'Bynavnet indeholder et link. Indtast navnet uden link.');
define('CMTX_ERROR_MESSAGE_NO_COUNTRY', 'Du skal vælge land.');
define('CMTX_ERROR_MESSAGE_INVALID_COUNTRY', 'Det valgte land er ugyldigt. Kontakt administrator.');
define('CMTX_ERROR_MESSAGE_COUNTRY_SEARCH', 'Det valgte land kunne ikke findes. Prøv igen.');
define('CMTX_ERROR_MESSAGE_NO_RATING', 'Karakterfeltet var ikke valgt. Vælg din karakter.');
define('CMTX_ERROR_MESSAGE_INVALID_RATING', 'Den valgte karakter er ugyldig. Kontakt administrator.');
define('CMTX_ERROR_MESSAGE_INVALID_REPLY', 'Du har svaret på en ugyldig kommentar. Prøv igen.');
define('CMTX_ERROR_MESSAGE_NO_COMMENT', 'Kommentarfeltet skal udfyldes. Indtast en kommentar.');
define('CMTX_ERROR_MESSAGE_COMMENT_MIN', 'Din kommentar er for kort. Indtast en længere kommentar.');
define('CMTX_ERROR_MESSAGE_COMMENT_MAX', 'Din kommentar er for lang. Indtast en kortere kommentar.');
define('CMTX_ERROR_MESSAGE_COMMENT_MAX_LINES', 'Der er for mange linjer i din kommentar. Indtast færre linjer.');
define('CMTX_ERROR_MESSAGE_COMMENT_RESUBMIT', 'Du har allerede indsendt denne kommentar. Indsend en ny kommentar.');
define('CMTX_ERROR_MESSAGE_SMILIES_MAX', 'Din kommentar indeholder for mange smilies. Maksimum er %d smilies.');
define('CMTX_ERROR_MESSAGE_MILD_SWEARING', 'Din kommentar indeholder anstødelige ord. Slet de anstødelige ord.');
define('CMTX_ERROR_MESSAGE_STRONG_SWEARING', 'Det er ikke tilladt at bruge bandeord. Slet bandeordene fra din kommentar.');
define('CMTX_ERROR_MESSAGE_SPAMMING', 'Det er ikke tilladt at spamme. Slet spam fra din kommentar.');
define('CMTX_ERROR_MESSAGE_LONG_WORD', 'Din kommentar indeholder et ord, der er for langt. Forkort ordet.');
define('CMTX_ERROR_MESSAGE_CAPITALS', 'Din kommentar indeholder for mange store bogstaver. Anvend færre store bogstaver.');
define('CMTX_ERROR_MESSAGE_LINK_IN_COMMENT', 'Din kommentar indeholder et link. Fjern linket.');
define('CMTX_ERROR_MESSAGE_REPEATS', 'Din kommentar indeholder gentagne tegn. Fjern de gentagne tegn.');
define('CMTX_ERROR_MESSAGE_NO_ANSWER', 'Spamfælden skal udfyldes. Indtast et svar.');
define('CMTX_ERROR_MESSAGE_WRONG_ANSWER', 'Du indtastede et forkert svar i spamfælden. Prøv igen.');
define('CMTX_ERROR_MESSAGE_NO_CAPTCHA', 'Captcha-feltet skal udfyldes. Indtast captcha.');
define('CMTX_ERROR_MESSAGE_WRONG_CAPTCHA', 'Du indtastede en forkert captcha. Prøv igen.');
define('CMTX_ERROR_MESSAGE_FLOOD_CONTROL_DELAY', 'Der skal være længere pause mellem indsendelse af kommentarer. Vent et øjeblik og prøv så igen.');
define('CMTX_ERROR_MESSAGE_FLOOD_CONTROL_MAXIMUM', 'Du har indsendt flere kommentarer på kort tid. Vent  lidt, før du indsender en ny kommentar.');
define('CMTX_ERROR_MESSAGE_NO_REFERRER', 'Skift indstilling i din browser, så den medsender referrer-info.');
define('CMTX_ERROR_MESSAGE_INCORRECT_REFERRER', 'Den medsendte referrer antyder, at du har indsendt formularen fra en fremmed hjemmeside.');
define('CMTX_ERROR_MESSAGE_MAXIMUMS', 'Din browser respekterer ikke den maksimale feltlængde.');
define('CMTX_ERROR_MESSAGE_HONEYPOT', 'Et skjult felt blev udfyldt. Det skal være tomt.');
define('CMTX_ERROR_MESSAGE_MIN_TIME', 'Formularen blev sendt for hurtigt. Brug længere tid.');
define('CMTX_ERROR_MESSAGE_MISSING_DATA', 'Nogle forventede data manglede. Prøv at sende formularen igen.');

/* Messages displayed to user when banned */
define('CMTX_BAN_MESSAGE_BANNED_NOW', '<p>Du er blevet spærret.</p><p>Dette kan skyldes forskellige årsager, heriblandt bandeord, spamming og hacker-agtig opførsel</p><p>Hvis du mener, at dette er en fejl, så kontakt administrator med oplysning om din IP-adresse.</p>');
define('CMTX_BAN_MESSAGE_BANNED_PREVIOUSLY', 'Du er tidligere blevet spærret.');

/* Ban reasons */
define('CMTX_BAN_REASON_NO_SECURITY_KEY', 'Ingen sikkerhedsnøgle.');
define('CMTX_BAN_REASON_INCORRECT_SECURITY_KEY', 'Forkert sikkerhedsnøgle.');
define('CMTX_BAN_REASON_NO_RESUBMIT_KEY', 'Ingen gensend-tast.');
define('CMTX_BAN_REASON_INVALID_RESUBMIT_KEY', 'Ugyldig gensend-tast.');
define('CMTX_BAN_REASON_INJECTION', 'Forsøg på injection.');
define('CMTX_BAN_REASON_RESERVED_NAME', 'Indtastning af reserveret navn.');
define('CMTX_BAN_REASON_BANNED_NAME', 'Indtastning af spærret navn.');
define('CMTX_BAN_REASON_DUMMY_NAME', 'Indtastning af ugyldigt navn.');
define('CMTX_BAN_REASON_LINK_IN_NAME', 'Brug af link i navn.');
define('CMTX_BAN_REASON_RESERVED_EMAIL', 'Indtastning af reserveret mailadresse.');
define('CMTX_BAN_REASON_BANNED_EMAIL', 'Indtastning af spærret mailadresse.');
define('CMTX_BAN_REASON_DUMMY_EMAIL', 'Indtastning af ugyldig mailadresse.');
define('CMTX_BAN_REASON_RESERVED_WEBSITE', 'Indtastning af reserveret hjemmesideadresse.');
define('CMTX_BAN_REASON_BANNED_WEBSITE_IN_WEBSITE', 'Indtastning af spærret hjemmesideadresse.');
define('CMTX_BAN_REASON_BANNED_WEBSITE_IN_COMMENT', 'Indtastning af spærret hjemmesideadresse i en kommentar.');
define('CMTX_BAN_REASON_DUMMY_WEBSITE', 'Indtastning af ugyldig hjemmesideadresse.');
define('CMTX_BAN_REASON_RESERVED_TOWN', 'Indtastning af reserveret bynavn.');
define('CMTX_BAN_REASON_BANNED_TOWN', 'Indtastning af spærret bynavn.');
define('CMTX_BAN_REASON_DUMMY_TOWN', 'Indtastning af ugyldigt bynavn.');
define('CMTX_BAN_REASON_LINK_IN_TOWN', 'Brug af link i bynavnet.');
define('CMTX_BAN_REASON_MILD_SWEARING', 'Brug af lettere bandeord.');
define('CMTX_BAN_REASON_STRONG_SWEARING', 'Brug af stærke bandeord.');
define('CMTX_BAN_REASON_SPAMMING', 'Spamming.');
define('CMTX_BAN_REASON_CAPITALS', 'Brug af for mange store bogstaver.');
define('CMTX_BAN_REASON_LINK_IN_COMMENT', 'Indtastning af link i kommentar.');
define('CMTX_BAN_REASON_REPEATS', 'Indtastning af gentagelser i kommentar.');

/* Approval reasons */
define('CMTX_APPROVE_REASON_ALL', 'Godkend alle.');
define('CMTX_APPROVE_REASON_RESERVED_NAME', 'Brug af reserveret navn.');
define('CMTX_APPROVE_REASON_BANNED_NAME', 'Brug af spærret navn.');
define('CMTX_APPROVE_REASON_DUMMY_NAME', 'Brug af ugyldigt navn.');
define('CMTX_APPROVE_REASON_LINK_IN_NAME', 'Brug af link i navnet.');
define('CMTX_APPROVE_REASON_RESERVED_EMAIL', 'Brug af reserveret mailadresse.');
define('CMTX_APPROVE_REASON_BANNED_EMAIL', 'Brug af spærret mailadresse.');
define('CMTX_APPROVE_REASON_DUMMY_EMAIL', 'Brug af ugyldig mailadresse.');
define('CMTX_APPROVE_REASON_WEBSITE_ENTERED', 'Brug af hjemmesideadresse.');
define('CMTX_APPROVE_REASON_RESERVED_WEBSITE', 'Brug af reserveret hjemmesideadresse.');
define('CMTX_APPROVE_REASON_BANNED_WEBSITE_IN_WEBSITE', 'Brug af spærret hjemmesideadresse i hjemmesidefeltet.');
define('CMTX_APPROVE_REASON_BANNED_WEBSITE_IN_COMMENT', 'Brug af spærret hjemmeside i en kommentar.');
define('CMTX_APPROVE_REASON_DUMMY_WEBSITE', 'Brug af ugyldig hjemmesideadresse.');
define('CMTX_APPROVE_REASON_RESERVED_TOWN', 'Brug af reserveret bynavn.');
define('CMTX_APPROVE_REASON_BANNED_TOWN', 'Brug af spærret bynavn.');
define('CMTX_APPROVE_REASON_DUMMY_TOWN', 'Brug af ugyldigt bynavn.');
define('CMTX_APPROVE_REASON_LINK_IN_TOWN', 'Brug af link i bynavn.');
define('CMTX_APPROVE_REASON_LINK_IN_COMMENT', 'Brug af link.');
define('CMTX_APPROVE_REASON_REPEATS', 'Brug af gentagne bogstaver.');
define('CMTX_APPROVE_REASON_IMAGE_ENTERED', 'Indsættelse af billede.');
define('CMTX_APPROVE_REASON_VIDEO_ENTERED', 'Indsættelse af video.');
define('CMTX_APPROVE_REASON_MILD_SWEARING', 'Brug af lettere bandeord.');
define('CMTX_APPROVE_REASON_STRONG_SWEARING', 'Brug af stærke bandeord.');
define('CMTX_APPROVE_REASON_SPAMMING', 'Spam.');
define('CMTX_APPROVE_REASON_CAPITALS', 'Brug af for mange store bogstaver.');
define('CMTX_APPROVE_REASON_AKISMET', 'Akismet (spam).');

?>