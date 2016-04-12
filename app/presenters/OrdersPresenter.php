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

    public function beforeRender(){
        $id = $this->getUser()->getIdentity()->getId();
        $isVendor = $this->listings->isVendor($id);
        $orders = $this->orders->getUserOrders($id);
        $pendingOrders = array();
        
        if ($this->listings->isVendor($id)){
            
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
        
        $form->addSelect("status", "Status")->setItems(array("Decline", "Shipped"), FALSE);
        $form->addSubmit("submit", "Potvrdit");
        $form->addSubmit("cancel", "Zpět na nevyřízené")->onClick[] = function(){
  
        };
        
        $form->onSuccess[] = array($this, "processOrderSuccess");
        $form->onValidate[] = array($this, "processOrderValidate");
        
        return $form;
    }
    
    public function processOrderSuccess($form){
 
    }
    
    public function processOrderValidate($form){
        
    }
    
    public function actionProcess($id){
        
       $form =  $this->getComponent("processOrderForm");
       $form->render();    
    }
}