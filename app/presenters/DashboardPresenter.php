<?php

namespace App\Presenters;

use Nette;

class DashboardPresenter extends ProtectedPresenter
{        
        public function beforeRender(){
            $identity = $this->getUser()->getIdentity()->login;
            $this->template->login = $identity;
        }

	public function renderDefault()
	{
                $template = $this->createTemplate();
                $template->setFile(__DIR__ . '/templates/Dashboard/in.latte');       
	}
        
        public function createComponentTest(){
            $form = new Nette\Application\UI\Form;
            
            $form->addText('search', 'Vyhledávání:');
            
            return $form;
        }
        
        public function handlelogout()
	{
		$this->getUser()->logout(TRUE);
		$this->flashMessage('You have been signed out.');
		$this->redirect('Login:in');
	}
}
