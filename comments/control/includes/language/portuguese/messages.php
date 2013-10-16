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

define('CMTX_MSG_SAVED', 'Configurações salvas.');

define('CMTX_MSG_COMMENT_UPDATED', 'Comentário atualizado.');
define('CMTX_MSG_COMMENT_DELETED', 'Comentário excluído.');
define('CMTX_MSG_COMMENT_BULK_DELETED', '1 comment deleted.');
define('CMTX_MSG_COMMENTS_BULK_DELETED', '%d comments deleted.');
define('CMTX_MSG_COMMENT_APPROVED', 'Comment approved.');
define('CMTX_MSG_COMMENT_BULK_APPROVED', '1 comment approved.');
define('CMTX_MSG_COMMENTS_BULK_APPROVED', '%d comments approved.');
define('CMTX_MSG_COMMENT_ALREADY_APPROVED', 'Comment already approved.');
define('CMTX_MSG_COMMENT_BULK_ALREADY_APPROVED', '1 comment already approved.');
define('CMTX_MSG_COMMENTS_BULK_ALREADY_APPROVED', '%d comments already approved.');
define('CMTX_MSG_COMMENT_SENT', 'Comment sent.');
define('CMTX_MSG_COMMENT_BULK_SENT', '1 comment sent.');
define('CMTX_MSG_COMMENTS_BULK_SENT', '%d comments sent.');
define('CMTX_MSG_COMMENT_ALREADY_SENT', 'Comment already sent.');
define('CMTX_MSG_COMMENT_BULK_ALREADY_SENT', '1 comment already sent.');
define('CMTX_MSG_COMMENTS_BULK_ALREADY_SENT', '%d comments already sent.');
define('CMTX_MSG_SPAM_REMOVED', 'Spam removido.');

define('CMTX_MSG_PAGE_UPDATED', 'Página atualizada.');
define('CMTX_MSG_PAGE_DELETED', 'Página excluída.');
define('CMTX_MSG_PAGE_BULK_DELETED', '1 page deleted.');
define('CMTX_MSG_PAGES_BULK_DELETED', '%d pages deleted.');
define('CMTX_MSG_IDENTIFIER_EXISTS', 'Identifier já existe.');

define('CMTX_MSG_ADMIN_ADDED', 'Administrador adicionado.');
define('CMTX_MSG_ADMIN_UPDATED', 'Administrador atualizado.');
define('CMTX_MSG_ADMIN_DELETED', 'Administrador excluído.');
define('CMTX_MSG_ADMIN_BULK_DELETED', '1 administrator deleted.');
define('CMTX_MSG_ADMINS_BULK_DELETED', '%d administrators deleted.');
define('CMTX_MSG_ADMIN_EXISTS', 'Usuário já existe.');
define('CMTX_MSG_ADMIN_SUPER_DELETE', 'O super administrador não pode ser excluído.');
define('CMTX_MSG_ADMIN_BULK_SUPER_DELETE', '1 super administrator could not be deleted.');
define('CMTX_MSG_ADMINS_BULK_SUPER_DELETE', '&d super administrator could not be deleted.');
define('CMTX_MSG_ADMIN_SUPER_DISABLE', 'The super administrator can not be disabled.');
define('CMTX_MSG_ADMIN_SUPER_RESTRICT', 'The super administrator can not be restricted.');
define('CMTX_MSG_ADMIN_SUPER_ONLY', 'Desculpe, apenas o super administrador pode acessar esta página.');

define('CMTX_MSG_BAN_ADDED', 'Banimento adicionado.');
define('CMTX_MSG_BAN_UPDATED', 'Ban updated.');
define('CMTX_MSG_BAN_DELETED', 'Banimento excluído.');
define('CMTX_MSG_BAN_BULK_DELETED', '1 ban deleted.');
define('CMTX_MSG_BANS_BULK_DELETED', '%d bans deleted.');

define('CMTX_MSG_SUB_ADDED', 'Inscrição adicionada.');
define('CMTX_MSG_SUB_UPDATED', 'Inscrição atualizada.');
define('CMTX_MSG_SUB_DELETED', 'Inscrição excluída.');
define('CMTX_MSG_SUB_BULK_DELETED', '1 subscriber deleted.');
define('CMTX_MSG_SUBS_BULK_DELETED', '%d subscribers deleted.');

define('CMTX_MSG_QUESTION_ADDED', 'Pergunta adicionada.');
define('CMTX_MSG_QUESTION_UPDATED', 'Pergunta atualizada.');
define('CMTX_MSG_QUESTION_DELETED', 'Pergunta excluída.');
define('CMTX_MSG_QUESTION_BULK_DELETED', '1 question deleted.');
define('CMTX_MSG_QUESTIONS_BULK_DELETED', '%d questions deleted.');

define('CMTX_MSG_IP_ADDRESS_UPDATED', 'Endereço IP Atualizado.');

define('CMTX_MSG_AKISMET_UNABLE', '<p>The fsockopen() function on your server appears to be disabled.</p><p>This feature requires it. Please contact your host to enable it.</p>');

define('CMTX_MSG_EMAIL_SENT', 'Email sent.');

define('CMTX_MSG_LOG_RESET', 'Resetar Log de erro.');

define('CMTX_MSG_LIST_UPDATED', 'Lista atualizada.');

define('CMTX_MSG_RECAPTCHA_UNABLE', '<p>The fsockopen() function on your server appears to be disabled.</p><p>This feature requires it. Please contact your host to enable it.</p>');

define('CMTX_MSG_SECURIMAGE_UNABLE', '<p>Either the \'<b>GD</b>\' extension and/or \'<b>FreeType</b>\' appear to be disabled.</p><p>This feature requires it. Please contact your host to enable them.</p>');

define('CMTX_MSG_BACKUP_CREATED', 'Backup criado.');
define('CMTX_MSG_BACKUP_DELETED', 'Backup atualizado.');
define('CMTX_MSG_BACKUP_BULK_DELETED', '1 backup deleted.');
define('CMTX_MSG_BACKUPS_BULK_DELETED', '%d backups deleted.');
define('CMTX_MSG_BACKUP_NOT_FOUND', 'Backup not found.');
define('CMTX_MSG_BACKUP_BULK_NOT_FOUND', '1 backup not found.');
define('CMTX_MSG_BACKUPS_BULK_NOT_FOUND', '%d backups not found.');
define('CMTX_MSG_BACKUP_UNABLE', '<p>The system() function on your server appears to be disabled.</p><p>This feature requires it. Please contact your host to enable it.</p>');

define('CMTX_MSG_OPTIMIZED', 'Tabelas otimizadas.');

define('CMTX_MSG_NOTICE_MANAGE_COMMENTS', 'Para melhorar o desempenho, apenas os últimos 50 comentários são mostrados. Vá para Settings -> System de alterar esta.');
define('CMTX_MSG_NOTICE_LAYOUT_FORM_QUESTIONS', 'Answers are case-insensitive. Separate multiple answers with the | character.');
define('CMTX_MSG_NOTICE_SETTINGS_ADMIN_DETECTION', 'Posting as the admin has <a href="http://www.commentics.org/wiki/doku.php?id=admin:settings_admin_detection" target="_blank">differences</a>.');
define('CMTX_MSG_NOTICE_SETTINGS_EMAIL_SENDER', 'The "<b>From Email</b>" should be a registered address.');

define('CMTX_MSG_RECORD_MISSING', 'Not found.');

define('CMTX_MSG_DEMO', 'Esta função foi desabilitada na demo.');

?>