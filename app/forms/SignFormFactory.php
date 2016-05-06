<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\User;

/**
 * 
 * @what Login Form Factory
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */


class SignFormFactory extends Nette\Object
{

    private $user;

    public function __construct(User $user)
    {
            $this->user = $user;
    }

    public function create()
    {
        $form = new Form;
        $form->addText('username', 'Username:')
                ->setRequired('Please enter your username.');

        $form->addPassword('password', 'Password:')
                ->setRequired('Please enter your password.');

        $form->addCheckbox('remember', 'Keep me signed in');

        $form->addSubmit('send', 'Sign in');

        $form->onSuccess[] = array($this, 'formSucceeded');
        return $form;
    }


    public function formSucceeded(Form $form, $values)
    {
        if ($values->remember) {
                $this->user->setExpiration('14 days', FALSE);
        } else {
                $this->user->setExpiration('20 minutes', TRUE);
        }

        try {
                $this->user->login($values->username, $values->password);
        } catch (Nette\Security\AuthenticationException $e) {
                $form->addError($e->getMessage());
        }
    }
}
