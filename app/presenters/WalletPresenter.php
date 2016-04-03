<?php

namespace App\Presenters;

use App\Model\Wallet;
use Nbobtc\Command\Command;

class WalletPresenter extends ProtectedPresenter {
    
    /** @var Model\Wallet */
    protected $wallet;

    public function injectBaseModels(Wallet $wallet)
    {
        $this->wallet = $wallet;
    }
    
    public function handleNewAddress(){
        $login = $this->getUser()->getIdentity()->login;
        
        //generate new address for existing account
        $command = new Command('getnewaddress', $login);
        
        //response handling
        $response = $this->btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true);
        
        //model - write to db
        $this->wallet->writeNewBtcAddress($result['result'], $login);
        
        $this->flashMessage("Akce proběhla úspěšně.");
        $this->redirect("Wallet:in");
    }

    public function beforeRender() {
        //get user login to check balance
        $login = $this->getUser()->getIdentity()->login;

        //query bitcoind, get response
        $command = new Command('getbalance', $login);             
        $response = $this->btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true);

        //render btc adress from db, bitcoind response
        $this->template->walletAddress = $this->wallet->getBtcAddress($login);
        $this->template->balance = $result['result'];          
    }
}
