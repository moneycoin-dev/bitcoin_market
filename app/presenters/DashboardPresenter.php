<?php

namespace App\Presenters;

use Nette;
use App\Model\Listings;

/**
 * 
 * @what Market user interface
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class DashboardPresenter extends ProtectedPresenter
{   
    protected $listings;
    
    public function injectListings(Listings $l){
        $this->listings = $l;
    }
    
    public function beforeRender(){
        
        $login = $this->getUser()->getIdentity()->login;
        $isVendor = $this->listings->isVendor($login);
        $this->template->login = $login;
        $this->template->isVendor = $isVendor;
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
