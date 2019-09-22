<?php
namespace GDO\Backup\Method;

use GDO\Core\GDT_Response;
use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\File\GDT_File;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;
use GDO\File\GDO_File;
use GDO\File\FileUtil;
use GDO\Core\MethodAdmin;
use GDO\UI\GDT_Paragraph;
use GDO\DB\Database;
use GDO\DB\Cache;
use GDO\Core\GDT_Hook;
use GDO\File\Filewalker;

/**
 * Import a backup.
 * @author gizmore
 * @since 6.10
 * @version 6.10
 */
final class ImportBackup extends MethodForm
{
	use MethodAdmin;
	
	public function getPermission() { return 'admin'; }
	
	public function renderPage()
	{
		return GDT_Response::makeWith(
			$this->renderNavBar(),
			Admin::make()->renderBackupNavBar(),
			parent::renderPage()
		);
	}
	
	public function createForm(GDT_Form $form)
	{
		$form->addFields(array(
			GDT_File::make('backup_file')->notNull()->maxsize(1000000000),
			GDT_AntiCSRF::make(),
			GDT_Submit::make(),
		));
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
		$path = $this->extractDir();
		$backup = "$path/backup.zip";
		FileUtil::removeDir($path);
		FileUtil::createDir($path);
		rename($file->getPath(), $backup);

		# Unzip
		$zip = new \ZipArchive();
		if (true !== ($code = $zip->open($backup)))
		{
			return $this->error('err_no_zip', [$code]);
		}
		$zip->extractTo($path);
		$zip->close();
		unlink($backup);
		
		# Import files
		Filewalker::traverse($path, '*.zip', function($entry, $fullpath, $path) {
			$path = $path . 'files/';
			FileUtil::createDir($path);
			$zip = new \ZipArchive();
			$zip->open($fullpath);
			$zip->extractTo($path);
			$zip->close();
			unlink($fullpath);
			FileUtil::removeDir(GDO_PATH.'files');
			rename($path, GDO_PATH.'files');
		}, false, false, $path);
			
		
		# Import DB
		Filewalker::traverse($path, '*.gz', function($entry, $fullpath){
			$dump = file_get_contents($fullpath);
			$newpath = realpath($fullpath.'.sql');
			file_put_contents($newpath, gzdecode($dump));
			Database::instance()->dropDatabase(GWF_DB_NAME);
			Database::instance()->createDatabase(GWF_DB_NAME);
			Database::instance()->useDatabase(GWF_DB_NAME);
			Database::instance()->queryWrite("SOURCE $newpath");
		});
		
		
		# Flush Cache
		Cache::flush();
		
		# Hook
		GDT_Hook::callWithIPC("BackupImported");
		
		return GDT_Response::makeWith(GDT_Paragraph::withHTML('HI'));
	}
}
