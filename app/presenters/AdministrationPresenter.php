<?php

namespace App\Presenters;

use App\Model\Configuration;
use Nette\Application\UI\Form;

class AdministrationPresenter extends ProtectedPresenter
{
    protected $configuration;
    
    public function injectConfiguration(Configuration $c){
        $this->configuration = $c;
    }
    
    public function createComponentClickableSettings(){
               
        $maitenance = $this->configuration->isMarketInMaintenanceMode();
         
        $form = new Form();
        
        if ($maitenance){
            $form->addSubmit("dm", "Disable Maitenance Mode")->onClick[] = function (){
                $this->configuration->changeMarketMode("off");
                $this->redirect("Administration:global");
            };
        } else {
            $form->addSubmit("em", "Enable Maitenance Mode")->onClick[] = function (){
                $this->configuration->changeMarketMode("on");
                $this->redirect("Administration:global");
            };
        }
        
        $withdrawals = $this->configuration->areWithdrawalsEnabled();
        
        if ($withdrawals){
            $form->addSubmit("dw", "Disable Withdrawals")->onClick[] = function (){
                $this->configuration->changeWithdrawalState("disabled");
                $this->redirect("Administration:global");
            };
        } else {
            $form->addSubmit("ew", "Enable WithDrawals")->onClick[] = function (){
                $this->configuration->changeWithdrawalState("enabled");
                $this->redirect("Administration:global");
            };
        }
        
        $form->addSubmit("wn", "New Escrow Wallet Address")->onClick[] = function (){
            $address = $this->wallet->generateAddress("escrow");
            $this->configuration->setEscrowAddress($address);
            $this->redirect("Administration:global");
        };
        
        return $form;
    }
}