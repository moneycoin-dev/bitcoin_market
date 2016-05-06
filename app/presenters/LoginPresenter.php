<?php

namespace App\Presenters;

use Nette;
use App\Model\UserManager;
use Nette\Security as NS;
use captcha\Captcha\CaptchaControl;

/**
 * 
 * @what Market login Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class LoginPresenter extends GuestPresenter
{        

    protected $userManager;
    
    public function injectBaseModels(UserManager $man)
    {
        $this->userManager = $man;
    }

    protected function createComponentSignInForm()
    {
        $form = new Nette\Application\UI\Form;
        $form->addText('username', 'Uživatelské jméno:')
        ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
        ->setRequired('Prosím vyplňte své heslo.');
                        
        $form->addCaptcha('captcha')  
             ->addRule(\Nette\Forms\Form::FILLED, "Rewrite text from image.")
             ->addRule(CaptchaControl::CPTCHA, 'Try it again.')  
             ->setFontSize(15)  
             ->setLength(6) 
             ->setTextMargin(20)
             ->setTextColor(\Nette\Utils\Image::rgb(0,0,0)) 
             ->setBackgroundColor(\Nette\Utils\Image::rgb(240,240,240))  
             ->setImageHeight(30) 
             ->setImageWidth(0)  
             ->setExpire(100) 
             ->setFilterSmooth(false)
             ->setFilterContrast(false)  
             ->useNumbers(true);               

        $form->addCheckbox('remember', 'Zůstat přihlášen');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = array($this, 'signInFormSucceeded');
        
        $form->onValidate[] = array($this, 'signInValidate');
        
        return $form;
    }
    
    public function signInFormSucceeded($form)
    {   
        $this->redirect('Dashboard:in');
    }
    
    public function signInValidate($form, $values){
        
        $username = $form->values->username;
        $password = $form->values->password;
        
        $user = $this->getUser();     
        
        try {
            $user->login($username, $password);
            $user->setExpiration('30 minutes', TRUE);              
            
        } catch (NS\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }       
    }
}
