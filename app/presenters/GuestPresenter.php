<?php

namespace App\Presenters;

/**
 * 
 * @what Guest Redirector
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

abstract class GuestPresenter extends BasePresenter
{        
    protected function startup(){

        parent::startup();

        if (!$this->getUser()->isAllowed(strtolower($this->getName()), 'list')) {
            $this->redirect('Dashboard:in');
        }    
    }
}
