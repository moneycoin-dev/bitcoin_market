<?php

namespace App\Presenters;

use Nette;
use App\Model\Registration;
use Nette\Security as NS;
use Nbobtc\Command\Command;

class RegisterPresenter extends GuestPresenter
{

    /** @var Models\Registration */
    protected $regModel;

    public function injectBaseModels(Registration $reg)
    {
        $this->regModel = $reg;
    }

    protected function createComponentRegisterForm()
    {
        $form = new Nette\Application\UI\Form;
        $form->addText('login', 'Uživatelské jméno:')
        ->setRequired('Prosím vyplňte své uživatelské jméno.');

        $form->addPassword('pass1', 'Heslo:')
        ->setRequired('Prosím vyplňte své heslo.');

        $form->addPassword('pass2', 'Heslo znovu:')
        ->setRequired('Prosím vyplňte své heslo.')
        ->addRule($form::EQUAL, "Hesla se neshoduji.", $form['pass1']);

        $form->addText('pin', 'Bepečnostní pin:')
        ->setRequired('Prosím vyplňte svůj pin')
        ->addRule($form::PATTERN, '6 místný PIN', '([0-9]){6}');

        $form->addText('referal', 'Reference:')
        ->setRequired('Nick uzivatele ktery vas doporucil.');

        $form->addSubmit('send', 'Registruj');

        $form->onSuccess[] = array($this, 'registerFormSucceeded');
        $form->onValidate[] = array($this, 'registerFormValidate');

        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=control-group';
        $renderer->wrappers['pair']['.error'] = 'error';
        $renderer->wrappers['control']['container'] = 'div class=controls';
        $renderer->wrappers['label']['container'] = 'div class=control-label';
        $renderer->wrappers['control']['description'] = 'span class=help-inline';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-inline';

        // make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');

        foreach ($form->getControls() as $control) {
            if ($control instanceof \Nette\Forms\Controls\Button) {

                $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn');
                $usedPrimary = TRUE;

            } elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
                $control->getLabelPrototype()->addClass($control->getControlPrototype()->type);
                $control->getSeparatorPrototype()->setName(NULL);
            }
        }

        return $form;
    }

    public function registerFormValidate($form){

        $values = $form->getValues(TRUE);
        
        //check for duplicate names in db
        try {
            $this->regModel->checkIfUserExists($values['login']);
        } catch(\App\Model\DuplicateNameException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function registerFormSucceeded($form)
    {   
        $values = $form->getValues(TRUE);
        
        //store password value for later use
        $password = $values['pass2'];
        
        //form array to match the db format
        unset($values['pass1']);
        unset($values['pass2']);

        //hash the password for store in database
        $values['password'] = NS\Passwords::hash($password);

        //create db record
        $this->regModel->createUser($values);

        //get btcClient, issue getnewadress command, process response
        $btcClient = $this->btcClient;
        $command = new Command('getnewaddress', $values['login']);  
        $response = $btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true);

        //store user's btc adress to db
        $this->regModel->assignBtcAdress($values['login'], $result['result']); 

        //redirect user
        $this->flashMessage('Registration succeeded!');
        $this->redirect('Dashboard:in');     
    }
}