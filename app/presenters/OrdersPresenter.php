<?php

namespace App\Presenters;

use App\Model\Orders;
use App\Model\Listings;
use Nette\Application\UI\Form;
use Nette\Utils\Paginator;

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
        
        return $this->orders->isOrderFinalized($id);
    }
    
    private function getOrderId(){
        $session = $this->getSession()->getSection("orders");
        return $session->orderID;
    }
    
    private function setOrderId($id){
        $session = $this->getSession()->getSection("orders");
        $session->orderID = $id;
    }

    public function renderIn($active = 1, $closed = 1){
        
        $pagActive = new Paginator();
        $pagActive->setItemsPerPage(2);
        $pagActive->setPage($active);
        
        $pagClosed = new Paginator();
        $pagClosed->setItemsPerPage(2);
        $pagClosed->setPage($closed);
      
        $login = $this->getUser()->getIdentity()->login;
        $pendingOrders = $this->orders->getOrders($login, $pagActive, "pending");
        $closedOrders = $this->orders->getOrders($login, $pagClosed, "closed");
       
        //set paginator itemCount after paginator was used in model
        $pagActive->setItemCount(count($pendingOrders));
        $pagClosed->setItemCount(count($closedOrders));
        
        //store page count into session
        //doesn't render paginator on subsequent pages without this code
        $session = $this->getSession()->getSection("paginator");
        
        if (is_null($session->totalOrders)){
            $session->totalOrders = $pagActive->getPageCount();
        }
        
        if (is_null($session->totalClosed)){
            $session->totalClosed = $pagClosed->getPageCount();
        }
       
        $this->template->totalOrders = $session->totalOrders; 
        $this->template->totalClosed = $session->totalClosed;
        $this->template->active = $active;
        $this->template->closed = $closed;
        $this->template->pendingOrders = $pendingOrders;
        $this->template->closedOrders = $closedOrders;

        unset($session);
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

    public function actionView($id){
        
        $this->setOrderId($id);     
        $login = $this->getUser()->getIdentity()->login;
        $order = $this->orders->getOrderDetails($id);
        
        if ($order['buyer'] == $login || $order['author'] == $login){
            $this->template->isVendor = $this->listings->isVendor($login);
            $this->template->isFinalized = $this->orders->isOrderFinalized($id);
            $this->template->orderInfo = $order;
        } else {
            $this->redirect("Orders:in");
        }
    }
}