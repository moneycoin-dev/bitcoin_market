<?php

namespace App\Presenters;

use Nette;
use App\Model;


/**
 * Base presenter for all application presenters.
 */
abstract class ProtectedPresenter extends Nette\Application\UI\Presenter
{        
	protected function startup(){
            
            parent::startup();
            
            if (!$this->getUser()->isAllowed(strtolower($this->getName()), 'list')) {
                $this->redirect('Login:in');
            }    
	}
}
