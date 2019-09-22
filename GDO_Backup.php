<?php
namespace GDO\Backup;

use GDO\Core\GDO;
use GDO\DB\GDT_String;
use GDO\File\GDT_Path;
use GDO\Date\GDT_DateTime;
use GDO\File\FileUtil;
use GDO\File\GDO_File;
use GDO\Date\Time;
use GDO\UI\GDT_Button;
use GDO\File\GDT_Filesize;

final class GDO_Backup extends GDO
{
	public function gdoColumns()
	{
		return array(
			GDT_String::make('backup_name')->label('name'),
			GDT_Path::make('backup_path'),
			GDT_DateTime::make('backup_created')->label('created_at'),
			GDT_Filesize::make('backup_size'),
		);
	}
	
	public function getID() { return null; }
	public function getName() { return $this->getVar('backup_name'); }

	public function href_backup_link() { return href('Backup', 'Download', "&backup_name=" . urlencode($this->getName())); }
	
	/**
	 * @return \GDO\File\GDO_File
	 */
	public function getFile()
	{
		$path = $this->getVar('backup_path');
		return GDO_File::blank(array(
			'file_name' => $this->getName(),
			'file_type' => 'application/zip',
			'file_size' => filesize($path),
		))->tempPath($path);
	}
	
	##############
	### Static ###
	##############
	/**
	 * @param string $name
	 * @return \GDO\Backup\GDO_Backup
	 */
	public static function findByName($name)
	{
		$path = GDO_PATH . 'protected/backup/' . $name;
		if (FileUtil::isFile($path))
		{
			return GDO_Backup::blank(array(
				'backup_name' => $name,
				'backup_path' => $path,
				'backup_created' => Time::getDate(stat($path)['mtime']),
				'backup_size' => filesize($path),
			));
		}
		
		throw new \GDO\Core\GDOError('err_file_not_found', [html($path)]);
	}
	
}
