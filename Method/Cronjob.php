<?php
namespace GDO\Backup\Method;

use GDO\Backup\Module_Backup;
use GDO\File\FileUtil;
use GDO\Mail\Mail;
use GDO\User\GDO_User;
use GDO\Cronjob\MethodCronjob;
use GDO\ZIP\Module_ZIP;

/**
 * Create a full site data backup.
 * Backup can be sent via mail, daily.
 * 
 * Requires the following executables in PATH: zip, gzip, mysqldump.
 * Detect all 3 biniaries in the config (Windows)
 * 
 * Backs up:
 * - Files in files/ folder used by GDO_File
 * - Config in protected/config.php
 * - Database via mysqldump binary
 * 
 * @author gizmore
 * @version 6.10
 * @since 6.03
 * @see Module_ZIP
 */
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
	    # Clean backup in temp
	    FileUtil::removeDir($this->tempDir());
	    FileUtil::createDir($this->tempDir());
	    
	    # Backup routines
	    if ($this->doConfigDump() &&
	        $this->doMysqlDump() &&
	        $this->doFilesDump())
	    {
	        # Create final archive
	        $this->createArchive();
	    }
	    
	    # Cleanup
	    FileUtil::removeDir($this->tempDir());
	}
	
	private function doConfigDump()
	{
	    $currPath = GDO_PATH . 'protected/config.php';
	    $destPath = $this->tempDir() . '/config.php';
	    copy($currPath, $destPath);
	    return true;
	}
	
	private function doMysqlDump()
	{
	    $sitename = GWF_SITENAME;
	    $today = date('Ymd');
	    $path = $this->tempDir() . "$sitename.$today.sql";
	    $path = FileUtil::path($path);
	    
	    $username = GWF_DB_USER;
	    $password = GWF_DB_PASS;
	    $database = GWF_DB_NAME;
	    
	    $mysqldump = Module_Backup::instance()->cfgMysqldumpPath();
	    
	    $command = "$mysqldump --add-drop-table --add-drop-database --skip-lock-tables --databases $database -u $username -p$password > $path";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not create sql backup: {$return_val}");
	        $this->logError(implode("\n", $output));
	        return false;
	    }
	    
	    $gzip = Module_ZIP::instance()->cfgGZipPath();
	    
	    $command = "$gzip $path";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not gzip sql backup");
	        return false;
	    }
	    
// 	    if (!@unlink($path))
// 	    {
// 	        $this->logError("Could not cleanup sql backup");
// 	        return false;
// 	    }
	    
	    return true;
	}

	private function doFilesDump()
	{
	    $src = GDO_PATH . 'files';
	    $src = FileUtil::path($src);
	    $sitename = GWF_SITENAME;
	    $today = date('Ymd');
	    $path = $this->tempDir() . "$sitename.$today.files.zip";
	    $path = FileUtil::path($path);
	    $zip = Module_ZIP::instance()->cfgZipPath();
	    $command = "$zip -j -r9 $path $src";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not create files backup");
	        return false;
	    }
	    return true;
	}

	#####################
	### Final archive ###
	#####################
	private function createArchive()
	{
	    $src = $this->tempDir();
	    $src = FileUtil::path($src);
	    $backupPath = GDO_PATH . "protected/backup/";
	    $sitename = GWF_SITENAME;
	    $today = date('Ymd');
	    FileUtil::createDir($backupPath);
	    $path = "$backupPath$sitename.$today.zip";
	    $path = FileUtil::path($path);
	    $zip = Module_ZIP::instance()->cfgZipPath();
	    $command = "$zip -j -r0 $path $src";
	    $output = null; $return_val = null;
	    exec($command, $output, $return_val);
	    if ($return_val !== 0)
	    {
	        $this->logError("Could not create final archive");
	        return false;
	    }
	    
	    # If we want backups sent via mail...
	    if (Module_Backup::instance()->cfgSendMail())
	    {
	    	# Send via mail
	    	$this->sendBackupPerMail($path);
	    }
	}
	
	############
	### Mail ###
	############
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
