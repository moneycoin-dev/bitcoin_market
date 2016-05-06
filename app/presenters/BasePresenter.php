<?php

namespace App\Presenters;

use Nette;
use App\BitcoindAuth as BTCAuth;
use App\Model\Wallet;

/**
 * 
 * @what Base from which all presenters inherits
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public $wallet;

    protected function startup(){

        parent::startup();
        
        $ddosProtection = $this->getSession()->getSection("ddos")->protection;
        
        if (!$ddosProtection){
             $this->redirect("Entry:in");
        } 

        $auth = new BTCAuth();
        $client = $auth->btcd;
        $this->wallet = new Wallet($client);
    }
}
