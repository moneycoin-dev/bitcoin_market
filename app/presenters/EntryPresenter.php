<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI;
use Captcha\Captcha\CaptchaControl;
use Nette\Http\Request;

/**
 * 
 * @what Market entry DDOS protection Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class EntryPresenter extends UI\Presenter
{   
    protected $httpRequest;
    
    public function __construct(Request $r){
        
        $this->httpRequest = $r;
    }
    
    protected function startup() {
        parent::startup();
        
        //redirections if protection passed
        //and someone tries to access domain root
        
        $session = $this->ddosSession();
        $req = $this->httpRequest;
        
        if ($session->protection && is_null($req->getReferer())){
            $this->redirect("Login:in");
        }     
    }
    
    private function ddosSession(){
        return $this->getSession()->getSection("ddos");
    }
    
    public function createComponentInitialProtection(){
        
        //protects against login bruteforcing
        //and excessive use of MYSQL resources
        
        $form = new Nette\Application\UI\Form;

        $form->addCaptcha('captcha')
             ->addRule(\Nette\Forms\Form::FILLED, "Rewrite text from image.")
             ->addRule(CaptchaControl::CPTCHA, 'Try it again.')  
             ->setFontSize(14)  
             ->setLength(6) 
             ->setTextMargin(20) 
             ->setTextColor(\Nette\Utils\Image::rgb(0,0,0)) 
             ->setBackgroundColor(\Nette\Utils\Image::rgb(240,240,240)) 
             ->setImageHeight(28)  
             ->setImageWidth(100)  
             ->setExpire(100) 
             ->setFilterSmooth(false) 
             ->setFilterContrast(false)  
             ->useNumbers(true);

        $form->addSubmit('send', 'Odeslat');
        $form->onSuccess[] = array($this, 'captchaSucceeded');

        return $form;
    }

    public function captchaSucceeded(){
        
        //set protection to TRUE, BasePresenter checks for this value
        //before granting access
        
        $session = $this->ddosSession();
        $session->protection = TRUE;
        $this->redirect("Login:in");
    }
}
