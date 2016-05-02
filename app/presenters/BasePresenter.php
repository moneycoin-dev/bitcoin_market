<?php

namespace App\Presenters;

use Nette;
use App\BitcoindAuth as BTCAuth;
use App\Model\Wallet;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public $wallet;

    protected function startup(){

        parent::startup();

        $auth = new BTCAuth();
        $client = $auth->btcd;
        $this->btcClient = $client; 
        $this->wallet = new Wallet($client);
    }
}
