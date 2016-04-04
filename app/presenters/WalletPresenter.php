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
    
    private function generateAddress($login){
        
        //generate new address for existing account
        //btc command object
        $command = new Command('getnewaddress', $login);
        
        //response handling
        $response = $this->btcClient->sendCommand($command);
        $result = json_decode($response->getBody()->getContents(), true);
        
        return $result;
    }
    
    private function redirector(){
        $this->flashMessage("Akce proběhla úspěšně.");
        $this->redirect("Wallet:in");
    }
    
    public function handleNewAddress(){
        $login = $this->getUser()->getIdentity()->login;
         
        //get timestamp and verify last address request
        //ddos protection
        $actualTimestamp = time();
        
        //convert timestamp to datetime object
        $date = date('Y-m-d H:i:s', $actualTimestamp);
        $dateToSave = new \DateTime($date);
        
        //get datetime of last request
        $lastRequested = $this->wallet->addressLastRequest($login);

        //ddos protection code
        //if last request is null, immediately generate new adress
        //user didn't used generation yet
        if (is_null($lastRequested)){
           $this->wallet->writeNewBtcAddress($this->generateAddress($login)['result'], $login, $dateToSave);
           $this->redirector();
        } else {
            
            //if last request is not null, then take diff against actual datetime
            $diff = $lastRequested->diff($dateToSave);
            
            //and check that min. 5 hours passed before generating new address
            if ($diff->h < 5){
                $this->flashMessage("Je nám líto, novou adresu můžete generovat pouze každých 5 hodin.");
                $this->redirect("Wallet:in");
            } else {
                
                //if 5 hours passed then generate and write new adress to db
                $this->wallet->writeNewBtcAddress($this->generateAddress($login)['result'], $login, $dateToSave);
                $this->redirector();
            }
        }
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
