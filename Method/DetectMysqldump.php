<?php
namespace GDO\Backup\Method;

use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;
use GDO\Util\Process;
use GDO\Backup\Module_Backup;
use GDO\Core\Website;
use GDO\Core\MethodAdmin;

/**
 * Auto-detect mysql binaries.
 * @author gizmore
 * @version 6.10
 */
final class DetectMysqldump extends MethodForm
{
    use MethodAdmin;
    
    public function beforeExecute()
    {
        $this->renderNavBar();
        Admin::make()->renderBackupNavBar();
    }
    
    public function createForm(GDT_Form $form)
    {
        $form->addFields([
            GDT_Submit::make(),
            GDT_AntiCSRF::make(),
        ]);
    }
    
    public function formValidated(GDT_Form $form)
    {
        # Detect mysql
        if ($path = Process::commandPath("mysql"))
        {
            Module_Backup::instance()->saveConfigVar('mysql_path', $path);
            Website::redirectMessage('msg_detected_mysql', null, href('Admin', 'Config', "&module=Backup"));
        }
        else
        {
            return $this->error('err_file_not_found', ['mysql'])->
            add($this->renderPage());
        }
        
        # Detect mysqldump
        if ($path = Process::commandPath("mysqldump"))
        {
            Module_Backup::instance()->saveConfigVar('mysqldump_path', $path);
            Website::redirectMessage('msg_detected_mysqldump', null, href('Admin', 'Config', "&module=Backup"));
        }
        else
        {
            return $this->error('err_file_not_found', ['mysqldump'])->
            add($this->renderPage());
        }
    }
    
}
