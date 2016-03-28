<?php

namespace App\Presenters;

use Nette;
use App\Model\Wallet;
use App\BitcoindAuth as BTCAuth;
use Nbobtc\Command\Command;

class WalletPresenter extends ProtectedPresenter {
    
        /** @var Model\Wallet */
        protected $wallet;
        
        public function injectBaseModels(Wallet $wallet)
        {
            $this->wallet = $wallet;
        }
        
        public function beforeRender() {
            //get user login to check balance
            $login = $this->getUser()->getIdentity()->login;
                         
            //query bitcoind, get response
            $btcauth = new BTCAuth();
            $client = $btcauth->btcd;
            $command = new Command('getbalance', $login);             
            $response = $client->sendCommand($command);
            $result = json_decode($response->getBody()->getContents(), true);
            //dump($result);
            
            //render btc adress from db, bitcoind response
            $this->template->walletAddress = $this->wallet->getBtcAddress($login);
            $this->template->balance = $result['result'];          
        }
}
