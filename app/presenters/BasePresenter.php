<?php

namespace App\Presenters;

use Nette;
use App\BitcoindAuth as BTCAuth;
use App\Helpers\BaseHelper;
use App\Model\Wallet;
use App\Model\Orders;
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

    public $wallet, $hlp, $configuration,
            $orders, $converter, $options;
    
    public function injectBaseHelper(BaseHelper $bh){
        $this->hlp = $bh;
    }
    
    public function injectConfiguration(Configuration $conf){
        $this->configuration = $conf;
    }
    
    public function injectPriceConverter(PriceConverter $conv){
        $this->converter = $conv;
    }
    
    public function injectOrders(Orders $o){
        $this->orders = $o;
    }

    protected function startup(){

        parent::startup();
        
        //If script runs from console
        //don't do redirect, it's CRON
        if (PHP_SAPI !== "cli"){    
            $ddosProtection = $this->getSession()->getSection("ddos")->protection;

            if (!$ddosProtection){
                 $this->redirect("Entry:in");
            } 
        }
        
        $this->options = $this->configuration->returnOptions();
        $this->template->title = $this->options["market_name"] . "|" .$this->getName();

        $auth = new BTCAuth();
        $client = $auth->btcd;
        $cv = new PriceConverter($this->configuration);
        $this->wallet = new Wallet($client, $cv);  
    }
}
