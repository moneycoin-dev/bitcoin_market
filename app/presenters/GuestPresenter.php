<?php

namespace App\Presenters;

abstract class GuestPresenter extends BasePresenter
{        
    protected function startup(){

        parent::startup();

        if (!$this->getUser()->isAllowed(strtolower($this->getName()), 'list')) {
            $this->redirect('Dashboard:in');
        }    
    }
}
