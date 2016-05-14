<?php

namespace App\Presenters;

use Nette\Application\UI\Form;
use App\Model\Settings;

/**
 * 
 * @what User's BTC receiving adress implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class WalletPresenter extends ProtectedPresenter {
    
    /** @var App\Model\Settings */
    protected $st;
    
    /**
     * Dependency injection
     * @param Settings $st
     */
    public function injectSettings(Settings $st){
        $this->st = $st;
    }

    /**
     * Shortcut for same redirect used multiple times
     */
    private function redirector(){
        $this->flashMessage("Akce proběhla úspěšně.");
        $this->redirect("Wallet:in");
    }
    
    /**
     * Generates new random float number
     * based on int input values.
     * 
     * @param int $min random number range
     * @param int $max random number range
     * @return float
     */
    private function random_float ($min,$max) {
        return ($min+lcg_value()*(abs($max-$min)));
    }
    
    /**
     * Parses string of entered BTC addresses
     * (one address per line) and returns array
     * 
     * @param string $adw
     * @return array
     */
    private function getLines($adw){
        return preg_split("/((\r?\n)|(\r\n?))/", $adw);
    }
    
    /**
     * Generates new BTC address for account
     * dependent on last time applied
     */
    public function handleNewAddress(){
        $login = $this->hlp->logn();
         
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
            
           $this->wallet->writeNewBtcAddress(
                   $this->wallet->generateAddress($login), $login, $dateToSave);
           
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
                $this->wallet->writeNewBtcAddress(
                        $this->wallet->generateAddress($login), $login, $dateToSave);
                
                $this->redirector();
            }
        }
    }
    
    /**
     * Creates BTC Withdrawal form
     * @return Form
     */
    public function createComponentWithdrawForm() {
        $form = new Form();
        
        $form->addText("balance", "Množství")
             ->addRule($form::FILLED, "Prosím zadejte množství,"
                     . " které si přejete vybrat.")
             ->addRule($form::FLOAT, "Množství musí být číslo!");
        $form->addTextArea("adw", "Adresy kam poslat", 60, 10)
             ->addRule($form::FILLED, "Prosím vyplňte jednu nebo více adres"
                     . "kam se mají prostředky poslat.");
        $form->addPassword("pin", "PIN")
             ->addRule($form::MIN_LENGTH, 'Zadejte 6-ti znakový PIN.', 6);
        $form->addSubmit("send", "Odeslat Transakci");
        
        $form->onValidate[] = array($this, "withdrawValidate");
        $form->onSuccess[] = array($this, "withdrawSuccess");
        
        return $form;
    }
    
    /**
     * Withdrawals Form Validate callbak
     * Checks address validity, entered PIN
     * and user's balance.
     * 
     * @param Form $form
     */
    public function withdrawValidate($form){
        $addresses = $this->getLines($form->values->adw);
        $flags = array();
        $cnt = 1;
        
        foreach($addresses as $line){
           $flags[$cnt] = FALSE;
           
           if (!$this->wallet->validateAddress($line)["isvalid"]){
               $flags[$cnt] = TRUE;
           }    
           
           $cnt++;
        }
        
        $flags = array_keys($flags, TRUE);
        
        if (!empty($flags)){
            $errStr = "Některá z Vámi zadaných address není platná! ";
            
            foreach($flags as $value){
                $errStr .= "[".$value."]";
            }
            
            $form->addError($errStr);
        }
        
        $login = $this->hlp->logn();
        $pin = intval($form->values->pin);
        $pinDB = $this->st->getPin($login);
        
        if ($pin != $pinDB){
            $form->addError("Vámi zadaný PIN kód je neplatný!");
        }
        
        $accBal = $this->wallet->getBalance($login);
        $desiredBal = floatval($form->values->balance);
        
        if ($desiredBal > $accBal){
            $form->addError("Na Vašem účtu není tolik prostředků.");
        }
    }
    
    /**
     * Withdrawal form success callback
     * 
     * Implements ordinary withdrawal
     *  -> Entire balance to one adress
     * Implements withdrawal to multiple addresses
     *  -> Ammounts are randomized and then send out
     * 
     * @param Form $form
     */
    public function withdrawSuccess($form){
        $origin = $this->hlp->logn();
        $bal = floatval($form->values->balance);
        $addresses = $this->getLines($form->values->adw);
        $randoms = array();
        $ammounts = array();
        $limit = count($addresses);
        
        if ($limit > 1){
            for($i = 0; $i<$limit; $i++){
                $randoms[$i] = $this->random_float(0, 100);
            }
            
            $sum = array_sum($randoms);

            for($i=0; $i<$limit; $i++){
                $ammounts[$i] = ($randoms[$i] / $sum) * $bal;
 
                $this->wallet->sendAndStore(
                        "withdraw", $origin, $addresses[$i], $ammounts[$i]);
            }
        } else {
            $this->wallet->sendAndStore(
                        "withdraw", $origin, $addresses[0], $bal);
        }
    }

    /**
     * Serves rendering of template variables
     */
    public function beforeRender() {
        $login = $this->hlp->logn();
        $this->template->walletAddress = $this->wallet->getBtcAddress($login);
        $this->template->balance = $this->wallet->getBalance($login);          
    }
}
