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
    
    private function getOrderId(){
        $session = $this->getSession()->getSection("orders");
        return $session->orderID;
    }
    
    private function setOrderId($id){
        $session = $this->getSession()->getSection("orders");
        $session->orderID = $id;
    }

    public function beforeRender(){
        $login = $this->getUser()->getIdentity()->login;
        $isVendor = $this->listings->isVendor($login);
        $userOrders = $this->orders->getOrders($login);
        $pendingOrders = array();
        
        if ($this->listings->isVendor($login)){
            
            $vendorOrders = $this->orders->getOrders($login, 1);
            
            foreach($vendorOrders as $order){

                if ($order["status"] == "pending"){
                    array_push($pendingOrders, $order);
                }
            }
            
            $this->template->pendingOrders = $pendingOrders;
        }
        
        $this->template->userOrders = $userOrders;
        $this->template->isVendor = $isVendor;
    }
    
    public function createComponentProcessOrderForm() {
        $form = new Form();
        
        $form->addTextArea("seller_notes", "Seller notes text:");
        $form->addSelect("status", "Status")->setItems(array("Decline", "Shipped"), FALSE);
        $form->addSubmit("submit", "Potvrdit");
        $form->addSubmit("cancel", "Zpět na nevyřízené")->setValidationScope(false)
             ->onClick[] = function(){
            
                $this->flashMessage("Vyřízení objednávky zrušeno.");
                $this->redirect("Orders:in");
            };
        
        $form->onSuccess[] = array($this, "processOrderSuccess");
        
        return $form;
    }
    
    public function createComponentFinalizeForm(){
        
        $form = new Form();
        $form->addTextArea("buyer_notes", "Buyer notes:");
        $form->addSubmit("dispute", "Dispute")->onClick[] = function(){
            
            $id = $this->getOrderId();   
            $this->orders->changeOrderStatus($id, "dispute");
            $this->redirect("Orders:dispute", $id);
        };
        
        $form->addSubmit("finalize", "Finalize")->onClick[] = function(){
            
            $id = $this->getOrderId();
            $this->orders->orderFinalize($id);
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
            
            $id = $this->getOrderId();
            
            $this->orders->changeOrderStatus($id, $values['status']);
            $this->orders->writeSellerNotes($id, $values['seller_notes']);
            
            unset($this->getSession()->getSection("orders")->orderID);
            
            $this->flashMessage("Objednávka úspěšně vyřízena!");
            $this->redirect("Orders:in");
        }
    }
    
    public function actionProcess($id){
        
       $login = $this->getUser()->getIdentity()->login;
       
       if ($this->orders->isOwner($id, $login)){     
           if ($this->orders->getOrderStatus($id) == "pending"){
              $this->setOrderId($id); 
           } else {
               $this->redirect("Orders:in");
           }
           
       } else {
           $this->redirect("Orders:in");
       }    
    }
    
    public function actionView($id){
        
        $this->setOrderId($id);
        
        $login = $this->getUser()->getIdentity()->login;
        $order = $this->orders->getOrderDetails($id);
        
        if ($order['author'] == $login || $order['buyer'] == $login){
            $this->template->isVendor = $this->listings->isVendor($login);
            $this->template->orderInfo = $order;
        } else {
            $this->redirect("Orders:in");
        }
    }
}