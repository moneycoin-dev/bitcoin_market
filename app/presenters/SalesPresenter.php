<?php

namespace App\Presenters;

use Nette\Application\UI\Form;
use App\Model\Settings;
use App\Helpers\OrderHelper;

/**
 * 
 * @what Vendor sales Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class SalesPresenter extends ProtectedPresenter {
    
    /**
     * Injected Helper instance
     * @var OrderHelper 
     */
    protected $oh;
    
    /** @var App\Model\Settings */
    protected $s;
    
    /**
     * Injects helper that takes care of
     * displaying paginated Sales
     * 
     * @param OrderHelper $oh
     */
    public function injectOrderHelper(OrderHelper $oh){
        $this->oh = $oh;
    }
    
    /**
     * Model dependency injection
     * @param \App\Presenters\Settings $s
     */
    public function injectSettings(Settings $s){
        $this->s = $s;
    }
        
    /**
     * Helper function
     * Returns orderID from session
     * 
     * @return int
     */
    private function getOrderId(){
        $session = $this->hlp->sess("orders");
        return $session->orderID;
    }
    
    /**
     * Helper function
     * Sets session with given id
     * 
     * @param int $id
     */
    private function setOrderId($id){
        $this->hlp->sets("orders", array("orderID" => $id));
    }
    
    /**
     * Sets variable $user for template
     */
    public function beforeRender(){
        $this->template->user = $this->hlp->logn();
    }
   
    /**
     * Pending sales page renderer
     * @param int $page
     */
    public function renderIn($page = 1){
        $this->oh->ordersRenderer($page, "pending", TRUE);
        $this->oh->totalsRenderer("pending", TRUE);
    }
    
    /**
     * Finished sales page renderer
     * @param int $page
     */
    public function renderClosed($page = 1){
        $this->oh->ordersRenderer($page, array("Shipped", "closed"), TRUE);
        $this->oh->totalsRenderer("closed", TRUE);
    }
    
    /**
     * Creates Order processing Form
     * with ability to cancel proccessing
     * 
     * @return Form
     */
    public function createComponentProcessSaleForm() {
        $form = new Form();

        $form->addTextArea("seller_notes", "Seller notes text:");
        $form->addSelect("status", "Status")->setItems(
                array("Decline", "Shipped"), FALSE);
        
        $form->addSubmit("submit", "Potvrdit");
        $form->addSubmit("cancel", "Zpět na nevyřízené")
             ->setValidationScope(false)
             ->onClick[] = function(){

                $this->flashMessage("Vyřízení objednávky zrušeno.");
                $this->redirect("Sales:in");
            };

        $form->onSuccess[] = array($this, "processSaleSuccess");

        return $form;
    }
    
    /**
     * Sale processing success event
     * @param Form $form
     */
    public function processSaleSuccess($form){                
        $values = $form->getValues(TRUE);
        
        if ($form['submit']->submittedBy){
            
            $id = $this->getOrderId();
                 
            if($values["status"] == "Shipped"){
                $this->orders->setShipped($id);
                
                if ($this->orders->isFe($id)){
                    $this->orders->changeStatus($id, "closed");
                } else {
                    $date = $this->orders->setAuFinalizeDate($id);
                    $this->hlp->newCronJob("autoFinalize", $date, $id);
                }
                
            } else {
                $this->orders->changeStatus($id, "Decline");
            }
            
            $this->orders->saveSellerNotes($id, $values['seller_notes']);
            
            unset($this->hlp->sess("orders")->orderID);
            
            $this->flashMessage("Objednávka úspěšně vyřízena!");
            $this->redirect("Sales:in");
        }
    }
    
    /**
     * Determines if user is the vendor with
     * privilege to process the sale
     * 
     * @param int $id
     */
    public function actionProcess($id){
        
       $login = $this->hlp->logn();
       
       if ($this->orders->isOwner($id, $login)){     
           if ($this->orders->hasStatus($id, "pending")){
              $this->setOrderId($id);     
              
              $buyer = $this->orders->getDetails($id)["buyer"];
              $bDetails = $this->s->getUserDetails($buyer);
              $this->template->bDetails = $bDetails;
           } else {         
              $this->redirect("Sales:in");
           }       
       } else {
            $this->redirect("Sales:in");
       }    
    }
}