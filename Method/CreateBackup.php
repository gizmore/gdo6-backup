<?php
namespace GDO\Backup\Method;

use GDO\Form\MethodForm;
use GDO\Form\GDT_Form;
use GDO\Core\GDT_Response;
use GDO\Core\MethodAdmin;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;

final class CreateBackup extends MethodForm
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
			GDT_AntiCSRF::make(),
			GDT_Submit::make(),
		));
	}
	
	public function formValidated(GDT_Form $form)
	{
		Cronjob::make()->doBackup();
		return $this->renderPage();
	}
	
}
