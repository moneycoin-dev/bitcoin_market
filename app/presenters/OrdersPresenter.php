<?php

namespace App\Presenters;

use App\Model\Listings;
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
    
    protected $listings;

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
                $author = $this->orders->getOrderDetails($id)["author"];
                $escrowed = $this->wallet->getEscrowed($id);
                $this->orders->orderFinalize($id);
                $this->wallet->moveFunds("escrow", $author, $escrowed);
                $this->wallet->changeTransactionState("finished", $id);
                $this->flashMessage("Vaše objednávka byla finalizována!");
                $this->redirect("Orders:Feedback", $id);
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

    public function actionView($id){
        
      //  dump($this->wallet->getBalance("fagan23"));
        
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
       $this->orders->writeDisputeContents($id, $values["complaintMessage"],
               $timestamp, $autor);

       $this->redirect("Orders:dispute", $id);
    }
    
    public function actionDispute($id){
        
        $login = $this->hlp->logn();
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
    
    public function actionFeedback($id){
        $finalized = $this->orders->isOrderFinalized($id);
        $hasFeedback = $this->orders->hasFeedback($id);
        
        if (!($finalized && !$hasFeedback)){
            $this->redirect("Orders:in");
        } else {
            $this->hlp->sets("order", array("orderid" => $id));
        }
    }
    
    protected $feedbackType = array("positive" => "Pozitivní", 
                                    "neutral" => "Neutrální", "negative" => "Negativní");
    
    private $feedbackMarks = array("1/5" => "1/5 - Velmi špatné", "2/5" => "2/5 - Špatné",
            "3/5" => "3/5 - Dostačující", "4/5" => "4/5 - Dobré","5/5" => "5/5 - Výborné");
    
    public function createComponentFeedbackForm(){
        $form = new Form();
        $form->addRadioList("type", "Vaše zkušenost:", $this->feedbackType)
             ->addRule($form::FILLED, "Prosím zvolte možnost odpovídající Vaší zkušenosti");
        
       krsort($this->feedbackMarks);
       $marks = $this->feedbackMarks;
        
        $form->addSelect("postage", "Rychlost doručení:", $marks);
        $form->addSelect("stealth", "Balení & Stealth:", $marks);
        $form->addSelect("quality", "Kvalita produktu:", $marks);
        $form->addTextArea("feedback_text", "Popište Vaši zkušenost:", 60, 10);
        $form->addSubmit("odeslat", "Odeslat Feedback!");
        $form->onSuccess[] = array($this, "feedbackSuccess");
        $form->onValidate[] = array($this, "feedbackValidate");
        
        return $form;
    }
    
    public function feedbackValidate($form){
        $values = $form->getValues(TRUE);
      
        if (!key_exists($values["type"], $this->feedbackType)){
            $form->addError("Detekovány úpravy formuláře na straně klienta! [Radio]");
        }
     
        $index = array("postage", "stealth", "quality");
        
        foreach($index as $i){
            
            if (!key_exists($values[$i], $this->feedbackMarks)){
                $form->addError("Detekovány úpravy formuláře na straně klienta! [Select]");
            }
        }
    }
    
    public function feedbackSuccess($form){
        $values = $form->getValues(TRUE);
          
        if ($values["feedback_text"] == ""){
            $values["feedback_text"] = "Uživatel nezanechal žádný komentář";
        }
        
        $orderId = $this->hlp->sess("order")->orderid;
  
        $values["order_id"] = $orderId;
        $values["listing_id"] = $this->orders->getOrderDetails($orderId)["listing_id"];
        $values["buyer"] = $this->hlp->logn();

        $this->orders->saveFeedback($values);
        $this->flashMessage("Feedback úspěšně uložen");
        $this->redirect("Orders:in");
    }
}