<?php

namespace App\Presenters;

use Nette;
use App\Model\Settings;

/**
 * 
 * @what User profile settings
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class SettingsPresenter extends ProtectedPresenter
{

    protected $settings;

    public function injectSettings(Settings $settings){
        $this->settings = $settings;
    }     

    public function createComponentSettingsForm(){
        $form = new Nette\Application\UI\Form;

        $form->addPassword('oldpw', 'Staré heslo');

        $form->addPassword('newpw', 'Nové heslo');

        $form->addPassword('newpw2', 'Nové heslo pro kontrolu')
             ->addRule($form::EQUAL, 'Hesla se musi shodovat', $form['newpw']);  

        $form->addText('pinold', 'Starý pin:');

        $form->addText('pinnew', 'Nový pin:');

        $form->addText('jabber', 'Jabber:');

        $form->addTextArea('pgp', 'PGP Klíč:');

        $form->addSubmit('sendx', 'Uložit změny');

        $form->onValidate[] = array($this, 'settingsFormValidate');
        $form->onSuccess[] = array($this, 'settingsFormSuccess');

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

    public function settingsFormValidate($form){

       $id = $this->getUser()->getIdentity()->getId();           
       $values = $form->getValues();

       if (strcmp($values->newpw, "") || strcmp($values->newpw2, "") !== 0){
            if (strcmp($values->oldpw, "") == 0){
                $form->addError('Pro zmenu hesla musite vyplnit i heslo soucasne.');
            } else {
                try{
                    $this->settings->newPassword($values->oldpw, $values->newpw, $id);
                } catch (Nette\Security\AuthenticationException $e) {
                    $form->addError($e->getMessage());
                }
            }
        }

        if (strcmp($values->pinnew, "") !== 0){
            if (strcmp($values->pinold, "") == 0){
                $form->addError('Pro zmenu Pinu zadejte starý pin.');
            } else {
                try {
                    $this->settings->newPin($values->pinold, $values->pinnew, $id);
                } catch (\Exception $e) {
                    $form->addError($e->getMessage());
                }
            }
        }

        if (strcmp($values->pgp, "") !==0 ){
            if ($this->settings->isPgpNull($id)){
                $this->settings->newPgpKey($values->pgp, $id);

            } else if (strcmp($values->oldpw, "") == 0){
                $form->addError('Pro opetovnou zmenu PGP klice vyplnte stare heslo.');

            } else {
                try{
                    if ($this->settings->verifyOldPassword($values->oldpw, $id)){
                        $this->settings->newPgpKey($values->pgp, $id);
                    }
                } catch (Nette\Security\AuthenticationException $e) {
                    $form->addError($e->getMessage());
                }
            }
        }

        if (strcmp($values->jabber, "") !== 0){
            $this->settings->jabberID($values->jabber, $id);
        }
    }

    public function settingsFormSuccess($form){
        $this->flashMessage("Zmeny probehly v poradku.");
        $this->restoreRequest($this->backlink);
        $this->redirect('Settings:in'); 
    }
}
