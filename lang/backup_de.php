<?php
return array(
### Config
'cfg_backup_lastdate' => 'Datum des letzten Backups',
'cfg_backup_send_mail' => 'Backups per Email senden?',
'cfg_tt_backup_send_mail' => 'Das Backup ist vielleicht zu groß :(',
### Links
'link_backup_create' => 'Backup erstellen',
'link_backup_import' => 'Backup einspielen',
'link_backup_downloads' => 'Backup herunterladen',
### Create	
'ft_backup_createbackup' => 'Ein Backup erstellen',
### Import
'ft_backup_importbackup' => 'Ein Backup einspielen',
### Download
'btn_download' => 'Download',
### Mail
'mail_subj_backup' => '%s: Backup',
'mail_body_backup' => '
Hallo %s,

Das neueste Backup ist angehängt.

Viele Grüße,
Das %s System.',
# mysqldump
'cfg_mysql_path' => 'Pfad zum MySQL client',
'cfg_mysqldump_path' => 'Pfad zu mysqldump',
'msg_backup_created' => 'Ein neues Backup wurde erstellt.',
'link_backup_detect_mysqldump' => 'Finde Mysqldump',
'ft_backup_detectmysqldump' => 'Finde Mysqldump',
'err_gunzip_backup' => 'Die Datenbank konnte nicht entpackt werden.',
'msg_backup_imported' => 'Das Backup wurde erfolgreich importiert.',
'msg_extracted_backup' => 'Das Backup wurde entpackt.',
'msg_imported_backup_files_db' => 'Der files/ Ordner wurde wiederhergestellt.',
'msg_imported_mysql_db' => 'Die Datenbank wurde erfolgreich importiert.',
'msg_replaced_config' => 'Die config.php wurde ersetzt.',
'err_source_mysql_backup' => 'Die Datenbank-Datei scheint beschädigt.',
'msg_detected_mysql' => 'mysql wurde gefunden.',
'msg_detected_mysqldump' => 'mysqldump wurde gefunden.',
'list_backup_listbackups' => '%s Backups gefunden',
'info_import_backup' => 'Ein Import zerstört die aktuelle Seite. Sind Sie sicher?',
);
