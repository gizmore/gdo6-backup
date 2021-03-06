<?php
namespace GDO\Backup\Method;

use GDO\Table\MethodTable;
use GDO\DB\ArrayResult;
use GDO\Core\MethodAdmin;
use GDO\File\Filewalker;
use GDO\Backup\GDO_Backup;
use GDO\Date\Time;
use GDO\UI\GDT_DownloadButton;

/**
 * List of Backups with downloads.
 * @author gizmore
 */
final class ListBackups extends MethodTable
{
	use MethodAdmin;
	
	private $backups;
	
	public function gdoTable() { return GDO_Backup::table(); }
	
	public function gdoHeaders()
	{
		$backups = GDO_Backup::table();
		return [
			GDT_DownloadButton::make('backup_link'),
			$backups->gdoColumn('backup_size'),
			$backups->gdoColumn('backup_name'),
// 			$backups->gdoColumn('backup_path'),
			$backups->gdoColumn('backup_created'),
		];
	}
	
	public function beforeExecute()
	{
	    $this->renderNavBar();
		Admin::make()->renderBackupNavBar();
	}
	
	public function getBackups()
	{
		$this->backups = [];
		Filewalker::traverse(GDO_PATH.'protected/backup', '/\\.zip$/', [$this, 'addBackup']);
		return $this->backups;
	}
	
	public function addBackup($entry, $fullpath)
	{
		$this->backups[] = GDO_Backup::blank([
			'backup_name' => $entry,
			'backup_path' => $fullpath,
			'backup_created' => Time::getDate(stat($fullpath)['mtime']),
			'backup_size' => filesize($fullpath),
		]);
	}

	public function getResult()
	{
	    $this->getBackups();
		return new ArrayResult($this->backups, GDO_Backup::table());
	}
	
}
