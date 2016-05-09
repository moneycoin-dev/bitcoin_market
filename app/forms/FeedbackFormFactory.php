<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;

/**
 * 
 * @what Factory for Feedback form creation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class FeedbackFormFactory extends Nette\Object
{
    /** @var array Type of possible order feedback */
    protected $fbType = array("positive" => "Pozitivní", 
            "neutral" => "Neutrální", "negative" => "Negativní");
    
    /** @var array Possible mark feedback for order */
    protected $fbMarks = array("1/5" => "1/5 - Velmi špatné", "2/5" => "2/5 - Špatné",
            "3/5" => "3/5 - Dostačující", "4/5" => "4/5 - Dobré","5/5" => "5/5 - Výborné");
    
    /**
     * Assembles form controls and eventually fill the
     * with values from database
     * 
     * @param Form $form
     * @param array $dbVal values from database
     */
    public function addControls($form, $dbVal = NULL){
        
        $controls = array();
        $controls["type"]    = "\$form->addRadioList(\"type\", \"Vaše zkušenost\", \$this->fbType)";
        $controls["postage"] = "\$form->addSelect(\"postage\", \"Rychlost doručení:\", \$this->fbMarks)";
        $controls["stealth"] = "\$form->addSelect(\"stealth\", \"Balení & Stealth:\", \$this->fbMarks)";
        $controls["quality"] = "\$form->addSelect(\"quality\", \"Kvalita produktu:\", \$this->fbMarks)";
        $setter = "->setValue(\$dbVal[\$key])";
        $rule = "->addRule(\$form::FILLED, \"Prosím zvolte možnost odpovídající Vaší zkušenosti\");";
        
        foreach ($controls as $key => $value){     
            $string  = $controls[$key];
            
            if ($dbVal){
                $string .= $setter;
            }
            
            $string .= $rule;
            eval($string);
        }
    }
    
    /** @var Presenter Storage of dependencies from LP */
    protected $presenter;
    
    /** 
     * Setter for Listings dependencies
     * @param Presenter $presenter
     */
    public function setPresenter($presenter){
        $this->presenter = $presenter;
    }
    
    /**
     * Getter for $presenter must be set
     * otherwise Nette\Object throws write only error
     * 
     * @return Presenter
     */
    public function getPresenter(){
        return $this->presenter;
    }
    
    /**
     * Creates form according form type needed
     * Possible types are edit, fe, escrow
     * 
     * @param bool $fe flag only
     * @param array $fdb values from database
     * @return Form
     */
    public function create($fe = NULL, $fdb = NULL)
    {
        //sort options from best to worst
        krsort($this->fbMarks);
        
        $form = new Form();
        
        //finalize early first attempt to create feedback
        if ($fe && !$fdb){
           $this->addControls($form);
           $form->addTextArea("feedback_text", "Popište Vaši zkušenost:", 60, 10)
                 ->setValue("Finalize Early dummy Feedback");
        } 
        
        //finalize early attempt to edit feedback after receiving goods
        if ($fe && $fdb){
            
            $fdb = $fdb[0]; 
            $this->addControls($form, $fdb);    
            $form->addTextArea("feedback_text", "Popište Vaši zkušenost:", 60, 10)
                 ->setValue($fdb->feedback_text);      
        }
        
        //leave feedback when escrow order has been finished
        if (!$fe && !$fdb){
            $this->addControls($form);
            $form->addTextArea("feedback_text", "Popište Vaši zkušenost:", 60, 10);
        }
        
        $form->onValidate[] = array($this, "feedbackValidate");
        $form->onSuccess[] = array($this, "feedbackSuccess");

        return $form;
    }
    
    /**
     * Validates FeedbackForm
     * Check for client side modifications of control's values
     * 
     * @param Form $form
     */
    public function feedbackValidate($form){
        $values = $form->getValues(TRUE);
      
        if (!key_exists($values["type"], $this->fbType)){
            $form->addError("Detekovány úpravy formuláře na straně klienta! [Radio]");
        }
     
        $index = array("postage", "stealth", "quality");
        
        foreach($index as $i){
            
            if (!key_exists($values[$i], $this->fbMarks)){
                $form->addError("Detekovány úpravy formuláře na straně klienta! [Select]");
            }
        }
    }
    
    /**
     * Stores or update database values from Feedback Form
     * @param Form $form
     */
    public function feedbackSuccess($form){
        $values = $form->getValues(TRUE);
          
        if ($values["feedback_text"] == ""){
            $values["feedback_text"] = "Uživatel nezanechal žádný komentář";
        }
        
        $presenter = $this->getPresenter();
        $orderid = $presenter->hlp->sess("feedback")->orderid;
        $values["order_id"] = $orderid;
        $values["listing_id"] = $presenter->orders->getDetails($orderid)["listing_id"];
        $values["buyer"] = $presenter->hlp->logn();
        
        //button checking for different types of form
        $send = isset($form["odeslat"]) ? $form["odeslat"] : NULL;
        
        if ($send){    
            if ($send->submittedBy){
                $presenter->orders->saveFeedback($values);
            }
        }  else {
            if ($form["upravit"]->submittedBy){
                $presenter->orders->updateFeedback($orderid, $values);
            }
        }
 
        $presenter->flashMessage("Feedback úspěšně uložen");
        $presenter->redirect("Listings:view", $values["listing_id"]); 
    }
}