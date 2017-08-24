<?php
namespace GDO\Backup;

use GDO\Core\Module;
use GDO\Type\GDO_Char;

final class Module_Backup extends Module
{
	public $module_priority = 100;
	public function getConfig()
	{
		return array(
			GDO_Char::make('backup_lastdate')->size(8)->initial('19700101'),
		);
	}
	public function cfgLastDate() { return $this->getConfigVar('backup_lastdate'); }
	
	
}
