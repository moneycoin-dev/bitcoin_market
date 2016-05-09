<?php

namespace App\Presenters;

use App\Model\Listings;
use App\Forms\FeedbackFormFactory;
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
    
    protected $listings, $fFactory;

    public function injectListings(Listings $l){
        $this->listings = $l;
    }
    
    public function injectFeedbackForm(FeedbackFormFactory $fFactory){
        $this->fFactory = $fFactory;
    }
    
    public function isFinalized($id){
        return $this->orders->isFinalized($id);
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
        return $this->orders->getStatus($this->getOrderId());
    }
   
    /**
     * Renders user orders and paginates results
     * 
     * @param int $active Paginator page for active orders
     * @param int $closed Paginator page for closed orders
     */
    public function renderIn($active = 1, $closed = 1){
        
        $pagActive = new Paginator();
        $pagActive->setItemsPerPage(2);
        $pagActive->setPage($active);
        
        $pagClosed = new Paginator();
        $pagClosed->setItemsPerPage(2);
        $pagClosed->setPage($closed);
      
        $login = $this->hlp->logn();
        $pendingOrders = $this->orders->getOrders($login,"pending", $pagActive);
        $closedOrders = $this->orders->getOrders($login, "closed", $pagClosed);
        $disputes = $this->orders->getOrders($login, "dispute");
       
        //set paginator itemCount after paginator was used in model
        $pagActive->setItemCount(count($pendingOrders));
        $pagClosed->setItemCount(count($closedOrders));
        
        //store page count into session
        //doesn't render paginator on subsequent pages without this code
        $session = $this->hlp->sess("paginator");
        
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

        $this->hlp->sess("feedback")->remove();
    }
    
    /**
     * Creates Finalize Form For escrowed orders
     * @return Form
     */
    public function createComponentFinalizeForm(){
        
        $form = new Form();
        $form->addTextArea("buyer_notes", "Buyer notes:");
        
        if ($this->shippedStatus() == "Shipped"){
               
            $form->addSubmit("dispute", "Dispute")->onClick[] = function(){

                $id = $this->getOrderId();   
                $this->orders->changeStatus($id, "dispute");
                $this->redirect("Orders:dispute", $id);
            };

            $form->addSubmit("finalize", "Finalize")->onClick[] = function(){
                
                $id = $this->getOrderId();
                $author = $this->orders->getDetails($id)["author"];
                $escrowed = $this->wallet->getEscrowed($id);
                $this->orders->finalize($id);
                $this->wallet->moveFunds("escrow", $author, $escrowed);
                $this->wallet->changeTransactionState("finished", $id);
                $this->flashMessage("Vaše objednávka byla finalizována!");
                $this->redirect("Orders:Feedback", $id);
            };    
        }    
        return $form;
    }
    
    /**
     * Creates form for partial Escrow Release
     * @return Form
     */
    public function createComponentPartialReleaseForm(){ 
        $form = new Form();
        
        if ($this->shippedStatus() == "Shipped"){
           
            $form->addText("amount", "Částka k uvolnění:");
            $form->addSubmit("submit", "Uvolnit");
        }   
         return $form;
    }
    
    /**
     * Displays order info and makes sure
     * that only buyer and vendor can view particular order
     * 
     * @param int $id order id from URL
     */
    public function actionView($id){
        
        $this->setOrderId($id);     
        $login = $this->hlp->logn();
        $order = $this->orders->getDetails($id);
        $buyer_notes = $this->orders->getNotesLeft($id);
        
       $this->getComponent("finalizeForm")->getComponents()["buyer_notes"]
               ->setValue($buyer_notes);
        
        if ($order["buyer"] == $login || $order["author"] == $login){
            
            
            $this->template->isVendor = $this->listings->isVendor($login);
            $this->template->isFinalized = $this->orders->isFinalized($id);
            $this->template->orderInfo = $order;
            $this->template->fdbk = FALSE;
            
            //if changes are 0 give option to change FE feedback 
            if($order["buyer"] == $login){
                if ($order["FE"] == "yes" && $this->isFbChA($id)){
                   $this->template->fdbk  = TRUE;
                }
            }
            
        } else {
            $this->redirect("Orders:in");
        }
    }
    
    /**
     * Creates Complaint Form
     * @return Form
     */
    public function createComponentDisputeComplaint(){
        $form = new Form();
        $form->addTextArea("complaintMessage", "Text zprávy" );
        $form->addSubmit("send", "Odeslat");
        
        $form->onSuccess[] = array($this, "sendComplaint");
        
        return $form;
    }
    
    /**
     * Complaint Form Success callback
     * Saves data into database and redirects to dispute chat
     * 
     * @param Form $form
     */
    public function sendComplaint($form){
       $values = $form->getValues(TRUE);
       $id =  $this->getOrderId();     
       $timestamp = time();
       $autor = $this->getUser()->getIdentity()->login;
       $this->orders->saveDisputeContents($id, $values["complaintMessage"],
               $timestamp, $autor);

       $this->redirect("Orders:dispute", $id);
    }
    
    /**
     * Lets user create dispute if he is not satisfied
     * Makes sure only order participants can view it
     * 
     * @param int $id order id from URL
     */
    public function actionDispute($id){
        
        $login = $this->hlp->logn();
        $orderStatus = $this->orders->getStatus($id);
        $participants = $this->orders->getParticipants($id);
        
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
    
    /**
     * Checks if user is able to modify feedback
     * 
     * @param int $id order id for getting feedback info
     * @return bool
     */
    private function isFbChA($id){
        return $this->orders->getFbChanges($id) == 0 ? TRUE : FALSE;
    }
    
    /**
     * Determines what type of feedback form create
     * Based on session settings and redirect malicious users
     * 
     * @param int $id order id from URL
     */
    public function actionFeedback($id){
        $finalized   = $this->orders->isFinalized($id);
        $hasFeedback = $this->orders->hasFeedback($id);
        $details = $this->orders->getDetails($id);
        
        $fe = $details["FE"] == "yes" ? TRUE : FALSE;
        $isBuyer = $details["buyer"] == $this->hlp->logn() ? TRUE : FALSE;
              
        $this->hlp->sets("feedback", array("orderid" => $id));
        
        if ($isBuyer){
            if ($finalized){        
                if ($hasFeedback && $fe){
                    //FE order has ability to change feedback one time
                    if ($this->isFbChA($id)){
                        $this->hlp->sets("feedback", array("FEedit" => TRUE));
                        $this->orders->fbInc($id); //inc fb change counter
                    } else {
                        $this->redirect("Orders:in");
                    }
                }
                
                if ($hasFeedback && !$fe){
                    //escrowed order has no ability to change feedback
                    $this->redirect("Orders:in");
                }

                if (!$hasFeedback && $fe){
                    $this->hlp->sets("feedback", array("FE" => TRUE));
                }

                if (!$hasFeedback && !$fe){
                    $this->hlp->sets("feedback", array("escrow" => TRUE));
                }        
            } else {
                $this->redirect("Orders:in");
            }
        } else {
            $this->redirect("Orders:in");
        }
    }
    
    /**
     * Creates form based on settings from 
     * actionFeedback
     * 
     * @return Form
     */
    public function createComponentFeedbackCreate(){
        
        $sess = $this->hlp->sess("feedback");
        $orderid = $sess->orderid;
       
        //set presenter as param due to
        //form handler function dependencies
        $this->fFactory->setPresenter($this->getPresenter());
       
        //finalize early first feedback attempt
        if (isset($sess->FE)){
            $form = $this->fFactory->create($sess->FE); 
            $form->addSubmit("odeslat", "Odeslat Feedback!");
        }
        
        //finalize early feedback update after goods received
        if (isset($sess->FEedit)){
            $fdb = $this->orders->getFeedback($orderid);           
            $form = $this->fFactory->create($sess->FEedit, $fdb);
            $form->addSubmit("upravit", "Upravit Feedback!");
        }
        
        //feedback after escrow order has been finalized
        if (isset($sess->escrow)){
            $form = $this->fFactory->create();
            $form->addSubmit("odeslat", "Odeslat Feedback!");
        }
       
        return $form;
    }
    
    /**
     * Destructs session settings set in actionFeedback
     */
    public function __destruct(){
        $this->hlp->sess("feedback")->remove();
    }
}