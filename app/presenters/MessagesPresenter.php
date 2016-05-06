<?php

namespace App\Presenters;

use Nette;
use App\Models\Messages;

/**
 * 
 * @what Private messages Implementation
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class MessagesPresenter extends ProtectedPresenter
{
    /** @var Models\Messages */
    protected $messages;
    
    /** @persistent */
    public $odpoved = 0;
    
    public function injectBaseModels(\App\Model\Messages $msg)
    {
        $this->messages = $msg;
    }
  
    public function setReply($msgdata){
        
        $section = $this->getSession()->getSection('msgs');
        $section->prijemce = $this->messages->getReplyRecipient($msgdata['userid']);
        $section->titulek = "Re: ". $msgdata['title'];
        $section->zprava = $msgdata['message'];       
 
            dump($section->prijemce['login']);
            
            dump($section->titulek);
            
            dump($section->zprava);        
    }
    
    public function createComponentMessagesForm(){
        $form = new Nette\Application\UI\Form;
        
        $form->addText('recipient', 'Příjemce:');
        $form->addText('title', 'Titulek:');
        $form->addTextArea('message', 'Text zprávy:');
        $form->addSubmit('Odeslat');
        
        $section = $this->getSession()->getSection('msgs');
        
              
        if ($this->odpoved == 1){
                $form->setDefaults(array(
                    'recipient' => $section->prijemce['login'],
                    'title' => $section->titulek,
                    'message' => $section->zprava));
        }  
        
        unset($section->prijemce);
        unset($section->titulek);
        unset($section->zprava);
        
        $form->onValidate[] = array($this, 'messageFormValidate');
        $form->onSuccess[] = array($this, 'messageFormSuccess');

        return $form;
    }
    
    public function createComponentReplyForm() {
        $form = new Nette\Application\UI\Form;
        
        $form->addTextArea('reply', 'Text zprávy:');
        $form->addSubmit('Odeslat');
        
        return $form;
    }
    
    private function returnId(){
        return $this->getUser()->getIdentity()->getId();
    }
    
    public function renderIn(){
      $id =  $this->returnId();   
      $this->template->unread = $this->messages->unreadMessages($id);
      $this->template->read = $this->messages->readMessages($id);
    }
    
    public function renderSent(){
        $id =  $this->returnId(); 
        $this->template->sent = $this->messages->sentMessages($id);
    }
    
    public function renderView($messageid){
        
       $discussion = $this->messages->getNarrators($messageid);
       $userid = $this->returnId();     
         
       if($discussion){
           if($discussion['user1']==$userid or $discussion['user2']==$userid){
               
               //zabranuje cteni smazanych zprav
               if (($discussion['user1'] == $userid && $discussion['sender_deleted'] == "no") 
                       || ($discussion['user2'] == $userid && $discussion['receiver_deleted'] == "no")){
                        
                        //odkaz "odpovedet" se nezobrazi pokud je to sender
                        $this->template->reply = 0;
                   
                    if($discussion['user2']== $userid){
                        
                        //flag to determine it's receiver in the model
                        $flag = 1;
                       
                        $this->messages->userRead($messageid, $flag);
                        
                        //pokud receiver tak zobrazit
                        $this->template->reply = 1;
                    } 
                    
                    $this->template->msgdata = $this->messages->getListOfMessages($messageid);
               } else {
                   $this->redirect('Messages:in');
               }
           } else {
               $this->redirect('Messages:in');
           }
       } else {
           $this->redirect('Messages:in');
       }
    }
    
    public function handleDelete($id){
             
         $request = $_SERVER['REQUEST_URI'];
         
         if (strpos($request, 'sent')){
      
             $this->messages->senderDeleteMessage($id);
         } else {
             $this->messages->receiverDeleteMessage($id);
         }      
    }
    
    public function messageFormValidate($form){
        
        $values = $form->getValues();
        $id =  $this->returnId();
        
        if (strcmp($values->recipient, "") !== 0){
            if (strcmp($values->title, "") !== 0){
                if (strcmp($values->message, "") !== 0){
                    try {
                        $this->messages->sendMessage($values->recipient, $values->title,
                        $values->message, $id);
                    } catch (\Exception $e) {
                        $form->addError($e->getMessage());
                    }
                } else {
                    $form->addError('Zprava znaky...');
                }
            } else {
                $form->addError('Vyplnte titulek.');
            }
        } else {
            $form->addError('Vyplnte prijemce');
        }
    }
    
    public function messageFormSuccess($form){
        $this->flashMessage("Zprava odeslana.");
        $this->redirect("Messages:in");
    }
}