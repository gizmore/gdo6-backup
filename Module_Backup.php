<?php
namespace GDO\Backup;

use GDO\Core\GDO_Module;
use GDO\DB\GDT_Char;
use GDO\DB\GDT_Checkbox;
use GDO\File\GDT_Path;

final class Module_Backup extends GDO_Module
{
	public $module_priority = 100;
	public function defaultEnabled() { return false; }
	public function onLoadLanguage() { return $this->loadLanguage('lang/backup'); }
	public function href_administrate_module() { return href('Backup', 'Admin'); }
	public function getDependencies() { return ['ZIP']; }
	public function getConfig()
	{
		return array(
			GDT_Char::make('backup_lastdate')->length(8)->initial('19700101')->editable(false),
			GDT_Checkbox::make('backup_send_mail')->initial('0'),
		    GDT_Path::make('mysql_path')->initial('mysql')->existingFile(),
		    GDT_Path::make('mysqldump_path')->initial('mysqldump')->existingFile(),
		);
	}
	public function cfgLastDate() { return $this->getConfigVar('backup_lastdate'); }
	public function cfgSendMail() { return $this->getConfigValue('backup_send_mail'); }
	public function cfgMysqlPath() { return $this->getConfigVar('mysql_path'); }
	public function cfgMysqldumpPath() { return $this->getConfigVar('mysqldump_path'); }
	
}
