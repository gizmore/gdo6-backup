<?php
namespace GDO\Backup\Method;

use GDO\Core\Method;
use GDO\Core\MethodAdmin;
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Page;
use GDO\Core\Application;
use GDO\Core\GDT_Response;
use GDO\UI\GDT_Panel;

/**
 * A backup admin method. renders backup tabs.
 * @author gizmore
 */
final class Admin extends Method
{
	use MethodAdmin;
	
	public function beforeExecute()
	{
	    if (Application::instance()->isHTML())
	    {
	    	GDT_Page::$INSTANCE->topTabs->addField($this->renderBackupNavBar());
	    }
	}
	
	public function renderBackupNavBar()
	{
		return GDT_Bar::makeWith(
			GDT_Link::make('link_backup_create')->href(href('Backup', 'CreateBackup')),
			GDT_Link::make('link_backup_import')->href(href('Backup', 'ImportBackup')),
		    GDT_Link::make('link_backup_downloads')->href(href('Backup', 'ListBackups')),
		    GDT_Link::make('link_backup_detect_mysqldump')->href(href('Backup', 'DetectMysqldump'))
	    )->horizontal();
	}
	
	public function execute()
	{
// 		return GDT_Response::make()->addField(GDT_Panel::make()->text('bla'));
	}
	
}
