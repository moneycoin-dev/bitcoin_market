<?php

namespace App\Presenters;

use App\Model\Orders;
use App\Model\Listings;
use App\Model\Configuration;
use Nette\Application\UI\Form;
use Nette\Utils\Paginator;

/**
 * 
 * @what Vendor & User order viewer Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

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
    
    private function shippedStatus(){
        return $this->orders->getOrderStatus($this->getOrderId());
    }

    public function renderIn($active = 1, $closed = 1){
        
        $pagActive = new Paginator();
        $pagActive->setItemsPerPage(2);
        $pagActive->setPage($active);
        
        $pagClosed = new Paginator();
        $pagClosed->setItemsPerPage(2);
        $pagClosed->setPage($closed);
      
        $login = $this->getUser()->getIdentity()->login;
        $pendingOrders = $this->orders->getOrders($login,"pending", $pagActive);
        $closedOrders = $this->orders->getOrders($login, "closed", $pagClosed);
        $disputes = $this->orders->getOrders($login, "dispute");
       
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
       
        $this->template->disputes = $disputes;
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
        
        if ($this->shippedStatus() == "Shipped"){
               
            $form->addSubmit("dispute", "Dispute")->onClick[] = function(){

                $id = $this->getOrderId();   
                $this->orders->changeOrderStatus($id, "dispute");
                $this->redirect("Orders:dispute", $id);
            };

            $form->addSubmit("finalize", "Finalize")->onClick[] = function(){

                $id = $this->getOrderId();
                $this->orders->orderFinalize($id);
            };    
        }
         
        return $form;
    }
    
    public function createComponentPartialReleaseForm(){
        
        $form = new Form();
        
        if ($this->shippedStatus() == "Shipped"){
           
            $form->addText("amount", "Částka k uvolnění:");
            $form->addSubmit("submit", "Uvolnit");
        }
        
         return $form;
    }
    
    protected $cm;
    
    public function injectX(Configuration $cm){
        $this->cm = $cm;
    }

    public function actionView($id){
        
       // $this->cm->changeWithdrawalsState("enabled");
        dump($this->cm->areWithdrawalsEnabled());
        
        $this->setOrderId($id);     
        $login = $this->getUser()->getIdentity()->login;
        $order = $this->orders->getOrderDetails($id);
        $buyer_notes = $this->orders->getNotesLeft($id);
        
       $this->getComponent("finalizeForm")->getComponents()["buyer_notes"]
               ->setValue($buyer_notes);
        
        if ($order["buyer"] == $login || $order["author"] == $login){
            $this->template->isVendor = $this->listings->isVendor($login);
            $this->template->isFinalized = $this->orders->isOrderFinalized($id);
            $this->template->orderInfo = $order;
        } else {
            $this->redirect("Orders:in");
        }
    }
    
    public function createComponentDisputeComplaint(){
        $form = new Form();
        $form->addTextArea("complaintMessage", "Text zprávy" );
        $form->addSubmit("send", "Odeslat");
        
        $form->onSuccess[] = array($this, "sendComplaint");
        
        return $form;
    }
    
    public function sendComplaint($form){
       $values = $form->getValues(TRUE);
       $id =  $this->getOrderId();     
       $timestamp = time();
       $autor = $this->getUser()->getIdentity()->login;
       $this->orders->writeDisputeContents($id, $values['complaintMessage'],
               $timestamp, $autor);

       $this->redirect("Orders:dispute", $id);
    }
    
    public function actionDispute($id){
        
        $login = $this->getUser()->getIdentity()->login;
        $orderStatus = $this->orders->getOrderStatus($id);
        $participants = $this->orders->getOrderParticipants($id);
        
        if ($orderStatus == "Shipped" || $orderStatus == "dispute"){
            if ($login == $participants["author"] || $login == $participants["buyer"]){
                $this->setOrderId($id);
                $this->template->messages =  $this->orders->getDisputeContents($id);
            } else {
                $this->redirect("Orders:in");
            }
        } else {
            $this->redirect("Orders:in");
        }
    }
}