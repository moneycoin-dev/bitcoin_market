<?php

namespace App\Presenters;

use Nette;
use App\BitcoindAuth as BTCAuth;
use App\Helpers\BaseHelper;
use App\Model\Wallet;
use App\Model\Configuration;
use App\Services\PriceConverter;

/**
 * 
 * @what Base from which all presenters inherits
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

abstract class BasePresenter extends Nette\Application\UI\Presenter
{

    public $wallet, $hlp, $configuration, $converter;
    
    public function injectBaseHelper(BaseHelper $bh){
        $this->hlp = $bh;
    }
    
    public function injectConfiguration(Configuration $conf){
        $this->configuration = $conf;
    }
    
    public function injectPriceConverter(PriceConverter $conv){
        $this->converter = $conv;
    }

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
