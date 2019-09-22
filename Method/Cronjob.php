<?php
namespace GDO\Backup\Method;

use GDO\Backup\Module_Backup;
use GDO\File\FileUtil;
use GDO\Mail\Mail;
use GDO\User\GDO_User;
use GDO\Cronjob\MethodCronjob;

final class Cronjob extends MethodCronjob
{
	public function run()
	{
		$module = Module_Backup::instance();
		$last = $module->cfgLastDate();
		$curr = date('Ymd');
		if ($last !== $curr)
		{
			$this->logNotice("Doing backup for $curr");
			$this->doBackup();
			$module->saveConfigVar('backup_lastdate', $curr);
		}
	}
	
	private function tempDir()
	{
	    return GDO_PATH . 'temp/backup/';
	}
	
	public function doBackup()
	{
	    FileUtil::removeDir($this->tempDir());
	    FileUtil::createDir($this->tempDir());
	    $this->doMysqlDump();
	    $this->doFilesDump();
	    $this->createArchive();
	    FileUtil::removeDir($this->tempDir());
	}
	
	private function doMysqlDump()
	{
	    $username = GWF_DB_USER;
	    $password = GWF_DB_PASS;
	    $database = GWF_DB_NAME;
	    $sitename = sitename();
	    $today = date('Ymd');
	    $path = $this->tempDir() . "$sitename.$today.sql.gz";
	    $command = "mysqldump -u $username -p$password $database | gzip > $path";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not create sql backup");
	    }
	}

	private function doFilesDump()
	{
	    $src = GDO_PATH . 'files';
	    $sitename = sitename();
	    $today = date('Ymd');
	    $path = $this->tempDir() . "$sitename.$today.files.zip";
	    $command = "zip -j -r9 $path $src";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not create files backup");
	    }
	}

	private function createArchive()
	{
	    $src = $this->tempDir();
	    $backupPath = GDO_PATH . "protected/backup/";
	    $sitename = sitename();
	    $today = date('Ymd');
	    FileUtil::createDir($backupPath);
	    $path = "$backupPath$sitename.$today.zip";
	    $command = "zip -j -r0 $path $src";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not create archive");
	    }
	    
	    # If we want backups sent via mail...
	    if (Module_Backup::instance()->cfgSendMail())
	    {
	    	# Send via mail
	    	$this->sendBackupPerMail($path);
	    }
	}
	
	private function sendBackupPerMail($path)
	{
		$filename = sitename().'.'.date('Ymd').'.zip';
		foreach (GDO_User::admins() as $admin)
		{
			$mail = Mail::botMail();
			$mail->setSubject(tusr($admin, 'mail_subj_backup', [sitename()]));
			$args = [$admin->displayNameLabel(), sitename()];
			$mail->setBody(tusr($admin, 'mail_body_backup', $args));
			$mail->addAttachmentFile($filename, $path);
			$mail->sendToUser($admin);
		}
	}
}
