<?php

namespace App\Presenters;

use App\Model\Orders;
use App\Model\Listings;
use Nette\Application\UI\Form;

class OrdersPresenter extends ProtectedPresenter {
    
    protected $orders;
    protected $listings;

    public function injectOrders(Orders $o){
        $this->orders = $o;
    }
    
    public function injectListings(Listings $l){
        $this->listings = $l;
    }
    
    public function isFinalized($id){
        
        //check if order has been finalized
        //used from template
        if ($this->orders->getOrderDetails($id)['finalized'] == "no"){
            return FALSE;
        } else {
            return TRUE;
        } 
    }

    public function beforeRender(){
        $login = $this->getUser()->getIdentity()->login;
        $isVendor = $this->listings->isVendor($login);
        $orders = $this->orders->getUserOrders($login);
        $pendingOrders = array();
        
        if ($this->listings->isVendor($login)){
            
            foreach($orders as $order){
                if ($order["status"] == "pending"){
                    array_push($pendingOrders, $order);
                }
            }
            
            $this->template->pendingOrders = $pendingOrders;
        }
        
        $this->template->orders = $orders;
        $this->template->isVendor = $isVendor;
    }
    
    public function createComponentProcessOrderForm() {
        $form = new Form();
        
        $form->addTextArea("seller_notes", "Seller notes text:");
        $form->addSelect("status", "Status")->setItems(array("Decline", "Shipped"), FALSE);
        $form->addSubmit("submit", "Potvrdit");
        $form->addSubmit("cancel", "Zpět na nevyřízené")->onClick[] = function(){
  
        };
        
        $form->onSuccess[] = array($this, "processOrderSuccess");
        $form->onValidate[] = array($this, "processOrderValidate");
        
        return $form;
    }
    
    public function createComponentFinalizeForm(){
        
        $form = new Form();
        $form->addTextArea("buyer_notes", "Buyer notes:");
        $form->addSubmit("dispute", "Dispute")->onClick[] = function(){
  
        };
        
        $form->addSubmit("finalize", "Finalize")->onClick[] = function(){
  
        };
         
        return $form;
    }
    
    public function createComponentPartialReleaseForm(){
        $form = new Form();
        
        $form->addText("amount", "Částka k uvolnění:");
        $form->addSubmit("submit", "Uvolnit");
        
        return $form;
    }
    
    public function processOrderSuccess($form){
        
        $values = $form->getValues(TRUE);
        
        if ($form['submit']->submittedBy){
            $session = $this->getSession()->getSection("orders");
            $id = $session->orderID;
            
            $this->orders->changeOrderStatus($id, $values['status']);
            $this->orders->writeSellerNotes($id, $values['seller_notes']);
            
            unset($session->orderID);
        }
    }
    
    public function processOrderValidate($form){
        
    }
    
    public function actionProcess($id){
        
       $login = $this->getUser()->getIdentity()->login;
       
       if ($this->orders->isOwner($id, $login)){
           
           if ($this->orders->getOrderStatus($id) == "pending"){
               
               $session = $this->getSession()->getSection("orders");
               $session->orderID = $id;

               $form =  $this->getComponent("processOrderForm");
               $form->render();          
           } else {
               $this->redirect("Orders:in");
           }
           
       } else {
           $this->redirect("Orders:in");
       }    
    }
}