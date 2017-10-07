<?php
namespace GDO\Backup;

use GDO\Core\GDO_Module;
use GDO\DB\GDT_Char;

final class Module_Backup extends GDO_Module
{
	public $module_priority = 100;
	public function getConfig()
	{
		return array(
			GDT_Char::make('backup_lastdate')->size(8)->initial('19700101'),
		);
	}
	public function cfgLastDate() { return $this->getConfigVar('backup_lastdate'); }
	
	
}
