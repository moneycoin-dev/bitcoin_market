<?php

namespace App\Presenters;

use App\Model\Orders;
use Nette\Utils\Paginator;

class SalesPresenter extends ProtectedPresenter {
   /* 
    public function startup() {
        parent::startup();
        
        if (!$this->getUser()->isAllowed(strtolower($this->getName()), "list")) {
            $this->redirect("Dashboard:in");
        }
    } */
    
    protected $orders;
    
    public function injectOrders(Orders $o){
        $this->orders = $o;
    }
    
    public function renderOn($param = 1){
        
        dump($this->getName());
        
        $paginator = new Paginator();
        $paginator->setItemsPerPage(2);
        $paginator->setPage($param);
      
        $login = $this->getUser()->getIdentity()->login;
        $userOrders = $this->orders->getOrders($login, $paginator);
        $pendingOrders = array();
        $closedOrders = array();
       
        //set paginator itemCount after paginator was used in model
        $paginator->setItemCount(count($userOrders));
        
        //store page count into session
        //doesn't render paginator on subsequent pages without this code
        $session = $this->getSession()->getSection("paginator");
        
        if (is_null($session->totalOrders)){
            $session->totalOrders = $paginator->getPageCount();
        }
        
        $vendorOrders = $this->orders->getOrders($login, $paginator, 1);

        foreach($vendorOrders as $order){

            if ($order["status"] == "pending"){
                array_push($pendingOrders, $order);
            }
            
            if ($order["status"] == "closed"){
                array_push($closedOrders, $order);
            }
        }

        $this->template->pendingOrders = $pendingOrders;
        $this->template->totalOrders = $session->totalOrders;                        
        $this->template->page = $param;
        
        unset($session); 
    }
    
    public function renderIn($page = 1){
        
        $paginator = new Paginator();
        
        $paginator->setItemsPerPage(4);
        $paginator->setPage($page);
        
      
        $login = $this->getUser()->getIdentity()->login;
       $pendingOrders = $this->orders->getOrders($login, $paginator, "pending", 1);
       
       dump(count($pendingOrders));
       
       
        //set paginator itemCount after paginator was used in model
        $paginator->setItemCount(count($pendingOrders));
        
        //store page count into session
        //doesn't render paginator on subsequent pages without this code
        $session = $this->getSession()->getSection("paginator");
        
        if (is_null($session->totalOrders)){
            $session->totalOrders = $paginator->getPageCount();
        }
        

       
       $closedOrders = $this->orders->getOrders($login, $paginator, "closed", 1);
   
        dump(count($closedOrders));
        dump(count($pendingOrders));

        $this->template->closedOrders = $closedOrders;
        $this->template->pendingOrders = $pendingOrders;  
        $this->template->totalOrders = $session->totalOrders;                     
        $this->template->page = $page;
        
        unset($this->getSession()->getSection("paginator")->totalOrders);     
    }
    
    public function createComponentProcessSaleForm() {
        $form = new Form();

        $form->addTextArea("seller_notes", "Seller notes text:");
        $form->addSelect("status", "Status")->setItems(array("Decline", "Shipped"), FALSE);
        $form->addSubmit("submit", "Potvrdit");
        $form->addSubmit("cancel", "Zpět na nevyřízené")->setValidationScope(false)
             ->onClick[] = function(){

                $this->flashMessage("Vyřízení objednávky zrušeno.");
                $this->redirect("Sales:in");
            };

        $form->onSuccess[] = array($this, "processSaleSuccess");

        return $form;
    }
    
    public function processSaleSuccess($form){
        
        $values = $form->getValues(TRUE);
        
        if ($form['submit']->submittedBy){
            
            $id = $this->getOrderId();
            
            $this->orders->changeOrderStatus($id, $values['status']);
            $this->orders->writeSellerNotes($id, $values['seller_notes']);
            
            unset($this->getSession()->getSection("orders")->orderID);
            
            $this->flashMessage("Objednávka úspěšně vyřízena!");
            $this->redirect("Sales:in");
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
}