<?php
namespace GDO\Backup\Method;

use GDO\Table\MethodTable;
use GDO\DB\ArrayResult;
use GDO\Core\GDT_Response;
use GDO\Core\MethodAdmin;
use GDO\File\Filewalker;
use GDO\Backup\GDO_Backup;
use GDO\Date\Time;
use GDO\UI\GDT_Button;

final class ListBackups extends MethodTable
{
	use MethodAdmin;
	
	private $backups;
	
	public function getHeaders()
	{
		$backups = GDO_Backup::table();
		return array(
			GDT_Button::make('backup_link')->label('btn_download'),
			$backups->gdoColumn('backup_size'),
			$backups->gdoColumn('backup_name'),
			$backups->gdoColumn('backup_path'),
			$backups->gdoColumn('backup_created'),
		);
	}
	
	public function execute()
	{
		return GDT_Response::makeWith(
			$this->renderNavBar(),
			Admin::make()->renderBackupNavBar(),
			parent::execute()
		);
	}
	
	public function getBackups()
	{
		$this->backups = [];
		Filewalker::traverse(GDO_PATH.'protected/backup', '/\\.zip$/', [$this, 'addBackup']);
		return $this->backups;
	}
	
	public function addBackup($entry, $fullpath)
	{
		$this->backups[] = GDO_Backup::blank(array(
			'backup_name' => $entry,
			'backup_path' => $fullpath,
			'backup_created' => Time::getDate(stat($fullpath)['mtime']),
			'backup_size' => filesize($fullpath),
		));
	}

	public function getResult()
	{
		return new ArrayResult($this->getBackups(), GDO_Backup::table());
	}

	
}
