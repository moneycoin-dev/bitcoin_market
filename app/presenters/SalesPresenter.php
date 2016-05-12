<?php

namespace App\Presenters;

use Nette\Application\UI\Form;
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
     * Pending sales page renderer
     * @param int $page
     */
    public function renderIn($page = 1){
        $this->oh->ordersRenderer($page, "pending", TRUE);
    }
    
    /**
     * Finished sales page renderer
     * @param int $page
     */
    public function renderClosed($page = 1){
        $this->oh->ordersRenderer($page, "closed", TRUE);
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
            
            $this->orders->changeStatus($id, $values['status']);
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
           if ($this->orders->getStatus($id) == "pending"){
              $this->setOrderId($id); 
           } else {         
              $this->redirect("Orders:in");
           }
           
       } else {
            $this->redirect("Orders:in");
       }    
    }
}