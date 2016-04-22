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

    public function renderIn($page = 1){
        
        $paginator = new Paginator();
        $paginator->setItemsPerPage(2);
        $paginator->setPage($page);
        
        $pagX = new Paginator();
        $pagX->setItemsPerPage(2);
        $pagX->setPage($page);
      
        $login = $this->getUser()->getIdentity()->login;
        $pendingOrders = $this->orders->getOrders($login, $paginator, "pending");
        $closedOrders = $this->orders->getOrders($login, $pagX, "closed");
        
        dump(count($pendingOrders));
        dump(count($closedOrders));
       
        //set paginator itemCount after paginator was used in model
        $paginator->setItemCount(count($pendingOrders));
        $pagX->setItemCount(count($closedOrders));
        
        //store page count into session
        //doesn't render paginator on subsequent pages without this code
        $session = $this->getSession()->getSection("paginator");
        
        if (is_null($session->totalOrders)){
            $session->totalOrders = $paginator->getPageCount();
        }
        
        if (is_null($session->x)){
            $session->x = $pagX->getPageCount();
        }
       
        $this->template->totalOrders = $session->totalOrders; 
        $this->template->x = $session->x;
        $this->template->page = $page;
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