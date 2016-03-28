<?php

namespace App\Presenters;

use Nette;
use App\Model;

abstract class GuestPresenter extends Nette\Application\UI\Presenter
{        
	protected function startup(){
            
            parent::startup();
            
            if (!$this->getUser()->isAllowed(strtolower($this->getName()), 'list')) {
                $this->redirect('Dashboard:in');
            }    
	}
}
