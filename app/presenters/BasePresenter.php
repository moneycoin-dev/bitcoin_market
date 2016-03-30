<?php

namespace App\Presenters;

use Nette;
use App\Model;
use App\BitcoindAuth as BTCAuth;

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	public $btcClient;  

	protected function startup(){
            
        parent::startup();
        
    	$auth = new BTCAuth();
    	$client = $auth->btcd;
    	$this->btcClient = $client;
	}
}
