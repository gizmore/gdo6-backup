<?php
namespace GDO\Backup\Method;

use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\File\GDT_File;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;
use GDO\File\GDO_File;
use GDO\File\FileUtil;
use GDO\Core\MethodAdmin;
use GDO\DB\Database;
use GDO\DB\Cache;
use GDO\Core\GDT_Hook;
use GDO\File\Filewalker;
use GDO\ZIP\Module_ZIP;
use GDO\Core\GDT_Response;
use GDO\Backup\Module_Backup;
use GDO\Form\GDT_DeleteButton;
use GDO\DB\GDT_String;
use GDO\Install\Config;
use GDO\Net\GDT_Hostname;
use GDO\Net\GDT_Url;
use GDO\DB\GDT_Checkbox;
use GDO\UI\GDT_Divider;
use GDO\Form\GDT_Hidden;

/**
 * Import a backup created by GDO Backup module.
 * @author gizmore
 * @version 6.10.1
 * @since 6.10.0
 */
final class ImportBackup extends MethodForm
{
	use MethodAdmin;
	
	public function getPermission() { return 'admin'; }
	
	public function isTransactional() { return false; }
	
	public function beforeExecute()
	{
	    $this->renderNavBar();
	    Admin::make()->renderBackupNavBar();
	}

	public function createForm(GDT_Form $form)
	{
	    $form->info(t('info_import_backup'));
		$form->addFields([
		    GDT_Divider::make()->label('div_after_import'),
		    GDT_Hostname::make('hostname')->initial(GDT_Url::host()),
		    GDT_String::make('cookie_domain')->initial(GDT_Url::host()),
		    GDT_Checkbox::make('enable_mail')->initial('0'),
		    GDT_Divider::make()->label('backup_file'),
		    GDT_File::make('backup_file')->maxsize(1024*1024*1024)->notNull(), # max 1GB
			GDT_AntiCSRF::make(),
		]);
		$btn = GDT_DeleteButton::make('submit')->label('submit')->confirmText('info_import_backup');
		$form->actions()->addField($btn);
	}
	
	public function formValidated(GDT_Form $form)
	{
		$file = $form->getFormValue('backup_file');
		$this->importBackup($file);
		return $this->renderPage();
	}
	
	public function extractDir()
	{
		return GDO_PATH.'temp/backup_import/';
	}
	
	public function importBackup(GDO_File $file)
	{
	    $form = $this->getForm();
	    
		$path = $this->extractDir();
		FileUtil::removeDir($path);
		$backup = "{$path}backup.zip";
		FileUtil::removeDir($path);
		FileUtil::createDir($path);
		copy($file->getPath(), $backup);
		
		# Unzip
		$zip = new \ZipArchive();
		if (!($code = $zip->open($backup)))
		{
			return $this->error('err_no_zip', [$code]);
		}
		$zip->extractTo($path);
		$zip->close();
		unlink($backup);
		$this->message('msg_extracted_backup');
		
		# Import files
		Filewalker::traverse($path, '/\\.zip$/', function($entry, $fullpath, $path) {
		    # Extract
			$path = $path . 'files/';
			FileUtil::createDir($path);
			$zip = new \ZipArchive();
			$zip->open($fullpath);
			$zip->extractTo($path);
			$zip->close();
			unlink($fullpath);
			# Delete old
			FileUtil::removeDir(GDO_PATH.'files');
			# Rename new
			rename($path, GDO_PATH.'files');
		}, false, false, $path);
		$this->message('msg_imported_backup_files_db');
		    
		# Import DB
		Filewalker::traverse($path, '/\\.gz$/', function($entry, $fullpath) {
		    
		    # gunzip
		    $gzip = Module_ZIP::instance()->cfgGZipPath();
		    $fullpath = FileUtil::path($fullpath);
		    $command = "$gzip -d $fullpath";
		    $output = null; $return_val = null;
		    exec($command, $output, $return_val);
		    if ($return_val !== 0)
		    {
		        return $this->error('err_gunzip_backup');
		    }
		    
		    # Import
		    $mysql = Module_Backup::instance()->cfgMysqlPath();
		    $user = GWF_DB_USER;
		    $pass = GWF_DB_PASS;
		    $db = GWF_DB_NAME;
		    Database::instance()->closeLink();
		    $newpath = substr($fullpath, 0, -3);
		    $command = "$mysql -u $user -p{$pass} $db < $newpath";
		    $output = null; $return_val = null;
		    exec($command, $output, $return_val);
		    if ($return_val !== 0)
		    {
		        return $this->error('err_source_mysql_backup');
		    }
// 		    Database::instance()->connect();
		});
        if (GDT_Response::globalError())
        {
            return null;
        }
        
        $this->message('msg_imported_mysql_db');
        
	    # Backup current config
        $path = $this->extractDir();
        rename(GDO_PATH.'protected/config.php', GDO_PATH.'protected/' . date('YmdHis') . '_config.php');
	    rename("{$path}config.php", GDO_PATH.'protected/config.php');
	    
	    $configFile = file(GDO_PATH.'protected/config.php');
	    $configFile = $this->replaceConfig($configFile, 'GWF_DB_HOST', GWF_DB_HOST);
	    $configFile = $this->replaceConfig($configFile, 'GWF_DB_NAME', GWF_DB_NAME);
	    $configFile = $this->replaceConfig($configFile, 'GWF_DB_USER', GWF_DB_USER);
	    $configFile = $this->replaceConfig($configFile, 'GWF_DB_PASS', GWF_DB_PASS);
	    $configFile = $this->replaceConfig($configFile, 'GWF_DOMAIN', $form->getFormVar('hostname'));
	    $configFile = $this->replaceConfig($configFile, 'GWF_SESS_DOMAIN', $form->getFormVar('cookie_domain'));
	    $configFile = $this->replaceConfig($configFile, 'GWF_ENABLE_EMAIL', $form->getFormVar('enable_email'));
	    file_put_contents(GDO_PATH.'protected/config.php', implode('', $configFile));
	    $this->message('msg_replaced_config');
	    
		# Flush Cache
		Cache::flush();
		
		# Hook
		GDT_Hook::callWithIPC("BackupImported");
		
		return $this->message('msg_backup_imported');
	}
	
	private function replaceConfig(array $lines, $key, $value)
	{
	    foreach ($lines as $n => $line)
	    {
	        if (strpos($lines[$n], $key))
	        {
	            $lines[$n] = sprintf("define('$key', '$value');\n");
	        }
	    }
	    return $lines;
	}
	
}
