<?php

namespace App\Presenters;

use Nette\Utils\Paginator;
use Nette\Application\UI\Form;

/**
 * 
 * @what Vendor sales Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class SalesPresenter extends ProtectedPresenter {
        
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
     * Helper function
     * Gets URL path strips slashes
     * 
     * @return string
     */
    private function getPathInfo(){
        $path = $this->getHttpRequest()
                    ->getUrl()
                    ->getPathInfo();
        
        return str_replace("/", "", $path);   
    }
    
    /**
     * According to actual URL path sets session variables
     * which are later used, to determine page count of paginator
     * 
     * @param Paginator $paginator
     * @param Session $session
     */
    private function pagSession($paginator, $session){
        
        $path = $this->getPathInfo();

        if (is_null($session->$path)){
            $this->hlp->sets("paginator", array($path => 
                $paginator->getPageCount()));
        }

        $this->hlp->sets("paginator", 
                array("totalOrders" => $session->$path)); 
    }
    
    /**
     * Determines if user accessing directly non first
     * paginator page, and redirect to create neccessary session
     * 
     * @param int $page
     * @param string $origP
     */
    private function determineRedirect($page, $origP){
        $session = $this->hlp->sess("paginator");
        $rdr = $session->rdr;
        $ord = $session->totalOrders;
        
        if ($page > 1 && !$rdr){
            if (is_null($ord) || $ord == 0){
                $this->hlp->sets("paginator", 
                        array("rdr" => TRUE, "page" => $page));

                $this->redirector($origP);
            }
        }   
    }
    
    /**
     * Redirects according to URL paths from which
     * it was accessed. Gets you to the desired paginator 
     * page after sessions was set.
     * 
     * @param string $origP
     * @param int $page
     */
    private function redirector($origP, $page = NULL){  
        $sess = $this->hlp->sess("paginator");
        $sess->rdrSTOP = isset($page) ? TRUE : FALSE;
        
        if ($origP == "sales"){
            $this->redirect("Sales:in", $page);
        } else {
            $this->redirect("Sales:closed", $page);
        } 
    }
    
    /**
     * Main sales rendering logic
     * Renders Pending or finished sales according 
     * to parameters
     * 
     * @param int $page
     * @param string $type
     */
    private function renderer($page, $type){
        
        $session = $this->hlp->sess("paginator");
        
        //get URL path from which renderer was loaded
        $origP = $this->getPathInfo();
   
        //check if this is direct page linking
        //eventually redirect
        if (!$session->rdr){
            $this->determineRedirect($page, $origP);
        }
        
        //start paginator construction
        $paginator = new Paginator();
        $paginator->setItemsPerPage(4);
        $paginator->setPage($page);

        //get paginated data
        $orders = $this->orders->getOrders($this->hlp->logn(),
                  $type, $paginator, TRUE);

        $paginator->setItemCount(count($orders)); 
        $this->pagSession($paginator, $session);
        
        //if it was direct page linking
        //redirect to user's desired page
        //necessary to set paginator page counter session
        if (!$session->rdrSTOP){    
            $rdrPage = $session->page;
            $this->redirector($origP, $rdrPage);
        }
       
        //finally render template variables
        $this->template->$type = TRUE;
        $this->template->orders = $orders; 
        $this->template->totalOrders = $session->totalOrders;                   
        $this->template->page = $page;
    }
   
    /**
     * Pending sales page renderer
     * @param int $page
     */
    public function renderIn($page = 1){
        $this->renderer($page, "pending");
    }
    
    /**
     * Finished sales page renderer
     * @param int $page
     */
    public function renderClosed($page = 1){
        $this->renderer($page, "closed");
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